import $ from 'jquery';
import { lenguajeDataTableEs } from './helpers/datatable-espanol.js';
import {
    successAlert,
    errorAlert,
    editAlert,
    confirmActionAlert
} from './helpers/alerts.js';
import { abrirReporteEnPestana } from './helpers/report-viewer.js';
import { patrones } from './helpers/expresiones.js';

$(document).ready(function () {
    const $formRegistrarConsumo = $('#formRegistrarConsumo');
    const $formEditarConsumo = $('#formEditarConsumo');
    const $materiaPrimaRegistrar = $('#registrar_id_materia_prima');
    const $costoUnitarioRegistrar = $('#registrar_costo_unitario');

    let solicitudCostoMateriaPrima;

    function escaparHtml(valor) {
        return String(valor ?? '').replace(/[&<>"']/g, function (caracter) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[caracter];
        });
    }

    const urlControlador = 'index.php?controller=consumoMaterial&action=listar';
    const $tablaConsumos = $('#tablaConsumos');
    
    $tablaConsumos.on('xhr.dt', function (event, settings, json, xhr) {
        if (json) {
            if (!json.success || !Array.isArray(json.data)) {
                console.warn('Respuesta inesperada al cargar los consumos:', json);
            }
            return;
        }

        console.error('Error al cargar los consumos:', xhr.statusText);
        mostrarAlertaInformativa('Error', 'No se pudieron cargar los consumos de material.', 'error');
    });

    const tablaConsumos = $tablaConsumos.DataTable({
        ajax: {
            url: urlControlador,
            type: 'GET',
            data: { accion: 'listar' },
            dataSrc: function (json) {
                return json && json.success && Array.isArray(json.data) ? json.data : [];
            }
        },
        columns: [
            {
                data: 'id_consumo_material',
                render: function (data, type) {
                    if (type !== 'display') return data;
                    return `<span class="fw-bold">#${escaparHtml(data)}</span>`;
                }
            },
            {
                data: null,
                render: function (data, type, consumo) {
                    if (type !== 'display') {
                        return `${consumo.nombre_materia_prima || 'Sin material'} ${consumo.descripcion_de_consumo || 'Sin descripción'}`;
                    }
                    return `
                        <div class="fw-bold text-dark">${escaparHtml(consumo.nombre_materia_prima || 'Sin material')}</div>
                        <small class="text-muted"><i class="bi bi-info-circle"></i> ${escaparHtml(consumo.descripcion_de_consumo || 'Sin descripción')}</small>
                    `;
                }
            },
            {
                data: 'costo_unitario',
                render: function (data, type) {
                    const costo = Number(data || 0).toFixed(2);
                    return type === 'display'
                        ? `<span class="fw-bold text-muted">$${costo}</span>`
                        : costo;
                }
            },
            {
                data: null,
                render: function (data, type, consumo) {
                    const cantidad = `${consumo.cantidad_usada || 0} ${consumo.unidad_de_medida || ''}`.trim();
                    return type === 'display'
                        ? `<span class="fw-bold">${escaparHtml(cantidad)}</span>`
                        : cantidad;
                }
            },
            {
                data: null,
                render: function (data, type, consumo) {
                    const costoTotal = (Number(consumo.costo_unitario || 0) * Number(consumo.cantidad_usada || 0)).toFixed(2);
                    return type === 'display'
                        ? `<span class="text-success fw-bold">$${costoTotal}</span>`
                        : costoTotal;
                }
            },
            {
                data: null,
                render: function (data, type, consumo) {
                    if (type !== 'display') {
                        return `Orden #${consumo.id_produccion || 'N/A'} ${consumo.descripcion_pedido || ''} ${consumo.estado_de_produccion || ''}`;
                    }

                    return `
                        <span class="badge bg-secondary">Orden #${escaparHtml(consumo.id_produccion || 'N/A')}</span>
                        ${consumo.descripcion_pedido ? `<div class="small mt-1">${escaparHtml(consumo.descripcion_pedido)}</div>` : ''}
                        ${consumo.estado_de_produccion ? `<small class="text-muted d-block">${escaparHtml(consumo.estado_de_produccion)}</small>` : ''}
                    `;
                }
            },
            {
                data: null,
                orderable: false,
                render: function (data, type, consumo) {
                    if (type !== 'display') return '';
                    return `
                        <div class="text-center d-flex justify-content-center gap-1">
                            <button type="button" class="btn btn-sm btn-outline-primary btnEditarConsumo"
                                data-id_consumo_material="${escaparHtml(consumo.id_consumo_material || '')}"
                                data-id_materia_prima="${escaparHtml(consumo.id_materia_prima || '')}"
                                data-costo_unitario="${escaparHtml(consumo.costo_unitario || '')}"
                                data-cantidad_usada="${escaparHtml(consumo.cantidad_usada || '')}"
                                data-descripcion_de_consumo="${escaparHtml(consumo.descripcion_de_consumo || '')}"
                                data-id_produccion="${escaparHtml(consumo.id_produccion || '')}">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        language: lenguajeDataTableEs,
        pageLength: 10,
        responsive: true,
        ordering: false,
        dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>'
    });

    function marcarCampo($campo, esValido) {
        $campo.removeClass('is-valid is-invalid')
            .addClass(esValido ? 'is-valid' : 'is-invalid')
            .css({
                'border-color': esValido ? 'green' : 'red',
                'box-shadow': esValido ? '0 0 5px green' : '0 0 5px red'
            });
    }

    function validarCampo($campo) {
        const valor = String($campo.val() || '').trim();
        let esValido = $campo[0].checkValidity();

        if ($campo.attr('name') === 'costo_unitario' && valor === '') {
            esValido = false;
        }

        if (esValido && $campo.attr('name') === 'descripcion_de_consumo' && valor !== '') {
            esValido = patrones.descripcion.test(valor);
        }

        marcarCampo($campo, esValido);
        return esValido;
    }

    function validarFormulario($form) {
        let formularioValido = true;

        $form.find('input:not([type="hidden"]), select, textarea').each(function () {
            if (!validarCampo($(this))) formularioValido = false;
        });

        return formularioValido;
    }

    function limpiarValidaciones($form) {
        $form.find('.is-valid, .is-invalid')
            .removeClass('is-valid is-invalid')
            .removeAttr('style');
    }

    function configurarCombobox(inputSelector, idSelector, opcionesSelector, etiqueta) {
        const $busqueda = $(inputSelector);
        const $idSeleccionado = $(idSelector);
        const $opciones = $(opcionesSelector);
        const $contenedor = $busqueda.closest('.consumo-combobox');
        const normalizar = texto => texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase();
        const mensajeSeleccion = `Seleccione ${etiqueta} de la lista.`;

        function ocultarOpciones() {
            $opciones.removeClass('show');
            $busqueda.attr('aria-expanded', 'false').removeAttr('aria-activedescendant');
            $opciones.find('.active').removeClass('active');
        }

        function filtrarOpciones(filtro = '') {
            const textoFiltro = normalizar(filtro);
            const $opcionesFiltradas = $opciones.find('[data-value]');
            let cantidadCoincidencias = 0;
            $opcionesFiltradas.each(function () {
                const coincide = normalizar(this.textContent).includes(textoFiltro);
                $(this).toggle(coincide);
                if (coincide) cantidadCoincidencias++;
            });
            $opciones.find('[data-empty-message]').prop('hidden', cantidadCoincidencias > 0);
            $opciones.addClass('show');
            $busqueda.attr('aria-expanded', 'true');
            $opciones.find('.active').removeClass('active');
        }

        function seleccionarOpcion($opcion) {
            if (!$opcion.length || $opcion.prop('disabled')) return;
            $opciones.find('[data-value]').attr('aria-selected', 'false');
            $opcion.attr('aria-selected', 'true');
            $busqueda.val($opcion.text().trim()).get(0).setCustomValidity('');
            $idSeleccionado.val($opcion.data('value')).trigger('change');
            ocultarOpciones();
        }

        $busqueda.get(0).setCustomValidity(mensajeSeleccion);
        $busqueda.on('focus', function () {
            filtrarOpciones(this.value);
        }).on('input', function () {
            $idSeleccionado.val('').removeClass('is-valid is-invalid');
            $opciones.find('[data-value]').attr('aria-selected', 'false');
            this.setCustomValidity(mensajeSeleccion);
            filtrarOpciones(this.value);
            $idSeleccionado.trigger('change');
        }).on('keydown', function (event) {
            let $visibles = $opciones.find('[data-value]:visible:not(:disabled)');
            let indiceActivo = $visibles.index($opciones.find('.active'));

            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                filtrarOpciones(this.value);
                $visibles = $opciones.find('[data-value]:visible:not(:disabled)');
                if (!$visibles.length) return;
                indiceActivo = event.key === 'ArrowDown'
                    ? (indiceActivo + 1) % $visibles.length
                    : (indiceActivo <= 0 ? $visibles.length - 1 : indiceActivo - 1);
                const $activo = $visibles.eq(indiceActivo);
                $opciones.find('.active').removeClass('active');
                $activo.addClass('active');
                const idOpcion = `${$opciones.attr('id')}_item_${$activo.data('value')}`;
                $activo.attr('id', idOpcion);
                $busqueda.attr('aria-activedescendant', idOpcion);
            } else if (event.key === 'Enter' && $opciones.hasClass('show')) {
                const $activo = $opciones.find('.active');
                if ($activo.length) {
                    event.preventDefault();
                    seleccionarOpcion($activo);
                }
            } else if (event.key === 'Escape') {
                ocultarOpciones();
            }
        });

        $opciones.on('click', '[data-value]', function () {
            seleccionarOpcion($(this));
        });

        $(document).on('click', function (event) {
            if (!$contenedor[0].contains(event.target)) ocultarOpciones();
        });

        $idSeleccionado.on('change', function () {
            $busqueda.get(0).setCustomValidity(this.value ? '' : mensajeSeleccion);
            validarCampo($busqueda);
        });

        return {
            reset: function () {
                $busqueda.val('').removeClass('is-valid is-invalid').removeAttr('style');
                $busqueda.get(0).setCustomValidity(mensajeSeleccion);
                $idSeleccionado.val('').removeClass('is-valid is-invalid');
                $opciones.find('[data-value]').attr('aria-selected', 'false');
                filtrarOpciones('');
                ocultarOpciones();
            },
            seleccionar: function (id) {
                const $opcion = $opciones.find('[data-value]').filter(function () {
                    return String($(this).data('value')) === String(id);
                }).first();
                seleccionarOpcion($opcion);
            }
        };
    }

    const comboboxMateriaRegistrar = configurarCombobox(
        '#buscar_registrar_materia_prima',
        '#registrar_id_materia_prima',
        '#opciones_registrar_materia_prima',
        'una materia prima'
    );
    const comboboxOrdenRegistrar = configurarCombobox(
        '#buscar_registrar_orden_produccion',
        '#registrar_id_produccion',
        '#opciones_registrar_orden_produccion',
        'una orden de producción'
    );
    const comboboxMateriaEdicion = configurarCombobox(
        '#buscar_edit_materia_prima',
        '#edit_id_materia_prima',
        '#opciones_edit_materia_prima',
        'una materia prima'
    );
    const comboboxOrdenEdicion = configurarCombobox(
        '#buscar_edit_orden_produccion',
        '#edit_id_produccion',
        '#opciones_edit_orden_produccion',
        'una orden de producción'
    );

    async function confirmarAccion(titulo, texto) {
        return await confirmActionAlert(titulo, texto);
    }

    async function mostrarAlertaInformativa(titulo, texto, icono) {
        return icono === 'error'
            ? await errorAlert(titulo, texto)
            : await successAlert(titulo, texto);
    }

    function cargarCostoMateriaPrima(idMateriaPrima) {
        if (solicitudCostoMateriaPrima) solicitudCostoMateriaPrima.abort();
        $costoUnitarioRegistrar.val('');

        if (!idMateriaPrima) return;

        solicitudCostoMateriaPrima = $.ajax({
            url: 'index.php?controller=consumoMaterial&action=listar',
            type: 'GET',
            data: {
                accion: 'obtenerCostoMateriaPrima',
                id_materia_prima: idMateriaPrima
            },
            dataType: 'json'
        }).done(function (data) {
            if (data.success && data.costo_unitario !== undefined) {
                $costoUnitarioRegistrar.val(Number(data.costo_unitario).toFixed(2));
                return;
            }

            mostrarAlertaInformativa('Error', data.message || 'No se pudo obtener el costo actual de la materia prima.', 'error');
        }).fail(function (xhr, estado, error) {
            if (estado === 'abort') return;
            console.error('Error al obtener el costo de la materia prima:', error || estado);
            mostrarAlertaInformativa('Error', 'No se pudo obtener el costo actual de la materia prima.', 'error');
        }).always(function () {
            solicitudCostoMateriaPrima = null;
        });
    }

    function enviarFormulario($form, $modal) {
        if (!$form.length) return;

        const $submitButton = $form.find('button[type="submit"]').first();
        const textoOriginal = $submitButton.text();
        const actionUrl = $form.attr('action');

        if (!actionUrl) {
            mostrarAlertaInformativa('Error', 'URL de acción del formulario no encontrada.', 'error');
            return;
        }

        $submitButton.prop('disabled', true).text('Enviando...');

        $.ajax({
            url: actionUrl,
            type: 'POST',
            data: new FormData($form[0]),
            processData: false,
            contentType: false,
            dataType: 'json',
            headers: {
                Accept: 'application/json'
            }
        }).done(async function (data) {
            if (data.success) {
                const alertaExito = $form.is($formEditarConsumo) ? editAlert : successAlert;
                await alertaExito('¡Éxito!', data.message || 'Guardado correctamente.');
                if ($modal.length) $modal.modal('hide');
                $form[0].reset();
                if ($form.is($formRegistrarConsumo)) {
                    comboboxMateriaRegistrar.reset();
                    comboboxOrdenRegistrar.reset();
                }
                if ($form.is($formEditarConsumo)) {
                    comboboxMateriaEdicion.reset();
                    comboboxOrdenEdicion.reset();
                }
                limpiarValidaciones($form);
                tablaConsumos.ajax.reload();
            } else {
                await mostrarAlertaInformativa('Error', data.message || 'No se pudo procesar la solicitud.', 'error');
            }
        }).fail(async function (xhr, estado, error) {
            console.error('Error al enviar el formulario:', error || estado);
            await mostrarAlertaInformativa('Error de red', 'No se pudo conectar con el servidor.', 'error');
        }).always(function () {
            $submitButton.prop('disabled', false).text(textoOriginal);
        });
    }

    [$formRegistrarConsumo, $formEditarConsumo].forEach(function ($form) {
        $form.on('input change', 'input:not([type="hidden"]), select, textarea', function () {
            validarCampo($(this));
        });
    });

    $formRegistrarConsumo.on('submit', async function (event) {
        event.preventDefault();
        if (!validarFormulario($(this))) {
            await mostrarAlertaInformativa('Error', 'Revise los campos inválidos (marcados en rojo).', 'error');
            return;
        }

        const confirmacion = await confirmarAccion('¿Registrar consumo?', 'Se registrará el consumo de material.');
        if (!confirmacion.isConfirmed) return;

        enviarFormulario($(this), $('#modalRegistrarConsumo'));
    });

    $materiaPrimaRegistrar.on('change', function () {
        cargarCostoMateriaPrima($(this).val());
    });

    $('#modalRegistrarConsumo').on('shown.bs.modal', function () {
        cargarCostoMateriaPrima($materiaPrimaRegistrar.val());
    });

    $formEditarConsumo.on('submit', async function (event) {
        event.preventDefault();
        if (!validarFormulario($(this))) {
            await mostrarAlertaInformativa('Error', 'Revise los campos inválidos (marcados en rojo).', 'error');
            return;
        }

        const confirmacion = await confirmarAccion('¿Guardar cambios?', 'Se actualizarán los datos del consumo de material.');
        if (!confirmacion.isConfirmed) return;

        enviarFormulario($(this), $('#modalEditarConsumo'));
    });

    $('#formFiltrosReporteConsumo').on('submit', async function (event) {
        event.preventDefault();

        const parametros = new URLSearchParams({ accion: 'reporte' });
        const idMateriaPrima = $('#reporteMateriaPrima').val();
        const estadoProduccion = $('#reporteEstadoProduccion').val();
        if (idMateriaPrima) parametros.set('id_materia_prima', idMateriaPrima);
        if (estadoProduccion) parametros.set('estado_produccion', estadoProduccion);

        const pestanaReporte = abrirReporteEnPestana(
            `index.php?controller=consumoMaterial&action=listar&${parametros.toString()}`
        );
        if (!pestanaReporte) {
            await mostrarAlertaInformativa('Ventana bloqueada', 'Permite las ventanas emergentes para abrir el reporte.', 'warning');
            return;
        }
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalFiltrosReporteConsumo')).hide();
    });

    $('#tablaConsumos tbody').on('click', '.btnEditarConsumo', function () {
        const $boton = $(this);

        $('#edit_id_consumo_material').val($boton.attr('data-id_consumo_material') || '');
        comboboxMateriaEdicion.reset();
        comboboxMateriaEdicion.seleccionar($boton.attr('data-id_materia_prima') || '');
        comboboxOrdenEdicion.reset();
        comboboxOrdenEdicion.seleccionar($boton.attr('data-id_produccion') || '');
        $('#edit_costo_unitario').val($boton.attr('data-costo_unitario') || '');
        $('#edit_cantidad_usada').val($boton.attr('data-cantidad_usada') || '');
        $('#edit_descripcion_de_consumo').val($boton.attr('data-descripcion_de_consumo') || '');

        validarFormulario($formEditarConsumo);
        $('#modalEditarConsumo').modal('show');
    });
});
