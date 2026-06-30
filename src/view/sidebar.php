<aside class="sidebar">
    <div class="sidebar-header">
        <i class="bi bi-lightning-charge-fill logo-icon"></i>
        <span>Idealo</span>
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
                <button type="button" class="menu-item trigger" onclick="toggleMenu('sub-empleados')">
                    <div class="trigger-left">
                        <i class="bi bi-people"></i>
                        <span>Empleados</span>
                    </div>
                    <i class="bi bi-chevron-down arrow-icon"></i>
                </button>
                <ul id="sub-empleados" class="submenu">
                    <li><a href="index.php?controller=empleado&action=listar"><i class="bi bi-person-lines-fill"></i> Gestionar Empleados</a></li>
                </ul>
            </li>
            <li class="menu-group">
                <button type="button" class="menu-item trigger" onclick="toggleMenu('sub-inventario')">
                    <div class="trigger-left">
                        <i class="bi bi-box-seam"></i>
                        <span>Inventario</span>
                    </div>
                    <i class="bi bi-chevron-down arrow-icon"></i>
                </button>
                <ul id="sub-inventario" class="submenu">
                    <li><a href="index.php?controller=tipoMateriaPrima&action=listar"><i class="bi bi-egg"></i> Materia Prima</a></li>
                    <li>
                        <a href="index.php?controller=tipoMateriaPrima&action=listar">
                            <i class="bi bi-tags"></i> Tipo de Material 
                        </a>
                    </li>
                    <li><a href="index.php?controller=producto&action=listar"><i class="bi bi-tags"></i> Catálogo Productos</a></li>
                </ul>
            </li>
            <li class="menu-group">
                <button type="button" class="menu-item trigger" onclick="toggleMenu('sub-pedidos')">
                    <div class="trigger-left">
                        <i class="bi bi-cart3"></i>
                        <span>Pedidos y Ventas</span>
                    </div>
                    <i class="bi bi-chevron-down arrow-icon"></i>
                </button>
                <ul id="sub-pedidos" class="submenu">
                    <li><a href="index.php?controller=pedido&action=listar"><i class="bi bi-receipt"></i> Ver Pedidos</a></li>
                    <li><a href="index.php?controller=servicio&action=listar"><i class="bi bi-wrench-adjustable"></i> Servicios Extra</a></li>
                    <li><a href="index.php?controller=tipoPedido&action=listar"><i class="bi bi-clipboard-check-fill"></i> Tipo Pedido</a></li>
                </ul>
            </li>
            <li class="menu-group">
                <button type="button" class="menu-item trigger" onclick="toggleMenu('sub-clientes-grupo')">
                    <div class="trigger-left">
                        <i class="bi bi-people"></i>
                        <span>Clientes</span>
                    </div>
                    <i class="bi bi-chevron-down arrow-icon"></i>
                </button>
                <ul id="sub-clientes-grupo" class="submenu">
                    <li><a href="index.php?controller=cliente&action=listar"><i class="bi bi-person-gear"></i> Gestión de Clientes</a></li>
                </ul>
            </li>
            <li class="menu-group">
                <button type="button" class="menu-item trigger" onclick="toggleMenu('sub-produccion')">
                    <div class="trigger-left">
                        <i class="bi bi-cpu"></i>
                        <span>Producción</span>
                    </div>
                    <i class="bi bi-chevron-down arrow-icon"></i>
                </button>
                <ul id="sub-produccion" class="submenu">
                    <li><a href="index.php?controller=Ordenproduccion&action=listarordenproduccion"><i class="bi bi-activity"></i> Órdenes Activas</a></li>
                    <li><a href="index.php?controller=consumoMaterial&action=listar"><i class="bi bi-graph-down-arrow"></i> Consumo de Material</a></li>
                    <li><a href="index.php?controller=perdidaMaterial&action=listar"><i class="bi bi-trash3"></i> Pérdidas y Desmarques</a></li>
                </ul>
            </li>
            <li class="menu-group">
                <button type="button" class="menu-item trigger" onclick="toggleMenu('sub-finanzas')">
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
            <li class="menu-group">
                <button type="button" class="menu-item trigger" onclick="toggleMenu('sub-perfil')">
                    <div class="trigger-left">
                        <i class="bi bi-person-circle"></i>
                        <span>Perfil</span>
                    </div>
                    <i class="bi bi-chevron-down arrow-icon"></i>
                </button>
                <ul id="sub-perfil" class="submenu">
                    <li><a href="index.php?controller=usuario&action=perfil"><i class="bi bi-person-vcard"></i> Mi perfil</a></li>
                    <li><a href="index.php?controller=gestionUsuario&action=listar"><i class="bi bi-person-gear"></i> Gestionar Usuarios</a></li>
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

<script>
// Forzamos el registro en el objeto global WINDOW para que sobreviva a jQuery y DataTables
window.toggleMenu = function(menuId) {
    const menu = document.getElementById(menuId);
    if (!menu) return;
    
    menu.classList.toggle('active');
    
    // Verificación segura para evitar errores si la estructura previa cambia dinámicamente
    const trigger = menu.previousElementSibling;
    if (trigger) {
        const arrow = trigger.querySelector('.arrow-icon');
        if (arrow) {
            arrow.classList.toggle('rotated');
        }
    }
};
</script>
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