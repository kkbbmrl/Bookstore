<?php
require_once 'includes/header.php';

// Check if admin is viewing a specific message
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $message_id = $_GET['view'];
    $message_query = $conn->prepare("SELECT * FROM contact_messages WHERE id = ?");
    $message_query->bind_param("i", $message_id);
    $message_query->execute();
    $message = $message_query->get_result()->fetch_assoc();
    $message_query->close();
    
    // Mark message as read if found
    if ($message) {
        if (!$message['read_status']) {
            $mark_read = $conn->prepare("UPDATE contact_messages SET read_status = 1 WHERE id = ?");
            $mark_read->bind_param("i", $message_id);
            $mark_read->execute();
            $mark_read->close();
            $message['read_status'] = 1;
        }
    }
}

// Delete message if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $message_id = $_GET['delete'];
    $delete_query = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
    $delete_query->bind_param("i", $message_id);
    
    if ($delete_query->execute()) {
        $_SESSION['success_message'] = "Message deleted successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to delete message.";
    }
    
    $delete_query->close();
    header("Location: contact_messages.php");
    exit();
}

// Get messages with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$count_query = $conn->query("SELECT COUNT(*) as total FROM contact_messages");
$total_messages = $count_query->fetch_assoc()['total'];
$total_pages = ceil($total_messages / $limit);

// Get messages for current page
$messages_query = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT $offset, $limit");
$messages = array();
while ($row = $messages_query->fetch_assoc()) {
    $messages[] = $row;
}
?>

<!-- Display session messages -->
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success" role="alert">
        <?php 
        echo $_SESSION['success_message']; 
        unset($_SESSION['success_message']);
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger" role="alert">
        <?php 
        echo $_SESSION['error_message']; 
        unset($_SESSION['error_message']);
        ?>
    </div>
<?php endif; ?>

<!-- View single message -->
<?php if (isset($message) && $message): ?>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Message Details</h5>
            <a href="contact_messages.php" class="btn btn-sm btn-primary">Back to Messages</a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>From:</strong> <?php echo htmlspecialchars($message['name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($message['email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($message['phone']); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Date:</strong> <?php echo date('F j, Y g:i a', strtotime($message['created_at'])); ?></p>
                    <p><strong>Status:</strong> 
                        <?php echo $message['read_status'] ? '<span class="badge badge-success">Read</span>' : '<span class="badge badge-warning">Unread</span>'; ?>
                    </p>
                </div>
            </div>
            <hr>
            <div class="message-content mt-3">
                <h6>Message:</h6>
                <div class="p-3 bg-light rounded">
                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                </div>
            </div>
            <div class="mt-4 text-right">
                <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>" class="btn btn-primary">
                    <i class="fas fa-reply"></i> Reply via Email
                </a>
                <a href="contact_messages.php?delete=<?php echo $message['id']; ?>" class="btn btn-danger ml-2" 
                   onclick="return confirm('Are you sure you want to delete this message?');">
                    <i class="fas fa-trash"></i> Delete
                </a>
            </div>
        </div>
    </div>
<?php else: ?>
<!-- List all messages -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Contact Messages</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Sender</th>
                            <th>Email</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($messages) > 0): ?>
                            <?php foreach ($messages as $msg): ?>
                            <tr <?php echo !$msg['read_status'] ? 'class="table-active font-weight-bold"' : ''; ?>>
                                <td>#<?php echo $msg['id']; ?></td>
                                <td><?php echo htmlspecialchars($msg['name']); ?></td>
                                <td><?php echo htmlspecialchars($msg['email']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($msg['created_at'])); ?></td>
                                <td>
                                    <?php echo $msg['read_status'] ? 
                                        '<span class="badge badge-success">Read</span>' : 
                                        '<span class="badge badge-warning">Unread</span>'; ?>
                                </td>
                                <td>
                                    <a href="contact_messages.php?view=<?php echo $msg['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="contact_messages.php?delete=<?php echo $msg['id']; ?>" class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this message?');">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-3">No messages found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination-container d-flex justify-content-center my-4">
                <nav>
                    <ul class="pagination">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>