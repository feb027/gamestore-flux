<?php
require_once '../includes/config.php';

// Clear the cart
$_SESSION['cart'] = [];
setFlashMessage('success', 'Cart cleared successfully');

// Redirect back to cart
redirect('/pages/cart.php');
?> 