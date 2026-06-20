<?php
require_once '../../config/db.php';

if (!isLoggedIn() || isOperator()) {
    if (isOperator()) {
        setFlash('error', 'Operators do not have permission to add troubleshooting steps.');
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $observation = sanitize($_POST['observation']);
    $actions_taken = sanitize($_POST['actions_taken']);
    $measurement = sanitize($_POST['measurement']);
    $recorded_by = $_SESSION['user_id'];

    $stmt = $pdo->prepare("
        INSERT INTO troubleshooting (fault_id, observation, actions_taken, measurement, recorded_by) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$fault_id, $observation, $actions_taken, $measurement, $recorded_by]);

    // Update fault status to In Progress if it's Open
    if ($fault['status'] == 'Open') {
        $stmt = $pdo->prepare("UPDATE faults SET status = 'In Progress' WHERE id = ?");
        $stmt->execute([$fault_id]);
    }

    logAction($_SESSION['user_id'], 'Add Troubleshooting', "Added troubleshooting for fault {$fault['fault_no']}");
    setFlash('success', 'Troubleshooting step added successfully!');
    redirect("../details.php?id=$fault_id");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Troubleshooting - Transmitter Fault System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/twr-theme.css">
</head>

<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container mt-4">
        <h2><i class="bi bi-tools"></i> Add Troubleshooting Step</h2>

        <div class="alert alert-info">
            <strong>Fault:</strong> <?= htmlspecialchars($fault['fault_no']) ?> - <?= htmlspecialchars($fault['fault_description']) ?><br>
            <strong>Transmitter:</strong> <?= htmlspecialchars($fault['transmitter_id'] ?? 'N/A') ?> |
            <strong>Frequency:</strong> <?= htmlspecialchars($fault['frequency']) ?>
        </div>

        <div class="card mt-4">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Observations *</label>
                        <textarea name="observation" class="form-control" rows="3" required
                            placeholder="What did you observe? (e.g., voltage readings, error messages, physical condition)"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Actions Taken *</label>
                        <textarea name="actions_taken" class="form-control" rows="3" required
                            placeholder="What actions did you take? (e.g., restarted equipment, replaced parts, adjusted settings)"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Measurements</label>
                        <textarea name="measurement" class="form-control" rows="2"
                            placeholder="Record any measurements taken (e.g., voltage: 12.5V, current: 2.3A, VSWR: 1.5:1)"></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Step
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