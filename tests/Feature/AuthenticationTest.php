<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SuperRootSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', 'administrador')->value('id'),
            'account_type' => 'staff',
            'approval_status' => 'approved',
            'active' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_registration_is_closed(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
    }

    public function test_existing_pending_client_cannot_enter(): void
    {
        $pending = User::factory()->create([
            'email' => 'cliente@example.com',
            'account_type' => 'client',
            'role_id' => null,
            'approval_status' => 'pending',
            'active' => false,
            'password' => 'Password12345',
        ]);

        $this->post('/login', ['email' => $pending->email, 'password' => 'Password12345'])
            ->assertRedirect(route('login'))->assertSessionHas('warning');
        $this->assertGuest();
    }

    public function test_client_request_routes_are_removed(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin/clientes/solicitudes')->assertNotFound();
        $this->actingAs($admin)->post('/admin/clientes/12/aprobar')->assertNotFound();
        $this->actingAs($admin)->post('/admin/clientes/12/rechazar')->assertNotFound();
        $this->actingAs($admin)->get('/portal')->assertNotFound();
    }

    public function test_old_approved_client_cannot_enter_staff_panel(): void
    {
        $client = User::factory()->create([
            'account_type' => 'client',
            'role_id' => null,
            'approval_status' => 'approved',
            'active' => true,
            'password' => 'Password12345',
        ]);

        $this->post('/login', ['email' => $client->email, 'password' => 'Password12345'])
            ->assertRedirect(route('login'))->assertSessionHas('warning');
        $this->assertGuest();
    }

    public function test_super_root_is_created_from_private_configuration_once(): void
    {
        config([
            'auth_setup.super_root_email' => 'root@example.com',
            'auth_setup.super_root_password' => 'ExampleSecret123!',
        ]);
        $this->seed(SuperRootSeeder::class);
        $root = User::where('email', 'root@example.com')->firstOrFail();

        $this->assertTrue($root->is_super_root);
        $this->assertTrue($root->hasVerifiedEmail());
        $this->assertTrue(Hash::check('ExampleSecret123!', $root->password));
        $this->assertTrue($root->allows('users.manage'));

        $this->seed(SuperRootSeeder::class);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_password_reset_notification_still_works(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $this->post('/forgot-password', ['email' => $admin->email])
            ->assertRedirect(route('password.email.sent'));
        Notification::assertSentTo($admin, ResetPassword::class);
    }
}
