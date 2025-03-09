<?php
require_once '../includes/config.php';

// Require admin login
if (!isAdmin()) {
    setFlashMessage('error', 'Access denied. Admin privileges required.');
    redirect('/index.php');
}

$conn = getDbConnection();

// Get filter parameters
$search = $_GET['search'] ?? '';
$genre = $_GET['genre'] ?? 'all';
$stock = $_GET['stock'] ?? 'all';
$sort = $_GET['sort'] ?? 'newest';

// Get unique genres for filter
$genres_query = "SELECT DISTINCT genre FROM products WHERE genre IS NOT NULL ORDER BY genre";
$genres = $conn->query($genres_query)->fetch_all(MYSQLI_ASSOC);

// Base query
$query = "
    SELECT 
        p.*,
        (SELECT COUNT(*) FROM order_items oi 
         JOIN orders o ON oi.order_id = o.id 
         WHERE oi.product_id = p.id AND o.status = 'completed') as times_sold
    FROM products p
    WHERE 1=1
";

// Apply filters
$params = [];
$types = '';

if ($search) {
    $search = "%$search%";
    $query .= " AND (p.title LIKE ? OR p.description LIKE ? OR p.developer LIKE ? OR p.publisher LIKE ?)";
    $params = array_merge($params, [$search, $search, $search, $search]);
    $types .= 'ssss';
}

if ($genre !== 'all') {
    $query .= " AND p.genre = ?";
    $params[] = $genre;
    $types .= 's';
}

if ($stock !== 'all') {
    $query .= " AND p.stock_status = ?";
    $params[] = $stock;
    $types .= 's';
}

// Apply sorting
switch ($sort) {
    case 'title_asc':
        $query .= " ORDER BY p.title ASC";
        break;
    case 'title_desc':
        $query .= " ORDER BY p.title DESC";
        break;
    case 'price_low':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'bestselling':
        $query .= " ORDER BY times_sold DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY p.created_at ASC";
        break;
    default: // newest
        $query .= " ORDER BY p.created_at DESC";
}

// Prepare and execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get product statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_products,
        SUM(CASE WHEN stock_status = 'in_stock' THEN 1 ELSE 0 END) as in_stock,
        SUM(CASE WHEN stock_status = 'out_of_stock' THEN 1 ELSE 0 END) as out_of_stock,
        SUM(CASE WHEN stock_status = 'coming_soon' THEN 1 ELSE 0 END) as coming_soon,
        (SELECT COUNT(DISTINCT genre) FROM products WHERE genre IS NOT NULL) as total_genres
    FROM products
";
$stats = $conn->query($stats_query)->fetch_assoc();

$additional_css = '<style>
.product-filters {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    margin-bottom: 1.5rem;
}

.product-table {
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.product-table th {
    background: #f8f9fa;
    font-weight: 600;
}

.product-image {
    width: 48px;
    height: 48px;
    object-fit: cover;
    border-radius: 8px;
}

.stock-badge {
    padding: 0.35rem 0.75rem;
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 500;
}

.stock-in_stock {
    background: #d4edda;
    color: #155724;
}

.stock-out_of_stock {
    background: #f8d7da;
    color: #721c24;
}

.stock-coming_soon {
    background: #fff3cd;
    color: #856404;
}

.product-actions .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.stats-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    height: 100%;
}

