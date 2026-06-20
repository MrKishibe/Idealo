<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Panel de Control</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/estilo.css">
</head>

<body>
<?php include 'src/view/sidebar.php'; ?>

    <main class="main-content">
        <div class="view-container" style="padding: 2rem max(2vw, 20px);">

            <header class="page-header mb-4 pb-3" style="border-bottom: 1px solid #f1f5f9;">
                <div>
                    <h1 class="fw-bold text-dark mb-1" style="font-size: 1.75rem; letter-spacing: -0.5px;">Panel de Control</h1>
                    <p class="text-muted mb-0" style="font-size: 0.95rem;">
                        Bienvenido, <?php echo htmlspecialchars($nombreUsuario); ?> (<?php echo htmlspecialchars($rolUsuario); ?>). Este es el núcleo operativo de tu taller de sublimación.
                    </p>
                </div>
            </header>

            <section class="row g-4 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="p-4 bg-white shadow-sm h-100 d-flex align-items-center justify-content-between" style="border-radius: 16px; border: 1px solid #e2e8f0;">
                        <div>
                            <span class="text-secondary fw-semibold mb-1 d-block" style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Personal Activo</span>
                            <span class="text-dark fw-bold" style="font-size: 28px;"><?php echo $total_empleados; ?></span>
                        </div>
                        <div class="d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background-color: #f1f5f9; border-radius: 12px; color: #475569;">
                            <i class="bi bi-people-fill" style="font-size: 20px;"></i>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="p-4 bg-white shadow-sm h-100 d-flex align-items-center justify-content-between" style="border-radius: 16px; border: 1px solid #e2e8f0;">
                        <div>
                            <span class="text-secondary fw-semibold mb-1 d-block" style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Pedidos Pendientes</span>
                            <span class="text-dark fw-bold" style="font-size: 28px;"><?php echo $pedidos_counts['pendiente']; ?></span>
                        </div>
                        <div class="d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background-color: #fff8e1; border-radius: 12px; color: #ffb300;">
                            <i class="bi bi-clock-history" style="font-size: 20px;"></i>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="p-4 bg-white shadow-sm h-100 d-flex align-items-center justify-content-between" style="border-radius: 16px; border: 1px solid #e2e8f0;">
                        <div>
                            <span class="text-secondary fw-semibold mb-1 d-block" style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">En Producción</span>
                            <span class="text-dark fw-bold" style="font-size: 28px;"><?php echo $pedidos_counts['en proceso']; ?></span>
                        </div>
                        <div class="d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background-color: #e0f2fe; border-radius: 12px; color: #0284c7;">
                            <i class="bi bi-cpu" style="font-size: 20px;"></i>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="p-4 bg-white shadow-sm h-100 d-flex align-items-center justify-content-between" style="border-radius: 16px; border: 1px solid #e2e8f0;">
                        <div>
                            <span class="text-secondary fw-semibold mb-1 d-block" style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Crítico de Stock</span>
                            <span class="text-dark fw-bold" style="font-size: 28px;"><?php echo count($materia_bajo_stock); ?></span>
                        </div>
                        <div class="d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background-color: #fee2e2; border-radius: 12px; color: #dc2626;">
                            <i class="bi bi-exclamation-triangle-fill" style="font-size: 20px;"></i>
                        </div>
                    </div>
                </div>
            </section>

            <div class="row g-4">
                <div class="col-12 col-lg-7">
                    <div class="bg-white shadow-sm p-4 h-100" style="border-radius: 16px; border: 1px solid #e2e8f0;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-dark mb-0" style="font-size: 16px;">Últimos Pedidos Recibidos</h5>
                            <a href="index.php?controller=pedido&action=listar" class="btn btn-sm btn-light border fw-semibold" style="font-size: 12px; border-radius: 8px;">Ver todo</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0" style="font-size: 14px;">
                                <thead class="table-light" style="font-size: 12px; text-transform: uppercase; font-weight: 700; color: #64748b;">
                                    <tr>
                                        <th class="border-0">ID</th>
                                        <th class="border-0">Cliente</th>
                                        <th class="border-0">Fecha</th>
                                        <th class="border-0">Monto</th>
                                        <th class="border-0 text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($pedidos_recientes)): ?>
                                        <?php foreach ($pedidos_recientes as $ped): ?>
                                            <tr>
                                                <td class="fw-bold text-secondary">#<?php echo $ped['id_pedido']; ?></td>
                                                <td class="text-dark fw-semibold"><?php echo htmlspecialchars($ped['nombre_razon_social']); ?></td>
                                                <td class="text-muted"><?php echo date('d/m/Y', strtotime($ped['fecha_creacion'])); ?></td>
                                                <td class="fw-bold text-dark">$<?php echo number_format($ped['monto_total'], 2); ?></td>
                                                <td class="text-center">
                                                    <?php
                                                    $badge_class = 'bg-secondary';
                                                    $est = mb_strtolower($ped['estado_pedido'], 'UTF-8');
                                                    if ($est === 'pendiente') $badge_class = 'bg-warning text-dark';
                                                    elseif ($est === 'en proceso') $badge_class = 'bg-info text-white';
                                                    elseif ($est === 'completado') $badge_class = 'bg-success text-white';
                                                    elseif ($est === 'cancelado') $badge_class = 'bg-danger text-white';
                                                    ?>
                                                    <span class="badge <?php echo $badge_class; ?> px-2 py-1" style="border-radius: 6px; font-size: 11px; text-transform: capitalize;">
                                                        <?php echo htmlspecialchars($ped['estado_pedido']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">No se registran pedidos en el sistema.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-5">
                    <div class="bg-white shadow-sm p-4 h-100" style="border-radius: 16px; border: 1px solid #e2e8f0;">
                        <h5 class="fw-bold text-dark mb-3" style="font-size: 16px;">Alertas de Inventario (Bajo Mínimo)</h5>
                        <div style="max-height: 280px; overflow-y: auto;">
                            <?php if (!empty($materia_bajo_stock)): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($materia_bajo_stock as $mat): ?>
                                        <div class="list-group-item px-0 py-2.5 d-flex justify-content-between align-items-center border-0 border-bottom">
                                            <div>
                                                <h6 class="fw-semibold text-dark mb-0" style="font-size: 14px;"><?php echo htmlspecialchars($mat['nombre_materia_prima']); ?></h6>
                                                <small class="text-danger fw-medium" style="font-size: 12px;">Stock actual inferior al mínimo de seguridad</small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size: 12px; font-weight: 700; border-radius: 6px;">
                                                    <?php echo $mat['stock_actual']; ?> en almacén
                                                </span>
                                                <small class="text-muted d-block mt-1" style="font-size: 11px;">Mínimo: <?php echo $mat['stock_minimo']; ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-check-circle-fill text-success mb-2 d-block" style="font-size: 28px;"></i>
                                    El inventario se encuentra por encima de los límites mínimos.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
</body>

</html>