import $ from 'jquery';
import { lenguajeDataTableEs } from './helpers/datatable-espanol.js';
import { crearMarkupSkeletonDataTable } from './helpers/datatable-skeleton.js';
import { successAlert, errorAlert, confirmActionAlert } from './helpers/alerts.js';
import { validarCampo } from './helpers/expresiones.js';
import { limpiarValidaciones } from './helpers/validaciones.js';

$(function () {
    const URL = 'index.php?controller=rol';

    let tabla = null;
    let verInactivos = false;

    const REGLAS = {
        tipo_de_usuario: {
            patron: /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]{3,50}$/u,
            mensaje: 'El nombre del rol debe tener entre 3 y 50 caracteres (solo letras y espacios).',
            mensajeVacio: 'El nombre del rol es obligatorio.'
        }
    };

    const FORMS = {
        registro: {
            form: '#formRegistrarRol', prefijo: 'reg_', accion: 'guardar', modal: 'modalRegistrarRol',
            titulo: '¡Registrado!', reiniciar: true
        },
        edicion: {
            form: '#formEditarActivo', prefijo: 'edit_activo_', accion: 'editar', modal: 'modalEditarActivo',
            titulo: '¡Actualizado!', reiniciar: false
        }
    };

    const escapar = (texto) => $('<div>').text(texto ?? '').html();
    const activo = (rol) => String(rol.status_roles || '').trim().toLowerCase() === 'activo';
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
        return `<button type="button" class="btn btn-sm btn-outline-warning btnEditarInactivo" data-id="${escapar(id)}" title="Reactivar Rol">` +
            '<i class="bi bi-arrow-counterclockwise"></i></button>';
    }

    function badgeRol(rol) {
        const nombre = escapar(rol ? rol.charAt(0).toUpperCase() + rol.slice(1) : 'Sin rol');
        return '<span class="badge bg-light text-primary border px-2 py-1" style="font-weight: 600; font-size: 12px; border-radius: 6px;">' +
            '<i class="bi bi-shield-lock me-1"></i>' + nombre + '</span>';
    }

    function badgeEstado(valor) {
        return activo({ status_roles: valor })
            ? '<span class="badge bg-success">Activo</span>'
            : '<span class="badge bg-danger">Inactivo</span>';
    }

    function acciones(rol) {
        const botones = activo(rol)
            ? boton('btn-outline-primary btnEditarActivo', 'pencil-square', 'Editar Rol', rol.id_rol) +
              boton('btn-outline-danger btnCambiarEstado', 'trash3-fill', 'Inactivar Rol', rol.id_rol)
            : reactivar(rol.id_rol);

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

    function validarRol(config) {
        let valido = true;

        $.each(REGLAS, function (campo, regla) {
            if (!validarCampo('#' + config.prefijo + campo, regla)) valido = false;
        });

        return valido;
    }

    async function guardarRol(config) {
        const $form = $(config.form);
        limpiarValidaciones($form);

        if (!validarRol(config)) {
            return errorAlert('Datos inválidos', 'Revise los campos marcados en rojo.');
        }

        const respuesta = await peticion(URL + '&action=' + config.accion, $form.serialize(), config.titulo);
        if (!respuesta) return;

        $('#' + config.modal).modal('hide');
        recargar(config.reiniciar);
    }

    async function cambiarEstado(id, estado, modal, titulo) {
        const respuesta = await peticion(URL + '&action=cambiarEstado', { id_rol: id, status_roles: estado }, titulo);
        if (!respuesta) return;

        if (modal) $('#' + modal).modal('hide');
        recargar();
    }

    // Validación en vivo
    $('#modalRegistrarRol, #modalEditarActivo').on('input change', 'input, select', function () {
        const $campo = $(this);
        if (!$campo.hasClass('is-invalid') && !$campo.hasClass('is-valid')) return;

        const config = $campo.closest('#modalEditarActivo').length ? FORMS.edicion : FORMS.registro;
        validarCampo($campo, REGLAS.tipo_de_usuario);
    });

    // Activos / inhabilitados
    $('#btnAlternarEstado').on('click', function () {
        verInactivos = !verInactivos;
        $(this).toggleClass('btn-outline-secondary', !verInactivos).toggleClass('btn-secondary', verInactivos);
        $('#txtBotonEstado').text(verInactivos ? 'Ver Activos' : 'Ver inhabilitados');
        $('#iconoEstado')
            .toggleClass('bi-eye-slash-fill', !verInactivos)
            .toggleClass('bi-eye-fill', verInactivos);
        $('#tituloVista').text(verInactivos ? 'Roles Inhabilitados' : 'Gestión de Roles');
        recargar(true);
    });

    // Registrar
    $('#modalRegistrarRol').on('show.bs.modal', function () {
        const $form = $('#formRegistrarRol');
        $form[0].reset();
        limpiarValidaciones($form);
    });

    $('#formRegistrarRol').on('submit', function (e) {
        e.preventDefault();
        guardarRol(FORMS.registro);
    });

    // Editar rol activo
    $('#tablaRoles tbody').on('click', '.btnEditarActivo', function () {
        const rol = deFila($(this));
        if (!rol) return;

        $('#edit_activo_id_rol').val(rol.id_rol);
        $('#edit_activo_tipo_de_usuario').val(rol.tipo_de_usuario);
        limpiarValidaciones('#formEditarActivo');

        $('#modalEditarActivo').modal('show');
    });

    $('#formEditarActivo').on('submit', function (e) {
        e.preventDefault();
        guardarRol(FORMS.edicion);
    });

    // Inactivar
    $('#tablaRoles tbody').on('click', '.btnCambiarEstado', async function () {
        const rol = deFila($(this));
        if (!rol) return;

        const confirmacion = await confirmActionAlert(
            '¿Inactivar rol?',
            'El rol "' + rol.tipo_de_usuario + '" dejará de estar disponible al registrar usuarios.'
        );

        if (confirmacion.isConfirmed) await cambiarEstado(rol.id_rol, 'inactivo', null, '¡Inactivado!');
    });

    // Editar / reactivar inactivo
    $('#tablaRoles tbody').on('click', '.btnEditarInactivo', function () {
        const rol = deFila($(this));
        if (!rol) return;

        $('#edit_inactivo_id_rol').val(rol.id_rol);
        $('#edit_inactivo_tipo_de_usuario').val(rol.tipo_de_usuario);
        $('#edit_inactivo_status').val('inactivo');
        $('#modalEditarInactivo').modal('show');
    });

    $('#btnGuardarEdicionInactivo').on('click', async function () {
        const id = $('#edit_inactivo_id_rol').val();
        const estado = $('#edit_inactivo_status').val();

        if (estado === 'inactivo') {
            $('#modalEditarInactivo').modal('hide');
            return;
        }

        const confirmacion = await confirmActionAlert('¿Reactivar rol?', 'El rol volverá a estar disponible en el sistema.');
        if (confirmacion.isConfirmed) await cambiarEstado(id, estado, 'modalEditarInactivo', '¡Reactivado!');
    });

    $('#modalRegistrarRol').on('hidden.bs.modal', function () {
        $('#formRegistrarRol')[0].reset();
        limpiarValidaciones('#formRegistrarRol');
    });

    $('#modalEditarActivo, #modalEditarInactivo').on('hidden.bs.modal', function () {
        limpiarValidaciones($(this).find('form'));
    });

    // Tabla
    tabla = $('#tablaRoles').DataTable({
        ajax: {
            url: URL + '&action=listarRolesAjax',
            dataSrc: (json) => $.grep(json && json.roles ? json.roles : [], (rol) => verInactivos ? !activo(rol) : activo(rol))
        },
        columns: [
            { data: 'id_rol', render: (id, tipo) => tipo === 'display' ? `<code>#${escapar(id)}</code>` : id },
            { data: 'tipo_de_usuario', render: badgeRol },
            { data: 'status_roles', render: badgeEstado },
            { data: null, orderable: false, render: acciones }
        ],
        columnDefs: [{ targets: 0, type: 'num' }],
        processing: true,
        language: {
            ...lenguajeDataTableEs,
            processing: crearMarkupSkeletonDataTable($('#tablaRoles thead th').length)
        },
        pageLength: 10,
        dom: '<"row mb-3"<"col-md-6"l><"col-md-6 text-end"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
        order: [[0, 'desc']]
    });
});
