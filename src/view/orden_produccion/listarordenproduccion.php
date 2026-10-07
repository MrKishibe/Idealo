<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Gestión de Órdenes de Producción</title>
    
    <link rel="stylesheet" href="assets/libs/css/bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/Img/Iconos/bootstrap-icons.min.css">    
    <link rel="stylesheet" href="assets/libs/css/iconos.css">
    <link rel="stylesheet" href="assets/libs/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/libs/css/estilo.css">
</head>

<body>

    <?php include 'src/view/sidebar.php'; ?>

    <main class="main-content">
        <div class="view-container">
            <header class="page-header">
                <div>
                    <h1 id="tituloVista">Gestión de Órdenes de Producción</h1>
                    <p>Administra las órdenes de producción y su estado en el proceso.</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" id="btnAlternarEstado" class="btn btn-outline-secondary px-2 py-1" style="border-radius: var(--radius-md); font-weight: 600;" data-vista="activos">
                        <i class="bi bi-eye-slash-fill me-1" id="iconoEstado"></i> <span id="txtBotonEstado">Ver inactivas</span>
                    </button>
                    <button type="button" id="btnGenerarReporte" class="btn btn-outline-danger px-2 py-1" style="border-radius: var(--radius-md); font-weight: 600;" data-bs-toggle="modal" data-bs-target="#modalReporteOrdenes">
                        <i class="bi bi-file-earmark-pdf-fill me-1"></i> Generar Reporte
                    </button>
                    <button type="button" class="btn-idealo-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarOrden">
                        <i class="bi bi-box-seam me-1"></i> Registrar Orden
                    </button>
                </div>
            </header>

            <div class="table-container p-3">
                <div class="table-responsive">
                    <table class="custom-table" id="tablaOrdenProduccion" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Finalización</th>
                                <th>Descripción del Pedido</th>
                                <th>Estado Producción</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyOrdenProduccion">
                            <!-- Filas dinámicas -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div class="modal fade modal-idealo" id="modalReporteOrdenes" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="formReporteOrdenes">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-file-earmark-pdf me-2"></i>Filtrar reporte de producción</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <fieldset class="mb-3">
                            <legend class="form-label">Estados de producción</legend>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="reporteSeleccionarTodos" checked>
                                <label class="form-check-label fw-semibold" for="reporteSeleccionarTodos">Seleccionar todos</label>
                            </div>
                            <div class="row">
                                <?php $estadosProduccion = $estadosProduccion ?? []; ?>
                                <?php foreach ($estadosProduccion as $indice => $estado): ?>
                                    <div class="col-sm-6">
                                        <div class="form-check">
                                            <input class="form-check-input check-estado-reporte" type="checkbox" name="estados[]" value="<?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?>" id="reporteEstado<?= $indice ?>" checked>
                                            <label class="form-check-label" for="reporteEstado<?= $indice ?>"><?= htmlspecialchars(ucfirst($estado), ENT_QUOTES, 'UTF-8') ?></label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                        <fieldset>
                            <legend class="form-label">Rango por fecha de inicio</legend>
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label" for="reporteFechaDesde">Desde</label>
                                    <input type="date" class="form-control" id="reporteFechaDesde">
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label" for="reporteFechaHasta">Hasta</label>
                                    <input type="date" class="form-control" id="reporteFechaHasta">
                                </div>
                            </div>
                            <small class="text-muted">Las fechas elegidas se incluyen en el reporte. Déjalas vacías para no limitar por fecha.</small>
                        </fieldset>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger"><i class="bi bi-file-earmark-pdf-fill me-1"></i>Generar PDF</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Registrar Orden -->
    <div class="modal fade modal-idealo" id="modalRegistrarOrden" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-box-seam me-2"></i>Registrar Orden de Producción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="index.php?url=ordenproduccion/listarordenproduccion" method="POST" id="formRegistrarOrden">
                    <input type="hidden" name="accion" value="guardar">

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Fecha de Inicio</label>
                                <input type="date" class="form-control" name="fecha_de_inicio" id="fecha_de_inicio" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fecha de Finalización</label>
                                <input type="date" class="form-control" name="fecha_terminado" id="fecha_terminado">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Descripción del Pedido</label>
                                <select class="form-select" name="id_detalle_pedido" id="id_detalle_pedido" required>
                                    <option value="">Seleccione un pedido</option>
                                    <?php if (!empty($detallesPedido)): ?>
                                        <?php foreach ($detallesPedido as $detalle): ?>
                                            <option value="<?= htmlspecialchars($detalle['id_detalle_pedido']) ?>"><?= htmlspecialchars($detalle['descripcion']) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado de Producción</label>
                                <select class="form-select" name="estado_de_produccion" id="estado_de_produccion" required>
                                    <option value="Planificado">Planificado</option>
                                    <option value="En Proceso">En Proceso</option>
                                    <option value="Finalizado">Finalizado</option>
                                    <option value="Inactiva">Inactiva</option>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Asignar Trabajadores</label>
                                <div class="border rounded p-2" style="max-height: 150px; overflow-y: auto; background-color: #f8f9fa;">
                                    <?php if (!empty($empleados)): ?>
                                        <?php foreach ($empleados as $emp): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="empleados[]" value="<?= htmlspecialchars($emp['id_empleado']) ?>" id="emp_<?= htmlspecialchars($emp['id_empleado']) ?>">
                                                <label class="form-check-label" for="emp_<?= htmlspecialchars($emp['id_empleado']) ?>">
                                                    <?= htmlspecialchars($emp['nombres'] . ' ' . $emp['apellidos']) ?> <span class="text-muted" style="font-size: 0.85em;">(<?= htmlspecialchars($emp['cargo']) ?>)</span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted mb-0 small">No hay trabajadores registrados o activos.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" style="border-radius: var(--radius-md);" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-idealo-success" id="btnGuardarOrden">Registrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Orden -->
    <div class="modal fade modal-idealo" id="modalEditarOrden" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Orden de Producción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="index.php?url=ordenproduccion/listarordenproduccion" method="POST" id="formEditarOrden">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id_produccion" id="edit_id_orden">

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Fecha de Inicio</label>
                                <input type="date" class="form-control" name="fecha_de_inicio" id="edit_fecha_de_inicio" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fecha de Finalización</label>
                                <input type="date" class="form-control" name="fecha_terminado" id="edit_fecha_terminado">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Descripción del Pedido</label>
                                <select class="form-select" name="id_detalle_pedido" id="edit_id_detalle_pedido" required>
                                    <option value="">Seleccione un pedido</option>
                                    <?php if (!empty($detallesPedido)): ?>
                                        <?php foreach ($detallesPedido as $detalle): ?>
                                            <option value="<?= htmlspecialchars($detalle['id_detalle_pedido']) ?>"><?= htmlspecialchars($detalle['descripcion']) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado de Producción</label>
                                <select class="form-select" name="estado_de_produccion" id="edit_estado_de_produccion" required>
                                    <option value="Planificado">Planificado</option>
                                    <option value="En Proceso">En Proceso</option>
                                    <option value="Finalizado">Finalizado</option>
                                    <option value="Inactiva">Inactiva</option>
                                </select>

                            <div class="col-12 mt-3">
                                <label class="form-label">Modificar Trabajadores Asignados</label>
                                <div class="border rounded p-2" style="max-height: 150px; overflow-y: auto; background-color: #f8f9fa;">
                                    <?php if (!empty($empleados)): ?>
                                        <?php foreach ($empleados as $emp): ?>
                                            <div class="form-check">
                                                <input class="form-check-input check-edit-empleado" type="checkbox" name="empleados[]" value="<?= htmlspecialchars($emp['id_empleado']) ?>" id="edit_emp_<?= htmlspecialchars($emp['id_empleado']) ?>">
                                                <label class="form-check-label" for="edit_emp_<?= htmlspecialchars($emp['id_empleado']) ?>">
                                                    <?= htmlspecialchars($emp['nombres'] . ' ' . $emp['apellidos']) ?> <span class="text-muted" style="font-size: 0.85em;">(<?= htmlspecialchars($emp['cargo']) ?>)</span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted mb-0 small">No hay trabajadores registrados o activos.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                             
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" style="border-radius: var(--radius-md);" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btnGuardarEdicionOrden">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/libs/jquery-4.0.0.min.js"></script>
    <script src="assets/js/helpers/expresiones.js"></script>
    <script src="assets/libs/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/jquery.dataTables.min.js"></script>
    <script src="assets/libs/dataTables.bootstrap5.min.js"></script>
    <script src="assets/libs/sweetalert2.all.min.js"></script>
    <script src="assets/js/ordenproduccion.js"></script>

</body>

</html>