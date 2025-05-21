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

// Fetch user info
$stmt = $pdo->prepare("
    SELECT 
        u.id,
        u.username,
        u.balance,
        u.total_spent,
        l.name AS level_name,
        l.discount,
        l.required_amount
    FROM users u
    LEFT JOIN level l ON u.level_id = l.id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate next level progress
$stmt = $pdo->prepare("SELECT required_amount FROM level WHERE required_amount > ? ORDER BY required_amount ASC LIMIT 1");
$stmt->execute([$user['total_spent']]);
$nextLevel = $stmt->fetch(PDO::FETCH_ASSOC);

$next_amount = $nextLevel ? $nextLevel['required_amount'] : $user['total_spent'];
$progress = $next_amount > 0 ? min(100, ($user['total_spent'] / $next_amount) * 100) : 100;

// Fetch total orders
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$total_orders = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html>

<head>
    <title>User Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="asha.png">
    <style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background: #111;
        color: #fff;
    }

    .container {
        max-width: 400px;
        margin: auto;
        padding: 20px;
        text-align: center;
    }

    .nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-left: 30px;
        margin-right: 30px;
    }

    .header img {
        width: 150px;
    }

    .logo {
        font-size: 24px;
        font-weight: bold;
        color: #3a8eff;
        margin-bottom: 20px;
    }

    .card {
        background: #ddd;
        color: #000;
        border-radius: 10px;
        padding: 12px;
        min-height: 40px;
        margin: 10px 0;
        font-weight: bold;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .level-box {
        background: #1e1e1e;
        border-radius: 12px;
        padding: 15px;
        margin-top: 15px;
    }

    .level-title {
        font-size: 18px;
        font-weight: bold;
        color: #4ea4ff;
        margin-bottom: 10px;
    }

    .progress-container {
        background: #333;
        border-radius: 10px;
        overflow: hidden;
        height: 20px;
        margin-bottom: 5px;
    }

    .progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #4ea4ff, #00c6ff);
        transition: width 0.3s ease;
    }

    .progress-label {
        font-size: 13px;
        color: #ccc;
    }

    .btn-order {
        margin-top: 20px;
        background-color: #4ea4ff;
        color: white;
        border: none;
        border-radius: 12px;
        padding: 12px;
        font-size: 16px;
        font-weight: bold;
        width: 100%;
        cursor: pointer;
    }

    .logout-link {
        display: block;
        margin-top: 20px;
        color: #ccc;
        font-size: 14px;
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
        align-items: center;
    }

    .dropdown a:last-child {
        border-bottom: none;
    }

    .dropdown a:hover {
        background-color: #333;
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
                    <a href="services.php">🛒 All Services</a>
                    <a href="history.php">🧾 History</a>
                    <a href="funds.php">💶 Add Funds</a>
                    <a href="fundhistories.php">🧾 Funds History</a>
                    <a href="tickets.php">🎫 My Tickets</a>
                    <a href="logout.php">🚪 Logout</a>
                </div>
            </div>
        </div>

        <div class="level-box">
            <div class="level-title">🏆 Level: <?= htmlspecialchars($user['level_name']) ?></div>
            <div class="progress-container">
                <div class="progress-bar" style="width: <?= $progress ?>%;"></div>
            </div>
            <div class="progress-label">
                <?= number_format($user['total_spent'], 0) ?> MMK / <?= number_format($next_amount, 0) ?> MMK
            </div>
        </div>

        <div class="card">
            <div>Balance</div>
            <div><?= number_format($user['balance'], 0) ?> MMK</div>
        </div>

        <div class="card">
            <div>Discount</div>
            <div><?= htmlspecialchars($user['discount']) ?>%</div>
        </div>

        <div class="card">
            <div>Total Spent</div>
            <div><?= number_format($user['total_spent'], 0) ?> MMK</div>
        </div>

        <div class="card">
            <div>Total Orders</div>
            <div><?= $total_orders ?></div>
        </div>

        <a href="order.php"><button class="btn-order">New Order</button></a>

    </div>

    <script>
    function toggleMenu() {
        const dropdown = document.getElementById('menuDropdown');
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    }

    document.addEventListener('click', function(e) {
        const menu = document.querySelector('.menu');
        if (!menu.contains(e.target)) {
            document.getElementById('menuDropdown').style.display = 'none';
        }
    });
    </script>
</body>

</html>