<?php
class Vehiculo {

    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getVehiculos($id_usuario) {
        $stmt = $this->conn->prepare("SELECT * FROM vehiculos WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getVehiculo($id, $id_usuario) {
        $stmt = $this->conn->prepare("SELECT * FROM vehiculos WHERE id_vehiculo = ? AND id_usuario = ?");
        $stmt->execute([$id, $id_usuario]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createVehiculo($data, $id_usuario) {
        $stmt = $this->conn->prepare(
            "INSERT INTO vehiculos (id_usuario, id_cliente, marca, modelo, matricula, anio)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            $id_usuario,
            $data["id_cliente"],
            $data["marca"],
            $data["modelo"]    ?? null,
            $data["matricula"],
            $data["anio"]      ?? null
        ]);
    }

    public function deleteVehiculo($id, $id_usuario) {
        $stmt = $this->conn->prepare("DELETE FROM vehiculos WHERE id_vehiculo = ? AND id_usuario = ?");
        return $stmt->execute([$id, $id_usuario]);
    }
}