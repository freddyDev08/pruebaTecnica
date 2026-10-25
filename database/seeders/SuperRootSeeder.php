<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperRootSeeder extends Seeder
{
    public function run(): void
    {
        $email = Str::lower(trim((string) config('auth_setup.super_root_email')));
        $password = (string) config('auth_setup.super_root_password');

        // Sin credenciales privadas de entorno, no se crea una cuenta privilegiada.
        if ($email === '' || $password === '') {
            $this->command?->warn('SUPER_ROOT_EMAIL y SUPER_ROOT_PASSWORD no configurados: se omite el Super Root.');
            return;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 10) {
            throw new \RuntimeException('Configuración de SUPER_ROOT_EMAIL o SUPER_ROOT_PASSWORD inválida.');
        }

        if (User::where('is_super_root', true)->exists()) {
            return; // Nunca sobrescribir un Super Root existente al repetir el seeder.
        }
        if (User::where('email', $email)->exists()) {
            throw new \RuntimeException('El correo SUPER_ROOT_EMAIL ya pertenece a otra cuenta. No se elevan permisos automáticamente.');
        }

        $superRoot = new User([
            'name' => 'Super Root',
            'email' => $email,
            'password' => Hash::make($password),
            'role_id' => Role::where('slug', 'administrador')->firstOrFail()->id,
            'account_type' => 'staff',
            'approval_status' => 'approved',
            'active' => true,
            'approved_at' => now(),
        ]);

        // Campos reservados: nunca asignables desde formularios públicos.
        $superRoot->forceFill([
            'email_verified_at' => now(),
            'is_super_root' => true,
        ]);
        $superRoot->save();
    }
}
