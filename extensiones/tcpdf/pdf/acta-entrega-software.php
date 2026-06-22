<?php
ob_start();

require_once __DIR__ . "/../../../controladores/proyectos.controlador.php";
require_once __DIR__ . "/../../../modelos/proyectos.modelo.php";
require_once __DIR__ . "/../../../controladores/clientes.controlador.php";
require_once __DIR__ . "/../../../modelos/clientes.modelo.php";
require_once __DIR__ . "/../../../controladores/usuarios.controlador.php";
require_once __DIR__ . "/../../../modelos/usuarios.modelo.php";

function aesTxt($valor){ return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8"); }

chdir(__DIR__);
require_once('tcpdf_include_notaventa.php');

$idProyecto = isset($_GET["idProyecto"]) ? (int)$_GET["idProyecto"] : 0;
$proyecto = ControladorProyectos::ctrMostrarProyectoSoftware("id", $idProyecto);
if(!$proyecto){ die("Proyecto no encontrado"); }
$cliente = ControladorClientes::ctrMostrarClientes("id", $proyecto["id_cliente"]);
$desarrollador = !empty($proyecto["id_desarrollador"]) ? ControladorUsuarios::ctrMostrarUsuarios("id", $proyecto["id_desarrollador"]) : null;
$documentos = ModeloProyectos::mdlMostrarDocumentos($idProyecto);

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
$pdf->Cell(90, 7, 'TECHMIND S.R.L.', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 9);
$pdf->SetX(40);
$pdf->Cell(90, 5, 'Km 6 doble via la guardia, calle paraiso Nro 6387', 0, 1, 'L');
$pdf->SetX(40);
$pdf->Cell(90, 5, '(+591) 75556540 | (+591) 78572656', 0, 1, 'L');
$pdf->SetXY(120, 14);
$pdf->SetFont('helvetica', 'B', 13);
$pdf->Cell(75, 7, 'ACTA DE ENTREGA', 0, 1, 'R');
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(120, 22);
$pdf->Cell(75, 6, 'NRO: '.$proyecto["codigo"], 0, 1, 'R');
$pdf->SetTextColor(0);
$pdf->Ln(14);

$listaDocs = '';
foreach($documentos as $doc){
	$listaDocs .= '<li>'.aesTxt($doc["tipo_documento"]).' - '.aesTxt($doc["titulo"]).'</li>';
}
if($listaDocs == ''){ $listaDocs = '<li>Sin documentos adjuntos registrados.</li>'; }

$html = '
<style>
	body, table, p { font-size:9px; line-height:1.35; }
	h3 { font-size:12px; }
	h4 { font-size:10px; margin-bottom:4px; }
</style>
<h3 align="center">ACTA DE ENTREGA Y CONFORMIDAD DE SOFTWARE</h3>
<table cellpadding="5" border="1">
<tr><td><b>Cliente:</b> '.aesTxt($cliente["nombre"] ?? "").'</td><td><b>Documento/NIT:</b> '.aesTxt($cliente["documento"] ?? "").'</td></tr>
<tr><td><b>Proyecto:</b> '.aesTxt($proyecto["nombre_proyecto"]).'</td><td><b>Tipo:</b> '.aesTxt($proyecto["tipo_software"]).'</td></tr>
<tr><td><b>Desarrollador:</b> '.aesTxt($desarrollador["nombre"] ?? "").'</td><td><b>Fecha entrega:</b> '.date("d/m/Y H:i").'</td></tr>
</table>

<h4>Detalle entregado</h4>
<p>'.nl2br(aesTxt($proyecto["entregables"])).'</p>

<h4>Trabajo realizado / alcance cubierto</h4>
<p>'.nl2br(aesTxt($proyecto["observaciones"])).'</p>

<h4>Documentos y respaldos registrados</h4>
<ul>'.$listaDocs.'</ul>

<h4>Conformidad</h4>
<p>EL CLIENTE declara recibir el proyecto indicado, quedando constancia de la entrega. Cualquier mejora, nuevo modulo, ajuste fuera del alcance inicial o soporte adicional sera tratado como requerimiento nuevo y cotizado por separado.</p>

<br><br><br><br>
<table cellpadding="8" border="1"><tr><td align="center">_________________________<br>Firma y sello TechMind</td><td align="center">_________________________<br>Firma cliente</td></tr></table>';

$pdf->writeHTML($html, true, false, true, false, '');
if(ob_get_length()){ ob_end_clean(); }
$pdf->Output('acta-entrega-software-'.$proyecto["codigo"].'.pdf', 'I');



