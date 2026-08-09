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
        Schema::table('devices', function (Blueprint $table) {
            $table->integer('wifi_rssi')->nullable()->after('last_seen_at');
            $table->unsignedBigInteger('uptime_seconds')->nullable()->after('wifi_rssi');
            $table->string('firmware_version')->nullable()->after('uptime_seconds');
            $table->string('sensor_status')->nullable()->after('firmware_version');
            $table->string('power_status')->nullable()->after('sensor_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'wifi_rssi',
                'uptime_seconds',
                'firmware_version',
                'sensor_status',
                'power_status',
            ]);
        });
    }
};
