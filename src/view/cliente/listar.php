<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idéalo - Gestión de Clientes</title>

    <!-- Fuentes y CDN -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- CSS Locales -->
    <link rel="stylesheet" href="assets/css/bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/css/estilo.css">
</head>

<body>

    <?php include __DIR__ . '/../sidebar.php'; ?>

    <main class="main-content">
        <div class="view-container">

            <!-- Encabezado de la Vista -->
            <header class="page-header">
                <div>
                    <h1 id="tituloVista">Gestión de Clientes</h1>
                    <p>Administra los datos de clientes y razones sociales.</p>
                </div>

                <div class="d-flex gap-2 align-items-center">
                    <button type="button" 
                            id="btnAlternarEstado" 
                            class="btn btn-outline-secondary px-3 py-2" 
                            style="border-radius: var(--radius-md); font-weight: 600;" 
                            data-vista="activos">
                        <i class="bi bi-eye-slash-fill me-1" id="iconoEstado"></i> 
                        <span id="txtBotonEstado">Ver inhabilitados</span>
                    </button>

                    <button type="button" 
                            class="btn-idealo-success" 
                            data-bs-toggle="modal" 
                            data-bs-target="#modalRegistrarCliente">
                        <i class="bi bi-person-plus-fill"></i> Registrar Cliente
                    </button>
                </div>
            </header>

            <!-- Tabla Principal -->
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

    <!-- ================================================================
         MODAL PARA REGISTRAR CLIENTE
         ================================================================= -->
    <div class="modal fade modal-idealo" id="modalRegistrarCliente" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person-plus-fill me-2"></i>Registrar Cliente
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form id="formRegistrarCliente" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="guardar">
                    <input type="hidden" name="accion" value="guardar">

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="tipo_de_documento">Tipo de Documento</label>
                                <select class="form-select" name="tipo_de_documento" id="tipo_de_documento" required>
                                    <option value="" disabled selected>Seleccione...</option>
                                    <option value="natural">Natural</option>
                                    <option value="extranjero">Extranjero</option>
                                    <option value="juridico">Jurídico</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="numero_de_documento">Número de Documento / RIF</label>
                                <input type="text" class="form-control" name="numero_de_documento" id="numero_de_documento" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="telefono">Teléfono</label>
                                <input type="text" class="form-control" name="telefono" id="telefono">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="nombre_razon_social">Nombre / Razón Social</label>
                                <input type="text" class="form-control" name="nombre_razon_social" id="nombre_razon_social" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="apellido">Apellido</label>
                                <input type="text" class="form-control" name="apellido" id="apellido">
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="direccion">Dirección</label>
                                <textarea class="form-control" name="direccion" id="direccion" rows="2"></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="correo">Correo</label>
                                <input type="email" class="form-control" name="correo" id="correo">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light btn-cancelar-modal" style="border-radius: var(--radius-md);" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-idealo-success" id="btnGuardar">Registrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ================================================================
         MODAL PARA EDITAR CLIENTE
         ================================================================= -->
    <div class="modal fade modal-idealo" id="modalEditarCliente" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square me-2"></i>Editar Cliente
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form id="formEditarCliente" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="editar">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id_cliente" id="edit_id_cliente">

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="edit_tipo_de_documento">Tipo de Documento</label>
                                <select class="form-select" name="tipo_de_documento" id="edit_tipo_de_documento" required>
                                    <option value="natural">Natural</option>
                                    <option value="extranjero">Extranjero</option>
                                    <option value="juridico">Jurídico</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="edit_numero_de_documento">Número de Documento</label>
                                <input type="text" class="form-control" name="numero_de_documento" id="edit_numero_de_documento" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="edit_telefono">Teléfono</label>
                                <input type="text" class="form-control" name="telefono" id="edit_telefono">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="edit_nombre_razon_social">Nombre / Razón Social</label>
                                <input type="text" class="form-control" name="nombre_razon_social" id="edit_nombre_razon_social" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="edit_apellido">Apellido</label>
                                <input type="text" class="form-control" name="apellido" id="edit_apellido">
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="edit_direccion">Dirección</label>
                                <textarea class="form-control" name="direccion" id="edit_direccion" rows="2"></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="edit_correo">Correo</label>
                                <input type="email" class="form-control" name="correo" id="edit_correo">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="edit_status_cliente">Estatus del Cliente</label>
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

    <!-- Scripts Locales -->
    <script src="assets/js/jquery-3.7.0.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/jquery.dataTables.min.js"></script>
    <script src="assets/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/js/sweetalert2.all.min.js"></script>
    <script src="assets/js/cliente.js"></script>

</body>

</html>