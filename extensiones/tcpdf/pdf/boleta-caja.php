<?php
ob_start();

require_once __DIR__ . "/../../../controladores/ventas.controlador.php";
require_once __DIR__ . "/../../../modelos/ventas.modelo.php";
require_once __DIR__ . "/../../../controladores/clientes.controlador.php";
require_once __DIR__ . "/../../../modelos/clientes.modelo.php";
require_once __DIR__ . "/../../../controladores/usuarios.controlador.php";
require_once __DIR__ . "/../../../modelos/usuarios.modelo.php";

function RotatedTextVentaCaja($pdf, $x, $y, $txt, $angle) {
	$pdf->StartTransform();
	$pdf->Rotate($angle, $x, $y);
	$pdf->SetFont('helvetica', 'B', 55);
	$pdf->SetTextColor(50, 50, 50);
	$pdf->Text($x, $y, $txt);
	$pdf->StopTransform();
}

chdir(__DIR__);
require_once('tcpdf_include_notaventa.php');
require_once __DIR__ . '/pdf-empresa-config.php';

$idVenta = isset($_GET["idVenta"]) && is_numeric($_GET["idVenta"]) ? (int)$_GET["idVenta"] : 0;
$codigo = $_GET["codigo"] ?? "";
$venta = $idVenta > 0 ? ControladorVentas::ctrMostrarVentas("id", $idVenta) : ControladorVentas::ctrMostrarVentas("codigo", $codigo);

if (!$venta) {
	die("Venta no encontrada");
}

$cliente = ControladorClientes::ctrMostrarClientes("id", $venta["id_cliente"]);
$vendedor = ControladorUsuarios::ctrMostrarUsuarios("id", $venta["id_vendedor"]);
$productos = json_decode($venta["productos"], true);
$fechaVenta = substr($venta["fecha"], 0, -8);

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->startPageGroup();
$pdf->AddPage();

$pdf->SetAlpha(0.1);
RotatedTextVentaCaja($pdf, 35, 180, 'POR COBRAR', 45);
$pdf->Image('images/ICONO.png', 45, 80, 120);
$pdf->SetAlpha(1);

$pdf->SetFillColor(230, 240, 255);
$pdf->Rect(10, 10, 190, 30, 'F');
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

$pdf->SetXY(135, 14);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(70, 130, 180);
$pdf->Cell(60, 7, 'BOLETA DE CAJA', 0, 1, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(135, 22);
$pdf->Cell(60, 6, 'NRO: '.$venta["codigo"], 0, 1, 'R');
$pdf->SetXY(135, 28);
$pdf->Cell(60, 6, 'FECHA: '.$fechaVenta, 0, 1, 'R');
$pdf->SetTextColor(0);
$pdf->Ln(20);

$nombreCliente = htmlspecialchars($cliente["nombre"] ?? '', ENT_QUOTES, "UTF-8");
$documentoCliente = htmlspecialchars($cliente["documento"] ?? '', ENT_QUOTES, "UTF-8");
$nombreVendedor = htmlspecialchars($vendedor["nombre"] ?? '', ENT_QUOTES, "UTF-8");

$infoTable = <<<EOF
<table style="font-size:10px;" cellspacing="0" cellpadding="4" border="1">
  <tr>
    <td style="border:1px solid #000; width:180px;">Cliente: <b>$nombreCliente</b></td>
    <td style="border:1px solid #000; width:180px;">CI/NIT: <b>$documentoCliente</b></td>
    <td style="border:1px solid #000; width:180px;">Estado: <b>POR COBRAR</b></td>
  </tr>
  <tr>
    <td style="border:1px solid #000; width:270px;">Vendedor: <b>$nombreVendedor</b></td>
    <td colspan="2" style="border:1px solid #000; width:270px;">Documento: <b>Boleta para cobro en caja</b></td>
  </tr>
</table>
EOF;
$pdf->writeHTML($infoTable, false, false, false, false, '');

$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(100, 7, 'Producto', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Cantidad', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Valor Unit.', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Valor Total', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 9);
if (is_array($productos)) {
	foreach ($productos as $item) {
		$descripcion = $item["descripcion"] ?? "";
		$cantidad = (int)($item["cantidad"] ?? 0);
		$precioTotal = number_format((float)($item["total"] ?? 0), 2);
		$valorUnitario = number_format((float)($item["precio"] ?? 0), 2);

		$pdf->Cell(100, 6, $descripcion, 1, 0, 'L');
		$pdf->Cell(30, 6, $cantidad, 1, 0, 'C');
		$pdf->Cell(30, 6, "Bs $valorUnitario", 1, 0, 'C');
		$pdf->Cell(30, 6, "Bs $precioTotal", 1, 1, 'C');
	}
}

$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(160, 7, 'TOTAL A COBRAR', 1, 0, 'R');
$pdf->Cell(30, 7, "Bs ".number_format((float)$venta["total"], 2), 1, 1, 'C');

$pdf->Ln(5);
$pdf->SetFillColor(255, 245, 220);
$pdf->SetTextColor(180, 95, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->MultiCell(190, 8, 'NOTA: El cliente debe entregar esta boleta al cajero para realizar el cobro correspondiente.', 1, 'C', true);
$pdf->SetTextColor(0);

$pdf->Ln(24);
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(95, 5, '___________________________', 0, 0, 'C');
$pdf->Cell(95, 5, '___________________________', 0, 1, 'C');
$pdf->Cell(95, 5, 'Firma del cajero', 0, 0, 'C');
$pdf->Cell(95, 5, 'Sello de caja', 0, 1, 'C');

ob_end_clean();
$pdf->Output('BoletaCaja.pdf', 'I');



