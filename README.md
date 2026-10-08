# 🏛️ CivicConnect
> **Cleaner Communities. Stronger Tomorrow.**  
> A smart, transparent public issue reporting & municipal governance portal bridging citizens with city departments for real-time tracking, geotagged evidence dispatching, and automated SLA accountability.

---

## 🌟 Key Features

### 👤 1. Citizen Portal
- **Interactive Leaflet.js Map Picker**: Drop pinpoint coordinates on OpenStreetMap with reverse geocoding or auto-detect GPS location.
- **4-Step Grievance Submission**: Categorized selection across 10 civic domains with dynamic statutory SLA estimation.
- **Photo Evidence Upload**: Ground photo upload with real-time client-side preview.
- **Live Progress Timeline**: 4-stage lifecycle tracker (*Registered → Assigned → In Progress → Resolved*) with timestamped nodal officer remarks.

### 👷 2. Department Official Console
- **Dedicated Department Queues**: Isolated work queues for Public Works (PWD), Solid Waste Management (SWM), Water Supply, and Electricity.
- **Urgent Priority Escalation**: Instant visual highlight for hazardous or emergency civic issues.
- **Field Redressal & Verification**: Assign technicians, update status, and log verified resolution remarks.
- **Workload Donut Analytics**: Live status share charts.

### 🛡️ 3. Administrative Governance & Command Center
- **City-Wide Command Center**: Executive KPIs on total complaints, resolution rates, and active municipal units.
- **Chart.js Visual Analytics**: Multi-department resolution load and backlog comparison charts.
- **Department & Category Management**: Dynamic SLA configuration, nodal officer assignment, and routing rules.
- **1-Click CSV Audit Reports**: Export full municipal grievance history for council auditing.

### ✨ Platform Innovations
- **3-Mode Theme Switcher**: Instant live switching between Dark Mode, Light Mode, and System Sync.
- **SFW Mode Media Protection**: Real-time Gaussian blur on sensitive incident photos with click-to-reveal toggle.
- **Role-Based Access Control (RBAC)**: Secure access separation protected with BCrypt password hashing, PDO prepared statements, and CSRF token verification.
- **UN SDG Alignment**: Mapped directly to **SDG 11** (Sustainable Cities), **SDG 16** (Strong Institutions), **SDG 9** (Infrastructure), and **SDG 3** (Good Health).

---

## 🛠️ Technology Stack

- **Frontend**: HTML5, Vanilla CSS3 (Custom Design System & Glassmorphism), JavaScript (ES6+), FontAwesome 6
- **Mapping & Geolocation**: Leaflet.js, OpenStreetMap Tiles, Nominatim API
- **Data Visualization**: Chart.js 4.x
- **Backend**: PHP 8.x (Modular Routing, PDO Abstraction, Session RBAC)
- **Database**: MySQL / MariaDB (Normalized 3NF Schema)
- **Presentation Deck**: Python `python-pptx` (Widescreen 16:9 Presentation with embedded live screenshots)

---

## 🚀 Getting Started

### Prerequisites
- **XAMPP / WAMP / LAMP** (PHP 8.0+ and MySQL / MariaDB)

### Setup Instructions

1. **Clone or copy the project into your web root**:
   ```bash
   # In XAMPP (Windows): C:\xampp\htdocs\CivicConnect
   git clone https://github.com/CipherX-Adith/Civic-Connect.git CivicConnect
   ```

2. **Start Apache & MySQL**:
   - Open **XAMPP Control Panel** and start **Apache** and **MySQL**.

3. **Database Configuration**:
   - Open `http://localhost/CivicConnect/` in your browser.
   - The application **automatically creates the database (`civicconnect`) and executes `schema.sql`** with demo seed data upon first visit!
   - Default DB Port: `3307` (or `3306`, configurable in `config/database.php`).

4. **Default Demo Accounts** (Password: `Password@123` for all):
   - 🛡️ **Admin**: `admin@civicconnect.gov`
   - 👷 **PWD Official**: `pwd.officer@civicconnect.gov`
   - 🧹 **Sanitation Official**: `swm.officer@civicconnect.gov`
   - 💧 **Water Official**: `water.officer@civicconnect.gov`
   - ⚡ **Electrical Official**: `elec.officer@civicconnect.gov`
   - 👤 **Citizen**: `citizen@civicconnect.gov`

---

## 👥 Microproject Team
**Group No. 01 — Web Programming**
1. **Abhiram Rajesh** (Roll No. 1)
2. **Abhishek S** (Roll No. 2)
3. **Adith S Kumar** (Roll No. 3)

---

## 📄 License
This project is developed as part of an Academic Web Programming Microproject.
