<?php
require_once 'includes/header.php';

// Get order ID from URL
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$orderId) {
    $_SESSION['error_message'] = "Invalid order ID!";
    header("Location: orders.php");
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $_POST['status'];
    $updateStmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $updateStmt->bind_param("si", $newStatus, $orderId);
    
    if ($updateStmt->execute()) {
        $_SESSION['success_message'] = "Order status updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating order status: " . $conn->error;
    }
    
    $updateStmt->close();
    header("Location: view_order.php?id=" . $orderId);
    exit();
}

// Get order details
$orderStmt = $conn->prepare("SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone
                            FROM orders o
                            JOIN users u ON o.user_id = u.id
                            WHERE o.id = ?");
$orderStmt->bind_param("i", $orderId);
$orderStmt->execute();
$orderDetails = $orderStmt->get_result()->fetch_assoc();
$orderStmt->close();

if (!$orderDetails) {
    $_SESSION['error_message'] = "Order not found!";
    header("Location: orders.php");
    exit();
}

// Get order items
$itemsStmt = $conn->prepare("SELECT oi.*, b.title, b.cover_image, GROUP_CONCAT(a.name SEPARATOR ', ') as authors
                            FROM order_items oi
                            JOIN books b ON oi.book_id = b.id
                            LEFT JOIN book_authors ba ON b.id = ba.book_id
                            LEFT JOIN authors a ON ba.author_id = a.id
                            WHERE oi.order_id = ?
                            GROUP BY oi.id");
$itemsStmt->bind_param("i", $orderId);
$itemsStmt->execute();
$itemsResult = $itemsStmt->get_result();
$orderItems = [];
while ($item = $itemsResult->fetch_assoc()) {
    $orderItems[] = $item;
}
$itemsStmt->close();
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 text-primary">
            <i class="fas fa-shopping-cart me-2"></i>Order #<?php echo $orderId; ?> Details
        </h4>
        <a href="orders.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i> Back to Orders
        </a>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php 
            echo $_SESSION['success_message'];
            unset($_SESSION['success_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php 
            echo $_SESSION['error_message'];
            unset($_SESSION['error_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Order Information -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 text-primary">
                        <i class="fas fa-info-circle me-2"></i>Order Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded">
                                <small class="text-muted d-block mb-1">Order Date</small>
                                <strong><?php echo date('F d, Y H:i', strtotime($orderDetails['order_date'])); ?></strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded">
                                <small class="text-muted d-block mb-1">Payment Method</small>
                                <strong><?php echo $orderDetails['payment_method']; ?></strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded">
                                <small class="text-muted d-block mb-1">Shipping Fee</small>
                                <strong>$<?php echo number_format($orderDetails['shipping_fee'], 2); ?></strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded">
                                <small class="text-muted d-block mb-1">Total Amount</small>
                                <strong class="text-primary">$<?php echo number_format($orderDetails['total_amount'], 2); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 text-primary">
                        <i class="fas fa-user me-2"></i>Customer Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="p-3 bg-light rounded mb-3">
                        <small class="text-muted d-block mb-1">Name</small>
                        <strong><?php echo $orderDetails['user_name']; ?></strong>
                    </div>
                    <div class="p-3 bg-light rounded mb-3">
                        <small class="text-muted d-block mb-1">Email</small>
                        <strong><?php echo $orderDetails['user_email']; ?></strong>
                    </div>
                    <?php if (!empty($orderDetails['user_phone'])): ?>
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">Phone</small>
                            <strong><?php echo $orderDetails['user_phone']; ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Addresses -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 text-primary">
                        <i class="fas fa-map-marker-alt me-2"></i>Addresses
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded h-100">
                                <h6 class="text-primary mb-2">Shipping Address</h6>
                                <p class="mb-0"><?php echo nl2br($orderDetails['shipping_address']); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded h-100">
                                <h6 class="text-primary mb-2">Billing Address</h6>
                                <p class="mb-0"><?php echo nl2br($orderDetails['billing_address']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items and Status -->
        <div class="col-md-6">
            <!-- Order Status -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 text-primary">
                        <i class="fas fa-tasks me-2"></i>Order Status
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <span class="me-2">Current Status:</span>
                            <?php
                            $statusColors = [
                                'pending' => 'warning',
                                'processing' => 'info',
                                'shipped' => 'primary',
                                'delivered' => 'success',
                                'cancelled' => 'danger'
                            ];
                            $statusIcons = [
                                'pending' => 'clock',
                                'processing' => 'cog',
                                'shipped' => 'truck',
                                'delivered' => 'check-circle',
                                'cancelled' => 'times-circle'
                            ];
                            $currentStatus = $orderDetails['status'];
                            $statusColor = $statusColors[$currentStatus] ?? 'secondary';
                            $statusIcon = $statusIcons[$currentStatus] ?? 'question-circle';
                            ?>
                            <span class="badge bg-<?php echo $statusColor; ?> p-2">
                                <i class="fas fa-<?php echo $statusIcon; ?> me-1"></i>
                                <?php echo ucfirst($currentStatus); ?>
                            </span>
                        </div>
                    </div>
                    <form method="POST" action="">
                        <div class="form-group mb-3">
                            <label for="status" class="form-label">Update Status</label>
                            <select class="form-select" id="status" name="status">
                                <?php foreach ($statusColors as $status => $color): ?>
                                    <option value="<?php echo $status; ?>" <?php echo ($orderDetails['status'] == $status) ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($status); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="update_status" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Status
                        </button>
                    </form>
                </div>
            </div>

            <!-- Order Items -->
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 text-primary">
                        <i class="fas fa-book me-2"></i>Order Items
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
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
                                        <?php if (!empty($item['cover_image'])): ?>
                                            <img src="<?php echo '../' . $item['cover_image']; ?>" alt="<?php echo $item['title']; ?>" class="img-thumbnail" style="width: 40px; height: 40px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light rounded" style="width: 40px; height: 40px;"></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?php echo $item['title']; ?></div>
                                        <?php if (!empty($item['authors'])): ?>
                                            <small class="text-muted"><?php echo $item['authors']; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>$<?php echo number_format($item['price_at_purchase'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-primary rounded-pill"><?php echo $item['quantity']; ?></span>
                                    </td>
                                    <td class="fw-bold">$<?php echo number_format($item['price_at_purchase'] * $item['quantity'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                    <td>$<?php echo number_format($orderDetails['total_amount'] - $orderDetails['shipping_fee'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Shipping:</strong></td>
                                    <td>$<?php echo number_format($orderDetails['shipping_fee'], 2); ?></td>
                                </tr>
                                <tr class="fw-bold">
                                    <td colspan="4" class="text-end">Total:</td>
                                    <td class="text-primary">$<?php echo number_format($orderDetails['total_amount'], 2); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 