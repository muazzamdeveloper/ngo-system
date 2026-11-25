<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AdminPermissionsSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactUsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(AdminPermissionsSeeder::class);
    }

    private function user(string $role, bool $super = false): User
    {
        $user = new User();
        $user->forceFill([
            'name' => 'User ' . $role, 'email' => $role . '-' . uniqid() . '@example.test', 'password' => 'Password123!',
            'is_active' => true, 'is_super_admin' => $super, 'role_id' => Role::where('name', $role)->value('id'),
        ])->save();

        return $user;
    }

    public function test_contact_page_shows_the_form(): void
    {
        $this->get('/page/contact-us')->assertOk()->assertSee('Send us a message');
    }

    public function test_message_is_stored_as_new(): void
    {
        $this->post('/contact-us', ['name' => 'Ravi', 'mobile' => '9123456780', 'message' => 'Hello there'])
            ->assertRedirect('/page/contact-us');

        $this->assertDatabaseHas('contact_messages', ['name' => 'Ravi', 'status' => 'new']);
    }

    public function test_honeypot_drops_the_message(): void
    {
        $this->post('/contact-us', ['name' => 'Bot', 'mobile' => '9123456780', 'message' => 'spam', 'website' => 'x']);
        $this->assertSame(0, ContactMessage::query()->count());
    }

    public function test_invalid_message_is_rejected(): void
    {
        $this->post('/contact-us', ['name' => '', 'mobile' => 'abc', 'message' => ''])
            ->assertSessionHasErrors(['name', 'mobile', 'message']);
    }

    public function test_admin_can_read_and_delete_messages_but_staff_without_permission_cannot(): void
    {
        $message = ContactMessage::query()->create(['name' => 'Ravi', 'mobile' => '9123456780', 'message' => 'Hi', 'status' => 'new']);

        $this->actingAs($this->user('member'))->get(route('content.messages.index'))->assertForbidden();

        $admin = $this->user('manager', true);
        $this->actingAs($admin)->get(route('content.messages.index'))->assertOk()->assertSee('Ravi');
        $this->actingAs($admin)->put(route('content.messages.read', $message))->assertRedirect();
        $this->assertSame('read', $message->fresh()->status);
        $this->actingAs($admin)->delete(route('content.messages.destroy', $message))->assertRedirect();
        $this->assertSame(0, ContactMessage::query()->count());
    }
}
