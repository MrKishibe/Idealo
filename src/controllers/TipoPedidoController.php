<?php

namespace Idealo\Controllers;

class TipoPedidoController
{
    /**
     * Lista los tipos de pedido harcodeados y carga la vista
     */
    public function listar(): void
    {
        // DATOS HARDCODEADOS: Modifica o añade más elementos aquí si lo deseas
        $tiposPedido = [
            [
                'id_tipo_pedido' => 1,
                'nombre_tipo_pedido' => 'Patrocinio',
                'status_tipo_servicio' => 'Activo'
            ],
            [
                'id_tipo_pedido' => 2,
                'nombre_tipo_pedido' => 'Alquiler de maquinaria',
                'status_tipo_servicio' => 'Activo'
            ],
            [
                'id_tipo_pedido' => 3,
                'nombre_tipo_pedido' => 'Pedido natural',
                'status_tipo_servicio' => 'Activo'
            ],
            [
                'id_tipo_pedido' => 4,
                'nombre_tipo_pedido' => 'Sublimación de Tazas Mágicas',
                'status_tipo_servicio' => 'Inactivo'
            ]
        ];
        
        // RUTA RELATIVA CORREGIDA: Sube un nivel desde src/controllers/ y entra a src/view/
        $rutaVista = __DIR__ . '/../view/tipo_pedido/listar.php';

        if (file_exists($rutaVista)) {
            require_once $rutaVista;
        } else {
            die("Error crítico: No se encontró el archivo de la vista en: " . $rutaVista);
        }
    }

    // Métodos vacíos para evitar que el FrontController rompa si se invocan las rutas de los formularios
    public function guardar(): void { $this->redirigir(); }
    public function editar(): void { $this->redirigir(); }
    public function eliminar(): void { $this->redirigir(); }

    private function redirigir(): void {
        header('Location: index.php?controller=tipoPedido&action=listar');
        exit;
    }
}