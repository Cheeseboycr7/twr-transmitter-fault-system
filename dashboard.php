<?php
require_once 'config/db.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Get statistics
$stats = [];

// Total faults
$stmt = $pdo->query("SELECT COUNT(*) as count FROM faults");
$stats['total_faults'] = $stmt->fetch()['count'];

// Open faults
$stmt = $pdo->query("SELECT COUNT(*) as count FROM faults WHERE status IN ('Open', 'In Progress')");
$stats['open_faults'] = $stmt->fetch()['count'];

// Fixed faults
$stmt = $pdo->query("SELECT COUNT(*) as count FROM faults WHERE status = 'Fixed'");
$stats['fixed_faults'] = $stmt->fetch()['count'];

// Critical faults
$stmt = $pdo->query("SELECT COUNT(*) as count FROM faults WHERE severity = 'Critical' AND status IN ('Open', 'In Progress')");
$stats['critical_faults'] = $stmt->fetch()['count'];

// Faults by transmitter
$stmt = $pdo->query("
    SELECT t.transmitter_name, COUNT(f.id) as count 
    FROM transmitters t 
    LEFT JOIN faults f ON t.id = f.transmitter_id 
    GROUP BY t.id 
    ORDER BY count DESC 
    LIMIT 5
");
$transmitter_faults = $stmt->fetchAll();

// Recent faults
$stmt = $pdo->query("
    SELECT f.*, t.transmitter_name, u.fullname as reported_by_name 
    FROM faults f 
    LEFT JOIN transmitters t ON f.transmitter_id = t.id 
    LEFT JOIN users u ON f.reported_by = u.id 
    ORDER BY f.date_reported DESC 
    LIMIT 10
");
$recent_faults = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Transmitter Fault System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/twr-theme.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-broadcast"></i> BTFMS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="faults/list.php"><i class="bi bi-exclamation-triangle"></i> Faults</a></li>
                    <li class="nav-item"><a class="nav-link" href="transmitters/list.php"><i class="bi bi-radio"></i> Transmitters</a></li>
                    <li class="nav-item"><a class="nav-link" href="maintenance/list.php"><i class="bi bi-tools"></i> Maintenance</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports/index.php"><i class="bi bi-file-text"></i> Reports</a></li>
                    <li class="nav-item"><a class="nav-link" href="knowledge_base/search.php"><i class="bi bi-search"></i> Knowledge Base</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><span class="navbar-text me-3">👋 <?= $_SESSION['fullname'] ?> (<?= $_SESSION['role'] ?>)</span></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <h2>📊 Dashboard</h2>

        <div class="row mt-4">
            <div class="col-md-3">
                <div class="stat-card stat-total">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6>Total Faults</h6>
                            <h3><?= $stats['total_faults'] ?></h3>
                        </div>
                        <i class="bi bi-clipboard-data"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-open">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6>Open Faults</h6>
                            <h3><?= $stats['open_faults'] ?></h3>
                        </div>
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-fixed">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6>Fixed Faults</h6>
                            <h3><?= $stats['fixed_faults'] ?></h3>
                        </div>
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-critical">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6>Critical Open</h6>
                            <h3><?= $stats['critical_faults'] ?></h3>
                        </div>
                        <i class="bi bi-exclamation-octagon"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Faults</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Fault No</th>
                                        <th>Transmitter</th>
                                        <th>Frequency</th>
                                        <th>Severity</th>
                                        <th>Status</th>
                                        <th>Reported</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_faults as $fault): ?>
                                        <tr>
                                            <td><a href="faults/details.php?id=<?= $fault['id'] ?>"><?= $fault['fault_no'] ?></a></td>
                                            <td><?= $fault['transmitter_name'] ?? 'N/A' ?></td>
                                            <td><?= $fault['frequency'] ?></td>
                                            <td>
                                                <span class="badge bg-<?= $fault['severity'] == 'Critical' ? 'danger' : ($fault['severity'] == 'Medium' ? 'warning' : 'info') ?>">
                                                    <?= $fault['severity'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $fault['status'] == 'Open' ? 'warning' : ($fault['status'] == 'In Progress' ? 'info' : 'success') ?>">
                                                    <?= $fault['status'] ?>
                                                </span>
                                            </td>
                                            <td><?= date('d-M-Y', strtotime($fault['date_reported'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Top Problematic Transmitters</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="transmitterChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Transmitter fault chart
        const ctx = document.getElementById('transmitterChart').getContext('2d');
        const labels = <?= json_encode(array_column($transmitter_faults, 'transmitter_name')) ?>;
        const data = <?= json_encode(array_column($transmitter_faults, 'count')) ?>;

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: ['#667eea', '#f093fb', '#4facfe', '#fa709a', '#43e97b']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>

</html>