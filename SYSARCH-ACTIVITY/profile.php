<?php
session_start();
include('config.php');

// Redirect if not logged in
if(!isset($_SESSION['user_id'])){
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare("SELECT * FROM students WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch sit-in summary for current user
$id_number = $user['id_number'];
$summaryQuery = "
    SELECT 
        COUNT(*) as total_sessions,
        SUM(TIMESTAMPDIFF(MINUTE, time_in, time_out)) as total_minutes,
        AVG(TIMESTAMPDIFF(MINUTE, time_in, time_out)) as avg_minutes,
        MAX(TIMESTAMPDIFF(MINUTE, time_in, time_out)) as max_minutes
    FROM sitin_records 
    WHERE id_number = ? AND status = 'Ended' AND time_out IS NOT NULL
";
$summaryStmt = $conn->prepare($summaryQuery);
$summaryStmt->bind_param("s", $id_number);
$summaryStmt->execute();
$summaryResult = $summaryStmt->get_result();
$summary = $summaryResult->fetch_assoc();
$summaryStmt->close();

$totalSessions = $summary['total_sessions'] ?? 0;
$totalMinutes = $summary['total_minutes'] ?? 0;
$avgMinutes = $summary['avg_minutes'] ?? 0;
$maxMinutes = $summary['max_minutes'] ?? 0;

$totalHours = $totalMinutes > 0 ? $totalMinutes / 60 : 0;
$avgHours = $avgMinutes > 0 ? $avgMinutes / 60 : 0;
$maxHours = $maxMinutes > 0 ? $maxMinutes / 60 : 0;

$stmt->close();
$conn->close();

// Load HTML template
include('profile_template.php');
?>