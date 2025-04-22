<?php
require_once '../connection.php';
session_start();

// Return JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please log in to like books',
        'redirect' => 'login.php?redirect=' . urlencode($_SERVER['HTTP_REFERER'])
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
$book_id = intval($_POST['book_id']);
$is_api_book = isset($_POST['is_api_book']) && $_POST['is_api_book'] == '1';

// If API book (from external source), we need to check if it exists in our local DB
// If not, create a placeholder entry
if ($is_api_book) {
    // Check if this API book already has an entry in our local database
    $check_stmt = $conn->prepare("SELECT id FROM books WHERE title LIKE ? LIMIT 1");
    $api_book_title = "API Book " . $book_id; // Placeholder title with API ID
    $check_stmt->bind_param("s", $api_book_title);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Create a placeholder book entry
        $insert_book_stmt = $conn->prepare("
            INSERT INTO books (title, price, stock_quantity, created_at) 
            VALUES (?, 0, 0, NOW())
        ");
        $insert_book_stmt->bind_param("s", $api_book_title);
        $insert_book_stmt->execute();
        
        // Get the new book's ID
        $book_id = $conn->insert_id;
    } else {
        $row = $result->fetch_assoc();
        $book_id = $row['id'];
    }
}

// Check if the user has already liked this book
$check_stmt = $conn->prepare("SELECT id FROM wishlists WHERE user_id = ? AND book_id = ?");
$check_stmt->bind_param("ii", $user_id, $book_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    // User already liked the book, so unlike it
    $delete_stmt = $conn->prepare("DELETE FROM wishlists WHERE user_id = ? AND book_id = ?");
    $delete_stmt->bind_param("ii", $user_id, $book_id);
    
    if ($delete_stmt->execute()) {
        // Get updated like count
        $count_stmt = $conn->prepare("SELECT COUNT(*) as like_count FROM wishlists WHERE book_id = ?");
        $count_stmt->bind_param("i", $book_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_data = $count_result->fetch_assoc();
        $like_count = $count_data['like_count'];
        
        echo json_encode([
            'success' => true,
            'action' => 'unliked',
            'message' => 'Book removed from favorites',
            'likes' => $like_count
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to unlike book: ' . $conn->error
        ]);
    }
} else {
    // User hasn't liked the book yet, add the like
    $add_stmt = $conn->prepare("INSERT INTO wishlists (user_id, book_id, added_at) VALUES (?, ?, NOW())");
    $add_stmt->bind_param("ii", $user_id, $book_id);
    
    if ($add_stmt->execute()) {
        // Get updated like count
        $count_stmt = $conn->prepare("SELECT COUNT(*) as like_count FROM wishlists WHERE book_id = ?");
        $count_stmt->bind_param("i", $book_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_data = $count_result->fetch_assoc();
        $like_count = $count_data['like_count'];
        
        echo json_encode([
            'success' => true,
            'action' => 'liked',
            'message' => 'Book added to favorites',
            'likes' => $like_count
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to like book: ' . $conn->error
        ]);
    }
}
?>