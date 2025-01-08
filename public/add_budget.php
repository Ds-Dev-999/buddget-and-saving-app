<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($_SESSION['user_id'], $data['month_year'], $data['budget_amount'])) {
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Check if a budget already exists for the given month and user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM budgets WHERE user_id = :user_id AND month_year = :month_year");
    $stmt->execute([
        ':user_id' => $user_id,
        ':month_year' => $data['month_year']
    ]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['error' => 'Budget for this month already exists']);
        exit;
    }

    // Insert the budget
    $stmt = $pdo->prepare("INSERT INTO budgets (user_id, month_year, budget_amount, savings_goal) 
                           VALUES (:user_id, :month_year, :budget_amount, :savings_goal)");
    $stmt->execute([
        ':user_id' => $user_id,
        ':month_year' => $data['month_year'],
        ':budget_amount' => $data['budget_amount'],
        ':savings_goal' => $data['savings_goal'] ?? 0.00
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
