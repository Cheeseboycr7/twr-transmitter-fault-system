<?php
require_once '../config/db.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$results = [];
$search = '';

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = sanitize($_GET['search']);

    // Improved search query
    $query = "
        SELECT 
            f.*, 
            t.transmitter_name, 
            s.solution, 
            s.root_cause, 
            s.repair_time,
            s.parts_replaced,
            u.fullname as fixed_by_name,
            (SELECT COUNT(*) FROM troubleshooting WHERE fault_id = f.id) as troubleshooting_count
        FROM faults f
        LEFT JOIN transmitters t ON f.transmitter_id = t.id
        LEFT JOIN solutions s ON f.id = s.fault_id
        LEFT JOIN users u ON s.fixed_by = u.id
        WHERE f.status = 'Fixed' 
        AND (
            f.fault_description LIKE ? 
            OR f.fault_no LIKE ?
            OR f.frequency LIKE ? 
            OR f.program_name LIKE ?
            OR t.transmitter_name LIKE ?
            OR s.solution LIKE ?
            OR s.root_cause LIKE ?
            OR s.parts_replaced LIKE ?
        )
        ORDER BY 
            CASE 
                WHEN f.fault_description LIKE ? THEN 1
                WHEN f.frequency LIKE ? THEN 2
                WHEN f.program_name LIKE ? THEN 3
                WHEN t.transmitter_name LIKE ? THEN 4
                ELSE 5
            END,
            f.date_reported DESC
    ";

    $searchTerm = "%$search%";
    $params = [
        $searchTerm, // fault_description
        $searchTerm, // fault_no
        $searchTerm, // frequency
        $searchTerm, // program_name
        $searchTerm, // transmitter_name
        $searchTerm, // solution
        $searchTerm, // root_cause
        $searchTerm, // parts_replaced
        $searchTerm, // fault_description (for ordering)
        $searchTerm, // frequency (for ordering)
        $searchTerm, // program_name (for ordering)
        $searchTerm  // transmitter_name (for ordering)
    ];

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll();

    // Get search statistics for feedback
    $totalResults = count($results);
    $exactMatches = array_filter($results, function ($r) use ($search) {
        return stripos($r['fault_description'], $search) !== false ||
            stripos($r['fault_no'], $search) !== false;
    });
}

