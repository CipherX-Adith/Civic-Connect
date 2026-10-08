import os
import pptx
from pptx import Presentation
from pptx.util import Inches, Pt
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN, MSO_ANCHOR
from pptx.enum.shapes import MSO_SHAPE

def create_presentation():
    prs = Presentation()
    prs.slide_width = Inches(13.333)
    prs.slide_height = Inches(7.5)
    blank_layout = prs.slide_layouts[6] # Blank layout

    # Color Palette Constants
    PRIMARY_EMERALD = RGBColor(5, 150, 105)      # #059669
    ACCENT_GREEN = RGBColor(16, 185, 129)        # #10b981
    DARK_NAVY = RGBColor(15, 23, 42)             # #0f172a
    DARK_CARD = RGBColor(17, 28, 42)             # #111c2a
    LIGHT_BG = RGBColor(248, 250, 252)           # #f8fafc
    LIGHT_CARD = RGBColor(255, 255, 255)         # #ffffff
    LIGHT_MINT = RGBColor(236, 253, 245)         # #ecfdf5
    LIGHT_BLUE = RGBColor(239, 246, 255)         # #eff6ff
    LIGHT_AMBER = RGBColor(255, 251, 235)        # #fffbeb
    LIGHT_ROSE = RGBColor(255, 241, 242)         # #fff1f2
    LIGHT_PURPLE = RGBColor(245, 243, 255)       # #f5f3ff
    TEXT_MAIN = RGBColor(15, 23, 42)             # #0f172a
    TEXT_MUTED = RGBColor(100, 116, 139)         # #64748b
    BORDER_COLOR = RGBColor(226, 232, 240)       # #e2e8f0
    BORDER_MINT = RGBColor(167, 243, 208)        # #a7f3d0
    ACCENT_BLUE = RGBColor(37, 99, 235)          # #2563eb
    ACCENT_AMBER = RGBColor(245, 158, 11)        # #f59e0b
    ACCENT_ROSE = RGBColor(244, 63, 94)          # #f43f5e
    ACCENT_PURPLE = RGBColor(124, 58, 237)       # #7c3aed
    WHITE = RGBColor(255, 255, 255)

    LOGO_PATH = "assets/images/logo.png"
    SCREENSHOT_DIR = "assets/screenshots"

    def add_header(slide, slide_num_str, slide_title):
        # Top micro-bar
        top_bar = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.8), Inches(0.35), Inches(11.733), Inches(0.45))
        top_bar.fill.background()
        top_bar.line.fill.background()
        tf = top_bar.text_frame
        tf.word_wrap = True
        tf.margin_left = tf.margin_top = tf.margin_right = tf.margin_bottom = 0
        p = tf.paragraphs[0]
        p.text = "WEB PROGRAMMING MICROPROJECT PRESENTATION"
        p.font.name = "Plus Jakarta Sans"
        p.font.size = Pt(9.5)
        p.font.bold = True
        p.font.color.rgb = TEXT_MUTED

        # Right header - Group badge
        grp_box = slide.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(10.533), Inches(0.3), Inches(2.0), Inches(0.38))
        grp_box.fill.solid()
        grp_box.fill.fore_color.rgb = LIGHT_MINT
        grp_box.line.color.rgb = BORDER_MINT
        gtf = grp_box.text_frame
        gtf.margin_left = gtf.margin_top = gtf.margin_right = gtf.margin_bottom = 0
        gp = gtf.paragraphs[0]
        gp.text = "GROUP NO. 01"
        gp.alignment = PP_ALIGN.CENTER
        gp.font.name = "Plus Jakarta Sans"
        gp.font.size = Pt(9.5)
        gp.font.bold = True
        gp.font.color.rgb = PRIMARY_EMERALD

        # Slide Number + Title Header
        title_box = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.8), Inches(0.75), Inches(11.733), Inches(0.55))
        title_box.fill.background()
        title_box.line.fill.background()
        ttf = title_box.text_frame
        ttf.margin_left = ttf.margin_top = ttf.margin_right = ttf.margin_bottom = 0
        tp = ttf.paragraphs[0]
        
        # Slide number part
        r1 = tp.add_run()
        r1.text = f"{slide_num_str}  |  "
        r1.font.name = "Plus Jakarta Sans"
        r1.font.size = Pt(16.5)
        r1.font.bold = True
        r1.font.color.rgb = PRIMARY_EMERALD

        # Title part
        r2 = tp.add_run()
        r2.text = slide_title.upper()
        r2.font.name = "Plus Jakarta Sans"
        r2.font.size = Pt(16.5)
        r2.font.bold = True
        r2.font.color.rgb = DARK_NAVY

    def add_footer(slide):
        # Footer divider line
        line = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.8), Inches(6.92), Inches(11.733), Inches(0.015))
        line.fill.solid()
        line.fill.fore_color.rgb = BORDER_COLOR
        line.line.fill.background()

        # Left credits
        cred_box = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.8), Inches(7.0), Inches(7.5), Inches(0.32))
        cred_box.fill.background()
        cred_box.line.fill.background()
        ctf = cred_box.text_frame
        ctf.margin_left = ctf.margin_top = ctf.margin_right = ctf.margin_bottom = 0
        cp = ctf.paragraphs[0]
        cp.text = "TEAM: 1. ABHIRAM RAJESH (1)   |   2. ABHISHEK S (2)   |   3. ADITH S KUMAR (3)"
        cp.font.name = "Plus Jakarta Sans"
        cp.font.size = Pt(8.5)
        cp.font.bold = True
        cp.font.color.rgb = TEXT_MUTED

        # Right Tech Stack
        tech_box = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(8.5), Inches(7.0), Inches(4.033), Inches(0.32))
        tech_box.fill.background()
        tech_box.line.fill.background()
        ttf = tech_box.text_frame
        ttf.margin_left = ttf.margin_top = ttf.margin_right = ttf.margin_bottom = 0
        tp = ttf.paragraphs[0]
        tp.alignment = PP_ALIGN.RIGHT
        tp.text = "STACK: PHP 8 • MySQL • Leaflet.js • Chart.js • CSS3"
        tp.font.name = "Plus Jakarta Sans"
        tp.font.size = Pt(8.5)
        tp.font.bold = True
        tp.font.color.rgb = PRIMARY_EMERALD

    # =========================================================================
    # SLIDE 1: Title & Project Overview
    # =========================================================================
    slide1 = prs.slides.add_slide(blank_layout)
    
    top_bar1 = slide1.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.8), Inches(0.4), Inches(11.733), Inches(0.5))
    top_bar1.fill.background()
    top_bar1.line.fill.background()
    tf1 = top_bar1.text_frame
    p1 = tf1.paragraphs[0]
    p1.text = "WEB PROGRAMMING MICROPROJECT PRESENTATION"
    p1.font.name = "Plus Jakarta Sans"
    p1.font.size = Pt(11)
    p1.font.bold = True
    p1.font.color.rgb = TEXT_MUTED

    grp1 = slide1.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(10.533), Inches(0.35), Inches(2.0), Inches(0.4))
    grp1.fill.solid()
    grp1.fill.fore_color.rgb = LIGHT_MINT
    grp1.line.color.rgb = BORDER_MINT
    gp1 = grp1.text_frame.paragraphs[0]
    gp1.text = "GROUP NO. 01"
    gp1.alignment = PP_ALIGN.CENTER
    gp1.font.name = "Plus Jakarta Sans"
    gp1.font.size = Pt(10)
    gp1.font.bold = True
    gp1.font.color.rgb = PRIMARY_EMERALD

    # Left Container: Logo, Title & Value Prop
    if os.path.exists(LOGO_PATH):
        slide1.shapes.add_picture(LOGO_PATH, Inches(0.8), Inches(1.1), width=Inches(1.5), height=Inches(1.5))

    title_box1 = slide1.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(2.45), Inches(1.1), Inches(9.8), Inches(1.6))
    title_box1.fill.background()
    title_box1.line.fill.background()
    tframe1 = title_box1.text_frame
    tframe1.word_wrap = True
    tframe1.margin_left = tframe1.margin_top = tframe1.margin_right = tframe1.margin_bottom = 0
    
    tp1 = tframe1.paragraphs[0]
    r_c = tp1.add_run()
    r_c.text = "Civic"
    r_c.font.name = "Plus Jakarta Sans"
    r_c.font.size = Pt(36)
    r_c.font.bold = True
    r_c.font.color.rgb = DARK_NAVY

    r_conn = tp1.add_run()
    r_conn.text = "Connect"
    r_conn.font.name = "Plus Jakarta Sans"
    r_conn.font.size = Pt(36)
    r_conn.font.bold = True
    r_conn.font.color.rgb = PRIMARY_EMERALD

    tp1_sub = tframe1.add_paragraph()
    tp1_sub.text = "CLEANER COMMUNITIES. STRONGER TOMORROW."
    tp1_sub.font.name = "Plus Jakarta Sans"
    tp1_sub.font.size = Pt(12)
    tp1_sub.font.bold = True
    tp1_sub.font.color.rgb = ACCENT_BLUE

    tp1_desc = tframe1.add_paragraph()
    tp1_desc.text = "A smart, transparent public issue reporting & municipal governance portal bridging citizens with city departments for real-time tracking, geotagged evidence dispatching, and automated SLA accountability."
    tp1_desc.font.name = "Plus Jakarta Sans"
    tp1_desc.font.size = Pt(11.5)
    tp1_desc.font.color.rgb = TEXT_MUTED

    card_data = [
        ("Citizen Geo-Reporting", "Interactive Leaflet.js GPS map picker, photo evidence upload, 4-step streamlined grievance submission, and live status timeline.", LIGHT_MINT, BORDER_MINT, PRIMARY_EMERALD),
        ("Department Dispatch Queue", "Department-specific worklists (PWD, SWM, Water, Electrical), urgency escalation, nodal officer assignment, and verified resolution remarks.", LIGHT_BLUE, RGBColor(191, 219, 254), ACCENT_BLUE),
        ("City Governance Console", "Real-time municipal performance KPIs, Chart.js resolution load charts, configurable SLA policies, user roles, and 1-click audit CSV export.", LIGHT_AMBER, RGBColor(254, 240, 138), ACCENT_AMBER)
    ]

    card_w = Inches(3.75)
    card_gap = Inches(0.24)
    start_x = Inches(0.8)
    card_y = Inches(2.9)
    card_h = Inches(2.4)

    for i, (head, desc, bg, border, accent) in enumerate(card_data):
        cx = start_x + i * (card_w + card_gap)
        c_shape = slide1.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, cx, card_y, card_w, card_h)
        c_shape.fill.solid()
        c_shape.fill.fore_color.rgb = bg
        c_shape.line.color.rgb = border
        c_shape.line.width = Pt(1.5)

        ctf = c_shape.text_frame
        ctf.word_wrap = True
        ctf.margin_left = ctf.margin_right = ctf.margin_top = ctf.margin_bottom = Inches(0.22)

        p_h = ctf.paragraphs[0]
        p_h.text = head
        p_h.font.name = "Plus Jakarta Sans"
        p_h.font.size = Pt(13)
        p_h.font.bold = True
        p_h.font.color.rgb = accent

        p_d = ctf.add_paragraph()
        p_d.text = desc
        p_d.font.name = "Plus Jakarta Sans"
        p_d.font.size = Pt(10)
        p_d.font.color.rgb = TEXT_MAIN
        p_d.space_before = Pt(8)

    pills = [
        ("GPS Map Picker", PRIMARY_EMERALD),
        ("Auto SLA Routing", ACCENT_BLUE),
        ("SFW Media Blur", ACCENT_ROSE),
        ("Dark/Light Mode", DARK_NAVY),
        ("Live CSV Analytics", ACCENT_AMBER)
    ]
    pill_w = Inches(2.15)
    pill_gap = Inches(0.24)
    pill_y = Inches(5.5)
    for i, (pill_txt, p_col) in enumerate(pills):
        px = start_x + i * (pill_w + pill_gap)
        p_shape = slide1.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, px, pill_y, pill_w, Inches(0.45))
        p_shape.fill.solid()
        p_shape.fill.fore_color.rgb = WHITE
        p_shape.line.color.rgb = BORDER_COLOR
        ptf = p_shape.text_frame
        ptf.margin_left = ptf.margin_right = ptf.margin_top = ptf.margin_bottom = 0
        pp = ptf.paragraphs[0]
        pp.alignment = PP_ALIGN.CENTER
        pp.text = f"✔ {pill_txt}"
        pp.font.name = "Plus Jakarta Sans"
        pp.font.size = Pt(9.5)
        pp.font.bold = True
        pp.font.color.rgb = p_col

    add_footer(slide1)

    # =========================================================================
    # SLIDE 2: Problem Statement & Motivation
    # =========================================================================
    slide2 = prs.slides.add_slide(blank_layout)
    add_header(slide2, "02", "Problem Statement & Motivation")

    sec1_label = slide2.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(1.5), Inches(2.2), Inches(0.36))
    sec1_label.fill.solid()
    sec1_label.fill.fore_color.rgb = PRIMARY_EMERALD
    sec1_label.line.fill.background()
    s1_p = sec1_label.text_frame.paragraphs[0]
    s1_p.text = "Why Is It Needed?"
    s1_p.alignment = PP_ALIGN.CENTER
    s1_p.font.name = "Plus Jakarta Sans"
    s1_p.font.size = Pt(10.5)
    s1_p.font.bold = True
    s1_p.font.color.rgb = WHITE

    problems = [
        ("Lack of Transparency & Lost Complaints", "Traditional paper registers or phone calls offer zero status tracking. Citizens never know if an issue is being addressed or ignored.", LIGHT_ROSE, RGBColor(254, 205, 211), ACCENT_ROSE),
        ("Slow Cross-Department Dispatching", "Municipal issues (PWD roads, water leaks, streetlight failures) require manual triage, resulting in weeks of departmental ping-pong.", LIGHT_AMBER, RGBColor(254, 240, 138), ACCENT_AMBER),
        ("Inaccurate Location & Vague Proof", "Field repair crews struggle to locate specific potholes or broken cables when complaints lack precise GPS coordinates and photos.", LIGHT_BLUE, RGBColor(191, 219, 254), ACCENT_BLUE),
        ("No Accountability & SLA Audits", "Municipal administrators lack aggregated metrics to evaluate department resolution speeds, backlog sizes, or SLA compliance.", LIGHT_MINT, BORDER_MINT, PRIMARY_EMERALD)
    ]

    prob_w = Inches(2.78)
    prob_gap = Inches(0.2)
    for i, (title, desc, bg, border, acc) in enumerate(problems):
        px = Inches(0.8) + i * (prob_w + prob_gap)
        p_box = slide2.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, px, Inches(1.98), prob_w, Inches(2.1))
        p_box.fill.solid()
        p_box.fill.fore_color.rgb = bg
        p_box.line.color.rgb = border
        p_box.line.width = Pt(1.5)
        ptf = p_box.text_frame
        ptf.word_wrap = True
        ptf.margin_left = ptf.margin_right = ptf.margin_top = ptf.margin_bottom = Inches(0.18)

        pp1 = ptf.paragraphs[0]
        pp1.text = title
        pp1.font.name = "Plus Jakarta Sans"
        pp1.font.size = Pt(11)
        pp1.font.bold = True
        pp1.font.color.rgb = acc

        pp2 = ptf.add_paragraph()
        pp2.text = desc
        pp2.font.name = "Plus Jakarta Sans"
        pp2.font.size = Pt(9)
        pp2.font.color.rgb = TEXT_MAIN
        pp2.space_before = Pt(6)

    sec2_label = slide2.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(4.35), Inches(2.2), Inches(0.36))
    sec2_label.fill.solid()
    sec2_label.fill.fore_color.rgb = DARK_NAVY
    sec2_label.line.fill.background()
    s2_p = sec2_label.text_frame.paragraphs[0]
    s2_p.text = "Our Vision & Solution"
    s2_p.alignment = PP_ALIGN.CENTER
    s2_p.font.name = "Plus Jakarta Sans"
    s2_p.font.size = Pt(10.5)
    s2_p.font.bold = True
    s2_p.font.color.rgb = WHITE

    vision_container = slide2.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(4.85), Inches(11.733), Inches(1.8))
    vision_container.fill.solid()
    vision_container.fill.fore_color.rgb = WHITE
    vision_container.line.color.rgb = BORDER_COLOR
    vision_container.line.width = Pt(1.5)

    steps = [
        ("1. Geo-Tag & Snap", "Citizen selects category, uploads photo evidence, and drops pinpoint on GPS map."),
        ("2. SLA & Auto-Routing", "System calculates SLA resolution deadline and dispatches directly to nodal department."),
        ("3. Official Redressal", "Assigned field crew inspects site, resolves grievance, and logs resolution remarks."),
        ("4. Verified Closure", "Citizen tracks live timeline, receives instant alert, and audits verified solution.")
    ]

    step_w = Inches(2.65)
    step_gap = Inches(0.28)
    for i, (st, sd) in enumerate(steps):
        sx = Inches(1.05) + i * (step_w + step_gap)
        s_box = slide2.shapes.add_shape(MSO_SHAPE.RECTANGLE, sx, Inches(5.0), step_w, Inches(1.5))
        s_box.fill.background()
        s_box.line.fill.background()
        stf = s_box.text_frame
        stf.word_wrap = True
        stf.margin_left = stf.margin_right = stf.margin_top = stf.margin_bottom = 0

        sp1 = stf.paragraphs[0]
        sp1.text = st
        sp1.font.name = "Plus Jakarta Sans"
        sp1.font.size = Pt(11.5)
        sp1.font.bold = True
        sp1.font.color.rgb = PRIMARY_EMERALD

        sp2 = stf.add_paragraph()
        sp2.text = sd
        sp2.font.name = "Plus Jakarta Sans"
        sp2.font.size = Pt(9.5)
        sp2.font.color.rgb = TEXT_MUTED
        sp2.space_before = Pt(4)

    add_footer(slide2)

    # =========================================================================
    # SLIDE 3: Key Features & Multi-Role Architecture
    # =========================================================================
    slide3 = prs.slides.add_slide(blank_layout)
    add_header(slide3, "03", "Multi-Role System Architecture")

    roles = [
        ("Citizen Portal", "Empowering Public Voice", [
            ("Interactive Leaflet.js Map Picker", "Pinpoint exact issue coordinates with reverse geocoding."),
            ("4-Step Issue Composer", "Categorized selection with dynamic SLA estimation."),
            ("Photo Evidence Upload", "Image preview and client-side validation."),
            ("Live Progress Timeline", "Real-time audit trail with Pending/In Progress/Resolved states."),
            ("Personal Notification Stream", "Instant updates when official remarks are posted.")
        ], LIGHT_MINT, BORDER_MINT, PRIMARY_EMERALD),

        ("Department Official Console", "Streamlined Field Redressal", [
            ("Targeted Department Queue", "Filtered by assigned agency (PWD, SWM, Water, Electrical)."),
            ("Urgent Priority Escalations", "Highlighted cards for hazardous/emergency civic issues."),
            ("Nodal Staff Dispatch", "Assign individual technicians and field workers."),
            ("Resolution Remark Engine", "Submit verified resolution notes and timestamped proof."),
            ("Department Status Share", "Live donut charts for resolution workload distribution.")
        ], LIGHT_BLUE, RGBColor(191, 219, 254), ACCENT_BLUE),

        ("Administrative Governance", "Full Municipal Oversight", [
            ("City-Wide Command Center", "Executive KPIs on total complaints, rate & departments."),
            ("User & Role Management", "Create official staff, manage citizens, and enforce access."),
            ("Department & Category Hub", "Configure routing codes, nodal heads, and SLA rules."),
            ("Dynamic Chart.js Analytics", "Stacked resolution load charts across municipal units."),
            ("1-Click CSV & Print Exports", "Full data dump for municipal auditing and council review.")
        ], LIGHT_AMBER, RGBColor(254, 240, 138), ACCENT_AMBER)
    ]

    r_w = Inches(3.75)
    r_gap = Inches(0.24)
    r_y = Inches(1.5)
    r_h = Inches(5.15)

    for i, (role_name, role_sub, feature_list, bg, border, acc) in enumerate(roles):
        rx = start_x + i * (r_w + r_gap)
        r_box = slide3.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, rx, r_y, r_w, r_h)
        r_box.fill.solid()
        r_box.fill.fore_color.rgb = bg
        r_box.line.color.rgb = border
        r_box.line.width = Pt(1.5)

        rtf = r_box.text_frame
        rtf.word_wrap = True
        rtf.margin_left = rtf.margin_right = rtf.margin_top = rtf.margin_bottom = Inches(0.22)

        rp1 = rtf.paragraphs[0]
        rp1.text = role_name
        rp1.font.name = "Plus Jakarta Sans"
        rp1.font.size = Pt(14)
        rp1.font.bold = True
        rp1.font.color.rgb = acc

        rp_sub = rtf.add_paragraph()
        rp_sub.text = role_sub
        rp_sub.font.name = "Plus Jakarta Sans"
        rp_sub.font.size = Pt(9.5)
        rp_sub.font.bold = True
        rp_sub.font.color.rgb = TEXT_MUTED
        rp_sub.space_before = Pt(2)

        for f_title, f_desc in feature_list:
            fp = rtf.add_paragraph()
            fp.space_before = Pt(8)
            
            run_t = fp.add_run()
            run_t.text = f"• {f_title}: "
            run_t.font.name = "Plus Jakarta Sans"
            run_t.font.size = Pt(9)
            run_t.font.bold = True
            run_t.font.color.rgb = TEXT_MAIN

            run_d = fp.add_run()
            run_d.text = f_desc
            run_d.font.name = "Plus Jakarta Sans"
            run_d.font.size = Pt(8.5)
            run_d.font.color.rgb = TEXT_MUTED

    add_footer(slide3)

    # =========================================================================
    # SLIDE 4: Technology Stack & SDG Mapping
    # =========================================================================
    slide4 = prs.slides.add_slide(blank_layout)
    add_header(slide4, "04", "Technology Stack & UN SDG Alignment")

    tech_categories = [
        ("Frontend & UI System", "HTML5 Semantic Layout\nVanilla CSS3 Design Tokens\nGlassmorphism & Responsive Grids\nDark / Light / SFW Themes", PRIMARY_EMERALD),
        ("Maps & Visual Analytics", "Leaflet.js Mapping Library\nOpenStreetMap Geodata Tiles\nNominatim Reverse Geocoding\nChart.js Responsive Charts", ACCENT_BLUE),
        ("Backend & Business Logic", "PHP 8 Modular Routing\nPDO Database Abstraction\nCSRF Token Security\nSession RBAC Authentication", RGBColor(99, 102, 241)),
        ("Relational Database", "MySQL / MariaDB\nNormalized Schema (3NF)\nForeign Key Constraints\nIndexed Complaint Queries", ACCENT_AMBER)
    ]

    t_w = Inches(2.78)
    t_gap = Inches(0.2)
    for i, (t_title, t_desc, t_col) in enumerate(tech_categories):
        tx = start_x + i * (t_w + t_gap)
        t_box = slide4.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, tx, Inches(1.5), t_w, Inches(2.05))
        t_box.fill.solid()
        t_box.fill.fore_color.rgb = WHITE
        t_box.line.color.rgb = BORDER_COLOR
        t_box.line.width = Pt(1.5)

        ttf = t_box.text_frame
        ttf.word_wrap = True
        ttf.margin_left = ttf.margin_right = ttf.margin_top = ttf.margin_bottom = Inches(0.18)

        tp1 = ttf.paragraphs[0]
        tp1.text = t_title
        tp1.font.name = "Plus Jakarta Sans"
        tp1.font.size = Pt(11)
        tp1.font.bold = True
        tp1.font.color.rgb = t_col

        tp2 = ttf.add_paragraph()
        tp2.text = t_desc
        tp2.font.name = "Plus Jakarta Sans"
        tp2.font.size = Pt(8.5)
        tp2.font.color.rgb = TEXT_MAIN
        tp2.space_before = Pt(6)

    sdg_box_title = slide4.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.8), Inches(3.78), Inches(11.733), Inches(0.35))
    sdg_box_title.fill.background()
    sdg_box_title.line.fill.background()
    s_p = sdg_box_title.text_frame.paragraphs[0]
    s_p.text = "UNITED NATIONS SUSTAINABLE DEVELOPMENT GOALS (SDG MAPPING)"
    s_p.font.name = "Plus Jakarta Sans"
    s_p.font.size = Pt(11)
    s_p.font.bold = True
    s_p.font.color.rgb = DARK_NAVY

    sdgs = [
        ("SDG 11: Sustainable Cities", "Fosters clean, resilient, and inclusive urban habitats by accelerating municipal repairs of roads, streetlights, and drainage systems.", RGBColor(249, 157, 38), LIGHT_AMBER),
        ("SDG 16: Strong Institutions", "Enhances transparent and accountable public governance with real-time audit logs, public resolution proof, and citizen tracking.", RGBColor(0, 104, 157), LIGHT_BLUE),
        ("SDG 9: Resilient Infrastructure", "Upgrades municipal service delivery through digital issue logging, automated department dispatch, and spatial hotspot mapping.", RGBColor(235, 68, 48), LIGHT_ROSE),
        ("SDG 3: Health & Well-being", "Eliminates environmental health hazards such as stagnant garbage dumps, sewage leaks, and mosquito breeding zones.", RGBColor(44, 155, 64), LIGHT_MINT)
    ]

    sdg_w = Inches(2.78)
    sdg_gap = Inches(0.2)
    for i, (sdg_name, sdg_desc, sdg_col, sdg_bg) in enumerate(sdgs):
        sx = start_x + i * (sdg_w + sdg_gap)
        s_card = slide4.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, sx, Inches(4.25), sdg_w, Inches(2.4))
        s_card.fill.solid()
        s_card.fill.fore_color.rgb = sdg_bg
        s_card.line.color.rgb = sdg_col
        s_card.line.width = Pt(1.5)

        stf = s_card.text_frame
        stf.word_wrap = True
        stf.margin_left = stf.margin_right = stf.margin_top = stf.margin_bottom = Inches(0.18)

        sp1 = stf.paragraphs[0]
        sp1.text = sdg_name
        sp1.font.name = "Plus Jakarta Sans"
        sp1.font.size = Pt(11)
        sp1.font.bold = True
        sp1.font.color.rgb = sdg_col

        sp2 = stf.add_paragraph()
        sp2.text = sdg_desc
        sp2.font.name = "Plus Jakarta Sans"
        sp2.font.size = Pt(8.5)
        sp2.font.color.rgb = TEXT_MAIN
        sp2.space_before = Pt(6)

    add_footer(slide4)

    # =========================================================================
    # SLIDE 5: LIVE SHOWCASE – Citizen Experience (REAL SCREENSHOTS)
    # =========================================================================
    slide5 = prs.slides.add_slide(blank_layout)
    add_header(slide5, "05", "Live System Showcase: Citizen Experience")

    # Left Column: Report Issue
    c1_bg = slide5.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(1.45), Inches(5.72), Inches(5.25))
    c1_bg.fill.solid()
    c1_bg.fill.fore_color.rgb = LIGHT_MINT
    c1_bg.line.color.rgb = BORDER_MINT
    c1_bg.line.width = Pt(1.5)

    c1_tf = c1_bg.text_frame
    c1_tf.margin_left = c1_tf.margin_right = c1_tf.margin_top = c1_tf.margin_bottom = Inches(0.15)
    cp1 = c1_tf.paragraphs[0]
    cp1.text = "1. Interactive Geo-Reporting & SLA Estimation"
    cp1.font.name = "Plus Jakarta Sans"
    cp1.font.size = Pt(11.5)
    cp1.font.bold = True
    cp1.font.color.rgb = PRIMARY_EMERALD

    shot1 = os.path.join(SCREENSHOT_DIR, "04_citizen_report_issue.png")
    if os.path.exists(shot1):
        slide5.shapes.add_picture(shot1, Inches(0.95), Inches(1.85), width=Inches(5.42), height=Inches(3.38))

    desc_box1 = slide5.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.95), Inches(5.32), Inches(5.42), Inches(1.25))
    desc_box1.fill.background()
    desc_box1.line.fill.background()
    dtf1 = desc_box1.text_frame
    dtf1.word_wrap = True
    dtf1.margin_left = dtf1.margin_right = dtf1.margin_top = dtf1.margin_bottom = 0
    p_b1 = dtf1.paragraphs[0]
    p_b1.text = "• Category Selection: 10 structured civic domains with dynamic SLA targets."
    p_b1.font.name = "Plus Jakarta Sans"
    p_b1.font.size = Pt(8.5)
    p_b1.font.color.rgb = TEXT_MAIN

    p_b2 = dtf1.add_paragraph()
    p_b2.text = "• Interactive Map Pinpoint: Leaflet.js with browser GPS auto-detection."
    p_b2.font.name = "Plus Jakarta Sans"
    p_b2.font.size = Pt(8.5)
    p_b2.font.color.rgb = TEXT_MUTED

    p_b3 = dtf1.add_paragraph()
    p_b3.text = "• Evidence Validation: Ground photo upload with instant client preview."
    p_b3.font.name = "Plus Jakarta Sans"
    p_b3.font.size = Pt(8.5)
    p_b3.font.color.rgb = TEXT_MUTED

    # Right Column: Complaint Timeline & Tracking
    c2_bg = slide5.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(6.81), Inches(1.45), Inches(5.72), Inches(5.25))
    c2_bg.fill.solid()
    c2_bg.fill.fore_color.rgb = LIGHT_BLUE
    c2_bg.line.color.rgb = RGBColor(191, 219, 254)
    c2_bg.line.width = Pt(1.5)

    c2_tf = c2_bg.text_frame
    c2_tf.margin_left = c2_tf.margin_right = c2_tf.margin_top = c2_tf.margin_bottom = Inches(0.15)
    cp2 = c2_tf.paragraphs[0]
    cp2.text = "2. Live Resolution Lifecycle & Progress Timeline"
    cp2.font.name = "Plus Jakarta Sans"
    cp2.font.size = Pt(11.5)
    cp2.font.bold = True
    cp2.font.color.rgb = ACCENT_BLUE

    shot2 = os.path.join(SCREENSHOT_DIR, "05_citizen_complaint_timeline.png")
    if os.path.exists(shot2):
        slide5.shapes.add_picture(shot2, Inches(6.96), Inches(1.85), width=Inches(5.42), height=Inches(3.38))

    desc_box2 = slide5.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(6.96), Inches(5.32), Inches(5.42), Inches(1.25))
    desc_box2.fill.background()
    desc_box2.line.fill.background()
    dtf2 = desc_box2.text_frame
    dtf2.word_wrap = True
    dtf2.margin_left = dtf2.margin_right = dtf2.margin_top = dtf2.margin_bottom = 0
    p2_b1 = dtf2.paragraphs[0]
    p2_b1.text = "• 4-Stage Lifecycle: Registered → Assigned → In Progress → Resolved."
    p2_b1.font.name = "Plus Jakarta Sans"
    p2_b1.font.size = Pt(8.5)
    p2_b1.font.color.rgb = TEXT_MAIN

    p2_b2 = dtf2.add_paragraph()
    p2_b2.text = "• Department Attribution: Clear display of nodal officer & SLA deadline."
    p2_b2.font.name = "Plus Jakarta Sans"
    p2_b2.font.size = Pt(8.5)
    p2_b2.font.color.rgb = TEXT_MUTED

    p2_b3 = dtf2.add_paragraph()
    p2_b3.text = "• Status Trail: Immutable chronological logs with official resolution notes."
    p2_b3.font.name = "Plus Jakarta Sans"
    p2_b3.font.size = Pt(8.5)
    p2_b3.font.color.rgb = TEXT_MUTED

    add_footer(slide5)

    # =========================================================================
    # SLIDE 6: LIVE SHOWCASE – Department Official Console (REAL SCREENSHOTS)
    # =========================================================================
    slide6 = prs.slides.add_slide(blank_layout)
    add_header(slide6, "06", "Live System Showcase: Department Official Console")

    # Left: Official Dashboard
    o1_bg = slide6.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(1.45), Inches(5.72), Inches(5.25))
    o1_bg.fill.solid()
    o1_bg.fill.fore_color.rgb = LIGHT_BLUE
    o1_bg.line.color.rgb = RGBColor(191, 219, 254)
    o1_bg.line.width = Pt(1.5)

    o1_tf = o1_bg.text_frame
    o1_tf.margin_left = o1_tf.margin_right = o1_tf.margin_top = o1_tf.margin_bottom = Inches(0.15)
    op1 = o1_tf.paragraphs[0]
    op1.text = "1. Department Workspace & Priority Alert Center"
    op1.font.name = "Plus Jakarta Sans"
    op1.font.size = Pt(11.5)
    op1.font.bold = True
    op1.font.color.rgb = ACCENT_BLUE

    shot3 = os.path.join(SCREENSHOT_DIR, "07_official_dashboard.png")
    if os.path.exists(shot3):
        slide6.shapes.add_picture(shot3, Inches(0.95), Inches(1.85), width=Inches(5.42), height=Inches(3.38))

    desc_box3 = slide6.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.95), Inches(5.32), Inches(5.42), Inches(1.25))
    desc_box3.fill.background()
    desc_box3.line.fill.background()
    dtf3 = desc_box3.text_frame
    dtf3.word_wrap = True
    dtf3.margin_left = dtf3.margin_right = dtf3.margin_top = dtf3.margin_bottom = 0
    p3_b1 = dtf3.paragraphs[0]
    p3_b1.text = "• Live Department Counters: Active, pending action, and resolved counts."
    p3_b1.font.name = "Plus Jakarta Sans"
    p3_b1.font.size = Pt(8.5)
    p3_b1.font.color.rgb = TEXT_MAIN

    p3_b2 = dtf3.add_paragraph()
    p3_b2.text = "• Urgent Priority Escalation: Highlighted red alert banner for hazards."
    p3_b2.font.name = "Plus Jakarta Sans"
    p3_b2.font.size = Pt(8.5)
    p3_b2.font.color.rgb = TEXT_MUTED

    p3_b3 = dtf3.add_paragraph()
    p3_b3.text = "• Workload Donut Chart: Dynamic real-time status distribution breakdown."
    p3_b3.font.name = "Plus Jakarta Sans"
    p3_b3.font.size = Pt(8.5)
    p3_b3.font.color.rgb = TEXT_MUTED

    # Right: Official Grievance Queue & Actions
    o2_bg = slide6.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(6.81), Inches(1.45), Inches(5.72), Inches(5.25))
    o2_bg.fill.solid()
    o2_bg.fill.fore_color.rgb = LIGHT_MINT
    o2_bg.line.color.rgb = BORDER_MINT
    o2_bg.line.width = Pt(1.5)

    o2_tf = o2_bg.text_frame
    o2_tf.margin_left = o2_tf.margin_right = o2_tf.margin_top = o2_tf.margin_bottom = Inches(0.15)
    op2 = o2_tf.paragraphs[0]
    op2.text = "2. Department Grievance Queue & Action Engine"
    op2.font.name = "Plus Jakarta Sans"
    op2.font.size = Pt(11.5)
    op2.font.bold = True
    op2.font.color.rgb = PRIMARY_EMERALD

    shot4 = os.path.join(SCREENSHOT_DIR, "08_official_queue.png")
    if os.path.exists(shot4):
        slide6.shapes.add_picture(shot4, Inches(6.96), Inches(1.85), width=Inches(5.42), height=Inches(3.38))

    desc_box4 = slide6.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(6.96), Inches(5.32), Inches(5.42), Inches(1.25))
    desc_box4.fill.background()
    desc_box4.line.fill.background()
    dtf4 = desc_box4.text_frame
    dtf4.word_wrap = True
    dtf4.margin_left = dtf4.margin_right = dtf4.margin_top = dtf4.margin_bottom = 0
    p4_b1 = dtf4.paragraphs[0]
    p4_b1.text = "• Filtered Work Queue: Agency isolation ensures PWD/SWM focus."
    p4_b1.font.name = "Plus Jakarta Sans"
    p4_b1.font.size = Pt(8.5)
    p4_b1.font.color.rgb = TEXT_MAIN

    p4_b2 = dtf4.add_paragraph()
    p4_b2.text = "• Rapid Redressal Dispatch: Assign field crews directly to specific sites."
    p4_b2.font.name = "Plus Jakarta Sans"
    p4_b2.font.size = Pt(8.5)
    p4_b2.font.color.rgb = TEXT_MUTED

    p4_b3 = dtf4.add_paragraph()
    p4_b3.text = "• Verification Remarks: Mandatory resolution logging before ticket closure."
    p4_b3.font.name = "Plus Jakarta Sans"
    p4_b3.font.size = Pt(8.5)
    p4_b3.font.color.rgb = TEXT_MUTED

    add_footer(slide6)

    # =========================================================================
    # SLIDE 7: LIVE SHOWCASE – City Governance & Command Center (REAL SCREENSHOTS)
    # =========================================================================
    slide7 = prs.slides.add_slide(blank_layout)
    add_header(slide7, "07", "Live System Showcase: City Governance Console")

    # Left: Admin Command Center
    a1_bg = slide7.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(1.45), Inches(5.72), Inches(5.25))
    a1_bg.fill.solid()
    a1_bg.fill.fore_color.rgb = LIGHT_AMBER
    a1_bg.line.color.rgb = RGBColor(254, 240, 138)
    a1_bg.line.width = Pt(1.5)

    a1_tf = a1_bg.text_frame
    a1_tf.margin_left = a1_tf.margin_right = a1_tf.margin_top = a1_tf.margin_bottom = Inches(0.15)
    ap1 = a1_tf.paragraphs[0]
    ap1.text = "1. City-Wide Command Center & Load Distribution"
    ap1.font.name = "Plus Jakarta Sans"
    ap1.font.size = Pt(11.5)
    ap1.font.bold = True
    ap1.font.color.rgb = ACCENT_AMBER

    shot5 = os.path.join(SCREENSHOT_DIR, "10_admin_command_center.png")
    if os.path.exists(shot5):
        slide7.shapes.add_picture(shot5, Inches(0.95), Inches(1.85), width=Inches(5.42), height=Inches(3.38))

    desc_box5 = slide7.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.95), Inches(5.32), Inches(5.42), Inches(1.25))
    desc_box5.fill.background()
    desc_box5.line.fill.background()
    dtf5 = desc_box5.text_frame
    dtf5.word_wrap = True
    dtf5.margin_left = dtf5.margin_right = dtf5.margin_top = dtf5.margin_bottom = 0
    p5_b1 = dtf5.paragraphs[0]
    p5_b1.text = "• Executive Metrics: Total complaints, city resolution rate & active units."
    p5_b1.font.name = "Plus Jakarta Sans"
    p5_b1.font.size = Pt(8.5)
    p5_b1.font.color.rgb = TEXT_MAIN

    p5_b2 = dtf5.add_paragraph()
    p5_b2.text = "• Chart.js Stacked Analytics: Department resolution vs backlog load."
    p5_b2.font.name = "Plus Jakarta Sans"
    p5_b2.font.size = Pt(8.5)
    p5_b2.font.color.rgb = TEXT_MUTED

    p5_b3 = dtf5.add_paragraph()
    p5_b3.text = "• User & Role Management: Full control over official and citizen credentials."
    p5_b3.font.name = "Plus Jakarta Sans"
    p5_b3.font.size = Pt(8.5)
    p5_b3.font.color.rgb = TEXT_MUTED

    # Right: Admin Reports & CSV Analytics
    a2_bg = slide7.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(6.81), Inches(1.45), Inches(5.72), Inches(5.25))
    a2_bg.fill.solid()
    a2_bg.fill.fore_color.rgb = LIGHT_PURPLE
    a2_bg.line.color.rgb = RGBColor(221, 214, 254)
    a2_bg.line.width = Pt(1.5)

    a2_tf = a2_bg.text_frame
    a2_tf.margin_left = a2_tf.margin_right = a2_tf.margin_top = a2_tf.margin_bottom = Inches(0.15)
    ap2 = a2_tf.paragraphs[0]
    ap2.text = "2. Municipal Analytics & 1-Click CSV Audit Reports"
    ap2.font.name = "Plus Jakarta Sans"
    ap2.font.size = Pt(11.5)
    ap2.font.bold = True
    ap2.font.color.rgb = ACCENT_PURPLE

    shot6 = os.path.join(SCREENSHOT_DIR, "11_admin_reports.png")
    if os.path.exists(shot6):
        slide7.shapes.add_picture(shot6, Inches(6.96), Inches(1.85), width=Inches(5.42), height=Inches(3.38))

    desc_box6 = slide7.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(6.96), Inches(5.32), Inches(5.42), Inches(1.25))
    desc_box6.fill.background()
    desc_box6.line.fill.background()
    dtf6 = desc_box6.text_frame
    dtf6.word_wrap = True
    dtf6.margin_left = dtf6.margin_right = dtf6.margin_top = dtf6.margin_bottom = 0
    p6_b1 = dtf6.paragraphs[0]
    p6_b1.text = "• Department Scorecard: Real-time efficiency, backlog size & speed."
    p6_b1.font.name = "Plus Jakarta Sans"
    p6_b1.font.size = Pt(8.5)
    p6_b1.font.color.rgb = TEXT_MAIN

    p6_b2 = dtf6.add_paragraph()
    p6_b2.text = "• 1-Click CSV Export: Download full dataset for municipal council audit."
    p6_b2.font.name = "Plus Jakarta Sans"
    p6_b2.font.size = Pt(8.5)
    p6_b2.font.color.rgb = TEXT_MUTED

    p6_b3 = dtf6.add_paragraph()
    p6_b3.text = "• SDG Impact Alignment: Direct tracking of UN SDG 11 & SDG 16 goals."
    p6_b3.font.name = "Plus Jakarta Sans"
    p6_b3.font.size = Pt(8.5)
    p6_b3.font.color.rgb = TEXT_MUTED

    add_footer(slide7)

    # =========================================================================
    # SLIDE 8: Platform Innovations & UI/UX Excellence
    # =========================================================================
    slide8 = prs.slides.add_slide(blank_layout)
    add_header(slide8, "08", "Platform Innovations & UI/UX Design")

    innovations = [
        ("3-Mode Dynamic Theme Switcher", "Dark, Light & System Sync", "Pixel-perfect capsule switcher supporting instant live theme switching without page reload. High-contrast accessible dark palette ensures optimal readability for night-shift municipal officials and citizens alike.", PRIMARY_EMERALD, LIGHT_MINT),
        ("SFW Mode Media Protection", "Blur Sensitive Evidence", "First-of-its-kind civic privacy feature that applies real-time Gaussian blurs on sensitive photos (e.g. hazardous spills, accident scenes, dead animals) with one-click toggle to reveal on demand.", ACCENT_ROSE, LIGHT_ROSE),
        ("Interactive Geolocation Pin-Drop", "OpenStreetMap & GPS Hook", "Enables citizens to auto-detect location or manually position pins on responsive Leaflet maps with automatic reverse geocoding for precise municipal dispatch.", ACCENT_BLUE, LIGHT_BLUE),
        ("Dynamic SLA & Auto-Routing Engine", "Automated Turnaround Rules", "Computes statutory resolution SLA targets dynamically upon category selection (e.g., 2 days for electrical sparks vs. 7 days for road paving) and routes straight to the nodal officer.", ACCENT_AMBER, LIGHT_AMBER),
        ("Live Governance Analytics & CSV", "Instant Audit Reporting", "Visual Chart.js department load distributions and instant 1-click CSV audit report generation with timestamps, coordinates, and resolution remarks.", RGBColor(99, 102, 241), RGBColor(238, 242, 255)),
        ("Secure Multi-Role Access Control", "BCrypt & CSRF Protected", "Strict role segmentation ensuring citizens, department officers, and master administrators only access authorized views, secured by CSRF tokens and PDO parameterization.", DARK_NAVY, RGBColor(241, 245, 249))
    ]

    inn_w = Inches(3.75)
    inn_h = Inches(2.35)
    inn_gap_x = Inches(0.24)
    inn_gap_y = Inches(0.22)

    for i, (in_title, in_sub, in_desc, in_col, in_bg) in enumerate(innovations):
        col_idx = i % 3
        row_idx = i // 3
        ix = start_x + col_idx * (inn_w + inn_gap_x)
        iy = Inches(1.5) + row_idx * (inn_h + inn_gap_y)

        i_box = slide8.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, ix, iy, inn_w, inn_h)
        i_box.fill.solid()
        i_box.fill.fore_color.rgb = in_bg
        i_box.line.color.rgb = in_col
        i_box.line.width = Pt(1.5)

        itf = i_box.text_frame
        itf.word_wrap = True
        itf.margin_left = itf.margin_right = itf.margin_top = itf.margin_bottom = Inches(0.18)

        ip1 = itf.paragraphs[0]
        ip1.text = in_title
        ip1.font.name = "Plus Jakarta Sans"
        ip1.font.size = Pt(11)
        ip1.font.bold = True
        ip1.font.color.rgb = in_col

        ip_sub = itf.add_paragraph()
        ip_sub.text = in_sub
        ip_sub.font.name = "Plus Jakarta Sans"
        ip_sub.font.size = Pt(8.5)
        ip_sub.font.bold = True
        ip_sub.font.color.rgb = TEXT_MUTED

        ip2 = itf.add_paragraph()
        ip2.text = in_desc
        ip2.font.name = "Plus Jakarta Sans"
        ip2.font.size = Pt(8.5)
        ip2.font.color.rgb = TEXT_MAIN
        ip2.space_before = Pt(4)

    add_footer(slide8)

    # =========================================================================
    # SLIDE 9: System & Database Architecture
    # =========================================================================
    slide9 = prs.slides.add_slide(blank_layout)
    add_header(slide9, "09", "System & Database Architecture")

    layers = [
        ("Presentation Layer (Client)", "Browser UI / Frontend", [
            "Semantic HTML5 & Vanilla CSS3 Design System",
            "Leaflet.js Map Picker & OpenStreetMap Tiles",
            "Chart.js Dynamic Data Visualization",
            "Responsive Drawer & Dark/Light/SFW Mode"
        ], PRIMARY_EMERALD, LIGHT_MINT),
        ("Application Layer (Server)", "PHP 8 Processing & Security", [
            "Role-Based Access Control & Session Management",
            "CSRF Cryptographic Verification & Input Sanitization",
            "Automatic SLA Calculation & Department Routing",
            "File Upload Handler with Type & Size Validation"
        ], ACCENT_BLUE, LIGHT_BLUE),
        ("Data Layer (Storage)", "MySQL / MariaDB Relational", [
            "users: Citizens, Department Officials, Administrators",
            "departments: Municipal units (PWD, SWM, Water, Elec)",
            "categories: Issue taxonomy, SLAs & department keys",
            "complaints & timeline: Normalized logs & coordinates"
        ], ACCENT_AMBER, LIGHT_AMBER)
    ]

    layer_w = Inches(3.75)
    layer_h = Inches(3.2)
    for i, (l_name, l_sub, l_points, l_col, l_bg) in enumerate(layers):
        lx = start_x + i * (layer_w + r_gap)
        l_box = slide9.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, lx, Inches(1.5), layer_w, layer_h)
        l_box.fill.solid()
        l_box.fill.fore_color.rgb = l_bg
        l_box.line.color.rgb = l_col
        l_box.line.width = Pt(1.5)

        ltf = l_box.text_frame
        ltf.word_wrap = True
        ltf.margin_left = ltf.margin_right = ltf.margin_top = ltf.margin_bottom = Inches(0.2)

        lp1 = ltf.paragraphs[0]
        lp1.text = l_name
        lp1.font.name = "Plus Jakarta Sans"
        lp1.font.size = Pt(12)
        lp1.font.bold = True
        lp1.font.color.rgb = l_col

        lp_sub = ltf.add_paragraph()
        lp_sub.text = l_sub
        lp_sub.font.name = "Plus Jakarta Sans"
        lp_sub.font.size = Pt(9)
        lp_sub.font.bold = True
        lp_sub.font.color.rgb = TEXT_MUTED
        lp_sub.space_before = Pt(2)

        for pt in l_points:
            p_pt = ltf.add_paragraph()
            p_pt.text = f"✔  {pt}"
            p_pt.font.name = "Plus Jakarta Sans"
            p_pt.font.size = Pt(8.5)
            p_pt.font.color.rgb = TEXT_MAIN
            p_pt.space_before = Pt(5)

    schema_box = slide9.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(4.95), Inches(11.733), Inches(1.75))
    schema_box.fill.solid()
    schema_box.fill.fore_color.rgb = WHITE
    schema_box.line.color.rgb = BORDER_COLOR
    schema_box.line.width = Pt(1.5)

    stf9 = schema_box.text_frame
    stf9.word_wrap = True
    stf9.margin_left = stf9.margin_right = stf9.margin_top = stf9.margin_bottom = Inches(0.2)

    sp_head = stf9.paragraphs[0]
    sp_head.text = "DATABASE SCHEMA HIGHLIGHTS & DATA INTEGRITY"
    sp_head.font.name = "Plus Jakarta Sans"
    sp_head.font.size = Pt(11)
    sp_head.font.bold = True
    sp_head.font.color.rgb = DARK_NAVY

    schema_points = [
        "• users Table: Stores user authentication, role ('citizen'/'official'/'admin'), and department assignments.",
        "• complaints Table: Unique complaint_uid (e.g. CC-2026-0001), citizen_id, department_id, priority, status, latitude & longitude.",
        "• complaint_updates & timeline Table: Immutable historical audit log recording state transitions, timestamps, and officer remarks.",
        "• PDO Prepared Statements: Ensures 100% parameter separation to eliminate SQL Injection risks."
    ]

    for pt in schema_points:
        p_pt = stf9.add_paragraph()
        p_pt.text = pt
        p_pt.font.name = "Plus Jakarta Sans"
        p_pt.font.size = Pt(8.5)
        p_pt.font.color.rgb = TEXT_MUTED
        p_pt.space_before = Pt(2)

    add_footer(slide9)

    # =========================================================================
    # SLIDE 10: Real-World Impact, Conclusion & Future Scope
    # =========================================================================
    slide10 = prs.slides.add_slide(blank_layout)
    add_header(slide10, "10", "Impact, Conclusion & Future Scope")

    impact_box = slide10.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(1.5), Inches(5.7), Inches(2.55))
    impact_box.fill.solid()
    impact_box.fill.fore_color.rgb = LIGHT_MINT
    impact_box.line.color.rgb = BORDER_MINT
    impact_box.line.width = Pt(1.5)

    itf10 = impact_box.text_frame
    itf10.word_wrap = True
    itf10.margin_left = itf10.margin_right = itf10.margin_top = itf10.margin_bottom = Inches(0.2)

    ip_h = itf10.paragraphs[0]
    ip_h.text = "REAL-WORLD CIVIC IMPACT"
    ip_h.font.name = "Plus Jakarta Sans"
    ip_h.font.size = Pt(12)
    ip_h.font.bold = True
    ip_h.font.color.rgb = PRIMARY_EMERALD

    impacts = [
        ("70% Faster Turnaround: ", "Automated routing directly cuts manual paper triage delays."),
        ("100% Auditability: ", "Timestamps and GPS coordinates eliminate duplicate or lost complaints."),
        ("Citizen Trust & Inclusion: ", "Complete tracking transparency restores faith in local governance.")
    ]
    for h, d in impacts:
        p = itf10.add_paragraph()
        p.space_before = Pt(6)
        r1 = p.add_run()
        r1.text = f"✔ {h}"
        r1.font.bold = True
        r1.font.size = Pt(9)
        r1.font.color.rgb = DARK_NAVY
        r2 = p.add_run()
        r2.text = d
        r2.font.size = Pt(8.5)
        r2.font.color.rgb = TEXT_MUTED

    conc_box = slide10.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(6.8), Inches(1.5), Inches(5.733), Inches(2.55))
    conc_box.fill.solid()
    conc_box.fill.fore_color.rgb = LIGHT_BLUE
    conc_box.line.color.rgb = RGBColor(191, 219, 254)
    conc_box.line.width = Pt(1.5)

    ctf10 = conc_box.text_frame
    ctf10.word_wrap = True
    ctf10.margin_left = ctf10.margin_right = ctf10.margin_top = ctf10.margin_bottom = Inches(0.2)

    cp_h = ctf10.paragraphs[0]
    cp_h.text = "PROJECT CONCLUSION"
    cp_h.font.name = "Plus Jakarta Sans"
    cp_h.font.size = Pt(12)
    cp_h.font.bold = True
    cp_h.font.color.rgb = ACCENT_BLUE

    cp_body = ctf10.add_paragraph()
    cp_body.text = "CivicConnect successfully proves that modern full-stack web technologies can bridge the gap between citizens and municipal authorities. By integrating spatial GPS tagging, automated department SLA routing, role-based workflows, and real-time visual analytics, the platform establishes an accountable, responsive, and sustainable smart city ecosystem."
    cp_body.font.name = "Plus Jakarta Sans"
    cp_body.font.size = Pt(9)
    cp_body.font.color.rgb = TEXT_MAIN
    cp_body.space_before = Pt(6)

    future_box = slide10.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(4.25), Inches(11.733), Inches(2.45))
    future_box.fill.solid()
    future_box.fill.fore_color.rgb = WHITE
    future_box.line.color.rgb = BORDER_COLOR
    future_box.line.width = Pt(1.5)

    ftf10 = future_box.text_frame
    ftf10.word_wrap = True
    ftf10.margin_left = ftf10.margin_right = ftf10.margin_top = ftf10.margin_bottom = Inches(0.2)

    fp_h = ftf10.paragraphs[0]
    fp_h.text = "FUTURE SCOPE & PRODUCT ROADMAP"
    fp_h.font.name = "Plus Jakarta Sans"
    fp_h.font.size = Pt(12)
    fp_h.font.bold = True
    fp_h.font.color.rgb = DARK_NAVY

    future_items = [
        ("AI Computer Vision Verification", "Automatic duplicate detection and severity estimation from uploaded pothole/garbage photos via Google Gemini / Vision models.", PRIMARY_EMERALD, LIGHT_MINT),
        ("WhatsApp & SMS Webhook Bot", "Allow citizens to text complaints and photos via WhatsApp chatbot with zero app installation required.", ACCENT_BLUE, LIGHT_BLUE),
        ("Civic Karma Gamification", "Reward citizen contributions with civic points, community leaderboards, and municipal volunteer recognition badges.", ACCENT_AMBER, LIGHT_AMBER),
        ("Progressive Web App (PWA)", "Offline complaint caching, push notification alerts, and native mobile installability for field workers.", RGBColor(99, 102, 241), RGBColor(238, 242, 255))
    ]

    f_w = Inches(2.65)
    f_gap = Inches(0.25)
    for i, (f_title, f_desc, f_col, f_bg) in enumerate(future_items):
        fx = Inches(1.05) + i * (f_w + f_gap)
        f_card = slide10.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, fx, Inches(4.75), f_w, Inches(1.75))
        f_card.fill.solid()
        f_card.fill.fore_color.rgb = f_bg
        f_card.line.color.rgb = f_col
        f_card.line.width = Pt(1)

        ftf = f_card.text_frame
        ftf.word_wrap = True
        ftf.margin_left = ftf.margin_right = ftf.margin_top = ftf.margin_bottom = Inches(0.12)

        p1 = ftf.paragraphs[0]
        p1.text = f_title
        p1.font.name = "Plus Jakarta Sans"
        p1.font.size = Pt(9.5)
        p1.font.bold = True
        p1.font.color.rgb = f_col

        p2 = ftf.add_paragraph()
        p2.text = f_desc
        p2.font.name = "Plus Jakarta Sans"
        p2.font.size = Pt(8)
        p2.font.color.rgb = TEXT_MAIN
        p2.space_before = Pt(3)

    add_footer(slide10)

    output_path = "CivicConnect_Presentation.pptx"
    prs.save(output_path)
    print(f"Presentation successfully created and saved to: {output_path}")

    # Also copy to Desktop if user wants it there
    desktop_civic = "C:/Users/adith/OneDrive/Desktop/civic/CivicConnect_Presentation.pptx"
    if os.path.exists("C:/Users/adith/OneDrive/Desktop/civic"):
        try:
            prs.save(desktop_civic)
            print(f"Also saved copy to Desktop: {desktop_civic}")
        except Exception as e:
            print(f"Note: Could not overwrite Desktop file (likely open in PowerPoint): {e}")

if __name__ == "__main__":
    create_presentation()
