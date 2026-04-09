<?php

class Usuario {

    private $conn;
    private $table = "usuarios";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getByEmail($email) {
        $query = "SELECT * FROM usuarios WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createUsuario($data) {
        $query = "INSERT INTO usuarios (nombre, email, password, rol)
                  VALUES (:nombre, :email, :password, :rol)";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            "nombre"   => $data["nombre"],
            "email"    => $data["email"],
            "password" => password_hash($data["password"], PASSWORD_DEFAULT),
            "rol"      => $data["rol"] ?? "empleado"
        ]);
    }
}
