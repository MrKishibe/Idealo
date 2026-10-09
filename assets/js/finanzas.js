console.log("jQuery se conecto bein.");

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

window.recargarDatos = async function() {
    try {
        const html = await $.ajax({
            url: window.location.href,
            method: 'GET',
            dataType: 'html'
        });
        
        let newTbody = $(html).find('.custom-table tbody').html();
        if (tablaFinanzas) tablaFinanzas.destroy();
        $('.custom-table tbody').html(newTbody);
        window.inicializarTabla();
        tablaFinanzas.draw();
    } catch (error) {
        console.error("Error recargando los datos:", error);
    }
};

window.limpiarFormulario = function(btn) {
    let $form = $(btn).closest('form');
    if ($form.length > 0) {
        $form[0].reset();
        $form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        $form.find('.feedback-validacion').text('');
    }
};

$(document).ready(function () {
    window.inicializarTabla();

    // Filtro personalizado de DataTable
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        let rowNode = settings.aoData[dataIndex].nTr;
        let estado = $(rowNode).attr('data-estado');
        let $btn = $('#btnAlternarEstado');
        
        if($btn.length === 0) return true;

        let vistaActual = $btn.attr('data-vista');
        if (vistaActual === 'activos') {
            return estado !== 'inhabilitado';
        } else {
            return estado === 'inhabilitado';
        }
    });

    if (tablaFinanzas) tablaFinanzas.draw();

    // Función de validación adaptada para usar $.expresionesRegulares
    function validarCampo($input, nombrePatron, mensajeError) {
        if (!$input || $input.length === 0) return false;
        
        let $feedback = $input.next('.feedback-validacion');
        if ($feedback.length === 0) {
            $feedback = $('<small class="feedback-validacion form-text"></small>');
            $input.after($feedback);
        }

        let valor = $input.val() ? $input.val().trim() : '';

        if (valor === '' && !$input.prop('required')) {
            $input.removeClass("is-invalid").addClass("is-valid");
            $feedback.text("").css('color', '');
            return true;
        }

        // Llamada al helper externo
        if ($.expresionesRegulares.validar(nombrePatron, valor)) {
            $input.removeClass("is-invalid").addClass("is-valid");
            $feedback.text("Campo válido").css('color', '#198754');
            return true;
        } else {
            $input.removeClass("is-valid").addClass("is-invalid");
            $feedback.text(mensajeError).css('color', '#dc3545');
            return false;
        }
    }

    // Eventos de validación en tiempo real
    $(document).on('input', '.finanzas-form input[name="referencia"]', function() {
        $(this).val($(this).val().replace(/[^0-9]/g, ''));
    });

    $(document).on('keyup blur', '.finanzas-form input[name="titular"]', function() {
        validarCampo($(this), 'titularCuenta', "Titular inválido (Debe tener entre 3 y 60 caracteres).");
    });
    
    $(document).on('keyup blur', '.finanzas-form input[name="identificador"]', function() {
        let val = $(this).val().trim();
        let msj = (val.length !== 20) 
            ? "Debe tener exactamente 20 números (Actualmente: " + val.length + ")" 
            : "Identificador inválido (Solo números).";
        validarCampo($(this), 'identificadorCuenta', msj);
    });
    
    $(document).on('keyup blur', '.finanzas-form input[name="nombre_metodo_de_pago"]', function() {
        validarCampo($(this), 'metodoPago', "Nombre inválido (Solo letras, sin números ni símbolos).");
    });
    
    $(document).on('keyup blur', '.finanzas-form input[name="monto_pago"]', function() {
        let esValido = validarCampo($(this), 'moneda', "Monto inválido (Solo números positivos).");
        if (esValido && parseFloat($(this).val()) <= 0) {
            $(this).removeClass("is-valid").addClass("is-invalid");
            let $feedback = $(this).next('.feedback-validacion');
            if($feedback.length > 0) { 
                $feedback.text("El monto debe ser mayor a 0.").css('color', '#dc3545'); 
            }
        }
    });
    
    $(document).on('keyup blur', '.finanzas-form input[name="referencia"]', function() {
        let val = $(this).val().trim();
        let msj = (val.length !== 6) 
            ? "Debe tener exactamente 6 números (Actualmente: " + val.length + ")" 
            : "Referencia inválida (Solo números permitidos).";
        validarCampo($(this), 'referenciaPago', msj);
    });

    // Delegación de eventos en 'document' para asegurar la captura sin recargar
    $(document).on('submit', '.finanzas-form', async function (e) {
        e.preventDefault();
        
        let formularioValido = true;
        let $formActual = $(this); 

        let $inputTitular = $formActual.find('input[name="titular"]');
        let $inputIdentificador = $formActual.find('input[name="identificador"]');
        let $inputMetodo = $formActual.find('input[name="nombre_metodo_de_pago"]');
        let $inputMonto = $formActual.find('input[name="monto_pago"]');
        let $inputReferencia = $formActual.find('input[name="referencia"]');

        if ($inputTitular.length > 0) { if (!validarCampo($inputTitular, 'titularCuenta', "Titular inválido.")) formularioValido = false; }
        if ($inputIdentificador.length > 0) { if (!validarCampo($inputIdentificador, 'identificadorCuenta', "Debe tener 20 números exactos.")) formularioValido = false; }
        if ($inputMetodo.length > 0) { if (!validarCampo($inputMetodo, 'metodoPago', "Nombre del método inválido.")) formularioValido = false; }
        
        if ($inputMonto.length > 0) { 
            if (!validarCampo($inputMonto, 'moneda', "Monto inválido.")) formularioValido = false; 
            if (parseFloat($inputMonto.val()) <= 0) formularioValido = false;
        }
        
        if ($inputReferencia.length > 0) { 
            if (!validarCampo($inputReferencia, 'referenciaPago', "Referencia obligatoria de 6 números.")) formularioValido = false; 
        }

        if (!formularioValido) {
            Swal.fire({ icon: 'warning', title: 'Atención', text: 'Corrija los campos marcados en rojo antes de guardar.' });
            return; 
        }

        const formData = new FormData(this);

        try {
            const data = await $.ajax({
                url: $formActual.attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json'
            });

            if (data.success) {
                await Swal.fire({ icon: 'success', title: '¡Éxito!', text: data.message, timer: 1500, showConfirmButton: false });
                $formActual[0].reset();
                if (typeof bootstrap !== 'undefined') {
                    $('.modal.show').each(function() {
                        const modalInstance = bootstrap.Modal.getInstance(this);
                        if (modalInstance) modalInstance.hide();
                    });
                }
                await window.recargarDatos();
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Ocurrió un error en el servidor.' });
            }
        } catch (error) {
            console.error("Fallo AJAX en formulario: ", error);
            Swal.fire({ icon: 'error', title: 'Error Crítico', text: 'Fallo de conexión con el servidor al procesar el formulario.' });
        }
    });
});

