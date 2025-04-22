<?php
require_once '../connection.php';
session_start();

// Return JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in',
        'count' => 0
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get count of books in user's wishlist
$count_stmt = $conn->prepare("SELECT COUNT(*) as like_count FROM wishlists WHERE user_id = ?");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_data = $count_result->fetch_assoc();
$like_count = $count_data['like_count'];

echo json_encode([
    'success' => true,
    'count' => $like_count
]);
?>