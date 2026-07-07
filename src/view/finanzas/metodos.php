<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Métodos de Pago</title>
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

    <?php include __DIR__ . '/../sidebar.php'; ?>

    <main class="main-content">
        <div class="view-container">
            <header class="page-header">
                <div>
                    <h1 id="tituloVista">Métodos de Pago</h1>
                    <p>Configura las vías de pago comerciales aceptadas en la facturación.</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" id="btnAlternarEstado" class="btn btn-outline-secondary px-3 py-2" style="border-radius: var(--radius-md); font-weight: 600;" data-vista="activos">
                        <i class="bi bi-eye-slash-fill me-1" id="iconoEstado"></i> <span id="txtBotonEstado">Ver inhabilitados</span>
                    </button>
                    <button type="button" class="btn-idealo-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarMetodo" onclick="limpiarFormularioCrear()">
                        <i class="bi bi-credit-card"></i> Nuevo Método
                    </button>
                </div>
            </header>

            <div class="table-container p-3">
                <div class="table-responsive">
                    <table class="custom-table" id="tablaMetodos" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre del Método</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($metodos as $met): ?>
                                <tr>
                                    <td><code>#<?= $met['id_metodo_de_pago'] ?></code></td>
                                    <td><strong><?= htmlspecialchars($met['nombre_metodo']) ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?= $met['estado'] === 'activo' ? 'success' : 'danger' ?>">
                                            <?= ucfirst($met['estado']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-2 justify-content-center">
                                            <button class="btn btn-sm btn-outline-primary" style="border-radius: var(--radius-sm);" data-bs-toggle="modal" data-bs-target="#modalEditarMetodo" onclick='cargarDatosEdicion(<?= json_encode($met) ?>)'>
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-<?= $met['estado'] === 'activo' ? 'danger' : 'success' ?>"
                                                style="border-radius: var(--radius-sm);"
                                                onclick="cambiarEstadoMetodo(<?= $met['id_metodo_de_pago'] ?>, <?= $met['estado'] === 'activo' ? 0 : 1 ?>)">
                                                <i class="bi bi-<?= $met['estado'] === 'activo' ? 'eye-slash-fill' : 'eye-fill' ?>"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div class="modal fade modal-idealo" id="modalRegistrarMetodo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-credit-card me-2"></i>Registrar Método</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formRegistrarMetodo">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Seleccione el Método de Pago</label>
                            <select class="form-select" name="nombre_metodo_de_pago" required>
                                <option value="" disabled selected>Elija una opción...</option>
                                <option value="Pago Móvil">Pago Móvil</option>
                                <option value="Transferencia Bancaria">Transferencia Bancaria</option>
                                <option value="Efectivo Dólares">Efectivo Dólares</option>
                                <option value="Efectivo Bolívares">Efectivo Bolívares</option>
                                <option value="Zelle">Zelle</option>
                                <option value="PayPal">PayPal</option>
                                <option value="Punto de Venta">Punto de Venta</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" style="border-radius: var(--radius-md); font-weight: 600;" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-idealo-success">Guardar Canal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade modal-idealo" id="modalEditarMetodo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Método de Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditarMetodo">
                    <input type="hidden" name="id_metodo_de_pago" id="edit_id_metodo">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Modificar Método de Pago</label>
                            <select class="form-select" name="nombre_metodo_de_pago" id="edit_nombre_metodo" required>
                                <option value="Pago Móvil">Pago Móvil</option>
                                <option value="Transferencia Bancaria">Transferencia Bancaria</option>
                                <option value="Efectivo Dólares">Efectivo Dólares</option>
                                <option value="Efectivo Bolívares">Efectivo Bolívares</option>
                                <option value="Zelle">Zelle</option>
                                <option value="PayPal">PayPal</option>
                                <option value="Punto de Venta">Punto de Venta</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" style="border-radius: var(--radius-md); font-weight: 600;" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" style="background-color: var(--azul-opaco); border: none; border-radius: var(--radius-md); padding: 10px 20px; font-weight: 600;">Guardar Cambios</button>
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
    <script src="assets/js/metodos.js"></script>
</body>

</html>