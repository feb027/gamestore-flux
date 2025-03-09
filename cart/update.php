<?php
require_once '../includes/config.php';

// Require login for updating cart
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? null;
    $quantity = intval($_POST['quantity'] ?? 1);
    $user_id = getCurrentUserId();

    if ($product_id && $quantity > 0) {
        $conn = getDbConnection();
        
        // Update cart item quantity
        $stmt = $conn->prepare('UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?');
        $stmt->bind_param('iii', $quantity, $user_id, $product_id);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Cart updated successfully!');
        } else {
            setFlashMessage('danger', 'Failed to update cart. Please try again.');
        }
        
        $conn->close();
    }
}

// Redirect back to cart
redirect('/pages/cart.php');
?> 