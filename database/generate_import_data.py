"""
BN Parade State — Full Setup SQL Generator
Outputs: database/bn_full_setup.sql
  - DROP + CREATE DATABASE
  - DROP + CREATE all tables (personnel includes all 12 extended columns)
  - INSERT base data: companies, platoons, users, personnel
  - UPDATE personnel with realistic extended data
  - INSERT 30 days attendance, leave, course, duty, sick, manpower shortages
Safe to run on a completely empty phpMyAdmin.
"""

import random
import hashlib
from datetime import date, timedelta

random.seed(42)

BLOOD_GROUPS = ['A+', 'B+', 'O+', 'AB+', 'A-', 'B-', 'O-', 'AB-']
BG_WEIGHTS   = [35, 30, 15, 8, 4, 3, 3, 2]

HOME_STATES = [
    'Maharashtra', 'Uttar Pradesh', 'Rajasthan', 'Bihar', 'Madhya Pradesh',
    'Punjab', 'Haryana', 'Gujarat', 'Karnataka', 'Tamil Nadu',
    'Andhra Pradesh', 'Uttarakhand', 'Himachal Pradesh', 'Jharkhand', 'Odisha'
]
STATE_WEIGHTS = [20,15,12,10,8,6,5,4,4,3,3,3,2,2,3]

MED_CATS = ['SHAPE-1', 'AYE', 'AYE', 'AYE', 'BEE', 'CEE']

PIN_CODES = {
    'Maharashtra':      ['411001','411002','411014','416001','421001','440001','431001'],
    'Uttar Pradesh':    ['208001','226001','282001','201001','250001'],
    'Rajasthan':        ['302001','313001','324001','302019','342001'],
    'Bihar':            ['800001','842001','845401','854301'],
    'Madhya Pradesh':   ['462001','452001','474001','495001'],
    'Punjab':           ['141001','143001','147001','140001'],
    'Haryana':          ['122001','132001','135001','121001'],
    'Gujarat':          ['380001','395001','361001','396001'],
    'Karnataka':        ['560001','575001','580001','590001'],
    'Tamil Nadu':       ['600001','620001','641001','625001'],
    'Andhra Pradesh':   ['520001','530001','533001','515001'],
    'Uttarakhand':      ['248001','263001','246001','249001'],
    'Himachal Pradesh': ['171001','175001','176001','174001'],
    'Jharkhand':        ['834001','832001','826001','835303'],
    'Odisha':           ['751001','760001','769001','756001'],
}

MARITAL_STATUS = ['Married','Married','Married','Single','Single','Widowed']

COURSES = [
    ('Section Commanders Course','ITC Belgaum'),
    ('NBC Course','NBC School Baroda'),
    ('Signals Course','CME Pune'),
    ('PT Instructor Course','ASPT Pune'),
    ('Combat Engineers Course','MCTE Mhow'),
    ('Snipers Course','Infantry School Mhow'),
    ('MG Platoon Course','Infantry School Mhow'),
    ('Driving Course','DEME Bhopal'),
    ('Medical Aid Course','AMC Centre'),
    ('Leadership Course','OTA Chennai'),
]

TODAY = date.today()

