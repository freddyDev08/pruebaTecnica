<div class="row">
    <div class="col-lg-6 mb-3">
        <div class="card h-100"><div class="card-body">
            <h4 class="header-title mb-3"><i class="fe-user mr-1"></i> Datos personales</h4>
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf @method('PUT')
                <div class="form-group">
                    <label for="name">Nombre</label>
                    <input type="text" name="name" id="name" maxlength="255" class="form-control" required value="{{ old('name', auth()->user()->name) }}">
                </div>
                <div class="form-group">
                    <label for="phone">Teléfono</label>
                    <input type="tel" name="phone" id="phone" maxlength="25" class="form-control" required value="{{ old('phone', auth()->user()->phone) }}">
                </div>
                <button type="submit" class="btn btn-primary">Guardar datos</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-6 mb-3">
        <div class="card h-100"><div class="card-body">
            <h4 class="header-title mb-3"><i class="fe-mail mr-1"></i> Correo electrónico</h4>
            <p class="mb-2">Correo actual: <strong>{{ auth()->user()->email }}</strong></p>
            @if ($pendingEmail)
                <p class="text-muted mb-2">Pendiente de confirmar: <strong>{{ $pendingEmail }}</strong></p>
                <form method="POST" action="{{ route('profile.email.cancel') }}" class="mb-3">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-secondary btn-sm" type="submit">Cancelar solicitud</button>
                </form>
            @endif
            <form method="POST" action="{{ route('profile.email.request') }}">
                @csrf
                <div class="form-group">
                    <label for="email">Nuevo correo</label>
                    <input type="email" name="email" id="email" maxlength="255" class="form-control" required value="{{ old('email') }}" autocomplete="email">
                    <small class="form-text text-muted">Se enviará un enlace al nuevo correo. No se cambiará hasta que lo confirmes.</small>
                </div>
                <button type="submit" class="btn btn-primary">Solicitar cambio</button>
            </form>
        </div></div>
    </div>
</div>
<div class="row" id="contrasena">
    <div class="col-lg-8 mb-3">
        <div class="card"><div class="card-body">
            <h4 class="header-title mb-3"><i class="fe-lock mr-1"></i> Cambiar contraseña</h4>
            <form method="POST" action="{{ route('password.change.update') }}">
                @csrf @method('PUT')
                <div class="form-group">
                    <label for="current_password">Contraseña actual</label>
                    <input class="form-control" id="current_password" name="current_password" type="password" required autocomplete="current-password">
                </div>
                <div class="form-group">
                    <label for="password">Nueva contraseña</label>
                    <input class="form-control" id="password" name="password" type="password" required autocomplete="new-password" minlength="10">
                    <small class="form-text text-muted">Mínimo 10 caracteres, incluyendo letras y números.</small>
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Confirmar nueva contraseña</label>
                    <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
                </div>
                <button class="btn btn-primary" type="submit">Actualizar contraseña</button>
                <p class="text-muted mt-2 mb-0"><small>Recibirás un correo de confirmación después del cambio.</small></p>
            </form>
        </div></div>
    </div>
</div>
