import $ from 'jquery';

if (!$) {
    throw new Error("expresiones.js requiere jQuery.");
}

export const patrones = Object.freeze({
        letras: /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]+$/,
        nombre: /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s.'-]{2,100}$/,
        nombres: /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s.'-]{2,100}$/,
        apellido: /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s.'-]{2,100}$/,
        razonSocial: /^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s.,&'()/-]{2,150}$/,
        alfanumerico: /^[A-Za-z0-9]+$/,
        alfanumericoConEspacios: /^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s]+$/,
        usuario: /^[A-Za-z0-9_]{3,30}$/,
        correo: /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/,
        telefono: /^\+?[0-9\s().-]{7,20}$/,
        telefonoVenezuela: /^(?:(?:0412|0414|0416|0424|0426|02)[0-9]{7})$/,
        documentoVenezolano: /^[VvEeJjGgCc]?\s?-?[0-9]{7,10}$/,
        cedula: /^[VvEe]?-?[0-9]{6,10}$/,
        rifVenezolano: /^[JjGgVvEePpCc]-?[0-9]{8}-?[0-9]$/,
        direccion: /^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s.,#º°()/-]{5,250}$/,
        descripcion: /^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s.,;:¡!¿?'"()#º°/-]{3,500}$/,
        soloDigitos: /^[0-9]+$/,
        enteroPositivo: /^[1-9][0-9]*$/,
        numeroNoNegativo: /^(?:0|[1-9][0-9]*)(?:[.,][0-9]+)?$/,
        numeroDecimal: /^-?[0-9]+(?:[.,][0-9]+)?$/,
        moneda: /^[0-9]+(?:[.,][0-9]{1,2})?$/,
        porcentaje: /^(?:100|[1-9]?[0-9])(?:[.,][0-9]{1,2})?$/,
        contrasena: /^.{8,}$/,
        contrasenaSegura: /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}$/,
        url: /^https?:\/\/(?:[\w-]+\.)+[\w-]+(?:\/[^\s]*)?$/i,
        ipv4: /^(?:(?:25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])$/,
        codigoPostal: /^[A-Za-z0-9\s-]{3,12}$/,
        fechaISO: /^\d{4}-\d{2}-\d{2}$/,
        hora24: /^(?:[01]\d|2[0-3]):[0-5]\d$/,
        slug: /^[a-z0-9]+(?:-[a-z0-9]+)*$/,
        colorHex: /^#?(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/,
        tarjeta: /^[0-9]{13,19}$/,
        
        // nuevas expresiones para  finanzas (adrian chavales)
        titularCuenta: /^[a-zA-ZñÑáéíóúÁÉÍÓÚ0-9\s\.\-]{3,60}$/,
        identificadorCuenta: /^[0-9]{20}$/,
        metodoPago: /^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]{3,50}$/,
        referenciaPago: /^[0-9]{6}$/
});

export function obtenerPatron(nombre) {
    if (!Object.prototype.hasOwnProperty.call(patrones, nombre)) {
        throw new RangeError("No existe el patrón de validación: " + nombre);
    }

    return patrones[nombre];
}

export function marcarCampo(campo, esValido, opciones) {
    const $campo = $(campo);
    if ($campo.length === 0) {
        return false;
    }

    opciones = opciones || {};
    $campo.toggleClass("is-valid", esValido)
        .toggleClass("is-invalid", !esValido);

    if (opciones.estilo) {
        $campo.css({
            "border-color": esValido ? "green" : "red",
            "box-shadow": esValido ? "0 0 5px green" : "0 0 5px red"
        });
    }

    if (opciones.feedback !== false) {
        let $feedback = $campo.siblings(".feedback-validacion").first();
        if ($feedback.length === 0) {
            $feedback = $('<small class="feedback-validacion form-text d-block"></small>');
            $campo.after($feedback);
        }

        const mensaje = esValido
            ? (opciones.mensajeValido || "")
            : (opciones.mensaje || "El valor ingresado no es válido.");
        $feedback.text(mensaje)
            .css("color", esValido ? "#198754" : "#dc3545")
            .toggle(Boolean(mensaje));
    }

    return esValido;
}

export function validarCampo(campo, patron, opciones) {
    const $campo = $(campo);
    if ($campo.length === 0) {
        return false;
    }

    if (patron && typeof patron === "object" && !(patron instanceof RegExp)) {
        opciones = patron;
        patron = opciones.patron;
    }
    opciones = opciones || {};

    const valor = String($campo.val() ?? "").trim();
    const requerido = opciones.requerido !== false;
    let esValido = true;
    let mensaje = opciones.mensaje || "El valor ingresado no es válido.";

    if (valor === "") {
        esValido = !requerido;
        mensaje = opciones.mensajeVacio || "Este campo no puede estar vacío.";
    } else {
        let regex = null;
        if (typeof patron === "string") {
            regex = obtenerPatron(patron);
        } else if (patron instanceof RegExp) {
            regex = patron;
        }

        if (regex) {
            regex.lastIndex = 0;
            esValido = regex.test(valor);
        }

        if (esValido && typeof opciones.validar === "function") {
            const resultado = opciones.validar(valor, $campo);
            esValido = resultado === true;
            if (typeof resultado === "string") {
                mensaje = resultado;
            }
        }
    }

    return marcarCampo($campo, esValido, {
        estilo: opciones.estilo,
        feedback: opciones.feedback,
        mensaje: mensaje,
        mensajeValido: opciones.mensajeValido
    });
}

