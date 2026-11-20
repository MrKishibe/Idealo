<style>
    #idealo-page-loader {
        position: fixed;
        inset: 0;
        z-index: 2147483000;
        display: grid;
        place-items: center;
        background: #f8fafc;
        color: #1e293b;
        font-family: Arial, sans-serif;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 260ms ease, visibility 260ms ease;
    }

    #idealo-page-loader.is-visible {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    #idealo-page-loader.is-leaving {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    #idealo-page-loader .loader-content {
        width: min(340px, calc(100vw - 48px));
        padding: 32px 28px;
        text-align: center;
        background: #fff;
        border: 1px solid #e8edf2;
        border-radius: 18px;
        box-shadow: 0 16px 48px rgba(30, 41, 59, 0.1);
    }

    #idealo-page-loader .loader-logo {
        display: block;
        width: 92px;
        height: 92px;
        margin: 0 auto 12px;
        object-fit: contain;
        animation: idealo-loader-breathe 1.8s ease-in-out infinite;
        will-change: transform, opacity;
    }

    #idealo-page-loader .loader-brand {
        display: block;
        color: #1e293b;
        font-size: 19px;
        font-weight: 700;
        letter-spacing: 0.04em;
    }

    #idealo-page-loader .loader-message {
        margin: 7px 0 22px;
        color: #64748b;
        font-size: 14px;
    }

    #idealo-page-loader .loader-skeleton {
        display: grid;
        gap: 9px;
    }

    #idealo-page-loader .loader-skeleton span {
        display: block;
        height: 10px;
        border-radius: 999px;
        background: linear-gradient(90deg, #e8edf2 25%, #f5f7f9 50%, #e8edf2 75%);
        background-size: 200% 100%;
        animation: idealo-loader-wave 1.5s ease-in-out infinite;
    }

    #idealo-page-loader .loader-skeleton span:nth-child(2) {
        width: 76%;
        margin: 0 auto;
        animation-delay: 120ms;
    }

    #idealo-page-loader .loader-skeleton span:nth-child(3) {
        width: 54%;
        margin: 0 auto;
        animation-delay: 240ms;
    }

    @keyframes idealo-loader-breathe {
        0%, 100% { transform: scale(0.94); opacity: 0.78; }
        50% { transform: scale(1.04); opacity: 1; }
    }

    @keyframes idealo-loader-wave {
        0% { background-position: 100% 0; }
        100% { background-position: -100% 0; }
    }

    @media (prefers-reduced-motion: reduce) {
        #idealo-page-loader *,
        #idealo-page-loader::before,
        #idealo-page-loader::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }
</style>
<script>
    (function () {
        if (document.getElementById('idealo-page-loader')) return;

        var loader = document.createElement('div');
        loader.id = 'idealo-page-loader';
        loader.setAttribute('role', 'status');
        loader.setAttribute('aria-live', 'polite');
        loader.setAttribute('aria-label', 'Cargando Idéalo');
        loader.setAttribute('aria-hidden', 'true');
        loader.innerHTML = '<div class="loader-content">' +
            '<img class="loader-logo" src="assets/Img/Iconos/Idealo_logo1.svg" alt="">' +
            '<strong class="loader-brand">IDÉALO</strong>' +
            '<p class="loader-message">Preparando tu espacio...</p>' +
            '<div class="loader-skeleton" aria-hidden="true"><span></span><span></span><span></span></div>' +
            '</div>';

        document.documentElement.appendChild(loader);

        var terminado = false;
        var temporizadorMostrar = window.setTimeout(function () {
            if (terminado) return;
            loader.classList.add('is-visible');
            loader.setAttribute('aria-hidden', 'false');
        }, 140);

        window.addEventListener('load', function () {
            terminado = true;
            window.clearTimeout(temporizadorMostrar);

            if (loader.classList.contains('is-visible')) {
                loader.classList.add('is-leaving');
                window.setTimeout(function () {
                    loader.remove();
                }, 280);
            } else {
                loader.remove();
            }
        }, { once: true });
    })();
</script>
