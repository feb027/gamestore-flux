<tr>
    <td>
        <img src="<?php echo BASE_URL . ($item['image_url'] ?? '/images/games/placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="cart-item-image">
        <span class="cart-item-title"><?php echo htmlspecialchars($item['title']); ?></span>
    </td>
    <td class="price"><?php echo formatIDR($item['price']); ?></td>
    <td>
        <form action="update.php" method="post" class="quantity-form">
            <input type="hidden" name="game_id" value="<?php echo $item['id']; ?>">
            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="10" onchange="this.form.submit()">
        </form>
    </td>
    <td class="subtotal"><?php echo formatIDR($item['price'] * $item['quantity']); ?></td>
    <td>
        <form action="remove.php" method="post">
            <input type="hidden" name="game_id" value="<?php echo $item['id']; ?>">
            <button type="submit" class="remove-item">×</button>
        </form>
    </td>
</tr>

<div class="cart-summary">
    <div class="summary-row">
        <span>Subtotal:</span>
        <span><?php echo formatIDR($total); ?></span>
    </div>
    <div class="summary-row">
        <span>Tax (10%):</span>
        <span><?php echo formatIDR($tax); ?></span>
    </div>
    <div class="summary-row total">
        <span>Total:</span>
        <span><?php echo formatIDR($total + $tax); ?></span>
    </div>
</div> 