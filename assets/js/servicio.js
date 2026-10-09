document.addEventListener("DOMContentLoaded", function () {
    const $txtNombre =$("#nombre_servicio");
    const $editNombre =$("#edit_nombre_servicio");

    function bloqEntradaNoPermitida(input, tipoPatron) {
        if (!window.jQuery || !$.expresionesRegulares) return;

        input.on("keypress", function (e) {
            if (e.ctrlKey || e.altKey || e.metaKey || e.which < 32) return;
            const valActual = $(this).val();
            const nuevoVal = valActual.slice(0, this.selectionStart ?? valActual.length) + String.fromCharCode(e.which) + valActual.slice(this.selectionEnd ?? valActual.length);

            try {
                const fuente = $.expresionesRegulares.obtener(tipoPatron).source.replace(/^\^\vert{}\$$/g, "");
                const regexParcial = new RegExp("^" + (fuente.includes("{") ? fuente.replace(/\{[^}]+\}/, "+") : fuente) + "$");
                if (!regexParcial.test(nuevoVal)) e.preventDefault();
            } catch (err) {}
        });

        input.on("paste", function (e) {
            const pasted = (e.originalEvent || e).clipboardData?.getData("text") || "";
            const valActual = $(this).val();
            const nuevoVal = valActual.slice(0, this.selectionStart ?? valActual.length) + pasted + valActual.slice(this.selectionEnd ?? valActual.length);

            try {
                if (!$.expresionesRegulares.validar(tipoPatron, nuevoVal)) e.preventDefault();
            } catch (err) {}
        });
    }

    function validarCampoEntrada(input, tipoPatron, esRequerido = true) {
        if (!input || !input.length) return false;
        const valor = input.val().trim();
        let $feedback = input.siblings(".feedback-validacion");

        if (!$feedback.length) {
            $feedback =$('<small class="feedback-validacion form-text d-block"></small>').insertAfter(input);
        }

        if (!valor) {
            input.toggleClass("is-invalid", esRequerido).toggleClass("is-valid", !esRequerido);
            $feedback.text(esRequerido ? "Este campo es obligatorio." : "").css("color", "#dc3545").toggle(esRequerido);
            return !esRequerido;
        }

        const esValido = $.expresionesRegulares?.validar ? $.expresionesRegulares.validar(tipoPatron, valor) : true;
        input.toggleClass("is-valid", esValido).toggleClass("is-invalid", !esValido);
        $feedback.text(esValido ? "Nombre válido" : "Formato o longitud inválida según reglas.")
                 .css("color", esValido ? "#198754" : "#dc3545").show();

        return esValido;
    }

    function configurarCampo(selector, tipoPatron, esRequerido = true) {
        const $el =$(selector);
        bloqEntradaNoPermitida($el, tipoPatron);$(document).on("input change", selector, () => validarCampoEntrada($el, tipoPatron, esRequerido));
    }

    configurarCampo("#nombre_servicio", "nombre", true);
    configurarCampo("#edit_nombre_servicio", "nombre", true);

    function limpiarFormulario(formSelector, inputsJQuery = null) {
        const $target = formSelector ? $(formSelector) : inputsJQuery;
        if (!$target) return;
        if (formSelector && $target[0]) $target[0].reset();$target.find('.is-valid, .is-invalid').addBack('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        $target.siblings(".feedback-validacion").add($target.find(".feedback-validacion")).remove();
    }

    const enviarPeticion = (url, data) => $.ajax({
        url, type: 'POST', data, dataType: 'json',
        processData: !(data instanceof FormData),
        contentType: (data instanceof FormData) ? false : 'application/x-www-form-urlencoded; charset=UTF-8'
    });

    function prepararModal(idModal, $input, selectorForm) {
        const el = document.getElementById(idModal);
        if (!el) return null;
        const bsModal = bootstrap.Modal.getOrCreateInstance(el);
        el.addEventListener('shown.bs.modal', () => $input.focus());
        el.addEventListener('hide.bs.modal', () => { document.activeElement?.blur(); limpiarFormulario(null, $input); });
        el.querySelector('[data-bs-dismiss="modal"]:not(.btn-close)')?.addEventListener('click', () => limpiarFormulario(selectorForm));
        return bsModal;
    }

    const bsModalRegistrar = prepararModal('modalRegistrarServicio', $txtNombre, "#formRegistrarServicio");
    const bsModalEditar = prepararModal('modalEditarServicio', $editNombre, "#formEditarServicio");

    async function manejarSubmit(e, $input, form, url, bsModal, tituloExito) {
        e.preventDefault();
        if (!validarCampoEntrada($input, "nombre", true)) return;

        try {
            const data = await enviarPeticion(url, new FormData(form));
            document.activeElement?.blur();
            
            Swal.fire({
                icon: data.success ? 'success' : 'error',
                title: data.success ? tituloExito : 'Error',
                text: data.message,
                confirmButtonColor: data.success ? '#10b981' : '#dc3545',
                focusConfirm: false, heightAuto: false,
                didClose: () => {
                    if (data.success) {
                        limpiarFormulario(form);
                        bsModal?.hide();
                        window.recargarTablaServicios();
                    } else {
                        bsModal?.show();
                    }
                }
            });
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.', confirmButtonColor: '#dc3545', focusConfirm: false, heightAuto: false });
        }
    }

    document.getElementById("formRegistrarServicio")?.addEventListener("submit", (e) => 
        manejarSubmit(e, $txtNombre, e.target, 'index.php?controller=servicio&action=guardar', bsModalRegistrar, '¡Registrado!')
    );

    document.getElementById("formEditarServicio")?.addEventListener("submit", (e) => 
        manejarSubmit(e, $editNombre, e.target, 'index.php?controller=servicio&action=editar', bsModalEditar, '¡Actualizado!')
    );

    window.abrirModalEditarServicio = function (data) {
        $('#edit_id_servicio').val(data.id_servicio);
        $('#edit_nombre_servicio').val(data.nombre_servicio);
        limpiarFormulario(null, $('#edit_nombre_servicio'));

        const esInactivo = data.status_servicio === 'inactivo';
        $('#edit_status_servicio').val(esInactivo ? 'inactivo' : 'activo');
        $('#contenedor_edit_estado').toggle(esInactivo);

        bsModalEditar?.show();
    };
});

