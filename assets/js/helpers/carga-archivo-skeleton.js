import Swal from 'sweetalert2';

const cargasActivas = new WeakMap();
let consecutivoProgreso = 0;

export function mostrarProgresoCargaArchivo() {
    const clasePopup = 'carga-archivo-popup-' + (++consecutivoProgreso);
    let popup = null;
    let barra = null;
    let textoProgreso = null;
    let progresoPendiente = null;
    let cargaIndeterminada = false;

    function actualizarVista() {
        if (!barra || !textoProgreso) return;

        if (cargaIndeterminada) {
            const indicador = barra.querySelector('.progress-bar');
            indicador.style.width = '100%';
            barra.removeAttribute('aria-valuenow');
            barra.setAttribute('aria-valuetext', 'Progreso indeterminado');
            textoProgreso.textContent = 'Subiendo archivo...';
            return;
        }

        if (progresoPendiente === null) return;

        const indicador = barra.querySelector('.progress-bar');
        indicador.style.width = progresoPendiente + '%';
        barra.setAttribute('aria-valuenow', String(progresoPendiente));
        barra.removeAttribute('aria-valuetext');
        textoProgreso.textContent = progresoPendiente + '%';
    }

    Swal.fire({
        title: 'Cargando archivo',
        html: `
            <div class="text-start">
                <p class="mb-2">Espera mientras se completa la carga.</p>
                <div class="progress" role="progressbar" aria-label="Progreso de carga"
                    aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                        style="width: 0%"></div>
                </div>
                <div class="small text-muted text-end mt-1" aria-live="polite">0%</div>
            </div>
        `,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        customClass: { popup: clasePopup },
        didOpen: () => {
            const popupActual = Swal.getPopup();
            if (!popupActual || !popupActual.classList.contains(clasePopup)) return;

            popup = popupActual;
            barra = popup.querySelector('.progress');
            textoProgreso = popup.querySelector('[aria-live="polite"]');
            actualizarVista();
        }
    });

    return {
        actualizar: function (cargados, total) {
            if (!total) return;

            progresoPendiente = Math.min(100, Math.round((cargados / total) * 100));
            cargaIndeterminada = false;
            actualizarVista();
        },
        indeterminado: function () {
            cargaIndeterminada = true;
            actualizarVista();
        },
        cerrar: function () {
            const popupActual = Swal.getPopup();
            if (popupActual && popupActual.classList.contains(clasePopup)) Swal.close();
        }
    };
}

export function crearXhrProgresoCarga(progreso) {
    return function () {
        const xhr = new XMLHttpRequest();

        if (xhr.upload) {
            xhr.upload.addEventListener('progress', function (evento) {
                if (evento.lengthComputable) {
                    progreso.actualizar(evento.loaded, evento.total);
                } else {
                    progreso.indeterminado();
                }
            });
        }

        return xhr;
    };
}

export function mostrarSkeletonCargaArchivo(contenedor, etiqueta = 'Cargando archivo') {
    const elemento = contenedor instanceof Element ? contenedor : null;
    if (!elemento) return function () {};

    const cargaAnterior = cargasActivas.get(elemento);
    if (cargaAnterior) cargaAnterior();

    const skeleton = document.createElement('span');
    skeleton.className = 'skeleton-block skeleton-effect-wave carga-archivo-skeleton';
    skeleton.setAttribute('role', 'status');
    skeleton.setAttribute('aria-label', etiqueta);

    elemento.classList.add('carga-archivo-skeleton-host');
    elemento.setAttribute('aria-busy', 'true');
    elemento.appendChild(skeleton);

    function quitarSkeleton() {
        if (cargasActivas.get(elemento) !== quitarSkeleton) return;

        skeleton.remove();
        elemento.classList.remove('carga-archivo-skeleton-host');
        elemento.removeAttribute('aria-busy');
        cargasActivas.delete(elemento);
    }

    cargasActivas.set(elemento, quitarSkeleton);
    return quitarSkeleton;
}
