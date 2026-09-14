$(document).ready(function () {

    let tablaDataTable = null;
    let todosLosPedidos = [];
    let verEliminados = false;

    // Expresión regular para validar la longitud final (entre 3 y 50 caracteres)
    const regexNombre = /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]{3,50}$/;

    // Expresión regular para bloquear/limpiar caracteres no permitidos mientras el usuario escribe
    const regexCaracteresPermitidos = /[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]/g;

    /*
    |--------------------------------------------------------------------------
    | Bloqueo en tiempo real de caracteres no permitidos
    |--------------------------------------------------------------------------
    */
    $(document).on('input', '#nombre_tipo_pedido, #edit_activo_nombre, #edit_nombre_pedido', function () {
        const valorOriginal = $(this).val();
        const valorLimpio = valorOriginal.replace(regexCaracteresPermitidos, '');

        if (valorOriginal !== valorLimpio) {
            $(this).val(valorLimpio);
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Obtener URL del módulo
    |--------------------------------------------------------------------------
    */
    const urlModulo = 'index.php?controller=tipoPedido&action=listar';

    /*
    |--------------------------------------------------------------------------
    | Escapar HTML para evitar XSS
    |--------------------------------------------------------------------------
    */
    function escaparHTML(texto) {
        return $('<div>').text(texto ?? '').html();
    }

    /*
    |--------------------------------------------------------------------------
    | Mostrar mensaje de validación (Sin duplicados)
    |--------------------------------------------------------------------------
    */
    function validarCampo(input, regex, mensajeError) {
        if (!input || input.length === 0) {
            return false;
        }

        const domInput = input[0];
        const $contenedor = $(domInput).closest('.mb-3, .form-group, div');

        // Limpieza de feedback previo en el contenedor y hermanos directos
        $contenedor.find('.feedback-validacion').remove();
        $(domInput).siblings('.invalid-feedback, .valid-feedback').remove();

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

        if (regex.test(valor)) {
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

    /*
    |--------------------------------------------------------------------------
    | Limpiar formulario y estados de validación de un modal
    |--------------------------------------------------------------------------
    */
    function limpiarFormularioModal(modalSelector, formSelector) {
        if (formSelector && $(formSelector).length) {
            $(formSelector)[0].reset();
        }
        const $modal = $(modalSelector);
        $modal.find('input, select, textarea').val('').removeClass('is-valid is-invalid');
        $modal.find('.feedback-validacion, .invalid-feedback, .valid-feedback').remove();
    }

    /*
    |--------------------------------------------------------------------------
    | Eventos de limpieza explícita para modales (SOLO con botón Cancelar)
    |--------------------------------------------------------------------------
    */
    $('#modalRegistrarPedido').on('click', '[data-bs-dismiss="modal"]', function () {
        // Solo limpia si es el botón Cancelar (no la X)
        if ($(this).text().trim().toLowerCase() === 'cancelar') {
            limpiarFormularioModal('#modalRegistrarPedido', '#formTipoPedido');
        }
    });

    $('#modalEditarActivo').on('click', '[data-bs-dismiss="modal"]', function () {
        if ($(this).text().trim().toLowerCase() === 'cancelar') {
            limpiarFormularioModal('#modalEditarActivo', '#formEditarActivo');
        }
    });

    $('#modalEditarInactivo').on('click', '[data-bs-dismiss="modal"]', function () {
        if ($(this).text().trim().toLowerCase() === 'cancelar') {
            limpiarFormularioModal('#modalEditarInactivo', '#formEditarPedido');
        }
    });

    // Al cerrar con X o clic fuera: NO limpiar (los datos se mantienen)
    $('#modalRegistrarPedido, #modalEditarActivo, #modalEditarInactivo').on('hide.bs.modal', function () {
        // No hacemos nada aquí intencionalmente
    });

    /*
    |--------------------------------------------------------------------------
    | Cargar pedidos desde el servidor
    |--------------------------------------------------------------------------
    */
    function cargarPedidos() {
        $.ajax({
            url: `${urlModulo}&ajax=listar`,
            type: 'GET',
            dataType: 'json',

            success: function (respuesta) {
                if (respuesta && Array.isArray(respuesta.pedidos)) {
                    todosLosPedidos = respuesta.pedidos;
                } else {
                    todosLosPedidos = [];
                }

                renderizarTabla(verEliminados ? 'Inactivo' : 'Activo');
            },

            error: function (xhr, status, error) {
                console.error('Error al cargar los tipos de pedido:', error);

                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudieron cargar los tipos de pedido.',
                    confirmButtonColor: '#dc3545'
                });
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Renderizar tabla
    |--------------------------------------------------------------------------
    */
    function renderizarTabla(estadoFiltro) {
        const tabla = $('#tablaTipoPedido');
        const tbody = tabla.find('tbody');

        if ($.fn.DataTable.isDataTable('#tablaTipoPedido')) {
            tablaDataTable.destroy();
            tablaDataTable = null;
        }

        tbody.empty();

        const filtrados = todosLosPedidos.filter(function (pedido) {
            return pedido.status_tipo_servicio === estadoFiltro;
        });

        filtrados.forEach(function (pedido) {
            const id = escaparHTML(pedido.id_tipo_pedido);
            const nombre = escaparHTML(pedido.nombre_tipo_pedido);
            const nombreData = escaparHTML(pedido.nombre_tipo_pedido);
            const estado = escaparHTML(pedido.status_tipo_servicio);

            const badge = estado === 'Activo'
                ? '<span class="badge bg-success">Activo</span>'
                : '<span class="badge bg-danger">Inactivo</span>';

            let acciones = '';

            if (estado === 'Activo') {
                acciones = `
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary btnEditarActivo me-1"
                        data-id="${id}"
                        data-nombre="${nombreData}"
                        data-estado="${estado}"
                        title="Editar Tipo de Pedido">

                        <i class="bi bi-pencil-square"></i>
                    </button>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger btnCambiarEstado"
                        data-id="${id}"
                        data-nombre="${nombreData}"
                        data-estado="Inactivo"
                        title="Inhabilitar Tipo de Pedido">

                        <i class="bi bi-trash3-fill"></i>
                    </button>
                `;
            } else {
                acciones = `
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-warning btnEditarInactivo"
                        data-id="${id}"
                        data-nombre="${nombreData}"
                        data-estado="${estado}"
                        title="Editar / Reactivar">

                        <i class="bi bi-pencil-square"></i>
                    </button>
                `;
            }

            tbody.append(`
                <tr id="fila-${id}">
                    <td class="fw-bold">${nombre}</td>
                    <td>${badge}</td>
                    <td>
                        <div class="text-center">
                            ${acciones}
                        </div>
                    </td>
                </tr>
            `);
        });

        tablaDataTable = tabla.DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                emptyTable: 'No hay registros en esta vista',
                zeroRecords: 'No se encontraron coincidencias'
            },
            pageLength: 10,
            responsive: true,
            ordering: false
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Alternar activos e inactivos
    |--------------------------------------------------------------------------
    */
    $('#btnAlternarEstado').on('click', function () {
        verEliminados = !verEliminados;

        if (verEliminados) {
            $(this)
                .attr('data-vista', 'eliminados')
                .removeClass('btn-outline-secondary')
                .addClass('btn-secondary');

            $('#txtBotonEstado').text('Ver Activos');
            $('#iconoEstado').removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
            $('#tituloVista').text('Tipos de Pedido Inhabilitados');

            renderizarTabla('Inactivo');

        } else {
            $(this)
                .attr('data-vista', 'activos')
                .removeClass('btn-secondary')
                .addClass('btn-outline-secondary');

            $('#txtBotonEstado').text('Ver inhabilitados');
            $('#iconoEstado').removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
            $('#tituloVista').text('Tipo de Pedido');

            renderizarTabla('Activo');
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Registrar tipo de pedido
    |--------------------------------------------------------------------------
    */
    $('#btnEnvio').on('click', function (evento) {
        evento.preventDefault();

        const inputNombre = $('#nombre_tipo_pedido');

        const nombreValido = validarCampo(
            inputNombre,
            regexNombre,
            'El nombre debe tener entre 3 y 50 caracteres.'
        );

        if (!nombreValido) {
            return;
        }

        $.ajax({
            url: urlModulo,
            type: 'POST',
            data: {
                nombre: inputNombre.val().trim()
            },
            dataType: 'json',

            success: function (respuesta) {
                if (respuesta.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Completado!',
                        text: respuesta.message,
                        confirmButtonColor: '#10b981'
                    });

                    // Limpiar SOLO después de registro exitoso
                    limpiarFormularioModal('#modalRegistrarPedido', '#formTipoPedido');

                    const modal = bootstrap.Modal.getInstance(
                        document.getElementById('modalRegistrarPedido')
                    );

                    if (modal) {
                        modal.hide();
                    }

                    cargarPedidos();

                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de validación',
                        text: respuesta.message,
                        confirmButtonColor: '#dc3545'
                    });
                }
            },

            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Sucedió un error inesperado de comunicación con el servidor.',
                    confirmButtonColor: '#dc3545'
                });
            }
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Abrir modal de edición (Para Activos e Inactivos)
    |--------------------------------------------------------------------------
    */
    $(document).on('click', '.btnEditarActivo', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const estado = $(this).data('estado') || 'Activo';

        $('#edit_activo_id').val(id);
        $('#edit_activo_nombre').val(nombre).removeClass('is-invalid is-valid');
        $('#modalEditarActivo').find('.feedback-validacion, .invalid-feedback, .valid-feedback').remove();

        new bootstrap.Modal(
            document.getElementById('modalEditarActivo')
        ).show();
    });

    // Abrir modal de edición para INACTIVOS (ahora con nombre editable + estado)
    $(document).on('click', '.btnEditarInactivo', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const estado = $(this).data('estado') || 'Inactivo';

        $('#edit_id_pedido').val(id);
        $('#edit_nombre_pedido').val(nombre).removeClass('is-invalid is-valid');
        $('#edit_status_pedido').val(estado);

        $('#modalEditarInactivo').find('.feedback-validacion, .invalid-feedback, .valid-feedback').remove();

        new bootstrap.Modal(
            document.getElementById('modalEditarInactivo')
        ).show();
    });

    /*
    |--------------------------------------------------------------------------
    | Guardar edición (Activo)
    |--------------------------------------------------------------------------
    */
    $('#btnGuardarEdicionActivo').on('click', function (evento) {
        evento.preventDefault();

        const id = $('#edit_activo_id').val();
        const inputNombre = $('#edit_activo_nombre');

        const nombreValido = validarCampo(
            inputNombre,
            regexNombre,
            'El nombre debe tener entre 3 y 50 caracteres.'
        );

        if (!nombreValido) {
            return;
        }

        const estadoDestino = 'Activo';

        $.ajax({
            url: urlModulo,
            type: 'POST',
            data: {
                id_accion: id,
                nuevo_estado: estadoDestino,
                nombre: inputNombre.val().trim()
            },
            dataType: 'json',

            success: function (respuesta) {
                if (respuesta.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Actualizado',
                        text: respuesta.message,
                        confirmButtonColor: '#10b981'
                    });

                    // Limpiar SOLO después de edición exitosa
                    limpiarFormularioModal('#modalEditarActivo', '#formEditarActivo');

                    const modal = bootstrap.Modal.getInstance(
                        document.getElementById('modalEditarActivo')
                    );

                    if (modal) {
                        modal.hide();
                    }

                    cargarPedidos();

                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: respuesta.message,
                        confirmButtonColor: '#dc3545'
                    });
                }
            },

            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo actualizar el tipo de pedido.',
                    confirmButtonColor: '#dc3545'
                });
            }
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Guardar edición desde modal INACTIVO (NOMBRE + ESTADO)
    |--------------------------------------------------------------------------
    */
    $('#btnGuardarEdicionInactivo').on('click', function (evento) {
        evento.preventDefault();

        const id = $('#edit_id_pedido').val();
        const inputNombre = $('#edit_nombre_pedido');
        const nuevoEstado = $('#edit_status_pedido').val();

        const nombreValido = validarCampo(
            inputNombre,
            regexNombre,
            'El nombre debe tener entre 3 y 50 caracteres.'
        );

        if (!nombreValido) {
            return;
        }

        $.ajax({
            url: urlModulo,
            type: 'POST',
            data: {
                id_accion: id,
                nombre: inputNombre.val().trim(),
                nuevo_estado: nuevoEstado
            },
            dataType: 'json',

            success: function (respuesta) {
                if (respuesta.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Actualizado',
                        text: respuesta.message,
                        confirmButtonColor: '#10b981'
                    });

                    // Limpiar SOLO después de edición exitosa
                    limpiarFormularioModal('#modalEditarInactivo', '#formEditarPedido');

                    const modal = bootstrap.Modal.getInstance(
                        document.getElementById('modalEditarInactivo')
                    );

                    if (modal) {
                        modal.hide();
                    }

                    cargarPedidos();

                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: respuesta.message,
                        confirmButtonColor: '#dc3545'
                    });
                }
            },

            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo actualizar el tipo de pedido.',
                    confirmButtonColor: '#dc3545'
                });
            }
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Inhabilitar tipo de pedido (Solamente Activos)
    |--------------------------------------------------------------------------
    */
    $(document).on('click', '.btnCambiarEstado', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const nuevoEstado = $(this).data('estado');

        Swal.fire({
            title: '¿Inhabilitar Tipo de Pedido?',
            text: `El registro "${nombre}" pasará a la lista de inhabilitados.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, inhabilitar',
            cancelButtonText: 'Cancelar'

        }).then(function (resultado) {
            if (!resultado.isConfirmed) {
                return;
            }

            $.ajax({
                url: urlModulo,
                type: 'POST',
                data: {
                    id_accion: id,
                    nuevo_estado: nuevoEstado
                },
                dataType: 'json',

                success: function (respuesta) {
                    if (respuesta.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Inhabilitado',
                            text: respuesta.message,
                            confirmButtonColor: '#10b981'
                        });

                        cargarPedidos();

                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: respuesta.message,
                            confirmButtonColor: '#dc3545'
                        });
                    }
                },

                error: function () {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo cambiar el estado del tipo de pedido.',
                        confirmButtonColor: '#dc3545'
                    });
                }
            });
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Carga inicial
    |--------------------------------------------------------------------------
    */
    cargarPedidos();

});