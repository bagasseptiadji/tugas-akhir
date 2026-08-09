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
        Schema::create('water_quality_thresholds', function (Blueprint $table) {
            $table->id();
            $table->string('parameter')->unique();
            $table->string('label');
            $table->string('unit')->nullable();
            $table->decimal('min_value', 8, 2)->nullable();
            $table->decimal('max_value', 8, 2)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('water_quality_thresholds');
    }
};
