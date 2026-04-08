<?php
include('core/db.php');
$email = 'ronalshrestha123@gmail.com';
$user = $pdo->prepare("SELECT id, name, role FROM users WHERE email = ?");
$user->execute([$email]);
$u = $user->fetch(PDO::FETCH_ASSOC);

if ($u) {
    echo "User found: " . $u['id'] . " | " . $u['role'] . "\n";
    $rest = $pdo->prepare("SELECT * FROM restaurants WHERE owner_id = ?");
    $rest->execute([$u['id']]);
    $r = $rest->fetch(PDO::FETCH_ASSOC);
    if ($r) {
        echo "Restaurant found: " . $r['id'] . " | " . $r['name'] . "\n";
    } else {
        echo "No restaurant found for this owner!\n";
    }
} else {
    echo "User not found!\n";
}
?>
