<?php
include('includes/header.php');
require_once __DIR__ . '/includes/restaurant-db.php';

if (!isset($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

$currentRole = $_SESSION['role'];
$sessionOwnerId = (int) ($_SESSION['user_id'] ?? 0);

$successMessage = '';
$errorMessage = '';

// ── Build time options helper ──────────────────────────────────────────────
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

// ── Opening days options ───────────────────────────────────────────────────
$openingDaysOptions = [
    'Mon-Fri'  => 'Mon-Fri (Weekdays)',
    'Mon-Sat'  => 'Mon-Sat',
    'Mon-Sun'  => 'Mon-Sun (Everyday)',
    'Sat-Sun'  => 'Sat-Sun (Weekends)',
    'Tue-Sun'  => 'Tue-Sun',
    'Wed-Sun'  => 'Wed-Sun',
];

// ── Default restaurant profile ─────────────────────────────────────────────
$restaurantProfile = [
    'idRestaurants' => '',
    'RestaurantName' => '',
    'OwnerId'        => '',
    'Address'        => '',
    'PhoneNum'       => '',
    'CusineType'     => '',
    'OpeningHours'   => '',
    'ClosingHours'   => '',
    'OpeningDays'    => '',
    'PriceRange'     => ''
];

$dinerProfile = [
    'name'     => $_SESSION['name']     ?? '',
    'email'    => $_SESSION['email']    ?? '',
    'password' => $_SESSION['password'] ?? ''
];

$adminProfile = [
    'name'     => $_SESSION['name']     ?? '',
    'email'    => $_SESSION['email']    ?? '',
    'password' => $_SESSION['password'] ?? ''
];

// ── Fetch owner's restaurants for the dropdown ─────────────────────────────
$ownerRestaurants = [];
if ($currentRole === 'restaurant' && $sessionOwnerId > 0) {
    $dbErr = '';
    $dbConn = getDatabaseConnection($dbErr);
    if ($dbConn) {
        $ownerRestaurants = getRestaurantsByOwnerId($dbConn, $sessionOwnerId, $dbErr);
        $dbConn->close();
    }
}

// ── Handle GET: load restaurant by ID for pre-fill ───────────────────────
if ($currentRole === 'restaurant' && isset($_GET['load_restaurant']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $loadId = (int) $_GET['load_restaurant'];
    if ($loadId > 0) {
        $dbErr = '';
        $dbConn = getDatabaseConnection($dbErr);
        if ($dbConn) {
            $loaded = getRestaurantById($dbConn, $loadId, $dbErr);
            $dbConn->close();
            if ($loaded && (int) $loaded['OwnerId'] === $sessionOwnerId) {
                $restaurantProfile = [
                    'idRestaurants'  => $loaded['idRestaurants'],
                    'RestaurantName' => $loaded['RestaurantName'],
                    'OwnerId'        => $loaded['OwnerId'],
                    'Address'        => $loaded['Address'],
                    'PhoneNum'       => $loaded['PhoneNum'],
                    'CusineType'     => $loaded['CusineType'],
                    'OpeningHours'   => $loaded['OpeningHours'],
                    'ClosingHours'   => $loaded['ClosingHours'],
                    'OpeningDays'    => $loaded['OpeningDays'],
                    'PriceRange'     => $loaded['PriceRange']
                ];
            } else {
                $errorMessage = 'Restaurant not found or does not belong to your account.';
            }
        }
    }
}

// ── Handle POST ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($currentRole === 'restaurant') {
        $idRestaurants = trim($_POST['idRestaurants'] ?? '');
        $restaurantName = trim($_POST['RestaurantName'] ?? '');
        $ownerId        = trim($_POST['OwnerId'] ?? '');
        $address        = trim($_POST['Address'] ?? '');
        $phoneNum       = trim($_POST['PhoneNum'] ?? '');
        $cusineType     = trim($_POST['CusineType'] ?? '');
        $openingHours   = trim($_POST['OpeningHours'] ?? '');
        $closingHours   = trim($_POST['ClosingHours'] ?? '');
        $openingDays    = trim($_POST['OpeningDays'] ?? '');
        $priceRange     = trim($_POST['PriceRange'] ?? '');

        $restaurantProfile = [
            'idRestaurants'  => $idRestaurants,
            'RestaurantName' => $restaurantName,
            'OwnerId'        => $ownerId,
            'Address'        => $address,
            'PhoneNum'       => $phoneNum,
            'CusineType'     => $cusineType,
            'OpeningHours'   => $openingHours,
            'ClosingHours'   => $closingHours,
            'OpeningDays'    => $openingDays,
            'PriceRange'     => $priceRange
        ];

        if ($idRestaurants === '' || $restaurantName === '' || $ownerId === '' || $address === '' || $phoneNum === '' || $cusineType === '' || $openingHours === '' || $closingHours === '' || $openingDays === '' || $priceRange === '') {
            $errorMessage = 'Please complete all restaurant fields before saving changes.';
        } elseif (!ctype_digit($idRestaurants) || (int) $idRestaurants < 1 || !ctype_digit($ownerId) || (int) $ownerId < 1) {
            $errorMessage = 'Restaurant ID and Owner ID must be valid positive integers.';
        } else {
            $restaurantPayload = [
                'idRestaurants'  => (int) $idRestaurants,
                'RestaurantName' => $restaurantName,
                'OwnerId'        => (int) $ownerId,
                'Address'        => $address,
                'PhoneNum'       => $phoneNum,
                'CusineType'     => $cusineType,
                'OpeningHours'   => $openingHours,
                'ClosingHours'   => $closingHours,
                'OpeningDays'    => $openingDays,
                'PriceRange'     => $priceRange
            ];

            $connection = getDatabaseConnection($errorMessage);

            if ($connection) {
                if (ownerIdExists($connection, $restaurantPayload['OwnerId'], $errorMessage)) {
                    if (updateRestaurantRecord($connection, $restaurantPayload, $errorMessage)) {
                        $successMessage = 'Restaurant information updated successfully.';
                        // Refresh the owner's restaurant list after update
                        $ownerRestaurants = getRestaurantsByOwnerId($connection, $sessionOwnerId, $errorMessage);
                    }
                }
                $connection->close();
            }
        }
    } else {
        $name     = trim($_POST['name']     ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($name === '' || $email === '' || $password === '') {
            $errorMessage = 'Please complete all profile fields before saving changes.';
        } else {
            if ($currentRole === 'admin') {
                $adminProfile = ['name' => $name, 'email' => $email, 'password' => $password];
            } else {
                $dinerProfile = ['name' => $name, 'email' => $email, 'password' => $password];
            }
            $successMessage = 'Profile updated successfully.';
        }
    }
}

// ── Normalise time values for display ─────────────────────────────────────
if (!empty($restaurantProfile['OpeningHours'])) {
    $restaurantProfile['OpeningHours'] = date('H:i', strtotime($restaurantProfile['OpeningHours']));
}
if (!empty($restaurantProfile['ClosingHours'])) {
    $restaurantProfile['ClosingHours'] = date('H:i', strtotime($restaurantProfile['ClosingHours']));
}

// ── Page meta ──────────────────────────────────────────────────────────────
$pageTitle = 'Edit Profile';
$pageDescription = 'Update your account details below.';

if ($currentRole === 'restaurant') {
    $pageTitle = 'Edit Restaurant Details';
    $pageDescription = 'Select one of your restaurants from the dropdown, update the details, and save.';
} elseif ($currentRole === 'admin') {
    $pageTitle = 'Edit Admin Profile';
    $pageDescription = 'Update your administrator account information.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodview - <?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <main class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4 p-md-5">
                            <h1 class="h3 mb-3"><?php echo htmlspecialchars($pageTitle); ?></h1>

                            <?php if ($currentRole === 'restaurant'): ?>
                                <div class="alert alert-info">This page is for editing your restaurant details. Select a restaurant from the dropdown below to begin.</div>
                            <?php endif; ?>

                            <p class="text-muted mb-4"><?php echo htmlspecialchars($pageDescription); ?></p>

                            <?php if ($errorMessage !== ''): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                            <?php endif; ?>

                            <?php if ($successMessage !== ''): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
                            <?php endif; ?>

                            <form method="post" action="edit-profile.php">
                                <?php if ($currentRole === 'restaurant'): ?>

                                    <!-- Restaurant selector dropdown -->
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-8">
                                            <label for="restaurantSelector" class="form-label fw-semibold">Select Your Restaurant</label>
                                            <?php if (empty($ownerRestaurants)): ?>
                                                <div class="alert alert-warning mb-0">No restaurants found under your account. <a href="add-restaurant.php">Add one now.</a></div>
                                            <?php else: ?>
                                                <select class="form-select" id="restaurantSelector">
                                                    <option value="">-- Choose a restaurant to edit --</option>
                                                    <?php foreach ($ownerRestaurants as $r): ?>
                                                        <option value="<?php echo (int) $r['idRestaurants']; ?>"
                                                            data-id="<?php echo (int) $r['idRestaurants']; ?>"
                                                            <?php echo ((string)$restaurantProfile['idRestaurants'] === (string)$r['idRestaurants']) ? 'selected' : ''; ?>>
                                                            #<?php echo (int) $r['idRestaurants']; ?> &mdash; <?php echo htmlspecialchars($r['RestaurantName']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="form-text">Selecting a restaurant will pre-fill the form below via the page.</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-center">
                                            <a href="add-restaurant.php" class="btn btn-outline-primary w-100">+ Add New Restaurant</a>
                                        </div>
                                    </div>

                                    <hr>

                                    <!-- Hidden idRestaurants field (populated by JS or carried from POST) -->
                                    <input type="hidden" id="idRestaurantsHidden" name="idRestaurants" value="<?php echo htmlspecialchars($restaurantProfile['idRestaurants']); ?>">

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="RestaurantName" class="form-label">Restaurant Name</label>
                                            <input type="text" class="form-control" id="RestaurantName" name="RestaurantName" value="<?php echo htmlspecialchars($restaurantProfile['RestaurantName']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Owner ID</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($sessionOwnerId); ?>" disabled>
                                            <input type="hidden" name="OwnerId" value="<?php echo htmlspecialchars($sessionOwnerId); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="PhoneNum" class="form-label">Phone Number</label>
                                            <input type="text" class="form-control" id="PhoneNum" name="PhoneNum" value="<?php echo htmlspecialchars($restaurantProfile['PhoneNum']); ?>" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="Address" class="form-label">Address</label>
                                            <textarea class="form-control" id="Address" name="Address" rows="2" required><?php echo htmlspecialchars($restaurantProfile['Address']); ?></textarea>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="CusineType" class="form-label">Cuisine Type</label>
                                            <input type="text" class="form-control" id="CusineType" name="CusineType" value="<?php echo htmlspecialchars($restaurantProfile['CusineType']); ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="OpeningHours" class="form-label">Opening Hours</label>
                                            <select class="form-select" id="OpeningHours" name="OpeningHours" required>
                                                <?php echo buildTimeOptions($restaurantProfile['OpeningHours']); ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="ClosingHours" class="form-label">Closing Hours</label>
                                            <select class="form-select" id="ClosingHours" name="ClosingHours" required>
                                                <?php echo buildTimeOptions($restaurantProfile['ClosingHours']); ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="OpeningDays" class="form-label">Opening Days</label>
                                            <input type="text" class="form-control" id="OpeningDays" name="OpeningDays"
                                                placeholder="Mon-Fri"
                                                value="<?php echo htmlspecialchars($restaurantProfile['OpeningDays']); ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="PriceRange" class="form-label">Price Range</label>
                                            <select class="form-select" id="PriceRange" name="PriceRange" required>
                                                <option value="">Select a price range</option>
                                                <option value="$"   <?php echo ($restaurantProfile['PriceRange'] === '$')   ? 'selected' : ''; ?>>$ &mdash; Budget</option>
                                                <option value="$$"  <?php echo ($restaurantProfile['PriceRange'] === '$$')  ? 'selected' : ''; ?>>$$ &mdash; Moderate</option>
                                                <option value="$$$" <?php echo ($restaurantProfile['PriceRange'] === '$$$') ? 'selected' : ''; ?>>$$$ &mdash; Fine Dining</option>
                                            </select>
                                        </div>
                                    </div>

                                <?php else: ?>
                                    <?php $profileData = $currentRole === 'admin' ? $adminProfile : $dinerProfile; ?>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="name" class="form-label">Name</label>
                                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($profileData['name']); ?>" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($profileData['email']); ?>" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="password" class="form-label">Password</label>
                                            <input type="password" class="form-control" id="password" name="password" value="<?php echo htmlspecialchars($profileData['password']); ?>" required>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex gap-3 flex-wrap mt-4">
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                    <?php if ($currentRole === 'admin'): ?>
                                        <a href="moderation.php" class="btn btn-outline-secondary">Go to Moderation</a>
                                    <?php endif; ?>
                                    <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
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
    <script>
        // When the restaurant selector changes, reload the page with the chosen restaurant ID
        // so the server can fetch and pre-fill that restaurant's details.
        const selector = document.getElementById('restaurantSelector');
        if (selector) {
            selector.addEventListener('change', function () {
                const selectedId = this.value;
                if (selectedId) {
                    window.location.href = 'edit-profile.php?load_restaurant=' + encodeURIComponent(selectedId);
                }
            });
        }
    </script>
</body>
</html>
