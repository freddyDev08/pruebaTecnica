<div class="modal fade" id="modalPermisosRol" tabindex="-1" role="dialog" aria-labelledby="tituloPermisosRol" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloPermisosRol">Asignar permisos</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <form id="formPermisosRol" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <p class="text-muted">Selecciona los permisos que tendrá este rol.</p>
                    <div class="row">
                        @foreach ($permisos as $clave => $descripcion)
                            <div class="col-12 col-md-6 mb-3">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input js-permiso-check"
                                        name="permissions[]" value="{{ $clave }}" id="permiso-{{ $clave }}">
                                    <label class="custom-control-label" for="permiso-{{ $clave }}">{{ $descripcion }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarPermisos">Guardar permisos</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(function () {
        var $modal = $('#modalPermisosRol');
        var $form = $('#formPermisosRol');
        var $button = $('#btnGuardarPermisos');
        var $selectedRowButton = null;

        $('#tablaRoles').on('click', '.js-role-permissions', function () {
            $selectedRowButton = $(this);
            var stored = $selectedRowButton.attr('data-permissions');
            var active = [];
            try {
                active = JSON.parse(stored || '[]');
            } catch (e) {
                active = [];
            }
            $form.attr('action', $selectedRowButton.attr('data-url'));
            $('#tituloPermisosRol').text('Permisos: ' + $selectedRowButton.attr('data-role'));
            $form.find('.js-permiso-check').each(function () {
                $(this).prop('checked', active.indexOf($(this).val()) !== -1);
            });
            $modal.modal('show');
        });

        $form.on('submit', function (event) {
            event.preventDefault();
            $button.prop('disabled', true).text('Guardando...');
            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                headers: { 'Accept': 'application/json' },
                success: function (response) {
                    if ($selectedRowButton) {
                        $selectedRowButton.attr('data-permissions', JSON.stringify(response.permissions));
                    }
                    $modal.modal('hide');
                    Swal.fire({ type: 'success', title: 'Permisos guardados', text: response.message, confirmButtonText: 'Aceptar' });
                },
                error: function (xhr) {
                    var message = (xhr.responseJSON && xhr.responseJSON.message) || 'No fue posible guardar los permisos.';
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = Object.values(xhr.responseJSON.errors);
                        if (errors.length) message = errors[0].join(' ');
                    }
                    Swal.fire({ type: 'error', title: 'No se pudieron guardar', text: message, confirmButtonText: 'Aceptar' });
                },
                complete: function () {
                    $button.prop('disabled', false).text('Guardar permisos');
                }
            });
        });
    });
</script>
@endpush
