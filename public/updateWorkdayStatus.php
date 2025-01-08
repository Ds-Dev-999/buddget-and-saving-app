<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Validate input
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($_SESSION['user_id'], $data['id'], $data['isCompleted'])) {
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

$user_id = $_SESSION['user_id'];
$id = $data['id'];
$isCompleted = $data['isCompleted'] ? 1 : 0;

try {
    $stmt = $pdo->prepare("
        UPDATE calendar
        SET is_completed = :isCompleted
        WHERE id = :id AND user_id = :user_id
    ");
    $stmt->execute([
        ':isCompleted' => $isCompleted,
        ':id' => $id,
        ':user_id' => $user_id
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
