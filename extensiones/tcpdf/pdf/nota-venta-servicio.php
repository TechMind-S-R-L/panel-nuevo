<?php
ob_start();

require_once __DIR__ . "/../../../controladores/servicios.controlador.php";
require_once __DIR__ . "/../../../modelos/servicios.modelo.php";
require_once __DIR__ . "/../../../controladores/clientes.controlador.php";
require_once __DIR__ . "/../../../modelos/clientes.modelo.php";
require_once __DIR__ . "/../../../controladores/usuarios.controlador.php";
require_once __DIR__ . "/../../../modelos/usuarios.modelo.php";

function RotatedTextNotaServicio($pdf, $x, $y, $txt, $angle) {
	$pdf->StartTransform();
	$pdf->Rotate($angle, $x, $y);
	$pdf->SetFont('helvetica', 'B', 55);
	$pdf->SetTextColor(50, 50, 50);
	$pdf->Text($x, $y, $txt);
	$pdf->StopTransform();
}

chdir(__DIR__);
require_once('tcpdf_include_notaventa.php');
require_once __DIR__ . '/pdf-empresa-config.php';

$idServicio = isset($_GET["idServicio"]) ? (int)$_GET["idServicio"] : 0;
$destinoNota = $_GET["destino"] ?? "cliente";
$servicio = ControladorServicios::ctrMostrarServicios("id", $idServicio);

if(!$servicio){
	die("Servicio no encontrado");
}

$cliente = ControladorClientes::ctrMostrarClientes("id", $servicio["id_cliente"]);
$vendedor = ControladorUsuarios::ctrMostrarUsuarios("id", $servicio["id_vendedor"]);
$cajero = !empty($servicio["id_cajero"]) ? ControladorUsuarios::ctrMostrarUsuarios("id", $servicio["id_cajero"]) : null;
$tecnico = !empty($servicio["id_tecnico"]) ? ControladorUsuarios::ctrMostrarUsuarios("id", $servicio["id_tecnico"]) : null;
$fechaPago = !empty($servicio["fecha_pago"]) ? $servicio["fecha_pago"] : $servicio["fecha"];

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->startPageGroup();
$pdf->AddPage();

$pdf->SetAlpha(0.1);
RotatedTextNotaServicio($pdf, 35, 180, 'NOTA DE VENTA', 45);
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

