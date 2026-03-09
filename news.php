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

// Get all posts with race info
$stmt = $db->prepare("
    SELECT p.id, p.race_id, p.title, p.content, p.created_at, r.race_name, r.country, u.username
    FROM posts p
    LEFT JOIN races r ON p.race_id = r.id
    LEFT JOIN users u ON p.author_id = u.id
    ORDER BY p.created_at DESC
");
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
                            <div class="prose prose-invert max-w-none mb-4">
                                <div class="text-gray-300 whitespace-pre-wrap leading-relaxed">
                                    <?php 
                                    // Simple HTML rendering for the debrief content
                                    $content = htmlspecialchars($post['content']);
                                    // Convert line breaks
                                    $content = nl2br($content);
                                    // Convert simple markdown-style formatting
                                    $content = preg_replace('/\*\*(.*?)\*\*/', '<strong class="text-white">$1</strong>', $content);
                                    $content = preg_replace('/__(.*?)__/', '<em class="text-orange-300">$1</em>', $content);
                                    echo $content;
                                    ?>
                                </div>
                            </div>

                            <!-- Post Footer -->
                            <div class="pt-4 border-t border-white/10 flex justify-between items-center">
                                <div class="flex gap-4 text-sm">
                                    <button class="text-gray-400 hover:text-orange-400 transition flex items-center gap-1">
                                        <i class="fas fa-heart"></i>
                                        <span>Like</span>
                                    </button>
                                    <button class="text-gray-400 hover:text-orange-400 transition flex items-center gap-1">
                                        <i class="fas fa-comment"></i>
                                        <span>Comment</span>
                                    </button>
                                </div>
                                <button class="text-gray-400 hover:text-orange-400 transition">
                                    <i class="fas fa-share"></i>
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
