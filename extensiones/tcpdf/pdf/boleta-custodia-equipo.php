<?php
ob_start();

require_once __DIR__ . "/../../../controladores/servicios.controlador.php";
require_once __DIR__ . "/../../../modelos/servicios.modelo.php";
require_once __DIR__ . "/../../../controladores/clientes.controlador.php";
require_once __DIR__ . "/../../../modelos/clientes.modelo.php";
require_once __DIR__ . "/../../../controladores/usuarios.controlador.php";
require_once __DIR__ . "/../../../modelos/usuarios.modelo.php";

function RotatedTextCustodiaEquipo($pdf, $x, $y, $txt, $angle) {
	$pdf->StartTransform();
	$pdf->Rotate($angle, $x, $y);
	$pdf->SetFont('helvetica', 'B', 48);
	$pdf->SetTextColor(50, 50, 50);
	$pdf->Text($x, $y, $txt);
	$pdf->StopTransform();
}

chdir(__DIR__);
require_once('tcpdf_include_notaventa.php');
require_once __DIR__ . '/pdf-empresa-config.php';

$idServicio = isset($_GET["idServicio"]) ? (int)$_GET["idServicio"] : 0;
$tipo = $_GET["tipo"] ?? "recepcion";
$servicio = ControladorServicios::ctrMostrarServicios("id", $idServicio);
if(!$servicio){ die("Servicio no encontrado"); }

$equipo = ControladorServicios::ctrMostrarEquipoTaller($idServicio);
if(!$equipo){ die("Equipo no encontrado"); }

$cliente = ControladorClientes::ctrMostrarClientes("id", $servicio["id_cliente"]);
$vendedor = ControladorUsuarios::ctrMostrarUsuarios("id", $servicio["id_vendedor"]);
$tecnico = !empty($servicio["id_tecnico"]) ? ControladorUsuarios::ctrMostrarUsuarios("id", $servicio["id_tecnico"]) : null;
$almacenRecepcion = !empty($equipo["id_almacenero_recepcion"]) ? ControladorUsuarios::ctrMostrarUsuarios("id", $equipo["id_almacenero_recepcion"]) : null;
$almacenRetiro = !empty($equipo["id_almacenero_retiro"]) ? ControladorUsuarios::ctrMostrarUsuarios("id", $equipo["id_almacenero_retiro"]) : null;
$almacenReingreso = !empty($equipo["id_almacenero_reingreso"]) ? ControladorUsuarios::ctrMostrarUsuarios("id", $equipo["id_almacenero_reingreso"]) : null;

$titulos = array(
	"ingreso" => array("titulo" => "BOLETA INGRESO EQUIPO", "marca" => "INGRESO EQUIPO", "uso" => "Constancia inicial para entregar el equipo a almacen."),
	"recepcion" => array("titulo" => "CONSTANCIA RECEPCION ALMACEN", "marca" => "RECIBIDO ALMACEN", "uso" => "Constancia de entrega del vendedor a almacen."),
	"retiro" => array("titulo" => "CONSTANCIA RETIRO TECNICO", "marca" => "RETIRO TECNICO", "uso" => "Constancia de salida desde almacen hacia soporte tecnico."),
	"reingreso" => array("titulo" => "CONSTANCIA REINGRESO ALMACEN", "marca" => "REINGRESO ALMACEN", "uso" => "Constancia de retorno del tecnico a almacen.")
);
$datosTitulo = $titulos[$tipo] ?? $titulos["recepcion"];

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->startPageGroup();
$pdf->AddPage();

$pdf->SetAlpha(0.1);
RotatedTextCustodiaEquipo($pdf, 25, 180, $datosTitulo["marca"], 45);
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

