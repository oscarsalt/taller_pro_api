<?php

require_once __DIR__ . '/../controllers/ClientesController.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/VehiculosController.php';
require_once __DIR__ . '/../controllers/CitasController.php';
require_once __DIR__ . '/../controllers/DashboardController.php';

$request = $_SERVER['REQUEST_URI'];
$method  = $_SERVER['REQUEST_METHOD'];

$request = explode("?", $request)[0];
$segments = explode("/", trim($request, "/"));

$id = null;
if (isset($segments[count($segments) - 1]) && is_numeric($segments[count($segments) - 1])) {
    $id = $segments[count($segments) - 1];
}

$clientes  = new ClientesController();
$auth      = new AuthController();
$vehiculos = new VehiculosController();
$citas     = new CitasController();
$dashboard = new DashboardController();

/* AUTH */
if (str_contains($request, "register") && $method === "POST") { $auth->register(); return; }
if (str_contains($request, "login")    && $method === "POST") { $auth->login();    return; }

/* CLIENTES */
if (str_contains($request, "clientes")) {
    if ($method === "GET"    && !$id) { $clientes->getClientes();      return; }
    if ($method === "GET"    &&  $id) { $clientes->getCliente($id);    return; }
    if ($method === "POST")           { $clientes->createCliente();    return; }
    if ($method === "PUT"    &&  $id) { $clientes->updateCliente($id); return; }
    if ($method === "DELETE" &&  $id) { $clientes->deleteCliente($id); return; }
}

/* VEHICULOS */
if (str_contains($request, "vehiculos")) {
    if ($method === "GET"    && !$id) { $vehiculos->getVehiculos();      return; }
    if ($method === "GET"    &&  $id) { $vehiculos->getVehiculo($id);    return; }
    if ($method === "POST")           { $vehiculos->createVehiculo();    return; }
    if ($method === "DELETE" &&  $id) { $vehiculos->deleteVehiculo($id); return; }
}

/* CITAS */
if (str_contains($request, "citas")) {
    if ($method === "POST" && str_contains($request, "upload") && $id) { $citas->subirPresupuesto($id); return; }
    if ($method === "POST" && str_contains($request, "update") && $id) { $citas->updateEstado($id);     return; }
    if ($method === "POST" && str_contains($request, "coste")  && $id) { $citas->updateCoste($id);      return; }
    if ($method === "GET"    && !$id) { $citas->getCitas();      return; }
    if ($method === "GET"    &&  $id) { $citas->getCita($id);    return; }
    if ($method === "POST")           { $citas->createCita();    return; }
    if ($method === "DELETE" &&  $id) { $citas->deleteCita($id); return; }
}

/* DASHBOARD */
if (str_contains($request, "dashboard")) {
    if ($method === "GET" && str_contains($request, "semana")) {
        $dashboard->citasSemana();
        return;
    }
    if ($method === "GET") {
        $dashboard->estadisticas();
        return;
    }
}

// Actualizar desglose: POST /citas/desglose/{id}
if ($method === "POST" && str_contains($request, "desglose") && $id) {
    $citas->updateDesglose($id);
    return;
}

/* NO ENCONTRADO */
http_response_code(404);
echo json_encode(["error" => "Endpoint no encontrado"]);
