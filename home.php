<?php
session_start();

//  Block unauthenticated access
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

//  Connect to database
try {
    $db = new PDO("sqlite:database.sqlite");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$username = $_SESSION['username'];

try {
    //  Fetch rooms with error handling
    $stmt = $db->query("SELECT * FROM rooms");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

//  Ask Us Anything logic
$question = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['question'])) {
    $question = $_POST['question'];
    $user_id = $_SESSION['user_id'];

    $stmt = $db->prepare("INSERT INTO questions (user_id, content) VALUES (?, ?)");
    $stmt->execute([$user_id, $question]);
}

$questions = $db->query("SELECT q.content, u.username FROM questions q JOIN users u ON q.user_id = u.id ORDER BY q.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Escape Rooms</title>
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
        .topbar .logout, .topbar .profile, .topbar .ask-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            margin-left: 10px;
        }
        .topbar .logout {
            background: #f44336;
        }
        .container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 30px;
        }
        .card {
            background: white;
            border-radius: 10px;
            margin: 15px;
            width: 300px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            overflow: hidden;
            text-align: center;
        }
        .card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .card h3 {
            margin: 15px 0 5px;
            color: #333;
        }
        .card p {
            margin: 0 15px 15px;
            color: #666;
        }
        .book-btn {
            background: #4CAF50;
            color: white;
            padding: 10px 15px;
            margin-bottom: 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .book-btn:hover {
            background: #388e3c;
        }
        #askPopup {
            display: none;
            position: fixed;
            top: 30%;
            left: 40%;
            background: #f2f2f2;
            padding: 20px;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>

<!-- ✅ Topbar -->
<div class="topbar">
    <h2>Hello User, <?php echo htmlspecialchars($username); ?>!</h2>
    <div>
        <form method="POST" action="profile.php" style="display:inline;">
            <button class="profile" type="submit">Profile</button>
        </form>
        <form method="POST" action="logout.php" style="display:inline;">
            <button class="logout" type="submit">Logout</button>
        </form>
        <button class="ask-btn" onclick="showAskPopup()">Ask Us Anything!</button>
    </div>
</div>

<h2 style="text-align: center; margin-top: 30px;">Available Escape Rooms</h2>

<div class="container">
    <?php foreach ($rooms as $room): ?>
        <?php
            $image = htmlspecialchars($room['image']);
            if (file_exists("images/" . $room['image'])) {
                $imagePath = "images/" . $room['image'];
            } elseif (file_exists("uploads/" . $room['image'])) {
                $imagePath = "uploads/" . $room['image'];
            } else {
                $imagePath = "images/default.png";
            }
        ?>
        <div class="card">
            <img src="<?php echo $imagePath; ?>" alt="Escape Room">
            <h3><?php echo htmlspecialchars($room['name']); ?></h3>
            <p><?php echo htmlspecialchars($room['description']); ?></p>
            <p><strong>Difficulty:</strong> <?php echo htmlspecialchars($room['difficulty']); ?></p>
            <p><strong>Price:</strong> <?php echo htmlspecialchars($room['price']); ?> SR</p>
            <a class="book-btn" href="escape_details.php?id=<?php echo $room['id']; ?>">Book Now</a>
        </div>
    <?php endforeach; ?>
</div>

<!-- ✅ Ask Popup -->
<div id="askPopup">
    <form method="POST">
        <label>Ask us anything:</label><br>
        <input type="text" name="question" placeholder="Type your question here" style="width: 100%;"><br><br>
        <button type="submit">Submit</button>
        <button type="button" onclick="hideAskPopup()">Cancel</button>
    </form>
</div>
<script>
function showAskPopup() {
    document.getElementById('askPopup').style.display = 'block';
}
function hideAskPopup() {
    document.getElementById('askPopup').style.display = 'none';
}
</script>

<!-- ✅ Public Questions -->
<h3 style="text-align:center; margin-top: 40px;">Public Questions</h3>
<div style="margin: 0 auto; width: 80%;">
    <?php foreach ($questions as $q): ?>
        <div style="background:#fff; border-radius:8px; padding:15px; margin-bottom:10px; box-shadow:0 2px 6px rgba(0,0,0,0.1);">
            <strong><?php echo htmlspecialchars($q['username']); ?> asks:</strong><br>
            <div style="color: #333;"> <?php echo htmlspecialchars($q['content']); ?> </div>
        </div>
    <?php endforeach; ?>
</div>

</body>
</html>
