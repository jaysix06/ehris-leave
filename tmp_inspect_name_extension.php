<?php

$templateSheet = __DIR__ . '/storage/app/tmp_pds_debug/unzipped/xl/worksheets/sheet1.xml';
$templateRels  = __DIR__ . '/storage/app/tmp_pds_debug/unzipped/xl/worksheets/_rels/sheet1.xml.rels';

echo "== TEMPLATE M11 CELL ==\n";
if (is_file($templateSheet)) {
    $xml = file_get_contents($templateSheet);
    $dom = new DOMDocument();
    $dom->loadXML($xml);
    $xp = new DOMXPath($dom);
    $xp->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $cell = $xp->query('//s:c[@r="M11"]')->item(0);
    if ($cell instanceof DOMElement) {
        echo $dom->saveXML($cell) . "\n";
    } else {
        echo "no M11 cell\n";
    }

    echo "\n== TEMPLATE CONTROLS ==\n";
    $controls = $xp->query('//*[local-name()="control"]');
    foreach ($controls as $ctrl) {
        if (! $ctrl instanceof DOMElement) {
            continue;
        }
        $name = $ctrl->getAttribute('name');
        $rid = $ctrl->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id') ?: $ctrl->getAttribute('r:id');
        $shapeId = $ctrl->getAttribute('shapeId');
        echo "control name={$name}, rId={$rid}, shapeId={$shapeId}\n";
    }
} else {
    echo "template sheet not found\n";
}

echo "\n== CTRLPROP RELS (DROPDOWNS) ==\n";
if (is_file($templateRels)) {
    $relsXml = file_get_contents($templateRels);
    $rels = simplexml_load_string($relsXml);
    $rels->registerXPathNamespace('pr', 'http://schemas.openxmlformats.org/package/2006/relationships');
    $ctrlRels = $rels->xpath("//pr:Relationship[contains(@Type, '/ctrlProp')]");
    foreach ($ctrlRels as $rel) {
        $rid = (string) $rel['Id'];
        $target = (string) $rel['Target'];
        echo "ctrlProp rel {$rid} -> {$target}\n";
    }
} else {
    echo "no sheet1.xml.rels\n";
}

