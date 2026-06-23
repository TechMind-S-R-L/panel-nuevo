<?php
ob_start();

require_once __DIR__ . "/../../../controladores/compras.controlador.php";
require_once __DIR__ . "/../../../modelos/compras.modelo.php";
require_once __DIR__ . "/../../../controladores/usuarios.controlador.php";
require_once __DIR__ . "/../../../modelos/usuarios.modelo.php";
require_once __DIR__ . "/../../../controladores/proveedor.controlador.php";
require_once __DIR__ . "/../../../modelos/proveedor.modelo.php";

function RotatedTextDesembolso($pdf, $x, $y, $txt, $angle) {
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

$idCompra = isset($_GET["idCompra"]) ? (int)$_GET["idCompra"] : 0;
$compra = ControladorCompras::ctrMostrarCompras("id", $idCompra);
if(!$compra){ die("Solicitud no encontrada"); }

$usuario = ControladorUsuarios::ctrMostrarUsuarios("id", $compra["id_usuario"]);
$mensajero = !empty($compra["id_mensajero"]) ? ControladorUsuarios::ctrMostrarUsuarios("id", $compra["id_mensajero"]) : null;
$cajero = !empty($compra["id_cajero_desembolso"]) ? ControladorUsuarios::ctrMostrarUsuarios("id", $compra["id_cajero_desembolso"]) : null;
$proveedor = ControladorProveedor::ctrMostrarProveedor("id", $compra["id_proveedor"]);
$productos = json_decode($compra["productos"], true);
$estadosVisibles = array(
	"desembolsado" => "EN PROCESO DE COMPRA",
	"en_compra" => "EN DESEMBOLSO",
	"entregado_almacen" => "ENTREGADO A ALMACEN",
	"completado" => "COMPLETADO CON EXITO"
);
$estado = $estadosVisibles[trim($compra["estado"])] ?? strtoupper(str_replace("_", " ", $compra["estado"]));
$monto = !empty($compra["monto_desembolsado"]) ? (float)$compra["monto_desembolsado"] : (float)$compra["total"];

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->startPageGroup();
$pdf->AddPage();

$pdf->SetAlpha(0.1);
RotatedTextDesembolso($pdf, 32, 180, 'DESEMBOLSO', 45);
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

$pdf->SetXY(120, 14);
$pdf->SetFont('helvetica', 'B', 13);
$pdf->SetTextColor(70, 130, 180);
$pdf->Cell(75, 7, 'CONSTANCIA DE DESEMBOLSO', 0, 1, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(135, 23);
$pdf->Cell(60, 6, 'SOLICITUD: '.$compra["codigo"], 0, 1, 'R');
$pdf->SetXY(135, 29);
$pdf->Cell(60, 6, 'ESTADO: '.$estado, 0, 1, 'R');
$pdf->SetTextColor(0);
$pdf->Ln(20);

$detalle = '';
if(is_array($productos)){
	foreach($productos as $producto){
		$detalle .= '<tr>
			<td width="70%">'.htmlspecialchars($producto["descripcion"] ?? "Producto", ENT_QUOTES, "UTF-8").'</td>
			<td width="30%" align="center">'.(int)($producto["cantidad"] ?? 0).'</td>
		</tr>';
	}
}

$html = '
<table style="font-size:10px;" cellspacing="0" cellpadding="4" border="1">
  <tr>
    <td><b>Solicitante:</b> '.htmlspecialchars($usuario["nombre"] ?? "", ENT_QUOTES, "UTF-8").'</td>
    <td><b>Mensajero:</b> '.htmlspecialchars($mensajero["nombre"] ?? "Pendiente", ENT_QUOTES, "UTF-8").'</td>
  </tr>
  <tr>
    <td><b>Cajero:</b> '.htmlspecialchars($cajero["nombre"] ?? "Pendiente de registrar", ENT_QUOTES, "UTF-8").'</td>
    <td><b>Proveedor:</b> '.htmlspecialchars($proveedor["nombre"] ?? "", ENT_QUOTES, "UTF-8").'</td>
  </tr>
  <tr>
    <td><b>Monto a entregar:</b> Bs '.number_format($monto, 2).'</td>
    <td><b>Fecha desembolso:</b> '.htmlspecialchars($compra["fecha_desembolso"] ?? "Pendiente", ENT_QUOTES, "UTF-8").'</td>
  </tr>
</table>
<br>
<h3>PRODUCTOS AUTORIZADOS PARA COMPRA</h3>
<table style="font-size:10px;" cellspacing="0" cellpadding="4" border="1">
  <tr style="font-weight:bold;background-color:#f2f2f2;">
    <td width="70%">Producto</td>
    <td width="30%" align="center">Cantidad</td>
  </tr>
  '.$detalle.'
</table>
<br>
<p><b>Uso:</b> Constancia de entrega de efectivo de caja al mensajero para ejecutar la compra aprobada.</p>
<br><br><br><br>
<table style="font-size:9px;" cellpadding="8">
  <tr>
    <td align="center">_________________________<br>Firma cajero</td>
    <td align="center">_________________________<br>Firma mensajero</td>
  </tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
if(ob_get_length()){ ob_end_clean(); }
$pdf->Output('boleta-desembolso-'.$compra["codigo"].'.pdf', 'I');



