<?php
/**
 * pdf_bon.php
 *
 * Generates a valid receipt PDF using plain PHP, without an external library.
 *
 * A PDF contains objects (page, fonts, and text) followed by a cross-reference
 * table (xref) that records each object's byte offset. This function builds
 * that structure programmatically so the offsets remain valid.
 *
 * Usage:
 *   $pdfBytes = generateReceiptPdf([
 *       'tafel_naam'  => 'Tafel 10',
 *       'bestelling_id' => 7,
 *       'datum_tijd'  => '2026-09-14 19:32:00',
 *       'orderregels' => [ ['gerecht_naam'=>'Sangria (glas)', 'aantal'=>2, 'subtotaal'=>11.00], ... ],
 *       'totaal'      => 42.50,
 *   ]);
 */

function escapePdfText(string $text): string
{
    // Escape PDF string delimiters and backslashes.
    $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    // Convert to WinAnsi, which supports common Western European characters.
    $convertedText = @iconv('UTF-8', 'CP1252//TRANSLIT', $text);
    return $convertedText !== false ? $convertedText : $text;
}

function pdfCharacterLength(string $text): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($text, 'UTF-8');
    }

    $length = function_exists('iconv_strlen') ? iconv_strlen($text, 'UTF-8') : false;
    return $length !== false ? $length : strlen($text);
}

function generateReceiptPdf(array $data): string
{
    $lineHeight = 14;
    $lines = []; // Each line contains its text, font size, and bold flag.

    $lines[] = ['LAS TAPAS', 13, true];
    $lines[] = ['Bar de Espana - Tapas y Vino', 9, false];
    $lines[] = ['------------------------------', 9, false];
    $lines[] = [$data['tafel_naam'], 10, true];
    $dateText = date('d-m-Y H:i', strtotime($data['datum_tijd']));
    $lines[] = ['Datum: ' . $dateText, 9, false];
    $lines[] = ['Bonnummer: #' . str_pad((string) $data['bestelling_id'], 5, '0', STR_PAD_LEFT), 9, false];
    if (!empty($data['aantal_personen'])) {
        $lines[] = ['Personen: ' . $data['aantal_personen'], 9, false];
    }
    $lines[] = ['------------------------------', 9, false];

    foreach ($data['orderregels'] as $row) {
        $description = $row['aantal'] . 'x ' . $row['gerecht_naam'];
        $amount = number_format((float) $row['subtotaal'], 2, ',', '.');
        // Courier is monospaced, so spaces can be used to align the amount.
        $lineWidthCharacters = 32;
        $availableSpace = max(1, $lineWidthCharacters - pdfCharacterLength($description) - pdfCharacterLength('EUR' . $amount));
        $lines[] = [$description . str_repeat(' ', $availableSpace) . 'EUR' . $amount, 9, false];
    }

    $lines[] = ['------------------------------', 9, false];
    $totalText = number_format((float) $data['totaal'], 2, ',', '.');
    $lines[] = ['TOTAAL' . str_repeat(' ', max(1, 26 - pdfCharacterLength('TOTAAL') - pdfCharacterLength('EUR' . $totalText))) . 'EUR' . $totalText, 11, true];
    $lines[] = ['', 6, false];
    $lines[] = ['Bedankt voor uw bezoek aan Las Tapas!', 8, false];

    $pageHeight = 60 + (count($lines) * $lineHeight) + 20;
    $pageWidth = 230;

    // Td applies a relative offset, so set the initial position once and move
    // each subsequent line down vertically to keep the text aligned.
    $startY = $pageHeight - 40;
    $content = "BT\n";
    $isFirstLine = true;
    foreach ($lines as [$text, $fontSize, $isBold]) {
        $font = $isBold ? 'F2' : 'F1';
        $content .= "/{$font} {$fontSize} Tf\n";
        if ($isFirstLine) {
            $content .= "15 {$startY} Td\n";
            $isFirstLine = false;
        } else {
            $content .= "0 -{$lineHeight} Td\n";
        }
        $content .= '(' . escapePdfText($text) . ") Tj\n";
    }
    $content .= "ET";

    $objects = [];
    $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
    $objects[2] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
    $objects[3] = "<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> "
        . "/MediaBox [0 0 {$pageWidth} {$pageHeight}] /Contents 6 0 R >>";
    $objects[4] = "<< /Type /Font /Subtype /Type1 /BaseFont /Courier /Encoding /WinAnsiEncoding >>";
    $objects[5] = "<< /Type /Font /Subtype /Type1 /BaseFont /Courier-Bold /Encoding /WinAnsiEncoding >>";
    $objects[6] = "<< /Length " . strlen($content) . " >>\nstream\n{$content}\nendstream";

    $pdfDocument = "%PDF-1.4\n";
    $offsets = [];

    foreach ($objects as $objectNumber => $objectContent) {
        $offsets[$objectNumber] = strlen($pdfDocument);
        $pdfDocument .= "{$objectNumber} 0 obj\n{$objectContent}\nendobj\n";
    }

    $xrefStart = strlen($pdfDocument);
    $objectCount = count($objects) + 1; // Include object 0, which is always free.

    $pdfDocument .= "xref\n0 {$objectCount}\n";
    $pdfDocument .= "0000000000 65535 f \n";
    for ($index = 1; $index <= count($objects); $index++) {
        $pdfDocument .= str_pad((string) $offsets[$index], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
    }

    $pdfDocument .= "trailer\n<< /Size {$objectCount} /Root 1 0 R >>\n";
    $pdfDocument .= "startxref\n{$xrefStart}\n%%EOF";

    return $pdfDocument;
}
