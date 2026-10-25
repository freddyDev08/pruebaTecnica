@extends('administrador')
@section('title', 'Mi perfil')
@section('contenido')
<div class="container-fluid">
    <div class="row"><div class="col-12"><div class="page-title-box">
        <h4 class="page-title">Mi perfil</h4>
    </div></div></div>
    @include('profile.fields')
</div>
@endsection
