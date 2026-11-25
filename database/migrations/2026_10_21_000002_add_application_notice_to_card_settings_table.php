<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('card_settings', function (Blueprint $table) {
            // The yellow note under the public application form. Empty means the default text.
            $table->text('application_notice')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('card_settings', function (Blueprint $table) {
            $table->dropColumn('application_notice');
        });
    }
};
