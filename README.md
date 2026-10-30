# Student Feedback and Course Evaluation System
### Department of Computer Science & Engineering | Academic Mini-Project

A responsive, database-driven web application developed using **PHP, MySQL, Bootstrap 5, Vanilla JavaScript, and Chart.js**. The system allows students to evaluate courses and faculty members across 7 key academic parameters with an interactive 5-star rating system, supports complete anonymity with duplicate submission prevention, and provides administrators with real-time visual analytics and appraisal reports.

---

## 1. Technology Stack

- **Frontend:** HTML5, CSS3, Bootstrap 5.3.2, Bootstrap Icons 1.11, Vanilla JavaScript (ES6)
- **Backend:** PHP 7.4 / 8.x (Native PDO with prepared statements, session management, `password_hash()` and `password_verify()`)
- **Database:** MySQL 5.7+ / MariaDB 10.4+ (`InnoDB`, `utf8mb4_unicode_ci`)
- **Data Visualization:** Chart.js 4.4
- **Local Development Environment:** XAMPP (Apache & MySQL) / Visual Studio Code

---

## 2. Project Folder Structure

```text
webtech/ (or student-feedback-system/)
│
├── index.php                         // Public landing page with role portals & platform overview
│
├── config/
│   ├── database.php                  // Secure PDO MySQL connection handler
│   ├── chatbot_config.php            // OpenAI API key configuration & system prompt
│   └── setup.php                     // One-click automated database installer & health check
│
├── api/
│   └── chat.php                      // Secure PHP backend proxy for AI chatbot & offline fallback
│
├── auth/
│   ├── student_register.php          // Student registration form & password hashing
│   ├── student_login.php             // Student authentication portal
│   ├── admin_login.php               // Administrator authentication portal
│   └── logout.php                    // Secure session destruction & redirect
│
├── student/
│   ├── dashboard.php                 // Student dashboard (assigned courses & progress bar)
│   ├── feedback_form.php             // 7-criteria 5-star evaluation form
│   ├── submit_feedback.php           // Evaluation processor (transaction & anonymity decoupling)
│   ├── feedback_success.php          // Post-submission receipt page
│   └── get_faculty_courses.php       // AJAX JSON endpoint for dynamic cascading dropdowns
│
├── admin/
│   ├── dashboard.php                 // Central KPI cards, quick actions & recent logs
│   ├── departments.php               // Department CRUD operations
│   ├── courses.php                   // Course management CRUD operations
│   ├── faculty.php                   // Faculty directory & course allocation CRUD
│   ├── students.php                  // Student records management & status control
│   ├── feedback.php                  // Feedback responses viewer with filters & safety-locked controls
│   ├── reports.php                   // Visual analytics, statistical tables & print-ready reports
│   └── get_analytics_data.php        // AJAX JSON endpoint providing metrics to Chart.js
│
├── includes/
│   ├── header.php                    // Shared HTML <head>, Bootstrap 5 CSS, Bootstrap Icons
│   ├── footer.php                    // Shared footer, Bootstrap 5 JS bundle, custom scripts
│   ├── navbar.php                    // Responsive top navigation for public & student pages
│   ├── sidebar.php                   // Collapsible modern sidebar for admin portal
│   ├── chatbot.php                   // Floating Campus Assistant HTML modal component
│   ├── auth_check.php                // Role-based session gatekeeper (guards student and admin areas)
│   └── csrf.php                      // Helper functions for CSRF token generation and validation
│
├── assets/
│   ├── css/
│   │   ├── style.css                 // Navy blue theme (#0a2240, #163d6f), star rating UI, card styles
│   │   └── chatbot.css               // Modern floating chat window, bubbles & typing indicators
│   └── js/
│       ├── script.js                 // Common interactive utilities, form validations, toast notifications
│       ├── student_feedback.js       // Dynamic star rating hover/select, live average calculation
│       ├── admin_charts.js           // Chart.js renderers (Faculty comparison, Satisfaction pie, Trend line)
│       ├── theme_switcher.js         // Multi-palette theme switcher & custom color picker engine
│       └── chatbot.js                // Chatbot client controller, auto-scroll, markdown parser & AJAX
│
├── database/
│   └── database.sql                  // Database schema, foreign keys, constraints & sample seed records
│
└── README.md                         // Documentation, test cases, execution steps & viva Q&A
```

