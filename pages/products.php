<?php
require_once '../includes/config.php';

$conn = connectDB();
if (!$conn) {
    setFlashMessage('danger', 'Database connection failed');
    redirect('../index.php');
}

// Get all available genres for filter
$genres_query = "SELECT DISTINCT genre FROM products ORDER BY genre";
$genres_result = $conn->query($genres_query);
$available_genres = [];
while ($row = $genres_result->fetch_assoc()) {
    if ($row['genre']) {
        $available_genres[] = $row['genre'];
    }
}

// Initialize query parts
$where_conditions = [];
$params = [];
$param_types = '';

// Handle search query
if (!empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $where_conditions[] = '(title LIKE ? OR description LIKE ? OR genre LIKE ?)';
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $param_types .= 'sss';
}

// Handle genre filter
if (!empty($_GET['genre'])) {
    $where_conditions[] = 'genre = ?';
    $params[] = $_GET['genre'];
    $param_types .= 's';
}

// Handle price range filter
$min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : null;

if ($min_price !== null) {
    $where_conditions[] = 'price >= ?';
    $params[] = $min_price;
    $param_types .= 'i';
}
if ($max_price !== null) {
    $where_conditions[] = 'price <= ?';
    $params[] = $max_price;
    $param_types .= 'i';
}

// Handle sorting
$sort_options = [
    'newest' => 'release_date DESC',
    'oldest' => 'release_date ASC',
    'price_asc' => 'price ASC',
    'price_desc' => 'price DESC',
    'title_asc' => 'title ASC',
    'title_desc' => 'title DESC'
];

$sort = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sort_options) 
    ? $_GET['sort'] 
    : 'newest';

// Build the final query
$sql = "SELECT * FROM products";
if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(' AND ', $where_conditions);
}
$sql .= " ORDER BY " . $sort_options[$sort];

// Prepare and execute the query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$additional_css = '<style>
.products-page {
    padding: 2rem 0;
    min-height: calc(100vh - 200px);
    background: #f8f9fa;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: #2d3436;
    margin-bottom: 0.5rem;
}

.filters {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    position: sticky;
    top: 2rem;
}

.filters h3 {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    color: #2d3436;
}

.filter-section {
    margin-bottom: 2rem;
}

.filter-section:last-child {
    margin-bottom: 0;
}

.filter-section h4 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #2d3436;
}

.genre-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.genre-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    border-radius: 8px;
    color: #2d3436;
    text-decoration: none;
    transition: background-color 0.2s;
}

.genre-item:hover {
    background: #f8f9fa;
}

.genre-item.active {
    background: var(--bs-primary);
    color: white;
}

.genre-item i {
    font-size: 1.1rem;
}

.price-range {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    margin-bottom: 1rem;
}

.price-input {
    width: 120px;
}

.game-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
}

.game-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s ease;
}

.game-card:hover {
    transform: translateY(-5px);
}

.game-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.game-info {
    padding: 1.5rem;
}

.game-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #2d3436;
}

.game-description {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 1rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.game-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.game-price {
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--bs-primary);
}

.game-genre {
    background: var(--bs-primary);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.85rem;
    text-decoration: none;
}

.game-actions {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.5rem;
}

.sort-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.sort-select {
    min-width: 200px;
}

@media (max-width: 768px) {
    .filters {
        margin-bottom: 2rem;
    }
}
</style>';

include '../includes/header.php';
?>

