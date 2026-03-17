<?php
session_start();
include "config.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_number = $_POST['id_number'];
    $student_name = $_POST['student_name'];
    $purpose = $_POST['purpose'];
    $lab = $_POST['lab'];

    // Fetch current remaining sessions
    $stmtStudent = $conn->prepare("SELECT sessions_remaining FROM students WHERE id_number = ?");
    $stmtStudent->bind_param("s", $id_number);
    $stmtStudent->execute();
    $resultStudent = $stmtStudent->get_result();
    $student = $resultStudent->fetch_assoc();

    if(!$student) {
        die("Student not found.");
    }

    $remaining_sessions = $student['sessions_remaining'];

    if($remaining_sessions <= 0){
        die("No remaining sessions left for this student.");
    }

    // Insert sit-in record with Active status
    $stmt = $conn->prepare("
    INSERT INTO sitin_records (id_number, purpose, lab, time_in, status)
    VALUES (?, ?, ?, NOW(), 'Active')
    ");
    $stmt->bind_param("sss", $id_number, $purpose, $lab);  
    $stmt->execute();

    // Decrease remaining sessions by 1
    $stmtUpdate = $conn->prepare("UPDATE students SET sessions_remaining = sessions_remaining - 1 WHERE id_number = ?");
    $stmtUpdate->bind_param("s", $id_number);
    $stmtUpdate->execute();

    // Redirect to view sit-in records
    header("Location: view_sitin_records.php");
    exit();
}
?>