$(document).ready(function () {
    let tablaClientesDataTable = null;
    let todosLosClientes = []; 
    let verEliminados = false;

    console.log("Inicialización: cliente.js listo con depuración inteligente.");

    // Expresiones regulares de validación sintáctica
    const regexDoc = /^[VvEeJjGgCc0-9][- ]?[0-9]{7,10}$/;
    const regexTexto = /^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s\.]{3,50}$/;
    const regexCorreo = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    const regexTelefono = /^(0414|0424|0412|0416|02)[0-9]{7}$/;

    // Bloqueo de teclado nativo
    function soloNumerosTeclado(e) {
        if (['Backspace', 'Tab', 'ArrowLeft', 'ArrowRight', 'Delete', 'Enter'].includes(e.key)) return;
        if (!/^[0-9]$/.test(e.key)) e.preventDefault();
    }

    function soloLetrasTeclado(e) {
        if (['Backspace', 'Tab', 'ArrowLeft', 'ArrowRight', 'Delete', 'Enter', '.'].includes(e.key)) return;
        if (!/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]$/.test(e.key)) e.preventDefault();
    }

    // Renderizador visual de alertas individuales debajo de los inputs
    function validarCampo(input, regex, mensajeError) {
        if (!input || input.length === 0) return false;
        let domInput = input[0];
        let feedback = domInput.nextElementSibling;
        
        if (!feedback || !feedback.classList.contains("feedback-validación")) {
            feedback = document.createElement("small");
            feedback.classList.add("feedback-validación", "form-text");
            domInput.parentNode.appendChild(feedback);
        }

        if (regex.test(input.val().trim())) {
            input.removeClass("is-invalid").addClass("is-valid");
            feedback.textContent = "Campo válido";
            feedback.style.color = "#198754";
            return true;
        } else {
            input.removeClass("is-valid").addClass("is-invalid");
            feedback.textContent = mensajeError;
            feedback.style.color = "#dc3545";
            return false;
        }
    }

    // Asignación de filtros de teclado en tiempo real
    $("#nombre_razon_social, #apellido, #edit_nombre_razon_social, #edit_apellido").on("keydown", soloLetrasTeclado);
    $("#numero_de_documento, #telefono, #edit_numero_de_documento, #edit_telefono").on("keydown", soloNumerosTeclado);

    // Accesibilidad y enfoque automático en Modales Bootstrap 5
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

    // Carga de Datos con detector inteligente de respuestas HTML/JSON
    function cargarClientes() {
        $.ajax({
            url: 'index.php?url=cliente/listar&ajax=listar',
            type: 'GET',
            dataType: 'text', 
            success: function (respuestaCruda) {
                let data;
                try {
                    data = JSON.parse(respuestaCruda);
                } catch (e) {
                    console.error("%c⚠️ ERROR DETECTADO DESDE PHP (No es un JSON válido):", "color: #ff3333; font-weight: bold; font-size: 14px;");
                    console.log(respuestaCruda);
                    todosLosClientes = [];
                    renderizarTabla(verEliminados ? 'eliminados' : 'activos');
                    return;
                }

                if (data && data.success === false) {
                    console.error("PHP rechazó la consulta explícitamente:", data.message);
                    todosLosClientes = [];
                    renderizarTabla(verEliminados ? 'eliminados' : 'activos');
                    return;
                }

                // ADAPTACIÓN: Si es un objeto que contiene la propiedad 'clientes', extraemos su matriz interna
                if (data && typeof data === 'object' && Array.isArray(data.clientes)) {
                    todosLosClientes = data.clientes;
                } else if (Array.isArray(data)) {
                    todosLosClientes = data;
                } else {
                    console.error("Error estructural interno inesperado. Tipo:", typeof data);
                    todosLosClientes = []; 
                }

                renderizarTabla(verEliminados ? 'eliminados' : 'activos');
            },
            error: function (error) { 
                console.error("Error crítico de comunicación HTTP de red:", error); 
                todosLosClientes = [];
                renderizarTabla(verEliminados ? 'eliminados' : 'activos');
            }
        });
    }

    window.recargarTablaClientes = cargarClientes;

    // Pintar los datos en el DOM y mapear DataTables
    function renderizarTabla(modo) {
        const tbody = $('#tbodyClientes');
        
        if ($.fn.DataTable.isDataTable('#tablaClientes')) {
            tablaClientesDataTable.destroy();
        }
        tbody.empty();

        if (!Array.isArray(todosLosClientes)) {
            todosLosClientes = [];
        }

        const filtrados = todosLosClientes.filter(cliente => {
            let esActivo = cliente.status_cliente === 'activo' || parseInt(cliente.estado) === 1 || cliente.status_cliente === '1';
            return modo === 'eliminados' ? !esActivo : esActivo;
        });

        if (filtrados.length > 0) {
            filtrados.forEach(cliente => {
                let tipoDoc = cliente.tipo_de_documento || 'Natural';
                let documentoFormateado = '';
                let numLimpio = cliente.numero_de_documento || '';
                
                if (/^[VvEeJjGgCc]/.test(numLimpio)) {
                    documentoFormateado = numLimpio.toUpperCase();
                } else {
                    let inicial = tipoDoc.toLowerCase() === 'extranjero' ? 'E' : ((tipoDoc.toLowerCase() === 'jurídico' || tipoDoc.toLowerCase() === 'juridico') ? 'J' : 'V');
                    documentoFormateado = `${inicial}-${numLimpio}`;
                }

                let esActivo = cliente.status_cliente === 'activo' || parseInt(cliente.estado) === 1 || cliente.status_cliente === '1';
                let estadoBadge = esActivo
                    ? '<span class="badge bg-success-subtle text-success" style="padding: 6px 12px; border-radius: 6px;">Activo</span>' 
                    : '<span class="badge bg-danger-subtle text-danger" style="padding: 6px 12px; border-radius: 6px;">Inactivo</span>';

                let btnAcciones = `<button class="btn btn-sm btn-outline-primary btn-editar me-1" style="border-radius:8px;" data-cliente='${JSON.stringify(cliente).replace(/'/g, "&apos;")}'><i class="bi bi-pencil-square"></i></button>`;
                
                if (esActivo) {
                    btnAcciones += `<button class="btn btn-sm btn-outline-danger btn-eliminar" style="border-radius:8px;" data-id="${cliente.id_cliente}" data-nombre="${cliente.nombre_razon_social}"><i class="bi bi-trash3"></i></button>`;
                }

                tbody.append(`<tr>
                    <td class="px-4"><div class="fw-bold text-dark">${documentoFormateado}</div></td>
                    <td class="px-4"><div class="fw-semibold text-secondary">${cliente.nombre_razon_social} ${cliente.apellido || ''}</div></td>
                    <td class="px-4">
                        <div class="small text-dark"><i class="bi bi-telephone text-muted me-1"></i> ${cliente.telefono || 'N/A'}</div>
                        <div class="small text-muted"><i class="bi bi-envelope text-muted me-1"></i> ${cliente.correo || 'N/A'}</div>
                    </td>
                    <td class="px-4">${estadoBadge}</td>
                    <td class="px-4 text-center">${btnAcciones}</td>
                </tr>`);
            });
        }

        tablaClientesDataTable = $('#tablaClientes').DataTable({
            language: { 
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                emptyTable: "No hay registros en esta vista",
                zeroRecords: "No se encontraron resultados"
            },
            pageLength: 10, 
            responsive: true, 
            ordering: false,
            dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>'
        });
    }

    // Manejador del botón para alternar vistas (Activos / Inhabilitados)
    $('#btnAlternarEstado').on('click', function() {
        verEliminados = !verEliminados;
        if (verEliminados) {
            $(this).attr('data-vista', 'eliminados').removeClass('btn-outline-secondary').addClass('btn-secondary');
            $('#txtBotonEstado').text('Ver Activos');
            $('#iconoEstado').removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
            $('#tituloVista').text('Clientes Inhabilitados');
            renderizarTabla('eliminados');
        } else {
            $(this).attr('data-vista', 'activos').removeClass('btn-secondary').addClass('btn-outline-secondary');
            $('#txtBotonEstado').text('Ver Inhabilitados');
            $('#iconoEstado').removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
            $('#tituloVista').text('Gestión de Clientes');
            renderizarTabla('activos');
        }
    });

    // Envío del Formulario de Registro
    $("#formRegistrarCliente").on("submit", function (e) {
        e.preventDefault(); 

        let vDoc = validarCampo($("#numero_de_documento"), regexDoc, "Revise este campo.");
        let vNom = validarCampo($("#nombre_razon_social"), regexTexto, "Revise este campo.");
        let vApe = $("#apellido").val().trim() === "" ? true : validarCampo($("#apellido"), regexTexto, "Revise este campo.");
        let vCor = validarCampo($("#correo"), regexCorreo, "Revise este campo.");
        let vTel = validarCampo($("#telefono"), regexTelefono, "Revise este campo.");

        if (!vDoc || !vNom || !vApe || !vCor || !vTel || $("#numero_de_documento").hasClass("is-invalid")) {
            Swal.fire({ icon: 'error', title: 'Campos Inválidos', text: 'Por favor complete correctamente las casillas resaltadas.', confirmButtonColor: '#dc3545' });
            return;
        }
        
        const datosEnviados = $(this).serializeArray();

        $.ajax({
            url: 'index.php?url=cliente/listar',
            type: 'POST',
            data: $.param(datosEnviados),
            dataType: 'json',
            success: function(data) {
                if(data.success) {
                    Swal.fire({ icon: 'success', title: 'Guardado', text: data.message, confirmButtonColor: '#10b981' });
                    $("#formRegistrarCliente")[0].reset();
                    $("#formRegistrarCliente").find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
                    bootstrap.Modal.getInstance(document.getElementById('modalRegistrarCliente')).hide();
                    cargarClientes();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error de Validación', text: data.message, confirmButtonColor: '#dc3545' });
                }
            },
            error: function(err) { 
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error interno o de comunicación con el servidor.', confirmButtonColor: '#dc3545' }); 
            }
        });
    });

    // Apertura e inyección de datos al Modal de Edición
    $(document).on('click', '.btn-editar', function() {
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

    // Envío del Formulario de Edición y Cambio de Estatus
    $("#formEditarCliente").on("submit", function (e) {
        e.preventDefault(); 

        let inputDoc = $("#edit_numero_de_documento");
        let idCliente = $('#edit_id_cliente').val();

        let vDoc = validarCampo(inputDoc, regexDoc, "Documento inválido.");
        let vNom = validarCampo($("#edit_nombre_razon_social"), regexTexto, "Revise este campo.");
        let vApe = $("#edit_apellido").val().trim() === "" ? true : validarCampo($("#edit_apellido"), regexTexto, "Revise este campo.");
        let vCor = validarCampo($("#edit_correo"), regexCorreo, "Revise este campo.");
        let vTel = validarCampo($("#edit_telefono"), regexTelefono, "Revise este campo.");

        if (!vDoc || !vNom || !vApe || !vCor || !vTel) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Campos inválidos en el formulario de edición.', confirmButtonColor: '#dc3545' });
            return;
        }

        let numDoc = inputDoc.val().trim();
        let feedback = inputDoc[0].nextElementSibling;
        const datosModificados = $(this).serializeArray();

        // Validación de duplicados
        $.ajax({
            url: 'index.php?url=cliente/listar',
            type: "POST",
            data: { numero_de_documento: numDoc },
            dataType: "json",
            success: function (data) {
                if (data.duplicado && data.id_cliente_duplicado != idCliente) {
                    inputDoc.removeClass("is-valid").addClass("is-invalid");
                    if (feedback) feedback.textContent = "Documento duplicado.";
                    Swal.fire({ icon: 'error', title: 'Documento Duplicado', text: 'Esta cédula ya pertenece a otro cliente.', confirmButtonColor: '#dc3545' });
                } else {
                    $.ajax({
                        url: 'index.php?url=cliente/listar',
                        type: 'POST',
                        data: $.param(datosModificados),
                        dataType: 'json',
                        success: function(response) {
                            if(response.success) {
                                Swal.fire({ icon: 'success', title: 'Actualizado', text: response.message, confirmButtonColor: '#10b981' });
                                bootstrap.Modal.getInstance(document.getElementById('modalEditarCliente')).hide();
                                cargarClientes();
                            } else {
                                Swal.fire({ icon: 'error', title: 'Error', text: response.message, confirmButtonColor: '#dc3545' });
                            }
                        }
                    });
                }
            }
        });
    });

    // Botón Inhabilitar directo de la fila activa
    $(document).on('click', '.btn-eliminar', function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        
        Swal.fire({
            title: '¿Inhabilitar cliente?',
            text: `¿Cambiar el estado de "${nombre}" a inactivo?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Inhabilitar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `index.php?url=cliente/listar&accion=eliminar&id=${id}`,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            Swal.fire({ icon: 'success', title: 'Inhabilitado', text: data.message, confirmButtonColor: '#10b981' });
                            cargarClientes();
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#dc3545' });
                        }
                    }
                });
            }
        });
    });

    cargarClientes();
});