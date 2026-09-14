$(document).ready(function () {

    let tablaDataTable = null;
    let todasLasMateriasPrimas = [];
    let verEliminados = false;

    const regexNombre = /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-\(\)\.\/]{3,100}$/;
    const urlModulo = 'index.php?controller=materiaPrima&action=listar';

    console.log('JS cargado');

    function escaparHTML(texto) {
        return $('<div>').text(texto ?? '').html();
    }

    function validarCampo(input, regex, mensajeError) {
        if (!input || input.length === 0) return false;
        const domInput = input[0];
        $(domInput).siblings('.feedback-validacion').remove();
        const feedback = document.createElement('small');
        feedback.classList.add('feedback-validacion', 'form-text', 'd-block', 'mt-1');
        domInput.parentNode.appendChild(feedback);
        const valor = input.val().trim();
        if (valor === '') {
            input.removeClass('is-valid').addClass('is-invalid');
            feedback.textContent = 'Este campo no puede estar vacío.';
            feedback.style.color = '#dc3545';
            return false;
        }
        if (regex.test(valor)) {
            input.removeClass('is-invalid').addClass('is-valid');
            feedback.textContent = 'Campo válido';
            feedback.style.color = '#198754';
            return true;
        }
        input.removeClass('is-valid').addClass('is-invalid');
        feedback.textContent = mensajeError;
        feedback.style.color = '#dc3545';
        return false;
    }

    function validarNumero(input, mensajeError) {
        if (!input || input.length === 0) return false;
        const domInput = input[0];
        $(domInput).siblings('.feedback-validacion').remove();
        const feedback = document.createElement('small');
        feedback.classList.add('feedback-validacion', 'form-text', 'd-block', 'mt-1');
        domInput.parentNode.appendChild(feedback);
        const valor = input.val().trim();
        if (valor === '') {
            input.removeClass('is-valid').addClass('is-invalid');
            feedback.textContent = 'Este campo no puede estar vacío.';
            feedback.style.color = '#dc3545';
            return false;
        }
        const numero = parseFloat(valor);
        if (isNaN(numero) || numero < 0) {
            input.removeClass('is-valid').addClass('is-invalid');
            feedback.textContent = mensajeError;
            feedback.style.color = '#dc3545';
            return false;
        }
        input.removeClass('is-invalid').addClass('is-valid');
        feedback.textContent = 'Campo válido';
        feedback.style.color = '#198754';
        return true;
    }

    function limpiarFormularioModal(formSelector) {
        if (!formSelector || !$(formSelector).length) return;
        $(formSelector)[0].reset();
        $(formSelector).find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        $(formSelector).find('.feedback-validacion').remove();
    }

    function cargarTiposActivos() {
        return $.ajax({
            url: `${urlModulo}&ajax=tipos_activos`,
            type: 'GET',
            dataType: 'json'
        });
    }

    function llenarSelectTipos() {
        return cargarTiposActivos().done(function (respuesta) {
            const opciones = '<option value="">Seleccione un tipo...</option>';
            $('#id_tipo_materia_prima, #edit_activo_id_tipo, #edit_inactivo_id_tipo').empty().append(opciones);

            if (respuesta && Array.isArray(respuesta.tipos)) {
                respuesta.tipos.forEach(function (tipo) {
                    const opcion = `<option value="${tipo.id_tipo_materia_prima}">${tipo.nombre_de_material}</option>`;
                    $('#id_tipo_materia_prima, #edit_activo_id_tipo, #edit_inactivo_id_tipo').append(opcion);
                });
            }
        });
    }

    function cargarMateriasPrimas() {
        $.ajax({
            url: `${urlModulo}&ajax=listar`,
            type: 'GET',
            dataType: 'json',
            success: function (respuesta) {
                if (respuesta && Array.isArray(respuesta.materias_primas)) {
                    todasLasMateriasPrimas = respuesta.materias_primas;
                } else {
                    todasLasMateriasPrimas = [];
                }
                renderizarTabla(verEliminados ? 'Inactivo' : 'Activo');
            },
            error: function (xhr, status, error) {
                console.error('Error:', error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar las materias primas.', confirmButtonColor: '#dc3545' });
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
                        data-nombre="${nombre}" 
                        data-id-tipo="${mp.id_tipo_materia_prima}" 
                        data-costo="${costo}" 
                        data-stock-actual="${stockActual}" 
                        data-stock-minimo="${stockMinimo}" 
                        data-unidad="${unidad}">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger btnCambiarEstado" 
                        data-id="${id}" 
                        data-nombre="${nombre}">
                        <i class="bi bi-trash3-fill"></i>
                    </button>
                `;
            } else {
                acciones = `
                    <button type="button" class="btn btn-sm btn-outline-warning btnEditarInactivo" 
                        data-id="${id}" 
                        data-nombre="${nombre}" 
                        data-id-tipo="${mp.id_tipo_materia_prima}" 
                        data-costo="${costo}" 
                        data-stock-actual="${stockActual}" 
                        data-stock-minimo="${stockMinimo}" 
                        data-unidad="${unidad}"
                        data-estado="${estado}">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                `;
            }

            tbody.append(`
                <tr>
                    <td class="fw-bold">${nombre}</td>
                    <td>${tipo}</td>
                    <td>${costo}</td>
                    <td>${stockActual} ${unidad}</td>
                    <td>${stockMinimo} ${unidad}</td>
                    <td>${badge}</td>
                    <td><div class="text-center">${acciones}</div></td>
                </tr>
            `);
        });

        tablaDataTable = tabla.DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                emptyTable: 'No hay registros',
                zeroRecords: 'No se encontraron coincidencias'
            },
            pageLength: 10,
            responsive: true,
            ordering: false
        });
    }

    $('#btnAlternarEstado').on('click', function () {
        verEliminados = !verEliminados;
        if (verEliminados) {
            $(this).removeClass('btn-outline-secondary').addClass('btn-secondary');
            $('#txtBotonEstado').text('Ver Activos');
            $('#iconoEstado').removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
            $('#tituloVista').text('Materias Primas Inhabilitadas');
            renderizarTabla('Inactivo');
        } else {
            $(this).removeClass('btn-secondary').addClass('btn-outline-secondary');
            $('#txtBotonEstado').text('Ver inhabilitados');
            $('#iconoEstado').removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
            $('#tituloVista').text('Materia Prima');
            renderizarTabla('Activo');
        }
    });

    $('#modalRegistrarMateriaPrima').on('show.bs.modal', function () {
        llenarSelectTipos();
    });

    // Eventos de limpieza
    $('#modalRegistrarMateriaPrima, #modalEditarActivo, #modalEditarInactivo').on('click', '[data-bs-dismiss="modal"]', function () {
        if ($(this).text().trim().toLowerCase() === 'cancelar') {
            const formId = $(this).closest('.modal').find('form').attr('id');
            if (formId) limpiarFormularioModal('#' + formId);
        }
    });

    // Registrar
    $('#btnEnvio').on('click', function (e) {
        e.preventDefault();
        const inputNombre = $('#nombre_materia_prima');
        const selectTipo = $('#id_tipo_materia_prima');
        const inputCosto = $('#costo_unitario');
        const inputStockActual = $('#stock_actual');
        const inputStockMinimo = $('#stock_minimo');
        const selectUnidad = $('#unidad_de_medida');

        if (!validarCampo(inputNombre, regexNombre, 'El nombre debe tener entre 3 y 100 caracteres.')) return;
        if (selectTipo.val() === '') {
            selectTipo.addClass('is-invalid');
            Swal.fire({ icon: 'error', title: 'Error', text: 'Seleccione un tipo.', confirmButtonColor: '#dc3545' });
            return;
        }
        if (!validarNumero(inputCosto, 'El costo debe ser >= 0.')) return;
        if (!validarNumero(inputStockActual, 'El stock debe ser >= 0.')) return;
        if (!validarNumero(inputStockMinimo, 'El stock debe ser >= 0.')) return;
        if (selectUnidad.val() === '') {
            selectUnidad.addClass('is-invalid');
            Swal.fire({ icon: 'error', title: 'Error', text: 'Seleccione una unidad.', confirmButtonColor: '#dc3545' });
            return;
        }

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
                    Swal.fire({ icon: 'success', title: 'Completado', text: respuesta.message, confirmButtonColor: '#10b981' });
                    limpiarFormularioModal('#formMateriaPrima');
                    bootstrap.Modal.getInstance(document.getElementById('modalRegistrarMateriaPrima')).hide();
                    cargarMateriasPrimas();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: respuesta.message, confirmButtonColor: '#dc3545' });
                }
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación.', confirmButtonColor: '#dc3545' });
            }
        });
    });

    // EDITAR ACTIVO
    $('body').on('click', '.btnEditarActivo', function () {
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

        llenarSelectTipos().done(function () {
            $('#edit_activo_id_tipo').val(idTipo);
            const modal = new bootstrap.Modal(document.getElementById('modalEditarActivo'));
            modal.show();
            console.log('Modal Editar Activo mostrado');
        });
    });

    // EDITAR INACTIVO
    $('body').on('click', '.btnEditarInactivo', function () {
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

        llenarSelectTipos().done(function () {
            $('#edit_inactivo_id_tipo').val(idTipo);
            const modal = new bootstrap.Modal(document.getElementById('modalEditarInactivo'));
            modal.show();
            console.log('Modal Editar Inactivo mostrado');
        });
    });

    // Guardar edición activo
    $('#btnGuardarEdicionActivo').on('click', function (e) {
        e.preventDefault();
        const id = $('#edit_activo_id').val();
        const inputNombre = $('#edit_activo_nombre');
        const selectTipo = $('#edit_activo_id_tipo');
        const inputCosto = $('#edit_activo_costo');
        const inputStockActual = $('#edit_activo_stock_actual');
        const inputStockMinimo = $('#edit_activo_stock_minimo');
        const selectUnidad = $('#edit_activo_unidad');

        if (!validarCampo(inputNombre, regexNombre, 'Nombre inválido.')) return;
        if (selectTipo.val() === '') {
            selectTipo.addClass('is-invalid');
            Swal.fire({ icon: 'error', title: 'Error', text: 'Seleccione un tipo.', confirmButtonColor: '#dc3545' });
            return;
        }
        if (!validarNumero(inputCosto, 'Costo inválido.')) return;
        if (!validarNumero(inputStockActual, 'Stock inválido.')) return;
        if (!validarNumero(inputStockMinimo, 'Stock inválido.')) return;
        if (selectUnidad.val() === '') {
            selectUnidad.addClass('is-invalid');
            Swal.fire({ icon: 'error', title: 'Error', text: 'Seleccione una unidad.', confirmButtonColor: '#dc3545' });
            return;
        }

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
                    Swal.fire({ icon: 'success', title: 'Actualizado', text: respuesta.message, confirmButtonColor: '#10b981' });
                    limpiarFormularioModal('#formEditarActivo');
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarActivo')).hide();
                    cargarMateriasPrimas();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: respuesta.message, confirmButtonColor: '#dc3545' });
                }
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo actualizar.', confirmButtonColor: '#dc3545' });
            }
        });
    });

    // Guardar edición inactivo
    $('#btnGuardarEdicionInactivo').on('click', function (e) {
        e.preventDefault();
        const id = $('#edit_inactivo_id').val();
        const inputNombre = $('#edit_inactivo_nombre');
        const selectTipo = $('#edit_inactivo_id_tipo');
        const inputCosto = $('#edit_inactivo_costo');
        const inputStockActual = $('#edit_inactivo_stock_actual');
        const inputStockMinimo = $('#edit_inactivo_stock_minimo');
        const selectUnidad = $('#edit_inactivo_unidad');
        const selectEstado = $('#edit_inactivo_estado');

        if (!validarCampo(inputNombre, regexNombre, 'Nombre inválido.')) return;
        if (selectTipo.val() === '') {
            selectTipo.addClass('is-invalid');
            Swal.fire({ icon: 'error', title: 'Error', text: 'Seleccione un tipo.', confirmButtonColor: '#dc3545' });
            return;
        }
        if (!validarNumero(inputCosto, 'Costo inválido.')) return;
        if (!validarNumero(inputStockActual, 'Stock inválido.')) return;
        if (!validarNumero(inputStockMinimo, 'Stock inválido.')) return;
        if (selectUnidad.val() === '') {
            selectUnidad.addClass('is-invalid');
            Swal.fire({ icon: 'error', title: 'Error', text: 'Seleccione una unidad.', confirmButtonColor: '#dc3545' });
            return;
        }
        if (selectEstado.val() === '') {
            selectEstado.addClass('is-invalid');
            Swal.fire({ icon: 'error', title: 'Error', text: 'Seleccione un estado.', confirmButtonColor: '#dc3545' });
            return;
        }

        $.ajax({
            url: urlModulo,
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
            },
            dataType: 'json',
            success: function (respuesta) {
                if (respuesta.success) {
                    Swal.fire({ icon: 'success', title: 'Actualizado', text: respuesta.message, confirmButtonColor: '#10b981' });
                    limpiarFormularioModal('#formEditarInactivo');
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarInactivo')).hide();
                    cargarMateriasPrimas();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: respuesta.message, confirmButtonColor: '#dc3545' });
                }
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo actualizar.', confirmButtonColor: '#dc3545' });
            }
        });
    });

    // Inhabilitar
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
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: urlModulo,
                    type: 'POST',
                    data: { id_accion: id, nuevo_estado: 'Inactivo' },
                    dataType: 'json',
                    success: function (respuesta) {
                        if (respuesta.success) {
                            Swal.fire({ icon: 'success', title: 'Inhabilitado', text: respuesta.message, confirmButtonColor: '#10b981' });
                            cargarMateriasPrimas();
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: respuesta.message, confirmButtonColor: '#dc3545' });
                        }
                    },
                    error: function () {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo inhabilitar.', confirmButtonColor: '#dc3545' });
                    }
                });
            }
        });
    });

    cargarMateriasPrimas();
});