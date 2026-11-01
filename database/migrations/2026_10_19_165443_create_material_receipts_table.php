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
        Schema::create('material_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials', 'material_id')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers', 'supplier_id')->restrictOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('unit');
            $table->date('received_at');
            $table->string('purchase_order_number')->nullable();
            $table->string('delivery_note_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['received_at', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_receipts');
    }
};
