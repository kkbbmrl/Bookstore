<?php
require_once '../connection.php';
session_start();

// Return JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please log in to manage your favorites'
    ]);
    exit;
}

// Check if request has wishlist_id
if (!isset($_POST['wishlist_id']) || empty($_POST['wishlist_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request: Missing favorite ID'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$wishlist_id = intval($_POST['wishlist_id']);

// Verify the wishlist item belongs to this user
$verify_stmt = $conn->prepare("SELECT id FROM wishlists WHERE id = ? AND user_id = ?");
$verify_stmt->bind_param("ii", $wishlist_id, $user_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'You do not have permission to remove this item'
    ]);
    exit;
}

// Delete the wishlist entry
$delete_stmt = $conn->prepare("DELETE FROM wishlists WHERE id = ?");
$delete_stmt->bind_param("i", $wishlist_id);
$success = $delete_stmt->execute();

if ($success) {
    echo json_encode([
        'success' => true,
        'message' => 'Book removed from favorites'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to remove book from favorites: ' . $conn->error
    ]);
}
?>