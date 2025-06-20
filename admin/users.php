<?php
require_once '../config.php';
require_once '../auth.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$db = new Database();
$pdo = $db->getConnection();

$error = '';
$success = '';

// Handle create user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $name = $_POST['name'] ?? '';
    $role = $_POST['role'] ?? '';

    if (empty($username) || empty($password) || empty($name) || empty($role)) {
        $error = 'All fields are required';
    } else {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $hashedPassword, $name, $role]);
            $success = 'User created successfully!';
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $error = 'Username already exists';
            } else {
                $error = 'Error creating user: ' . $e->getMessage();
            }
        }
    }
}

// Handle update user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = $_POST['id'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $name = $_POST['name'] ?? '';
    $role = $_POST['role'] ?? '';

    if (empty($username) || empty($name) || empty($role) || empty($id)) {
        $error = 'Username, name, and role are required';
    } else {
        try {
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, name = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $hashedPassword, $name, $role, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, name = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $name, $role, $id]);
            }
            $success = 'User updated successfully!';
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $error = 'Username already exists';
            } else {
                $error = 'Error updating user: ' . $e->getMessage();
            }
        }
    }
}

// Handle delete user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $userId = $_GET['delete'];

    // Check if user has articles
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM articles WHERE author_id = ?");
    $stmt->execute([$userId]);
    $articleCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Don't allow deleting current user
    if ($userId == $_SESSION['user_id']) {
        $error = 'You cannot delete your own account.';
    } elseif ($articleCount > 0) {
        $error = 'Cannot delete user who has authored articles. Please reassign or delete the articles first.';
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $success = 'User deleted successfully!';
    }
}

// Get users with article count
$stmt = $pdo->prepare("
    SELECT u.*, COUNT(a.id) as article_count 
    FROM users u 
    LEFT JOIN articles a ON u.id = a.author_id 
    GROUP BY u.id 
    ORDER BY u.created_at DESC
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user for editing
$editUser = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Article CMS</title>
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
                        <li><a href="users.php" class="font-medium">Users</a></li>
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
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Users</h2>
            <p class="text-gray-600 mt-2">Manage system users</p>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- User Form -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <?php echo $editUser ? 'Edit User' : 'Create New User'; ?>
                </h3>

                <form method="POST" class="space-y-4">
                    <?php if ($editUser): ?>
                        <input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">
                    <?php endif; ?>

                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username *</label>
                        <input type="text" id="username" name="username" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            value="<?php echo htmlspecialchars($editUser['username'] ?? ''); ?>">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Password <?php echo $editUser ? '(leave blank to keep current)' : '*'; ?>
                        </label>
                        <input type="password" id="password" name="password"
                            <?php echo $editUser ? '' : 'required'; ?>
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                        <input type="text" id="name" name="name" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            value="<?php echo htmlspecialchars($editUser['name'] ?? ''); ?>">
                    </div>

                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Role *</label>
                        <select id="role" name="role" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select a role</option>
                            <option value="admin" <?php echo (isset($editUser['role']) && $editUser['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="author" <?php echo (isset($editUser['role']) && $editUser['role'] == 'author') ? 'selected' : ''; ?>>Author</option>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-2">
                        <?php if ($editUser): ?>
                            <a href="users.php"
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit" name="update"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Update User
                            </button>
                        <?php else: ?>
                            <button type="submit" name="create"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Create User
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Users List -->
            <div class="lg:col-span-2 bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">System Users</h3>
                </div>

                <?php if (empty($users)): ?>
                    <div class="p-6 text-center text-gray-500">
                        No users found.
                    </div>
                <?php else: ?>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Articles</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($user['name']); ?>
                                                <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                    <span class="text-xs text-blue-600">(You)</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                @<?php echo htmlspecialchars($user['username']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'; ?>">
                                            <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $user['article_count']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo formatDate($user['created_at']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="?edit=<?php echo $user['id']; ?>"
                                                class="text-blue-600 hover:text-blue-900">Edit</a>
                                            <?php if ($user['id'] != $_SESSION['user_id'] && $user['article_count'] == 0): ?>
                                                <a href="?delete=<?php echo $user['id']; ?>"
                                                    onclick="return confirm('Are you sure you want to delete this user?')"
                                                    class="text-red-600 hover:text-red-900">Delete</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>