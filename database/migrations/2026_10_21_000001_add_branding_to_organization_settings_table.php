<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_settings', function (Blueprint $table) {
            $table->string('site_title', 100)->nullable()->after('name');       // browser tab title; the name is used when empty
            $table->string('tagline', 160)->nullable()->after('site_title');
            $table->string('primary_color', 7)->nullable()->after('tagline');   // e.g. #1f6f5c; the default green is used when empty
            $table->string('logo_path')->nullable()->after('primary_color');    // private disk
            $table->string('favicon_path')->nullable()->after('logo_path');     // private disk
        });
    }

    public function down(): void
    {
        Schema::table('organization_settings', function (Blueprint $table) {
            $table->dropColumn(['site_title', 'tagline', 'primary_color', 'logo_path', 'favicon_path']);
        });
    }
};
