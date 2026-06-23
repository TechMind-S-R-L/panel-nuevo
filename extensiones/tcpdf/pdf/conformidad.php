<?php
ob_start();

require_once __DIR__ . "/../../../controladores/ventas.controlador.php";
require_once __DIR__ . "/../../../modelos/ventas.modelo.php";
require_once __DIR__ . "/../../../controladores/clientes.controlador.php";
require_once __DIR__ . "/../../../modelos/clientes.modelo.php";
require_once __DIR__ . "/../../../controladores/usuarios.controlador.php";
require_once __DIR__ . "/../../../modelos/usuarios.modelo.php";

function RotatedTextControlEntrega($pdf, $x, $y, $txt, $angle) {
	$pdf->StartTransform();
	$pdf->Rotate($angle, $x, $y);
	$pdf->SetFont('helvetica', 'B', 50);
	$pdf->SetTextColor(50, 50, 50);
	$pdf->Text($x, $y, $txt);
	$pdf->StopTransform();
}

chdir(__DIR__);
require_once('tcpdf_include_notaventa.php');
require_once __DIR__ . '/pdf-empresa-config.php';

$idVenta = isset($_GET["idVenta"]) && is_numeric($_GET["idVenta"]) ? (int)$_GET["idVenta"] : 0;
$venta = ControladorVentas::ctrMostrarVentas("id", $idVenta);

if (!$venta) {
	die("Venta no encontrada");
}

$cliente = ControladorClientes::ctrMostrarClientes("id", $venta["id_cliente"]);
$vendedor = ControladorUsuarios::ctrMostrarUsuarios("id", $venta["id_vendedor"]);
$productos = json_decode($venta["productos"], true);
$productos = is_array($productos) ? $productos : [];
$codigosEntregados = ControladorVentas::ctrMostrarCodigosDespacho((int)$idVenta);
$fechaEntrega = $venta["fecha_despacho"] ? substr($venta["fecha_despacho"], 0, -3) : substr($venta["fecha"], 0, -8);

$codigosPorProducto = [];
foreach ($codigosEntregados as $codigoEntregado) {
	$idProducto = (int)$codigoEntregado["id_producto"];
	if (!isset($codigosPorProducto[$idProducto])) {
		$codigosPorProducto[$idProducto] = [];
	}
	$codigosPorProducto[$idProducto][] = $codigoEntregado["codigo_barras_unico"];
}

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->startPageGroup();
$pdf->AddPage();

$pdf->SetAlpha(0.1);
RotatedTextControlEntrega($pdf, 30, 180, 'CONTROL INTERNO', 45);
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

$pdf->SetXY(130, 12);
$pdf->SetFont('helvetica', 'B', 13);
$pdf->SetTextColor(70, 130, 180);
$pdf->Cell(70, 7, 'CONTROL DE ENTREGA', 0, 1, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(135, 22);
$pdf->Cell(60, 6, 'NRO: '.$venta["codigo"], 0, 1, 'R');
$pdf->SetXY(135, 28);
$pdf->Cell(60, 6, 'FECHA: '.$fechaEntrega, 0, 1, 'R');
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
    <td style="border:1px solid #000; width:180px;">Estado: <b>ENTREGADO</b></td>
  </tr>
  <tr>
    <td style="border:1px solid #000; width:270px;">Vendedor: <b>$nombreVendedor</b></td>
    <td colspan="2" style="border:1px solid #000; width:270px;">Uso: <b>Control interno de codigos entregados</b></td>
  </tr>
</table>
EOF;
$pdf->writeHTML($infoTable, false, false, false, false, '');

$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(75, 7, 'Producto entregado', 1, 0, 'C', true);
$pdf->Cell(22, 7, 'Cantidad', 1, 0, 'C', true);
$pdf->Cell(93, 7, 'Codigos entregados', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 8);
if (is_array($productos)) {
	foreach ($productos as $item) {
		$idProducto = (int)($item["id"] ?? 0);
		$descripcion = $item["descripcion"] ?? "";
		$cantidad = (int)($item["cantidad"] ?? 0);
		$codigos = implode("\n", $codigosPorProducto[$idProducto] ?? []);

		$yInicio = $pdf->GetY();
		$pdf->MultiCell(75, 12, $descripcion, 1, 'L', false, 0);
		$pdf->MultiCell(22, 12, (string)$cantidad, 1, 'C', false, 0);
		$pdf->MultiCell(93, 12, $codigos, 1, 'L', false, 1);
		$pdf->SetY(max($pdf->GetY(), $yInicio + 12));
	}
}

$pdf->Ln(24);
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(63, 5, '_____________________', 0, 0, 'C');
$pdf->Cell(64, 5, '_____________________', 0, 0, 'C');
$pdf->Cell(63, 5, '_____________________', 0, 1, 'C');
$pdf->Cell(63, 5, 'Firma cliente', 0, 0, 'C');
$pdf->Cell(64, 5, 'Firma almacen', 0, 0, 'C');
$pdf->Cell(63, 5, 'Control interno', 0, 1, 'C');

ob_end_clean();
$pdf->Output('ControlEntrega.pdf', 'I');



