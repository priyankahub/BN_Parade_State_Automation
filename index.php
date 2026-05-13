<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("includes/head_meta.php"); ?>
    <title>BN Parade State Automation Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="homepage">

<!-- ================= HEADER ================= -->
<div class="header-banner">
    <img src="images/bannerpic.png" class="banner-img" alt="Infantry School banner">
    <div class="banner-overlay"></div>

    <div class="banner-content">
        <img src="images/logo.jpg" class="logo" alt="Infantry School crest">
        <h1>THE INFANTRY SCHOOL MHOW</h1>
        <img src="images/logo.jpg" class="logo" alt="Infantry School crest">
    </div>
</div>

<!-- ================= MOVING RIBBON ================= -->
<div class="news-ticker">
    <div class="ticker-track">
        Daily Parade State Automation Portal &nbsp;|&nbsp;
        Nominal Roll Analytics Dashboard Operational &nbsp;|&nbsp;
        Leave, Course &amp; Duty Reporting Module Enabled &nbsp;|&nbsp;
        Security-Question Based Password Reset Active &nbsp;|&nbsp;
        Scheduled System Maintenance: Sunday 02:00 hrs &nbsp;|&nbsp;
        Welcome to the Battalion Parade State Automation Portal
    </div>
</div>

<!-- ================= NAVBAR ================= -->
<div class="navbar">

    <!-- Home -->
    <a href="index.php" class="home-icon">Home</a>

    <!-- About Us -->
    <div class="dropdown">
        <button class="dropbtn">About Us</button>
        <div class="dropdown-content">
            <a href="https://indianarmy.nic.in/KnowYourArmy/know-your-army-main/history" target="_blank">History</a>
            <a href="https://nda.nic.in/site-page-viewer/21" target="_blank">Mission &amp; Vision</a>
            <a href="https://indianarmy.nic.in/leaders/leaders-site-main/chief-of-the-army-staff-leaders-site-main" target="_blank">Leadership</a>
        </div>
    </div>

    <!-- Know Your Army -->
    <div class="dropdown">
        <button class="dropbtn">Know Your Army</button>
        <div class="dropdown-content">
            <a href="https://indianarmy.nic.in/KnowYourArmy/know-your-army-main/combat-edge" target="_blank">Combat Edge</a>
            <a href="https://indianarmy.nic.in/KnowYourArmy/know-your-army-main/command-and-control" target="_blank">Command &amp; Control</a>
            <a href="https://indianarmy.nic.in/KnowYourArmy/know-your-army-main/leadership" target="_blank">Leadership</a>
            <a href="https://indianarmy.nic.in/KnowYourArmy/know-your-army-main/operations-un-mission" target="_blank">Operations &amp; UN Missions</a>
            <a href="https://indianarmy.nic.in/KnowYourArmy/know-your-army-main/afspa" target="_blank">AFSPA</a>
        </div>
    </div>

    <!-- Gallery -->
    <div class="dropdown">
        <button class="dropbtn">Gallery</button>
        <div class="dropdown-content">
            <a href="https://indianarmy.nic.in/Media/" target="_blank">Photos</a>
            <a href="https://indianarmy.nic.in/Media/Videos" target="_blank">Videos</a>
        </div>
    </div>

    <!-- External Links -->
    <div class="dropdown">
        <button class="dropbtn">External Links</button>
        <div class="dropdown-content">
            <a href="https://www.mod.gov.in/" target="_blank">Ministry of Defence</a>
            <a href="https://indianarmy.nic.in/Home/Index" target="_blank">Indian Army</a>
            <a href="https://indiannavy.nic.in/" target="_blank">Indian Navy</a>
            <a href="https://indianairforce.nic.in/" target="_blank">Indian Air Force</a>
        </div>
    </div>

    <!-- Contact Us -->
    <a href="https://joinindianarmy.nic.in/contact-us.htm" target="_blank">Contact Us</a>

    <!-- LOGIN + SIGNUP -->
    <div class="nav-right">
        <a href="auth/login.php" class="login-btn">Login</a>
        <a href="auth/register.php" class="signup-btn">Sign Up</a>
    </div>

</div>

