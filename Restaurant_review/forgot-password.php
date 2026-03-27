<?php
$message = '';
$error = '';
$defaultEmail = 'oruicheng2501@gmail.com';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $error = 'Please enter your email address before requesting a password reset.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $message = 'Password reset instructions have been sent to ' . $email . '.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodview - Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include('includes/header.php'); ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="text-center mb-4">
                    <h2>Forgot Password</h2>
                    <p class="text-muted mb-0">Enter your email and we will simulate sending password reset instructions.</p>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($message !== ''): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <form method="post" class="shadow-sm rounded bg-white p-4">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($defaultEmail); ?>" required>
                    </div>
                    <div class="d-flex gap-3 flex-wrap">
                        <button type="submit" class="btn btn-primary">Send Reset Link</button>
                        <a href="login.php" class="btn btn-outline-secondary">Back to Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include('includes/footer.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
