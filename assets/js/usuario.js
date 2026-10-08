let tablaUsuarios = null;

function initTablaUsuarios() {
    if ($.fn.DataTable.isDataTable('#tablaUsuarios')) {
        $('#tablaUsuarios').DataTable().destroy();
    }

    tablaUsuarios = $('#tablaUsuarios').DataTable({
        language: {
            lengthMenu: "Mostrar _MENU_ registros",
            zeroRecords: "No se encontraron resultados",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando 0 a 0 de 0 registros",
            infoFiltered: "(filtrado de un total de _MAX_ registros)",
            search: "Buscar:",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior"
            }
        },
        pageLength: 10,
        responsive: true,
        order: [[0, 'desc']]
    });
}

function filtrarPorEstado(estado) {
    const patron = '(^|[\\s\\-])' + estado + '([\\s\\-]|$)';
    if (tablaUsuarios.column(4).length) {
        tablaUsuarios.column(4).search(patron, true, false).draw();
    } else if (tablaUsuarios.column(3).length) {
        tablaUsuarios.column(3).search(patron, true, false).draw();
    }
}

function aplicarFiltroEstado() {
    const btnEstado = document.getElementById("btnAlternarEstado");
    const vista = btnEstado ? btnEstado.getAttribute("data-vista") : "activos";
    filtrarPorEstado(vista === "inhabilitados" ? "inactivo" : "activo");
}

function recargarTabla() {
    $.ajax({
        url: window.location.href,
        type: 'GET',
        dataType: 'html',
        success: function (html) {
            const doc = $('<div></div>').html(html);
            const nuevoTbody = doc.find('#tablaUsuarios tbody');
            const tbodyActual = $('#tablaUsuarios tbody');

            if (nuevoTbody.length && tbodyActual.length) {
                tablaUsuarios.destroy();
                tbodyActual.html(nuevoTbody.html());
                initTablaUsuarios();
                aplicarFiltroEstado();
            }
        },
        error: function () {
            location.reload();
        }
    });
}

function limpiarFormularioCrear() {
    $('#formRegistrarUsuario').trigger('reset');
    $.limpiarValidaciones('#formRegistrarUsuario');
}

function cargarOpcionesEmpleados($select, usuarioActual, esEdicion, empleadoPreseleccionado) {
    $.getJSON('index.php?controller=usuario&action=listarEmpleadosAjax', function (data) {
        if (data.status !== 'success') return;

        const opciones = ['<option value="">Seleccione el empleado...</option>'];

        (data.empleados || []).forEach(function (emp) {
            const vinculado = emp.id_usuario_vinculado ? String(emp.id_usuario_vinculado) : null;
            const esActivo = emp.status_empleado === 'Activo';

            const disponible = !esEdicion
                ? (vinculado === null && esActivo)
                : (vinculado === null || vinculado === String(usuarioActual));

            if (!disponible) return;

            const sufijo = esActivo ? '' : ' (inactivo)';
            opciones.push('<option value="' + emp.id_empleado + '">' + emp.nombres + ' ' + emp.apellidos + ' — ' + emp.cedula + sufijo + '</option>');
        });

        $select.html(opciones.join(''));
        if (empleadoPreseleccionado) {
            $select.val(empleadoPreseleccionado);
        }
    }).fail(function () {});
}

// ===== Validaciones (jQuery + helpers/expresiones.js) =====
function validarFormularioUsuario($form) {
    let valido = true;

    valido = $.validarCampo($form.find('#reg_nombre_usuario, #edit_activo_nombre_usuario'), {
        patron: /^[a-zA-Z0-9_]{3,20}$/,
        mensaje: 'El nombre de usuario debe tener entre 3 y 20 caracteres (letras, números o guiones bajos).'
    }) && valido;

    valido = $.validarCampo($form.find('#reg_correo, #edit_activo_correo'), {
        patron: 'correo',
        mensaje: 'El correo electrónico no es válido (ej. nombre@dominio.com).'
    }) && valido;

    valido = $.validarCampo($form.find('#reg_id_rol, #edit_activo_id_rol'), {
        patron: /^[0-9]+$/,
        mensaje: 'Seleccione un rol de permisos.'
    }) && valido;

    return valido;
}

