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

// Get transmitters for dropdown
$transmitters = $pdo->query("SELECT * FROM transmitters ORDER BY transmitter_name")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $transmitter_id = !empty($_POST['transmitter_id']) ? $_POST['transmitter_id'] : null;
    $maintenance_type = sanitize($_POST['maintenance_type']);
    $description = sanitize($_POST['description']);
    $date_done = sanitize($_POST['date_done']);
    $engineer = sanitize($_POST['engineer']);
    $next_maintenance_date = !empty($_POST['next_maintenance_date']) ? sanitize($_POST['next_maintenance_date']) : null;
    $status = sanitize($_POST['status']);

    // Validate
    if (empty($maintenance_type) || empty($description) || empty($date_done) || empty($engineer)) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO maintenance (
                    transmitter_id, 
                    maintenance_type, 
                    description, 
                    date_done, 
                    engineer, 
                    next_maintenance_date, 
                    status
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $transmitter_id,
                $maintenance_type,
                $description,
                $date_done,
                $engineer,
                $next_maintenance_date,
                $status
            ]);

            $maintenance_id = $pdo->lastInsertId();

            // Update transmitter status if maintenance is completed
            if ($transmitter_id && $status == 'Completed') {
                $stmt = $pdo->prepare("UPDATE transmitters SET status = 'Operational' WHERE id = ?");
                $stmt->execute([$transmitter_id]);
            }

            logAction($_SESSION['user_id'], 'Add Maintenance', "Added maintenance record ID: $maintenance_id");
            setFlash('success', 'Maintenance record added successfully!');
            redirect('list.php');
        } catch (PDOException $e) {
            $error = 'Failed to add maintenance record. Please try again.';
            error_log("Maintenance add error: " . $e->getMessage());
        }
    }
}

