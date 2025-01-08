<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$current_month = date('Y-m');

// Fetch total income for the month
try {
    $incomeStmt = $pdo->prepare("
        SELECT IFNULL(SUM(amount_earned), 0) AS total_income, COUNT(*) AS workdays
        FROM calendar
        WHERE user_id = :user_id AND DATE_FORMAT(date, '%Y-%m') = :month AND is_estimated_workday = 0
    ");
    $incomeStmt->execute([':user_id' => $user_id, ':month' => $current_month]);
    $incomeData = $incomeStmt->fetch(PDO::FETCH_ASSOC);

    $budgetStmt = $pdo->prepare("
        SELECT IFNULL(SUM(budget_amount), 0) AS total_budget
        FROM budgets
        WHERE user_id = :user_id AND month_year = :month
    ");
    $budgetStmt->execute([':user_id' => $user_id, ':month' => $current_month]);
    $budgetData = $budgetStmt->fetch(PDO::FETCH_ASSOC);

    $expensesStmt = $pdo->prepare("
        SELECT IFNULL(SUM(expense_amount), 0) AS total_expenses
        FROM expenses
        WHERE budget_id IN (
            SELECT id FROM budgets WHERE user_id = :user_id AND month_year = :month
        )
    ");
    $expensesStmt->execute([':user_id' => $user_id, ':month' => $current_month]);
    $expensesData = $expensesStmt->fetch(PDO::FETCH_ASSOC);

    $savings = $incomeData['total_income'] - $expensesData['total_expenses'];
    $remaining_budget = $budgetData['total_budget'] - $expensesData['total_expenses'];

    echo json_encode([
        'total_income' => $incomeData['total_income'] ?? 0,
        'workdays' => $incomeData['workdays'] ?? 0,
        'total_budget' => $budgetData['total_budget'] ?? 0,
        'total_expenses' => $expensesData['total_expenses'] ?? 0,
        'remaining_budget' => $remaining_budget ?? 0,
        'savings' => $savings ?? 0
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
