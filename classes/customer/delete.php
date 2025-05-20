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
require_once '../../classes/Customer.php';

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: index.php?error=No+customer+ID+provided");
    exit();
}

try {
    $customer = new Customer();
    
    // Get customer details before deletion
    $customer_data = $customer->getById($_GET['id']);
    
    if (!$customer_data) {
        header("Location: index.php?error=Customer+not+found");
        exit();
    }

    // Get sales data
    $db = Database::getInstance()->getConnection();
    $sql = "SELECT COUNT(*) as total_orders, SUM(total_amount) as total_spent 
            FROM sales WHERE customer_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$_GET['id']]);
    $sales_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Handle form submission
    if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
        if ($sales_data['total_orders'] > 0) {
            throw new Exception("Cannot delete customer: They have {$sales_data['total_orders']} sales records");
        }

        if ($customer->delete($_GET['id'])) {
            header("Location: index.php?success=3&deleted=" . urlencode($customer_data['first_name'] . ' ' . $customer_data['last_name']));
            exit();
        } else {
            throw new Exception("Failed to delete customer");
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
    <title>Delete Customer - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>
                <i class="fas fa-user-times"></i> Delete Customer
            </h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Customers
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title text-danger">
                <i class="fas fa-exclamation-triangle"></i> Confirm Deletion
            </h5>
            
            <div class="alert alert-info">
                <h6 class="alert-heading">Customer Details:</h6>
                <p class="mb-0">
                    <strong>Name:</strong> 
                    <?php echo htmlspecialchars($customer_data['first_name'] . ' ' . $customer_data['last_name']); ?>
                    <br>
                    <?php if($customer_data['company_name']): ?>
                        <strong>Company:</strong> 
                        <?php echo htmlspecialchars($customer_data['company_name']); ?>
                        <br>
                    <?php endif; ?>
                    <?php if($customer_data['email']): ?>
                        <strong>Email:</strong> 
                        <?php echo htmlspecialchars($customer_data['email']); ?>
                        <br>
                    <?php endif; ?>
                    <?php if($customer_data['phone']): ?>
                        <strong>Phone:</strong> 
                        <?php echo htmlspecialchars($customer_data['phone']); ?>
                        <br>
                    <?php endif; ?>
                    <strong>Created:</strong> 
                    <?php echo date('d/m/Y', strtotime($customer_data['created_at'])); ?>
                </p>
            </div>

            <?php if($sales_data['total_orders'] > 0): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle"></i>
                    <strong>Cannot Delete:</strong> 
                    This customer has <?php echo $sales_data['total_orders']; ?> sales records 
                    totaling $<?php echo number_format($sales_data['total_spent'], 2); ?>.
                    <br>
                    You must maintain these records for accounting purposes.
                </div>
                
                <div class="text-end">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Customers
                    </a>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Warning:</strong> This action cannot be undone! 
                    All customer information will be permanently deleted.
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="confirm" value="yes">
                    <div class="text-end">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete Customer
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
