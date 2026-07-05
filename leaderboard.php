<?php
require_once __DIR__ . '/includes/maintenance-gate.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/avatars.php';

$leaderboard = getLeaderboard(100);
$user = getCurrentUser();
$currentUser = $user;

// Check if we're viewing a specific race
$selectedRaceId = isset($_GET['race_id']) ? intval($_GET['race_id']) : null;

// Get all races for the dropdown
$db = getDB();
$racesQuery = $db->query("SELECT id, country, race_date FROM races ORDER BY race_date DESC");
$allRaces = $racesQuery->fetch_all(MYSQLI_ASSOC);

// Determine which leaderboard to show
$showRaceLeaderboard = $selectedRaceId && $selectedRaceId > 0;

if ($showRaceLeaderboard) {
    $leaderboard = getRaceLeaderboard($selectedRaceId, 100);
    $selectedRaceStmt = $db->prepare("SELECT id, country, race_date FROM races WHERE id = ?");
    $selectedRaceStmt->bind_param("i", $selectedRaceId);
    $selectedRaceStmt->execute();
    $selectedRaceInfo = $selectedRaceStmt->get_result()->fetch_assoc();
} else {
    $leaderboard = getLeaderboard(100);
    $selectedRaceInfo = null;
}

// Find latest deadline-passed race for peek links
$now = new DateTime('now', new DateTimeZone('UTC'));
$latestRaceId = 1;
$candidatesQuery = $db->query("SELECT id, race_date FROM races ORDER BY race_date DESC LIMIT 20");
$candidates = $candidatesQuery->fetch_all(MYSQLI_ASSOC);
foreach ($candidates as $candidate) {
    $candidateDeadline = getPredictionDeadline($candidate['race_date']);
    if ($now >= $candidateDeadline) {
        $latestRaceId = (int)$candidate['id'];
        break;
    }
}

$latestRaceForPeek = null;
$latestRacePeekQuery = $db->prepare("SELECT id, country, race_date FROM races WHERE id = ?");
$latestRacePeekQuery->bind_param("i", $latestRaceId);
$latestRacePeekQuery->execute();
$latestRaceForPeek = $latestRacePeekQuery->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard — <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Bebas+Neue&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/gaming-style.css">
    <style>
        :root {
            --f1-red: #ff0077;
            --f1-text-sec: #666666;
            --bg-primary: #0A0A0A;
            --text-primary: #FFFFFF;
        }
        .f1-number {
            font-family: 'Bebas Neue', 'Titillium Web', sans-serif;
            font-weight: 900;
            font-style: italic;
            letter-spacing: -0.02em;
        }
        .peak-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10px;
            background: rgba(255,0,119,0.1);
            color: var(--f1-red);
            border: 1px solid rgba(255,0,119,0.2);
            padding: 2px 10px;
            border-radius: 999px;
            text-decoration: none;
            transition: all 0.2s;
        }
        .peak-btn:hover {
            background: rgba(255,0,119,0.2);
            border-color: rgba(255,0,119,0.4);
        }
        table a { color: inherit; }
    </style>
