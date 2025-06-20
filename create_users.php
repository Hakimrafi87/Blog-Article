<?php
require_once 'config.php';

echo "<h2>👥 Create Default Users</h2>";

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if (!$stmt->fetch()) {
        throw new Exception("Users table does not exist. Please import database.sql first.");
    }

    // Clear existing users (optional - comment out if you want to keep existing users)
    // $pdo->exec("DELETE FROM users");

    // Create admin user
    $adminPassword = password_hash('password', PASSWORD_DEFAULT);

    // Check if admin already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin'");
    $stmt->execute();

    if ($stmt->fetch()) {
        // Update existing admin
        $stmt = $pdo->prepare("UPDATE users SET password = ?, name = 'Administrator', role = 'admin' WHERE username = 'admin'");
        $stmt->execute([$adminPassword]);
        echo "<p style='color: green;'>✅ Updated admin user</p>";
    } else {
        // Create new admin
        $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role) VALUES ('admin', ?, 'Administrator', 'admin')");
        $stmt->execute([$adminPassword]);
        echo "<p style='color: green;'>✅ Created admin user</p>";
    }

    // Create author user
    $authorPassword = password_hash('password', PASSWORD_DEFAULT);

    // Check if author1 already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'author1'");
    $stmt->execute();

    if ($stmt->fetch()) {
        // Update existing author
        $stmt = $pdo->prepare("UPDATE users SET password = ?, name = 'John Doe', role = 'author' WHERE username = 'author1'");
        $stmt->execute([$authorPassword]);
        echo "<p style='color: green;'>✅ Updated author1 user</p>";
    } else {
        // Create new author
        $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role) VALUES ('author1', ?, 'John Doe', 'author')");
        $stmt->execute([$authorPassword]);
        echo "<p style='color: green;'>✅ Created author1 user</p>";
    }

    // Verify users
    echo "<h3>👀 User Verification</h3>";
    $stmt = $pdo->query("SELECT username, name, role FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f8f9fa;'><th>Username</th><th>Name</th><th>Role</th><th>Password Test</th></tr>";

    foreach ($users as $user) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($user['username']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($user['name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['role']) . "</td>";

        // Test password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE username = ?");
        $stmt->execute([$user['username']]);
        $passwordHash = $stmt->fetch(PDO::FETCH_ASSOC)['password'];

        $isValid = password_verify('password', $passwordHash);
        $testResult = $isValid ?
            "<span style='color: green;'>✅ Valid</span>" :
            "<span style='color: red;'>❌ Invalid</span>";

        echo "<td>$testResult</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 Users Created Successfully!</h3>";
    echo "<p><strong>Login Credentials:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> username = admin, password = password</li>";
    echo "<li><strong>Author:</strong> username = author1, password = password</li>";
    echo "</ul>";
    echo "<p><a href='login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Try Login</a></p>";
    echo "<p><a href='debug_complete.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Run Debug Again</a></p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ User Creation Failed</h3>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        line-height: 1.6;
    }

    table {
        background: white;
    }

    th,
    td {
        text-align: left;
        border: 1px solid #dee2e6;
    }

    th {
        background: #f8f9fa;
    }
</style>