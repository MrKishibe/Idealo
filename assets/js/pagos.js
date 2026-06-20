document.addEventListener("DOMContentLoaded", function () {
    // Inicializar DataTable de forma segura
    if ($.fn.DataTable.isDataTable('#tablaPagos')) {
        $('#tablaPagos').DataTable().destroy();
    }

    const tabla = $('#tablaPagos').DataTable({
        responsive: true,
        order: [[4, "desc"]],
        language: {
            lengthMenu: "Mostrar _MENU_ registros por página",
            zeroRecords: "No se encontraron pagos registrados",
            info: "Mostrando página _PAGE_ de _PAGES_",
            infoEmpty: "No hay registros disponibles",
            infoFiltered: "(filtrado de _MAX_ registros totales)",
            search: "Buscar:",
            paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" }
        }
    });

    // Control del filtrado por estados (Procesados / Inhabilitados)
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
                txtBoton.innerText = "Ver Procesados";
                icono.classList.replace("bi-eye-slash-fill", "bi-eye-fill");
                titulo.innerText = "Pagos Inhabilitados";
                tabla.column(5).search("Inhabilitado").draw();
            } else {
                this.setAttribute("data-vista", "activos");
                this.classList.replace("btn-secondary", "btn-outline-secondary");
                txtBoton.innerText = "Ver inhabilitados";
                icono.classList.replace("bi-eye-fill", "bi-eye-slash-fill");
                titulo.innerText = "Control de Pagos";
                tabla.column(5).search("Procesado").draw();
            }
        });
        tabla.column(5).search("Procesado").draw();
    }

    // Manejo de Envío del Formulario de Registro
    const formRegistrar = document.getElementById('formRegistrarPago');
    if (formRegistrar) {
        formRegistrar.addEventListener('submit', function (e) {
            e.preventDefault();
            fetch('index.php?controller=finanzas&action=guardar', { method: 'POST', body: new FormData(formRegistrar) })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Cierre seguro del modal usando jQuery nativo de Bootstrap atributos
                    $('#modalRegistrarPago').modal('hide');
                    Swal.fire({ icon: 'success', title: '¡Registrado!', text: 'El pago ha sido guardado correctamente.', confirmButtonColor: '#2b4c7e' }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error de Validación', text: data.message || 'No se pudo procesar la transacción.', confirmButtonColor: '#d33' });
                }
            }).catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' }));
        });
    }

    // Manejo de Envío del Formulario de Edición
    const formEditar = document.getElementById('formEditarPago');
    if (formEditar) {
        formEditar.addEventListener('submit', function (e) {
            e.preventDefault();
            fetch('index.php?controller=finanzas&action=guardar', { method: 'POST', body: new FormData(formEditar) })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    $('#modalEditarPago').modal('hide');
                    Swal.fire({ icon: 'success', title: '¡Actualizado!', text: 'Los cambios del pago fueron aplicados.', confirmButtonColor: '#2b4c7e' }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error de Validación', text: data.message || 'No se pudieron guardar los cambios.', confirmButtonColor: '#d33' });
                }
            }).catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' }));
        });
    }
});

// Funciones auxiliares invocadas directamente por los clics del HTML
function limpiarFormularioCrear() {
    const form = document.getElementById('formRegistrarPago');
    if (form) form.reset();
    const ahora = new Date();
    ahora.setMinutes(ahora.getMinutes() - ahora.getTimezoneOffset());
    const inputFecha = document.getElementById('fecha_pago');
    if (inputFecha) inputFecha.value = ahora.toISOString().slice(0, 16);
}

function cargarDatosEdicion(pago) {
    const form = document.getElementById('formEditarPago');
    if (form) form.reset();

    document.getElementById('edit_id_pago').value = pago.id_pago;
    document.getElementById('edit_id_pedido').value = pago.id_pedido;
    document.getElementById('edit_monto_pago').value = pago.monto_pago;
    document.getElementById('edit_id_metodo_de_pago').value = pago.id_metodo_de_pago;
    document.getElementById('edit_referencia').value = pago.referencia ? pago.referencia : "";
    if (pago.fecha_pago) {
        document.getElementById('edit_fecha_pago').value = pago.fecha_pago.replace(" ", "T").slice(0, 16);
    }
}

function cambiarEstadoPago(idPago, nuevoEstado) {
    const tituloAlerta = nuevoEstado === 0 ? '¿Inhabilitar pago?' : '¿Habilitar pago?';
    const textoAlerta = nuevoEstado === 0 ? 'El estado de la transacción cambiará a inhabilitado.' : 'El pago volverá a registrarse como procesado.';
    Swal.fire({
        title: tituloAlerta, text: textoAlerta, icon: 'warning', showCancelButton: true,
        confirmButtonColor: nuevoEstado === 0 ? '#d33' : '#2b4c7e', cancelButtonColor: '#6e7881',
        confirmButtonText: nuevoEstado === 0 ? 'Sí, inhabilitar' : 'Sí, restaurar', cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('id_pago', idPago);
            formData.append('estado', nuevoEstado);
            fetch('index.php?controller=finanzas&action=inhabilitar', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({ icon: 'success', title: '¡Estado Cambiado!', text: 'La operación se completó correctamente.', confirmButtonColor: '#2b4c7e' }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo cambiar el estado.', confirmButtonColor: '#d33' });
                }
            }).catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' }));
        }
    });
}