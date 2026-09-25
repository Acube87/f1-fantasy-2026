<?php
/**
 * Admin Tool: Publish the official Azerbaijan GP (Baku) lineup briefing.
 *
 * Replaces the existing announcement post for the Azerbaijan GP with the full
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

$authorId = 0;
if (!$isCli) {
    $authorId = (int)$user['id'];
} else {
    $stmt = $db->prepare("SELECT id FROM users WHERE username = 'Angrycube' LIMIT 1");
    $stmt->execute();
    $authorId = (int)($stmt->get_result()->fetch_assoc()['id'] ?? 0);
}
if (!$authorId) {
    die('ERROR: could not resolve an author user id.');
}

$title = 'Azerbaijan GP Lineup — Hadjar Returns to Red Bull';

$content = '<div class="post-body">'
    . '<div class="pb-kicker">Azerbaijan GP &middot; Baku &middot; Lineup</div>'
    . '<div class="pb-title">Hadjar is back in the Red Bull seat</div>'
    . '<div class="pb-lede"><strong>Isack Hadjar</strong> has been declared fit and returns from his wrist injury this weekend, re-joining <strong>Max Verstappen</strong> at Red Bull for the Azerbaijan Grand Prix.</div>'
    . '<div class="pb-section"><div class="pb-label"><span class="num">01</span> What changed</div>'
    . '<p>Hadjar missed the Dutch, Italian and Spanish Grands Prix after injuring his wrist in training during the summer break. He has now completed his rehabilitation and been given the all-clear for Baku&rsquo;s high-downforce street circuit.</p>'
    . '<p>With Hadjar back, <strong>Liam Lawson</strong> returns to Racing Bulls after standing in at Red Bull, and <strong>Yuki Tsunoda</strong> steps back down to reserve duties. Red Bull thanked Lawson for covering three races.</p></div>'
    . '<div class="pb-section"><div class="pb-label"><span class="num">02</span> What we did in the app</div>'
    . '<ul>'
    . '<li><strong>Hadjar</strong> is listed under Red Bull Racing and is available to pick again.</li>'
    . '<li><strong>Lawson</strong> is listed under Racing Bulls; <strong>Tsunoda</strong> drops to reserve and off the pick list.</li>'
    . '<li>Any pick you had for <strong>Tsunoda</strong> has been moved to <strong>Hadjar</strong> in the same position.</li>'
    . '<li>If you had already picked <strong>Hadjar</strong> yourself, that pick is kept and the vacated slot is simply cleared.</li>'
    . '<li><strong>Lawson picks were never touched</strong> &mdash; his results follow the driver either way.</li>'
    . '<li>The roster is a <strong>full 22-car grid</strong> &mdash; everyone predicts the same field.</li>'
    . '</ul></div>'
    . '<div class="pb-callout"><strong>Action required before midnight UK time tonight (Friday).</strong> Baku is a Saturday race, so the prediction window shuts at midnight. Check your grid, and if a slot was cleared, add a driver to get back to a full 22.</div>'
    . '<div class="pb-medium">No scoring changes &mdash; completed results are unaffected and scoring runs normally after the race.</div>'
    . '<div class="pb-quote">&ldquo;Welcome back, Isack. Baku is a low-grip, high-commitment track &mdash; good luck to everyone.&rdquo;</div>'
    . '</div>';

// Resolve Azerbaijan GP race id by name (not hardcoded).
$stmt = $db->prepare("SELECT id FROM races WHERE race_name = 'Azerbaijan Grand Prix' ORDER BY race_date DESC LIMIT 1");
$stmt->execute();
$race = $stmt->get_result()->fetch_assoc();
$raceId = $race ? (int)$race['id'] : 0;

// Find the existing announcement post (race-linked, same author).
$existing = null;
if ($raceId) {
    $stmt = $db->prepare("SELECT id FROM posts WHERE race_id = ? AND author_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('ii', $raceId, $authorId);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
}

$mode = '';
$error = null;
if ($apply && $raceId) {
    if ($existing) {
        $stmt = $db->prepare('UPDATE posts SET title = ?, content = ? WHERE id = ?');
        $stmt->bind_param('ssi', $title, $content, $existing['id']);
        if (!$stmt->execute()) {
            $error = $db->error;
        } else {
            $mode = 'updated';
        }
    } else {
        $stmt = $db->prepare('INSERT INTO posts (race_id, title, content, author_id, is_manual) VALUES (?, ?, ?, ?, 1)');
        $stmt->bind_param('issi', $raceId, $title, $content, $authorId);
        if (!$stmt->execute()) {
            $error = $db->error;
        } else {
            $mode = 'created';
        }
    }
} elseif ($apply && !$raceId) {
    $error = 'Azerbaijan Grand Prix row not found, so there is no race to attach the post to.';
}

if ($isCli) {
    if (!$raceId) {
        echo "ERROR: Azerbaijan Grand Prix not found.\n";
        exit(1);
    }
    if (!$apply) {
        echo "PREVIEW: would " . ($existing ? "replace post #{$existing['id']}" : "create a new announcement") . " for the Azerbaijan GP.\n";
        echo "Title: $title\n";
        echo "Run again with --apply to apply.\n";
        exit(0);
    }
    if ($error) {
        echo "ERROR: $error\n";
        exit(1);
    }
    echo "APPLIED ($mode): $title\n";
    $check = $db->query("SELECT p.id, p.race_id, p.author_id, p.title FROM posts p ORDER BY p.id DESC LIMIT 1")->fetch_assoc();
    echo "VERIFIED: post #{$check['id']} race_id={$check['race_id']} author_id={$check['author_id']}\n";
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
    <title>Publish Azerbaijan GP Briefing - Race Control</title>
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
                📣 Publish Azerbaijan GP Briefing
            </h1>
            <p class="text-gray-400 text-sm mb-6">
                Replaces the Azerbaijan GP announcement post in the updates feed with the full
                briefing (lineup changes + actions users must take before the deadline).
            </p>

            <?php if ($error): ?>
            <div class="mb-6 p-4 rounded-xl border bg-red-500/10 border-red-500/30 text-red-400">
                <p class="font-bold text-sm">❌ Publish failed — nothing was saved.</p>
                <p class="text-xs mt-1 text-red-300/80"><?php echo htmlspecialchars($error); ?></p>
            </div>
            <?php endif; ?>

            <?php if ($mode): ?>
            <?php
            $verify = $db->query("SELECT id, race_id, author_id, created_at FROM posts ORDER BY id DESC LIMIT 1")->fetch_assoc();
            ?>
            <div class="mb-6 p-4 rounded-xl border bg-green-500/10 border-green-500/30 text-green-400">
                <p class="font-bold text-sm">✅ Announcement <?php echo $mode; ?> — it's live at the top of the updates feed.</p>
                <p class="text-xs mt-1 text-green-300/80">
                    Post #<?php echo (int)$verify['id']; ?> &middot; race #<?php echo (int)$verify['race_id']; ?>
                    &middot; author #<?php echo (int)$verify['author_id']; ?>
                </p>
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