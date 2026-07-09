<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Cuentas Bancarias</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
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
                <button type="button" id="btnAlternarEstado" class="btn btn-outline-secondary px-3 py-2" data-vista="activos" onclick="alternarVistaInhabilitados('tablaCuentas')">
                    <i class="bi bi-eye-slash-fill me-1" id="iconoEstado"></i> <span id="txtBotonEstado">Ver inhabilitados</span>
                </button>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarCuenta">
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
                        <tr class="fila-cuenta" data-estado="<?= htmlspecialchars($cta['estado_cuenta']) ?>">
                            <td><strong><?= htmlspecialchars($cta['titular']) ?></strong></td>
                            <td><code><?= htmlspecialchars($cta['identificador']) ?></code></td>
                            <td><?= htmlspecialchars($cta['tipo_cuenta']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($cta['nombre_metodo']) ?></span></td>
                            <td>
                                <span class="badge <?= $cta['estado_cuenta'] === 'inhabilitado' ? 'bg-danger' : 'bg-success' ?>">
                                    <?= ucfirst($cta['estado_cuenta'] ?? 'activo') ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-2 justify-content-center">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditarCuenta" 
                                        onclick="cargarDatosEdicionCuenta('<?= $cta['id_cuenta'] ?>', '<?= htmlspecialchars($cta['titular']) ?>', '<?= htmlspecialchars($cta['identificador']) ?>', '<?= htmlspecialchars($cta['tipo_cuenta']) ?>', '<?= $cta['id_metodo_de_pago'] ?? '' ?>')">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    
                                    <button class="btn btn-sm <?= $cta['estado_cuenta'] === 'inhabilitado' ? 'btn-outline-success' : 'btn-outline-danger' ?>" 
                                        onclick="cambiarEstado(<?= $cta['id_cuenta'] ?>, 'cuenta', '<?= $cta['estado_cuenta'] === 'inhabilitado' ? 'activo' : 'inhabilitado' ?>')">
                                        <i class="bi <?= $cta['estado_cuenta'] === 'inhabilitado' ? 'bi-check-circle-fill' : 'bi-trash-fill' ?>"></i>
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

<div class="modal fade" id="modalRegistrarCuenta" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="finanzas-form" action="index.php?controller=Finanzas" method="POST">
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="entidad" value="cuenta">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Registrar Cuenta</h5></div>
                <div class="modal-body">
                    <div class="mb-3"><label>Titular</label><input type="text" class="form-control" name="titular" required></div>
                    <div class="mb-3"><label>Identificador</label><input type="text" class="form-control" name="identificador" required></div>
                    <div class="row">
                        <div class="col-6 mb-3"><label>Tipo</label><input type="text" class="form-control" name="tipo_cuenta" required></div>
                        <div class="col-6 mb-3"><label>Método</label>
                            <select class="form-select" name="id_metodo_de_pago" required>
                                <?php foreach($metodos as $met): ?>
                                    <option value="<?= $met['id_metodo_de_pago'] ?>"><?= htmlspecialchars($met['nombre_metodo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-success">Guardar</button></div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEditarCuenta" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="finanzas-form" action="index.php?controller=Finanzas" method="POST">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="entidad" value="cuenta">
            <input type="hidden" name="id_cuenta" id="edit_cta_id"> 
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Editar Cuenta</h5></div>
                <div class="modal-body">
                    <div class="mb-3"><label>Titular</label><input type="text" class="form-control" name="titular" id="edit_cta_titular" required></div>
                    <div class="mb-3"><label>Identificador</label><input type="text" class="form-control" name="identificador" id="edit_cta_ident" required></div>
                    <div class="row">
                        <div class="col-6 mb-3"><label>Tipo</label><input type="text" class="form-control" name="tipo_cuenta" id="edit_cta_tipo" required></div>
                        <div class="col-6 mb-3"><label>Método</label>
                            <select class="form-select" name="id_metodo_de_pago" id="edit_cta_metodo" required>
                                <?php foreach($metodos as $met): ?>
                                    <option value="<?= $met['id_metodo_de_pago'] ?>"><?= htmlspecialchars($met['nombre_metodo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Guardar Cambios</button></div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="assets/css/bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/finanzas.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('tr[data-estado="inhabilitado"]').forEach(f => f.style.display = 'none');
});

function cargarDatosEdicionCuenta(id, titular, ident, tipo, metodo) {
    document.getElementById('edit_cta_id').value = id;
    document.getElementById('edit_cta_titular').value = titular;
    document.getElementById('edit_cta_ident').value = ident;
    document.getElementById('edit_cta_tipo').value = tipo;
    document.getElementById('edit_cta_metodo').value = metodo;
}
</script>
</body>
</html>