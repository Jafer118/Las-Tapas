const API_URL = 'voorraad_api.php';
let items = [];
let selectedCategory = 'all';

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
    if (body) {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(body);
    }
    const response = await fetch(API_URL, options);
    const data = await response.json().catch(() => ({}));
    if (response.status === 401) {
        window.location.href = 'login.php';
        throw new Error('Inloggen vereist.');
    }
    if (!response.ok) throw new Error(data.error || `API-fout (${response.status})`);
    return data;
}

async function loadItems(showMessage = false) {
    try {
        const data = await apiRequest();
        items = data.items || [];
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
            <td><button class="row-menu" data-action="archive" type="button" title="Artikel archiveren" aria-label="${escapeHtml(item.name)} archiveren">•••</button></td>
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

document.querySelector('#inventory-body').addEventListener('click', async event => {
    const button = event.target.closest('button');
    if (!button) return;
    const row = button.closest('tr');
    const id = Number(row.dataset.id);
    if (button.dataset.action === 'increase') await changeStock(id, 1);
    if (button.dataset.action === 'decrease') await changeStock(id, -1);
    if (button.dataset.action === 'archive') {
        try {
            await apiRequest('DELETE', { id });
            await loadItems();
            showToast('Artikel is gearchiveerd.');
        } catch (error) {
            showToast(error.message);
        }
    }
});

document.querySelector('#search-input').addEventListener('input', render);
document.querySelector('#low-only').addEventListener('change', render);
document.querySelectorAll('.filter-button').forEach(button => button.addEventListener('click', () => {
    selectedCategory = button.dataset.category;
    document.querySelectorAll('.filter-button').forEach(filter => filter.classList.toggle('active', filter === button));
    render();
}));

document.querySelector('#refresh-button').addEventListener('click', () => loadItems(true));
const backdrop = document.querySelector('#modal-backdrop');
function toggleModal(open) { backdrop.hidden = !open; if (open) backdrop.querySelector('input').focus(); }
document.querySelector('#open-add').addEventListener('click', () => toggleModal(true));
document.querySelector('#close-modal').addEventListener('click', () => toggleModal(false));
document.querySelector('#cancel-modal').addEventListener('click', () => toggleModal(false));
backdrop.addEventListener('click', event => { if (event.target === backdrop) toggleModal(false); });

document.querySelector('#add-form').addEventListener('submit', async event => {
    event.preventDefault();
    const form = new FormData(event.target);
    try {
        await apiRequest('POST', { name: form.get('name').trim(), category: form.get('category'), stock: Number(form.get('stock')), minimum: Number(form.get('minimum')), price: Number(form.get('price')) });
        await loadItems();
        event.target.reset();
        toggleModal(false);
        showToast('Nieuw artikel toegevoegd aan MySQL.');
    } catch (error) {
        showToast(error.message);
    }
});

document.querySelector('#export-button').addEventListener('click', () => {
    const lowItems = items.filter(isLow);
    const lines = ['Las Tapas - Inkooplijst', `Gegenereerd: ${new Date().toLocaleString('nl-NL')}`, '', 'Artikel;Categorie;Huidige voorraad;Minimum;Bijbestellen'];
    lowItems.forEach(item => lines.push(`${item.name};${categoryLabel(item.category)};${item.stock};${item.minimum};${Math.max(item.minimum - item.stock, 0)}`));
    const link = document.createElement('a');
    link.href = URL.createObjectURL(new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8' }));
    link.download = 'las-tapas-inkooplijst.csv';
    link.click();
    URL.revokeObjectURL(link.href);
});

document.querySelector('#today-label').textContent = new Intl.DateTimeFormat('nl-NL', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date());
loadItems();
setInterval(() => loadItems(), 30000);