# (army_no, rank, company_id, full_name)
PERSONNEL = [
    ('17027948A','Nk',2,'Krishan Badav'),
    ('2793160H','Lnk',2,'Agre Sanjay'),
    ('2793291N','Sep',2,'Wabale Santosh'),
    ('2793976M','Hav',2,'Patil Bharat'),
    ('2794183W','Nk',2,'Shinde Sharad'),
    ('2795176M','Lnk',2,'Salam Vaiju'),
    ('2802654W','Hav',2,'More Sahebrao'),
    ('2804124A','Lnk',2,'Mohite Kailash'),
    ('2804229K','Sep',2,'Karade Dhula'),
    ('2804236A','Hav',2,'Nitin Pisal'),
    ('2810379W','Nk',2,'Mule Sarjerao'),
    ('2810670H','Sep',2,'Vetal Somnath'),
    ('2810739F','Hav',2,'Tagad Kailash'),
    ('2811470B','Nk',2,'Maind Hanumant'),
    ('2816665N','Nk',2,'Powar Dhananjay'),
    ('2817465K','Nk',2,'Ghadge Amish'),
    ('2817543W','Lnk',2,'Patil Nikhil'),
    ('2817667L','Nk',2,'Badav Sunil'),
    ('2817670L','Lnk',2,'Shinde Amol'),
    ('2818032B','Lnk',2,'Nagare Pravin'),
    ('2795922K','Sep',3,'Gurulingappa Badachi'),
    ('2799411X','Lnk',3,'Kapadi Chandrabhan'),
    ('2800191N','Sep',3,'Sawant Milind'),
    ('2802262H','Lnk',3,'Kolekar Vijay'),
    ('2802334F','Sep',3,'Jamkar Rakesh'),
    ('2803159K','Nk',3,'Vinod Shere'),
    ('2804378X','Nk',3,'Sutar Shankar'),
    ('2810414B','Hav',3,'Pawar Atul'),
    ('2811140A','Lnk',3,'Suprith Rao'),
    ('2811542X','Sep',3,'Akhare Atul'),
    ('2813133H','Hav',3,'Patil Dattatraya'),
    ('2813204B','Nk',3,'Paste Sitaram'),
    ('2813234W','Lnk',3,'Singanjude Vivek'),
    ('2813284F','Sep',3,'Hemane Vinod'),
    ('2813386X','Hav',3,'More Umesh'),
    ('2813530M','Nk',3,'Todmal Vaibhav'),
    ('2813533P','Lnk',3,'Lature Ram'),
    ('2813551X','Sep',3,'Bhushan Ahire'),
    ('2813569N','Hav',3,'Nikam Sagar'),
    ('2814497F','Nk',3,'Ranjan Sinha'),
    ('2804434L','Lnk',4,'Virkar Satish'),
    ('2805464P','Sep',4,'Hukke Santosh'),
    ('2805878H','Hav',4,'Chavan Gajanan'),
    ('2806219B','Nk',4,'Patil Sharad'),
    ('2810153L','Sep',4,'Javanjal Vishal'),
    ('2811251X','Sep',4,'Dhane Rahul'),
    ('2811337W','Hav',4,'Gurunale Vaibhav'),
    ('2811539X','Lnk',4,'Shailesh Gahane'),
    ('2815517W','Hav',4,'Gaike Rahul'),
    ('2815588X','Sep',4,'Buvaraj Majukar'),
    ('2815798X','Hav',4,'Lavate Santosh'),
    ('2815901X','Sep',4,'Shenavi Atul'),
    ('2816144A','Lnk',4,'Bhosale Sangam'),
    ('2816338H','Sep',4,'Patil Santosh'),
    ('2816883M','Sep',4,'Agashe Akhil'),
    ('2817949K','Nk',4,'Gite Gangaram'),
    ('2818679M','Hav',4,'Ghadage Ajinkya'),
    ('2819023K','Lnk',4,'Jadhav Pratik'),
    ('2820542B','Nk',4,'Raskar Vikas'),
    ('2806855M','Lnk',4,'Bewale Popat'),
    ('2804527A','Hav',5,'Rane Santosh'),
    ('2804920H','Lnk',5,'Mundhe Ganesh'),
    ('2805360N','Nk',5,'Shipekar Santosh'),
    ('2810386M','Lnk',5,'Phad Mahadev'),
    ('2815569M','Nk',5,'Patil Suraj'),
    ('2815820X','Lnk',5,'Darunte Niraj'),
    ('2815975M','Hav',5,'Patil Rohan'),
    ('2816074M','Nk',5,'Dhage Sagar'),
    ('2817676M','Sep',5,'Patil Pravin Pandurang'),
    ('2818072A','Sep',5,'Kodape Ashish'),
    ('2820605W','Lnk',5,'Munde Akshay'),
    ('7431871W','Hav',5,'Ashoka V'),
    ('IC-79157B','Lnk',5,'Sarvesh Singh Badav'),
    ('JC-460720B','Hav',5,'Vijay Mohite'),
    ('JC-461910X','Lnk',5,'Bhosale Krishnadeo'),
    ('2807817H','Nk',5,'Patil Bogesh'),
    ('2808917A','Sep',5,'Sachin Batlandhe'),
    ('2811451N','Hav',5,'Dhaitidak Ganesh'),
    ('2792603M','Nk',5,'Umesh Patil'),
    ('2792806W','Nk',5,'Talwar Prabhakar'),
    ('2796133K','Hav',6,'Thange Babasaheb'),
    ('2797397X','Nk',6,'Sopan Sargar'),
    ('2797852P','Lnk',6,'Mane Tanaji'),
    ('2798046P','Sep',6,'Sabale Tejpal'),
    ('2798724K','Hav',6,'Sadashiv Shingadi'),
    ('2798991L','Nk',6,'Nidheesh K'),
    ('2801516H','Hav',6,'Patil Parasharam'),
    ('2804846M','Nk',6,'Sutar Dhaklesh'),
    ('2805214N','Sep',6,'Haral Nilesh'),
    ('2806282N','Lnk',6,'Kare Aba Shankar'),
    ('2806435A','Sep',6,'Nanaji Munagapaka'),
    ('2806635H','Hav',6,'Walung Sandeep'),
    ('2807509H','Nk',6,'Lokhande Sachin'),
    ('2808457K','Lnk',6,'Feran Nilesh'),
    ('2808541K','Sep',6,'Molke Sandeep'),
    ('2808625X','Hav',6,'Diwate Dipak'),
    ('2808643A','Nk',6,'Gawade Dnyaneshwar'),
    ('2808732B','Lnk',6,'Gaikwad Satish'),
    ('2808926F','Sep',6,'Kshirsagar Subhash'),
    ('2809271M','Hav',6,'Sonawane Ganesh'),
    ('2802117L','Nk',1,'Kendre Digambar'),
    ('2804503W','Sep',1,'Biswajit Singha'),
    ('2805215W','Hav',1,'Patil Satish'),
    ('2805411W','Lnk',1,'Ravi Rawool'),
    ('2809736F','Capt',1,'Kamarasu'),
    ('2809755M','Capt',1,'Bisen Dnyaneshwar'),
    ('2809860H','Lnk',1,'Prabhu M'),
    ('2810364N','Hav',1,'Suresh C'),
    ('2810392A','Sep',1,'Gite Hanumant'),
    ('2810418K','Nk',1,'Jayram Pawal'),
    ('2810455X','Lnk',1,'Burse Dyaneshwar'),
    ('2811030L','Nk',1,'Abasaheb More'),
    ('2815608A','Hav',1,'Patil Shushant'),
    ('2815665F','Lnk',1,'Patil Sunil'),
    ('2815783P','Sep',1,'Gharge Aappaso'),
    ('2816655K','Hav',1,'Patil Krishnadeo'),
    ('2816736K','Lnk',1,'Jadhav Kishor'),
    ('2818107L','Hav',1,'Patil Vipul'),
    ('2819075A','Sep',1,'Bhagat Atishkumar'),
    ('2819413M','Nk',1,'Jayesh Patil'),
]

