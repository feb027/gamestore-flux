<?php
require_once '../includes/config.php';

// Require login
requireLogin();

$conn = getDbConnection();
$user_id = getCurrentUserId();

// Create upload directory if it doesn't exist
$upload_dir = '../uploads/profile_images/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Get user data
$user_query = "SELECT id, username, email, profile_image, created_at FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get user's total orders and total spent
$stats_query = "
    SELECT 
        COUNT(DISTINCT o.id) as total_orders,
        SUM(o.total_amount) as total_spent,
        COUNT(DISTINCT oi.product_id) as unique_games
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ? AND o.status = 'completed'";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param('i', $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $success = false;
    
    // Validate email
    if (empty($_POST['email'])) {
        $errors[] = "Email is required";
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Validate current password if changing password
    if (!empty($_POST['new_password'])) {
        if (empty($_POST['current_password'])) {
            $errors[] = "Current password is required to set a new password";
        } else {
            // Verify current password
            $verify_query = "SELECT password_hash FROM users WHERE id = ?";
            $verify_stmt = $conn->prepare($verify_query);
            $verify_stmt->bind_param('i', $user_id);
            $verify_stmt->execute();
            $hash = $verify_stmt->get_result()->fetch_assoc()['password_hash'];
            $verify_stmt->close();
            
            if (!password_verify($_POST['current_password'], $hash)) {
                $errors[] = "Current password is incorrect";
            }
        }
        
        // Validate new password
        if (strlen($_POST['new_password']) < 8) {
            $errors[] = "New password must be at least 8 characters long";
        }
    }
    
    // Handle profile image upload
    $profile_image = $user['profile_image']; // Keep existing by default
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['size'] > 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        // CELAH KEAMANAN: Hanya memeriksa ekstensi file, tidak kontennya
        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array(strtolower($file_extension), $allowed_extensions)) {
            $errors[] = "Only JPG, PNG, and GIF images are allowed";
        } elseif ($_FILES['profile_image']['size'] > $max_size) {
            $errors[] = "Image size must be less than 2MB";
        } else {
            // Generate unique filename
            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_path)) {
                $profile_image = 'uploads/profile_images/' . $new_filename;
                
                // Remove old profile image if it exists
                if ($user['profile_image'] && file_exists('../' . $user['profile_image'])) {
                    unlink('../' . $user['profile_image']);
                }
            } else {
                $errors[] = "Failed to upload image";
            }
        }
    }
    
    if (empty($errors)) {
        // Update user information
        $update_fields = ["email = ?", "profile_image = ?"];
        $params = [$_POST['email'], $profile_image];
        $types = "ss";
        
        // Add password update if provided
        if (!empty($_POST['new_password'])) {
            $update_fields[] = "password_hash = ?";
            $params[] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $types .= "s";
        }
        
        // Add user ID to params
        $params[] = $user_id;
        $types .= "i";
        
        $update_query = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param($types, ...$params);
        
        if ($update_stmt->execute()) {
            $success = true;
            setFlashMessage('success', 'Profile updated successfully');
            // Refresh user data
            $stmt = $conn->prepare($user_query);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else {
            $errors[] = "An error occurred while updating your profile";
        }
        
        $update_stmt->close();
    }
}

$additional_css = '<style>
.profile-page {
    padding: 2rem 0;
    background: #f8f9fa;
    min-height: calc(100vh - 200px);
}

.page-header {
    margin-bottom: 2rem;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: #2d3436;
    margin-bottom: 0.5rem;
}

.page-subtitle {
    color: #6c757d;
    font-size: 1.1rem;
}

.profile-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.profile-header {
    background: var(--bs-primary);
    padding: 2rem;
    color: white;
    text-align: center;
}

.profile-avatar {
    width: 100px;
    height: 100px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    overflow: hidden;
}

.profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-avatar i {
    font-size: 3rem;
    color: var(--bs-primary);
}

.profile-username {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.profile-member-since {
    font-size: 0.9rem;
    opacity: 0.9;
}

.profile-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    padding: 1.5rem;
    background: #f8f9fa;
    text-align: center;
}

.stat-item {
    padding: 1rem;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--bs-primary);
    margin-bottom: 0.25rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
}

.profile-body {
    padding: 2rem;
}

.form-section {
    margin-bottom: 2rem;
}

.form-section:last-child {
    margin-bottom: 0;
}

.form-section-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: #2d3436;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e9ecef;
}

