<?php
require_once '../connection.php';
session_start();

// Return JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

// Check if request has book_id
if (!isset($_POST['book_id']) || empty($_POST['book_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid book ID'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$book_id = $_POST['book_id'];
$is_api_book = isset($_POST['is_api_book']) && $_POST['is_api_book'] == '1';

// If API book, we need to check if there's a placeholder entry
if ($is_api_book) {
    // Check if this API book already has an entry in our local database
    $check_stmt = $conn->prepare("SELECT id FROM books WHERE title LIKE ? LIMIT 1");
    $api_book_title = "API Book " . $book_id; // Placeholder title with API ID
    $check_stmt->bind_param("s", $api_book_title);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Book exists, check if user has liked it
        $row = $result->fetch_assoc();
        $local_book_id = $row['id'];
        
        $like_stmt = $conn->prepare("SELECT id FROM wishlists WHERE user_id = ? AND book_id = ?");
        $like_stmt->bind_param("ii", $user_id, $local_book_id);
        $like_stmt->execute();
        $like_result = $like_stmt->get_result();
        
        echo json_encode([
            'success' => true,
            'isLiked' => ($like_result->num_rows > 0)
        ]);
    } else {
        // Book doesn't exist yet
        echo json_encode([
            'success' => true,
            'isLiked' => false
        ]);
    }
} else {
    // Regular book, check if user has liked it
    $like_stmt = $conn->prepare("SELECT id FROM wishlists WHERE user_id = ? AND book_id = ?");
    $like_stmt->bind_param("ii", $user_id, $book_id);
    $like_stmt->execute();
    $like_result = $like_stmt->get_result();
    
    echo json_encode([
        'success' => true,
        'isLiked' => ($like_result->num_rows > 0)
    ]);
}
?>