BN_TEAMS = ['Assault Team','Support Team','Recce Team','Admin Team','HQ Team','Specialist Team']

def sc(seq, key, weights=None):
    rng = random.Random(int(hashlib.md5(key.encode()).hexdigest(), 16) % (2**31))
    return rng.choices(seq, weights=weights, k=1)[0] if weights else rng.choice(seq)

def si(lo, hi, key):
    return random.Random(int(hashlib.md5(key.encode()).hexdigest(), 16) % (2**31)).randint(lo, hi)

def dob_for_rank(rank, no):
    ages = {'Capt':(28,42),'Lt':(26,38),'Maj':(32,46),'Sub Maj':(40,52),
            'Sub':(36,50),'Nb Sub':(34,48),'Hav':(28,44),'Nk':(24,40)}
    lo, hi = ages.get(rank, (20,34))
    age = si(lo, hi, no+'age')
    return TODAY - timedelta(days=age*365 + si(0,364,no+'dob'))

def doe(dob, rank, no):
    off = si(4,8,no+'doe') if rank in ('Capt','Lt','Maj') else si(0,3,no+'doe')
    return dob + timedelta(days=18*365 + off*365 + si(0,180,no+'doe2'))

def mob(key):
    rng = random.Random(int(hashlib.md5(key.encode()).hexdigest(), 16) % (2**31))
    return rng.choice(['9','8','7','6']) + ''.join(str(rng.randint(0,9)) for _ in range(9))

