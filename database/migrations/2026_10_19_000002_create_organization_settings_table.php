<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row only: the organization this installation belongs to.
        Schema::create('organization_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('membership_prefix', 5);   // used in membership numbers, e.g. LM-2026-0001
            $table->timestamps();
        });

        DB::table('organization_settings')->insert([
            'name' => 'Lizer Media',
            'membership_prefix' => 'LM',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_settings');
    }
};
