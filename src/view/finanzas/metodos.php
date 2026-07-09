<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Métodos de Pago</title>
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
                <h1 id="tituloVista">Métodos de Pago</h1>
                <p>Configura las vías de pago comerciales aceptadas en la facturación.</p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <button type="button" id="btnAlternarEstado" class="btn btn-outline-secondary px-3 py-2" data-vista="activos" onclick="alternarVistaInhabilitados('tablaMetodos')">
                    <i class="bi bi-eye-slash-fill me-1" id="iconoEstado"></i> <span id="txtBotonEstado">Ver inhabilitados</span>
                </button>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarMetodo">
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
                        <tr class="fila-metodo" data-estado="<?= htmlspecialchars($met['status_metodo_de_pago'] ?? 'activo') ?>">
                            <td><code>#<?= $met['id_metodo_de_pago'] ?></code></td>
                            <td><strong><?= htmlspecialchars($met['nombre_metodo_de_pago']) ?></strong></td>
                            <td>
                                <span class="badge <?= ($met['status_metodo_de_pago'] ?? 'activo') === 'inhabilitado' ? 'bg-danger' : 'bg-success' ?>">
                                    <?= ucfirst($met['status_metodo_de_pago'] ?? 'activo') ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-2 justify-content-center">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditarMetodo" 
                                        onclick="cargarDatosEdicionMetodo('<?= $met['id_metodo_de_pago'] ?>', '<?= htmlspecialchars($met['nombre_metodo_de_pago']) ?>')">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn btn-sm <?= ($met['status_metodo_de_pago'] ?? 'activo') === 'inhabilitado' ? 'btn-outline-success' : 'btn-outline-danger' ?>" 
                                        onclick="cambiarEstado(<?= $met['id_metodo_de_pago'] ?>, 'metodo', '<?= ($met['status_metodo_de_pago'] ?? 'activo') === 'inhabilitado' ? 'activo' : 'inhabilitado' ?>')">
                                        <i class="bi <?= ($met['status_metodo_de_pago'] ?? 'activo') === 'inhabilitado' ? 'bi-check-circle-fill' : 'bi-trash-fill' ?>"></i>
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
<div class="modal fade" id="modalRegistrarMetodo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered"> <form class="finanzas-form" action="index.php?controller=Finanzas" method="POST" id="formRegistrarMetodo">
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="entidad" value="metodo">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registrar Método</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Método</label>
                        <input type="text" class="form-control" name="nombre_metodo_de_pago" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEditarMetodo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered"> <form class="finanzas-form" action="index.php?controller=Finanzas" method="POST">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="entidad" value="metodo">
            <input type="hidden" name="id_metodo_de_pago" id="edit_met_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Método</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Método</label>
                        <input type="text" class="form-control" name="nombre_metodo_de_pago" id="edit_met_nombre" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </div>
        </form>
    </div>

<script src="assets/css/bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/finanzas.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('tr[data-estado="inhabilitado"]').forEach(f => f.style.display = 'none');
});

function cargarDatosEdicionMetodo(id, nombre) {
    document.getElementById('edit_met_id').value = id;
    document.getElementById('edit_met_nombre').value = nombre;
}
</script>
</body>
</html>