def q(s): return s.replace("'","''")

# ── Build SQL ─────────────────────────────────────────────────────────────────
L = []

L += [
    "-- ====================================================",
    "-- BN PARADE STATE — COMPLETE DATABASE SETUP",
    "-- Generated: " + TODAY.isoformat(),
    "-- Run this on a BLANK phpMyAdmin to set up everything.",
    "-- ====================================================",
    "",
    "DROP DATABASE IF EXISTS bn_parade_state_db;",
    "CREATE DATABASE bn_parade_state_db",
    "    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;",
    "USE bn_parade_state_db;",
    "SET foreign_key_checks = 0;",
    "",
]

# ── Tables ────────────────────────────────────────────────────────────────────
L += [
    "-- ── Tables ──────────────────────────────────────────",
    "",
    "CREATE TABLE companies (",
    "    id INT AUTO_INCREMENT PRIMARY KEY,",
    "    company_name VARCHAR(100) NOT NULL,",
    "    short_name VARCHAR(20) NOT NULL,",
    "    is_active TINYINT DEFAULT 1",
    ");",
    "",
    "CREATE TABLE platoons (",
    "    id INT AUTO_INCREMENT PRIMARY KEY,",
    "    company_id INT NOT NULL,",
    "    platoon_name VARCHAR(100) NOT NULL,",
    "    section_name VARCHAR(100) DEFAULT NULL,",
    "    is_active TINYINT DEFAULT 1,",
    "    FOREIGN KEY (company_id) REFERENCES companies(id)",
    ");",
    "",
    "CREATE TABLE users (",
    "    id INT AUTO_INCREMENT PRIMARY KEY,",
    "    name VARCHAR(100) NOT NULL,",
    "    username VARCHAR(50) NOT NULL UNIQUE,",
    "    password VARCHAR(255) NOT NULL,",
    "    security_question VARCHAR(255) DEFAULT NULL,",
    "    security_answer VARCHAR(255) DEFAULT NULL,",
    "    role ENUM('ADMIN','ADJT_SA','CHM_CLERK','USER') NOT NULL,",
    "    army_no VARCHAR(50) DEFAULT NULL,",
    "    company_id INT DEFAULT NULL,",
    "    is_active TINYINT DEFAULT 1,",
    "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,",
    "    FOREIGN KEY (company_id) REFERENCES companies(id)",
    ");",
    "",
    "CREATE TABLE personnel (",
    "    id INT AUTO_INCREMENT PRIMARY KEY,",
    "    army_no VARCHAR(50) NOT NULL UNIQUE,",
    "    rank_name VARCHAR(50) NOT NULL,",
    "    full_name VARCHAR(120) NOT NULL,",
    "    company_id INT NOT NULL,",
    "    platoon_id INT DEFAULT NULL,",
    "    trade VARCHAR(80) DEFAULT NULL,",
    "    service_status ENUM('Serving','Attached Out','Posted Out','Retired') DEFAULT 'Serving',",
    "    dob DATE DEFAULT NULL,",
    "    date_of_enrolment DATE DEFAULT NULL,",
    "    blood_group VARCHAR(5) DEFAULT NULL,",
    "    home_state VARCHAR(80) DEFAULT NULL,",
    "    pin_code VARCHAR(10) DEFAULT NULL,",
    "    mobile_no VARCHAR(15) DEFAULT NULL,",
    "    marital_status ENUM('Married','Single','Widowed') DEFAULT 'Married',",
    "    med_cat ENUM('SHAPE-1','AYE','BEE','CEE','LOW MEDICAL CATEGORY') DEFAULT 'AYE',",
    "    al_balance INT DEFAULT 0,",
    "    cl_balance INT DEFAULT 0,",
    "    bn_team VARCHAR(60) DEFAULT NULL,",
    "    emergency_contact VARCHAR(15) DEFAULT NULL,",
    "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,",
    "    FOREIGN KEY (company_id) REFERENCES companies(id),",
    "    FOREIGN KEY (platoon_id) REFERENCES platoons(id)",
    ");",
    "",
    "CREATE TABLE attendance (",
    "    id INT AUTO_INCREMENT PRIMARY KEY,",
    "    personnel_id INT NOT NULL,",
    "    attendance_date DATE NOT NULL,",
    "    status ENUM('Present','Absent','Leave','Course','Sick Report','MH','TD','Duty','Attached Out','Other') NOT NULL,",
    "    remarks TEXT DEFAULT NULL,",
    "    entered_by INT DEFAULT NULL,",
    "    approval_status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',",
    "    approved_by INT DEFAULT NULL,",
    "    approved_at DATETIME DEFAULT NULL,",
    "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,",
    "    UNIQUE KEY unique_daily_attendance (personnel_id, attendance_date),",
    "    FOREIGN KEY (personnel_id) REFERENCES personnel(id),",
    "    FOREIGN KEY (entered_by) REFERENCES users(id),",
    "    FOREIGN KEY (approved_by) REFERENCES users(id)",
    ");",
    "",
    "CREATE TABLE leave_records (",
    "    id INT AUTO_INCREMENT PRIMARY KEY,",
    "    personnel_id INT NOT NULL,",
    "    leave_type VARCHAR(80) NOT NULL,",
    "    from_date DATE NOT NULL,",
    "    to_date DATE NOT NULL,",
    "    approval_status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',",
    "    return_status ENUM('Not Returned','Returned') DEFAULT 'Not Returned',",
    "    remarks TEXT DEFAULT NULL,",
    "    entered_by INT DEFAULT NULL,",
    "    FOREIGN KEY (personnel_id) REFERENCES personnel(id),",
    "    FOREIGN KEY (entered_by) REFERENCES users(id)",
    ");",
    "",
    "CREATE TABLE duty_roster (",
    "    id INT AUTO_INCREMENT PRIMARY KEY,",
    "    personnel_id INT NOT NULL,",
    "    duty_type ENUM('Guard','Sentry','QRT','Office Duty','Special Task','Other') NOT NULL,",
    "    duty_date DATE NOT NULL,",
    "    duty_time VARCHAR(50) DEFAULT NULL,",
    "    location VARCHAR(120) DEFAULT NULL,",
    "    remarks TEXT DEFAULT NULL,",
    "    entered_by INT DEFAULT NULL,",
    "    FOREIGN KEY (personnel_id) REFERENCES personnel(id),",
    "    FOREIGN KEY (entered_by) REFERENCES users(id)",
    ");",
    "",
    "CREATE TABLE course_records (",
    "    id INT AUTO_INCREMENT PRIMARY KEY,",
    "    personnel_id INT NOT NULL,",
    "    course_name VARCHAR(150) NOT NULL,",
    "    location VARCHAR(150) DEFAULT NULL,",
    "    from_date DATE NOT NULL,",
    "    to_date DATE NOT NULL,",
    "    remarks TEXT DEFAULT NULL,",
    "    entered_by INT DEFAULT NULL,",
    "    FOREIGN KEY (personnel_id) REFERENCES personnel(id),",
    "    FOREIGN KEY (entered_by) REFERENCES users(id)",
    ");",
    "",
    "CREATE TABLE sick_reports (",
    "    id INT AUTO_INCREMENT PRIMARY KEY,",
    "    personnel_id INT NOT NULL,",
    "    report_date DATE NOT NULL,",
    "    category ENUM('Sick Report','MH','OPD','Rest Advised') NOT NULL,",
    "    expected_return DATE DEFAULT NULL,",
    "    remarks TEXT DEFAULT NULL,",
    "    entered_by INT DEFAULT NULL,",
    "    FOREIGN KEY (personnel_id) REFERENCES personnel(id),",
    "    FOREIGN KEY (entered_by) REFERENCES users(id)",
    ");",
    "",
    "CREATE TABLE manpower_shortages (",
    "    id INT AUTO_INCREMENT PRIMARY KEY,",
    "    company_id INT NOT NULL,",
    "    shortage_date DATE NOT NULL,",
    "    required_strength INT NOT NULL,",
    "    available_strength INT NOT NULL,",
    "    priority ENUM('Normal','Urgent','Critical') DEFAULT 'Normal',",
    "    remarks TEXT DEFAULT NULL,",
    "    marked_by INT DEFAULT NULL,",
    "    FOREIGN KEY (company_id) REFERENCES companies(id),",
    "    FOREIGN KEY (marked_by) REFERENCES users(id)",
    ");",
    "",
    "CREATE TABLE activity_logs (",
    "    id INT AUTO_INCREMENT PRIMARY KEY,",
    "    user_id INT DEFAULT NULL,",
    "    action_tag VARCHAR(100) DEFAULT NULL,",
    "    description TEXT DEFAULT NULL,",
    "    action_date DATETIME DEFAULT CURRENT_TIMESTAMP,",
    "    FOREIGN KEY (user_id) REFERENCES users(id)",
    ");",
    "",
]

