let tablaFinanzas;

window.inicializarTabla = function() {
    tablaFinanzas = $('.custom-table').DataTable({
        language: {
            "sProcessing":     "Procesando...",
            "sLengthMenu":     "Mostrar _MENU_ registros",
            "sZeroRecords":    "No se encontraron resultados",
            "sEmptyTable":     "Ningún dato disponible en esta tabla",
            "sInfo":           "Mostrando del _START_ al _END_ de _TOTAL_ registros",
            "sInfoEmpty":      "Mostrando del 0 al 0 de 0 registros",
            "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
            "sSearch":         "Buscar:",
            "oPaginate": {
                "sFirst":    "Primero",
                "sLast":     "Último",
                "sNext":     "Siguiente",
                "sPrevious": "Anterior"
            }
        },
        pageLength: 10,
        responsive: true
    });
};

window.recargarDatos = function() {
    fetch(window.location.href)
    .then(r => r.text())
    .then(html => {
        let parser = new DOMParser();
        let doc = parser.parseFromString(html, 'text/html');
        let newTbody = doc.querySelector('.custom-table tbody').innerHTML;
        tablaFinanzas.destroy();
        document.querySelector('.custom-table tbody').innerHTML = newTbody;
        window.inicializarTabla();
        tablaFinanzas.draw();
    });
};


window.limpiarFormulario = function(btn) {
    let form = $(btn).closest('form');
    if (form.length > 0) {
        form[0].reset(); // Resetea los valores escritos
        
   
        form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        form.find('.feedback-validacion').text('');
    }
};

$(document).ready(function () {
    window.inicializarTabla();

    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        let rowNode = settings.aoData[dataIndex].nTr;
        let estado = $(rowNode).attr('data-estado');
        let btn = document.getElementById('btnAlternarEstado');
        
        if(!btn) return true;

        let vistaActual = btn.getAttribute('data-vista');
        if (vistaActual === 'activos') {
            return estado !== 'inhabilitado';
        } else {
            return estado === 'inhabilitado';
        }
    });

    if (tablaFinanzas) {
        tablaFinanzas.draw();
    }

    const regexTitular = /^[a-zA-ZñÑáéíóúÁÉÍÓÚ0-9\s\.\-]{3,60}$/;
    const regexIdentificador = /^[0-9]{20}$/; 
    const regexMetodo = /^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]{3,50}$/; 
    const regexMonto = /^[0-9]+(\.[0-9]{1,2})?$/; 
    const regexReferencia = /^[0-9]{6}$/;

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

        if (valor === '' && !domInput.hasAttribute('required')) {
            input.removeClass("is-invalid").addClass("is-valid");
            feedback.textContent = "";
            return true;
        }

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

    
    $(document).on('input', '.finanzas-form input[name="referencia"]', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    $(document).on('keyup blur', '.finanzas-form input[name="titular"]', function() {
        validarCampo($(this), regexTitular, "Titular inválido (Debe tener entre 3 y 60 caracteres).");
    });
    $(document).on('keyup blur', '.finanzas-form input[name="identificador"]', function() {
        let val = $(this).val().trim();
        let msj = (val.length !== 20) 
            ? `Debe tener exactamente 20 números (Actualmente: ${val.length})` 
            : "Identificador inválido (Solo números).";
        validarCampo($(this), regexIdentificador, msj);
    });
    $(document).on('keyup blur', '.finanzas-form input[name="nombre_metodo_de_pago"]', function() {
        validarCampo($(this), regexMetodo, "Nombre inválido (Solo letras, sin números ni símbolos).");
    });
    
    $(document).on('keyup blur', '.finanzas-form input[name="monto_pago"]', function() {
        let esValido = validarCampo($(this), regexMonto, "Monto inválido (Solo números positivos).");
        if (esValido && parseFloat($(this).val()) <= 0) {
            $(this).removeClass("is-valid").addClass("is-invalid");
            let feedback = this.nextElementSibling;
            if(feedback) { feedback.textContent = "El monto debe ser mayor a 0."; feedback.style.color = "#dc3545"; }
        }
    });
    
    $(document).on('keyup blur', '.finanzas-form input[name="referencia"]', function() {
        let val = $(this).val().trim();
        let msj = (val.length !== 6) 
            ? `Debe tener exactamente 6 números (Actualmente: ${val.length})` 
            : "Referencia inválida (Solo números permitidos).";
        validarCampo($(this), regexReferencia, msj);
    });

    $('.finanzas-form').on('submit', function (e) {
        e.preventDefault();
        let formularioValido = true;
        let formActual = $(this); 

        let inputTitular = formActual.find('input[name="titular"]');
        let inputIdentificador = formActual.find('input[name="identificador"]');
        let inputMetodo = formActual.find('input[name="nombre_metodo_de_pago"]');
        let inputMonto = formActual.find('input[name="monto_pago"]');
        let inputReferencia = formActual.find('input[name="referencia"]');

        if (inputTitular.length > 0) { if (!validarCampo(inputTitular, regexTitular, "Titular inválido.")) formularioValido = false; }
        if (inputIdentificador.length > 0) { if (!validarCampo(inputIdentificador, regexIdentificador, "Debe tener 20 números exactos.")) formularioValido = false; }
        if (inputMetodo.length > 0) { if (!validarCampo(inputMetodo, regexMetodo, "Nombre del método inválido.")) formularioValido = false; }
        
        if (inputMonto.length > 0) { 
            if (!validarCampo(inputMonto, regexMonto, "Monto inválido.")) formularioValido = false; 
            if (parseFloat(inputMonto.val()) <= 0) formularioValido = false;
        }
        
        if (inputReferencia.length > 0) { 
            if (!validarCampo(inputReferencia, regexReferencia, "Referencia obligatoria de 6 números.")) formularioValido = false; 
        }

        if (!formularioValido) {
            Swal.fire({ icon: 'warning', title: 'Atención', text: 'Corrija los campos marcados en rojo antes de guardar.' });
            return; 
        }

        const formData = new FormData(this);
        fetch(formActual.attr('action'), { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({ icon: 'success', title: '¡Éxito!', text: data.message, timer: 1500, showConfirmButton: false }).then(() => {
                    formActual[0].reset();
                    if (typeof bootstrap !== 'undefined') {
                        const modales = document.querySelectorAll('.modal.show');
                        modales.forEach(modal => {
                            const modalInstance = bootstrap.Modal.getInstance(modal);
                            if (modalInstance) modalInstance.hide();
                        });
                    }
                    window.recargarDatos();
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Ocurrió un error en el servidor.' });
            }
        })
        .catch(error => {
            Swal.fire({ icon: 'error', title: 'Error Crítico', text: 'Fallo de conexión con el servidor.' });
        });
    });
});

