const faviconUrl = new URL('../../Img/Iconos/Idealo_logo1.svg', import.meta.url).href;

export function crearPestanaReporte() {
    const ventana = window.open('about:blank', '_blank');
    if (!ventana) {
        return null;
    }

    const documento = ventana.document;
    documento.title = 'Reporte | Idealo';

    const favicon = documento.createElement('link');
    favicon.rel = 'icon';
    favicon.type = 'image/svg+xml';
    favicon.href = faviconUrl;
    documento.head.appendChild(favicon);

    const estilos = documento.createElement('style');
    estilos.textContent = 'html, body { width: 100%; height: 100%; margin: 0; overflow: hidden; } iframe { display: block; width: 100%; height: 100%; border: 0; }';
    documento.head.appendChild(estilos);

    const visor = documento.createElement('iframe');
    visor.title = 'Reporte Idealo';
    documento.body.appendChild(visor);

    return { ventana, visor };
}

export function abrirReporteEnPestana(url) {
    const pestana = crearPestanaReporte();
    if (pestana) {
        pestana.visor.src = url;
    }
    return pestana;
}

export function cargarReporteEnPestana(pestana, url) {
    if (pestana.ventana.closed) {
        URL.revokeObjectURL(url);
        return;
    }

    pestana.ventana.addEventListener('beforeunload', function () {
        URL.revokeObjectURL(url);
    }, { once: true });
    pestana.visor.src = url;
}
