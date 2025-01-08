<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($_SESSION['user_id'], $data['expense_name'], $data['expense_amount'], $data['expense_date'])) {
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

$user_id = $_SESSION['user_id'];
$expense_date = $data['expense_date']; // Expected format: YYYY-MM-DD

// Extract the month and year from the expense date
$month_year = date('Y-m', strtotime($expense_date));

try {
    // Find the budget ID for the given month and user
    $stmt = $pdo->prepare("SELECT id FROM budgets WHERE user_id = :user_id AND month_year = :month_year");
    $stmt->execute([
        ':user_id' => $user_id,
        ':month_year' => $month_year
    ]);
    $budget = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$budget) {
        echo json_encode(['error' => 'No budget found for the specified month']);
        exit;
    }

    $budget_id = $budget['id'];

    // Check if the expense name already exists for the user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM expenses WHERE user_id = :user_id AND expense_name = :expense_name");
    $stmt->execute([
        ':user_id' => $user_id,
        ':expense_name' => $data['expense_name']
    ]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['error' => 'Expense name already exists']);
        exit;
    }

    // Insert the expense
    $stmt = $pdo->prepare("INSERT INTO expenses (user_id, budget_id, expense_name, expense_amount, expense_date) 
                           VALUES (:user_id, :budget_id, :expense_name, :expense_amount, :expense_date)");
    $stmt->execute([
        ':user_id' => $user_id,
        ':budget_id' => $budget_id,
        ':expense_name' => $data['expense_name'],
        ':expense_amount' => $data['expense_amount'],
        ':expense_date' => $expense_date
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
