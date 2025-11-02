# HRMS System - Human Resource Management System

<p align="center">
  <strong>A complete, production-ready HR management solution with attendance tracking, leave management, and employee management.</strong>
</p>

---

## 📋 Table of Contents

- [About](#about)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [API Documentation](#api-documentation)
- [Database Schema](#database-schema)
- [Login Credentials](#login-credentials)
- [Roles & Permissions](#roles--permissions)
- [Contributing](#contributing)
- [License](#license)

---

## 🎯 About

**HRMS System** is a comprehensive web-based Human Resource Management platform designed to streamline HR operations. It provides complete solutions for employee management, attendance tracking, leave management, performance reviews, and administrative auditing.

Built with **PHP**, **MySQL**, **Bootstrap**, and **jQuery**, this system is fully responsive, feature-rich, and ready for deployment.

### Key Highlights
- ✅ **Multi-role Authentication** (Employee, HR, Superadmin)
- ✅ **Real-time Attendance Tracking** with manual entry support
- ✅ **Comprehensive Leave Management** with approval workflow
- ✅ **Performance Review System** with 1-5 ratings
- ✅ **Complete Audit Logging** for all administrative actions
- ✅ **Creator Tracking** for accountability
- ✅ **Export Functionality** (CSV/Excel)
- ✅ **Responsive Design** for all devices

---

## ⭐ Features

### 1. **Authentication & Authorization**
- Role-based login system (Employee, HR, Superadmin)
- Session management
- Secure session handling
- Auto-logout functionality

### 2. **Attendance Management** ⭐ NEW
- ✅ Employee self-marking (auto-attendance)
- ✅ Manual entry by HR/Superadmin
- ✅ **Entry tracking (who entered the record)**
- ✅ **Modification timestamps**
- ✅ **Manual vs. Auto-marked distinction**
- ✅ Time-in and time-out tracking
- ✅ Filter & search functionality
- ✅ Export to CSV/Excel

### 3. **Leave Management**
- Apply for leave (CL, SL, Weekoff, Holiday)
- Leave approval/rejection workflow
- HR comments on leave requests
- Active leave monitoring
- Days remaining calculation
- Leave progress tracking

### 4. **Employee Management** ⭐ ENHANCED
- Add employees with **creator tracking**
- View all employees with creator info
- Filter by creator (HR/Superadmin)
- Employee status management
- Designation assignment
- **Creator accountability**

### 5. **HR Management**
- Add HR profiles (Superadmin only)
- HR list with **employee counts**
- Track HR performance
- Manage team members

### 6. **Designation Management**
- Add job designations
- Promote employees
- Description for each role
- Promotion tracking

### 7. **Performance Management**
- Add reviews (1-5 rating scale)
- Detailed comments
- Average rating calculation
- Review history

### 8. **Holiday Management**
- Add company holidays
- Holiday calendar
- Upcoming holidays list
- Company-wide visibility

### 9. **Dashboard & Analytics**
- **Employee Dashboard:** Personal stats, quick actions
- **HR Dashboard:** Employee management, leave approvals
- **Superadmin Dashboard:** System-wide management

### 10. **Audit Logging**
- Complete action history
- Track who did what
- Timestamp for each action
- Action type filtering

### 11. **Export & Reporting**
- Export attendance records
- Filtered export capabilities
- CSV format
- Excel format

---

## 🛠️ Tech Stack

### Backend
- **Language:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Pattern:** MVC Architecture

### Frontend
- **HTML5** - Markup
- **CSS3** - Styling
- **Bootstrap 4.5.2** - Responsive framework
- **jQuery 3.6** - JavaScript
- **AJAX** - Async operations

### Libraries
- **Font Awesome 5.15.4** - Icons
- **Prepared Statements** - SQL security
- **JSON API** - Data exchange

---

## 📁 Project Structure
hrms-system/
├── index.html # Login page
├── employee-dashboard.html # Employee interface
├── hr-dashboard.html # HR interface
├── superadmin-dashboard.html # Superadmin interface
├── attendance-management.html # Manual attendance entry
├── active-leave-list.html # Leave monitoring
├── audit-log.html # Audit log viewer
│
├── config/
│ └── db.php # Database configuration
│
├── api/
│ └── handler.php # API (40+ functions)
│
├── database.sql # Database schema
├── README.md # Documentation
└── .gitignore


---

## 🚀 Installation

### Prerequisites
- PHP 7.4+
- MySQL 5.7+
- Web server
- Modern browser

### Step 1: Clone Repository
git clone https://github.com/s-rasaili/hrms-system.git
cd hrms-system

### Step 2: Create Database
mysql -u root -p hrms_system < database.sql

### Step 3: Setup Files
Copy to web directory
cp -r hrms-system /var/www/html/

### Step 4: Verify
http://localhost/hrms-system/


## ⚙️ Configuration

### Edit `config/db.php`:
$servername = 'localhost';
$username = 'root';
$password = '';
$database = 'hrms_system';


---

## 💡 Usage

### 1. Login
Select role → Enter email & password → Submit

### 2. Navigate Dashboards
- **Employees:** Mark attendance, apply leaves
- **HR:** Manage employees, approve leaves
- **Superadmin:** Full system control

### 3. Mark Attendance
Status: Present/Absent
Comment: (Optional) Reason

### 4. Apply Leave
Type: CL/SL/Weekoff/Holiday
Start Date: YYYY-MM-DD
End Date: YYYY-MM-DD
Comment: Reason

### 5. Manual Attendance (HR Only)
- Go to Attendance Management
- Add/edit attendance for any employee
- Track who entered the record

---

## 📡 API Documentation

### Base URL
POST api/handler.php

### Authentication
{
"action": "login",
"role": "employee|hr|superadmin",
"email": "user@example.com",
"password": "password"
}

### Attendance
{
"action": "add_manual_attendance",
"employee_id": 1,
"date": "2025-11-02",
"status": "present",
"in_time": "09:00:00",
"out_time": "18:00:00",
"comment": "reason"
}

### Leave
{
"action": "apply_leave",
"leave_type": "cl",
"start_date": "2025-11-05",
"end_date": "2025-11-07",
"comment": "reason"
}

### Employee
{
"action": "add_employee",
"name": "John Doe",
"email": "john@example.com",
"password": "password",
"designation_id": 1
}

[See code for complete API documentation]

---

## 🗄️ Database Schema

### Tables
1. **designations** - Job titles
2. **users** - Accounts (Employee/HR/Superadmin)
3. **attendance** - Attendance records with tracking
4. **leaves** - Leave applications
5. **performance** - Performance reviews
6. **holidays** - Company holidays
7. **audit_log** - Admin action history

### Key Features
- `attendance.entered_by` - Manual entry tracker
- `attendance.is_manual` - Manual vs Auto flag
- `users.created_by` - Creator tracking
- `audit_log` - Complete history

---

## 🔐 Login Credentials

### Demo Users

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@hrms.com | admin123 |
| HR 1 | hr1@hrms.com | hr123 |
| HR 2 | hr2@hrms.com | hr123 |
| Employee 1 | john@hrms.com | john123 |
| Employee 2 | jane@hrms.com | jane123 |
| Employee 3 | mike@hrms.com | mike123 |
| Employee 4 | sarah@hrms.com | sarah123 |
| Employee 5 | robert@hrms.com | robert123 |

⚠️ **Change passwords in production!**

---

## 👥 Roles & Permissions

### Employee
- ✅ Mark own attendance
- ✅ View attendance history
- ✅ Apply for leave
- ✅ View personal stats
- ❌ Cannot manage others

### HR Manager
- ✅ All employee features
- ✅ Add employees (tracked)
- ✅ Manual attendance entry
- ✅ Approve/reject leaves
- ✅ Add performance reviews
- ✅ Add holidays
- ❌ Cannot add HR users

### Superadmin
- ✅ All HR features
- ✅ Add HR users
- ✅ View audit logs
- ✅ Full system access

---

## 🔧 Deployment

### Production Checklist
- [ ] Use HTTPS/SSL
- [ ] Hash passwords (bcrypt)
- [ ] Implement 2FA
- [ ] Setup automated backups
- [ ] Configure monitoring
- [ ] Use environment variables
- [ ] Setup rate limiting

---

## 🐛 Known Issues

### Current Version (1.0)
- Passwords in plain text (demo only)
- Local file storage only
- No email notifications
- Manual backups required

### Future Enhancements
- [ ] Password hashing
- [ ] Email notifications
- [ ] Mobile app
- [ ] Advanced analytics
- [ ] Auto backup
- [ ] 2FA support

---

## 🤝 Contributing

1. Fork repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

---

## 📝 License

MIT License - See LICENSE file for details

### Summary
- ✅ Free for personal/commercial use
- ✅ Modify and distribute
- ❌ No warranty provided

---

## 📞 Support

- **Issues:** [GitHub Issues](https://github.com/s-rasaili/hrms-system/issues)
- **Email:** sacrasa3@gmail.com
- **Documentation:** See inline code comments

---

## 📊 System Stats

| Metric | Value |
|--------|-------|
| Code Lines | 4840+ |
| PHP Lines | 1000+ |
| HTML Lines | 2500+ |
| Database Tables | 7 |
| API Functions | 40+ |
| Features | 25+ |

---

## ⭐ If Helpful

- ⭐ **Star** this repo
- 👁️ **Watch** for updates
- 🍴 **Fork** for your use
- 📢 **Share** with others

---

**Made with ❤️ | Last Updated: November 2, 2025**