$pdf->SetXY(116, 12);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(70, 130, 180);
$pdf->Cell(84, 7, $datosTitulo["titulo"], 0, 1, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(135, 23);
$pdf->Cell(60, 6, 'CASO: '.$servicio["codigo"], 0, 1, 'R');
$pdf->SetXY(135, 29);
$pdf->Cell(60, 6, 'EQUIPO: '.$equipo["codigo_equipo"], 0, 1, 'R');
$pdf->SetTextColor(0);
$pdf->Ln(20);

$fechaMovimiento = date("Y-m-d H:i:s");
if($tipo == "recepcion" && !empty($equipo["fecha_recepcion_almacen"])){
	$fechaMovimiento = $equipo["fecha_recepcion_almacen"];
}else if($tipo == "retiro" && !empty($equipo["fecha_retiro_tecnico"])){
	$fechaMovimiento = $equipo["fecha_retiro_tecnico"];
}else if($tipo == "reingreso" && !empty($equipo["fecha_reingreso_almacen"])){
	$fechaMovimiento = $equipo["fecha_reingreso_almacen"];
}

$html = '
<table style="font-size:10px;" cellspacing="0" cellpadding="4" border="1">
  <tr>
    <td><b>Cliente:</b> '.htmlspecialchars($cliente["nombre"] ?? "", ENT_QUOTES, "UTF-8").'</td>
    <td><b>Telefono:</b> '.htmlspecialchars($cliente["telefono"] ?? "", ENT_QUOTES, "UTF-8").'</td>
  </tr>
  <tr>
    <td><b>Vendedor:</b> '.htmlspecialchars($vendedor["nombre"] ?? "", ENT_QUOTES, "UTF-8").'</td>
    <td><b>Tecnico asignado:</b> '.htmlspecialchars($tecnico["nombre"] ?? "Pendiente", ENT_QUOTES, "UTF-8").'</td>
  </tr>
  <tr>
    <td><b>Almacen recepcion:</b> '.htmlspecialchars($almacenRecepcion["nombre"] ?? "Pendiente", ENT_QUOTES, "UTF-8").'</td>
    <td><b>Almacen entrega a tecnico:</b> '.htmlspecialchars($almacenRetiro["nombre"] ?? "Pendiente", ENT_QUOTES, "UTF-8").'</td>
  </tr>
  <tr>
    <td><b>Almacen reingreso:</b> '.htmlspecialchars($almacenReingreso["nombre"] ?? "Pendiente", ENT_QUOTES, "UTF-8").'</td>
    <td><b>Tecnico recibe:</b> '.htmlspecialchars($tecnico["nombre"] ?? "Pendiente", ENT_QUOTES, "UTF-8").'</td>
  </tr>
  <tr>
    <td><b>Uso:</b> '.htmlspecialchars($datosTitulo["uso"], ENT_QUOTES, "UTF-8").'</td>
    <td><b>Fecha movimiento:</b> '.htmlspecialchars($fechaMovimiento, ENT_QUOTES, "UTF-8").'</td>
  </tr>
</table>
<br>
<h3>DETALLE DEL EQUIPO</h3>
<table style="font-size:10px;" cellspacing="0" cellpadding="4" border="1" border="1">
  <tr>
    <td><b>Codigo unico:</b> '.htmlspecialchars($equipo["codigo_equipo"], ENT_QUOTES, "UTF-8").'</td>
    <td><b>Estado custodia:</b> '.htmlspecialchars($equipo["estado_equipo"], ENT_QUOTES, "UTF-8").'</td>
  </tr>
  <tr>
    <td><b>Equipo:</b> '.htmlspecialchars(trim($equipo["tipo_equipo"]." ".$equipo["marca"]." ".$equipo["modelo"]), ENT_QUOTES, "UTF-8").'</td>
    <td><b>Serie:</b> '.htmlspecialchars($equipo["serie"], ENT_QUOTES, "UTF-8").'</td>
  </tr>
  <tr><td colspan="2"><b>Accesorios:</b><br>'.nl2br(htmlspecialchars($equipo["accesorios"], ENT_QUOTES, "UTF-8")).'</td></tr>
  <tr><td colspan="2"><b>Falla reportada:</b><br>'.nl2br(htmlspecialchars($equipo["falla_reportada"], ENT_QUOTES, "UTF-8")).'</td></tr>
  <tr><td colspan="2"><b>Estado fisico:</b><br>'.nl2br(htmlspecialchars($equipo["estado_fisico"], ENT_QUOTES, "UTF-8")).'</td></tr>';

if($tipo == "reingreso"){
	$html .= '<tr><td colspan="2"><b>Detalle reparado / devuelto:</b><br>'.nl2br(htmlspecialchars($equipo["observacion_reingreso"], ENT_QUOTES, "UTF-8")).'</td></tr>
  <tr><td colspan="2"><b>Trabajo tecnico:</b><br>'.nl2br(htmlspecialchars($equipo["reparacion_realizada"], ENT_QUOTES, "UTF-8")).'</td></tr>';
}

$html .= '</table>
<br><br><br><br>
<table style="font-size:9px;" cellpadding="8">
  <tr>
    <td align="center">_________________________<br>Entrega</td>
    <td align="center">_________________________<br>Recibe</td>
  </tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');

if(!empty($equipo["foto_equipo"]) && file_exists(__DIR__ . "/../../../".$equipo["foto_equipo"])){
	$pdf->AddPage();
	$pdf->SetFont('helvetica', 'B', 12);
	$pdf->Cell(0, 8, 'FOTO DEL EQUIPO', 0, 1, 'C');
	$pdf->Image(__DIR__ . "/../../../".$equipo["foto_equipo"], 20, 30, 170, 0, '', '', '', true);
}

if(ob_get_length()){ ob_end_clean(); }
$pdf->Output('boleta-custodia-equipo-'.$tipo.'-'.$equipo["codigo_equipo"].'.pdf', 'I');



