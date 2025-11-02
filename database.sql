-- ==========================================
-- HRMS Database Complete Schema (Error-Free)
-- ==========================================

-- 1. Drop database if exists
DROP DATABASE IF EXISTS hrms_system;

-- 2. Create database
CREATE DATABASE hrms_system;
USE hrms_system;

-- 3. Designations Table (Create first - no dependencies)
CREATE TABLE designations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(100) NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Users Table
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role ENUM('employee','hr','superadmin') NOT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  designation_id INT DEFAULT NULL,
  created_by INT DEFAULT NULL,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (designation_id) REFERENCES designations(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 5. Attendance Table
CREATE TABLE attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  date DATE NOT NULL,
  status ENUM('present','absent') NOT NULL,
  in_time TIME DEFAULT NULL,
  out_time TIME DEFAULT NULL,
  comment TEXT,
  entered_by INT DEFAULT NULL,
  modified_at TIMESTAMP NULL DEFAULT NULL,
  is_manual TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (entered_by) REFERENCES users(id) ON DELETE SET NULL,
  UNIQUE KEY unique_attendance (user_id, date)
);

-- 6. Leaves Table
CREATE TABLE leaves (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  leave_type ENUM('cl','sl','weekoff','holiday') NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  comment TEXT,
  hr_comment TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 7. Performance Table
CREATE TABLE performance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  review_date DATE NOT NULL,
  comments TEXT,
  rating TINYINT,
  reviewed_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE CASCADE
);

-- 8. Holidays Table
CREATE TABLE holidays (
  id INT AUTO_INCREMENT PRIMARY KEY,
  date DATE NOT NULL UNIQUE,
  description VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 9. Audit Log Table
CREATE TABLE audit_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  action_by INT NOT NULL,
  action_type VARCHAR(100) NOT NULL,
  target_user_id INT DEFAULT NULL,
  details TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (action_by) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ==========================================
-- INSERT INITIAL DATA
-- ==========================================

-- 1. Insert Designations
INSERT INTO designations (title, description) VALUES
('Manager', 'Team Manager'),
('Senior Developer', 'Senior level developer'),
('Junior Developer', 'Junior level developer'),
('Sales Executive', 'Sales department'),
('HR Executive', 'HR department'),
('Accountant', 'Finance department'),
('Admin', 'Administrative staff');

-- 2. Insert Superadmin User (id = 1)
INSERT INTO users (role, name, email, password, status) VALUES
('superadmin', 'Super Admin', 'admin@hrms.com', 'admin123', 'active');

-- 3. Insert HR Users (id = 2, 3)
INSERT INTO users (role, name, email, password, status, created_by) VALUES
('hr', 'HR Manager 1', 'hr1@hrms.com', 'hr123', 'active', 1),
('hr', 'HR Manager 2', 'hr2@hrms.com', 'hr123', 'active', 1);

-- 4. Insert Employee Users (id = 4, 5, 6, 7, 8)
INSERT INTO users (role, name, email, password, designation_id, status, created_by) VALUES
('employee', 'John Doe', 'john@hrms.com', 'john123', 2, 'active', 2),
('employee', 'Jane Smith', 'jane@hrms.com', 'jane123', 3, 'active', 2),
('employee', 'Mike Johnson', 'mike@hrms.com', 'mike123', 1, 'active', 3),
('employee', 'Sarah Williams', 'sarah@hrms.com', 'sarah123', 4, 'active', 3),
('employee', 'Robert Brown', 'robert@hrms.com', 'robert123', 5, 'active', 2);

-- 5. Insert Sample Attendance Records
INSERT INTO attendance (user_id, date, status, in_time, out_time, created_at) VALUES
(4, '2025-11-02', 'present', '09:00:00', '18:00:00', NOW()),
(5, '2025-11-02', 'present', '09:15:00', '17:45:00', NOW()),
(6, '2025-11-02', 'present', '08:45:00', '18:15:00', NOW()),
(7, '2025-11-02', 'absent', NULL, NULL, NOW()),
(8, '2025-11-02', 'present', '09:30:00', '18:30:00', NOW()),
(4, '2025-11-01', 'present', '09:10:00', '17:50:00', NOW()),
(5, '2025-11-01', 'present', '09:20:00', '18:00:00', NOW());

-- 6. Insert Sample Holidays
INSERT INTO holidays (date, description) VALUES
('2025-11-15', 'Diwali'),
('2025-11-25', 'Thanksgiving'),
('2025-12-25', 'Christmas'),
('2026-01-26', 'Republic Day'),
('2026-03-08', 'Women Day');

-- 7. Insert Sample Leaves
INSERT INTO leaves (user_id, leave_type, start_date, end_date, status, comment) VALUES
(4, 'cl', '2025-11-05', '2025-11-07', 'pending', 'Personal work'),
(5, 'sl', '2025-11-10', '2025-11-10', 'approved', 'Doctor appointment'),
(6, 'cl', '2025-11-15', '2025-11-17', 'rejected', 'Not approved');

-- ==========================================
-- VERIFY TABLES CREATED
-- ==========================================
SHOW TABLES;
