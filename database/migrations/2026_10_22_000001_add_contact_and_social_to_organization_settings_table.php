<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_settings', function (Blueprint $table) {
            $table->string('phone', 30)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('address', 300)->nullable();             // head office
            $table->string('address_secondary', 300)->nullable();   // registered office
            $table->string('working_hours', 120)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('ticker_label', 30)->nullable();         // label of the news ticker; default "Latest news"
        });
    }

    public function down(): void
    {
        Schema::table('organization_settings', function (Blueprint $table) {
            $table->dropColumn(['phone', 'email', 'address', 'address_secondary', 'working_hours', 'whatsapp',
                'facebook_url', 'instagram_url', 'youtube_url', 'ticker_label']);
        });
    }
};
