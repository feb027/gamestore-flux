<?php
require_once '../includes/config.php';

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? '';
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

$conn = connectDB();
if (!$conn) {
    setFlashMessage('danger', 'Database connection failed');
    redirect('cart.php');
}

switch ($action) {
    case 'add':
        if ($product_id) {
            // Check if product exists and get its details
            $stmt = $conn->prepare("SELECT id, title, price FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            
            if ($product) {
                // Add to cart or increment quantity
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id]['quantity']++;
                } else {
                    $_SESSION['cart'][$product_id] = [
                        'title' => $product['title'],
                        'price' => $product['price'],
                        'quantity' => 1
                    ];
                }
                setFlashMessage('success', 'Item added to cart');
            }
            $stmt->close();
        }
        break;

    case 'update':
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
        if ($product_id && isset($_SESSION['cart'][$product_id])) {
            if ($quantity > 0) {
                $_SESSION['cart'][$product_id]['quantity'] = $quantity;
                setFlashMessage('success', 'Cart updated');
            } else {
                unset($_SESSION['cart'][$product_id]);
                setFlashMessage('success', 'Item removed from cart');
            }
        }
        break;

    case 'remove':
        if ($product_id && isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
            setFlashMessage('success', 'Item removed from cart');
        }
        break;

    case 'clear':
        $_SESSION['cart'] = [];
        setFlashMessage('success', 'Cart cleared');
        break;
}

$conn->close();
redirect('cart.php'); 