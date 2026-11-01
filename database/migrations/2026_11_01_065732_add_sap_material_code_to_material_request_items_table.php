<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('material_request_items', function (Blueprint $table): void {
            $table->string('sap_material_code', 40)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_request_items', function (Blueprint $table): void {
            $table->dropColumn('sap_material_code');
        });
    }
};
