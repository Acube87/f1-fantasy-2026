/* ─── PADDOCK PICKS — Premium Interactive Effects ─── */

document.addEventListener('DOMContentLoaded', function() {

  // ─── Card 3D Tilt ───
  var tiltCards = document.querySelectorAll('.hero-display, .promo-card');
  tiltCards.forEach(function(card) {
    card.addEventListener('mousemove', function(e) {
      var rect = card.getBoundingClientRect();
      var x = (e.clientX - rect.left) / rect.width;
      var y = (e.clientY - rect.top) / rect.height;
      var tiltX = (y - 0.5) * -6;
      var tiltY = (x - 0.5) * 6;
      card.style.transform = 'perspective(1000px) rotateX(' + tiltX + 'deg) rotateY(' + tiltY + 'deg)';
      card.style.transition = 'transform 0.1s ease';
    });
    card.addEventListener('mouseleave', function() {
      card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0)';
      card.style.transition = 'transform 0.5s cubic-bezier(0.16,1,0.3,1)';
    });
  });

  // ─── Parallax Hero Background ───
  var heroDisplay = document.querySelector('.hero-display');
  if (heroDisplay) {
    var heroBg = heroDisplay.querySelector('.hero-bg');
    if (heroBg) {
      heroDisplay.addEventListener('mousemove', function(e) {
        var rect = heroDisplay.getBoundingClientRect();
        var x = ((e.clientX - rect.left) / rect.width - 0.5) * 8;
        var y = ((e.clientY - rect.top) / rect.height - 0.5) * 8;
        heroBg.style.transform = 'scale(1.08) translate(' + x + 'px, ' + y + 'px)';
      });
      heroDisplay.addEventListener('mouseleave', function() {
        heroBg.style.transform = 'scale(1) translate(0,0)';
      });
    }
  }

  // ─── Smooth SPA-like transitions ───
  var navLinks = document.querySelectorAll('[data-nav-link]');
  navLinks.forEach(function(link) {
    link.addEventListener('click', function(e) {
      var href = link.getAttribute('href');
      if (!href || href === '#' || href.includes('logout') || href.startsWith('http')) return;
      e.preventDefault();
      document.body.style.opacity = '0';
      document.body.style.transition = 'opacity 0.15s ease';
      setTimeout(function() { window.location.href = href; }, 150);
    });
  });

  // ─── Scroll Reveal ───
  var reveals = document.querySelectorAll('.reveal');
  if (reveals.length) {
    var obs = new IntersectionObserver(function(entries) {
      entries.forEach(function(e) {
        if (e.isIntersecting) e.target.classList.add('visible');
      });
    }, { threshold: 0.1 });
    reveals.forEach(function(el) { obs.observe(el); });
  }

  // ─── Animate in staggered items ───
  var staggerGroups = document.querySelectorAll('.anim-stagger');
  staggerGroups.forEach(function(group) {
    var children = Array.from(group.children);
    children.forEach(function(child, i) {
      setTimeout(function() {
        child.style.opacity = '1';
        child.style.animation = 'fade-up 0.5s cubic-bezier(0.16,1,0.3,1) forwards';
      }, i * 60);
    });
  });

  // ─── Countdown Ring Effect ───
  var rings = document.querySelectorAll('.hero-countdown-ring .progress');
  rings.forEach(function(ring) {
    var deadline = parseInt(ring.getAttribute('data-deadline'));
    if (!deadline) return;
    var circumference = 213.6;
    var cdEl = document.getElementById('cd-center');
    
    function tick() {
      var left = deadline - Date.now();
      if (left <= 0) {
        ring.style.strokeDashoffset = '0';
        if (cdEl) cdEl.textContent = 'Locked';
        return;
      }
      var d = Math.floor(left / 86400000);
      var h = Math.floor((left % 86400000) / 3600000);
      var m = Math.floor((left % 3600000) / 60000);
      var s = Math.floor((left % 60000) / 1000);
      if (cdEl) cdEl.textContent = d > 0 ? d+'d '+h+'h' : h > 0 ? h+'h '+m+'m' : m > 0 ? m+'m' : s+'s';
      var maxW = 7 * 24 * 60 * 60 * 1000;
      var p = Math.min(Math.max(((maxW - left) / maxW), 0), 1);
      ring.style.strokeDashoffset = (circumference * (1 - p)).toFixed(1);
    }
    tick();
    setInterval(tick, 1000);
  });

  // ─── Graph curve animation ───
  var graphSvg = document.querySelector('.graph-svg path:first-child');
  if (graphSvg) {
    var length = graphSvg.getTotalLength();
    graphSvg.style.strokeDasharray = length;
    graphSvg.style.strokeDashoffset = length;
    graphSvg.style.transition = 'stroke-dashoffset 2s cubic-bezier(0.16,1,0.3,1)';
    setTimeout(function() { graphSvg.style.strokeDashoffset = '0'; }, 300);
  }

  // ─── Wallet points display ───
  var walletEl = document.querySelector('.topnav-wallet-value');
  if (walletEl && window.f1WalletPoints) {
    walletEl.textContent = window.f1WalletPoints;
  }

});
