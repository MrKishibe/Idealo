import $ from 'jquery';
import { obtenerPatron, validar } from './expresiones.js';

export function obtenerFeedback($campo) {
    let $fb = $campo.siblings(".feedback-validacion");
    if ($fb.length === 0) {
        $fb = $('<small class="feedback-validacion form-text d-block"></small>');
        $campo.after($fb);
    }
    return $fb;
}

export function limpiarValidaciones(contenedor) {
    const $c = $(contenedor);
    $c.find(".is-valid, .is-invalid").removeClass("is-valid is-invalid");
    $c.find(".feedback-validacion, .feedback-validación").remove();
}

export function soloNumerosTeclado(e) {
    if (['Backspace', 'Tab', 'ArrowLeft', 'ArrowRight', 'Delete', 'Enter'].includes(e.key)) return;
    if (!/^[0-9]$/.test(e.key)) e.preventDefault();
}

export function soloLetrasTeclado(e) {
    if (['Backspace', 'Tab', 'ArrowLeft', 'ArrowRight', 'Delete', 'Enter', '.'].includes(e.key)) return;
    if (!/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]$/.test(e.key)) e.preventDefault();
}

export function validarCampoNombrePatron(input, nombrePatron, mensajeError) {
    if (!input || input.length === 0) return false;
    const $input = $(input);
    const domInput = $input[0];
    let feedback = domInput.nextElementSibling;

    if (!feedback || !feedback.classList.contains("feedback-validación")) {
        feedback = document.createElement("small");
        feedback.classList.add("feedback-validación", "form-text");
        domInput.parentNode.appendChild(feedback);
    }

    const esValido = validar(nombrePatron, $input.val());

    if (esValido) {
        $input.removeClass("is-invalid").addClass("is-valid");
        feedback.textContent = "Campo válido";
        feedback.style.color = "#198754";
        return true;
    } else {
        $input.removeClass("is-valid").addClass("is-invalid");
        feedback.textContent = mensajeError;
        feedback.style.color = "#dc3545";
        return false;
    }
}

/**
 * Valida un campo con jQuery.
 */
export function validarCampo($campo, opciones) {
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
                regex = obtenerPatron(opciones.patron);
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