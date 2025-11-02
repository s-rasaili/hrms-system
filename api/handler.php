<?php
header('Content-Type: application/json');
require_once '../config/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the action from POST
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Route to appropriate function
switch($action) {
    // Authentication
    case 'login':
        login();
        break;
    case 'logout':
        logout();
        break;
    
    // Attendance
    case 'mark_attendance':
        markAttendance();
        break;
    case 'get_attendance':
        getAttendance();
        break;
    case 'add_manual_attendance':
        addManualAttendance();
        break;
    case 'get_all_attendance':
        getAllAttendance();
        break;
    case 'update_manual_attendance':
        updateManualAttendance();
        break;
    
    // Leave Management
    case 'apply_leave':
        applyLeave();
        break;
    case 'get_leaves':
        getLeaves();
        break;
    case 'manage_leave':
        manageLeave();
        break;
    
    // Employee Management
    case 'add_employee':
        addEmployee();
        break;
    case 'get_employees':
        getEmployees();
        break;
    case 'get_employees_created_by':
        getEmployeesCreatedBy();
        break;
    
    // HR Management
    case 'add_hr':
        addHR();
        break;
    case 'get_hr_list':
        getHRList();
        break;
    
    // Designation Management
    case 'get_designations':
        getDesignations();
        break;
    case 'add_designation':
        addDesignation();
        break;
    case 'update_designation':
        updateDesignation();
        break;
    
    // Performance Management
    case 'add_performance':
        addPerformance();
        break;
    case 'get_performance':
        getPerformance();
        break;
    
    // Holiday Management
    case 'add_holiday':
        addHoliday();
        break;
    case 'get_holidays':
        getHolidays();
        break;
    
    // Statistics & Reporting
    case 'get_dashboard_stats':
        getDashboardStats();
        break;
    case 'get_audit_log':
        getAuditLog();
        break;
    
    // Export
    case 'export_attendance':
        exportAttendance();
        break;
    case 'export_attendance_filtered':
        exportAttendanceFiltered();
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

// ========== HELPER FUNCTIONS ==========

function logAuditAction($action_by, $action_type, $target_user_id = null, $details = '') {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO audit_log (action_by, action_type, target_user_id, details) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $action_by, $action_type, $target_user_id, $details);
    $stmt->execute();
    $stmt->close();
}

// ========== AUTHENTICATION ==========

