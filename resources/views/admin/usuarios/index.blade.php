@extends('administrador')

@section('title', 'Usuarios')

@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-12"><div class="page-title-box"><h4 class="page-title">Usuarios</h4></div></div>
    </div>

    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
            <div>
                <h4 class="header-title mb-1">Usuarios del equipo interno</h4>
                <p class="text-muted mb-0">Administra los usuarios y asigna sus roles según tus permisos.</p>
            </div>
            @if(auth()->user()->allows('users.manage'))
                <a class="btn btn-primary mt-2 mt-sm-0" href="{{ route('admin.users.create') }}">
                    <i class="fe-plus mr-1"></i> Crear usuario
                </a>
            @endif
        </div>

        <div class="table-responsive">
            <table id="tablaUsuarios" class="table table-hover table-centered js-datatable w-100 mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Registro</th>
                        <th class="text-center" data-orderable="false">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($usuarios as $usuario)
                        <tr>
                            <td><strong>{{ $usuario->name }}</strong> @if ($usuario->is_super_root)<span class="badge badge-info">Super Root</span>@endif</td>
                            <td>{{ $usuario->email }}</td>
                            <td>{{ $usuario->phone ?: '—' }}</td>
                            <td class="js-user-role">@if($usuario->role){{ $usuario->role->name }}@else<span class="badge badge-warning">Pendiente de asignación de rol</span>@endif</td>
                            <td class="js-user-status" data-order="{{ $usuario->active ? 'Activo' : 'Deshabilitado' }}">
                                <span class="badge {{ $usuario->active ? 'badge-success' : 'badge-secondary' }}">{{ $usuario->active ? 'Activo' : 'Deshabilitado' }}</span>
                            </td>
                            <td data-order="{{ $usuario->created_at?->format('Y-m-d H:i:s') }}">{{ $usuario->created_at?->format('d/m/Y H:i') ?: '—' }}</td>
                            <td class="text-center text-nowrap">
                                @if (! $usuario->is_super_root && auth()->id() !== $usuario->id)
                                    @if(auth()->user()->allows('users.manage'))
                                        <a href="{{ route('admin.users.edit', $usuario) }}" class="btn btn-sm btn-outline-primary mb-1">
                                            <i class="fe-edit mr-1"></i> Editar
                                        </a>
                                        <button type="button" class="btn btn-sm {{ $usuario->active ? 'btn-outline-warning' : 'btn-outline-success' }} js-toggle-user mb-1"
                                            data-url="{{ route('admin.users.toggle', $usuario) }}"
                                            data-name="{{ $usuario->name }}"
                                            data-active="{{ $usuario->active ? 1 : 0 }}">
                                            {{ $usuario->active ? 'Deshabilitar' : 'Habilitar' }}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger js-delete-user mb-1"
                                            data-url="{{ route('admin.users.destroy', $usuario) }}"
                                            data-name="{{ $usuario->name }}">
                                            <i class="fe-trash-2 mr-1"></i> Eliminar
                                        </button>
                                    @endif
                                    @if(auth()->user()->allows('roles.manage'))
                                        <button type="button" class="btn btn-sm btn-outline-info js-assign-role mb-1"
                                            data-toggle="modal" data-target="#modalAsignarRol"
                                            data-url="{{ route('admin.users.role', $usuario) }}"
                                            data-name="{{ $usuario->name }}"
                                            data-role="{{ $usuario->role_id }}">
                                            <i class="fe-shield mr-1"></i> Asignar rol
                                        </button>
                                    @endif
                                @else
                                    <span class="text-muted">Protegido</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div></div>
</div>
@if(auth()->user()->allows('roles.manage'))
    @include('admin.usuarios.rol')
@endif
@endsection

