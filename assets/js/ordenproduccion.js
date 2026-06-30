document.addEventListener('DOMContentLoaded', function () {
    const tablaBody = document.getElementById('tbodyOrdenProduccion');
    const btnAlternarEstado = document.getElementById('btnAlternarEstado');
    const txtBotonEstado = document.getElementById('txtBotonEstado');
    const iconoEstado = document.getElementById('iconoEstado');
    const tituloVista = document.getElementById('tituloVista');

    let verInactivas = false;
    let ordenes = [];

    function fetchOrdenes() {
        // Se agregó '&accion=listar' para que el controlador de PHP detecte la petición y devuelva el JSON
        fetch('index.php?url=ordenproduccion/listarordenproduccion&accion=listar')
            .then(response => response.json())
            .then(data => {
                // El controlador PHP devuelve la data dentro del atributo 'data'
                if (data.success && Array.isArray(data.data)) {
                    ordenes = data.data;
                } else if (Array.isArray(data)) {
                    ordenes = data; // Respaldo por si cambia la estructura
                } else {
                    ordenes = [];
                }
                renderizarTabla();
            })
            .catch(error => {
                console.error('Error al cargar las órdenes de producción:', error);
                ordenes = [];
                renderizarTabla();
            });
    }

    function renderizarTabla() {
        if (!tablaBody) return;

        tablaBody.innerHTML = '';

        if (!Array.isArray(ordenes)) {
            ordenes = [];
        }

        const filtradas = ordenes.filter(orden => {
            const estado = String(orden.estado_de_produccion ?? '').toLowerCase();
            const esInactiva = estado === 'inactiva';
            return verInactivas ? esInactiva : !esInactiva;
        });

        filtradas.forEach(orden => {
            const fila = document.createElement('tr');
            const estado = String(orden.estado_de_produccion ?? '');
            const estadoClase = estado.toLowerCase() === 'finalizado'
                ? 'bg-success text-white'
                : estado.toLowerCase() === 'en proceso'
                    ? 'bg-warning text-dark'
                    : estado.toLowerCase() === 'inactiva'
                        ? 'bg-danger text-white'
                        : 'bg-secondary text-white';

            // Se actualizaron las variables a id_produccion, fecha_terminado y descripcion_pedido
            fila.innerHTML = `
                <td><strong>#${orden.id_produccion}</strong></td>
                <td>${orden.fecha_de_inicio || 'N/A'}</td>
                <td>${orden.fecha_terminado || 'N/A'}</td>
                <td>${orden.descripcion_pedido || 'Sin descripción'} (Cant: ${orden.cantidad || 0})</td>
                <td><span class="badge ${estadoClase}">${estado || 'Sin estado'}</span></td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-warning btnEditarOrden" 
                        data-id_produccion="${orden.id_produccion}" 
                        data-fecha_inicio="${orden.fecha_de_inicio}" 
                        data-fecha_terminado="${orden.fecha_terminado}" 
                        data-id_detalle_pedido="${orden.id_detalle_pedido}" 
                        data-estado="${orden.estado_de_produccion}">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                </td>
            `;
            tablaBody.appendChild(fila);
        });

        if (filtradas.length === 0) {
            const fila = document.createElement('tr');
            fila.innerHTML = '<td colspan="6" class="text-center text-muted py-4">No hay órdenes de producción para mostrar.</td>';
            tablaBody.appendChild(fila);
        }
    }

    if (btnAlternarEstado) {
        btnAlternarEstado.addEventListener('click', function () {
            verInactivas = !verInactivas;
            if (verInactivas) {
                btnAlternarEstado.setAttribute('data-vista', 'inactivas');
                btnAlternarEstado.classList.remove('btn-outline-secondary');
                btnAlternarEstado.classList.add('btn-secondary');
                iconoEstado.classList.remove('bi-eye-slash-fill');
                iconoEstado.classList.add('bi-eye-fill');
                txtBotonEstado.textContent = 'Ver Activas';
                tituloVista.textContent = 'Órdenes Inactivas';
            } else {
                btnAlternarEstado.setAttribute('data-vista', 'activos');
                btnAlternarEstado.classList.remove('btn-secondary');
                btnAlternarEstado.classList.add('btn-outline-secondary');
                iconoEstado.classList.remove('bi-eye-fill');
                iconoEstado.classList.add('bi-eye-slash-fill');
                txtBotonEstado.textContent = 'Ver inactivas';
                tituloVista.textContent = 'Gestión de Órdenes de Producción';
            }
            renderizarTabla();
        });
    }

    document.addEventListener('click', function (event) {
        const target = event.target.closest('.btnEditarOrden');
        if (!target) return;

        // Capturamos los datos actualizados según la base de datos
        const id_produccion = target.getAttribute('data-id_produccion') || '';
        const id_detalle_pedido = target.getAttribute('data-id_detalle_pedido') || '';
        const fechaInicio = target.getAttribute('data-fecha_inicio') || '';
        const fechaTerminado = target.getAttribute('data-fecha_terminado') || '';
        const estado = target.getAttribute('data-estado') || '';

        // Buscamos los elementos del DOM en el modal de edición
        const editId = document.getElementById('edit_id_orden'); // Input oculto
        const editIdDetallePedido = document.getElementById('edit_id_detalle_pedido'); // El nuevo <select>
        const editFechaInicio = document.getElementById('edit_fecha_de_inicio');
        const editFechaTerminado = document.getElementById('edit_fecha_terminado'); // Corregido ID
        const editEstado = document.getElementById('edit_estado_de_produccion');

        // Asignamos los valores a los inputs del modal
        if (editId) editId.value = id_produccion;
        if (editIdDetallePedido) editIdDetallePedido.value = id_detalle_pedido;
        if (editFechaInicio) editFechaInicio.value = fechaInicio;
        if (editFechaTerminado) editFechaTerminado.value = fechaTerminado;
        if (editEstado) editEstado.value = estado;

        const modal = new bootstrap.Modal(document.getElementById('modalEditarOrden'));
        modal.show();
    });

    fetchOrdenes();
});