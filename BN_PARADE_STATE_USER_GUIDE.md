# BN Parade State Automation Portal — User Guide

**System:** Battalion Parade State Automation Portal  
**Unit:** The Infantry School, MHOW  
**Version:** 1.2  
**Last Updated:** May 2026

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [First-Time Setup (Database)](#2-first-time-setup-database)
3. [Getting Started](#3-getting-started)
4. [User Roles](#4-user-roles)
5. [Homepage & Navigation](#5-homepage--navigation)
6. [Dashboard](#6-dashboard)
7. [ADMIN — User Management](#7-admin--user-management)
8. [ADMIN — Master Data](#8-admin--master-data)
9. [CHM CLERK — Daily Entries](#9-chm-clerk--daily-entries)
10. [ADJT SA — Parade State Control](#10-adjt-sa--parade-state-control)
11. [Nominal Roll](#11-nominal-roll)
12. [Reports](#12-reports)
13. [Profile & Password Management](#13-profile--password-management)
14. [Attendance Status Reference](#14-attendance-status-reference)
15. [Daily Workflow — Step by Step](#15-daily-workflow--step-by-step)
16. [Troubleshooting](#16-troubleshooting)

---

## 1. System Overview

The BN Parade State Automation Portal replaces manual parade state registers and Excel workbooks with a role-based web application. It enables:

- Daily attendance entry by Company Clerks
- Automated strength calculation across all companies
- Leave, Course, Duty and Sick Report tracking
- Approval workflow from Company level → Adjutant
- Nominal Roll management with personnel extended data (DOB, blood group, medical category, leave balances)
- Visual analytics dashboard
- Dark mode and Light mode UI — preference saved in browser

**Technology:** PHP web application running on a local XAMPP server (Windows).  
**Access:** Open a browser and navigate to `http://localhost/BN_Parade_State_Automation`

---

## 2. First-Time Setup (Database)

> Skip this section if the database is already set up and the system is working.

### 2.1 Fresh Installation

If starting on a new machine or after a database loss, use the single combined setup file:

1. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Click the **Import** tab in the **top navigation bar** (not inside any database)
3. Click **Choose File** and select:
   ```
   database/bn_full_setup.sql
   ```
4. Click **Go**

This file performs everything in one pass:
- Drops and recreates `bn_parade_state_db`
- Creates all 11 tables with the complete schema
- Inserts 6 companies, 4 default users, 120 personnel
- Seeds 30 days of attendance data, leave records, course records, duty roster, sick reports, and manpower shortages

### 2.2 Default Login Credentials (After Import)

| Username | Password | Role |
|---|---|---|
| `admin` | `Admin@123` | ADMIN |
| `adjt` | `Adjt@123` | ADJT_SA |
| `acoy.clerk` | `Clerk@123` | CHM_CLERK |
| `user` | `User@123` | USER |

> **Important:** After first login, go to **Profile → Change Password** immediately to set a secure bcrypt-hashed password. The initial import uses a placeholder hash.

### 2.3 Database Files Reference

| File | Purpose |
|---|---|
| `database/bn_full_setup.sql` | **Use this.** Complete setup — schema + all seed data |
| `database/bn_import_data.sql` | Supplementary data only — requires existing schema |
| `database/bn_parade_state.sql` | Original base schema (no extended columns, no seed data) |
| `database/generate_import_data.py` | Python script that regenerates `bn_full_setup.sql` and `bn_import_data.sql` |

---

## 3. Getting Started

### 3.1 Starting the Server

1. Open **XAMPP Control Panel**
2. Start **Apache** — status turns green
3. Start **MySQL** — status turns green
4. Open any browser and go to:
   ```
   http://localhost/BN_Parade_State_Automation
   ```

### 3.2 Logging In

1. From the homepage, click **Login** (top-right of the navigation bar) or go directly to:
   ```
   http://localhost/BN_Parade_State_Automation/auth/login.php
   ```
2. Enter your **Username** and **Password**
3. Click **Sign In**

> **Note:** After 5 failed login attempts from the same device, the account is locked for 15 minutes. Contact the Admin if locked out.

### 3.3 Logging Out

Click **Logout** in the top-right of any page. Sessions expire automatically when the browser is closed.

### 3.4 Forgot Password

1. Click **Forgot password?** on the Login page
2. Enter your username and click **Continue**
3. Answer your security question
4. Enter and confirm your new password
5. Click **Reset Password**

> You must have previously set a security question from your Profile page. If not set, contact the Admin to reset your password manually.

### 3.5 Dark Mode / Light Mode

Every page has a **theme toggle button** in the top-right navigation bar:

- Shows **"Light"** (sun icon) when in dark mode — click to switch to light
- Shows **"Dark"** (moon icon) when in light mode — click to switch to dark
- Preference is saved in the browser and persists across sessions and page navigation

---

## 4. User Roles

The system has four roles. Each role sees only the modules relevant to their duties.

| Role | Badge Colour | Who Uses It | Access Level |
|---|---|---|---|
| **ADMIN** | Red | System Administrator | Full access — all modules, user management, approvals, master data |
| **ADJT_SA** | Blue | Adjutant / Subedar Adjutant | Parade State Control, Approvals, Reports, Nominal Roll |
| **CHM_CLERK** | Olive Green | Company Havildar Major / Company Clerk | Daily entries for their assigned company only |
| **USER** | Gold | Commanding Officer, 2IC, OCs (read-only) | View-only access to Parade State and Reports |

---

## 5. Homepage & Navigation

The homepage (`index.php`) is publicly accessible and serves as the portal landing page.

### 5.1 Navigation Bar

| Menu Item | Contents |
|---|---|
| **Home** | Returns to the portal homepage |
| **About Us** | Links to Indian Army History, Mission & Vision, Leadership (external) |
| **Know Your Army** | Combat Edge, Command & Control, Operations, AFSPA (external) |
| **Gallery** | Indian Army photos and videos (external) |
| **External Links** | Ministry of Defence, Indian Army, Navy, Air Force websites |
| **Contact Us** | Join Indian Army contact page (external) |
| **Login** | Portal login page |
| **Sign Up** | User registration (contact Admin) |

### 5.2 News Ticker

The scrolling gold ribbon displays system-wide announcements.

### 5.3 Image Slider

Displays Infantry training and parade images. Use the **◀** and **▶** buttons to navigate manually.

### 5.4 Indian Army Commands Panel

Quick links to all seven Army Command websites — Western, Southern, Northern, Eastern, Central, ARTRAC, and South Western.

---

## 6. Dashboard

After login, all users land on the Dashboard (`dashboard.php`).

### 6.1 Quick Stats Bar

Four tiles show real-time figures — each tile has an icon and coloured accent:

| Tile | Colour | Description |
|---|---|---|
| **Serving Personnel** | Gold | Total active soldiers in the Nominal Roll |
| **Active Companies** | Gold | Number of enabled companies |
| **Present Today** | Green | Count of soldiers marked Present for today's date |
| **Pending Approvals** | Amber | Attendance records awaiting approval (ADMIN and ADJT_SA only; turns amber when non-zero) |

### 6.2 Module Cards

Cards visible depend on the logged-in role. Each card has an SVG icon in the header.

**ADMIN sees:**
- Nominal Roll, Master Data, User Control, Approvals, Reports

**ADJT_SA sees:**
- Nominal Roll, Parade State Control, Reports

**CHM_CLERK sees:**
- Nominal Roll, Company Entries (daily data entry for their company)

**USER sees:**
- Nominal Roll, Reports (read-only)

---

## 7. ADMIN — User Management

### 7.1 Create User (`admin/create_user.php`)

Used to add new portal users (clerks, officers, SA).

**Fields:**

| Field | Required | Notes |
|---|---|---|
| Full Name | Yes | Include rank — e.g., `Hav Ram Kumar` |
| Username | Yes | Login ID — must be unique, no spaces |
| Password | Yes | Minimum 6 characters |
| Role | Yes | ADMIN / ADJT_SA / CHM_CLERK / USER |
| Company | No | Assign a specific company for CHM_CLERK; leave blank for ADMIN/ADJT_SA |
| Army No | No | Optional — links the user account to a personnel record |

**Steps:**
1. Go to Dashboard → User Control → **Create User**
2. Fill in all required fields
3. For CHM_CLERK role, always assign the correct company — the clerk will only see their own company's personnel
4. Click **Create User**
5. A success message confirms creation

### 7.2 Manage Users (`admin/manage_users.php`)

Lists all system users. Allows:

- **Change Role** — update a user's role using the dropdown
- **Change Company** — reassign a clerk to a different company
- **Activate / Deactivate** — toggle access without deleting the account. Deactivated users cannot log in.

**To change a user's role:**
1. Locate the user in the table
2. Select the new role from the **Role** dropdown in their row
3. Select company if applicable
4. Click **Save**

**To deactivate a user:**
1. Locate the user
2. Click **Deactivate** in the Toggle column
3. The status badge changes from Active (green) to Inactive (red)

---

## 8. ADMIN — Master Data

### 8.1 Company Master (`admin/company_master.php`)

Defines the battalion's company structure.

**To add a company:**
1. Enter **Company Name** — e.g., `Alpha Company`
2. Enter **Short Name** — e.g., `A COY` (auto-capitalised)
3. Click **Add Company**

Default companies (pre-loaded): HQ Company, A Company, B Company, C Company, D Company, SP Company.

**To deactivate a company:** Click **Deactivate** next to the company. Inactive companies are hidden from clerk dropdowns.

### 8.2 Platoon / Section Master (`admin/platoon_section_master.php`)

Defines platoons and sections within each company.

**To add a platoon:**
1. Select the **Company** from the dropdown
2. Enter **Platoon Name** — e.g., `1 Platoon`
3. Enter **Section Name** (optional) — e.g., `A Section`
4. Click **Add Platoon**

Use the **Filter by Company** dropdown to view platoons for a specific company.

### 8.3 Approve Attendance (`admin/approve_attendance.php`)

Reviews and approves attendance records submitted by company clerks.

- Records arrive with status **Pending**
- Admin can **Approve** or **Reject** entries
- Approved records are locked — clerks cannot edit them

### 8.4 Approve Leave (`admin/approve_leave.php`)

Reviews leave requests submitted by clerks.

- Approve or reject with remarks
- Approved leave automatically reflects in strength reports

### 8.5 Notification Settings (`admin/notification_settings.php`)

Configure system-level alerts and notification preferences.

---

## 9. CHM CLERK — Daily Entries

> **Important:** A clerk can only enter data for their own assigned company. If a clerk cannot see their soldiers, the Admin must check the company assignment in Manage Users.

### 9.1 Daily Attendance (`clerk/daily_attendance.php`)

The primary daily task — must be completed every morning before 0800 hrs.

**Steps:**
1. Go to Dashboard → Company Entries → **Daily Attendance**
2. The page defaults to **today's date** — verify this is correct
3. To enter for a different date, use the **View Date** field at the top and click **Go**
4. For each soldier, select their attendance status from the dropdown:

   | Status | When to Use |
   |---|---|
   | **Present** | Soldier is in unit and on parade |
   | **Absent** | Absent without leave or reason unknown |
   | **Leave** | On sanctioned Annual Leave or Casual Leave |
   | **Course** | Attending a course or cadre |
   | **Sick Report** | Reported sick, attending OPD / RMO |
   | **MH** | Admitted to Military Hospital |
   | **TD** | Temporary Duty — Local Attachment, Forward/Op Area, Bde/Div |
   | **Duty** | Guard, Sentry, QRT, Office Duty or Special Task |
   | **Attached Out** | Permanently attached to another unit |
   | **Other** | Any other reason — add remarks |

5. Add **Remarks** for any status that needs clarification (e.g., leave station, hospital name, duty location)
6. Click **Save Attendance**
7. A summary bar at the top shows the count for each status category

> **Locked Rows:** Rows showing **Approved** in the Approval column are greyed out and cannot be edited. Contact the Admin if a correction is needed on an approved record.

### 9.2 Leave Entry (`clerk/leave_entry.php`)

Records leave details for soldiers going on leave.

**Fields:** Soldier (select from dropdown), Leave Type, From Date, To Date, Remarks

**Leave Types:** Annual Leave (AL), Casual Leave (CL), Maternity Leave, Sick Leave, Special Leave, Earned Leave, Ex-India Leave

### 9.3 Duty Roster (`clerk/duty_roster.php`)

Records duty assignments for company soldiers.

**Duty Types:** Guard, Sentry, QRT (Quick Reaction Team), Office Duty, Special Task, Other

**Fields:** Soldier, Duty Type, Date, Time, Location, Remarks

### 9.4 Course Entry (`clerk/course_entry.php`)

Records soldiers attending external courses or cadres.

**Fields:** Soldier, Course Name, Location, From Date, To Date, Remarks

### 9.5 Sick Report / MH (`clerk/sick_report.php`)

Records sick report and hospitalisation details.

**Categories:** Sick Report, MH (Military Hospital), OPD, Rest Advised

**Fields:** Soldier, Date, Category, Expected Return Date, Remarks

### 9.6 Company Strength (`clerk/company_strength.php`)

View-only summary of the company's current strength breakdown — Posted, Present, and Absent by reason.

### 9.7 My Activity (`clerk/my_activity.php`)

Activity log showing all entries made by the logged-in clerk — useful for audit and correction reference.

---

## 10. ADJT SA — Parade State Control

### 10.1 Battalion Parade State (`adjt_sa/battalion_parade_state.php`)

Master entry point for the battalion-level daily parade state. Consolidates all company inputs into the official BN Parade State format showing strength by rank across all status categories.

### 10.2 Approve Parade State (`adjt_sa/approve_parade_state.php`)

Reviews and approves the consolidated parade state before it becomes the official record for the day. Once approved, the state is locked and moved to archive.

### 10.3 Manpower Shortages (`adjt_sa/manpower_shortages.php`)

Records and tracks manpower shortfalls by company.

**Priority Levels:** Normal, Urgent, Critical

**Fields:** Company, Date, Required Strength, Available Strength, Priority, Remarks

### 10.4 Duty Overview (`adjt_sa/duty_overview.php`)

Battalion-wide view of all duties — consolidates Guard, Sentry, QRT and other assignments across all companies for a given date.

---

## 11. Nominal Roll

Accessible to all roles.

### 11.1 View Personnel (`admin/manage_soldiers.php`)

Displays the full battalion nominal roll with search and filter options.

**Filters:**
- **Army No** — partial match search
- **Company** — filter by company
- **Rank** — filter by rank

**Sort Columns:** Name, Army No, Rank, Company (click column header)

**Export Options:**
- **Download PDF** — generates a printable nominal roll PDF
- **Download Excel** — exports to `.xls` for offline use

### 11.2 Personnel Extended Data

Each soldier record includes the following fields (populated via the database import):

| Field | Description |
|---|---|
| Date of Birth | DOB for service calculations |
| Date of Enrolment | Date joined the Army |
| Blood Group | A+, B+, O+, AB+, A-, B-, O-, AB- |
| Home State | State of domicile |
| PIN Code | Home district PIN |
| Mobile No | Personal mobile number |
| Emergency Contact | Next-of-kin contact number |
| Marital Status | Married / Single / Widowed |
| Medical Category | SHAPE-1, AYE, BEE, CEE |
| AL Balance | Annual Leave days remaining |
| CL Balance | Casual Leave days remaining |
| BN Team | Assigned team within battalion |

### 11.3 Nominal Roll Analytics (`admin/nominal_roll_analytics.php`)

Visual analytics dashboard showing:
- Strength donut chart by company
- Legend with count and percentage per company
- Exportable data

---

## 12. Reports

Accessible to ADMIN, ADJT_SA, and USER roles.

### 12.1 Daily Parade State (`reports/daily_parade_state.php`)

The official battalion parade state report. Shows strength by rank across all status rows:

**Rows:** AUTH STR → POSTED → In Unit → AL → CL → Sikh Leave → Temp Duty → Local Att → Att Duty → MH → Course/Cadre → AWL/OSL → Fwd/Op Area → Bde/Div → Pension Drill → Posting Out → Other Out → **Total Absent** → **Total Present**

**Columns:** All ranks from Colonel to Sepoy, plus Clerks, ERE, with OFFR / JCO / OR / GRAND totals.

### 12.2 Coy-wise Strength Summary (`reports/coy_strength_summary.php`)

Company-by-company breakdown showing posted strength, present strength, and absentee reasons.

### 12.3 Leave / Course / Duty Report (`reports/leave_course_duty_report.php`)

Detailed list of all soldiers currently on Leave, Course, or Duty — shows name, rank, company, type, from/to dates, and location.

### 12.4 Attendance Analytics (`reports/attendance_analytics.php`)

Trend charts and comparisons:
- Attendance percentage by company
- Month-on-month trend
- Shortage pattern analysis

### 12.5 Archive (`reports/archive.php`)

Historical parade states stored by date. Select a date to view the approved parade state for that day.

### 12.6 Download Options

From any report page:
- **Download PDF** — formatted printable report (`reports/download_pdf.php`)
- **Download Excel** — `.xls` export for further analysis (`reports/download_excel.php`)

---

## 13. Profile & Password Management

### 13.1 Profile Page (`profile.php`)

Access via the **Profile** button in the top navigation bar (visible on all internal pages).

Displays:
- Full Name
- Username
- Role
- Current Security Question (if set)

### 13.2 Change Password

1. Go to Profile → **Change Password** panel
2. Enter **Current Password**
3. Enter and confirm the security answer for verification
4. Enter and confirm the **New Password** (minimum 6 characters)
5. Click **Update Password**

> You must have a security question set before you can change your password.

### 13.3 Set Security Question

Required for password self-reset. Must be set on first login.

1. Go to Profile → **Security Question** panel
2. Select a question from the dropdown
3. Type your answer (case-insensitive)
4. Click **Save Question**

**Available questions:**
- What was the name of your first school?
- What is your mother's maiden name?
- What was your childhood nickname?
- What is the name of your first pet?
- In which city were you born?
- What is your favorite book?
- What is your favorite food?
- What was the model of your first phone?
- What is the name of your best childhood friend?
- What is your favorite sports team?

---

## 14. Attendance Status Reference

Quick reference for clerks when entering daily attendance.

| Status Code | Full Name | Parade State Row |
|---|---|---|
| Present | Present | Total Present |
| Absent | Absent Without Leave | AWL/OSL |
| Leave | Annual / Casual / Special Leave | AL or CL |
| Course | Course / Cadre | Course/Cadre |
| Sick Report | Reported Sick (OPD/RMO) | MH (or In Unit if light duty) |
| MH | Military Hospital (admitted) | MH |
| TD | Temporary Duty (local, Bde/Div, Fwd) | Temp Duty / Att Duty / Fwd/Op Area |
| Duty | Guard, Sentry, QRT | In Unit (counted as present) |
| Attached Out | Permanently attached to another unit | Posting Out / Local Att |
| Other | Any other — must add remarks | Other Out |

---

## 15. Daily Workflow — Step by Step

### For Company Clerks (CHM_CLERK)

**Every morning (recommended before 0800 hrs):**

```
1. Log in → Dashboard
2. Click Daily Attendance
3. Verify the date is correct (today's date)
4. For each soldier, select the correct status
5. Add remarks for TD, Course, Leave (add location/reason)
6. Click Save Attendance
7. If required — enter Leave Entry, Course Entry or Duty Roster separately
8. Log out
```

### For Adjutant / SA (ADJT_SA)

**After all company clerks have submitted:**

```
1. Log in → Dashboard
2. Check Pending Approvals count on stat bar
3. Click Battalion Parade State — verify consolidated figures
4. Click Approve Parade State — review and approve
5. Check Manpower Shortages if any companies have flagged shortfalls
6. Generate Daily Parade State report (Reports → Daily Parade State)
7. Download PDF for CO's signature
8. Log out
```

### For Admin (ADMIN)

**Weekly / as required:**

```
1. Check Manage Users for any deactivation required
2. Review Approve Attendance for any pending clerk submissions
3. Check Attendance Analytics for trends or anomalies
4. Add new users as soldiers join or are transferred in
5. Deactivate users when soldiers are posted out
```

---

## 16. Troubleshooting

| Problem | Likely Cause | Solution |
|---|---|---|
| Cannot log in | Wrong credentials, account deactivated, or placeholder password not yet reset | Go to Profile → Change Password; ensure XAMPP is running |
| Blank screen or "Connection Failed" | MySQL not running | Open XAMPP Control Panel, start MySQL |
| Clerk cannot see any soldiers | Company not assigned to clerk account | Admin → Manage Users → update company for that clerk |
| Soldier missing from attendance list | Service status set to non-Serving, or wrong company | Admin → Manage Soldiers → verify company and service status |
| Attendance row is greyed out | Record already approved by Admin | Contact Admin for correction |
| "CSRF token mismatch" error | Session expired or back-button resubmission | Reload the page and submit again |
| Account locked (15-minute lockout) | 5 incorrect password attempts | Wait 15 minutes or contact Admin |
| Password reset fails | Security question not set | Admin must manually reset password in database |
| Report shows zeros | No attendance data entered for selected date | Ensure clerks have submitted attendance for that date |
| PDF download shows garbled text | Browser PDF viewer issue | Download and open with Adobe Reader |
| Theme toggle not working | JavaScript disabled or browser privacy mode | Enable JavaScript; theme toggle requires localStorage |
| Database import error "#1146 Table doesn't exist" | Wrong database selected in phpMyAdmin | Use top-level Import tab (not inside a specific database) — import `bn_full_setup.sql` |

---

## Appendix A — File Structure

```
BN_Parade_State_Automation/
├── index.php                  — Homepage (public)
├── dashboard.php              — Post-login landing page
├── profile.php                — User profile & password management
├── logout.php                 — Session termination
│
├── auth/
│   ├── login.php              — Login form with army emblem
│   ├── reset_password.php     — Password reset via security question
│   └── register.php           — User registration (Admin-managed)
│
├── admin/
│   ├── create_user.php        — Create new portal user
│   ├── manage_users.php       — Edit roles, companies, active status
│   ├── company_master.php     — Manage battalion companies
│   ├── platoon_section_master.php  — Manage platoons and sections
│   ├── manage_soldiers.php    — Full nominal roll CRUD
│   ├── nominal_roll_analytics.php  — Visual analytics dashboard
│   ├── approve_attendance.php — Approve clerk submissions
│   ├── approve_leave.php      — Approve leave requests
│   └── notification_settings.php  — System alerts config
│
├── clerk/
│   ├── daily_attendance.php   — Core daily attendance entry
│   ├── leave_entry.php        — Leave request entry
│   ├── duty_roster.php        — Duty assignment entry
│   ├── course_entry.php       — Course/cadre entry
│   ├── sick_report.php        — Sick report / MH entry
│   ├── company_strength.php   — Company strength summary
│   └── my_activity.php        — Clerk's activity log
│
├── adjt_sa/
│   ├── battalion_parade_state.php  — BN-level parade state
│   ├── approve_parade_state.php    — Approve and lock parade state
│   ├── manpower_shortages.php      — Record shortfalls
│   └── duty_overview.php           — BN-wide duty view
│
├── reports/
│   ├── daily_parade_state.php      — Official parade state report
│   ├── coy_strength_summary.php    — Company-wise strength
│   ├── leave_course_duty_report.php — LCD report
│   ├── attendance_analytics.php    — Trend analytics
│   ├── archive.php                 — Historical states
│   ├── download_pdf.php            — PDF export
│   └── download_excel.php          — Excel export
│
├── assets/
│   ├── css/style.css          — All styles (Indian Army theme, Fira Sans / Fira Code fonts)
│   └── js/
│       ├── script.js          — Date defaults
│       └── slider.js          — Image carousel
│
├── config/
│   └── db.php                 — Database connection (bn_parade_state_db)
│
├── database/
│   ├── bn_full_setup.sql      — COMPLETE setup: schema + all seed data (USE THIS)
│   ├── bn_import_data.sql     — Supplementary data only (requires existing schema)
│   ├── bn_parade_state.sql    — Original base schema reference
│   └── generate_import_data.py — Regenerates the SQL files from Python
│
└── includes/
    ├── head_meta.php          — Shared HTML head + dark/light mode theme script
    └── csrf.php               — CSRF protection helpers
```

---

## Appendix B — Database Tables

| Table | Purpose |
|---|---|
| `companies` | Battalion companies (HQ, A, B, C, D, SP) |
| `platoons` | Platoons and sections per company |
| `users` | Portal user accounts with roles |
| `personnel` | Soldier nominal roll — Army No, Rank, Name, Company, plus 12 extended fields (DOB, blood group, home state, medical category, leave balances, etc.) |
| `attendance` | Daily attendance records with approval status |
| `leave_records` | Leave requests with from/to dates and return status |
| `duty_roster` | Duty assignments by soldier and date |
| `course_records` | Course and cadre attendance records |
| `sick_reports` | Sick report and MH admission records |
| `manpower_shortages` | Recorded shortfalls by company and date |
| `activity_logs` | Audit trail of all user actions |

---

## Appendix C — Role Access Matrix

| Module | ADMIN | ADJT_SA | CHM_CLERK | USER |
|---|:---:|:---:|:---:|:---:|
| View Nominal Roll | ✓ | ✓ | ✓ | ✓ |
| Nominal Roll Analytics | ✓ | ✓ | ✓ | ✓ |
| Daily Attendance Entry | ✓ | — | ✓ (own coy) | — |
| Leave Entry | ✓ | — | ✓ (own coy) | — |
| Course Entry | ✓ | — | ✓ (own coy) | — |
| Duty Roster | ✓ | — | ✓ (own coy) | — |
| Sick Report Entry | ✓ | — | ✓ (own coy) | — |
| Company Strength | ✓ | — | ✓ (own coy) | — |
| Approve Attendance | ✓ | — | — | — |
| Approve Leave | ✓ | — | — | — |
| Battalion Parade State | ✓ | ✓ | — | — |
| Approve Parade State | ✓ | ✓ | — | — |
| Manpower Shortages | ✓ | ✓ | — | — |
| Duty Overview | ✓ | ✓ | — | — |
| Daily Parade State Report | ✓ | ✓ | — | ✓ |
| All Reports | ✓ | ✓ | — | ✓ |
| Create / Manage Users | ✓ | — | — | — |
| Company Master | ✓ | — | — | — |
| Platoon / Section Master | ✓ | — | — | — |
| Notification Settings | ✓ | — | — | — |

---

*Version 1.2 — covers complete system including database setup, dark/light mode, UI v2 (Fira Sans / Fira Code / SVG icons), and personnel extended data. For technical issues, contact the System Administrator.*
