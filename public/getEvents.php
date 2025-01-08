<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT
        id,
        date AS start,
        CONCAT('LKR ', amount_earned) AS title,
        IF(is_estimated_workday = 1, '#FFD700', '#008000') AS backgroundColor,
        IF(is_estimated_workday = 1, '#FFD700', '#008000') AS borderColor,
        is_estimated_workday AS isEstimated
    FROM calendar
    WHERE user_id = :user_id
");
$stmt->execute([':user_id' => $user_id]);

$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($events);
?>
