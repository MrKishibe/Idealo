<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Órdenes de Producción</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght=400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dataTables.bootstrap5@1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/css/estilo.css">
    <style>
        .dataTables_wrapper .dataTables_length, 
        .dataTables_wrapper .dataTables_filter, 
        .dataTables_wrapper .dataTables_info, 
        .dataTables_wrapper .dataTables_processing, 
        .dataTables_wrapper .dataTables_paginate {
            color: #475569 !important;
        }
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 6px 12px;
            margin-left: 10px;
        }
        .dataTables_wrapper .dataTables_length select {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 6px 12px;
            margin: 0 10px;
        }
        .table thead {
            background: #f8fafc;
        }
        .dt-pagination .page-item.active .page-link {
            background-color: #10b981;
            border-color: #10b981;
        }
    </style>
</head>
<body>

<?php include 'src/view/sidebar.php'; ?>
<main class="main-content">
    <div class="view-container" style="padding: 2rem max(2vw, 20px);">
        <header class="page-header d-flex justify-content-between align-items-center mb-4 pb-3" style="border-bottom: 1px solid #f1f5f9;">
            <div>
                <h1 class="fw-bold text-dark mb-1" style="font-size: 1.75rem;">Órdenes de Producción</h1>
                <p class="text-muted mb-0">Monitoreo y asignación de flujos de manufactura activa.</p>
            </div>
            <div>
                <button type="button" class="btn btn-success px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalRegistrarOrden" style="border-radius: 12px; font-weight: 600;">
                    <i class="bi bi-plus-circle-fill me-1"></i> Nueva Orden
                </button>
            </div>
        </header>

        <div class="table-responsive shadow-sm" style="background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden;">
            <table class="table table-hover mb-0 align-middle" id="tablaOrdenes">
                <thead>
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Detalle Pedido</th>
                        <th class="px-4 py-3">Inicio</th>
                        <th class="px-4 py-3">Terminado</th>
                        <th class="px-4 py-3">Monto Total</th>
                        <th class="px-4 py-3">Estado Pedido</th>
                        <th class="px-4 py-3">Estado Línea</th>
                        <th class="px-4 py-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($ordenes)): ?>
                        <?php foreach ($ordenes as $orden): ?>
                            <tr>
                                <td class="px-4 py-3 fw-semibold">#<?php echo htmlspecialchars($orden['id_produccion']); ?></td>
                                <td class="px-4 py-3 text-muted">ID-REF: <?php echo htmlspecialchars($orden['id_detalle_pedido']); ?></td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($orden['fecha_de_inicio']); ?></td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($orden['fecha_terminado']); ?></td>
                                <td class="px-4 py-3 fw-bold text-dark"><?php echo number_format($orden['monto_total'], 2); ?> $</td>
                                <td class="px-4 py-3">
                                    <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($orden['estado_de_pedido']); ?></span>
                                </td>
                                <td class="px-4 py-3">
                                    <?php 
                                        $status = $orden['estado'];
                                        $badgeClass = 'bg-secondary';
                                        if ($status === 'Finalizado') $badgeClass = 'bg-success';
                                        if ($status === 'En Proceso') $badgeClass = 'bg-warning text-dark';
                                        if ($status === 'Planificado') $badgeClass = 'bg-info text-dark';
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#modalEditarOrden" onclick='cargarDatosEditar(<?php echo json_encode($orden); ?>)'>
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="index.php?controller=produccion&action=eliminar&id=<?php echo $orden['id_produccion']; ?>" class="btn btn-sm btn-light border text-danger" onclick="return confirm('¿Deseas suspender esta orden?')">
                                            <i class="bi bi-x-circle-fill"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div class="modal fade" id="modalRegistrarOrden" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 16px; border: none;">
            <div class="modal-header px-4 pt-4 pb-2 border-0">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-plus-circle-fill text-success me-2"></i>Crear Orden de Producción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="index.php?controller=produccion&action=guardar" method="POST">
                <div class="modal-body px-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">ID Detalle Pedido</label>
                            <input type="number" class="form-control" name="id_detalle_pedido" required placeholder="Ej: 104">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Monto Total</label>
                            <input type="number" step="0.01" class="form-control" name="monto_total" required placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fecha de Inicio</label>
                            <input type="date" class="form-control" name="fecha_de_inicio" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Estado de Pedido</label>
                            <select class="form-select" name="estado_de_pedido" required>
                                <option value="En espera" selected>En espera</option>
                                <option value="Entregado">Entregado</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Estado Producción</label>
                            <select class="form-select" name="estado" required>
                                <option value="Planificado">Planificado</option>
                                <option value="En Proceso">En Proceso</option>
                                <option value="Finalizado">Finalizado</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer px-4 pb-4 pt-3 border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Crear Orden</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarOrden" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 16px; border: none;">
            <div class="modal-header px-4 pt-4 pb-2 border-0">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-pencil-square text-primary me-2"></i>Actualizar Orden</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="index.php?controller=produccion&action=editar" method="POST">
                <input type="hidden" name="id_produccion" id="edit_id_produccion">
                <div class="modal-body px-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fecha Terminado</label>
                            <input type="date" class="form-control" name="fecha_terminado" id="edit_fecha_terminado">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Estado de Pedido</label>
                            <select class="form-select" name="estado_de_pedido" id="edit_estado_de_pedido">
                                <option value="En espera">En espera</option>
                                <option value="Entregado">Entregado</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Estado Línea</label>
                            <select class="form-select" name="estado" id="edit_estado">
                                <option value="Planificado">Planificado</option>
                                <option value="En Proceso">En Proceso</option>
                                <option value="Finalizado">Finalizado</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer px-4 pb-4 pt-3 border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>


function cargarDatosEditar(orden) {
    document.getElementById('edit_id_produccion').value = orden.id_produccion || '';
    document.getElementById('edit_fecha_terminado').value = orden.fecha_terminado !== '—' ? orden.fecha_terminado : '';
    document.getElementById('edit_estado_de_pedido').value = orden.estado_de_pedido || 'En espera';
    document.getElementById('edit_estado').value = orden.estado || 'Planificado';
}

$(document).ready(function() {
    $('#tablaOrdenes').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        pageLength: 10,
        ordering: true,
        searching: true,
        destroy: true
    });
});
</script>

</body>
</html>