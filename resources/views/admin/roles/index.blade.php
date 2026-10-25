@extends('administrador')

@section('title', 'Roles')

@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Roles</h4>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <div>
                    <h4 class="header-title mb-1">Listado de roles</h4>
                    <p class="text-muted mb-0">Administración de los roles y permisos del personal interno.</p>
                </div>
                <button type="button" class="btn btn-primary mt-2 mt-sm-0" data-toggle="modal" data-target="#modalNuevoRol">
                    <i class="fe-plus mr-1"></i> Nuevo rol
                </button>
            </div>
            <div class="table-responsive">
                <table id="tablaRoles" class="table table-hover table-centered js-datatable w-100 mb-0">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th class="text-center">Usuarios asignados</th>
                            <th>Fecha de creación</th>
                            <th class="text-center" data-orderable="false">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roles as $role)
                            <tr>
                                <td><strong>{{ $role->name }}</strong></td>
                                <td>{{ $role->description ?: 'Sin descripción' }}</td>
                                <td class="text-center">{{ $role->users_count }}</td>
                                <td data-order="{{ $role->created_at?->format('Y-m-d H:i:s') }}">{{ $role->created_at?->format('d/m/Y H:i') ?: '—' }}</td>
                                <td class="text-center text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary js-role-permissions mb-1"
                                        data-url="{{ route('admin.roles.permissions.update', $role) }}"
                                        data-role="{{ $role->name }}"
                                        data-permissions='@json($role->permissions ?? [])'
                                        aria-label="Asignar permisos a {{ $role->name }}">
                                        <i class="fe-shield mr-1"></i> Asignar permisos
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger js-delete-role mb-1"
                                        data-url="{{ route('admin.roles.destroy', $role) }}"
                                        data-role="{{ $role->name }}"
                                        data-users="{{ $role->users_count }}"
                                        aria-label="Eliminar rol {{ $role->name }}">
                                        <i class="fe-trash-2 mr-1"></i> Eliminar
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('admin.roles.nuevo')
@include('admin.roles.permisos')
@include('admin.roles.eliminar')
@endsection
