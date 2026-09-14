/**
 * Idéalo - Gestión de Servicios
 * Validaciones, Control de Accesibilidad y Operaciones Asíncronas (DataTables/AJAX)
 */

document.addEventListener("DOMContentLoaded", function () {
    console.log("Modulo de Servicios: Validaciones nativas cargadas correctamente.");

    const regexServicio = /^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ\s]{3,50}$/;

    function filtrarTecladoAlfaNumerico(e) {
        if (['Backspace', 'Tab', 'ArrowLeft', 'ArrowRight', 'Delete', 'Enter'].includes(e.key)) return;
        if (!/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ\s]$/.test(e.key)) {
            e.preventDefault();
        }
    }

    function limpiarFormulario(formElement, inputElement) {
        if (formElement) formElement.reset();
        if (inputElement) {
            inputElement.classList.remove('is-valid', 'is-invalid');
            const feedback = inputElement.parentNode.querySelector(".feedback-validación");
            if (feedback) feedback.remove();
        }
    }

    function validarCampoVisual(input, regex, mensajeError) {
        if (!input) return false;
        
        let feedback = input.parentNode.querySelector(".feedback-validación");
        if (!feedback) {
            feedback = document.createElement("small");
            feedback.classList.add("feedback-validación", "form-text");
            input.parentNode.appendChild(feedback);
        }

        const valorLimpio = input.value.trim();

        if (regex.test(valorLimpio)) {
            input.classList.remove("is-invalid");
            input.classList.add("is-valid");
            feedback.textContent = "Nombre válido";
            feedback.style.color = "#198754";
            return true;
        } else {
            input.classList.remove("is-valid");
            input.classList.add("is-invalid");
            feedback.textContent = mensajeError;
            feedback.style.color = "#dc3545";
            console.warn("Inválido: " + input.id);
            return false;
        }
    }

    const txtNombre = document.getElementById("nombre_servicio");
    const editNombre = document.getElementById("edit_nombre_servicio");

    if (txtNombre) txtNombre.addEventListener("keydown", filtrarTecladoAlfaNumerico);
    if (editNombre) editNombre.addEventListener("keydown", filtrarTecladoAlfaNumerico);

    // =========================================================================
    // CONTROL DE ACCESIBILIDAD Y MODALES (BOOTSTRAP)
    // =========================================================================
    const modalRegistrarEl = document.getElementById('modalRegistrarServicio');
    let bsModalRegistrar = null;
    if (modalRegistrarEl) {
        bsModalRegistrar = bootstrap.Modal.getOrCreateInstance(modalRegistrarEl);

        modalRegistrarEl.addEventListener('shown.bs.modal', () => txtNombre && txtNombre.focus());

        // Al ocultar por X o Clic afuera: solo limpiamos estilos de validación, sin borrar texto
        modalRegistrarEl.addEventListener('hide.bs.modal', function () {
            if (document.activeElement) document.activeElement.blur(); 
            if (txtNombre) {
                txtNombre.classList.remove('is-valid', 'is-invalid');
                const feedback = txtNombre.parentNode.querySelector(".feedback-validación");
                if (feedback) feedback.remove();
            }
        });

        // Botón Cancelar explícito: limpia el formulario completamente
        const btnCancelarRegistrar = modalRegistrarEl.querySelector('[data-bs-dismiss="modal"]:not(.btn-close)');
        if (btnCancelarRegistrar) {
            btnCancelarRegistrar.addEventListener('click', () => {
                limpiarFormulario(document.getElementById("formRegistrarServicio"), txtNombre);
            });
        }
    }

    const modalEditarEl = document.getElementById('modalEditarServicio');
    let bsModalEditar = null;
    if (modalEditarEl) {
        bsModalEditar = bootstrap.Modal.getOrCreateInstance(modalEditarEl);

        modalEditarEl.addEventListener('shown.bs.modal', () => editNombre && editNombre.focus());

        modalEditarEl.addEventListener('hide.bs.modal', function () {
            if (document.activeElement) document.activeElement.blur();
            if (editNombre) {
                editNombre.classList.remove('is-valid', 'is-invalid');
                const feedback = editNombre.parentNode.querySelector(".feedback-validación");
                if (feedback) feedback.remove();
            }
        });

        // Botón Cancelar explícito para edición
        const btnCancelarEditar = modalEditarEl.querySelector('[data-bs-dismiss="modal"]:not(.btn-close)');
        if (btnCancelarEditar) {
            btnCancelarEditar.addEventListener('click', () => {
                limpiarFormulario(document.getElementById("formEditarServicio"), editNombre);
            });
        }
    }

    // =========================================================================
    // GUARDAR NUEVO SERVICIO via Fetch API
    // =========================================================================
    const formRegistrar = document.getElementById("formRegistrarServicio");
    if (formRegistrar) {
        formRegistrar.addEventListener("submit", function (e) {
            e.preventDefault();

            if (!validarCampoVisual(txtNombre, regexServicio, "Mínimo 3 caracteres, sin símbolos especiales.")) {
                return;
            }

            const formData = new FormData(formRegistrar);
            
            fetch('index.php?controller=servicio&action=guardar', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (document.activeElement) document.activeElement.blur();

                    Swal.fire({ 
                        icon: 'success', 
                        title: '¡Registrado!', 
                        text: data.message, 
                        confirmButtonColor: '#10b981',
                        focusConfirm: false,
                        heightAuto: false, 
                        didClose: () => {
                            limpiarFormulario(formRegistrar, txtNombre);

                            if (bsModalRegistrar) bsModalRegistrar.hide();
                            window.recargarTablaServicios();
                        }
                    });
                } else {
                    if (document.activeElement) document.activeElement.blur();
                    Swal.fire({ 
                        icon: 'error', 
                        title: 'Error de validación', 
                        text: data.message, 
                        confirmButtonColor: '#dc3545',
                        focusConfirm: false,
                        heightAuto: false,
                        didClose: () => { 
                            if (bsModalRegistrar) bsModalRegistrar.show();
                        }
                    });
                }
            }).catch(err => console.error("Error crítico HTTP guardar:", err));
        });
    }

    // =========================================================================
    // EDITAR SERVICIO EXISTENTE via Fetch API
    // =========================================================================
    const formEditar = document.getElementById("formEditarServicio");
    if (formEditar) {
        formEditar.addEventListener("submit", function (e) {
            e.preventDefault();

            if (!validarCampoVisual(editNombre, regexServicio, "Mínimo 3 caracteres, sin símbolos especiales.")) {
                return;
            }

            const formData = new FormData(formEditar);
            
            fetch('index.php?controller=servicio&action=editar', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (document.activeElement) document.activeElement.blur();

                    Swal.fire({ 
                        icon: 'success', 
                        title: '¡Actualizado!', 
                        text: data.message, 
                        confirmButtonColor: '#10b981',
                        focusConfirm: false,
                        heightAuto: false,
                        didClose: () => {
                            if (bsModalEditar) bsModalEditar.hide();
                            window.recargarTablaServicios();
                        }
                    });
                } else {
                    if (document.activeElement) document.activeElement.blur();
                    Swal.fire({ 
                        icon: 'error', 
                        title: 'Error al actualizar', 
                        text: data.message, 
                        confirmButtonColor: '#dc3545',
                        focusConfirm: false,
                        heightAuto: false,
                        didClose: () => { 
                            if (bsModalEditar) bsModalEditar.show();
                        }
                    });
                }
            }).catch(err => console.error("Error crítico HTTP editar:", err));
        });
    }

    // Exposición de apertura manual para integración con DataTables
    window.abrirModalEditarServicio = function(data) {
        $('#edit_id_servicio').val(data.id_servicio);
        $('#edit_nombre_servicio').val(data.nombre_servicio);
        
        $('#edit_nombre_servicio').removeClass('is-valid is-invalid');
        const feedback = $('#edit_nombre_servicio').parentNode ? $('#edit_nombre_servicio').parentNode.querySelector(".feedback-validación") : null;
        if (feedback) feedback.remove();

        if (data.status_servicio === 'inactivo') {
            $('#edit_status_servicio').val('inactivo');
            $('#contenedor_edit_estado').slideDown(250);
        } else {
            $('#edit_status_servicio').val('activo');
            $('#contenedor_edit_estado').hide();
        }

        if (bsModalEditar) {
            bsModalEditar.show();
        }
    };
});

