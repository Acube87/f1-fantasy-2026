<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$user = getCurrentUser();
if (!$user) {
    header('Location: login.php');
    exit;
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
    FOREIGN KEY (author_id) REFERENCES users(id),
    INDEX (race_id),
    INDEX (created_at)
)");

// Ensure social tables exist (likes/comments)
$db->query("CREATE TABLE IF NOT EXISTS post_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (post_id, user_id)
)");

$db->query("CREATE TABLE IF NOT EXISTS post_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Get all posts with race info
$stmt = $db->prepare("
    SELECT p.id, p.race_id, p.title, p.content, p.created_at, r.race_name, r.country, u.username, p.is_manual,
           (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as like_count,
           (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comment_count,
           (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND user_id = ?) as user_liked
    FROM posts p
    LEFT JOIN races r ON p.race_id = r.id
    LEFT JOIN users u ON p.author_id = u.id
    ORDER BY p.created_at DESC
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>F1 Fantasy News - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-950 text-white font-sans">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="border-b border-white/10 sticky top-0 z-50 bg-slate-950/95 backdrop-blur-sm">
            <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
                <a href="dashboard.php" class="flex items-center gap-2 hover:opacity-80 transition">
                    <i class="fas fa-chevron-left"></i>
                    <span class="font-bold text-orange-500">Back to Dashboard</span>
                </a>
                <h1 class="text-2xl font-bold text-orange-500 flex items-center gap-2">
                    <i class="fas fa-newspaper"></i> F1 Fantasy News
                </h1>
                <div class="w-24"></div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="max-w-4xl mx-auto px-4 py-8">
            <!-- System Announcement -->
            <div class="mb-8 bg-gradient-to-r from-emerald-900/30 to-teal-900/30 border border-emerald-500/40 rounded-lg p-6">
                <div class="flex items-start gap-4">
                    <div class="text-3xl mt-1">📢</div>
                    <div>
                        <h3 class="text-lg font-bold text-emerald-300 mb-2">System Maintenance Resolved ✅</h3>
                        <p class="text-emerald-100 mb-2">
                            We sincerely apologize for the technical difficulties you experienced with prediction saving. Our engineering team has identified and resolved the CSRF token validation issue that was preventing predictions from being saved.
                        </p>
                        <p class="text-sm text-emerald-200/80">
                            <i class="fas fa-check-circle"></i> <strong>System Fix Completed:</strong> March 9, 2026 at 08:30 UTC
                        </p>
                        <p class="text-sm text-emerald-200/80 mt-2">
                            The prediction system is now fully operational. Thank you for your patience and continued participation in Paddock Picks!
                        </p>
                    </div>
                </div>
            </div>

            <?php if (empty($posts)): ?>
                <div class="text-center py-12">
                    <div class="text-6xl mb-4">📰</div>
                    <h2 class="text-2xl font-bold text-gray-400 mb-2">No News Yet</h2>
                    <p class="text-gray-500">Check back after races are completed for post-race debrief and highlights!</p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($posts as $post): ?>
                        <article class="bg-gradient-to-br from-slate-900 to-slate-950 border border-orange-500/20 rounded-lg p-6 hover:border-orange-500/40 transition">
                            <!-- Post Header -->
                            <div class="mb-4">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <h2 class="text-2xl font-bold text-orange-400 mb-2">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </h2>
                                        <?php if ($post['race_name']): ?>
                                            <div class="flex items-center gap-2 text-sm text-gray-400">
                                                <i class="fas fa-flag-checkered"></i>
                                                <span><?php echo htmlspecialchars($post['race_name'] . ' - ' . $post['country']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <time class="text-sm text-gray-500 whitespace-nowrap">
                                        <?php echo date('M d, Y H:i', strtotime($post['created_at'])); ?>
                                    </time>
                                </div>
                                <div class="text-xs text-gray-500">
                                    By <strong class="text-gray-300"><?php echo htmlspecialchars($post['username'] ?? 'System'); ?></strong>
                                </div>
                            </div>

                            <!-- Post Content -->
                            <div class="mb-4">
                                <?php 
                                // Trusted content: either auto-debrief or manual admin post
                                $isTrusted = (isset($post['is_manual']) && $post['is_manual'] == 0) || (isset($post['is_manual']) && $post['is_manual'] == 1);
                                
                                if ($isTrusted) {
                                    // Render HTML directly for debrief and admin posts
                                    echo $post['content'];
                                } else {
                                    // Simple HTML rendering for regular posts
                                    echo '<div class="prose prose-invert max-w-none">';
                                    echo '<div class="text-gray-300 leading-relaxed">';
                                    $content = htmlspecialchars($post['content']);
                                    // Convert line breaks
                                    $content = nl2br($content);
                                    // Convert simple markdown-style formatting
                                    $content = preg_replace('/\*\*(.*?)\*\*/', '<strong class="text-white">$1</strong>', $content);
                                    $content = preg_replace('/__(.*?)__/', '<em class="text-orange-300">$1</em>', $content);
                                    echo $content;
                                    echo '</div>';
                                    echo '</div>';
                                }
                                ?>
                            </div>

                            <!-- Post Footer -->
                            <div class="pt-4 border-t border-white/10 flex justify-between items-center">
                                <div class="flex gap-4 text-sm">
                                    <button onclick="toggleLike(<?php echo $post['id']; ?>)" 
                                            class="transition flex items-center gap-1 <?php echo $post['user_liked'] ? 'text-red-400' : 'text-gray-400 hover:text-red-400'; ?>" 
                                            id="like-btn-<?php echo $post['id']; ?>">
                                        <i class="fas fa-heart"></i>
                                        <span id="like-count-<?php echo $post['id']; ?>"><?php echo $post['like_count']; ?></span>
                                    </button>
                                    <button onclick="toggleComments(<?php echo $post['id']; ?>)" 
                                            class="text-gray-400 hover:text-orange-400 transition flex items-center gap-1">
                                        <i class="fas fa-comment"></i>
                                        <span id="comment-count-<?php echo $post['id']; ?>"><?php echo $post['comment_count']; ?></span>
                                    </button>
                                </div>
                                <button class="text-gray-400 hover:text-orange-400 transition">
                                    <i class="fas fa-share"></i>
                                </button>
                            </div>

                            <!-- Comments Section -->
                            <div id="comments-<?php echo $post['id']; ?>" class="hidden mt-4 pt-4 border-t border-white/5">
                                <div class="space-y-3 mb-4" id="comments-list-<?php echo $post['id']; ?>">
                                    <!-- Comments will be loaded here -->
                                </div>
                                <div class="flex gap-2">
                                    <input type="text" id="comment-input-<?php echo $post['id']; ?>" 
                                           placeholder="Write a comment..." 
                                           class="flex-1 bg-slate-800/50 border border-white/10 rounded px-3 py-2 text-sm text-white placeholder-gray-400 focus:border-orange-500 focus:outline-none">
                                    <button onclick="addComment(<?php echo $post['id']; ?>)" 
                                            class="bg-orange-600 hover:bg-orange-500 px-4 py-2 rounded text-sm font-medium transition">
                                        Post
                                    </button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        console.log('News social script loaded');
        function toggleLike(postId) {
            fetch('api/social.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'toggle_like',
                    post_id: postId,
                    csrf_token: '<?php echo getCSRFToken(); ?>'
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const btn = document.getElementById('like-btn-' + postId);
                    const count = document.getElementById('like-count-' + postId);
                    
                    count.textContent = data.like_count;
                    
                    if (data.liked) {
                        btn.classList.remove('text-gray-400', 'hover:text-red-400');
                        btn.classList.add('text-red-400');
                    } else {
                        btn.classList.remove('text-red-400');
                        btn.classList.add('text-gray-400', 'hover:text-red-400');
                    }
                }
            })
            .catch(err => console.error('Like error:', err));
        }

        function toggleComments(postId) {
            const commentsDiv = document.getElementById('comments-' + postId);
            const isVisible = !commentsDiv.classList.contains('hidden');
            
            if (!isVisible) {
                // Show comments and load them
                commentsDiv.classList.remove('hidden');
                loadComments(postId);
            } else {
                // Hide comments
                commentsDiv.classList.add('hidden');
            }
        }

        function loadComments(postId) {
            fetch('api/social.php?action=get_comments&post_id=' + postId)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const container = document.getElementById('comments-list-' + postId);
                    container.innerHTML = '';
                    
                    data.comments.forEach(comment => {
                        const commentDiv = document.createElement('div');
                        commentDiv.className = 'bg-slate-800/30 rounded p-3';
                        commentDiv.innerHTML = `
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-medium text-orange-400 text-sm">${comment.username}</span>
                                <span class="text-xs text-gray-500">${comment.created_at}</span>
                            </div>
                            <p class="text-gray-300 text-sm">${comment.comment}</p>
                        `;
                        container.appendChild(commentDiv);
                    });
                }
            })
            .catch(err => console.error('Comments error:', err));
        }

        function addComment(postId) {
            const input = document.getElementById('comment-input-' + postId);
            const comment = input.value.trim();
            
            if (!comment) return;
            
            fetch('api/social.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'add_comment',
                    post_id: postId,
                    comment: comment,
                    csrf_token: '<?php echo getCSRFToken(); ?>'
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    loadComments(postId);
                    // Update comment count
                    const count = document.getElementById('comment-count-' + postId);
                    count.textContent = data.comment_count;
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => console.error('Comment error:', err));
        }
    </script>
</body>
</html>
