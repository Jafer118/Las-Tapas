const API_URL = 'voorraad_api.php';
let items = [];
let selectedCategory = 'all';
let csrfToken = '';

function formatMoney(value) {
    return new Intl.NumberFormat('nl-NL', { style: 'currency', currency: 'EUR', maximumFractionDigits: 2 }).format(value);
}

function categoryLabel(category) {
    return category === 'bar' ? 'Bar' : 'Keuken';
}

function isLow(item) {
    return item.stock <= item.minimum;
}

function escapeHtml(value) {
    return String(value).replace(/[&<>'"]/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[character]));
}

async function apiRequest(method = 'GET', body = null) {
    const options = { method, headers: { 'Accept': 'application/json' } };
    if (method !== 'GET') {
        if (!csrfToken) throw new Error('Beveiligingstoken ontbreekt. Vernieuw de pagina.');
        options.headers['X-CSRF-Token'] = csrfToken;
    }
    if (body) {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(body);
    }
    const response = await fetch(API_URL, options);
    let data;
    try {
        data = await response.json();
    } catch {
        throw new Error('De server heeft een ongeldig antwoord teruggestuurd.');
    }
    if (!data || typeof data !== 'object' || Array.isArray(data)) {
        throw new Error('De server heeft een ongeldig antwoord teruggestuurd.');
    }
    if (response.status === 401) {
        window.location.href = 'login.php';
        throw new Error('Inloggen vereist.');
    }
    if (!response.ok) throw new Error(data.error || `API-fout (${response.status})`);
    if (data.csrfToken) csrfToken = data.csrfToken;
    return data;
}

async function loadItems(showMessage = false) {
    try {
        const data = await apiRequest();
        if (!Array.isArray(data.items)) {
            throw new Error('De voorraadgegevens hebben een ongeldig formaat.');
        }
        items = data.items;
        render();
        if (showMessage) showToast('Voorraad is gesynchroniseerd.');
    } catch (error) {
        showToast(error.message);
        console.error(error);
    }
}

function visibleItems() {
    const query = document.querySelector('#search-input').value.trim().toLowerCase();
    const lowOnly = document.querySelector('#low-only').checked;
    return items.filter(item => (selectedCategory === 'all' || item.category === selectedCategory)
        && item.name.toLowerCase().includes(query)
        && (!lowOnly || isLow(item)));
}

function render() {
    const lowItems = items.filter(isLow);
    const healthyPercentage = items.length ? Math.round(((items.length - lowItems.length) / items.length) * 100) : 0;
    const inventoryValue = items.reduce((total, item) => total + item.stock * item.price, 0);
    document.querySelector('#stat-total').textContent = items.length;
    document.querySelector('#stat-low').textContent = lowItems.length;
    document.querySelector('#stat-healthy').textContent = `${healthyPercentage}%`;
    document.querySelector('#stat-value').textContent = formatMoney(inventoryValue);
    document.querySelector('#nav-low-count').textContent = lowItems.length;
    document.querySelector('#all-count').textContent = items.length;
    document.querySelector('#kitchen-count').textContent = items.filter(item => item.category === 'keuken').length;
    document.querySelector('#bar-count').textContent = items.filter(item => item.category === 'bar').length;

    const filtered = visibleItems();
    document.querySelector('#inventory-body').innerHTML = filtered.map(item => `
        <tr data-id="${item.id}">
            <td><div class="item-name"><span class="item-thumb ${item.category}">${item.category === 'bar' ? '◌' : '✦'}</span><span><strong>${escapeHtml(item.name)}</strong><small>SKU-${String(item.id).padStart(4, '0')}</small></span></div></td>
            <td><span class="category-pill ${item.category}">${categoryLabel(item.category)}</span></td>
            <td><div class="stock-control"><button class="step-button" data-action="decrease" type="button" aria-label="Voorraad verlagen">−</button><strong class="stock-number ${isLow(item) ? 'low-number' : ''}">${item.stock}</strong><button class="step-button" data-action="increase" type="button" aria-label="Voorraad verhogen">+</button></div></td>
            <td><span class="minimum-value">${item.minimum} stuks</span></td>
            <td><span class="status-pill ${isLow(item) ? 'low' : 'good'}"><i></i>${isLow(item) ? 'Bijvullen' : 'Op niveau'}</span></td>
            <td class="value-cell">${formatMoney(item.stock * item.price)}</td>
        </tr>`).join('');
    document.querySelector('#result-count').textContent = `${filtered.length} ${filtered.length === 1 ? 'artikel' : 'artikelen'}`;
    document.querySelector('#empty-state').hidden = filtered.length > 0;
    document.querySelector('#last-sync').textContent = new Date().toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' });
}

function showToast(message) {
    const toast = document.querySelector('#toast');
    toast.textContent = message;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2800);
}

async function changeStock(id, delta) {
    try {
        await apiRequest('PATCH', { id, delta });
        await loadItems();
    } catch (error) {
        showToast(error.message);
    }
}

async function handleInventoryClick(event) {
    const button = event.target.closest('button[data-action]');
    if (!button) return;

    const row = button.closest('tr[data-id]');
    if (!row) return;

    const id = Number(row.dataset.id);
    if (!Number.isInteger(id) || id < 1) return;

    const stockDelta = button.dataset.action === 'increase'
        ? 1
        : button.dataset.action === 'decrease'
            ? -1
            : null;

    if (stockDelta !== null) {
        await changeStock(id, stockDelta);
    }
}

function handleCategoryFilterClick(event) {
    const button = event.currentTarget;
    selectedCategory = button.dataset.category;

    document.querySelectorAll('.filter-button').forEach(filter => {
        filter.classList.toggle('active', filter === button);
    });

    render();
}

async function handleLogout(event) {
    const button = event.currentTarget;
    button.disabled = true;

    try {
        if (!csrfToken) throw new Error('Beveiligingstoken ontbreekt. Vernieuw de pagina.');

        const response = await fetch('logout.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-Token': csrfToken,
            },
        });

        if (!response.ok) throw new Error('Uitloggen is mislukt.');
        window.location.href = '../projectinformatie/index.html';
    } catch (error) {
        button.disabled = false;
        showToast(error.message);
    }
}

