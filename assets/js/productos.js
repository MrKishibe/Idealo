import $ from 'jquery';
import { lenguajeDataTableEs } from './helpers/datatable-espanol.js';
import { successAlert, errorAlert, confirmActionAlert } from './helpers/alerts.js';
import { obtenerPatron, validarCampo } from './helpers/expresiones.js';
import { limpiarValidaciones } from './helpers/validaciones.js';

const CONFIG_TALLAS = {
    Ropa: {
        tallas: ['s', 'm', 'l', 'xl', 'xxl'],
        lblPrenda: 'Tipo de Prenda',
        placeholderPrenda: 'Ej: Camiseta, Camisa, Pantalón',
        placeholderMaterial: 'Ej: Algodón, Poliéster, Lino',
        lblTalla: 'Tallas disponibles (Ropa)'
    },
    Accesorio: {
        tallas: ['pequeño', 'mediano', 'grande', 'extragrande'],
        lblPrenda: 'Tipo de Accesorio',
        placeholderPrenda: 'Ej: Bolso, Gorra, Mochila, Cartera',
        placeholderMaterial: 'Ej: Cuero, Lona, Sintético, Metal',
        lblTalla: 'Tamaños / Medidas (Accesorio)'
    }
};

const CAMPOS_TIPO = {
    reg: {
        label: 'reg_lbl_subtipo', prenda: 'reg_tipo_de_prenda', material: 'reg_detalle_material',
        tallaLabel: 'reg_lbl_talla', contenedor: 'reg_contenedor_tallas'
    },
    edit: {
        label: 'edit_lbl_subtipo', prenda: 'edit_activo_tipo_de_prenda', material: 'edit_activo_detalle_material',
        tallaLabel: 'edit_lbl_talla', contenedor: 'edit_contenedor_tallas'
    }
};