<!-- ================= MAIN SECTION ================= -->
<div class="main-section">

    <!-- LEFT SIDE (Slider + Commands stacked) -->
    <div class="left-panel">

        <div class="slider-container">
            <div class="slider">
                <img src="images/infantry1.jpg" class="slide active" alt="Infantry parade">
                <img src="images/infantry2.jpg" class="slide" alt="Infantry training">
                <img src="images/infantry3.jpg" class="slide" alt="Infantry field activity">

                <button class="prev" type="button" onclick="moveSlide(-1)">&#10094;</button>
                <button class="next" type="button" onclick="moveSlide(1)">&#10095;</button>
            </div>
        </div>

        <!-- ================= INDIAN ARMY COMMAND PANEL ================= -->
        <div class="commands-panel">
            <h3>Indian Army Commands</h3>

            <div class="commands-row">

                <a href="https://indianarmy.nic.in/command/command/western-command-commands-site-main" target="_blank" class="command-item">
                    <img src="images/Western.png" alt="Western Command">
                    <span>Western</span>
                </a>

                <a href="https://indianarmy.nic.in/command/command/southern-command-commands-site-main" target="_blank" class="command-item">
                    <img src="images/Southern.png" alt="Southern Command">
                    <span>Southern</span>
                </a>

                <a href="https://indianarmy.nic.in/command/command/northern-command-commands-site-main" target="_blank" class="command-item">
                    <img src="images/Northen.png" alt="Northern Command">
                    <span>Northern</span>
                </a>

                <a href="https://indianarmy.nic.in/command/command/eastern-command-commands-site-main" target="_blank" class="command-item">
                    <img src="images/Eastern.png" alt="Eastern Command">
                    <span>Eastern</span>
                </a>

                <a href="https://indianarmy.nic.in/command/command/central-command-commands-site-main" target="_blank" class="command-item">
                    <img src="images/Central.png" alt="Central Command">
                    <span>Central</span>
                </a>

                <a href="https://indianarmy.nic.in/command/command/artrac-commands-site-main" target="_blank" class="command-item">
                    <img src="images/Arctrac.png" alt="ARTRAC">
                    <span>ARTRAC</span>
                </a>

                <a href="https://indianarmy.nic.in/command/command/south-western-command-commands-site-main" target="_blank" class="command-item">
                    <img src="images/Southern-Western.png" alt="South Western Command">
                    <span>South Western</span>
                </a>

            </div>
        </div>

    </div>

    <!-- RIGHT SIDE PANEL -->
    <div class="right-panel">

        <div class="info-card">
            <h3>Latest News</h3>
            <div class="news-scroll">
                <ul>
                    <li>Battalion Parade State Automation Portal launched for all companies.</li>
                    <li>Nominal Roll analytics dashboard now available to authorised users.</li>
                    <li>Daily company-wise parade state reports operational.</li>
                    <li>Leave, Course and Duty (LCD) reporting module enabled.</li>
                    <li>Security-question based password reset active for all accounts.</li>
                    <li>Annual Tactical Training Exercise concluded successfully.</li>
                    <li>Young Officers Leadership Capsule commenced at Infantry School.</li>
                    <li>Joint Indo-Foreign Military Exercise completed.</li>
                    <li>Army Day Parade rehearsals progressing as scheduled.</li>
                    <li>Modern Warfare Simulation Wing inaugurated at Mhow.</li>
                </ul>
            </div>
        </div>

        <div class="info-card">
            <h3>Notices / Circulars</h3>
            <div class="notice-scroll">
                <ul>
                    <li>Company clerks to update nominal roll data before weekly report generation.</li>
                    <li>Daily attendance, leave and duty entries to be verified before 1800 hrs.</li>
                    <li>All users to set a security question via the Profile page.</li>
                    <li>Official sharing of reports to be done via portal-generated PDF and Excel only.</li>
                    <li>Discrepancies in parade state to be reported to Adjutant immediately.</li>
                    <li>Scheduled system maintenance: Sunday 02:00 hrs to 03:00 hrs.</li>
                    <li>Cyber-security awareness training mandatory for all users this quarter.</li>
                    <li>Quarterly audit documentation submission due by month-end.</li>
                    <li>Periodic password reset advisory issued for all account holders.</li>
                    <li>Data backup validation exercise to commence next week.</li>
                </ul>
            </div>
        </div>

        <div class="info-card">
            <h3>Useful Links</h3>
            <ul>
                <li><a href="https://indianarmy.nic.in/" target="_blank">Indian Army Official Website</a></li>
                <li><a href="https://www.rashtriyamilitaryschools.edu.in/" target="_blank">Rashtriya Military School</a></li>
                <li><a href="https://indianarmy.nic.in/Training/training-site-main/training-teams" target="_blank">Training &amp; Doctrine</a></li>
                <li><a href="https://indianarmy.nic.in/honours/honours-awards-site-main/honorary-commission" target="_blank">Honorary Commissions</a></li>
            </ul>
        </div>

    </div>

</div>

<div class="footer">
    &copy; 2026 THE INFANTRY SCHOOL MHOW &nbsp;|&nbsp; Battalion Parade State Automation Portal
</div>

<script src="assets/js/script.js"></script>
<script src="assets/js/slider.js"></script>

</body>
</html>
