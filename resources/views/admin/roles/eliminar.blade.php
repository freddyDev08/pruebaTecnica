<div class="modal fade" id="modalEliminarRol" tabindex="-1" role="dialog" aria-labelledby="tituloEliminarRol" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloEliminarRol">Eliminar rol</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">¿Seguro que deseas eliminar el rol <strong id="nombreRolEliminar"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarEliminarRol">Eliminar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(function () {
        var $modal = $('#modalEliminarRol');
        var $currentButton = null;

        $('#tablaRoles').on('click', '.js-delete-role', function () {
            $currentButton = $(this);
            var users = Number($currentButton.attr('data-users'));
            var roleName = $currentButton.attr('data-role');
            if (users > 0) {
                Swal.fire({
                    type: 'warning',
                    title: 'No se puede eliminar',
                    text: 'El rol "' + roleName + '" tiene ' + users + ' usuario(s) asignado(s). Debes reasignarlos antes de eliminarlo.',
                    confirmButtonText: 'Entendido'
                });
                return;
            }
            $('#nombreRolEliminar').text(roleName);
            $modal.modal('show');
        });

        $('#btnConfirmarEliminarRol').on('click', function () {
            if (!$currentButton) return;
            var $submit = $(this);
            $submit.prop('disabled', true).text('Eliminando...');
            $.ajax({
                url: $currentButton.attr('data-url'),
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                data: { _token: $('meta[name="csrf-token"]').attr('content'), _method: 'DELETE' },
                success: function (response) {
                    var dataTable = $('#tablaRoles').DataTable();
                    var $row = $currentButton.closest('tr');
                    if ($row.hasClass('child')) $row = $row.prev();
                    dataTable.row($row).remove().draw(false);
                    $modal.modal('hide');
                    Swal.fire({ type: 'success', title: 'Rol eliminado', text: response.message });
                },
                error: function (xhr) {
                    var message = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo eliminar el rol.';
                    if (xhr.status === 409 && xhr.responseJSON && xhr.responseJSON.users_count) {
                        $currentButton.attr('data-users', xhr.responseJSON.users_count);
                    }
                    $modal.modal('hide');
                    Swal.fire({ type: xhr.status === 409 ? 'warning' : 'error', title: 'Atención', text: message });
                },
                complete: function () {
                    $submit.prop('disabled', false).text('Eliminar');
                }
            });
        });
    });
</script>
@endpush
