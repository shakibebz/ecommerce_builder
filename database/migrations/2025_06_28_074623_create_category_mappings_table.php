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
        Schema::create('category_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('source_name')->unique(); // The original category name, e.g., "عینک آفتابی"
            $table->unsignedBigInteger('magento_category_id')->nullable(); // The ID from Magento, e.g., 12
            $table->boolean('is_mapped')->default(false); // Status flag
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_mappings');
    }
};
