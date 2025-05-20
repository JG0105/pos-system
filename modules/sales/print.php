<?php
session_start();
// Set timezone to Adelaide/Australia
date_default_timezone_set('Australia/Adelaide');

require_once '../../config/database.php';
require_once '../../classes/Sales.php';
require_once '../../includes/functions.php';

// Company Details
define('COMPANY_NAME', 'Gawler Irrigation');
define('COMPANY_ADDRESS', 'Lot 11 Paxton Street');
define('COMPANY_CITY', 'Willaston');
define('COMPANY_STATE', 'SA');
define('COMPANY_POSTCODE', '5118');
define('COMPANY_PHONE', '08 8523 2350');
define('COMPANY_ABN', '13 068 715 828');

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Check if ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid sale ID');
}

$sale_id = (int)$_GET['id'];
$sales = new Sales();

// Get sale data
$sale_data = $sales->getById($sale_id);
if(!$sale_data) {
    die('Sale not found');
}

$print_type = isset($_GET['type']) ? $_GET['type'] : 'invoice';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $print_type == 'receipt' ? 'Receipt' : 'Tax Invoice'; ?> #<?php echo 'INV-' . str_pad($sale_data['sale_id'], 6, '0', STR_PAD_LEFT); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
            .page-break { page-break-before: always; }
            body { margin: 0; padding: 0; }
        }

        /* Common Styles */
        body {
            font-family: Arial, sans-serif;
            line-height: 1.4;
            color: #000;
        }

        /* Receipt Styles */
        <?php if($print_type == 'receipt'): ?>
        body {
            width: 80mm;
            margin: 0 auto;
            font-size: 12px;
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 10px;
        }
        .tax-invoice-title {
            margin: 10px 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 5px;
            font-weight: bold;
            font-size: 14px;
        }
        .receipt-table {
            width: 100%;
            margin-bottom: 10px;
        }
        .receipt-table td {
            padding: 2px 0;
        }
        .receipt-total {
            border-top: 1px dashed #000;
            margin-top: 10px;
            padding-top: 10px;
        }
        .receipt-footer {
            text-align: center;
            margin-top: 20px;
            border-top: 1px dashed #000;
            padding-top: 10px;
        }
        <?php else: ?>
        /* A4 Invoice Styles */
        body {
            font-size: 14px;
            background: #fff;
        }
        .invoice-header {
            border-bottom: 2px solid #000;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }
        .tax-invoice-title {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            border: 2px solid #000;
            padding: 5px;
        }
        .company-info {
            margin-bottom: 20px;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .totals-table {
            margin-top: 20px;
            width: 100%;
            max-width: 400px;
            margin-left: auto;
        }
        <?php endif; ?>
    </style>
</head>
<body>
    <div class="<?php echo $print_type == 'receipt' ? 'receipt-container' : 'container mt-4'; ?>">
        <div class="no-print mb-4">
            <button onclick="printAndClose()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print <?php echo ucfirst($print_type); ?>
            </button>
            <button onclick="window.close();" class="btn btn-secondary">
                <i class="fas fa-times"></i> Close
            </button>
        </div>

        <?php if($print_type == 'receipt'): ?>
        <!-- Receipt Format -->
        <div class="receipt-header">
            <h3><?php echo COMPANY_NAME; ?></h3>
            <div class="tax-invoice-title">TAX INVOICE</div>
            <p>
                <?php echo COMPANY_ADDRESS; ?><br>
                <?php echo COMPANY_CITY . ' ' . COMPANY_STATE . ' ' . COMPANY_POSTCODE; ?><br>
                Phone: <?php echo COMPANY_PHONE; ?><br>
                ABN: <?php echo COMPANY_ABN; ?>
            </p>
            <p>
                Receipt #<?php echo 'INV-' . str_pad($sale_data['sale_id'], 6, '0', STR_PAD_LEFT); ?><br>
                Date: <?php echo date('d/m/Y H:i'); ?>
            </p>
        </div>

        <table class="receipt-table">
            <?php foreach($sale_data['items'] as $item): ?>
                <tr>
                    <td colspan="2"><?php echo htmlspecialchars($item['product_name']); ?></td>
                </tr>
                <tr>
                    <td><?php echo $item['quantity'] . ' @ $' . number_format($item['unit_price'], 2); ?></td>
                    <td align="right">$<?php echo number_format($item['total_amount'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <div class="receipt-total">
            <table width="100%">
                <tr>
                    <td>Subtotal:</td>
                    <td align="right">$<?php echo number_format($sale_data['subtotal'], 2); ?></td>
                </tr>
                <tr>
                    <td>GST:</td>
                    <td align="right">$<?php echo number_format($sale_data['tax_amount'], 2); ?></td>
                </tr>
                <tr>
                    <td><strong>Total:</strong></td>
                    <td align="right"><strong>$<?php echo number_format($sale_data['total_amount'], 2); ?></strong></td>
                </tr>
            </table>
        </div>

        <div class="receipt-footer">
            <p>Payment Method: <?php echo ucfirst(str_replace('_', ' ', $sale_data['payment_method'])); ?></p>
            <p>Thank you for shopping with us!</p>
            <p><?php echo date('d/m/Y H:i'); ?></p>
        </div>

        <?php else: ?>
        <!-- A4 Tax Invoice Format -->
        <div class="tax-invoice-title">
            TAX INVOICE
        </div>

        <div class="company-info">
            <h2><?php echo COMPANY_NAME; ?></h2>
            <p>
                <?php echo COMPANY_ADDRESS; ?><br>
                <?php echo COMPANY_CITY . ' ' . COMPANY_STATE . ' ' . COMPANY_POSTCODE; ?><br>
                Phone: <?php echo COMPANY_PHONE; ?><br>
                <strong>ABN: <?php echo COMPANY_ABN; ?></strong>
            </p>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <h5>Bill To:</h5>
                <?php if($sale_data['customer_id']): ?>
                    <p>
                        <?php 
                        if($sale_data['company_name']) {
                            echo htmlspecialchars($sale_data['company_name']) . "<br>";
                        }
                        echo htmlspecialchars($sale_data['first_name'] . ' ' . $sale_data['last_name']);
                        ?>
                    </p>
                <?php else: ?>
                    <p>Walk-in Customer</p>
                <?php endif; ?>
            </div>
            <div class="col-md-6 text-end">
                <p>
                    <strong>Invoice #:</strong> <?php echo 'INV-' . str_pad($sale_data['sale_id'], 6, '0', STR_PAD_LEFT); ?><br>
                    <strong>Date:</strong> <?php echo date('d/m/Y'); ?><br>
                    <strong>Payment Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $sale_data['payment_method'])); ?>
                </p>
            </div>
        </div>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">GST</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($sale_data['items'] as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                        <td class="text-end">$<?php echo number_format($item['unit_price'], 2); ?></td>
                        <td class="text-end">$<?php echo number_format($item['tax_amount'], 2); ?></td>
                        <td class="text-end">$<?php echo number_format($item['total_amount'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals-table">
            <table class="table table-borderless text-end">
                <tr>
                    <td><strong>Subtotal:</strong></td>
                    <td width="150">$<?php echo number_format($sale_data['subtotal'], 2); ?></td>
                </tr>
                <tr>
                    <td><strong>GST:</strong></td>
                    <td>$<?php echo number_format($sale_data['tax_amount'], 2); ?></td>
                </tr>
                <tr class="total-line">
                    <td><strong>Total Amount:</strong></td>
                    <td><strong>$<?php echo number_format($sale_data['total_amount'], 2); ?></strong></td>
                </tr>
            </table>
        </div>

        <div class="mt-5 text-center">
            <p>Thank you for your business!</p>
            <p class="small"><?php echo date('d/m/Y H:i'); ?></p>
        </div>
        <?php endif; ?>
    </div>

    <script>
    function printAndClose() {
        window.print();
        // After printing, close this window
        window.onafterprint = function() {
            window.close();
        };
    }

    // Close window if escape key is pressed
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            window.close();
        }
    });
    </script>
</body>
</html>
