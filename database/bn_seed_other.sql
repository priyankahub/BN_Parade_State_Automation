-- BN PARADE STATE — LEAVE / COURSE / DUTY / SICK / SHORTAGE SEED DATA
-- Generated: 2026-05-03
USE bn_parade_state_db;
SET foreign_key_checks=0;

DELETE FROM leave_records;
DELETE FROM course_records;
DELETE FROM duty_roster;
DELETE FROM sick_reports;
DELETE FROM manpower_shortages;

-- ── Leave Records ───────────────────────────────
INSERT INTO leave_records (personnel_id,leave_type,from_date,to_date,approval_status,return_status,remarks,entered_by) VALUES (4,'Annual Leave','2026-04-04','2026-04-18','Approved','Returned','Leave approved as per entitlement',1);
INSERT INTO leave_records (personnel_id,leave_type,from_date,to_date,approval_status,return_status,remarks,entered_by) VALUES (7,'Casual Leave','2026-04-26','2026-04-28','Approved','Returned','Leave approved as per entitlement',1);
INSERT INTO leave_records (personnel_id,leave_type,from_date,to_date,approval_status,return_status,remarks,entered_by) VALUES (18,'Medical Leave','2026-04-19','2026-04-23','Approved','Returned','Leave approved as per entitlement',1);
INSERT INTO leave_records (personnel_id,leave_type,from_date,to_date,approval_status,return_status,remarks,entered_by) VALUES (28,'Annual Leave','2026-03-30','2026-04-13','Approved','Returned','Leave approved as per entitlement',1);
INSERT INTO leave_records (personnel_id,leave_type,from_date,to_date,approval_status,return_status,remarks,entered_by) VALUES (35,'Compassionate Leave','2026-04-28','2026-04-30','Approved','Returned','Leave approved as per entitlement',1);
INSERT INTO leave_records (personnel_id,leave_type,from_date,to_date,approval_status,return_status,remarks,entered_by) VALUES (43,'Annual Leave','2026-03-25','2026-04-08','Approved','Returned','Leave approved as per entitlement',1);
INSERT INTO leave_records (personnel_id,leave_type,from_date,to_date,approval_status,return_status,remarks,entered_by) VALUES (47,'Casual Leave','2026-04-24','2026-04-26','Approved','Returned','Leave approved as per entitlement',1);
INSERT INTO leave_records (personnel_id,leave_type,from_date,to_date,approval_status,return_status,remarks,entered_by) VALUES (51,'Medical Leave','2026-04-15','2026-04-21','Approved','Returned','Leave approved as per entitlement',1);
INSERT INTO leave_records (personnel_id,leave_type,from_date,to_date,approval_status,return_status,remarks,entered_by) VALUES (67,'Annual Leave','2026-03-20','2026-04-03','Approved','Returned','Leave approved as per entitlement',1);
INSERT INTO leave_records (personnel_id,leave_type,from_date,to_date,approval_status,return_status,remarks,entered_by) VALUES (72,'Casual Leave','2026-04-27','2026-04-29','Approved','Returned','Leave approved as per entitlement',1);
INSERT INTO leave_records (personnel_id,leave_type,from_date,to_date,approval_status,return_status,remarks,entered_by) VALUES (81,'Annual Leave','2026-03-30','2026-04-13','Approved','Returned','Leave approved as per entitlement',1);
INSERT INTO leave_records (personnel_id,leave_type,from_date,to_date,approval_status,return_status,remarks,entered_by) VALUES (87,'Compassionate Leave','2026-04-25','2026-04-28','Approved','Returned','Leave approved as per entitlement',1);
INSERT INTO leave_records (personnel_id,leave_type,from_date,to_date,approval_status,return_status,remarks,entered_by) VALUES (103,'Annual Leave','2026-04-04','2026-04-18','Approved','Returned','Leave approved as per entitlement',1);
INSERT INTO leave_records (personnel_id,leave_type,from_date,to_date,approval_status,return_status,remarks,entered_by) VALUES (116,'Casual Leave','2026-04-25','2026-04-27','Approved','Returned','Leave approved as per entitlement',1);
INSERT INTO leave_records (personnel_id,leave_type,from_date,to_date,approval_status,return_status,remarks,entered_by) VALUES (118,'Medical Leave','2026-04-18','2026-04-23','Approved','Returned','Leave approved as per entitlement',1);

