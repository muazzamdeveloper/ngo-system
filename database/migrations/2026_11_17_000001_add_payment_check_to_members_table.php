<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // Result of the staff check of the application-fee payment screenshot.
            // NULL = not checked yet (shown as "Pending" when a screenshot exists).
            $table->string('payment_status', 20)->nullable()->index();
            $table->string('payment_note')->nullable();
            $table->timestamp('payment_checked_at')->nullable();
            $table->unsignedBigInteger('payment_checked_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropColumn(['payment_status', 'payment_note', 'payment_checked_at', 'payment_checked_by']);
        });
    }
};
