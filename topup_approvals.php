<?php
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=zwamlszw_InsertCart', 'zwamlszw_myo', '914161827');

// Check admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $id = $_POST['id'];
    $action = $_POST['action'];

    $stmt = $pdo->prepare("SELECT user_id, amount FROM topup_requests WHERE id = ? AND status = 'pending'");
    $stmt->execute([$id]);
    $request = $stmt->fetch();

    if ($request) {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")
                ->execute([$request['amount'], $request['user_id']]);
            $pdo->prepare("UPDATE topup_requests SET status = 'approved' WHERE id = ?")
                ->execute([$id]);
        } elseif ($action === 'reject') {
            $pdo->prepare("UPDATE topup_requests SET status = 'rejected' WHERE id = ?")
                ->execute([$id]);
        }
    }
}

$requests = $pdo->query("
    SELECT tr.id, u.username, tr.amount, tr.transaction_id, p.name AS method
    FROM topup_requests tr
    JOIN users u ON tr.user_id = u.id
    JOIN payment p ON tr.paid = p.id
    WHERE tr.status = 'pending'
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Top-Up Approvals - Asha Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="asha.png">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #111;
            color: #fff;
            margin: 0;
            padding: 0;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-right: 20px;
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
        width: 200px;
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

        h2 {
            text-align: center;
            padding: 20px 10px 10px;
        }

        table {
            width: 100%;
            background: #222;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        th, td {
            padding: 12px;
            border: 1px solid #444;
            text-align: center;
        }

        th {
            background: #333;
        }

        form {
            display: inline-block;
            margin: 0 2px;
        }

        button {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
        }

        .approve {
            background: #28a745;
            color: white;
        }

        .reject {
            background: #dc3545;
            color: white;
        }

        @media screen and (max-width: 768px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }

            thead tr {
                display: none;
            }

            tr {
                margin-bottom: 15px;
                background: #1a1a1a;
                padding: 10px;
                border-radius: 8px;
            }

            td {
                text-align: right;
                position: relative;
                padding-left: 50%;
            }

            td::before {
                content: attr(data-label);
                position: absolute;
                left: 15px;
                top: 12px;
                font-weight: bold;
                color: #aaa;
                text-align: left;
            }

            button {
                margin-top: 8px;
                width: 48%;
                font-size: 0.85em;
            }

            form {
                display: block;
                text-align: center;
            }
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
                    d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5"/>
            </svg>
            <div class="dropdown" id="menuDropdown">
                <a href="admindashboard.php">🏠 Admin Dashboard</a>
                <a href="dashboard.php">🏠 Dashboard</a>
                <a href="history.php">🧾 History</a>
                <a href="logout.php">🚪 Logout</a>
            </div>
        </div>
    </div>

    <h2>Pending Top-Up Requests</h2>
    <table>
        <thead>
            <tr>
                <th>User</th>
                <th>Amount (MMK)</th>
                <th>Transaction ID</th>
                <th>Method</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($requests as $req): ?>
            <tr>
                <td data-label="User"><?= htmlspecialchars($req['username']) ?></td>
                <td data-label="Amount"><?= number_format($req['amount']) ?></td>
                <td data-label="Transaction ID"><?= htmlspecialchars($req['transaction_id']) ?></td>
                <td data-label="Method"><?= htmlspecialchars($req['method']) ?></td>
                <td data-label="Action">
                    <form method="POST">
                        <input type="hidden" name="id" value="<?= $req['id'] ?>">
                        <button type="submit" name="action" value="approve" class="approve">Approve</button>
                        <button type="submit" name="action" value="reject" class="reject">Reject</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

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