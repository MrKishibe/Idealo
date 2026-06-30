-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 19-06-2026 a las 23:12:34
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
-- Estructura de tabla para la tabla `asignacion_produccion`
--

CREATE TABLE `asignacion_produccion` (
  `id_asignacion_produccion` int(11) NOT NULL,
  `id_produccion` int(11) NOT NULL,
  `id_empleado` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consumo_material`
--

CREATE TABLE `consumo_material` (
  `id_consumo_material` int(11) NOT NULL,
  `costo_unitaro` decimal(10,2) NOT NULL,
  `descripcion_de_consumo` text DEFAULT NULL,
  `cantidad_usada` int(11) NOT NULL,
  `id_materia_prima` int(11) NOT NULL,
  `id_produccion` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

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
(1, 2, 'Ahorro', '01020987654323456789', 'Idealo sisas', 'activo');

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
  `status_empleado` varchar(20) NOT NULL DEFAULT 'activo',
  `id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

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
(1, 'Pago Movil', NULL, 'activo'),
(2, 'Transferencia Bancaria', NULL, 'activo');

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
  `id_pedido` int(11) NOT NULL,
  `id_metodo_de_pago` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `perdida_material`
--

CREATE TABLE `perdida_material` (
  `id_perdida` int(11) NOT NULL,
  `cantidad_perdida` int(11) NOT NULL,
  `fecha_de_registro` date NOT NULL,
  `motivo` text DEFAULT NULL,
  `costo_unitario` decimal(10,2) NOT NULL,
  `id_produccion` int(11) NOT NULL
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
(2, 'Franela de Algodon', 'Ropa', 'activo');

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
(2, 'administrador', 'activo'),
(3, 'contador', 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicio`
--

CREATE TABLE `servicio` (
  `id_servicio` int(11) NOT NULL,
  `nombre_servicio` varchar(100) NOT NULL,
  `status_servicio` varchar(20) NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

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
(1, 'taza', 'taza en blanco', 'Activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_de_pedido`
--

CREATE TABLE `tipo_de_pedido` (
  `id_tipo_pedido` int(11) NOT NULL,
  `nombre_tipo_pedido` varchar(100) NOT NULL,
  `status_tipo_servicio` varchar(20) NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `cedula_usuario` varchar(20) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `status_usuario` varchar(20) NOT NULL DEFAULT 'activo',
  `id_rol` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `cedula_usuario`, `contrasena`, `status_usuario`, `id_rol`) VALUES
(1, '30233554', '$2y$10$NEw.BFkGs9q8MVZaXe94gOnUfODWg9sSkJyF3.YYweQZXsNmidfw.', 'activo', 1),
(2, '31973792', '$2y$10$NEw.BFkGs9q8MVZaXe94gOnUfODWg9sSkJyF3.YYweQZXsNmidfw.', 'activo', 1),
(3, '30601666', '$2y$10$NEw.BFkGs9q8MVZaXe94gOnUfODWg9sSkJyF3.YYweQZXsNmidfw.', 'activo', 1),
(4, '30591032', '$2y$10$NEw.BFkGs9q8MVZaXe94gOnUfODWg9sSkJyF3.YYweQZXsNmidfw.', 'activo', 1),
(5, '30529022', '$2y$10$NEw.BFkGs9q8MVZaXe94gOnUfODWg9sSkJyF3.YYweQZXsNmidfw.', 'activo', 1);

--
-- Índices para tablas volcadas
--

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
  ADD UNIQUE KEY `numero_de_documento` (`numero_de_documento`);

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
  ADD UNIQUE KEY `cedula` (`cedula`),
  ADD KEY `id_usuario` (`id_usuario`);

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
  ADD KEY `id_produccion` (`id_produccion`);

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
  ADD UNIQUE KEY `cedula_usuario` (`cedula_usuario`),
  ADD KEY `id_rol` (`id_rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asignacion_produccion`
--
ALTER TABLE `asignacion_produccion`
  MODIFY `id_asignacion_produccion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `caracteristica`
--
ALTER TABLE `caracteristica`
  MODIFY `id_caracteristica` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `consumo_material`
--
ALTER TABLE `consumo_material`
  MODIFY `id_consumo_material` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cuenta_empresa`
--
ALTER TABLE `cuenta_empresa`
  MODIFY `id_cuenta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  MODIFY `id_detalle_pedido` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `empleado`
--
ALTER TABLE `empleado`
  MODIFY `id_empleado` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `materia_prima`
--
ALTER TABLE `materia_prima`
  MODIFY `id_materia_prima` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metodo_de_pago`
--
ALTER TABLE `metodo_de_pago`
  MODIFY `id_metodo_de_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `orden_de_produccion`
--
ALTER TABLE `orden_de_produccion`
  MODIFY `id_produccion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pago`
--
ALTER TABLE `pago`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedido`
--
ALTER TABLE `pedido`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `perdida_material`
--
ALTER TABLE `perdida_material`
  MODIFY `id_perdida` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `permiso`
--
ALTER TABLE `permiso`
  MODIFY `id_permiso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `permisos_rol`
--
ALTER TABLE `permisos_rol`
  MODIFY `id_permiso_rol` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `producto_caracteristica`
--
ALTER TABLE `producto_caracteristica`
  MODIFY `id_producto_caracteristica` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `servicio`
--
ALTER TABLE `servicio`
  MODIFY `id_servicio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipo_de_materia_prima`
--
ALTER TABLE `tipo_de_materia_prima`
  MODIFY `id_tipo_materia_prima` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `tipo_de_pedido`
--
ALTER TABLE `tipo_de_pedido`
  MODIFY `id_tipo_pedido` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asignacion_produccion`
--
ALTER TABLE `asignacion_produccion`
  ADD CONSTRAINT `asignacion_produccion_ibfk_1` FOREIGN KEY (`id_produccion`) REFERENCES `orden_de_produccion` (`id_produccion`),
  ADD CONSTRAINT `asignacion_produccion_ibfk_2` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`);

--
-- Filtros para la tabla `consumo_material`
--
ALTER TABLE `consumo_material`
  ADD CONSTRAINT `consumo_material_ibfk_1` FOREIGN KEY (`id_materia_prima`) REFERENCES `materia_prima` (`id_materia_prima`),
  ADD CONSTRAINT `consumo_material_ibfk_2` FOREIGN KEY (`id_produccion`) REFERENCES `orden_de_produccion` (`id_produccion`);

--
-- Filtros para la tabla `cuenta_empresa`
--
ALTER TABLE `cuenta_empresa`
  ADD CONSTRAINT `cuenta_empresa_ibfk_1` FOREIGN KEY (`id_metodo_de_pago`) REFERENCES `metodo_de_pago` (`id_metodo_de_pago`);

--
-- Filtros para la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD CONSTRAINT `detalle_pedido_ibfk_1` FOREIGN KEY (`id_producto_caracteristica`) REFERENCES `producto_caracteristica` (`id_producto_caracteristica`),
  ADD CONSTRAINT `detalle_pedido_ibfk_2` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`),
  ADD CONSTRAINT `detalle_pedido_ibfk_3` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`);

--
-- Filtros para la tabla `empleado`
--
ALTER TABLE `empleado`
  ADD CONSTRAINT `empleado_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);

--
-- Filtros para la tabla `materia_prima`
--
ALTER TABLE `materia_prima`
  ADD CONSTRAINT `materia_prima_ibfk_1` FOREIGN KEY (`id_tipo_materia_prima`) REFERENCES `tipo_de_materia_prima` (`id_tipo_materia_prima`);

--
-- Filtros para la tabla `orden_de_produccion`
--
ALTER TABLE `orden_de_produccion`
  ADD CONSTRAINT `orden_de_produccion_ibfk_1` FOREIGN KEY (`id_detalle_pedido`) REFERENCES `detalle_pedido` (`id_detalle_pedido`);

--
-- Filtros para la tabla `pago`
--
ALTER TABLE `pago`
  ADD CONSTRAINT `pago_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`),
  ADD CONSTRAINT `pago_ibfk_2` FOREIGN KEY (`id_metodo_de_pago`) REFERENCES `metodo_de_pago` (`id_metodo_de_pago`);

--
-- Filtros para la tabla `pedido`
--
ALTER TABLE `pedido`
  ADD CONSTRAINT `pedido_ibfk_1` FOREIGN KEY (`id_tipo_pedido`) REFERENCES `tipo_de_pedido` (`id_tipo_pedido`),
  ADD CONSTRAINT `pedido_ibfk_2` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`);

--
-- Filtros para la tabla `perdida_material`
--
ALTER TABLE `perdida_material`
  ADD CONSTRAINT `perdida_material_ibfk_1` FOREIGN KEY (`id_produccion`) REFERENCES `orden_de_produccion` (`id_produccion`);

--
-- Filtros para la tabla `permisos_rol`
--
ALTER TABLE `permisos_rol`
  ADD CONSTRAINT `permisos_rol_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`),
  ADD CONSTRAINT `permisos_rol_ibfk_2` FOREIGN KEY (`id_permiso`) REFERENCES `permiso` (`id_permiso`);

--
-- Filtros para la tabla `producto_caracteristica`
--
ALTER TABLE `producto_caracteristica`
  ADD CONSTRAINT `producto_caracteristica_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`),
  ADD CONSTRAINT `producto_caracteristica_ibfk_2` FOREIGN KEY (`id_caracteristica`) REFERENCES `caracteristica` (`id_caracteristica`);

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
