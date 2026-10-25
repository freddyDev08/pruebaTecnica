<?php

namespace Tests\Feature;

use App\Models\EmailChangeRequest;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ConfirmProfileEmailChange;
use App\Notifications\PasswordChangedNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function staff(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', 'administrador')->value('id'),
            'account_type' => 'staff',
            'approval_status' => 'approved',
            'active' => true,
            'email_verified_at' => now(),
            'phone' => '9611111111',
            'password' => 'OldSecret12345',
        ]);
    }

    public function test_staff_can_view_and_update_profile(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff)->get(route('profile.show'))->assertOk()->assertSee('Mi perfil');
        $this->actingAs($staff)->put(route('profile.update'), [
            'name' => 'Nombre Cambiado', 'phone' => '9611234567',
        ])->assertRedirect(route('profile.show'));

        $this->assertDatabaseHas('users', ['id' => $staff->id, 'name' => 'Nombre Cambiado', 'phone' => '9611234567']);
    }

    public function test_email_only_changes_after_confirmation_and_link_is_single_use(): void
    {
        Notification::fake();
        $staff = $this->staff();
        $this->actingAs($staff)->post(route('profile.email.request'), [
            'email' => 'nuevo@example.com',
        ])->assertRedirect(route('profile.show'));

        Notification::assertSentOnDemand(ConfirmProfileEmailChange::class);
        $this->assertSame($staff->email, $staff->fresh()->email);
        $pending = EmailChangeRequest::where('user_id', $staff->id)->firstOrFail();
        $this->assertSame('nuevo@example.com', $pending->new_email);

        $token = Str::random(64);
        $pending->update(['token_hash' => hash('sha256', $token)]);
        $link = URL::temporarySignedRoute('profile.email.confirm', now()->addHour(), ['token' => $token]);

        $this->get($link)->assertRedirect(route('profile.show'));
        $this->assertSame('nuevo@example.com', $staff->fresh()->email);
        $this->assertNotNull($staff->fresh()->email_verified_at);
        $this->assertDatabaseMissing('email_change_requests', ['user_id' => $staff->id]);
        $this->get($link)->assertSessionHas('warning');
    }

    public function test_invalid_email_confirmation_signature_does_not_change_email(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff)->get('/profile/email/confirm/invalid')->assertRedirect(route('login'));
        $this->assertSame($staff->email, $staff->fresh()->email);
    }

    public function test_password_change_requires_current_password_and_notifies(): void
    {
        Notification::fake();
        $staff = $this->staff();
        $this->actingAs($staff)->put(route('password.change.update'), [
            'current_password' => 'Incorrecta123',
            'password' => 'NewSecret12345',
            'password_confirmation' => 'NewSecret12345',
        ])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('OldSecret12345', $staff->fresh()->password));

        $this->actingAs($staff)->put(route('password.change.update'), [
            'current_password' => 'OldSecret12345',
            'password' => 'NewSecret12345',
            'password_confirmation' => 'NewSecret12345',
        ])->assertRedirect(route('profile.show'));
        $this->assertTrue(Hash::check('NewSecret12345', $staff->fresh()->password));
        Notification::assertSentTo($staff, PasswordChangedNotification::class);
    }
}
