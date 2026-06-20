<?php
require_once '../config/db.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$transmitter_id = $_GET['transmitter_id'] ?? 0;

if (!$transmitter_id) {
    echo json_encode([]);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT id, program_name, frequency 
        FROM programs 
        WHERE transmitter_id = ? AND status = 'Active' 
        ORDER BY program_name
    ");
    $stmt->execute([$transmitter_id]);
    $programs = $stmt->fetchAll();

    header('Content-Type: application/json');
    echo json_encode($programs);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    error_log('Error fetching programs: ' . $e->getMessage());
}
