# Las Tapas — Digitaal bestel- en kassasysteem (prototype)

Opdracht: Gilde DevOps Solutions — MBO4 Software Development
Opdrachtgever: Maria & Marcus, restaurant Las Tapas

Dit is een werkend prototype dat papieren bestelbonnetjes vervangt door een
digitaal bestel-, keuken/bar- en kassasysteem.

## Mapstructuur

```
las-tapas/
├── database/
│   └── las_tapas.sql          -> databaseschema + voorbeelddata
├── backend/
│   ├── db.php                 -> databaseverbinding (PDO)
│   └── api/
│       ├── gerechten_ophalen.php
│       ├── tafels_ophalen.php
│       ├── bestelling_toevoegen.php   -> bestelling opslaan + voorraad verlagen
│       ├── keuken_bestellingen.php    -> live data voor keukenscherm
│       ├── bar_bestellingen.php       -> live data voor barscherm
│       ├── status_bijwerken.php       -> orderregel status wijzigen
│       ├── rekening.php               -> totaalrekening per tafel
│       └── afrekenen.php              -> tafel afrekenen/sluiten
└── frontend/
    ├── css/stijl.css
    ├── js/gedeeld.js
    ├── bestellen.html          -> bestelscherm (kies tafel + gerechten)
    ├── keuken.html              -> live keukenscherm
    ├── bar.html                 -> live barscherm
    ├── kassa.html                -> kassascherm (rekening + afrekenen)
    └── privacy.html               -> AVG/privacy-pagina
```

## Installatie (lokaal testen, bv. met XAMPP/MAMP)

1. **Database aanmaken**
   Open phpMyAdmin (of de mysql command line) en importeer `database/las_tapas.sql`.
   Dit maakt de database `las_tapas` aan met tabellen én voorbeelddata (tafels en gerechten).

2. **Backend configureren**
   Open `backend/db.php` en pas zo nodig `$DB_HOST`, `$DB_USER` en `$DB_PASS` aan
   voor jouw lokale omgeving (bij standaard XAMPP is dit meestal al goed: user
   `root`, wachtwoord leeg).

3. **Bestanden op de webserver zetten**
   Zet de hele map `las-tapas/` in de `htdocs`-map van XAMPP (of `www` bij MAMP).

4. **Openen in de browser**
   - Bestellen: `http://localhost/las-tapas/frontend/bestellen.html`
   - Keukenscherm: `http://localhost/las-tapas/frontend/keuken.html`
   - Barscherm: `http://localhost/las-tapas/frontend/bar.html`
   - Kassa: `http://localhost/las-tapas/frontend/kassa.html`
   - AVG/Privacy: `http://localhost/las-tapas/frontend/privacy.html`

## Werking in het kort

1. Op **bestellen.html** kies je een tafel en klik je gerechten/drankjes bij elkaar.
   Bij versturen slaat `bestelling_toevoegen.php` de orderregels op **en**
   verlaagt automatisch de voorraad (binnen één databasetransactie, dus het
   gaat altijd samen goed of samen fout — nooit half).
2. **keuken.html** en **bar.html** halen elke 5 seconden de openstaande
   orderregels op (gefilterd op categorie) en tonen ze per tafel. Personeel
   kan een regel op "bereid" en daarna "geserveerd" zetten.
3. **kassa.html** toont alle bezette tafels. Bij het kiezen van een tafel wordt
   de rekening opgebouwd uit de orderregels (`rekening.php`) en kan de
   bestelling worden afgesloten met **Afrekenen** (`afrekenen.php`), waarna de
   tafel weer vrij komt.
4. **Bonnetje (PDF)**: op de kassapagina kun je altijd op **"Bonnetje bekijken
   / printen"** klikken. Dit opent `bon.html` — een nette, smalle kassabon-lay-out.
   Klik daar op **"Print / opslaan als PDF"**: in het printvenster van de
   browser kies je als "printer" de optie **"Opslaan als PDF"** (Chrome/Edge)
   of **"Microsoft Print to PDF"** (Windows), en je krijgt een echt
   `.pdf`-bestand van het bonnetje. Dit werkt zowel vóór het afrekenen
   (tussentijds bonnetje, via `bon.html?tafel_id=X`) als erna (definitief
   bonnetje van een afgesloten rekening, via `bon.html?bestelling_id=X`).

5. **Echte PDF-download (zonder printdialoog)**: naast de printknop is er nu
   ook een knop **"PDF downloaden"**. Deze roept `backend/api/bon_pdf.php`
   aan, dat met pure PHP (geen externe library!) een écht `.pdf`-bestand
   genereert en direct teruggeeft als download.

