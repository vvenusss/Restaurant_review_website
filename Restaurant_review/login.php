<?php
session_start();
require_once __DIR__ . '/includes/restaurant-db.php';

$isAuthenticated = isset($_SESSION['role']);
$currentRole = $_SESSION['role'] ?? 'guest';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errorMessage = 'Please enter your email and password before logging in.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Please enter a valid email address.';
    } else {
        $connection = getDatabaseConnection($errorMessage);

        if ($connection) {
            $stmt = $connection->prepare('SELECT idusers, name, email, password, userType FROM users WHERE email = ? LIMIT 1');

            if (!$stmt) {
                $errorMessage = 'Failed to prepare login query: ' . $connection->error;
            } else {
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $stmt->bind_result($userId, $userName, $userEmail, $passwordHash, $userRole);
                $userFound = $stmt->fetch();

                if (!$userFound || !password_verify($password, $passwordHash)) {
                    $errorMessage = 'Invalid email or password.';
                } else {
                    $_SESSION['idusers'] = (int) $userId;
                    $_SESSION['name'] = $userName;
                    $_SESSION['email'] = $userEmail;
                    $_SESSION['role'] = $userRole;

                    if ($_SESSION['role'] === 'admin') {
                        header('Location: admin-dashboard.php');
                    } else {
                        header('Location: dashboard.php');
                    }
                    $stmt->close();
                    $connection->close();
                    exit;
                }

                $stmt->close();
            }

            $connection->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodview - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include("includes/header.php"); ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="text-center mb-4">
                    <h2>Login</h2>
                    <p class="text-muted mb-0">Sign in to access your dashboard, search tools, and role-based features.</p>
                </div>

                <?php if ($errorMessage !== ''): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                <?php endif; ?>

                <form class="shadow-sm rounded bg-white p-4" method="post" action="login.php">
                    <div class="mb-3">
                        <label for="loginEmail" class="form-label">Email</label>
                        <input type="email" id="loginEmail" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-2">
                        <label for="loginPassword" class="form-label">Password</label>
                        <input type="password" id="loginPassword" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
                    <p class="text-center text-muted small mb-0">Role is determined by your userType in the users table.</p>
                </form>
            </div>
        </div>
    </div>
    <?php include("includes/footer.php"); ?>

</body>
</html>
