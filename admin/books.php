<?php
require_once 'includes/header.php';

// Define variables
$books = array();
$totalBooks = 0;
$limit = 10; // Records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$bookId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get all categories for dropdown
$categories = array();
$categoriesQuery = $conn->query("SELECT * FROM categories");
if ($categoriesQuery) {
    while ($category = $categoriesQuery->fetch_assoc()) {
        $categories[$category['id']] = $category['name'];
    }
}

// Handle book actions
if ($action && $bookId) {
    switch ($action) {
        case 'delete':
            // Get the book's cover image before deleting
            $getImageStmt = $conn->prepare("SELECT cover_image FROM books WHERE id = ?");
            $getImageStmt->bind_param("i", $bookId);
            $getImageStmt->execute();
            $bookImage = $getImageStmt->get_result()->fetch_assoc();
            $getImageStmt->close();
            
            // Delete book
            $deleteStmt = $conn->prepare("DELETE FROM books WHERE id = ?");
            $deleteStmt->bind_param("i", $bookId);
            
            if ($deleteStmt->execute()) {
                // Delete the book's cover image if it exists and is not a default image
                if (!empty($bookImage['cover_image']) && 
                    $bookImage['cover_image'] !== 'book-1.jpg' && 
                    $bookImage['cover_image'] !== 'book-loader.gif') {
                    
                    $image_paths = [
                        "../images/books/" . $bookImage['cover_image'],
                        "../images/" . $bookImage['cover_image'],
                        $bookImage['cover_image']
                    ];
                    
                    foreach($image_paths as $path) {
                        if(file_exists($path)) {
                            @unlink($path);
                            break;
                        }
                    }
                }
                $_SESSION['success_message'] = "Book deleted successfully!";
            } else {
                $_SESSION['error_message'] = "Error deleting book: " . $conn->error;
            }
            
            $deleteStmt->close();
            
            // Set a redirect flag and URL instead of using header() directly
            $redirect_flag = true;
            $redirect_url = "books.php";
            break;
            
        case 'featured':
            // Check which column exists before toggling
            $checkStmt = $conn->prepare("SHOW COLUMNS FROM books LIKE 'featured'");
            $checkStmt->execute();
            $columnExists = $checkStmt->get_result()->num_rows > 0;
            $checkStmt->close();
            
            if ($columnExists) {
                // Toggle featured status without affecting cover image
                $toggleStmt = $conn->prepare("UPDATE books SET featured = NOT featured WHERE id = ?");
                $toggleStmt->bind_param("i", $bookId);
            } else {
                // Try with is_featured instead
                $toggleStmt = $conn->prepare("UPDATE books SET is_featured = NOT is_featured WHERE id = ?");
                $toggleStmt->bind_param("i", $bookId);
            }
            
            if ($toggleStmt->execute()) {
                $_SESSION['success_message'] = "Book featured status updated successfully!";
            } else {
                $_SESSION['error_message'] = "Error updating book: " . $conn->error;
            }
            
            $toggleStmt->close();
            
            // Set a redirect flag and URL instead of using header() directly
            $redirect_flag = true;
            $redirect_url = "books.php";
            break;
    }
}

