$(document).ready(function () {

    let tablaDataTable = null;
    let todasLasMateriasPrimas = [];
    let verEliminados = false;

    const regexNombre =
        /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-\(\)\.\/]{3,100}$/;

    const urlModulo =
        'index.php?controller=materiaPrima&action=listar';

    function escaparHTML(texto) {
        return $('<div>')
            .text(texto ?? '')
            .html();
    }

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

    function validarNumero(input, mensajeError) {
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

        const numero = parseFloat(valor);

        if (isNaN(numero) || numero < 0) {
            input
                .removeClass('is-valid')
                .addClass('is-invalid');

            feedback.textContent = mensajeError;
            feedback.style.color = '#dc3545';

            return false;
        }

        input
            .removeClass('is-invalid')
            .addClass('is-valid');

        feedback.textContent = 'Campo válido';
        feedback.style.color = '#198754';

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Cargar tipos activos para el select
    |--------------------------------------------------------------------------
    */
    function cargarTiposActivos() {
        return $.ajax({
            url: `${urlModulo}&ajax=tipos_activos`,
            type: 'GET',
            dataType: 'json'
        });
    }

    function cargarMateriasPrimas() {
        $.ajax({
            url: `${urlModulo}&ajax=listar`,
            type: 'GET',
            dataType: 'json',

            success: function (respuesta) {

                if (
                    respuesta &&
                    Array.isArray(respuesta.materias_primas)
                ) {
                    todasLasMateriasPrimas = respuesta.materias_primas;
                } else {
                    todasLasMateriasPrimas = [];
                }

                renderizarTabla(
                    verEliminados
                        ? 'Inactivo'
                        : 'Activo'
                );
            },

            error: function (xhr, status, error) {
                console.error(
                    'Error al cargar las materias primas:',
                    error
                );

                console.log(
                    'Respuesta cruda del servidor:',
                    xhr.responseText
                );

                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudieron cargar las materias primas.',
                    confirmButtonColor: '#dc3545'
                });
            }
        });
    }

    function renderizarTabla(estadoFiltro) {

        const tabla = $('#tablaMateriaPrima');
        const tbody = tabla.find('tbody');

        if ($.fn.DataTable.isDataTable('#tablaMateriaPrima')) {
            tablaDataTable.destroy();
            tablaDataTable = null;
        }

        tbody.empty();

        const filtrados = todasLasMateriasPrimas.filter(function (mp) {
            return mp.status_materia_prima === estadoFiltro;
        });

        filtrados.forEach(function (mp) {

            const id = escaparHTML(
                mp.id_materia_prima
            );

            const nombre = escaparHTML(
                mp.nombre_materia_prima
            );

            const tipo = escaparHTML(
                mp.nombre_de_material
            );

            const costo = escaparHTML(
                mp.costo_unitario
            );

            const stockActual = escaparHTML(
                mp.stock_actual
            );

            const stockMinimo = escaparHTML(
                mp.stock_minimo
            );

            const unidad = escaparHTML(
                mp.unidad_de_medida
            );

            const estado = escaparHTML(
                mp.status_materia_prima
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
                        data-nombre="${nombre}"
                        data-id-tipo="${mp.id_tipo_materia_prima}"
                        data-costo="${costo}"
                        data-stock-actual="${stockActual}"
                        data-stock-minimo="${stockMinimo}"
                        data-unidad="${unidad}"
                        title="Editar Materia Prima">

                        <i class="bi bi-pencil-square"></i>
                    </button>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger btnCambiarEstado"
                        data-id="${id}"
                        data-nombre="${nombre}"
                        data-estado="Inactivo"
                        title="Inhabilitar Materia Prima">

                        <i class="bi bi-trash3-fill"></i>
                    </button>
                `;

            } else {

                acciones = `
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-warning btnEditarInactivo"
                        data-id="${id}"
                        data-nombre="${nombre}"
                        title="Reactivar Materia Prima">

                        <i class="bi bi-pencil-square"></i>
                        Editar / Reactivar
                    </button>
                `;
            }

            tbody.append(`
                <tr id="fila-${id}">
                    <td class="fw-bold">${nombre}</td>
                    <td>${tipo}</td>
                    <td>${costo}</td>
                    <td>${stockActual} ${unidad}</td>
                    <td>${stockMinimo} ${unidad}</td>
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
    | Cargar tipos en el modal de registro
    |--------------------------------------------------------------------------
    */
    function llenarSelectTipos() {
        cargarTiposActivos().done(function (respuesta) {
            const select = $('#id_tipo_materia_prima');

            select.empty();
            select.append(
                '<option value="">Seleccione un tipo...</option>'
            );

            if (respuesta && Array.isArray(respuesta.tipos)) {
                respuesta.tipos.forEach(function (tipo) {
                    select.append(
                        `<option value="${tipo.id_tipo_materia_prima}">
                            ${tipo.nombre_de_material}
                        </option>`
                    );
                });
            }
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
                    .text('Materias Primas Inhabilitadas');

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
                    .text('Materia Prima');

                renderizarTabla('Activo');
            }
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Abrir modal de registro y cargar tipos
    |--------------------------------------------------------------------------
    */
    $('#modalRegistrarMateriaPrima').on(
        'show.bs.modal',
        function () {
            llenarSelectTipos();
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Registrar materia prima
    |--------------------------------------------------------------------------
    */
    $('#btnEnvio').on(
        'click',
        function (evento) {

            evento.preventDefault();

            const inputNombre =
                $('#nombre_materia_prima');

            const selectTipo =
                $('#id_tipo_materia_prima');

            const inputCosto =
                $('#costo_unitario');

            const inputStockActual =
                $('#stock_actual');

            const inputStockMinimo =
                $('#stock_minimo');

            const selectUnidad =
                $('#unidad_de_medida');

            const nombreValido = validarCampo(
                inputNombre,
                regexNombre,
                'El nombre debe tener entre 3 y 100 caracteres.'
            );

            if (!nombreValido) {
                return;
            }

            if (selectTipo.val() === '') {
                selectTipo
                    .removeClass('is-valid')
                    .addClass('is-invalid');

                Swal.fire({
                    icon: 'error',
                    title: 'Error de validación',
                    text: 'Debe seleccionar un tipo de materia prima.',
                    confirmButtonColor: '#dc3545'
                });

                return;
            }

            selectTipo
                .removeClass('is-invalid')
                .addClass('is-valid');

            const costoValido = validarNumero(
                inputCosto,
                'El costo unitario debe ser un número mayor o igual a 0.'
            );

            if (!costoValido) {
                return;
            }

            const stockActualValido = validarNumero(
                inputStockActual,
                'El stock actual debe ser un número mayor o igual a 0.'
            );

            if (!stockActualValido) {
                return;
            }

            const stockMinimoValido = validarNumero(
                inputStockMinimo,
                'El stock mínimo debe ser un número mayor o igual a 0.'
            );

            if (!stockMinimoValido) {
                return;
            }

            if (selectUnidad.val() === '') {
                selectUnidad
                    .removeClass('is-valid')
                    .addClass('is-invalid');

                Swal.fire({
                    icon: 'error',
                    title: 'Error de validación',
                    text: 'Debe seleccionar una unidad de medida.',
                    confirmButtonColor: '#dc3545'
                });

                return;
            }

            selectUnidad
                .removeClass('is-invalid')
                .addClass('is-valid');

            $.ajax({
                url: urlModulo,
                type: 'POST',
                data: {
                    nombre: inputNombre.val().trim(),
                    id_tipo_materia_prima: selectTipo.val(),
                    costo_unitario: inputCosto.val().trim(),
                    stock_actual: inputStockActual.val().trim(),
                    stock_minimo: inputStockMinimo.val().trim(),
                    unidad_de_medida: selectUnidad.val()
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

                        $('#formMateriaPrima')[0].reset();

                        $('#formMateriaPrima')
                            .find('.is-valid, .is-invalid')
                            .removeClass(
                                'is-valid is-invalid'
                            );

                        $('#formMateriaPrima')
                            .find('.feedback-validacion')
                            .remove();

                        const modal =
                            bootstrap.Modal.getInstance(
                                document.getElementById(
                                    'modalRegistrarMateriaPrima'
                                )
                            );

                        if (modal) {
                            modal.hide();
                        }

                        cargarMateriasPrimas();

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
            const idTipo = $(this).data('id-tipo');
            const costo = $(this).data('costo');
            const stockActual = $(this).data('stock-actual');
            const stockMinimo = $(this).data('stock-minimo');
            const unidad = $(this).data('unidad');

            $('#edit_activo_id')
                .val(id);

            $('#edit_activo_nombre')
                .val(nombre)
                .removeClass('is-invalid is-valid');

            $('#edit_activo_costo')
                .val(costo)
                .removeClass('is-invalid is-valid');

            $('#edit_activo_stock_actual')
                .val(stockActual)
                .removeClass('is-invalid is-valid');

            $('#edit_activo_stock_minimo')
                .val(stockMinimo)
                .removeClass('is-invalid is-valid');

            $('#edit_activo_unidad')
                .val(unidad)
                .removeClass('is-invalid is-valid');

            $('#modalEditarActivo')
                .find('.feedback-validacion')
                .remove();

            llenarSelectTipos().done(function () {
                $('#edit_activo_id_tipo')
                    .val(idTipo);
            });

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

            const selectTipo =
                $('#edit_activo_id_tipo');

            const inputCosto =
                $('#edit_activo_costo');

            const inputStockActual =
                $('#edit_activo_stock_actual');

            const inputStockMinimo =
                $('#edit_activo_stock_minimo');

            const selectUnidad =
                $('#edit_activo_unidad');

            const nombreValido = validarCampo(
                inputNombre,
                regexNombre,
                'El nombre debe tener entre 3 y 100 caracteres.'
            );

            if (!nombreValido) {
                return;
            }

            if (selectTipo.val() === '') {
                selectTipo
                    .removeClass('is-valid')
                    .addClass('is-invalid');

                Swal.fire({
                    icon: 'error',
                    title: 'Error de validación',
                    text: 'Debe seleccionar un tipo de materia prima.',
                    confirmButtonColor: '#dc3545'
                });

                return;
            }

            selectTipo
                .removeClass('is-invalid')
                .addClass('is-valid');

            const costoValido = validarNumero(
                inputCosto,
                'El costo unitario debe ser un número mayor o igual a 0.'
            );

            if (!costoValido) {
                return;
            }

            const stockActualValido = validarNumero(
                inputStockActual,
                'El stock actual debe ser un número mayor o igual a 0.'
            );

            if (!stockActualValido) {
                return;
            }

            const stockMinimoValido = validarNumero(
                inputStockMinimo,
                'El stock mínimo debe ser un número mayor o igual a 0.'
            );

            if (!stockMinimoValido) {
                return;
            }

            if (selectUnidad.val() === '') {
                selectUnidad
                    .removeClass('is-valid')
                    .addClass('is-invalid');

                Swal.fire({
                    icon: 'error',
                    title: 'Error de validación',
                    text: 'Debe seleccionar una unidad de medida.',
                    confirmButtonColor: '#dc3545'
                });

                return;
            }

            selectUnidad
                .removeClass('is-invalid')
                .addClass('is-valid');

            $.ajax({
                url: urlModulo,
                type: 'POST',
                data: {
                    id_accion: id,
                    nuevo_estado: 'Activo',
                    nombre: inputNombre.val().trim(),
                    id_tipo_materia_prima: selectTipo.val(),
                    costo_unitario: inputCosto.val().trim(),
                    stock_actual: inputStockActual.val().trim(),
                    stock_minimo: inputStockMinimo.val().trim(),
                    unidad_de_medida: selectUnidad.val()
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

                        cargarMateriasPrimas();

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
                        text: 'No se pudo actualizar la materia prima.',
                        confirmButtonColor: '#dc3545'
                    });
                }
            });
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Abrir modal de edición de inactivo
    |--------------------------------------------------------------------------
    */
    $(document).on(
        'click',
        '.btnEditarInactivo',
        function () {

            const id = $(this).data('id');
            const nombre = $(this).data('nombre');

            $('#edit_id_materia_prima')
                .val(id);

            $('#edit_nombre_materia_prima')
                .val(nombre);

            $('#edit_status_materia_prima')
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
    | Reactivar materia prima
    |--------------------------------------------------------------------------
    */
    $('#btnGuardarEdicionInactivo').on(
        'click',
        function (evento) {

            evento.preventDefault();

            const id =
                $('#edit_id_materia_prima').val();

            const nuevoEstado =
                $('#edit_status_materia_prima').val();

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

                        cargarMateriasPrimas();

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
    | Inhabilitar materia prima
    |--------------------------------------------------------------------------
    */
    $(document).on(
        'click',
        '.btnCambiarEstado',
        function () {

            const id = $(this).data('id');
            const nombre = $(this).data('nombre');

            Swal.fire({
                title: '¿Inhabilitar Materia Prima?',
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

                            cargarMateriasPrimas();

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
                            text: 'No se pudo inhabilitar la materia prima.',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                });
            });
        }
    );

    cargarMateriasPrimas();

});