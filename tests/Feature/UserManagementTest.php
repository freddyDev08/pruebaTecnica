<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Notifications\StaffInvitationNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function staff(string $roleSlug = 'administrador'): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', $roleSlug)->value('id'),
            'account_type' => 'staff',
            'approval_status' => 'approved',
            'active' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_public_registration_is_not_available(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', ['email' => 'nuevo@example.com'])->assertNotFound();
    }

    public function test_administrator_can_create_user_and_notification_contains_temporary_access(): void
    {
        Notification::fake();
        $admin = $this->staff();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Usuario de pruebas',
            'phone' => '9611112233',
            'email' => 'creado@example.com',
            'role_id' => Role::where('slug', 'gerente')->value('id'),
        ])->assertRedirect(route('admin.users.index'));

        $created = User::where('email', 'creado@example.com')->firstOrFail();
        $this->assertTrue($created->active);
        $this->assertTrue($created->must_change_password);
        $this->assertTrue($created->isStaff());
        $this->assertNull($created->email_verified_at);
        $this->assertNotEmpty($created->password);
        $this->assertNotEquals('creado@example.com', $created->password);
        Notification::assertSentTo($created, StaffInvitationNotification::class);
    }

    public function test_manager_cannot_manage_users(): void
    {
        $manager = $this->staff('gerente');
        $this->actingAs($manager)->get(route('admin.users.index'))->assertForbidden();
        $this->post(route('admin.users.store'), [])->assertForbidden();
    }

    public function test_role_management_permission_also_allows_user_management(): void
    {
        $role = Role::create([
            'name' => 'Encargado de roles',
            'slug' => 'encargado-de-roles',
            'description' => 'Gestiona los roles',
            'permissions' => ['roles.manage'],
        ]);
        $user = $this->staff();
        $user->update(['role_id' => $role->id]);
        $this->actingAs($user)->get(route('admin.users.index'))->assertOk();
    }

    public function test_admin_can_disable_and_enable_other_user(): void
    {
        $admin = $this->staff();
        $target = $this->staff('gerente');

        $this->actingAs($admin)->patchJson(route('admin.users.toggle', $target), ['active' => false])
            ->assertOk()->assertJsonPath('active', false);
        $this->assertFalse($target->fresh()->active);

        $this->post('/logout');
        $this->post('/login', ['email' => $target->email, 'password' => 'password'])
            ->assertRedirect(route('login'));
        $this->assertGuest();

        $this->actingAs($admin)->patchJson(route('admin.users.toggle', $target), ['active' => true])
            ->assertOk()->assertJsonPath('active', true);
        $this->assertTrue($target->fresh()->active);
    }

    public function test_admin_can_soft_delete_user(): void
    {
        $admin = $this->staff();
        $target = $this->staff('gerente');
        $this->actingAs($admin)->deleteJson(route('admin.users.destroy', $target))
            ->assertOk()->assertJsonPath('deleted', true);
        $this->assertSoftDeleted('users', ['id' => $target->id]);
        $this->assertFalse(User::whereKey($target->id)->exists());
    }

    public function test_last_administrator_cannot_be_disabled_deleted_or_demoted(): void
    {
        $admin = $this->staff();
        $controller = $this->staff('gerente');
        $managerRole = Role::where('slug', 'gerente')->firstOrFail();
        $managerRole->update(['permissions' => ['users.manage', 'roles.manage']]);

        $this->actingAs($controller)
            ->patchJson(route('admin.users.toggle', $admin), ['active' => false])
            ->assertUnprocessable();
        $this->deleteJson(route('admin.users.destroy', $admin))
            ->assertUnprocessable();
        $this->patch(route('admin.users.role', $admin), [
            'role_id' => $managerRole->id,
        ])->assertSessionHasErrors('user');

        $this->assertTrue($admin->fresh()->active);
        $this->assertEquals(Role::where('slug', 'administrador')->value('id'), $admin->fresh()->role_id);
    }

    public function test_super_root_is_protected_from_changes_in_user_management(): void
    {
        $operator = $this->staff();
        $root = $this->staff();
        $root->forceFill(['is_super_root' => true])->save();

        $this->actingAs($operator)->deleteJson(route('admin.users.destroy', $root))->assertForbidden();
        $this->patchJson(route('admin.users.toggle', $root), ['active' => false])->assertForbidden();
        $this->get(route('admin.users.edit', $root))->assertForbidden();
    }

    public function test_editing_email_requires_confirmation_at_new_address(): void
    {
        Notification::fake();
        $admin = $this->staff();
        $target = $this->staff('gerente');
        $previous = $target->email;

        $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'name' => 'Actualizado',
            'phone' => '9611234567',
            'email' => 'nuevo-correo@example.com',
            'role_id' => $target->role_id,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertSame($previous, $target->fresh()->email);
        $this->assertDatabaseHas('email_change_requests', [
            'user_id' => $target->id,
            'new_email' => 'nuevo-correo@example.com',
        ]);
    }

    public function test_user_with_temporary_password_cannot_open_dashboard(): void
    {
        $staff = $this->staff();
        $staff->update(['must_change_password' => true]);
        $this->actingAs($staff)->get(route('dashboard'))->assertRedirect(route('profile.show'));
        $this->get(route('profile.show'))->assertOk();
    }
    public function test_user_manager_without_role_permission_creates_pending_user_and_cannot_assign_role(): void
    {
        Notification::fake();
        $role = Role::create([
            'name' => 'Gestor de usuarios',
            'slug' => 'gestor-usuarios-test',
            'description' => 'Gestiona datos sin asignar roles.',
            'permissions' => ['users.manage'],
        ]);
        $operator = $this->staff();
        $operator->update(['role_id' => $role->id]);
        $target = $this->staff('gerente');

        $this->actingAs($operator)->get(route('admin.users.index'))
            ->assertOk()->assertDontSee('Asignar rol');
        $this->get(route('admin.users.create'))->assertOk()->assertDontSee('name="role_id"', false);

        $this->post(route('admin.users.store'), [
            'name' => 'Nuevo sin rol',
            'phone' => '9611112233',
            'email' => 'sinrol@example.com',
        ])->assertRedirect(route('admin.users.index'));
        $created = User::where('email', 'sinrol@example.com')->firstOrFail();
        $this->assertNull($created->role_id);
        Notification::assertSentTo($created, StaffInvitationNotification::class);

        $this->post(route('admin.users.store'), [
            'name' => 'Otro usuario',
            'phone' => '9611112233',
            'email' => 'segundo@example.com',
            'role_id' => Role::where('slug', 'administrador')->value('id'),
        ])->assertForbidden();
        $this->patchJson(route('admin.users.role', $target), [
            'role_id' => Role::where('slug', 'administrador')->value('id'),
        ])->assertForbidden();
        $this->put(route('admin.users.update', $target), [
            'name' => 'Intento',
            'phone' => '9611112233',
            'email' => $target->email,
            'role_id' => Role::where('slug', 'administrador')->value('id'),
        ])->assertForbidden();
    }

    public function test_role_manager_can_assign_role_but_not_create_or_edit_users(): void
    {
        $role = Role::create([
            'name' => 'Gestor de roles',
            'slug' => 'gestor-roles-test',
            'description' => 'Gestiona solamente roles.',
            'permissions' => ['roles.manage'],
        ]);
        $operator = $this->staff();
        $operator->update(['role_id' => $role->id]);
        $target = $this->staff('gerente');
        $target->update(['role_id' => null]);

        $this->actingAs($operator)->get(route('admin.users.index'))
            ->assertOk()->assertSee('Asignar rol')->assertDontSee('Crear usuario');
        $this->get(route('admin.users.create'))->assertForbidden();
        $this->get(route('admin.users.edit', $target))->assertForbidden();
        $this->post(route('admin.users.store'), [])->assertForbidden();
        $this->put(route('admin.users.update', $target), [])->assertForbidden();
        $this->patchJson(route('admin.users.toggle', $target), ['active' => false])->assertForbidden();
        $this->deleteJson(route('admin.users.destroy', $target))->assertForbidden();

        $managerId = Role::where('slug', 'gerente')->value('id');
        $this->patchJson(route('admin.users.role', $target), ['role_id' => $managerId])
            ->assertOk()->assertJsonPath('role_name', 'Gerente');
        $this->assertEquals($managerId, $target->fresh()->role_id);
    }

    public function test_pending_role_login_shows_specific_warning(): void
    {
        $target = $this->staff('gerente');
        $target->update(['role_id' => null]);
        $this->post(route('login.store'), ['email' => $target->email, 'password' => 'password'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('warning', 'Tu cuenta está pendiente de asignación de rol. Contacta al administrador.');
        $this->assertGuest();
    }

    public function test_disabled_login_shows_specific_warning(): void
    {
        $target = $this->staff('gerente');
        $target->update(['active' => false]);
        $this->post(route('login.store'), ['email' => $target->email, 'password' => 'password'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('warning', 'Tu cuenta está deshabilitada. Contacta al administrador para solicitar su habilitación.');
        $this->assertGuest();
    }

    public function test_role_manager_without_dashboard_permission_is_redirected_to_users_listing(): void
    {
        $role = Role::create([
            'name' => 'Asignador de roles',
            'slug' => 'asignador-roles-test',
            'description' => 'Solo asigna roles desde el listado.',
            'permissions' => ['roles.manage'],
        ]);
        $operator = $this->staff();
        $operator->update(['role_id' => $role->id]);

        $this->post(route('login.store'), [
            'email' => $operator->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.users.index'));
    }

}
