<?php
/**
 * pdf_bon.php
 *
 * Genereert een echt, geldig PDF-bestand voor een kassabon — volledig met
 * pure PHP, zonder externe library (geen Composer/TCPDF/FPDF nodig).
 *
 * Werking in het kort: een PDF-bestand bestaat uit een reeks "objecten"
 * (pagina, lettertype, tekstinhoud) gevolgd door een verwijzingstabel
 * (xref) die exact bijhoudt op welke bytepositie elk object begint. Deze
 * functie bouwt die structuur programmatisch op, zodat de bytes altijd
 * kloppen.
 *
 * Gebruik:
 *   $pdfBytes = genereer_bon_pdf([
 *       'tafel_naam'  => 'Tafel 10',
 *       'bestelling_id' => 7,
 *       'datum_tijd'  => '2026-09-14 19:32:00',
 *       'orderregels' => [ ['gerecht_naam'=>'Sangria (glas)', 'aantal'=>2, 'subtotaal'=>11.00], ... ],
 *       'totaal'      => 42.50,
 *   ]);
 */

function pdf_tekst_escapen(string $tekst): string
{
    // PDF-tekststrings gebruiken ( ) en \ als speciale tekens; die moeten escaped.
    $tekst = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $tekst);
    // Omzetten naar WinAnsi (ondersteunt Nederlandse tekens zoals é, ë, ü)
    $omgezet = @iconv('UTF-8', 'CP1252//TRANSLIT', $tekst);
    return $omgezet !== false ? $omgezet : $tekst;
}

function genereer_bon_pdf(array $data): string
{
    $regelHoogte = 14;
    $regels = []; // elke regel: ['tekst' => ..., 'grootte' => 10, 'vet' => false]

    $regels[] = ['Las Tapas', 13, true];
    $regels[] = ['Restaurant & Tapasbar', 9, false];
    $regels[] = ['------------------------------', 9, false];
    $regels[] = [$data['tafel_naam'], 10, true];
    $datumTekst = date('d-m-Y H:i', strtotime($data['datum_tijd']));
    $regels[] = ['Datum: ' . $datumTekst, 9, false];
    $regels[] = ['Bonnummer: #' . str_pad((string) $data['bestelling_id'], 5, '0', STR_PAD_LEFT), 9, false];
    $regels[] = ['------------------------------', 9, false];

    foreach ($data['orderregels'] as $regel) {
        $omschrijving = $regel['aantal'] . 'x ' . $regel['gerecht_naam'];
        $bedrag = number_format((float) $regel['subtotaal'], 2, ',', '.');
        // Rechts uitlijnen door spaties te berekenen (courier is monospace, 1 teken = vaste breedte)
        $regelBreedteTekens = 32;
        $vrijeRuimte = max(1, $regelBreedteTekens - mb_strlen($omschrijving) - mb_strlen('EUR' . $bedrag));
        $regels[] = [$omschrijving . str_repeat(' ', $vrijeRuimte) . 'EUR' . $bedrag, 9, false];
    }

    $regels[] = ['------------------------------', 9, false];
    $totaalTekst = number_format((float) $data['totaal'], 2, ',', '.');
    $regels[] = ['TOTAAL' . str_repeat(' ', max(1, 26 - mb_strlen('TOTAAL') - mb_strlen('EUR' . $totaalTekst))) . 'EUR' . $totaalTekst, 11, true];
    $regels[] = ['', 6, false];
    $regels[] = ['Bedankt voor uw bezoek aan Las Tapas!', 8, false];

    $hoogte = 60 + (count($regels) * $regelHoogte) + 20;
    $breedte = 230;

    // Contentstream (tekstopdrachten) opbouwen
    // Belangrijk: Td in PDF is een RELATIEVE verplaatsing t.o.v. de vorige
    // regel, geen absolute co+ordinaat. Daarom zetten we de x-positie maar
    // één keer (bij de eerste regel) en verplaatsen we daarna steeds alleen
    // verticaal naar beneden — zo blijft elke regel netjes uitgelijnd.
    $startY = $hoogte - 40;
    $content = "BT\n";
    $eersteRegel = true;
    foreach ($regels as [$tekst, $grootte, $vet]) {
        $font = $vet ? 'F2' : 'F1';
        $content .= "/{$font} {$grootte} Tf\n";
        if ($eersteRegel) {
            $content .= "15 {$startY} Td\n";
            $eersteRegel = false;
        } else {
            $content .= "0 -{$regelHoogte} Td\n";
        }
        $content .= '(' . pdf_tekst_escapen($tekst) . ") Tj\n";
    }
    $content .= "ET";

    // ---------- PDF-objecten opbouwen ----------
    $objecten = [];
    $objecten[1] = "<< /Type /Catalog /Pages 2 0 R >>";
    $objecten[2] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
    $objecten[3] = "<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> "
        . "/MediaBox [0 0 {$breedte} {$hoogte}] /Contents 6 0 R >>";
    $objecten[4] = "<< /Type /Font /Subtype /Type1 /BaseFont /Courier /Encoding /WinAnsiEncoding >>";
    $objecten[5] = "<< /Type /Font /Subtype /Type1 /BaseFont /Courier-Bold /Encoding /WinAnsiEncoding >>";
    $objecten[6] = "<< /Length " . strlen($content) . " >>\nstream\n{$content}\nendstream";

    // ---------- Bytes + xref-tabel opbouwen ----------
    $pdf = "%PDF-1.4\n";
    $offsets = [];

    foreach ($objecten as $nummer => $inhoud) {
        $offsets[$nummer] = strlen($pdf);
        $pdf .= "{$nummer} 0 obj\n{$inhoud}\nendobj\n";
    }

    $xrefStart = strlen($pdf);
    $aantalObjecten = count($objecten) + 1; // +1 voor object 0 (altijd vrij)

    $pdf .= "xref\n0 {$aantalObjecten}\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objecten); $i++) {
        $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
    }

    $pdf .= "trailer\n<< /Size {$aantalObjecten} /Root 1 0 R >>\n";
    $pdf .= "startxref\n{$xrefStart}\n%%EOF";

    return $pdf;
}
