<?php
$files = [
    'index.php',
    'menu.php',
    'restaurant.php',
    'restaurants.php',
    'food_detail.php',
    'cart.php',
    'order_history.php',
    'order_details.php',
    'profile.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        $pattern = '/<button class="cart-fab".*?<\/button>/s';
        $replacement = '<?php include \'sections/floating_menu.php\'; ?>';
        
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
