import $ from 'jquery';
import { lenguajeDataTableEs } from './helpers/datatable-espanol.js';
import { soloLetrasTeclado, validarCampoNombrePatron } from './helpers/validaciones.js';
import { successAlert, errorAlert, confirmDeleteAlert } from './helpers/alerts.js';

$(document).ready(function () {
    let tablaDataTable = null;
    let verEliminados = false;

    const urlModulo = 'index.php?controller=materiaPrima&action=listar';

    console.log('JS Materia Prima cargado');

    function escaparHTML(texto) {
        return $('<div>').text(texto ?? '').html();
    }

    function limpiarFormularioModal(formSelector) {
        if (!formSelector || !$(formSelector).length) return;
        $(formSelector)[0].reset();
        $(formSelector).find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        $(formSelector).find('.feedback-validacion').remove();
    }

    // ==========================================
    // 1. AJAX PARA CARGAR TIPOS (GET)
    // ==========================================
    async function cargarTiposActivos() {
        return await $.ajax({
            url: `${urlModulo}&ajax=tipos_activos`,
            type: 'GET',
            dataType: 'json'
        });
    }

    async function llenarSelectTipos() {
        try {
            const respuesta = await cargarTiposActivos();
            const opciones = '<option value="">Seleccione un tipo...</option>';
            $('#id_tipo_materia_prima, #edit_activo_id_tipo, #edit_inactivo_id_tipo').empty().append(opciones);

            if (respuesta && Array.isArray(respuesta.tipos)) {
                respuesta.tipos.forEach(function (tipo) {
                    const opcion = `<option value="${tipo.id_tipo_materia_prima}">${escaparHTML(tipo.nombre_de_material)}</option>`;
                    $('#id_tipo_materia_prima, #edit_activo_id_tipo, #edit_inactivo_id_tipo').append(opcion);
                });
            }
        } catch (error) {
            console.error('Error al cargar tipos de materia prima:', error);
        }
    }

    // ==========================================
    // INICIALIZACIÓN DEL DATATABLE (Estructura de Cuaderno)
    // ==========================================
    function inicializarDataTable() {
        if ($.fn.DataTable.isDataTable('#tablaMateriaPrima')) {
            tablaDataTable.destroy();
        }

        const estadoFiltro = verEliminados ? 'Inactivo' : 'Activo';

        tablaDataTable = $('#tablaMateriaPrima').DataTable({
            language: lenguajeDataTableEs,
            pageLength: 10,
            responsive: true,
            ordering: false,
            ajax: {
                url: `${urlModulo}&ajax=listar`,
                method: 'GET',
                data: { 'Consultar': 'yes' },
                dataSrc: function (json) {
                    let materiasPrimas = [];
                    if (json && Array.isArray(json.materias_primas)) {
                        materiasPrimas = json.materias_primas;
                    }
                    // Filtrar dinámicamente entre Activos e Inactivos según la vista seleccionada
                    return materiasPrimas.filter(mp => mp.status_materia_prima === estadoFiltro);
                }
            },
            columns: [
                {
                    data: null,
                    className: 'fw-bold',
                    render: function (data) {
                        return escaparHTML(data.nombre_materia_prima);
                    }
                },
                {
                    data: null,
                    render: function (data) {
                        return escaparHTML(data.nombre_de_material || 'Sin tipo');
                    }
                },
                {
                    data: null,
                    render: function (data) {
                        return data.costo_unitario;
                    }
                },
                {
                    data: null,
                    render: function (data) {
                        return `${data.stock_actual} ${escaparHTML(data.unidad_de_medida)}`;
                    }
                },
                {
                    data: null,
                    render: function (data) {
                        return `${data.stock_minimo} ${escaparHTML(data.unidad_de_medida)}`;
                    }
                },
                {
                    data: null,
                    render: function (data) {
                        return data.status_materia_prima === 'Activo'
                            ? '<span class="badge bg-success">Activo</span>'
                            : '<span class="badge bg-danger">Inactivo</span>';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function (data) {
                        const id = data.id_materia_prima;
                        const nombre = escaparHTML(data.nombre_materia_prima);
                        const tipo = data.id_tipo_materia_prima;
                        const costo = data.costo_unitario;
                        const stockActual = data.stock_actual;
                        const stockMinimo = data.stock_minimo;
                        const unidad = escaparHTML(data.unidad_de_medida);
                        const estado = data.status_materia_prima;

                        let acciones = '';

                        if (estado === 'Activo') {
                            acciones = `
                                <button type="button" class="btn btn-sm btn-outline-primary btnEditarActivo me-1" 
                                    data-id="${id}" 
                                    data-nombre="${nombre}" 
                                    data-id-tipo="${tipo}" 
                                    data-costo="${costo}" 
                                    data-stock-actual="${stockActual}" 
                                    data-stock-minimo="${stockMinimo}" 
                                    data-unidad="${unidad}">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger btnCambiarEstado" 
                                    data-id="${id}" 
                                    data-nombre="${nombre}">
                                    <i class="bi bi-trash3-fill"></i>
                                </button>
                            `;
                        } else {
                            acciones = `
                                <button type="button" class="btn btn-sm btn-outline-warning btnEditarInactivo" 
                                    data-id="${id}" 
                                    data-nombre="${nombre}" 
                                    data-id-tipo="${tipo}" 
                                    data-costo="${costo}" 
                                    data-stock-actual="${stockActual}" 
                                    data-stock-minimo="${stockMinimo}" 
                                    data-unidad="${unidad}"
                                    data-estado="${estado}">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            `;
                        }
                        return `<div class="text-center">${acciones}</div>`;
                    }
                }
            ]
        });
    }

    // Función auxiliar para recargar la tabla de forma limpia
    function recargarTabla() {
        if (tablaDataTable) {
            tablaDataTable.ajax.reload(null, false);
        } else {
            inicializarDataTable();
        }
    }

    $('#btnAlternarEstado').on('click', function () {
        verEliminados = !verEliminados;
        if (verEliminados) {
            $(this).removeClass('btn-outline-secondary').addClass('btn-secondary');
            $('#txtBotonEstado').text('Ver Activos');
            $('#iconoEstado').removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
            $('#tituloVista').text('Materias Primas Inhabilitadas');
        } else {
            $(this).removeClass('btn-secondary').addClass('btn-outline-secondary');
            $('#txtBotonEstado').text('Ver inhabilitados');
            $('#iconoEstado').removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
            $('#tituloVista').text('Materia Prima');
        }
        inicializarDataTable();
    });

    $('#modalRegistrarMateriaPrima').on('show.bs.modal', async function () {
        await llenarSelectTipos();
    });

    $(document).on('keydown', '#nombre_materia_prima, #edit_activo_nombre, #edit_inactivo_nombre', soloLetrasTeclado);

    $('#modalRegistrarMateriaPrima, #modalEditarActivo, #modalEditarInactivo').on('click', '[data-bs-dismiss="modal"]', function () {
        if ($(this).text().trim().toLowerCase() === 'cancelar') {
            const formId = $(this).closest('.modal').find('form').attr('id');
            if (formId) limpiarFormularioModal('#' + formId);
        }
    });

    // ==========================================
    // 2. AJAX PARA REGISTRAR (POST)
    // ==========================================
    $('#btnEnvio').on('click', async function (e) {
        e.preventDefault();
        const inputNombre = $('#nombre_materia_prima');
        const selectTipo = $('#id_tipo_materia_prima');
        const inputCosto = $('#costo_unitario');
        const inputStockActual = $('#stock_actual');
        const inputStockMinimo = $('#stock_minimo');
        const selectUnidad = $('#unidad_de_medida');

        if (!validarCampoNombrePatron(inputNombre, 'nombre', 'El nombre debe tener entre 2 y 100 caracteres válidos.')) return;

        if (selectTipo.val() === '') {
            selectTipo.addClass('is-invalid');
            await errorAlert('Error', 'Seleccione un tipo de materia prima.');
            return;
        }

        if (!validarCampoNombrePatron(inputCosto, 'numeroNoNegativo', 'El costo debe ser un valor no negativo.')) return;
        if (!validarCampoNombrePatron(inputStockActual, 'numeroNoNegativo', 'El stock actual debe ser un valor no negativo.')) return;
        if (!validarCampoNombrePatron(inputStockMinimo, 'numeroNoNegativo', 'El stock mínimo debe ser un valor no negativo.')) return;

        if (selectUnidad.val() === '') {
            selectUnidad.addClass('is-invalid');
            await errorAlert('Error', 'Seleccione una unidad de medida.');
            return;
        }

        try {
            const respuesta = await $.ajax({
                url: urlModulo,
                type: 'POST',
                data: {
                    nombre: inputNombre.val().trim(),
                    id_tipo_materia_prima: selectTipo.val(),
                    costo_unitario: inputCosto.val().trim(),
                    stock_actual: inputStockActual.val().trim(),
                    stock_minimo: inputStockMinimo.val().trim(),
                    unidad_de_medida: selectUnidad.val()
                },
                dataType: 'json'
            });

            if (respuesta.success) {
                await successAlert('Completado', respuesta.message);
                limpiarFormularioModal('#formMateriaPrima');
                bootstrap.Modal.getInstance(document.getElementById('modalRegistrarMateriaPrima')).hide();
                recargarTabla();
            } else {
                await errorAlert('Error', respuesta.message);
            }
        } catch (error) {
            await errorAlert('Error', 'Error de comunicación con el servidor.');
        }
    });

    $('body').on('click', '.btnEditarActivo', async function () {
        $('#edit_activo_id').val($(this).data('id'));
        $('#edit_activo_nombre').val($(this).data('nombre'));
        $('#edit_activo_costo').val($(this).data('costo'));
        $('#edit_activo_stock_actual').val($(this).data('stock-actual'));
        $('#edit_activo_stock_minimo').val($(this).data('stock-minimo'));
        $('#edit_activo_unidad').val($(this).data('unidad'));

        await llenarSelectTipos();
        $('#edit_activo_id_tipo').val($(this).data('id-tipo'));
        new bootstrap.Modal(document.getElementById('modalEditarActivo')).show();
    });

    $('body').on('click', '.btnEditarInactivo', async function () {
        $('#edit_inactivo_id').val($(this).data('id'));
        $('#edit_inactivo_nombre').val($(this).data('nombre'));
        $('#edit_inactivo_costo').val($(this).data('costo'));
        $('#edit_inactivo_stock_actual').val($(this).data('stock-actual'));
        $('#edit_inactivo_stock_minimo').val($(this).data('stock-minimo'));
        $('#edit_inactivo_unidad').val($(this).data('unidad'));
        $('#edit_inactivo_estado').val($(this).data('estado') || 'Inactivo');

        await llenarSelectTipos();
        $('#edit_inactivo_id_tipo').val($(this).data('id-tipo'));
        new bootstrap.Modal(document.getElementById('modalEditarInactivo')).show();
    });

    // ==========================================
    // 3. AJAX PARA EDITAR (POST)
    // ==========================================
    async function enviarEdicion(id, inputNombre, selectTipo, inputCosto, inputStockActual, inputStockMinimo, selectUnidad, estadoModal, selectorModalForm, selectorModalId) {
        if (!validarCampoNombrePatron(inputNombre, 'nombre', 'Nombre inválido.')) return;

        if (selectTipo.val() === '') {
            selectTipo.addClass('is-invalid');
            await errorAlert('Error', 'Seleccione un tipo.');
            return;
        }

        if (!validarCampoNombrePatron(inputCosto, 'numeroNoNegativo', 'Costo inválido.')) return;
        if (!validarCampoNombrePatron(inputStockActual, 'numeroNoNegativo', 'Stock actual inválido.')) return;
        if (!validarCampoNombrePatron(inputStockMinimo, 'numeroNoNegativo', 'Stock mínimo inválido.')) return;

        if (selectUnidad.val() === '') {
            selectUnidad.addClass('is-invalid');
            await errorAlert('Error', 'Seleccione una unidad.');
            return;
        }

        try {
            const respuesta = await $.ajax({
                url: urlModulo,
                type: 'POST',
                data: {
                    id_accion: id,
                    nuevo_estado: estadoModal,
                    nombre: inputNombre.val().trim(),
                    id_tipo_materia_prima: selectTipo.val(),
                    costo_unitario: inputCosto.val().trim(),
                    stock_actual: inputStockActual.val().trim(),
                    stock_minimo: inputStockMinimo.val().trim(),
                    unidad_de_medida: selectUnidad.val()
                },
                dataType: 'json'
            });

            if (respuesta.success) {
                await successAlert('Actualizado', respuesta.message);
                limpiarFormularioModal(selectorModalForm);
                bootstrap.Modal.getInstance(document.getElementById(selectorModalId)).hide();
                recargarTabla();
            } else {
                await errorAlert('Error', respuesta.message);
            }
        } catch (error) {
            await errorAlert('Error', 'No se pudo actualizar el registro.');
        }
    }

    $('#btnGuardarEdicionActivo').on('click', async function (e) {
        e.preventDefault();
        await enviarEdicion(
            $('#edit_activo_id').val(),
            $('#edit_activo_nombre'),
            $('#edit_activo_id_tipo'),
            $('#edit_activo_costo'),
            $('#edit_activo_stock_actual'),
            $('#edit_activo_stock_minimo'),
            $('#edit_activo_unidad'),
            'Activo',
            '#formEditarActivo',
            'modalEditarActivo'
        );
    });

    $('#btnGuardarEdicionInactivo').on('click', async function (e) {
        e.preventDefault();
        await enviarEdicion(
            $('#edit_inactivo_id').val(),
            $('#edit_inactivo_nombre'),
            $('#edit_inactivo_id_tipo'),
            $('#edit_inactivo_costo'),
            $('#edit_inactivo_stock_actual'),
            $('#edit_inactivo_stock_minimo'),
            $('#edit_inactivo_unidad'),
            $('#edit_inactivo_estado').val(),
            '#formEditarInactivo',
            'modalEditarInactivo'
        );
    });

    // ==========================================
    // 4. AJAX PARA INHABILITAR / ELIMINAR (POST)
    // ==========================================
    $('body').on('click', '.btnCambiarEstado', function () {
        const id = $(this).data('id');

        confirmDeleteAlert(async () => {
            try {
                const respuesta = await $.ajax({
                    url: urlModulo,
                    type: 'POST',
                    data: { 
                        id_accion: id, 
                        nuevo_estado: 'Inactivo' 
                    },
                    dataType: 'json'
                });

                if (respuesta.success) {
                    await successAlert('Inhabilitado', respuesta.message);
                    recargarTabla();
                } else {
                    await errorAlert('Error', respuesta.message);
                }
            } catch (error) {
                await errorAlert('Error', 'No se pudo inhabilitar el registro.');
            }
        });
    });

    // Carga inicial
    inicializarDataTable();
});