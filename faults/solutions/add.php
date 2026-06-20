<?php
require_once '../../config/db.php';

if (!isLoggedIn() || isOperator()) {
    if (isOperator()) {
        setFlash('error', 'Operators do not have permission to mark faults as fixed.');
    }
    redirect('../list.php');
}

$fault_id = $_GET['fault_id'] ?? 0;

// Get fault details
$stmt = $pdo->prepare("SELECT * FROM faults WHERE id = ?");
$stmt->execute([$fault_id]);
$fault = $stmt->fetch();

if (!$fault) {
    setFlash('error', 'Fault not found.');
    redirect('../list.php');
}

if ($fault['status'] == 'Fixed') {
    setFlash('warning', 'This fault is already marked as fixed.');
    redirect("../details.php?id=$fault_id");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $root_cause = sanitize($_POST['root_cause']);
    $solution = sanitize($_POST['solution']);
    $parts_replaced = sanitize($_POST['parts_replaced']);
    $repair_time = sanitize($_POST['repair_time']);
    $fixed_by = $_SESSION['user_id'];

    // Insert solution
    $stmt = $pdo->prepare("
        INSERT INTO solutions (fault_id, root_cause, solution, parts_replaced, repair_time, fixed_by) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$fault_id, $root_cause, $solution, $parts_replaced, $repair_time, $fixed_by]);

    // Update fault status
    $stmt = $pdo->prepare("UPDATE faults SET status = 'Fixed' WHERE id = ?");
    $stmt->execute([$fault_id]);

    logAction($_SESSION['user_id'], 'Fix Fault', "Fixed fault {$fault['fault_no']}");
    setFlash('success', 'Fault marked as fixed! Solution recorded.');
    redirect("../details.php?id=$fault_id");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Solution - Transmitter Fault System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/twr-theme.css">
</head>

<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container mt-4">
        <h2><i class="bi bi-check-circle"></i> Add Solution for <?= htmlspecialchars($fault['fault_no']) ?></h2>

        <div class="alert alert-info">
            <strong>Fault:</strong> <?= htmlspecialchars($fault['fault_description']) ?><br>
            <strong>Transmitter:</strong> <?= htmlspecialchars($fault['transmitter_id'] ?? 'N/A') ?> |
            <strong>Frequency:</strong> <?= htmlspecialchars($fault['frequency']) ?> |
            <strong>Program:</strong> <?= htmlspecialchars($fault['program_name'] ?? 'N/A') ?>
        </div>

        <div class="card mt-4">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Root Cause *</label>
                        <textarea name="root_cause" class="form-control" rows="3" required
                            placeholder="What was the root cause of the fault? (e.g., Frozen Optimod, Power supply failure, Software bug)"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Solution *</label>
                        <textarea name="solution" class="form-control" rows="3" required
                            placeholder="How was the fault fixed? (e.g., Restarted equipment, Replaced part, Updated software)"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Parts Replaced</label>
                        <textarea name="parts_replaced" class="form-control" rows="2"
                            placeholder="List any parts that were replaced (e.g., Power supply module, Fuse, Circuit board)"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Repair Time</label>
                        <input type="text" name="repair_time" class="form-control"
                            placeholder="e.g., 30 minutes, 2 hours, 1 day">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Mark as Fixed
                        </button>
                        <a href="../details.php?id=<?= $fault_id ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>