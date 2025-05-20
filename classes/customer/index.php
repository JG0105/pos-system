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

$customer = new Customer();
$customers = $customer->getAll();

// Handle search
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($searchTerm) {
    $customers = $customer->search($searchTerm);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2><i class="fas fa-users"></i> Customers</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="../../index.php" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Customer
            </a>
        </div>
    </div>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?php
            switch($_GET['success']) {
                case '1':
                    echo "Customer added successfully!";
                    break;
                case '2':
                    echo "Customer updated successfully!";
                    break;
                case '3':
                    echo "Customer deleted successfully!";
                    break;
            }
            ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-8">
                    <input type="text" 
                           class="form-control" 
                           name="search" 
                           placeholder="Search customers by name, email, phone or company..."
                           value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if($searchTerm): ?>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Company</th>
                            <th>Contact</th>
                            <th>Sales</th>
                            <th>Total Spent</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($customers && count($customers) > 0): ?>
                            <?php foreach($customers as $cust): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($cust['first_name'] . ' ' . $cust['last_name']); ?>
                                        <?php if($cust['abn']): ?>
                                            <br>
                                            <small class="text-muted">ABN: <?php echo htmlspecialchars($cust['abn']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($cust['company_name'] ?? ''); ?></td>
                                    <td>
                                        <?php if($cust['email']): ?>
                                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($cust['email']); ?><br>
                                        <?php endif; ?>
                                        <?php if($cust['phone']): ?>
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($cust['phone']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo $cust['sales_count']; ?> orders
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($cust['total_spent']): ?>
                                            $<?php echo number_format($cust['total_spent'], 2); ?>
                                        <?php else: ?>
                                            $0.00
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($cust['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="view.php?id=<?php echo $cust['customer_id']; ?>" 
                                               class="btn btn-sm btn-info" 
                                               title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $cust['customer_id']; ?>" 
                                               class="btn btn-sm btn-primary" 
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if($cust['sales_count'] == 0): ?>
                                                <a href="delete.php?id=<?php echo $cust['customer_id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   title="Delete"
                                                   onclick="return confirm('Are you sure you want to delete this customer?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-danger" 
                                                        title="Cannot delete: Customer has sales records" 
                                                        disabled>
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">
                                    <?php if($searchTerm): ?>
                                        No customers found matching your search.
                                    <?php else: ?>
                                        No customers found in the system.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if($customers && count($customers) > 0): ?>
        <div class="card mt-3">
            <div class="card-body">
                <h5 class="card-title">Customer Summary</h5>
                <?php
                $totalCustomers = count($customers);
                $activeCustomers = array_filter($customers, function($cust) {
                    return $cust['sales_count'] > 0;
                });
                $totalSales = array_sum(array_column($customers, 'sales_count'));
                $totalRevenue = array_sum(array_column($customers, 'total_spent'));
                ?>
                <div class="row">
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Total Customers</h6>
                                <p class="card-text h4"><?php echo $totalCustomers; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Active Customers</h6>
                                <p class="card-text h4"><?php echo count($activeCustomers); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Total Orders</h6>
                                <p class="card-text h4"><?php echo $totalSales; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Total Revenue</h6>
                                <p class="card-text h4">$<?php echo number_format($totalRevenue, 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
