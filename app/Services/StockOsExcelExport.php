<?php

namespace App\Services;

use App\Models\StockTransaction;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;
use XMLWriter;
use ZipArchive;

class StockOsExcelExport
{
    public function stream(Builder $items): void
    {
        $workbookPath = tempnam(sys_get_temp_dir(), 'stock-os-');
        if ($workbookPath === false) {
            throw new RuntimeException('Tidak dapat membuat file ekspor Excel sementara.');
        }

        $sheetPath = tempnam(sys_get_temp_dir(), 'stock-os-sheet-');
        if ($sheetPath === false) {
            unlink($workbookPath);
            throw new RuntimeException('Tidak dapat membuat lembar kerja Excel sementara.');
        }

        $usageSheetPath = tempnam(sys_get_temp_dir(), 'stock-os-usage-');
        if ($usageSheetPath === false) {
            unlink($workbookPath);
            unlink($sheetPath);
            throw new RuntimeException('Tidak dapat membuat lembar catatan pemakaian sementara.');
        }

        try {
            $usageItemIds = (clone $items)->reorder()->select('id');
            $usageItems = StockTransaction::query()
                ->where('type', 'OUT')
                ->whereIn('stock_os_item_id', $usageItemIds)
                ->with([
                    'item:id,material_name,warehouse_name',
                    'user:user_id,email',
                ])
                ->orderBy('transaction_date')
                ->orderBy('id');

            $rowCount = $this->writeWorksheet($items, $sheetPath);
            $usageRowCount = $this->writeUsageWorksheet($usageItems, $usageSheetPath);
            $this->createWorkbook($workbookPath, $sheetPath, $rowCount, $usageSheetPath, $usageRowCount);

            if (readfile($workbookPath) === false) {
                throw new RuntimeException('Tidak dapat mengirim file ekspor Excel.');
            }
        } finally {
            @unlink($workbookPath);
            @unlink($sheetPath);
            @unlink($usageSheetPath);
        }
    }

