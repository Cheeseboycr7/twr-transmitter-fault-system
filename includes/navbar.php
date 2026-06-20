<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="../dashboard.php">
            <i class="bi bi-broadcast"></i> BTFMS
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>"
                        href="../dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="faultsDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-exclamation-triangle"></i> Faults
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../faults/add.php"><i class="bi bi-plus-circle"></i> Record Fault</a></li>
                        <li><a class="dropdown-item" href="../faults/list.php"><i class="bi bi-list"></i> View All Faults</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../transmitters/list.php">
                        <i class="bi bi-radio"></i> Transmitters
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../maintenance/list.php">
                        <i class="bi bi-tools"></i> Maintenance
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../knowledge_base/search.php">
                        <i class="bi bi-search"></i> Knowledge Base
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../reports/index.php">
                        <i class="bi bi-file-text"></i> Reports
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= $_SESSION['fullname'] ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text">Role: <?= $_SESSION['role'] ?></span></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="../profile.php"><i class="bi bi-gear"></i> Profile</a></li>
                        <li><a class="dropdown-item text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>