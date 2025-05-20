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

$error = null;
$success = null;
$customer_data = null;

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: index.php?error=No+customer+ID+provided");
    exit();
}

$customer = new Customer();

// Get customer data
try {
    $customer_data = $customer->getById($_GET['id']);
    if (!$customer_data) {
        header("Location: index.php?error=Customer+not+found");
        exit();
    }

    // Get sales data
    $db = Database::getInstance()->getConnection();
    $sql = "SELECT COUNT(*) as total_orders, COALESCE(SUM(total_amount), 0) as total_spent 
            FROM sales WHERE customer_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$_GET['id']]);
    $sales_data = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    header("Location: index.php?error=" . urlencode($e->getMessage()));
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Check if email exists (if changed and provided)
        if (!empty($_POST['email']) && 
            $_POST['email'] !== $customer_data['email'] && 
            $customer->emailExists($_POST['email'])) {
            $error = "Email address already exists";
        } else {
            $data = [
                'first_name' => trim($_POST['first_name']),
                'last_name' => trim($_POST['last_name']),
                'email' => trim($_POST['email']) ?: null,
                'phone' => trim($_POST['phone']) ?: null,
                'address' => trim($_POST['address']) ?: null,
                'company_name' => trim($_POST['company_name']) ?: null,
                'abn' => trim($_POST['abn']) ?: null
            ];

            if ($customer->update($_GET['id'], $data)) {
                $success = "Customer updated successfully";
                // Refresh customer data
                $customer_data = $customer->getById($_GET['id']);
            } else {
                $error = "Failed to update customer";
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
    <title>Edit Customer - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>
                <i class="fas fa-user-edit"></i> 
                Edit Customer: <?php echo htmlspecialchars($customer_data['first_name'] . ' ' . $customer_data['last_name']); ?>
            </h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Customers
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

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-3">Personal Information</h5>
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="first_name" 
                                           name="first_name" 
                                           required
                                           value="<?php echo htmlspecialchars($customer_data['first_name']); ?>">
                                    <div class="invalid-feedback">
                                        Please provide a first name.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="last_name" 
                                           name="last_name" 
                                           required
                                           value="<?php echo htmlspecialchars($customer_data['last_name']); ?>">
                                    <div class="invalid-feedback">
                                        Please provide a last name.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email"
                                           value="<?php echo htmlspecialchars($customer_data['email'] ?? ''); ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" 
                                           class="form-control" 
                                           id="phone" 
                                           name="phone"
                                           value="<?php echo htmlspecialchars($customer_data['phone'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h5 class="mb-3">Business Information</h5>
                                <div class="mb-3">
                                    <label for="company_name" class="form-label">Company Name</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="company_name" 
                                           name="company_name"
                                           value="<?php echo htmlspecialchars($customer_data['company_name'] ?? ''); ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="abn" class="form-label">ABN</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="abn" 
                                           name="abn"
                                           pattern="[0-9]{11}"
                                           title="ABN must be 11 digits"
                                           value="<?php echo htmlspecialchars($customer_data['abn'] ?? ''); ?>">
                                    <div class="form-text">Australian Business Number (11 digits)</div>
                                </div>

                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" 
                                              id="address" 
                                              name="address" 
                                              rows="3"><?php echo htmlspecialchars($customer_data['address'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Customer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle"></i> Customer Information
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-1">
                        <strong>Customer ID:</strong> <?php echo $customer_data['customer_id']; ?>
                    </p>
                    <p class="mb-1">
                        <strong>Created:</strong> 
                        <?php echo date('d/m/Y', strtotime($customer_data['created_at'])); ?>
                    </p>
                    <p class="mb-1">
                        <strong>Last Updated:</strong> 
                        <?php echo date('d/m/Y H:i', strtotime($customer_data['updated_at'])); ?>
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line"></i> Sales Summary
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <h6 class="text-muted">Total Orders</h6>
                            <h3><?php echo number_format($sales_data['total_orders']); ?></h3>
                        </div>
                        <div class="col-6">
                            <h6 class="text-muted">Total Spent</h6>
                            <h3>$<?php echo number_format($sales_data['total_spent'], 2); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
})()
</script>
</body>
</html>