// Get current date for default
$today = date('Y-m-d');
$next_month = date('Y-m-d', strtotime('+1 month'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Maintenance - Transmitter Fault System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/twr-theme.css">
    <style>
        .maintenance-type-card {
            cursor: pointer;
            transition: var(--twr-transition);
            border: 2px solid var(--twr-gray-200);
            padding: 1rem;
            border-radius: var(--twr-radius-sm);
            text-align: center;
            height: 100%;
        }

        .maintenance-type-card:hover {
            border-color: var(--twr-teal);
            transform: translateY(-2px);
            box-shadow: var(--twr-shadow-sm);
        }

        .maintenance-type-card.selected {
            border-color: var(--twr-teal);
            background-color: rgba(0, 140, 140, 0.05);
        }

        .maintenance-type-card .icon {
            font-size: 2rem;
            display: block;
            margin-bottom: 0.5rem;
        }

        .maintenance-type-card .type-name {
            font-weight: 600;
            color: var(--twr-navy);
        }

        .form-section-title {
            font-weight: 600;
            color: var(--twr-navy);
            border-bottom: 2px solid var(--twr-light-bg);
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }

        .schedule-info {
            background: var(--twr-light-bg);
            padding: 1rem;
            border-radius: var(--twr-radius-sm);
        }
    </style>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-plus-circle"></i> Add Maintenance Record</h2>
            <a href="list.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>

        <?php displayFlash(); ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST" id="maintenanceForm">
                    <!-- Transmitter Selection -->
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="form-section-title"><i class="bi bi-radio"></i> Equipment Information</h6>

                            <div class="mb-3">
                                <label class="form-label">Transmitter</label>
                                <select name="transmitter_id" class="form-select">
                                    <option value="">Select Transmitter (Optional)</option>
                                    <?php foreach ($transmitters as $t): ?>
                                        <option value="<?= $t['id'] ?>" <?= ($_POST['transmitter_id'] ?? '') == $t['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($t['transmitter_name']) ?> - <?= htmlspecialchars($t['frequency']) ?>
                                            (<?= htmlspecialchars($t['status']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Engineer Name *</label>
                                <input type="text" name="engineer" class="form-control"
                                    placeholder="Enter engineer name"
                                    value="<?= htmlspecialchars($_POST['engineer'] ?? $_SESSION['fullname']) ?>" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6 class="form-section-title"><i class="bi bi-calendar"></i> Schedule Information</h6>

                            <div class="mb-3">
                                <label class="form-label">Date Done *</label>
                                <input type="date" name="date_done" class="form-control"
                                    value="<?= htmlspecialchars($_POST['date_done'] ?? $today) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Next Maintenance Date</label>
                                <input type="date" name="next_maintenance_date" class="form-control"
                                    value="<?= htmlspecialchars($_POST['next_maintenance_date'] ?? $next_month) ?>">
                                <div class="form-hint">
                                    <i class="bi bi-info-circle"></i>
                                    Suggested: <?= date('d-M-Y', strtotime('+1 month')) ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Scheduled" <?= ($_POST['status'] ?? '') == 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                    <option value="In Progress" <?= ($_POST['status'] ?? '') == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="Completed" <?= ($_POST['status'] ?? '') == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="Overdue" <?= ($_POST['status'] ?? '') == 'Overdue' ? 'selected' : '' ?>>Overdue</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Maintenance Type -->
                    <h6 class="form-section-title mt-3"><i class="bi bi-tools"></i> Maintenance Type</h6>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="maintenance-type-card" data-type="Preventive">
                                <span class="icon">🔧</span>
                                <span class="type-name">Preventive</span>
                                <small class="text-muted d-block">Scheduled maintenance</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="maintenance-type-card" data-type="Corrective">
                                <span class="icon">🔨</span>
                                <span class="type-name">Corrective</span>
                                <small class="text-muted d-block">Fix existing issues</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="maintenance-type-card" data-type="Predictive">
                                <span class="icon">📊</span>
                                <span class="type-name">Predictive</span>
                                <small class="text-muted d-block">Condition monitoring</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="maintenance-type-card" data-type="Emergency">
                                <span class="icon">🚨</span>
                                <span class="type-name">Emergency</span>
                                <small class="text-muted d-block">Urgent repairs</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Maintenance Type *</label>
                        <input type="text" name="maintenance_type" id="maintenanceTypeInput" class="form-control"
                            placeholder="Select from above or type manually"
                            value="<?= htmlspecialchars($_POST['maintenance_type'] ?? '') ?>" required>
                        <div class="form-hint">
                            <i class="bi bi-info-circle"></i>
                            Click on a card above to auto-fill or type manually
                        </div>
                    </div>

                    <!-- Description -->
                    <h6 class="form-section-title mt-3"><i class="bi bi-file-text"></i> Details</h6>

                    <div class="mb-3">
                        <label class="form-label">Description *</label>
                        <textarea name="description" class="form-control" rows="5" required
                            placeholder="Describe the maintenance performed..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <!-- Quick Templates -->
                    <div class="mb-3">
                        <label class="form-label">Quick Templates</label>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm template-btn" data-template="PLC Backup - Performed full PLC program backup and verified checksum. All modules operating within normal parameters.">
                                <i class="bi bi-database"></i> PLC Backup
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm template-btn" data-template="Generator Test - Performed weekly generator test under full load. Fuel level 78%, oil pressure normal, temperature within range. Transfer switch tested OK.">
                                <i class="bi bi-lightning"></i> Generator Test
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm template-btn" data-template="Filter Cleaning - Cleaned all air filters and cooling fans. Removed dust accumulation from heat sinks. Air flow restored to normal levels.">
                                <i class="bi bi-fan"></i> Filter Cleaning
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm template-btn" data-template="UPS Check - Performed UPS battery test. All batteries showing nominal voltage. Load capacity at 65%. No alarms or warnings.">
                                <i class="bi bi-battery"></i> UPS Check
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm template-btn" data-template="Cooling System - Inspected cooling system. Coolant level normal, no leaks detected. Pump operating at normal pressure (85 PSI). Temperature maintained at 42°C.">
                                <i class="bi bi-water"></i> Cooling System
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm template-btn" data-template="Software Update - Applied latest firmware update to transmitter controller. Verified all settings preserved. System stability improved.">
                                <i class="bi bi-code"></i> Software Update
                            </button>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Maintenance Record
                        </button>
                        <a href="list.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick Tips -->
        <div class="card mt-4">
            <div class="card-body">
                <h6><i class="bi bi-lightbulb text-twr-accent"></i> Maintenance Tips</h6>
                <div class="row">
                    <div class="col-md-4">
                        <ul class="text-muted mb-0">
                            <li><strong>Preventive:</strong> Regular scheduled maintenance to prevent failures</li>
                            <li><strong>Corrective:</strong> Fixing issues that have already occurred</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <ul class="text-muted mb-0">
                            <li><strong>Predictive:</strong> Monitoring condition to predict failures</li>
                            <li><strong>Emergency:</strong> Urgent repairs for critical failures</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <ul class="text-muted mb-0">
                            <li><strong>Next Maintenance:</strong> Set reminder for future maintenance</li>
                            <li><strong>Status:</strong> Track progress from Scheduled to Completed</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Maintenance type selection
        document.querySelectorAll('.maintenance-type-card').forEach(card => {
            card.addEventListener('click', function() {
                // Remove selected class from all cards
                document.querySelectorAll('.maintenance-type-card').forEach(c => {
                    c.classList.remove('selected');
                });

                // Add selected class to clicked card
                this.classList.add('selected');

                // Set the maintenance type input
                const type = this.dataset.type;
                document.getElementById('maintenanceTypeInput').value = type;
            });
        });

        // Template buttons
        document.querySelectorAll('.template-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const template = this.dataset.template;
                const textarea = document.querySelector('textarea[name="description"]');

                // Add template to textarea
                if (textarea.value.trim() && !confirm('This will replace the current description. Continue?')) {
                    return;
                }

                textarea.value = template;

                // Show feedback
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="bi bi-check-circle"></i> Applied!';
                this.classList.remove('btn-outline-secondary');
                this.classList.add('btn-success');

                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.classList.remove('btn-success');
                    this.classList.add('btn-outline-secondary');
                }, 2000);
            });
        });

        // Form validation
        document.getElementById('maintenanceForm').addEventListener('submit', function(e) {
            const type = document.getElementById('maintenanceTypeInput');
            const description = document.querySelector('textarea[name="description"]');
            const dateDone = document.querySelector('input[name="date_done"]');
            const engineer = document.querySelector('input[name="engineer"]');

            if (!type.value.trim()) {
                e.preventDefault();
                alert('Please select or enter a maintenance type.');
                type.focus();
                return false;
            }

            if (!description.value.trim()) {
                e.preventDefault();
                alert('Please enter a description.');
                description.focus();
                return false;
            }

            if (!dateDone.value) {
                e.preventDefault();
                alert('Please select the date maintenance was performed.');
                dateDone.focus();
                return false;
            }

            if (!engineer.value.trim()) {
                e.preventDefault();
                alert('Please enter the engineer name.');
                engineer.focus();
                return false;
            }
        });

        // Auto-set next maintenance date when status is completed
        document.querySelector('select[name="status"]').addEventListener('change', function() {
            if (this.value === 'Completed') {
                const nextDate = document.querySelector('input[name="next_maintenance_date"]');
                if (!nextDate.value) {
                    const date = new Date();
                    date.setMonth(date.getMonth() + 1);
                    nextDate.value = date.toISOString().split('T')[0];
                }
            }
        });

        // Update transmitter status hint
        document.querySelector('select[name="transmitter_id"]').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const statusText = selectedOption.text.match(/\(([^)]+)\)/);
            if (statusText) {
                console.log('Selected transmitter status:', statusText[1]);
            }
        });
    </script>
</body>

</html>