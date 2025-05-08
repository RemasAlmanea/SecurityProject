<?php
session_start();
$message = "";

//  Redirect user if already logged in
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'user') {
    header("Location: home.php");
    exit();
}

//  Secure database connection with error handling
try {
    $db = new PDO("sqlite:database.sqlite");
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Fetch user using a prepared statement to prevent SQL injection
    $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    /*
       Secure password verification:
      We use password_verify() to compare the entered password with the
      hashed password stored in the database. This ensures protection against
      brute-force and timing attacks. It must be used with password_hash().
    */
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = 'user'; // Assign role as user

        header("Location: home.php");
        exit();
    } else {
        $message = "❌ Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Login</title>
    <style>
        body {
            background: linear-gradient(to right, #a1c4fd, #c2e9fb);
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
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
            box-sizing: border-box;
        }
        button {
            background: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background: #45a049;
        }
        .message {
            color: red;
        }
        a {
            display: block;
            margin-top: 10px;
            color: #007BFF;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>User Login</h2>
        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <a href="register.php">Don't have an account?</a>
        <a href="admin_login.php">Admin Login</a>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    </div>
</body>
</html>
