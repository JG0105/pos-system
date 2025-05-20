<?php
session_start();
require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'includes/functions.php';

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Process login form
$error = null;
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    $user = new User();
    
    try {
        // Debug information
        $sql = "SELECT * FROM users WHERE username = :username";
        $stmt = Database::getInstance()->getConnection()->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $userRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$userRecord) {
            $error = "Username not found in database";
        } else {
            if($user->login($username, $password)) {
                header("Location: index.php");
                exit();
            } else {
                $error = "Password verification failed";
            }
        }
    } catch(Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Now include the header
require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card mt-5">
            <div class="card-header">
                <h4 class="text-center">Login</h4>
            </div>
            <div class="card-body">
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
