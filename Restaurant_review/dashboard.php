<?php
    include("includes/header.php");

    if (!isset($_SESSION['role'])) {
        header("Location: login.php");
        exit();
    }
    $role = $_SESSION['role'];

    if ($role === 'admin') {
        header("Location: admin-dashboard.php");
        exit();
    }
    
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
    $isAuthenticated = isset($_SESSION['role']);

    $allowedRoles = ['diner', 'restaurant', 'admin'];
    if (!in_array($role, $allowedRoles, true)) {
        $role = 'diner';
    }

    $roleTitle = 'Diner Dashboard';
    $roleDescription = 'Browse restaurants, search listings, and manage your dining activity.';

    if ($role === 'restaurant') {
        $roleTitle = 'Restaurant Owner Dashboard';
        $roleDescription = 'Use the diner dashboard as your base and access extra business management tools.';
    } elseif ($role === 'admin') {
        $roleTitle = 'Admin Dashboard';
        $roleDescription = 'Use platform-wide moderation tools while retaining the shared dashboard experience.';
    }

    $exampleRestaurants = [
        [
            'name' => 'Nasi & Co.',
            'cuisine' => 'Malaysian',
            'rating' => '4.7 / 5',
            'price' => '$$',
            'image' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80',
            'summary' => 'Popular for nasi lemak, grilled chicken, and quick lunch sets.'
        ],
        [
            'name' => 'Pasta House',
            'cuisine' => 'Italian',
            'rating' => '4.5 / 5',
            'price' => '$$$',
            'image' => 'https://images.unsplash.com/photo-1552566626-52f8b828add9?auto=format&fit=crop&w=900&q=80',
            'summary' => 'Known for handmade pasta, creamy sauces, and date-night ambience.'
        ],
        [
            'name' => 'Tokyo Flame',
            'cuisine' => 'Japanese',
            'rating' => '4.8 / 5',
            'price' => '$$$',
            'image' => 'https://images.unsplash.com/photo-1579027989536-b7b1f875659b?auto=format&fit=crop&w=900&q=80',
            'summary' => 'Featured for sushi platters, robata dishes, and premium tasting menus.'
        ]
    ];
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
    <div class="container py-5">
        <?php if (!$isAuthenticated): ?>
            <div class="alert alert-warning">You must log in first to access the dashboard.</div>
        <?php else: ?>
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="mb-2"><?php echo htmlspecialchars($roleTitle); ?></h1>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($roleDescription); ?></p>
                </div>
                <span class="badge bg-dark text-white px-3 py-2 text-uppercase"><?php echo htmlspecialchars($role); ?></span>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body p-4">
                            <h3 class="h4 mb-3">Shared Base Dashboard</h3>
                            <p>This is the core dashboard used by diners, restaurant owners, and admins. Users can search restaurants, browse recent activity, and access the main platform sections from here.</p>
                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <div class="border rounded p-3 h-100 bg-light-subtle">
                                        <h4 class="h6">Search Restaurants</h4>
                                        <p class="mb-3 text-muted">Open restaurant listings and search the platform.</p>
                                        <a href="index.php#search" class="btn btn-outline-primary btn-sm">Go to Search</a>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded p-3 h-100 bg-light-subtle">
                                        <h4 class="h6">Account Tools</h4>
                                        <p class="mb-3 text-muted">Access profile editing and role-specific account actions from here.</p>
                                        <?php if ($role === 'restaurant'): ?>
                                            <a href="edit-profile.php" class="btn btn-outline-secondary btn-sm">Edit Restaurant</a>
                                        <?php elseif ($role === 'diner'): ?>
                                            <a href="view-profile.php" class="btn btn-outline-secondary btn-sm">View Profile</a>
                                        <?php else: ?>
                                            <a href="view-profile.php" class="btn btn-outline-secondary btn-sm">View Profile</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
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

            <?php if ($role === 'restaurant'): ?>
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                            <div>
                                <h2 class="h4 mb-1">Restaurant Management</h2>
                                <p class="text-muted mb-0">Add another restaurant listing to your owner account or update your existing restaurant details.</p>
                            </div>
                            <a href="add-restaurant.php" class="btn btn-primary">Add Restaurant</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($role === 'diner' || $role === 'restaurant'): ?>
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                            <div>
                                <h2 class="h4 mb-1">Example Restaurants</h2>
                                <p class="text-muted mb-0">Sample restaurants are showcased here so diners and restaurant owners can immediately explore featured listings.</p>
                            </div>
                            <a href="restaurant.php" class="btn btn-outline-primary btn-sm">Open Sample Review Page</a>
                        </div>

                        <div class="row g-4">
                            <?php foreach ($exampleRestaurants as $restaurant): ?>
                                <div class="col-md-6 col-xl-4">
                                    <div class="card h-100 shadow-sm border-0 overflow-hidden">
                                        <img src="<?php echo htmlspecialchars($restaurant['image']); ?>" alt="<?php echo htmlspecialchars($restaurant['name']); ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
                                        <div class="card-body d-flex flex-column">
                                            <h3 class="h5 mb-2"><?php echo htmlspecialchars($restaurant['name']); ?></h3>
                                            <p class="mb-2 text-muted"><?php echo htmlspecialchars($restaurant['summary']); ?></p>
                                            <p class="mb-1"><strong>Cuisine:</strong> <?php echo htmlspecialchars($restaurant['cuisine']); ?></p>
                                            <p class="mb-1"><strong>Rating:</strong> <?php echo htmlspecialchars($restaurant['rating']); ?></p>
                                            <p class="mb-3"><strong>Price:</strong> <?php echo htmlspecialchars($restaurant['price']); ?></p>
                                            <a href="restaurant.php" class="btn btn-primary btn-sm mt-auto">View Restaurant</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php include("includes/footer.php"); ?>
</body>
</html>
