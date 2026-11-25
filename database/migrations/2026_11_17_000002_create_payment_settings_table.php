<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row only. Donation settings live here (the application-fee QR stays in Card Settings).
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            $table->string('donation_qr_path')->nullable();   // private disk
            $table->string('donation_upi_id', 100)->nullable();
            $table->string('donation_instructions', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};
