<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = new PDO('mysql:host=localhost;dbname=zwamlszw_InsertCart', 'zwamlszw_myo', '914161827');

// Fetch user info
$stmt = $pdo->prepare("
    SELECT u.username, l.name AS level_name
    FROM users u
    LEFT JOIN level l ON u.level_id = l.id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle search query
$search = $_GET['search'] ?? '';
$sql = "SELECT 
            services.service_id,
            services.name,
            services.category_id,
            i.name AS category_name,
            services.sell_price,
            services.description
        FROM services 
        LEFT JOIN categories i ON i.id = services.category_id";

if (!empty($search)) {
    $sql .= " WHERE 
                services.service_id LIKE :search 
                OR services.name LIKE :search 
                OR i.name LIKE :search";
}

$sql .= " ORDER BY services.category_id ASC";

$stmt = $pdo->prepare($sql);
if (!empty($search)) {
    $stmt->execute(['search' => "%$search%"]);
} else {
    $stmt->execute();
}
$services = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Services</title>
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
        .header{
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            word-break: break-word;
        }

        form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        input[type="text"] {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            border: none;
        }

        button {
            padding: 10px 16px;
            border: none;
            background-color: #3a8eff;
            color: white;
            border-radius: 8px;
            cursor: pointer;
        }

        button:hover {
            background-color: #1d6edb;
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

            button {
                width: 20%;
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
                      d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5"/>
            </svg>

            <div class="dropdown" id="menuDropdown">
                <a href="dashboard.php">🏠 Dashboard</a>
                <a href="order.php">🛒 New Order</a>
                <a href="funds.php">💶 Add Funds</a>
                <a href="fundhistories.php">🧾 Funds History</a>
                <a href="tickets.php">🎫 My Tickets</a>
                <a href="logout.php">🚪 Logout</a>
            </div>
        </div>
    </div>

    <!-- Search Form -->
    <form method="GET">
        <input type="text" name="search" placeholder="Search by ID, name, or category" 
               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        <button type="submit">🔍</button>
    </form>

    <h2>All Services</h2>

    <?php foreach ($services as $service): ?>
        <div class="card">
            <h3><?= htmlspecialchars($service['name']) ?></h3>
            <p><strong>Category Name: </strong> <?= htmlspecialchars($service['category_name']) ?></p>
            <p><strong>Service ID: </strong> <?= htmlspecialchars($service['service_id']) ?></p>
            <p><strong>Sell Price: </strong><?= htmlspecialchars($service['sell_price']) ?></p>
            <p class="word-wrap"><strong>Description:<br></strong> <?= nl2br(htmlspecialchars($service['description'] ?? 'N/A')) ?></p>
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
</script>
</body>
</html>