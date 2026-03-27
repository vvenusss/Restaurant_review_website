<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/includes/restaurant-db.php';

$isAuthenticated = isset($_GET['auth']) && $_GET['auth'] === '1';
$currentRole = isset($_GET['role']) ? $_GET['role'] : 'guest';

$name = '';
$email = '';
$userType = 'diner';
$pwd_hashed = '';
$errorMsg = '';
$success = true;
$restaurantName = '';
$ownerName = '';
$restaurantPhone = '';
$restaurantAddress = '';
$restaurantCuisine = '';
$restaurantHours = '';
$restaurantClosingHours = '';
$restaurantOpeningDays = '';
$restaurantPriceRange = '';
$restaurantFrontImage = '';
$restaurantMenuImages = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $userType = isset($_POST['userType']) ? trim($_POST['userType']) : 'diner';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirmPassword = isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : '';
        $restaurantName = isset($_POST['restaurantName']) ? trim($_POST['restaurantName']) : '';
        $ownerName = isset($_POST['ownerName']) ? trim($_POST['ownerName']) : '';
        $restaurantPhone = isset($_POST['restaurantPhone']) ? trim($_POST['restaurantPhone']) : '';
        $restaurantAddress = isset($_POST['restaurantAddress']) ? trim($_POST['restaurantAddress']) : '';
        $restaurantCuisine = isset($_POST['restaurantCuisine']) ? trim($_POST['restaurantCuisine']) : '';
        $restaurantHours = isset($_POST['restaurantHours']) ? trim($_POST['restaurantHours']) : '';
        $restaurantClosingHours = isset($_POST['restaurantClosingHours']) ? trim($_POST['restaurantClosingHours']) : '';
        $restaurantOpeningDays = isset($_POST['restaurantOpeningDays']) ? trim($_POST['restaurantOpeningDays']) : '';
        $restaurantPriceRange = isset($_POST['restaurantPriceRange']) ? trim($_POST['restaurantPriceRange']) : '';
        $restaurantFrontImage = isset($_POST['restaurantFrontImage']) ? trim($_POST['restaurantFrontImage']) : '';
        $restaurantMenuImages = isset($_POST['restaurantMenuImages']) ? trim($_POST['restaurantMenuImages']) : '';

        if ($name === '' || $email === '') {
            $errorMsg = 'Please provide your name and email.';
            $success = false;
        }
        else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'Please enter a valid email address.';
            $success = false;
        }
        else if (!in_array($userType, ['diner', 'restaurant'], true)) {
            $errorMsg = 'Invalid user type selected.';
            $success = false;
        }
        else if ($password === '' || $confirmPassword === '') {
            $errorMsg = 'Please enter your password and confirmation password.';
            $success = false;
        }
        else if (strlen($password) < 6) {
            $errorMsg = 'Your password must be at least 6 characters long.';
            $success = false;
        }
        else if ($password !== $confirmPassword) {
            $errorMsg = 'Password and confirmation password do not match.';
            $success = false;
        }
        else if ($userType === 'restaurant' && (
            $restaurantName === '' ||
            $ownerName === '' ||
            $restaurantPhone === '' ||
            $restaurantAddress === '' ||
            $restaurantCuisine === '' ||
            $restaurantHours === '' ||
            $restaurantClosingHours === '' ||
            $restaurantOpeningDays === '' ||
            $restaurantPriceRange === ''
        )) {
            $errorMsg = 'Please complete all required restaurant details.';
            $success = false;
        }

        if ($success) {
            $pwd_hashed = password_hash($password, PASSWORD_DEFAULT);
            $newUserId = saveUserToDB();

            if ($success) {
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['email'] = $email;
                    $_SESSION['name']  = $name;
                    $_SESSION['role']  = $userType;

                header('Location: dashboard.php');
                exit();
            }
        }
    } catch (Throwable $e) {
        $errorMsg = 'Signup failed due to a server error: ' . $e->getMessage();
        $success = false;
    }
}

