<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$message = "";

//  Connect to SQLite database
try {
    $db = new PDO("sqlite:database.sqlite");
} catch (PDOException $e) {
    die("Database connection failed.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = htmlspecialchars(trim($_POST['username']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = "❌ Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $message = "❌ Passwords do not match.";
    } else {
        try {
            //  Check if email already exists
            $checkStmt = $db->prepare("SELECT * FROM users WHERE email = :email");
            $checkStmt->execute(['email' => $email]);
            $existingUser = $checkStmt->fetch();

            if ($existingUser) {
                $message = "❌ Email already registered.";
            } else {
                //  Simulated weak password storage (MD5 - insecure)
                $weakPassword = md5($password);

                //  Secure password hash (bcrypt)              
  /*
  We use PHP's password_hash() with the default bcrypt algorithm to securely store user passwords. 
  Bcrypt is a one-way hashing algorithm that automatically generates a unique salt for each password, 
  making it resistant and also includes a cost factor, which can be adjusted 
  to slow down brute-force attempts. We verify passwords using password_verify(), which safely compares 
  the entered password with the stored hash. This approach follows modern best practices for password security.
*/
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // AES encryption of original password
                $key = '12345678901234567890123456789012'; // exactly 32 chars
                $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
                $encrypted = openssl_encrypt($password, 'aes-256-cbc', $key, 0, $iv);
                $encryptedPassword = base64_encode($iv . $encrypted); // save IV + ciphertext

                //  Insert securely into DB
                $stmt = $db->prepare("INSERT INTO users (username, email, password, encrypted_password, role)
                                      VALUES (:username, :email, :password, :encrypted_password, :role)");
                $stmt->execute([
                    'username' => $username,
                    'email' => $email,
                    'password' => $hashedPassword,
                    'encrypted_password' => $encryptedPassword,
                    'role' => 'user'
                ]);

                header("Location: login.php");
                exit();
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();

        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <style>
        body {
            background: linear-gradient(to right, #ffecd2, #fcb69f);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .register-box {
            background: white;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            width: 350px;
            text-align: center;
        }
        .register-box h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .register-box input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        .register-box button {
            width: 100%;
            padding: 10px;
            background: #FF5722;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }
        .register-box button:hover {
            background: #e64a19;
        }
        .register-box a {
            display: block;
            margin-top: 15px;
            color: #007BFF;
            text-decoration: none;
        }
        .message {
            color: red;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="register-box">
        <h2>Register</h2>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            <button type="submit">Register</button>
        </form>
        <a href="login.php">Already have an account? Login here</a>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    </div>
</body>
</html>
