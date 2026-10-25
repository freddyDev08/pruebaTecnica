<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->allows('roles.manage'), 403);

        return view('admin.roles.index', [
            'roles' => Role::withCount('users')->orderBy('name')->get(),
            'permisos' => config('staff_permissions'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->allows('roles.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'description' => ['required', 'string', 'min:5', 'max:255'],
        ], [
            'name.required' => 'El nombre del rol es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
            'description.required' => 'La descripción del rol es obligatoria.',
            'description.min' => 'La descripción debe tener al menos 5 caracteres.',
            'description.max' => 'La descripción no puede superar los 255 caracteres.',
        ]);

        $name = trim($data['name']);
        $slug = Str::slug($name);

        if ($slug === '') {
            throw ValidationException::withMessages([
                'name' => 'Escribe un nombre válido para identificar el rol.',
            ]);
        }

        DB::transaction(function () use ($slug, $name, $data) {
            $existing = Role::withTrashed()->where('slug', $slug)->lockForUpdate()->first();

            if ($existing && ! $existing->trashed()) {
                throw ValidationException::withMessages([
                    'name' => 'Ya existe un rol registrado con ese nombre.',
                ]);
            }

            if ($existing) {
                $existing->restore();
                $existing->update([
                    'name' => $name,
                    'description' => trim($data['description']),
                    'permissions' => [],
                ]);
            } else {
                Role::create([
                    'name' => $name,
                    'description' => trim($data['description']),
                    'slug' => $slug,
                    'permissions' => [],
                ]);
            }
        });

        return redirect()->route('admin.roles.index')->with('status', 'Rol registrado correctamente. Aún no tiene permisos asignados.');
    }

    public function updatePermissions(Request $request, Role $role): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()->allows('roles.manage'), 403);

        $validated = $request->validate([
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['required', 'string', 'distinct', Rule::in(array_keys(config('staff_permissions')))],
        ], [
            'permissions.array' => 'La selección de permisos no es válida.',
            'permissions.*.in' => 'Uno de los permisos seleccionados no está permitido.',
        ]);

        $permissions = array_values($validated['permissions'] ?? []);
        DB::transaction(function () use ($role, $permissions) {
            $locked = Role::whereKey($role->id)->lockForUpdate()->firstOrFail();
            $locked->update(['permissions' => $permissions]);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Los permisos del rol se guardaron correctamente.',
                'permissions' => $permissions,
            ]);
        }

        return redirect()->route('admin.roles.index')->with('status', 'Los permisos del rol se guardaron correctamente.');
    }

    public function destroy(Request $request, Role $role): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()->allows('roles.manage'), 403);

        $result = DB::transaction(function () use ($role): array {
            $locked = Role::whereKey($role->id)->lockForUpdate()->firstOrFail();
            $assigned = $locked->users()->count();
            if ($assigned > 0) {
                return [
                    'deleted' => false,
                    'users_count' => $assigned,
                    'message' => 'No se puede eliminar este rol porque tiene usuarios asignados. Primero debes reasignarlos a otro rol.',
                ];
            }

            $locked->delete();
            return ['deleted' => true, 'message' => 'Rol eliminado correctamente.'];
        });

        $status = $result['deleted'] ? 200 : 409;
        if ($request->expectsJson()) {
            return response()->json($result, $status);
        }

        return redirect()->route('admin.roles.index')->with(
            $result['deleted'] ? 'status' : 'warning',
            $result['message']
        );
    }
}
