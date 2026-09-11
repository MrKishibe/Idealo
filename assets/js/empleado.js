const URL_EMPLEADO = 'index.php?controller=empleado';

$(document).ready(function () {

    let tablaEmpleados = $('#tablaEmpleados').DataTable({
        language: {
            url: 'assets/js/es-ES.json'
        },
        responsive: true,
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: [6] }
        ]
    });

    let vistaActual = 'activos';

    function filtrarPorEstado() {
        if (vistaActual === 'activos') {
            tablaEmpleados.column(5).search('^Activo$', true, false).draw();
            $('#txtBotonEstado').text('Ver inhabilitados');
            $('#iconoEstado').removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
            $('#tituloVista').text('Gestión de Empleados');
        } else {
            tablaEmpleados.column(5).search('^Inactivo$', true, false).draw();
            $('#txtBotonEstado').text('Ver activos');
            $('#iconoEstado').removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
            $('#tituloVista').text('Empleados Inhabilitados');
        }
    }

    filtrarPorEstado();

    function recargarTabla() {
        $.getJSON(URL_EMPLEADO + '&ajax=listar', function (res) {
            tablaEmpleados.clear();

            res.empleados.forEach(function (emp) {
                let nombre = emp.nombres + ' ' + emp.apellidos;
                let estado = emp.status_empleado;
                let esActivo = estado === 'Activo';

                let acciones = '';
                if (esActivo) {
                    acciones =
                        '<div class="text-center d-flex justify-content-center gap-1">' +
                            '<button class="btn btn-sm btn-outline-primary btnEditarActivo"' +
                                ' data-id="' + emp.id_empleado + '"' +
                                ' data-cedula="' + (emp.cedula || '') + '"' +
                                ' data-nombres="' + (emp.nombres || '') + '"' +
                                ' data-apellidos="' + (emp.apellidos || '') + '"' +
                                ' data-telefono="' + (emp.telefono || '') + '"' +
                                ' data-direccion="' + (emp.direccion || '') + '"' +
                                ' data-cargo="' + (emp.cargo || '') + '"' +
                                ' data-salario="' + (emp.salario || '') + '">' +
                                '<i class="bi bi-pencil-square"></i>' +
                            '</button>' +
                            '<button class="btn btn-sm btn-outline-danger btnCambiarEstado"' +
                                ' data-id="' + emp.id_empleado + '"' +
                                ' data-nombre="' + nombre + '">' +
                                '<i class="bi bi-trash3-fill"></i>' +
                            '</button>' +
                        '</div>';
                } else {
                    acciones =
                        '<div class="text-center d-flex justify-content-center gap-1">' +
                            '<button class="btn btn-sm btn-outline-warning btnEditarInactivo"' +
                                ' data-id="' + emp.id_empleado + '"' +
                                ' data-nombre="' + nombre + '">' +
                                '<i class="bi bi-pencil-square"></i> Editar / Reactivar' +
                            '</button>' +
                        '</div>';
                }

                tablaEmpleados.row.add([
                    '<span class="fw-bold">' + emp.cedula + '</span>',
                    '<div class="fw-bold text-dark">' + nombre + '</div>' +
                        '<small class="text-muted"><i class="bi bi-geo-alt"></i> ' + (emp.direccion || 'Sin dirección') + '</small>',
                    emp.telefono || 'N/A',
                    emp.cargo,
                    '<span class="text-success fw-bold">$' + parseFloat(emp.salario).toFixed(2) + '</span>',
                    '<span class="badge ' + (esActivo ? 'bg-success' : 'bg-danger') + '">' + estado + '</span>',
                    acciones
                ]);
            });

            tablaEmpleados.draw();
            filtrarPorEstado();
        });
    }

    $('#btnAlternarEstado').on('click', function () {
        vistaActual = vistaActual === 'activos' ? 'inactivos' : 'activos';
        $(this).attr('data-vista', vistaActual);
        filtrarPorEstado();
    });

    $('#btnEnvio').on('click', function () {
        let cedula    = $('#reg_cedula').val().trim();
        let nombres   = $('#reg_nombres').val().trim();
        let apellidos = $('#reg_apellidos').val().trim();
        let telefono  = $('#reg_telefono').val().trim();
        let direccion = $('#reg_direccion').val().trim();
        let cargo     = $('#reg_cargo').val();
        let salario   = $('#reg_salario').val().trim();

        if (!cedula || !nombres || !apellidos || !cargo || !salario) {
            Swal.fire('Campos incompletos', 'Por favor complete todos los campos obligatorios.', 'warning');
            return;
        }

        $.ajax({
            url: URL_EMPLEADO,
            type: 'POST',
            data: { cedula, nombres, apellidos, telefono, direccion, cargo, salario },
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    bootstrap.Modal.getInstance(document.getElementById('modalRegistrarEmpleado'))?.hide();
                    Swal.fire('¡Registrado!', res.message, 'success');
                    recargarTabla();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
            }
        });
    });

    $(document).on('click', '.btnEditarActivo', function () {
        let btn = $(this);
        $('#edit_activo_id_empleado').val(btn.data('id'));
        $('#edit_activo_cedula').val(btn.data('cedula'));
        $('#edit_activo_nombres').val(btn.data('nombres'));
        $('#edit_activo_apellidos').val(btn.data('apellidos'));
        $('#edit_activo_telefono').val(btn.data('telefono'));
        $('#edit_activo_direccion').val(btn.data('direccion'));
        $('#edit_activo_cargo').val(btn.data('cargo'));
        $('#edit_activo_salario').val(btn.data('salario'));

        let modal = new bootstrap.Modal(document.getElementById('modalEditarActivo'));
        modal.show();
    });

    $('#btnGuardarEdicionActivo').on('click', function () {
        let idEmpleado = $('#edit_activo_id_empleado').val();
        let nombres    = $('#edit_activo_nombres').val().trim();
        let apellidos  = $('#edit_activo_apellidos').val().trim();
        let telefono   = $('#edit_activo_telefono').val().trim();
        let direccion  = $('#edit_activo_direccion').val().trim();
        let cargo      = $('#edit_activo_cargo').val();
        let salario    = $('#edit_activo_salario').val().trim();
        let cedula     = $('#edit_activo_cedula').val().trim();

        if (!nombres || !apellidos || !cargo || !salario) {
            Swal.fire('Campos incompletos', 'Por favor complete todos los campos obligatorios.', 'warning');
            return;
        }

        $.ajax({
            url: URL_EMPLEADO,
            type: 'POST',
            data: {
                id_accion: idEmpleado, nuevo_estado: 'Activo',
                nombres, apellidos, cedula, telefono, direccion, cargo, salario
            },
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarActivo'))?.hide();
                    Swal.fire('¡Actualizado!', res.message, 'success');
                    recargarTabla();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
            }
        });
    });

    $(document).on('click', '.btnCambiarEstado', function () {
        let idEmpleado = $(this).data('id');
        let nombre     = $(this).data('nombre');

        Swal.fire({
            title: '¿Inhabilitar empleado?',
            html: `El empleado <strong>${nombre}</strong> será marcado como <span class="text-danger fw-bold">Inactivo</span>.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, inhabilitar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: URL_EMPLEADO,
                    type: 'POST',
                    data: { id_accion: idEmpleado, nuevo_estado: 'Inactivo' },
                    dataType: 'json',
                    success: function (res) {
                        if (res.success) {
                            Swal.fire('¡Inhabilitado!', res.message, 'success');
                            recargarTabla();
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
                    }
                });
            }
        });
    });

    $(document).on('click', '.btnEditarInactivo', function () {
        let btn = $(this);
        $('#edit_inactivo_id_empleado').val(btn.data('id'));
        $('#edit_inactivo_nombre_completo').val(btn.data('nombre'));
        $('#edit_inactivo_status').val('Inactivo');

        let modal = new bootstrap.Modal(document.getElementById('modalEditarInactivo'));
        modal.show();
    });

    $('#btnGuardarEdicionInactivo').on('click', function () {
        let idEmpleado  = $('#edit_inactivo_id_empleado').val();
        let nuevoEstado = $('#edit_inactivo_status').val();

        $.ajax({
            url: URL_EMPLEADO,
            type: 'POST',
            data: { id_accion: idEmpleado, nuevo_estado: nuevoEstado },
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarInactivo'))?.hide();
                    Swal.fire('¡Actualizado!', res.message, 'success');
                    recargarTabla();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
            }
        });
    });

    $('#modalRegistrarEmpleado').on('hidden.bs.modal', function () {
        $('#formEmpleado')[0].reset();
        $('#formEmpleado').removeClass('was-validated');
    });
});