$pdf->SetXY(127, 14);
$pdf->SetFont('helvetica', 'B', 13);
$pdf->SetTextColor(70, 130, 180);
$pdf->Cell(68, 7, 'NOTA DE VENTA', 0, 1, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(135, 22);
$pdf->Cell(60, 6, 'NRO: '.$servicio["codigo"], 0, 1, 'R');
$pdf->SetXY(135, 28);
$pdf->Cell(60, 6, 'FECHA: '.$fechaPago, 0, 1, 'R');
$pdf->SetTextColor(0);
$pdf->Ln(20);

$nombreCliente = htmlspecialchars($cliente["nombre"] ?? '', ENT_QUOTES, "UTF-8");
$documentoCliente = htmlspecialchars($cliente["documento"] ?? '', ENT_QUOTES, "UTF-8");
$telefonoCliente = htmlspecialchars($cliente["telefono"] ?? '', ENT_QUOTES, "UTF-8");
$nombreVendedor = htmlspecialchars($vendedor["nombre"] ?? '', ENT_QUOTES, "UTF-8");
$nombreCajero = htmlspecialchars($cajero["nombre"] ?? '', ENT_QUOTES, "UTF-8");
$nombreTecnico = htmlspecialchars($tecnico["nombre"] ?? 'Asignado despues del cobro', ENT_QUOTES, "UTF-8");
$metodoPago = htmlspecialchars($servicio["metodo_pago"] ?? '', ENT_QUOTES, "UTF-8");
$codigoTransaccion = htmlspecialchars($servicio["codigo_transaccion"] ?? '', ENT_QUOTES, "UTF-8");
$esTaller = ($servicio["tipo_servicio"] ?? "") == "Soporte tecnico en taller";
$equipoTaller = $esTaller ? ControladorServicios::ctrMostrarEquipoTaller($idServicio) : null;
$repuestosTaller = $esTaller ? ControladorServicios::ctrMostrarRepuestosTaller($idServicio) : array();

$infoTable = <<<EOF
<table style="font-size:10px;" cellspacing="0" cellpadding="4" border="1">
  <tr>
    <td style="border:1px solid #000; width:180px;">Cliente: <b>$nombreCliente</b></td>
    <td style="border:1px solid #000; width:180px;">CI/NIT: <b>$documentoCliente</b></td>
    <td style="border:1px solid #000; width:180px;">Telefono: <b>$telefonoCliente</b></td>
  </tr>
  <tr>
    <td style="border:1px solid #000; width:180px;">Vendedor: <b>$nombreVendedor</b></td>
    <td style="border:1px solid #000; width:180px;">Cajero: <b>$nombreCajero</b></td>
    <td style="border:1px solid #000; width:180px;">Tecnico: <b>$nombreTecnico</b></td>
  </tr>
  <tr>
    <td style="border:1px solid #000; width:180px;">Pago: <b>$metodoPago</b></td>
    <td style="border:1px solid #000; width:180px;">Recibido: <b>Bs {$servicio["monto_recibido"]}</b></td>
    <td style="border:1px solid #000; width:180px;">Cambio: <b>Bs {$servicio["cambio"]}</b></td>
  </tr>
</table>
EOF;
$pdf->writeHTML($infoTable, false, false, false, false, '');

$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(100, 7, 'Servicio', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Cantidad', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Valor Unit.', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Valor Total', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 9);
function filaNotaServicio($pdf, $nombre, $cantidad, $unitario) {
	$cantidad = (float)$cantidad;
	$unitario = (float)$unitario;
	if($cantidad <= 0 || $unitario <= 0){
		return;
	}
	$total = $cantidad * $unitario;
	$pdf->Cell(100, 6, $nombre, 1, 0, 'L');
	$pdf->Cell(30, 6, rtrim(rtrim(number_format($cantidad, 2), '0'), '.'), 1, 0, 'C');
	$pdf->Cell(30, 6, "Bs ".number_format($unitario, 2), 1, 0, 'C');
	$pdf->Cell(30, 6, "Bs ".number_format($total, 2), 1, 1, 'C');
}

filaNotaServicio($pdf, $servicio["tipo_servicio"]." - camaras", $servicio["cantidad_camaras"], $servicio["precio_por_camara"]);
filaNotaServicio($pdf, "Metros de cable", $servicio["metros_distancia"], $servicio["precio_por_metro"]);
filaNotaServicio($pdf, "Metros de canalizacion", $servicio["metros_canalizacion"], $servicio["precio_canalizacion_metro"]);
filaNotaServicio($pdf, "Mano de obra", 1, $servicio["costo_mano_obra"]);
filaNotaServicio($pdf, "Visita tecnica", 1, $servicio["costo_visita"]);
filaNotaServicio($pdf, "Diagnostico", 1, $servicio["costo_diagnostico"]);
filaNotaServicio($pdf, "Transporte", 1, $servicio["costo_transporte"]);
filaNotaServicio($pdf, "Recargo altura", 1, $servicio["recargo_altura"]);
filaNotaServicio($pdf, "Recargo urgencia", 1, $servicio["recargo_urgencia"]);

if($esTaller){
	$totalRepuestos = 0;
	foreach($repuestosTaller as $repuesto){
		if(($repuesto["estado"] ?? "") != "entregado"){
			continue;
		}
		$totalRepuestos += (float)$repuesto["subtotal"];
	}

	$manoObraTaller = max(0, (float)$servicio["total"] - $totalRepuestos);
	filaNotaServicio($pdf, "Mano de obra soporte tecnico", 1, $manoObraTaller);

	foreach($repuestosTaller as $repuesto){
		if(($repuesto["estado"] ?? "") != "entregado"){
			continue;
		}
		$codigos = "";
		$codigosEntregados = json_decode($repuesto["codigos_entregados"] ?? "[]", true);
		if(is_array($codigosEntregados) && count($codigosEntregados) > 0){
			$codigos = " Codigos: ".implode(", ", $codigosEntregados);
		}
		filaNotaServicio($pdf, ($repuesto["descripcion"] ?? "Repuesto").$codigos, $repuesto["cantidad"], $repuesto["precio_unitario"]);
	}
}

$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(160, 7, 'TOTAL CANCELADO', 1, 0, 'R');
$pdf->Cell(30, 7, "Bs ".number_format((float)$servicio["total"], 2), 1, 1, 'C');

if($codigoTransaccion != ""){
	$pdf->SetFont('helvetica', '', 9);
	$pdf->Cell(190, 6, "Referencia de pago: ".$codigoTransaccion, 0, 1, 'L');
}

if($esTaller && $equipoTaller){
	$pdf->Ln(4);
	$pdf->SetFont('helvetica', 'B', 10);
	$pdf->Cell(190, 7, 'DETALLE DEL SERVICIO TECNICO', 1, 1, 'C', true);
	$pdf->SetFont('helvetica', '', 9);
	$detalleTaller = '<table style="font-size:9px;" cellspacing="0" cellpadding="4" border="1" border="1">
		<tr><td width="25%"><b>Equipo</b></td><td width="75%">'.htmlspecialchars(trim(($equipoTaller["tipo_equipo"] ?? "")." ".($equipoTaller["marca"] ?? "")." ".($equipoTaller["modelo"] ?? "")), ENT_QUOTES, "UTF-8").'</td></tr>
		<tr><td><b>Codigo equipo</b></td><td>'.htmlspecialchars($equipoTaller["codigo_equipo"] ?? "", ENT_QUOTES, "UTF-8").'</td></tr>
		<tr><td><b>Falla reportada</b></td><td>'.nl2br(htmlspecialchars($equipoTaller["falla_reportada"] ?? "", ENT_QUOTES, "UTF-8")).'</td></tr>
		<tr><td><b>Trabajo realizado</b></td><td>'.nl2br(htmlspecialchars($equipoTaller["reparacion_realizada"] ?? "", ENT_QUOTES, "UTF-8")).'</td></tr>
		<tr><td><b>Repuestos / componentes</b></td><td>'.nl2br(htmlspecialchars($equipoTaller["repuestos_detalle"] ?? "", ENT_QUOTES, "UTF-8")).'</td></tr>
		<tr><td><b>Garantia</b></td><td>'.nl2br(htmlspecialchars($equipoTaller["garantia_detalle"] ?? "", ENT_QUOTES, "UTF-8")).'</td></tr>
	</table>';
	$pdf->writeHTML($detalleTaller, true, false, true, false, '');
}

if($esTaller && $destinoNota == "almacen"){
	$pdf->Ln(4);
	$pdf->SetFont('helvetica', 'B', 10);
	$pdf->SetFillColor(255, 245, 200);
	$pdf->MultiCell(190, 8, "COMPROBANTE PARA ALMACEN: Cliente debe presentar esta nota para retirar su equipo reparado/devuelto.", 1, 'C', true);
}

$pdf->Ln(12);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(190, 5, 'Gracias por confiar en TechMind S.R.L.', 0, 1, 'C');

if(ob_get_length()){ ob_end_clean(); }
$pdf->Output('nota-venta-servicio-'.$servicio["codigo"].'.pdf', 'I');



