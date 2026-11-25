<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('blood_group', 3)->nullable()->after('gender');
            $table->string('whatsapp', 20)->nullable()->after('mobile');
            $table->string('district', 100)->nullable()->after('city');
            $table->string('work_area')->nullable()->after('district');

            // Uploaded documents. Files live on the private disk, never in a public folder.
            $table->string('aadhaar_front_path')->nullable()->after('photo_path');
            $table->string('aadhaar_back_path')->nullable()->after('aadhaar_front_path');
            $table->string('payment_screenshot_path')->nullable()->after('aadhaar_back_path');
            $table->string('resume_path')->nullable()->after('payment_screenshot_path');

            // Where the record came from: added by staff, or sent through the public form.
            $table->string('source', 10)->default('admin')->after('status_note');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn([
                'blood_group', 'whatsapp', 'district', 'work_area',
                'aadhaar_front_path', 'aadhaar_back_path', 'payment_screenshot_path', 'resume_path',
                'source',
            ]);
        });
    }
};
