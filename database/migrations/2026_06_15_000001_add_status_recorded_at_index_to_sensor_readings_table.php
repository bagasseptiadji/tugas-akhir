<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sensor_readings', function (Blueprint $table): void {
            $table->index(['quality_status', 'recorded_at'], 'sensor_readings_status_recorded_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('sensor_readings', function (Blueprint $table): void {
            $table->dropIndex('sensor_readings_status_recorded_at_index');
        });
    }
};
