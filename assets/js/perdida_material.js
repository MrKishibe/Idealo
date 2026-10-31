import $ from 'jquery';
import { lenguajeDataTableEs } from './helpers/datatable-espanol.js';
import { confirmActionAlert, statusAlert } from './helpers/alerts.js';
import {
    validarCampo as validarCampoCompartido,
    marcarCampo,
    patrones,
    obtenerFechaLocalISO
} from './helpers/expresiones.js';
import { abrirReporteEnPestana } from './helpers/report-viewer.js';

$(document).ready(function () {
    const $formRegistrarPerdida = $('#formRegistrarPerdida');
    const $formEditarPerdida = $('#formEditarPerdida');

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

    const urlControlador = 'index.php?url=perdidaMaterial/listar';
    const $tablaPerdidas = $('#tablaPerdidasMaterial');
    
    $tablaPerdidas.on('xhr.dt', function (event, settings, json, xhr) {
        if (json) {
            if (!json.success || !Array.isArray(json.data)) {
                console.warn('Respuesta inesperada al cargar las pérdidas de material:', json);
            }
            return;
        }

        console.error('Error al cargar las pérdidas de material:', xhr.statusText);
        mostrarAlertaInformativa('Error', 'No se pudieron cargar las pérdidas de material.', 'error');
    });

    const tablaPerdidas = $tablaPerdidas.DataTable({
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
                data: 'id_perdida_material',
                render: function (data, type) {
                    if (type !== 'display') return data;
                    return `<strong>#${escaparHtml(data)}</strong>`;
                }
            },
            { data: 'cantidad_perdida', defaultContent: '0' },
            { data: 'fecha_de_registro', defaultContent: 'N/A' },
            {
                data: 'motivo',
                render: function (data, type) {
                    const motivo = data || 'Sin motivo especificado';
                    return type === 'display' ? escaparHtml(motivo) : motivo;
                }
            },
            {
                data: 'costo_total_perdida',
                render: function (data, type) {
                    const costo = Number(data || 0).toFixed(2);
                    return type === 'display' ? `$${costo}` : costo;
                }
            },
            {
                data: null,
                render: function (data, type, perdida) {
                    const materiaPrima = perdida.nombre_materia_prima
                        ? perdida.nombre_materia_prima + (perdida.unidad_de_medida ? ` (${perdida.unidad_de_medida})` : '')
                        : 'Materia prima no disponible';
                    return type === 'display' ? escaparHtml(materiaPrima) : materiaPrima;
                }
            },
            {
                data: null,
                render: function (data, type, perdida) {
                    let produccion = `Orden #${perdida.id_produccion || 'N/A'}`;
                    if (perdida.descripcion_pedido) produccion += ` - ${perdida.descripcion_pedido}`;
                    return type === 'display' ? escaparHtml(produccion) : produccion;
                }
            },
            {
                data: null,
                orderable: false,
                render: function (data, type, perdida) {
                    if (type !== 'display') return '';
                    return `
                        <div class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-primary btnEditarPerdida"
                                data-id_perdida="${escaparHtml(perdida.id_perdida_material || '')}"
                                data-cantidad="${escaparHtml(perdida.cantidad_perdida || '')}"
                                data-fecha="${escaparHtml(perdida.fecha_de_registro || '')}"
                                data-costo-total="${escaparHtml(perdida.costo_total_perdida || '')}"
                                data-id_produccion="${escaparHtml(perdida.id_produccion || '')}"
                                data-id_materia_prima="${escaparHtml(perdida.id_materia_prima || '')}"
                                data-motivo="${escaparHtml(perdida.motivo || '')}">
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

    function validarCampo($campo) {
        const valor = String($campo.val() || '').trim();
        if ($campo.attr('type') === 'date' && $campo.closest('#formRegistrarPerdida').length) {
            $campo.attr('min', obtenerFechaLocalISO());
        }
        let esValido = $campo[0].checkValidity();

        if (esValido && $campo.attr('name') === 'motivo') {
            esValido = validarCampoCompartido($campo, patrones.descripcion, {
                requerido: false,
                estilo: true,
                feedback: false
            });
        }

        marcarCampo($campo, esValido, {
            estilo: true,
            feedback: false
        });
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

    function configurarComboboxOrden(inputSelector, idSelector, opcionesSelector) {
        const $busqueda = $(inputSelector);
        const $idProduccion = $(idSelector);
        const $opciones = $(opcionesSelector);
        const $contenedor = $busqueda.closest('.orden-combobox');
        const normalizar = texto => texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase();

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
            if (!$opcion.length) return;
            $opciones.find('[data-value]').attr('aria-selected', 'false');
            $opcion.attr('aria-selected', 'true');
            $busqueda.val($opcion.text().trim()).get(0).setCustomValidity('');
            $idProduccion.val($opcion.data('value')).trigger('change');
            ocultarOpciones();
        }

        $busqueda.get(0).setCustomValidity('Seleccione una orden de producción de la lista.');
        $busqueda.on('focus', function () {
            filtrarOpciones(this.value);
        }).on('input', function () {
            $idProduccion.val('').removeClass('is-valid is-invalid');
            $opciones.find('[data-value]').attr('aria-selected', 'false');
            this.setCustomValidity('Seleccione una orden de producción de la lista.');
            filtrarOpciones(this.value);
            $idProduccion.trigger('change');
        }).on('keydown', function (event) {
            let $visibles = $opciones.find('[data-value]:visible');
            let indiceActivo = $visibles.index($opciones.find('.active'));

            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                filtrarOpciones(this.value);
                $visibles = $opciones.find('[data-value]:visible');
                if (!$visibles.length) return;
                indiceActivo = event.key === 'ArrowDown'
                    ? (indiceActivo + 1) % $visibles.length
                    : (indiceActivo <= 0 ? $visibles.length - 1 : indiceActivo - 1);
                const $activo = $visibles.eq(indiceActivo);
                $opciones.find('.active').removeClass('active');
                $activo.addClass('active');
                const idOpcion = `${$opciones.attr('id')}_orden_${$activo.data('value')}`;
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

        $idProduccion.on('change', function () {
            $busqueda.get(0).setCustomValidity(this.value ? '' : 'Seleccione una orden de producción de la lista.');
            validarCampo($busqueda);
        });

        return {
            reset: function () {
                $busqueda.val('').removeClass('is-valid is-invalid').removeAttr('style');
                $busqueda.get(0).setCustomValidity('Seleccione una orden de producción de la lista.');
                $idProduccion.val('').removeClass('is-valid is-invalid');
                $opciones.find('[data-value]').attr('aria-selected', 'false');
                filtrarOpciones('');
                ocultarOpciones();
            },
            seleccionar: function (idProduccion) {
                seleccionarOpcion($opciones.find(`[data-value="${idProduccion}"]`));
            }
        };
    }

    function configurarComboboxMateriaPrima(inputSelector, idSelector, opcionesSelector) {
        const $busqueda = $(inputSelector);
        const $idMateriaPrima = $(idSelector);
        const $opciones = $(opcionesSelector);
        const $contenedor = $busqueda.closest('.materia-combobox');
        const mensajeSeleccion = 'Seleccione una materia prima de la lista.';
        const normalizar = texto => texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase();

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
            if (!$opcion.length) return;
            $opciones.find('[data-value]').attr('aria-selected', 'false');
            $opcion.attr('aria-selected', 'true');
            $busqueda.val($opcion.text().trim()).get(0).setCustomValidity('');
            $idMateriaPrima.val($opcion.data('value')).trigger('change');
            ocultarOpciones();
        }

        $busqueda.get(0).setCustomValidity(mensajeSeleccion);
        $busqueda.on('focus', function () {
            filtrarOpciones(this.value);
        }).on('input', function () {
            $idMateriaPrima.val('').removeClass('is-valid is-invalid');
            $opciones.find('[data-value]').attr('aria-selected', 'false');
            this.setCustomValidity(mensajeSeleccion);
            filtrarOpciones(this.value);
            $idMateriaPrima.trigger('change');
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
                const idOpcion = `${$opciones.attr('id')}_materia_${$activo.data('value')}`;
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

        $idMateriaPrima.on('change', function () {
            $busqueda.get(0).setCustomValidity(this.value ? '' : mensajeSeleccion);
            validarCampo($busqueda);
        });

        return {
            reset: function () {
                $busqueda.val('').removeClass('is-valid is-invalid').removeAttr('style');
                $busqueda.get(0).setCustomValidity(mensajeSeleccion);
                $idMateriaPrima.val('').removeClass('is-valid is-invalid');
                $opciones.find('[data-value]').attr('aria-selected', 'false');
                filtrarOpciones('');
                ocultarOpciones();
            },
            seleccionar: function (idMateriaPrima) {
                const $opcion = $opciones.find('[data-value]').filter(function () {
                    return String($(this).data('value')) === String(idMateriaPrima);
                }).first();
                seleccionarOpcion($opcion);
            }
        };
    }

    async function confirmarAccion(titulo, texto) {
        return await confirmActionAlert(titulo, texto);
    }

    async function mostrarAlertaInformativa(titulo, texto, icono) {
        return await statusAlert(titulo, texto, icono);
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
                await mostrarAlertaInformativa('¡Éxito!', data.message || 'Guardado correctamente.', 'success');
                if ($modal.length) $modal.modal('hide');
                $form[0].reset();
                if ($form.is($formRegistrarPerdida)) comboboxOrdenRegistro.reset();
                if ($form.is($formEditarPerdida)) comboboxOrdenEdicion.reset();
                if ($form.is($formRegistrarPerdida)) comboboxMateriaRegistro.reset();
                if ($form.is($formEditarPerdida)) comboboxMateriaEdicion.reset();
                actualizarCostoPerdida($form);
                limpiarValidaciones($form);
                tablaPerdidas.ajax.reload();
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

    const $fechaRegistro = $formRegistrarPerdida.find('[name="fecha_de_registro"]');
    $fechaRegistro.attr('min', obtenerFechaLocalISO());

    const comboboxOrdenRegistro = configurarComboboxOrden(
        '#buscar_orden_produccion',
        '#id_produccion',
        '#opciones_orden_produccion'
    );
    const comboboxOrdenEdicion = configurarComboboxOrden(
        '#buscar_edit_orden_produccion',
        '#edit_id_produccion',
        '#opciones_edit_orden_produccion'
    );
    const comboboxMateriaRegistro = configurarComboboxMateriaPrima(
        '#buscar_materia_prima',
        '#id_materia_prima',
        '#opciones_materia_prima'
    );
    const comboboxMateriaEdicion = configurarComboboxMateriaPrima(
        '#buscar_edit_materia_prima',
        '#edit_id_materia_prima',
        '#opciones_edit_materia_prima'
    );

    $('#modalRegistrarPerdida').on('show.bs.modal', function () {
        $fechaRegistro.val(obtenerFechaLocalISO());
        comboboxOrdenRegistro.reset();
        comboboxMateriaRegistro.reset();
    });

    function actualizarCostoPerdida($form) {
        const idProduccion = String($form.find('[name="id_produccion"]').val() || '');
        const idMateriaPrima = String($form.find('[name="id_materia_prima"]').val() || '');
        const cantidadPerdida = Number($form.find('[name="cantidad_perdida"]').val());
        const $orden = $form.find('[data-role="orden-combobox"]');
        const $materiaPrima = $form.find('[data-role="materia-combobox"]');
        const $costoUnitario = $form.find('[data-role="costo-unitario"]');
        const $costoTotal = $form.find('[name="costo_total_perdida"]');

        $orden[0].setCustomValidity(idProduccion ? '' : 'Seleccione una orden de producción de la lista.');
        $materiaPrima[0].setCustomValidity(idMateriaPrima ? '' : 'Seleccione una materia prima de la lista.');
        $costoUnitario.val('');
        $costoTotal.val('');

        if (!idProduccion || !idMateriaPrima) return;

        const consumos = JSON.parse($form.attr('data-consumos') || '[]');
        const consumo = consumos.find(function (item) {
            return String(item.id_produccion) === idProduccion
                && String(item.id_materia_prima) === idMateriaPrima;
        });

        if (!consumo) {
            const mensaje = 'No hay consumo registrado para la orden y materia prima seleccionadas.';
            $orden[0].setCustomValidity(mensaje);
            $materiaPrima[0].setCustomValidity(mensaje);
            return;
        }

        const costoUnitario = Number(consumo.costo_unitario);
        if (!Number.isFinite(costoUnitario) || costoUnitario < 0) {
            $orden[0].setCustomValidity('El costo unitario del consumo no es válido.');
            $materiaPrima[0].setCustomValidity('El costo unitario del consumo no es válido.');
            return;
        }

        $costoUnitario.val(costoUnitario.toFixed(2));
        if (Number.isFinite(cantidadPerdida) && cantidadPerdida > 0) {
            $costoTotal.val((costoUnitario * cantidadPerdida).toFixed(2));
        }
    }

    [$formRegistrarPerdida, $formEditarPerdida].forEach(function ($form) {
        $form.on('change input', '[name="id_produccion"], [name="id_materia_prima"], [name="cantidad_perdida"]', function () {
            actualizarCostoPerdida($form);
            validarCampo($form.find('[data-role="orden-combobox"]'));
            validarCampo($form.find('[data-role="materia-combobox"]'));
        });
    });

    [$formRegistrarPerdida, $formEditarPerdida].forEach(function ($form) {
        $form.on('input change', 'input:not([type="hidden"]), select, textarea', function () {
            validarCampo($(this));
        });
    });

    $formRegistrarPerdida.on('submit', async function (event) {
        event.preventDefault();
        actualizarCostoPerdida($(this));
        if (!validarFormulario($(this))) {
            await mostrarAlertaInformativa('Error', 'Revise los campos inválidos (marcados en rojo).', 'error');
            return;
        }

        const confirmacion = await confirmarAccion('¿Registrar pérdida?', 'Se registrará una nueva pérdida de material.');
        if (!confirmacion.isConfirmed) return;

        enviarFormulario($(this), $('#modalRegistrarPerdida'));
    });

    $formEditarPerdida.on('submit', async function (event) {
        event.preventDefault();
        actualizarCostoPerdida($(this));
        if (!validarFormulario($(this))) {
            await mostrarAlertaInformativa('Error', 'Revise los campos inválidos (marcados en rojo).', 'error');
            return;
        }

        const confirmacion = await confirmarAccion('¿Guardar cambios?', 'Se actualizarán los datos de la pérdida de material.');
        if (!confirmacion.isConfirmed) return;

        enviarFormulario($(this), $('#modalEditarPerdida'));
    });

    $('#formFiltrosReportePerdida').on('submit', async function (event) {
        event.preventDefault();

        const fechaDesde = $('#reporteFechaDesde').val();
        const fechaHasta = $('#reporteFechaHasta').val();
        if (fechaDesde && fechaHasta && fechaDesde > fechaHasta) {
            await mostrarAlertaInformativa('Rango de fechas inválido', 'La fecha inicial no puede ser posterior a la fecha final.', 'warning');
            return;
        }

        const parametros = new URLSearchParams({ accion: 'reporte' });
        if (fechaDesde) parametros.set('fecha_desde', fechaDesde);
        if (fechaHasta) parametros.set('fecha_hasta', fechaHasta);

        const estadoProduccion = $('#reporteEstadoProduccion').val();
        if (estadoProduccion) parametros.set('estado_produccion', estadoProduccion);

        const pestanaReporte = abrirReporteEnPestana(
            `index.php?url=perdidaMaterial/reporte&${parametros.toString()}`
        );
        if (!pestanaReporte) {
            await mostrarAlertaInformativa('Ventana bloqueada', 'Permite las ventanas emergentes para abrir el reporte.', 'warning');
            return;
        }
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalFiltrosReportePerdida')).hide();
    });

    $('#tablaPerdidasMaterial tbody').on('click', '.btnEditarPerdida', function () {
        const $boton = $(this);

        $('#edit_id_perdida_material').val($boton.attr('data-id_perdida') || '');
        $('#edit_cantidad_perdida').val($boton.attr('data-cantidad') || '');
        $('#edit_fecha_de_registro').val($boton.attr('data-fecha') || '');
        comboboxOrdenEdicion.reset();
        comboboxOrdenEdicion.seleccionar($boton.attr('data-id_produccion') || '');
        comboboxMateriaEdicion.reset();
        comboboxMateriaEdicion.seleccionar($boton.attr('data-id_materia_prima') || '');
        $('#edit_costo_total_perdida').val($boton.attr('data-costo-total') || '');
        actualizarCostoPerdida($formEditarPerdida);
        $('#edit_motivo').val($boton.attr('data-motivo') || '');

        validarFormulario($formEditarPerdida);
        $('#modalEditarPerdida').modal('show');
    });
});
