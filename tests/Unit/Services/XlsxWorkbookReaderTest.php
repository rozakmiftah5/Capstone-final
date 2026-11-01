<?php

namespace Tests\Unit\Services;

use App\Services\XlsxWorkbookReader;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class XlsxWorkbookReaderTest extends TestCase
{
    public function test_it_reads_headers_shared_strings_and_inline_cell_values(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'workbook-');
        $archive = new ZipArchive;
        $archive->open($path, ZipArchive::OVERWRITE);
        $archive->addFromString('xl/workbook.xml', <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
                <sheets><sheet name="Materials" sheetId="1" r:id="rId1"/></sheets>
            </workbook>
            XML);
        $archive->addFromString('xl/_rels/workbook.xml.rels', <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
                <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
            </Relationships>
            XML);
        $archive->addFromString('xl/sharedStrings.xml', <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="3" uniqueCount="3">
                <si><t>Material</t></si>
                <si><t>Material Description</t></si>
                <si><r><t>Pompa </t></r><r><t>industri</t></r></si>
            </sst>
            XML);
        $archive->addFromString('xl/worksheets/sheet1.xml', <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
                <sheetData>
                    <row r="1"><c r="A1" t="s"><v>0</v></c><c r="B1" t="s"><v>1</v></c><c r="C1" t="inlineStr"><is><t>Base Unit of Measure</t></is></c></row>
                    <row r="2"><c r="A2"><v>2000000002</v></c><c r="B2" t="s"><v>2</v></c><c r="C2" t="inlineStr"><is><t>EA</t></is></c></row>
                    <row r="3"><c r="A3"><v>2000000003</v></c></row>
                </sheetData>
            </worksheet>
            XML);
        $archive->close();

        try {
            $rows = (new XlsxWorkbookReader)->readFirstSheet($path);
        } finally {
            unlink($path);
        }

        self::assertSame([
            [
                'Material' => '2000000002',
                'Material Description' => 'Pompa industri',
                'Base Unit of Measure' => 'EA',
            ],
            [
                'Material' => '2000000003',
                'Material Description' => '',
                'Base Unit of Measure' => '',
            ],
        ], $rows);
    }
}
