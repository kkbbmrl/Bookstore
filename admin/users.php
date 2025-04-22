<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../connection.php'; // Ensure $conn is available

// Define variables
$users = array();
$totalUsers = 0;
$limit = 10; // Records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle user actions
if ($action && $userId) {
    switch ($action) {
        case 'delete':
            $deleteStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $deleteStmt->bind_param("i", $userId);

            if ($deleteStmt->execute()) {
                $_SESSION['success_message'] = "User deleted successfully!";
            } else {
                $_SESSION['error_message'] = "Error deleting user: " . $conn->error;
            }

            $deleteStmt->close();
            header("Location: users.php");
            exit();

        case 'toggle_admin':
            $toggleStmt = $conn->prepare("UPDATE users SET is_admin = NOT is_admin WHERE id = ?");
            $toggleStmt->bind_param("i", $userId);

            if ($toggleStmt->execute()) {
                $_SESSION['success_message'] = "User admin status updated successfully!";
            } else {
                $_SESSION['error_message'] = "Error updating user: " . $conn->error;
            }

            $toggleStmt->close();
            header("Location: users.php");
            exit();
    }
}

// Add new user
if (isset($_POST['add_user'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();

    if ($checkEmail->num_rows > 0) {
        $_SESSION['error_message'] = "Email already exists!";
    } else {
        $insertStmt = $conn->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, ?)");
        $insertStmt->bind_param("sssi", $name, $email, $password, $is_admin);

        if ($insertStmt->execute()) {
            $_SESSION['success_message'] = "User added successfully!";
        } else {
            $_SESSION['error_message'] = "Error adding user: " . $conn->error;
        }

        $insertStmt->close();
    }

    $checkEmail->close();
    header("Location: users.php");
    exit();
}

// Include header after all logic
require_once 'includes/header.php';

// Search functionality
$whereClause = "";
if (!empty($search)) {
    $whereClause = "WHERE name LIKE ? OR email LIKE ?";
    $searchParam = "%$search%";
}

// Count total users for pagination
if (empty($whereClause)) {
    $countQuery = $conn->query("SELECT COUNT(*) as count FROM users");
    $totalUsers = $countQuery->fetch_assoc()['count'];
} else {
    $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM users $whereClause");
    $countStmt->bind_param("ss", $searchParam, $searchParam);
    $countStmt->execute();
    $result = $countStmt->get_result();
    $totalUsers = $result->fetch_assoc()['count'];
    $countStmt->close();
}

// Get users with pagination
if (empty($whereClause)) {
    $query = "SELECT * FROM users ORDER BY id DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $limit, $offset);
} else {
    $query = "SELECT * FROM users $whereClause ORDER BY id DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssii", $searchParam, $searchParam, $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

while ($user = $result->fetch_assoc()) {
    $users[] = $user;
}

$stmt->close();

// Calculate total pages
$totalPages = ceil($totalUsers / $limit);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>Manage Users</h4>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addUserModal">
        <i class="fas fa-plus"></i> Add New User
    </button>
</div>

<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-users mr-1"></i> All Users
                <span class="badge badge-secondary ml-2"><?php echo $totalUsers; ?></span>
            </div>
            <form class="form-inline" method="GET" action="">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search users..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="users.php" class="btn btn-outline-danger">
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
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Registration Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar mr-2">
                                        <div class="avatar-text rounded-circle bg-<?php echo $user['is_admin'] ? 'primary' : 'secondary'; ?> text-white">
                                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                        </div>
                                    </div>
                                    <?php echo $user['name']; ?>
                                </div>
                            </td>
                            <td><?php echo $user['email']; ?></td>
                            <td>
                                <span class="badge badge-<?php echo $user['is_admin'] ? 'primary' : 'secondary'; ?>">
                                    <?php echo $user['is_admin'] ? 'Admin' : 'User'; ?>
                                </span>
                            </td>
                            <td><?php echo isset($user['registration_date']) ? date('M d, Y', strtotime($user['registration_date'])) : 'N/A'; ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="?action=toggle_admin&id=<?php echo $user['id']; ?>" class="btn btn-outline-primary" title="Toggle Admin Status">
                                        <i class="fas <?php echo $user['is_admin'] ? 'fa-user' : 'fa-user-shield'; ?>"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" title="Delete User" 
                                            onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo $user['name']; ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-3">No users found</td>
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

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="is_admin" name="is_admin">
                        <label class="form-check-label" for="is_admin">Admin Privileges</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(userId, userName) {
    if (confirm('Are you sure you want to delete user "' + userName + '"? This action cannot be undone.')) {
        window.location.href = 'users.php?action=delete&id=' + userId;
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>