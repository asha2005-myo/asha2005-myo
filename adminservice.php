<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=zwamlszw_InsertCart', 'zwamlszw_myo', '914161827');


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['fcm_token'])) {
    $userId = $_POST['user_id']; // Get the user ID
    $fcmToken = $_POST['fcm_token']; // Get the FCM token

    // Insert or update the FCM token for the user
    $stmt = $pdo->prepare("INSERT INTO users (id, fcm_token) VALUES (?, ?) ON DUPLICATE KEY UPDATE fcm_token = ?");
    $stmt->execute([$userId, $fcmToken, $fcmToken]);

    // Send response
    echo json_encode(["status" => "success", "message" => "FCM token saved"]);
}

// Handle adding category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_category'])) {
    $catName = trim($_POST['new_category']);
    if ($catName) {
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$catName]);
        $categoryMessage = "Category added successfully!";
    }
}

// Handle adding service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service_name'], $_POST['category_id'])) {
    $stmt = $pdo->prepare("INSERT INTO services (service_id, category_id, name, description, sell_price) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['service_id'],
        $_POST['category_id'],
        $_POST['service_name'],
        $_POST['description'],
        $_POST['sell_price']
    ]);

// Send to Telegram Channel
$botToken = '7140260134:AAGv48GxZzpTKXegsC-CM_oFG1fe7Mhds4g';
$chatId = '@Asha_service_broadcast'; // For public channels

// Get category name
$catStmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
$catStmt->execute([$_POST['category_id']]);
$categoryName = $catStmt->fetchColumn();

$message = "📢 *New Service Added!*\n\n"
    . "🆔 *Service ID:* " . $_POST['service_id'] . "\n"
    . "📂 *Category:* " . $categoryName . "\n"
    . "📄 *Name:* " . $_POST['service_name'] . "\n"
    . "💬 *Description:*\n " . $_POST['description'] . "\n"
    . "💰 *Price:* " . $_POST['sell_price'] . " MMK\n"
    . "*Website*\n\nhttps://socialboost.z246014-0w10am.ls02.zwhhosting.com/login.php";

$url = "https://api.telegram.org/bot$botToken/sendMessage";
$params = [
    'chat_id' => $chatId,
    'text' => $message,
    'parse_mode' => 'Markdown'
];

file_get_contents($url . '?' . http_build_query($params));

    $serviceMessage = "Service added successfully!";
}

// Fetch categories for dropdown
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Add Service</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="asha.png">
  <style>
    body {
        font-family: Arial, sans-serif;
        background: #111;
        color: #fff;
        padding: 20px;
        margin: 0;
    }
    h2 {
        margin-top: 30px;
        font-size: 1.4em;
    }
    form {
        margin-top: 10px;
        background: #1d1d1d;
        padding: 15px;
        border-radius: 8px;
        max-width: 500px;
        margin-bottom: 20px;
    }
    input, textarea, button {
        width: 90%;
        padding: 10px;
        margin: 8px 0;
        border: none;
        border-radius: 4px;
        font-size: 1em;
    }
    button{
        width: 95%;
        padding: 10px;
        margin: 8px 0;
        border: none;
        border-radius: 4px;
        font-size: 1em;
    }
    textarea {
        resize: vertical;
    }
    button {
        background-color: #28a745;
        color: white;
        cursor: pointer;
    }

    /* Custom Dropdown */
    .dropdown {
        position: relative;
        width: 90%;
        margin-bottom: 12px;
    }
    .dropdown-btn {
        width: 100%;
        background: #333;
        color: #fff;
        padding: 10px;
        border-radius: 4px;
        cursor: pointer;
    }
    .dropdown-content {
        position: absolute;
        background: #222;
        width: 100%;
        max-height: 200px;
        overflow-y: auto;
        border-radius: 4px;
        display: none;
        z-index: 99;
    }
    .dropdown-content div {
        padding: 10px;
        cursor: pointer;
        border-bottom: 1px solid #444;
    }
    .dropdown-content div:hover {
        background: #444;
    }

    @media screen and (max-width: 600px) {
        form {
            padding: 10px;
        }
    }
  </style>
</head>
<body>
<a href="admindashboard.php" style="display:inline-block; margin-bottom:20px; background:#007bff; color:#fff; padding:10px 16px; border-radius:8px; text-decoration:none; font-size:16px;">
    ← Back to Admin Dashboard
    </a>
<?php if (isset($categoryMessage)): ?>
    <div style="background: #28a745; color: white; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
        <?= htmlspecialchars($categoryMessage) ?>
    </div>
<?php endif; ?>

<?php if (isset($serviceMessage)): ?>
    <div style="background: #28a745; color: white; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
        <?= htmlspecialchars($serviceMessage) ?>
    </div>
<?php endif; ?>

<h2>Add Category</h2>
<form method="POST">
    <input type="text" name="new_category" placeholder="Category Name" required>
    <button type="submit">Add Category</button>
</form>

<h2>Add Service</h2>
<form method="POST" onsubmit="return setCategoryId();">
    <div class="dropdown">
        <div class="dropdown-btn" onclick="toggleDropdown()">Select Category</div>
        <div class="dropdown-content" id="categoryList">
            <?php foreach ($categories as $cat): ?>
                <div data-id="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
    <input type="hidden" name="category_id" id="selectedCategoryId" required>
    <input type="number" name="service_id" placeholder="Service ID(from API)" required>
    <input type="text" name="service_name" placeholder="Service Name" required>
    <textarea name="description" placeholder="Service Description" required></textarea>
    <input type="number" step="0.01" name="sell_price" placeholder="Sell Price (MMK)" required>
    <button type="submit">Add Service</button>
</form>

<script>
function toggleDropdown() {
    document.getElementById('categoryList').style.display =
        document.getElementById('categoryList').style.display === 'block' ? 'none' : 'block';
}

document.querySelectorAll('#categoryList div').forEach(item => {
    item.addEventListener('click', () => {
        document.querySelector('.dropdown-btn').textContent = item.textContent;
        document.getElementById('selectedCategoryId').value = item.dataset.id;
        document.getElementById('categoryList').style.display = 'none';
    });
});

function setCategoryId() {
    const id = document.getElementById('selectedCategoryId').value;
    if (!id) {
        alert("Please select a category.");
        return false;
    }
    return true;
}
</script>

</body>
</html>