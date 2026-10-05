<?php

namespace Idealo\Models;

use Exception;

class RecuperacionPasswordModel
{
    public function generarCodigo(): string
    {
        return str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function generarToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}