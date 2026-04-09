<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Vehiculo.php';

class VehiculosController {

    private function getDB() {
        $database = new Database();
        return $database->connect();
    }

    private function userId() {
        return $GLOBALS['id_usuario'];
    }

    public function getVehiculos() {
        $db = $this->getDB();
        $vehiculo = new Vehiculo($db);
        echo json_encode($vehiculo->getVehiculos($this->userId()));
    }

    public function getVehiculo($id) {
        $db = $this->getDB();
        $vehiculo = new Vehiculo($db);
        $result = $vehiculo->getVehiculo($id, $this->userId());

        if (!$result) {
            http_response_code(404);
            echo json_encode(["error" => "Vehículo no encontrado"]);
            return;
        }

        echo json_encode($result);
    }

    public function createVehiculo() {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data) {
            $data = $_POST;
        }

        if (empty($data["marca"]) || empty($data["matricula"])) {
            http_response_code(400);
            echo json_encode(["error" => "Datos incompletos"]);
            return;
        }

        $db = $this->getDB();
        $vehiculo = new Vehiculo($db);
        $vehiculo->createVehiculo($data, $this->userId());

        http_response_code(201);
        echo json_encode(["message" => "Vehículo creado"]);
    }

    public function deleteVehiculo($id) {
        $db = $this->getDB();
        $vehiculo = new Vehiculo($db);
        $vehiculo->deleteVehiculo($id, $this->userId());
        echo json_encode(["message" => "Vehículo eliminado"]);
    }
}