<?php
require_once '../connection.php';
session_start();

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_id = $_SESSION['user_id'];

// Get all books user has liked (added to wishlist)
$favorites = [];

// First get books from database
$stmt = $conn->prepare("
    SELECT b.*, w.id as wishlist_id 
    FROM books b 
    JOIN wishlists w ON b.id = w.book_id 
    WHERE w.user_id = ?
    ORDER BY w.added_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Determine if it's an API book by title format
    $is_api_book = (strpos($row['title'], 'API Book ') === 0);
    $api_id = $is_api_book ? substr($row['title'], 9) : null;
    
    // For API books, fetch real data from Open Library
    if ($is_api_book && $api_id) {
        $api_url = "https://openlibrary.org/works/" . urlencode($api_id) . ".json";
        $response = @file_get_contents($api_url);
        
        if ($response !== false) {
            $api_data = json_decode($response, true);
            
            // Get book details
            $title = isset($api_data['title']) ? $api_data['title'] : 'Unknown Title';
            
            // Get author
            $author = 'Unknown Author';
            if (isset($api_data['authors'][0]['author']['key'])) {
                $author_key = $api_data['authors'][0]['author']['key'];
                $author_url = "https://openlibrary.org" . $author_key . ".json";
                $author_response = @file_get_contents($author_url);
                
                if ($author_response !== false) {
                    $author_data = json_decode($author_response, true);
                    $author = isset($author_data['name']) ? $author_data['name'] : 'Unknown Author';
                }
            }
            
            // Get cover image
            $cover_url = '../images/book-loader.gif';
            if (isset($api_data['covers'][0])) {
                $cover_id = $api_data['covers'][0];
                $cover_url = "https://covers.openlibrary.org/b/id/" . $cover_id . "-M.jpg";
            }
            
            $favorites[] = [
                'id' => $row['id'],
                'wishlist_id' => $row['wishlist_id'],
                'title' => $title,
                'author' => $author,
                'cover_img' => $cover_url,
                'is_api_book' => true,
                'api_id' => $api_id,
                'price' => $row['price'] ?: 0
            ];
        }
    } else {
        // For regular books
        // Handle cover image path
        $cover_image = !empty($row['cover_image']) ? $row['cover_image'] : "";
        
        // Check if the image path needs to be fixed
        if (!empty($cover_image)) {
            // If it's just a filename, prepend the books directory path
            if (!strpos($cover_image, '/') && !strpos($cover_image, '\\')) {
                $cover_image = "../images/books/" . $cover_image;
            }
            
            // Verify the file exists, if not use default
            if (!file_exists($cover_image)) {
                $cover_image = "../images/book-1.jpg";
            }
        } else {
            $cover_image = "../images/book-1.jpg";
        }
        
        // Get author name
        $author = "Unknown Author";
        try {
            $author_stmt = $conn->prepare("
                SELECT a.name FROM authors a 
                JOIN book_authors ba ON a.id = ba.author_id 
                WHERE ba.book_id = ?
            ");
            if ($author_stmt) {
                $author_stmt->bind_param("i", $row['id']);
                $author_stmt->execute();
                $author_result = $author_stmt->get_result();
                if ($author_result->num_rows > 0) {
                    $author_data = $author_result->fetch_assoc();
                    $author = $author_data['name'];
                }
            }
        } catch (Exception $e) {
            // If error, keep default author name
        }
        
        $favorites[] = [
            'id' => $row['id'],
            'wishlist_id' => $row['wishlist_id'],
            'title' => $row['title'],
            'author' => $author,
            'cover_img' => $cover_image,
            'is_api_book' => false,
            'api_id' => null,
            'price' => $row['price']
        ];
    }
}

// Get total count of liked books
$total_favorites = count($favorites);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Favorites - Fassila Bookstore</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        .favorite-books {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .favorite-book-card {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            position: relative;
            background-color: #fff;
        }
        
        .favorite-book-card:hover {
            transform: translateY(-5px);
        }
        
        .favorite-book-card .img {
            height: 280px;
            position: relative;
            overflow: hidden;
        }
        
        .favorite-book-card .img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .favorite-book-card .img img:hover {
            transform: scale(1.05);
        }
        
        .favorite-book-card .content {
            padding: 15px;
        }
        
        .favorite-book-card .title {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 16px;
        }
        
        .favorite-book-card .author {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .favorite-book-card .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }
        
        .favorite-book-card .view-button {
            background: #6c5dd4;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            flex: 1;
            margin-right: 5px;
        }
        
        .favorite-book-card .view-button:hover {
            background: #5a4cbe;
        }
        
        .favorite-book-card .remove-button {
            background: #f44336;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
            flex: 1;
            margin-left: 5px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
        }
        
        .favorite-book-card .remove-button:hover {
            background: #d32f2f;
        }
        
        .price {
            font-weight: 600;
            color: #6c5dd4;
            margin-top: 5px;
            font-size: 16px;
        }
        
        .favorites-header {
            background-color: #f8f9fa;
            padding: 20px 0;
            margin-bottom: 20px;
        }
        
        .favorites-header h1 {
            font-size: 24px;
            color: #333;
            text-align: center;
            margin: 0;
            padding: 0;
        }
        
        .favorites-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .empty-favorites {
            text-align: center;
            padding: 50px 0;
        }
        
        .empty-favorites i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 20px;
        }
        
        .empty-favorites h2 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .empty-favorites p {
            color: #666;
            margin-bottom: 25px;
        }
        
        .empty-favorites .browse-button {
            display: inline-block;
            background: #6c5dd4;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            transition: background 0.2s;
        }
        
        .empty-favorites .browse-button:hover {
            background: #5a4cbe;
        }
        
        #toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .toast {
            min-width: 250px;
            background-color: #6c5dd4;
            color: white;
            padding: 12px;
            border-radius: 4px;
            margin-top: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="breadcrumb-container">
        <ul class="breadcrumb">
            <li><a href="../index.php">Home</a></li>
            <li><a href="#">My Favorites</a></li>
        </ul>
    </div>
    
    <div class="favorites-header">
        <div class="favorites-container">
            <h1>My Favorites <span class="favorite-count">(<?php echo $total_favorites; ?>)</span></h1>
        </div>
    </div>
    
    <div class="favorites-container">
        <?php if (empty($favorites)): ?>
            <div class="empty-favorites">
                <i class="fa-regular fa-heart"></i>
                <h2>Your favorites list is empty</h2>
                <p>Browse our catalog and add books you like to your favorites.</p>
                <a href="book-filter.php" class="browse-button">Browse Books</a>
            </div>
        <?php else: ?>
            <div class="favorite-books">
                <?php foreach ($favorites as $book): ?>
                    <div class="favorite-book-card" data-wishlist-id="<?php echo $book['wishlist_id']; ?>">
                        <div class="img">
                            <?php if ($book['is_api_book']): ?>
                                <a href="book-detail.php?id=<?php echo $book['api_id']; ?>&api=1">
                                    <img src="<?php echo htmlspecialchars($book['cover_img']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                                </a>
                            <?php else: ?>
                                <a href="book-detail.php?id=<?php echo $book['id']; ?>">
                                    <img src="<?php echo htmlspecialchars($book['cover_img']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="content">
                            <div class="title"><?php echo htmlspecialchars($book['title']); ?></div>
                            <div class="author">by <?php echo htmlspecialchars($book['author']); ?></div>
                            <div class="price">
                                $<?php echo number_format($book['price'], 2); ?>
                            </div>
                            <div class="action-buttons">
                                <?php if ($book['is_api_book']): ?>
                                    <a href="book-detail.php?id=<?php echo $book['api_id']; ?>&api=1" class="view-button">View Details</a>
                                <?php else: ?>
                                    <a href="book-detail.php?id=<?php echo $book['id']; ?>" class="view-button">View Details</a>
                                <?php endif; ?>
                                <button class="remove-button" data-id="<?php echo $book['wishlist_id']; ?>">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="../js/back-to-top.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle removing items from favorites
            const removeButtons = document.querySelectorAll('.remove-button');
            
            removeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const wishlistId = this.getAttribute('data-id');
                    const bookCard = this.closest('.favorite-book-card');
                    
                    // Send AJAX request to remove from wishlist
                    const formData = new FormData();
                    formData.append('wishlist_id', wishlistId);
                    formData.append('action', 'remove');
                    
                    fetch('remove_favorite.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Animate removal and update count
                            bookCard.style.opacity = '0';
                            bookCard.style.transform = 'scale(0.8)';
                            
                            setTimeout(() => {
                                bookCard.remove();
                                
                                // Update count
                                const countElem = document.querySelector('.favorite-count');
                                const currentCount = parseInt(countElem.textContent.match(/\d+/)[0]) - 1;
                                countElem.textContent = `(${currentCount})`;
                                
                                // Update header like count
                                updateHeaderLikeCount();
                                
                                // Show empty message if no more favorites
                                if (currentCount === 0) {
                                    const favoritesContainer = document.querySelector('.favorites-container');
                                    favoritesContainer.innerHTML = `
                                        <div class="empty-favorites">
                                            <i class="fa-regular fa-heart"></i>
                                            <h2>Your favorites list is empty</h2>
                                            <p>Browse our catalog and add books you like to your favorites.</p>
                                            <a href="book-filter.php" class="browse-button">Browse Books</a>
                                        </div>
                                    `;
                                }
                                
                                // Show success message
                                showToast('Book removed from favorites');
                            }, 300);
                        } else {
                            showToast(data.message || 'An error occurred');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('An error occurred while processing your request');
                    });
                });
            });
        });
        
        // Function to update header like count
        function updateHeaderLikeCount() {
            const headerLikeCount = document.querySelector('.nav-end .likebtn span');
            if (headerLikeCount) {
                fetch('get_like_count.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            headerLikeCount.textContent = data.count;
                        }
                    })
                    .catch(error => {
                        console.error('Error updating like count:', error);
                    });
            }
        }
        
        // Function to show toast message
        function showToast(message) {
            // Create toast container if it doesn't exist
            let toastContainer = document.getElementById('toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toast-container';
                document.body.appendChild(toastContainer);
            }
            
            // Create toast element
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.textContent = message;
            
            // Add toast to container
            toastContainer.appendChild(toast);
            
            // Show the toast
            setTimeout(() => {
                toast.style.opacity = '1';
            }, 10);
            
            // Hide and remove the toast after a delay
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => {
                    toastContainer.removeChild(toast);
                }, 300);
            }, 3000);
        }
    </script>
</body>
</html>