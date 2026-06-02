<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Materia Prima</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght=400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/estilo.css">
</head>

<body>

    <aside class=" sidebar">
        <div class="sidebar-header">
            <i class="bi bi-lightning-charge-fill logo-icon"></i>
            <span>Idéalo</span>
        </div>
        <nav class="sidebar-menu">
            <ul>
                <li>
                    <a href="index.php?controller=auth&action=dashboard" class="menu-item">
                        <i class="bi bi-speedometer2"></i>
                        <span>Panel de Control</span>
                    </a>
                </li>
                <li class="menu-group">
                    <button class="menu-item trigger" onclick="toggleMenu('sub-empleados')">
                        <div class="trigger-left">
                            <i class="bi bi-people"></i>
                            <span>Empleados</span>
                        </div>
                        <i class="bi bi-chevron-down arrow-icon"></i>
                    </button>
                    <ul id="sub-empleados" class="submenu">
                        <li><a href="index.php?controller=usuario&action=listar"><i class="bi bi-person-lines-fill"></i> Gestionar Empleados</a></li>
                        <li><a href="index.php?controller=usuario&action=roles"><i class="bi bi-shield-lock"></i> Roles y Permisos</a></li>
                    </ul>
                </li>
                <li class="menu-group open">
                    <button class="menu-item trigger" onclick="toggleMenu('sub-inventario')">
                        <div class="trigger-left">
                            <i class="bi bi-box-seam"></i>
                            <span>Inventario</span>
                        </div>
                        <i class="bi bi-chevron-down arrow-icon"></i>
                    </button>
                    <ul id="sub-inventario" class="submenu">
                        <li><a href="index.php?controller=inventario&action=materiaPrima" class="active"><i class="bi bi-egg"></i> Materia Prima</a></li>
                        <li><a href="index.php?controller=inventario&action=productos"><i class="bi bi-tags"></i> Catálogo Productos</a></li>
                    </ul>
                </li>
                <li class="menu-group">
                    <button class="menu-item trigger" onclick="toggleMenu('sub-pedidos')">
                        <div class="trigger-left">
                            <i class="bi bi-cart3"></i>
                            <span>Pedidos y Ventas</span>
                        </div>
                        <i class="bi bi-chevron-down arrow-icon"></i>
                    </button>
                    <ul id="sub-pedidos" class="submenu">
                        <li><a href="index.php?controller=pedido&action=listar"><i class="bi bi-receipt"></i> Ver Pedidos</a></li>
                        <li><a href="index.php?controller=pedido&action=clientes"><i class="bi bi-person-vcard"></i> Registro Clientes</a></li>
                        <li><a href="index.php?controller=pedido&action=servicios"><i class="bi bi-wrench-adjustable"></i> Servicios Extra</a></li>
                    </ul>
                </li>
                <li class="menu-group">
                    <button class="menu-item trigger" onclick="toggleMenu('sub-produccion')">
                        <div class="trigger-left">
                            <i class="bi bi-cpu"></i>
                            <span>Producción</span>
                        </div>
                        <i class="bi bi-chevron-down arrow-icon"></i>
                    </button>
                    <ul id="sub-produccion" class="submenu">
                        <li><a href="index.php?controller=produccion&action=ordenes"><i class="bi bi-activity"></i> Órdenes Activas</a></li>
                        <li><a href="index.php?controller=produccion&action=consumos"><i class="bi bi-graph-down-arrow"></i> Consumo de Material</a></li>
                        <li><a href="index.php?controller=produccion&action=perdidas"><i class="bi bi-trash3"></i> Pérdidas y Desmarques</a></li>
                    </ul>
                </li>
                <li class="menu-group">
                    <button class="menu-item trigger" onclick="toggleMenu('sub-finanzas')">
                        <div class="trigger-left">
                            <i class="bi bi-wallet2"></i>
                            <span>Finanzas</span>
                        </div>
                        <i class="bi bi-chevron-down arrow-icon"></i>
                    </button>
                    <ul id="sub-finanzas" class="submenu">
                        <li><a href="index.php?controller=finanzas&action=pagos"><i class="bi bi-cash-stack"></i> Control de Pagos</a></li>
                        <li><a href="index.php?controller=finanzas&action=cuentas"><i class="bi bi-bank"></i> Cuentas Bancarias</a></li>
                        <li><a href="index.php?controller=finanzas&action=metodos"><i class="bi bi-credit-card"></i> Métodos de Pago</a></li>
                    </ul>
                </li>
                <li class="logout-section">
                    <a href="index.php?controller=auth&action=logout" class="menu-item logout">
                        <i class="bi bi-box-arrow-left"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <div class="view-container">

            <header class="page-header">
                <div>
                    <h1>Inventario de Materia Prima</h1>
                    <p>Monitoreo y auditoría de insumos base, rollos de tela y tintas de sublimación.</p>
                </div>
            </header>

            <div class="table-space">
                <div class="table-actions-bar">
                    <button type="button" class="btn-idealo-success" onclick="abrirModalRegistrar()">
                        <i class="bi bi-plus-circle-fill"></i> Agregar Material / Insumo
                    </button>
                </div>

                <div class="table-container">
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Material base</th>
                                    <th>Categoría de Uso</th>
                                    <th>Stock Actual</th>
                                    <th>Stock Mínimo</th>
                                    <th>Estado de Alerta</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($materiales)): ?>
                                    <?php foreach ($materiales as $mat): ?>
                                        <?php
                                        $unidad = ($mat['id_tipo_materia_prima'] == 1) ? ' m' : ' ml';
                                        $alertaStock = ($mat['stock_actual'] <= $mat['stock_minimo']);
                                        ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($mat['nombre_materia_prima']); ?></strong></td>
                                            <td>
                                                <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 11.5px; font-weight: 600;">
                                                    <?php echo ($mat['id_tipo_materia_prima'] == 1) ? 'Textil / Rollos' : 'Tintas / Líquidos'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span style="font-weight: 700; font-size: 15px; color: <?php echo $alertaStock ? 'var(--rojo-alerta)' : 'var(--azul-opaco)'; ?>;">
                                                    <?php echo htmlspecialchars($mat['stock_actual']) . $unidad; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-muted"><?php echo htmlspecialchars($mat['stock_minimo']) . $unidad; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($alertaStock): ?>
                                                    <span class="status-indicator inactive">Stock Crítico</span>
                                                <?php else: ?>
                                                    <span class="status-indicator active">Disponible</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn-action btn-edit" onclick='abrirModalEditar(<?php echo json_encode($mat); ?>)' title="Modificar Insumo">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <a href="index.php?controller=inventario&action=eliminarMateria&id=<?php echo $mat['id_materia_prima']; ?>"
                                                    class="btn-action btn-delete"
                                                    onclick="return confirmarEliminacion('<?php echo htmlspecialchars($mat['nombre_materia_prima'], ENT_QUOTES, 'UTF-8'); ?>')" title="Dar de baja">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-2 d-block mb-2 text-opacity-50"></i>
                                            No hay insumos registrados en el inventario actual.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <div id="modalRegistrar" class="custom-modal">
        <div class="custom-modal-content">
            <div class="modal-idealo">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-box-seam"></i> Incorporar Nuevo Insumo</h5>
                    <button type="button" class="btn-close" onclick="cerrarModalRegistrar()"></button>
                </div>
                <form action="index.php?controller=inventario&action=guardarMateria" method="POST">
                    <input type="hidden" id="reg_id_unidad_de_medida" name="id_unidad_de_medida" value="1">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="reg_nombre" class="form-label">Nombre o Identificador del Insumo</label>
                            <input type="text" class="form-control" id="reg_nombre" name="nombre_materia_prima" required placeholder="Ej: Tela Atlética Microfibra Premium">
                        </div>
                        <div class="mb-3">
                            <label for="reg_id_tipo" class="form-label">Categoría de Almacén</label>
                            <select class="form-select" id="reg_id_tipo" name="id_tipo_materia_prima" onchange="actualizarEtiquetaUnidad('reg')" required>
                                <option value="1">Textiles e Hilados (Unidades en Metros - m)</option>
                                <option value="2">Insumos Líquidos / Tintas (Unidades en Mililitros - ml)</option>
                            </select>
                        </div>
                        <div class="row g-3 mb-2">
                            <div class="col-6">
                                <label for="reg_actual" class="form-label">Cantidad en Stock</label>
                                <div class="input-unit-group">
                                    <input type="number" class="form-control" id="reg_actual" name="stock_actual" required placeholder="0">
                                    <span id="reg_lbl_unidad1" class="input-unit-badge">m</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <label for="reg_minimo" class="form-label">Límite Mínimo</label>
                                <div class="input-unit-group">
                                    <input type="number" class="form-control" id="reg_minimo" name="stock_minimo" required placeholder="0">
                                    <span id="reg_lbl_unidad2" class="input-unit-badge">m</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light p-3 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light border px-4" onclick="cerrarModalRegistrar()">Cancelar</button>
                        <button type="submit" class="btn btn-idealo-success px-4">Confirmar e Ingresar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modalEditar" class="custom-modal">
        <div class="custom-modal-content">
            <div class="modal-idealo">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Actualizar Propiedades del Material</h5>
                    <button type="button" class="btn-close" onclick="cerrarModalEditar()"></button>
                </div>
                <form action="index.php?controller=inventario&action=editarMateria" method="POST">
                    <input type="hidden" id="edit_id_materia_prima" name="id_materia_prima">
                    <input type="hidden" id="edit_id_unidad_de_medida" name="id_unidad_de_medida">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_nombre" class="form-label">Nombre del Material</label>
                            <input type="text" class="form-control" id="edit_nombre" name="nombre_materia_prima" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_id_tipo" class="form-label">Categoría de Almacén</label>
                            <select class="form-select" id="edit_id_tipo" name="id_tipo_materia_prima" onchange="actualizarEtiquetaUnidad('edit')" required>
                                <option value="1">Textiles e Hilados (Metros - m)</option>
                                <option value="2">Insumos Líquidos / Tintas (Mililitros - ml)</option>
                            </select>
                        </div>
                        <div class="row g-3 mb-2">
                            <div class="col-6">
                                <label for="edit_actual" class="form-label">Stock Físico Real</label>
                                <div class="input-unit-group">
                                    <input type="number" class="form-control" id="edit_actual" name="stock_actual" required>
                                    <span id="edit_lbl_unidad1" class="input-unit-badge">m</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <label for="edit_minimo" class="form-label">Stock Mínimo Permitido</label>
                                <div class="input-unit-group">
                                    <input type="number" class="form-control" id="edit_minimo" name="stock_minimo" required>
                                    <span id="edit_lbl_unidad2" class="input-unit-badge">m</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light p-3 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light border px-4" onclick="cerrarModalEditar()">Cancelar</button>
                        <button type="submit" class="btn btn-idealo-success px-4">Aplicar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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

        const modalReg = document.getElementById('modalRegistrar');
        const modalEdi = document.getElementById('modalEditar');

        function abrirModalRegistrar() {
            modalReg.classList.add('show');
            actualizarEtiquetaUnidad('reg');
        }

        function cerrarModalRegistrar() {
            modalReg.classList.remove('show');
        }

        function abrirModalEditar(datos) {
            document.getElementById('edit_id_materia_prima').value = datos.id_materia_prima;
            document.getElementById('edit_nombre').value = datos.nombre_materia_prima;
            document.getElementById('edit_id_tipo').value = datos.id_tipo_materia_prima;
            document.getElementById('edit_actual').value = datos.stock_actual;
            document.getElementById('edit_minimo').value = datos.stock_minimo;

            modalEdi.classList.add('show');
            actualizarEtiquetaUnidad('edit');
        }

        function cerrarModalEditar() {
            modalEdi.classList.remove('show');
        }

        window.onclick = function(e) {
            if (e.target == modalReg) cerrarModalRegistrar();
            if (e.target == modalEdi) cerrarModalEditar();
        }

        function confirmarEliminacion(nombre) {
            return confirm(`¿Estás completamente seguro de retirar "${nombre}" permanentemente del inventario?`);
        }

        function actualizarEtiquetaUnidad(prefijo) {
            const selectTipo = document.getElementById(`${prefijo}_id_tipo`);
            const lbl1 = document.getElementById(`${prefijo}_lbl_unidad1`);
            const lbl2 = document.getElementById(`${prefijo}_lbl_unidad2`);
            const inputUnidadId = document.getElementById(`${prefijo}_id_unidad_de_medida`);

            if (!selectTipo) return;
            const valorTipo = selectTipo.value;

            if (valorTipo === "1") {
                if (lbl1) lbl1.textContent = "m";
                if (lbl2) lbl2.textContent = "m";
                if (inputUnidadId) inputUnidadId.value = "1";
            } else if (valorTipo === "2") {
                if (lbl1) lbl1.textContent = "ml";
                if (lbl2) lbl2.textContent = "ml";
                if (inputUnidadId) inputUnidadId.value = "2";
            }
        }
    </script>
</body>

</html>