<?php

namespace Idealo\Models;

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers/ImagenPerfilHelper.php';

use Idealo\Config\Database;
use Idealo\Helpers\ImagenPerfilHelper;
use PDOException;
use Exception;

class UsuarioModel extends Database
{
    use ImagenPerfilHelper;

    private $conex;

    public $expNombreUsuario = '/^[a-zA-Z0-9_]{3,20}$/';

    public function __construct()
    {
        $this->conex = parent::connect();
    }

    public function listarRoles(): array
    {
        try {
            $sql = "SELECT id_rol, tipo_de_usuario
                    FROM roles
                    WHERE status_roles = 'activo'
                    ORDER BY id_rol ASC";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $error) {
            error_log('Error al listar roles: ' . $error->getMessage());
            return [];
        }
    }

    public function listarTodos(): array
    {
        try {
            $sql = "SELECT u.id_usuario,
                           u.nombre_usuario,
                           u.correo,
                           u.imagen,
                           u.status_usuario,
                           u.id_rol,
                           r.tipo_de_usuario,
                           ae.id_empleado AS id_empleado_vinculado
                    FROM usuario u
                    LEFT JOIN roles r ON u.id_rol = r.id_rol
                    LEFT JOIN acceso_empleado ae ON ae.id_usuario = u.id_usuario
                    ORDER BY u.id_usuario DESC";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $error) {
            error_log('Error al listar usuarios: ' . $error->getMessage());
            return [];
        }
    }

    public function guardar(array $datos): array
    {
        $nombreUsuario = trim($datos['nombre_usuario'] ?? '');
        $contrasena    = $datos['contrasena'] ?? '';
        $correo        = trim($datos['correo'] ?? '');
        $idRol         = intval($datos['id_rol'] ?? 0);

        $this->validarNombreUsuario($nombreUsuario);
        $this->validarContrasena($contrasena);

        if (empty($correo)) {
            throw new Exception("[Validación] El correo electrónico es obligatorio.");
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("[Validación] El correo electrónico no es válido.");
        }

        if (!$this->rolExiste($idRol)) {
            throw new Exception("[Validación] El rol seleccionado no es válido.");
        }

        if ($this->existeNombreUsuario($nombreUsuario)) {
            throw new Exception("[Validación] El nombre de usuario '{$nombreUsuario}' ya está registrado.");
        }
        if ($this->existeCorreo($correo)) {
            throw new Exception("[Validación] El correo electrónico '{$correo}' ya está registrado.");
        }

        $hash = password_hash($contrasena, PASSWORD_BCRYPT);

        try {
            $sql = "INSERT INTO usuario (nombre_usuario, correo, contrasena, status_usuario, id_rol)
                    VALUES (:nombre_usuario, :correo, :contrasena, 'activo', :id_rol)";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([
                ':nombre_usuario' => $nombreUsuario,
                ':correo'         => $correo,
                ':contrasena'     => $hash,
                ':id_rol'         => $idRol
            ]);

