document.addEventListener("DOMContentLoaded", function () {
    // Inicializar DataTable de forma aislada
    if ($.fn.DataTable.isDataTable('#tablaCuentas')) {
        $('#tablaCuentas').DataTable().destroy();
    }

    const tabla = $('#tablaCuentas').DataTable({
        responsive: true,
        order: [[0, "asc"]],
        language: {
            lengthMenu: "Mostrar _MENU_ registros por página",
            zeroRecords: "No se encontraron cuentas bancarias registradas",
            info: "Mostrando página _PAGE_ de _PAGES_",
            infoEmpty: "No hay registros disponibles",
            infoFiltered: "(filtrado de _MAX_ registros totales)",
            search: "Buscar:",
            paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" }
        }
    });

    // Filtro visual entre Cuentas Activas / Inhabilitadas
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
                txtBoton.innerText = "Ver Activas";
                icono.classList.replace("bi-eye-slash-fill", "bi-eye-fill");
                titulo.innerText = "Cuentas Inhabilitadas";
                tabla.column(4).search("Inhabilitado").draw();
            } else {
                this.setAttribute("data-vista", "activos");
                this.classList.replace("btn-secondary", "btn-outline-secondary");
                txtBoton.innerText = "Ver inhabilitadas";
                icono.classList.replace("bi-eye-fill", "bi-eye-slash-fill");
                titulo.innerText = "Cuentas de la Empresa";
                tabla.column(4).search("Activo").draw();
            }
        });
        tabla.column(4).search("Activo").draw();
    }

    // INTERCEPTOR Y VALIDACIÓN: Formulario de Registro
    const formRegistrar = document.getElementById('formRegistrarCuenta');
    if (formRegistrar) {
        formRegistrar.addEventListener('submit', function (e) {
            e.preventDefault();
            
            const inputIdentificador = formRegistrar.querySelector('[name="identificador"]').value;
            const identificador = inputIdentificador.replace(/[\s-]/g, ''); // Limpiar guiones/espacios para contar
            
            const selectMetodo = formRegistrar.querySelector('[name="id_metodo_de_pago"]');
            const textoMetodo = selectMetodo.options[selectMetodo.selectedIndex].text.toLowerCase();
            const tipoCuenta = formRegistrar.querySelector('[name="tipo_cuenta"]').value.toLowerCase();

            // Validación Banco (20 números obligatorios)
            if (textoMetodo.includes('banco') || textoMetodo.includes('transferencia') || tipoCuenta.includes('corriente') || tipoCuenta.includes('ahorro')) {
                if (identificador.length !== 20 || isNaN(identificador)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Número de Cuenta Inválido',
                        text: 'Una cuenta bancaria tradicional requiere exactamente 20 dígitos numéricos (sin letras ni guiones).',
                        confirmButtonColor: '#d33'
                    });
                    return; 
                }
            }

            // Validación Pago Móvil (11 números obligatorios)
            if (textoMetodo.includes('movil') || textoMetodo.includes('móvil') || tipoCuenta.includes('movil') || tipoCuenta.includes('móvil')) {
                if (identificador.length !== 11 || isNaN(identificador)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Pago Móvil Inválido',
                        text: 'El pago móvil debe configurarse exactamente con el número telefónico de 11 dígitos (Ej: 04121234567).',
                        confirmButtonColor: '#d33'
                    });
                    return;
                }
            }

            // Si es válido, se efectúa el Fetch asíncrono
            fetch('index.php?controller=finanzas&action=guardarCuenta', { method: 'POST', body: new FormData(formRegistrar) })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    $('#modalRegistrarCuenta').modal('hide');
                    Swal.fire({ icon: 'success', title: '¡Guardada!', text: 'La cuenta bancaria ha sido registrada de forma segura.', confirmButtonColor: '#2b4c7e' }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error en Servidor', text: data.message || 'No se pudo guardar la cuenta.', confirmButtonColor: '#d33' });
                }
            }).catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' }));
        });
    }

    // INTERCEPTOR Y VALIDACIÓN: Formulario de Edición
    const formEditar = document.getElementById('formEditarCuenta');
    if (formEditar) {
        formEditar.addEventListener('submit', function (e) {
            e.preventDefault();
            
            const identificador = document.getElementById('edit_identificador').value.replace(/[\s-]/g, '');
            const selectMetodo = document.getElementById('edit_id_metodo_de_pago');
            const textoMetodo = selectMetodo.options[selectMetodo.selectedIndex].text.toLowerCase();
            const tipoCuenta = document.getElementById('edit_tipo_cuenta').value.toLowerCase();

            if (textoMetodo.includes('banco') || textoMetodo.includes('transferencia') || tipoCuenta.includes('corriente') || tipoCuenta.includes('ahorro')) {
                if (identificador.length !== 20 || isNaN(identificador)) {
                    Swal.fire({ icon: 'error', title: 'Número de Cuenta Inválido', text: 'El número de cuenta de banco debe poseer exactamente 20 números.', confirmButtonColor: '#d33' });
                    return;
                }
            }

            if (textoMetodo.includes('movil') || textoMetodo.includes('móvil') || tipoCuenta.includes('movil') || tipoCuenta.includes('móvil')) {
                if (identificador.length !== 11 || isNaN(identificador)) {
                    Swal.fire({ icon: 'error', title: 'Pago Móvil Inválido', text: 'El identificador para pago móvil requiere exactamente 11 números de teléfono.', confirmButtonColor: '#d33' });
                    return;
                }
            }

            fetch('index.php?controller=finanzas&action=guardarCuenta', { method: 'POST', body: new FormData(formEditar) })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    $('#modalEditarCuenta').modal('hide');
                    Swal.fire({ icon: 'success', title: '¡Actualizada!', text: 'Los cambios fueron aplicados con éxito.', confirmButtonColor: '#2b4c7e' }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error en Servidor', text: data.message || 'No se pudieron guardar los cambios.', confirmButtonColor: '#d33' });
                }
            }).catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' }));
        });
    }
});

