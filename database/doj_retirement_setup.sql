USE bn_parade_state_db;

-- ── 1. Add date_of_joining column ────────────────────────────────────────────
ALTER TABLE personnel
  ADD COLUMN IF NOT EXISTS date_of_joining DATE DEFAULT NULL AFTER date_of_enrolment;

-- ── 2. Assign trades to all existing personnel ────────────────────────────────
-- Start: everyone gets Rifleman
UPDATE personnel SET trade = 'Rifleman' WHERE trade IS NULL OR trade = '';

-- Signalman
UPDATE personnel SET trade = 'Signalman' WHERE id IN (108,6,24,64,80,90,94,13,33,47,76);

-- Armourer
UPDATE personnel SET trade = 'Armourer' WHERE id IN (116,110,44,56,93,88,39,51,7,35);

-- Cook
UPDATE personnel SET trade = 'Cook' WHERE id IN (109,12,34,50,77,89,25,30,45,71);

-- Pioneer
UPDATE personnel SET trade = 'Pioneer' WHERE id IN (119,3,21,70,91,99,52,60,75,83);

-- Clerk/SKT
UPDATE personnel SET trade = 'Clerk/SKT' WHERE id IN (113,82,97,15,36,55,68,86);

-- Safaiwala
UPDATE personnel SET trade = 'Safaiwala' WHERE id IN (115,23,38,54,84,95);

-- Infantry (Commissioned Officers)
UPDATE personnel SET trade = 'Infantry' WHERE rank_name = 'Capt';

-- ── 3. Set date_of_joining for all 120 personnel ─────────────────────────────
-- Legend for retirement dates (today = 2026-05-23):
--   Sep/Lnk: +17 yr → upcoming if joined 2009-05 to 2010-05
--   Nk:      +22 yr → upcoming if joined 2004-05 to 2005-05
--   Hav:     +24 yr → upcoming if joined 2002-05 to 2003-05
--   Capt:    +32 yr → upcoming if joined 1994-05 to 1995-05

