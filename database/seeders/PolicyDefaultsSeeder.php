<?php

namespace Database\Seeders;

use App\Models\Policy;
use App\Models\PolicySection;
use Illuminate\Database\Seeder;

/**
 * Default policy text, shown until the admin edits it under Policies.
 * Only adds what is missing: a policy that already has sections is never changed.
 * The wording is a starting draft and should be checked before publishing.
 */
class PolicyDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->defaults() as $type => $policy) {
            $row = Policy::query()->firstOrCreate(
                ['type' => $type],
                ['title' => $policy['title'], 'is_published' => true],
            );

            if ($row->sections()->exists()) {
                continue;
            }

            foreach ($policy['sections'] as $order => [$title, $icon, $content]) {
                PolicySection::query()->create([
                    'policy_id' => $row->id,
                    'title' => $title,
                    'icon' => $icon,
                    'content' => $content,
                    'sort_order' => $order + 1,
                    'status' => 'active',
                ]);
            }
        }
    }

    private function defaults(): array
    {
        return [
            'privacy' => ['title' => 'Privacy Policy', 'sections' => [
                ['Our Commitment to Privacy', 'fa-user-shield', '<p>Sarv Abhyudaya Foundation is committed to protecting the privacy and personal information of our donors, volunteers, members and beneficiaries. This policy explains how we collect, use and protect that information.</p>'],
                ['Information We Collect', 'fa-database', '<p>We collect basic personal information such as name, contact details (email and phone), PAN number where required for 80G tax exemption, and address when you donate, register as a member or volunteer with us. Payment information is processed securely and is not stored on our servers.</p>'],
                ['How We Use Your Information', 'fa-gears', '<p>Your information is used to process donations, issue 80G tax exemption certificates, send updates about our programmes and campaigns, and keep records required by law. We do not sell, rent or share your personal data with third parties for commercial purposes.</p>'],
                ['Data Security', 'fa-lock', '<p>We use technical and organisational measures to protect your data from unauthorised access, change or disclosure. Access to donor and member records is limited to authorised staff at our Registered Office.</p>'],
            ]],
            'terms' => ['title' => 'Terms & Conditions', 'sections' => [
                ['Acceptance of Terms', 'fa-file-signature', '<p>By using this website or donating to Sarv Abhyudaya Foundation, you agree to these Terms and Conditions. Please read them before making a donation or registering.</p>'],
                ['Use of Donation Funds', 'fa-hand-holding-heart', '<p>Donations are used for charitable activities, social welfare programmes and the administrative costs needed to run them. The use of funds is decided by the management of the Foundation.</p>'],
                ['Intellectual Property', 'fa-copyright', '<p>All content, logos, images and materials on this website belong to Sarv Abhyudaya Foundation. They may not be reproduced or used commercially without written permission.</p>'],
                ['Governing Law', 'fa-scale-balanced', '<p>These terms are governed by the laws of India. Any dispute arising from the use of this website or our services is subject to the courts of New Delhi.</p>'],
                ['Contact Information', 'fa-envelope', '<p>For questions about these terms, contact Sarv Abhyudaya Foundation at C-42, First Floor, Jawahar Park, Khanpur, New Delhi – 110062. Email: special@portalwale.com.</p>'],
            ]],
            'refund' => ['title' => 'Refund & Cancellation Policy', 'sections' => [
                ['Donation Refund Policy', 'fa-rotate-left', '<p>Donations to Sarv Abhyudaya Foundation are voluntary and are intended for charitable use. We handle refund requests fairly and openly, and we acknowledge that technical errors can occur.</p>'],
                ['Exceptional Circumstances', 'fa-circle-check', '<p>Refunds may be considered for duplicate transactions, accidental double payments or unauthorised card use. Requests must be sent within 7 days of the transaction, with supporting documents.</p>'],
                ['Refund Request Process', 'fa-list-check', '<p>To request a refund, email special@portalwale.com or call +91 9818398199 with your transaction ID, the donation date and proof of payment. Requests are reviewed within 7–10 working days.</p>'],
                ['Donations With an 80G Certificate', 'fa-file-circle-check', '<p>Donations for which an 80G tax exemption certificate has already been issued are not eligible for refund under government financial rules. Please review your transaction before submitting a donation.</p>'],
            ]],
            'shipping' => ['title' => 'Shipping & Delivery Policy', 'sections' => [
                ['Nature of Services', 'fa-truck', '<p>Sarv Abhyudaya Foundation is a non-profit social welfare organisation and does not sell physical goods. This policy covers the delivery of donation receipts, membership certificates and related documents.</p>'],
                ['Donation Receipts', 'fa-receipt', '<p>Digital receipts are sent immediately or within 24 hours of a confirmed donation. Membership ID cards are dispatched within 7–10 working days.</p>'],
                ['Tax Exemption Certificates', 'fa-file-invoice', '<p>80G tax exemption certificates are issued annually or on request. Digital copies are sent by email. Printed copies, if requested, are sent by standard courier from our Registered Office.</p>'],
                ['Delivery Timelines', 'fa-clock', '<p>Physical certificates, where applicable, are dispatched within 10–15 working days from the Registered Office. Delays caused by courier services are outside our control.</p>'],
            ]],
            'disclaimer' => ['title' => 'Legal Disclaimer', 'sections' => [
                ['General Information', 'fa-circle-info', '<p>The information on this website is provided for general information only. It is provided in good faith, but we make no representation about its accuracy, completeness or suitability for any purpose.</p>'],
                ['External Links', 'fa-link', '<p>This website may link to other websites. We do not control those sites and are not responsible for their content or practices.</p>'],
                ['Professional Disclaimer', 'fa-user-doctor', '<p>Content on this website is not medical, legal or financial advice. Please consult a qualified professional before acting on any information here.</p>'],
                ['Limitation of Liability', 'fa-triangle-exclamation', '<p>To the fullest extent permitted by law, Sarv Abhyudaya Foundation is not liable for any loss arising from the use of this website or the information on it.</p>'],
            ]],
        ];
    }
}
