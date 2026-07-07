<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Cuentas Bancarias</title>
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
                    <h1 id="tituloVista">Cuentas de la Empresa</h1>
                    <p>Gestiona las cuentas de banco, pagos móviles y billeteras del negocio.</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" id="btnAlternarEstado" class="btn btn-outline-secondary px-3 py-2" style="border-radius: var(--radius-md); font-weight: 600;" data-vista="activos">
                        <i class="bi bi-eye-slash-fill me-1" id="iconoEstado"></i> <span id="txtBotonEstado">Ver inhabilitadas</span>
                    </button>
                    <button type="button" class="btn-idealo-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarCuenta" onclick="limpiarFormularioCrear()">
                        <i class="bi bi-bank"></i> Registrar Cuenta
                    </button>
                </div>
            </header>

            <div class="table-container p-3">
                <div class="table-responsive">
                    <table class="custom-table" id="tablaCuentas" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Titular</th>
                                <th>Identificador / Número</th>
                                <th>Tipo</th>
                                <th>Método Asociado</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cuentas as $cta): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($cta['titular']) ?></strong></td>
                                    <td><code><?= htmlspecialchars($cta['identificador']) ?></code></td>
                                    <td><?= htmlspecialchars($cta['tipo_cuenta']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($cta['nombre_metodo']) ?></span></td>
                                    <td>
                                        <span class="badge bg-<?= $cta['estado'] === 'activo' ? 'success' : 'danger' ?>">
                                            <?= ucfirst($cta['estado']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-2 justify-content-center">
                                            <button class="btn btn-sm btn-outline-primary" style="border-radius: var(--radius-sm);" data-bs-toggle="modal" data-bs-target="#modalEditarCuenta" onclick='cargarDatosEdicion(<?= json_encode($cta) ?>)'>
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-<?= $cta['estado'] === 'activo' ? 'danger' : 'success' ?>"
                                                style="border-radius: var(--radius-sm);"
                                                onclick="cambiarEstadoCuenta(<?= $cta['id_cuenta'] ?>, <?= $cta['estado'] === 'activo' ? 0 : 1 ?>)">
                                                <i class="bi bi-<?= $cta['estado'] === 'activo' ? 'eye-slash-fill' : 'eye-fill' ?>"></i>
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

    <!-- MODAL REGISTRAR -->
    <div class="modal fade modal-idealo" id="modalRegistrarCuenta" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-bank me-2"></i>Registrar Nueva Cuenta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formRegistrarCuenta">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Titular de la Cuenta</label>
                                <input type="text" class="form-control" name="titular" required placeholder="Ej: Inversiones Idealo C.A.">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Método de Pago Comercial</label>
                                <select class="form-select" name="id_metodo_de_pago" required>
                                    <option value="" disabled selected>Seleccione método...</option>
                                    <?php foreach ($metodos as $met): ?>
                                        <option value="<?= $met['id_metodo_de_pago'] ?>"><?= htmlspecialchars($met['nombre_metodo']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipo de Cuenta</label>
                                <input type="text" class="form-control" name="tipo_cuenta" required placeholder="Ej: Corriente, Ahorros, Única">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Identificador (Nro. Cuenta / Teléfono / Correo)</label>
                                <input type="text" class="form-control" name="identificador" required placeholder="Ej: 0102-XXXX-XX-XXXXXXXXXX">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" style="border-radius: var(--radius-md); font-weight: 600;" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-idealo-success">Guardar Cuenta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL EDITAR -->
    <div class="modal fade modal-idealo" id="modalEditarCuenta" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Cuenta Bancaria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditarCuenta">
                    <input type="hidden" name="id_cuenta" id="edit_id_cuenta">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Titular de la Cuenta</label>
                                <input type="text" class="form-control" name="titular" id="edit_titular" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Método de Pago Comercial</label>
                                <select class="form-select" name="id_metodo_de_pago" id="edit_id_metodo_de_pago" required>
                                    <?php foreach ($metodos as $met): ?>
                                        <option value="<?= $met['id_metodo_de_pago'] ?>"><?= htmlspecialchars($met['nombre_metodo']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipo de Cuenta</label>
                                <input type="text" class="form-control" name="tipo_cuenta" id="edit_tipo_cuenta" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Identificador (Nro. Cuenta / Teléfono / Correo)</label>
                                <input type="text" class="form-control" name="identificador" id="edit_identificador" required>
                            </div>
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
    <script src="assets/js/cuentas.js"></script>
</body>

</html>