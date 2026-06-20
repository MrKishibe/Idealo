<?php

namespace Idealo\Controllers;

class PedidoController
{
    /**
     * Carga y procesa la lista de pedidos de sublimación y costura.
     */
    public function listar(): void
    {
        // Simulación de datos estructurados provenientes del Modelo
        $pedidos = [
            [
                'id_pedido' => 101,
                'fecha_creacion' => '2026-06-10',
                'fecha_entrega' => '2026-06-25',
                'id_tipo_pedido' => 'Sublimación',
                'descripcion' => 'Lote de 50 tazas corporativas color mate.',
                'estado_pedido' => 'pendiente',
                'descuento_divisa' => 15.50,
                'monto_total' => 284.50,
                'id_cliente' => 'C-847291 (Inversiones Alpa C.A)'
            ],
            [
                'id_pedido' => 102,
                'fecha_creacion' => '2026-06-14',
                'fecha_entrega' => '2026-06-18',
                'id_tipo_pedido' => 'Bordado',
                'descripcion' => '10 Gorras estructuradas con relieve frontal.',
                'estado_pedido' => 'completado',
                'descuento_divisa' => 0.00,
                'monto_total' => 120.00,
                'id_cliente' => 'C-104922 (Carlos Mendoza)'
            ],
            [
                'id_pedido' => 103,
                'fecha_creacion' => '2026-06-16',
                'fecha_entrega' => '2026-06-17',
                'id_tipo_pedido' => 'Estampado',
                'descripcion' => 'Impresión textil DTF para 5 franelas promocionales.',
                'estado_pedido' => 'cancelado',
                'descuento_divisa' => 5.00,
                'monto_total' => 45.00,
                'id_cliente' => 'C-302911 (Studio Creativo S.A.)'
            ]
        ];

        // RUTA CORREGIDA: Sube un nivel desde src/controllers/ y entra a src/view/pedido/listar.php
        require_once __DIR__ . '/../view/pedido/listar.php';
    }

    /**
     * Procesa la inserción de un nuevo registro de pedido
     */
    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Lógica de sanitización y guardado
            // $this->modelo->insertar($_POST);
            
            header('Location: index.php?controller=pedido&action=listar');
            exit;
        }
    }

    /**
     * Procesa la edición o actualización de estados del pedido
     */
    public function editar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Lógica de actualización
            // $this->modelo->actualizar($_POST['id_pedido'], $_POST);
            
            header('Location: index.php?controller=pedido&action=listar');
            exit;
        }
    }
}