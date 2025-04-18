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
$apiKey = 'YOUR_GOOGLE_API_KEY'; // Replace with your Google Books API key

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
    
    // PART 1: Search local database
    $sql = "SELECT * FROM books 
            WHERE 
                title LIKE ? OR 
                description LIKE ? OR 
                isbn LIKE ? OR
                publisher LIKE ?
            ORDER BY title ASC";
    
    // Prepare and execute the statement
    $stmt = $conn->prepare($sql);
    $searchTerm = "%" . $searchQuery . "%";
    $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
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
                    
                    $external_books[] = [
                        'title' => $doc['title'] ?? 'Unknown Title',
                        'authors' => isset($doc['author_name']) ? implode(', ', $doc['author_name']) : 'Unknown Author',
                        'description' => '',  // Open Library search doesn't include descriptions
                        'publisher' => isset($doc['publisher']) && !empty($doc['publisher']) ? $doc['publisher'][0] : '',
                        'published_date' => isset($doc['first_publish_year']) ? $doc['first_publish_year'] : '',
                        'isbn' => $isbn,
                        'page_count' => $doc['number_of_pages_median'] ?? 0,
                        'categories' => isset($doc['subject']) ? implode(', ', array_slice($doc['subject'], 0, 3)) : '',
                        'thumbnail' => $coverUrl,
                        'preview_link' => "https://openlibrary.org" . ($doc['key'] ?? ''),
                        'is_external' => true
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
    <!-- FIX PATHS HERE -->
    <link rel="stylesheet" href="../css/style.css" />
    <link rel="stylesheet" href="book-filter.css" />
    <!-- Rest of your head content -->
</head>
<body>
    <!-- FIX PATHS HERE -->
    <?php //include '../includes/header.php'; ?>
    
    <div class="breadcrumb-container">
        <ul class="breadcrumb">
            <li><a href="../index.php">Home</a></li>
            <li><a href="#">Search Results</a></li>
        </ul>
    </div>
    
    <section class="search-results">
        <div class="search-header">
            <h3>Search Results</h3>
            <div class="search-message"><?php echo $message; ?></div>
            
            <!-- New search form -->
            <div class="search-box" style="max-width: 500px; margin-bottom: 20px;">
                <form action="search.php" method="GET">
                    <div class="search-field">
                        <input
                            type="text"
                            name="query"
                            placeholder="Search books by title, ISBN, etc."
                            value="<?php echo $searchQuery; ?>"
                            required
                        />
                        <button class="search-icon" type="submit">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if($totalResults > 0): ?>
            <div class="books-container">
                <?php foreach($books as $book): ?>
                    <div class="book-card">
                        <!-- FIX COLUMN REFERENCES -->
                        <?php if(isset($book['bestseller']) && $book['bestseller'] == 1): ?>
                            <div class="book-badge bestseller-badge">BESTSELLER</div>
                        <?php endif; ?>
                        <?php if(isset($book['featured']) && $book['featured'] == 1): ?>
                            <div class="book-badge featured-badge">FEATURED</div>
                        <?php endif; ?>
                        
                        <div class="book-image">
                            <!-- FIX IMAGE PATH -->
                            <?php if(!empty($book['cover_image']) && file_exists('../images/books/' . $book['cover_image'])): ?>
                                <img src="../images/books/<?php echo $book['cover_image']; ?>" alt="<?php echo $book['title']; ?>">
                            <?php else: ?>
                                <img src="../images/books/default-book.jpg" alt="Book cover not available">
                            <?php endif; ?>
                        </div>
                        <div class="book-details">
                            <div class="book-title"><?php echo $book['title']; ?></div>
                            
                            <div class="book-price">
                                $<?php echo number_format($book['price'], 2); ?>
                                <?php if(!empty($book['old_price']) && $book['old_price'] > 0): ?>
                                    <span class="book-old-price">$<?php echo number_format($book['old_price'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if(!empty($book['language'])): ?>
                                <div class="book-language">Language: <?php echo $book['language']; ?></div>
                            <?php endif; ?>
                            
                            <?php if(!empty($book['publisher'])): ?>
                                <div class="book-publisher">Publisher: <?php echo $book['publisher']; ?></div>
                            <?php endif; ?>
                            
                            <div class="book-stock">
                                <!-- FIX COLUMN NAME -->
                                <?php if($book['stock_quantity'] > 10): ?>
                                    <span class="in-stock">In Stock</span>
                                <?php elseif($book['stock_quantity'] > 0): ?>
                                    <span class="low-stock">Low Stock (<?php echo $book['stock_quantity']; ?>)</span>
                                <?php else: ?>
                                    <span class="out-of-stock">Out of Stock</span>
                                <?php endif; ?>
                            </div>
                            
                            <button class="add-to-cart">
                                <i class="fa-solid fa-cart-plus"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Display Open Library API results -->
            <?php if(count($external_books) > 0): ?>
                <div class="google-books-separator">
                    <h3>Additional Books from Open Library</h3>
                </div>
                <div class="books-container">
                    <?php foreach($external_books as $book): ?>
                        <div class="book-card external-book">
                            <div class="api-badge">Open Library</div>
                            
                            <div class="book-image">
                                <?php if(!empty($book['thumbnail'])): ?>
                                    <img src="<?php echo $book['thumbnail']; ?>" alt="<?php echo $book['title']; ?>">
                                <?php else: ?>
                                    <img src="../images/books/default-book.jpg" alt="Book cover not available">
                                <?php endif; ?>
                            </div>
                            <div class="book-details">
                                <div class="book-title"><?php echo $book['title']; ?></div>
                                
                                <div class="book-author">By: <?php echo $book['authors']; ?></div>
                                
                                <?php if(!empty($book['publisher'])): ?>
                                    <div class="book-publisher">Publisher: <?php echo $book['publisher']; ?></div>
                                <?php endif; ?>
                                
                                <?php if(!empty($book['published_date'])): ?>
                                    <div class="book-date">Published: <?php echo $book['published_date']; ?></div>
                                <?php endif; ?>
                                
                                <?php if(!empty($book['categories'])): ?>
                                    <div class="book-categories">Categories: <?php echo $book['categories']; ?></div>
                                <?php endif; ?>
                                
                                <a href="<?php echo $book['preview_link']; ?>" target="_blank" class="preview-link">
                                    <button class="preview-button">
                                        <i class="fa-solid fa-eye"></i> View on Open Library
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
    </section>
    
    <!-- FIX PATH HERE -->
    <?php //include '../includes/footer.php'; ?>
    
    <button class="back-to-top"><i class="fa-solid fa-chevron-up"></i></button>
    <!-- FIX PATH HERE -->
    <script src="../js/back-to-top.js"></script>
</body>
</html>

<style>
/* Optional - update badge color to match Open Library branding */
.api-badge {
    background-color: #0074D9; /* Open Library blue color */
}
</style>