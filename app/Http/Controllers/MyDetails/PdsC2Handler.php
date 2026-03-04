<?php

namespace App\Http\Controllers\MyDetails;

/**
 * Handles building the C2 (Civil Service Eligibility & Work Experience) cell map and applying it to the C2 worksheet XML.
 */
class PdsC2Handler
{
    private const ELIGIBILITY_START_ROW = 18;

    private const ELIGIBILITY_MAX_ROWS = 17;

    private const WORK_EXPERIENCE_START_ROW = 35;

    private const WORK_EXPERIENCE_MAX_ROWS = 10;

    /**
     * Build cell map for the C2 sheet (Civil Service Eligibility rows 18–34, Work Experience rows 35–44).
     *
     * @param  \Illuminate\Support\Collection<int, object>  $eligibility
     * @param  \Illuminate\Support\Collection<int, object>  $workExperience
     * @param  callable(mixed): string  $formatDate
     * @param  callable(mixed): string  $pdsValue
     * @return array<string, string>
     */
    public function buildCellMap($eligibility, $workExperience, callable $formatDate, callable $pdsValue): array
    {
        $map = [];

        foreach ($eligibility->take(self::ELIGIBILITY_MAX_ROWS)->values() as $i => $row) {
            $r = self::ELIGIBILITY_START_ROW + $i;
            // IV. CIVIL SERVICE ELIGIBILITY (C2)
            // A:B – CAREER SERVICE / ELIGIBILITY; C – RATING; D:F – DATE OF EXAMINATION / CONFERMENT;
            // G:I – PLACE OF EXAMINATION / CONFERMENT; J – LICENSE NUMBER; K – VALID UNTIL.
            $map["A{$r}"] = $pdsValue($row->title ?? null);
            $map["F{$r}"] = $pdsValue($row->rating ?? null);
            $map["G{$r}"] = $pdsValue($formatDate($row->date_exam ?? null));
            $map["I{$r}"] = $pdsValue($row->place_exam ?? null);
            $map["J{$r}"] = $pdsValue($row->license_no ?? null);
            $map["K{$r}"] = $pdsValue($formatDate($row->date_release ?? null));
        }

        foreach ($workExperience->take(self::WORK_EXPERIENCE_MAX_ROWS)->values() as $i => $row) {
            $r = self::WORK_EXPERIENCE_START_ROW + $i;
            $from = $formatDate($row->inclusive_date_from ?? null);
            $to = $formatDate($row->inclusive_date_to ?? null);
            // V. WORK EXPERIENCE (C2) – Inclusive Dates (From/To), Position Title, Department/Agency/Office/Company,
            // Status of Appointment, Gov't Service (Y/N).
            $map["A{$r}"] = $pdsValue($from === 'N/A' ? null : $from);
            $map["C{$r}"] = $pdsValue($to === 'N/A' ? null : $to);
            $map["D{$r}"] = $pdsValue($row->position_title ?? null);
            $map["G{$r}"] = $pdsValue($row->company_name ?? null);
            $map["J{$r}"] = $pdsValue($row->employment_status ?? null);
            $map["K{$r}"] = $pdsValue($row->government_service ?? null);
        }

        return $map;
    }

    /**
     * Apply cell map to C2 worksheet XML (values only; preserves existing cell styles).
     */
    public function applyCellMapToWorksheetXml(string $worksheetXml, array $cellMap): string
    {
        if ($cellMap === []) {
            return $worksheetXml;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (! @$dom->loadXML($worksheetXml)) {
            return $worksheetXml;
        }

        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('s', $ns);

        $sheetData = $xpath->query("//*[local-name()='sheetData']")->item(0);
        if (! $sheetData instanceof \DOMElement) {
            return $worksheetXml;
        }

        foreach ($cellMap as $cellRef => $value) {
            preg_match('/([A-Z]+)(\d+)/', $cellRef, $parts);
            $rowNumber = isset($parts[2]) ? (int) $parts[2] : 0;
            if ($rowNumber <= 0) {
                continue;
            }

            $rowNode = $xpath->query(".//*[local-name()='row'][@r='{$rowNumber}']", $sheetData)->item(0);
            if (! $rowNode instanceof \DOMElement) {
                $rowNode = $dom->createElementNS($ns, 'row');
                $rowNode->setAttribute('r', (string) $rowNumber);
                $prevRow = $rowNumber > 1 ? $xpath->query(".//*[local-name()='row'][@r='".($rowNumber - 1)."']", $sheetData)->item(0) : null;
                if ($prevRow instanceof \DOMElement) {
                    foreach ($prevRow->attributes as $attr) {
                        if ($attr->name === 'r') {
                            continue;
                        }
                        if ($attr->namespaceURI === null || $attr->namespaceURI === '') {
                            $rowNode->setAttribute($attr->name, $attr->value);

                            continue;
                        }
                        try {
                            $prefix = (string) ($attr->prefix ?? '');
                            $localName = (string) ($attr->localName ?? $attr->name);
                            $qualifiedName = $prefix !== '' ? $prefix.':'.$localName : $localName;
                            $rowNode->setAttributeNS($attr->namespaceURI, $qualifiedName, $attr->value);
                        } catch (\DOMException) {
                            // Skip attributes that cause namespace errors
                        }
                    }
                    $next = $prevRow->nextSibling;
                    if ($next !== null) {
                        $sheetData->insertBefore($rowNode, $next);
                    } else {
                        $sheetData->appendChild($rowNode);
                    }
                } else {
                    $sheetData->appendChild($rowNode);
                }
            }

            $cellNode = $xpath->query("*[local-name()='c'][@r='{$cellRef}']", $rowNode)->item(0);
            if (! $cellNode instanceof \DOMElement) {
                $cellNode = $dom->createElementNS($ns, 'c');
                $cellNode->setAttribute('r', $cellRef);
                $rowNode->appendChild($cellNode);
            }

            while ($cellNode->firstChild) {
                $cellNode->removeChild($cellNode->firstChild);
            }

            $cellNode->setAttribute('t', 'inlineStr');
            $is = $dom->createElementNS($ns, 'is');
            $t = $dom->createElementNS($ns, 't');
            $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
            $t->appendChild($dom->createTextNode((string) $value));
            $is->appendChild($t);
            $cellNode->appendChild($is);
        }

        return $dom->saveXML() ?: $worksheetXml;
    }
}
