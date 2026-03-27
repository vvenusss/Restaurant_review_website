<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/includes/restaurant-db.php';

$adminName = $_SESSION['name'] ?? 'Admin';

// ── Database connection ──────────────────────────────────────────────────────
$dbError = '';
$conn = getDatabaseConnection($dbError);

// ── Stat counters ────────────────────────────────────────────────────────────
$totalUsers        = 0;
$totalRestaurants  = 0;
$flaggedReviews    = 0;   // reviews with Rating <= 2
$activeSessions    = 18;  // no sessions table — kept as a static indicator

if ($conn) {

    // Total registered users
    $result = $conn->query('SELECT COUNT(*) AS cnt FROM users');
    if ($result) {
        $totalUsers = (int) $result->fetch_assoc()['cnt'];
        $result->free();
    }

    // Total restaurant listings
    $result = $conn->query('SELECT COUNT(*) AS cnt FROM Restaurants');
    if ($result) {
        $totalRestaurants = (int) $result->fetch_assoc()['cnt'];
        $result->free();
    }

    // Flagged reviews = reviews with a rating of 2 stars or below
    $result = $conn->query('SELECT COUNT(*) AS cnt FROM reviews WHERE Rating <= 2');
    if ($result) {
        $flaggedReviews = (int) $result->fetch_assoc()['cnt'];
        $result->free();
    }
}

// ── Stat cards ───────────────────────────────────────────────────────────────
$statCards = [
    [
        'title' => 'Total Users',
        'value' => $totalUsers,
        'icon'  => 'bi-people-fill',
        'desc'  => 'Registered platform users'
    ],
    [
        'title' => 'Restaurants',
        'value' => $totalRestaurants,
        'icon'  => 'bi-shop',
        'desc'  => 'Business listings managed'
    ],
    [
        'title' => 'Flagged Reviews',
        'value' => $flaggedReviews,
        'icon'  => 'bi-flag-fill',
        'desc'  => 'Reviews rated 2 stars or below'
    ],
    [
        'title' => 'Active Sessions',
        'value' => $activeSessions,
        'icon'  => 'bi-shield-lock-fill',
        'desc'  => 'Currently active logins'
    ]
];

// ── Quick actions (unchanged — no DB data needed) ────────────────────────────
$quickActions = [
    [
        'title'  => 'Moderation Panel',
        'desc'   => 'View all users and restaurants, then remove items when necessary.',
        'icon'   => 'bi-kanban-fill',
        'link'   => 'moderation.php',
        'button' => 'Open Moderation',
        'class'  => 'primary'
    ],
    [
        'title'  => 'Edit Admin Profile',
        'desc'   => 'Update admin information, profile details, and account preferences.',
        'icon'   => 'bi-person-circle',
        'link'   => 'edit-profile.php',
        'button' => 'Edit Profile',
        'class'  => 'dark'
    ],
    [
        'title'  => 'Review Homepage',
        'desc'   => 'Check the public-facing restaurant experience from the user perspective.',
        'icon'   => 'bi-window-stack',
        'link'   => 'index.php',
        'button' => 'Open Homepage',
        'class'  => 'light'
    ],
    [
        'title'  => 'Log Out',
        'desc'   => 'End the current admin session securely.',
        'icon'   => 'bi-box-arrow-right',
        'link'   => 'logout.php',
        'button' => 'Log Out',
        'class'  => 'danger'
    ]
];

// ── Recent activity: last 4 reviews from the DB ──────────────────────────────
$recentActivities = [];

if ($conn) {
    $stmt = $conn->prepare(
        'SELECT rv.idReview, rv.Rating, rv.Comments, rv.ReviewDate,
                u.name   AS ReviewerName,
                r.RestaurantName
         FROM   reviews rv
         INNER  JOIN users       u ON u.idusers        = rv.UserId
         INNER  JOIN Restaurants r ON r.idRestaurants  = rv.RestaurantID
         ORDER  BY rv.ReviewDate DESC
         LIMIT  4'
    );

    if ($stmt) {
        $stmt->execute();
        $rows = fetchAllAssocFromStatement($stmt);
        $stmt->close();

        foreach ($rows as $row) {
            // Build a human-readable "time ago" label
            $reviewTime = strtotime($row['ReviewDate']);
            $diffMins   = max(0, (int) round((time() - $reviewTime) / 60));

            if ($diffMins < 60) {
                $timeLabel = $diffMins . ' minute' . ($diffMins !== 1 ? 's' : '') . ' ago';
            } elseif ($diffMins < 1440) {
                $hrs = (int) round($diffMins / 60);
                $timeLabel = $hrs . ' hour' . ($hrs !== 1 ? 's' : '') . ' ago';
            } else {
                $days = (int) round($diffMins / 1440);
                $timeLabel = $days . ' day' . ($days !== 1 ? 's' : '') . ' ago';
            }

            // Flag low-rated reviews as needing attention
            $rating = (int) $row['Rating'];
            if ($rating <= 2) {
                $status = 'Needs Attention';
            } elseif ($rating === 3) {
                $status = 'Pending Review';
            } else {
                $status = 'Synced';
            }

            $recentActivities[] = [
                'title'  => 'Review submitted for ' . $row['RestaurantName'],
                'meta'   => htmlspecialchars($row['ReviewerName']) . ' · ' . $timeLabel
                            . ' · ' . $rating . '★',
                'status' => $status
            ];
        }
    }
}