// Add new book
if (isset($_POST['add_book'])) {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $discount_price = isset($_POST['discount_price']) ? $_POST['discount_price'] : $price;
    $stock = $_POST['stock'];
    $category_id = $_POST['category_id'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    // Handle file upload for book cover
    if(isset($_FILES['cover_img']) && $_FILES['cover_img']['error'] == 0) {
        $upload_dir = "../images/books/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['cover_img']['name']);
        $target_file = $upload_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        if(in_array($file_type, ['jpg', 'jpeg', 'png', 'gif'])) {
            if(move_uploaded_file($_FILES['cover_img']['tmp_name'], $target_file)) {
                $cover_img = $file_name; // Store just the filename
            } else {
                $cover_img = "book-1.jpg";
            }
        } else {
            $cover_img = "book-1.jpg";
        }
    } else {
        $cover_img = "book-1.jpg";
    }
    
    // Insert new book
    $insertStmt = $conn->prepare("INSERT INTO books (title, description, price, old_price, cover_image, stock_quantity, featured, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $insertStmt->bind_param("ssddsis", $title, $description, $price, $discount_price, $cover_img, $stock, $is_featured);
    
    if ($insertStmt->execute()) {
        $book_id = $conn->insert_id;
        
        // Handle category association
        if (!empty($category_id)) {
            $categoryLinkStmt = $conn->prepare("INSERT INTO book_categories (book_id, category_id) VALUES (?, ?)");
            $categoryLinkStmt->bind_param("ii", $book_id, $category_id);
            $categoryLinkStmt->execute();
            $categoryLinkStmt->close();
        }
        
        // If we have an author, handle author association
        if (!empty($author)) {
            // First check if author exists
            $authorCheckStmt = $conn->prepare("SELECT id FROM authors WHERE name = ?");
            $authorCheckStmt->bind_param("s", $author);
            $authorCheckStmt->execute();
            $authorResult = $authorCheckStmt->get_result();
            
            if ($authorResult->num_rows > 0) {
                // Author exists
                $author_id = $authorResult->fetch_assoc()['id'];
            } else {
                // Author doesn't exist, create new
                $authorInsertStmt = $conn->prepare("INSERT INTO authors (name) VALUES (?)");
                $authorInsertStmt->bind_param("s", $author);
                $authorInsertStmt->execute();
                $author_id = $conn->insert_id;
                $authorInsertStmt->close();
            }
            
            // Now link book and author
            $linkStmt = $conn->prepare("INSERT INTO book_authors (book_id, author_id) VALUES (?, ?)");
            $linkStmt->bind_param("ii", $book_id, $author_id);
            $linkStmt->execute();
            $linkStmt->close();
            $authorCheckStmt->close();
        }
        
        $_SESSION['success_message'] = "Book added successfully!";
    } else {
        $_SESSION['error_message'] = "Error adding book: " . $conn->error;
    }
    
    $insertStmt->close();
    
    // Set a redirect flag and URL instead of using header() directly
    $redirect_flag = true;
    $redirect_url = "books.php";
}

// Edit book
if (isset($_POST['edit_book'])) {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $discount_price = isset($_POST['discount_price']) ? $_POST['discount_price'] : $price;
    $stock = $_POST['stock'];
    $category_id = $_POST['category_id'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    // Get current book data to ensure we have the correct cover image
    $currentBookStmt = $conn->prepare("SELECT cover_image FROM books WHERE id = ?");
    $currentBookStmt->bind_param("i", $id);
    $currentBookStmt->execute();
    $currentBook = $currentBookStmt->get_result()->fetch_assoc();
    $currentBookStmt->close();
    
    // Handle file upload for book cover
    $cover_img = $currentBook['cover_image']; // Keep the current cover by default
    
    // Only process new image if one was actually uploaded
    if(isset($_FILES['cover_img']) && $_FILES['cover_img']['error'] == 0 && $_FILES['cover_img']['size'] > 0) {
        $upload_dir = "../images/books/";
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['cover_img']['name']);
        $target_file = $upload_dir . $file_name;
        
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
        
        if(in_array($file_type, $allowed_types)) {
            if(move_uploaded_file($_FILES['cover_img']['tmp_name'], $target_file)) {
                // Only update cover_img if new image was successfully uploaded
                $cover_img = $file_name;
                
                // Delete old image if it exists and is not a default image
                if (!empty($currentBook['cover_image']) && 
                    $currentBook['cover_image'] !== 'book-1.jpg' && 
                    $currentBook['cover_image'] !== 'book-loader.gif') {
                    
                    // Check all possible locations for the old image
                    $old_image_paths = [
                        "../images/books/" . $currentBook['cover_image'],
                        "../images/" . $currentBook['cover_image'],
                        $currentBook['cover_image']
                    ];
                    
                    foreach($old_image_paths as $old_path) {
                        if(file_exists($old_path)) {
                            @unlink($old_path);
                            break;
                        }
                    }
                }
            } else {
                $_SESSION['error_message'] = "Error uploading new image. Keeping current image.";
            }
        } else {
            $_SESSION['error_message'] = "Invalid file type. Allowed types: jpg, jpeg, png, gif";
        }
    }
    
    // Update book with proper image path
    $updateStmt = $conn->prepare("UPDATE books SET title = ?, description = ?, price = ?, old_price = ?, stock_quantity = ?, cover_image = ?, featured = ? WHERE id = ?");
    $updateStmt->bind_param("ssddsisi", $title, $description, $price, $discount_price, $stock, $cover_img, $is_featured, $id);
    
    if ($updateStmt->execute()) {
        // Handle category association for existing book
        if (!empty($category_id)) {
            // First remove existing category associations
            $deleteCategoryStmt = $conn->prepare("DELETE FROM book_categories WHERE book_id = ?");
            $deleteCategoryStmt->bind_param("i", $id);
            $deleteCategoryStmt->execute();
            $deleteCategoryStmt->close();
            
            // Add the new category association
            $categoryLinkStmt = $conn->prepare("INSERT INTO book_categories (book_id, category_id) VALUES (?, ?)");
            $categoryLinkStmt->bind_param("ii", $id, $category_id);
            $categoryLinkStmt->execute();
            $categoryLinkStmt->close();
        }
        
        // Handle author association for existing book
        if (!empty($author)) {
            // First check if author exists
            $authorCheckStmt = $conn->prepare("SELECT id FROM authors WHERE name = ?");
            $authorCheckStmt->bind_param("s", $author);
            $authorCheckStmt->execute();
            $authorResult = $authorCheckStmt->get_result();
            
            if ($authorResult->num_rows > 0) {
                // Author exists
                $author_id = $authorResult->fetch_assoc()['id'];
            } else {
                // Author doesn't exist, create new
                $authorInsertStmt = $conn->prepare("INSERT INTO authors (name) VALUES (?)");
                $authorInsertStmt->bind_param("s", $author);
                $authorInsertStmt->execute();
                $author_id = $conn->insert_id;
                $authorInsertStmt->close();
            }
            
            // Remove existing author associations for this book
            $deleteAuthorsStmt = $conn->prepare("DELETE FROM book_authors WHERE book_id = ?");
            $deleteAuthorsStmt->bind_param("i", $id);
            $deleteAuthorsStmt->execute();
            $deleteAuthorsStmt->close();
            
            // Add the new author association
            $linkStmt = $conn->prepare("INSERT INTO book_authors (book_id, author_id) VALUES (?, ?)");
            $linkStmt->bind_param("ii", $id, $author_id);
            $linkStmt->execute();
            $linkStmt->close();
            $authorCheckStmt->close();
        }
        
        $_SESSION['success_message'] = "Book updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating book: " . $conn->error;
    }
    
    $updateStmt->close();
    
    // Set a redirect flag and URL instead of using header() directly
    $redirect_flag = true;
    $redirect_url = "books.php";
}

// Search functionality
$whereClause = "";
if (!empty($search)) {
    $whereClause = "WHERE title LIKE ? OR authors.name LIKE ?";
    $searchParam = "%$search%";
}

// Count total books for pagination
if (empty($whereClause)) {
    $countQuery = $conn->query("SELECT COUNT(*) as count FROM books");
    $totalBooks = $countQuery->fetch_assoc()['count'];
} else {
    $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM books 
                                 LEFT JOIN book_authors ON books.id = book_authors.book_id 
                                 LEFT JOIN authors ON book_authors.author_id = authors.id 
                                 $whereClause");
    $countStmt->bind_param("ss", $searchParam, $searchParam);
    $countStmt->execute();
    $result = $countStmt->get_result();
    $totalBooks = $result->fetch_assoc()['count'];
    $countStmt->close();
}

// Get books with pagination
if (empty($whereClause)) {
    // First, check which timestamp column exists
    $checkColumnsStmt = $conn->query("SHOW COLUMNS FROM books");
    $existingColumns = [];
    while ($column = $checkColumnsStmt->fetch_assoc()) {
        $existingColumns[] = $column['Field'];
    }
    
    // Determine which timestamp column to use for ordering
    $orderColumn = "books.id DESC"; // Default fallback order
    if (in_array('created_at', $existingColumns)) {
        $orderColumn = "books.created_at DESC";
    } elseif (in_array('added_date', $existingColumns)) {
        $orderColumn = "books.added_date DESC"; 
    } elseif (in_array('updated_at', $existingColumns)) {
        $orderColumn = "books.updated_at DESC";
    }
    
    // Query with left join to get author info
    $query = "SELECT books.*, authors.name as author_name 
              FROM books 
              LEFT JOIN book_authors ON books.id = book_authors.book_id 
              LEFT JOIN authors ON book_authors.author_id = authors.id 
              ORDER BY $orderColumn LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $limit, $offset);
} else {
    // Same check for search query
    $checkColumnsStmt = $conn->query("SHOW COLUMNS FROM books");
    $existingColumns = [];
    while ($column = $checkColumnsStmt->fetch_assoc()) {
        $existingColumns[] = $column['Field'];
    }
    
    // Determine which timestamp column to use for ordering
    $orderColumn = "books.id DESC"; // Default fallback order
    if (in_array('created_at', $existingColumns)) {
        $orderColumn = "books.created_at DESC";
    } elseif (in_array('added_date', $existingColumns)) {
        $orderColumn = "books.added_date DESC"; 
    } elseif (in_array('updated_at', $existingColumns)) {
        $orderColumn = "books.updated_at DESC";
    }
    
    $query = "SELECT books.*, authors.name as author_name 
              FROM books 
              LEFT JOIN book_authors ON books.id = book_authors.book_id 
              LEFT JOIN authors ON book_authors.author_id = authors.id 
              $whereClause ORDER BY $orderColumn LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssii", $searchParam, $searchParam, $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

while ($book = $result->fetch_assoc()) {
    // Get book category
    $categoryQuery = $conn->prepare("SELECT category_id FROM book_categories WHERE book_id = ? LIMIT 1");
    $categoryQuery->bind_param("i", $book['id']);
    $categoryQuery->execute();
    $categoryResult = $categoryQuery->get_result();
    
    if ($categoryResult->num_rows > 0) {
        $book['category_id'] = $categoryResult->fetch_assoc()['category_id'];
    } else {
        $book['category_id'] = "";
    }
    $categoryQuery->close();
    
    $books[] = $book;
}

$stmt->close();

// Calculate total pages
$totalPages = ceil($totalBooks / $limit);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>Manage Books</h4>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addBookModal">
        <i class="fas fa-plus"></i> Add New Book
    </button>
</div>

<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-book mr-1"></i> All Books
                <span class="badge badge-secondary ml-2"><?php echo $totalBooks; ?></span>
            </div>
            <form class="form-inline" method="GET" action="">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search books..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="books.php" class="btn btn-outline-danger">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 60px;">Cover</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($books) > 0): ?>
                        <?php foreach ($books as $book): ?>
                        <tr>
                            <td>
                                <?php
                                $coverImage = isset($book['cover_image']) ? $book['cover_image'] : 'book-loader.gif';
                                
                                // Check all possible image locations
                                $imagePaths = [
                                    '../images/books/' . $coverImage,
                                    '../images/' . $coverImage,
                                    $coverImage
                                ];
                                
                                $imageUrl = '../images/book-loader.gif'; // Default image
                                
                                foreach($imagePaths as $path) {
                                    if(file_exists($path)) {
                                        $imageUrl = $path;
                                        break;
                                    }
                                }
                                ?>
                                <img src="<?php echo $imageUrl; ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                            </td>
                            <td>
                                <?php echo $book['title']; ?>
                                <?php if (isset($book['is_featured']) && $book['is_featured']): ?>
                                    <span class="badge badge-success ml-1">Featured</span>
                                <?php elseif (isset($book['featured']) && $book['featured']): ?>
                                    <span class="badge badge-success ml-1">Featured</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo isset($book['author_name']) ? $book['author_name'] : 'Unknown'; ?></td>
                            <td>
                                <?php 
                                    if (isset($book['category_id']) && isset($categories[$book['category_id']])) {
                                        echo $categories[$book['category_id']];
                                    } else {
                                        echo 'Uncategorized';
                                    }
                                ?>
                            </td>
                            <td>
                                <?php 
                                $price = isset($book['price']) ? $book['price'] : 0.00;
                                $discount_price = isset($book['discount_price']) ? $book['discount_price'] : $price;
                                if ($discount_price < $price): 
                                ?>
                                    <span class="text-danger">$<?php echo number_format($discount_price, 2); ?></span>
                                    <small class="text-muted">$<?php echo number_format($price, 2); ?>
</small>
                                <?php else: ?>
                                    <span>$<?php echo number_format($price, 2); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $stock = isset($book['stock']) ? $book['stock'] : (isset($book['stock_quantity']) ? $book['stock_quantity'] : 0);
                                if ($stock <= 5): 
                                ?>
                                    <span class="text-danger"><?php echo $stock; ?></span>
                                <?php else: ?>
                                    <?php echo $stock; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $stock = isset($book['stock']) ? $book['stock'] : (isset($book['stock_quantity']) ? $book['stock_quantity'] : 0);
                                if ($stock > 0): 
                                ?>
                                    <span class="badge badge-success">In Stock</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Out of Stock</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-primary" title="Edit Book" 
                                            data-toggle="modal" data-target="#editBookModal<?php echo $book['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?action=featured&id=<?php echo $book['id']; ?>" class="btn btn-success" title="Toggle Featured Status">
                                        <i class="fas fa-star"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger" title="Delete Book" 
                                            onclick="confirmDelete(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars(addslashes($book['title'])); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-3">No books found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="card-footer">
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mb-0">
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo (!empty($search)) ? '&search='.$search : ''; ?>">Previous</a>
                </li>
                
                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo (!empty($search)) ? '&search='.$search : ''; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo (!empty($search)) ? '&search='.$search : ''; ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- Add Book Modal -->
<div class="modal fade" id="addBookModal" tabindex="-1" role="dialog" aria-labelledby="addBookModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addBookModalLabel">Add New Book</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="title">Book Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="form-group">
                                <label for="author">Author</label>
                                <input type="text" class="form-control" id="author" name="author" required>
                            </div>
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="category_id">Category</label>
                                <select class="form-control" id="category_id" name="category_id" required>
                                    <option value="">Select a category</option>
                                    <?php foreach ($categories as $id => $name): ?>
                                        <option value="<?php echo $id; ?>"><?php echo $name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="price">Price ($)</label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                            </div>
                            <div class="form-group">
                                <label for="discount_price">Discount Price ($)</label>
                                <input type="number" class="form-control" id="discount_price" name="discount_price" step="0.01" min="0">
                                <small class="form-text text-muted">Leave empty to use regular price</small>
                            </div>
                            <div class="form-group">
                                <label for="stock">Stock</label>
                                <input type="number" class="form-control" id="stock" name="stock" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="cover_img">Book Cover</label>
                        <div class="custom-file-upload">
                            <input type="file" class="form-control-file" id="cover_img" name="cover_img" onchange="previewImage(this, 'imagePreview')">
                            <small class="form-text text-muted">Recommended size: 600x900 pixels</small>
                            <div id="imagePreview" class="mt-2" style="display: none;">
                                <img src="" alt="Image Preview" style="max-height: 150px; max-width: 100%;" class="img-thumbnail">
                            </div>
                        </div>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured">
                        <label class="form-check-label" for="is_featured">Feature this book on homepage</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_book" class="btn btn-primary">Add Book</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Book Modal -->
<?php foreach ($books as $book): ?>
<div class="modal fade" id="editBookModal<?php echo $book['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editBookModalLabel<?php echo $book['id']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editBookModalLabel<?php echo $book['id']; ?>">Edit Book</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $book['id']; ?>">
                <input type="hidden" name="current_cover" value="<?php echo htmlspecialchars($book['cover_image']); ?>">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="title<?php echo $book['id']; ?>">Book Title</label>
                                <input type="text" class="form-control" id="title<?php echo $book['id']; ?>" name="title" required value="<?php echo htmlspecialchars($book['title']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="author<?php echo $book['id']; ?>">Author</label>
                                <input type="text" class="form-control" id="author<?php echo $book['id']; ?>" name="author" required value="<?php echo htmlspecialchars($book['author_name']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="description<?php echo $book['id']; ?>">Description</label>
                                <textarea class="form-control" id="description<?php echo $book['id']; ?>" name="description" rows="3"><?php echo htmlspecialchars($book['description']); ?></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="category_id<?php echo $book['id']; ?>">Category</label>
                                <select class="form-control" id="category_id<?php echo $book['id']; ?>" name="category_id" required>
                                    <option value="">Select a category</option>
                                    <?php foreach ($categories as $id => $name): ?>
                                        <option value="<?php echo $id; ?>" <?php echo ($book['category_id'] == $id) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="price<?php echo $book['id']; ?>">Price ($)</label>
                                <input type="number" class="form-control" id="price<?php echo $book['id']; ?>" name="price" step="0.01" min="0" required value="<?php echo $book['price']; ?>">
                            </div>
                            <div class="form-group">
                                <label for="discount_price<?php echo $book['id']; ?>">Discount Price ($)</label>
                                <input type="number" class="form-control" id="discount_price<?php echo $book['id']; ?>" name="discount_price" step="0.01" min="0" value="<?php echo isset($book['old_price']) ? $book['old_price'] : $book['price']; ?>">
                                <small class="form-text text-muted">Leave empty to use regular price</small>
                            </div>
                            <div class="form-group">
                                <label for="stock<?php echo $book['id']; ?>">Stock</label>
                                <input type="number" class="form-control" id="stock<?php echo $book['id']; ?>" name="stock" min="0" required value="<?php echo isset($book['stock']) ? $book['stock'] : (isset($book['stock_quantity']) ? $book['stock_quantity'] : 0); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_cover_img<?php echo $book['id']; ?>">Book Cover</label>
                        <div class="mb-2">
                            <?php
                            $cover_path = isset($book['cover_image']) ? $book['cover_image'] : 'book-loader.gif';
                            
                            // Check all possible image paths
                            $possible_paths = [
                                '../images/books/' . $cover_path,
                                '../images/' . $cover_path,
                                $cover_path
                            ];
                            
                            $img_path = '../images/book-loader.gif'; // Default fallback
                            foreach ($possible_paths as $path) {
                                if (file_exists($path)) {
                                    $img_path = $path;
                                    break;
                                }
                            }
                            ?>
                            <img src="<?php echo $img_path; ?>" alt="Current Cover" style="max-height: 150px; max-width: 100%;" class="img-thumbnail" id="currentCoverImage<?php echo $book['id']; ?>">
                        </div>
                        <div class="custom-file-upload">
                            <input type="file" class="form-control-file" id="edit_cover_img<?php echo $book['id']; ?>" name="cover_img" accept="image/jpeg,image/png,image/gif" onchange="previewImage(this, 'editImagePreview<?php echo $book['id']; ?>')">
                            <small class="form-text text-muted">Leave empty to keep current cover</small>
                            <div id="editImagePreview<?php echo $book['id']; ?>" class="mt-2" style="display: none;">
                                <img src="" alt="New Image Preview" style="max-height: 150px; max-width: 100%;" class="img-thumbnail">
                            </div>
                        </div>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="is_featured<?php echo $book['id']; ?>" name="is_featured" 
                               <?php echo (isset($book['featured']) && $book['featured']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_featured<?php echo $book['id']; ?>">Feature this book on homepage</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_book" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Common JavaScript functions -->
<script>
function confirmDelete(bookId, bookTitle) {
    if (confirm('Are you sure you want to delete book "' + bookTitle + '"? This action cannot be undone.')) {
        window.location.href = 'books.php?action=delete&id=' + bookId;
    }
}

function previewImage(input, previewId) {
    const previewContainer = document.getElementById(previewId);
    const previewImage = previewContainer.querySelector('img');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            previewContainer.style.display = 'block';
            
            // Update the current image preview as well
            const modalId = input.closest('.modal').id;
            const bookId = modalId.replace('editBookModal', '');
            const currentImage = document.getElementById('currentCoverImage' + bookId);
            if (currentImage) {
                currentImage.src = e.target.result;
            }
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        previewContainer.style.display = 'none';
    }
}
</script>

<!-- Handle redirects via JavaScript instead of PHP headers -->
<?php if(isset($redirect_flag) && $redirect_flag): ?>
<script>
    window.location.href = "<?php echo $redirect_url; ?>";
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>

<!-- Initialize modals after footer (which contains jQuery) -->
<script>
$(document).ready(function() {
    // Initialize any modals that need to be shown
    if($('#editBookModal').length) {
        $('#editBookModal').modal('show');
    }
});
</script>