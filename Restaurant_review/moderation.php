<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$isAuthenticated = isset($_SESSION['role']);
$currentRole = $_SESSION['role'] ?? 'guest';

if (!$isAuthenticated || $currentRole !== 'admin') {
    header('Location: login.php');
    exit;
}

$users = [
    ['id' => 'DIN-001', 'name' => 'Sarah Lee', 'email' => 'sarah@example.com', 'role' => 'Diner'],
    ['id' => 'DIN-002', 'name' => 'Jason Lim', 'email' => 'jason@example.com', 'role' => 'Diner'],
    ['id' => 'OWN-001', 'name' => 'Amir Hassan', 'email' => 'owner@foodview.com', 'role' => 'Restaurant Owner']
];

$restaurants = [
    ['id' => 'REST-001', 'name' => 'Nasi & Co.', 'owner' => 'Amir Hassan', 'cuisine' => 'Malaysian', 'price' => '$$'],
    ['id' => 'REST-002', 'name' => 'Pasta House', 'owner' => 'Melissa Tan', 'cuisine' => 'Italian', 'price' => '$$$'],
    ['id' => 'REST-003', 'name' => 'Tokyo Flame', 'owner' => 'Daniel Wong', 'cuisine' => 'Japanese', 'price' => '$$$']
];

$actionMessage = '';
if (isset($_GET['deleted'])) {
    $deletedType = $_GET['deleted'] === 'restaurant' ? 'restaurant' : 'user';
    $actionMessage = 'The selected ' . $deletedType . ' has been removed from the moderation view.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodview - Moderation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include('includes/header.php'); ?>

    <main class="py-5">
        <div class="container">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-2">Admin Moderation</h1>
                    <p class="text-muted mb-0">Review user and restaurant records, then use the delete controls to moderate the platform interface.</p>
                </div>
                <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
            </div>

            <?php if ($actionMessage !== ''): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($actionMessage); ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body p-4">
                            <h2 class="h5 mb-3">All Users</h2>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>User ID</th>
                                            <th>Name</th>
                                            <th>Role</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                                <td>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($user['name']); ?></div>
                                                    <div class="text-muted small"><?php echo htmlspecialchars($user['email']); ?></div>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                                <td class="text-end">
                                                    <a href="moderation.php?deleted=user" class="btn btn-outline-danger btn-sm">Delete</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body p-4">
                            <h2 class="h5 mb-3">All Restaurants</h2>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Restaurant ID</th>
                                            <th>Name</th>
                                            <th>Cuisine</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($restaurants as $restaurant): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($restaurant['id']); ?></td>
                                                <td>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($restaurant['name']); ?></div>
                                                    <div class="text-muted small">Owner: <?php echo htmlspecialchars($restaurant['owner']); ?></div>
                                                </td>
                                                <td><?php echo htmlspecialchars($restaurant['cuisine']); ?> · <?php echo htmlspecialchars($restaurant['price']); ?></td>
                                                <td class="text-end">
                                                    <a href="moderation.php?deleted=restaurant" class="btn btn-outline-danger btn-sm">Delete</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include('includes/footer.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
