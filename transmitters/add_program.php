<?php
require_once '../config/db.php';

if (!isLoggedIn() || isOperator()) {
    redirect('list.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $program_id = $_POST['program_id'] ?? 0;
    $transmitter_id = $_POST['transmitter_id'] ?? 0;
    $program_name = sanitize($_POST['program_name']);
    $frequency = sanitize($_POST['frequency']);
    $language = sanitize($_POST['language']);
    $broadcast_time = sanitize($_POST['broadcast_time']);
    $status = sanitize($_POST['status']);

    if (empty($program_name)) {
        setFlash('error', 'Program name is required.');
        redirect("edit.php?id=$transmitter_id");
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE programs 
            SET program_name = ?, frequency = ?, language = ?, broadcast_time = ?, status = ?
            WHERE id = ?
        ");
        $stmt->execute([$program_name, $frequency, $language, $broadcast_time, $status, $program_id]);

        logAction($_SESSION['user_id'], 'Edit Program', "Edited program: $program_name");
        setFlash('success', "Program updated successfully!");
    } catch (PDOException $e) {
        setFlash('error', 'Failed to update program. Please try again.');
        error_log($e->getMessage());
    }

    redirect("edit.php?id=$transmitter_id");
} else {
    redirect('list.php');
}
