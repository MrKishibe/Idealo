import $ from 'jquery';
import { lenguajeDataTableEs } from './helpers/datatable-espanol.js';
import { successAlert, errorAlert, confirmActionAlert } from './helpers/alerts.js';
import { obtenerPatron, validarCampo } from './helpers/expresiones.js';
import { limpiarValidaciones } from './helpers/validaciones.js';

$(function () {
    const URL = 'index.php?controller=usuario';
    let tabla = null;
    let verInactivos = false;

    const REGLAS = {
        nombre_usuario: {
            patron: obtenerPatron('usuario'),
            mensaje: 'El nombre de usuario debe tener entre 3 y 20 caracteres (letras, números o guiones bajos).',
            mensajeVacio: 'El nombre de usuario es obligatorio.'
        },
        correo: {
            patron: obtenerPatron('correo'),
            mensaje: 'El correo electrónico no es válido (ej. nombre@dominio.com).',
            mensajeVacio: 'El correo electrónico es obligatorio.'
        },
        id_rol: { mensajeVacio: 'Seleccione un rol de permisos.' }
    };

    const FORMS = {
        registro: {
            form: '#formRegistrarUsuario', prefijo: 'reg_', accion: 'guardar', modal: 'modalRegistrarUsuario',
            titulo: '¡Registrado!', check: '#reg_es_empleado', select: '#reg_id_empleado', esRegistro: true, reiniciar: true
        },
        edicion: {
            form: '#formEditarActivo', prefijo: 'edit_activo_', accion: 'editar', modal: 'modalEditarActivo',
            titulo: '¡Actualizado!', check: '#edit_es_empleado', select: '#edit_id_empleado', esRegistro: false, reiniciar: false
        }
    };

    const escapar = (texto) => $('<div>').text(texto ?? '').html();
    const activo = (usuario) => String(usuario.status_usuario || '').trim().toLowerCase() === 'activo';
    const recargar = (reiniciar) => tabla.ajax.reload(null, Boolean(reiniciar));

    function deFila($boton) {
        const fila = tabla.row($boton.closest('tr'));
        return fila && fila.data() ? fila.data() : null;
    }

    function boton(clases, icono, alt, id) {
        return `<button type="button" class="btn btn-sm ${clases}" data-id="${escapar(id)}" title="${alt}">` +
            `<i class="bi bi-${icono}"></i></button>`;
    }

    function reactivar(id) {
        return `<button type="button" class="btn btn-sm btn-outline-warning btnEditarInactivo" data-id="${escapar(id)}" title="Reactivar Usuario">` +
            '<i class="bi bi-arrow-counterclockwise"></i></button>';
    }

    function badgeRol(rol) {
        const nombre = escapar(rol ? rol.charAt(0).toUpperCase() + rol.slice(1) : 'Sin rol');
        return '<span class="badge bg-light text-primary border px-2 py-1" style="font-weight: 600; font-size: 12px; border-radius: 6px;">' +
            '<i class="bi bi-shield-lock me-1"></i>' + nombre + '</span>';
    }

    function badgeEstado(valor) {
        return activo({ status_usuario: valor })
            ? '<span class="badge bg-success">Activo</span>'
            : '<span class="badge bg-danger">Inactivo</span>';
    }

    function acciones(usuario) {
        const botones = activo(usuario)
            ? boton('btn-outline-primary btnEditarActivo', 'pencil-square', 'Editar Usuario', usuario.id_usuario) +
              boton('btn-outline-danger btnCambiarEstado', 'trash3-fill', 'Inactivar Usuario', usuario.id_usuario)
            : reactivar(usuario.id_usuario);

        return '<div class="text-center d-flex justify-content-center gap-1">' + botones + '</div>';
    }

    async function peticion(url, datos, titulo) {
        try {
            const respuesta = await $.ajax({ url, type: 'POST', dataType: 'json', data: datos });

            if (respuesta.status !== 'success') {
                await errorAlert('Error', respuesta.message || 'No se pudo completar la operación.');
                return null;
            }

            await successAlert(titulo, respuesta.message);
            return respuesta;
        } catch (error) {
            await errorAlert('Error', 'No se pudo conectar con el servidor.');
            return null;
        }
    }

    function cargarEmpleados($select, usuarioActual, esEdicion, preseleccionado) {
        $select.html('<option value="">Cargando...</option>');

        $.ajax({ url: URL + '&action=listarEmpleadosAjax', dataType: 'json' })
            .done(function (respuesta) {
                if (!respuesta || respuesta.status !== 'success') {
                    return $select.html('<option value="">No se pudieron cargar los empleados</option>');
                }

                const opciones = $.map(respuesta.empleados || [], function (empleado) {
                    const vinculado = empleado.id_usuario_vinculado ? String(empleado.id_usuario_vinculado) : null;
                    const esActivo = String(empleado.status_empleado || '').trim().toLowerCase() === 'activo';
                    const noDisponible = esEdicion ? (vinculado && vinculado !== String(usuarioActual)) : (vinculado || !esActivo);
                    if (noDisponible) return null;

                    return $('<option>', {
                        value: empleado.id_empleado,
                        text: [empleado.nombres, empleado.apellidos].filter(Boolean).join(' ') +
                            ' — ' + (empleado.cedula || 'Sin cédula') + (esActivo ? '' : ' (inactivo)')
                    })[0];
                });

                $select.empty()
                    .append($('<option>', { value: '', text: 'Seleccione el empleado...' }))
                    .append(opciones);

                if (preseleccionado) $select.val(String(preseleccionado));
            })
            .fail(() => $select.html('<option value="">No se pudieron cargar los empleados</option>'));
    }

    function validarUsuario(config) {
        let valido = true;

        $.each(REGLAS, function (campo, regla) {
            if (!validarCampo('#' + config.prefijo + campo, regla)) valido = false;
        });

        const $password = $('#' + config.prefijo + 'contrasena');
        const $confirmar = $('#' + config.prefijo + 'confirmar_contrasena');
        const password = String($password.val() || '');

        if (!validarCampo($password, {
            requerido: config.esRegistro,
            patron: /^.{6,255}$/,
            mensaje: 'La contraseña debe tener al menos 6 caracteres.',
            mensajeVacio: 'La contraseña es obligatoria.'
        })) valido = false;

        if (!validarCampo($confirmar, {
            requerido: config.esRegistro || password !== '',
            mensajeVacio: 'Confirme la contraseña.',
            mensaje: 'Las contraseñas no coinciden.',
            validar: (valor) => valor === password || 'Las contraseñas no coinciden.'
        })) valido = false;

        return valido;
    }

    function validarCampoUsuario($campo, config) {
        const campo = String($campo.attr('id') || '').replace(/^(?:edit_activo_|reg_|edit_)/, '');

        if (REGLAS[campo]) return validarCampo($campo, REGLAS[campo]);

        if (campo === 'id_empleado') {
            return validarCampo($campo, {
                requerido: $(config.check).prop('checked'),
                mensajeVacio: 'Seleccione el empleado asociado.'
            });
        }

        return /contrasena|confirmar_contrasena/.test(campo) ? validarUsuario(config) : true;
    }

    async function guardarUsuario(config) {
        const $form = $(config.form);
        limpiarValidaciones($form);

        if (!validarUsuario(config)) {
            return errorAlert('Datos inválidos', 'Revise los campos marcados en rojo.');
        }

        const $empleado = $(config.select);

        if ($(config.check).prop('checked') && !$empleado.val()) {
            validarCampo($empleado, { mensajeVacio: 'Seleccione el empleado asociado.' });
            return errorAlert('Empleado requerido', 'Seleccione el empleado asociado al usuario.');
        }

        const respuesta = await peticion(URL + '&action=' + config.accion, $form.serialize(), config.titulo);
        if (!respuesta) return;

        $('#' + config.modal).modal('hide');
        recargar(config.reiniciar);
    }

    async function cambiarEstado(id, estado, modal, titulo) {
        const respuesta = await peticion(URL + '&action=cambiarEstado', { id_usuario: id, status_usuario: estado }, titulo);
        if (!respuesta) return;

        if (modal) $('#' + modal).modal('hide');
        recargar();
    }

    // Validación en vivo
    $('#modalRegistrarUsuario, #modalEditarActivo').on('input change', 'input, select', function () {
        const $campo = $(this);
        if (!$campo.hasClass('is-invalid') && !$campo.hasClass('is-valid')) return;

        const config = $campo.closest('#modalEditarActivo').length ? FORMS.edicion : FORMS.registro;
        validarCampoUsuario($campo, config);
    });

    // Activos / inhabilitados
    $('#btnAlternarEstado').on('click', function () {
        verInactivos = !verInactivos;
        $(this).toggleClass('btn-outline-secondary', !verInactivos).toggleClass('btn-secondary', verInactivos);
        $('#txtBotonEstado').text(verInactivos ? 'Ver Activos' : 'Ver inhabilitados');
        $('#iconoEstado')
            .toggleClass('bi-eye-slash-fill', !verInactivos)
            .toggleClass('bi-eye-fill', verInactivos);
        $('#tituloVista').text(verInactivos ? 'Usuarios Inhabilitados' : 'Gestión de Usuarios');
        recargar(true);
    });

    // Empleado asociado
    $('#reg_es_empleado, #edit_es_empleado').on('change', function () {
        const config = this.id.startsWith('edit_') ? FORMS.edicion : FORMS.registro;
        $(config.select).closest('[id$="_empleado_wrap"]').toggleClass('d-none', !this.checked);

        if (!this.checked) $(config.select).val('').removeClass('is-valid is-invalid');
    });

    // Registrar
    $('#modalRegistrarUsuario').on('show.bs.modal', function () {
        const $form = $('#formRegistrarUsuario');
        $form[0].reset();
        limpiarValidaciones($form);
        $('#reg_es_empleado').prop('checked', false).trigger('change');
        cargarEmpleados($('#reg_id_empleado'), null, false, null);
    });

    $('#formRegistrarUsuario').on('submit', function (e) {
        e.preventDefault();
        guardarUsuario(FORMS.registro);
    });

    // Editar usuario activo
    $('#tablaUsuarios tbody').on('click', '.btnEditarActivo', function () {
        const usuario = deFila($(this));
        if (!usuario) return;

        $('#edit_activo_id_usuario').val(usuario.id_usuario);
        $('#edit_activo_nombre_usuario').val(usuario.nombre_usuario);
        $('#edit_activo_correo').val(usuario.correo);
        $('#edit_activo_id_rol').val(usuario.id_rol);
        $('#edit_activo_contrasena, #edit_activo_confirmar_contrasena').val('');
        limpiarValidaciones('#formEditarActivo');

        const empleado = usuario.id_empleado_vinculado ? String(usuario.id_empleado_vinculado) : null;
        $('#edit_es_empleado').prop('checked', Boolean(empleado)).trigger('change');
        cargarEmpleados($('#edit_id_empleado'), usuario.id_usuario, true, empleado);
        $('#modalEditarActivo').modal('show');
    });

    $('#formEditarActivo').on('submit', function (e) {
        e.preventDefault();
        guardarUsuario(FORMS.edicion);
    });

    // Inactivar
    $('#tablaUsuarios tbody').on('click', '.btnCambiarEstado', async function () {
        const usuario = deFila($(this));
        if (!usuario) return;

        const confirmacion = await confirmActionAlert(
            '¿Inactivar usuario?',
            'El usuario "' + usuario.nombre_usuario + '" no podrá iniciar sesión hasta ser reactivado.'
        );

        if (confirmacion.isConfirmed) await cambiarEstado(usuario.id_usuario, 'inactivo', null, '¡Inactivado!');
    });

    // Editar / reactivar inactivo
    $('#tablaUsuarios tbody').on('click', '.btnEditarInactivo', function () {
        const usuario = deFila($(this));
        if (!usuario) return;

        $('#edit_inactivo_id_usuario').val(usuario.id_usuario);
        $('#edit_inactivo_nombre_usuario').val(usuario.nombre_usuario);
        $('#edit_inactivo_status').val('inactivo');
        $('#modalEditarInactivo').modal('show');
    });

    $('#btnGuardarEdicionInactivo').on('click', async function () {
        const id = $('#edit_inactivo_id_usuario').val();
        const estado = $('#edit_inactivo_status').val();

        if (estado === 'inactivo') {
            $('#modalEditarInactivo').modal('hide');
            return;
        }

        const confirmacion = await confirmActionAlert('¿Reactivar usuario?', 'El usuario volverá a estar activo en el sistema.');
        if (confirmacion.isConfirmed) await cambiarEstado(id, estado, 'modalEditarInactivo', '¡Reactivado!');
    });

    // Tabla
    tabla = $('#tablaUsuarios').DataTable({
        ajax: {
            url: URL + '&action=listarUsuariosAjax',
            dataSrc: (json) => $.grep(json && json.usuarios ? json.usuarios : [], (usuario) => verInactivos ? !activo(usuario) : activo(usuario))
        },
        columns: [
            { data: 'id_usuario', render: (id, tipo) => tipo === 'display' ? `<code>#${escapar(id)}</code>` : id },
            { data: 'nombre_usuario', className: 'fw-bold text-dark', render: escapar },
            { data: 'correo', className: 'text-muted small', render: escapar },
            { data: 'tipo_de_usuario', render: badgeRol },
            { data: 'status_usuario', render: badgeEstado },
            { data: null, orderable: false, render: acciones }
        ],
        columnDefs: [{ targets: 0, type: 'num' }],
        language: lenguajeDataTableEs,
        pageLength: 10,
        dom: '<"row mb-3"<"col-md-6"l><"col-md-6 text-end"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
        order: [[0, 'desc']]
    });
});
