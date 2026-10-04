(function ($) {
    "use strict";

    if (!$) {
        throw new Error("expresiones-regulares.js requiere jQuery.");
    }

    const patrones = Object.freeze({
        letras: /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]+$/,
        nombre: /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s.'-]{2,100}$/,
        alfanumerico: /^[A-Za-z0-9]+$/,
        alfanumericoConEspacios: /^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s]+$/,
        usuario: /^[A-Za-z0-9_]{3,30}$/,
        correo: /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/,
        telefono: /^\+?[0-9\s().-]{7,20}$/,
        telefonoVenezuela: /^(?:(?:0412|0414|0416|0424|0426|02)[0-9]{7})$/,
        documentoVenezolano: /^[VvEeJjGgCc]?\s?-?[0-9]{7,10}$/,
        soloDigitos: /^[0-9]+$/,
        enteroPositivo: /^[1-9][0-9]*$/,
        numeroDecimal: /^-?[0-9]+(?:[.,][0-9]+)?$/,
        moneda: /^[0-9]+(?:[.,][0-9]{1,2})?$/,
        contrasenaSegura: /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}$/,
        url: /^https?:\/\/(?:[\w-]+\.)+[\w-]+(?:\/[^\s]*)?$/i,
        ipv4: /^(?:(?:25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])$/,
        codigoPostal: /^[A-Za-z0-9\s-]{3,12}$/,
        fechaISO: /^\d{4}-\d{2}-\d{2}$/,
        hora24: /^(?:[01]\d|2[0-3]):[0-5]\d$/,
        slug: /^[a-z0-9]+(?:-[a-z0-9]+)*$/,
        colorHex: /^#?(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/,
        tarjeta: /^[0-9]{13,19}$/
    });

    $.expresionesRegulares = Object.freeze({
        patrones: patrones,

        obtener: function (nombre) {
            if (!Object.prototype.hasOwnProperty.call(patrones, nombre)) {
                throw new RangeError("No existe el patrón de validación: " + nombre);
            }

            return patrones[nombre];
        },

        validar: function (nombre, valor) {
            return this.obtener(nombre).test(String(valor));
        }
    });
})(window.jQuery);
