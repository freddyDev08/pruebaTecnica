@extends('auth.layout')
@section('title', 'Nueva contraseña')
@section('heading', 'Restablecer contraseña')
@section('subtitle', 'Escribe y confirma tu nueva contraseña')
@section('form')
<form class="form-horizontal" method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="form-group row"><div class="col-12">
        <label for="email">Correo electrónico</label>
        <input class="form-control" name="email" id="email" type="email" required readonly value="{{ old('email', $email) }}">
    </div></div>
    <div class="form-group row"><div class="col-12">
        <label for="password">Nueva contraseña</label>
        <input class="form-control" name="password" id="password" type="password" required autocomplete="new-password">
    </div></div>
    <div class="form-group row"><div class="col-12">
        <label for="password_confirmation">Confirmar nueva contraseña</label>
        <input class="form-control" name="password_confirmation" id="password_confirmation" type="password" required autocomplete="new-password">
    </div></div>
    <div class="form-group row text-center mt-2"><div class="col-12">
        <button class="btn btn-md btn-block btn-primary waves-effect waves-light" type="submit">Guardar contraseña</button>
    </div></div>
</form>
<div class="row mt-4"><div class="col-sm-12 text-center">
    <a href="{{ route('login') }}" class="text-dark"><b>Volver a iniciar sesión</b></a>
</div></div>
@endsection