    private function writeWorksheet(Builder $items, string $path): int
    {
        $xml = new XMLWriter;
        if (! $xml->openUri($path)) {
            throw new RuntimeException('Tidak dapat menulis lembar kerja Excel.');
        }

        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('worksheet');
        $xml->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $xml->writeAttribute('xmlns:r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $xml->startElement('sheetViews');
        $xml->startElement('sheetView');
        $xml->writeAttribute('workbookViewId', '0');
        $xml->startElement('pane');
        $xml->writeAttribute('ySplit', '1');
        $xml->writeAttribute('topLeftCell', 'A2');
        $xml->writeAttribute('activePane', 'bottomLeft');
        $xml->writeAttribute('state', 'frozen');
        $xml->endElement();
        $xml->endElement();
        $xml->endElement();
        $xml->startElement('sheetFormatPr');
        $xml->writeAttribute('defaultRowHeight', '20');
        $xml->endElement();
        $xml->startElement('cols');

        foreach ([34, 24, 16, 16, 16, 18, 40, 23] as $index => $width) {
            $xml->startElement('col');
            $xml->writeAttribute('min', (string) ($index + 1));
            $xml->writeAttribute('max', (string) ($index + 1));
            $xml->writeAttribute('width', (string) $width);
            $xml->writeAttribute('customWidth', '1');
            $xml->endElement();
        }

        $xml->endElement();
        $xml->startElement('sheetData');
        $this->writeRow($xml, 1, [
            ['Nama Material', 'text', 1],
            ['Gudang', 'text', 1],
            ['Stok (kg)', 'text', 1],
            ['Sisa PO (kg)', 'text', 1],
            ['Safety Stock (kg)', 'text', 1],
            ['Status', 'text', 1],
            ['Catatan', 'text', 1],
            ['Update', 'text', 1],
        ]);

        $rowNumber = 1;
        foreach ($items->lazy(500) as $item) {
            $rowNumber++;
            $status = (float) $item->stock_qty <= 0
                ? 'Stok Habis'
                : ((float) $item->stock_qty <= (float) $item->safety_stock ? 'Stok Kritis' : 'Aman');

            $this->writeRow($xml, $rowNumber, [
                [$item->material_name, 'text', 0],
                [$item->warehouse_name, 'text', 0],
                [$item->stock_qty, 'number', 2],
                [$item->outstanding_qty, 'number', 2],
                [$item->safety_stock, 'number', 2],
                [$status, 'text', 3],
                [$item->notes ?: '-', 'text', 0],
                [$item->last_updated, 'date', 4],
            ]);
        }

        $xml->endElement();
        $xml->startElement('pageMargins');
        $xml->writeAttribute('left', '0.25');
        $xml->writeAttribute('right', '0.25');
        $xml->writeAttribute('top', '0.5');
        $xml->writeAttribute('bottom', '0.5');
        $xml->writeAttribute('header', '0.2');
        $xml->writeAttribute('footer', '0.2');
        $xml->endElement();
        $xml->startElement('pageSetup');
        $xml->writeAttribute('orientation', 'landscape');
        $xml->writeAttribute('paperSize', '9');
        $xml->writeAttribute('fitToWidth', '1');
        $xml->writeAttribute('fitToHeight', '0');
        $xml->endElement();
        $xml->startElement('tableParts');
        $xml->writeAttribute('count', '1');
        $xml->startElement('tablePart');
        $xml->writeAttribute('r:id', 'rId1');
        $xml->endElement();
        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();
        $xml->flush();

        return $rowNumber;
    }

    private function writeUsageWorksheet(Builder $transactions, string $path): int
    {
        $xml = new XMLWriter;
        if (! $xml->openUri($path)) {
            throw new RuntimeException('Tidak dapat menulis lembar catatan pemakaian.');
        }

        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('worksheet');
        $xml->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $xml->writeAttribute('xmlns:r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $xml->startElement('sheetViews');
        $xml->startElement('sheetView');
        $xml->writeAttribute('workbookViewId', '0');
        $xml->startElement('pane');
        $xml->writeAttribute('ySplit', '1');
        $xml->writeAttribute('topLeftCell', 'A2');
        $xml->writeAttribute('activePane', 'bottomLeft');
        $xml->writeAttribute('state', 'frozen');
        $xml->endElement();
        $xml->endElement();
        $xml->endElement();
        $xml->startElement('sheetFormatPr');
        $xml->writeAttribute('defaultRowHeight', '20');
        $xml->endElement();
        $xml->startElement('cols');

        foreach ([18, 34, 24, 16, 42, 28] as $index => $width) {
            $xml->startElement('col');
            $xml->writeAttribute('min', (string) ($index + 1));
            $xml->writeAttribute('max', (string) ($index + 1));
            $xml->writeAttribute('width', (string) $width);
            $xml->writeAttribute('customWidth', '1');
            $xml->endElement();
        }

        $xml->endElement();
        $xml->startElement('sheetData');
        $this->writeRow($xml, 1, [
            ['Tanggal', 'text', 1],
            ['Nama Material', 'text', 1],
            ['Gudang', 'text', 1],
            ['Jumlah Pemakaian (kg)', 'text', 1],
            ['Keterangan Pemakaian', 'text', 1],
            ['Dicatat Oleh', 'text', 1],
        ]);

        $rowNumber = 1;
        foreach ($transactions->lazy(500) as $transaction) {
            $rowNumber++;
            $this->writeRow($xml, $rowNumber, [
                [$transaction->transaction_date, 'date', 4],
                [$transaction->item->material_name, 'text', 0],
                [$transaction->item->warehouse_name, 'text', 0],
                [$transaction->qty, 'number', 2],
                [$transaction->notes ?: '-', 'text', 0],
                [$transaction->user?->email ?? '-', 'text', 0],
            ]);
        }

        $xml->endElement();
        $xml->startElement('pageMargins');
        $xml->writeAttribute('left', '0.25');
        $xml->writeAttribute('right', '0.25');
        $xml->writeAttribute('top', '0.5');
        $xml->writeAttribute('bottom', '0.5');
        $xml->writeAttribute('header', '0.2');
        $xml->writeAttribute('footer', '0.2');
        $xml->endElement();
        $xml->startElement('pageSetup');
        $xml->writeAttribute('orientation', 'landscape');
        $xml->writeAttribute('paperSize', '9');
        $xml->writeAttribute('fitToWidth', '1');
        $xml->writeAttribute('fitToHeight', '0');
        $xml->endElement();
        $xml->startElement('tableParts');
        $xml->writeAttribute('count', '1');
        $xml->startElement('tablePart');
        $xml->writeAttribute('r:id', 'rId1');
        $xml->endElement();
        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();
        $xml->flush();

        return $rowNumber;
    }

    private function writeRow(XMLWriter $xml, int $rowNumber, array $cells): void
    {
        $xml->startElement('row');
        $xml->writeAttribute('r', (string) $rowNumber);

        foreach ($cells as $index => [$value, $type, $style]) {
            if ($value === null && $type === 'date') {
                $this->writeTextCell($xml, $index, $rowNumber, '', $style);

                continue;
            }

            if ($type === 'text') {
                $this->writeTextCell($xml, $index, $rowNumber, (string) $value, $style);

                continue;
            }

            $xml->startElement('c');
            $xml->writeAttribute('r', $this->columnName($index + 1).$rowNumber);
            $xml->writeAttribute('s', (string) $style);

            if ($type === 'date') {
                $xml->writeElement('v', (string) $this->excelDate($value));
            } else {
                $xml->writeAttribute('t', 'n');
                $xml->writeElement('v', (string) $value);
            }

            $xml->endElement();
        }

        $xml->endElement();
    }

    private function writeTextCell(XMLWriter $xml, int $column, int $row, string $value, int $style): void
    {
        $xml->startElement('c');
        $xml->writeAttribute('r', $this->columnName($column + 1).$row);
        $xml->writeAttribute('s', (string) $style);
        $xml->writeAttribute('t', 'inlineStr');
        $xml->startElement('is');
        $xml->writeElement('t', $value);
        $xml->endElement();
        $xml->endElement();
    }

    private function createWorkbook(string $path, string $sheetPath, int $rowCount, string $usageSheetPath, int $usageRowCount): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Tidak dapat membuat file Excel.');
        }

        try {
            $entries = [
                '[Content_Types].xml' => $this->contentTypesXml(),
                '_rels/.rels' => $this->rootRelationshipsXml(),
                'docProps/core.xml' => $this->corePropertiesXml(),
                'docProps/app.xml' => $this->appPropertiesXml(),
                'xl/workbook.xml' => $this->workbookXml(),
                'xl/_rels/workbook.xml.rels' => $this->workbookRelationshipsXml(),
                'xl/styles.xml' => $this->stylesXml(),
                'xl/worksheets/_rels/sheet1.xml.rels' => $this->worksheetRelationshipsXml(),
                'xl/worksheets/_rels/sheet2.xml.rels' => $this->usageWorksheetRelationshipsXml(),
                'xl/tables/table1.xml' => $this->tableXml($rowCount),
                'xl/tables/table2.xml' => $this->usageTableXml($usageRowCount),
            ];

            foreach ($entries as $name => $contents) {
                if (! $zip->addFromString($name, $contents)) {
                    throw new RuntimeException("Tidak dapat menambahkan {$name} ke file Excel.");
                }
            }

            if (! $zip->addFile($sheetPath, 'xl/worksheets/sheet1.xml')) {
                throw new RuntimeException('Tidak dapat menambahkan lembar kerja ke file Excel.');
            }
            if (! $zip->addFile($usageSheetPath, 'xl/worksheets/sheet2.xml')) {
                throw new RuntimeException('Tidak dapat menambahkan catatan pemakaian ke file Excel.');
            }
        } finally {
            $zip->close();
        }
    }

