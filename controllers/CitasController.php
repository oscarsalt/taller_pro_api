<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Cita.php';
require_once __DIR__ . '/../vendor/autoload.php';

class CitasController {

    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    private function userId() {
        return $GLOBALS['id_usuario'];
    }

    public function getCitas() {
        $cita = new Cita($this->conn);
        echo json_encode($cita->getCitas($this->userId()));
    }

    public function getCita($id) {
        $cita = new Cita($this->conn);
        $result = $cita->getCitaById($id, $this->userId());

        if (!$result) {
            http_response_code(404);
            echo json_encode(["error" => "Cita no encontrada"]);
            return;
        }

        echo json_encode($result);
    }

    public function createCita() {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(["error" => "Datos invalidos"]);
            return;
        }

        // Obtener datos del cliente y vehiculo para el PDF
        $stmtVeh = $this->conn->prepare(
            "SELECT v.marca, v.modelo, v.matricula, v.anio,
                    cl.nombre, cl.telefono, cl.email, cl.direccion
             FROM vehiculos v
             JOIN clientes cl ON v.id_cliente = cl.id_cliente
             WHERE v.id_vehiculo = ? AND v.id_usuario = ?"
        );
        $stmtVeh->execute([$data["id_vehiculo"], $this->userId()]);
        $info = $stmtVeh->fetch(PDO::FETCH_ASSOC);

        // Obtener nombre del taller
        $stmtUser = $this->conn->prepare("SELECT nombre FROM usuarios WHERE id_usuario = ?");
        $stmtUser->execute([$this->userId()]);
        $taller = $stmtUser->fetch(PDO::FETCH_ASSOC);

        $cita = new Cita($this->conn);
        $id_cita = $cita->createCita($data, $this->userId());

        // Numero de presupuesto
        $numPresupuesto = str_pad($id_cita, 6, "0", STR_PAD_LEFT);

        // Calcular importes
        $mano_obra = (float)($data["mano_obra"] ?? 0);
        $piezas    = (float)($data["piezas"]    ?? 0);
        $otros     = (float)($data["otros"]     ?? 0);
        $subtotal  = $mano_obra + $piezas + $otros;
        $iva       = round($subtotal * 0.21, 2);
        $total     = round($subtotal * 1.21, 2);

        // Generar PDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->SetCreator('TallerPro');
        $pdf->SetAuthor($taller['nombre'] ?? 'Taller');
        $pdf->SetTitle('Presupuesto #' . $numPresupuesto);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage();

        // Titulo
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->SetTextColor(44, 62, 80);
        $pdf->Cell(0, 12, strtoupper($taller['nombre'] ?? 'TALLER'), 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 6, 'PRESUPUESTO', 0, 1, 'C');

        $pdf->Ln(4);

        // Linea separadora
        $pdf->SetDrawColor(44, 62, 80);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(6);

        // Numero y fecha
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor(44, 62, 80);
        $pdf->Cell(90, 6, 'N Presupuesto: #' . $numPresupuesto, 0, 0, 'L');
        $pdf->Cell(90, 6, 'Fecha: ' . date('d/m/Y'), 0, 1, 'R');
        $pdf->Ln(4);

