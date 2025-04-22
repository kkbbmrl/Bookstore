<?php
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "fassila"; // Updated to match your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Get search query
$query = isset($_GET['query']) ? $_GET['query'] : '';

if (empty($query) || strlen($query) < 2) {
    echo json_encode([]);
    exit();
}

// Prepare and execute search query
// Updated SQL to match your database schema
$search = "%{$query}%";
$sql = "SELECT b.id, b.title, b.price, b.cover_image, 
        CONCAT(COALESCE((SELECT a.name FROM authors a 
                        JOIN book_authors ba ON a.id = ba.author_id 
                        WHERE ba.book_id = b.id LIMIT 1), 'Unknown Author')) as author
        FROM books b
        WHERE b.title LIKE ? OR 
              EXISTS (SELECT 1 FROM book_authors ba 
                     JOIN authors a ON ba.author_id = a.id 
                     WHERE ba.book_id = b.id AND a.name LIKE ?) OR
              b.description LIKE ?
        LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $search, $search, $search);
$stmt->execute();
$result = $stmt->get_result();

// Fetch all results
$books = [];
while ($row = $result->fetch_assoc()) {
    // Ensure the image path is correct
    $row['image_url'] = $row['cover_image'] ? $row['cover_image'] : 'images/default-book.jpg';
    unset($row['cover_image']); // Remove the original field
    $books[] = $row;
}

// Return JSON response
echo json_encode($books);

// Close connections
$stmt->close();
$conn->close();
?>
