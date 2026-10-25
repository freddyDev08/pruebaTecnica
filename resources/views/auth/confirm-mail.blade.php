@extends('auth.layout')
@section('title', 'Revisa tu correo')
@section('heading', 'Revisa tu correo')
@section('subtitle', 'Solicitud de recuperación de contraseña')
@section('form')
<div class="text-center">
    @include('auth.partials.mail-icon')
    <p class="text-muted font-13 mt-3">Si ese correo corresponde a una cuenta, recibirás un enlace con instrucciones para restablecer tu contraseña.</p>
    <p class="text-muted font-13">Revisa también la carpeta de correo no deseado.</p>
    <a href="{{ route('login') }}" class="btn btn-md btn-block btn-primary waves-effect waves-light mt-4">Volver a iniciar sesión</a>
</div>
@endsection
