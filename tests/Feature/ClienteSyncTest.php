<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\FakeApi\ClienteSyncService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClienteSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function staff(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', $role)->firstOrFail()->id,
            'account_type' => 'staff', 'approval_status' => 'approved',
            'active' => true, 'email_verified_at' => now(),
            'must_change_password' => false,
        ]);
    }

    public function test_sync_saves_all_fields_without_duplicating_clients(): void
    {
        Http::fake([
            'fakeapi.dev/*' => Http::response([
                'success' => true,
                'data' => [
                    ['id' => 241, 'name' => 'Cliente Uno', 'email' => 'uno@example.test', 'phone' => '5551', 'dob' => '1990-04-30T00:00:00Z', 'address' => ['city' => 'Tuxtla'], 'new_field' => 'conservado'],
                ],
                'meta' => ['page' => 1, 'has_more' => false, 'total_pages' => 1],
            ])
        ]);

        $this->actingAs($this->staff('administrador'))
            ->postJson(route('admin.clientes.sync'))->assertOk()->assertJsonPath('result.total', 1);
        $this->actingAs($this->staff('administrador'))
            ->postJson(route('admin.clientes.sync'))->assertOk()->assertJsonPath('result.total', 1);
        $this->assertDatabaseCount('clientes', 1);
        $this->assertDatabaseHas('clientes', ['external_id' => 241, 'email' => 'uno@example.test']);
        $this->assertSame('conservado', \App\Models\Cliente::firstOrFail()->raw_data['new_field']);
    }

    public function test_sync_reads_all_pages(): void
    {
        Http::fake(function ($request) {
            $query = [];
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);
            $page = (int) ($query['page'] ?? 1);
            return Http::response([
                'data' => [['id' => $page, 'name' => 'Cliente ' . $page]],
                'meta' => ['page' => $page, 'has_more' => $page < 2, 'total_pages' => 2],
            ]);
        });
        $this->actingAs($this->staff('administrador'))
            ->postJson(route('admin.clientes.sync'))->assertOk()->assertJsonPath('result.total', 2);
        $this->assertDatabaseCount('clientes', 2);
    }

    public function test_customer_service_stores_log_of_sync(): void
    {
        Http::fake(['fakeapi.dev/*' => Http::response(['data' => [], 'meta' => ['has_more' => false]])]);
        app(ClienteSyncService::class)->sync();
        $this->assertDatabaseHas('fakeapi_sync_runs', ['status' => 'success', 'requests' => 1]);
    }

    public function test_customer_care_can_view_but_not_sync(): void
    {
        $this->actingAs($this->staff('atencion-clientes'))
            ->get(route('admin.clientes.index'))->assertOk();
        $this->postJson(route('admin.clientes.sync'))->assertForbidden();
    }
}