window.alternarVistaInhabilitados = function(tablaId) {
    const $btn = $('#btnAlternarEstado');
    if ($btn.length === 0) return;
    
    const esActivo = $btn.attr('data-vista') === 'activos';
    const nuevoEstado = esActivo ? 'inhabilitados' : 'activos';
    
    $btn.attr('data-vista', nuevoEstado);
    $('#txtBotonEstado').text(esActivo ? 'Ver activos' : 'Ver inhabilitados');
    $('#iconoEstado').attr('class', esActivo ? 'bi bi-eye-fill me-1' : 'bi bi-eye-slash-fill me-1');
    
    const $btnPdf = $('#btnGenerarReporteCuentas, #btnGenerarReporteMetodos, #btnGenerarReportePagos');
                   
    if ($btnPdf.length > 0 && $btnPdf.prop('tagName') === 'A') {
        let url = new URL($btnPdf.prop('href'));
        url.searchParams.set('estado', nuevoEstado);
        $btnPdf.prop('href', url.toString());
    }

    if (tablaFinanzas) {
        tablaFinanzas.draw();
    }
};

window.cambiarEstado = async function(id, entidad, nuevoEstado) {
    const titulo = (nuevoEstado === 'inhabilitado') ? '¿Inactivar registro?' : '¿Habilitar registro?';
    
    const result = await Swal.fire({
        title: titulo, 
        text: 'Esta acción cambiará el estado del registro.', 
        icon: 'warning', 
        showCancelButton: true,
        confirmButtonColor: (nuevoEstado === 'inhabilitado') ? '#d33' : '#28a745', 
        confirmButtonText: 'Confirmar', 
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const data = await $.ajax({
                url: 'index.php?controller=Finanzas',
                method: 'POST',
                data: {
                    accion: 'cambiar_estado',
                    entidad: entidad,
                    id: id,
                    nuevo_estado: nuevoEstado
                },
                dataType: 'json'
            });

            if (data.success) {
                await Swal.fire({ title: '¡Éxito!', text: data.message, icon: 'success', timer: 1500, showConfirmButton: false });
                await window.recargarDatos();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (error) {
            console.error("Fallo AJAX al cambiar estado: ", error);
            Swal.fire('Error', 'No se pudo contactar al servidor al cambiar el estado', 'error');
        }
    }
};