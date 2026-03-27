<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/includes/restaurant-db.php';

$isAuthenticated = isset($_SESSION['role']);
$currentRole = $_SESSION['role'] ?? 'guest';
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);

$restaurantId = isset($_GET['id']) && ctype_digit($_GET['id']) && (int) $_GET['id'] > 0 ? (int) $_GET['id'] : null;
$reviewError = '';
$reviewSuccess = isset($_GET['review']) && $_GET['review'] === 'saved' ? 'Your review has been submitted successfully.' : '';
$dataError = '';

$connection = getDatabaseConnection($dataError);
$restaurant = null;
$reviews = [];

if ($connection) {
    if ($restaurantId === null) {
        $restaurantId = getFirstRestaurantId($connection, $dataError);
    }

    if ($restaurantId === null && $dataError === '') {
        $dataError = 'No restaurants are available yet.';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
        if ($restaurantId === null) {
            $reviewError = 'No restaurant is available for review submission.';
        } elseif (!$isAuthenticated || $currentRole !== 'diner' || $currentUserId < 1) {
            $reviewError = 'Only signed-in diners can leave reviews.';
        } else {
            $reviewRating = trim($_POST['review_rating'] ?? '');
            $reviewComment = trim($_POST['review_comment'] ?? '');

            if (!ctype_digit($reviewRating) || (int) $reviewRating < 1 || (int) $reviewRating > 5 || $reviewComment === '') {
                $reviewError = 'Please provide a valid rating (1 to 5) and a comment.';
            } else {
                $reviewPayload = [
                    'UserId'       => $currentUserId,
                    'RestaurantID' => $restaurantId,
                    'Rating'       => (int) $reviewRating,
                    'Comments'     => $reviewComment
                ];

                if (insertReviewRecord($connection, $reviewPayload, $reviewError)) {
                    // Redirect to tip prompt after a successful review submission
                    header('Location: tip-prompt.php?restaurant_id=' . urlencode((string) $restaurantId));
                    $connection->close();
                    exit;
                }
            }
        }
    }

    if ($restaurantId !== null) {
        $restaurant = getRestaurantById($connection, $restaurantId, $dataError);
    }
    if ($restaurant) {
        $reviews = getRestaurantReviews($connection, $restaurantId, $dataError);
    }

    $connection->close();
}

if (!$restaurant && $dataError === '') {
    $dataError = 'Restaurant was not found.';
}

$averageRating = null;
if (count($reviews) > 0) {
    $sum = 0;
    foreach ($reviews as $reviewRow) {
        $sum += (int) $reviewRow['Rating'];
    }
    $averageRating = number_format($sum / count($reviews), 1);
}

$openingHoursDisplay = '';
if ($restaurant && !empty($restaurant['OpeningHours'])) {
    $openingHoursDisplay = date('Y-m-d H:i', strtotime($restaurant['OpeningHours']));
}

