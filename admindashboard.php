<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

$pdo = new PDO('mysql:host=localhost;dbname=zwamlszw_InsertCart', 'zwamlszw_myo', '914161827');

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

date_default_timezone_set('Asia/Yangon'); // or your preferred timezone

$today = date('Y-m-d');

// 1. Users signed up today
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) = ?");
$stmt->execute([$today]);
$usersToday = $stmt->fetchColumn();

// 2. Total spent today
$stmt = $pdo->prepare("SELECT SUM(amount) FROM orders WHERE DATE(created_at) = ?");
$stmt->execute([$today]);
$totalSpentToday = $stmt->fetchColumn() ?? 0;

// 3. Total number of users
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// 4. Total revenue
$totalRevenue = $pdo->query("SELECT SUM(amount) FROM orders")->fetchColumn() ?? 0;

// 5. Sum of all user balances
$stmt = $pdo->query("SELECT SUM(CASE WHEN balance <= 200 THEN 5 ELSE balance END) AS adjusted_balance FROM users");
$totalUserBalance = $stmt->fetchColumn() ?? 0;

// 6. API balance fetch
$apiKey = '0579b56348043d4ee1d70f92bb5601ed';
$apiUrl = 'https://socialfastmm.com/api/v2';

$postData = [
    'key' => $apiKey,
    'action' => 'balance'
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
$response = curl_exec($ch);
curl_close($ch);

$apiBalance = 0;
if ($response) {
    $data = json_decode($response, true);
    if (isset($data['balance'])) {
        $apiBalance = $data['balance'];
    }
}

// 7. Calculate your total financial difference
$convertedApiBalanceMMK = $apiBalance * 4500;
$adjustedUserBalance = $totalUserBalance * 0.85;
$difference = $adjustedUserBalance - $convertedApiBalanceMMK;

// 8. Total top-up requests today
$stmt = $pdo->prepare("SELECT COUNT(*) FROM topup_requests WHERE status = ?");
$stmt->execute(['pending']);
$requestsToday = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Asha Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="asha.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        body {
            background-color: #111;
            color: #fff;
            padding: 20px;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-right: 20px;
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
        padding: 10px 10px;
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

        .profile {
            margin-bottom: 20px;
        }
        .profile p {
            font-size: 16px;
            color: #bbb;
        }
        .dashboard-header {
            font-size: 20px;
            margin-bottom: 30px;
            text-align: center;
        }
        .icons {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            background-color: white;
            border: none;
            border-radius: 20px;
            padding: 20px;
            width: 90%;
            margin-right: 20px;
            margin-left: 20px;
        }
        .icon {
            width: 40px;
            height: 40px;
            background-color: #444;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .icon.green {
            position: relative;
        }
        .icon.green span {
            position: absolute;
            background-color: white;
            top: -10px;
            right: -10px;
            color: black;
            font-size: 16px;
            font-weight: bold;
            width: 25px;
            height: 25px;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cards {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }
        .card {
            background: #2b2b2b;
            padding: 15px;
            border-radius: 10px;
            width: 160px;
            text-align: center;
        }
        .card h3 {
            font-size: 14px;
            color: #ccc;
            margin-bottom: 10px;
        }
        .card p {
            font-size: 18px;
            color: #fff;
            font-weight: bold;
        }
        
    .nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-left: 30px;
        margin-right: 30px;
        margin-bottom: 30px;
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
    .flex{
        width: 330px;
    }

    </style>
</head>
<body>
    <div class="header">
            <img src="asha.png" alt="Logo">
            <div class="logo">Asha Company</div>
        </div>
 <div class="nav">
            <div class="user">
                <h3>Myo Thiha Ko</h3>
            </div>
            <div class="menu" onclick="toggleMenu()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="#fff" class="menu-icon" viewBox="0 0 16 16">
                    <path fill-rule="evenodd"
                        d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5" />
                </svg>

                <div class="dropdown" id="menuDropdown">
                <a href="dashboard.php">🏠 Dashboard</a>
                <a href="topup_approvals.php">💶 Approve Funds</a>
                <a href="adminservice.php">🧾 Add Service</a>
                <a href="adminedit.php">✏️ Edit Service</a>
                <a href="logout.php">🚪 Logout</a>
            </div>
            </div>
        </div>    <div class="dashboard-header">Admin Dashboard</div>
    <div class="icons">
        <div class="icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart-plus" viewBox="0 0 16 16">
  <path d="M9 5.5a.5.5 0 0 0-1 0V7H6.5a.5.5 0 0 0 0 1H8v1.5a.5.5 0 0 0 1 0V8h1.5a.5.5 0 0 0 0-1H9z"/>
  <path d="M.5 1a.5.5 0 0 0 0 1h1.11l.401 1.607 1.498 7.985A.5.5 0 0 0 4 12h1a2 2 0 1 0 0 4 2 2 0 0 0 0-4h7a2 2 0 1 0 0 4 2 2 0 0 0 0-4h1a.5.5 0 0 0 .491-.408l1.5-8A.5.5 0 0 0 14.5 3H2.89l-.405-1.621A.5.5 0 0 0 2 1zm3.915 10L3.102 4h10.796l-1.313 7zM6 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0m7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
</svg>
        </div>
        <a href="admin_users.php" style="color: white;">
            <div class="icon green">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16">
                    <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
                    <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1"/>
                </svg>
            </div>
        </a>
        <a href="topup_approvals.php" style="color: white;">
            <div class="icon green">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-wallet-fill" viewBox="0 0 16 16">
                    <path d="M1.5 2A1.5 1.5 0 0 0 0 3.5v2h6a.5.5 0 0 1 .5.5c0 .253.08.644.306.958.207.288.557.542 1.194.542s.987-.254 1.194-.542C9.42 6.644 9.5 6.253 9.5 6a.5.5 0 0 1 .5-.5h6v-2A1.5 1.5 0 0 0 14.5 2z"/>
                    <path d="M16 6.5h-5.551a2.7 2.7 0 0 1-.443 1.042C9.613 8.088 8.963 8.5 8 8.5s-1.613-.412-2.006-.958A2.7 2.7 0 0 1 5.551 6.5H0v6A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5z"/>
                </svg>
                <span><?= $requestsToday ?></span>
            </div>
        </a>
    </div>
    <div class="cards">
        <div class="card">
            <h3>API Account</h3>
            <p><?= number_format($apiBalance * 4500) ?> MMK</p>
        </div>
        <div class="card">
            <h3>Total User Balance</h3>
            <p><?= number_format($totalUserBalance) ?> MMK</p>
        </div>
        <div class="card flex">
            <h3>To Add Amount</h3>
            <p><?= number_format($difference) ?> MMK</p>
        </div>
        <div class="card">
            <h3>New Users</h3>
            <p><?= $usersToday ?></p>
        </div>
        <div class="card">
            <h3>Total Spent Today</h3>
            <p><?= number_format($totalSpentToday) ?> MMK</p>
        </div>
        <div class="card flex">
            <h3>Total Users</h3>
            <p><?= $totalUsers ?></p>
        </div>
    </div>
     <script>
        function toggleMenu() {
            const dropdown = document.getElementById('menuDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }
    </script>
</body>
</html>