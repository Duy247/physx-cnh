<?php
session_start(); // Start a session

$count_file = './visit.txt'; // Store outside the public directory

// Generate a token if it doesn't exist in the session
if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(16)); // Generate a secure token
    setcookie('token', $_SESSION['token'], time() + (86400 * 30), '/'); // Store in a cookie for 30 days
}

// Check if the token from the request matches the session token
if (isset($_COOKIE['token']) && $_COOKIE['token'] === $_SESSION['token']) {
    $count = file_exists($count_file) ? (int)file_get_contents($count_file) : 0;
    $count++;
    file_put_contents($count_file, $count);

    header('Content-Type: application/json');
    echo json_encode(['count' => $count]);
} else {
    // Deny access if tokens don't match
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Invalid token']);
}
?>