<?php
ob_start();

if (isset($_GET["debugpdf"]) && hash_equals("techmind-pdf-2026", (string)$_GET["debugpdf"])) {
	ini_set("display_errors", "1");
	ini_set("display_startup_errors", "1");
	error_reporting(E_ALL);
	header("Content-Type: text/html; charset=utf-8");

	$basePdf = __DIR__;
	$baseTcpdf = dirname(__DIR__);
	$tcpdfFile = $baseTcpdf . "/tcpdf.php";
	$includeFile = $basePdf . "/tcpdf_include_notaventa.php";
	$configFile = $basePdf . "/config/tcpdf_config_notaventa.php";
	$logoFile = $basePdf . "/images/ICONO.png";
	$cacheDir = $basePdf . "/cache";

	function contratoDebugFila($nombre, $ok, $detalle) {
		echo "<tr>";
		echo "<td style='padding:8px;border-bottom:1px solid #e5edf7;font-weight:700'>" . htmlspecialchars($nombre, ENT_QUOTES, "UTF-8") . "</td>";
		echo "<td style='padding:8px;border-bottom:1px solid #e5edf7;color:" . ($ok ? "#079455" : "#d92d20") . ";font-weight:800'>" . ($ok ? "OK" : "ERROR") . "</td>";
		echo "<td style='padding:8px;border-bottom:1px solid #e5edf7'>" . htmlspecialchars((string)$detalle, ENT_QUOTES, "UTF-8") . "</td>";
		echo "</tr>";
	}

	echo "<!doctype html><html><head><meta charset='utf-8'><title>Debug PDF Contrato</title></head>";
	echo "<body style='font-family:Arial,sans-serif;background:#f5f8fc;color:#172033;padding:24px'>";
	echo "<section style='max-width:980px;margin:auto;background:#fff;border:1px solid #d9e6f5;border-radius:18px;padding:24px;box-shadow:0 18px 45px rgba(15,54,110,.12)'>";
	echo "<h1 style='margin:0 0 8px'>Diagnóstico PDF - contrato-software.php</h1>";
	echo "<p style='color:#596b86;margin-top:0'>Si esta pantalla abre, el archivo existe y podemos ver el error real del servidor.</p>";

	echo "<h2>Ambiente</h2><table style='width:100%;border-collapse:collapse;background:#fbfdff;border:1px solid #e5edf7'>";
	contratoDebugFila("PHP", version_compare(PHP_VERSION, "7.3.0", ">="), PHP_VERSION);
	foreach (array("mbstring", "gd", "iconv", "zlib", "curl", "json") as $ext) {
		contratoDebugFila("Extensión ".$ext, extension_loaded($ext), extension_loaded($ext) ? "Cargada" : "No cargada");
	}
	contratoDebugFila("Temp PHP escribible", is_writable(sys_get_temp_dir()), sys_get_temp_dir());
	echo "</table>";

	echo "<h2>Rutas</h2><table style='width:100%;border-collapse:collapse;background:#fbfdff;border:1px solid #e5edf7'>";
	contratoDebugFila("tcpdf.php", is_file($tcpdfFile), $tcpdfFile);
	contratoDebugFila("include", is_file($includeFile), $includeFile);
	contratoDebugFila("config", is_file($configFile), $configFile);
	contratoDebugFila("ICONO.png", is_file($logoFile), $logoFile);
	if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0775, true); }
	contratoDebugFila("cache", is_dir($cacheDir) && is_writable($cacheDir), $cacheDir);
	echo "</table>";

	echo "<h2>Generación TCPDF</h2><pre style='white-space:pre-wrap;background:#0d2238;color:#dff6ff;border-radius:14px;padding:16px;line-height:1.5'>";
	try {
		chdir(__DIR__);
		require_once $includeFile;
		echo "Include cargado correctamente.\n";
		echo "TCPDF disponible: " . (class_exists("TCPDF") ? "sí" : "no") . "\n";
		$versionFile = dirname(__DIR__) . "/VERSION";
		echo "TCPDF versión: " . (is_file($versionFile) ? trim((string)file_get_contents($versionFile)) : "no detectada") . "\n";
		echo "K_PATH_CACHE: " . (defined("K_PATH_CACHE") ? K_PATH_CACHE : "no definido") . "\n";

		$pdfDebug = new TCPDF("P", "mm", array(210, 290), true, "UTF-8", false);
		$pdfDebug->setPrintHeader(false);
		$pdfDebug->setPrintFooter(false);
		$pdfDebug->AddPage();
		$pdfDebug->SetFont("helvetica", "B", 16);
		$pdfDebug->Cell(0, 10, "Diagnostico PDF TechMind OK", 0, 1, "L");
		if (is_file($logoFile)) {
			$pdfDebug->Image($logoFile, 15, 30, 22, 22);
		}
		$pdfBytes = $pdfDebug->Output("", "S");
		echo "PDF generado correctamente. Tamaño: " . strlen($pdfBytes) . " bytes.\n\n";
		echo "RESULTADO FINAL: PDF OK";
	} catch (Throwable $e) {
		echo "ERROR REAL DEL SERVIDOR:\n";
		echo $e->getMessage() . "\n\n";
		echo "Archivo: " . $e->getFile() . "\n";
		echo "Línea: " . $e->getLine() . "\n\n";
		echo $e->getTraceAsString();
	}
	echo "</pre>";
	echo "<p style='color:#d92d20;font-weight:700'>Cuando terminemos, quita este debug o deja de usar la clave.</p>";
	echo "</section></body></html>";
	exit;
}

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




