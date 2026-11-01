function cargarPerfilEdicion() {
    const campo = document.getElementById('perfil_nombre_usuario');
    const actual = document.getElementById('perfilNombreUsuario');
    if (campo && actual) {
        campo.value = actual.textContent.trim();
    }

    const campoCorreo = document.getElementById('perfil_correo');
    const actualCorreo = document.getElementById('perfilCorreo');
    if (campoCorreo && actualCorreo) {
        campoCorreo.value = actualCorreo.textContent.trim();
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

                    const nuevoCorreo = document.getElementById('perfil_correo').value;
                    const spanCorreo = document.getElementById('perfilCorreo');
                    if (spanCorreo) spanCorreo.textContent = nuevoCorreo;

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

    const modalEditarPerfil = document.getElementById('modalEditarPerfil');
    if (modalEditarPerfil) {
        modalEditarPerfil.addEventListener('show.bs.modal', cargarPerfilEdicion);
    }

    iniciarFotoPerfil();
});

const FOTO_PERFIL_POR_DEFECTO = 'assets/Img/Iconos/person-fill.svg';
const FOTO_PERFIL_PESO_MAXIMO = 2 * 1024 * 1024;
const FOTO_PERFIL_TIPOS = ['image/jpeg', 'image/png', 'image/webp'];
const RECORTE_SALIDA = 512;
const RECORTE_ZOOM_MIN = 1;
const RECORTE_ZOOM_MAX = 3;

let recorteModal = null;
let recorteUrl = null;
let recorteUrlAlCerrar = null;

const recorte = {
    listo: false,
    subiendo: false,
    anchoNatural: 0,
    altoNatural: 0,
    viewport: 300,
    escalaBase: 1,
    zoom: 1,
    offsetX: 0,
    offsetY: 0,
    arrastrando: false,
    inicioX: 0,
    inicioY: 0,
    offsetInicioX: 0,
    offsetInicioY: 0
};

function iniciarFotoPerfil() {
    const avatar = document.getElementById('perfilAvatar');
    const imagen = document.getElementById('perfilAvatarImg');
    const botones = document.querySelectorAll('#btnCambiarFoto, #btnCambiarFotoAccion');
    const input = document.getElementById('inputFotoPerfil');

    if (!avatar || !imagen || !botones.length || !input) return;

    imagen.addEventListener('error', mostrarFotoPerfilPorDefecto);
    botones.forEach(boton => boton.addEventListener('click', () => input.click()));

    input.addEventListener('change', function () {
        const archivo = input.files && input.files[0];
        if (!archivo) return;

        if (!FOTO_PERFIL_TIPOS.includes(archivo.type)) {
            Swal.fire({
                icon: 'warning',
                title: 'Formato no permitido',
                text: 'Sube una imagen en formato JPG, PNG o WEBP.'
            });
            input.value = '';
            return;
        }

        if (archivo.size > FOTO_PERFIL_PESO_MAXIMO) {
            Swal.fire({
                icon: 'warning',
                title: 'Imagen muy pesada',
                text: 'La foto de perfil no debe superar los 2 MB.'
            });
            input.value = '';
            return;
        }

        abrirRecorteFoto(archivo);
    });

    iniciarRecorteFoto();
}

