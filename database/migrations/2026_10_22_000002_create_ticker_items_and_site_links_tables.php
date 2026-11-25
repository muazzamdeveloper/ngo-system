<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Headlines that slide across the top of every public page.
        Schema::create('ticker_items', function (Blueprint $table) {
            $table->id();
            $table->string('text', 160);
            $table->string('url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Footer links: quick links, action buttons and the small legal links.
        Schema::create('site_links', function (Blueprint $table) {
            $table->id();
            $table->string('group', 10);          // quick, action, legal
            $table->string('label', 60);
            $table->string('url');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['group', 'is_active']);
        });

        // Only pages that already exist, so no link starts out broken. More can be added from the admin panel.
        $now = now();
        DB::table('site_links')->insert([
            ['group' => 'quick', 'label' => 'Apply for membership', 'url' => '/apply', 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'quick', 'label' => 'Verify membership', 'url' => '/verify', 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'action', 'label' => 'Member Apply', 'url' => '/apply', 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'action', 'label' => 'Member Verify', 'url' => '/verify', 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_links');
        Schema::dropIfExists('ticker_items');
    }
};
