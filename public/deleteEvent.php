<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Get JSON input data
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['id'])) {
    $id = $data['id'];

    $stmt = $pdo->prepare("DELETE FROM calendar WHERE id = :id");
    $stmt->execute([':id' => $id]);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}
?>
