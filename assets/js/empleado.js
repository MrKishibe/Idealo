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
            $('#iconoEstado').attr('src', 'assets/Img/Iconos/eye-slash.svg');
            $('#tituloVista').text('Gestión de Empleados');
        } else {
            tablaEmpleados.column(5).search('^Inactivo$', true, false).draw();
            $('#txtBotonEstado').text('Ver activos');
            $('#iconoEstado').attr('src', 'assets/Img/Iconos/eye.svg');
            $('#tituloVista').text('Empleados Inhabilitados');
        }
    }

    filtrarPorEstado();

    function cargarOpcionesUsuarios($select, empleadoActual, esEdicion, usuarioPreseleccionado) {
        $.getJSON(URL_EMPLEADO + '&ajax=usuarios', function (res) {
            if (!res.success) return;

            const opciones = ['<option value="">Seleccione el usuario...</option>'];

            (res.usuarios || []).forEach(function (u) {
                const vinculado = u.id_empleado_vinculado ? String(u.id_empleado_vinculado) : null;
                const esActivo = u.status_usuario === 'activo';

                const disponible = !esEdicion
                    ? (vinculado === null && esActivo)
                    : (vinculado === null || vinculado === String(empleadoActual));

                if (!disponible) return;

                const sufijo = esActivo ? '' : ' (inactivo)';
                opciones.push('<option value="' + u.id_usuario + '">' + u.nombre_usuario + ' — ' + (u.correo || 'sin correo') + sufijo + '</option>');
            });

            $select.html(opciones.join(''));
            if (usuarioPreseleccionado) {
                $select.val(usuarioPreseleccionado);
            }
        });
    }

    $('#reg_es_usuario').on('change', function () {
        $('#reg_usuario_wrap').toggleClass('d-none', !this.checked);
        if (!this.checked) {
            $('#reg_id_usuario').val('');
        }
    });

    $('#edit_es_usuario').on('change', function () {
        $('#edit_usuario_wrap').toggleClass('d-none', !this.checked);
        if (!this.checked) {
            $('#edit_id_usuario').val('');
        }
    });

    $('#modalRegistrarEmpleado').on('show.bs.modal', function () {
        $('#reg_es_usuario').prop('checked', false).trigger('change');
        cargarOpcionesUsuarios($('#reg_id_usuario'), null, false, null);
    });

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
                                ' data-salario="' + (emp.salario || '') + '"' +
                                 ' data-usuario="' + (emp.id_usuario_vinculado || '') + '">' +
                                '<img src="assets/Img/Iconos/pencil-square.svg" class="icono-svg icono-azul" alt="Editar">' +
                            '</button>' +
                            '<button class="btn btn-sm btn-outline-danger btnCambiarEstado"' +
                                ' data-id="' + emp.id_empleado + '"' +
                                ' data-nombre="' + nombre + '">' +
                                '<img src="assets/Img/Iconos/trash.svg" class="icono-svg icono-rojo" alt="Inhabilitar">' +
                            '</button>' +
                        '</div>';
                } else {
                    acciones =
                        '<div class="text-center d-flex justify-content-center gap-1">' +
                            '<button class="btn btn-sm btn-outline-warning btnEditarInactivo"' +
                                ' data-id="' + emp.id_empleado + '"' +
                                ' data-nombre="' + nombre + '">' +
                                '<img src="assets/Img/Iconos/pencil-square.svg" class="icono-svg icono-amarillo" alt="Editar">' +
                            '</button>' +
                        '</div>';
                }

                tablaEmpleados.row.add([
                    '<span class="fw-bold">' + emp.cedula + '</span>',
                    '<div class="fw-bold text-dark">' + nombre + '</div>' +
                        '<small class="text-muted"><img src="assets/Img/Iconos/geo-alt.svg" class="icono-svg icono-gris me-1" alt="Dirección"> ' + (emp.direccion || 'Sin dirección') + '</small>',
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

        let esUsuario = $('#reg_es_usuario').prop('checked');
        let idUsuario = $('#reg_id_usuario').val();

        if (!cedula || !nombres || !apellidos || !cargo || !salario) {
            Swal.fire('Campos incompletos', 'Por favor complete todos los campos obligatorios.', 'warning');
            return;
        }

        if (esUsuario && !idUsuario) {
            Swal.fire('Usuario requerido', 'Si el empleado tiene usuario, seleccione el usuario asociado.', 'warning');
            return;
        }

        $.ajax({
            url: URL_EMPLEADO,
            type: 'POST',
            data: { cedula, nombres, apellidos, telefono, direccion, cargo, salario, es_usuario: esUsuario ? 1 : '', id_usuario: idUsuario || '' },
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

        const empleadoId = btn.data('id');
        const usuarioId = btn.data('usuario') ? String(btn.data('usuario')) : null;
        $('#edit_es_usuario').prop('checked', !!usuarioId).trigger('change');
        cargarOpcionesUsuarios($('#edit_id_usuario'), empleadoId, true, usuarioId);

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

        let esUsuario = $('#edit_es_usuario').prop('checked');
        let idUsuario = $('#edit_id_usuario').val();

        if (!nombres || !apellidos || !cargo || !salario) {
            Swal.fire('Campos incompletos', 'Por favor complete todos los campos obligatorios.', 'warning');
            return;
        }

        if (esUsuario && !idUsuario) {
            Swal.fire('Usuario requerido', 'Si el empleado tiene usuario, seleccione el usuario asociado.', 'warning');
            return;
        }

        $.ajax({
            url: URL_EMPLEADO,
            type: 'POST',
            data: {
                id_accion: idEmpleado, nuevo_estado: 'Activo',
                nombres, apellidos, cedula, telefono, direccion, cargo, salario,
                es_usuario: esUsuario ? 1 : '', id_usuario: idUsuario || ''
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
        $('#reg_usuario_wrap').addClass('d-none');
        $('#reg_id_usuario').html('<option value="">Seleccione el usuario...</option>');
    });
});