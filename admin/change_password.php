<?php
require_once '../auth.php';

requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword)) {
        $error = 'Current password and new password are required';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match';
    } elseif (strlen($newPassword) < 6) {
        $error = 'New password must be at least 6 characters long';
    } else {
        $result = $auth->updatePassword($_SESSION['user_id'], $currentPassword, $newPassword);

        if (isset($result['success'])) {
            $success = $result['message'];
        } else {
            $error = $result['error'];
        }
    }
}

$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Article CMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-blue-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-8">
                    <h1 class="text-xl font-bold">Article CMS</h1>
                    <ul class="flex space-x-6">
                        <li><a href="dashboard.php" class="hover:text-blue-200">Dashboard</a></li>
                        <li><a href="articles.php" class="hover:text-blue-200">Articles</a></li>
                        <li><a href="categories.php" class="hover:text-blue-200">Categories</a></li>
                        <?php if (isAdmin()): ?>
                            <li><a href="users.php" class="hover:text-blue-200">Users</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="flex items-center space-x-4">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <a href="../index.php" class="hover:text-blue-200" target="_blank">View Site</a>
                    <a href="logout.php" class="bg-blue-700 px-3 py-1 rounded hover:bg-blue-800">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-2xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Change Password</h2>
            <p class="text-gray-600 mt-2">Update your account password</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-8">
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Account Information</h3>
                <div class="mt-2 text-sm text-gray-600">
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($currentUser['username']); ?></p>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($currentUser['name']); ?></p>
                    <p><strong>Role:</strong> <?php echo ucfirst(htmlspecialchars($currentUser['role'])); ?></p>
                </div>
            </div>

            <hr class="mb-6">

            <form method="POST" class="space-y-6">
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password *</label>
                    <input type="password" id="current_password" name="current_password" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Enter your current password">
                </div>

                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">New Password *</label>
                    <input type="password" id="new_password" name="new_password" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Enter new password (min 6 characters)">
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Confirm new password">
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="dashboard.php"
                        class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Update Password
                    </button>
                </div>
            </form>
        </div>

        <div class="mt-6 text-center">
            <a href="dashboard.php" class="text-blue-600 hover:text-blue-800">
                ← Back to Dashboard
            </a>
        </div>
    </div>

    <script>
        // Client-side password validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;

            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            if (password.length < 6) {
                this.setCustomValidity('Password must be at least 6 characters long');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>

</html>