<?php
/**
 * Admin: Create News Post
 * Simple admin tool to publish posts to the news feed.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

$user = getCurrentUser();
if (!$user || $user['username'] !== 'Angrycube') {
    die('Unauthorized');
}

$db = getDB();

// Ensure posts table exists
$db->query("CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    race_id INT,
    title VARCHAR(255),
    content LONGTEXT,
    author_id INT,
    is_manual TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (race_id) REFERENCES races(id),
    FOREIGN KEY (author_id) REFERENCES users(id)
)");

$races = $db->query("SELECT id, race_name FROM races ORDER BY race_date ASC")->fetch_all(MYSQLI_ASSOC);

$message = null;
$messageType = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRF($_POST['csrf_token'] ?? '')) {
    $title    = trim($_POST['title'] ?? '');
    $content  = trim($_POST['content'] ?? '');
    $raceId   = !empty($_POST['race_id']) ? (int)$_POST['race_id'] : null;
    $isManual = 1;

    if ($title && $content) {
        $stmt = $db->prepare("INSERT INTO posts (race_id, title, content, author_id, is_manual) VALUES (?, ?, ?, ?, 1)");
        $stmt->bind_param("issi", $raceId, $title, $content, $user['id']);
        if ($stmt->execute()) {
            $message = "✅ Post published! <a href='../news.php' class='underline text-green-300'>View on News page →</a>";
            $messageType = 'success';
        } else {
            $message = "❌ DB Error: " . htmlspecialchars($db->error);
            $messageType = 'error';
        }
    } else {
        $message = "❌ Title and content are required.";
        $messageType = 'error';
    }
}

// Handle Demelete
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && isset($_GET['csrf']) && validateCSRF($_GET['csrf'])) {
    $delId = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->bind_param("i", $delId);
    if ($stmt->execute()) {
        $message = "✅ Post deleted successfully.";
        $messageType = 'success';
    } else {
        $message = "❌ Failed to delete post.";
        $messageType = 'error';
    }
}

$csrfToken = getCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post — <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,500;1,400;1,500&display=swap" rel="stylesheet">
    <style>
        :root{
          --canvas:#F5F3EF;--bg:#F5F3EF;--surface:#FFF;--border:#E8E5E0;--border-light:#F0EDE8;
          --text:#1A1A1A;--text2:#6B6864;--text3:#A09C96;
          --accent:#C41E3A;--accent-soft:#F5E6E9;
          --live:#2D6A4F;
        }
        *{margin:0;padding:0;box-sizing:border-box}
        body{background:var(--canvas);color:var(--text);font-family:'Inter',sans-serif;font-size:15px;line-height:1.6;-webkit-font-smoothing:antialiased;padding:24px}
        a{text-decoration:none;color:inherit}
        .caps{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:var(--text3)}
        .card{background:var(--surface);border:1px solid var(--border)}
        .badge{display:inline-flex;align-items:center;gap:4px;padding:2px 10px;font-size:10px;font-weight:500;border:1px solid}
        input,select,textarea{font-family:'Inter',sans-serif;font-size:14px;color:var(--text);background:var(--surface);border:1px solid var(--border);padding:10px 14px;outline:none;transition:border-color 150ms;width:100%}
        input:focus,select:focus,textarea:focus{border-color:var(--accent)}
        textarea{resize:vertical;font-family:'JetBrains Mono',monospace;font-size:13px}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 24px;font-size:14px;font-weight:600;border:1px solid var(--border);cursor:pointer;transition:all 150ms;background:var(--surface);color:var(--text)}
        .btn:hover{background:var(--canvas)}
        .btn-primary{background:var(--accent);color:#fff;border-color:var(--accent)}
        .btn-primary:hover{opacity:0.9}
        label{display:block;font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:var(--text3);margin-bottom:6px}
        .page{max-width:640px;margin:0 auto}
        .flex{display:flex}.items-center{align-items:center}.justify-between{justify-content:space-between}.gap-3{gap:12px}.gap-4{gap:16px}.mb-4{margin-bottom:16px}.mb-6{margin-bottom:24px}.mb-8{margin-bottom:32px}.mt-8{margin-top:32px}
        .p-4{padding:16px}.p-6{padding:24px}
        .msg{font-size:13px;padding:12px 16px;border:1px solid}
        .msg.success{background:rgba(45,106,79,0.06);border-color:rgba(45,106,79,0.15);color:var(--live)}
        .msg.error{background:rgba(196,30,58,0.06);border-color:rgba(196,30,58,0.15);color:var(--accent)}
        .msg a{font-weight:600;text-decoration:underline}
        .truncate{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    </style>
</head>
<body>
    <div class="page">

        <!-- Nav -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--border)">
            <div style="display:flex;align-items:center;gap:8px">
                <i class="fas fa-pen-nib" style="color:var(--accent);font-size:16px"></i>
                <span style="font-weight:700;font-size:16px">Create News Post</span>
            </div>
            <div style="display:flex;align-items:center;gap:12px">
                <a href="race-control.php" style="font-size:12px;color:var(--text2)"><i class="fas fa-arrow-left"></i> Race Control</a>
                <a href="../news.php" style="font-size:12px;color:var(--accent);font-weight:600"><i class="fas fa-newspaper"></i> News Feed</a>
            </div>
        </div>

        <!-- Form -->
        <div class="card" style="padding:24px;margin-bottom:24px">
            <div style="margin-bottom:20px">
                <div style="font-weight:700;font-size:18px;margin-bottom:2px">New Post</div>
                <div class="caps">Posts appear on the News page with likes and comments</div>
            </div>

            <?php if ($message): ?>
            <div class="msg <?php echo $messageType === 'success' ? 'success' : 'error'; ?>" style="margin-bottom:20px">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <form method="POST" style="display:flex;flex-direction:column;gap:20px">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                <div>
                    <label>Post Title *</label>
                    <input type="text" name="title" required style="font-weight:600" placeholder="e.g. App Update v2.1 — Score Corrections">
                </div>

                <div>
                    <label>Related Race <span style="font-weight:400;text-transform:none;color:var(--text3)">(optional)</span></label>
                    <select name="race_id">
                        <option value="">— General post (no specific race) —</option>
                        <?php foreach ($races as $r): ?>
                        <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['race_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label>Content * <span style="font-weight:400;text-transform:none;color:var(--text3)">(HTML supported)</span></label>
                    <textarea name="content" required rows="14" placeholder="Write your post content here. HTML is supported for rich formatting."></textarea>
                    <div class="caps" style="margin-top:4px;font-size:9px">HTML supported for rich posts. Line breaks are preserved.</div>
                </div>

                <div style="padding:12px 16px;background:var(--accent-soft);border:1px solid rgba(196,30,58,0.12);font-size:12px;color:var(--text2)">
                    <strong style="color:var(--accent)">Preview:</strong> The post will appear at the top of the News feed immediately. Users can like and comment.
                </div>

                <button type="submit" class="btn btn-primary" style="padding:14px;font-size:15px;font-weight:700">
                    <i class="fas fa-paper-plane"></i> Publish to News Feed
                </button>
            </form>
        </div>

        <!-- Recent Posts -->
        <div class="card" style="padding:20px">
            <div class="caps" style="margin-bottom:12px;font-size:11px"><i class="fas fa-list" style="margin-right:8px"></i>Recent Posts</div>
            <?php
            $recent = $db->query("SELECT id, title, created_at FROM posts ORDER BY created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
            foreach ($recent as $rp): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border-light)">
                <div class="truncate" style="flex:1">
                    <div style="font-weight:600;font-size:13px"><?php echo htmlspecialchars($rp['title']); ?></div>
                    <div class="caps" style="font-size:9px"><?php echo date('d M H:i', strtotime($rp['created_at'])); ?></div>
                </div>
                <a href="?delete=<?php echo $rp['id']; ?>&csrf=<?php echo $csrfToken; ?>" 
                   onclick="return confirm('Delete this post?');"
                   style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-size:11px;color:var(--text3);border:1px solid var(--border);flex-shrink:0">
                    <i class="fas fa-trash"></i>
                </a>
            </div>
            <?php endforeach; ?>
            <?php if (empty($recent)): ?><div class="caps" style="font-size:11px;padding:12px;text-align:center">No posts yet.</div><?php endif; ?>
        </div>
    </div>
</body>
</html>
