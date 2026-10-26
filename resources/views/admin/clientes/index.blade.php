@extends('administrador')

@section('title', 'Clientes')

@section('contenido')
<div class="container-fluid">
    <div class="row">
        <div class="col-12"><div class="page-title-box"><h4 class="page-title">Clientes</h4></div></div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <div>
                    <h4 class="header-title mb-1">Clientes de la tienda</h4>
                    <p class="text-muted mb-1">Registros importados desde fakeapi.dev.</p>
                    <small class="text-muted">Total registrado: <strong id="cantidadClientes">{{ $total }}</strong>. Última sincronización: {{ $ultimaSync ? \Carbon\Carbon::parse($ultimaSync->finished_at)->format('d/m/Y H:i') : 'Sin sincronizar' }}</small>
                </div>
                @if($puedeSincronizar)
                <button type="button" class="btn btn-primary mt-2 mt-sm-0" id="btnSincronizarClientes">
                    <i class="fe-refresh-cw mr-1"></i> Sincronizar clientes
                </button>
                @endif
            </div>
            <table id="tablaClientes" class="table table-hover table-centered w-100 mb-0 text-nowrap">
                <thead>
                    <tr>
                        <th>ID API</th>
                        <th>Foto</th>
                        <th>Nombre</th>
                        <th>Nombres</th>
                        <th>Apellidos</th>
                        <th>Usuario API</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Fecha de nacimiento</th>
                        <th>Género</th>
                        <th>Edad</th>
                        <th>Idioma</th>
                        <th>Dirección</th>
                        <th>Empresa</th>
                        <th>Puesto</th>
                        <th>Registro API</th>
                        <th data-orderable="false" class="text-center">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@include('admin.clientes.detalle')
@endsection

@push('scripts')
<script>
$(function () {
    var escapeHtml = $.fn.dataTable.render.text();
    function safe(value) { return value === null || value === undefined || value === '' ? '—' : escapeHtml.display(value); }
    var tabla = $('#tablaClientes').DataTable({
        ajax: '{{ route('admin.clientes.data') }}',
        processing: true,
        responsive: false,
        scrollX: true,
        autoWidth: false,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[0, 'asc']],
        columns: [
            {data: 'external_id'},
            {data: 'avatar', orderable: false, searchable: false, render: function (value, type) {
                if (type !== 'display') return '';
                if (!value || !/^https?:\/\//i.test(value)) return '—';
                return $('<img>').attr({src: value, alt: 'Avatar', width: '32', height: '32'}).addClass('rounded-circle').prop('outerHTML');
            }},
            {data: 'name', render: function (value) { return safe(value); }},
            {data: 'first_name', render: function (value) { return safe(value); }},
            {data: 'last_name', render: function (value) { return safe(value); }},
            {data: 'username', render: function (value) { return safe(value); }},
            {data: 'email', render: function (value) { return safe(value); }},
            {data: 'phone', render: function (value) { return safe(value); }},
            {data: 'dob', render: function (value) { return safe(value ? String(value).substring(0, 10) : null); }},
            {data: 'gender', render: function (value) { return safe(value); }},
            {data: 'age', render: function (value) { return safe(value); }},
            {data: 'locale', render: function (value) { return safe(value); }},
            {data: 'address', render: function (value) { return safe(value ? JSON.stringify(value) : null); }},
            {data: 'company', render: function (value) { return safe(value); }},
            {data: 'job_title', render: function (value) { return safe(value); }},
            {data: 'source_created_at', render: function (value) { return safe(value ? String(value).substring(0, 10) : null); }},
            {data: null, orderable: false, searchable: false, className: 'text-center', render: function () {
                return '<button type="button" class="btn btn-sm btn-outline-primary js-ver-cliente"><i class="fe-eye mr-1"></i> Ver datos</button>';
            }}
        ],
        language: {
            processing: 'Procesando...', search: 'Buscar:', lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros', infoEmpty: 'Sin registros',
            infoFiltered: '(filtrados de _MAX_ registros)', zeroRecords: 'No se encontraron resultados',
            emptyTable: 'No hay clientes sincronizados',
            paginate: {first: 'Primero', previous: 'Anterior', next: 'Siguiente', last: 'Último'}
        }
    });

    $('#tablaClientes').on('click', '.js-ver-cliente', function () {
        var fila = $(this).closest('tr');
        if (fila.hasClass('child')) fila = fila.prev();
        var cliente = tabla.row(fila).data();
        if (!cliente) return;
        $('#tituloDetalleCliente').text(cliente.name || 'Cliente #' + cliente.external_id);
        $('#detalleClienteJson').text(JSON.stringify(cliente.raw_data || {}, null, 2));
        $('#modalDetalleCliente').modal('show');
    });

    $('#btnSincronizarClientes').on('click', function () {
        var boton = $(this);
        Swal.fire({type: 'question', title: '¿Sincronizar clientes?', text: 'Se consultará fakeapi.dev y se actualizarán los registros en MySQL.', showCancelButton: true, confirmButtonText: 'Sincronizar', cancelButtonText: 'Cancelar'}).then(function (respuesta) {
            if (!respuesta.value) return;
            boton.prop('disabled', true);
            Swal.fire({title: 'Sincronizando...', text: 'Consultando los clientes de la tienda.', allowOutsideClick: false, onBeforeOpen: function () { Swal.showLoading(); }});
            $.ajax({
                url: '{{ route('admin.clientes.sync') }}', type: 'POST', dataType: 'json',
                headers: {'Accept': 'application/json'},
                data: {_token: $('meta[name="csrf-token"]').attr('content')},
                success: function (respuesta) {
                    tabla.ajax.reload(null, false);
                    $('#cantidadClientes').text(respuesta.result.total);
                    Swal.fire({type: 'success', title: 'Sincronización completada', text: respuesta.result.received + ' registros recibidos. ' + respuesta.result.skipped + ' omitidos.'});
                },
                error: function (xhr) {
                    Swal.fire({type: 'error', title: 'No se pudo sincronizar', text: (xhr.responseJSON && xhr.responseJSON.message) || 'Ocurrió un error al consultar la API.'});
                },
                complete: function () { boton.prop('disabled', false); }
            });
        });
    });
});
</script>
@endpush
