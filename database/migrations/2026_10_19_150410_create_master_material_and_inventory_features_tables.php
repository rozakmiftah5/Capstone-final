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
        Schema::create('master_materials', function (Blueprint $table) {
            $table->id();
            $table->string('material_code')->unique();
            $table->string('short_text');
            $table->string('uom')->nullable();
            $table->string('material_group')->nullable();
            $table->string('material_type')->nullable();
            $table->string('valuation_class')->nullable();
            $table->timestamps();
        });

        Schema::create('po_histories', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->index();
            $table->date('po_date')->nullable()->index();
            $table->string('material_code')->index();
            $table->string('short_text')->nullable();
            $table->string('material_group')->nullable();
            $table->string('vendor')->nullable();
            $table->decimal('qty', 15, 3)->default(0);
            $table->string('uom')->nullable();
            $table->string('currency', 8)->default('IDR');
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('total_price', 18, 2)->default(0);
            $table->string('source_key')->unique();
            $table->timestamps();
        });

        Schema::create('stock_os_items', function (Blueprint $table) {
            $table->id();
            $table->string('material_name');
            $table->string('warehouse_name');
            $table->decimal('stock_qty', 15, 3)->default(0);
            $table->decimal('outstanding_qty', 15, 3)->default(0);
            $table->decimal('safety_stock', 15, 3)->default(0);
            $table->text('notes')->nullable();
            $table->date('last_updated')->nullable();
            $table->timestamps();
            $table->index(['warehouse_name', 'material_name']);
        });

        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_os_item_id')->constrained('stock_os_items')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->string('type', 10);
            $table->decimal('qty', 15, 3);
            $table->string('supplier')->nullable();
            $table->date('transaction_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['stock_os_item_id', 'transaction_date']);
        });

        Schema::create('material_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->string('letter_number', 50)->nullable();
            $table->string('requesting_division', 100)->nullable();
            $table->string('requesting_department', 100)->nullable();
            $table->date('request_date')->nullable();
            $table->string('status', 30)->default('new');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('material_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_request_id')->constrained('material_requests')->cascadeOnDelete();
            $table->string('material_description', 40);
            $table->json('details');
            $table->timestamps();
        });

        Schema::create('price_comparisons', function (Blueprint $table) {
            $table->id();
            $table->string('pr_number')->unique();
            $table->string('procurement_name');
            $table->string('procurement_specification')->nullable();
            $table->string('signature_city')->nullable();
            $table->date('signature_date')->nullable();
            $table->string('officer_name')->nullable();
            $table->string('officer_title')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('vendor_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_comparison_id')->constrained('price_comparisons')->cascadeOnDelete();
            $table->string('material_code')->nullable()->index();
            $table->string('material_description');
            $table->string('vendor_name');
            $table->string('vendor_status', 30)->default('principal');
            $table->decimal('qty', 15, 3);
            $table->decimal('unit_price', 18, 2);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->nullable();
            $table->string('franco')->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('delivery_terms')->nullable();
            $table->timestamps();
        });

        Schema::create('tracking_shipments', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_number')->unique();
            $table->string('receiver_id', 50);
            $table->string('receiver_name');
            $table->string('status', 40)->default('Dalam Perjalanan');
            $table->string('courier');
            $table->date('shipped_at');
            $table->string('package')->nullable();
            $table->decimal('total', 15, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('shipment_journeys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tracking_shipment_id')->constrained('tracking_shipments')->cascadeOnDelete();
            $table->timestamp('occurred_at');
            $table->string('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_journeys');
        Schema::dropIfExists('tracking_shipments');
        Schema::dropIfExists('vendor_offers');
        Schema::dropIfExists('price_comparisons');
        Schema::dropIfExists('material_request_items');
        Schema::dropIfExists('material_requests');
        Schema::dropIfExists('stock_transactions');
        Schema::dropIfExists('stock_os_items');
        Schema::dropIfExists('po_histories');
        Schema::dropIfExists('master_materials');
    }
};
