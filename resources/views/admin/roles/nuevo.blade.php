<div class="modal fade" id="modalNuevoRol" tabindex="-1" role="dialog" aria-labelledby="tituloNuevoRol" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloNuevoRol">Nuevo rol</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="{{ route('admin.roles.store') }}" method="POST" id="formNuevoRol">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="_roles_form" value="1">
                    <div class="form-group">
                        <label for="nombreRol">Nombre del rol <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="nombreRol" class="form-control" maxlength="100"
                            value="{{ old('name') }}" placeholder="Ej. Supervisor" required>
                    </div>
                    <div class="form-group mb-0">
                        <label for="descripcionRol">Descripción breve <span class="text-danger">*</span></label>
                        <textarea name="description" id="descripcionRol" class="form-control" rows="3" maxlength="255"
                            placeholder="Descripción de las responsabilidades de este rol" required>{{ old('description') }}</textarea>
                        <small class="form-text text-muted">Máximo 255 caracteres.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarRol">Guardar rol</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(function () {
        @if (old('_roles_form') && $errors->any())
            $('#modalNuevoRol').modal('show');
        @endif
        $('#formNuevoRol').on('submit', function () {
            $('#btnGuardarRol').prop('disabled', true).text('Guardando...');
        });
    });
</script>
@endpush
