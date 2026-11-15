import $ from 'jquery';
import { lenguajeDataTableEs } from './helpers/datatable-espanol.js';
import { successAlert, errorAlert, confirmDeleteAlert } from './helpers/alerts.js';
import { obtenerPatron, validarCampo, validarLongitud, formatearFechaLatinoamericana } from './helpers/expresiones.js';
import { limpiarValidaciones, soloNumerosTeclado, soloLetrasTeclado } from './helpers/validaciones.js';
import { crearRecortadorFoto } from './helpers/fotodeperfil.js';
import { abrirReporteEnPestana } from './helpers/report-viewer.js';

console.log("Finanzas conectado correctamente");

$(document).ready(function () {
    let verInhabilitados = false;
    let tablaFinanzas;

    function obtenerFechaHoraActual() {
        const ahora = new Date();
        ahora.setMinutes(ahora.getMinutes() - ahora.getTimezoneOffset());
        return ahora.toISOString().slice(0, 16);
    }

    function limpiarFormularioModal(formSelector) {
        const $form = $(formSelector);
        if ($form.length) {
            $form[0].reset();
            limpiarValidaciones($form);
            $form.find('select.form-select').val('').trigger('change');
            $form.find('input[name="referencia"]').prop('disabled', false).attr('required', true);
        }
    }

    // Inicializar Select2 en modales
    if ($('#select_registrar_pedido').length) {
        $('#select_registrar_pedido').select2({
            dropdownParent: $('#modalRegistrarPago'),
            width: '100%',
            placeholder: 'Seleccione un pedido...',
            language: { noResults: () => "No se encontraron pedidos" }
        });
    }

    if ($('#edit_id_pedido').length) {
        $('#edit_id_pedido').select2({
            dropdownParent: $('#modalEditarPago'),
            width: '100%',
            placeholder: 'Seleccione un pedido...',
            language: { noResults: () => "No se encontraron pedidos" }
        });
    }

    // =========================================================================
    // VALIDACIONES EN TIEMPO REAL Y LÓGICA DE EFECTIVO
    // =========================================================================

    // Detectar cambio en método de pago para manejar la referencia obligatoria o deshabilitada si es efectivo
    $(document).on('change', 'select[name="id_metodo_de_pago"]', function () {
        const textoMetodo = $(this).find('option:selected').text().toLowerCase();
        const $form = $(this).closest('form');
        const $inputRef = $form.find('input[name="referencia"]');

        if (textoMetodo.includes('efectivo')) {
            $inputRef.val('').prop('disabled', true).removeAttr('required');
            $inputRef.removeClass('is-invalid is-valid');
            $inputRef.siblings('.feedback-validacion').hide();
        } else {
            $inputRef.prop('disabled', false).attr('required', true);
        }

        validarCampo(this, { requerido: true, mensaje: 'Seleccione un método de pago válido.' });
    });

    // Validación en tiempo real para Pedido
    $(document).on('change', 'select[name="id_pedido"]', function () {
        validarCampo(this, { requerido: true, mensaje: 'Debe seleccionar un pedido.' });
    });

    // Validación en tiempo real para Referencia
    $(document).on('input keypress', 'input[name="referencia"]', function (e) {
        if ($(this).is(':disabled')) return;
        if (e.type === 'keypress') {
            soloNumerosTeclado(e);
        }
        let valorLimpio = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        if (this.value !== valorLimpio) {
            this.value = valorLimpio;
        }

        validarCampo(this, {
            patron: 'referenciaPago',
            mensaje: 'La referencia debe contener exactamente 6 dígitos numéricos.'
        });
    });

    // Validación en tiempo real para Monto
    $(document).on('input keypress', 'input[name="monto_pago"]', function (e) {
        let valor = this.value;
        if (e.type === 'keypress') {
            const charCode = (e.which) ? e.which : e.keyCode;
            if (charCode > 31 && (charCode < 48 || charCode > 57) && charCode !== 46) {
                e.preventDefault();
            }
            if (charCode === 46 && valor.indexOf('.') !== -1) {
                e.preventDefault();
            }
        }

        validarCampo(this, {
            patron: 'moneda',
            mensaje: 'Ingrese un monto válido mayor a 0.'
        });
    });

    // Validación en tiempo real para Fecha de Pago
    $(document).on('change input', 'input[name="fecha_pago"]', function () {
        validarCampo(this, { requerido: true, mensaje: 'Seleccione una fecha de pago válida.' });
    });

    function determinarConfiguracionTabla() {
        if ($('#tablaPagos').length > 0) {
            return {
                url: 'index.php?controller=Finanzas&action=pagos&ajax=listar',
                columns: [
                    { data: 'referencia', render: d => `<strong>${d || 'N/A'}</strong>` },
                    { 
                        data: 'id_pedido', 
                        render: (d, t, r) => `${r.descripcion_pedido || 'Pedido #' + d}<br><small class="text-muted">${(r.nombre_razon_social || '')} ${(r.apellido || '')}</small>` 
                    },
                    { data: 'monto_pago', render: d => `<span class="text-success fw-bold">$${parseFloat(d || 0).toFixed(2)}</span>` },
                    { data: 'nombre_metodo', render: d => d || 'N/A' },
                    // Formato de fecha latinoamericano: Día / Mes / Año
                    { data: 'fecha_pago', render: d => d ? formatearFechaLatinoamericana(d) : '' },
                    { 
                        data: 'estado', 
                        render: d => (d === 'inhabilitado') 
                            ? '<span class="badge bg-danger-subtle text-danger" style="padding: 6px 12px; border-radius: 6px;">Inhabilitado</span>' 
                            : '<span class="badge bg-success-subtle text-success" style="padding: 6px 12px; border-radius: 6px;">Procesado</span>' 
                    },
                    {
                        data: null,
                        className: "text-center",
                        orderable: false,
                        searchable: false,
                        render: function (data) {
                            let esInhabilitado = data.estado === 'inhabilitado';
                            let jsonStr = JSON.stringify(data).replace(/'/g, "&apos;");
                            let btn = '';

                            if (data.comprobante_de_pago) {
                                btn += `<button type="button" class="btn btn-sm btn-outline-secondary btn-ver-comprobante me-1" style="border-radius:8px;" data-ruta="${data.comprobante_de_pago}" title="Ver Comprobante"><i class="bi bi-file-earmark-text-fill"></i></button>`;
                            }

                            btn += `<button type="button" class="btn btn-sm btn-outline-primary btn-editar-pago me-1" style="border-radius:8px;" data-pago='${jsonStr}' title="Editar Pago"><i class="bi bi-pencil-square"></i></button>`;
                            
                            if (!esInhabilitado) {
                                btn += `<button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-pago" style="border-radius:8px;" data-id="${data.id_pago}" title="Inhabilitar Pago"><i class="bi bi-trash-fill"></i></button>`;
                            }
                            return btn;
                        }
                    }
                ]
            };
        } else if ($('#tablaCuentas').length > 0) {
            return {
                url: 'index.php?controller=Finanzas&action=cuentas&ajax=listar',
                columns: [
                    { data: 'id_cuenta', render: d => `#${d || ''}` },
                    { data: 'titular', render: d => `<strong>${d || ''}</strong>` },
                    { data: 'identificador', render: d => d || '' },
                    { data: 'tipo_cuenta', render: d => d || '' },
                    { data: 'nombre_metodo', render: d => d || 'N/A' },
                    { 
                        data: 'estado', 
                        render: d => (d === 'inhabilitado') 
                            ? '<span class="badge bg-danger-subtle text-danger" style="padding: 6px 12px; border-radius: 6px;">Inhabilitado</span>' 
                            : '<span class="badge bg-success-subtle text-success" style="padding: 6px 12px; border-radius: 6px;">Activo</span>' 
                    },
                    {
                        data: null,
                        className: "text-center",
                        orderable: false,
                        searchable: false,
                        render: function (data) {
                            let esInhabilitado = data.estado === 'inhabilitado';
                            let jsonStr = JSON.stringify(data).replace(/'/g, "&apos;");
                            let btn = `<button type="button" class="btn btn-sm btn-outline-primary btn-editar-cuenta me-1" style="border-radius:8px;" data-cuenta='${jsonStr}'><i class="bi bi-pencil-square"></i></button>`;
                            if (!esInhabilitado) {
                                btn += `<button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-cuenta" style="border-radius:8px;" data-id="${data.id_cuenta}"><i class="bi bi-trash-fill"></i></button>`;
                            }
                            return btn;
                        }
                    }
                ]
            };
        } else if ($('#tablaMetodos').length > 0) {
            return {
                url: 'index.php?controller=Finanzas&action=metodos&ajax=listar',
                columns: [
                    { data: 'id_metodo_de_pago', render: d => `#${d || ''}` },
                    { data: 'nombre_metodo_de_pago', render: d => `<strong>${d || ''}</strong>` },
                    { 
                        data: 'estado', 
                        render: d => (d === 'inhabilitado') 
                            ? '<span class="badge bg-danger-subtle text-danger" style="padding: 6px 12px; border-radius: 6px;">Inhabilitado</span>' 
                            : '<span class="badge bg-success-subtle text-success" style="padding: 6px 12px; border-radius: 6px;">Activo</span>' 
                    },
                    {
                        data: null,
                        className: "text-center",
                        orderable: false,
                        searchable: false,
                        render: function (data) {
                            let esInhabilitado = data.estado === 'inhabilitado';
                            let jsonStr = JSON.stringify(data).replace(/'/g, "&apos;");
                            let btn = `<button type="button" class="btn btn-sm btn-outline-primary btn-editar-metodo me-1" style="border-radius:8px;" data-metodo='${jsonStr}'><i class="bi bi-pencil-square"></i></button>`;
                            if (!esInhabilitado) {
                                btn += `<button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-metodo" style="border-radius:8px;" data-id="${data.id_metodo_de_pago}"><i class="bi bi-trash-fill"></i></button>`;
                            }
                            return btn;
                        }
                    }
                ]
            };
        }
        return null;
    }

    let config = determinarConfiguracionTabla();
    if (config) {
        let tablaSelector = $('#tablaPagos').length ? '#tablaPagos' : ($('#tablaCuentas').length ? '#tablaCuentas' : '#tablaMetodos');
        
        tablaFinanzas = $(tablaSelector).DataTable({
            ajax: {
                url: config.url,
                method: 'GET',
                dataSrc: function (json) {
                    let registros = [];
                    if (json && json.success === false) {
                        console.error("Error en respuesta:", json.message);
                    } else if (json && typeof json === 'object') {
                        registros = json.datos || json.pagos || json.cuentas || json.metodos || [];
                    } else if (Array.isArray(json)) {
                        registros = json;
                    }

                    return registros.filter(item => {
                        let estadoItem = item.estado || item.status_cuenta_empresa || item.status_metodo_de_pago || 'activo';
                        let esInhabilitado = estadoItem === 'inhabilitado' || estadoItem === 'inactivo';
                        return verInhabilitados ? esInhabilitado : !esInhabilitado;
                    });
                }
            },
            columns: config.columns,
            language: lenguajeDataTableEs,
            pageLength: 10,
            responsive: true,
            dom: '<"row mb-3"<"col-md-6"l><"col-md-6 text-end"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>'
        });
    }

    window.recargarDatos = function() {
        if (tablaFinanzas) {
            tablaFinanzas.ajax.reload(null, false);
        }
    };

    $('#btnAlternarEstado').on('click', function () {
        verInhabilitados = !verInhabilitados;
        let nombreVistaActual = $('#tituloVista').text();
        if (verInhabilitados) {
            $(this).attr('data-vista', 'inhabilitados').removeClass('btn-outline-secondary').addClass('btn-secondary');
            $('#txtBotonEstado').text('Ver Activos');
            $('#iconoEstado').removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
            $('#tituloVista').text(nombreVistaActual.includes('Inhabilitados') ? nombreVistaActual : nombreVistaActual + ' Inhabilitados');
        } else {
            $(this).attr('data-vista', 'activos').removeClass('btn-secondary').addClass('btn-outline-secondary');
            $('#txtBotonEstado').text('Ver Inhabilitados');
            $('#iconoEstado').removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
            $('#tituloVista').text(nombreVistaActual.replace(' Inhabilitados', ''));
        }
        window.recargarDatos();
    });

    // Abrir modal de filtros de reporte al hacer clic en el botón de generar reporte
    $(document).on('click', '#btnGenerarReportePagos, .btn-generar-reportes', function (e) {
        e.preventDefault();
        const modalEl = document.getElementById('modalFiltroReporte');
        if (modalEl) {
            new bootstrap.Modal(modalEl).show();
        } else {
            errorAlert('Aviso', 'No se encontró la ventana de filtros de reporte.');
        }
    });

    $(document).on('submit', '#formFiltroReporte', function (e) {
        e.preventDefault();

        const urlReporte = new URL(this.getAttribute('action'), window.location.href);
        new FormData(this).forEach((valor, nombre) => {
            if (typeof valor === 'string') {
                urlReporte.searchParams.set(nombre, valor);
            }
        });

        const pestanaReporte = abrirReporteEnPestana(urlReporte.toString());
        if (!pestanaReporte) {
            errorAlert('Ventana bloqueada', 'Permite las ventanas emergentes para abrir el reporte.');
            return;
        }

        const modal = bootstrap.Modal.getInstance(document.getElementById('modalFiltroReporte'));
        if (modal) modal.hide();
    });

    $(document).on('click', '.btn-ver-comprobante', function () {
        const ruta = $(this).data('ruta');
        if (!ruta) {
            errorAlert('Aviso', 'No se encontró el archivo de comprobante.');
            return;
        }

        const extension = ruta.split('.').pop().toLowerCase();
        const $contenedor = $('#contenedorComprobante');
        $contenedor.empty();

        if (extension === 'pdf') {
            $contenedor.append(`<iframe src="${ruta}" style="width:100%; height:500px; border:none; border-radius: 8px;"></iframe>`);
        } else if (['jpg', 'jpeg', 'png', 'webp'].includes(extension)) {
            $contenedor.append(`<img src="${ruta}" alt="Comprobante de Pago" class="img-fluid rounded shadow-sm" style="max-height: 500px; object-fit: contain;">`);
        } else {
            $contenedor.append(`<div class="alert alert-warning w-100">Este formato no se puede previsualizar directamente, pero puedes descargarlo.</div>`);
        }

        $('#btnDescargarComprobante').attr('href', ruta);

        const modalEl = document.getElementById('modalVerComprobante');
        if (modalEl) {
            new bootstrap.Modal(modalEl).show();
        } else {
            window.open(ruta, '_blank');
        }
    });

    $(document).on('click', '.btn-editar-pago', function () {
        const data = $(this).data('pago');
        if (!data) return;
        $('#edit_id_pago').val(data.id_pago);
        $('#edit_id_pedido').val(data.id_pedido).trigger('change');
        $('#edit_monto_pago').val(data.monto_pago);
        $('#edit_id_metodo').val(data.id_metodo_de_pago).trigger('change');
        $('#edit_referencia').val(data.referencia);
        $('#edit_fecha').val(data.fecha_pago ? data.fecha_pago.replace(' ', 'T') : obtenerFechaHoraActual());
        $('#edit_status_pago').val(data.estado || 'procesado');
        new bootstrap.Modal(document.getElementById('modalEditarPago')).show();
    });

    $(document).on('click', '.btn-editar-cuenta', function () {
        const data = $(this).data('cuenta');
        if (!data) return;
        $('#edit_id_cuenta').val(data.id_cuenta);
        $('#edit_id_metodo_pago').val(data.id_metodo_de_pago);
        $('#edit_tipo_cuenta').val(data.tipo_cuenta);
        $('#edit_titular').val(data.titular);
        $('#edit_identificador').val(data.identificador);
        $('#edit_status_cuenta').val(data.estado || 'activo');
        new bootstrap.Modal(document.getElementById('modalEditarCuenta')).show();
    });

    $(document).on('click', '.btn-editar-metodo', function () {
        const data = $(this).data('metodo');
        if (!data) return;
        $('#edit_id_metodo_de_pago').val(data.id_metodo_de_pago);
        $('#edit_nombre_metodo_de_pago').val(data.nombre_metodo_de_pago);
        $('#edit_status_metodo').val(data.estado || 'activo');
        new bootstrap.Modal(document.getElementById('modalEditarMetodo')).show();
    });

    $(document).on('submit', '.finanzas-form', async function (e) {
        e.preventDefault();
        let $formActual = $(this);
        const formData = new FormData(this);

        try {
            const data = await $.ajax({
                url: $formActual.attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json'
            });

            if (data.success) {
                $('.modal.show').each(function() {
                    let modalInstance = bootstrap.Modal.getInstance(this);
                    if (modalInstance) modalInstance.hide();
                });
                limpiarFormularioModal($formActual);
                await successAlert('¡Éxito!', data.message || 'Operación realizada correctamente.');
                window.recargarDatos();
            } else {
                await errorAlert('Error de Validación', data.message || 'No se pudo procesar la solicitud.');
            }
        } catch (error) {
            await errorAlert('Error de Conexión', 'Fallo de comunicación con el servidor.');
        }
    });

    async function ejecutarCambioEstado(id, entidad, nuevoEstado) {
        confirmDeleteAlert(async () => {
            try {
                const data = await $.ajax({
                    url: 'index.php?controller=Finanzas',
                    method: 'POST',
                    data: { accion: 'cambiar_estado', entidad: entidad, id: id, nuevo_estado: nuevoEstado },
                    dataType: 'json'
                });

                if (data.success) {
                    await successAlert('Inhabilitado', data.message || 'El registro ha sido inhabilitado.');
                    window.recargarDatos();
                } else {
                    await errorAlert('Error', data.message || 'No se pudo cambiar el estado.');
                }
            } catch (error) {
                await errorAlert('Error', 'No se pudo contactar al servidor.');
            }
        });
    }

    $(document).on('click', '.btn-eliminar-pago', function() {
        ejecutarCambioEstado($(this).data('id'), 'pago', 'inhabilitado');
    });

    $(document).on('click', '.btn-eliminar-cuenta', function() {
        ejecutarCambioEstado($(this).data('id'), 'cuenta', 'inhabilitado');
    });

    $(document).on('click', '.btn-eliminar-metodo', function() {
        ejecutarCambioEstado($(this).data('id'), 'metodo', 'inhabilitado');
    });
});