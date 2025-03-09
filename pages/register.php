<?php
require_once '../includes/config.php';

// If user is already logged in, redirect to home
if (isset($_SESSION['user_id'])) {
    redirect('/pages/products.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate input
    if (empty($username)) {
        $errors[] = 'Username is required';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters long';
    }

    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }

    // If no errors, try to register
    if (empty($errors)) {
        $conn = getDbConnection();
        
        // Check if username exists
        $stmt = $conn->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'Username already exists';
        } else {
            // Check if email exists
            $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors[] = 'Email already registered';
            } else {
                // Create new user
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
                $stmt->bind_param('sss', $username, $email, $password_hash);
                
                if ($stmt->execute()) {
                    // Set success message and redirect to login
                    setFlashMessage('success', 'Registration successful! Please log in.');
                    redirect('/pages/login.php');
                } else {
                    $errors[] = 'Registration failed. Please try again.';
                }
            }
        }
        $conn->close();
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
</style>';

include '../includes/header.php';
?>

<div class="auth-page">
    <div class="container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="auth-title">Create Account</h1>
                <p class="auth-subtitle">Join us to start buying your favorite games</p>
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
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="Email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    <label for="email">Email address</label>
                </div>

                <div class="form-floating">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Password" required>
                    <label for="password">Password</label>
                </div>

                <div class="form-floating">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                           placeholder="Confirm Password" required>
                    <label for="confirm_password">Confirm Password</label>
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-lg mt-3">Create Account</button>

                <div class="auth-footer">
                    Already have an account? <a href="login.php">Log in</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 