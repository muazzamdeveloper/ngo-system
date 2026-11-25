<?php

namespace Tests\Feature;

use App\Models\LoginActivity;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Events\Failed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LoginActivityTest extends TestCase
{
    use RefreshDatabase;

    private const CHROME = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeUser(string $roleName, bool $super = false, ?string $email = null, array $extra = []): User
    {
        $user = new User();
        $user->forceFill(array_merge([
            'name' => 'User ' . $roleName,
            'email' => $email ?? $roleName . '@example.test',
            'password' => 'Password123!',
            'is_active' => true,
            'is_super_admin' => $super,
            'role_id' => Role::where('name', $roleName)->value('id'),
        ], $extra))->save();

        return $user;
    }

    private function attemptLogin(string $email, string $password, string $ip = '203.0.113.9')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->withHeader('User-Agent', self::CHROME)
            ->post('/login', ['email' => $email, 'password' => $password]);
    }

    // ---------- Recording ----------

    public function test_successful_login_is_recorded(): void
    {
        $user = $this->makeUser('manager');

        $this->attemptLogin('manager@example.test', 'Password123!')->assertRedirect('/admin/dashboard');

        $row = LoginActivity::firstOrFail();
        $this->assertSame('login', $row->event);
        $this->assertSame($user->id, $row->user_id);
        $this->assertSame('manager@example.test', $row->email);
        $this->assertSame('203.0.113.9', $row->ip_address);
        $this->assertSame(self::CHROME, $row->user_agent);
        $this->assertSame('Chrome on Windows', $row->browser());
        $this->assertNotNull($row->created_at);
    }

    public function test_failed_attempts_are_recorded_without_the_password(): void
    {
        $user = $this->makeUser('manager');

        $this->attemptLogin('manager@example.test', 'TotallyWrongPass9');
        $this->attemptLogin('nobody@example.test', 'AnotherWrongPass9');

        $rows = LoginActivity::orderBy('id')->get();

        $this->assertCount(2, $rows);
        $this->assertSame('failed', $rows[0]->event);
        $this->assertSame($user->id, $rows[0]->user_id);
        $this->assertSame('failed', $rows[1]->event);
        $this->assertNull($rows[1]->user_id);
        $this->assertSame('nobody@example.test', $rows[1]->email);

        $dump = json_encode(LoginActivity::all()->toArray());
        $this->assertStringNotContainsString('TotallyWrongPass9', $dump);
        $this->assertStringNotContainsString('AnotherWrongPass9', $dump);
        $this->assertStringNotContainsString('Password123!', $dump);
    }

    public function test_login_to_a_disabled_account_is_recorded_as_failed(): void
    {
        $this->makeUser('manager', false, 'off@example.test', ['is_active' => false]);

        $this->attemptLogin('off@example.test', 'Password123!')->assertSessionHasErrors('email');

        $this->assertSame(['failed'], LoginActivity::pluck('event')->all());
        $this->assertSame('off@example.test', LoginActivity::first()->email);
    }

    public function test_lockout_after_repeated_failures_is_recorded(): void
    {
        $this->makeUser('manager');

        foreach (range(1, 5) as $i) {
            $this->attemptLogin('manager@example.test', 'wrong-password');
        }
        $this->attemptLogin('manager@example.test', 'wrong-password')->assertSessionHasErrors('email');

        $this->assertSame(5, LoginActivity::where('event', 'failed')->count());
        $this->assertSame(1, LoginActivity::where('event', 'lockout')->count());
        $this->assertSame('manager@example.test', LoginActivity::where('event', 'lockout')->value('email'));
    }

    public function test_logout_is_recorded(): void
    {
        $user = $this->makeUser('manager');

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');

        $row = LoginActivity::firstOrFail();
        $this->assertSame('logout', $row->event);
        $this->assertSame($user->id, $row->user_id);
    }

    public function test_overlong_values_are_cut_to_fit(): void
    {
        event(new Failed('web', null, ['email' => str_repeat('a', 400), 'password' => 'secret-pass']));

        $row = LoginActivity::firstOrFail();
        $this->assertSame(255, mb_strlen($row->email));
        $this->assertStringNotContainsString('secret-pass', json_encode($row->toArray()));
    }

    public function test_a_logging_problem_never_blocks_login(): void
    {
        $this->makeUser('manager');
        Schema::drop('login_activities');

        $this->attemptLogin('manager@example.test', 'Password123!')->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated();
    }

    // ---------- The page ----------

    public function test_only_the_super_admin_can_see_the_page(): void
    {
        foreach (['manager', 'coordinator', 'member'] as $name) {
            $this->actingAs($this->makeUser($name))->get('/admin/login-activity')->assertForbidden();
        }

        $this->actingAs($this->makeUser('super-admin', true))->get('/admin/login-activity')->assertOk();

        auth()->logout();
        $this->get('/admin/login-activity')->assertRedirect('/login');
    }

    public function test_sidebar_link_is_only_for_the_super_admin(): void
    {
        $this->actingAs($this->makeUser('super-admin', true))->get('/admin/dashboard')->assertSee('Login activity');
        $this->actingAs($this->makeUser('manager'))->get('/admin/dashboard')->assertDontSee('Login activity');
    }

    public function test_page_lists_activity_with_names_and_summary(): void
    {
        $admin = $this->makeUser('super-admin', true);
        $this->makeUser('manager', false, 'manager@example.test', ['name' => 'Mona Manager']);

        // Two failures as a visitor, then a real login.
        $this->attemptLogin('manager@example.test', 'bad-one');
        $this->attemptLogin('ghost@example.test', 'bad-two', '198.51.100.7');
        $this->attemptLogin('manager@example.test', 'Password123!');
        $this->app['auth']->forgetGuards();

        // Old entries do not count in the 24-hour summary.
        LoginActivity::create(['event' => 'failed', 'email' => 'old@example.test', 'created_at' => now()->subDays(3)]);

        $this->actingAs($admin)->get('/admin/login-activity')
            ->assertOk()
            ->assertSee('Mona Manager')
            ->assertSee('ghost@example.test')
            ->assertSee('no matching account')
            ->assertSee('198.51.100.7')
            ->assertSee('Chrome on Windows')
            ->assertSee('data-stat="logins-24h">1<', false)
            ->assertSee('data-stat="failed-24h">2<', false)
            ->assertSee('data-stat="lockouts-24h">0<', false);
    }

    public function test_filter_search_and_pagination(): void
    {
        $admin = $this->makeUser('super-admin', true);

        LoginActivity::create(['event' => 'login', 'email' => 'alpha@example.test', 'ip_address' => '10.0.0.1']);
        LoginActivity::create(['event' => 'failed', 'email' => 'beta@example.test', 'ip_address' => '10.0.0.2']);

        $this->actingAs($admin)->get('/admin/login-activity?event=failed')
            ->assertSee('beta@example.test')->assertDontSee('alpha@example.test');

        $this->actingAs($admin)->get('/admin/login-activity?q=10.0.0.1')
            ->assertSee('alpha@example.test')->assertDontSee('beta@example.test');

        $this->actingAs($admin)->get('/admin/login-activity?event=nonsense')
            ->assertSee('alpha@example.test')->assertSee('beta@example.test');

        // % is matched literally, so it cannot be used to list everything.
        $this->actingAs($admin)->get('/admin/login-activity?q=%25')->assertSee('No activity recorded yet');

        foreach (range(1, 30) as $i) {
            LoginActivity::create(['event' => 'login', 'email' => "bulk{$i}@example.test"]);
        }

        $this->actingAs($admin)->get('/admin/login-activity')->assertSee('Showing 1 to 25 of 32')->assertSee('page=2', false);
    }

    // ---------- Housekeeping ----------

    public function test_old_records_can_be_pruned(): void
    {
        LoginActivity::create(['event' => 'login', 'email' => 'old@example.test', 'created_at' => now()->subDays(200)]);
        LoginActivity::create(['event' => 'login', 'email' => 'new@example.test', 'created_at' => now()->subDays(5)]);

        $this->artisan('app:prune-login-activity')->assertSuccessful();

        $this->assertSame(['new@example.test'], LoginActivity::pluck('email')->all());

        // A very small number of days is raised to the 30-day minimum.
        LoginActivity::create(['event' => 'login', 'email' => 'recent@example.test', 'created_at' => now()->subDays(10)]);
        $this->artisan('app:prune-login-activity', ['--days' => 1])->assertSuccessful();
        $this->assertSame(2, LoginActivity::count());
    }

    public function test_browser_names(): void
    {
        $edge = new LoginActivity(['user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/126 Safari/537.36 Edg/126.0']);
        $phone = new LoginActivity(['user_agent' => 'Mozilla/5.0 (Linux; Android 14) Chrome/126 Mobile Safari/537.36']);

        $this->assertSame('Edge on Windows', $edge->browser());
        $this->assertSame('Chrome on Android', $phone->browser());
        $this->assertSame('Unknown', (new LoginActivity())->browser());
    }
}
