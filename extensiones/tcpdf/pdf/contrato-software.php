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

function contratoTxt($valor){ return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8"); }

chdir(__DIR__);
require_once('tcpdf_include_notaventa.php');

class ContratoSoftwarePDF extends TCPDF {
	public $codigoContrato = "";

	public function Header() {
		$this->SetAlpha(0.08);
		$this->Image('images/ICONO.png', 45, 85, 120);
		$this->SetAlpha(1);

		$this->SetFillColor(230, 240, 255);
		$this->Rect(10, 10, 190, 30, 'F');
		$this->Image('images/ICONO.png', 17, 13, 21);

		$this->SetXY(40, 14);
		$this->SetFont('helvetica', 'B', 14);
		$this->SetTextColor(70, 130, 180);
		$this->Cell(90, 7, 'TECHMIND S.R.L.', 0, 1, 'L');
		$this->SetFont('helvetica', '', 9);
		$this->SetX(40);
		$this->Cell(90, 5, 'Km 6 doble via la guardia, calle paraiso Nro 6387', 0, 1, 'L');
		$this->SetX(40);
		$this->Cell(90, 5, '(+591) 75556540 | (+591) 78572656', 0, 1, 'L');

		$this->SetXY(120, 14);
		$this->SetFont('helvetica', 'B', 13);
		$this->Cell(75, 7, 'CONTRATO DE SOFTWARE', 0, 1, 'R');
		$this->SetFont('helvetica', '', 9);
		$this->SetXY(120, 22);
		$this->Cell(75, 6, 'NRO: '.$this->codigoContrato, 0, 1, 'R');
		$this->SetDrawColor(210, 225, 235);
		$this->Line(10, 43, 200, 43);
		$this->SetTextColor(0);
	}

	public function Footer() {
		$this->SetY(-14);
		$this->SetDrawColor(210, 225, 235);
		$this->Line(10, 283, 200, 283);
		$this->SetFont('helvetica', '', 8);
		$this->SetTextColor(90, 110, 120);
		$this->Cell(95, 6, 'TechMind S.R.L. - Contrato de desarrollo de software', 0, 0, 'L');
		$this->Cell(95, 6, 'Pagina '.$this->getAliasNumPage().' de '.$this->getAliasNbPages(), 0, 0, 'R');
		$this->SetTextColor(0);
	}
}

$idServicio = isset($_GET["idServicio"]) ? (int)$_GET["idServicio"] : 0;
$servicio = ControladorServicios::ctrMostrarServicios("id", $idServicio);
if(!$servicio){ die("Servicio no encontrado"); }
$proyecto = ControladorProyectos::ctrMostrarProyectoPorServicio($idServicio);
if(!$proyecto){ die("Proyecto no encontrado"); }
$cliente = ControladorClientes::ctrMostrarClientes("id", $servicio["id_cliente"]);
$vendedor = ControladorUsuarios::ctrMostrarUsuarios("id", $servicio["id_vendedor"]);
$porcentajeAdelanto = number_format((float)$proyecto["porcentaje_adelanto"], 2);

$pdf = new ContratoSoftwarePDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->codigoContrato = $proyecto["codigo"];
$pdf->SetMargins(10, 49, 10);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(14);
$pdf->SetAutoPageBreak(true, 18);
$pdf->AddPage();

$html = '
<style>
	body, table, p { font-size:9px; line-height:1.35; }
	h3 { font-size:12px; }
	h4 { font-size:10px; margin-bottom:4px; }
	.firmas td { font-size:9px; }
</style>
<h3 align="center">CONTRATO DE DESARROLLO DE SOFTWARE</h3>
<table cellpadding="5" border="1">
<tr><td><b>Cliente:</b> '.contratoTxt($cliente["nombre"] ?? "").'</td><td><b>Documento/NIT:</b> '.contratoTxt($cliente["documento"] ?? "").'</td></tr>
<tr><td><b>Telefono:</b> '.contratoTxt($cliente["telefono"] ?? "").'</td><td><b>Vendedor:</b> '.contratoTxt($vendedor["nombre"] ?? "").'</td></tr>
<tr><td><b>Proyecto:</b> '.contratoTxt($proyecto["nombre_proyecto"]).'</td><td><b>Tipo:</b> '.contratoTxt($proyecto["tipo_software"]).'</td></tr>
<tr><td><b>Fecha estimada entrega:</b> '.contratoTxt($proyecto["fecha_entrega_estimada"] ?? "").'</td><td><b>Plazo:</b> '.contratoTxt($proyecto["plazo_entrega"] ?? "").'</td></tr>
</table>

<h4>1. Objeto del contrato</h4>
<p>TECHMIND S.R.L. se compromete a desarrollar para EL CLIENTE el proyecto descrito como <b>'.contratoTxt($proyecto["nombre_proyecto"]).'</b>, de tipo <b>'.contratoTxt($proyecto["tipo_software"]).'</b>, conforme al alcance inicial definido en este documento.</p>

<h4>2. Alcance inicial</h4>
<p>'.nl2br(contratoTxt($proyecto["alcance"])).'</p>

<h4>3. Entregables incluidos</h4>
<p>'.nl2br(contratoTxt($proyecto["entregables"])).'</p>

<h4>4. Exclusiones y cambios fuera de alcance</h4>
<p>'.nl2br(contratoTxt($proyecto["exclusiones"])).'</p>
<p>Todo requerimiento adicional, cambio funcional, integracion, pantalla, reporte, migracion o ajuste no descrito expresamente en el alcance inicial sera cotizado y aprobado por separado antes de su ejecucion.</p>

<h4>5. Precio y forma de pago</h4>
<table cellpadding="5" border="1">
<tr><td><b>Precio total acordado</b></td><td align="right">Bs '.number_format((float)$proyecto["precio_total"], 2).'</td></tr>
<tr><td><b>Adelanto inicial ('.$porcentajeAdelanto.'%)</b></td><td align="right">Bs '.number_format((float)$proyecto["monto_adelanto"], 2).'</td></tr>
<tr><td><b>Saldo pendiente a la entrega</b></td><td align="right">Bs '.number_format((float)$proyecto["saldo_pendiente"], 2).'</td></tr>
</table>
<p>El adelanto inicial corresponde al monto entregado por EL CLIENTE y equivale al '.$porcentajeAdelanto.'% del precio total acordado. El desarrollo inicia despues de confirmado este pago. El saldo pendiente debera ser cancelado antes de la entrega final, instalacion definitiva, liberacion de credenciales productivas o entrega de respaldos finales.</p>

<h4>6. Responsabilidades del cliente</h4>
<p>EL CLIENTE debera entregar oportunamente informacion, textos, logotipos, accesos, usuarios de prueba, credenciales, dominio, hosting, contenido y aprobaciones necesarias. Las demoras de entrega de informacion por parte del cliente podran modificar el plazo estimado.</p>

<h4>7. Garantia y soporte</h4>
<p>La garantia cubre correccion de errores atribuibles al desarrollo entregado, siempre que no existan modificaciones externas, cambios de servidor, cambios de alcance o mal uso. Nuevas funciones, mejoras o cambios posteriores se cotizaran aparte.</p>

<br><br><br><br><br><br><br><br>
<table class="firmas" cellpadding="10">
<tr>
<td align="center"><br><br>____________________________________<br>Firma y sello TechMind</td>
<td align="center"><br><br>____________________________________<br>Firma cliente</td>
</tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
if(ob_get_length()){ ob_end_clean(); }
$pdf->Output('contrato-software-'.$proyecto["codigo"].'.pdf', 'I');


