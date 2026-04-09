<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Cliente.php';

class ClientesController {

    private function getDB() {
        $database = new Database();
        return $database->connect();
    }

    private function userId() {
        return $GLOBALS['id_usuario'];
    }

    public function getClientes() {
        $db = $this->getDB();
        $cliente = new Cliente($db);
        echo json_encode($cliente->getClientes($this->userId()));
    }

    public function createCliente() {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data || empty($data["nombre"])) {
            http_response_code(400);
            echo json_encode(["error" => "Datos incompletos"]);
            return;
        }

        $db = $this->getDB();
        $cliente = new Cliente($db);
        $cliente->createCliente($data, $this->userId());

        http_response_code(201);
        echo json_encode(["message" => "Cliente creado"]);
    }

    public function deleteCliente($id) {
        $db = $this->getDB();
        $cliente = new Cliente($db);
        $cliente->deleteCliente($id, $this->userId());
        echo json_encode(["message" => "Cliente eliminado"]);
    }

    public function updateCliente($id) {
        $data = json_decode(file_get_contents("php://input"), true);
        $db = $this->getDB();
        $cliente = new Cliente($db);
        $cliente->updateCliente($id, $data, $this->userId());
        echo json_encode(["message" => "Cliente actualizado"]);
    }
}