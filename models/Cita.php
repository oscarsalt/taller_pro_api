<?php

class Cita {

    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

  public function getCitas($id_usuario) {
    $stmt = $this->conn->prepare(
        "SELECT
            c.id_cita,
            c.fecha,
            c.hora,
            c.estado,
            c.descripcion,
            c.mano_obra,
            c.piezas,
            c.otros,
            c.coste,
            c.presupuesto,
            v.marca,
            v.modelo,
            v.matricula,
            cl.nombre
         FROM citas c
         JOIN vehiculos v  ON c.id_vehiculo = v.id_vehiculo
         JOIN clientes cl  ON c.id_cliente  = cl.id_cliente
         WHERE c.id_usuario = ?
         ORDER BY c.fecha DESC, c.hora DESC"
    );
    $stmt->execute([$id_usuario]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    public function getCitaById($id, $id_usuario) {
        $stmt = $this->conn->prepare(
            "SELECT
                c.id_cita,
                c.fecha,
                c.hora,
                c.estado,
                c.descripcion,
                c.coste,
                c.presupuesto,
                v.marca,
                v.modelo,
                v.matricula,
                cl.nombre
             FROM citas c
             JOIN vehiculos v  ON c.id_vehiculo = v.id_vehiculo
             JOIN clientes cl  ON c.id_cliente  = cl.id_cliente
             WHERE c.id_cita = ? AND c.id_usuario = ?"
        );
        $stmt->execute([$id, $id_usuario]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

public function createCita($data, $id_usuario) {
    $stmt = $this->conn->prepare(
        "INSERT INTO citas (id_usuario, id_cliente, id_vehiculo, fecha, hora, estado, descripcion, mano_obra, piezas, otros, coste)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $mano_obra = (float)($data["mano_obra"] ?? 0);
    $piezas    = (float)($data["piezas"]    ?? 0);
    $otros     = (float)($data["otros"]     ?? 0);
    $subtotal  = $mano_obra + $piezas + $otros;
    $coste     = round($subtotal * 1.21, 2); // IVA 21%

    $stmt->execute([
        $id_usuario,
        $data["id_cliente"],
        $data["id_vehiculo"],
        $data["fecha"],
        $data["hora"],
        $data["estado"]      ?? "pendiente",
        $data["descripcion"] ?? null,
        $mano_obra,
        $piezas,
        $otros,
        $coste
    ]);

    return $this->conn->lastInsertId();
}

    public function updateCoste($id, $coste, $id_usuario) {
        $stmt = $this->conn->prepare(
            "UPDATE citas SET coste = ? WHERE id_cita = ? AND id_usuario = ?"
        );
        return $stmt->execute([$coste, $id, $id_usuario]);
    }

    public function deleteCita($id, $id_usuario) {
        $stmt = $this->conn->prepare("DELETE FROM citas WHERE id_cita = ? AND id_usuario = ?");
        return $stmt->execute([$id, $id_usuario]);
    }
}