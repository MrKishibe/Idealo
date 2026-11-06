import $ from 'jquery';
import Swal from 'sweetalert2';

const TIPOS_POR_DEFECTO = ['image/jpeg', 'image/png', 'image/webp'];
const PESO_MAXIMO_POR_DEFECTO = 2 * 1024 * 1024;
const ZOOM_MIN_POR_DEFECTO = 1;
const ZOOM_MAX_POR_DEFECTO = 3;

const OPCIONES_POR_DEFECTO = {
    modal: '#modalRecortarFoto',
    viewport: '#recorteViewport',
    imagen: '#recorteImagen',
    preview: '#recortePreview',
    zoom: '#recorteZoom',
    input: '#inputFotoPerfil',
    confirmar: '#btnConfirmarRecorte',
    salida: 512,
    calidad: 0.9,
    tipos: TIPOS_POR_DEFECTO,
    pesoMaximo: PESO_MAXIMO_POR_DEFECTO,
    zoomMin: ZOOM_MIN_POR_DEFECTO,
    zoomMax: ZOOM_MAX_POR_DEFECTO,
    textoConfirmar: 'Confirmar Foto',
    textoSubiendo: 'Subiendo...',
    mensajeFormato: 'Sube una imagen en formato JPG, PNG o WEBP.',
    mensajePeso: 'La imagen no debe superar los 2 MB.',
    mensajeError: 'No se pudo procesar la imagen recortada.',
    alConfirmar: null
};

/**
 * Recortador de imágenes reutilizable (centrar, arrastrar y hacer zoom).
 * Al confirmar genera un archivo cuadrado y se lo entrega a `alConfirmar`.
 * Si `alConfirmar` devuelve false, el modal permanece abierto para reintentar.
 */
