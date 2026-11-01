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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id('po_id');
            $table->string('po_number')->unique();
            $table->date('po_date');
            $table->unsignedBigInteger('supplier_id');
            $table->foreign('supplier_id')->references('supplier_id')->on('suppliers')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('item_purchase_orders', function (Blueprint $table) {
            $table->id('item_po_id');
            $table->unsignedBigInteger('po_id');
            $table->foreign('po_id')->references('po_id')->on('purchase_orders')->cascadeOnDelete();
            $table->unsignedBigInteger('material_id');
            $table->foreign('material_id')->references('material_id')->on('materials')->cascadeOnDelete();
            $table->integer('qty');
            $table->string('unit');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('purchase_orders');
    }
};
