// Simple F1 Fantasy App - No Backend Needed!
// Uses localStorage to store data

// Sample 2026 Races
const races = [
    { id: 1, name: 'Bahrain Grand Prix', circuit: 'Bahrain International Circuit', date: '2026-03-01', status: 'upcoming' },
    { id: 2, name: 'Saudi Arabian Grand Prix', circuit: 'Jeddah Corniche Circuit', date: '2026-03-08', status: 'upcoming' },
    { id: 3, name: 'Australian Grand Prix', circuit: 'Albert Park Circuit', date: '2026-03-22', status: 'upcoming' },
];

// Sample Drivers
const drivers = [
    'Max Verstappen', 'Lewis Hamilton', 'Charles Leclerc', 'Lando Norris',
    'George Russell', 'Carlos Sainz', 'Fernando Alonso', 'Oscar Piastri',
    'Sergio Perez', 'Pierre Gasly', 'Esteban Ocon', 'Lance Stroll',
    'Yuki Tsunoda', 'Alexander Albon', 'Daniel Ricciardo', 'Nico Hulkenberg',
    'Valtteri Bottas', 'Zhou Guanyu', 'Kevin Magnussen', 'Logan Sargeant'
];

// Initialize App
function init() {
    checkLogin();
    loadRaces();
    loadLeaderboard();
    updateStats();
}

// Login System (Simple - just stores name)
function login() {
    const name = document.getElementById('playerName').value.trim();
    if (!name) {
        alert('Please enter your name');
        return;
    }
    
    localStorage.setItem('playerName', name);
    if (!localStorage.getItem('players')) {
        localStorage.setItem('players', JSON.stringify([]));
    }
    
    // Add player to leaderboard if new
    const players = JSON.parse(localStorage.getItem('players') || '[]');
    if (!players.find(p => p.name === name)) {
        players.push({ name, points: 0, races: 0 });
        localStorage.setItem('players', JSON.stringify(players));
    }
    
    checkLogin();
    document.getElementById('loginModal').classList.add('hidden');
    document.getElementById('playerName').value = '';
}

function logout() {
    localStorage.removeItem('playerName');
    checkLogin();
}

function showLogin() {
    document.getElementById('loginModal').classList.remove('hidden');
    document.getElementById('loginModal').classList.add('flex');
}

function checkLogin() {
    const name = localStorage.getItem('playerName');
    if (name) {
        document.getElementById('userName').textContent = name;
        document.getElementById('loginBtn').classList.add('hidden');
        document.getElementById('logoutBtn').classList.remove('hidden');
        document.getElementById('statsSection').classList.remove('hidden');
    } else {
        document.getElementById('userName').textContent = '';
        document.getElementById('loginBtn').classList.remove('hidden');
        document.getElementById('logoutBtn').classList.add('hidden');
        document.getElementById('statsSection').classList.add('hidden');
    }
}

// Load Races
function loadRaces() {
    const container = document.getElementById('racesList');
    container.innerHTML = races.map(race => `
        <div class="race-card-premium card-glass rounded-xl p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <h3 class="text-xl font-bold mb-1">${race.name}</h3>
                    <p class="text-gray-400 text-sm">${race.circuit}</p>
                </div>
                <div class="w-12 h-12 rounded-full bg-red-500/20 flex items-center justify-center flex-shrink-0 ml-4">
                    <i class="fas fa-flag-checkered text-red-400"></i>
                </div>
            </div>
            <div class="flex items-center text-gray-300 mb-4">
                <i class="fas fa-calendar mr-2"></i>
                <span>${new Date(race.date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}</span>
            </div>
            <button onclick="makePrediction(${race.id})" class="w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white text-center py-2 rounded-lg font-semibold transition">
                Make Prediction
            </button>
        </div>
    `).join('');
}

