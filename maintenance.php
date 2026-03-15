<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Race Control in Progress - Paddock Picks</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #080c14;
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Animated background */
        .bg {
            position: fixed;
            inset: 0;
            background: 
                radial-gradient(ellipse at 20% 50%, rgba(249,115,22,0.08) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 50%, rgba(239,68,68,0.06) 0%, transparent 60%);
        }

        /* Racing stripe animation */
        .stripes {
            position: fixed;
            inset: 0;
            background: repeating-linear-gradient(
                -45deg,
                transparent,
                transparent 40px,
                rgba(255,255,255,0.01) 40px,
                rgba(255,255,255,0.01) 80px
            );
            animation: stripeMove 4s linear infinite;
        }
        @keyframes stripeMove {
            from { background-position: 0 0; }
            to { background-position: 113px 0; }
        }

        .card {
            position: relative;
            z-index: 10;
            text-align: center;
            padding: 60px 80px;
            max-width: 680px;
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 32px;
            backdrop-filter: blur(30px);
            box-shadow: 0 50px 100px -20px rgba(0,0,0,0.8);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(249,115,22,0.15);
            border: 1px solid rgba(249,115,22,0.4);
            color: #f97316;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            padding: 8px 20px;
            border-radius: 999px;
            margin-bottom: 32px;
        }

        .dot {
            width: 8px;
            height: 8px;
            background: #f97316;
            border-radius: 50%;
            animation: pulse 1.5s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.7); }
        }

        h1 {
            font-size: 64px;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -0.03em;
            margin-bottom: 8px;
            text-transform: uppercase;
            font-style: italic;
        }

        .subtitle {
            font-size: 16px;
            font-weight: 700;
            color: #f97316;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            margin-bottom: 32px;
        }

        p {
            color: #94a3b8;
            font-size: 15px;
            line-height: 1.7;
            max-width: 480px;
            margin: 0 auto 40px;
        }

        /* Spinner */
        .spinner-wrap {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
        }
        .spinner {
            width: 56px;
            height: 56px;
            border: 3px solid rgba(249,115,22,0.2);
            border-top-color: #f97316;
            border-radius: 50%;
            animation: spin 0.9s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Race number */
        .race-info {
            display: flex;
            justify-content: center;
            gap: 32px;
            margin-top: 8px;
        }
        .race-stat {
            text-align: center;
        }
        .race-stat .val {
            font-size: 28px;
            font-weight: 900;
            color: white;
            font-style: italic;
        }
        .race-stat .lbl {
            font-size: 9px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            margin-top: 4px;
        }
        .divider {
            width: 1px;
            background: rgba(255,255,255,0.07);
        }

        .logo {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #dc2626, #f97316);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 22px;
        }
    </style>
</head>
<body>
    <div class="bg"></div>
    <div class="stripes"></div>

    <div class="card">
        <div class="logo">🏁</div>
        
        <div class="badge">
            <div class="dot"></div>
            Race Control Active
        </div>
        
        <h1>POST-RACE<br>DEBRIEF</h1>
        <div class="subtitle">Chinese Grand Prix · Round 02</div>

        <div class="spinner-wrap">
            <div class="spinner"></div>
        </div>

        <p>
            Our Race Engineers are currently calculating points for the <strong style="color:#a78bfa;">⚡ DOUBLE POINTS</strong> Chinese Grand Prix, updating the Championship Standings, and preparing for the next round in Japan.
            <br><br>
            <strong style="color:white;">Check back in a few minutes — results incoming! 🏆</strong>
        </p>

        <div class="race-info">
            <div class="race-stat">
                <div class="val">02</div>
                <div class="lbl">Round</div>
            </div>
            <div class="divider"></div>
            <div class="race-stat">
                <div class="val">🇨🇳</div>
                <div class="lbl">China ⚡</div>
            </div>
            <div class="divider"></div>
            <div class="race-stat">
                <div class="val">03</div>
                <div class="lbl">Next: Japan</div>
            </div>
        </div>

        <!-- Hidden admin login link -->
        <a href="login.php" class="absolute bottom-6 left-1/2 -translate-x-1/2 text-[10px] text-gray-600 hover:text-orange-500 uppercase tracking-widest font-bold z-50 transition">
            Admin Access
        </a>
    </div>
</body>
</html>