        // Datos del cliente
        $pdf->SetFillColor(44, 62, 80);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 7, '  DATOS DEL CLIENTE', 0, 1, 'L', true);
        $pdf->Ln(2);

        $pdf->SetTextColor(44, 62, 80);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(40, 6, 'Nombre:', 0, 0);
        $pdf->Cell(0, 6, $info['nombre'] ?? '-', 0, 1);
        $pdf->Cell(40, 6, 'Telefono:', 0, 0);
        $pdf->Cell(0, 6, $info['telefono'] ?? '-', 0, 1);
        $pdf->Cell(40, 6, 'Email:', 0, 0);
        $pdf->Cell(0, 6, $info['email'] ?? '-', 0, 1);
        $pdf->Cell(40, 6, 'Direccion:', 0, 0);
        $pdf->Cell(0, 6, $info['direccion'] ?? '-', 0, 1);
        $pdf->Ln(4);

        // Datos del vehiculo
        $pdf->SetFillColor(44, 62, 80);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 7, '  DATOS DEL VEHICULO', 0, 1, 'L', true);
        $pdf->Ln(2);

        $pdf->SetTextColor(44, 62, 80);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(40, 6, 'Marca:', 0, 0);
        $pdf->Cell(0, 6, $info['marca'] ?? '-', 0, 1);
        $pdf->Cell(40, 6, 'Modelo:', 0, 0);
        $pdf->Cell(0, 6, $info['modelo'] ?? '-', 0, 1);
        $pdf->Cell(40, 6, 'Matricula:', 0, 0);
        $pdf->Cell(0, 6, $info['matricula'] ?? '-', 0, 1);
        $pdf->Cell(40, 6, 'Anio:', 0, 0);
        $pdf->Cell(0, 6, $info['anio'] ?? '-', 0, 1);
        $pdf->Ln(4);

        // Detalles de la cita
        $pdf->SetFillColor(44, 62, 80);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 7, '  DETALLES DE LA CITA', 0, 1, 'L', true);
        $pdf->Ln(2);

        $pdf->SetTextColor(44, 62, 80);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(40, 6, 'Fecha:', 0, 0);
        $pdf->Cell(0, 6, date('d/m/Y', strtotime($data['fecha'])), 0, 1);
        $pdf->Cell(40, 6, 'Hora:', 0, 0);
        $pdf->Cell(0, 6, $data['hora'] ?? '-', 0, 1);
        $pdf->Cell(40, 6, 'Descripcion:', 0, 0);
        $pdf->Cell(0, 6, $data['descripcion'] ?? '-', 0, 1);
        $pdf->Ln(4);

        // Desglose del presupuesto
        $pdf->SetFillColor(44, 62, 80);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 7, '  DESGLOSE DEL PRESUPUESTO', 0, 1, 'L', true);
        $pdf->Ln(2);

        // Cabecera tabla
        $pdf->SetFillColor(236, 240, 241);
        $pdf->SetTextColor(44, 62, 80);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(150, 7, 'Concepto', 1, 0, 'L', true);
        $pdf->Cell(30, 7, 'Importe', 1, 1, 'R', true);

        // Filas
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(150, 7, 'Mano de obra', 1, 0, 'L');
        $pdf->Cell(30, 7, number_format($mano_obra, 2) . ' EUR', 1, 1, 'R');

        $pdf->Cell(150, 7, 'Piezas / Recambios', 1, 0, 'L');
        $pdf->Cell(30, 7, number_format($piezas, 2) . ' EUR', 1, 1, 'R');

        $pdf->Cell(150, 7, 'Otros conceptos', 1, 0, 'L');
        $pdf->Cell(30, 7, number_format($otros, 2) . ' EUR', 1, 1, 'R');

        $pdf->Ln(2);

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(150, 7, 'Subtotal (sin IVA)', 1, 0, 'R');
        $pdf->Cell(30, 7, number_format($subtotal, 2) . ' EUR', 1, 1, 'R');

        $pdf->Cell(150, 7, 'IVA (21%)', 1, 0, 'R');
        $pdf->Cell(30, 7, number_format($iva, 2) . ' EUR', 1, 1, 'R');

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(150, 8, 'TOTAL', 1, 0, 'R');
        $pdf->Cell(30, 8, number_format($total, 2) . ' EUR', 1, 1, 'R');

        $pdf->Ln(8);
        $pdf->SetFont('helvetica', 'I', 9);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Cell(0, 6, 'Presupuesto valido por 30 dias desde la fecha de emision.', 0, 1, 'C');

        // Guardar PDF
        $carpeta = __DIR__ . "/../uploads/";
        if (!file_exists($carpeta)) mkdir($carpeta, 0777, true);

        $nombrePDF = "presupuesto_" . $numPresupuesto . "_" . time() . ".pdf";
        $rutaPDF   = $carpeta . $nombrePDF;
        $rutaBD    = "uploads/" . $nombrePDF;

        $pdf->Output($rutaPDF, 'F');

        // Guardar ruta en la BD
        $stmtUpd = $this->conn->prepare(
            "UPDATE citas SET presupuesto = ? WHERE id_cita = ? AND id_usuario = ?"
        );
        $stmtUpd->execute([$rutaBD, $id_cita, $this->userId()]);

        http_response_code(201);
        echo json_encode(["message" => "Cita creada", "presupuesto" => $rutaBD]);
    }

    public function deleteCita($id) {
        $cita = new Cita($this->conn);
        $cita->deleteCita($id, $this->userId());
        echo json_encode(["message" => "Cita eliminada"]);
    }

    public function updateEstado($id) {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data || empty($data["estado"])) {
            http_response_code(400);
            echo json_encode(["error" => "Estado requerido"]);
            return;
        }

        $stmt = $this->conn->prepare(
            "UPDATE citas SET estado = ? WHERE id_cita = ? AND id_usuario = ?"
        );
        $stmt->execute([$data["estado"], $id, $this->userId()]);
        echo json_encode(["message" => "Estado actualizado"]);
    }

    public function updateDesglose($id) {
        $data = json_decode(file_get_contents("php://input"), true);

        $mano_obra = (float)($data["mano_obra"] ?? 0);
        $piezas    = (float)($data["piezas"]    ?? 0);
        $otros     = (float)($data["otros"]     ?? 0);
        $subtotal  = $mano_obra + $piezas + $otros;
        $coste     = round($subtotal * 1.21, 2);

        $stmt = $this->conn->prepare(
            "UPDATE citas SET mano_obra = ?, piezas = ?, otros = ?, coste = ?
             WHERE id_cita = ? AND id_usuario = ?"
        );
        $stmt->execute([$mano_obra, $piezas, $otros, $coste, $id, $this->userId()]);

        echo json_encode(["message" => "Desglose actualizado", "coste_total" => $coste]);
    }

    public function subirPresupuesto($id) {
        if (!isset($_FILES["file"])) {
            http_response_code(400);
            echo json_encode(["error" => "No se envio archivo"]);
            return;
        }

        $file = $_FILES["file"];
        $ext  = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

        if ($ext !== "pdf") {
            http_response_code(400);
            echo json_encode(["error" => "Solo se permiten archivos PDF"]);
            return;
        }

        $nombre  = time() . "_" . basename($file["name"]);
        $carpeta = __DIR__ . "/../uploads/";
        $ruta    = $carpeta . $nombre;
        $rutaBD  = "uploads/" . $nombre;

        if (!file_exists($carpeta)) mkdir($carpeta, 0777, true);

        if (move_uploaded_file($file["tmp_name"], $ruta)) {
            $stmt = $this->conn->prepare(
                "UPDATE citas SET presupuesto = ? WHERE id_cita = ? AND id_usuario = ?"
            );
            $stmt->execute([$rutaBD, $id, $this->userId()]);
            echo json_encode(["message" => "PDF subido"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Error al mover el archivo"]);
        }
    }
}
