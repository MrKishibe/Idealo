# AGENTS.md - Idealo (PHP MVC)

## Architecture
- **Entry point:** `index.php` loads `vendor/autoload.php` and `Idealo\Controllers\FrontController`. 
- **Routing:** `FrontController` (`src/controllers/FrontController.php`) parses `$_GET['url']` or falls back to `controller/action`. It maps to `{$controller}Controller` class, validates URL, loads `src/controllers/{$controller}.php`, and calls method with params. Also supports procedural-style controllers that don't expose classes.
- **Autoloading:** PSR-4 via Composer (`src/` mapped to `Idealo\`, `config/` mapped to `Idealo\Config\`).
- **Database:** `config/database.php` defines `Idealo\Config\Database::connect()` returning a singleton PDO (host `localhost`, db `idealo`, user `root`, empty password, UTF8MB4). Note: `AuthController` creates its own PDO directly (bypasses `Database` class). `ModeloBase` (`src/models/ModeloBase.php`) expects `config/Database.php` path case-sensitively (`/../../config/Database.php`).
- **Models:** Many models extend `Database` (e.g. `ClienteModel`, `EmpleadoModel`, `PedidoModel`), others like `ServicioModel` define static methods that use `Database::connect()`/PDO. `ModeloBase` provides `conexion()`, `ejecutar()`, and `verificarExistencia()` with a small allowlist of tables.
- **Views/Assets:** Views live under `src/view/{module}/listar.php`. `sidebar.php` is included from views. JS lives in `assets/js/*.js` and uses relative paths like `assets/js/bootstrap.bundle.min.js`, `assets/js/jquery-3.7.0.min.js`. CSS under `assets/css/`.

## Conventions (high-signal)
- **URLs:** Frontend uses `index.php?controller=xxx&action=yyy` (see `assets/js/servicio.js`, `sidebar.php`). JS expects this routing pattern.
- **AJAX responses:** Controllers return JSON with headers `Content-Type: application/json; charset=utf-8`. Key names vary by module: some use `['success' => bool, 'message' => ...]` (Servicio, Empleado), others use `['status' => 'success'|'error', 'message' => ...]` (Producto), others use `['success' => bool, 'estado' => 'completado'|'error', ...]` (ConsumoMaterial, MateriaPrima, Ordenproduccion, PerdidaMaterial, Finanzas). When consuming endpoints, match the module's expected keys.
- **Procedural + OOP mix:** Several controllers are procedural (e.g. `ServicioController`, `FinanzasController`, `ConsumoMaterialController`, `OrdenproduccionController`, `PerdidaMaterialController`) reading `$_GET['action']` directly; others are class-based with namespaces (Auth, Front, Usuario, Producto). The FrontController handles both cases. 
- **Session auth:** `AuthController` uses `$_SESSION['usuario']`, `$_SESSION['rol']`, `$_SESSION['nombre_usuario']`, `$_SESSION['nombre_rol']`. Other controllers also call `session_start()` when needed.
- **Status values:** Commonly `activo`/`inactivo` (e.g. servicio, cliente). `materia_prima.status_materia_prima` uses `disponible`. `pedido.estado_pedido` values seen: pendiente, en proceso, completado, cancelado. Follow existing values per table (see `config/idealo.sql`).

## Running/Environment
- **PHP version:** 8.2.x (see `php -v`). No build step; this is a traditional XAMPP-style PHP app.
- **Dependencies:** `tecnickcom/tcpdf` (^6.11) in `composer.json`. Vendor files are tracked in repo (`vendor/` present).
- **Database setup:** Import `config/idealo.sql` into MySQL database `idealo` with user `root`/empty password (matches `config/database.php` and `AuthController`).
- **Local dev:** Serve from document root `C:\xampp\htdocs\Idealo` (or equivalent) so `index.php` is at root. Assets use relative paths (`assets/...`), so base path must be root.

## Navigation/entrypoints
- **Dashboard:** `index.php?controller=auth&action=dashboard`
- **Login:** `index.php?controller=auth&action=login` (root redirects to `auth/login` if no URL)
- **Key modules:** Servicio (`servicio/listar`), Cliente, Empleado, Pedido/TipoPedido, Producto, MateriaPrima/TipoMateriaPrima, OrdenProduccion, ConsumoMaterial, PerdidaMaterial, Finanzas (pagos/cuentas/metodos), Usuario/Perfil. See `src/view/sidebar.php` for canonical links.

## Gotchas to avoid
- **Case sensitivity:** Autoload maps `Idealo\Config\` to `config/`; `ModeloBase` requires `Database.php` with capital D (`require_once ... '/../../config/Database.php'`) while the file is `database.php` (lowercase d). On case-insensitive filesystems this may work; be careful on *nix deployments. Also controller class names like `tipomateriaprimacontroller.php` exist but referenced via `tipoMateriaPrima` in routes/JS in places — FrontController does `ucfirst($segments[0]) . 'Controller'`, so filename case must match.
- **Mixed DB access patterns:** Don't assume all models use `ModeloBase`; some use static methods with direct PDO. `AuthController` bypasses `Database` class entirely.
- **AJAX endpoint contracts differ:** When editing JS/controllers, preserve response key names the existing JS expects for that module (don't blindly normalize to one shape).
- **Procedural controllers exit early:** They output JSON and `exit` directly (e.g. ServicioController). The FrontController also handles the case where no class/function remains. Avoid assuming all actions flow through class methods.
- **No automated tests/lint:** No `phpunit`, `phpcs`, `phpstan`, `pint`, etc. configured. There are no `scripts` in composer.json. Don't add test commands unless you see existing ones.
- **Tracked vendor:** `vendor/` is checked in (13 files under git). Avoid modifying vendor unless necessary.
- **TODO exists:** `TODO.md` contains notes about reviewing `src/models/ServicioModel.php` and related Servicio files — may be relevant context.

## Quick verification
- Check routing: `index.php?controller=servicio&action=listar` loads `src/controllers/ServicioController.php`, which requires `src/view/servicio/listar.php` when `action=listar` and no AJAX.
- Check AJAX: `assets/js/servicio.js` calls `index.php?controller=servicio&action=listarServiciosAjax` and `guardar/editar/eliminar`. The controller responds with `success`/`message` keys.
- DB: `Database::connect()` is the canonical abstraction; prefer using it over creating new PDOs when adding code.
