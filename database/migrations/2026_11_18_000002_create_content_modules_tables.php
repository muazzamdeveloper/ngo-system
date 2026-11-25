<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->string('status', 20)->default('active')->index();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('image_path')->nullable();
            $table->longText('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->date('event_date');
            $table->time('event_time')->nullable();
            $table->string('location', 255)->nullable();
            $table->string('image_path')->nullable();
            $table->longText('description')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('press_releases', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->date('release_date')->nullable();
            $table->string('excerpt', 500)->nullable();
            $table->longText('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('awards', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->date('award_date')->nullable();
            $table->string('issuer', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('role', 150)->nullable();
            $table->text('message');
            $table->unsignedTinyInteger('rating')->default(5);
            $table->string('photo_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('logo_path')->nullable();
            $table->string('website_url', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('category', 60)->default('Other');
            $table->string('file_path');
            $table->string('original_name', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['legal_documents', 'partners', 'testimonials', 'awards', 'press_releases', 'events', 'projects'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