-- ── Course Records ─────────────────────────────
INSERT INTO course_records (personnel_id,course_name,location,from_date,to_date,remarks,entered_by) VALUES (3,'Section Commanders Course','ITC Belgaum','2026-04-20','2026-05-06','Nominated by CO',1);
INSERT INTO course_records (personnel_id,course_name,location,from_date,to_date,remarks,entered_by) VALUES (25,'NBC Course','NBC School Baroda','2026-04-27','2026-05-12','Nominated by CO',1);
INSERT INTO course_records (personnel_id,course_name,location,from_date,to_date,remarks,entered_by) VALUES (46,'Signals Course','CME Pune','2026-04-28','2026-05-15','Nominated by CO',1);
INSERT INTO course_records (personnel_id,course_name,location,from_date,to_date,remarks,entered_by) VALUES (66,'PT Instructor Course','ASPT Pune','2026-04-18','2026-05-01','Nominated by CO',1);
INSERT INTO course_records (personnel_id,course_name,location,from_date,to_date,remarks,entered_by) VALUES (82,'Combat Engineers Course','MCTE Mhow','2026-04-28','2026-05-16','Nominated by CO',1);
INSERT INTO course_records (personnel_id,course_name,location,from_date,to_date,remarks,entered_by) VALUES (105,'Snipers Course','Infantry School Mhow','2026-04-28','2026-05-18','Nominated by CO',1);
INSERT INTO course_records (personnel_id,course_name,location,from_date,to_date,remarks,entered_by) VALUES (102,'MG Platoon Course','Infantry School Mhow','2026-04-13','2026-05-02','Nominated by CO',1);
INSERT INTO course_records (personnel_id,course_name,location,from_date,to_date,remarks,entered_by) VALUES (55,'Driving Course','DEME Bhopal','2026-04-19','2026-05-03','Nominated by CO',1);
INSERT INTO course_records (personnel_id,course_name,location,from_date,to_date,remarks,entered_by) VALUES (70,'Medical Aid Course','AMC Centre','2026-04-26','2026-05-15','Nominated by CO',1);
INSERT INTO course_records (personnel_id,course_name,location,from_date,to_date,remarks,entered_by) VALUES (95,'Leadership Course','OTA Chennai','2026-04-22','2026-05-05','Nominated by CO',1);

-- ── Duty Roster ─────────────────────────────────
INSERT INTO duty_roster (personnel_id,duty_type,duty_date,duty_time,location,remarks,entered_by) VALUES (11,'Guard','2026-05-02','0600-1800 hrs','Main Gate','Detailed as per routine order',1);
INSERT INTO duty_roster (personnel_id,duty_type,duty_date,duty_time,location,remarks,entered_by) VALUES (8,'Sentry','2026-05-02','0600-1800 hrs','Armoury','Detailed as per routine order',1);
INSERT INTO duty_roster (personnel_id,duty_type,duty_date,duty_time,location,remarks,entered_by) VALUES (31,'Office Duty','2026-05-02','0600-1800 hrs','Orderly Room','Detailed as per routine order',1);
INSERT INTO duty_roster (personnel_id,duty_type,duty_date,duty_time,location,remarks,entered_by) VALUES (43,'QRT','2026-05-03','0600-1800 hrs','Bn HQ','Detailed as per routine order',1);
INSERT INTO duty_roster (personnel_id,duty_type,duty_date,duty_time,location,remarks,entered_by) VALUES (47,'Guard','2026-05-02','0600-1800 hrs','Quarter Guard','Detailed as per routine order',1);
INSERT INTO duty_roster (personnel_id,duty_type,duty_date,duty_time,location,remarks,entered_by) VALUES (51,'Sentry','2026-04-30','0600-1800 hrs','Vehicle Park','Detailed as per routine order',1);
INSERT INTO duty_roster (personnel_id,duty_type,duty_date,duty_time,location,remarks,entered_by) VALUES (81,'Office Duty','2026-05-01','0600-1800 hrs','CO Office','Detailed as per routine order',1);
INSERT INTO duty_roster (personnel_id,duty_type,duty_date,duty_time,location,remarks,entered_by) VALUES (87,'Guard','2026-04-28','0600-1800 hrs','Main Gate','Detailed as per routine order',1);
INSERT INTO duty_roster (personnel_id,duty_type,duty_date,duty_time,location,remarks,entered_by) VALUES (105,'Special Task','2026-05-02','0600-1800 hrs','Bn HQ','Detailed as per routine order',1);
INSERT INTO duty_roster (personnel_id,duty_type,duty_date,duty_time,location,remarks,entered_by) VALUES (116,'Office Duty','2026-05-01','0600-1800 hrs','Orderly Room','Detailed as per routine order',1);

