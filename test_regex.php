<?php
$text = <<<EOT
OKA WAHYU SABARUDIN, BAPAK Tgl Faktur

19 Nop 2025

Jatuh Tempo

JL PAKU SARI NO, 08 SESETAN No. Faktur

INV/BS25/X1/3355

Total Faktur |:

7.081.816)
EOT;

$invoiceKeyword = 'No. Faktur';
$dateKeyword = 'Tgl Faktur';
$totalKeyword = 'Total Faktur';

$pattern1 = '/' . preg_quote($invoiceKeyword, '/') . '[\s\W]*([A-Za-z0-9\-\/]+)/i';
if (preg_match($pattern1, $text, $matches)) {
    echo "Invoice: " . trim($matches[1]) . "\n";
} else {
    echo "Invoice pattern failed\n";
}

$pattern2 = '/' . preg_quote($dateKeyword, '/') . '[\s\W]*([0-9]{1,2}\s+[a-zA-Z]+\s+[0-9]{4}|[\d\/\-]+)/i';
if (preg_match($pattern2, $text, $matches)) {
    echo "Date: " . trim($matches[1]) . "\n";
} else {
    echo "Date pattern failed\n";
}

$pattern3 = '/' . preg_quote($totalKeyword, '/') . '[\s\W]*((?:Rp)?\s*[\d\.\,]+)/i';
if (preg_match($pattern3, $text, $matches)) {
    echo "Total: " . trim($matches[1]) . "\n";
} else {
    echo "Total pattern failed\n";
}
