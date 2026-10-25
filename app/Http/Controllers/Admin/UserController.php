<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailChangeRequest;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ConfirmProfileEmailChange;
use App\Notifications\StaffInvitationNotification;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class UserController extends Controller
{
    private function authorizeListing(Request $request): void
    {
        abort_unless($request->user()->allows('users.manage') || $request->user()->allows('roles.manage'), 403);
    }

    private function authorizeUsers(Request $request): void
    {
        abort_unless($request->user()->allows('users.manage'), 403);
    }

    private function authorizeRoles(Request $request): void
    {
        abort_unless($request->user()->allows('roles.manage'), 403);
    }

    private function authorizeTarget(Request $request, User $user): void
    {
        abort_unless($user->isStaff(), 404);
        abort_if($user->is_super_root, 403, 'La cuenta Super Root se administra desde Mi perfil.');
        abort_if($request->user()->is($user), 403, 'No puedes modificar tu propia cuenta desde este módulo.');
    }

    public function index(Request $request): View
    {
        $this->authorizeListing($request);

        return view('admin.usuarios.index', [
            'usuarios' => User::with('role')->where('account_type', 'staff')->orderBy('name')->get(),
            'roles' => $request->user()->allows('roles.manage') ? Role::orderBy('name')->get() : collect(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeUsers($request);

        return view('admin.usuarios.form', [
            'usuario' => null,
            'roles' => $request->user()->allows('roles.manage') ? Role::orderBy('name')->get() : collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeUsers($request);
        $canAssignRoles = $request->user()->allows('roles.manage');
        abort_if(! $canAssignRoles && $request->exists('role_id'), 403);

        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[0-9+()\s-]{7,25}$/'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
        ];
        if ($canAssignRoles) {
            $rules['role_id'] = ['nullable', 'integer', Rule::exists('roles', 'id')->whereNull('deleted_at')];
        }

        $data = $request->validate($rules, [
            'email.unique' => 'El correo ya pertenece a una cuenta, incluso si está eliminada.',
            'role_id.exists' => 'Selecciona un rol disponible.',
        ]);
        $temporaryPassword = Str::random(24);

        try {
            DB::transaction(function () use ($data, $temporaryPassword, $request, $canAssignRoles) {
                $user = User::create([
                    'name' => trim($data['name']),
                    'phone' => trim($data['phone']),
                    'email' => $data['email'],
                    'password' => $temporaryPassword,
                    'role_id' => $canAssignRoles ? ($data['role_id'] ?? null) : null,
                    'account_type' => 'staff',
                    'approval_status' => 'approved',
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                    'active' => true,
                    'must_change_password' => true,
                ]);

                $user->notify(new StaffInvitationNotification($temporaryPassword));
            });
        } catch (UniqueConstraintViolationException $e) {
            return back()->withInput()->withErrors(['email' => 'Este correo ya se encuentra registrado.']);
        } catch (Throwable $e) {
            report($e);
            return back()->withInput()->with('warning',
                'No se pudo crear la cuenta y enviar sus credenciales. Revisa el servicio de correo e intenta nuevamente.');
        }

        return redirect()->route('admin.users.index')->with('status',
            $canAssignRoles && ! empty($data['role_id'])
                ? 'Usuario creado. Se enviaron sus credenciales al correo.'
                : 'Usuario creado y correo enviado. Está pendiente de que le asignen un rol para ingresar.');
    }

    public function edit(Request $request, User $user): View
    {
        $this->authorizeUsers($request);
        $this->authorizeTarget($request, $user);

        return view('admin.usuarios.form', [
            'usuario' => $user,
            'roles' => $request->user()->allows('roles.manage') ? Role::orderBy('name')->get() : collect(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUsers($request);
        $this->authorizeTarget($request, $user);
        $canAssignRoles = $request->user()->allows('roles.manage');
        abort_if(! $canAssignRoles && $request->exists('role_id'), 403);

        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[0-9+()\s-]{7,25}$/'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ];
        if ($canAssignRoles) {
            $rules['role_id'] = ['nullable', 'integer', Rule::exists('roles', 'id')->whereNull('deleted_at')];
        }
        $data = $request->validate($rules);

        DB::transaction(function () use ($user, $data, $canAssignRoles) {
            if ($canAssignRoles) {
                $this->lockAdministratorRole();
            }
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $values = [
                'name' => trim($data['name']),
                'phone' => trim($data['phone']),
            ];
            if ($canAssignRoles && array_key_exists('role_id', $data) && $locked->role_id !== ($data['role_id'] === null ? null : (int) $data['role_id'])) {
                $this->assertNotLastAdministrator($locked);
                $values['role_id'] = $data['role_id'] === null ? null : (int) $data['role_id'];
            }
            $locked->update($values);
        });

        if ($data['email'] !== $user->email) {
            $token = Str::random(64);
            $pending = EmailChangeRequest::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'new_email' => $data['email'],
                    'token_hash' => hash('sha256', $token),
                    'expires_at' => now()->addHour(),
                ]
            );
            $url = URL::temporarySignedRoute('profile.email.confirm', now()->addHour(), ['token' => $token]);

            try {
                Notification::route('mail', $data['email'])->notify(new ConfirmProfileEmailChange($url));
            } catch (Throwable $e) {
                report($e);
                $pending->delete();
                return redirect()->route('admin.users.index')->with('warning',
                    'Se actualizaron los datos, pero no se pudo enviar la confirmación al correo nuevo. El correo anterior permanece activo.');
            }

            return redirect()->route('admin.users.index')->with('status',
                'Datos actualizados. El correo nuevo quedará activo cuando su propietario confirme el enlace enviado.');
        }

        return redirect()->route('admin.users.index')->with('status', 'Datos del usuario actualizados correctamente.');
    }

    public function assignRole(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $this->authorizeRoles($request);
        $this->authorizeTarget($request, $user);
        $data = $request->validate([
            'role_id' => ['nullable', 'integer', Rule::exists('roles', 'id')->whereNull('deleted_at')],
        ]);

        $result = DB::transaction(function () use ($user, $data): array {
            $this->lockAdministratorRole();
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $newRoleId = empty($data['role_id']) ? null : (int) $data['role_id'];
            if ($locked->role_id !== $newRoleId) {
                $this->assertNotLastAdministrator($locked);
                $locked->update(['role_id' => $newRoleId]);
                if ($newRoleId === null) {
                    $locked->forceFill(['remember_token' => null])->save();
                    DB::table('sessions')->where('user_id', $locked->id)->delete();
                }
            }

            $locked->unsetRelation('role');

            return [
                'role_id' => $locked->role_id,
                'role_name' => $locked->role?->name ?? 'Pendiente de asignación de rol',
                'message' => $newRoleId === null
                    ? 'El usuario quedó pendiente de asignación de rol.'
                    : 'Rol asignado correctamente.',
            ];
        });

        return $this->respond($request, $result);
    }

    public function toggleActive(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $this->authorizeUsers($request);
        $this->authorizeTarget($request, $user);
        $data = $request->validate(['active' => ['required', 'boolean']]);
        $enable = (bool) $data['active'];

        $result = DB::transaction(function () use ($user, $enable): array {
            $this->lockAdministratorRole();
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (! $enable && $locked->active) {
                $this->assertNotLastAdministrator($locked);
            }
            $values = ['active' => $enable];
            if (! $enable) {
                $values['remember_token'] = null;
            }
            $locked->forceFill($values)->save();
            if (! $enable) {
                DB::table('sessions')->where('user_id', $locked->id)->delete();
            }

            return [
                'active' => $locked->active,
                'message' => $enable ? 'Usuario habilitado correctamente.' : 'Usuario deshabilitado correctamente.',
            ];
        });

        return $this->respond($request, $result);
    }

    public function destroy(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $this->authorizeUsers($request);
        $this->authorizeTarget($request, $user);

        $result = DB::transaction(function () use ($user): array {
            $this->lockAdministratorRole();
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $this->assertNotLastAdministrator($locked);
            $locked->forceFill(['active' => false, 'remember_token' => null])->save();
            $locked->delete();
            DB::table('sessions')->where('user_id', $locked->id)->delete();

            return ['deleted' => true, 'message' => 'Usuario eliminado correctamente.'];
        });

        return $this->respond($request, $result);
    }

    private function lockAdministratorRole(): void
    {
        Role::where('slug', 'administrador')->lockForUpdate()->firstOrFail();
    }

    private function assertNotLastAdministrator(User $user): void
    {
        if (! $user->active || ! $user->isStaff() || $user->role?->slug !== 'administrador') {
            return;
        }

        $remaining = User::where('account_type', 'staff')
            ->where('approval_status', 'approved')
            ->where('active', true)
            ->whereNotNull('email_verified_at')
            ->where('role_id', $user->role_id)
            ->whereKeyNot($user->id)
            ->count();

        if ($remaining === 0) {
            throw ValidationException::withMessages([
                'user' => 'No se puede deshabilitar, eliminar ni retirar el rol al último administrador activo.',
            ]);
        }
    }

    private function respond(Request $request, array $result): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json($result);
        }
        return redirect()->route('admin.users.index')->with('status', $result['message']);
    }
}
