<?php
// -------------------- BOOTSTRAP --------------------
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";
require_login();
require_role(['admin', 'asset_manager']);
require_once __DIR__ . "/../includes/db.php";

$pdo = db('alms');
if (!$pdo) { http_response_code(500); exit('Database connect failed for ALMS.'); }

$section = 'alms';
$active  = 'dropoff_reports';

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// -------------------- FILTERS --------------------
$fLoc       = trim($_GET['loc'] ?? '');
$fPersonnel = trim($_GET['personnel'] ?? '');
$fStatus    = $_GET['dropoff_status'] ?? '';
$fFrom      = $_GET['from'] ?? '';
$fTo        = $_GET['to'] ?? '';
$fQ         = trim($_GET['q'] ?? '');

$params = []; $where = ["dropoff_location IS NOT NULL AND dropoff_location <> ''"];
if ($fLoc !== '')       { $where[] = 'dropoff_location LIKE :loc';        $params[':loc'] = '%'.$fLoc.'%'; }
if ($fPersonnel !== '') { $where[] = 'assigned_personnel LIKE :pers';     $params[':pers'] = '%'.$fPersonnel.'%'; }
if ($fStatus !== '')    { $where[] = 'dropoff_status = :dstatus';         $params[':dstatus'] = $fStatus; }
if ($fFrom !== '')      { $where[] = 'dropoff_date >= :dfrom';            $params[':dfrom'] = $fFrom; }
if ($fTo !== '')        { $where[] = "dropoff_date <= :dto";              $params[':dto'] = $fTo . ' 23:59:59'; }
if ($fQ !== '')         { $where[] = '(name LIKE :q OR unique_id LIKE :q)'; $params[':q'] = '%'.$fQ.'%'; }

$w = 'WHERE ' . implode(' AND ', $where);

// -------------------- EXPORT --------------------
if (($_GET['action'] ?? '') === 'export') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=dropoff_report_'.date('Ymd_His').'.csv');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['ID','Asset Name','Unique ID','Asset Type','Drop-off Location','Assigned Personnel','Drop-off Date','Drop-off Status','Department','Notes']);
  $stmt = $pdo->prepare("SELECT id,name,unique_id,asset_type,dropoff_location,assigned_personnel,dropoff_date,dropoff_status,department,notes FROM assets $w ORDER BY dropoff_date DESC");
  $stmt->execute($params);
  while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) { fputcsv($out, $r); }
  fclose($out); exit;
}

// -------------------- LOAD DATA --------------------
$stmt = $pdo->prepare("SELECT * FROM assets $w ORDER BY dropoff_date DESC LIMIT 1000");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$totalDropoffs = count($rows);
$pending   = count(array_filter($rows, fn($r) => ($r['dropoff_status'] ?? '') === 'pending'));
$inTransit = count(array_filter($rows, fn($r) => ($r['dropoff_status'] ?? '') === 'in_transit'));
$delivered = count(array_filter($rows, fn($r) => ($r['dropoff_status'] ?? '') === 'delivered'));
$confirmed = count(array_filter($rows, fn($r) => ($r['dropoff_status'] ?? '') === 'confirmed'));

