import $ from 'jquery';
import { lenguajeDataTableEs } from './helpers/datatable-espanol.js';
import { crearMarkupSkeletonDataTable } from './helpers/datatable-skeleton.js';
import { confirmActionAlert, errorAlert, successAlert } from './helpers/alerts.js';

$(function () {
    const URL = 'index.php?controller=permisos';
    const URL_ROLES = 'index.php?controller=rol&action=listarRolesAjax';
    let verInactivos = false;
    let permisos = [];

    const escapar = (texto) => $('<div>').text(texto ?? '').html();
    const esActivo = (permiso) => String(permiso.status_permiso || '').trim().toLowerCase() === 'activo';
    const etiquetasModulo = {
        usuarios: 'Usuarios',
        clientes: 'Clientes',
        empleados: 'Empleados',
        pedidos: 'Pedidos',
        tipos_pedido: 'Tipos de pedido',
        inventario: 'Inventario',
        servicios: 'Servicios',
        produccion: 'Producción',
        finanzas: 'Finanzas'
    };
    const etiquetasAccion = {
        consultar: 'Consultar',
        crear: 'Crear',
        editar: 'Editar',
        eliminar: 'Eliminar',
        gestionar: 'Gestionar todo'
    };
    const alcance = (permiso) => {
        const modulo = etiquetasModulo[permiso.modulo_permiso];
        const accion = etiquetasAccion[permiso.accion_permiso];
        return modulo && accion ? `${modulo} — ${accion}` : 'Sin módulo u operación';
    };

    const tabla = $('#tablaPermisos').DataTable({
        ajax: {
            url: URL + '&action=listarPermisosAjax',
            error: function (xhr) {
                const respuesta = xhr.responseJSON || {};
                errorAlert('Error', respuesta.message || 'No se pudieron cargar los permisos.');
            },
            dataSrc: function (json) {
                if (!json || json.status !== 'success') {
                    errorAlert('Error', json && json.message ? json.message : 'No se pudieron cargar los permisos.');
                    return [];
                }
                permisos = json.permisos || [];
                return permisos.filter((permiso) => verInactivos ? !esActivo(permiso) : esActivo(permiso));
            }
        },
        columns: [
            { data: 'id_permiso', render: (id, tipo) => tipo === 'display' ? `<code>#${escapar(id)}</code>` : id },
            { data: 'nombre_permiso', render: (nombre) => escapar(nombre) },
            {
                data: null,
                render: (data, tipo, permiso) => {
                    const modulo = etiquetasModulo[permiso.modulo_permiso];
                    const accion = etiquetasAccion[permiso.accion_permiso];
                    return modulo && accion
                        ? escapar(`${modulo} — ${accion}`)
                        : '<span class="text-muted">Sin módulo u operación</span>';
                }
            },
            {
                data: 'status_permiso',
                render: (estado) => String(estado || '').toLowerCase() === 'activo'
                    ? '<span class="badge bg-success">Activo</span>'
                    : '<span class="badge bg-danger">Inactivo</span>'
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function (data, tipo, permiso) {
                    if (!esActivo(permiso)) {
                        return `<button type="button" class="btn btn-sm btn-outline-success btn-activar-permiso" data-id="${escapar(permiso.id_permiso)}" title="Habilitar permiso" aria-label="Habilitar ${escapar(permiso.nombre_permiso)}"><i class="bi bi-arrow-counterclockwise"></i></button>`;
                    }
                    return `<button type="button" class="btn btn-sm btn-outline-danger btn-inactivar-permiso" data-id="${escapar(permiso.id_permiso)}" title="Inactivar permiso" aria-label="Inactivar ${escapar(permiso.nombre_permiso)}"><i class="bi bi-trash3-fill"></i></button>`;
                }
            }
        ],
        columnDefs: [{ targets: 0, type: 'num' }],
        processing: true,
        language: {
            ...lenguajeDataTableEs,
            processing: crearMarkupSkeletonDataTable(5)
        },
        pageLength: 10,
        responsive: true,
        order: [[0, 'asc']],
        dom: '<"row align-items-center mb-3"<"col-md-6"l><"col-md-6 text-end"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>'
    });

    async function cargarRolesYPermisos() {
        const [respuestaRoles, respuestaPermisos] = await Promise.all([
            $.ajax({ url: URL_ROLES, type: 'GET', dataType: 'json' }),
            $.ajax({ url: URL + '&action=listarPermisosAjax', type: 'GET', dataType: 'json' })
        ]);

        if (respuestaRoles.status !== 'success') {
            throw new Error(respuestaRoles.message || 'No se pudieron cargar los roles.');
        }
        if (respuestaPermisos.status !== 'success') {
            throw new Error(respuestaPermisos.message || 'No se pudieron cargar los permisos.');
        }

        const rolesActivos = (respuestaRoles.roles || []).filter(
            (rol) => String(rol.status_roles || '').trim().toLowerCase() === 'activo'
        );
        permisos = respuestaPermisos.permisos || [];

        const $select = $('#selectRolPermisos').empty()
            .append($('<option>', { value: '', text: 'Seleccione un rol' }));
        rolesActivos.forEach((rol) => {
            $select.append($('<option>', { value: rol.id_rol, text: rol.tipo_de_usuario }));
        });

        if (!rolesActivos.length) {
            throw new Error('No hay roles activos para asignar permisos.');
        }
    }

    function mostrarPermisos(identificadoresAsignados) {
        const asignados = new Set(identificadoresAsignados.map((id) => String(id)));
        const $lista = $('#listaPermisosRol').empty();

        if (!permisos.length) {
            $lista.append($('<p>', { class: 'text-muted mb-0', text: 'No hay permisos registrados.' }));
            return;
        }

        permisos.forEach((permiso) => {
            const id = String(permiso.id_permiso);
            const yaAsignado = asignados.has(id);
            const activo = esActivo(permiso);
            const $input = $('<input>', {
                type: 'checkbox',
                class: 'form-check-input',
                name: 'id_permisos[]',
                value: id,
                id: 'permiso-' + id,
                checked: yaAsignado,
                disabled: !activo && !yaAsignado
            });
            const etiquetaEstado = activo ? '' : ' (inactivo)';
            const $label = $('<label>', {
                class: 'form-check-label',
                for: 'permiso-' + id,
                text: `${permiso.nombre_permiso} — ${alcance(permiso)}${etiquetaEstado}`
            });

            $lista.append(
                $('<div>', { class: 'form-check mb-2' }).append($input, $label)
            );
        });
    }

    async function cargarAsignacionesRol(idRol) {
        const $lista = $('#listaPermisosRol').empty()
            .append($('<p>', { class: 'text-muted mb-0', text: 'Cargando permisos...' }));
        $('#btnGuardarPermisos').prop('disabled', true);
        if (!idRol) {
            $lista.empty().append($('<p>', {
                class: 'text-muted mb-0',
                text: 'Seleccione un rol para cargar los permisos.'
            }));
            return;
        }

        try {
            const respuesta = await $.ajax({
                url: URL + '&action=listarPermisosPorRolAjax',
                type: 'GET',
                dataType: 'json',
                data: { id_rol: idRol }
            });
            if (respuesta.status !== 'success') {
                throw new Error(respuesta.message || 'No se pudieron consultar las asignaciones.');
            }

            const ids = (respuesta.permisos || []).map((permiso) => permiso.id_permiso);
            mostrarPermisos(ids);
            $('#btnGuardarPermisos').prop('disabled', false);
        } catch (error) {
            $lista.empty().append($('<p>', {
                class: 'text-danger mb-0',
                text: error.message || 'No se pudieron cargar los permisos del rol.'
            }));
        }
    }

    $('#btnAlternarEstado').on('click', function () {
        cambiarVistaEstado(!verInactivos);
    });

    function cambiarVistaEstado(mostrarInactivos) {
        verInactivos = mostrarInactivos;
        const $botonEstado = $('#btnAlternarEstado');
        $botonEstado.toggleClass('btn-outline-secondary', !verInactivos)
            .toggleClass('btn-secondary', verInactivos);
        $('#txtBotonEstado').text(verInactivos ? 'Ver activos' : 'Ver inactivos');
        $('#iconoEstado')
            .toggleClass('bi-eye-slash-fill', !verInactivos)
            .toggleClass('bi-eye-fill', verInactivos);
        $('#tituloVista').text(verInactivos ? 'Permisos inactivos' : 'Permisos por Rol');
        tabla.ajax.reload();
    }

    $('#formRegistrarPermiso').on('submit', async function (event) {
        event.preventDefault();
        const $boton = $('#btnRegistrarPermiso').prop('disabled', true);

        try {
            const respuesta = await $.ajax({
                url: URL + '&action=registrarPermiso',
                type: 'POST',
                dataType: 'json',
                data: {
                    nombre_permiso: $('#nombrePermiso').val().trim(),
                    modulo_permiso: $('#moduloPermiso').val(),
                    accion_permiso: $('#accionPermiso').val()
                }
            });
            if (respuesta.status !== 'success') {
                throw new Error(respuesta.message || 'No se pudo registrar el permiso.');
            }

            await successAlert('¡Registrado!', respuesta.message || 'Permiso registrado correctamente.');
            $('#modalRegistrarPermiso').modal('hide');
            $('#formRegistrarPermiso')[0].reset();
            if (verInactivos) {
                cambiarVistaEstado(false);
            } else {
                tabla.ajax.reload(null, false);
            }
        } catch (error) {
            const respuesta = error.responseJSON || {};
            await errorAlert('Error', respuesta.message || error.message || 'No se pudo registrar el permiso.');
        } finally {
            $boton.prop('disabled', false);
        }
    });

    $('#modalAsignarPermisos').on('show.bs.modal', async function () {
        $('#formAsignarPermisos')[0].reset();
        $('#selectRolPermisos').prop('disabled', true);
        $('#btnGuardarPermisos').prop('disabled', true);
        $('#listaPermisosRol').empty().append($('<p>', {
            class: 'text-muted mb-0',
            text: 'Cargando roles y permisos...'
        }));

        try {
            await cargarRolesYPermisos();
            $('#selectRolPermisos').prop('disabled', false);
            $('#listaPermisosRol').empty().append($('<p>', {
                class: 'text-muted mb-0',
                text: 'Seleccione un rol para cargar los permisos.'
            }));
        } catch (error) {
            $('#listaPermisosRol').empty().append($('<p>', {
                class: 'text-danger mb-0',
                text: error.message || 'No se pudieron cargar los datos.'
            }));
            await errorAlert('Error', error.message || 'No se pudieron cargar roles y permisos.');
        }
    });

    $('#selectRolPermisos').on('change', function () {
        cargarAsignacionesRol($(this).val());
    });

    $('#formAsignarPermisos').on('submit', async function (event) {
        event.preventDefault();
        const idRol = $('#selectRolPermisos').val();
        const idPermisos = $('#listaPermisosRol').find('input[name="id_permisos[]"]:checked')
            .map(function () { return this.value; }).get();

        if (!idRol) {
            await errorAlert('Datos incompletos', 'Seleccione un rol.');
            return;
        }

        $('#btnGuardarPermisos').prop('disabled', true);
        try {
            const respuesta = await $.ajax({
                url: URL + '&action=asignarPermisosRol',
                type: 'POST',
                dataType: 'json',
                data: { id_rol: idRol, id_permisos: idPermisos }
            });
            if (respuesta.status !== 'success') {
                throw new Error(respuesta.message || 'No se pudieron guardar los permisos.');
            }

            await successAlert('¡Guardado!', respuesta.message || 'Permisos del rol actualizados correctamente.');
            $('#modalAsignarPermisos').modal('hide');
        } catch (error) {
            await errorAlert('Error', error.message || 'No se pudieron guardar los permisos.');
            $('#btnGuardarPermisos').prop('disabled', false);
        }
    });

    $('#tablaPermisos tbody').on('click', '.btn-inactivar-permiso', async function () {
        const fila = tabla.row($(this).closest('tr')).data();
        if (!fila) return;

        const confirmacion = await confirmActionAlert(
            '¿Inactivar permiso?',
            `El permiso "${fila.nombre_permiso}" dejará de estar disponible para nuevas asignaciones.`
        );
        if (!confirmacion.isConfirmed) return;

        try {
            const respuesta = await $.ajax({
                url: URL + '&action=eliminarPermiso',
                type: 'POST',
                dataType: 'json',
                data: { id_permiso: fila.id_permiso }
            });
            if (respuesta.status !== 'success') {
                throw new Error(respuesta.message || 'No se pudo inactivar el permiso.');
            }

            await successAlert('¡Inactivado!', respuesta.message || 'Permiso inactivado correctamente.');
            tabla.ajax.reload(null, false);
        } catch (error) {
            await errorAlert('Error', error.message || 'No se pudo inactivar el permiso.');
        }
    });

    $('#tablaPermisos tbody').on('click', '.btn-activar-permiso', async function () {
        const fila = tabla.row($(this).closest('tr')).data();
        if (!fila) return;

        const confirmacion = await confirmActionAlert(
            '¿Habilitar permiso?',
            `El permiso "${fila.nombre_permiso}" volverá a estar disponible.`
        );
        if (!confirmacion.isConfirmed) return;

        try {
            const respuesta = await $.ajax({
                url: URL + '&action=activarPermiso',
                type: 'POST',
                dataType: 'json',
                data: { id_permiso: fila.id_permiso }
            });
            if (respuesta.status !== 'success') {
                throw new Error(respuesta.message || 'No se pudo habilitar el permiso.');
            }

            await successAlert('¡Habilitado!', respuesta.message || 'Permiso habilitado correctamente.');
            tabla.ajax.reload(null, false);
        } catch (error) {
            const respuesta = error.responseJSON || {};
            await errorAlert('Error', respuesta.message || error.message || 'No se pudo habilitar el permiso.');
        }
    });
});
