<?php
require_once '../config/db.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

// Check if user has permission
if (!isEngineer() && !isAdmin()) {
    setFlash('error', 'You do not have permission to access maintenance functions.');
    redirect('../dashboard.php');
}

// Get maintenance records
$stmt = $pdo->query("
    SELECT m.*, t.transmitter_name 
    FROM maintenance m
    LEFT JOIN transmitters t ON m.transmitter_id = t.id
    ORDER BY m.date_done DESC
");
$maintenance = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - Transmitter Fault System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/twr-theme.css">
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-tools"></i> Maintenance Records</h2>
            <a href="add.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Maintenance Record
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Transmitter</th>
                                <th>Maintenance Type</th>
                                <th>Description</th>
                                <th>Date Done</th>
                                <th>Engineer</th>
                                <th>Status</th>
                                <th>Next Maintenance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($maintenance)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No maintenance records found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($maintenance as $record): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($record['transmitter_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($record['maintenance_type']) ?></td>
                                        <td><?= truncate($record['description'], 50) ?></td>
                                        <td><?= formatDate($record['date_done'], 'd-M-Y') ?></td>
                                        <td><?= htmlspecialchars($record['engineer']) ?></td>
                                        <td><?= statusBadge($record['status']) ?></td>
                                        <td>
                                            <?php if ($record['next_maintenance_date']): ?>
                                                <?= formatDate($record['next_maintenance_date'], 'd-M-Y') ?>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="edit.php?id=<?= $record['id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="delete.php?id=<?= $record['id'] ?>" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure you want to delete this record?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>