<?php
require_once 'includes/header.php';

// Get dashboard statistics
$stats = array(
    'total_books' => 0,
    'total_users' => 0,
    'total_orders' => 0,
    'total_categories' => 0,
    'total_messages' => 0
);

// Count total books
$booksQuery = $conn->query("SELECT COUNT(*) as count FROM books");
if ($booksQuery && $row = $booksQuery->fetch_assoc()) {
    $stats['total_books'] = $row['count'];
}

// Count total users
$usersQuery = $conn->query("SELECT COUNT(*) as count FROM users");
if ($usersQuery && $row = $usersQuery->fetch_assoc()) {
    $stats['total_users'] = $row['count'];
}

// Count total orders (if you have an orders table)
$ordersQuery = $conn->query("SELECT COUNT(*) as count FROM orders");
if ($ordersQuery && $row = $ordersQuery->fetch_assoc()) {
    $stats['total_orders'] = $row['count'];
}

// Count total categories
$categoriesQuery = $conn->query("SELECT COUNT(*) as count FROM categories");
if ($categoriesQuery && $row = $categoriesQuery->fetch_assoc()) {
    $stats['total_categories'] = $row['count'];
}

// Count total contact messages
$messagesQuery = $conn->query("SELECT COUNT(*) as count FROM contact_messages");
if ($messagesQuery && $row = $messagesQuery->fetch_assoc()) {
    $stats['total_messages'] = $row['count'];
}

// Get recent orders (if you have an orders table)
$recentOrders = array();
$recentOrdersQuery = $conn->query("SELECT o.*, u.name as user_name FROM orders o 
                                 JOIN users u ON o.user_id = u.id 
                                 ORDER BY o.order_date DESC LIMIT 5");

if ($recentOrdersQuery) {
    while ($order = $recentOrdersQuery->fetch_assoc()) {
        $recentOrders[] = $order;
    }
}

// Get recent users
$recentUsers = array();
$recentUsersQuery = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
if ($recentUsersQuery) {
    while ($user = $recentUsersQuery->fetch_assoc()) {
        $recentUsers[] = $user;
    }
}

// Get recent contact messages
$recentMessages = array();
$recentMessagesQuery = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5");
if ($recentMessagesQuery) {
    while ($message = $recentMessagesQuery->fetch_assoc()) {
        $recentMessages[] = $message;
    }
}
?>

<div class="row">
    <div class="col-lg-3 col-md-6">
        <div class="card stats-card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="count"><?php echo $stats['total_books']; ?></h5>
                        <p class="title mb-0">Books</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-book"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card stats-card purple mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="count"><?php echo $stats['total_users']; ?></h5>
                        <p class="title mb-0">Users</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card stats-card blue mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="count"><?php echo $stats['total_orders']; ?></h5>
                        <p class="title mb-0">Orders</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card stats-card orange mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="count"><?php echo $stats['total_categories']; ?></h5>
                        <p class="title mb-0">Categories</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-list"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Recent Orders</h6>
                <a href="orders.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recentOrders) > 0): ?>
                                <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo $order['user_name']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <?php
                                        $status = $order['status'];
                                        $statusClass = '';
                                        switch($status) {
                                            case 'Completed':
                                                $statusClass = 'success';
                                                break;
                                            case 'Pending':
                                                $statusClass = 'warning';
                                                break;
                                            case 'Cancelled':
                                                $statusClass = 'danger';
                                                break;
                                            default:
                                                $statusClass = 'primary';
                                        }
                                        ?>
                                        <span class="badge badge-<?php echo $statusClass; ?>"><?php echo $status; ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-3">No orders found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Recent Users</h6>
                <a href="users.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Joined</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recentUsers) > 0): ?>
                                <?php foreach ($recentUsers as $user): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar mr-2">
                                                <div class="avatar-text rounded-circle bg-secondary text-white">
                                                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                                </div>
                                            </div>
                                            <div>
                                                <?php echo $user['name']; ?>
                                                <small class="d-block text-muted"><?php echo $user['email']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <a href="users.php?view=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-3">No users found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Recent Contact Messages</h6>
                <a href="contact_messages.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Message ID</th>
                                <th>Sender</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recentMessages) > 0): ?>
                                <?php foreach ($recentMessages as $message): ?>
                                <tr>
                                    <td>#<?php echo $message['id']; ?></td>
                                    <td><?php echo $message['name']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($message['created_at'])); ?></td>
                                    <td>
                                        <a href="contact_messages.php?view=<?php echo $message['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3">No messages found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">System Information</h6>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>PHP Version</span>
                        <span class="text-muted"><?php echo phpversion(); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>MySQL Version</span>
                        <span class="text-muted">
                            <?php 
                            $versionQuery = $conn->query("SELECT VERSION() as version");
                            echo ($versionQuery && $row = $versionQuery->fetch_assoc()) ? $row['version'] : 'Unknown';
                            ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>Server</span>
                        <span class="text-muted"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>