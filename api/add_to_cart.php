<?php
// API endpoint to add a book to the cart
require_once '../connection.php';
session_start();

// Return JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please log in to add items to your cart',
        'redirect' => '../Pages/login.php?redirect=' . urlencode($_SERVER['HTTP_REFERER'])
    ]);
    exit;
}

// Validate required parameters
if (!isset($_POST['book_id']) || empty($_POST['book_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing book ID'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$book_id = intval($_POST['book_id']);
$quantity = isset($_POST['quantity']) && intval($_POST['quantity']) > 0 ? intval($_POST['quantity']) : 1;

// Check if book exists and is in stock
$book_stmt = $conn->prepare("SELECT id, title, price, stock_quantity FROM books WHERE id = ?");
$book_stmt->bind_param("i", $book_id);
$book_stmt->execute();
$book_result = $book_stmt->get_result();

if ($book_result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Book not found'
    ]);
    exit;
}

$book = $book_result->fetch_assoc();

// Check if book is in stock
if ($book['stock_quantity'] <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'This book is out of stock'
    ]);
    exit;
}

// Check if requested quantity exceeds available stock
if ($quantity > $book['stock_quantity']) {
    echo json_encode([
        'success' => false,
        'message' => 'Not enough stock. Only ' . $book['stock_quantity'] . ' available.'
    ]);
    exit;
}

// Check if the book is already in the cart
$check_stmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND book_id = ?");
$check_stmt->bind_param("ii", $user_id, $book_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    // Book already exists in cart, update quantity
    $cart_item = $check_result->fetch_assoc();
    $new_quantity = $cart_item['quantity'] + $quantity;
    
    // Check if new quantity exceeds stock
    if ($new_quantity > $book['stock_quantity']) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot add more of this book. Maximum stock reached.'
        ]);
        exit;
    }
    
    $update_stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
    $update_stmt->bind_param("ii", $new_quantity, $cart_item['id']);
    
    if ($update_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Cart updated successfully',
            'action' => 'updated',
            'quantity' => $new_quantity
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update cart: ' . $conn->error
        ]);
    }
} else {
    // Add new book to cart
    $add_stmt = $conn->prepare("INSERT INTO cart_items (user_id, book_id, quantity, added_at) VALUES (?, ?, ?, NOW())");
    $add_stmt->bind_param("iii", $user_id, $book_id, $quantity);
    
    if ($add_stmt->execute()) {
        // Get cart item count for header badge update
        $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?");
        $count_stmt->bind_param("i", $user_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_data = $count_result->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'message' => 'Book added to cart successfully',
            'action' => 'added',
            'cart_count' => $count_data['count']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to add book to cart: ' . $conn->error
        ]);
    }
}
?>