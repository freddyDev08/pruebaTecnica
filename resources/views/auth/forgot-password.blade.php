@extends('auth.layout')
@section('title', 'Recuperar contraseña')
@section('heading', 'Recuperar contraseña')
@section('subtitle', 'Te enviaremos un enlace para restablecer tu contraseña')
@section('form')
<form class="form-horizontal" method="POST" action="{{ route('password.email') }}">
    @csrf
    <div class="form-group row"><div class="col-12">
        <label for="email">Correo electrónico</label>
        <input class="form-control @error('email') is-invalid @enderror" type="email" name="email"
               id="email" value="{{ old('email') }}" required autofocus placeholder="correo@ejemplo.com">
    </div></div>
    <div class="form-group row text-center mt-2"><div class="col-12">
        <button class="btn btn-md btn-block btn-primary waves-effect waves-light" type="submit">Enviar enlace</button>
    </div></div>
</form>
<div class="row mt-4"><div class="col-sm-12 text-center">
    <p class="text-muted mb-0">Volver a <a href="{{ route('login') }}" class="text-dark ml-1"><b>Iniciar sesión</b></a></p>
</div></div>
@endsection