function login() {
    global $conn;
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    if(empty($email) || empty($password) || empty($role)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT id, name, role, password, designation_id FROM users WHERE email = ? AND role = ?");
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or role']);
        return;
    }
    
    $user = $result->fetch_assoc();
    
    if($password === $user['password']) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['designation_id'] = $user['designation_id'];
        
        echo json_encode([
            'success' => true, 
            'message' => 'Login successful',
            'role' => $user['role'],
            'name' => $user['name']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
    }
    
    $stmt->close();
}

function logout() {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
}

// ========== ATTENDANCE MANAGEMENT ==========

function markAttendance() {
    global $conn;
    
    if(!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    $status = $_POST['status'] ?? '';
    $comment = trim($_POST['comment'] ?? '');
    $date = date('Y-m-d');
    $time = date('H:i:s');
    
    if(empty($status) || !in_array($status, ['present', 'absent'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT id, in_time FROM attendance WHERE user_id = ? AND date = ?");
    $stmt->bind_param("is", $user_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if($row['in_time'] !== null && $status === 'present') {
            $stmt2 = $conn->prepare("UPDATE attendance SET out_time = ?, comment = ? WHERE id = ?");
            $stmt2->bind_param("ssi", $time, $comment, $row['id']);
            $stmt2->execute();
            echo json_encode(['success' => true, 'message' => 'Out time marked successfully']);
            $stmt2->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Attendance already marked for today']);
        }
    } else {
        $in_time = $status === 'present' ? $time : null;
        $stmt2 = $conn->prepare("INSERT INTO attendance (user_id, date, status, in_time, comment) VALUES (?, ?, ?, ?, ?)");
        $stmt2->bind_param("issss", $user_id, $date, $status, $in_time, $comment);
        
        if($stmt2->execute()) {
            echo json_encode(['success' => true, 'message' => 'Attendance marked as ' . $status]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to mark attendance']);
        }
        $stmt2->close();
    }
    
    $stmt->close();
}

function getAttendance() {
    global $conn;
    
    if(!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['user_role'];
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $status = $_POST['status'] ?? null;
    
    if($role === 'employee') {
        $query = "SELECT date, status, in_time, out_time, comment FROM attendance WHERE user_id = ?";
        
        if($start_date) {
            $query .= " AND date >= '" . $conn->real_escape_string($start_date) . "'";
        }
        if($end_date) {
            $query .= " AND date <= '" . $conn->real_escape_string($end_date) . "'";
        }
        if($status) {
            $query .= " AND status = '" . $conn->real_escape_string($status) . "'";
        }
        
        $query .= " ORDER BY date DESC LIMIT 30";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
    } else {
        $query = "SELECT a.date, a.status, a.in_time, a.out_time, a.comment, u.name, u.email FROM attendance a JOIN users u ON a.user_id = u.id WHERE 1=1";
        
        if($start_date) {
            $query .= " AND a.date >= '" . $conn->real_escape_string($start_date) . "'";
        }
        if($end_date) {
            $query .= " AND a.date <= '" . $conn->real_escape_string($end_date) . "'";
        }
        if($status) {
            $query .= " AND a.status = '" . $conn->real_escape_string($status) . "'";
        }
        
        $query .= " ORDER BY a.date DESC LIMIT 100";
        $stmt = $conn->prepare($query);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
    $stmt->close();
}

function addManualAttendance() {
    global $conn;
    
    if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['hr', 'superadmin'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $employee_id = $_POST['employee_id'] ?? 0;
    $date = $_POST['date'] ?? '';
    $in_time = $_POST['in_time'] ?? null;
    $out_time = $_POST['out_time'] ?? null;
    $status = $_POST['status'] ?? 'present';
    $comment = trim($_POST['comment'] ?? '');
    $entered_by = $_SESSION['user_id'];
    $modified_at = date('Y-m-d H:i:s');
    
    if(empty($employee_id) || empty($date)) {
        echo json_encode(['success' => false, 'message' => 'Employee and Date are required']);
        return;
    }
    
    if(!in_array($status, ['present', 'absent'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT id FROM attendance WHERE user_id = ? AND date = ?");
    $stmt->bind_param("is", $employee_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stmt2 = $conn->prepare("UPDATE attendance SET status = ?, in_time = ?, out_time = ?, comment = ?, entered_by = ?, modified_at = ?, is_manual = 1 WHERE id = ?");
        $stmt2->bind_param("ssssssi", $status, $in_time, $out_time, $comment, $entered_by, $modified_at, $row['id']);
        
        if($stmt2->execute()) {
            logAuditAction($entered_by, 'ATTENDANCE_UPDATED', $employee_id, "Manual attendance updated for $date");
            echo json_encode(['success' => true, 'message' => 'Attendance updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update attendance']);
        }
        $stmt2->close();
    } else {
        $stmt2 = $conn->prepare("INSERT INTO attendance (user_id, date, status, in_time, out_time, comment, entered_by, modified_at, is_manual) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt2->bind_param("isssssss", $employee_id, $date, $status, $in_time, $out_time, $comment, $entered_by, $modified_at);
        
        if($stmt2->execute()) {
            logAuditAction($entered_by, 'ATTENDANCE_CREATED', $employee_id, "Manual attendance entry for $date");
            echo json_encode(['success' => true, 'message' => 'Attendance added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add attendance']);
        }
        $stmt2->close();
    }
    
    $stmt->close();
}

function getAllAttendance() {
    global $conn;
    
    if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['hr', 'superadmin'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $employee_id = $_POST['employee_id'] ?? null;
    $date = $_POST['date'] ?? null;
    $month = $_POST['month'] ?? null;
    $year = $_POST['year'] ?? null;
    
    $query = "SELECT 
                a.id,
                a.user_id,
                u.name,
                u.email,
                d.title as designation,
                a.date,
                a.status,
                a.in_time,
                a.out_time,
                a.comment,
                a.created_at,
                a.is_manual,
                a.modified_at,
                a.entered_by,
                entered_user.name as entered_by_name,
                entered_user.role as entered_by_role
              FROM attendance a
              JOIN users u ON a.user_id = u.id
              LEFT JOIN designations d ON u.designation_id = d.id
              LEFT JOIN users entered_user ON a.entered_by = entered_user.id
              WHERE u.role = 'employee'";
    
    $params = "";
    $paramValues = [];
    
    if($employee_id) {
        $query .= " AND a.user_id = ?";
        $params .= "i";
        $paramValues[] = $employee_id;
    }
    
    if($date) {
        $query .= " AND a.date = ?";
        $params .= "s";
        $paramValues[] = $date;
    }
    
    if($month && $year) {
        $query .= " AND MONTH(a.date) = ? AND YEAR(a.date) = ?";
        $params .= "ii";
        $paramValues[] = $month;
        $paramValues[] = $year;
    }
    
    $query .= " ORDER BY a.date DESC, u.name ASC";
    
    if(!empty($paramValues)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($params, ...$paramValues);
    } else {
        $stmt = $conn->prepare($query);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
    $stmt->close();
}

function updateManualAttendance() {
    global $conn;
    
    if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['hr', 'superadmin'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $attendance_id = $_POST['attendance_id'] ?? 0;
    $in_time = $_POST['in_time'] ?? null;
    $out_time = $_POST['out_time'] ?? null;
    $status = $_POST['status'] ?? null;
    $comment = trim($_POST['comment'] ?? '');
    $entered_by = $_SESSION['user_id'];
    $modified_at = date('Y-m-d H:i:s');
    
    if(!$attendance_id) {
        echo json_encode(['success' => false, 'message' => 'Attendance ID required']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT user_id FROM attendance WHERE id = ?");
    $stmt->bind_param("i", $attendance_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $employee_id = $row['user_id'] ?? 0;
    
    $stmt2 = $conn->prepare("UPDATE attendance SET in_time = ?, out_time = ?, status = ?, comment = ?, entered_by = ?, modified_at = ?, is_manual = 1 WHERE id = ?");
    $stmt2->bind_param("ssssssi", $in_time, $out_time, $status, $comment, $entered_by, $modified_at, $attendance_id);
    
    if($stmt2->execute()) {
        logAuditAction($entered_by, 'ATTENDANCE_MODIFIED', $employee_id, "Manual attendance modified");
        echo json_encode(['success' => true, 'message' => 'Attendance updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update attendance']);
    }
    
    $stmt2->close();
}

// ========== LEAVE MANAGEMENT ==========

function applyLeave() {
    global $conn;
    
    if(!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    $leave_type = $_POST['leave_type'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $comment = trim($_POST['comment'] ?? '');
    
    if(empty($leave_type) || empty($start_date) || empty($end_date)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    if(!in_array($leave_type, ['cl', 'sl', 'weekoff', 'holiday'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid leave type']);
        return;
    }
    
    $stmt = $conn->prepare("INSERT INTO leaves (user_id, leave_type, start_date, end_date, comment) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $leave_type, $start_date, $end_date, $comment);
    
    if($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Leave application submitted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit leave application']);
    }
    
    $stmt->close();
}

function getLeaves() {
    global $conn;
    
    if(!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['user_role'];
    
    if($role === 'employee') {
        $stmt = $conn->prepare("SELECT id, leave_type, start_date, end_date, status, comment, hr_comment, created_at FROM leaves WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $user_id);
    } else {
        $stmt = $conn->prepare("SELECT l.id, l.leave_type, l.start_date, l.end_date, l.status, l.comment, l.hr_comment, l.created_at, u.name, u.email FROM leaves l JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC");
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
    $stmt->close();
}

function manageLeave() {
    global $conn;
    
    if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] === 'employee') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $leave_id = $_POST['leave_id'] ?? 0;
    $status = $_POST['status'] ?? '';
    $hr_comment = trim($_POST['hr_comment'] ?? '');
    
    if(empty($leave_id) || !in_array($status, ['approved', 'rejected'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        return;
    }
    
    $stmt = $conn->prepare("UPDATE leaves SET status = ?, hr_comment = ? WHERE id = ?");
    $stmt->bind_param("ssi", $status, $hr_comment, $leave_id);
    
    if($stmt->execute()) {
        logAuditAction($_SESSION['user_id'], 'LEAVE_' . strtoupper($status), $leave_id, "Leave " . $status);
        echo json_encode(['success' => true, 'message' => 'Leave ' . $status . ' successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update leave status']);
    }
    
    $stmt->close();
}

// ========== EMPLOYEE MANAGEMENT ==========

function addEmployee() {
    global $conn;
    
    if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['hr', 'superadmin'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $designation_id = $_POST['designation_id'] ?? null;
    $created_by = $_SESSION['user_id'];
    $created_by_role = $_SESSION['user_role'];
    
    if(empty($name) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        return;
    }
    
    $stmt = $conn->prepare("INSERT INTO users (role, name, email, password, designation_id, created_by, status) VALUES ('employee', ?, ?, ?, ?, ?, 'active')");
    $stmt->bind_param("sssii", $name, $email, $password, $designation_id, $created_by);
    
    if($stmt->execute()) {
        $employee_id = $conn->insert_id;
        logAuditAction($created_by, 'EMPLOYEE_CREATED', $employee_id, "Employee '$name' created by " . $_SESSION['user_role']);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Employee added successfully',
            'employee_id' => $employee_id,
            'created_by' => $_SESSION['user_name'],
            'created_by_role' => $created_by_role
        ]);
    } else {
        if($conn->errno === 1062) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add employee']);
        }
    }
    
    $stmt->close();
}

function getEmployees() {
    global $conn;
    
    if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] === 'employee') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT u.id, u.name, u.email, u.created_at, u.status, 
                            d.title as designation, 
                            creator.name as created_by_name, 
                            creator.role as created_by_role
                            FROM users u 
                            LEFT JOIN designations d ON u.designation_id = d.id 
                            LEFT JOIN users creator ON u.created_by = creator.id
                            WHERE u.role = 'employee' 
                            ORDER BY u.created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
    $stmt->close();
}

function getEmployeesCreatedBy() {
    global $conn;
    
    if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['hr', 'superadmin'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $created_by_id = $_POST['created_by_id'] ?? null;
    
    if(!$created_by_id) {
        echo json_encode(['success' => false, 'message' => 'Created by ID required']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT u.id, u.name, u.email, u.created_at, u.status,
                            d.title as designation,
                            creator.name as created_by_name,
                            creator.role as created_by_role
                            FROM users u
                            LEFT JOIN designations d ON u.designation_id = d.id
                            LEFT JOIN users creator ON u.created_by = creator.id
                            WHERE u.role = 'employee' AND u.created_by = ?
                            ORDER BY u.created_at DESC");
    $stmt->bind_param("i", $created_by_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
    $stmt->close();
}

// ========== HR MANAGEMENT ==========

function addHR() {
    global $conn;
    
    if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'superadmin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $created_by = $_SESSION['user_id'];
    
    if(empty($name) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        return;
    }
    
    $stmt = $conn->prepare("INSERT INTO users (role, name, email, password, created_by, status) VALUES ('hr', ?, ?, ?, ?, 'active')");
    $stmt->bind_param("sssi", $name, $email, $password, $created_by);
    
    if($stmt->execute()) {
        $hr_id = $conn->insert_id;
        logAuditAction($created_by, 'HR_CREATED', $hr_id, "HR Profile '$name' created by Superadmin");
        
        echo json_encode([
            'success' => true, 
            'message' => 'HR added successfully',
            'hr_id' => $hr_id,
            'created_by' => $_SESSION['user_name']
        ]);
    } else {
        if($conn->errno === 1062) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add HR']);
        }
    }
    
    $stmt->close();
}

function getHRList() {
    global $conn;
    
    if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'superadmin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT u.id, u.name, u.email, u.created_at, u.status,
                            creator.name as created_by_name,
                            COUNT(emp.id) as employees_managed
                            FROM users u
                            LEFT JOIN users creator ON u.created_by = creator.id
                            LEFT JOIN users emp ON emp.created_by = u.id AND emp.role = 'employee'
                            WHERE u.role = 'hr'
                            GROUP BY u.id
                            ORDER BY u.created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
    $stmt->close();
}

// ========== DESIGNATION MANAGEMENT ==========

function getDesignations() {
    global $conn;
    
    if(!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT id, title, description, created_at FROM designations ORDER BY title ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
    $stmt->close();
}

function addDesignation() {
    global $conn;
    
    if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['hr', 'superadmin'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if(empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Title is required']);
        return;
    }
    
    $stmt = $conn->prepare("INSERT INTO designations (title, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $title, $description);
    
    if($stmt->execute()) {
        logAuditAction($_SESSION['user_id'], 'DESIGNATION_CREATED', null, "Designation '$title' created");
        echo json_encode(['success' => true, 'message' => 'Designation added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add designation']);
    }
    
    $stmt->close();
}

function updateDesignation() {
    global $conn;
    
    if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['hr', 'superadmin'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $user_id = $_POST['user_id'] ?? 0;
    $designation_id = $_POST['designation_id'] ?? null;
    
    if(empty($user_id)) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT title FROM designations WHERE id = ?");
    $stmt->bind_param("i", $designation_id);
    $stmt->execute();
    $newDesig = $stmt->get_result()->fetch_assoc();
    
    $stmt2 = $conn->prepare("UPDATE users SET designation_id = ? WHERE id = ? AND role = 'employee'");
    $stmt2->bind_param("ii", $designation_id, $user_id);
    
    if($stmt2->execute()) {
        logAuditAction($_SESSION['user_id'], 'EMPLOYEE_PROMOTED', $user_id, "Employee promoted to " . ($newDesig['title'] ?? 'No Designation'));
        echo json_encode(['success' => true, 'message' => 'Designation updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update designation']);
    }
    
    $stmt2->close();
}

// ========== PERFORMANCE MANAGEMENT ==========

function addPerformance() {
    global $conn;
    
    if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] === 'employee') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $user_id = $_POST['user_id'] ?? 0;
    $review_date = $_POST['review_date'] ?? date('Y-m-d');
    $comments = trim($_POST['comments'] ?? '');
    $rating = $_POST['rating'] ?? 0;
    $reviewed_by = $_SESSION['user_id'];
    
    if(empty($user_id) || empty($comments) || $rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'All fields are required and rating must be 1-5']);
        return;
    }
    
    $stmt = $conn->prepare("INSERT INTO performance (user_id, review_date, comments, rating, reviewed_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issii", $user_id, $review_date, $comments, $rating, $reviewed_by);
    
    if($stmt->execute()) {
        logAuditAction($reviewed_by, 'PERFORMANCE_REVIEW', $user_id, "Performance review added with rating $rating/5");
        echo json_encode(['success' => true, 'message' => 'Performance review added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add performance review']);
    }
    
    $stmt->close();
}

function getPerformance() {
    global $conn;
    
    if(!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['user_role'];
    
    if($role === 'employee') {
        $stmt = $conn->prepare("SELECT p.review_date, p.comments, p.rating, u.name as reviewer FROM performance p JOIN users u ON p.reviewed_by = u.id WHERE p.user_id = ? ORDER BY p.review_date DESC");
        $stmt->bind_param("i", $user_id);
    } else {
        $stmt = $conn->prepare("SELECT p.review_date, p.comments, p.rating, u1.name as employee_name, u2.name as reviewer FROM performance p JOIN users u1 ON p.user_id = u1.id JOIN users u2 ON p.reviewed_by = u2.id ORDER BY p.review_date DESC LIMIT 50");
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
    $stmt->close();
}

// ========== HOLIDAY MANAGEMENT ==========

function addHoliday() {
    global $conn;
    
    if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['hr', 'superadmin'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $date = $_POST['date'] ?? '';
    $description = trim($_POST['description'] ?? '');
    
    if(empty($date) || empty($description)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    $stmt = $conn->prepare("INSERT INTO holidays (date, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $date, $description);
    
    if($stmt->execute()) {
        logAuditAction($_SESSION['user_id'], 'HOLIDAY_ADDED', null, "Holiday added on $date: $description");
        echo json_encode(['success' => true, 'message' => 'Holiday added successfully']);
    } else {
        if($conn->errno === 1062) {
            echo json_encode(['success' => false, 'message' => 'Holiday already exists for this date']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add holiday']);
        }
    }
    
    $stmt->close();
}

function getHolidays() {
    global $conn;
    
    if(!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT date, description FROM holidays ORDER BY date ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
    $stmt->close();
}

// ========== DASHBOARD STATISTICS ==========

function getDashboardStats() {
    global $conn;
    
    if(!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['user_role'];
    $stats = [];
    
    if($role === 'employee') {
        $stmt = $conn->prepare("SELECT COUNT(*) as total_present FROM attendance WHERE user_id = ? AND status = 'present' AND MONTH(date) = MONTH(CURRENT_DATE())");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stats['present_days'] = $stmt->get_result()->fetch_assoc()['total_present'];
        
        $stmt = $conn->prepare("SELECT COUNT(*) as total_absent FROM attendance WHERE user_id = ? AND status = 'absent' AND MONTH(date) = MONTH(CURRENT_DATE())");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stats['absent_days'] = $stmt->get_result()->fetch_assoc()['total_absent'];
        
        $stmt = $conn->prepare("SELECT COUNT(*) as pending_leaves FROM leaves WHERE user_id = ? AND status = 'pending'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stats['pending_leaves'] = $stmt->get_result()->fetch_assoc()['pending_leaves'];
        
        $stmt = $conn->prepare("SELECT AVG(rating) as avg_rating FROM performance WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stats['avg_rating'] = round($stmt->get_result()->fetch_assoc()['avg_rating'] ?? 0, 1);
        
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) as total_employees FROM users WHERE role = 'employee'");
        $stmt->execute();
        $stats['total_employees'] = $stmt->get_result()->fetch_assoc()['total_employees'];
        
        $stmt = $conn->prepare("SELECT COUNT(*) as present_today FROM attendance WHERE date = CURDATE() AND status = 'present'");
        $stmt->execute();
        $stats['present_today'] = $stmt->get_result()->fetch_assoc()['present_today'];
        
        $stmt = $conn->prepare("SELECT COUNT(*) as pending_leaves FROM leaves WHERE status = 'pending'");
        $stmt->execute();
        $stats['pending_leaves'] = $stmt->get_result()->fetch_assoc()['pending_leaves'];
        
        $stmt = $conn->prepare("SELECT COUNT(*) as total_holidays FROM holidays WHERE date >= CURDATE()");
        $stmt->execute();
        $stats['upcoming_holidays'] = $stmt->get_result()->fetch_assoc()['total_holidays'];
    }
    
    echo json_encode(['success' => true, 'data' => $stats]);
    $stmt->close();
}

// ========== AUDIT LOG ==========

function getAuditLog() {
    global $conn;
    
    if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['hr', 'superadmin'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $limit = $_POST['limit'] ?? 50;
    $action_type = $_POST['action_type'] ?? null;
    
    $query = "SELECT al.id, al.action_type, al.details, al.created_at, 
                     actor.name as performed_by, actor.role as performer_role,
                     target.name as target_name, target.role as target_role
              FROM audit_log al
              LEFT JOIN users actor ON al.action_by = actor.id
              LEFT JOIN users target ON al.target_user_id = target.id
              WHERE 1=1";
    
    if($action_type) {
        $query .= " AND al.action_type = '" . $conn->real_escape_string($action_type) . "'";
    }
    
    $query .= " ORDER BY al.created_at DESC LIMIT $limit";
    
    $result = $conn->query($query);
    $data = [];
    
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
}

// ========== EXPORT FUNCTIONS ==========

function exportAttendance() {
    global $conn;
    
    if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] === 'employee') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $format = $_POST['format'] ?? 'csv';
    
    $stmt = $conn->prepare("SELECT u.name, u.email, a.date, a.status, a.in_time, a.out_time, a.comment, d.title as designation FROM attendance a JOIN users u ON a.user_id = u.id LEFT JOIN designations d ON u.designation_id = d.id ORDER BY a.date DESC, u.name ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $records = [];
    while($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    $stmt->close();
    
    if(count($records) === 0) {
        echo json_encode(['success' => false, 'message' => 'No attendance records found']);
        return;
    }
    
    if($format === 'csv') {
        exportToCSV($records);
    } else if($format === 'excel') {
        exportToExcel($records);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid format']);
    }
}

function exportToCSV($records) {
    $csv = "Employee Name,Email,Designation,Date,Status,In Time,Out Time,Comment\n";
    
    foreach($records as $record) {
        $csv .= '"' . $record['name'] . '",';
        $csv .= '"' . $record['email'] . '",';
        $csv .= '"' . ($record['designation'] ?? '-') . '",';
        $csv .= '"' . $record['date'] . '",';
        $csv .= '"' . $record['status'] . '",';
        $csv .= '"' . ($record['in_time'] ?? '-') . '",';
        $csv .= '"' . ($record['out_time'] ?? '-') . '",';
        $csv .= '"' . str_replace('"', '""', $record['comment'] ?? '-') . '"' . "\n";
    }
    
    echo json_encode(['success' => true, 'data' => $csv]);
}

function exportToExcel($records) {
    $excelData = array(
        array('Employee Name', 'Email', 'Designation', 'Date', 'Status', 'In Time', 'Out Time', 'Comment')
    );
    
    foreach($records as $record) {
        $excelData[] = array(
            $record['name'],
            $record['email'],
            $record['designation'] ?? '-',
            $record['date'],
            $record['status'],
            $record['in_time'] ?? '-',
            $record['out_time'] ?? '-',
            $record['comment'] ?? '-'
        );
    }
    
    $csv = '';
    foreach($excelData as $row) {
        $csv .= implode(',', array_map(function($cell) {
            return '"' . str_replace('"', '""', $cell) . '"';
        }, $row)) . "\n";
    }
    
    echo json_encode(['success' => true, 'data' => $csv]);
}

function exportAttendanceFiltered() {
    global $conn;
    
    if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] === 'employee') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $format = $_POST['format'] ?? 'csv';
    $startDate = $_POST['start_date'] ?? null;
    $endDate = $_POST['end_date'] ?? null;
    $status = $_POST['status'] ?? null;
    $employeeId = $_POST['employee_id'] ?? null;
    
    $query = "SELECT u.name, u.email, a.date, a.status, a.in_time, a.out_time, a.comment, d.title as designation FROM attendance a JOIN users u ON a.user_id = u.id LEFT JOIN designations d ON u.designation_id = d.id WHERE 1=1";
    
    $params = "";
    $paramValues = [];
    
    if($startDate) {
        $query .= " AND a.date >= ?";
        $params .= "s";
        $paramValues[] = $startDate;
    }
    if($endDate) {
        $query .= " AND a.date <= ?";
        $params .= "s";
        $paramValues[] = $endDate;
    }
    if($status && in_array($status, ['present', 'absent'])) {
        $query .= " AND a.status = ?";
        $params .= "s";
        $paramValues[] = $status;
    }
    if($employeeId) {
        $query .= " AND u.id = ?";
        $params .= "i";
        $paramValues[] = $employeeId;
    }
    
    $query .= " ORDER BY a.date DESC, u.name ASC";
    
    if(!empty($paramValues)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($params, ...$paramValues);
    } else {
        $stmt = $conn->prepare($query);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $records = [];
    
    while($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    $stmt->close();
    
    if(count($records) === 0) {
        echo json_encode(['success' => false, 'message' => 'No records found for selected filters']);
        return;
    }
    
    if($format === 'csv') {
        exportToCSV($records);
    } else if($format === 'excel') {
        exportToExcel($records);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid format']);
    }
}

$conn->close();
?>
