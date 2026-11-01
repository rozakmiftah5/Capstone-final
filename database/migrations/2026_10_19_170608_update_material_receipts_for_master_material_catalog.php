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
            $table->dropForeign(['material_id']);
            $table->unsignedBigInteger('material_id')->nullable()->change();
            $table->string('material_code')->nullable();
            $table->string('material_name')->nullable();
            $table->foreign('material_id')->references('material_id')->on('materials')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('material_receipts')->whereNull('material_id')->exists()) {
            throw new RuntimeException('Catalog-only receipts must be assigned to Data Material before this migration can be rolled back.');
        }

        Schema::table('material_receipts', function (Blueprint $table): void {
            $table->dropForeign(['material_id']);
            $table->dropColumn(['material_code', 'material_name']);
            $table->unsignedBigInteger('material_id')->nullable(false)->change();
            $table->foreign('material_id')->references('material_id')->on('materials')->restrictOnDelete();
        });
    }
};
