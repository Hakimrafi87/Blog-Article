<?php
require_once '../auth.php';

// Require login to access dashboard
requireLogin();

// Validate session
if (!$auth->validateSession()) {
    redirect('../login.php');
}

$db = new Database();
$pdo = $db->getConnection();

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM articles WHERE published = 1");
$stmt->execute();
$publishedArticles = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM articles WHERE published = 0");
$stmt->execute();
$draftArticles = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM categories");
$stmt->execute();
$totalCategories = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users");
$stmt->execute();
$totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get recent articles
$stmt = $pdo->prepare("
    SELECT a.*, c.name as category_name 
    FROM articles a 
    LEFT JOIN categories c ON a.category_id = c.id 
    ORDER BY a.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recentArticles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Article CMS</title>
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
                        <li><a href="dashboard.php" class="font-medium">Dashboard</a></li>
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
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Dashboard</h2>
            <p class="text-gray-600 mt-2">Welcome to your content management system</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo $publishedArticles; ?></h3>
                        <p class="text-sm text-gray-600">Published Articles</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo $draftArticles; ?></h3>
                        <p class="text-sm text-gray-600">Draft Articles</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo $totalCategories; ?></h3>
                        <p class="text-sm text-gray-600">Categories</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo $totalUsers; ?></h3>
                        <p class="text-sm text-gray-600">Users</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Recent Articles -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Articles</h3>
                </div>
                <div class="p-6">
                    <?php if (empty($recentArticles)): ?>
                        <p class="text-gray-500 text-center py-4">No articles yet.</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recentArticles as $article): ?>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                    <div>
                                        <h4 class="font-medium text-gray-900">
                                            <?php echo htmlspecialchars($article['title']); ?>
                                        </h4>
                                        <p class="text-sm text-gray-600">
                                            <?php echo htmlspecialchars($article['category_name']); ?> •
                                            <?php echo formatDate($article['created_at']); ?>
                                        </p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="<?php echo $article['published'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?> px-2 py-1 rounded text-xs">
                                            <?php echo $article['published'] ? 'Published' : 'Draft'; ?>
                                        </span>
                                        <a href="edit_article.php?id=<?php echo $article['id']; ?>"
                                            class="text-blue-600 hover:text-blue-800 text-sm">Edit</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="mt-4">
                        <a href="articles.php" class="text-blue-600 hover:text-blue-800 font-medium">
                            View all articles →
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <a href="create_article.php"
                            class="block w-full bg-blue-600 text-white text-center py-3 px-4 rounded hover:bg-blue-700 font-medium">
                            Create New Article
                        </a>
                        <a href="categories.php"
                            class="block w-full border border-gray-300 text-gray-700 text-center py-3 px-4 rounded hover:bg-gray-50 font-medium">
                            Manage Categories
                        </a>
                        <?php if (isAdmin()): ?>
                            <a href="users.php"
                                class="block w-full border border-gray-300 text-gray-700 text-center py-3 px-4 rounded hover:bg-gray-50 font-medium">
                                Manage Users
                            </a>
                        <?php endif; ?>
                        <a href="../index.php" target="_blank"
                            class="block w-full border border-gray-300 text-gray-700 text-center py-3 px-4 rounded hover:bg-gray-50 font-medium">
                            View Website
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>