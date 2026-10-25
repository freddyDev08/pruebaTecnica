$(function () {
    if (!$.fn.DataTable) return;

    $('.js-datatable').each(function () {
        if ($.fn.DataTable.isDataTable(this)) return;

        var $table = $(this);
        var nonOrderableColumns = [];
        $table.find('thead th').each(function (index) {
            if ($(this).attr('data-orderable') === 'false') nonOrderableColumns.push(index);
        });

        $table.DataTable({
            responsive: true,
            autoWidth: false,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [],
            columnDefs: [{ targets: nonOrderableColumns, orderable: false, searchable: false }],
            language: {
                processing: 'Procesando...',
                search: 'Buscar:',
                lengthMenu: 'Mostrar _MENU_ registros',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                infoFiltered: '(filtrados de _MAX_ registros)',
                loadingRecords: 'Cargando...',
                zeroRecords: 'No se encontraron resultados',
                emptyTable: 'No hay registros disponibles',
                paginate: { first: 'Primero', previous: 'Anterior', next: 'Siguiente', last: 'Último' },
                aria: { sortAscending: ': activar orden ascendente', sortDescending: ': activar orden descendente' }
            }
        });
    });
});