-- ── Sick Reports ────────────────────────────────
INSERT INTO sick_reports (personnel_id,report_date,category,expected_return,remarks,entered_by) VALUES (2,'2026-04-27','Sick Report','2026-04-30','Reported to MI Room, fit certificate awaited',1);
INSERT INTO sick_reports (personnel_id,report_date,category,expected_return,remarks,entered_by) VALUES (12,'2026-05-01','OPD','2026-05-05','Reported to MI Room, fit certificate awaited',1);
INSERT INTO sick_reports (personnel_id,report_date,category,expected_return,remarks,entered_by) VALUES (30,'2026-04-26','Rest Advised','2026-04-30','Reported to MI Room, fit certificate awaited',1);
INSERT INTO sick_reports (personnel_id,report_date,category,expected_return,remarks,entered_by) VALUES (50,'2026-04-30','Sick Report','2026-05-07','Reported to MI Room, fit certificate awaited',1);
INSERT INTO sick_reports (personnel_id,report_date,category,expected_return,remarks,entered_by) VALUES (99,'2026-04-29','MH','2026-05-04','Reported to MI Room, fit certificate awaited',1);
INSERT INTO sick_reports (personnel_id,report_date,category,expected_return,remarks,entered_by) VALUES (119,'2026-04-30','OPD','2026-05-03','Reported to MI Room, fit certificate awaited',1);

-- ── Manpower Shortages ──────────────────────────
INSERT INTO manpower_shortages (company_id,shortage_date,required_strength,available_strength,priority,remarks,marked_by) VALUES (2,'2026-04-26',20,17,'Normal','A Coy - routine strength report',2);
INSERT INTO manpower_shortages (company_id,shortage_date,required_strength,available_strength,priority,remarks,marked_by) VALUES (2,'2026-04-30',20,17,'Normal','A Coy - routine strength report',2);
INSERT INTO manpower_shortages (company_id,shortage_date,required_strength,available_strength,priority,remarks,marked_by) VALUES (2,'2026-05-02',20,17,'Normal','A Coy - routine strength report',2);
INSERT INTO manpower_shortages (company_id,shortage_date,required_strength,available_strength,priority,remarks,marked_by) VALUES (3,'2026-04-26',20,19,'Normal','B Coy - routine strength report',2);
INSERT INTO manpower_shortages (company_id,shortage_date,required_strength,available_strength,priority,remarks,marked_by) VALUES (3,'2026-04-30',20,19,'Normal','B Coy - routine strength report',2);
INSERT INTO manpower_shortages (company_id,shortage_date,required_strength,available_strength,priority,remarks,marked_by) VALUES (3,'2026-05-02',20,19,'Normal','B Coy - routine strength report',2);
INSERT INTO manpower_shortages (company_id,shortage_date,required_strength,available_strength,priority,remarks,marked_by) VALUES (4,'2026-04-26',20,15,'Urgent','C Coy - routine strength report',2);
INSERT INTO manpower_shortages (company_id,shortage_date,required_strength,available_strength,priority,remarks,marked_by) VALUES (4,'2026-04-30',20,15,'Urgent','C Coy - routine strength report',2);
INSERT INTO manpower_shortages (company_id,shortage_date,required_strength,available_strength,priority,remarks,marked_by) VALUES (4,'2026-05-02',20,15,'Urgent','C Coy - routine strength report',2);
INSERT INTO manpower_shortages (company_id,shortage_date,required_strength,available_strength,priority,remarks,marked_by) VALUES (5,'2026-04-26',20,18,'Normal','D Coy - routine strength report',2);
INSERT INTO manpower_shortages (company_id,shortage_date,required_strength,available_strength,priority,remarks,marked_by) VALUES (5,'2026-04-30',20,18,'Normal','D Coy - routine strength report',2);
INSERT INTO manpower_shortages (company_id,shortage_date,required_strength,available_strength,priority,remarks,marked_by) VALUES (5,'2026-05-02',20,18,'Normal','D Coy - routine strength report',2);
INSERT INTO manpower_shortages (company_id,shortage_date,required_strength,available_strength,priority,remarks,marked_by) VALUES (6,'2026-04-26',20,16,'Urgent','SP Coy - routine strength report',2);
INSERT INTO manpower_shortages (company_id,shortage_date,required_strength,available_strength,priority,remarks,marked_by) VALUES (6,'2026-04-30',20,16,'Urgent','SP Coy - routine strength report',2);
INSERT INTO manpower_shortages (company_id,shortage_date,required_strength,available_strength,priority,remarks,marked_by) VALUES (6,'2026-05-02',20,16,'Urgent','SP Coy - routine strength report',2);
INSERT INTO manpower_shortages (company_id,shortage_date,required_strength,available_strength,priority,remarks,marked_by) VALUES (1,'2026-04-26',20,20,'Normal','HQ Coy - routine strength report',2);
INSERT INTO manpower_shortages (company_id,shortage_date,required_strength,available_strength,priority,remarks,marked_by) VALUES (1,'2026-04-30',20,20,'Normal','HQ Coy - routine strength report',2);
INSERT INTO manpower_shortages (company_id,shortage_date,required_strength,available_strength,priority,remarks,marked_by) VALUES (1,'2026-05-02',20,20,'Normal','HQ Coy - routine strength report',2);

SET foreign_key_checks=1;
