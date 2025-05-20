<?php
// Security and Validation Functions

/**
 * Sanitize user input
 * @param string $data
 * @return string
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Generate random string
 * @param int $length
 * @return string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

/**
 * Format currency
 * @param float $amount
 * @return string
 */
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Format date
 * @param string $date
 * @return string
 */
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

/**
 * Check if user is logged in
 * @return boolean
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect to a URL
 * @param string $url
 */
function redirect($url) {
    header("Location: " . SITE_URL . $url);
    exit();
}

/**
 * Display error message
 * @param string $message
 * @return string
 */
function showError($message) {
    return "<div class='alert alert-danger' role='alert'>$message</div>";
}

/**
 * Display success message
 * @param string $message
 * @return string
 */
function showSuccess($message) {
    return "<div class='alert alert-success' role='alert'>$message</div>";
}

/**
 * Log system activity
 * @param string $action
 * @param string $description
 */
function logActivity($action, $description) {
    // We'll implement this later when we create the activity_log table
    // For now, it just writes to the PHP error log
    error_log("Activity: [$action] $description");
}
?>
