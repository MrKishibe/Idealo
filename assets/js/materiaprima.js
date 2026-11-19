import $ from 'jquery';
import { lenguajeDataTableEs } from './helpers/datatable-espanol.js';
import { crearMarkupSkeletonDataTable } from './helpers/datatable-skeleton.js';
import { soloLetrasTeclado, soloNumerosTeclado, validarCampoNombrePatron } from './helpers/validaciones.js';
import { successAlert, errorAlert, confirmDeleteAlert } from './helpers/alerts.js';
import { abrirReporteEnPestana } from './helpers/report-viewer.js';

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
        const $form = $(formSelector);
        $form[0].reset();
        $form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        $form.find('.invalid-feedback, .valid-feedback, .feedback-validacion, .feedback-validación').remove();
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
    // INICIALIZACIÓN DEL DATATABLE CON ORDEN
    // ==========================================
    function inicializarDataTable() {
        if ($.fn.DataTable.isDataTable('#tablaMateriaPrima')) {
            tablaDataTable.destroy();
        }

        const estadoFiltro = verEliminados ? 'Inactivo' : 'Activo';

        tablaDataTable = $('#tablaMateriaPrima').DataTable({
            processing: true,
            language: {
                ...lenguajeDataTableEs,
                processing: crearMarkupSkeletonDataTable($('#tablaMateriaPrima thead th').length)
            },
            pageLength: 10,
            responsive: true,
            ordering: true,
            order: [[0, 'asc']],
            dom: '<"row mb-3"<"col-md-6"l><"col-md-6 text-end"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
            ajax: {
                url: `${urlModulo}&ajax=listar`,
                method: 'GET',
                data: { 'Consultar': 'yes' },
                dataSrc: function (json) {
                    let materiasPrimas = [];
                    if (json && Array.isArray(json.materias_primas)) {
                        materiasPrimas = json.materias_primas;
                    }
                    return materiasPrimas.filter(mp => mp.status_materia_prima === estadoFiltro);
                }
            },
            columns: [
                {
                    data: 'nombre_materia_prima',
                    type: 'string',
                    className: 'fw-bold',
                    render: function (data) {
                        return escaparHTML(data);
                    }
                },
                {
                    data: 'nombre_de_material',
                    type: 'string',
                    render: function (data) {
                        return escaparHTML(data || 'Sin tipo');
                    }
                },
                {
                    data: 'costo_unitario',
                    type: 'num',
                    render: function (data) {
                        return Number(data || 0).toFixed(2);
                    }
                },
                {
                    data: 'stock_actual',
                    type: 'num',
                    render: function (data, type, row) {
                        return `${parseInt(data || 0, 10)} ${escaparHTML(row.unidad_de_medida)}`;
                    }
                },
                {
                    data: 'stock_minimo',
                    type: 'num',
                    render: function (data, type, row) {
                        return `${parseInt(data || 0, 10)} ${escaparHTML(row.unidad_de_medida)}`;
                    }
                },
                {
                    data: 'status_materia_prima',
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
                    render: function (data) {
                        const id = data.id_materia_prima;
                        const nombre = escaparHTML(data.nombre_materia_prima);
                        const tipo = data.id_tipo_materia_prima;
                        const costo = data.costo_unitario;
                        const stockActual = parseInt(data.stock_actual || 0, 10);
                        const stockMinimo = parseInt(data.stock_minimo || 0, 10);
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
        limpiarFormularioModal('#formMateriaPrima');
        if ($('#id_tipo_materia_prima option').length <= 1) {
            await llenarSelectTipos();
        }
    });

    $('#modalReporteMateria').on('show.bs.modal', async function () {
        try {
            const respuesta = await cargarTiposActivos();
            const select = $('#tipoReporteSelect');
            select.find('option:not([value="todos"])').remove();

            if (respuesta && Array.isArray(respuesta.tipos)) {
                respuesta.tipos.forEach(function (tipo) {
                    const opcion = `<option value="${tipo.id_tipo_materia_prima}">${escaparHTML(tipo.nombre_de_material)}</option>`;
                    select.append(opcion);
                });
            }
        } catch (error) {
            console.error('Error al cargar tipos en reporte:', error);
        }
    });

    // Restricciones de teclado modulares
    $(document).on('keydown', '#nombre_materia_prima, #edit_activo_nombre, #edit_inactivo_nombre', soloLetrasTeclado);
    $(document).on('keydown', '#costo_unitario, #edit_activo_costo, #edit_inactivo_costo', soloNumerosTeclado);

    // Bloqueo estricto de solo números enteros en Stock (teclado y pegado)
    const selectoresStockEntero = '#stock_actual, #stock_minimo, #edit_activo_stock_actual, #edit_activo_stock_minimo, #edit_inactivo_stock_actual, #edit_inactivo_stock_minimo, #valor_stock_actual_reporte, #valor_stock_minimo_reporte';

    $(document).on('keydown', selectoresStockEntero, function (e) {
        const teclasPermitidas = [8, 9, 13, 27, 35, 36, 37, 38, 39, 40, 46];
        if (teclasPermitidas.includes(e.keyCode) || ((e.ctrlKey || e.metaKey) && [65, 67, 86, 88].includes(e.keyCode))) {
            return;
        }
        if (!/^[0-9]$/.test(e.key)) {
            e.preventDefault();
        }
    });

    $(document).on('input paste', selectoresStockEntero, function () {
        const valLimpio = $(this).val().replace(/[^0-9]/g, '');
        if ($(this).val() !== valLimpio) {
            $(this).val(valLimpio);
        }
    });

    // Validador dedicado para Stock Entero sin duplicar mensajes en pantalla
    function validarStockEntero($input, mensajeError) {
        const valor = $input.val().trim();
        $input.siblings('.invalid-feedback, .valid-feedback, .feedback-validacion').remove();

        if (!/^\d+$/.test(valor)) {
            $input.removeClass('is-valid').addClass('is-invalid');
            $('<div class="invalid-feedback d-block"></div>').text(mensajeError).insertAfter($input);
            return false;
        }

        $input.removeClass('is-invalid').addClass('is-valid');
        $('<div class="valid-feedback d-block">Campo válido</div>').insertAfter($input);
        return true;
    }

    // Limpieza explícita al hacer clic en cancelar o cerrar
    $(document).on('click', '.btn-cancelar-modal, button:contains("Cancelar")', function () {
        const $modal = $(this).closest('.modal');
        const formId = $modal.find('form').attr('id');
        if (formId) {
            limpiarFormularioModal('#' + formId);
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

        // Limpiar feedbacks estáticos previos antes de validar
        $('#formMateriaPrima').find('.invalid-feedback, .valid-feedback').remove();

        const vNom = validarCampoNombrePatron(inputNombre, 'nombre', 'El nombre debe tener entre 3 y 100 caracteres.');

        let vTipo = true;
        if (selectTipo.val() === '') {
            selectTipo.removeClass('is-valid').addClass('is-invalid');
            $('<div class="invalid-feedback d-block">Seleccione un tipo de materia prima.</div>').insertAfter(selectTipo);
            vTipo = false;
        } else {
            selectTipo.removeClass('is-invalid').addClass('is-valid');
        }

        const vCosto = validarCampoNombrePatron(inputCosto, 'numeroNoNegativo', 'El costo debe ser mayor o igual a 0.');
        const vStockAct = validarStockEntero(inputStockActual, 'El stock actual debe ser un número entero mayor o igual a 0.');
        const vStockMin = validarStockEntero(inputStockMinimo, 'El stock mínimo debe ser un número entero mayor o igual a 0.');

        let vUnidad = true;
        if (selectUnidad.val() === '') {
            selectUnidad.removeClass('is-valid').addClass('is-invalid');
            $('<div class="invalid-feedback d-block">Seleccione una unidad de medida.</div>').insertAfter(selectUnidad);
            vUnidad = false;
        } else {
            selectUnidad.removeClass('is-invalid').addClass('is-valid');
        }

        if (!vNom || !vTipo || !vCosto || !vStockAct || !vStockMin || !vUnidad) {
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
                    stock_actual: parseInt(inputStockActual.val().trim(), 10),
                    stock_minimo: parseInt(inputStockMinimo.val().trim(), 10),
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
        limpiarFormularioModal('#formEditarActivo');
        $('#edit_activo_id').val($(this).data('id'));
        $('#edit_activo_nombre').val($(this).data('nombre'));
        $('#edit_activo_costo').val($(this).data('costo'));
        $('#edit_activo_stock_actual').val(parseInt($(this).data('stock-actual'), 10));
        $('#edit_activo_stock_minimo').val(parseInt($(this).data('stock-minimo'), 10));
        $('#edit_activo_unidad').val($(this).data('unidad'));

        await llenarSelectTipos();
        $('#edit_activo_id_tipo').val($(this).data('id-tipo'));
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarActivo')).show();
    });

    $('body').on('click', '.btnEditarInactivo', async function () {
        limpiarFormularioModal('#formEditarInactivo');
        $('#edit_inactivo_id').val($(this).data('id'));
        $('#edit_inactivo_nombre').val($(this).data('nombre'));
        $('#edit_inactivo_costo').val($(this).data('costo'));
        $('#edit_inactivo_stock_actual').val(parseInt($(this).data('stock-actual'), 10));
        $('#edit_inactivo_stock_minimo').val(parseInt($(this).data('stock-minimo'), 10));
        $('#edit_inactivo_unidad').val($(this).data('unidad'));
        $('#edit_inactivo_estado').val($(this).data('estado') || 'Inactivo');

        await llenarSelectTipos();
        $('#edit_inactivo_id_tipo').val($(this).data('id-tipo'));
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarInactivo')).show();
    });

    // ==========================================
    // 3. AJAX PARA EDITAR (POST)
    // ==========================================
    async function enviarEdicion(id, inputNombre, selectTipo, inputCosto, inputStockActual, inputStockMinimo, selectUnidad, estadoModal, selectorModalForm, selectorModalId) {
        $(selectorModalForm).find('.invalid-feedback, .valid-feedback').remove();

        const vNom = validarCampoNombrePatron(inputNombre, 'nombre', 'El nombre debe tener entre 3 y 100 caracteres.');

        let vTipo = true;
        if (selectTipo.val() === '') {
            selectTipo.removeClass('is-valid').addClass('is-invalid');
            $('<div class="invalid-feedback d-block">Seleccione un tipo.</div>').insertAfter(selectTipo);
            vTipo = false;
        } else {
            selectTipo.removeClass('is-invalid').addClass('is-valid');
        }

        const vCosto = validarCampoNombrePatron(inputCosto, 'numeroNoNegativo', 'Costo inválido.');
        const vStockAct = validarStockEntero(inputStockActual, 'El stock actual debe ser un número entero mayor o igual a 0.');
        const vStockMin = validarStockEntero(inputStockMinimo, 'El stock mínimo debe ser un número entero mayor o igual a 0.');

        let vUnidad = true;
        if (selectUnidad.val() === '') {
            selectUnidad.removeClass('is-valid').addClass('is-invalid');
            $('<div class="invalid-feedback d-block">Seleccione una unidad.</div>').insertAfter(selectUnidad);
            vUnidad = false;
        } else {
            selectUnidad.removeClass('is-invalid').addClass('is-valid');
        }

        if (!vNom || !vTipo || !vCosto || !vStockAct || !vStockMin || !vUnidad) {
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
                    stock_actual: parseInt(inputStockActual.val().trim(), 10),
                    stock_minimo: parseInt(inputStockMinimo.val().trim(), 10),
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
    // 4. AJAX PARA INHABILITAR (POST)
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

    const formReporteMateria = document.getElementById('formReporteMateria');
    const modalReporteMateriaElement = document.getElementById('modalReporteMateria');
    const modalReporteMateria = modalReporteMateriaElement ? bootstrap.Modal.getOrCreateInstance(modalReporteMateriaElement) : null;

    if (formReporteMateria) {
        formReporteMateria.addEventListener('submit', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const parametros = new URLSearchParams(new FormData(formReporteMateria));
            parametros.set('accion', 'generar_reporte');

            const estadoReporteSelect = $('#estadoReporteSelect').val();

            if (tablaDataTable && estadoReporteSelect === 'Activo') {
                const datosFiltrados = tablaDataTable.rows({ search: 'applied' }).data().toArray();
                const ids = datosFiltrados.map(mp => mp.id_materia_prima);
                parametros.set('ids_filtrados', JSON.stringify(ids));
            } else {
                parametros.set('ids_filtrados', JSON.stringify([]));
            }

            const pestanaReporte = abrirReporteEnPestana(`${urlModulo}&${parametros.toString()}`);
            if (!pestanaReporte) {
                errorAlert('Ventana bloqueada', 'Permite las ventanas emergentes para abrir el reporte.');
                return;
            }

            if (modalReporteMateria) {
                modalReporteMateria.hide();
            }
        });
    }

    // Carga inicial
    inicializarDataTable();
});