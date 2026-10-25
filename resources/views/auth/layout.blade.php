<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Acceso') | {{ config('app.name', 'Programa de Lealtad') }}</title>
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" id="bootstrap-stylesheet">
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" id="app-stylesheet">
    <link href="{{ asset('assets/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
    @stack('styles')
    <style>
        body.authentication-bg { min-height: 100vh; height: auto !important; }
        .account-pages { margin-top: 2rem !important; margin-bottom: 2rem !important; }
        .form-control.is-invalid { background-image: none; }
        .validation-message { font-size: .85rem; }
        .account-logo-box img { max-width: 100%; }
    </style>
</head>
<body class="authentication-bg bg-primary authentication-bg-pattern d-flex align-items-center pb-0">
    <div class="account-pages w-100">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card mb-0">
                        <div class="card-body p-4">
                            <div class="account-box">
                                <div class="account-logo-box">
                                    <div class="text-center">
                                        <a href="{{ route(auth()->user()?->homeRoute() ?? 'login') }}">
                                            <img src="{{ asset('assets/images/logo-dark.png') }}" alt="Programa de Lealtad" height="30">
                                        </a>
                                    </div>
                                    <h5 class="text-uppercase mb-1 mt-4">@yield('heading')</h5>
                                    <p class="mb-0">@yield('subtitle')</p>
                                </div>
                                <div class="account-content mt-4">
                                    @yield('form')
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="text-center text-white-50 mt-3 mb-0">Programa de Lealtad</p>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('assets/js/vendor.min.js') }}"></script>
    <script src="{{ asset('assets/js/app.min.js') }}"></script>
    <script src="{{ asset('assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    @include('partials.sweetalert-messages')
    @stack('scripts')
</body>
</html>
