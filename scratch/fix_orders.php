<?php
include('core/db.php');
$pdo->exec("UPDATE orders SET status = 'pending' WHERE customer_email = 'sheetpillo@gmail.com' AND (status IS NULL OR status = '')");
echo "Updated statuses.";
