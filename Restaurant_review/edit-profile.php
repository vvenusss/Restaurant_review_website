<?php
    include('includes/header.php');
    require_once __DIR__ . '/includes/restaurant-db.php';

if (!isset($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

$currentRole = $_SESSION['role'];

$successMessage = '';
$errorMessage = '';

$restaurantProfile = [
    'idRestaurants' => '',
    'RestaurantName' => '',
    'OwnerId' => '',
    'Address' => '',
    'PhoneNum' => '',
    'CusineType' => '',
    'OpeningHours' => '',
    'ClosingHours' => '',
    'OpeningDays' => '',
    'PriceRange' => ''
];

$dinerProfile = [
    'name' => $_SESSION['name'] ?? ' ',
    'email' => $_SESSION['email'] ?? ' ',
    'password' => $_SESSION['password'] ?? ' '
];

$adminProfile = [
    'name' => $_SESSION['name'] ?? ' ',
    'email' => $_SESSION['email'] ?? ' ',
    'password' => $_SESSION['password'] ?? ' '
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($currentRole === 'restaurant') {
        $idRestaurants = trim($_POST['idRestaurants'] ?? '');
        $restaurantName = trim($_POST['RestaurantName'] ?? '');
        $ownerId = trim($_POST['OwnerId'] ?? '');
        $address = trim($_POST['Address'] ?? '');
        $phoneNum = trim($_POST['PhoneNum'] ?? '');
        $cusineType = trim($_POST['CusineType'] ?? '');
        $openingHours = trim($_POST['OpeningHours'] ?? '');
        $closingHours = trim($_POST['ClosingHours'] ?? '');
        $openingDays = trim($_POST['OpeningDays'] ?? '');
        $priceRange = trim($_POST['PriceRange'] ?? '');

        $restaurantProfile = [
            'idRestaurants' => $idRestaurants,
            'RestaurantName' => $restaurantName,
            'OwnerId' => $ownerId,
            'Address' => $address,
            'PhoneNum' => $phoneNum,
            'CusineType' => $cusineType,
            'OpeningHours' => $openingHours,
            'ClosingHours' => $closingHours,
            'OpeningDays' => $openingDays,
            'PriceRange' => $priceRange
        ];

        if ($idRestaurants === '' || $restaurantName === '' || $ownerId === '' || $address === '' || $phoneNum === '' || $cusineType === '' || $openingHours === '' || $closingHours === '' || $openingDays === '' || $priceRange === '') {
            $errorMessage = 'Please complete all restaurant fields before saving changes.';
        } elseif (!ctype_digit($idRestaurants) || (int) $idRestaurants < 1 || !ctype_digit($ownerId) || (int) $ownerId < 1) {
            $errorMessage = 'Restaurant ID and Owner ID must be valid positive integers.';
        } else {
            $restaurantPayload = [
                'idRestaurants' => (int) $idRestaurants,
                'RestaurantName' => $restaurantName,
                'OwnerId' => (int) $ownerId,
                'Address' => $address,
                'PhoneNum' => $phoneNum,
                'CusineType' => $cusineType,
                'OpeningHours' => $openingHours,
                'ClosingHours' => $closingHours,
                'OpeningDays' => $openingDays,
                'PriceRange' => $priceRange
            ];

            $connection = getDatabaseConnection($errorMessage);

            if ($connection) {
                if (ownerIdExists($connection, $restaurantPayload['OwnerId'], $errorMessage)) {
                    if (updateRestaurantRecord($connection, $restaurantPayload, $errorMessage)) {
                        $successMessage = 'Restaurant information updated successfully.';
                    }
                }

                $connection->close();
            }
        }
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($name === '' || $email === '' || $password === '') {
            $errorMessage = 'Please complete all profile fields before saving changes.';
        } else {
            if ($currentRole === 'admin') {
                $adminProfile = [
                    'name' => $name,
                    'email' => $email,
                    'password' => $password
                ];
            } else {
                $dinerProfile = [
                    'name' => $name,
                    'email' => $email,
                    'password' => $password
                ];
            }
            $successMessage = 'Profile updated successfully.';
        }
    }
}

$pageTitle = 'Edit Profile';
$pageDescription = 'Update your account details below.';

if ($currentRole === 'restaurant') {
    $pageTitle = 'Edit Restaurant Details';
    $pageDescription = 'Update your restaurant details, including business information, contact details, menu image references, and front image references.';
} elseif ($currentRole === 'admin') {
    $pageTitle = 'Edit Admin Profile';
    $pageDescription = 'Update your administrator account information.';
}

if (!empty($restaurantProfile['OpeningHours'])) {
    $restaurantProfile['OpeningHours'] = date('H:i', strtotime($restaurantProfile['OpeningHours']));
}

if (!empty($restaurantProfile['ClosingHours'])) {
    $restaurantProfile['ClosingHours'] = date('H:i', strtotime($restaurantProfile['ClosingHours']));
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
                                <div class="alert alert-info">This page is for editing your restaurant details, not a separate owner personal profile.</div>
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
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="idRestaurants" class="form-label">Restaurant ID</label>
                                            <input type="number" min="1" class="form-control" id="idRestaurants" name="idRestaurants" value="<?php echo htmlspecialchars($restaurantProfile['idRestaurants']); ?>" placeholder="Enter existing restaurant ID" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="RestaurantName" class="form-label">Restaurant Name</label>
                                            <input type="text" class="form-control" id="RestaurantName" name="RestaurantName" value="<?php echo htmlspecialchars($restaurantProfile['RestaurantName']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="OwnerId" class="form-label">Owner ID</label>
                                            <input type="number" min="1" class="form-control" id="OwnerId" name="OwnerId" value="<?php echo htmlspecialchars($restaurantProfile['OwnerId']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="PhoneNum" class="form-label">Phone Number</label>
                                            <input type="text" class="form-control" id="PhoneNum" name="PhoneNum" value="<?php echo htmlspecialchars($restaurantProfile['PhoneNum']); ?>" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="Address" class="form-label">Address</label>
                                            <input type="text" class="form-control" id="Address" name="Address" value="<?php echo htmlspecialchars($restaurantProfile['Address']); ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="CusineType" class="form-label">Cuisine Type</label>
                                            <input type="text" class="form-control" id="CusineType" name="CusineType" value="<?php echo htmlspecialchars($restaurantProfile['CusineType']); ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="OpeningHours" class="form-label">Opening Hours</label>
                                            <input type="time" class="form-control" id="OpeningHours" name="OpeningHours" value="<?php echo htmlspecialchars($restaurantProfile['OpeningHours']); ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="ClosingHours" class="form-label">Closing Hours</label>
                                            <input type="time" class="form-control" id="ClosingHours" name="ClosingHours" value="<?php echo htmlspecialchars($restaurantProfile['ClosingHours']); ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="OpeningDays" class="form-label">Opening Days</label>
                                            <input type="text" class="form-control" id="OpeningDays" name="OpeningDays" value="<?php echo htmlspecialchars($restaurantProfile['OpeningDays']); ?>" placeholder="e.g. Mon-Sun" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="PriceRange" class="form-label">Price Range</label>
                                            <input type="text" class="form-control" id="PriceRange" name="PriceRange" value="<?php echo htmlspecialchars($restaurantProfile['PriceRange']); ?>" required>
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
</body>
</html>
