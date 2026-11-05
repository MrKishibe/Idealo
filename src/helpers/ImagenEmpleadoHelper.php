<?php

namespace Idealo\Helpers;

use Exception;

/**
 * Guarda y elimina las fotos de los empleados.
 * La foto es opcional: si no se recibe archivo simplemente se devuelve null.
 */
class ImagenEmpleadoHelper
{
    private static $carpetaEmpleados = 'assets/Img/Empleados/';
    private static $tamanoMaximo = 2097152; // 2 MB

    /**
     * Valida y guarda la imagen recibida.
     * Devuelve la ruta pública o null cuando no se envió archivo.
     */
    public static function guardar(?array $archivo): ?string
    {
        if (empty($archivo) || intval($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $erroresSubida = [
            UPLOAD_ERR_INI_SIZE   => 'La imagen supera el tamaño permitido por el servidor.',
            UPLOAD_ERR_FORM_SIZE  => 'La imagen supera el tamaño permitido.',
            UPLOAD_ERR_PARTIAL    => 'La imagen se subió de forma incompleta. Inténtalo de nuevo.',
            UPLOAD_ERR_NO_TMP_DIR => 'No se pudo procesar la imagen en el servidor.',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo guardar la imagen en el servidor.'
        ];

        $error = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error !== UPLOAD_ERR_OK) {
            throw new Exception($erroresSubida[$error] ?? 'No se pudo subir la imagen.');
        }

        if (intval($archivo['size'] ?? 0) <= 0 || intval($archivo['size']) > self::$tamanoMaximo) {
            throw new Exception('La imagen no debe superar los 2 MB.');
        }

        $info = @getimagesize($archivo['tmp_name'] ?? '');
        $tiposPermitidos = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG  => 'png',
            IMAGETYPE_WEBP => 'webp'
        ];

        if (!$info || !isset($tiposPermitidos[$info[2]])) {
            throw new Exception('Formato no permitido. Usa una imagen JPG, PNG o WEBP.');
        }

        $directorio = self::carpetaFisica();
        if (!is_dir($directorio) && !mkdir($directorio, 0775, true) && !is_dir($directorio)) {
            throw new Exception('No se pudo crear el directorio de fotos de empleados.');
        }

        $nombreArchivo = 'empleado_' . time() . '_' . bin2hex(random_bytes(4))
            . '.' . $tiposPermitidos[$info[2]];
        $rutaDestino = $directorio . '/' . $nombreArchivo;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            throw new Exception('No se pudo guardar la imagen.');
        }

        return self::$carpetaEmpleados . $nombreArchivo;
    }

    /**
     * Elimina una foto anterior solo si está dentro de la carpeta de empleados.
     */
    public static function eliminar(?string $ruta): void
    {
        if (empty($ruta) || strpos($ruta, self::$carpetaEmpleados) !== 0) {
            return;
        }

        $rutaFisica = self::raizProyecto() . '/' . self::$carpetaEmpleados . basename($ruta);
        if (is_file($rutaFisica)) {
            @unlink($rutaFisica);
        }
    }

    private static function raizProyecto(): string
    {
        return realpath(__DIR__ . '/../..') ?: dirname(__DIR__, 2);
    }

    private static function carpetaFisica(): string
    {
        return self::raizProyecto() . '/' . rtrim(self::$carpetaEmpleados, '/');
    }
}
