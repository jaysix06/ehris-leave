<?php

$sharedPath = __DIR__ . '/storage/app/tmp_pds_debug/unzipped/xl/sharedStrings.xml';
if (!is_file($sharedPath)) {
    echo "sharedStrings.xml not found\n";
    exit(1);
}

$xml = file_get_contents($sharedPath);
$dom = new DOMDocument();
$dom->loadXML($xml);
$xp = new DOMXPath($dom);
$xp->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

foreach ($xp->query('//s:si') as $idx => $si) {
    if (! $si instanceof DOMElement) continue;
    $text = '';
    foreach ($si->getElementsByTagName('t') as $t) {
        $text .= $t->textContent;
    }
    if (stripos($text, 'name extension') !== false) {
        echo "index={$idx} text=" . $text . PHP_EOL;
    }
}

