<?php
require_once 'includes/header.php';

// Define variables
$orders = array();
$totalOrders = 0;
$limit = 10; // Records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle order actions
if ($action && $orderId) {
    switch ($action) {
        case 'update_status':
            $newStatus = $_POST['status'];
            $updateStmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $updateStmt->bind_param("si", $newStatus, $orderId);
            
            if ($updateStmt->execute()) {
                $_SESSION['success_message'] = "Order status updated successfully!";
            } else {
                $_SESSION['error_message'] = "Error updating order status: " . $conn->error;
            }
            
            $updateStmt->close();
            header("Location: orders.php");
            exit();
            
        case 'delete':
            // Delete order items first
            $deleteItemsStmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
            $deleteItemsStmt->bind_param("i", $orderId);
            $deleteItemsStmt->execute();
            $deleteItemsStmt->close();
            
            // Then delete the order
            $deleteOrderStmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
            $deleteOrderStmt->bind_param("i", $orderId);
            
            if ($deleteOrderStmt->execute()) {
                $_SESSION['success_message'] = "Order deleted successfully!";
            } else {
                $_SESSION['error_message'] = "Error deleting order: " . $conn->error;
            }
            
            $deleteOrderStmt->close();
            header("Location: orders.php");
            exit();
            
        case 'view':
            // The view action is handled below to display order details
            break;
    }
}

// Search functionality
$whereClause = "";
$params = [];
$types = "";

if (!empty($search)) {
    $whereClause = "WHERE o.id LIKE ? OR u.name LIKE ? OR u.email LIKE ?";
    $searchParam = "%$search%";
    $params = [$searchParam, $searchParam, $searchParam];
    $types = "sss";
}

if (!empty($status)) {
    if (empty($whereClause)) {
        $whereClause = "WHERE o.status = ?";
    } else {
        $whereClause .= " AND o.status = ?";
    }
    $params[] = $status;
    $types .= "s";
}

// Count total orders for pagination
if (empty($whereClause)) {
    $countQuery = $conn->query("SELECT COUNT(*) as count FROM orders");
    $totalOrders = $countQuery->fetch_assoc()['count'];
} else {
    $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM orders o JOIN users u ON o.user_id = u.id $whereClause");
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $result = $countStmt->get_result();
    $totalOrders = $result->fetch_assoc()['count'];
    $countStmt->close();
}

// Prepare pagination parameters
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

// Get orders with pagination and join with users
$query = "SELECT o.*, u.name as user_name, u.email as user_email 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          $whereClause 
          ORDER BY o.order_date DESC 
          LIMIT ? OFFSET ?";
          
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

while ($order = $result->fetch_assoc()) {
    $orders[] = $order;
}

$stmt->close();

// Calculate total pages
$totalPages = ceil($totalOrders / $limit);

