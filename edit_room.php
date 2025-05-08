<?php
session_start();

// Only allow admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

$db = new PDO("sqlite:database.sqlite");
$room_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$upload_dir = 'images/';

//  Fetch room info
$stmt = $db->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    $_SESSION['message'] = "Room not found";
    header("Location: admin_dashboard.php");
    exit();
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $difficulty = trim($_POST['difficulty']);
    $price = (float)$_POST['price'];
    $image = $room['image'];

    //  Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_ext, $allowed_ext)) {
            if (!empty($room['image']) && file_exists($upload_dir . $room['image'])) {
                unlink($upload_dir . $room['image']);
            }

            $image_name = uniqid('room_', true) . '.' . $file_ext;
            $target_file = $upload_dir . $image_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image = $image_name;
            } else {
                $message = "Error uploading image.";
            }
        } else {
            $message = "Invalid image format. Only JPG, JPEG, PNG, GIF allowed.";
        }
    }

    if (empty($message)) {
        try {
            $stmt = $db->prepare("UPDATE rooms SET name = ?, description = ?, difficulty = ?, price = ?, image = ? WHERE id = ?");
            $stmt->execute([$name, $description, $difficulty, $price, $image, $room_id]);

            $_SESSION['message'] = "Room updated successfully!";
            header("Location: admin_dashboard.php");
            exit();
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Room</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
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
        .container {
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            padding: 10px 18px;
            border-radius: 5px;
            border: none;
            color: white;
            cursor: pointer;
        }
        .btn-primary {
            background: #4CAF50;
        }
        .btn-secondary {
            background: #6c757d;
            text-decoration: none;
            padding: 10px 18px;
            display: inline-block;
        }
        .message {
            padding: 10px;
            margin-bottom: 15px;
            background: #f2dede;
            color: #a94442;
            border-radius: 4px;
        }
        .current-image {
            margin-top: 10px;
            max-width: 100%;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <h2>Edit Escape Room</h2>
        <a href="admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <div class="section">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Room Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($room['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($room['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="difficulty">Difficulty</label>
                    <select name="difficulty" id="difficulty" required>
                        <option value="Easy" <?php if ($room['difficulty'] == 'Easy') echo 'selected'; ?>>Easy</option>
                        <option value="Medium" <?php if ($room['difficulty'] == 'Medium') echo 'selected'; ?>>Medium</option>
                        <option value="Hard" <?php if ($room['difficulty'] == 'Hard') echo 'selected'; ?>>Hard</option>
                        <option value="Expert" <?php if ($room['difficulty'] == 'Expert') echo 'selected'; ?>>Expert</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="price">Price (SR)</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($room['price']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="image">Upload New Image (optional)</label>
                    <input type="file" name="image" id="image" accept="image/*">
                    <?php if (!empty($room['image'])): ?>
                        <p>Current Image:</p>
                        <img src="images/<?php echo htmlspecialchars($room['image']); ?>" class="current-image" alt="Room Image">
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <button class="btn btn-primary" type="submit">Update Room</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
