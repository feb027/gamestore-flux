<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/css/header.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/css/footer.css" rel="stylesheet">
    <?php echo $additional_css ?? ''; ?>
    <link href="<?php echo BASE_URL; ?>/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Top bar -->
    <div class="top-bar">
        <div class="nav-container">
            <div class="top-bar-content">
                <a href="mailto:support@gamestore.com">
                    <i class="bi bi-envelope"></i>
                    <span>support@gamestore.com</span>
                </a>
                <a href="#">
                    <i class="bi bi-clock"></i>
                    <span>24/7 Support</span>
                </a>
                <a href="#"><i class="bi bi-facebook"></i></a>
                <a href="#"><i class="bi bi-twitter-x"></i></a>
                <a href="#"><i class="bi bi-instagram"></i></a>
                <a href="#"><i class="bi bi-discord"></i></a>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="main-nav">
        <div class="nav-container">
            <!-- Brand -->
            <a href="<?php echo BASE_URL; ?>/index.php" class="brand-logo">
                <i class="bi bi-controller"></i>
                Game Store
            </a>

            <!-- Search -->
            <form class="search-form" action="<?php echo BASE_URL; ?>/pages/products.php" method="GET">
                <input type="search" 
                       class="search-input" 
                       name="search" 
                       placeholder="Search games..."
                       value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>"
                       aria-label="Search">
            </form>

            <!-- Navigation Links -->
            <ul class="nav-links">
                <li>
                    <a href="<?php echo BASE_URL; ?>/index.php" 
                       class="<?php echo ($_SERVER['PHP_SELF'] == '/index.php' ? 'active' : ''); ?>">
                        <i class="bi bi-house"></i>
                        Home
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/pages/products.php"
                       class="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/products.php') !== false ? 'active' : ''); ?>">
                        <i class="bi bi-grid-3x3-gap"></i>
                        Games
                    </a>
                </li>
                <li>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#genresModal">
                        <i class="bi bi-collection"></i>
                        Genres
                    </a>
                </li>
                <?php if (isLoggedIn()): ?>
                    <li>
                        <a href="<?php echo BASE_URL; ?>/pages/cart.php" class="cart-link">
                            <i class="bi bi-cart2"></i>
                            <?php
                            if (isLoggedIn()):
                                $cart_conn = getDbConnection();
                                $user_id = getCurrentUserId();
                                $cart_stmt = $cart_conn->prepare('SELECT SUM(quantity) as total FROM cart_items WHERE user_id = ?');
                                $cart_stmt->bind_param('i', $user_id);
                                $cart_stmt->execute();
                                $cart_result = $cart_stmt->get_result();
                                $total = $cart_result->fetch_assoc()['total'];
                                $cart_stmt->close();
                                if ($total > 0):
                            ?>
                                <span class="cart-badge"><?php echo $total; ?></span>
                            <?php 
                                endif;
                            endif; 
                            ?>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <?php echo getCurrentUsername(); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/profile.php">
                                <i class="bi bi-person"></i>Profile
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/order-history.php">
                                <i class="bi bi-clock-history"></i>Order History
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/pages/logout.php">
                                    <i class="bi bi-box-arrow-right"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li>
                        <a href="<?php echo BASE_URL; ?>/pages/login.php">
                            <i class="bi bi-box-arrow-in-right"></i>
                            Login
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>/pages/register.php">
                            <i class="bi bi-person-plus"></i>
                            Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Genres Modal -->
    <div class="modal fade" id="genresModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Game Genres</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <a href="<?php echo BASE_URL; ?>/pages/products.php?genre=Action" class="text-decoration-none">
                                <div class="genre-card">
                                    <i class="bi bi-lightning-charge"></i> Action
                                </div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="<?php echo BASE_URL; ?>/pages/products.php?genre=Adventure" class="text-decoration-none">
                                <div class="genre-card">
                                    <i class="bi bi-map"></i> Adventure
                                </div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="<?php echo BASE_URL; ?>/pages/products.php?genre=RPG" class="text-decoration-none">
                                <div class="genre-card">
                                    <i class="bi bi-shield"></i> RPG
                                </div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="<?php echo BASE_URL; ?>/pages/products.php?genre=Strategy" class="text-decoration-none">
                                <div class="genre-card">
                                    <i class="bi bi-diagram-3"></i> Strategy
                                </div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="<?php echo BASE_URL; ?>/pages/products.php?genre=Sports" class="text-decoration-none">
                                <div class="genre-card">
                                    <i class="bi bi-trophy"></i> Sports
                                </div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="<?php echo BASE_URL; ?>/pages/products.php?genre=Racing" class="text-decoration-none">
                                <div class="genre-card">
                                    <i class="bi bi-flag"></i> Racing
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_messages'])): ?>
        <?php foreach ($_SESSION['flash_messages'] as $type => $message): ?>
            <div class="alert alert-<?php echo $type === 'error' ? 'danger' : $type; ?> alert-dismissible fade show m-0">
                <div class="container">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
            <?php unset($_SESSION['flash_messages'][$type]); ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (isAdmin()): ?>
        <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="admin-panel-button">
            <i class="bi bi-gear-fill"></i>
            <span>Admin Panel</span>
        </a>
        <style>
            .admin-panel-button {
                position: fixed;
                bottom: 2rem;
                right: 2rem;
                background: var(--bs-primary);
                color: white;
                padding: 1rem;
                border-radius: 15px;
                text-decoration: none;
                box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
                display: flex;
                align-items: center;
                gap: 0.5rem;
                z-index: 1000;
                transition: all 0.2s;
            }

            .admin-panel-button:hover {
                background: var(--bs-primary-dark, #0a58ca);
                color: white;
                transform: translateY(-2px);
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            }

            .admin-panel-button i {
                font-size: 1.2rem;
            }

            @media (max-width: 768px) {
                .admin-panel-button span {
                    display: none;
                }
                
                .admin-panel-button {
                    padding: 1rem;
                    border-radius: 50%;
                }
            }
        </style>
    <?php endif; ?>