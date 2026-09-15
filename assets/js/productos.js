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
    fetch(window.location.href)
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const nuevoTbody = doc.querySelector('#tablaProductos tbody');
            const tbodyActual = document.querySelector('#tablaProductos tbody');

            if (nuevoTbody && tbodyActual) {
                tablaProductos.destroy();
                tbodyActual.innerHTML = nuevoTbody.innerHTML;
                initTablaProductos();
                aplicarFiltroEstado();
            }
        })
        .catch(() => location.reload());
}

document.addEventListener("DOMContentLoaded", function () {
    initTablaProductos();

    const btnAlternarEstado = document.getElementById("btnAlternarEstado");
    if (btnAlternarEstado) {
        btnAlternarEstado.addEventListener("click", function () {
            const vistaActual = this.getAttribute("data-vista");
            const txtBoton = document.getElementById("txtBotonEstado");
            const icono = document.getElementById("iconoEstado");
            const titulo = document.getElementById("tituloVista");

            if (vistaActual === "activos") {
                this.setAttribute("data-vista", "inhabilitados");
                txtBoton.innerText = "Ver Activos";
                if (icono) icono.src = "assets/Img/Iconos/eye.svg";
                if (titulo) titulo.innerText = "Productos Inhabilitados";
                filtrarPorEstado("inactivo");
            } else {
                this.setAttribute("data-vista", "activos");
                txtBoton.innerText = "Ver inhabilitados";
                if (icono) icono.src = "assets/Img/Iconos/eye-slash.svg";
                if (titulo) titulo.innerText = "Catálogo de Productos";
                filtrarPorEstado("activo");
            }
        });

        filtrarPorEstado("activo");
    }

    const regTipo = document.getElementById('reg_tipo_de_producto');
    if (regTipo) {
        regTipo.addEventListener('change', function () {
            actualizarCamposPorTipo('reg', this.value);
        });
    }

    const editTipo = document.getElementById('edit_activo_tipo_de_producto');
    if (editTipo) {
        editTipo.addEventListener('change', function () {
            const seleccionadas = obtenerTallasSeleccionadas('edit');
            actualizarCamposPorTipo('edit', this.value, seleccionadas);
        });
    }

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

    const btnGuardarInactivo = document.getElementById('btnGuardarEdicionInactivo');
    if (btnGuardarInactivo) {
        btnGuardarInactivo.addEventListener('click', function () {
            const id = $('#edit_inactivo_id_producto').val();
            const nuevoEstado = $('#edit_inactivo_status').val();

            if (nuevoEstado === 'inactivo') {
                $('#modalEditarInactivo').modal('hide');
                return;
            }

            const formData = new FormData();
            formData.append('id_producto', id);
            formData.append('status_producto', nuevoEstado);

            fetch('index.php?controller=producto&action=cambiarEstado', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
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
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' }));
        });
    }

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
                const formData = new FormData();
                formData.append('id_producto', id);
                formData.append('status_producto', 'inactivo');

                fetch('index.php?controller=producto&action=cambiarEstado', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
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
                })
                .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' }));
            }
        });
    });

    const formRegistrar = document.getElementById('formRegistrarProducto');
    if (formRegistrar) {
        formRegistrar.addEventListener('submit', function (e) {
            e.preventDefault();

            const tallas = obtenerTallasSeleccionadas('reg');
            if (tallas.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Talla requerida',
                    text: 'Debe seleccionar al menos una talla o medida para el producto.'
                });
                return;
            }

            fetch('index.php?controller=producto&action=guardar', {
                method: 'POST',
                body: new FormData(formRegistrar)
            })
            .then(res => res.json())
            .then(data => {
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
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' }));
        });
    }

    const formEditar = document.getElementById('formEditarActivo');
    if (formEditar) {
        formEditar.addEventListener('submit', function (e) {
            e.preventDefault();

            const tallas = obtenerTallasSeleccionadas('edit');
            if (tallas.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Talla requerida',
                    text: 'Debe seleccionar al menos una talla o medida para el producto.'
                });
                return;
            }

            fetch('index.php?controller=producto&action=guardar', {
                method: 'POST',
                body: new FormData(formEditar)
            })
            .then(res => res.json())
            .then(data => {
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
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error de comunicación con el servidor.' }));
        });
    }

    limpiarFormularioCrear();
});

function actualizarCamposPorTipo(prefix, tipo, tallasSeleccionadas = []) {
    const tipoKey = (tipo.toLowerCase().includes('accesorio')) ? 'Accesorio' : 'Ropa';
    const config = CONFIG_TALLAS[tipoKey];

    const lblPrenda = document.getElementById(`${prefix}_lbl_subtipo`);
    const inputPrenda = document.getElementById(`${prefix}_tipo_de_prenda`);
    const inputMaterial = document.getElementById(`${prefix}_detalle_material`);
    const lblTalla = document.getElementById(`${prefix}_lbl_talla`);
    const contenedorTallas = document.getElementById(`${prefix}_contenedor_tallas`);

    if (lblPrenda) lblPrenda.innerText = config.lblPrenda;
    if (inputPrenda) inputPrenda.placeholder = config.placeholderPrenda;
    if (inputMaterial) inputMaterial.placeholder = config.placeholderMaterial;
    if (lblTalla) lblTalla.innerText = config.lblTalla;

    if (contenedorTallas) {
        contenedorTallas.innerHTML = '';
        config.tallas.forEach((talla, index) => {
            const idCheck = `${prefix}_talla_${index}_${talla}`;
            const estaMarcada = tallasSeleccionadas.some(t => t.toLowerCase() === talla.toLowerCase());

            const div = document.createElement('div');
            div.innerHTML = `
                <input type="checkbox" class="btn-check" name="tallas[]" id="${idCheck}" value="${talla}" ${estaMarcada ? 'checked' : ''} autocomplete="off">
                <label class="btn btn-outline-success btn-sm rounded-pill px-3 py-1 fw-semibold" for="${idCheck}">
                    ${talla.toUpperCase()}
                </label>
            `;
            contenedorTallas.appendChild(div);
        });
    }
}

function obtenerTallasSeleccionadas(prefix) {
    const contenedor = document.getElementById(`${prefix}_contenedor_tallas`);
    if (!contenedor) return [];
    const checks = contenedor.querySelectorAll('input[name="tallas[]"]:checked');
    return Array.from(checks).map(c => c.value);
}

function seleccionarTodasTallas(prefix) {
    const contenedor = document.getElementById(`${prefix}_contenedor_tallas`);
    if (contenedor) {
        contenedor.querySelectorAll('input[name="tallas[]"]').forEach(c => c.checked = true);
    }
}

function deseleccionarTodasTallas(prefix) {
    const contenedor = document.getElementById(`${prefix}_contenedor_tallas`);
    if (contenedor) {
        contenedor.querySelectorAll('input[name="tallas[]"]').forEach(c => c.checked = false);
    }
}

function limpiarFormularioCrear() {
    const form = document.getElementById('formRegistrarProducto');
    if (form) form.reset();

    const regTipo = document.getElementById('reg_tipo_de_producto');
    if (regTipo) {
        regTipo.value = "Ropa";
        actualizarCamposPorTipo('reg', 'Ropa');
    }
}
