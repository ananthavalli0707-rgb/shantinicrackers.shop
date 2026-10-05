<?php
require __DIR__ . '/../includes/header.php';

if (empty($_SESSION['is_admin'])) {
    redirect('admin-login');
}

// Fetch stats
$totalVisits = $pdo->query('SELECT COUNT(*) FROM page_views')->fetchColumn();
$uniqueVisitors = $pdo->query('SELECT COUNT(DISTINCT ip_address) FROM page_views')->fetchColumn();

// Today's stats
$todayVisits = $pdo->query('SELECT COUNT(*) FROM page_views WHERE DATE(created_at) = CURDATE()')->fetchColumn();
$todayUnique = $pdo->query('SELECT COUNT(DISTINCT ip_address) FROM page_views WHERE DATE(created_at) = CURDATE()')->fetchColumn();

// Top pages
$topPages = $pdo->query('
    SELECT page_url, COUNT(*) as views 
    FROM page_views 
    GROUP BY page_url 
    ORDER BY views DESC 
    LIMIT 10
')->fetchAll();

// Recent visitors
$recentVisitors = $pdo->query('
    SELECT page_url, ip_address, created_at 
    FROM page_views 
    ORDER BY created_at DESC 
    LIMIT 15
')->fetchAll();
?>

<section>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Visitor Analytics Dashboard</h2>
    </div>

    <!-- Quick Stats -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-primary text-white text-center p-4 h-100">
                <i class="fa-solid fa-eye fs-1 mb-2 opacity-75"></i>
                <h5 class="fw-bold mb-0">Today's Views</h5>
                <h2 class="display-5 fw-bold mb-0"><?= number_format($todayVisits) ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-info text-white text-center p-4 h-100">
                <i class="fa-solid fa-users fs-1 mb-2 opacity-75"></i>
                <h5 class="fw-bold mb-0">Today's Visitors</h5>
                <h2 class="display-5 fw-bold mb-0"><?= number_format($todayUnique) ?></h2>
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
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Live Recent Activity</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Time</th>
                                    <th>Page</th>
                                    <th class="pe-4">Visitor IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentVisitors as $visit): ?>
                                <tr>
                                    <td class="ps-4 text-muted small">
                                        <?= date('h:i A (d M)', strtotime($visit['created_at'])) ?>
                                    </td>
                                    <td>
                                        <span class="text-primary fw-medium">/<?= htmlspecialchars($visit['page_url']) ?></span>
                                    </td>
                                    <td class="pe-4 text-muted small">
                                        <?= htmlspecialchars(substr($visit['ip_address'], 0, 10)) ?>***
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

<?php require __DIR__ . '/../includes/footer.php'; ?>
