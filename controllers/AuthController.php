<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController {

    private function getDB() {
        $database = new Database();
        return $database->connect();
    }

    public function register() {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data || empty($data["nombre"]) || empty($data["email"]) || empty($data["password"])) {
            http_response_code(400);
            echo json_encode(["error" => "Datos incompletos"]);
            return;
        }

        // Validar formato email
        if (!filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(["error" => "Email no válido"]);
            return;
        }

        // Validar longitud contraseña
        if (strlen($data["password"]) < 6) {
            http_response_code(400);
            echo json_encode(["error" => "La contraseña debe tener al menos 6 caracteres"]);
            return;
        }

        $db = $this->getDB();
        $usuario = new Usuario($db);

        if ($usuario->getByEmail($data["email"])) {
            http_response_code(409);
            echo json_encode(["error" => "El email ya está registrado"]);
            return;
        }

        $usuario->createUsuario($data);

        http_response_code(201);
        echo json_encode(["message" => "Usuario creado"]);
    }

    public function login() {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data || empty($data["email"]) || empty($data["password"])) {
            http_response_code(400);
            echo json_encode(["error" => "Email y contraseña requeridos"]);
            return;
        }

        // Límite de intentos de login
        $ip = $_SERVER['REMOTE_ADDR'];
        $intentosKey = sys_get_temp_dir() . '/login_' . md5($ip) . '.json';
        $intentos = file_exists($intentosKey) ? json_decode(file_get_contents($intentosKey), true) : ['count' => 0, 'time' => time()];

        // Resetear si han pasado más de 15 minutos
        if (time() - $intentos['time'] > 900) {
            $intentos = ['count' => 0, 'time' => time()];
        }

        if ($intentos['count'] >= 5) {
            $espera = 900 - (time() - $intentos['time']);
            http_response_code(429);
            echo json_encode(["error" => "Demasiados intentos. Espera " . ceil($espera / 60) . " minutos."]);
            return;
        }

        $db = $this->getDB();
        $usuario = new Usuario($db);
        $user = $usuario->getByEmail($data["email"]);

	// DEBUG - quitar después
	error_log("Usuario encontrado: " . json_encode($user));
	error_log("Password recibido: " . $data["password"]);
	error_log("Verify resultado: " . (password_verify($data["password"], $user["password"]) ? "true" : "false"));

        if ($user && password_verify($data["password"], $user["password"])) {

            // Login correcto — resetear intentos
            file_put_contents($intentosKey, json_encode(['count' => 0, 'time' => time()]));

            unset($user["password"]);

            // Generar JWT
            $payload = [
                "iat"         => time(),
                "exp"         => time() + JWT_EXPIRATION,
                "id_usuario"  => $user["id_usuario"],
                "email"       => $user["email"],
                "nombre"      => $user["nombre"]
            ];

            $token = JWT::encode($payload, JWT_SECRET, 'HS256');

            echo json_encode([
                "message" => "Login correcto",
                "token"   => $token,
                "user"    => $user
            ]);

        } else {

            // Login fallido — incrementar intentos
            $intentos['count']++;
            $intentos['time'] = $intentos['time'];
            file_put_contents($intentosKey, json_encode($intentos));

            http_response_code(401);
            echo json_encode(["message" => "Credenciales incorrectas"]);
        }
    }
}