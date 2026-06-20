<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Gestión de Clientes</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/css/estilo.css">
</head>
<body>

<?php include 'src/view/sidebar.php'; ?>

<main class="main-content">
    <div class="view-container">
        <header class="page-header">
            <div>
                <h1 id="tituloVista">Gestión de Clientes</h1>
                <p>Administra los datos de clientes y razones sociales.</p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <button type="button" id="btnAlternarEstado" class="btn btn-outline-secondary px-3 py-2" style="border-radius: var(--radius-md); font-weight: 600;" data-vista="activos">
                    <i class="bi bi-eye-slash-fill me-1" id="iconoEstado"></i> <span id="txtBotonEstado">Ver inhabilitados</span>
                </button>
                <button type="button" class="btn-idealo-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarCliente">
                    <i class="bi bi-person-plus-fill"></i> Registrar Cliente
                </button>
            </div>
        </header>

        <div class="table-container p-3">
            <div class="table-responsive">
                <table class="custom-table" id="tablaClientes" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Cliente</th>
                            <th>Contacto</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyClientes">
                        </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="modal fade modal-idealo" id="modalRegistrarCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus-fill me-2"></i>Registrar Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="index.php?url=cliente/listar" method="POST" id="formRegistrarCliente">
                
                <input type="hidden" name="action" value="guardar">
                <input type="hidden" name="accion" value="guardar">

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Tipo de Documento</label>
                            <select class="form-select" name="tipo_de_documento" id="tipo_de_documento" required>
                                <option value="" disabled selected>Seleccione...</option>
                                <option value="natural">Natural</option>
                                <option value="extranjero">Extranjero</option>
                                <option value="juridico">Jurídico</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Número de Documento / RIF</label>
                            <input type="text" class="form-control" name="numero_de_documento" id="numero_de_documento" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control" name="telefono" id="telefono">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombre / Razón Social</label>
                            <input type="text" class="form-control" name="nombre_razon_social" id="nombre_razon_social" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellido</label>
                            <input type="text" class="form-control" name="apellido" id="apellido">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Dirección</label>
                            <textarea class="form-control" name="direccion" id="direccion" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Correo</label>
                            <input type="email" class="form-control" name="correo" id="correo">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" style="border-radius: var(--radius-md);" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-idealo-success" id="btnGuardar">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade modal-idealo" id="modalEditarCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="index.php?url=cliente/listar" method="POST" id="formEditarCliente">
                
                <input type="hidden" name="action" value="editar">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id_cliente" id="edit_id_cliente">
                
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Tipo de Documento</label>
                            <select class="form-select" name="tipo_de_documento" id="edit_tipo_de_documento" required>
                                <option value="natural">Natural</option>
                                <option value="extranjero">Extranjero</option>
                                <option value="juridico">Jurídico</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Número de Documento</label>
                            <input type="text" class="form-control" name="numero_de_documento" id="edit_numero_de_documento" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control" name="telefono" id="edit_telefono">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombre / Razón Social</label>
                            <input type="text" class="form-control" name="nombre_razon_social" id="edit_nombre_razon_social" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellido</label>
                            <input type="text" class="form-control" name="apellido" id="edit_apellido">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Dirección</label>
                            <textarea class="form-control" name="direccion" id="edit_direccion" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Correo</label>
                            <input type="email" class="form-control" name="correo" id="edit_correo">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estatus del Cliente</label>
                            <select class="form-select" name="status_cliente" id="edit_status_cliente" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" style="border-radius: var(--radius-md);" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" style="background-color: var(--azul-opaco); border: none; border-radius: var(--radius-md); padding: 10px 20px; font-weight: 600;" id="btnGuardarEdicion">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

<script src="assets/js/cliente.js"></script>

</body>
</html>