<div class="products-page">
    <div class="container">
        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-lg-3">
                <div class="filters">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="m-0">Filters</h3>
                        <?php if (isset($_GET['genre']) || isset($_GET['min_price']) || isset($_GET['max_price'])): ?>
                            <a href="?<?php 
                                $params = [];
                                if (isset($_GET['search'])) $params[] = 'search=' . urlencode($_GET['search']);
                                if (isset($_GET['sort'])) $params[] = 'sort=' . urlencode($_GET['sort']);
                                echo implode('&', $params);
                            ?>" 
                               class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-x-circle me-1"></i>Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Genre Filter -->
                    <div class="filter-section">
                        <h4>Genre</h4>
                        <div class="genre-list">
                            <?php foreach ($available_genres as $genre): ?>
                                <?php
                                    $params = ['genre=' . urlencode($genre)];
                                    if (isset($_GET['sort'])) $params[] = 'sort=' . urlencode($_GET['sort']);
                                    if (isset($_GET['search'])) $params[] = 'search=' . urlencode($_GET['search']);
                                    $query = implode('&', $params);
                                ?>
                                <a href="?<?php echo $query; ?>" 
                                   class="genre-item <?php echo (isset($_GET['genre']) && $_GET['genre'] === $genre) ? 'active' : ''; ?>">
                                    <i class="bi bi-controller"></i>
                                    <?php echo htmlspecialchars($genre); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Price Range Filter -->
                    <div class="filter-section">
                        <h4>Price Range</h4>
                        <form action="" method="GET">
                            <?php if (isset($_GET['genre'])): ?>
                                <input type="hidden" name="genre" value="<?php echo htmlspecialchars($_GET['genre']); ?>">
                            <?php endif; ?>
                            <div class="price-range">
                                <input type="number" name="min_price" class="form-control price-input" 
                                       placeholder="Min" value="<?php echo $min_price ?? ''; ?>">
                                <span>-</span>
                                <input type="number" name="max_price" class="form-control price-input" 
                                       placeholder="Max" value="<?php echo $max_price ?? ''; ?>">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Apply</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Games Grid -->
            <div class="col-lg-9">
                <div class="sort-section">
                    <h1 class="page-title">Our Games</h1>
                    <select class="form-select sort-select" onchange="window.location.href=this.value">
                        <option value="?sort=newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="?sort=price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="?sort=price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="?sort=title_asc" <?php echo $sort === 'title_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                        <option value="?sort=title_desc" <?php echo $sort === 'title_desc' ? 'selected' : ''; ?>>Name: Z to A</option>
                    </select>
                </div>

                <?php if ($result->num_rows === 0): ?>
                    <div class="alert alert-info">
                        <?php 
                        if (isset($_GET['search'])) {
                            echo 'No results found for: ' . $_GET['search'];
                        } else {
                            echo 'No games found matching your criteria.';
                        }
                        ?>
                    </div>
                <?php else: ?>
                    <?php if (isset($_GET['search'])): ?>
                        <div class="alert alert-info">
                            Showing results for: <?php echo $_GET['search']; ?>
                        </div>
                    <?php endif; ?>
                    <div class="game-grid">
                        <?php while ($game = $result->fetch_assoc()): ?>
                            <div class="game-card">
                                <img src="<?php echo BASE_URL . ($game['image_url'] ?? '/images/games/placeholder.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($game['title']); ?>"
                                     class="game-image">
                                <div class="game-info">
                                    <h2 class="game-title"><?php echo htmlspecialchars($game['title']); ?></h2>
                                    <p class="game-description"><?php echo htmlspecialchars($game['description']); ?></p>
                                    <div class="game-meta">
                                        <span class="game-price"><?php echo formatIDR($game['price']); ?></span>
                                        <a href="?genre=<?php echo urlencode($game['genre']); ?>" 
                                           class="game-genre">
                                            <?php echo htmlspecialchars($game['genre']); ?>
                                        </a>
                                    </div>
                                    <div class="game-actions">
                                        <a href="product-detail.php?id=<?php echo $game['id']; ?>" 
                                           class="btn btn-outline-primary">
                                            <i class="bi bi-info-circle me-2"></i>View Details
                                        </a>
                                        <?php if (isLoggedIn()): ?>
                                            <form action="../cart/add.php" method="POST">
                                                <input type="hidden" name="product_id" value="<?php echo $game['id']; ?>">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="bi bi-cart-plus me-2"></i>Add to Cart
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <a href="../pages/login.php" class="btn btn-primary">
                                                <i class="bi bi-box-arrow-in-right me-2"></i>Login to Buy
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Close only the statement
if (isset($stmt)) {
    $stmt->close();
}

include '../includes/footer.php';
?>