.form-floating {
    margin-bottom: 1rem;
}

.form-floating > label {
    color: #6c757d;
}

.password-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    margin-top: 1rem;
}

.password-section .form-floating:last-child {
    margin-bottom: 0;
}

@media (max-width: 768px) {
    .profile-stats {
        grid-template-columns: 1fr;
    }
}

.image-upload-container {
    margin-bottom: 1.5rem;
}

.image-preview {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    overflow: hidden;
    margin: 0 auto 1rem;
    border: 2px solid var(--bs-primary);
    position: relative;
}

.image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.image-preview .placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f0f0f0;
    color: #999;
}

.image-preview .placeholder i {
    font-size: 3rem;
}

.custom-file-upload {
    display: inline-block;
    cursor: pointer;
    padding: 0.5rem 1rem;
    background: var(--bs-primary);
    color: white;
    border-radius: 5px;
    font-size: 0.9rem;
    margin-top: 10px;
}

.custom-file-upload:hover {
    background: var(--bs-primary-dark);
}

.custom-file-upload input {
    display: none;
}
</style>';

include '../includes/header.php';
?>

<div class="profile-page">
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">My Profile</h1>
            <p class="page-subtitle">View and update your account information</p>
        </div>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="profile-card">
                    <!-- Profile Header -->
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <?php if ($user['profile_image']): ?>
                                <img src="<?php echo htmlspecialchars('../' . $user['profile_image']); ?>" alt="Profile picture">
                            <?php else: ?>
                                <i class="bi bi-person"></i>
                            <?php endif; ?>
                        </div>
                        <h2 class="profile-username"><?php echo htmlspecialchars($user['username']); ?></h2>
                        <p class="profile-member-since">
                            Member since <?php echo date('F Y', strtotime($user['created_at'])); ?>
                        </p>
                    </div>

                    <!-- Profile Stats -->
                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $stats['total_orders'] ?? 0; ?></div>
                            <div class="stat-label">Orders</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $stats['unique_games'] ?? 0; ?></div>
                            <div class="stat-label">Games Owned</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo formatIDR($stats['total_spent'] ?? 0); ?></div>
                            <div class="stat-label">Total Spent</div>
                        </div>
                    </div>

                    <!-- Profile Form -->
                    <div class="profile-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <!-- Profile Picture -->
                            <div class="form-section">
                                <h3 class="form-section-title">Profile Picture</h3>
                                <div class="image-upload-container text-center">
                                    <div class="image-preview">
                                        <?php if ($user['profile_image']): ?>
                                            <img id="preview-image" src="<?php echo htmlspecialchars('../' . $user['profile_image']); ?>" alt="Profile picture">
                                        <?php else: ?>
                                            <div id="preview-placeholder" class="placeholder">
                                                <i class="bi bi-person"></i>
                                            </div>
                                            <img id="preview-image" src="" alt="" style="display: none;">
                                        <?php endif; ?>
                                    </div>
                                    <label class="custom-file-upload">
                                        <input type="file" name="profile_image" id="profile-image-input" accept="image/jpeg, image/png, image/gif">
                                        <i class="bi bi-camera me-1"></i> Change Picture
                                    </label>
                                    <p class="text-muted small mt-2">Max size: 2MB. Allowed formats: JPG, PNG, GIF</p>
                                </div>
                            </div>

                            <!-- Account Information -->
                            <div class="form-section">
                                <h3 class="form-section-title">Account Information</h3>
                                
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="username" 
                                           value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                    <label for="username">Username</label>
                                </div>
                                
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    <label for="email">Email Address</label>
                                </div>
                            </div>

                            <!-- Change Password -->
                            <div class="form-section">
                                <h3 class="form-section-title">Change Password</h3>
                                <div class="password-section">
                                    <div class="form-floating">
                                        <input type="password" class="form-control" id="current_password" 
                                               name="current_password">
                                        <label for="current_password">Current Password</label>
                                    </div>
                                    
                                    <div class="form-floating">
                                        <input type="password" class="form-control" id="new_password" 
                                               name="new_password">
                                        <label for="new_password">New Password</label>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>Save Changes
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('profile-image-input').onchange = function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewImage = document.getElementById('preview-image');
            const placeholder = document.getElementById('preview-placeholder');
            
            if (placeholder) {
                placeholder.style.display = 'none';
            }
            
            previewImage.src = e.target.result;
            previewImage.style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
};
</script>

<?php include '../includes/footer.php'; ?>