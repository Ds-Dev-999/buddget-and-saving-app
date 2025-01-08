<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if the session and POST data are valid
if (!isset($_SESSION['user_id'], $_POST['expense_name'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$user_id = $_SESSION['user_id'];
$expense_name = $_POST['expense_name'];

try {
    // Check if the expense exists
    $stmt = $pdo->prepare("
        SELECT id 
        FROM expenses 
        WHERE expense_name = :expense_name 
        AND user_id = :user_id
    ");
    $stmt->execute([
        ':expense_name' => $expense_name,
        ':user_id' => $user_id
    ]);

    $expense = $stmt->fetch();

    if (!$expense) {
        echo json_encode(['error' => 'Expense not found or unauthorized']);
        exit;
    }

    // Proceed to delete the expense
    $stmt = $pdo->prepare("
        DELETE FROM expenses 
        WHERE expense_name = :expense_name 
        AND user_id = :user_id
    ");
    $stmt->execute([
        ':expense_name' => $expense_name,
        ':user_id' => $user_id
    ]);

    echo json_encode(['success' => 'Expense deleted successfully.']);
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    echo json_encode(['error' => 'An error occurred. Please try again.']);
}
