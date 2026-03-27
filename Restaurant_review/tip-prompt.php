<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Only authenticated diners can tip
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'diner') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/restaurant-db.php';
require_once __DIR__ . '/stripe-config.php';

$restaurantId = isset($_GET['restaurant_id']) && ctype_digit($_GET['restaurant_id'])
    ? (int) $_GET['restaurant_id']
    : null;

$restaurant = null;

if ($restaurantId) {
    $dbError = '';
    $conn = getDatabaseConnection($dbError);
    if ($conn) {
        $restaurant = getRestaurantById($conn, $restaurantId, $dbError);
        $conn->close();
    }
}

$restaurantName = $restaurant['RestaurantName'] ?? 'this restaurant';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodview - Leave a Tip</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .tip-page-wrapper {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .tip-card {
            background: #fff;
            border-radius: 1.25rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.10);
            padding: 2.5rem 2rem;
            max-width: 520px;
            width: 100%;
        }
        .tip-icon {
            font-size: 3rem;
            color: #f59e0b;
        }
        .tip-preset-btn {
            border: 2px solid #dee2e6;
            background: #fff;
            border-radius: 0.75rem;
            padding: 0.75rem 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            transition: all 0.18s;
            cursor: pointer;
            width: 100%;
        }
        .tip-preset-btn:hover,
        .tip-preset-btn.active {
            border-color: #0d6efd;
            background: #e8f0fe;
            color: #0d6efd;
        }
        .tip-mode-btn {
            border: 2px solid #dee2e6;
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 0.4rem 1rem;
            font-size: 0.9rem;
            font-weight: 500;
            color: #555;
            cursor: pointer;
            transition: all 0.15s;
        }
        .tip-mode-btn.active {
            border-color: #0d6efd;
            background: #0d6efd;
            color: #fff;
        }
        .tip-summary {
            background: #f0f7ff;
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
            font-size: 1rem;
        }
        #customAmountInput, #billAmountInput {
            border-radius: 0.6rem;
            border: 2px solid #dee2e6;
            padding: 0.6rem 1rem;
            font-size: 1rem;
            width: 100%;
            margin-top: 0.5rem;
            outline: none;
            transition: border-color 0.15s;
        }
        #customAmountInput:focus, #billAmountInput:focus {
            border-color: #0d6efd;
        }
        .divider-text {
            color: #adb5bd;
            font-size: 0.85rem;
            text-align: center;
            margin: 0.5rem 0;
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>

    <div class="tip-page-wrapper">
        <div class="tip-card">
            <!-- Header -->
            <div class="text-center mb-4">
                <div class="tip-icon mb-2"><i class="bi bi-emoji-smile-fill"></i></div>
                <h1 class="h3 fw-bold mb-1">Review submitted! 🎉</h1>
                <p class="text-muted mb-0">
                    Would you like to leave a tip for <strong><?php echo htmlspecialchars($restaurantName); ?></strong>?
                </p>
            </div>

            <!-- Tip Mode Toggle -->
            <div class="d-flex gap-2 justify-content-center mb-4">
                <button class="tip-mode-btn active" id="modeFixed" onclick="switchMode('fixed')">Fixed</button>
                <button class="tip-mode-btn" id="modeCustom" onclick="switchMode('custom')">Custom</button>
                <button class="tip-mode-btn" id="modePercent" onclick="switchMode('percent')">Percentage</button>
            </div>

            <!-- Fixed Amounts -->
            <div id="sectionFixed">
                <p class="text-muted small mb-2 text-center">Select a tip amount</p>
                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <button class="tip-preset-btn" onclick="selectPreset(this, 2)">$2</button>
                    </div>
                    <div class="col-4">
                        <button class="tip-preset-btn" onclick="selectPreset(this, 5)">$5</button>
                    </div>
                    <div class="col-4">
                        <button class="tip-preset-btn" onclick="selectPreset(this, 10)">$10</button>
                    </div>
                </div>
            </div>

            <!-- Custom Amount -->
            <div id="sectionCustom" style="display:none;">
                <p class="text-muted small mb-1 text-center">Enter any amount you'd like</p>
                <input type="number" id="customAmountInput" min="1" step="0.01"
                       placeholder="e.g. 7.50" oninput="updateCustom(this.value)">
            </div>

            <!-- Percentage -->
            <div id="sectionPercent" style="display:none;">
                <p class="text-muted small mb-1 text-center">Enter your bill total to calculate tip</p>
                <input type="number" id="billAmountInput" min="1" step="0.01"
                       placeholder="Your bill amount (e.g. 45.00)" oninput="updatePercent()">
                <div class="row g-2 mt-2 mb-2">
                    <div class="col-4">
                        <button class="tip-preset-btn" id="pct5" onclick="selectPct(this, 5)">5%</button>
                    </div>
                    <div class="col-4">
                        <button class="tip-preset-btn" id="pct10" onclick="selectPct(this, 10)">10%</button>
                    </div>
                    <div class="col-4">
                        <button class="tip-preset-btn" id="pct15" onclick="selectPct(this, 15)">15%</button>
                    </div>
                </div>
            </div>

            <!-- Tip Summary -->
            <div class="tip-summary mb-4" id="tipSummary" style="display:none;">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">Tip amount</span>
                    <span class="fw-bold fs-5 text-primary" id="tipDisplay">$0.00</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <form method="POST" action="create-checkout-session.php" id="tipForm">
                <input type="hidden" name="tip_amount" id="tipAmountField" value="0">
                <input type="hidden" name="restaurant_id" value="<?php echo (int)$restaurantId; ?>">
                <input type="hidden" name="restaurant_name" value="<?php echo htmlspecialchars($restaurantName); ?>">

                <button type="submit" class="btn btn-primary w-100 mb-2 py-2 fw-semibold fs-5"
                        id="tipSubmitBtn" disabled>
                    <i class="bi bi-credit-card me-2"></i> Proceed to Payment
                </button>
            </form>

            <div class="divider-text">or</div>

            <a href="restaurant.php?id=<?php echo (int)$restaurantId; ?>"
               class="btn btn-outline-secondary w-100 py-2">
                <i class="bi bi-x-circle me-2"></i> No thanks, skip
            </a>

        </div>
    </div>

    <?php include('includes/footer.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentMode  = 'fixed';
        let selectedPct  = 0;
        let tipAmount    = 0;

        function switchMode(mode) {
            currentMode = mode;
            tipAmount   = 0;
            selectedPct = 0;

            // Toggle sections
            document.getElementById('sectionFixed').style.display   = mode === 'fixed'   ? '' : 'none';
            document.getElementById('sectionCustom').style.display  = mode === 'custom'  ? '' : 'none';
            document.getElementById('sectionPercent').style.display = mode === 'percent' ? '' : 'none';

            // Toggle mode buttons
            ['modeFixed','modeCustom','modePercent'].forEach(id => {
                document.getElementById(id).classList.remove('active');
            });
            document.getElementById('mode' + mode.charAt(0).toUpperCase() + mode.slice(1)).classList.add('active');

            // Reset preset buttons
            document.querySelectorAll('.tip-preset-btn').forEach(b => b.classList.remove('active'));

            updateSummary();
        }

        function selectPreset(btn, amount) {
            document.querySelectorAll('#sectionFixed .tip-preset-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            tipAmount = amount;
            updateSummary();
        }

        function updateCustom(val) {
            tipAmount = parseFloat(val) || 0;
            updateSummary();
        }

        function selectPct(btn, pct) {
            document.querySelectorAll('#sectionPercent .tip-preset-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selectedPct = pct;
            updatePercent();
        }

        function updatePercent() {
            const bill = parseFloat(document.getElementById('billAmountInput').value) || 0;
            tipAmount  = selectedPct > 0 ? parseFloat((bill * selectedPct / 100).toFixed(2)) : 0;
            updateSummary();
        }

        function updateSummary() {
            const summary   = document.getElementById('tipSummary');
            const display   = document.getElementById('tipDisplay');
            const field     = document.getElementById('tipAmountField');
            const submitBtn = document.getElementById('tipSubmitBtn');

            if (tipAmount > 0) {
                summary.style.display   = '';
                display.textContent     = '$' + tipAmount.toFixed(2);
                field.value             = tipAmount.toFixed(2);
                submitBtn.disabled      = false;
            } else {
                summary.style.display   = 'none';
                field.value             = '0';
                submitBtn.disabled      = true;
            }
        }
    </script>
</body>
</html>
