<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$isAuthenticated = isset($_SESSION['role']);
$currentRole = $_SESSION['role'] ?? 'guest';

require_once __DIR__ . '/includes/restaurant-db.php';

if (!$isAuthenticated || $currentRole !== 'restaurant') {
    header('Location: login.php');
    exit;
}

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $requiredFields = [
            'RestaurantName' => 'restaurant name',
            'OwnerId' => 'owner ID',
            'Address' => 'address',
            'PhoneNum' => 'phone number',
            'CusineType' => 'type of cuisine',
            'OpeningHours' => 'opening hours',
            'ClosingHours' => 'closing hours',
            'OpeningDays' => 'opening days',
            'PriceRange' => 'price range'
        ];

        foreach ($requiredFields as $field => $label) {
            if (trim($_POST[$field] ?? '') === '') {
                $errorMessage = 'Please enter the ' . $label . ' before adding a restaurant.';
                break;
            }
        }

        if ($errorMessage === '' && (!ctype_digit(trim($_POST['OwnerId'] ?? '')) || (int) $_POST['OwnerId'] < 1)) {
            $errorMessage = 'Owner ID must be a valid positive integer.';
        }

        if ($errorMessage === '') {
            $restaurantPayload = [
                'IdRestaurants' => null,
                'RestaurantName' => trim($_POST['RestaurantName']),
                'OwnerId' => (int) trim($_POST['OwnerId']),
                'Address' => trim($_POST['Address']),
                'PhoneNum' => trim($_POST['PhoneNum']),
                'CusineType' => trim($_POST['CusineType']),
                'OpeningHours' => trim($_POST['OpeningHours']),
                'ClosingHours' => trim($_POST['ClosingHours']),
                'OpeningDays' => trim($_POST['OpeningDays']),
                'PriceRange' => trim($_POST['PriceRange'])
            ];

            $connection = getDatabaseConnection($errorMessage);

            if ($connection) {
                if (ownerIdExists($connection, $restaurantPayload['OwnerId'], $errorMessage)) {
                    $insertedId = insertRestaurantRecord($connection, $restaurantPayload, $errorMessage);

                    if ($insertedId !== false) {
                        $successMessage = 'Restaurant added successfully. New IdRestaurants: ' . $insertedId . '.';
                        $_POST = [];
                    }
                }

                $connection->close();
            }
        }
    } catch (Throwable $e) {
        $errorMessage = 'Restaurant creation failed due to a server error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodview - Add Restaurant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include('includes/header.php'); ?>

    <main class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4 p-md-5">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                                <div>
                                    <h1 class="h3 mb-2">Add Restaurant</h1>
                                    <p class="text-muted mb-0">Create an additional restaurant listing under your restaurant-owner account.</p>
                                </div>
                                <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                            </div>

                            <?php if ($errorMessage !== ''): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                            <?php endif; ?>

                            <?php if ($successMessage !== ''): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
                            <?php endif; ?>

                            <form method="post" action="add-restaurant.php">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="RestaurantName" class="form-label">Restaurant Name</label>
                                        <input type="text" class="form-control" id="RestaurantName" name="RestaurantName" value="<?php echo htmlspecialchars($_POST['RestaurantName'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="OwnerId" class="form-label">Owner ID</label>
                                        <input type="number" min="1" class="form-control" id="OwnerId" name="OwnerId" value="<?php echo htmlspecialchars($_POST['OwnerId'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-12">
                                        <label for="Address" class="form-label">Address</label>
                                        <input type="text" class="form-control" id="Address" name="Address" value="<?php echo htmlspecialchars($_POST['Address'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="PhoneNum" class="form-label">Phone Number</label>
                                        <input type="text" class="form-control" id="PhoneNum" name="PhoneNum" value="<?php echo htmlspecialchars($_POST['PhoneNum'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="CusineType" class="form-label">Type of Cuisine</label>
                                        <input type="text" class="form-control" id="CusineType" name="CusineType" value="<?php echo htmlspecialchars($_POST['CusineType'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="OpeningHours" class="form-label">Opening Hours</label>
                                        <input type="time" class="form-control" id="OpeningHours" name="OpeningHours" value="<?php echo htmlspecialchars($_POST['OpeningHours'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="ClosingHours" class="form-label">Closing Hours</label>
                                        <input type="time" class="form-control" id="ClosingHours" name="ClosingHours" value="<?php echo htmlspecialchars($_POST['ClosingHours'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="OpeningDays" class="form-label">Opening Days</label>
                                        <input type="text" class="form-control" id="OpeningDays" name="OpeningDays" value="<?php echo htmlspecialchars($_POST['OpeningDays'] ?? ''); ?>" placeholder="e.g. Mon-Sun" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="PriceRange" class="form-label">Price Range</label>
                                        <select class="form-select" id="PriceRange" name="PriceRange" required>
                                            <option value="">Select a price range</option>
                                            <option value="$" <?php echo (($_POST['PriceRange'] ?? '') === '$') ? 'selected' : ''; ?>>$</option>
                                            <option value="$$" <?php echo (($_POST['PriceRange'] ?? '') === '$$') ? 'selected' : ''; ?>>$$</option>
                                            <option value="$$$" <?php echo (($_POST['PriceRange'] ?? '') === '$$$') ? 'selected' : ''; ?>>$$$</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <div class="form-text mb-2">IdRestaurants is auto-generated by the database.</div>
                                    </div>
                                </div>

                                <div class="mt-4 d-flex gap-3 flex-wrap">
                                    <button type="submit" class="btn btn-primary">Add Restaurant</button>
                                    <a href="edit-profile.php" class="btn btn-outline-secondary">Edit Existing Restaurant</a>
                                </div>
                            </form>
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
