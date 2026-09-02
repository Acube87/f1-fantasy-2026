<?php
/**
 * Admin Tool: Reformat all posts to the standardised Scanerrific structure.
 *
 * Replaces the raw/mixed HTML of every existing post with a clean structured
 * template (kicker, lede, numbered sections, bullets, callouts) that matches
 * the .post-body stylesheet in index.php.
 *
 * Idempotent: skips any post whose content already contains the "pb-kicker"
 * marker used by the new format.
 *
 * CLI:
 *   php admin/reformat-posts.php            (preview)
 *   php admin/reformat-posts.php --apply    (apply)
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

// ---- Full replacement content per post (matched by id and/or title keyword) ----
$rewrites = [];

function rewrite($id, $keywords, $title, $content) {
    return ['id' => $id, 'match' => $keywords, 'title' => $title, 'content' => $content];
}

$styled = "<div class='post-body'>";

$rewrites[] = rewrite(2, ['Australian Grand Prix'],
    'Australian Grand Prix — Race Debrief',
    $styled
        . "<div class='pb-kicker'>Race Debrief &middot; Round 1 &middot; Australia</div>"
        . "<div class='pb-title'>Australian Grand Prix — The Verdict</div>"
        . "<div class='pb-lede'><strong>George Russell</strong> took the chequered flag victory, with <strong>Mercedes</strong> bringing the constructors' energy to Albert Park.</div>"
        . "<div class='pb-section'><div class='pb-label'><span class='num'>01</span> Your predictions — the real race</div>"
        . "<p>1 of you made predictions for the season opener. Solid start &mdash; the grid is live.</p></div>"
        . "<div class='pb-section'><div class='pb-label'><span class='num'>02</span> Podium (top 3 scorers)</div>"
        . "<ul>"
        . "<li><strong>P1 &middot; testuser</strong> &mdash; 0 points</li>"
        . "</ul></div>"
        . "<div class='pb-section'><div class='pb-label'><span class='num'>03</span> Up next</div>"
        . "<p>We head to <strong>China</strong> for the <strong>Chinese Grand Prix</strong> &mdash; get your lineups in before the lights go out.</p></div>"
        . "<div class='pb-quote'>&ldquo;Data is beautiful. Predictions are educated guesses. See you next race.&rdquo;</div>"
        . '</div>');

$rewrites[] = rewrite(4, ['Dutch GP Lineup', 'Dutch Grand Prix'],
    'Dutch GP Lineup Change & App Update — Action Required',
    $styled
        . "<div class='pb-kicker'>Dutch GP &middot; Zandvoort &middot; Lineup</div>"
        . "<div class='pb-title'>Hadjar out, Lawson in at Red Bull</div>"
        . "<div class='pb-lede'>Red Bull confirmed <strong>Isack Hadjar</strong> is out of the Dutch GP with a wrist injury. <strong>Liam Lawson</strong> moves up from Racing Bulls to replace him, with <strong>Yuki Tsunoda</strong> filling the Racing Bulls seat.</div>"
        . "<div class='pb-section'><div class='pb-label'><span class='num'>01</span> What we did in the app</div><ul>"
        . "<li><strong>Lawson</strong> is now listed under Red Bull; <strong>Tsunoda</strong> under Racing Bulls.</li>"
        . "<li><strong>Hadjar is removed from the pick list</strong> &mdash; no wasted slots.</li>"
        . "<li>Anyone who had picked Hadjar was <strong>auto-substituted to Lawson at the exact same position</strong> &mdash; nobody loses a pick.</li>"
        . "<li>Picks that already included Lawson keep Lawson; results follow the driver.</li>"
        . "<li>Roster is the <strong>full 22-car grid</strong> &mdash; everyone predicts the same field.</li>"
        . "</ul></div>"
        . "<div class='pb-callout'><strong>Action required before Friday 23:59 UK.</strong> If you picked Hadjar, confirm your grid now shows <strong>Lawson</strong>. Affected users may be at 21 picks &mdash; add a 22nd driver.</div>"
        . "<div class='pb-medium'>No scoring changes &mdash; scoring runs normally after the race.</div>"
        . "<div class='pb-quote'>&ldquo;Fair play, sharp picks. See you at Zandvoort.&rdquo;</div>"
        . '</div>');

// App Update post (was a heavy dark-theme layout).
$rewrites[] = rewrite(3, ['App Update v2.1'],
    'App Update v2.1 — Score Corrections & New Features',
    $styled
        . "<div class='pb-kicker'>App Update &middot; v2.1</div>"
        . "<div class='pb-title'>Scores corrected, double-points shipped</div>"
        . "<div class='pb-callout'><strong>Constructor Bonus fixed &mdash; scores retroactively corrected.</strong> A bug meant the Constructor Bonus (+5 pts) was silently not awarded to anyone. Both the Australian GP and Chinese GP scores have been fully recalculated &mdash; with the China GP 2&times; multiplier the bonus is now worth +10 pts there.</div>"
        . "<div class='pb-section'><div class='pb-label'><span class='num'>01</span> Also shipped this session</div><ul>"
        . "<li><strong>Double points engine</strong> &mdash; China GP 2&times; multiplier correctly applied everywhere.</li>"
        . "<li><strong>Race updates page redesign</strong> &mdash; live telemetry, submission tracker, season progress and post-race debrief.</li>"
        . "<li><strong>Dashboard auto-advances</strong> &mdash; switches to the next race the moment results deploy.</li>"
        . "<li><strong>Maintenance mode</strong> &mdash; locks all users during post-race score processing.</li>"
        . "</ul></div>"
        . "<div class='pb-quote'>&ldquo;Scores are live and correct. Japan is up next &mdash; get your grids locked in.&rdquo;</div>"
        . '</div>');

// ---- Load current posts ----
$posts = $db->query("SELECT id, title, content FROM posts ORDER BY id")->fetch_all(MYSQLI_ASSOC);

$changes = [];
foreach ($posts as $post) {
    $id = (int)$post['id'];

    // Find a matching rewrite by id, else by title keyword.
    $matched = null;
    foreach ($rewrites as $rw) {
        if ($rw['id'] === $id) { $matched = $rw; break; }
    }
    if (!$matched) {
        foreach ($rewrites as $rw) {
            foreach ($rw['match'] as $kw) {
                if (stripos($post['title'], $kw) !== false || stripos($post['content'], $kw) !== false) {
                    $matched = $rw; break 2;
                }
            }
        }
    }
    if (!$matched) {
        continue; // leave unknown/new posts untouched
    }
    // Idempotence guard: skip if already in new format
    if (strpos($post['content'], "pb-kicker") !== false) {
        $changes[] = ['id' => $id, 'title' => $post['title'], 'action' => 'skip (already formatted)'];
        continue;
    }
    if ($apply) {
        $stmt = $db->prepare('UPDATE posts SET title = ?, content = ? WHERE id = ?');
        $stmt->bind_param('ssi', $matched['title'], $matched['content'], $id);
        $stmt->execute();
    }
    $changed = $post['title'] !== $matched['title'];
    $changes[] = ['id' => $id, 'title' => $matched['title'], 'action' => $apply ? ($changed ? 'updated (title+content)' : 'content reformatted') : 'would reformat'];
}

if ($isCli) {
    if (!$apply) { echo "PREVIEW: reformat existing posts to Scanerrific structure.\n"; }
    else { echo "APPLIED: posts reformatted.\n"; }
    foreach ($changes as $c) {
        echo "  #{$c['id']} [{$c['action']}] {$c['title']}\n";
    }
    if (!$apply) echo "Run again with --apply to apply.\n";
    exit(0);
}

$isDirty = false;
foreach ($changes as $c) { if ($c['action'] !== 'skip (already formatted)') { $isDirty = true; } }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reformat Posts - Race Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/gaming-style.css">
</head>
<body class="gaming-theme text-gray-200 min-h-screen flex items-center justify-center p-8">
    <div class="max-w-2xl w-full">
        <div class="mb-8">
            <a href="race-control.php" class="text-gray-400 hover:text-white text-sm transition"><i class="fas fa-arrow-left mr-2"></i>Back to Race Control</a>
        </div>
        <div class="g-card p-8 rounded-[2rem] border-t-4 border-t-purple-500">
            <h1 class="text-3xl font-black text-white italic uppercase mb-2">✏️ Reformat Posts</h1>
            <p class="text-gray-400 text-sm mb-6">Rewrites every existing post into the standardised Scanerrific structure (kicker, lede, numbered sections, bullets, callouts).</p>

            <?php if ($apply): ?>
            <div class="mb-6 p-4 rounded-xl border bg-green-500/10 border-green-500/30 text-green-400">
                <p class="font-bold text-sm">✅ Posts reformatted to the standard structure.</p>
            </div>
            <?php endif; ?>

            <div class="mb-6">
                <table class="w-full text-sm">
                    <thead><tr class="text-gray-500 text-xs"><th class="text-left pb-2">Post</th><th class="pb-2">Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($changes as $c): ?>
                    <tr class="border-t border-white/5">
                        <td class="py-2 text-white font-bold">#<?php echo $c['id']; ?> · <?php echo htmlspecialchars($c['title']); ?></td>
                        <td class="py-2 <?php echo strpos($c['action'],'skip')!==false ? 'text-gray-500' : 'text-purple-400'; ?>"><?php echo $c['action']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <a href="?apply=1" onclick="return confirm('Reformat all existing posts to the standard Scanerrific structure?');"
               class="block w-full text-center py-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-black text-lg rounded-2xl transition">
                <i class="fas fa-wand-magic-sparkles"></i> REFORMAT ALL POSTS
            </a>
        </div>
    </div>
</body>
</html>