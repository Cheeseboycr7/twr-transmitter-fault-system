<?php
require_once '../config/db.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

// Get all transmitters with program counts
$stmt = $pdo->query("
    SELECT t.*, 
           COUNT(DISTINCT p.id) as program_count,
           COUNT(DISTINCT f.id) as fault_count,
           SUM(CASE WHEN f.status IN ('Open', 'In Progress') THEN 1 ELSE 0 END) as open_faults
    FROM transmitters t
    LEFT JOIN programs p ON t.id = p.transmitter_id
    LEFT JOIN faults f ON t.id = f.transmitter_id
    GROUP BY t.id
    ORDER BY t.transmitter_name
");
$transmitters = $stmt->fetchAll();

// Get all programs grouped by transmitter
$programsByTransmitter = [];
$stmt = $pdo->query("
    SELECT p.*, t.transmitter_name 
    FROM programs p
    JOIN transmitters t ON p.transmitter_id = t.id
    WHERE p.status = 'Active'
    ORDER BY t.transmitter_name, p.program_name
");
$allPrograms = $stmt->fetchAll();

foreach ($allPrograms as $program) {
    $programsByTransmitter[$program['transmitter_id']][] = $program;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transmitters - Transmitter Fault System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/twr-theme.css">
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-radio"></i> Transmitters</h2>
            <?php if (!isOperator()): ?>
                <a href="add.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add Transmitter
                </a>
            <?php endif; ?>
        </div>

        <div class="row">
            <?php foreach ($transmitters as $transmitter): ?>
                <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-radio text-twr-teal"></i>
                                <?= htmlspecialchars($transmitter['transmitter_name']) ?>
                            </h5>
                            <?= statusBadge($transmitter['status']) ?>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted d-block">Frequency</small>
                                    <strong><?= htmlspecialchars($transmitter['frequency']) ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Location</small>
                                    <strong><?= htmlspecialchars($transmitter['location'] ?? 'N/A') ?></strong>
                                </div>
                                <div class="col-6 mt-2">
                                    <small class="text-muted d-block">Manufacturer</small>
                                    <strong><?= htmlspecialchars($transmitter['manufacturer'] ?? 'N/A') ?></strong>
                                </div>
                                <div class="col-6 mt-2">
                                    <small class="text-muted d-block">Power Rating</small>
                                    <strong><?= htmlspecialchars($transmitter['power_rating'] ?? 'N/A') ?></strong>
                                </div>
                            </div>

                            <!-- Programs Section -->
                            <div class="mt-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="bi bi-broadcast"></i>
                                        Programs (<?= $transmitter['program_count'] ?? 0 ?>)
                                    </small>
                                    <?php if (!isOperator()): ?>
                                        <a href="edit.php?id=<?= $transmitter['id'] ?>#programs" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-plus-circle"></i> Manage
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <?php if (isset($programsByTransmitter[$transmitter['id']])): ?>
                                    <div class="mt-2">
                                        <?php foreach ($programsByTransmitter[$transmitter['id']] as $program): ?>
                                            <span class="badge bg-light text-dark border me-1 mb-1 p-2">
                                                <i class="bi bi-mic"></i>
                                                <?= htmlspecialchars($program['program_name']) ?>
                                                <?php if ($program['broadcast_time']): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($program['broadcast_time']) ?></small>
                                                <?php endif; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <small class="text-muted">No programs assigned</small>
                                <?php endif; ?>
                            </div>

                            <hr>

                            <!-- Fault Statistics -->
                            <div class="d-flex justify-content-between">
                                <div>
                                    <span class="badge bg-primary">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        Total: <?= $transmitter['fault_count'] ?? 0 ?>
                                    </span>
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-clock"></i>
                                        Open: <?= $transmitter['open_faults'] ?? 0 ?>
                                    </span>
                                </div>
                                <div>
                                    <a href="../faults/list.php?transmitter_id=<?= $transmitter['id'] ?>"
                                        class="btn btn-sm btn-primary">
                                        <i class="bi bi-list"></i> View Faults
                                    </a>
                                    <?php if (!isOperator()): ?>
                                        <a href="edit.php?id=<?= $transmitter['id'] ?>" class="btn btn-sm btn-secondary">
                                            <i class="bi bi-gear"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>