export function crearRecortadorFoto(opciones = {}) {
    const cfg = $.extend({}, OPCIONES_POR_DEFECTO, opciones);
    const $modal = $(cfg.modal);
    const $viewport = $(cfg.viewport);
    const $imagen = $(cfg.imagen);
    const $preview = $(cfg.preview);
    const $zoom = $(cfg.zoom);
    const $confirmar = $(cfg.confirmar);

    if (!$modal.length || !$viewport.length || !$imagen.length || !$confirmar.length) {
        return null;
    }

    const estado = {
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

    let url = null;
    let urlAlCerrar = null;

    function validar(archivo) {
        if (!archivo) return false;

        if (cfg.tipos.length && $.inArray(archivo.type, cfg.tipos) === -1) {
            Swal.fire({ icon: 'warning', title: 'Formato no permitido', text: cfg.mensajeFormato });
            $(cfg.input).val('');
            return false;
        }

        if (cfg.pesoMaximo > 0 && archivo.size > cfg.pesoMaximo) {
            Swal.fire({ icon: 'warning', title: 'Imagen muy pesada', text: cfg.mensajePeso });
            $(cfg.input).val('');
            return false;
        }

        return true;
    }

    function bloquearCierre(bloquear) {
        $modal.find('[data-bs-dismiss="modal"]').prop('disabled', Boolean(bloquear));
    }

    function calcular() {
        const escala = estado.escalaBase * estado.zoom;
        const lado = Math.min(
            estado.viewport / escala,
            estado.anchoNatural,
            estado.altoNatural
        );
        const centroX = estado.anchoNatural / 2 - estado.offsetX / escala;
        const centroY = estado.altoNatural / 2 - estado.offsetY / escala;
        const sx = Math.min(Math.max(0, centroX - lado / 2), estado.anchoNatural - lado);
        const sy = Math.min(Math.max(0, centroY - lado / 2), estado.altoNatural - lado);

        return { sx, sy, lado };
    }

    function dibujarVistaPrevia() {
        const preview = $preview[0];
        const imagen = $imagen[0];

        if (!preview || !imagen || !estado.listo || !imagen.complete) return;

        const contexto = preview.getContext('2d');
        const { sx, sy, lado } = calcular();

        contexto.save();
        contexto.beginPath();
        contexto.arc(preview.width / 2, preview.height / 2, preview.width / 2, 0, Math.PI * 2);
        contexto.clip();
        contexto.fillStyle = '#ffffff';
        contexto.fillRect(0, 0, preview.width, preview.height);
        contexto.drawImage(imagen, sx, sy, lado, lado, 0, 0, preview.width, preview.height);
        contexto.restore();
    }

    function aplicar() {
        if (!estado.listo) return;

        const escala = estado.escalaBase * estado.zoom;
        const ancho = estado.anchoNatural * escala;
        const alto = estado.altoNatural * escala;
        const maxX = Math.max(0, (ancho - estado.viewport) / 2);
        const maxY = Math.max(0, (alto - estado.viewport) / 2);

        estado.offsetX = Math.min(maxX, Math.max(-maxX, estado.offsetX));
        estado.offsetY = Math.min(maxY, Math.max(-maxY, estado.offsetY));

        $imagen.css({
            width: ancho + 'px',
            height: alto + 'px',
            transform: 'translate(-50%, -50%) translate(' + estado.offsetX + 'px, ' + estado.offsetY + 'px)'
        });

        dibujarVistaPrevia();
    }

    function ajustarZoom(nuevoZoom) {
        if (!estado.listo) return;

        estado.zoom = Math.min(cfg.zoomMax, Math.max(cfg.zoomMin, parseFloat(nuevoZoom) || cfg.zoomMin));
        $zoom.val(estado.zoom);
        aplicar();
    }

    function preparar() {
        const imagen = $imagen[0];
        if (!imagen || !imagen.naturalWidth || !imagen.naturalHeight) return;

        estado.anchoNatural = imagen.naturalWidth;
        estado.altoNatural = imagen.naturalHeight;
        estado.viewport = $viewport[0].clientWidth || 300;
        estado.escalaBase = estado.viewport / Math.min(estado.anchoNatural, estado.altoNatural);
        estado.zoom = cfg.zoomMin;
        estado.offsetX = 0;
        estado.offsetY = 0;
        estado.listo = true;

        $zoom.val(cfg.zoomMin).prop('disabled', false);
        $confirmar.prop('disabled', false).text(cfg.textoConfirmar);
        aplicar();
    }

    function cargar() {
        const imagen = $imagen[0];
        if (!imagen || !url) return;

        imagen.onload = preparar;
        imagen.onerror = function () {
            Swal.fire({
                icon: 'error',
                title: 'Imagen no válida',
                text: 'No se pudo leer la imagen seleccionada.'
            });
            $modal.modal('hide');
        };

        imagen.src = url;
    }

    function limpiar() {
        if (url && url === urlAlCerrar) {
            URL.revokeObjectURL(url);
            url = null;
        }

        urlAlCerrar = null;

        // Si ya se abrió otra sesión de recorte, conservar su estado.
        if (url) return;

        estado.listo = false;
        estado.subiendo = false;
        estado.arrastrando = false;
        estado.offsetX = 0;
        estado.offsetY = 0;

        const imagen = $imagen[0];
        if (imagen) {
            imagen.onload = null;
            imagen.onerror = null;
        }

        $imagen.removeAttr('src').css({ width: '', height: '', transform: '' });
        $viewport.removeClass('arrastrando');
        $zoom.val(cfg.zoomMin).prop('disabled', true);
        $confirmar.prop('disabled', true).text(cfg.textoConfirmar);
        $(cfg.input).val('');
        bloquearCierre(false);
    }

    function iniciarArrastre(e) {
        if (!estado.listo || estado.subiendo) return;
        if (e.pointerType === 'mouse' && e.button !== 0) return;

        estado.arrastrando = true;
        estado.inicioX = e.clientX;
        estado.inicioY = e.clientY;
        estado.offsetInicioX = estado.offsetX;
        estado.offsetInicioY = estado.offsetY;

        e.currentTarget.setPointerCapture(e.pointerId);
        $viewport.addClass('arrastrando');
    }

    function moverArrastre(e) {
        if (!estado.arrastrando) return;

        estado.offsetX = estado.offsetInicioX + (e.clientX - estado.inicioX);
        estado.offsetY = estado.offsetInicioY + (e.clientY - estado.inicioY);
        aplicar();
    }

    function terminarArrastre(e) {
        if (!estado.arrastrando) return;

        estado.arrastrando = false;
        $viewport.removeClass('arrastrando');

        if (e.currentTarget.hasPointerCapture(e.pointerId)) {
            e.currentTarget.releasePointerCapture(e.pointerId);
        }
    }

    function moverConTeclado(e) {
        if (!estado.listo) return;

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
        estado.offsetX += movimiento[0];
        estado.offsetY += movimiento[1];
        aplicar();
    }

    async function confirmar() {
        if (!estado.listo || estado.subiendo) return;

        const imagen = $imagen[0];
        if (!imagen) return;

        const { sx, sy, lado } = calcular();
        const canvas = document.createElement('canvas');
        canvas.width = cfg.salida;
        canvas.height = cfg.salida;

        const contexto = canvas.getContext('2d');
        contexto.fillStyle = '#ffffff';
        contexto.fillRect(0, 0, cfg.salida, cfg.salida);
        contexto.drawImage(imagen, sx, sy, lado, lado, 0, 0, cfg.salida, cfg.salida);

        const blob = await new Promise(resolver => canvas.toBlob(resolver, 'image/jpeg', cfg.calidad));

        if (!blob) {
            Swal.fire({ icon: 'error', title: 'Error', text: cfg.mensajeError });
            return;
        }

        if (typeof cfg.alConfirmar !== 'function') return;

        const archivo = new File([blob], 'recorte_' + Date.now() + '.jpg', { type: 'image/jpeg' });

        estado.subiendo = true;
        bloquearCierre(true);
        $confirmar.prop('disabled', true).text(cfg.textoSubiendo);

        let cerrar = true;

        try {
            cerrar = await cfg.alConfirmar(archivo, api);
        } catch (error) {
            cerrar = false;
        } finally {
            estado.subiendo = false;
            bloquearCierre(false);
            $confirmar.prop('disabled', false).text(cfg.textoConfirmar);
        }

        if (cerrar !== false) $modal.modal('hide');
    }

    function abrir(archivo) {
        if (!validar(archivo)) return false;

        if (url) URL.revokeObjectURL(url);

        url = URL.createObjectURL(archivo);
        estado.listo = false;

        $(cfg.input).val('');
        $zoom.val(cfg.zoomMin).prop('disabled', true);
        $confirmar.prop('disabled', true).text(cfg.textoConfirmar);
        $modal.modal('show');

        return true;
    }

    function destruir() {
        $modal.off('.recorteFoto');
        $viewport.off('.recorteFoto');
        $zoom.off('.recorteFoto');
        $confirmar.off('.recorteFoto');

        if (url) {
            URL.revokeObjectURL(url);
            url = null;
        }
    }

    $modal.on('shown.bs.modal.recorteFoto', cargar);
    $modal.on('hide.bs.modal.recorteFoto', function () {
        urlAlCerrar = url;
    });
    $modal.on('hidden.bs.modal.recorteFoto', limpiar);

    $viewport.on('pointerdown.recorteFoto', iniciarArrastre);
    $viewport.on('pointermove.recorteFoto', moverArrastre);
    $viewport.on('pointerup.recorteFoto pointercancel.recorteFoto', terminarArrastre);
    $viewport.on('keydown.recorteFoto', moverConTeclado);
    $viewport.on('wheel.recorteFoto', function (e) {
        if (!estado.listo) return;

        e.preventDefault();
        ajustarZoom(estado.zoom - e.originalEvent.deltaY * 0.0015);
    });

    $zoom.on('input.recorteFoto', function () {
        ajustarZoom(this.value);
    });

    $confirmar.on('click.recorteFoto', confirmar);

    const api = {
        abrir,
        cerrar: function () {
            $modal.modal('hide');
        },
        ajustarZoom,
        destruir
    };

    return api;
}
