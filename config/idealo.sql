-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 14-11-2026 a las 18:03:26
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `idealo`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `acceso_empleado`
--

CREATE TABLE `acceso_empleado` (
  `id_acceso` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_empleado` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignacion_produccion`
--

CREATE TABLE `asignacion_produccion` (
  `id_asignacion_produccion` int(11) NOT NULL,
  `id_produccion` int(11) NOT NULL,
  `id_empleado` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `asignacion_produccion`
--

INSERT INTO `asignacion_produccion` (`id_asignacion_produccion`, `id_produccion`, `id_empleado`) VALUES
(8, 4, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `caracteristica`
--

CREATE TABLE `caracteristica` (
  `id_caracteristica` int(11) NOT NULL,
  `detalle_material` varchar(150) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `tipo_de_prenda` varchar(100) DEFAULT NULL,
  `status_caracteristica` varchar(20) NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `caracteristica`
--

INSERT INTO `caracteristica` (`id_caracteristica`, `detalle_material`, `color`, `tipo_de_prenda`, `status_caracteristica`) VALUES
(1, 'Algodón Peinado 24/1', 'Azul Marino', 'Franela', 'activo'),
(2, 'Algodón Peinado 24/1', 'Blanco', 'Franela', 'activo'),
(3, 'Piqué Algodón/Poliéster', 'Negro', 'Chemise', 'activo'),
(4, 'Gabardina Pesada', 'Rojo', 'Gorra', 'activo'),
(5, 'asdasdasdasdasd', 'rojo', 'conjunto', 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `id_cliente` int(11) NOT NULL,
  `tipo_de_documento` varchar(20) NOT NULL,
  `numero_de_documento` varchar(50) NOT NULL,
  `nombre_razon_social` varchar(150) NOT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `status_cliente` varchar(20) NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`id_cliente`, `tipo_de_documento`, `numero_de_documento`, `nombre_razon_social`, `apellido`, `direccion`, `correo`, `telefono`, `status_cliente`) VALUES
(1, 'juridico', 'J-309485720', 'Inversiones Textil Express CA', '', 'Av. Las Industrias, Barquisimeto', 'contacto@textilexpress.com', '04145550101', 'activo'),
(2, 'natural', 'V-18493021', 'Carlos', 'Mendoza', 'Calle 25 con Carrera 19, Barquisimeto', 'carlos.mendoza@gmail.com', '04125550102', 'inactivo'),
(3, 'V', 'V-20192834', 'María', 'Gómez', 'Urb. El Parral, Cabudare', 'maria.gomez@hotmail.com', '04165550103', 'inactivo'),
(4, 'juridico', 'J-401293845', 'Diseños y Modas', '', 'Centro Comercial Las Trinitarias', 'ventas@disenosymodas.com', '04125155501', 'inactivo'),
(5, 'extranjero', '345345345', 'jonh price', 'price', 'la SAS', 'CJP@gmail.com', '04245651636', 'activo'),
(6, 'natural', '321321321', 'jose', 'jose', 'por ahí', 'josej@gmail.com', '04125155501', 'activo'),
(7, 'extranjero', '311321321', 'dfgdf', 'dfgdfgdfxcvcxv', 'dsfsd', 'alan@gmail.com', '04245651636', 'inactivo'),
(8, 'extranjero', '321321329', 'dfgdfgdfg', 'montilla', 'sdfsdf', 'alan@gmail.com', '04125155501', 'inactivo'),
(9, 'extranjero', '321321325', 'oliver', 'atom', 'la cancha', 'oliver@gmail.com', '04125155501', 'activo'),
(10, 'juridico', '321321326', 'industrias stark', '', 'torre stark', 'aironman@gmail.com', '04125155501', 'activo'),
(11, 'extranjero', '321321321', 'mufasa', 'el rey', 'su reino', 'rey@gmail.com', '04125155501', 'activo'),
(12, 'natural', '321321389', 'delsin', 'roug', 'seatle', 'infame@gmail.com', '04125155506', 'activo'),
(13, 'natural', '321321328', 'ottis', 'gomez', 'un casa verde con negro', 'ott@gmail.com', '04125155506', 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consumo_material`
--

CREATE TABLE `consumo_material` (
  `id_consumo_material` int(11) NOT NULL,
  `costo_unitario` decimal(10,2) NOT NULL,
  `descripcion_de_consumo` text DEFAULT NULL,
  `cantidad_usada` int(11) NOT NULL,
  `id_materia_prima` int(11) NOT NULL,
  `id_produccion` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `consumo_material`
--

INSERT INTO `consumo_material` (`id_consumo_material`, `costo_unitario`, `descripcion_de_consumo`, `cantidad_usada`, `id_materia_prima`, `id_produccion`) VALUES
(1, 6.00, 'dfsf', 1, 22, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuenta_empresa`
--

CREATE TABLE `cuenta_empresa` (
  `id_cuenta` int(11) NOT NULL,
  `id_metodo_de_pago` int(11) NOT NULL,
  `tipo_cuenta` varchar(50) DEFAULT NULL,
  `identificador` varchar(100) NOT NULL,
  `titular` varchar(150) DEFAULT NULL,
  `status_cuenta_empresa` varchar(20) NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `cuenta_empresa`
--

INSERT INTO `cuenta_empresa` (`id_cuenta`, `id_metodo_de_pago`, `tipo_cuenta`, `identificador`, `titular`, `status_cuenta_empresa`) VALUES
(1, 1, 'Corriente', '01020124500012345675', 'Idealo Textil C.A.', 'activo'),
(2, 2, 'Pago Móvil', '04145550000500123450', 'Idealo Textil C.A.', 'activo'),
(3, 4, 'Zelle', '11234567891011121314', 'Idealo Textil LLC', 'activo'),
(4, 1, 'sddsf', '12346589897986546544', 'cxcv', 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_pedido`
--

CREATE TABLE `detalle_pedido` (
  `id_detalle_pedido` int(11) NOT NULL,
  `costo_mano_de_obra` decimal(10,2) NOT NULL DEFAULT 0.00,
  `costo_materiales` decimal(10,2) NOT NULL DEFAULT 0.00,
  `descuento_producto` decimal(10,2) DEFAULT 0.00,
  `metodo_servicio` varchar(100) DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `id_producto_caracteristica` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_servicio` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `detalle_pedido`
--

INSERT INTO `detalle_pedido` (`id_detalle_pedido`, `costo_mano_de_obra`, `costo_materiales`, `descuento_producto`, `metodo_servicio`, `cantidad`, `id_producto_caracteristica`, `id_pedido`, `id_servicio`) VALUES
(23, 0.00, 0.00, 0.00, NULL, 1, 2, 2, 3),
(24, 0.00, 0.00, 0.00, 'd', 1, 1, 3, 2),
(28, 0.00, 0.00, 0.00, 'd', 1, 3, 1, 3),
(33, 5.00, 6.00, 0.00, NULL, 200, 1, 4, 3),
(34, 0.00, 0.00, 0.00, NULL, 1, 3, 9, 9),
(35, 0.00, 0.00, 0.00, NULL, 1, 2, 8, 3),
(36, 4.00, 6.00, 0.00, NULL, 10, 4, 5, 4),
(37, 0.00, 0.00, 0.00, NULL, 12, 2, 6, 2),
(38, 0.00, 0.00, 0.00, NULL, 1, 7, 7, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado`
--

CREATE TABLE `empleado` (
  `id_empleado` int(11) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `cedula` varchar(20) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `salario` decimal(10,2) NOT NULL DEFAULT 0.00,
  `foto` varchar(255) DEFAULT NULL,
  `status_empleado` varchar(20) NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `empleado`
--

INSERT INTO `empleado` (`id_empleado`, `nombres`, `apellidos`, `cedula`, `telefono`, `direccion`, `cargo`, `salario`, `foto`, `status_empleado`) VALUES
(1, 'Ana', 'Martínez', 'V-15234891', '04141112233', 'Urb. Los Crepúsculos', 'Contador', 450.00, NULL, 'Activo'),
(2, 'José', 'Hernández', 'V-17823901', '04242223344', 'Av. Lara con Argimiro Bracamonte', 'Contador', 320.00, 'assets/Img/Empleados/empleado_1793939529_84d2ce0b.jpg', 'Activo'),
(3, 'Pedro', 'Pérez', 'V-19345678', '04123334455', 'El Ustatus', 'Editor', 250.00, 'assets/Img/Empleados/empleado_1793939511_389dffd8.jpg', 'Activo'),
(4, 'Luisa', 'Rojas', 'V-22341098', '04164445566', 'Cabudare Centro', 'Recepcionista', 200.00, NULL, 'Inactivo'),
(5, 'alan', 'montilla', '30601666', '04245851326', 'mi casa', 'Costurero', 300.00, 'assets/Img/Empleados/empleado_1793939495_fcfbdd00.jpg', 'Activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materia_prima`
--

CREATE TABLE `materia_prima` (
  `id_materia_prima` int(11) NOT NULL,
  `nombre_materia_prima` varchar(150) NOT NULL,
  `id_tipo_materia_prima` int(11) NOT NULL,
  `costo_unitario` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_actual` int(11) NOT NULL DEFAULT 0,
  `stock_minimo` int(11) NOT NULL DEFAULT 0,
  `status_materia_prima` varchar(20) NOT NULL DEFAULT 'disponible',
  `unidad_de_medida` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `materia_prima`
--

INSERT INTO `materia_prima` (`id_materia_prima`, `nombre_materia_prima`, `id_tipo_materia_prima`, `costo_unitario`, `stock_actual`, `stock_minimo`, `status_materia_prima`, `unidad_de_medida`) VALUES
(1, 'Tela Piqué Negra (Metros)', 1, 3.50, 115, 20, 'Activo', 'Metros'),
(2, 'Tela Algodón Blanco 24/1 (Metros)', 1, 2.80, 200, 30, 'Inactivo', 'Metros'),
(3, 'Hilo Bordado Poliéster Rojo', 1, 1.50, 40, 5, 'Inactivo', 'pieza'),
(4, 'Tinta Plastisol Negra Kilos', 4, 12.00, 8, 2, 'Activo', 'miligramo'),
(5, 'dfgdg', 2, 55.00, 45, 24, 'Activo', 'unidad'),
(6, 'Tinta Sublimación Negra', 1, 15.50, 50, 10, 'Activo', 'litro'),
(7, 'Papel Sublimación A', 3, 25.00, 100, 20, 'Activo', 'paquete'),
(8, 'Cinta Térmica', 6, 3.25, 30, 5, 'Activo', 'unidad'),
(9, 'Taza Cerámica Blanca', 4, 1.20, 200, 40, 'Inactivo', 'pieza'),
(10, 'hilo rojo', 2, 11.00, 41, 4, 'Activo', 'unidad'),
(11, 'tela termica', 1, 65.00, 45, 45, 'Activo', 'milímetro'),
(12, 'boton grabado', 4, 654654.00, 45, 54, 'Activo', 'metro'),
(13, 'tinta de impresora', 8, 2.00, 4, 4, 'Activo', 'unidad'),
(14, 'sdfsdft', 4, 2.00, 45, 4, 'Activo', 'miligramo'),
(15, 'carton de sublimado', 6, 5.00, 5, 5, 'Activo', 'metro'),
(16, 'fddfgdg', 7, 453.00, 345, 345, 'Activo', 'milímetro'),
(17, 'hilo de maquina', 2, 4.00, 34, 34, 'Activo', 'docena'),
(18, 'cierre magico', 4, 3.00, 4, 5, 'Activo', 'metro'),
(19, 'hilo negro', 2, 20.00, 50, 10, 'Activo', 'unidad'),
(20, 'carton de caja', 6, 3.00, 20, 4, 'Activo', 'caja'),
(21, 'aceite', 3, 2.00, 13, 5, 'Activo', 'galon'),
(22, 'plastico', 3, 6.00, 41, 2, 'Activo', 'docena'),
(23, 'boton negro', 4, 6.00, 23, 4, 'Activo', 'paquete'),
(24, 'asd', 6, 45.00, 60, 2, 'Activo', 'miligramo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metodo_de_pago`
--

CREATE TABLE `metodo_de_pago` (
  `id_metodo_de_pago` int(11) NOT NULL,
  `nombre_metodo_de_pago` varchar(100) NOT NULL,
  `descripcion_metodo_de_pago` text DEFAULT NULL,
  `status_metodo_de_pago` varchar(20) NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `metodo_de_pago`
--

INSERT INTO `metodo_de_pago` (`id_metodo_de_pago`, `nombre_metodo_de_pago`, `descripcion_metodo_de_pago`, `status_metodo_de_pago`) VALUES
(1, 'Transferencia Bancaria', 'Pagos por transferencia en moneda nacional (VES)', 'activo'),
(2, 'Pago Móvil', 'Pago instantáneo por telefonía móvil', 'activo'),
(3, 'Efectivo USD', 'Divisas en efectivo', 'activo'),
(4, 'Zelle', 'Transferencia directa en dólares americanos', 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orden_de_produccion`
--

CREATE TABLE `orden_de_produccion` (
  `id_produccion` int(11) NOT NULL,
  `fecha_de_inicio` date NOT NULL,
  `fecha_terminado` date DEFAULT NULL,
  `estado_de_produccion` varchar(50) NOT NULL DEFAULT 'en espera',
  `id_detalle_pedido` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `orden_de_produccion`
--

INSERT INTO `orden_de_produccion` (`id_produccion`, `fecha_de_inicio`, `fecha_terminado`, `estado_de_produccion`, `id_detalle_pedido`) VALUES
(4, '2026-11-06', '0000-00-00', 'Planificado', 35);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pago`
--

CREATE TABLE `pago` (
  `id_pago` int(11) NOT NULL,
  `monto_abonado` decimal(10,2) NOT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `fecha_pago` date NOT NULL,
  `status_pago` varchar(20) NOT NULL DEFAULT 'procesado',
  `comprobante_de_pago` varchar(255) DEFAULT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_metodo_de_pago` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `pago`
--

INSERT INTO `pago` (`id_pago`, `monto_abonado`, `referencia`, `fecha_pago`, `status_pago`, `comprobante_de_pago`, `id_pedido`, `id_metodo_de_pago`) VALUES
(1, 225.00, '987123', '2026-11-28', 'procesado', NULL, 3, 1),
(2, 225.00, '987890', '2026-11-27', 'procesado', NULL, 6, 4),
(3, 60.00, '451239', '2026-11-06', 'procesado', 'assets/uploads/comprobantes/comprobante_1793939451_6aed57fb02064.png', 2, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido`
--

CREATE TABLE `pedido` (
  `id_pedido` int(11) NOT NULL,
  `fecha_creacion` date NOT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `id_tipo_pedido` int(11) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado_pedido` varchar(50) NOT NULL DEFAULT 'pendiente',
  `descuento_divisa` decimal(10,2) DEFAULT 0.00,
  `monto_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `id_cliente` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `pedido`
--

INSERT INTO `pedido` (`id_pedido`, `fecha_creacion`, `fecha_entrega`, `id_tipo_pedido`, `descripcion`, `estado_pedido`, `descuento_divisa`, `monto_total`, `id_cliente`) VALUES
(1, '2026-09-01', '2026-09-10', 1, 'Lote de 50 chemises corporativas con bordado en pecho', 'pendiente', 10.00, 0.00, 1),
(2, '2026-09-15', '2026-09-25', 1, '12 franelas blancas impresas en serigrafía para evento', 'en proceso', 0.00, 0.00, 1),
(3, '2026-10-01', '2026-10-12', 2, '100 gorras rojas bordadas con logo de la marca', 'inhabilitado', 15.00, 0.00, 5),
(4, '2026-10-31', '2026-11-07', 1, 'dfgdg', 'pendiente', 0.00, 2200.00, 2),
(5, '2026-10-29', '2026-10-31', 2, 'tanta para el señor jonh', 'pendiente', 0.00, 100.00, 5),
(6, '2026-10-29', '2026-10-31', 3, 'primera parte del encargo', 'pendiente', 0.00, 0.00, 1),
(7, '2026-11-03', NULL, 1, '1 pieza con grabado único en el pecho', 'entregado', 0.00, 0.00, 1),
(8, '2026-11-04', NULL, 1, 'planchado personal', 'Planificado', 0.00, 0.00, 6),
(9, '2026-11-04', NULL, 2, 'cliente especial', 'pendiente', 0.00, 0.00, 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `perdida_material`
--

CREATE TABLE `perdida_material` (
  `id_perdida` int(11) NOT NULL,
  `cantidad_perdida` int(11) NOT NULL,
  `fecha_de_registro` date NOT NULL,
  `motivo` text DEFAULT NULL,
  `costo_total_perdida` decimal(10,2) NOT NULL,
  `id_consumo_material` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permiso`
--

CREATE TABLE `permiso` (
  `id_permiso` int(11) NOT NULL,
  `nombre_permiso` varchar(100) NOT NULL,
  `status_permiso` varchar(20) NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `permiso`
--

INSERT INTO `permiso` (`id_permiso`, `nombre_permiso`, `status_permiso`) VALUES
(1, 'GESTION_USUARIOS', 'activo'),
(2, 'CREAR_PEDIDO', 'activo'),
(3, 'GESTION_INVENTARIO', 'activo'),
(4, 'ASIGNAR_PRODUCCION', 'activo'),
(5, 'REGISTRAR_PAGO', 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos_rol`
--

CREATE TABLE `permisos_rol` (
  `id_permiso_rol` int(11) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `id_permiso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `id_producto` int(11) NOT NULL,
  `nombre_producto` varchar(150) NOT NULL,
  `tipo_de_producto` varchar(100) DEFAULT NULL,
  `status_producto` varchar(20) NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`id_producto`, `nombre_producto`, `tipo_de_producto`, `status_producto`) VALUES
(1, 'Franela Algodón 100%', 'Ropa Casual', 'activo'),
(2, 'Camisa Chemise', 'Ropa Corporativa', 'activo'),
(3, 'Gorra Gabardina', 'Accesorio', 'activo'),
(4, 'Uniforme Deportivo', 'Ropa', 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_caracteristica`
--

CREATE TABLE `producto_caracteristica` (
  `id_producto_caracteristica` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `id_caracteristica` int(11) NOT NULL,
  `talla` varchar(10) DEFAULT NULL,
  `status_producto_caracteristica` varchar(20) NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `producto_caracteristica`
--

INSERT INTO `producto_caracteristica` (`id_producto_caracteristica`, `id_producto`, `id_caracteristica`, `talla`, `status_producto_caracteristica`) VALUES
(1, 2, 3, 'L', 'activo'),
(2, 2, 3, 'M', 'activo'),
(3, 1, 2, 'M', 'activo'),
(4, 3, 4, 'Única', 'activo'),
(5, 4, 5, 's', 'activo'),
(6, 4, 5, 'm', 'activo'),
(7, 4, 5, 'l', 'activo'),
(8, 4, 5, 'xl', 'activo'),
(9, 4, 5, 'xxl', 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `tipo_de_usuario` varchar(50) NOT NULL,
  `status_roles` varchar(20) NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `tipo_de_usuario`, `status_roles`) VALUES
(1, 'superadministrador', 'activo'),
(2, 'administrador', 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicio`
--

CREATE TABLE `servicio` (
  `id_servicio` int(11) NOT NULL,
  `nombre_servicio` varchar(100) NOT NULL,
  `status_servicio` varchar(20) NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `servicio`
--

INSERT INTO `servicio` (`id_servicio`, `nombre_servicio`, `status_servicio`) VALUES
(1, 'Bordado Computarizado', 'inactivo'),
(2, 'Estampado y Serigrafía', 'activo'),
(3, 'Corte y Costura', 'activo'),
(4, 'Sublimación', 'activo'),
(8, 'plancha', 'activo'),
(9, 'grabado laser', 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_de_materia_prima`
--

CREATE TABLE `tipo_de_materia_prima` (
  `id_tipo_materia_prima` int(11) NOT NULL,
  `nombre_de_material` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `status_tipo_materia` varchar(20) NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `tipo_de_materia_prima`
--

INSERT INTO `tipo_de_materia_prima` (`id_tipo_materia_prima`, `nombre_de_material`, `descripcion`, `status_tipo_materia`) VALUES
(1, 'Telas', 'Telas de algodón, poliéster, piqué y mezclas', 'Activo'),
(2, 'Hilos', 'Hilos para costura y bordado de alta resistencia', 'Activo'),
(3, 'Insumos', 'Tintas mallas y emulsionantes', 'Inactivo'),
(4, 'Botones y Cierres', 'Mercería varia para confección', 'Activo'),
(6, 'carton', '', 'Activo'),
(7, 'tinta', 'para sublimar', 'Activo'),
(8, 'cartucho', 'de tinta', 'Activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_de_pedido`
--

CREATE TABLE `tipo_de_pedido` (
  `id_tipo_pedido` int(11) NOT NULL,
  `nombre_tipo_pedido` varchar(100) NOT NULL,
  `status_tipo_servicio` varchar(20) NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `tipo_de_pedido`
--

INSERT INTO `tipo_de_pedido` (`id_tipo_pedido`, `nombre_tipo_pedido`, `status_tipo_servicio`) VALUES
(1, 'Personalizado', 'Activo'),
(2, 'Al Mayor si', 'Activo'),
(3, 'Reparación Ajuste', 'Activo'),
(4, 'rapido', 'Activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `nombre_usuario` varchar(20) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `status_usuario` varchar(20) NOT NULL DEFAULT 'activo',
  `id_rol` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `nombre_usuario`, `contrasena`, `correo`, `imagen`, `status_usuario`, `id_rol`) VALUES
(1, 'admin', '$2y$10$lJNl2/LmuthUAAFJHcmGEuXjTqXip.6GOACyxRfbxBAyt4OLCj0mW', '', 'assets/Img/Perfiles/perfil_1_1793684796_dc19c862.jpg', 'activo', 2);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `acceso_empleado`
--
ALTER TABLE `acceso_empleado`
  ADD PRIMARY KEY (`id_acceso`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`),
  ADD UNIQUE KEY `id_empleado` (`id_empleado`);

--
-- Indices de la tabla `asignacion_produccion`
--
ALTER TABLE `asignacion_produccion`
  ADD PRIMARY KEY (`id_asignacion_produccion`),
  ADD KEY `id_produccion` (`id_produccion`),
  ADD KEY `id_empleado` (`id_empleado`);

--
-- Indices de la tabla `caracteristica`
--
ALTER TABLE `caracteristica`
  ADD PRIMARY KEY (`id_caracteristica`);

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `unique_tipo_documento` (`tipo_de_documento`,`numero_de_documento`);

--
-- Indices de la tabla `consumo_material`
--
ALTER TABLE `consumo_material`
  ADD PRIMARY KEY (`id_consumo_material`),
  ADD KEY `id_materia_prima` (`id_materia_prima`),
  ADD KEY `id_produccion` (`id_produccion`);

--
-- Indices de la tabla `cuenta_empresa`
--
ALTER TABLE `cuenta_empresa`
  ADD PRIMARY KEY (`id_cuenta`),
  ADD KEY `id_metodo_de_pago` (`id_metodo_de_pago`);

--
-- Indices de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD PRIMARY KEY (`id_detalle_pedido`),
  ADD KEY `id_producto_caracteristica` (`id_producto_caracteristica`),
  ADD KEY `id_pedido` (`id_pedido`),
  ADD KEY `id_servicio` (`id_servicio`);

--
-- Indices de la tabla `empleado`
--
ALTER TABLE `empleado`
  ADD PRIMARY KEY (`id_empleado`),
  ADD UNIQUE KEY `cedula` (`cedula`);

--
-- Indices de la tabla `materia_prima`
--
ALTER TABLE `materia_prima`
  ADD PRIMARY KEY (`id_materia_prima`),
  ADD KEY `id_tipo_materia_prima` (`id_tipo_materia_prima`);

--
-- Indices de la tabla `metodo_de_pago`
--
ALTER TABLE `metodo_de_pago`
  ADD PRIMARY KEY (`id_metodo_de_pago`);

--
-- Indices de la tabla `orden_de_produccion`
--
ALTER TABLE `orden_de_produccion`
  ADD PRIMARY KEY (`id_produccion`),
  ADD KEY `id_detalle_pedido` (`id_detalle_pedido`);

--
-- Indices de la tabla `pago`
--
ALTER TABLE `pago`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `id_pedido` (`id_pedido`),
  ADD KEY `id_metodo_de_pago` (`id_metodo_de_pago`);

--
-- Indices de la tabla `pedido`
--
ALTER TABLE `pedido`
  ADD PRIMARY KEY (`id_pedido`),
  ADD KEY `id_tipo_pedido` (`id_tipo_pedido`),
  ADD KEY `id_cliente` (`id_cliente`);

--
-- Indices de la tabla `perdida_material`
--
ALTER TABLE `perdida_material`
  ADD PRIMARY KEY (`id_perdida`),
  ADD KEY `id_consumo_material` (`id_consumo_material`);

--
-- Indices de la tabla `permiso`
--
ALTER TABLE `permiso`
  ADD PRIMARY KEY (`id_permiso`);

--
-- Indices de la tabla `permisos_rol`
--
ALTER TABLE `permisos_rol`
  ADD PRIMARY KEY (`id_permiso_rol`),
  ADD KEY `id_rol` (`id_rol`),
  ADD KEY `id_permiso` (`id_permiso`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`id_producto`);

--
-- Indices de la tabla `producto_caracteristica`
--
ALTER TABLE `producto_caracteristica`
  ADD PRIMARY KEY (`id_producto_caracteristica`),
  ADD KEY `id_producto` (`id_producto`),
  ADD KEY `id_caracteristica` (`id_caracteristica`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `servicio`
--
ALTER TABLE `servicio`
  ADD PRIMARY KEY (`id_servicio`);

--
-- Indices de la tabla `tipo_de_materia_prima`
--
ALTER TABLE `tipo_de_materia_prima`
  ADD PRIMARY KEY (`id_tipo_materia_prima`);

--
-- Indices de la tabla `tipo_de_pedido`
--
ALTER TABLE `tipo_de_pedido`
  ADD PRIMARY KEY (`id_tipo_pedido`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `id_rol` (`id_rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `acceso_empleado`
--
ALTER TABLE `acceso_empleado`
  MODIFY `id_acceso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `asignacion_produccion`
--
ALTER TABLE `asignacion_produccion`
  MODIFY `id_asignacion_produccion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `caracteristica`
--
ALTER TABLE `caracteristica`
  MODIFY `id_caracteristica` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `consumo_material`
--
ALTER TABLE `consumo_material`
  MODIFY `id_consumo_material` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `cuenta_empresa`
--
ALTER TABLE `cuenta_empresa`
  MODIFY `id_cuenta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  MODIFY `id_detalle_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT de la tabla `empleado`
--
ALTER TABLE `empleado`
  MODIFY `id_empleado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `materia_prima`
--
ALTER TABLE `materia_prima`
  MODIFY `id_materia_prima` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `metodo_de_pago`
--
ALTER TABLE `metodo_de_pago`
  MODIFY `id_metodo_de_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `orden_de_produccion`
--
ALTER TABLE `orden_de_produccion`
  MODIFY `id_produccion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `pago`
--
ALTER TABLE `pago`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `pedido`
--
ALTER TABLE `pedido`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `perdida_material`
--
ALTER TABLE `perdida_material`
  MODIFY `id_perdida` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `permiso`
--
ALTER TABLE `permiso`
  MODIFY `id_permiso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `permisos_rol`
--
ALTER TABLE `permisos_rol`
  MODIFY `id_permiso_rol` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `producto_caracteristica`
--
ALTER TABLE `producto_caracteristica`
  MODIFY `id_producto_caracteristica` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `servicio`
--
ALTER TABLE `servicio`
  MODIFY `id_servicio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `tipo_de_materia_prima`
--
ALTER TABLE `tipo_de_materia_prima`
  MODIFY `id_tipo_materia_prima` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `tipo_de_pedido`
--
ALTER TABLE `tipo_de_pedido`
  MODIFY `id_tipo_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `acceso_empleado`
--
ALTER TABLE `acceso_empleado`
  ADD CONSTRAINT `acceso_empleado_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `acceso_empleado_ibfk_2` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`) ON DELETE CASCADE;

--
-- Filtros para la tabla `asignacion_produccion`
--
ALTER TABLE `asignacion_produccion`
  ADD CONSTRAINT `asignacion_produccion_ibfk_1` FOREIGN KEY (`id_produccion`) REFERENCES `orden_de_produccion` (`id_produccion`) ON DELETE CASCADE,
  ADD CONSTRAINT `asignacion_produccion_ibfk_2` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`) ON DELETE CASCADE;

--
-- Filtros para la tabla `consumo_material`
--
ALTER TABLE `consumo_material`
  ADD CONSTRAINT `consumo_material_ibfk_1` FOREIGN KEY (`id_materia_prima`) REFERENCES `materia_prima` (`id_materia_prima`) ON DELETE CASCADE,
  ADD CONSTRAINT `consumo_material_ibfk_2` FOREIGN KEY (`id_produccion`) REFERENCES `orden_de_produccion` (`id_produccion`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cuenta_empresa`
--
ALTER TABLE `cuenta_empresa`
  ADD CONSTRAINT `cuenta_empresa_ibfk_1` FOREIGN KEY (`id_metodo_de_pago`) REFERENCES `metodo_de_pago` (`id_metodo_de_pago`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD CONSTRAINT `detalle_pedido_ibfk_1` FOREIGN KEY (`id_producto_caracteristica`) REFERENCES `producto_caracteristica` (`id_producto_caracteristica`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalle_pedido_ibfk_2` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalle_pedido_ibfk_3` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`) ON DELETE CASCADE;

--
-- Filtros para la tabla `materia_prima`
--
ALTER TABLE `materia_prima`
  ADD CONSTRAINT `materia_prima_ibfk_1` FOREIGN KEY (`id_tipo_materia_prima`) REFERENCES `tipo_de_materia_prima` (`id_tipo_materia_prima`) ON DELETE CASCADE;

--
-- Filtros para la tabla `orden_de_produccion`
--
ALTER TABLE `orden_de_produccion`
  ADD CONSTRAINT `orden_de_produccion_ibfk_1` FOREIGN KEY (`id_detalle_pedido`) REFERENCES `detalle_pedido` (`id_detalle_pedido`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pago`
--
ALTER TABLE `pago`
  ADD CONSTRAINT `pago_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE,
  ADD CONSTRAINT `pago_ibfk_2` FOREIGN KEY (`id_metodo_de_pago`) REFERENCES `metodo_de_pago` (`id_metodo_de_pago`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pedido`
--
ALTER TABLE `pedido`
  ADD CONSTRAINT `pedido_ibfk_1` FOREIGN KEY (`id_tipo_pedido`) REFERENCES `tipo_de_pedido` (`id_tipo_pedido`) ON DELETE CASCADE,
  ADD CONSTRAINT `pedido_ibfk_2` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE CASCADE;

--
-- Filtros para la tabla `perdida_material`
--
ALTER TABLE `perdida_material`
  ADD CONSTRAINT `perdida_material_ibfk_1` FOREIGN KEY (`id_consumo_material`) REFERENCES `consumo_material` (`id_consumo_material`) ON DELETE CASCADE;

--
-- Filtros para la tabla `permisos_rol`
--
ALTER TABLE `permisos_rol`
  ADD CONSTRAINT `permisos_rol_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE CASCADE,
  ADD CONSTRAINT `permisos_rol_ibfk_2` FOREIGN KEY (`id_permiso`) REFERENCES `permiso` (`id_permiso`) ON DELETE CASCADE;

--
-- Filtros para la tabla `producto_caracteristica`
--
ALTER TABLE `producto_caracteristica`
  ADD CONSTRAINT `producto_caracteristica_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE CASCADE,
  ADD CONSTRAINT `producto_caracteristica_ibfk_2` FOREIGN KEY (`id_caracteristica`) REFERENCES `caracteristica` (`id_caracteristica`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