---

## 3. Seed Accounts & Credentials

The system comes pre-configured with test accounts for immediate demonstration:

| User Role | Username / Identifier | Password | Access Rights |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `Admin@123` | Full administrative control, CRUD, reports, feedback management |
| **Student 1** | `2023CSE001` | `Student@123` | Rahul Sharma (CSE, Sem 5) - Evaluated DBMS |
| **Student 2** | `2023CSE002` | `Student@123` | Priya Patel (CSE, Sem 5) - Evaluated DBMS (Anonymous) |
| **Student 3** | `2023CSE003` | `Student@123` | Amit Kumar (CSE, Sem 5) - Evaluated Web Tech |

*Note: New students can register anytime via `auth/student_register.php`.*

---

## 4. Local Execution Guide (Step-by-Step with XAMPP)

Follow these steps to run the application on your computer:

### Step 1: Install & Start XAMPP
1. Download and install **XAMPP** from [https://www.apachefriends.org/](https://www.apachefriends.org/).
2. Open the **XAMPP Control Panel**.
3. Click **Start** for both **Apache** and **MySQL**. Verify that both show green status badges.

### Step 2: Place the Project in XAMPP `htdocs`
- Copy your project folder into:
  `C:\xampp\htdocs\student-feedback-system`
  *(or leave in `htdocs\webtech` depending on your folder name)*.

### Step 3: Database Setup (Two Easy Methods)

#### Method A: One-Click Automated Installer (Recommended)
1. Open your web browser (Chrome, Edge, or Firefox).
2. Visit: `http://localhost/student-feedback-system/config/setup.php` (or `http://localhost/webtech/config/setup.php`).
3. Click the blue button: **"Run One-Click Database Setup"**.
4. The tool automatically creates `student_feedback_system`, sets up all 8 relational tables, seeds realistic sample data, and generates native bcrypt password hashes!

#### Method B: Manual Import via phpMyAdmin
1. Open your browser and go to `http://localhost/phpmyadmin`.
2. Click **New** in the left sidebar.
3. Name the database: `student_feedback_system` and select collation `utf8mb4_unicode_ci`. Click **Create**.
4. Select the newly created database and click the **Import** tab at the top.
5. Click **Choose File**, select `database/database.sql` from your project folder, and click **Import** at the bottom.

### Step 4: Access the Application
- **Landing Page:** `http://localhost/student-feedback-system/index.php`
- **Student Login:** `http://localhost/student-feedback-system/auth/student_login.php`
- **Admin Console:** `http://localhost/student-feedback-system/auth/admin_login.php`

---

## 5. Live Web Hosting Deployment Guide (InfinityFree / cPanel)

Follow these steps to host your application online for public access:

### Step 1: Sign Up for Free PHP & MySQL Hosting
1. Visit [InfinityFree](https://www.infinityfree.com/) and register a free account.
2. In the Client Area, click **Create Account**.
3. Choose a free subdomain (e.g., `edurating.infinityfreeapp.com` or `student-feedback.epizy.com`).
4. Wait a few moments for the account to be provisioned, then open the **Control Panel (vPanel)**.

### Step 2: Create a MySQL Database
1. Inside the hosting Control Panel, navigate to **MySQL Databases**.
2. Under "Create a New Database", enter a database name extension (e.g., `feedback`).
3. Click **Create Database**.
4. Note your database connection details displayed on the page:
   - **MySQL Hostname:** (e.g., `sql200.epizy.com`)
   - **MySQL Database Name:** (e.g., `if0_38123456_feedback`)
   - **MySQL Username:** (e.g., `if0_38123456`)
   - **MySQL Password:** (Your hosting account password from the client area)

### Step 3: Import database.sql via phpMyAdmin
1. In the Control Panel, click **phpMyAdmin** next to your newly created database.
2. Inside phpMyAdmin, select your database from the left sidebar.
3. Click the **Import** tab at the top.
4. Click **Choose File** and select `database/database.sql` from your project folder.
5. Click **Import** at the bottom. All 8 tables and sample seed records will be created.

### Step 4: Upload Project Files to `htdocs`
1. In the InfinityFree Client Area, click **File Manager** (or connect via FileZilla FTP).
2. Open the **`htdocs`** folder of your domain.
3. Upload all files and folders from this project (`index.php`, `config/`, `auth/`, `student/`, `admin/`, `api/`, `includes/`, `assets/`, `database/`) directly into `htdocs/`.
   *(Ensure `index.php` is directly inside `htdocs/`, not inside a subfolder)*.

### Step 5: Configure Production Database Credentials
1. Inside the hosting File Manager, open the **`config`** folder.
2. Duplicate or rename `config/env.sample.php` to **`config/env.php`**.
3. Edit `config/env.php` and insert your live database credentials:
   ```php
   return [
       'DB_HOST' => 'sql200.epizy.com',               // Your MySQL Hostname
       'DB_NAME' => 'if0_38123456_feedback',         // Your Full DB Name
       'DB_USER' => 'if0_38123456',                  // Your MySQL Username
       'DB_PASS' => 'YourHostingPasswordHere',       // Your vPanel Password
       'DB_CHARSET' => 'utf8mb4',
       'OPENAI_API_KEY' => ''                        // Optional OpenAI Key
   ];
   ```
4. Save the file.

### Step 6: Enable Free SSL / HTTPS
1. In the InfinityFree Client Area, click **Free SSL Certificates**.
2. Click **New SSL Certificate**, select your domain, and follow the instructions to generate and install the free Let's Encrypt / ZeroSSL certificate.
3. Your live application is now accessible securely via **`https://yourdomain.infinityfreeapp.com`**!

---

## 6. GitHub Repository Setup Guide

To upload this complete project to GitHub:

```bash
# 1. Open Terminal or PowerShell in your project folder:
cd c:\Users\YOGESHWAR\OneDrive\Desktop\webtech

# 2. Initialize Git (if not already initialized):
git init

# 3. Add remote repository:
git remote add origin https://github.com/karthikeyantamilarasan2006/webtech.git
# (Or if origin already exists: git remote set-url origin https://github.com/karthikeyantamilarasan2006/webtech.git)

# 4. Stage all files (sensitive .env & config/env.php are auto-excluded by .gitignore):
git add .

# 5. Commit project files:
git commit -m "feat: complete student feedback and course evaluation system with AI chatbot and themes"

# 6. Set main branch and push:
git branch -M main
git push -u origin main
```

---

## 7. Test Cases Table (Functional & Security Verification)

| Test Case ID | Test Scenario | Input Data | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **TC-01** | Student Self-Registration | Valid Name, Unique Roll, Valid Email, Dept, Sem 5, matching passwords | Student account registered in MySQL; redirected to login with flash notice | Account created and confirmation displayed | **PASS** |
| **TC-02** | Duplicate Roll Registration | Existing Roll Number (`2023CSE001`) | Registration rejected with message: "A student with Roll Number is already registered." | Error displayed; form retained | **PASS** |
| **TC-03** | Student Valid Login | Roll: `2023CSE001`, Password: `Student@123` | Password verified with `password_verify()`; session initiated; redirected to dashboard | Dashboard opened with personalized greeting | **PASS** |
| **TC-04** | Student Invalid Login | Roll: `2023CSE001`, Password: `WrongPass!` | Access denied with message: "Invalid Roll Number or Password." | Denied with alert notice | **PASS** |
| **TC-05** | Admin Login | Username: `admin`, Password: `Admin@123` | Authenticated as superadmin; session role set to admin; opens Admin Console | Admin Dashboard rendered with live metrics | **PASS** |
| **TC-06** | Unauthorized Dashboard Access | Direct URL access to `admin/dashboard.php` as guest or student | Access blocked; redirected to `auth/admin_login.php` with authorization warning | Access blocked; login enforced | **PASS** |
| **TC-07** | Course-Faculty Dynamic Cascade | Student selects "CS502 - Web Technologies" from dropdown | AJAX fetch queries `get_faculty_courses.php` and auto-loads assigned faculty "Dr. Ada Lovelace" | Faculty dropdown auto-populated seamlessly | **PASS** |
| **TC-08** | Incomplete Rating Validation | 6 of 7 criteria rated, 1 criterion left blank | Form submission blocked with alert highlighting the missing academic parameter | Blocked and scrolled to unrated criterion | **PASS** |
| **TC-09** | Live Average Score Recalculation | Student rates criteria with varying stars (5, 5, 4, 5, 4, 5, 5) | Header live average badge dynamically updates in real-time to 4.71 / 5.00 | Real-time score calculated accurately | **PASS** |
| **TC-10** | Anonymous Feedback Submission | Checkbox "Submit as Anonymous Feedback" checked | `feedback_submissions` logs student receipt; `feedback` record inserts `student_id = NULL` | Identity decoupled in database and admin views | **PASS** |
| **TC-11** | Duplicate Feedback Prevention | Student attempts to evaluate the same Course & Faculty again | System rejects submission; alerts: "You have already completed feedback for this course." | Blocked; duplicate insertion prevented | **PASS** |
| **TC-12** | Admin Create Department | Code: `AI-DS`, Name: `Artificial Intelligence and Data Science` | Record inserted in `departments` table; appears immediately in table | Department added with 0 initial courses | **PASS** |
| **TC-13** | Admin Create Course | Code: `CS505`, Title: `Compiler Design`, Dept: CSE, Sem: 5, Credits: 4 | Record inserted in `courses` table; available in student evaluation lists | Created and mapped successfully | **PASS** |
| **TC-14** | Admin Course Allocation to Faculty | Course: `CS505`, Faculty: `Dr. Alan Turing`, Sem: 5 | Record inserted in `course_faculty`; faculty now appears for CS505 | Dynamic mapping active | **PASS** |
| **TC-15** | Admin Visual Reports Generation | Admin clicks "Reports & Analytics" | Chart.js renders Faculty Bar Chart, Satisfaction Pie, Category Radar, and Trend Line from MySQL data | All 4 charts render with live database metrics | **PASS** |
| **TC-16** | Print-Friendly Appraisal Report | Admin clicks "Print Appraisal Report" | Browser print preview triggers; navigation sidebar, buttons, and footers hidden cleanly | Clean academic paper appraisal formatted | **PASS** |
| **TC-17** | Chatbot Floating Widget & UI | User clicks floating trigger button at bottom-right corner | Chatbot window slides open with greeting, suggested pills, and active online status | Smooth slide-up transition & auto-focus | **PASS** |
| **TC-18** | AI & Knowledge Base Chat Responses | User asks "How does anonymous feedback work?" | PHP backend processes question; returns detailed breakdown of receipt vs. decoupled evaluation | Accurate, friendly markdown explanation rendered | **PASS** |
| **TC-19** | Dynamic Preset Theme Switching | User selects 'Dark Mode' or 'Emerald Green' from navbar palette | System applies `[data-theme]` instantly; cards, tables, sidebar, buttons update seamlessly | Immediate theme switch & localStorage saved | **PASS** |
| **TC-20** | Custom Color Studio Studio | User picks custom Primary, Secondary, Accent, Background colors | CSS variables recomputed dynamically; text contrast auto-adjusts; persists across reload | Custom palette applied without page flicker | **PASS** |

---

## 6. Academic Mini-Project Documentation Report

### 1. Title Page
- **Project Title:** Student Feedback and Course Evaluation System
- **Domain:** Web Technologies & Educational Management Systems
- **Target Audience:** Engineering Colleges, Autonomous Institutions & Universities

### 2. Abstract
The Student Feedback and Course Evaluation System is a secure, automated web platform designed to streamline course and instructor appraisals in higher education institutions. Traditional paper-based surveys suffer from low student engagement, delayed analysis, and privacy concerns. This system provides an intuitive 5-star rating interface covering 7 academic parameters, incorporates guaranteed student anonymity with duplicate-submission prevention, and equips administrators with real-time Chart.js visual analytics and printable appraisal summaries.

### 3. Existing System vs. Proposed System
| Parameter | Existing Traditional System | Proposed Webtech System |
| :--- | :--- | :--- |
| **Collection Medium** | Physical paper forms or generic Google Forms | Dedicated role-based academic portal |
| **Anonymity Guarantee** | Questionable; handwriting can be recognized | Mathematically decoupled in database schema |
| **Duplicate Prevention** | Hard to detect without compromising anonymity | Unique composite key tracking receipts |
| **Data Analysis** | Manual Excel tallying taking days/weeks | Instant Chart.js visual graphs & aggregate scores |
| **Reporting** | Static paper binders | One-click filterable, print-ready appraisal cards |

### 4. System Architecture
The application employs a 4-tier modular web architecture:
1. **Client Tier:** HTML5, Bootstrap 5, responsive CSS3, and JavaScript event handlers.
2. **Security & Session Tier:** Role-Based Access Control (`auth_check.php`), Anti-CSRF token validation (`csrf.php`), and native PHP session isolation.
3. **Application Controller Tier:** Modular PHP controllers managing authentication (`password_hash`), course allocation, feedback processing, and analytics aggregation.
4. **Data Tier:** MySQL database with InnoDB engine, enforcing referential integrity, foreign key constraints, and utf8mb4 encoding.

### 5. Anonymity Decoupling Architecture
To guarantee student anonymity while strictly preventing duplicate evaluations:
- **Receipt Table (`feedback_submissions`):** Stores `student_id`, `course_id`, `faculty_id`, `academic_year`, `semester`. A unique compound constraint prevents multiple entries.
- **Evaluation Table (`feedback`):** Stores ratings and qualitative comments. When anonymous is selected, `feedback.student_id` is set to `NULL`.
- **Outcome:** The administrator can see aggregate ratings, department breakdowns, and constructive comments, but can never trace anonymous feedback back to any student.

### 6. Advantages & Future Enhancements
- **Advantages:** Eliminates paper waste, guarantees objective student feedback, automates NAAC/NBA accreditation data gathering, and provides faculty with constructive insights.
- **Future Enhancements:** Automated email notifications when feedback opens, AI sentiment analysis on student comments, and PDF export integration (TCPDF/DOMPDF).

---

## 7. Viva Voce Q&A Preparation Guide

**Q1: How do you prevent a student from submitting feedback multiple times for the same subject?**
> *Answer:* We implement a separate tracking table called `feedback_submissions` with a unique compound index on `(student_id, course_id, faculty_id, academic_year, semester)`. When a student attempts to submit, the system checks this table first. If a record exists, the submission is rejected.

**Q2: If you track submissions, how is student anonymity preserved?**
> *Answer:* In the main `feedback` table where ratings (1-5) and comments are saved, the `student_id` column is nullable. If the student selects "Submit as Anonymous", the application inserts `student_id = NULL` into the `feedback` table. The receipt in `feedback_submissions` only verifies *that* student X submitted, not *what* ratings or comments they gave.

**Q3: How is password security handled in the system?**
> *Answer:* Passwords are never stored in plain text. We utilize PHP's built-in `password_hash($password, PASSWORD_BCRYPT)` during registration and `password_verify($password, $hash)` during login. This uses salt generation and adaptive cost factors to safeguard against rainbow table attacks.

**Q4: How does the system defend against SQL Injection and Cross-Site Request Forgery (CSRF)?**
> *Answer:* For SQL Injection, all database queries use PDO prepared statements with bound parameters (`$stmt->prepare()` and `$stmt->execute()`). For CSRF, each form includes a cryptographically secure random token (`bin2hex(random_bytes(32))`) validated on submission via `hash_equals()`.

**Q5: How are the visual charts generated?**
> *Answer:* When the administrator visits `reports.php`, the frontend JavaScript calls `get_analytics_data.php` via `fetch()`. The PHP backend executes SQL aggregate functions (`COUNT`, `AVG`, `GROUP BY`) and returns JSON. Chart.js then dynamically renders responsive Bar, Pie, Radar, and Line charts.

**Q6: How does the Campus Assistant AI Chatbot work without exposing the API key?**
> *Answer:* The chatbot utilizes a secure server-side PHP proxy architecture (`api/chat.php`). The browser's JavaScript communicates exclusively with `api/chat.php` via JSON. The PHP backend manages the OpenAI API key server-side (from environment variables or `chatbot_config.php`) using PHP cURL. If an OpenAI key is not provided or network is offline, a built-in Academic Knowledge Engine automatically serves accurate, context-aware answers so the chatbot never fails during college viva evaluations.

