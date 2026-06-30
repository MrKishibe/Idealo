$(document).ready(function() {
    // 1. Inicializar DataTables
    var table = $('#tablaEmpleados').DataTable({
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json"
        },
        "order": [[0, "desc"]]
    });

    // 2. Filtro de activos/inactivos
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            var estadoColumna = $(table.row(dataIndex).node()).find('td:nth-child(6) span').text().toLowerCase().trim();
            var vistaActual = $('#btnAlternarEstado').attr('data-vista');
            
            if (vistaActual === 'activos') {
                return estadoColumna === 'activo';
            } else {
                return estadoColumna === 'inactivo';
            }
        }
    );

    table.draw();

    // 3. Alternar Vista
    $('#btnAlternarEstado').click(function() {
        var vista = $(this).attr('data-vista');
        if (vista === 'activos') {
            $(this).attr('data-vista', 'inactivos');
            $(this).removeClass('btn-outline-secondary').addClass('btn-secondary');
            $('#txtBotonEstado').text('Ver activos');
            $('#iconoEstado').removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
            $('#tituloVista').text('Empleados Inhabilitados');
        } else {
            $(this).attr('data-vista', 'activos');
            $(this).removeClass('btn-secondary').addClass('btn-outline-secondary');
            $('#txtBotonEstado').text('Ver inhabilitados');
            $('#iconoEstado').removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
            $('#tituloVista').text('Gestión de Empleados');
        }
        table.draw();
    });

    // 4. Guardar Nuevo Empleado (sin rol ni password)
    $('#btnEnvio').click(function(e) {
        e.preventDefault();
        var form = $('#formEmpleado')[0];
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        var datos = {
            ajax: 1,
            cedula: $('#reg_cedula').val(),
            nombres: $('#reg_nombres').val(),
            apellidos: $('#reg_apellidos').val(),
            telefono: $('#reg_telefono').val(),
            cargo: $('#reg_cargo').val(),
            salario: $('#reg_salario').val(),
            direccion: $('#reg_direccion').val()
        };

        $.ajax({
            url: 'index.php?controller=empleado&action=guardar',
            type: 'POST',
            data: datos,
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Registrado!',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: res.message
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error de comunicación con el servidor.'
                });
            }
        });
    });

    // 5. Cargar Modal Editar Activo
    $(document).on('click', '.btnEditarActivo', function() {
        var id = $(this).data('id');
        var cedula = $(this).data('cedula');
        var nombres = $(this).data('nombres');
        var apellidos = $(this).data('apellidos');
        var telefono = $(this).data('telefono');
        var direccion = $(this).data('direccion');
        var cargo = $(this).data('cargo');
        var salario = $(this).data('salario');

        $('#edit_activo_id_empleado').val(id);
        $('#edit_activo_cedula').val(cedula);
        $('#edit_activo_nombres').val(nombres);
        $('#edit_activo_apellidos').val(apellidos);
        $('#edit_activo_telefono').val(telefono);
        $('#edit_activo_direccion').val(direccion);
        $('#edit_activo_cargo').val(cargo);
        $('#edit_activo_salario').val(salario);

        var myModal = new bootstrap.Modal(document.getElementById('modalEditarActivo'));
        myModal.show();
    });

    // 6. Guardar Cambios Activo
    $('#btnGuardarEdicionActivo').click(function(e) {
        e.preventDefault();
        var form = $('#formEditarActivo')[0];
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        var datos = {
            ajax: 1,
            id_empleado: $('#edit_activo_id_empleado').val(),
            nombres: $('#edit_activo_nombres').val(),
            apellidos: $('#edit_activo_apellidos').val(),
            telefono: $('#edit_activo_telefono').val(),
            cargo: $('#edit_activo_cargo').val(),
            salario: $('#edit_activo_salario').val(),
            direccion: $('#edit_activo_direccion').val()
        };

        $.ajax({
            url: 'index.php?controller=empleado&action=editar',
            type: 'POST',
            data: datos,
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Actualizado!',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: res.message
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error al guardar los cambios.'
                });
            }
        });
    });

    // 7. Inactivar Empleado (Papelera)
    $(document).on('click', '.btnCambiarEstado', function() {
        var id = $(this).data('id');
        var nombre = $(this).data('nombre');

        Swal.fire({
            title: '¿Inhabilitar empleado?',
            text: `¿Estás seguro de que deseas inhabilitar a ${nombre}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, inhabilitar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'index.php?controller=empleado&action=cambiarEstado',
                    type: 'POST',
                    data: {
                        id_empleado: id,
                        status_empleado: 'inactivo',
                        ajax: 1
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Inhabilitado!',
                                text: res.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: res.message
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo inhabilitar al empleado.'
                        });
                    }
                });
            }
        });
    });

    // 8. Cargar Modal Inactivo (para reactivar)
    $(document).on('click', '.btnEditarInactivo', function() {
        var id = $(this).data('id');
        var nombre = $(this).data('nombre');

        $('#edit_inactivo_id_empleado').val(id);
        $('#edit_inactivo_nombre_completo').val(nombre);
        $('#edit_inactivo_status').val('inactivo');

        var myModal = new bootstrap.Modal(document.getElementById('modalEditarInactivo'));
        myModal.show();
    });

    // 9. Guardar Cambios Inactivo (Reactivación)
    $('#btnGuardarEdicionInactivo').click(function(e) {
        e.preventDefault();
        var id = $('#edit_inactivo_id_empleado').val();
        var nuevoEstado = $('#edit_inactivo_status').val();

        $.ajax({
            url: 'index.php?controller=empleado&action=cambiarEstado',
            type: 'POST',
            data: {
                id_empleado: id,
                status_empleado: nuevoEstado,
                ajax: 1
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Actualizado!',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: res.message
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error al procesar el estado.'
                });
            }
        });
    });
});