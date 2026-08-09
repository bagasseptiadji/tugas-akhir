<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('water_quality_thresholds', function (Blueprint $table): void {
            $table->boolean('alert_enabled')->default(true)->after('calibration_multiplier');
            $table->decimal('warning_tolerance', 8, 2)->default(0)->after('alert_enabled');
            $table->decimal('critical_delta', 8, 2)->default(0)->after('warning_tolerance');
        });

        DB::table('water_quality_thresholds')->where('parameter', 'ph')->update(['warning_tolerance' => 0.20, 'critical_delta' => 0.80]);
        DB::table('water_quality_thresholds')->where('parameter', 'temperature_celsius')->update(['warning_tolerance' => 0.50, 'critical_delta' => 2.00]);
        DB::table('water_quality_thresholds')->where('parameter', 'tds_ppm')->update(['warning_tolerance' => 25.00, 'critical_delta' => 100.00]);
    }

    public function down(): void
    {
        Schema::table('water_quality_thresholds', function (Blueprint $table): void {
            $table->dropColumn(['alert_enabled', 'warning_tolerance', 'critical_delta']);
        });
    }
};
