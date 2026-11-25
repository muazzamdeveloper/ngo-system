<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->string('membership_level', 40)->index();
            $table->string('name', 150);
            $table->unsignedInteger('sort_order')->default(1);
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 170)->unique();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('volunteers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 150);
            $table->string('email', 190)->nullable();
            $table->string('mobile', 20);
            $table->string('city', 120)->nullable();
            $table->string('state', 120)->nullable();
            $table->string('interests', 500)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('pending')->index(); // pending, approved, rejected
            $table->string('source', 20)->default('admin'); // admin, public
            $table->dateTime('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['volunteers', 'membership_plans', 'designations'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
