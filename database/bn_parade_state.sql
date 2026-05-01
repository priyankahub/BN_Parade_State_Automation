CREATE DATABASE IF NOT EXISTS bn_parade_state_db;
USE bn_parade_state_db;

CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(100) NOT NULL,
    short_name VARCHAR(20) NOT NULL,
    is_active TINYINT DEFAULT 1
);

CREATE TABLE platoons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    platoon_name VARCHAR(100) NOT NULL,
    section_name VARCHAR(100) DEFAULT NULL,
    is_active TINYINT DEFAULT 1,
    FOREIGN KEY (company_id) REFERENCES companies(id)
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(100) NOT NULL,
    security_question VARCHAR(255) DEFAULT NULL,
    security_answer VARCHAR(255) DEFAULT NULL,
    role ENUM('ADMIN','ADJT_SA','CHM_CLERK','USER') NOT NULL,
    army_no VARCHAR(50) DEFAULT NULL,
    company_id INT DEFAULT NULL,
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id)
);

CREATE TABLE personnel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    army_no VARCHAR(50) NOT NULL UNIQUE,
    rank_name VARCHAR(50) NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    company_id INT NOT NULL,
    platoon_id INT DEFAULT NULL,
    trade VARCHAR(80) DEFAULT NULL,
    service_status ENUM('Serving','Attached Out','Posted Out','Retired') DEFAULT 'Serving',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (platoon_id) REFERENCES platoons(id)
);

CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('Present','Absent','Leave','Course','Sick Report','MH','TD','Duty','Attached Out','Other') NOT NULL,
    remarks TEXT DEFAULT NULL,
    entered_by INT DEFAULT NULL,
    approval_status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    approved_by INT DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_daily_attendance (personnel_id, attendance_date),
    FOREIGN KEY (personnel_id) REFERENCES personnel(id),
    FOREIGN KEY (entered_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

CREATE TABLE leave_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    leave_type VARCHAR(80) NOT NULL,
    from_date DATE NOT NULL,
    to_date DATE NOT NULL,
    approval_status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    return_status ENUM('Not Returned','Returned') DEFAULT 'Not Returned',
    remarks TEXT DEFAULT NULL,
    entered_by INT DEFAULT NULL,
    FOREIGN KEY (personnel_id) REFERENCES personnel(id),
    FOREIGN KEY (entered_by) REFERENCES users(id)
);

CREATE TABLE duty_roster (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    duty_type ENUM('Guard','Sentry','QRT','Office Duty','Special Task','Other') NOT NULL,
    duty_date DATE NOT NULL,
    duty_time VARCHAR(50) DEFAULT NULL,
    location VARCHAR(120) DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    entered_by INT DEFAULT NULL,
    FOREIGN KEY (personnel_id) REFERENCES personnel(id),
    FOREIGN KEY (entered_by) REFERENCES users(id)
);

CREATE TABLE course_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    course_name VARCHAR(150) NOT NULL,
    location VARCHAR(150) DEFAULT NULL,
    from_date DATE NOT NULL,
    to_date DATE NOT NULL,
    remarks TEXT DEFAULT NULL,
    entered_by INT DEFAULT NULL,
    FOREIGN KEY (personnel_id) REFERENCES personnel(id),
    FOREIGN KEY (entered_by) REFERENCES users(id)
);

CREATE TABLE sick_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    report_date DATE NOT NULL,
    category ENUM('Sick Report','MH','OPD','Rest Advised') NOT NULL,
    expected_return DATE DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    entered_by INT DEFAULT NULL,
    FOREIGN KEY (personnel_id) REFERENCES personnel(id),
    FOREIGN KEY (entered_by) REFERENCES users(id)
);

CREATE TABLE manpower_shortages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    shortage_date DATE NOT NULL,
    required_strength INT NOT NULL,
    available_strength INT NOT NULL,
    priority ENUM('Normal','Urgent','Critical') DEFAULT 'Normal',
    remarks TEXT DEFAULT NULL,
    marked_by INT DEFAULT NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (marked_by) REFERENCES users(id)
);

CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action_tag VARCHAR(100) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    action_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

INSERT INTO companies (company_name, short_name) VALUES
('Headquarters Company', 'HQ'),
('A Company', 'A Coy'),
('B Company', 'B Coy'),
('C Company', 'C Coy'),
('D Company', 'D Coy'),
('Support Company', 'Sp Coy');

INSERT INTO users (name, username, password, role, army_no, company_id, security_question, security_answer) VALUES
('System Administrator', 'admin', 'Admin@123', 'ADMIN', 'IC-0001', NULL, NULL, NULL),
('Adjutant / SA', 'adjt', 'Adjt@123', 'ADJT_SA', 'IC-0002', NULL, NULL, NULL),
('A Coy Clerk', 'acoy.clerk', 'Clerk@123', 'CHM_CLERK', 'CL-0001', 2, NULL, NULL),
('Authorized User', 'user', 'User@123', 'USER', 'OR-0001', NULL, NULL, NULL);
