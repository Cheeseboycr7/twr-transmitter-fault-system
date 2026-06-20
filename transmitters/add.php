<?php
require_once '../config/db.php';

if (!isLoggedIn() || isOperator()) {
    if (isOperator()) {
        setFlash('error', 'Operators do not have permission to add transmitters.');
    }
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

    // Validate
    if (empty($transmitter_name) || empty($frequency)) {
        $error = 'Transmitter name and frequency are required.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO transmitters (transmitter_name, frequency, location, manufacturer, power_rating, status) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$transmitter_name, $frequency, $location, $manufacturer, $power_rating, $status]);
            $transmitter_id = $pdo->lastInsertId();

            logAction($_SESSION['user_id'], 'Add Transmitter', "Added transmitter: $transmitter_name");
            setFlash('success', "Transmitter '$transmitter_name' added successfully!");
            redirect("list.php");
        } catch (PDOException $e) {
            $error = 'Failed to add transmitter. Please try again.';
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
    <title>Add Transmitter - Transmitter Fault System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/twr-theme.css">
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-4">
        <h2><i class="bi bi-plus-circle"></i> Add New Transmitter</h2>

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
                                    placeholder="e.g., TX1, Main Transmitter" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Frequency *</label>
                                <input type="text" name="frequency" class="form-control"
                                    placeholder="e.g., 101.5 MHz, 15105 kHz" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" name="location" class="form-control"
                                    placeholder="e.g., Studio A, Tower 1">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Manufacturer</label>
                                <input type="text" name="manufacturer" class="form-control"
                                    placeholder="e.g., Harris, Nautel, Continental">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Power Rating</label>
                                <input type="text" name="power_rating" class="form-control"
                                    placeholder="e.g., 100 kW, 50 kW">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Operational">Operational</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Offline">Offline</option>
                                    <option value="Faulty">Faulty</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Transmitter
                        </button>
                        <a href="list.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>