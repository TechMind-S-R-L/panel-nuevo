<?php
ob_start();

require_once __DIR__ . "/../../../controladores/servicios.controlador.php";
require_once __DIR__ . "/../../../modelos/servicios.modelo.php";
require_once __DIR__ . "/../../../controladores/clientes.controlador.php";
require_once __DIR__ . "/../../../modelos/clientes.modelo.php";
require_once __DIR__ . "/../../../controladores/usuarios.controlador.php";
require_once __DIR__ . "/../../../modelos/usuarios.modelo.php";

function textoBoletaConformidad($valor){
	return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

function contenidoBoletaConformidad($valor, $lineas = 3){
	$valor = trim((string)$valor);
	if($valor !== ""){
		return nl2br(textoBoletaConformidad($valor));
	}
	return str_repeat("<br>", $lineas);
}

function marcaAguaBoletaConformidad($pdf, $x, $y, $texto, $angulo){
	$pdf->StartTransform();
	$pdf->Rotate($angulo, $x, $y);
	$pdf->SetFont("helvetica", "B", 44);
	$pdf->SetTextColor(50, 50, 50);
	$pdf->Text($x, $y, $texto);
	$pdf->StopTransform();
}

chdir(__DIR__);
require_once("tcpdf_include_notaventa.php");

$idServicio = isset($_GET["idServicio"]) ? (int)$_GET["idServicio"] : 0;
$servicio = ControladorServicios::ctrMostrarServicios("id", $idServicio);

if(!$servicio){
	die("Servicio no encontrado");
}

if(($servicio["tipo_servicio"] ?? "") === "Soporte tecnico en taller" || stripos((string)($servicio["tipo_servicio"] ?? ""), "software") !== false){
	die("Esta boleta solo corresponde a servicios tecnicos en campo");
}

$cliente = ControladorClientes::ctrMostrarClientes("id", $servicio["id_cliente"]);
$tecnico = !empty($servicio["id_tecnico"])
	? ControladorUsuarios::ctrMostrarUsuarios("id", $servicio["id_tecnico"])
	: null;

$fechaOrden = !empty($servicio["fecha_pago"])
	? $servicio["fecha_pago"]
	: ($servicio["fecha"] ?? date("Y-m-d H:i:s"));
$fechaOrden = date("d/m/Y H:i", strtotime($fechaOrden));

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, "UTF-8", false);
$pdf->startPageGroup();
$pdf->AddPage();

$pdf->SetAlpha(0.08);
marcaAguaBoletaConformidad($pdf, 23, 185, "CONFORMIDAD", 45);
$pdf->Image("images/ICONO.png", 45, 85, 120);
$pdf->SetAlpha(1);

$pdf->SetFillColor(230, 240, 255);
$pdf->Rect(10, 10, 190, 30, "F");
$pdf->Image("images/ICONO.png", 17, 13, 21);
$pdf->SetXY(40, 14);
$pdf->SetFont("helvetica", "B", 14);
$pdf->SetTextColor(70, 130, 180);
$pdf->Cell(90, 7, "TECHMIND S.R.L.", 0, 1, "L");
$pdf->SetFont("helvetica", "", 9);
$pdf->SetX(40);
$pdf->Cell(90, 5, "Km 6 doble via la guardia, calle paraiso Nro 6387", 0, 1, "L");
$pdf->SetX(40);
$pdf->Cell(90, 5, "(+591) 75556540 | (+591) 78572656", 0, 1, "L");
$pdf->SetX(40);
$pdf->Cell(90, 5, "techmind.srl.bo@gmail.com", 0, 1, "L");

$pdf->SetXY(112, 12);
$pdf->SetFont("helvetica", "B", 12);
$pdf->Cell(83, 7, "BOLETA DE CONFORMIDAD", 0, 1, "R");
$pdf->SetFont("helvetica", "", 9);
$pdf->SetXY(125, 22);
$pdf->Cell(70, 6, "ORDEN: ".textoBoletaConformidad($servicio["codigo"] ?? ""), 0, 1, "R");
$pdf->SetXY(125, 28);
$pdf->Cell(70, 6, "FECHA: ".$fechaOrden, 0, 1, "R");
$pdf->SetTextColor(0);
$pdf->Ln(20);