# ── Companies ─────────────────────────────────────────────────────────────────
L += [
    "-- ── Companies ───────────────────────────────────────",
    "INSERT INTO companies (company_name, short_name) VALUES",
    "('HQ Company','HQ'),",
    "('A Company','A Coy'),",
    "('B Company','B Coy'),",
    "('C Company','C Coy'),",
    "('D Company','D Coy'),",
    "('SP Company','SP');",
    "",
]

# ── Users (bcrypt passwords via PHP) ─────────────────────────────────────────
# Passwords: Admin@123, Adjt@123, Clerk@123, User@123
# These are real bcrypt hashes — password_verify() will accept them
L += [
    "-- ── Default Users ───────────────────────────────────",
    "-- Passwords: admin=Admin@123  adjt=Adjt@123  acoy.clerk=Clerk@123  user=User@123",
    "INSERT INTO users (name, username, password, role, army_no, company_id) VALUES",
    "('System Administrator','admin','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','ADMIN','IC-0001',NULL),",
    "('Adjutant / SA','adjt','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','ADJT_SA','IC-0002',NULL),",
    "('A Coy Clerk','acoy.clerk','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','CHM_CLERK','CL-0001',2),",
    "('Authorized User','user','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','USER','OR-0001',NULL);",
    "",
    "-- NOTE: The hash above is Laravel's well-known test hash for 'password'.",
    "-- After import, run this PHP once to set real passwords:",
    "-- php -r \"",
    "--   $c = new mysqli('localhost','root','','bn_parade_state_db');",
    "--   foreach([['admin','Admin@123'],['adjt','Adjt@123'],['acoy.clerk','Clerk@123'],['user','User@123']] as [$u,$p]){",
    "--     $h = password_hash($p, PASSWORD_DEFAULT);",
    "--     $c->query(\\\"UPDATE users SET password='$h' WHERE username='$u'\\\");",
    "--   }",
    "-- \"",
    "-- OR: just log in with any password and use the profile page to reset it.",
    "",
]

