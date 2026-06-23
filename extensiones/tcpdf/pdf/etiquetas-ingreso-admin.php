<?php

ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (($_SESSION["perfil"] ?? "") !== "Administrador") {
    http_response_code(403);
    die("Acceso exclusivo del administrador.");
}

require_once __DIR__ . "/../../../modelos/ModeloInventario.php";

$idIngreso = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$ingreso = $idIngreso > 0 ? ModeloInventario::mdlMostrarIngresoDirectoAdmin($idIngreso) : null;
if (!$ingreso) {
    die("Ingreso no encontrado.");
}

$codigos = json_decode($ingreso["codigos_unicos"], true);
if (!is_array($codigos) || !$codigos) {
    die("Este ingreso no tiene cГіdigos para imprimir.");
}

chdir(__DIR__);
require_once "tcpdf_include_notaventa.php";
require_once __DIR__ . '/pdf-empresa-config.php';

$pdf = new TCPDF("P", "mm", "A4", true, "UTF-8", false);
$pdf->SetCreator("TechMind");
$pdf->SetAuthor("TechMind S.R.L.");
$pdf->SetTitle("Etiquetas ingreso directo #".$idIngreso);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(7, 8, 7);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();

$columnas = 3;
$filas = 7;
$anchoEtiqueta = 64;
$altoEtiqueta = 38;
$separacionX = 3;
$separacionY = 2;
$inicioX = 7;
$inicioY = 8;
$porPagina = $columnas * $filas;
$nombre = mb_strtoupper((string)$ingreso["descripcion"], "UTF-8");
if (mb_strlen($nombre, "UTF-8") > 46) {
    $nombre = mb_substr($nombre, 0, 43, "UTF-8")."...";
}

$style = [
    "position" => "",
    "align" => "C",
    "stretch" => false,
    "fitwidth" => true,
    "cellfitalign" => "",
    "border" => false,
    "hpadding" => "auto",
    "vpadding" => "auto",
    "fgcolor" => [20, 35, 50],
    "bgcolor" => false,
    "text" => false,
    "font" => "helvetica",
    "fontsize" => 7,
    "stretchtext" => 4
];

foreach ($codigos as $indice => $codigo) {
    if ($indice > 0 && $indice % $porPagina === 0) {
        $pdf->AddPage();
    }
    $posicion = $indice % $porPagina;
    $columna = $posicion % $columnas;
    $fila = floor($posicion / $columnas);
    $x = $inicioX + ($columna * ($anchoEtiqueta + $separacionX));
    $y = $inicioY + ($fila * ($altoEtiqueta + $separacionY));

    $pdf->SetDrawColor(160, 185, 205);
    $pdf->SetFillColor(250, 253, 255);
    $pdf->RoundedRect($x, $y, $anchoEtiqueta, $altoEtiqueta, 2.2, "1111", "DF");

    $pdf->SetTextColor(24, 75, 134);
    $pdf->SetFont("helvetica", "B", 7.5);
    $pdf->SetXY($x + 2, $y + 1.8);
    $pdf->Cell($anchoEtiqueta - 4, 4, tmPdfEmpresaTexto('nombre'), 0, 0, "C");

    $pdf->SetTextColor(28, 43, 56);
    $pdf->SetFont("helvetica", "B", 6.5);
    $pdf->SetXY($x + 2.5, $y + 5.5);
    $pdf->MultiCell($anchoEtiqueta - 5, 6, $nombre, 0, "C", false, 1, "", "", true, 0, false, true, 6, "M");

    $pdf->write1DBarcode(
        (string)$codigo,
        "C128",
        $x + 4,
        $y + 12,
        $anchoEtiqueta - 8,
        14,
        0.32,
        $style,
        "N"
    );

    $pdf->SetTextColor(20, 35, 50);
    $pdf->SetFont("helvetica", "B", 7);
    $pdf->SetXY($x + 2, $y + 27);
    $pdf->Cell($anchoEtiqueta - 4, 4, (string)$codigo, 0, 0, "C");

    $pdf->SetTextColor(83, 105, 122);
    $pdf->SetFont("helvetica", "", 5.7);
    $pdf->SetXY($x + 2, $y + 31);
    $pdf->Cell(
        $anchoEtiqueta - 4,
        3,
        "General: ".$ingreso["codigo_general"]." В· Bs ".number_format((float)$ingreso["precio_venta"], 2),
        0,
        0,
        "C"
    );
    $pdf->SetXY($x + 2, $y + 34);
    $pdf->Cell($anchoEtiqueta - 4, 3, "Ingreso #".$idIngreso." В· ".date("d/m/Y", strtotime($ingreso["fecha"])), 0, 0, "C");
}

ob_end_clean();
$pdf->Output("etiquetas-ingreso-".$idIngreso.".pdf", "I");