/*
* Helper function to write the user data to the database.
*/
function saveUserToDB()
{
    global $name, $email, $userType, $pwd_hashed, $errorMsg, $success;
    global $restaurantName, $restaurantPhone, $restaurantAddress, $restaurantCuisine, $restaurantHours, $restaurantClosingHours, $restaurantOpeningDays, $restaurantPriceRange;
    global $restaurantFrontImage, $restaurantMenuImages;

    $conn = getDatabaseConnection($errorMsg);
    if (!$conn) {
        $success = false;
        return 0;
    }

    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare('INSERT INTO users (name, email, password, userType) VALUES (?, ?, ?, ?)');

        if (!$stmt) {
            $errorMsg = 'Prepare failed: (' . $conn->errno . ') ' . $conn->error;
            $success = false;
            $conn->close();
            return 0;
        }

        $stmt->bind_param('ssss', $name, $email, $pwd_hashed, $userType);

        if (!$stmt->execute()) {
            $errorMsg = 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error;
            $success = false;
            $stmt->close();
            $conn->rollback();
            $conn->close();
            return 0;
        }

        $insertedId = (int) $conn->insert_id;
        $stmt->close();

        if ($userType === 'restaurant') {
            $restaurantPayload = [
                'RestaurantName' => $restaurantName,
                'OwnerId' => $insertedId,
                'Address' => $restaurantAddress,
                'PhoneNum' => $restaurantPhone,
                'CusineType' => $restaurantCuisine,
                'OpeningHours' => $restaurantHours,
                'ClosingHours' => $restaurantClosingHours,
                'OpeningDays' => $restaurantOpeningDays,
                'PriceRange' => $restaurantPriceRange
            ];

            $restaurantId = insertRestaurantRecord($conn, $restaurantPayload, $errorMsg);

            if ($restaurantId === false) {
                $success = false;
                $conn->rollback();
                $conn->close();
                return 0;
            }

            $imageUrls = [];
            if ($restaurantFrontImage !== '') {
                $imageUrls[] = $restaurantFrontImage;
            }

            if ($restaurantMenuImages !== '') {
                $menuParts = explode(',', $restaurantMenuImages);
                foreach ($menuParts as $menuUrl) {
                    $trimmedUrl = trim($menuUrl);
                    if ($trimmedUrl !== '') {
                        $imageUrls[] = $trimmedUrl;
                    }
                }
            }

            if (count($imageUrls) > 0) {
                $imageStmt = $conn->prepare('INSERT INTO RestaurantImages (idRestaurants, ImageUrl) VALUES (?, ?)');

                if (!$imageStmt) {
                    $errorMsg = 'Prepare failed for RestaurantImages: (' . $conn->errno . ') ' . $conn->error;
                    $success = false;
                    $conn->rollback();
                    $conn->close();
                    return 0;
                }

                foreach ($imageUrls as $imageUrl) {
                    $imageStmt->bind_param('is', $restaurantId, $imageUrl);
                    if (!$imageStmt->execute()) {
                        $errorMsg = 'Execute failed for RestaurantImages: (' . $imageStmt->errno . ') ' . $imageStmt->error;
                        $success = false;
                        $imageStmt->close();
                        $conn->rollback();
                        $conn->close();
                        return 0;
                    }
                }

                $imageStmt->close();
            }
        }

        $conn->commit();
        $conn->close();
        return $insertedId;
    } catch (Throwable $e) {
        $errorMsg = 'Database operation failed: ' . $e->getMessage();
        $success = false;
        try {
            $conn->rollback();
        } catch (Throwable $rollbackError) {
            // No-op: preserve original database error.
        }
        $conn->close();
        return 0;
    }
}

