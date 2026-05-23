<?php
session_start();
if (!isset($_SESSION["role"])) {
    header("Location: auth/login.php"); exit;
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

include("config/db.php");

$role     = $_SESSION["role"];
$isClerk  = ($role === "CHM_CLERK");
$clerkCid = (int)($_SESSION["company_id"] ?? 0);

// ── Retirement year logic ─────────────────────────────────────────────────────
// Sep / Lnk → 17 | Nk → 22 | Hav → 24 | Nb Sub → 26 | Sub → 28 | Others → 32
$retirementSql = "CASE
    WHEN p.rank_name IN ('Sep','Lnk') THEN DATE_ADD(p.date_of_joining, INTERVAL 17 YEAR)
    WHEN p.rank_name = 'Nk'           THEN DATE_ADD(p.date_of_joining, INTERVAL 22 YEAR)
    WHEN p.rank_name = 'Hav'          THEN DATE_ADD(p.date_of_joining, INTERVAL 24 YEAR)
    WHEN p.rank_name IN ('Nb Sub','NbSub') THEN DATE_ADD(p.date_of_joining, INTERVAL 26 YEAR)
    WHEN p.rank_name = 'Sub'          THEN DATE_ADD(p.date_of_joining, INTERVAL 28 YEAR)
    ELSE DATE_ADD(p.date_of_joining, INTERVAL 32 YEAR)
  END";

// ── Filters ──────────────────────────────────────────────────────────────────
$window      = max(1, min(36, (int)($_GET['months'] ?? 12))); // 1-36 month window
$filterCid   = $isClerk ? $clerkCid : (int)($_GET['company_id'] ?? 0);
$filterRank  = $_GET['rank'] ?? '';
$search      = trim($_GET['q'] ?? '');

// ── Build query ───────────────────────────────────────────────────────────────
$conditions = [
    "p.date_of_joining IS NOT NULL",
    "p.service_status = 'Serving'",
    "$retirementSql BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? MONTH)"
];
$params = [$window];
$types  = 'i';

if ($isClerk) {
    $conditions[] = "p.company_id = ?";
    $params[]     = $clerkCid;
    $types       .= 'i';
} elseif ($filterCid > 0) {
    $conditions[] = "p.company_id = ?";
    $params[]     = $filterCid;
    $types       .= 'i';
}

if ($filterRank !== '') {
    $conditions[] = "p.rank_name = ?";
    $params[]     = $filterRank;
    $types       .= 's';
}

if ($search !== '') {
    $conditions[] = "(p.full_name LIKE ? OR p.army_no LIKE ?)";
    $like         = "%$search%";
    $params[]     = $like;
    $params[]     = $like;
    $types       .= 'ss';
}

$whereSQL = "WHERE " . implode(" AND ", $conditions);

$sql = "SELECT p.id, p.army_no, p.rank_name, p.full_name, p.trade,
               p.date_of_joining, p.service_status, c.company_name, c.short_name,
               $retirementSql AS retirement_date,
               DATEDIFF($retirementSql, CURDATE()) AS days_left
        FROM personnel p
        JOIN companies c ON c.id = p.company_id
        $whereSQL
        ORDER BY retirement_date ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$rows  = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
$total = count($rows);

// ── Summary stats ─────────────────────────────────────────────────────────────
$within30  = 0; $within90  = 0; $within180 = 0; $within365 = 0;
foreach ($rows as $r) {
    $d = (int)$r['days_left'];
    if ($d <= 30)  $within30++;
    if ($d <= 90)  $within90++;
    if ($d <= 180) $within180++;
    if ($d <= 365) $within365++;
}

// ── Sidebar data ──────────────────────────────────────────────────────────────
$companies = mysqli_fetch_all(mysqli_query($conn,
    "SELECT id, company_name FROM companies WHERE is_active = 1 ORDER BY company_name"), MYSQLI_ASSOC);

$rankOptions = mysqli_fetch_all(mysqli_query($conn,
    "SELECT DISTINCT rank_name FROM personnel WHERE date_of_joining IS NOT NULL ORDER BY rank_name"), MYSQLI_ASSOC);

$clerkCoyName = '';
if ($isClerk && $clerkCid) {
    $r = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT company_name FROM companies WHERE id = $clerkCid LIMIT 1"));
    $clerkCoyName = $r['company_name'] ?? '';
}

// ── Retirement years label ────────────────────────────────────────────────────
function retirementYears(string $rank): string {
    return match(true) {
        in_array($rank, ['Sep','Lnk'])         => '17 yrs',
        $rank === 'Nk'                          => '22 yrs',
        $rank === 'Hav'                         => '24 yrs',
        in_array($rank, ['Nb Sub','NbSub'])     => '26 yrs',
        $rank === 'Sub'                         => '28 yrs',
        default                                 => '32 yrs',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("includes/head_meta.php"); ?>
    <title>Upcoming Retirements | BN Parade State Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .ret-stat-bar { display:flex; flex-wrap:wrap; gap:12px; margin-bottom:22px; }
        .ret-stat {
            flex:1; min-width:120px;
            background:var(--card-bg,rgba(255,255,255,.04));
            border:1px solid var(--border,rgba(255,255,255,.08));
            border-radius:10px; padding:12px 18px; text-align:center;
        }
        .ret-stat-num   { font-size:26px; font-weight:700; font-family:'Fira Code',monospace; }
        .ret-stat-label { font-size:11px; color:var(--muted,#8fa8c6); text-transform:uppercase; letter-spacing:.05em; margin-top:2px; }
        .rs-30  { border-color:#f87171; } .rs-30  .ret-stat-num { color:#f87171; }
        .rs-90  { border-color:#d97706; } .rs-90  .ret-stat-num { color:#d97706; }
        .rs-180 { border-color:#c9a227; } .rs-180 .ret-stat-num { color:var(--gold,#c9a227); }
        .rs-365 { border-color:var(--status-present,#22863a); } .rs-365 .ret-stat-num { color:var(--status-present,#22863a); }

        .urgency-critical { background:rgba(239,68,68,.1); }
        .urgency-high     { background:rgba(234,179,8,.08); }
        .urgency-medium   { }

        .ret-badge {
            display:inline-block; padding:2px 8px; border-radius:5px;
            font-size:11px; font-weight:700; white-space:nowrap;
        }
        .ret-critical { background:rgba(239,68,68,.18); color:#f87171; }
        .ret-high     { background:rgba(234,179,8,.18);  color:#d97706; }
        .ret-medium   { background:rgba(201,162,39,.15); color:var(--gold,#c9a227); }
        .ret-normal   { background:rgba(34,134,58,.12);  color:var(--status-present,#22863a); }

        .rank-years { font-size:11px; color:var(--muted,#8fa8c6); margin-left:5px; }

        .window-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:18px; }
        .window-tab  {
            padding:5px 14px; border-radius:20px; font-size:12px; font-weight:600;
            border:1px solid var(--border,rgba(255,255,255,.1));
            color:var(--muted,#8fa8c6); text-decoration:none; transition:all .2s;
        }
        .window-tab:hover, .window-tab.active {
            background:var(--gold,#c9a227); color:#0b1a2e; border-color:transparent;
        }
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
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     width="22" height="22" style="vertical-align:-4px;margin-right:8px;color:var(--gold,#c9a227)">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
                Upcoming Retirements
                <?php if ($isClerk && $clerkCoyName): ?> &mdash; <?php echo h($clerkCoyName); ?><?php endif; ?>
            </h1>
            <p class="page-sub">
                <?php if ($isClerk): ?>
                    <?php echo h($clerkCoyName); ?> personnel retiring within next <?php echo $window; ?> months
                <?php else: ?>
                    Battalion personnel retiring within next <?php echo $window; ?> months
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Quick window switcher -->
    <div class="window-tabs">
        <?php foreach ([3 => '3 Months', 6 => '6 Months', 12 => '1 Year', 24 => '2 Years', 36 => '3 Years'] as $m => $label):
            $tabParams = array_merge($_GET, ['months' => $m]);
            unset($tabParams['q']); // keep company/rank filters
        ?>
        <a href="?<?php echo http_build_query($tabParams); ?>"
           class="window-tab<?php echo $window == $m ? ' active' : ''; ?>">
            <?php echo $label; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Summary stat bar -->
    <div class="ret-stat-bar">
        <div class="ret-stat rs-30">
            <div class="ret-stat-num"><?php echo $within30; ?></div>
            <div class="ret-stat-label">Within 30 days</div>
        </div>
        <div class="ret-stat rs-90">
            <div class="ret-stat-num"><?php echo $within90; ?></div>
            <div class="ret-stat-label">Within 90 days</div>
        </div>
        <div class="ret-stat rs-180">
            <div class="ret-stat-num"><?php echo $within180; ?></div>
            <div class="ret-stat-label">Within 6 months</div>
        </div>
        <div class="ret-stat rs-365">
            <div class="ret-stat-num"><?php echo $within365; ?></div>
            <div class="ret-stat-label">Within 1 year</div>
        </div>
        <div class="ret-stat" style="border-color:var(--border);">
            <div class="ret-stat-num" style="color:var(--text);"><?php echo $total; ?></div>
            <div class="ret-stat-label">Total shown</div>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" class="form-grid filters" style="margin-bottom:20px;">
        <input type="hidden" name="months" value="<?php echo $window; ?>">

        <div class="form-group">
            <label for="q">Search</label>
            <input id="q" type="text" name="q" value="<?php echo h($search); ?>"
                   placeholder="Name or Army No…" class="form-control">
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
            <label for="rank">Rank</label>
            <select id="rank" name="rank" class="form-control">
                <option value="">All Ranks</option>
                <?php foreach ($rankOptions as $ro): ?>
                    <option value="<?php echo h($ro['rank_name']); ?>"
                        <?php echo $filterRank === $ro['rank_name'] ? 'selected' : ''; ?>>
                        <?php echo h($ro['rank_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="align-self:flex-end;display:flex;gap:8px;">
            <button type="submit" class="btn primary">Filter</button>
            <a href="upcoming_retirement.php" class="btn secondary">Reset</a>
        </div>
    </form>

    <!-- Retirement rule reference -->
    <details style="margin-bottom:16px;font-size:12px;color:var(--muted,#8fa8c6);">
        <summary style="cursor:pointer;color:var(--gold,#c9a227);font-weight:600;">
            Retirement Age Rules (click to expand)
        </summary>
        <div style="margin-top:10px;display:flex;flex-wrap:wrap;gap:8px 24px;padding:12px;background:var(--card-bg,rgba(0,0,0,.2));border-radius:8px;">
            <span>Sep / Lnk → <strong>17 years</strong></span>
            <span>Nk → <strong>22 years</strong></span>
            <span>Hav → <strong>24 years</strong></span>
            <span>Nb Sub → <strong>26 years</strong></span>
            <span>Sub → <strong>28 years</strong></span>
            <span>Sub Maj / Others → <strong>32 years</strong></span>
        </div>
    </details>

    <!-- Results -->
    <?php if (empty($rows)): ?>
        <p style="text-align:center;padding:48px 0;color:var(--muted,#8fa8c6);">
            No personnel retiring within the next <?php echo $window; ?> months.
        </p>
    <?php else: ?>

    <div style="margin-bottom:10px;font-size:13px;color:var(--muted,#8fa8c6);">
        Showing <strong style="color:var(--text)"><?php echo $total; ?></strong>
        personnel retiring within next <strong style="color:var(--text)"><?php echo $window; ?></strong> months,
        sorted by nearest retirement date.
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Army No</th>
                    <th>Rank</th>
                    <th>Name</th>
                    <?php if (!$isClerk): ?><th>Company</th><?php endif; ?>
                    <th>Trade</th>
                    <th>Date of Joining</th>
                    <th>Service (yrs)</th>
                    <th>Retirement Date</th>
                    <th>Time Left</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $i => $row):
                    $days = (int)$row['days_left'];
                    if ($days <= 30) {
                        $rowClass   = 'urgency-critical';
                        $badgeClass = 'ret-critical';
                        $badgeText  = $days . ' day' . ($days !== 1 ? 's' : '');
                    } elseif ($days <= 90) {
                        $rowClass   = 'urgency-high';
                        $badgeClass = 'ret-high';
                        $badgeText  = $days . ' days';
                    } elseif ($days <= 180) {
                        $rowClass   = 'urgency-medium';
                        $badgeClass = 'ret-medium';
                        $badgeText  = round($days / 30) . ' months';
                    } else {
                        $rowClass   = '';
                        $badgeClass = 'ret-normal';
                        $mos        = round($days / 30);
                        $badgeText  = $mos >= 12
                            ? floor($mos / 12) . ' yr ' . ($mos % 12 ? ($mos % 12) . ' mo' : '')
                            : $mos . ' months';
                    }
                    $serviceYears = retirementYears($row['rank_name']);
                ?>
                <tr class="<?php echo $rowClass; ?>">
                    <td style="color:var(--muted);font-size:12px;"><?php echo $i + 1; ?></td>
                    <td style="font-family:'Fira Code',monospace;font-size:12px;"><?php echo h($row['army_no']); ?></td>
                    <td>
                        <strong><?php echo h($row['rank_name']); ?></strong>
                        <span class="rank-years">(<?php echo $serviceYears; ?>)</span>
                    </td>
                    <td style="font-weight:600;"><?php echo h($row['full_name']); ?></td>
                    <?php if (!$isClerk): ?>
                    <td style="color:var(--muted,#8fa8c6);"><?php echo h($row['company_name']); ?></td>
                    <?php endif; ?>
                    <td><?php echo h($row['trade'] ?? '—'); ?></td>
                    <td style="font-size:13px;color:var(--muted,#8fa8c6);">
                        <?php echo $row['date_of_joining'] ? date('d M Y', strtotime($row['date_of_joining'])) : '—'; ?>
                    </td>
                    <td style="text-align:center;font-weight:600;">
                        <?php echo $serviceYears; ?>
                    </td>
                    <td style="font-weight:700;">
                        <?php echo date('d M Y', strtotime($row['retirement_date'])); ?>
                    </td>
                    <td>
                        <span class="ret-badge <?php echo $badgeClass; ?>"><?php echo $badgeText; ?></span>
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
