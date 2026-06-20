<?php
require_once '../config/db.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

// Get filter parameters
$status = $_GET['status'] ?? '';
$severity = $_GET['severity'] ?? '';
$search = $_GET['search'] ?? '';
$transmitter_id = $_GET['transmitter_id'] ?? '';

// Build query
$query = "SELECT f.*, t.transmitter_name, u.fullname as reported_by_name 
          FROM faults f 
          LEFT JOIN transmitters t ON f.transmitter_id = t.id 
          LEFT JOIN users u ON f.reported_by = u.id 
          WHERE 1=1";
$params = [];

if (!empty($status)) {
    $query .= " AND f.status = ?";
    $params[] = $status;
}

if (!empty($severity)) {
    $query .= " AND f.severity = ?";
    $params[] = $severity;
}

if (!empty($search)) {
    $query .= " AND (f.fault_description LIKE ? OR f.fault_no LIKE ? OR f.frequency LIKE ? OR f.program_name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($transmitter_id)) {
    $query .= " AND f.transmitter_id = ?";
    $params[] = $transmitter_id;
}

$query .= " ORDER BY f.date_reported DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$faults = $stmt->fetchAll();

// Get transmitters for filter
$transmitters = $pdo->query("SELECT id, transmitter_name FROM transmitters ORDER BY transmitter_name")->fetchAll();

// Get counts for stats
$stmt = $pdo->query("SELECT COUNT(*) as total FROM faults");
$totalFaults = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as open FROM faults WHERE status IN ('Open', 'In Progress')");
$openFaults = $stmt->fetch()['open'];

$stmt = $pdo->query("SELECT COUNT(*) as fixed FROM faults WHERE status = 'Fixed'");
$fixedFaults = $stmt->fetch()['fixed'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faults List - Transmitter Fault System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/twr-theme.css">
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-list"></i> Faults List</h2>
            <?php if (!isOperator()): ?>
                <a href="add.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Record New Fault
                </a>
            <?php endif; ?>
        </div>

        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card stat-total">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6>Total Faults</h6>
                            <h3><?= $totalFaults ?></h3>
                        </div>
                        <i class="bi bi-clipboard-data"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card stat-open">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6>Open Faults</h6>
                            <h3><?= $openFaults ?></h3>
                        </div>
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card stat-fixed">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6>Fixed Faults</h6>
                            <h3><?= $fixedFaults ?></h3>
                        </div>
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Search faults..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Open" <?= $status == 'Open' ? 'selected' : '' ?>>Open</option>
                            <option value="In Progress" <?= $status == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="Fixed" <?= $status == 'Fixed' ? 'selected' : '' ?>>Fixed</option>
                            <option value="Closed" <?= $status == 'Closed' ? 'selected' : '' ?>>Closed</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Severity</label>
                        <select name="severity" class="form-select">
                            <option value="">All Severity</option>
                            <option value="Low" <?= $severity == 'Low' ? 'selected' : '' ?>>Low</option>
                            <option value="Medium" <?= $severity == 'Medium' ? 'selected' : '' ?>>Medium</option>
                            <option value="Critical" <?= $severity == 'Critical' ? 'selected' : '' ?>>Critical</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Transmitter</label>
                        <select name="transmitter_id" class="form-select">
                            <option value="">All Transmitters</option>
                            <?php foreach ($transmitters as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= $transmitter_id == $t['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['transmitter_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <a href="list.php" class="btn btn-secondary w-100 ms-2">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Faults Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Fault No</th>
                                <th>Transmitter</th>
                                <th>Frequency</th>
                                <th>Program</th>
                                <th>Severity</th>
                                <th>Status</th>
                                <th>Reported By</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($faults)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No faults found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($faults as $fault): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($fault['fault_no']) ?></strong></td>
                                        <td><?= htmlspecialchars($fault['transmitter_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($fault['frequency']) ?></td>
                                        <td><?= htmlspecialchars($fault['program_name'] ?? 'N/A') ?></td>
                                        <td><?= severityBadge($fault['severity']) ?></td>
                                        <td><?= statusBadge($fault['status']) ?></td>
                                        <td><?= htmlspecialchars($fault['reported_by_name'] ?? 'Unknown') ?></td>
                                        <td><?= formatDate($fault['date_reported'], 'd-M-Y') ?></td>
                                        <td>
                                            <a href="details.php?id=<?= $fault['id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if (!isOperator() && $fault['status'] != 'Fixed'): ?>
                                                <a href="troubleshooting/add.php?fault_id=<?= $fault['id'] ?>" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-tools"></i>
                                                </a>
                                            <?php endif; ?>
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