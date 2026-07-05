<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/avatars.php';
require_once 'includes/csrf.php';

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

// Ensure social tables exist
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
    <title>News – <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --f1-carbon: #0A0A0A;
            --f1-text: #FFFFFF;
            --nfs-pink: #ff0077;
            --nfs-cyan: #00e5ff;
            --nfs-orange: #ff6a00;
            --nfs-green: #00ff88;
            --nfs-purple: #8b5cf6;
        }
        body {
            font-family: 'Inter', sans-serif;
        }
        .hero-card {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            min-height: 160px;
            display: flex;
            align-items: center;
        }
        .hero-card .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(10,10,10,0.9), rgba(10,10,10,0.5));
        }
        .hero-card .hero-carbon {
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 20px,
                rgba(255,255,255,0.02) 20px,
                rgba(255,255,255,0.02) 40px
            );
        }
        .hero-content {
            position: relative;
            z-index: 2;
            padding: 32px 28px;
        }
        .post-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            transition: border-color 0.2s;
        }
        .post-card:hover {
            border-color: rgba(255,0,119,0.2);
        }
        .nfs-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            background: linear-gradient(135deg, #ff0077, #ff6a00);
            color: white;
            font-weight: 700;
            font-size: 0.8rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .nfs-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(255,0,119,0.3);
        }
        .comment-input {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
            padding: 8px 12px;
            color: white;
            font-size: 0.85rem;
            outline: none;
            width: 100%;
        }
        .comment-input:focus {
            border-color: rgba(255,0,119,0.4);
        }
    </style>
