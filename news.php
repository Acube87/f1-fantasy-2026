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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:ital,wght@1,400;1,500&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root{--canvas:#F5F3EF;--bg:#F5F3EF;--bg2:#FAF9F7;--surface:#FFF;--card:#FFF;--card2:#FAF9F7;--surface-muted:#FAF9F7;--border:#E8E5E0;--border-light:#F0EDE8;--text:#1A1A1A;--text2:#6B6864;--text3:#A09C96;--accent:#C41E3A;--accent-soft:#F5E6E9;--live:#2D6A4F;--gold:#C9A96E;--silver:#A8A5A0;--bronze:#B08050}
        body {
            font-family: 'Inter', sans-serif; background: var(--canvas); color: var(--text);
            min-height: 100vh;
        }
        .hero{position:relative;height:200px;overflow:hidden}
        .hero-bg{position:absolute;inset:0;background-size:cover;background-position:center}
        .hero-overlay{position:absolute;inset:0;background:linear-gradient(to bottom,rgba(0,0,0,0.1) 0%,rgba(0,0,0,0.5) 100%)}
        .race-info{background:var(--surface);border:1px solid var(--border);padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:16px}
        .race-info-left{flex:1;min-width:0}
        .race-info-right{flex-shrink:0;display:flex;align-items:center;gap:12px}
        .race-title{font-family:'Playfair Display',serif;font-style:italic;font-weight:400;font-size:24px;color:var(--text);line-height:1.2;margin-bottom:2px}
        .race-meta{font-size:11px;text-transform:uppercase;letter-spacing:0.08em;color:var(--text2)}
        .post-card {
            background: var(--surface);
            border: 1px solid var(--border);
            transition: border-color 0.2s;
        }
        .post-card:hover {
            border-color: var(--accent);
        }
        .btn-accent {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 18px; background: var(--accent);
            color: #fff; font-weight: 700; font-size: 0.8rem;
            border: none; cursor: pointer; transition: all 0.2s;
        }
        .btn-accent:hover { opacity: 0.9; }
        .comment-input {
            background: var(--surface); border: 1px solid var(--border);
            padding: 8px 12px; color: var(--text);
            font-size: 0.85rem; outline: none; width: 100%;
            font-family: 'Inter', sans-serif;
        }
        .comment-input:focus { border-color: var(--accent); }

        /* Override Tailwind colors for editorial theme */
        .text-white { color: var(--text); }
        .text-gray-300 { color: var(--text); }
        .text-gray-400 { color: var(--text2); }
        .text-gray-500 { color: var(--text2); }
        .text-gray-600 { color: var(--text3); }
        .text-red-400 { color: var(--accent); }
        .text-\[#ff0077\] { color: var(--accent) !important; }
        .text-\[#ff6a00\] { color: var(--accent) !important; }
        .hover\:text-\[#ff0077\]:hover { color: var(--accent) !important; }
        .hover\:text-\[#ff6a00\]:hover { color: var(--accent) !important; }
        .border-white\/5, .border-white\/10 { border-color: var(--border); }
        .bg-white\/5 { background: var(--surface-muted); }
        .nfs-btn { background: var(--accent); color: #fff; }
        .nfs-btn:hover { opacity: 0.9; box-shadow: none; transform: none; }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/includes/nav.php'; ?>

    <main class="max-w-4xl mx-auto px-4" style="padding-top:80px;padding-bottom:60px">

        <!-- Hero Banner (just image) -->
        <div class="hero" style="height:200px">
            <div class="hero-bg" style="background-image:url('https://images.unsplash.com/photo-1678919225767-c2d4dff33ab4?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fGVufHx8fA%3D%3D')"></div>
            <div class="hero-overlay"></div>
        </div>

        <!-- Page Header (below hero) -->
        <div class="race-info" style="margin-bottom:24px">
            <div class="race-info-left">
                <div class="race-title" style="font-size:24px"><i class="fas fa-newspaper" style="color:var(--accent);margin-right:8px"></i>Paddock News</div>
                <div class="race-meta">Race debriefs, results roundups, and league updates</div>
            </div>
        </div>

        <?php if (empty($posts)): ?>
            <div class="text-center py-12">
                <div class="text-6xl mb-4">📰</div>
                <h2 class="text-2xl font-black mb-2" style="font-family:'Playfair Display',serif;font-style:italic;letter-spacing:0.03em;color:var(--text3)">No News Yet</h2>
                <p class="text-sm" style="color:var(--text2)">Check back after races are completed for post-race debrief and highlights!</p>
            </div>
        <?php else: ?>
            <div class="space-y-5">
                <?php foreach ($posts as $post): ?>
                    <article class="post-card p-6">
                        <div class="mb-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h2 class="text-xl font-black" style="font-family:'Playfair Display',serif;font-style:italic;letter-spacing:0.03em">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </h2>
                                    <?php if ($post['race_name']): ?>
                                        <div class="flex items-center gap-2 text-sm mt-1" style="color:var(--text2)">
                                            <i class="fas fa-flag-checkered" style="color:var(--accent)"></i>
                                            <span><?php echo htmlspecialchars($post['race_name'] . ' – ' . $post['country']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <time class="text-xs whitespace-nowrap" style="color:var(--text3)">
                                    <?php echo date('M d, Y H:i', strtotime($post['created_at'])); ?>
                                </time>
                            </div>
                            <div class="text-xs mt-1" style="color:var(--text3)">
                                By <strong style="color:var(--text2)"><?php echo htmlspecialchars($post['username'] ?? 'System'); ?></strong>
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
                                $content = preg_replace('/^(\s*(?:🥇|🥈|🥉|\d+\.|P\d+:?)\s*)([a-zA-Z0-9_\s]+?)(\s*-|\s*$)/m', '<strong style="color:var(--accent)">$1$2</strong>$3', $content);
                                $content = preg_replace('/@([a-zA-Z0-9_]+)/', '<strong style="color:var(--accent)">@$1</strong>', $content);
                                $content = preg_replace('/\*\*(.*?)\*\*/', '<strong class="text-white">$1</strong>', $content);
                                $content = preg_replace('/__(.*?)__/', '<em style="color:var(--accent)">$1</em>', $content);

                                echo '<div class="text-sm leading-relaxed space-y-2" style="color:var(--text)">';
                                echo $content;
                                echo '</div>';
                            }
                            ?>
                        </div>

                        <div class="pt-4 flex justify-between items-center" style="border-top:1px solid var(--border)">
                            <div class="flex gap-4 text-sm">
                                <button onclick="toggleLike(<?php echo $post['id']; ?>)" 
                                        class="transition flex items-center gap-1 <?php echo $post['user_liked'] ? 'text-red-400' : ''; ?>" 
                                        id="like-btn-<?php echo $post['id']; ?>" style="color:<?php echo $post['user_liked'] ? 'var(--accent)' : 'var(--text2)'; ?>">
                                    <i class="fas fa-heart"></i>
                                    <span id="like-count-<?php echo $post['id']; ?>"><?php echo $post['like_count']; ?></span>
                                </button>
                                <button onclick="toggleComments(<?php echo $post['id']; ?>)" 
                                        class="transition flex items-center gap-1" style="color:var(--text2)">
                                    <i class="fas fa-comment"></i>
                                    <span id="comment-count-<?php echo $post['id']; ?>"><?php echo $post['comment_count']; ?></span>
                                </button>
                            </div>
                        </div>

                        <div id="comments-<?php echo $post['id']; ?>" class="hidden mt-4 pt-4" style="border-top:1px solid var(--border)">
                            <div class="space-y-3 mb-4" id="comments-list-<?php echo $post['id']; ?>"></div>
                            <div class="flex gap-2">
                                <input type="text" id="comment-input-<?php echo $post['id']; ?>" 
                                       placeholder="Write a comment..." 
                                       class="comment-input">
                                <button onclick="addComment(<?php echo $post['id']; ?>)" 
                                        class="btn-accent text-xs px-4 py-2 whitespace-nowrap">
                                    Post
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer class="mt-12 py-6 text-center" style="border-top:1px solid var(--border)">
        <p class="text-sm mb-2" style="color:var(--text2)">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        <p class="text-xs" style="color:var(--text3)">
            Powered by <a href="https://www.scanerrific.com" target="_blank" style="color:var(--accent);font-weight:600">Scanerrific</a>
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
                    el.className = 'p-3';
                    el.style.cssText = 'background:var(--surface-muted);border:1px solid var(--border-light)';
                    el.innerHTML = '<div class="flex items-center gap-2 mb-1"><span class="font-semibold text-sm" style="color:var(--accent)">' + c.username + '</span><span class="text-xs" style="color:var(--text3)">' + c.created_at + '</span></div><p class="text-sm" style="color:var(--text)">' + c.comment + '</p>';
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
