    <?php

session_start();

$host = getenv("DB_HOST");
$db   = getenv("DB_NAME");
$user = getenv("DB_USER");
$pass = getenv("DB_PASSWORD");

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Store session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role']; // Storing role

        // Redirect based on role
        if ($user['role'] === 'admin') {
            header('Location: admindashboard.php');
        } else {
            header('Location: dashboard.php');
        }
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Asha Company Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="asha.png">
    <style>
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Arial', sans-serif; }
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
    .bottom-text, .forgot-password {
        text-align: center;
        font-size: 14px;
        margin-top: 15px;
        color: #ccc;
    }
    .bottom-text a, .forgot-password a {
        color: #aaaaff;
        text-decoration: none;
    }
    .bottom-text a:hover, .forgot-password a:hover {
        text-decoration: underline;
    }
    .error {
        color: #ff4d4d;
        text-align: center;
        margin-bottom: 15px;
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
            <h2>Welcome Back!</h2>
            <?php if (!empty($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <div class="forgot-password">
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>
                <br>
                <button type="submit">Login</button>
            </form>
            <div class="bottom-text">
                Don’t have an account? <a href="register.php">Sign Up</a>
            </div>
        </div>
    </div>
</body>
</html>