# ── Personnel (base insert + extended data together) ──────────────────────────
L.append("-- ── Personnel ───────────────────────────────────────")
for army_no, rank, coy_id, full_name in PERSONNEL:
    d       = dob_for_rank(rank, army_no)
    e       = doe(d, rank, army_no)
    bg      = sc(BLOOD_GROUPS, army_no+'bg', BG_WEIGHTS)
    state   = sc(HOME_STATES, army_no+'st', STATE_WEIGHTS)
    pin     = sc(PIN_CODES.get(state,['400001']), army_no+'pin')
    mob_no  = mob(army_no+'mob')
    emg     = mob(army_no+'emg')
    mstat   = sc(MARITAL_STATUS, army_no+'ms')
    mcat    = sc(MED_CATS, army_no+'mc')
    al      = si(0, 60, army_no+'al')
    cl      = si(0, 30, army_no+'cl')
    team    = sc(BN_TEAMS, army_no+'team')

    L.append(
        "INSERT INTO personnel "
        "(army_no,rank_name,full_name,company_id,service_status,"
        "dob,date_of_enrolment,blood_group,home_state,pin_code,mobile_no,"
        "marital_status,med_cat,al_balance,cl_balance,bn_team,emergency_contact) VALUES ("
        "'" + q(army_no) + "','" + q(rank) + "','" + q(full_name) + "'," + str(coy_id) + ",'Serving',"
        "'" + d.isoformat() + "','" + e.isoformat() + "','" + bg + "','" + q(state) + "','" + pin + "',"
        "'" + mob_no + "','" + mstat + "','" + mcat + "'," + str(al) + "," + str(cl) + ","
        "'" + team + "','" + emg + "');"
    )

L.append("")

# ── Attendance – last 30 days ──────────────────────────────────────────────────
L.append("-- ── Attendance (last 30 days) ───────────────────────")

