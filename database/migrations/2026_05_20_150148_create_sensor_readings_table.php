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
        Schema::create('sensor_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->decimal('ph', 4, 2);
            $table->decimal('temperature_celsius', 5, 2);
            $table->unsignedInteger('tds_ppm');
            $table->string('quality_status')->default('normal');
            $table->json('raw_payload')->nullable();
            $table->timestamp('recorded_at')->index();
            $table->timestamps();

            $table->index(['device_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_readings');
    }
};
