import os
import subprocess
import time
import json
import base64
import asyncio
import websockets
import requests

CHROME_PATH = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
PROFILE_DIR = r"C:\xampp\htdocs\CivicConnect\scratch\chrome_profile"
SCREENSHOT_DIR = r"C:\xampp\htdocs\CivicConnect\assets\screenshots"
os.makedirs(SCREENSHOT_DIR, exist_ok=True)
os.makedirs(PROFILE_DIR, exist_ok=True)

async def send_cdp(ws, method, params=None, msg_id=1):
    req = {"id": msg_id, "method": method, "params": params or {}}
    await ws.send(json.dumps(req))
    while True:
        res = json.loads(await ws.recv())
        if res.get("id") == msg_id:
            return res.get("result", {})

async def capture_page(ws, url, out_path, eval_js=None, wait_sec=2.0):
    await send_cdp(ws, "Page.navigate", {"url": url}, 1)
    await asyncio.sleep(wait_sec)
    if eval_js:
        await send_cdp(ws, "Runtime.evaluate", {"expression": eval_js, "awaitPromise": True}, 2)
        await asyncio.sleep(1.0)
    res = await send_cdp(ws, "Page.captureScreenshot", {"format": "png", "quality": 100}, 3)
    img_b64 = res.get("data")
    if img_b64:
        with open(out_path, "wb") as f:
            f.write(base64.b64decode(img_b64))
        print(f"Captured: {out_path} ({os.path.getsize(out_path)} bytes)", flush=True)

