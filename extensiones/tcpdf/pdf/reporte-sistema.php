<?php
ob_start();

require_once __DIR__ . "/../../../modelos/conexion.php";

function RotatedTextReporteSistema($pdf, $x, $y, $txt, $angle) {
	$pdf->StartTransform();
	$pdf->Rotate($angle, $x, $y);
	$pdf->SetFont('helvetica', 'B', 52);
	$pdf->SetTextColor(50, 50, 50);
	$pdf->Text($x, $y, $txt);
	$pdf->StopTransform();
}

function reporteFechaValida($fecha, $defecto) {
	return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$fecha) ? $fecha : $defecto;
}

function reporteRows($db, $sql, $params = array()) {
	$stmt = $db->prepare($sql);
	foreach($params as $key => $value) {
		$stmt->bindValue($key, $value);
	}
	$stmt->execute();
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function reporteMoney($valor) {
	return "Bs ".number_format((float)$valor, 2);
}

function reporteFechaHoraCorta($fecha) {
	if(!$fecha || $fecha == "-") {
		return "-";
	}
	$timestamp = strtotime($fecha);
	return $timestamp ? date("d/m/Y H:i", $timestamp) : (string)$fecha;
}

function reporteText($valor) {
	return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

function reportePdfText($valor) {
	return html_entity_decode((string)$valor, ENT_QUOTES, "UTF-8");
}

function reporteEstado($valor) {
	return ucwords(str_replace("_", " ", (string)$valor));
}

function reporteOrigenCaja($origen) {
	$nombres = array(
		"venta" => "Venta de productos",
		"servicio" => "Cobro de servicio",
		"desembolso_compra" => "Desembolso de compra",
		"devolucion_compra" => "Devolucion de compra",
		"manual" => "Movimiento manual"
	);
	return $nombres[$origen] ?? ucwords(str_replace("_", " ", (string)$origen));
}

function reporteFinanzasVentas($db, $ventas) {
	$idsProductos = array();
	foreach($ventas as $venta) {
		$productos = json_decode($venta["productos"] ?? "[]", true);
		if(!is_array($productos)) {
			continue;
		}
		foreach($productos as $producto) {
			$idProducto = (int)($producto["id"] ?? 0);
			if($idProducto > 0) {
				$idsProductos[$idProducto] = $idProducto;
			}
		}
	}

	$productosBase = array();
	if(count($idsProductos) > 0) {
		$placeholders = array();
		$paramsProductos = array();
		$i = 0;
		foreach($idsProductos as $idProducto) {
			$key = ":producto_".$i++;
			$placeholders[] = $key;
			$paramsProductos[$key] = $idProducto;
		}
		$rowsProductos = reporteRows($db, "SELECT id, descripcion, precio_compra, precio_venta FROM productos WHERE id IN (".implode(",", $placeholders).")", $paramsProductos);
		foreach($rowsProductos as $rowProducto) {
			$productosBase[(int)$rowProducto["id"]] = $rowProducto;
		}
	}

	$resultado = array(
		"ventas" => array(),
		"items" => array(),
		"totales" => array("capital" => 0, "impuesto" => 0, "ganancia_bruta" => 0, "ganancia_liquida" => 0)
	);

	foreach($ventas as $venta) {
		$idVenta = (int)($venta["id"] ?? 0);
		$productos = json_decode($venta["productos"] ?? "[]", true);
		if(!is_array($productos)) {
			$productos = array();
		}

		$ventaTotal = (float)($venta["total"] ?? 0);
		$detalleItems = array();
		$capital = 0;
		$subtotalProductos = 0;

		foreach($productos as $producto) {
			$cantidad = max(1, (int)($producto["cantidad"] ?? 1));
			$idProducto = (int)($producto["id"] ?? 0);
			$base = $productosBase[$idProducto] ?? array();
			$precioVentaUnitario = (float)($producto["precio"] ?? $producto["precio_venta"] ?? $base["precio_venta"] ?? 0);
			$totalLinea = (float)($producto["total"] ?? ($precioVentaUnitario * $cantidad));
			if($precioVentaUnitario <= 0 && $cantidad > 0) {
				$precioVentaUnitario = $totalLinea / $cantidad;
			}
			$precioCompraUnitario = (float)($producto["precio_compra"] ?? $producto["costo_compra"] ?? $base["precio_compra"] ?? 0);
			$capitalLinea = $precioCompraUnitario * $cantidad;
			$gananciaBrutaLinea = $totalLinea - $capitalLinea;

			$item = array(
				"venta" => $venta["codigo"] ?? ("#".$idVenta),
				"producto" => $producto["descripcion"] ?? ($base["descripcion"] ?? "Producto"),
				"cantidad" => $cantidad,
				"precio_compra" => $precioCompraUnitario,
				"precio_venta" => $precioVentaUnitario,
				"capital" => $capitalLinea,
				"total" => $totalLinea,
				"ganancia_bruta" => $gananciaBrutaLinea
			);

			$detalleItems[] = $item;
			$resultado["items"][] = $item;
			$capital += $capitalLinea;
			$subtotalProductos += $totalLinea;
		}

		$baseImpuesto = $ventaTotal > 0 ? $ventaTotal : $subtotalProductos;
		$impuesto = $baseImpuesto * 0.16;
		$gananciaBruta = $baseImpuesto - $capital;
		$gananciaLiquida = $gananciaBruta - $impuesto;

		$resultado["ventas"][$idVenta] = array(
			"items" => $detalleItems,
			"capital" => $capital,
			"impuesto" => $impuesto,
			"ganancia_bruta" => $gananciaBruta,
			"ganancia_liquida" => $gananciaLiquida
		);

		$resultado["totales"]["capital"] += $capital;
		$resultado["totales"]["impuesto"] += $impuesto;
		$resultado["totales"]["ganancia_bruta"] += $gananciaBruta;
		$resultado["totales"]["ganancia_liquida"] += $gananciaLiquida;
	}

	return $resultado;
}

function reporteHeader($pdf, $titulo, $fechaInicial, $fechaFinal, $ventaIndividual = false) {
	$pdf->SetAlpha(0.08);
	RotatedTextReporteSistema($pdf, 28, 180, 'REPORTE', 45);
	$pdf->Image('images/ICONO.png', 45, 82, 120);
	$pdf->SetAlpha(1);

	$pdf->SetFillColor(230, 240, 255);
	$pdf->Rect(10, 10, 190, 32, 'F');
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
	$pdf->SetTextColor(70, 130, 180);
	$pdf->Cell(75, 7, $titulo, 0, 1, 'R');
	$pdf->SetFont('helvetica', '', 9);
	$pdf->SetXY(120, 22);
	if($ventaIndividual) {
		$pdf->Cell(75, 6, 'REPORTE INDIVIDUAL', 0, 1, 'R');
	}else{
		$pdf->Cell(75, 6, 'PERIODO: '.date("d/m/Y", strtotime($fechaInicial)).' - '.date("d/m/Y", strtotime($fechaFinal)), 0, 1, 'R');
	}
	$pdf->SetXY(120, 28);
	$pdf->Cell(75, 6, 'EMITIDO: '.date("d/m/Y H:i"), 0, 1, 'R');
	$pdf->SetTextColor(0);
	$pdf->Ln(10);
}

function reporteSectionTitle($pdf, $titulo) {
	$pdf->Ln(2);
	$pdf->SetFont('helvetica', 'B', 10);
	$pdf->SetFillColor(18, 62, 88);
	$pdf->SetTextColor(255);
	$pdf->Cell(190, 8, $titulo, 0, 1, 'C', true);
	$pdf->SetTextColor(0);
}

function reporteSummaryCards($pdf, $items) {
	$items = array_slice($items, 0, 4, true);
	$gap = 2;
	$cardWidth = (190 - ($gap * 3)) / 4;
	$cardHeight = 18;
	$xInicio = $pdf->GetX();
	$y = $pdf->GetY() + 2;
	if($y + $cardHeight > 270) {
		$pdf->AddPage();
		$xInicio = $pdf->GetX();
		$y = $pdf->GetY();
	}
	$i = 0;
	foreach($items as $label => $value) {
		$x = $xInicio + ($i * ($cardWidth + $gap));
		$pdf->SetDrawColor(184, 212, 230);
		$pdf->SetFillColor(247, 251, 255);
		$pdf->RoundedRect($x, $y, $cardWidth, $cardHeight, 1.7, '1111', 'DF');
		$pdf->SetXY($x + 2.5, $y + 2.2);
		$pdf->SetTextColor(95, 120, 144);
		$pdf->SetFont('helvetica', 'B', 6.4);
		$pdf->MultiCell($cardWidth - 5, 5, reportePdfText($label), 0, 'L', false, 1, '', '', true, 0, false, true, 5, 'T');
		$pdf->SetXY($x + 2.5, $y + 8.5);
		$pdf->SetTextColor(18, 48, 68);
		$pdf->SetFont('helvetica', 'B', 10);
		$pdf->MultiCell($cardWidth - 5, 7, reportePdfText($value), 0, 'L', false, 1, '', '', true, 0, false, true, 7, 'M');
		$i++;
	}
	$pdf->SetTextColor(0);
	$pdf->SetY($y + $cardHeight + 4);
}

function reporteVentaNota($pdf, $texto) {
	if(trim((string)$texto) === "") {
		return;
	}
	if($pdf->GetY() + 8 > 270) {
		$pdf->AddPage();
	}
	$pdf->SetFont('helvetica', '', 7.5);
	$pdf->SetTextColor(84, 103, 121);
	$pdf->SetFillColor(247, 251, 255);
	$pdf->SetDrawColor(218, 233, 247);
	$pdf->MultiCell(190, 7, reportePdfText($texto), 1, 'L', true, 1);
	$pdf->SetTextColor(0);
	$pdf->Ln(1);
}

function reporteSubtituloTabla($pdf, $texto) {
	if(trim((string)$texto) === "") {
		return;
	}
	if($pdf->GetY() + 7 > 270) {
		$pdf->AddPage();
	}
	$pdf->SetFont('helvetica', 'B', 8);
	$pdf->SetTextColor(18, 62, 88);
	$pdf->SetFillColor(236, 246, 253);
	$pdf->Cell(190, 7, reportePdfText($texto), 0, 1, 'L', true);
	$pdf->SetTextColor(0);
}

function reporteHtmlTable($pdf, $headers, $rows, $widths) {
	$lineHeight = 4.2;
	$pdf->SetFont('helvetica', 'B', 7);
	$pdf->SetFillColor(47, 137, 184);
	$pdf->SetTextColor(255);
	$pdf->SetDrawColor(90, 110, 120);
	$pdf->SetLineWidth(0.15);

	$headerHeight = 8;
	foreach($headers as $i => $header) {
		$headerHeight = max($headerHeight, $pdf->getNumLines(reportePdfText($header), $widths[$i]) * $lineHeight + 2);
	}
	if($pdf->GetY() + $headerHeight > 270) {
		$pdf->AddPage();
	}
	$x = $pdf->GetX();
	$y = $pdf->GetY();
	foreach($headers as $i => $header) {
		$pdf->MultiCell($widths[$i], $headerHeight, reportePdfText($header), 1, 'C', true, 0, $x, $y, true, 0, false, true, $headerHeight, 'M');
		$x += $widths[$i];
	}
	$pdf->Ln($headerHeight);

	if(count($rows) == 0) {
		$pdf->SetFont('helvetica', '', 8);
		$pdf->SetTextColor(0);
		$pdf->SetFillColor(255, 255, 255);
		$pdf->MultiCell(array_sum($widths), 8, 'Sin registros para este reporte.', 1, 'C', true, 1);
		return;
	}

	$pdf->SetFont('helvetica', '', 6.8);
	$pdf->SetTextColor(0);
	foreach($rows as $index => $row) {
		$rowHeight = 7;
		foreach($row as $i => $value) {
			$rowHeight = max($rowHeight, $pdf->getNumLines(reportePdfText($value), $widths[$i]) * $lineHeight + 2);
		}
		if($pdf->GetY() + $rowHeight > 270) {
			$pdf->AddPage();
			$pdf->SetFont('helvetica', 'B', 7);
			$pdf->SetFillColor(47, 137, 184);
			$pdf->SetTextColor(255);
			$x = $pdf->GetX();
			$y = $pdf->GetY();
			foreach($headers as $i => $header) {
				$pdf->MultiCell($widths[$i], $headerHeight, reportePdfText($header), 1, 'C', true, 0, $x, $y, true, 0, false, true, $headerHeight, 'M');
				$x += $widths[$i];
			}
			$pdf->Ln($headerHeight);
			$pdf->SetFont('helvetica', '', 6.8);
			$pdf->SetTextColor(0);
		}
		$fill = ($index % 2 == 1);
		if($fill) {
			$pdf->SetFillColor(247, 251, 253);
		}else{
			$pdf->SetFillColor(255, 255, 255);
		}
		$x = $pdf->GetX();
		$y = $pdf->GetY();
		foreach($row as $i => $value) {
			$align = in_array($i, array(count($row) - 2)) ? 'R' : 'L';
			$pdf->MultiCell($widths[$i], $rowHeight, reportePdfText($value), 1, $align, true, 0, $x, $y, true, 0, false, true, $rowHeight, 'M');
			$x += $widths[$i];
		}
		$pdf->Ln($rowHeight);
	}
	$pdf->Ln(3);
}

function reporteTotales($pdf, $items) {
	$pdf->SetFont('helvetica', '', 8);
	$pdf->SetDrawColor(90, 110, 120);
	$pdf->SetLineWidth(0.15);
	$labelWidth = 135;
	$valueWidth = 55;
	$x = $pdf->GetX();
	$pdf->SetX(200 - $labelWidth - $valueWidth);
	foreach($items as $label => $value) {
		if($pdf->GetY() + 8 > 270) {
			$pdf->AddPage();
			$pdf->SetX(200 - $labelWidth - $valueWidth);
		}
		$y = $pdf->GetY();
		$pdf->SetFont('helvetica', 'B', 8);
		$pdf->SetFillColor(234, 244, 251);
		$pdf->MultiCell($labelWidth, 8, reportePdfText($label), 1, 'L', true, 0, 10, $y, true, 0, false, true, 8, 'M');
		$pdf->MultiCell($valueWidth, 8, reportePdfText($value), 1, 'R', true, 1, 10 + $labelWidth, $y, true, 0, false, true, 8, 'M');
	}
	$pdf->SetX($x);
	$pdf->Ln(3);
}

chdir(__DIR__);
require_once('tcpdf_include_notaventa.php');
require_once __DIR__ . '/pdf-empresa-config.php';

$tipo = $_GET["tipo"] ?? "general";
$permitidos = array("general", "ventas", "servicios", "compras", "caja", "cotizaciones", "clientes", "software", "stock", "ranking");
if(!in_array($tipo, $permitidos)) {
	$tipo = "general";
}

$fechaInicial = reporteFechaValida($_GET["fechaInicial"] ?? null, date("Y-m-01"));
$fechaFinal = reporteFechaValida($_GET["fechaFinal"] ?? null, date("Y-m-d"));
$inicio = $fechaInicial." 00:00:00";
$fin = $fechaFinal." 23:59:59";
$params = array(":inicio" => $inicio, ":fin" => $fin);
$ventaReporteId = max(0, (int)($_GET["idVenta"] ?? 0));

$db = Conexion::conectar();

$whereVentasCobradas = "v.estado_pago = 'aprobado' AND COALESCE(v.fecha_pago, v.fecha) BETWEEN :inicio AND :fin";
$paramsVentasCobradas = $params;
if($ventaReporteId > 0) {
	$whereVentasCobradas .= " AND v.id = :id_venta_reporte";
	$paramsVentasCobradas[":id_venta_reporte"] = $ventaReporteId;
}

$ventasCobradas = reporteRows($db,
	"SELECT v.*, COALESCE(v.fecha_pago, v.fecha) AS fecha_reporte, c.nombre AS cliente, uv.nombre AS vendedor, uc.nombre AS cajero
	 FROM ventas v
	 LEFT JOIN clientes c ON c.id = v.id_cliente
	 LEFT JOIN usuarios uv ON uv.id = v.id_vendedor
	 LEFT JOIN usuarios uc ON uc.id = v.id_cajero
	 WHERE ".$whereVentasCobradas."
	 ORDER BY fecha_reporte DESC, v.id DESC",
	$paramsVentasCobradas
);

$serviciosCobrados = reporteRows($db,
	"SELECT s.*, COALESCE(s.fecha_pago, s.fecha) AS fecha_reporte, c.nombre AS cliente, uv.nombre AS vendedor, uc.nombre AS cajero, ut.nombre AS tecnico
	 FROM servicios_ventas s
	 LEFT JOIN clientes c ON c.id = s.id_cliente
	 LEFT JOIN usuarios uv ON uv.id = s.id_vendedor
	 LEFT JOIN usuarios uc ON uc.id = s.id_cajero
	 LEFT JOIN usuarios ut ON ut.id = s.id_tecnico
	 WHERE s.estado_pago = 'aprobado' AND COALESCE(s.fecha_pago, s.fecha) BETWEEN :inicio AND :fin
	 ORDER BY fecha_reporte DESC, s.id DESC",
	$params
);

$compras = reporteRows($db,
	"SELECT co.*, p.nombre AS proveedor, u.nombre AS usuario
	 FROM compra co
	 LEFT JOIN proveedor p ON p.id = co.id_proveedor
	 LEFT JOIN usuarios u ON u.id = co.id_usuario
	 WHERE co.fecha BETWEEN :inicio AND :fin
	 ORDER BY co.fecha DESC, co.id DESC",
	$params
);

$cotizaciones = reporteRows($db,
	"SELECT ct.*, c.nombre AS cliente, u.nombre AS vendedor
	 FROM cotizaciones ct
	 LEFT JOIN clientes c ON c.id = ct.id_cliente
	 LEFT JOIN usuarios u ON u.id = ct.id_user
	 WHERE ct.fecha BETWEEN :inicio AND :fin
	 ORDER BY ct.fecha DESC, ct.id DESC",
	$params
);

$cajaCajero = max(0, (int)($_GET["cajaCajero"] ?? 0));
$cajaApertura = max(0, (int)($_GET["cajaApertura"] ?? 0));
$cajaTipo = strtolower(trim((string)($_GET["cajaTipo"] ?? "")));
$cajaOrigen = trim((string)($_GET["cajaOrigen"] ?? ""));
if(!in_array($cajaTipo, array("", "ingreso", "egreso"), true)) {
	$cajaTipo = "";
}

$whereCaja = array("m.fecha BETWEEN :inicio AND :fin");
$paramsCaja = $params;
if($cajaCajero > 0) {
	$whereCaja[] = "a.id_cajero = :caja_cajero";
	$paramsCaja[":caja_cajero"] = $cajaCajero;
}
if($cajaApertura > 0) {
	$whereCaja[] = "m.id_apertura = :caja_apertura";
	$paramsCaja[":caja_apertura"] = $cajaApertura;
}
if($cajaTipo !== "") {
	$whereCaja[] = "m.tipo = :caja_tipo";
	$paramsCaja[":caja_tipo"] = $cajaTipo;
}
if($cajaOrigen !== "") {
	$whereCaja[] = "m.origen = :caja_origen";
	$paramsCaja[":caja_origen"] = $cajaOrigen;
}

$movimientosCaja = reporteRows($db,
	"SELECT m.*, a.id_cajero, uc.nombre AS cajero, ur.nombre AS usuario_registro
	 FROM caja_movimientos m
	 INNER JOIN caja_aperturas a ON a.id = m.id_apertura
	 LEFT JOIN usuarios uc ON uc.id = a.id_cajero
	 LEFT JOIN usuarios ur ON ur.id = m.id_usuario
	 WHERE ".implode(" AND ", $whereCaja)."
	 ORDER BY m.fecha DESC, m.id DESC",
	$paramsCaja
);

$whereTurnosCaja = array("a.fecha_apertura BETWEEN :inicio AND :fin");
$paramsTurnosCaja = $params;
if($cajaCajero > 0) {
	$whereTurnosCaja[] = "a.id_cajero = :turno_cajero";
	$paramsTurnosCaja[":turno_cajero"] = $cajaCajero;
}
if($cajaApertura > 0) {
	$whereTurnosCaja[] = "a.id = :turno_apertura";
	$paramsTurnosCaja[":turno_apertura"] = $cajaApertura;
}
$turnosCaja = reporteRows($db,
	"SELECT a.*, u.nombre AS cajero
	 FROM caja_aperturas a
	 LEFT JOIN usuarios u ON u.id = a.id_cajero
	 WHERE ".implode(" AND ", $whereTurnosCaja)."
	 ORDER BY a.fecha_apertura DESC, a.id DESC",
	$paramsTurnosCaja
);

$proyectosSoftware = reporteRows($db,
	"SELECT p.*, s.codigo AS codigo_servicio, s.estado_pago, s.estado_servicio, c.nombre AS cliente, uv.nombre AS vendedor, ud.nombre AS desarrollador
	 FROM proyectos_software p
	 INNER JOIN servicios_ventas s ON s.id = p.id_servicio
	 LEFT JOIN clientes c ON c.id = s.id_cliente
	 LEFT JOIN usuarios uv ON uv.id = s.id_vendedor
	 LEFT JOIN usuarios ud ON ud.id = p.id_desarrollador
	 WHERE p.fecha BETWEEN :inicio AND :fin
	 ORDER BY p.fecha DESC, p.id DESC",
	$params
);

$clientesReporte = reporteRows($db,
	"SELECT id, nombre, documento, email, telefono, direccion, compras, ultima_compra, fecha
	 FROM clientes
	 WHERE fecha BETWEEN :inicio AND :fin
	 ORDER BY fecha DESC, id DESC",
	$params
);

$stock = reporteRows($db,
	"SELECT p.codigo, p.descripcion, p.stock, p.precio_compra, p.precio_venta, c.categoria
	 FROM productos p
	 LEFT JOIN categorias c ON c.id = p.id_categoria
	 WHERE p.stock <= 3
	 ORDER BY p.stock ASC, p.descripcion ASC"
);

$totalVentasProductos = array_sum(array_map(function($row){ return (float)($row["total"] ?? 0); }, $ventasCobradas));
$finanzasVentas = reporteFinanzasVentas($db, $ventasCobradas);
$totalCapitalVentas = (float)($finanzasVentas["totales"]["capital"] ?? 0);
$totalImpuestosVentas = (float)($finanzasVentas["totales"]["impuesto"] ?? 0);
$totalGananciaBrutaVentas = (float)($finanzasVentas["totales"]["ganancia_bruta"] ?? 0);
$totalGananciaLiquidaVentas = (float)($finanzasVentas["totales"]["ganancia_liquida"] ?? 0);
$totalServicios = array_sum(array_map(function($row){ return (float)($row["total"] ?? 0); }, $serviciosCobrados));
$totalVentas = $totalVentasProductos + $totalServicios;
$totalCompras = array_sum(array_map(function($row){ return (float)($row["total"] ?? 0); }, $compras));
$totalCotizaciones = array_sum(array_map(function($row){ return (float)($row["total"] ?? 0); }, $cotizaciones));
$totalSoftware = array_sum(array_map(function($row){ return (float)($row["precio_total"] ?? 0); }, $proyectosSoftware));
$totalSoftwareAdelantos = array_sum(array_map(function($row){ return (float)($row["pago_adelanto"] ?? 0); }, $proyectosSoftware));
$totalSoftwareSaldos = array_sum(array_map(function($row){ return (float)($row["saldo_pendiente"] ?? 0); }, $proyectosSoftware));
$totalClientesCompras = array_sum(array_map(function($row){ return (float)($row["compras"] ?? 0); }, $clientesReporte));
$totalCajaIngresos = 0;
$totalCajaEgresos = 0;
$totalCajaEfectivoIngresos = 0;
$totalCajaEfectivoEgresos = 0;
foreach($movimientosCaja as $movimientoCaja) {
	$montoCaja = (float)($movimientoCaja["monto"] ?? 0);
	if(($movimientoCaja["tipo"] ?? "") === "ingreso") {
		$totalCajaIngresos += $montoCaja;
		if((int)($movimientoCaja["afecta_efectivo"] ?? 0) === 1) {
			$totalCajaEfectivoIngresos += $montoCaja;
		}
	}else if(($movimientoCaja["tipo"] ?? "") === "egreso") {
		$totalCajaEgresos += $montoCaja;
		if((int)($movimientoCaja["afecta_efectivo"] ?? 0) === 1) {
			$totalCajaEfectivoEgresos += $montoCaja;
		}
	}
}
$balanceCaja = $totalCajaIngresos - $totalCajaEgresos;
$balanceCajaEfectivo = $totalCajaEfectivoIngresos - $totalCajaEfectivoEgresos;

$titulos = array(
	"general" => "REPORTE GENERAL",
	"ventas" => "REPORTE DE VENTAS",
	"servicios" => "REPORTE SERVICIOS",
	"compras" => "REPORTE COMPRAS",
	"caja" => "MOVIMIENTOS DE CAJA",
	"cotizaciones" => "REPORTE COTIZACIONES",
	"clientes" => "REPORTE CLIENTES",
	"software" => "REPORTE SOFTWARE",
	"stock" => "REPORTE STOCK",
	"ranking" => "REPORTE RANKING"
);

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->startPageGroup();
$pdf->AddPage();
$tituloReporte = $titulos[$tipo];
if($tipo === "ventas" && $ventaReporteId > 0 && count($ventasCobradas) > 0) {
	$tituloReporte = "REPORTE VENTA ".$ventasCobradas[0]["codigo"];
}
reporteHeader($pdf, $tituloReporte, $fechaInicial, $fechaFinal, $tipo === "ventas" && $ventaReporteId > 0);

if($tipo == "general") {
	reporteSectionTitle($pdf, "RESUMEN GENERAL");
	reporteSummaryCards($pdf, array(
		"Total ventas productos + servicios" => reporteMoney($totalVentas),
		"Ventas de productos cobradas" => reporteMoney($totalVentasProductos),
		"Servicios cobrados" => reporteMoney($totalServicios),
		"Compras solicitadas" => reporteMoney($totalCompras),
		"Cotizaciones emitidas" => reporteMoney($totalCotizaciones),
		"Clientes registrados" => count($clientesReporte),
		"Proyectos software" => reporteMoney($totalSoftware),
		"Productos en stock critico" => count($stock)
	));
}

if($tipo == "general" || $tipo == "ventas") {
	reporteSectionTitle($pdf, "VENTAS DE PRODUCTOS COBRADAS");
	reporteSummaryCards($pdf, array(
		"Total vendido" => reporteMoney($totalVentasProductos),
		"Capital de compra" => reporteMoney($totalCapitalVentas),
		"Impuestos 16%" => reporteMoney($totalImpuestosVentas),
		"Ganancia liquida" => reporteMoney($totalGananciaLiquidaVentas)
	));
	$rows = array();
	foreach($ventasCobradas as $venta) {
		$finanzaVenta = $finanzasVentas["ventas"][(int)($venta["id"] ?? 0)] ?? array("capital" => 0, "impuesto" => 0, "ganancia_liquida" => 0);
		$rows[] = array(
			reporteText($venta["codigo"]),
			reporteText($venta["cliente"] ?? "-"),
			reporteMoney($venta["total"]),
			reporteMoney($finanzaVenta["capital"] ?? 0),
			reporteMoney($finanzaVenta["impuesto"] ?? 0),
			reporteMoney($finanzaVenta["ganancia_liquida"] ?? 0),
			reporteText(reporteFechaHoraCorta($venta["fecha_reporte"] ?? "-"))
		);
	}
	reporteHtmlTable($pdf, array("Codigo", "Cliente", "Vendido", "Capital", "Imp. 16%", "Gan. liquida", "Fecha cobro"), $rows, array(22, 43, 24, 24, 24, 28, 25));

	reporteSectionTitle($pdf, "DETALLE DE PRODUCTOS VENDIDOS");
	$rowsDetalleProductos = array();
	foreach($finanzasVentas["items"] as $itemVenta) {
		$rowsDetalleProductos[] = array(
			reporteText($itemVenta["venta"] ?? "-"),
			reporteText($itemVenta["producto"] ?? "Producto"),
			reporteText($itemVenta["cantidad"] ?? 0),
			reporteMoney($itemVenta["precio_compra"] ?? 0),
			reporteMoney($itemVenta["precio_venta"] ?? 0),
			reporteMoney($itemVenta["capital"] ?? 0),
			reporteMoney($itemVenta["total"] ?? 0),
			reporteMoney($itemVenta["ganancia_bruta"] ?? 0)
		);
	}
	reporteHtmlTable($pdf, array("Venta", "Producto", "Cant.", "Compra U.", "Venta U.", "Capital", "Total", "Bruta"), $rowsDetalleProductos, array(18, 56, 12, 18, 18, 22, 22, 24));
	reporteTotales($pdf, array(
		"Total ventas productos" => reporteMoney($totalVentasProductos),
		"Capital de compra" => reporteMoney($totalCapitalVentas),
		"Impuestos 16%" => reporteMoney($totalImpuestosVentas),
		"Ganancia bruta" => reporteMoney($totalGananciaBrutaVentas),
		"Ganancia liquida" => reporteMoney($totalGananciaLiquidaVentas),
		"Cantidad de ventas cobradas" => count($ventasCobradas)
	));
}

if($tipo == "general" || $tipo == "servicios") {
	reporteSectionTitle($pdf, "SERVICIOS COBRADOS");
	$rows = array();
	foreach($serviciosCobrados as $servicio) {
		$rows[] = array(
			reporteText($servicio["codigo"]),
			reporteText($servicio["tipo_servicio"]),
			reporteText($servicio["cliente"] ?? "-"),
			reporteText($servicio["cajero"] ?? "-"),
			reporteText($servicio["tecnico"] ?? "-"),
			reporteMoney($servicio["total"]),
			reporteText(reporteFechaHoraCorta($servicio["fecha_reporte"] ?? "-"))
		);
	}
	reporteHtmlTable($pdf, array("Codigo", "Servicio", "Cliente", "Cajero", "Tecnico", "Total", "Fecha cobro"), $rows, array(20, 42, 36, 25, 25, 24, 18));
	reporteTotales($pdf, array("Total servicios" => reporteMoney($totalServicios), "Cantidad de servicios cobrados" => count($serviciosCobrados)));
}

if($tipo == "general" || $tipo == "compras") {
	reporteSectionTitle($pdf, "SOLICITUDES Y COMPRAS");
	$rows = array();
	foreach($compras as $compra) {
		$rows[] = array(
			reporteText($compra["codigo"]),
			reporteText($compra["proveedor"] ?? "-"),
			reporteText($compra["usuario"] ?? "-"),
			reporteText(reporteEstado($compra["estado"] ?? "")),
			reporteMoney($compra["total"]),
			reporteText(reporteFechaHoraCorta($compra["fecha"]))
		);
	}
	reporteHtmlTable($pdf, array("Codigo", "Proveedor", "Usuario", "Estado", "Total", "Fecha"), $rows, array(22, 45, 35, 28, 28, 32));
	reporteTotales($pdf, array("Total compras solicitadas" => reporteMoney($totalCompras), "Cantidad de solicitudes" => count($compras)));
}

if($tipo == "general" || $tipo == "caja") {
	reporteSectionTitle($pdf, "RESUMEN DE CAJA");
	reporteSummaryCards($pdf, array(
		"Ingresos registrados" => reporteMoney($totalCajaIngresos),
		"Egresos registrados" => reporteMoney($totalCajaEgresos),
		"Balance del periodo" => reporteMoney($balanceCaja),
		"Flujo neto en efectivo" => reporteMoney($balanceCajaEfectivo)
	));

	reporteSectionTitle($pdf, "APERTURAS Y ARQUEOS");
	$rows = array();
	foreach($turnosCaja as $turno) {
		$rows[] = array(
			reporteText("#".($turno["id"] ?? "-")),
			reporteText($turno["cajero"] ?? "-"),
			reporteText(reporteFechaHoraCorta($turno["fecha_apertura"] ?? "-")),
			reporteText(reporteFechaHoraCorta($turno["fecha_cierre"] ?? "-")),
			reporteText(ucfirst($turno["estado"] ?? "-")),
			reporteMoney($turno["monto_inicial"] ?? 0),
			reporteMoney($turno["monto_esperado_cierre"] ?? 0),
			reporteMoney($turno["monto_contado_cierre"] ?? 0),
			reporteMoney($turno["diferencia"] ?? 0)
		);
	}
	reporteHtmlTable(
		$pdf,
		array("Turno", "Cajero", "Apertura", "Cierre", "Estado", "Inicial", "Esperado", "Contado", "Diferencia"),
		$rows,
		array(12, 28, 23, 23, 16, 22, 22, 22, 22)
	);

	reporteSectionTitle($pdf, "DETALLE DE MOVIMIENTOS");
	$rows = array();
	foreach($movimientosCaja as $movimiento) {
		$referencia = trim((string)($movimiento["codigo_referencia"] ?? ""));
		if($referencia === "") {
			$referencia = trim((string)($movimiento["referencia_tipo"] ?? "-"));
		}
		$rows[] = array(
			reporteText(reporteFechaHoraCorta($movimiento["fecha"] ?? "-")),
			reporteText("#".($movimiento["id_apertura"] ?? "-")),
			reporteText($movimiento["cajero"] ?? "-"),
			reporteText(ucfirst($movimiento["tipo"] ?? "-")),
			reporteText(reporteOrigenCaja($movimiento["origen"] ?? "")),
			reporteText($referencia),
			reporteText($movimiento["metodo_pago"] ?? "-"),
			reporteMoney($movimiento["monto"] ?? 0),
			reporteText($movimiento["descripcion"] ?? "-")
		);
	}
	reporteHtmlTable(
		$pdf,
		array("Fecha", "Turno", "Cajero", "Tipo", "Origen", "Referencia", "Metodo", "Monto", "Detalle"),
		$rows,
		array(22, 12, 24, 14, 24, 20, 17, 20, 37)
	);
	reporteTotales($pdf, array(
		"Total ingresos" => reporteMoney($totalCajaIngresos),
		"Total egresos" => reporteMoney($totalCajaEgresos),
		"Balance ingresos - egresos" => reporteMoney($balanceCaja),
		"Movimientos encontrados" => count($movimientosCaja)
	));
}

if($tipo == "general" || $tipo == "cotizaciones") {
	reporteSectionTitle($pdf, "COTIZACIONES EMITIDAS");
	$rows = array();
	foreach($cotizaciones as $cotizacion) {
		$rows[] = array(
			reporteText($cotizacion["codigo"]),
			reporteText($cotizacion["cliente"] ?? "-"),
			reporteText($cotizacion["vendedor"] ?? "-"),
			reporteText($cotizacion["valido_hasta"] ?? "-"),
			reporteMoney($cotizacion["total"]),
			reporteText(reporteFechaHoraCorta($cotizacion["fecha"]))
		);
	}
	reporteHtmlTable($pdf, array("Codigo", "Cliente", "Vendedor", "Valido hasta", "Total", "Fecha"), $rows, array(22, 48, 32, 28, 28, 32));
	reporteTotales($pdf, array("Total cotizado" => reporteMoney($totalCotizaciones), "Cantidad de cotizaciones" => count($cotizaciones)));
}

if($tipo == "general" || $tipo == "clientes") {
	reporteSectionTitle($pdf, "CLIENTES REGISTRADOS");
	$rows = array();
	foreach($clientesReporte as $cliente) {
		$rows[] = array(
			reporteText($cliente["nombre"] ?? "-"),
			reporteText($cliente["documento"] ?? "-"),
			reporteText($cliente["telefono"] ?? "-"),
			reporteText($cliente["email"] ?? "-"),
			reporteMoney($cliente["compras"] ?? 0),
			reporteText(reporteFechaHoraCorta($cliente["ultima_compra"] ?? "-")),
			reporteText(reporteFechaHoraCorta($cliente["fecha"] ?? "-"))
		);
	}
	reporteHtmlTable($pdf, array("Cliente", "Documento", "Telefono", "Email", "Compras", "Ultima compra", "Registro"), $rows, array(42, 24, 24, 38, 20, 22, 20));
	reporteTotales($pdf, array(
		"Clientes registrados" => count($clientesReporte),
		"Total compras acumuladas" => reporteMoney($totalClientesCompras)
	));
}

if($tipo == "general" || $tipo == "software") {
	reporteSectionTitle($pdf, "DESARROLLO DE SOFTWARE");
	$rows = array();
	foreach($proyectosSoftware as $proyecto) {
		$rows[] = array(
			reporteText($proyecto["codigo"]),
			reporteText($proyecto["nombre_proyecto"]),
			reporteText($proyecto["cliente"] ?? "-"),
			reporteText($proyecto["desarrollador"] ?? "Sin asignar"),
			reporteText(reporteEstado($proyecto["estado"] ?? "")),
			reporteText(((int)($proyecto["porcentaje_avance"] ?? 0))."%"),
			reporteMoney($proyecto["precio_total"] ?? 0),
			reporteMoney($proyecto["pago_adelanto"] ?? 0),
			reporteText($proyecto["fecha_entrega_estimada"] ?? "-")
		);
	}
	reporteHtmlTable($pdf, array("Codigo", "Proyecto", "Cliente", "Desarrollador", "Estado", "Avance", "Total", "Adelanto", "Entrega"), $rows, array(17, 35, 25, 25, 22, 13, 18, 18, 14));
	reporteTotales($pdf, array(
		"Total proyectos software" => reporteMoney($totalSoftware),
		"Adelantos pagados" => reporteMoney($totalSoftwareAdelantos),
		"Saldos pendientes" => reporteMoney($totalSoftwareSaldos),
		"Cantidad de proyectos" => count($proyectosSoftware)
	));
}

if($tipo == "general" || $tipo == "stock") {
	reporteSectionTitle($pdf, "STOCK CRITICO");
	$rows = array();
	foreach($stock as $producto) {
		$rows[] = array(
			reporteText($producto["codigo"]),
			reporteText($producto["descripcion"]),
			reporteText($producto["categoria"] ?? "-"),
			reporteText($producto["stock"]),
			reporteMoney($producto["precio_compra"]),
			reporteMoney($producto["precio_venta"])
		);
	}
	reporteHtmlTable($pdf, array("Codigo", "Producto", "Categoria", "Stock", "P. compra", "P. venta"), $rows, array(25, 65, 35, 15, 25, 25));
	reporteTotales($pdf, array("Productos en stock critico" => count($stock)));
}

if($tipo == "general" || $tipo == "ranking") {
	$productosMasVendidos = array();
	foreach($ventasCobradas as $venta) {
		$productos = json_decode($venta["productos"] ?? "[]", true);
		if(!is_array($productos)) {
			continue;
		}
		foreach($productos as $producto) {
			$id = (string)($producto["id"] ?? $producto["descripcion"] ?? "producto");
			if(!isset($productosMasVendidos[$id])) {
				$productosMasVendidos[$id] = array("descripcion" => $producto["descripcion"] ?? "Producto", "cantidad" => 0, "total" => 0);
			}
			$productosMasVendidos[$id]["cantidad"] += (int)($producto["cantidad"] ?? 0);
			$productosMasVendidos[$id]["total"] += (float)($producto["total"] ?? 0);
		}
	}
	usort($productosMasVendidos, function($a, $b){
		if($a["cantidad"] != $b["cantidad"]) {
			return $b["cantidad"] <=> $a["cantidad"];
		}
		return $b["total"] <=> $a["total"];
	});
	$productosMasVendidos = array_slice($productosMasVendidos, 0, 10);

	$serviciosPorTipo = reporteRows($db,
		"SELECT tipo_servicio, COUNT(*) AS cantidad, COALESCE(SUM(total), 0) AS total
		 FROM servicios_ventas
		 WHERE estado_pago = 'aprobado' AND COALESCE(fecha_pago, fecha) BETWEEN :inicio AND :fin
		 GROUP BY tipo_servicio
		 ORDER BY total DESC",
		$params
	);

	reporteSectionTitle($pdf, "PRODUCTOS MAS VENDIDOS");
	$rows = array();
	foreach($productosMasVendidos as $producto) {
		$rows[] = array(reporteText($producto["descripcion"]), reporteText($producto["cantidad"]), reporteMoney($producto["total"]));
	}
	reporteHtmlTable($pdf, array("Producto", "Cantidad", "Total"), $rows, array(120, 30, 40));

	reporteSectionTitle($pdf, "SERVICIOS POR TIPO");
	$rows = array();
	foreach($serviciosPorTipo as $servicioTipo) {
		$rows[] = array(reporteText($servicioTipo["tipo_servicio"]), reporteText($servicioTipo["cantidad"]), reporteMoney($servicioTipo["total"]));
	}
	reporteHtmlTable($pdf, array("Servicio", "Cantidad", "Total"), $rows, array(120, 30, 40));
}

$pdf->Ln(10);
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(190, 5, 'Reporte generado por TechMind S.R.L.', 0, 1, 'C');

if(ob_get_length()) {
	ob_end_clean();
}
$pdf->Output('reporte-'.$tipo.'-'.$fechaInicial.'-'.$fechaFinal.'.pdf', 'I');
