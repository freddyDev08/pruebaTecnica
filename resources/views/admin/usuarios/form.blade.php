@extends('administrador')

@section('title', $usuario ? 'Editar usuario' : 'Crear usuario')

@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-12"><div class="page-title-box"><h4 class="page-title">{{ $usuario ? 'Editar usuario' : 'Crear usuario' }}</h4></div></div>
    </div>

    <div class="row"><div class="col-lg-8 col-xl-7">
        <div class="card"><div class="card-body">
            <h4 class="header-title mb-3">{{ $usuario ? 'Datos del usuario' : 'Nuevo usuario interno' }}</h4>
            <form method="POST" action="{{ $usuario ? route('admin.users.update', $usuario) : route('admin.users.store') }}">
                @csrf
                @if ($usuario) @method('PUT') @endif

                <div class="form-group">
                    <label for="name">Nombre completo</label>
                    <input type="text" class="form-control" name="name" id="name" maxlength="255" required value="{{ old('name', $usuario?->name) }}">
                </div>
                <div class="form-group">
                    <label for="phone">Teléfono</label>
                    <input type="tel" class="form-control" name="phone" id="phone" maxlength="25" required value="{{ old('phone', $usuario?->phone) }}">
                </div>
                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" class="form-control" name="email" id="email" maxlength="255" required value="{{ old('email', $usuario?->email) }}">
                    @if ($usuario)
                        <small class="form-text text-muted">Si se modifica el correo, se enviará un enlace a la dirección nueva. El correo anterior seguirá activo hasta su confirmación.</small>
                    @else
                        <small class="form-text text-muted">Se enviará a este correo una contraseña temporal y un enlace de verificación. No se mostrará la contraseña en pantalla.</small>
                    @endif
                </div>
                @if(auth()->user()->allows('roles.manage'))
                <div class="form-group">
                    <label for="role_id">Rol</label>
                    <select name="role_id" id="role_id" class="form-control">
                        <option value="">Pendiente de asignación de rol</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" {{ (string) old('role_id', $usuario?->role_id) === (string) $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                @elseif (! $usuario)
                <p class="text-muted small mb-3">El usuario quedará pendiente de asignación de rol y no podrá ingresar hasta que alguien autorizado se lo asigne.</p>
                @endif
                <div class="d-flex justify-content-end mt-4">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-light mr-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">{{ $usuario ? 'Guardar cambios' : 'Crear usuario' }}</button>
                </div>
            </form>
        </div></div>
    </div></div>
</div>
@endsection