UPDATE personnel SET date_of_joining = CASE id

  -- ── HQ Company ──────────────────────────────────────────────────────────────
  WHEN 105 THEN '1995-03-15'   -- Capt Kamarasu      → retires 2027-03-15
  WHEN 106 THEN '1993-06-20'   -- Capt Bisen          → retires 2025-06-20 (past)
  WHEN 103 THEN '2002-08-10'   -- Hav Patil Satish    → retires 2026-08-10 ★UPCOMING
  WHEN 108 THEN '2001-11-05'   -- Hav Suresh C        → retires 2025-11-05 (past)
  WHEN 113 THEN '2003-04-22'   -- Hav Patil Shushant  → retires 2027-04-22
  WHEN 116 THEN '2004-09-15'   -- Hav Patil Krishna   → retires 2028-09-15
  WHEN 118 THEN '2002-12-30'   -- Hav Patil Vipul     → retires 2026-12-30 ★UPCOMING
  WHEN 104 THEN '2010-06-15'   -- Lnk Ravi Rawool     → retires 2027-06-15
  WHEN 107 THEN '2009-08-20'   -- Lnk Prabhu M        → retires 2026-08-20 ★UPCOMING
  WHEN 111 THEN '2011-03-10'   -- Lnk Burse           → retires 2028-03-10
  WHEN 114 THEN '2012-07-05'   -- Lnk Patil Sunil     → retires 2029-07-05
  WHEN 117 THEN '2010-11-18'   -- Lnk Jadhav Kishor   → retires 2027-11-18
  WHEN 101 THEN '2004-05-25'   -- Nk Kendre Digambar  → retires 2026-05-25 ★UPCOMING (3 days!)
  WHEN 110 THEN '2003-09-14'   -- Nk JaBram Pawal     → retires 2025-09-14 (past)
  WHEN 112 THEN '2005-12-08'   -- Nk Abasaheb More    → retires 2027-12-08
  WHEN 120 THEN '2006-07-20'   -- Nk JaBesh Patil     → retires 2028-07-20
  WHEN 102 THEN '2012-04-15'   -- Sep Biswajit Singha → retires 2029-04-15
  WHEN 109 THEN '2011-09-28'   -- Sep Gite Hanumant   → retires 2028-09-28
  WHEN 115 THEN '2010-02-14'   -- Sep Gharge Aappaso  → retires 2027-02-14
  WHEN 119 THEN '2013-06-01'   -- Sep Bhagat          → retires 2030-06-01

  -- ── A Company ───────────────────────────────────────────────────────────────
  WHEN 4  THEN '2002-07-10'    -- Hav Patil Bharat    → retires 2026-07-10 ★UPCOMING
  WHEN 7  THEN '2003-02-28'    -- Hav More Sahebrao   → retires 2027-02-28
  WHEN 10 THEN '2001-05-15'    -- Hav Nitin Pisal     → retires 2025-05-15 (past)
  WHEN 13 THEN '2004-11-22'    -- Hav Tagad Kailash   → retires 2028-11-22
  WHEN 2  THEN '2009-10-05'    -- Lnk Agre SanjaB     → retires 2026-10-05 ★UPCOMING
  WHEN 6  THEN '2010-03-18'    -- Lnk Salam Vaiju     → retires 2027-03-18
  WHEN 8  THEN '2011-08-14'    -- Lnk Mohite Kailash  → retires 2028-08-14
  WHEN 17 THEN '2009-06-28'    -- Lnk Patil Nikhil    → retires 2026-06-28 ★UPCOMING
  WHEN 19 THEN '2012-01-10'    -- Lnk Shinde Amol     → retires 2029-01-10
  WHEN 20 THEN '2013-05-20'    -- Lnk Nagare Pravin   → retires 2030-05-20
  WHEN 1  THEN '2004-09-30'    -- Nk Krishan Badav    → retires 2026-09-30 ★UPCOMING
  WHEN 5  THEN '2003-04-12'    -- Nk Shinde Sharad    → retires 2025-04-12 (past)
  WHEN 11 THEN '2005-07-25'    -- Nk Mule Sarjerao    → retires 2027-07-25
  WHEN 14 THEN '2006-11-08'    -- Nk Maind Hanumant   → retires 2028-11-08
  WHEN 15 THEN '2007-02-14'    -- Nk Powar            → retires 2029-02-14
  WHEN 16 THEN '2005-10-30'    -- Nk Ghadge Amish     → retires 2027-10-30
  WHEN 18 THEN '2004-12-22'    -- Nk Badav Sunil      → retires 2026-12-22 ★UPCOMING
  WHEN 3  THEN '2011-05-10'    -- Sep Wabale Santosh  → retires 2028-05-10
  WHEN 9  THEN '2012-09-25'    -- Sep Karade Dhula    → retires 2029-09-25
  WHEN 12 THEN '2010-11-15'    -- Sep Vetal Somnath   → retires 2027-11-15

  -- ── B Company ───────────────────────────────────────────────────────────────
  WHEN 28 THEN '2003-08-20'    -- Hav Pawar Atul      → retires 2027-08-20
  WHEN 31 THEN '2002-03-15'    -- Hav Patil Datt      → retires 2026-03-15 (past)
  WHEN 35 THEN '2002-10-28'    -- Hav More Umesh      → retires 2026-10-28 ★UPCOMING
  WHEN 39 THEN '2004-06-05'    -- Hav Nikam Sagar     → retires 2028-06-05
  WHEN 22 THEN '2009-12-10'    -- Lnk Kapadi          → retires 2026-12-10 ★UPCOMING
  WHEN 24 THEN '2010-07-22'    -- Lnk Kolekar VijaB   → retires 2027-07-22
  WHEN 29 THEN '2008-04-15'    -- Lnk Suprith Rao     → retires 2025-04-15 (past)
  WHEN 33 THEN '2011-09-05'    -- Lnk Singanjude      → retires 2028-09-05
  WHEN 37 THEN '2012-02-18'    -- Lnk Lature Ram      → retires 2029-02-18
  WHEN 26 THEN '2005-05-30'    -- Nk Vinod Shere      → retires 2027-05-30
  WHEN 27 THEN '2004-01-14'    -- Nk Sutar Shankar    → retires 2026-01-14 (past)
  WHEN 32 THEN '2005-08-22'    -- Nk Paste Sitaram    → retires 2027-08-22
  WHEN 36 THEN '2006-11-10'    -- Nk Todmal Vaibhav   → retires 2028-11-10
  WHEN 40 THEN '2004-11-28'    -- Nk Ranjan Sinha     → retires 2026-11-28 ★UPCOMING
  WHEN 21 THEN '2012-07-04'    -- Sep Gurulingappa    → retires 2029-07-04
  WHEN 23 THEN '2011-01-20'    -- Sep Sawant Milind   → retires 2028-01-20
  WHEN 25 THEN '2010-09-14'    -- Sep Jamkar Rakesh   → retires 2027-09-14
  WHEN 30 THEN '2013-03-08'    -- Sep Akhare Atul     → retires 2030-03-08
  WHEN 34 THEN '2009-11-05'    -- Sep Hemane Vinod    → retires 2026-11-05 ★UPCOMING
  WHEN 38 THEN '2014-06-15'    -- Sep Bhushan Ahire   → retires 2031-06-15

  -- ── C Company ───────────────────────────────────────────────────────────────
  WHEN 43 THEN '2003-06-18'    -- Hav Chavan Gajanan  → retires 2027-06-18
  WHEN 47 THEN '2002-01-25'    -- Hav Gurunale        → retires 2026-01-25 (past)
  WHEN 49 THEN '2002-08-14'    -- Hav Gaike Rahul     → retires 2026-08-14 ★UPCOMING
  WHEN 51 THEN '2004-03-30'    -- Hav Lavate Santosh  → retires 2028-03-30
  WHEN 57 THEN '2003-10-22'    -- Hav Ghadage         → retires 2027-10-22
  WHEN 41 THEN '2009-05-15'    -- Lnk Virkar Satish   → retires 2026-05-15 (past)
  WHEN 48 THEN '2010-12-08'    -- Lnk Shailesh Gahane → retires 2027-12-08
  WHEN 53 THEN '2012-04-20'    -- Lnk Bhosale Sangam  → retires 2029-04-20
  WHEN 58 THEN '2009-07-28'    -- Lnk Jadhav Pratik   → retires 2026-07-28 ★UPCOMING
  WHEN 60 THEN '2011-01-05'    -- Lnk Bewale Popat    → retires 2028-01-05
  WHEN 44 THEN '2004-08-18'    -- Nk Patil Sharad     → retires 2026-08-18 ★UPCOMING
  WHEN 56 THEN '2006-03-12'    -- Nk Gite Gangaram    → retires 2028-03-12
  WHEN 59 THEN '2007-09-25'    -- Nk Raskar Vikas     → retires 2029-09-25
  WHEN 42 THEN '2012-02-28'    -- Sep Hukke Santosh   → retires 2029-02-28
  WHEN 45 THEN '2010-08-10'    -- Sep Javanjal Vishal → retires 2027-08-10
  WHEN 46 THEN '2011-04-15'    -- Sep Dhane Rahul     → retires 2028-04-15
  WHEN 50 THEN '2013-11-20'    -- Sep Buvaraj         → retires 2030-11-20
  WHEN 52 THEN '2012-07-08'    -- Sep Shenavi Atul    → retires 2029-07-08
  WHEN 54 THEN '2009-12-25'    -- Sep Patil Santosh   → retires 2026-12-25 ★UPCOMING
  WHEN 55 THEN '2014-03-18'    -- Sep Agashe Akhil    → retires 2031-03-18

  -- ── D Company ───────────────────────────────────────────────────────────────
  WHEN 61 THEN '2003-05-20'    -- Hav Rane Santosh    → retires 2027-05-20
  WHEN 67 THEN '2002-09-12'    -- Hav Patil Rohan     → retires 2026-09-12 ★UPCOMING
  WHEN 72 THEN '2001-07-30'    -- Hav Ashoka V        → retires 2025-07-30 (past)
  WHEN 74 THEN '2002-11-15'    -- Hav VijaB Mohite    → retires 2026-11-15 ★UPCOMING
  WHEN 78 THEN '2004-02-25'    -- Hav Dhaitidak       → retires 2028-02-25
  WHEN 62 THEN '2010-04-08'    -- Lnk Mundhe Ganesh   → retires 2027-04-08
  WHEN 64 THEN '2009-09-22'    -- Lnk Phad Mahadev    → retires 2026-09-22 ★UPCOMING
  WHEN 66 THEN '2011-06-14'    -- Lnk Darunte Niraj   → retires 2028-06-14
  WHEN 71 THEN '2012-10-30'    -- Lnk Munde AkshaB    → retires 2029-10-30
  WHEN 73 THEN '2009-03-05'    -- Lnk Sarvesh Singh   → retires 2026-03-05 (past)
  WHEN 75 THEN '2013-08-18'    -- Lnk Bhosale Krishna → retires 2030-08-18
  WHEN 63 THEN '2004-06-28'    -- Nk Shipekar Santosh → retires 2026-06-28 ★UPCOMING
  WHEN 65 THEN '2005-11-10'    -- Nk Patil Suraj      → retires 2027-11-10
  WHEN 68 THEN '2003-12-15'    -- Nk Dhage Sagar      → retires 2025-12-15 (past)
  WHEN 76 THEN '2006-04-22'    -- Nk Patil Bogesh     → retires 2028-04-22
  WHEN 79 THEN '2007-08-09'    -- Nk Umesh Patil      → retires 2029-08-09
  WHEN 80 THEN '2005-03-18'    -- Nk Talwar Prabhakar → retires 2027-03-18
  WHEN 69 THEN '2011-12-05'    -- Sep Patil Pravin    → retires 2028-12-05
  WHEN 70 THEN '2010-07-20'    -- Sep Kodape Ashish   → retires 2027-07-20
  WHEN 77 THEN '2012-05-14'    -- Sep Sachin B.       → retires 2029-05-14
  WHEN 122 THEN '2009-10-28'   -- Sep Rajendra Singh  → retires 2026-10-28 ★UPCOMING

  -- ── SP Company ──────────────────────────────────────────────────────────────
  WHEN 81 THEN '2002-07-05'    -- Hav Thange Babasaheb → retires 2026-07-05 ★UPCOMING
  WHEN 85 THEN '2003-11-28'    -- Hav Sadashiv        → retires 2027-11-28
  WHEN 87 THEN '2002-04-18'    -- Hav Patil Parasharam → retires 2026-04-18 (past)
  WHEN 92 THEN '2004-08-10'    -- Hav Walung Sandeep  → retires 2028-08-10
  WHEN 96 THEN '2003-02-22'    -- Hav Diwate Dipak    → retires 2027-02-22
  WHEN 100 THEN '2005-06-15'   -- Hav Sonawane Ganesh → retires 2029-06-15
  WHEN 83 THEN '2010-01-25'    -- Lnk Mane Tanaji     → retires 2027-01-25
  WHEN 90 THEN '2009-11-08'    -- Lnk Kare Aba        → retires 2026-11-08 ★UPCOMING
  WHEN 94 THEN '2011-04-20'    -- Lnk Feran Nilesh    → retires 2028-04-20
  WHEN 98 THEN '2010-08-15'    -- Lnk Gaikwad Satish  → retires 2027-08-15
  WHEN 82 THEN '2005-09-22'    -- Nk Sopan Sargar     → retires 2027-09-22
  WHEN 86 THEN '2004-03-14'    -- Nk Nidheesh K       → retires 2026-03-14 (past)
  WHEN 88 THEN '2005-12-28'    -- Nk Sutar Dhaklesh   → retires 2027-12-28
  WHEN 93 THEN '2006-07-04'    -- Nk Lokhande Sachin  → retires 2028-07-04
  WHEN 97 THEN '2004-10-30'    -- Nk Gawade           → retires 2026-10-30 ★UPCOMING
  WHEN 84 THEN '2010-06-18'    -- Sep Sabale Tejpal   → retires 2027-06-18
  WHEN 89 THEN '2012-11-08'    -- Sep Haral Nilesh    → retires 2029-11-08
  WHEN 91 THEN '2011-02-20'    -- Sep Nanaji          → retires 2028-02-20
  WHEN 95 THEN '2009-08-14'    -- Sep Molke Sandeep   → retires 2026-08-14 ★UPCOMING
  WHEN 99 THEN '2013-04-25'    -- Sep Kshirsagar      → retires 2030-04-25

  ELSE date_of_joining
END;