            return [
                'success'    => true,
                'message'    => 'Usuario registrado con éxito.',
                'id_usuario' => (int)$this->conex->lastInsertId()
            ];
        } catch (PDOException $error) {
            error_log('Error al guardar usuario: ' . $error->getMessage());
            throw new Exception('No se pudo registrar el usuario. Intente nuevamente.');
        }
    }

    public function editar(array $datos): array
    {
        $idUsuario     = intval($datos['id_usuario'] ?? 0);
        $nombreUsuario = trim($datos['nombre_usuario'] ?? '');
        $contrasena    = $datos['contrasena'] ?? '';
        $correo        = trim($datos['correo'] ?? '');
        $idRol         = intval($datos['id_rol'] ?? 0);

        if ($idUsuario <= 0) {
            throw new Exception("[Validación] El ID del usuario es obligatorio para editar.");
        }

        $this->validarNombreUsuario($nombreUsuario);

        if (empty($correo)) {
            throw new Exception("[Validación] El correo electrónico es obligatorio.");
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("[Validación] El correo electrónico no es válido.");
        }

        if (!$this->rolExiste($idRol)) {
            throw new Exception("[Validación] El rol seleccionado no es válido.");
        }

        if ($this->existeNombreUsuario($nombreUsuario, $idUsuario)) {
            throw new Exception("[Validación] El nombre de usuario '{$nombreUsuario}' ya está registrado.");
        }
        if ($this->existeCorreo($correo, $idUsuario)) {
            throw new Exception("[Validación] El correo electrónico '{$correo}' ya está registrado.");
        }

        if (!empty($contrasena)) {
            $this->validarContrasena($contrasena);
            $hash = password_hash($contrasena, PASSWORD_BCRYPT);
            $sql = "UPDATE usuario
                    SET nombre_usuario = :nombre_usuario,
                        correo = :correo,
                        contrasena = :contrasena,
                        id_rol = :id_rol
                    WHERE id_usuario = :id_usuario";
            $params = [
                ':nombre_usuario' => $nombreUsuario,
                ':correo'         => $correo,
                ':contrasena'     => $hash,
                ':id_rol'         => $idRol,
                ':id_usuario'     => $idUsuario
            ];
        } else {
            $sql = "UPDATE usuario
                    SET nombre_usuario = :nombre_usuario,
                        correo = :correo,
                        id_rol = :id_rol
                    WHERE id_usuario = :id_usuario";
            $params = [
                ':nombre_usuario' => $nombreUsuario,
                ':correo'         => $correo,
                ':id_rol'         => $idRol,
                ':id_usuario'     => $idUsuario
            ];
        }

        try {
            $stmt = $this->conex->prepare($sql);
            $stmt->execute($params);

            return [
                'success' => true,
                'message' => 'Usuario actualizado con éxito.'
            ];
        } catch (PDOException $error) {
            error_log('Error al editar usuario: ' . $error->getMessage());
            throw new Exception('No se pudo actualizar el usuario. Intente nuevamente.');
        }
    }

    public function cambiarEstado(int $id, string $estado): array
    {
        if ($id <= 0) {
            throw new Exception("[Validación] El ID del usuario no es válido.");
        }

        $estado = strtolower(trim($estado));
        if (!in_array($estado, ['activo', 'inactivo'], true)) {
            throw new Exception("[Validación] El estado del usuario no es válido.");
        }

        try {
            $sql = "UPDATE usuario
                    SET status_usuario = :estado
                    WHERE id_usuario = :id_usuario";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([
                ':estado'      => $estado,
                ':id_usuario'  => $id
            ]);

            $mensaje = $estado === 'activo'
                ? 'Usuario activado con éxito.'
                : 'Usuario inactivado con éxito.';

            return [
                'success' => true,
                'message' => $mensaje
            ];
        } catch (PDOException $error) {
            error_log('Error al cambiar el estado del usuario: ' . $error->getMessage());
            throw new Exception('No se pudo cambiar el estado del usuario. Intente nuevamente.');
        }
    }

    public function obtenerPerfil(int $idUsuario): ?array
    {
        try {
            $sql = "SELECT u.id_usuario,
                           u.nombre_usuario,
                           u.correo,
                           u.imagen,
                           u.status_usuario,
                           u.id_rol,
                           r.tipo_de_usuario,
                           e.nombres,
                           e.apellidos,
                           e.cedula,
                           e.telefono,
                           e.cargo
                    FROM usuario u
                    LEFT JOIN roles r ON u.id_rol = r.id_rol
                    LEFT JOIN acceso_empleado ae ON ae.id_usuario = u.id_usuario
                    LEFT JOIN empleado e ON ae.id_empleado = e.id_empleado
                    WHERE u.id_usuario = :id_usuario
                    LIMIT 1";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([':id_usuario' => $idUsuario]);
            $perfil = $stmt->fetch();
            return $perfil ?: null;
        } catch (PDOException $error) {
            error_log('Error al obtener el perfil del usuario: ' . $error->getMessage());
            return null;
        }
    }

    public function cambiarContrasena(int $idUsuario, string $contrasenaActual, string $nuevaContrasena): array
    {
        try {
            $sql = "SELECT contrasena FROM usuario WHERE id_usuario = :id_usuario LIMIT 1";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([':id_usuario' => $idUsuario]);
            $fila = $stmt->fetch();

            if (!$fila) {
                throw new Exception("[Validación] El usuario no existe.");
            }

            if (!password_verify($contrasenaActual, $fila['contrasena'])) {
                throw new Exception("[Validación] La contraseña actual es incorrecta.");
            }

            $this->validarContrasena($nuevaContrasena);

            $hash = password_hash($nuevaContrasena, PASSWORD_BCRYPT);
            $sql = "UPDATE usuario
                    SET contrasena = :contrasena
                    WHERE id_usuario = :id_usuario";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([
                ':contrasena' => $hash,
                ':id_usuario' => $idUsuario
            ]);

            return [
                'success' => true,
                'message' => 'Contraseña actualizada con éxito.'
            ];
        } catch (PDOException $error) {
            error_log('Error al cambiar la contraseña: ' . $error->getMessage());
            throw new Exception('No se pudo cambiar la contraseña. Intente nuevamente.');
        }
    }

    public function cambiarContrasenaSimple(int $idUsuario, string $contrasena): bool
    {
        $this->validarContrasena($contrasena);
        $hash = password_hash($contrasena, PASSWORD_BCRYPT);
        try {
            $sql = "UPDATE usuario SET contrasena = :contrasena WHERE id_usuario = :id_usuario";
            $stmt = $this->conex->prepare($sql);
            return (bool)$stmt->execute([
                ':contrasena' => $hash,
                ':id_usuario' => $idUsuario
            ]);
        } catch (PDOException $error) {
            error_log('Error al cambiar la contraseña: ' . $error->getMessage());
            throw new Exception('No se pudo cambiar la contraseña. Intente nuevamente.');
        }
    }

    public function cambiarNombreUsuario(int $idUsuario, string $nuevoNombre): array
    {
        $nuevoNombre = trim($nuevoNombre);

        $this->validarNombreUsuario($nuevoNombre);

        if ($this->existeNombreUsuario($nuevoNombre, $idUsuario)) {
            throw new Exception("[Validación] El nombre de usuario '{$nuevoNombre}' ya está registrado.");
        }

        try {
            $sql = "UPDATE usuario
                    SET nombre_usuario = :nombre_usuario
                    WHERE id_usuario = :id_usuario";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([
                ':nombre_usuario' => $nuevoNombre,
                ':id_usuario'     => $idUsuario
            ]);

            return [
                'success' => true,
                'message' => 'Perfil actualizado con éxito.'
            ];
        } catch (PDOException $error) {
            error_log('Error al cambiar el nombre de usuario: ' . $error->getMessage());
            throw new Exception('No se pudo actualizar el perfil. Intente nuevamente.');
        }
    }

    /**
     * Actualiza nombre de usuario y correo desde el perfil del propio usuario.
     */
    public function cambiarDatosPerfil(int $idUsuario, string $nuevoNombre, string $nuevoCorreo): array
    {
        $nuevoNombre = trim($nuevoNombre);
        $nuevoCorreo = trim($nuevoCorreo);

        if ($idUsuario <= 0) {
            throw new Exception("[Validación] El ID del usuario es obligatorio.");
        }

        $this->validarNombreUsuario($nuevoNombre);

        if (empty($nuevoCorreo)) {
            throw new Exception("[Validación] El correo electrónico es obligatorio.");
        }
        if (!filter_var($nuevoCorreo, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("[Validación] El correo electrónico no es válido.");
        }
        if ($this->existeNombreUsuario($nuevoNombre, $idUsuario)) {
            throw new Exception("[Validación] El nombre de usuario '{$nuevoNombre}' ya está registrado.");
        }
        if ($this->existeCorreo($nuevoCorreo, $idUsuario)) {
            throw new Exception("[Validación] El correo electrónico '{$nuevoCorreo}' ya está registrado.");
        }

        try {
            $sql = "UPDATE usuario
                    SET nombre_usuario = :nombre_usuario,
                        correo = :correo
                    WHERE id_usuario = :id_usuario";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([
                ':nombre_usuario' => $nuevoNombre,
                ':correo'         => $nuevoCorreo,
                ':id_usuario'     => $idUsuario
            ]);

            return [
                'success' => true,
                'message' => 'Perfil actualizado con éxito.'
            ];
        } catch (PDOException $error) {
            error_log('Error al actualizar el perfil: ' . $error->getMessage());
            throw new Exception('No se pudo actualizar el perfil. Intente nuevamente.');
        }
    }

    /**
     * Devuelve la ruta de la foto de perfil actual (o null si no tiene).
     */
    public function obtenerImagen(int $idUsuario): ?string
    {
        if ($idUsuario <= 0) {
            return null;
        }

        try {
            $sql = "SELECT imagen FROM usuario WHERE id_usuario = :id_usuario LIMIT 1";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([':id_usuario' => $idUsuario]);
            $imagen = $stmt->fetchColumn();

            return $imagen !== false && trim((string) $imagen) !== '' ? trim((string) $imagen) : null;
        } catch (PDOException $error) {
            error_log('Error al obtener la imagen del usuario: ' . $error->getMessage());
            return null;
        }
    }

    /**
     * Guarda la ruta de la foto de perfil del usuario.
     */
    public function actualizarImagen(int $idUsuario, string $ruta): array
    {
        $ruta = trim($ruta);

        if ($idUsuario <= 0) {
            throw new Exception("[Validación] El ID del usuario no es válido.");
        }
        if ($ruta === '') {
            throw new Exception("[Validación] La ruta de la imagen no es válida.");
        }

        try {
            $sql = "UPDATE usuario
                    SET imagen = :imagen
                    WHERE id_usuario = :id_usuario";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([
                ':imagen'     => $ruta,
                ':id_usuario' => $idUsuario
            ]);

            return [
                'success' => true,
                'message' => 'Foto de perfil actualizada con éxito.',
                'imagen'  => $ruta
            ];
        } catch (PDOException $error) {
            error_log('Error al actualizar la imagen del usuario: ' . $error->getMessage());
            throw new Exception('No se pudo actualizar la foto de perfil. Intente nuevamente.');
        }
    }

    public function existeNombreUsuario(string $nombre, ?int $exceptuarId = null): bool
    {
        try {
            if ($exceptuarId !== null && $exceptuarId > 0) {
                $sql = "SELECT id_usuario
                        FROM usuario
                        WHERE nombre_usuario = :nombre
                          AND id_usuario <> :id
                        LIMIT 1";
                $stmt = $this->conex->prepare($sql);
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':id'     => $exceptuarId
                ]);
            } else {
                $sql = "SELECT id_usuario
                        FROM usuario
                        WHERE nombre_usuario = :nombre
                        LIMIT 1";
                $stmt = $this->conex->prepare($sql);
                $stmt->execute([':nombre' => $nombre]);
            }

            return (bool)$stmt->fetch();
        } catch (PDOException $error) {
            error_log('Error al verificar el nombre de usuario: ' . $error->getMessage());
            throw new Exception('No se pudo verificar el nombre de usuario. Intente nuevamente.');
        }
    }

    public function existeCorreo(string $correo, ?int $exceptuarId = null): bool
    {
        try {
            if ($exceptuarId !== null && $exceptuarId > 0) {
                $sql = "SELECT id_usuario
                        FROM usuario
                        WHERE correo = :correo
                          AND id_usuario <> :id
                        LIMIT 1";
                $stmt = $this->conex->prepare($sql);
                $stmt->execute([
                    ':correo' => $correo,
                    ':id'     => $exceptuarId
                ]);
            } else {
                $sql = "SELECT id_usuario
                        FROM usuario
                        WHERE correo = :correo
                        LIMIT 1";
                $stmt = $this->conex->prepare($sql);
                $stmt->execute([':correo' => $correo]);
            }

            return (bool)$stmt->fetch();
        } catch (PDOException $error) {
            error_log('Error al verificar el correo del usuario: ' . $error->getMessage());
            throw new Exception('No se pudo verificar el correo electrónico. Intente nuevamente.');
        }
    }

    public function obtenerUsuarioPorCorreo(string $correo): ?array
    {
        try {
            $sql = "SELECT id_usuario, nombre_usuario, correo, contrasena, status_usuario, id_rol
                    FROM usuario
                    WHERE correo = :correo AND status_usuario = 'activo'
                    LIMIT 1";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([':correo' => $correo]);
            $resultado = $stmt->fetch();
            return $resultado ?: null;
        } catch (PDOException $error) {
            error_log('Error al obtener el usuario por correo: ' . $error->getMessage());
            return null;
        }
    }

    public function obtenerPorCredencial(string $credencial): ?array
    {
        try {
            $sql = "SELECT u.id_usuario, u.nombre_usuario, u.correo, u.contrasena, u.status_usuario, u.id_rol, r.tipo_de_usuario
                    FROM usuario u
                    LEFT JOIN roles r ON u.id_rol = r.id_rol
                    WHERE (u.nombre_usuario = :credencial1 OR u.correo = :credencial2) AND u.status_usuario = 'activo'
                    LIMIT 1";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([':credencial1' => $credencial, ':credencial2' => $credencial]);
            $resultado = $stmt->fetch();
            return $resultado ?: null;
        } catch (PDOException $error) {
            error_log('Error al obtener el usuario por credencial: ' . $error->getMessage());
            return null;
        }
    }

    public function obtenerPorId(int $idUsuario): ?array
    {
        try {
            $sql = "SELECT u.id_usuario, u.nombre_usuario, u.correo, u.contrasena, u.status_usuario, u.id_rol, r.tipo_de_usuario
                    FROM usuario u
                    LEFT JOIN roles r ON u.id_rol = r.id_rol
                    WHERE u.id_usuario = :id_usuario
                    LIMIT 1";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([':id_usuario' => $idUsuario]);
            $resultado = $stmt->fetch();
            return $resultado ?: null;
        } catch (PDOException $error) {
            error_log('Error al obtener el usuario por id: ' . $error->getMessage());
            return null;
        }
    }

    private function rolExiste(int $idRol): bool
    {
        if ($idRol <= 0) {
            return false;
        }
        try {
            $sql = "SELECT id_rol FROM roles WHERE id_rol = :id_rol LIMIT 1";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([':id_rol' => $idRol]);
            return (bool)$stmt->fetch();
        } catch (PDOException $error) {
            error_log('Error al verificar el rol: ' . $error->getMessage());
            throw new Exception('No se pudo verificar el rol seleccionado. Intente nuevamente.');
        }
    }

    private function validarNombreUsuario(string $nombre): void
    {
        if (!preg_match($this->expNombreUsuario, $nombre)) {
            throw new Exception("[Validación] El nombre de usuario debe tener entre 3 y 20 caracteres (letras, números o guiones bajos).");
        }
    }

    private function validarContrasena(string $contrasena): void
    {
        if (strlen($contrasena) < 6 || strlen($contrasena) > 255) {
            throw new Exception("[Validación] La contraseña debe tener al menos 6 caracteres.");
        }
    }

    /**
     * Empleados con datos de su vínculo en acceso_empleado, para poblar
     * los selectores de "empleado asociado" en registro/edición de usuarios.
     */
    public function listarEmpleadosParaVincular(): array
    {
        try {
            $sql = "SELECT e.id_empleado, e.nombres, e.apellidos, e.cedula, e.status_empleado,
                           ae.id_usuario AS id_usuario_vinculado
                    FROM empleado e
                    LEFT JOIN acceso_empleado ae ON ae.id_empleado = e.id_empleado
                    ORDER BY e.nombres ASC, e.apellidos ASC";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $error) {
            error_log('Error al listar empleados para vincular: ' . $error->getMessage());
            return [];
        }
    }

    /**
     * Vincula (o reemplaza) un empleado a un usuario en acceso_empleado.
     * Elimina antes cualquier vínculo previo de ese usuario o de ese empleado.
     */
    public function vincularEmpleado(int $idUsuario, int $idEmpleado): bool
    {
        if ($idUsuario <= 0 || $idEmpleado <= 0) {
            throw new Exception("[Validación] Usuario o empleado inválido para vincular.");
        }

        try {
            $stmt = $this->conex->prepare("DELETE FROM acceso_empleado WHERE id_usuario = :u OR id_empleado = :e");
            $stmt->execute([':u' => $idUsuario, ':e' => $idEmpleado]);

            $stmt = $this->conex->prepare("INSERT INTO acceso_empleado (id_usuario, id_empleado) VALUES (:u, :e)");
            return $stmt->execute([':u' => $idUsuario, ':e' => $idEmpleado]);
        } catch (PDOException $error) {
            error_log('Error al vincular el empleado: ' . $error->getMessage());
            throw new Exception('No se pudo vincular el empleado. Intente nuevamente.');
        }
    }

    /**
     * Elimina el vínculo de un usuario con su empleado (si existe).
     */
    public function desvincularEmpleado(int $idUsuario): bool
    {
        try {
            $stmt = $this->conex->prepare("DELETE FROM acceso_empleado WHERE id_usuario = :u");
            return $stmt->execute([':u' => $idUsuario]);
        } catch (PDOException $error) {
            error_log('Error al desvincular el empleado: ' . $error->getMessage());
            throw new Exception('No se pudo desvincular el empleado. Intente nuevamente.');
        }
    }
}











