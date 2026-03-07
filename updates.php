<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/avatars.php';

$user = getCurrentUser();
if (!$user) {
    header('Location: index.php');
    exit;
}

$db = getDB();
$userId = $user['id'];

// Get Dynamic User Lists for the "Engineer Roll Call" & "Prediction Track"
// 1. Random Engineers for the "Featured" section
$stmt = $db->query("SELECT username FROM users ORDER BY RAND() LIMIT 4");
$randomEngineers = $stmt->fetch_all(MYSQLI_ASSOC);

// 2. Prediction Status for the Next Race (Australia 2026)
$nextRace = getNextRace();
$submitted = [];
$missing = [];
if ($nextRace) {
    $raceId = $nextRace['id'];
    
    // Users who HAVE submitted
    $stmt = $db->prepare("SELECT DISTINCT u.username FROM users u JOIN predictions p ON u.id = p.user_id WHERE p.race_id = ?");
    $stmt->bind_param("i", $raceId);
    $stmt->execute();
    $submitted = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Users who HAVE NOT submitted
    $stmt = $db->prepare("SELECT username FROM users WHERE id NOT IN (SELECT DISTINCT user_id FROM predictions WHERE race_id = ?)");
    $stmt->bind_param("i", $raceId);
    $stmt->execute();
    $missing = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Race Updates - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/gaming-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .update-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }
        .shocker-border {
            border-left: 4px solid #ef4444;
            background: rgba(239, 68, 68, 0.05);
        }
        .vibration-text {
            animation: shake 0.15s infinite;
            display: inline-block;
        }
        @keyframes shake {
            0% { transform: translate(1px, 1px) rotate(0deg); }
            10% { transform: translate(-1px, -2px) rotate(-1deg); }
            20% { transform: translate(-3px, 0px) rotate(1deg); }
            30% { transform: translate(3px, 2px) rotate(0deg); }
            40% { transform: translate(1px, -1px) rotate(1deg); }
            50% { transform: translate(-1px, 2px) rotate(-1deg); }
            60% { transform: translate(-3px, 1px) rotate(0deg); }
            70% { transform: translate(3px, 1px) rotate(-1deg); }
            80% { transform: translate(-1px, -1px) rotate(1deg); }
            90% { transform: translate(1px, 2px) rotate(0deg); }
            100% { transform: translate(1px, -2px) rotate(-1deg); }
        }
    </style>
