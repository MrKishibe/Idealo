let tablaUsuarios = null;

function initTablaUsuarios() {
    if ($.fn.DataTable.isDataTable('#tablaUsuarios')) {
        $('#tablaUsuarios').DataTable().destroy();
    }

    tablaUsuarios = $('#tablaUsuarios').DataTable({
        language: {
            lengthMenu: "Mostrar _MENU_ registros",
            zeroRecords: "No se encontraron resultados",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando 0 a 0 de 0 registros",
            infoFiltered: "(filtrado de un total de _MAX_ registros)",
            search: "Buscar:",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior"
            }
        },
        pageLength: 10,
        responsive: true,
        order: [[0, 'desc']]
    });
}

function filtrarPorEstado(estado) {
    const patron = '(^|[\\s\\-])' + estado + '([\\s\\-]|$)';
    tablaUsuarios.column(3).search(patron, true, false).draw();
}

function aplicarFiltroEstado() {
    const btnEstado = document.getElementById("btnAlternarEstado");
    const vista = btnEstado ? btnEstado.getAttribute("data-vista") : "activos";
    filtrarPorEstado(vista === "inhabilitados" ? "inactivo" : "activo");
}

function recargarTabla() {
    fetch(window.location.href)
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const nuevoTbody = doc.querySelector('#tablaUsuarios tbody');
            const tbodyActual = document.querySelector('#tablaUsuarios tbody');

            if (nuevoTbody && tbodyActual) {
                tablaUsuarios.destroy();
                tbodyActual.innerHTML = nuevoTbody.innerHTML;
                initTablaUsuarios();
                aplicarFiltroEstado();
            }
        })
        .catch(() => location.reload());
}

function limpiarFormularioCrear() {
    const form = document.getElementById('formRegistrarUsuario');
    if (form) form.reset();
}

document.addEventListener("DOMContentLoaded", function () {
    initTablaUsuarios();

    const btnAlternarEstado = document.getElementById("btnAlternarEstado");
    if (btnAlternarEstado) {
        btnAlternarEstado.addEventListener("click", function () {
            const vistaActual = this.getAttribute("data-vista");
            const txtBoton = document.getElementById("txtBotonEstado");
            const icono = document.getElementById("iconoEstado");
            const titulo = document.getElementById("tituloVista");

            if (vistaActual === "activos") {
                this.setAttribute("data-vista", "inhabilitados");
                txtBoton.innerText = "Ver Activos";
                if (icono) icono.src = "assets/Img/Iconos/eye.svg";
                if (titulo) titulo.innerText = "Usuarios Inhabilitados";
                filtrarPorEstado("inactivo");
            } else {
                this.setAttribute("data-vista", "activos");
                txtBoton.innerText = "Ver inhabilitados";
                if (icono) icono.src = "assets/Img/Iconos/eye-slash.svg";
                if (titulo) titulo.innerText = "Gestión de Usuarios";
                filtrarPorEstado("activo");
            }
        });

        filtrarPorEstado("activo");
    }

    $(document).on('click', '.btnEditarActivo', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const rol = $(this).data('rol');

        $('#edit_activo_id_usuario').val(id);
        $('#edit_activo_nombre_usuario').val(nombre);
        $('#edit_activo_id_rol').val(rol);
        $('#edit_activo_contrasena').val('');
        $('#edit_activo_confirmar_contrasena').val('');

        $('#modalEditarActivo').modal('show');
    });

    $(document).on('click', '.btnEditarInactivo', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');

        $('#edit_inactivo_id_usuario').val(id);
        $('#edit_inactivo_nombre_usuario').val(nombre);
        $('#edit_inactivo_status').val('inactivo').change();

        $('#modalEditarInactivo').modal('show');
    });

    const btnGuardarInactivo = document.getElementById('btnGuardarEdicionInactivo');
    if (btnGuardarInactivo) {
        btnGuardarInactivo.addEventListener('click', function () {
            const id = $('#edit_inactivo_id_usuario').val();
            const nuevoEstado = $('#edit_inactivo_status').val();

            if (nuevoEstado === 'inactivo') {
                $('#modalEditarInactivo').modal('hide');
                return;
            }

            const formData = new FormData();
            formData.append('id_usuario', id);
            formData.append('status_usuario', nuevoEstado);

            fetch('index.php?controller=usuario&action=cambiarEstado', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    $('#modalEditarInactivo').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: '¡Reactivado!',
                        text: data.message,
                        confirmButtonColor: '#1e5631'
                    }).then(() => recargarTabla());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' }));
        });
    }

    $(document).on('click', '.btnCambiarEstado', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');

        Swal.fire({
            title: '¿Inactivar usuario?',
            text: `El usuario "${nombre}" no podrá iniciar sesión hasta ser reactivado.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Sí, inactivar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('id_usuario', id);
                formData.append('status_usuario', 'inactivo');

                fetch('index.php?controller=usuario&action=cambiarEstado', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Inactivado!',
                            text: data.message,
                            confirmButtonColor: '#1e5631'
                        }).then(() => recargarTabla());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                    }
                })
                .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' }));
            }
        });
    });

    const formRegistrar = document.getElementById('formRegistrarUsuario');
    if (formRegistrar) {
        formRegistrar.addEventListener('submit', function (e) {
            e.preventDefault();

            const contrasena = document.getElementById('reg_contrasena').value;
            const confirmar = document.getElementById('reg_confirmar_contrasena').value;

            if (contrasena !== confirmar) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Contraseñas no coinciden',
                    text: 'La contraseña y su confirmación deben ser iguales.'
                });
                return;
            }

            fetch('index.php?controller=usuario&action=guardar', {
                method: 'POST',
                body: new FormData(formRegistrar)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    $('#modalRegistrarUsuario').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: '¡Registrado!',
                        text: data.message,
                        confirmButtonColor: '#1e5631'
                    }).then(() => recargarTabla());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' }));
        });
    }

    const formEditar = document.getElementById('formEditarActivo');
    if (formEditar) {
        formEditar.addEventListener('submit', function (e) {
            e.preventDefault();

            const nuevaPass = document.getElementById('edit_activo_contrasena').value;
            const confirmarPass = document.getElementById('edit_activo_confirmar_contrasena').value;

            if (nuevaPass !== '' && nuevaPass !== confirmarPass) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Contraseñas no coinciden',
                    text: 'La contraseña y su confirmación deben ser iguales.'
                });
                return;
            }

            fetch('index.php?controller=usuario&action=editar', {
                method: 'POST',
                body: new FormData(formEditar)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    $('#modalEditarActivo').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: '¡Actualizado!',
                        text: data.message,
                        confirmButtonColor: '#1e5631'
                    }).then(() => recargarTabla());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' }));
        });
    }

    limpiarFormularioCrear();
});