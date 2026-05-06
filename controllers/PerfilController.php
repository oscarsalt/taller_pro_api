<?php
require_once __DIR__ . '/../config/database.php';

class PerfilController {
    private function getDB() {
        $database = new Database();
        return $database->connect();
    }

    public function getPerfil() {
        $db  = $this->getDB();
        $uid = $GLOBALS['id_usuario'];
        $stmt = $db->prepare("SELECT id_usuario, nombre, email, rol, fecha_creacion FROM usuarios WHERE id_usuario = ?");
        $stmt->execute([$uid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            http_response_code(404);
            echo json_encode(["error" => "Usuario no encontrado"]);
            return;
        }
        echo json_encode($user);
    }

    public function updatePerfil() {
        $db   = $this->getDB();
        $uid  = $GLOBALS['id_usuario'];
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['nombre']) || empty($data['email'])) {
            http_response_code(400);
            echo json_encode(["error" => "Nombre y email son obligatorios"]);
            return;
        }

        // Verificar que el email no lo use otro usuario
        $stmt = $db->prepare("SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ?");
        $stmt->execute([$data['email'], $uid]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(["error" => "El email ya está en uso por otro usuario"]);
            return;
        }

        if (!empty($data['password'])) {
            // Actualizar con nueva contraseña
            $hash = password_hash($data['password'], PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, email = ?, password = ? WHERE id_usuario = ?");
            $stmt->execute([$data['nombre'], $data['email'], $hash, $uid]);
        } else {
            // Actualizar sin cambiar contraseña
            $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, email = ? WHERE id_usuario = ?");
            $stmt->execute([$data['nombre'], $data['email'], $uid]);
        }

        echo json_encode(["message" => "Perfil actualizado"]);
    }
}