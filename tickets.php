<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = new PDO('mysql:host=localhost;dbname=zwamlszw_InsertCart', 'zwamlszw_myo', '914161827');

// Get logged in user info
$stmt = $pdo->prepare("
    SELECT u.username, l.name AS level_name
    FROM users u
    LEFT JOIN level l ON u.level_id = l.id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $ticcat_id = intval($_POST['category'] ?? 0);

    if ($title && $description && $ticcat_id) {
        $stmt = $pdo->prepare("
            INSERT INTO tickets (user_id, ticcat_id, title, description, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, 'Pending', NOW(), NOW())
        ");
        $stmt->execute([$_SESSION['user_id'], $ticcat_id, $title, $description]);
    }
}

// Fetch ticket categories
$categories = $pdo->query("SELECT id, name FROM ticketCategories")->fetchAll(PDO::FETCH_ASSOC);

// Fetch user tickets
$stmt = $pdo->prepare("
    SELECT t.*, c.name AS category_name
    FROM tickets t
    LEFT JOIN ticketCategories c ON t.ticcat_id = c.id
    WHERE t.user_id = ?
    ORDER BY t.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map statuses to CSS classes
function statusClass($status) {
    return match(strtolower($status)) {
        'pending' => 'status-pending',
        'in progress', 'inprogress' => 'status-inprogress',
        'resolved' => 'status-resolved',
        'closed' => 'status-closed',
        default => 'status-pending',
    };
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tickets</title>
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

        .header {
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
            color: #3a8eff;
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
            flex-direction: column;
            gap: 10px;
            margin-bottom: 30px;
        }

        input[type="text"],
        textarea,
        select {
            padding: 10px;
            border-radius: 8px;
            border: none;
            background-color: #333;
            color: #fff;
            font-size: 15px;
        }

        button {
            font-size: 15px;
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

        /* Status badges */
        .status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            min-width: 80px;
            text-align: center;
        }

        .status-pending {
            background-color: #f0ad4e; /* orange */
        }

        .status-inprogress {
            background-color: #5bc0de; /* blue */
        }

        .status-resolved {
            background-color: #5cb85c; /* green */
        }

        .status-closed {
            background-color: #777; /* gray */
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
                      d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5"/>
            </svg>
            <div class="dropdown" id="menuDropdown">
                <a href="dashboard.php">🏠 Dashboard</a>
                <a href="order.php">🛒 New Order</a>
                <a href="services.php">🛒 All Services</a>
                <a href="history.php">🧾 History</a>
                <a href="funds.php">💶 Add Funds</a>
                <a href="fundhistories.php">🧾 Funds History</a>
                <a href="logout.php">🚪 Logout</a>
            </div>
        </div>
    </div>

    <h2>Submit New Ticket</h2>

    <form method="POST">
        <input type="text" name="title" placeholder="Enter title" required>
        <select name="category" required>
            <option value="">Select category</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <textarea name="description" rows="4" placeholder="Describe your issue..." required></textarea>
        <button type="submit">Submit Ticket</button>
    </form>

    <h2>My Tickets</h2>

    <?php if ($tickets): ?>
        <?php foreach ($tickets as $ticket): ?>
            <div class="card">
                <h3><?= htmlspecialchars($ticket['title']) ?></h3>
                <p><strong>Category:</strong> <?= htmlspecialchars($ticket['category_name']) ?></p>
                <p><strong>Status:</strong> 
                    <span class="status <?= statusClass($ticket['status']) ?>">
                        <?= htmlspecialchars($ticket['status']) ?>
                    </span>
                </p>
                <p class="word-wrap"><strong>Description:</strong><br><?= nl2br(htmlspecialchars($ticket['description'])) ?></p>
                <p><small><strong>Created:</strong> <?= $ticket['created_at'] ?></small></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No tickets found.</p>
    <?php endif; ?>
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