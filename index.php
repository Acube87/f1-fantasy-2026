<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Paddock Picks</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
<script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
<script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
  --bg:#0c0f16;--bg2:#111620;--card:#181e2c;--card2:#1e2537;--border:rgba(255,255,255,0.05);
  --border2:rgba(255,255,255,0.08);--text:#f0f2f5;--text2:#8a92a8;--text3:#555b6e;
  --purple:#7c3aed;--purple2:#a855f7;--blue:#4f7cff;--green:#22c55e;--red:#ef4444;
  --orange:#fb923c;--rad:12px;--rad-lg:16px;
}
body{background:var(--bg);color:var(--text);font-family:'Inter',-apple-system,sans-serif;min-height:100vh;-webkit-font-smoothing:antialiased}
::-webkit-scrollbar{width:4px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--card2);border-radius:2px}
a{text-decoration:none;color:inherit}
input:focus{outline:none}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:8px 18px;font-family:'Inter',sans-serif;font-size:13px;font-weight:600;border:none;border-radius:8px;cursor:pointer;transition:all 0.2s}
.btn-primary{background:var(--purple);color:#fff}
.btn-primary:hover{background:#6d28d9;transform:translateY(-1px)}
.btn-outline{background:transparent;border:1px solid var(--border2);color:var(--text2)}
.btn-outline:hover{border-color:var(--purple);color:var(--text)}
.btn-ghost{background:transparent;color:var(--text2);padding:6px}
.btn-ghost:hover{color:var(--text)}
.btn-sm{padding:5px 12px;font-size:11px}
.btn-lg{padding:12px 28px;font-size:15px}
.btn-block{width:100%}
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--rad-lg);box-shadow:0 4px 24px rgba(0,0,0,0.3)}
.badge{display:inline-flex;align-items:center;gap:4px;padding:2px 10px;font-size:11px;font-weight:600;border-radius:999px}
.badge-purple{background:rgba(124,58,237,0.15);color:var(--purple2);border:1px solid rgba(124,58,237,0.2)}
.badge-green{background:rgba(34,197,94,0.12);color:var(--green);border:1px solid rgba(34,197,94,0.15)}
.badge-red{background:rgba(239,68,68,0.12);color:var(--red);border:1px solid rgba(239,68,68,0.15)}
.badge-gray{background:rgba(255,255,255,0.04);color:var(--text2);border:1px solid var(--border)}
.input{width:100%;background:rgba(0,0,0,0.3);border:1px solid var(--border2);border-radius:8px;padding:10px 14px;color:var(--text);font-family:'Inter',sans-serif;font-size:14px;outline:none}
.input:focus{border-color:var(--purple);box-shadow:0 0 0 2px rgba(124,58,237,0.08)}
.input::placeholder{color:var(--text3)}
.nav{position:fixed;top:0;left:0;right:0;z-index:100;height:56px;background:rgba(12,15,22,0.92);backdrop-filter:blur(16px);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 20px}
.nav-inner{display:flex;align-items:center;justify-content:space-between;width:100%;max-width:1400px;margin:0 auto}
.nav-brand{display:flex;align-items:center;gap:10px;font-weight:800;font-size:15px;letter-spacing:-0.02em;cursor:pointer}
.nav-brand-icon{width:32px;height:32px;background:var(--purple);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;color:#fff}
.nav-links{display:flex;align-items:center;gap:2px}
.nav-link{width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--text3);font-size:15px;cursor:pointer;transition:all 0.2s}
.nav-link:hover{color:var(--text);background:rgba(255,255,255,0.04)}
.nav-link.active{color:var(--purple2);background:rgba(124,58,237,0.1)}
.page{padding-top:72px;padding-bottom:24px;max-width:1400px;margin:0 auto;padding-left:20px;padding-right:20px}
.hero{position:relative;border-radius:var(--rad-lg);overflow:hidden;min-height:280px;display:flex;flex-direction:column;justify-content:flex-end}
.hero-bg{position:absolute;inset:0;background-size:cover;background-position:center;transition:transform 0.6s ease}
.hero-overlay{position:absolute;inset:0;background:linear-gradient(180deg,rgba(12,15,22,0.2) 0%,rgba(12,15,22,0.85) 60%,var(--bg) 100%)}
.hero-content{position:relative;z-index:2;padding:24px}
.cd-ring{position:relative;width:64px;height:64px;flex-shrink:0}
.cd-ring svg{width:100%;height:100%;transform:rotate(-90deg)}
.cd-ring circle{fill:none;stroke-width:3.5}
.cd-ring .bg{stroke:var(--card2)}
.cd-ring .fg{stroke:var(--purple);stroke-linecap:round;transition:stroke-dashoffset 1s ease}
.cd-text{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:var(--text)}
.status-bar{height:4px;background:var(--card2);border-radius:2px;overflow:hidden;margin-top:12px}
.status-fill{height:100%;border-radius:2px;transition:width 1s ease}
.status-fill.open{background:linear-gradient(90deg,var(--green),#16a34a)}
.status-fill.closed{background:var(--text3)}
.status-fill.green{background:var(--green)}
.status-fill.purple{background:var(--purple)}
.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
.stat-box{padding:16px;text-align:center;border-radius:var(--rad)}
.stat-box .v{font-size:24px;font-weight:800;line-height:1.2}
.stat-box .l{font-size:10px;color:var(--text2);text-transform:uppercase;letter-spacing:0.05em;font-weight:600;margin-top:2px}
.race-row{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--border)}
.race-row:last-child{border-bottom:none}
.r-flag{font-size:18px;width:32px;text-align:center;flex-shrink:0}
.r-name{font-weight:600;font-size:14px;color:var(--text)}
.r-meta{font-size:11px;color:var(--text2)}
.r-status{margin-left:auto}
.res-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)}
.res-row:last-child{border-bottom:none}
.res-icon{width:32px;height:32px;border-radius:8px;background:var(--card2);display:flex;align-items:center;justify-content:center;color:var(--text2);flex-shrink:0}
.res-name{font-weight:600;font-size:13px;color:var(--text)}
.res-date{font-size:11px;color:var(--text2)}
.res-pts{font-weight:700;font-size:16px;color:var(--green);margin-left:auto}
.lb-row{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;transition:background 0.2s}
.lb-row:hover{background:rgba(255,255,255,0.02)}
.lb-row.me{background:rgba(124,58,237,0.06)}
.lb-rk{width:24px;text-align:center;font-weight:700;font-size:15px}
.lb-rk-1{color:var(--orange)}
.lb-rk-2{color:#a0a0a0}
.lb-rk-3{color:#cd7f32}
.lb-name{flex:1;min-width:0}
.lb-user{font-weight:600;font-size:13px}
.lb-lvl{font-size:10px;color:var(--text2)}
.lb-pts{font-weight:700;color:var(--text)}
.modal-overlay{position:fixed;inset:0;z-index:200;background:rgba(0,0,0,0.6);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;padding:20px}
.modal{background:var(--card);border:1px solid var(--border2);border-radius:var(--rad-lg);padding:28px;width:100%;max-width:380px;box-shadow:0 24px 80px rgba(0,0,0,0.5)}
.modal .field{margin-bottom:14px}
.modal .field label{display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:var(--text3);margin-bottom:5px}
.modal .field .iw{position:relative}
.modal .field .iw i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text3);font-size:14px}
.modal .field .iw input{padding-left:36px}
.modal .err{background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.15);color:var(--red);padding:10px 14px;border-radius:8px;font-size:13px;text-align:center;margin-bottom:14px}
.modal .success{background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.15);color:var(--green);padding:10px 14px;border-radius:8px;font-size:13px;text-align:center;margin-bottom:14px}
@keyframes fadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.anim{animation:fadeUp 0.5s cubic-bezier(0.16,1,0.3,1) both}
.anim-d1{animation-delay:0.05s}
.anim-d2{animation-delay:0.1s}
.anim-d3{animation-delay:0.15s}
.anim-d4{animation-delay:0.2s}
.driver-row{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;transition:background 0.2s;border-bottom:1px solid var(--border)}
.driver-row:last-child{border-bottom:none}
.driver-row:hover{background:rgba(255,255,255,0.02)}
.driver-pos{width:28px;height:28px;border-radius:6px;background:var(--card2);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:12px;color:var(--text);flex-shrink:0}
.team-badge{width:32px;height:20px;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:8px;font-weight:800;color:#fff;text-transform:uppercase;flex-shrink:0;letter-spacing:0.5px}
.driver-name{font-weight:600;font-size:13px;flex:1}
.team-name{font-size:10px;color:var(--text2)}
.move-btn{width:26px;height:26px;border-radius:6px;border:none;background:var(--card2);color:var(--text2);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:11px;transition:all 0.15s;flex-shrink:0}
.move-btn:hover{background:var(--purple);color:#fff}
.move-btn:disabled{opacity:0.3;cursor:not-allowed}
.move-btn:disabled:hover{background:var(--card2);color:var(--text2)}
.avatar-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(72px,1fr));gap:10px;max-height:400px;overflow-y:auto;padding:4px}
.avatar-option{width:100%;aspect-ratio:1;border-radius:12px;border:2px solid var(--border);background:#fff;cursor:pointer;transition:all 0.2s;display:flex;align-items:center;justify-content:center;overflow:hidden;padding:8px}
.avatar-option:hover{border-color:var(--purple2);box-shadow:0 0 0 3px rgba(124,58,237,0.15)}
.avatar-option.active{border-color:var(--purple);box-shadow:0 0 0 3px rgba(124,58,237,0.3)}
.avatar-option img{width:100%;height:100%;object-fit:contain}
.ach-card{position:relative;padding:16px;border-radius:var(--rad);text-align:center;transition:all 0.3s}
.ach-card.locked{opacity:0.35;filter:grayscale(1)}
.ach-card .ach-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;margin:0 auto 10px}
.ach-card .ach-name{font-weight:700;font-size:12px;margin-bottom:4px}
.ach-card .ach-desc{font-size:10px;color:var(--text2);line-height:1.3}
.ach-tag{position:absolute;top:8px;right:8px;font-size:8px;font-weight:700;text-transform:uppercase;padding:2px 8px;border-radius:999px}
@keyframes spin{to{transform:rotate(360deg)}}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.5}}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
@keyframes float-slow{0%,100%{transform:translateY(0)}50%{transform:translateY(-4px)}}
@keyframes shimmer{0%{background-position:-200% 0}100%{background-position:200% 0}}
@keyframes glow{0%,100%{box-shadow:0 0 8px rgba(124,58,237,0.2)}50%{box-shadow:0 0 24px rgba(124,58,237,0.4)}}
@keyframes glow-green{0%,100%{box-shadow:0 0 8px rgba(34,197,94,0.2)}50%{box-shadow:0 0 24px rgba(34,197,94,0.4)}}
@keyframes glow-blue{0%,100%{box-shadow:0 0 8px rgba(79,124,255,0.2)}50%{box-shadow:0 0 24px rgba(79,124,255,0.4)}}
@keyframes scale-in{from{opacity:0;transform:scale(0.92)}to{opacity:1;transform:scale(1)}}
@keyframes slide-up{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
@keyframes slide-right{from{opacity:0;transform:translateX(-16px)}to{opacity:1;transform:translateX(0)}}
@keyframes bounce-in{0%{opacity:0;transform:scale(0.3)}50%{transform:scale(1.08)}70%{transform:scale(0.95)}100%{opacity:1;transform:scale(1)}}
@keyframes shimmer-card{0%{background-position:200% 0}100%{background-position:-200% 0}}
@keyframes orbit{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}
@keyframes count-pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.06)}}
.anim-float{animation:float 3s ease-in-out infinite}
.anim-float-slow{animation:float-slow 4s ease-in-out infinite}
.anim-shimmer{background:linear-gradient(90deg,transparent,rgba(255,255,255,0.03),transparent);background-size:200% 100%;animation:shimmer 3s ease-in-out infinite}
.anim-glow{animation:glow 3s ease-in-out infinite}
.anim-glow-green{animation:glow-green 3s ease-in-out infinite}
.anim-glow-blue{animation:glow-blue 3s ease-in-out infinite}
.anim-scale-in{animation:scale-in 0.5s cubic-bezier(0.16,1,0.3,1) both}
.anim-slide-up{animation:slide-up 0.6s cubic-bezier(0.16,1,0.3,1) both}
.anim-slide-right{animation:slide-right 0.5s cubic-bezier(0.16,1,0.3,1) both}
.anim-bounce-in{animation:bounce-in 0.6s cubic-bezier(0.16,1,0.3,1) both}
.card-hover{transition:all 0.3s cubic-bezier(0.16,1,0.3,1)}
.card-hover:hover{transform:translateY(-2px) scale(1.01);box-shadow:0 8px 32px rgba(0,0,0,0.4),0 0 0 1px rgba(124,58,237,0.15)}
.card-hover-green:hover{box-shadow:0 8px 32px rgba(0,0,0,0.4),0 0 0 1px rgba(34,197,94,0.15)}
.card-hover-blue:hover{box-shadow:0 8px 32px rgba(0,0,0,0.4),0 0 0 1px rgba(79,124,255,0.15)}
.card-hover-orange:hover{box-shadow:0 8px 32px rgba(0,0,0,0.4),0 0 0 1px rgba(251,146,60,0.15)}
.fa-spinner{animation:spin 1s linear infinite}
.toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:300;background:var(--card);border:1px solid var(--border2);border-radius:var(--rad);padding:12px 20px;font-size:13px;font-weight:600;box-shadow:0 8px 32px rgba(0,0,0,0.5);display:flex;align-items:center;gap:8px;animation:fadeUp 0.3s ease both}
.toast.success{border-color:rgba(34,197,94,0.3);color:var(--green)}
.toast.error{border-color:rgba(239,68,68,0.3);color:var(--red)}
.sortable-ghost{opacity:0.3;background:var(--card2)!important}
.sortable-drag{opacity:0.9;background:var(--card2)!important;border:1px solid var(--purple)!important;box-shadow:0 8px 32px rgba(0,0,0,0.5)!important;transform:scale(1.02)}
.sortable-chosen{background:rgba(124,58,237,0.06)!important}
.driver-row{cursor:grab}
.driver-row:active{cursor:grabbing}
.driver-row.hidden{display:none!important}
</style>
</head>
<body>
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
  common:{color:'var(--green)',bg:'rgba(34,197,94,0.1)',color2:'#22c55e'},
  rare:{color:'var(--blue)',bg:'rgba(79,124,255,0.1)',color2:'#4f7cff'},
  epic:{color:'var(--purple2)',bg:'rgba(168,85,247,0.1)',color2:'#a855f7'},
  legendary:{color:'var(--red)',bg:'rgba(239,68,68,0.1)',color2:'#ef4444'},
  special:{color:'var(--orange)',bg:'rgba(251,146,60,0.1)',color2:'#fb923c'}
};

