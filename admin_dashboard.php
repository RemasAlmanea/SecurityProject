
<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

// Database connection
try {
    $db = new PDO("sqlite:database.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle room deletion
if (isset($_GET['delete_room'])) {
    $room_id = (int)$_GET['delete_room'];
    
    try {
        // Check if room has bookings
        $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ?");
        $stmt->execute([$room_id]);
        $booking_count = $stmt->fetchColumn();
        
        if ($booking_count > 0) {
            $_SESSION['message'] = "Cannot delete room - it has active bookings!";
        } else {
            // Get image filename
            $stmt = $db->prepare("SELECT image FROM rooms WHERE id = ?");
            $stmt->execute([$room_id]);
            $image = $stmt->fetchColumn();
            
            // Delete image file if exists
            if ($image && file_exists("uploads/" . $image)) {
                unlink("uploads/" . $image);
            }
            
            // Delete the room
            $stmt = $db->prepare("DELETE FROM rooms WHERE id = ?");
            $stmt->execute([$room_id]);
            $_SESSION['message'] = "Room deleted successfully";
        }
        
        header("Location: admin_dashboard.php");
        exit();
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}

// Fetch all bookings
$bookings = [];
try {
    $stmt = $db->query("SELECT b.id, u.username, u.email, r.name, r.price, b.booking_day, b.booking_time 
                       FROM bookings b
                       JOIN users u ON b.user_id = u.id
                       JOIN rooms r ON b.room_id = r.id
                       ORDER BY b.id DESC");
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching bookings: " . $e->getMessage();
}

// Fetch all rooms
$rooms = [];
try {
    $stmt = $db->query("SELECT * FROM rooms ORDER BY id DESC");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching rooms: " . $e->getMessage();
}

// Fetch customer questions
$questions = [];
try {
    $stmt = $db->query("SELECT q.id, q.content, q.created_at, u.username 
                        FROM questions q
                        JOIN users u ON q.user_id = u.id
                        ORDER BY q.created_at DESC");
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching questions: " . $e->getMessage();
}

// Display messages
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background: linear-gradient(to right, #8EC5FC, #E0C3FC);
        }
        .topbar {
            background-color: #ffffffcc;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .topbar h2 {
            margin: 0;
            color: #333;
        }
        .topbar .logout {
            background: #f44336;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
        }
        .profile {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }
        .container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        h1 {
            color: #666;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #d2a9d9;
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .btn {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            font-size: 14px;
            margin: 0 2px;
        }
        .btn-primary {
            background: #4CAF50;
        }
        .btn-danger {
            background: #f44336;
        }
        .btn-edit {
            background: #2196F3;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            background: #dff0d8;
            color: #3c763d;
        }
        .error {
            background: #f2dede;
            color: #a94442;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <h2>Admin Dashboard</h2>
        <div>
            <form method="GET" action="profile.php" style="display: inline;">
                <button class="profile" type="submit">Profile</button>
            </form>
            <form method="POST" action="admin_logout.php" style="display: inline;">
                <button class="logout" type="submit">Logout</button>
            </form>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Rooms Management Section -->
        <div class="section">
            <h1>Manage Escape Rooms</h1>
            <a href="add_room.php" class="btn btn-primary">Add New Room</a>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Difficulty</th>
                        <th>Price (SR)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($room['id']); ?></td>
                        <td><?php echo htmlspecialchars($room['name']); ?></td>
                        <td><?php echo htmlspecialchars($room['difficulty']); ?></td>
                        <td><?php echo number_format($room['price'], 2); ?></td>
                        <td>
                            <a href="edit_room.php?id=<?php echo $room['id']; ?>" class="btn btn-edit">Edit</a>
                            <a href="?delete_room=<?php echo $room['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rooms)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No rooms found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Bookings Section -->
        <div class="section">
            <h1>All Bookings</h1>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Room</th>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Price (SR)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($booking['id']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($booking['username']); ?><br>
                            <small><?php echo htmlspecialchars($booking['email']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($booking['name']); ?></td>
                        <td><?php echo htmlspecialchars($booking['booking_day']); ?></td>
                        <td><?php echo htmlspecialchars($booking['booking_time']); ?></td>
                        <td><?php echo number_format($booking['price'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No bookings found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Customer Questions Section -->
        <div class="section">
            <h1>Customer Questions</h1>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Question</th>
                        <th>Date Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($questions as $q): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($q['id']); ?></td>
                        <td><?php echo htmlspecialchars($q['username']); ?></td>
                        <td><?php echo htmlspecialchars($q['content']); ?></td>
                        <td><?php echo htmlspecialchars($q['created_at']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($questions)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">No questions submitted yet.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>