$heroImage = $restaurant['ImageUrl'] ?? '';
if ($heroImage === '') {
    $heroImage = 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1200&q=80';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodview - Restaurant Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>
    <?php include("includes/header.php"); ?>

    <main class="py-5">
        <div class="container">
            <?php if ($dataError !== ''): ?>
                <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($dataError); ?></div>
            <?php endif; ?>

            <?php if ($restaurant): ?>
            <div class="card shadow-sm border-0 p-4 mb-4">
                <div class="row g-4 align-items-stretch">
                    <div class="col-lg-6">
                        <div class="h-100">
                            <img src="<?php echo htmlspecialchars($heroImage); ?>" alt="<?php echo htmlspecialchars($restaurant['RestaurantName']); ?>" class="img-fluid rounded w-100 h-100 object-fit-cover" style="min-height: 320px; max-height: 420px; object-fit: cover;">
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="h-100 d-flex flex-column justify-content-center border rounded p-4 bg-light">
                            <h1 class="h2 mb-3"><?php echo htmlspecialchars($restaurant['RestaurantName']); ?></h1>
                            <p class="mb-2"><strong>Rating:</strong> <?php echo $averageRating !== null ? htmlspecialchars($averageRating . ' / 5') : 'No ratings yet'; ?></p>
                            <p class="mb-2"><strong>Cuisine:</strong> <?php echo htmlspecialchars($restaurant['CusineType']); ?></p>
                            <p class="mb-2"><strong>Price Range:</strong> <?php echo htmlspecialchars($restaurant['PriceRange']); ?></p>
                            <p class="mb-2"><strong>Address:</strong> <?php echo htmlspecialchars($restaurant['Address']); ?></p>
                            <p class="mb-0"><strong>Opening Hours:</strong> <?php echo htmlspecialchars($openingHoursDisplay); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($isAuthenticated && $currentRole === 'diner'): ?>
                <div class="card shadow-sm border-0 p-4 mb-4">
                    <h2 class="h4 mb-3">Leave a Review</h2>
                    <p class="text-muted">As a diner, you can submit a review for this restaurant.</p>

                    <?php if ($reviewError !== ''): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($reviewError); ?></div>
                    <?php endif; ?>

                    <?php if ($reviewSuccess !== ''): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($reviewSuccess); ?></div>
                    <?php endif; ?>

                    <form method="post" action="restaurant.php?id=<?php echo urlencode((string) $restaurantId); ?>">
                        <input type="hidden" name="submit_review" value="1">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="reviewer_name" class="form-label">Your Name</label>
                                <input type="text" class="form-control" id="reviewer_name" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="review_rating" class="form-label">Rating</label>
                                <select class="form-select" id="review_rating" name="review_rating">
                                    <option value="">Select a rating</option>
                                    <option value="5" <?php echo (($_POST['review_rating'] ?? '') === '5') ? 'selected' : ''; ?>>5</option>
                                    <option value="4" <?php echo (($_POST['review_rating'] ?? '') === '4') ? 'selected' : ''; ?>>4</option>
                                    <option value="3" <?php echo (($_POST['review_rating'] ?? '') === '3') ? 'selected' : ''; ?>>3</option>
                                    <option value="2" <?php echo (($_POST['review_rating'] ?? '') === '2') ? 'selected' : ''; ?>>2</option>
                                    <option value="1" <?php echo (($_POST['review_rating'] ?? '') === '1') ? 'selected' : ''; ?>>1</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="review_comment" class="form-label">Comment</label>
                                <textarea class="form-control" id="review_comment" name="review_comment" rows="4" placeholder="Share your dining experience"><?php echo htmlspecialchars($_POST['review_comment'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Submit Review</button>
                            <a href="tip-prompt.php?restaurant_id=<?php echo $restaurantId; ?>"
                                class="btn btn-warning ms-2">
                                    <i class="bi bi-cash-coin"></i> Leave a Tip
                            </a>
                        </div>
                    </form>
                </div>
            <?php elseif ($isAuthenticated && $currentRole !== 'diner'): ?>
                <div class="alert alert-info mb-4">Only diner accounts can submit reviews. Restaurant owners and admins can still view all reviews.</div>
            <?php else: ?>
                <div class="alert alert-secondary mb-4">Log in as a diner to leave a review for this restaurant.</div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 p-4">
                <h2 class="h4 mb-4">Reviews Section</h2>
                <div class="row g-3">
                    <?php foreach ($reviews as $index => $review): ?>
                        <div class="col-12">
                            <div class="border rounded p-3 bg-white">
                                <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                                    <strong>Review <?php echo $index + 1; ?> - <?php echo htmlspecialchars($review['reviewer_name'] ?? 'Anonymous'); ?></strong>
                                    <span><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($review['ReviewDate']))); ?></span>
                                </div>
                                <p class="mb-2"><strong>Rating:</strong> <?php echo htmlspecialchars($review['Rating']); ?> / 5</p>
                                <p class="mb-0"><?php echo htmlspecialchars($review['Comments']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($reviews) === 0): ?>
                        <div class="col-12">
                            <div class="alert alert-light border mb-0">No reviews yet for this restaurant.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include("includes/footer.php"); ?>
</body>
</html>
