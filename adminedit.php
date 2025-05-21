<?php
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=zwamlszw_InsertCart', 'zwamlszw_myo', '914161827');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Get all categories
$categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {
    $stmt = $pdo->prepare("UPDATE services SET category_id=?, name=?, description=?, sell_price=? WHERE service_id=?");
    $stmt->execute([
        $_POST['category_id'],
        $_POST['service_name'],
        $_POST['description'],
        $_POST['sell_price'],
        $_POST['service_id']
    ]);
    $message = "✅ Service updated successfully!";
}

// If service_id is provided, fetch and show only that service
$service = null;
if (isset($_GET['service_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE service_id = ?");
    $stmt->execute([$_GET['service_id']]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    // List all services
    $search = $_GET['search'] ?? '';
    $searchParam = '%' . $search . '%';
    $stmt = $pdo->prepare("SELECT s.*, c.name as category_name FROM services s JOIN categories c ON s.category_id = c.id WHERE s.name LIKE ? OR s.service_id LIKE ? ORDER BY s.service_id");
    $stmt->execute([$searchParam, $searchParam]);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Services - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            background: #111;
            color: #fff;
            font-family: Arial, sans-serif;
            padding: 20px;
            font-size: 18px;
        }
        h2 {
            color: #28a745;
            font-size: 26px;
        }
        .message {
            background: #28a745;
            color: white;
            padding: 14px;
            margin-bottom: 24px;
            border-radius: 8px;
            font-size: 18px;
        }
        form.search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        form.search-form input {
            flex: 1;
            padding: 14px;
            font-size: 18px;
            border-radius: 8px;
            border: none;
        }
        form.search-form button {
            padding: 14px 18px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
        }
        .card {
            background: #1d1d1d;
            border: 1px solid #444;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .card label {
            display: block;
            margin-bottom: 8px;
            color: #ccc;
            font-size: 17px;
        }
        .card input{
            width: 90%;
            padding: 14px;
            margin-bottom: 14px;
            background: #222;
            color: #fff;
            border: 1px solid #444;
            border-radius: 8px;
            font-size: 18px;
        }
        .card textarea {
            width: 90%;
            min-height: 100px;
            padding: 14px;
            background: #222;
            color: #fff;
            border: 1px solid #444;
            border-radius: 8px;
            font-size: 18px;
        }
        .card select{
            width: 100%;
            padding: 14px;
            margin-bottom: 14px;
            background: #222;
            color: #fff;
            border: 1px solid #444;
            border-radius: 8px;
            font-size: 18px;
        }
        .card button{
            background: #28a745;
            color: white;
            border: none;
            padding: 14px 18px;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
            width: 100%;
        }
        .edit-btn {
            border: none;
            width: 95%;
            background: #007bff;
            padding: 10px;
            border-radius: 6px;
            display: inline-block;
            margin-top: 10px;
            text-align: center;
            font-size: 16px;
            color: white;
            text-decoration: none;
        }

        /* Enhanced mobile responsiveness */
        @media screen and (max-width: 600px) {
            .card {
                padding: 16px;
                margin-bottom: 16px;
            }
            h2 {
                font-size: 22px;
            }
            input, select, textarea, button {
                font-size: 16px;
                padding: 12px;
            }
            .edit-btn {
                font-size: 14px;
            }
            .search-form input {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>

<?php if (!empty($message)): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($service): ?>
    <!-- Edit form for single service -->
    <a href="adminedit.php" style="display:inline-block; margin-bottom:20px; background:#007bff; color:#fff; padding:10px 16px; border-radius:8px; text-decoration:none; font-size:16px;">
    ← Back to Service List
    </a>
    <h2>Admin - Edit Services</h2>
    <div class="card">
    
        <form method="POST">
            <label>Service ID</label>
            <input type="number" name="service_id" value="<?= htmlspecialchars($service['service_id']) ?>" required>
            <label>Service Name</label>
            <input type="text" name="service_name" value="<?= htmlspecialchars($service['name']) ?>" required>
            <label>Description</label>
            <textarea name="description" required><?= htmlspecialchars($service['description']) ?></textarea>
            <label>Price (MMK)</label>
            <input type="number" step="0.01" name="sell_price" value="<?= htmlspecialchars($service['sell_price']) ?>" required>
            <label>Category</label>
            <select name="category_id">
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $service['category_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="update_service">Update</button>
        </form>
    </div>
<?php else: ?>
    <!-- Search and list -->
    <a href="admindashboard.php" style="display:inline-block; margin-bottom:20px; background:#007bff; color:#fff; padding:10px 16px; border-radius:8px; text-decoration:none; font-size:16px;">
    ← Back to Admin Dashboard
    </a>
    <h2>Admin - Edit Services</h2>
    <form method="GET" class="search-form">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or ID...">
        <button type="submit">Search</button>
    </form>

    <?php foreach ($services as $srv): ?>
        <div class="card">
            <label>Service ID</label>
            <input type="number" value="<?= htmlspecialchars($srv['service_id']) ?>" readonly>
            <label>Service Name</label>
            <input type="text" value="<?= htmlspecialchars($srv['name']) ?>" readonly>
            <label>Category</label>
            <input type="text" value="<?= htmlspecialchars($srv['category_name']) ?>" readonly>
            <label>Price</label>
            <input type="text" value="<?= htmlspecialchars($srv['sell_price']) ?> MMK" readonly>
            <label>Description</label>
            <textarea name="description" readonly><?= htmlspecialchars($srv['description']) ?></textarea>
            <a href="?service_id=<?= $srv['service_id'] ?>" class="edit-btn">Edit</a>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll("textarea").forEach(textarea => {
        const resize = () => {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + "px";
        };
        textarea.addEventListener("input", resize);
        resize(); // run once on load
    });
});
</script>
</body>
</html>