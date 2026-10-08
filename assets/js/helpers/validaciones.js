(function ($) {
    "use strict";

    if (!$) {
        throw new Error("validaciones.js requiere jQuery.");
    }

    // Crea u obtiene el <small class="feedback-validacion"> que se muestra bajo el campo
    function obtenerFeedback($campo) {
        let $fb = $campo.siblings(".feedback-validacion");
        if ($fb.length === 0) {
            $fb = $('<small class="feedback-validacion form-text d-block"></small>');
            $campo.after($fb);
        }
        return $fb;
    }

    // Limpia estados is-valid/is-invalid y mensajes de un formulario (o contenedor)
    function limpiarValidaciones(contenedor) {
        const $c = $(contenedor);
        $c.find(".is-valid, .is-invalid").removeClass("is-valid is-invalid");
        $c.find(".feedback-validacion").remove();
    }

    /**
     * Valida un campo con jQuery.
     * opciones:
     *   - requerido   (bool, true)       campo obligatorio
     *   - patron      (string|RegExp)    patrón de $.expresionesRegulares o regex directo
     *   - mensaje     (string)           mensaje de formato inválido
     *   - mensajeVacio(string)           mensaje cuando está vacío
     *   - validar     (function)         validación adicional: devolver true o un string de error
     */
    function validarCampo($campo, opciones) {
        opciones = opciones || {};
        $campo = $($campo);
        if ($campo.length === 0) return true;

        const valor = String($campo.val() ?? "").trim();
        const requerido = opciones.requerido !== false;
        const $fb = obtenerFeedback($campo);

        let valido = true;
        let mensaje = opciones.mensaje || "El valor ingresado no es válido.";

        if (valor === "") {
            if (requerido) {
                valido = false;
                mensaje = opciones.mensajeVacio || "Este campo no puede estar vacío.";
            }
        } else {
            let regex = null;
            if (typeof opciones.patron === "string") {
                try {
                    regex = $.expresionesRegulares.obtener(opciones.patron);
                } catch (e) {
                    regex = null;
                }
            } else if (opciones.patron instanceof RegExp) {
                regex = opciones.patron;
            }

            if (regex && !regex.test(valor)) {
                valido = false;
            }
        }

        if (valido && typeof opciones.validar === "function") {
            const resultado = opciones.validar(valor, $campo);
            if (resultado !== true) {
                valido = false;
                if (typeof resultado === "string") mensaje = resultado;
            }
        }

        if (valido) {
            $campo.removeClass("is-invalid").addClass("is-valid");
            $fb.text("").hide();
        } else {
            $campo.removeClass("is-valid").addClass("is-invalid");
            $fb.text(mensaje).css("color", "#dc3545").show();
        }

        return valido;
    }

    // Exponer como métodos jQuery globales
    $.validarCampo = validarCampo;
    $.limpiarValidaciones = limpiarValidaciones;
    $.obtenerFeedback = obtenerFeedback;
})(window.jQuery);