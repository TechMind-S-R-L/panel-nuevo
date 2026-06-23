<?php
ob_start();

require_once __DIR__ . "/../../../controladores/cotizacion.controlador.php";
require_once __DIR__ . "/../../../modelos/cotizacion.modelo.php";
require_once __DIR__ . "/../../../controladores/clientes.controlador.php";
require_once __DIR__ . "/../../../modelos/clientes.modelo.php";
require_once __DIR__ . "/../../../controladores/usuarios.controlador.php";
require_once __DIR__ . "/../../../modelos/usuarios.modelo.php";

function RotatedTextCotizacion($pdf, $x, $y, $txt, $angle) {
	$pdf->StartTransform();
	$pdf->Rotate($angle, $x, $y);
	$pdf->SetFont('helvetica', 'B', 54);
	$pdf->SetTextColor(50, 50, 50);
	$pdf->Text($x, $y, $txt);
	$pdf->StopTransform();
}

chdir(__DIR__);
require_once('tcpdf_include_notaventa.php');
require_once __DIR__ . '/pdf-empresa-config.php';

$idCotizacion = $_GET["idCotizacion"] ?? "";
$codigoCotizacion = $_GET["codigoCotizacion"] ?? "";

if ($idCotizacion !== "") {
	$cotizacion = ControladorCotizacion::ctrMostrarCotizacion("id", $idCotizacion);
} else {
	$cotizacion = ControladorCotizacion::ctrMostrarCotizacion("codigo", $codigoCotizacion);
}

if (!$cotizacion) {
	die("Cotizacion no encontrada");
}

$cliente = ControladorClientes::ctrMostrarClientes("id", $cotizacion["id_cliente"]);
$vendedor = ControladorUsuarios::ctrMostrarUsuarios("id", $cotizacion["id_user"]);
$productos = json_decode($cotizacion["productos"], true);
$productos = is_array($productos) ? $productos : [];

$fechaCotizacion = !empty($cotizacion["fecha"]) ? date("d/m/Y", strtotime($cotizacion["fecha"])) : date("d/m/Y");
$validoHasta = !empty($cotizacion["valido_hasta"]) ? date("d/m/Y", strtotime($cotizacion["valido_hasta"])) : "No definido";
$condiciones = trim($cotizacion["condiciones"] ?? "");
if ($condiciones == "") {
	$condiciones = "Forma de pago: efectivo, transferencia o segun acuerdo con el cliente.\nForma de entrega: en instalaciones del cliente o punto acordado.\nPrecios: incluyen impuestos de ley.\nGarantia: segun condiciones del fabricante y servicio contratado.";
}

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->startPageGroup();
$pdf->AddPage();

$pdf->SetAlpha(0.08);
RotatedTextCotizacion($pdf, 30, 180, 'COTIZACION', 45);
$pdf->Image('images/ICONO.png', 45, 82, 120);
$pdf->SetAlpha(1);

$pdf->SetFillColor(230, 240, 255);
$pdf->Rect(10, 10, 190, 32, 'F');
$pdf->Image('images/ICONO.png', 17, 13, 21);
$pdf->SetXY(40, 14);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(70, 130, 180);
$pdf->Cell(90, 7, tmPdfEmpresaTexto('nombre'), 0, 1, 'L');
$pdf->SetFont('helvetica', '', 9);
$pdf->SetX(40);
$pdf->Cell(90, 5, tmPdfEmpresaTexto('direccion'), 0, 1, 'L');
$pdf->SetX(40);
$pdf->Cell(90, 5, tmPdfEmpresaTexto('telefono'), 0, 1, 'L');
$pdf->SetX(40);
$pdf->Cell(90, 5, tmPdfEmpresaTexto('correo'), 0, 1, 'L');