function iniciarRecorteFoto() {
    const modal = document.getElementById('modalRecortarFoto');
    const viewport = document.getElementById('recorteViewport');
    const zoom = document.getElementById('recorteZoom');
    const botonConfirmar = document.getElementById('btnConfirmarRecorte');

    if (!modal || !viewport || !zoom || !botonConfirmar) return;

    recorteModal = bootstrap.Modal.getOrCreateInstance(modal);

    modal.addEventListener('shown.bs.modal', cargarImagenRecorte);
    modal.addEventListener('hide.bs.modal', function () {
        recorteUrlAlCerrar = recorteUrl;
    });
    modal.addEventListener('hidden.bs.modal', limpiarRecorte);

    viewport.addEventListener('pointerdown', iniciarArrastreRecorte);
    viewport.addEventListener('pointermove', moverArrastreRecorte);
    viewport.addEventListener('pointerup', terminarArrastreRecorte);
    viewport.addEventListener('pointercancel', terminarArrastreRecorte);
    viewport.addEventListener('wheel', function (e) {
        if (!recorte.listo) return;
        e.preventDefault();
        ajustarZoomRecorte(recorte.zoom - e.deltaY * 0.0015);
    }, { passive: false });

    viewport.addEventListener('keydown', function (e) {
        if (!recorte.listo) return;

        const paso = e.shiftKey ? 20 : 10;
        const movimientos = {
            ArrowLeft: [-paso, 0],
            ArrowRight: [paso, 0],
            ArrowUp: [0, -paso],
            ArrowDown: [0, paso]
        };
        const movimiento = movimientos[e.key];

        if (!movimiento) return;

        e.preventDefault();
        recorte.offsetX += movimiento[0];
        recorte.offsetY += movimiento[1];
        aplicarRecorteFoto();
    });

    zoom.addEventListener('input', function () {
        ajustarZoomRecorte(parseFloat(zoom.value));
    });

    botonConfirmar.addEventListener('click', confirmarRecorteFoto);
}

function mostrarFotoPerfilPorDefecto() {
    const imagen = document.getElementById('perfilAvatarImg');
    if (!imagen) return;

    imagen.src = FOTO_PERFIL_POR_DEFECTO;
    imagen.classList.remove('perfil-foto');
    imagen.classList.add('icono-svg', 'icono-gris');
}

function mostrarFotoPerfil(ruta) {
    const imagen = document.getElementById('perfilAvatarImg');
    if (!imagen || !ruta) return;

    imagen.src = ruta + '?v=' + Date.now();
    imagen.classList.remove('icono-svg', 'icono-gris');
    imagen.classList.add('perfil-foto');
}

function abrirRecorteFoto(archivo) {
    if (recorteUrl) {
        URL.revokeObjectURL(recorteUrl);
    }

    recorteUrl = URL.createObjectURL(archivo);
    recorte.listo = false;

    const input = document.getElementById('inputFotoPerfil');
    const botonConfirmar = document.getElementById('btnConfirmarRecorte');

    if (input) input.value = '';
    if (botonConfirmar) botonConfirmar.disabled = true;

    if (recorteModal) recorteModal.show();
}

function cargarImagenRecorte() {
    const imagen = document.getElementById('recorteImagen');
    if (!imagen || !recorteUrl) return;

    imagen.onload = prepararRecorteFoto;
    imagen.onerror = function () {
        Swal.fire({
            icon: 'error',
            title: 'Imagen no válida',
            text: 'No se pudo leer la imagen seleccionada.'
        });
        if (recorteModal) recorteModal.hide();
    };

    imagen.src = recorteUrl;
}

function prepararRecorteFoto() {
    const imagen = document.getElementById('recorteImagen');
    const viewport = document.getElementById('recorteViewport');
    const zoom = document.getElementById('recorteZoom');
    const botonConfirmar = document.getElementById('btnConfirmarRecorte');

    if (!imagen || !viewport || !zoom || !botonConfirmar) return;
    if (!imagen.naturalWidth || !imagen.naturalHeight) return;

    recorte.anchoNatural = imagen.naturalWidth;
    recorte.altoNatural = imagen.naturalHeight;
    recorte.viewport = viewport.clientWidth || 300;
    recorte.escalaBase = recorte.viewport / Math.min(recorte.anchoNatural, recorte.altoNatural);
    recorte.zoom = 1;
    recorte.offsetX = 0;
    recorte.offsetY = 0;
    recorte.listo = true;

    zoom.value = 1;
    zoom.disabled = false;
    botonConfirmar.disabled = false;

    aplicarRecorteFoto();
}

