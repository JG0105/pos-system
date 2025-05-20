<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Supplier.php';
require_once 'includes/functions.php';

// Ensure only logged in users can access this
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Create instance of Supplier class
$supplier = new Supplier();

// Test 1: Create a supplier
echo "<h3>Test 1: Creating a supplier</h3>";
$testData = [
    'company_name' => 'Test Irrigation Supplies',
    'contact_name' => 'John Test',
    'email' => 'test@testirrigation.com',
    'phone' => '0412345678',
    'address' => '123 Test Street, Test City',
    'abn' => '12345678901'
];

$newSupplierId = $supplier->create($testData);
if($newSupplierId) {
    echo "✅ Supplier created successfully with ID: " . $newSupplierId . "<br>";
} else {
    echo "❌ Failed to create supplier<br>";
}

// Test 2: Get supplier by ID
echo "<h3>Test 2: Getting supplier by ID</h3>";
$retrievedSupplier = $supplier->getById($newSupplierId);
if($retrievedSupplier) {
    echo "✅ Retrieved supplier details:<br>";
    echo "<pre>";
    print_r($retrievedSupplier);
    echo "</pre>";
} else {
    echo "❌ Failed to retrieve supplier<br>";
}

// Test 3: Update supplier
echo "<h3>Test 3: Updating supplier</h3>";
$updateData = [
    'company_name' => 'Updated Test Irrigation',
    'contact_name' => 'John Updated',
    'email' => 'updated@testirrigation.com',
    'phone' => '0412345679',
    'address' => '124 Test Street, Test City',
    'abn' => '12345678901',
    'status' => 'active'
];

if($supplier->update($newSupplierId, $updateData)) {
    echo "✅ Supplier updated successfully<br>";
} else {
    echo "❌ Failed to update supplier<br>";
}

// Test 4: Get all suppliers
echo "<h3>Test 4: Getting all suppliers</h3>";
$allSuppliers = $supplier->getAll();
if($allSuppliers) {
    echo "✅ Retrieved all suppliers:<br>";
    echo "<pre>";
    print_r($allSuppliers);
    echo "</pre>";
} else {
    echo "❌ Failed to retrieve suppliers<br>";
}

// Test 5: Delete supplier (commented out for safety)
/*
echo "<h3>Test 5: Deleting supplier</h3>";
if($supplier->delete($newSupplierId)) {
    echo "✅ Supplier deleted successfully<br>";
} else {
    echo "❌ Failed to delete supplier<br>";
}
*/

?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h3 { color: #333; margin-top: 20px; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    .success { color: green; }
    .error { color: red; }
</style>
