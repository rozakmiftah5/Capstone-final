<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('material_receipts', function (Blueprint $table): void {
            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses', 'warehouse_id')
                ->restrictOnDelete();
        });

        DB::table('material_receipts')
            ->whereNotNull('material_id')
            ->update([
                'warehouse_id' => DB::raw(
                    '(SELECT warehouse_id FROM materials WHERE materials.material_id = material_receipts.material_id)'
                ),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_receipts', function (Blueprint $table): void {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });
    }
};
