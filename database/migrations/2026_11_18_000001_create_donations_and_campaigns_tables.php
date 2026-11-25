<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->string('short_description', 500)->nullable();
            $table->longText('description')->nullable();
            $table->string('image_path')->nullable();
            $table->decimal('goal_amount', 12, 2)->default(0);
            $table->string('status', 20)->default('active')->index();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no', 40)->nullable()->unique();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->string('donor_name', 150);
            $table->string('donor_email', 190)->nullable();
            $table->string('donor_phone', 20)->nullable();
            $table->string('donor_pan', 10)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('method', 20)->default('upi');   // online, upi, bank, cash, cheque
            $table->string('status', 20)->default('pending')->index(); // pending, completed, failed, cancelled
            $table->string('source', 20)->default('admin'); // admin, public
            $table->string('transaction_ref', 120)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('donated_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
        Schema::dropIfExists('campaigns');
    }
};
