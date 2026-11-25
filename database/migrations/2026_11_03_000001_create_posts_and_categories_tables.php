<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('slug', 80)->unique();
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();   // author
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();          // cleaned HTML from the editor
            $table->string('featured_image_path')->nullable();
            $table->string('status', 10)->default('draft');   // draft or published
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });

        Schema::create('category_post', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->primary(['category_id', 'post_id']);
        });

        // Like WordPress: every post has at least one category, "Uncategorized" being the default.
        $now = now();
        DB::table('categories')->insert(['name' => 'Uncategorized', 'slug' => 'uncategorized', 'created_at' => $now, 'updated_at' => $now]);

        // How many latest posts a News section on the home page shows, and how many titles feed the header ticker.
        Schema::table('home_sections', function (Blueprint $table) {
            $table->unsignedTinyInteger('item_limit')->default(6);
        });

        Schema::table('organization_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('ticker_posts')->default(0);   // 0 = the ticker shows only the headlines typed by hand
        });

        // A News section on the home page, ready to be moved or hidden.
        DB::table('home_sections')->insert([
            'type' => 'news', 'title' => 'Latest news', 'subtitle' => 'Updates from our work.',
            'is_active' => true, 'item_limit' => 6, 'sort_order' => (int) DB::table('home_sections')->max('sort_order') + 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('home_sections')->where('type', 'news')->delete();

        Schema::table('organization_settings', function (Blueprint $table) {
            $table->dropColumn('ticker_posts');
        });
        Schema::table('home_sections', function (Blueprint $table) {
            $table->dropColumn('item_limit');
        });

        Schema::dropIfExists('category_post');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('categories');
    }
};
