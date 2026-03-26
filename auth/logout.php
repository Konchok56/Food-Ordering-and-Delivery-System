<?php
session_start();
session_destroy();
header("Location: /food/swiftbite_php_starter/index.php");
exit;
?>