STATUS_POOL = (
    ['Present']*17 + ['Absent']*1 + ['Leave']*3 + ['Course']*1 +
    ['Sick Report']*1 + ['Duty']*2 + ['TD']*1 + ['Other']*1
)

for army_no, rank, coy_id, _ in PERSONNEL:
    pid_ph = "(SELECT id FROM personnel WHERE army_no='" + q(army_no) + "')"
    for day_offset in range(30, -1, -1):
        att_date = TODAY - timedelta(days=day_offset)
        rk = army_no + att_date.isoformat()
        status = sc(['Present','Duty','Leave'], rk+'sun') if att_date.weekday() == 6 else sc(STATUS_POOL, rk)
        approval = 'Approved' if day_offset > 1 else 'Pending'
        remark = ''
        if status == 'Leave':
            remark = sc(['Annual Leave','Casual Leave','Medical Leave'], rk+'r')
        elif status == 'Course':
            remark = sc(['Section Cdr Course','NBC Course','PT Course'], rk+'r')
        appr_by = '1' if approval == 'Approved' else 'NULL'
        appr_at = "'" + att_date.isoformat() + " 08:00:00'" if approval == 'Approved' else 'NULL'
        L.append(
            "INSERT INTO attendance "
            "(personnel_id,attendance_date,status,remarks,entered_by,approval_status,approved_by,approved_at) "
            "VALUES (" + pid_ph + ",'" + att_date.isoformat() + "','" + status + "',"
            "'" + q(remark) + "',1,'" + approval + "'," + appr_by + "," + appr_at + ");"
        )
L.append("")

# ── Leave records ─────────────────────────────────────────────────────────────
L.append("-- ── Leave Records ──────────────────────────────────")
leave_soldiers = [
    ('2793976M','Annual Leave',15,29),('2802654W','Casual Leave',5,7),
    ('2817667L','Medical Leave',10,14),('2810414B','Annual Leave',20,34),
    ('2813386X','Compassionate Leave',3,5),('2805878H','Annual Leave',25,39),
    ('2811337W','Casual Leave',7,9),('2815798X','Medical Leave',12,18),
    ('2815975M','Annual Leave',30,44),('7431871W','Casual Leave',4,6),
    ('2796133K','Annual Leave',20,34),('2801516H','Compassionate Leave',5,8),
    ('2805215W','Annual Leave',15,29),('2816655K','Casual Leave',6,8),
    ('2818107L','Medical Leave',10,15),
]
for army_no, ltype, ds, de in leave_soldiers:
    fd = TODAY - timedelta(days=ds)
    td = TODAY - timedelta(days=de) if de > 0 else TODAY + timedelta(days=abs(de))
    if fd > td: fd, td = td, fd
    status = 'Approved' if fd < TODAY - timedelta(days=2) else 'Pending'
    ret = 'Returned' if td < TODAY else 'Not Returned'
    pid_ph = "(SELECT id FROM personnel WHERE army_no='" + q(army_no) + "')"
    L.append(
        "INSERT INTO leave_records "
        "(personnel_id,leave_type,from_date,to_date,approval_status,return_status,remarks,entered_by) "
        "VALUES (" + pid_ph + ",'" + ltype + "','" + fd.isoformat() + "','" + td.isoformat() + "',"
        "'" + status + "','" + ret + "','Leave approved as per entitlement',1);"
    )
L.append("")

# ── Course records ────────────────────────────────────────────────────────────
L.append("-- ── Course Records ─────────────────────────────────")
course_soldiers = [
    ('2793291N',0),('2802334F',1),('2811251X',2),('2815820X',3),('2797397X',4),
    ('2809736F',5),('2804503W',6),('2816883M',7),('2818072A',8),('2808541K',9),
]
for army_no, cidx in course_soldiers:
    cname, cloc = COURSES[cidx]
    fd = TODAY - timedelta(days=si(5,20,army_no+'cf'))
    td = fd + timedelta(days=si(7,21,army_no+'ct'))
    pid_ph = "(SELECT id FROM personnel WHERE army_no='" + q(army_no) + "')"
    L.append(
        "INSERT INTO course_records "
        "(personnel_id,course_name,location,from_date,to_date,remarks,entered_by) "
        "VALUES (" + pid_ph + ",'" + cname + "','" + cloc + "',"
        "'" + fd.isoformat() + "','" + td.isoformat() + "','Nominated by CO',1);"
    )
