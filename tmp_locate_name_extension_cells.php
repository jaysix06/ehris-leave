<?php

$sheetPath = __DIR__ . '/storage/app/tmp_pds_debug/unzipped/xl/worksheets/sheet1.xml';
$sharedPath = __DIR__ . '/storage/app/tmp_pds_debug/unzipped/xl/sharedStrings.xml';

if (!is_file($sheetPath) || !is_file($sharedPath)) {
    echo "missing files\n";
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
    $text = '';
    foreach ($si->getElementsByTagName('t') as $t) {
        $text .= $t->textContent;
    }
    $strings[$idx] = $text;
}

$sheetXml = file_get_contents($sheetPath);
$dom = new DOMDocument();
$dom->loadXML($sheetXml);
$xp = new DOMXPath($dom);
$xp->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

echo "Cells whose sharedString contains 'Name Extension':\n";
foreach ($xp->query('//s:c[@t="s"]') as $cell) {
    if (! $cell instanceof DOMElement) continue;
    $ref = $cell->getAttribute('r');
    $v = $cell->getElementsByTagName('v')->item(0);
    if (! $v) continue;
    $idx = (int) $v->textContent;
    $text = $strings[$idx] ?? '';
    if (stripos($text, 'name extension') !== false) {
        echo $ref . ' -> [' . $idx . '] ' . $text . PHP_EOL;
    }
}

