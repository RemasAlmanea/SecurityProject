<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

try {
    $db = new PDO("sqlite:database.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

try {
    $stmt = $db->prepare("SELECT username, email FROM users WHERE id = ? AND role = ?");
    $stmt->execute([$user_id, $role]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("User not found.");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #eef2f3;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .profile-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            width: 300px;
            text-align: center;
        }
        h2 {
            margin-bottom: 10px;
        }
        .info {
            margin-bottom: 15px;
            font-size: 16px;
            text-align: left;
        }
        .info label {
            font-weight: bold;
        }
        .back-btn, .logout-btn {
            display: inline-block;
            margin: 5px;
            padding: 8px 16px;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
            border: none;
        }
        .back-btn {
            background-color: #4CAF50;
        }
        .logout-btn {
            background-color: #f44336;
        }
    </style>
</head>
<body>

<div class="profile-box">
    <h2>Welcome, <?php echo htmlspecialchars($user['username']); ?></h2>
    
    <div class="info">
        <label>Email:</label><br>
        <?php echo htmlspecialchars($user['email']); ?>
    </div>

    <div class="info">
        <label>Password:</label><br>
        ••••••••• (hidden for security)
    </div>

    <a href="<?php echo ($role === 'admin') ? 'admin_dashboard.php' : 'home.php'; ?>" class="back-btn">Back</a>
    <form method="POST" action="logout.php" style="display:inline;">
        <button type="submit" class="logout-btn">Logout</button>
    </form>
</div>

</body>
</html>
