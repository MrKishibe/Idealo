import $ from 'jquery';
import { soloNumerosTeclado, soloLetrasTeclado, validarCampoNombrePatron } from './helpers/validaciones.js';
import { lenguajeDataTableEs } from './helpers/datatable-espanol.js';
import { successAlert, errorAlert, confirmDeleteAlert } from './helpers/alerts.js';

// DEJEN DE TOCAR EL CODIGO AJENO POR FAVOR

$(document).ready(function () {
    let verEliminados = false;
    let debeReiniciarModal = false;

    const urlControlador = 'index.php?url=cliente/listar';

    // Inicialización del DataTable con ordenamiento activo y flechitas
    let tablaClientesDataTable = $('#tablaClientes').DataTable({
        ajax: {
            url: `${urlControlador}&ajax=listar`,
            method: 'GET',
            data: { 'Consultar': 'yes' },
            dataSrc: function (json) {
                let clientes = [];
                if (json && json.success === false) {
                    console.error("PHP rechazó la consulta explícitamente:", json.message);
                } else if (json && typeof json === 'object' && Array.isArray(json.clientes)) {
                    clientes = json.clientes;
                } else if (Array.isArray(json)) {
                    clientes = json;
                }

                // Filtrar dinámicamente entre activos e inhabilitados según la vista seleccionada
                return clientes.filter(cliente => {
                    let esActivo = cliente.status_cliente === 'activo' || parseInt(cliente.estado) === 1 || cliente.status_cliente === '1';
                    return verEliminados ? !esActivo : esActivo;
                });
            }
        },
        columns: [
            {
                data: 'numero_de_documento',
                type: 'string', // Orden alfabético/numérico activo por documento
                className: "px-4",
                render: function (data, type, row) {
                    let tipoDoc = row.tipo_de_documento || 'Natural';
                    let numLimpio = data || '';
                    let documentoFormateado = '';

                    if (/^[VvEeJjGgCc]/.test(numLimpio)) {
                        documentoFormateado = numLimpio.toUpperCase();
                    } else {
                        let inicial = tipoDoc.toLowerCase() === 'extranjero' ? 'E' : ((tipoDoc.toLowerCase() === 'jurídico' || tipoDoc.toLowerCase() === 'juridico') ? 'J' : 'V');
                        documentoFormateado = `${inicial}-${numLimpio}`;
                    }
                    return `<div class="fw-bold text-dark">${documentoFormateado}</div>`;
                }
            },
            {
                data: 'nombre_razon_social',
                type: 'string', // Orden alfabético activo por nombre o razón social
                className: "px-4",
                render: function (data, type, row) {
                    return `<div class="fw-semibold text-secondary">${data || ''} ${row.apellido || ''}</div>`;
                }
            },
            {
                data: 'telefono',
                type: 'string', // Orden alfabético por contacto
                className: "px-4",
                render: function (data, type, row) {
                    return `
                        <div class="small text-dark"><i class="bi bi-telephone text-muted me-1"></i> ${data || 'N/A'}</div>
                        <div class="small text-muted"><i class="bi bi-envelope text-muted me-1"></i> ${row.correo || 'N/A'}</div>
                    `;
                }
            },
            {
                data: 'status_cliente',
                type: 'string', // Orden activo por estado
                className: "px-4",
                render: function (data, type, row) {
                    let esActivo = data === 'activo' || parseInt(row.estado) === 1 || data === '1';
                    return esActivo
                        ? '<span class="badge bg-success-subtle text-success" style="padding: 6px 12px; border-radius: 6px;">Activo</span>'
                        : '<span class="badge bg-danger-subtle text-danger" style="padding: 6px 12px; border-radius: 6px;">Inactivo</span>';
                }
            },
            {
                data: null,
                className: "px-4 text-center",
                orderable: false, // Acciones sin ordenamiento
                searchable: false,
                render: function (data) {
                    let esActivo = data.status_cliente === 'activo' || parseInt(data.estado) === 1 || data.status_cliente === '1';
                    let clienteJson = JSON.stringify(data).replace(/'/g, "&apos;");
                    
                    let btnAcciones = `<button type="button" class="btn btn-sm btn-outline-primary btn-editar me-1" style="border-radius:8px;" data-cliente='${clienteJson}'><i class="bi bi-pencil-square"></i></button>`;

                    if (esActivo) {
                        btnAcciones += `<button type="button" class="btn btn-sm btn-outline-danger btn-eliminar" style="border-radius:8px;" data-id="${data.id_cliente}" data-nombre="${data.nombre_razon_social}"><i class="bi bi-trash3"></i></button>`;
                    }
                    return btnAcciones;
                }
            }
        ],
        language: lenguajeDataTableEs,
        pageLength: 10,
        responsive: true,
        ordering: true, 
        order: [[0, 'asc']], 
        dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>'
    });

    // Función global para recargar la tabla mediante Ajax de DataTables
    window.recargarTablaClientes = function () {
        tablaClientesDataTable.ajax.reload(null, false);
    };

    function limpiarFormularioModal(formId) {
        const $form = $(formId);
        if ($form.length) {
            $form[0].reset();
            $form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
            $form.find('.feedback-validación').remove();
        }
    }

    function apiGuardarCliente(datosFormulario) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: urlControlador,
                type: 'POST',
                data: $.param(datosFormulario),
                dataType: 'json',
                success: (data) => resolve(data),
                error: (err) => reject(err)
            });
        });
    }

    function apiVerificarDuplicado(numDoc) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: urlControlador,
                type: 'POST',
                data: { numero_de_documento: numDoc },
                dataType: 'json',
                success: (data) => resolve(data),
                error: (err) => reject(err)
            });
        });
    }

    function apiInhabilitarCliente(id) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: `${urlControlador}&accion=eliminar&id=${id}`,
                type: 'GET',
                dataType: 'json',
                success: (data) => resolve(data),
                error: (err) => reject(err)
            });
        });
    }

    $("#nombre_razon_social, #apellido, #edit_nombre_razon_social, #edit_apellido").on("keydown", soloLetrasTeclado);
    $("#numero_de_documento, #telefono, #edit_numero_de_documento, #edit_telefono").on("keydown", soloNumerosTeclado);

    const setupAccesibilidadModal = (idModal, idInputFoco) => {
        const modalEl = document.getElementById(idModal);
        if (modalEl) {
            modalEl.addEventListener('shown.bs.modal', () => {
                const input = document.getElementById(idInputFoco);
                if (input) input.focus();
            });
            modalEl.addEventListener('hide.bs.modal', () => {
                if (document.activeElement) {
                    document.activeElement.blur();
                }
            });
        }
    };

    setupAccesibilidadModal('modalRegistrarCliente', 'numero_de_documento');
    setupAccesibilidadModal('modalEditarCliente', 'edit_nombre_razon_social');

    $(document).on('click', '#modalRegistrarCliente .btn-cancelar-modal', function () {
        debeReiniciarModal = true;
        const modalInstance = bootstrap.Modal.getInstance(document.getElementById('modalRegistrarCliente'));
        if (modalInstance) modalInstance.hide();
    });

    $('#modalRegistrarCliente').on('hidden.bs.modal', function () {
        if (debeReiniciarModal) {
            limpiarFormularioModal('#formRegistrarCliente');
            debeReiniciarModal = false;
        }
    });

    $('#modalEditarCliente').on('hidden.bs.modal', function () {
        limpiarFormularioModal('#formEditarCliente');
    });

    $('#btnAlternarEstado').on('click', function () {
        verEliminados = !verEliminados;
        if (verEliminados) {
            $(this).attr('data-vista', 'eliminados').removeClass('btn-outline-secondary').addClass('btn-secondary');
            $('#txtBotonEstado').text('Ver Activos');
            $('#iconoEstado').removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
            $('#tituloVista').text('Clientes Inhabilitados');
        } else {
            $(this).attr('data-vista', 'activos').removeClass('btn-secondary').addClass('btn-outline-secondary');
            $('#txtBotonEstado').text('Ver Inhabilitados');
            $('#iconoEstado').removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
            $('#tituloVista').text('Gestión de Clientes');
        }
        window.recargarTablaClientes();
    });

    $("#formRegistrarCliente").on("submit", async function (e) {
        e.preventDefault();

        let vDoc = validarCampoNombrePatron($("#numero_de_documento"), "documentoVenezolano", "Revise este campo.");
        let vNom = validarCampoNombrePatron($("#nombre_razon_social"), "razonSocial", "Revise este campo.");
        let vApe = $("#apellido").val().trim() === "" ? true : validarCampoNombrePatron($("#apellido"), "apellido", "Revise este campo.");
        let vCor = validarCampoNombrePatron($("#correo"), "correo", "Revise este campo.");
        let vTel = validarCampoNombrePatron($("#telefono"), "telefonoVenezuela", "Revise este campo.");

        if (!vDoc || !vNom || !vApe || !vCor || !vTel || $("#numero_de_documento").hasClass("is-invalid")) {
            await errorAlert('Campos Inválidos', 'Por favor complete correctamente las casillas resaltadas.');
            return;
        }

        const datosEnviados = $(this).serializeArray();

        try {
            const data = await apiGuardarCliente(datosEnviados);
            if (data.success) {
                await successAlert('Guardado', data.message);
                debeReiniciarModal = true;
                bootstrap.Modal.getInstance(document.getElementById('modalRegistrarCliente')).hide();
                window.recargarTablaClientes();
            } else {
                await errorAlert('Error de Validación', data.message);
            }
        } catch (err) {
            await errorAlert('Error', 'Error interno o de comunicación con el servidor.');
        }
    });

    $('#tablaClientes tbody').on('click', '.btn-editar', function () {
        const data = $(this).data('cliente');
        if (!data) return;

        const idCliente = data.id_cliente || data.id;
        if (!idCliente) return;

        $('#edit_id_cliente').val(idCliente);
        $('#edit_tipo_de_documento').val(data.tipo_de_documento);
        $('#edit_numero_de_documento').val(data.numero_de_documento).removeClass("is-invalid is-valid");
        $('#edit_telefono').val(data.telefono).removeClass("is-invalid is-valid");
        $('#edit_nombre_razon_social').val(data.nombre_razon_social).removeClass("is-invalid is-valid");
        $('#edit_apellido').val(data.apellido).removeClass("is-invalid is-valid");
        $('#edit_direccion').val(data.direccion);
        $('#edit_correo').val(data.correo).removeClass("is-invalid is-valid");

        let esInactivo = data.status_cliente === 'inactivo' || parseInt(data.estado) === 0 || data.status_cliente === '0';
        $('#edit_status_cliente').val(esInactivo ? 'inactivo' : 'activo');

        new bootstrap.Modal(document.getElementById('modalEditarCliente')).show();
    });

    $("#formEditarCliente").on("submit", async function (e) {
        e.preventDefault();

        let inputDoc = $("#edit_numero_de_documento");
        let idCliente = $('#edit_id_cliente').val();

        let vDoc = validarCampoNombrePatron(inputDoc, "documentoVenezolano", "Documento inválido.");
        let vNom = validarCampoNombrePatron($("#edit_nombre_razon_social"), "razonSocial", "Revise este campo.");
        let vApe = $("#edit_apellido").val().trim() === "" ? true : validarCampoNombrePatron($("#edit_apellido"), "apellido", "Revise este campo.");
        let vCor = validarCampoNombrePatron($("#edit_correo"), "correo", "Revise este campo.");
        let vTel = validarCampoNombrePatron($("#edit_telefono"), "telefonoVenezuela", "Revise este campo.");

        if (!vDoc || !vNom || !vApe || !vCor || !vTel) {
            await errorAlert('Error', 'Campos inválidos en el formulario de edición.');
            return;
        }

        let numDoc = inputDoc.val().trim();
        let feedback = inputDoc[0].nextElementSibling;
        const datosModificados = $(this).serializeArray();

        try {
            const dataDup = await apiVerificarDuplicado(numDoc);

            if (dataDup.duplicado && dataDup.id_cliente_duplicado != idCliente) {
                inputDoc.removeClass("is-valid").addClass("is-invalid");
                if (feedback) feedback.textContent = "Documento duplicado.";
                await errorAlert('Documento Duplicado', 'Esta cédula ya pertenece a otro cliente.');
            } else {
                const response = await apiGuardarCliente(datosModificados);
                if (response.success) {
                    await successAlert('Actualizado', response.message);
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarCliente')).hide();
                    window.recargarTablaClientes();
                } else {
                    await errorAlert('Error', response.message);
                }
            }
        } catch (err) {
            await errorAlert('Error', 'Error interno o de comunicación con el servidor.');
        }
    });

    $('#tablaClientes tbody').on('click', '.btn-eliminar', async function () {
        const id = $(this).data('id');

        confirmDeleteAlert(async () => {
            try {
                const data = await apiInhabilitarCliente(id);
                if (data.success) {
                    await successAlert('Inhabilitado', data.message);
                    window.recargarTablaClientes();
                } else {
                    await errorAlert('Error', data.message);
                }
            } catch (err) {
                await errorAlert('Error', 'Error al procesar la inhabilitación.');
            }
        });
    });
});