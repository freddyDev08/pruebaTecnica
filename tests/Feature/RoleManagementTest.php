<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function staff(string $roleSlug): User
    {
        return User::factory()->create([
            'account_type' => 'staff',
            'approval_status' => 'approved',
            'active' => true,
            'email_verified_at' => now(),
            'role_id' => Role::where('slug', $roleSlug)->firstOrFail()->id,
        ]);
    }

    public function test_admin_can_see_initial_roles(): void
    {
        $this->actingAs($this->staff('administrador'))
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('Administrador')
            ->assertSee('Gerente')
            ->assertSee('Atención a clientes');
    }

    public function test_admin_can_create_role_without_permissions(): void
    {
        $this->actingAs($this->staff('administrador'))
            ->post(route('admin.roles.store'), [
                'name' => 'Supervisor',
                'description' => 'Supervisa el equipo de la tienda',
            ])
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('status');

        $role = Role::where('slug', 'supervisor')->firstOrFail();
        $this->assertSame('Supervisa el equipo de la tienda', $role->description);
        $this->assertSame([], $role->permissions);
    }

    public function test_duplicate_role_is_rejected(): void
    {
        $this->actingAs($this->staff('administrador'))
            ->post(route('admin.roles.store'), ['name' => 'Administrador', 'description' => 'Otro rol para administración'])
            ->assertSessionHasErrors('name');
        $this->assertSame(3, Role::count());
    }

    public function test_staff_without_role_permission_cannot_see_or_create_roles(): void
    {
        $this->actingAs($this->staff('gerente'))
            ->get(route('admin.roles.index'))->assertForbidden();

        $this->post(route('admin.roles.store'), ['name' => 'Inesperado', 'description' => 'Descripción de ejemplo'])
            ->assertForbidden();

        $this->assertDatabaseMissing('roles', ['slug' => 'inesperado']);
    }

    public function test_client_cannot_manage_roles(): void
    {
        $client = User::factory()->create([
            'account_type' => 'client',
            'approval_status' => 'approved',
            'active' => true,
            'email_verified_at' => now(),
            'role_id' => null,
        ]);
        $this->actingAs($client)->get(route('admin.roles.index'))->assertForbidden();
    }
    public function test_admin_can_soft_delete_unassigned_role(): void
    {
        $admin = $this->staff('administrador');
        $role = Role::create([
            'name' => 'Auxiliar', 'slug' => 'auxiliar',
            'description' => 'Rol de apoyo operativo', 'permissions' => [],
        ]);

        $this->actingAs($admin)->deleteJson(route('admin.roles.destroy', $role))
            ->assertOk()->assertJsonPath('deleted', true);

        $this->assertSoftDeleted('roles', ['id' => $role->id]);
        $this->assertNull(Role::find($role->id));
    }

    public function test_assigned_role_cannot_be_soft_deleted(): void
    {
        $admin = $this->staff('administrador');
        $role = Role::where('slug', 'administrador')->firstOrFail();

        $this->actingAs($admin)->deleteJson(route('admin.roles.destroy', $role))
            ->assertStatus(409)->assertJsonPath('deleted', false);

        $this->assertNull($role->fresh()->deleted_at);
    }

    public function test_staff_without_permission_cannot_delete_role(): void
    {
        $manager = $this->staff('gerente');
        $role = Role::where('slug', 'atencion-clientes')->firstOrFail();

        $this->actingAs($manager)->deleteJson(route('admin.roles.destroy', $role))
            ->assertForbidden();

        $this->assertNull($role->fresh()->deleted_at);
    }

    public function test_admin_can_assign_permissions_to_role(): void
    {
        $admin = $this->staff('administrador');
        $role = Role::where('slug', 'gerente')->firstOrFail();

        $this->actingAs($admin)->putJson(route('admin.roles.permissions.update', $role), [
            'permissions' => ['clients.view', 'reports.export'],
        ])->assertOk()->assertJsonPath('permissions', ['clients.view', 'reports.export']);

        $this->assertSame(['clients.view', 'reports.export'], $role->fresh()->permissions);
    }

    public function test_admin_can_clear_all_role_permissions(): void
    {
        $admin = $this->staff('administrador');
        $role = Role::where('slug', 'gerente')->firstOrFail();

        $this->actingAs($admin)->putJson(route('admin.roles.permissions.update', $role), [])
            ->assertOk()->assertJsonPath('permissions', []);

        $this->assertSame([], $role->fresh()->permissions);
    }

    public function test_invalid_permission_cannot_be_assigned(): void
    {
        $admin = $this->staff('administrador');
        $role = Role::where('slug', 'gerente')->firstOrFail();
        $before = $role->permissions;

        $this->actingAs($admin)->putJson(route('admin.roles.permissions.update', $role), [
            'permissions' => ['admin.superpower'],
        ])->assertUnprocessable();

        $this->assertSame($before, $role->fresh()->permissions);
    }

    public function test_staff_without_permission_cannot_assign_permissions(): void
    {
        $manager = $this->staff('gerente');
        $role = Role::where('slug', 'atencion-clientes')->firstOrFail();

        $this->actingAs($manager)->putJson(route('admin.roles.permissions.update', $role), [
            'permissions' => ['roles.manage'],
        ])->assertForbidden();

        $this->assertNotContains('roles.manage', $role->fresh()->permissions);
    }

    public function test_recreating_soft_deleted_role_does_not_retain_old_permissions(): void
    {
        $admin = $this->staff('administrador');
        $role = Role::create([
            'name' => 'Temporal', 'slug' => 'temporal', 'description' => 'Antiguo rol operativo',
            'permissions' => ['users.manage'],
        ]);
        $role->delete();

        $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'Temporal', 'description' => 'Nuevo rol sin permisos',
        ])->assertRedirect(route('admin.roles.index'));

        $this->assertSame(1, Role::where('slug', 'temporal')->count());
        $this->assertSame([], Role::where('slug', 'temporal')->first()->permissions);
    }

}
