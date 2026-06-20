<?php
// fix_password.php - Quick password fix
require_once 'config/db.php';

// Set all passwords to 'password123'
$password = 'password123';
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("UPDATE users SET password = ?");
    $stmt->execute([$hash]);

    $count = $stmt->rowCount();
    echo "<h2 style='color: green;'>✅ Password Reset Complete!</h2>";
    echo "<p>Updated <strong>$count</strong> user(s) password to: <code>password123</code></p>";
    echo "<hr>";
    echo "<h3>Current Users:</h3>";

    $users = $pdo->query("SELECT id, username, fullname, role FROM users")->fetchAll();
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Password</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['fullname']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "<td><code>password123</code></td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<p style='margin-top: 20px;'><a href='login.php' style='font-size: 18px;'>🔐 Go to Login</a></p>";
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ Error: " . $e->getMessage() . "</h2>";
}