$(document).ready(function () {
    let datatableInstancia = null;
    let listaServiciosGlobal = [];
    let verEliminados = false;

    // Objeto de idioma en español configurado de forma local
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
        emptyTable:     "No hay datos disponibles en la tabla",
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

    async function cargarServicios() {
        try {
            const data = await $.ajax({ url: 'index.php?controller=servicio&action=listarServiciosAjax', type: 'GET', dataType: 'json' });
            if (data.error) return;
            listaServiciosGlobal = Array.isArray(data) ? data : [];
            renderizarTablaServicios(verEliminados ? 'eliminados' : 'activos');
        } catch (err) {}
    }

    window.recargarTablaServicios = cargarServicios;

    function renderizarTablaServicios(modo) {
        const tbody = $('#tbodyServicios');
        if ($.fn.DataTable.isDataTable('#tablaServicios')) datatableInstancia.destroy();
        tbody.empty();

        const filtrados = listaServiciosGlobal.filter(s => modo === 'eliminados' ? s.status_servicio !== 'activo' : s.status_servicio === 'activo');

        if (filtrados.length > 0) {
            const fragmento = $(document.createDocumentFragment());
            filtrados.forEach(serv => {
                const esActivo = serv.status_servicio === 'activo';
                const badge = `<span class="badge bg-${esActivo ? 'success' : 'danger'}-subtle text-${esActivo ? 'success' : 'danger'}" style="padding: 6px 12px; border-radius: 6px;">${esActivo ? 'Activo' : 'Inactivo'}</span>`;
                const btnEditar = `<button class="btn btn-sm btn-outline-primary btn-editar me-1" style="border-radius:8px;" data-servicio='${JSON.stringify(serv).replace(/'/g, "&apos;")}'>
                    <i class="bi bi-pencil-square"></i>
                </button>`;
                const btnEliminar = `<button class="btn btn-sm btn-outline-danger btn-eliminar" style="border-radius:8px;" data-id="${serv.id_servicio}" data-nombre="${serv.nombre_servicio}">
                    <i class="bi bi-trash3"></i>
                </button>`;

                fragmento.append(`
                    <tr>
                        <td class="px-4"><div class="fw-semibold text-dark">${serv.nombre_servicio}</div></td>
                        <td class="px-4">${badge}</td>
                        <td class="px-4 text-center">${btnEditar}${modo === 'eliminados' ? '' : btnEliminar}</td>
                    </tr>
                `);
            });
            tbody.append(fragmento);
        }

        datatableInstancia = $('#tablaServicios').DataTable({
            language: lenguajeEspanolDataTables,
            pageLength: 10,
            responsive: true,
            ordering: false,
            dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>'
        });
    }

    $('#btnAlternarEstado').on('click', function () {
        verEliminados = !verEliminados;
        $(this).attr('data-vista', verEliminados ? 'eliminados' : 'activos')
               .toggleClass('btn-outline-secondary', !verEliminados)
               .toggleClass('btn-secondary', verEliminados);
        $('#txtBotonEstado').text(verEliminados ? 'Ver Activos' : 'Ver Inhabilitados');
        $('#iconoEstado').toggleClass('bi-eye-slash-fill', !verEliminados).toggleClass('bi-eye-fill', verEliminados);
        $('#tituloVista').text(verEliminados ? 'Servicios (Inhabilitados)' : 'Servicios');
        renderizarTablaServicios(verEliminados ? 'eliminados' : 'activos');
    });

    $(document).on('click', '.btn-editar', function () {
        const data = $(this).data('servicio');
        if (data) window.abrirModalEditarServicio(data);
    });

    $(document).on('click', '.btn-eliminar', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        document.activeElement?.blur();

        Swal.fire({
            title: '¿Inhabilitar este servicio?',
            text: `¿Desea cambiar el estado de "${nombre}" a inactivo dentro del catálogo?`,
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, inhabilitar', cancelButtonText: 'Cancelar',
            focusConfirm: false, heightAuto: false
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const data = await $.ajax({ url: `index.php?controller=servicio&action=eliminar&id=${id}`, type: 'GET', dataType: 'json' });
                    Swal.fire({
                        icon: data.success ? 'success' : 'error',
                        title: data.success ? 'Inhabilitado' : 'Error de base de datos',
                        text: data.message,
                        confirmButtonColor: data.success ? '#10b981' : '#dc3545',
                        focusConfirm: false, heightAuto: false
                    });
                    if (data.success) cargarServicios();
                } catch (err) {
                    Swal.fire({ icon: 'error', title: 'Error de comunicación', text: 'No se pudo completar la solicitud.', confirmButtonColor: '#dc3545', focusConfirm: false, heightAuto: false });
                }
            }
        });
    });

    cargarServicios();
});