$(function () {
    const URL = 'index.php?controller=producto';
    let tabla = null;
    let verInactivos = false;

    const REGLAS = {
        nombre_producto: {
            patron: obtenerPatron('producto'),
            mensaje: 'El nombre del producto debe tener entre 3 y 150 caracteres.',
            mensajeVacio: 'El nombre del producto es obligatorio.'
        },
        tipo_de_prenda: {
            patron: obtenerPatron('detalleProducto'),
            mensaje: 'El tipo de prenda o accesorio contiene caracteres no válidos.',
            mensajeVacio: 'El tipo de prenda o accesorio es obligatorio.'
        },
        detalle_material: {
            patron: obtenerPatron('detalleProducto'),
            mensaje: 'El detalle del material contiene caracteres no válidos.',
            mensajeVacio: 'El detalle del material es obligatorio.'
        },
        color: {
            patron: obtenerPatron('detalleProducto'),
            mensaje: 'El color contiene caracteres no válidos.',
            mensajeVacio: 'El color es obligatorio.'
        },
        tipo_de_producto: { mensajeVacio: 'Seleccione un tipo de producto.' }
    };

    const FORMS = {
        registro: { form: '#formRegistrarProducto', prefijo: 'reg', modal: 'modalRegistrarProducto', titulo: '¡Registrado!', reiniciar: true },
        edicion: { form: '#formEditarActivo', prefijo: 'edit', modal: 'modalEditarActivo', titulo: '¡Actualizado!', reiniciar: false }
    };

    const escapar = (texto) => $('<div>').text(texto ?? '').html();
    const activo = (producto) => String(producto.status_producto || '').trim().toLowerCase() === 'activo';
    const recargar = (reiniciar) => tabla.ajax.reload(null, Boolean(reiniciar));

    function deFila($boton) {
        const fila = tabla.row($boton.closest('tr'));
        return fila && fila.data() ? fila.data() : null;
    }

    function boton(clases, icono, alt, id) {
        return `<button type="button" class="${clases}" data-id="${escapar(id)}" title="${alt}">` +
            `<img src="assets/Img/Iconos/${icono}" class="icono-svg" alt="${alt}"></button>`;
    }

    function reactivar(id) {
        return `<button type="button" class="btn-accion-reactivar btnEditarInactivo" data-id="${escapar(id)}">` +
            '<img src="assets/Img/Iconos/pencil-square.svg" class="icono-svg icono-amarillo" alt="Editar">' +
            '<span>Editar / Reactivar</span></button>';
    }

    function badgeEstado(valor) {
        return activo({ status_producto: valor })
            ? '<span class="badge bg-success">Activo</span>'
            : '<span class="badge bg-danger">Inactivo</span>';
    }

    function badgeTallas(tallas) {
        const lista = Array.isArray(tallas) ? tallas : [];
        if (!lista.length) return '<span class="text-muted small">Sin tallas</span>';

        return '<div class="d-flex flex-wrap gap-1">' + $.map(lista, (talla) =>
            '<span class="badge bg-light text-primary border px-2 py-1" style="text-transform: uppercase;">' +
            escapar(talla) + '</span>').join('') + '</div>';
    }

    function acciones(producto) {
        const botones = activo(producto)
            ? boton('btn-accion-edit btnEditarActivo', 'pencil-square.svg', 'Editar Producto', producto.id_producto) +
              boton('btn-accion-delete btnCambiarEstado', 'trash.svg', 'Inactivar Producto', producto.id_producto)
            : reactivar(producto.id_producto);

        return '<div class="text-center d-flex justify-content-center gap-1">' + botones + '</div>';
    }

    async function peticion(url, datos, titulo) {
        try {
            const respuesta = await $.ajax({ url, type: 'POST', dataType: 'json', data: datos });

            if (respuesta.status !== 'success') {
                await errorAlert('Error', respuesta.message || 'No se pudo completar la operación.');
                return null;
            }

            await successAlert(titulo, respuesta.message);
            return respuesta;
        } catch (error) {
            await errorAlert('Error', 'No se pudo conectar con el servidor.');
            return null;
        }
    }

    function validarFormularioProducto($form) {
        let valido = true;

        $form.find('input, select').each(function () {
            if (!validarCampoProducto($(this))) valido = false;
        });

        return valido;
    }

    function validarCampoProducto($campo) {
        const campo = String($campo.attr('id') || '').replace(/^(?:reg_|edit_activo_)/, '');
        return REGLAS[campo] ? validarCampo($campo, REGLAS[campo]) : true;
    }

    function actualizarCamposPorTipo(prefijo, tipo, tallasSeleccionadas = []) {
        const campos = CAMPOS_TIPO[prefijo];
        const config = CONFIG_TALLAS[String(tipo || '').toLowerCase().includes('accesorio') ? 'Accesorio' : 'Ropa'];

        $('#' + campos.label).text(config.lblPrenda);
        $('#' + campos.prenda).attr('placeholder', config.placeholderPrenda);
        $('#' + campos.material).attr('placeholder', config.placeholderMaterial);
        $('#' + campos.tallaLabel).text(config.lblTalla);

        const $contenedor = $('#' + campos.contenedor);
        if (!$contenedor.length) return;

        $contenedor.empty();

        $.each(config.tallas, function (indice, talla) {
            const id = `${prefijo}_talla_${indice}_${talla}`;
            const marcada = $.grep(tallasSeleccionadas, (seleccionada) =>
                String(seleccionada).toLowerCase() === talla.toLowerCase()).length > 0;

            $contenedor.append(
                $('<input>', { type: 'checkbox', class: 'btn-check', name: 'tallas[]', id, value: talla, autocomplete: 'off' })
                    .prop('checked', marcada),
                $('<label>', {
                    class: 'btn btn-outline-success btn-sm rounded-pill px-3 py-1 fw-semibold',
                    for: id,
                    text: talla.toUpperCase()
                })
            );
        });
    }

    function obtenerTallasSeleccionadas(prefijo) {
        const campos = CAMPOS_TIPO[prefijo];
        return $('#' + campos.contenedor + ' input[name="tallas[]"]:checked').map(function () {
            return $(this).val();
        }).get();
    }

    function limpiarFormularioCrear() {
        const $form = $('#formRegistrarProducto');
        $form[0].reset();
        limpiarValidaciones($form);
        $('#reg_tipo_de_producto').val('Ropa');
        actualizarCamposPorTipo('reg', 'Ropa');
    }

    async function guardarProducto(config) {
        const $form = $(config.form);
        limpiarValidaciones($form);

        if (!validarFormularioProducto($form)) {
            return errorAlert('Datos inválidos', 'Revise los campos marcados en rojo.');
        }

        if (obtenerTallasSeleccionadas(config.prefijo).length === 0) {
            return errorAlert('Talla requerida', 'Debe seleccionar al menos una talla o medida para el producto.');
        }

        const respuesta = await peticion(URL + '&action=guardar', $form.serialize(), config.titulo);
        if (!respuesta) return;

        $('#' + config.modal).modal('hide');
        recargar(config.reiniciar);
    }

    async function cambiarEstado(id, estado, modal, titulo) {
        const respuesta = await peticion(URL + '&action=cambiarEstado', { id_producto: id, status_producto: estado }, titulo);
        if (!respuesta) return;

        if (modal) $('#' + modal).modal('hide');
        recargar();
    }

    // Validación en vivo
    $('#modalRegistrarProducto, #modalEditarActivo').on('input change', 'input, select', function () {
        const $campo = $(this);
        if (!$campo.hasClass('is-invalid') && !$campo.hasClass('is-valid')) return;

        validarCampoProducto($campo);
    });

    // Activos / inhabilitados
    $('#btnAlternarEstado').on('click', function () {
        verInactivos = !verInactivos;
        $(this).toggleClass('btn-outline-secondary', !verInactivos).toggleClass('btn-secondary', verInactivos);
        $('#txtBotonEstado').text(verInactivos ? 'Ver Activos' : 'Ver inhabilitados');
        $('#iconoEstado').attr('src', verInactivos ? 'assets/Img/Iconos/eye.svg' : 'assets/Img/Iconos/eye-slash.svg');
        $('#tituloVista').text(verInactivos ? 'Productos Inhabilitados' : 'Catálogo de Productos');
        recargar(true);
    });

    // Cambios de tipo y selección de tallas
    $('#reg_tipo_de_producto').on('change', function () {
        actualizarCamposPorTipo('reg', this.value);
    });

    $('#edit_activo_tipo_de_producto').on('change', function () {
        actualizarCamposPorTipo('edit', this.value, obtenerTallasSeleccionadas('edit'));
    });

    $('#modalRegistrarProducto, #modalEditarActivo').on('click', '.btnSeleccionarTallas, .btnLimpiarTallas', function () {
        const $boton = $(this);
        const marcar = $boton.hasClass('btnSeleccionarTallas');
        $('#' + $boton.data('prefijo') + '_contenedor_tallas input[name="tallas[]"]').prop('checked', marcar);
    });

    // Registrar
    $('#modalRegistrarProducto').on('show.bs.modal hidden.bs.modal', limpiarFormularioCrear);

    $('#formRegistrarProducto').on('submit', function (e) {
        e.preventDefault();
        guardarProducto(FORMS.registro);
    });

    // Editar activo
    $('#tablaProductos tbody').on('click', '.btnEditarActivo', function () {
        const producto = deFila($(this));
        if (!producto) return;

        $('#edit_activo_id_producto').val(producto.id_producto);
        $('#edit_activo_nombre_producto').val(producto.nombre_producto);
        $('#edit_activo_tipo_de_producto').val(producto.tipo_de_producto);
        $('#edit_activo_tipo_de_prenda').val(producto.tipo_de_prenda);
        $('#edit_activo_detalle_material').val(producto.detalle_material);
        $('#edit_activo_color').val(producto.color);
        $('#edit_activo_status_producto').val('activo');
        limpiarValidaciones('#formEditarActivo');

        actualizarCamposPorTipo('edit', producto.tipo_de_producto, producto.tallas_array || []);
        $('#modalEditarActivo').modal('show');
    });

    $('#formEditarActivo').on('submit', function (e) {
        e.preventDefault();
        guardarProducto(FORMS.edicion);
    });

    // Inactivar
    $('#tablaProductos tbody').on('click', '.btnCambiarEstado', async function () {
        const producto = deFila($(this));
        if (!producto) return;

        const confirmacion = await confirmActionAlert(
            '¿Inactivar producto?',
            'El producto "' + producto.nombre_producto + '" y sus variantes pasarán a la sección de inhabilitados.'
        );

        if (confirmacion.isConfirmed) await cambiarEstado(producto.id_producto, 'inactivo', null, '¡Inactivado!');
    });

    // Editar / reactivar inactivo
    $('#tablaProductos tbody').on('click', '.btnEditarInactivo', function () {
        const producto = deFila($(this));
        if (!producto) return;

        $('#edit_inactivo_id_producto').val(producto.id_producto);
        $('#edit_inactivo_nombre').val(producto.nombre_producto);
        $('#edit_inactivo_status').val('inactivo');
        $('#modalEditarInactivo').modal('show');
    });

    $('#btnGuardarEdicionInactivo').on('click', async function () {
        const id = $('#edit_inactivo_id_producto').val();
        const estado = $('#edit_inactivo_status').val();

        if (estado === 'inactivo') {
            $('#modalEditarInactivo').modal('hide');
            return;
        }

        const confirmacion = await confirmActionAlert('¿Reactivar producto?', 'El producto volverá a estar activo en el catálogo.');
        if (confirmacion.isConfirmed) await cambiarEstado(id, estado, 'modalEditarInactivo', '¡Reactivado!');
    });

    // Tabla
    tabla = $('#tablaProductos').DataTable({
        ajax: {
            url: URL + '&action=listarProductosAjax',
            dataSrc: (json) => $.grep(json && json.productos ? json.productos : [], (producto) => verInactivos ? !activo(producto) : activo(producto))
        },
        columns: [
            { data: 'id_producto', render: (id, tipo) => tipo === 'display' ? `<code>#${escapar(id)}</code>` : id },
            { data: 'nombre_producto', className: 'fw-bold text-dark', render: escapar },
            { data: 'tipo_de_producto', render: escapar },
            { data: 'tipo_de_prenda', render: (valor) => escapar(valor || 'N/A') },
            { data: 'detalle_material', render: (valor) => escapar(valor || 'N/A') },
            { data: 'color', render: (valor) => escapar(valor || 'N/A') },
            { data: 'tallas_array', render: badgeTallas },
            { data: 'status_producto', render: badgeEstado },
            { data: null, orderable: false, render: acciones }
        ],
        columnDefs: [
            { targets: 0, type: 'num' },
            { targets: [6, 8], orderable: false }
        ],
        language: lenguajeDataTableEs,
        pageLength: 10,
        order: [[0, 'desc']]
    });
});
