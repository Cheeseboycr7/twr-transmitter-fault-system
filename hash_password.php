<?php
// hash_password.php - Generate password hash for new users

$password = 'password123'; // Change this to your desired password
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: $password\n";
echo "Hash: $hash\n";
echo "\nCopy this hash into your database.\n";
