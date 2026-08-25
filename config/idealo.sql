-- Configuración inicial de la base de datos
CREATE DATABASE IF NOT EXISTS idealo 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_spanish2_ci;

USE idealo;

-- =========================================================================
-- 1. TABLAS INDEPENDIENTES Y CATÁLOGOS BASE
-- =========================================================================

CREATE TABLE cliente (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    tipo_de_documento VARCHAR(20) NOT NULL,
    numero_de_documento VARCHAR(50) NOT NULL UNIQUE,
    nombre_razon_social VARCHAR(150) NOT NULL,
    apellido VARCHAR(100),
    direccion TEXT,
    correo VARCHAR(100),
    telefono VARCHAR(20),
    status_cliente VARCHAR(20) NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

CREATE TABLE tipo_de_pedido (
    id_tipo_pedido INT AUTO_INCREMENT PRIMARY KEY,
    nombre_tipo_pedido VARCHAR(100) NOT NULL,
    status_tipo_servicio VARCHAR(20) NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

CREATE TABLE metodo_de_pago (
    id_metodo_de_pago INT AUTO_INCREMENT PRIMARY KEY,
    nombre_metodo_de_pago VARCHAR(100) NOT NULL,
    descripcion_metodo_de_pago TEXT,
    status_metodo_de_pago VARCHAR(20) NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

CREATE TABLE servicio (
    id_servicio INT AUTO_INCREMENT PRIMARY KEY,
    nombre_servicio VARCHAR(100) NOT NULL,
    status_servicio VARCHAR(20) NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

CREATE TABLE producto (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    nombre_producto VARCHAR(150) NOT NULL,
    tipo_de_producto VARCHAR(100),
    status_producto VARCHAR(20) NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

CREATE TABLE caracteristica (
    id_caracteristica INT AUTO_INCREMENT PRIMARY KEY,
    detalle_material VARCHAR(150),
    color VARCHAR(50),
    tipo_de_prenda VARCHAR(100),
    status_caracteristica VARCHAR(20) NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

CREATE TABLE tipo_de_materia_prima (
    id_tipo_materia_prima INT AUTO_INCREMENT PRIMARY KEY,
    nombre_de_material VARCHAR(100) NOT NULL,
    descripcion TEXT,
    status_tipo_materia VARCHAR(20) NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

CREATE TABLE roles (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    tipo_de_usuario VARCHAR(50) NOT NULL,
    status_roles VARCHAR(20) NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

CREATE TABLE permiso (
    id_permiso INT AUTO_INCREMENT PRIMARY KEY,
    nombre_permiso VARCHAR(100) NOT NULL,
    status_permiso VARCHAR(20) NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

CREATE TABLE empleado (
    id_empleado INT AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    cedula VARCHAR(20) NOT NULL UNIQUE,
    telefono VARCHAR(20),
    direccion TEXT,
    cargo VARCHAR(100),
    salario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status_empleado VARCHAR(20) NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- =========================================================================
-- 2. TABLAS CON DEPENDENCIAS DE PRIMER NIVEL
-- =========================================================================

CREATE TABLE cuenta_empresa (
    id_cuenta INT AUTO_INCREMENT PRIMARY KEY,
    id_metodo_de_pago INT NOT NULL,
    tipo_cuenta VARCHAR(50),
    identificador VARCHAR(100) NOT NULL,
    titular VARCHAR(150),
    status_cuenta_empresa VARCHAR(20) NOT NULL DEFAULT 'activo',
    FOREIGN KEY (id_metodo_de_pago) REFERENCES metodo_de_pago(id_metodo_de_pago) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

CREATE TABLE pedido (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    fecha_creacion DATE NOT NULL,
    fecha_entrega DATE,
    id_tipo_pedido INT NOT NULL,
    descripcion TEXT,
    estado_pedido VARCHAR(50) NOT NULL DEFAULT 'pendiente',
    descuento_divisa DECIMAL(10,2) DEFAULT 0.00,
    monto_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    id_cliente INT NOT NULL,
    FOREIGN KEY (id_tipo_pedido) REFERENCES tipo_de_pedido(id_tipo_pedido) ON DELETE CASCADE,
    FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

CREATE TABLE pago (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    monto_abonado DECIMAL(10,2) NOT NULL,
    referencia VARCHAR(100),
    fecha_pago DATE NOT NULL,
    status_pago VARCHAR(20) NOT NULL DEFAULT 'procesado',
    id_pedido INT NOT NULL,
    id_metodo_de_pago INT NOT NULL,
    FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido) ON DELETE CASCADE,
    FOREIGN KEY (id_metodo_de_pago) REFERENCES metodo_de_pago(id_metodo_de_pago) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

CREATE TABLE producto_caracteristica (
    id_producto_caracteristica INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    id_caracteristica INT NOT NULL,
    talla VARCHAR(10),
    status_producto_caracteristica VARCHAR(20) NOT NULL DEFAULT 'activo',
    FOREIGN KEY (id_producto) REFERENCES producto(id_producto) ON DELETE CASCADE,
    FOREIGN KEY (id_caracteristica) REFERENCES caracteristica(id_caracteristica) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

CREATE TABLE materia_prima (
    id_materia_prima INT AUTO_INCREMENT PRIMARY KEY,
    nombre_materia_prima VARCHAR(150) NOT NULL,
    id_tipo_materia_prima INT NOT NULL,
    costo_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock_actual INT NOT NULL DEFAULT 0,
    stock_minimo INT NOT NULL DEFAULT 0,
    status_materia_prima VARCHAR(20) NOT NULL DEFAULT 'disponible',
    unidad_de_medida VARCHAR(50) NOT NULL,
    FOREIGN KEY (id_tipo_materia_prima) REFERENCES tipo_de_materia_prima(id_tipo_materia_prima) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

CREATE TABLE usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(20) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    status_usuario VARCHAR(20) NOT NULL DEFAULT 'activo',
    id_rol INT NOT NULL,
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

CREATE TABLE acceso_empleado (
    id_acceso INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL UNIQUE,
    id_empleado INT NOT NULL UNIQUE,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_empleado) REFERENCES empleado(id_empleado) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

CREATE TABLE permisos_rol (
    id_permiso_rol INT AUTO_INCREMENT PRIMARY KEY,
    id_rol INT NOT NULL,
    id_permiso INT NOT NULL,
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol) ON DELETE CASCADE,
    FOREIGN KEY (id_permiso) REFERENCES permiso(id_permiso) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- =========================================================================
-- 3. TABLAS CON DEPENDENCIAS COMPLEJAS (DETALLES Y PRODUCCIÓN)
-- =========================================================================

CREATE TABLE detalle_pedido (
    id_detalle_pedido INT AUTO_INCREMENT PRIMARY KEY,
    costo_mano_de_obra DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    costo_materiales DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    descuento_producto DECIMAL(10,2) DEFAULT 0.00,
    metodo_servicio VARCHAR(100),
    cantidad INT NOT NULL,
    id_producto_caracteristica INT NOT NULL,
    id_pedido INT NOT NULL,
    id_servicio INT NOT NULL,
    FOREIGN KEY (id_producto_caracteristica) REFERENCES producto_caracteristica(id_producto_caracteristica) ON DELETE CASCADE,
    FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido) ON DELETE CASCADE,
    FOREIGN KEY (id_servicio) REFERENCES servicio(id_servicio) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

CREATE TABLE orden_de_produccion (
    id_produccion INT AUTO_INCREMENT PRIMARY KEY,
    fecha_de_inicio DATE NOT NULL,
    fecha_terminado DATE,
    estado_de_produccion VARCHAR(50) NOT NULL DEFAULT 'en espera',
    id_detalle_pedido INT NOT NULL,
    FOREIGN KEY (id_detalle_pedido) REFERENCES detalle_pedido(id_detalle_pedido) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

CREATE TABLE consumo_material (
    id_consumo_material INT AUTO_INCREMENT PRIMARY KEY,
    costo_unitario DECIMAL(10,2) NOT NULL,
    descripcion_de_consumo TEXT,
    cantidad_usada INT NOT NULL,
    id_materia_prima INT NOT NULL,
    id_produccion INT NOT NULL,
    FOREIGN KEY (id_materia_prima) REFERENCES materia_prima(id_materia_prima) ON DELETE CASCADE,
    FOREIGN KEY (id_produccion) REFERENCES orden_de_produccion(id_produccion) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

CREATE TABLE perdida_material (
    id_perdida INT AUTO_INCREMENT PRIMARY KEY,
    cantidad_perdida INT NOT NULL,
    fecha_de_registro DATE NOT NULL,
    motivo TEXT,
    costo_unitario DECIMAL(10,2) NOT NULL,
    id_produccion INT NOT NULL,
    FOREIGN KEY (id_produccion) REFERENCES orden_de_produccion(id_produccion) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

CREATE TABLE asignacion_produccion (
    id_asignacion_produccion INT AUTO_INCREMENT PRIMARY KEY,
    id_produccion INT NOT NULL,
    id_empleado INT NOT NULL,
    FOREIGN KEY (id_produccion) REFERENCES orden_de_produccion(id_produccion) ON DELETE CASCADE,
    FOREIGN KEY (id_empleado) REFERENCES empleado(id_empleado) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;