<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/avatars.php';
$user        = getCurrentUser();
$currentUser = $user;
$stats       = $user ? getUserStats($user['id']) : [];

if (!$user) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Paddock Picks</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,500;1,400;1,500&family=Teko:wght@400;500;600;700&display=swap" rel="stylesheet">
<script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
<script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
<script src="https://unpkg.com/@babel/standalone@7/babel.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15/Sortable.min.js"></script>
<style>
/* ===== RESET ===== */
*{margin:0;padding:0;box-sizing:border-box}
#root .card,#root .btn,#root .badge,#root .modal,#root .toast,#root .hero,#root .constr-card,#root .ach-card,#root .avatar-option{border-radius:0!important}
/* ===== TOKENS ===== */
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
body{background:var(--canvas);color:var(--text);font-family:'Inter',sans-serif;font-size:15px;line-height:1.6;-webkit-font-smoothing:antialiased}
::selection{background:var(--accent-soft)}
::-webkit-scrollbar{width:0}
a{text-decoration:none;color:inherit}
/* ===== TYPE ===== */
.display{font-family:'Playfair Display',serif;font-weight:400;font-style:italic;line-height:1.1}
.serif{font-family:'Playfair Display',serif}
h1,.h1{font-weight:800;letter-spacing:-0.02em;line-height:1.1}
h2,.h2{font-weight:700;letter-spacing:-0.01em;line-height:1.2}
h3,.h3{font-weight:600;letter-spacing:-0.01em;line-height:1.3}
.lead{font-size:17px;font-weight:400;color:var(--text2);line-height:1.6}
.muted{font-size:13px;color:var(--text3)}
.sans{font-family:'Inter',sans-serif}
.mono{font-family:'JetBrains Mono',monospace;font-variant-numeric:tabular-nums}
.racing{font-family:'Teko',sans-serif;font-weight:500;text-transform:uppercase;letter-spacing:0.04em;line-height:1}
.caps{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:var(--text2)}
.ch{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:var(--text2)}
/* ===== BUTTONS ===== */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:8px 20px;font-family:'Inter',sans-serif;font-size:14px;font-weight:500;border:1px solid var(--border);border-radius:var(--rad-pill);cursor:pointer;transition:all 200ms;background:var(--surface);color:var(--text)}
.btn:hover{background:var(--canvas);border-color:var(--border)}
.btn-primary{background:var(--accent);color:#fff;border-color:var(--accent)}
.btn-primary:hover{opacity:0.9;background:var(--accent)}
.btn-outline{background:transparent;border-color:var(--border);color:var(--text2)}
.btn-outline:hover{background:var(--surface);border-color:var(--accent);color:var(--accent)}
.btn-ghost{background:transparent;border-color:transparent;color:var(--text2)}
.btn-ghost:hover{background:var(--canvas);color:var(--text)}
.btn-sm{padding:5px 14px;font-size:12px;border-radius:14px}
.btn-lg{padding:12px 28px;font-size:16px;border-radius:28px}
.btn-block{width:100%}
/* ===== CARDS ===== */
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--rad)}
.card-hover{transition:all 200ms ease}
.card-hover:hover{background:var(--surface-muted);border-color:var(--border)}
/* ===== BADGES ===== */
.badge{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;font-size:12px;font-weight:500;border:1px solid;border-radius:var(--rad-sm);background:var(--surface)}
.badge-accent{background:var(--accent-soft);color:var(--accent);border-color:rgba(196,30,58,0.15)}
.badge-live{background:rgba(45,106,79,0.08);color:var(--live);border-color:rgba(45,106,79,0.15)}
.badge-gray{background:var(--surface-muted);color:var(--text2);border-color:var(--border)}
.badge-gold{background:rgba(201,169,110,0.1);color:var(--gold);border-color:rgba(201,169,110,0.2)}
.badge-green{background:rgba(45,106,79,0.08);color:var(--live);border-color:rgba(45,106,79,0.15)}
.badge-red{background:rgba(196,30,58,0.08);color:var(--accent);border-color:rgba(196,30,58,0.15)}
.badge-purple{background:var(--accent-soft);color:var(--accent);border-color:rgba(196,30,58,0.15)}
/* ===== INPUTS ===== */
.input{width:100%;background:var(--surface);border:1px solid var(--border);border-radius:var(--rad-sm);padding:10px 16px;color:var(--text);font-family:'Inter',sans-serif;font-size:15px;outline:none;transition:border-color 200ms}
.input:focus{border-color:var(--accent)}
.input::placeholder{color:var(--text3)}
/* ===== LAYOUT ===== */
.page{max-width:1080px;margin:0 auto;padding:80px 24px 60px}
@media(max-width:768px){.page{padding:72px 12px 40px}}
/* ===== FLOATING ACTIONS ===== */
.hero{position:relative;height:280px;overflow:hidden}
.hero-bg{position:absolute;inset:0;background-size:cover;background-position:center;background-color:#1a1a2e;background-image:linear-gradient(135deg,#1a1a2e,#16213e)}
.hero-overlay{position:absolute;inset:0;background:linear-gradient(to bottom,rgba(0,0,0,0.1) 0%,rgba(0,0,0,0.5) 100%)}
.race-info{background:var(--surface);border:1px solid var(--border);padding:20px 24px;display:flex;align-items:center;justify-content:space-between;gap:20px;margin-top:24px}
.race-info-left{flex:1;min-width:0}
.race-info-right{flex-shrink:0;display:flex;align-items:center;gap:16px}
.race-title{font-family:'Teko',sans-serif;font-weight:500;text-transform:uppercase;letter-spacing:0.04em;font-size:28px;color:var(--text);line-height:1;margin-bottom:2px}
.race-meta{font-size:12px;text-transform:uppercase;letter-spacing:0.08em;color:var(--text2)}
.cd-ring{position:relative;width:52px;height:52px;flex-shrink:0}
.cd-ring svg{width:100%;height:100%;transform:rotate(-90deg)}
.cd-ring circle{fill:none;stroke-width:4}
.cd-ring .bg{stroke:var(--border)}
.cd-ring .fg{stroke:var(--accent);stroke-linecap:round;transition:stroke-dashoffset 1s ease}
.cd-text{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-family:'JetBrains Mono',monospace;font-size:9px;color:var(--text);font-weight:700}
@media(max-width:768px){.hero{height:180px}.race-info{flex-direction:column;align-items:stretch;padding:16px;gap:12px;margin-top:16px}.race-title{font-size:20px}}
/* ===== QUOTE ===== */
.quote-block{max-width:680px;margin:0 auto 64px;padding-left:24px;border-left:2px solid var(--gold)}
.quote-avatar{width:40px;height:40px;border-radius:50%;overflow:hidden;margin-bottom:16px;background:var(--surface-muted)}
.quote-avatar img{width:100%;height:100%;object-fit:cover}
.quote-text{font-family:'Playfair Display',serif;font-style:italic;font-weight:400;font-size:26px;color:var(--text);line-height:1.4;margin-bottom:16px}
.quote-attribution{font-size:14px;color:var(--text2)}
.quote-attribution::before{content:'';display:block;width:24px;height:2px;background:var(--gold);margin-bottom:8px}
/* ===== FLOATING ACTIONS ===== */
.float-actions{position:fixed;bottom:32px;right:32px;z-index:50;display:flex;flex-direction:column;gap:12px}
.float-btn{width:48px;height:48px;border-radius:14px;background:var(--surface);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:20px;color:var(--text);cursor:pointer;transition:all 150ms;box-shadow:0 2px 8px rgba(0,0,0,0.04)}
.float-btn:hover{background:var(--text);color:var(--surface)}
/* ===== PREDICTION ROW ===== */
.pred-row{display:flex;align-items:center;gap:16px;height:72px;padding:0 16px;border-bottom:1px solid var(--border-light);transition:background 200ms;cursor:pointer}
.pred-row:hover{background:var(--surface-muted)}
.pred-pos{width:40px;font-family:'JetBrains Mono',monospace;font-size:14px;color:var(--text3);flex-shrink:0}
.pred-avatar{width:48px;height:48px;border-radius:50%;overflow:hidden;flex-shrink:0;border:2px solid var(--surface);box-shadow:0 0 0 1px var(--border)}
.pred-avatar img{width:100%;height:100%;object-fit:cover}
.pred-info{flex:1;min-width:0}
.pred-name{font-size:16px;font-weight:500;color:var(--text);line-height:1.3}
.pred-team{font-size:13px;color:var(--text2)}
.pred-odds{font-family:'JetBrains Mono',monospace;font-size:14px;color:var(--text);margin-right:16px}
.pred-selector{width:28px;height:28px;border-radius:50%;border:1.5px solid #D5D2CC;flex-shrink:0;transition:all 200ms;display:flex;align-items:center;justify-content:center;cursor:pointer}
.pred-selector.selected{background:var(--accent);border-color:var(--accent)}
.pred-selector.selected::after{content:'✓';color:#fff;font-size:14px;font-weight:700}
/* ===== LEADERBOARD MINI ===== */
.lb-mini{border:1px solid var(--border);border-radius:var(--rad);padding:24px}
.lb-mini-header{font-size:12px;font-weight:500;text-transform:uppercase;letter-spacing:0.08em;color:var(--text2);margin-bottom:20px}
.lb-mini-row{display:flex;align-items:center;gap:12px;height:48px;padding:0 4px}
.lb-mini-avatar{width:32px;height:32px;border-radius:50%;overflow:hidden;flex-shrink:0;background:var(--surface-muted)}
.lb-mini-avatar img{width:100%;height:100%;object-fit:cover}
.lb-mini-name{font-size:14px;font-weight:500;flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.lb-mini-pts{font-family:'JetBrains Mono',monospace;font-size:14px}
.lb-mini-me{background:var(--accent-soft);margin:0 -8px;padding:0 12px;border-radius:var(--rad-sm);border-left:2px solid var(--accent)}
.lb-mini-dot{width:6px;height:6px;border-radius:50%;flex-shrink:0}
.lb-mini-dot.gold{background:var(--gold)}
.lb-mini-dot.silver{background:var(--silver)}
.lb-mini-dot.bronze{background:var(--bronze)}
.ach-dropdown{max-height:0;overflow:hidden;transition:max-height 0.35s ease,opacity 0.3s ease,padding 0.3s ease;opacity:0;padding:0 8px}
.ach-dropdown.open{max-height:400px;opacity:1;padding:12px 8px}


/* ===== CONSTRUCTOR CARD ===== */
.constr-card{border:1px solid var(--border);border-radius:var(--rad);overflow:hidden;cursor:pointer;transition:all 200ms;aspect-ratio:4/5;position:relative}
.constr-card:hover{border-color:#D5D2CC;transform:translateY(-2px)}
.constr-card.selected{border:2px solid var(--accent)}
.constr-card-bg{position:absolute;inset:0;background:var(--border-light);display:flex;align-items:center;justify-content:center;font-size:48px;opacity:0.3}
.constr-card-img{width:100%;height:100%;object-fit:cover}
.constr-card-overlay{position:absolute;bottom:0;left:0;right:0;padding:20px;background:linear-gradient(to top,rgba(0,0,0,0.6),transparent)}
.constr-card-name{font-size:20px;font-weight:500;color:#fff}
.constr-card-count{font-size:13px;text-transform:uppercase;letter-spacing:0.06em;color:rgba(255,255,255,0.7)}
/* ===== MODAL ===== */
.modal-overlay{position:fixed;inset:0;z-index:200;background:rgba(0,0,0,0.25);display:flex;align-items:center;justify-content:center;padding:20px}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:var(--rad);padding:28px;width:100%;max-width:400px}
.modal .field{margin-bottom:16px}
.modal .field label{display:block;font-size:12px;font-weight:500;text-transform:uppercase;letter-spacing:0.08em;color:var(--text3);margin-bottom:6px}
.modal .field .iw{position:relative}
.modal .field .iw i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text3);font-size:14px}
.modal .field .iw input{padding-left:40px}
.modal .err{background:rgba(255,59,48,0.06);border:1px solid rgba(255,59,48,0.15);color:var(--accent);padding:10px 14px;border-radius:var(--rad-sm);font-size:13px;text-align:center;margin-bottom:16px}
.modal .success{background:rgba(45,106,79,0.06);border:1px solid rgba(45,106,79,0.15);color:var(--live);padding:10px 14px;border-radius:var(--rad-sm);font-size:13px;text-align:center;margin-bottom:16px}
/* ===== AVATAR ===== */
.avatar-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(72px,1fr));gap:10px;max-height:400px;overflow-y:auto;padding:4px}
.avatar-option{width:100%;aspect-ratio:1;border-radius:var(--rad-sm);border:2px solid var(--border);background:var(--surface);cursor:pointer;transition:all 200ms;display:flex;align-items:center;justify-content:center;overflow:hidden;padding:8px}
.avatar-option:hover{border-color:var(--accent)}
.avatar-option.active{border-color:var(--accent);background:var(--accent-soft)}
.avatar-option img{width:100%;height:100%;object-fit:contain}
/* ===== ACHIEVEMENTS ===== */
.ach-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px}
.ach-card{position:relative;padding:20px;border:1px solid var(--border);border-radius:var(--rad);text-align:center;transition:all 300ms;background:var(--surface)}
.ach-card.locked{opacity:0.4;filter:grayscale(1)}
.ach-card .ach-icon{width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;margin:0 auto 12px}
.ach-card .ach-name{font-weight:600;font-size:13px;margin-bottom:4px}
.ach-card .ach-desc{font-size:11px;color:var(--text2);line-height:1.4}
.ach-tag{position:absolute;top:12px;right:12px;font-size:9px;font-weight:600;text-transform:uppercase;padding:2px 8px;border-radius:var(--rad-sm)}
/* ===== KEYFRAMES ===== */
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.anim{animation:fadeUp 600ms cubic-bezier(0.22,1,0.36,1) both}
.anim-d1{animation-delay:80ms}
.anim-d2{animation-delay:160ms}
.anim-d3{animation-delay:240ms}
.anim-d4{animation-delay:320ms}
.fa-spinner{animation:spin .8s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
/* ===== TOAST ===== */
.toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:300;background:var(--surface);border:1px solid var(--border);border-radius:var(--rad-pill);padding:12px 24px;font-size:14px;font-weight:500;display:flex;align-items:center;gap:10px;animation:fadeUp 300ms ease both}
.toast.success{color:var(--live)}
.toast.error{color:var(--accent)}
/* ===== SORTABLE ===== */
.sortable-ghost{opacity:0.15!important}
.sortable-drag{opacity:0.95;border:1px solid var(--accent)!important}
.sortable-chosen{background:var(--accent-soft)!important}
/* ===== DRIVER ROW (predict page) ===== */
.driver-row{display:flex;align-items:center;gap:12px;padding:10px 14px;border-bottom:1px solid var(--border-light);cursor:grab;transition:background 200ms}
.driver-row:last-child{border-bottom:none}
.driver-row:hover{background:var(--surface-muted)}
.driver-row:active{cursor:grabbing}
.driver-row.hidden{display:none!important}
.driver-pos{width:28px;height:28px;border-radius:50%;background:var(--canvas);display:flex;align-items:center;justify-content:center;font-weight:600;font-size:12px;color:var(--text2);flex-shrink:0;font-family:'JetBrains Mono',monospace}
.team-badge{width:28px;height:20px;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:7px;font-weight:700;color:#fff;text-transform:uppercase;flex-shrink:0;letter-spacing:0.5px}
.driver-name{font-weight:500;font-size:15px;flex:1}
.team-name{font-size:12px;color:var(--text2)}
.move-btn{width:28px;height:28px;border-radius:50%;border:none;background:var(--canvas);color:var(--text2);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:11px;transition:all 150ms;flex-shrink:0}
.move-btn:hover{background:var(--accent);color:#fff}
.move-btn:disabled{opacity:0.25;cursor:not-allowed}
/* ===== UTILITY ===== */
.caps-label{font-size:12px;font-weight:500;text-transform:uppercase;letter-spacing:0.08em;color:var(--text2)}
.data-number{font-family:'JetBrains Mono',monospace;font-variant-numeric:tabular-nums}
.text-accent{color:var(--accent)}
.text-gold{color:var(--gold)}
.text-live{color:var(--live)}
.bg-accent-soft{background:var(--accent-soft)}
</style>
</head>
<body>
<?php require_once __DIR__ . '/includes/nav.php'; ?>
<div id="root"></div>

<script type="text/babel">
const { useState, useEffect, useRef, useMemo, useCallback } = React;

const api = (type, params) => {
    const p = new URLSearchParams({ type, ...params });
    return fetch('api/data.php?' + p, { credentials: 'same-origin' }).then(r => r.text()).then(t => { try { return JSON.parse(t); } catch(e) { console.error('API RAW RESPONSE for', type+':', t); return {}; } });
  };
const apiError = (d) => d && d.error && d.error !== 'not_authenticated';
const apiPost = (type, data) => fetch('api/data.php?type=' + type, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data), credentials: 'same-origin' }).then(r => r.json());
const postAuth = (data) => fetch('api/auth.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams(data), credentials: 'same-origin' }).then(r => r.json());

const TEAM_COLORS = {
  'Ferrari':'#DC0000','Mercedes':'#00D2BE','Red Bull':'#3671C6','McLaren':'#FF8000','Aston Martin':'#00665F','Alpine':'#0090FF','Williams':'#005AFF','Haas':'#B6BABD','RB':'#6692FF','Racing Bulls':'#6692FF','Sauber':'#00E701','Kick Sauber':'#00E701','Audi':'#FF1721','Cadillac':'#C41E3A'
};
const TEAM_ABBREV = {
  'Ferrari':'FER','Mercedes':'MER','Red Bull':'RBR','McLaren':'MCL','Aston Martin':'AMR','Alpine':'ALP','Williams':'WIL','Haas':'HAA','RB':'RB','Racing Bulls':'RB','Sauber':'SAU','Kick Sauber':'SAU','Audi':'AUD','Cadillac':'CAD'
};
const F1_POINTS = [25,18,15,12,10,8,6,4,2,1];
const getF1Pts = (pos) => pos >= 1 && pos <= 10 ? F1_POINTS[pos-1] : 0;

const ALL_ACHIEVEMENTS = [
  {id:'first_prediction',name:'Rookie Driver',desc:'Make your first prediction',tier:'common',icon:'fa-flag'},
  {id:'welcome_aboard',name:'Welcome to the Paddock',desc:'Complete your profile setup',tier:'common',icon:'fa-user-check'},
  {id:'first_points',name:'On the Board',desc:'Score your first points',tier:'common',icon:'fa-star'},
  {id:'participation_5',name:'Racing Regular',desc:'Participate in 5 races',tier:'common',icon:'fa-calendar-check'},
  {id:'streak_3',name:'Consistency Counts',desc:'Score points 3 races in a row',tier:'common',icon:'fa-fire'},
  {id:'participation_10',name:'Season Veteran',desc:'Participate in 10 races',tier:'rare',icon:'fa-medal'},
  {id:'podium_sweep_1',name:'Podium Prophet',desc:'Get your first podium sweep',tier:'rare',icon:'fa-trophy'},
  {id:'total_500',name:'Point Collector',desc:'Score 100 total points',tier:'rare',icon:'fa-coins'},
  {id:'constructor_correct_5',name:'Team Tactician',desc:'Predict winning constructor 5 times',tier:'rare',icon:'fa-wrench'},
  {id:'perfectionist',name:'Perfectionist',desc:'Get 5+ exact predictions in one race',tier:'rare',icon:'fa-bullseye'},
  {id:'accuracy_20',name:'Sharp Shooter',desc:'Achieve 45% prediction accuracy',tier:'rare',icon:'fa-crosshairs'},
  {id:'big_score',name:'Big Score',desc:'Score 150+ points in one race',tier:'epic',icon:'fa-bolt'},
  {id:'podium_sweep_3',name:'Crystal Ball',desc:'Get 5 podium sweeps',tier:'epic',icon:'fa-eye'},
  {id:'streak_10',name:'Unbreakable Focus',desc:'Predict 10 races in a row',tier:'epic',icon:'fa-fire'},
  {id:'double_points_master',name:'Double Trouble',desc:'Score 200+ in a 2x points race',tier:'epic',icon:'fa-gem'},
  {id:'accuracy_30',name:'Precision Engineer',desc:'Achieve 30% prediction accuracy',tier:'epic',icon:'fa-bullseye'},
  {id:'total_1000',name:'Points Millionaire',desc:'Score 1000 total points',tier:'epic',icon:'fa-sack-dollar'},
  {id:'race_winner_3',name:'Hat Trick Hero',desc:'Win 3 individual races',tier:'epic',icon:'fa-crown'},
  {id:'legendary_performance',name:'Legendary Performance',desc:'Score 150+ points in single race',tier:'legendary',icon:'fa-trophy'},
  {id:'podium_sweep_5',name:'Oracle of the Grid',desc:'Get 10 podium sweeps',tier:'legendary',icon:'fa-eye'},
  {id:'accuracy_40',name:'The Nostradamus',desc:'Achieve 66% prediction accuracy',tier:'legendary',icon:'fa-magic'},
  {id:'total_2500',name:'Point Legend',desc:'Score 2000 total points',tier:'legendary',icon:'fa-infinity'},
  {id:'first_race_winner',name:'Early Bird',desc:'Win the opening race',tier:'special',icon:'fa-bolt'},
  {id:'constructor_sweep',name:'Team Whisperer',desc:'Predict constructor correctly 7 times',tier:'special',icon:'fa-handshake'},
  {id:'perfect_weekend',name:'Perfect Weekend',desc:'Score 100+ points in 3 consecutive races',tier:'special',icon:'fa-check'},
  {id:'mega_race',name:'Mega Race',desc:'Score 200+ points in a 2x event',tier:'special',icon:'fa-rocket'},
  {id:'silver_arrows',name:'Silver Arrows',desc:'Predict Mercedes 1-2 finish correctly',tier:'special',icon:'fa-star'},
  {id:'columbus',name:'Columbus',desc:'Win in all continents',tier:'special',icon:'fa-globe-americas'},
  {id:'f1_hero',name:'F1 Hero',desc:'Compete in all races',tier:'special',icon:'fa-flag-checkered'}
];

const TIER_CONFIG = {
  common:{color:'var(--success)',bg:'rgba(40,167,69,0.08)',color2:'#28A745'},
  rare:{color:'var(--accent)',bg:'rgba(0,210,190,0.08)',color2:'#00D2BE'},
  epic:{color:'var(--primary)',bg:'rgba(225,6,0,0.08)',color2:'#C00500'},
  legendary:{color:'var(--danger)',bg:'rgba(220,53,69,0.08)',color2:'#DC3545'},
  special:{color:'var(--accent-warm)',bg:'rgba(255,135,0,0.08)',color2:'#FF8700'}
};

const getAvatarUrl = (style, seed) => {
  const m = style && style.match(/^(.+)-v(\d+)$/);
  const baseStyle = m ? m[1] : (style || 'avataaars');
  const fullSeed = m ? seed + '_v' + m[2] : (seed || 'user');
  return 'https://api.dicebear.com/7.x/' + baseStyle + '/svg?seed=' + fullSeed;
};

const I = ({ n, s }) => React.createElement('i', { className: 'fa-solid fa-' + n, style: s });


const LoginModal = ({ onClose, onAuth, defaultTab }) => {
  const [tab, setTab] = useState(defaultTab || 'login');
  const [form, setForm] = useState({username:'',password:'',email:'',full_name:'',confirm_password:''});
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handle = async (e) => {
    e.preventDefault(); setLoading(true); setError('');
    if (tab === 'login') {
      const r = await postAuth({action:'login', username: form.username, password: form.password});
      if (r.ok) { onAuth(); return; }
      setError(r.error || 'Login failed');
    } else {
      if (form.password !== form.confirm_password) { setError('Passwords do not match'); setLoading(false); return; }
      if (form.password.length < 8) { setError('Password must be at least 8 characters'); setLoading(false); return; }
      const r = await postAuth({action:'signup', ...form});
      if (r.ok) { onAuth(); return; }
      setError(r.error || 'Signup failed');
    }
    setLoading(false);
  };

  return (
    <div className="modal-overlay" onClick={(e)=>{if(e.target===e.currentTarget)onClose()}}>
      <div className="modal anim" style={{padding:0,overflow:'hidden'}}>
        <div style={{padding:'24px 28px 0'}}>
          <h2 style={{fontSize:'22px',fontWeight:'800',marginBottom:'4px'}}>{tab === 'login' ? 'Welcome back' : 'Join the grid'}</h2>
          <p style={{color:'var(--text2)',fontSize:'13px',marginBottom:'16px'}}>{tab === 'login' ? 'Log in to manage your predictions' : 'Create your account to start playing'}</p>
          {error && <div className="err"><I n="exclamation-circle" style={{marginRight:6}} /> {error}</div>}
        </div>
        <div style={{display:'flex',margin:'0 28px'}}>
          <button className={'btn btn-sm '+(tab==='login'?'btn-primary':'btn-outline')} onClick={()=>setTab('login')} style={{flex:1,borderRadius:'8px 8px 0 0'}}>Log In</button>
          <button className={'btn btn-sm '+(tab==='signup'?'btn-primary':'btn-outline')} onClick={()=>setTab('signup')} style={{flex:1,borderRadius:'8px 8px 0 0'}}>Sign Up</button>
        </div>
        <form onSubmit={handle} style={{padding:'16px 28px 24px'}}>
          <div className="field">
            <label>Username</label>
            <div className="iw"><I n="user" /><input className="input" placeholder="Username" value={form.username} onChange={e=>setForm({...form,username:e.target.value})} required autoFocus={tab==='login'} /></div>
          </div>
          {tab === 'signup' && (
            <div className="field">
              <label>Email</label>
              <div className="iw"><I n="envelope" /><input className="input" type="email" placeholder="Email" value={form.email} onChange={e=>setForm({...form,email:e.target.value})} required autoFocus={tab==='signup'} /></div>
            </div>
          )}
          <div className="field">
            <label>Password</label>
            <div className="iw"><I n="lock" /><input className="input" type="password" placeholder="Password" value={form.password} onChange={e=>setForm({...form,password:e.target.value})} required /></div>
          </div>
          {tab === 'signup' && (
            <div className="field">
              <label>Confirm Password</label>
              <div className="iw"><I n="check" /><input className="input" type="password" placeholder="Repeat" value={form.confirm_password} onChange={e=>setForm({...form,confirm_password:e.target.value})} required /></div>
            </div>
          )}
          <button type="submit" className="btn btn-primary btn-block btn-lg" disabled={loading} style={{marginTop:'4px'}}>
            {loading ? 'Please wait...' : (tab === 'login' ? 'Log In' : 'Create Account')}
          </button>
        </form>
      </div>
    </div>
  );
};

const CountdownRing = ({ deadline, open, text, progress }) => {
  const [cd, setCd] = useState(text || '');
  const [offset, setOffset] = useState((1-(progress||0)/100)*180);
  const circ = 180;

  useEffect(() => {
    if (!deadline) return;
    const tick = () => {
      const left = deadline - Date.now();
      if (left <= 0) { setCd('Locked'); setOffset(0); return; }
      const d = Math.floor(left/86400000), h = Math.floor((left%86400000)/3600000), m = Math.floor((left%3600000)/60000);
      setCd(d > 0 ? d+'d '+h+'h' : h > 0 ? h+'h '+m+'m' : m > 0 ? m+'m' : '');
      const maxW = 7*24*60*60*1000;
      const p = Math.min(Math.max((maxW-left)/maxW,0),1);
      setOffset(circ*(1-p));
    };
    tick();
    const id = setInterval(tick, 1000);
    return () => clearInterval(id);
  }, [deadline]);

  return (
    <div className="cd-ring">
      <svg viewBox="0 0 64 64">
        <circle className="bg" cx="32" cy="32" r="27"/>
        <circle className="fg" cx="32" cy="32" r="27" strokeDasharray={circ} strokeDashoffset={offset} style={{stroke:open?'var(--green)':'var(--text3)'}}/>
      </svg>
      <div className="cd-text">{cd}</div>
    </div>
  );
};

const StatusBar = ({ progress, open }) => (
  <div className="status-bar">
    <div className={'status-fill '+(open?'open':'closed')} style={{width:open?Math.max(progress,5)+'%':'100%'}}></div>
  </div>
);

const TeamBadge = ({ team }) => {
  const color = TEAM_COLORS[team] || '#64748b';
  const abbr = TEAM_ABBREV[team] || team?.substring(0,3)?.toUpperCase() || '???';
  return <div className="team-badge" style={{background:color}}>{abbr}</div>;
};

const Toast = ({ message, type, onClose }) => {
  useEffect(() => { const t = setTimeout(() => onClose(), 3000); return () => clearTimeout(t); }, []);
  return <div className={'toast '+type}><I n={type==='success'?'check-circle':'exclamation-circle'} />{message}</div>;
};

const Dashboard = ({ onNav }) => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [animKey, setAnimKey] = useState(0);
  const [podiumIdx, setPodiumIdx] = useState(0);
  const ref = useRef(null);

  const load = () => {
    api('dashboard').then(d => {
      if (d.error !== 'not_authenticated') {
        setData(d);
        setAnimKey(k => k + 1);
        setPodiumIdx(0);
      }
      setLoading(false);
    }).catch(() => setLoading(false));
  };

  useEffect(() => { load(); ref.current = setInterval(load, 15000); return () => clearInterval(ref.current); }, []);

  if (loading) return (
    <div className="page" style={{textAlign:'center',paddingTop:120}}>
      <div style={{width:64,height:64,border:'4px solid var(--border)',borderTopColor:'var(--primary)',borderRadius:'50%',animation:'spin 1s linear infinite',margin:'0 auto 16px'}} />
      <div style={{height:14,width:200,background:'var(--bg2)',borderRadius:6,margin:'0 auto',animation:'pulse 1.5s ease-in-out infinite'}} />
    </div>
  );

  if (!data) return null;
  if (data.error || (!data.stats && !data.accuracy)) {
    return <div className="page" style={{padding:40,textAlign:'center'}}><div className="card" style={{padding:24,background:'rgba(220,53,69,0.06)',border:'1px solid rgba(220,53,69,0.15)'}}><I n="exclamation-triangle" style={{fontSize:32,color:'var(--danger)',marginBottom:12}} /><h3 style={{color:'var(--danger)',marginBottom:8}}>Dashboard Error</h3><p style={{color:'var(--text2)',fontSize:13}}>{data.message || 'Empty response from server - try refreshing the page.'}</p>{data.file && <p style={{fontSize:11,color:'var(--text3)',marginTop:8}}>{data.file}:{data.line}</p>}</div></div>;
  }

  const nr = data.nextRace;
  const unlockedAchs = (data.userAchievements || []);
  const prevTotal = (data.previousTotal ?? 0);

  const TeamBadge = ({ team }) => {
    const color = TEAM_COLORS[team] || '#555';
    const abbr = TEAM_ABBREV[team] || team?.substring(0,3).toUpperCase() || 'F1';
    return <div className="team-badge" style={{background:color}}>{abbr}</div>;
  };

  const nextRaceStr = nr?.country ? nr.country.toLowerCase().replace(/\s/g, '').replace(/grandprix/gi, '') + 'gp' : 'silverstone';
  const heroBg = nr?.hero || '';
  const lb = data.leaderboard || [];
  const maxRecentPts = Math.max(...(data.recentResults||[]).map(x=>x.total_points), 1);

  return (
    <div className="page">

      {/* ===== HERO (just image) ===== */}
      <div className="hero">
        <div className="hero-bg" style={heroBg ? {backgroundImage:'url('+heroBg+')'} : {}}></div>
        <div className="hero-overlay"></div>
      </div>

      {/* ===== RACE INFO (below hero) ===== */}
      {nr && <div className="race-info">
        <div className="race-info-left">
          <div style={{display:'flex',alignItems:'center',gap:6,marginBottom:6,flexWrap:'wrap'}}>
            <span className="badge badge-live" style={{fontSize:10,padding:'2px 8px'}}><I n="flag-checkered" style={{fontSize:10}} /> Round {nr.race_number}</span>
            {data.isDoublePoints && <span className="badge badge-gold" style={{fontSize:10,padding:'2px 8px'}}><I n="bolt" style={{fontSize:10}} /> 2x</span>}
            {data.predictionsOpen !== undefined && (
              data.predictionsOpen
                ? <span className="badge badge-live" style={{fontSize:10,padding:'2px 8px'}}><span style={{width:6,height:6,borderRadius:'50%',background:'var(--live)',display:'inline-block'}} /> Open</span>
                : <span className="badge badge-gray" style={{fontSize:10,padding:'2px 8px'}}><I n="lock" style={{fontSize:10}} /> Locked</span>
            )}
          </div>
          <div className="race-title">{nr.country}</div>
          <div className="race-meta">{nr.circuit_name ? nr.circuit_name + ' \u00B7 ' : ''}{nr ? new Date(nr.race_date).toLocaleDateString('en-US',{weekday:'short',month:'short',day:'numeric',year:'numeric'}) : ''}</div>
        </div>
        <div className="race-info-right">
          <div style={{textAlign:'right'}}>
            <div className="caps" style={{fontSize:9,marginBottom:2}}>Deadline</div>
            <div className="mono" style={{fontSize:13,color:'var(--accent)',fontWeight:700}}>{data.countdownText || '—'}</div>
          </div>
          <CountdownRing deadline={data.deadline} open={data.predictionsOpen} text={data.countdownText} progress={data.progressBarWidth} />
          {data.predictionsOpen && (
            <button onClick={()=>onNav('predict')} className="btn btn-primary btn-sm" style={{whiteSpace:'nowrap',marginLeft:4}}>
              Predict <I n="arrow-right" style={{fontSize:11}} />
            </button>
          )}
        </div>
      </div>}

      {/* ===== CONTENT STACK (zero gaps, 1px separator lines) ===== */}
      <div style={{display:'flex',flexDirection:'column',gap:'1px',background:'var(--border)',marginTop:24}}>

        {/* STAT GRID (4-up) */}
        <div style={{display:'grid',gridTemplateColumns:'repeat(4,1fr)',gap:'1px',background:'var(--border)'}}>
          {[
            { label:'Rank', val:'#'+(data.stats?.rank||'-'), icon:'crown' },
            { label:'Points', val:data.stats?.total_points??0, icon:'star' },
            { label:'Races', val:data.stats?.races_participated??0, icon:'flag-checkered' },
            { label:'Accuracy', val:data.accuracy+'%', icon:'crosshairs' }
          ].map((s,i) => (
            <div key={i} style={{background:'var(--surface)',padding:'16px 8px',textAlign:'center'}}>
              <div style={{fontSize:13,color:'var(--accent)',marginBottom:4}}><I n={s.icon} /></div>
              <div className="mono" style={{fontSize:22,fontWeight:700,color:'var(--text)'}}>{s.val}</div>
              <div className="caps" style={{fontSize:10,marginTop:2}}>{s.label}</div>
            </div>
          ))}
        </div>

        {/* LAST RACE */}
        {data.lastRace && (
          <a href={'#results?race_id='+data.lastRace.id} onClick={(e)=>{e.preventDefault();onNav('results?race_id='+data.lastRace.id)}}
            style={{display:'flex',alignItems:'center',gap:12,padding:'14px 18px',background:'var(--surface)',cursor:'pointer',textDecoration:'none'}}>
            <div style={{width:36,height:36,fontSize:20,display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>{data.lastRace.flag}</div>
            <div style={{flex:1,minWidth:0}}>
              <div className="caps" style={{marginBottom:2}}>Previous Race</div>
              <div style={{fontWeight:600,fontSize:14}}><span className="racing">{data.lastRace.country}</span> Grand Prix</div>
              <div style={{fontSize:12,color:'var(--text2)'}}>
                {data.lastRace.myScore ? <>Scored <strong style={{color:'var(--live)'}}>+{data.lastRace.myScore.total_points} pts</strong></> : <>Click to see results</>}
              </div>
            </div>
            {data.lastRace.myScore && (
              <div className="mono" style={{fontWeight:700,fontSize:20,color:'var(--live)'}}>+{data.lastRace.myScore.total_points}</div>
            )}
            <I n="chevron-right" style={{color:'var(--text3)',fontSize:14}} />
          </a>
        )}

        {/* STANDINGS (full) */}
        <div style={{background:'var(--surface)'}}>
          <div style={{padding:'14px 18px',display:'flex',justifyContent:'space-between',alignItems:'center',borderBottom:'1px solid var(--border)'}}>
            <span className="ch"><I n="trophy" /><span>Standings</span></span>
            <span style={{fontSize:12,color:'var(--text3)'}}>{lb.length} players</span>
          </div>
          <div style={{display:'grid',gridTemplateColumns:'36px 1fr 70px',padding:'8px 18px',fontSize:11,color:'var(--text3)',borderBottom:'1px solid var(--border-light)'}}>
            <span className="caps" style={{fontSize:10}}>Pos</span><span className="caps" style={{fontSize:10}}>Player</span><span className="caps" style={{fontSize:10,textAlign:'right'}}>Pts</span>
          </div>
          {lb.slice(0,10).map((p,i) => {
            const pos = i + 1;
            const isMe = data.auth?.username === p.username;
            return (
              <div key={i} style={{
                display:'grid',gridTemplateColumns:'36px 1fr 70px',padding:'8px 18px',
                borderBottom:i < Math.min(lb.length,10)-1 ? '1px solid var(--border-light)' : 'none',
                background:isMe ? 'var(--accent-soft)' : (i%2===0 ? '' : 'var(--surface-muted)')
              }}>
                <div style={{display:'flex',alignItems:'center',gap:4}}>
                  {pos <= 3 ? <span style={{fontSize:14}}>{['🥇','🥈','🥉'][pos-1]}</span>
                    : <span className="mono" style={{fontWeight:600,fontSize:12,color:'var(--text3)'}}>{pos}</span>}
                </div>
                <div style={{display:'flex',alignItems:'center',gap:8,minWidth:0}}>
                  <div style={{width:28,height:28,borderRadius:'50%',overflow:'hidden',background:'var(--surface-muted)',flexShrink:0}}>
                    <img src={getAvatarUrl(p.avatar_style,p.username)} style={{width:'100%',height:'100%',objectFit:'cover'}} />
                  </div>
                  <span style={{fontWeight:600,fontSize:13,color:isMe?'var(--accent)':'var(--text)'}}>{p.username}</span>
                  {isMe && <span className="badge badge-accent" style={{fontSize:8,padding:'1px 6px'}}>You</span>}
                </div>
                <div style={{display:'flex',alignItems:'center',justifyContent:'flex-end'}}>
                  <span className="mono" style={{fontWeight:700,fontSize:15}}>{p.total_points}</span>
                </div>
              </div>
            );
          })}
        </div>

        {/* TWO-COLUMN: Recent Results + Race Winners */}
        <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:'1px',background:'var(--border)'}}>

          {/* Recent Results (left) — user own scores */}
          <div style={{background:'var(--surface)',padding:'16px'}}>
            <div className="ch" style={{marginBottom:6}}><I n="flag-checkered" /><span>Recent Results</span></div>
            {data.recentResults?.[0] && data.totalRaces ? (
              <div style={{fontSize:11,color:'var(--text2)',marginBottom:10,display:'flex',alignItems:'center',gap:8}}>
                <span className="mono" style={{fontWeight:600,color:'var(--text)'}}>R{data.recentResults[0].race_number}/{data.totalRaces}</span>
                {data.mostPickedWinner && (
                  <span>· Most picked winner: <strong>{data.mostPickedWinner.driver_name}</strong> ({data.mostPickedWinner.count}/{data.mostPickedWinner.total} players)</span>
                )}
              </div>
            ) : null}
            {data.recentResults?.length > 0 ? data.recentResults.map((r,i) => (
              <a key={i} href={'#results?race_id='+r.race_id} onClick={(e)=>{e.preventDefault();onNav('results?race_id='+r.race_id)}}
                style={{display:'flex',alignItems:'center',gap:10,padding:'8px 0',borderBottom:i<data.recentResults.length-1?'1px solid var(--border-light)':'none',cursor:'pointer',textDecoration:'none'}}>
                <div style={{width:28,height:28,fontSize:16,display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0,background:'var(--surface-muted)'}}>{r.flag || '🏁'}</div>
                <div style={{flex:1,minWidth:0}}>
                  <div style={{fontWeight:600,fontSize:13}}><span className="racing">{r.country}</span> GP</div>
                  <div style={{fontSize:11,color:'var(--text2)'}}>{new Date(r.race_date).toLocaleDateString('en-US',{month:'short',day:'numeric'})}</div>
                </div>
                <div className="mono" style={{fontWeight:700,fontSize:18,color:'var(--live)'}}>+{r.total_points}</div>
              </a>
            )) : (
              <div style={{textAlign:'center',padding:'20px 0',color:'var(--text3)',fontSize:13}}>No races yet</div>
            )}
          </div>

          {/* Race Winners (right) — top 3 users per race */}
          <div style={{background:'var(--surface)',padding:'16px'}}>
            <div className="ch" style={{marginBottom:12}}><I n="trophy" /><span>Race Winners</span></div>
            {data && (()=>{
              const podiums = data.racePodiums || [];
              if (podiums.length === 0) return <div style={{textAlign:'center',padding:'20px 0',color:'var(--text3)',fontSize:13}}>No races yet</div>;
              const total = podiums.length;
              const pIdx = podiumIdx < total ? podiumIdx : 0;
              const race = podiums[pIdx];
              const medal = ['🥇','🥈','🥉'];
              const posColors = ['#C9A96E','#A8A5A0','#B08050'];
              return (
                <>
                  <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',marginBottom:10}}>
                    <button onClick={()=>setPodiumIdx(pIdx>0?pIdx-1:total-1)} style={{background:'none',border:'1px solid var(--border)',padding:'4px 8px',cursor:'pointer',fontSize:11,color:'var(--text2)'}}><I n="chevron-left" /></button>
                    <span style={{fontSize:10,color:'var(--text3)',fontWeight:500}}>{pIdx+1} / {total}</span>
                    <button onClick={()=>setPodiumIdx(pIdx<total-1?pIdx+1:0)} style={{background:'none',border:'1px solid var(--border)',padding:'4px 8px',cursor:'pointer',fontSize:11,color:'var(--text2)'}}><I n="chevron-right" /></button>
                  </div>
                  <div style={{padding:'10px',background:'var(--surface-muted)',border:'1px solid var(--border-light)'}}>
                    <a href={'#results?race_id='+race.id} onClick={(e)=>{e.preventDefault();onNav('results?race_id='+race.id)}} style={{display:'flex',alignItems:'center',justifyContent:'space-between',marginBottom:8,cursor:'pointer',textDecoration:'none'}}>
                      <div style={{fontWeight:600,fontSize:12,display:'flex',alignItems:'center',gap:6}}>
                        <span>{race.flag}</span>
                        <span className="racing" style={{fontSize:17}}>{race.country}</span>
                        <span style={{fontWeight:400,color:'var(--text3)',fontSize:11}}>GP</span>
                      </div>
                      <span style={{fontSize:10,color:'var(--text3)'}}>{new Date(race.race_date).toLocaleDateString('en-US',{month:'short',day:'numeric'})}</span>
                    </a>
                    <div style={{display:'flex',flexDirection:'column',gap:3}}>
                      {race.podium.map((d,pi) => (
                        <div key={pi} style={{display:'flex',alignItems:'center',gap:6,padding:'4px 6px',fontSize:12,background:pi===0?'rgba(201,169,110,0.08)':'transparent'}}>
                          <span style={{fontSize:15}}>{medal[pi]}</span>
                          <span style={{fontWeight:700,color:posColors[pi],fontSize:12,minWidth:20}}>{'P'+(pi+1)}</span>
                          <div style={{width:20,height:20,borderRadius:'50%',overflow:'hidden',background:'var(--surface-muted)',flexShrink:0}}>
                            <img src={getAvatarUrl(d.avatar_style,d.username)} style={{width:'100%',height:'100%',objectFit:'cover'}} />
                          </div>
                          <span style={{fontWeight:600,fontSize:13}}>{d.username}</span>
                          <span className="mono" style={{fontSize:12,fontWeight:700,color:'var(--success)',marginLeft:'auto'}}>+{d.total_points}</span>
                        </div>
                      ))}
                    </div>
                  </div>
                </>
              );
            })()}
          </div>

        </div>

        {/* Next Round */}
        {data.nextRace && (
          <div style={{display:'grid',gridTemplateColumns:'1fr',gap:'1px',background:'var(--border)'}}>
            <div style={{background:'var(--surface)',padding:'20px'}}>
              <div className="ch" style={{marginBottom:16}}><I n="flag-checkered" /><span>Next Round</span></div>
              <div style={{display:'flex',alignItems:'flex-start',gap:20}}>
                {/* Left: Round number large */}
                {data.totalRaces ? (
                  <div style={{flexShrink:0,textAlign:'center',minWidth:70}}>
                    <div className="mono" style={{fontSize:40,fontWeight:800,lineHeight:1,color:'var(--accent)'}}>{data.nextRace.race_number}</div>
                    <div style={{fontSize:10,color:'var(--text3)',textTransform:'uppercase',letterSpacing:'0.08em',marginTop:2}}>/ {data.totalRaces}</div>
                  </div>
                ) : null}
                {/* Right: Track details + button */}
                <div style={{flex:1,minWidth:0}}>
                  <div style={{display:'flex',alignItems:'center',gap:8,marginBottom:4}}>
                    <span style={{fontSize:22}}>{data.nextRace.flag}</span>
                    <span className="racing" style={{fontSize:22,lineHeight:1}}>{data.nextRace.country}</span>
                    <span style={{fontWeight:400,color:'var(--text3)',fontSize:14}}>GP</span>
                  </div>
                  <div style={{fontSize:12,color:'var(--text2)',marginBottom:2}}>{data.nextRace.circuit_name}</div>
                  <div style={{fontSize:11,color:'var(--text3)',marginBottom:16}}>{new Date(data.nextRace.race_date).toLocaleDateString('en-US',{month:'long',day:'numeric',year:'numeric'})}</div>

                  {/* Most picked winner for this race */}
                  {data.mostPickedNextWinner && (
                    <div style={{fontSize:11,color:'var(--text2)',marginBottom:14,padding:'8px 10px',background:'var(--surface-muted)',border:'1px solid var(--border-light)'}}>
                      <i className="fa-solid fa-users" style={{marginRight:6,color:'var(--text3)'}}></i>
                      Most picked winner: <strong>{data.mostPickedNextWinner.driver_name}</strong>
                      <span style={{color:'var(--text3)',marginLeft:4}}>({data.mostPickedNextWinner.count}/{data.mostPickedNextWinner.total} players)</span>
                    </div>
                  )}

                  <button className="btn btn-sm" style={{background:'var(--text)',color:'#fff',padding:'8px 18px',fontSize:12,fontWeight:600,border:'none',cursor:'pointer',fontFamily:'Inter,sans-serif'}}
                    onClick={()=>onNav('predict')}>
                    Make Predictions <i className="fa-solid fa-arrow-right" style={{fontSize:10,marginLeft:4}}></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* TWO-COLUMN: Upcoming + Trophy */}
        <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:'1px',background:'var(--border)'}}>

          {/* Upcoming */}
          <div style={{background:'var(--surface)',padding:'16px'}}>
            <div className="ch" style={{marginBottom:12}}><I n="calendar-alt" /><span>Upcoming</span></div>
            {data.upcomingRaces?.length > 0 ? data.upcomingRaces.map((r,i) => (
              <div key={i} style={{display:'flex',alignItems:'center',gap:10,padding:'8px 0',borderBottom:i<data.upcomingRaces.length-1?'1px solid var(--border-light)':'none',opacity:r.unlocked?1:0.45}}>
                <div style={{fontSize:20,width:28,textAlign:'center',flexShrink:0}}>{r.flag}</div>
                <div style={{flex:1}}>
                  <div style={{fontWeight:600,fontSize:13}}><span className="racing">{r.country}</span> GP</div>
                  <div style={{fontSize:11,color:'var(--text2)'}}>{new Date(r.race_date).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'})}</div>
                </div>
                {r.unlocked && r.is_open ? <span className="badge badge-live" style={{fontSize:10}}>Open</span>
                  : r.unlocked && !r.is_open ? <span className="badge badge-gray" style={{fontSize:10}}>Closed</span>
                  : <span className="badge badge-gray" style={{fontSize:10}}>Locked</span>}
              </div>
            )) : (
              <div style={{textAlign:'center',padding:'16px 0',color:'var(--text3)',fontSize:13}}>Season complete</div>
            )}
          </div>

          {/* Trophy Cabinet */}
          <div style={{background:'var(--surface)',padding:'20px'}}>
            <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:14}}>
              <span className="ch" style={{fontSize:11}}><I n="trophy" /><span>Trophy Cabinet</span></span>
              <button className="btn btn-sm btn-outline" style={{fontSize:10}} onClick={()=>onNav('achievements')}>All</button>
            </div>

            <div style={{display:'flex',gap:16,flexWrap:'wrap'}}>
              {/* LEFT: Quick Stats + Next Up */}
              <div style={{flex:'0 0 140px',minWidth:120}}>
                <div style={{textAlign:'center',padding:'12px 8px',border:'1px solid var(--border-light)',background:'var(--surface-muted)'}}>
                  <div className="mono" style={{fontSize:28,fontWeight:700,color:'var(--accent)'}}>{unlockedAchs.length}</div>
                  <div className="caps" style={{fontSize:9}}>Unlocked</div>
                  <div className="mono" style={{fontSize:13,fontWeight:600,color:'var(--text2)',marginTop:4}}>/ {ALL_ACHIEVEMENTS.length}</div>
                  <div style={{marginTop:8,height:3,background:'var(--border)',borderRadius:2,overflow:'hidden'}}>
                    <div style={{height:'100%',width:Math.round((unlockedAchs.length/ALL_ACHIEVEMENTS.length)*100)+'%',background:'var(--accent)'}}></div>
                  </div>
                  <div style={{fontSize:10,fontWeight:600,marginTop:4,color:'var(--text2)'}}>{Math.round((unlockedAchs.length/ALL_ACHIEVEMENTS.length)*100)}%</div>
                </div>

                {/* Next up */}
                {unlockedAchs.length < ALL_ACHIEVEMENTS.length && (
                  <div style={{marginTop:10}}>
                    <div className="caps" style={{fontSize:9,marginBottom:6}}>Next Up</div>
                    {(()=>{
                      const locked = ALL_ACHIEVEMENTS.filter(a => !unlockedAchs.find(u => u.id === a.id));
                      return locked.slice(0,3).map((a,i) => {
                        const t = TIER_CONFIG[a.tier];
                        return <div key={i} style={{display:'flex',alignItems:'center',gap:6,padding:'4px 0',fontSize:11}}>
                          <span style={{fontSize:12,color:t.color2,width:16,textAlign:'center'}}><I n={a.icon.replace('fa-','')} /></span>
                          <span style={{color:'var(--text2)'}}>{a.name}</span>
                        </div>;
                      });
                    })()}
                  </div>
                )}
              </div>

              {/* RIGHT: Trophy Grid */}
              <div style={{flex:1,minWidth:200}}>
                {unlockedAchs.length > 0 ? (
                  <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fill,minmax(72px,1fr))',gap:6}}>
                    {unlockedAchs.slice(0,18).map((a,i) => {
                      const def = ALL_ACHIEVEMENTS.find(x => x.id === a.id);
                      if (!def) return null;
                      const t = TIER_CONFIG[def.tier] || TIER_CONFIG.common;
                      return (
                        <div key={a.id} style={{padding:'8px 4px',textAlign:'center',background:t.bg,border:'1px solid '+t.color2+'20',position:'relative'}} title={def.desc}>
                          <div style={{fontSize:18,color:t.color2,lineHeight:1,marginBottom:4}}><I n={def.icon.replace('fa-','')} /></div>
                          <div style={{fontSize:9,fontWeight:600,lineHeight:1.2,color:'var(--text)'}}>{def.name}</div>
                          <div style={{fontSize:7,position:'absolute',top:2,right:2,color:t.color2,opacity:0.5}}>{def.tier.charAt(0).toUpperCase()}</div>
                        </div>
                      );
                    })}
                    {unlockedAchs.length > 18 && (
                      <div style={{display:'flex',alignItems:'center',justifyContent:'center',padding:'8px',background:'var(--surface-muted)',border:'1px dashed var(--border)',cursor:'pointer',fontSize:10,color:'var(--text2)'}} onClick={()=>onNav('achievements')}>
                        +{unlockedAchs.length - 18} more
                      </div>
                    )}
                  </div>
                ) : (
                  <div style={{textAlign:'center',padding:'20px 0',color:'var(--text3)',fontSize:12}}>
                    <I n="trophy" style={{fontSize:24,color:'var(--text3)',marginBottom:6}} /><br />
                    No trophies yet — start predicting!
                  </div>
                )}
              </div>
            </div>
          </div>

        </div>

        {/* PROMO ROW (4 items) */}
        <div style={{display:'grid',gridTemplateColumns:'1fr 1fr 1fr 1fr',gap:'1px',background:'var(--border)'}}>
          <div style={{background:'var(--surface)',padding:'10px 12px',display:'flex',alignItems:'center',gap:10}}>
            <I n="star" style={{color:'var(--accent)',fontSize:14}} />
            <span style={{fontSize:11,color:'var(--text2)',lineHeight:1.3}}>Make picks before deadline</span>
          </div>
          <div style={{background:'var(--surface)',padding:'10px 12px',display:'flex',alignItems:'center',gap:10}}>
            <I n="trophy" style={{color:'var(--gold)',fontSize:14}} />
            <span style={{fontSize:11,color:'var(--text2)',lineHeight:1.3}}>Double points races active</span>
          </div>
          <div style={{background:'var(--surface)',padding:'10px 12px',display:'flex',alignItems:'center',gap:10}}>
            <I n="bolt" style={{color:'var(--accent)',fontSize:14}} />
            <span style={{fontSize:11,color:'var(--text2)',lineHeight:1.3}}>Check the leaderboard</span>
          </div>
          <div style={{background:'var(--surface)',padding:'10px 12px',display:'flex',alignItems:'center',gap:10,borderLeft:'1px dashed var(--border)'}}>
            <I n="calendar-alt" style={{color:'var(--text2)',fontSize:14}} />
            <span style={{fontSize:11,color:'var(--text2)',lineHeight:1.3}}>Add to Calendar</span>
            <a href="calendar.php" target="_blank" className="btn btn-sm btn-outline" style={{fontSize:9,marginLeft:'auto',whiteSpace:'nowrap'}}><I n="download" /> .ics</a>
          </div>
        </div>

        {/* SCANERRIFIC */}
        <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:'1px',background:'var(--border)'}}>
          <a href="https://scanerrific.com" target="_blank" rel="noopener" style={{background:'var(--surface)',padding:'16px',display:'flex',alignItems:'center',justifyContent:'center',textDecoration:'none',minHeight:80}}>
            <img src="/assets/logo_refreshed_scanerrific_no_bg_black.png" alt="Scanerrific" style={{maxHeight:36,objectFit:'contain'}} />
          </a>
          <div style={{background:'var(--surface)',padding:'12px 16px',display:'flex',alignItems:'center',justifyContent:'center',gap:10,flexWrap:'wrap'}}>
            <span style={{fontSize:11,fontWeight:600,color:'var(--text)',whiteSpace:'nowrap'}}>Fuel Your NPD</span>
            <a href="https://scanerrific.com" target="_blank" rel="noopener" className="btn btn-sm btn-outline" style={{fontSize:9}}>Scanerrific.com</a>
            <div style={{display:'flex',gap:6}}>
              <a href="https://x.com/Scanerrific" target="_blank" rel="noopener" style={{width:22,height:22,display:'flex',alignItems:'center',justifyContent:'center',background:'var(--surface-muted)',fontSize:10,color:'var(--text2)'}}><i className="fa-brands fa-twitter"></i></a>
              <a href="https://www.facebook.com/profile.php?id=61574704850921" target="_blank" rel="noopener" style={{width:22,height:22,display:'flex',alignItems:'center',justifyContent:'center',background:'var(--surface-muted)',fontSize:10,color:'var(--text2)'}}><i className="fa-brands fa-facebook-f"></i></a>
              <a href="https://www.linkedin.com/company/86236157" target="_blank" rel="noopener" style={{width:22,height:22,display:'flex',alignItems:'center',justifyContent:'center',background:'var(--surface-muted)',fontSize:10,color:'var(--text2)'}}><i className="fa-brands fa-linkedin-in"></i></a>
            </div>
          </div>
        </div>

      </div>

      {/* ===== FLOATING ACTIONS ===== */}
      <div className="float-actions">
        <button className="float-btn" onClick={()=>onNav('predict')}><I n="pencil-alt" /></button>
        <button className="float-btn" onClick={()=>window.location.reload()}><I n="sync" /></button>
      </div>

    </div>
  );
};

