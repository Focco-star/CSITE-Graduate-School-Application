# CSITE Graduate School Application

A web-based application system for the Ateneo de Zamboanga University (AdZU) College of Science and Information Technology and Engineering (CSITE) Graduate School. Students use it to apply for capstone, seminar paper, and thesis presentations, submit their requirements, and track their progress. Coordinators use it to review applications, verify documents, record payments, assign panels, and schedule presentations.

It is a plain PHP + MySQL application that runs on XAMPP. There is no build step and no framework to install.

---

## Group Members

- Marc Laurence M. Arbilera
- Rhett Ushley E. Epino
- John Matthew S.J. Gler
- Sean Benedict D. Recto
- Robbie Ryan A. Torres

---

## Table of Contents

[Group Members](#group-members)

1. [Purpose](#1-purpose)
2. [Features](#2-features)
3. [How the Workflow Works](#3-how-the-workflow-works)
4. [Tools and Software Used](#4-tools-and-software-used)
5. [Requirements](#5-requirements)
6. [Installation](#6-installation)
7. [How to Run](#7-how-to-run)
8. [Test Accounts](#8-test-accounts)
9. [Project Structure](#9-project-structure)
10. [Configuration](#10-configuration)
11. [Optional Integrations](#11-optional-integrations)
12. [Troubleshooting](#12-troubleshooting)
13. [Documentation](#13-documentation)

---

## 1. Purpose

Before this system, students had to visit the Graduate School office to submit forms, adviser endorsements, and payment receipts, and to ask about their presentation schedule.

This system puts that process online:

- Students submit applications and documents from anywhere.
- Coordinators review everything in one dashboard.
- Both sides see the same status, so nobody has to ask "what is the status of my application?"

Every application follows a fixed set of stages based on the student's program and research track, so requirements and approvals are consistent.

### Who uses it

| Role | What they do |
|---|---|
| **Student** | Registers, applies for a presentation, uploads requirements, downloads templates, tracks status |
| **Coordinator** | Reviews applications, verifies documents, records payments, schedules presentations, manages panels and templates |

---

## 2. Features

### Student portal

- **Account registration** using an ADZU email and ADZU ID number.
- **Login** with email and password, or with Google Sign-In.
- **Dashboard** showing current progress, pending requirements, and the next step.
- **Online application** for the appropriate presentation stage of the student's program.
- **Draft saving**, so a partly filled application can be resumed later.
- **Document upload** for concept papers, proposals, final papers, adviser endorsements, graduate school endorsements, and payment receipts (up to 20 MB per file).
- **Document status tracking**: submitted, verified, incomplete, or needs revision, with coordinator comments.
- **Templates and forms** download, filtered by program and stage.
- **Update notifications** when the official templates in Google Drive change.
- **Payment and receipt details** recorded by the coordinator.
- **Profile editing** and viewing or downloading previously submitted files.

### Coordinator portal

- **Dashboard** with application statistics, program charts, and a presentation calendar.
- **Applications queue** with search, filters, and a full review page.
- **Document verification** (verify, mark incomplete, request revision).
- **Payment recording** with receipt number and payment date.
- **Presentation result** recording (Approved or Requires Revision), where an approved result completes the application automatically.
- **Presentation scheduling** with date, time, venue, panel members, and panel roles.
- **Student search** on the schedule page by name, email, or program.
- **Pools** for advisers, panel members, and documenters.
- **Template library management**, including syncing the official forms from Google Drive.
- **Archived applications** that can be restored to the active list or permanently deleted.

---

## 3. How the Workflow Works

### Presentation tracks

The student's program decides which track applies:

| Track | Stages |
|---|---|
| **Thesis** | Concept Paper Presentation → Thesis Proposal Presentation → Final Thesis Defense |
| **Capstone** | Capstone Proposal Presentation → Final Capstone Presentation |
| **Seminar Paper** | Seminar Paper Proposal Presentation → Final Seminar Paper Presentation |

Each stage has its own required documents and endorsement forms.

### Application status flow

```text
Draft
 └─> Submitted
      └─> Under Review
            ├─> Requires Revision  (returned to the student)
            └─> For Payment
                  └─> Payment Recorded
                        └─> Ready for Presentation
                              └─> Scheduled for Presentation
                                    └─> Completed
```

Key rules built into the system:

- An application can only become **Ready for Presentation** once the required documents are verified, the adviser and Graduate School endorsements are present, and the payment is recorded.
- An application can only be **Scheduled** once it is ready.
- Recording an **Approved** presentation result completes the application and its schedule.
- A **Requires Revision** result sends the application back to the student for corrections instead of completing it.
- Editing a schedule keeps a completed schedule completed; it never downgrades it.
- Student progress is computed from document and milestone statuses, not stored as a separate score.

---

## 4. Tools and Software Used

### Development and hosting tools

| Tool | Version used | Purpose |
|---|---|---|
| **XAMPP** | Apache 2.4.58, PHP 8.2.12, MariaDB 10.4.32 | Local development environment. One control panel starts Apache and MySQL together. |
| **PHP** | 8.2.12 | Server-side language for every page and helper in this project. |
| **MySQL** | MariaDB 10.4.32 (MySQL compatible) | Stores students, applications, documents, schedules, panels, and payments. |
| **phpMyAdmin** | 5.2.1 | Creates the database, imports `csite_grad_school.sql`, and inspects or edits tables. |
| **InfinityFree** | Free PHP/MySQL hosting | Hosts the published copy of the system. See the note below. |

### Application stack

| Layer | Technology |
|---|---|
| **Web server** | Apache (bundled with XAMPP) |
| **Backend** | PHP 8.2, no framework |
| **Database access** | PDO prepared statements |
| **Frontend** | HTML5, CSS3, vanilla JavaScript (no build tools) |
| **Icons and fonts** | Font Awesome 6, bundled web fonts |
| **Word preview** | Mammoth.js (in-browser DOCX preview) |
| **Sign-in** | Google Identity Services (optional) |
| **Template sync** | Google Drive public folder read through cURL (optional) |

### PHP extensions used

`pdo_mysql`, `curl`, `mbstring`, `fileinfo`, `json`, `dom`, `xml`, `openssl`

These are enabled by default in a standard XAMPP installation.

### Hosted copy

The system was published on **InfinityFree** at <http://csite-grad-school.infinityfree.me/>.

That online copy is **outdated and incomplete**. Many changes have been made since it was published, so features and fixes that already exist in this repository are missing from it, and its data does not match the local database. Treat the local XAMPP copy as the current, reference version of the system.

---

## 5. Requirements

- Windows, macOS, or Linux with **XAMPP** installed (Apache + PHP 8.2 + MySQL/MariaDB). The project was tested on XAMPP with PHP 8.2.12.
- A web browser (Chrome, Edge, or Firefox).
- About 350 MB of free disk space (the project plus its bundled uploads), with room to grow as students submit more files.
- Internet access only for the optional Google features.

---

## 6. Installation

### Step 1: Copy the project into XAMPP

Place the project folder inside the XAMPP `htdocs` directory:

```text
C:\xampp\htdocs\CSITE-Graduate-School-Application
```

On this machine the XAMPP folder is at:

```text
C:\Users\asus\Documents\Computer Science\Code\Xamp
```

so the project lives at:

```text
C:\Users\asus\Documents\Computer Science\Code\Xamp\htdocs\CSITE-Graduate-School-Application
```

### Step 2: Start Apache and MySQL

Open the **XAMPP Control Panel** and press **Start** next to **Apache** and **MySQL**.

Both should stay green. If MySQL stops immediately, see [Troubleshooting](#12-troubleshooting).

### Step 3: Create the database

1. Open `http://localhost/phpmyadmin`
2. Click **New**
3. Database name: `csite_grad_school`
4. Collation: `utf8mb4_general_ci`
5. Click **Create**

### Step 4: Import the database

**Using phpMyAdmin:**

1. Select the `csite_grad_school` database.
2. Open the **Import** tab.
3. Choose the file `csite_grad_school.sql` from the project root.
4. Click **Import**.

**Using the command line (alternative):**

```bat
cd C:\Users\asus\Documents\Computer Science\Code\Xamp\mysql\bin
mysql -u root csite_grad_school < "C:\Users\asus\Documents\Computer Science\Code\Xamp\htdocs\CSITE-Graduate-School-Application\csite_grad_school.sql"
```

The dump creates the tables, views, triggers, and a set of seed records (programs, tracks, workflow stages, templates, and demo accounts).

### Step 5: Check the database credentials

Open `includes/config/db.php` and confirm these values match your MySQL setup:

```php
$host = 'localhost';
$db   = 'csite_grad_school';
$user = 'root';
$pass = '';
$port = '3306';
```

The defaults match a standard XAMPP installation, where the `root` user has an empty password.

### Step 6: Make sure the storage folder is writable

This folder must exist and be writable by the web server:

```text
storage/uploads     (student document uploads)
```

It is included in the project. On Windows with XAMPP it normally works without changes.

### Step 7: Open the site

```text
http://localhost/CSITE-Graduate-School-Application/
```

The root `index.php` redirects to `public/index.php`, which is the public home page.

### Optional: deploy to InfinityFree

The free hosting account does not allow triggers and does not need the two unused `v_*` views, so it uses a prepared dump instead of the main one:

1. Upload the project files to the InfinityFree `htdocs` folder.
2. Create a database in the InfinityFree control panel.
3. Import `deploy/csite_grad_school.infinityfree.sql` through phpMyAdmin.
4. Put the InfinityFree database host, name, user, and password in `includes/config/db.php`.
5. Open the domain. `BASE_URL` detects the hosted folder automatically.

The version online now was published this way and has not been updated since. Repeat these steps to publish the current code.

---

## 7. How to Run

1. Start **Apache** and **MySQL** from the XAMPP Control Panel.
2. Open `http://localhost/CSITE-Graduate-School-Application/` in a browser.
3. Choose one of the two entry points on the home page:

| Portal | URL |
|---|---|
| Student | `http://localhost/CSITE-Graduate-School-Application/student/login.php` |
| Coordinator | `http://localhost/CSITE-Graduate-School-Application/coordinator/login.php` |

4. Log in using the accounts in the next section.

Do not open the PHP files directly from the file system. They must be served by Apache through `http://localhost/...`.

### Live copy (InfinityFree)

The published copy is at <http://csite-grad-school.infinityfree.me/>.

It is kept for demonstration only and is **outdated and incomplete**. Features and fixes added to this repository after that upload are not there, and its data is older. Run the local XAMPP copy when you need the current version.

### Everyday use

**As a student**

1. Log in.
2. Open **Templates** and download the forms for your program and stage.
3. Open **Application**, fill in the details, and upload the requirements.
4. Submit and follow the status on the dashboard.

**As a coordinator**

1. Log in.
2. Open **Applications** and select a student to review.
3. Verify documents and record the payment.
4. When the application is ready, open **Presentation Schedules** to set the date, time, venue, and panel.
5. After the presentation, record the result as **Approved** to complete the application.

---

## 8. Test Accounts

All demo accounts below are part of `csite_grad_school.sql`, so a fresh import gives exactly these logins. Every account uses the password **`Password123`**.

### Accounts by role

| Role | Username (email) | Password | Access |
|---|---|---|---|
| **Coordinator** | `gpc-csite@adzu.edu.ph` | `Password123` | Full coordinator portal: applications, document verification, payments, schedules, panels, templates |
| **Student** | any account in the table below (32 total) | `Password123` | Student portal: dashboard, application, templates, profile |

Panel members and advisers **cannot log in**. They are records in the panel and adviser pools that the coordinator assigns to a presentation schedule.

### Student accounts

| Username (email) | Password | Student | Program | Track |
|---|---|---|---|---|
| `co222222@adzu.edu.ph` | `Password123` | Afus, Mila | MSCS | Thesis |
| `co230159@adzu.edu.ph` | `Password123` | Torres, Robbie | Master of Science in Computer Science (Thesis) | Thesis |
| `co240234@adzu.edu.ph` | `Password123` | Epino, Rhett | MSCS | Thesis |
| `co240527@adzu.edu.ph` | `Password123` | Arbilera, Marc | Master of Science in Computer Science (Thesis) | Thesis |
| `co251001@adzu.edu.ph` | `Password123` | Alonzo, Maria Clara | Master of Science in Computer Science | Thesis |
| `co251002@adzu.edu.ph` | `Password123` | Benedicto, Rafael | Master of Science in Computer Science | Thesis |
| `co251003@adzu.edu.ph` | `Password123` | Cabrera, Liza Marie | Master of Library and Information Science | Thesis |
| `co251004@adzu.edu.ph` | `Password123` | Dizon, Angelo | Master of Science in Computer Science | Thesis |
| `co251005@adzu.edu.ph` | `Password123` | Estrada, Hannah Jane | Master of Science in Computer Science | Thesis |
| `co251006@adzu.edu.ph` | `Password123` | Fajardo, Miguel Antonio | Master of Library and Information Science | Thesis |
| `co251007@adzu.edu.ph` | `Password123` | Galvez, Patricia Anne | Master of Science in Computer Science | Thesis |
| `co251008@adzu.edu.ph` | `Password123` | Hernandez, Kevin Paul | Master of Library and Information Science | Thesis |
| `co251009@adzu.edu.ph` | `Password123` | Ibarra, Sofia Nicole | Master of Science in Computer Science | Thesis |
| `co333333@adzu.edu.ph` | `Password123` | Kramer, Leighton | MSLIS | Thesis |
| `co240092@adzu.edu.ph` | `Password123` | Gler, John | Master in Information Technology | Capstone |
| `co250250@adzu.edu.ph` | `Password123` | Austin, James | Master in Science Education major in Chemistry (Capstone) | Capstone |
| `co251010@adzu.edu.ph` | `Password123` | Javier, Carlo Emmanuel | Master in Information Technology | Capstone |
| `co251011@adzu.edu.ph` | `Password123` | Katigbak, Angela Rose | Master in Information Technology | Capstone |
| `co251012@adzu.edu.ph` | `Password123` | Lagman, Robert John | Master in Library and Information System | Capstone |
| `co251013@adzu.edu.ph` | `Password123` | Magbanua, Denise Claire | Master in Information Technology | Capstone |
| `co251014@adzu.edu.ph` | `Password123` | Ocampo, Christian James | Master in Information Technology | Capstone |
| `co251015@adzu.edu.ph` | `Password123` | Peralta, Jasmine Mae | Master in Science Education major in Chemistry | Capstone |
| `co251016@adzu.edu.ph` | `Password123` | Quijano, Mark Anthony | Master in Science Education major in Biology | Capstone |
| `co251017@adzu.edu.ph` | `Password123` | Rivera, Erika Lyn | Master in Library and Information System | Capstone |
| `co251018@adzu.edu.ph` | `Password123` | Salvador, Paolo Miguel | Master of Science in Mechanical Engineering | Capstone |
| `co2402323@adzu.edu.ph` | `Password123` | Lee, Kay | MATH | Seminar |
| `co251019@adzu.edu.ph` | `Password123` | Tamayo, Alyssa Marie | Master in Mathematics Education | Seminar |
| `co251020@adzu.edu.ph` | `Password123` | Uy, Samantha Joy | Master in Mathematics Education | Seminar |
| `co251021@adzu.edu.ph` | `Password123` | Valdez, Patricia Nicole | Master in Science Education major in Physics | Seminar |
| `co251022@adzu.edu.ph` | `Password123` | Wenceslao, John Patrick | Master in Science Education major in General Science | Seminar |
| `co251023@adzu.edu.ph` | `Password123` | Yumul, Rachel Anne | Master in Science Education major in Chemistry | Seminar |
| `jajisahaa@adzu.edu.ph` | `Password123` | Jaji, Shalimar | MATH | Seminar |

The track column is the most useful thing to test with: each track has its own stages and required documents.

> These are demonstration accounts only. Change or remove them before using the system with real student data.

---

## 9. Project Structure

```text
CSITE-Graduate-School-Application/
├── index.php                  Redirects to public/index.php
├── template_sync.php          JSON endpoint that refreshes templates from Google Drive
├── csite_grad_school.sql      Database dump (tables, views, triggers, seed data)
│
├── public/
│   └── index.php              Public home page (features, process, tracks)
│
├── student/                   Student portal pages
│   ├── login.php, register.php, logout.php
│   ├── dashboard.php, application.php, status.php
│   ├── templates.php, profile.php
│   ├── upload.php, download.php, view_document.php
│   └── google_auth.php
│
├── coordinator/               Coordinator portal pages
│   ├── login.php, logout.php, dashboard.php
│   ├── applications/          Review, manage, archived applications
│   ├── schedule/              Schedules, calendar, panel assignment
│   ├── students/              Student records
│   ├── templates/             Template library management
│   ├── advisers/              Adviser pool
│   ├── panel/                 Panel members
│   └── documenter/            Documenter pool
│
├── includes/
│   ├── config/                Settings, helpers, database, Google client ID
│   │   ├── config.php         Loader for the modules below
│   │   ├── db.php             PDO connection and CRUD helpers
│   │   ├── google_config.php  Google OAuth client ID
│   │   └── modules/           19 numbered modules (settings, workflow,
│   │                          applications, documents, schedules, ...)
│   ├── layout/                header, footer, sidebar, slideshow
│   ├── components/            calendar, program chart, template library
│   └── integrations/          Google Drive, Google Sign-In, template sync
│
├── assets/
│   ├── css/                   Shared, student, coordinator, application styles
│   ├── js/                    Shared app script, dashboard, calendar scripts
│   ├── img/                   Logos, seals, illustrations
│   ├── papers/                Bundled paper templates
│   └── vendor/                Font Awesome, fonts, Mammoth.js
│
├── storage/
│   └── uploads/               Student-submitted documents (runtime data)
│
├── database_migrations/       Ordered SQL migrations (newest changes)
├── deploy/                    Deployment helpers and hosting-specific SQL dumps
└── docs/                      Final project documentation (DOCX and PDF)
```

The `includes/config/config.php` file is only a small loader. The real helpers live in the numbered files under `includes/config/modules/`, grouped by responsibility: settings, UI and URLs, workflow, people, applications, documents, templates, database access, schedules, progress, milestones, drafts, and requirements.

---

## 10. Configuration

| What to change | Where |
|---|---|
| Database host, name, user, password, port | `includes/config/db.php` |
| Site name and tagline | `includes/config/modules/00-settings.php` |
| Program and track mapping, statuses, colors | `includes/config/modules/00-settings.php` |
| Required documents per stage | `includes/config/modules/20-workflow-definitions.php` |
| Google Sign-In client ID | `includes/config/google_config.php` |
| Google Drive template folder | `includes/integrations/gdrive.php` |

### Base URL

The base URL is detected automatically from the folder that serves the project, so a copy placed in any `htdocs` subfolder works without editing code. It can also be overridden with the `BASE_URL` environment variable.

---

## 11. Optional Integrations

The system works fully without these. They add convenience.

### Google Sign-In

Lets students log in with their Google account instead of a password.

1. Open <https://console.cloud.google.com/apis/credentials>
2. Create an **OAuth 2.0 Client ID** of type **Web application**.
3. Add `http://localhost` under **Authorised JavaScript origins**.
4. Paste the Client ID into `includes/config/google_config.php`.

### Google Drive template sync

Mirrors the official forms folder into the portal and shows a notification badge when templates change.

- The folder ID is set in `includes/integrations/gdrive.php`.
- The folder must be shared as **Anyone with the link**.
- Templates are re-checked at most every 15 minutes.
- The endpoint `template_sync.php` performs a sync when the Templates page is opened.

---

## 12. Troubleshooting

### "Database Connection Error" appears on every page

- MySQL is not running. Start it from the XAMPP Control Panel.
- The database name or credentials do not match `includes/config/db.php`.
- The database was not imported. Import `csite_grad_school.sql` again.

### MySQL starts, then stops after a few seconds

Open `mysql\data\mysql_error.log` and read the last lines. Typical causes:

- A corrupted system table in `mysql\data\mysql\` (the log names it).
- Another MySQL instance already using port 3306.
- A damaged or incomplete data folder.

### Uploads fail or documents do not appear

- Check that `storage/uploads` exists and is writable.
- Check `upload_max_filesize` and `post_max_size` in `php.ini` (each file is limited to 20 MB by the application).
- Confirm the file type is allowed (PDF, DOC, DOCX).

### Template badge never updates

- Check the internet connection.
- Confirm the Drive folder is still shared publicly.
- Delete `storage/.template-sync.lock` if a previous sync was interrupted.
- Visit `template_sync.php` once to force a check.

### Styles or icons are missing

Hard-refresh the page (`Ctrl` + `F5`). If it persists, confirm the `assets/` folder was copied completely.

### A page shows a blank screen

Enable PHP error display in `php.ini` (`display_errors = On`), reload, and read the error. Also check the Apache `error.log`.

---

## 13. Documentation

- `docs/CSITE-Graduate-School-Application-Final-Project.pdf` — full project documentation.
- `docs/CSITE-Graduate-School-Application-Final-Project.docx` — editable version.
- `includes/README.md` — how the shared PHP files are organized.
- `database_migrations/` — every schema change, in date order.
- `deploy/` — deployment helpers and hosting-specific database dumps.

---

## Notes for Maintainers

- Application tables are prefixed with `application_`; documents live in `storage/uploads` plus a row in `application_documents`.
- Business rules (statuses, gates, stage order, milestones) belong in `includes/config/modules/`, not in page files.
- The database is the source of truth. Do not delete `storage/uploads` files without also cleaning their `application_documents` rows.
- The project is intentionally framework-free. Keep new code in the existing style: procedural PHP pages, helper functions in the config modules, and CSS/JS split by responsibility under `assets/`.
