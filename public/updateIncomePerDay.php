<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($_SESSION['user_id'], $data['income_per_day'])) {
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

$user_id = $_SESSION['user_id'];
$income_per_day = $data['income_per_day'];

try {
    $stmt = $pdo->prepare("UPDATE users SET income_per_day = :income_per_day WHERE id = :user_id");
    $stmt->execute([
        ':income_per_day' => $income_per_day,
        ':user_id' => $user_id
    ]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
