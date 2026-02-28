<?php

$sheetPath = __DIR__ . '/storage/app/tmp_pds_debug/unzipped/xl/worksheets/sheet1.xml';
$sharedPath = __DIR__ . '/storage/app/tmp_pds_debug/unzipped/xl/sharedStrings.xml';

if (!is_file($sheetPath) || !is_file($sharedPath)) {
    echo "sheet or sharedStrings not found\n";
    exit(1);
}

$sharedXml = file_get_contents($sharedPath);
$sharedDom = new DOMDocument();
$sharedDom->loadXML($sharedXml);
$sxp = new DOMXPath($sharedDom);
$sxp->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
$strings = [];
foreach ($sxp->query('//s:si') as $idx => $si) {
    if (! $si instanceof DOMElement) continue;
    // concatenate all text nodes under si
    $text = '';
    foreach ($si->getElementsByTagName('t') as $t) {
        $text .= $t->textContent;
    }
    $strings[$idx] = trim($text);
}

$sheetXml = file_get_contents($sheetPath);
$dom = new DOMDocument();
$dom->loadXML($sheetXml);
$xp = new DOMXPath($dom);
$xp->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

echo "Q11-Q216 values on C1 (subset around Philippines):\n";
for ($row = 11; $row <= 216; $row++) {
    $ref = 'Q' . $row;
    $cell = $xp->query('//s:c[@r="'.$ref.'"]')->item(0);
    if (! $cell instanceof DOMElement) {
        continue;
    }
    $type = $cell->getAttribute('t');
    $value = '';
    if ($type === 's') {
        $v = $cell->getElementsByTagName('v')->item(0);
        if ($v) {
            $idx = (int) $v->textContent;
            $value = $strings[$idx] ?? '';
        }
    } elseif ($type === 'inlineStr') {
        $t = $cell->getElementsByTagName('t')->item(0);
        $value = $t ? $t->textContent : '';
    } else {
        $v = $cell->getElementsByTagName('v')->item(0);
        $value = $v ? $v->textContent : '';
    }
    echo $ref . ' = ' . $value . "\n";
}

