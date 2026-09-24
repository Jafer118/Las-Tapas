// gedeeld.js - kleine helperfuncties die op meerdere schermen gebruikt worden

// Pas dit pad aan als jouw backend-map ergens anders staat
const API_BASIS = '../backend/api';

async function authGuard() {
    const response = await fetch(`${API_BASIS}/auth.php?actie=me`, { credentials: 'same-origin' });
    const data = await response.json();
    if (!data.ingelogd) {
        const next = `${location.pathname.substring(location.pathname.lastIndexOf('/') + 1)}${location.search}`;
        location.href = `login.html?next=${encodeURIComponent(next)}`;
    } else if (data.gebruiker) {
        const avatar = document.querySelector('.db-avatar');
        if (avatar) {
            avatar.textContent = data.gebruiker.naam;
            avatar.title = 'Klik om uit te loggen';
            avatar.style.cursor = 'pointer';
            avatar.addEventListener('click', async () => {
                await fetch(`${API_BASIS}/auth.php?actie=logout`, { method: 'POST', credentials: 'same-origin' });
                location.href = 'login.html';
            }, { once: true });
        }
        if (!document.querySelector('.auth-uitloggen')) {
            const knop = document.createElement('button');
            knop.className = 'auth-uitloggen';
            knop.type = 'button';
            knop.textContent = 'Uitloggen';
            knop.style.cssText = 'position:fixed;right:18px;bottom:18px;z-index:20;padding:9px 14px;background:#8c2f39;color:#fbf4e6;border:0;border-radius:6px;font:700 0.85rem Lato,Arial,sans-serif;cursor:pointer;box-shadow:0 3px 10px rgba(0,0,0,.28)';
            knop.addEventListener('click', async () => {
                await fetch(`${API_BASIS}/auth.php?actie=logout`, { method: 'POST', credentials: 'same-origin' });
                location.href = 'login.html';
            });
            document.body.appendChild(knop);
        }
    }
    return data;
}

async function apiGet(pad) {
    const response = await fetch(`${API_BASIS}/${pad}`, { credentials: 'same-origin' });
    if (response.status === 401) { await authGuard(); return { succes: false }; }
    return response.json();
}

async function apiPost(pad, data) {
    const response = await fetch(`${API_BASIS}/${pad}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify(data),
    });
    if (response.status === 401) { await authGuard(); return { succes: false }; }
    return response.json();
}

authGuard();

function formatTijd(datumString) {
    const datum = new Date(datumString.replace(' ', 'T'));
    return datum.toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

function formatEuro(bedrag) {
    return '€' + Number(bedrag).toFixed(2).replace('.', ',');
}
