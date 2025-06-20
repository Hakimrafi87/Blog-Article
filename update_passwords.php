<?php
require_once 'config.php';

// This script updates the user passwords to properly hashed versions
// Run this once to fix password issues

$db = new Database();
$pdo = $db->getConnection();

// Update admin password
$adminPassword = password_hash('password', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
$stmt->execute([$adminPassword]);

// Update author password  
$authorPassword = password_hash('password', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'author1'");
$stmt->execute([$authorPassword]);

echo "Passwords updated successfully!\n";
echo "Admin: username=admin, password=password\n";
echo "Author: username=author1, password=password\n";

// Test the new passwords
echo "\nTesting passwords:\n";

// Test admin
$stmt = $pdo->prepare("SELECT password FROM users WHERE username = 'admin'");
$stmt->execute();
$adminHash = $stmt->fetch(PDO::FETCH_ASSOC)['password'];
echo "Admin password test: " . (password_verify('password', $adminHash) ? 'PASS' : 'FAIL') . "\n";

// Test author
$stmt = $pdo->prepare("SELECT password FROM users WHERE username = 'author1'");
$stmt->execute();
$authorHash = $stmt->fetch(PDO::FETCH_ASSOC)['password'];
echo "Author password test: " . (password_verify('password', $authorHash) ? 'PASS' : 'FAIL') . "\n";

echo "\nYou can now delete this file for security.\n";