// Make Prediction
function makePrediction(raceId) {
    const name = localStorage.getItem('playerName');
    if (!name) {
        showLogin();
        return;
    }
    
    const race = races.find(r => r.id === raceId);
    if (!race) return;
    
    // Simple prediction modal
    let modal = `
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="card-glass rounded-2xl p-8 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <h2 class="text-2xl font-bold mb-4 gradient-text">${race.name}</h2>
                <p class="text-gray-400 mb-6">Predict the top 3 finishers:</p>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">1st Place</label>
                        <select id="pred1" class="w-full px-4 py-2 rounded-lg bg-white/10 border border-white/20 text-white">
                            <option value="">Select driver</option>
                            ${drivers.map(d => `<option value="${d}">${d}</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">2nd Place</label>
                        <select id="pred2" class="w-full px-4 py-2 rounded-lg bg-white/10 border border-white/20 text-white">
                            <option value="">Select driver</option>
                            ${drivers.map(d => `<option value="${d}">${d}</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">3rd Place</label>
                        <select id="pred3" class="w-full px-4 py-2 rounded-lg bg-white/10 border border-white/20 text-white">
                            <option value="">Select driver</option>
                            ${drivers.map(d => `<option value="${d}">${d}</option>`).join('')}
                        </select>
                    </div>
                </div>
                <div class="flex gap-4 mt-6">
                    <button onclick="savePrediction(${raceId})" class="flex-1 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white py-3 rounded-lg font-semibold transition">
                        Save Prediction
                    </button>
                    <button onclick="closeModal()" class="px-6 py-3 bg-white/10 hover:bg-white/20 text-white rounded-lg font-semibold transition">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modal);
}

function savePrediction(raceId) {
    const pred1 = document.getElementById('pred1').value;
    const pred2 = document.getElementById('pred2').value;
    const pred3 = document.getElementById('pred3').value;
    
    if (!pred1 || !pred2 || !pred3) {
        alert('Please select all three positions');
        return;
    }
    
    if (pred1 === pred2 || pred1 === pred3 || pred2 === pred3) {
        alert('Each driver can only be selected once');
        return;
    }
    
    const name = localStorage.getItem('playerName');
    const predictions = JSON.parse(localStorage.getItem('predictions') || '{}');
    predictions[`${name}_${raceId}`] = { raceId, pred1, pred2, pred3, name };
    localStorage.setItem('predictions', JSON.stringify(predictions));
    
    closeModal();
    
    // Show Lottie animation
    const lottieModal = document.getElementById('lottieModal');
    lottieModal.classList.add('active');
    
    // Hide animation after 2.5 seconds
    setTimeout(() => {
        lottieModal.classList.remove('active');
    }, 2500);
}

function closeModal() {
    const modal = document.querySelector('.fixed.inset-0.bg-black\\/50');
    if (modal) modal.remove();
}

// Load Leaderboard
function loadLeaderboard() {
    const players = JSON.parse(localStorage.getItem('players') || '[]');
    const name = localStorage.getItem('playerName');
    
    // Sort by points
    players.sort((a, b) => b.points - a.points);
    
    const container = document.getElementById('leaderboard');
    
    if (players.length === 0) {
        container.innerHTML = '<div class="p-12 text-center"><p class="text-gray-400">No players yet. Be the first!</p></div>';
        return;
    }
    
    let html = `
        <div class="p-6 border-b border-white/10">
            <h3 class="text-2xl font-bold flex items-center">
                <i class="fas fa-trophy text-yellow-500 mr-3"></i>
                Rankings
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-white/10">
                        <th class="text-left p-4 text-gray-400 font-semibold uppercase text-sm">Rank</th>
                        <th class="text-left p-4 text-gray-400 font-semibold uppercase text-sm">Player</th>
                        <th class="text-right p-4 text-gray-400 font-semibold uppercase text-sm">Points</th>
                        <th class="text-center p-4 text-gray-400 font-semibold uppercase text-sm">Races</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    players.forEach((player, index) => {
        const isCurrentUser = player.name === name;
        const rank = index + 1;
        const medal = rank === 1 ? '👑' : rank === 2 ? '🥈' : rank === 3 ? '🥉' : rank;
        
        html += `
            <tr class="border-b border-white/5 ${isCurrentUser ? 'bg-red-500/10' : ''} hover:bg-white/5 transition">
                <td class="p-4">
                    <div class="flex items-center">
                        <span class="text-xl">${medal}</span>
                        <span class="ml-2">${rank}</span>
                    </div>
                </td>
                <td class="p-4">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-red-500 to-red-700 flex items-center justify-center text-white font-bold mr-3">
                            ${player.name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <div class="font-semibold text-white">
                                ${player.name}
                                ${isCurrentUser ? '<span class="ml-2 text-xs bg-red-500/20 text-red-400 px-2 py-1 rounded">You</span>' : ''}
                            </div>
                        </div>
                    </div>
                </td>
                <td class="p-4 text-right">
                    <div class="text-xl font-black text-red-400">${player.points}</div>
                </td>
                <td class="p-4 text-center">
                    <div class="inline-flex items-center px-3 py-1 rounded-full bg-white/5 text-sm font-semibold">
                        ${player.races}
                    </div>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

// Update Stats
function updateStats() {
    const name = localStorage.getItem('playerName');
    if (!name) return;
    
    const players = JSON.parse(localStorage.getItem('players') || '[]');
    const player = players.find(p => p.name === name);
    
    if (player) {
        document.getElementById('totalPoints').textContent = player.points || 0;
        document.getElementById('racesCount').textContent = player.races || 0;
    }
}

// Initialize on load
document.addEventListener('DOMContentLoaded', init);

// Allow Enter key in login
document.addEventListener('keypress', (e) => {
    if (e.key === 'Enter' && document.getElementById('playerName') === document.activeElement) {
        login();
    }
});

