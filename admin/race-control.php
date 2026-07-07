<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/avatars.php';

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

// Fetch ALL races so completed ones can also be re-processed
$stmt = $db->query("SELECT id, race_name, country, race_date, status FROM races ORDER BY race_date ASC");
$races = $stmt->fetch_all(MYSQLI_ASSOC);

// Fetch all drivers for the manual selection
$stmt = $db->query("SELECT id, driver_name, team FROM drivers ORDER BY team, driver_name");
$drivers = $stmt->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Race Control — <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,500;1,400;1,500&display=swap" rel="stylesheet">
    <style>
        :root{
          --canvas:#F5F3EF;--bg:#F5F3EF;--surface:#FFF;--card:#FFF;--border:#E8E5E0;--border-light:#F0EDE8;
          --text:#1A1A1A;--text2:#6B6864;--text3:#A09C96;
          --accent:#C41E3A;--accent-soft:#F5E6E9;
          --live:#2D6A4F;--gold:#C9A96E;
        }
        *{margin:0;padding:0;box-sizing:border-box}
        body{background:var(--canvas);color:var(--text);font-family:'Inter',sans-serif;font-size:15px;line-height:1.6;-webkit-font-smoothing:antialiased}
        a{text-decoration:none;color:inherit}
        .caps{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:var(--text3)}
        .mono{font-family:'JetBrains Mono',monospace}
        .card{background:var(--surface);border:1px solid var(--border)}
        .badge{display:inline-flex;align-items:center;gap:4px;padding:2px 10px;font-size:10px;font-weight:500;border:1px solid}
        .badge-accent{background:var(--accent-soft);color:var(--accent);border-color:rgba(196,30,58,0.15)}
        .badge-live{background:rgba(45,106,79,0.08);color:var(--live);border-color:rgba(45,106,79,0.15)}
        input,select,textarea{font-family:'Inter',sans-serif;font-size:14px;color:var(--text);background:var(--surface);border:1px solid var(--border);padding:10px 14px;outline:none;transition:border-color 150ms}
        input:focus,select:focus,textarea:focus{border-color:var(--accent)}
        textarea{resize:vertical}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 24px;font-size:14px;font-weight:600;border:1px solid var(--border);cursor:pointer;transition:all 150ms;background:var(--surface);color:var(--text)}
        .btn:hover{background:var(--canvas)}
        .btn-primary{background:var(--accent);color:#fff;border-color:var(--accent)}
        .btn-primary:hover{opacity:0.9}
        .btn-outline{background:transparent;border-color:var(--border);color:var(--text2)}
        .btn-outline:hover{border-color:var(--accent);color:var(--accent)}
        .fade{transition:opacity 150ms}
        .w{width:100%}
        .flex{display:flex}.flex-col{flex-direction:column}.items-center{align-items:center}.justify-between{justify-content:space-between}.gap-2{gap:8px}.gap-3{gap:12px}.gap-4{gap:16px}.gap-6{gap:24px}.gap-8{gap:32px}
        .grid{display:grid}.grid-cols-1{grid-template-columns:1fr}
        .mt-4{margin-top:16px}.mt-6{margin-top:24px}.mt-8{margin-top:32px}.mt-12{margin-top:48px}.mb-4{margin-bottom:16px}.mb-6{margin-bottom:24px}.mb-8{margin-bottom:32px}.mb-12{margin-bottom:48px}
        .p-3{padding:12px}.p-4{padding:16px}.p-6{padding:24px}.p-8{padding:32px}.px-4{padding-left:16px;padding-right:16px}.py-3{padding-top:12px;padding-bottom:12px}.py-5{padding-top:20px;padding-bottom:20px}
        .text-center{text-align:center}
        .overflow-y-auto{overflow-y:auto}
        .page{max-width:960px;margin:0 auto;padding:60px 24px 40px}
        @media(min-width:1024px){.lg\:grid-cols-2{grid-template-columns:1fr 1fr}.lg\:grid-cols-3{grid-template-columns:1fr 1fr 1fr}.lg\:col-span-3{grid-column:span 3}}
        .pr-2{padding-right:8px}
        .driver-slot{transition:all 150ms;border:1px solid var(--border);background:var(--surface)}
        .driver-slot:hover{border-color:var(--accent);background:var(--accent-soft)}
        .ovl{position:fixed;inset:0;z-index:100;background:rgba(0,0,0,0.25);display:none;flex-direction:column;align-items:center;justify-content:center}
        .ovl.active{display:flex}
        .spin{width:48px;height:48px;border:3px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:spin 1s linear infinite;margin-bottom:16px}
        @keyframes spin{to{transform:rotate(360deg)}}
    </style>
</head>
<body>

    <nav style="background:var(--surface);border-bottom:1px solid var(--border);padding:12px 24px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50">
        <div style="display:flex;align-items:center;gap:12px">
            <i class="fas fa-tower-broadcast" style="color:var(--accent);font-size:20px"></i>
            <span style="font-weight:800;font-size:18px;text-transform:uppercase;letter-spacing:-0.02em">Race Control</span>
        </div>
        <div style="display:flex;align-items:center;gap:16px">
            <a href="generate-debriefs.php" class="btn btn-primary" style="padding:6px 16px;font-size:12px"><i class="fas fa-wand-magic-sparkles"></i> Debriefs</a>
            <a href="create-post.php" class="btn" style="padding:6px 16px;font-size:12px"><i class="fas fa-pen-nib"></i> News Post</a>
            <a href="../dashboard.php" style="font-size:12px;color:var(--text2)">Paddock <i class="fa-solid fa-arrow-right" style="font-size:10px"></i></a>
        </div>
    </nav>

    <main class="page">

        <!-- STEP 1: Select Race -->
        <div class="card" style="padding:20px;margin-bottom:16px">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
                <span class="badge badge-accent" style="font-size:9px;padding:3px 10px">Step 1</span>
                <span class="caps">Select Race</span>
            </div>
            <select id="raceSelect" style="width:100%;max-width:500px">
                <option value="">-- Select the race you are updating --</option>
                <?php foreach ($races as $race): 
                    $statusLabel = $race['status'] === 'completed' ? '✅ DONE' : '🔓 UPCOMING';
                ?>
                    <option value="<?php echo $race['id']; ?>">
                        <?php echo $statusLabel; ?> — RD <?php echo $race['id']; ?> — <?php echo htmlspecialchars($race['race_name']); ?> (<?php echo date('M d', strtotime($race['race_date'])); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- STEP 2: Parser -->
        <div class="card" style="padding:24px;margin-bottom:16px">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px">
                <div>
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                        <span class="badge badge-accent" style="font-size:9px;padding:3px 10px">Step 2</span>
                        <span style="font-weight:700;font-size:16px">Bulk Parser</span>
                    </div>
                    <div class="caps">Paste a results table below — auto-detects drivers</div>
                </div>
                <div style="display:flex;align-items:center;gap:12px">
                    <button onclick="clearParser()" class="btn btn-outline" style="font-size:11px;padding:6px 14px"><i class="fa-solid fa-eraser"></i> Clear</button>
                    <span id="classificationCount" class="mono" style="font-weight:700;font-size:13px;color:var(--accent)">0 / <?php echo count($drivers); ?> detected</span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Input -->
                <div>
                    <div class="caps" style="margin-bottom:6px">Input Stream</div>
                    <textarea id="resultsPaste" oninput="autoParse()"
                        style="width:100%;height:460px;padding:16px;font-family:'JetBrains Mono',monospace;font-size:12px;line-height:1.6;background:var(--surface);border:1px solid var(--border);resize:none"
                        placeholder="Paste the results table here...&#10;&#10;1 George Russell Mercedes 1:30:11&#10;2 Kimi Antonelli Mercedes +2.2&#10;3 Charles Leclerc Ferrari +5.5"></textarea>
                </div>

                <!-- Detection Grid -->
                <div>
                    <div class="caps" style="margin-bottom:6px">Detection Grid</div>
                    <div id="resultsGrid" style="height:460px;overflow-y:auto;display:flex;flex-direction:column;gap:4px;padding-right:4px">
                        <?php for ($i = 1; $i <= count($drivers); $i++): ?>
                            <div id="row-<?php echo $i; ?>" class="driver-slot" style="display:flex;align-items:center;gap:8px;padding:6px 10px;opacity:0.5">
                                <span style="width:28px;text-align:center;font-family:'JetBrains Mono',monospace;font-weight:700;font-size:13px;color:var(--text3)">#<?php echo $i; ?></span>
                                <select data-pos="<?php echo $i; ?>" class="driver-select" style="flex:1;padding:6px 10px;font-size:12px">
                                    <option value="">-- No driver --</option>
                                    <?php foreach ($drivers as $d): ?>
                                        <option value="<?php echo $d['id']; ?>">
                                            <?php echo htmlspecialchars($d['driver_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="status-<?php echo $i; ?>" class="caps" style="font-size:8px;min-width:50px;text-align:right">Standby</div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <!-- Deploy -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:24px;padding:16px 20px;background:var(--accent-soft);border:1px solid rgba(196,30,58,0.12);flex-wrap:wrap;gap:16px">
                <div>
                    <div style="font-weight:700;font-size:14px">Ready to deploy?</div>
                    <div class="caps" style="font-size:10px">Double-check the grid above. Scores will be calculated.</div>
                </div>
                <button onclick="submitResults()" id="submitBtn" class="btn btn-primary" style="padding:12px 32px;font-size:15px;font-weight:700"><i class="fas fa-rocket"></i> Deploy Classification</button>
            </div>
        </div>

    </main>

    <div id="loadingOverlay" class="ovl">
        <div class="spin"></div>
        <p style="font-weight:700;font-size:18px;color:var(--text)">Processing...</p>
    </div>

    <style id="detectStyle"></style>

    <script>
        const driversList = <?php echo json_encode($drivers); ?>;

        function clearParser() {
            document.getElementById('resultsPaste').value = "";
            document.querySelectorAll('.driver-select').forEach(s => s.value = "");
            autoParse();
        }

        function autoParse() {
            const raw = document.getElementById('resultsPaste').value;
            const lines = raw.split('\n').filter(l => l.trim() !== "");
            const selectors = document.querySelectorAll('.driver-select');
            
            // Temporary clear logic
            selectors.forEach(s => s.value = "");
            
            let detectedCount = 0;

            lines.forEach((line, index) => {
                // Try to find a position number if it exists, otherwise use index+1
                const posMatch = line.match(/^(\d+)/);
                const targetPos = posMatch ? parseInt(posMatch[1]) : (index + 1);
                
                if (targetPos > driversList.length) return;

                // Strip numbers and common noise, then normalize accents (ü -> u, é -> e)
                let cleanLine = line.replace(/^\d+/, '').replace(/[+\-]?\d+[:.]\d+[:.]?\d*/g, '').toLowerCase().trim();
                cleanLine = cleanLine.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                
                // Fuzzy Matcher: Look for name matches within the cleaned line
                let bestMatch = null;
                driversList.forEach(driver => {
                    const normalizedDriverName = driver.driver_name.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                    const nameParts = normalizedDriverName.split(' ');
                    const surname = nameParts[nameParts.length - 1];
                    const idPart = driver.id.toLowerCase();
                    
                    if (cleanLine.includes(normalizedDriverName) || cleanLine.includes(surname) || cleanLine.includes(idPart)) {
                        bestMatch = driver;
                    }
                });

                const row = document.getElementById(`row-${targetPos}`);
                const select = document.querySelector(`.driver-select[data-pos="${targetPos}"]`);
                const status = document.getElementById(`status-${targetPos}`);

                if (bestMatch && select) {
                    select.value = bestMatch.id;
                    row.style.opacity = '1';
                    row.style.borderColor = 'var(--accent)';
                    row.style.background = 'var(--accent-soft)';
                    status.textContent = "OK";
                    status.style.color = 'var(--live)';
                    status.style.fontWeight = '700';
                    detectedCount++;
                } else if (row) {
                    row.style.opacity = '0.5';
                    row.style.borderColor = 'var(--border)';
                    row.style.background = 'var(--surface)';
                    status.textContent = "Standby";
                    status.style.color = 'var(--text3)';
                    status.style.fontWeight = '500';
                }
            });

            document.getElementById('classificationCount').textContent = `${detectedCount} / ${driversList.length} DRIVERS DETECTED`;
        }

        async function submitResults() {
            const raceId = document.getElementById('raceSelect').value;
            if (!raceId) {
                alert('⚠️ YOU MUST SELECT A RACE FIRST (Step 1)!');
                return;
            }
            const results = {};
            document.querySelectorAll('.driver-select').forEach(s => {
                if (s.value) results[s.getAttribute('data-pos')] = s.value;
            });

            if (Object.keys(results).length === 0) {
                alert("Nothing to deploy! Paste some data first.");
                return;
            }

            document.getElementById('loadingOverlay').classList.remove('hidden');

            try {
                const response = await fetch('process-results.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        race_id: raceId,
                        results: results
                    })
                });

                const data = await response.json();
                if (data.success) {
                    alert("DEPLOYMENT SUCCESSFUL! Scores updated.");
                    window.location.href = '../dashboard.php';
                } else {
                    alert("Error: " + data.message);
                    document.getElementById('loadingOverlay').classList.add('hidden');
                }
            } catch (err) {
                alert("Critical failure during scoring.");
                document.getElementById('loadingOverlay').classList.add('hidden');
            }
        }
    </script>
</body>
</html>
