<?php
ob_start();

require_once __DIR__ . "/../../../controladores/servicios.controlador.php";
require_once __DIR__ . "/../../../modelos/servicios.modelo.php";
require_once __DIR__ . "/../../../controladores/clientes.controlador.php";
require_once __DIR__ . "/../../../modelos/clientes.modelo.php";
require_once __DIR__ . "/../../../controladores/usuarios.controlador.php";
require_once __DIR__ . "/../../../modelos/usuarios.modelo.php";

function RotatedTextServicio($pdf, $x, $y, $txt, $angle) {
	$pdf->StartTransform();
	$pdf->Rotate($angle, $x, $y);
	$pdf->SetFont('helvetica', 'B', 55);
	$pdf->SetTextColor(50, 50, 50);
	$pdf->Text($x, $y, $txt);
	$pdf->StopTransform();
}

chdir(__DIR__);
require_once('tcpdf_include_notaventa.php');

$idServicio = isset($_GET["idServicio"]) ? (int)$_GET["idServicio"] : 0;
$servicio = ControladorServicios::ctrMostrarServicios("id", $idServicio);
if(!$servicio){ die("Servicio no encontrado"); }

$cliente = ControladorClientes::ctrMostrarClientes("id", $servicio["id_cliente"]);
$vendedor = ControladorUsuarios::ctrMostrarUsuarios("id", $servicio["id_vendedor"]);
$tecnico = !empty($servicio["id_tecnico"]) ? ControladorUsuarios::ctrMostrarUsuarios("id", $servicio["id_tecnico"]) : null;

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->AddPage();
$pdf->SetAlpha(0.08);
RotatedTextServicio($pdf, 35, 180, 'SERVICIO', 45);
$pdf->Image('images/ICONO.png', 45, 85, 120);
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
$pdf->SetXY(127, 14);
$pdf->SetFont('helvetica', 'B', 13);
$pdf->Cell(68, 7, 'BOLETA DE SERVICIO', 0, 1, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(135, 23);
$pdf->Cell(60, 6, 'NRO: '.$servicio["codigo"], 0, 1, 'R');
$pdf->SetTextColor(0);
$pdf->Ln(20);

$html = '
<style>
	body, table, p { font-size:9px; line-height:1.35; }
	h2 { font-size:13px; }
	h3 { font-size:11px; }
</style>
<h3>DATOS DEL CLIENTE</h3>
<table cellpadding="5" border="1">
<tr><td><b>Cliente:</b> '.htmlspecialchars($cliente["nombre"] ?? "").'</td><td><b>Telefono:</b> '.htmlspecialchars($cliente["telefono"] ?? "").'</td></tr>
<tr><td colspan="2"><b>Direccion instalacion:</b> '.htmlspecialchars($servicio["direccion_instalacion"]).'</td></tr>
<tr><td colspan="2"><b>Referencia:</b> '.htmlspecialchars($servicio["referencia"]).'</td></tr>
</table>
<h3>DETALLE DEL SERVICIO</h3>
<table cellpadding="5" border="1" border="1">
<tr><td><b>Servicio:</b> '.htmlspecialchars($servicio["tipo_servicio"]).'</td><td><b>Instalacion:</b> '.htmlspecialchars($servicio["tipo_instalacion"]).'</td></tr>
<tr><td><b>Camaras:</b> '.$servicio["cantidad_camaras"].'</td><td><b>Metros:</b> '.$servicio["metros_distancia"].'</td></tr>
<tr><td><b>Vendedor:</b> '.htmlspecialchars($vendedor["nombre"] ?? "").'</td><td><b>Tecnico:</b> '.htmlspecialchars($tecnico["nombre"] ?? "Se asigna automaticamente al pagar").'</td></tr>
</table>
<h2 align="right">TOTAL: Bs '.number_format($servicio["total"], 2).'</h2>
<br><br><br><br>
<table cellpadding="8" border="1"><tr><td align="center">_________________________<br>Firma cliente</td><td align="center">_________________________<br>Firma caja</td></tr></table>';

$pdf->writeHTML($html, true, false, true, false, '');
if(ob_get_length()){ ob_end_clean(); }
$pdf->Output('boleta-servicio-'.$servicio["codigo"].'.pdf', 'I');



