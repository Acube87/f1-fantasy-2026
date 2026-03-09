<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

$user = getCurrentUser();
if (!$user) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$input = [];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Verify CSRF token for POST requests
    if (!isset($input['csrf_token']) || !validateCSRF($input['csrf_token'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Security validation failed']);
        exit;
    }
}

header('Content-Type: application/json');

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    $postId = (int)($_GET['post_id'] ?? 0);
    
    if ($action === 'get_comments' && $postId) {
        // Get comments for a post
        $stmt = $db->prepare("
            SELECT c.comment, c.created_at, u.username
            FROM post_comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.post_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Format timestamps
        foreach ($comments as &$comment) {
            $comment['created_at'] = date('M d, H:i', strtotime($comment['created_at']));
        }
        
        echo json_encode(['success' => true, 'comments' => $comments]);
        exit;
    }
}

if ($method === 'POST') {
    $action = $input['action'] ?? '';
    $postId = (int)($input['post_id'] ?? 0);
    
    if ($action === 'toggle_like' && $postId) {
        // Check if user already liked this post
        $stmt = $db->prepare("SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $postId, $user['id']);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        
        if ($existing) {
            // Unlike
            $stmt = $db->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $postId, $user['id']);
            $stmt->execute();
            $liked = false;
        } else {
            // Like
            $stmt = $db->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $postId, $user['id']);
            $stmt->execute();
            $liked = true;
        }
        
        // Get new like count
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM post_likes WHERE post_id = ?");
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['count'];
        
        echo json_encode(['success' => true, 'liked' => $liked, 'like_count' => $count]);
        exit;
    }
    
    if ($action === 'add_comment' && $postId) {
        $comment = trim($input['comment'] ?? '');
        
        if (empty($comment)) {
            echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
            exit;
        }
        
        if (strlen($comment) > 500) {
            echo json_encode(['success' => false, 'message' => 'Comment too long (max 500 characters)']);
            exit;
        }
        
        // Add comment
        $stmt = $db->prepare("INSERT INTO post_comments (post_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $postId, $user['id'], $comment);
        $stmt->execute();
        
        // Get new comment count
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM post_comments WHERE post_id = ?");
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['count'];
        
        echo json_encode(['success' => true, 'comment_count' => $count]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>