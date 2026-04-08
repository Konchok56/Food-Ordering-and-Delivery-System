<?php
$files = [
    'index.php',
    'menu.php',
    'restaurant.php',
    'restaurants.php',
    'food_detail.php',
    'orders/cart.php',
    'user/order_history.php',
    'user/order_details.php',
    'user/profile.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        $pattern = '/<button class="cart-fab".*?<\/button>/s';
        $replacement = '<?php include \'templates/floating_menu.php\'; ?>';
        
        $newContent = preg_replace($pattern, $replacement, $content);
        
        if ($newContent !== null && $newContent !== $content) {
            file_put_contents($file, $newContent);
            echo "Updated $file\n";
        } else {
            echo "Skipped $file (no match)\n";
        }
    }
}
?>
