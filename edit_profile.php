<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

/* FETCH USER DATA */
$stmt = $conn->prepare("SELECT * FROM students WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

/* UPDATE PROFILE */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $firstName = trim($_POST['firstName']);
    $middle_name = trim($_POST['middle_name']);
    $lastName = trim($_POST['lastName']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);

    $photoPath = $user['photo'] ?? '';

    if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0){

        $uploadDir = "uploads/";

        if(!is_dir($uploadDir)){
            mkdir($uploadDir,0777,true);
        }

        $fileName = time()."_".basename($_FILES['photo']['name']);
        $targetFile = $uploadDir.$fileName;

        if(move_uploaded_file($_FILES['photo']['tmp_name'],$targetFile)){
            $photoPath = $targetFile;
        }
    }

    $update = $conn->prepare("UPDATE students SET first_name=?, middle_name=?, last_name=?, email=?, address=?, photo=? WHERE id=?");
    $update->bind_param("ssssssi",
        $firstName,
        $middle_name,
        $lastName,
        $email,
        $address,
        $photoPath,
        $user_id
    );

    if($update->execute()){
        echo "<script>alert('Profile Updated');window.location='dashboard.php';</script>";
        exit();
    }

    $update->close();
}

// Fetch notifications for the user
$userIdNumber = $user['id_number'];
$notifQuery = "SELECT * FROM notifications WHERE id_number = ? ORDER BY created_at DESC LIMIT 10";
$notifStmt = $conn->prepare($notifQuery);
$notifStmt->bind_param("s", $userIdNumber);
$notifStmt->execute();
$notifications = $notifStmt->get_result();

// Store notifications in array before closing
$notifications_data = [];
if($notifications && $notifications->num_rows > 0){
    while($n = $notifications->fetch_assoc()){
        $notifications_data[] = $n;
    }
}
$notifStmt->close();

$conn->close();
unset($conn);

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
/* ===== GLOBAL ===== */
body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    position: relative;
}
    opacity: 0;
    animation: fadeIn 0.8s ease forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ===== NAVBAR ===== */
.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    padding: 15px 40px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    transition: all 0.4s ease;
}

.navbar:hover {
    box-shadow: 0 6px 25px rgba(0,0,0,0.4);
}

.navbar .logo {
    color: white;
    font-weight: bold;
    font-size: 24px;
    transition: transform 0.3s;
}

.navbar .logo:hover {
    transform: scale(1.05);
}

.navbar ul {
    list-style: none;
    display: flex;
    gap: 5px;
    margin: 0;
    padding: 0;
}

.navbar ul li a {
    text-decoration: none;
    color: white;
    font-weight: 500;
    padding: 10px 18px;
    border-radius: 8px;
    transition: all 0.3s ease;
    position: relative;
}

.navbar ul li a::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    width: 0;
    height: 3px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    transition: all 0.3s ease;
    transform: translateX(-50%);
    border-radius: 2px;
}

.navbar ul li a:hover {
    background: rgba(255,255,255,0.1);
    transform: translateY(-2px);
}

.navbar ul li a:hover::before {
    width: 80%;
}

/* ===== DROPDOWN / NOTIFICATIONS ===== */
.dropdown {
    position: relative;
    z-index: 9999;
}

.dropdown > a {
    display: inline-block;
    transition: all 0.3s ease;
}

.dropdown:hover > a {
    transform: translateY(-2px);
}

.dropdown-content {
    display: none;
    position: absolute;
    top: 50px;
    right: 0;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    min-width: 280px;
    max-height: 350px;
    overflow-y: auto;
    box-shadow: 0 15px 35px rgba(0,0,0,0.4);
    border-radius: 12px;
    overflow: hidden;
    z-index: 10000;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    opacity: 0;
    pointer-events: none;
}

.notif-badge {
    background: #eb3349;
    color: white;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 5px;
}

.notif-item {
    display: block;
    padding: 12px 15px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    transition: all 0.3s ease;
    cursor: default;
}