function aplicarRecorteFoto() {
    const imagen = document.getElementById('recorteImagen');
    if (!imagen || !recorte.listo) return;

    const escala = recorte.escalaBase * recorte.zoom;
    const ancho = recorte.anchoNatural * escala;
    const alto = recorte.altoNatural * escala;
    const maxX = Math.max(0, (ancho - recorte.viewport) / 2);
    const maxY = Math.max(0, (alto - recorte.viewport) / 2);

    recorte.offsetX = Math.min(maxX, Math.max(-maxX, recorte.offsetX));
    recorte.offsetY = Math.min(maxY, Math.max(-maxY, recorte.offsetY));

    imagen.style.width = ancho + 'px';
    imagen.style.height = alto + 'px';
    imagen.style.transform = 'translate(-50%, -50%) translate('
        + recorte.offsetX + 'px, ' + recorte.offsetY + 'px)';

    dibujarVistaPreviaRecorte();
}

function ajustarZoomRecorte(nuevoZoom) {
    if (!recorte.listo) return;

    const zoom = document.getElementById('recorteZoom');
    recorte.zoom = Math.min(RECORTE_ZOOM_MAX, Math.max(RECORTE_ZOOM_MIN, nuevoZoom || 1));

    if (zoom) zoom.value = recorte.zoom;
    aplicarRecorteFoto();
}

function iniciarArrastreRecorte(e) {
    if (!recorte.listo || recorte.subiendo) return;
    if (e.pointerType === 'mouse' && e.button !== 0) return;

    recorte.arrastrando = true;
    recorte.inicioX = e.clientX;
    recorte.inicioY = e.clientY;
    recorte.offsetInicioX = recorte.offsetX;
    recorte.offsetInicioY = recorte.offsetY;

    e.currentTarget.setPointerCapture(e.pointerId);
    e.currentTarget.classList.add('arrastrando');
}

function moverArrastreRecorte(e) {
    if (!recorte.arrastrando) return;

    recorte.offsetX = recorte.offsetInicioX + (e.clientX - recorte.inicioX);
    recorte.offsetY = recorte.offsetInicioY + (e.clientY - recorte.inicioY);
    aplicarRecorteFoto();
}

function terminarArrastreRecorte(e) {
    if (!recorte.arrastrando) return;

    recorte.arrastrando = false;
    e.currentTarget.classList.remove('arrastrando');

    if (e.currentTarget.hasPointerCapture(e.pointerId)) {
        e.currentTarget.releasePointerCapture(e.pointerId);
    }
}

function calcularRecorteFoto() {
    const escala = recorte.escalaBase * recorte.zoom;
    const lado = Math.min(
        recorte.viewport / escala,
        recorte.anchoNatural,
        recorte.altoNatural
    );
    const centroX = recorte.anchoNatural / 2 - recorte.offsetX / escala;
    const centroY = recorte.altoNatural / 2 - recorte.offsetY / escala;
    const sx = Math.min(Math.max(0, centroX - lado / 2), recorte.anchoNatural - lado);
    const sy = Math.min(Math.max(0, centroY - lado / 2), recorte.altoNatural - lado);

    return { sx, sy, lado };
}

function dibujarVistaPreviaRecorte() {
    const preview = document.getElementById('recortePreview');
    const imagen = document.getElementById('recorteImagen');
    if (!preview || !imagen || !recorte.listo || !imagen.complete) return;

    const contexto = preview.getContext('2d');
    const { sx, sy, lado } = calcularRecorteFoto();

    contexto.save();
    contexto.beginPath();
    contexto.arc(preview.width / 2, preview.height / 2, preview.width / 2, 0, Math.PI * 2);
    contexto.clip();
    contexto.fillStyle = '#ffffff';
    contexto.fillRect(0, 0, preview.width, preview.height);
    contexto.drawImage(imagen, sx, sy, lado, lado, 0, 0, preview.width, preview.height);
    contexto.restore();
}

