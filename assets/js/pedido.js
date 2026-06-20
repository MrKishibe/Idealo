$(document).ready(function() {
    // 1. Limpiar por completo el almacenamiento viejo del navegador para esta página
    localStorage.clear();
    sessionStorage.clear();

    // 2. Inicializar la tabla asegurando la destrucción de estados previos
    $('#tablaTipoPedido').DataTable({
        "bStateSave": false, // Evita que guarde datos antiguos en el navegador
        "destroy": true,     // Destruye cualquier instancia previa con datos basura
        "language": {
            "decimal": "",
            "emptyTable": "No hay tipos de pedido guardados en la sesión",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "infoEmpty": "Mostrando 0 a 0 de 0 registros",
            "infoFiltered": "(filtrado de un total de _MAX_ registros)",
            "thousands": ",",
            "lengthMenu": "Mostrar _MENU_ registros",
            "loadingRecords": "Cargando...",
            "processing": "Procesando...",
            "search": "Buscar:",
            "zeroRecords": "No se encontraron coincidencias",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        },
        "pageLength": 10,
        "responsive": true
    });

    // Mapeo automático al abrir el modal de edición
    $(document).on('click', '.btnEditarTipo', function() {
        $('#edit_id_tipo').val($(this).data('id'));
        $('#edit_nombre_tipo').val($(this).data('nombre'));
        
        const status = $(this).data('status');
        const statusFormateado = status.charAt(0).toUpperCase() + status.slice(1);
        $('#edit_status_tipo').val(statusFormateado);

        const modal = new bootstrap.Modal(document.getElementById('modalEditarTipo'));
        modal.show();
    });
});