@push('scripts')
<script>
    $(function () {
        var $table = $('#tablaUsuarios');
        var dataTable = $table.DataTable();
        var csrf = $('meta[name="csrf-token"]').attr('content');

        function parentRow($button) {
            var $row = $button.closest('tr');
            return $row.hasClass('child') ? $row.prev() : $row;
        }

        function showError(xhr) {
            var response = xhr.responseJSON || {};
            var firstError = response.errors ? Object.values(response.errors).flat()[0] : null;
            Swal.fire({
                type: xhr.status === 422 || xhr.status === 409 ? 'warning' : 'error',
                title: 'No se pudo realizar la operación',
                text: firstError || response.message || 'Ocurrió un error. Intenta nuevamente.'
            });
        }

        $table.on('click', '.js-assign-role', function () {
            var $button = $(this);
            $('#formAsignarRol').attr('action', $button.attr('data-url'));
            $('#nombreUsuarioRol').text($button.attr('data-name'));
            $('#rolUsuarioSelect').val($button.attr('data-role') || '');
        });

        $('#formAsignarRol').on('submit', function (event) {
            event.preventDefault();
            var $form = $(this);
            var $save = $form.find('button[type="submit"]');
            var $button = $table.find('.js-assign-role').filter(function () {
                return $(this).attr('data-url') === $form.attr('action');
            });
            $save.prop('disabled', true);
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                dataType: 'json',
                headers: {'Accept': 'application/json'},
                data: {_token: csrf, _method: 'PATCH', role_id: $('#rolUsuarioSelect').val()},
                success: function (response) {
                    var $row = parentRow($button);
                    var $roleCell = $row.find('.js-user-role');
                    $roleCell.empty();
                    if (response.role_id === null) {
                        $('<span class="badge badge-warning">Pendiente de asignación de rol</span>').appendTo($roleCell);
                    } else {
                        $roleCell.text(response.role_name);
                    }
                    $button.attr('data-role', response.role_id || '');
                    dataTable.row($row).invalidate('dom').draw(false);
                    $('#modalAsignarRol').modal('hide');
                    Swal.fire({type: 'success', title: 'Correcto', text: response.message});
                },
                error: showError,
                complete: function () {$save.prop('disabled', false);}
            });
        });

        $table.on('click', '.js-toggle-user', function () {
            var $button = $(this);
            var enabled = $button.attr('data-active') === '1';
            var next = enabled ? 0 : 1;
            Swal.fire({
                type: 'question',
                title: enabled ? '¿Deshabilitar usuario?' : '¿Habilitar usuario?',
                text: $button.attr('data-name'),
                showCancelButton: true,
                confirmButtonText: enabled ? 'Sí, deshabilitar' : 'Sí, habilitar',
                cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (!result.value) return;
                $button.prop('disabled', true);
                $.ajax({
                    url: $button.attr('data-url'),
                    type: 'POST',
                    dataType: 'json',
                    headers: {'Accept': 'application/json'},
                    data: {_token: csrf, _method: 'PATCH', active: next},
                    success: function (response) {
                        var active = !!response.active;
                        var $row = parentRow($button);
                        var $status = $row.find('.js-user-status');
                        $status.html('<span class="badge ' + (active ? 'badge-success' : 'badge-secondary') + '">' + (active ? 'Activo' : 'Deshabilitado') + '</span>');
                        $status.attr('data-order', active ? 'Activo' : 'Deshabilitado');
                        $button.attr('data-active', active ? '1' : '0')
                            .toggleClass('btn-outline-warning', active)
                            .toggleClass('btn-outline-success', !active)
                            .text(active ? 'Deshabilitar' : 'Habilitar');
                        dataTable.row($row).invalidate('dom').draw(false);
                        Swal.fire({type: 'success', title: 'Correcto', text: response.message});
                    },
                    error: showError,
                    complete: function () {$button.prop('disabled', false);}
                });
            });
        });

        $table.on('click', '.js-delete-user', function () {
            var $button = $(this);
            Swal.fire({
                type: 'warning',
                title: '¿Eliminar usuario?',
                text: 'El usuario ' + $button.attr('data-name') + ' ya no podrá iniciar sesión. El registro se conservará en la base de datos.',
                showCancelButton: true,
                confirmButtonColor: '#d9534f',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (!result.value) return;
                $button.prop('disabled', true);
                $.ajax({
                    url: $button.attr('data-url'),
                    type: 'POST',
                    dataType: 'json',
                    headers: {'Accept': 'application/json'},
                    data: {_token: csrf, _method: 'DELETE'},
                    success: function (response) {
                        dataTable.row(parentRow($button)).remove().draw(false);
                        Swal.fire({type: 'success', title: 'Usuario eliminado', text: response.message});
                    },
                    error: showError,
                    complete: function () {$button.prop('disabled', false);}
                });
            });
        });
    });
</script>
@endpush
