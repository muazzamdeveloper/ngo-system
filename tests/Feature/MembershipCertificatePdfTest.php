<?php

namespace Tests\Feature;

use App\Models\MembershipCertificate;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\MembershipCertificatePdf;
use Database\Seeders\AdminPermissionsSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipCertificatePdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(AdminPermissionsSeeder::class);
    }

    /** A staff account that can open the admin panel and the membership certificates list (nothing else). */
    private function certificateStaff(array $permissions = ['admin.access', 'certs.membership']): User
    {
        $user = new User();
        $user->forceFill([
            'name' => 'Certificate staff',
            'email' => 'cert-' . uniqid() . '@example.test',
            'password' => 'Password123!',
            'is_active' => true,
            'is_super_admin' => false,
            'role_id' => Role::where('name', 'coordinator')->value('id'),
        ])->save();
        $user->directPermissions()->sync(Permission::whereIn('name', $permissions)->pluck('id'));

        return $user;
    }

    private function certificate(array $overrides = []): MembershipCertificate
    {
        return MembershipCertificate::create(array_merge([
            'member_name' => 'Asha Devi',
            'designation' => 'Volunteer',
            'issue_date' => '2026-11-01',
            'valid_until' => null,
            'status' => 'active',
        ], $overrides));
    }

    public function test_certificate_html_shows_name_number_designation_and_lifetime_validity(): void
    {
        $certificate = $this->certificate();

        $html = app(MembershipCertificatePdf::class)->html($certificate);

        $this->assertStringContainsString('Asha Devi', $html);
        $this->assertStringContainsString($certificate->certificate_no, $html);
        $this->assertStringContainsString('Volunteer', $html);
        $this->assertStringContainsString('Lifetime', $html);
        $this->assertStringContainsString('Certificate of Membership', $html);
    }

    public function test_certificate_html_shows_the_valid_until_date_when_set(): void
    {
        $certificate = $this->certificate(['valid_until' => '2027-10-31']);

        $html = app(MembershipCertificatePdf::class)->html($certificate);

        $this->assertStringContainsString('October 31, 2027', $html);
        $this->assertStringNotContainsString('Lifetime', $html);
    }

    public function test_list_shows_a_download_link_for_each_certificate(): void
    {
        $certificate = $this->certificate();

        $this->actingAs($this->certificateStaff())
            ->get(route('modules.index', 'membership-certificates'))
            ->assertOk()
            ->assertSee(route('certificates.pdf', $certificate), false)
            ->assertSee('Download PDF');
    }

    public function test_download_explains_the_missing_pdf_library_instead_of_failing(): void
    {
        if (class_exists(\Dompdf\Dompdf::class)) {
            $this->markTestSkipped('dompdf is installed; the download itself is used instead.');
        }

        $certificate = $this->certificate();

        $this->actingAs($this->certificateStaff())
            ->get(route('certificates.pdf', $certificate))
            ->assertRedirect()
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'composer require dompdf/dompdf'));
    }

    public function test_staff_without_the_certificate_permission_cannot_download(): void
    {
        $certificate = $this->certificate();

        $this->actingAs($this->certificateStaff(['admin.access']))
            ->get(route('certificates.pdf', $certificate))
            ->assertForbidden();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $certificate = $this->certificate();

        $this->get(route('certificates.pdf', $certificate))->assertRedirect('/login');
    }
}
