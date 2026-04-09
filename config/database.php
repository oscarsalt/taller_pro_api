<?php

class Database {

    private $host;
    private $db_name;
    private $username;
    private $password;

    public function __construct() {
        // Usar variables de entorno si existen, si no usar valores por defecto
        $this->host     = getenv('DB_HOST')     ?: 'localhost';
        $this->db_name  = getenv('DB_NAME')     ?: 'taller_db';
        $this->username = getenv('DB_USER')     ?: 'root';
        $this->password = getenv('DB_PASSWORD') ?: '';
    }

    public function connect() {

        try {

            $conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password
            );

            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            return $conn;

        } catch (PDOException $e) {

            http_response_code(500);
            echo json_encode(["error" => "Error de conexión: " . $e->getMessage()]);
            exit();

        }
    }
}
