<?php
declare(strict_types=1);

/**
 * Diagnóstico temporal para producción.
 * Abrir con:
 * /extensiones/tcpdf/pdf/diagnostico-pdf.php?clave=techmind-pdf-2026
 *
 * IMPORTANTE: eliminar este archivo cuando el problema quede resuelto.
 */

$claveEsperada = 'techmind-pdf-2026';
if (!isset($_GET['clave']) || !hash_equals($claveEsperada, (string) $_GET['clave'])) {
	http_response_code(404);
	exit;
}

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');

function estado_item(string $label, bool $ok, string $detalle = ''): void
{
	$color = $ok ? '#089e5a' : '#d93025';
	$texto = $ok ? 'OK' : 'ERROR';
	echo '<tr>';
	echo '<td style="padding:8px 10px;border-bottom:1px solid #e6eef7;font-weight:700;">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</td>';
	echo '<td style="padding:8px 10px;border-bottom:1px solid #e6eef7;color:' . $color . ';font-weight:800;">' . $texto . '</td>';
	echo '<td style="padding:8px 10px;border-bottom:1px solid #e6eef7;">' . htmlspecialchars($detalle, ENT_QUOTES, 'UTF-8') . '</td>';
	echo '</tr>';
}

echo '<!doctype html><html><head><meta charset="utf-8"><title>Diagnóstico PDF TechMind</title></head>';
echo '<body style="font-family:Arial,sans-serif;background:#f5f8fc;color:#15243b;padding:24px;">';
echo '<section style="max-width:980px;margin:auto;background:#fff;border:1px solid #d9e6f5;border-radius:18px;box-shadow:0 18px 50px rgba(15,54,110,.12);padding:24px;">';
echo '<h1 style="margin:0 0 6px;">Diagnóstico PDF TechMind</h1>';
echo '<p style="margin:0 0 18px;color:#58708f;">Este archivo revisa rutas, extensiones PHP y generación básica de TCPDF.</p>';

$basePdf = __DIR__;
$baseTcpdf = dirname(__DIR__);
$tcpdfFile = $baseTcpdf . '/tcpdf.php';
$includeFile = $basePdf . '/tcpdf_include_notaventa.php';
$configFile = $basePdf . '/config/tcpdf_config_notaventa.php';
$logoFile = $basePdf . '/images/ICONO.png';
$cacheDir = $basePdf . '/cache';

echo '<h2>1) Ambiente del servidor</h2>';
echo '<table style="width:100%;border-collapse:collapse;background:#fbfdff;border:1px solid #e6eef7;border-radius:12px;overflow:hidden;">';
estado_item('Versión PHP', version_compare(PHP_VERSION, '7.3.0', '>='), PHP_VERSION);
foreach (['mbstring', 'gd', 'iconv', 'zlib', 'curl', 'json'] as $extension) {
	estado_item('Extensión ' . $extension, extension_loaded($extension), extension_loaded($extension) ? 'Cargada' : 'No cargada');
}
estado_item('Directorio temporal PHP', is_writable(sys_get_temp_dir()), sys_get_temp_dir());
echo '</table>';

echo '<h2>2) Archivos y rutas TCPDF</h2>';
echo '<table style="width:100%;border-collapse:collapse;background:#fbfdff;border:1px solid #e6eef7;border-radius:12px;overflow:hidden;">';
estado_item('tcpdf.php', is_file($tcpdfFile), $tcpdfFile);
estado_item('include TechMind', is_file($includeFile), $includeFile);
estado_item('config TechMind', is_file($configFile), $configFile);
estado_item('logo ICONO.png', is_file($logoFile), $logoFile);
if (!is_dir($cacheDir)) {
	@mkdir($cacheDir, 0775, true);
}
estado_item('cache TCPDF', is_dir($cacheDir) && is_writable($cacheDir), $cacheDir);
echo '</table>';

echo '<h2>3) Prueba de carga y generación PDF</h2>';
echo '<pre style="white-space:pre-wrap;background:#0d2238;color:#dff6ff;border-radius:14px;padding:16px;line-height:1.5;">';

try {
	require_once $includeFile;
	echo "Include cargado correctamente.\n";
	echo 'TCPDF disponible: ' . (class_exists('TCPDF') ? 'sí' : 'no') . "\n";
	$versionFile = dirname(__DIR__) . '/VERSION';
	$versionTcpdf = is_file($versionFile) ? trim((string) file_get_contents($versionFile)) : (defined('TCPDF_VERSION') ? TCPDF_VERSION : 'no detectada');
	echo 'TCPDF versión: ' . $versionTcpdf . "\n";
	echo 'K_PATH_CACHE: ' . (defined('K_PATH_CACHE') ? K_PATH_CACHE : 'no definido') . "\n";
	echo 'K_PATH_IMAGES: ' . (defined('K_PATH_IMAGES') ? K_PATH_IMAGES : 'no definido') . "\n";

	$pdf = new TCPDF('P', 'mm', [210, 290], true, 'UTF-8', false);
	$pdf->SetCreator('TechMind');
	$pdf->SetAuthor('TechMind');
	$pdf->SetTitle('Diagnóstico PDF');
	$pdf->setPrintHeader(false);
	$pdf->setPrintFooter(false);
	$pdf->AddPage();
	$pdf->SetFont('helvetica', 'B', 16);
	$pdf->Cell(0, 10, 'Diagnóstico PDF TechMind OK', 0, 1, 'L');
	$pdf->SetFont('helvetica', '', 10);
	$pdf->MultiCell(0, 8, 'Si este texto se genera, TCPDF puede crear documentos en este servidor.');

	if (is_file($logoFile)) {
		$pdf->Image($logoFile, 15, 32, 22, 22);
		$pdf->SetXY(42, 35);
		$pdf->MultiCell(0, 8, 'Imagen cargada correctamente desde la ruta de producción.');
	}

	$pdf->write1DBarcode('TM-DIAGNOSTICO-2026', 'C128', 15, 62, 80, 16, 0.35, ['position' => 'S', 'border' => false], 'N');
	$contenido = $pdf->Output('', 'S');
	echo 'PDF generado correctamente. Tamaño: ' . strlen($contenido) . " bytes.\n";
	echo "\nRESULTADO FINAL: PDF OK";
} catch (Throwable $e) {
	echo "ERROR REAL DEL SERVIDOR:\n";
	echo $e->getMessage() . "\n\n";
	echo 'Archivo: ' . $e->getFile() . "\n";
	echo 'Línea: ' . $e->getLine() . "\n\n";
	echo $e->getTraceAsString();
}

echo '</pre>';
echo '<p style="color:#d93025;font-weight:700;">Después de revisarlo, elimine este archivo por seguridad.</p>';
echo '</section></body></html>';
