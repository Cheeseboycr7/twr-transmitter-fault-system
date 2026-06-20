<?php
// config/db.php - Complete Database Configuration
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// Database Configuration
// ============================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'transmitter_fault_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ============================================
// Application Configuration
// ============================================
define('APP_NAME', 'TWR Transmitter Fault Management System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/transmitter_fault_system/');
define('TIMEZONE', 'Africa/Johannesburg');

// Set timezone
date_default_timezone_set(TIMEZONE);

// ============================================
// Database Connection
// ============================================
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database Connection Error: " . $e->getMessage());
    die("Sorry, we're experiencing technical difficulties. Please try again later.");
}

// ============================================
// Session Management Functions
// ============================================

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId()
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 * @return string|null
 */
function getCurrentUserRole()
{
    return $_SESSION['role'] ?? null;
}

/**
 * Get current user full name
 * @return string|null
 */
function getCurrentUserName()
{
    return $_SESSION['fullname'] ?? null;
}

/**
 * Get current user email
 * @return string|null
 */
function getCurrentUserEmail()
{
    return $_SESSION['email'] ?? null;
}

/**
 * Check if user has specific role
 * @param string $role
 * @return bool
 */
function hasRole($role)
{
    return isset($_SESSION['role']) && strtolower($_SESSION['role']) === strtolower($role);
}

/**
 * Check if user has any of the specified roles
 * @param array $roles
 * @return bool
 */
function hasAnyRole($roles)
{
    if (!isset($_SESSION['role'])) {
        return false;
    }
    return in_array(strtolower($_SESSION['role']), array_map('strtolower', $roles));
}

/**
 * Check if user is admin
 * @return bool
 */
function isAdmin()
{
    return hasRole('Admin');
}

/**
 * Check if user is operator
 * @return bool
 */
function isOperator()
{
    return hasRole('Operator');
}

/**
 * Check if user is engineer
 * @return bool
 */
function isEngineer()
{
    return hasRole('Engineer');
}

/**
 * Check if user is technician
 * @return bool
 */
function isTechnician()
{
    return hasRole('Technician');
}

// ============================================
// Redirect Functions
// ============================================

/**
 * Redirect to a URL
 * @param string $url
 * @return void
 */
function redirect($url)
{
    header("Location: " . $url);
    exit();
}

/**
 * Redirect back to previous page
 * @return void
 */
function redirectBack()
{
    $referer = $_SERVER['HTTP_REFERER'] ?? APP_URL . 'dashboard.php';
    redirect($referer);
}

/**
 * Redirect with success message
 * @param string $url
 * @param string $message
 * @return void
 */
function redirectWithSuccess($url, $message)
{
    $_SESSION['success'] = $message;
    redirect($url);
}

/**
 * Redirect with error message
 * @param string $url
 * @param string $message
 * @return void
 */
function redirectWithError($url, $message)
{
    $_SESSION['error'] = $message;
    redirect($url);
}

// ============================================
// Sanitization Functions
// ============================================

/**
 * Sanitize input string
 * @param string $input
 * @return string
 */
function sanitize($input)
{
    if (is_null($input)) {
        return '';
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize array of inputs
 * @param array $inputs
 * @return array
 */
function sanitizeArray($inputs)
{
    return array_map('sanitize', $inputs);
}

/**
 * Validate email address
 * @param string $email
 * @return bool
 */
function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 * @param string $password
 * @return array [bool, string]
 */
function validatePassword($password)
{
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errors[] = 'Password must contain at least one special character';
    }

    return [empty($errors), $errors];
}

// ============================================
// Fault Number Generation
// ============================================

/**
 * Generate unique fault number
 * Format: FYYYYXXXX (e.g., F20260001)
 * @return string
 */
function generateFaultNo()
{
    global $pdo;
    $year = date('Y');

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM faults WHERE YEAR(date_reported) = ?");
        $stmt->execute([$year]);
        $result = $stmt->fetch();
        $count = ($result['count'] ?? 0) + 1;
        return 'F' . $year . str_pad($count, 4, '0', STR_PAD_LEFT);
    } catch (PDOException $e) {
        error_log("Error generating fault number: " . $e->getMessage());
        return 'F' . $year . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}

// ============================================
// Logging Functions
// ============================================

/**
 * Log user action to audit log
 * @param int $user_id
 * @param string $action
 * @param string $description
 * @return bool
 */
function logAction($user_id, $action, $description)
{
    global $pdo;

    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        $stmt = $pdo->prepare("
            INSERT INTO audit_log (user_id, action, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$user_id, $action, $description, $ip, $user_agent]);
    } catch (PDOException $e) {
        error_log("Error logging action: " . $e->getMessage());
        return false;
    }
}