async function confirmarRecorteFoto() {
    if (!recorte.listo || recorte.subiendo) return;

    const imagen = document.getElementById('recorteImagen');
    const botonConfirmar = document.getElementById('btnConfirmarRecorte');
    if (!imagen || !botonConfirmar) return;

    const { sx, sy, lado } = calcularRecorteFoto();
    const canvas = document.createElement('canvas');
    canvas.width = RECORTE_SALIDA;
    canvas.height = RECORTE_SALIDA;

    const contexto = canvas.getContext('2d');
    contexto.fillStyle = '#ffffff';
    contexto.fillRect(0, 0, RECORTE_SALIDA, RECORTE_SALIDA);
    contexto.drawImage(imagen, sx, sy, lado, lado, 0, 0, RECORTE_SALIDA, RECORTE_SALIDA);

    const blob = await new Promise(resolver => canvas.toBlob(resolver, 'image/jpeg', 0.9));
    if (!blob) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo procesar la imagen recortada.'
        });
        return;
    }

    const archivo = new File([blob], 'perfil_' + Date.now() + '.jpg', { type: 'image/jpeg' });

    recorte.subiendo = true;
    bloquearCierreRecorte(true);
    botonConfirmar.disabled = true;
    botonConfirmar.textContent = 'Subiendo...';

    const resultado = await subirFotoPerfil(archivo);

    recorte.subiendo = false;
    bloquearCierreRecorte(false);
    botonConfirmar.disabled = false;
    botonConfirmar.textContent = 'Confirmar Foto';

    if (resultado.exito) {
        if (recorteModal) recorteModal.hide();
        Swal.fire({
            icon: 'success',
            title: '¡Foto actualizada!',
            text: resultado.mensaje,
            confirmButtonColor: '#1e5631'
        });
    }
}

function bloquearCierreRecorte(bloquear) {
    const modal = document.getElementById('modalRecortarFoto');
    if (!modal) return;

    modal.querySelectorAll('[data-bs-dismiss="modal"]').forEach(boton => {
        boton.disabled = bloquear;
    });
}

function limpiarRecorte() {
    if (recorteUrl && recorteUrl === recorteUrlAlCerrar) {
        URL.revokeObjectURL(recorteUrl);
        recorteUrl = null;
    }

    recorteUrlAlCerrar = null;

    // Si ya se abrió otra sesión de recorte, conservar su estado.
    if (recorteUrl) return;

    const imagen = document.getElementById('recorteImagen');
    const zoom = document.getElementById('recorteZoom');
    const viewport = document.getElementById('recorteViewport');
    const botonConfirmar = document.getElementById('btnConfirmarRecorte');
    const input = document.getElementById('inputFotoPerfil');

    recorte.listo = false;
    recorte.subiendo = false;
    recorte.arrastrando = false;
    recorte.offsetX = 0;
    recorte.offsetY = 0;

    if (recorteUrl) {
        URL.revokeObjectURL(recorteUrl);
        recorteUrl = null;
    }

    if (imagen) {
        imagen.onload = null;
        imagen.onerror = null;
        imagen.removeAttribute('src');
        imagen.style.width = '';
        imagen.style.height = '';
        imagen.style.transform = '';
    }

    if (viewport) viewport.classList.remove('arrastrando');
    if (zoom) {
        zoom.value = 1;
        zoom.disabled = true;
    }
    if (botonConfirmar) {
        botonConfirmar.disabled = true;
        botonConfirmar.textContent = 'Confirmar Foto';
    }
    if (input) input.value = '';

    bloquearCierreRecorte(false);
}

async function subirFotoPerfil(archivo) {
    const avatar = document.getElementById('perfilAvatar');
    const input = document.getElementById('inputFotoPerfil');

    if (avatar) avatar.classList.add('cargando-foto');

    try {
        const datos = new FormData();
        datos.append('foto_perfil', archivo);

        const respuesta = await fetch('index.php?controller=usuario&action=subirFotoPerfil', {
            method: 'POST',
            body: datos
        });
        const data = await respuesta.json();

        if (data.status === 'success' && data.url) {
            mostrarFotoPerfil(data.url);
            return { exito: true, mensaje: data.message };
        }

        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: data.message || 'No se pudo actualizar la foto de perfil.'
        });
        return { exito: false };
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo conectar con el servidor.'
        });
        return { exito: false };
    } finally {
        if (avatar) avatar.classList.remove('cargando-foto');
        if (input) input.value = '';
    }
}
