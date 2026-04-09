<?php

class Reparacion {

    private $conn;
    private $table = "reparaciones";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getReparaciones() {
        $query = "SELECT * FROM reparaciones";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getReparacion($id) {
        $query = "SELECT * FROM reparaciones WHERE id_reparacion = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createReparacion($data) {
        $query = "INSERT INTO reparaciones
                  (id_cita, descripcion, coste, estado, fecha_inicio, fecha_fin)
                  VALUES
                  (:id_cita, :descripcion, :coste, :estado, :fecha_inicio, :fecha_fin)";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            "id_cita"     => $data["id_cita"],
            "descripcion" => $data["descripcion"] ?? null,
            "coste"       => $data["coste"]       ?? 0,
            "estado"      => $data["estado"]      ?? "pendiente",
            "fecha_inicio"=> $data["fecha_inicio"]?? null,
            "fecha_fin"   => $data["fecha_fin"]   ?? null
        ]);
    }

    // FIX: $id se concatenaba directamente en la SQL (inyección SQL)
    // Ahora es un parámetro preparado :id
    public function updateReparacion($id, $data) {
        $query = "UPDATE reparaciones SET
                  descripcion   = :descripcion,
                  coste         = :coste,
                  estado        = :estado,
                  fecha_inicio  = :fecha_inicio,
                  fecha_fin     = :fecha_fin
                  WHERE id_reparacion = :id";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            "descripcion"  => $data["descripcion"] ?? null,
            "coste"        => $data["coste"]       ?? 0,
            "estado"       => $data["estado"]      ?? "pendiente",
            "fecha_inicio" => $data["fecha_inicio"]?? null,
            "fecha_fin"    => $data["fecha_fin"]   ?? null,
            "id"           => $id
        ]);
    }

    public function deleteReparacion($id) {
        $query = "DELETE FROM reparaciones WHERE id_reparacion = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }
}
