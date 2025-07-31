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
        Schema::create('attribute_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('source_label')->unique(); // The original Persian label, e.g., "وزن"
            $table->string('magento_attribute_code')->nullable(); // The code admin enters, e.g., "weight_custom"
            $table->string('magento_attribute_type')->default('select'); // 'select', 'text', 'textarea'
            $table->boolean('is_mapped')->default(false); // Status flag
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_mappings');
    }
};