function validarContrasenas($form) {
    const esRegistro = $form.is('#formRegistrarUsuario');
    const $password = $form.find(esRegistro ? '#reg_contrasena' : '#edit_activo_contrasena');
    const $confirmar = $form.find(esRegistro ? '#reg_confirmar_contrasena' : '#edit_activo_confirmar_contrasena');
    const passwordEsObligatoria = esRegistro;

    let valido = true;

    valido = $.validarCampo($password, {
        requerido: passwordEsObligatoria,
        patron: /^.{6,255}$/,
        mensaje: 'La contraseña debe tener al menos 6 caracteres.'
    }) && valido;

    valido = $.validarCampo($confirmar, {
        requerido: passwordEsObligatoria,
        validar: function (valor) {
            // En edición: si la contraseña nueva está vacía no se exige confirmar
            if (!passwordEsObligatoria && ($password.val() || '') === '') return true;
            if (valor === '') return 'Confirma la nueva contraseña.';
            return valor === $password.val()
                ? true
                : 'Las contraseñas no coinciden.';
        }
    }) && valido;

    return valido;
}

$(document).ready(function () {
    initTablaUsuarios();

    $('#btnAlternarEstado').on('click', function () {
        const vistaActual = $(this).attr('data-vista');

        if (vistaActual === 'activos') {
            $(this).attr('data-vista', 'inhabilitados');
            $('#txtBotonEstado').text('Ver Activos');
            $('#iconoEstado').attr('src', 'assets/Img/Iconos/eye.svg');
            $('#tituloVista').text('Usuarios Inhabilitados');
            filtrarPorEstado('inactivo');
        } else {
            $(this).attr('data-vista', 'activos');
            $('#txtBotonEstado').text('Ver inhabilitados');
            $('#iconoEstado').attr('src', 'assets/Img/Iconos/eye-slash.svg');
            $('#tituloVista').text('Gestión de Usuarios');
            filtrarPorEstado('activo');
        }
    });
    filtrarPorEstado('activo');

    // Switches de vínculo empleado
    $('#reg_es_empleado').on('change', function () {
        $('#reg_empleado_wrap').toggleClass('d-none', !this.checked);
        if (!this.checked) $('#reg_id_empleado').val('');
    });

    $('#edit_es_empleado').on('change', function () {
        $('#edit_empleado_wrap').toggleClass('d-none', !this.checked);
        if (!this.checked) $('#edit_id_empleado').val('');
    });

    $('#modalRegistrarUsuario').on('show.bs.modal', function () {
        $('#reg_es_empleado').prop('checked', false).trigger('change');
        cargarOpcionesEmpleados($('#reg_id_empleado'), null, false, null);
    });

    // Revalida en tiempo real los campos ya marcados como inválidos
    $('#modalRegistrarUsuario, #modalEditarActivo').on('input change', 'input, select', function () {
        const $campo = $(this);
        if (!$campo.hasClass('is-invalid')) return;

        const $form = $campo.closest('form');
        if (/contrasena|confirmar/.test($campo.attr('id') || '')) {
            validarContrasenas($form);
        } else {
            validarFormularioUsuario($form);
        }
    });

    // REGISTRAR USUARIO
    $('#formRegistrarUsuario').on('submit', function (e) {
        e.preventDefault();

        $.limpiarValidaciones('#formRegistrarUsuario');

        let valido = true;
        valido = validarFormularioUsuario($('#formRegistrarUsuario')) && valido;
        valido = validarContrasenas($('#formRegistrarUsuario')) && valido;

        if ($('#reg_es_empleado').prop('checked') && !$('#reg_id_empleado').val()) {
            Swal.fire({
                icon: 'warning',
                title: 'Empleado requerido',
                text: 'Si el usuario es empleado, seleccione el empleado asociado.'
            });
            return;
        }

        if (!valido) {
            Swal.fire('Datos inválidos', 'Corrige los campos marcados en rojo.', 'warning');
            return;
        }

        $.ajax({
            url: 'index.php?controller=usuario&action=guardar',
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (data) {
                if (data.status === 'success') {
                    $('#modalRegistrarUsuario').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: '¡Registrado!',
                        text: data.message,
                        confirmButtonColor: '#1e5631'
                    }).then(() => recargarTabla());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' });
            }
        });
    });

    // EDITAR USUARIO ACTIVO
    $(document).on('click', '.btnEditarActivo', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const rol = $(this).data('rol');
        const correo = $(this).data('correo');

        $('#edit_activo_id_usuario').val(id);
        $('#edit_activo_nombre_usuario').val(nombre);
        $('#edit_activo_correo').val(correo);
        $('#edit_activo_id_rol').val(rol);
        $('#edit_activo_contrasena').val('');
        $('#edit_activo_confirmar_contrasena').val('');
        $.limpiarValidaciones('#formEditarActivo');

        const empleadoId = $(this).data('empleado') ? String($(this).data('empleado')) : null;
        const swEdit = document.getElementById('edit_es_empleado');
        if (swEdit) {
            swEdit.checked = !!empleadoId;
            swEdit.dispatchEvent(new Event('change'));
        }
        cargarOpcionesEmpleados($('#edit_id_empleado'), id, true, empleadoId);

        $('#modalEditarActivo').modal('show');
    });

    $('#formEditarActivo').on('submit', function (e) {
        e.preventDefault();

        $.limpiarValidaciones('#formEditarActivo');

        let valido = true;
        valido = validarFormularioUsuario($('#formEditarActivo')) && valido;
        valido = validarContrasenas($('#formEditarActivo')) && valido;

        if ($('#edit_es_empleado').prop('checked') && !$('#edit_id_empleado').val()) {
            Swal.fire({
                icon: 'warning',
                title: 'Empleado requerido',
                text: 'Si el usuario es empleado, seleccione el empleado asociado.'
            });
            return;
        }

        if (!valido) {
            Swal.fire('Datos inválidos', 'Corrige los campos marcados en rojo.', 'warning');
            return;
        }

        $.ajax({
            url: 'index.php?controller=usuario&action=editar',
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (data) {
                if (data.status === 'success') {
                    $('#modalEditarActivo').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: '¡Actualizado!',
                        text: data.message,
                        confirmButtonColor: '#1e5631'
                    }).then(() => recargarTabla());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' });
            }
        });
    });

    // EDITAR/REACTIVAR USUARIO INACTIVO
    $(document).on('click', '.btnEditarInactivo', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');

        $('#edit_inactivo_id_usuario').val(id);
        $('#edit_inactivo_nombre_usuario').val(nombre);
        $('#edit_inactivo_status').val('inactivo').change();

        $('#modalEditarInactivo').modal('show');
    });

    $('#btnGuardarEdicionInactivo').on('click', function () {
        const id = $('#edit_inactivo_id_usuario').val();
        const nuevoEstado = $('#edit_inactivo_status').val();

        if (nuevoEstado === 'inactivo') {
            $('#modalEditarInactivo').modal('hide');
            return;
        }

        $.ajax({
            url: 'index.php?controller=usuario&action=cambiarEstado',
            type: 'POST',
            data: { id_usuario: id, status_usuario: nuevoEstado },
            dataType: 'json',
            success: function (data) {
                if (data.status === 'success') {
                    $('#modalEditarInactivo').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: '¡Reactivado!',
                        text: data.message,
                        confirmButtonColor: '#1e5631'
                    }).then(() => recargarTabla());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' });
            }
        });
    });

    // INHABILITAR USUARIO
    $(document).on('click', '.btnCambiarEstado', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');

        Swal.fire({
            title: '¿Inactivar usuario?',
            text: `El usuario "${nombre}" no podrá iniciar sesión hasta ser reactivado.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Sí, inactivar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'index.php?controller=usuario&action=cambiarEstado',
                    type: 'POST',
                    data: { id_usuario: id, status_usuario: 'inactivo' },
                    dataType: 'json',
                    success: function (data) {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Inactivado!',
                                text: data.message,
                                confirmButtonColor: '#1e5631'
                            }).then(() => recargarTabla());
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                        }
                    },
                    error: function () {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' });
                    }
                });
            }
        });
    });

    limpiarFormularioCrear();
});