<?php

namespace App\Console\Commands;

use App\Services\SpreadsheetImportService;
use App\Services\XlsxWorkbookReader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncReferenceWorkbooks extends Command
{
    protected $signature = 'reference-data:sync {--force : Replace the current material catalog and PO history}';

    protected $description = 'Import material catalog and purchase-order data from Project-MM-main workbooks';

    public function handle(XlsxWorkbookReader $reader, SpreadsheetImportService $importer): int
    {
        $directory = base_path('Project-MM-main/data');
        $materialsPath = $directory . DIRECTORY_SEPARATOR . 'material list.xlsx';
        $purchaseOrdersPath = $directory . DIRECTORY_SEPARATOR . 'Riwayat PO.xlsx';

        if (! is_file($materialsPath) || ! is_file($purchaseOrdersPath)) {
            $this->error("Workbook material dan PO harus tersedia di {$directory}.");

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Sinkronisasi ini akan mengganti seluruh katalog material dan riwayat PO yang tersimpan. Lanjutkan?')) {
            $this->warn('Sinkronisasi dibatalkan.');

            return self::SUCCESS;
        }

        try {
            $materials = $reader->readFirstSheet($materialsPath);
            $purchaseOrders = $reader->readFirstSheet($purchaseOrdersPath);

            $summary = DB::transaction(fn (): array => [
                'materials' => $importer->importMaterials($materials),
                'purchase_orders' => $importer->importPoHistory($purchaseOrders),
            ]);
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('Sinkronisasi gagal dan perubahan database dibatalkan: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Sinkronisasi selesai: %s material dan %s baris PO tersimpan.',
            number_format($summary['materials']['imported']),
            number_format($summary['purchase_orders']['imported']),
        ));
        if ($summary['materials']['skipped'] > 0 || $summary['purchase_orders']['skipped'] > 0) {
            $this->warn(sprintf(
                'Baris dilewati: %s material, %s PO.',
                number_format($summary['materials']['skipped']),
                number_format($summary['purchase_orders']['skipped']),
            ));
        }

        return self::SUCCESS;
    }
}
