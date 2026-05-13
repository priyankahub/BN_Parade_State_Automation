"""
Generates two small SQL files using hardcoded personnel IDs:
  bn_seed_attendance.sql  — 30 days attendance (batched multi-row INSERTs)
  bn_seed_other.sql       — leave, course, duty, sick, shortages
"""
import random, hashlib
from datetime import date, timedelta

random.seed(42)
TODAY = date.today()

# ── personnel id → (army_no, rank, company_id) ────────────────────────────────
PERSONNEL = [
    (1,'17027948A','Nk',2),(2,'2793160H','Lnk',2),(3,'2793291N','Sep',2),
    (4,'2793976M','Hav',2),(5,'2794183W','Nk',2),(6,'2795176M','Lnk',2),
    (7,'2802654W','Hav',2),(8,'2804124A','Lnk',2),(9,'2804229K','Sep',2),
    (10,'2804236A','Hav',2),(11,'2810379W','Nk',2),(12,'2810670H','Sep',2),
    (13,'2810739F','Hav',2),(14,'2811470B','Nk',2),(15,'2816665N','Nk',2),
    (16,'2817465K','Nk',2),(17,'2817543W','Lnk',2),(18,'2817667L','Nk',2),
    (19,'2817670L','Lnk',2),(20,'2818032B','Lnk',2),
    (21,'2795922K','Sep',3),(22,'2799411X','Lnk',3),(23,'2800191N','Sep',3),
    (24,'2802262H','Lnk',3),(25,'2802334F','Sep',3),(26,'2803159K','Nk',3),
    (27,'2804378X','Nk',3),(28,'2810414B','Hav',3),(29,'2811140A','Lnk',3),
    (30,'2811542X','Sep',3),(31,'2813133H','Hav',3),(32,'2813204B','Nk',3),
    (33,'2813234W','Lnk',3),(34,'2813284F','Sep',3),(35,'2813386X','Hav',3),
    (36,'2813530M','Nk',3),(37,'2813533P','Lnk',3),(38,'2813551X','Sep',3),
    (39,'2813569N','Hav',3),(40,'2814497F','Nk',3),
    (41,'2804434L','Lnk',4),(42,'2805464P','Sep',4),(43,'2805878H','Hav',4),
    (44,'2806219B','Nk',4),(45,'2810153L','Sep',4),(46,'2811251X','Sep',4),
    (47,'2811337W','Hav',4),(48,'2811539X','Lnk',4),(49,'2815517W','Hav',4),
    (50,'2815588X','Sep',4),(51,'2815798X','Hav',4),(52,'2815901X','Sep',4),
    (53,'2816144A','Lnk',4),(54,'2816338H','Sep',4),(55,'2816883M','Sep',4),
    (56,'2817949K','Nk',4),(57,'2818679M','Hav',4),(58,'2819023K','Lnk',4),
    (59,'2820542B','Nk',4),(60,'2806855M','Lnk',4),
    (61,'2804527A','Hav',5),(62,'2804920H','Lnk',5),(63,'2805360N','Nk',5),
    (64,'2810386M','Lnk',5),(65,'2815569M','Nk',5),(66,'2815820X','Lnk',5),
    (67,'2815975M','Hav',5),(68,'2816074M','Nk',5),(69,'2817676M','Sep',5),
    (70,'2818072A','Sep',5),(71,'2820605W','Lnk',5),(72,'7431871W','Hav',5),
    (73,'IC-79157B','Lnk',5),(74,'JC-460720B','Hav',5),(75,'JC-461910X','Lnk',5),
    (76,'2807817H','Nk',5),(77,'2808917A','Sep',5),(78,'2811451N','Hav',5),
    (79,'2792603M','Nk',5),(80,'2792806W','Nk',5),
    (81,'2796133K','Hav',6),(82,'2797397X','Nk',6),(83,'2797852P','Lnk',6),
    (84,'2798046P','Sep',6),(85,'2798724K','Hav',6),(86,'2798991L','Nk',6),
    (87,'2801516H','Hav',6),(88,'2804846M','Nk',6),(89,'2805214N','Sep',6),
    (90,'2806282N','Lnk',6),(91,'2806435A','Sep',6),(92,'2806635H','Hav',6),
    (93,'2807509H','Nk',6),(94,'2808457K','Lnk',6),(95,'2808541K','Sep',6),
    (96,'2808625X','Hav',6),(97,'2808643A','Nk',6),(98,'2808732B','Lnk',6),
    (99,'2808926F','Sep',6),(100,'2809271M','Hav',6),
    (101,'2802117L','Nk',1),(102,'2804503W','Sep',1),(103,'2805215W','Hav',1),
    (104,'2805411W','Lnk',1),(105,'2809736F','Capt',1),(106,'2809755M','Capt',1),
    (107,'2809860H','Lnk',1),(108,'2810364N','Hav',1),(109,'2810392A','Sep',1),
    (110,'2810418K','Nk',1),(111,'2810455X','Lnk',1),(112,'2811030L','Nk',1),
    (113,'2815608A','Hav',1),(114,'2815665F','Lnk',1),(115,'2815783P','Sep',1),
    (116,'2816655K','Hav',1),(117,'2816736K','Lnk',1),(118,'2818107L','Hav',1),
    (119,'2819075A','Sep',1),(120,'2819413M','Nk',1),
]

