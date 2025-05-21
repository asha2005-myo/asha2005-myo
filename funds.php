<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$pdo = new PDO('mysql:host=localhost;dbname=zwamlszw_InsertCart', 'zwamlszw_myo', '914161827');
$message = null;

// Fetch user info with level
$stmt = $pdo->prepare("
    SELECT 
        u.username,
        l.name AS level_name,
        l.discount
    FROM users u
    LEFT JOIN level l ON u.level_id = l.id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch payment methods
$methods = $pdo->query("SELECT id, name, description FROM payment")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $paid = $_POST['method'];
    $txn = trim($_POST['transaction_id']);
    $userId = $_SESSION['user_id'];

    // Validate input
    if ($amount <= 0 || empty($paid) || empty($txn)) {
        $message = "❌ Please fill all fields correctly.";
    } elseif (!preg_match("/^\d{5}$/", $txn)) {  // Ensure transaction ID is 5 digits
        $message = "❌ Transaction ID should be exactly 5 digits.";
    } else {
        // Insert request into the database
        $stmt = $pdo->prepare("INSERT INTO topup_requests (user_id, amount, paid, transaction_id) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$userId, $amount, $paid, $txn])) {
            $message = "✅ Top-up request submitted. Please wait for admin approval.";
        } else {
            $message = "❌ Failed to submit request.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Top-Up Request</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="asha.png">
    <style>
    * {
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', sans-serif;
        background-color: #0d0d0d;
        margin: 0;
        padding: 0;
        color: white;
    }

    .container {
        max-width: 400px;
        margin: auto;
        padding: 20px;
        text-align: center;
    }

    .header {
        height: 100px;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .header img {
        width: 150px;
    }

    .logo {
        font-size: 24px;
        font-weight: bold;
        color: #3a8eff;
    }

    .nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 0 30px;

    }

    .menu {
        position: relative;
        cursor: pointer;
    }

    .menu-icon {
        width: 24px;
        height: 24px;
    }

    .dropdown {
        display: none;
        position: absolute;
        right: 0;
        top: 28px;
        background: #222;
        border: 1px solid #444;
        border-radius: 8px;
        width: 160px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        text-align: left;
        z-index: 999;
    }

    .dropdown a {
        display: block;
        padding: 10px 15px;
        color: #fff;
        text-decoration: none;
        border-bottom: 1px solid #333;
    }

    .dropdown a:last-child {
        border-bottom: none;
    }

    .dropdown a:hover {
        background-color: #333;
    }

    h2 {
        color: #ffffff;
        font-size: 1.5em;
        margin-bottom: 20px;
        text-align: center;
    }

    label {
        margin-top: 15px;
        margin-bottom: 5px;
        font-weight: 600;
        display: block;
    }

    input[type="number"],
    input[type="text"],
    select {
        width: 100%;
        padding: 14px;
        font-size: 1em;
        border: none;
        border-radius: 25px;
        background: white;
        color: black;
        margin-bottom: 10px;
    }

    textarea {
        width: 100%;
        padding: 14px;
        font-size: 1em;
        border-radius: 25px;
        border: none;
        background: #ddd;
        margin-bottom: 10px;
        resize: none;
    }

    button {
        width: 100%;
        padding: 14px;
        border-radius: 25px;
        background-color: #4ea4ff;
        color: white;
        border: none;
        font-size: 1em;
        cursor: pointer;
        margin-top: 20px;
        font-weight: bold;
    }

    button:hover {
        background-color: #3b90e0;
    }

    .message {
        background-color: #d4edda;
        color: #155724;
        padding: 12px;
        margin-top: 15px;
        border-radius: 25px;
        border: 1px solid #c3e6cb;
    }

    .rank-box {
        background: #1e1e1e;
        padding: 15px;
        border-radius: 15px;
        margin-bottom: 20px;
        text-align: center;
    }

    .rank-box strong {
        font-size: 18px;
        color: #4ea4ff;
    }

    .rank-box small {
        display: block;
        margin-top: 4px;
        color: #ccc;
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <img src="asha.png" alt="Logo">
            <div class="logo">Asha Company</div>
        </div>

        <div class="nav">
            <div class="user">
                <h3><?= htmlspecialchars($user['username']) ?></h3>
            </div>
            <div class="menu" onclick="toggleMenu()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="#fff" class="menu-icon" viewBox="0 0 16 16">
                    <path fill-rule="evenodd"
                        d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5" />
                </svg>
                <div class="dropdown" id="menuDropdown">
                    <a href="dashboard.php">🏠 Dashboard</a>
                    <a href="order.php">🛒 New Order</a>
                    <a href="services.php">🛒 All Services</a>
                    <a href="history.php">🧾 History</a>
                    <a href="fundhistories.php">🧾 Funds History</a>
                    <a href="tickets.php">🎫 My Tickets</a>
                    <a href="logout.php">🚪 Logout</a>
                </div>
            </div>
        </div>

        <div class="rank-box">
            <div>🏆 Level: <?= htmlspecialchars($user['level_name']) ?></div>
            <small>Discount: <?= htmlspecialchars($user['discount']) ?>%</small>
        </div>

        <div class="contain">
            <h2>Request Top-Up</h2>

            <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form method="POST">
                <label for="method">Payment Method:</label>
                <select name="method" id="method" required>
                    <option value="">Select a method</option>
                    <?php foreach ($methods as $method): ?>
                    <option value="<?= htmlspecialchars($method['id']) ?>"
                        data-instruction="<?= htmlspecialchars($method['description']) ?>">
                        <?= htmlspecialchars($method['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <label for="instruction">Instruction:</label>
                <textarea id="instruction" rows="7" readonly placeholder="Instructions will appear here..."></textarea>

                <label for="amount">Amount (MMK):</label>
                <input type="number" name="amount" id="amount" required step="500" placeholder="e.g., 10000" min="1000">

                <label for="transaction_id">Transaction ID:</label>
                <input type="text" name="transaction_id" id="transaction_id" required
                    placeholder="Transaction ID (Last 5 digits only)">
                <button type="submit">Submit Request</button>
            </form>
        </div>

        <script>
            // Toggle the dropdown menu
            function toggleMenu() {
                const dropdown = document.getElementById('menuDropdown');
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            }

            // Close dropdown if clicked outside
            document.addEventListener('click', function(e) {
                const menu = document.querySelector('.menu');
                if (!menu.contains(e.target)) {
                    document.getElementById('menuDropdown').style.display = 'none';
                }
            });

            // Handle changing payment method and displaying instructions
            const methodSelect = document.getElementById("method");
            const instructionBox = document.getElementById("instruction");

            methodSelect.addEventListener("change", function() {
                const selected = this.options[this.selectedIndex];
                instructionBox.value = selected.getAttribute("data-instruction") ||
                    "Instructions will appear here...";
            });
        </script>
    </div>
</body>

</html>