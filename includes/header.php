<?php 
// Define the root directory path
define('ROOT_PATH', realpath(dirname(dirname(__FILE__))));

// Include core files with absolute paths
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gawler Irrigation POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/style.css" rel="stylesheet">

    <!-- Add these lines for DataTables -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <!-- End of DataTables includes -->

    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            padding: 0.4rem 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-weight: 600;
            font-size: 1rem;
            padding: 0 0.5rem;
            margin-right: 0.5rem;
            white-space: nowrap;
            min-width: 180px;
        }
        .navbar-nav .nav-item {
            margin: 0;
        }
        .navbar-nav .nav-link {
            padding: 0.5rem 0.6rem;
            transition: all 0.3s ease;
            border-radius: 4px;
            display: flex;
            align-items: center;
            font-weight: 500;
            font-size: 0.85rem;
        }
        .navbar-nav .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            color: #17a2b8 !important;
        }
        .navbar-nav .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            color: #17a2b8 !important;
        }
        .navbar-nav .nav-link i {
            margin-right: 6px;
            font-size: 0.95rem;
            width: 16px;
            text-align: center;
        }
        .dropdown-menu {
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 0.5rem;
            margin-top: 0.5rem;
            background-color: #343a40;
        }
        .dropdown-item {
            color: #fff;
            padding: 0.5rem 0.8rem;
            border-radius: 4px;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }
        .dropdown-item:hover {
            background-color: rgba(255,255,255,0.1);
            color: #17a2b8;
        }
        .dropdown-item i {
            margin-right: 6px;
            width: 16px;
            text-align: center;
        }
        .navbar-nav .ms-auto .nav-link {
            padding: 0.5rem 0.6rem;
        }
        .welcome-text {
            font-weight: 500;
            color: rgba(255,255,255,0.8);
            font-size: 0.85rem;
        }
        @media (max-width: 1400px) {
            .navbar-collapse {
                max-width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .navbar-nav {
                flex-wrap: nowrap;
                white-space: nowrap;
            }
        }
        /* Page Header Styles */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #dee2e6;
        }
        .page-header h1 {
            font-size: 1.5rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .page-header h1 i {
            font-size: 1.2rem;
        }
        .page-header .btn {
            padding: 0.375rem 0.75rem;
        }
        .page-header .btn i {
            margin-right: 0.25rem;
        }
        .back-button {
            margin-right: 0.5rem;
        }
        /* Table Styles */
        .table-responsive {
            background: #fff;
            border-radius: 0.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            padding: 0.75rem;
        }
        .badge {
            padding: 0.5em 0.8em;
            font-weight: 500;
        }
        .action-buttons {
            display: flex;
            gap: 0.25rem;
        }
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
            <i class="fas fa-store"></i> Gawler Irrigation POS
        </a>
        <?php if(isset($_SESSION['user_id'])): ?>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/index.php') !== false || $_SERVER['REQUEST_URI'] == '/') ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/modules/categories/') !== false) ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/modules/categories/">
                        <i class="fas fa-tags"></i> Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/modules/products/') !== false) ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/modules/products/">
                        <i class="fas fa-box"></i> Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/modules/customers/') !== false) ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/modules/customers/">
                        <i class="fas fa-users"></i> Customers
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/modules/suppliers/') !== false) ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/modules/suppliers/">
                        <i class="fas fa-truck"></i> Suppliers
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo (strpos($_SERVER['REQUEST_URI'], '/modules/purchase/') !== false) ? 'active' : ''; ?>" 
                       href="#" 
                       id="purchaseDropdown" 
                       role="button" 
                       data-bs-toggle="dropdown" 
                       aria-expanded="false">
                        <i class="fas fa-shopping-basket"></i> Purchases
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="purchaseDropdown">
                        <li>
                            <a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/purchase/">
                                <i class="fas fa-list"></i> All Purchases
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/purchase/add.php">
                                <i class="fas fa-plus"></i> New Purchase
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo (strpos($_SERVER['REQUEST_URI'], '/modules/sales/') !== false) ? 'active' : ''; ?>" 
                       href="#" 
                       id="salesDropdown" 
                       role="button" 
                       data-bs-toggle="dropdown" 
                       aria-expanded="false">
                        <i class="fas fa-shopping-cart"></i> Sales
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="salesDropdown">
                        <li>
                            <a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/sales/">
                                <i class="fas fa-list"></i> All Sales
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/sales/add.php">
                                <i class="fas fa-plus"></i> New Sale
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo (strpos($_SERVER['REQUEST_URI'], '/modules/reports/') !== false) ? 'active' : ''; ?>" 
                       href="#" 
                       id="reportsDropdown" 
                       role="button" 
                       data-bs-toggle="dropdown" 
                       aria-expanded="false">
                        <i class="fas fa-chart-line"></i> Reports
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="reportsDropdown">
                        <li>
                            <a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/reports/sales/">
                                <i class="fas fa-chart-bar"></i> Sales Reports
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/reports/inventory/">
                                <i class="fas fa-boxes"></i> Inventory Reports
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/reports/customers/">
                                <i class="fas fa-users"></i> Customer Reports
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/reports/suppliers/">
                                <i class="fas fa-truck"></i> Supplier Reports
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/reports/gst/">
                                <i class="fas fa-file-invoice-dollar"></i> GST Reports
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/reports/financial/">
                                <i class="fas fa-dollar-sign"></i> Financial Reports
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/modules/user/') !== false) ? 'active' : ''; ?>" 
                       href="<?php echo SITE_URL; ?>/modules/user/">
                        <i class="fas fa-user-cog"></i> Users
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <span class="nav-link welcome-text">
                        <i class="fas fa-user"></i> Welcome, <?php echo $_SESSION['first_name']; ?>
                    </span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</nav>
<div class="container mt-4">

<!-- Make sure these scripts are loaded before the closing body tag -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    $('#dataTable').DataTable({
        destroy: true,
        pageLength: 50,
        order: [[0, 'asc']],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries per page",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
        }
    });
});
</script>
</div>
</body>
</html>