STATUS_POOL = (
    ['Present']*17 + ['Absent']*1 + ['Leave']*3 + ['Course']*1 +
    ['Sick Report']*1 + ['Duty']*2 + ['TD']*1 + ['Other']*1
)

def sc(seq, key):
    return random.Random(int(hashlib.md5(key.encode()).hexdigest(),16)%(2**31)).choice(seq)

def si(lo, hi, key):
    return random.Random(int(hashlib.md5(key.encode()).hexdigest(),16)%(2**31)).randint(lo,hi)

# ── 1. ATTENDANCE ─────────────────────────────────────────────────────────────
BATCH = 400
rows = []
for pid, army_no, rank, coy_id in PERSONNEL:
    for day_offset in range(30, -1, -1):
        att_date = TODAY - timedelta(days=day_offset)
        rk = army_no + att_date.isoformat()
        status = sc(['Present','Duty','Leave'], rk+'sun') if att_date.weekday()==6 else sc(STATUS_POOL, rk)
        approval = 'Approved' if day_offset > 1 else 'Pending'
        remark = ''
        if status == 'Leave':
            remark = sc(['Annual Leave','Casual Leave','Medical Leave'], rk+'r')
        elif status == 'Course':
            remark = sc(['Section Cdr Course','NBC Course','PT Course'], rk+'r')
        remark = remark.replace("'","''")
        appr_by = '1' if approval == 'Approved' else 'NULL'
        appr_at = "'" + att_date.isoformat() + " 08:00:00'" if approval == 'Approved' else 'NULL'
        rows.append(
            "(" + str(pid) + ",'" + att_date.isoformat() + "','" + status + "','" +
            remark + "',1,'" + approval + "'," + appr_by + "," + appr_at + ")"
        )

att_lines = [
    "-- BN PARADE STATE — ATTENDANCE SEED DATA",
    "-- Generated: " + TODAY.isoformat(),
    "-- " + str(len(rows)) + " rows across 30 days for 120 personnel",
    "USE bn_parade_state_db;",
    "SET foreign_key_checks=0;",
    "",
    "-- Clear existing attendance first",
    "DELETE FROM attendance;",
    "",
]

header = ("INSERT INTO attendance "
          "(personnel_id,attendance_date,status,remarks,entered_by,"
          "approval_status,approved_by,approved_at) VALUES\n")

for i in range(0, len(rows), BATCH):
    chunk = rows[i:i+BATCH]
    att_lines.append(header + ',\n'.join(chunk) + ';')
    att_lines.append("")

att_lines += ["SET foreign_key_checks=1;", ""]

with open('database/bn_seed_attendance.sql','w',encoding='utf-8') as f:
    f.write('\n'.join(att_lines))

# ── 2. OTHER SEED DATA ────────────────────────────────────────────────────────
oth = [
    "-- BN PARADE STATE — LEAVE / COURSE / DUTY / SICK / SHORTAGE SEED DATA",
    "-- Generated: " + TODAY.isoformat(),
    "USE bn_parade_state_db;",
    "SET foreign_key_checks=0;",
    "",
    "DELETE FROM leave_records;",
    "DELETE FROM course_records;",
    "DELETE FROM duty_roster;",
    "DELETE FROM sick_reports;",
    "DELETE FROM manpower_shortages;",
    "",
    "-- ── Leave Records ───────────────────────────────",
]

leave_data = [
    (4,'Annual Leave',15,29),(7,'Casual Leave',5,7),(18,'Medical Leave',10,14),
    (28,'Annual Leave',20,34),(35,'Compassionate Leave',3,5),(43,'Annual Leave',25,39),
    (47,'Casual Leave',7,9),(51,'Medical Leave',12,18),(67,'Annual Leave',30,44),
    (72,'Casual Leave',4,6),(81,'Annual Leave',20,34),(87,'Compassionate Leave',5,8),
    (103,'Annual Leave',15,29),(116,'Casual Leave',6,8),(118,'Medical Leave',10,15),
]
for pid, ltype, ds, de in leave_data:
    fd = TODAY - timedelta(days=ds)
    td = TODAY - timedelta(days=de) if de > 0 else TODAY + timedelta(days=abs(de))
    if fd > td: fd, td = td, fd
    status = 'Approved' if fd < TODAY - timedelta(days=2) else 'Pending'
    ret = 'Returned' if td < TODAY else 'Not Returned'
    oth.append(
        "INSERT INTO leave_records (personnel_id,leave_type,from_date,to_date,"
        "approval_status,return_status,remarks,entered_by) VALUES ("
        + str(pid) + ",'" + ltype + "','" + fd.isoformat() + "','" + td.isoformat() + "',"
        "'" + status + "','" + ret + "','Leave approved as per entitlement',1);"
    )

