<?php
require_once '../config/db.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

// Check if operator has permission
if (isOperator()) {
    setFlash('error', 'Operators do not have permission to record faults.');
    redirect('list.php');
}

// Get transmitters for dropdown
$transmitters = $pdo->query("SELECT * FROM transmitters WHERE status = 'Operational' ORDER BY transmitter_name")->fetchAll();
$users = $pdo->query("SELECT id, fullname FROM users ORDER BY fullname")->fetchAll();

// Get programs for AJAX population
$programs = [];
if (!empty($_POST['transmitter_id'])) {
    $stmt = $pdo->prepare("SELECT id, program_name, frequency FROM programs WHERE transmitter_id = ? AND status = 'Active' ORDER BY program_name");
    $stmt->execute([$_POST['transmitter_id']]);
    $programs = $stmt->fetchAll();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $fault_no = generateFaultNo();
    $transmitter_id = !empty($_POST['transmitter_id']) ? $_POST['transmitter_id'] : null;
    $program_id = !empty($_POST['program_id']) ? $_POST['program_id'] : null;
    $program_name = sanitize($_POST['program_name'] ?? '');
    $frequency = sanitize($_POST['frequency'] ?? '');
    $fault_description = sanitize($_POST['fault_description']);
    $severity = $_POST['severity'] ?? 'Medium';
    $reported_by = $_SESSION['user_id'];

    // Validate
    if (empty($fault_description)) {
        $error = 'Fault description is required.';
    } elseif (empty($transmitter_id) && empty($program_name) && empty($frequency)) {
        $error = 'Please select a transmitter or enter program name and frequency.';
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

            // Insert fault
            $stmt = $pdo->prepare("
                INSERT INTO faults (
                    fault_no, 
                    transmitter_id, 
                    program_id, 
                    program_name, 
                    frequency, 
                    fault_description, 
                    severity, 
                    reported_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $fault_no,
                $transmitter_id,
                $program_id,
                $program_name,
                $frequency,
                $fault_description,
                $severity,
                $reported_by
            ]);

            $fault_id = $pdo->lastInsertId();
            logAction($reported_by, 'Add Fault', "Added fault $fault_no");

            setFlash('success', "Fault recorded successfully! Fault Number: $fault_no");
            redirect('list.php');
        } catch (PDOException $e) {
            $error = 'Failed to record fault. Please try again.';
            error_log("Fault recording error: " . $e->getMessage());
        }
    }
}

