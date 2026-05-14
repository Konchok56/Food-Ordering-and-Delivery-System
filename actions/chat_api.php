<?php
session_start();
header('Content-Type: application/json');

require_once '../core/config.php';
require_once '../core/db.php';
require_once '../core/recommendation_helper.php';

// Base URL constant from config (e.g. /food/swiftbite_php_starter)
$base = SITE_BASE_URL;

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$message = isset($input['message']) ? strtolower(trim($input['message'])) : '';

if (empty($message)) {
    echo json_encode(['reply' => "I didn't quite catch that. Could you please rephrase?"]);
    exit;
}

$reply = "";
include 'chat_api_logic.php';

// Slight delay to simulate thinking
usleep(400000); // 0.4s

echo json_encode(['reply' => $reply]);
