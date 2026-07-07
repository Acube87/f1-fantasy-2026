<?php
require_once __DIR__ . '/includes/maintenance-gate.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/avatars.php';

$user = getCurrentUser();
$currentUser = $user;
$stats = $user ? getUserStats($user['id']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Points System — <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/gaming-style.css">
    <style>
        :root {
            --f1-red:    #ff0077;
            --f1-green:  #00ff88;
            --f1-cyan:   #00e5ff;
            --f1-orange: #ff6a00;
            --f1-gold:   #ffd700;
            --f1-purple: #8b5cf6;
            --bg:        #0a0a0a;
            --bg2:       #111111;
            --text:      #f1f5f9;
            --muted:     #475569;
        }
        * { box-sizing: border-box; }
        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ── Animations ───────────────────────────────────── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(28px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; } to { opacity: 1; }
        }
        @keyframes popIn {
            0%   { opacity: 0; transform: scale(0.6) translateY(10px); }
            70%  { transform: scale(1.06) translateY(-2px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }
        @keyframes countUp {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes neonPulse {
            0%,100% { text-shadow: 0 0 10px currentColor, 0 0 30px currentColor; }
            50%     { text-shadow: 0 0 20px currentColor, 0 0 60px currentColor, 0 0 100px currentColor; }
        }
        @keyframes borderGlow {
            0%,100% { box-shadow: 0 0 10px rgba(255,0,119,0.2), inset 0 0 10px rgba(255,0,119,0.03); }
            50%     { box-shadow: 0 0 30px rgba(255,0,119,0.45), inset 0 0 20px rgba(255,0,119,0.08); }
        }
        @keyframes doubleGlow {
            0%,100% { box-shadow: 0 0 20px rgba(139,92,246,0.3), 0 0 40px rgba(0,229,255,0.15); }
            50%     { box-shadow: 0 0 40px rgba(139,92,246,0.6), 0 0 80px rgba(0,229,255,0.35); }
        }
        @keyframes slideLeft {
            from { opacity: 0; transform: translateX(-20px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes slideRight {
            from { opacity: 0; transform: translateX(20px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes scanline {
            0%   { transform: translateY(-100%); }
            100% { transform: translateY(100vh); }
        }
        @keyframes gridMove {
            from { background-position: 0 0; }
            to   { background-position: 60px 60px; }
        }
        @keyframes float {
            0%,100% { transform: translateY(0); }
            50%     { transform: translateY(-8px); }
        }
        @keyframes barGrow {
            from { width: 0; }
            to   { width: var(--bar-w); }
        }
        @keyframes rowIn {
            from { opacity: 0; transform: translateX(-10px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes sparkle {
            0%,100% { opacity: 0; transform: scale(0); }
            50%     { opacity: 1; transform: scale(1); }
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }

        /* ── Hero ─────────────────────────────────────────── */
        .hero {
            position: relative; min-height: 420px;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
        }
        .hero-grid {
            position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(255,0,119,0.07) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,0,119,0.07) 1px, transparent 1px);
            background-size: 60px 60px;
            animation: gridMove 8s linear infinite;
        }
        .hero-glow {
            position: absolute; inset: 0;
            background: radial-gradient(ellipse 70% 60% at 50% 50%, rgba(255,0,119,0.12) 0%, transparent 70%);
        }
        .hero-scanline {
            position: absolute; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, transparent, rgba(255,0,119,0.4), transparent);
            animation: scanline 4s linear infinite;
        }
        .hero-content { position: relative; z-index: 2; text-align: center; padding: 80px 24px 60px; }
        .hero-eyebrow {
            font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.18em;
            color: var(--f1-red); margin-bottom: 16px;
            animation: fadeIn 0.6s ease both;
        }
        .hero-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(3.5rem, 10vw, 7rem);
            letter-spacing: -0.01em; line-height: 0.9;
            color: #fff;
            animation: fadeUp 0.7s ease 0.1s both;
        }
        .hero-title span {
            color: var(--f1-red);
            animation: neonPulse 3s ease-in-out infinite;
        }
        .hero-subtitle {
            font-size: 1rem; color: #64748b; margin-top: 16px; max-width: 500px; margin-inline: auto;
            animation: fadeUp 0.7s ease 0.25s both;
        }
        .hero-chips {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            flex-wrap: wrap; margin-top: 28px;
            animation: fadeUp 0.7s ease 0.35s both;
        }
        .hero-chip {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 7px 16px; border-radius: 999px;
            font-size: 0.75rem; font-weight: 700;
            border: 1px solid; letter-spacing: 0.02em;
        }
        .chip-green  { color: var(--f1-green);  border-color: rgba(0,255,136,0.3);  background: rgba(0,255,136,0.07); }
        .chip-orange { color: var(--f1-orange); border-color: rgba(255,106,0,0.3);  background: rgba(255,106,0,0.07); }
        .chip-purple { color: var(--f1-purple); border-color: rgba(139,92,246,0.3); background: rgba(139,92,246,0.07); }
        .chip-cyan   { color: var(--f1-cyan);   border-color: rgba(0,229,255,0.3);  background: rgba(0,229,255,0.07); }

        /* ── Layout ───────────────────────────────────────── */
        .page-body { max-width: 1020px; margin: 0 auto; padding: 0 20px 80px; }

        /* ── Section label ────────────────────────────────── */
        .section-eyebrow {
            font-size: 0.6rem; font-weight: 800; text-transform: uppercase;
            letter-spacing: 0.15em; color: var(--f1-red); margin-bottom: 6px;
        }
        .section-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 2.2rem; letter-spacing: 0.02em; line-height: 1;
            color: #fff; margin-bottom: 6px;
        }
        .section-sub { font-size: 0.85rem; color: #64748b; }

        /* ── Cards ────────────────────────────────────────── */
        .card {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 18px; overflow: hidden;
        }
        .card-accent-red    { border-top: 2px solid var(--f1-red); }
        .card-accent-green  { border-top: 2px solid var(--f1-green); }
        .card-accent-orange { border-top: 2px solid var(--f1-orange); }
        .card-accent-purple { border-top: 2px solid var(--f1-purple); }
        .card-accent-gold   { border-top: 2px solid var(--f1-gold); }

        /* ── Points table ─────────────────────────────────── */
        .pts-table { width: 100%; border-collapse: collapse; }
        .pts-table th {
            padding: 10px 14px; font-size: 0.58rem; font-weight: 800;
            text-transform: uppercase; letter-spacing: 0.1em; color: #334155;
            border-bottom: 1px solid rgba(255,255,255,0.05); text-align: left;
        }
        .pts-table th.right { text-align: right; }
        .pts-row {
            border-bottom: 1px solid rgba(255,255,255,0.035);
            animation: rowIn 0.4s ease both;
            transition: background 0.15s;
        }
        .pts-row:hover { background: rgba(255,255,255,0.025); }
        .pts-row:last-child { border-bottom: none; }
        .pts-row.podium-row { background: rgba(255,215,0,0.03); }
        .pts-row td { padding: 10px 14px; vertical-align: middle; }

        .pos-badge {
            display: inline-flex; align-items: center; justify-content: center;
            width: 38px; height: 38px; border-radius: 9px;
            font-family: 'Bebas Neue', sans-serif; font-size: 1rem; font-weight: 900;
        }
        .pos-badge.p1  { background: rgba(255,215,0,0.12); color: var(--f1-gold); border: 1px solid rgba(255,215,0,0.28); }
        .pos-badge.p2  { background: rgba(192,192,192,0.09); color: #c0c8d8; border: 1px solid rgba(192,192,192,0.2); }
        .pos-badge.p3  { background: rgba(205,127,50,0.1); color: #e09060; border: 1px solid rgba(205,127,50,0.25); }
        .pos-badge.top { background: rgba(255,106,0,0.08); color: #ff8040; border: 1px solid rgba(255,106,0,0.18); }
        .pos-badge.out { background: rgba(255,255,255,0.04); color: #475569; border: 1px solid rgba(255,255,255,0.08); }

        .bar-wrap { height: 6px; border-radius: 999px; background: rgba(255,255,255,0.05); min-width: 80px; overflow: hidden; }
        .bar-fill {
            height: 100%; border-radius: 999px;
            background: linear-gradient(90deg, var(--f1-red), var(--f1-orange));
            animation: barGrow 0.8s ease both;
            animation-delay: var(--bar-delay, 0.3s);
        }

        .pts-val {
            font-family: 'Bebas Neue', sans-serif; font-size: 1.3rem; letter-spacing: 0.02em;
        }
        .bonus-tag {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 2px 8px; border-radius: 999px; font-size: 0.58rem; font-weight: 800;
            text-transform: uppercase; letter-spacing: 0.06em;
        }
        .tag-green  { background: rgba(0,255,136,0.1); color: var(--f1-green); border: 1px solid rgba(0,255,136,0.2); }
        .tag-orange { background: rgba(255,106,0,0.1); color: var(--f1-orange); border: 1px solid rgba(255,106,0,0.2); }
        .tag-gray   { background: rgba(255,255,255,0.04); color: #475569; border: 1px solid rgba(255,255,255,0.08); }

        /* ── Bonus cards ──────────────────────────────────── */
        .bonus-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 640px) { .bonus-grid { grid-template-columns: 1fr; } }

        .bonus-card {
            border-radius: 18px; padding: 28px 24px;
            border: 1px solid rgba(255,255,255,0.07);
            position: relative; overflow: hidden;
        }
        .bonus-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
        }
        .bonus-card.bc-orange::before { background: var(--f1-orange); }
        .bonus-card.bc-blue::before   { background: #3b82f6; }
        .bonus-num {
            font-family: 'Bebas Neue', sans-serif; font-size: 3.5rem;
            letter-spacing: -0.02em; line-height: 1;
            animation: popIn 0.6s cubic-bezier(0.175,0.885,0.32,1.275) 0.3s both;
        }
        .bonus-icon { font-size: 2rem; margin-bottom: 12px; display: block; animation: float 3s ease-in-out infinite; }

        /* ── Double points ────────────────────────────────── */
        .double-hero {
            border-radius: 20px; padding: 40px 32px;
            background: linear-gradient(135deg, rgba(139,92,246,0.12) 0%, rgba(0,229,255,0.08) 100%);
            border: 1px solid rgba(139,92,246,0.25);
            position: relative; overflow: hidden;
            animation: doubleGlow 3s ease-in-out infinite;
        }
        .double-hero::before {
            content: '2×'; position: absolute; right: 24px; top: 50%; transform: translateY(-50%);
            font-family: 'Bebas Neue', sans-serif; font-size: 9rem; color: rgba(139,92,246,0.08);
            line-height: 1; pointer-events: none; letter-spacing: -0.04em;
        }
        .double-title {
            font-family: 'Bebas Neue', sans-serif; font-size: 2.5rem;
            letter-spacing: 0.02em; line-height: 1; margin-bottom: 8px;
            background: linear-gradient(90deg, var(--f1-purple), var(--f1-cyan));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .track-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-top: 24px; }
        @media (max-width: 560px) { .track-cards { grid-template-columns: 1fr; } }

        .track-card {
            border-radius: 14px; padding: 22px 16px; text-align: center;
            background: rgba(0,0,0,0.4); border: 1px solid rgba(139,92,246,0.2);
            position: relative; overflow: hidden; transition: transform 0.2s, border-color 0.2s;
            animation: popIn 0.5s cubic-bezier(0.175,0.885,0.32,1.275) both;
        }
        .track-card:hover { transform: translateY(-4px); border-color: rgba(139,92,246,0.5); }
        .track-card::after {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, var(--f1-purple), var(--f1-cyan));
        }
        .track-flag { font-size: 2.5rem; display: block; margin-bottom: 8px; }
        .track-name { font-weight: 800; font-size: 0.95rem; color: #fff; margin-bottom: 4px; }
        .track-badge {
            display: inline-flex; align-items: center; gap: 5px;
            background: linear-gradient(90deg, rgba(139,92,246,0.2), rgba(0,229,255,0.2));
            border: 1px solid rgba(139,92,246,0.3);
            color: #c084fc; font-size: 0.65rem; font-weight: 800;
            padding: 3px 10px; border-radius: 999px;
            text-transform: uppercase; letter-spacing: 0.07em; margin-top: 8px;
        }

        /* ── Example calculator ───────────────────────────── */
        .example-grid {
            display: grid; grid-template-columns: 1fr 1px 1fr; gap: 0;
        }
        @media (max-width: 640px) {
            .example-grid { grid-template-columns: 1fr; }
            .example-divider { display: none; }
        }
        .example-divider { background: rgba(255,255,255,0.06); }
        .example-col { padding: 28px 24px; }
        .example-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 9px 12px; border-radius: 8px; margin-bottom: 6px;
            font-size: 0.85rem;
            animation: rowIn 0.35s ease both;
        }
        .example-row.er-exact  { background: rgba(0,255,136,0.07); border: 1px solid rgba(0,255,136,0.15); }
        .example-row.er-miss   { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); }
        .example-row.er-bonus  { background: rgba(255,106,0,0.08); border: 1px solid rgba(255,106,0,0.18); }
        .example-row.er-cons   { background: rgba(59,130,246,0.08); border: 1px solid rgba(59,130,246,0.18); }
        .example-row.er-total  { background: rgba(0,255,136,0.1); border: 1px solid rgba(0,255,136,0.25); font-weight: 800; }

        .er-pts {
            font-family: 'Bebas Neue', sans-serif; font-size: 1.1rem; letter-spacing: 0.02em;
            animation: popIn 0.4s cubic-bezier(0.175,0.885,0.32,1.275) both;
        }

        .total-banner {
            background: linear-gradient(135deg, rgba(0,255,136,0.08), rgba(0,229,255,0.05));
            border: 1px solid rgba(0,255,136,0.2);
            border-radius: 14px; padding: 20px; text-align: center; margin-top: 16px;
        }
        .total-num {
            font-family: 'Bebas Neue', sans-serif; font-size: 4rem; color: var(--f1-green);
            line-height: 1; letter-spacing: -0.02em;
            animation: popIn 0.6s cubic-bezier(0.175,0.885,0.32,1.275) 0.5s both;
        }
        .double-banner {
            background: linear-gradient(135deg, rgba(139,92,246,0.12), rgba(0,229,255,0.08));
            border: 1px solid rgba(139,92,246,0.25);
            border-radius: 14px; padding: 20px; text-align: center; margin-top: 12px;
        }
        .double-num {
            font-family: 'Bebas Neue', sans-serif; font-size: 4rem;
            background: linear-gradient(90deg, var(--f1-purple), var(--f1-cyan));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
            line-height: 1; letter-spacing: -0.02em;
            animation: popIn 0.6s cubic-bezier(0.175,0.885,0.32,1.275) 0.7s both;
        }

        /* ── Tips ─────────────────────────────────────────── */
        .tips-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        @media (max-width: 640px) { .tips-grid { grid-template-columns: 1fr; } }
        .tip-card {
            border-radius: 14px; padding: 20px; position: relative; overflow: hidden;
            background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.07);
            animation: popIn 0.5s cubic-bezier(0.175,0.885,0.32,1.275) both;
            transition: transform 0.2s, border-color 0.2s;
        }
        .tip-card:hover { transform: translateY(-3px); border-color: rgba(255,255,255,0.14); }
        .tip-icon { font-size: 1.5rem; margin-bottom: 10px; display: block; }
        .tip-title { font-weight: 800; font-size: 0.9rem; color: #fff; margin-bottom: 6px; }
        .tip-text  { font-size: 0.78rem; color: #64748b; line-height: 1.5; }

        /* ── CTA ──────────────────────────────────────────── */
        .cta-btn {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 14px 32px; border-radius: 12px; font-weight: 800; font-size: 0.95rem;
            text-decoration: none; transition: all 0.2s; letter-spacing: 0.01em;
        }
        .cta-primary {
            background: var(--f1-red);
            color: #fff;
            box-shadow: 0 4px 24px rgba(255,0,119,0.35);
        }
        .cta-primary:hover { background: #e60068; transform: translateY(-2px); box-shadow: 0 8px 32px rgba(255,0,119,0.5); }
        .cta-outline {
            border: 1px solid rgba(255,255,255,0.15); color: #94a3b8;
        }
        .cta-outline:hover { border-color: rgba(255,255,255,0.3); color: #fff; }

        /* scroll reveal */
        .reveal { opacity: 0; transform: translateY(24px); transition: opacity 0.6s ease, transform 0.6s ease; }
        .reveal.visible { opacity: 1; transform: none; }
    </style>
</head>
<body>

    <?php require_once __DIR__ . '/includes/nav.php'; ?>

    <!-- Hero -->
    <div class="hero">
        <div class="hero-grid"></div>
        <div class="hero-glow"></div>
        <div class="hero-scanline"></div>
        <div class="hero-content">
            <div class="hero-eyebrow"><i class="fas fa-bolt"></i> &nbsp;Paddock Picks</div>
            <h1 class="hero-title">Points <span>System</span></h1>
            <p class="hero-subtitle">Master the scoring, dominate the leaderboard. Every position counts.</p>
            <div class="hero-chips">
                <span class="hero-chip chip-green"><i class="fas fa-crosshairs"></i> Exact Match Bonus</span>
                <span class="hero-chip chip-orange"><i class="fas fa-medal"></i> Podium Sweep +10</span>
                <span class="hero-chip chip-purple"><i class="fas fa-bolt"></i> 2× Double Points</span>
                <span class="hero-chip chip-cyan"><i class="fas fa-wrench"></i> Constructor +5</span>
            </div>
        </div>
    </div>

    <div class="page-body">

        <!-- ── How it works – quick summary ──────────────── -->
        <section class="reveal" style="margin-bottom:56px;">
            <div style="text-align:center;margin-bottom:28px;">
                <div class="section-eyebrow">The Formula</div>
                <div class="section-title">How Points Are Scored</div>
                <div class="section-sub">Predict exactly right and stack those bonuses</div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;">
                <?php
                $pillars = [
                    ['icon'=>'fas fa-crosshairs','color'=>'#00ff88','bg'=>'rgba(0,255,136,0.1)','title'=>'Exact Position','sub'=>'Predict a driver\'s exact finishing position','val'=>'F1 pts + 3'],
                    ['icon'=>'fas fa-medal','color'=>'#ff6a00','bg'=>'rgba(255,106,0,0.1)','title'=>'Podium Sweep','sub'=>'Get P1, P2 &amp; P3 all correct in order','val'=>'+10 bonus'],
                    ['icon'=>'fas fa-wrench','color'=>'#3b82f6','bg'=>'rgba(59,130,246,0.1)','title'=>'Constructor','sub'=>'Predict the top constructor by driver points','val'=>'+5 bonus'],
                    ['icon'=>'fas fa-bolt','color'=>'#a855f7','bg'=>'rgba(168,85,247,0.1)','title'=>'Double Points','sub'=>'China · UK · Singapore multiply everything','val'=>'× 2'],
                ];
                foreach ($pillars as $d => $p): ?>
                <div class="card" style="padding:24px;text-align:center;animation:popIn 0.5s cubic-bezier(0.175,0.885,0.32,1.275) <?php echo $d*0.1; ?>s both;">
                    <div style="width:48px;height:48px;border-radius:12px;background:<?php echo $p['bg']; ?>;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:1.2rem;color:<?php echo $p['color']; ?>;">
                        <i class="<?php echo $p['icon']; ?>"></i>
                    </div>
                    <div style="font-weight:800;font-size:0.9rem;color:#fff;margin-bottom:5px;"><?php echo $p['title']; ?></div>
                    <div style="font-size:0.72rem;color:#475569;margin-bottom:12px;line-height:1.45;"><?php echo $p['sub']; ?></div>
                    <div style="font-family:'Bebas Neue',sans-serif;font-size:1.4rem;color:<?php echo $p['color']; ?>;letter-spacing:0.02em;"><?php echo $p['val']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- ── Position points table ──────────────────────── -->
        <section class="reveal" style="margin-bottom:56px;">
            <div style="margin-bottom:24px;">
                <div class="section-eyebrow">The Breakdown</div>
                <div class="section-title">Position Points Table</div>
                <div class="section-sub">F1 standard base points + 3 strategy bonus for every exact hit</div>
            </div>
            <div class="card">
                <table class="pts-table">
                    <thead>
                        <tr>
                            <th style="width:60px">Pos</th>
                            <th>Driver</th>
                            <th class="right" style="min-width:80px">F1 Base</th>
                            <th class="right" style="min-width:80px">+Exact</th>
                            <th class="right" style="min-width:80px">Max Total</th>
                            <th style="min-width:120px;padding-left:6px;">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $pts_map = [25,18,15,12,10,8,6,4,2,1];
                    $positions = [
                        ['pos'=>1,'emoji'=>'🥇','label'=>'Race Winner',    'cls'=>'p1' ],
                        ['pos'=>2,'emoji'=>'🥈','label'=>'2nd Place',      'cls'=>'p2' ],
                        ['pos'=>3,'emoji'=>'🥉','label'=>'3rd Place',      'cls'=>'p3' ],
                        ['pos'=>4,'emoji'=>'','label'=>'4th Place',       'cls'=>'top'],
                        ['pos'=>5,'emoji'=>'','label'=>'5th Place',       'cls'=>'top'],
                        ['pos'=>6,'emoji'=>'','label'=>'6th Place',       'cls'=>'top'],
                        ['pos'=>7,'emoji'=>'','label'=>'7th Place',       'cls'=>'top'],
                        ['pos'=>8,'emoji'=>'','label'=>'8th Place',       'cls'=>'top'],
                        ['pos'=>9,'emoji'=>'','label'=>'9th Place',       'cls'=>'top'],
                        ['pos'=>10,'emoji'=>'','label'=>'10th Place',     'cls'=>'top'],
                        ['pos'=>'11–20','emoji'=>'','label'=>'Outside Points','cls'=>'out'],
                    ];
                    $maxBase = 25;
                    foreach ($positions as $i => $p):
                        $isRange = $p['pos'] === '11–20';
                        $base    = $isRange ? 0 : ($pts_map[$p['pos']-1] ?? 0);
                        $total   = $base + 3;
                        $barPct  = round(($total / 31) * 100);
                        $delay   = number_format($i * 0.06 + 0.2, 2);
                        $isPodium = !$isRange && $p['pos'] <= 3;
                    ?>
                    <tr class="pts-row <?php echo $isPodium ? 'podium-row' : ''; ?>" style="animation-delay:<?php echo $delay; ?>s">
                        <td>
                            <div class="pos-badge <?php echo $p['cls']; ?>">
                                <?php echo $isRange ? '11+' : 'P'.$p['pos']; ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-size:0.85rem;font-weight:700;color:#e2e8f0;">
                                <?php echo $p['emoji'] ? $p['emoji'].' ' : ''; ?><?php echo $p['label']; ?>
                            </div>
                            <?php if ($isPodium): ?>
                            <div style="margin-top:3px;"><span class="bonus-tag tag-orange"><i class="fas fa-medal"></i> Podium sweep eligible</span></div>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;">
                            <span class="pts-val" style="color:<?php echo $base > 0 ? '#f97316' : '#2d3748'; ?>">
                                <?php echo $base > 0 ? $base : '—'; ?>
                            </span>
                        </td>
                        <td style="text-align:right;">
                            <span class="pts-val" style="color:#00ff88">+3</span>
                        </td>
                        <td style="text-align:right;">
                            <span class="pts-val" style="color:#fff;font-size:1.5rem;">
                                <?php echo $base > 0 ? $total : '3'; ?>
                            </span>
                        </td>
                        <td style="padding-left:6px;">
                            <div class="bar-wrap">
                                <div class="bar-fill" style="--bar-w:<?php echo $barPct; ?>%;animation-delay:<?php echo $delay; ?>s;width:<?php echo $barPct; ?>%"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="padding:14px 16px;border-top:1px solid rgba(255,255,255,0.05);font-size:0.72rem;color:#334155;line-height:1.5;">
                    <i class="fas fa-info-circle" style="color:var(--f1-red);margin-right:6px;"></i>
                    <strong style="color:#475569;">Note:</strong> Base points follow the official F1 scoring system (25–18–15–12–10–8–6–4–2–1 for P1–P10).
                    Positions 11+ earn 0 base points but still award the <strong style="color:#00ff88;">+3 strategy bonus</strong> for exact prediction.
                </div>
            </div>
        </section>

        <!-- ── Bonus cards ─────────────────────────────────── -->
        <section class="reveal" style="margin-bottom:56px;">
            <div style="margin-bottom:24px;">
                <div class="section-eyebrow">Stack Your Points</div>
                <div class="section-title">Bonus Points</div>
                <div class="section-sub">Earn extra on top of your position points</div>
            </div>
            <div class="bonus-grid">
                <!-- Podium sweep -->
                <div class="bonus-card bc-orange">
                    <span class="bonus-icon">🏆</span>
                    <div style="margin-bottom:12px;">
                        <div class="section-eyebrow" style="font-size:0.55rem;">All three correct</div>
                        <div style="font-family:'Bebas Neue',sans-serif;font-size:1.5rem;color:#fff;letter-spacing:0.02em;">Podium Sweep</div>
                    </div>
                    <div class="bonus-num" style="color:var(--f1-orange);">+10</div>
                    <div style="font-size:0.65rem;color:#64748b;margin-top:4px;margin-bottom:16px;">bonus points</div>
                    <p style="font-size:0.78rem;color:#64748b;line-height:1.5;margin-bottom:12px;">
                        Predict <strong style="color:#fff;">P1, P2, and P3</strong> all in the exact correct order. Get all three right and this bonus is yours.
                    </p>
                    <div style="background:rgba(255,106,0,0.07);border:1px solid rgba(255,106,0,0.15);border-radius:10px;padding:10px 12px;font-size:0.7rem;color:#64748b;">
                        <i class="fas fa-exclamation-triangle" style="color:var(--f1-orange);margin-right:6px;"></i>
                        Requires all 3 podium positions correct — 1 or 2 alone won't trigger this.
                    </div>
                </div>

                <!-- Constructor bonus -->
                <div class="bonus-card bc-blue">
                    <span class="bonus-icon">🔧</span>
                    <div style="margin-bottom:12px;">
                        <div class="section-eyebrow" style="font-size:0.55rem;">Beat the paddock</div>
                        <div style="font-family:'Bebas Neue',sans-serif;font-size:1.5rem;color:#fff;letter-spacing:0.02em;">Constructor Pick</div>
                    </div>
                    <div class="bonus-num" style="color:#3b82f6;">+5</div>
                    <div style="font-size:0.65rem;color:#64748b;margin-top:4px;margin-bottom:16px;">bonus points</div>
                    <p style="font-size:0.78rem;color:#64748b;line-height:1.5;margin-bottom:12px;">
                        Predict which <strong style="color:#fff;">Constructor</strong> scores the most combined driver points in the race.
                    </p>
                    <div style="background:rgba(59,130,246,0.07);border:1px solid rgba(59,130,246,0.15);border-radius:10px;padding:10px 12px;font-size:0.7rem;color:#64748b;">
                        <i class="fas fa-info-circle" style="color:#3b82f6;margin-right:6px;"></i>
                        Calculated automatically from the combined F1 points of your predicted drivers.
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Double points races ─────────────────────────── -->
        <section class="reveal" style="margin-bottom:56px;">
            <div class="double-hero">
                <div style="position:relative;z-index:1;">
                    <div class="section-eyebrow" style="color:#c084fc;"><i class="fas fa-bolt"></i> &nbsp;Special Events</div>
                    <div class="double-title">Double Points Races</div>
                    <p style="font-size:0.9rem;color:#64748b;max-width:520px;line-height:1.5;">
                        Three marquee Grand Prix events award <strong style="color:#c084fc;">2× ALL points</strong> — your driver accuracy, podium sweep, and constructor bonus all get doubled.
                    </p>
                    <div class="track-cards">
                        <?php
                        $tracks = [
                            ['flag'=>'🇨🇳','name'=>'China GP',    'delay'=>'0.1s'],
                            ['flag'=>'🇬🇧','name'=>'British GP',  'delay'=>'0.2s'],
                            ['flag'=>'🇸🇬','name'=>'Singapore GP','delay'=>'0.3s'],
                        ];
                        foreach ($tracks as $t): ?>
                        <div class="track-card" style="animation-delay:<?php echo $t['delay']; ?>">
                            <span class="track-flag"><?php echo $t['flag']; ?></span>
                            <div class="track-name"><?php echo $t['name']; ?></div>
                            <div class="track-badge"><i class="fas fa-bolt"></i> 2× Points</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top:20px;background:rgba(0,0,0,0.4);border:1px solid rgba(139,92,246,0.2);border-radius:10px;padding:12px 16px;font-size:0.75rem;color:#64748b;display:flex;align-items:center;gap:10px;">
                        <i class="fas fa-lightbulb" style="color:#c084fc;font-size:1rem;flex-shrink:0;"></i>
                        <span>Example: Earn 50 pts on a normal race? The same predictions at a Double Points race = <strong style="color:#c084fc;">100 points</strong>. Plan accordingly.</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Live example ────────────────────────────────── -->
        <section class="reveal" style="margin-bottom:56px;">
            <div style="margin-bottom:24px;">
                <div class="section-eyebrow">See It In Action</div>
                <div class="section-title">Example Calculation</div>
                <div class="section-sub">A perfect race prediction — and what it's worth</div>
            </div>
            <div class="card">
                <div class="example-grid">
                    <!-- Left: Predictions -->
                    <div class="example-col">
                        <div style="font-size:0.6rem;font-weight:800;text-transform:uppercase;letter-spacing:0.1em;color:#334155;margin-bottom:16px;">Your Predictions</div>
                        <?php
                        $preds = [
                            ['pos'=>1,'driver'=>'Max Verstappen','team'=>'Red Bull'],
                            ['pos'=>2,'driver'=>'Lando Norris','team'=>'McLaren'],
                            ['pos'=>3,'driver'=>'Charles Leclerc','team'=>'Ferrari'],
                            ['pos'=>4,'driver'=>'Oscar Piastri','team'=>'McLaren'],
                            ['pos'=>5,'driver'=>'Lewis Hamilton','team'=>'Ferrari'],
                        ];
                        foreach ($preds as $j => $pred): ?>
                        <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:8px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.05);margin-bottom:6px;animation:slideLeft 0.35s ease <?php echo $j*0.07; ?>s both;">
                            <div style="width:30px;height:30px;border-radius:6px;background:rgba(255,255,255,0.05);display:flex;align-items:center;justify-content:center;font-family:'Bebas Neue',sans-serif;font-size:0.85rem;color:#64748b;flex-shrink:0;">P<?php echo $pred['pos']; ?></div>
                            <div>
                                <div style="font-size:0.82rem;font-weight:700;color:#e2e8f0;"><?php echo $pred['driver']; ?></div>
                                <div style="font-size:0.62rem;color:#475569;text-transform:uppercase;letter-spacing:0.04em;"><?php echo $pred['team']; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <div style="margin-top:10px;padding:8px 12px;border-radius:8px;background:rgba(59,130,246,0.06);border:1px solid rgba(59,130,246,0.15);font-size:0.78rem;color:#64748b;animation:slideLeft 0.35s ease 0.4s both;">
                            <i class="fas fa-wrench" style="color:#3b82f6;margin-right:6px;"></i>Top Constructor: <strong style="color:#fff;">Red Bull</strong>
                        </div>
                    </div>

                    <div class="example-divider"></div>

                    <!-- Right: Results & points -->
                    <div class="example-col">
                        <div style="font-size:0.6rem;font-weight:800;text-transform:uppercase;letter-spacing:0.1em;color:#334155;margin-bottom:16px;">Points Earned</div>

                        <?php
                        $rows = [
                            ['label'=>'✓ P1 Verstappen exact',  'pts'=>'+28', 'cls'=>'er-exact',  'delay'=>0.1, 'sub'=>'25 base + 3 exact'],
                            ['label'=>'✓ P2 Norris exact',      'pts'=>'+21', 'cls'=>'er-exact',  'delay'=>0.17,'sub'=>'18 base + 3 exact'],
                            ['label'=>'✓ P3 Leclerc exact',     'pts'=>'+18', 'cls'=>'er-exact',  'delay'=>0.24,'sub'=>'15 base + 3 exact'],
                            ['label'=>'✗ P4 Piastri miss',      'pts'=>'0',   'cls'=>'er-miss',   'delay'=>0.31,'sub'=>'No exact match'],
                            ['label'=>'✗ P5 Hamilton miss',     'pts'=>'0',   'cls'=>'er-miss',   'delay'=>0.38,'sub'=>'No exact match'],
                            ['label'=>'🏆 Podium Sweep bonus',  'pts'=>'+10', 'cls'=>'er-bonus',  'delay'=>0.48,'sub'=>'All 3 podium correct'],
                            ['label'=>'🔧 Constructor bonus',   'pts'=>'+5',  'cls'=>'er-cons',   'delay'=>0.55,'sub'=>'Red Bull top team'],
                        ];
                        foreach ($rows as $r): ?>
                        <div class="example-row <?php echo $r['cls']; ?>" style="animation-delay:<?php echo $r['delay']; ?>s">
                            <div>
                                <div style="font-size:0.8rem;color:<?php echo str_contains($r['cls'],'exact') ? '#00ff88' : (str_contains($r['cls'],'miss') ? '#475569' : '#f97316'); ?>;"><?php echo $r['label']; ?></div>
                                <div style="font-size:0.6rem;color:#334155;margin-top:1px;"><?php echo $r['sub']; ?></div>
                            </div>
                            <?php
                            if (str_contains($r['cls'],'miss'))  $erColor = '#2d3748';
                            elseif (str_contains($r['cls'],'cons'))  $erColor = '#3b82f6';
                            elseif (str_contains($r['cls'],'bonus')) $erColor = '#f97316';
                            else $erColor = '#00ff88';
                            ?>
                            <div class="er-pts" style="color:<?php echo $erColor; ?>;animation-delay:<?php echo $r['delay']+0.1; ?>s"><?php echo $r['pts']; ?></div>
                        </div>
                        <?php endforeach; ?>

                        <div class="total-banner">
                            <div style="font-size:0.6rem;font-weight:800;text-transform:uppercase;letter-spacing:0.1em;color:#00cc70;margin-bottom:4px;">Normal Race Total</div>
                            <div class="total-num">82 <span style="font-size:1.5rem;">pts</span></div>
                        </div>

                        <div class="double-banner">
                            <div style="font-size:0.6rem;font-weight:800;text-transform:uppercase;letter-spacing:0.1em;color:#c084fc;margin-bottom:4px;"><i class="fas fa-bolt"></i> Double Points Race</div>
                            <div class="double-num">164 <span style="font-size:1.5rem;">pts</span></div>
                            <div style="font-size:0.7rem;color:#64748b;margin-top:4px;">82 × 2 = 164</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Pro tips ─────────────────────────────────────── -->
        <section class="reveal" style="margin-bottom:56px;">
            <div style="margin-bottom:24px;">
                <div class="section-eyebrow">Race Strategy</div>
                <div class="section-title">Pro Tips</div>
                <div class="section-sub">How to climb the standings faster</div>
            </div>
            <div class="tips-grid">
                <?php
                $tips = [
                    ['icon'=>'🎯','title'=>'Every position matters','text'=>'Even outside the points (P11–P20), an exact prediction earns you +3 strategy bonus. Accuracy compounds over a full season.','delay'=>0.05,'border'=>'rgba(0,255,136,0.15)'],
                    ['icon'=>'🏆','title'=>'Guard the podium','text'=>'The +10 podium sweep bonus is massive. If you\'re confident in the top 3, lock them in — three correct positions plus the bonus can swing the leaderboard.','delay'=>0.1,'border'=>'rgba(255,106,0,0.15)'],
                    ['icon'=>'🔧','title'=>'Constructor is free points','text'=>'The constructor prediction is automatically derived from your driver picks. Choose drivers from teams likely to score together.','delay'=>0.15,'border'=>'rgba(59,130,246,0.15)'],
                    ['icon'=>'⚡','title'=>'Save your sharpest reads','text'=>'China, UK, and Singapore GP are worth double. A great read on these races is worth far more than a perfect prediction at a standard race.','delay'=>0.2,'border'=>'rgba(139,92,246,0.15)'],
                ];
                foreach ($tips as $tip): ?>
                <div class="tip-card" style="animation-delay:<?php echo $tip['delay']; ?>s;border-color:<?php echo $tip['border']; ?>;">
                    <span class="tip-icon"><?php echo $tip['icon']; ?></span>
                    <div class="tip-title"><?php echo $tip['title']; ?></div>
                    <div class="tip-text"><?php echo $tip['text']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- ── CTA ─────────────────────────────────────────── -->
        <section class="reveal" style="text-align:center;padding:40px 0 20px;">
            <?php if ($user): ?>
            <a href="index.php#predict" class="cta-btn cta-primary"><i class="fas fa-pencil-alt"></i> Make Your Predictions</a>
            &nbsp;&nbsp;
            <a href="leaderboard.php" class="cta-btn cta-outline"><i class="fas fa-trophy"></i> View Leaderboard</a>
            <?php else: ?>
            <a href="signup.php" class="cta-btn cta-primary"><i class="fas fa-user-plus"></i> Join & Start Playing</a>
            &nbsp;&nbsp;
            <a href="login.php" class="cta-btn cta-outline"><i class="fas fa-sign-in-alt"></i> Log In</a>
            <?php endif; ?>
        </section>

    </div><!-- /page-body -->

    <footer style="margin-top:40px;border-top:1px solid rgba(255,255,255,0.05);padding:24px;text-align:center;">
        <p style="font-size:11px;color:#334155;margin-bottom:4px;">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        <p style="font-size:10px;color:rgba(255,255,255,0.2);">Powered by <a href="https://www.scanerrific.com" target="_blank" style="color:var(--f1-red);text-decoration:none;font-weight:600;">Scanerrific</a></p>
    </footer>

<script src="app.js"></script>
<script>
// Scroll reveal
(function() {
    var els = document.querySelectorAll('.reveal');
    if (!window.IntersectionObserver) {
        els.forEach(function(el) { el.classList.add('visible'); });
        return;
    }
    var obs = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) {
            if (e.isIntersecting) {
                e.target.classList.add('visible');
                obs.unobserve(e.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
    els.forEach(function(el) { obs.observe(el); });
})();

// Animated counter for score totals
document.addEventListener('DOMContentLoaded', function() {
    var counters = document.querySelectorAll('[data-count]');
    counters.forEach(function(el) {
        var target = parseInt(el.dataset.count, 10);
        var start = 0; var dur = 1200;
        var step = function(ts) {
            if (!step.startTs) step.startTs = ts;
            var progress = Math.min((ts - step.startTs) / dur, 1);
            var ease = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.round(ease * target);
            if (progress < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    });
});
</script>
</body>
</html>
