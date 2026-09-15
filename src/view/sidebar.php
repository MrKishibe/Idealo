<link rel="stylesheet" href="assets/css/sidebar.css">
<aside class="sidebar">
    <div class="sidebar-header">
        <i>
            <img src="assets/Img/Iconos/Idealo_logo1.svg" alt="Logo Idealo" class="logo-img">
        </i>
        <span>Idealo</span>
    </div>
    <nav class="sidebar-menu">
        <ul>

            <li>
                <a href="index.php?controller=auth&action=dashboard" class="menu-item">
                    <img src="assets/Img/Iconos/speedometer2.svg" alt="Panel de Control" class="menu-icon">
                    <span>Panel de Control</span>
                </a>
            </li>

            <li class="menu-group">
                <button type="button" class="menu-item trigger" onclick="toggleMenu('sub-empleados')">
                    <div class="trigger-left">
                        <img src="assets/Img/Iconos/people.svg" alt="Empleados" class="menu-icon">
                        <span>Empleados</span>
                    </div>
                    <img src="assets/Img/Iconos/chevron-down.svg" alt="Flecha" class="arrow-icon">
                </button>
                <ul id="sub-empleados" class="submenu">
                    <li>
                        <a href="index.php?controller=empleado&action=listar" class="menu-item">
                            <img src="assets/Img/Iconos/person-lines-fill.svg" alt="Gestionar Empleados" class="menu-icon">
                            <span>Gestionar Empleados</span>
                        </a>
                    </li>
                </ul>
            </li>


            <li class="menu-group">
                <button type="button" class="menu-item trigger" onclick="toggleMenu('sub-inventario')">
                    <div class="trigger-left">
                        <img src="assets/Img/Iconos/box-seam.svg" alt="Inventario" class="menu-icon">
                        <span>Inventario</span>
                    </div>
                    <img src="assets/Img/Iconos/chevron-down.svg" alt="Flecha" class="arrow-icon">
                </button>
                <ul id="sub-inventario" class="submenu">
                    <li>
                        <a href="index.php?controller=MateriaPrima&action=materiaPrima" class="menu-item">
                            <img src="assets/Img/Iconos/egg.svg" alt="Materia Prima" class="menu-icon">
                            <span>Materia Prima</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?controller=tipoMateriaPrima&action=listar" class="menu-item">
                            <img src="assets/Img/Iconos/tags.svg" alt="Tipo de Material" class="menu-icon">
                            <span>Tipo Materia Prima</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?controller=producto&action=listar" class="menu-item">
                            <img src="assets/Img/Iconos/tags.svg" alt="Catálogo Productos" class="menu-icon">
                            <span> Productos</span>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="menu-group">
                <button type="button" class="menu-item trigger" onclick="toggleMenu('sub-pedidos')">
                    <div class="trigger-left">
                        <img src="assets/Img/Iconos/cart3.svg" alt="Pedidos y Ventas" class="menu-icon">
                        <span>Pedidos y Ventas</span>
                    </div>
                    <img src="assets/Img/Iconos/chevron-down.svg" alt="Flecha" class="arrow-icon">
                </button>
                <ul id="sub-pedidos" class="submenu">
                    <li>
                        <a href="index.php?controller=pedido&action=listar" class="menu-item">
                            <img src="assets/Img/Iconos/receipt.svg" alt="Ver Pedidos" class="menu-icon">
                            <span> Pedido</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?controller=servicio&action=listar" class="menu-item">
                            <img src="assets/Img/Iconos/wrench-adjustable.svg" alt="Servicios Extra" class="menu-icon">
                            <span>Servicio </span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?controller=tipoPedido&action=listar" class="menu-item">
                            <img src="assets/Img/Iconos/clipboard-check-fill.svg" alt="Tipo Pedido" class="menu-icon">
                            <span>Tipo De Pedido</span>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="menu-group">
                <button type="button" class="menu-item trigger" onclick="toggleMenu('sub-clientes-grupo')">
                    <div class="trigger-left">
                        <img src="assets/Img/Iconos/people.svg" alt="Clientes" class="menu-icon">
                        <span>Clientes</span>
                    </div>
                    <img src="assets/Img/Iconos/chevron-down.svg" alt="Flecha" class="arrow-icon">
                </button>
                <ul id="sub-clientes-grupo" class="submenu">
                    <li>
                        <a href="index.php?controller=cliente&action=listar" class="menu-item">
                            <img src="assets/Img/Iconos/person-gear.svg" alt="Gestión de Clientes" class="menu-icon">
                            <span>Gestionar Clientes</span>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="menu-group">
                <button type="button" class="menu-item trigger" onclick="toggleMenu('sub-produccion')">
                    <div class="trigger-left">
                        <img src="assets/Img/Iconos/cpu.svg" alt="Producción" class="menu-icon">
                        <span>Producción</span>
                    </div>
                    <img src="assets/Img/Iconos/chevron-down.svg" alt="Flecha" class="arrow-icon">
                </button>
                <ul id="sub-produccion" class="submenu">
                    <li>
                        <a href="index.php?controller=Ordenproduccion&action=listarordenproduccion" class="menu-item">
                            <img src="assets/Img/Iconos/activity.svg" alt="Órdenes Activas" class="menu-icon">
                            <span>Órdenen De Producción</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?controller=consumoMaterial&action=listar" class="menu-item">
                            <img src="assets/Img/Iconos/graph-down-arrow.svg" alt="Consumo de Material" class="menu-icon">
                            <span>Consumo de Material</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?controller=perdidaMaterial&action=listar" class="menu-item">
                            <img src="assets/Img/Iconos/trash3.svg" alt="Pérdidas" class="menu-icon">
                            <span>Pérdida De Material</span>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="menu-group">
                <button type="button" class="menu-item trigger" onclick="toggleMenu('sub-finanzas')">
                    <div class="trigger-left">
                        <img src="assets/Img/Iconos/wallet2.svg" alt="Finanzas" class="menu-icon">
                        <span>Finanzas</span>
                    </div>
                    <img src="assets/Img/Iconos/chevron-down.svg" alt="Flecha" class="arrow-icon">
                </button>
                <ul id="sub-finanzas" class="submenu">
                    <li>
                        <a href="index.php?controller=finanzas&action=pagos" class="menu-item">
                            <img src="assets/Img/Iconos/cash-stack.svg" alt="Control de Pagos" class="menu-icon">
                            <span>Pagos</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?controller=finanzas&action=cuentas" class="menu-item">
                            <img src="assets/Img/Iconos/bank.svg" alt="Cuentas Bancarias" class="menu-icon">
                            <span>Cuenta De Empresa</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?controller=finanzas&action=metodos" class="menu-item">
                            <img src="assets/Img/Iconos/credit-card.svg" alt="Métodos de Pago" class="menu-icon">
                            <span>Método de Pago</span>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="menu-group">
                <button type="button" class="menu-item trigger" onclick="toggleMenu('sub-usuarios')">
                    <div class="trigger-left">
                        <img src="assets/Img/Iconos/shield-lock.svg" alt="Usuarios" class="menu-icon">
                        <span>Usuarios</span>
                    </div>
                    <img src="assets/Img/Iconos/chevron-down.svg" alt="Flecha" class="arrow-icon">
                </button>
                <ul id="sub-usuarios" class="submenu">
                    <li>
                        <a href="index.php?controller=gestionUsuario&action=listar" class="menu-item">
                            <img src="assets/Img/Iconos/person-gear.svg" alt="Gestionar Usuarios" class="menu-icon">
                            <span>Gestionar Usuarios</span>
                        </a>
                    </li>
                </ul>
            </li>

            <li>
                <a href="index.php?controller=usuario&action=perfil" class="menu-item">
                    <img src="assets/Img/Iconos/person-circle.svg" alt="Perfil" class="menu-icon">
                    <span>Perfil</span>
                </a>
            </li>

            <li class="logout-section">
                <a href="index.php?controller=auth&action=logout" class="menu-item">
                    <img src="assets/Img/Iconos/box-arrow-left.svg" alt="Cerrar Sesión" class="menu-icon">
                    <span>Cerrar Sesión</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>

<script>
    window.toggleMenu = function(menuId) {
        const submenu = document.getElementById(menuId);
        if (!submenu) return;

        const container = submenu.parentElement;

        document.querySelectorAll('.menu-group').forEach(group => {
            if (group !== container && group.classList.contains('open')) {
                group.classList.remove('open');
                const otherArrow = group.querySelector('.arrow-icon');
                if (otherArrow) otherArrow.classList.remove('rotated');
            }
        });

        container.classList.toggle('open');
        submenu.classList.toggle('active');

        const arrow = container.querySelector('.arrow-icon');
        if (arrow) {
            arrow.classList.toggle('rotated');
        }
    };
</script>