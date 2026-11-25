<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row only: everything the ID card, the joining letter and the public
        // application page need from the organization (images, fee, texts).
        Schema::create('card_settings', function (Blueprint $table) {
            $table->id();

            // Images live on the private disk. Only the payment QR is shown publicly.
            $table->string('logo_path')->nullable();
            $table->string('payment_qr_path')->nullable();
            $table->string('website_qr_path')->nullable();
            $table->string('stamp_path')->nullable();

            // Application fee. Empty (or 0) means no fee text is shown above the QR.
            $table->decimal('fee_amount', 10, 2)->nullable();
            $table->string('fee_currency', 10)->default('₹');

            // ID card
            $table->string('card_heading', 30)->default('PRESS');
            $table->string('card_designation', 60)->default('Reporter');
            $table->unsignedSmallInteger('card_validity_months')->default(12);
            $table->string('card_aadhaar_display', 10)->default('full');   // full, masked or hidden
            $table->text('card_terms');                                    // one term per line
            $table->string('udyam_number', 50)->nullable();
            $table->string('website_url')->nullable();

            // Joining letter
            $table->string('letter_site_title', 100)->nullable();
            $table->string('letter_signatory_name', 100)->nullable();
            $table->string('letter_signatory_role', 60)->default('Editor in Chief');
            $table->string('letter_address', 300)->nullable();
            $table->string('letter_phone', 30)->nullable();
            $table->string('letter_email')->nullable();
            $table->date('letter_date')->nullable();            // empty = date the letter is generated
            $table->date('letter_joining_date')->nullable();    // empty = date of approval
            $table->mediumText('letter_template');

            $table->timestamps();
        });

        $defaults = [
            'card_terms' => implode("\n", [
                'This card is non-transferable.',
                'In case of loss, please contact our office or the nearest police station.',
                'If the card holder is involved in any illegal, criminal or anti-social activity, the card is cancelled immediately and the card holder alone is responsible.',
                'The card holder may use this card only for news collection.',
                'The organization is not responsible for any anti-social or criminal activity done by the card holder.',
            ]),
            'letter_template' => '<p>Dear {full_name},</p>'
                . '<p>We are pleased to inform you that you have been selected for the position of <strong>{designation}</strong> at {site_title}. We are excited to have you join our team and look forward to working with you.</p>'
                . '<p>Your official joining date will be {joining_date}. You are expected to report to our office at 9:00 AM on the first day. Please bring the following documents with you:</p>'
                . '<ol><li>Original educational certificates</li><li>A copy of your Aadhaar card</li><li>Recent passport size photographs (2)</li><li>Bank account details</li></ol>'
                . '<p>As a {designation}, you will be responsible for gathering, researching and reporting news content in line with our editorial standards. We believe your skills and enthusiasm will be a great addition to our team.</p>'
                . '<p>If you have any questions, feel free to contact us at the number or email provided below.</p>'
                . '<p>Once again, welcome to {site_title}. We look forward to a successful journey together.</p>',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('card_settings')->insert($defaults);
    }

    public function down(): void
    {
        Schema::dropIfExists('card_settings');
    }
};
