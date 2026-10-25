<div class="modal fade" id="modalAsignarRol" tabindex="-1" role="dialog" aria-labelledby="modalAsignarRolTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAsignarRolTitulo">Asignar rol</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <form id="formAsignarRol" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <p class="mb-3">Usuario: <strong id="nombreUsuarioRol"></strong></p>
                    <div class="form-group mb-0">
                        <label for="rolUsuarioSelect">Rol</label>
                        <select class="form-control" id="rolUsuarioSelect" name="role_id">
                            <option value="">Pendiente de asignación de rol</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Sin rol, el usuario no podrá acceder al panel.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar rol</button>
                </div>
            </form>
        </div>
    </div>
</div>
