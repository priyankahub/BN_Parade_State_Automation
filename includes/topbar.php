<?php
$_tp   = $_tp ?? '';
$_role = $_SESSION['role'] ?? '';
$_roleH = htmlspecialchars($_role, ENT_QUOTES, 'UTF-8');
$_self  = $_SERVER['PHP_SELF'] ?? '';

if (!function_exists('_navActive')) {
    function _navActive(string $fragment): string {
        global $_self;
        return strpos($_self, $fragment) !== false ? ' active' : '';
    }
}

// Role-based nav items: [fragment-to-match, href-relative-to-tp, label, svg-path]
$_navSets = [
    'ADMIN' => [
        ['dashboard',               'dashboard.php',                        'Home',         '<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>'],
        ['manage_soldiers',         'admin/manage_soldiers.php',            'Nominal Roll', '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>'],
        ['agniveer_list',           'agniveer_list.php',                    'Agniveer List','<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>'],
        ['upcoming_retirement',     'upcoming_retirement.php',              'Retirements',  '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
        ['company_master',          'admin/company_master.php',             'Companies',    '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>'],
        ['manage_users',            'admin/manage_users.php',               'Users',        '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>'],
        ['approve_',                'admin/approve_attendance.php',         'Approvals',    '<polyline points="20 6 9 17 4 12"/>'],
        ['battalion_parade_state',  'adjt_sa/battalion_parade_state.php',   'Parade State', '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>'],
    ],
    'ADJT_SA' => [
        ['dashboard',               'dashboard.php',                        'Home',         '<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>'],
        ['manage_soldiers',         'admin/manage_soldiers.php',            'Nominal Roll', '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>'],
        ['agniveer_list',           'agniveer_list.php',                    'Agniveer List','<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>'],
        ['upcoming_retirement',     'upcoming_retirement.php',              'Retirements',  '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
        ['battalion_parade_state',  'adjt_sa/battalion_parade_state.php',   'Parade State', '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/>'],
        ['approve_parade_state',    'adjt_sa/approve_parade_state.php',     'Approvals',    '<polyline points="20 6 9 17 4 12"/>'],
        ['duty_overview',           'adjt_sa/duty_overview.php',            'Duty',         '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>'],
        ['manpower_shortages',      'adjt_sa/manpower_shortages.php',       'Shortages',    '<path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>'],
    ],
    'CHM_CLERK' => [
        ['dashboard',        'dashboard.php',                 'Home',        '<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>'],
        ['manage_soldiers',  'admin/manage_soldiers.php',     'Nominal Roll','<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>'],
        ['agniveer_list',        'agniveer_list.php',         'Agniveer List','<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>'],
        ['upcoming_retirement',  'upcoming_retirement.php',  'Retirements', '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
        ['daily_attendance',     'clerk/daily_attendance.php','Attendance',  '<polyline points="20 6 9 17 4 12"/>'],
        ['leave_entry',      'clerk/leave_entry.php',         'Leave',       '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>'],
        ['duty_roster',      'clerk/duty_roster.php',         'Duty',        '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>'],
        ['course_entry',     'clerk/course_entry.php',        'Course',      '<path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/>'],
        ['sick_report',      'clerk/sick_report.php',         'Sick',        '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>'],
        ['company_strength', 'clerk/company_strength.php',    'Strength',    '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>'],
        ['my_activity',      'clerk/my_activity.php',         'My Activity', '<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/>'],
    ],
    'USER' => [
        ['dashboard',         'dashboard.php',               'Home',         '<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>'],
        ['agniveer_list',       'agniveer_list.php',          'Agniveer List','<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>'],
        ['upcoming_retirement', 'upcoming_retirement.php',   'Retirements', '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
        ['view_parade_state',   'user/view_parade_state.php','Parade State', '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>'],
        ['view_reports',      'user/view_reports.php',       'Reports',      '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>'],
    ],
];

$_items = $_navSets[$_role] ?? [];
?>
<div class="topbar" role="banner">
    <a href="<?php echo $_tp; ?>dashboard.php" class="brand" aria-label="BN Parade State Portal — Go to Dashboard">
        <svg class="brand-emblem" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M20 2L4 10V22C4 30.5 11.2 37.6 20 39C28.8 37.6 36 30.5 36 22V10L20 2Z" fill="rgba(201,162,39,0.15)" stroke="#c9a227" stroke-width="1.5"/>
            <path d="M20 8L10 13V21C10 26.5 14.4 31.3 20 32.5C25.6 31.3 30 26.5 30 21V13L20 8Z" fill="rgba(201,162,39,0.1)" stroke="#c9a227" stroke-width="1"/>
            <circle cx="20" cy="20" r="5" fill="#c9a227" opacity="0.7"/>
            <path d="M20 14V26M14 20H26" stroke="#c9a227" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <div class="brand-text">
            <span class="brand-title">BN Parade State</span>
            <span class="brand-sub">Infantry School · Mhow</span>
        </div>
    </a>
    <div class="nav-actions">
        <span class="badge" data-role="<?php echo $_roleH; ?>"><?php echo $_roleH; ?></span>
        <a class="btn secondary" href="<?php echo $_tp; ?>profile.php">Profile</a>
        <a class="btn secondary" href="<?php echo $_tp; ?>logout.php">Logout</a>
    </div>
</div>

<?php if (!empty($_items)): ?>
<nav class="sitenav" role="navigation" aria-label="Main navigation">
    <?php foreach ($_items as $_item): ?>
        <a href="<?php echo $_tp . $_item[1]; ?>"
           class="sitenav-item<?php echo _navActive($_item[0]); ?>"
           <?php echo _navActive($_item[0]) ? 'aria-current="page"' : ''; ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <?php echo $_item[3]; ?>
            </svg>
            <span><?php echo htmlspecialchars($_item[2], ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
    <?php endforeach; ?>
</nav>
<?php endif; ?>