// =========================================================================
// ASINCRONÍA Y GESTIÓN DE DATATABLES (JQUERY COMPATIBLE)
// =========================================================================
$(document).ready(function() {
    let datatableInstancia = null;
    let listaServiciosGlobal = [];
    let verEliminados = false;

    function cargarServicios() {
        fetch('index.php?controller=servicio&action=listarServiciosAjax')
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    console.error("GET ajax error:", data.error);
                    return;
                }
                listaServiciosGlobal = data;
                renderizarTablaServicios(verEliminados ? 'eliminados' : 'activos');
            })
            .catch(err => console.error("Error de comunicación HTTP listar:", err));
    }

    window.recargarTablaServicios = cargarServicios;

    function renderizarTablaServicios(modo) {
        const tbody = $('#tbodyServicios');
        
        if ($.fn.DataTable.isDataTable('#tablaServicios')) {
            datatableInstancia.destroy();
        }
        tbody.empty();

        const filtrados = listaServiciosGlobal.filter(serv => {
            let esActivo = serv.status_servicio === 'activo';
            return modo === 'eliminados' ? !esActivo : esActivo;
        });

        if (filtrados.length > 0) {
            filtrados.forEach(serv => {
                let badge = serv.status_servicio === 'activo'
                    ? '<span class="badge bg-success-subtle text-success" style="padding: 6px 12px; border-radius: 6px;">Activo</span>'
                    : '<span class="badge bg-danger-subtle text-danger" style="padding: 6px 12px; border-radius: 6px;">Inactivo</span>';

                // Botón único de edición estándar para ambas vistas
                let btnEditar = `<button class="btn btn-sm btn-outline-primary btn-editar me-1" style="border-radius:8px;" data-servicio='${JSON.stringify(serv).replace(/'/g, "&apos;")}'><i class="bi bi-pencil-square"></i></button>`;

                let botones = '';
                if (modo === 'eliminados') {
                    botones = btnEditar;
                } else {
                    botones = `${btnEditar}<button class="btn btn-sm btn-outline-danger btn-eliminar" style="border-radius:8px;" data-id="${serv.id_servicio}" data-nombre="${serv.nombre_servicio}"><i class="bi bi-trash3"></i></button>`;
                }

                tbody.append(`<tr>
                    <td class="px-4"><div class="fw-semibold text-dark">${serv.nombre_servicio}</div></td>
                    <td class="px-4">${badge}</td>
                    <td class="px-4 text-center">${botones}</td>
                </tr>`);
            });
        }

        datatableInstancia = $('#tablaServicios').DataTable({
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
            pageLength: 10,
            responsive: true,
            ordering: false,
            dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>'
        });
    }

    $('#btnAlternarEstado').on('click', function() {
        verEliminados = !verEliminados;
        
        if (verEliminados) {
            $(this).attr('data-vista', 'eliminados').removeClass('btn-outline-secondary').addClass('btn-secondary');
            $('#txtBotonEstado').text('Ver Activos');
            $('#iconoEstado').removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
            $('#tituloVista').text('Servicios (Inhabilitados)');
            renderizarTablaServicios('eliminados');
        } else {
            $(this).attr('data-vista', 'activos').removeClass('btn-secondary').addClass('btn-outline-secondary');
            $('#txtBotonEstado').text('Ver Inhabilitados');
            $('#iconoEstado').removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
            $('#tituloVista').text('Servicios');
            renderizarTablaServicios('activos');
        }
    });

    $(document).on('click', '.btn-editar', function() {
        const data = $(this).data('servicio');
        if (!data) return;
        window.abrirModalEditarServicio(data);
    });

    $(document).on('click', '.btn-eliminar', function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');

        if (document.activeElement) document.activeElement.blur();

        Swal.fire({
            title: '¿Inhabilitar este servicio?',
            text: `¿Desea cambiar el estado de "${nombre}" a inactivo dentro del catálogo?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, inhabilitar',
            cancelButtonText: 'Cancelar',
            focusConfirm: false,
            heightAuto: false
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`index.php?controller=servicio&action=eliminar&id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ 
                            icon: 'success', 
                            title: 'Inhabilitado', 
                            text: data.message, 
                            confirmButtonColor: '#10b981',
                            focusConfirm: false,
                            heightAuto: false
                        });
                        cargarServicios();
                    } else {
                        Swal.fire({ 
                            icon: 'error', 
                            title: 'Error de base de datos', 
                            text: data.message, 
                            confirmButtonColor: '#dc3545',
                            focusConfirm: false,
                            heightAuto: false
                        });
                    }
                }).catch(err => console.error("Error crítico HTTP en baja lógica:", err));
            }
        });
    });

    cargarServicios();
});