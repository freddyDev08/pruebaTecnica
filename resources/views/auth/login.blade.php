@extends('auth.layout')
@section('title', 'Iniciar sesión')
@section('heading', 'Iniciar sesión')
@section('subtitle', 'Accede a tu cuenta del programa de lealtad')
@section('form')
<form class="form-horizontal" method="POST" action="{{ route('login.store') }}">
    @csrf
    <div class="form-group row">
        <div class="col-12">
            <label for="email">Correo electrónico</label>
            <input class="form-control @error('email') is-invalid @enderror" type="email" name="email" id="email"
                   value="{{ old('email') }}" required autocomplete="username" autofocus placeholder="correo@ejemplo.com">
        </div>
    </div>
    <div class="form-group row">
        <div class="col-12">
            <a href="{{ route('password.request') }}" class="text-muted float-right"><small>¿Olvidaste tu contraseña?</small></a>
            <label for="password">Contraseña</label>
            <input class="form-control @error('password') is-invalid @enderror" type="password" name="password"
                   id="password" required autocomplete="current-password" placeholder="Tu contraseña">
        </div>
    </div>
    <div class="form-group row">
        <div class="col-12">
            <div class="checkbox checkbox-success">
                <input id="remember" name="remember" value="1" type="checkbox" {{ old('remember') ? 'checked' : '' }}>
                <label for="remember">Mantener sesión iniciada</label>
            </div>
        </div>
    </div>
    <div class="form-group row text-center mt-2">
        <div class="col-12">
            <button class="btn btn-md btn-block btn-primary waves-effect waves-light" type="submit">Entrar</button>
        </div>
    </div>
</form>

@endsection
