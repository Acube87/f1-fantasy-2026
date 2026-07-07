<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$user = getCurrentUser();
if (!$user) {
    header('Location: ../login.php');
    exit;
}

$db = getDB();

// Ensure Angrycube is always admin (special case for main user)
if ($user['username'] === 'Angrycube') {
    // Make sure they're marked as admin
    $db->query("UPDATE users SET is_admin = 1 WHERE username = 'Angrycube'");
    $user['is_admin'] = 1;
} else {
    // For other users, require admin access
    requireAdmin();
}

// Handle preview request
$previewContent = '';
$previewRace = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['preview_race_id'])) {
    $previewRaceId = (int)$_POST['preview_race_id'];
    
    // Get race details for preview
    $raceStmt = $db->prepare("SELECT * FROM races WHERE id = ?");
    $raceStmt->bind_param("i", $previewRaceId);
    $raceStmt->execute();
    $previewRace = $raceStmt->get_result()->fetch_assoc();
    
    if ($previewRace && $previewRace['status'] === 'completed') {
        // Generate preview content (same logic as createPostRaceDebrief but return content instead of saving)
        $previewContent = generateDebriefPreview($previewRaceId, $db);
    }
}

// Handle manual debrief generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['race_id'])) {
    $raceId = (int)$_POST['race_id'];
    
    // Verify race exists and is completed
    $raceStmt = $db->prepare("SELECT id, race_name, status FROM races WHERE id = ?");
    $raceStmt->bind_param("i", $raceId);
    $raceStmt->execute();
    $race = $raceStmt->get_result()->fetch_assoc();
    
    if (!$race) {
        $message = "Race not found!";
    } elseif ($race['status'] !== 'completed') {
        $message = "Race must be completed before creating debrief!";
    } else {
        // Check if debrief already exists
        $existsStmt = $db->prepare("SELECT id FROM posts WHERE race_id = ?");
        $existsStmt->bind_param("i", $raceId);
        $existsStmt->execute();
        $existing = $existsStmt->get_result()->fetch_assoc();
        
        if ($existing) {
            $message = "Debrief already exists for this race! Removing old one and creating new...";
            $db->query("DELETE FROM posts WHERE race_id = $raceId");
        }
        
        // Create the debrief
        if (createPostRaceDebrief($raceId, $db)) {
            $message = "✅ Post-race debrief created successfully for " . htmlspecialchars($race['race_name']) . "!";
            $success = true;
        } else {
            $message = "❌ Error creating debrief. Please check the database.";
        }
    }
}

