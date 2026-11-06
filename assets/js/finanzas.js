import $ from 'jquery';
import { validar } from './helpers/expresiones.js';

$(document).ready(function () {
    let verInhabilitados = false;
    let tablaFinanzas;

    function obtenerFechaHoraActual() {
        const ahora = new Date();
        ahora.setMinutes(ahora.getMinutes() - ahora.getTimezoneOffset());
        return ahora.toISOString().slice(0, 16);
    }

    function determinarConfiguracionTabla() {
        if ($('#tablaPagos').length > 0) {
            return {
                url: 'index.php?controller=Finanzas&action=pagos&ajax=listar',
                arrayKey: 'datos',
                columns: [
                    { data: 'referencia', render: d => `<strong>${d || 'N/A'}</strong>` },
                    { data: 'id_pedido', render: (d, t, r) => `Pedido #${d || ''}<br><small class="text-muted">${(r.nombre_razon_social || '')} ${(r.apellido || '')}</small>` },
                    { data: 'monto_pago', render: d => `<span class="text-success fw-bold">$${parseFloat(d || 0).toFixed(2)}</span>` },
                    { data: 'nombre_metodo', render: d => d || 'N/A' },
                    { data: 'fecha_pago', render: d => d ? d.replace('T', ' ') : '' },
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
                            let btn = `<button type="button" class="btn btn-sm btn-outline-primary btn-editar-pago me-1" style="border-radius:8px;" data-pago='${jsonStr}'><i class="bi bi-pencil-square"></i></button>`;
                            if (!esInhabilitado) {
                                btn += `<button type="button" class="btn btn-sm btn-outline-danger" style="border-radius:8px;" onclick="cambiarEstado(${data.id_pago}, 'pago', 'inhabilitado')"><i class="bi bi-trash-fill"></i></button>`;
                            }
                            return btn;
                        }
                    }
                ]
            };
        } else if ($('#tablaCuentas').length > 0) {
            return {
                url: 'index.php?controller=Finanzas&action=cuentas&ajax=listar',
                arrayKey: 'datos',
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
                                btn += `<button type="button" class="btn btn-sm btn-outline-danger" style="border-radius:8px;" onclick="cambiarEstado(${data.id_cuenta}, 'cuenta', 'inhabilitado')"><i class="bi bi-trash-fill"></i></button>`;
                            }
                            return btn;
                        }
                    }
                ]
            };
        } else if ($('#tablaMetodos').length > 0) {
            return {
                url: 'index.php?controller=Finanzas&action=metodos&ajax=listar',
                arrayKey: 'datos',
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
                                btn += `<button type="button" class="btn btn-sm btn-outline-danger" style="border-radius:8px;" onclick="cambiarEstado(${data.id_metodo_de_pago}, 'metodo', 'inhabilitado')"><i class="bi bi-trash-fill"></i></button>`;
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
                        registros = json.datos || json.pagos || [];
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
            language: {
                "sProcessing": "Procesando...",
                "sLengthMenu": "Mostrar _MENU_ registros",
                "sZeroRecords": "No se encontraron resultados",
                "sEmptyTable": "Ningún dato disponible en esta tabla",
                "sInfo": "Mostrando del _START_ al _END_ de _TOTAL_ registros",
                "sInfoEmpty": "Mostrando del 0 al 0 de 0 registros",
                "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
                "sSearch": "Buscar:",
                "oPaginate": { "sFirst": "Primero", "sLast": "Último", "sNext": "Siguiente", "sPrevious": "Anterior" }
            },
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

    $(document).on('click', '.btn-editar-pago', function () {
        const data = $(this).data('pago');
        if (!data) return;
        $('#edit_id_pago').val(data.id_pago);
        $('#edit_id_pedido').val(data.id_pedido).trigger('change');
        $('#edit_monto_pago').val(data.monto_pago);
        $('#edit_id_metodo').val(data.id_metodo_de_pago);
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
                $formActual[0].reset();
                $('.modal.show').each(function() {
                    let modalInstance = bootstrap.Modal.getInstance(this);
                    if (modalInstance) modalInstance.hide();
                });
                await Swal.fire({ icon: 'success', title: '¡Éxito!', text: data.message, timer: 1500, showConfirmButton: false });
                window.recargarDatos();
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Error en el servidor.' });
            }
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Fallo de conexión con el servidor.' });
        }
    });

    window.cambiarEstado = async function(id, entidad, nuevoEstado) {
        const titulo = (nuevoEstado === 'inhabilitado') ? '¿Inactivar registro?' : '¿Habilitar registro?';
        const result = await Swal.fire({
            title: titulo,
            text: 'Esta acción cambiará el estado del registro.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: (nuevoEstado === 'inhabilitado') ? '#d33' : '#28a745',
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar'
        });

        if (result.isConfirmed) {
            try {
                const data = await $.ajax({
                    url: 'index.php?controller=Finanzas',
                    method: 'POST',
                    data: { accion: 'cambiar_estado', entidad: entidad, id: id, nuevo_estado: nuevoEstado },
                    dataType: 'json'
                });

                if (data.success) {
                    await Swal.fire({ title: '¡Éxito!', text: data.message, icon: 'success', timer: 1500, showConfirmButton: false });
                    window.recargarDatos();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'No se pudo contactar al servidor', 'error');
            }
        }
    };
});