// Get programs for selected transmitter (if any from previous POST)
$selected_transmitter = $_POST['transmitter_id'] ?? '';
$programs = [];
if ($selected_transmitter) {
    $stmt = $pdo->prepare("SELECT id, program_name, frequency FROM programs WHERE transmitter_id = ? AND status = 'Active' ORDER BY program_name");
    $stmt->execute([$selected_transmitter]);
    $programs = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Fault - Transmitter Fault System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/twr-theme.css">
    <style>
        .program-group {
            background: var(--twr-light-bg);
            padding: 1rem;
            border-radius: var(--twr-radius-sm);
            margin-top: 0.5rem;
        }

        .program-group label {
            font-weight: 500;
            color: var(--twr-navy);
        }

        .loading-spinner {
            display: none;
            margin-left: 0.5rem;
        }

        .loading-spinner.show {
            display: inline-block;
        }

        .form-hint {
            font-size: 0.8rem;
            color: var(--twr-gray-600);
            margin-top: 0.2rem;
        }
    </style>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-4">
        <h2><i class="bi bi-plus-circle"></i> Record New Fault</h2>

        <?php displayFlash(); ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card mt-4">
            <div class="card-body">
                <form method="POST" id="faultForm">
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Transmitter Selection -->
                            <div class="mb-3">
                                <label class="form-label">Transmitter</label>
                                <select name="transmitter_id" id="transmitterSelect" class="form-select">
                                    <option value="">Select Transmitter (Optional)</option>
                                    <?php foreach ($transmitters as $t): ?>
                                        <option value="<?= $t['id'] ?>" <?= $selected_transmitter == $t['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($t['transmitter_name']) ?> - <?= htmlspecialchars($t['frequency']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-hint">
                                    <i class="bi bi-info-circle"></i>
                                    Select a transmitter to auto-populate frequency and programs
                                </div>
                            </div>

                            <!-- Program Selection -->
                            <div class="mb-3" id="programSection">
                                <label class="form-label">Program</label>
                                <select name="program_id" id="programSelect" class="form-select">
                                    <option value="">Select Program (Optional)</option>
                                    <?php if ($selected_transmitter && $programs): ?>
                                        <?php foreach ($programs as $p): ?>
                                            <option value="<?= $p['id'] ?>" <?= ($_POST['program_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($p['program_name']) ?> - <?= htmlspecialchars($p['frequency']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="form-hint" id="programHint">
                                    <i class="bi bi-info-circle"></i>
                                    <?php if ($selected_transmitter && empty($programs)): ?>
                                        No active programs found for this transmitter.
                                    <?php else: ?>
                                        Select a program or enter manually below
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Manual Entry Section -->
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
                                            value="<?= htmlspecialchars($_POST['program_name'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" name="frequency" class="form-control"
                                            placeholder="Frequency"
                                            value="<?= htmlspecialchars($_POST['frequency'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <!-- Fault Description -->
                            <div class="mb-3">
                                <label class="form-label">Fault Description *</label>
                                <textarea name="fault_description" class="form-control" rows="4" required
                                    placeholder="Describe the fault in detail..."><?= htmlspecialchars($_POST['fault_description'] ?? '') ?></textarea>
                                <div class="form-hint">
                                    <i class="bi bi-info-circle"></i>
                                    Be specific about what you observed
                                </div>
                            </div>

                            <!-- Severity -->
                            <div class="mb-3">
                                <label class="form-label">Severity *</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="severity" value="Low"
                                            id="severityLow" <?= ($_POST['severity'] ?? '') == 'Low' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="severityLow">
                                            <span class="badge bg-info">Low</span>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="severity" value="Medium"
                                            id="severityMedium" <?= ($_POST['severity'] ?? 'Medium') == 'Medium' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="severityMedium">
                                            <span class="badge bg-warning text-dark">Medium</span>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="severity" value="Critical"
                                            id="severityCritical" <?= ($_POST['severity'] ?? '') == 'Critical' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="severityCritical">
                                            <span class="badge bg-danger">Critical</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Reported By (hidden) -->
                            <input type="hidden" name="reported_by" value="<?= $_SESSION['user_id'] ?>">
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Record Fault
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
                <h6><i class="bi bi-lightbulb text-twr-accent"></i> Quick Tips</h6>
                <ul class="text-muted mb-0">
                    <li>Select a transmitter to auto-fill program options</li>
                    <li>Choose a program from the dropdown or enter manually</li>
                    <li>Provide detailed fault description for better troubleshooting</li>
                    <li>Critical faults require immediate attention</li>
                </ul>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-populate programs when transmitter is selected
        document.getElementById('transmitterSelect').addEventListener('change', function() {
            const transmitterId = this.value;
            const programSelect = document.getElementById('programSelect');
            const programHint = document.getElementById('programHint');

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
                            programHint.innerHTML = '<i class="bi bi-check-circle text-success"></i> Programs loaded successfully';
                        } else {
                            programHint.innerHTML = '<i class="bi bi-info-circle"></i> No active programs found';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        programSelect.innerHTML = '<option value="">Error loading programs</option>';
                        programHint.innerHTML = '<i class="bi bi-exclamation-triangle text-danger"></i> Could not load programs';
                    });
            } else {
                programSelect.innerHTML = '<option value="">Select Program (Optional)</option>';
                programHint.innerHTML = '<i class="bi bi-info-circle"></i> Select a transmitter to see programs';
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
        document.getElementById('faultForm').addEventListener('submit', function(e) {
            const description = document.querySelector('textarea[name="fault_description"]');
            const transmitter = document.getElementById('transmitterSelect');
            const programName = document.querySelector('input[name="program_name"]');
            const frequency = document.querySelector('input[name="frequency"]');

            if (!description.value.trim()) {
                e.preventDefault();
                alert('Please enter a fault description.');
                description.focus();
                return false;
            }

            // Check if either transmitter is selected OR manual entry is filled
            if (!transmitter.value && !programName.value.trim() && !frequency.value.trim()) {
                e.preventDefault();
                alert('Please select a transmitter or enter program name and frequency.');
                return false;
            }
        });

        // Clear program name when transmitter is selected
        document.getElementById('transmitterSelect').addEventListener('change', function() {
            if (this.value) {
                document.querySelector('input[name="program_name"]').value = '';
            }
        });
    </script>
</body>

</html>