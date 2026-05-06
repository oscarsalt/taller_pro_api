<?php
require_once __DIR__ . '/../config/database.php';

class BusquedaController {
    private function getDB() {
        $database = new Database();
        return $database->connect();
    }

    public function buscar() {
        $db  = $this->getDB();
        $uid = $GLOBALS['id_usuario'];
        $q   = '%' . ($_GET['q'] ?? '') . '%';

        $clientes = $db->prepare(
            "SELECT id_cliente as id, nombre, telefono, email, 'cliente' as tipo
             FROM clientes WHERE id_usuario = ? AND (nombre LIKE ? OR telefono LIKE ? OR email LIKE ?)
             LIMIT 5"
        );
        $clientes->execute([$uid, $q, $q, $q]);

        $vehiculos = $db->prepare(
            "SELECT id_vehiculo as id, CONCAT(marca, ' ', modelo) as nombre, matricula as subtitulo, 'vehiculo' as tipo
             FROM vehiculos WHERE id_usuario = ? AND (marca LIKE ? OR modelo LIKE ? OR matricula LIKE ?)
             LIMIT 5"
        );
        $vehiculos->execute([$uid, $q, $q, $q]);

        $citas = $db->prepare(
            "SELECT c.id_cita as id, CONCAT(v.marca, ' ', v.modelo, ' - ', v.matricula) as nombre,
                    CONCAT(c.fecha, ' ', c.estado) as subtitulo, 'cita' as tipo
             FROM citas c
             JOIN vehiculos v ON c.id_vehiculo = v.id_vehiculo
             WHERE c.id_usuario = ? AND (c.descripcion LIKE ? OR v.matricula LIKE ? OR c.estado LIKE ?)
             LIMIT 5"
        );
        $citas->execute([$uid, $q, $q, $q]);

        echo json_encode([
            'clientes'  => $clientes->fetchAll(PDO::FETCH_ASSOC),
            'vehiculos' => $vehiculos->fetchAll(PDO::FETCH_ASSOC),
            'citas'     => $citas->fetchAll(PDO::FETCH_ASSOC),
        ]);
    }
}