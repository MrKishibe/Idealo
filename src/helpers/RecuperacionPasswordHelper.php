<?php

namespace Idealo\Helpers;

class RecuperacionPasswordHelper
{
    public static function generarCodigo(): string
    {
        return str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    public static function generarCodigoHash(string $codigo): string
    {
        return password_hash($codigo, PASSWORD_DEFAULT);
    }

    public static function verificarCodigo(string $codigoIngresado, string $codigoHash): bool
    {
        if (empty($codigoHash)) {
            return false;
        }

        return password_verify($codigoIngresado, $codigoHash);
    }

    public static function generarToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function generarTokenHash(string $token): string
    {
        return password_hash($token, PASSWORD_DEFAULT);
    }

    public static function verificarToken(string $tokenIngresado, string $tokenHash): bool
    {
        if (empty($tokenHash)) {
            return false;
        }

        return password_verify($tokenIngresado, $tokenHash);
    }

    public static function limpiarSesionRecuperacion(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        unset($_SESSION['recuperacion_codigo_hash']);
        unset($_SESSION['recuperacion_token_hash']);
        unset($_SESSION['recuperacion_token']);
        unset($_SESSION['recuperacion_codigo']);
        unset($_SESSION['recuperacion_correo']);
        unset($_SESSION['recuperacion_id_usuario']);
        unset($_SESSION['recuperacion_expira']);
        unset($_SESSION['recuperacion_usado']);
        unset($_SESSION['recuperacion_verificado']);
    }
}










