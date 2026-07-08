document.addEventListener('DOMContentLoaded', function () {
    const tablaBody = document.getElementById('tbodyPerdidaMaterial'); 
    const formRegistrarPerdida = document.getElementById('formRegistrarPerdida');
    const formEditarPerdida = document.getElementById('formEditarPerdida');
    const modalRegistrarPerdidaElement = document.getElementById('modalRegistrarPerdida');
    const modalEditarPerdidaElement = document.getElementById('modalEditarPerdida');

    let perdidas = [];

    // Esta es la función que genera la alerta. 
    // Si SweetAlert2 está en tu HTML, se verá como en el video.
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
                fetchPerdidas();
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

    function fetchPerdidas() {
        // Asegúrate de que esta URL coincida con tu controlador PHP
        fetch('index.php?url=perdidaMaterial/listar&accion=listar')
            .then(response => response.json())
            .then(data => {
                if (data.success && Array.isArray(data.data)) {
                    perdidas = data.data;
                    renderizarTabla();
                } else if (Array.isArray(data)) {
                    perdidas = data;
                    renderizarTabla();
                } else {
                    console.warn('Respuesta inesperada al cargar las pérdidas de material:', data);
                }
            })
            .catch(error => {
                console.error('Error al cargar las pérdidas de material:', error);
                // Si ocurre un error en la petición AJAX, mantenemos la tabla HTML estática renderizada por PHP.
            });
    }

    function renderizarTabla() {
        if (!tablaBody) return;

        tablaBody.innerHTML = '';

        if (!Array.isArray(perdidas)) {
            perdidas = [];
        }

        perdidas.forEach(perdida => {
            const produccionLabel = perdida.descripcion_pedido
                ? (perdida.cantidad_detalle
                    ? `Pedido de ${perdida.cantidad_detalle} ${perdida.descripcion_pedido}`
                    : perdida.descripcion_pedido)
                : `Orden #${perdida.id_produccion || 'N/A'}`;

            const fila = document.createElement('tr');

            fila.innerHTML = `
                <td><strong>#${perdida.id_perdida_material}</strong></td>
                <td>${perdida.cantidad_perdida || 0}</td>
                <td>${perdida.fecha_de_registro || 'N/A'}</td>
                <td>${perdida.motivo || 'Sin motivo especificado'}</td>
                <td>$${perdida.costo_unitario || '0.00'}</td>
                <td>${produccionLabel}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-warning btnEditarPerdida" 
                        data-id_perdida="${perdida.id_perdida_material}" 
                        data-cantidad="${perdida.cantidad_perdida}" 
                        data-fecha="${perdida.fecha_de_registro}" 
                        data-costo="${perdida.costo_unitario}" 
                        data-id_produccion="${perdida.id_produccion}"
                        data-motivo="${perdida.motivo}">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                </td>
            `;
            tablaBody.appendChild(fila);
        });

        if (perdidas.length === 0) {
            const fila = document.createElement('tr');
            fila.innerHTML = '<td colspan="7" class="text-center text-muted py-4">No hay pérdidas registradas para mostrar.</td>';
            tablaBody.appendChild(fila);
        }
    }

    if (formRegistrarPerdida) {
        formRegistrarPerdida.addEventListener('submit', function (event) {
            event.preventDefault();
            enviarFormulario(formRegistrarPerdida, modalRegistrarPerdidaElement);
        });
    }

    if (formEditarPerdida) {
        formEditarPerdida.addEventListener('submit', function (event) {
            event.preventDefault();
            enviarFormulario(formEditarPerdida, modalEditarPerdidaElement);
        });
    }

    document.addEventListener('click', function (event) {
        const target = event.target.closest('.btnEditarPerdida');
        if (!target) return;

        const id_perdida = target.getAttribute('data-id_perdida') || '';
        const cantidad = target.getAttribute('data-cantidad') || '';
        const fecha = target.getAttribute('data-fecha') || '';
        const costo = target.getAttribute('data-costo') || '';
        const id_produccion = target.getAttribute('data-id_produccion') || '';
        const motivo = target.getAttribute('data-motivo') || '';

        const editId = document.getElementById('edit_id_perdida_material');
        const editCantidad = document.getElementById('edit_cantidad_perdida');
        const editFecha = document.getElementById('edit_fecha_de_registro');
        const editCosto = document.getElementById('edit_costo_unitario');
        const editProduccion = document.getElementById('edit_id_produccion');
        const editMotivo = document.getElementById('edit_motivo');

        if (editId) editId.value = id_perdida;
        if (editCantidad) editCantidad.value = cantidad;
        if (editFecha) editFecha.value = fecha;
        if (editCosto) editCosto.value = costo;
        if (editProduccion) editProduccion.value = id_produccion;
        if (editMotivo) editMotivo.value = motivo;

        const modal = new bootstrap.Modal(document.getElementById('modalEditarPerdida'));
        modal.show();
    });

    fetchPerdidas();
});