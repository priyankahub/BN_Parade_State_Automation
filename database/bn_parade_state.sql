CREATE DATABASE IF NOT EXISTS bn_parade_state_db;
USE bn_parade_state_db;

-- Run this once on an existing database to widen the password column
-- ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL;

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
    password VARCHAR(255) NOT NULL,
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
('HQ Company', 'HQ'),
('A Company', 'A Coy'),
('B Company', 'B Coy'),
('C Company', 'C Coy'),
('D Company', 'D Coy'),
('SP Company', 'SP');

INSERT INTO personnel (army_no, rank_name, full_name, company_id) VALUES
('17027948A', 'Nk', 'Krishan Badav', 2),
('2793160H', 'Lnk', 'Agre SanjaB', 2),
('2793291N', 'Sep', 'Wabale Santosh', 2),
('2793976M', 'Hav', 'Patil Bharat', 2),
('2794183W', 'Nk', 'Shinde Sharad', 2),
('2795176M', 'Lnk', 'Salam Vaiju', 2),
('2802654W', 'Hav', 'More Sahebrao', 2),
('2804124A', 'Lnk', 'Mohite Kailash', 2),
('2804229K', 'Sep', 'Karade Dhula', 2),
('2804236A', 'Hav', 'Nitin Pisal', 2),
('2810379W', 'Nk', 'Mule Sarjerao', 2),
('2810670H', 'Sep', 'Vetal Somnath', 2),
('2810739F', 'Hav', 'Tagad Kailash', 2),
('2811470B', 'Nk', 'Maind Hanumant', 2),
('2816665N', 'Nk', 'Powar DhananjaB', 2),
('2817465K', 'Nk', 'Ghadge Amish', 2),
('2817543W', 'Lnk', 'Patil Nikhil', 2),
('2817667L', 'Nk', 'Badav Sunil', 2),
('2817670L', 'Lnk', 'Shinde Amol', 2),
('2818032B', 'Lnk', 'Nagare Pravin', 2),
('2795922K', 'Sep', 'Gurulingappa Badachi', 3),
('2799411X', 'Lnk', 'Kapadi Chandrabhan', 3),
('2800191N', 'Sep', 'Sawant Milind', 3),
('2802262H', 'Lnk', 'Kolekar VijaB', 3),
('2802334F', 'Sep', 'Jamkar Rakesh', 3),
('2803159K', 'Nk', 'Vinod Shere', 3),
('2804378X', 'Nk', 'Sutar Shankar', 3),
('2810414B', 'Hav', 'Pawar Atul', 3),
('2811140A', 'Lnk', 'Suprith Rao', 3),
('2811542X', 'Sep', 'Akhare Atul', 3),
('2813133H', 'Hav', 'Patil DattatraBa', 3),
('2813204B', 'Nk', 'Paste Sitaram', 3),
('2813234W', 'Lnk', 'Singanjude Vivek', 3),
('2813284F', 'Sep', 'Hemane Vinod', 3),
('2813386X', 'Hav', 'More Umesh', 3),
('2813530M', 'Nk', 'Todmal Vaibhav', 3),
('2813533P', 'Lnk', 'Lature Ram', 3),
('2813551X', 'Sep', 'Bhushan Ahire', 3),
('2813569N', 'Hav', 'Nikam Sagar', 3),
('2814497F', 'Nk', 'Ranjan Sinha', 3),
('2804434L', 'Lnk', 'Virkar Satish', 4),
('2805464P', 'Sep', 'Hukke Santosh', 4),
('2805878H', 'Hav', 'Chavan Gajanan', 4),
('2806219B', 'Nk', 'Patil Sharad', 4),
('2810153L', 'Sep', 'Javanjal Vishal', 4),
('2811251X', 'Sep', 'Dhane Rahul', 4),
('2811337W', 'Hav', 'Gurunale Vaibhav', 4),
('2811539X', 'Lnk', 'Shailesh Gahane', 4),
('2815517W', 'Hav', 'Gaike Rahul', 4),
('2815588X', 'Sep', 'Buvaraj Majukar', 4),
('2815798X', 'Hav', 'Lavate Santosh', 4),
('2815901X', 'Sep', 'Shenavi Atul', 4),
('2816144A', 'Lnk', 'Bhosale Sangam', 4),
('2816338H', 'Sep', 'Patil Santosh', 4),
('2816883M', 'Sep', 'Agashe Akhil', 4),
('2817949K', 'Nk', 'Gite Gangaram', 4),
('2818679M', 'Hav', 'Ghadage AjinkBa', 4),
('2819023K', 'Lnk', 'Jadhav Pratik', 4),
('2820542B', 'Nk', 'Raskar Vikas', 4),
('2806855M', 'Lnk', 'Bewale Popat', 4),
('2804527A', 'Hav', 'Rane Santosh', 5),
('2804920H', 'Lnk', 'Mundhe Ganesh', 5),
('2805360N', 'Nk', 'Shipekar Santosh', 5),
('2810386M', 'Lnk', 'Phad Mahadev', 5),
('2815569M', 'Nk', 'Patil Suraj', 5),
('2815820X', 'Lnk', 'Darunte Niraj', 5),
('2815975M', 'Hav', 'Patil Rohan', 5),
('2816074M', 'Nk', 'Dhage Sagar', 5),
('2817676M', 'Sep', 'Patil Pravin Pandurang', 5),
('2818072A', 'Sep', 'Kodape Ashish', 5),
('2820605W', 'Lnk', 'Munde AkshaB', 5),
('7431871W', 'Hav', 'Ashoka V', 5),
('IC-79157B', 'Lnk', 'Sarvesh Singh Badav', 5),
('JC-460720B', 'Hav', 'VijaB Mohite', 5),
('JC-461910X', 'Lnk', 'Bhosale Krishnadeo', 5),
('2807817H', 'Nk', 'Patil Bogesh', 5),
('2808917A', 'Sep', 'Sachin Batlandhe', 5),
('2811451N', 'Hav', 'Dhaitidak Ganesh', 5),
('2792603M', 'Nk', 'Umesh Patil', 5),
('2792806W', 'Nk', 'Talwar Prabhakar', 5),
('2796133K', 'Hav', 'Thange Babasaheb', 6),
('2797397X', 'Nk', 'Sopan Sargar', 6),
('2797852P', 'Lnk', 'Mane Tanaji', 6),
('2798046P', 'Sep', 'Sabale Tejpal', 6),
('2798724K', 'Hav', 'Sadashiv Shingadi', 6),
('2798991L', 'Nk', 'Nidheesh K', 6),
('2801516H', 'Hav', 'Patil Parasharam', 6),
('2804846M', 'Nk', 'Sutar Dhaklesh', 6),
('2805214N', 'Sep', 'Haral Nilesh', 6),
('2806282N', 'Lnk', 'Kare Aba ShaNkar', 6),
('2806435A', 'Sep', 'Nanaji Munagapaka', 6),
('2806635H', 'Hav', 'Walung Sandeep', 6),
('2807509H', 'Nk', 'Lokhande Sachin', 6),
('2808457K', 'Lnk', 'Feran Nilesh', 6),
('2808541K', 'Sep', 'Molke Sandeep', 6),
('2808625X', 'Hav', 'Diwate Dipak', 6),
('2808643A', 'Nk', 'Gawade DnBaneshwar', 6),
('2808732B', 'Lnk', 'Gaikwad Satish', 6),
('2808926F', 'Sep', 'Kshirsagar Subhash', 6),
('2809271M', 'Hav', 'Sonawane Ganesh', 6),
('2802117L', 'Nk', 'Kendre Digambar', 1),
('2804503W', 'Sep', 'Biswajit Singha', 1),
('2805215W', 'Hav', 'Patil Satish', 1),
('2805411W', 'Lnk', 'Ravi Rawool', 1),
('2809736F', 'Capt', 'Kamarasu', 1),
('2809755M', 'Capt', 'Bisen DnBaneshwar', 1),
('2809860H', 'Lnk', 'Prabhu M', 1),
('2810364N', 'Hav', 'Suresh C', 1),
('2810392A', 'Sep', 'Gite Hanumant', 1),
('2810418K', 'Nk', 'JaBram Pawal', 1),
('2810455X', 'Lnk', 'Burse DBaneshwar', 1),
('2811030L', 'Nk', 'Abasaheb More', 1),
('2815608A', 'Hav', 'Patil Shushant', 1),
('2815665F', 'Lnk', 'Patil Sunil', 1),
('2815783P', 'Sep', 'Gharge Aappaso', 1),
('2816655K', 'Hav', 'Patil Krishnadeo', 1),
('2816736K', 'Lnk', 'Jadhav Kishor', 1),
('2818107L', 'Hav', 'Patil Vipul', 1),
('2819075A', 'Sep', 'Bhagat Atishkumar', 1),
('2819413M', 'Nk', 'JaBesh Patil', 1);

INSERT INTO users (name, username, password, role, army_no, company_id, security_question, security_answer) VALUES
('System Administrator', 'admin', 'Admin@123', 'ADMIN', 'IC-0001', NULL, NULL, NULL),
('Adjutant / SA', 'adjt', 'Adjt@123', 'ADJT_SA', 'IC-0002', NULL, NULL, NULL),
('A Coy Clerk', 'acoy.clerk', 'Clerk@123', 'CHM_CLERK', 'CL-0001', 2, NULL, NULL),
('Authorized User', 'user', 'User@123', 'USER', 'OR-0001', NULL, NULL, NULL);
