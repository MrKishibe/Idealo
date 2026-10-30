$(document).ready(function () {
    // Interceptar el envío del formulario de login
    $('#loginForm').on('submit', function (e) {
        let credencial = $('#credencial').val().trim();
        let contrasena = $('#contrasena').val().trim();

     
        if (credencial === '' || contrasena === '') {
            e.preventDefault(); 
            Swal.fire({
                icon: 'warning',
                title: 'Campos incompletos',
                text: 'Por favor, ingresa tu usuario/correo y contraseña.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Entendido'
            });
        }
    });

  
    let backendMessage = $('#backend-messages');
    if (backendMessage.length) {
        let errorMsg = backendMessage.data('error');
        let successMsg = backendMessage.data('success');

        if (errorMsg) {
            Swal.fire({
                icon: 'error',
                title: 'Error de Autenticación',
                text: errorMsg,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Intentar de nuevo'
            });
        }

        if (successMsg) {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: successMsg,
                confirmButtonColor: '#28a745',
                confirmButtonText: 'Continuar'
            });
        }
    }
});