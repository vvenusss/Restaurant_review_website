<?php
    include('includes/header.php');

if (!isset($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

$name = $_SESSION['name'] ?? ' ';
$email = $_SESSION['email'] ?? ' ';
$role = $_SESSION['role'] ?? ' ';
$pageTitle = 'View Profile';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodview - <?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="container py-5">

        <h2 class="mb-4">Your Profile</h2>

        <div class="card shadow-sm">
            <div class="card-body">

                <div class="mb-3">
                    <strong>Name:</strong>
                    <p><?php echo htmlspecialchars($name); ?></p>
                </div>

                <div class="mb-3">
                    <strong>Email:</strong>
                    <p><?php echo htmlspecialchars($email); ?></p>
                </div>

                <div class="mb-3">
                    <strong>Password:</strong>
                    <p>••••••••</p>
                </div>

                <div class="mb-3">
                    <strong>Role:</strong>
                    <p><?php echo htmlspecialchars($role); ?></p>
                </div>

                <div class="mt-4">

                    <a href="edit-profile.php" 
                    class="btn btn-primary">
                    Edit Profile
                    </a>

                    <a href="dashboard.php" 
                    class="btn btn-secondary">
                    Back to Dashboard
                    </a>

                </div>

            </div>
        </div>

    </div>

    <?php include('includes/footer.php'); ?>

</body>
</html>