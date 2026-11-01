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
        Schema::create('materials', function (Blueprint $table) {
            $table->id('material_id');
            $table->string('material_code')->unique();
            $table->string('material_name');
            $table->string('material_type')->nullable();
            $table->integer('material_stock')->default(0);
            $table->integer('sisa_po')->default(0);
            $table->integer('safety_stock')->default(0);
            $table->string('status')->default('active');
            $table->string('category');
            $table->string('unit');
            $table->unsignedBigInteger('warehouse_id');
            $table->foreign('warehouse_id')->references('warehouse_id')->on('warehouses')->cascadeOnDelete();
            $table->string('valuation_class')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
