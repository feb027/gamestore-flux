<?php
require_once '../includes/config.php';

// Require login for removing from cart
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? null;
    $user_id = getCurrentUserId();

    if ($product_id) {
        $conn = getDbConnection();
        
        // Remove item from cart
        $stmt = $conn->prepare('DELETE FROM cart_items WHERE user_id = ? AND product_id = ?');
        $stmt->bind_param('ii', $user_id, $product_id);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Item removed from cart successfully!');
        } else {
            setFlashMessage('danger', 'Failed to remove item from cart. Please try again.');
        }
        
        $conn->close();
    }
}

// Redirect back to cart
redirect('/pages/cart.php');
?> 