async def main():
    cmd = [
        CHROME_PATH,
        "--headless=new",
        "--remote-debugging-port=9222",
        "--disable-gpu",
        "--no-sandbox",
        "--window-size=1440,900",
        f"--user-data-dir={PROFILE_DIR}"
    ]
    proc = subprocess.Popen(cmd, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    time.sleep(2)

    try:
        res = requests.put("http://127.0.0.1:9222/json/new?about:blank")
        tab_info = res.json()
        ws_url = tab_info["webSocketDebuggerUrl"]

        async with websockets.connect(ws_url, max_size=20 * 1024 * 1024) as ws:
            await send_cdp(ws, "Page.enable")
            await send_cdp(ws, "Runtime.enable")
            await send_cdp(ws, "Emulation.setDeviceMetricsOverride", {
                "width": 1440,
                "height": 900,
                "deviceScaleFactor": 1,
                "mobile": False
            })

            # 1. Landing Page Hero
            await capture_page(
                ws,
                "http://localhost/CivicConnect/",
                os.path.join(SCREENSHOT_DIR, "01_landing_hero.png"),
                wait_sec=2.0
            )

            # 2. Login Page
            await capture_page(
                ws,
                "http://localhost/CivicConnect/login.php",
                os.path.join(SCREENSHOT_DIR, "02_login_page.png"),
                wait_sec=1.5
            )

            # 3. Citizen Login & Dashboard
            citizen_login_js = """
            document.querySelector("input[name='email']").value = 'citizen@civicconnect.gov';
            document.querySelector("input[name='password']").value = 'Password@123';
            document.querySelector("button[type='submit']").click();
            """
            await send_cdp(ws, "Page.navigate", {"url": "http://localhost/CivicConnect/login.php"})
            await asyncio.sleep(1.5)
            await send_cdp(ws, "Runtime.evaluate", {"expression": citizen_login_js})
            await asyncio.sleep(2.0)
            await capture_page(
                ws,
                "http://localhost/CivicConnect/citizen/dashboard.php",
                os.path.join(SCREENSHOT_DIR, "03_citizen_dashboard.png"),
                wait_sec=2.0
            )

            # 4. Citizen Report Issue Page with Leaflet Map
            await capture_page(
                ws,
                "http://localhost/CivicConnect/citizen/report_issue.php",
                os.path.join(SCREENSHOT_DIR, "04_citizen_report_issue.png"),
                eval_js="""
                const cat = document.querySelector("select[name='category_id']");
                if (cat && cat.options.length > 1) { cat.selectedIndex = 1; cat.dispatchEvent(new Event('change')); }
                """,
                wait_sec=2.5
            )

            # 5. Citizen Complaint Timeline / Details View
            await capture_page(
                ws,
                "http://localhost/CivicConnect/citizen/complaint_details.php?id=1",
                os.path.join(SCREENSHOT_DIR, "05_citizen_complaint_timeline.png"),
                wait_sec=2.0
            )

            # 6. Citizen My Complaints List
            await capture_page(
                ws,
                "http://localhost/CivicConnect/citizen/my_complaints.php",
                os.path.join(SCREENSHOT_DIR, "06_citizen_my_complaints.png"),
                wait_sec=2.0
            )

            # 7. Official Login & Dashboard
            await send_cdp(ws, "Page.navigate", {"url": "http://localhost/CivicConnect/logout.php"})
            await asyncio.sleep(1.0)
            await send_cdp(ws, "Page.navigate", {"url": "http://localhost/CivicConnect/login.php"})
            await asyncio.sleep(1.5)
            official_login_js = """
            document.querySelector("input[name='email']").value = 'pwd.officer@civicconnect.gov';
            document.querySelector("input[name='password']").value = 'Password@123';
            document.querySelector("button[type='submit']").click();
            """
            await send_cdp(ws, "Runtime.evaluate", {"expression": official_login_js})
            await asyncio.sleep(2.0)
            await capture_page(
                ws,
                "http://localhost/CivicConnect/official/dashboard.php",
                os.path.join(SCREENSHOT_DIR, "07_official_dashboard.png"),
                wait_sec=2.0
            )

            # 8. Official Complaints Work Queue
            await capture_page(
                ws,
                "http://localhost/CivicConnect/official/complaints.php",
                os.path.join(SCREENSHOT_DIR, "08_official_queue.png"),
                wait_sec=2.0
            )

            # 9. Official Complaint Inspection & Remarks Update
            await capture_page(
                ws,
                "http://localhost/CivicConnect/official/complaint_details.php?id=1",
                os.path.join(SCREENSHOT_DIR, "09_official_inspection.png"),
                wait_sec=2.0
            )

            # 10. Admin Login & Command Center
            await send_cdp(ws, "Page.navigate", {"url": "http://localhost/CivicConnect/logout.php"})
            await asyncio.sleep(1.0)
            await send_cdp(ws, "Page.navigate", {"url": "http://localhost/CivicConnect/login.php"})
            await asyncio.sleep(1.5)
            admin_login_js = """
            document.querySelector("input[name='email']").value = 'admin@civicconnect.gov';
            document.querySelector("input[name='password']").value = 'Password@123';
            document.querySelector("button[type='submit']").click();
            """
            await send_cdp(ws, "Runtime.evaluate", {"expression": admin_login_js})
            await asyncio.sleep(2.0)
            await capture_page(
                ws,
                "http://localhost/CivicConnect/admin/dashboard.php",
                os.path.join(SCREENSHOT_DIR, "10_admin_command_center.png"),
                wait_sec=2.5
            )

            # 11. Admin Reports & Audit Analytics
            await capture_page(
                ws,
                "http://localhost/CivicConnect/admin/reports.php",
                os.path.join(SCREENSHOT_DIR, "11_admin_reports.png"),
                wait_sec=2.0
            )

            # 12. Admin Categories & Dynamic SLAs
            await capture_page(
                ws,
                "http://localhost/CivicConnect/admin/categories.php",
                os.path.join(SCREENSHOT_DIR, "12_admin_categories.png"),
                wait_sec=2.0
            )

        print("All screenshots successfully captured via CDP!", flush=True)

    finally:
        proc.terminate()
        try:
            proc.wait(timeout=2)
        except Exception:
            proc.kill()

if __name__ == "__main__":
    asyncio.run(main())
