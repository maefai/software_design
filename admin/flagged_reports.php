<?php
// admin/flagged_reports.php
require_once 'includes/admin_auth.php';

// Get flagged items
$sql = "SELECT f.*, 
        CASE WHEN f.item_type = 'post' THEN p.content 
             WHEN f.item_type = 'comment' THEN c.comment END as content,
        u.email as flagged_by_email
        FROM flagged_items f
        LEFT JOIN posts p ON f.item_type = 'post' AND f.item_id = p.id
        LEFT JOIN comments c ON f.item_type = 'comment' AND f.item_id = c.id
        LEFT JOIN users u ON f.flagged_by = u.id
        WHERE f.status = 'pending'
        ORDER BY f.created_at DESC";
$flagged_items = $conn->query($sql)->fetchAll();

// Handle review action
if (isset($_POST['action']) && isset($_POST['item_id'])) {
    $item_id = $_POST['item_id'];
    $item_type = $_POST['item_type'];
    $action = $_POST['action']; // 'dismiss' or 'remove'
    $notes = $_POST['notes'] ?? '';
    
    if ($action === 'remove') {
        $table = ($item_type === 'post') ? 'posts' : 'comments';
        $stmt = $conn->prepare("UPDATE $table SET status = 'removed' WHERE id = ?");
        $stmt->execute([$item_id]);
    }
    
    $stmt = $conn->prepare("UPDATE flagged_items SET status = 'reviewed', resolved_by = ?, resolved_at = NOW(), admin_notes = ? WHERE id = ?");
    $stmt->execute([$_SESSION['user_id'], $notes, $item_id]);
    
    $_SESSION['success'] = "Report reviewed successfully!";
    header("Location: flagged_reports.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Flagged Reports - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Flagged Content for Review</h2>
    
    <?php if (empty($flagged_items)): ?>
        <div class="alert alert-success">No pending flagged items. All good!</div>
    <?php else: ?>
        <?php foreach ($flagged_items as $item): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <strong>Reported by:</strong> <?php echo htmlspecialchars($item['flagged_by_email']); ?>
                    <span class="float-end"><?php echo $item['created_at']; ?></span>
                </div>
                <div class="card-body">
                    <p><strong>Reason:</strong> <?php echo htmlspecialchars($item['reason']); ?></p>
                    <p><strong>Content:</strong></p>
                    <div class="border p-3 bg-light">
                        <?php echo nl2br(htmlspecialchars($item['content'])); ?>
                    </div>
                    
                    <form method="POST" class="mt-3">
                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                        <input type="hidden" name="item_type" value="<?php echo $item['item_type']; ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <textarea name="notes" class="form-control" placeholder="Admin notes (optional)"></textarea>
                            </div>
                            <div class="col-md-6">
                                <button type="submit" name="action" value="dismiss" class="btn btn-secondary">Dismiss (Keep Content)</button>
                                <button type="submit" name="action" value="remove" class="btn btn-danger">Remove Content</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>