// Get completed races without debriefs
$completedRaces = $db->query("
    SELECT r.id, r.race_name, r.country, r.race_date, 
           CASE WHEN p.id IS NOT NULL THEN 'Yes' ELSE 'No' END as has_debrief
    FROM races r
    LEFT JOIN posts p ON r.id = p.race_id
    WHERE r.status = 'completed'
    ORDER BY r.race_date DESC
");
$races = $completedRaces->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Debriefs — <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,500;1,400;1,500&display=swap" rel="stylesheet">
    <style>
        :root{
          --canvas:#F5F3EF;--bg:#F5F3EF;--surface:#FFF;--border:#E8E5E0;--border-light:#F0EDE8;
          --text:#1A1A1A;--text2:#6B6864;--text3:#A09C96;
          --accent:#C41E3A;--accent-soft:#F5E6E9;
          --live:#2D6A4F;
        }
        *{margin:0;padding:0;box-sizing:border-box}
        body{background:var(--canvas);color:var(--text);font-family:'Inter',sans-serif;font-size:15px;line-height:1.6;-webkit-font-smoothing:antialiased}
        a{text-decoration:none;color:inherit}
        .caps{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:var(--text3)}
        .card{background:var(--surface);border:1px solid var(--border)}
        .badge{display:inline-flex;align-items:center;gap:4px;padding:2px 10px;font-size:10px;font-weight:500;border:1px solid}
        .badge-accent{background:var(--accent-soft);color:var(--accent);border-color:rgba(196,30,58,0.15)}
        .badge-live{background:rgba(45,106,79,0.08);color:var(--live);border-color:rgba(45,106,79,0.15)}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:8px 18px;font-size:13px;font-weight:600;border:1px solid var(--border);cursor:pointer;transition:all 150ms;background:var(--surface);color:var(--text)}
        .btn:hover{background:var(--canvas)}
        .btn-primary{background:var(--accent);color:#fff;border-color:var(--accent)}
        .btn-primary:hover{opacity:0.9}
        .btn-live{background:var(--live);color:#fff;border-color:var(--live)}
        .btn-live:hover{opacity:0.9}
        .page{max-width:720px;margin:0 auto;padding:40px 24px}
        .flex{display:flex}.items-center{align-items:center}.justify-between{justify-content:space-between}.gap-2{gap:8px}.gap-3{gap:12px}.gap-4{gap:16px}
        .mb-4{margin-bottom:16px}.mb-6{margin-bottom:24px}.mb-8{margin-bottom:32px}
        .p-4{padding:16px}.p-6{padding:24px}.mt-3{margin-top:12px}
        .msg{font-size:13px;padding:12px 16px;border:1px solid}
        .msg.success{background:rgba(45,106,79,0.06);border-color:rgba(45,106,79,0.15);color:var(--live)}
        .msg.error{background:rgba(196,30,58,0.06);border-color:rgba(196,30,58,0.15);color:var(--accent)}
        .post-content{font-family:'Inter',sans-serif;line-height:1.7;font-size:14px}
        .post-content strong{color:var(--accent);font-weight:700}
        .ovf{max-height:360px;overflow-y:auto;background:var(--surface);border:1px solid var(--border);padding:16px}
    </style>
</head>
<body>

    <!-- Nav -->
    <nav style="background:var(--surface);border-bottom:1px solid var(--border);padding:12px 24px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50">
        <div style="display:flex;align-items:center;gap:8px">
            <i class="fas fa-wand-magic-sparkles" style="color:var(--accent);font-size:16px"></i>
            <span style="font-weight:700;font-size:16px">Generate Debriefs</span>
        </div>
        <a href="race-control.php" style="font-size:12px;color:var(--text2)"><i class="fas fa-chevron-left"></i> Race Control</a>
    </nav>

    <main class="page">

        <!-- Message -->
        <?php if ($message): ?>
            <div class="msg <?php echo $success ? 'success' : 'error'; ?>" style="margin-bottom:20px"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- Preview Section -->
        <?php if ($previewContent && $previewRace): ?>
            <div class="card" style="padding:20px;margin-bottom:24px;border-left:3px solid var(--accent)">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
                    <div style="font-weight:700;font-size:15px"><i class="fas fa-eye" style="margin-right:8px"></i>Preview: <?php echo htmlspecialchars($previewRace['race_name']); ?></div>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="race_id" value="<?php echo $previewRace['id']; ?>">
                        <button type="submit" class="btn btn-live" style="font-size:12px"><i class="fas fa-check"></i> Publish</button>
                    </form>
                </div>
                <div class="ovf">
                    <div class="post-content"><?php echo $previewContent; ?></div>
                </div>
                <div style="display:flex;gap:8px;margin-top:12px">
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="race_id" value="<?php echo $previewRace['id']; ?>">
                        <button type="submit" class="btn btn-live" style="font-size:12px"><i class="fas fa-paper-plane"></i> Publish Debrief</button>
                    </form>
                    <button onclick="this.parentElement.parentElement.remove()" class="btn" style="font-size:12px"><i class="fas fa-times"></i> Close</button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Info -->
        <div class="card" style="padding:20px;margin-bottom:24px;border-left:3px solid var(--live)">
            <div style="font-weight:700;font-size:15px;margin-bottom:8px"><i class="fas fa-info-circle" style="margin-right:8px;color:var(--live)"></i>How it Works</div>
            <p style="color:var(--text2);font-size:13px;margin-bottom:8px">Generates automated post-race debrief posts for completed races:</p>
            <ul style="color:var(--text2);font-size:12px;margin-left:16px;display:flex;flex-direction:column;gap:4px">
                <li>✅ Top 3 scoring users</li>
                <li>✅ Race winner and constructor champion</li>
                <li>✅ Participation statistics</li>
                <li>✅ User achievements and highlights</li>
                <li>✅ Next upcoming race info</li>
            </ul>
        </div>

        <!-- Races List -->
        <div style="margin-bottom:16px;font-weight:600;font-size:14px"><i class="fas fa-flag-checkered" style="margin-right:8px"></i>Completed Races</div>

        <?php if (empty($races)): ?>
            <div class="card" style="padding:32px;text-align:center"><span style="color:var(--text3);font-size:13px">No completed races found</span></div>
        <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:8px">
                <?php foreach ($races as $race): ?>
                    <div class="card" style="padding:14px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px">
                        <div style="flex:1;min-width:0">
                            <div style="font-weight:600;font-size:13px"><?php echo htmlspecialchars($race['race_name']); ?></div>
                            <div class="caps" style="font-size:9px;margin-top:2px"><?php echo htmlspecialchars($race['country']); ?> &middot; <?php echo date('M d, Y', strtotime($race['race_date'])); ?></div>
                            <span style="display:inline-block;font-size:9px;padding:1px 8px;margin-top:4px;font-weight:600;background:<?php echo $race['has_debrief']==='Yes'?'rgba(45,106,79,0.08)':'rgba(196,30,58,0.08)'; ?>;color:<?php echo $race['has_debrief']==='Yes'?'var(--live)':'var(--accent)'; ?>;border:1px solid <?php echo $race['has_debrief']==='Yes'?'rgba(45,106,79,0.15)':'rgba(196,30,58,0.15)'; ?>">
                                Debrief: <?php echo $race['has_debrief']; ?>
                            </span>
                        </div>
                        <div style="display:flex;gap:6px;flex-shrink:0">
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="preview_race_id" value="<?php echo $race['id']; ?>">
                                <button type="submit" class="btn" style="font-size:10px;padding:5px 12px"><i class="fas fa-eye"></i> Preview</button>
                            </form>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="race_id" value="<?php echo $race['id']; ?>">
                                <button type="submit" class="btn btn-primary" style="font-size:10px;padding:5px 12px">
                                    <?php echo $race['has_debrief'] === 'Yes' ? 'Regen' : 'Generate'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
