document.addEventListener('DOMContentLoaded', function () {
    const tablaBody = document.getElementById('tbodyConsumos');
    const formRegistrarConsumo = document.getElementById('formRegistrarConsumo');
    const formEditarConsumo = document.getElementById('formEditarConsumo');
    const modalRegistrarConsumoElement = document.getElementById('modalRegistrarConsumo');
    const modalEditarConsumoElement = document.getElementById('modalEditarConsumo');

    let consumos = [];

    function mostrarAlerta(tipo, titulo, texto) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: tipo,
                title: titulo,
                text: texto,
                timer: 2200,
                showConfirmButton: false,
                timerProgressBar: true
            });
        } else {
            alert(titulo + '\n' + texto);
        }
    }

    async function enviarFormulario(form, modalElement) {
        if (!form) return;

        const submitButton = form.querySelector('button[type="submit"]');
        const textoOriginal = submitButton ? submitButton.textContent : '';
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Enviando...';
        }

        try {
            const actionUrl = form.getAttribute('action') || form.action;
            if (!actionUrl) {
                throw new Error('URL de acción del formulario no encontrada.');
            }

            const response = await fetch(actionUrl, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                mostrarAlerta('success', 'Operación exitosa', data.message || 'Guardado correctamente.');
                if (modalElement) {
                    const modalInstance = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
                    modalInstance.hide();
                }
                form.reset();
                fetchConsumos();
            } else {
                mostrarAlerta('error', 'Error', data.message || 'No se pudo procesar la solicitud.');
            }
        } catch (error) {
            console.error('Error al enviar el formulario:', error);
            mostrarAlerta('error', 'Error', 'No se pudo conectar con el servidor.');
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = textoOriginal;
            }
        }
    }

    function fetchConsumos() {
        fetch('index.php?controller=consumoMaterial&action=listar&accion=listar')
            .then(response => response.json())
            .then(data => {
                if (data.success && Array.isArray(data.data)) {
                    consumos = data.data;
                } else if (Array.isArray(data)) {
                    consumos = data;
                } else {
                    consumos = [];
                    console.warn('Respuesta inesperada al cargar los consumos:', data);
                }
                renderizarTabla();
            })
            .catch(error => {
                console.error('Error al cargar los consumos:', error);
                consumos = [];
                renderizarTabla();
            });
    }

    function renderizarTabla() {
        if (!tablaBody) return;

        tablaBody.innerHTML = '';

        if (!Array.isArray(consumos) || consumos.length === 0) {
            const fila = document.createElement('tr');
            fila.innerHTML = '<td colspan="7" class="text-center text-muted py-4">No hay consumos registrados para mostrar.</td>';
            tablaBody.appendChild(fila);
            return;
        }

        consumos.forEach(consumo => {
            const costoTotal = (Number(consumo.costo_unitario || 0) * Number(consumo.cantidad_usada || 0)).toFixed(2);
            const fila = document.createElement('tr');

            fila.innerHTML = `
                <td class="fw-bold">#${consumo.id_consumo_material || ''}</td>
                <td>
                    <div class="fw-bold text-dark">${consumo.nombre_materia_prima || 'Sin material'}</div>
                    <small class="text-muted"><i class="bi bi-info-circle"></i> ${consumo.descripcion_de_consumo || 'Sin descripción'}</small>
                </td>
                <td class="fw-bold text-muted">$${Number(consumo.costo_unitario || 0).toFixed(2)}</td>
                <td class="fw-bold">${consumo.cantidad_usada || 0} ${consumo.unidad_de_medida || ''}</td>
                <td class="text-success fw-bold">$${costoTotal}</td>
                <td><span class="badge bg-secondary">OP-${String(consumo.id_produccion || 0).padStart(4, '0')}</span></td>
                <td>
                    <div class="text-center d-flex justify-content-center gap-1">
                        <button type="button"
                            class="btn btn-sm btn-outline-primary btnEditarConsumo"
                            data-id_consumo_material="${consumo.id_consumo_material || ''}"
                            data-id_materia_prima="${consumo.id_materia_prima || ''}"
                            data-costo_unitario="${consumo.costo_unitario || ''}"
                            data-cantidad_usada="${consumo.cantidad_usada || ''}"
                            data-descripcion_de_consumo="${consumo.descripcion_de_consumo || ''}"
                            data-id_produccion="${consumo.id_produccion || ''}">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                    </div>
                </td>
            `;
            tablaBody.appendChild(fila);
        });
    }

    if (formRegistrarConsumo) {
        formRegistrarConsumo.addEventListener('submit', function (event) {
            event.preventDefault();
            enviarFormulario(formRegistrarConsumo, modalRegistrarConsumoElement);
        });
    }

    if (formEditarConsumo) {
        formEditarConsumo.addEventListener('submit', function (event) {
            event.preventDefault();
            enviarFormulario(formEditarConsumo, modalEditarConsumoElement);
        });
    }

    document.addEventListener('click', function (event) {
        const target = event.target.closest('.btnEditarConsumo');
        if (!target) return;

        const editIdConsumo = document.getElementById('edit_id_consumo_material');
        const editIdMateria = document.getElementById('edit_id_materia_prima');
        const editIdProduccion = document.getElementById('edit_id_produccion');
        const editCosto = document.getElementById('edit_costo_unitario');
        const editCantidad = document.getElementById('edit_cantidad_usada');
        const editDescripcion = document.getElementById('edit_descripcion_de_consumo');

        if (editIdConsumo) editIdConsumo.value = target.dataset.id_consumo_material || '';
        if (editIdMateria) editIdMateria.value = target.dataset.id_materia_prima || '';
        if (editIdProduccion) editIdProduccion.value = target.dataset.id_produccion || '';
        if (editCosto) editCosto.value = target.dataset.costo_unitario || '';
        if (editCantidad) editCantidad.value = target.dataset.cantidad_usada || '';
        if (editDescripcion) editDescripcion.value = target.dataset.descripcion_de_consumo || '';

        const modal = new bootstrap.Modal(document.getElementById('modalEditarConsumo'));
        modal.show();
    });

    fetchConsumos();
});