/**
 * Log user login
 * @param int $user_id
 * @return bool
 */
function logLogin($user_id)
{
    return logAction($user_id, 'Login', 'User logged in');
}

/**
 * Log user logout
 * @param int $user_id
 * @return bool
 */
function logLogout($user_id)
{
    return logAction($user_id, 'Logout', 'User logged out');
}

/**
 * Log failed login attempt
 * @param string $username
 * @return bool
 */
function logFailedLogin($username)
{
    global $pdo;

    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt = $pdo->prepare("
            INSERT INTO audit_log (user_id, action, description, ip_address) 
            VALUES (NULL, 'Failed Login', ?, ?)
        ");
        return $stmt->execute(["Failed login attempt for username: $username", $ip]);
    } catch (PDOException $e) {
        error_log("Error logging failed login: " . $e->getMessage());
        return false;
    }
}

// ============================================
// User Functions
// ============================================

/**
 * Get user by ID
 * @param int $id
 * @return array|null
 */
function getUserById($id)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT id, fullname, username, email, role, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching user: " . $e->getMessage());
        return null;
    }
}

/**
 * Get user by username
 * @param string $username
 * @return array|null
 */
function getUserByUsername($username)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching user: " . $e->getMessage());
        return null;
    }
}

/**
 * Get user by email
 * @param string $email
 * @return array|null
 */
function getUserByEmail($email)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching user: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if username exists
 * @param string $username
 * @return bool
 */
function usernameExists($username)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Error checking username: " . $e->getMessage());
        return true; // Assume exists to prevent duplicates
    }
}

/**
 * Check if email exists
 * @param string $email
 * @return bool
 */
function emailExists($email)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Error checking email: " . $e->getMessage());
        return true; // Assume exists to prevent duplicates
    }
}

/**
 * Create new user
 * @param array $userData
 * @return int|false
 */
function createUser($userData)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (fullname, username, email, password, role) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userData['fullname'],
            $userData['username'],
            $userData['email'],
            password_hash($userData['password'], PASSWORD_DEFAULT),
            $userData['role'] ?? 'Technician'
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error creating user: " . $e->getMessage());
        return false;
    }
}

// ============================================
// Flash Messages
// ============================================

/**
 * Set flash message
 * @param string $type (success, error, warning, info)
 * @param string $message
 * @return void
 */
function setFlash($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
        'timestamp' => time()
    ];
}

/**
 * Get flash message and clear it
 * @return array|null
 */
function getFlash()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Display flash message if exists
 * @return void
 */
function displayFlash()
{
    $flash = getFlash();
    if ($flash) {
        $alertClass = match ($flash['type']) {
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info',
            default => 'alert-info'
        };
        echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
        echo $flash['message'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
}

// ============================================
// CSRF Protection
// ============================================

/**
 * Generate CSRF token
 * @return string
 */
function generateCsrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 * @param string $token
 * @return bool
 */
function validateCsrfToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate CSRF token field for forms
 * @return string
 */
function csrfField()
{
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

// ============================================
// Display Functions
// ============================================

/**
 * Display status badge
 * @param string $status
 * @return string
 */
function statusBadge($status)
{
    $statuses = [
        'Open' => 'warning',
        'In Progress' => 'info',
        'Fixed' => 'success',
        'Closed' => 'secondary',
        'Pending' => 'warning',
        'Operational' => 'success',
        'Maintenance' => 'warning',
        'Offline' => 'danger',
        'Faulty' => 'danger'
    ];

    $color = $statuses[$status] ?? 'secondary';
    return '<span class="badge bg-' . $color . '">' . $status . '</span>';
}

/**
 * Display severity badge
 * @param string $severity
 * @return string
 */
function severityBadge($severity)
{
    $severities = [
        'Low' => 'info',
        'Medium' => 'warning',
        'Critical' => 'danger'
    ];

    $color = $severities[$severity] ?? 'secondary';
    return '<span class="badge bg-' . $color . '">' . $severity . '</span>';
}

// ============================================
// Helper Functions
// ============================================

/**
 * Format date for display
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'd-M-Y H:i')
{
    if (empty($date)) {
        return 'N/A';
    }
    try {
        $timestamp = strtotime($date);
        return date($format, $timestamp);
    } catch (Exception $e) {
        return $date;
    }
}

/**
 * Truncate text
 * @param string $text
 * @param int $length
 * @param string $suffix
 * @return string
 */
function truncate($text, $length = 100, $suffix = '...')
{
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Generate random string
 * @param int $length
 * @return string
 */
function randomString($length = 32)
{
    return bin2hex(random_bytes($length / 2));
}

/**
 * Get user IP address
 * @return string
 */
function getUserIP()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// ============================================
// Initialize CSRF Token
// ============================================
generateCsrfToken();
