<?php
session_start();
$message = "";

if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}


$admin_username = "admin@example.com";
$admin_password_hash = '$2y$12$9F6x8NRV3m/mQygGtvxB9e12gk6XG/WJbff0uycTa9rkC7Aup1mWG'; // hashed 'admin123'

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($username === $admin_username && password_verify($password, $admin_password_hash)) {
        $_SESSION['user_id'] = 8; // Real admin ID from database
        $_SESSION['username'] = $admin_username;
        $_SESSION['role'] = 'admin';

        header("Location: admin_dashboard.php");
        exit();
    } else {
        $message = "❌ Invalid admin credentials.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <style>
        body {
            background: linear-gradient(to right, #a1c4fd, #c2e9fb);
            font-family: Arial;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 320px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
        }
        input, button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        button {
            background: #4CAF50;
            color: white;
            border: none;
        }
        .message {
            color: red;
        }
        a {
            display: block;
            margin-top: 10px;
            color: #007BFF;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Admin Login</h2>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <a href="login.php">Back to user login</a>
        <div class="message"><?php echo $message; ?></div>
    </div>
</body>
</html>
