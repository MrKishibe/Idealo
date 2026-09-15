function cargarPerfilEdicion() {
    const campo = document.getElementById('perfil_nombre_usuario');
    const actual = document.getElementById('perfilNombreUsuario');
    if (campo && actual) {
        campo.value = actual.textContent.trim();
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const formContrasena = document.getElementById('formCambiarContrasena');
    if (formContrasena) {
        formContrasena.addEventListener('submit', function (e) {
            e.preventDefault();

            const nueva = document.getElementById('contrasena_nueva').value;
            const confirmar = document.getElementById('confirmar_contrasena_nueva').value;

            if (nueva !== confirmar) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Contraseñas no coinciden',
                    text: 'La nueva contraseña y su confirmación deben ser iguales.'
                });
                return;
            }

            fetch('index.php?controller=usuario&action=cambiarContrasena', {
                method: 'POST',
                body: new FormData(formContrasena)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    $('#modalCambiarContrasena').modal('hide');
                    formContrasena.reset();
                    Swal.fire({
                        icon: 'success',
                        title: '¡Actualizado!',
                        text: data.message,
                        confirmButtonColor: '#1e5631'
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' }));
        });
    }

    const formPerfil = document.getElementById('formEditarPerfil');
    if (formPerfil) {
        formPerfil.addEventListener('submit', function (e) {
            e.preventDefault();

            fetch('index.php?controller=usuario&action=actualizarPerfil', {
                method: 'POST',
                body: new FormData(formPerfil)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    $('#modalEditarPerfil').modal('hide');

                    const nuevoNombre = document.getElementById('perfil_nombre_usuario').value;
                    const spanNombre = document.getElementById('perfilNombreUsuario');
                    const h2Nombre = document.getElementById('perfilNombreH2');

                    if (spanNombre) spanNombre.textContent = nuevoNombre;
                    if (h2Nombre) h2Nombre.textContent = nuevoNombre;

                    Swal.fire({
                        icon: 'success',
                        title: '¡Actualizado!',
                        text: data.message,
                        confirmButtonColor: '#1e5631'
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' }));
        });
    }
});