// Get order details for view
$orderDetails = null;
$orderItems = [];
if (isset($_GET['action']) && $_GET['action'] == 'view' && !empty($_GET['id'])) {
    $viewId = (int)$_GET['id'];
    
    // Get order details
    $orderStmt = $conn->prepare("SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone
                                FROM orders o
                                JOIN users u ON o.user_id = u.id
                                WHERE o.id = ?");
    $orderStmt->bind_param("i", $viewId);
    $orderStmt->execute();
    $orderDetails = $orderStmt->get_result()->fetch_assoc();
    $orderStmt->close();
    
    // Get order items
    if ($orderDetails) {
        $itemsStmt = $conn->prepare("SELECT oi.*, b.title, b.author, b.cover_img
                                    FROM order_items oi
                                    JOIN books b ON oi.book_id = b.id
                                    WHERE oi.order_id = ?");
        $itemsStmt->bind_param("i", $viewId);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result();
        
        while ($item = $itemsResult->fetch_assoc()) {
            $orderItems[] = $item;
        }
        
        $itemsStmt->close();
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>Manage Orders</h4>
</div>

<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-shopping-cart mr-1"></i> All Orders
                <span class="badge badge-secondary ml-2"><?php echo $totalOrders; ?></span>
            </div>
            <div class="d-flex">
                <!-- Status filter -->
                <div class="mr-2">
                    <select class="form-control" id="statusFilter" onchange="filterByStatus(this.value)">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?php echo ($status == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="Processing" <?php echo ($status == 'Processing') ? 'selected' : ''; ?>>Processing</option>
                        <option value="Shipped" <?php echo ($status == 'Shipped') ? 'selected' : ''; ?>>Shipped</option>
                        <option value="Delivered" <?php echo ($status == 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
                        <option value="Cancelled" <?php echo ($status == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <!-- Search form -->
                <form class="form-inline" method="GET" action="">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search orders..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                            <?php if (!empty($search) || !empty($status)): ?>
                                <a href="orders.php" class="btn btn-outline-danger">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td>
                                <div>
                                    <?php echo $order['user_name']; ?>
                                    <small class="d-block text-muted"><?php echo $order['user_email']; ?></small>
                                </div>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <?php
                                $statusClass = '';
                                switch($order['status']) {
                                    case 'Pending':
                                        $statusClass = 'warning';
                                        break;
                                    case 'Processing':
                                        $statusClass = 'info';
                                        break;
                                    case 'Shipped':
                                        $statusClass = 'primary';
                                        break;
                                    case 'Delivered':
                                        $statusClass = 'success';
                                        break;
                                    case 'Cancelled':
                                        $statusClass = 'danger';
                                        break;
                                    default:
                                        $statusClass = 'secondary';
                                }
                                ?>
                                <span class="badge badge-<?php echo $statusClass; ?>"><?php echo $order['status']; ?></span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="?action=view&id=<?php echo $order['id']; ?>" class="btn btn-outline-primary" title="View Order">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" title="Delete Order" 
                                            onclick="confirmDelete(<?php echo $order['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-3">No orders found</td>
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
                    <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo (!empty($search)) ? '&search='.$search : ''; ?><?php echo (!empty($status)) ? '&status='.$status : ''; ?>">Previous</a>
                </li>
                
                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo (!empty($search)) ? '&search='.$search : ''; ?><?php echo (!empty($status)) ? '&status='.$status : ''; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo (!empty($search)) ? '&search='.$search : ''; ?><?php echo (!empty($status)) ? '&status='.$status : ''; ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- Order Details Modal -->
<?php if ($orderDetails): ?>
<div class="modal fade" id="orderDetailsModal" tabindex="-1" role="dialog" aria-labelledby="orderDetailsModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderDetailsModalLabel">Order #<?php echo $orderDetails['id']; ?> Details</h5>
                <a href="orders.php" class="close" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </a>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Customer Information</h6>
                        <p>
                            <strong>Name:</strong> <?php echo $orderDetails['user_name']; ?><br>
                            <strong>Email:</strong> <?php echo $orderDetails['user_email']; ?><br>
                            <?php if (!empty($orderDetails['user_phone'])): ?>
                                <strong>Phone:</strong> <?php echo $orderDetails['user_phone']; ?><br>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Order Information</h6>
                        <p>
                            <strong>Order Date:</strong> <?php echo date('F d, Y H:i', strtotime($orderDetails['order_date'])); ?><br>
                            <strong>Status:</strong> <span class="badge badge-<?php echo $statusClass; ?>"><?php echo $orderDetails['status']; ?></span><br>
                            <strong>Payment Method:</strong> <?php echo $orderDetails['payment_method']; ?>
                        </p>
                    </div>
                </div>
                
                <?php if (!empty($orderDetails['shipping_address'])): ?>
                <div class="mb-4">
                    <h6>Shipping Address</h6>
                    <p><?php echo nl2br($orderDetails['shipping_address']); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="mb-4">
                    <h6>Order Items</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th style="width: 50px;"></th>
                                    <th>Book</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo '../' . $item['cover_img']; ?>" alt="<?php echo $item['title']; ?>" style="width: 40px;" class="img-thumbnail">
                                    </td>
                                    <td>
                                        <div><?php echo $item['title']; ?></div>
                                        <small class="text-muted"><?php echo $item['author']; ?></small>
                                    </td>
                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-right"><strong>Subtotal:</strong></td>
                                    <td>$<?php echo number_format($orderDetails['subtotal'], 2); ?></td>
                                </tr>
                                <?php if ($orderDetails['tax'] > 0): ?>
                                <tr>
                                    <td colspan="4" class="text-right"><strong>Tax:</strong></td>
                                    <td>$<?php echo number_format($orderDetails['tax'], 2); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($orderDetails['shipping_fee'] > 0): ?>
                                <tr>
                                    <td colspan="4" class="text-right"><strong>Shipping:</strong></td>
                                    <td>$<?php echo number_format($orderDetails['shipping_fee'], 2); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="4" class="text-right"><strong>Total:</strong></td>
                                    <td><strong>$<?php echo number_format($orderDetails['total_amount'], 2); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <form action="orders.php?action=update_status&id=<?php echo $orderDetails['id']; ?>" method="POST">
                    <div class="form-group">
                        <label for="status">Update Order Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="Pending" <?php echo ($orderDetails['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="Processing" <?php echo ($orderDetails['status'] == 'Processing') ? 'selected' : ''; ?>>Processing</option>
                            <option value="Shipped" <?php echo ($orderDetails['status'] == 'Shipped') ? 'selected' : ''; ?>>Shipped</option>
                            <option value="Delivered" <?php echo ($orderDetails['status'] == 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
                            <option value="Cancelled" <?php echo ($orderDetails['status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="text-right">
                        <a href="orders.php" class="btn btn-secondary">Close</a>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function(){
    $('#orderDetailsModal').modal('show');
});
</script>
<?php endif; ?>

<script>
function confirmDelete(orderId) {
    if (confirm('Are you sure you want to delete this order? This action cannot be undone.')) {
        window.location.href = 'orders.php?action=delete&id=' + orderId;
    }
}

function filterByStatus(status) {
    window.location.href = 'orders.php?status=' + status<?php echo (!empty($search)) ? " + '&search=" . $search . "'" : ''; ?>;
}
</script>

<?php require_once 'includes/footer.php'; ?>