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
$api_key = '0579b56348043d4ee1d70f92bb5601ed';
$orderResponse = null;

// Fetch categories and services
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$services = $pdo->query("SELECT service_id, category_id, name, sell_price, description FROM services")->fetchAll(PDO::FETCH_ASSOC);

// Fetch user info
$stmt = $pdo->prepare("SELECT username, balance, name, discount, level_id, total_spent FROM users u LEFT JOIN level i ON i.id = u.level_id WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $service_id = $_POST['service'];
    $link = $_POST['link'];
    $quantity = (int)$_POST['quantity'];

    if (!$user) {
        $orderResponse = ['error' => 'User not found'];
    } else {
        $stmt = $pdo->prepare("SELECT sell_price FROM services WHERE service_id = ?");
        $stmt->execute([$service_id]);
        $service = $stmt->fetch();

        if (!$service) {
            $orderResponse = ['error' => 'Service not found'];
        } else {
            $base_price = $service['sell_price'] * $quantity / 1000;
            $discount_percentage = isset($user['discount']) ? $user['discount'] : 0;
            $discounted_price = $base_price * ((100 - $discount_percentage) / 100);
            $total_price = round($discounted_price, 2);

            if ($user['balance'] < $total_price) {
                $orderResponse = ['error' => 'Insufficient balance'];
            } else {
                $url = 'https://socialfastmm.com/api/v2';
                $data = [
                    'key' => $api_key,
                    'action' => 'add',
                    'service' => $service_id,
                    'link' => $link,
                    'quantity' => $quantity
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                $response = curl_exec($ch);

                if (curl_errno($ch)) {
                    $orderResponse = ['error' => 'cURL Error: ' . curl_error($ch)];
                } else {
                    $orderResult = json_decode($response, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $orderResponse = ['error' => 'Invalid JSON: ' . $response];
                    } elseif (isset($orderResult['order'])) {
                        try {
                            $pdo->beginTransaction();

                            $stmt = $pdo->prepare("INSERT INTO orders (user_id, service_id, link, quantity, amount, order_id) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$user_id, $service_id, $link, $quantity, $total_price, $orderResult['order']]);

                            $stmt = $pdo->prepare("UPDATE users SET balance = balance - ?, total_spent = total_spent + ? WHERE id = ?");
                            $stmt->execute([$total_price, $total_price, $user_id]);

                            // Get updated total_spent
                            $stmt = $pdo->prepare("SELECT total_spent FROM users WHERE id = ?");
                            $stmt->execute([$user_id]);
                            $newSpent = $stmt->fetchColumn();

                            // Check level upgrade
                            $stmt = $pdo->prepare("SELECT id FROM level WHERE required_amount <= ? ORDER BY required_amount DESC LIMIT 1");
                            $stmt->execute([$newSpent]);
                            $newLevel = $stmt->fetchColumn();

                            if ($newLevel && $newLevel != $user['level_id']) {
                                $stmt = $pdo->prepare("UPDATE users SET level_id = ? WHERE id = ?");
                                $stmt->execute([$newLevel, $user_id]);
                                $orderResponse['level_up'] = "🎉 Level up!";
                            }

                            $pdo->commit();
                            $user['balance'] -= $total_price;
                            $orderResponse['order'] = $orderResult['order'];
                        } catch (PDOException $e) {
                            $pdo->rollBack();
                            $orderResponse = ['error' => 'Database Error: ' . $e->getMessage()];
                        }
                    } else {
                        $orderResponse = ['error' => 'API Error: ' . $response];
                    }
                }
                curl_close($ch);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Place Order</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="asha.png">
    <style>
    body {
        font-family: Arial, sans-serif;
        background: #111;
        color: white;
        margin: 0;
        padding: 20px;
    }

    .container {
        max-width: 400px;
        margin: auto;
    }

    .nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-left: 10px;
        margin-right: 30px;
    }

    h2 {
        text-align: center;
        color: #4ea4ff;
    }

    label {
        display: block;
        margin-top: 15px;
        font-weight: bold;
    }

    select,
    input[type="text"],
    input[type="number"] {
        width: 90%;
        padding: 10px;
        margin-top: 5px;
        border-radius: 10px;
        border: none;
        font-size: 16px;
    }

    button {
        width: 95%;
        background: #4ea4ff;
        border: none;
        padding: 15px;
        border-radius: 15px;
        margin-top: 25px;
        font-size: 18px;
        font-weight: bold;
        color: white;
        cursor: pointer;
    }

    .response {
        margin-top: 20px;
        padding: 15px;
        border-radius: 10px;
    }

    .response.error {
        background: #f8d7da;
        color: #721c24;
    }

    .response.success {
        background: #d4edda;
        color: #155724;
    }

    .header {
        margin-bottom: 5px;
        display: flex;
        align-items: center;
        justify-content: space-round;
    }

    .header img {
        width: 140px;
        margin-bottom: 10px;
    }

    .logo {
        font-size: 24px;
        font-weight: bold;
        color: #3a8eff;
    }

    .user-info {
        text-align: center;
        margin-bottom: 20px;
    }

    .user-info h3 {
        margin: 0;
        font-size: 18px;
    }

    .user-info span {
        display: block;
        color: #aaa;
        font-size: 14px;
    }

    .menu {
        position: relative;
        cursor: pointer;
    }

    textarea {
        width: 90%;
        min-height: 70px;
        max-height: 500px;
        overflow: hidden;
        resize: none;
        padding: 10px;
        margin-top: 5px;
        border-radius: 10px;
        border: none;
        font-size: 16px;
        white-space: pre-wrap;
        word-wrap: break-word;
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
    .dropdown-btn {
        width: 90%;
        padding: 10px;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        text-align: left;
        background-color: #222;
        color: #fff;
        position: relative;
        margin-top: 5px;
        cursor: pointer;
    }

    .dropdown-list {
        display: none;
        background-color: #333;
        border-radius: 10px;
        position: absolute;
        width: 90%;
        z-index: 100;
        max-height: 200px;
        overflow-y: auto;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.5);
    }

    .dropdown-list div {
        padding: 10px;
        cursor: pointer;
    }

    .dropdown-list div:hover {
        background-color: #000;
    }

    .dropdown-wrapper {
        position: relative;
        margin-bottom: 15px;
    }
    </style>

</head>

<body>
    <div class="container">
        <div class="header">
            <img src="asha.png" alt="Asha Logo">
            <div class="logo">Asha Company</div>
        </div>

        <div class="nav">
            <div class="user">
                <h3><?= htmlspecialchars($user['username']) ?></h3>
                <p>Balance: <?= number_format($user['balance'], 0) ?> MMK</p>
            </div>
            <div class="menu" onclick="toggleMenu()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="#fff" class="menu-icon" viewBox="0 0 16 16">
                    <path fill-rule="evenodd"
                        d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5" />
                </svg>

                <div class="dropdown" id="menuDropdown">
                    <a href="dashboard.php">👤 Dashboard</a>
                    <a href="services.php">🛒 All Services</a>
                    <a href="history.php">🧾 History</a>
                    <a href="funds.php">💶 Add Funds</a>
                    <a href="fundhistories.php">🧾 Funds History</a>
                    <a href="tickets.php">🎫 My Tickets</a>
                    <a href="logout.php">🚪 Logout</a>
                </div>
            </div>
        </div>

        <?php if ($orderResponse): ?>
        <div class="response <?= isset($orderResponse['error']) ? 'error' : 'success' ?>">
            <?php if (isset($orderResponse['error'])): ?>
            ❌ <?= htmlspecialchars($orderResponse['error']) ?>
            <?php else: ?>
            ✅ Order Placed Successfully! ID: <?= htmlspecialchars($orderResponse['order']) ?>
            <?php if (isset($orderResponse['level_up'])): ?>
            🎉 <?= htmlspecialchars($orderResponse['level_up']) ?>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <form method="POST" oninput="updateCharge()" id="orderForm">
            <label>Category</label>
            <div class="dropdown-wrapper">
                <button type="button" id="categoryBtn" class="dropdown-btn">-- Select Category --</button>
                <div class="dropdown-list" id="categoryList">
                    <?php foreach ($categories as $cat): ?>
                    <div onclick="selectCategory('<?= $cat['id'] ?>', '<?= htmlspecialchars($cat['name']) ?>')">
                        <?= htmlspecialchars($cat['name']) ?>
                    </div>
<hr>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="category" />
            </div>

            <label>Service</label>
            <div class="dropdown-wrapper">
                <button type="button" id="serviceBtn" class="dropdown-btn">-- Select Service --</button>
                <div class="dropdown-list" id="serviceList">
                    <?php foreach ($services as $srv): ?>
                    <div onclick="selectService(this)" data-id="<?= $srv['service_id'] ?>"
                        data-category="<?= $srv['category_id'] ?>" data-price="<?= $srv['sell_price'] ?>"
                        data-description="<?= htmlspecialchars($srv['description']) ?>">ID : <?= $srv['service_id'] ?><br>
                        <?= htmlspecialchars($srv['name']) ?>(<?= $srv['sell_price'] ?> MMK / 1k)
                    </div>
                    
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="service" id="service">
            </div>

            <label>Description</label>
            <textarea id="description" readonly></textarea>

            <label>Link</label>
            <input type="text" name="link" required>

            <label>Quantity</label>
            <input type="number" name="quantity" id="quantity" min="100" step="100" required>

            <label>Charge (after discount)</label>
            <input type="text" id="charge" readonly>

            <button type="button" onclick="showConfirm()">Order</button>
        </form>

        <div id="confirmModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; align-items:center; justify-content:center;">
            <div style="background:#222; padding:30px; border-radius:15px; max-width:300px; text-align:center; color:white;">
                <p>Are you sure you want to place this order?</p>
                <button onclick="document.getElementById('orderForm').submit()" style="background:#4ea4ff; color:white; border:none; padding:10px 20px; border-radius:10px; margin-right:10px;">Yes</button>
                <button onclick="hideConfirm()" style="background:#555; color:white; border:none; padding:10px 20px; border-radius:10px;">Cancel</button>
            </div>
        </div>
    </div>

    <script>
    const userDiscount = <?= isset($user['discount']) ? (float)$user['discount'] : 0 ?>;

    function toggleDropdown(id) {
        document.getElementById(id).style.display =
            document.getElementById(id).style.display === 'block' ? 'none' : 'block';
    }

    document.getElementById("categoryBtn").addEventListener("click", () => toggleDropdown("categoryList"));
    document.getElementById("serviceBtn").addEventListener("click", () => toggleDropdown("serviceList"));

    function selectCategory(id, name) {
        document.getElementById("category").value = id;
        document.getElementById("categoryBtn").innerText = name;
        document.getElementById("categoryList").style.display = "none";

        // Filter services
        const serviceOptions = document.querySelectorAll('#serviceList div');
        serviceOptions.forEach(opt => {
            opt.style.display = opt.dataset.category === id ? 'block' : 'none';
        });

        // Reset service
        document.getElementById("service").value = "";
        document.getElementById("serviceBtn").innerText = "-- Select Service --";
        document.getElementById("description").value = "";
        document.getElementById("charge").value = "";
    }

    function selectService(elem) {
        const id = elem.dataset.id;
        const name = elem.innerText;
        const desc = elem.dataset.description;
        const price = elem.dataset.price;

        document.getElementById("service").value = id;
        document.getElementById("serviceBtn").innerText = name;
        document.getElementById("serviceList").style.display = "none";

        const descBox = document.getElementById("description");
        descBox.value = desc || "";
        descBox.style.height = "auto";
        descBox.style.height = descBox.scrollHeight + "px";

        updateCharge();
    }

    function updateCharge() {
        let serviceElem = document.querySelector(`#serviceList div[data-id="${document.getElementById("service").value}"]`);
        let quantity = document.getElementById("quantity").value;
        if (serviceElem && quantity) {
            let baseCharge = serviceElem.dataset.price * quantity / 1000;
            let finalCharge = baseCharge * (1 - userDiscount / 100);
            document.getElementById("charge").value = finalCharge.toFixed(2);
        }
    }

    function showConfirm() {
        document.getElementById('confirmModal').style.display = 'flex';
    }

    function hideConfirm() {
        document.getElementById('confirmModal').style.display = 'none';
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown-wrapper')) {
            document.getElementById('categoryList').style.display = 'none';
            document.getElementById('serviceList').style.display = 'none';
        }
    });

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