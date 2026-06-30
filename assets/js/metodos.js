document.addEventListener("DOMContentLoaded", function () {
    if ($.fn.DataTable.isDataTable('#tablaMetodos')) {
        $('#tablaMetodos').DataTable().destroy();
    }

    const tabla = $('#tablaMetodos').DataTable({
        responsive: true,
        order: [[0, "asc"]],
        language: {
            lengthMenu: "Mostrar _MENU_ registros por página",
            zeroRecords: "No se encontraron métodos de pago comerciales",
            info: "Mostrando página _PAGE_ de _PAGES_",
            infoEmpty: "No hay registros disponibles",
            infoFiltered: "(filtrado de _MAX_ registros totales)",
            search: "Buscar:",
            paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" }
        }
    });

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
                titulo.innerText = "Canales Inhabilitados";
                tabla.column(2).search("Inhabilitado").draw();
            } else {
                this.setAttribute("data-vista", "activos");
                this.classList.replace("btn-secondary", "btn-outline-secondary");
                txtBoton.innerText = "Ver inhabilitados";
                icono.classList.replace("bi-eye-fill", "bi-eye-slash-fill");
                titulo.innerText = "Métodos de Pago";
                tabla.column(2).search("Activo").draw();
            }
        });
        tabla.column(2).search("Activo").draw();
    }

    // Formulario Registro
    const formRegistrar = document.getElementById('formRegistrarMetodo');
    if (formRegistrar) {
        formRegistrar.addEventListener('submit', function (e) {
            e.preventDefault();
            fetch('index.php?controller=finanzas&action=guardarMetodo', { method: 'POST', body: new FormData(formRegistrar) })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    $('#modalRegistrarMetodo').modal('hide');
                    Swal.fire({ icon: 'success', title: '¡Guardado!', text: 'El nuevo método de pago está listo.', confirmButtonColor: '#2b4c7e' }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error de Validación', text: data.message || 'No se pudo guardar.', confirmButtonColor: '#d33' });
                }
            }).catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' }));
        });
    }

    // Formulario Edición
    const formEditar = document.getElementById('formEditarMetodo');
    if (formEditar) {
        formEditar.addEventListener('submit', function (e) {
            e.preventDefault();
            fetch('index.php?controller=finanzas&action=guardarMetodo', { method: 'POST', body: new FormData(formEditar) })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    $('#modalEditarMetodo').modal('hide');
                    Swal.fire({ icon: 'success', title: '¡Actualizado!', text: 'El método fue renombrado con éxito.', confirmButtonColor: '#2b4c7e' }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error de Validación', text: data.message || 'No se pudo editar.', confirmButtonColor: '#d33' });
                }
            }).catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' }));
        });
    }
});

function limpiarFormularioCrear() {
    const form = document.getElementById('formRegistrarMetodo');
    if (form) form.reset();
}

function cargarDatosEdicion(metodo) {
    const form = document.getElementById('formEditarMetodo');
    if (form) form.reset();

    document.getElementById('edit_id_metodo').value = metodo.id_metodo_de_pago;
    
    const selectEditar = document.getElementById('edit_nombre_metodo');
    if (selectEditar) {
        selectEditar.value = metodo.nombre_metodo;
    }
}

function cambiarEstadoMetodo(idMetodo, nuevoEstado) {
    const tituloAlerta = nuevoEstado === 0 ? '¿Inhabilitar método?' : '¿Habilitar método?';
    const textoAlerta = nuevoEstado === 0 ? 'Las cuentas y los abonos vinculados a este canal se congelarán de la vista.' : 'El método volverá a estar disponible.';
    
    Swal.fire({
        title: tituloAlerta, text: textoAlerta, icon: 'warning', showCancelButton: true,
        confirmButtonColor: nuevoEstado === 0 ? '#d33' : '#2b4c7e', cancelButtonColor: '#6e7881',
        confirmButtonText: nuevoEstado === 0 ? 'Sí, inhabilitar' : 'Sí, activar', cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('id_metodo_de_pago', idMetodo);
            formData.append('estado', nuevoEstado);
            
            fetch('index.php?controller=finanzas&action=inhabilitarMetodo', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({ icon: 'success', title: '¡Completado!', text: 'Estado actualizado.', confirmButtonColor: '#2b4c7e' }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo cambiar el estado.', confirmButtonColor: '#d33' });
                }
            }).catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' }));
        }
    });
}