import $ from 'jquery';
import { lenguajeDataTableEs } from './helpers/datatable-espanol.js';
import { successAlert, errorAlert, confirmActionAlert } from './helpers/alerts.js';
import { obtenerPatron, validarCampo, validarLongitud } from './helpers/expresiones.js';
import { limpiarValidaciones, soloNumerosTeclado, soloLetrasTeclado } from './helpers/validaciones.js';

$(function () {
    const URL_MODULO = 'index.php?controller=empleado';
    const CAMPOS = ['cedula', 'nombres', 'apellidos', 'telefono', 'direccion', 'cargo', 'salario'];

    let tabla = null;
    let verInactivos = false;

    // Reglas de validación por sufijo del id (reg_cedula, edit_activo_cedula, ...)
    const REGLAS = {
        cedula: {
            patron: obtenerPatron('cedula'),
            mensaje: 'Cédula inválida. Ejemplo: V-12345678.',
            mensajeVacio: 'La cédula es obligatoria.'
        },
        nombres: {
            patron: obtenerPatron('nombres'),
            mensaje: 'Los nombres solo deben contener letras y espacios.',
            mensajeVacio: 'Los nombres son obligatorios.'
        },
        apellidos: {
            patron: obtenerPatron('apellido'),
            mensaje: 'Los apellidos solo deben contener letras y espacios.',
            mensajeVacio: 'Los apellidos son obligatorios.'
        },
        telefono: {
            requerido: false,
            patron: obtenerPatron('telefonoVenezuela'),
            mensaje: 'Teléfono inválido. Ejemplo: 04125555555.'
        },
        direccion: {
            requerido: false,
            maximo: 500,
            mensaje: 'La dirección no puede superar los 500 caracteres.'
        },
        cargo: {
            mensajeVacio: 'Seleccione un cargo.'
        },
        salario: {
            patron: obtenerPatron('moneda'),
            mensaje: 'El salario debe ser un número con hasta dos decimales.',
            mensajeVacio: 'El salario es obligatorio.'
        }
    };

    const FORMS = {
        registro: {
            form: '#formEmpleado',
            prefijo: 'reg_',
            incluirCedula: true,
            modal: 'modalRegistrarEmpleado',
            mensaje: '¡Registrado!',
            reiniciar: true
        },
        edicion: {
            form: '#formEditarActivo',
            prefijo: 'edit_activo_',
            incluirCedula: false,
            modal: 'modalEditarActivo',
            mensaje: '¡Actualizado!',
            reiniciar: false
        }
    };

    function reglaDe(id) {
        const sufijo = String(id || '').match(/_([a-z]+)$/);
        return (sufijo && REGLAS[sufijo[1]]) || null;
    }

    function validarRegla($campo) {
        const regla = reglaDe($campo.attr('id'));
        if (!regla) return true;

        return regla.maximo
            ? validarLongitud($campo, 0, regla.maximo, regla)
            : validarCampo($campo, regla);
    }

    function validarFormulario($form, incluirCedula) {
        let valido = true;

        $form.find('input, select, textarea')
            .filter(':enabled')
            .not('[type="hidden"], [readonly]')
            .each(function () {
                const esCedula = String(this.id).endsWith('_cedula');
                if ((incluirCedula || !esCedula) && !validarRegla($(this))) valido = false;
            });

        return valido;
    }

    // Utilidades
    const escaparHTML = (texto) => $('<div>').text(texto ?? '').html();
    const nombreCompleto = (empleado) => [empleado.nombres, empleado.apellidos].filter(Boolean).join(' ').trim();
    const estaActivo = (empleado) => String(empleado.status_empleado || '').trim().toLowerCase() === 'activo';
    const recargar = (reiniciar) => tabla.ajax.reload(null, Boolean(reiniciar));

    function empleadoDeFila($boton) {
        const fila = tabla.row($boton.closest('tr'));
        return fila && fila.data() ? fila.data() : null;
    }

    function botonAccion(clase, icono, color, alt, id) {
        return '<button type="button" class="btn btn-sm ' + clase + '" data-id="' + escaparHTML(id) + '">' +
            '<img src="assets/Img/Iconos/' + icono + '" class="icono-svg ' + color + '" alt="' + alt + '"></button>';
    }

    // Petición POST con alertas
    async function peticion(datos, titulo) {
        try {
            const respuesta = await $.ajax({
                url: URL_MODULO,
                type: 'POST',
                dataType: 'json',
                data: datos
            });

            if (!respuesta.success) {
                await errorAlert('Error', respuesta.message);
                return null;
            }

            await successAlert(titulo, respuesta.message);
            return respuesta;
        } catch (error) {
            await errorAlert('Error', 'No se pudo conectar con el servidor.');
            return null;
        }
    }

    // Usuarios vinculables
    function cargarUsuarios($select, idEmpleado, esEdicion, idSeleccionado) {
        $select.html('<option value="">Cargando...</option>');

        $.ajax({
            url: URL_MODULO + '&ajax=usuarios',
            type: 'GET',
            dataType: 'json'
        })
            .done(function (respuesta) {
                if (!respuesta || respuesta.success === false) {
                    $select.html('<option value="">No se pudieron cargar los usuarios</option>');
                    return;
                }

                const opciones = $.map(respuesta.usuarios || [], function (usuario) {
                    const vinculado = usuario.id_empleado_vinculado ? String(usuario.id_empleado_vinculado) : null;
                    const activo = String(usuario.status_usuario || '').trim().toLowerCase() === 'activo';
                    const disponible = esEdicion
                        ? (!vinculado || vinculado === String(idEmpleado))
                        : (!vinculado && activo);

                    if (!disponible) return null;

                    return $('<option>', {
                        value: usuario.id_usuario,
                        text: usuario.nombre_usuario + ' — ' + (usuario.correo || 'sin correo') +
                            (activo ? '' : ' (inactivo)')
                    })[0];
                });

                $select.empty()
                    .append($('<option>', { value: '', text: 'Seleccione el usuario...' }))
                    .append(opciones);

                if (idSeleccionado) $select.val(String(idSeleccionado));
            })
            .fail(function () {
                $select.html('<option value="">No se pudieron cargar los usuarios</option>');
            });
    }

    // Guardar (registro y edición activa)
    async function guardarEmpleado(config) {
        const $form = $(config.form);
        const $usuario = $('#' + config.prefijo + 'id_usuario');
        const usaUsuario = $('#' + config.prefijo + 'es_usuario').prop('checked');

        limpiarValidaciones($form);

        if (!validarFormulario($form, config.incluirCedula)) {
            return errorAlert('Datos inválidos', 'Revise los campos marcados en rojo.');
        }

        if (usaUsuario && !$usuario.val()) {
            validarCampo($usuario, { mensajeVacio: 'Seleccione el usuario asociado.' });
            return errorAlert('Usuario requerido', 'Seleccione el usuario asociado al empleado.');
        }

        const datos = { es_usuario: usaUsuario ? 1 : '', id_usuario: usaUsuario ? $usuario.val() : '' };

        $.each(CAMPOS, function (_, campo) {
            datos[campo] = $('#' + config.prefijo + campo).val().trim();
        });

        const idEmpleado = $('#' + config.prefijo + 'id_empleado').val();

        if (idEmpleado) {
            datos.id_accion = idEmpleado;
            datos.nuevo_estado = 'Activo';
        }

        const respuesta = await peticion(datos, config.mensaje);
        if (!respuesta) return;

        $('#' + config.modal).modal('hide');
        recargar(config.reiniciar);
    }

    async function cambiarEstado(idEmpleado, nuevoEstado, modal) {
        const respuesta = await peticion({ id_accion: idEmpleado, nuevo_estado: nuevoEstado }, '¡Actualizado!');
        if (!respuesta) return;

        if (modal) $('#' + modal).modal('hide');
        recargar();
    }

    async function guardarEstadoInactivo() {
        const idEmpleado = $('#edit_inactivo_id_empleado').val();
        const nuevoEstado = $('#edit_inactivo_status').val();

        if (nuevoEstado === 'Activo') {
            const confirmacion = await confirmActionAlert(
                '¿Reactivar empleado?',
                'El empleado volverá a estar Activo en el sistema.'
            );

            if (!confirmacion.isConfirmed) return;
        }

        await cambiarEstado(idEmpleado, nuevoEstado, 'modalEditarInactivo');
    }

    // Validación en vivo y teclado
    $('#modalRegistrarEmpleado, #modalEditarActivo').on('input change', 'input, select, textarea', function () {
        const $campo = $(this);

        if ($campo.hasClass('is-valid') || $campo.hasClass('is-invalid')) {
            validarRegla($campo);
        }
    });

    $('#reg_nombres, #reg_apellidos, #edit_activo_nombres, #edit_activo_apellidos')
        .on('keydown', soloLetrasTeclado);

    $('#reg_telefono, #edit_activo_telefono')
        .on('keydown', soloNumerosTeclado);

    // Activos / inhabilitados
    $('#btnAlternarEstado').on('click', function () {
        verInactivos = !verInactivos;

        $(this)
            .attr('data-vista', verInactivos ? 'inactivos' : 'activos')
            .toggleClass('btn-outline-secondary', !verInactivos)
            .toggleClass('btn-secondary', verInactivos);

        $('#txtBotonEstado').text(verInactivos ? 'Ver Activos' : 'Ver inhabilitados');
        $('#iconoEstado').attr('src', verInactivos ? 'assets/Img/Iconos/eye.svg' : 'assets/Img/Iconos/eye-slash.svg');
        $('#tituloVista').text(verInactivos ? 'Empleados Inhabilitados' : 'Gestión de Empleados');

        recargar(true);
    });

    // Checkbox de usuario asociado
    $('#reg_es_usuario, #edit_es_usuario').on('change', function () {
        const prefijo = this.id.startsWith('reg_') ? 'reg_' : 'edit_';
        const tieneUsuario = $(this).prop('checked');

        $('#' + prefijo + 'usuario_wrap').toggleClass('d-none', !tieneUsuario);

        if (!tieneUsuario) {
            $('#' + prefijo + 'id_usuario').val('').removeClass('is-valid is-invalid');
        }
    });

    // Registrar
    $('#modalRegistrarEmpleado').on('show.bs.modal', function () {
        $('#reg_es_usuario').prop('checked', false).trigger('change');
        limpiarValidaciones('#formEmpleado');
        cargarUsuarios($('#reg_id_usuario'), null, false, null);
    });

    // Editar activo
    $('#tablaEmpleados tbody').on('click', '.btnEditarActivo', function () {
        const empleado = empleadoDeFila($(this));
        if (!empleado) return;

        $.each({
            edit_activo_id_empleado: empleado.id_empleado,
            edit_activo_cedula: empleado.cedula,
            edit_activo_nombres: empleado.nombres,
            edit_activo_apellidos: empleado.apellidos,
            edit_activo_telefono: empleado.telefono,
            edit_activo_direccion: empleado.direccion,
            edit_activo_cargo: empleado.cargo,
            edit_activo_salario: empleado.salario
        }, function (id, valor) {
            $('#' + id).val(valor);
        });

        const usuarioVinculado = empleado.id_usuario_vinculado ? String(empleado.id_usuario_vinculado) : null;

        $('#edit_es_usuario').prop('checked', Boolean(usuarioVinculado)).trigger('change');
        limpiarValidaciones('#formEditarActivo');
        cargarUsuarios($('#edit_id_usuario'), empleado.id_empleado, true, usuarioVinculado);
        $('#modalEditarActivo').modal('show');
    });

    // Inhabilitar
    $('#tablaEmpleados tbody').on('click', '.btnCambiarEstado', async function () {
        const empleado = empleadoDeFila($(this));
        if (!empleado) return;

        const confirmacion = await confirmActionAlert(
            '¿Inhabilitar empleado?',
            'El empleado ' + nombreCompleto(empleado) + ' será marcado como Inactivo.'
        );

        if (confirmacion.isConfirmed) {
            await cambiarEstado(empleado.id_empleado, 'Inactivo');
        }
    });

    // Editar / reactivar inactivo
    $('#tablaEmpleados tbody').on('click', '.btnEditarInactivo', function () {
        const empleado = empleadoDeFila($(this));
        if (!empleado) return;

        $('#edit_inactivo_id_empleado').val(empleado.id_empleado);
        $('#edit_inactivo_nombre_completo').val(nombreCompleto(empleado));
        $('#edit_inactivo_status').val('Inactivo');

        limpiarValidaciones('#formEditarInactivo');
        $('#modalEditarInactivo').modal('show');
    });

    // Botones y submit de formularios
    $('#btnEnvio').on('click', function () {
        guardarEmpleado(FORMS.registro);
    });

    $('#btnGuardarEdicionActivo').on('click', function () {
        guardarEmpleado(FORMS.edicion);
    });

    $('#btnGuardarEdicionInactivo').on('click', guardarEstadoInactivo);

    $.each([
        ['#formEmpleado', '#btnEnvio'],
        ['#formEditarActivo', '#btnGuardarEdicionActivo'],
        ['#formEditarInactivo', '#btnGuardarEdicionInactivo']
    ], function (_, par) {
        $(par[0]).on('submit', function (e) {
            e.preventDefault();
            $(par[1]).trigger('click');
        });
    });

    // Limpieza de modales
    $('#modalRegistrarEmpleado').on('hidden.bs.modal', function () {
        $('#formEmpleado')[0].reset();
        limpiarValidaciones('#formEmpleado');
        $('#reg_es_usuario').prop('checked', false);
        $('#reg_usuario_wrap').addClass('d-none');
        $('#reg_id_usuario').html('<option value="">Seleccione el usuario...</option>');
    });

    $('#modalEditarActivo, #modalEditarInactivo').on('hidden.bs.modal', function () {
        limpiarValidaciones($(this).find('form'));
    });

    // Tabla
    tabla = $('#tablaEmpleados').DataTable({
        ajax: {
            url: URL_MODULO + '&ajax=listar',
            type: 'GET',
            dataSrc: function (json) {
                return $.grep(json && json.empleados ? json.empleados : [], function (empleado) {
                    return verInactivos ? !estaActivo(empleado) : estaActivo(empleado);
                });
            }
        },
        columns: [
            { data: 'cedula', className: 'fw-bold', render: escaparHTML },
            {
                data: null,
                render: function (empleado) {
                    return '<div class="fw-bold text-dark">' + escaparHTML(nombreCompleto(empleado)) + '</div>' +
                        '<small class="text-muted">' +
                        '<img src="assets/Img/Iconos/geo-alt.svg" class="icono-svg icono-gris me-1" alt="Dirección">' +
                        escaparHTML(empleado.direccion || 'Sin dirección') +
                        '</small>';
                }
            },
            {
                data: 'telefono',
                render: function (valor) {
                    return escaparHTML(valor || 'N/A');
                }
            },
            { data: 'cargo', render: escaparHTML },
            {
                data: 'salario',
                render: function (valor) {
                    const salario = parseFloat(valor);
                    return '<span class="text-success fw-bold">$' +
                        (Number.isFinite(salario) ? salario.toFixed(2) : '0.00') +
                        '</span>';
                }
            },
            {
                data: 'status_empleado',
                render: function (valor) {
                    return estaActivo({ status_empleado: valor })
                        ? '<span class="badge bg-success">Activo</span>'
                        : '<span class="badge bg-danger">Inactivo</span>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function (empleado) {
                    const id = empleado.id_empleado;

                    const botones = estaActivo(empleado)
                        ? botonAccion('btn-outline-primary btnEditarActivo', 'pencil-square.svg', 'icono-azul', 'Editar', id) +
                          botonAccion('btn-outline-danger btnCambiarEstado', 'trash.svg', 'icono-rojo', 'Inhabilitar', id)
                        : botonAccion('btn-outline-warning btnEditarInactivo', 'pencil-square.svg', 'icono-amarillo', 'Editar', id);

                    return '<div class="text-center d-flex justify-content-center gap-1">' + botones + '</div>';
                }
            }
        ],
        language: lenguajeDataTableEs,
        pageLength: 10,
        responsive: true,
        ordering: false
    });
});
