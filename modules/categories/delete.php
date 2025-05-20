<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';
require_once '../../classes/Category.php';

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: index.php?error=No+category+ID+provided");
    exit();
}

try {
    $category = new Category();
    
    // Get category details before deletion
    $category_data = $category->getById($_GET['id']);
    
    if (!$category_data) {
        header("Location: index.php?error=Category+not+found");
        exit();
    }

    // Get product count
    $db = Database::getInstance()->getConnection();
    $sql = "SELECT COUNT(*) FROM products WHERE category_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$_GET['id']]);
    $productCount = $stmt->fetchColumn();

    // Handle form submission
    if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
        if ($productCount > 0) {
            throw new Exception("Cannot delete category: It has {$productCount} associated products");
        }

        if ($category->delete($_GET['id'])) {
            header("Location: index.php?success=3&deleted=" . urlencode($category_data['category_name']));
            exit();
        } else {
            throw new Exception("Failed to delete category");
        }
    }
} catch (Exception $e) {
    header("Location: index.php?error=" . urlencode($e->getMessage()));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Category - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Delete Category</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Categories
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">
                <i class="fas fa-exclamation-triangle text-warning"></i> 
                Confirm Deletion
            </h5>
            
            <div class="alert alert-info">
                <h6 class="alert-heading">Category Details:</h6>
                <p class="mb-0">
                    <strong>Name:</strong> <?php echo htmlspecialchars($category_data['category_name']); ?><br>
                    <strong>Description:</strong> <?php echo htmlspecialchars($category_data['description'] ?? 'No description'); ?><br>
                    <strong>Created:</strong> <?php echo date('d/m/Y H:i', strtotime($category_data['created_at'])); ?><br>
                    <strong>Associated Products:</strong> <?php echo $productCount; ?>
                </p>
            </div>

            <?php if($productCount > 0): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle"></i>
                    <strong>Cannot Delete:</strong> This category has <?php echo $productCount; ?> associated products.
                    You must reassign or delete these products before deleting this category.
                </div>
                
                <div class="text-end">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Categories
                    </a>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Warning:</strong> This action cannot be undone!
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="confirm" value="yes">
                    <div class="text-end">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete Category
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
