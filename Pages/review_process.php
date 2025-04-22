<?php
require_once '../connection.php';
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['HTTP_REFERER']));
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $user_id = $_SESSION['user_id'];
    $book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
    $is_api_book = isset($_POST['is_api_book']) && $_POST['is_api_book'] == '1';
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $review_text = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';
    
    $error = null;
    
    // Validate input
    if ($book_id <= 0) {
        $error = "Invalid book ID";
    } elseif ($rating < 1 || $rating > 5) {
        $error = "Rating must be between 1 and 5";
    } elseif (empty($review_text)) {
        $error = "Review text cannot be empty";
    }
    
    // If API book (from external source), we need to check if it exists in our local DB
    // If not, create a placeholder entry
    if ($is_api_book && !$error) {
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
    
    if (!$error) {
        // Check if user has already reviewed this book
        $check_stmt = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND book_id = ?");
        $check_stmt->bind_param("ii", $user_id, $book_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing review
            $update_stmt = $conn->prepare("
                UPDATE reviews 
                SET rating = ?, review_text = ?, created_at = NOW() 
                WHERE user_id = ? AND book_id = ?
            ");
            $update_stmt->bind_param("isii", $rating, $review_text, $user_id, $book_id);
            
            if ($update_stmt->execute()) {
                // Update successful
                $_SESSION['review_message'] = "Your review has been updated!";
            } else {
                $error = "Error updating review: " . $conn->error;
            }
        } else {
            // Insert new review
            $insert_stmt = $conn->prepare("
                INSERT INTO reviews (user_id, book_id, rating, review_text, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $insert_stmt->bind_param("iiis", $user_id, $book_id, $rating, $review_text);
            
            if ($insert_stmt->execute()) {
                // Insert successful
                $_SESSION['review_message'] = "Thank you for your review!";
            } else {
                $error = "Error adding review: " . $conn->error;
            }
        }
    }
    
    if ($error) {
        $_SESSION['review_error'] = $error;
    }
    
    // Redirect back to the book details page
    $redirect_url = $is_api_book ? "book-detail.php?id=" . $book_id . "&api=1" : "book-detail.php?id=" . $book_id;
    header("Location: " . $redirect_url);
    exit;
} else {
    // If not POST request, redirect to home
    header("Location: ../index.php");
    exit;
}
?>