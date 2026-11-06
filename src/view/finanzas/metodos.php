<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/svg+xml" href="assets/Img/Iconos/Idealo_logo1.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Métodos de Pago</title>
    <link rel="stylesheet" href="assets/libs/css/bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/Img/Iconos/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/libs/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/libs/css/estilo.css">
</head>
<body>

<?php include __DIR__ . '/../sidebar.php'; ?>

<main class="main-content">
    <div class="view-container">
        <header class="page-header">
            <div>
                <h1 id="tituloVista">Métodos de Pago</h1>
                <p>Administra las formas de pago aceptadas en el sistema.</p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <button type="button" id="btnAlternarEstado" class="btn btn-outline-secondary px-3 py-2" data-vista="activos">
                    <i class="bi bi-eye-slash-fill me-1" id="iconoEstado"></i> <span id="txtBotonEstado">Ver inhabilitados</span>
                </button>
                
                <button type="button" class="btn-idealo-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarMetodo">
                    <i class="bi bi-credit-card-2-front-fill"></i> Registrar Método
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
                    <tbody id="tbodyMetodos">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="modal fade modal-idealo" id="modalRegistrarMetodo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <form action="index.php?controller=finanzas&action=metodos" method="POST" class="finanzas-form" id="formRegistrarMetodo">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-credit-card-2-front-fill me-2"></i>Registrar Método de Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="guardar">
                    <input type="hidden" name="entidad" value="metodo">
                    
                    <div class="mb-3">
                        <label class="form-label">Nombre del Método de Pago</label>
                        <input type="text" class="form-control" name="nombre_metodo_de_pago" required placeholder="Ej: Transferencia Bancaria">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar Método</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade modal-idealo" id="modalEditarMetodo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <form action="index.php?controller=finanzas&action=metodos" method="POST" class="finanzas-form" id="formEditarMetodo">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Método de Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="entidad" value="metodo">
                    <input type="hidden" name="id_metodo_de_pago" id="edit_id_metodo_de_pago">
                    
                    <div class="mb-3">
                        <label class="form-label">Nombre del Método de Pago</label>
                        <input type="text" class="form-control" name="nombre_metodo_de_pago" id="edit_nombre_metodo_de_pago" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="edit_status_metodo">Estatus del Método</label>
                        <select class="form-select" name="status_metodo_de_pago" id="edit_status_metodo" required>
                            <option value="activo">Activo</option>
                            <option value="inhabilitado">Inhabilitado</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="assets/libs/css/bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/libs/jquery-4.0.0.min.js"></script>
<script src="assets/libs/jquery.dataTables.min.js"></script>
<script src="assets/libs/dataTables.bootstrap5.min.js"></script>
<script src="assets/libs/sweetalert2.all.min.js"></script>

<script type="importmap">
    {"imports":{"jquery":"./assets/js/helpers/jquery-bridge.js"}}
</script>
<script type="module" src="assets/js/finanzas.js"></script>
</body>
</html>