// Fallback if no reviews are returned
if (empty($recentActivities)) {
    $recentActivities[] = [
        'title'  => 'No recent review activity',
        'meta'   => 'Nothing to display yet',
        'status' => 'Up to Date'
    ];
}

// ── System status ────────────────────────────────────────────────────────────
$systemStatus = [
    [
        'label' => 'Authentication',
        'value' => 'Operational',
        'good'  => true
    ],
    [
        'label' => 'Moderation Queue',
        'value' => $flaggedReviews > 0 ? $flaggedReviews . ' item' . ($flaggedReviews !== 1 ? 's' : '') . ' open' : 'All clear',
        'good'  => $flaggedReviews === 0
    ],
    [
        'label' => 'User Sessions',
        'value' => 'Stable',
        'good'  => true
    ],
    [
        'label' => 'Platform Visibility',
        'value' => 'Public pages live',
        'good'  => true
    ]
];

// Close DB connection
if ($conn) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodview - Admin Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="admin-dashboard-page">

<?php include("includes/header.php"); ?>

<div class="container py-5">

    <?php if ($dbError !== ''): ?>
        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <span>Database connection issue: <?php echo htmlspecialchars($dbError); ?></span>
        </div>
    <?php endif; ?>

    <section class="admin-hero mb-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="admin-badge mb-3 d-inline-flex align-items-center">
                    <i class="bi bi-shield-check me-2"></i> Admin Control Center
                </span>
                <h1 class="admin-hero-title mb-2">
                    Welcome back, <?php echo htmlspecialchars($adminName); ?>
                </h1>
                <p class="admin-hero-text mb-0">
                    Manage users, restaurants, reviews, and platform activity from one clean dashboard.
                </p>
            </div>

            <div class="col-lg-4">
                <div class="admin-hero-actions">
                    <a href="moderation.php" class="btn btn-admin-primary w-100 mb-2">
                        <i class="bi bi-kanban-fill me-2"></i> Go to Moderation
                    </a>
                    <a href="edit-profile.php" class="btn btn-admin-outline w-100">
                        <i class="bi bi-pencil-square me-2"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-4">
        <div class="row g-4">
            <?php foreach ($statCards as $card): ?>
                <div class="col-sm-6 col-xl-3">
                    <div class="admin-stat-card h-100">
                        <div class="admin-stat-icon">
                            <i class="bi <?php echo htmlspecialchars($card['icon']); ?>"></i>
                        </div>
                        <div>
                            <p class="admin-stat-label mb-1"><?php echo htmlspecialchars($card['title']); ?></p>
                            <h3 class="admin-stat-value mb-1"><?php echo htmlspecialchars((string)$card['value']); ?></h3>
                            <p class="admin-stat-desc mb-0"><?php echo htmlspecialchars($card['desc']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-xl-8">
            <section class="admin-panel mb-4">
                <div class="admin-panel-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h2 class="admin-section-title mb-1">Quick Actions</h2>
                        <p class="admin-section-subtitle mb-0">Fast access to the most important admin tasks.</p>
                    </div>
                </div>

                <div class="row g-4 mt-1">
                    <?php foreach ($quickActions as $action): ?>
                        <div class="col-md-6">
                            <div class="admin-action-card h-100">
                                <div class="admin-action-icon <?php echo htmlspecialchars($action['class']); ?>">
                                    <i class="bi <?php echo htmlspecialchars($action['icon']); ?>"></i>
                                </div>
                                <h3 class="admin-action-title"><?php echo htmlspecialchars($action['title']); ?></h3>
                                <p class="admin-action-text"><?php echo htmlspecialchars($action['desc']); ?></p>
                                <a href="<?php echo htmlspecialchars($action['link']); ?>" class="btn btn-action mt-auto">
                                    <?php echo htmlspecialchars($action['button']); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="admin-panel mb-4">
                <div class="admin-panel-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h2 class="admin-section-title mb-1">Management Overview</h2>
                        <p class="admin-section-subtitle mb-0">Keep the platform clean, safe, and organized.</p>
                    </div>
                </div>

                <div class="row g-4 mt-1">
                    <div class="col-md-6">
                        <div class="admin-mini-panel h-100">
                            <div class="d-flex align-items-center mb-3">
                                <div class="mini-panel-icon me-3">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                                <div>
                                    <h3 class="h5 mb-1">User Management</h3>
                                    <p class="text-muted mb-0">Review accounts and remove invalid users.</p>
                                </div>
                            </div>
                            <ul class="admin-check-list">
                                <li>Display all users</li>
                                <li>Delete users when needed</li>
                                <li>Monitor suspicious activity</li>
                            </ul>
                            <a href="moderation.php" class="btn btn-sm btn-admin-outline">Manage Users</a>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="admin-mini-panel h-100">
                            <div class="d-flex align-items-center mb-3">
                                <div class="mini-panel-icon me-3">
                                    <i class="bi bi-shop"></i>
                                </div>
                                <div>
                                    <h3 class="h5 mb-1">Restaurant Management</h3>
                                    <p class="text-muted mb-0">Check listings and remove invalid restaurants.</p>
                                </div>
                            </div>
                            <ul class="admin-check-list">
                                <li>Display all restaurant listings</li>
                                <li>Delete inappropriate entries</li>
                                <li>Review listing quality</li>
                            </ul>
                            <a href="moderation.php" class="btn btn-sm btn-admin-outline">Manage Restaurants</a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h2 class="admin-section-title mb-1">Recent Activity</h2>
                        <p class="admin-section-subtitle mb-0">The 4 most recent reviews submitted to the platform.</p>
                    </div>
                </div>

                <div class="activity-list mt-3">
                    <?php foreach ($recentActivities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-dot"></div>
                            <div class="activity-content">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                    <div>
                                        <h3 class="activity-title mb-1"><?php echo htmlspecialchars($activity['title']); ?></h3>
                                        <p class="activity-meta mb-0"><?php echo htmlspecialchars($activity['meta']); ?></p>
                                    </div>
                                    <span class="activity-badge"><?php echo htmlspecialchars($activity['status']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>

        <div class="col-xl-4">
            <section class="admin-side-card mb-4">
                <div class="admin-profile-block text-center">
                    <div class="admin-avatar mx-auto mb-3">
                        <i class="bi bi-person-fill-gear"></i>
                    </div>
                    <h2 class="h4 mb-1"><?php echo htmlspecialchars($adminName); ?></h2>
                    <p class="text-muted mb-3">Platform Administrator</p>

                    <div class="d-grid gap-2">
                        <a href="edit-profile.php" class="btn btn-admin-primary">
                            <i class="bi bi-pencil-square me-2"></i> Edit Profile
                        </a>
                        <a href="logout.php" class="btn btn-danger-subtle-custom">
                            <i class="bi bi-box-arrow-right me-2"></i> Log Out
                        </a>
                    </div>
                </div>
            </section>

            <section class="admin-side-card mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="h5 mb-0">System Status</h2>
                    <i class="bi bi-cpu"></i>
                </div>

                <?php foreach ($systemStatus as $item): ?>
                    <div class="status-row">
                        <div>
                            <p class="status-label mb-0"><?php echo htmlspecialchars($item['label']); ?></p>
                        </div>
                        <span class="status-pill <?php echo $item['good'] ? 'good' : 'warn'; ?>">
                            <?php echo htmlspecialchars($item['value']); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </section>

            <section class="admin-side-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="h5 mb-0">Admin Notes</h2>
                    <i class="bi bi-lightbulb"></i>
                </div>

                <div class="admin-note-box">
                    <h3 class="h6 mb-2">Recommended workflow</h3>
                    <p class="mb-3 text-muted">
                        Start with moderation, clear flagged items, then check public-facing pages before logging out.
                    </p>

                    <div class="admin-note-item">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Review users first
                    </div>
                    <div class="admin-note-item">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Verify restaurant listings
                    </div>
                    <div class="admin-note-item">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Confirm homepage looks clean
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>