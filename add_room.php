<?php
session_start();

// Role-based access control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

$db = new PDO("sqlite:database.sqlite");
$message = '';
$upload_dir = 'uploads/';

//  Create uploads folder if needed
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //  Sanitize inputs to prevent XSS
    $name = htmlspecialchars(trim($_POST['name']));
    $description = htmlspecialchars(trim($_POST['description']));
    $difficulty = htmlspecialchars(trim($_POST['difficulty']));
    $price = (float)$_POST['price'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Check file size
        if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
            $message = "❌ Image file is too large. Max size is 2MB.";
        } else {
            $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($file_ext, $allowed_ext)) {
                $image_name = uniqid('room_', true) . '.' . $file_ext;
                $target_file = $upload_dir . $image_name;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    try {
                        $stmt = $db->prepare("INSERT INTO rooms (name, description, difficulty, price, image) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$name, $description, $difficulty, $price, $image_name]);

                        $_SESSION['message'] = "✅ Room added successfully!";
                        header("Location: admin_dashboard.php");
                        exit();
                    } catch (PDOException $e) {
                        $message = "❌ Error adding room.";
                        if (file_exists($target_file)) {
                            unlink($target_file);
                        }
                    }
                } else {
                    $message = "❌ Error uploading image.";
                }
            } else {
                $message = "❌ Invalid image format. Only JPG, JPEG, PNG, and GIF allowed.";
            }
        }
    } else {
        $message = "❌ Image upload is required.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add New Room</title>
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
            font-weight: bold;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background: #4CAF50;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            background: #f2dede;
            color: #a94442;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <h2>Add New Escape Room</h2>
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
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label for="difficulty">Difficulty</label>
                    <select id="difficulty" name="difficulty" required>
                        <option value="Easy">Easy</option>
                        <option value="Medium">Medium</option>
                        <option value="Hard">Hard</option>
                        <option value="Expert">Expert</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="price">Price (SR)</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="image">Room Image</label>
                    <input type="file" id="image" name="image" accept="image/*" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Add Room</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
