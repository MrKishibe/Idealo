const CONFIG_TALLAS = {
    "Ropa": {
        tallas: ["s", "m", "l", "xl", "xxl"],
        lblPrenda: "Tipo de Prenda",
        placeholderPrenda: "Ej: Camiseta, Camisa, Pantalón",
        placeholderMaterial: "Ej: Algodón, Poliéster, Lino",
        lblTalla: "Tallas disponibles (Ropa)"
    },
    "Accesorio": {
        tallas: ["pequeño", "mediano", "grande", "extragrande"],
        lblPrenda: "Tipo de Accesorio",
        placeholderPrenda: "Ej: Bolso, Gorra, Mochila, Cartera",
        placeholderMaterial: "Ej: Cuero, Lona, Sintético, Metal",
        lblTalla: "Tamaños / Medidas (Accesorio)"
    }
};

let tablaProductos = null;

function initTablaProductos() {
    if ($.fn.DataTable.isDataTable('#tablaProductos')) {
        $('#tablaProductos').DataTable().destroy();
    }

    tablaProductos = $('#tablaProductos').DataTable({
        language: {
            lengthMenu: "Mostrar _MENU_ registros",
            zeroRecords: "No se encontraron resultados",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando 0 a 0 de 0 registros",
            infoFiltered: "(filtrado de un total de _MAX_ registros)",
            search: "Buscar:",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior"
            }
        },
        pageLength: 10,
        responsive: true,
        order: [[0, 'desc']]
    });
}

function filtrarPorEstado(estado) {
    const patron = '(^|[\\s\\-])' + estado + '([\\s\\-]|$)';
    tablaProductos.column(7).search(patron, true, false).draw();
}

function aplicarFiltroEstado() {
    const btnEstado = document.getElementById("btnAlternarEstado");
    const vista = btnEstado ? btnEstado.getAttribute("data-vista") : "activos";
    filtrarPorEstado(vista === "inhabilitados" ? "inactivo" : "activo");
}

function recargarTabla() {
    $.ajax({
        url: window.location.href,
        type: 'GET',
        dataType: 'html',
        success: function (html) {
            const doc = $('<div></div>').html(html);
            const nuevoTbody = doc.find('#tablaProductos tbody');
            const tbodyActual = $('#tablaProductos tbody');

            if (nuevoTbody.length && tbodyActual.length) {
                tablaProductos.destroy();
                tbodyActual.html(nuevoTbody.html());
                initTablaProductos();
                aplicarFiltroEstado();
            }
        },
        error: function () {
            location.reload();
        }
    });
}

