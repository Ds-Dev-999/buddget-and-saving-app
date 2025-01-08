<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$current_month = $_GET['month'] ?? date('Y-m'); // Allow dynamic filtering

try {
    // Fetch budget details
    $budgetStmt = $pdo->prepare("
        SELECT id, budget_amount, savings
        FROM budgets
        WHERE user_id = :user_id AND month_year = :month
    ");
    $budgetStmt->execute([':user_id' => $user_id, ':month' => $current_month]);
    $budget = $budgetStmt->fetch(PDO::FETCH_ASSOC);

    // Fetch expenses for the month
    $expensesStmt = $pdo->prepare("
        SELECT expense_name, expense_amount, expense_date
        FROM expenses
        WHERE budget_id = :budget_id
    ");
    $expensesStmt->execute([':budget_id' => $budget['id']]);
    $expenses = $expensesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total expenses
    $total_expenses = array_sum(array_column($expenses, 'expense_amount'));

    // Calculate remaining budget and required working days
    $remaining_budget = $budget['budget_amount'] - $total_expenses;
    $income_per_day = 5000; // Assume $5000/day income
    $required_workdays = ceil(max(0, $total_expenses) / $income_per_day);

    echo json_encode([
        'budget' => $budget,
        'expenses' => $expenses,
        'total_expenses' => $total_expenses,
        'remaining_budget' => $remaining_budget,
        'required_workdays' => $required_workdays
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