</head>
<body style="background:var(--bg-primary);color:var(--text-primary);min-height:100vh;font-family:'Inter',sans-serif;">

    <?php require_once __DIR__ . '/includes/nav.php'; ?>

    <main class="page">
        <div class="container" style="max-width:1000px;margin:0 auto;padding:100px 20px 40px;">

        <div style="text-align:center;margin-bottom:2rem;">
            <h1 style="font-size:2.5rem;font-weight:900;font-family:'Bebas Neue',sans-serif;letter-spacing:-0.02em;margin-bottom:0.25rem;line-height:1.1;">
                <?php echo $showRaceLeaderboard ? htmlspecialchars($selectedRaceInfo['country'] ?? 'Race') : 'Championship'; ?>
                <span style="color:var(--f1-red);"><?php echo $showRaceLeaderboard ? 'GP' : 'Standings'; ?></span>
            </h1>
            <p style="color:var(--f1-text-sec);font-size:13px;">
                <?php echo $showRaceLeaderboard ? 'Results for ' . htmlspecialchars($selectedRaceInfo['country'] ?? '') . ' GP' : 'The fastest predictors in the paddock'; ?>
            </p>
        </div>

        <!-- Race Selector -->
        <div style="display:flex;justify-content:center;margin-bottom:2rem;">
            <form method="get">
                <select name="race_id" onchange="this.form.submit()" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);color:var(--text-primary);padding:10px 16px;border-radius:10px;font-weight:600;font-size:13px;outline:none;cursor:pointer;min-width:280px;appearance:none">
                    <option value="" style="background:#1a1a1a">🏆 Overall Standings</option>
                    <?php foreach ($allRaces as $race): ?>
                        <?php $raceDate = new DateTime($race['race_date'], new DateTimeZone('UTC')); ?>
                        <option value="<?php echo $race['id']; ?>" style="background:#1a1a1a" <?php echo ($selectedRaceId == $race['id']) ? 'selected' : ''; ?>>
                            🏁 <?php echo htmlspecialchars($race['country']); ?> GP — <?php echo $raceDate->format('F j, Y'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <!-- Top 3 Podium Cards -->
        <?php if (!empty($leaderboard) && count($leaderboard) >= 3): ?>
        <div style="display:grid;grid-template-columns:1fr 1.2fr 1fr;gap:16px;align-items:end;margin-bottom:2.5rem;">

            <!-- 2nd Place -->
            <div style="background:var(--bg-primary);border:1px solid rgba(255,255,255,0.08);border-radius:16px;padding:24px 16px;text-align:center;border-top:4px solid #a0a0a0;">
                <div style="font-size:2.5rem;margin-bottom:8px;">🥈</div>
                <h3 style="font-weight:800;font-size:1rem;color:var(--text-primary);margin-bottom:4px;"><?php echo htmlspecialchars($leaderboard[1]['username']); ?></h3>
                <div style="font-size:1.8rem;font-weight:900;font-family:'Bebas Neue',sans-serif;color:#a0a0a0;letter-spacing:-0.02em;"><?php echo number_format($leaderboard[1]['total_points'] ?? 0); ?></div>
                <div style="font-size:9px;color:var(--f1-text-sec);text-transform:uppercase;letter-spacing:0.08em;font-weight:600;">Points</div>
            </div>

            <!-- 1st Place -->
            <div style="background:var(--bg-primary);border:1px solid rgba(255,215,0,0.2);border-radius:16px;padding:32px 20px;text-align:center;border-top:4px solid #ffd700;transform:scale(1.05);box-shadow:0 8px 32px rgba(255,215,0,0.08);">
                <div style="font-size:3rem;margin-bottom:8px;">👑</div>
                <h3 style="font-weight:900;font-size:1.2rem;color:var(--text-primary);margin-bottom:4px;"><?php echo htmlspecialchars($leaderboard[0]['username']); ?></h3>
                <div style="font-size:2.2rem;font-weight:900;font-family:'Bebas Neue',sans-serif;color:#ffd700;letter-spacing:-0.02em;"><?php echo number_format($leaderboard[0]['total_points'] ?? 0); ?></div>
                <div style="font-size:9px;color:var(--f1-text-sec);text-transform:uppercase;letter-spacing:0.08em;font-weight:600;">Points</div>
                <?php if (!$showRaceLeaderboard): ?>
                    <div style="margin-top:8px;font-size:9px;font-weight:700;background:rgba(255,215,0,0.15);color:#ffd700;border:1px solid rgba(255,215,0,0.2);padding:3px 12px;border-radius:999px;display:inline-block;text-transform:uppercase;letter-spacing:0.06em;">Season Leader</div>
                <?php endif; ?>
            </div>

            <!-- 3rd Place -->
            <div style="background:var(--bg-primary);border:1px solid rgba(255,255,255,0.08);border-radius:16px;padding:24px 16px;text-align:center;border-top:4px solid #cd7f32;">
                <div style="font-size:2.5rem;margin-bottom:8px;">🥉</div>
                <h3 style="font-weight:800;font-size:1rem;color:var(--text-primary);margin-bottom:4px;"><?php echo htmlspecialchars($leaderboard[2]['username']); ?></h3>
                <div style="font-size:1.8rem;font-weight:900;font-family:'Bebas Neue',sans-serif;color:#cd7f32;letter-spacing:-0.02em;"><?php echo number_format($leaderboard[2]['total_points'] ?? 0); ?></div>
                <div style="font-size:9px;color:var(--f1-text-sec);text-transform:uppercase;letter-spacing:0.08em;font-weight:600;">Points</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Full Table -->
        <div style="background:var(--bg-primary);border:1px solid rgba(255,255,255,0.06);border-radius:16px;overflow:hidden;">
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.06);background:rgba(255,255,255,0.02);">
                            <th style="padding:12px 14px;text-align:left;font-size:9px;font-weight:700;color:var(--f1-text-sec);text-transform:uppercase;letter-spacing:0.1em;">Rank</th>
                            <th style="padding:12px 14px;text-align:left;font-size:9px;font-weight:700;color:var(--f1-text-sec);text-transform:uppercase;letter-spacing:0.1em;">Player</th>
                            <?php if ($showRaceLeaderboard): ?>
                                <th style="padding:12px 14px;text-align:right;font-size:9px;font-weight:700;color:var(--f1-text-sec);text-transform:uppercase;letter-spacing:0.1em;">Driver</th>
                                <th style="padding:12px 14px;text-align:right;font-size:9px;font-weight:700;color:var(--f1-text-sec);text-transform:uppercase;letter-spacing:0.1em;">Const</th>
                                <th style="padding:12px 14px;text-align:right;font-size:9px;font-weight:700;color:var(--f1-text-sec);text-transform:uppercase;letter-spacing:0.1em;">Top 3</th>
                                <th style="padding:12px 14px;text-align:right;font-size:9px;font-weight:700;color:var(--f1-text-sec);text-transform:uppercase;letter-spacing:0.1em;">Total</th>
                            <?php else: ?>
                                <th style="padding:12px 14px;text-align:right;font-size:9px;font-weight:700;color:var(--f1-text-sec);text-transform:uppercase;letter-spacing:0.1em;">Points</th>
                                <th style="padding:12px 14px;text-align:right;font-size:9px;font-weight:700;color:var(--f1-text-sec);text-transform:uppercase;letter-spacing:0.1em;">Races</th>
                                <th style="padding:12px 14px;text-align:right;font-size:9px;font-weight:700;color:var(--f1-text-sec);text-transform:uppercase;letter-spacing:0.1em;">Avg</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody style="divide-y:1px solid rgba(255,255,255,0.04);">
                        <?php
                        $rank = 1;
                        foreach ($leaderboard as $entry):
                            $avgPoints = ($entry['races_participated'] ?? 0) > 0
                                ? number_format(($entry['total_points'] ?? 0) / $entry['races_participated'], 1)
                                : '0.0';
                            $isMe = ($user && $user['username'] === $entry['username']);
                            $rowBg = $isMe ? 'rgba(255,0,119,0.06)' : ($rank % 2 === 0 ? 'rgba(255,255,255,0.01)' : 'transparent');
                        ?>
                        <tr style="background:<?php echo $rowBg; ?>;transition:background 0.15s;">
                            <td style="padding:10px 14px;">
                                <?php if ($rank === 1): ?>
                                    <span style="font-size:1.1rem;display:inline-flex;align-items:center;gap:4px;"><span style="color:#ffd700">👑</span> <span class="f1-number" style="font-size:1.3rem;color:#ffd700">1</span></span>
                                <?php elseif ($rank === 2): ?>
                                    <span style="font-size:1.1rem;display:inline-flex;align-items:center;gap:4px;"><span style="color:#a0a0a0">🥈</span> <span class="f1-number" style="font-size:1.3rem;color:#a0a0a0">2</span></span>
                                <?php elseif ($rank === 3): ?>
                                    <span style="font-size:1.1rem;display:inline-flex;align-items:center;gap:4px;"><span style="color:#cd7f32">🥉</span> <span class="f1-number" style="font-size:1.3rem;color:#cd7f32">3</span></span>
                                <?php else: ?>
                                    <span class="f1-number" style="font-size:1.1rem;color:var(--f1-text-sec);padding-left:20px;"><?php echo $rank; ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:10px 14px;">
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <img src="<?php echo getAvatarUrl($entry['avatar_style'] ?? 'avataaars', $entry['username']); ?>" style="width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,0.04);flex-shrink:0;">
                                    <div>
                                        <div style="font-weight:700;font-size:13px;color:<?php echo $isMe ? 'var(--f1-red)' : 'var(--text-primary)'; ?>;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                            <?php if (!$isMe): ?>
                                                <a href="view-predictions.php?user_id=<?php echo $entry['id']; ?>&race_id=<?php echo $latestRaceId; ?>">
                                                    <?php echo htmlspecialchars($entry['username']); ?>
                                                </a>
                                                <a href="view-predictions.php?user_id=<?php echo $entry['id']; ?>&race_id=<?php echo $latestRaceId; ?>" class="peak-btn">
                                                    <i class="fas fa-eye"></i> Peek <?php if ($latestRaceForPeek): ?><span style="opacity:0.7;">(<?php echo htmlspecialchars($latestRaceForPeek['country']); ?>)</span><?php endif; ?>
                                                </a>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($entry['username']); ?>
                                            <?php endif; ?>
                                            <?php if ($isMe): ?><span style="font-size:8px;background:var(--f1-red);color:#fff;padding:1px 6px;border-radius:999px;font-weight:700;">YOU</span><?php endif; ?>
                                            <?php
                                            if (isset($entry['badges_data']) && $entry['badges_data']) {
                                                $badges = explode('|', $entry['badges_data']);
                                                $tierColors = [
                                                    'common' => ['#00ff88','rgba(0,255,136,0.15)'],
                                                    'rare' => ['#00e5ff','rgba(0,229,255,0.15)'],
                                                    'epic' => ['#ff0077','rgba(255,0,119,0.15)'],
                                                    'legendary' => ['#ff0077','rgba(255,0,119,0.15)'],
                                                    'special' => ['#ff6a00','rgba(255,106,0,0.15)']
                                                ];
                                                $count = 0;
                                                foreach ($badges as $badgeStr) {
                                                    if ($count >= 8) break;
                                                    $parts = explode(':', $badgeStr);
                                                    if (count($parts) >= 3) {
                                                        $icon = $parts[0];
                                                        $tier = $parts[1];
                                                        $name = $parts[2];
                                                        $tc = $tierColors[$tier ?? 'common'] ?? ['#888','rgba(255,255,255,0.1)'];
                                                        echo '<span style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:4px;background:'.$tc[1].';color:'.$tc[0].';font-size:9px;cursor:help;" title="'.htmlspecialchars($name).'"><i class="fas '.htmlspecialchars($icon).'"></i></span>';
                                                        $count++;
                                                    }
                                                }
                                            }
                                            ?>
                                        </div>
                                        <?php if (!empty($entry['full_name'])): ?>
                                            <div style="font-size:9px;color:var(--f1-text-sec);margin-top:1px;"><?php echo htmlspecialchars($entry['full_name']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <?php if ($showRaceLeaderboard): ?>
                                <td style="padding:10px 14px;text-align:right;font-size:14px;font-weight:700;color:var(--text-primary);font-family:'Bebas Neue',sans-serif;"><?php echo number_format($entry['driver_points'] ?? 0); ?></td>
                                <td style="padding:10px 14px;text-align:right;font-size:14px;font-weight:700;color:var(--f1-red);font-family:'Bebas Neue',sans-serif;"><?php echo number_format($entry['constructor_points'] ?? 0); ?></td>
                                <td style="padding:10px 14px;text-align:right;font-size:14px;font-weight:700;color:#ffd700;font-family:'Bebas Neue',sans-serif;"><?php echo number_format(($entry['top3_bonus'] ?? 0) + ($entry['constructor_top3_bonus'] ?? 0)); ?></td>
                                <td style="padding:10px 14px;text-align:right;font-size:20px;font-weight:900;color:<?php echo $isMe ? 'var(--f1-red)' : '#00ff88'; ?>;font-family:'Bebas Neue',sans-serif;letter-spacing:-0.02em;"><?php echo number_format($entry['total_points'] ?? 0); ?></td>
                            <?php else: ?>
                                <td style="padding:10px 14px;text-align:right;font-size:20px;font-weight:900;color:#00ff88;font-family:'Bebas Neue',sans-serif;letter-spacing:-0.02em;"><?php echo number_format($entry['total_points'] ?? 0); ?></td>
                                <td style="padding:10px 14px;text-align:right;font-size:13px;color:var(--f1-text-sec);"><?php echo $entry['races_participated'] ?? 0; ?></td>
                                <td style="padding:10px 14px;text-align:right;font-size:12px;color:var(--f1-text-sec);"><?php echo $avgPoints; ?></td>
                            <?php endif; ?>
                        </tr>
                        <?php $rank++; endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        </div>
    </main>

    <footer style="margin-top:3rem;border-top:1px solid rgba(255,255,255,0.06);padding:24px;text-align:center;">
        <p style="font-size:11px;color:var(--f1-text-sec);margin-bottom:4px;">&copy; <?php echo date('Y'); ?> PADDOCK PITS</p>
        <p style="font-size:10px;color:rgba(255,255,255,0.3);">Powered by <a href="https://www.scanerrific.com" target="_blank" style="color:var(--f1-red);text-decoration:none;font-weight:600;">Scanerrific</a></p>
    </footer>

<script src="app.js"></script>
</body>
</html>
