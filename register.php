<?php
// register.php - User Registration
require_once 'config/db.php';

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = sanitize($_POST['fullname']);
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'] ?? 'Technician';

    // Validation
    if (empty($fullname) || empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'Username already taken. Please choose another.';
        } else {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already registered. Please use another email.';
            } else {
                // Hash password and insert user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (fullname, username, email, password, role) 
                    VALUES (?, ?, ?, ?, ?)
                ");

                try {
                    $stmt->execute([$fullname, $username, $email, $hashed_password, $role]);
                    $success = 'Registration successful! You can now <a href="login.php">login</a>.';

                    // Log the registration
                    logAction(0, 'User Registration', "New user registered: $username");

                    // Clear form fields
                    $_POST = [];
                } catch (PDOException $e) {
                    $error = 'Registration failed. Please try again.';
                    error_log($e->getMessage());
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - TWR Fault Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/twr-theme.css">
    <style>
        /* Register page specific styles */
        .register-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--twr-navy) 0%, var(--twr-navy-light) 100%);
            padding: 2rem 1rem;
        }

        .register-card {
            background: var(--twr-white);
            border-radius: var(--twr-radius-lg);
            box-shadow: var(--twr-shadow-xl);
            padding: 2.5rem 3rem;
            max-width: 520px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .register-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--twr-teal) 0%, var(--twr-navy) 100%);
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-header .logo-icon {
            font-size: 2.5rem;
            color: var(--twr-teal);
            display: block;
            margin-bottom: 0.5rem;
        }

        .register-header h3 {
            color: var(--twr-navy);
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .register-header p {
            color: var(--twr-gray-600);
            font-size: 0.9rem;
        }

        .register-card .form-control {
            padding: 0.75rem 1rem;
            border-radius: var(--twr-radius-sm);
        }

        .register-card .form-control:focus {
            border-color: var(--twr-teal);
            box-shadow: 0 0 0 3px rgba(0, 140, 140, 0.15);
        }

        .register-card .btn-primary {
            width: 100%;
            padding: 0.8rem;
            font-weight: 600;
            border-radius: var(--twr-radius-sm);
        }

        .register-card .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--twr-shadow-md);
        }

        .register-card .form-text {
            font-size: 0.8rem;
        }

        .register-card .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 0.3rem;
            background: var(--twr-gray-200);
            transition: var(--twr-transition);
        }

        .register-card .password-strength .strength-bar {
            height: 100%;
            border-radius: 2px;
            transition: var(--twr-transition);
            width: 0%;
        }

        .register-card .strength-text {
            font-size: 0.75rem;
            margin-top: 0.2rem;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--twr-light-bg);
        }

        .login-link a {
            color: var(--twr-teal);
            font-weight: 500;
        }

        .login-link a:hover {
            color: var(--twr-navy);
        }

        /* Role selection styling */
        .role-options {
            display: flex;
            gap: 1rem;
            margin-top: 0.3rem;
        }

        .role-option {
            flex: 1;
            position: relative;
        }

        .role-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .role-option label {
            display: block;
            padding: 0.6rem 0.5rem;
            text-align: center;
            border: 2px solid var(--twr-gray-300);
            border-radius: var(--twr-radius-sm);
            cursor: pointer;
            transition: var(--twr-transition);
            font-weight: 500;
            font-size: 0.85rem;
            background: var(--twr-white);
        }

        .role-option input[type="radio"]:checked+label {
            border-color: var(--twr-teal);
            background-color: rgba(0, 140, 140, 0.05);
            color: var(--twr-teal);
        }

        .role-option label:hover {
            border-color: var(--twr-teal);
        }

        .role-option label i {
            display: block;
            font-size: 1.2rem;
            margin-bottom: 0.2rem;
        }

        @media (max-width: 576px) {
            .register-card {
                padding: 2rem 1.5rem;
            }

            .role-options {
                flex-direction: column;
                gap: 0.5rem;
            }

            .register-header h3 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>

<body>
    <div class="register-page">
        <div class="register-card">
            <div class="register-header">
                <span class="logo-icon">
                    <i class="bi bi-broadcast"></i>
                </span>
                <h3>Create Account</h3>
                <p>Join the TWR Fault Management System</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> <?= $success ?>
                </div>
            <?php else: ?>
                <form method="POST" id="registerForm" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="bi bi-person"></i>
                            </span>
                            <input type="text" name="fullname" class="form-control"
                                placeholder="Enter your full name"
                                value="<?= $_POST['fullname'] ?? '' ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="bi bi-person-badge"></i>
                            </span>
                            <input type="text" name="username" class="form-control"
                                placeholder="Choose a username"
                                value="<?= $_POST['username'] ?? '' ?>" required>
                        </div>
                        <div class="form-text">Username must be unique and alphanumeric.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Address *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="bi bi-envelope"></i>
                            </span>
                            <input type="email" name="email" class="form-control"
                                placeholder="Enter your email"
                                value="<?= $_POST['email'] ?? '' ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" name="password" class="form-control"
                                id="password" placeholder="Create a password" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="strength-text" id="strengthText"></div>
                        <div class="form-text">Password must be at least 6 characters.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm Password *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="bi bi-lock-fill"></i>
                            </span>
                            <input type="password" name="confirm_password" class="form-control"
                                id="confirmPassword" placeholder="Confirm your password" required>
                        </div>
                        <div id="passwordMatch" class="form-text"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Select Role</label>
                        <div class="role-options">
                            <div class="role-option">
                                <input type="radio" name="role" value="Engineer" id="roleEngineer" <?= ($_POST['role'] ?? 'Technician') == 'Engineer' ? 'checked' : '' ?>>
                                <label for="roleEngineer">
                                    <i class="bi bi-tools"></i>
                                    Engineer
                                </label>
                            </div>
                            <div class="role-option">
                                <input type="radio" name="role" value="Technician" id="roleTechnician" <?= ($_POST['role'] ?? 'Technician') == 'Technician' ? 'checked' : '' ?>>
                                <label for="roleTechnician">
                                    <i class="bi bi-wrench"></i>
                                    Technician
                                </label>
                            </div>
                            <div class="role-option">
                                <input type="radio" name="role" value="Admin" id="roleAdmin" <?= ($_POST['role'] ?? '') == 'Admin' ? 'checked' : '' ?>>
                                <label for="roleAdmin">
                                    <i class="bi bi-shield"></i>
                                    Admin
                                </label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-person-plus"></i> Create Account
                    </button>
                </form>

                <div class="login-link">
                    Already have an account? <a href="login.php">Login here</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password visibility toggle
        document.getElementById('togglePassword')?.addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });

        // Password strength meter
        document.getElementById('password')?.addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');

            let strength = 0;
            let message = '';
            let color = '';

            if (password.length >= 6) strength += 25;
            if (password.match(/[a-z]+/)) strength += 25;
            if (password.match(/[A-Z]+/)) strength += 25;
            if (password.match(/[0-9]+/)) strength += 25;

            if (strength === 0) {
                message = 'Enter a password';
                color = '#e9ecef';
            } else if (strength <= 25) {
                message = 'Weak';
                color = '#dc3545';
            } else if (strength <= 50) {
                message = 'Fair';
                color = '#ffc107';
            } else if (strength <= 75) {
                message = 'Good';
                color = '#17a2b8';
            } else {
                message = 'Strong';
                color = '#28a745';
            }

            strengthBar.style.width = strength + '%';
            strengthBar.style.backgroundColor = color;
            strengthText.textContent = message;
            strengthText.style.color = color;
        });

        // Password match validation
        document.getElementById('confirmPassword')?.addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirm = this.value;
            const matchText = document.getElementById('passwordMatch');

            if (confirm.length === 0) {
                matchText.textContent = '';
                return;
            }

            if (password === confirm) {
                matchText.textContent = '✓ Passwords match';
                matchText.style.color = '#28a745';
            } else {
                matchText.textContent = '✗ Passwords do not match';
                matchText.style.color = '#dc3545';
            }
        });

        // Form validation before submit
        document.getElementById('registerForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirmPassword').value;

            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match. Please try again.');
            }
        });
    </script>
</body>

</html>