<?php
require_once '../config/db.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

// Check if user has permission
if (isOperator()) {
    setFlash('error', 'Operators do not have permission to edit faults.');
    redirect('list.php');
}

$id = $_GET['id'] ?? 0;

// Get fault details
$stmt = $pdo->prepare("
    SELECT f.*, t.transmitter_name, u.fullname as reported_by_name 
    FROM faults f
    LEFT JOIN transmitters t ON f.transmitter_id = t.id
    LEFT JOIN users u ON f.reported_by = u.id
    WHERE f.id = ?
");
$stmt->execute([$id]);
$fault = $stmt->fetch();

if (!$fault) {
    setFlash('error', 'Fault not found.');
    redirect('list.php');
}

// Get transmitters for dropdown
$transmitters = $pdo->query("SELECT * FROM transmitters ORDER BY transmitter_name")->fetchAll();

// Get programs for the selected transmitter
$programs = [];
if ($fault['transmitter_id']) {
    $stmt = $pdo->prepare("SELECT id, program_name, frequency FROM programs WHERE transmitter_id = ? AND status = 'Active' ORDER BY program_name");
    $stmt->execute([$fault['transmitter_id']]);
    $programs = $stmt->fetchAll();
}

// Get users for assignment
$users = $pdo->query("SELECT id, fullname, role FROM users ORDER BY fullname")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $transmitter_id = !empty($_POST['transmitter_id']) ? $_POST['transmitter_id'] : null;
    $program_id = !empty($_POST['program_id']) ? $_POST['program_id'] : null;
    $program_name = sanitize($_POST['program_name'] ?? '');
    $frequency = sanitize($_POST['frequency'] ?? '');
    $fault_description = sanitize($_POST['fault_description']);
    $severity = $_POST['severity'] ?? 'Medium';
    $status = $_POST['status'] ?? 'Open';

    // Validate
    if (empty($fault_description)) {
        $error = 'Fault description is required.';
    } else {
        try {
            // If program is selected, get its details
            if ($program_id) {
                $stmt = $pdo->prepare("SELECT program_name, frequency FROM programs WHERE id = ?");
                $stmt->execute([$program_id]);
                $program = $stmt->fetch();
                if ($program) {
                    $program_name = $program['program_name'];
                    $frequency = $program['frequency'];
                }
            }

            // If transmitter is selected but no program, get transmitter frequency
            if ($transmitter_id && empty($frequency)) {
                $stmt = $pdo->prepare("SELECT frequency FROM transmitters WHERE id = ?");
                $stmt->execute([$transmitter_id]);
                $trans = $stmt->fetch();
                if ($trans) {
                    $frequency = $trans['frequency'];
                }
            }

            // Update fault
            $stmt = $pdo->prepare("
                UPDATE faults 
                SET transmitter_id = ?, 
                    program_id = ?, 
                    program_name = ?, 
                    frequency = ?, 
                    fault_description = ?, 
                    severity = ?, 
                    status = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $transmitter_id,
                $program_id,
                $program_name,
                $frequency,
                $fault_description,
                $severity,
                $status,
                $id
            ]);

            logAction($_SESSION['user_id'], 'Edit Fault', "Edited fault {$fault['fault_no']}");
            setFlash('success', "Fault {$fault['fault_no']} updated successfully!");
            redirect("details.php?id=$id");
        } catch (PDOException $e) {
            $error = 'Failed to update fault. Please try again.';
            error_log("Fault update error: " . $e->getMessage());
        }
    }
}

