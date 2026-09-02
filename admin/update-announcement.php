<?php
/**
 * Admin Tool: Publish the official Italian GP (Monza) lineup briefing.
 *
 * Replaces the existing announcement post for the Italian GP with the full
 * briefing (what changed + actions users must take). Falls back to creating
 * a new post if the announcement isn't found.
 *
 * CLI:
 *   php admin/update-announcement.php            (preview)
 *   php admin/update-announcement.php --apply    (apply)
 */
require_once __DIR__ . '/../config.php';

$isCli = (PHP_SAPI === 'cli');

if ($isCli) {
    $apply = in_array('--apply', $argv, true);
} else {
    require_once __DIR__ . '/../includes/auth.php';
    $user = getCurrentUser();
    if (!$user || empty($user['is_admin'])) {
        die('Unauthorized');
    }
    $apply = isset($_GET['apply']) || isset($_POST['apply']);
}

$db = getDB();

$title = '🏁 Italian GP Lineup — Hadjar OUT for Monza, Lawson in at Red Bull';

$content = '<div class="post-body">'
    . '<div class="pb-kicker">Italian GP &middot; Monza &middot; Lineup</div>'
    . '<div class="pb-title">Hadjar out, Lawson in at Red Bull</div>'
    . '<div class="pb-lede">Red Bull confirmed <strong>Isack Hadjar</strong> will miss the Italian Grand Prix as he continues to recover from his wrist injury &mdash; with <strong>Liam Lawson</strong> stepping up for a second consecutive race.</div>'
    . '<div class="pb-section"><div class="pb-label"><span class="num">01</span> What happened</div>'
    . '<p>Hadjar sustained the injury during the summer break and has not recovered in time for Monza. With one seat open alongside <strong>Max Verstappen</strong>, <strong>Lawson</strong> moves up from Racing Bulls, while <strong>Yuki Tsunoda</strong> fills the Racing Bulls seat for another chance to impress.</p></div>'
    . '<div class="pb-section"><div class="pb-label"><span class="num">02</span> What we did in the app</div>'
    . '<ul>'
    . '<li><strong>Lawson</strong> is now listed under Red Bull; <strong>Tsunoda</strong> under Racing Bulls.</li>'
    . '<li><strong>Hadjar is removed from the pick list</strong> so nobody wastes a slot on an unavailable driver.</li>'
    . '<li>Anyone who had picked Hadjar was <strong>auto-substituted to Lawson at the exact same position</strong> &mdash; nobody loses a pick.</li>'
    . '<li>Picks that already included Lawson keep Lawson; results follow the driver.</li>'
    . '<li>The roster stays a <strong>full 22-car grid</strong> &mdash; everyone predicts the same field.</li>'
    . '</ul></div>'
    . '<div class="pb-callout"><strong>Action required before Friday 23:59 UK.</strong> If you had picked Hadjar, confirm your grid now shows <strong>Lawson</strong> in that spot. Affected users may be at 21 picks &mdash; add a 22nd driver to complete your grid.</div>'
    . '<div class="pb-medium">No scoring changes &mdash; scoring runs normally after the race.</div>'
    . '<div class="pb-quote">&ldquo;Fair play, sharp picks. See you at Monza.&rdquo;</div>'
    . '</div>';

// Resolve Italian GP race id by name (not hardcoded).
$stmt = $db->prepare("SELECT id FROM races WHERE race_name = 'Italian Grand Prix' AND status = 'upcoming' ORDER BY race_date LIMIT 1");
$stmt->execute();
$race = $stmt->get_result()->fetch_assoc();
$raceId = $race ? (int)$race['id'] : 0;

// Find the existing announcement post (race-linked, author Race Control).
$existing = null;
if ($raceId) {
    $stmt = $db->prepare("SELECT id FROM posts WHERE race_id = ? AND author_id = 3 ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('i', $raceId);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
}

$mode = '';
if ($apply && $raceId) {
    if ($existing) {
        $stmt = $db->prepare('UPDATE posts SET title = ?, content = ? WHERE id = ?');
        $stmt->bind_param('ssi', $title, $content, $existing['id']);
        $stmt->execute();
        $mode = 'updated';
    } else {
        $stmt = $db->prepare('INSERT INTO posts (race_id, title, content, author_id, is_manual) VALUES (?, ?, ?, 3, 1)');
        $stmt->bind_param('iss', $raceId, $title, $content);
        $stmt->execute();
        $mode = 'created';
    }
}

if ($isCli) {
    if (!$raceId) {
        echo "ERROR: Italian Grand Prix not found.\n";
        exit(1);
    }
    if (!$apply) {
        echo "PREVIEW: would " . ($existing ? "replace post #{$existing['id']}" : "create a new announcement") . " for the Italian GP.\n";
        echo "Title: $title\n";
        echo "Run again with --apply to apply.\n";
        exit(0);
    }
    echo "APPLIED ($mode): $title\n";
    exit(0);
}

$total = (int)$db->query("SELECT COUNT(*) c FROM posts")->fetch_assoc()['c'];
$latestTitle = $db->query("SELECT title FROM posts ORDER BY id DESC LIMIT 1")->fetch_assoc()['title'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publish Italian GP Briefing - Race Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="gaming-theme text-gray-200 min-h-screen flex items-center justify-center p-8">
    <div class="max-w-2xl w-full">
        <div class="mb-8">
            <a href="race-control.php" class="text-gray-400 hover:text-white text-sm transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to Race Control
            </a>
        </div>

        <div class="g-card p-8 rounded-[2rem] border-t-4 border-t-purple-500">
            <h1 class="text-3xl font-black text-white italic uppercase mb-2">
                📣 Publish Italian GP Briefing
            </h1>
            <p class="text-gray-400 text-sm mb-6">
                Replaces the Italian GP announcement post in the updates feed with the full
                briefing (lineup changes + actions users must take before the deadline).
            </p>

            <?php if ($mode): ?>
            <div class="mb-6 p-4 rounded-xl border bg-green-500/10 border-green-500/30 text-green-400">
                <p class="font-bold text-sm">✅ Announcement <?php echo $mode; ?> — it's live at the top of the updates feed.</p>
            </div>
            <?php endif; ?>

            <div class="mb-6 p-4 rounded-xl bg-black/30 border border-white/10">
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-2">Title</p>
                <p class="text-white font-bold text-sm"><?php echo htmlspecialchars($title); ?></p>

                <p class="text-xs text-gray-400 uppercase tracking-widest mt-4 mb-2">Content preview</p>
                <div class="text-xs text-gray-300 leading-relaxed">
                    <?php echo nl2br(htmlspecialchars(strip_tags(str_replace(['</div>', '</strong>', '</p>'], "\n", $content)))); ?>
                </div>
            </div>

            <a href="?apply=1" onclick="return confirm('Publish this announcement now?');"
               class="block w-full text-center py-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-black text-lg rounded-2xl transition transform hover:scale-[1.02]">
                <i class="fas fa-paper-plane"></i> PUBLISH ANNOUNCEMENT
            </a>
        </div>
    </div>
</body>
</html>