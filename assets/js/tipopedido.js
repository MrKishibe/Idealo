$(document).ready(function () {

    let tablaDataTable = null;
    let todosLosPedidos = [];
    let verEliminados = false;

    const regexNombre =
        /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]{3,50}$/;

    /*
    |--------------------------------------------------------------------------
    | Obtener URL del módulo
    |--------------------------------------------------------------------------
    */
    const urlModulo =
        'index.php?controller=tipoPedido&action=listar';

    /*
    |--------------------------------------------------------------------------
    | Escapar HTML para evitar insertar contenido sin protección
    |--------------------------------------------------------------------------
    */
    function escaparHTML(texto) {
        return $('<div>')
            .text(texto ?? '')
            .html();
    }

    /*
    |--------------------------------------------------------------------------
    | Mostrar mensaje de validación
    |--------------------------------------------------------------------------
    */
    function validarCampo(
        input,
        regex,
        mensajeError
    ) {
        if (!input || input.length === 0) {
            return false;
        }

        const domInput = input[0];

        $(domInput)
            .siblings('.feedback-validacion')
            .remove();

        const feedback = document.createElement('small');

        feedback.classList.add(
            'feedback-validacion',
            'form-text'
        );

        domInput.parentNode.appendChild(feedback);

        const valor = input.val().trim();

        if (valor === '') {
            input
                .removeClass('is-valid')
                .addClass('is-invalid');

            feedback.textContent =
                'Este campo no puede estar vacío.';

            feedback.style.color = '#dc3545';

            return false;
        }

        if (regex.test(valor)) {
            input
                .removeClass('is-invalid')
                .addClass('is-valid');

            feedback.textContent = 'Campo válido';
            feedback.style.color = '#198754';

            return true;
        }

        input
            .removeClass('is-valid')
            .addClass('is-invalid');

        feedback.textContent = mensajeError;
        feedback.style.color = '#dc3545';

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Validación en tiempo real
    |--------------------------------------------------------------------------
    */
    $('#nombre_tipo_pedido').on(
        'input',
        function () {
            validarCampo(
                $(this),
                regexNombre,
                'El nombre debe tener entre 3 y 50 caracteres.'
            );
        }
    );

    $('#edit_activo_nombre').on(
        'input',
        function () {
            validarCampo(
                $(this),
                regexNombre,
                'El nombre debe tener entre 3 y 50 caracteres.'
            );
        }
    );

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

                if (
                    respuesta &&
                    Array.isArray(respuesta.pedidos)
                ) {
                    todosLosPedidos = respuesta.pedidos;
                } else {
                    todosLosPedidos = [];
                }

                renderizarTabla(
                    verEliminados
                        ? 'Inactivo'
                        : 'Activo'
                );
            },

            error: function (xhr, status, error) {
                console.error(
                    'Error al cargar los tipos de pedido:',
                    error
                );

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

            const id = escaparHTML(
                pedido.id_tipo_pedido
            );

            const nombre = escaparHTML(
                pedido.nombre_tipo_pedido
            );

            const nombreData = escaparHTML(
                pedido.nombre_tipo_pedido
            );

            const estado = escaparHTML(
                pedido.status_tipo_servicio
            );

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
                        title="Reactivar Tipo de Pedido">

                        <i class="bi bi-pencil-square"></i>
                        Editar / Reactivar
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
    $('#btnAlternarEstado').on(
        'click',
        function () {

            verEliminados = !verEliminados;

            if (verEliminados) {

                $(this)
                    .attr('data-vista', 'eliminados')
                    .removeClass('btn-outline-secondary')
                    .addClass('btn-secondary');

                $('#txtBotonEstado')
                    .text('Ver Activos');

                $('#iconoEstado')
                    .removeClass('bi-eye-slash-fill')
                    .addClass('bi-eye-fill');

                $('#tituloVista')
                    .text('Tipos de Pedido Inhabilitados');

                renderizarTabla('Inactivo');

            } else {

                $(this)
                    .attr('data-vista', 'activos')
                    .removeClass('btn-secondary')
                    .addClass('btn-outline-secondary');

                $('#txtBotonEstado')
                    .text('Ver inhabilitados');

                $('#iconoEstado')
                    .removeClass('bi-eye-fill')
                    .addClass('bi-eye-slash-fill');

                $('#tituloVista')
                    .text('Tipo de Pedido');

                renderizarTabla('Activo');
            }
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Registrar tipo de pedido
    |--------------------------------------------------------------------------
    */
    $('#btnEnvio').on(
        'click',
        function (evento) {

            evento.preventDefault();

            const inputNombre =
                $('#nombre_tipo_pedido');

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

                        $('#formTipoPedido')[0].reset();

                        $('#formTipoPedido')
                            .find('.is-valid, .is-invalid')
                            .removeClass(
                                'is-valid is-invalid'
                            );

                        $('#formTipoPedido')
                            .find('.feedback-validacion')
                            .remove();

                        const modal =
                            bootstrap.Modal.getInstance(
                                document.getElementById(
                                    'modalRegistrarPedido'
                                )
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
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Abrir modal de edición de activo
    |--------------------------------------------------------------------------
    */
    $(document).on(
        'click',
        '.btnEditarActivo',
        function () {

            const id = $(this).data('id');
            const nombre = $(this).data('nombre');

            $('#edit_activo_id')
                .val(id);

            $('#edit_activo_nombre')
                .val(nombre)
                .removeClass('is-invalid is-valid');

            $('#modalEditarActivo')
                .find('.feedback-validacion')
                .remove();

            new bootstrap.Modal(
                document.getElementById(
                    'modalEditarActivo'
                )
            ).show();
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Guardar edición de activo
    |--------------------------------------------------------------------------
    */
    $('#btnGuardarEdicionActivo').on(
        'click',
        function (evento) {

            evento.preventDefault();

            const id =
                $('#edit_activo_id').val();

            const inputNombre =
                $('#edit_activo_nombre');

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
                    nuevo_estado: 'Activo',
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

                        const modal =
                            bootstrap.Modal.getInstance(
                                document.getElementById(
                                    'modalEditarActivo'
                                )
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
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Abrir modal de registro inactivo
    |--------------------------------------------------------------------------
    */
    $(document).on(
        'click',
        '.btnEditarInactivo',
        function () {

            const id = $(this).data('id');
            const nombre = $(this).data('nombre');

            $('#edit_id_pedido')
                .val(id);

            $('#edit_nombre_pedido')
                .val(nombre);

            $('#edit_status_pedido')
                .val('Inactivo');

            new bootstrap.Modal(
                document.getElementById(
                    'modalEditarInactivo'
                )
            ).show();
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Reactivar tipo de pedido
    |--------------------------------------------------------------------------
    */
    $('#btnGuardarEdicionInactivo').on(
        'click',
        function (evento) {

            evento.preventDefault();

            const id =
                $('#edit_id_pedido').val();

            const nuevoEstado =
                $('#edit_status_pedido').val();

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
                            title: 'Estado modificado',
                            text: respuesta.message,
                            confirmButtonColor: '#10b981'
                        });

                        const modal =
                            bootstrap.Modal.getInstance(
                                document.getElementById(
                                    'modalEditarInactivo'
                                )
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
                        text: 'No se pudo cambiar el estado del registro.',
                        confirmButtonColor: '#dc3545'
                    });
                }
            });
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Inhabilitar tipo de pedido
    |--------------------------------------------------------------------------
    */
    $(document).on(
        'click',
        '.btnCambiarEstado',
        function () {

            const id = $(this).data('id');
            const nombre = $(this).data('nombre');

            Swal.fire({
                title: '¿Inhabilitar Tipo de Pedido?',
                text:
                    `El registro "${nombre}" pasará a la lista de inhabilitados.`,
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
                        nuevo_estado: 'Inactivo'
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
                            text: 'No se pudo inhabilitar el tipo de pedido.',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                });
            });
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Carga inicial
    |--------------------------------------------------------------------------
    */
    cargarPedidos();

});