<?php
require_once '../connection.php';
session_start();

// Get the book ID from URL parameter
$book_id = isset($_GET['id']) ? $_GET['id'] : (isset($_GET['book_id']) ? $_GET['book_id'] : null);
$is_api_book = isset($_GET['api']) && $_GET['api'] == 1;

// Initialize book data with default values
$book = [
    'title' => 'Book Title Not Found',
    'author' => 'Unknown Author',
    'cover_img' => '../images/book-loader.gif',
    'rating' => '0.0',
    'reviews' => '0'
];

// If we have a book ID, try to fetch from database
if ($book_id) {
    if ($is_api_book) {
        // Fetch from Open Library API
        $api_url = "https://openlibrary.org/works/" . urlencode($book_id) . ".json";
        $response = @file_get_contents($api_url);
        
        if ($response !== false) {
            $api_data = json_decode($response, true);
            
            // Get book details
            $book['title'] = isset($api_data['title']) ? $api_data['title'] : 'Unknown Title';
            
            // Get author details from another API call if author key exists
            if (isset($api_data['authors'][0]['author']['key'])) {
                $author_key = $api_data['authors'][0]['author']['key'];
                $author_url = "https://openlibrary.org" . $author_key . ".json";
                $author_response = @file_get_contents($author_url);
                
                if ($author_response !== false) {
                    $author_data = json_decode($author_response, true);
                    $book['author'] = isset($author_data['name']) ? $author_data['name'] : 'Unknown Author';
                }
            }
            
            // Get cover image
            $cover_id = isset($api_data['covers'][0]) ? $api_data['covers'][0] : null;
            if ($cover_id) {
                $book['cover_img'] = "https://covers.openlibrary.org/b/id/" . $cover_id . "-L.jpg";
            }
            
            // Random ratings and reviews
            $book['rating'] = rand(35, 50) / 10; // 3.5 to 5.0
            $book['reviews'] = rand(10, 500);
        }
    } else {
        // Fetch from local database
        $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $db_book = $result->fetch_assoc();
            
            // Set book details from database
            $book['title'] = $db_book['title'];
            
            // Get author name from a separate query since book_authors table exists
            $author_name = "Unknown Author";
            try {
                $author_stmt = $conn->prepare("
                    SELECT a.name FROM authors a 
                    JOIN book_authors ba ON a.id = ba.author_id 
                    WHERE ba.book_id = ?
                ");
                if ($author_stmt) {
                    $author_stmt->bind_param("i", $book_id);
                    $author_stmt->execute();
                    $author_result = $author_stmt->get_result();
                    if ($author_result->num_rows > 0) {
                        $author_data = $author_result->fetch_assoc();
                        $author_name = $author_data['name'];
                    }
                }
            } catch (Exception $e) {
                // If error, keep default author name
            }
            $book['author'] = $author_name;
            
            // Handle cover image path
            if(!empty($db_book['cover_image'])) {
                // If it's just a filename, prepend the books directory path
                if (!strpos($db_book['cover_image'], '/') && !strpos($db_book['cover_image'], '\\')) {
                    $book['cover_img'] = "../images/books/" . $db_book['cover_image'];
                } else {
                    // If it's a full path, keep it as is
                    $book['cover_img'] = $db_book['cover_image'];
                }
                
                // Verify the file exists, if not use default
                if (!file_exists($book['cover_img'])) {
                    $book['cover_img'] = "../images/book-1.jpg";
                }
            } else {
                $book['cover_img'] = "../images/book-1.jpg";
            }
            
            // Get average rating from reviews table
            try {
                $rating_stmt = $conn->prepare("
                    SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
                    FROM reviews 
                    WHERE book_id = ?
                ");
                if ($rating_stmt) {
                    $rating_stmt->bind_param("i", $book_id);
                    $rating_stmt->execute();
                    $rating_result = $rating_stmt->get_result();
                    if ($rating_result->num_rows > 0) {
                        $rating_data = $rating_result->fetch_assoc();
                        $book['rating'] = round($rating_data['avg_rating'], 1) ?: 0;
                        $book['reviews'] = $rating_data['review_count'];
                    }
                }
            } catch (Exception $e) {
                // If error, keep default rating/reviews
            }
        }
    }
}

// Get all reviews for this book
$reviews = [];
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

if (!$is_api_book && $book_id) {
    // Get total count for pagination
    $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM reviews WHERE book_id = ?");
    $count_stmt->bind_param("i", $book_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_row = $count_result->fetch_assoc();
    $total_reviews = $count_row['total'];
    $total_pages = ceil($total_reviews / $per_page);
    
    // Get reviews for this page
    $review_stmt = $conn->prepare("
        SELECT r.*, u.name, u.profile_picture 
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.book_id = ? 
        ORDER BY r.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $review_stmt->bind_param("iii", $book_id, $per_page, $offset);
    $review_stmt->execute();
    $review_result = $review_stmt->get_result();
    
    while ($row = $review_result->fetch_assoc()) {
        $reviews[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>All Reviews - <?php echo htmlspecialchars($book['title']); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    />
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <style>
        .review-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .book-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            gap: 20px;
        }
        .book-header img {
            width: 120px;
            height: auto;
            object-fit: cover;
            border-radius: 5px;
        }
        .book-info h1 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .book-info p {
            margin: 0;
            color: #666;
        }
        .rating-summary {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        .rating-stars {
            color: #ffc107;
            margin-right: 10px;
        }
        .reviews-count {
            color: #666;
        }
        .review-list {
            margin-top: 30px;
        }
        .review {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .reviewer-info {
            display: flex;
            align-items: center;
        }
        .reviewer-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }
        .reviewer-name {
            font-weight: bold;
        }
        .review-date {
            color: #666;
            font-size: 14px;
        }
        .review-rating {
            color: #ffc107;
        }
        .review-content {
            margin-top: 10px;
            line-height: 1.5;
        }
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 12px;
            margin: 0 5px;
            border-radius: 4px;
            color: #333;
            text-decoration: none;
        }
        .pagination a {
            background-color: #f0f0f0;
        }
        .pagination a:hover {
            background-color: #ddd;
        }
        .pagination .active {
            background-color: #6c5dd4;
            color: white;
        }
        .back-to-book {
            display: inline-block;
            margin-bottom: 20px;
            color: #6c5dd4;
            text-decoration: none;
        }
        .back-to-book i {
            margin-right: 5px;
        }
        .back-to-book:hover {
            text-decoration: underline;
        }
        .no-reviews {
            text-align: center;
            padding: 30px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="review-container">
        <a href="book-detail.php?id=<?php echo $book_id; ?><?php echo $is_api_book ? '&api=1' : ''; ?>" class="back-to-book">
            <i class="fa-solid fa-arrow-left"></i> Back to Book Details
        </a>

        <div class="book-header">
            <img src="<?php echo htmlspecialchars($book['cover_img']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
            <div class="book-info">
                <h1><?php echo htmlspecialchars($book['title']); ?></h1>
                <p>By <?php echo htmlspecialchars($book['author']); ?></p>
                <div class="rating-summary">
                    <div class="rating-stars">
                        <?php for ($i = 0; $i < floor($book['rating']); $i++): ?>
                            <i class="fa-solid fa-star"></i>
                        <?php endfor; ?>
                        <?php if ($book['rating'] - floor($book['rating']) >= 0.5): ?>
                            <i class="fa-solid fa-star-half-stroke"></i>
                            <?php $i++; ?>
                        <?php endif; ?>
                        <?php for ($i = ceil($book['rating']); $i < 5; $i++): ?>
                            <i class="fa-regular fa-star"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="reviews-count"><?php echo $book['rating']; ?> out of 5 (<?php echo $book['reviews']; ?> reviews)</span>
                </div>
            </div>
        </div>

        <h2>All Reviews</h2>
        
        <?php if(empty($reviews)): ?>
            <div class="no-reviews">
                <p>No reviews have been written for this book yet.</p>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="book-detail.php?id=<?php echo $book_id; ?><?php echo $is_api_book ? '&api=1' : ''; ?>#customer" style="color: #6c5dd4; font-weight: bold;">Be the first to write a review!</a>
                <?php else: ?>
                    <p><a href="login.php?redirect=<?php echo urlencode('book-detail.php?id=' . $book_id . ($is_api_book ? '&api=1' : '')); ?>" style="color: #6c5dd4; font-weight: bold;">Login</a> to write a review.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="review-list">
                <?php foreach($reviews as $review): ?>
                    <div class="review">
                        <div class="review-header">
                            <div class="reviewer-info">
                                <?php
                                $profile_pic = !empty($review['profile_picture']) ? $review['profile_picture'] : '../images/man1.png';
                                // Fix path if needed
                                if (!file_exists($profile_pic) && strpos($profile_pic, 'uploads/') === 0) {
                                    $profile_pic = '../' . $profile_pic;
                                }
                                if (!file_exists($profile_pic)) {
                                    $profile_pic = '../images/man1.png';
                                }
                                ?>
                                <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="<?php echo htmlspecialchars($review['name']); ?>">
                                <div>
                                    <div class="reviewer-name"><?php echo htmlspecialchars($review['name']); ?></div>
                                    <div class="review-date"><?php echo date('F j, Y', strtotime($review['created_at'])); ?></div>
                                </div>
                            </div>
                            <div class="review-rating">
                                <?php for($i = 0; $i < $review['rating']; $i++): ?>
                                    <i class="fa-solid fa-star"></i>
                                <?php endfor; ?>
                                <?php for($i = $review['rating']; $i < 5; $i++): ?>
                                    <i class="fa-regular fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="review-content">
                            <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if(isset($total_pages) && $total_pages > 1): ?>
                <div class="pagination">
                    <?php if($page > 1): ?>
                        <a href="?id=<?php echo $book_id; ?><?php echo $is_api_book ? '&api=1' : ''; ?>&page=<?php echo $page-1; ?>">
                            <i class="fa-solid fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $start_page + 4);
                    if ($end_page - $start_page < 4) {
                        $start_page = max(1, $end_page - 4);
                    }
                    ?>
                    
                    <?php for($i = $start_page; $i <= $end_page; $i++): ?>
                        <?php if($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?id=<?php echo $book_id; ?><?php echo $is_api_book ? '&api=1' : ''; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if($page < $total_pages): ?>
                        <a href="?id=<?php echo $book_id; ?><?php echo $is_api_book ? '&api=1' : ''; ?>&page=<?php echo $page+1; ?>">
                            Next <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="../js/back-to-top.js"></script>
</body>
</html>