</head>
<body style="background:var(--f1-carbon);color:var(--f1-text);min-height:100vh;">

    <?php require_once __DIR__ . '/includes/nav.php'; ?>

    <main class="pt-24 max-w-4xl mx-auto px-4 py-8">

        <!-- Hero -->
        <div class="hero-card mb-8">
            <div class="hero-overlay"></div>
            <div class="hero-carbon"></div>
            <div class="hero-content">
                <h1 class="text-3xl font-black text-white uppercase tracking-wide" style="font-family:'Bebas Neue',sans-serif;letter-spacing:0.04em">
                    <i class="fas fa-newspaper text-[#ff0077] mr-3"></i>Paddock News
                </h1>
                <p class="text-gray-500 text-sm mt-1">Race debriefs, results roundups, and league updates</p>
            </div>
        </div>

        <?php if (empty($posts)): ?>
            <div class="text-center py-12">
                <div class="text-6xl mb-4">📰</div>
                <h2 class="text-2xl font-black text-gray-400 mb-2" style="font-family:'Bebas Neue',sans-serif;letter-spacing:0.03em">No News Yet</h2>
                <p class="text-gray-500 text-sm">Check back after races are completed for post-race debrief and highlights!</p>
            </div>
        <?php else: ?>
            <div class="space-y-5">
                <?php foreach ($posts as $post): ?>
                    <article class="post-card p-6">
                        <div class="mb-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h2 class="text-xl font-black text-white" style="font-family:'Bebas Neue',sans-serif;letter-spacing:0.03em">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </h2>
                                    <?php if ($post['race_name']): ?>
                                        <div class="flex items-center gap-2 text-sm text-gray-500 mt-1">
                                            <i class="fas fa-flag-checkered text-[#ff0077]"></i>
                                            <span><?php echo htmlspecialchars($post['race_name'] . ' – ' . $post['country']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <time class="text-xs text-gray-600 whitespace-nowrap">
                                    <?php echo date('M d, Y H:i', strtotime($post['created_at'])); ?>
                                </time>
                            </div>
                            <div class="text-xs text-gray-600 mt-1">
                                By <strong class="text-gray-400"><?php echo htmlspecialchars($post['username'] ?? 'System'); ?></strong>
                            </div>
                        </div>

                        <div class="mb-4">
                            <?php
                            $rawContent = $post['content'];
                            $isRichHtml = preg_match('/<\/?(div|p|h[1-6]|table|ul|ol|strong|em|span|br)[^>]*>/i', $rawContent);

                            if ($isRichHtml) {
                                echo $rawContent;
                            } else {
                                $content = htmlspecialchars($rawContent);
                                $content = nl2br($content);
                                $content = preg_replace('/^(\s*(?:🥇|🥈|🥉|\d+\.|P\d+:?)\s*)([a-zA-Z0-9_\s]+?)(\s*-|\s*$)/m', '$1<strong class="text-[#ff0077]">$2</strong>$3', $content);
                                $content = preg_replace('/@([a-zA-Z0-9_]+)/', '<strong class="text-[#ff0077]">@$1</strong>', $content);
                                $content = preg_replace('/\*\*(.*?)\*\*/', '<strong class="text-white">$1</strong>', $content);
                                $content = preg_replace('/__(.*?)__/', '<em class="text-[#ff6a00]">$1</em>', $content);

                                echo '<div class="text-gray-300 text-sm leading-relaxed space-y-2">';
                                echo $content;
                                echo '</div>';
                            }
                            ?>
                        </div>

                        <div class="pt-4 border-t border-white/5 flex justify-between items-center">
                            <div class="flex gap-4 text-sm">
                                <button onclick="toggleLike(<?php echo $post['id']; ?>)" 
                                        class="transition flex items-center gap-1 <?php echo $post['user_liked'] ? 'text-red-400' : 'text-gray-500 hover:text-red-400'; ?>" 
                                        id="like-btn-<?php echo $post['id']; ?>">
                                    <i class="fas fa-heart"></i>
                                    <span id="like-count-<?php echo $post['id']; ?>"><?php echo $post['like_count']; ?></span>
                                </button>
                                <button onclick="toggleComments(<?php echo $post['id']; ?>)" 
                                        class="text-gray-500 hover:text-[#ff0077] transition flex items-center gap-1">
                                    <i class="fas fa-comment"></i>
                                    <span id="comment-count-<?php echo $post['id']; ?>"><?php echo $post['comment_count']; ?></span>
                                </button>
                            </div>
                        </div>

                        <div id="comments-<?php echo $post['id']; ?>" class="hidden mt-4 pt-4 border-t border-white/5">
                            <div class="space-y-3 mb-4" id="comments-list-<?php echo $post['id']; ?>"></div>
                            <div class="flex gap-2">
                                <input type="text" id="comment-input-<?php echo $post['id']; ?>" 
                                       placeholder="Write a comment..." 
                                       class="comment-input">
                                <button onclick="addComment(<?php echo $post['id']; ?>)" 
                                        class="nfs-btn text-xs px-4 py-2 whitespace-nowrap">
                                    Post
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer class="mt-12 border-t border-white/10 py-6 text-center">
        <p class="text-gray-500 text-sm mb-2">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        <p class="text-gray-600 text-xs">
            Powered by <a href="https://www.scanerrific.com" target="_blank" class="text-[#ff0077] hover:text-[#ff6a00] font-semibold transition">Scanerrific</a>
        </p>
    </footer>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
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
                    document.getElementById('like-count-' + postId).textContent = data.like_count;
                    const btn = document.getElementById('like-btn-' + postId);
                    btn.classList.toggle('text-red-400', data.liked);
                    btn.classList.toggle('text-gray-500', !data.liked);
                    btn.classList.toggle('hover:text-red-400', !data.liked);
                }
            })
            .catch(err => console.error('Like error:', err));
        }

        function toggleComments(postId) {
            const div = document.getElementById('comments-' + postId);
            const hidden = div.classList.contains('hidden');
            div.classList.toggle('hidden');
            if (!hidden) return;
            fetch('api/social.php?action=get_comments&post_id=' + postId)
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;
                const container = document.getElementById('comments-list-' + postId);
                container.innerHTML = '';
                data.comments.forEach(c => {
                    const el = document.createElement('div');
                    el.className = 'bg-white/5 rounded p-3';
                    el.innerHTML = '<div class="flex items-center gap-2 mb-1"><span class="font-semibold text-[#ff0077] text-sm">' + c.username + '</span><span class="text-xs text-gray-600">' + c.created_at + '</span></div><p class="text-gray-300 text-sm">' + c.comment + '</p>';
                    container.appendChild(el);
                });
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
                    document.getElementById('comment-count-' + postId).textContent = data.comment_count;
                    // Reload comments
                    const btn = document.querySelector('[onclick="toggleComments(' + postId + ')"]');
                    if (btn) btn.click();
                }
            })
            .catch(err => console.error('Comment error:', err));
        }
    </script>
<script src="app.js"></script>
</body>
</html>
