<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Gestión de Órdenes de Producción</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/css/estilo.css">
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
                    <button type="button" id="btnAlternarEstado" class="btn btn-outline-secondary px-3 py-2" style="border-radius: var(--radius-md); font-weight: 600;" data-vista="activos">
                        <i class="bi bi-eye-slash-fill me-1" id="iconoEstado"></i> <span id="txtBotonEstado">Ver inactivas</span>
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
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div class="modal fade modal-idealo" id="modalRegistrarOrden" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-box-seam me-2"></i>Registrar Orden de Producción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="index.php?url=ordenproduccion/listarordenproduccion" method="POST" id="formRegistrarOrden">
                    <input type="hidden" name="action" value="guardar">
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

    <div class="modal fade modal-idealo" id="modalEditarOrden" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Orden de Producción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="index.php?url=ordenproduccion/listarordenproduccion" method="POST" id="formEditarOrden">
                    <input type="hidden" name="action" value="editar">
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

    <script src="assets/js/jquery-3.7.0.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/jquery.dataTables.min.js"></script>
    <script src="assets/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/js/sweetalert2.all.min.js"></script>

    <script>
        function toggleMenu(id) {
            const submenu = document.getElementById(id);
            const container = submenu.parentElement;

            document.querySelectorAll('.menu-group').forEach(group => {
                if (group !== container && group.classList.contains('open')) {
                    group.classList.remove('open');
                }
            });
            container.classList.toggle('open');
        }
    </script>

    <script src="assets/js/ordenproduccion.js"></script>

</body>

</html>