</head>
<body class="gaming-theme text-gray-200">

    <!-- Navbar -->
    <nav class="g-nav fixed w-full z-50 px-6 py-4 flex justify-between items-center bg-slate-900/80 backdrop-blur-md border-b border-white/5">
        <div class="flex items-center gap-4">
            <a href="dashboard.php" class="flex items-center gap-4 hover:opacity-80 transition group">
                <div class="w-10 h-10 bg-gradient-to-br from-red-600 to-orange-500 rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/20 group-hover:scale-105 transition-transform">
                    <i class="fas fa-flag-checkered text-white text-lg"></i>
                </div>
                <span class="font-bold text-xl tracking-wide text-white uppercase italic">PADDOCK PICKS</span>
            </a>
        </div>
        
        <div class="flex items-center gap-6">
            <div class="hidden lg:flex items-center gap-8 text-xs uppercase tracking-[0.2em] font-black">
                <a href="dashboard.php" class="text-gray-400 hover:text-white transition">Dashboard</a>
                <a href="updates.php" class="text-orange-500 border-b-2 border-orange-500 pb-1">Race Updates</a>
                <a href="leaderboard.php" class="text-gray-400 hover:text-white transition">Leaderboard</a>
            </div>
            
            <div class="flex items-center gap-3 pl-6 border-l border-white/10">
                <a href="profile.php" class="w-10 h-10 rounded-full bg-slate-700 border-2 border-white/10 overflow-hidden hover:border-blue-500 transition">
                    <img src="<?php echo getAvatarUrl($user['avatar_style'] ?? 'avataaars', $user['username']); ?>" alt="Avatar" class="w-full h-full">
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-32 pb-20 px-4 md:px-8 max-w-6xl mx-auto">
        
        <div class="mb-12">
            <span class="bg-orange-500 text-white text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest mb-4 inline-block">Official Paddock Dispatch</span>
            <h1 class="text-5xl md:text-7xl font-black text-white italic uppercase leading-tight">WELCOME TO THE <br><span class="g-text-gradient underline decoration-orange-500">SCANERRIFIC LEAGUE!</span></h1>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left & Center: Main Story -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Intro Card -->
                <div class="update-card p-8 rounded-[2rem] border-l-8 border-orange-500">
                    <p class="text-xl text-gray-300 leading-relaxed italic">
                        "Congratulations, Race Engineers! You've successfully signed up for Paddock Picks—the most competitive (and currently most confused) corner of the office." 🏎️
                    </p>
                    <div class="mt-6 text-gray-400 leading-relaxed font-light">
                        The 2026 season is officially here, and the "technical reset" has worked—if the goal was to make everyone’s head spin. Between the new active aero, the 50/50 hybrid power split, and the fact that we have 22 cars on the grid now, I think we’ve all realized that 2025 was a walk in the park.
                    </div>
                </div>

                <!-- Qualy Card -->
                <div class="update-card p-8 rounded-[2rem]">
                    <h2 class="text-3xl font-black text-white italic uppercase mb-6 flex items-center gap-3">
                        <i class="fas fa-stopwatch text-orange-500"></i> Qualifying Debrief
                    </h2>
                    
                    <div class="bg-blue-600/10 p-6 rounded-2xl border border-blue-500/20 mb-6">
                        <h3 class="text-blue-400 font-black uppercase text-sm mb-2 italic">The Mercedes Masterclass</h3>
                        <p class="text-gray-300">If you had "Mercedes Front Row Lockout" on your bingo card, go collect your prize. **George Russell** snatched the first pole of the era, with the Italian prodigy **Kimi Antonelli** right beside him in P2.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="shocker-border p-5 rounded-r-xl">
                            <h4 class="text-red-400 font-black uppercase text-xs mb-1">The Shockers</h4>
                            <p class="text-sm text-gray-400">**Max Verstappen is starting P20.** Yes, you read that right. A massive Q1 crash means the 4-time champ is starting from the back. If you picked him for a P1 finish, your "Precision Bonus" is currently crying in the garage.</p>
                        </div>
                        <div class="bg-white/5 p-5 rounded-xl border border-white/5">
                            <h4 class="text-white font-black uppercase text-xs mb-1">The Hadjar Hype</h4>
                            <p class="text-sm text-gray-400">Red Bull’s new boy **Isack Hadjar** snagged P3. Not a bad way to debut for the senior team.</p>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-white/5 flex flex-wrap gap-4">
                        <div class="text-xs font-bold text-gray-500 uppercase tracking-widest">Lurking:</div>
                        <span class="bg-red-600/10 text-red-500 text-xs font-black px-2 py-1 rounded">LECLERC P4</span>
                        <span class="bg-orange-600/10 text-orange-500 text-xs font-black px-2 py-1 rounded">PIASTRI P5</span>
                        <span class="bg-orange-600/10 text-orange-500 text-xs font-black px-2 py-1 rounded">NORRIS P6</span>
                        <span class="bg-red-600/10 text-red-500 text-xs font-black px-2 py-1 rounded">HAMILTON P7</span>
                    </div>
                </div>

                <!-- Vibration Section -->
                <div class="update-card p-8 rounded-[2rem] border border-red-500/20 relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition duration-1000">
                        <i class="fas fa-radiation text-8xl text-red-500"></i>
                    </div>
                    <h3 class="text-xl font-black text-white uppercase italic mb-4 flex items-center gap-2">
                        <i class="fas fa-exclamation-triangle text-red-500"></i> Technical Bulletin: The "Vibration" Issue
                    </h3>
                    <div class="space-y-4 text-gray-400 leading-relaxed">
                        <p>A quick note for those who ignored the FP2 warnings: **Aston Martin is indeed in "dire trouble."** Fernando Alonso and Lance Stroll are essentially driving high-speed paint mixers.</p>
                        <p>With mirrors falling off and drivers reporting "hand numbness" from the Honda <span class="vibration-text text-red-400 font-bold">vibrations</span>, surviving 58 laps tomorrow will be a miracle.</p>
                        <p class="text-sm italic border-l-2 border-white/10 pl-4 py-1">"If they finish in the points, Adrian Newey is officially a wizard."</p>
                    </div>
                </div>

            </div>

            <!-- Right Column: Stats & Meta -->
            <div class="space-y-8">
                
                <!-- Roll Call -->
                <div class="update-card p-6 rounded-[2rem]">
                    <h3 class="text-sm font-black text-white uppercase tracking-widest mb-6 flex items-center gap-2">
                        <i class="fas fa-id-card text-blue-500"></i> Engineer Roll Call
                    </h3>
                    <div class="space-y-4 mb-6">
                        <div class="bg-white/5 p-4 rounded-xl border border-white/5">
                            <div class="text-blue-400 font-black italic text-xs mb-1">@ANGRYCUBE</div>
                            <div class="text-[10px] text-gray-500 font-bold leading-tight">Presumably how you feel after seeing Verstappen’s Q1 lap.</div>
                        </div>
                        <div class="bg-white/5 p-4 rounded-xl border border-white/5">
                            <div class="text-blue-400 font-black italic text-xs mb-1">@TESTUSER</div>
                            <div class="text-[10px] text-gray-500 font-bold leading-tight">We see you. We hope your predictions are better than your naming creativity.</div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 pt-4 border-t border-white/5">
                        <?php foreach ($randomEngineers as $eng): ?>
                            <span class="text-[10px] text-gray-600 font-bold italic">@<?php echo htmlspecialchars($eng['username']); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Prediction Track -->
                <div class="update-card p-6 rounded-[2rem] border-t-4 border-t-green-500">
                    <h3 class="text-sm font-black text-white uppercase tracking-widest mb-6">Track Status: Submissions</h3>
                    
                    <div class="space-y-6">
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-[10px] font-black text-green-500 tracking-tighter italic uppercase">Ready on Grid</span>
                                <span class="text-[10px] text-gray-600 font-bold uppercase"><?php echo count($submitted); ?> Users</span>
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                <?php foreach ($submitted as $s): ?>
                                    <span class="bg-green-500/10 text-green-500 text-[10px] font-black px-2 py-0.5 rounded border border-green-500/20 uppercase italic">@<?php echo htmlspecialchars($s['username']); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-[10px] font-black text-red-500 tracking-tighter italic uppercase">Missing Data</span>
                                <span class="text-[10px] text-gray-600 font-bold uppercase"><?php echo count($missing); ?> Users</span>
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                <?php foreach ($missing as $m): ?>
                                    <span class="bg-white/5 text-gray-500 text-[10px] font-black px-2 py-0.5 rounded border border-white/5 uppercase italic line-through opacity-40">@<?php echo htmlspecialchars($m['username']); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Achievements -->
                <div class="update-card p-8 rounded-[2rem] bg-gradient-to-br from-indigo-600/20 to-transparent">
                    <div class="w-12 h-12 bg-white text-slate-900 rounded-xl flex items-center justify-center text-xl mb-6 shadow-xl">
                        <i class="fas fa-award"></i>
                    </div>
                    <h3 class="text-xl font-black text-white italic mb-3">Flex the Hardware</h3>
                    <p class="text-xs text-gray-400 leading-relaxed mb-6">
                        Unlock badges based on accuracy and streaks. Bragging rights are the only currency we accept here, but we’ve got **Real Trophies** for the season winners!
                    </p>
                    <a href="achievements.php" class="g-btn g-btn-blue w-full py-4 text-xs font-black uppercase tracking-widest shadow-lg shadow-blue-500/10">GO TO ACHIEVEMENTS</a>
                </div>

                <!-- Call to Action -->
                <div class="pt-10">
                    <a href="predict.php?race_id=<?php echo $nextRace['id'] ?? 1; ?>" class="g-btn bg-white text-slate-950 px-12 py-5 font-black text-xl rounded-2xl flex items-center justify-center gap-4 hover:gap-6 transition-all hover:scale-105 shadow-2xl shadow-white/10 group w-full">
                        LOCK IN YOUR GRID <i class="fas fa-chevron-right transition-transform group-hover:translate-x-1"></i>
                    </a>
                </div>

            </div>
        </div>

        <!-- Footer / Signoff -->
        <div class="mt-12 text-center md:text-left flex flex-col md:flex-row items-center gap-8 bg-slate-900/40 p-10 rounded-[2rem] border border-white/5 border-b-4 border-b-orange-500">
            <div class="w-20 h-20 rounded-2xl bg-orange-500/20 flex items-center justify-center text-orange-500 text-3xl shadow-2xl border border-orange-500/30">
                <i class="fas fa-user-tie"></i>
            </div>
            <div>
                <p class="text-lg text-gray-400 italic mb-4">
                    "Good luck to everyone. I'll be monitoring the telemetry and will see you for the **Post-Race Briefing** tomorrow. Keep it clean in Turn 1!"
                </p>
                <div class="font-black italic text-white text-xl uppercase leading-none">Aurimas <span class="text-orange-500 font-light ml-2">Race Controller</span></div>
                <div class="text-[10px] text-gray-600 font-black uppercase tracking-[0.4em] mt-2">Scanerrific Paddock Picks • Competition Bureau</div>
            </div>
        </div>

    </main>

    <footer class="mt-20 border-t border-white/5 py-12 text-center text-gray-700 font-bold text-[10px] uppercase tracking-[0.6em]">
        &copy; 2026 Paddock Picks • Melbourne Grand Prix Edition
    </footer>

</body>
</html>
