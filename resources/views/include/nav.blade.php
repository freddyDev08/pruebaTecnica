<div class="navbar-custom">
    <ul class="list-unstyled topnav-menu float-right mb-0">
        <li class="dropdown notification-list">
            <a class="nav-link dropdown-toggle nav-user mr-0 waves-effect waves-light" data-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                <img src="{{ asset('assets/images/users/avatar-1.jpg') }}" alt="Usuario" class="rounded-circle">
                <span class="pro-user-name ml-1">{{ auth()->user()->name }} <i class="mdi mdi-chevron-down"></i></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right profile-dropdown">
                <div class="dropdown-header noti-title">
                    <h6 class="text-overflow m-0">{{ auth()->user()->role?->name ?? 'Usuario' }}</h6>
                </div>
                <a href="{{ route('profile.show') }}" class="dropdown-item notify-item">
                    <i class="fe-user"></i><span>Mi perfil</span>
                </a>
                <div class="dropdown-divider"></div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="dropdown-item notify-item">
                        <i class="fe-log-out"></i><span>Cerrar sesión</span>
                    </button>
                </form>
            </div>
        </li>
    </ul>

    <div class="logo-box">
        <a href="{{ route('dashboard') }}" class="logo text-center">
            <span class="logo-lg"><img src="{{ asset('assets/images/logo-light.png') }}" alt="Programa de Lealtad" height="25"></span>
            <span class="logo-sm"><img src="{{ asset('assets/images/logo-sm.png') }}" alt="Programa de Lealtad" height="28"></span>
        </a>
    </div>

    <ul class="list-unstyled topnav-menu topnav-menu-left m-0">
        <li><button class="button-menu-mobile waves-effect waves-light" type="button" aria-label="Abrir menú"><i class="fe-menu"></i></button></li>
    </ul>
</div>
