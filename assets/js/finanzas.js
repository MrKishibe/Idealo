$(document).ready(function () {
    console.log("Inicialización: finanzas.js listo con validaciones dinámicas y control de estados.");

    // 1. Expresiones regulares
    const regexTitular = /^[a-zA-ZñÑáéíóúÁÉÍÓÚ0-9\s\.\-]{3,60}$/;
    const regexIdentificador = /^[0-9]{20}$/; // Exáctamente 20 números

    // 2. Renderizador visual de alertas
    function validarCampo(input, regex, mensajeError) {
        if (!input || input.length === 0) return false;
        let domInput = input[0];
        let feedback = domInput.nextElementSibling;
        
        if (!feedback || !feedback.classList.contains("feedback-validacion")) {
            feedback = document.createElement("small");
            feedback.classList.add("feedback-validacion", "form-text");
            domInput.parentNode.appendChild(feedback);
        }

        let valor = input.val() ? input.val().trim() : '';

        if (regex.test(valor)) {
            input.removeClass("is-invalid").addClass("is-valid");
            feedback.textContent = "Campo válido";
            feedback.style.color = "#198754";
            return true;
        } else {
            input.removeClass("is-valid").addClass("is-invalid");
            feedback.textContent = mensajeError;
            feedback.style.color = "#dc3545";
            return false;
        }
    }

    // 3. Eventos en tiempo real: USAMOS NAME EN VEZ DE ID PARA EVITAR CONFLICTOS ENTRE MODALES
    $(document).on('keyup blur', '.finanzas-form input[name="titular"]', function() {
        validarCampo($(this), regexTitular, "Titular inválido (Debe tener entre 3 y 60 caracteres).");
    });

    $(document).on('keyup blur', '.finanzas-form input[name="identificador"]', function() {
        // Mensaje de error personalizado para los 20 dígitos
        let val = $(this).val().trim();
        let msj = (val.length !== 20) 
            ? `Debe tener exactamente 20 números (Actualmente: ${val.length})` 
            : "Identificador inválido (Solo números).";
            
        validarCampo($(this), regexIdentificador, msj);
    });

    // 4. Intercepción de Formularios
    $('.finanzas-form').on('submit', function (e) {
        e.preventDefault();
        
        let formularioValido = true;
        let formActual = $(this); 

        // Buscar campos específicamente DENTRO del formulario actual
        let inputTitular = formActual.find('input[name="titular"]');
        let inputIdentificador = formActual.find('input[name="identificador"]');

        if (inputTitular.length > 0) {
            let vTitular = validarCampo(inputTitular, regexTitular, "Titular inválido.");
            if (!vTitular) formularioValido = false;
        }

        if (inputIdentificador.length > 0) {
            let vIdentificador = validarCampo(inputIdentificador, regexIdentificador, "Debe tener 20 números exactos.");
            if (!vIdentificador) formularioValido = false;
        }

        if (!formularioValido) {
            Swal.fire({ icon: 'warning', title: 'Atención', text: 'Corrija los campos marcados en rojo antes de guardar.' });
            return; 
        }

        const formData = new FormData(this);

        fetch(formActual.attr('action'), {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({ icon: 'success', title: '¡Éxito!', text: data.message })
                    .then(() => location.reload());
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Ocurrió un error en el servidor.' });
            }
        })
        .catch(error => {
            Swal.fire({ icon: 'error', title: 'Error Crítico', text: 'Fallo de conexión con el servidor.' });
        });
    });
});

// ==========================================
// FUNCIONES GLOBALES: ESTADOS Y VISTAS
// ==========================================

// Función centralizada para alternar vistas (Activos/Inhabilitados)
window.alternarVistaInhabilitados = function(tablaId) {
    const btn = document.getElementById('btnAlternarEstado');
    if (!btn) return;
    
    const filas = document.querySelectorAll(`#${tablaId} tbody tr`);
    const esActivo = btn.getAttribute('data-vista') === 'activos';

    filas.forEach(fila => {
        const estado = fila.getAttribute('data-estado');
        fila.style.display = (esActivo) 
            ? (estado === 'inhabilitado' ? 'none' : '') 
            : (estado === 'activo' ? 'none' : '');
    });

    btn.setAttribute('data-vista', esActivo ? 'inhabilitados' : 'activos');
    document.getElementById('txtBotonEstado').textContent = esActivo ? 'Ver inhabilitados' : 'Ver activos';
    document.getElementById('iconoEstado').className = esActivo ? 'bi bi-eye-slash-fill me-1' : 'bi bi-eye-fill me-1';
};

// Función para inactivar o restaurar (habilitar) registros
window.cambiarEstado = function(id, entidad, nuevoEstado) {
    const titulo = (nuevoEstado === 'inhabilitado') ? '¿Inactivar registro?' : '¿Habilitar registro?';
    Swal.fire({
        title: titulo,
        text: 'Esta acción cambiará el estado del registro.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: (nuevoEstado === 'inhabilitado') ? '#d33' : '#28a745',
        confirmButtonText: 'Confirmar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const params = new URLSearchParams();
            params.append('accion', 'cambiar_estado');
            params.append('entidad', entidad);
            params.append('id', id);
            params.append('nuevo_estado', nuevoEstado); // Ej: 'inhabilitado' o 'activo'

            fetch('index.php?controller=Finanzas', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('¡Éxito!', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(() => Swal.fire('Error', 'No se pudo contactar al servidor', 'error'));
        }
    });
};