import $ from 'jquery';
import { lenguajeDataTableEs } from './helpers/datatable-espanol.js';
import { crearMarkupSkeletonDataTable } from './helpers/datatable-skeleton.js';
import { successAlert, errorAlert, confirmDeleteAlert } from './helpers/alerts.js';
import { validarCampo } from './helpers/expresiones.js';
import { limpiarValidaciones } from './helpers/validaciones.js';

console.log("Módulo de Métodos de Pago conectado correctamente");

$(document).ready(function () {
    let verInhabilitados = false;
    let tablaMetodos;

    function limpiarFormularioModal(formSelector) {
        const $form = $(formSelector);
        if ($form.length) {
            $form[0].reset();
            limpiarValidaciones($form);
        }
    }

    $(document).on('input', 'input[name="nombre_metodo_de_pago"]', function () {
        validarCampo(this, { requerido: true, mensaje: 'El nombre del método es obligatorio.' });
    });

    if ($('#tablaMetodos').length > 0) {
        tablaMetodos = $('#tablaMetodos').DataTable({
            processing: true,
            ajax: {
                url: 'index.php?controller=Finanzas&action=metodos&ajax=listar',
                method: 'GET',
                dataSrc: function (json) {
                    let registros = [];
                    if (json && json.success === false) {
                        console.error("Error en respuesta:", json.message);
                    } else if (json && typeof json === 'object') {
                        registros = json.datos || json.metodos || [];
                    } else if (Array.isArray(json)) {
                        registros = json;
                    }

                    return registros.filter(item => {
                        let estadoItem = item.estado || item.status_metodo_de_pago || 'activo';
                        let esInhabilitado = estadoItem === 'inhabilitado' || estadoItem === 'inactivo';
                        return verInhabilitados ? esInhabilitado : !esInhabilitado;
                    });
                }
            },
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
            ],
            language: {
                ...lenguajeDataTableEs,
                processing: crearMarkupSkeletonDataTable(4)
            },
            pageLength: 10,
            responsive: true,
            dom: '<"row mb-3"<"col-md-6"l><"col-md-6 text-end"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>'
        });
    }

    window.recargarDatos = function() {
        if (tablaMetodos) {
            tablaMetodos.ajax.reload(null, false);
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

    $(document).on('click', '.btn-eliminar-metodo', function() {
        let idMetodo = $(this).data('id');
        confirmDeleteAlert(async () => {
            try {
                const data = await $.ajax({
                    url: 'index.php?controller=Finanzas',
                    method: 'POST',
                    data: { accion: 'cambiar_estado', entidad: 'metodo', id: idMetodo, nuevo_estado: 'inhabilitado' },
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
    });
});