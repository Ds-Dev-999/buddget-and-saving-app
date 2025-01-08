<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($_SESSION['user_id'], $data['budget_id'], $data['budget_amount'])) {
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

$user_id = $_SESSION['user_id'];
$budget_id = $data['budget_id'];
$budget_amount = $data['budget_amount'];

try {
    $stmt = $pdo->prepare("
        UPDATE budgets
        SET budget_amount = :budget_amount
        WHERE id = :budget_id AND user_id = :user_id
    ");
    $stmt->execute([
        ':budget_amount' => $budget_amount,
        ':budget_id' => $budget_id,
        ':user_id' => $user_id
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