const getAvatarUrl = (style, seed) => {
  const m = style && style.match(/^(.+)-v(\d+)$/);
  const baseStyle = m ? m[1] : (style || 'avataaars');
  const fullSeed = m ? seed + '_v' + m[2] : (seed || 'user');
  return 'https://api.dicebear.com/7.x/' + baseStyle + '/svg?seed=' + fullSeed;
};

const I = ({ n, s }) => React.createElement('i', { className: 'fa-solid fa-' + n, style: s });

const Nav = ({ user, page, onNav, onLogin, onLogout }) => {
  const links = [
    {page:'dashboard',icon:'grip'}, {page:'predict',icon:'list-ol'}, {page:'results',icon:'flag-checkered'},
    {page:'updates',icon:'newspaper'}, {page:'news',icon:'rss'}, {page:'leaderboard',icon:'trophy'}, {page:'achievements',icon:'medal'}, {page:'profile',icon:'user'}
  ];
  return (
    <nav className="nav">
      <div className="nav-inner">
        <a href="#dashboard" onClick={(e)=>{e.preventDefault();onNav('dashboard')}} className="nav-brand">
          <div className="nav-brand-icon"><I n="flag-checkered" /></div>
          PADDOCK
        </a>
        <div className="nav-links">
          {links.map(l => (
            <a key={l.page} href={'#'+l.page} className={'nav-link'+(page.split('?')[0]===l.page?' active':'')}
               onClick={(e)=>{e.preventDefault();onNav(l.page)}}>
              <I n={l.icon} />
            </a>
          ))}
        </div>
        <div style={{display:'flex',alignItems:'center',gap:'10px'}}>
          {user ? (
            <>
              <a style={{display:'flex',alignItems:'center',gap:'6px',padding:'4px 8px 4px 4px',borderRadius:'8px',background:'var(--card2)',border:'1px solid var(--border)',cursor:'pointer'}}
                 onClick={(e)=>{e.preventDefault();onNav('profile')}}>
                <div style={{width:'28px',height:'28px',borderRadius:'50%',overflow:'hidden',background:'var(--card2)',flexShrink:0}}>
                  <img src={getAvatarUrl(user.avatar_style,user.username)} style={{width:'100%',height:'100%',objectFit:'cover'}} />
                </div>
                <span style={{fontSize:'13px',fontWeight:'600'}}>{user.username}</span>
              </a>
              {user.is_admin && (
                <a href="#admin" className={'nav-link'+(page==='admin'?' active':'')} title="Race Control" onClick={(e)=>{e.preventDefault();onNav('admin')}} style={{color:'var(--orange)'}}><I n="shield" /></a>
              )}
              <a className="nav-link" onClick={(e)=>{e.preventDefault();postAuth({action:'logout'}).then(()=>onLogout())}}><I n="sign-out-alt" /></a>
            </>
          ) : (
            <>
              <a className="btn btn-ghost btn-sm" onClick={(e)=>{e.preventDefault();onLogin()}}>Log In</a>
              <a className="btn btn-primary btn-sm" onClick={(e)=>{e.preventDefault();onLogin()}}>Sign Up</a>
            </>
          )}
        </div>
      </div>
    </nav>
  );
};

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
  const ref = useRef(null);

  const load = () => {
    api('dashboard').then(d => {
      if (d.error !== 'not_authenticated') {
        setData(d);
        setAnimKey(k => k + 1);
      }
      setLoading(false);
    }).catch(() => setLoading(false));
  };

  useEffect(() => { load(); ref.current = setInterval(load, 15000); return () => clearInterval(ref.current); }, []);

  if (loading) return (
    <div className="page" style={{textAlign:'center',paddingTop:120}}>
      <div style={{width:64,height:64,border:'4px solid var(--card2)',borderTopColor:'var(--purple)',borderRadius:'50%',animation:'spin 1s linear infinite',margin:'0 auto 16px'}} />
      <div style={{height:14,width:200,background:'var(--card2)',borderRadius:6,margin:'0 auto',animation:'shimmer-card 2s linear infinite',background:'linear-gradient(90deg,var(--card2) 25%,var(--card) 50%,var(--card2) 75%)',backgroundSize:'200% 100%'}} />
    </div>
  );

  if (!data) return null;
  if (data.error || (!data.stats && !data.accuracy)) {
    return <div className="page" style={{padding:40,textAlign:'center'}}><div className="card" style={{padding:24,background:'rgba(239,68,68,0.1)',border:'1px solid rgba(239,68,68,0.3)'}}><I n="exclamation-triangle" style={{fontSize:32,color:'#ef4444',marginBottom:12}} /><h3 style={{color:'#ef4444',marginBottom:8}}>Dashboard Error</h3><p style={{color:'var(--text2)',fontSize:13}}>{data.message || 'Empty response from server - try refreshing the page.'}</p>{data.file && <p style={{fontSize:11,color:'var(--text3)',marginTop:8}}>{data.file}:{data.line}</p>}</div></div>;
  }

  const nr = data.nextRace;
  const unlockedAchs = (data.userAchievements || []);
  const prevTotal = (data.previousTotal ?? 0);

  const TeamBadge = ({ team }) => {
    const color = TEAM_COLORS[team] || '#555';
    const abbr = TEAM_ABBREV[team] || team?.substring(0,3).toUpperCase() || 'F1';
    return <div className="team-badge" style={{background:color}}>{abbr}</div>;
  };

  return (
    <div className="page" style={{position:'relative',overflow:'hidden',minHeight:'100vh'}}>
      {/* Ambient background orbs */}
      <div style={{position:'fixed',inset:0,pointerEvents:'none',overflow:'hidden',zIndex:0}}>
        <div style={{position:'absolute',width:400,height:400,borderRadius:'50%',background:'radial-gradient(circle,rgba(124,58,237,0.06),transparent 70%)',top:'-100px',left:'-100px',animation:'float 8s ease-in-out infinite'}} />
        <div style={{position:'absolute',width:300,height:300,borderRadius:'50%',background:'radial-gradient(circle,rgba(79,124,255,0.05),transparent 70%)',bottom:'-50px',right:'-50px',animation:'float 6s ease-in-out infinite reverse'}} />
        <div style={{position:'absolute',width:250,height:250,borderRadius:'50%',background:'radial-gradient(circle,rgba(251,146,60,0.04),transparent 70%)',top:'40%',right:'10%',animation:'float 10s ease-in-out infinite 2s'}} />
      </div>

      <div style={{position:'relative',zIndex:1}}>

        {/* ===== HERO ===== */}
        <div className="card anim-scale-in" style={{overflow:'hidden',position:'relative',marginBottom:16,minHeight:200}}>
          <div className="hero-bg" style={{backgroundImage:'url('+(nr?.hero||'')+')',position:'absolute',inset:0,backgroundSize:'cover',backgroundPosition:'center',transition:'transform 0.6s ease'}}></div>
          <div className="hero-overlay" style={{position:'absolute',inset:0,background:'linear-gradient(180deg,rgba(12,15,22,0.2) 0%,rgba(12,15,22,0.85) 60%,var(--bg) 100%)'}}></div>
          <div style={{position:'relative',zIndex:2,padding:24,display:'flex',flexDirection:'row',alignItems:'flex-end',gap:16,flexWrap:'wrap',minHeight:200}}>
            <div style={{flex:1,minWidth:200}}>
              <div style={{display:'flex',alignItems:'center',gap:10,marginBottom:10,flexWrap:'wrap'}}>
                <span className="badge badge-purple anim-bounce-in" style={{animationDelay:'0.1s'}}><I n="flag-checkered" style={{fontSize:10}} /> R{nr?.race_number||'-'}</span>
                {nr && <span style={{fontSize:24,animation:'float 3s ease-in-out infinite'}}>{nr.flag}</span>}
                {data.isDoublePoints && <span className="badge badge-green anim-glow-green"><I n="bolt" /> 2x</span>}
                {data.predictionsOpen && <span className="badge badge-green anim-glow-green"><I n="check-circle" /> Open</span>}
                {!data.predictionsOpen && nr && <span className="badge badge-red"><I n="lock" /> Locked</span>}
              </div>
              <div style={{fontSize:26,fontWeight:'900',textTransform:'uppercase',letterSpacing:'-0.02em',marginBottom:2}}>
                {nr?.country||'Season Complete'}
              </div>
              <div style={{fontSize:13,color:'var(--text2)',marginBottom:8}}>
                <I n="map-marker-alt" style={{color:'var(--purple2)',fontSize:11,marginRight:4}} /> {nr?.circuit_name||''}
              </div>
              <div style={{display:'flex',gap:10,flexWrap:'wrap',alignItems:'center'}}>
                <span style={{fontSize:12,color:'var(--text2)'}}><I n="calendar" style={{marginRight:4}} />{nr ? new Date(nr.race_date).toLocaleDateString('en-US',{weekday:'short',month:'short',day:'numeric',year:'numeric'}) : ''}</span>
                {data.predictionsOpen && (
                  <button className="btn btn-primary btn-sm anim-glow" onClick={()=>onNav('predict')} style={{animationDelay:'1s'}}>
                    <I n="arrow-right" /> Predict Now
                  </button>
                )}
              </div>
            </div>
            <CountdownRing deadline={data.deadline} open={data.predictionsOpen} text={data.countdownText} progress={data.progressBarWidth} />
          </div>
          <StatusBar progress={data.progressBarWidth} open={data.predictionsOpen} />
        </div>

        {/* ===== STATS ROW ===== */}
        <div style={{display:'grid',gridTemplateColumns:'repeat(4,1fr)',gap:10,marginBottom:16}}>
          {[
            { label:'Rank', val:'#'+(data.stats?.rank||'-'), color:'var(--purple2)', icon:'crown', delay:'0.05s' },
            { label:'Points', val:data.stats?.total_points??0, color:'var(--green)', icon:'star', delay:'0.1s' },
            { label:'Races', val:data.stats?.races_participated??0, color:'var(--blue)', icon:'flag-checkered', delay:'0.15s' },
            { label:'Accuracy', val:data.accuracy+'%', color:'var(--orange)', icon:'crosshairs', delay:'0.2s' }
          ].map((s,i) => (
            <div key={i} className="card card-hover" style={{padding:'16px 12px',textAlign:'center',animation:'scale-in 0.5s cubic-bezier(0.16,1,0.3,1) both',animationDelay:s.delay}}>
              <div className="anim-float-slow" style={{fontSize:22,color:s.color,marginBottom:4}}><I n={s.icon} /></div>
              <div style={{fontSize:22,fontWeight:'800',lineHeight:1.2,color:s.color,transition:'all 0.5s ease'}} key={animKey}>{s.val}</div>
              <div style={{fontSize:10,color:'var(--text2)',textTransform:'uppercase',letterSpacing:'0.06em',fontWeight:'600',marginTop:2}}>{s.label}</div>
            </div>
          ))}
        </div>

        {/* ===== PROMO CARDS + CALENDAR ===== */}
        <div style={{display:'grid',gridTemplateColumns:'1fr 1fr 1fr auto',gap:10,marginBottom:16}}>
          <div className="card card-hover" style={{padding:'14px 16px',display:'flex',alignItems:'center',gap:12,background:'linear-gradient(135deg,rgba(124,58,237,0.08),transparent)',border:'1px solid rgba(124,58,237,0.15)'}}>
            <div style={{fontSize:22,color:'var(--purple2)',animation:'float 3s ease-in-out infinite'}}><I n="star" /></div>
            <span style={{fontSize:12,color:'var(--text2)',lineHeight:1.4}}>Stay ahead of the grid — make your picks before the deadline!</span>
          </div>
          <div className="card card-hover" style={{padding:'14px 16px',display:'flex',alignItems:'center',gap:12,background:'linear-gradient(135deg,rgba(79,124,255,0.08),transparent)',border:'1px solid rgba(79,124,255,0.15)'}}>
            <div style={{fontSize:22,color:'var(--blue)',animation:'float 3s ease-in-out infinite 0.5s'}}><I n="trophy" /></div>
            <span style={{fontSize:12,color:'var(--text2)',lineHeight:1.4}}>Double points in China, UK & Singapore — plan your strategy!</span>
          </div>
          <div className="card card-hover" style={{padding:'14px 16px',display:'flex',alignItems:'center',gap:12,background:'linear-gradient(135deg,rgba(251,146,60,0.08),transparent)',border:'1px solid rgba(251,146,60,0.15)'}}>
            <div style={{fontSize:22,color:'var(--orange)',animation:'float 3s ease-in-out infinite 1s'}}><I n="bolt" /></div>
            <span style={{fontSize:12,color:'var(--text2)',lineHeight:1.4}}>Check the leaderboard and see how you stack up against rivals.</span>
          </div>
          <a href="calendar.php" target="_blank" className="card card-hover" style={{padding:'14px 16px',display:'flex',alignItems:'center',gap:8,textDecoration:'none',background:'var(--card2)',border:'1px dashed var(--border2)'}}>
            <I n="calendar-alt" style={{color:'var(--orange)',fontSize:16}} />
            <span style={{fontSize:10,fontWeight:'600',color:'var(--text2)',whiteSpace:'nowrap'}}>Subscribe</span>
          </a>
        </div>

        {/* ===== MAIN GRID ===== */}
        <div style={{display:'grid',gridTemplateColumns:'1.3fr 1fr',gap:14,alignItems:'start'}}>

          {/* LEFT COLUMN */}
          <div style={{display:'flex',flexDirection:'column',gap:14}}>

            {/* Recent Results */}
            <div className="card card-hover anim-slide-up" style={{animationDelay:'0.15s',padding:'16px'}}>
              <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:12}}>
                <span style={{fontSize:11,fontWeight:'700',color:'var(--text2)',textTransform:'uppercase',letterSpacing:'0.08em'}}>
                  <I n="history" style={{color:'var(--blue)',marginRight:6}} />Recent Results
                </span>
                <span style={{fontSize:10,color:'var(--text3)',background:'var(--card2)',padding:'3px 10px',borderRadius:999}}>{data.totalPredictions} picks</span>
              </div>
              {data.recentResults?.length > 0 ? data.recentResults.map((r,i) => {
                const maxPts = Math.max(...(data.recentResults||[]).map(x=>x.total_points), 1);
                const barW = (r.total_points / maxPts) * 100;
                return (
                  <a key={i} href={'#results?race_id='+r.race_id} onClick={(e)=>{e.preventDefault();onNav('results?race_id='+r.race_id)}}
                    style={{display:'block',padding:'10px 0',borderBottom:i<data.recentResults.length-1?'1px solid var(--border)':'none',cursor:'pointer',transition:'all 0.25s'}}
                    onMouseEnter={e=>{e.currentTarget.style.transform='translateX(4px)'}}
                    onMouseLeave={e=>{e.currentTarget.style.transform='translateX(0)'}}>
                    <div style={{display:'flex',alignItems:'center',gap:10}}>
                      <div style={{width:36,height:36,borderRadius:10,background:'linear-gradient(135deg,var(--purple),var(--blue))',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
                        <I n="flag" style={{color:'#fff',fontSize:14}} />
                      </div>
                      <div style={{flex:1,minWidth:0}}>
                        <div style={{display:'flex',alignItems:'center',gap:8}}>
                          <span style={{fontWeight:'700',fontSize:13}}>{r.country} GP</span>
                          <span style={{fontSize:10,color:'var(--text3)'}}>{new Date(r.race_date).toLocaleDateString('en-US',{month:'short',day:'numeric'})}</span>
                        </div>
                        <div style={{marginTop:4,height:4,background:'var(--card2)',borderRadius:2,overflow:'hidden',width:'100%',maxWidth:200}}>
                          <div style={{height:'100%',width:barW+'%',background:'linear-gradient(90deg,var(--green),#16a34a)',borderRadius:2,transition:'width 1s cubic-bezier(0.16,1,0.3,1)'}} />
                        </div>
                      </div>
                      <div style={{fontWeight:'900',fontSize:20,color:'var(--green)',letterSpacing:'-0.02em'}}>+{r.total_points}</div>
                    </div>
                  </a>
                );
              }) : (
                <div style={{textAlign:'center',padding:'24px 0',color:'var(--text3)',fontSize:13}}>
                  <I n="flag-checkered" style={{fontSize:24,display:'block',margin:'0 auto 8px',opacity:0.3}} />
                  No races yet — make your first prediction!
                </div>
              )}
            </div>

            {/* My Picks for Next Race */}
            {data.userPicks?.length > 0 && (
              <div className="card card-hover anim-slide-up" style={{animationDelay:'0.2s',padding:'16px'}}>
                <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:12}}>
                  <span style={{fontSize:11,fontWeight:'700',color:'var(--text2)',textTransform:'uppercase',letterSpacing:'0.08em'}}>
                    <I n="list-ol" style={{color:'var(--orange)',marginRight:6}} />My Predictions
                  </span>
                  <span style={{fontSize:10,color:'var(--text3)',background:'var(--card2)',padding:'3px 10px',borderRadius:999}}>Rd {nr?.race_number||'-'}</span>
                </div>
                <div style={{display:'flex',flexDirection:'column',gap:6}}>
                  {data.userPicks.map((p,i) => (
                    <div key={i} style={{display:'flex',alignItems:'center',gap:10,padding:'8px 10px',borderRadius:10,background:'var(--card2)',border:'1px solid var(--border)',animation:'slide-right 0.4s cubic-bezier(0.16,1,0.3,1) both',animationDelay:(0.25 + i*0.06)+'s'}}>
                      <div style={{width:26,height:26,borderRadius:6,background:'linear-gradient(135deg,var(--purple),var(--orange))',display:'flex',alignItems:'center',justifyContent:'center',fontWeight:'800',fontSize:11,color:'#fff',flexShrink:0}}>P{i+1}</div>
                      <TeamBadge team={p.team} />
                      <span style={{fontWeight:'600',fontSize:13,flex:1}}>{p.driver_name}</span>
                      <span style={{fontSize:10,color:'var(--text3)'}}>#{p.predicted_position}</span>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Upcoming Races */}
            <div className="card card-hover anim-slide-up" style={{animationDelay:'0.25s',padding:'16px'}}>
              <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:8}}>
                <span style={{fontSize:11,fontWeight:'700',color:'var(--text2)',textTransform:'uppercase',letterSpacing:'0.08em'}}>
                  <I n="calendar-alt" style={{color:'var(--purple2)',marginRight:6}} />Upcoming
                </span>
                <span style={{fontSize:10,color:'var(--text3)'}}>{data.upcomingRaces?.length||0} events</span>
              </div>
              {data.upcomingRaces?.length > 0 ? data.upcomingRaces.map((r,i) => (
                <div key={i} className="race-row card-hover" style={{padding:'10px 8px',borderRadius:8,opacity:r.unlocked?1:0.4,animation:'slide-right 0.4s cubic-bezier(0.16,1,0.3,1) both',animationDelay:(0.3 + i*0.04)+'s'}}>
                  <div style={{fontSize:20,width:32,textAlign:'center',flexShrink:0}}>{r.flag || nr?.flag || ''}</div>
                  <div style={{flex:1}}>
                    <div className="r-name" style={{fontSize:13}}>{r.country} GP</div>
                    <div className="r-meta" style={{fontSize:11}}>{new Date(r.race_date).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'})}</div>
                  </div>
                  <div className="r-status">
                    {r.unlocked && r.is_open ? <span className="badge badge-green anim-glow-green"><I n="check-circle" style={{fontSize:9}} /> Open</span>
                      : r.unlocked && !r.is_open ? <span className="badge badge-red"><I n="lock" style={{fontSize:9}} /> Closed</span>
                      : <span className="badge badge-gray"><I n="lock" style={{fontSize:9}} /> Locked</span>}
                  </div>
                </div>
              )) : (
                <div style={{textAlign:'center',padding:'20px 0',color:'var(--text3)',fontSize:13}}>Season complete!</div>
              )}
            </div>
          </div>

          {/* RIGHT COLUMN */}
          <div style={{display:'flex',flexDirection:'column',gap:14}}>

            {/* Standings */}
            <div className="card card-hover anim-slide-up" style={{animationDelay:'0.2s',padding:'16px'}}>
              <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:8}}>
                <span style={{fontSize:11,fontWeight:'700',color:'var(--text2)',textTransform:'uppercase',letterSpacing:'0.08em'}}>
                  <I n="trophy" style={{color:'var(--orange)',marginRight:6}} />Standings
                </span>
                <span className="badge badge-purple" style={{fontSize:9}}>Top 5</span>
              </div>
              {data.leaderboard?.map((p,i) => (
                <div key={i} className={'lb-row card-hover'+(data.auth?.username===p.username?' me':'')} style={{borderRadius:10,padding:'9px 10px',animation:'slide-right 0.4s cubic-bezier(0.16,1,0.3,1) both',animationDelay:(0.25 + i*0.05)+'s'}}>
                  <div className={'lb-rk lb-rk-'+(i<3?(i+1):'')} style={{fontSize:14}}>{i+1}</div>
                  <div style={{width:30,height:30,borderRadius:'50%',overflow:'hidden',background:'var(--card2)',flexShrink:0,border:'2px solid '+(i===0?'var(--orange)':i===1?'#a0a0a0':i===2?'#cd7f32':'transparent')}}>
                    <img src={getAvatarUrl(p.avatar_style,p.username)} style={{width:'100%',height:'100%',objectFit:'cover'}} />
                  </div>
                  <div className="lb-name">
                    <div className="lb-user" style={{fontSize:12}}>
                      {p.username}
                      {i===0 && <I n="crown" style={{color:'var(--orange)',fontSize:10,marginLeft:4}} />}
                      {data.auth?.username===p.username && <span className="badge badge-purple" style={{fontSize:8,padding:'1px 6px',marginLeft:4}}>You</span>}
                    </div>
                    <div className="lb-lvl" style={{fontSize:10}}>{p.races_participated||0} races</div>
                  </div>
                  <div className="lb-pts" style={{fontSize:16}}>{p.total_points}</div>
                </div>
              ))}
              <div style={{marginTop:8,paddingTop:8,borderTop:'1px solid var(--border)'}}>
                <a className="btn btn-outline btn-sm btn-block" onClick={(e)=>{e.preventDefault();onNav('leaderboard')}}><I n="arrow-right" /> Full Standings</a>
              </div>
            </div>

            {/* Trophy Cabinet */}
            <div className="card card-hover anim-slide-up" style={{animationDelay:'0.25s',padding:'16px'}}>
              <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:12}}>
                <span style={{fontSize:11,fontWeight:'700',color:'var(--text2)',textTransform:'uppercase',letterSpacing:'0.08em'}}>
                  <I n="medal" style={{color:'var(--orange)',marginRight:6}} />Trophy Cabinet
                </span>
                <a className="btn btn-outline btn-sm" style={{fontSize:9,padding:'3px 10px'}} onClick={(e)=>{e.preventDefault();onNav('achievements')}}>
                  <I n="trophy" /> All {ALL_ACHIEVEMENTS.length}
                </a>
              </div>
              {unlockedAchs.length > 0 ? (
                <div style={{display:'grid',gridTemplateColumns:'repeat(2,1fr)',gap:8}}>
                  {unlockedAchs.slice(0, 8).map((a,i) => {
                    const def = ALL_ACHIEVEMENTS.find(x => x.id === a.id);
                    if (!def) return null;
                    const t = TIER_CONFIG[def.tier] || TIER_CONFIG.common;
                    return (
                      <div key={a.id} className="card-hover" style={{
                        padding:'10px 8px',borderRadius:10,textAlign:'center',
                        background:t.bg,border:'1px solid '+t.color+'30',
                        animation:'bounce-in 0.5s cubic-bezier(0.16,1,0.3,1) both',
                        animationDelay:(0.3 + i*0.06)+'s',position:'relative',overflow:'hidden'
                      }}>
                        <div style={{fontSize:18,color:t.color2,marginBottom:4}}><I n={def.icon.replace('fa-','')} /></div>
                        <div style={{fontSize:10,fontWeight:'700',color:'var(--text)',lineHeight:1.2}}>{def.name}</div>
                        <div style={{fontSize:8,color:'var(--text3)',marginTop:2}}>{def.desc}</div>
                        <div className={`anim-shimmer`} style={{
                          position:'absolute',inset:0,pointerEvents:'none',
                          background:'linear-gradient(90deg,transparent,rgba(255,255,255,0.03),transparent)',
                          backgroundSize:'200% 100%',animation:'shimmer-card 3s ease-in-out infinite'
                        }} />
                      </div>
                    );
                  })}
                  {unlockedAchs.length > 8 && (
                    <div onClick={(e)=>{e.preventDefault();onNav('achievements')}}
                      style={{padding:'10px 8px',borderRadius:10,textAlign:'center',background:'var(--card2)',border:'1px dashed var(--border2)',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:6,fontSize:11,fontWeight:'600',color:'var(--text2)',transition:'all 0.2s',gridColumn:'span 2'}}>
                      <I n="plus-circle" /> {unlockedAchs.length - 8} more
                    </div>
                  )}
                </div>
              ) : (
                <div style={{textAlign:'center',padding:'20px 0',color:'var(--text3)',fontSize:13}}>
                  <I n="trophy" style={{fontSize:28,display:'block',margin:'0 auto 8px',opacity:0.2}} />
                  No achievements unlocked yet
                </div>
              )}
            </div>

          </div>
        </div>
      </div>
    </div>
  );
};

