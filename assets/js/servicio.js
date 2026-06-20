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

    function validarCampoVisual(input, regex, mensajeError) {
        if (!input) return false;
        
        let feedback = input.nextElementSibling;
        if (!feedback || !feedback.classList.contains("feedback-validación")) {
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
            console.warn("Invalido: " + input.id);
            return false;
        }
    }

    const txtNombre = document.getElementById("nombre_servicio");
    const editNombre = document.getElementById("edit_nombre_servicio");

    if (txtNombre) txtNombre.addEventListener("keydown", filtrarTecladoAlfaNumerico);
    if (editNombre) editNombre.addEventListener("keydown", filtrarTecladoAlfaNumerico);

    // =========================================================================
    // CONTROL DE ACCESIBILIDAD (Modales Bootstrap)
    // =========================================================================
    const modalRegistrarEl = document.getElementById('modalRegistrarServicio');
    if (modalRegistrarEl) {
        modalRegistrarEl.addEventListener('shown.bs.modal', () => txtNombre && txtNombre.focus());
        modalRegistrarEl.addEventListener('hide.bs.modal', function () {
            if (document.activeElement) document.activeElement.blur(); 
        });
    }

    const modalEditarEl = document.getElementById('modalEditarServicio');
    if (modalEditarEl) {
        modalEditarEl.addEventListener('shown.bs.modal', () => editNombre && editNombre.focus());
        modalEditarEl.addEventListener('hide.bs.modal', function () {
            if (document.activeElement) document.activeElement.blur();
        });
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
            console.log("AJAX POST guardar:", txtNombre.value.trim());
            
            fetch('index.php?controller=servicio&action=guardar', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                console.log("Servidor response (Guardar):", data);
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
                            formRegistrar.reset();
                            txtNombre.classList.remove('is-valid');
                            const feedback = txtNombre.nextElementSibling;
                            if (feedback && feedback.classList.contains("feedback-validación")) {
                                feedback.remove();
                            }
                            bootstrap.Modal.getInstance(modalRegistrarEl).hide();
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
                        didClose: () => { if (txtNombre) txtNombre.focus(); }
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
            console.log("AJAX POST editar ID:", document.getElementById("edit_id_servicio").value);
            
            fetch('index.php?controller=servicio&action=editar', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                console.log("Servidor response (Editar):", data);
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
                            bootstrap.Modal.getInstance(modalEditarEl).hide();
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
                        didClose: () => { if (editNombre) editNombre.focus(); }
                    });
                }
            }).catch(err => console.error("Error crítico HTTP editar:", err));
        });
    }
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
                console.log("Bitácora DataTables: Carga completa. Registros mapeados:", listaServiciosGlobal.length);
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

                let botones = '';
                if (modo === 'eliminados') {
                    botones = `<button class="btn btn-sm btn-outline-primary btn-editar" style="border-radius:8px;" data-servicio='${JSON.stringify(serv).replace(/'/g, "&apos;")}'><i class="bi bi-pencil-square"></i> Reestablecer</button>`;
                } else {
                    botones = `<button class="btn btn-sm btn-outline-primary btn-editar me-1" style="border-radius:8px;" data-servicio='${JSON.stringify(serv).replace(/'/g, "&apos;")}'><i class="bi bi-pencil-square"></i></button>
                               <button class="btn btn-sm btn-outline-danger btn-eliminar" style="border-radius:8px;" data-id="${serv.id_servicio}" data-nombre="${serv.nombre_servicio}"><i class="bi bi-trash3"></i></button>`;
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
        console.log("Mutación de visualización de tabla de servicios:", verEliminados ? "Filtro Inhabilitados" : "Filtro Activos");
        
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

        console.log("Intercepción de datos para modal edición. ID objetivo:", data.id_servicio);

        $('#edit_id_servicio').val(data.id_servicio);
        $('#edit_nombre_servicio').val(data.nombre_servicio);
        
        $('#edit_nombre_servicio').removeClass('is-valid is-invalid');
        const feedback = $('#edit_nombre_servicio').next('.feedback-validación');
        if (feedback.length) feedback.remove();

        if (data.status_servicio === 'inactivo') {
            $('#edit_status_servicio').val('inactivo');
            $('#contenedor_edit_estado').slideDown(250);
        } else {
            $('#edit_status_servicio').val('activo');
            $('#contenedor_edit_estado').hide();
        }

        new bootstrap.Modal(document.getElementById('modalEditarServicio')).show();
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
                console.log("Despachando baja lógica vía Fetch API para ID:", id);
                fetch(`index.php?controller=servicio&action=eliminar&id=${id}`)
                .then(res => res.json())
                .then(data => {
                    console.log("Servidor response (Baja):", data);
                    
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