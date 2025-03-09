<?php
require_once '../includes/config.php';

// If user is already logged in, redirect to home
if (isset($_SESSION['user_id'])) {
    redirect('/pages/products.php');
}


$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate input
    if (empty($username)) {
        $errors[] = 'Username is required';
    }

    if (empty($password)) {
        $errors[] = 'Password is required';
    }

    // If no errors, try to login
    if (empty($errors)) {
        $conn = getDbConnection();
        
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        $query = "SELECT * FROM users WHERE username = '$username'";  
        $result = $conn->query($query);
        
        if ($result === false) {
            $errors[] = 'Database error occurred';
        } else if ($result->num_rows > 0) {
            // Get the first user found
            $user = $result->fetch_assoc();
                        
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // Redirect to intended page or home
            $redirect_to = $_SESSION['redirect_after_login'] ?? '/pages/products.php';
            unset($_SESSION['redirect_after_login']);
            redirect($redirect_to);
        } else {
            $errors[] = 'Invalid username or password';
        }
    }
}

// Custom CSS
$additional_css = '<style>
.auth-page {
    min-height: calc(100vh - 200px);
    background: #f8f9fa;
    padding: 4rem 0;
}

.auth-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    padding: 2rem;
    max-width: 500px;
    margin: 0 auto;
}

.auth-header {
    text-align: center;
    margin-bottom: 2rem;
}

.auth-title {
    font-size: 2rem;
    font-weight: 700;
    color: #2d3436;
    margin-bottom: 0.5rem;
}

.auth-subtitle {
    color: #6c757d;
}

.form-floating {
    margin-bottom: 1rem;
}

.auth-footer {
    text-align: center;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e9ecef;
}

.remember-me {
    margin: 1rem 0;
}
</style>';

include '../includes/header.php';
?>

<div class="auth-page">
    <div class="container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="auth-title">Welcome Back!</h1>
                <p class="auth-subtitle">Log in to your account to continue shopping</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-floating">
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                    <label for="username">Username</label>
                </div>

                <div class="form-floating">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Password" required>
                    <label for="password">Password</label>
                </div>

                <div class="remember-me">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label" for="remember">
                            Remember me
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-lg">Log In</button>

                <div class="auth-footer">
                    Don't have an account? <a href="register.php">Create one</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 