<?php
require_once '../connection.php';
session_start();

// Get the book ID from URL parameter
$book_id = isset($_GET['id']) ? $_GET['id'] : null;
$is_api_book = isset($_GET['api']) && $_GET['api'] == 1;

// Check if the user has already liked this book
$user_has_liked = false;
if (isset($_SESSION['user_id']) && !$is_api_book && $book_id) {
    $like_check_stmt = $conn->prepare("SELECT id FROM wishlists WHERE user_id = ? AND book_id = ?");
    $like_check_stmt->bind_param("ii", $_SESSION['user_id'], $book_id);
    $like_check_stmt->execute();
    $like_check_result = $like_check_stmt->get_result();
    $user_has_liked = ($like_check_result->num_rows > 0);
}

// Initialize book data with default values
$book = [
    'title' => 'Book Title Not Found',
    'author' => 'Unknown Author',
    'cover_img' => '../images/book-loader.gif',
    'price' => '0.00',
    'original_price' => '0.00',
    'discount' => '0',
    'rating' => '0.0',
    'reviews' => '0',
    'likes' => '0',
    'description' => 'No description available',
    'publisher' => 'Unknown Publisher',
    'year' => 'Unknown',
    'isbn' => 'Unknown',
    'language' => 'Unknown',
    'format' => 'Unknown',
    'pages' => '0',
    'tags' => []
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
            
            // Get description
            if (isset($api_data['description'])) {
                $book['description'] = is_array($api_data['description']) ? 
                    (isset($api_data['description']['value']) ? $api_data['description']['value'] : 'No description available') : 
                    $api_data['description'];
            }
            
            // Other details
            $book['year'] = isset($api_data['first_publish_date']) ? $api_data['first_publish_date'] : 'Unknown';
            
            // Get subjects as tags
            if (isset($api_data['subjects'])) {
                $book['tags'] = array_slice($api_data['subjects'], 0, 5); // Get first 5 tags
            }
            
            // Create a random price and discount
            $original_price = rand(10, 50) + 0.99;
            $discount = rand(5, 30);
            $discounted_price = round($original_price * (1 - $discount/100), 2);
            
            $book['price'] = $discounted_price;
            $book['original_price'] = $original_price;
            $book['discount'] = $discount;
            
            // Random ratings and reviews
            $book['rating'] = rand(35, 50) / 10; // 3.5 to 5.0
            $book['reviews'] = rand(10, 500);
            $book['likes'] = rand(100, 10000);
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
                // Check if the image path is just a filename or a full path
                $cover_image = $db_book['cover_image'];
                
                // If it's just a filename, prepend the books directory path
                if (!strpos($cover_image, '/') && !strpos($cover_image, '\\')) {
                    $book['cover_img'] = "../images/books/" . $cover_image;
                } else {
                    // If it's a full path, keep it as is
                    $book['cover_img'] = $cover_image;
                }
                
                // Verify the file exists, if not use default
                if (!file_exists($book['cover_img'])) {
                    $book['cover_img'] = "../images/book-1.jpg";
                }
            } else {
                $book['cover_img'] = "../images/book-1.jpg";
            }

            $book['price'] = $db_book['price'];
            // Map old_price to original_price
            $book['original_price'] = isset($db_book['old_price']) ? $db_book['old_price'] : $db_book['price'];
            
            // Calculate discount if old_price exists
            $discount = 0;
            if (!empty($db_book['old_price']) && $db_book['old_price'] > 0) {
                $discount = round(100 - (($db_book['price'] / $db_book['old_price']) * 100));
            }
            $book['discount'] = $discount;
            
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
            
            // Get like count from wishlists
            try {
                $likes_stmt = $conn->prepare("
                    SELECT COUNT(*) as like_count 
                    FROM wishlists 
                    WHERE book_id = ?
                ");
                if ($likes_stmt) {
                    $likes_stmt->bind_param("i", $book_id);
                    $likes_stmt->execute();
                    $likes_result = $likes_stmt->get_result();
                    if ($likes_result->num_rows > 0) {
                        $likes_data = $likes_result->fetch_assoc();
                        $book['likes'] = $likes_data['like_count'];
                    }
                }
            } catch (Exception $e) {
                // If error, keep default likes count
            }
            
            $book['description'] = $db_book['description'] ?: 'No description available';
            $book['publisher'] = $db_book['publisher'] ?: 'Unknown Publisher';
            $book['year'] = $db_book['publication_date'] ? date('Y', strtotime($db_book['publication_date'])) : 'Unknown';
            $book['isbn'] = $db_book['isbn'] ?: 'Unknown';
            $book['language'] = $db_book['language'] ?: 'Unknown';
            $book['format'] = 'Paperback'; // Default format since it's not in your DB schema
            $book['pages'] = $db_book['pages'] ?: '0';
            
            // Get categories as tags
            try {
                $tags_stmt = $conn->prepare("
                    SELECT c.name FROM categories c 
                    JOIN book_categories bc ON c.id = bc.category_id 
                    WHERE bc.book_id = ?
                ");
                if ($tags_stmt) {
                    $tags_stmt->bind_param("i", $book_id);
                    $tags_stmt->execute();
                    $tags_result = $tags_stmt->get_result();
                    
                    while ($tag = $tags_result->fetch_assoc()) {
                        $book['tags'][] = $tag['name'];
                    }
                }
            } catch (Exception $e) {
                // If error, keep default empty tags array
            }
        }
    }
}

