<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sliders', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255)->nullable();
            $table->string('image_path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('gallery_photos', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255)->nullable();
            $table->string('category', 100)->nullable();
            $table->string('image_path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('gallery_videos', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('youtube_url', 500);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->string('group', 20)->default('leadership')->index(); // leadership, governing
            $table->string('name', 150);
            $table->string('designation', 150)->nullable();
            $table->text('message')->nullable();
            $table->string('photo_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['team_members', 'gallery_videos', 'gallery_photos', 'sliders'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
