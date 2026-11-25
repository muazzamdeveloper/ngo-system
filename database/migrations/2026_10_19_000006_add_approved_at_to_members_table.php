<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // Set once, at the first approval, and never changed afterwards.
            $table->timestamp('approved_at')->nullable()->after('reviewed_at')->index();
        });

        // Members approved before this column existed: use the last decision date as the best guess.
        DB::table('members')
            ->whereNotNull('membership_number')
            ->whereNull('approved_at')
            ->update(['approved_at' => DB::raw('reviewed_at')]);
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('approved_at');
        });
    }
};
