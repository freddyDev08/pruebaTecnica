<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('description', 255)->nullable()->after('name');
        });

        // Completa la descripción de los roles ya existentes sin sobrescribir cambios.
        foreach ([
            'administrador' => 'Administración del sistema y gestión de usuarios.',
            'gerente' => 'Supervisión de clientes, recompensas y reportes.',
            'atencion-clientes' => 'Consulta de clientes y seguimiento de canjes.',
        ] as $slug => $description) {
            DB::table('roles')->where('slug', $slug)->whereNull('description')
                ->update(['description' => $description]);
        }
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
