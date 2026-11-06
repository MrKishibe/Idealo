import $ from 'jquery';
import { successAlert, errorAlert, statusAlert } from './helpers/alerts.js';
import { crearRecortadorFoto } from './helpers/fotodeperfil.js';

$(function () {
    const URL_MODULO = 'index.php?controller=usuario';
    const FOTO_POR_DEFECTO = 'assets/Img/Iconos/person-fill.svg';

    // Datos del modal de edición
    function cargarPerfilEdicion() {
        $('#perfil_nombre_usuario').val(String($('#perfilNombreUsuario').text()).trim());
        $('#perfil_correo').val(String($('#perfilCorreo').text()).trim());
    }

    function actualizarPerfilEnPantalla() {
        const nombre = $('#perfil_nombre_usuario').val();
        const correo = $('#perfil_correo').val();

        $('#perfilNombreUsuario, #perfilNombreH2').text(nombre);
        $('#perfilCorreo').text(correo);
    }

    // Avatar
    function mostrarFotoPorDefecto() {
        $('#perfilAvatarImg')
            .attr('src', FOTO_POR_DEFECTO)
            .removeClass('perfil-foto')
            .addClass('icono-svg icono-gris');
    }

    function mostrarFoto(ruta) {
        if (!ruta) return;

        $('#perfilAvatarImg')
            .attr('src', ruta + '?v=' + Date.now())
            .removeClass('icono-svg icono-gris')
            .addClass('perfil-foto');
    }

    async function subirFoto(archivo) {
        const $avatar = $('#perfilAvatar');
        const $input = $('#inputFotoPerfil');
        const datos = new FormData();

        datos.append('foto_perfil', archivo);
        $avatar.addClass('cargando-foto');

        try {
            const respuesta = await $.ajax({
                url: URL_MODULO + '&action=subirFotoPerfil',
                type: 'POST',
                data: datos,
                processData: false,
                contentType: false,
                dataType: 'json'
            });

            if (respuesta.status === 'success' && respuesta.url) {
                mostrarFoto(respuesta.url);
                successAlert('¡Foto actualizada!', respuesta.message);
                return true;
            }

            await errorAlert('Error', respuesta.message || 'No se pudo actualizar la foto de perfil.');
            return false;
        } catch (error) {
            await errorAlert('Error', 'No se pudo conectar con el servidor.');
            return false;
        } finally {
            $avatar.removeClass('cargando-foto');
            $input.val('');
        }
    }

    function iniciarFotoPerfil() {
        const $input = $('#inputFotoPerfil');
        if (!$input.length) return;

        const recortador = crearRecortadorFoto({ alConfirmar: subirFoto });
        if (!recortador) return;

        $('#perfilAvatarImg').on('error', mostrarFotoPorDefecto);

        $('#btnCambiarFoto, #btnCambiarFotoAccion').on('click', function () {
            $input.trigger('click');
        });

        $input.on('change', function () {
            const archivo = this.files && this.files[0];
            if (archivo) recortador.abrir(archivo);
        });
    }

    // Contraseña
    $('#formCambiarContrasena').on('submit', async function (e) {
        e.preventDefault();

        const nueva = $('#contrasena_nueva').val();
        const confirmar = $('#confirmar_contrasena_nueva').val();

        if (nueva !== confirmar) {
            statusAlert('Contraseñas no coinciden', 'La nueva contraseña y su confirmación deben ser iguales.', 'warning');
            return;
        }

        const $form = $(this);

        try {
            const respuesta = await $.ajax({
                url: URL_MODULO + '&action=cambiarContrasena',
                type: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false,
                dataType: 'json'
            });

            if (respuesta.status === 'success') {
                $('#modalCambiarContrasena').modal('hide');
                $form[0].reset();
                successAlert('¡Actualizado!', respuesta.message);
            } else {
                errorAlert('Error', respuesta.message);
            }
        } catch (error) {
            errorAlert('Error', 'Error de comunicación con el servidor.');
        }
    });

    // Datos del perfil
    $('#modalEditarPerfil').on('show.bs.modal', cargarPerfilEdicion);

    $('#formEditarPerfil').on('submit', async function (e) {
        e.preventDefault();

        try {
            const respuesta = await $.ajax({
                url: URL_MODULO + '&action=actualizarPerfil',
                type: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false,
                dataType: 'json'
            });

            if (respuesta.status === 'success') {
                $('#modalEditarPerfil').modal('hide');
                actualizarPerfilEnPantalla();
                successAlert('¡Actualizado!', respuesta.message);
            } else {
                errorAlert('Error', respuesta.message);
            }
        } catch (error) {
            errorAlert('Error', 'Error de comunicación con el servidor.');
        }
    });

    iniciarFotoPerfil();
});