window.alternarVistaInhabilitados = function(tablaId) {
    const btn = document.getElementById('btnAlternarEstado');
    if (!btn) return;
    
    const esActivo = btn.getAttribute('data-vista') === 'activos';
    const nuevoEstado = esActivo ? 'inhabilitados' : 'activos';
    
    btn.setAttribute('data-vista', nuevoEstado);
    document.getElementById('txtBotonEstado').textContent = esActivo ? 'Ver activos' : 'Ver inhabilitados';
    document.getElementById('iconoEstado').className = esActivo ? 'bi bi-eye-fill me-1' : 'bi bi-eye-slash-fill me-1';
    
    const btnPdf = document.getElementById('btnGenerarReporteCuentas') || 
                   document.getElementById('btnGenerarReporteMetodos') || 
                   document.getElementById('btnGenerarReportePagos');
                   
    if (btnPdf && btnPdf.tagName === 'A') {
        let url = new URL(btnPdf.href);
        url.searchParams.set('estado', nuevoEstado);
        btnPdf.href = url.toString();
    }

    $('#' + tablaId).DataTable().draw();
};

window.cambiarEstado = function(id, entidad, nuevoEstado) {
    const titulo = (nuevoEstado === 'inhabilitado') ? '¿Inactivar registro?' : '¿Habilitar registro?';
    Swal.fire({
        title: titulo, text: 'Esta acción cambiará el estado del registro.', icon: 'warning', showCancelButton: true,
        confirmButtonColor: (nuevoEstado === 'inhabilitado') ? '#d33' : '#28a745', confirmButtonText: 'Confirmar', cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const params = new URLSearchParams();
            params.append('accion', 'cambiar_estado'); params.append('entidad', entidad);
            params.append('id', id); params.append('nuevo_estado', nuevoEstado);

            fetch('index.php?controller=Finanzas', {
                method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: params
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ title: '¡Éxito!', text: data.message, icon: 'success', timer: 1500, showConfirmButton: false }).then(() => {
                        window.recargarDatos();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(() => Swal.fire('Error', 'No se pudo contactar al servidor', 'error'));
        }
    });
};