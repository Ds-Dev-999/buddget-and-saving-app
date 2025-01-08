<?php
require_once '../config/database.php'; // Adjust path as needed

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['id']) && isset($data['status'])) {
    $stmt = $pdo->prepare("UPDATE expenses SET status = :status WHERE id = :id");
    $stmt->execute([':status' => $data['status'], ':id' => $data['id']]);

    echo json_encode(['success' => $stmt->rowCount() > 0]);
} else {
    echo json_encode(['success' => false]);
}
?>