export function validarCampoConMensaje(campo, patron, mensaje, requerido) {
    return validarCampo(campo, patron, {
        mensaje: mensaje,
        requerido: requerido !== false
    });
}

export function validarLongitud(campo, minimo, maximo, opciones) {
    const $campo = $(campo);
    if ($campo.length === 0) {
        return false;
    }

    opciones = opciones || {};
    const valor = String($campo.val() ?? "").trim();
    const requerido = opciones.requerido !== false;
    const esValido = valor === ""
        ? !requerido
        : valor.length >= minimo && valor.length <= maximo;
    return marcarCampo($campo, esValido, {
        estilo: opciones.estilo,
        feedback: opciones.feedback,
        mensaje: valor === ""
            ? (opciones.mensajeVacio || "Este campo no puede estar vacío.")
            : (opciones.mensaje || "La longitud del valor no es válida.")
    });
}

export function validarNumeroPositivo(campo, opciones) {
    const $campo = $(campo);
    if ($campo.length === 0) {
        return false;
    }

    opciones = opciones || {};
    const valor = String($campo.val() ?? "").trim();
    const formatoValido = patrones.moneda.test(valor);
    const esValido = formatoValido && Number(valor.replace(",", ".")) > 0;
    return marcarCampo($campo, esValido, {
        estilo: opciones.estilo,
        feedback: opciones.feedback,
        mensaje: opciones.mensaje || "El monto debe ser un número mayor que cero."
    });
}

export function validar(nombre, valor) {
    if (valor === null || typeof valor === "undefined") {
        return false;
    }

    return obtenerPatron(nombre).test(String(valor).trim());
}

export function validarCampoEntrada(campo, patron, requerido) {
    return validarCampo(campo, patron, { requerido: requerido !== false });
}

export function validarFormulario(formulario, reglas, opciones) {
    const $formulario = $(formulario);
    if ($formulario.length === 0) {
        return false;
    }

    opciones = opciones || {};
    let formularioValido = true;
    $formulario.find("input, select, textarea").each(function () {
        const $campo = $(this);
        if ($campo.is(":disabled, [readonly], [type='hidden']")) {
            return true;
        }

        const regla = typeof reglas === "function"
            ? reglas(this)
            : (reglas && (reglas[this.name] || reglas[this.id]));

        if (regla) {
            if (!validarCampo($campo, regla)) {
                formularioValido = false;
            }
        } else if (opciones.validarNativo !== false && !this.checkValidity()) {
            marcarCampo($campo, false, {
                feedback: opciones.feedback,
                mensaje: opciones.mensajeNativo || "Revise el valor ingresado."
            });
            formularioValido = false;
        }
    });

    return formularioValido;
}

export function obtenerFechaLocalISO(diasDesdeHoy) {
    const fecha = new Date();
    fecha.setHours(0, 0, 0, 0);
    fecha.setDate(fecha.getDate() + (diasDesdeHoy || 0));
    const anio = fecha.getFullYear();
    const mes = String(fecha.getMonth() + 1).padStart(2, "0");
    const dia = String(fecha.getDate()).padStart(2, "0");
    return anio + "-" + mes + "-" + dia;
}

export function validarFecha(campo, obligatoria, diasMinimosDesdeHoy) {
    const $campo = $(campo);
    if ($campo.length === 0) {
        return false;
    }

    const valor = String($campo.val() ?? "").trim();
    const requerido = obligatoria !== false;
    if (valor === "" && !requerido) {
        return marcarCampo($campo, true);
    }

    const formatoValido = validar("fechaISO", valor);
    const fechaValida = formatoValido
        && valor >= obtenerFechaLocalISO(diasMinimosDesdeHoy);
    return marcarCampo($campo, fechaValida, {
        mensaje: "La fecha no es válida o no cumple con la fecha mínima."
    });
}

export function validarCoincidencia(campo, campoConfirmacion, opciones) {
    const $campo = $(campo);
    const $confirmacion = $(campoConfirmacion);
    if ($campo.length === 0 || $confirmacion.length === 0) {
        return false;
    }

    opciones = opciones || {};
    const valor = String($campo.val() ?? "");
    const confirmacion = String($confirmacion.val() ?? "");
    const opcionalVacio = opciones.requerido === false && valor === "";

    const esValido = opcionalVacio
        || (valor.length >= (opciones.minimo || 0)
            && valor.length <= (opciones.maximo || Infinity)
            && confirmacion !== ""
            && valor === confirmacion);

    marcarCampo($campo, opcionalVacio || valor.length >= (opciones.minimo || 0)
        && valor.length <= (opciones.maximo || Infinity), {
        mensaje: opciones.mensajeContrasena || "La contraseña no cumple con la longitud requerida."
    });
    return marcarCampo($confirmacion, esValido, {
        mensaje: confirmacion === ""
            ? (opciones.mensajeVacio || "Confirme el valor ingresado.")
            : (opciones.mensaje || "Los valores no coinciden.")
    });
}

export const validarCampoNombrePatron = validarCampoConMensaje;
export const validarNumero = validarCampoConMensaje;

export const expresionesRegulares = Object.freeze({
    patrones,
    obtener: obtenerPatron,
    validar,
    marcarCampo,
    validarCampo,
    validarCampoConMensaje,
    validarCampoEntrada,
    validarCampoNombrePatron,
    validarNumero,
    validarLongitud,
    validarNumeroPositivo,
    validarFormulario,
    validarFecha,
    validarCoincidencia,
    obtenerFechaLocalISO
});