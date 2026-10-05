<?php
require __DIR__ . '/../includes/header.php';

if (empty($_SESSION['is_admin'])) {
    redirect('admin-login');
}

try {
    $selectedDate = $_GET['date'] ?? date('Y-m-d');
    $isToday = $selectedDate === date('Y-m-d');

    // Fetch stats
    $totalVisits = $pdo->query('SELECT COUNT(*) FROM page_views')->fetchColumn();
    $uniqueVisitors = $pdo->query('SELECT COUNT(DISTINCT ip_address) FROM page_views')->fetchColumn();

    // Selected Date stats
    $stmtDateVisits = $pdo->prepare('SELECT COUNT(*) FROM page_views WHERE DATE(created_at) = ?');
    $stmtDateVisits->execute([$selectedDate]);
    $dateVisitsCount = $stmtDateVisits->fetchColumn();

    $stmtDateUnique = $pdo->prepare('SELECT COUNT(DISTINCT ip_address) FROM page_views WHERE DATE(created_at) = ?');
    $stmtDateUnique->execute([$selectedDate]);
    $dateUniqueCount = $stmtDateUnique->fetchColumn();

    // Top pages (All Time)
    $topPages = $pdo->query('
        SELECT page_url, COUNT(*) as views 
        FROM page_views 
        GROUP BY page_url 
        ORDER BY views DESC 
        LIMIT 10
    ')->fetchAll();

    // Activity on selected date
    $stmtRecent = $pdo->prepare('
        SELECT page_url, ip_address, user_agent, created_at 
        FROM page_views 
        WHERE DATE(created_at) = ?
        ORDER BY created_at DESC 
        LIMIT 50
    ');
    $stmtRecent->execute([$selectedDate]);
    $recentVisitors = $stmtRecent->fetchAll();
    // 20-Day Trend
    $trend = $pdo->query('
        SELECT DATE(created_at) as date, COUNT(*) as views, COUNT(DISTINCT ip_address) as visitors
        FROM page_views 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 20 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ')->fetchAll();
} catch (PDOException $e) {
    $totalVisits = $uniqueVisitors = $dateVisitsCount = $dateUniqueCount = 0;
    $topPages = $recentVisitors = $trend = [];
    $error = "Analytics table missing! Please run yourdomain.com/sync_online_db.php to install the tracker.";
}

// Prepare Chart.js data
$chartDates = [];
$chartViews = [];
$chartVisitors = [];

// Fill in missing dates with zero for a smooth 20-day chart
$today = time();
$trendMap = [];
foreach ($trend as $t) {
    $trendMap[$t['date']] = $t;
}

for ($i = 19; $i >= 0; $i--) {
    $dateStr = date('Y-m-d', strtotime("-$i days", $today));
    $chartDates[] = date('M d', strtotime($dateStr));
    $chartViews[] = $trendMap[$dateStr]['views'] ?? 0;
    $chartVisitors[] = $trendMap[$dateStr]['visitors'] ?? 0;
}

?>

<section>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Visitor Analytics Dashboard</h2>
        <form method="GET" action="<?= url('admin-analytics') ?>" class="d-flex align-items-center gap-2">
            <input type="hidden" name="page" value="admin-analytics">
            <label class="fw-medium text-muted mb-0">Date:</label>
            <input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars($selectedDate) ?>" onchange="this.form.submit()" max="<?= date('Y-m-d') ?>">
            <?php if (!$isToday): ?>
                <a href="<?= url('admin-analytics') ?>" class="btn btn-sm btn-outline-primary ms-2">Today</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-warning shadow-sm border-0 fw-medium">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Quick Stats -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-primary text-white text-center p-4 h-100">
                <i class="fa-solid fa-eye fs-1 mb-2 opacity-75"></i>
                <h5 class="fw-bold mb-0"><?= $isToday ? "Today's Views" : "Views on " . date('M d', strtotime($selectedDate)) ?></h5>
                <h2 class="display-5 fw-bold mb-0"><?= number_format($dateVisitsCount) ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-info text-white text-center p-4 h-100">
                <i class="fa-solid fa-users fs-1 mb-2 opacity-75"></i>
                <h5 class="fw-bold mb-0"><?= $isToday ? "Today's Visitors" : "Visitors on " . date('M d', strtotime($selectedDate)) ?></h5>
                <h2 class="display-5 fw-bold mb-0"><?= number_format($dateUniqueCount) ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-success text-white text-center p-4 h-100">
                <i class="fa-solid fa-chart-line fs-1 mb-2 opacity-75"></i>
                <h5 class="fw-bold mb-0">All Time Views</h5>
                <h2 class="display-5 fw-bold mb-0"><?= number_format($totalVisits) ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-dark text-white text-center p-4 h-100">
                <i class="fa-solid fa-globe fs-1 mb-2 opacity-75"></i>
                <h5 class="fw-bold mb-0">All Time Visitors</h5>
                <h2 class="display-5 fw-bold mb-0"><?= number_format($uniqueVisitors) ?></h2>
            </div>
        </div>
    </div>

    <!-- 20 Day Trend Chart -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card shadow-sm border-0 p-4">
                <h5 class="fw-bold mb-4"><i class="fa-solid fa-chart-area text-primary me-2"></i> Last 20 Days Traffic Trend</h5>
                <canvas id="trafficChart" height="80"></canvas>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Top Pages -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-fire text-danger me-2"></i> Most Visited Pages</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Page Name</th>
                                    <th class="text-end pe-4">Total Views</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topPages as $page): ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="badge bg-light text-dark border">/<?= htmlspecialchars($page['page_url']) ?></span>
                                    </td>
                                    <td class="text-end pe-4 fw-bold text-primary">
                                        <?= number_format($page['views']) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (!$topPages): ?>
                                    <tr><td colspan="2" class="text-center py-4 text-muted">No data yet</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Activity on <?= date('M d, Y', strtotime($selectedDate)) ?></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Time</th>
                                    <th>Page</th>
                                    <th>Visitor IP</th>
                                    <th class="pe-4">Device Info</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentVisitors as $visit): 
                                    $ua = $visit['user_agent'] ?? '';
                                    $device = stripos($ua, 'mobile') !== false ? 'fa-mobile-screen' : 'fa-laptop';
                                    $os = 'fa-desktop';
                                    if (stripos($ua, 'windows') !== false) $os = 'fa-windows';
                                    elseif (stripos($ua, 'mac') !== false || stripos($ua, 'iphone') !== false) $os = 'fa-apple';
                                    elseif (stripos($ua, 'android') !== false) $os = 'fa-android';
                                    elseif (stripos($ua, 'linux') !== false) $os = 'fa-linux';
                                ?>
                                <tr>
                                    <td class="ps-4 text-muted small">
                                        <?= date('h:i A (d M)', strtotime($visit['created_at'])) ?>
                                    </td>
                                    <td>
                                        <span class="text-primary fw-medium">/<?= htmlspecialchars($visit['page_url']) ?></span>
                                    </td>
                                    <td class="text-muted small">
                                        <?= htmlspecialchars(substr($visit['ip_address'], 0, 10)) ?>***
                                    </td>
                                    <td class="pe-4 text-muted">
                                        <i class="fa-solid <?= $device ?> me-2"></i>
                                        <i class="fa-brands <?= $os ?>"></i>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (!$recentVisitors): ?>
                                    <tr><td colspan="3" class="text-center py-4 text-muted">No data yet</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('trafficChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartDates) ?>,
            datasets: [
                {
                    label: 'Page Views',
                    data: <?= json_encode($chartViews) ?>,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Unique Visitors',
                    data: <?= json_encode($chartVisitors) ?>,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
