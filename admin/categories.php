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

// Handle create category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';

    if (empty($name)) {
        $error = 'Category name is required';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            $success = 'Category created successfully!';
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $error = 'Category name already exists';
            } else {
                $error = 'Error creating category: ' . $e->getMessage();
            }
        }
    }
}

// Handle update category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';

    if (empty($name) || empty($id)) {
        $error = 'Category name and ID are required';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $description, $id]);
            $success = 'Category updated successfully!';
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $error = 'Category name already exists';
            } else {
                $error = 'Error updating category: ' . $e->getMessage();
            }
        }
    }
}

// Handle delete category
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $categoryId = $_GET['delete'];

    // Check if category has articles
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM articles WHERE category_id = ?");
    $stmt->execute([$categoryId]);
    $articleCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    if ($articleCount > 0) {
        $error = 'Cannot delete category that has articles. Please move or delete the articles first.';
    } else {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$categoryId]);
        $success = 'Category deleted successfully!';
    }
}

// Get categories with article count
$stmt = $pdo->prepare("
    SELECT c.*, COUNT(a.id) as article_count 
    FROM categories c 
    LEFT JOIN articles a ON c.id = a.category_id 
    GROUP BY c.id 
    ORDER BY c.name
");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get category for editing
$editCategory = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editCategory = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Article CMS</title>
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
                        <li><a href="categories.php" class="font-medium">Categories</a></li>
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
    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Categories</h2>
            <p class="text-gray-600 mt-2">Manage article categories</p>
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

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Category Form -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <?php echo $editCategory ? 'Edit Category' : 'Create New Category'; ?>
                </h3>

                <form method="POST" class="space-y-4">
                    <?php if ($editCategory): ?>
                        <input type="hidden" name="id" value="<?php echo $editCategory['id']; ?>">
                    <?php endif; ?>

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                        <input type="text" id="name" name="name" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            value="<?php echo htmlspecialchars($editCategory['name'] ?? ''); ?>">
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea id="description" name="description" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Optional description"><?php echo htmlspecialchars($editCategory['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="flex justify-end space-x-2">
                        <?php if ($editCategory): ?>
                            <a href="categories.php"
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit" name="update"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Update Category
                            </button>
                        <?php else: ?>
                            <button type="submit" name="create"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Create Category
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Categories List -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Existing Categories</h3>
                </div>

                <div class="divide-y divide-gray-200">
                    <?php if (empty($categories)): ?>
                        <div class="p-6 text-center text-gray-500">
                            No categories found. Create your first category!
                        </div>
                    <?php else: ?>
                        <?php foreach ($categories as $category): ?>
                            <div class="p-4">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </h4>
                                        <?php if ($category['description']): ?>
                                            <p class="text-sm text-gray-600 mt-1">
                                                <?php echo htmlspecialchars($category['description']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <?php echo $category['article_count']; ?> articles
                                        </p>
                                    </div>
                                    <div class="flex space-x-2 ml-4">
                                        <a href="?edit=<?php echo $category['id']; ?>"
                                            class="text-blue-600 hover:text-blue-800 text-sm">Edit</a>
                                        <?php if ($category['article_count'] == 0): ?>
                                            <a href="?delete=<?php echo $category['id']; ?>"
                                                onclick="return confirm('Are you sure you want to delete this category?')"
                                                class="text-red-600 hover:text-red-800 text-sm">Delete</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>

</html>