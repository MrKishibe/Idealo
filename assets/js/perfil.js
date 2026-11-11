import $ from 'jquery';
import { successAlert, errorAlert } from './helpers/alerts.js';
import { obtenerPatron } from './helpers/expresiones.js';
import { crearRecortadorFoto } from './helpers/fotodeperfil.js';

$(function () {
    const URL_MODULO = 'index.php?controller=usuario';
    const FOTO_POR_DEFECTO = 'assets/Img/Iconos/person-fill.svg';

    /* ======================================================================
       Foto de perfil
       ====================================================================== */
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

    /* ======================================================================
       Utilidades de validación en línea
       ====================================================================== */
    function marcarError($campo, $mensaje, texto) {
        $campo.addClass('campo-invalido').attr('aria-invalid', 'true');
        $mensaje.text(texto).addClass('visible');
    }

    function limpiarErrores($contenedor) {
        $contenedor.find('.perfil-input').removeClass('campo-invalido').removeAttr('aria-invalid');
        $contenedor.find('.perfil-error').removeClass('visible').text('');
    }

    /* ======================================================================
       Tarjeta: Datos Personales (edición en línea)
       ====================================================================== */
    const $tarjetaDatos = $('#tarjetaDatos');
    const $btnDatos = $('#btnEditarPerfil');
    const $nombreUsuario = $('#perfil_nombre_usuario');
    const $correo = $('#perfil_correo');

    function estaEditandoDatos() {
        return $tarjetaDatos.hasClass('modo-edicion');
    }

    function ponerModoDatos(editar) {
        $tarjetaDatos.toggleClass('modo-edicion', editar);
        $btnDatos.find('.perfil-btn-texto').text(editar ? 'Guardar Cambios' : 'Editar Perfil');
        limpiarErrores($tarjetaDatos);

        if (editar) {
            $nombreUsuario.val($('#perfilNombreUsuario').text().trim());
            $correo.val($('#perfilCorreo').text().trim());
            $nombreUsuario.trigger('focus');
        }
    }

    function validarDatos() {
        limpiarErrores($tarjetaDatos);

        let valido = true;
        const usuario = String($nombreUsuario.val() || '').trim();
        const correo = String($correo.val() || '').trim();

        if (!obtenerPatron('usuario').test(usuario)) {
            marcarError($nombreUsuario, $('#error_nombre_usuario'), 'Usa entre 3 y 20 caracteres: letras, números o guion bajo.');
            valido = false;
        }

        if (!obtenerPatron('correo').test(correo)) {
            marcarError($correo, $('#error_correo'), 'Ingresa un correo electrónico válido.');
            valido = false;
        }

        return valido;
    }

    async function guardarDatos() {
        if (!validarDatos()) return;

        const usuario = String($nombreUsuario.val() || '').trim();
        const correo = String($correo.val() || '').trim();

        $btnDatos.prop('disabled', true).find('.perfil-btn-texto').text('Guardando...');

        try {
            const respuesta = await $.ajax({
                url: URL_MODULO + '&action=actualizarPerfil',
                type: 'POST',
                dataType: 'json',
                data: {
                    nombre_usuario: usuario,
                    correo: correo
                }
            });

            if (respuesta.status !== 'success') {
                await errorAlert('Error', respuesta.message || 'No se pudo actualizar el perfil.');
                return;
            }

            $('#perfilNombreUsuario').text(usuario);
            $('#perfilCorreo').text(correo);
            $('#perfilUsuario').text('@' + usuario);

            if ($('#perfilNombreH2').data('usa-usuario')) {
                $('#perfilNombreH2').text(usuario);
            }

            ponerModoDatos(false);
            await successAlert('¡Actualizado!', respuesta.message);
        } catch (error) {
            await errorAlert('Error', 'Error de comunicación con el servidor.');
        } finally {
            $btnDatos
                .prop('disabled', false)
                .find('.perfil-btn-texto')
                .text(estaEditandoDatos() ? 'Guardar Cambios' : 'Editar Perfil');
        }
    }

    $btnDatos.on('click', function () {
        if (estaEditandoDatos()) {
            guardarDatos();
        } else {
            ponerModoDatos(true);
        }
    });

    $tarjetaDatos.on('keydown', '.perfil-input-inline', function (e) {
        if (e.key === 'Enter' && estaEditandoDatos()) {
            e.preventDefault();
            guardarDatos();
        }
    });

    /* ======================================================================
       Modal: Cambiar contraseña
       ====================================================================== */
    const $modalContrasena = $('#modalCambiarContrasena');
    const $formContrasena = $('#formCambiarContrasena');
    const $btnGuardarContrasena = $('#btnGuardarContrasena');

    function limpiarFormularioContrasena() {
        if (!$formContrasena.length) return;
        $formContrasena[0].reset();
        $formContrasena.find('.form-control').removeClass('is-invalid');
    }

    function validarContrasena() {
        const actual = String($('#contrasena_actual').val() || '');
        const nueva = String($('#contrasena_nueva').val() || '');
        const confirmar = String($('#confirmar_contrasena_nueva').val() || '');

        $formContrasena.find('.form-control').removeClass('is-invalid');

        if (actual.trim() === '') {
            $('#contrasena_actual').addClass('is-invalid').trigger('focus');
            return 'Ingresa tu contraseña actual.';
        }

        if (nueva.length < 6) {
            $('#contrasena_nueva').addClass('is-invalid').trigger('focus');
            return 'La nueva contraseña debe tener al menos 6 caracteres.';
        }

        if (confirmar === '' || confirmar !== nueva) {
            $('#confirmar_contrasena_nueva').addClass('is-invalid').trigger('focus');
            return 'La confirmación no coincide con la nueva contraseña.';
        }

        return null;
    }

    async function guardarContrasena() {
        const error = validarContrasena();
        if (error) {
            await errorAlert('Revisa los datos', error);
            return;
        }

        $btnGuardarContrasena.prop('disabled', true).text('Guardando...');

        try {
            const respuesta = await $.ajax({
                url: URL_MODULO + '&action=cambiarContrasena',
                type: 'POST',
                dataType: 'json',
                data: {
                    contrasena_actual: String($('#contrasena_actual').val() || ''),
                    contrasena_nueva: String($('#contrasena_nueva').val() || '')
                }
            });

            if (respuesta.status !== 'success') {
                await errorAlert('Error', respuesta.message || 'No se pudo actualizar la contraseña.');
                return;
            }

            const modal = bootstrap.Modal.getInstance($modalContrasena[0]);
            if (modal) modal.hide();

            limpiarFormularioContrasena();
            await successAlert('¡Actualizado!', respuesta.message);
        } catch (error) {
            await errorAlert('Error', 'Error de comunicación con el servidor.');
        } finally {
            $btnGuardarContrasena.prop('disabled', false).text('Guardar Contraseña');
        }
    }

    $formContrasena.on('submit', function (e) {
        e.preventDefault();
        guardarContrasena();
    });

    $modalContrasena.on('show.bs.modal', limpiarFormularioContrasena);

    $modalContrasena.on('shown.bs.modal', function () {
        $('#contrasena_actual').trigger('focus');
    });

    /* ======================================================================
       Inicialización
       ====================================================================== */
    iniciarFotoPerfil();
});
