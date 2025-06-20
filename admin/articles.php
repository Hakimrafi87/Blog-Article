<?php
require_once '../config.php';
require_once '../auth.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$db = new Database();
$pdo = $db->getConnection();

// Handle delete article
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $articleId = $_GET['delete'];

    // Check permissions
    if (isAdmin()) {
        $stmt = $pdo->prepare("DELETE FROM articles WHERE id = ?");
        $stmt->execute([$articleId]);
        redirect('articles.php');
    } else {
        // Check if author owns the article
        $stmt = $pdo->prepare("SELECT author_id FROM articles WHERE id = ?");
        $stmt->execute([$articleId]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($article && $article['author_id'] == $_SESSION['user_id']) {
            $stmt = $pdo->prepare("DELETE FROM articles WHERE id = ?");
            $stmt->execute([$articleId]);
        }
        redirect('articles.php');
    }
}

// Get articles with filters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$sql = "
    SELECT a.*, c.name as category_name, u.name as author_name 
    FROM articles a 
    LEFT JOIN categories c ON a.category_id = c.id 
    LEFT JOIN users u ON a.author_id = u.id 
    WHERE 1=1
";

$params = [];

// Apply author filter for non-admin users
if (!isAdmin()) {
    $sql .= " AND a.author_id = ?";
    $params[] = $_SESSION['user_id'];
}

// Apply status filter
if ($filter === 'published') {
    $sql .= " AND a.published = 1";
} elseif ($filter === 'draft') {
    $sql .= " AND a.published = 0";
}

// Apply search filter
if ($search) {
    $sql .= " AND (a.title LIKE ? OR a.content LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY a.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Articles - Article CMS</title>
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
                        <li><a href="articles.php" class="font-medium">Articles</a></li>
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
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Articles</h2>
                <p class="text-gray-600 mt-2">Manage your articles</p>
            </div>
            <a href="create_article.php"
                class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 font-medium">
                Create New Article
            </a>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" id="search" name="search"
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Search articles..."
                        class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="filter" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="filter" name="filter"
                        class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Articles</option>
                        <option value="published" <?php echo $filter === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="draft" <?php echo $filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    </select>
                </div>

                <button type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Filter
                </button>

                <a href="articles.php"
                    class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
                    Clear
                </a>
            </form>
        </div>

        <!-- Articles List -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <?php if (empty($articles)): ?>
                <div class="p-6 text-center">
                    <p class="text-gray-500">No articles found.</p>
                    <a href="create_article.php"
                        class="text-blue-600 hover:text-blue-800 font-medium">
                        Create your first article
                    </a>
                </div>
            <?php else: ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <?php if (isAdmin()): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                            <?php endif; ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($articles as $article): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($article['title']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($article['category_name']); ?>
                                    </span>
                                </td>
                                <?php if (isAdmin()): ?>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($article['author_name']); ?>
                                        </span>
                                    </td>
                                <?php endif; ?>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $article['published'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo $article['published'] ? 'Published' : 'Draft'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo formatDate($article['created_at']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <?php if ($article['published']): ?>
                                            <a href="../article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>"
                                                target="_blank"
                                                class="text-green-600 hover:text-green-900">View</a>
                                        <?php endif; ?>
                                        <a href="edit_article.php?id=<?php echo $article['id']; ?>"
                                            class="text-blue-600 hover:text-blue-900">Edit</a>
                                        <?php if (isAdmin() || $article['author_id'] == $_SESSION['user_id']): ?>
                                            <a href="?delete=<?php echo $article['id']; ?>"
                                                onclick="return confirm('Are you sure you want to delete this article?')"
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
</body>

</html>