.notif-item:last-child { border-bottom: none; }
.notif-item:hover { background: rgba(255,255,255,0.1); }
.notif-item.success { border-left: 3px solid #38ef7d; }
.notif-item.error { border-left: 3px solid #eb3349; }
.notif-item.info { border-left: 3px solid #667eea; }

.notif-icon { font-weight: 700; margin-right: 8px; }
.notif-item.success .notif-icon { color: #38ef7d; }
.notif-item.error .notif-icon { color: #eb3349; }
.notif-item.info .notif-icon { color: #667eea; }

.notif-time {
    display: block;
    font-size: 11px;
    color: rgba(255,255,255,0.5);
    margin-top: 5px;
}

.dropdown:hover .dropdown-content {
    display: block;
    transform: translateY(0);
    opacity: 1;
    pointer-events: auto;
}

.dropdown-content p {
    padding: 12px 16px;
    margin: 0;
    font-size: 14px;
    color: rgba(255,255,255,0.9);
    transition: all 0.3s ease;
    cursor: default;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.dropdown-content p:last-child {
    border-bottom: none;
}

.dropdown-content p:hover {
    background: rgba(255,255,255,0.1);
}

/* ===== EDIT PROFILE CARD ===== */
.edit-container {
    max-width: 800px;
    margin: 30px auto;
    background: white;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    transition: all 0.4s ease;
    opacity: 0;
    transform: translateY(20px);
    animation: fadeUp 0.7s forwards;
}

@keyframes fadeUp {
    to { opacity: 1; transform: translateY(0); }
}

.edit-container:hover {
    transform: translateY(-6px);
    box-shadow: 0 25px 70px rgba(0,0,0,0.4);
}

/* ===== FORM GRID ===== */
form {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px 25px;
}

form label {
    display: block;
    font-weight: 600;
    margin-top: 8px;
    color: #333;
    opacity: 0;
    transform: translateY(10px);
    animation: labelUp 0.6s forwards;
}

@keyframes labelUp {
    to { opacity: 1; transform: translateY(0); }
}

form input[type="text"],
form input[type="email"],
form input[type="file"] {
    width: 100%;
    padding: 12px;
    margin-top: 6px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    transition: all 0.4s ease;
    font-family: 'Poppins', sans-serif;
}

form input:focus {
    border-color: #667eea;
    outline: none;
    box-shadow: 0 0 15px rgba(102, 126, 234, 0.4);
    transform: translateY(-2px);
}

/* ID Number Full Width */
form input[disabled] {
    grid-column: 1 / -1;
    background: #f0f0f0;
}

/* Profile Photo Full Width */
form input[type="file"] {
    grid-column: 1 / -1;
}

/* Buttons Full Width on Bottom */
.btn-group {
    grid-column: 1 / -1;
    display: flex;
    justify-content: space-between;
    gap: 15px;
    margin-top: 15px;
}

.btn-group button {
    flex: 1;
    padding: 14px;
    font-size: 16px;
    font-weight: 500;
    border-radius: 10px;
    cursor: pointer;
    border: none;
    color: #fff;
    transition: all 0.35s ease;
    font-family: 'Poppins', sans-serif;
}

.btn-group button[type="submit"] {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.btn-group button[type="submit"]:hover {
    background: linear-gradient(135deg, #5a6fd6 0%, #6a4190 100%);
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(102,126,234,0.5);
}

.btn-group .cancel-btn {
    background: #6c757d;
}

.btn-group .cancel-btn:hover {
    background: #5a6268;
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.3);
}

/* Profile Preview */
.profile-preview {
    grid-column: 1 / -1;
    margin: 15px auto;
    display: block;
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #007bff;
    transition: all 0.4s ease;
}

.profile-preview:hover {
    transform: scale(1.12) rotate(2deg);
    box-shadow: 0 12px 30px rgba(0,123,255,0.45);
}

/* RESPONSIVE */
@media (max-width: 768px) {
    form {
        display: block;
    }

    .btn-group {
        flex-direction: column;
    }

    .btn-group button {
        width: 100%;
    }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <div class="logo">CCS Sit-in System</div>
    <ul>
         <li class="dropdown">
            <a href="javascript:void(0)" class="dropdown-trigger">
                Notifications 
                <?php 
                $unreadCount = 0;
                foreach($notifications_data as $n){
                    if(!$n['is_read']) $unreadCount++;
                }
                if($unreadCount > 0) echo '<span class="notif-badge">' . $unreadCount . '</span>';
                ?>
            </a>
            <div class="dropdown-content">
                <?php 
                if(count($notifications_data) > 0): 
                    foreach($notifications_data as $notif): 
                        $redirectUrl = ($notif['type'] === 'success') ? 'history.php' : 'reservation.php';
                ?>
                    <p class="notif-item <?php echo htmlspecialchars($notif['type']); ?>" onclick="window.location.href='<?php echo $redirectUrl; ?>'" style="cursor:pointer;">
                        <span class="notif-icon"><?php echo $notif['type'] === 'success' ? '✓' : ($notif['type'] === 'error' ? '✗' : 'ℹ'); ?></span>
                        <?php echo htmlspecialchars($notif['message']); ?>
                        <small class="notif-time"><?php echo date("M d, h:i A", strtotime($notif['created_at'])); ?></small>
                    </p>
                <?php 
                    endforeach; 
                else: 
                ?>
                    <p>No new notifications</p>
                <?php endif; ?>
            </div>
        </li>
        <li><a href="dashboard.php">Home</a></li>
        <li><a href="edit_profile.php" class="active">Edit Profile</a></li>
        <li><a href="history.php">History</a></li>
        <li><a href="logout.php">Logout</a></li>
    
    </ul>
</div>

<!-- EDIT PROFILE FORM -->
<div class="edit-container">
    <h2>Edit Profile</h2>
    <form method="POST" enctype="multipart/form-data">

        <label>ID Number</label>
        <input type="text" value="<?php echo htmlspecialchars($user['id_number']); ?>" disabled>

        <label>First Name</label>
        <input type="text" name="firstName" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>

        <label>Middle Name</label>
        <input type="text" name="middle_name" value="<?php echo htmlspecialchars($user['middle_name']); ?>">

        <label>Last Name</label>
        <input type="text" name="lastName" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

        <label>Address</label>
        <input type="text" name="address" value="<?php echo htmlspecialchars($user['address']); ?>">

        <label>Profile Photo</label>
        <input type="file" name="photo">

        <?php if(!empty($user['photo'])): ?>
            <img src="<?php echo htmlspecialchars($user['photo']); ?>" class="profile-preview">
        <?php endif; ?>

        <div class="btn-group">
            <button type="submit">Update Profile</button>
            <button type="button" class="cancel-btn" onclick="window.location='dashboard.php'">Cancel</button>
        </div>

    </form>
</div>

</body>
</html>