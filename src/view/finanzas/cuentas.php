<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/svg+xml" href="assets/Img/Iconos/Idealo_logo1.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Cuentas de Empresa</title>
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
                <h1 id="tituloVista">Cuentas de Empresa</h1>
                <p>Administra las cuentas bancarias y de pago de la organización.</p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <button type="button" id="btnAlternarEstado" class="btn btn-outline-secondary px-3 py-2" data-vista="activos">
                    <i class="bi bi-eye-slash-fill me-1" id="iconoEstado"></i> <span id="txtBotonEstado">Ver inhabilitados</span>
                </button>
                
                <button type="button" class="btn-idealo-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarCuenta">
                    <i class="bi bi-bank"></i> Registrar Cuenta
                </button>
            </div>
        </header>

        <div class="table-container p-3">
            <div class="table-responsive">
                <table class="custom-table" id="tablaCuentas" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Titular</th>
                            <th>Identificador / Nro. Cuenta</th>
                            <th>Tipo</th>
                            <th>Método Asociado</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyCuentas">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="modal fade modal-idealo" id="modalRegistrarCuenta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="index.php?controller=finanzas&action=cuentas" method="POST" class="finanzas-form" id="formRegistrarCuenta">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-bank me-2"></i>Registrar Cuenta de Empresa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="guardar">
                    <input type="hidden" name="entidad" value="cuenta">
                    
                    <div class="mb-3">
                        <label class="form-label">Método de Pago Asociado</label>
                        <select class="form-select" name="id_metodo_de_pago" required>
                            <option value="">Seleccione...</option>
                            <?php foreach($metodos as $met): ?>
                                <option value="<?= $met['id_metodo_de_pago'] ?>"><?= htmlspecialchars($met['nombre_metodo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo de Cuenta</label>
                            <select class="form-select" name="tipo_cuenta" required>
                                <option value="">Seleccione...</option>
                                <option value="Corriente">Corriente</option>
                                <option value="Ahorro">Ahorro</option>
                                <option value="Zelle">Zelle</option>
                                <option value="Pago Móvil">Pago Móvil</option>
                                <option value="Binance Pay">Binance Pay</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Titular</label>
                            <input type="text" class="form-control" name="titular" required placeholder="Nombre del titular">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Identificador / Número de Cuenta (20 dígitos)</label>
                        <input type="text" class="form-control" name="identificador" maxlength="20" required placeholder="Número de cuenta o correo Zelle">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar Cuenta</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade modal-idealo" id="modalEditarCuenta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="index.php?controller=finanzas&action=cuentas" method="POST" class="finanzas-form" id="formEditarCuenta">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Cuenta de Empresa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="entidad" value="cuenta">
                    <input type="hidden" name="id_cuenta" id="edit_id_cuenta">
                    
                    <div class="mb-3">
                        <label class="form-label">Método de Pago Asociado</label>
                        <select class="form-select" name="id_metodo_de_pago" id="edit_id_metodo_pago" required>
                            <?php foreach($metodos as $met): ?>
                                <option value="<?= $met['id_metodo_de_pago'] ?>"><?= htmlspecialchars($met['nombre_metodo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo de Cuenta</label>
                            <select class="form-select" name="tipo_cuenta" id="edit_tipo_cuenta" required>
                                <option value="Corriente">Corriente</option>
                                <option value="Ahorro">Ahorro</option>
                                <option value="Zelle">Zelle</option>
                                <option value="Pago Móvil">Pago Móvil</option>
                                <option value="Binance Pay">Binance Pay</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Titular</label>
                            <input type="text" class="form-control" name="titular" id="edit_titular" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Identificador / Número de Cuenta</label>
                            <input type="text" class="form-control" name="identificador" id="edit_identificador" maxlength="20" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Estatus de la Cuenta</label>
                            <select class="form-select" name="status_cuenta_empresa" id="edit_status_cuenta" required>
                                <option value="activo">Activo</option>
                                <option value="inhabilitado">Inhabilitado</option>
                            </select>
                        </div>
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