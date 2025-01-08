<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'], $_POST['month_year'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$user_id = $_SESSION['user_id'];
$month_year = $_POST['month_year'];

try {
    $stmt = $pdo->prepare("DELETE FROM budgets WHERE user_id = :user_id AND month_year = :month_year");
    $stmt->execute([
        ':user_id' => $user_id,
        ':month_year' => $month_year
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Budget not found']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
