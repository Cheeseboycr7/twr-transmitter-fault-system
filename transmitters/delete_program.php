<?php
require_once '../config/db.php';

if (!isLoggedIn() || isOperator()) {
    redirect('list.php');
}

$program_id = $_GET['id'] ?? 0;
$transmitter_id = $_GET['transmitter_id'] ?? 0;

if ($program_id) {
    try {
        // Check if program has any faults
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM faults WHERE program_id = ?");
        $stmt->execute([$program_id]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            setFlash('error', "Cannot delete program as it has $count fault(s) associated with it.");
        } else {
            $stmt = $pdo->prepare("DELETE FROM programs WHERE id = ?");
            $stmt->execute([$program_id]);
            setFlash('success', 'Program deleted successfully.');
        }
    } catch (PDOException $e) {
        setFlash('error', 'Failed to delete program. Please try again.');
        error_log($e->getMessage());
    }
}

redirect("edit.php?id=$transmitter_id");
