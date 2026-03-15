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
    <title>Create News Post — Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="gaming-theme text-gray-200 min-h-screen p-8">
    <div class="max-w-3xl mx-auto">
        <div class="mb-6 flex items-center justify-between">
            <a href="race-control.php" class="text-gray-400 hover:text-white text-sm transition">
                <i class="fas fa-arrow-left mr-2"></i>Race Control
            </a>
            <a href="../news.php" class="text-orange-400 hover:text-orange-300 text-sm transition font-bold">
                <i class="fas fa-newspaper mr-1"></i>View News Feed
            </a>
        </div>

        <div class="g-card p-8 rounded-[2rem] border-t-4 border-t-orange-500">
            <h1 class="text-3xl font-black text-white italic uppercase mb-2">
                <i class="fas fa-pen-nib text-orange-500 mr-2"></i>Create News Post
            </h1>
            <p class="text-gray-400 text-sm mb-8">Posts appear on the <strong class="text-white">News & Posts</strong> page for all users with likes and comments.</p>

            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-xl border <?php echo $messageType === 'success' ? 'bg-green-500/10 border-green-500/30 text-green-300' : 'bg-red-500/10 border-red-500/30 text-red-400'; ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Post Title *</label>
                    <input type="text" name="title" required
                           class="w-full p-4 rounded-xl bg-black/30 border border-white/10 text-white font-bold placeholder-gray-600 focus:border-orange-500 focus:outline-none"
                           placeholder="e.g. App Update v2.1 — Score Corrections & New Features">
                </div>

                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Related Race <span class="text-gray-600 font-normal normal-case">(optional)</span></label>
                    <select name="race_id" class="w-full p-4 rounded-xl bg-black/30 border border-white/10 text-white font-bold focus:border-orange-500 focus:outline-none">
                        <option value="">— No specific race (General post) —</option>
                        <?php foreach ($races as $r): ?>
                        <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['race_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">
                        Content * <span class="text-gray-600 font-normal normal-case">(HTML supported for rich posts)</span>
                    </label>
                    <textarea name="content" required rows="14"
                              class="w-full p-4 rounded-xl bg-black/30 border border-white/10 text-white font-mono text-sm placeholder-gray-600 focus:border-orange-500 focus:outline-none resize-y"
                              placeholder="Write your post content here. HTML is supported for rich formatting.&#10;&#10;Plain text works too — line breaks are preserved."></textarea>
                    <p class="text-[10px] text-gray-600 mt-1">Tip: Use HTML for rich posts (bold, colors, grids). For simple posts, plain text with line breaks works fine.</p>
                </div>

                <div class="bg-orange-500/5 border border-orange-500/15 rounded-xl p-4">
                    <p class="text-xs text-orange-300">
                        <strong>Preview:</strong> The post will appear at the top of the News feed immediately after publishing. Users can like and comment on it.
                    </p>
                </div>

                <button type="submit" id="publish-btn"
                        class="w-full py-4 bg-gradient-to-r from-orange-600 to-orange-500 hover:from-orange-500 hover:to-orange-400 text-white font-black text-lg rounded-2xl transition transform hover:scale-[1.02] flex items-center justify-center gap-3 shadow-lg shadow-orange-500/20">
                    <i class="fas fa-paper-plane"></i>
                    PUBLISH TO NEWS FEED
                </button>
            </form>
        </div>

        <!-- Recent Posts -->
        <div class="mt-8 g-card p-6 rounded-[2rem]">
            <h2 class="text-sm font-black text-white uppercase tracking-widest mb-4"><i class="fas fa-list text-gray-500 mr-2"></i>Manage Posts</h2>
            <?php
            $recent = $db->query("SELECT id, title, created_at FROM posts ORDER BY created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
            foreach ($recent as $rp): ?>
            <div class="flex items-center justify-between py-3 border-b border-white/5 last:border-0 group">
                <div class="truncate flex-1">
                    <div class="text-sm text-gray-300 font-bold"><?php echo htmlspecialchars($rp['title']); ?></div>
                    <div class="text-[10px] text-gray-600"><?php echo date('d M H:i', strtotime($rp['created_at'])); ?></div>
                </div>
                <div class="flex items-center gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                    <a href="?delete=<?php echo $rp['id']; ?>&csrf=<?php echo $csrfToken; ?>" 
                       onclick="return confirm('Delete this post?');"
                       class="w-8 h-8 rounded-full bg-red-500/10 text-red-400 flex items-center justify-center hover:bg-red-500 hover:text-white transition">
                        <i class="fas fa-trash text-xs"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($recent)): ?><p class="text-gray-500 text-sm">No posts yet.</p><?php endif; ?>
        </div>
    </div>
</body>
</html>
