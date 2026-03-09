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
    <title>Race Control Portal - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-card {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .driver-slot {
            transition: all 0.3s ease;
        }
        .driver-slot:hover {
            border-color: rgba(249, 115, 22, 0.5);
            background: rgba(255, 255, 255, 0.05);
        }
        .input-dark {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
        }
        .input-dark:focus {
            border-color: #f97316;
            outline: none;
        }
    </style>
</head>
<body class="gaming-theme text-gray-200">

    <nav class="g-nav fixed w-full z-50 px-6 py-4 flex justify-between items-center bg-slate-900/80 backdrop-blur-md border-b border-white/5">
        <div class="flex items-center gap-4">
            <a href="../dashboard.php" class="flex items-center gap-4 hover:opacity-80 transition group">
                <div class="w-10 h-10 bg-gradient-to-br from-red-600 to-orange-500 rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/20">
                    <i class="fas fa-tower-broadcast text-white text-lg"></i>
                </div>
                <span class="font-bold text-xl tracking-wide text-white uppercase italic">RACE CONTROL</span>
            </a>
        </div>
        <div class="flex items-center gap-6">
            <a href="generate-debriefs.php" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-orange-600 to-orange-500 hover:from-orange-500 hover:to-orange-400 rounded-lg font-bold text-sm text-white shadow-lg shadow-orange-500/30 transition transform hover:scale-105 whitespace-nowrap">
                <i class="fas fa-wand-magic-sparkles"></i> Generate Debriefs
            </a>
            <a href="../dashboard.php" class="text-gray-400 hover:text-white transition text-sm">Return to Paddock</a>
        </div>
    </nav>

    <main class="pt-32 pb-20 px-4 md:px-8 max-w-6xl mx-auto">
        
        <div class="mb-12">
            <span class="bg-red-500 text-white text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest mb-4 inline-block">Manual Overide Mode</span>
            <h1 class="text-5xl font-black text-white italic uppercase">RACE <span class="g-text-gradient">COMMANDER</span></h1>
            <p class="text-gray-400 mt-2">Update race results, calculate points, and move the season forward.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Main Interface: Bulk Parser -->
            <div class="lg:col-span-3">
                <div class="admin-card p-8 rounded-[2rem] border-t-4 border-t-blue-500">
                    <!-- STEP 1: Race Selector -->
                    <div class="mb-8 p-6 bg-orange-500/10 border border-orange-500/30 rounded-2xl flex flex-col md:flex-row items-center gap-6">
                        <div class="flex items-center gap-3 text-orange-400 font-black uppercase text-sm tracking-widest whitespace-nowrap">
                            <i class="fas fa-flag-checkered"></i> STEP 1: Select Race
                        </div>
                        <select id="raceSelect" class="flex-grow p-4 rounded-xl input-dark font-bold text-white text-base">
                            <option value="">-- SELECT THE RACE YOU ARE UPDATING --</option>
                            <?php foreach ($races as $race): 
                                $statusLabel = $race['status'] === 'completed' ? '✅ DONE' : '🔓 UPCOMING';
                            ?>
                                <option value="<?php echo $race['id']; ?>">
                                    <?php echo $statusLabel; ?> — RD <?php echo $race['id']; ?> — <?php echo htmlspecialchars($race['race_name']); ?> (<?php echo date('M d', strtotime($race['race_date'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8">
                        <div>
                            <h2 class="text-3xl font-black text-white italic uppercase leading-none">STEP 2: ULTRA-PARSER <span class="text-blue-500">v2.0</span></h2>
                            <p class="text-gray-500 text-sm mt-2">DUMP THE WHOLE TABLE FROM BBC / SKY / ESPN BELOW. AUTO-DETECTS DRIVERS.</p>
                        </div>
                        <div class="flex gap-4">
                            <button onclick="clearParser()" class="px-6 py-3 bg-white/5 hover:bg-white/10 text-gray-400 font-bold rounded-xl transition text-xs uppercase tracking-widest">Clear Deck</button>
                            <div class="admin-card px-6 py-3 rounded-xl border-blue-500/30">
                                <span id="classificationCount" class="text-blue-400 font-black">0 / <?php echo count($drivers); ?> DRIVERS DETECTED</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Input Zone -->
                        <div class="space-y-4">
                            <div class="relative">
                                <div class="absolute top-4 left-4 text-[10px] font-black text-blue-500/50 uppercase tracking-[0.2em] pointer-events-none">Input Stream</div>
                                <textarea id="resultsPaste" 
                                    oninput="autoParse()"
                                    class="w-full h-[500px] p-8 pt-12 rounded-3xl input-dark text-sm font-mono leading-relaxed" 
                                    placeholder="PASTE THE WHOLE DAMN TABLE HERE...
Example:
1 George Russell Mercedes 1:30:11
2 Kimi Antonelli Mercedes +2.2
3 Charles Leclerc Ferrari +5.5
..."></textarea>
                            </div>
                        </div>

                        <!-- Detection Sync Zone -->
                        <div class="space-y-4">
                            <div id="resultsGrid" class="space-y-2 h-[500px] overflow-y-auto pr-2 custom-scrollbar">
                                <?php for ($i = 1; $i <= count($drivers); $i++): ?>
                                    <div id="row-<?php echo $i; ?>" class="driver-slot flex items-center gap-4 p-3 rounded-2xl border border-white/5 bg-white/5 opacity-40">
                                        <span class="w-10 text-center font-black text-white italic text-xl h-10 flex items-center justify-center bg-black/40 rounded-lg">#<?php echo $i; ?></span>
                                        <div class="flex-grow flex items-center gap-3">
                                            <select data-pos="<?php echo $i; ?>" class="driver-select flex-grow p-3 rounded-xl input-dark text-sm font-bold appearance-none">
                                                <option value="">-- NO DRIVER DETECTED --</option>
                                                <?php foreach ($drivers as $d): ?>
                                                    <option value="<?php echo $d['id']; ?>">
                                                        <?php echo htmlspecialchars($d['driver_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div id="status-<?php echo $i; ?>" class="text-[10px] font-black uppercase text-gray-600 tracking-widest">Standby</div>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mt-12 flex flex-col md:flex-row items-center gap-8 bg-blue-500/5 p-8 rounded-3xl border border-blue-500/10">
                        <div class="flex-grow">
                            <h3 class="text-xl font-black text-white uppercase italic">Ready for Launch?</h3>
                            <p class="text-sm text-gray-500">Double check the grid above. The system will calculate scores for all engineers once you hit the button.</p>
                        </div>
                        <div class="w-full md:w-auto">
                            <button onclick="submitResults()" id="submitBtn" class="whitespace-nowrap px-12 py-5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-black text-xl rounded-2xl shadow-2xl shadow-blue-500/20 transition-all transform hover:scale-[1.05] active:scale-95 flex items-center gap-4">
                                <i class="fas fa-rocket"></i> DEPLOY CLASSIFICATION
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </main>

    <div id="loadingOverlay" class="fixed inset-0 bg-black/90 backdrop-blur-xl z-[100] hidden flex flex-col items-center justify-center">
        <div class="w-20 h-20 border-4 border-orange-500 border-t-transparent rounded-full animate-spin mb-6"></div>
        <p class="text-white font-black text-2xl animate-pulse italic uppercase">Processing Telemetry...</p>
    </div>

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
                    row.classList.remove('opacity-40');
                    row.classList.add('border-blue-500/50', 'bg-blue-500/10');
                    status.textContent = "DETECTED";
                    status.className = "text-[10px] font-black uppercase text-blue-400 tracking-widest";
                    detectedCount++;
                } else if (row) {
                    row.classList.add('opacity-40');
                    row.classList.remove('border-blue-500/50', 'bg-blue-500/10');
                    status.textContent = "STANDBY";
                    status.className = "text-[10px] font-black uppercase text-gray-600 tracking-widest";
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
