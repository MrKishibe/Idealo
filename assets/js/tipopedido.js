import $ from 'jquery';
import { lenguajeDataTableEs } from './helpers/datatable-espanol.js';
import { crearMarkupSkeletonDataTable } from './helpers/datatable-skeleton.js';
import { soloLetrasTeclado, validarCampoNombrePatron } from './helpers/validaciones.js';
import { successAlert, errorAlert, confirmDeleteAlert } from './helpers/alerts.js';

$(document).ready(function () {
    let tablaDataTable = null;
    let verEliminados = false;

    const urlModulo = 'index.php?controller=tipoPedido&action=listar';

    function inicializarDataTable() {
        if ($.fn.DataTable.isDataTable('#tablaTipoPedido')) {
            tablaDataTable.destroy();
        }

        const estadoFiltro = verEliminados ? 'Inactivo' : 'Activo';

        tablaDataTable = $('#tablaTipoPedido').DataTable({
            ajax: {
                url: `${urlModulo}&ajax=listar`,
                type: 'GET',
                dataType: 'json',
                dataSrc: function (json) {
                    let pedidos = [];
                    if (json && Array.isArray(json.pedidos)) {
                        pedidos = json.pedidos;
                    }
                    // Filtrar dinámicamente entre Activos e Inactivos según la vista seleccionada
                    return pedidos.filter(p => p.status_tipo_servicio === estadoFiltro);
                },
                error: async function () {
                    await errorAlert('Error de conexión', 'No se pudieron cargar los tipos de pedido.');
                }
            },
            columns: [
                {
                    data: 'nombre_tipo_pedido',
                    type: 'string',
                    render: function (data) {
                        return `<span class="fw-bold">${escaparHTML(data)}</span>`;
                    }
                },
                {
                    data: 'status_tipo_servicio',
                    type: 'string',
                    render: function (data) {
                        return data === 'Activo'
                            ? '<span class="badge bg-success">Activo</span>'
                            : '<span class="badge bg-danger">Inactivo</span>';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    render: function (data) {
                        const id = escaparHTML(data.id_tipo_pedido);
                        const nombre = escaparHTML(data.nombre_tipo_pedido);
                        const estado = escaparHTML(data.status_tipo_servicio);

                        let acciones = '';
                        if (estado === 'Activo') {
                            acciones = `
                                <button type="button" class="btn btn-sm btn-outline-primary btnEditarActivo me-1" data-id="${id}" data-nombre="${nombre}" data-estado="${estado}" title="Editar Tipo de Pedido">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger btnCambiarEstado" data-id="${id}" data-nombre="${nombre}" data-estado="Inactivo" title="Inhabilitar Tipo de Pedido">
                                    <i class="bi bi-trash3-fill"></i>
                                </button>
                            `;
                        } else {
                            acciones = `
                                <button type="button" class="btn btn-sm btn-outline-warning btnEditarInactivo" data-id="${id}" data-nombre="${nombre}" data-estado="${estado}" title="Editar / Reactivar">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            `;
                        }
                        return `<div>${acciones}</div>`;
                    }
                }
            ],
            processing: true,
            language: {
                ...lenguajeDataTableEs,
                processing: crearMarkupSkeletonDataTable($('#tablaTipoPedido thead th').length)
            },
            pageLength: 10,
            responsive: true,
            ordering: true,
            order: [[0, 'asc']],
            dom: '<"row mb-3"<"col-md-6"l><"col-md-6 text-end"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>'
        });
    }

    function recargarTablaPedidos() {
        if (tablaDataTable) {
            tablaDataTable.ajax.reload(null, false);
        } else {
            inicializarDataTable();
        }
    }

    async function procesarGuardado({ datosBody, modalSelector, formSelector }) {
        try {
            const respuesta = await $.ajax({
                url: urlModulo,
                type: 'POST',
                data: datosBody,
                dataType: 'json'
            });

            if (respuesta.success) {
                await successAlert('¡Completado!', respuesta.message);

                if (formSelector && $(formSelector).length) {
                    $(formSelector)[0].reset();
                }

                const $modal = $(modalSelector);
                $modal.find('input, select, textarea').val('').removeClass('is-valid is-invalid');
                $modal.find('.feedback-validacion, .invalid-feedback, .valid-feedback').remove();
                
                const modalElement = $modal[0];
                if (modalElement) {
                    const modalInstance = bootstrap.Modal.getInstance(modalElement);
                    if (modalInstance) modalInstance.hide();
                }

                recargarTablaPedidos();
            } else {
                await errorAlert('Error de validación', respuesta.message);
            }
        } catch (error) {
            await errorAlert('Error', 'Ocurrió un error inesperado al procesar la solicitud.');
        }
    }

    // Evento Registrar (AJAX POST)
    $('#btnEnvio').on('click', async function (evento) {
        evento.preventDefault();
        const inputNombre = $('#nombre_tipo_pedido');

        if (!validarCampoNombrePatron(inputNombre, 'nombre', 'El nombre debe tener entre 2 y 100 caracteres válidos.')) return;

        await procesarGuardado({
            datosBody: { nombre: inputNombre.val().trim() },
            modalSelector: '#modalRegistrarPedido',
            formSelector: '#formTipoPedido'
        });
    });

    // Evento Editar Activo (AJAX POST)
    $('#btnGuardarEdicionActivo').on('click', async function (evento) {
        evento.preventDefault();
        const id = $('#edit_activo_id').val();
        const inputNombre = $('#edit_activo_nombre');

        if (!validarCampoNombrePatron(inputNombre, 'nombre', 'El nombre debe tener entre 2 y 100 caracteres válidos.')) return;

        await procesarGuardado({
            datosBody: {
                id_accion: id,
                nuevo_estado: 'Activo',
                nombre: inputNombre.val().trim()
            },
            modalSelector: '#modalEditarActivo',
            formSelector: '#formEditarActivo'
        });
    });

    // Evento Editar Inactivo / Reactivar (AJAX POST)
    $('#btnGuardarEdicionInactivo').on('click', async function (evento) {
        evento.preventDefault();
        const id = $('#edit_id_pedido').val();
        const inputNombre = $('#edit_nombre_pedido');
        const nuevoEstado = $('#edit_status_pedido').val();

        if (!validarCampoNombrePatron(inputNombre, 'nombre', 'El nombre debe tener entre 2 y 100 caracteres válidos.')) return;

        await procesarGuardado({
            datosBody: {
                id_accion: id,
                nombre: inputNombre.val().trim(),
                nuevo_estado: nuevoEstado
            },
            modalSelector: '#modalEditarInactivo',
            formSelector: '#formEditarPedido'
        });
    });

    $(document).on('click', '.btnCambiarEstado', async function () {
        const id = $(this).data('id');
        const nuevoEstado = $(this).data('estado');

        confirmDeleteAlert(async () => {
            try {
                const respuesta = await $.ajax({
                    url: urlModulo,
                    type: 'POST',
                    data: { id_accion: id, nuevo_estado: nuevoEstado },
                    dataType: 'json'
                });

                if (respuesta.success) {
                    await successAlert('Inhabilitado', respuesta.message);
                    recargarTablaPedidos();
                } else {
                    await errorAlert('Error', respuesta.message);
                }
            } catch (error) {
                await errorAlert('Error', 'No se pudo cambiar el estado del tipo de pedido.');
            }
        });
    });

    function escaparHTML(texto) {
        return $('<div>').text(texto ?? '').html();
    }

    $(document).on('keydown', '#nombre_tipo_pedido, #edit_activo_nombre, #edit_nombre_pedido', soloLetrasTeclado);

    $('#btnAlternarEstado').on('click', function () {
        verEliminados = !verEliminados;
        const $btn = $(this);

        if (verEliminados) {
            $btn.attr('data-vista', 'eliminados').removeClass('btn-outline-secondary').addClass('btn-secondary');
            $('#txtBotonEstado').text('Ver Activos');
            $('#iconoEstado').removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
            $('#tituloVista').text('Tipos de Pedido Inhabilitados');
        } else {
            $btn.attr('data-vista', 'activos').removeClass('btn-secondary').addClass('btn-outline-secondary');
            $('#txtBotonEstado').text('Ver inhabilitados');
            $('#iconoEstado').removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
            $('#tituloVista').text('Tipo de Pedido');
        }
        inicializarDataTable();
    });

    $(document).on('click', '.btnEditarActivo', function () {
        $('#edit_activo_id').val($(this).data('id'));
        $('#edit_activo_nombre').val($(this).data('nombre')).removeClass('is-invalid is-valid');
        bootstrap.Modal.getOrCreateInstance($('#modalEditarActivo')[0]).show();
    });

    $(document).on('click', '.btnEditarInactivo', function () {
        $('#edit_id_pedido').val($(this).data('id'));
        $('#edit_nombre_pedido').val($(this).data('nombre')).removeClass('is-invalid is-valid');
        $('#edit_status_pedido').val($(this).data('estado') || 'Inactivo');
        bootstrap.Modal.getOrCreateInstance($('#modalEditarInactivo')[0]).show();
    });

    // Carga inicial de la tabla
    inicializarDataTable();
});