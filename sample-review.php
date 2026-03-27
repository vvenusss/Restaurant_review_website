<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/includes/restaurant-db.php';

$isAuthenticated = isset($_SESSION['role']);
$currentRole     = $_SESSION['role'] ?? 'guest';

// ── Resolve restaurant ID ─────────────────────────────────────────────────
$restaurantId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$restaurant  = null;
$reviews     = [];
$dbErr       = '';
$dbAvailable = false;

$dbConn = getDatabaseConnection($dbErr);
if ($dbConn) {
    $dbAvailable = true;

    // If no ID given, pick the first one available
    if ($restaurantId <= 0) {
        $restaurantId = getFirstRestaurantId($dbConn, $dbErr) ?? 0;
    }

    if ($restaurantId > 0) {
        $restaurant = getRestaurantById($dbConn, $restaurantId, $dbErr);
        if ($restaurant) {
            $reviews = getRestaurantReviews($dbConn, $restaurantId, $dbErr);
        }
    }

    $dbConn->close();
}

// ── Average rating ────────────────────────────────────────────────────────
$averageRating = null;
if (!empty($reviews)) {
    $averageRating = number_format(
        array_sum(array_column($reviews, 'Rating')) / count($reviews),
        1
    );
}

// ── Fallback image ────────────────────────────────────────────────────────
$heroImage = !empty($restaurant['ImageUrl'])
    ? $restaurant['ImageUrl']
    : 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1200&q=80';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodview - <?php echo $restaurant ? htmlspecialchars($restaurant['RestaurantName']) : 'Restaurant Reviews'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include("includes/header.php"); ?>

    <main class="py-5">
        <div class="container">

            <?php if (!$dbAvailable): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Unable to connect to the database. Please try again later.
                </div>

            <?php elseif (!$restaurant): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-search me-2"></i>
                    No restaurant found<?php echo $restaurantId > 0 ? ' for ID ' . $restaurantId : ''; ?>.
                    <a href="dashboard.php" class="alert-link">Return to Dashboard</a>
                </div>

            <?php else: ?>

                <!-- Restaurant hero card -->
                <div class="card shadow-sm border-0 p-4 mb-4">
                    <div class="row g-4 align-items-stretch">
                        <div class="col-lg-6">
                            <img src="<?php echo htmlspecialchars($heroImage); ?>"
                                 alt="<?php echo htmlspecialchars($restaurant['RestaurantName']); ?>"
                                 class="img-fluid rounded w-100"
                                 style="min-height: 320px; max-height: 420px; object-fit: cover;">
                        </div>
                        <div class="col-lg-6">
                            <div class="h-100 d-flex flex-column justify-content-center border rounded p-4 bg-light">
                                <h1 class="h2 mb-3"><?php echo htmlspecialchars($restaurant['RestaurantName']); ?></h1>

                                <?php if ($averageRating !== null): ?>
                                    <p class="mb-2">
                                        <strong>Average Rating:</strong> <?php echo $averageRating; ?> / 5
                                        <span class="ms-1 text-warning">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="bi bi-star<?php echo $i <= round((float) $averageRating) ? '-fill' : ''; ?>"></i>
                                            <?php endfor; ?>
                                        </span>
                                    </p>
                                <?php else: ?>
                                    <p class="mb-2 text-muted"><em>No ratings yet.</em></p>
                                <?php endif; ?>

                                <p class="mb-2"><strong>Restaurant ID:</strong> <?php echo (int) $restaurant['idRestaurants']; ?></p>
                                <p class="mb-2"><strong>Cuisine:</strong> <?php echo htmlspecialchars($restaurant['CusineType']); ?></p>
                                <p class="mb-2"><strong>Price Range:</strong> <?php echo htmlspecialchars($restaurant['PriceRange']); ?></p>
                                <p class="mb-2"><strong>Address:</strong> <?php echo htmlspecialchars($restaurant['Address']); ?></p>
                                <p class="mb-2"><strong>Phone:</strong> <?php echo htmlspecialchars($restaurant['PhoneNum']); ?></p>
                                <p class="mb-2"><strong>Hours:</strong>
                                    <?php
                                        $oh = $restaurant['OpeningHours'];
                                        $ch = $restaurant['ClosingHours'];
                                        // Display just the time portion if stored as datetime
                                        echo htmlspecialchars(strlen($oh) > 8 ? date('h:i A', strtotime($oh)) : $oh);
                                        echo ' &ndash; ';
                                        echo htmlspecialchars(strlen($ch) > 8 ? date('h:i A', strtotime($ch)) : $ch);
                                    ?>
                                </p>
                                <p class="mb-0"><strong>Open:</strong> <?php echo htmlspecialchars($restaurant['OpeningDays']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Review submission notice -->
                <?php if ($isAuthenticated && $currentRole === 'diner'): ?>
                    <div class="alert alert-secondary mb-4">
                        <i class="bi bi-pencil-square me-1"></i>
                        Want to leave a review? Visit the full restaurant page via
                        <a href="restaurant.php?id=<?php echo (int) $restaurant['idRestaurants']; ?>">restaurant.php</a>.
                    </div>
                <?php elseif (!$isAuthenticated): ?>
                    <div class="alert alert-secondary mb-4">
                        <a href="login.php">Log in as a diner</a> to submit a review for this restaurant.
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-4">
                        Only diner accounts can submit reviews. Restaurant owners and admins can still view all reviews.
                    </div>
                <?php endif; ?>

                <!-- Reviews section -->
                <div class="card shadow-sm border-0 p-4">
                    <h2 class="h4 mb-4">
                        Reviews
                        <?php if (!empty($reviews)): ?>
                            <span class="badge bg-secondary ms-2"><?php echo count($reviews); ?></span>
                        <?php endif; ?>
                    </h2>

                    <?php if (empty($reviews)): ?>
                        <p class="text-muted">No reviews have been submitted for this restaurant yet.</p>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($reviews as $index => $review): ?>
                                <div class="col-12">
                                    <div class="border rounded p-3 bg-white">
                                        <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                                            <strong>
                                                Review <?php echo $index + 1; ?> &mdash; <?php echo htmlspecialchars($review['reviewer_name'] ?? 'Anonymous'); ?>
                                            </strong>
                                            <span class="text-muted small">
                                                <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($review['ReviewDate']))); ?>
                                            </span>
                                        </div>
                                        <p class="mb-2">
                                            <strong>Rating:</strong>
                                            <?php echo (int) $review['Rating']; ?> / 5
                                            <span class="ms-1 text-warning">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="bi bi-star<?php echo $i <= (int) $review['Rating'] ? '-fill' : ''; ?>"></i>
                                                <?php endfor; ?>
                                            </span>
                                        </p>
                                        <p class="mb-0"><?php echo htmlspecialchars($review['Comments']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4 pt-3 border-top">
                        <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                    </div>
                </div>

            <?php endif; ?>

        </div>
    </main>

    <?php include("includes/footer.php"); ?>
</body>
</html>
