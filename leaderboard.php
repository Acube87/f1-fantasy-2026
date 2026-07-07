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

// No peek needed — achievements toggle is handled client-side
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Standings — <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,500;1,400;1,500&family=Teko:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root{
          --canvas:#F5F3EF;--bg:#F5F3EF;--bg2:#FAF9F7;--surface:#FFF;--card:#FFF;--card2:#FAF9F7;--surface-muted:#FAF9F7;
          --border:#E8E5E0;--border-light:#F0EDE8;
          --text:#1A1A1A;--text2:#6B6864;--text3:#A09C96;
          --accent:#C41E3A;--accent-soft:#F5E6E9;
          --live:#2D6A4F;
          --gold:#C9A96E;--silver:#A8A5A0;--bronze:#B08050;
          --primary:#C41E3A;--primary-hover:#A0182E;--success:#2D6A4F;--danger:#C41E3A;
          --orange:#C9A96E;--blue:#A8A5A0;--green:#2D6A4F;--red:#C41E3A;
          --accent-warm:#C9A96E;
          --rad:0;--rad-pill:0;--rad-sm:0;
          --shadow:0 4px 24px rgba(0,0,0,0.04);
        }
        *{margin:0;padding:0;box-sizing:border-box}
        body{background:var(--canvas);color:var(--text);font-family:'Inter',sans-serif;font-size:15px;line-height:1.6;-webkit-font-smoothing:antialiased;min-height:100vh}
        ::selection{background:var(--accent-soft)}
        a{text-decoration:none;color:inherit}
        .card{background:var(--surface);border:1px solid var(--border)}
        .badge{display:inline-flex;align-items:center;gap:4px;padding:2px 10px;font-size:10px;font-weight:500;border:1px solid}
        .badge-accent{background:var(--accent-soft);color:var(--accent);border-color:rgba(196,30,58,0.15)}
        .badge-live{background:rgba(45,106,79,0.08);color:var(--live);border-color:rgba(45,106,79,0.15)}
        .badge-gray{background:var(--surface-muted);color:var(--text2);border-color:var(--border)}
        .badge-gold{background:rgba(201,169,110,0.1);color:var(--gold);border-color:rgba(201,169,110,0.2)}
        .caps{font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:0.08em;color:var(--text2)}
        .mono{font-family:'JetBrains Mono',monospace;font-variant-numeric:tabular-nums}
        .serif{font-family:'Playfair Display',serif}
        .hero{position:relative;height:200px;overflow:hidden}
        .hero-bg{position:absolute;inset:0;background-size:cover;background-position:center;background-color:#1a1a2e;background-image:linear-gradient(135deg,#1a1a2e,#16213e)}
        .hero-overlay{position:absolute;inset:0;background:linear-gradient(to bottom,rgba(0,0,0,0.1) 0%,rgba(0,0,0,0.5) 100%)}
        .race-info{background:var(--surface);border:1px solid var(--border);padding:20px 24px;display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:24px}
        .race-info-left{flex:1;min-width:0}
        .race-info-right{flex-shrink:0;display:flex;align-items:center;gap:12px}
        .race-title{font-family:'Teko',sans-serif;font-weight:500;text-transform:uppercase;letter-spacing:0.04em;font-size:28px;color:var(--text);line-height:1;margin-bottom:2px}
        .racing{font-family:'Teko',sans-serif;font-weight:500;text-transform:uppercase;letter-spacing:0.04em;line-height:1}
        .race-meta{font-size:12px;text-transform:uppercase;letter-spacing:0.08em;color:var(--text2)}
        .page{max-width:1000px;margin:0 auto;padding:80px 24px 60px}
        @media(max-width:768px){.page{padding:72px 12px 40px}.hero{height:150px}.race-info{flex-direction:column;align-items:stretch;padding:16px;gap:12px}.race-title{font-size:20px}}
        table{width:100%;border-collapse:collapse}
        thead th{padding:12px 14px;text-align:left;font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:var(--text3);border-bottom:1px solid var(--border)}
        thead th.right{text-align:right}
        tbody tr{border-bottom:1px solid var(--border-light);transition:background 150ms}
        tbody tr:hover{background:var(--surface-muted)}
        tbody tr.me{background:var(--accent-soft);border-left:2px solid var(--accent)}
        td{padding:10px 14px;vertical-align:middle}
        .sel-wrap{display:flex;justify-content:center;margin-bottom:24px}
        select{background:var(--surface);border:1px solid var(--border);color:var(--text);padding:10px 16px;font-family:'Inter',sans-serif;font-size:13px;font-weight:500;outline:none;cursor:pointer;min-width:280px;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%236B6864' d='M6 8L0 0h12z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center;padding-right:36px}
        .podium{display:grid;grid-template-columns:1fr 1.2fr 1fr;align-items:end;margin-bottom:28px}
        .pod-card{background:var(--surface);border:1px solid var(--border);padding:24px 16px;text-align:center;position:relative}
        .pod-card.first{border-top:3px solid var(--gold);padding:32px 20px}
        .pod-card.second{border-top:3px solid var(--silver)}
        .pod-card.third{border-top:3px solid var(--bronze)}
        .pod-medal{font-size:28px;margin-bottom:8px}
        .pod-name{font-weight:700;font-size:15px;color:var(--text);margin-bottom:4px}
        .pod-pts{font-family:'JetBrains Mono',monospace;font-size:28px;font-weight:700;color:var(--gold);line-height:1}
        .pod-pts.second{color:var(--silver)}
        .pod-pts.third{color:var(--bronze)}
        .pod-label{font-size:9px;text-transform:uppercase;letter-spacing:0.08em;color:var(--text2);margin-top:4px}
        .pod-badge{display:inline-block;font-size:9px;font-weight:600;background:rgba(201,169,110,0.1);color:var(--gold);border:1px solid rgba(201,169,110,0.2);padding:2px 10px;margin-top:8px;text-transform:uppercase;letter-spacing:0.06em}
        .rank-cell{font-family:'JetBrains Mono',monospace;font-weight:600;font-size:14px}
        .rank-1{color:var(--gold)}
        .rank-2{color:var(--silver)}
        .rank-3{color:var(--bronze)}
        .pts-cell{font-family:'JetBrains Mono',monospace;font-weight:700;font-size:18px;text-align:right;color:var(--success)}
        .pts-cell-lg{font-family:'JetBrains Mono',monospace;font-weight:700;font-size:22px;text-align:right;color:var(--success)}
        .pts-cell-me{color:var(--accent)}
        .foot{text-align:center;margin-top:48px;padding-top:24px;border-top:1px solid var(--border);font-size:11px;color:var(--text3)}
        .ach-row{display:none}
        .ach-row.open{display:table-row}
        .ach-row td{padding:0}
        .ach-inner{max-height:0;overflow:hidden;transition:max-height 0.35s ease,opacity 0.3s ease,padding 0.3s ease;opacity:0;padding:0 14px}
        .ach-inner.open{max-height:400px;opacity:1;padding:12px 14px}
        .ach-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:4px}
        .ach-item{display:flex;align-items:center;gap:6px;padding:5px 6px;font-size:10px;line-height:1.2}
        .ach-item.unlocked{opacity:1}
        .ach-item.locked{opacity:0.35}
        .ach-icon{font-size:12px;width:14px;text-align:center}
        .ach-check{font-size:7px;font-weight:700;margin-left:auto}
        .clickable-name{cursor:pointer;border-bottom:1px dotted var(--text3);transition:color 150ms}
        .clickable-name:hover{color:var(--accent)}
        .lvl-badge{display:inline-flex;align-items:center;gap:3px;padding:1px 6px;font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:0.04em}
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/includes/nav.php'; ?>

    <main class="page">

        <?php
        $heroImg = 'https://images.unsplash.com/photo-1678919225767-c2d4dff33ab4?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fGVufHx8fA%3D%3D';
        $title = $showRaceLeaderboard ? ($selectedRaceInfo['country'] ?? 'Race').' GP' : 'Championship Standings';
        $subtitle = $showRaceLeaderboard ? 'Results for '.htmlspecialchars($selectedRaceInfo['country'] ?? '').' GP' : 'The fastest predictors in the paddock';
        ?>

        <!-- HERO -->
        <div class="hero">
            <div class="hero-bg" style="background-image:url('<?php echo $heroImg; ?>')"></div>
            <div class="hero-overlay"></div>
        </div>

        <!-- RACE INFO -->
        <div class="race-info">
            <div class="race-info-left">
                <div class="race-title"><?php echo $title; ?></div>
                <div class="race-meta"><?php echo $subtitle; ?></div>
            </div>
            <div class="race-info-right">
                <span class="badge badge-gray"><i class="fa-regular fa-calendar"></i> Season 2026</span>
            </div>
        </div>

        <!-- Race Selector -->
        <div class="sel-wrap">
            <form method="get">
                <select name="race_id" onchange="this.form.submit()">
                    <option value="">&#x1F3C6; Overall Standings</option>
                    <?php foreach ($allRaces as $race): ?>
                        <?php $raceDate = new DateTime($race['race_date'], new DateTimeZone('UTC')); ?>
                        <option value="<?php echo $race['id']; ?>" <?php echo ($selectedRaceId == $race['id']) ? 'selected' : ''; ?>>
                            &#x1F3C1; <?php echo htmlspecialchars($race['country']); ?> GP — <?php echo $raceDate->format('F j, Y'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <!-- Top 3 Podium -->
        <?php if (!empty($leaderboard) && count($leaderboard) >= 3): ?>
        <div class="podium">
            <div class="pod-card second">
                <div class="pod-medal">&#x1F948;</div>
                <div style="width:48px;height:48px;border-radius:50%;overflow:hidden;margin:0 auto 8px;background:var(--surface-muted);border:2px solid var(--silver)">
                    <img src="<?php echo getAvatarUrl($leaderboard[1]['avatar_style'] ?? 'avataaars', $leaderboard[1]['username']); ?>" style="width:100%;height:100%;object-fit:cover">
                </div>
                <div class="pod-name"><?php echo htmlspecialchars($leaderboard[1]['username']); ?></div>
                <div class="pod-pts second"><?php echo number_format($leaderboard[1]['total_points'] ?? 0); ?></div>
                <div class="pod-label">Points</div>
            </div>

            <div class="pod-card first">
                <div class="pod-medal">&#x1F451;</div>
                <div style="width:56px;height:56px;border-radius:50%;overflow:hidden;margin:0 auto 8px;background:var(--surface-muted);border:2px solid var(--gold)">
                    <img src="<?php echo getAvatarUrl($leaderboard[0]['avatar_style'] ?? 'avataaars', $leaderboard[0]['username']); ?>" style="width:100%;height:100%;object-fit:cover">
                </div>
                <div class="pod-name" style="font-size:18px"><?php echo htmlspecialchars($leaderboard[0]['username']); ?></div>
                <div class="pod-pts"><?php echo number_format($leaderboard[0]['total_points'] ?? 0); ?></div>
                <div class="pod-label">Points</div>
                <?php if (!$showRaceLeaderboard): ?>
                    <div class="pod-badge">Season Leader</div>
                <?php endif; ?>
            </div>

            <div class="pod-card third">
                <div class="pod-medal">&#x1F949;</div>
                <div style="width:48px;height:48px;border-radius:50%;overflow:hidden;margin:0 auto 8px;background:var(--surface-muted);border:2px solid var(--bronze)">
                    <img src="<?php echo getAvatarUrl($leaderboard[2]['avatar_style'] ?? 'avataaars', $leaderboard[2]['username']); ?>" style="width:100%;height:100%;object-fit:cover">
                </div>
                <div class="pod-name"><?php echo htmlspecialchars($leaderboard[2]['username']); ?></div>
                <div class="pod-pts third"><?php echo number_format($leaderboard[2]['total_points'] ?? 0); ?></div>
                <div class="pod-label">Points</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Full Standings Table -->
        <div class="card" style="overflow:hidden">
            <div style="overflow-x:auto">
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Player</th>
                            <?php if ($showRaceLeaderboard): ?>
                                <th class="right">Driver</th>
                                <th class="right">Const</th>
                                <th class="right">Bonus</th>
                                <th class="right">Total</th>
                            <?php else: ?>
                                <th class="right">Points</th>
                                <th class="right">Races</th>
                                <th class="right">Avg</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        function getLevelLabel($pts) {
                            if ($pts >= 1000) return ['label'=>'Legend','icon'=>'fa-crown','color'=>'var(--danger)'];
                            if ($pts >= 500) return ['label'=>'Expert','icon'=>'fa-star','color'=>'var(--primary)'];
                            if ($pts >= 250) return ['label'=>'Veteran','icon'=>'fa-shield','color'=>'var(--accent)'];
                            if ($pts >= 100) return ['label'=>'Pro','icon'=>'fa-rocket','color'=>'var(--success)'];
                            return ['label'=>'Rookie','icon'=>'fa-seedling','color'=>'var(--text2)'];
                        }

                        $ALL_ACHIEVEMENTS = [
                            ['id'=>'first_prediction','name'=>'Rookie Driver','tier'=>'common','icon'=>'fa-flag'],
                            ['id'=>'welcome_aboard','name'=>'Welcome to the Paddock','tier'=>'common','icon'=>'fa-user-check'],
                            ['id'=>'first_points','name'=>'On the Board','tier'=>'common','icon'=>'fa-star'],
                            ['id'=>'participation_5','name'=>'Racing Regular','tier'=>'common','icon'=>'fa-calendar-check'],
                            ['id'=>'streak_3','name'=>'Consistency Counts','tier'=>'common','icon'=>'fa-fire'],
                            ['id'=>'participation_10','name'=>'Season Veteran','tier'=>'rare','icon'=>'fa-medal'],
                            ['id'=>'podium_sweep_1','name'=>'Podium Prophet','tier'=>'rare','icon'=>'fa-trophy'],
                            ['id'=>'total_500','name'=>'Point Collector','tier'=>'rare','icon'=>'fa-coins'],
                            ['id'=>'constructor_correct_5','name'=>'Team Tactician','tier'=>'rare','icon'=>'fa-wrench'],
                            ['id'=>'perfectionist','name'=>'Perfectionist','tier'=>'rare','icon'=>'fa-bullseye'],
                            ['id'=>'accuracy_20','name'=>'Sharp Shooter','tier'=>'rare','icon'=>'fa-crosshairs'],
                            ['id'=>'big_score','name'=>'Big Score','tier'=>'epic','icon'=>'fa-bolt'],
                            ['id'=>'podium_sweep_3','name'=>'Crystal Ball','tier'=>'epic','icon'=>'fa-eye'],
                            ['id'=>'streak_10','name'=>'Unbreakable Focus','tier'=>'epic','icon'=>'fa-fire'],
                            ['id'=>'double_points_master','name'=>'Double Trouble','tier'=>'epic','icon'=>'fa-gem'],
                            ['id'=>'accuracy_30','name'=>'Precision Engineer','tier'=>'epic','icon'=>'fa-bullseye'],
                            ['id'=>'total_1000','name'=>'Points Millionaire','tier'=>'epic','icon'=>'fa-sack-dollar'],
                            ['id'=>'race_winner_3','name'=>'Hat Trick Hero','tier'=>'epic','icon'=>'fa-crown'],
                            ['id'=>'legendary_performance','name'=>'Legendary Performance','tier'=>'legendary','icon'=>'fa-trophy'],
                            ['id'=>'podium_sweep_5','name'=>'Oracle of the Grid','tier'=>'legendary','icon'=>'fa-eye'],
                            ['id'=>'accuracy_40','name'=>'The Nostradamus','tier'=>'legendary','icon'=>'fa-magic'],
                            ['id'=>'total_2500','name'=>'Point Legend','tier'=>'legendary','icon'=>'fa-infinity'],
                            ['id'=>'first_race_winner','name'=>'Early Bird','tier'=>'special','icon'=>'fa-bolt'],
                            ['id'=>'constructor_sweep','name'=>'Team Whisperer','tier'=>'special','icon'=>'fa-handshake'],
                            ['id'=>'perfect_weekend','name'=>'Perfect Weekend','tier'=>'special','icon'=>'fa-check'],
                            ['id'=>'mega_race','name'=>'Mega Race','tier'=>'special','icon'=>'fa-rocket'],
                            ['id'=>'silver_arrows','name'=>'Silver Arrows','tier'=>'special','icon'=>'fa-star'],
                            ['id'=>'columbus','name'=>'Columbus','tier'=>'special','icon'=>'fa-globe-americas'],
                            ['id'=>'f1_hero','name'=>'F1 Hero','tier'=>'special','icon'=>'fa-flag-checkered']
                        ];
                        $TIER_COLORS = [
                            'common' => ['bg'=>'rgba(45,106,79,0.08)', 'color'=>'#2D6A4F'],
                            'rare' => ['bg'=>'rgba(196,30,58,0.08)', 'color'=>'#C41E3A'],
                            'epic' => ['bg'=>'rgba(196,30,58,0.08)', 'color'=>'#C41E3A'],
                            'legendary' => ['bg'=>'rgba(220,53,69,0.08)', 'color'=>'#DC3545'],
                            'special' => ['bg'=>'rgba(201,169,110,0.08)', 'color'=>'#C9A96E']
                        ];

                        $rank = 1;
                        foreach ($leaderboard as $entry):
                            $avgPoints = ($entry['races_participated'] ?? 0) > 0
                                ? number_format(($entry['total_points'] ?? 0) / $entry['races_participated'], 1)
                                : '0.0';
                            $isMe = ($user && $user['username'] === $entry['username']);
                            $meClass = $isMe ? 'me' : '';
                            $achIds = $entry['all_achievements'] ?? '';
                            $pts = $entry['total_points'] ?? 0;
                            $level = getLevelLabel($pts);
                        ?>
                        <tr class="<?php echo $meClass; ?>" data-user-id="<?php echo $entry['id']; ?>" data-achievements="<?php echo htmlspecialchars($achIds); ?>">
                            <td>
                                <?php if ($rank === 1): ?>
                                    <span class="rank-cell rank-1">1</span>
                                <?php elseif ($rank === 2): ?>
                                    <span class="rank-cell rank-2">2</span>
                                <?php elseif ($rank === 3): ?>
                                    <span class="rank-cell rank-3">3</span>
                                <?php else: ?>
                                    <span class="rank-cell" style="color:var(--text3)"><?php echo $rank; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px">
                                    <div style="width:32px;height:32px;border-radius:50%;overflow:hidden;flex-shrink:0;background:var(--surface-muted)">
                                        <img src="<?php echo getAvatarUrl($entry['avatar_style'] ?? 'avataaars', $entry['username']); ?>" style="width:100%;height:100%;object-fit:cover">
                                    </div>
                                    <div>
                                        <div style="font-weight:600;font-size:14px;display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                                            <span class="clickable-name" onclick="toggleAchievements(<?php echo $entry['id']; ?>)">
                                                <?php echo htmlspecialchars($entry['username']); ?>
                                            </span>
                                            <?php if ($isMe): ?>
                                                <span class="badge badge-accent" style="font-size:8px;padding:1px 6px">YOU</span>
                                            <?php endif; ?>
                                            <span style="display:inline-flex;align-items:center;gap:3px;padding:1px 6px;background:<?php echo $level['color']; ?>15;color:<?php echo $level['color']; ?>;font-weight:600;font-size:8px;text-transform:uppercase;letter-spacing:0.03em">
                                                <i class="fa-solid <?php echo $level['icon']; ?>" style="font-size:7px"></i> <?php echo $level['label']; ?>
                                            </span>
                                        </div>
                                        <?php if (!empty($entry['full_name'])): ?>
                                            <div style="font-size:10px;color:var(--text3);margin-top:1px"><?php echo htmlspecialchars($entry['full_name']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <?php if ($showRaceLeaderboard): ?>
                                <td class="pts-cell" style="font-size:16px;color:var(--text)"><?php echo number_format($entry['driver_points'] ?? 0); ?></td>
                                <td class="pts-cell" style="font-size:16px;color:var(--accent)"><?php echo number_format($entry['constructor_points'] ?? 0); ?></td>
                                <td class="pts-cell" style="font-size:16px;color:var(--gold)"><?php echo number_format(($entry['top3_bonus'] ?? 0) + ($entry['constructor_top3_bonus'] ?? 0)); ?></td>
                                <td class="pts-cell-lg <?php echo $isMe ? 'pts-cell-me' : ''; ?>"><?php echo number_format($entry['total_points'] ?? 0); ?></td>
                            <?php else: ?>
                                <td class="pts-cell-lg <?php echo $isMe ? 'pts-cell-me' : ''; ?>"><?php echo number_format($entry['total_points'] ?? 0); ?></td>
                                <td class="pts-cell" style="font-size:14px;color:var(--text2)"><?php echo $entry['races_participated'] ?? 0; ?></td>
                                <td class="pts-cell" style="font-size:14px;color:var(--text2)"><?php echo $avgPoints; ?></td>
                            <?php endif; ?>
                        </tr>
                        <tr class="ach-row" id="ach-row-<?php echo $entry['id']; ?>">
                            <td colspan="<?php echo $showRaceLeaderboard ? 6 : 5; ?>" style="padding:0">
                                <div class="ach-inner" id="ach-inner-<?php echo $entry['id']; ?>">
                                    <div class="ach-grid">
                                        <?php $unlockedAchIds = $achIds ? explode(',', $achIds) : []; ?>
                                        <?php foreach ($ALL_ACHIEVEMENTS as $ach):
                                            $isUnlocked = in_array($ach['id'], $unlockedAchIds);
                                            $tc = $TIER_COLORS[$ach['tier']] ?? $TIER_COLORS['common'];
                                        ?>
                                        <div class="ach-item <?php echo $isUnlocked ? 'unlocked' : 'locked'; ?>" style="background:<?php echo $isUnlocked ? $tc['bg'] : ''; ?>;border:1px solid <?php echo $isUnlocked ? $tc['color'].'30' : 'var(--border)'; ?>">
                                            <span class="ach-icon" style="color:<?php echo $isUnlocked ? $tc['color'] : 'var(--text3)'; ?>">
                                                <?php if ($isUnlocked): ?>
                                                    <i class="fa-solid <?php echo $ach['icon']; ?>"></i>
                                                <?php else: ?>
                                                    <i class="fa-solid fa-lock"></i>
                                                <?php endif; ?>
                                            </span>
                                            <span style="color:<?php echo $isUnlocked ? 'var(--text)' : 'var(--text3)'; ?>;font-weight:<?php echo $isUnlocked ? 600 : 400; ?>"><?php echo htmlspecialchars($ach['name']); ?></span>
                                            <?php if ($isUnlocked): ?>
                                                <span class="ach-check" style="color:<?php echo $tc['color']; ?>">&#10003;</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php $rank++; endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <footer class="foot">
        <p>&copy; <?php echo date('Y'); ?> PADDOCK PICKS</p>
    </footer>

    <script>
    let openAchRow = null;
    function toggleAchievements(userId) {
        const row = document.getElementById('ach-row-' + userId);
        const inner = document.getElementById('ach-inner-' + userId);
        if (!row || !inner) return;
        if (row.classList.contains('open')) {
            row.classList.remove('open');
            inner.classList.remove('open');
            openAchRow = null;
        } else {
            if (openAchRow) {
                const prevRow = document.getElementById('ach-row-' + openAchRow);
                const prevInner = document.getElementById('ach-inner-' + openAchRow);
                if (prevRow) prevRow.classList.remove('open');
                if (prevInner) prevInner.classList.remove('open');
            }
            row.classList.add('open');
            inner.classList.add('open');
            openAchRow = userId;
        }
    }
    </script>
</body>
</html>
