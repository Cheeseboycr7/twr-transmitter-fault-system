<?php
require_once '../config/db.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

// Check if user has permission
if (!isEngineer() && !isAdmin()) {
    setFlash('error', 'You do not have permission to access reports.');
    redirect('../dashboard.php');
}

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM faults");
$totalFaults = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM faults WHERE status = 'Fixed'");
$fixedFaults = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM faults WHERE severity = 'Critical'");
$criticalFaults = $stmt->fetch()['total'];

// Get fault statistics by transmitter
$stmt = $pdo->query("
    SELECT t.transmitter_name, COUNT(f.id) as count, 
           SUM(CASE WHEN f.status = 'Fixed' THEN 1 ELSE 0 END) as fixed
    FROM transmitters t
    LEFT JOIN faults f ON t.id = f.transmitter_id
    GROUP BY t.id
    ORDER BY count DESC
");
$transmitterStats = $stmt->fetchAll();

// Get monthly fault trends
$stmt = $pdo->query("
    SELECT DATE_FORMAT(date_reported, '%Y-%m') as month, COUNT(*) as count
    FROM faults
    WHERE date_reported >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
    GROUP BY month
    ORDER BY month DESC
");
$monthlyTrends = $stmt->fetchAll();

// Get most common fault types
$stmt = $pdo->query("
    SELECT root_cause, COUNT(*) as count
    FROM solutions s
    JOIN faults f ON s.fault_id = f.id
    GROUP BY root_cause
    ORDER BY count DESC
    LIMIT 10
");
$commonCauses = $stmt->fetchAll();

// Get average repair time
$stmt = $pdo->query("
    SELECT AVG(TIME_TO_SEC(repair_time)) as avg_time
    FROM solutions
    WHERE repair_time IS NOT NULL
");
$avgRepairTime = $stmt->fetch()['avg_time'] ?? 0;
$avgRepairTimeFormatted = gmdate('H:i:s', $avgRepairTime);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Transmitter Fault System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/twr-theme.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid mt-4">
        <h2><i class="bi bi-file-text"></i> Reports & Analytics</h2>

        <!-- Summary Stats -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="stat-card stat-total">
                    <h6>Total Faults</h6>
                    <h3><?= $totalFaults ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-fixed">
                    <h6>Fixed Faults</h6>
                    <h3><?= $fixedFaults ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-critical">
                    <h6>Critical Faults</h6>
                    <h3><?= $criticalFaults ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-open">
                    <h6>Avg Repair Time</h6>
                    <h3><?= $avgRepairTimeFormatted ?></h3>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Faults by Transmitter</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="transmitterChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Monthly Fault Trends (Last 12 Months)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Top 10 Common Causes</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="causeChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Export Reports</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="export_pdf.php?type=daily" class="btn btn-primary">
                                <i class="bi bi-file-pdf"></i> Daily Report
                            </a>
                            <a href="export_pdf.php?type=monthly" class="btn btn-success">
                                <i class="bi bi-file-pdf"></i> Monthly Report
                            </a>
                            <a href="export_excel.php" class="btn btn-info">
                                <i class="bi bi-file-excel"></i> Export to Excel
                            </a>
                            <a href="print.php" class="btn btn-secondary" target="_blank">
                                <i class="bi bi-printer"></i> Print Report
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Transmitter chart
        const ctx1 = document.getElementById('transmitterChart').getContext('2d');
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($transmitterStats, 'transmitter_name')) ?>,
                datasets: [{
                    label: 'Total Faults',
                    data: <?= json_encode(array_column($transmitterStats, 'count')) ?>,
                    backgroundColor: '#0B2D4E'
                }, {
                    label: 'Fixed',
                    data: <?= json_encode(array_column($transmitterStats, 'fixed')) ?>,
                    backgroundColor: '#008C8C'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Trend chart
        const ctx2 = document.getElementById('trendChart').getContext('2d');
        new Chart(ctx2, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($monthlyTrends, 'month')) ?>,
                datasets: [{
                    label: 'Faults per Month',
                    data: <?= json_encode(array_column($monthlyTrends, 'count')) ?>,
                    borderColor: '#0B2D4E',
                    backgroundColor: 'rgba(11, 45, 78, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Common causes chart
        const ctx3 = document.getElementById('causeChart').getContext('2d');
        const causeLabels = <?= json_encode(array_column($commonCauses, 'root_cause')) ?>;
        const causeData = <?= json_encode(array_column($commonCauses, 'count')) ?>;

        new Chart(ctx3, {
            type: 'doughnut',
            data: {
                labels: causeLabels,
                datasets: [{
                    data: causeData,
                    backgroundColor: ['#0B2D4E', '#008C8C', '#E35229', '#f0b400', '#2d7d46',
                        '#1a4b73', '#00b3b3', '#f0643d', '#3a9d5a', '#6c757d'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>