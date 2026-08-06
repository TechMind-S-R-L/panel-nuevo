<?php
ob_start();

require_once __DIR__ . "/../../../controladores/servicios.controlador.php";
require_once __DIR__ . "/../../../modelos/servicios.modelo.php";
require_once __DIR__ . "/../../../controladores/proyectos.controlador.php";
require_once __DIR__ . "/../../../modelos/proyectos.modelo.php";
require_once __DIR__ . "/../../../controladores/clientes.controlador.php";
require_once __DIR__ . "/../../../modelos/clientes.modelo.php";
require_once __DIR__ . "/../../../controladores/usuarios.controlador.php";
require_once __DIR__ . "/../../../modelos/usuarios.modelo.php";

function swpTxt($valor){ return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8"); }
function swpFechaHora($valor){
	if(!$valor){ return date("d/m/Y"); }
	$ts = strtotime((string)$valor);
	return $ts ? date("d/m/Y", $ts) : date("d/m/Y");
}

chdir(__DIR__);
require_once('tcpdf_include_notaventa.php');
require_once __DIR__ . '/pdf-empresa-config.php';

$idServicio = isset($_GET["idServicio"]) ? (int)$_GET["idServicio"] : 0;
$servicio = ControladorServicios::ctrMostrarServicios("id", $idServicio);
if(!$servicio){ die("Servicio no encontrado"); }
$pagoServicio = null;
if(isset($_GET["idPago"])){
	$pagoServicio = ModeloServicios::mdlMostrarPagoServicio((int)$_GET["idPago"]);
	if(!$pagoServicio || (int)$pagoServicio["id_servicio"] !== $idServicio){
		$pagoServicio = null;
	}
}
$proyecto = ControladorProyectos::ctrMostrarProyectoPorServicio($idServicio);
if(!$proyecto){ die("Proyecto no encontrado"); }
$cliente = ControladorClientes::ctrMostrarClientes("id", $servicio["id_cliente"]);
$cajero = $pagoServicio ? array("nombre" => ($pagoServicio["cajero"] ?? "")) : (!empty($servicio["id_cajero"]) ? ControladorUsuarios::ctrMostrarUsuarios("id", $servicio["id_cajero"]) : null);

$tipoPagoServicio = $pagoServicio["tipo_pago"] ?? "";
$concepto = "PAGO DE SOFTWARE";
if($tipoPagoServicio == "adelanto_parcial_software"){
	$concepto = "PAGO PARCIAL DE ADELANTO";
}elseif($tipoPagoServicio == "amortizacion_software"){
	$concepto = "DESARROLLO DE SOFTWARE";
}elseif($tipoPagoServicio == "pago_final_software" || strpos($tipoPagoServicio, "saldo_final") !== false || (!$pagoServicio && ($servicio["estado_pago"] == "aprobado" || (float)$proyecto["saldo_pendiente"] <= 0))){
	$concepto = "PAGO FINAL DE SOFTWARE";
}elseif(!$pagoServicio){
	$concepto = "ADELANTO DE SOFTWARE";
}
$monto = $pagoServicio ? (float)$pagoServicio["monto"] : (float)($servicio["monto_recibido"] ?? 0);
if($monto <= 0){ $monto = (float)$proyecto["pago_adelanto"]; }
$porcentajeAdelanto = number_format((float)$proyecto["porcentaje_adelanto"], 2);
$porcentajePagoActual = (float)$proyecto["precio_total"] > 0 ? number_format(($monto / (float)$proyecto["precio_total"]) * 100, 2) : "0.00";
$conceptoDetalle = $concepto." (".$porcentajePagoActual."% del proyecto)";
$saldoPendienteReal = max(0, (float)$proyecto["precio_total"] - (float)$proyecto["pago_adelanto"] - (float)$proyecto["pago_final"]);
$metodoPagoServicio = $pagoServicio["metodo_pago"] ?? ($servicio["metodo_pago"] ?? "");
$referenciaPagoServicio = $pagoServicio["codigo_transaccion"] ?? ($servicio["codigo_transaccion"] ?? "");
$fechaPagoServicio = $pagoServicio["fecha_pago"] ?? $pagoServicio["fecha"] ?? $pagoServicio["created_at"] ?? $pagoServicio["fecha_registro"] ?? $servicio["fecha"] ?? $servicio["fecha_servicio"] ?? date("Y-m-d H:i:s");
$saldoAntesPago = $pagoServicio ? (float)$pagoServicio["saldo_antes"] : (float)$proyecto["precio_total"];
$saldoDespuesPago = $pagoServicio ? (float)$pagoServicio["saldo_despues"] : $saldoPendienteReal;
$totalPagado = max(0, (float)$proyecto["precio_total"] - $saldoPendienteReal);

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('TechMind S.R.L.');
$pdf->SetAuthor('TechMind S.R.L.');
$pdf->SetTitle('Comprobante de pago de proyecto');
$pdf->SetSubject($concepto);
$pdf->AddPage();
$pdf->SetAlpha(0.08);
$pdf->Image('images/ICONO.png', 45, 85, 120);
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
$pdf->Cell(75, 7, $concepto, 0, 1, 'R');
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(120, 22);
$pdf->Cell(75, 6, 'NRO: '.$proyecto["codigo"], 0, 1, 'R');
$pdf->SetXY(120, 28);
$pdf->Cell(75, 5, 'FECHA: '.swpFechaHora($fechaPagoServicio), 0, 1, 'R');
$pdf->SetTextColor(0);
$pdf->Ln(14);

$html = '
<style>
	body, table, p { font-size:9px; line-height:1.35; color:#162033; }
	.titulo { font-size:14px; font-weight:bold; color:#102f45; text-align:center; }
	.subtitulo { font-size:9px; color:#5a6b7c; text-align:center; }
	.box { border:1px solid #d7e5f2; background-color:#f8fbff; }
	.label { color:#55708a; font-size:8px; font-weight:bold; text-transform:uppercase; }
	.valor { font-size:10px; font-weight:bold; color:#102f45; }
	.monto { font-size:14px; font-weight:bold; color:#1687b9; }
	.tabla th { background-color:#12384d; color:#ffffff; font-weight:bold; line-height:2; }
	.tabla td { border-bottom:1px solid #d8e6f3; }
	.nota { background-color:#eef8ff; color:#315a70; border:1px solid #cce8f8; font-size:8.5px; }
	.firma { color:#102f45; font-size:9px; }
</style>

<div class="titulo" align="center">COMPROBANTE DE PAGO DE PROYECTO</div>
<div class="subtitulo">Documento de constancia por dinero recibido para desarrollo contratado con TechMind S.R.L.</div>
<br>

<table cellpadding="8" cellspacing="0">
<tr>
	<td width="58%" class="box">
		<span class="label">Cliente</span><br>
		<span class="valor">'.swpTxt($cliente["nombre"] ?? "").'</span><br>
		Documento/NIT: '.swpTxt($cliente["documento"] ?? "0").'<br><br>
		<span class="label">Proyecto</span><br>
		<span class="valor">'.swpTxt($proyecto["nombre_proyecto"]).'</span><br>
		Tipo: '.swpTxt($proyecto["tipo_software"]).'
	</td>
	<td width="4%"></td>
	<td width="38%" class="box">
		<span class="label">Pago recibido</span><br>
		<span class="monto">Bs '.number_format($monto, 2).'</span><br>
		'.swpTxt($concepto).'<br><br>
		Fecha: '.swpTxt(swpFechaHora($fechaPagoServicio)).'<br>
		Metodo: '.swpTxt($metodoPagoServicio ?: "Efectivo").'<br>
		Referencia: '.swpTxt($referenciaPagoServicio ?: "-").'<br>
		Recibido por: '.swpTxt($cajero["nombre"] ?? "TechMind S.R.L.").'
	</td>
</tr>
</table>
<br><br>

<table class="tabla" cellpadding="6" cellspacing="0">
<tr>
	<th width="55%" height="20" valign="middle">Detalle financiero</th>
	<th width="45%" height="20" align="right" valign="middle">Importe</th>
</tr>
<tr><td>'.swpTxt($conceptoDetalle).'</td><td align="right"><b>Bs '.number_format($monto, 2).'</b></td></tr>
<tr><td>Precio total acordado</td><td align="right">Bs '.number_format((float)$proyecto["precio_total"], 2).'</td></tr>
<tr><td>Adelanto pactado ('.$porcentajeAdelanto.'%)</td><td align="right">Bs '.number_format((float)$proyecto["monto_adelanto"], 2).'</td></tr>
<tr><td>Total pagado acumulado</td><td align="right">Bs '.number_format($totalPagado, 2).'</td></tr>
<tr><td>Saldo antes de este pago</td><td align="right">Bs '.number_format($saldoAntesPago, 2).'</td></tr>
<tr><td><b>Saldo pendiente despues del pago</b></td><td align="right"><b>Bs '.number_format($saldoDespuesPago, 2).'</b></td></tr>
</table>
<br>
<table cellpadding="8" cellspacing="0">
<tr>
	<td class="nota">
		<b>Nota:</b> Los pagos posteriores se registraran como amortizaciones libres contra el saldo pendiente hasta completar el precio total acordado.
	</td>
</tr>
</table>
<br><br><br><br><br><br>
<table cellpadding="0" cellspacing="0">
<tr>
	<td width="45%" align="center" class="firma">______________________________<br>Firma caja / TechMind S.R.L.</td>
	<td width="10%"></td>
	<td width="45%" align="center" class="firma">______________________________<br>Firma cliente</td>
</tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
if(ob_get_length()){ ob_end_clean(); }
$pdf->Output('boleta-software-pago-'.$proyecto["codigo"].'.pdf', 'I');



