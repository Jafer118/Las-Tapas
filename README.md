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
