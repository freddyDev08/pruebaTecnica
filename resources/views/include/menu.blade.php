<div class="left-side-menu">
    <div class="slimscroll-menu">
        <div id="sidebar-menu">
            <ul class="metismenu" id="side-menu">
                <li class="menu-title">Administración</li>
                @if (auth()->user()->allows('panel.view'))
                <li>
                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="fe-home"></i><span> Inicio </span>
                    </a>
                </li>
                @endif
                @if (auth()->user()->allows('clients.view'))
                <li>
                    <a href="{{ route('admin.clientes.index') }}" class="{{ request()->routeIs('admin.clientes.*') ? 'active' : '' }}">
                        <i class="fe-users"></i><span> Clientes </span>
                    </a>
                </li>
                @endif
                @if (auth()->user()->allows('roles.manage'))
                <li>
                    <a href="{{ route('admin.roles.index') }}" class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                        <i class="fe-shield"></i><span> Roles </span>
                    </a>
                </li>
                @endif
                @if (auth()->user()->allows('users.manage') || auth()->user()->allows('roles.manage'))
                <li>
                    <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <i class="fe-users"></i><span> Usuarios </span>
                    </a>
                </li>
                @endif
            </ul>
        </div>
        <div class="clearfix"></div>
    </div>
</div>
