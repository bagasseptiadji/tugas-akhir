<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('water_quality_thresholds', function (Blueprint $table): void {
            $table->decimal('calibration_offset', 8, 2)->default(0)->after('max_value');
            $table->decimal('calibration_multiplier', 8, 4)->default(1)->after('calibration_offset');
        });
    }

    public function down(): void
    {
        Schema::table('water_quality_thresholds', function (Blueprint $table): void {
            $table->dropColumn(['calibration_offset', 'calibration_multiplier']);
        });
    }
};
