<?php
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=zwamlszw_InsertCart', 'zwamlszw_myo', '914161827');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$search = $_GET['search'] ?? '';
$searchParam = '%' . $search . '%';

// Fetch users with total orders and used amount
$stmt = $pdo->prepare("
    SELECT 
        u.*, 
        COUNT(o.id) AS total_orders, 
        IFNULL(SUM(o.amount), 0) AS total_amount
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    WHERE u.username LIKE ? OR u.email LIKE ?
    GROUP BY u.id
    ORDER BY u.id DESC
");
$stmt->execute([$searchParam, $searchParam]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch users registered today
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM users WHERE DATE(created_at) = ? ORDER BY id DESC");
$stmt->execute([$today]);
$newUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Users - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="asha.png">
    <style>
        body {
            background: #111;
            color: #fff;
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        h2 {
            color: #28a745;
            font-size: 30px;
            margin-top: 40px;
            margin-bottom: 20px;
        }

        form {
            margin-bottom: 30px;
        }

        input[type="text"] {
            padding: 14px;
            width: 60%;
            max-width: 400px;
            font-size: 16px;
            border-radius: 8px;
            border: none;
        }

        button {
            width: 25%;
            padding: 14px 20px;
            font-size: 16px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .card-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .user-card {
            background: #1d1d1d;
            border: 1px solid #333;
            border-radius: 16px;
            padding: 24px;
            width: 85%;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.5);
            font-size: 18px;
            transition: transform 0.2s ease;
        }

        .user-card:hover {
            transform: translateY(-2px);
        }

        .user-card h3 {
            color: #28a745;
            margin: 0 0 12px;
            font-size: 26px;
        }

        .user-card p {
            margin: 10px 0;
            font-size: 16px;
            color: #ccc;
        }

        @media screen and (min-width: 700px) {
            .card-container {
                flex-direction: row;
                flex-wrap: wrap;
            }

            .user-card {
                width: calc(50% - 20px);
            }
        }

        @media screen and (min-width: 1024px) {
            .user-card {
                width: calc(33.33% - 20px);
            }
        }
    </style>
</head>
<body>
<a href="admindashboard.php" style="display:inline-block; margin-bottom:20px; background:#007bff; color:#fff; padding:10px 16px; border-radius:8px; text-decoration:none; font-size:16px;">
    ← Back to Admin Dashboard
</a>

<h2>User Management</h2>

<form method="GET">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by username or email...">
    <button type="submit">Search</button>
</form>

<h2>New Users Today (<?= count($newUsers) ?>)</h2>
<div class="card-container">
    <?php foreach ($newUsers as $user): ?>
        <div class="user-card">
            <h3><?= htmlspecialchars($user['username']) ?> (#<?= $user['id'] ?>)</h3>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
            <p><strong>Balance:</strong> <?= htmlspecialchars($user['balance']) ?> MMK</p>
            <p><strong>Registered:</strong> <?= htmlspecialchars($user['created_at']) ?></p>
        </div>
    <?php endforeach; ?>
</div>

<h2>All Users (<?= count($users) ?>)</h2>
<div class="card-container">
    <?php foreach ($users as $user): ?>
        <div class="user-card">
            <h3><?= htmlspecialchars($user['username']) ?> (#<?= $user['id'] ?>)</h3>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
            <p><strong>Balance:</strong> <?= htmlspecialchars($user['balance']) ?> MMK</p>
            <p><strong>Registered:</strong> <?= htmlspecialchars($user['created_at']) ?></p>
            <p><strong>Total Orders:</strong> <?= htmlspecialchars($user['total_orders']) ?></p>
            <p><strong>Total Used:</strong> <?= htmlspecialchars($user['total_amount']) ?> MMK</p>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>