const getLevel = (pts) => {
  if (pts >= 1000) return {label:'Legend',icon:'crown',color:'var(--danger)'};
  if (pts >= 500) return {label:'Expert',icon:'star',color:'var(--primary)'};
  if (pts >= 250) return {label:'Veteran',icon:'shield',color:'var(--accent)'};
  if (pts >= 100) return {label:'Pro',icon:'rocket',color:'var(--success)'};
  return {label:'Rookie',icon:'seedling',color:'var(--text2)'};
};

const LeaderboardPage = () => {
  const [d, setD] = useState(null);
  const [expanded, setExpanded] = useState(null);
  useEffect(() => { api('leaderboard').then(setD).catch(() => setD(null)); }, []);
  const lb = d?.leaderboard || [];
  const medalIcons = ['crown','medal','medal'];
  const medalColors = ['#FFD700','#C0C0C0','#CD7F32'];
  const gradColors = [
    'linear-gradient(135deg,rgba(255,215,0,0.08),rgba(255,180,0,0.02))',
    'linear-gradient(135deg,rgba(192,192,192,0.06),rgba(160,160,160,0.02))',
    'linear-gradient(135deg,rgba(205,127,50,0.06),rgba(180,100,30,0.02))'
  ];

  const top3 = lb.slice(0, 3);
  const rest = lb.slice(3);

  const toggleExpand = (id) => setExpanded(expanded === id ? null : id);

  const unlockedIds = (p) => (p.all_achievements || '').split(',').filter(Boolean);

  const AchievementDropdown = ({p,isOpen}) => {
    const ids = unlockedIds(p);
    return (
      <div className={'ach-dropdown' + (isOpen ? ' open' : '')}>
        <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fill,minmax(140px,1fr))',gap:'4px'}}>
          {ALL_ACHIEVEMENTS.map(a => {
            const unlocked = ids.includes(a.id);
            const t = TIER_CONFIG[a.tier] || TIER_CONFIG.common;
            return (
              <div key={a.id} style={{
                display:'flex',alignItems:'center',gap:'6px',padding:'5px 6px',
                background:unlocked ? t.bg : 'var(--surface-muted)',
                border:'1px solid ' + (unlocked ? t.color2+'30' : 'var(--border)'),
                fontSize:'10px',lineHeight:1.2,
                opacity:unlocked ? 1 : 0.35
              }} title={a.desc}>
                <span style={{fontSize:'12px',color:unlocked?t.color2:'var(--text3)',width:14,textAlign:'center'}}>
                  {unlocked ? <I n={a.icon.replace('fa-','')} /> : <I n="lock" />}
                </span>
                <span style={{color:unlocked?'var(--text)':'var(--text3)',fontWeight:unlocked?600:400}}>{a.name}</span>
                {unlocked && <span style={{marginLeft:'auto',fontSize:'7px',color:t.color2,fontWeight:700}}>✓</span>}
              </div>
            );
          })}
        </div>
      </div>
    );
  };

  return (
    <div className="page" style={{maxWidth:800}}>
      <div className="anim" style={{textAlign:'center',marginBottom:'32px'}}>
        <div style={{fontSize:'11px',fontWeight:'700',textTransform:'uppercase',letterSpacing:'0.12em',color:'var(--primary)',marginBottom:'6px'}}>Season 2026</div>
        <h1 style={{fontSize:'32px',fontWeight:'900',letterSpacing:'-0.02em',marginBottom:'4px'}}>Championship <span style={{background:'linear-gradient(135deg,var(--primary),#8B0000)',WebkitBackgroundClip:'text',WebkitTextFillColor:'transparent'}}>Standings</span></h1>
        <p style={{color:'var(--text2)',fontSize:'14px'}}>{lb.length} players competing</p>
      </div>

      {top3.length > 0 && (
        <div className="anim anim-d1" style={{display:'grid',gridTemplateColumns:'1fr 1.2fr 1fr',gap:'0',alignItems:'end',marginBottom:'32px',padding:'0 20px'}}>
          {[1,0,2].map(pi => {
            const p = top3[pi];
            if (!p) return <div key={pi}></div>;
            const isFirst = pi === 0;
            const col = medalColors[pi];
            const isOpen = expanded === p.id;
            return (
              <div key={p.id} style={{textAlign:'center',transform:isFirst?'translateY(-12px)':'none',position:'relative'}}>
                <div style={{background:gradColors[pi],borderRadius:'20px',border:'1px solid ' + col + '30',padding:isFirst?'28px 16px 18px':'20px 14px 16px',margin:'0 4px'}}>
                  <div style={{fontSize:'28px',marginBottom:'8px',color:col}}>
                    <I n={medalIcons[pi]} />
                  </div>
                  <div style={{width:isFirst?'72px':'56px',height:isFirst?'72px':'56px',borderRadius:'50%',overflow:'hidden',margin:'0 auto 10px',border:'3px solid ' + col,background:'var(--card2)'}}>
                    <img src={getAvatarUrl(p.avatar_style,p.username)} style={{width:'100%',height:'100%',objectFit:'cover'}} />
                  </div>
                  <div style={{fontWeight:'800',fontSize:isFirst?'16px':'14px',marginBottom:'2px',cursor:'pointer'}} onClick={()=>toggleExpand(p.id)}>
                    {p.username} <I n={isOpen?'chevron-up':'chevron-down'} style={{fontSize:'10px',color:'var(--text3)'}} />
                  </div>
                  <div style={{fontSize:'10px',color:'var(--text2)',marginBottom:'8px'}}>
                    <span style={{display:'inline-flex',alignItems:'center',gap:'4px',padding:'2px 8px',borderRadius:'999px',background:getLevel(p.total_points).color+'15',color:getLevel(p.total_points).color,fontWeight:'700',fontSize:'9px',textTransform:'uppercase',letterSpacing:'0.04em'}}>
                      <I n={getLevel(p.total_points).icon} style={{fontSize:'8px'}} /> {getLevel(p.total_points).label}
                    </span>
                  </div>
                  <div style={{fontWeight:'900',fontSize:isFirst?'28px':'22px',color:col}}>{p.total_points}</div>
                  <div style={{fontSize:'9px',color:'var(--text2)',textTransform:'uppercase',letterSpacing:'0.06em',marginTop:'2px'}}>Points</div>
                  {d?.auth?.username===p.username && (
                    <div style={{marginTop:'8px'}}><span className="badge badge-accent" style={{fontSize:'9px',padding:'2px 10px'}}>You</span></div>
                  )}
                </div>
                <div style={{fontSize:'12px',fontWeight:'700',color:col,marginTop:'10px',textTransform:'uppercase',letterSpacing:'0.08em'}}>
                  {pi === 0 ? 'Championship Leader' : pi === 1 ? '2nd Place' : '3rd Place'}
                </div>
                {isOpen && <div style={{height:4}} />}
                <AchievementDropdown p={p} isOpen={isOpen} />
              </div>
            );
          })}
        </div>
      )}

      <div className="anim anim-d2">
        <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',padding:'0 4px 12px',borderBottom:'1px solid var(--border)',marginBottom:'4px'}}>
          <span style={{fontSize:'11px',fontWeight:'600',color:'var(--text3)',textTransform:'uppercase',letterSpacing:'0.06em'}}>Rank</span>
          <span style={{fontSize:'11px',fontWeight:'600',color:'var(--text3)',textTransform:'uppercase',letterSpacing:'0.06em',flex:1,marginLeft:'12px'}}>Player</span>
          <span style={{fontSize:'11px',fontWeight:'600',color:'var(--text3)',textTransform:'uppercase',letterSpacing:'0.06em',marginRight:'30px'}}>Races</span>
          <span style={{fontSize:'11px',fontWeight:'600',color:'var(--text3)',textTransform:'uppercase',letterSpacing:'0.06em'}}>Points</span>
        </div>
        <div className="card" style={{padding:'4px 16px'}}>
          {rest.map((p,i) => {
            const rank = i + 4;
            const isOpen = expanded === p.id;
            return (
              <React.Fragment key={p.id}>
                <div className={'lb-row'+(d?.auth?.username===p.username?' me':'')} style={{padding:'10px 8px',cursor:'pointer'}} onClick={()=>toggleExpand(p.id)}>
                  <div style={{width:'28px',textAlign:'center',fontWeight:'700',fontSize:'14px',color:'var(--text2)'}}>{rank}</div>
                  <div style={{width:'34px',height:'34px',borderRadius:'50%',overflow:'hidden',background:'var(--card2)',flexShrink:0}}>
                    <img src={getAvatarUrl(p.avatar_style,p.username)} style={{width:'100%',height:'100%',objectFit:'cover'}} />
                  </div>
                  <div className="lb-name" style={{marginLeft:'8px'}}>
                    <div className="lb-user" style={{fontSize:'14px',display:'flex',alignItems:'center',gap:'4px',flexWrap:'wrap'}}>
                      <span style={{borderBottom:'1px dotted var(--text3)'}}>{p.username}</span>
                      {d?.auth?.username===p.username && <span className="badge badge-purple" style={{fontSize:'8px',padding:'1px 6px'}}>You</span>}
                      <I n={isOpen?'chevron-up':'chevron-down'} style={{fontSize:'9px',color:'var(--text3)'}} />
                    </div>
                    <div className="lb-lvl" style={{fontSize:'10px',display:'flex',alignItems:'center',gap:'4px',marginTop:'2px'}}>
                      <span style={{display:'inline-flex',alignItems:'center',gap:'3px',padding:'1px 6px',borderRadius:'999px',background:getLevel(p.total_points).color+'15',color:getLevel(p.total_points).color,fontWeight:'600',fontSize:'8px',textTransform:'uppercase',letterSpacing:'0.03em'}}>
                        <I n={getLevel(p.total_points).icon} style={{fontSize:'7px'}} /> {getLevel(p.total_points).label}
                      </span>
                    </div>
                  </div>
                  <div style={{fontSize:'12px',color:'var(--text2)',fontWeight:'500',marginLeft:'auto',marginRight:'24px'}}>{p.races_participated||0}</div>
                  <div style={{fontWeight:'800',fontSize:'18px',color:'var(--success)',minWidth:'50px',textAlign:'right',fontFamily:'JetBrains Mono,monospace'}}>{p.total_points}</div>
                </div>
                <AchievementDropdown p={p} isOpen={isOpen} />
              </React.Fragment>
            );
          })}
          {rest.length === 0 && top3.length > 0 && (
            <div style={{textAlign:'center',padding:'32px',color:'var(--text3)',fontSize:'13px'}}>No more players in the standings</div>
          )}
          {lb.length === 0 && (
            <div style={{textAlign:'center',padding:'48px',color:'var(--text3)',fontSize:'14px'}}>
              <I n="trophy" style={{fontSize:'36px',display:'block',margin:'0 auto 12px',opacity:0.2}} />
              No players yet
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

const PredictPage = ({ onNav }) => {
  const [data, setData] = useState(null);
  const [drivers, setDrivers] = useState([]);
  const [editing, setEditing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [toast, setToast] = useState(null);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [renderTick, setRenderTick] = useState(0);
  const listRef = useRef(null);
  const sortRef = useRef(null);
  const driversRef = useRef([]);

  const buildList = (ordered) => {
    if (!listRef.current) return;
    driversRef.current = ordered;
    const canEdit = data?.predictionsOpen && (editing || !data?.hasPrediction);
    const term = searchTerm.toLowerCase();
    listRef.current.innerHTML = ordered.map((d, i) => {
      const match = term ? d.driver_name.toLowerCase().includes(term) || (d.team && d.team.toLowerCase().includes(term)) : true;
      return `<div class="driver-row${!match?' hidden':''}" data-driver-id="${d.id}">
        <div class="driver-pos">${i+1}</div>
        <div class="team-badge" style="background:${TEAM_COLORS[d.team]||'#64748b'}">${TEAM_ABBREV[d.team]||d.team?.substring(0,3)?.toUpperCase()||'???'}</div>
        <div style="flex:1;min-width:0">
          <div class="driver-name">${d.driver_name}</div>
          <div class="team-name">${d.team||''}</div>
        </div>
        ${canEdit ? `<div style="display:flex;gap:3px;margin-left:auto">
          <button class="move-btn" data-dir="up" data-idx="${i}"><i class="fa-solid fa-chevron-up"></i></button>
          <button class="move-btn" data-dir="down" data-idx="${i}"><i class="fa-solid fa-chevron-down"></i></button>
        </div>` : ''}
      </div>`;
    }).join('');
    if (!canEdit) { if (sortRef.current) { sortRef.current.destroy(); sortRef.current = null; } return; }
    if (sortRef.current) sortRef.current.destroy();
    sortRef.current = Sortable.create(listRef.current, {
      animation: 150, ghostClass: 'sortable-ghost', dragClass: 'sortable-drag', handle: '.driver-row',
      onEnd: () => {
        const items = listRef.current.querySelectorAll('.driver-row');
        const newOrder = [];
        items.forEach(el => {
          const id = el.getAttribute('data-driver-id');
          const driver = driversRef.current.find(d => d.id === id);
          if (driver) newOrder.push(driver);
        });
        if (newOrder.length === driversRef.current.length) {
          driversRef.current = newOrder;
          setRenderTick(t => t + 1);
        }
      }
    });
  };

  const load = () => {
    api('predict').then(d => {
      if (d.error) { setLoading(false); return; }
      setData(d);
      let ordered = [...(d.drivers || [])];
      const ex = d.existingPredictions || {};
      if (d.hasPrediction && Object.keys(ex).length > 0) {
        ordered.sort((a,b) => (ex[a.id]||999) - (ex[b.id]||999));
      }
      setDrivers(ordered);
      driversRef.current = ordered;
      setLoading(false);
    }).catch(() => setLoading(false));
  };

  useEffect(() => { load(); }, []);

  useEffect(() => {
    if (!drivers.length || !data) return;
    buildList(drivers);
  }, [drivers, editing, data?.predictionsOpen, data?.hasPrediction, searchTerm]);

  useEffect(() => {
    if (!listRef.current) return;
    const handler = (e) => {
      const btn = e.target.closest('.move-btn');
      if (!btn) return;
      e.stopPropagation();
      const dir = btn.getAttribute('data-dir');
      const idx = parseInt(btn.getAttribute('data-idx'));
      if (dir === 'up' && idx > 0) {
        const d = [...driversRef.current];
        [d[idx-1], d[idx]] = [d[idx], d[idx-1]];
        driversRef.current = d;
        setDrivers(d);
      } else if (dir === 'down' && idx < driversRef.current.length - 1) {
        const d = [...driversRef.current];
        [d[idx], d[idx+1]] = [d[idx+1], d[idx]];
        driversRef.current = d;
        setDrivers(d);
      }
    };
    listRef.current.addEventListener('click', handler);
    return () => { if (listRef.current) listRef.current.removeEventListener('click', handler); };
  }, [drivers]);

  const copyPrev = () => {
    const prev = data?.prevPredictions;
    if (!prev || Object.keys(prev).length === 0) {
      setToast({message:'No previous race predictions found to copy.',type:'error'});
      return;
    }
    const d = [...(data.drivers || [])];
    d.sort((a,b) => (prev[a.id]||999) - (prev[b.id]||999));
    driversRef.current = d;
    setDrivers(d);
    setEditing(true);
  };

  const calcConstructorProjection = () => {
    const pts = {};
    (driversRef.current.length ? driversRef.current : drivers).forEach((d,i) => {
      const pos = i + 1;
      if (pos <= 10) {
        if (!pts[d.team]) pts[d.team] = 0;
        pts[d.team] += F1_POINTS[pos-1];
      }
    });
    return Object.entries(pts).sort((a,b) => b[1] - a[1]);
  };

  const save = async () => {
    if (!data?.nextRace?.id) return;
    setSaving(true);
    const order = driversRef.current.length ? driversRef.current : drivers;
    const preds = order.map((d,i) => ({
      driver_id: d.id, driver_name: d.driver_name, predicted_position: i + 1
    }));
    const constructorPts = calcConstructorProjection();
    const constructorPreds = constructorPts.map(([name],i) => ({
      constructor_id: name, constructor_name: name, predicted_position: i + 1
    }));
    const r = await apiPost('save_predictions', {
      race_id: data.nextRace.id, predictions: preds, constructor_predictions: constructorPreds
    });
    setSaving(false);
    if (r.success) {
      setToast({message:'Predictions saved!',type:'success'});
      setTimeout(() => onNav('dashboard'), 1200);
    } else {
      setToast({message:r.message||'Failed to save',type:'error'});
    }
  };

  if (loading) return <div className="page" style={{textAlign:'center',paddingTop:120,color:'var(--text2)'}}><I n="spinner" /></div>;
  if (!data) return (
    <div className="page" style={{textAlign:'center',paddingTop:80}}>
      <div className="card" style={{maxWidth:400,margin:'0 auto',padding:40}}>
        <I n="calendar-times" style={{fontSize:48,color:'var(--text3)',marginBottom:16}} />
        <h3 style={{color:'var(--text)',marginBottom:8}}>Season Complete</h3>
        <p style={{color:'var(--text2)',fontSize:14,lineHeight:1.6}}>All races are done! Check back when the next season starts.</p>
        <button className="btn btn-outline btn-sm" style={{marginTop:12}} onClick={() => window.location.hash='dashboard'}>
          <I n="arrow-left" /> Back to Dashboard
        </button>
      </div>
    </div>
  );

  const nr = data.nextRace;
  const deadlineMs = data.deadline ? data.deadline * 1000 : 0;
  const constructorProjection = calcConstructorProjection();
  const canEdit = data.predictionsOpen && (editing || !data.hasPrediction);

  return (
    <div className="page">
      {toast && <Toast message={toast.message} type={toast.type} onClose={()=>setToast(null)} />}

      {/* ===== HERO (just image) ===== */}
      <div className="hero">
        <div className="hero-bg" style={nr?.hero ? {backgroundImage:'url('+nr.hero+')'} : {}}></div>
        <div className="hero-overlay"></div>
      </div>

      {/* ===== RACE INFO (below hero) ===== */}
      {nr && <div className="race-info" style={{marginBottom:20}}>
        <div className="race-info-left">
          <div style={{display:'flex',alignItems:'center',gap:6,marginBottom:4}}>
            <span className="badge badge-live" style={{fontSize:10,padding:'2px 8px'}}><I n="flag-checkered" style={{fontSize:10}} /> Round {nr.race_number}</span>
            {data.isDoublePoints && <span className="badge badge-gold" style={{fontSize:10,padding:'2px 8px'}}><I n="bolt" style={{fontSize:10}} /> 2x</span>}
          </div>
          <div className="race-title" style={{fontSize:22}}>{nr.country}</div>
          <div className="race-meta">{nr.circuit_name || ''}</div>
        </div>
        <div className="race-info-right">
          <div style={{textAlign:'right'}}>
            <div className="caps" style={{fontSize:9,marginBottom:2}}>Deadline</div>
            <div className="mono" style={{fontSize:13,color:'var(--accent)',fontWeight:700}}>{data.countdownText || '—'}</div>
          </div>
          <CountdownRing deadline={deadlineMs} open={data.predictionsOpen} text={data.countdownText} progress={data.progressBarWidth} />
        </div>
      </div>}

      {!data.predictionsOpen && (
        <div className="card" style={{padding:'14px 20px',marginBottom:'16px',background:'rgba(220,53,69,0.04)',borderColor:'rgba(220,53,69,0.12)'}}>
          <div style={{display:'flex',alignItems:'center',gap:'10px'}}>
            <I n="lock" style={{color:'var(--danger)'}} />
            <span style={{fontWeight:'600',fontSize:'14px',color:'var(--danger)'}}>Predictions Closed</span>
            <span style={{color:'var(--text2)',fontSize:'13px',marginLeft:'auto'}}>Deadline has passed</span>
          </div>
        </div>
      )}

      {data.predictionsOpen && data.hasPrediction && !editing && (
        <div className="card" style={{padding:'14px 20px',marginBottom:'16px',background:'rgba(40,167,69,0.04)',borderColor:'rgba(40,167,69,0.12)'}}>
          <div style={{display:'flex',alignItems:'center',gap:'10px'}}>
            <I n="check-circle" style={{color:'var(--success)'}} />
            <span style={{fontWeight:'600',fontSize:'14px',color:'var(--success)'}}>Prediction Submitted</span>
            <button className="btn btn-outline btn-sm" onClick={()=>setEditing(true)} style={{marginLeft:'auto'}}><I n="pencil" /> Edit</button>
          </div>
        </div>
      )}

      <div style={{display:'grid',gridTemplateColumns:'1fr 300px',gap:'16px',alignItems:'start'}}>
        <div className="card anim anim-d1" style={{padding:'12px'}}>
          <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:'8px',padding:'4px 8px'}}>
            <span style={{fontSize:'12px',fontWeight:'600',color:'var(--text2)',textTransform:'uppercase',letterSpacing:'0.04em'}}>
              <I n="list-ol" style={{marginRight:10}} />Driver Order
            </span>
            <div style={{display:'flex',gap:'6px'}}>
              {data.predictionsOpen && (
                <>
                  <button className="btn btn-outline btn-sm" onClick={copyPrev}><I n="copy" /> Copy Previous</button>
                  <button className="btn btn-primary btn-sm" onClick={save} disabled={saving}>
                    {saving ? 'Saving...' : <><I n="save" /> Save</>}
                  </button>
                </>
              )}
            </div>
          </div>
          {canEdit && (
            <div style={{padding:'0 8px 8px'}}>
              <div className="iw" style={{position:'relative'}}>
                <I n="search" style={{position:'absolute',left:'10px',top:'50%',transform:'translateY(-50%)',color:'var(--text3)',fontSize:'12px'}} />
                <input className="input" placeholder="Search drivers or teams..." value={searchTerm} onChange={e=>setSearchTerm(e.target.value)}
                  style={{paddingLeft:'28px',fontSize:'12px',padding:'7px 10px 7px 28px'}} />
              </div>
            </div>
          )}
          <div ref={listRef}></div>
        </div>

        <div className="card anim anim-d2" style={{padding:'14px 16px'}}>
          <div style={{fontSize:'12px',fontWeight:'600',color:'var(--text2)',textTransform:'uppercase',letterSpacing:'0.04em',marginBottom:'10px'}}>
            <I n="industry" style={{marginRight:10}} />Projected Constructors
          </div>
          {constructorProjection.length === 0 ? (
            <div style={{textAlign:'center',padding:'20px',color:'var(--text3)',fontSize:'12px'}}>Order drivers to see projections</div>
          ) : (
            constructorProjection.map(([team, pts], i) => {
              const color = TEAM_COLORS[team] || '#64748b';
              const abbr = TEAM_ABBREV[team] || team?.substring(0,3)?.toUpperCase() || '???';
              return (
                <div className="res-row" key={team} style={{padding:'8px 0'}}>
                  <div style={{width:'20px',textAlign:'center',fontWeight:'700',fontSize:'12px',color: i < 3 ? ['var(--orange)','#a0a0a0','#cd7f32'][i] : 'var(--text3)'}}>{i+1}</div>
                  <div className="team-badge" style={{background:color,width:'28px',height:'18px',fontSize:'7px'}}>{abbr}</div>
                  <div style={{fontSize:'12px',fontWeight:'600',flex:1}}>{team}</div>
                  <div style={{fontWeight:'700',fontSize:'14px',color:'var(--green)'}}>{pts}</div>
                </div>
              );
            })
          )}
        </div>
      </div>
    </div>
  );
};

const ResultsPage = ({ onNav }) => {
  const [d, setD] = useState(null);
  const [loading, setLoading] = useState(true);

  const loadRace = (rid) => {
    setLoading(true);
    api('results', { race_id: rid }).then(r => { setD(r); setLoading(false); }).catch(() => setLoading(false));
  };
  useEffect(() => {
    const h = window.location.hash.replace('#','');
    const q = h.split('?')[1];
    const p = new URLSearchParams(q || '');
    const rid = p.get('race_id');
    if (rid) {
      api('results', { race_id: rid }).then(r => { setD(r); setLoading(false); }).catch(() => setLoading(false));
    } else {
      api('results').then(r => { setD(r); setLoading(false); }).catch(() => setLoading(false));
    }
  }, []);

  if (loading) return <div className="page" style={{textAlign:'center',paddingTop:120,color:'var(--text2)'}}><I n="spinner" /></div>;
  if (!d) return null;

  if (!d.race) {
    return (
      <div className="page">
        <div className="anim" style={{marginBottom:'20px',textAlign:'center'}}>
          <h1 style={{fontSize:'24px',fontWeight:'800'}}>Race <span style={{color:'var(--accent)'}}>Results</span></h1>
          <p style={{color:'var(--text2)',fontSize:'13px',marginTop:'4px'}}>Select a race to view results</p>
        </div>
        <div className="card" style={{overflow:'hidden'}}>
          <table style={{width:'100%',borderCollapse:'collapse',fontSize:13}}>
            <thead>
              <tr style={{borderBottom:'1px solid var(--border)'}}>
                <td style={{padding:'10px 14px',textTransform:'uppercase',fontSize:10,fontWeight:600,letterSpacing:'0.08em',color:'var(--text3)'}}>Grand Prix</td>
                <td style={{padding:'10px 14px',textTransform:'uppercase',fontSize:10,fontWeight:600,letterSpacing:'0.08em',color:'var(--text3)'}}>Circuit</td>
                <td style={{padding:'10px 14px',textTransform:'uppercase',fontSize:10,fontWeight:600,letterSpacing:'0.08em',color:'var(--text3)'}}>Date</td>
                <td style={{padding:'10px 14px',textAlign:'right',textTransform:'uppercase',fontSize:10,fontWeight:600,letterSpacing:'0.08em',color:'var(--text3)'}}>Status</td>
              </tr>
            </thead>
            <tbody>
              {d.raceList?.map((rc,i) => (
                <tr key={rc.id} onClick={() => loadRace(rc.id)} style={{cursor:'pointer',borderBottom:i<d.raceList.length-1?'1px solid var(--border-light)':'none',transition:'background 150ms'}} onMouseOver={e=>e.currentTarget.style.background='var(--surface-muted)'} onMouseOut={e=>e.currentTarget.style.background='transparent'}>
                  <td style={{padding:'10px 14px'}}>
                    <div style={{display:'flex',alignItems:'center',gap:10}}>
                      <span style={{fontSize:22}}>{rc.flag}</span>
                      <span style={{fontWeight:600}}><span className="racing">{rc.country}</span> GP</span>
                    </div>
                  </td>
                  <td style={{padding:'10px 14px',color:'var(--text2)',fontSize:12}}>{rc.circuit_name||'—'}</td>
                  <td style={{padding:'10px 14px',color:'var(--text2)',fontSize:12}}>{new Date(rc.race_date).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'})}</td>
                  <td style={{padding:'10px 14px',textAlign:'right'}}>
                    {rc.status === 'completed' ? <span className="badge badge-green"><I n="check" style={{fontSize:9}} /> Results</span> :
                     rc.status === 'cancelled' ? <span className="badge badge-red"><I n="ban" style={{fontSize:9}} /> Cancelled</span> :
                     <span className="badge badge-gray"><I n="clock" style={{fontSize:9}} /> Upcoming</span>}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    );
  }

  const r = d.race;
  return (
    <div className="page">
      {/* ===== HERO ===== */}
      <div className="hero" style={{height:200}}>
        <div className="hero-bg" style={r?.hero ? {backgroundImage:'url('+r.hero+')'} : {}}></div>
        <div className="hero-overlay"></div>
      </div>

      {/* ===== RACE INFO ===== */}
      {r && <div className="race-info" style={{marginBottom:20}}>
        <div className="race-info-left">
          <div style={{display:'flex',alignItems:'center',gap:6,marginBottom:6,flexWrap:'wrap'}}>
            {r.flag && <span style={{fontSize:20}}>{r.flag}</span>}
          </div>
          <div className="race-title"><span className="racing">{r.country}</span> GP</div>
          <div className="race-meta">{r.circuit_name}{r.circuit_name && r.race_date ? ' \u00B7 ' : ''}{r.race_date ? new Date(r.race_date).toLocaleDateString('en-US',{month:'long',day:'numeric',year:'numeric'}) : ''}</div>
        </div>
        <div className="race-info-right">
          {d?.race?.status === 'completed' && <span className="badge badge-green" style={{fontSize:11,padding:'4px 12px'}}><I n="check" /> Results</span>}
        </div>
      </div>}

      {!d.hasResults ? (
        <div className="card" style={{padding:'24px',textAlign:'center'}}>
          <I n="clock" style={{fontSize:'32px',color:'var(--text3)',marginBottom:'10px'}} />
          <div style={{fontSize:'16px',fontWeight:'700',marginBottom:'6px'}}>Results Pending</div>
          <div style={{color:'var(--text2)',fontSize:'13px',marginBottom:'16px'}}>Race results are not yet available</div>
          <div style={{fontSize:'12px',fontWeight:'600',color:'var(--text2)',textTransform:'uppercase',letterSpacing:'0.04em',marginBottom:'10px'}}>Your Predictions</div>
          {d.predictions?.map((p,i) => (
            <div className="driver-row" key={i} style={{maxWidth:400,margin:'0 auto'}}>
              <div className="driver-pos">{p.predicted_position}</div>
              <TeamBadge team={p.team} />
              <div style={{flex:1,minWidth:0}}>
                <div className="driver-name">{p.driver_name}</div>
                <div className="team-name">{p.team}</div>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <>
          {d.scoreRecord && (
            <div style={{display:'grid',gridTemplateColumns:'repeat(4,1fr)',gap:1,background:'var(--border)',marginBottom:16}}>
              <div style={{background:'var(--surface)',padding:'16px 8px',textAlign:'center'}}>
                <div className="mono" style={{fontSize:22,fontWeight:700,color:'var(--live)'}}>{d.scoreRecord.total_points}</div>
                <div className="caps" style={{fontSize:10,marginTop:2}}>Total</div>
              </div>
              <div style={{background:'var(--surface)',padding:'16px 8px',textAlign:'center'}}>
                <div className="mono" style={{fontSize:22,fontWeight:700,color:'var(--text)'}}>{d.scoreRecord.driver_points}</div>
                <div className="caps" style={{fontSize:10,marginTop:2}}>Base</div>
              </div>
              <div style={{background:'var(--surface)',padding:'16px 8px',textAlign:'center'}}>
                <div className="mono" style={{fontSize:22,fontWeight:700,color:'var(--gold)'}}>{d.scoreRecord.top3_bonus}</div>
                <div className="caps" style={{fontSize:10,marginTop:2}}>Podium</div>
              </div>
              <div style={{background:'var(--surface)',padding:'16px 8px',textAlign:'center'}}>
                <div className="mono" style={{fontSize:22,fontWeight:700,color:'var(--accent)'}}>{d.scoreRecord.constructor_points}</div>
                <div className="caps" style={{fontSize:10,marginTop:2}}>Constructor</div>
              </div>
            </div>
          )}

          {/* ===== RACE LEADERBOARD ===== */}
          <div className="card anim anim-d1" style={{marginBottom:12}}>
            <div style={{padding:20}}>
              {/* Podium */}
              <div style={{display:'flex',justifyContent:'center',alignItems:'flex-end',gap:20,marginBottom:24,minHeight:150}}>
                {(()=>{
                  const t=d.raceLeaderboard?.slice(0,3)||[];
                  const p=t.length>=2?[t[1],t[0],t[2]]:t;
                  return p.map((x,i)=>{
                    if(!x)return null;
                    const f=i===1||(p.length===1&&i===0);
                    const s=i===0&&p.length>=2;
                    const c=f?'var(--gold)':s?'var(--silver)':'var(--bronze)';
                    const m=f?'\u{1F451}':s?'\u{1F948}':'\u{1F949}';
                    return <div key={i} style={{textAlign:'center'}}>
                      <div style={{width:f?56:48,height:f?56:48,borderRadius:'50%',overflow:'hidden',margin:'0 auto 6px',border:'2px solid '+c,background:'var(--surface-muted)'}}>
                        <img src={getAvatarUrl(x.avatar_style,x.username)} style={{width:'100%',height:'100%',objectFit:'cover'}} />
                      </div>
                      <div style={{fontWeight:700,fontSize:f?15:13,lineHeight:1.2}}>{x.username}</div>
                      <div className="mono" style={{fontWeight:700,fontSize:f?28:22,color:c,lineHeight:1.2,marginTop:2}}>+{x.total_points}</div>
                      <div style={{fontSize:28,marginTop:6}}>{m}</div>
                    </div>;
                  });
                })()}
              </div>
              {/* Leaderboard table */}
              <table style={{width:'100%',borderCollapse:'collapse',fontSize:13}}>
                <thead>
                  <tr style={{borderBottom:'1px solid var(--border)'}}>
                    <td style={{padding:'8px 10px',textTransform:'uppercase',fontSize:10,fontWeight:600,letterSpacing:'0.08em',color:'var(--text3)'}}>#</td>
                    <td style={{padding:'8px 10px',textTransform:'uppercase',fontSize:10,fontWeight:600,letterSpacing:'0.08em',color:'var(--text3)'}}>Player</td>
                    <td style={{padding:'8px 10px',textAlign:'right',textTransform:'uppercase',fontSize:10,fontWeight:600,letterSpacing:'0.08em',color:'var(--text3)'}}>Driver</td>
                    <td style={{padding:'8px 10px',textAlign:'right',textTransform:'uppercase',fontSize:10,fontWeight:600,letterSpacing:'0.08em',color:'var(--text3)'}}>Bonus</td>
                    <td style={{padding:'8px 10px',textAlign:'right',textTransform:'uppercase',fontSize:10,fontWeight:600,letterSpacing:'0.08em',color:'var(--text3)'}}>Total</td>
                  </tr>
                </thead>
                <tbody>{d.raceLeaderboard?.map((u,i)=>{
                  const me=d.auth?.username===u.username;
                  const bc=i===0?'var(--gold)':i===1?'var(--silver)':i===2?'var(--bronze)':'';
                  return <tr key={i} style={{
                    borderBottom:i<d.raceLeaderboard.length-1?'1px solid var(--border-light)':'none',
                    background:me?'var(--accent-soft)':'transparent'
                  }}>
                    <td style={{padding:'8px 10px',fontFamily:'JetBrains Mono,monospace',fontWeight:600,fontSize:13,color:bc||'var(--text3)'}}>{i+1}</td>
                    <td style={{padding:'8px 10px'}}>
                      <div style={{display:'flex',alignItems:'center',gap:8}}>
                        <div style={{width:26,height:26,borderRadius:'50%',overflow:'hidden',flexShrink:0,background:'var(--surface-muted)'}}>
                          <img src={getAvatarUrl(u.avatar_style,u.username)} style={{width:'100%',height:'100%',objectFit:'cover'}} />
                        </div>
                        <a href={'view-predictions.php?user_id='+u.user_id+'&race_id='+d.race.id} target="_blank" style={{fontWeight:600,fontSize:13,color:me?'var(--accent)':'var(--text)',textDecoration:'none'}}>{u.username}{me && <span className="badge badge-accent" style={{fontSize:7,padding:'1px 5px',marginLeft:4}}>You</span>}</a>
                      </div>
                    </td>
                    <td style={{padding:'8px 10px',textAlign:'right',fontFamily:'JetBrains Mono,monospace',fontSize:13,color:'var(--text2)'}}>{u.driver_points}</td>
                    <td style={{padding:'8px 10px',textAlign:'right',fontFamily:'JetBrains Mono,monospace',fontWeight:600,fontSize:13,color:u.top3_bonus+u.constructor_points>0?'var(--gold)':'var(--text3)'}}>{u.top3_bonus+u.constructor_points>0?'+'+(u.top3_bonus+u.constructor_points):0}</td>
                    <td style={{padding:'8px 10px',textAlign:'right',fontFamily:'JetBrains Mono,monospace',fontWeight:700,fontSize:16,color:me?'var(--accent)':'var(--success)'}}>{u.total_points}</td>
                  </tr>;
                })}</tbody>
              </table>
            </div>
          </div>

          {/* ===== OFFICIAL RESULTS ===== */}
          <div className="card anim anim-d2" style={{marginBottom:12}}>
            <div style={{padding:'14px 16px'}}>
              <div className="ch" style={{marginBottom:10}}><I n="flag-checkered" /><span>Official Results</span></div>
              {d.actualResults ? (
                <table style={{width:'100%',borderCollapse:'collapse',fontSize:13}}>
                  <thead>
                    <tr style={{borderBottom:'1px solid var(--border)'}}>
                      <td style={{padding:'8px 10px',textTransform:'uppercase',fontSize:10,fontWeight:600,letterSpacing:'0.08em',color:'var(--text3)'}}>Pos</td>
                      <td style={{padding:'8px 10px',textTransform:'uppercase',fontSize:10,fontWeight:600,letterSpacing:'0.08em',color:'var(--text3)'}}>Driver</td>
                      <td style={{padding:'8px 10px',textTransform:'uppercase',fontSize:10,fontWeight:600,letterSpacing:'0.08em',color:'var(--text3)'}}>Team</td>
                      <td style={{padding:'8px 10px',textAlign:'right',textTransform:'uppercase',fontSize:10,fontWeight:600,letterSpacing:'0.08em',color:'var(--text3)'}}>Pts</td>
                    </tr>
                  </thead>
                  <tbody>{d.actualResults.map((res,i)=>{
                    const tc=res.position<=3?['var(--gold)','var(--silver)','var(--bronze)'][res.position-1]:'';
                    const pts=res.points_earned||(res.position<=10?[25,18,15,12,10,8,6,4,2,1][res.position-1]:0);
                    return <tr key={i} style={{borderBottom:i<d.actualResults.length-1?'1px solid var(--border-light)':'none'}}>
                      <td style={{padding:'8px 10px',fontFamily:'JetBrains Mono,monospace',fontWeight:600,fontSize:13,color:tc||'var(--text3)'}}>{res.position}</td>
                      <td style={{padding:'8px 10px'}}>
                        <div style={{display:'flex',alignItems:'center',gap:6}}>
                          <TeamBadge team={res.constructor_name} />
                          <span style={{fontWeight:500}}>{res.driver_name}</span>
                          {res.fastest_lap>0&&<span style={{fontSize:9,color:'var(--accent)',fontWeight:600}}><I n="bolt" /> FL</span>}
                        </div>
                      </td>
                      <td style={{padding:'8px 10px',color:'var(--text2)',fontSize:12}}>{res.constructor_name}</td>
                      <td style={{padding:'8px 10px',textAlign:'right',fontFamily:'JetBrains Mono,monospace',fontWeight:700,fontSize:15,color:'var(--success)'}}>{pts}</td>
                    </tr>;
                  })}</tbody>
                </table>
              ) : (
                <div style={{textAlign:'center',padding:20,color:'var(--text3)',fontSize:13}}>
                  <I n="clock" style={{fontSize:24,marginBottom:8}} /><br />Official results not yet available
                </div>
              )}
            </div>
          </div>

          {/* ===== YOUR PREDICTIONS ===== */}
          <div className="card anim anim-d3" style={{marginBottom:12}}>
            <div style={{padding:'14px 16px'}}>
              <div className="ch" style={{marginBottom:10}}><I n="list" /><span>Your Predictions vs Actual</span></div>
              {d.predictions ? (
                <table style={{width:'100%',borderCollapse:'collapse',fontSize:13}}>
                  <thead>
                    <tr style={{borderBottom:'1px solid var(--border)'}}>
                      <td style={{padding:'8px 10px',textTransform:'uppercase',fontSize:10,fontWeight:600,letterSpacing:'0.08em',color:'var(--text3)'}}>Pred</td>
                      <td style={{padding:'8px 10px',textTransform:'uppercase',fontSize:10,fontWeight:600,letterSpacing:'0.08em',color:'var(--text3)'}}>Driver</td>
                      <td style={{padding:'8px 10px',textAlign:'center',textTransform:'uppercase',fontSize:10,fontWeight:600,letterSpacing:'0.08em',color:'var(--text3)'}}>Actual</td>
                      <td style={{padding:'8px 10px',textAlign:'right',textTransform:'uppercase',fontSize:10,fontWeight:600,letterSpacing:'0.08em',color:'var(--text3)'}}>Pts</td>
                    </tr>
                  </thead>
                  <tbody>{d.predictions.map((p,i)=>{
                    return <tr key={i} style={{borderBottom:i<d.predictions.length-1?'1px solid var(--border-light)':'none'}}>
                      <td style={{padding:'8px 10px',fontFamily:'JetBrains Mono,monospace',fontWeight:600,fontSize:13,color:'var(--text2)'}}>P{p.predicted_position}</td>
                      <td style={{padding:'8px 10px'}}>
                        <div style={{display:'flex',alignItems:'center',gap:6}}>
                          <TeamBadge team={p.team} />
                          <span style={{fontWeight:500}}>{p.driver_name}</span>
                        </div>
                      </td>
                      <td style={{padding:'8px 10px',textAlign:'center'}}>{p.actual_position?(
                        <span style={{display:'inline-flex',alignItems:'center',gap:6}}>
                          <span style={{fontFamily:'JetBrains Mono,monospace',fontWeight:600,fontSize:13,color:p.is_exact?'var(--live)':'var(--danger)'}}>P{p.actual_position}</span>
                          {p.is_exact?<span style={{fontSize:11,color:'var(--live)'}}><I n="check" /></span>:<span style={{fontSize:11,color:'var(--danger)'}}><I n="times" /></span>}
                        </span>
                      ):<span style={{color:'var(--text3)',fontSize:12}}>—</span>}</td>
                      <td style={{padding:'8px 10px',textAlign:'right',fontFamily:'JetBrains Mono,monospace',fontWeight:700,fontSize:15,color:p.points_earned>0?'var(--success)':'var(--text3)'}}>{p.points_earned>0?'+'+p.points_earned:0}</td>
                    </tr>;
                  })}</tbody>
                </table>
              ) : (
                <div style={{textAlign:'center',padding:20,color:'var(--text3)',fontSize:13}}>
                  <I n="file" style={{fontSize:24,marginBottom:8}} /><br />No predictions for this race
                </div>
              )}
            </div>
          </div>
        </>
      )}
    </div>
  );
};

const ProfilePage = ({ user, onNav }) => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState({full_name:'',username:'',current_password:'',new_password:'',confirm_password:''});
  const [msg, setMsg] = useState(null);
  const [toast, setToast] = useState(null);

  const load = () => { api('profile').then(d => { setData(d); setLoading(false); }).catch(() => setLoading(false)); };
  useEffect(() => { load(); }, []);

      const handleSave = async (action, body) => {
    const r = await apiPost('update_profile', { action, ...body });
    if (r.success) {
      setToast({message:r.message,type:'success'});
      setEditing(null);
      setForm({full_name:'',username:'',current_password:'',new_password:'',confirm_password:''});
      load();
    } else {
      setMsg({type:'error',text:r.message||'Failed'});
    }
  };

  const changeAvatar = async (style) => {
    await handleSave('update_avatar', { avatar_style: style });
  };

  if (loading) return <div className="page" style={{textAlign:'center',paddingTop:120,color:'var(--text2)'}}><I n="spinner" /></div>;
  if (!data) return null;

  const stats = data.stats || {};
  const avatars = data.allAvatars?.all?.avatars || {};
  const userAchievements = data.userAchievements || [];

  return (
    <div className="page" style={{maxWidth:700}}>
      {toast && <Toast message={toast.message} type={toast.type} onClose={()=>setToast(null)} />}

      <div className="card anim" style={{padding:'32px',textAlign:'center',marginBottom:'24px'}}>
        <div style={{width:'96px',height:'96px',borderRadius:'50%',overflow:'hidden',background:'var(--bg2)',margin:'0 auto 16px',border:'3px solid var(--primary)'}}>
          <img src={getAvatarUrl(data.currentAvatarStyle,data.auth?.username)} style={{width:'100%',height:'100%',objectFit:'cover'}} />
        </div>
        <h2 style={{fontSize:'24px',fontWeight:'800'}}>{data.auth?.username||''}</h2>
        <p style={{color:'var(--text2)',fontSize:'14px',marginBottom:'6px'}}>{data.auth?.email||''}</p>
          <div className="stat-grid" style={{marginTop:'20px',maxWidth:440,marginLeft:'auto',marginRight:'auto',gap:'12px'}}>
            <div className="card stat-box" style={{background:'var(--bg2)',padding:'18px 12px'}}>
              <div className="v" style={{color:'var(--primary)'}}>#{stats.rank||'-'}</div>
              <div className="l">Rank</div>
            </div>
            <div className="card stat-box" style={{background:'var(--bg2)',padding:'18px 12px'}}>
              <div className="v" style={{color:'var(--success)'}}>{stats.total_points||0}</div>
              <div className="l">Points</div>
            </div>
            <div className="card stat-box" style={{background:'var(--bg2)',padding:'18px 12px'}}>
              <div className="v" style={{color:'var(--accent)'}}>{stats.races_participated||0}</div>
              <div className="l">Races</div>
            </div>
            <div className="card stat-box" style={{background:'var(--bg2)',padding:'18px 12px'}}>
              <div className="v" style={{color:'var(--accent-warm)'}}>{data.accuracy||0}%</div>
              <div className="l">Accuracy</div>
            </div>
        </div>
      </div>

      <div className="card anim anim-d1" style={{padding:'24px',marginBottom:'24px'}}>
        <div className="caps-label" style={{fontSize:'14px',fontWeight:'700',marginBottom:'16px'}}>
          <I n="palette" style={{marginRight:10,color:'var(--primary)'}} />Choose Your Avatar
        </div>
        <div className="avatar-grid">
          {Object.entries(avatars).map(([key, label]) => (
            <div key={key} className={'avatar-option'+(data.currentAvatarStyle===key?' active':'')}
                 title={label} onClick={() => changeAvatar(key)}>
              <img src={getAvatarUrl(key,data.auth?.username)} alt={label} />
            </div>
          ))}
        </div>
      </div>

      <div className="card anim anim-d2" style={{padding:'24px',marginBottom:'24px'}}>
        <div className="caps-label" style={{fontSize:'14px',fontWeight:'700',marginBottom:'20px'}}>
          <I n="cog" style={{marginRight:10,color:'var(--primary)'}} />Account Settings
        </div>

        <div style={{display:'flex',flexDirection:'column',gap:'20px'}}>
          <div className="field" style={{margin:0}}>
            <label>Full Name</label>
            {editing === 'name' ? (
              <div style={{display:'flex',gap:'8px'}}>
                <input className="input" value={form.full_name} onChange={e=>setForm({...form,full_name:e.target.value})} placeholder={data.auth?.full_name||'Set your name'} />
                <button className="btn btn-primary btn-sm" onClick={()=>handleSave('update_name',{full_name:form.full_name})}><I n="check" /></button>
                <button className="btn btn-ghost btn-sm" onClick={()=>setEditing(null)}><I n="times" /></button>
              </div>
            ) : (
              <div style={{display:'flex',justifyContent:'space-between',alignItems:'center'}}>
                <span style={{fontSize:'15px',fontWeight:'600'}}>{data.auth?.full_name||'Not set'}</span>
                <button className="btn btn-ghost btn-sm" onClick={()=>{setEditing('name');setForm({...form,full_name:data.auth?.full_name||''})}}><I n="pencil" /></button>
              </div>
            )}
          </div>

          <div className="field" style={{margin:0}}>
            <label>Username</label>
            {editing === 'username' ? (
              <div style={{display:'flex',gap:'8px'}}>
                <input className="input" value={form.username} onChange={e=>setForm({...form,username:e.target.value})} placeholder={data.auth?.username} />
                <button className="btn btn-primary btn-sm" onClick={()=>handleSave('update_username',{username:form.username})}><I n="check" /></button>
                <button className="btn btn-ghost btn-sm" onClick={()=>setEditing(null)}><I n="times" /></button>
              </div>
            ) : (
              <div style={{display:'flex',justifyContent:'space-between',alignItems:'center'}}>
                <span style={{fontSize:'15px',fontWeight:'600'}}>{data.auth?.username||''}</span>
                <button className="btn btn-ghost btn-sm" onClick={()=>{setEditing('username');setForm({...form,username:data.auth?.username||''})}}><I n="pencil" /></button>
              </div>
            )}
          </div>

          <div className="field" style={{margin:0}}>
            <label>Password</label>
            {editing === 'password' ? (
              <>
                {msg && <div className={msg.type==='error'?'err':'success'}>{msg.text}</div>}
                <div style={{display:'flex',flexDirection:'column',gap:'8px'}}>
                  <input className="input" type="password" placeholder="Current password" value={form.current_password} onChange={e=>setForm({...form,current_password:e.target.value})} />
                  <input className="input" type="password" placeholder="New password" value={form.new_password} onChange={e=>setForm({...form,new_password:e.target.value})} />
                  <input className="input" type="password" placeholder="Confirm new password" value={form.confirm_password} onChange={e=>setForm({...form,confirm_password:e.target.value})} />
                  <div style={{display:'flex',gap:'8px'}}>
                    <button className="btn btn-primary btn-sm" onClick={()=>{setMsg(null);handleSave('update_password',{current_password:form.current_password,new_password:form.new_password,confirm_password:form.confirm_password})}}><I n="check" /> Update</button>
                    <button className="btn btn-ghost btn-sm" onClick={()=>{setEditing(null);setMsg(null)}}>Cancel</button>
                  </div>
                </div>
              </>
            ) : (
              <button className="btn btn-outline btn-sm" onClick={()=>{setEditing('password');setMsg(null)}}><I n="lock" /> Change Password</button>
            )}
          </div>
        </div>
      </div>

      <div className="card anim anim-d3" style={{padding:'24px',marginBottom:'24px'}}>
        <div className="caps-label" style={{fontSize:'14px',fontWeight:'700',marginBottom:'16px'}}>
          <I n="crosshairs" style={{marginRight:10,color:'var(--primary)'}} />Prediction Accuracy
        </div>
          <div className="stat-grid" style={{gap:'12px'}}>
            <div className="card stat-box" style={{background:'var(--bg2)',padding:'18px 12px'}}>
              <div className="v" style={{color:'var(--success)'}}>{data.accuracy}%</div>
              <div className="l">Overall</div>
            </div>
            <div className="card stat-box" style={{background:'var(--bg2)',padding:'18px 12px'}}>
              <div className="v" style={{color:'var(--accent-warm)'}}>{data.avgPositionError||'-'}</div>
              <div className="l">Avg Error</div>
            </div>
            <div className="card stat-box" style={{background:'var(--bg2)',padding:'18px 12px'}}>
              <div className="v" style={{color:'var(--primary)'}}>{data.exactMatches||0}</div>
              <div className="l">Exact</div>
            </div>
        </div>
      </div>

      <div className="card anim anim-d4" style={{padding:'24px'}}>
        <div className="caps-label" style={{fontSize:'14px',fontWeight:'700',marginBottom:'16px'}}>
          <I n="crown" style={{marginRight:10,color:'var(--primary)'}} />Best Performance
        </div>
        {data.bestRace ? (
          <div style={{background:'var(--bg2)',padding:'24px',borderRadius:'var(--rad)',textAlign:'center',marginBottom:'20px'}}>
            <div style={{fontSize:'42px',fontWeight:'900',color:'var(--success)',fontFamily:'JetBrains Mono,monospace'}}>+{data.bestRace.total_points}</div>
            <div style={{fontWeight:'700',fontSize:'15px',marginTop:'6px'}}><span className="racing">{data.bestRace.country}</span> GP</div>
            <div style={{fontSize:'12px',color:'var(--text2)',marginTop:'4px'}}>{new Date(data.bestRace.race_date).toLocaleDateString('en-US',{month:'long',day:'numeric',year:'numeric'})}</div>
          </div>
        ) : (
          <div style={{textAlign:'center',padding:'32px',color:'var(--text3)',fontSize:'13px',marginBottom:'20px'}}>No races yet</div>
        )}
        <div style={{fontSize:'12px',fontWeight:'600',color:'var(--text2)',textTransform:'uppercase',letterSpacing:'0.04em',marginBottom:'12px'}}>
          <I n="history" style={{marginRight:10}} />Race History
        </div>
        {data.recentRaces?.length > 0 ? data.recentRaces.map((rc,i) => (
          <div className="race-row" key={i} style={{padding:'12px 0'}}>
            <div style={{width:'32px',height:'32px',borderRadius:'8px',background:'var(--card2)',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
              <I n="flag-checkered" style={{fontSize:'12px',color:'var(--text3)'}} />
            </div>
            <div style={{flex:1}}>
              <div className="r-name" style={{fontSize:'14px'}}><span className="racing">{rc.country}</span> GP</div>
              <div className="r-meta">{new Date(rc.race_date).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'})}</div>
            </div>
            <div style={{fontWeight:'800',fontSize:'16px',color:'var(--success)',fontFamily:'JetBrains Mono,monospace'}}>+{rc.total_points}</div>
          </div>
        )) : (
          <div style={{textAlign:'center',padding:'24px',color:'var(--text3)',fontSize:'13px'}}>No race history yet. Start predicting!</div>
        )}
      </div>
    </div>
  );
};

const AchievementsPage = () => {
  const [profile, setProfile] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api('profile').then(d => { setProfile(d); setLoading(false); }).catch(() => setLoading(false));
  }, []);

  const unlocked = profile?.userAchievements || [];
  const unlockedIds = new Set(unlocked.map(a => a.id));
  const tiers = ['common','rare','epic','legendary','special'];
  const tierLabels = {common:'Common',rare:'Rare',epic:'Epic',legendary:'Legendary',special:'Special'};
  const unlockedCount = unlocked.length;
  const totalCount = ALL_ACHIEVEMENTS.length;
  const completionPct = totalCount > 0 ? Math.round((unlockedCount/totalCount)*100) : 0;

  if (loading) return <div className="page" style={{textAlign:'center',paddingTop:120,color:'var(--text2)'}}><I n="spinner" /></div>;

  return (
    <div className="page">
      <div className="anim" style={{marginBottom:'20px',textAlign:'center'}}>
        <h1 style={{fontSize:'24px',fontWeight:'800',color:'var(--text)'}}>Achievements</h1>
        <p style={{color:'var(--text2)',fontSize:'13px',marginTop:'4px'}}>Unlock every achievement to become the ultimate predictor</p>
      </div>

      <div className="stat-grid anim anim-d1" style={{maxWidth:500,margin:'0 auto 20px'}}>
        <div className="card stat-box">
          <div className="v" style={{color:'var(--success)'}}>{unlockedCount}</div>
          <div className="l">Unlocked</div>
        </div>
        <div className="card stat-box">
          <div className="v" style={{color:'var(--text)'}}>{totalCount}</div>
          <div className="l">Total</div>
        </div>
        <div className="card stat-box">
          <div className="v" style={{color:'var(--primary)'}}>{completionPct}%</div>
          <div className="l">Complete</div>
        </div>
        <div className="card stat-box">
          <div className="v" style={{color:'var(--accent-warm)'}}>{profile?.auth?.username?.charAt(0)?.toUpperCase()||''}</div>
          <div className="l">Player</div>
        </div>
      </div>

      {tiers.map(tier => {
        const t = TIER_CONFIG[tier];
        const items = ALL_ACHIEVEMENTS.filter(a => a.tier === tier);
        if (items.length === 0) return null;
        return (
          <div key={tier} style={{marginBottom:'20px'}}>
            <div style={{display:'flex',alignItems:'center',gap:'8px',marginBottom:'10px'}}>
              <div style={{width:'3px',height:'16px',borderRadius:'2px',background:t.color2}}></div>
              <span style={{fontSize:'12px',fontWeight:'700',textTransform:'uppercase',letterSpacing:'0.06em',color:t.color2}}>{tierLabels[tier]}</span>
              <span style={{fontSize:'11px',color:'var(--text3)',marginLeft:'auto'}}>{items.filter(a => unlockedIds.has(a.id)).length}/{items.length}</span>
            </div>
            <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fill,minmax(260px,1fr))',gap:'12px'}}>
              {items.map(ach => {
                const isUnlocked = unlockedIds.has(ach.id);
                return (
                  <div key={ach.id} className={'card card-hover'+(isUnlocked?'':'')}
                    style={{padding:'20px',borderLeft:'4px solid '+(isUnlocked?t.color2:'var(--border)'),background:isUnlocked?t.bg:'var(--card)',opacity:isUnlocked?1:0.7,filter:isUnlocked?'none':'grayscale(0.5)',position:'relative'}}>
                    <div style={{display:'flex',alignItems:'flex-start',justifyContent:'space-between',marginBottom:'14px'}}>
                      <div style={{width:56,height:56,borderRadius:14,background:t.bg,display:'flex',alignItems:'center',justifyContent:'center',fontSize:'24px',color:t.color2,flexShrink:0}}>
                        <I n={ach.icon.replace('fa-','')} />
                      </div>
                      {!isUnlocked ? (
                        <I n="lock" style={{fontSize:'16px',color:'var(--text3)'}} />
                      ) : (
                        <I n="check-circle" style={{fontSize:'16px',color:t.color2}} />
                      )}
                    </div>
                    <div style={{fontWeight:'700',fontSize:'15px',marginBottom:'6px',color:'var(--text)'}}>{ach.name}</div>
                    <div style={{fontSize:'12px',color:'var(--text2)',lineHeight:1.4,marginBottom:'14px'}}>{ach.desc}</div>
                    <div style={{paddingTop:'12px',borderTop:'1px solid var(--border)',display:'flex',alignItems:'center',justifyContent:'space-between'}}>
                      <span style={{fontSize:'10px',fontWeight:'600',textTransform:'uppercase',color:'var(--text3)',letterSpacing:'0.03em'}}>How to Unlock</span>
                      <span style={{fontSize:'9px',fontWeight:'700',textTransform:'uppercase',padding:'2px 10px',borderRadius:999,background:t.bg,color:t.color2,letterSpacing:'0.05em'}}>{tierLabels[tier]}</span>
                    </div>
                    <div style={{fontSize:'11px',color:'var(--text3)',marginTop:'6px',lineHeight:1.3}}>{ach.desc}</div>
                  </div>
                );
              })}
            </div>
          </div>
        );
      })}
    </div>
  );
};

const UpdatesPage = ({ onNav }) => {
  const [d, setD] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => { api('updates').then(r => { setD(r); setLoading(false); }).catch(() => setLoading(false)); }, []);

  if (loading) return <div className="page" style={{textAlign:'center',paddingTop:120,color:'var(--text2)'}}><I n="spinner" /></div>;
  if (!d) return null;

  return (
    <div className="page">
      <div className="anim" style={{marginBottom:'20px',textAlign:'center'}}>
        <h1 style={{fontSize:'24px',fontWeight:'800',color:'var(--text)'}}>Race <span style={{color:'var(--primary)'}}>Roundup</span></h1>
        <p style={{color:'var(--text2)',fontSize:'13px',marginTop:'4px'}}>Latest race results, standings, and prediction tracker</p>
      </div>

      {/* NEXT RACE — main focus, moved above last race */}
      {d.nextRace && (
        <div className="card anim anim-d1" style={{padding:'16px',marginBottom:'16px',border:'1px solid rgba(225,6,0,0.12)'}}>
          <div style={{display:'flex',alignItems:'center',gap:'12px',marginBottom:'14px'}}>
            <span style={{fontSize:'28px'}}>{d.nextRace.flag || '🏁'}</span>
            <div style={{flex:1}}>
              <div style={{fontSize:'16px',fontWeight:'700'}}><span className="racing">{d.nextRace.country}</span> GP <span className="badge badge-purple" style={{marginLeft:'8px',fontSize:'9px'}}>Up Next</span></div>
              <div style={{fontSize:'12px',color:'var(--text2)'}}>{new Date(d.nextRace.race_date).toLocaleDateString('en-US',{month:'long',day:'numeric',year:'numeric'})} &middot; {d.nextRace.circuit_name}</div>
            </div>
          </div>

          {/* Calendar download buttons */}
          <div style={{display:'flex',gap:'8px',marginBottom:'14px',flexWrap:'wrap'}}>
            <a href={'calendar.php?race_id='+d.nextRace.id} target="_blank" className="btn btn-sm" style={{background:'var(--bg2)',color:'var(--text)',border:'1px solid var(--border)'}}>
              <I n="calendar-plus" style={{fontSize:12}} /> Download .ics <span style={{fontSize:9,color:'var(--text3)'}}>(iCal / Android / Outlook)</span>
            </a>
            <a href={'calendar.php'} target="_blank" className="btn btn-sm" style={{background:'var(--bg2)',color:'var(--text)',border:'1px solid var(--border)'}}>
              <I n="calendar-alt" style={{fontSize:12}} /> Full Season Calendar
            </a>
          </div>

          {/* Progress bar */}
          <div style={{marginBottom:'12px'}}>
            <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:'6px'}}>
              <span style={{fontSize:'12px',fontWeight:'600',color:'var(--text2)'}}>Prediction Progress</span>
              <span style={{fontSize:'11px',fontWeight:'600',color:'var(--purple2)'}}>{d.submitted?.length||0}/{d.submitted?.length+d.missing?.length||0} players</span>
            </div>
            <div className="status-bar">
              <div className="status-fill purple" style={{width:Math.max(d.submissionPct||0,3)+'%'}}></div>
            </div>
          </div>

          {/* Full lists — no slice limit */}
          <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:'10px'}}>
            <div style={{background:'var(--bg2)',padding:'10px',borderRadius:'var(--rad)',maxHeight:320,overflow:'auto'}}>
              <div style={{fontSize:'10px',fontWeight:'700',color:'var(--success)',textTransform:'uppercase',letterSpacing:'0.04em',marginBottom:'6px',position:'sticky',top:0,background:'var(--bg2)',paddingBottom:4,zIndex:1}}>
                <I n="check-circle" style={{marginRight:4}} />Submitted ({d.submitted?.length||0})
              </div>
              {d.submitted?.length > 0 ? d.submitted.map((s,i) => (
                <div key={i} style={{display:'flex',alignItems:'center',gap:'6px',padding:'3px 0'}}>
                  <div style={{width:'20px',height:'20px',borderRadius:'50%',overflow:'hidden',background:'var(--card2)',flexShrink:0}}>
                    <img src={getAvatarUrl(s.avatar_style,s.username)} style={{width:'100%',height:'100%',objectFit:'cover'}} />
                  </div>
                  <span style={{fontSize:'11px',fontWeight:'600'}}>{s.username}</span>
                </div>
              )) : <div style={{fontSize:'11px',color:'var(--text3)',padding:'8px 0'}}>No predictions yet</div>}
            </div>
            <div style={{background:'var(--bg2)',padding:'10px',borderRadius:'var(--rad)',maxHeight:320,overflow:'auto'}}>
              <div style={{fontSize:'10px',fontWeight:'700',color:'var(--danger)',textTransform:'uppercase',letterSpacing:'0.04em',marginBottom:'6px',position:'sticky',top:0,background:'var(--bg2)',paddingBottom:4,zIndex:1}}>
                <I n="times-circle" style={{marginRight:4}} />Missing ({d.missing?.length||0})
              </div>
              {d.missing?.length > 0 ? d.missing.map((s,i) => (
                <div key={i} style={{display:'flex',alignItems:'center',gap:'6px',padding:'3px 0'}}>
                  <div style={{width:'20px',height:'20px',borderRadius:'50%',overflow:'hidden',background:'var(--card2)',flexShrink:0}}>
                    <img src={getAvatarUrl(s.avatar_style,s.username)} style={{width:'100%',height:'100%',objectFit:'cover'}} />
                  </div>
                  <span style={{fontSize:'11px',fontWeight:'600'}}>{s.username}</span>
                </div>
              )) : <div style={{fontSize:'11px',color:'var(--green)',padding:'8px 0'}}>Everyone has submitted!</div>}
            </div>
          </div>

          <div style={{marginTop:12,fontSize:10,color:'var(--text3)',textAlign:'center'}}>
            Deadline: {new Date(d.nextRace.race_date).toLocaleDateString('en-US',{weekday:'long',month:'long',day:'numeric'})} at midnight UTC
          </div>
        </div>
      )}

      {/* LAST RACE — moved below next race */}
      {d.lastRace && (
        <div className="card anim anim-d2" style={{padding:'16px',marginBottom:'16px'}}>
          <div style={{display:'flex',alignItems:'center',gap:'12px',marginBottom:'14px'}}>
            <span style={{fontSize:'24px'}}>{d.lastRace.flag}</span>
            <div>
              <div style={{fontSize:'16px',fontWeight:'700'}}><span className="racing">{d.lastRace.country}</span> GP <span className="badge badge-green" style={{marginLeft:'8px',fontSize:'9px'}}>Completed</span></div>


              <div style={{fontSize:'12px',color:'var(--text2)'}}>{new Date(d.lastRace.race_date).toLocaleDateString('en-US',{month:'long',day:'numeric',year:'numeric'})}</div>
            </div>
          </div>

          {d.raceWinner && (
            <div style={{display:'flex',alignItems:'center',gap:'12px',padding:'12px',background:'var(--bg2)',borderRadius:'var(--rad)',marginBottom:'12px'}}>
              <I n="trophy" style={{fontSize:'18px',color:'var(--orange)'}} />
              <div>
                <div style={{fontSize:'13px',fontWeight:'600'}}>{d.raceWinner.driver_name} wins!</div>
                <div style={{fontSize:'11px',color:'var(--text2)'}}>{d.raceWinner.constructor_name}</div>
              </div>
            </div>
          )}

          <div style={{fontSize:'12px',fontWeight:'600',color:'var(--text2)',textTransform:'uppercase',letterSpacing:'0.04em',marginBottom:'8px'}}>Top 5</div>
          {d.top5Results?.map((res,i) => {
            const colors = ['var(--orange)','#a0a0a0','#cd7f32'];
            return (
              <div className="driver-row" key={i} style={{padding:'7px 0'}}>
                <div style={{width:'24px',textAlign:'center',fontWeight:'700',fontSize:'13px',color:i < 3 ? colors[i] : 'var(--text3)'}}>P{res.position}</div>
                <TeamBadge team={res.constructor_name} />
                <div style={{fontWeight:'600',fontSize:'12px'}}>{res.driver_name}</div>
                <div style={{fontSize:'10px',color:'var(--text2)',marginLeft:'auto'}}>{res.constructor_name}</div>
              </div>
            );
          })}

          <div style={{fontSize:'12px',fontWeight:'600',color:'var(--text2)',textTransform:'uppercase',letterSpacing:'0.04em',margin:'14px 0 8px'}}>Top Scorers</div>
          {d.topScorers?.map((s,i) => (
            <div className="lb-row" key={i} style={{padding:'8px 4px'}}>
              <div style={{fontSize:'16px',width:'24px',textAlign:'center'}}>{i===0?'🥇':i===1?'🥈':i===2?'🥉':(i+1)}</div>
              <div style={{width:'28px',height:'28px',borderRadius:'50%',overflow:'hidden',background:'var(--bg2)',flexShrink:0}}>
                <img src={getAvatarUrl(s.avatar_style,s.username)} style={{width:'100%',height:'100%',objectFit:'cover'}} />
              </div>
              <div className="lb-name">
                <div className="lb-user">{s.username}</div>
              </div>
              <div style={{fontSize:'15px',fontWeight:'700',color:'var(--success)',fontFamily:'JetBrains Mono,monospace'}}>+{s.total_points}</div>
            </div>
          ))}

          {(d.podiumSweepUsers?.length > 0 || d.constructorBonusUsers?.length > 0) && (
            <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:'10px',marginTop:'12px'}}>
              {d.podiumSweepUsers?.length > 0 && (
                <div style={{background:'var(--bg2)',padding:'10px',borderRadius:'var(--rad)'}}>
                  <div style={{fontSize:'10px',color:'var(--accent-warm)',fontWeight:'700',textTransform:'uppercase',letterSpacing:'0.04em',marginBottom:'4px'}}><I n="crown" style={{marginRight:4}} />Podium Sweep</div>
                  <div style={{fontSize:'12px'}}>{d.podiumSweepUsers.join(', ')}</div>
                </div>
              )}
              {d.constructorBonusUsers?.length > 0 && (
                <div style={{background:'var(--card2)',padding:'10px',borderRadius:'var(--rad)'}}>
                  <div style={{fontSize:'10px',color:'var(--blue)',fontWeight:'700',textTransform:'uppercase',letterSpacing:'0.04em',marginBottom:'4px'}}><I n="wrench" style={{marginRight:4}} />Constructor Bonus</div>
                  <div style={{fontSize:'12px'}}>{d.constructorBonusUsers.join(', ')}</div>
                </div>
              )}
            </div>
          )}
        </div>
      )}

      <div style={{background:'var(--surface)',border:'1px solid var(--border)',padding:'16px'}}>
        <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:'10px'}}>
          <span className="caps-label" style={{fontSize:11}}><I n="trophy" /> Standings</span>
          <a className="btn btn-outline btn-sm" style={{fontSize:10}} onClick={(e)=>{e.preventDefault();onNav('leaderboard')}}>Full Standings</a>
        </div>

        {/* Column headers */}
        <div style={{display:'flex',alignItems:'center',gap:8,padding:'6px 4px 6px 26px',borderBottom:'1px solid var(--border)',marginBottom:4}}>
          <div style={{fontSize:9,fontWeight:700,color:'var(--text3)',textTransform:'uppercase',letterSpacing:'0.08em',width:28,flexShrink:0}}>Pos</div>
          <div style={{fontSize:9,fontWeight:700,color:'var(--text3)',textTransform:'uppercase',letterSpacing:'0.08em',flex:1}}>Player</div>
          <div style={{fontSize:9,fontWeight:700,color:'var(--text3)',textTransform:'uppercase',letterSpacing:'0.08em',textAlign:'right',width:50,flexShrink:0}}>Pts</div>
        </div>

        {d.leaderboard?.slice(0,10).map((p,i) => {
          const isMe = d.auth?.username === p.username;
          const medals = ['🥇','🥈','🥉'];
          return (
          <div key={i} style={{display:'flex',alignItems:'center',gap:8,padding:'7px 4px',borderBottom:i<Math.min(d.leaderboard.length,10)-1?'1px solid var(--border-light)':'none',background:isMe?'rgba(0,0,0,0.02)':'transparent'}}>
            <div style={{width:22,flexShrink:0,textAlign:'center',fontSize:12,lineHeight:1}}>
              {i < 3 ? <span style={{fontSize:14}}>{medals[i]}</span> : <span className="mono" style={{fontSize:10,color:'var(--text3)'}}>{i+1}</span>}
            </div>
            <div style={{width:26,height:26,borderRadius:'50%',overflow:'hidden',background:'var(--surface-muted)',flexShrink:0}}>
              <img src={getAvatarUrl(p.avatar_style,p.username)} style={{width:'100%',height:'100%',objectFit:'cover'}} />
            </div>
            <div style={{flex:1,minWidth:0}}>
              <div style={{fontSize:12,fontWeight:600,color:'var(--text)',lineHeight:1.3}}>
                {p.username}
                {isMe && <span style={{fontSize:9,color:'var(--text3)',fontWeight:400,marginLeft:4}}>(you)</span>}
              </div>
              {p.full_name && <div style={{fontSize:10,color:'var(--text3)',lineHeight:1.2,whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'}}>{p.full_name}</div>}
            </div>
            <div className="mono" style={{fontSize:14,fontWeight:700,color:'var(--text)',textAlign:'right',width:50,flexShrink:0}}>{p.total_points}</div>
          </div>
          );
        })}
        {d.leaderboard?.length > 10 && (
          <div style={{textAlign:'center',padding:'8px 0 0',fontSize:11,color:'var(--text3)'}}>
            <a style={{cursor:'pointer',color:'var(--text2)',textDecoration:'none',fontWeight:600}} onClick={(e)=>{e.preventDefault();onNav('leaderboard')}}>
              View all {d.leaderboard.length} players →
            </a>
          </div>
        )}
      </div>
    </div>
  );
};

const AdminPage = () => {
  const [races, setRaces] = useState([]);
  const [drivers, setDrivers] = useState([]);
  const [raceId, setRaceId] = useState('');
  const [pasteText, setPasteText] = useState('');
  const [matches, setMatches] = useState([]);
  const [detectedCount, setDetectedCount] = useState(0);
  const [submitting, setSubmitting] = useState(false);
  const [loading, setLoading] = useState(true);
  const [toast, setToast] = useState(null);

  useEffect(() => {
    api('admin').then(d => {
      if (d.error) return;
      setRaces(d.races || []);
      setDrivers(d.drivers || []);
      setLoading(false);
    }).catch(() => setLoading(false));
  }, []);

  useEffect(() => {
    if (drivers.length > 0) {
      setMatches(drivers.map((_, i) => ({ pos: i + 1, driverId: '', driverName: '' })));
    }
  }, [drivers]);

  const norm = (s) => (s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

  const autoParse = (text) => {
    setPasteText(text);
    const lines = text.split('\n').filter(l => l.trim());
    const newMatches = drivers.map((_, i) => ({ pos: i + 1, driverId: '', driverName: '' }));
    let count = 0;

    lines.forEach((line, idx) => {
      const posMatch = line.match(/^(\d+)/);
      const targetPos = posMatch ? parseInt(posMatch[1]) : (idx + 1);
      if (targetPos < 1 || targetPos > drivers.length) return;

      let clean = line.replace(/^\d+/, '').replace(/[+\-]?\d+[:.]\d+[:.]?\d*/g, '').trim().toLowerCase();
      clean = clean.normalize('NFD').replace(/[\u0300-\u036f]/g, '');

      let best = null;
      drivers.forEach(d => {
        const dn = norm(d.driver_name);
        const parts = dn.split(' ');
        const surname = parts[parts.length - 1];
        if (clean.includes(dn) || clean.includes(surname) || clean.includes(d.id.toLowerCase())) {
          best = d;
        }
      });

      if (best) {
        newMatches[targetPos - 1] = { pos: targetPos, driverId: best.id, driverName: best.driver_name };
        count++;
      }
    });

    setMatches(newMatches);
    setDetectedCount(count);
  };

  const clearParser = () => {
    setPasteText('');
    setMatches(drivers.map((_, i) => ({ pos: i + 1, driverId: '', driverName: '' })));
    setDetectedCount(0);
  };

  const manualSelect = (pos, driverId) => {
    const d = drivers.find(x => x.id === driverId);
    const newMatches = [...matches];
    newMatches[pos - 1] = { pos, driverId, driverName: d ? d.driver_name : '' };
    setMatches(newMatches);
    setDetectedCount(newMatches.filter(m => m.driverId).length);
  };

  const submitResults = async () => {
    if (!raceId) {
      setToast({ type: 'error', msg: '⚠️ Select a race first!' });
      return;
    }
    const results = {};
    matches.forEach(m => { if (m.driverId) results[m.pos] = m.driverId; });
    if (Object.keys(results).length === 0) {
      setToast({ type: 'error', msg: 'Nothing to deploy! Paste some data first.' });
      return;
    }
    setSubmitting(true);
    try {
      const r = await fetch('admin/process-results.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ race_id: raceId, results }),
        credentials: 'same-origin',
      });
      const data = await r.json();
      if (data.success) {
        setToast({ type: 'success', msg: 'Deployment successful! Scores updated.' });
        setTimeout(() => setToast(null), 4000);
      } else {
        setToast({ type: 'error', msg: data.message || 'Deploy failed' });
      }
    } catch (err) {
      setToast({ type: 'error', msg: 'Server error during deployment' });
    }
    setSubmitting(false);
  };

  if (loading) return <div className="page" style={{textAlign:'center',paddingTop:120,color:'var(--text2)'}}><I n="spinner" style={{animation:'spin 1s linear infinite'}} /></div>;

  return (
    <div className="page" style={{maxWidth:1200}}>
      {/* Toast */}
      {toast && <div className={'toast ' + toast.type} style={{zIndex:999}}><I n={toast.type==='error'?'exclamation-circle':'check-circle'} /> {toast.msg}</div>}

      {/* Loading overlay */}
      {submitting && (
        <div style={{position:'fixed',inset:0,background:'rgba(0,0,0,0.9)',backdropFilter:'blur(24px)',zIndex:100,display:'flex',flexDirection:'column',alignItems:'center',justifyContent:'center'}}>
          <div style={{width:80,height:80,border:'4px solid var(--accent-warm)',borderTopColor:'transparent',borderRadius:'50%',animation:'spin 1s linear infinite',marginBottom:24}} />
          <div style={{color:'var(--text)',fontWeight:'800',fontSize:'24px',animation:'pulse 1s ease-in-out infinite',textTransform:'uppercase',fontStyle:'italic',letterSpacing:'0.04em'}}>Processing Telemetry...</div>
        </div>
      )}

      {/* Header */}
      <div style={{marginBottom:32}}>
          <span style={{background:'var(--danger)',color:'#fff',fontSize:10,fontWeight:'900',padding:'4px 12px',borderRadius:999,textTransform:'uppercase',letterSpacing:'0.12em',display:'inline-block',marginBottom:12}}>
            <I n="terminal" style={{marginRight:10}} />Manual Override Mode
          </span>
          <h1 style={{fontSize:48,fontWeight:'900',color:'var(--text)',fontStyle:'italic',textTransform:'uppercase',lineHeight:1.1}}>Race <span style={{background:'linear-gradient(135deg,var(--primary),var(--accent-warm))',WebkitBackgroundClip:'text',WebkitTextFillColor:'transparent'}}>Commander</span></h1>
        <p style={{color:'var(--text2)',fontSize:14,marginTop:8}}>Update race results, calculate points, and move the season forward.</p>
      </div>

      {/* Main Card */}
      <div className="card" style={{padding:32,borderTop:'4px solid var(--accent)',borderRadius:24}}>

        {/* Step 1: Race Selector */}
        <div style={{marginBottom:32,padding:24,background:'rgba(0,210,190,0.04)',border:'1px solid rgba(0,210,190,0.12)',borderRadius:16,display:'flex',flexDirection:'row',alignItems:'center',gap:16,flexWrap:'wrap'}}>
          <div style={{display:'flex',alignItems:'center',gap:8,color:'var(--accent)',fontWeight:'900',textTransform:'uppercase',fontSize:12,letterSpacing:'0.08em',whiteSpace:'nowrap'}}>
            <I n="flag-checkered" /> Step 1: Select Race
          </div>
          <select value={raceId} onChange={e => setRaceId(e.target.value)}
            style={{flex:1,minWidth:200,padding:'14px 16px',borderRadius:12,background:'var(--card)',border:'1px solid var(--border)',color:'var(--text)',fontWeight:'700',fontSize:14,outline:'none',cursor:'pointer'}}>
            <option value="">-- SELECT THE RACE YOU ARE UPDATING --</option>
            {races.map(r => (
              <option key={r.id} value={r.id}>
                {r.status === 'completed' ? '✅ DONE' : '🔓 UPCOMING'} — RD {r.id} — {r.race_name} ({new Date(r.race_date).toLocaleDateString('en-US',{month:'short',day:'numeric'})})
              </option>
            ))}
          </select>
        </div>

        {/* Step 2: Parser */}
        <div style={{display:'flex',flexDirection:'row',alignItems:'center',justifyContent:'space-between',flexWrap:'wrap',gap:16,marginBottom:24}}>
          <div>
            <h2 style={{fontSize:28,fontWeight:'900',color:'var(--text)',fontStyle:'italic',textTransform:'uppercase',lineHeight:1}}>Step 2: <span style={{color:'var(--accent)'}}>Ultra-Parser v2.0</span></h2>
            <p style={{color:'var(--text3)',fontSize:12,marginTop:6}}>DUMP THE WHOLE TABLE FROM BBC / SKY / ESPN BELOW. AUTO-DETECTS DRIVERS.</p>
          </div>
          <div style={{display:'flex',gap:12,alignItems:'center'}}>
            <button className="btn btn-outline btn-sm" onClick={clearParser}><I n="trash-alt" /> Clear Deck</button>
            <div style={{padding:'10px 20px',background:'var(--bg2)',borderRadius:10,border:'1px solid rgba(0,210,190,0.15)'}}>
              <span style={{color:'var(--accent)',fontWeight:'900',fontSize:13}}>{detectedCount} / {drivers.length} DETECTED</span>
            </div>
          </div>
        </div>

        <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:24}}>
          {/* Input Zone */}
          <div>
            <div className="card" style={{position:'relative',padding:0,overflow:'hidden'}}>
              <div style={{position:'absolute',top:12,left:16,fontSize:9,fontWeight:'900',color:'rgba(0,210,190,0.4)',textTransform:'uppercase',letterSpacing:'0.2em',pointerEvents:'none',zIndex:1}}>Input Stream</div>
              <textarea value={pasteText} onChange={e => autoParse(e.target.value)}
                style={{width:'100%',height:500,padding:'40px 20px 20px',background:'var(--card)',border:'1px solid var(--border)',borderRadius:12,color:'var(--text)',fontSize:13,fontFamily:'monospace',lineHeight:1.7,outline:'none',resize:'none'}}
                placeholder={"PASTE THE WHOLE DAMN TABLE HERE...\n\nExample:\n1 George Russell Mercedes 1:30:11\n2 Kimi Antonelli Mercedes +2.2\n3 Charles Leclerc Ferrari +5.5\n..."} />
            </div>
          </div>

          {/* Detection Grid */}
          <div style={{maxHeight:500,overflowY:'auto',paddingRight:8}}>
            {matches.map((m, i) => (
              <div key={i}
                style={{display:'flex',alignItems:'center',gap:12,padding:'10px 14px',marginBottom:6,borderRadius:12,border:'1px solid ' + (m.driverId ? 'rgba(0,210,190,0.3)' : 'var(--border)'),background: m.driverId ? 'rgba(0,210,190,0.04)' : 'transparent',opacity: m.driverId ? 1 : 0.4,transition:'all 0.2s'}}>
                <div style={{width:36,height:36,borderRadius:8,background:'var(--bg2)',display:'flex',alignItems:'center',justifyContent:'center',fontWeight:'900',fontSize:16,color:'var(--text2)',fontStyle:'italic',flexShrink:0}}>#{m.pos}</div>
                <select value={m.driverId} onChange={e => manualSelect(m.pos, e.target.value)}
                  style={{flex:1,padding:'10px 12px',borderRadius:10,background:'var(--card)',border:'1px solid var(--border)',color:'var(--text)',fontWeight:'600',fontSize:13,outline:'none',cursor:'pointer'}}>
                  <option value="">-- NO DRIVER DETECTED --</option>
                  {drivers.map(d => (
                    <option key={d.id} value={d.id}>{d.driver_name}</option>
                  ))}
                </select>
                <div style={{fontSize:9,fontWeight:'900',textTransform:'uppercase',letterSpacing:'0.08em',color: m.driverId ? 'var(--accent)' : 'var(--text3)',whiteSpace:'nowrap'}}>{m.driverId ? 'Detected' : 'Standby'}</div>
              </div>
            ))}
          </div>
        </div>

        {/* Launch Button */}
        <div style={{marginTop:32,padding:24,background:'rgba(0,210,190,0.03)',border:'1px solid rgba(0,210,190,0.08)',borderRadius:16,display:'flex',flexDirection:'row',alignItems:'center',gap:24,flexWrap:'wrap'}}>
          <div style={{flex:1}}>
            <h3 style={{fontSize:18,fontWeight:'900',color:'var(--text)',textTransform:'uppercase',fontStyle:'italic'}}>Ready for Launch?</h3>
            <p style={{fontSize:13,color:'var(--text3)'}}>Double check the grid above. The system will calculate scores for all engineers once you hit the button.</p>
          </div>
          <button onClick={submitResults} disabled={submitting}
            style={{whiteSpace:'nowrap',padding:'18px 48px',background:'linear-gradient(135deg,var(--accent),var(--primary))',border:'none',borderRadius:14,color:'#fff',fontWeight:'900',fontSize:18,cursor:'pointer',boxShadow:'0 8px 32px rgba(225,6,0,0.2)',transition:'all 0.2s',display:'flex',alignItems:'center',gap:12}}>
            <I n="rocket" /> DEPLOY CLASSIFICATION
          </button>
        </div>
      </div>

      {/* ===== RACE CONTROL — Post-Race Briefing & Tools ===== */}
      <div className="card" style={{padding:32,borderTop:'4px solid var(--accent-warm)',borderRadius:24,marginTop:24}}>
        <div style={{marginBottom:24}}>
          <span style={{background:'var(--accent-warm)',color:'#fff',fontSize:10,fontWeight:'900',padding:'4px 12px',borderRadius:999,textTransform:'uppercase',letterSpacing:'0.12em',display:'inline-block',marginBottom:12}}>
            <I n="cog" style={{marginRight:10}} />Race Control
          </span>
          <h2 style={{fontSize:32,fontWeight:'900',color:'var(--text)',fontStyle:'italic',textTransform:'uppercase',lineHeight:1.1}}>Briefing &amp; <span style={{color:'var(--accent-warm)'}}>Ops</span></h2>
          <p style={{color:'var(--text2)',fontSize:14,marginTop:8}}>Generate post-race debriefs, rescore races, create news posts, and manage the season.</p>
        </div>

        <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:20}}>
          {/* Selected Race Details */}
          <div style={{padding:20,background:'rgba(255,135,0,0.04)',border:'1px solid rgba(255,135,0,0.1)',borderRadius:16}}>
            <div style={{fontSize:11,fontWeight:'700',color:'var(--accent-warm)',textTransform:'uppercase',letterSpacing:'0.06em',marginBottom:12}}>
              <I n="flag-checkered" style={{marginRight:10}} />Selected Race
            </div>
            {raceId ? (() => {
              const r = races.find(x => x.id == raceId);
              if (!r) return <div style={{fontSize:13,color:'var(--text3)'}}>Race not found</div>;
              const isComplete = r.status === 'completed';
              return (
                <div>
                  <div style={{fontSize:20,fontWeight:'800',marginBottom:4}}><span className="racing">{r.country || r.race_name}</span> GP</div>
                  <div style={{fontSize:13,color:'var(--text2)',marginBottom:8}}>
                    {new Date(r.race_date).toLocaleDateString('en-US',{month:'long',day:'numeric',year:'numeric'})}
                    {' '}
                    <span className={'badge ' + (isComplete ? 'badge-green' : 'badge-purple')} style={{fontSize:9}}>
                      {isComplete ? 'Completed' : 'Upcoming'}
                    </span>
                  </div>
                  <div style={{display:'flex',gap:8,flexWrap:'wrap',marginTop:12}}>
                    <a href={'admin/generate-debriefs.php?race_id='+raceId} target="_blank" className="btn btn-sm" style={{background:'var(--accent-warm)',color:'#fff',border:'none'}}>
                      <I n="newspaper" style={{fontSize:11}} /> Generate Debrief
                    </a>
                    {isComplete && (
                      <a href={'admin/rescore-race.php?race_id='+raceId} target="_blank" className="btn btn-sm" style={{background:'var(--card)',color:'var(--text)',border:'1px solid var(--border)'}}>
                        <I n="redo-alt" style={{fontSize:11}} /> Rescore
                      </a>
                    )}
                    <a href={'admin/create-post.php?race_id='+raceId} target="_blank" className="btn btn-sm" style={{background:'var(--card)',color:'var(--text)',border:'1px solid var(--border)'}}>
                      <I n="pen" style={{fontSize:11}} /> Create Post
                    </a>
                  </div>
                </div>
              );
            })() : (
              <div style={{fontSize:13,color:'var(--text3)'}}>Select a race above first to access post-race tools.</div>
            )}
          </div>

          {/* Quick Links */}
          <div style={{padding:20,background:'var(--bg2)',border:'1px solid var(--border)',borderRadius:16}}>
            <div className="caps-label" style={{marginBottom:12}}>
              <I n="link" style={{marginRight:10}} />Admin Tools
            </div>
            <div style={{display:'flex',flexDirection:'column',gap:8}}>
              {[
                {url:'admin/generate-debriefs.php',label:'Debrief Manager',icon:'newspaper',desc:'Preview & publish post-race debriefs'},
                {url:'admin/create-post.php',label:'Create Post',icon:'pen',desc:'Write a manual news update'},
                {url:'admin/rescore-race.php',label:'Rescore Race',icon:'redo-alt',desc:'Recalculate scores for a race'},
                {url:'admin/fetch-race-results.php',label:'Fetch from F1 API',icon:'cloud-download-alt',desc:'Auto-fetch race results'},
                {url:'admin/backup-database.php',label:'Backup Database',icon:'database',desc:'Download SQL export'},
                {url:'admin/diagnose.php',label:'Diagnostics',icon:'stethoscope',desc:'Fix duplicate entries'},
              ].map((t,i) => (
                <a key={i} href={t.url} target="_blank" style={{display:'flex',alignItems:'center',gap:10,padding:'10px 12px',borderRadius:10,background:'var(--card)',border:'1px solid var(--border)',textDecoration:'none',transition:'all 0.15s'}}>
                  <div style={{width:28,height:28,borderRadius:8,background:'rgba(255,135,0,0.08)',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
                    <I n={t.icon} style={{fontSize:12,color:'var(--accent-warm)'}} />
                  </div>
                  <div style={{flex:1}}>
                    <div style={{fontSize:12,fontWeight:'700',color:'var(--text)'}}>{t.label}</div>
                    <div style={{fontSize:10,color:'var(--text3)'}}>{t.desc}</div>
                  </div>
                  <I n="external-link-alt" style={{fontSize:10,color:'var(--text3)'}} />
                </a>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

let __csrf = '';

const NewsPage = ({ onNav }) => {
  const [posts, setPosts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [openComments, setOpenComments] = useState({});
  const [comments, setComments] = useState({});
  const [commentText, setCommentText] = useState({});

  const load = () => {
    api('news').then(d => {
      if (d.posts) setPosts(d.posts);
      setLoading(false);
    }).catch(() => setLoading(false));
  };

  useEffect(() => { load(); }, []);

  const toggleLike = async (postId) => {
    const r = await fetch('api/social.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'toggle_like', post_id: postId, csrf_token: __csrf }),
      credentials: 'same-origin',
    });
    const data = await r.json();
    if (data.success) {
      setPosts(prev => prev.map(p => p.id === postId ? { ...p, like_count: data.like_count, user_liked: data.liked ? 1 : 0 } : p));
    }
  };

  const loadComments = async (postId) => {
    const r = await fetch('api/social.php?action=get_comments&post_id=' + postId, { credentials: 'same-origin' });
    const data = await r.json();
    if (data.success) setComments(prev => ({ ...prev, [postId]: data.comments }));
  };

  const addComment = async (postId) => {
    const text = commentText[postId] || '';
    if (!text.trim()) return;
    const r = await fetch('api/social.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'add_comment', post_id: postId, comment: text, csrf_token: __csrf }),
      credentials: 'same-origin',
    });
    const data = await r.json();
    if (data.success) {
      setCommentText(prev => ({ ...prev, [postId]: '' }));
      setPosts(prev => prev.map(p => p.id === postId ? { ...p, comment_count: data.comment_count } : p));
      loadComments(postId);
    }
  };

  const toggleComments = (postId) => {
    const isOpen = openComments[postId];
    if (!isOpen) loadComments(postId);
    setOpenComments(prev => ({ ...prev, [postId]: !isOpen }));
  };

  const formatDate = (d) => new Date(d).toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric', hour:'2-digit', minute:'2-digit' });

  if (loading) return <div className="page" style={{textAlign:'center',paddingTop:120,color:'var(--text2)'}}><I n="spinner" style={{animation:'spin 1s linear infinite'}} /></div>;

  return (
    <div className="page" style={{maxWidth:700}}>
      <div className="anim" style={{marginBottom:20,textAlign:'center'}}>
        <h1 style={{fontSize:24,fontWeight:'800',color:'var(--text)'}}><I n="rss" style={{color:'var(--accent-warm)',marginRight:8}} />Paddock News</h1>
        <p style={{color:'var(--text2)',fontSize:13,marginTop:4}}>Post-race debriefs, highlights, and community chatter</p>
      </div>

      {posts.length === 0 ? (
        <div className="card" style={{textAlign:'center',padding:'40px 20px'}}>
          <div style={{fontSize:48,marginBottom:12}}>📰</div>
          <h2 style={{fontSize:18,fontWeight:'700',color:'var(--text2)',marginBottom:8}}>No News Yet</h2>
          <p style={{color:'var(--text3)',fontSize:13}}>Check back after races are completed for post-race debrief and highlights!</p>
        </div>
      ) : (
        <div style={{display:'flex',flexDirection:'column',gap:16}}>
          {posts.map(post => (
            <div key={post.id} className="card" style={{padding:20,borderLeft:'3px solid var(--accent-warm)'}}>
              <div style={{display:'flex',justifyContent:'space-between',alignItems:'flex-start',marginBottom:10}}>
                <div>
                  <h2 style={{fontSize:18,fontWeight:'800',color:'var(--accent-warm)',marginBottom:4}}>{post.title}</h2>
                  {post.race_name && (
                    <div style={{fontSize:12,color:'var(--text3)'}}>
                      <I n="flag-checkered" style={{marginRight:4}} />{post.race_name} - <span className="racing">{post.country}</span>
                    </div>
                  )}
                </div>
                <div style={{fontSize:11,color:'var(--text3)',whiteSpace:'nowrap'}}>{formatDate(post.created_at)}</div>
              </div>
              <div style={{fontSize:11,color:'var(--text3)',marginBottom:12}}>
                By <strong style={{color:'var(--text2)'}}>{post.username || 'System'}</strong>
              </div>

              {/* Content */}
              <div style={{fontSize:13,color:'var(--text)',lineHeight:1.7,marginBottom:16}}
                dangerouslySetInnerHTML={{ __html: post.content }} />

              {/* Actions */}
              <div style={{display:'flex',alignItems:'center',gap:16,paddingTop:12,borderTop:'1px solid var(--border)'}}>
                <a onClick={() => toggleLike(post.id)} style={{display:'flex',alignItems:'center',gap:4,cursor:'pointer',color: post.user_liked ? 'var(--red)' : 'var(--text3)',fontSize:12,transition:'color 0.2s'}}>
                  <I n="heart" /> <span>{post.like_count}</span>
                </a>
                <a onClick={() => toggleComments(post.id)} style={{display:'flex',alignItems:'center',gap:4,cursor:'pointer',color:'var(--text3)',fontSize:12,transition:'color 0.2s'}}>
                  <I n="comment" /> <span>{post.comment_count}</span>
                </a>
              </div>

              {/* Comments */}
              {openComments[post.id] && (
                <div style={{marginTop:12,paddingTop:12,borderTop:'1px solid var(--border)'}}>
                  <div style={{display:'flex',flexDirection:'column',gap:8,marginBottom:12}}>
                    {(comments[post.id] || []).map((c, i) => (
                      <div key={i} style={{background:'var(--card2)',padding:'8px 12px',borderRadius:8}}>
                        <div style={{display:'flex',alignItems:'center',gap:6,marginBottom:2}}>
                          <strong style={{fontSize:11,color:'var(--accent-warm)'}}>{c.username}</strong>
                          <span style={{fontSize:10,color:'var(--text3)'}}>{c.created_at}</span>
                        </div>
                        <p style={{fontSize:12,color:'var(--text)'}}>{c.comment}</p>
                      </div>
                    ))}
                  </div>
                  <div style={{display:'flex',gap:8}}>
                    <input className="input" style={{flex:1}} placeholder="Write a comment..." value={commentText[post.id] || ''}
                      onChange={e => setCommentText(prev => ({ ...prev, [post.id]: e.target.value }))}
                      onKeyDown={e => e.key === 'Enter' && addComment(post.id)} />
                    <button className="btn btn-primary btn-sm" onClick={() => addComment(post.id)}>Post</button>
                  </div>
                </div>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

const App = () => {
  const [page, setPage] = useState('dashboard');
  const [user, setUser] = useState(null);
  const [showLogin, setShowLogin] = useState(false);
  const [loading, setLoading] = useState(true);

  const checkAuth = () => {
    api('user').then(d => {
      if (d.auth) { setUser(d.auth); __csrf = d.csrf_token || ''; } else { setUser(null); }
      setLoading(false);
    }).catch(() => setLoading(false));
  };

  useEffect(() => {
    checkAuth();
    const h = window.location.hash.replace('#','');
    if (h) setPage(h);
    window.addEventListener('hashchange', () => {
      const h2 = window.location.hash.replace('#','');
      if (h2) setPage(h2);
    });
  }, []);

  const handleNav = (p) => { setPage(p); window.location.hash = p; };
  const handleAuth = () => { setShowLogin(false); checkAuth(); };

  const getPageName = () => page.split('?')[0];

  if (loading) return null;

  if (false && !user && getPageName() !== 'leaderboard') {
    return (
      <>
        <style>{`
          @keyframes wlcmFade{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
          @keyframes wlcmSlide{from{opacity:0;transform:translateX(30px)}to{opacity:1;transform:translateX(0)}}
        `}</style>
        <div style={{display:'flex',minHeight:'100vh',background:'var(--bg)'}}>
          {/* LEFT — Hero Image Panel */}
          <div style={{
            flex:'1.3',position:'relative',overflow:'hidden',display:'flex',
            alignItems:'center',justifyContent:'center',
            background:'linear-gradient(135deg,#F8F9FA 0%,#F0F2F5 100%)',
            minHeight:'100vh'
          }}>
            {/* Abstract gradient mesh */}
            <div style={{
              position:'absolute',inset:0,
              background:'radial-gradient(ellipse at 20% 50%,rgba(225,6,0,0.04),transparent 60%),radial-gradient(ellipse at 80% 30%,rgba(0,210,190,0.03),transparent 50%),radial-gradient(ellipse at 50% 80%,rgba(255,135,0,0.02),transparent 50%)',
            }} />
            {/* Subtle grid pattern */}
            <div style={{
              position:'absolute',inset:0,
              backgroundImage:'linear-gradient(rgba(0,0,0,0.03) 1px,transparent 1px),linear-gradient(90deg,rgba(0,0,0,0.03) 1px,transparent 1px)',
              backgroundSize:'60px 60px'
            }} />
            {/* Brand content */}
            <div style={{position:'relative',zIndex:2,textAlign:'center',padding:'40px',maxWidth:480}}>
              <div style={{width:72,height:72,background:'linear-gradient(135deg,var(--primary),var(--accent))',borderRadius:20,display:'flex',alignItems:'center',justifyContent:'center',margin:'0 auto 28px',fontSize:32,color:'#fff',boxShadow:'0 8px 32px rgba(225,6,0,0.15)',animation:'wlcmFade 0.6s ease both'}}>
                <I n="flag-checkered" />
              </div>
              <h1 style={{fontSize:42,fontWeight:'900',color:'var(--text)',textTransform:'uppercase',letterSpacing:'-0.03em',lineHeight:1.05,marginBottom:12,animation:'wlcmFade 0.6s ease 0.1s both'}}>
                Paddock<br /><span style={{background:'linear-gradient(135deg,var(--primary),var(--accent))',WebkitBackgroundClip:'text',WebkitTextFillColor:'transparent'}}>Picks</span>
              </h1>
              <p style={{color:'var(--text2)',fontSize:15,lineHeight:1.7,marginBottom:32,animation:'wlcmFade 0.6s ease 0.2s both'}}>
                The ultimate F1 prediction league. Pick your drivers,<br />beat your rivals, own the podium.
              </p>
              <div style={{display:'grid',gridTemplateColumns:'repeat(3,1fr)',gap:12,animation:'wlcmFade 0.6s ease 0.25s both'}}>
                {[{n:'24',l:'Races'},{n:'22',l:'Drivers'},{n:'11',l:'Teams'}].map((s,i) => (
                  <div key={i} style={{background:'var(--card)',border:'1px solid var(--border)',borderRadius:12,padding:'14px 8px',textAlign:'center'}}>
                    <div style={{fontSize:24,fontWeight:'800',color:'var(--primary)',lineHeight:1}}>{s.n}</div>
                    <div style={{fontSize:10,color:'var(--text3)',textTransform:'uppercase',letterSpacing:'0.06em',fontWeight:'600',marginTop:4}}>{s.l}</div>
                  </div>
                ))}
              </div>
              <div style={{marginTop:24,display:'flex',alignItems:'center',justifyContent:'center',gap:8,animation:'wlcmFade 0.6s ease 0.3s both'}}>
                <span className="pulse-dot" style={{boxShadow:'0 0 8px rgba(0,210,190,0.4)'}} />
                <span style={{fontSize:12,color:'var(--text3)'}}>Season 2026 active</span>
              </div>
            </div>
          </div>

          {/* RIGHT — Login Card */}
          <div style={{
            flex:'1',display:'flex',alignItems:'center',justifyContent:'center',
            padding:'40px 32px',minHeight:'100vh',background:'var(--bg)'
          }}>
            <div style={{width:'100%',maxWidth:420,animation:'wlcmSlide 0.7s cubic-bezier(0.16,1,0.3,1) both'}}>
              {/* Small logo for mobile / right panel */}
              <div style={{display:'flex',alignItems:'center',gap:10,marginBottom:32}}>
                <div style={{width:36,height:36,background:'linear-gradient(135deg,var(--primary),var(--accent))',borderRadius:10,display:'flex',alignItems:'center',justifyContent:'center',fontSize:16,color:'#fff'}}>
                  <I n="flag-checkered" />
                </div>
                <span style={{fontWeight:'800',fontSize:16,color:'var(--text)',letterSpacing:'-0.02em'}}>PADDOCK PICKS</span>
              </div>

              <h2 style={{fontSize:28,fontWeight:'800',color:'var(--text)',marginBottom:6}}>
                Welcome back
              </h2>
              <p style={{color:'var(--text2)',fontSize:14,marginBottom:32}}>
                Log in to manage your predictions or create a new account.
              </p>

              {/* Login Form */}
              <form onSubmit={async (e) => {
                e.preventDefault();
                const fd = new FormData(e.target);
                const r = await postAuth(Object.fromEntries(fd));
                if (r.ok) { handleAuth(); return; }
                alert(r.error || 'Login failed');
              }}>
                <div className="field">
                  <label>Username</label>
                  <div className="iw">
                    <I n="user" />
                    <input className="input" name="username" placeholder="Enter your username" required />
                  </div>
                </div>
                <div className="field">
                  <label>Password</label>
                  <div className="iw">
                    <I n="lock" />
                    <input className="input" type="password" name="password" placeholder="Enter your password" required />
                  </div>
                </div>
                <input type="hidden" name="action" value="login" />
                <button className="btn btn-primary btn-lg btn-block" style={{marginTop:6,background:'linear-gradient(135deg,var(--primary),var(--primary-hover))',padding:'14px 24px',borderRadius:10,fontSize:15}}>
                  Log In <I n="arrow-right" />
                </button>
              </form>

              <div style={{margin:'24px 0',display:'flex',alignItems:'center',gap:16}}>
                <div style={{flex:1,height:1,background:'var(--border)'}} />
                <span style={{fontSize:11,color:'var(--text3)',fontWeight:'600',textTransform:'uppercase',letterSpacing:'0.06em'}}>or</span>
                <div style={{flex:1,height:1,background:'var(--border)'}} />
              </div>

              <button className="btn btn-outline btn-lg btn-block" style={{background:'var(--bg2)',borderColor:'var(--border)',padding:'14px 24px',borderRadius:10,fontSize:14}} onClick={() => setShowLogin('signup')}>
                <I n="user-plus" /> Create Account
              </button>

              <div style={{marginTop:32,textAlign:'center',fontSize:11,color:'var(--text3)'}}>
                <a onClick={() => {window.location.hash='leaderboard';setPage('leaderboard')}} style={{color:'var(--text2)',cursor:'pointer',textDecoration:'none'}}>
                  <I n="trophy" style={{marginRight:4}} /> Browse Standings
                </a>
                &nbsp;&middot;&nbsp;
                <a href="calendar.php" target="_blank" style={{color:'var(--text2)',textDecoration:'none'}}>
                  <I n="calendar-alt" style={{marginRight:4}} /> Calendar
                </a>
              </div>
            </div>
          </div>
        </div>
        {showLogin && <LoginModal onClose={() => setShowLogin(false)} onAuth={handleAuth} defaultTab={typeof showLogin === 'string' ? showLogin : undefined} />}
      </>
    );
  }

  const pn = getPageName();

  return (
    <>
      {(pn === 'dashboard' || pn === '') && <Dashboard onNav={handleNav} />}
      {pn === 'leaderboard' && <LeaderboardPage />}
      {pn === 'predict' && <PredictPage onNav={handleNav} />}
      {pn === 'results' && <ResultsPage onNav={handleNav} />}
      {pn === 'profile' && <ProfilePage user={user} onNav={handleNav} />}
      {pn === 'achievements' && <AchievementsPage />}
      {pn === 'updates' && <UpdatesPage onNav={handleNav} />}
      {pn === 'news' && <NewsPage onNav={handleNav} />}
      {pn === 'admin' && <AdminPage />}
      {showLogin && <LoginModal onClose={() => setShowLogin(false)} onAuth={handleAuth} defaultTab={typeof showLogin === 'string' ? showLogin : undefined} />}
      {/* Footer */}
      <footer style={{padding:'24px 24px',borderTop:'1px solid var(--border)',marginTop:40,textAlign:'center'}}>
        <p style={{fontSize:11,color:'var(--text3)',fontWeight:'600',letterSpacing:'0.04em'}}>
          &copy; {new Date().getFullYear()} Paddock Picks &middot; Powered by <a href="https://www.scanerrific.com" target="_blank" rel="noopener noreferrer" style={{color:'var(--accent-warm)',fontWeight:'700',textDecoration:'none'}}>Scanerrific</a>
          &middot; <a href="calendar.php" target="_blank" style={{color:'var(--text2)',textDecoration:'none'}}><I n="calendar-alt" style={{marginRight:4}} />Subscribe to Calendar</a>
        </p>
      </footer>
    </>
  );
};

ReactDOM.createRoot(document.getElementById('root')).render(React.createElement(App));
</script>
</body>
</html>
