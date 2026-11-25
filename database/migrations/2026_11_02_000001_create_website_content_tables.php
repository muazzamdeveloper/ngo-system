<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Menus: the header menu and header buttons join the footer groups.
        Schema::table('site_links', function (Blueprint $table) {
            $table->string('group', 20)->change();
            $table->boolean('new_tab')->default(false)->after('url');
        });

        // Sections of the home page, in order. Each has a type (hero slider, cards, FAQ ...).
        Schema::create('home_sections', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('body')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image2_path')->nullable();
            $table->string('link_label', 60)->nullable();
            $table->string('link_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // The slides, cards, people, questions ... inside a section.
        Schema::create('home_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_section_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('body')->nullable();
            $table->string('image_path')->nullable();
            $table->string('link_label', 60)->nullable();
            $table->string('link_url')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Ordinary pages (About us, Terms ...) written in the admin panel.
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('body')->nullable();      // simple formatting (Markdown), always shown safely
            $table->string('image_path')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        $now = now();
        $link = fn (string $group, string $label, string $url, int $order) => [
            'group' => $group, 'label' => $label, 'url' => $url, 'is_active' => true, 'new_tab' => false,
            'sort_order' => $order, 'created_at' => $now, 'updated_at' => $now,
        ];

        // A starting menu and a starting home page; everything can be changed or removed in the admin panel.
        DB::table('site_links')->insert([
            $link('nav', 'Home', '/', 1),
            $link('nav', 'Apply', '/apply', 2),
            $link('nav', 'Verify', '/verify', 3),
            $link('header_action', 'Become a Member', '/apply', 1),
            $link('header_action', 'Verify Membership', '/verify', 2),
        ]);

        $heroId = DB::table('home_sections')->insertGetId([
            'type' => 'hero', 'title' => 'Hero slider', 'is_active' => true, 'sort_order' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('home_items')->insert([
            'home_section_id' => $heroId, 'title' => 'Welcome', 'body' => 'Edit this slide from the admin panel: Home page.',
            'link_label' => 'Become a Member', 'link_url' => '/apply', 'is_active' => true, 'sort_order' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('home_sections')->insert([
            'type' => 'contact', 'title' => 'Contact us', 'subtitle' => 'We would love to hear from you.',
            'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
        Schema::dropIfExists('home_items');
        Schema::dropIfExists('home_sections');

        DB::table('site_links')->whereIn('group', ['nav', 'header_action'])->delete();

        Schema::table('site_links', function (Blueprint $table) {
            $table->dropColumn('new_tab');
        });
    }
};