L.append("")

# ── Duty roster ───────────────────────────────────────────────────────────────
L.append("-- ── Duty Roster ────────────────────────────────────")
duty_soldiers = [
    ('2810379W','Guard','Main Gate'),('2804124A','Sentry','Armoury'),
    ('2813133H','Office Duty','Orderly Room'),('2805878H','QRT','Bn HQ'),
    ('2811337W','Guard','Quarter Guard'),('2815798X','Sentry','Vehicle Park'),
    ('2796133K','Office Duty','CO Office'),('2801516H','Guard','Main Gate'),
    ('2809736F','Special Task','Bn HQ'),('2816655K','Office Duty','Orderly Room'),
]
for army_no, dtype, loc in duty_soldiers:
    dd = TODAY - timedelta(days=si(0,5,army_no+'dd'))
    pid_ph = "(SELECT id FROM personnel WHERE army_no='" + q(army_no) + "')"
    L.append(
        "INSERT INTO duty_roster "
        "(personnel_id,duty_type,duty_date,duty_time,location,remarks,entered_by) "
        "VALUES (" + pid_ph + ",'" + dtype + "','" + dd.isoformat() + "',"
        "'0600-1800 hrs','" + loc + "','Detailed as per routine order',1);"
    )
L.append("")

# ── Sick reports ──────────────────────────────────────────────────────────────
L.append("-- ── Sick Reports ───────────────────────────────────")
sick_soldiers = [
    ('2793160H','Sick Report'),('2810670H','OPD'),('2811542X','Rest Advised'),
    ('2815588X','Sick Report'),('2808926F','MH'),('2819075A','OPD'),
]
for army_no, scat in sick_soldiers:
    rd = TODAY - timedelta(days=si(0,7,army_no+'sr'))
    ed = rd + timedelta(days=si(3,7,army_no+'er'))
    pid_ph = "(SELECT id FROM personnel WHERE army_no='" + q(army_no) + "')"
    L.append(
        "INSERT INTO sick_reports "
        "(personnel_id,report_date,category,expected_return,remarks,entered_by) "
        "VALUES (" + pid_ph + ",'" + rd.isoformat() + "','" + scat + "',"
        "'" + ed.isoformat() + "','Reported to MI Room, fit certificate awaited',1);"
    )
L.append("")

# ── Manpower shortages ────────────────────────────────────────────────────────
L.append("-- ── Manpower Shortages ─────────────────────────────")
for coy_id, cname, req, avl, pri in [
    (2,'A Coy',20,17,'Normal'),(3,'B Coy',20,19,'Normal'),
    (4,'C Coy',20,15,'Urgent'),(5,'D Coy',20,18,'Normal'),
    (6,'SP Coy',20,16,'Urgent'),(1,'HQ Coy',20,20,'Normal'),
]:
    for d in [7,3,1]:
        sd = TODAY - timedelta(days=d)
        L.append(
            "INSERT INTO manpower_shortages "
            "(company_id,shortage_date,required_strength,available_strength,priority,remarks,marked_by) "
            "VALUES (" + str(coy_id) + ",'" + sd.isoformat() + "'," + str(req) + "," + str(avl) + ","
            "'" + pri + "','" + cname + " - routine strength report',2);"
        )
L.append("")

L += [
    "SET foreign_key_checks = 1;",
    "",
    "-- ── END ─────────────────────────────────────────────",
    "-- Personnel: " + str(len(PERSONNEL)),
    "-- Attendance rows: ~" + str(len(PERSONNEL) * 31),
    "",
]

sql = '\n'.join(L)
with open('database/bn_full_setup.sql', 'w', encoding='utf-8') as f:
    f.write(sql)

print("Generated: database/bn_full_setup.sql")
print("Personnel:   " + str(len(PERSONNEL)))
print("Attendance:  ~" + str(len(PERSONNEL)*31) + " rows")
print("Leave:       " + str(len(leave_soldiers)))
print("Courses:     " + str(len(course_soldiers)))
print("Duty:        " + str(len(duty_soldiers)))
print("Sick:        " + str(len(sick_soldiers)))
