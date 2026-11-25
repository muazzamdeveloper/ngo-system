<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Editable content of the public pages defined in config/site_pages.php (text, rich text and images). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('page_key', 60);
            $table->string('block_key', 80);
            $table->text('value')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
            $table->unique(['page_key', 'block_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_blocks');
    }
};
