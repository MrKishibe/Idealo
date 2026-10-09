$(document).ready(function () {
    let tablaDataTable = null;
    let todasLasMateriasPrimas = [];
    let verEliminados = false;

    const urlModulo = 'index.php?controller=materiaPrima&action=listar';

    console.log('JS cargado');

    // Helper unificado de peticiones AJAX envuelto en Promesas
    function peticionAjax(url, opciones = {}) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: url,
                type: opciones.type || 'GET',
                data: opciones.data || null,
                processData: opciones.processData !== undefined ? opciones.processData : true,
                contentType: opciones.contentType !== undefined ? opciones.contentType : 'application/x-www-form-urlencoded; charset=UTF-8',
                dataType: 'json'
            })
            .done(function (respuesta) {
                resolve(respuesta);
            })
            .fail(function (jqXHR, textStatus, errorThrown) {
                console.error('Error en petición AJAX:', jqXHR.responseText || textStatus);
                let mensaje = 'Error de comunicación con el servidor.';
                if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    mensaje = jqXHR.responseJSON.message;
                }
                reject(new Error(mensaje));
            });
        });
    }

    function alerta(icono, titulo, texto, colorBoton = '#dc3545') {
        return Swal.fire({
            icon: icono,
            title: titulo,
            text: texto,
            confirmButtonColor: colorBoton
        });
    }

    function escaparHTML(texto) {
        return $('<div>').text(texto ?? '').html();
    }

    function aplicarFeedbackUI(input, esValido, mensaje) {
        const domInput = input[0];
        $(domInput).siblings('.feedback-validacion').remove();

        const feedback = document.createElement('small');
        feedback.classList.add('feedback-validacion', 'form-text', 'd-block', 'mt-1');
        domInput.parentNode.appendChild(feedback);

        if (esValido) {
            input.removeClass('is-invalid').addClass('is-valid');
            feedback.textContent = mensaje || 'Campo válido';
            feedback.style.color = '#198754';
        } else {
            input.removeClass('is-valid').addClass('is-invalid');
            feedback.textContent = mensaje;
            feedback.style.color = '#dc3545';
        }
        return esValido;
    }

    // Validaciones reutilizando $.expresionesRegulares
    function validarCampo(input, patronNombre, mensajeError) {
        if (!input || input.length === 0) return false;
        const valor = input.val().trim();

        if (valor === '') {
            return aplicarFeedbackUI(input, false, 'Este campo no puede estar vacío.');
        }

        const esValido = $.expresionesRegulares.validar(patronNombre, valor);
        return aplicarFeedbackUI(input, esValido, esValido ? 'Campo válido' : mensajeError);
    }

    function validarNumero(input, patronNombre, mensajeError) {
        if (!input || input.length === 0) return false;
        const valor = input.val().trim();

        if (valor === '') {
            return aplicarFeedbackUI(input, false, 'Este campo no puede estar vacío.');
        }

        const esValido = $.expresionesRegulares.validar(patronNombre, valor);
        return aplicarFeedbackUI(input, esValido, esValido ? 'Campo válido' : mensajeError);
    }

    function limpiarFormularioModal(formSelector) {
        if (!formSelector || !$(formSelector).length) return;
        $(formSelector)[0].reset();
        $(formSelector).find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');$(formSelector).find('.feedback-validacion').remove();
    }

    async function cargarTiposActivos() {
        return await peticionAjax(`${urlModulo}&ajax=tipos_activos`, { type: 'GET' });
    }

    async function llenarSelectTipos() {
        try {
            const respuesta = await cargarTiposActivos();
            const opciones = '<option value="">Seleccione un tipo...</option>';
            $('#id_tipo_materia_prima, #edit_activo_id_tipo, #edit_inactivo_id_tipo').empty().append(opciones);

            if (respuesta && Array.isArray(respuesta.tipos)) {
                respuesta.tipos.forEach(function (tipo) {
                    const opcion = `<option value="${tipo.id_tipo_materia_prima}">${escaparHTML(tipo.nombre_de_material)}</option>`;
                    $('#id_tipo_materia_prima, #edit_activo_id_tipo, #edit_inactivo_id_tipo').append(opcion);
                });
            }
        } catch (error) {
            console.error('Error al cargar tipos de materia prima:', error);
        }
    }

    async function cargarMateriasPrimas() {
        try {
            const respuesta = await peticionAjax(`${urlModulo}&ajax=listar`, { type: 'GET' });
            if (respuesta && Array.isArray(respuesta.materias_primas)) {
                todasLasMateriasPrimas = respuesta.materias_primas;
            } else {
                todasLasMateriasPrimas = [];
            }
            renderizarTabla(verEliminados ? 'Inactivo' : 'Activo');
        } catch (error) {
            console.error('Error:', error);
            alerta('error', 'Error', 'No se pudieron cargar las materias primas.');
        }
    }

    function renderizarTabla(estadoFiltro) {
        const tabla = $('#tablaMateriaPrima');
        const tbody = tabla.find('tbody');

        if ($.fn.DataTable.isDataTable('#tablaMateriaPrima')) {
            tablaDataTable.destroy();
            tablaDataTable = null;
        }

        tbody.empty();

        const filtrados = todasLasMateriasPrimas.filter(mp => mp.status_materia_prima === estadoFiltro);

        filtrados.forEach(function (mp) {
            const id = mp.id_materia_prima;
            const nombre = mp.nombre_materia_prima;
            const tipo = mp.nombre_de_material || 'Sin tipo';
            const costo = mp.costo_unitario;
            const stockActual = mp.stock_actual;
            const stockMinimo = mp.stock_minimo;
            const unidad = mp.unidad_de_medida;
            const estado = mp.status_materia_prima;

            const badge = estado === 'Activo'
                ? '<span class="badge bg-success">Activo</span>'
                : '<span class="badge bg-danger">Inactivo</span>';

            let acciones = '';

            if (estado === 'Activo') {
                acciones = `
                    <button type="button" class="btn btn-sm btn-outline-primary btnEditarActivo me-1" 
                        data-id="${id}" 
                        data-nombre="${escaparHTML(nombre)}" 
                        data-id-tipo="${mp.id_tipo_materia_prima}" 
                        data-costo="${costo}" 
                        data-stock-actual="${stockActual}" 
                        data-stock-minimo="${stockMinimo}" 
                        data-unidad="${escaparHTML(unidad)}">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger btnCambiarEstado" 
                        data-id="${id}" 
                        data-nombre="${escaparHTML(nombre)}">
                        <i class="bi bi-trash3-fill"></i>
                    </button>
                `;
            } else {
                acciones = `
                    <button type="button" class="btn btn-sm btn-outline-warning btnEditarInactivo" 
                        data-id="${id}" 
                        data-nombre="${escaparHTML(nombre)}" 
                        data-id-tipo="${mp.id_tipo_materia_prima}" 
                        data-costo="${costo}" 
                        data-stock-actual="${stockActual}" 
                        data-stock-minimo="${stockMinimo}" 
                        data-unidad="${escaparHTML(unidad)}"
                        data-estado="${estado}">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                `;
            }

            tbody.append(`
                <tr>
                    <td class="fw-bold">${escaparHTML(nombre)}</td>
                    <td>${escaparHTML(tipo)}</td>
                    <td>${costo}</td>
                    <td>${stockActual} ${escaparHTML(unidad)}</td>
                    <td>${stockMinimo} ${escaparHTML(unidad)}</td>
                    <td>${badge}</td>
                    <td><div class="text-center">${acciones}</div></td>
                </tr>
            `);
        });

        // Configuración con Traducción Manual de DataTables
        tablaDataTable = tabla.DataTable({
            language: {
                processing: 'Procesando...',
                search: 'Buscar:',
                lengthMenu: 'Mostrar _MENU_ registros',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                infoFiltered: '(filtrado de _MAX_ registros en total)',
                zeroRecords: 'No se encontraron coincidencias',
                emptyTable: 'No hay registros',
                loadingRecords: 'Cargando...',
                paginate: {
                    first: 'Primero',
                    last: 'Último',
                    next: 'Siguiente',
                    previous: 'Anterior'
                },
                aria: {
                    sortAscending: ': Activar para ordenar la columna de manera ascendente',
                    sortDescending: ': Activar para ordenar la columna de manera descendente'
                }
            },
            pageLength: 10,
            responsive: true,
            ordering: false
        });
    }

    // Alternar vista entre activos e inhabilitados
    $('#btnAlternarEstado').on('click', function () {
        verEliminados = !verEliminados;
        if (verEliminados) {
            $(this).removeClass('btn-outline-secondary').addClass('btn-secondary');$('#txtBotonEstado').text('Ver Activos');
            $('#iconoEstado').removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
            $('#tituloVista').text('Materias Primas Inhabilitadas');
            renderizarTabla('Inactivo');
        } else {
            $(this).removeClass('btn-secondary').addClass('btn-outline-secondary');$('#txtBotonEstado').text('Ver inhabilitados');
            $('#iconoEstado').removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
            $('#tituloVista').text('Materia Prima');
            renderizarTabla('Activo');
        }
    });

    $('#modalRegistrarMateriaPrima').on('show.bs.modal', async function () {
        await llenarSelectTipos();
    });

    // Eventos de limpieza
    $('#modalRegistrarMateriaPrima, #modalEditarActivo, #modalEditarInactivo').on('click', '[data-bs-dismiss="modal"]', function () {
        if ($(this).text().trim().toLowerCase() === 'cancelar') {
            const formId = $(this).closest('.modal').find('form').attr('id');
            if (formId) limpiarFormularioModal('#' + formId);
        }
    });

    // Registrar Materia Prima
    $('#btnEnvio').on('click', async function (e) {
        e.preventDefault();
        const inputNombre = $('#nombre_materia_prima');
        const selectTipo = $('#id_tipo_materia_prima');
        const inputCosto = $('#costo_unitario');
        const inputStockActual = $('#stock_actual');
        const inputStockMinimo = $('#stock_minimo');
        const selectUnidad = $('#unidad_de_medida');

        if (!validarCampo(inputNombre, 'nombre', 'El nombre debe tener entre 2 y 100 caracteres.')) return;

        if (selectTipo.val() === '') {
            selectTipo.addClass('is-invalid');
            alerta('error', 'Error', 'Seleccione un tipo.');
            return;
        }

        if (!validarNumero(inputCosto, 'numeroNoNegativo', 'El costo debe ser un valor no negativo.')) return;
        if (!validarNumero(inputStockActual, 'numeroNoNegativo', 'El stock actual debe ser un valor no negativo.')) return;
        if (!validarNumero(inputStockMinimo, 'numeroNoNegativo', 'El stock mínimo debe ser un valor no negativo.')) return;

        if (selectUnidad.val() === '') {
            selectUnidad.addClass('is-invalid');
            alerta('error', 'Error', 'Seleccione una unidad.');
            return;
        }

        try {
            const respuesta = await peticionAjax(urlModulo, {
                type: 'POST',
                data: {
                    nombre: inputNombre.val().trim(),
                    id_tipo_materia_prima: selectTipo.val(),
                    costo_unitario: inputCosto.val().trim(),
                    stock_actual: inputStockActual.val().trim(),
                    stock_minimo: inputStockMinimo.val().trim(),
                    unidad_de_medida: selectUnidad.val()
                }
            });

            if (respuesta.success) {
                await alerta('success', 'Completado', respuesta.message, '#10b981');
                limpiarFormularioModal('#formMateriaPrima');
                bootstrap.Modal.getInstance(document.getElementById('modalRegistrarMateriaPrima')).hide();
                await cargarMateriasPrimas();
            } else {
                alerta('error', 'Error', respuesta.message);
            }
        } catch (error) {
            alerta('error', 'Error', error.message || 'Error de comunicación.');
        }
    });

    // Abrir Modal EDITAR ACTIVO
    $('body').on('click', '.btnEditarActivo', async function () {
        console.log('Click en Editar Activo');

        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const idTipo = $(this).data('id-tipo');
        const costo = $(this).data('costo');
        const stockActual = $(this).data('stock-actual');
        const stockMinimo = $(this).data('stock-minimo');
        const unidad = $(this).data('unidad');

        $('#edit_activo_id').val(id);
        $('#edit_activo_nombre').val(nombre);
        $('#edit_activo_costo').val(costo);
        $('#edit_activo_stock_actual').val(stockActual);
        $('#edit_activo_stock_minimo').val(stockMinimo);
        $('#edit_activo_unidad').val(unidad);

        await llenarSelectTipos();
        $('#edit_activo_id_tipo').val(idTipo);
        const modal = new bootstrap.Modal(document.getElementById('modalEditarActivo'));
        modal.show();
        console.log('Modal Editar Activo mostrado');
    });

    // Abrir Modal EDITAR INACTIVO
    $('body').on('click', '.btnEditarInactivo', async function () {
        console.log('Click en Editar Inactivo');

        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const idTipo = $(this).data('id-tipo');
        const costo = $(this).data('costo');
        const stockActual = $(this).data('stock-actual');
        const stockMinimo = $(this).data('stock-minimo');
        const unidad = $(this).data('unidad');
        const estado = $(this).data('estado') || 'Inactivo';

        $('#edit_inactivo_id').val(id);
        $('#edit_inactivo_nombre').val(nombre);
        $('#edit_inactivo_costo').val(costo);
        $('#edit_inactivo_stock_actual').val(stockActual);
        $('#edit_inactivo_stock_minimo').val(stockMinimo);
        $('#edit_inactivo_unidad').val(unidad);
        $('#edit_inactivo_estado').val(estado);

        await llenarSelectTipos();
        $('#edit_inactivo_id_tipo').val(idTipo);
        const modal = new bootstrap.Modal(document.getElementById('modalEditarInactivo'));
        modal.show();
        console.log('Modal Editar Inactivo mostrado');
    });

    // Guardar Edición Activo
    $('#btnGuardarEdicionActivo').on('click', async function (e) {
        e.preventDefault();
        const id = $('#edit_activo_id').val();
        const inputNombre = $('#edit_activo_nombre');
        const selectTipo = $('#edit_activo_id_tipo');
        const inputCosto = $('#edit_activo_costo');
        const inputStockActual = $('#edit_activo_stock_actual');
        const inputStockMinimo = $('#edit_activo_stock_minimo');
        const selectUnidad = $('#edit_activo_unidad');

        if (!validarCampo(inputNombre, 'nombre', 'Nombre inválido.')) return;

        if (selectTipo.val() === '') {
            selectTipo.addClass('is-invalid');
            alerta('error', 'Error', 'Seleccione un tipo.');
            return;
        }

        if (!validarNumero(inputCosto, 'numeroNoNegativo', 'Costo inválido.')) return;
        if (!validarNumero(inputStockActual, 'numeroNoNegativo', 'Stock actual inválido.')) return;
        if (!validarNumero(inputStockMinimo, 'numeroNoNegativo', 'Stock mínimo inválido.')) return;

        if (selectUnidad.val() === '') {
            selectUnidad.addClass('is-invalid');
            alerta('error', 'Error', 'Seleccione una unidad.');
            return;
        }

        try {
            const respuesta = await peticionAjax(urlModulo, {
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
                }
            });

            if (respuesta.success) {
                await alerta('success', 'Actualizado', respuesta.message, '#10b981');
                limpiarFormularioModal('#formEditarActivo');
                bootstrap.Modal.getInstance(document.getElementById('modalEditarActivo')).hide();
                await cargarMateriasPrimas();
            } else {
                alerta('error', 'Error', respuesta.message);
            }
        } catch (error) {
            alerta('error', 'Error', error.message || 'No se pudo actualizar.');
        }
    });

    // Guardar Edición Inactivo
    $('#btnGuardarEdicionInactivo').on('click', async function (e) {
        e.preventDefault();
        const id = $('#edit_inactivo_id').val();
        const inputNombre = $('#edit_inactivo_nombre');
        const selectTipo = $('#edit_inactivo_id_tipo');
        const inputCosto = $('#edit_inactivo_costo');
        const inputStockActual = $('#edit_inactivo_stock_actual');
        const inputStockMinimo = $('#edit_inactivo_stock_minimo');
        const selectUnidad = $('#edit_inactivo_unidad');
        const selectEstado = $('#edit_inactivo_estado');

        if (!validarCampo(inputNombre, 'nombre', 'Nombre inválido.')) return;

        if (selectTipo.val() === '') {
            selectTipo.addClass('is-invalid');
            alerta('error', 'Error', 'Seleccione un tipo.');
            return;
        }

        if (!validarNumero(inputCosto, 'numeroNoNegativo', 'Costo inválido.')) return;
        if (!validarNumero(inputStockActual, 'numeroNoNegativo', 'Stock actual inválido.')) return;
        if (!validarNumero(inputStockMinimo, 'numeroNoNegativo', 'Stock mínimo inválido.')) return;

        if (selectUnidad.val() === '') {
            selectUnidad.addClass('is-invalid');
            alerta('error', 'Error', 'Seleccione una unidad.');
            return;
        }

        if (selectEstado.val() === '') {
            selectEstado.addClass('is-invalid');
            alerta('error', 'Error', 'Seleccione un estado.');
            return;
        }

        try {
            const respuesta = await peticionAjax(urlModulo, {
                type: 'POST',
                data: {
                    id_accion: id,
                    nuevo_estado: selectEstado.val(),
                    nombre: inputNombre.val().trim(),
                    id_tipo_materia_prima: selectTipo.val(),
                    costo_unitario: inputCosto.val().trim(),
                    stock_actual: inputStockActual.val().trim(),
                    stock_minimo: inputStockMinimo.val().trim(),
                    unidad_de_medida: selectUnidad.val()
                }
            });

            if (respuesta.success) {
                await alerta('success', 'Actualizado', respuesta.message, '#10b981');
                limpiarFormularioModal('#formEditarInactivo');
                bootstrap.Modal.getInstance(document.getElementById('modalEditarInactivo')).hide();
                await cargarMateriasPrimas();
            } else {
                alerta('error', 'Error', respuesta.message);
            }
        } catch (error) {
            alerta('error', 'Error', error.message || 'No se pudo actualizar.');
        }
    });

    // Inhabilitar Registro
    $('body').on('click', '.btnCambiarEstado', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');

        Swal.fire({
            title: '¿Inhabilitar?',
            text: 'El registro "' + nombre + '" pasará a inhabilitados.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Sí',
            cancelButtonText: 'Cancelar'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const respuesta = await peticionAjax(urlModulo, {
                        type: 'POST',
                        data: { id_accion: id, nuevo_estado: 'Inactivo' }
                    });

                    if (respuesta.success) {
                        await alerta('success', 'Inhabilitado', respuesta.message, '#10b981');
                        await cargarMateriasPrimas();
                    } else {
                        alerta('error', 'Error', respuesta.message);
                    }
                } catch (error) {
                    alerta('error', 'Error', error.message || 'No se pudo inhabilitar.');
                }
            }
        });
    });

    // Inicializar módulo
    cargarMateriasPrimas();
});