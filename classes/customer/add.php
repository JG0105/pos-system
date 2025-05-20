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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $customer = new Customer();
        
        // Check if email exists (if provided)
        if (!empty($_POST['email']) && $customer->emailExists($_POST['email'])) {
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

            if ($customer->create($data)) {
                header("Location: index.php?success=1");
                exit();
            } else {
                $error = "Failed to create customer";
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
    <title>Add Customer - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2><i class="fas fa-user-plus"></i> Add New Customer</h2>
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

    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
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
                                   value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="last_name" 
                                   name="last_name" 
                                   required
                                   value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            <div class="form-text">Optional, but useful for sending invoices and updates</div>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" 
                                   class="form-control" 
                                   id="phone" 
                                   name="phone"
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
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
                                   value="<?php echo isset($_POST['company_name']) ? htmlspecialchars($_POST['company_name']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="abn" class="form-label">ABN</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="abn" 
                                   name="abn"
                                   pattern="[0-9]{11}"
                                   title="ABN must be 11 digits"
                                   value="<?php echo isset($_POST['abn']) ? htmlspecialchars($_POST['abn']) : ''; ?>">
                            <div class="form-text">Australian Business Number (11 digits)</div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" 
                                      id="address" 
                                      name="address" 
                                      rows="3"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-info-circle"></i> Important Notes
                                </h6>
                                <ul class="mb-0">
                                    <li>Fields marked with * are required</li>
                                    <li>Email address must be unique in the system</li>
                                    <li>ABN should be 11 digits (if provided)</li>
                                    <li>Phone numbers are stored as entered</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Customer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
