<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$isAuthenticated = isset($_SESSION['role']);
$currentRole = $_SESSION['role'] ?? 'guest';
$roleLabel = 'Guest';

if ($currentRole === 'diner') {
    $roleLabel = 'Diner';
} elseif ($currentRole === 'restaurant') {
    $roleLabel = 'Restaurant Owner';
} elseif ($currentRole === 'admin') {
    $roleLabel = 'Admin';
}

$featuredReviews = [
    [
        'restaurant' => 'Nasi & Co.',
        'reviewer' => 'Sarah Lee',
        'rating' => '5 / 5',
        'comment' => 'Great ambience, fast service, and excellent nasi lemak.'
    ],
    [
        'restaurant' => 'Pasta House',
        'reviewer' => 'Jason Lim',
        'rating' => '4 / 5',
        'comment' => 'Very solid pasta dishes and a comfortable dinner setting.'
    ],
    [
        'restaurant' => 'Tokyo Flame',
        'reviewer' => 'Alicia Tan',
        'rating' => '5 / 5',
        'comment' => 'Fresh sushi, premium ingredients, and memorable service.'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodview - Food Review</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include("includes/header.php"); ?>

    <header class="bg-light p-5 border-bottom">
        <div class="container text-center">
            <h1>Welcome to Foodview</h1>
            <p class="lead mb-3">Your trusted platform for food reviews, restaurant discovery, and role-based management.</p>
            <?php if ($isAuthenticated): ?>
                <span class="badge bg-dark mb-3">Logged in as <?php echo htmlspecialchars($roleLabel); ?></span>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="#search" class="btn btn-primary">Search Restaurants</a>
                    <a href="dashboard.php" class="btn btn-outline-dark">Go to Dashboard</a>
                </div>
            <?php else: ?>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="login.php" class="btn btn-primary">Login</a>
                    <a href="signup.php" class="btn btn-outline-dark">Sign Up</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <main class="py-5">
        <div class="container">
            <section id="search" class="mb-5">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card shadow-sm border-0">
                            <div class="card-body p-4">
                                <h2 class="h4 mb-3">Search Restaurants</h2>
                                <p class="text-muted">This shared search area appears as part of the main experience for diners, restaurant owners, and admins after login.</p>
                                <form>
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <label for="searchKeyword" class="form-label">Restaurant, cuisine, or location</label>
                                            <input type="text" id="searchKeyword" class="form-control" placeholder="Search restaurants">
                                            <script>
                                            document.getElementById('searchBtn').addEventListener('click', function(e) {
                                                e.preventDefault();
                                                const q = document.getElementById('searchKeyword').value;
                                                window.location.href = 'restaurant.php?search=' + encodeURIComponent(q);
                                            });
                                            </script>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <a href="restaurant.php?search=<?php echo urlencode($_GET['q'] ?? ''); ?>" 
                                                class="btn btn-primary w-100" id="searchBtn">Search</a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mb-5">
                <div class="row g-4 justify-content-center">
                    <div class="col-md-5">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body">
                                <h3 class="h5">Diner Base Experience</h3>
                                <p class="mb-0">Diners use the base experience to search restaurants, read reviews, and manage their account.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body">
                                <h3 class="h5">Restaurant Owner Additions</h3>
                                <p class="mb-0">Restaurant owners keep the diner base experience and gain business management options.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                            <div>
                                <h2 class="h4 mb-1">Featured Reviews</h2>
                                <p class="text-muted mb-0">Everyone sees the same homepage review feed, while admins get moderation controls beside each review.</p>
                            </div>
                        </div>
                        <div class="row g-3">
                            <?php foreach ($featuredReviews as $index => $review): ?>
                                <div class="col-12">
                                    <div class="border rounded p-3 bg-white">
                                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                                            <div>
                                                <h3 class="h6 mb-1"><?php echo htmlspecialchars($review['restaurant']); ?></h3>
                                                <p class="mb-1 text-muted">Reviewed by <?php echo htmlspecialchars($review['reviewer']); ?></p>
                                                <p class="mb-1"><strong>Rating:</strong> <?php echo htmlspecialchars($review['rating']); ?></p>
                                                <p class="mb-0"><?php echo htmlspecialchars($review['comment']); ?></p>
                                            </div>
                                            <?php if ($isAuthenticated && $currentRole === 'admin'): ?>
                                                <button type="button" class="btn btn-outline-danger btn-sm">Delete Review</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <?php include("includes/footer.php"); ?>
    <script src="js/script.js"></script>
</body>
</html>
