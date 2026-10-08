<?php

namespace Idealo\Models;

require_once __DIR__ . '/../../config/database.php';

use Idealo\Config\Database;
use Exception;

class UsuarioModel extends Database
{
    private $conex;

    public $expNombreUsuario = '/^[a-zA-Z0-9_]{3,20}$/';

    public function __construct()
    {
        $this->conex = parent::connect();
    }

    public function listarRoles(): array
    {
        $sql = "SELECT id_rol, tipo_de_usuario
                FROM roles
                WHERE status_roles = 'activo'
                ORDER BY id_rol ASC";
        $stmt = $this->conex->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function listarTodos(): array
    {
        $sql = "SELECT u.id_usuario,
                       u.nombre_usuario,
                       u.correo,
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

        $stmt = $this->conex->prepare($sql);
        $stmt->execute($params);

        return [
            'success' => true,
            'message' => 'Usuario actualizado con éxito.'
        ];
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
    }

    public function obtenerPerfil(int $idUsuario): ?array
    {
        $sql = "SELECT u.id_usuario,
                       u.nombre_usuario,
                       u.correo,
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
    }

    public function cambiarContrasena(int $idUsuario, string $contrasenaActual, string $nuevaContrasena): array
    {
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
    }

    public function cambiarContrasenaSimple(int $idUsuario, string $contrasena): bool
    {
        $this->validarContrasena($contrasena);
        $hash = password_hash($contrasena, PASSWORD_BCRYPT);
        $sql = "UPDATE usuario SET contrasena = :contrasena WHERE id_usuario = :id_usuario";
        $stmt = $this->conex->prepare($sql);
        return (bool)$stmt->execute([
            ':contrasena' => $hash,
            ':id_usuario' => $idUsuario
        ]);
    }

    public function cambiarNombreUsuario(int $idUsuario, string $nuevoNombre): array
    {
        $nuevoNombre = trim($nuevoNombre);

        $this->validarNombreUsuario($nuevoNombre);

        if ($this->existeNombreUsuario($nuevoNombre, $idUsuario)) {
            throw new Exception("[Validación] El nombre de usuario '{$nuevoNombre}' ya está registrado.");
        }

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
    }

    public function existeNombreUsuario(string $nombre, ?int $exceptuarId = null): bool
    {
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
    }

    public function existeCorreo(string $correo, ?int $exceptuarId = null): bool
    {
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
    }

    public function obtenerUsuarioPorCorreo(string $correo): ?array
    {
        $sql = "SELECT id_usuario, nombre_usuario, correo, contrasena, status_usuario, id_rol
                FROM usuario
                WHERE correo = :correo AND status_usuario = 'activo'
                LIMIT 1";
        $stmt = $this->conex->prepare($sql);
        $stmt->execute([':correo' => $correo]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function obtenerPorCredencial(string $credencial): ?array
    {
        $sql = "SELECT u.id_usuario, u.nombre_usuario, u.correo, u.contrasena, u.status_usuario, u.id_rol, r.tipo_de_usuario
                FROM usuario u
                LEFT JOIN roles r ON u.id_rol = r.id_rol
                WHERE (u.nombre_usuario = :credencial1 OR u.correo = :credencial2) AND u.status_usuario = 'activo'
                LIMIT 1";
        $stmt = $this->conex->prepare($sql);
        $stmt->execute([':credencial1' => $credencial, ':credencial2' => $credencial]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function obtenerPorId(int $idUsuario): ?array
    {
        $sql = "SELECT u.id_usuario, u.nombre_usuario, u.correo, u.contrasena, u.status_usuario, u.id_rol, r.tipo_de_usuario
                FROM usuario u
                LEFT JOIN roles r ON u.id_rol = r.id_rol
                WHERE u.id_usuario = :id_usuario
                LIMIT 1";
        $stmt = $this->conex->prepare($sql);
        $stmt->execute([':id_usuario' => $idUsuario]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    private function rolExiste(int $idRol): bool
    {
        if ($idRol <= 0) {
            return false;
        }
        $sql = "SELECT id_rol FROM roles WHERE id_rol = :id_rol LIMIT 1";
        $stmt = $this->conex->prepare($sql);
        $stmt->execute([':id_rol' => $idRol]);
        return (bool)$stmt->fetch();
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
        $sql = "SELECT e.id_empleado, e.nombres, e.apellidos, e.cedula, e.status_empleado,
                       ae.id_usuario AS id_usuario_vinculado
                FROM empleado e
                LEFT JOIN acceso_empleado ae ON ae.id_empleado = e.id_empleado
                ORDER BY e.nombres ASC, e.apellidos ASC";
        $stmt = $this->conex->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
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

        $stmt = $this->conex->prepare("DELETE FROM acceso_empleado WHERE id_usuario = :u OR id_empleado = :e");
        $stmt->execute([':u' => $idUsuario, ':e' => $idEmpleado]);

        $stmt = $this->conex->prepare("INSERT INTO acceso_empleado (id_usuario, id_empleado) VALUES (:u, :e)");
        return $stmt->execute([':u' => $idUsuario, ':e' => $idEmpleado]);
    }

    /**
     * Elimina el vínculo de un usuario con su empleado (si existe).
     */
    public function desvincularEmpleado(int $idUsuario): bool
    {
        $stmt = $this->conex->prepare("DELETE FROM acceso_empleado WHERE id_usuario = :u");
        return $stmt->execute([':u' => $idUsuario]);
    }
}











