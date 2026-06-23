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
$desarrollador = !empty($proyecto["id_desarrollador"]) ? ControladorUsuarios::ctrMostrarUsuarios("id", $proyecto["id_desarrollador"]) : null;

$tipoPagoServicio = $pagoServicio["tipo_pago"] ?? "";
$concepto = (strpos($tipoPagoServicio, "saldo_final") !== false || (!$pagoServicio && ($servicio["estado_pago"] == "aprobado" || $proyecto["pago_final"] > 0))) ? "PAGO FINAL DE SOFTWARE" : "ADELANTO DE SOFTWARE";
$monto = $pagoServicio ? (float)$pagoServicio["monto"] : (($concepto == "PAGO FINAL DE SOFTWARE") ? (float)$proyecto["pago_final"] : (float)($servicio["monto_recibido"] ?? 0));
if($monto <= 0){ $monto = (float)$servicio["monto_recibido"]; }
if($monto <= 0 && $concepto != "PAGO FINAL DE SOFTWARE"){ $monto = (float)$proyecto["pago_adelanto"]; }
$porcentajeAdelanto = number_format((float)$proyecto["porcentaje_adelanto"], 2);
$porcentajePagoActual = (float)$proyecto["precio_total"] > 0 ? number_format(($monto / (float)$proyecto["precio_total"]) * 100, 2) : "0.00";
$conceptoDetalle = ($concepto == "ADELANTO DE SOFTWARE") ? $concepto." (pago actual ".$porcentajePagoActual."%)" : $concepto;
$saldoPendienteReal = max(0, (float)$proyecto["precio_total"] - (float)$proyecto["pago_adelanto"] - (float)$proyecto["pago_final"]);
$metodoPagoServicio = $pagoServicio["metodo_pago"] ?? ($servicio["metodo_pago"] ?? "");
$referenciaPagoServicio = $pagoServicio["codigo_transaccion"] ?? ($servicio["codigo_transaccion"] ?? "");
$filasSaldoPago = $pagoServicio ? '
<tr><td>Saldo antes de este pago</td><td align="right">Bs '.number_format((float)$pagoServicio["saldo_antes"], 2).'</td></tr>
<tr><td>Saldo despues de este pago</td><td align="right">Bs '.number_format((float)$pagoServicio["saldo_despues"], 2).'</td></tr>' : '';

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
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
$pdf->SetTextColor(0);
$pdf->Ln(14);

$html = '
<style>
	body, table, p { font-size:9px; line-height:1.35; }
	h3 { font-size:12px; }
</style>
<h3>COMPROBANTE DE PAGO</h3>
<table cellpadding="5" border="1">
<tr><td><b>Cliente:</b> '.swpTxt($cliente["nombre"] ?? "").'</td><td><b>Documento/NIT:</b> '.swpTxt($cliente["documento"] ?? "").'</td></tr>
<tr><td><b>Proyecto:</b> '.swpTxt($proyecto["nombre_proyecto"]).'</td><td><b>Tipo:</b> '.swpTxt($proyecto["tipo_software"]).'</td></tr>
<tr><td><b>Metodo de pago:</b> '.swpTxt($metodoPagoServicio).'</td><td><b>Referencia:</b> '.swpTxt($referenciaPagoServicio).'</td></tr>
<tr><td><b>Cajero:</b> '.swpTxt($cajero["nombre"] ?? "").'</td><td><b>Desarrollador asignado:</b> '.swpTxt($desarrollador["nombre"] ?? "Pendiente").'</td></tr>
</table>
<br>
<table cellpadding="6" border="1">
<tr style="background-color:#eaf4fb;font-weight:bold"><td>Concepto</td><td align="right">Monto</td></tr>
<tr><td>'.swpTxt($conceptoDetalle).'</td><td align="right">Bs '.number_format($monto, 2).'</td></tr>
<tr><td>Precio total del proyecto</td><td align="right">Bs '.number_format((float)$proyecto["precio_total"], 2).'</td></tr>
<tr><td>Adelanto pactado ('.$porcentajeAdelanto.'%)</td><td align="right">Bs '.number_format((float)$proyecto["monto_adelanto"], 2).'</td></tr>
<tr><td>Adelanto acumulado</td><td align="right">Bs '.number_format((float)$proyecto["pago_adelanto"], 2).'</td></tr>
'.$filasSaldoPago.'
<tr><td>Saldo pendiente</td><td align="right">Bs '.number_format($saldoPendienteReal, 2).'</td></tr>
</table>
<br><br><br><br>
<table cellpadding="8" border="1"><tr><td align="center">_________________________<br>Firma caja</td><td align="center">_________________________<br>Firma cliente</td></tr></table>';

$pdf->writeHTML($html, true, false, true, false, '');
if(ob_get_length()){ ob_end_clean(); }
$pdf->Output('boleta-software-pago-'.$proyecto["codigo"].'.pdf', 'I');



