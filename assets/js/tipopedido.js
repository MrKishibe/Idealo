$(document).ready(function () {
    let tablaDataTable = null;
    let todosLosPedidos = [];
    let verEliminados = false;

    const urlModulo = 'index.php?controller=tipoPedido&action=listar';

    // Objeto de idioma en español configurado manualmente
    const lenguajeEspanolDataTables = {
        processing:     "Procesando...",
        search:         "Buscar:",
        lengthMenu:     "Mostrar _MENU_ registros",
        info:           "Mostrando del _START_ al _END_ de _TOTAL_ registros",
        infoEmpty:      "Mostrando 0 de 0 registros",
        infoFiltered:   "(filtrado de _MAX_ registros en total)",
        infoPostFix:    "",
        loadingRecords: "Cargando...",
        zeroRecords:    "No se encontraron coincidencias",
        emptyTable:     "No hay registros en esta vista",
        paginate: {
            first:      "Primero",
            previous:   "Anterior",
            next:       "Siguiente",
            last:       "Último"
        },
        aria: {
            sortAscending:  ": Activar para ordenar la columna de manera ascendente",
            sortDescending: ": Activar para ordenar la columna de manera descendente"
        }
    };

    async function solicitarAPI(url, opciones = {}) {
        try {
            return await $.ajax({
                url: url,
                type: opciones.method || 'GET',
                data: opciones.data || {},
                dataType: 'json'
            });
        } catch (xhr) {
            console.error('Error en la petición AJAX:', xhr.statusText || xhr);
            throw new Error(xhr.responseText || 'Error de comunicación');
        }
    }

    function escaparHTML(texto) {
        return $('<div>').text(texto ?? '').html();
    }

    $(document).on('input', '#nombre_tipo_pedido, #edit_activo_nombre, #edit_nombre_pedido', function () {
        const valorOriginal = $(this).val();
        const valorLimpio = valorOriginal.replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s.'-]/g, '');
        if (valorOriginal !== valorLimpio) {
            $(this).val(valorLimpio);
        }
    });

    /**
     * Valida un campo utilizando expresiones regulares
     * @param {jQuery} input
     * @param {string} nombrePatron 
     * @param {string} mensajeError 
     */
    function validarCampo(input, nombrePatron, mensajeError) {
        if (!input || input.length === 0) return false;

        const domInput = input[0];
        const $contenedor =$(domInput).closest('.mb-3, .form-group, div');

        $contenedor.find('.feedback-validacion').remove();$(domInput).siblings('.invalid-feedback, .valid-feedback').remove();

        const feedback = document.createElement('small');
        feedback.classList.add('feedback-validacion', 'form-text', 'd-block', 'mt-1');

        const valor = input.val().trim();

        if (valor === '') {
            input.removeClass('is-valid').addClass('is-invalid');
            feedback.textContent = 'Este campo no puede estar vacío.';
            feedback.style.color = '#dc3545';
            domInput.parentNode.appendChild(feedback);
            return false;
        }

        // Validación delegada a expresiones.js
        if ($.expresionesRegulares && $.expresionesRegulares.validar(nombrePatron, valor)) {
            input.removeClass('is-invalid').addClass('is-valid');
            feedback.textContent = 'Campo válido';
            feedback.style.color = '#198754';
            domInput.parentNode.appendChild(feedback);
            return true;
        }

        input.removeClass('is-valid').addClass('is-invalid');
        feedback.textContent = mensajeError;
        feedback.style.color = '#dc3545';
        domInput.parentNode.appendChild(feedback);

        return false;
    }

    function limpiarFormularioModal(modalSelector, formSelector) {
        if (formSelector && $(formSelector).length) {$(formSelector)[0].reset();
        }
        const $modal =$(modalSelector);
        $modal.find('input, select, textarea').val('').removeClass('is-valid is-invalid');$modal.find('.feedback-validacion, .invalid-feedback, .valid-feedback').remove();
    }

    // Eventos de cancelación manual (Solo botón Cancelar)
    $('#modalRegistrarPedido, #modalEditarActivo, #modalEditarInactivo').on('click', '[data-bs-dismiss="modal"]', function () {
        if ($(this).text().trim().toLowerCase() === 'cancelar') {
            const modalId = $(this).closest('.modal').attr('id');
            const formId = $(this).closest('.modal').find('form').attr('id');
            limpiarFormularioModal(`#${modalId}`, `#${formId}`);
        }
    });

    async function cargarPedidos() {
        try {
            const respuesta = await solicitarAPI(`${urlModulo}&ajax=listar`);
            todosLosPedidos = Array.isArray(respuesta?.pedidos) ? respuesta.pedidos : [];
            renderizarTabla(verEliminados ? 'Inactivo' : 'Activo');
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudieron cargar los tipos de pedido.',
                confirmButtonColor: '#dc3545'
            });
        }
    }

    function inicializarDataTable() {
        if (!tablaDataTable) {
            tablaDataTable = $('#tablaTipoPedido').DataTable({
                language: lenguajeEspanolDataTables,
                pageLength: 10,
                responsive: true,
                ordering: false
            });
        }
    }

    function renderizarTabla(estadoFiltro) {
        inicializarDataTable();
        tablaDataTable.clear();

        const filtrados = todosLosPedidos.filter(p => p.status_tipo_servicio === estadoFiltro);

        const filas = filtrados.map(pedido => {
            const id = escaparHTML(pedido.id_tipo_pedido);
            const nombre = escaparHTML(pedido.nombre_tipo_pedido);
            const estado = escaparHTML(pedido.status_tipo_servicio);

            const badge = estado === 'Activo'
                ? '<span class="badge bg-success">Activo</span>'
                : '<span class="badge bg-danger">Inactivo</span>';

            const acciones = estado === 'Activo'
                ? `
                    <button type="button" class="btn btn-sm btn-outline-primary btnEditarActivo me-1" data-id="${id}" data-nombre="${nombre}" data-estado="${estado}" title="Editar Tipo de Pedido">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger btnCambiarEstado" data-id="${id}" data-nombre="${nombre}" data-estado="Inactivo" title="Inhabilitar Tipo de Pedido">
                        <i class="bi bi-trash3-fill"></i>
                    </button>
                `
                : `
                    <button type="button" class="btn btn-sm btn-outline-warning btnEditarInactivo" data-id="${id}" data-nombre="${nombre}" data-estado="${estado}" title="Editar / Reactivar">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                `;

            return [
                `<span class="fw-bold">${nombre}</span>`,
                badge,
                `<div class="text-center">${acciones}</div>`
            ];
        });

        tablaDataTable.rows.add(filas).draw();
    }

    $('#btnAlternarEstado').on('click', function () {
        verEliminados = !verEliminados;

        if (verEliminados) {
            $(this).attr('data-vista', 'eliminados').removeClass('btn-outline-secondary').addClass('btn-secondary');$('#txtBotonEstado').text('Ver Activos');
            $('#iconoEstado').removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
            $('#tituloVista').text('Tipos de Pedido Inhabilitados');
            renderizarTabla('Inactivo');
        } else {
            $(this).attr('data-vista', 'activos').removeClass('btn-secondary').addClass('btn-outline-secondary');$('#txtBotonEstado').text('Ver inhabilitados');
            $('#iconoEstado').removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
            $('#tituloVista').text('Tipo de Pedido');
            renderizarTabla('Activo');
        }
    });

    async function procesarGuardado({ datosBody, modalSelector, formSelector }) {
        try {
            const respuesta = await solicitarAPI(urlModulo, {
                method: 'POST',
                data: datosBody
            });

            if (respuesta.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Completado!',
                    text: respuesta.message,
                    confirmButtonColor: '#10b981'
                });

                limpiarFormularioModal(modalSelector, formSelector);
                const modalElement = document.querySelector(modalSelector);
                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                if (modalInstance) modalInstance.hide();

                await cargarPedidos();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de validación',
                    text: respuesta.message,
                    confirmButtonColor: '#dc3545'
                });
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un error inesperado al procesar la solicitud.',
                confirmButtonColor: '#dc3545'
            });
        }
    }

    $('#btnEnvio').on('click', async function (evento) {
        evento.preventDefault();
        const inputNombre = $('#nombre_tipo_pedido');

        if (!validarCampo(inputNombre, 'nombre', 'El nombre debe tener entre 2 y 100 caracteres válidos.')) return;

        await procesarGuardado({
            datosBody: { nombre: inputNombre.val().trim() },
            modalSelector: '#modalRegistrarPedido',
            formSelector: '#formTipoPedido'
        });
    });

    $('#btnGuardarEdicionActivo').on('click', async function (evento) {
        evento.preventDefault();
        const id = $('#edit_activo_id').val();
        const inputNombre = $('#edit_activo_nombre');

        if (!validarCampo(inputNombre, 'nombre', 'El nombre debe tener entre 2 y 100 caracteres válidos.')) return;

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

    $('#btnGuardarEdicionInactivo').on('click', async function (evento) {
        evento.preventDefault();
        const id = $('#edit_id_pedido').val();
        const inputNombre = $('#edit_nombre_pedido');
        const nuevoEstado = $('#edit_status_pedido').val();

        if (!validarCampo(inputNombre, 'nombre', 'El nombre debe tener entre 2 y 100 caracteres válidos.')) return;

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

    $(document).on('click', '.btnEditarActivo', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');

        $('#edit_activo_id').val(id);
        $('#edit_activo_nombre').val(nombre).removeClass('is-invalid is-valid');
        $('#modalEditarActivo').find('.feedback-validacion, .invalid-feedback, .valid-feedback').remove();

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarActivo')).show();
    });

    $(document).on('click', '.btnEditarInactivo', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const estado = $(this).data('estado') || 'Inactivo';

        $('#edit_id_pedido').val(id);
        $('#edit_nombre_pedido').val(nombre).removeClass('is-invalid is-valid');
        $('#edit_status_pedido').val(estado);
        $('#modalEditarInactivo').find('.feedback-validacion, .invalid-feedback, .valid-feedback').remove();

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarInactivo')).show();
    });

    $(document).on('click', '.btnCambiarEstado', async function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const nuevoEstado = $(this).data('estado');

        const resultado = await Swal.fire({
            title: '¿Inhabilitar Tipo de Pedido?',
            text: `El registro "${nombre}" pasará a la lista de inhabilitados.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, inhabilitar',
            cancelButtonText: 'Cancelar'
        });

        if (!resultado.isConfirmed) return;

        try {
            const respuesta = await solicitarAPI(urlModulo, {
                method: 'POST',
                data: { id_accion: id, nuevo_estado: nuevoEstado }
            });

            if (respuesta.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Inhabilitado',
                    text: respuesta.message,
                    confirmButtonColor: '#10b981'
                });
                await cargarPedidos();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: respuesta.message,
                    confirmButtonColor: '#dc3545'
                });
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo cambiar el estado del tipo de pedido.',
                confirmButtonColor: '#dc3545'
            });
        }
    });

    cargarPedidos();
});