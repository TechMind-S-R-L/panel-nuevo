<?php

if(($_SESSION["perfil"] ?? "") != "Administrador"){
  echo '<script>window.location = "inicio";</script>';
  return;
}

$dbReportes = Conexion::conectar();
$fechaInicialReporte = $_GET["fechaInicial"] ?? date("Y-m-01");
$fechaFinalReporte = $_GET["fechaFinal"] ?? date("Y-m-d");

if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicialReporte)){
  $fechaInicialReporte = date("Y-m-01");
}

if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFinalReporte)){
  $fechaFinalReporte = date("Y-m-d");
}

$inicioReporte = $fechaInicialReporte." 00:00:00";
$finReporte = $fechaFinalReporte." 23:59:59";
$paramsFecha = array(":inicio" => $inicioReporte, ":fin" => $finReporte);

function tmReporteRows($db, $sql, $params = array()){
  try{
    $stmt = $db->prepare($sql);
    foreach($params as $key => $value){
      $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }catch(Exception $e){
    return array();
  }
}

function tmReporteScalar($db, $sql, $params = array()){
  try{
    $stmt = $db->prepare($sql);
    foreach($params as $key => $value){
      $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->fetchColumn();
  }catch(Exception $e){
    return 0;
  }
}

function tmE($valor){
  return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

function tmMoney($valor){
  return "Bs ".number_format((float)$valor, 2);
}

function tmFechaReporte($fecha){
  if(!$fecha || $fecha == "0000-00-00" || $fecha == "0000-00-00 00:00:00"){
    return "-";
  }

  $time = strtotime($fecha);
  return $time ? date("d/m/Y H:i", $time) : tmE($fecha);
}

function tmEstadoClass($estado){
  $estado = strtolower((string)$estado);

  if(in_array($estado, array("aprobado", "completado", "entregado", "entregado_almacen", "cobrado_y_entregado", "finalizado"))){
    return "success";
  }

  if(in_array($estado, array("pendiente", "pendiente_retiro", "pendiente_almacen", "pendiente_adelanto", "revision_interna", "en_desarrollo", "asignado", "desembolsado", "en_compra"))){
    return "warning";
  }

  if(in_array($estado, array("rechazado", "cancelado", "anulado"))){
    return "danger";
  }

  return "info";
}

function tmEstadoTextoVisible($estado){
  $estado = trim((string)$estado);
  $textos = array(
    "desembolsado" => "En proceso de compra",
    "en_compra" => "En desembolso",
    "entregado_almacen" => "Entregado a almacen",
    "completado" => "Completado con exito",
    "pendiente_adelanto" => "Pendiente adelanto",
    "revision_interna" => "Revision interna",
    "en_desarrollo" => "En desarrollo"
  );

  return $textos[$estado] ?? str_replace("_", " ", $estado ?: "-");
}

function tmReportePdf($tipo, $fechaInicial, $fechaFinal, $extras = array()){
  $parametros = array_merge(array(
    "tipo" => $tipo,
    "fechaInicial" => $fechaInicial,
    "fechaFinal" => $fechaFinal
  ), $extras);
  return "extensiones/tcpdf/pdf/reporte-sistema.php?".http_build_query($parametros);
}

function tmReporteOrigenCaja($origen){
  $nombres = array(
    "venta" => "Venta de productos",
    "servicio" => "Cobro de servicio",
    "desembolso_compra" => "Desembolso de compra",
    "devolucion_compra" => "Devolucion de compra",
    "manual" => "Movimiento manual"
  );
  return $nombres[$origen] ?? ucwords(str_replace("_", " ", (string)$origen));
}

function tmReporteFinanzasVentas($db, $ventas){
  $idsProductos = array();
  foreach($ventas as $venta){
    $productos = json_decode($venta["productos"] ?? "[]", true);
    if(!is_array($productos)){
      continue;
    }
    foreach($productos as $producto){
      $idProducto = (int)($producto["id"] ?? 0);
      if($idProducto > 0){
        $idsProductos[$idProducto] = $idProducto;
      }
    }
  }

  $productosBase = array();
  if(count($idsProductos) > 0){
    $placeholders = array();
    $paramsProductos = array();
    $i = 0;
    foreach($idsProductos as $idProducto){
      $key = ":producto_".$i++;
      $placeholders[] = $key;
      $paramsProductos[$key] = $idProducto;
    }
    $rowsProductos = tmReporteRows($db, "SELECT id, descripcion, precio_compra, precio_venta FROM productos WHERE id IN (".implode(",", $placeholders).")", $paramsProductos);
    foreach($rowsProductos as $rowProducto){
      $productosBase[(int)$rowProducto["id"]] = $rowProducto;
    }
  }

  $resultado = array(
    "ventas" => array(),
    "items" => array(),
    "totales" => array("capital" => 0, "impuesto" => 0, "ganancia_bruta" => 0, "ganancia_liquida" => 0)
  );

  foreach($ventas as $venta){
    $idVenta = (int)($venta["id"] ?? 0);
    $productos = json_decode($venta["productos"] ?? "[]", true);
    if(!is_array($productos)){
      $productos = array();
    }

    $ventaTotal = (float)($venta["total"] ?? 0);
    $detalleItems = array();
    $capital = 0;
    $subtotalProductos = 0;

    foreach($productos as $producto){
      $cantidad = max(1, (int)($producto["cantidad"] ?? 1));
      $idProducto = (int)($producto["id"] ?? 0);
      $base = $productosBase[$idProducto] ?? array();
      $precioVentaUnitario = (float)($producto["precio"] ?? $producto["precio_venta"] ?? $base["precio_venta"] ?? 0);
      $totalLinea = (float)($producto["total"] ?? ($precioVentaUnitario * $cantidad));
      if($precioVentaUnitario <= 0 && $cantidad > 0){
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

$ventasProductos = tmReporteRows($dbReportes,
  "SELECT v.*, c.nombre AS cliente, uv.nombre AS vendedor, uc.nombre AS cajero
   FROM ventas v
   LEFT JOIN clientes c ON c.id = v.id_cliente
   LEFT JOIN usuarios uv ON uv.id = v.id_vendedor
   LEFT JOIN usuarios uc ON uc.id = v.id_cajero
   WHERE v.fecha BETWEEN :inicio AND :fin
   ORDER BY v.fecha DESC, v.id DESC",
  $paramsFecha
);

$ventasProductosCobradas = tmReporteRows($dbReportes,
  "SELECT v.*, COALESCE(v.fecha_pago, v.fecha) AS fecha_reporte, c.nombre AS cliente, uv.nombre AS vendedor, uc.nombre AS cajero
   FROM ventas v
   LEFT JOIN clientes c ON c.id = v.id_cliente
   LEFT JOIN usuarios uv ON uv.id = v.id_vendedor
   LEFT JOIN usuarios uc ON uc.id = v.id_cajero
   WHERE v.estado_pago = 'aprobado' AND COALESCE(v.fecha_pago, v.fecha) BETWEEN :inicio AND :fin
   ORDER BY fecha_reporte DESC, v.id DESC",
  $paramsFecha
);

$servicios = tmReporteRows($dbReportes,
  "SELECT s.*, c.nombre AS cliente, uv.nombre AS vendedor, uc.nombre AS cajero, ut.nombre AS tecnico
   FROM servicios_ventas s
   LEFT JOIN clientes c ON c.id = s.id_cliente
   LEFT JOIN usuarios uv ON uv.id = s.id_vendedor
   LEFT JOIN usuarios uc ON uc.id = s.id_cajero
   LEFT JOIN usuarios ut ON ut.id = s.id_tecnico
   WHERE s.fecha BETWEEN :inicio AND :fin
   ORDER BY s.fecha DESC, s.id DESC",
  $paramsFecha
);

$serviciosCobrados = tmReporteRows($dbReportes,
  "SELECT s.*, COALESCE(s.fecha_pago, s.fecha) AS fecha_reporte, c.nombre AS cliente, uv.nombre AS vendedor, uc.nombre AS cajero, ut.nombre AS tecnico
   FROM servicios_ventas s
   LEFT JOIN clientes c ON c.id = s.id_cliente
   LEFT JOIN usuarios uv ON uv.id = s.id_vendedor
   LEFT JOIN usuarios uc ON uc.id = s.id_cajero
   LEFT JOIN usuarios ut ON ut.id = s.id_tecnico
   WHERE s.estado_pago = 'aprobado' AND COALESCE(s.fecha_pago, s.fecha) BETWEEN :inicio AND :fin
   ORDER BY fecha_reporte DESC, s.id DESC",
  $paramsFecha
);

$compras = tmReporteRows($dbReportes,
  "SELECT co.*, p.nombre AS proveedor, u.nombre AS usuario
   FROM compra co
   LEFT JOIN proveedor p ON p.id = co.id_proveedor
   LEFT JOIN usuarios u ON u.id = co.id_usuario
   WHERE co.fecha BETWEEN :inicio AND :fin
   ORDER BY co.fecha DESC, co.id DESC",
  $paramsFecha
);

$cotizaciones = tmReporteRows($dbReportes,
  "SELECT ct.*, c.nombre AS cliente, u.nombre AS vendedor
   FROM cotizaciones ct
   LEFT JOIN clientes c ON c.id = ct.id_cliente
   LEFT JOIN usuarios u ON u.id = ct.id_user
   WHERE ct.fecha BETWEEN :inicio AND :fin
   ORDER BY ct.fecha DESC, ct.id DESC",
  $paramsFecha
);

$proyectosSoftware = tmReporteRows($dbReportes,
  "SELECT p.*, s.codigo AS codigo_servicio, s.estado_pago, s.estado_servicio, c.nombre AS cliente, uv.nombre AS vendedor, ud.nombre AS desarrollador
   FROM proyectos_software p
   INNER JOIN servicios_ventas s ON s.id = p.id_servicio
   LEFT JOIN clientes c ON c.id = s.id_cliente
   LEFT JOIN usuarios uv ON uv.id = s.id_vendedor
   LEFT JOIN usuarios ud ON ud.id = p.id_desarrollador
   WHERE p.fecha BETWEEN :inicio AND :fin
   ORDER BY p.fecha DESC, p.id DESC",
  $paramsFecha
);

$clientesReporte = tmReporteRows($dbReportes,
  "SELECT id, nombre, documento, email, telefono, direccion, compras, ultima_compra, fecha
   FROM clientes
   WHERE fecha BETWEEN :inicio AND :fin
   ORDER BY fecha DESC, id DESC",
  $paramsFecha
);

$stockCritico = tmReporteRows($dbReportes,
  "SELECT p.codigo, p.descripcion, p.stock, p.precio_compra, p.precio_venta, c.categoria
   FROM productos p
   LEFT JOIN categorias c ON c.id = p.id_categoria
   WHERE p.stock <= 3
   ORDER BY p.stock ASC, p.descripcion ASC"
);

$ventasProductosTotal = array_sum(array_map(function($venta){ return (float)($venta["total"] ?? 0); }, $ventasProductosCobradas));
$ventasProductosFinanzas = tmReporteFinanzasVentas($dbReportes, $ventasProductosCobradas);
$ventasCapitalCompra = (float)($ventasProductosFinanzas["totales"]["capital"] ?? 0);
$ventasImpuesto16 = (float)($ventasProductosFinanzas["totales"]["impuesto"] ?? 0);
$ventasGananciaBruta = (float)($ventasProductosFinanzas["totales"]["ganancia_bruta"] ?? 0);
$ventasGananciaLiquida = (float)($ventasProductosFinanzas["totales"]["ganancia_liquida"] ?? 0);
$serviciosTotal = array_sum(array_map(function($servicio){ return (float)($servicio["total"] ?? 0); }, $serviciosCobrados));
$totalVentasGeneral = $ventasProductosTotal + $serviciosTotal;
$comprasTotal = array_sum(array_map(function($compra){ return (float)($compra["total"] ?? 0); }, $compras));
$cotizacionesTotal = array_sum(array_map(function($cotizacion){ return (float)($cotizacion["total"] ?? 0); }, $cotizaciones));
$softwareTotal = array_sum(array_map(function($proyecto){ return (float)($proyecto["precio_total"] ?? 0); }, $proyectosSoftware));
$softwareAdelantos = array_sum(array_map(function($proyecto){ return (float)($proyecto["pago_adelanto"] ?? 0); }, $proyectosSoftware));
$softwareSaldos = array_sum(array_map(function($proyecto){ return (float)($proyecto["saldo_pendiente"] ?? 0); }, $proyectosSoftware));
$clientesComprasTotal = array_sum(array_map(function($cliente){ return (float)($cliente["compras"] ?? 0); }, $clientesReporte));

$cajaCajero = max(0, (int)($_GET["cajaCajero"] ?? 0));
$cajaApertura = max(0, (int)($_GET["cajaApertura"] ?? 0));
$cajaTipo = strtolower(trim((string)($_GET["cajaTipo"] ?? "")));
$cajaOrigen = trim((string)($_GET["cajaOrigen"] ?? ""));
$tabReporte = ($_GET["reporteTab"] ?? "") === "caja" ? "caja" : "ventas";

if(!in_array($cajaTipo, array("", "ingreso", "egreso"), true)){
  $cajaTipo = "";
}

$cajerosCaja = tmReporteRows($dbReportes,
  "SELECT DISTINCT u.id, u.nombre
   FROM caja_aperturas a
   INNER JOIN usuarios u ON u.id = a.id_cajero
   ORDER BY u.nombre ASC"
);

$aperturasCaja = tmReporteRows($dbReportes,
  "SELECT a.id, a.estado, a.fecha_apertura, u.nombre AS cajero
   FROM caja_aperturas a
   LEFT JOIN usuarios u ON u.id = a.id_cajero
   ORDER BY a.id DESC
   LIMIT 200"
);

$origenesCaja = tmReporteRows($dbReportes,
  "SELECT DISTINCT origen FROM caja_movimientos WHERE origen <> '' ORDER BY origen ASC"
);

$whereCaja = array("m.fecha BETWEEN :inicio AND :fin");
$paramsCaja = $paramsFecha;

if($cajaCajero > 0){
  $whereCaja[] = "a.id_cajero = :caja_cajero";
  $paramsCaja[":caja_cajero"] = $cajaCajero;
}
if($cajaApertura > 0){
  $whereCaja[] = "m.id_apertura = :caja_apertura";
  $paramsCaja[":caja_apertura"] = $cajaApertura;
}
if($cajaTipo !== ""){
  $whereCaja[] = "m.tipo = :caja_tipo";
  $paramsCaja[":caja_tipo"] = $cajaTipo;
}
if($cajaOrigen !== ""){
  $whereCaja[] = "m.origen = :caja_origen";
  $paramsCaja[":caja_origen"] = $cajaOrigen;
}

$movimientosCajaReporte = tmReporteRows($dbReportes,
  "SELECT m.*, a.id_cajero, a.estado AS estado_apertura, a.fecha_apertura, a.fecha_cierre,
          ur.nombre AS usuario_registro, uc.nombre AS cajero
   FROM caja_movimientos m
   INNER JOIN caja_aperturas a ON a.id = m.id_apertura
   LEFT JOIN usuarios ur ON ur.id = m.id_usuario
   LEFT JOIN usuarios uc ON uc.id = a.id_cajero
   WHERE ".implode(" AND ", $whereCaja)."
   ORDER BY m.fecha DESC, m.id DESC",
  $paramsCaja
);

$whereTurnosCaja = array("a.fecha_apertura BETWEEN :inicio AND :fin");
$paramsTurnosCaja = $paramsFecha;
if($cajaCajero > 0){
  $whereTurnosCaja[] = "a.id_cajero = :turno_cajero";
  $paramsTurnosCaja[":turno_cajero"] = $cajaCajero;
}
if($cajaApertura > 0){
  $whereTurnosCaja[] = "a.id = :turno_apertura";
  $paramsTurnosCaja[":turno_apertura"] = $cajaApertura;
}

$turnosCajaReporte = tmReporteRows($dbReportes,
  "SELECT a.*, u.nombre AS cajero
   FROM caja_aperturas a
   LEFT JOIN usuarios u ON u.id = a.id_cajero
   WHERE ".implode(" AND ", $whereTurnosCaja)."
   ORDER BY a.fecha_apertura DESC, a.id DESC",
  $paramsTurnosCaja
);

$cajaIngresosTotal = 0;
$cajaEgresosTotal = 0;
$cajaIngresosEfectivo = 0;
$cajaEgresosEfectivo = 0;
foreach($movimientosCajaReporte as $movimientoCajaReporte){
  $montoCaja = (float)($movimientoCajaReporte["monto"] ?? 0);
  if(($movimientoCajaReporte["tipo"] ?? "") === "ingreso"){
    $cajaIngresosTotal += $montoCaja;
    if((int)($movimientoCajaReporte["afecta_efectivo"] ?? 0) === 1){
      $cajaIngresosEfectivo += $montoCaja;
    }
  }else if(($movimientoCajaReporte["tipo"] ?? "") === "egreso"){
    $cajaEgresosTotal += $montoCaja;
    if((int)($movimientoCajaReporte["afecta_efectivo"] ?? 0) === 1){
      $cajaEgresosEfectivo += $montoCaja;
    }
  }
}

$cajaBalanceTotal = $cajaIngresosTotal - $cajaEgresosTotal;
$cajaBalanceEfectivo = $cajaIngresosEfectivo - $cajaEgresosEfectivo;
$parametrosPdfCaja = array(
  "cajaCajero" => $cajaCajero,
  "cajaApertura" => $cajaApertura,
  "cajaTipo" => $cajaTipo,
  "cajaOrigen" => $cajaOrigen
);

$ventasPendientes = tmReporteScalar($dbReportes, "SELECT COUNT(*) FROM ventas WHERE estado_pago = 'pendiente'");
$serviciosPendientes = tmReporteScalar($dbReportes, "SELECT COUNT(*) FROM servicios_ventas WHERE estado_pago IN ('pendiente','pendiente_retiro')");
$despachosPendientes = tmReporteScalar($dbReportes, "SELECT COUNT(*) FROM ventas WHERE estado_pago = 'aprobado' AND estado_despacho = 'pendiente'");

$productosMasVendidos = array();
foreach($ventasProductosCobradas as $ventaCobrada){
  $productos = json_decode($ventaCobrada["productos"] ?? "[]", true);
  if(!is_array($productos)){
    continue;
  }

  foreach($productos as $producto){
    $idProducto = (string)($producto["id"] ?? $producto["descripcion"] ?? "producto");
    if(!isset($productosMasVendidos[$idProducto])){
      $productosMasVendidos[$idProducto] = array(
        "descripcion" => $producto["descripcion"] ?? "Producto",
        "cantidad" => 0,
        "total" => 0
      );
    }

    $productosMasVendidos[$idProducto]["cantidad"] += (int)($producto["cantidad"] ?? 0);
    $productosMasVendidos[$idProducto]["total"] += (float)($producto["total"] ?? 0);
  }
}

usort($productosMasVendidos, function($a, $b){
  if($a["cantidad"] != $b["cantidad"]){
    return $b["cantidad"] <=> $a["cantidad"];
  }
  return $b["total"] <=> $a["total"];
});

$productosMasVendidos = array_slice($productosMasVendidos, 0, 10);

$serviciosPorTipo = tmReporteRows($dbReportes,
  "SELECT tipo_servicio, COUNT(*) AS cantidad, COALESCE(SUM(total), 0) AS total
   FROM servicios_ventas
   WHERE estado_pago = 'aprobado' AND COALESCE(fecha_pago, fecha) BETWEEN :inicio AND :fin
   GROUP BY tipo_servicio
   ORDER BY total DESC",
  $paramsFecha
);

?>

<div class="content-wrapper reportes-page">
<style>
  .reportes-page{
    background:transparent !important;
  }

  .reportes-page .content{
    padding-top:10px;
  }

  .tm-report-page{
    color:#13243d;
    font-family:"Segoe UI",Roboto,Arial,sans-serif;
  }

  .tm-report-hero{
    display:grid;
    grid-template-columns:minmax(0,1fr) auto;
    gap:18px;
    align-items:center;
    padding:20px 22px;
    margin-bottom:14px;
    border:1px solid rgba(255,255,255,.22);
    border-radius:20px;
    background:linear-gradient(135deg,rgba(10,47,68,.96),rgba(18,145,205,.92));
    color:#fff;
    box-shadow:0 18px 42px rgba(13,54,89,.18);
    overflow:hidden;
    position:relative;
  }

  .tm-report-hero:after{
    content:"";
    position:absolute;
    width:240px;
    height:240px;
    right:-80px;
    bottom:-100px;
    border-radius:50%;
    background:rgba(255,255,255,.13);
  }

  .tm-report-hero h2{
    margin:0 0 6px;
    font-size:26px;
    line-height:1.1;
    font-weight:900;
  }

  .tm-report-hero p{
    margin:0;
    max-width:760px;
    color:rgba(255,255,255,.9);
    font-size:13px;
    font-weight:700;
  }

  .tm-report-hero-actions{
    position:relative;
    z-index:1;
    display:flex;
    gap:9px;
    flex-wrap:wrap;
    justify-content:flex-end;
  }

  .tm-report-btn{
    border:0;
    border-radius:12px;
    min-height:39px;
    padding:9px 13px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:7px;
    color:#174066;
    background:#fff;
    font-size:12px;
    font-weight:900;
    box-shadow:0 10px 22px rgba(0,0,0,.14);
  }

  .tm-report-btn:hover,
  .tm-report-btn:focus{
    color:#0f5d91;
    text-decoration:none;
    filter:brightness(.97);
  }

  .tm-report-btn.primary{
    color:#fff;
    background:linear-gradient(135deg,#1b5da8,#13a6dc);
  }

  .tm-report-btn.success{
    color:#fff;
    background:#10b981;
  }

  .tm-report-btn.info{
    color:#fff;
    background:#06a9d9;
  }

  .tm-report-btn.warning{
    color:#fff;
    background:#f59e0b;
  }

  .tm-report-filter{
    display:grid;
    grid-template-columns:190px 190px minmax(0,1fr);
    gap:12px;
    align-items:end;
    padding:14px;
    margin-bottom:14px;
    border:1px solid rgba(176,207,232,.8);
    border-radius:18px;
    background:rgba(255,255,255,.78);
    box-shadow:0 14px 34px rgba(32,77,118,.08);
    backdrop-filter:blur(8px);
  }

  .tm-report-field label{
    display:block;
    margin-bottom:6px;
    color:#526a84;
    font-size:11px;
    font-weight:900;
    text-transform:uppercase;
  }

  .tm-report-field .form-control{
    height:40px;
    border-radius:12px;
    border-color:#d9e7f5;
    box-shadow:none;
    font-weight:800;
  }

  .tm-report-filter-actions{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    justify-content:flex-end;
  }

  .tm-report-filter.caja-filter{
    grid-template-columns:repeat(4,minmax(150px,1fr)) auto;
    margin:0 0 14px;
    padding:12px;
    border-radius:15px;
    background:rgba(247,251,255,.82);
    box-shadow:none;
  }

  .tm-caja-summary{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:10px;
    margin-bottom:14px;
  }

  .tm-caja-summary .tm-report-kpi{min-height:96px;}
  .tm-caja-amount.ingreso{color:#07865c;}
  .tm-caja-amount.egreso{color:#d84b42;}

  .tm-caja-type{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:5px 9px;
    border-radius:999px;
    font-size:10px;
    font-weight:900;
    text-transform:uppercase;
  }

  .tm-caja-type.ingreso{color:#087353;background:#e4f8f0;}
  .tm-caja-type.egreso{color:#b83b35;background:#ffebe9;}

  .tm-report-kpis{
    display:grid;
    grid-template-columns:repeat(6,minmax(0,1fr));
    gap:12px;
    margin-bottom:12px;
  }

  .tm-report-kpi{
    min-height:112px;
    padding:13px;
    border:1px solid rgba(39,114,187,.14);
    border-radius:17px;
    background:rgba(255,255,255,.76);
    box-shadow:0 12px 30px rgba(20,80,135,.08);
    backdrop-filter:blur(8px);
    position:relative;
    overflow:hidden;
  }

  .tm-report-kpi:after{
    content:"";
    position:absolute;
    right:-36px;
    bottom:-44px;
    width:105px;
    height:105px;
    border-radius:50%;
    background:rgba(28,111,185,.12);
  }

  .tm-report-kpi i{
    width:37px;
    height:37px;
    border-radius:13px;
    display:flex;
    align-items:center;
    justify-content:center;
    margin-bottom:10px;
    color:#fff;
    background:linear-gradient(135deg,#1d75d1,#0bb4dc);
    font-size:17px;
  }

  .tm-report-kpi span{
    display:block;
    color:#667b91;
    font-size:11px;
    font-weight:900;
    text-transform:uppercase;
  }

  .tm-report-kpi strong{
    display:block;
    margin:3px 0;
    color:#13243d;
    font-size:21px;
    line-height:1.05;
    font-weight:900;
  }

  .tm-report-kpi small{
    display:block;
    color:#60758d;
    font-size:11px;
    font-weight:800;
  }

  .tm-report-mini-kpis{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:12px;
    margin-bottom:14px;
  }

  .tm-report-tabs{
    border:1px solid rgba(176,207,232,.8);
    border-radius:18px;
    background:rgba(255,255,255,.76);
    box-shadow:0 14px 34px rgba(32,77,118,.08);
    overflow:hidden;
    backdrop-filter:blur(8px);
  }

  .tm-report-tabs > .nav-tabs{
    display:flex;
    gap:6px;
    flex-wrap:wrap;
    padding:10px;
    border:0;
    background:rgba(241,247,253,.72);
  }

  .tm-report-tabs > .nav-tabs > li{
    margin:0;
  }

  .tm-report-tabs > .nav-tabs > li > a{
    border:0 !important;
    border-radius:12px;
    color:#49637d;
    padding:9px 12px;
    font-size:12px;
    font-weight:900;
  }

  .tm-report-tabs > .nav-tabs > li.active > a,
  .tm-report-tabs > .nav-tabs > li > a:hover{
    color:#fff !important;
    background:linear-gradient(135deg,#1b5da8,#13a6dc) !important;
  }

  .tm-report-tabs .tab-content{
    padding:14px;
    background:transparent;
  }

  .tm-report-section-head{
    display:grid;
    grid-template-columns:minmax(0,1fr) auto;
    gap:12px;
    align-items:center;
    margin-bottom:12px;
    padding:12px;
    border:1px solid #dce9f7;
    border-radius:16px;
    background:rgba(255,255,255,.72);
  }

  .tm-report-section-head h3{
    margin:0 0 4px;
    color:#14243a;
    font-size:17px;
    font-weight:900;
  }

  .tm-report-section-head p{
    margin:0;
    color:#60758d;
    font-size:12px;
    font-weight:800;
  }

  .tm-report-section-actions{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    justify-content:flex-end;
  }

  .tm-report-card-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(270px,1fr));
    gap:12px;
  }

  .tm-report-card-grid.wide{
    grid-template-columns:repeat(auto-fill,minmax(300px,1fr));
  }

  .tm-report-card{
    border:1px solid #dce9f7;
    border-radius:16px;
    background:rgba(255,255,255,.88);
    box-shadow:0 10px 24px rgba(22,78,132,.08);
    overflow:hidden;
    min-height:210px;
    display:flex;
    flex-direction:column;
  }

  .tm-report-card-head{
    display:flex;
    justify-content:space-between;
    gap:10px;
    padding:12px;
    border-bottom:1px solid #e5eef8;
    background:linear-gradient(135deg,#eef8ff,#fff);
  }

  .tm-report-code{
    display:inline-flex;
    align-items:center;
    gap:6px;
    max-width:100%;
    padding:5px 8px;
    border-radius:999px;
    background:#eaf5ff;
    color:#155f97;
    font-size:11px;
    line-height:1.1;
    font-weight:900;
    overflow-wrap:anywhere;
  }

  .tm-report-card h4{
    margin:7px 0 0;
    color:#14243a;
    font-size:15px;
    line-height:1.25;
    font-weight:900;
    overflow-wrap:anywhere;
  }

  .tm-report-amount{
    text-align:right;
    color:#0c5d93;
    font-size:15px;
    font-weight:900;
    white-space:nowrap;
  }

  .tm-report-card-body{
    padding:12px;
    display:grid;
    gap:9px;
  }

  .tm-report-info-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:8px;
  }

  .tm-report-info{
    min-width:0;
    border:1px solid #e1ecf8;
    border-radius:12px;
    background:#f8fbff;
    padding:8px;
    color:#273e58;
    font-size:12px;
    line-height:1.25;
    font-weight:800;
    overflow-wrap:anywhere;
  }

  .tm-report-info b{
    display:block;
    margin-bottom:4px;
    color:#6d839a;
    font-size:9px;
    line-height:1;
    font-weight:900;
    text-transform:uppercase;
  }

  .tm-report-finance-strip{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:8px;
    margin:10px 0 0;
  }

  .tm-report-product-list{
    margin-top:10px;
    border:1px dashed #c9dff4;
    border-radius:14px;
    background:rgba(255,255,255,.68);
    overflow:hidden;
  }

  .tm-report-product-row{
    display:grid;
    grid-template-columns:minmax(0,1.4fr) 70px 90px 90px;
    gap:8px;
    padding:8px 10px;
    border-bottom:1px solid #e8f1fb;
    font-size:11px;
    color:#324961;
    align-items:center;
  }

  .tm-report-product-row:last-child{
    border-bottom:0;
  }

  .tm-report-product-row b{
    display:block;
    color:#193449;
    font-size:12px;
    overflow-wrap:anywhere;
  }

  .tm-report-product-row span{
    color:#6d819b;
    font-weight:800;
  }

  .tm-report-badges{
    display:flex;
    flex-wrap:wrap;
    gap:6px;
  }

  .tm-report-badge{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    max-width:100%;
    padding:6px 9px;
    border-radius:999px;
    color:#fff;
    font-size:10px;
    line-height:1.1;
    font-weight:900;
    text-transform:uppercase;
    overflow-wrap:anywhere;
  }

  .tm-report-badge.success{background:#10b981;}
  .tm-report-badge.warning{background:#f59e0b;}
  .tm-report-badge.danger{background:#ef4444;}
  .tm-report-badge.info{background:#0ea5e9;}

  .tm-report-empty{
    grid-column:1 / -1;
    padding:24px;
    border:1px dashed #bcd9f3;
    border-radius:16px;
    background:rgba(255,255,255,.55);
    text-align:center;
    color:#668097;
    font-weight:900;
  }

  .tm-rank-list{
    display:grid;
    gap:10px;
  }

  .tm-rank-item{
    display:grid;
    grid-template-columns:38px minmax(0,1fr) auto;
    gap:10px;
    align-items:center;
    padding:10px;
    border:1px solid #dce9f7;
    border-radius:14px;
    background:rgba(255,255,255,.84);
  }

  .tm-rank-number{
    width:38px;
    height:38px;
    border-radius:13px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:linear-gradient(135deg,#1d75d1,#0bb4dc);
    color:#fff;
    font-weight:900;
  }

  .tm-rank-item h4{
    margin:0 0 3px;
    color:#14243a;
    font-size:14px;
    font-weight:900;
    overflow-wrap:anywhere;
  }

  .tm-rank-item p{
    margin:0;
    color:#657991;
    font-size:12px;
    font-weight:800;
  }

  .tm-rank-total{
    text-align:right;
    font-size:13px;
    font-weight:900;
    color:#0c5d93;
  }

  body.tm-dark-mode .tm-report-filter,
  body.tm-dark-mode .tm-report-kpi,
  body.tm-dark-mode .tm-report-tabs,
  body.tm-dark-mode .tm-report-section-head,
  body.tm-dark-mode .tm-report-card,
  body.tm-dark-mode .tm-rank-item{
    background:rgba(14,29,52,.66);
    border-color:rgba(120,158,205,.28);
    color:#eef6ff;
  }

  body.tm-dark-mode .tm-report-tabs > .nav-tabs,
  body.tm-dark-mode .tm-report-card-head{
    background:rgba(19,42,70,.64);
    border-color:rgba(120,158,205,.25);
  }

  body.tm-dark-mode .tm-report-kpi strong,
  body.tm-dark-mode .tm-report-section-head h3,
  body.tm-dark-mode .tm-report-card h4,
  body.tm-dark-mode .tm-rank-item h4{
    color:#fff;
  }

  body.tm-dark-mode .tm-report-kpi span,
  body.tm-dark-mode .tm-report-kpi small,
  body.tm-dark-mode .tm-report-section-head p,
  body.tm-dark-mode .tm-report-field label,
  body.tm-dark-mode .tm-rank-item p{
    color:#c9dcf0;
  }

  body.tm-dark-mode .tm-report-info{
    background:rgba(10,24,42,.62);
    border-color:rgba(120,158,205,.24);
    color:#eef6ff;
  }

  body.tm-dark-mode .tm-report-field .form-control{
    background:#0c1a2d;
    border-color:#28425f;
    color:#eef6ff;
  }

  @media(max-width:1200px){
    .tm-report-kpis{
      grid-template-columns:repeat(3,minmax(0,1fr));
    }
  }

  @media(max-width:991px){
    .tm-report-hero,
    .tm-report-filter,
    .tm-report-filter.caja-filter{
      grid-template-columns:1fr;
    }

    .tm-report-filter-actions,
    .tm-report-hero-actions{
      justify-content:flex-start;
    }

    .tm-report-mini-kpis,
    .tm-caja-summary{
      grid-template-columns:repeat(2,minmax(0,1fr));
    }
  }

  @media(max-width:640px){
    .tm-report-kpis,
    .tm-report-mini-kpis,
    .tm-caja-summary,
    .tm-report-info-grid,
    .tm-report-finance-strip,
    .tm-report-product-row,
    .tm-report-section-head{
      grid-template-columns:1fr;
    }

    .tm-report-section-actions{
      justify-content:flex-start;
    }

    .tm-report-btn{
      width:100%;
    }
  }
</style>

  <section class="content-header">
    <h1>Reportes</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Reportes</li>
    </ol>
  </section>

  <section class="content">
    <div class="tm-report-page">

      <div class="tm-report-hero">
        <div>
          <h2>Panel de reportes del sistema</h2>
          <p>Consulta el movimiento real del periodo seleccionado: ventas cobradas, servicios, compras, cotizaciones, clientes, proyectos de software y stock critico.</p>
        </div>
        <div class="tm-report-hero-actions">
          <a class="tm-report-btn" target="_blank" href="<?php echo tmReportePdf("general", $fechaInicialReporte, $fechaFinalReporte); ?>"><i class="fa fa-print"></i> Reporte general</a>
          <a class="tm-report-btn success" href="vistas/modulos/descargar-reporte.php?reporte=reporte&fechaInicial=<?php echo urlencode($fechaInicialReporte); ?>&fechaFinal=<?php echo urlencode($fechaFinalReporte); ?>"><i class="fa fa-file-excel-o"></i> Excel ventas</a>
        </div>
      </div>

      <form class="tm-report-filter" method="get" action="index.php">
        <input type="hidden" name="ruta" value="reportes">
        <div class="tm-report-field">
          <label>Fecha inicial</label>
          <input type="date" class="form-control" name="fechaInicial" value="<?php echo tmE($fechaInicialReporte); ?>">
        </div>
        <div class="tm-report-field">
          <label>Fecha final</label>
          <input type="date" class="form-control" name="fechaFinal" value="<?php echo tmE($fechaFinalReporte); ?>">
        </div>
        <div class="tm-report-filter-actions">
          <button type="submit" class="tm-report-btn primary"><i class="fa fa-filter"></i> Generar reporte</button>
          <a class="tm-report-btn" href="reportes"><i class="fa fa-refresh"></i> Mes actual</a>
          <a class="tm-report-btn info" target="_blank" href="<?php echo tmReportePdf("general", $fechaInicialReporte, $fechaFinalReporte); ?>"><i class="fa fa-file-pdf-o"></i> PDF general</a>
        </div>
      </form>

      <div class="tm-report-kpis">
        <div class="tm-report-kpi"><i class="fa fa-line-chart"></i><span>Total ventas</span><strong><?php echo tmMoney($totalVentasGeneral); ?></strong><small>Productos + servicios cobrados</small></div>
        <div class="tm-report-kpi"><i class="fa fa-shopping-cart"></i><span>Productos cobrados</span><strong><?php echo tmMoney($ventasProductosTotal); ?></strong><small><?php echo count($ventasProductosCobradas); ?> cobro(s)</small></div>
        <div class="tm-report-kpi"><i class="fa fa-wrench"></i><span>Servicios cobrados</span><strong><?php echo tmMoney($serviciosTotal); ?></strong><small><?php echo count($serviciosCobrados); ?> cobro(s)</small></div>
        <div class="tm-report-kpi"><i class="fa fa-shopping-bag"></i><span>Compras solicitadas</span><strong><?php echo tmMoney($comprasTotal); ?></strong><small><?php echo count($compras); ?> solicitud(es)</small></div>
        <div class="tm-report-kpi"><i class="fa fa-file-text-o"></i><span>Cotizaciones</span><strong><?php echo tmMoney($cotizacionesTotal); ?></strong><small><?php echo count($cotizaciones); ?> emitida(s)</small></div>
        <div class="tm-report-kpi"><i class="fa fa-code"></i><span>Software</span><strong><?php echo tmMoney($softwareTotal); ?></strong><small><?php echo count($proyectosSoftware); ?> proyecto(s)</small></div>
      </div>

      <div class="tm-report-mini-kpis">
        <div class="tm-report-kpi"><i class="fa fa-clock-o"></i><span>Ventas por cobrar</span><strong><?php echo (int)$ventasPendientes; ?></strong><small>Pendientes en caja</small></div>
        <div class="tm-report-kpi"><i class="fa fa-credit-card"></i><span>Servicios por cobrar</span><strong><?php echo (int)$serviciosPendientes; ?></strong><small>Pendientes en caja</small></div>
        <div class="tm-report-kpi"><i class="fa fa-truck"></i><span>Despachos pendientes</span><strong><?php echo (int)$despachosPendientes; ?></strong><small>Pagados sin entregar</small></div>
        <div class="tm-report-kpi"><i class="fa fa-users"></i><span>Clientes registrados</span><strong><?php echo count($clientesReporte); ?></strong><small><?php echo tmMoney($clientesComprasTotal); ?> en compras</small></div>
      </div>

      <div class="nav-tabs-custom tm-report-tabs">
        <ul class="nav nav-tabs">
          <li class="<?php echo $tabReporte === "ventas" ? "active" : ""; ?>"><a href="#repVentas" data-toggle="tab"><i class="fa fa-shopping-cart"></i> Ventas</a></li>
          <li><a href="#repServicios" data-toggle="tab"><i class="fa fa-wrench"></i> Servicios</a></li>
          <li><a href="#repCompras" data-toggle="tab"><i class="fa fa-shopping-bag"></i> Compras</a></li>
          <li class="<?php echo $tabReporte === "caja" ? "active" : ""; ?>"><a href="#repCaja" data-toggle="tab"><i class="fa fa-calculator"></i> Caja</a></li>
          <li><a href="#repCotizaciones" data-toggle="tab"><i class="fa fa-file-text-o"></i> Cotizaciones</a></li>
          <li><a href="#repClientes" data-toggle="tab"><i class="fa fa-users"></i> Clientes</a></li>
          <li><a href="#repSoftware" data-toggle="tab"><i class="fa fa-code"></i> Software</a></li>
          <li><a href="#repStock" data-toggle="tab"><i class="fa fa-warning"></i> Stock critico</a></li>
          <li><a href="#repRanking" data-toggle="tab"><i class="fa fa-trophy"></i> Rankings</a></li>
        </ul>

        <div class="tab-content">
          <div class="tab-pane <?php echo $tabReporte === "ventas" ? "active" : ""; ?>" id="repVentas">
            <div class="tm-report-section-head">
              <div>
                <h3>Ventas de productos</h3>
                <p><?php echo count($ventasProductos); ?> venta(s) creadas en el periodo. Total cobrado: <?php echo tmMoney($ventasProductosTotal); ?>. Ganancia liquida estimada: <?php echo tmMoney($ventasGananciaLiquida); ?>.</p>
              </div>
              <div class="tm-report-section-actions">
                <a class="tm-report-btn" target="_blank" href="<?php echo tmReportePdf("ventas", $fechaInicialReporte, $fechaFinalReporte); ?>"><i class="fa fa-print"></i> Imprimir ventas</a>
              </div>
            </div>

            <div class="tm-report-mini-kpis">
              <div class="tm-report-kpi"><i class="fa fa-money"></i><span>Capital de compra</span><strong><?php echo tmMoney($ventasCapitalCompra); ?></strong><small>Costo base de productos</small></div>
              <div class="tm-report-kpi"><i class="fa fa-percent"></i><span>Impuestos 16%</span><strong><?php echo tmMoney($ventasImpuesto16); ?></strong><small>Calculado sobre ventas cobradas</small></div>
              <div class="tm-report-kpi"><i class="fa fa-line-chart"></i><span>Ganancia bruta</span><strong><?php echo tmMoney($ventasGananciaBruta); ?></strong><small>Venta - capital</small></div>
              <div class="tm-report-kpi"><i class="fa fa-check-circle"></i><span>Ganancia liquida</span><strong><?php echo tmMoney($ventasGananciaLiquida); ?></strong><small>Bruta - impuestos</small></div>
            </div>

            <div class="tm-report-card-grid wide">
              <?php if(count($ventasProductos) == 0): ?>
                <div class="tm-report-empty">Sin ventas de productos en este rango.</div>
              <?php endif; ?>
              <?php foreach($ventasProductos as $venta): ?>
                <?php $finanzaVenta = $ventasProductosFinanzas["ventas"][(int)($venta["id"] ?? 0)] ?? array("items" => array(), "capital" => 0, "impuesto" => 0, "ganancia_bruta" => 0, "ganancia_liquida" => 0); ?>
                <article class="tm-report-card">
                  <div class="tm-report-card-head">
                    <div>
                      <span class="tm-report-code"><i class="fa fa-hashtag"></i> <?php echo tmE($venta["codigo"] ?? "-"); ?></span>
                      <h4><?php echo tmE($venta["cliente"] ?? "Sin cliente"); ?></h4>
                    </div>
                    <div class="tm-report-amount"><?php echo tmMoney($venta["total"] ?? 0); ?></div>
                  </div>
                  <div class="tm-report-card-body">
                    <div class="tm-report-badges">
                      <span class="tm-report-badge <?php echo tmEstadoClass($venta["estado_pago"] ?? ""); ?>">Pago: <?php echo tmE(tmEstadoTextoVisible($venta["estado_pago"] ?? "")); ?></span>
                      <span class="tm-report-badge <?php echo tmEstadoClass($venta["estado_despacho"] ?? ""); ?>">Despacho: <?php echo tmE(tmEstadoTextoVisible($venta["estado_despacho"] ?? "")); ?></span>
                    </div>
                    <div class="tm-report-info-grid">
                      <div class="tm-report-info"><b>Vendedor</b><?php echo tmE($venta["vendedor"] ?? "-"); ?></div>
                      <div class="tm-report-info"><b>Cajero</b><?php echo tmE($venta["cajero"] ?? "-"); ?></div>
                      <div class="tm-report-info"><b>Creacion</b><?php echo tmFechaReporte($venta["fecha"] ?? ""); ?></div>
                      <div class="tm-report-info"><b>Cobro</b><?php echo tmFechaReporte($venta["fecha_reporte"] ?? ""); ?></div>
                    </div>
                    <div class="tm-report-finance-strip">
                      <div class="tm-report-info"><b>Capital compra</b><?php echo tmMoney($finanzaVenta["capital"] ?? 0); ?></div>
                      <div class="tm-report-info"><b>Impuesto 16%</b><?php echo tmMoney($finanzaVenta["impuesto"] ?? 0); ?></div>
                      <div class="tm-report-info"><b>Ganancia bruta</b><?php echo tmMoney($finanzaVenta["ganancia_bruta"] ?? 0); ?></div>
                      <div class="tm-report-info"><b>Ganancia liquida</b><?php echo tmMoney($finanzaVenta["ganancia_liquida"] ?? 0); ?></div>
                    </div>
                    <?php if(!empty($finanzaVenta["items"])): ?>
                      <div class="tm-report-product-list">
                        <?php foreach($finanzaVenta["items"] as $itemFinanciero): ?>
                          <div class="tm-report-product-row">
                            <div><b><?php echo tmE($itemFinanciero["producto"] ?? "Producto"); ?></b><span>Cant: <?php echo (int)($itemFinanciero["cantidad"] ?? 0); ?></span></div>
                            <div><span>Compra</span><?php echo tmMoney($itemFinanciero["precio_compra"] ?? 0); ?></div>
                            <div><span>Venta</span><?php echo tmMoney($itemFinanciero["precio_venta"] ?? 0); ?></div>
                            <div><span>Total</span><?php echo tmMoney($itemFinanciero["total"] ?? 0); ?></div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="tab-pane" id="repServicios">
            <div class="tm-report-section-head">
              <div>
                <h3>Servicios vendidos</h3>
                <p><?php echo count($servicios); ?> servicio(s) creados en el periodo. Total cobrado: <?php echo tmMoney($serviciosTotal); ?>.</p>
              </div>
              <div class="tm-report-section-actions">
                <a class="tm-report-btn" target="_blank" href="<?php echo tmReportePdf("servicios", $fechaInicialReporte, $fechaFinalReporte); ?>"><i class="fa fa-print"></i> Imprimir servicios</a>
              </div>
            </div>

            <div class="tm-report-card-grid wide">
              <?php if(count($servicios) == 0): ?>
                <div class="tm-report-empty">Sin servicios en este rango.</div>
              <?php endif; ?>
              <?php foreach($servicios as $servicio): ?>
                <?php $servicioNombre = trim(($servicio["tipo_servicio"] ?? "Servicio")." / ".($servicio["tipo_instalacion"] ?? ""), " /"); ?>
                <article class="tm-report-card">
                  <div class="tm-report-card-head">
                    <div>
                      <span class="tm-report-code"><i class="fa fa-hashtag"></i> <?php echo tmE($servicio["codigo"] ?? "-"); ?></span>
                      <h4><?php echo tmE($servicioNombre); ?></h4>
                    </div>
                    <div class="tm-report-amount"><?php echo tmMoney($servicio["total"] ?? 0); ?></div>
                  </div>
                  <div class="tm-report-card-body">
                    <div class="tm-report-badges">
                      <span class="tm-report-badge <?php echo tmEstadoClass($servicio["estado_pago"] ?? ""); ?>">Pago: <?php echo tmE(tmEstadoTextoVisible($servicio["estado_pago"] ?? "")); ?></span>
                      <span class="tm-report-badge <?php echo tmEstadoClass($servicio["estado_servicio"] ?? ""); ?>">Servicio: <?php echo tmE(tmEstadoTextoVisible($servicio["estado_servicio"] ?? "")); ?></span>
                    </div>
                    <div class="tm-report-info-grid">
                      <div class="tm-report-info"><b>Cliente</b><?php echo tmE($servicio["cliente"] ?? "Sin cliente"); ?></div>
                      <div class="tm-report-info"><b>Tecnico</b><?php echo tmE($servicio["tecnico"] ?? "-"); ?></div>
                      <div class="tm-report-info"><b>Vendedor</b><?php echo tmE($servicio["vendedor"] ?? "-"); ?></div>
                      <div class="tm-report-info"><b>Cajero</b><?php echo tmE($servicio["cajero"] ?? "-"); ?></div>
                      <div class="tm-report-info"><b>Creacion</b><?php echo tmFechaReporte($servicio["fecha"] ?? ""); ?></div>
                      <div class="tm-report-info"><b>Cobro</b><?php echo tmFechaReporte($servicio["fecha_reporte"] ?? ""); ?></div>
                    </div>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="tab-pane" id="repCompras">
            <div class="tm-report-section-head">
              <div>
                <h3>Solicitudes y compras</h3>
                <p><?php echo count($compras); ?> movimiento(s) de compra. Total solicitado: <?php echo tmMoney($comprasTotal); ?>.</p>
              </div>
              <div class="tm-report-section-actions">
                <a class="tm-report-btn" target="_blank" href="<?php echo tmReportePdf("compras", $fechaInicialReporte, $fechaFinalReporte); ?>"><i class="fa fa-print"></i> Imprimir compras</a>
              </div>
            </div>

            <div class="tm-report-card-grid">
              <?php if(count($compras) == 0): ?>
                <div class="tm-report-empty">Sin compras en este rango.</div>
              <?php endif; ?>
              <?php foreach($compras as $compra): ?>
                <article class="tm-report-card">
                  <div class="tm-report-card-head">
                    <div>
                      <span class="tm-report-code"><i class="fa fa-hashtag"></i> <?php echo tmE($compra["codigo"] ?? "-"); ?></span>
                      <h4><?php echo tmE($compra["proveedor"] ?? "Sin proveedor"); ?></h4>
                    </div>
                    <div class="tm-report-amount"><?php echo tmMoney($compra["total"] ?? 0); ?></div>
                  </div>
                  <div class="tm-report-card-body">
                    <div class="tm-report-badges">
                      <span class="tm-report-badge <?php echo tmEstadoClass($compra["estado"] ?? ""); ?>"><?php echo tmE(tmEstadoTextoVisible($compra["estado"] ?? "")); ?></span>
                    </div>
                    <div class="tm-report-info-grid">
                      <div class="tm-report-info"><b>Solicitante</b><?php echo tmE($compra["usuario"] ?? "-"); ?></div>
                      <div class="tm-report-info"><b>Fecha</b><?php echo tmFechaReporte($compra["fecha"] ?? ""); ?></div>
                    </div>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="tab-pane <?php echo $tabReporte === "caja" ? "active" : ""; ?>" id="repCaja">
            <div class="tm-report-section-head">
              <div>
                <h3>Ingresos, egresos y movimientos de caja</h3>
                <p><?php echo count($movimientosCajaReporte); ?> movimiento(s) encontrados. Balance del filtro: <?php echo tmMoney($cajaBalanceTotal); ?>.</p>
              </div>
              <div class="tm-report-section-actions">
                <a class="tm-report-btn" target="_blank" href="<?php echo tmReportePdf("caja", $fechaInicialReporte, $fechaFinalReporte, $parametrosPdfCaja); ?>"><i class="fa fa-print"></i> Imprimir movimientos</a>
              </div>
            </div>

            <form class="tm-report-filter caja-filter" method="get" action="index.php">
              <input type="hidden" name="ruta" value="reportes">
              <input type="hidden" name="reporteTab" value="caja">
              <input type="hidden" name="fechaInicial" value="<?php echo tmE($fechaInicialReporte); ?>">
              <input type="hidden" name="fechaFinal" value="<?php echo tmE($fechaFinalReporte); ?>">
              <div class="tm-report-field">
                <label>Cajero</label>
                <select class="form-control" name="cajaCajero">
                  <option value="0">Todos los cajeros</option>
                  <?php foreach($cajerosCaja as $cajeroCaja): ?>
                    <option value="<?php echo (int)$cajeroCaja["id"]; ?>" <?php echo $cajaCajero === (int)$cajeroCaja["id"] ? "selected" : ""; ?>><?php echo tmE($cajeroCaja["nombre"]); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="tm-report-field">
                <label>Apertura / turno</label>
                <select class="form-control" name="cajaApertura">
                  <option value="0">Todas las aperturas</option>
                  <?php foreach($aperturasCaja as $aperturaCajaReporte): ?>
                    <option value="<?php echo (int)$aperturaCajaReporte["id"]; ?>" <?php echo $cajaApertura === (int)$aperturaCajaReporte["id"] ? "selected" : ""; ?>>
                      #<?php echo (int)$aperturaCajaReporte["id"]; ?> · <?php echo tmE($aperturaCajaReporte["cajero"] ?? "Sin cajero"); ?> · <?php echo tmE(date("d/m/Y H:i", strtotime($aperturaCajaReporte["fecha_apertura"]))); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="tm-report-field">
                <label>Tipo</label>
                <select class="form-control" name="cajaTipo">
                  <option value="">Ingresos y egresos</option>
                  <option value="ingreso" <?php echo $cajaTipo === "ingreso" ? "selected" : ""; ?>>Solo ingresos</option>
                  <option value="egreso" <?php echo $cajaTipo === "egreso" ? "selected" : ""; ?>>Solo egresos</option>
                </select>
              </div>
              <div class="tm-report-field">
                <label>Origen</label>
                <select class="form-control" name="cajaOrigen">
                  <option value="">Todos los origenes</option>
                  <?php foreach($origenesCaja as $origenCajaItem): $valorOrigenCaja = (string)$origenCajaItem["origen"]; ?>
                    <option value="<?php echo tmE($valorOrigenCaja); ?>" <?php echo $cajaOrigen === $valorOrigenCaja ? "selected" : ""; ?>><?php echo tmE(tmReporteOrigenCaja($valorOrigenCaja)); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="tm-report-filter-actions">
                <button type="submit" class="tm-report-btn primary"><i class="fa fa-filter"></i> Filtrar</button>
                <a class="tm-report-btn" href="reportes?reporteTab=caja&fechaInicial=<?php echo urlencode($fechaInicialReporte); ?>&fechaFinal=<?php echo urlencode($fechaFinalReporte); ?>"><i class="fa fa-eraser"></i> Limpiar</a>
              </div>
            </form>

            <div class="tm-caja-summary">
              <div class="tm-report-kpi"><i class="fa fa-arrow-down"></i><span>Ingresos</span><strong><?php echo tmMoney($cajaIngresosTotal); ?></strong><small>Total registrado</small></div>
              <div class="tm-report-kpi"><i class="fa fa-arrow-up"></i><span>Egresos</span><strong><?php echo tmMoney($cajaEgresosTotal); ?></strong><small>Total desembolsado</small></div>
              <div class="tm-report-kpi"><i class="fa fa-balance-scale"></i><span>Balance</span><strong><?php echo tmMoney($cajaBalanceTotal); ?></strong><small>Ingresos menos egresos</small></div>
              <div class="tm-report-kpi"><i class="fa fa-money"></i><span>Flujo en efectivo</span><strong><?php echo tmMoney($cajaBalanceEfectivo); ?></strong><small>Solo movimientos que afectan efectivo</small></div>
            </div>

            <div class="tm-report-section-head">
              <div>
                <h3>Aperturas y arqueos de caja</h3>
                <p><?php echo count($turnosCajaReporte); ?> turno(s) iniciados dentro del periodo seleccionado.</p>
              </div>
            </div>
            <div class="table-responsive" style="margin-bottom:14px">
              <table class="table table-hover">
                <thead>
                  <tr><th>Turno</th><th>Cajero</th><th>Apertura</th><th>Cierre</th><th>Estado</th><th class="text-right">Inicial</th><th class="text-right">Esperado</th><th class="text-right">Contado</th><th class="text-right">Diferencia</th></tr>
                </thead>
                <tbody>
                  <?php if(count($turnosCajaReporte) === 0): ?>
                    <tr><td colspan="9" class="text-center text-muted">No existen aperturas de caja en este periodo.</td></tr>
                  <?php endif; ?>
                  <?php foreach($turnosCajaReporte as $turnoCajaReporte): ?>
                    <tr>
                      <td><strong>#<?php echo (int)$turnoCajaReporte["id"]; ?></strong></td>
                      <td><?php echo tmE($turnoCajaReporte["cajero"] ?? "-"); ?></td>
                      <td><?php echo tmFechaReporte($turnoCajaReporte["fecha_apertura"] ?? ""); ?></td>
                      <td><?php echo tmFechaReporte($turnoCajaReporte["fecha_cierre"] ?? ""); ?></td>
                      <td><span class="tm-report-badge <?php echo ($turnoCajaReporte["estado"] ?? "") === "abierta" ? "warning" : "success"; ?>"><?php echo tmE($turnoCajaReporte["estado"] ?? "-"); ?></span></td>
                      <td class="text-right"><?php echo tmMoney($turnoCajaReporte["monto_inicial"] ?? 0); ?></td>
                      <td class="text-right"><?php echo tmMoney($turnoCajaReporte["monto_esperado_cierre"] ?? 0); ?></td>
                      <td class="text-right"><?php echo tmMoney($turnoCajaReporte["monto_contado_cierre"] ?? 0); ?></td>
                      <td class="text-right"><strong><?php echo tmMoney($turnoCajaReporte["diferencia"] ?? 0); ?></strong></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <div class="tm-report-section-head">
              <div>
                <h3>Detalle de movimientos</h3>
                <p>Cobros, desembolsos, devoluciones y ajustes registrados en cada turno.</p>
              </div>
            </div>
            <div class="tm-report-card-grid wide">
              <?php if(count($movimientosCajaReporte) === 0): ?>
                <div class="tm-report-empty">No existen movimientos de caja con los filtros seleccionados.</div>
              <?php endif; ?>
              <?php foreach($movimientosCajaReporte as $movimientoCaja): ?>
                <article class="tm-report-card">
                  <div class="tm-report-card-head">
                    <div>
                      <span class="tm-report-code"><i class="fa fa-unlock-alt"></i> Apertura #<?php echo (int)$movimientoCaja["id_apertura"]; ?></span>
                      <h4><?php echo tmE(tmReporteOrigenCaja($movimientoCaja["origen"] ?? "")); ?></h4>
                    </div>
                    <div class="tm-report-amount tm-caja-amount <?php echo tmE($movimientoCaja["tipo"] ?? ""); ?>">
                      <?php echo ($movimientoCaja["tipo"] ?? "") === "egreso" ? "- " : "+ "; ?><?php echo tmMoney($movimientoCaja["monto"] ?? 0); ?>
                    </div>
                  </div>
                  <div class="tm-report-card-body">
                    <div class="tm-report-badges">
                      <span class="tm-caja-type <?php echo tmE($movimientoCaja["tipo"] ?? ""); ?>"><i class="fa <?php echo ($movimientoCaja["tipo"] ?? "") === "egreso" ? "fa-minus-circle" : "fa-plus-circle"; ?>"></i><?php echo tmE($movimientoCaja["tipo"] ?? "-"); ?></span>
                      <span class="tm-report-badge info"><?php echo tmE($movimientoCaja["metodo_pago"] ?? "-"); ?></span>
                      <span class="tm-report-badge <?php echo (int)($movimientoCaja["afecta_efectivo"] ?? 0) === 1 ? "success" : "info"; ?>"><?php echo (int)($movimientoCaja["afecta_efectivo"] ?? 0) === 1 ? "Afecta efectivo" : "No afecta efectivo"; ?></span>
                    </div>
                    <div class="tm-report-info-grid">
                      <div class="tm-report-info"><b>Cajero del turno</b><?php echo tmE($movimientoCaja["cajero"] ?? "-"); ?></div>
                      <div class="tm-report-info"><b>Registrado por</b><?php echo tmE($movimientoCaja["usuario_registro"] ?? "-"); ?></div>
                      <div class="tm-report-info"><b>Fecha</b><?php echo tmFechaReporte($movimientoCaja["fecha"] ?? ""); ?></div>
                      <div class="tm-report-info"><b>Referencia</b><?php echo tmE($movimientoCaja["codigo_referencia"] ?: ($movimientoCaja["referencia_tipo"] ?? "-")); ?></div>
                    </div>
                    <div class="tm-report-info"><b>Detalle</b><?php echo tmE($movimientoCaja["descripcion"] ?? "Sin descripcion"); ?></div>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="tab-pane" id="repCotizaciones">
            <div class="tm-report-section-head">
              <div>
                <h3>Cotizaciones emitidas</h3>
                <p><?php echo count($cotizaciones); ?> cotizacion(es). Total cotizado: <?php echo tmMoney($cotizacionesTotal); ?>.</p>
              </div>
              <div class="tm-report-section-actions">
                <a class="tm-report-btn" target="_blank" href="<?php echo tmReportePdf("cotizaciones", $fechaInicialReporte, $fechaFinalReporte); ?>"><i class="fa fa-print"></i> Imprimir cotizaciones</a>
              </div>
            </div>

            <div class="tm-report-card-grid">
              <?php if(count($cotizaciones) == 0): ?>
                <div class="tm-report-empty">Sin cotizaciones en este rango.</div>
              <?php endif; ?>
              <?php foreach($cotizaciones as $cotizacion): ?>
                <article class="tm-report-card">
                  <div class="tm-report-card-head">
                    <div>
                      <span class="tm-report-code"><i class="fa fa-hashtag"></i> <?php echo tmE($cotizacion["codigo"] ?? "-"); ?></span>
                      <h4><?php echo tmE($cotizacion["cliente"] ?? "Sin cliente"); ?></h4>
                    </div>
                    <div class="tm-report-amount"><?php echo tmMoney($cotizacion["total"] ?? 0); ?></div>
                  </div>
                  <div class="tm-report-card-body">
                    <div class="tm-report-info-grid">
                      <div class="tm-report-info"><b>Vendedor</b><?php echo tmE($cotizacion["vendedor"] ?? "-"); ?></div>
                      <div class="tm-report-info"><b>Valido hasta</b><?php echo tmE($cotizacion["valido_hasta"] ?? "-"); ?></div>
                      <div class="tm-report-info"><b>Fecha</b><?php echo tmFechaReporte($cotizacion["fecha"] ?? ""); ?></div>
                      <div class="tm-report-info"><b>Total</b><?php echo tmMoney($cotizacion["total"] ?? 0); ?></div>
                    </div>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="tab-pane" id="repClientes">
            <div class="tm-report-section-head">
              <div>
                <h3>Clientes registrados</h3>
                <p><?php echo count($clientesReporte); ?> cliente(s) registrados. Compras acumuladas en el rango: <?php echo tmMoney($clientesComprasTotal); ?>.</p>
              </div>
              <div class="tm-report-section-actions">
                <a class="tm-report-btn" target="_blank" href="<?php echo tmReportePdf("clientes", $fechaInicialReporte, $fechaFinalReporte); ?>"><i class="fa fa-print"></i> Imprimir clientes</a>
              </div>
            </div>

            <div class="tm-report-card-grid">
              <?php if(count($clientesReporte) == 0): ?>
                <div class="tm-report-empty">Sin clientes registrados en este rango.</div>
              <?php endif; ?>
              <?php foreach($clientesReporte as $cliente): ?>
                <article class="tm-report-card">
                  <div class="tm-report-card-head">
                    <div>
                      <span class="tm-report-code"><i class="fa fa-id-card"></i> <?php echo tmE($cliente["documento"] ?? "-"); ?></span>
                      <h4><?php echo tmE($cliente["nombre"] ?? "Sin nombre"); ?></h4>
                    </div>
                    <div class="tm-report-amount"><?php echo tmMoney($cliente["compras"] ?? 0); ?></div>
                  </div>
                  <div class="tm-report-card-body">
                    <div class="tm-report-info-grid">
                      <div class="tm-report-info"><b>Email</b><?php echo tmE($cliente["email"] ?? "-"); ?></div>
                      <div class="tm-report-info"><b>Telefono</b><?php echo tmE($cliente["telefono"] ?? "-"); ?></div>
                      <div class="tm-report-info"><b>Direccion</b><?php echo tmE($cliente["direccion"] ?? "-"); ?></div>
                      <div class="tm-report-info"><b>Ultima compra</b><?php echo tmFechaReporte($cliente["ultima_compra"] ?? ""); ?></div>
                      <div class="tm-report-info"><b>Registro</b><?php echo tmFechaReporte($cliente["fecha"] ?? ""); ?></div>
                      <div class="tm-report-info"><b>Total compras</b><?php echo tmMoney($cliente["compras"] ?? 0); ?></div>
                    </div>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="tab-pane" id="repSoftware">
            <div class="tm-report-section-head">
              <div>
                <h3>Desarrollo de software</h3>
                <p><?php echo count($proyectosSoftware); ?> proyecto(s). Adelantos: <?php echo tmMoney($softwareAdelantos); ?>. Saldos pendientes: <?php echo tmMoney($softwareSaldos); ?>.</p>
              </div>
              <div class="tm-report-section-actions">
                <a class="tm-report-btn" target="_blank" href="<?php echo tmReportePdf("software", $fechaInicialReporte, $fechaFinalReporte); ?>"><i class="fa fa-print"></i> Imprimir software</a>
              </div>
            </div>

            <div class="tm-report-card-grid wide">
              <?php if(count($proyectosSoftware) == 0): ?>
                <div class="tm-report-empty">Sin proyectos de software en este rango.</div>
              <?php endif; ?>
              <?php foreach($proyectosSoftware as $proyecto): ?>
                <article class="tm-report-card">
                  <div class="tm-report-card-head">
                    <div>
                      <span class="tm-report-code"><i class="fa fa-code"></i> <?php echo tmE($proyecto["codigo"] ?? $proyecto["codigo_servicio"] ?? "-"); ?></span>
                      <h4><?php echo tmE($proyecto["nombre_proyecto"] ?? "Proyecto de software"); ?></h4>
                    </div>
                    <div class="tm-report-amount"><?php echo (int)($proyecto["porcentaje_avance"] ?? 0); ?>%</div>
                  </div>
                  <div class="tm-report-card-body">
                    <div class="tm-report-badges">
                      <span class="tm-report-badge <?php echo tmEstadoClass($proyecto["estado"] ?? ""); ?>"><?php echo tmE(tmEstadoTextoVisible($proyecto["estado"] ?? "")); ?></span>
                    </div>
                    <div class="tm-report-info-grid">
                      <div class="tm-report-info"><b>Tipo</b><?php echo tmE($proyecto["tipo_software"] ?? "-"); ?></div>
                      <div class="tm-report-info"><b>Cliente</b><?php echo tmE($proyecto["cliente"] ?? "-"); ?></div>
                      <div class="tm-report-info"><b>Vendedor</b><?php echo tmE($proyecto["vendedor"] ?? "-"); ?></div>
                      <div class="tm-report-info"><b>Desarrollador</b><?php echo tmE($proyecto["desarrollador"] ?? "Sin asignar"); ?></div>
                      <div class="tm-report-info"><b>Total</b><?php echo tmMoney($proyecto["precio_total"] ?? 0); ?></div>
                      <div class="tm-report-info"><b>Adelanto</b><?php echo tmMoney($proyecto["pago_adelanto"] ?? 0); ?></div>
                      <div class="tm-report-info"><b>Saldo</b><?php echo tmMoney($proyecto["saldo_pendiente"] ?? 0); ?></div>
                      <div class="tm-report-info"><b>Entrega estimada</b><?php echo tmFechaReporte($proyecto["fecha_entrega_estimada"] ?? ""); ?></div>
                    </div>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="tab-pane" id="repStock">
            <div class="tm-report-section-head">
              <div>
                <h3>Stock critico</h3>
                <p><?php echo count($stockCritico); ?> producto(s) con stock bajo o en cero. Este reporte no depende del rango de fechas.</p>
              </div>
              <div class="tm-report-section-actions">
                <a class="tm-report-btn" target="_blank" href="<?php echo tmReportePdf("stock", $fechaInicialReporte, $fechaFinalReporte); ?>"><i class="fa fa-print"></i> Imprimir stock</a>
                <a class="tm-report-btn warning" href="crear-compra-almacen?stock=bajo"><i class="fa fa-cart-plus"></i> Crear compra</a>
              </div>
            </div>

            <div class="tm-report-card-grid">
              <?php if(count($stockCritico) == 0): ?>
                <div class="tm-report-empty">No hay productos con stock critico.</div>
              <?php endif; ?>
              <?php foreach($stockCritico as $producto): ?>
                <article class="tm-report-card">
                  <div class="tm-report-card-head">
                    <div>
                      <span class="tm-report-code"><i class="fa fa-barcode"></i> <?php echo tmE($producto["codigo"] ?? "-"); ?></span>
                      <h4><?php echo tmE($producto["descripcion"] ?? "Producto"); ?></h4>
                    </div>
                    <div class="tm-report-amount"><?php echo (int)($producto["stock"] ?? 0); ?> u.</div>
                  </div>
                  <div class="tm-report-card-body">
                    <div class="tm-report-badges">
                      <span class="tm-report-badge <?php echo ((int)($producto["stock"] ?? 0) <= 0) ? "danger" : "warning"; ?>"><?php echo ((int)($producto["stock"] ?? 0) <= 0) ? "Sin stock" : "Stock bajo"; ?></span>
                    </div>
                    <div class="tm-report-info-grid">
                      <div class="tm-report-info"><b>Categoria</b><?php echo tmE($producto["categoria"] ?? "-"); ?></div>
                      <div class="tm-report-info"><b>Compra</b><?php echo tmMoney($producto["precio_compra"] ?? 0); ?></div>
                      <div class="tm-report-info"><b>Venta</b><?php echo tmMoney($producto["precio_venta"] ?? 0); ?></div>
                      <div class="tm-report-info"><b>Stock</b><?php echo (int)($producto["stock"] ?? 0); ?></div>
                    </div>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="tab-pane" id="repRanking">
            <div class="tm-report-section-head">
              <div>
                <h3>Rankings del periodo</h3>
                <p>Productos y servicios con mayor movimiento cobrado dentro del rango seleccionado.</p>
              </div>
              <div class="tm-report-section-actions">
                <a class="tm-report-btn" target="_blank" href="<?php echo tmReportePdf("ranking", $fechaInicialReporte, $fechaFinalReporte); ?>"><i class="fa fa-print"></i> Imprimir ranking</a>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="tm-rank-list">
                  <?php if(count($productosMasVendidos) == 0): ?>
                    <div class="tm-report-empty">Sin productos vendidos cobrados.</div>
                  <?php endif; ?>
                  <?php foreach($productosMasVendidos as $indice => $producto): ?>
                    <div class="tm-rank-item">
                      <span class="tm-rank-number"><?php echo $indice + 1; ?></span>
                      <div>
                        <h4><?php echo tmE($producto["descripcion"] ?? "Producto"); ?></h4>
                        <p><?php echo (int)($producto["cantidad"] ?? 0); ?> unidad(es) vendidas</p>
                      </div>
                      <div class="tm-rank-total"><?php echo tmMoney($producto["total"] ?? 0); ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <div class="col-md-6">
                <div class="tm-rank-list">
                  <?php if(count($serviciosPorTipo) == 0): ?>
                    <div class="tm-report-empty">Sin servicios cobrados.</div>
                  <?php endif; ?>
                  <?php foreach($serviciosPorTipo as $indice => $tipo): ?>
                    <div class="tm-rank-item">
                      <span class="tm-rank-number"><?php echo $indice + 1; ?></span>
                      <div>
                        <h4><?php echo tmE($tipo["tipo_servicio"] ?? "Servicio"); ?></h4>
                        <p><?php echo (int)($tipo["cantidad"] ?? 0); ?> servicio(s)</p>
                      </div>
                      <div class="tm-rank-total"><?php echo tmMoney($tipo["total"] ?? 0); ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </section>
</div>
