<?php
ob_start();

require_once __DIR__ . "/../../../controladores/servicios.controlador.php";
require_once __DIR__ . "/../../../modelos/servicios.modelo.php";
require_once __DIR__ . "/../../../controladores/clientes.controlador.php";
require_once __DIR__ . "/../../../modelos/clientes.modelo.php";
require_once __DIR__ . "/../../../controladores/usuarios.controlador.php";
require_once __DIR__ . "/../../../modelos/usuarios.modelo.php";

function RotatedTextTaller($pdf, $x, $y, $txt, $angle) {
	$pdf->StartTransform();
	$pdf->Rotate($angle, $x, $y);
	$pdf->SetFont('helvetica', 'B', 48);
	$pdf->SetTextColor(50, 50, 50);
	$pdf->Text($x, $y, $txt);
	$pdf->StopTransform();
}

chdir(__DIR__);
require_once('tcpdf_include_notaventa.php');

$idServicio = isset($_GET["idServicio"]) ? (int)$_GET["idServicio"] : 0;
$tipo = $_GET["tipo"] ?? "diagnostico";
$servicio = ControladorServicios::ctrMostrarServicios("id", $idServicio);
if(!$servicio){ die("Servicio no encontrado"); }

$equipo = ControladorServicios::ctrMostrarEquipoTaller($idServicio);
if(!$equipo){ die("Equipo no encontrado"); }

$cliente = ControladorClientes::ctrMostrarClientes("id", $servicio["id_cliente"]);
$tecnico = !empty($servicio["id_tecnico"]) ? ControladorUsuarios::ctrMostrarUsuarios("id", $servicio["id_tecnico"]) : null;

$titulos = array(
	"diagnostico" => "BOLETA DE DIAGNOSTICO",
	"notificacion" => "BOLETA DE NOTIFICACION",
	"correctivo" => "BOLETA DE SOPORTE CORRECTIVO",
	"devolucion" => "BOLETA DE DEVOLUCION"
);
$titulo = $titulos[$tipo] ?? $titulos["diagnostico"];

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->startPageGroup();
$pdf->AddPage();

$pdf->SetAlpha(0.1);
RotatedTextTaller($pdf, 32, 180, 'SOPORTE TECNICO', 45);
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
$pdf->SetXY(120, 12);
$pdf->SetFont('helvetica', 'B', 13);
$pdf->Cell(80, 7, $titulo, 0, 1, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(135, 23);
$pdf->Cell(60, 6, 'CASO: '.$servicio["codigo"], 0, 1, 'R');
$pdf->SetXY(135, 29);
$pdf->Cell(60, 6, 'EQUIPO: '.$equipo["codigo_equipo"], 0, 1, 'R');
$pdf->SetTextColor(0);
$pdf->Ln(20);

$respuesta = $equipo["respuesta_cliente"] == "conforme" ? "Conforme, autoriza reparacion" : ($equipo["respuesta_cliente"] == "no_conforme" ? "No conforme, solicita devolucion" : "Pendiente");

$html = '
<table style="font-size:10px;" cellspacing="0" cellpadding="4" border="1">
<tr><td><b>Cliente:</b> '.htmlspecialchars($cliente["nombre"] ?? "").'</td><td><b>Telefono:</b> '.htmlspecialchars($cliente["telefono"] ?? "").'</td></tr>
<tr><td><b>Tecnico:</b> '.htmlspecialchars($tecnico["nombre"] ?? "").'</td><td><b>Estado equipo:</b> '.htmlspecialchars($equipo["estado_equipo"]).'</td></tr>
<tr><td><b>Equipo:</b> '.htmlspecialchars($equipo["tipo_equipo"]." ".$equipo["marca"]." ".$equipo["modelo"]).'</td><td><b>Serie:</b> '.htmlspecialchars($equipo["serie"]).'</td></tr>
</table>
<h3>DIAGNOSTICO</h3>
<table style="font-size:10px;" cellspacing="0" cellpadding="4" border="1" border="1">
<tr><td><b>Falla reportada:</b><br>'.nl2br(htmlspecialchars($equipo["falla_reportada"])).'</td></tr>
<tr><td><b>Diagnostico tecnico:</b><br>'.nl2br(htmlspecialchars($equipo["diagnostico_tecnico"])).'</td></tr>
</table>
<h3>NOTIFICACION AL CLIENTE</h3>
<table style="font-size:10px;" cellspacing="0" cellpadding="4" border="1" border="1">
<tr><td><b>Notificado:</b> '.($equipo["notificado_cliente"] ? "Si" : "No").'</td><td><b>Fecha:</b> '.htmlspecialchars($equipo["fecha_notificacion"]).'</td></tr>
<tr><td colspan="2"><b>Respuesta:</b> '.htmlspecialchars($respuesta).'</td></tr>
<tr><td colspan="2"><b>Detalle:</b><br>'.nl2br(htmlspecialchars($equipo["detalle_notificacion"])).'</td></tr>
</table>';

if($tipo == "correctivo" || $tipo == "devolucion"){
	$html .= '
<h3>RESULTADO</h3>
<table style="font-size:10px;" cellspacing="0" cellpadding="4" border="1" border="1">
<tr><td><b>Trabajo realizado:</b><br>'.nl2br(htmlspecialchars($equipo["reparacion_realizada"])).'</td></tr>
<tr><td><b>Repuestos/componentes:</b><br>'.nl2br(htmlspecialchars($equipo["repuestos_detalle"])).'</td></tr>
<tr><td><b>Garantia / condiciones:</b><br>'.nl2br(htmlspecialchars($equipo["garantia_detalle"])).'</td></tr>
</table>';
}

$html .= '<br><br><br><br><table cellpadding="8" style="font-size:9px;"><tr><td align="center">_________________________<br>Firma cliente</td><td align="center">_________________________<br>Firma tecnico</td></tr></table>';

$pdf->writeHTML($html, true, false, true, false, '');
if(ob_get_length()){ ob_end_clean(); }
$pdf->Output('boleta-taller-'.$tipo.'-'.$equipo["codigo_equipo"].'.pdf', 'I');



