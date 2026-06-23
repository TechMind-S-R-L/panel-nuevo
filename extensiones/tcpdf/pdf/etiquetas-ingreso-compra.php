<?php

ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$esAdministrador = ($_SESSION["perfil"] ?? "") === "Administrador";
$esAlmacen = ($_SESSION["rol"] ?? "") === "almacen";
if (!$esAdministrador && !$esAlmacen) {
    http_response_code(403);
    die("No tiene permiso para imprimir estas etiquetas.");
}

require_once __DIR__ . "/../../../modelos/ModeloInventario.php";

$idCompra = (int)($_GET["compra"] ?? 0);
$idProducto = (int)($_GET["producto"] ?? 0);
$etiquetas = ModeloInventario::mdlEtiquetasIngresoCompra($idCompra, $idProducto);
if (!$etiquetas) {
    die("Esta recepciГіn todavГ­a no tiene cГіdigos registrados para imprimir.");
}

$producto = $etiquetas[0];
chdir(__DIR__);
require_once "tcpdf_include_notaventa.php";
require_once __DIR__ . '/pdf-empresa-config.php';

$pdf = new TCPDF("P", "mm", "A4", true, "UTF-8", false);
$pdf->SetCreator("TechMind");
$pdf->SetAuthor("TechMind S.R.L.");
$pdf->SetTitle("Etiquetas compra ".$producto["codigo_compra"]);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(7, 8, 7);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();

$columnas = 3;
$filas = 7;
$ancho = 64;
$alto = 38;
$espacioX = 3;
$espacioY = 2;
$porPagina = $columnas * $filas;
$nombre = mb_strtoupper((string)$producto["descripcion"], "UTF-8");
if (mb_strlen($nombre, "UTF-8") > 46) {
    $nombre = mb_substr($nombre, 0, 43, "UTF-8")."...";
}
$codigoGeneral = trim((string)$producto["codigo_producto_generico"]);
if ($codigoGeneral === "") {
    $codigoGeneral = (string)$producto["codigo"];
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

foreach ($etiquetas as $indice => $item) {
    if ($indice > 0 && $indice % $porPagina === 0) {
        $pdf->AddPage();
    }
    $codigo = (string)$item["codigo_barras_unico"];
    $posicion = $indice % $porPagina;
    $columna = $posicion % $columnas;
    $fila = floor($posicion / $columnas);
    $x = 7 + ($columna * ($ancho + $espacioX));
    $y = 8 + ($fila * ($alto + $espacioY));

    $pdf->SetDrawColor(160, 185, 205);
    $pdf->SetFillColor(250, 253, 255);
    $pdf->RoundedRect($x, $y, $ancho, $alto, 2.2, "1111", "DF");
    $pdf->SetTextColor(24, 75, 134);
    $pdf->SetFont("helvetica", "B", 7.5);
    $pdf->SetXY($x + 2, $y + 1.8);
    $pdf->Cell($ancho - 4, 4, tmPdfEmpresaTexto('nombre'), 0, 0, "C");
    $pdf->SetTextColor(28, 43, 56);
    $pdf->SetFont("helvetica", "B", 6.5);
    $pdf->SetXY($x + 2.5, $y + 5.5);
    $pdf->MultiCell($ancho - 5, 6, $nombre, 0, "C", false, 1, "", "", true, 0, false, true, 6, "M");
    $pdf->write1DBarcode($codigo, "C128", $x + 4, $y + 12, $ancho - 8, 14, 0.32, $style, "N");
    $pdf->SetTextColor(20, 35, 50);
    $pdf->SetFont("helvetica", "B", 7);
    $pdf->SetXY($x + 2, $y + 27);
    $pdf->Cell($ancho - 4, 4, $codigo, 0, 0, "C");
    $pdf->SetTextColor(83, 105, 122);
    $pdf->SetFont("helvetica", "", 5.7);
    $pdf->SetXY($x + 2, $y + 31);
    $pdf->Cell($ancho - 4, 3, "General: ".$codigoGeneral, 0, 0, "C");
    $pdf->SetXY($x + 2, $y + 34);
    $origen = $item["origen"] === "generado" ? "CГіdigo TechMind" : "CГіdigo escaneado";
    $pdf->Cell($ancho - 4, 3, $origen." В· Compra ".$producto["codigo_compra"], 0, 0, "C");
}

ob_end_clean();
$pdf->Output("etiquetas-compra-".$producto["codigo_compra"]."-".$producto["codigo"].".pdf", "I");
