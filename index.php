<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/jwt.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Rutas públicas que no necesitan token
$request = explode("?", $_SERVER['REQUEST_URI'])[0];
$esPublica = (str_contains($request, "login") || str_contains($request, "register"));

if (!$esPublica) {

    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';

    if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
        http_response_code(401);
        echo json_encode(["error" => "Token requerido"]);
        exit();
    }

    $token = substr($authHeader, 7);

    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
        $GLOBALS['id_usuario'] = $decoded->id_usuario;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(["error" => "Token inválido o expirado"]);
        exit();
    }

}

require_once "routes/api.php";