<?php
require_once '../includes/config.php';

// Require admin login
if (!isAdmin()) {
    setFlashMessage('error', 'Access denied. Admin privileges required.');
    redirect('/admin/products.php');
}

// Get form data
$product_id = $_POST['product_id'] ?? null;
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$price = $_POST['price'] ?? 0;
$genre = $_POST['genre'] ?? '';
$developer = $_POST['developer'] ?? '';
$publisher = $_POST['publisher'] ?? '';
$release_date = $_POST['release_date'] ?? date('Y-m-d');
$stock_status = $_POST['stock_status'] ?? 'in_stock';

$conn = getDbConnection();

// Handle image upload
$image_url = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../uploads/products/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $file_extension;
    $target_path = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
        $image_url = 'uploads/products/' . $filename;
    }
}

try {
    if ($product_id) {
        // Update existing product
        $query = "
            UPDATE products 
            SET title = ?, description = ?, price = ?, genre = ?, 
                developer = ?, publisher = ?, release_date = ?, stock_status = ?
            " . ($image_url ? ", image_url = ?" : "") . "
            WHERE id = ?
        ";
        
        $stmt = $conn->prepare($query);
        
        if ($image_url) {
            $stmt->bind_param('ssdssssssi', $title, $description, $price, $genre, 
                            $developer, $publisher, $release_date, $stock_status, 
                            $image_url, $product_id);
        } else {
            $stmt->bind_param('ssdsssssi', $title, $description, $price, $genre, 
                            $developer, $publisher, $release_date, $stock_status, 
                            $product_id);
        }
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Product updated successfully.');
        } else {
            throw new Exception('Error updating product.');
        }
    } else {
        // Add new product
        $query = "
            INSERT INTO products (title, description, price, genre, developer, 
                                publisher, release_date, stock_status, image_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssdsssssss', $title, $description, $price, $genre, 
                         $developer, $publisher, $release_date, $stock_status, 
                         $image_url);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Product added successfully.');
        } else {
            throw new Exception('Error adding product.');
        }
    }
    
    $stmt->close();
} catch (Exception $e) {
    setFlashMessage('error', $e->getMessage());
}

redirect('/admin/products.php'); 