6. **Bonnetje naar de klant sturen** (via Gmail, in 2 stappen):
   - Bij het **bestellen** (`bestellen.html`) kan de klant optioneel een
     e-mailadres invullen — dit wordt opgeslagen bij de bestelling en na het
     afrekenen automatisch ingevuld in het mailveld.
   - Na het **afrekenen** (`kassa.html`) verschijnt een blokje met 2 stappen:
     1. **PDF downloaden** — genereert en downloadt het bonnetje als
        `.pdf`-bestand.
     2. **Openen in Gmail** — opent Gmail in een nieuw tabblad met de
        ontvanger, het onderwerp en de berichttekst al ingevuld. Je sleept
        daar de gedownloade PDF in als bijlage en klikt op Verzenden.

   **Waarom niet volledig automatisch?** Browsers mogen om
   veiligheidsredenen geen bestanden van je computer automatisch aan een
   e-mail koppelen — anders zou elke website ongemerkt je bestanden kunnen
   versturen. Volledig automatisch verzenden zou een mailserver of
   SMTP-account vereisen (met opgeslagen wachtwoord in de code). Voor een
   restaurant-kassasysteem is deze aanpak prima werkbaar: het scheelt de
   medewerker het handmatig overtypen van adres, onderwerp en tekst.

7. **Aantal personen & tafelcapaciteit**:
   - Elke tafel heeft nu een vaste **capaciteit** (max. aantal personen),
     ingesteld in de database (kolom `capaciteit` in `tafels`).
   - Bij het **bestellen** (`bestellen.html`) kies je eerst een tafel — het
     maximumaantal personen wordt er meteen bij getoond — en vul je het
     **aantal personen** in. Vul je meer personen in dan de tafel aankan,
     dan krijg je direct een waarschuwing en weigert de server het
     versturen van de bestelling (dubbele controle: in de browser én in
     `backend/api/bestelling_toevoegen.php`).
   - Het aantal personen wordt opgeslagen bij de bestelling en is zichtbaar
     op `overzicht.html` (tafelkaart), `kassa.html` (rekening) en op het
     bonnetje (`bon.html` + PDF).

   **Migratie (alleen als je de database al eerder importeerde):** voer de
   twee ALTER-regels onderaan `las_tapas.sql` uit (kolom `capaciteit` bij
   `tafels`, kolom `aantal_personen` bij `bestellingen`), en pas daarna
   optioneel de capaciteit per tafel aan met de voorbeeld-UPDATE-regels.

## Mogelijke uitbreidingen (voor een hogere score / doorontwikkeling)

- Inloggen voor personeel (authenticatie/autorisatie), zodat niet iedereen bij
  de kassa/voorraad kan.
- Extra kassafunctie: rekening splitsen of kortingen toepassen.
- Notificatie/geluid op het keuken-/barscherm bij een nieuwe bestelling
  (bijv. met WebSockets i.p.v. elke 5 sec. verversen).
- Voorraadwaarschuwing wanneer een gerecht bijna op is.
- Exporteren van de dagomzet (bijv. naar CSV) voor de administratie.

## AVG & Privacy

Zie `frontend/privacy.html` voor de privacyverklaring die hoort bij dit
systeem. Deze legt uit welke gegevens worden opgeslagen (uitsluitend
operationele bestelgegevens, geen persoonsgegevens van gasten), hoe lang deze
bewaard worden en hoe de toegang beveiligd is geregeld.

## Officiële menukaart & plattegrond (belangrijke update)

De database is bijgewerkt met de **officiële menukaart** (55 gerechten in 5
categorieën: Frías, Calientes, Especialidades, Postres, Bebidas) en de
**officiële plattegrond** (26 tafels: 10 op de begane grond, 16 op de eerste
verdieping, elk met plaats voor 4 personen).

**Let op — prijzen zijn nog placeholders.** De aangeleverde menukaart bevatte
geen prijzen. Er staan nu realistische standaardprijzen in (gebaseerd op
gangbare tapasbar-prijzen), zodat het systeem meteen te testen is. Pas de
echte prijzen aan via phpMyAdmin → tabel `gerechten` → kolom `prijs`, of met
een UPDATE-query, bijvoorbeeld:
```sql
UPDATE gerechten SET prijs = 6.95 WHERE naam = 'Gambas al ajillo';
```

**Twee soorten indeling, niet met elkaar te verwarren:**
- `categorie` (keuken/bar) bepaalt **wie het bereidt** — stuurt het gerecht
  naar `keuken.html` of `bar.html`.
- `menugroep` (Frías/Calientes/Especialidades/Postres/Bebidas) bepaalt **waar
  het op de menukaart staat** — gebruikt door de tabbladen op
  `bestellen.html` en de groepering op `voorraad.html`.

**Nieuw op de bestelpagina:** de menukaart is nu opgedeeld in tabbladen per
categorie (net als op een echte kaart), met een korte beschrijving per
gerecht.

**Migreren van een bestaande database:** de nieuwste ALTER-regels staan
onderaan `las_tapas.sql`. Omdat de menukaart en plattegrond zo grondig
veranderd zijn, is het meestal simpeler om de hele database te droppen en
`las_tapas.sql` in zijn geheel opnieuw te importeren — zie de instructie
onderaan dat bestand.
