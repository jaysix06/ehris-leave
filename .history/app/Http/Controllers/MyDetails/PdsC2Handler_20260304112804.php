<?php

namespace App\Http\Controllers\MyDetails;

/**
 * Handles building the C2 (Work Experience continuation) cell map and applying it to the C2 worksheet XML.
 */
class PdsC2Handler
{
    private const WORK_EXPERIENCE_START_ROW = 18;

    private const WORK_EXPERIENCE_MAX_ROWS = 45;

    /**
     * Build cell map for the C2 sheet (Work Experience continuation rows 18–44).
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

        foreach ($workExperience->take(self::WORK_EXPERIENCE_MAX_ROWS)->values() as $i => $row) {
            $r = self::WORK_EXPERIENCE_START_ROW + $i;
            $from = $formatDate($row->inclusive_date_from ?? null);
            $to = $formatDate($row->inclusive_date_to ?? null);
            // V. WORK EXPERIENCE (C2) – Inclusive Dates (From/To), Position Title,
            // Department/Agency/Office/Company, Status of Appointment, Gov't Service (Y/N).
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
            $cellText = $this->sanitizeForXml((string) $value);
            $t->appendChild($dom->createTextNode($cellText !== '' ? $cellText : ' '));
            $is->appendChild($t);
            $cellNode->appendChild($is);
        }

        return $dom->saveXML() ?: $worksheetXml;
    }

    /**
     * Keep only XML 1.0 allowed characters so Microsoft Excel 2016 opens the workbook without
     * "problem with some content". Same logic as PdsExportHandler::sanitizeForXml.
     */
    private function sanitizeForXml(string $s): string
    {
        $s = $s ?? '';
        $result = preg_replace_callback('/./u', function (array $m): string {
            $char = $m[0];
            $byte = strlen($char) === 1 ? ord($char) : null;
            if ($byte !== null) {
                if ($byte === 0x09 || $byte === 0x0A || $byte === 0x0D || ($byte >= 0x20 && $byte <= 0x7F)) {
                    return $char;
                }

                return '';
            }
            $codepoint = unpack('N', mb_convert_encoding($char, 'UCS-4BE', 'UTF-8'));
            $cp = $codepoint ? $codepoint[1] : 0;
            if ($cp >= 0x80 && $cp <= 0xD7FF) {
                return $char;
            }
            if ($cp >= 0xE000 && $cp <= 0xFFFD) {
                return $char;
            }
            if ($cp >= 0x10000 && $cp <= 0x10FFFF) {
                return $char;
            }

            return '';
        }, $s);

        return $result ?? $s;
    }
}