const backdrop = document.querySelector('#modal-backdrop');

function toggleModal(open) {
    backdrop.hidden = !open;

    if (open) {
        backdrop.querySelector('input').focus();
    }
}

async function handleAddItemSubmit(event) {
    event.preventDefault();
    const form = new FormData(event.target);

    try {
        await apiRequest('POST', {
            name: String(form.get('name') || '').trim(),
            category: form.get('category'),
            stock: Number(form.get('stock')),
            minimum: Number(form.get('minimum')),
            price: Number(form.get('price')),
        });
        await loadItems();
        event.target.reset();
        toggleModal(false);
        showToast('Nieuw artikel toegevoegd aan MySQL.');
    } catch (error) {
        showToast(error.message);
    }
}

function escapeCsvValue(value) {
    const text = String(value);
    const spreadsheetSafeText = /^[\t\r ]*[=+\-@]/.test(text)
        ? `'${text}`
        : text;

    return `"${spreadsheetSafeText.replace(/"/g, '""')}"`;
}

function exportLowStockItems() {
    const lowItems = items.filter(isLow);
    const rows = [
        ['Las Tapas - Inkooplijst'],
        [`Gegenereerd: ${new Date().toLocaleString('nl-NL')}`],
        [],
        ['Artikel', 'Categorie', 'Huidige voorraad', 'Minimum', 'Bijbestellen'],
        ...lowItems.map(item => [
            item.name,
            categoryLabel(item.category),
            item.stock,
            item.minimum,
            Math.max(item.minimum - item.stock, 0),
        ]),
    ];
    const csv = rows
        .map(row => row.map(escapeCsvValue).join(';'))
        .join('\r\n');
    const link = document.createElement('a');
    const downloadUrl = URL.createObjectURL(
        new Blob(['\uFEFF', csv], { type: 'text/csv;charset=utf-8' })
    );

    link.href = downloadUrl;
    link.download = 'las-tapas-inkooplijst.csv';
    link.click();

    window.setTimeout(() => URL.revokeObjectURL(downloadUrl), 1000);
}

function initializeInventoryDashboard() {
    document.querySelector('#inventory-body').addEventListener('click', handleInventoryClick);
    document.querySelector('#search-input').addEventListener('input', render);
    document.querySelector('#low-only').addEventListener('change', render);
    document.querySelectorAll('.filter-button').forEach(button => {
        button.addEventListener('click', handleCategoryFilterClick);
    });
    document.querySelector('#refresh-button').addEventListener('click', () => loadItems(true));
    document.querySelector('#logout-button').addEventListener('click', handleLogout);
    document.querySelector('#open-add').addEventListener('click', () => toggleModal(true));
    document.querySelector('#close-modal').addEventListener('click', () => toggleModal(false));
    document.querySelector('#cancel-modal').addEventListener('click', () => toggleModal(false));
    backdrop.addEventListener('click', event => {
        if (event.target === backdrop) {
            toggleModal(false);
        }
    });
    document.querySelector('#add-form').addEventListener('submit', handleAddItemSubmit);
    document.querySelector('#export-button').addEventListener('click', exportLowStockItems);
    document.querySelector('#today-label').textContent = new Intl.DateTimeFormat(
        'nl-NL',
        { weekday: 'long', day: 'numeric', month: 'long' }
    ).format(new Date());

    loadItems();
    setInterval(() => loadItems(), 30000);
}

initializeInventoryDashboard();
