@extends('auth.layout')
@section('title', 'Verificar correo')
@section('heading', 'Confirmar correo')
@section('subtitle', 'Para continuar, verifica tu dirección de correo')
@section('form')
<div class="text-center">
    @include('auth.partials.mail-icon')
    <p class="text-muted font-13 mt-3">Enviamos un enlace de confirmación al correo <b>{{ auth()->user()->email }}</b>.
        Abre el mensaje y sigue las instrucciones. Si no aparece, revisa correo no deseado.</p>
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button class="btn btn-md btn-block btn-primary waves-effect waves-light mt-4" type="submit">Reenviar correo de verificación</button>
    </form>
    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <button type="submit" class="btn btn-link text-dark">Cerrar sesión</button>
    </form>
</div>
@endsection
