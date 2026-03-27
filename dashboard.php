<?php
// ── All PHP logic MUST run before any HTML output ────────────────────────────
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];

if ($role === 'admin') {
    header("Location: admin-dashboard.php");
    exit();
}

$isAuthenticated = true;

$allowedRoles = ['diner', 'restaurant', 'admin'];
if (!in_array($role, $allowedRoles, true)) {
    $role = 'diner';
}

$roleTitle       = 'Diner Dashboard';
$roleDescription = 'Browse restaurants, search listings, and manage your dining activity.';

if ($role === 'restaurant') {
    $roleTitle       = 'Restaurant Owner Dashboard';
    $roleDescription = 'Use the diner dashboard as your base and access extra business management tools.';
} elseif ($role === 'admin') {
    $roleTitle       = 'Admin Dashboard';
    $roleDescription = 'Use platform-wide moderation tools while retaining the shared dashboard experience.';
}

// ── Example restaurants — fetched live from DB ───────────────────────────────
$exampleRestaurants = [];

// ── DB: fetch restaurant dropdown list and selected restaurant details ────────
require_once __DIR__ . '/includes/restaurant-db.php';

$dbErr                  = '';
$dbConn                 = getDatabaseConnection($dbErr);
$restaurantDropdownList = [];
$selectedRestaurant     = null;
$selectedReviews        = [];
$reviewErr              = '';

