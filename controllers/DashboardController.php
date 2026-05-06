<?php

require_once __DIR__ . '/../config/database.php';

class DashboardController {

    private function getDB() {
        $database = new Database();
        return $database->connect();
    }

    public function estadisticas() {
        $db  = $this->getDB();
        $uid = $GLOBALS['id_usuario'];
        $data = [];

        $stmt = $db->prepare("SELECT COUNT(*) as total FROM clientes WHERE id_usuario = ?");
        $stmt->execute([$uid]);
        $data["clientes"] = (int) $stmt->fetch()["total"];

        $stmt = $db->prepare("SELECT COUNT(*) as total FROM vehiculos WHERE id_usuario = ?");
        $stmt->execute([$uid]);
        $data["vehiculos"] = (int) $stmt->fetch()["total"];

        $stmt = $db->prepare("SELECT COUNT(*) as total FROM citas WHERE id_usuario = ?");
        $stmt->execute([$uid]);
        $data["citas"] = (int) $stmt->fetch()["total"];

        $stmt = $db->prepare("SELECT COUNT(*) as total FROM citas WHERE id_usuario = ? AND fecha = CURDATE()");
        $stmt->execute([$uid]);
        $data["citas_hoy"] = (int) $stmt->fetch()["total"];

        $stmt = $db->prepare("SELECT IFNULL(SUM(coste), 0) as total FROM citas WHERE id_usuario = ?");
        $stmt->execute([$uid]);
        $data["ingresos"] = (float) $stmt->fetch()["total"];

        echo json_encode($data);
    }

  public function citasSemana() {
    $db  = $this->getDB();
    $uid = $GLOBALS['id_usuario'];

    // Recibir fechas desde el frontend
    $inicio = $_GET['inicio'] ?? date('Y-m-d', strtotime('monday this week'));
    $fin    = $_GET['fin']    ?? date('Y-m-d', strtotime('sunday this week'));

    $stmt = $db->prepare(
        "SELECT
            c.id_cita,
            c.fecha,
            c.hora,
            c.estado,
            c.descripcion,
            c.coste,
            v.marca,
            v.modelo,
            v.matricula,
            cl.nombre
         FROM citas c
         JOIN vehiculos v  ON c.id_vehiculo = v.id_vehiculo
         JOIN clientes cl  ON c.id_cliente  = cl.id_cliente
         WHERE c.id_usuario = ?
         AND c.fecha BETWEEN ? AND ?
         ORDER BY c.fecha, c.hora"
    );
    $stmt->execute([$uid, $inicio, $fin]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
}