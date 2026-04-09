<?php
class Cliente {

    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getClientes($id_usuario) {
        $stmt = $this->conn->prepare("SELECT * FROM clientes WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getClienteById($id, $id_usuario) {
        $stmt = $this->conn->prepare("SELECT * FROM clientes WHERE id_cliente = ? AND id_usuario = ?");
        $stmt->execute([$id, $id_usuario]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createCliente($data, $id_usuario) {
        $stmt = $this->conn->prepare(
            "INSERT INTO clientes (id_usuario, nombre, telefono, email, direccion)
             VALUES (?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            $id_usuario,
            $data["nombre"],
            $data["telefono"]  ?? null,
            $data["email"]     ?? null,
            $data["direccion"] ?? null
        ]);
    }

    public function updateCliente($id, $data, $id_usuario) {
        $stmt = $this->conn->prepare(
            "UPDATE clientes SET nombre=?, telefono=?, email=?, direccion=?
             WHERE id_cliente=? AND id_usuario=?"
        );
        return $stmt->execute([
            $data["nombre"],
            $data["telefono"]  ?? null,
            $data["email"]     ?? null,
            $data["direccion"] ?? null,
            $id,
            $id_usuario
        ]);
    }

    public function deleteCliente($id, $id_usuario) {
        $stmt = $this->conn->prepare("DELETE FROM clientes WHERE id_cliente=? AND id_usuario=?");
        return $stmt->execute([$id, $id_usuario]);
    }
}