.stats-icon {
    width: 48px;
    height: 48px;
    background: var(--bs-primary);
    color: white;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.stats-number {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.stats-label {
    color: #6c757d;
    font-size: 0.875rem;
}

.modal-body {
    max-height: 70vh;
    overflow-y: auto;
}

.image-preview {
    max-width: 200px;
    max-height: 200px;
    object-fit: cover;
    border-radius: 8px;
    margin-top: 1rem;
}

.admin-dashboard {
    padding: 2rem 0;
    background: #f8f9fa;
    min-height: calc(100vh - 60px);
}

.admin-nav {
    background: white;
    padding: 1rem;
    border-radius: 15px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    margin-bottom: 2rem;
}

.admin-nav-list {
    display: flex;
    gap: 1rem;
    list-style: none;
    padding: 0;
    margin: 0;
}

.admin-nav-link {
    color: #6c757d;
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    transition: all 0.2s;
}

.admin-nav-link:hover {
    background: #f8f9fa;
    color: var(--bs-primary);
}

.admin-nav-link.active {
    background: var(--bs-primary);
    color: white;
}

@media (max-width: 768px) {
    .admin-nav-list {
        flex-wrap: wrap;
    }
    
    .admin-nav-link {
        width: 100%;
    }
}
</style>';

include '../includes/header.php';
?>

<div class="admin-dashboard">
    <div class="container">
        <!-- Admin Navigation -->
        <nav class="admin-nav">
            <ul class="admin-nav-list">
                <li>
                    <a href="dashboard.php" class="admin-nav-link">
                        <i class="bi bi-graph-up me-2"></i>Dashboard
                    </a>
                </li>
                <li>
                    <a href="orders.php" class="admin-nav-link">
                        <i class="bi bi-cart-check me-2"></i>Orders
                    </a>
                </li>
                <li>
                    <a href="products.php" class="admin-nav-link active">
                        <i class="bi bi-grid me-2"></i>Products
                    </a>
                </li>
                <li>
                    <a href="customers.php" class="admin-nav-link">
                        <i class="bi bi-people me-2"></i>Customers
                    </a>
                </li>
                <li>
                    <a href="reports.php" class="admin-nav-link">
                        <i class="bi bi-file-earmark-text me-2"></i>Reports
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Product Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-primary">
                        <i class="bi bi-grid"></i>
                    </div>
                    <div class="stats-number"><?php echo $stats['total_products']; ?></div>
                    <div class="stats-label">Total Products</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-success">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stats-number"><?php echo $stats['in_stock']; ?></div>
                    <div class="stats-label">In Stock</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-danger">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <div class="stats-number"><?php echo $stats['out_of_stock']; ?></div>
                    <div class="stats-label">Out of Stock</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-info">
                        <i class="bi bi-collection"></i>
                    </div>
                    <div class="stats-number"><?php echo $stats['total_genres']; ?></div>
                    <div class="stats-label">Game Genres</div>
                </div>
            </div>
        </div>

        <!-- Product Filters -->
        <div class="product-filters">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Products</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
                    <i class="bi bi-plus-lg"></i> Add Product
                </button>
            </div>
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search products...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Genre</label>
                    <select class="form-select" name="genre">
                        <option value="all" <?php echo $genre === 'all' ? 'selected' : ''; ?>>All Genres</option>
                        <?php foreach ($genres as $g): ?>
                            <option value="<?php echo htmlspecialchars($g['genre']); ?>" <?php echo $genre === $g['genre'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($g['genre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Stock Status</label>
                    <select class="form-select" name="stock">
                        <option value="all" <?php echo $stock === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="in_stock" <?php echo $stock === 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                        <option value="out_of_stock" <?php echo $stock === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                        <option value="coming_soon" <?php echo $stock === 'coming_soon' ? 'selected' : ''; ?>>Coming Soon</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sort By</label>
                    <select class="form-select" name="sort">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="title_asc" <?php echo $sort === 'title_asc' ? 'selected' : ''; ?>>Title (A-Z)</option>
                        <option value="title_desc" <?php echo $sort === 'title_desc' ? 'selected' : ''; ?>>Title (Z-A)</option>
                        <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price (Low to High)</option>
                        <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price (High to Low)</option>
                        <option value="bestselling" <?php echo $sort === 'bestselling' ? 'selected' : ''; ?>>Best Selling</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>

        <!-- Products Table -->
        <div class="product-table table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 80px">Image</th>
                        <th>Title</th>
                        <th>Genre</th>
                        <th>Price</th>
                        <th>Stock Status</th>
                        <th>Release Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <?php if ($product['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['title']); ?>"
                                         class="product-image">
                                <?php else: ?>
                                    <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                        <i class="bi bi-image text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($product['title']); ?></div>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($product['developer']); ?> / 
                                    <?php echo htmlspecialchars($product['publisher']); ?>
                                </small>
                            </td>
                            <td><?php echo htmlspecialchars($product['genre']); ?></td>
                            <td><?php echo formatIDR($product['price']); ?></td>
                            <td>
                                <span class="stock-badge stock-<?php echo $product['stock_status']; ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $product['stock_status'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($product['release_date'])); ?></td>
                            <td class="product-actions">
                                <button type="button" class="btn btn-sm btn-primary edit-product" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#productModal"
                                        data-product-id="<?php echo $product['id']; ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger delete-product"
                                        data-product-id="<?php echo $product['id']; ?>"
                                        data-product-title="<?php echo htmlspecialchars($product['title']); ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="bi bi-inbox text-muted d-block mb-2" style="font-size: 2rem;"></i>
                                No products found matching your criteria
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="productForm" action="save_product.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" id="productId">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Genre</label>
                            <input type="text" class="form-control" name="genre" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Developer</label>
                            <input type="text" class="form-control" name="developer" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Publisher</label>
                            <input type="text" class="form-control" name="publisher" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Price</label>
                            <input type="number" class="form-control" name="price" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Release Date</label>
                            <input type="date" class="form-control" name="release_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stock Status</label>
                            <select class="form-select" name="stock_status" required>
                                <option value="in_stock">In Stock</option>
                                <option value="out_of_stock">Out of Stock</option>
                                <option value="coming_soon">Coming Soon</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Image</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                            <div id="currentImage"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="4" required></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="productForm" class="btn btn-primary">Save Product</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productModal = document.getElementById('productModal');
    const productForm = document.getElementById('productForm');
    const modalTitle = productModal.querySelector('.modal-title');
    
    // Reset form when modal is closed
    productModal.addEventListener('hidden.bs.modal', function() {
        productForm.reset();
        document.getElementById('productId').value = '';
        document.getElementById('currentImage').innerHTML = '';
        modalTitle.textContent = 'Add Product';
    });

    // Edit product
    document.querySelectorAll('.edit-product').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            modalTitle.textContent = 'Edit Product';
            
            // Fetch product details
            fetch(`get_product.php?id=${productId}`)
                .then(response => response.json())
                .then(product => {
                    document.getElementById('productId').value = product.id;
                    productForm.title.value = product.title;
                    productForm.genre.value = product.genre;
                    productForm.developer.value = product.developer;
                    productForm.publisher.value = product.publisher;
                    productForm.price.value = product.price;
                    productForm.release_date.value = product.release_date;
                    productForm.stock_status.value = product.stock_status;
                    productForm.description.value = product.description;

                    if (product.image_url) {
                        document.getElementById('currentImage').innerHTML = `
                            <img src="${product.image_url}" class="image-preview mt-2" alt="${product.title}">
                        `;
                    }
                })
                .catch(error => {
                    alert('Error loading product details. Please try again.');
                });
        });
    });

    // Delete product
    document.querySelectorAll('.delete-product').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const productTitle = this.dataset.productTitle;
            
            if (confirm(`Are you sure you want to delete "${productTitle}"?`)) {
                fetch('delete_product.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting product. Please try again.');
                    }
                })
                .catch(error => {
                    alert('Error deleting product. Please try again.');
                });
            }
        });
    });

    // Preview image before upload
    productForm.image.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('currentImage').innerHTML = `
                    <img src="${e.target.result}" class="image-preview" alt="Preview">
                `;
            };
            reader.readAsDataURL(file);
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?> 