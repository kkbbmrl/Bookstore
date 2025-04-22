<?php
// filepath: c:\xampp\htdocs\Bookstore\Pages\search.php
// Start session if needed
session_start();

// Include database connection
require_once '../connection.php';

// Initialize variables
$books = [];
$external_books = [];
$searchQuery = '';
$message = '';
$totalResults = 0;

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if search query is provided
if(isset($_GET['query']) && !empty($_GET['query'])) {
    // Get and sanitize the search query
    $searchQuery = sanitize_input($_GET['query']);
    
    // PART 1: Search local database with proper joins for authors and categories
    $sql = "SELECT b.*, 
                   GROUP_CONCAT(DISTINCT a.name SEPARATOR ', ') as author,
                   GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as categories
            FROM books b
            LEFT JOIN book_authors ba ON b.id = ba.book_id
            LEFT JOIN authors a ON ba.author_id = a.id
            LEFT JOIN book_categories bc ON b.id = bc.book_id
            LEFT JOIN categories c ON bc.category_id = c.id
            WHERE 
                b.title LIKE ? OR 
                b.description LIKE ? OR 
                b.isbn LIKE ? OR
                b.publisher LIKE ? OR
                a.name LIKE ? OR
                c.name LIKE ?
            GROUP BY b.id
            ORDER BY b.title ASC";
    
    // Prepare and execute the statement
    $stmt = $conn->prepare($sql);
    $searchTerm = "%" . $searchQuery . "%";
    $stmt->bind_param("ssssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Get local results
    $books = $result->fetch_all(MYSQLI_ASSOC);
    $localResults = count($books);
    
    // PART 2: Search Open Library API
    $apiUrl = 'https://openlibrary.org/search.json?q=' . urlencode($searchQuery) . '&limit=10';
    
    // Make API request
    $response = @file_get_contents($apiUrl);

    if ($response !== false) {
        $apiData = json_decode($response, true);
        
        // Process Open Library results
        if (isset($apiData['docs']) && count($apiData['docs']) > 0) {
            foreach($apiData['docs'] as $doc) {
                // Check if book already exists in local results (by ISBN)
                $isbn = isset($doc['isbn']) && !empty($doc['isbn']) ? $doc['isbn'][0] : '';
                
                $exists = false;
                foreach($books as $book) {
                    if(!empty($book['isbn']) && !empty($isbn) && $book['isbn'] == $isbn) {
                        $exists = true;
                        break;
                    }
                }
                
                // Only add if not already in local results
                if (!$exists) {
                    $coverID = isset($doc['cover_i']) ? $doc['cover_i'] : '';
                    $coverUrl = $coverID ? "https://covers.openlibrary.org/b/id/{$coverID}-M.jpg" : '';
                    
                    // Create a random price and discount
                    $original_price = rand(10, 50) + 0.99;
                    $discount = rand(5, 30);
                    $discounted_price = round($original_price * (1 - $discount/100), 2);
                    
                    // Get work key for linking to book detail
                    $work_key = isset($doc['key']) ? $doc['key'] : '';
                    if (strpos($work_key, '/works/') === false && isset($doc['key'])) {
                        $work_key = '/works/' . $doc['key'];
                    }
                    
                    $external_books[] = [
                        'id' => str_replace('/works/', '', $work_key),
                        'title' => $doc['title'] ?? 'Unknown Title',
                        'author' => isset($doc['author_name']) ? implode(', ', $doc['author_name']) : 'Unknown Author',
                        'description' => '',  // Open Library search doesn't include descriptions
                        'publisher' => isset($doc['publisher']) && !empty($doc['publisher']) ? $doc['publisher'][0] : '',
                        'published_date' => isset($doc['first_publish_year']) ? $doc['first_publish_year'] : '',
                        'isbn' => $isbn,
                        'page_count' => $doc['number_of_pages_median'] ?? 0,
                        'categories' => isset($doc['subject']) ? implode(', ', array_slice($doc['subject'], 0, 3)) : '',
                        'cover_img' => $coverUrl,
                        'preview_link' => "https://openlibrary.org" . $work_key,
                        'is_external' => true,
                        'rating' => round(rand(35, 50) / 10, 1), // 3.5 to 5.0
                        'reviews' => rand(10, 500),
                        'likes' => rand(100, 10000),
                        'price' => $discounted_price,
                        'original_price' => $original_price,
                        'discount' => $discount
                    ];
                }
            }
        }
    }
    
    // Total results
    $totalResults = $localResults + count($external_books);
    
    // Set appropriate message
    if($totalResults == 0) {
        $message = "No books found matching '" . $searchQuery . "'";
    } else {
        $message = $totalResults . " book(s) found matching '" . $searchQuery . "'";
    }
} else {
    $message = "Please enter a search term";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Fassila Bookstore</title>
    <link rel="stylesheet" href="../css/style.css" />
    <link rel="stylesheet" href="book-filter.css" />
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Fontawesome Link for Icons -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <!--- google font link-->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap"
      rel="stylesheet"
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
    <link rel="icon" type="image/png" sizes="192x192" href="../favicon/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="../favicon/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="manifest" href="../favicon/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">
    <style>
        /* Additional styling for search results page */
        .search-results {
            padding: 2rem 5%;
            min-height: 50vh;
            background-color: #f8f9fa;
        }
        
        /* Search box styling */
        .navbar-2 .search-box {
            flex: 1 1 350px;
            max-width: 400px;
            min-width: 220px;
            margin: 0 30px;
            display: flex;
            align-items: center;
        }
        .search-box form {
            width: 100%;
        }
        .search-field {
            width: 100%;
            display: flex;
            background-color: #f3f0fe;
            border-radius: 8px;
            overflow: hidden;
            height: 45px;
            border: 1px solid transparent;
            transition: all 0.3s ease;
        }
        
        .search-field:focus-within {
            border-color: #6c5dd4;
            box-shadow: 0 0 0 3px rgba(108, 93, 212, 0.15);
        }
        
        .search-field input {
            flex: 1;
            height: 100%;
            border: none;
            outline: none;
            background-color: transparent;
            padding: 0 15px;
            font-size: 14px;
            color: #131428;
        }
        
        .search-field input::placeholder {
            color: #696a6e;
            font-size: 14px;
        }
        
        .search-field .search-icon {
            background-color: #6c5dd4;
            border: none;
            height: 100%;
            width: 50px;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s;
        }
        
        .search-field .search-icon:hover {
            background-color: #5a4cb8;
        }
        
        .search-field.loading .search-icon {
            position: relative;
            overflow: hidden;
        }
        
        .search-field.loading .search-icon::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        @media (max-width: 900px) {
            .navbar-2 .search-box {
                max-width: 100%;
                margin: 10px 0;
            }
        }
        
        @media (max-width: 600px) {
            .navbar-2 .search-box {
                min-width: 0;
                margin: 10px 0;
            }
        }
        
        @media (max-width: 768px) {
            .search-box {
                width: 100%;
                max-width: 500px;
                margin: 0;
            }
        }
        
        .search-message {
            margin-bottom: 1.5rem;
            color: #666;
            font-size: 1.1rem;
        }
        
        .search-header h3 {
            color: #6c5dd4;
            font-weight: 700;
            margin-bottom: 1rem;
            position: relative;
            padding-bottom: 10px;
        }
        
        .search-header h3:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, #6c5dd4, #ff7a00);
            border-radius: 10px;
        }
        
        .api-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #0074D9;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            z-index: 10;
        }
        
        .books-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 3rem;
        }
        
        .book-card {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            background: white;
            height: 100%;
            display: flex;
            flex-direction: column;
            border: none;
        }
        
        .book-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 25px rgba(108, 93, 212, 0.2);
        }
        
        .book-image {
            height: 250px;
            overflow: hidden;
            position: relative;
        }
        
        .book-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .book-card:hover .book-image img {
            transform: scale(1.05);
        }
        
        .book-details {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            background: #fff;
        }
        
        .book-title {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 8px;
            color: #333;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.3;
        }
        
        .book-author {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 12px;
        }
        
        .book-rating-review {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            gap: 15px;
        }
        
        .rating {
            display: flex;
            align-items: center;
        }
        
        .rating i {
            color: #FFD700; /* Yellow stars */
            margin-right: 5px;
        }
        
        .book-rating-review span {
            color: #FF6B35; /* Orange reviews */
            font-size: 0.85rem;
        }
        
        .badge {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 15px;
        }
        
        .badge span {
            background-color: #f0f0f0;
            color: #666;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            text-transform: capitalize;
        }
        
        .book-price {
            margin-top: auto;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .book-price strong {
            color: #6c5dd4;
            font-size: 1.2rem;
            margin-right: 8px;
        }
        
        .book-old-price {
            text-decoration: line-through;
            color: #999;
            font-size: 0.9rem;
        }
        
        .book-stock {
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        
        .in-stock {
            color: #28a745;
            display: flex;
            align-items: center;
        }
        
        .in-stock:before {
            content: '•';
            font-size: 1.5rem;
            margin-right: 5px;
        }
        
        .low-stock {
            color: #ffc107;
            display: flex;
            align-items: center;
        }
        
        .low-stock:before {
            content: '•';
            font-size: 1.5rem;
            margin-right: 5px;
        }
        
        .out-of-stock {
            color: #dc3545;
            display: flex;
            align-items: center;
        }
        
        .out-of-stock:before {
            content: '•';
            font-size: 1.5rem;
            margin-right: 5px;
        }
        
        .preview-link {
            text-decoration: none;
            width: 100%;
        }
        
        .preview-button {
            width: 100%;
            background-color: #6c5dd4;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .preview-button:hover {
            background-color: #5a4cb8;
        }
        
        /* Book badges */
        .book-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            z-index: 5;
        }
        
        .bestseller-badge {
            background-color: #FF6B35;
            color: white;
        }
        
        .featured-badge {
            background-color: #6c5dd4;
            color: white;
        }
        
        /* No results styling */
        .no-results {
            text-align: center;
            padding: 50px 0;
            color: #666;
        }
        
        .no-results h4 {
            margin: 15px 0;
            font-weight: 600;
        }
        
        /* Additional Bootstrap enhancements */
        .breadcrumb-container {
            background-color: #f2f2f2;
            padding: 10px 5%;
        }
        
        .breadcrumb {
            margin-bottom: 0;
        }
        
        /* Google Books separator */
        .google-books-separator {
            display: flex;
            align-items: center;
            margin: 40px 0;
            width: 100%;
        }
        
        .google-books-separator h3 {
            padding: 0 20px;
            color: #6c5dd4;
            font-size: 1.4rem;
            white-space: nowrap;
            margin-bottom: 0;
        }
        
        .separator-line {
            height: 2px;
            background: linear-gradient(to right, transparent, #d1d1d1, transparent);
            flex-grow: 1;
        }
        
        @media (max-width: 768px) {
            .google-books-separator h3 {
                font-size: 1.1rem;
                padding: 0 10px;
            }
        }
        
        /* Footer styles */
      
        
  
        
        .contact-info p {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
            color: #bbb;
        }
        
        .contact-info i {
            margin-right: 10px;
            color: #6c5dd4;
        }
        
        .social-icons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .social-icons a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #3a3952;
            color: #fff;
            transition: all 0.3s;
        }
        
        .social-icons a:hover {
            background: #6c5dd4;
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
<header>
        <nav class="navbar-2">
          <div class="logo">
            <div class="img">
              <img src="../images/logo.png" alt="" />
            </div>
            <div class="title">
                <h4><a href="../index.php" style="text-decoration: none; color: inherit;">Fassila<i class="fa-solid fa-grid"></i></a></h4>
              <small>Book Store Website</small>
            </div>
          </div>
          <div class="search-box">
  <form action="search.php" method="GET">
    <div class="search-field">
      <input
        type="text"
        name="query"
        placeholder="Search over 30 million Book titles"
        required
      />
      <button class="search-icon" type="submit">
        <i class="fa-solid fa-magnifying-glass"></i>
      </button>
    </div>
  </form>
</div>
          <div class="nav-end">
            <button class="likebtn">
              <i class="fa-regular fa-heart"></i> <span>35</span>
            </button>
            <button class="cart">
              <a href="cart-item.php"><i class="fa-solid fa-cart-shopping"></i> <span>4</span></a>
            </button>
            <div class="profile-img">
              <a href="account.php">
                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQiM0o_5tIn0DAmbB2wKS4GvurHctTwxD5om2vi4NOsj1ODDSGULrviZ-QV3ul8JYEMfO0&amp;usqp=CAU" alt="">
              </a>
            </div>
          </div>
        </nav>
      </header>

      <div class="breadcrumb-container">
        <ul class="breadcrumb">
            <li><a href="../index.php" style="color: #6c5dd4">Home</a></li>
          <li><a href="#">Search Results</a></li>
        </ul>
      </div>

    <section class="search-results">
        <div class="container">
            <div class="search-header mb-4">
                <h3 class="display-6">Search Results</h3>
                <div class="search-message"><?php echo $message; ?></div>
            </div>
            
            <?php if($totalResults > 0): ?>
                <!-- Display local database results -->
                <?php if(count($books) > 0): ?>
                    <div class="books-container">
                        <?php foreach($books as $book): ?>
                            <div class="card book-card">
                                <?php if(isset($book['bestseller']) && $book['bestseller'] == 1): ?>
                                    <div class="book-badge bestseller-badge">BESTSELLER</div>
                                <?php endif; ?>
                                <?php if(isset($book['featured']) && $book['featured'] == 1): ?>
                                    <div class="book-badge featured-badge">FEATURED</div>
                                <?php endif; ?>
                                
                                <div class="book-image">
                                    <a href="book-detail.php?id=<?php echo $book['id']; ?>">
                                        <?php 
                                        if(!empty($book['cover_image'])) {
                                            // Check if the image path is just a filename or a full path
                                            $cover_image = $book['cover_image'];
                                            
                                            // If it's just a filename, prepend the books directory path
                                            if (!strpos($cover_image, '/') && !strpos($cover_image, '\\')) {
                                                $imageUrl = "../images/books/" . $cover_image;
                                            } else {
                                                // If it's a full path, keep it as is
                                                $imageUrl = $cover_image;
                                            }
                                            
                                            // Verify the file exists, if not use default
                                            if (!file_exists($imageUrl)) {
                                                $imageUrl = "../images/book-1.jpg";
                                            }
                                            
                                            echo '<img src="' . $imageUrl . '" alt="' . $book['title'] . '" class="card-img-top" onerror="this.onerror=null; this.src=\'../images/book-1.jpg\';">';
                                        } else {
                                            echo '<img src="../images/book-1.jpg" alt="' . $book['title'] . '" class="card-img-top">';
                                        }
                                        ?>
                                    </a>
                                </div>
                                <div class="book-details card-body">
                                    <h5 class="book-title card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                                    
                                    <p class="book-author card-text">By: <?php echo htmlspecialchars($book['author'] ?? 'Unknown Author'); ?></p>
                                    
                                    <div class="book-rating-review">
                                        <div class="rating">
                                            <i class="fa-solid fa-star"></i> <?php echo isset($book['rating']) ? $book['rating'] : '4.0'; ?>
                                        </div>
                                        <span><?php echo isset($book['reviews']) ? $book['reviews'] : '100'; ?> Reviews</span>
                                    </div>
                                    
                                    <?php 
                                    // Generate dynamic genre tags based on categories or book title
                                    $categories = [];
                                    
                                    if (!empty($book['categories'])) {
                                        $categoryNames = explode(', ', $book['categories']);
                                        foreach ($categoryNames as $catName) {
                                            if (!empty($catName)) {
                                                $categories[] = $catName;
                                            }
                                        }
                                    }
                                    
                                    // If no categories found, generate based on title
                                    if (empty($categories)) {
                                        $title = strtolower($book['title']);
                                        if (strpos($title, 'harry') !== false || strpos($title, 'potter') !== false) {
                                            $categories = ['fantasy', 'magic', 'adventure'];
                                        } elseif (strpos($title, 'percy') !== false || strpos($title, 'olympus') !== false) {
                                            $categories = ['mythology', 'adventure', 'young adult'];
                                        } elseif (strpos($title, 'giver') !== false) {
                                            $categories = ['dystopian', 'sci-fi', 'young adult'];
                                        } elseif (strpos($title, 'mockingbird') !== false) {
                                            $categories = ['classic', 'drama', 'historical'];
                                        } elseif (strpos($title, 'ruins') !== false || strpos($title, 'gorlan') !== false) {
                                            $categories = ['adventure', 'fantasy', 'action'];
                                        } elseif (strpos($title, 'red queen') !== false) {
                                            $categories = ['fantasy', 'romance', 'dystopian'];
                                        } else {
                                            // Default genres if no match
                                            $categories = ['fiction', 'bestseller'];
                                        }
                                    }
                                    ?>
                                    
                                    <div class="badge">
                                        <?php foreach(array_slice($categories, 0, 3) as $genre): ?>
                                            <span class="bg-light text-dark"><?php echo htmlspecialchars($genre); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="book-price">
                                        <?php if (!empty($book['price']) && $book['price'] > 0): ?>
                                            <strong>$<?php echo number_format($book['price'], 2); ?></strong>
                                        <?php else: ?>
                                            <strong>$29.99</strong>
                                        <?php endif; ?>
                                        
                                        <?php if(!empty($book['old_price']) && $book['old_price'] > 0): ?>
                                            <span class="book-old-price">$<?php echo number_format($book['old_price'], 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="book-stock">
                                        <?php if(isset($book['stock_quantity'])): ?>
                                            <?php if($book['stock_quantity'] > 10): ?>
                                                <span class="in-stock">In Stock</span>
                                            <?php elseif($book['stock_quantity'] > 0): ?>
                                                <span class="low-stock">Low Stock (<?php echo $book['stock_quantity']; ?>)</span>
                                            <?php else: ?>
                                                <span class="out-of-stock">Out of Stock</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="in-stock">In Stock</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <a href="book-detail.php?id=<?php echo $book['id']; ?>" class="preview-link mt-auto">
                                        <button class="preview-button btn">
                                            <i class="fa-solid fa-eye"></i> View Details
                                        </button>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Display Open Library API results -->
                <?php if(count($external_books) > 0): ?>
                    <div class="google-books-separator">
                        <div class="separator-line"></div>
                        <h3>Additional Books from Open Library</h3>
                        <div class="separator-line"></div>
                    </div>
                    <div class="books-container">
                        <?php foreach($external_books as $book): ?>
                            <div class="card book-card external-book">
                                <div class="api-badge">Open Library</div>
                                
                                <div class="book-image">
                                    <a href="book-detail.php?id=<?php echo $book['id']; ?>&api=1">
                                        <?php if(!empty($book['cover_img'])): ?>
                                            <img src="<?php echo $book['cover_img']; ?>" alt="<?php echo $book['title']; ?>" class="card-img-top">
                                        <?php else: ?>
                                            <img src="../images/book-loader.gif" alt="Book cover not available" class="card-img-top">
                                        <?php endif; ?>
                                    </a>
                                </div>
                                <div class="book-details card-body">
                                    <h5 class="book-title card-title"><?php echo $book['title']; ?></h5>
                                    
                                    <p class="book-author card-text">By: <?php echo $book['author']; ?></p>
                                    
                                    <div class="book-rating-review">
                                        <div class="rating">
                                            <i class="fa-solid fa-star"></i> <?php echo $book['rating']; ?>
                                        </div>
                                        <span><?php echo $book['reviews']; ?> Reviews</span>
                                    </div>
                                    
                                    <?php 
                                    // Generate badges from categories if available
                                    $categories = [];
                                    if(!empty($book['categories'])) {
                                        $categories = explode(',', $book['categories']);
                                    }
                                    // If no categories, generate from title
                                    if(empty($categories)) {
                                        $title = strtolower($book['title']);
                                        if (strpos($title, 'harry') !== false || strpos($title, 'potter') !== false) {
                                            $categories = ['fantasy', 'magic', 'adventure'];
                                        } elseif (strpos($title, 'percy') !== false || strpos($title, 'olympus') !== false) {
                                            $categories = ['mythology', 'adventure', 'young adult'];
                                        } else {
                                            $categories = ['fiction', 'literature'];
                                        }
                                    }
                                    ?>
                                    
                                    <div class="badge">
                                        <?php foreach(array_slice($categories, 0, 3) as $category): ?>
                                            <span class="bg-light text-dark"><?php echo $category; ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <?php if(!empty($book['publisher'])): ?>
                                        <p class="book-publisher card-text">Publisher: <?php echo $book['publisher']; ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($book['published_date'])): ?>
                                        <p class="book-date card-text">Published: <?php echo $book['published_date']; ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="book-price">
                                        <strong>$<?php echo number_format($book['price'], 2); ?></strong>
                                        <span class="book-old-price">$<?php echo number_format($book['original_price'], 2); ?></span>
                                    </div>
                                    
                                    <a href="book-detail.php?id=<?php echo $book['id']; ?>&api=1" class="preview-link mt-auto">
                                        <button class="preview-button btn">
                                            <i class="fa-solid fa-eye"></i> View Details
                                        </button>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-results">
                    <i class="fa-solid fa-book-open" style="font-size: 50px; color: #ddd; margin-bottom: 20px;"></i>
                    <h4>No books found matching your search criteria</h4>
                    <p>Try different keywords or browse our book categories</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <footer>
        <div class="container">
          <div class="logo-description">
            <div class="logo">
              <div class="img">
                <img src="../images/logo.png" alt="" />
              </div>
              <div class="title">
                <h4><a href="../index.php" style="text-decoration: none; color: inherit;">Fassila</a></h4>
                <small>Book Store Website</small>
              </div>
            </div>
            <div class="logo-body">
              <p>
                Lorem ipsum, dolor sit amet consectetur adipisicing elit. Magnam
                voluptates eius quasi reiciendis recusandae provident veritatis
                sequi, dolores architecto dolor possimus quos
              </p>
            </div>
            <div class="social-links">
              <h4>Follow Us</h4>
              <ul class="links">
                <li>
                  <a href=""><i class="fa-brands fa-facebook-f"></i></a>
                </li>
                <li>
                  <a href=""><i class="fa-brands fa-youtube"></i></a>
                </li>
                <li>
                  <a href=""><i class="fa-brands fa-twitter"></i></a>
                </li>
                <li>
                  <a href=""><i class="fa-brands fa-linkedin"></i></a>
                </li>
                <li>
                  <a href=""><i class="fa-brands fa-instagram"></i></a>
                </li>
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
              <li><a href="../index.php">About Us</a></li>
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
            <div class="map" style="margin-top: 1rem">
              <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d6310.594819201665!2d-122.42768319999999!3d37.73616639999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x808f7e60a337d5f5%3A0xfa0bb626904e5ab2!2z4KSV4KWJ4KSy4KWH4KScIOCkueCkv-Cksiwg4KS44KS-4KSoIOCkq-CljeCksOCkvuCkguCkuOCkv-CkuOCljeCkleCliywg4KSV4KWI4KSy4KWA4KSr4KWL4KSw4KWN4KSo4KS_4KSv4KS-LCDgpK_gpYLgpKjgpL7gpIfgpJ_gpYfgpKEg4KS44KWN4KSf4KWH4KSf4KWN4oCN4KS4!5e0!3m2!1shi!2sin!4v1686917463994!5m2!1shi!2sin"
                height="70"
                style="width: 100%; border: none; border-radius: 5px"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
              ></iframe>
            </div>
            <ul>
              <li>
                <a href=""
                  ><i class="fa-solid fa-location-dot"></i>832 Thompson Drive,San
                  Fransisco CA 94 107,United States</a
                >
              </li>
              <li>
                <a href=""><i class="fa-solid fa-phone"></i>+12 1345678991</a>
              </li>
              <li>
                <a href=""
                  ><i class="fa-solid fa-envelope"></i>support@Fassila.id</a
                >
              </li>
            </ul>
          </div>
        </div>
      </footer>
    
    <button class="back-to-top"><i class="fa-solid fa-chevron-up"></i></button>
    <script src="../js/back-to-top.js"></script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
  // Get search form
  const searchForm = document.querySelector('.search-box form');
  
  // Add event listener for searching
  searchForm.addEventListener('submit', function(e) {
    const searchButton = this.querySelector('.search-icon');
    searchButton.closest('.search-field').classList.add('loading');
    
    // No need to prevent default - we want the form to submit to search.php
    
    // Store search in localStorage for analytics (optional)
    const searchQuery = document.querySelector('input[name="query"]').value;
    localStorage.setItem('lastSearch', searchQuery);
    
    // Track popular searches (optional)
    let popularSearches = JSON.parse(localStorage.getItem('popularSearches') || '[]');
    popularSearches.push({
      query: searchQuery,
      timestamp: new Date().toISOString()
    });
    
    // Keep only last 10 searches
    if (popularSearches.length > 10) {
      popularSearches = popularSearches.slice(-10);
    }
    
    localStorage.setItem('popularSearches', JSON.stringify(popularSearches));
  });
  
  // Show recent searches (optional enhancement)
  const searchInput = document.querySelector('input[name="query"]');
  searchInput.addEventListener('focus', function() {
    const lastSearch = localStorage.getItem('lastSearch');
    if (lastSearch) {
      // Create a suggestion element if you want
      // This is optional enhancement
    }
  });
  
  // If there's a search query in the URL, populate the search field
  const urlParams = new URLSearchParams(window.location.search);
  const queryParam = urlParams.get('query');
  if (queryParam) {
    searchInput.value = queryParam;
  }
});
</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>