oth += ["", "-- ── Course Records ─────────────────────────────"]
COURSES = [
    ('Section Commanders Course','ITC Belgaum'),('NBC Course','NBC School Baroda'),
    ('Signals Course','CME Pune'),('PT Instructor Course','ASPT Pune'),
    ('Combat Engineers Course','MCTE Mhow'),('Snipers Course','Infantry School Mhow'),
    ('MG Platoon Course','Infantry School Mhow'),('Driving Course','DEME Bhopal'),
    ('Medical Aid Course','AMC Centre'),('Leadership Course','OTA Chennai'),
]
for pid, cidx in [(3,0),(25,1),(46,2),(66,3),(82,4),(105,5),(102,6),(55,7),(70,8),(95,9)]:
    cname, cloc = COURSES[cidx]
    fd = TODAY - timedelta(days=si(5,20,str(pid)+'cf'))
    td = fd + timedelta(days=si(7,21,str(pid)+'ct'))
    oth.append(
        "INSERT INTO course_records (personnel_id,course_name,location,from_date,to_date,remarks,entered_by) "
        "VALUES (" + str(pid) + ",'" + cname + "','" + cloc + "',"
        "'" + fd.isoformat() + "','" + td.isoformat() + "','Nominated by CO',1);"
    )

oth += ["", "-- ── Duty Roster ─────────────────────────────────"]
for pid, dtype, loc in [
    (11,'Guard','Main Gate'),(8,'Sentry','Armoury'),(31,'Office Duty','Orderly Room'),
    (43,'QRT','Bn HQ'),(47,'Guard','Quarter Guard'),(51,'Sentry','Vehicle Park'),
    (81,'Office Duty','CO Office'),(87,'Guard','Main Gate'),
    (105,'Special Task','Bn HQ'),(116,'Office Duty','Orderly Room'),
]:
    dd = TODAY - timedelta(days=si(0,5,str(pid)+'dd'))
    oth.append(
        "INSERT INTO duty_roster (personnel_id,duty_type,duty_date,duty_time,location,remarks,entered_by) "
        "VALUES (" + str(pid) + ",'" + dtype + "','" + dd.isoformat() + "',"
        "'0600-1800 hrs','" + loc + "','Detailed as per routine order',1);"
    )

oth += ["", "-- ── Sick Reports ────────────────────────────────"]
for pid, scat in [(2,'Sick Report'),(12,'OPD'),(30,'Rest Advised'),(50,'Sick Report'),(99,'MH'),(119,'OPD')]:
    rd = TODAY - timedelta(days=si(0,7,str(pid)+'sr'))
    ed = rd + timedelta(days=si(3,7,str(pid)+'er'))
    oth.append(
        "INSERT INTO sick_reports (personnel_id,report_date,category,expected_return,remarks,entered_by) "
        "VALUES (" + str(pid) + ",'" + rd.isoformat() + "','" + scat + "',"
        "'" + ed.isoformat() + "','Reported to MI Room, fit certificate awaited',1);"
    )

oth += ["", "-- ── Manpower Shortages ──────────────────────────"]
for coy_id, cname, req, avl, pri in [
    (2,'A Coy',20,17,'Normal'),(3,'B Coy',20,19,'Normal'),(4,'C Coy',20,15,'Urgent'),
    (5,'D Coy',20,18,'Normal'),(6,'SP Coy',20,16,'Urgent'),(1,'HQ Coy',20,20,'Normal'),
]:
    for d in [7,3,1]:
        sd = TODAY - timedelta(days=d)
        oth.append(
            "INSERT INTO manpower_shortages (company_id,shortage_date,required_strength,"
            "available_strength,priority,remarks,marked_by) VALUES ("
            + str(coy_id) + ",'" + sd.isoformat() + "'," + str(req) + "," + str(avl) + ","
            "'" + pri + "','" + cname + " - routine strength report',2);"
        )

oth += ["", "SET foreign_key_checks=1;", ""]

with open('database/bn_seed_other.sql','w',encoding='utf-8') as f:
    f.write('\n'.join(oth))

print("Generated:")
print("  database/bn_seed_attendance.sql — " + str(len(rows)) + " attendance rows in batches of " + str(BATCH))
print("  database/bn_seed_other.sql      — leave, course, duty, sick, shortages")
