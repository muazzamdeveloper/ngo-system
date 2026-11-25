<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_no', 30)->unique();
            $table->string('member_name', 150);
            $table->string('designation', 150)->nullable();
            $table->date('issue_date');
            $table->date('valid_until')->nullable(); // null = lifetime
            $table->string('status', 20)->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('appointment_letters', function (Blueprint $table) {
            $table->id();
            $table->string('letter_no', 30)->unique();
            $table->string('recipient_name', 150);
            $table->string('designation', 150)->nullable();
            $table->date('issue_date');
            $table->date('valid_until')->nullable();
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('template_assets', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->string('path')->nullable();
            $table->string('original_name', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['template_assets', 'appointment_letters', 'membership_certificates'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
