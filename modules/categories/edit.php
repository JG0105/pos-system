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

$error = null;
$success = null;
$category_data = null;

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: index.php?error=No+category+ID+provided");
    exit();
}

$category = new Category();

// Get category data
try {
    $category_data = $category->getById($_GET['id']);
    if (!$category_data) {
        header("Location: index.php?error=Category+not+found");
        exit();
    }
} catch (Exception $e) {
    header("Location: index.php?error=" . urlencode($e->getMessage()));
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Check if category name exists (excluding current category)
        if ($category->nameExists($_POST['category_name'], $_GET['id'])) {
            $error = "Category name already exists";
        } else {
            $data = [
                'category_name' => trim($_POST['category_name']),
                'description' => trim($_POST['description'] ?? '')
            ];

            if ($category->update($_GET['id'], $data)) {
                $success = "Category updated successfully";
                // Refresh category data
                $category_data = $category->getById($_GET['id']);
            } else {
                $error = "Failed to update category";
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Edit Category</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Categories
            </a>
        </div>
    </div>

    <?php if($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="category_name" class="form-label">Category Name *</label>
                    <input type="text" 
                           class="form-control" 
                           id="category_name" 
                           name="category_name" 
                           required
                           maxlength="50"
                           value="<?php echo htmlspecialchars($category_data['category_name']); ?>">
                    <div class="form-text">Maximum 50 characters</div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" 
                              id="description" 
                              name="description" 
                              rows="3"
                              maxlength="255"><?php echo htmlspecialchars($category_data['description'] ?? ''); ?></textarea>
                    <div class="form-text">Maximum 255 characters</div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-info-circle"></i> Category Information
                                </h6>
                                <p class="card-text mb-1">
                                    <strong>Created:</strong> 
                                    <?php echo date('d/m/Y H:i', strtotime($category_data['created_at'])); ?>
                                </p>
                                <?php
                                // Get product count
                                $sql = "SELECT COUNT(*) FROM products WHERE category_id = ?";
                                $stmt = Database::getInstance()->getConnection()->prepare($sql);
                                $stmt->execute([$category_data['category_id']]);
                                $productCount = $stmt->fetchColumn();
                                ?>
                                <p class="card-text mb-0">
                                    <strong>Products:</strong> 
                                    <span class="badge bg-info"><?php echo $productCount; ?> products</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
