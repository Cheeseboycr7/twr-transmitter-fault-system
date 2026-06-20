<?php
require_once '../config/db.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT f.*, t.transmitter_name, t.location, 
           u.fullname as reported_by_name,
           s.root_cause, s.solution, s.parts_replaced, s.repair_time,
           u2.fullname as fixed_by_name
    FROM faults f
    LEFT JOIN transmitters t ON f.transmitter_id = t.id
    LEFT JOIN users u ON f.reported_by = u.id
    LEFT JOIN solutions s ON f.id = s.fault_id
    LEFT JOIN users u2 ON s.fixed_by = u2.id
    WHERE f.id = ?
");
$stmt->execute([$id]);
$fault = $stmt->fetch();

if (!$fault) {
    redirect('list.php');
}

// Get troubleshooting steps
$troubleshooting = $pdo->prepare("SELECT * FROM troubleshooting WHERE fault_id = ? ORDER BY date_recorded DESC");
$troubleshooting->execute([$id]);
$troubleshooting_steps = $troubleshooting->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fault Details - Transmitter Fault System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/twr-theme.css">
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-4">
        <h2><i class="bi bi-file-text"></i> Fault Details: <?= $fault['fault_no'] ?></h2>

        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Fault Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <tr>
                                <th>Status</th>
                                <td>
                                    <span class="badge bg-<?= $fault['status'] == 'Open' ? 'warning' : ($fault['status'] == 'In Progress' ? 'info' : 'success') ?>">
                                        <?= $fault['status'] ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Severity</th>
                                <td>
                                    <span class="badge bg-<?= $fault['severity'] == 'Critical' ? 'danger' : ($fault['severity'] == 'Medium' ? 'warning' : 'info') ?>">
                                        <?= $fault['severity'] ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Transmitter</th>
                                <td><?= $fault['transmitter_name'] ?? 'N/A' ?></td>
                            </tr>
                            <tr>
                                <th>Frequency</th>
                                <td><?= $fault['frequency'] ?></td>
                            </tr>
                            <tr>
                                <th>Program</th>
                                <td><?= $fault['program_name'] ?? 'N/A' ?></td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td><?= nl2br($fault['fault_description']) ?></td>
                            </tr>
                            <tr>
                                <th>Reported By</th>
                                <td><?= $fault['reported_by_name'] ?></td>
                            </tr>
                            <tr>
                                <th>Date Reported</th>
                                <td><?= date('d-M-Y H:i', strtotime($fault['date_reported'])) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <?php if ($fault['root_cause']): ?>
                    <div class="card mt-3">
                        <div class="card-header bg-success text-white">
                            <h5>✅ Solution</h5>
                        </div>
                        <div class="card-body">
                            <table class="table">
                                <tr>
                                    <th>Root Cause</th>
                                    <td><?= nl2br($fault['root_cause']) ?></td>
                                </tr>
                                <tr>
                                    <th>Solution</th>
                                    <td><?= nl2br($fault['solution']) ?></td>
                                </tr>
                                <tr>
                                    <th>Parts Replaced</th>
                                    <td><?= nl2br($fault['parts_replaced']) ?></td>
                                </tr>
                                <tr>
                                    <th>Repair Time</th>
                                    <td><?= $fault['repair_time'] ?></td>
                                </tr>
                                <tr>
                                    <th>Fixed By</th>
                                    <td><?= $fault['fixed_by_name'] ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if ($fault['status'] != 'Fixed'): ?>
                                <a href="troubleshooting/add.php?fault_id=<?= $fault['id'] ?>" class="btn btn-warning">
                                    <i class="bi bi-tools"></i> Add Troubleshooting
                                </a>
                                <a href="solutions/add.php?fault_id=<?= $fault['id'] ?>" class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> Mark as Fixed
                                </a>
                            <?php endif; ?>
                            <a href="edit.php?id=<?= $fault['id'] ?>" class="btn btn-primary">
                                <i class="bi bi-pencil"></i> Edit Fault
                            </a>
                            <a href="list.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>

                <?php if ($troubleshooting_steps): ?>
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5>Troubleshooting Steps</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($troubleshooting_steps as $step): ?>
                                <div class="border-bottom mb-2 pb-2">
                                    <small class="text-muted"><?= date('d-M-Y H:i', strtotime($step['date_recorded'])) ?></small>
                                    <p><strong>Observation:</strong><br><?= nl2br($step['observation']) ?></p>
                                    <p><strong>Actions:</strong><br><?= nl2br($step['actions_taken']) ?></p>
                                    <?php if ($step['measurement']): ?>
                                        <p><strong>Measurements:</strong><br><?= nl2br($step['measurement']) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>