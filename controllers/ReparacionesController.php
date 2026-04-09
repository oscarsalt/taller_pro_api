<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Reparacion.php';

class ReparacionesController {

    private function getDB() {
        $database = new Database();
        return $database->connect();
    }

    public function getReparaciones() {
        $db = $this->getDB();
        $rep = new Reparacion($db);
        $stmt = $rep->getReparaciones();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getReparacion($id) {
        $db = $this->getDB();
        $rep = new Reparacion($db);
        $result = $rep->getReparacion($id);

        if (!$result) {
            http_response_code(404);
            echo json_encode(["error" => "Reparación no encontrada"]);
            return;
        }

        echo json_encode($result);
    }

    public function createReparacion() {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data) {
            $data = $_POST;
        }

        if (empty($data)) {
            http_response_code(400);
            echo json_encode(["error" => "Datos inválidos"]);
            return;
        }

        $db = $this->getDB();
        $rep = new Reparacion($db);
        $rep->createReparacion($data);

        http_response_code(201);
        echo json_encode(["message" => "Reparación creada"]);
    }

    public function updateReparacion($id) {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(["error" => "Datos inválidos"]);
            return;
        }

        $db = $this->getDB();
        $rep = new Reparacion($db);
        $rep->updateReparacion($id, $data);

        echo json_encode(["message" => "Reparación actualizada"]);
    }

    public function deleteReparacion($id) {
        $db = $this->getDB();
        $rep = new Reparacion($db);
        $rep->deleteReparacion($id);
        echo json_encode(["message" => "Reparación eliminada"]);
    }
}
