<?php
ob_start();

require_once __DIR__ . "/../../../controladores/ventas.controlador.php";
require_once __DIR__ . "/../../../modelos/ventas.modelo.php";
require_once __DIR__ . "/../../../controladores/clientes.controlador.php";
require_once __DIR__ . "/../../../modelos/clientes.modelo.php";
require_once __DIR__ . "/../../../controladores/usuarios.controlador.php";
require_once __DIR__ . "/../../../modelos/usuarios.modelo.php";

function RotatedTextVentaDespacho($pdf, $x, $y, $txt, $angle) {
	$pdf->StartTransform();
	$pdf->Rotate($angle, $x, $y);
	$pdf->SetFont('helvetica', 'B', 55);
	$pdf->SetTextColor(50, 50, 50);
	$pdf->Text($x, $y, $txt);
	$pdf->StopTransform();
}

chdir(__DIR__);
require_once('tcpdf_include_notaventa.php');

$idVenta = isset($_GET["idVenta"]) && is_numeric($_GET["idVenta"]) ? (int)$_GET["idVenta"] : 0;
$codigo = $_GET["codigo"] ?? "";
$venta = $idVenta > 0 ? ControladorVentas::ctrMostrarVentas("id", $idVenta) : ControladorVentas::ctrMostrarVentas("codigo", $codigo);

if (!$venta) {
	die("Venta no encontrada");
}

$cliente = ControladorClientes::ctrMostrarClientes("id", $venta["id_cliente"]);
$vendedor = ControladorUsuarios::ctrMostrarUsuarios("id", $venta["id_vendedor"]);
$productos = json_decode($venta["productos"], true);
$productos = is_array($productos) ? $productos : [];
$fechaPago = $venta["fecha_pago"] ? substr($venta["fecha_pago"], 0, -3) : substr($venta["fecha"], 0, -8);

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->startPageGroup();
$pdf->AddPage();

$pdf->SetAlpha(0.1);
RotatedTextVentaDespacho($pdf, 35, 180, 'PAGADO', 45);
$pdf->Image('images/ICONO.png', 45, 80, 120);
$pdf->SetAlpha(1);

$pdf->SetFillColor(230, 240, 255);
$pdf->Rect(10, 10, 190, 30, 'F');
$pdf->Image('images/ICONO.png', 17, 13, 21);
$pdf->SetXY(40, 14);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(70, 130, 180);
$pdf->Cell(90, 7, 'TECHMIND S.R.L.', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 9);
$pdf->SetX(40);
$pdf->Cell(90, 5, 'Km 6 doble via la guardia, calle paraiso Nro 6387', 0, 1, 'L');
$pdf->SetX(40);
$pdf->Cell(90, 5, '(+591) 75556540 | (+591) 78572656', 0, 1, 'L');
$pdf->SetX(40);
$pdf->Cell(90, 5, 'techmind.srl.bo@gmail.com', 0, 1, 'L');

$pdf->SetXY(135, 14);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(70, 130, 180);
$pdf->Cell(60, 7, 'NOTA DE DESPACHO', 0, 1, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(135, 22);
$pdf->Cell(60, 6, 'NRO: '.$venta["codigo"], 0, 1, 'R');
$pdf->SetXY(135, 28);
$pdf->Cell(60, 6, 'FECHA: '.$fechaPago, 0, 1, 'R');
$pdf->SetTextColor(0);
$pdf->Ln(20);

$nombreCliente = htmlspecialchars($cliente["nombre"] ?? '', ENT_QUOTES, "UTF-8");
$documentoCliente = htmlspecialchars($cliente["documento"] ?? '', ENT_QUOTES, "UTF-8");
$nombreVendedor = htmlspecialchars($vendedor["nombre"] ?? '', ENT_QUOTES, "UTF-8");
$metodoPago = htmlspecialchars($venta["metodo_pago"] ?? '', ENT_QUOTES, "UTF-8");
$cambioVenta = number_format((float)$venta["cambio"], 2);

$infoTable = <<<EOF
<table style="font-size:10px;" cellspacing="0" cellpadding="4" border="1">
  <tr>
    <td style="border:1px solid #000; width:180px;">Cliente: <b>$nombreCliente</b></td>
    <td style="border:1px solid #000; width:180px;">CI/NIT: <b>$documentoCliente</b></td>
    <td style="border:1px solid #000; width:180px;">Estado: <b>PAGADO / POR ENTREGAR</b></td>
  </tr>
  <tr>
    <td style="border:1px solid #000; width:180px;">Vendedor: <b>$nombreVendedor</b></td>
    <td style="border:1px solid #000; width:180px;">Pago: <b>$metodoPago</b></td>
    <td style="border:1px solid #000; width:180px;">Cambio: <b>Bs $cambioVenta</b></td>
  </tr>
</table>
EOF;
$pdf->writeHTML($infoTable, false, false, false, false, '');

$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(100, 7, 'Producto autorizado', 1, 0, 'C', true);
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
$pdf->Cell(160, 7, 'TOTAL PAGADO', 1, 0, 'R');
$pdf->Cell(30, 7, "Bs ".number_format((float)$venta["total"], 2), 1, 1, 'C');

$pdf->Ln(24);
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(63, 5, '_____________________', 0, 0, 'C');
$pdf->Cell(64, 5, '_____________________', 0, 0, 'C');
$pdf->Cell(63, 5, '_____________________', 0, 1, 'C');
$pdf->Cell(63, 5, 'Firma cajero', 0, 0, 'C');
$pdf->Cell(64, 5, 'Sello caja', 0, 0, 'C');
$pdf->Cell(63, 5, 'Firma almacen', 0, 1, 'C');

ob_end_clean();
$pdf->Output('NotaDespacho.pdf', 'I');



