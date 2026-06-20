<?php
require_once '../config/db.php';

if (!isLoggedIn() || isOperator()) {
    if (isOperator()) {
        setFlash('error', 'Operators do not have permission to edit transmitters.');
    }
    redirect('list.php');
}

$id = $_GET['id'] ?? 0;

// Get transmitter details
$stmt = $pdo->prepare("SELECT * FROM transmitters WHERE id = ?");
$stmt->execute([$id]);
$transmitter = $stmt->fetch();

if (!$transmitter) {
    setFlash('error', 'Transmitter not found.');
    redirect('list.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $transmitter_name = sanitize($_POST['transmitter_name']);
    $frequency = sanitize($_POST['frequency']);
    $location = sanitize($_POST['location']);
    $manufacturer = sanitize($_POST['manufacturer']);
    $power_rating = sanitize($_POST['power_rating']);
    $status = sanitize($_POST['status']);

    if (empty($transmitter_name) || empty($frequency)) {
        $error = 'Transmitter name and frequency are required.';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE transmitters 
                SET transmitter_name = ?, frequency = ?, location = ?, manufacturer = ?, power_rating = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([$transmitter_name, $frequency, $location, $manufacturer, $power_rating, $status, $id]);

            logAction($_SESSION['user_id'], 'Edit Transmitter', "Edited transmitter: $transmitter_name");
            setFlash('success', "Transmitter '$transmitter_name' updated successfully!");
            redirect("list.php");
        } catch (PDOException $e) {
            $error = 'Failed to update transmitter. Please try again.';
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transmitter - Transmitter Fault System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/twr-theme.css">
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-4">
        <h2><i class="bi bi-pencil"></i> Edit Transmitter</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div class="card mt-4">
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Transmitter Name *</label>
                                <input type="text" name="transmitter_name" class="form-control"
                                    value="<?= htmlspecialchars($transmitter['transmitter_name']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Frequency *</label>
                                <input type="text" name="frequency" class="form-control"
                                    value="<?= htmlspecialchars($transmitter['frequency']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" name="location" class="form-control"
                                    value="<?= htmlspecialchars($transmitter['location'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Manufacturer</label>
                                <input type="text" name="manufacturer" class="form-control"
                                    value="<?= htmlspecialchars($transmitter['manufacturer'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Power Rating</label>
                                <input type="text" name="power_rating" class="form-control"
                                    value="<?= htmlspecialchars($transmitter['power_rating'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Operational" <?= $transmitter['status'] == 'Operational' ? 'selected' : '' ?>>Operational</option>
                                    <option value="Maintenance" <?= $transmitter['status'] == 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                    <option value="Offline" <?= $transmitter['status'] == 'Offline' ? 'selected' : '' ?>>Offline</option>
                                    <option value="Faulty" <?= $transmitter['status'] == 'Faulty' ? 'selected' : '' ?>>Faulty</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Transmitter
                        </button>
                        <a href="list.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Program Management Section -->
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-broadcast"></i> Programs for <?= htmlspecialchars($transmitter['transmitter_name']) ?></h5>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addProgramModal">
                    <i class="bi bi-plus-circle"></i> Add Program
                </button>
            </div>
            <div class="card-body">
                <?php
                // Get programs for this transmitter
                $stmt = $pdo->prepare("SELECT * FROM programs WHERE transmitter_id = ? ORDER BY program_name");
                $stmt->execute([$id]);
                $programs = $stmt->fetchAll();
                ?>

                <?php if (empty($programs)): ?>
                    <p class="text-muted text-center">No programs added yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Program Name</th>
                                    <th>Frequency</th>
                                    <th>Language</th>
                                    <th>Broadcast Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($programs as $program): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($program['program_name']) ?></td>
                                        <td><?= htmlspecialchars($program['frequency']) ?></td>
                                        <td><?= htmlspecialchars($program['language'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($program['broadcast_time'] ?? 'N/A') ?></td>
                                        <td><?= statusBadge($program['status']) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                                data-bs-target="#editProgramModal<?= $program['id'] ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="delete_program.php?id=<?= $program['id'] ?>&transmitter_id=<?= $id ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure you want to delete this program?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>

                                    <!-- Edit Program Modal -->
                                    <div class="modal fade" id="editProgramModal<?= $program['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="edit_program.php">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Program</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="program_id" value="<?= $program['id'] ?>">
                                                        <input type="hidden" name="transmitter_id" value="<?= $id ?>">

                                                        <div class="mb-3">
                                                            <label class="form-label">Program Name</label>
                                                            <input type="text" name="program_name" class="form-control"
                                                                value="<?= htmlspecialchars($program['program_name']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Frequency</label>
                                                            <input type="text" name="frequency" class="form-control"
                                                                value="<?= htmlspecialchars($program['frequency']) ?>">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Language</label>
                                                            <input type="text" name="language" class="form-control"
                                                                value="<?= htmlspecialchars($program['language'] ?? '') ?>">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Broadcast Time</label>
                                                            <input type="text" name="broadcast_time" class="form-control"
                                                                value="<?= htmlspecialchars($program['broadcast_time'] ?? '') ?>">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Status</label>
                                                            <select name="status" class="form-select">
                                                                <option value="Active" <?= $program['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                                                                <option value="Inactive" <?= $program['status'] == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                                                <option value="Scheduled" <?= $program['status'] == 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Program Modal -->
    <div class="modal fade" id="addProgramModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="add_program.php">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Program to <?= htmlspecialchars($transmitter['transmitter_name']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="transmitter_id" value="<?= $id ?>">

                        <div class="mb-3">
                            <label class="form-label">Program Name *</label>
                            <input type="text" name="program_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Frequency</label>
                            <input type="text" name="frequency" class="form-control"
                                placeholder="e.g., <?= htmlspecialchars($transmitter['frequency']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Language</label>
                            <input type="text" name="language" class="form-control"
                                placeholder="e.g., English, Afar, Tigrinya">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Broadcast Time</label>
                            <input type="text" name="broadcast_time" class="form-control"
                                placeholder="e.g., 06:00 - 09:00">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="Active">Active</option>
                                <option value="Scheduled">Scheduled</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Program</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>