$pdf->SetXY(135, 12);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(70, 130, 180);
$pdf->Cell(65, 7, 'COTIZACION', 0, 1, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(135, 20);
$pdf->Cell(65, 6, 'NRO: '.$cotizacion["codigo"], 0, 1, 'R');
$pdf->SetXY(135, 26);
$pdf->Cell(65, 6, 'FECHA: '.$fechaCotizacion, 0, 1, 'R');
$pdf->SetXY(135, 32);
$pdf->Cell(65, 6, 'VALIDO HASTA: '.$validoHasta, 0, 1, 'R');
$pdf->SetTextColor(0);
$pdf->Ln(16);

$nombreCliente = htmlspecialchars($cliente["nombre"] ?? '', ENT_QUOTES, "UTF-8");
$documentoCliente = htmlspecialchars($cliente["documento"] ?? '', ENT_QUOTES, "UTF-8");
$telefonoCliente = htmlspecialchars($cliente["telefono"] ?? '', ENT_QUOTES, "UTF-8");
$direccionCliente = htmlspecialchars($cliente["direccion"] ?? '', ENT_QUOTES, "UTF-8");
$nombreVendedor = htmlspecialchars($vendedor["nombre"] ?? '', ENT_QUOTES, "UTF-8");

$infoTable = <<<EOF
<table style="font-size:10px;" cellspacing="0" cellpadding="4" border="1">
  <tr>
    <td style="border:1px solid #000; width:95px;"><b>Cliente:</b></td>
    <td style="border:1px solid #000; width:285px;">$nombreCliente</td>
    <td style="border:1px solid #000; width:70px;"><b>Documento:</b></td>
    <td style="border:1px solid #000; width:90px;">$documentoCliente</td>
  </tr>
  <tr>
    <td style="border:1px solid #000;"><b>Telefono:</b></td>
    <td style="border:1px solid #000;">$telefonoCliente</td>
    <td style="border:1px solid #000;"><b>Vendedor:</b></td>
    <td style="border:1px solid #000;">$nombreVendedor</td>
  </tr>
  <tr>
    <td style="border:1px solid #000;"><b>Direccion:</b></td>
    <td style="border:1px solid #000;" colspan="3">$direccionCliente</td>
  </tr>
</table>
EOF;

$pdf->writeHTML($infoTable, false, false, false, false, '');
$pdf->Ln(5);

$tablaX = 10;
$anchoNro = 12;
$anchoCantidad = 21;
$anchoDescripcion = 101;
$anchoPrecio = 28;
$anchoTotal = 28;

$pdf->SetX($tablaX);
$pdf->SetFillColor(47, 137, 184);
$pdf->SetTextColor(255);
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell($anchoNro, 7, '#', 1, 0, 'C', true);
$pdf->Cell($anchoCantidad, 7, 'Cant.', 1, 0, 'C', true);
$pdf->Cell($anchoDescripcion, 7, 'Descripcion', 1, 0, 'C', true);
$pdf->Cell($anchoPrecio, 7, 'P. Unit.', 1, 0, 'C', true);
$pdf->Cell($anchoTotal, 7, 'Total', 1, 1, 'C', true);
$pdf->SetTextColor(0);

foreach ($productos as $index => $item) {
	$nro = $index + 1;
	$cantidad = (float)($item["cantidad"] ?? 0);
	$descripcion = $item["descripcion"] ?? '';
	$precioUnitario = number_format((float)($item["precio"] ?? 0), 2);
	$totalProducto = number_format((float)($item["total"] ?? 0), 2);

	$pdf->SetFont('helvetica', '', 8);
	$alturaFila = max(7, $pdf->getStringHeight($anchoDescripcion, $descripcion) + 2);
	$x = $tablaX;
	$y = $pdf->GetY();

	$pdf->MultiCell($anchoNro, $alturaFila, $nro, 1, 'C', false, 0, $x, $y, true, 0, false, true, $alturaFila, 'M');
	$x += $anchoNro;
	$pdf->MultiCell($anchoCantidad, $alturaFila, $cantidad, 1, 'C', false, 0, $x, $y, true, 0, false, true, $alturaFila, 'M');
	$x += $anchoCantidad;
	$pdf->MultiCell($anchoDescripcion, $alturaFila, $descripcion, 1, 'L', false, 0, $x, $y, true, 0, false, true, $alturaFila, 'M');
	$x += $anchoDescripcion;
	$pdf->MultiCell($anchoPrecio, $alturaFila, 'Bs '.$precioUnitario, 1, 'R', false, 0, $x, $y, true, 0, false, true, $alturaFila, 'M');
	$x += $anchoPrecio;
	$pdf->MultiCell($anchoTotal, $alturaFila, 'Bs '.$totalProducto, 1, 'R', false, 1, $x, $y, true, 0, false, true, $alturaFila, 'M');
}

$neto = number_format((float)$cotizacion["neto"], 2);
$descuento = number_format((float)$cotizacion["descuento"], 2);
$total = number_format((float)$cotizacion["total"], 2);

$totalesX = $tablaX + $anchoNro + $anchoCantidad + $anchoDescripcion;

$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetX($totalesX);
$pdf->Cell($anchoPrecio, 6, 'Subtotal', 1, 0, 'L', true);
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell($anchoTotal, 6, 'Bs '.$neto, 1, 1, 'R', true);

$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetX($totalesX);
$pdf->Cell($anchoPrecio, 6, 'Descuento', 1, 0, 'L', true);
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell($anchoTotal, 6, 'Bs '.$descuento, 1, 1, 'R', true);

$pdf->SetFillColor(230, 242, 251);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetX($totalesX);
$pdf->Cell($anchoPrecio, 6, 'Total', 1, 0, 'L', true);
$pdf->Cell($anchoTotal, 6, 'Bs '.$total, 1, 1, 'R', true);
$pdf->Ln(5);

$condicionesHtml = nl2br(htmlspecialchars($condiciones, ENT_QUOTES, "UTF-8"));
$bloqueCondiciones = <<<EOF
<table style="font-size:9.5px;" cellspacing="0" cellpadding="5" border="1">
  <tr>
    <td style="border:1px solid #000;background-color:#f4f8fb;font-weight:bold;">Condiciones de la cotizacion</td>
  </tr>
  <tr>
    <td style="border:1px solid #000;">$condicionesHtml</td>
  </tr>
</table>
EOF;
$pdf->writeHTML($bloqueCondiciones, false, false, false, false, '');

$pdf->Ln(24);
$firmas = <<<EOF
<table style="font-size:9px;text-align:center;" cellspacing="0" cellpadding="8" border="1">
  <tr>
    <td style="width:170px;"></td>
    <td style="width:200px;"></td>
    <td style="width:170px;"></td>
  </tr>
  <tr>
    <td></td>
    <td style="border-top:1px solid #000;">Firma y sello TechMind</td>
    <td></td>
  </tr>
</table>
EOF;
$pdf->writeHTML($firmas, false, false, false, false, '');

if (ob_get_length()) {
	ob_end_clean();
}
$pdf->Output("cotizacion-".$cotizacion["codigo"].".pdf", 'I');



