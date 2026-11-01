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
        Schema::dropIfExists('shipment_journeys');
        Schema::dropIfExists('tracking_shipments');
        Schema::dropIfExists('vendor_offers');
        Schema::dropIfExists('price_comparisons');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
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
};
