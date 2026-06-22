<?php

$db = Conexion::conectar();

$resumen = array(
  "por_cobrar" => 0,
  "por_entregar" => 0,
  "ventas_hoy" => 0,
  "ingresos_hoy" => 0,
  "stock_bajo" => 0,
  "sin_precio" => 0
);

$stmt = $db->query("SELECT
  SUM(CASE WHEN estado_pago = 'pendiente' THEN 1 ELSE 0 END) AS por_cobrar,
  SUM(CASE WHEN estado_pago = 'aprobado' AND estado_despacho = 'pendiente' THEN 1 ELSE 0 END) AS por_entregar,
  SUM(CASE WHEN DATE(fecha) = CURDATE() THEN 1 ELSE 0 END) AS ventas_hoy,
  SUM(CASE WHEN estado_pago = 'aprobado' AND DATE(fecha_pago) = CURDATE() THEN total ELSE 0 END) AS ingresos_hoy
  FROM ventas");
$datosVentas = $stmt->fetch(PDO::FETCH_ASSOC);

if($datosVentas){
  $resumen["por_cobrar"] = (int)$datosVentas["por_cobrar"];
  $resumen["por_entregar"] = (int)$datosVentas["por_entregar"];
  $resumen["ventas_hoy"] = (int)$datosVentas["ventas_hoy"];
  $resumen["ingresos_hoy"] = (float)$datosVentas["ingresos_hoy"];
}

$resumen["stock_bajo"] = (int)$db->query("SELECT COUNT(*) FROM productos WHERE stock <= 3")->fetchColumn();
$resumen["sin_precio"] = (int)$db->query("SELECT COUNT(*) FROM productos WHERE requiere_precio = 1 OR precio_venta <= 0")->fetchColumn();

$ventasRecientes = $db->query("SELECT v.id, v.codigo, v.total, v.estado_pago, v.estado_despacho, v.fecha, c.nombre AS cliente, u.nombre AS vendedor
  FROM ventas v
  LEFT JOIN clientes c ON c.id = v.id_cliente
  LEFT JOIN usuarios u ON u.id = v.id_vendedor
  ORDER BY v.fecha DESC, v.id DESC
  LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);

$logsRecientes = $db->query("SELECT usuario, rol, accion, modulo, detalle, fecha
  FROM sistema_logs
  ORDER BY fecha DESC, id DESC
  LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="row">
  <div class="col-lg-2 col-xs-6">
    <div class="small-box bg-yellow">
      <div class="inner"><h3><?php echo $resumen["por_cobrar"]; ?></h3><p>Ventas por cobrar</p></div>
      <div class="icon"><i class="fa fa-money"></i></div>
      <a href="pagos-ventas" class="small-box-footer">Ir a caja <i class="fa fa-arrow-circle-right"></i></a>
    </div>
  </div>
  <div class="col-lg-2 col-xs-6">
    <div class="small-box bg-aqua">
      <div class="inner"><h3><?php echo $resumen["por_entregar"]; ?></h3><p>Por entregar</p></div>
      <div class="icon"><i class="fa fa-truck"></i></div>
      <a href="despacho" class="small-box-footer">Ir a despacho <i class="fa fa-arrow-circle-right"></i></a>
    </div>
  </div>
  <div class="col-lg-2 col-xs-6">
    <div class="small-box bg-green">
      <div class="inner"><h3><?php echo $resumen["ventas_hoy"]; ?></h3><p>Ventas de hoy</p></div>
      <div class="icon"><i class="fa fa-shopping-cart"></i></div>
      <a href="ventas" class="small-box-footer">Ver ventas <i class="fa fa-arrow-circle-right"></i></a>
    </div>
  </div>
  <div class="col-lg-2 col-xs-6">
    <div class="small-box bg-teal">
      <div class="inner"><h3 style="font-size:24px">Bs <?php echo number_format($resumen["ingresos_hoy"], 2); ?></h3><p>Cobrado hoy</p></div>
      <div class="icon"><i class="fa fa-check"></i></div>
      <a href="ventas" class="small-box-footer">Detalle <i class="fa fa-arrow-circle-right"></i></a>
    </div>
  </div>
  <div class="col-lg-2 col-xs-6">
    <div class="small-box bg-red">
      <div class="inner"><h3><?php echo $resumen["stock_bajo"]; ?></h3><p>Stock bajo</p></div>
      <div class="icon"><i class="fa fa-warning"></i></div>
      <a href="productos-almacen" class="small-box-footer">Revisar <i class="fa fa-arrow-circle-right"></i></a>
    </div>
  </div>
  <div class="col-lg-2 col-xs-6">
    <div class="small-box bg-purple">
      <div class="inner"><h3><?php echo $resumen["sin_precio"]; ?></h3><p>Sin precio final</p></div>
      <div class="icon"><i class="fa fa-tag"></i></div>
      <a href="productos-cajero" class="small-box-footer">Poner precio <i class="fa fa-arrow-circle-right"></i></a>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-7">
    <div class="box box-primary">
      <div class="box-header with-border"><h3 class="box-title">Ultimas ventas y estado</h3></div>
      <div class="box-body table-responsive no-padding">
        <table class="table table-hover">
          <tr><th>Codigo</th><th>Cliente</th><th>Vendedor</th><th>Estado</th><th>Total</th><th>Fecha</th></tr>
          <?php foreach($ventasRecientes as $venta): ?>
            <?php
              $estado = '<span class="label label-warning">Por cobrar</span>';
              if($venta["estado_pago"] == "aprobado" && $venta["estado_despacho"] == "pendiente"){
                $estado = '<span class="label label-info">Por entregar</span>';
              }else if($venta["estado_pago"] == "aprobado" && $venta["estado_despacho"] == "entregado"){
                $estado = '<span class="label label-success">Completado</span>';
              }
            ?>
            <tr>
              <td><?php echo $venta["codigo"]; ?></td>
              <td><?php echo htmlspecialchars($venta["cliente"] ?? "", ENT_QUOTES, "UTF-8"); ?></td>
              <td><?php echo htmlspecialchars($venta["vendedor"] ?? "", ENT_QUOTES, "UTF-8"); ?></td>
              <td><?php echo $estado; ?></td>
              <td>Bs <?php echo number_format($venta["total"], 2); ?></td>
              <td><?php echo $venta["fecha"]; ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="box box-info">
      <div class="box-header with-border"><h3 class="box-title">Ultimos movimientos del sistema</h3></div>
      <div class="box-body">
        <ul class="timeline">
          <?php foreach($logsRecientes as $log): ?>
            <li>
              <i class="fa fa-history bg-blue"></i>
              <div class="timeline-item">
                <span class="time"><i class="fa fa-clock-o"></i> <?php echo $log["fecha"]; ?></span>
                <h3 class="timeline-header"><?php echo htmlspecialchars(($log["usuario"] ?? "Sistema")." - ".$log["accion"]." / ".$log["modulo"], ENT_QUOTES, "UTF-8"); ?></h3>
                <div class="timeline-body"><?php echo htmlspecialchars($log["detalle"] ?? "", ENT_QUOTES, "UTF-8"); ?></div>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
</div>
