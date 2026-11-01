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
        Schema::table('material_receipts', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->unsignedBigInteger('supplier_id')->nullable()->change();
            $table->string('supplier_name')->nullable();
            $table->foreign('supplier_id')->references('supplier_id')->on('suppliers')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('material_receipts')->whereNull('supplier_id')->exists()) {
            throw new RuntimeException('Manual vendor receipts must be assigned to a supplier before this migration can be rolled back.');
        }

        Schema::table('material_receipts', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn('supplier_name');
            $table->unsignedBigInteger('supplier_id')->nullable(false)->change();
            $table->foreign('supplier_id')->references('supplier_id')->on('suppliers')->restrictOnDelete();
        });
    }
};
