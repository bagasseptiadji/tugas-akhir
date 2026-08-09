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
        if (Schema::hasColumn('devices', 'location')) {
            Schema::table('devices', function (Blueprint $table): void {
                $table->dropColumn('location');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('devices', 'location')) {
            Schema::table('devices', function (Blueprint $table): void {
                $table->string('location')->nullable()->after('name');
            });
        }
    }
};
