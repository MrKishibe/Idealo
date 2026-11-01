<?php

namespace Idealo\Helpers;

require_once __DIR__ . '/../models/UsuarioModel.php';

use Idealo\Models\UsuarioModel;
use Exception;
use Throwable;

trait ImagenPerfilTrait
{
    private static $carpetaPerfiles = 'assets/Img/Perfiles/';
    private static $tamanoMaximo = 2097152; // 2 MB

    /**
     * Valida, guarda y registra la foto de perfil del usuario.
     * Devuelve ['success', 'message', 'url'].
     */
    public static function subirFotoPerfil(int $idUsuario, ?array $archivo): array
    {
        if ($idUsuario <= 0) {
            throw new Exception('Debes iniciar sesión para cambiar tu foto de perfil.');
        }

        if (empty($archivo)) {
            throw new Exception('Selecciona una imagen para tu perfil.');
        }

        $erroresSubida = [
            UPLOAD_ERR_INI_SIZE   => 'La imagen supera el tamaño permitido por el servidor.',
            UPLOAD_ERR_FORM_SIZE  => 'La imagen supera el tamaño permitido.',
            UPLOAD_ERR_PARTIAL    => 'La imagen se subió de forma incompleta. Inténtalo de nuevo.',
            UPLOAD_ERR_NO_FILE    => 'Selecciona una imagen para tu perfil.',
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
            throw new Exception('No se pudo crear el directorio de fotos de perfil.');
        }

        $nombreArchivo = 'perfil_' . $idUsuario . '_' . time() . '_' . bin2hex(random_bytes(4))
            . '.' . $tiposPermitidos[$info[2]];
        $rutaDestino = $directorio . '/' . $nombreArchivo;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            throw new Exception('No se pudo guardar la imagen.');
        }

        $rutaPublica = self::$carpetaPerfiles . $nombreArchivo;
        $usuarioModel = new UsuarioModel();
        $imagenAnterior = $usuarioModel->obtenerImagen($idUsuario);

        try {
            $resultado = $usuarioModel->actualizarImagen($idUsuario, $rutaPublica);
        } catch (Throwable $e) {
            @unlink($rutaDestino);
            throw $e;
        }

        if (empty($resultado['success'])) {
            @unlink($rutaDestino);
        } elseif ($imagenAnterior && $imagenAnterior !== $rutaPublica
            && strpos($imagenAnterior, self::$carpetaPerfiles) === 0) {
            $rutaAnterior = self::raizProyecto() . '/' . self::$carpetaPerfiles . basename($imagenAnterior);
            if (is_file($rutaAnterior)) {
                @unlink($rutaAnterior);
            }
        }

        return [
            'success' => true,
            'message' => $resultado['message'] ?? 'Foto de perfil actualizada con éxito.',
            'url'     => $rutaPublica
        ];
    }

    private static function raizProyecto(): string
    {
        return realpath(__DIR__ . '/../..') ?: dirname(__DIR__, 2);
    }

    private static function carpetaFisica(): string
    {
        return self::raizProyecto() . '/' . rtrim(self::$carpetaPerfiles, '/');
    }
}

class ImagenPerfilHelper
{
    use ImagenPerfilTrait;
}
