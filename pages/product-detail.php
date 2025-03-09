<?php
require_once '../includes/config.php';

// Get product ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    setFlashMessage('danger', 'Invalid product ID');
    redirect('products.php');
}

$conn = connectDB();
if (!$conn) {
    setFlashMessage('danger', 'Database connection failed');
    redirect('products.php');
}

// Get product details
$sql = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$game = $result->fetch_assoc();

if (!$game) {
    $stmt->close();
    $conn->close();
    setFlashMessage('danger', 'Product not found');
    redirect('products.php');
}

// Get related games (same genre, excluding current game)
$related_sql = "SELECT * FROM products WHERE genre = ? AND id != ? ORDER BY release_date DESC LIMIT 4";
$related_stmt = $conn->prepare($related_sql);
$related_stmt->bind_param("si", $game['genre'], $game['id']);
$related_stmt->execute();
$related_games = $related_stmt->get_result();

// Custom CSS for product detail page
$additional_css = '<style>
.product-detail {
    padding: 2rem 0;
}


.product-image-container {
    position: relative;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    background: #f8f9fa;
}

.product-image {
    width: 100%;
    height: 500px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.product-image:hover {
    transform: scale(1.02);
}

.product-info {
    padding: 1rem 0;
}

.product-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #2d3436;
}

.product-meta {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}

.meta-item {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 10px;
    transition: transform 0.2s ease;
}

.meta-item:hover {
    transform: translateY(-2px);
}

.meta-label {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.meta-value {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2d3436;
}

.product-description {
    font-size: 1.1rem;
    line-height: 1.7;
    color: #4a4a4a;
    margin-bottom: 2rem;
}

.product-price {
    font-size: 2rem;
    font-weight: 700;
    color: var(--bs-primary);
    margin-bottom: 1.5rem;
}

.purchase-section {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 15px;
    margin-bottom: 2rem;
}

.quantity-input {
    width: 100px;
    text-align: center;
    margin-right: 1rem;
}

.stock-status {
    display: inline-block;
    padding: 0.25rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

.stock-status.in-stock {
    background: #d4edda;
    color: #155724;
}

.stock-status.out-of-stock {
    background: #f8d7da;
    color: #721c24;
}

.related-games {
    margin-top: 4rem;
}

.related-games h2 {
    font-size: 1.75rem;
    font-weight: 600;
    margin-bottom: 2rem;
    color: #2d3436;
}

.related-game-card {
    border: none;
    border-radius: 15px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.related-game-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.related-game-card img {
    height: 200px;
    object-fit: cover;
}

.breadcrumb-item {
   color: rgba(var(--bs-link-color-rgb),var(--bs-link-opacity,1)); 
   text-decoration: none;
}


@media (max-width: 768px) {
    .product-image {
        height: 300px;
    }
    
    .product-title {
        font-size: 2rem;
    }
    
    .product-meta {
        grid-template-columns: 1fr;
    }
}
</style>';

include '../includes/header.php';
?>

<div class="product-detail">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/products.php">Games</a></li>
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/products.php?genre=<?php echo urlencode($game['genre']); ?>"><?php echo htmlspecialchars($game['genre']); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($game['title']); ?></li>
            </ol>
        </nav>

        <div class="row g-5">
            <!-- Product Image -->
            <div class="col-lg-6">
                <div class="product-image-container">
                    <img src="<?php echo BASE_URL . ($game['image_url'] ?? '/images/games/placeholder.jpg'); ?>" 
                         class="product-image" 
                         alt="<?php echo htmlspecialchars($game['title']); ?>">
                </div>
            </div>

            <!-- Product Info -->
            <div class="col-lg-6">
                <div class="product-info">
                    <h1 class="product-title"><?php echo htmlspecialchars($game['title']); ?></h1>
                    
                    <div class="product-meta">
                        <div class="meta-item">
                            <div class="meta-label">Developer</div>
                            <div class="meta-value"><?php echo htmlspecialchars($game['developer']); ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Publisher</div>
                            <div class="meta-value"><?php echo htmlspecialchars($game['publisher']); ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Release Date</div>
                            <div class="meta-value"><?php echo date('F j, Y', strtotime($game['release_date'])); ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Genre</div>
                            <div class="meta-value"><?php echo htmlspecialchars($game['genre']); ?></div>
                        </div>
                    </div>

                    <p class="product-description"><?php echo htmlspecialchars($game['description']); ?></p>

                    <div class="purchase-section">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="product-price"><?php echo formatIDR($game['price']); ?></div>
                            <span class="stock-status <?php echo $game['stock_status'] === 'in_stock' ? 'in-stock' : 'out-of-stock'; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $game['stock_status'])); ?>
                            </span>
                        </div>

                        <form action="<?php echo BASE_URL; ?>/cart/add.php" method="post" class="d-flex gap-2">
                            <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
                            <input type="number" name="quantity" value="1" min="1" max="10" 
                                   class="form-control quantity-input" aria-label="Quantity">
                            <button type="submit" class="btn btn-primary flex-grow-1 <?php echo $game['stock_status'] !== 'in_stock' ? 'disabled' : ''; ?>">
                            <?php if (isLoggedIn()): ?>
                                            <form action="<?php echo BASE_URL; ?>/cart/add.php" method="POST">
                                                <input type="hidden" name="product_id" value="<?php echo $game['id']; ?>">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="bi bi-cart-plus me-2"></i>Add to Cart
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <a href="<?php echo BASE_URL; ?>/pages/login.php" class="btn btn-primary">
                                                <i class="bi bi-box-arrow-in-right me-2"></i>Login to Buy
                                            </a>
                                        <?php endif; ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Games -->
        <?php if ($related_games->num_rows > 0): ?>
        <div class="related-games">
            <h2>More <?php echo htmlspecialchars($game['genre']); ?> Games</h2>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                <?php while ($related_game = $related_games->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card related-game-card">
                            <img src="<?php echo BASE_URL . ($related_game['image_url'] ?? '/images/games/placeholder.jpg'); ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($related_game['title']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($related_game['title']); ?></h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="price"><?php echo formatIDR($related_game['price']); ?></span>
                                    <a href="product-detail.php?id=<?php echo $related_game['id']; ?>" 
                                       class="btn btn-outline-primary btn-sm">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$stmt->close();
$related_stmt->close();
$conn->close();
include '../includes/footer.php';
?> 