// Function to get related books - either from DB or example data
function getRelatedBooks($book_id, $conn, $is_api_book) {
    $related_books = [];
    
    if (!$is_api_book && $book_id) {
        // First check if the book_tags table exists
        try {
            $table_exists = $conn->query("SHOW TABLES LIKE 'book_tags'");
            
            if ($table_exists && $table_exists->num_rows > 0) {
                // Table exists, proceed with query
                $stmt = $conn->prepare("
                    SELECT b.* FROM books b
                    JOIN book_tags bt ON b.id = bt.book_id
                    WHERE bt.tag_name IN (
                        SELECT tag_name FROM book_tags WHERE book_id = ?
                    )
                    AND b.id != ?
                    GROUP BY b.id
                    LIMIT 3
                ");
                $stmt->bind_param("ii", $book_id, $book_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Process cover image path
                        $cover_image = !empty($row['cover_image']) ? $row['cover_image'] : "";
                        
                        // Check if the image path needs to be fixed
                        if (!empty($cover_image)) {
                            // Check all possible image locations
                            $imagePaths = [
                                "../images/books/" . $cover_image,
                                "../images/" . $cover_image,
                                "../" . $cover_image,
                                $cover_image
                            ];
                            
                            $imageFound = false;
                            foreach($imagePaths as $path) {
                                if(file_exists($path)) {
                                    $cover_image = $path;
                                    $imageFound = true;
                                    break;
                                }
                            }
                            
                            if(!$imageFound) {
                                $cover_image = "../images/book-1.jpg";
                            }
                        } else {
                            $cover_image = "../images/book-1.jpg";
                        }
                        
                        $row['cover_img'] = $cover_image;
                        $related_books[] = $row;
                    }
                }
            } else {
                // If book_tags table doesn't exist, try to get related books by author or category
                $stmt = $conn->prepare("
                    SELECT b.* FROM books b
                    WHERE b.id != ? 
                    ORDER BY RAND()
                    LIMIT 3
                ");
                $stmt->bind_param("i", $book_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Process cover image path
                        $cover_image = !empty($row['cover_image']) ? $row['cover_image'] : "";
                        
                        // Check if the image path needs to be fixed
                        if (!empty($cover_image)) {
                            // Check all possible image locations
                            $imagePaths = [
                                "../images/books/" . $cover_image,
                                "../images/" . $cover_image,
                                "../" . $cover_image,
                                $cover_image
                            ];
                            
                            $imageFound = false;
                            foreach($imagePaths as $path) {
                                if(file_exists($path)) {
                                    $cover_image = $path;
                                    $imageFound = true;
                                    break;
                                }
                            }
                            
                            if(!$imageFound) {
                                $cover_image = "../images/book-1.jpg";
                            }
                        } else {
                            $cover_image = "../images/book-1.jpg";
                        }
                        
                        $row['cover_img'] = $cover_image;
                        $related_books[] = $row;
                    }
                }
            }
        } catch (Exception $e) {
            // If any error occurs, we'll use example books below
        }
    } else if ($is_api_book && $book_id) {
        // Fetch book data from API to get subjects for related books
        $api_url = "https://openlibrary.org/works/" . urlencode($book_id) . ".json";
        $response = @file_get_contents($api_url);
        
        if ($response !== false) {
            $api_data = json_decode($response, true);
            
            // Get subjects to find related books
            $subjects = [];
            if (isset($api_data['subjects']) && is_array($api_data['subjects'])) {
                $subjects = array_slice($api_data['subjects'], 0, 2); // Use up to 2 subjects to find related books
            }
            
            if (!empty($subjects)) {
                // Search for books with similar subjects
                $subject_query = implode(' OR ', $subjects);
                $search_url = "https://openlibrary.org/search.json?q=" . urlencode($subject_query) . "&limit=4";
                $search_response = @file_get_contents($search_url);
                
                if ($search_response !== false) {
                    $search_data = json_decode($search_response, true);
                    
                    if (isset($search_data['docs']) && is_array($search_data['docs'])) {
                        $count = 0;
                        foreach ($search_data['docs'] as $doc) {
                            // Skip the current book
                            if (isset($doc['key']) && strpos($doc['key'], $book_id) !== false) {
                                continue;
                            }
                            
                            // Get cover image
                            $cover_img = '../images/book-loader.gif';
                            if (isset($doc['cover_i'])) {
                                $cover_img = "https://covers.openlibrary.org/b/id/" . $doc['cover_i'] . "-M.jpg";
                            }
                            
                            // Get author
                            $author = 'Unknown Author';
                            if (isset($doc['author_name'][0])) {
                                $author = $doc['author_name'][0];
                            }
                            
                            // Create random price and discount
                            $original_price = rand(10, 50) + 0.99;
                            $discount = rand(5, 30);
                            $discounted_price = round($original_price * (1 - $discount/100), 2);
                            
                            // Create book data in same format as database books
                            $related_books[] = [
                                'id' => isset($doc['key']) ? $doc['key'] : '',
                                'title' => isset($doc['title']) ? $doc['title'] : 'Unknown Title',
                                'author' => $author,
                                'cover_img' => $cover_img,
                                'price' => $discounted_price,
                                'original_price' => $original_price,
                                'discount' => $discount,
                                'rating' => rand(35, 50) / 10, // 3.5 to 5.0
                                'reviews' => rand(10, 500),
                                'likes' => rand(100, 10000),
                                'is_api_book' => true
                            ];
                            
                            $count++;
                            if ($count >= 3) {
                                break; // Limit to 3 related books
                            }
                        }
                    }
                }
            }
        }
    }
    
    // If we don't have enough related books, add some example ones
    if (count($related_books) < 3) {
        $example_books = [
            ['id' => 1, 'title' => 'The Giver', 'cover_img' => '../images/book-1.jpg', 'price' => 45.50, 'original_price' => 98.40, 'rating' => 4.7],
            ['id' => 2, 'title' => 'Red Queen', 'cover_img' => '../images/book-4.jpg', 'price' => 32.99, 'original_price' => 65.99, 'rating' => 4.5],
            ['id' => 3, 'title' => 'Percy Jackson', 'cover_img' => '../images/book-10.jpg', 'price' => 28.75, 'original_price' => 57.50, 'rating' => 4.9]
        ];
        
        foreach ($example_books as $ex_book) {
            // Check if this example book is already in our related books
            $exists = false;
            foreach ($related_books as $rel_book) {
                if (isset($rel_book['id']) && $rel_book['id'] == $ex_book['id']) {
                    $exists = true;
                    break;
                }
            }
            
            if (!$exists && count($related_books) < 3) {
                $related_books[] = $ex_book;
            }
        }
    }
    
    return $related_books;
}

