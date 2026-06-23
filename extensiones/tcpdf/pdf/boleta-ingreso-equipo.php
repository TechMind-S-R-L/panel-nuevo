<?php
ob_start();

require_once __DIR__ . "/../../../controladores/servicios.controlador.php";
require_once __DIR__ . "/../../../modelos/servicios.modelo.php";
require_once __DIR__ . "/../../../controladores/clientes.controlador.php";
require_once __DIR__ . "/../../../modelos/clientes.modelo.php";
require_once __DIR__ . "/../../../controladores/usuarios.controlador.php";
require_once __DIR__ . "/../../../modelos/usuarios.modelo.php";

function RotatedTextIngresoEquipo($pdf, $x, $y, $txt, $angle) {
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

$idServicio = isset($_GET["idServicio"]) ? (int)$_GET["idServicio"] : 0;
$servicio = ControladorServicios::ctrMostrarServicios("id", $idServicio);
if(!$servicio){ die("Servicio no encontrado"); }

$equipo = ControladorServicios::ctrMostrarEquipoTaller($idServicio);
if(!$equipo){ die("Equipo no encontrado"); }

$cliente = ControladorClientes::ctrMostrarClientes("id", $servicio["id_cliente"]);
$vendedor = ControladorUsuarios::ctrMostrarUsuarios("id", $servicio["id_vendedor"]);
$tecnico = !empty($servicio["id_tecnico"]) ? ControladorUsuarios::ctrMostrarUsuarios("id", $servicio["id_tecnico"]) : null;

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->startPageGroup();
$pdf->AddPage();

$pdf->SetAlpha(0.1);
RotatedTextIngresoEquipo($pdf, 28, 180, 'INGRESO EQUIPO', 45);
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
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(70, 130, 180);
$pdf->Cell(70, 7, 'BOLETA DE INGRESO', 0, 1, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(135, 23);
$pdf->Cell(60, 6, 'CASO: '.$servicio["codigo"], 0, 1, 'R');
$pdf->SetXY(135, 29);
$pdf->Cell(60, 6, 'EQUIPO: '.$equipo["codigo_equipo"], 0, 1, 'R');
$pdf->SetTextColor(0);
$pdf->Ln(20);

$html = '
<table style="font-size:10px;" cellspacing="0" cellpadding="4" border="1">
<tr><td><b>Cliente:</b> '.htmlspecialchars($cliente["nombre"] ?? "").'</td><td><b>Telefono:</b> '.htmlspecialchars($cliente["telefono"] ?? "").'</td></tr>
<tr><td><b>Vendedor recibe:</b> '.htmlspecialchars($vendedor["nombre"] ?? "").'</td><td><b>Tecnico asignado:</b> '.htmlspecialchars($tecnico["nombre"] ?? "Pendiente").'</td></tr>
</table>
<br>
<h3>IDENTIFICACION DEL EQUIPO</h3>
<table style="font-size:10px;" cellspacing="0" cellpadding="4" border="1">
<tr><td><b>Codigo unico:</b> '.htmlspecialchars($equipo["codigo_equipo"]).'</td><td><b>Tipo:</b> '.htmlspecialchars($equipo["tipo_equipo"]).'</td></tr>
<tr><td><b>Marca:</b> '.htmlspecialchars($equipo["marca"]).'</td><td><b>Modelo:</b> '.htmlspecialchars($equipo["modelo"]).'</td></tr>
<tr><td colspan="2"><b>Serie / codigo visible:</b> '.htmlspecialchars($equipo["serie"]).'</td></tr>
<tr><td colspan="2"><b>Accesorios recibidos:</b> '.nl2br(htmlspecialchars($equipo["accesorios"])).'</td></tr>
<tr><td colspan="2"><b>Falla reportada:</b> '.nl2br(htmlspecialchars($equipo["falla_reportada"])).'</td></tr>
<tr><td colspan="2"><b>Estado fisico al recibir:</b> '.nl2br(htmlspecialchars($equipo["estado_fisico"])).'</td></tr>
</table>
<br>
<p><b>Importante:</b> El diagnostico tecnico se informara al cliente antes de realizar reparaciones. Si el cliente no autoriza, el equipo se devuelve segun estado de recepcion.</p>
<br><br>
<br><br>
<table cellpadding="8" border="1"><tr><td align="center">_________________________<br>Firma cliente</td><td align="center">_________________________<br>Recibido por TechMind</td></tr></table>';

$pdf->writeHTML($html, true, false, true, false, '');

if(!empty($equipo["foto_equipo"]) && file_exists(__DIR__ . "/../../../".$equipo["foto_equipo"])){
	$pdf->AddPage();
	$pdf->SetFont('helvetica', 'B', 12);
	$pdf->Cell(0, 8, 'FOTO DEL EQUIPO INGRESADO', 0, 1, 'C');
	$pdf->Image(__DIR__ . "/../../../".$equipo["foto_equipo"], 20, 30, 170, 0, '', '', '', true);
}

if(ob_get_length()){ ob_end_clean(); }
$pdf->Output('boleta-ingreso-equipo-'.$equipo["codigo_equipo"].'.pdf', 'I');



