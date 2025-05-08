<?php
session_start();

//  Secure: block non-logged-in users
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

//  Secure: database connection
try {
    $db = new PDO("sqlite:database.sqlite");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Process booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = (int) $_POST['room_id'];
    $day = trim($_POST['day']);
    $time = trim($_POST['time']);
    $user_id = $_SESSION['user_id'];

    //  Validate allowed values
    $valid_days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
    $valid_times = ['3:00 PM', '5:00 PM', '7:00 PM'];

    if (!in_array($day, $valid_days) || !in_array($time, $valid_times)) {
        die("❌ Invalid booking time or day.");
    }

    // Check if room exists
    $stmt = $db->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        die("❌ Room not found.");
    }

    //  Insert booking
    $stmt = $db->prepare("INSERT INTO bookings (user_id, room_id, booking_day, booking_time) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $room_id, $day, $time]);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Booking Confirmation</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(to right, #fdfbfb, #ebedee);
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 60px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        h2 {
            margin-bottom: 20px;
        }
        p {
            font-size: 18px;
        }
        .btn {
            margin-top: 30px;
            background: #4CAF50;
            color: white;
            padding: 12px 24px;
            font-size: 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn:hover {
            background: #388e3c;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Booking Confirmed ✅</h2>
    <p><strong>Room:</strong> <?php echo htmlspecialchars($room['name']); ?></p>
    <p><strong>Day:</strong> <?php echo htmlspecialchars($day); ?></p>
    <p><strong>Time:</strong> <?php echo htmlspecialchars($time); ?></p>
    <p><strong>Price:</strong> <?php echo htmlspecialchars($room['price']); ?> SR</p>

    <a class="btn" href="home.php">Back to Home</a>
</div>

</body>
</html>
