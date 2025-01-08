<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Get JSON input data
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['date'], $data['amount'])) {
    $date = $data['date'];
    $amount = $data['amount'];
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("INSERT INTO calendar (user_id, date, amount_earned) VALUES (:user_id, :date, :amount)");
    $stmt->execute([
        ':user_id' => $user_id,
        ':date' => $date,
        ':amount' => $amount
    ]);

    $eventId = $pdo->lastInsertId();

    echo json_encode(['success' => true, 'id' => $eventId]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}
?>
