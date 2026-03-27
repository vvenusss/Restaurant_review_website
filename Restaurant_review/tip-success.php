<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$restaurantId = isset($_GET['restaurant_id']) && ctype_digit($_GET['restaurant_id'])
    ? (int) $_GET['restaurant_id']
    : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodview - Tip Sent!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .result-wrapper {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .result-card {
            background: #fff;
            border-radius: 1.25rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.10);
            padding: 3rem 2rem;
            max-width: 460px;
            width: 100%;
            text-align: center;
        }
        .result-icon {
            width: 80px;
            height: 80px;
            background: #d1fae5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
            color: #059669;
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>

    <div class="result-wrapper">
        <div class="result-card">
            <div class="result-icon">
                <i class="bi bi-check-lg"></i>
            </div>
            <h1 class="h3 fw-bold mb-2">Tip sent successfully! 🎉</h1>
            <p class="text-muted mb-4">
                Your tip has been received. The restaurant truly appreciates your generosity and support!
            </p>

            <div class="d-grid gap-2">
                <?php if ($restaurantId): ?>
                    <a href="restaurant.php?id=<?php echo $restaurantId; ?>"
                       class="btn btn-primary py-2 fw-semibold">
                        <i class="bi bi-arrow-left me-2"></i> Back to Restaurant
                    </a>
                <?php endif; ?>
                <a href="index.php" class="btn btn-outline-secondary py-2">
                    <i class="bi bi-house me-2"></i> Go to Homepage
                </a>
            </div>
        </div>
    </div>

    <?php include('includes/footer.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
