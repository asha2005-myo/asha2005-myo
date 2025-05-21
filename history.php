<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = new PDO('mysql:host=localhost;dbname=zwamlszw_InsertCart', 'zwamlszw_myo', '914161827');

// Fetch user info
$stmt = $pdo->prepare("
    SELECT 
        u.username,
        l.name AS level_name
    FROM users u
    LEFT JOIN level l ON u.level_id = l.id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch user orders
$stmt = $pdo->prepare("
    SELECT o.*, s.name AS service_name 
    FROM orders AS o
    LEFT JOIN services AS s ON o.service_id = s.service_id
    WHERE o.user_id = ?
    ORDER BY o.id DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// API status fetching
$api_key = '0579b56348043d4ee1d70f92bb5601ed';
$liveStatuses = [];

foreach ($orders as $order) {
    $ch = curl_init('https://socialfastmm.com/api/v2');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'key' => $api_key,
        'action' => 'status',
        'order' => $order['order_id']
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);

    if (!curl_errno($ch)) {
        $status = json_decode($response, true);
        $liveStatuses[$order['order_id']] = $status;
    } else {
        $liveStatuses[$order['order_id']] = ['error' => curl_error($ch)];
    }

    curl_close($ch);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Orders</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="asha.png">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #0a0a0a;
            color: #ffffff;
        }

        .container {
            max-width: 400px;
            margin: auto;
            padding: 20px;
        }

        .header img {
            width: 150px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #3a8eff;
            margin-top: 10px;
        }

        .nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 10px 0 20px;
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
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
        }

        input#searchInput {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: none;
            font-size: 16px;
        }

        .card {
            background-color: #1a1a1a;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .card h3 {
            margin-top: 0;
            font-size: 18px;
            color: #007bff;
        }

        .card p {
            margin: 4px 0;
            font-size: 14px;
            line-height: 1.4;
        }

        .word-wrap {
            word-break: break-all;
        }

        .status {
            padding: 4px 8px;
            display: inline-block;
            border-radius: 6px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status.completed {
            background-color: #28a745;
            color: white;
        }

        .status.pending {
            background-color: #ffc107;
            color: black;
        }

        .status.processing {
            background-color: #17a2b8;
            color: white;
        }

        .status.canceled {
            background-color: #dc3545;
            color: white;
        }

        .status.default {
            background-color: #666;
            color: white;
        }

        .error {
            color: #ff4d4f;
            font-weight: bold;
        }

        .header {
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header img {
            width: 150px;
            margin-bottom: 10px;
        }

        @media screen and (max-width: 600px) {
            .card {
                padding: 12px;
            }

            .card h3 {
                font-size: 16px;
            }

            .card p {
                font-size: 13px;
            }
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
                    <a href="funds.php">💶 Add Funds</a>
                    <a href="fundhistories.php">🧾 Funds History</a>
                    <a href="tickets.php">🎫 My Tickets</a>
                    <a href="logout.php">🚪 Logout</a>
                </div>
            </div>
        </div>

        <h2>My Orders with Live Status</h2>

        <!-- Search bar with button -->
<div style="display: flex; gap: 8px; margin-bottom: 20px;">
  <input type="text" id="searchInput" placeholder="Search by Order ID or Link" style="flex:1; padding:10px; border-radius:8px; border:none; font-size:16px;">
  <button id="searchBtn" style="padding:10px 16px; border:none; border-radius:8px; background:#3a8eff; color:#fff; cursor:pointer; font-weight:600; height:40px;">Search</button>
</div>

        <?php foreach ($orders as $order): ?>
        <?php $status = $liveStatuses[$order['order_id']] ?? null; ?>
        <div class="card">
            <h3>Order #<?= htmlspecialchars($order['order_id']) ?></h3>
            <p><strong>Service ID:</strong> <?= htmlspecialchars($order['service_id'] ?? 'Unknown') ?></p>
            <p><strong>Service:</strong> <?= htmlspecialchars($order['service_name'] ?? 'Unknown') ?></p>
            <p class="word-wrap"><strong>Link:</strong> <a href="<?= htmlspecialchars($order['link']) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($order['link']) ?></a></p>
            <p><strong>Quantity:</strong> <?= htmlspecialchars($order['quantity']) ?></p>
            <p><strong>Ordered At:</strong> <?= htmlspecialchars($order['created_at']) ?></p>

            <?php if (isset($status['error'])): ?>
            <p class="error">API Error: <?= htmlspecialchars($status['error']) ?></p>
            <?php elseif ($status): ?>
            <?php
                    $statusText = strtolower($status['status']);
                    $statusClass = in_array($statusText, ['completed', 'pending', 'processing', 'canceled']) ? $statusText : 'default';
                ?>
            <p><strong>Status:</strong> <span class="status <?= $statusClass ?>"><?= htmlspecialchars($status['status']) ?></span></p>
            <p><strong>Charge:</strong> <?= htmlspecialchars($order['amount']) ?> MMK</p>
            <p><strong>Start Count:</strong> <?= htmlspecialchars($status['start_count']) ?></p>
            <p><strong>Remains:</strong> <?= htmlspecialchars($status['remains']) ?></p>
            <?php else: ?>
            <p>No live status available.</p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
        function toggleMenu() {
            const dropdown = document.getElementById('menuDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        document.addEventListener('click', function (e) {
            const menu = document.querySelector('.menu');
            if (!menu.contains(e.target)) {
                document.getElementById('menuDropdown').style.display = 'none';
            }
        });

        // Search filter for orders
        document.getElementById('searchInput').addEventListener('input', function () {
            const query = this.value.toLowerCase();
            const cards = document.querySelectorAll('.card');

            cards.forEach(card => {
                const orderId = card.querySelector('h3').textContent.toLowerCase();
                const link = card.querySelector('p.word-wrap a').textContent.toLowerCase();

                if (orderId.includes(query) || link.includes(query)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        document.getElementById('searchBtn').addEventListener('click', function () {
    const query = document.getElementById('searchInput').value.toLowerCase();
    const cards = document.querySelectorAll('.card');

    cards.forEach(card => {
        const orderId = card.querySelector('h3').textContent.toLowerCase();
        const link = card.querySelector('p.word-wrap a').textContent.toLowerCase();

        if (orderId.includes(query) || link.includes(query)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
});
    </script>
</body>

</html>