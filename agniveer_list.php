<?php
session_start();
if (!isset($_SESSION["role"])) {
    header("Location: auth/login.php"); exit;
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

include("config/db.php");

// Auto-create agniveers table if it doesn't exist on this installation
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS agniveers (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        army_no        VARCHAR(50) NOT NULL UNIQUE,
        full_name      VARCHAR(120) NOT NULL,
        company_id     INT NOT NULL,
        trade          ENUM('GD','Tech','Clerk','Tradesman') DEFAULT 'GD',
        dob            DATE DEFAULT NULL,
        enrolment_date DATE DEFAULT NULL,
        home_state     VARCHAR(80) DEFAULT NULL,
        blood_group    VARCHAR(10) DEFAULT NULL,
        mobile_no      VARCHAR(15) DEFAULT NULL,
        status         ENUM('Active','Transferred','Discharged') DEFAULT 'Active',
        created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES companies(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$role     = $_SESSION["role"];
$isClerk  = ($role === "CHM_CLERK");
$clerkCid = (int)($_SESSION["company_id"] ?? 0);

// ── Filters ──────────────────────────────────────────────────────────────────
$search       = trim($_GET['q'] ?? '');
$filterTrade  = $_GET['trade']  ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterCid    = $isClerk ? $clerkCid : (int)($_GET['company_id'] ?? 0);

// ── Build query with prepared statement ──────────────────────────────────────
$conditions = [];
$params     = [];
$types      = '';

if ($isClerk) {
    $conditions[] = "a.company_id = ?";
    $params[]     = $clerkCid;
    $types       .= 'i';
} elseif ($filterCid > 0) {
    $conditions[] = "a.company_id = ?";
    $params[]     = $filterCid;
    $types       .= 'i';
}

if ($search !== '') {
    $conditions[] = "(a.full_name LIKE ? OR a.army_no LIKE ?)";
    $like         = "%$search%";
    $params[]     = $like;
    $params[]     = $like;
    $types       .= 'ss';
}

if ($filterTrade !== '') {
    $conditions[] = "a.trade = ?";
    $params[]     = $filterTrade;
    $types       .= 's';
}

if ($filterStatus !== '') {
    $conditions[] = "a.status = ?";
    $params[]     = $filterStatus;
    $types       .= 's';
}

$whereSQL = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$today = date('Y-m-d');

$sql  = "SELECT a.*,
                c.company_name,
                DATE_ADD(a.enrolment_date, INTERVAL 4 YEAR) AS tenure_end_date,
                DATEDIFF(DATE_ADD(a.enrolment_date, INTERVAL 4 YEAR), CURDATE()) AS days_remaining
         FROM agniveers a
         JOIN companies c ON c.id = a.company_id
         $whereSQL
         ORDER BY c.company_name, a.full_name";

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$rows  = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
$total = count($rows);

// ── Sidebar data ─────────────────────────────────────────────────────────────
$companies = mysqli_fetch_all(mysqli_query($conn,
    "SELECT id, company_name FROM companies WHERE is_active = 1 ORDER BY company_name"), MYSQLI_ASSOC);

$clerkCoyName = '';
if ($isClerk && $clerkCid) {
    $r = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT company_name FROM companies WHERE id = $clerkCid LIMIT 1"));
    $clerkCoyName = $r['company_name'] ?? '';
}

// ── Summary counts (for stat bar) ────────────────────────────────────────────
$statSql  = "SELECT trade, COUNT(*) AS cnt FROM agniveers a $whereSQL GROUP BY trade";
$statStmt = mysqli_prepare($conn, $statSql);
if ($params) {
    mysqli_stmt_bind_param($statStmt, $types, ...$params);
}
mysqli_stmt_execute($statStmt);
$tradeStats = [];
foreach (mysqli_fetch_all(mysqli_stmt_get_result($statStmt), MYSQLI_ASSOC) as $r) {
    $tradeStats[$r['trade']] = (int)$r['cnt'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("includes/head_meta.php"); ?>
    <title>Agniveer List | BN Parade State Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .agn-stat-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 22px;
        }
        .agn-stat {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--card-bg, rgba(255,255,255,.04));
            border: 1px solid var(--border, rgba(255,255,255,.08));
            border-radius: 10px;
            padding: 10px 18px;
            min-width: 130px;
        }
        .agn-stat-num  { font-size: 22px; font-weight: 700; font-family: 'Fira Code', monospace; }
        .agn-stat-label{ font-size: 12px; color: var(--muted, #8fa8c6); text-transform: uppercase; letter-spacing: .05em; }
        .agn-total     { border-color: var(--gold, #c9a227); }
        .agn-total .agn-stat-num { color: var(--gold, #c9a227); }
        .agn-gd        .agn-stat-num { color: var(--gold, #c9a227); }
        .agn-tech      .agn-stat-num { color: var(--status-present, #22863a); }
        .agn-clerk     .agn-stat-num { color: #60a5fa; }
        .agn-trades    .agn-stat-num { color: #c084fc; }

        .trade-gd       { background:rgba(201,162,39,.15);  color: var(--gold,#c9a227); }
        .trade-tech     { background:rgba(34,134,58,.15);   color: var(--status-present,#22863a); }
        .trade-clerk    { background:rgba(59,130,246,.15);  color: #60a5fa; }
        .trade-tradesman{ background:rgba(168,85,247,.15);  color: #c084fc; }

        .status-active      { background:rgba(34,134,58,.15);  color: var(--status-present,#22863a); }
        .status-transferred { background:rgba(234,179,8,.15);  color: #ca8a04; }
        .status-discharged  { background:rgba(239,68,68,.15);  color: var(--status-absent,#d73a49); }

        .tenure-expired  { background:rgba(239,68,68,.15);  color: var(--status-absent,#d73a49); }
        .tenure-critical { background:rgba(239,68,68,.12);  color: #f87171; }
        .tenure-soon     { background:rgba(234,179,8,.15);  color: #d97706; }
        .tenure-ok       { background:rgba(34,134,58,.12);  color: var(--status-present,#22863a); }
        .tenure-wrap { display:flex; flex-direction:column; gap:3px; }
        .tenure-date { font-size:13px; font-weight:600; }
        .tenure-tag  { display:inline-block; padding:1px 7px; border-radius:4px; font-size:10px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; }

        .agniveer-icon { color: var(--gold,#c9a227); vertical-align:-4px; margin-right:8px; }
        .table-wrap table td code { font-family:'Fira Code',monospace; font-size:12px; opacity:.9; }
    </style>
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php $_tp = ''; include("includes/topbar.php"); ?>

<main id="main-content" class="container">
<div class="panel">

    <!-- Page header -->
    <div class="page-header">
        <div>
            <h1>
                <svg class="agniveer-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22" aria-hidden="true">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
                Agniveer List<?php if ($isClerk && $clerkCoyName): ?> &mdash; <?php echo h($clerkCoyName); ?><?php endif; ?>
            </h1>
            <p class="page-sub">
                <?php if ($isClerk): ?>
                    Showing Agniveers of <?php echo h($clerkCoyName); ?>
                <?php else: ?>
                    Battalion-wide Agniveer personnel register
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Trade summary bar -->
    <div class="agn-stat-bar">
        <div class="agn-stat agn-total">
            <div>
                <div class="agn-stat-num"><?php echo $total; ?></div>
                <div class="agn-stat-label">Total</div>
            </div>
        </div>
        <div class="agn-stat agn-gd">
            <div>
                <div class="agn-stat-num"><?php echo $tradeStats['GD'] ?? 0; ?></div>
                <div class="agn-stat-label">GD</div>
            </div>
        </div>
        <div class="agn-stat agn-tech">
            <div>
                <div class="agn-stat-num"><?php echo $tradeStats['Tech'] ?? 0; ?></div>
                <div class="agn-stat-label">Tech</div>
            </div>
        </div>
        <div class="agn-stat agn-clerk">
            <div>
                <div class="agn-stat-num"><?php echo $tradeStats['Clerk'] ?? 0; ?></div>
                <div class="agn-stat-label">Clerk</div>
            </div>
        </div>
        <div class="agn-stat agn-trades">
            <div>
                <div class="agn-stat-num"><?php echo $tradeStats['Tradesman'] ?? 0; ?></div>
                <div class="agn-stat-label">Tradesman</div>
            </div>
        </div>
    </div>

    <!-- Filter form -->
    <form method="GET" class="form-grid filters" style="margin-bottom:20px;">
        <div class="form-group">
            <label for="q">Search</label>
            <input id="q" type="text" name="q"
                   value="<?php echo h($search); ?>"
                   placeholder="Name or Army No…"
                   class="form-control">
        </div>

        <?php if (!$isClerk): ?>
        <div class="form-group">
            <label for="company_id">Company</label>
            <select id="company_id" name="company_id" class="form-control">
                <option value="">All Companies</option>
                <?php foreach ($companies as $c): ?>
                    <option value="<?php echo (int)$c['id']; ?>"
                        <?php echo $filterCid == $c['id'] ? 'selected' : ''; ?>>
                        <?php echo h($c['company_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="trade">Trade</label>
            <select id="trade" name="trade" class="form-control">
                <option value="">All Trades</option>
                <?php foreach (['GD', 'Tech', 'Clerk', 'Tradesman'] as $t): ?>
                    <option value="<?php echo $t; ?>"
                        <?php echo $filterTrade === $t ? 'selected' : ''; ?>>
                        <?php echo $t; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status" class="form-control">
                <option value="">All Status</option>
                <?php foreach (['Active', 'Transferred', 'Discharged'] as $s): ?>
                    <option value="<?php echo $s; ?>"
                        <?php echo $filterStatus === $s ? 'selected' : ''; ?>>
                        <?php echo $s; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="align-self:flex-end;display:flex;gap:8px;">
            <button type="submit" class="btn primary">Filter</button>
            <a href="agniveer_list.php" class="btn secondary">Reset</a>
        </div>
    </form>

    <!-- Results -->
    <?php if (empty($rows)): ?>
        <p style="text-align:center;padding:40px 0;color:var(--muted,#8fa8c6);">No Agniveer records match the current filter.</p>
    <?php else: ?>

    <div style="margin-bottom:10px;font-size:13px;color:var(--muted,#8fa8c6);">
        Showing <strong style="color:var(--text)"><?php echo $total; ?></strong> record<?php echo $total !== 1 ? 's' : ''; ?>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Army No</th>
                    <th>Name</th>
                    <?php if (!$isClerk): ?><th>Company</th><?php endif; ?>
                    <th>Trade</th>
                    <th>DOB</th>
                    <th>Enrolment Date</th>
                    <th>Tenure End Date</th>
                    <th>Home State</th>
                    <th>Blood Gp</th>
                    <th>Mobile</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $i => $row):
                    $tradeClass = match($row['trade']) {
                        'GD'        => 'trade-gd',
                        'Tech'      => 'trade-tech',
                        'Clerk'     => 'trade-clerk',
                        'Tradesman' => 'trade-tradesman',
                        default     => ''
                    };
                    $statusClass = match($row['status']) {
                        'Active'      => 'status-active',
                        'Transferred' => 'status-transferred',
                        'Discharged'  => 'status-discharged',
                        default       => ''
                    };
                ?>
                <tr>
                    <td style="color:var(--muted);font-size:12px;"><?php echo $i + 1; ?></td>
                    <td><code><?php echo h($row['army_no']); ?></code></td>
                    <td style="font-weight:600;"><?php echo h($row['full_name']); ?></td>
                    <?php if (!$isClerk): ?>
                    <td style="color:var(--muted,#8fa8c6);"><?php echo h($row['company_name']); ?></td>
                    <?php endif; ?>
                    <td>
                        <span class="badge <?php echo $tradeClass; ?>"
                              style="padding:2px 9px;border-radius:5px;font-size:11px;font-weight:600;">
                            <?php echo h($row['trade']); ?>
                        </span>
                    </td>
                    <td style="font-size:13px;"><?php echo $row['dob'] ? date('d M Y', strtotime($row['dob'])) : '—'; ?></td>
                    <td style="font-size:13px;"><?php echo $row['enrolment_date'] ? date('d M Y', strtotime($row['enrolment_date'])) : '—'; ?></td>
                    <td>
                        <?php if ($row['tenure_end_date']):
                            $days = (int)$row['days_remaining'];
                            if ($days < 0) {
                                $tenureClass = 'tenure-expired';
                                $tenureLabel = 'Completed';
                            } elseif ($days <= 90) {
                                $tenureClass = 'tenure-critical';
                                $tenureLabel = $days . 'd left';
                            } elseif ($days <= 180) {
                                $tenureClass = 'tenure-soon';
                                $tenureLabel = round($days / 30) . ' mo left';
                            } else {
                                $tenureClass = 'tenure-ok';
                                $tenureLabel = round($days / 30) . ' mo left';
                            }
                        ?>
                        <div class="tenure-wrap">
                            <span class="tenure-date"><?php echo date('d M Y', strtotime($row['tenure_end_date'])); ?></span>
                            <span class="tenure-tag <?php echo $tenureClass; ?>"><?php echo $tenureLabel; ?></span>
                        </div>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><?php echo h($row['home_state'] ?? '—'); ?></td>
                    <td style="font-weight:600;color:var(--gold,#c9a227);"><?php echo h($row['blood_group'] ?? '—'); ?></td>
                    <td style="font-family:'Fira Code',monospace;font-size:12px;"><?php echo h($row['mobile_no'] ?? '—'); ?></td>
                    <td>
                        <span class="badge <?php echo $statusClass; ?>"
                              style="padding:2px 9px;border-radius:5px;font-size:11px;font-weight:600;">
                            <?php echo h($row['status']); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div>
</main>
</body>
</html>
