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

// Fetch user info
$stmt = $pdo->prepare("
    SELECT 
        u.id,
        u.username,
        l.name AS level_name,
        l.discount,
        l.required_amount
    FROM users u
    LEFT JOIN level l ON u.level_id = l.id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch user's top-up history
$stmt = $pdo->prepare("SELECT * FROM topup_requests WHERE user_id = ? ORDER BY requested_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$topups = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Funds History</title>
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
            max-width: 800px;
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

        .card {
            background: #333;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .card .amount {
            font-size: 1.2em;
            font-weight: bold;
        }

        .card .transaction-id {
            font-size: 0.9em;
            color: #bbb;
        }

        .card .status {
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            margin-top: 10px;
            display: inline-block;
        }

        .card .status.approved {
            background-color: #28a745; /* Green */
            color: white;
        }

        .card .status.rejected {
            background-color: #dc3545; /* Red */
            color: white;
        }

        .card .status.pending {
            background-color: #fd7e14; /* Orange */
            color: white;
        }

        .card .date {
            font-size: 0.8em;
            color: #888;
            margin-top: 10px;
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
                      d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5"/>
            </svg>
            <div class="dropdown" id="menuDropdown">
                <a href="dashboard.php">🏠 Dashboard</a>
                <a href="order.php">🛒 New Order</a>
                <a href="services.php">🛒 All Services</a>
                <a href="history.php">🧾 History</a>
                <a href="tickets.php">🎫 My Tickets</a>
                <a href="logout.php">🚪 Logout</a>
            </div>
        </div>
    </div>

    <div class="rank-box">
        <div>🏆 Level: <?= htmlspecialchars($user['level_name']) ?></div>
        <small>Discount: <?= htmlspecialchars($user['discount']) ?>%</small>
    </div>

    <h2>Funds History</h2>

    <?php if (empty($topups)): ?>
        <div class="message">No top-up requests found.</div>
    <?php else: ?>
        <?php foreach ($topups as $topup): ?>
            <div class="card">
                <div class="amount"><?= htmlspecialchars($topup['amount']) ?> MMK</div>
                <div class="transaction-id">Transaction ID: <?= htmlspecialchars($topup['transaction_id']) ?></div>
                <div class="status <?= strtolower($topup['status']) ?>">
                    <?= ucfirst(htmlspecialchars($topup['status'])) ?>
                </div>
                <div class="date"><?= htmlspecialchars($topup['requested_at']) ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
    function toggleMenu() {
        const dropdown = document.getElementById('menuDropdown');
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    }
</script>
</body>
</html>