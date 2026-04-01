<?php
/**
 * Input Validation Helper
 */

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    $errors = [];
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    if (!preg_match('/[A-Za-z]/', $password)) {
        $errors[] = 'Password must contain at least one letter';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    return $errors;
}

function validateName($name) {
    $name = trim($name);
    if (strlen($name) < 2) return 'Name must be at least 2 characters';
    if (strlen($name) > 100) return 'Name must be less than 100 characters';
    if (!preg_match('/^[a-zA-Z\s\'\-\.]+$/', $name)) return 'Name contains invalid characters';
    return '';
}

function validatePhone($phone) {
    $phone = trim($phone);
    if (empty($phone)) return ''; // optional
    if (!preg_match('/^[\d\+\-\s\(\)]{7,20}$/', $phone)) return 'Invalid phone number';
    return '';
}
