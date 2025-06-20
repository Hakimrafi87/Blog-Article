<?php
require_once '../config.php';
require_once '../auth.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$db = new Database();
$pdo = $db->getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $image_url = $_POST['image_url'] ?? '';
    $published = isset($_POST['published']) ? 1 : 0;

    if (empty($title) || empty($content) || empty($category_id)) {
        $error = 'Title, content, and category are required';
    } else {
        $slug = generateSlug($title);

        // Check if slug already exists
        $stmt = $pdo->prepare("SELECT id FROM articles WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            $slug .= '-' . time();
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO articles (title, slug, content, category_id, author_id, image_url, published) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $slug, $content, $category_id, $_SESSION['user_id'], $image_url, $published]);

            $success = 'Article created successfully!';

            // Clear form data
            $title = $content = $category_id = $image_url = '';
            $published = 0;
        } catch (PDOException $e) {
            $error = 'Error creating article: ' . $e->getMessage();
        }
    }
}

// Get categories
$stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Article - Article CMS</title>
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
    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Create New Article</h2>
                <p class="text-gray-600 mt-2">Write and publish your content</p>
            </div>
            <a href="articles.php" class="text-blue-600 hover:text-blue-800">
                ← Back to Articles
            </a>
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

        <div class="bg-white rounded-lg shadow-md">
            <form method="POST" class="p-6 space-y-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                    <input type="text" id="title" name="title" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        value="<?php echo htmlspecialchars($title ?? ''); ?>">
                </div>

                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                    <select id="category_id" name="category_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"
                                <?php echo (isset($category_id) && $category_id == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="image_select" class="block text-sm font-medium text-gray-700 mb-2">Select Image</label>
                    <select id="image_select" name="image_select" onchange="updateImageUrl()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select from available images --</option>
                        <?php
                        $imgDir = dirname(__DIR__) . '/admin/img/';
                        if (is_dir($imgDir)) {
                            $images = scandir($imgDir);
                            $imageFiles = array_filter($images, function ($file) {
                                return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                            });
                            foreach ($imageFiles as $image) {
                                $imagePath = 'admin/img/' . $image;
                                $selected = (isset($image_url) && $image_url === $imagePath) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($imagePath) . "' $selected>" . htmlspecialchars($image) . "</option>";
                            }
                        }
                        ?>
                    </select>
                    <p class="text-sm text-gray-500 mt-1">Choose from uploaded images in the img folder</p>
                </div>

                <div>
                    <label for="image_url" class="block text-sm font-medium text-gray-700 mb-2">Or enter custom Image URL</label>
                    <input type="url" id="image_url" name="image_url"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        value="<?php echo htmlspecialchars($image_url ?? ''); ?>"
                        placeholder="https://example.com/image.jpg">
                </div>

                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700 mb-2">Content *</label>
                    <textarea id="content" name="content" rows="15" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Write your article content here..."><?php echo htmlspecialchars($content ?? ''); ?></textarea>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" id="published" name="published" value="1"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        <?php echo (isset($published) && $published) ? 'checked' : ''; ?>>
                    <label for="published" class="ml-2 block text-sm text-gray-900">
                        Publish immediately
                    </label>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="articles.php"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Create Article
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateImageUrl() {
            const select = document.getElementById('image_select');
            const imageUrl = document.getElementById('image_url');

            if (select.value) {
                imageUrl.value = select.value;
            }
        }
    </script>
</body>

</html>