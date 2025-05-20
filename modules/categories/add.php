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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $category = new Category();
        
        // Check if category name exists
        if ($category->nameExists($_POST['category_name'])) {
            $error = "Category name already exists";
        } else {
            $data = [
                'category_name' => trim($_POST['category_name']),
                'description' => trim($_POST['description'] ?? '')
            ];

            if ($category->create($data)) {
                header("Location: index.php?success=1");
                exit();
            } else {
                $error = "Failed to create category";
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
    <title>Add Category - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Add New Category</h2>
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
                           value="<?php echo isset($_POST['category_name']) ? htmlspecialchars($_POST['category_name']) : ''; ?>"
                           maxlength="50">
                    <div class="form-text">Maximum 50 characters</div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" 
                              id="description" 
                              name="description" 
                              rows="3"
                              maxlength="255"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    <div class="form-text">Maximum 255 characters</div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Category
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <h5 class="card-title">
                <i class="fas fa-info-circle"></i> Category Guidelines
            </h5>
            <ul class="mb-0">
                <li>Category names must be unique</li>
                <li>Category names should be clear and descriptive</li>
                <li>Use proper capitalization and spelling</li>
                <li>Avoid special characters when possible</li>
                <li>Description should provide additional context about the category</li>
            </ul>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
