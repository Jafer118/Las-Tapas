// gedeeld.js - kleine helperfuncties die op meerdere schermen gebruikt worden

// Pas dit pad aan als jouw backend-map ergens anders staat
const API_BASIS = '../backend/api';

async function apiGet(pad) {
    const response = await fetch(`${API_BASIS}/${pad}`);
    return response.json();
}

async function apiPost(pad, data) {
    const response = await fetch(`${API_BASIS}/${pad}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
    });
    return response.json();
}

function formatTijd(datumString) {
    const datum = new Date(datumString.replace(' ', 'T'));
    return datum.toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

function formatEuro(bedrag) {
    return '€' + Number(bedrag).toFixed(2).replace('.', ',');
}
