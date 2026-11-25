<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30)->unique(); // privacy, terms, refund, shipping, disclaimer
            $table->string('title', 255);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('policy_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('policies')->cascadeOnDelete();
            $table->string('title', 255);
            $table->string('icon', 60)->nullable();
            $table->longText('content');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        // Key/value store for site configuration, SMTP, themes, domain and payment gateway settings.
        // Secret values (passwords, API secrets) are stored encrypted by App\Models\SiteSetting.
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();
            $table->longText('value')->nullable();
            $table->boolean('is_secret')->default(false);
            $table->timestamps();
        });

        Schema::create('email_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('subject', 255);
            $table->text('body');
            $table->string('category', 30)->default('announcement');
            $table->string('audience', 40)->default('approved_members');
            $table->string('action_url', 500)->nullable();
            $table->boolean('via_smtp')->default(true);
            $table->unsignedInteger('recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['email_notifications', 'site_settings', 'policy_sections', 'policies'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