// ===== Validaciones (jQuery + helpers/expresiones.js) =====
function validarFormularioProducto($form) {
    const prefijo = $form.is('#formRegistrarProducto') ? 'reg' : 'edit_activo';
    const expNombre = /^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ\s.,#\-]{3,150}$/;

    let valido = true;

    valido = $.validarCampo($('#' + prefijo + '_nombre_producto'), {
        patron: expNombre,
        mensaje: 'El nombre del producto debe tener entre 3 y 150 caracteres.'
    }) && valido;

    valido = $.validarCampo($('#' + prefijo + '_tipo_de_prenda'), {
        patron: /^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s.,#\-/&()']{1,150}$/,
        mensaje: 'El tipo de prenda o accesorio contiene caracteres no válidos.'
    }) && valido;

    valido = $.validarCampo($('#' + prefijo + '_detalle_material'), {
        patron: /^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s.,#\-/&()']{1,150}$/,
        mensaje: 'El detalle del material contiene caracteres no válidos.'
    }) && valido;

    valido = $.validarCampo($('#' + prefijo + '_color'), {
        patron: /^[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s.,#\-/&()']{1,150}$/,
        mensaje: 'El color contiene caracteres no válidos.'
    }) && valido;

    return valido;
}

$(document).ready(function () {
    initTablaProductos();

    $('#btnAlternarEstado').on('click', function () {
        const vistaActual = $(this).attr('data-vista');

        if (vistaActual === 'activos') {
            $(this).attr('data-vista', 'inhabilitados');
            $('#txtBotonEstado').text('Ver Activos');
            $('#iconoEstado').attr('src', 'assets/Img/Iconos/eye.svg');
            $('#tituloVista').text('Productos Inhabilitados');
            filtrarPorEstado('inactivo');
        } else {
            $(this).attr('data-vista', 'activos');
            $('#txtBotonEstado').text('Ver inhabilitados');
            $('#iconoEstado').attr('src', 'assets/Img/Iconos/eye-slash.svg');
            $('#tituloVista').text('Catálogo de Productos');
            filtrarPorEstado('activo');
        }
    });
    filtrarPorEstado('activo');

    $('#reg_tipo_de_producto').on('change', function () {
        actualizarCamposPorTipo('reg', this.value);
    });

    $('#edit_activo_tipo_de_producto').on('change', function () {
        const seleccionadas = obtenerTallasSeleccionadas('edit');
        actualizarCamposPorTipo('edit', this.value, seleccionadas);
    });

    // Revalida en tiempo real los campos ya marcados como inválidos
    $('#modalRegistrarProducto, #modalEditarActivo').on('input change', 'input, select', function () {
        const $campo = $(this);
        if ($campo.hasClass('is-invalid')) {
            validarFormularioProducto($campo.closest('form'));
        }
    });

    // EDITAR PRODUCTO ACTIVO
    $(document).on('click', '.btnEditarActivo', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const tipo = $(this).data('tipo') || 'Ropa';
        const prenda = $(this).data('prenda');
        const material = $(this).data('material');
        const color = $(this).data('color');
        const tallasStr = $(this).data('tallas') || '';
        const tallasArray = tallasStr ? tallasStr.toString().split(',').map(s => s.trim()) : [];

        $('#edit_activo_id_producto').val(id);
        $('#edit_activo_nombre_producto').val(nombre);
        $('#edit_activo_tipo_de_producto').val(tipo);
        $('#edit_activo_tipo_de_prenda').val(prenda);
        $('#edit_activo_detalle_material').val(material);
        $('#edit_activo_color').val(color);
        $('#edit_activo_status_producto').val('activo');
        $.limpiarValidaciones('#formEditarActivo');

        actualizarCamposPorTipo('edit', tipo, tallasArray);

        $('#modalEditarActivo').modal('show');
    });

    $(document).on('click', '.btnEditarInactivo', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');

        $('#edit_inactivo_id_producto').val(id);
        $('#edit_inactivo_nombre').val(nombre);
        $('#edit_inactivo_status').val('inactivo');

        $('#modalEditarInactivo').modal('show');
    });

    $('#btnGuardarEdicionInactivo').on('click', function () {
        const id = $('#edit_inactivo_id_producto').val();
        const nuevoEstado = $('#edit_inactivo_status').val();

        if (nuevoEstado === 'inactivo') {
            $('#modalEditarInactivo').modal('hide');
            return;
        }

        $.ajax({
            url: 'index.php?controller=producto&action=cambiarEstado',
            type: 'POST',
            data: { id_producto: id, status_producto: nuevoEstado },
            dataType: 'json',
            success: function (data) {
                if (data.status === 'success') {
                    $('#modalEditarInactivo').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: '¡Reactivado!',
                        text: data.message,
                        confirmButtonColor: '#1e5631'
                    }).then(() => recargarTabla());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' });
            }
        });
    });

    // INHABILITAR PRODUCTO
    $(document).on('click', '.btnCambiarEstado', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');

        Swal.fire({
            title: '¿Inactivar producto?',
            text: `El producto "${nombre}" y sus variantes pasarán a la sección de inhabilitados.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Sí, inactivar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'index.php?controller=producto&action=cambiarEstado',
                    type: 'POST',
                    data: { id_producto: id, status_producto: 'inactivo' },
                    dataType: 'json',
                    success: function (data) {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Inactivado!',
                                text: data.message,
                                confirmButtonColor: '#1e5631'
                            }).then(() => recargarTabla());
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                        }
                    },
                    error: function () {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' });
                    }
                });
            }
        });
    });

    // REGISTRAR PRODUCTO
    $('#formRegistrarProducto').on('submit', function (e) {
        e.preventDefault();

        $.limpiarValidaciones('#formRegistrarProducto');

        const valido = validarFormularioProducto($('#formRegistrarProducto'));

        if (obtenerTallasSeleccionadas('reg').length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Talla requerida',
                text: 'Debe seleccionar al menos una talla o medida para el producto.'
            });
            return;
        }

        if (!valido) {
            Swal.fire('Datos inválidos', 'Corrige los campos marcados en rojo.', 'warning');
            return;
        }

        $.ajax({
            url: 'index.php?controller=producto&action=guardar',
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (data) {
                if (data.status === 'success') {
                    $('#modalRegistrarProducto').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: '¡Registrado!',
                        text: data.message,
                        confirmButtonColor: '#1e5631'
                    }).then(() => recargarTabla());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' });
            }
        });
    });

    // EDITAR PRODUCTO ACTIVO
    $('#formEditarActivo').on('submit', function (e) {
        e.preventDefault();

        $.limpiarValidaciones('#formEditarActivo');

        const valido = validarFormularioProducto($('#formEditarActivo'));

        if (obtenerTallasSeleccionadas('edit').length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Talla requerida',
                text: 'Debe seleccionar al menos una talla o medida para el producto.'
            });
            return;
        }

        if (!valido) {
            Swal.fire('Datos inválidos', 'Corrige los campos marcados en rojo.', 'warning');
            return;
        }

        $.ajax({
            url: 'index.php?controller=producto&action=guardar',
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (data) {
                if (data.status === 'success') {
                    $('#modalEditarActivo').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: '¡Actualizado!',
                        text: data.message,
                        confirmButtonColor: '#1e5631'
                    }).then(() => recargarTabla());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' });
            }
        });
    });

    limpiarFormularioCrear();
});

function actualizarCamposPorTipo(prefix, tipo, tallasSeleccionadas = []) {
    const tipoKey = (tipo.toLowerCase().includes('accesorio')) ? 'Accesorio' : 'Ropa';
    const config = CONFIG_TALLAS[tipoKey];

    const $lblPrenda = $('#' + prefix + '_lbl_subtipo');
    const $inputPrenda = $('#' + prefix + '_tipo_de_prenda');
    const $inputMaterial = $('#' + prefix + '_detalle_material');
    const $lblTalla = $('#' + prefix + '_lbl_talla');
    const $contenedorTallas = $('#' + prefix + '_contenedor_tallas');

    if ($lblPrenda.length) $lblPrenda.text(config.lblPrenda);
    if ($inputPrenda.length) $inputPrenda.attr('placeholder', config.placeholderPrenda);
    if ($inputMaterial.length) $inputMaterial.attr('placeholder', config.placeholderMaterial);
    if ($lblTalla.length) $lblTalla.text(config.lblTalla);

    if ($contenedorTallas.length) {
        $contenedorTallas.empty();
        config.tallas.forEach((talla, index) => {
            const idCheck = `${prefix}_talla_${index}_${talla}`;
            const estaMarcada = tallasSeleccionadas.some(t => t.toLowerCase() === talla.toLowerCase());

            $contenedorTallas.append(
                $('<input>')
                    .attr('type', 'checkbox')
                    .addClass('btn-check')
                    .attr('name', 'tallas[]')
                    .attr('id', idCheck)
                    .attr('value', talla)
                    .attr('autocomplete', 'off')
                    .prop('checked', estaMarcada),
                $('<label>')
                    .addClass('btn btn-outline-success btn-sm rounded-pill px-3 py-1 fw-semibold')
                    .attr('for', idCheck)
                    .text(talla.toUpperCase())
            );
        });
    }
}

function obtenerTallasSeleccionadas(prefix) {
    const $contenedor = $('#' + prefix + '_contenedor_tallas');
    if ($contenedor.length === 0) return [];
    const tallas = [];
    $contenedor.find('input[name="tallas[]"]:checked').each(function () {
        tallas.push($(this).val());
    });
    return tallas;
}

function seleccionarTodasTallas(prefix) {
    $('#' + prefix + '_contenedor_tallas').find('input[name="tallas[]"]').prop('checked', true);
}

function deseleccionarTodasTallas(prefix) {
    $('#' + prefix + '_contenedor_tallas').find('input[name="tallas[]"]').prop('checked', false);
}

function limpiarFormularioCrear() {
    const $form = $('#formRegistrarProducto');
    if ($form.length) $form.trigger('reset');
    $.limpiarValidaciones('#formRegistrarProducto');

    const $regTipo = $('#reg_tipo_de_producto');
    if ($regTipo.length) {
        $regTipo.val('Ropa');
        actualizarCamposPorTipo('reg', 'Ropa');
    }
}