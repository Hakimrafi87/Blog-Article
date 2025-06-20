<?php
require_once 'config.php';

$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    redirect('index.php');
}

$db = new Database();
$pdo = $db->getConnection();

// Get article details
$stmt = $pdo->prepare("
    SELECT a.*, c.name as category_name, u.name as author_name 
    FROM articles a 
    LEFT JOIN categories c ON a.category_id = c.id 
    LEFT JOIN users u ON a.author_id = u.id 
    WHERE a.slug = ? AND a.published = 1
");
$stmt->execute([$slug]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$article) {
    redirect('index.php');
}

// Get related articles
$stmt = $pdo->prepare("
    SELECT a.title, a.slug 
    FROM articles a 
    WHERE a.category_id = ? AND a.id != ? AND a.published = 1 
    ORDER BY a.created_at DESC 
    LIMIT 5
");
$stmt->execute([$article['category_id'], $article['id']]);
$relatedArticles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title']); ?> - Article Blog</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-blue-600 text-white">
        <div class="container mx-auto px-4">
            <ul class="flex space-x-6 py-4">
                <li><a href="index.php" class="hover:text-blue-200 font-medium">← Back to Home</a></li>
                <li><a href="#" class="hover:text-blue-200">About</a></li>
                <li><a href="#" class="hover:text-blue-200">About</a></li>
                <li><a href="#" class="hover:text-blue-200">Contact</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column - Article Detail -->
            <div class="lg:col-span-2">
                <article class="bg-white rounded-lg shadow-md overflow-hidden">
                    <?php
                    $safeImageUrl = getSafeImageUrl($article['image_url']);
                    if ($safeImageUrl):
                    ?>
                        <img src="<?php echo htmlspecialchars($safeImageUrl); ?>"
                            alt="<?php echo getImageAlt($article['title'], $safeImageUrl); ?>"
                            class="w-full h-64 object-cover"
                            onerror="this.style.display='none'">
                    <?php endif; ?>

                    <div class="p-8">
                        <div class="flex items-center text-sm text-gray-500 mb-4">
                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded text-sm mr-3">
                                <?php echo htmlspecialchars($article['category_name']); ?>
                            </span>
                            <span>By <?php echo htmlspecialchars($article['author_name']); ?></span>
                            <span class="mx-2">•</span>
                            <span><?php echo formatDate($article['created_at']); ?></span>
                        </div>

                        <h1 class="text-3xl font-bold text-gray-900 mb-6">
                            <?php echo htmlspecialchars($article['title']); ?>
                        </h1>

                        <div class="prose prose-lg max-w-none text-gray-700 leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($article['content'])); ?>
                        </div>

                        <?php if ($article['updated_at']): ?>
                            <div class="mt-8 pt-6 border-t border-gray-200 text-sm text-gray-500">
                                Last updated: <?php echo formatDate($article['updated_at']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
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

                <!-- Related Articles -->
                <?php if (!empty($relatedArticles)): ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Related Articles</h3>
                        <ul class="space-y-3">
                            <?php foreach ($relatedArticles as $related): ?>
                                <li>
                                    <a href="article.php?slug=<?php echo htmlspecialchars($related['slug']); ?>"
                                        class="text-blue-600 hover:text-blue-800 block">
                                        <?php echo htmlspecialchars($related['title']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
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