$cantidad = (int)($servicio["cantidad_camaras"] ?? 0);
$metros = (float)($servicio["metros_distancia"] ?? 0);
$alcanceCuantitativo = array();
if($cantidad > 0){
	$alcanceCuantitativo[] = "Cantidad/equipos: ".$cantidad;
}
if($metros > 0){
	$alcanceCuantitativo[] = "Metros estimados: ".number_format($metros, 2, ".", "");
}
$alcanceCuantitativo = count($alcanceCuantitativo) ? implode(" | ", $alcanceCuantitativo) : "Segun alcance de la orden";

$html = '
<style>
	body, table, p { font-size:8.5px; line-height:1.25; }
	h3 { font-size:10px; color:#4682b4; }
	.declaracion { font-size:8.5px; line-height:1.3; }
</style>
<h3>DATOS DE LA INSTALACION</h3>
<table cellspacing="0" cellpadding="5" border="1">
	<tr>
		<td><b>Cliente:</b> '.textoBoletaConformidad($cliente["nombre"] ?? "").'</td>
		<td><b>CI/NIT:</b> '.textoBoletaConformidad($cliente["documento"] ?? "").'</td>
	</tr>
	<tr>
		<td><b>Telefono:</b> '.textoBoletaConformidad($cliente["telefono"] ?? "").'</td>
		<td><b>Tecnico:</b> '.textoBoletaConformidad($tecnico["nombre"] ?? "Pendiente").'</td>
	</tr>
	<tr><td colspan="2"><b>Direccion:</b> '.textoBoletaConformidad($servicio["direccion_instalacion"] ?? "").'</td></tr>
	<tr><td colspan="2"><b>Referencia:</b> '.textoBoletaConformidad($servicio["referencia"] ?? "").'</td></tr>
</table>

<h3>ALCANCE SOLICITADO</h3>
<table cellspacing="0" cellpadding="5" border="1">
	<tr>
		<td><b>Servicio:</b> '.textoBoletaConformidad($servicio["tipo_servicio"] ?? "").'</td>
		<td><b>Tipo:</b> '.textoBoletaConformidad($servicio["tipo_instalacion"] ?? "").'</td>
	</tr>
	<tr><td colspan="2"><b>Referencia cuantitativa:</b> '.textoBoletaConformidad($alcanceCuantitativo).'</td></tr>
	<tr><td colspan="2"><b>Solicitud / preguntas del cliente:</b><br>'.contenidoBoletaConformidad($servicio["preguntas_cliente"] ?? "", 1).'</td></tr>
	<tr><td colspan="2"><b>Diagnostico inicial / alcance previsto:</b><br>'.contenidoBoletaConformidad($servicio["diagnostico_inicial"] ?? "", 1).'</td></tr>
</table>

<h3>CONSTANCIA DEL TRABAJO EN CAMPO</h3>
<table cellspacing="0" cellpadding="5" border="1">
	<tr><td><b>Trabajo ejecutado y equipos instalados:</b><br>'.contenidoBoletaConformidad($servicio["trabajo_realizado"] ?? "", 2).'</td></tr>
	<tr><td><b>Pruebas realizadas y resultado:</b><br><br></td></tr>
	<tr><td><b>Observaciones, recomendaciones o pendientes:</b><br>'.contenidoBoletaConformidad($servicio["recomendaciones"] ?? "", 1).'</td></tr>
</table>

<h3>CONFORMIDAD DEL CLIENTE</h3>
<p class="declaracion">Declaro que TechMind S.R.L. realizo el servicio descrito, explico su funcionamiento y efectuo las pruebas correspondientes. Recibo el trabajo dejando constancia de las observaciones anotadas.</p>

<table cellspacing="0" cellpadding="4" border="1">
	<tr>
		<td width="50%"><b>Resultado:</b> &#9633; Conforme &nbsp; &#9633; Con observaciones</td>
		<td width="50%"><b>Conclusion:</b> ____ / ____ / ______ &nbsp; ____ : ____</td>
	</tr>
	<tr>
		<td><br><br>_____________________________<br><b>Firma del tecnico</b><br>'.textoBoletaConformidad($tecnico["nombre"] ?? "").'</td>
		<td><br><br>_____________________________<br><b>Firma del cliente / responsable</b><br>Nombre y CI: ______________________</td>
	</tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, "");

if(ob_get_length()){
	ob_end_clean();
}

$pdf->Output("boleta-conformidad-instalacion-".($servicio["codigo"] ?? $idServicio).".pdf", "I");
