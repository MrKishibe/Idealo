document.addEventListener("DOMContentLoaded", function () {
    if ($.fn.DataTable.isDataTable('#tablaProductos')) {
        $('#tablaProductos').DataTable().destroy();
    }

    const tabla = $('#tablaProductos').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        pageLength: 10,
        responsive: true,
        order: [[0, 'desc']]
    });

    // LÓGICA DE FILTRADO IGUAL A CUENTA EMPRESARIAL
    const btnAlternarEstado = document.getElementById("btnAlternarEstado");
    if (btnAlternarEstado) {
        btnAlternarEstado.addEventListener("click", function () {
            const vistaActual = this.getAttribute("data-vista");
            const txtBoton = document.getElementById("txtBotonEstado");
            const icono = document.getElementById("iconoEstado");
            const titulo = document.getElementById("tituloVista");

            if (vistaActual === "activos") {
                this.setAttribute("data-vista", "inhabilitados");
                this.classList.replace("btn-outline-secondary", "btn-secondary");
                txtBoton.innerText = "Ver Activos";
                icono.classList.replace("bi-eye-slash-fill", "bi-eye-fill");
                titulo.innerText = "Productos Inhabilitados";
                
                // Filtramos la columna 3 (Estado) buscando la palabra inactivo
                tabla.column(3).search("inactivo").draw();
            } else {
                this.setAttribute("data-vista", "activos");
                this.classList.replace("btn-secondary", "btn-outline-secondary");
                txtBoton.innerText = "Ver inhabilitados";
                icono.classList.replace("bi-eye-fill", "bi-eye-slash-fill");
                titulo.innerText = "Catálogo de Productos";
                
                // Filtramos la columna 3 (Estado) buscando la palabra activo
                tabla.column(3).search("activo").draw();
            }
        });

        // Al cargar el módulo, filtra automáticamente para ver solo los activos
        tabla.column(3).search("activo").draw();
    }

    const formRegistrar = document.getElementById('formRegistrarProducto');
    if (formRegistrar) {
        formRegistrar.addEventListener('submit', function (e) {
            e.preventDefault();
            fetch('index.php?controller=producto&action=guardar', { method: 'POST', body: new FormData(formRegistrar) })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    $('#modalRegistrarProducto').modal('hide');
                    Swal.fire({ icon: 'success', title: '¡Registrado!', text: data.message, confirmButtonColor: '#10b981' }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#dc2626' });
                }
            }).catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' }));
        });
    }

    const formEditar = document.getElementById('formEditarProducto');
    if (formEditar) {
        formEditar.addEventListener('submit', function (e) {
            e.preventDefault();
            fetch('index.php?controller=producto&action=guardar', { method: 'POST', body: new FormData(formEditar) })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    $('#modalEditarProducto').modal('hide');
                    Swal.fire({ icon: 'success', title: '¡Actualizado!', text: data.message, confirmButtonColor: '#2b4c7e' }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#dc2626' });
                }
            }).catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' }));
        });
    }
});

function limpiarFormularioCrear() {
    const form = document.getElementById('formRegistrarProducto');
    if (form) form.reset();
}

function cargarDatosEditar(prod) {
    document.getElementById('edit_id_producto').value = prod.id_producto || '';
    document.getElementById('edit_nombre_producto').value = prod.nombre_producto || '';
    document.getElementById('edit_tipo_de_producto').value = prod.tipo_de_producto || '';
    document.getElementById('edit_status_producto').value = prod.status_producto || 'activo';
}

function alternarEstadoProducto(idProducto, estadoActual) {
    const nuevoEstado = (estadoActual === 'activo') ? 'inactivo' : 'activo';
    const tituloAlerta = (nuevoEstado === 'inactivo') ? '¿Inactivar producto?' : '¿Reactivar producto?';
    const textoAlerta = (nuevoEstado === 'inactivo') ? 'El producto se ocultará de esta lista y pasará a la sección de inhabilitados.' : 'El producto volverá al catálogo activo.';
    const colorBoton = (nuevoEstado === 'inactivo') ? '#dc2626' : '#10b981';

    Swal.fire({
        title: tituloAlerta, text: textoAlerta, icon: 'warning', showCancelButton: true,
        confirmButtonColor: colorBoton, cancelButtonColor: '#64748b',
        confirmButtonText: (nuevoEstado === 'inactivo') ? 'Sí, inactivar' : 'Sí, activar', cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('id_producto', idProducto);
            formData.append('status_producto', nuevoEstado);

            fetch('index.php?controller=producto&action=cambiarEstado', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({ icon: 'success', title: '¡Completado!', text: data.message, confirmButtonColor: '#10b981' }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#dc2626' });
                }
            }).catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' }));
        }
    });
}