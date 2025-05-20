<?php
session_start();
require_once '../../config/database.php';
require_once '../../classes/Supplier.php';
require_once '../../includes/functions.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$error = '';
$success = '';

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate input
    $required_fields = ['company_name', 'contact_name', 'email', 'phone'];
    $missing_fields = [];
    
    foreach($required_fields as $field) {
        if(empty($_POST[$field])) {
            $missing_fields[] = ucfirst(str_replace('_', ' ', $field));
        }
    }
    
    if(!empty($missing_fields)) {
        $error = 'Please fill in all required fields: ' . implode(', ', $missing_fields);
    } else {
        // Create new supplier
        $supplier = new Supplier();
        $data = [
            'company_name' => sanitize($_POST['company_name']),
            'contact_name' => sanitize($_POST['contact_name']),
            'email' => sanitize($_POST['email']),
            'phone' => sanitize($_POST['phone']),
            'address' => sanitize($_POST['address']),
            'abn' => sanitize($_POST['abn'])
        ];
        
        if($supplier->create($data)) {
            $_SESSION['status_message'] = 'Supplier added successfully';
            header("Location: index.php");
            exit();
        } else {
            $error = 'Failed to add supplier';
        }
    }
}

// Include header
require_once '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><i class="fas fa-truck"></i> Add New Supplier</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Suppliers
            </a>
        </div>
    </div>

    <?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="company_name" class="form-label">Company Name *</label>
                        <input type="text" 
                               class="form-control" 
                               id="company_name" 
                               name="company_name" 
                               value="<?php echo isset($_POST['company_name']) ? htmlspecialchars($_POST['company_name']) : ''; ?>"
                               required>
                        <div class="invalid-feedback">
                            Please enter company name
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="contact_name" class="form-label">Contact Person *</label>
                        <input type="text" 
                               class="form-control" 
                               id="contact_name" 
                               name="contact_name"
                               value="<?php echo isset($_POST['contact_name']) ? htmlspecialchars($_POST['contact_name']) : ''; ?>"
                               required>
                        <div class="invalid-feedback">
                            Please enter contact person name
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               required>
                        <div class="invalid-feedback">
                            Please enter a valid email address
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Phone *</label>
                        <input type="tel" 
                               class="form-control" 
                               id="phone" 
                               name="phone"
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                               required>
                        <div class="invalid-feedback">
                            Please enter phone number
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="abn" class="form-label">ABN</label>
                        <input type="text" 
                               class="form-control" 
                               id="abn" 
                               name="abn"
                               value="<?php echo isset($_POST['abn']) ? htmlspecialchars($_POST['abn']) : ''; ?>"
                               pattern="[0-9]{11}">
                        <div class="invalid-feedback">
                            Please enter a valid 11-digit ABN
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" 
                                  id="address" 
                                  name="address" 
                                  rows="3"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Supplier
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

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

<?php require_once '../../includes/footer.php'; ?>