// Get related books
$related_books = getRelatedBooks($book_id, $conn, $is_api_book);

// Get books on sale
function getBooksOnSale($conn) {
    $sale_books = [];
    
    // Try to get from database with a more flexible query
    try {
        // First check if discount column exists
        $check_column = $conn->query("SHOW COLUMNS FROM books LIKE 'discount'");
        
        if ($check_column->num_rows > 0) {
            // If discount column exists, use it for filtering
            $stmt = $conn->prepare("SELECT * FROM books WHERE discount > 0 LIMIT 7");
        } else {
            // If no discount column exists, just fetch some books
            $stmt = $conn->prepare("SELECT * FROM books LIMIT 7");
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Process cover image path
                $cover_image = !empty($row['cover_image']) ? $row['cover_image'] : "";
                
                // Check if the image path needs to be fixed
                if (!empty($cover_image)) {
                    // If path doesn't already start with ../images
                    if (strpos($cover_image, '../images') !== 0 && strpos($cover_image, '/images') !== 0) {
                        // Check all possible image locations
                        $imagePaths = [
                            "../images/books/" . $cover_image,
                            "../images/" . $cover_image,
                            "../" . $cover_image,
                            $cover_image
                        ];
                        
                        $imageFound = false;
                        foreach($imagePaths as $path) {
                            if(file_exists($path)) {
                                $cover_image = $path;
                                $imageFound = true;
                                break;
                            }
                        }
                        
                        if(!$imageFound) {
                            $cover_image = "../images/book-1.jpg";
                        }
                    }
                } else {
                    $cover_image = "../images/book-1.jpg";
                }
                
                $row['cover_img'] = $cover_image;
                
                // Set default discount if none exists
                if (!isset($row['discount'])) {
                    $original_price = isset($row['old_price']) ? $row['old_price'] : ($row['price'] * 1.5);
                    if ($original_price > $row['price']) {
                        $row['discount'] = round(100 - (($row['price'] / $original_price) * 100));
                    } else {
                        $row['discount'] = 0;
                    }
                }
                
                // Add rating if it doesn't exist
                if (!isset($row['rating'])) {
                    $row['rating'] = rand(30, 50) / 10; // 3.0 to 5.0
                }
                
                // Add original_price if it doesn't exist
                if (!isset($row['original_price'])) {
                    $row['original_price'] = isset($row['old_price']) ? $row['old_price'] : ($row['price'] * 1.5);
                }
                
                $sale_books[] = $row;
            }
        }
    } catch (Exception $e) {
        // If any error occurs, we'll use example books below
    }
    
    // If not enough books, add examples
    if (count($sale_books) < 7) {
        $example_sale_books = [
            ['title' => 'The Giver', 'cover_img' => '../images/book-1.jpg', 'price' => 45.40, 'original_price' => 90.40, 'discount' => 50],
            ['title' => 'The Wright Brothers', 'cover_img' => '../images/book-2.jpg', 'price' => 45.40, 'original_price' => 90.40, 'discount' => 50],
            ['title' => 'The Ruins Of Gorlan', 'cover_img' => '../images/book-9.jpg', 'price' => 45.40, 'original_price' => 90.40, 'discount' => 50],
            ['title' => 'Percy Jackson', 'cover_img' => '../images/book-10.jpg', 'price' => 45.40, 'original_price' => 90.40, 'discount' => 50],
            ['title' => 'To Kill a Mockingbird', 'cover_img' => '../images/book-5.jpg', 'price' => 45.40, 'original_price' => 90.40, 'discount' => 50],
            ['title' => 'Harry Potter', 'cover_img' => '../images/book-6.jpg', 'price' => 45.40, 'original_price' => 90.40, 'discount' => 50],
            ['title' => 'Heroes of Olympus', 'cover_img' => '../images/book-7.jpg', 'price' => 45.40, 'original_price' => 90.40, 'discount' => 50]
        ];
        
        foreach ($example_sale_books as $ex_book) {
            // Only add if we need more books
            if (count($sale_books) < 7) {
                $sale_books[] = $ex_book;
            }
        }
    }
    
    return $sale_books;
}