const LeaderboardPage = () => {
  const [d, setD] = useState(null);
  useEffect(() => { api('leaderboard').then(setD).catch(() => setD(null)); }, []);
  const lb = d?.leaderboard || [];
  const medalIcons = ['crown','medal','medal'];
  const medalColors = ['#ffd700','#c0c0c0','#cd7f32'];
  const gradColors = [
    'linear-gradient(135deg,rgba(255,215,0,0.15),rgba(255,180,0,0.05))',
    'linear-gradient(135deg,rgba(192,192,192,0.12),rgba(160,160,160,0.04))',
    'linear-gradient(135deg,rgba(205,127,50,0.12),rgba(180,100,30,0.04))'
  ];

  const top3 = lb.slice(0, 3);
  const rest = lb.slice(3);

  return (
    <div className="page" style={{maxWidth:800}}>
      <div className="anim" style={{textAlign:'center',marginBottom:'32px'}}>
        <div style={{fontSize:'11px',fontWeight:'700',textTransform:'uppercase',letterSpacing:'0.12em',color:'var(--purple2)',marginBottom:'6px'}}>Season 2026</div>
        <h1 style={{fontSize:'32px',fontWeight:'900',letterSpacing:'-0.02em',marginBottom:'4px'}}>Championship <span style={{background:'linear-gradient(135deg,var(--purple2),var(--purple))',WebkitBackgroundClip:'text',WebkitTextFillColor:'transparent'}}>Standings</span></h1>
        <p style={{color:'var(--text2)',fontSize:'14px'}}>{lb.length} players competing for the title</p>
      </div>

      {top3.length > 0 && (
        <div className="anim anim-d1" style={{display:'grid',gridTemplateColumns:'1fr 1.2fr 1fr',gap:'0',alignItems:'end',marginBottom:'32px',padding:'0 20px'}}>
          {[1,0,2].map(pi => {
            const p = top3[pi];
            if (!p) return <div key={pi}></div>;
            const isFirst = pi === 0;
            const col = medalColors[pi];
            return (
              <div key={p.id} style={{textAlign:'center',transform:isFirst?'translateY(-12px)':'none',position:'relative'}}>
                <div style={{background:gradColors[pi],borderRadius:'20px',border:'1px solid ' + col + '30',padding:isFirst?'28px 16px 24px':'20px 14px 18px',margin:'0 4px'}}>
                  <div style={{fontSize:'28px',marginBottom:'8px',color:col}}>
                    <I n={medalIcons[pi]} />
                  </div>
                  <div style={{width:isFirst?'72px':'56px',height:isFirst?'72px':'56px',borderRadius:'50%',overflow:'hidden',margin:'0 auto 10px',border:'3px solid ' + col,background:'var(--card2)'}}>
                    <img src={getAvatarUrl(p.avatar_style,p.username)} style={{width:'100%',height:'100%',objectFit:'cover'}} />
                  </div>
                  <div style={{fontWeight:'800',fontSize:isFirst?'16px':'14px',marginBottom:'2px'}}>{p.username}</div>
                  <div style={{fontSize:'10px',color:'var(--text2)',marginBottom:'8px'}}>{p.full_name||''}</div>
                  <div style={{fontWeight:'900',fontSize:isFirst?'28px':'22px',color:col}}>{p.total_points}</div>
                  <div style={{fontSize:'9px',color:'var(--text2)',textTransform:'uppercase',letterSpacing:'0.06em',marginTop:'2px'}}>Points</div>
                  {d?.auth?.username===p.username && (
                    <div style={{marginTop:'8px'}}><span className="badge badge-purple" style={{fontSize:'9px',padding:'2px 10px'}}>You</span></div>
                  )}
                </div>
                <div style={{fontSize:'12px',fontWeight:'700',color:col,marginTop:'10px',textTransform:'uppercase',letterSpacing:'0.08em'}}>
                  {pi === 0 ? 'Championship Leader' : pi === 1 ? '2nd Place' : '3rd Place'}
                </div>
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
        <div className="card" style={{padding:'4px 16px',background:'var(--card)'}}>
          {rest.map((p,i) => {
            const rank = i + 4;
            return (
              <div key={p.id} className={'lb-row'+(d?.auth?.username===p.username?' me':'')} style={{padding:'14px 8px'}}>
                <div style={{width:'28px',textAlign:'center',fontWeight:'700',fontSize:'14px',color:'var(--text2)'}}>{rank}</div>
                <div style={{width:'34px',height:'34px',borderRadius:'50%',overflow:'hidden',background:'var(--card2)',flexShrink:0}}>
                  <img src={getAvatarUrl(p.avatar_style,p.username)} style={{width:'100%',height:'100%',objectFit:'cover'}} />
                </div>
                <div className="lb-name" style={{marginLeft:'8px'}}>
                  <div className="lb-user" style={{fontSize:'14px'}}>{p.username} {d?.auth?.username===p.username && <span className="badge badge-purple" style={{fontSize:'8px',padding:'1px 6px',marginLeft:'4px'}}>You</span>}</div>
                  <div className="lb-lvl" style={{fontSize:'11px'}}>{p.full_name||''}</div>
                </div>
                <div style={{fontSize:'12px',color:'var(--text2)',fontWeight:'500',marginLeft:'auto',marginRight:'24px'}}>{p.races_participated||0}</div>
                <div style={{fontWeight:'800',fontSize:'18px',color:'var(--green)',minWidth:'50px',textAlign:'right'}}>{p.total_points}</div>
              </div>
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
      const p = (d.predictions || []).reduce((a, c) => { a[c.driver_id] = c; return a; }, {});
      setPreds(p); setDrivers(d.drivers); setConstructors(d.constructors);
      setExistingConstructor(d.constructor_prediction || null);
      setRaceData(d.race || d.upcomingRaces?.[0] || null);
      setDeadline(d.deadline ? new Date(d.deadline) : null);
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
  if (!data) return <div className="page" style={{textAlign:'center',paddingTop:120,color:'var(--text3)'}}>No race available</div>;

  const nr = data.nextRace;
  const deadlineMs = data.deadline ? data.deadline * 1000 : 0;
  const constructorProjection = calcConstructorProjection();
  const canEdit = data.predictionsOpen && (editing || !data.hasPrediction);

  return (
    <div className="page">
      {toast && <Toast message={toast.message} type={toast.type} onClose={()=>setToast(null)} />}

      <div className="hero anim" style={{minHeight:200,marginBottom:'16px'}}>
        <div className="hero-bg" style={{backgroundImage:'url(https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?q=80&w=2070&auto=format&fit=crop)'}}></div>
        <div className="hero-overlay"></div>
        <div className="hero-content">
          <div style={{display:'flex',alignItems:'center',justifyContent:'space-between'}}>
            <div>
              <div style={{display:'flex',alignItems:'center',gap:'10px',marginBottom:'6px'}}>
                <span className="badge badge-purple"><I n="flag-checkered" style={{fontSize:10}} /> R{nr?.race_number||'-'}</span>
                {nr && <span style={{fontSize:'20px'}}>{nr.flag}</span>}
              </div>
              <div style={{fontSize:'24px',fontWeight:'900',textTransform:'uppercase'}}>{nr?.country||''} GP</div>
              <div style={{fontSize:'12px',color:'var(--text2)',marginTop:'2px'}}>{nr?.circuit_name||''}</div>
            </div>
            <div style={{textAlign:'right'}}>
              <CountdownRing deadline={deadlineMs} open={data.predictionsOpen} text={data.countdownText} progress={data.progressBarWidth} />
              <StatusBar progress={data.progressBarWidth} open={data.predictionsOpen} />
            </div>
          </div>
        </div>
      </div>

      {!data.predictionsOpen && (
        <div className="card" style={{padding:'14px 20px',marginBottom:'16px',background:'rgba(239,68,68,0.06)',borderColor:'rgba(239,68,68,0.2)'}}>
          <div style={{display:'flex',alignItems:'center',gap:'10px'}}>
            <I n="lock" style={{color:'var(--red)'}} />
            <span style={{fontWeight:'600',fontSize:'14px',color:'var(--red)'}}>Predictions Closed</span>
            <span style={{color:'var(--text2)',fontSize:'13px',marginLeft:'auto'}}>Deadline has passed</span>
          </div>
        </div>
      )}

      {data.predictionsOpen && data.hasPrediction && !editing && (
        <div className="card" style={{padding:'14px 20px',marginBottom:'16px',background:'rgba(34,197,94,0.06)',borderColor:'rgba(34,197,94,0.2)'}}>
          <div style={{display:'flex',alignItems:'center',gap:'10px'}}>
            <I n="check-circle" style={{color:'var(--green)'}} />
            <span style={{fontWeight:'600',fontSize:'14px',color:'var(--green)'}}>Prediction Submitted</span>
            <button className="btn btn-outline btn-sm" onClick={()=>setEditing(true)} style={{marginLeft:'auto'}}><I n="pencil" /> Edit</button>
          </div>
        </div>
      )}

      <div style={{display:'grid',gridTemplateColumns:'1fr 300px',gap:'16px',alignItems:'start'}}>
        <div className="card anim anim-d1" style={{padding:'12px'}}>
          <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:'8px',padding:'4px 8px'}}>
            <span style={{fontSize:'12px',fontWeight:'600',color:'var(--text2)',textTransform:'uppercase',letterSpacing:'0.04em'}}>
              <I n="list-ol" style={{marginRight:6}} />Driver Order
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
            <I n="industry" style={{marginRight:6}} />Projected Constructors
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
      <div className="page" style={{maxWidth:600}}>
        <div className="anim" style={{marginBottom:'20px',textAlign:'center'}}>
          <h1 style={{fontSize:'24px',fontWeight:'800'}}>Race <span style={{color:'var(--purple2)'}}>Results</span></h1>
          <p style={{color:'var(--text2)',fontSize:'13px',marginTop:'4px'}}>Select a race to view results</p>
        </div>
        <div className="card" style={{padding:'12px 16px'}}>
          {d.raceList?.map(rc => (
            <div key={rc.id} className="race-row" style={{cursor:'pointer'}} onClick={() => loadRace(rc.id)}>
              <div className="r-flag">{rc.flag}</div>
              <div style={{flex:1}}>
                <div className="r-name">{rc.country} GP</div>
                <div className="r-meta">{new Date(rc.race_date).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'})}</div>
              </div>
              <div className="r-status">
                {rc.status === 'completed' ? <span className="badge badge-green"><I n="check" style={{fontSize:9}} /> Results</span> :
                 <span className="badge badge-gray"><I n="clock" style={{fontSize:9}} /> Upcoming</span>}
              </div>
            </div>
          ))}
        </div>
      </div>
    );
  }

  const r = d.race;
  return (
    <div className="page">
      <div className="hero anim" style={{minHeight:180,marginBottom:'16px'}}>
        <div className="hero-bg" style={{backgroundImage:'url(https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?q=80&w=2070&auto=format&fit=crop)'}}></div>
        <div className="hero-overlay" style={{background:'linear-gradient(180deg,rgba(12,15,22,0.3) 0%,rgba(12,15,22,0.9) 100%)'}}></div>
        <div className="hero-content">
          <div style={{display:'flex',alignItems:'center',gap:'12px'}}>
            <span style={{fontSize:'28px'}}>{r?.flag||''}</span>
            <div>
              <div style={{fontSize:'22px',fontWeight:'900',textTransform:'uppercase'}}>{r?.country||''} GP</div>
              <div style={{fontSize:'13px',color:'var(--text2)'}}>{r?.circuit_name||''} &middot; {r?.race_date ? new Date(r.race_date).toLocaleDateString('en-US',{month:'long',day:'numeric',year:'numeric'}) : ''}</div>
            </div>
          </div>
        </div>
      </div>

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
            <div className="stat-grid anim" style={{marginBottom:'16px'}}>
              <div className="card stat-box">
                <div className="v" style={{color:'var(--green)'}}>{d.scoreRecord.total_points}</div>
                <div className="l">Total Points</div>
              </div>
              <div className="card stat-box">
                <div className="v" style={{color:'var(--blue)'}}>{d.scoreRecord.driver_points}</div>
                <div className="l">Base Points</div>
              </div>
              <div className="card stat-box">
                <div className="v" style={{color:'var(--purple2)'}}>{d.scoreRecord.top3_bonus}</div>
                <div className="l">Podium Bonus</div>
              </div>
              <div className="card stat-box">
                <div className="v" style={{color:'var(--orange)'}}>{d.scoreRecord.constructor_points}</div>
                <div className="l">Constructor</div>
              </div>
            </div>
          )}

          <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:'16px',alignItems:'start'}}>
            <div className="card anim anim-d1" style={{padding:'14px 16px'}}>
              <div style={{fontSize:'12px',fontWeight:'600',color:'var(--text2)',textTransform:'uppercase',letterSpacing:'0.04em',marginBottom:'8px'}}>
                <I n="list" style={{marginRight:6}} />Predictions vs Actual
              </div>
              {d.predictions?.map((p,i) => (
                <div className="driver-row" key={i}>
                  <div className="driver-pos">{p.predicted_position}</div>
                  <TeamBadge team={p.team} />
                  <div style={{flex:1,minWidth:0}}>
                    <div className="driver-name">{p.driver_name}</div>
                  </div>
                  {p.actual_position ? (
                    <>
                      <span style={{fontSize:'11px',color:'var(--text2)',marginRight:'4px'}}>P{p.actual_position}</span>
                      {p.is_exact
                        ? <span className="badge badge-green"><I n="check" style={{fontSize:9}} /></span>
                        : <span className="badge badge-red"><I n="times" style={{fontSize:9}} /></span>
                      }
                      <span style={{fontWeight:'700',fontSize:'13px',color:'var(--green)',marginLeft:'6px',minWidth:30,textAlign:'right'}}>{p.points_earned > 0 ? '+'+p.points_earned : 0}</span>
                    </>
                  ) : (
                    <span style={{fontSize:'11px',color:'var(--text3)'}}>N/A</span>
                  )}
                </div>
              ))}
            </div>

            <div style={{display:'flex',flexDirection:'column',gap:'12px'}}>
              <div className="card anim anim-d2" style={{padding:'14px 16px'}}>
                <div style={{fontSize:'12px',fontWeight:'600',color:'var(--text2)',textTransform:'uppercase',letterSpacing:'0.04em',marginBottom:'8px'}}>
                  <I n="trophy" style={{marginRight:6}} />Race Leaderboard
                </div>
                {d.raceLeaderboard?.map((u,i) => (
                  <div className="lb-row" key={i} style={{padding:'8px 4px'}}>
                    <div className={'lb-rk lb-rk-'+(i<3?(i+1):'')}>{i+1}</div>
                    <div className="lb-name">
                      <div className="lb-user">{u.username}</div>
                    </div>
                    <div style={{display:'flex',gap:'8px',alignItems:'center'}}>
                      <span style={{fontSize:'10px',color:'var(--text3)'}}>{u.driver_points}</span>
                      {u.top3_bonus > 0 && <I n="crown" style={{fontSize:'10px',color:'var(--orange)'}} />}
                      {u.constructor_points > 0 && <I n="wrench" style={{fontSize:'10px',color:'var(--blue)'}} />}
                      <div className="lb-pts" style={{fontSize:'15px'}}>{u.total_points}</div>
                    </div>
                  </div>
                ))}
              </div>

              <div className="card anim anim-d3" style={{padding:'14px 16px'}}>
                <div style={{fontSize:'12px',fontWeight:'600',color:'var(--text2)',textTransform:'uppercase',letterSpacing:'0.04em',marginBottom:'8px'}}>
                  <I n="flag-checkered" style={{marginRight:6}} />Official Results
                </div>
                {d.actualResults?.map((res,i) => (
                  <div className="driver-row" key={i}>
                    <div className="driver-pos" style={{background:res.position <= 3 ? ['var(--orange)','#a0a0a0','#cd7f32'][res.position-1] + '33' : 'var(--card2)'}}>{res.position}</div>
                    <TeamBadge team={res.constructor_name} />
                    <div style={{flex:1,minWidth:0}}>
                      <div className="driver-name">{res.driver_name}</div>
                      <div className="team-name">{res.constructor_name}</div>
                    </div>
                    {res.fastest_lap > 0 && <span className="badge badge-purple" style={{fontSize:'9px'}}><I n="bolt" /> FL</span>}
                  </div>
                ))}
              </div>
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
        <div style={{width:'96px',height:'96px',borderRadius:'50%',overflow:'hidden',background:'var(--card2)',margin:'0 auto 16px',border:'3px solid var(--purple)'}}>
          <img src={getAvatarUrl(data.currentAvatarStyle,data.auth?.username)} style={{width:'100%',height:'100%',objectFit:'cover'}} />
        </div>
        <h2 style={{fontSize:'24px',fontWeight:'800'}}>{data.auth?.username||''}</h2>
        <p style={{color:'var(--text2)',fontSize:'14px',marginBottom:'6px'}}>{data.auth?.email||''}</p>
        <div className="stat-grid" style={{marginTop:'20px',maxWidth:440,marginLeft:'auto',marginRight:'auto',gap:'12px'}}>
          <div className="card stat-box" style={{background:'var(--card2)',padding:'18px 12px'}}>
            <div className="v" style={{color:'var(--purple2)'}}>#{stats.rank||'-'}</div>
            <div className="l">Rank</div>
          </div>
          <div className="card stat-box" style={{background:'var(--card2)',padding:'18px 12px'}}>
            <div className="v" style={{color:'var(--green)'}}>{stats.total_points||0}</div>
            <div className="l">Points</div>
          </div>
          <div className="card stat-box" style={{background:'var(--card2)',padding:'18px 12px'}}>
            <div className="v" style={{color:'var(--blue)'}}>{stats.races_participated||0}</div>
            <div className="l">Races</div>
          </div>
          <div className="card stat-box" style={{background:'var(--card2)',padding:'18px 12px'}}>
            <div className="v" style={{color:'var(--orange)'}}>{data.accuracy||0}%</div>
            <div className="l">Accuracy</div>
          </div>
        </div>
      </div>

      <div className="card anim anim-d1" style={{padding:'24px',marginBottom:'24px'}}>
        <div style={{fontSize:'14px',fontWeight:'700',color:'var(--text)',textTransform:'uppercase',letterSpacing:'0.06em',marginBottom:'16px'}}>
          <I n="palette" style={{marginRight:8,color:'var(--purple2)'}} />Choose Your Avatar
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
        <div style={{fontSize:'14px',fontWeight:'700',color:'var(--text)',textTransform:'uppercase',letterSpacing:'0.06em',marginBottom:'20px'}}>
          <I n="cog" style={{marginRight:8,color:'var(--purple2)'}} />Account Settings
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
        <div style={{fontSize:'14px',fontWeight:'700',color:'var(--text)',textTransform:'uppercase',letterSpacing:'0.06em',marginBottom:'16px'}}>
          <I n="crosshairs" style={{marginRight:8,color:'var(--purple2)'}} />Prediction Accuracy
        </div>
        <div className="stat-grid" style={{gap:'12px'}}>
          <div className="card stat-box" style={{background:'var(--card2)',padding:'18px 12px'}}>
            <div className="v" style={{color:'var(--green)'}}>{data.accuracy}%</div>
            <div className="l">Overall</div>
          </div>
          <div className="card stat-box" style={{background:'var(--card2)',padding:'18px 12px'}}>
            <div className="v" style={{color:'var(--orange)'}}>{data.avgPositionError||'-'}</div>
            <div className="l">Avg Error</div>
          </div>
          <div className="card stat-box" style={{background:'var(--card2)',padding:'18px 12px'}}>
            <div className="v" style={{color:'var(--purple2)'}}>{data.exactMatches||0}</div>
            <div className="l">Exact</div>
          </div>
        </div>
      </div>

      <div className="card anim anim-d4" style={{padding:'24px'}}>
        <div style={{fontSize:'14px',fontWeight:'700',color:'var(--text)',textTransform:'uppercase',letterSpacing:'0.06em',marginBottom:'16px'}}>
          <I n="crown" style={{marginRight:8,color:'var(--purple2)'}} />Best Performance
        </div>
        {data.bestRace ? (
          <div style={{background:'var(--card2)',padding:'24px',borderRadius:'var(--rad)',textAlign:'center',marginBottom:'20px'}}>
            <div style={{fontSize:'42px',fontWeight:'900',color:'var(--green)'}}>+{data.bestRace.total_points}</div>
            <div style={{fontWeight:'700',fontSize:'15px',marginTop:'6px'}}>{data.bestRace.country} GP</div>
            <div style={{fontSize:'12px',color:'var(--text2)',marginTop:'4px'}}>{new Date(data.bestRace.race_date).toLocaleDateString('en-US',{month:'long',day:'numeric',year:'numeric'})}</div>
          </div>
        ) : (
          <div style={{textAlign:'center',padding:'32px',color:'var(--text3)',fontSize:'13px',marginBottom:'20px'}}>No races yet</div>
        )}
        <div style={{fontSize:'12px',fontWeight:'600',color:'var(--text2)',textTransform:'uppercase',letterSpacing:'0.04em',marginBottom:'12px'}}>
          <I n="history" style={{marginRight:6}} />Race History
        </div>
        {data.recentRaces?.length > 0 ? data.recentRaces.map((rc,i) => (
          <div className="race-row" key={i} style={{padding:'12px 0'}}>
            <div style={{width:'32px',height:'32px',borderRadius:'8px',background:'var(--card2)',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
              <I n="flag-checkered" style={{fontSize:'12px',color:'var(--text3)'}} />
            </div>
            <div style={{flex:1}}>
              <div className="r-name" style={{fontSize:'14px'}}>{rc.country} GP</div>
              <div className="r-meta">{new Date(rc.race_date).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'})}</div>
            </div>
            <div style={{fontWeight:'800',fontSize:'16px',color:'var(--green)'}}>+{rc.total_points}</div>
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
        <h1 style={{fontSize:'24px',fontWeight:'800'}}>Achievements</h1>
        <p style={{color:'var(--text2)',fontSize:'13px',marginTop:'4px'}}>Unlock every achievement to become the ultimate predictor</p>
      </div>

      <div className="stat-grid anim anim-d1" style={{maxWidth:500,margin:'0 auto 20px'}}>
        <div className="card stat-box">
          <div className="v" style={{color:'var(--green)'}}>{unlockedCount}</div>
          <div className="l">Unlocked</div>
        </div>
        <div className="card stat-box">
          <div className="v" style={{color:'var(--text)'}}>{totalCount}</div>
          <div className="l">Total</div>
        </div>
        <div className="card stat-box">
          <div className="v" style={{color:'var(--purple2)'}}>{completionPct}%</div>
          <div className="l">Complete</div>
        </div>
        <div className="card stat-box">
          <div className="v" style={{color:'var(--orange)'}}>{profile?.auth?.username?.charAt(0)?.toUpperCase()||''}</div>
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
            <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fill,minmax(180px,1fr))',gap:'10px'}}>
              {items.map(ach => {
                const isUnlocked = unlockedIds.has(ach.id);
                return (
                  <div key={ach.id} className={'card ach-card'+(isUnlocked?'':' locked')} style={{background: isUnlocked ? t.bg : 'var(--card)'}}>
                    {isUnlocked && (
                      <div className="ach-tag" style={{background:t.color2,color:'#fff'}}>Unlocked</div>
                    )}
                    <div className="ach-icon" style={{background:t.bg,color:t.color2}}>
                      <I n={ach.icon.replace('fa-','')} />
                    </div>
                    <div className="ach-name">{ach.name}</div>
                    <div className="ach-desc">{ach.desc}</div>
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
        <h1 style={{fontSize:'24px',fontWeight:'800'}}>Race <span style={{color:'var(--purple2)'}}>Roundup</span></h1>
        <p style={{color:'var(--text2)',fontSize:'13px',marginTop:'4px'}}>Latest race results, standings, and prediction tracker</p>
      </div>

      {d.lastRace && (
        <>
          <div className="card anim anim-d1" style={{padding:'16px',marginBottom:'16px'}}>
            <div style={{display:'flex',alignItems:'center',gap:'12px',marginBottom:'14px'}}>
              <span style={{fontSize:'24px'}}>{d.lastRace.flag}</span>
              <div>
                <div style={{fontSize:'16px',fontWeight:'700'}}>{d.lastRace.country} GP <span className="badge badge-green" style={{marginLeft:'8px',fontSize:'9px'}}>Completed</span></div>
                <div style={{fontSize:'12px',color:'var(--text2)'}}>{new Date(d.lastRace.race_date).toLocaleDateString('en-US',{month:'long',day:'numeric',year:'numeric'})}</div>
              </div>
            </div>

            {d.raceWinner && (
              <div style={{display:'flex',alignItems:'center',gap:'12px',padding:'12px',background:'var(--card2)',borderRadius:'var(--rad)',marginBottom:'12px'}}>
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
                <div style={{fontSize:'16px',width:'24px',textAlign:'center'}}>{i===0?'\u{1F947}':i===1?'\u{1F948}':i===2?'\u{1F949}':(i+1)}</div>
                <div style={{width:'28px',height:'28px',borderRadius:'50%',overflow:'hidden',background:'var(--card2)',flexShrink:0}}>
                  <img src={getAvatarUrl(s.avatar_style,s.username)} style={{width:'100%',height:'100%',objectFit:'cover'}} />
                </div>
                <div className="lb-name">
                  <div className="lb-user">{s.username}</div>
                </div>
                <div style={{fontSize:'15px',fontWeight:'700',color:'var(--green)'}}>+{s.total_points}</div>
              </div>
            ))}

            {(d.podiumSweepUsers?.length > 0 || d.constructorBonusUsers?.length > 0) && (
              <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:'10px',marginTop:'12px'}}>
                {d.podiumSweepUsers?.length > 0 && (
                  <div style={{background:'var(--card2)',padding:'10px',borderRadius:'var(--rad)'}}>
                    <div style={{fontSize:'10px',color:'var(--orange)',fontWeight:'700',textTransform:'uppercase',letterSpacing:'0.04em',marginBottom:'4px'}}><I n="crown" style={{marginRight:4}} />Podium Sweep</div>
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
        </>
      )}

      {d.nextRace && (
        <div className="card anim anim-d2" style={{padding:'16px',marginBottom:'16px'}}>
          <div style={{display:'flex',alignItems:'center',gap:'12px',marginBottom:'12px'}}>
            <I n="calendar-alt" style={{color:'var(--purple2)'}} />
            <div style={{fontSize:'14px',fontWeight:'700'}}>Next Race: {d.nextRace.country} GP</div>
          </div>
          <div style={{marginBottom:'10px'}}>
            <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:'6px'}}>
              <span style={{fontSize:'12px',fontWeight:'600',color:'var(--text2)'}}>Prediction Progress</span>
              <span style={{fontSize:'11px',fontWeight:'600',color:'var(--purple2)'}}>{d.submitted?.length||0}/{d.submitted?.length+d.missing?.length||0}</span>
            </div>
            <div className="status-bar">
              <div className="status-fill purple" style={{width:Math.max(d.submissionPct||0,3)+'%'}}></div>
            </div>
          </div>
          <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:'8px'}}>
            <div>
              <div style={{fontSize:'10px',fontWeight:'600',color:'var(--green)',textTransform:'uppercase',letterSpacing:'0.04em',marginBottom:'6px'}}>Submitted ({d.submitted?.length||0})</div>
              {d.submitted?.slice(0,8).map((s,i) => (
                <div key={i} style={{display:'flex',alignItems:'center',gap:'6px',padding:'4px 0'}}>
                  <div style={{width:'20px',height:'20px',borderRadius:'50%',overflow:'hidden',background:'var(--card2)'}}>
                    <img src={getAvatarUrl(s.avatar_style,s.username)} style={{width:'100%',height:'100%',objectFit:'cover'}} />
                  </div>
                  <span style={{fontSize:'11px',fontWeight:'600'}}>{s.username}</span>
                </div>
              ))}
            </div>
            <div>
              <div style={{fontSize:'10px',fontWeight:'600',color:'var(--red)',textTransform:'uppercase',letterSpacing:'0.04em',marginBottom:'6px'}}>Missing ({d.missing?.length||0})</div>
              {d.missing?.slice(0,8).map((s,i) => (
                <div key={i} style={{display:'flex',alignItems:'center',gap:'6px',padding:'4px 0'}}>
                  <div style={{width:'20px',height:'20px',borderRadius:'50%',overflow:'hidden',background:'var(--card2)'}}>
                    <img src={getAvatarUrl(s.avatar_style,s.username)} style={{width:'100%',height:'100%',objectFit:'cover'}} />
                  </div>
                  <span style={{fontSize:'11px',fontWeight:'600'}}>{s.username}</span>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      <div className="card anim anim-d3" style={{padding:'16px'}}>
        <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:'8px'}}>
          <span style={{fontSize:'12px',fontWeight:'600',color:'var(--text2)',textTransform:'uppercase',letterSpacing:'0.04em'}}><I n="trophy" style={{color:'var(--orange)',marginRight:6}} />Leaderboard Snapshot</span>
          <a className="btn btn-outline btn-sm" onClick={(e)=>{e.preventDefault();onNav('leaderboard')}}>Full Standings</a>
        </div>
        {d.leaderboard?.map((p,i) => (
          <div className={'lb-row'+(d.auth?.username===p.username?' me':'')} key={i} style={{padding:'8px 4px'}}>
            <div className={'lb-rk lb-rk-'+(i<3?(i+1):'')}>{i+1}</div>
            <div style={{width:'28px',height:'28px',borderRadius:'50%',overflow:'hidden',background:'var(--card2)',flexShrink:0}}>
              <img src={getAvatarUrl(p.avatar_style,p.username)} style={{width:'100%',height:'100%',objectFit:'cover'}} />
            </div>
            <div className="lb-name">
              <div className="lb-user" style={{fontSize:'12px'}}>{p.username}</div>
            </div>
            <div className="lb-pts" style={{fontSize:'14px'}}>{p.total_points}</div>
          </div>
        ))}
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
          <div style={{width:80,height:80,border:'4px solid var(--orange)',borderTopColor:'transparent',borderRadius:'50%',animation:'spin 1s linear infinite',marginBottom:24}} />
          <div style={{color:'#fff',fontWeight:'800',fontSize:'24px',animation:'pulse 1s ease-in-out infinite',textTransform:'uppercase',fontStyle:'italic',letterSpacing:'0.04em'}}>Processing Telemetry...</div>
        </div>
      )}

      {/* Header */}
      <div style={{marginBottom:32}}>
        <span style={{background:'var(--red)',color:'#fff',fontSize:10,fontWeight:'900',padding:'4px 12px',borderRadius:999,textTransform:'uppercase',letterSpacing:'0.12em',display:'inline-block',marginBottom:12}}>
          <I n="terminal" style={{marginRight:6}} />Manual Override Mode
        </span>
        <h1 style={{fontSize:48,fontWeight:'900',color:'#fff',fontStyle:'italic',textTransform:'uppercase',lineHeight:1.1}}>Race <span style={{background:'linear-gradient(135deg,var(--purple),var(--orange))',WebkitBackgroundClip:'text',WebkitTextFillColor:'transparent'}}>Commander</span></h1>
        <p style={{color:'var(--text2)',fontSize:14,marginTop:8}}>Update race results, calculate points, and move the season forward.</p>
      </div>

      {/* Main Card */}
      <div className="card" style={{padding:32,borderTop:'4px solid var(--blue)',borderRadius:24}}>

        {/* Step 1: Race Selector */}
        <div style={{marginBottom:32,padding:24,background:'rgba(79,124,255,0.08)',border:'1px solid rgba(79,124,255,0.2)',borderRadius:16,display:'flex',flexDirection:'row',alignItems:'center',gap:16,flexWrap:'wrap'}}>
          <div style={{display:'flex',alignItems:'center',gap:8,color:'var(--blue)',fontWeight:'900',textTransform:'uppercase',fontSize:12,letterSpacing:'0.08em',whiteSpace:'nowrap'}}>
            <I n="flag-checkered" /> Step 1: Select Race
          </div>
          <select value={raceId} onChange={e => setRaceId(e.target.value)}
            style={{flex:1,minWidth:200,padding:'14px 16px',borderRadius:12,background:'rgba(0,0,0,0.3)',border:'1px solid var(--border2)',color:'#fff',fontWeight:'700',fontSize:14,outline:'none',cursor:'pointer'}}>
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
            <h2 style={{fontSize:28,fontWeight:'900',color:'#fff',fontStyle:'italic',textTransform:'uppercase',lineHeight:1}}>Step 2: <span style={{color:'var(--blue)'}}>Ultra-Parser v2.0</span></h2>
            <p style={{color:'var(--text3)',fontSize:12,marginTop:6}}>DUMP THE WHOLE TABLE FROM BBC / SKY / ESPN BELOW. AUTO-DETECTS DRIVERS.</p>
          </div>
          <div style={{display:'flex',gap:12,alignItems:'center'}}>
            <button className="btn btn-outline btn-sm" onClick={clearParser}><I n="trash-alt" /> Clear Deck</button>
            <div style={{padding:'10px 20px',background:'var(--card2)',borderRadius:10,border:'1px solid rgba(79,124,255,0.2)'}}>
              <span style={{color:'var(--blue)',fontWeight:'900',fontSize:13}}>{detectedCount} / {drivers.length} DETECTED</span>
            </div>
          </div>
        </div>

        <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:24}}>
          {/* Input Zone */}
          <div>
            <div className="card" style={{position:'relative',padding:0,overflow:'hidden'}}>
              <div style={{position:'absolute',top:12,left:16,fontSize:9,fontWeight:'900',color:'rgba(79,124,255,0.4)',textTransform:'uppercase',letterSpacing:'0.2em',pointerEvents:'none',zIndex:1}}>Input Stream</div>
              <textarea value={pasteText} onChange={e => autoParse(e.target.value)}
                style={{width:'100%',height:500,padding:'40px 20px 20px',background:'rgba(0,0,0,0.3)',border:'1px solid var(--border2)',borderRadius:12,color:'var(--text)',fontSize:13,fontFamily:'monospace',lineHeight:1.7,outline:'none',resize:'none'}}
                placeholder={"PASTE THE WHOLE DAMN TABLE HERE...\n\nExample:\n1 George Russell Mercedes 1:30:11\n2 Kimi Antonelli Mercedes +2.2\n3 Charles Leclerc Ferrari +5.5\n..."} />
            </div>
          </div>

          {/* Detection Grid */}
          <div style={{maxHeight:500,overflowY:'auto',paddingRight:8}}>
            {matches.map((m, i) => (
              <div key={i}
                style={{display:'flex',alignItems:'center',gap:12,padding:'10px 14px',marginBottom:6,borderRadius:12,border:'1px solid ' + (m.driverId ? 'rgba(79,124,255,0.4)' : 'var(--border)'),background: m.driverId ? 'rgba(79,124,255,0.08)' : 'transparent',opacity: m.driverId ? 1 : 0.4,transition:'all 0.2s'}}>
                <div style={{width:36,height:36,borderRadius:8,background:'rgba(0,0,0,0.3)',display:'flex',alignItems:'center',justifyContent:'center',fontWeight:'900',fontSize:16,color:'#fff',fontStyle:'italic',flexShrink:0}}>#{m.pos}</div>
                <select value={m.driverId} onChange={e => manualSelect(m.pos, e.target.value)}
                  style={{flex:1,padding:'10px 12px',borderRadius:10,background:'rgba(0,0,0,0.3)',border:'1px solid var(--border2)',color:'#fff',fontWeight:'600',fontSize:13,outline:'none',cursor:'pointer'}}>
                  <option value="">-- NO DRIVER DETECTED --</option>
                  {drivers.map(d => (
                    <option key={d.id} value={d.id}>{d.driver_name}</option>
                  ))}
                </select>
                <div style={{fontSize:9,fontWeight:'900',textTransform:'uppercase',letterSpacing:'0.08em',color: m.driverId ? 'var(--blue)' : 'var(--text3)',whiteSpace:'nowrap'}}>{m.driverId ? 'Detected' : 'Standby'}</div>
              </div>
            ))}
          </div>
        </div>

        {/* Launch Button */}
        <div style={{marginTop:32,padding:24,background:'rgba(79,124,255,0.05)',border:'1px solid rgba(79,124,255,0.1)',borderRadius:16,display:'flex',flexDirection:'row',alignItems:'center',gap:24,flexWrap:'wrap'}}>
          <div style={{flex:1}}>
            <h3 style={{fontSize:18,fontWeight:'900',color:'#fff',textTransform:'uppercase',fontStyle:'italic'}}>Ready for Launch?</h3>
            <p style={{fontSize:13,color:'var(--text3)'}}>Double check the grid above. The system will calculate scores for all engineers once you hit the button.</p>
          </div>
          <button onClick={submitResults} disabled={submitting}
            style={{whiteSpace:'nowrap',padding:'18px 48px',background:'linear-gradient(135deg,var(--blue),var(--purple))',border:'none',borderRadius:14,color:'#fff',fontWeight:'900',fontSize:18,cursor:'pointer',boxShadow:'0 8px 32px rgba(79,124,255,0.2)',transition:'all 0.2s',display:'flex',alignItems:'center',gap:12}}>
            <I n="rocket" /> DEPLOY CLASSIFICATION
          </button>
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
        <h1 style={{fontSize:24,fontWeight:'800'}}><I n="rss" style={{color:'var(--orange)',marginRight:8}} />Paddock News</h1>
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
            <div key={post.id} className="card" style={{padding:20,borderLeft:'3px solid var(--orange)'}}>
              <div style={{display:'flex',justifyContent:'space-between',alignItems:'flex-start',marginBottom:10}}>
                <div>
                  <h2 style={{fontSize:18,fontWeight:'800',color:'var(--orange)',marginBottom:4}}>{post.title}</h2>
                  {post.race_name && (
                    <div style={{fontSize:12,color:'var(--text3)'}}>
                      <I n="flag-checkered" style={{marginRight:4}} />{post.race_name} - {post.country}
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
                          <strong style={{fontSize:11,color:'var(--orange)'}}>{c.username}</strong>
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

  if (!user && getPageName() !== 'leaderboard') {
    return (
      <>
        <Nav user={null} page={page} onNav={handleNav} onLogin={() => setShowLogin(true)} onLogout={() => {}} />
        <style>{`
          @keyframes wlcmFade{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
          @keyframes wlcmSlide{from{opacity:0;transform:translateX(30px)}to{opacity:1;transform:translateX(0)}}
        `}</style>
        <div style={{display:'flex',minHeight:'100vh',background:'var(--bg)'}}>
          {/* LEFT — Hero Image Panel */}
          <div style={{
            flex:'1.3',position:'relative',overflow:'hidden',display:'flex',
            alignItems:'center',justifyContent:'center',
            background:'linear-gradient(135deg,#0c0f16 0%,#111620 100%)',
            minHeight:'100vh'
          }}>
            {/* Abstract gradient mesh */}
            <div style={{
              position:'absolute',inset:0,
              background:'radial-gradient(ellipse at 20% 50%,rgba(124,58,237,0.12),transparent 60%),radial-gradient(ellipse at 80% 30%,rgba(79,124,255,0.08),transparent 50%),radial-gradient(ellipse at 50% 80%,rgba(251,146,60,0.06),transparent 50%)',
            }} />
            {/* Subtle grid pattern */}
            <div style={{
              position:'absolute',inset:0,
              backgroundImage:'linear-gradient(rgba(255,255,255,0.015) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,0.015) 1px,transparent 1px)',
              backgroundSize:'60px 60px'
            }} />
            {/* Brand content */}
            <div style={{position:'relative',zIndex:2,textAlign:'center',padding:'40px',maxWidth:480}}>
              <div style={{width:72,height:72,background:'linear-gradient(135deg,var(--purple),var(--blue))',borderRadius:20,display:'flex',alignItems:'center',justifyContent:'center',margin:'0 auto 28px',fontSize:32,color:'#fff',boxShadow:'0 8px 32px rgba(124,58,237,0.3)',animation:'wlcmFade 0.6s ease both'}}>
                <I n="flag-checkered" />
              </div>
              <h1 style={{fontSize:42,fontWeight:'900',color:'#fff',textTransform:'uppercase',letterSpacing:'-0.03em',lineHeight:1.05,marginBottom:12,animation:'wlcmFade 0.6s ease 0.1s both'}}>
                Paddock<br /><span style={{background:'linear-gradient(135deg,var(--purple2),var(--blue))',WebkitBackgroundClip:'text',WebkitTextFillColor:'transparent'}}>Picks</span>
              </h1>
              <p style={{color:'var(--text2)',fontSize:15,lineHeight:1.7,marginBottom:32,animation:'wlcmFade 0.6s ease 0.2s both'}}>
                The ultimate F1 prediction league. Pick your drivers,<br />beat your rivals, own the podium.
              </p>
              <div style={{display:'grid',gridTemplateColumns:'repeat(3,1fr)',gap:12,animation:'wlcmFade 0.6s ease 0.25s both'}}>
                {[{n:'24',l:'Races'},{n:'22',l:'Drivers'},{n:'11',l:'Teams'}].map((s,i) => (
                  <div key={i} style={{background:'rgba(255,255,255,0.03)',border:'1px solid var(--border)',borderRadius:12,padding:'14px 8px',textAlign:'center'}}>
                    <div style={{fontSize:24,fontWeight:'800',color:'var(--purple2)',lineHeight:1}}>{s.n}</div>
                    <div style={{fontSize:10,color:'var(--text3)',textTransform:'uppercase',letterSpacing:'0.06em',fontWeight:'600',marginTop:4}}>{s.l}</div>
                  </div>
                ))}
              </div>
              <div style={{marginTop:24,display:'flex',alignItems:'center',justifyContent:'center',gap:8,animation:'wlcmFade 0.6s ease 0.3s both'}}>
                <span style={{width:8,height:8,borderRadius:'50%',background:'var(--green)',boxShadow:'0 0 8px rgba(34,197,94,0.4)'}} />
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
                <div style={{width:36,height:36,background:'linear-gradient(135deg,var(--purple),var(--blue))',borderRadius:10,display:'flex',alignItems:'center',justifyContent:'center',fontSize:16,color:'#fff'}}>
                  <I n="flag-checkered" />
                </div>
                <span style={{fontWeight:'800',fontSize:16,color:'var(--text)',letterSpacing:'-0.02em'}}>PADDOCK PICKS</span>
              </div>

              <h2 style={{fontSize:28,fontWeight:'800',color:'#fff',marginBottom:6}}>
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
                <button className="btn btn-primary btn-lg btn-block" style={{marginTop:6,background:'linear-gradient(135deg,var(--purple),var(--blue))',padding:'14px 24px',borderRadius:10,fontSize:15}}>
                  Log In <I n="arrow-right" />
                </button>
              </form>

              <div style={{margin:'24px 0',display:'flex',alignItems:'center',gap:16}}>
                <div style={{flex:1,height:1,background:'var(--border)'}} />
                <span style={{fontSize:11,color:'var(--text3)',fontWeight:'600',textTransform:'uppercase',letterSpacing:'0.06em'}}>or</span>
                <div style={{flex:1,height:1,background:'var(--border)'}} />
              </div>

              <button className="btn btn-outline btn-lg btn-block" style={{background:'var(--card2)',borderColor:'var(--border2)',padding:'14px 24px',borderRadius:10,fontSize:14}} onClick={() => setShowLogin('signup')}>
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
      <Nav user={user} page={page} onNav={handleNav} onLogin={() => setShowLogin(true)} onLogout={() => setUser(null)} />
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
      <footer style={{padding:'24px 20px',borderTop:'1px solid var(--border)',marginTop:40,textAlign:'center'}}>
        <p style={{fontSize:11,color:'var(--text3)',fontWeight:'600',letterSpacing:'0.04em'}}>
          &copy; {new Date().getFullYear()} Paddock Picks &middot; Powered by <a href="https://www.scanerrific.com" target="_blank" rel="noopener noreferrer" style={{color:'var(--orange)',fontWeight:'700',textDecoration:'none'}}>Scanerrific</a>
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