function limpiarFormularioCrear() {
    const form = document.getElementById('formRegistrarCuenta');
    if (form) form.reset();
}

function cargarDatosEdicion(cuenta) {
    const form = document.getElementById('formEditarCuenta');
    if (form) form.reset();

    document.getElementById('edit_id_cuenta').value = cuenta.id_cuenta;
    document.getElementById('edit_titular').value = cuenta.titular;
    document.getElementById('edit_id_metodo_de_pago').value = cuenta.id_metodo_de_pago;
    document.getElementById('edit_tipo_cuenta').value = cuenta.tipo_cuenta;
    document.getElementById('edit_identificador').value = cuenta.identificador;
}

function cambiarEstadoCuenta(idCuenta, nuevoEstado) {
    const tituloAlerta = nuevoEstado === 0 ? '¿Inhabilitar cuenta?' : '¿Habilitar cuenta?';
    const textoAlerta = nuevoEstado === 0 ? 'La cuenta ya no aparecerá disponible para recibir flujos financieros.' : 'La cuenta volverá a estar activa.';
    
    Swal.fire({
        title: tituloAlerta, text: textoAlerta, icon: 'warning', showCancelButton: true,
        confirmButtonColor: nuevoEstado === 0 ? '#d33' : '#2b4c7e', cancelButtonColor: '#6e7881',
        confirmButtonText: nuevoEstado === 0 ? 'Sí, inhabilitar' : 'Sí, activar', cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('id_cuenta', idCuenta);
            formData.append('estado', nuevoEstado);
            
            fetch('index.php?controller=finanzas&action=inhabilitarCuenta', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({ icon: 'success', title: '¡Completado!', text: 'El estado de la cuenta se actualizó correctamente.', confirmButtonColor: '#2b4c7e' }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo cambiar el estado.', confirmButtonColor: '#d33' });
                }
            }).catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' }));
        }
    });
}