<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $all = array_keys(config('staff_permissions'));
        $common = [
            'panel.view', 'clients.view', 'risk.view',
            'redemptions.manage', 'portal_access.manage',
        ];

        foreach ([
            ['slug' => 'administrador', 'name' => 'Administrador', 'description' => 'Administración del sistema y gestión de usuarios.', 'permissions' => $all],
            ['slug' => 'gerente', 'name' => 'Gerente', 'description' => 'Supervisión de clientes, recompensas y reportes.', 'permissions' => array_merge($common, [
                'amounts.view', 'reports.export', 'rewards.manage',
            ])],
            ['slug' => 'atencion-clientes', 'name' => 'Atención a clientes', 'description' => 'Consulta de clientes y seguimiento de canjes.', 'permissions' => $common],
        ] as $role) {
            // Conserva las modificaciones realizadas a los permisos si se vuelve a ejecutar.
            Role::withTrashed()->firstOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
