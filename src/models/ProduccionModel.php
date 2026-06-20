<?php

namespace Idealo\Models;

class ProduccionModel
{
    /**
     * Retorna un listado de órdenes de producción hardcodeadas
     */
    public function listarOrdenes()
    {
        return [
            [
                'id_produccion'     => 1,
                'fecha_de_inicio'   => '2026-05-26',
                'fecha_terminado'   => '2026-05-28',
                'monto_total'       => 450.00,
                'estado_de_pedido'  => 'Entregado',
                'estado'            => 'Finalizado',
                'id_detalle_pedido' => 101
            ],
            [
                'id_produccion'     => 2,
                'fecha_de_inicio'   => '2026-05-29',
                'fecha_terminado'   => '2026-05-30',
                'monto_total'       => 1200.50,
                'estado_de_pedido'  => 'En espera',
                'estado'            => 'En Proceso',
                'id_detalle_pedido' => 102
            ],
            [
                'id_produccion'     => 3,
                'fecha_de_inicio'   => '2026-06-01',
                'fecha_terminado'   => '—',
                'monto_total'       => 320.00,
                'estado_de_pedido'  => 'En espera',
                'estado'            => 'Planificado',
                'id_detalle_pedido' => 103
            ]
        ];
    }
}