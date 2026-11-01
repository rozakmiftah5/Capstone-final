<?php

namespace App\Services;

use App\Models\MasterMaterial;
use App\Models\PoHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SpreadsheetImportService
{
    public function importMaterials(array $rows): array
    {
        $materials = [];
        $skipped = 0;

        foreach ($rows as $row) {
            $fields = $this->normalizeHeaders($row);
            $code = $this->pick($fields, ['kodematerial', 'kode', 'material', 'materialno', 'nomaterial']);

            if ($code === '') {
                $skipped++;
                continue;
            }

            $materials[$code] = [
                'material_code' => $code,
                'short_text' => $this->pick($fields, ['shorttext', 'description', 'materialdescription', 'deskripsimaterial', 'deskripsi', 'namamaterial']) ?: $code,
                'uom' => $this->nullable($this->pick($fields, ['uom', 'unit', 'satuan', 'baseunit', 'baseunitofmeasure'])),
                'material_group' => $this->nullable($this->pick($fields, ['materialgroup', 'group', 'kelompok'])),
                'material_type' => $this->nullable($this->pick($fields, ['materialtype', 'type', 'jenis'])),
                'valuation_class' => $this->nullable($this->pick($fields, ['valuationclass', 'class', 'valuation'])),
            ];
        }

        if ($materials === []) {
            throw ValidationException::withMessages([
                'file' => 'Tidak ada baris katalog dengan kode material yang valid.',
            ]);
        }

        $now = now()->toDateTimeString();
        DB::transaction(function () use ($materials, $now): void {
            MasterMaterial::query()->delete();

            foreach (array_chunk(array_values($materials), 500) as $chunk) {
                $records = array_map(fn (array $material): array => [
                    ...$material,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $chunk);
                DB::table('master_materials')->insert($records);
            }
        });

        return ['imported' => count($materials), 'skipped' => $skipped];
    }

    public function importPoHistory(array $rows): array
    {
        $records = [];
        $skipped = 0;

        foreach ($rows as $index => $row) {
            $fields = $this->normalizeHeaders($row);
            $poNumber = $this->pick($fields, [
                'purchasingdocument', 'nomorpo', 'ponumber', 'pono', 'nopo', 'po', 'nomororder',
            ]);
            $materialCode = $this->pick($fields, [
                'kodematerial', 'materialcode', 'materialno', 'material', 'kode', 'nomormaterial', 'itemcode',
            ]);

            if ($poNumber === '' || $materialCode === '') {
                $skipped++;
                continue;
            }

            $qty = $this->number($this->pick($fields, [
                'orderquantity', 'qty', 'quantity', 'jumlah', 'kuantitas', 'jumlahpesan',
            ]));
            $unitPrice = $this->number($this->pick($fields, [
                'netprice', 'harga', 'price', 'unitprice', 'hargasatuan', 'hargaperunit',
            ]));
            $rowData = [
                'po_number' => $poNumber,
                'po_date' => $this->excelDate($this->pickRaw($fields, [
                    'documentdate', 'tanggal', 'date', 'podate', 'tgl', 'tanggalpo',
                ])),
                'material_code' => $materialCode,
                'short_text' => $this->nullable($this->pick($fields, [
                    'shorttext', 'description', 'deskripsimaterial', 'deskripsi', 'namamaterial', 'namabarang', 'materialname',
                ])),
                'material_group' => $this->nullable($this->pick($fields, ['materialgroup'])),
                'vendor' => $this->nullable($this->pick($fields, [
                    'suppliersupplyingplant', 'vendor', 'supplier', 'pemasok', 'namavendor', 'namasupplier',
                ])),
                'qty' => $qty,
                'uom' => $this->nullable($this->pick($fields, [
                    'orderunit', 'uom', 'unit', 'satuan', 'baseunit', 'unitofmeasure',
                ])),
                'currency' => $this->pick($fields, ['currency']) ?: 'IDR',
                'unit_price' => $unitPrice,
                'total_price' => $this->number($this->pick($fields, ['netordervalue'])) ?: $qty * $unitPrice,
            ];

            $records[] = [
                ...$rowData,
                'source_key' => hash('sha256', json_encode([$index, $rowData], JSON_THROW_ON_ERROR)),
            ];
        }

        if ($records === []) {
            throw ValidationException::withMessages([
                'file' => 'Tidak ada baris riwayat PO dengan nomor PO dan kode material yang valid.',
            ]);
        }

        $now = now()->toDateTimeString();
        DB::transaction(function () use ($records, $now): void {
            PoHistory::query()->delete();

            foreach (array_chunk($records, 500) as $chunk) {
                $rowsWithTimestamps = array_map(fn (array $record): array => [
                    ...$record,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $chunk);
                DB::table('po_histories')->insert($rowsWithTimestamps);
            }
        });

        return ['imported' => count($records), 'skipped' => $skipped];
    }

    private function normalizeHeaders(array $row): array
    {
        $fields = [];

        foreach ($row as $key => $value) {
            $normalized = mb_strtolower((string) $key);
            $normalized = preg_replace('/[^a-z0-9]/u', '', $normalized) ?? '';
            $fields[$normalized] = $value;
        }

        return $fields;
    }

    private function pick(array $fields, array $keys): string
    {
        $value = $this->pickRaw($fields, $keys);

        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function pickRaw(array $fields, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (isset($fields[$key]) && $fields[$key] !== '') {
                return $fields[$key];
            }
        }

        return null;
    }

    private function nullable(string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    private function number(string $value): float
    {
        $value = preg_replace('/[^\d,.\-]/u', '', trim($value)) ?? '';

        if (str_contains($value, ',') && str_contains($value, '.')) {
            $decimalSeparator = strrpos($value, ',') > strrpos($value, '.') ? ',' : '.';
            $thousandsSeparator = $decimalSeparator === ',' ? '.' : ',';
            $value = str_replace($thousandsSeparator, '', $value);
            $value = str_replace($decimalSeparator, '.', $value);
        } elseif (str_contains($value, ',')) {
            $value = str_replace(',', '.', $value);
        } elseif (preg_match('/\.\d{3}(?:\.|$)/', $value)) {
            $value = str_replace('.', '', $value);
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function excelDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return now()->startOfDay()->setDate(1899, 12, 30)->addDays((int) $value)->toDateString();
        }

        $value = trim((string) $value);
        foreach (['Y-m-d', 'd/m/Y', 'd.m.Y', 'm/d/Y', 'd-m-Y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!' . $format, $value);
            if ($date !== false && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->toDateString();
        } catch (\Exception) {
            throw ValidationException::withMessages([
                'file' => "Tanggal PO tidak dikenali: {$value}.",
            ]);
        }
    }
}
