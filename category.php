<?php
require_once 'config.php';

$categoryId = $_GET['id'] ?? '';
if (empty($categoryId) || !is_numeric($categoryId)) {
    redirect('index.php');
}

$db = new Database();
$pdo = $db->getConnection();

// Get category details
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$categoryId]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    redirect('index.php');
}

// Get articles in this category
$stmt = $pdo->prepare("
    SELECT a.*, c.name as category_name, u.name as author_name 
    FROM articles a 
    LEFT JOIN categories c ON a.category_id = c.id 
    LEFT JOIN users u ON a.author_id = u.id 
    WHERE a.category_id = ? AND a.published = 1 
    ORDER BY a.created_at DESC
");
$stmt->execute([$categoryId]);
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all categories for sidebar
$stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['name']); ?> - Article Blog</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Article Blog</h1>
                    <p class="text-gray-600">Your Source for Quality Content</p>
                </div>
                <a href="login.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Admin Login
                </a>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="bg-blue-600 text-white">
        <div class="container mx-auto px-4">
            <ul class="flex space-x-6 py-4">
                <li><a href="index.php" class="hover:text-blue-200 font-medium">Home</a></li>
                <li><a href="#" class="hover:text-blue-200">About</a></li>
                <li><a href="#" class="hover:text-blue-200">Contact</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column - Articles -->
            <div class="lg:col-span-2">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">
                        Articles in "<?php echo htmlspecialchars($category['name']); ?>"
                    </h2>
                    <?php if ($category['description']): ?>
                        <p class="text-gray-600"><?php echo htmlspecialchars($category['description']); ?></p>
                    <?php endif; ?>
                </div>

                <?php if (empty($articles)): ?>
                    <div class="bg-white rounded-lg shadow-md p-6 text-center">
                        <p class="text-gray-500">No articles found in this category.</p>
                        <a href="index.php" class="text-blue-600 hover:text-blue-800 mt-2 inline-block">
                            ← Back to all articles
                        </a>
                    </div>
                <?php else: ?>
                    <div class="space-y-6"> <?php foreach ($articles as $article): ?>
                            <article class="bg-white rounded-lg shadow-md overflow-hidden">
                                <?php
                                                $safeImageUrl = getSafeImageUrl($article['image_url']);
                                                if ($safeImageUrl):
                                ?>
                                    <img src="<?php echo htmlspecialchars($safeImageUrl); ?>"
                                        alt="<?php echo getImageAlt($article['title'], $safeImageUrl); ?>"
                                        class="w-full h-48 object-cover"
                                        onerror="this.style.display='none'">
                                <?php endif; ?>

                                <div class="p-6">
                                    <div class="flex items-center text-sm text-gray-500 mb-2">
                                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs mr-2">
                                            <?php echo htmlspecialchars($article['category_name']); ?>
                                        </span>
                                        <span>By <?php echo htmlspecialchars($article['author_name']); ?></span>
                                        <span class="mx-2">•</span>
                                        <span><?php echo formatDate($article['created_at']); ?></span>
                                    </div>

                                    <h3 class="text-xl font-semibold text-gray-900 mb-3">
                                        <a href="article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>"
                                            class="hover:text-blue-600">
                                            <?php echo htmlspecialchars($article['title']); ?>
                                        </a>
                                    </h3>

                                    <p class="text-gray-600 mb-4">
                                        <?php echo truncateText(strip_tags($article['content'])); ?>
                                    </p>

                                    <a href="article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>"
                                        class="text-blue-600 hover:text-blue-800 font-medium">
                                        Read More →
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column - Sidebar -->
            <div class="space-y-6">
                <!-- Search -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Search</h3>
                    <form method="GET" action="index.php">
                        <div class="flex">
                            <input type="text" name="search" placeholder="Search articles..."
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <button type="submit"
                                class="bg-blue-600 text-white px-4 py-2 rounded-r-md hover:bg-blue-700">
                                Search
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Categories -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Categories</h3>
                    <ul class="space-y-2">
                        <?php foreach ($categories as $cat): ?>
                            <li>
                                <a href="category.php?id=<?php echo $cat['id']; ?>"
                                    class="<?php echo $cat['id'] == $categoryId ? 'font-semibold text-blue-800' : 'text-blue-600 hover:text-blue-800'; ?> block py-1">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- About -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">About</h3>
                    <p class="text-gray-600">
                        Welcome to our blog! We share quality articles on technology, lifestyle, travel, and more.
                        Stay updated with the latest trends and insights.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-12">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; 2025 Article Blog. All rights reserved.</p>
        </div>
    </footer>
</body>

</html>