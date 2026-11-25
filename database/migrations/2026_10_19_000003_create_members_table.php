<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();

            // Optional login account. Membership and account access are separate things.
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();

            // Assigned when the member is approved (see the status workflow task).
            $table->string('membership_number')->nullable()->unique();

            $table->string('full_name');
            $table->string('father_name');
            $table->date('date_of_birth');
            $table->string('gender', 10);
            $table->string('mobile', 20);
            $table->string('email')->nullable();
            $table->text('address');
            $table->string('city', 100);
            $table->string('state', 100);
            $table->string('pincode', 10);
            $table->string('occupation')->nullable();
            $table->string('photo_path')->nullable();

            // Aadhaar is never stored in plain text.
            $table->text('aadhaar_encrypted')->nullable();          // encrypted full number
            $table->string('aadhaar_last4', 4)->nullable();         // for masked display
            $table->string('aadhaar_hash', 64)->nullable()->unique(); // keyed hash: duplicate check and lookup

            // pending, approved, rejected, blocked, deactivated
            $table->string('status', 20)->default('pending')->index();
            $table->text('status_note')->nullable();                 // reason for the last decision
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('full_name');
            $table->index('mobile');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
