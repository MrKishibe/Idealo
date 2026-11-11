<?php

namespace Idealo\Helpers;

use Idealo\Models\UsuarioModel;
use Exception;
use Throwable;

/**
 * Guarda y elimina imágenes de perfil de usuarios y fotos de empleados.
 *
 * Es un trait para reutilizar la misma validación y almacenamiento desde los
 * modelos, sin depender de una clase helper adicional.
 */
trait ImagenPerfilHelper
{
    private static $carpetaPerfiles = 'assets/Img/Perfiles/';
    private static $carpetaEmpleados = 'assets/Img/Empleados/';
    private static $tamanoMaximo = 2097152; // 2 MB

    /* ======================================================================
       Empleados
       ====================================================================== */

    /**
     * Valida y guarda la foto del empleado.
     * Devuelve la ruta pública o null cuando no se envió archivo.
     */
    public static function guardarEmpleado(?array $archivo): ?string
    {
        return self::guardarArchivo($archivo, self::$carpetaEmpleados, 'empleado_');
    }

    /**
     * Elimina una foto de empleado solo si está dentro de su carpeta.
     */
    public static function eliminarEmpleado(?string $ruta): void
    {
        self::eliminarArchivo($ruta, self::$carpetaEmpleados);
    }

    /* ======================================================================
       Perfiles de usuario
       ====================================================================== */

    /**
     * Valida y guarda una foto de perfil sin tocar la base de datos.
     * Devuelve la ruta pública o null cuando no se envió archivo.
     */
    public static function guardarPerfil(?array $archivo): ?string
    {
        return self::guardarArchivo($archivo, self::$carpetaPerfiles, 'perfil_');
    }

    /**
     * Elimina una foto de perfil solo si está dentro de su carpeta.
     */
    public static function eliminarPerfil(?string $ruta): void
    {
        self::eliminarArchivo($ruta, self::$carpetaPerfiles);
    }

    /**
     * Valida, guarda y registra la foto de perfil del usuario.
     * Devuelve ['success', 'message', 'url'].
     */
    public static function subirFotoPerfil(int $idUsuario, ?array $archivo): array
    {
        if ($idUsuario <= 0) {
            throw new Exception('Debes iniciar sesión para cambiar tu foto de perfil.');
        }

        if (empty($archivo) || intval($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new Exception('Selecciona una imagen para tu perfil.');
        }

        $rutaPublica = self::guardarArchivo(
            $archivo,
            self::$carpetaPerfiles,
            'perfil_' . $idUsuario . '_',
            true
        );

        if (!$rutaPublica) {
            throw new Exception('Selecciona una imagen para tu perfil.');
        }

        $usuarioModel = new UsuarioModel();
        $imagenAnterior = $usuarioModel->obtenerImagen($idUsuario);

        try {
            $resultado = $usuarioModel->actualizarImagen($idUsuario, $rutaPublica);

            if (empty($resultado['success'])) {
                throw new Exception($resultado['message'] ?? 'No se pudo actualizar la foto de perfil.');
            }
        } catch (Throwable $e) {
            self::eliminarArchivo($rutaPublica, self::$carpetaPerfiles);
            throw $e;
        }

        if ($imagenAnterior && $imagenAnterior !== $rutaPublica) {
            self::eliminarArchivo($imagenAnterior, self::$carpetaPerfiles);
        }

        return [
            'success' => true,
            'message' => $resultado['message'] ?? 'Foto de perfil actualizada con éxito.',
            'url'     => $rutaPublica
        ];
    }

    /* ======================================================================
       Utilidades internas
       ====================================================================== */

    /**
     * Valida el archivo y lo mueve a la carpeta indicada.
     * Devuelve null si no se envió archivo y la ruta pública si se guardó.
     */
    private static function guardarArchivo(
        ?array $archivo,
        string $carpeta,
        string $prefijo,
        bool $obligatorio = false
    ): ?string {
        if (empty($archivo) || intval($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if ($obligatorio) {
                throw new Exception('Selecciona una imagen.');
            }

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

        $directorio = self::carpetaFisica($carpeta);
        if (!is_dir($directorio) && !mkdir($directorio, 0775, true) && !is_dir($directorio)) {
            throw new Exception('No se pudo crear el directorio de imágenes.');
        }

        $nombreArchivo = $prefijo . time() . '_' . bin2hex(random_bytes(4))
            . '.' . $tiposPermitidos[$info[2]];
        $rutaDestino = $directorio . '/' . $nombreArchivo;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            throw new Exception('No se pudo guardar la imagen.');
        }

        return $carpeta . $nombreArchivo;
    }

    /**
     * Elimina un archivo solo si la ruta pertenece a la carpeta indicada.
     */
    private static function eliminarArchivo(?string $ruta, string $carpeta): void
    {
        if (empty($ruta) || strpos($ruta, $carpeta) !== 0) {
            return;
        }

        $rutaFisica = self::raizProyecto() . '/' . $carpeta . basename($ruta);
        if (is_file($rutaFisica)) {
            @unlink($rutaFisica);
        }
    }

    private static function raizProyecto(): string
    {
        return realpath(__DIR__ . '/../..') ?: dirname(__DIR__, 2);
    }

    private static function carpetaFisica(string $carpeta): string
    {
        return self::raizProyecto() . '/' . rtrim($carpeta, '/');
    }
}
