<?php
require_once 'includes/config.php';

// Set additional CSS before including header
$additional_css = '<link href="' . BASE_URL . '/css/home.css" rel="stylesheet">';

$conn = getDbConnection();
if (!$conn) {
    setFlashMessage('danger', 'Database connection failed');
    die("Unable to connect to database. Please try again later.");
}

// Get featured products for hero section
$sql = "SELECT id, title, image_url FROM products ORDER BY RAND() LIMIT 4";
$heroGames = $conn->query($sql);

// Get latest releases
$sql = "SELECT * FROM products ORDER BY release_date DESC LIMIT 6";
$result = $conn->query($sql);

include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-container">
        <div class="hero-content">
            <h1 class="hero-title">
                Level Up Your 
                <span>Gaming Experience</span>
            </h1>
            <p class="hero-subtitle">
                Discover an epic collection of digital games at unbeatable prices. 
                From thrilling adventures to competitive multiplayer, find your next gaming obsession today.
            </p>
            <div class="hero-buttons">
                <a href="<?php echo BASE_URL; ?>/pages/products.php" class="btn hero-btn hero-btn-primary">
                    <i class="bi bi-controller"></i>
                    Browse Games
                </a>
                <a href="#latest-releases" class="btn hero-btn hero-btn-outline">
                    <i class="bi bi-arrow-down-circle"></i>
                    Latest Releases
                </a>
            </div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <span class="hero-stat-number">500+</span>
                    <span class="hero-stat-label">Games Available</span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat-number">50K+</span>
                    <span class="hero-stat-label">Happy Gamers</span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat-number">24/7</span>
                    <span class="hero-stat-label">Support</span>
                </div>
            </div>
        </div>
        <div class="hero-image">
            <div class="hero-image-grid">
                <?php while ($game = $heroGames->fetch_assoc()): ?>
                    <div class="hero-game-card">
                        <img src="<?php echo BASE_URL . ($game['image_url'] ?? '/images/placeholder.jpg'); ?>" 
                             alt="<?php echo sanitize($game['title']); ?>">
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</section>

<!-- Latest Releases Section -->
<section class="container py-5" id="latest-releases">
    <h2 class="section-title">Latest Releases</h2>
    
    <div class="row row-cols-1 row-cols-md-3 g-4 game-grid">
        <?php while ($game = $result->fetch_assoc()): ?>
            <div class="col">
                <div class="card game-card h-100">
                    <img src="<?php echo BASE_URL . ($game['image_url'] ?? '/images/placeholder.jpg'); ?>" 
                         class="card-img-top" 
                         alt="<?php echo sanitize($game['title']); ?>">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?php echo sanitize($game['title']); ?></h5>
                        <p class="card-text"><?php echo substr(sanitize($game['description']), 0, 100) . '...'; ?></p>
                        
                        <div class="game-meta">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="price"><?php echo formatIDR($game['price']); ?></span>
                                <a href="<?php echo BASE_URL; ?>/pages/products.php?genre=<?php echo urlencode($game['genre']); ?>" 
                                   class="genre-badge text-decoration-none">
                                    <?php echo sanitize($game['genre']); ?>
                                </a>
                            </div>
                            
                            <div class="d-flex justify-content-between gap-2">
                                <a href="<?php echo BASE_URL; ?>/pages/product-detail.php?id=<?php echo $game['id']; ?>" 
                                   class="btn btn-view-details flex-grow-1">View Details</a>
                                <form action="<?php echo BASE_URL; ?>/pages/cart-actions.php" method="POST" class="flex-grow-1">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?php echo $game['id']; ?>">
                                    <button type="submit" class="btn btn-add-cart w-100">
                                        <i class="bi bi-cart-plus"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <div class="text-center mt-5">
        <a href="<?php echo BASE_URL; ?>/pages/products.php" class="btn btn-lg btn-outline-primary">
            View All Games
        </a>
    </div>
</section>

<?php
include 'includes/footer.php';
?> 