$userName = $_SESSION["user"]["name"] ?? "User";
$userRole = $_SESSION["user"]["role"] ?? "Asset Manager";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Drop-off Reports | TNVS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="../css/style.css" rel="stylesheet" />
  <link href="../css/modules.css" rel="stylesheet" />
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
  <script src="../js/sidebar-toggle.js"></script>
  <style>
    :root { --slate-50:#f8fafc; --slate-100:#f1f5f9; --slate-200:#e2e8f0; --slate-600:#475569; --slate-800:#1e293b; }
    body { background-color: var(--slate-50); }
    .text-label { font-size:.7rem; text-transform:uppercase; letter-spacing:.6px; font-weight:700; color:#94a3b8; margin-bottom:2px; }
    .stats-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:1.25rem; margin-bottom:1.25rem; }
    .stat-card { background:#fff; border:1px solid var(--slate-200); border-radius:1rem; padding:1.2rem; box-shadow:0 1px 3px rgba(0,0,0,.05); transition:transform .2s; }
    .stat-card:hover { transform:translateY(-2px); box-shadow:0 4px 6px -1px rgba(0,0,0,.1); }
    .stat-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; margin-bottom:.8rem; }
    .card-table { border:1px solid var(--slate-200); border-radius:1rem; background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.05); overflow:hidden; }
    .table-custom thead th { font-size:.75rem; text-transform:uppercase; letter-spacing:.5px; color:var(--slate-600); background:var(--slate-50); border-bottom:1px solid var(--slate-200); font-weight:600; padding:1rem 1.25rem; }
    .table-custom tbody td { padding:.95rem 1.25rem; border-bottom:1px solid var(--slate-100); font-size:.95rem; color:var(--slate-800); vertical-align:middle; }
    .table-custom tbody tr:last-child td { border-bottom:none; }
    .table-custom tbody tr:hover td { background-color:#f8fafc; }
    .filters-wrap .input-group-text { background:#fff; border-right:0; color:#94a3b8; }
    .filters-wrap .form-control.search-control { border-left:0; padding-left:0; }
    .badge-pending    { background:#fef3c7; color:#92400e; }
    .badge-in_transit { background:#dbeafe; color:#1e40af; }
    .badge-delivered  { background:#d1fae5; color:#065f46; }
    .badge-confirmed  { background:#ede9fe; color:#5b21b6; }
  </style>
</head>
<body class="saas-page">
  <div class="container-fluid p-0">
    <div class="row g-0">
      <?php include __DIR__ . '/../includes/sidebar.php'; ?>

      <div class="col main-content p-3 p-lg-4">
        <!-- Topbar -->
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle d-lg-none btn btn-outline-secondary btn-sm" id="sidebarToggle2" aria-label="Toggle sidebar">
              <ion-icon name="menu-outline"></ion-icon>
            </button>
            <h2 class="m-0 d-flex align-items-center gap-2 page-title">
              <ion-icon name="location-outline"></ion-icon>
              <span>Drop-off Reports</span>
            </h2>
          </div>
          <div class="profile-menu" data-profile-menu>
            <button class="profile-trigger" type="button" data-profile-trigger aria-expanded="false" aria-haspopup="true">
              <img src="../img/profile.jpg" class="rounded-circle" width="36" height="36" alt="">
              <div class="profile-text">
                <div class="profile-name"><?= h($userName) ?></div>
                <div class="profile-role"><?= h($userRole) ?></div>
              </div>
              <ion-icon class="profile-caret" name="chevron-down-outline"></ion-icon>
            </button>
            <div class="profile-dropdown" data-profile-dropdown role="menu">
              <a href="<?= rtrim(BASE_URL,'/') ?>/auth/logout.php" role="menuitem">Sign out</a>
            </div>
          </div>
        </div>

        <div class="px-4 pb-5">
          <!-- KPIs -->
          <section class="stats-row">
            <div class="stat-card">
              <div class="stat-icon bg-primary bg-opacity-10 text-primary"><ion-icon name="location-outline"></ion-icon></div>
              <div class="text-label">Total Drop-offs</div>
              <div class="fs-3 fw-bold text-dark mt-1"><?= $totalDropoffs ?></div>
            </div>
            <div class="stat-card">
              <div class="stat-icon bg-warning bg-opacity-10 text-warning"><ion-icon name="hourglass-outline"></ion-icon></div>
              <div class="text-label">Pending</div>
              <div class="fs-3 fw-bold text-dark mt-1"><?= $pending ?></div>
            </div>
            <div class="stat-card">
              <div class="stat-icon bg-info bg-opacity-10 text-info"><ion-icon name="paper-plane-outline"></ion-icon></div>
              <div class="text-label">In Transit</div>
              <div class="fs-3 fw-bold text-dark mt-1"><?= $inTransit ?></div>
            </div>
            <div class="stat-card">
              <div class="stat-icon bg-success bg-opacity-10 text-success"><ion-icon name="checkmark-circle-outline"></ion-icon></div>
              <div class="text-label">Delivered</div>
              <div class="fs-3 fw-bold text-dark mt-1"><?= $delivered ?></div>
            </div>
            <div class="stat-card">
              <div class="stat-icon" style="background:rgba(91,33,182,.1); color:#5b21b6;"><ion-icon name="shield-checkmark-outline"></ion-icon></div>
              <div class="text-label">Confirmed</div>
              <div class="fs-3 fw-bold text-dark mt-1"><?= $confirmed ?></div>
            </div>
          </section>

          <!-- Action bar -->
          <section class="mb-3">
            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
              <a class="btn btn-violet d-flex align-items-center gap-2"
                 href="?action=export&loc=<?= urlencode($fLoc) ?>&personnel=<?= urlencode($fPersonnel) ?>&dropoff_status=<?= urlencode($fStatus) ?>&from=<?= urlencode($fFrom) ?>&to=<?= urlencode($fTo) ?>&q=<?= urlencode($fQ) ?>">
                <ion-icon name="download-outline"></ion-icon> Export CSV
              </a>
            </div>
          </section>

          <!-- Filters -->
          <section class="filters-wrap mb-3">
            <form method="get" class="d-flex flex-wrap gap-2 align-items-center">
              <div class="flex-grow-1" style="max-width:260px">
                <div class="input-group">
                  <span class="input-group-text"><ion-icon name="search-outline"></ion-icon></span>
                  <input name="q" class="form-control search-control" placeholder="Search asset name / ID" value="<?= h($fQ) ?>">
                </div>
              </div>
              <input name="loc" class="form-control" style="max-width:180px" placeholder="Location" value="<?= h($fLoc) ?>">
              <input name="personnel" class="form-control" style="max-width:180px" placeholder="Personnel" value="<?= h($fPersonnel) ?>">
              <select name="dropoff_status" class="form-select" style="max-width:160px">
                <option value="">All statuses</option>
                <option value="pending"    <?= $fStatus==='pending'?'selected':'' ?>>Pending</option>
                <option value="in_transit" <?= $fStatus==='in_transit'?'selected':'' ?>>In Transit</option>
                <option value="delivered"  <?= $fStatus==='delivered'?'selected':'' ?>>Delivered</option>
                <option value="confirmed"  <?= $fStatus==='confirmed'?'selected':'' ?>>Confirmed</option>
              </select>
              <input name="from" type="date" class="form-control" style="max-width:160px" value="<?= h($fFrom) ?>" title="From date">
              <input name="to"   type="date" class="form-control" style="max-width:160px" value="<?= h($fTo) ?>" title="To date">
              <button class="btn btn-white border shadow-sm fw-medium px-3" type="submit">Filter</button>
              <a class="btn btn-link text-decoration-none text-muted p-0" href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">Reset</a>
            </form>
          </section>

          <!-- Table -->
          <section class="card-table">
            <div class="table-responsive">
              <table class="table table-custom mb-0 align-middle">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Asset Name</th>
                    <th>Unique ID</th>
                    <th>Drop-off Location</th>
                    <th>Assigned Personnel</th>
                    <th>Drop-off Date</th>
                    <th>Status</th>
                    <th>Department</th>
                    <th>Notes</th>
                  </tr>
                </thead>
                <tbody>
                <?php if (!$rows): ?>
                  <tr><td colspan="9" class="text-center py-5 text-muted">No drop-off records found.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                  <tr>
                    <td class="fw-semibold"><?= (int)$r['id'] ?></td>
                    <td><?= h($r['name']) ?></td>
                    <td class="text-primary" style="font-family:monospace"><?= h($r['unique_id'] ?: '—') ?></td>
                    <td><?= h($r['dropoff_location']) ?></td>
                    <td><?= h($r['assigned_personnel'] ?: '—') ?></td>
                    <td><?= h($r['dropoff_date'] ?: '—') ?></td>
                    <td>
                      <?php $ds = $r['dropoff_status'] ?? 'pending'; ?>
                      <span class="badge badge-<?= h($ds) ?>"><?= h(ucwords(str_replace('_',' ',$ds))) ?></span>
                    </td>
                    <td><?= h($r['department'] ?: '—') ?></td>
                    <td><?= h($r['notes'] ?: '—') ?></td>
                  </tr>
                <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
            <div class="d-flex justify-content-between align-items-center p-3 border-top bg-light bg-opacity-50">
              <div class="small text-muted">Showing <?= count($rows) ?> record(s)</div>
            </div>
          </section>
        </div>

      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/profile-dropdown.js"></script>
</body>
</html>
