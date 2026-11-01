<?php

namespace App\Services;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class XlsxWorkbookReader
{
    private const RELATIONSHIP_NAMESPACE = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    public function readFirstSheet(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("Workbook tidak ditemukan atau tidak dapat dibaca: {$path}");
        }

        $archive = new ZipArchive;
        if ($archive->open($path) !== true) {
            throw new RuntimeException("Workbook tidak dapat dibuka: {$path}");
        }

        try {
            $workbook = $this->readXml($archive, 'xl/workbook.xml');
            $relationships = $this->readXml($archive, 'xl/_rels/workbook.xml.rels');
            $sheet = $workbook->xpath('//*[local-name()="sheet"]')[0] ?? null;

            if (! $sheet instanceof SimpleXMLElement) {
                throw new RuntimeException("Workbook tidak memiliki worksheet: {$path}");
            }

            $relationshipId = (string) ($sheet->attributes(self::RELATIONSHIP_NAMESPACE)['id'] ?? '');
            $target = null;
            foreach ($relationships->xpath('//*[local-name()="Relationship"]') ?: [] as $relationship) {
                if ((string) $relationship['Id'] === $relationshipId) {
                    $target = (string) $relationship['Target'];
                    break;
                }
            }

            if ($target === null) {
                throw new RuntimeException("Worksheet pertama tidak ditemukan di workbook: {$path}");
            }

            $sheetPath = $this->resolveZipPath('xl/workbook.xml', $target);
            $worksheet = $this->readXml($archive, $sheetPath);
            $sharedStrings = $this->readSharedStrings($archive);
            $rowNodes = $worksheet->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') ?: [];

            return $this->rowsFromWorksheet($rowNodes, $sharedStrings);
        } finally {
            $archive->close();
        }
    }

    private function readXml(ZipArchive $archive, string $entry): SimpleXMLElement
    {
        $contents = $archive->getFromName($entry);
        if ($contents === false) {
            throw new RuntimeException("Workbook tidak memiliki bagian XML yang diperlukan: {$entry}");
        }

        $previousErrorMode = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($contents, SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);
            if ($xml === false) {
                $error = libxml_get_last_error();
                $detail = $error?->message ?? 'format XML tidak valid';
                throw new RuntimeException("Gagal membaca {$entry}: {$detail}");
            }

            return $xml;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorMode);
        }
    }

    private function readSharedStrings(ZipArchive $archive): array
    {
        if ($archive->locateName('xl/sharedStrings.xml') === false) {
            return [];
        }

        $sharedStrings = [];
        foreach ($this->readXml($archive, 'xl/sharedStrings.xml')->xpath('//*[local-name()="si"]') ?: [] as $item) {
            $text = '';
            foreach ($item->xpath('.//*[local-name()="t"]') ?: [] as $textNode) {
                $text .= (string) $textNode;
            }
            $sharedStrings[] = $text;
        }

        return $sharedStrings;
    }

    private function rowsFromWorksheet(array $rowNodes, array $sharedStrings): array
    {
        $headers = [];
        $rows = [];

        foreach ($rowNodes as $rowNode) {
            $cells = [];
            foreach ($rowNode->xpath('./*[local-name()="c"]') ?: [] as $cell) {
                $reference = (string) $cell['r'];
                $column = $this->columnIndex($reference);
                $type = (string) $cell['t'];
                $value = '';

                if ($type === 'inlineStr') {
                    foreach ($cell->xpath('.//*[local-name()="t"]') ?: [] as $textNode) {
                        $value .= (string) $textNode;
                    }
                } else {
                    $valueNode = $cell->xpath('./*[local-name()="v"]')[0] ?? null;
                    if ($valueNode !== null) {
                        $value = (string) $valueNode;
                    }
                    if ($type === 's' && $value !== '') {
                        $value = $sharedStrings[(int) $value] ?? '';
                    }
                }

                $cells[$column] = $value;
            }

            if ($cells === []) {
                continue;
            }

            if ($headers === []) {
                ksort($cells);
                foreach ($cells as $index => $header) {
                    $header = trim((string) $header);
                    if ($header !== '') {
                        $headers[$index] = $header;
                    }
                }
                if ($headers === []) {
                    throw new RuntimeException('Baris header workbook kosong.');
                }
                continue;
            }

            $record = [];
            foreach ($headers as $index => $header) {
                $record[$header] = $cells[$index] ?? '';
            }

            if (array_filter($record, fn (mixed $value): bool => $value !== '' && $value !== null) !== []) {
                $rows[] = $record;
            }
        }

        if ($headers === []) {
            throw new RuntimeException('Workbook tidak memiliki baris header.');
        }

        return $rows;
    }

    private function columnIndex(string $cellReference): int
    {
        preg_match('/^[A-Z]+/', strtoupper($cellReference), $matches);
        $letters = $matches[0] ?? '';
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + ord($letter) - ord('A') + 1;
        }

        return max(0, $index - 1);
    }

    private function resolveZipPath(string $source, string $target): string
    {
        $path = str_starts_with($target, '/')
            ? ltrim($target, '/')
            : dirname($source) . '/' . $target;
        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($segments);
                continue;
            }
            $segments[] = $segment;
        }

        return implode('/', $segments);
    }
}