// Get troubleshooting steps
$troubleshooting = $pdo->prepare("
    SELECT t.*, u.fullname as recorded_by_name 
    FROM troubleshooting t
    LEFT JOIN users u ON t.recorded_by = u.id
    WHERE t.fault_id = ? 
    ORDER BY t.date_recorded DESC
");
$troubleshooting->execute([$id]);
$troubleshooting_steps = $troubleshooting->fetchAll();

// Get solution if exists
$stmt = $pdo->prepare("
    SELECT s.*, u.fullname as fixed_by_name 
    FROM solutions s
    LEFT JOIN users u ON s.fixed_by = u.id
    WHERE s.fault_id = ?
");
$stmt->execute([$id]);
$solution = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Fault - Transmitter Fault System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/twr-theme.css">
    <style>
        .fault-header {
            background: var(--twr-light-bg);
            padding: 1rem;
            border-radius: var(--twr-radius-sm);
            margin-bottom: 1.5rem;
        }

        .fault-header .fault-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--twr-navy);
        }

        .fault-header .meta-info {
            color: var(--twr-gray-600);
            font-size: 0.9rem;
        }

        .troubleshooting-step {
            border-left: 3px solid var(--twr-teal);
            padding-left: 1rem;
            margin-bottom: 1rem;
        }

        .troubleshooting-step .step-header {
            font-weight: 600;
            color: var(--twr-navy);
        }

        .solution-box {
            background: rgba(0, 140, 140, 0.05);
            border: 1px solid var(--twr-teal);
            border-radius: var(--twr-radius-sm);
            padding: 1rem;
        }

        .solution-box .label {
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

        .status-badge-large {
            font-size: 1rem;
            padding: 0.5rem 1rem;
        }

        .program-group {
            background: var(--twr-light-bg);
            padding: 1rem;
            border-radius: var(--twr-radius-sm);
            margin-top: 0.5rem;
        }
    </style>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-pencil"></i> Edit Fault</h2>
            <div>
                <a href="details.php?id=<?= $id ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Details
                </a>
            </div>
        </div>

        <!-- Fault Header -->
        <div class="fault-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <span class="fault-number"><?= htmlspecialchars($fault['fault_no']) ?></span>
                    <span class="badge bg-<?= $fault['severity'] == 'Critical' ? 'danger' : ($fault['severity'] == 'Medium' ? 'warning' : 'info') ?> ms-2">
                        <?= $fault['severity'] ?>
                    </span>
                    <?= statusBadge($fault['status']) ?>
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="meta-info">
                        <i class="bi bi-calendar"></i> Reported: <?= formatDate($fault['date_reported']) ?> |
                        <i class="bi bi-person"></i> <?= htmlspecialchars($fault['reported_by_name'] ?? 'Unknown') ?>
                    </span>
                </div>
            </div>
        </div>

        <?php displayFlash(); ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Edit Form -->
        <div class="card">
            <div class="card-body">
                <form method="POST" id="editFaultForm">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="form-section-title"><i class="bi bi-info-circle"></i> Fault Information</h6>

                            <!-- Transmitter Selection -->
                            <div class="mb-3">
                                <label class="form-label">Transmitter</label>
                                <select name="transmitter_id" id="transmitterSelect" class="form-select">
                                    <option value="">Select Transmitter (Optional)</option>
                                    <?php foreach ($transmitters as $t): ?>
                                        <option value="<?= $t['id'] ?>" <?= $fault['transmitter_id'] == $t['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($t['transmitter_name']) ?> - <?= htmlspecialchars($t['frequency']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-hint">
                                    <i class="bi bi-info-circle"></i>
                                    Change transmitter to update available programs
                                </div>
                            </div>

                            <!-- Program Selection -->
                            <div class="mb-3" id="programSection">
                                <label class="form-label">Program</label>
                                <select name="program_id" id="programSelect" class="form-select">
                                    <option value="">Select Program (Optional)</option>
                                    <?php if ($programs): ?>
                                        <?php foreach ($programs as $p): ?>
                                            <option value="<?= $p['id'] ?>" <?= $fault['program_id'] == $p['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($p['program_name']) ?> - <?= htmlspecialchars($p['frequency']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Manual Entry -->
                            <div class="program-group mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label class="form-label mb-0">
                                        <i class="bi bi-pencil"></i> Manual Entry
                                    </label>
                                    <small class="text-muted">(If program not listed)</small>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <input type="text" name="program_name" class="form-control"
                                            placeholder="Program Name"
                                            value="<?= htmlspecialchars($fault['program_name'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" name="frequency" class="form-control"
                                            placeholder="Frequency"
                                            value="<?= htmlspecialchars($fault['frequency']) ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Severity -->
                            <div class="mb-3">
                                <label class="form-label">Severity</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="severity" value="Low"
                                            id="severityLow" <?= $fault['severity'] == 'Low' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="severityLow">
                                            <span class="badge bg-info">Low</span>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="severity" value="Medium"
                                            id="severityMedium" <?= $fault['severity'] == 'Medium' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="severityMedium">
                                            <span class="badge bg-warning text-dark">Medium</span>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="severity" value="Critical"
                                            id="severityCritical" <?= $fault['severity'] == 'Critical' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="severityCritical">
                                            <span class="badge bg-danger">Critical</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Open" <?= $fault['status'] == 'Open' ? 'selected' : '' ?>>Open</option>
                                    <option value="In Progress" <?= $fault['status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="Fixed" <?= $fault['status'] == 'Fixed' ? 'selected' : '' ?>>Fixed</option>
                                    <option value="Closed" <?= $fault['status'] == 'Closed' ? 'selected' : '' ?>>Closed</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6 class="form-section-title"><i class="bi bi-file-text"></i> Description</h6>

                            <!-- Fault Description -->
                            <div class="mb-3">
                                <label class="form-label">Fault Description *</label>
                                <textarea name="fault_description" class="form-control" rows="6" required
                                    placeholder="Describe the fault in detail..."><?= htmlspecialchars($fault['fault_description']) ?></textarea>
                            </div>

                            <?php if ($fault['status'] == 'Fixed' && $solution): ?>
                                <!-- Solution Summary (Read-only) -->
                                <div class="solution-box mt-3">
                                    <h6 class="text-success"><i class="bi bi-check-circle"></i> Solution Summary</h6>
                                    <p><span class="label">Root Cause:</span><br><?= nl2br(htmlspecialchars($solution['root_cause'])) ?></p>
                                    <p><span class="label">Solution:</span><br><?= nl2br(htmlspecialchars($solution['solution'])) ?></p>
                                    <p><span class="label">Repair Time:</span> <?= htmlspecialchars($solution['repair_time']) ?></p>
                                    <p class="mb-0"><span class="label">Fixed By:</span> <?= htmlspecialchars($solution['fixed_by_name'] ?? 'Unknown') ?></p>
                                    <small class="text-muted">Fixed on: <?= formatDate($solution['date_fixed']) ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Fault
                        </button>
                        <a href="details.php?id=<?= $id ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancel
                        </a>
                        <?php if ($fault['status'] != 'Fixed' && !isOperator()): ?>
                            <a href="solutions/add.php?fault_id=<?= $id ?>" class="btn btn-success ms-auto">
                                <i class="bi bi-check-circle"></i> Mark as Fixed
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Troubleshooting History -->
        <?php if ($troubleshooting_steps): ?>
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-tools"></i> Troubleshooting History</h5>
                    <?php if ($fault['status'] != 'Fixed' && !isOperator()): ?>
                        <a href="troubleshooting/add.php?fault_id=<?= $id ?>" class="btn btn-sm btn-warning">
                            <i class="bi bi-plus-circle"></i> Add Step
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php foreach ($troubleshooting_steps as $step): ?>
                        <div class="troubleshooting-step">
                            <div class="step-header">
                                <i class="bi bi-clock"></i> <?= formatDate($step['date_recorded']) ?>
                                <span class="text-muted">by <?= htmlspecialchars($step['recorded_by_name'] ?? 'Unknown') ?></span>
                            </div>
                            <p><strong>Observation:</strong><br><?= nl2br(htmlspecialchars($step['observation'])) ?></p>
                            <p><strong>Actions:</strong><br><?= nl2br(htmlspecialchars($step['actions_taken'])) ?></p>
                            <?php if ($step['measurement']): ?>
                                <p><strong>Measurements:</strong><br><?= nl2br(htmlspecialchars($step['measurement'])) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-populate programs when transmitter is selected
        document.getElementById('transmitterSelect').addEventListener('change', function() {
            const transmitterId = this.value;
            const programSelect = document.getElementById('programSelect');

            if (transmitterId) {
                // Show loading state
                programSelect.innerHTML = '<option value="">Loading programs...</option>';

                // Fetch programs via AJAX
                fetch(`get_programs.php?transmitter_id=${transmitterId}`)
                    .then(response => response.json())
                    .then(data => {
                        programSelect.innerHTML = '<option value="">Select Program (Optional)</option>';

                        if (data.length > 0) {
                            data.forEach(program => {
                                const option = document.createElement('option');
                                option.value = program.id;
                                option.textContent = `${program.program_name} - ${program.frequency}`;
                                programSelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        programSelect.innerHTML = '<option value="">Error loading programs</option>';
                    });
            } else {
                programSelect.innerHTML = '<option value="">Select Program (Optional)</option>';
            }
        });

        // Auto-fill frequency when program is selected
        document.getElementById('programSelect').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const frequencyInput = document.querySelector('input[name="frequency"]');
                const frequencyMatch = selectedOption.text.match(/- (.+)$/);
                if (frequencyMatch) {
                    frequencyInput.value = frequencyMatch[1].trim();
                }
            }
        });

        // Form validation
        document.getElementById('editFaultForm').addEventListener('submit', function(e) {
            const description = document.querySelector('textarea[name="fault_description"]');

            if (!description.value.trim()) {
                e.preventDefault();
                alert('Please enter a fault description.');
                description.focus();
                return false;
            }
        });

        // Show warning when changing status from Fixed
        document.querySelector('select[name="status"]').addEventListener('change', function() {
            const currentStatus = '<?= $fault['status'] ?>';
            if (currentStatus === 'Fixed' && this.value !== 'Fixed') {
                if (!confirm('This fault is marked as Fixed. Changing status will remove it from the Fixed list. Continue?')) {
                    this.value = 'Fixed';
                }
            }
        });
    </script>
</body>

</html>