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

// Auto-fill OwnerId from session
$sessionOwnerId = (int) ($_SESSION['user_id'] ?? 0);

$successMessage = '';
$errorMessage = '';

// Build time options: 12:00 AM to 11:30 PM in 30-minute increments
function buildTimeOptions($selectedValue = '') {
    $options = '<option value="">-- Select Time --</option>';
    for ($h = 0; $h < 24; $h++) {
        foreach ([0, 30] as $m) {
            $value = sprintf('%02d:%02d', $h, $m);
            $label = date('g:i A', strtotime($value));
            $selected = ($selectedValue === $value) ? ' selected' : '';
            $options .= '<option value="' . $value . '"' . $selected . '>' . $label . '</option>';
        }
    }
    return $options;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $requiredFields = [
            'RestaurantName' => 'restaurant name',
            'Address'        => 'address',
            'PhoneNum'       => 'phone number',
            'CusineType'     => 'type of cuisine',
            'OpeningHours'   => 'opening hours',
            'ClosingHours'   => 'closing hours',
            'OpeningDays'    => 'opening days',
            'PriceRange'     => 'price range'
        ];

        foreach ($requiredFields as $field => $label) {
            if (trim($_POST[$field] ?? '') === '') {
                $errorMessage = 'Please select the ' . $label . ' before adding a restaurant.';
                break;
            }
        }

        if ($errorMessage === '') {
            $restaurantPayload = [
                'RestaurantName' => trim($_POST['RestaurantName']),
                'OwnerId'        => $sessionOwnerId,
                'Address'        => trim($_POST['Address']),
                'PhoneNum'       => trim($_POST['PhoneNum']),
                'CusineType'     => trim($_POST['CusineType']),
                'OpeningHours'   => trim($_POST['OpeningHours']),
                'ClosingHours'   => trim($_POST['ClosingHours']),
                'OpeningDays'    => trim($_POST['OpeningDays']),
                'PriceRange'     => trim($_POST['PriceRange'])
            ];

            $connection = getDatabaseConnection($errorMessage);

            if ($connection) {
                if (ownerIdExists($connection, $sessionOwnerId, $errorMessage)) {
                    $insertedId = insertRestaurantRecord($connection, $restaurantPayload, $errorMessage);

                    if ($insertedId !== false) {
                        $successMessage = 'Restaurant added successfully. New Restaurant ID: ' . $insertedId . '.';
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

$postOpeningHours  = $_POST['OpeningHours'] ?? '';
$postClosingHours  = $_POST['ClosingHours'] ?? '';
$postOpeningDays   = $_POST['OpeningDays'] ?? '';
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

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="mb-4 text-center">
                    <h2>Add a New Restaurant</h2>
                    <p class="text-muted mb-0">Fill in your restaurant details below to create a new listing under your account.</p>
                </div>

                <?php if ($errorMessage !== ''): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                <?php endif; ?>

                <?php if ($successMessage !== ''): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
                <?php endif; ?>

                <form class="shadow-sm rounded bg-white p-4" method="post" action="add-restaurant.php">

                    <!-- Step 1: Basic Info -->
                    <h4 class="mb-3">Step 1: Basic Information</h4>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="RestaurantName" class="form-label">Restaurant Name</label>
                            <input type="text" class="form-control" id="RestaurantName" name="RestaurantName"
                                value="<?php echo htmlspecialchars($_POST['RestaurantName'] ?? ''); ?>" required>
                        </div>

                        <div class="col-12">
                            <label for="Address" class="form-label">Address</label>
                            <textarea class="form-control" id="Address" name="Address" rows="2" required><?php echo htmlspecialchars($_POST['Address'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="PhoneNum" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="PhoneNum" name="PhoneNum"
                                value="<?php echo htmlspecialchars($_POST['PhoneNum'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="CusineType" class="form-label">Type of Cuisine</label>
                            <input type="text" class="form-control" id="CusineType" name="CusineType"
                                value="<?php echo htmlspecialchars($_POST['CusineType'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <hr>

                    <!-- Step 2: Hours & Days -->
                    <h4 class="mb-3">Step 2: Operating Hours &amp; Days</h4>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="OpeningHours" class="form-label">Opening Hours</label>
                            <select class="form-select" id="OpeningHours" name="OpeningHours" required>
                                <?php echo buildTimeOptions($postOpeningHours); ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="ClosingHours" class="form-label">Closing Hours</label>
                            <select class="form-select" id="ClosingHours" name="ClosingHours" required>
                                <?php echo buildTimeOptions($postClosingHours); ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="OpeningDays" class="form-label">Opening Days</label>
                            <input type="text" class="form-control" id="OpeningDays" name="OpeningDays"
                                placeholder="Mon-Fri"
                                value="<?php echo htmlspecialchars($postOpeningDays); ?>" required>
                        </div>
                    </div>

                    <hr>

                    <!-- Step 3: Pricing -->
                    <h4 class="mb-3">Step 3: Pricing</h4>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="PriceRange" class="form-label">Price Range</label>
                            <select class="form-select" id="PriceRange" name="PriceRange" required>
                                <option value="">Select a price range</option>
                                <option value="$"   <?php echo (($_POST['PriceRange'] ?? '') === '$')   ? 'selected' : ''; ?>>$ &mdash; Budget</option>
                                <option value="$$"  <?php echo (($_POST['PriceRange'] ?? '') === '$$')  ? 'selected' : ''; ?>>$$ &mdash; Moderate</option>
                                <option value="$$$" <?php echo (($_POST['PriceRange'] ?? '') === '$$$') ? 'selected' : ''; ?>>$$$ &mdash; Fine Dining</option>
                            </select>
                        </div>
                        <div class="col-md-8 d-flex align-items-end">
                            <div class="form-text">Restaurant ID is auto-generated by the database upon submission.</div>
                        </div>
                    </div>

                    <div class="d-flex gap-3 flex-wrap mt-2">
                        <button type="submit" class="btn btn-primary">Add Restaurant</button>
                        <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <?php include('includes/footer.php'); ?>
</body>
</html>