if ($dbConn) {
    $listResult = $dbConn->query(
        'SELECT idRestaurants, RestaurantName FROM Restaurants ORDER BY idRestaurants ASC'
    );
    if ($listResult) {
        while ($r = $listResult->fetch_assoc()) {
            $restaurantDropdownList[] = $r;
        }
        $listResult->free();
    }

    // Fetch first 3 restaurants for Example Restaurants cards
    $exResult = $dbConn->query(
        'SELECT r.idRestaurants,
                r.RestaurantName,
                r.CusineType,
                r.PriceRange,
                r.Address,
                r.PhoneNum,
                r.OpeningHours,
                img.ImageUrl
         FROM   Restaurants r
         LEFT   JOIN RestaurantImages img ON img.idRestaurants = r.idRestaurants
         ORDER  BY r.idRestaurants ASC
         LIMIT  3'
    );
    if ($exResult) {
        while ($er = $exResult->fetch_assoc()) {
            $exampleRestaurants[] = $er;
        }
        $exResult->free();
    }

    $selectedId = isset($_GET['rest_id']) ? (int) $_GET['rest_id'] : 0;
    if ($selectedId > 0) {
        $selectedRestaurant = getRestaurantById($dbConn, $selectedId, $reviewErr);
        if ($selectedRestaurant) {
            $selectedReviews = getRestaurantReviews($dbConn, $selectedId, $reviewErr);
        }
    }

    $dbConn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodview - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include("includes/header.php"); ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
            <div>
                <h1 class="mb-2"><?php echo htmlspecialchars($roleTitle); ?></h1>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($roleDescription); ?></p>
            </div>
            <span class="badge bg-dark text-white px-3 py-2 text-uppercase"><?php echo htmlspecialchars($role); ?></span>
        </div>

        <!-- ── Main two-column row ──────────────────────────────────────── -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-4">
                        <h3 class="h4 mb-3">Shared Base Dashboard</h3>
                        <p>This is the core dashboard used by diners, restaurant owners, and admins. Users can search restaurants, browse recent activity, and access the main platform sections from here.</p>

                        <!-- Go to Search -->
                        <div class="border rounded p-3 bg-light-subtle mt-3">
                            <h4 class="h6 mb-1">Search Restaurants</h4>
                            <p class="mb-3 text-muted small">Open restaurant listings and search the platform.</p>
                            <a href="index.php#search" class="btn btn-outline-primary btn-sm">Go to Search</a>
                        </div>

                        <!-- Browse by Restaurant ID dropdown -->
                        <div class="border rounded p-3 mt-3">
                            <h4 class="h6 mb-1">Browse by Restaurant ID</h4>
                            <p class="mb-3 text-muted small">Select a restaurant from the list to view its details and reviews.</p>

                            <?php if (empty($restaurantDropdownList)): ?>
                                <p class="text-muted small mb-0">No restaurants found in the database.</p>
                            <?php else: ?>
                                <form method="GET" action="dashboard.php" class="d-flex gap-2 flex-wrap align-items-end">
                                    <div class="flex-grow-1">
                                        <label for="rest_id" class="form-label fw-semibold mb-1">Restaurant ID</label>
                                        <select name="rest_id" id="rest_id" class="form-select">
                                            <option value="">-- Select a Restaurant --</option>
                                            <?php foreach ($restaurantDropdownList as $r): ?>
                                                <option value="<?php echo (int) $r['idRestaurants']; ?>"
                                                    <?php echo (isset($_GET['rest_id']) && (int) $_GET['rest_id'] === (int) $r['idRestaurants']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($r['RestaurantName']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">View Reviews</button>
                                </form>
                            <?php endif; ?>

                            <!-- Inline review results -->
                            <?php if ($selectedRestaurant): ?>
                                <hr class="my-3">
                                <div class="mb-3">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($selectedRestaurant['RestaurantName']); ?></h5>
                                    <p class="mb-1 text-muted small">
                                        <strong>Cuisine:</strong> <?php echo htmlspecialchars($selectedRestaurant['CusineType']); ?> &nbsp;|&nbsp;
                                        <strong>Price:</strong> <?php echo htmlspecialchars($selectedRestaurant['PriceRange']); ?>
                                    </p>
                                    <p class="mb-1 text-muted small">
                                        <strong>Address:</strong> <?php echo htmlspecialchars($selectedRestaurant['Address']); ?>
                                    </p>
                                    <p class="mb-0 text-muted small">
                                        <strong>Phone:</strong> <?php echo htmlspecialchars($selectedRestaurant['PhoneNum']); ?> &nbsp;|&nbsp;
                                        <strong>Hours:</strong> <?php echo htmlspecialchars($selectedRestaurant['OpeningHours']); ?>
                                    </p>
                                </div>

                                <?php if (empty($selectedReviews)): ?>
                                    <p class="text-muted small mb-0">No reviews have been submitted for this restaurant yet.</p>
                                <?php else: ?>
                                    <h6 class="mb-2">Reviews (<?php echo count($selectedReviews); ?>)</h6>
                                    <div class="d-flex flex-column gap-2">
                                        <?php foreach ($selectedReviews as $rev): ?>
                                            <div class="border rounded p-3 bg-white">
                                                <div class="d-flex justify-content-between flex-wrap gap-1 mb-1">
                                                    <strong><?php echo htmlspecialchars($rev['reviewer_name'] ?? 'Anonymous'); ?></strong>
                                                    <span class="text-muted small"><?php echo htmlspecialchars(date('Y-m-d', strtotime($rev['ReviewDate']))); ?></span>
                                                </div>
                                                <p class="mb-1">
                                                    <strong>Rating:</strong> <?php echo (int) $rev['Rating']; ?> / 5
                                                    <span class="text-warning ms-1">
                                                        <?php for ($s = 1; $s <= 5; $s++): ?>
                                                            <?php echo $s <= (int) $rev['Rating'] ? '&#9733;' : '&#9734;'; ?>
                                                        <?php endfor; ?>
                                                    </span>
                                                </p>
                                                <p class="mb-0 small"><?php echo htmlspecialchars($rev['Comments']); ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php elseif (isset($_GET['rest_id']) && (int) $_GET['rest_id'] > 0): ?>
                                <div class="alert alert-warning mt-3 mb-0">Restaurant not found. It may have been removed.</div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-4">
                        <h3 class="h5 mb-3">Role-Based Options</h3>
                        <?php if ($role === 'diner'): ?>
                            <p class="mb-2">As a diner, you can browse restaurants, read reviews, manage your profile, and discover highlighted places to try next.</p>
                            <ul class="mb-0">
                                <li>Search restaurants</li>
                                <li>View reviews</li>
                                <li>View profile</li>
                            </ul>
                        <?php elseif ($role === 'restaurant'): ?>
                            <p class="mb-2">As a restaurant owner, you keep the diner dashboard and gain extra business tools.</p>
                            <ul class="mb-0">
                                <li>Edit restaurant information</li>
                                <li>Add a restaurant</li>
                                <li>View customer reviews</li>
                            </ul>
                        <?php else: ?>
                            <p class="mb-2">As an admin, you keep the shared dashboard and gain moderation controls.</p>
                            <ul class="mb-0">
                                <li>Edit profile</li>
                                <li>Moderate users and restaurants</li>
                                <li>Delete reviews from the homepage</li>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Restaurant Management (owners only) ──────────────────────── -->
        <?php if ($role === 'restaurant'): ?>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div>
                            <h2 class="h4 mb-1">Restaurant Management</h2>
                            <p class="text-muted mb-0">Add another restaurant listing to your owner account or update your existing restaurant details.</p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="add-restaurant.php" class="btn btn-primary">Add Restaurant</a>
                            <a href="edit-profile.php" class="btn btn-outline-secondary">Edit Existing Restaurant</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ── Example Restaurants ──────────────────────────────────────── -->
        <?php if ($role === 'diner' || $role === 'restaurant'): ?>
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                        <div>
                            <h2 class="h4 mb-1">Restaurant Reviews</h2>
                        </div>
                    </div>

                    <?php if (empty($exampleRestaurants)): ?>
                        <p class="text-muted">No restaurants found in the database yet. <a href="add-restaurant.php">Add one now</a>.</p>
                    <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($exampleRestaurants as $restaurant): ?>
                            <div class="col-md-6 col-xl-4">
                                <div class="card h-100 shadow-sm border-0 overflow-hidden">
                                    <img src="<?php echo htmlspecialchars($restaurant['ImageUrl'] ?: 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80'); ?>"
                                         alt="<?php echo htmlspecialchars($restaurant['RestaurantName']); ?>"
                                         class="card-img-top" style="height: 200px; object-fit: cover;">
                                    <div class="card-body d-flex flex-column">
                                        <h3 class="h5 mb-2"><?php echo htmlspecialchars($restaurant['RestaurantName']); ?></h3>
                                        <p class="mb-1"><strong>Cuisine:</strong> <?php echo htmlspecialchars($restaurant['CusineType']); ?></p>
                                        <p class="mb-1"><strong>Price:</strong> <?php echo htmlspecialchars($restaurant['PriceRange']); ?></p>
                                        <p class="mb-1"><strong>Address:</strong> <?php echo htmlspecialchars($restaurant['Address']); ?></p>
                                        <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($restaurant['PhoneNum']); ?></p>
                                        <p class="mb-3"><strong>Hours:</strong> <?php echo htmlspecialchars($restaurant['OpeningHours']); ?></p>
                                        <a href="sample-review.php?id=<?php echo (int) $restaurant['idRestaurants']; ?>" class="btn btn-primary btn-sm mt-auto">View Reviews</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include("includes/footer.php"); ?>
</body>
</html>
