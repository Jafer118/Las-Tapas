// Shared helpers used by multiple screens.

// Update this path if the backend directory is located elsewhere.
const API_BASE = '../backend/api';
const START_PAGE = '../../Las%20Tapas klanten systeem/Frontend/index.html';
let csrfToken = '';

function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    })[character]);
}

function showApiError(message) {
    const main = document.querySelector('main');
    if (!main) return;

    let errorBanner = document.getElementById('api-error-banner');
    if (!errorBanner) {
        errorBanner = document.createElement('div');
        errorBanner.id = 'api-error-banner';
        errorBanner.className = 'melding fout';
        errorBanner.setAttribute('role', 'alert');
        main.prepend(errorBanner);
    }
    errorBanner.textContent = message;
}

function addSystemNavigation() {
    const subnav = document.querySelector('.subnav');
    if (subnav) {
        const homeLink = document.createElement('a');
        homeLink.href = START_PAGE;
        homeLink.className = 'home-link';
        homeLink.textContent = '← Startpagina';
        subnav.prepend(homeLink);

        const stockLink = document.createElement('a');
        stockLink.href = '../../voorraad-las-tapas/index.html';
        stockLink.textContent = 'Voorraadbeheer';
        subnav.appendChild(stockLink);
    }

    const dashboardNav = document.querySelector('.db-navbar');
    if (dashboardNav) {
        const homeLink = document.createElement('a');
        homeLink.className = 'db-navitem home-link';
        homeLink.href = START_PAGE;
        homeLink.innerHTML = '<span class="icoon" aria-hidden="true">⌂</span><span>Start</span>';
        dashboardNav.prepend(homeLink);

        const stockLink = document.createElement('a');
        stockLink.className = 'db-navitem';
        stockLink.href = '../../voorraad-las-tapas/index.html';
        stockLink.innerHTML = '<span class="icoon" aria-hidden="true">▤</span><span>Voorraad</span>';
        dashboardNav.appendChild(stockLink);
    }
}

addSystemNavigation();

async function requireAuthentication() {
    const response = await fetch(`${API_BASE}/auth.php?actie=me`, { credentials: 'same-origin' });
    const sessionData = await response.json();
    csrfToken = sessionData.csrf_token || '';
    if (!sessionData.ingelogd) {
        const next = `${location.pathname.substring(location.pathname.lastIndexOf('/') + 1)}${location.search}`;
        location.href = `login.html?next=${encodeURIComponent(next)}`;
    } else if (sessionData.gebruiker) {
        const avatar = document.querySelector('.db-avatar');
        if (avatar) {
            avatar.textContent = sessionData.gebruiker.naam;
            avatar.title = 'Klik om uit te loggen';
            avatar.style.cursor = 'pointer';
            avatar.addEventListener('click', async () => {
                await fetch(`${API_BASE}/auth.php?actie=logout`, {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': csrfToken },
                    credentials: 'same-origin',
                });
                location.href = START_PAGE;
            }, { once: true });
        }
        if (!document.querySelector('.auth-uitloggen')) {
            const logoutButton = document.createElement('button');
            logoutButton.className = 'auth-uitloggen';
            logoutButton.type = 'button';
            logoutButton.textContent = 'Uitloggen';
            logoutButton.style.cssText = 'position:fixed;right:18px;bottom:18px;z-index:20;padding:9px 14px;background:var(--kleur-wijn);color:var(--kleur-tekst);border:0;border-radius:6px;font:700 0.85rem var(--font-tekst);cursor:pointer;box-shadow:0 3px 10px rgba(0,0,0,.28)';
            logoutButton.addEventListener('click', async () => {
                await fetch(`${API_BASE}/auth.php?actie=logout`, {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': csrfToken },
                    credentials: 'same-origin',
                });
                location.href = START_PAGE;
            });
            document.body.appendChild(logoutButton);
        }
    }
    return sessionData;
}

async function apiGet(endpointPath) {
    return requestJson(endpointPath, { credentials: 'same-origin' });
}

async function apiPost(endpointPath, data) {
    return requestJson(endpointPath, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
        credentials: 'same-origin',
        body: JSON.stringify(data),
    });
}

async function requestJson(endpointPath, options) {
    try {
        const response = await fetch(`${API_BASE}/${endpointPath}`, options);
        if (response.status === 401) {
            await requireAuthentication();
            return { succes: false, fout: 'Inloggen is vereist.' };
        }

        const responseData = await response.json();
        if (!responseData || typeof responseData !== 'object' || Array.isArray(responseData)) {
            throw new Error('The API returned an invalid response.');
        }
        if (!response.ok && !responseData.fout) {
            responseData.fout = 'De aanvraag kon niet worden verwerkt.';
        }
        if (response.ok) {
            document.getElementById('api-error-banner')?.remove();
        } else if (response.status >= 500) {
            showApiError(responseData.fout);
        }
        return responseData;
    } catch {
        showApiError('Verbinding met de server mislukt. Controleer de verbinding en probeer opnieuw.');
        return { succes: false, fout: 'De server is tijdelijk niet bereikbaar.' };
    }
}

requireAuthentication();

function formatTime(dateTime) {
    const date = new Date(dateTime.replace(' ', 'T'));
    return date.toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

function formatCurrency(amount) {
    return '€' + Number(amount).toFixed(2).replace('.', ',');
}
