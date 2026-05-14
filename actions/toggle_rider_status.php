<?php
require_once '../core/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check CSRF
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $new_status = $_POST['status'] ?? '';

    if (!in_array($new_status, ['online', 'offline'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE users SET availability_status = ? WHERE id = ? AND role = 'delivery_partner'");
        $stmt->execute([$new_status, $user_id]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Status updated to ' . ucfirst($new_status)]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
}