    private function excelDate(CarbonInterface $date): int
    {
        return (int) (new \DateTimeImmutable('1899-12-30'))->diff(
            new \DateTimeImmutable($date->toDateString()),
        )->format('%r%a');
    }

    private function columnName(int $column): string
    {
        $name = '';
        while ($column > 0) {
            $column--;
            $name = chr(65 + ($column % 26)).$name;
            $column = intdiv($column, 26);
        }

        return $name;
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'<Override PartName="/xl/tables/table1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.table+xml"/>'
            .'<Override PartName="/xl/tables/table2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.table+xml"/>'
            .'</Types>';
    }

    private function rootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            .'</Relationships>';
    }

    private function corePropertiesXml(): string
    {
        $createdAt = gmdate('Y-m-d\TH:i:s\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:creator>Master Material</dc:creator>'
            .'<cp:lastModifiedBy>Master Material</cp:lastModifiedBy>'
            .'<dcterms:created xsi:type="dcterms:W3CDTF">'.$createdAt.'</dcterms:created>'
            .'<dcterms:modified xsi:type="dcterms:W3CDTF">'.$createdAt.'</dcterms:modified>'
            .'</cp:coreProperties>';
    }

    private function appPropertiesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            .'<Application>Master Material</Application>'
            .'</Properties>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Stock Bahan Kimia (OS)" sheetId="1" r:id="rId1"/><sheet name="Catatan Pemakaian" sheetId="2" r:id="rId2"/></sheets>'
            .'</workbook>';
    }

    private function workbookRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function worksheetRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/table" Target="../tables/table1.xml"/>'
            .'</Relationships>';
    }

    private function usageWorksheetRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/table" Target="../tables/table2.xml"/>'
            .'</Relationships>';
    }

    private function tableXml(int $rowCount): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<table xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" id="1" name="StockOSReport" displayName="StockOSReport" ref="A1:H'.$rowCount.'" totalsRowShown="0">'
            .'<autoFilter ref="A1:H'.$rowCount.'"/>'
            .'<tableColumns count="8">'
            .'<tableColumn id="1" name="Nama Material"/>'
            .'<tableColumn id="2" name="Gudang"/>'
            .'<tableColumn id="3" name="Stok (kg)"/>'
            .'<tableColumn id="4" name="Sisa PO (kg)"/>'
            .'<tableColumn id="5" name="Safety Stock (kg)"/>'
            .'<tableColumn id="6" name="Status"/>'
            .'<tableColumn id="7" name="Catatan"/>'
            .'<tableColumn id="8" name="Update"/>'
            .'</tableColumns><tableStyleInfo name="TableStyleMedium2" showFirstColumn="0" showLastColumn="0" showRowStripes="1" showColumnStripes="0"/>'
            .'</table>';
    }

    private function usageTableXml(int $rowCount): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<table xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" id="2" name="StockOSUsageReport" displayName="StockOSUsageReport" ref="A1:F'.$rowCount.'" totalsRowShown="0">'
            .'<autoFilter ref="A1:F'.$rowCount.'"/>'
            .'<tableColumns count="6">'
            .'<tableColumn id="1" name="Tanggal"/>'
            .'<tableColumn id="2" name="Nama Material"/>'
            .'<tableColumn id="3" name="Gudang"/>'
            .'<tableColumn id="4" name="Jumlah Pemakaian (kg)"/>'
            .'<tableColumn id="5" name="Keterangan Pemakaian"/>'
            .'<tableColumn id="6" name="Dicatat Oleh"/>'
            .'</tableColumns><tableStyleInfo name="TableStyleMedium2" showFirstColumn="0" showLastColumn="0" showRowStripes="1" showColumnStripes="0"/>'
            .'</table>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="2"><numFmt numFmtId="164" formatCode="#,##0.### &quot;kg&quot;"/><numFmt numFmtId="165" formatCode="dd/mm/yyyy"/></numFmts>'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF1F4E78"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFD9E2F3"/></left><right style="thin"><color rgb="FFD9E2F3"/></right><top style="thin"><color rgb="FFD9E2F3"/></top><bottom style="thin"><color rgb="FFD9E2F3"/></bottom><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="5">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="165" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>'
            .'</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }
}
