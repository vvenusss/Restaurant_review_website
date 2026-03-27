<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/includes/restaurant-db.php';

$isAuthenticated = isset($_SESSION['role']);
$currentRole = $_SESSION['role'] ?? 'guest';
$searchKeyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$restaurants = [];
$dataError = '';

$connection = getDatabaseConnection($dataError);

if ($connection) {
    $restaurants = searchRestaurants($connection, $searchKeyword, $dataError);
    $connection->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodview - Search Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include("includes/header.php"); ?>

    <main class="py-5">
        <div class="container">
            <div class="row mb-5">
                <div class="col-lg-8 offset-lg-2">
                    <h1 class="mb-4">Search Results</h1>
                    
                    <!-- Search Form -->
                    <form method="GET" action="search-results.php" class="mb-4">
                        <div class="input-group">
                            <input type="text" name="keyword" class="form-control form-control-lg" 
                                   placeholder="Restaurant, cuisine, or location..." 
                                   value="<?php echo htmlspecialchars($searchKeyword); ?>">
                            <button class="btn btn-primary" type="submit">Search</button>
                        </div>
                    </form>

                    <?php if ($dataError): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($dataError); ?>
                        </div>
                    <?php elseif (empty($restaurants)): ?>
                        <div class="alert alert-info" role="alert">
                            <?php if ($searchKeyword === ''): ?>
                                No restaurants available yet.
                            <?php else: ?>
                                No restaurants found matching "<?php echo htmlspecialchars($searchKeyword); ?>". 
                                <a href="search-results.php">View all restaurants</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-4">
                            Found <?php echo count($restaurants); ?> 
                            restaurant<?php echo count($restaurants) !== 1 ? 's' : ''; ?>
                            <?php if ($searchKeyword): ?>
                                matching "<?php echo htmlspecialchars($searchKeyword); ?>"
                            <?php endif; ?>
                        </p>

                        <div class="row g-4">
                            <?php foreach ($restaurants as $restaurant): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card h-100 shadow-sm hover-card" style="cursor: pointer;" 
                                         onclick="location.href='restaurant.php?id=<?php echo (int) $restaurant['idRestaurants']; ?>'">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <?php echo htmlspecialchars($restaurant['RestaurantName']); ?>
                                            </h5>
                                            <p class="card-text text-muted small">
                                                <i class="bi bi-geo-alt"></i> 
                                                <?php echo htmlspecialchars($restaurant['Address']); ?>
                                            </p>
                                            <p class="card-text small">
                                                <span class="badge bg-light text-dark">
                                                    <?php echo htmlspecialchars($restaurant['CusineType']); ?>
                                                </span>
                                            </p>
                                            <p class="card-text small">
                                                <i class="bi bi-currency-dollar"></i> 
                                                <?php echo htmlspecialchars($restaurant['PriceRange']); ?>
                                            </p>
                                        </div>
                                        <div class="card-footer bg-white border-top">
                                            <a href="restaurant.php?id=<?php echo (int) $restaurant['idRestaurants']; ?>" 
                                               class="btn btn-sm btn-primary w-100">
                                                View Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include("includes/footer.php"); ?>
</body>
</html>