// Build time options helper
function buildSignupTimeOptions($selectedValue = '') {
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

$openingDaysOptions = [
    'Mon-Fri'  => 'Mon-Fri (Weekdays)',
    'Mon-Sat'  => 'Mon-Sat',
    'Mon-Sun'  => 'Mon-Sun (Everyday)',
    'Sat-Sun'  => 'Sat-Sun (Weekends)',
    'Tue-Sun'  => 'Tue-Sun',
    'Wed-Sun'  => 'Wed-Sun',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodview - Sign Up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include("includes/header.php"); ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="mb-4 text-center">
                    <h2>Create Your Account</h2>
                    <p class="text-muted mb-0">Start by choosing your account type, then continue to complete the rest of your registration details.</p>
                </div>

                <div id="signupError" class="alert alert-danger<?php echo $errorMsg !== '' ? '' : ' d-none'; ?>"><?php echo htmlspecialchars($errorMsg); ?></div>
                <div id="signupSuccess" class="alert alert-success d-none"></div>

                <form id="signupForm" class="shadow-sm rounded bg-white p-4" method="post" action="signup.php">
                    <div id="stepOneSection">
                        <h4 class="mb-3">Step 1: Basic Account Setup</h4>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label d-block mb-2">User Type</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="userType" id="userTypeDiner" value="diner" <?php echo $userType === 'diner' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="userTypeDiner">Diner</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="userType" id="userTypeRestaurant" value="restaurant" <?php echo $userType === 'restaurant' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="userTypeRestaurant">Restaurant Owner</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="signupName" class="form-label">Name</label>
                                <input type="text" class="form-control" id="signupName" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="signupEmail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="signupEmail" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="button" class="btn btn-primary" id="continueToStepTwo">Continue</button>
                        </div>
                    </div>

                    <div id="stepTwoSection" class="d-none">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0">Step 2: Complete Your Account</h4>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="backToStepOne">Back</button>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="signupPassword" class="form-label">Password</label>
                                <input type="password" class="form-control" id="signupPassword" name="password" required>
                            </div>
                            <div class="col-md-6">
                                <label for="signupConfirmPassword" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="signupConfirmPassword" name="confirmPassword" required>
                            </div>
                        </div>

                        <div id="dinerFields">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Diner ID</label>
                                    <input type="text" class="form-control" value="Assigned automatically after account creation" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Registered Name</label>
                                    <input type="text" class="form-control" id="dinerNameMirror" disabled>
                                </div>
                            </div>
                        </div>

                        <div id="restaurantFields" class="d-none">
                            <hr class="my-3">
                            <h5 class="mb-3">Restaurant Details</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="restIdPreview" class="form-label">Restaurant ID</label>
                                    <input type="text" class="form-control" id="restIdPreview" value="Assigned automatically after account creation" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label for="restaurantName" class="form-label">Restaurant Name</label>
                                    <input type="text" class="form-control" id="restaurantName" name="restaurantName" value="<?php echo htmlspecialchars($restaurantName); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="ownerName" class="form-label">Owner Name</label>
                                    <input type="text" class="form-control" id="ownerName" name="ownerName" value="<?php echo htmlspecialchars($ownerName); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="restaurantPhone" class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" id="restaurantPhone" name="restaurantPhone" value="<?php echo htmlspecialchars($restaurantPhone); ?>">
                                </div>
                                <div class="col-md-12">
                                    <label for="restaurantAddress" class="form-label">Address</label>
                                    <textarea class="form-control" id="restaurantAddress" name="restaurantAddress" rows="2"><?php echo htmlspecialchars($restaurantAddress); ?></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label for="restaurantCuisine" class="form-label">Type of Cuisine</label>
                                    <input type="text" class="form-control" id="restaurantCuisine" name="restaurantCuisine" value="<?php echo htmlspecialchars($restaurantCuisine); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="restaurantHours" class="form-label">Opening Hours</label>
                                    <select class="form-select" id="restaurantHours" name="restaurantHours">
                                        <?php echo buildSignupTimeOptions($restaurantHours); ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="restaurantClosingHours" class="form-label">Closing Hours</label>
                                    <select class="form-select" id="restaurantClosingHours" name="restaurantClosingHours">
                                        <?php echo buildSignupTimeOptions($restaurantClosingHours); ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="restaurantOpeningDays" class="form-label">Opening Days</label>
                                    <select class="form-select" id="restaurantOpeningDays" name="restaurantOpeningDays">
                                        <option value="">-- Select Days --</option>
                                        <?php foreach ($openingDaysOptions as $val => $lbl): ?>
                                            <option value="<?php echo htmlspecialchars($val); ?>"
                                                <?php echo ($restaurantOpeningDays === $val) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($lbl); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="restaurantPriceRange" class="form-label">Price Range</label>
                                    <select class="form-select" id="restaurantPriceRange" name="restaurantPriceRange">
                                        <option value="">Select a price range</option>
                                        <option value="$"   <?php echo $restaurantPriceRange === '$'   ? 'selected' : ''; ?>>$ &mdash; Budget</option>
                                        <option value="$$"  <?php echo $restaurantPriceRange === '$$'  ? 'selected' : ''; ?>>$$ &mdash; Moderate</option>
                                        <option value="$$$" <?php echo $restaurantPriceRange === '$$$' ? 'selected' : ''; ?>>$$$ &mdash; Fine Dining</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="restaurantFrontImage" class="form-label">Restaurant Front Image URL</label>
                                    <input type="url" class="form-control" id="restaurantFrontImage" name="restaurantFrontImage" value="<?php echo htmlspecialchars($restaurantFrontImage); ?>" placeholder="https://">
                                </div>
                                <div class="col-md-6">
                                    <label for="restaurantMenuImages" class="form-label">Menu Image URLs</label>
                                    <input type="text" class="form-control" id="restaurantMenuImages" name="restaurantMenuImages" value="<?php echo htmlspecialchars($restaurantMenuImages); ?>" placeholder="Comma-separated URLs">
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Your account type, name, and email from step one will be used for this registration.</span>
                            <button type="submit" class="btn btn-primary">Create Account</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include("includes/footer.php"); ?>
    <script>
        const signupForm = document.getElementById('signupForm');
        const stepOneSection = document.getElementById('stepOneSection');
        const stepTwoSection = document.getElementById('stepTwoSection');
        const dinerFields = document.getElementById('dinerFields');
        const restaurantFields = document.getElementById('restaurantFields');
        const signupError = document.getElementById('signupError');
        const signupSuccess = document.getElementById('signupSuccess');
        const dinerNameMirror = document.getElementById('dinerNameMirror');

        function showSignupError(message) {
            signupSuccess.classList.add('d-none');
            signupError.textContent = message;
            signupError.classList.remove('d-none');
        }

        function hideSignupMessages() {
            signupError.classList.add('d-none');
            signupSuccess.classList.add('d-none');
        }

        function getSelectedUserType() {
            return document.querySelector('input[name="userType"]:checked').value;
        }

        function updateStepTwoFields() {
            const selectedType = getSelectedUserType();
            dinerFields.classList.toggle('d-none', selectedType !== 'diner');
            restaurantFields.classList.toggle('d-none', selectedType !== 'restaurant');
            dinerNameMirror.value = document.getElementById('signupName').value.trim();
            if (selectedType === 'restaurant') {
                document.getElementById('ownerName').value = document.getElementById('signupName').value.trim();
            }
        }

        document.getElementById('continueToStepTwo').addEventListener('click', function () {
            hideSignupMessages();

            const name = document.getElementById('signupName').value.trim();
            const email = document.getElementById('signupEmail').value.trim();

            if (!name || !email) {
                showSignupError('Please choose a user type and complete your name and email before continuing.');
                return;
            }

            updateStepTwoFields();
            stepOneSection.classList.add('d-none');
            stepTwoSection.classList.remove('d-none');
        });

        document.getElementById('backToStepOne').addEventListener('click', function () {
            hideSignupMessages();
            stepTwoSection.classList.add('d-none');
            stepOneSection.classList.remove('d-none');
        });

        document.querySelectorAll('input[name="userType"]').forEach(function (radio) {
            radio.addEventListener('change', updateStepTwoFields);
        });

        signupForm.addEventListener('submit', function (event) {
            hideSignupMessages();

            const selectedType = getSelectedUserType();
            const password = document.getElementById('signupPassword').value;
            const confirmPassword = document.getElementById('signupConfirmPassword').value;

            if (!password || !confirmPassword) {
                event.preventDefault();
                showSignupError('Please enter your password and confirmation password.');
                return;
            }

            if (password.length < 6) {
                event.preventDefault();
                showSignupError('Your password must be at least 6 characters long.');
                return;
            }

            if (password !== confirmPassword) {
                event.preventDefault();
                showSignupError('Password and confirmation password do not match.');
                return;
            }

            if (selectedType === 'restaurant') {
                const requiredRestaurantFields = [
                    { id: 'restaurantName', label: 'restaurant name' },
                    { id: 'ownerName', label: 'owner name' },
                    { id: 'restaurantAddress', label: 'address' },
                    { id: 'restaurantPhone', label: 'phone number' },
                    { id: 'restaurantCuisine', label: 'type of cuisine' },
                    { id: 'restaurantHours', label: 'opening hours' },
                    { id: 'restaurantClosingHours', label: 'closing hours' },
                    { id: 'restaurantOpeningDays', label: 'opening days' },
                    { id: 'restaurantPriceRange', label: 'price range' },
                    { id: 'restaurantFrontImage', label: 'restaurant front image URL' },
                    { id: 'restaurantMenuImages', label: 'menu image URLs' }
                ];

                for (const field of requiredRestaurantFields) {
                    const value = document.getElementById(field.id).value.trim();
                    if (!value) {
                        event.preventDefault();
                        showSignupError('Please enter the ' + field.label + ' before creating a restaurant account.');
                        return;
                    }
                }
            }
        });
    </script>
</body>
</html>