$books_on_sale = getBooksOnSale($conn);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($book['title']); ?> - Book Detail Page</title>
    <link rel="stylesheet" href="book-filter.css" />
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
    <link rel="apple-touch-icon" sizes="57x57" href="../favicon/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="../favicon/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="../favicon/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="../favicon/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="../favicon/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="../favicon/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="../favicon/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="../favicon/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192"  href="../favicon/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="../favicon/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="manifest" href="../favicon/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">
  </head>
  <body>
    <header>
      <nav class="navbar-2">
        <div class="logo">
          <div class="img">
            <img src="../images/logo.png" alt="" />
          </div>
          <div class="title">
            <h4>Fassila<i class="fa-solid fa-grid"></i></h4>
            <small>Book Store Website</small>
          </div>
        </div>
        <div class="search-box">
          <div class="search-field">
            <input
              type="text"
              placeholder="Search over 30 million Book titles"
            />
            <button class="search-icon">
              <i class="fa-solid fa-magnifying-glass"></i>
            </button>
          </div>
        </div>
        <div class="nav-end">
          <button class="likebtn">
            <i class="fa-regular fa-heart"></i> <span>35</span>
          </button>
          <button class="cart">
            <a href="cart-item.html"><i class="fa-solid fa-cart-shopping"></i> <span>4</span></a>
          </button>
          <div class="profile-img">
            <img
              src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQiM0o_5tIn0DAmbB2wKS4GvurHctTwxD5om2vi4NOsj1ODDSGULrviZ-QV3ul8JYEMfO0&usqp=CAU"
              alt=""
            />
          </div>
        </div>
      </nav>
    </header>
    <div class="breadcrumb-container">
      <ul class="breadcrumb">
        <li><a href="#">Home</a></li>
        <li><a href="#" style="color: #6c5dd4">Books</a></li>
        <li><a href="#"><?php echo htmlspecialchars($book['title']); ?></a></li>
      </ul>
    </div>

    <section class="book-overview">
      <div class="img">
        <img src="<?php echo htmlspecialchars($book['cover_img']); ?>" alt="" />
      </div>
      <div class="book-content">
        <h4><?php echo htmlspecialchars($book['title']); ?></h4>
        <div class="meta">
          <div class="review">
            <div class="rating">
              <?php for ($i = 0; $i < floor($book['rating']); $i++): ?>
                <i class="fa-solid fa-star"></i>
              <?php endfor; ?>
              <?php for ($i = floor($book['rating']); $i < 5; $i++): ?>
                <i class="fa-regular fa-star"></i>
              <?php endfor; ?>
              <span><?php echo htmlspecialchars($book['rating']); ?></span>
            </div>
            <div class="comment-like">
              <small><img src="../images/comment.png" alt="" /> <span><?php echo htmlspecialchars($book['reviews']); ?> Reviews</span></small>
              <small><img src="../images/like.png" alt="" /> <span><?php echo htmlspecialchars($book['likes']); ?> Likes</span></small>
            </div>
          </div>
          <div class="social-btn">
            <a href=""><i class="fa-brands fa-facebook-f"></i>Facebook</a>
            <a href=""><i class="fa-brands fa-twitter"></i>Twitter</a>
            <a href=""><i class="fa-brands fa-whatsapp"></i>Whatsapp</a>
            <a href=""><i class="fa-regular fa-envelope"></i>Email</a>
          </div>
        </div>
        <p><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
        <div class="footer">
          <div class="author-detail">
            <div class="author">
              <small>Written by</small>
              <strong><?php echo htmlspecialchars($book['author']); ?></strong>
            </div>
            <div class="publisher">
              <small>Publisher</small>
              <strong><?php echo htmlspecialchars($book['publisher']); ?></strong>
            </div>
            <div class="year">
              <small>Year</small>
              <strong><?php echo htmlspecialchars($book['year']); ?></strong>
            </div>
          </div>
          <div class="badge">
            <span><i class="fa-solid fa-bolt-lightning"></i>free shipping</span>
            <span><i class="fa-solid fa-shield"></i>in stocks</span>
          </div>
        </div>
        <div class="book-price">
          <div class="price">
            <strong>$<?php echo htmlspecialchars($book['price']); ?></strong>
            <strike>$<?php echo htmlspecialchars($book['original_price']); ?></strike>
            <span><?php echo htmlspecialchars($book['discount']); ?>%</span>
          </div>
          <div class="input-group">
            <div class="quantity">
              <input
                type="button"
                value="-"
                class="button-minus"
                data-field="quantity"
              />
              <input
                type="text"
                step="1"
                min="1"
                value="1"
                name="quantity"
                class="quantity-field"
                style="width: 4.5rem"
              />
              <input
                type="button"
                value="+"
                class="button-plus"
                data-field="quantity"
              />
            </div>
            <button class="cartbtn" 
              data-book-id="<?php echo htmlspecialchars($book_id); ?>" 
              data-book-name="<?php echo htmlspecialchars($book['title']); ?>" 
              data-book-price="<?php echo htmlspecialchars($book['price']); ?>" 
              data-book-image="<?php echo htmlspecialchars($book['cover_img']); ?>">
              <i class="fa-solid fa-cart-shopping"></i>Add to Cart
            </button>
            <button class="like">
              <?php if ($user_has_liked): ?>
                <i class="fa-solid fa-heart"></i>
              <?php else: ?>
                <i class="fa-regular fa-heart"></i>
              <?php endif; ?>
            </button>
          </div>
        </div>
      </div>
    </section>
    <section class="book-info">
      <div class="detail-customer">
        <div class="tabbtns">
          <button class="tablink" data-btn="detail">Details Product</button>
          <button class="tablink" data-btn="customer">Customer Reviews</button>
        </div>
        <div class="book-detail tabcontent" id="detail">
          <div class="detail-line">
            <strong>Book Title</strong><span><?php echo htmlspecialchars($book['title']); ?></span>
          </div>
          <div class="detail-line">
            <strong>Author</strong><span><?php echo htmlspecialchars($book['author']); ?></span>
          </div>
          <div class="detail-line">
            <strong>ISBN</strong><span><?php echo htmlspecialchars($book['isbn']); ?></span>
          </div>
          <div class="detail-line">
            <strong>Edition Language</strong><span><?php echo htmlspecialchars($book['language']); ?></span>
          </div>
          <div class="detail-line">
            <strong>Book Format</strong><span><?php echo htmlspecialchars($book['format']); ?>, <?php echo htmlspecialchars($book['pages']); ?> Pages</span>
          </div>
          <div class="detail-line">
            <strong>Date Published</strong><span><?php echo htmlspecialchars($book['year']); ?></span>
          </div>
          <div class="detail-line">
            <strong>Publisher</strong><span><?php echo htmlspecialchars($book['publisher']); ?></span>
          </div>
          <div class="detail-line tag-line">
            <strong>Tags</strong>
            <div class="tags">
              <?php foreach ($book['tags'] as $tag): ?>
                <span><?php echo htmlspecialchars($tag); ?></span>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="customer-review tabcontent" id="customer">
          <div class="rating">
            <div class="rating-info">
              <h5>Rating Information</h5>
              <p>Average user rating for this book.</p>
            </div>
            <div class="star">
              <small><span><?php echo htmlspecialchars($book['rating']); ?></span>out of 5</small>
              <div class="stars">
                <?php for ($i = 0; $i < floor($book['rating']); $i++): ?>
                  <i class="fa-solid fa-star"></i>
                <?php endfor; ?>
                <?php for ($i = floor($book['rating']); $i < 5; $i++): ?>
                  <i class="fa-regular fa-star"></i>
                <?php endfor; ?>
              </div>
            </div>
          </div>
          
          <?php
          // Fetch actual reviews from the database
          $reviews = [];
          if (!$is_api_book && $book_id) {
              $review_stmt = $conn->prepare("
                  SELECT r.*, u.name, u.profile_picture 
                  FROM reviews r 
                  JOIN users u ON r.user_id = u.id 
                  WHERE r.book_id = ? 
                  ORDER BY r.created_at DESC
                  LIMIT 10
              ");
              $review_stmt->bind_param("i", $book_id);
              $review_stmt->execute();
              $review_result = $review_stmt->get_result();
              
              while ($row = $review_result->fetch_assoc()) {
                  $reviews[] = $row;
              }
          }
          ?>
          
          <strong>Showing <?php echo count($reviews); ?> of <?php echo htmlspecialchars($book['reviews']); ?> reviews</strong>
          
          <?php if(isset($_SESSION['user_id'])): ?>
            <!-- Review submission form -->
            <div class="review-form-container">
              <h4>Write Your Review</h4>
              <form action="review_process.php" method="post">
                <input type="hidden" name="book_id" value="<?php echo htmlspecialchars($book_id); ?>">
                <input type="hidden" name="is_api_book" value="<?php echo $is_api_book ? '1' : '0'; ?>">
                
                <div class="rating-selection">
                  <label>Your Rating:</label>
                  <div class="star-rating">
                    <input type="radio" id="star5" name="rating" value="5" required><label for="star5"></label>
                    <input type="radio" id="star4" name="rating" value="4"><label for="star4"></label>
                    <input type="radio" id="star3" name="rating" value="3"><label for="star3"></label>
                    <input type="radio" id="star2" name="rating" value="2"><label for="star2"></label>
                    <input type="radio" id="star1" name="rating" value="1"><label for="star1"></label>
                  </div>
                </div>
                
                <div class="review-text-area">
                  <label for="review_text">Your Review:</label>
                  <textarea name="review_text" id="review_text" rows="5" required></textarea>
                </div>
                
                <button type="submit" class="submit-review">Submit Review</button>
              </form>
            </div>
            
            <style>
              .review-form-container {
                margin-top: 30px;
                padding: 20px;
                background-color: #f9f9f9;
                border-radius: 8px;
              }
              .review-form-container h4 {
                margin-bottom: 15px;
                color: #333;
              }
              .rating-selection {
                margin-bottom: 15px;
                display: flex;
                align-items: center;
              }
              .star-rating {
                display: inline-flex;
                flex-direction: row-reverse;
                margin-left: 15px;
              }
              .star-rating input {
                display: none;
              }
              .star-rating label {
                cursor: pointer;
                width: 25px;
                height: 25px;
                background-image: url('data:image/svg+xml;charset=UTF-8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" fill="%23d4d4d4"/></svg>');
                background-repeat: no-repeat;
                background-position: center;
                background-size: 23px;
              }
              .star-rating input:checked ~ label,
              .star-rating label:hover,
              .star-rating label:hover ~ label {
                background-image: url('data:image/svg+xml;charset=UTF-8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" fill="%23ffc107"/></svg>');
              }
              .review-text-area {
                margin-bottom: 15px;
              }
              .review-text-area label {
                display: block;
                margin-bottom: 5px;
                font-weight: 500;
              }
              .review-text-area textarea {
                width: 100%;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                resize: vertical;
              }
              .submit-review {
                background-color: #6c5dd4;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 4px;
                cursor: pointer;
                font-weight: 600;
              }
              .submit-review:hover {
                background-color: #5a4cbe;
              }
            </style>
          <?php else: ?>
            <div class="login-to-review">
              <p>Please <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" style="color: #6c5dd4; font-weight: bold;">login</a> to write a review.</p>
            </div>
          <?php endif; ?>

          <?php if(!empty($reviews)): ?>
            <div class="reviewer-container">
              <?php foreach($reviews as $review): ?>
                <div class="review">
                  <div class="img-detail">
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
                    <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="">
                    <div class="name">
                      <h5><?php echo htmlspecialchars($review['name']); ?></h5>
                      <small><?php echo date('F j, Y', strtotime($review['created_at'])); ?></small>
                    </div>
                  </div>
                  <div class="review-footer">
                    <p><?php echo htmlspecialchars($review['review_text']); ?></p>
                    <div class="rating-star">
                      <?php for($i = 0; $i < $review['rating']; $i++): ?>
                        <i class="fa-solid fa-star"></i>
                      <?php endfor; ?>
                      <?php for($i = $review['rating']; $i < 5; $i++): ?>
                        <i class="fa-regular fa-star"></i>
                      <?php endfor; ?>
                      <span><?php echo htmlspecialchars($review['rating']); ?>.0</span>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
              <?php if(count($reviews) < $book['reviews']): ?>
                <button onclick="window.location.href='all_reviews.php?book_id=<?php echo $book_id; ?>&api=<?php echo $is_api_book ? '1' : '0'; ?>'">View More</button>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <div class="no-reviews">
              <p>No reviews yet. Be the first to review this book!</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="related-book">
        <h4>Related Books</h4>
        <div class="book-container">
          <?php foreach ($related_books as $related_book): ?>
            <div class="book">
              <div class="img">
                <?php if(isset($related_book['is_api_book']) && $related_book['is_api_book']): ?>
                  <a href="book-detail.php?id=<?php echo htmlspecialchars(str_replace('/works/', '', $related_book['id'])); ?>&api=1">
                    <img src="<?php echo htmlspecialchars($related_book['cover_img']); ?>" alt="">
                  </a>
                <?php else: ?>
                  <a href="book-detail.php?id=<?php echo htmlspecialchars($related_book['id']); ?>">
                    <img src="<?php echo htmlspecialchars($related_book['cover_img']); ?>" alt="">
                  </a>
                <?php endif; ?>
              </div>
              <div class="content">
                <h5><?php echo htmlspecialchars($related_book['title']); ?></h5>
                <div class="badge">
                  <?php 
                  // Generate dynamic genre tags based on book title or use default tags
                  $title = strtolower($related_book['title']);
                  $genres = [];
                  
                  if (strpos($title, 'harry') !== false || strpos($title, 'potter') !== false) {
                    $genres = ['fantasy', 'magic', 'adventure'];
                  } elseif (strpos($title, 'percy') !== false || strpos($title, 'olympus') !== false) {
                    $genres = ['mythology', 'adventure', 'young adult'];
                  } elseif (strpos($title, 'giver') !== false) {
                    $genres = ['dystopian', 'sci-fi', 'young adult'];
                  } elseif (strpos($title, 'mockingbird') !== false) {
                    $genres = ['classic', 'drama', 'historical'];
                  } elseif (strpos($title, 'ruins') !== false || strpos($title, 'gorlan') !== false) {
                    $genres = ['adventure', 'fantasy', 'action'];
                  } elseif (strpos($title, 'red queen') !== false) {
                    $genres = ['fantasy', 'romance', 'dystopian'];
                  } elseif (strpos($title, 'wright') !== false || strpos($title, 'brother') !== false) {
                    $genres = ['biography', 'history', 'aviation'];
                  } else {
                    // Default genres if no match
                    $genres = ['fiction', 'bestseller'];
                  }
                  
                  // Show up to 3 genres
                  $count = 0;
                  foreach ($genres as $genre) {
                    if ($count < 3) {
                      echo '<span>' . htmlspecialchars($genre) . '</span>';
                      $count++;
                    }
                  }
                  ?>
                </div>
                <div class="rating-review">
                  <span><i class="fa-solid fa-star"></i><?php echo isset($related_book['rating']) ? htmlspecialchars($related_book['rating']) : '4.0'; ?></span>
                  <span>244 Reviews</span>
                </div>
                <div class="price">
                  <strong>$<?php echo htmlspecialchars($related_book['price']); ?></strong>
                  <strike>$<?php echo isset($related_book['original_price']) ? htmlspecialchars($related_book['original_price']) : htmlspecialchars($related_book['price']); ?></strike>
                </div>
                <div class="btn">
                  <button class="cartbtn"><i class="fa-solid fa-cart-shopping"></i>Add to cart</button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
          <div class="morebtn">
            <button class="view-more" style="cursor: pointer;" onclick="window.location.href='book-filter.php'">View More</button>
          </div>
        </div>
      </div>
    </section>

    <section class="book-sale">
      <div class="heading">
        <h4>Books On Sale</h4>
        <div class="arrowbtn">
          <i id="left" class="fa-solid fa-angle-left"></i>
          <i id="right" class="fa-solid fa-angle-right"></i>
        </div>
      </div>
      <div class="book-container">
        <div class="wrapper">
          <ul class="carousel">
            <?php foreach ($books_on_sale as $sale_book): ?>
              <li class="card">
                <div class="img">
                  <?php if(isset($sale_book['id'])): ?>
                    <a href="book-detail.php?id=<?php echo htmlspecialchars($sale_book['id']); ?>">
                      <img src="<?php echo htmlspecialchars($sale_book['cover_img']); ?>" alt="" />
                    </a>
                  <?php else: ?>
                    <a href="book-filter.php">
                      <img src="<?php echo htmlspecialchars($sale_book['cover_img']); ?>" alt="" />
                    </a>
                  <?php endif; ?>
                  <span class="badge"><?php echo htmlspecialchars($sale_book['discount']); ?>%</span>
                </div>
                <?php if(isset($sale_book['id'])): ?>
                  <a href="book-detail.php?id=<?php echo htmlspecialchars($sale_book['id']); ?>" style="color: inherit; text-decoration: none;">
                    <h5><?php echo htmlspecialchars($sale_book['title']); ?></h5>
                  </a>
                <?php else: ?>
                  <a href="book-filter.php" style="color: inherit; text-decoration: none;">
                    <h5><?php echo htmlspecialchars($sale_book['title']); ?></h5>
                  </a>
                <?php endif; ?>
                <div class="genre">
                  <?php 
                  // Generate dynamic genre tags based on book title
                  $title = strtolower($sale_book['title']);
                  $genres = [];
                  
                  if (strpos($title, 'harry') !== false || strpos($title, 'potter') !== false) {
                    echo '<span>fantasy</span><span>magic</span>';
                  } elseif (strpos($title, 'percy') !== false || strpos($title, 'olympus') !== false) {
                    echo '<span>mythology</span><span>adventure</span>';
                  } elseif (strpos($title, 'giver') !== false) {
                    echo '<span>dystopian</span><span>sci-fi</span>';
                  } elseif (strpos($title, 'mockingbird') !== false) {
                    echo '<span>classic</span><span>drama</span>';
                  } elseif (strpos($title, 'ruins') !== false || strpos($title, 'gorlan') !== false) {
                    echo '<span>adventure</span><span>fantasy</span>';
                  } elseif (strpos($title, 'red queen') !== false) {
                    echo '<span>fantasy</span><span>dystopian</span>';
                  } elseif (strpos($title, 'wright') !== false || strpos($title, 'brother') !== false) {
                    echo '<span>biography</span><span>history</span>';
                  } else {
                    // Default genres if no match
                    echo '<span>fiction</span><span>bestseller</span>';
                  }
                  ?>
                </div>
                <div class="footer">
                  <span class="star"><i class="fa fa-star"></i> <?php echo isset($sale_book['rating']) ? htmlspecialchars($sale_book['rating']) : '4.0'; ?></span>
                  <div class="price">
                    <span>$<?php echo htmlspecialchars($sale_book['price']); ?></span>
                    <span><strike>$<?php echo htmlspecialchars($sale_book['original_price']); ?></strike></span>
                  </div>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </section>

    <section class="service">
      <div class="service-container">
        <div class="service-card">
          <div class="icon">
            <i class="fa-solid fa-bolt-lightning"></i>
          </div>
          <div class="service-content">
            <h5>Quick Delivery</h5>
            <p>
            Fast and reliable delivery across all 58 wilayas of Algeria. Get your order wherever you are!
            </p>
          </div>
        </div>
        <div class="service-card">
          <div class="icon">
            <i class="fa-solid fa-shield"></i>
          </div>
          <div class="service-content">
            <h5>Secure Payment</h5>
            <p>
              All transactions are encrypted and processed securely.
            </p>
          </div>
        </div>
        <div class="service-card">
          <div class="icon">
            <i class="fa-solid fa-thumbs-up"></i>
          </div>
          <div class="service-content">
            <h5>Best Quality</h5>
            <p>
              We source books directly from publishers to ensure quality.
            </p>
          </div>
        </div>
        <div class="service-card">
          <div class="icon">
            <i class="fa-solid fa-star"></i>
          </div>
          <div class="service-content">
            <h5>Return Guarantee</h5>
            <p>
              30-day return policy on all purchases, no questions asked.
            </p>
          </div>
        </div>
      </div>
    </section>

    <section class="subscription">
      <div class="container">
        <h4>Subscribe our newsletter for Latest <br> books updates</h4>
        <div class="input">
          <input type="text" placeholder="Type your email here">
          <button>subscribe</button>
        </div>
      </div>
      <div class="circle-1"></div>
      <div class="circle-2"></div>
    </section>

    <footer>
      <div class="container">
        <div class="logo-description">
          <div class="logo">
            <div class="img">
              <img src="../images/logo.png" alt="">
            </div>
            <div class="title">
              <h4>Fassila</h4>
              <small>Book Store Website</small>
            </div>
          </div>
          <div class="logo-body">
            <p>Your trusted online destination for quality books from all genres with fast shipping and secure transactions.</p>
          </div>
          <div class="social-links">
            <h4>Follow Us</h4>
            <ul class="links">
              <li><a href=""><i class="fa-brands fa-facebook-f"></i></a></li>
              <li><a href=""><i class="fa-brands fa-youtube"></i></a></li>
              <li><a href=""><i class="fa-brands fa-twitter"></i></a></li>
              <li><a href=""><i class="fa-brands fa-linkedin"></i></a></li>
              <li><a href=""><i class="fa-brands fa-instagram"></i></a></li>
            </ul>
          </div>
        </div>
        <div class="categories list">
          <h4>Book Categories</h4>
          <ul>
            <li><a href="">Action</a></li>
            <li><a href="">Adventure</a></li>
            <li><a href="">Comedy</a></li>
            <li><a href="">Crime</a></li>
            <li><a href="">Drama</a></li>
            <li><a href="">Fantasy</a></li>
            <li><a href="">Horror</a></li>
          </ul>
        </div>
        <div class="quick-links list">
          <h4>Quick Links</h4>
          <ul>
            <li><a href="../index.html">About Us</a></li>
            <li><a href="contact.html">Contact Us</a></li>
            <li><a href="book-filter.html">Products</a></li>
            <li><a href="login.php">Login</a></li>
            <li><a href="registration.php">Sign Up</a></li>
            <li><a href="cart-item.html">Cart</a></li>
            <li><a href="checkout.php">Checkout</a></li>
          </ul>
        </div>
        <div class="our-store list">
          <h4>Our Store</h4>
          <div class="map" style="margin-top: 1rem;">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d6310.594819201665!2d-122.42768319999999!3d37.73616639999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x808f7e60a337d5f5%3A0xfa0bb626904e5ab2!2z4KSV4KWJ4KSy4KWH4KScIOCkueCkv-Cksiwg4KS44KS-4KSoIOCkq-CljeCksOCkvuCkguCkuOCkv-CkuOCljeCkleCliywg4KSV4KWI4KSy4KWA4KSr4KWL4KSw4KWN4KSo4KS_4KSv4KS-LCDgpK_gpYLgpKjgpL7gpIfgpJ_gpYfgpKEg4KS44KWN4KSf4KWH4KSf4KWN4oCN4KS4!5e0!3m2!1shi!2sin!4v1686917463994!5m2!1shi!2sin" height="70" style="width: 100%;border: none;border-radius: 5px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
          </div>
          <ul>
            <li><a href=""><i class="fa-solid fa-location-dot"></i>832 Thompson Drive,San Fransisco CA 94 107,United States</a></li>
            <li><a href=""><i class="fa-solid fa-phone"></i>+12 1345678991</a></li>
            <li><a href=""><i class="fa-solid fa-envelope"></i>support@Fassila.id</a></li>
          </ul>
        </div>
      </div>
    </footer>
    <button class="back-to-top"><i class="fa-solid fa-chevron-up"></i></button>

    <script>
      const tabbtn = document.querySelectorAll(".tablink");
      for (let i = 0; i < tabbtn.length; i++) {
        tabbtn[i].addEventListener('click',() => {
          let tabName = tabbtn[i].dataset.btn;
          let tabContent = tabbtn[i].dataset.btn;
          let AllTabContent = document.querySelectorAll(".tabcontent");
          let tabbtns = document.querySelectorAll(".tablink");
          for (let j = 0; j < AllTabContent.length; j++) {
            AllTabContent[j].style.display = "none";
          }
          tabContent.style.display = "block";
          
        })
        
      }
    </script>

    <script
    src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.3/jquery.min.js"
    integrity="sha512-STof4xm1wgkfm7heWqFJVn58Hm3EtS31XFaagaa8VMReCXAkQnJZ+jEy8PCC/iT18dFy95WcExNHFTqLyp72eQ=="
    crossorigin="anonymous"
    referrerpolicy="no-referrer"
  ></script>
  <script src="../js/repeat-js.js"></script>
    <script src="../js/increment-decrement.js"></script>
    <script src="../js/back-to-top.js"></script>
    
    <!-- Like functionality script -->
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // Like button functionality
        const likeBtn = document.querySelector('.like');
        if (likeBtn) {
          likeBtn.addEventListener('click', function() {
            <?php if (isset($_SESSION['user_id'])): ?>
              const bookId = <?php echo json_encode($book_id); ?>;
              const isApiBook = <?php echo json_encode($is_api_book); ?>;
              
              // Send AJAX request to like_process.php
              const formData = new FormData();
              formData.append('book_id', bookId);
              formData.append('is_api_book', isApiBook ? '1' : '0');
              
              fetch('like_process.php', {
                method: 'POST',
                body: formData
              })
              .then(response => response.json())
              .then(data => {
                if (data.success) {
                  // Update like button icon
                  const heartIcon = likeBtn.querySelector('i');
                  if (data.action === 'liked') {
                    heartIcon.className = 'fa-solid fa-heart';
                    // Show success message
                    showToast('Added to favorites!');
                  } else {
                    heartIcon.className = 'fa-regular fa-heart';
                    // Show success message
                    showToast('Removed from favorites.');
                  }
                  
                  // Update like count
                  const likesCount = document.querySelector('.comment-like small:nth-child(2) span');
                  if (likesCount) {
                    likesCount.textContent = data.likes + ' Likes';
                  }
                } else {
                  // Show error message
                  showToast(data.message || 'An error occurred.');
                }
              })
              .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while processing your request.');
              });
            <?php else: ?>
              // Redirect to login page
              window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
            <?php endif; ?>
          });
        }
      });
      
      // Function to show toast message
      function showToast(message) {
        // Create toast container if it doesn't exist
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
          toastContainer = document.createElement('div');
          toastContainer.id = 'toast-container';
          document.body.appendChild(toastContainer);
          
          // Add styles for the toast container
          toastContainer.style.position = 'fixed';
          toastContainer.style.bottom = '20px';
          toastContainer.style.right = '20px';
          toastContainer.style.zIndex = '1000';
        }
        
        // Create toast element
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.textContent = message;
        
        // Style the toast
        toast.style.minWidth = '250px';
        toast.style.backgroundColor = '#6c5dd4';
        toast.style.color = 'white';
        toast.style.padding = '12px';
        toast.style.borderRadius = '4px';
        toast.style.marginTop = '10px';
        toast.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s ease-in-out';
        
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

    <!-- Add to Cart functionality script -->
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const cartButton = document.querySelector('.cartbtn');

        if (cartButton) {
          cartButton.addEventListener('click', function() {
            const bookId = this.dataset.bookId;
            const bookName = this.dataset.bookName;
            const bookPrice = this.dataset.bookPrice;
            const bookImage = this.dataset.bookImage;
            const quantity = document.querySelector('input[data-field="quantity"]').value || 1;

            const formData = new URLSearchParams();
            formData.append('book_id', bookId);
            formData.append('book_name', bookName);
            formData.append('book_price', bookPrice);
            formData.append('book_image', bookImage);
            formData.append('quantity', quantity);

            fetch('../api/add_to_cart.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
              },
              body: formData.toString()
            })
            .then(response => response.json())
            .then(data => {
              if (data.status === 'success') {
                alert('Book added to cart successfully!');
              } else {
                alert('Error: ' + data.message);
              }
            })
            .catch(error => {
              console.error('Error:', error);
            });
          });
        }
      });
    </script>
  </body>
</html>
