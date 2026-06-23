<?php
ob_start();

require_once __DIR__ . "/../../../controladores/servicios.controlador.php";
require_once __DIR__ . "/../../../modelos/servicios.modelo.php";
require_once __DIR__ . "/../../../controladores/clientes.controlador.php";
require_once __DIR__ . "/../../../modelos/clientes.modelo.php";
require_once __DIR__ . "/../../../controladores/usuarios.controlador.php";
require_once __DIR__ . "/../../../modelos/usuarios.modelo.php";

function RotatedTextRepuestosTaller($pdf, $x, $y, $txt, $angle) {
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
$servicio = ControladorServicios::ctrMostrarServicios("id", $idServicio);
if(!$servicio){ die("Servicio no encontrado"); }

$equipo = ControladorServicios::ctrMostrarEquipoTaller($idServicio);
$repuestos = ControladorServicios::ctrMostrarRepuestosTaller($idServicio);
if(!$repuestos || count($repuestos) == 0){ die("Sin repuestos registrados"); }

$cliente = ControladorClientes::ctrMostrarClientes("id", $servicio["id_cliente"]);
$tecnico = !empty($servicio["id_tecnico"]) ? ControladorUsuarios::ctrMostrarUsuarios("id", $servicio["id_tecnico"]) : null;
$almacenero = null;
foreach($repuestos as $repuesto){
	if(!empty($repuesto["id_almacenero_entrega"])){
		$almacenero = ControladorUsuarios::ctrMostrarUsuarios("id", $repuesto["id_almacenero_entrega"]);
		break;
	}
}

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->startPageGroup();
$pdf->AddPage();

$pdf->SetAlpha(0.1);
RotatedTextRepuestosTaller($pdf, 30, 180, 'REPUESTOS TALLER', 45);
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

$pdf->SetXY(120, 12);
$pdf->SetFont('helvetica', 'B', 13);
$pdf->SetTextColor(70, 130, 180);
$pdf->Cell(80, 7, 'CONSTANCIA REPUESTOS', 0, 1, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(135, 23);
$pdf->Cell(60, 6, 'CASO: '.$servicio["codigo"], 0, 1, 'R');
$pdf->SetXY(135, 29);
$pdf->Cell(60, 6, 'EQUIPO: '.($equipo["codigo_equipo"] ?? ""), 0, 1, 'R');
$pdf->SetTextColor(0);
$pdf->Ln(20);

$html = '
<table style="font-size:10px;" cellspacing="0" cellpadding="4" border="1">
  <tr>
    <td><b>Cliente:</b> '.htmlspecialchars($cliente["nombre"] ?? "", ENT_QUOTES, "UTF-8").'</td>
    <td><b>Telefono:</b> '.htmlspecialchars($cliente["telefono"] ?? "", ENT_QUOTES, "UTF-8").'</td>
  </tr>
  <tr>
    <td><b>Tecnico:</b> '.htmlspecialchars($tecnico["nombre"] ?? "Sin tecnico", ENT_QUOTES, "UTF-8").'</td>
    <td><b>Almacenero:</b> '.htmlspecialchars($almacenero["nombre"] ?? "Pendiente entrega", ENT_QUOTES, "UTF-8").'</td>
  </tr>
  <tr>
    <td colspan="2"><b>Equipo:</b> '.htmlspecialchars(trim(($equipo["tipo_equipo"] ?? "")." ".($equipo["marca"] ?? "")." ".($equipo["modelo"] ?? "")), ENT_QUOTES, "UTF-8").'</td>
  </tr>
</table>
<br>
<h3>PIEZAS / COMPONENTES SOLICITADOS</h3>
<table style="font-size:10px;" cellspacing="0" cellpadding="4" border="1">
  <tr style="font-weight:bold;background-color:#f2f2f2;">
    <td width="20%">Codigo</td>
    <td width="32%">Producto</td>
    <td width="10%">Cant.</td>
    <td width="15%">P. Unit.</td>
    <td width="15%">Subtotal</td>
    <td width="8%">Codigos</td>
  </tr>';

$total = 0;
foreach($repuestos as $repuesto){
	$total += (float)$repuesto["subtotal"];
	$html .= '<tr>
    <td width="20%">'.htmlspecialchars($repuesto["codigo"], ENT_QUOTES, "UTF-8").'</td>
    <td width="32%">'.htmlspecialchars($repuesto["descripcion"], ENT_QUOTES, "UTF-8").'</td>
    <td width="10%" align="center">'.(int)$repuesto["cantidad"].'</td>
    <td width="15%" align="right">Bs '.number_format((float)$repuesto["precio_unitario"], 2).'</td>
    <td width="15%" align="right">Bs '.number_format((float)$repuesto["subtotal"], 2).'</td>
    <td width="8%">'.htmlspecialchars(implode(", ", json_decode($repuesto["codigos_entregados"] ?? "[]", true) ?: array()), ENT_QUOTES, "UTF-8").'</td>
  </tr>';
}

$html .= '
  <tr>
    <td colspan="4" align="right"><b>Total para cobro posterior:</b></td>
    <td align="right"><b>Bs '.number_format($total, 2).'</b></td>
  </tr>
</table>
<br><br><br><br>
<table style="font-size:9px;" cellpadding="8">
  <tr>
    <td align="center">_________________________<br>Entrega almacen</td>
    <td align="center">_________________________<br>Recibe tecnico</td>
  </tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
if(ob_get_length()){ ob_end_clean(); }
$pdf->Output('boleta-repuestos-taller-'.$servicio["codigo"].'.pdf', 'I');



