<?php
ob_start();

require_once __DIR__ . "/../../../controladores/servicios.controlador.php";
require_once __DIR__ . "/../../../modelos/servicios.modelo.php";
require_once __DIR__ . "/../../../controladores/clientes.controlador.php";
require_once __DIR__ . "/../../../modelos/clientes.modelo.php";
require_once __DIR__ . "/../../../controladores/usuarios.controlador.php";
require_once __DIR__ . "/../../../modelos/usuarios.modelo.php";

chdir(__DIR__);
require_once('tcpdf_include_notaventa.php');

$idServicio = isset($_GET["idServicio"]) ? (int)$_GET["idServicio"] : 0;
$servicio = ControladorServicios::ctrMostrarServicios("id", $idServicio);
if(!$servicio){ die("Servicio no encontrado"); }

$cliente = ControladorClientes::ctrMostrarClientes("id", $servicio["id_cliente"]);
$tecnico = !empty($servicio["id_tecnico"]) ? ControladorUsuarios::ctrMostrarUsuarios("id", $servicio["id_tecnico"]) : null;
$maps = ($servicio["latitud"] != "" && $servicio["longitud"] != "") ? "https://www.google.com/maps?q=".$servicio["latitud"].",".$servicio["longitud"] : "Sin ubicacion";

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->AddPage();
$pdf->SetFillColor(230, 240, 255);
$pdf->Rect(10, 10, 190, 30, 'F');
$pdf->Image('images/ICONO.png', 17, 13, 21);
$pdf->SetXY(40, 14);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(70, 130, 180);
$pdf->Cell(90, 7, 'TECHMIND S.R.L.', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 9);
$pdf->SetX(40);
$pdf->Cell(90, 5, 'ORDEN PARA TECNICO', 0, 1, 'L');
$pdf->SetXY(127, 14);
$pdf->SetFont('helvetica', 'B', 13);
$pdf->Cell(68, 7, 'ORDEN DE SERVICIO', 0, 1, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(135, 23);
$pdf->Cell(60, 6, 'NRO: '.$servicio["codigo"], 0, 1, 'R');
$pdf->SetTextColor(0);
$pdf->Ln(20);

$htmlDatos = '
<table cellpadding="5" border="1">
<tr><td><b>Tecnico:</b> '.htmlspecialchars($tecnico["nombre"] ?? "").'</td><td><b>Estado:</b> '.htmlspecialchars($servicio["estado_servicio"]).'</td></tr>
<tr><td><b>Cliente:</b> '.htmlspecialchars($cliente["nombre"] ?? "").'</td><td><b>Telefono:</b> '.htmlspecialchars($cliente["telefono"] ?? "").'</td></tr>
<tr><td colspan="2"><b>Direccion:</b> '.htmlspecialchars($servicio["direccion_instalacion"]).'</td></tr>
<tr><td colspan="2"><b>Referencia:</b> '.htmlspecialchars($servicio["referencia"]).'</td></tr>
<tr><td colspan="2"><b>Ubicacion:</b> '.htmlspecialchars($maps).'</td></tr>
<tr><td><b>Latitud:</b> '.htmlspecialchars($servicio["latitud"] ?? "").'</td><td><b>Longitud:</b> '.htmlspecialchars($servicio["longitud"] ?? "").'</td></tr>
</table>';

$pdf->writeHTML($htmlDatos, true, false, true, false, '');

if($maps != "Sin ubicacion"){
	$pdf->Ln(2);
	$pdf->SetFont('helvetica', 'B', 9);
	$pdf->Cell(0, 5, 'QR DE UBICACION', 0, 1, 'L');
	$qrY = $pdf->GetY() + 1;
	$qrStyle = array(
		'border' => 0,
		'vpadding' => 0,
		'hpadding' => 0,
		'fgcolor' => array(0, 0, 0),
		'bgcolor' => array(255, 255, 255),
		'module_width' => 1,
		'module_height' => 1
	);
	$pdf->Rect(15, $qrY, 32, 32, 'F');
	$pdf->write2DBarcode($maps, 'QRCODE,L', 17, $qrY + 2, 28, 28, $qrStyle, 'N');
	$pdf->SetFont('helvetica', '', 8);
	$pdf->SetXY(52, $qrY + 8);
	$pdf->MultiCell(145, 5, 'Escanee este codigo para abrir la ubicacion marcada anteriormente en el mapa.', 0, 'L');
	$pdf->SetY($qrY + 36);
}

$html = '
<h3>TRABAJO A REALIZAR</h3>
<table cellpadding="5" border="1" border="1">
<tr><td><b>Servicio:</b> '.htmlspecialchars($servicio["tipo_servicio"]).'</td><td><b>Tipo:</b> '.htmlspecialchars($servicio["tipo_instalacion"]).'</td></tr>
<tr><td><b>Cantidad camaras:</b> '.$servicio["cantidad_camaras"].'</td><td><b>Metros estimados:</b> '.$servicio["metros_distancia"].'</td></tr>
<tr><td colspan="2"><b>Preguntas al cliente:</b> '.nl2br(htmlspecialchars($servicio["preguntas_cliente"] ?? "")).'</td></tr>
<tr><td colspan="2"><b>Diagnostico inicial / alcance:</b> '.nl2br(htmlspecialchars($servicio["diagnostico_inicial"] ?? "")).'</td></tr>
<tr><td colspan="2"><b>Observaciones:</b> '.nl2br(htmlspecialchars($servicio["observaciones"])).'</td></tr>
</table>
<h3>INFORME TECNICO PARA EL CLIENTE</h3>
<table cellpadding="5" border="1" border="1">
<tr><td colspan="2"><b>Hallazgos:</b> '.nl2br(htmlspecialchars($servicio["hallazgos_tecnicos"] ?? "")).'</td></tr>
<tr><td colspan="2"><b>Trabajo realizado:</b> '.nl2br(htmlspecialchars($servicio["trabajo_realizado"] ?? "")).'</td></tr>
<tr><td colspan="2"><b>Recomendaciones / pendientes:</b> '.nl2br(htmlspecialchars($servicio["recomendaciones"] ?? "")).'</td></tr>
</table>
<br><br>
<br><br>
<table cellpadding="8" style="font-size:9px;"><tr><td align="center">_________________________<br>Firma tecnico</td><td align="center">_________________________<br>Conformidad cliente</td></tr></table>';

$pdf->writeHTML($html, true, false, true, false, '');
if(ob_get_length()){ ob_end_clean(); }
$pdf->Output('orden-servicio-'.$servicio["codigo"].'.pdf', 'I');



