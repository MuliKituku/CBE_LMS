# 🎓 CBE Learning Management System

A web-based **Competency-Based Education (CBE) Learning Management System** built for Kenyan schools. It supports the full student lifecycle — from online enrollment and approval, to lesson delivery, competency-based grading, pathway recommendations, and parent engagement.

---

## 📋 Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [User Roles](#user-roles)
- [Project Structure](#project-structure)
- [Getting Started](#getting-started)
- [Environment Variables](#environment-variables)
- [Database Setup](#database-setup)
- [Screenshots](#screenshots)
- [License](#license)

---

## ✨ Features

- **Online Enrollment** — Students enroll with parent details, birth certificate & passport photo upload
- **Admin Approval Workflow** — Admins review, approve, or reject enrollments with automated email notifications
- **Role-Based Access Control** — Separate dashboards and permissions for Admin, Teacher, Student, and Parent
- **Lesson Management** — Teachers create, publish, and manage lessons with file/media attachments
- **Competency-Based Grading** — Assessments graded against CBE competency scales; admin can override grades
- **Pathway Recommendation Engine** — Interest surveys generate Senior School pathway recommendations
- **Discussion Threads** — Students and teachers engage in per-lesson discussion boards
- **Announcements** — Targeted announcements to all users or specific roles/individuals
- **Audit Logs** — Full activity trail for admin accountability
- **Password Management** — Forced password change on first login; admin-initiated resets with email delivery
- **Print-Ready Dashboards** — All dashboards include CSS print media query for clean report printing

---

## 🛠 Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8+ |
| Architecture | Custom MVC (no framework) |
| Database | MySQL (via PDO) |
| Email | PHPMailer 6.x (SMTP/Gmail) |
| Server | Apache (XAMPP) |
| Frontend | HTML5, Vanilla CSS, JavaScript |
| Environment | `.env` file (custom `Env` parser) |
| Dependencies | Composer |

---

## 👥 User Roles

| Role | Capabilities |
|---|---|
| **Admin** | Manage users, approve enrollments, override grades, manage content, audit logs, pathway settings |
| **Teacher** | Create/manage lessons, grade assessments, view discussions, submit resource requests |
| **Student** | View lessons, submit assessments, take pathway survey, view results & announcements |
| **Parent** | View child's progress, grades, announcements, and pathway recommendations |

---

## 📁 Project Structure

```
CBE_LMS/
├── app/
│   ├── controllers/        # AdminController, AuthController, TeacherController, etc.
│   ├── models/             # User, Student, TeacherModel, AdminModel, etc.
│   └── views/              # HTML views per role (admin/, teacher/, student/, parent/)
├── config/
│   └── database.php        # PDO database connection (reads from .env)
├── core/
│   ├── Controller.php      # Base controller
│   ├── Env.php             # .env file parser
│   ├── Mailer.php          # PHPMailer wrapper (reads from .env)
│   ├── Model.php           # Base model
│   └── Router.php          # URL router
├── database/               # SQL schema files
├── public/
│   ├── index.php           # Application entry point
│   ├── css/                # Stylesheets
│   ├── js/                 # JavaScript
│   └── uploads/            # User-uploaded files (gitignored)
├── vendor/                 # Composer dependencies (gitignored)
├── .env.example            # Environment variable template
├── .gitignore
├── .htaccess               # URL rewriting
├── composer.json
└── migrate.php             # Database migration runner
```

---

## 🚀 Getting Started

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (PHP 8+, Apache, MySQL)
- [Composer](https://getcomposer.org/)
- A Gmail account with **2FA enabled** and an [App Password](https://myaccount.google.com/apppasswords) generated

### Installation

**1. Clone the repository**
```bash
git clone https://github.com/MuliKituku/CBE_LMS.git
cd CBE_LMS
```

**2. Install PHP dependencies**
```bash
composer install
```

**3. Configure environment variables**
```bash
cp .env.example .env
```
Then open `.env` and fill in your database and mail credentials (see [Environment Variables](#environment-variables)).

**4. Set up the database**

Create a MySQL database named `cbelms` (or whatever you set in `.env`), then run:
```bash
php migrate.php
```
Or import your schema SQL file manually via phpMyAdmin.

**5. Configure Apache**

Place the project in your XAMPP `htdocs` folder and ensure `mod_rewrite` is enabled. The included `.htaccess` handles URL routing.

**6. Access the application**
```
http://localhost/CBE_LMS/public/index.php
```

---

## 🔐 Environment Variables

Copy `.env.example` to `.env` and set the following:

```env
# Application
APP_NAME="CBE Learning Management System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost
APP_BASE_PATH=/CBE_LMS
APP_TIMEZONE=Africa/Nairobi

# Database
DB_HOST=localhost
DB_NAME=cbelms
DB_USERNAME=root
DB_PASSWORD=your-db-password

# Mail (Gmail SMTP)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your-gmail-app-password
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME="CBE LMS"
```

---

## 🗄 Database Setup

The system uses MySQL. After creating your database and configuring `.env`, run the migration script:

```bash
php migrate.php
```

This executes the schema SQL file located in `/database/`.

---

## 📄 License

This project is developed as an academic research project for the Kenyan Competency-Based Education curriculum.

---

<p align="center">Built with ❤️ for Kenyan CBE Schools</p>
