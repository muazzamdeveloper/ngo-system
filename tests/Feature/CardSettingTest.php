<?php

namespace Tests\Feature;

use App\Models\CardSetting;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CardSettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    private function makeUser(string $roleName, bool $super = false): User
    {
        $user = new User();
        $user->forceFill([
            'name' => 'User ' . $roleName,
            'email' => $roleName . '@example.test',
            'password' => 'Password123!',
            'is_active' => true,
            'is_super_admin' => $super,
            'role_id' => Role::where('name', $roleName)->value('id'),
        ])->save();

        return $user;
    }

    /** A staff user whose role holds only the given permissions (plus admin access). */
    private function userWith(array $permissions): User
    {
        $role = Role::create(['name' => 'role-' . uniqid(), 'label' => 'Custom', 'is_system' => false]);
        $role->permissions()->sync(Permission::whereIn('name', ['admin.access', ...$permissions])->pluck('id'));

        $user = new User();
        $user->forceFill([
            'name' => 'Custom user',
            'email' => uniqid() . '@example.test',
            'password' => 'Password123!',
            'is_active' => true,
            'is_super_admin' => false,
            'role_id' => $role->id,
        ])->save();

        return $user;
    }

    private ?User $adminUser = null;

    private function admin(): User
    {
        return $this->adminUser ??= $this->makeUser('super-admin', true);
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'fee_amount' => '500',
            'fee_currency' => '₹',
            'card_heading' => 'PRESS',
            'card_designation' => 'Reporter',
            'card_validity_months' => 12,
            'card_aadhaar_display' => 'full',
            'card_terms' => "First term\nSecond term",
            'udyam_number' => 'UDYAM-CG-14-0058352',
            'website_url' => 'https://example.test',
            'letter_site_title' => 'Example Media',
            'letter_signatory_name' => 'A. Editor',
            'letter_signatory_role' => 'Editor in Chief',
            'letter_address' => "12 Park Road\nPune",
            'letter_phone' => '+91 98765 43210',
            'letter_email' => 'hr@example.test',
            'letter_date' => '',
            'letter_joining_date' => '',
            'letter_template' => '<p>Dear {full_name}, welcome to {site_title}.</p>',
        ], $overrides);
    }

    private function png(string $name = 'logo.png', int $w = 200, int $h = 100): UploadedFile
    {
        return UploadedFile::fake()->image($name, $w, $h);
    }

    // ---------- Defaults ----------

    public function test_default_row_exists_with_the_default_values(): void
    {
        $row = CardSetting::query()->firstOrFail();
        $defaults = CardSetting::defaults();

        $this->assertSame(1, CardSetting::count());
        $this->assertSame('PRESS', $row->card_heading);
        $this->assertSame('Reporter', $row->card_designation);
        $this->assertSame(12, $row->card_validity_months);
        $this->assertSame('full', $row->card_aadhaar_display);
        $this->assertSame($defaults['card_terms'], $row->card_terms);
        $this->assertSame($defaults['letter_template'], $row->letter_template);
        $this->assertNull($row->fee_amount);
    }

    public function test_default_letter_template_only_uses_known_placeholders(): void
    {
        preg_match_all('/\{([A-Za-z_]+)\}/', CardSetting::defaults()['letter_template'], $found);

        $this->assertSame([], array_diff($found[1], array_keys(CardSetting::PLACEHOLDERS)));
    }

    // ---------- Access ----------

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/admin/card-settings')->assertRedirect('/login');
        $this->put('/admin/card-settings', $this->validData())->assertRedirect('/login');
    }

    public function test_only_super_admin_or_holders_of_the_permission_get_in(): void
    {
        foreach (['manager', 'coordinator'] as $role) {
            $user = $this->makeUser($role);
            $this->actingAs($user)->get('/admin/card-settings')->assertForbidden();
            $this->actingAs($user)->put('/admin/card-settings', $this->validData())->assertForbidden();
        }

        // settings.manage (organization settings) is a different permission.
        $this->actingAs($this->userWith(['settings.manage']))->get('/admin/card-settings')->assertForbidden();

        $this->actingAs($this->userWith(['card_settings.manage']))->get('/admin/card-settings')->assertOk();
        $this->actingAs($this->admin())->get('/admin/card-settings')->assertOk()->assertSee('Card settings');
    }

    public function test_menu_link_follows_the_permission(): void
    {
        $this->actingAs($this->makeUser('manager'))->get('/admin/dashboard')->assertDontSee('/admin/card-settings');
        $this->actingAs($this->admin())->get('/admin/dashboard')->assertSee('/admin/card-settings');
    }

    public function test_new_permission_is_not_given_to_default_roles(): void
    {
        foreach (['manager', 'coordinator', 'member'] as $role) {
            $this->assertFalse(
                Role::where('name', $role)->first()->permissions()->where('name', 'card_settings.manage')->exists()
            );
        }
    }

    // ---------- Saving ----------

    public function test_settings_are_saved(): void
    {
        $this->actingAs($this->admin())
            ->put('/admin/card-settings', $this->validData([
                'card_heading' => 'MEMBER',
                'card_designation' => 'Member',
                'card_validity_months' => 24,
                'card_aadhaar_display' => 'masked',
                'letter_date' => '2026-11-01',
                'letter_joining_date' => '2026-11-15',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/admin/card-settings');

        $row = CardSetting::first();
        $this->assertSame(1, CardSetting::count());
        $this->assertSame('MEMBER', $row->card_heading);
        $this->assertSame('Member', $row->card_designation);
        $this->assertSame(24, $row->card_validity_months);
        $this->assertSame('masked', $row->card_aadhaar_display);
        $this->assertSame('UDYAM-CG-14-0058352', $row->udyam_number);
        $this->assertSame('2026-11-01', $row->letter_date->format('Y-m-d'));
        $this->assertSame('2026-11-15', $row->letter_joining_date->format('Y-m-d'));
        $this->assertNull(CardSetting::first()->logo_path);
    }

    public function test_saved_values_are_shown_in_the_form(): void
    {
        $this->actingAs($this->admin())->put('/admin/card-settings', $this->validData(['card_designation' => 'Field Reporter']));

        $this->actingAs($this->admin())->get('/admin/card-settings')
            ->assertSee('Field Reporter')
            ->assertSee('UDYAM-CG-14-0058352')
            ->assertSee('value="500"', false);
    }

    public function test_dates_can_be_cleared(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->put('/admin/card-settings', $this->validData(['letter_date' => '2026-11-01']));
        $this->actingAs($admin)->put('/admin/card-settings', $this->validData(['letter_date' => '']));

        $this->assertNull(CardSetting::first()->letter_date);
    }

    public function test_validation_rejects_bad_values(): void
    {
        $admin = $this->admin();

        $bad = [
            ['card_validity_months' => 0],
            ['card_validity_months' => 121],
            ['card_validity_months' => 'abc'],
            ['card_aadhaar_display' => 'everything'],
            ['card_heading' => ''],
            ['card_designation' => ''],
            ['card_heading' => str_repeat('x', 31)],
            ['fee_amount' => '-5'],
            ['fee_amount' => 'free'],
            ['fee_amount' => '10.555'],
            ['website_url' => 'javascript:alert(1)'],
            ['website_url' => 'example.test'],
            ['letter_email' => 'not-an-email'],
            ['letter_phone' => 'call me'],
            ['udyam_number' => 'UDYAM <b>'],
            ['letter_date' => 'not-a-date'],
            ['card_terms' => ''],
            ['card_terms' => implode("\n", range(1, 11))],
            ['card_terms' => str_repeat('a', 301)],
            ['letter_template' => ''],
        ];

        foreach ($bad as $override) {
            $this->actingAs($admin)->put('/admin/card-settings', $this->validData($override))
                ->assertSessionHasErrors(array_key_first($override));
        }

        $this->assertSame('PRESS', CardSetting::first()->card_heading);
    }

    public function test_boundary_values_are_accepted(): void
    {
        $this->actingAs($this->admin())->put('/admin/card-settings', $this->validData([
            'card_validity_months' => 1,
            'fee_amount' => '0.50',
            'card_terms' => implode("\n", range(1, 10)),
        ]))->assertSessionHasNoErrors();

        $this->actingAs($this->admin())->put('/admin/card-settings', $this->validData(['card_validity_months' => 120]))
            ->assertSessionHasNoErrors();
    }

    public function test_terms_are_cleaned_up(): void
    {
        $this->actingAs($this->admin())->put('/admin/card-settings', $this->validData([
            'card_terms' => "  One  \r\n\r\n\r\nTwo\r\n   \r\nThree  ",
        ]))->assertSessionHasNoErrors();

        $row = CardSetting::first();
        $this->assertSame("One\nTwo\nThree", $row->card_terms);
        $this->assertSame(['One', 'Two', 'Three'], $row->termsList());
    }

    public function test_card_texts_are_escaped_on_the_page(): void
    {
        $this->actingAs($this->admin())->put('/admin/card-settings', $this->validData(['card_designation' => '<script>alert(1)</script>']));

        $this->actingAs($this->admin())->get('/admin/card-settings')
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_image_paths_cannot_be_set_from_the_form(): void
    {
        $this->actingAs($this->admin())->put('/admin/card-settings', $this->validData([
            'logo_path' => '../../.env',
            'payment_qr_path' => 'members/documents/secret.jpg',
        ]));

        $row = CardSetting::first();
        $this->assertNull($row->logo_path);
        $this->assertNull($row->payment_qr_path);
    }

    // ---------- Fee text ----------

    public function test_fee_label_is_shown_only_when_an_amount_is_set(): void
    {
        $this->assertNull((new CardSetting(['fee_amount' => null, 'fee_currency' => '₹']))->feeLabel());
        $this->assertNull((new CardSetting(['fee_amount' => '0', 'fee_currency' => '₹']))->feeLabel());
        $this->assertSame('Application fee: ₹500', (new CardSetting(['fee_amount' => '500.00', 'fee_currency' => '₹']))->feeLabel());
        $this->assertSame('Application fee: Rs.499.50', (new CardSetting(['fee_amount' => '499.50', 'fee_currency' => 'Rs.']))->feeLabel());
    }

    public function test_clearing_the_fee_removes_it(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->put('/admin/card-settings', $this->validData(['fee_amount' => '750']));
        $this->assertSame('Application fee: ₹750', CardSetting::first()->feeLabel());

        $this->actingAs($admin)->put('/admin/card-settings', $this->validData(['fee_amount' => '']));
        $this->assertNull(CardSetting::first()->fee_amount);
        $this->assertNull(CardSetting::first()->feeLabel());
    }

    // ---------- Expiry ----------

    public function test_expiry_is_validity_months_after_the_start_date(): void
    {
        $setting = new CardSetting(['card_validity_months' => 12]);
        $this->assertSame('2027-10-20', $setting->expiryDateFor(Carbon::parse('2026-10-20'))->format('Y-m-d'));

        $setting = new CardSetting(['card_validity_months' => 6]);
        $this->assertSame('2027-02-28', $setting->expiryDateFor(Carbon::parse('2026-08-31'))->format('Y-m-d'));

        // The original date object is not changed.
        $from = Carbon::parse('2026-10-20');
        $setting->expiryDateFor($from);
        $this->assertSame('2026-10-20', $from->format('Y-m-d'));
    }

    // ---------- Letter text ----------

    public function test_unknown_placeholders_are_rejected(): void
    {
        $this->actingAs($this->admin())->put('/admin/card-settings', $this->validData([
            'letter_template' => '<p>Dear {full_nmae}, {first_name} {salary}</p>',
        ]))->assertSessionHasErrors('letter_template');

        $this->assertStringNotContainsString('full_nmae', CardSetting::first()->letter_template);
    }

    public function test_every_listed_placeholder_is_accepted(): void
    {
        $text = '<p>' . implode(' ', array_map(fn ($k) => '{' . $k . '}', array_keys(CardSetting::PLACEHOLDERS))) . '</p>';

        $this->actingAs($this->admin())->put('/admin/card-settings', $this->validData(['letter_template' => $text]))
            ->assertSessionHasNoErrors();
    }

    public function test_letter_text_is_cleaned_to_simple_formatting(): void
    {
        $this->actingAs($this->admin())->put('/admin/card-settings', $this->validData([
            'letter_template' => '<p onclick="steal()" style="x">Hi <strong class="a">{full_name}</strong></p>'
                . '<script>alert(1)</script><img src="http://evil.test/x.png"><a href="http://evil.test">link</a>'
                . '<iframe src="http://evil.test"></iframe><ul><li>One</li></ul>',
        ]))->assertSessionHasNoErrors();

        $saved = CardSetting::first()->letter_template;

        $this->assertStringContainsString('<p>Hi <strong>{full_name}</strong></p>', $saved);
        $this->assertStringContainsString('<ul><li>One</li></ul>', $saved);
        foreach (['onclick', 'style', '<script', '<img', '<a ', '<iframe', 'evil.test/x.png', 'href'] as $bad) {
            $this->assertStringNotContainsString($bad, $saved, "{$bad} should be removed");
        }
    }

    // ---------- Images ----------

    public function test_images_are_stored_on_the_private_disk(): void
    {
        $this->actingAs($this->admin())->put('/admin/card-settings', $this->validData([
            'logo' => $this->png('logo.png'),
            'payment_qr' => $this->png('qr.jpg', 300, 300),
            'website_qr' => $this->png('web.png', 300, 300),
            'stamp' => $this->png('stamp.png'),
        ]))->assertSessionHasNoErrors();

        $row = CardSetting::first();

        foreach (CardSetting::IMAGES as $key => $column) {
            $this->assertStringStartsWith('card-settings/', $row->{$column});
            Storage::disk('local')->assertExists($row->{$column});
            $this->assertTrue($row->hasImage($key));
            $this->assertNotNull($row->imagePath($key));
        }
    }

    public function test_saving_without_files_keeps_existing_images(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->put('/admin/card-settings', $this->validData(['logo' => $this->png()]));
        $path = CardSetting::first()->logo_path;

        $this->actingAs($admin)->put('/admin/card-settings', $this->validData(['card_heading' => 'NEWS']));

        $this->assertSame($path, CardSetting::first()->logo_path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_replacing_an_image_deletes_the_old_file(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->put('/admin/card-settings', $this->validData(['logo' => $this->png('a.png')]));
        $old = CardSetting::first()->logo_path;

        $this->actingAs($admin)->put('/admin/card-settings', $this->validData(['logo' => $this->png('b.png')]));
        $new = CardSetting::first()->logo_path;

        $this->assertNotSame($old, $new);
        Storage::disk('local')->assertMissing($old);
        Storage::disk('local')->assertExists($new);
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_images_can_be_removed(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->put('/admin/card-settings', $this->validData(['stamp' => $this->png('s.png')]));
        $path = CardSetting::first()->stamp_path;

        $this->actingAs($admin)->put('/admin/card-settings', $this->validData(['remove_stamp' => '1']));

        $this->assertNull(CardSetting::first()->stamp_path);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_uploading_and_removing_together_keeps_the_new_image(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->put('/admin/card-settings', $this->validData(['logo' => $this->png('a.png')]));

        $this->actingAs($admin)->put('/admin/card-settings', $this->validData(['logo' => $this->png('b.png'), 'remove_logo' => '1']));

        $row = CardSetting::first();
        $this->assertNotNull($row->logo_path);
        Storage::disk('local')->assertExists($row->logo_path);
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_bad_images_are_rejected_and_nothing_is_stored(): void
    {
        $admin = $this->admin();

        $bad = [
            ['logo' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain')],
            ['logo' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php')],
            ['logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml')],
            ['logo' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')],
            ['payment_qr' => UploadedFile::fake()->image('qr.gif', 100, 100)],
            ['payment_qr' => UploadedFile::fake()->image('qr.webp', 100, 100)],
            ['website_qr' => UploadedFile::fake()->image('big.png', 100, 100)->size(3000)],
            ['stamp' => UploadedFile::fake()->image('wide.png', 5000, 100)],
        ];

        foreach ($bad as $override) {
            $this->actingAs($admin)->put('/admin/card-settings', $this->validData($override))
                ->assertSessionHasErrors(array_key_first($override));
        }

        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertNull(CardSetting::first()->logo_path);
    }

    public function test_a_failed_save_does_not_change_text_or_leave_files(): void
    {
        $this->actingAs($this->admin())->put('/admin/card-settings', $this->validData([
            'card_heading' => 'CHANGED',
            'card_validity_months' => 0,
            'logo' => $this->png(),
        ]))->assertSessionHasErrors('card_validity_months');

        $this->assertSame('PRESS', CardSetting::first()->card_heading);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    // ---------- Showing images ----------

    public function test_admin_image_route_needs_the_permission_and_a_file(): void
    {
        $this->actingAs($this->admin())->put('/admin/card-settings', $this->validData(['logo' => $this->png()]));

        $this->actingAs($this->admin())->get('/admin/card-settings/images/logo')
            ->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->actingAs($this->admin())->get('/admin/card-settings/images/stamp')->assertNotFound();
        $this->actingAs($this->admin())->get('/admin/card-settings/images/other')->assertNotFound();

        $this->actingAs($this->makeUser('manager'))->get('/admin/card-settings/images/logo')->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->get('/admin/card-settings/images/logo')->assertRedirect('/login');
    }

    public function test_form_shows_current_images_and_remove_options(): void
    {
        $this->actingAs($this->admin())->get('/admin/card-settings')
            ->assertDontSee('remove_logo')->assertDontSee('images/logo');

        $this->actingAs($this->admin())->put('/admin/card-settings', $this->validData(['logo' => $this->png()]));

        $this->actingAs($this->admin())->get('/admin/card-settings')
            ->assertSee('remove_logo')->assertSee('images/logo')->assertDontSee('remove_stamp');
    }

    public function test_payment_qr_is_public_but_nothing_else_is(): void
    {
        $this->get('/payment-qr')->assertNotFound();

        $this->actingAs($this->admin())->put('/admin/card-settings', $this->validData([
            'payment_qr' => $this->png('qr.png', 300, 300),
            'logo' => $this->png('logo.png'),
        ]));
        $this->app['auth']->forgetGuards();

        $this->get('/payment-qr')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

        // The other images stay behind the admin permission.
        $this->get('/admin/card-settings/images/logo')->assertRedirect('/login');
        // Laravel answers an unsigned /storage link with 403 (or 404 when no link exists): never the file.
        $this->assertContains(
            $this->get('/storage/card-settings/' . basename(CardSetting::first()->logo_path))->getStatusCode(),
            [403, 404]
        );
    }

    public function test_payment_qr_disappears_after_removal(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->put('/admin/card-settings', $this->validData(['payment_qr' => $this->png('qr.png', 300, 300)]));
        $this->get('/payment-qr')->assertOk();

        $this->actingAs($admin)->put('/admin/card-settings', $this->validData(['remove_payment_qr' => '1']));
        $this->get('/payment-qr')->assertNotFound();
    }

    public function test_missing_file_on_disk_gives_404_not_an_error(): void
    {
        $this->actingAs($this->admin())->put('/admin/card-settings', $this->validData(['payment_qr' => $this->png('qr.png', 300, 300)]));
        Storage::disk('local')->delete(CardSetting::first()->payment_qr_path);

        $this->get('/payment-qr')->assertNotFound();
        $this->assertNull(CardSetting::first()->imagePath('payment_qr'));
    }

    public function test_pdf_library_notice_matches_whether_dompdf_is_installed(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/card-settings')->assertOk();

        if (class_exists(\Dompdf\Dompdf::class)) {
            $response->assertDontSee('PDF library not installed yet');
        } else {
            $response->assertSee('PDF library not installed yet');
        }
    }
}
