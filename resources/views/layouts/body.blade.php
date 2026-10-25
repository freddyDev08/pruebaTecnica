@extends('administrador')

@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item">Programa de Lealtad</li>
                        <li class="breadcrumb-item active">Inicio</li>
                    </ol>
                </div>
                <h4 class="page-title">Panel administrativo</h4>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">Bienvenido, {{ auth()->user()->name }}</h4>
                    <p class="text-muted mb-2">Has iniciado sesión correctamente en el panel del programa de lealtad.</p>
                    <p class="mb-0">Rol: <strong>{{ auth()->user()->role?->name ?? 'Sin asignar' }}</strong></p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
