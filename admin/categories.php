<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../connection.php';

// Add new category
if (isset($_POST['add_category'])) {
    $categoryName = $_POST['category_name'];
    $description = $_POST['description'];
    
    // Check if category already exists
    $checkStmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
    $checkStmt->bind_param("s", $categoryName);
    $checkStmt->execute();
    $checkStmt->store_result();
    
    if ($checkStmt->num_rows > 0) {
        $_SESSION['error_message'] = "Category already exists!";
    } else {
        // Insert new category
        $insertStmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $insertStmt->bind_param("ss", $categoryName, $description);
        
        if ($insertStmt->execute()) {
            $_SESSION['success_message'] = "Category added successfully!";
        } else {
            $_SESSION['error_message'] = "Error adding category: " . $conn->error;
        }
        
        $insertStmt->close();
    }
    
    $checkStmt->close();
    header("Location: categories.php");
    exit();
}

// Edit category
if (isset($_POST['edit_category'])) {
    $id = $_POST['id'];
    $categoryName = $_POST['category_name'];
    $description = $_POST['description'];
    
    // Check if another category with the same name exists
    $checkStmt = $conn->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
    $checkStmt->bind_param("si", $categoryName, $id);
    $checkStmt->execute();
    $checkStmt->store_result();
    
    if ($checkStmt->num_rows > 0) {
        $_SESSION['error_message'] = "Another category with this name already exists!";
    } else {
        // Update category
        $updateStmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
        $updateStmt->bind_param("ssi", $categoryName, $description, $id);
        
        if ($updateStmt->execute()) {
            $_SESSION['success_message'] = "Category updated successfully!";
        } else {
            $_SESSION['error_message'] = "Error updating category: " . $conn->error;
        }
        
        $updateStmt->close();
    }
    
    $checkStmt->close();
    header("Location: categories.php");
    exit();
}

// Handle category actions
$action = isset($_GET['action']) ? $_GET['action'] : '';
$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action && $categoryId) {
    switch ($action) {
        case 'delete':
            // Check if category has books assigned using the book_categories junction table
            $checkStmt = $conn->prepare("SELECT COUNT(*) as book_count FROM book_categories WHERE category_id = ?");
            $checkStmt->bind_param("i", $categoryId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $bookCount = $result->fetch_assoc()['book_count'];
            $checkStmt->close();
            
            if ($bookCount > 0) {
                $_SESSION['error_message'] = "Cannot delete category: It has $bookCount book(s) assigned to it.";
            } else {
                // Delete category
                $deleteStmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
                $deleteStmt->bind_param("i", $categoryId);
                
                if ($deleteStmt->execute()) {
                    $_SESSION['success_message'] = "Category deleted successfully!";
                } else {
                    $_SESSION['error_message'] = "Error deleting category: " . $conn->error;
                }
                
                $deleteStmt->close();
            }
            
            header("Location: categories.php");
            exit();
    }
}

require_once 'includes/header.php';

// Define variables
$categories = array();
$totalCategories = 0;
$limit = 10; // Records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Search functionality
$whereClause = "";
if (!empty($search)) {
    $whereClause = "WHERE name LIKE ? OR description LIKE ?";
    $searchParam = "%$search%";
}

// Count total categories for pagination
if (empty($whereClause)) {
    $countQuery = $conn->query("SELECT COUNT(*) as count FROM categories");
    $totalCategories = $countQuery->fetch_assoc()['count'];
} else {
    $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM categories $whereClause");
    $countStmt->bind_param("ss", $searchParam, $searchParam);
    $countStmt->execute();
    $result = $countStmt->get_result();
    $totalCategories = $result->fetch_assoc()['count'];
    $countStmt->close();
}

// Get categories with pagination
if (empty($whereClause)) {
    $query = "SELECT c.* FROM categories c ORDER BY name LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $limit, $offset);
} else {
    $query = "SELECT c.* FROM categories c $whereClause ORDER BY name LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssii", $searchParam, $searchParam, $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

while ($category = $result->fetch_assoc()) {
    $categories[] = $category;
}

$stmt->close();

// Calculate total pages
$totalPages = ceil($totalCategories / $limit);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>Manage Categories</h4>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addCategoryModal">
        <i class="fas fa-plus"></i> Add New Category
    </button>
</div>

<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-list mr-1"></i> All Categories
                <span class="badge badge-secondary ml-2"><?php echo $totalCategories; ?></span>
            </div>
            <form class="form-inline" method="GET" action="">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search categories..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="categories.php" class="btn btn-outline-danger">
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
                        <th>ID</th>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Books</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($categories) > 0): ?>
                        <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo $category['id']; ?></td>
                            <td><?php echo $category['name']; ?></td>
                            <td><?php echo $category['description'] ? $category['description'] : '<em>No description</em>'; ?></td>
                            <td>
                                <a href="books.php?category=<?php echo $category['id']; ?>" class="badge badge-info">
                                    View books
                                </a>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary" title="Edit Category" 
                                            data-toggle="modal" data-target="#editCategoryModal<?php echo $category['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" title="Delete Category" 
                                            onclick="confirmDelete(<?php echo $category['id']; ?>, '<?php echo $category['name']; ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-3">No categories found</td>
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

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="category_name">Category Name</label>
                        <input type="text" class="form-control" id="category_name" name="category_name" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<?php foreach ($categories as $category): ?>
<div class="modal fade" id="editCategoryModal<?php echo $category['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editCategoryModalLabel<?php echo $category['id']; ?>" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCategoryModalLabel<?php echo $category['id']; ?>">Edit Category</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="category_name<?php echo $category['id']; ?>">Category Name</label>
                        <input type="text" class="form-control" id="category_name<?php echo $category['id']; ?>" 
                               name="category_name" required value="<?php echo htmlspecialchars($category['name']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="description<?php echo $category['id']; ?>">Description</label>
                        <textarea class="form-control" id="description<?php echo $category['id']; ?>" 
                                name="description" rows="3"><?php echo htmlspecialchars($category['description']); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_category" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
function confirmDelete(categoryId, categoryName) {
    if (confirm('Are you sure you want to delete category "' + categoryName + '"? This action cannot be undone.')) {
        window.location.href = 'categories.php?action=delete&id=' + categoryId;
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>