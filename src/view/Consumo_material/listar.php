<?php
if (!isset($consumo_material)) {
    $consumo_material = [
        [
            'id_consumo_material'    => 1,
            'nombre_materia_prima'   => 'Tela de Algodón 20/1',
            'costo_unitaro'          => 4.50,
            'descripcion_de_consumo' => 'Corte de franelas talla M',
            'cantidad_usada'         => 25,
            'unidad_de_medida'       => 'Metros',
            'id_materia_prima'       => 101,
            'id_produccion'          => 12
        ],
        [
            'id_consumo_material'    => 2,
            'nombre_materia_prima'   => 'Tinta Textil Plastisol Negra',
            'costo_unitaro'          => 12.00,
            'descripcion_de_consumo' => 'Estampado de logos frontales en franelas',
            'cantidad_usada'         => 2,
            'unidad_de_medida'       => 'Litros',
            'id_materia_prima'       => 104,
            'id_produccion'          => 12
        ],

        [
            'id_consumo_material'    => 4,
            'nombre_materia_prima'   => 'Tela Canvas Impermeable',
            'costo_unitaro'          => 6.20,
            'descripcion_de_consumo' => 'Producción de bolsos deportivos',
            'cantidad_usada'         => 15,
            'unidad_de_medida'       => 'Metros',
            'id_materia_prima'       => 102,
            'id_produccion'          => 15
        ],
        [
            'id_consumo_material'    => 5,
            'nombre_materia_prima'   => 'Tinta al Agua Violeta',
            'costo_unitaro'          => 9.50,
            'descripcion_de_consumo' => 'Serigrafía suave en lote de suéteres',
            'cantidad_usada'         => 1,
            'unidad_de_medida'       => 'Litros',
            'id_materia_prima'       => 105,
            'id_produccion'          => 17
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Consumo de Material</title>
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
                    <h1 id="tituloVista">Consumo de Materiales</h1>
                    <p>Registra y administra el consumo de materia prima en las órdenes de producción.</p>
                </div>
                <div>
                    <button type="button" class="btn-idealo-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarConsumo">
                        <i class="bi bi-clipboard-plus"></i> Registrar Consumo
                    </button>
                </div>
            </header>

            <div class="table-container p-3">
                <div class="table-responsive">
                    <table class="custom-table" id="tablaConsumos" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Materia Prima / Descripción</th>
                                <th>Costo Unitario</th>
                                <th>Cantidad Usada</th>
                                <th>Costo Total</th>
                                <th>ID Producción</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (is_array($consumo_material)): ?>
                                <?php foreach ($consumo_material as $c):
                                    $costoTotal = $c['costo_unitaro'] * $c['cantidad_usada'];
                                ?>
                                    <tr id="fila-<?php echo $c['id_consumo_material']; ?>">
                                        <td class="fw-bold">#<?php echo $c['id_consumo_material']; ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($c['nombre_materia_prima']); ?></div>
                                            <small class="text-muted"><i class="bi bi-info-circle"></i> <?php echo htmlspecialchars($c['descripcion_de_consumo'] ?: 'Sin descripción'); ?></small>
                                        </td>
                                        <td class="fw-bold text-muted">$<?php echo number_format((float)$c['costo_unitaro'], 2, '.', ''); ?></td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($c['cantidad_usada']) . ' ' . htmlspecialchars($c['unidad_de_medida']); ?></td>
                                        <td class="text-success fw-bold">$<?php echo number_format((float)$costoTotal, 2, '.', ''); ?></td>
                                        <td>
                                            <span class="badge bg-secondary">OP-<?php echo str_pad($c['id_produccion'], 4, '0', STR_PAD_LEFT); ?></span>
                                        </td>
                                        <td>
                                            <div class="text-center d-flex justify-content-center gap-1">
                                                <button class="btn btn-sm btn-outline-primary btnEditarConsumo"
                                                    data-id="<?php echo $c['id_consumo_material']; ?>"
                                                    data-materia="<?php echo $c['id_materia_prima']; ?>"
                                                    data-costo="<?php echo $c['costo_unitaro']; ?>"
                                                    data-cantidad="<?php echo $c['cantidad_usada']; ?>"
                                                    data-descripcion="<?php echo htmlspecialchars($c['descripcion_de_consumo']); ?>"
                                                    data-produccion="<?php echo $c['id_produccion']; ?>">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger btnEliminarConsumo"
                                                    data-id="<?php echo $c['id_consumo_material']; ?>"
                                                    data-materia="<?php echo htmlspecialchars($c['nombre_materia_prima']); ?>">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

</body>

</html>