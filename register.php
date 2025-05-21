<?php
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=zwamlszw_InsertCart', 'zwamlszw_myo', '914161827');
$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $errors = [];

    // Validate empty fields
    if (empty($username) || empty($email) || empty($password)) {
        $errors[] = "All fields are required.";
    }

    // Email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }

    // Username length
    if (strlen($username) < 3 || strlen($username) > 20) {
        $errors[] = "Username must be between 3 and 20 characters.";
    }

    // Password strength
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    // If validation errors
    if (!empty($errors)) {
        $message = "⚠️ " . implode("<br>⚠️ ", $errors);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $existingUser = $stmt->fetch();

        if ($existingUser) {
            $message = "⚠️ Username already taken.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, balance, level_id, total_spent, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$username, $hashedPassword, $email, 200, 1, 0 , 'user'])) {
                $message = "✅ Registered successfully! <a href='login.php'>Login here</a>.";
            } else {
                $message = "❌ Registration failed. Try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Asha Company Register</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="asha.png">
    <style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Arial', sans-serif;
    }
    body {
        background: #0f0f0f;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        color: #fff;
    }
    .container {
        width: 100%;
        max-width: 400px;
        padding: 20px;
    }
    .logo {
        text-align: center;
        margin-bottom: 30px;
    }
    .logo img {
        width: 200px;
        margin-bottom: 10px;
    }
    .logo h1 {
        color: #5a57ff;
        font-size: 22px;
    }
    .card {
        background: #121212;
        border-radius: 16px;
        padding: 30px;
    }
    h2 {
        text-align: center;
        font-size: 20px;
        margin-bottom: 25px;
    }
    input {
        width: 100%;
        padding: 14px;
        background-color: #d3d3d3;
        border: none;
        border-radius: 24px;
        margin-bottom: 20px;
        font-size: 16px;
    }
    button {
        width: 100%;
        padding: 14px;
        background: #5a57ff;
        color: white;
        border: none;
        border-radius: 24px;
        font-size: 18px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.3s ease-in-out;
    }
    button:hover {
        background: #3c3acf;
    }
    .bottom-text {
        text-align: center;
        font-size: 14px;
        margin-top: 15px;
        color: #ccc;
    }
    .bottom-text a {
        color: #aaaaff;
        text-decoration: none;
    }
    .bottom-text a:hover {
        text-decoration: underline;
    }
    .message {
        color: #ff4d4d;
        text-align: center;
        margin-bottom: 15px;
    }
    .message a {
        color: #28a745;
        text-decoration: underline;
    }
    </style>
</head>
<body>
<div class="container">
    <div class="logo">
        <img src="asha.png" alt="Asha Logo">
        <h1>Asha Company</h1>
    </div>
    <div class="card">
        <h2>Create An Account</h2>
        <?php if (!empty($message)): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" id="password" placeholder="Password" required>
            <input type="password" id="confirm_password" placeholder="Confirm Password" required>
            <button type="submit">Sign Up</button>
        </form>
        <div class="bottom-text">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </div>
</div>

<script>
// Client-side password confirmation
document.querySelector("form").addEventListener("submit", function(e) {
    const password = document.getElementById("password").value;
    const confirm = document.getElementById("confirm_password").value;
    if (password !== confirm) {
        e.preventDefault();
        alert("Passwords do not match.");
    }
});
</script>
</body>
</html>