// Get popular search suggestions
$stmt = $pdo->query("
    SELECT frequency, COUNT(*) as count 
    FROM faults 
    WHERE status = 'Fixed' AND frequency IS NOT NULL 
    GROUP BY frequency 
    ORDER BY count DESC 
    LIMIT 10
");
$popularFrequencies = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT transmitter_name, COUNT(*) as count 
    FROM faults f
    JOIN transmitters t ON f.transmitter_id = t.id
    WHERE f.status = 'Fixed'
    GROUP BY transmitter_name 
    ORDER BY count DESC 
    LIMIT 10
");
$popularTransmitters = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knowledge Base - Transmitter Fault System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/twr-theme.css">
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-4">
        <h2><i class="bi bi-search"></i> Knowledge Base Search</h2>
        <p class="text-muted">Search through all fixed faults and their solutions</p>

        <!-- Search Form -->
        <div class="card mt-4">
            <div class="card-body">
                <form method="GET">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-twr-navy text-white">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="search" class="form-control"
                            placeholder="Search by frequency, program, transmitter, fault description, or solution..."
                            value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="search.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-counterclockwise"></i> Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Search Results -->
        <?php if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['search']) && !empty(trim($_GET['search']))): ?>
            <div class="mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5>
                        Found <strong><?= $totalResults ?></strong> result<?= $totalResults != 1 ? 's' : '' ?>
                        for "<strong><?= htmlspecialchars($search) ?></strong>"
                    </h5>
                    <?php if ($totalResults > 0): ?>
                        <span class="text-muted">
                            <i class="bi bi-info-circle"></i>
                            <?= count($exactMatches) ?> exact match<?= count($exactMatches) != 1 ? 'es' : '' ?> found
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (empty($results)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        No results found for "<strong><?= htmlspecialchars($search) ?></strong>".
                        Try searching with different keywords or browse the suggestions below.
                    </div>
                <?php else: ?>
                    <?php foreach ($results as $result): ?>
                        <div class="card mt-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= htmlspecialchars($result['fault_no']) ?></strong>
                                    <span class="badge bg-secondary ms-2">
                                        <?= htmlspecialchars($result['transmitter_name'] ?? 'N/A') ?>
                                    </span>
                                    <span class="badge bg-info">
                                        <?= htmlspecialchars($result['frequency'] ?? 'N/A') ?>
                                    </span>
                                </div>
                                <span class="text-muted small">
                                    <i class="bi bi-calendar"></i> <?= formatDate($result['date_reported'], 'd-M-Y') ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <!-- Highlight matching text -->
                                <?php
                                $description = htmlspecialchars($result['fault_description']);
                                if (stripos($description, $search) !== false) {
                                    $description = preg_replace('/' . preg_quote($search, '/') . '/i', '<mark>$0</mark>', $description);
                                }
                                ?>
                                <p><strong>Problem:</strong> <?= $description ?></p>

                                <?php if ($result['root_cause']): ?>
                                    <?php
                                    $rootCause = htmlspecialchars($result['root_cause']);
                                    if (stripos($rootCause, $search) !== false) {
                                        $rootCause = preg_replace('/' . preg_quote($search, '/') . '/i', '<mark>$0</mark>', $rootCause);
                                    }
                                    ?>
                                    <p><strong>Cause:</strong> <?= nl2br($rootCause) ?></p>
                                <?php endif; ?>

                                <?php if ($result['solution']): ?>
                                    <?php
                                    $solution = htmlspecialchars($result['solution']);
                                    if (stripos($solution, $search) !== false) {
                                        $solution = preg_replace('/' . preg_quote($search, '/') . '/i', '<mark>$0</mark>', $solution);
                                    }
                                    ?>
                                    <p><strong>Solution:</strong> <?= nl2br($solution) ?></p>
                                <?php endif; ?>

                                <?php if ($result['parts_replaced']): ?>
                                    <p><strong>Parts Replaced:</strong> <?= nl2br(htmlspecialchars($result['parts_replaced'])) ?></p>
                                <?php endif; ?>

                                <?php if ($result['repair_time']): ?>
                                    <p><strong>Repair Time:</strong> <?= htmlspecialchars($result['repair_time']) ?></p>
                                <?php endif; ?>

                                <?php if ($result['fixed_by_name']): ?>
                                    <p><small class="text-muted">
                                            <i class="bi bi-person"></i> Fixed by: <?= htmlspecialchars($result['fixed_by_name']) ?>
                                            <?php if ($result['troubleshooting_count'] > 0): ?>
                                                | <i class="bi bi-tools"></i> <?= $result['troubleshooting_count'] ?> troubleshooting step<?= $result['troubleshooting_count'] != 1 ? 's' : '' ?>
                                            <?php endif; ?>
                                        </small></p>
                                <?php endif; ?>

                                <a href="../faults/details.php?id=<?= $result['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-file-text"></i> View Full Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Popular Searches / Suggestions -->
        <div class="mt-5">
            <h5><i class="bi bi-lightbulb"></i> Popular Search Suggestions</h5>
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">Frequencies</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($popularFrequencies as $freq): ?>
                                    <a href="?search=<?= urlencode($freq['frequency']) ?>" class="btn btn-outline-primary btn-sm">
                                        <?= htmlspecialchars($freq['frequency']) ?>
                                        <span class="badge bg-primary ms-1"><?= $freq['count'] ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">Transmitters</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($popularTransmitters as $tx): ?>
                                    <a href="?search=<?= urlencode($tx['transmitter_name']) ?>" class="btn btn-outline-success btn-sm">
                                        <?= htmlspecialchars($tx['transmitter_name']) ?>
                                        <span class="badge bg-success ms-1"><?= $tx['count'] ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">Quick Links</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="?search=power" class="btn btn-outline-danger btn-sm">Power Issues</a>
                                <a href="?search=audio" class="btn btn-outline-info btn-sm">Audio Problems</a>
                                <a href="?search=screen" class="btn btn-outline-warning btn-sm">Screen/Display</a>
                                <a href="?search=optimod" class="btn btn-outline-primary btn-sm">Optimod</a>
                                <a href="?search=exciter" class="btn btn-outline-success btn-sm">Exciter</a>
                                <a href="?search=vswr" class="btn btn-outline-secondary btn-sm">VSWR</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>