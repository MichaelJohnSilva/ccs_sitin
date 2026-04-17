<?php
include 'config.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$id_number = $_GET['id_number'] ?? '';

if (!empty($id_number)) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id_number = ? AND is_read = 0");
    $stmt->bind_param("s", $id_number);
    $success = $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => $success]);
} else {
    echo json_encode(['success' => false, 'error' => 'No id_number provided']);
}

$conn->close();