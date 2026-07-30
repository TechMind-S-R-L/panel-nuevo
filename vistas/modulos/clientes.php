<?php

if($_SESSION["perfil"] == "Especial"){
  echo '<script>window.location = "inicio";</script>';
  return;
}

$clientes = ControladorClientes::ctrMostrarClientes(null, null);
$totalClientes = is_array($clientes) ? count($clientes) : 0;
$clientesHoy = 0;
$totalComprasClientes = 0;
$ultimoRegistroCliente = "-";

if(is_array($clientes)){
  foreach($clientes as $clienteResumen){
    $totalComprasClientes += (float)($clienteResumen["compras"] ?? 0);
    if(!empty($clienteResumen["fecha"]) && substr($clienteResumen["fecha"], 0, 10) == date("Y-m-d")){
      $clientesHoy++;
    }
    if(!empty($clienteResumen["fecha"]) && ($ultimoRegistroCliente == "-" || strtotime($clienteResumen["fecha"]) > strtotime($ultimoRegistroCliente))){
      $ultimoRegistroCliente = $clienteResumen["fecha"];
    }
  }
}

function tmClienteTexto($valor, $fallback = "-"){
  $valor = trim((string)$valor);
  return htmlspecialchars($valor !== "" ? $valor : $fallback, ENT_QUOTES, "UTF-8");
}

function tmClienteFecha($valor){
  if(empty($valor) || $valor == "0000-00-00" || $valor == "0000-00-00 00:00:00"){
    return "-";
  }
  $timestamp = strtotime($valor);
  return $timestamp ? date("d/m/Y H:i", $timestamp) : $valor;
}

function tmClienteIniciales($nombre){
  $nombre = trim((string)$nombre);
  if($nombre === ""){
    return "CL";
  }
  $partes = preg_split('/\s+/', $nombre);
  $iniciales = "";
  foreach($partes as $parte){
    if($parte !== ""){
      $iniciales .= strtoupper(substr($parte, 0, 1));
    }
    if(strlen($iniciales) >= 2){
      break;
    }
  }
  return htmlspecialchars($iniciales ?: "CL", ENT_QUOTES, "UTF-8");
}

?>

<div class="content-wrapper clientes-page">
  <style>
    .clientes-page{background:#eef3f7 !important;}
    .clientes-hero{
      background:#163140;
      color:#fff;
      padding:18px 20px;
      border-radius:6px;
      margin-bottom:16px;
      display:flex;
      justify-content:space-between;
      gap:16px;
      align-items:center;
      flex-wrap:wrap;
    }
    .clientes-hero h2{margin:0 0 6px;font-size:24px;font-weight:700;}
    .clientes-hero p{margin:0;color:#c8d7df;}
    .cliente-kpi{
      background:#fff;
      border:1px solid #dbe5ec;
      border-radius:6px;
      padding:16px;
      min-height:108px;
      margin-bottom:16px;
      box-shadow:0 1px 2px rgba(0,0,0,.06);
    }
    .cliente-kpi i{font-size:24px;color:#3c8dbc;}
    .cliente-kpi span{display:block;color:#60717f;font-weight:700;margin-top:10px;}
    .cliente-kpi strong{display:block;font-size:22px;color:#1f2d3d;line-height:1.1;margin-top:4px;}
    .cliente-panel{
      background:#fff;
      border:1px solid #dbe5ec;
      border-radius:6px;
      box-shadow:0 1px 2px rgba(0,0,0,.06);
      overflow:hidden;
    }
    .cliente-panel-header{
      padding:14px 16px;
      background:#fbfdff;
      border-bottom:1px solid #e5edf2;
      display:flex;
      justify-content:space-between;
      gap:10px;
      align-items:center;
      flex-wrap:wrap;
    }
    .cliente-panel-header h3{margin:0;font-size:18px;font-weight:700;color:#1f2d3d;}
    .cliente-card-toolbar{
      padding:14px 16px;
      border-bottom:1px solid #e5edf2;
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
      flex-wrap:wrap;
      background:#fff;
    }
    .cliente-card-search{
      position:relative;
      flex:1 1 320px;
      max-width:520px;
    }
    .cliente-card-search i{
      position:absolute;
      left:13px;
      top:50%;
      transform:translateY(-50%);
      color:#6f8190;
    }
    .cliente-card-search input{
      height:42px;
      padding-left:38px;
      border:1px solid #d7e1e8;
      border-radius:6px;
      box-shadow:none;
    }
    .cliente-card-info{
      color:#60717f;
      font-size:12px;
      font-weight:800;
      text-transform:uppercase;
      letter-spacing:.03em;
    }
    .cliente-card-grid{
      padding:12px;
      display:grid;
      grid-template-columns:repeat(auto-fill, minmax(230px, 1fr));
      gap:10px;
    }
    .cliente-card{
      border:1px solid #dbe5ec;
      border-radius:10px;
      background:rgba(255,255,255,.92);
      box-shadow:0 12px 28px rgba(22,49,64,.07);
      padding:11px;
      cursor:pointer;
      transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
      min-height:205px;
      display:flex;
      flex-direction:column;
      gap:9px;
    }
    .cliente-card:hover{
      border-color:#3c8dbc;
      box-shadow:0 16px 34px rgba(22,49,64,.14);
      transform:translateY(-2px);
    }
    .cliente-card-top{
      display:flex;
      gap:9px;
      align-items:flex-start;
    }
    .cliente-avatar{
      width:40px;
      height:40px;
      border-radius:12px;
      background:linear-gradient(135deg,#185a9d,#36aee2);
      color:#fff;
      display:flex;
      align-items:center;
      justify-content:center;
      font-weight:900;
      letter-spacing:.04em;
      flex:0 0 40px;
      box-shadow:0 8px 18px rgba(24,90,157,.24);
    }
    .cliente-card-name{
      min-width:0;
      flex:1;
    }
    .cliente-card-name h4{
      margin:0 0 3px;
      font-size:14px;
      font-weight:900;
      color:#1f2d3d;
      line-height:1.25;
      overflow-wrap:anywhere;
    }
    .cliente-card-name small{
      color:#60717f;
      font-weight:700;
      font-size:11px;
    }
    .cliente-card-data{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:6px;
    }
    .cliente-card-item{
      border:1px solid #edf2f6;
      background:#f8fbfd;
      border-radius:8px;
      padding:7px;
      min-height:50px;
    }
    .cliente-card-item span{
      display:block;
      color:#7b8b96;
      font-size:10px;
      font-weight:800;
      text-transform:uppercase;
      margin-bottom:4px;
    }
    .cliente-card-item strong,
    .cliente-card-item p{
      margin:0;
      color:#263845;
      font-size:11px;
      font-weight:800;
      line-height:1.25;
      overflow-wrap:anywhere;
    }
    .cliente-card-full{grid-column:1 / -1;}
    .cliente-card-footer{
      margin-top:auto;
      padding-top:8px;
      border-top:1px solid #edf2f6;
      display:grid;
      grid-template-columns:1fr;
      gap:7px;
    }
    .cliente-card-footer .label{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      max-width:100%;
      min-height:24px;
      white-space:normal;
      line-height:1.1;
      padding:5px 8px;
      border-radius:999px;
    }
    .cliente-actions{
      width:100%;
      display:grid;
      grid-template-columns:repeat(4, minmax(0, 1fr));
      gap:5px;
      align-items:center;
    }
    .cliente-actions .btn{
      width:100%;
      height:30px;
      padding:5px 0;
      text-align:center;
      border-radius:7px;
      overflow:hidden;
    }
    .cliente-empty{
      grid-column:1 / -1;
      min-height:140px;
      border:1px dashed #c6d4df;
      border-radius:8px;
      display:flex;
      align-items:center;
      justify-content:center;
      color:#7d8c96;
      font-weight:800;
      background:#f8fbfd;
      text-align:center;
      padding:18px;
    }
    .cliente-modal .modal-dialog{max-width:820px;width:92%;}
    .cliente-modal .modal-content{
      border:0;
      border-radius:16px;
      overflow:hidden;
      background:#f4f8fb;
      box-shadow:0 28px 80px rgba(10,30,45,.32);
    }
    .cliente-modal .modal-header{
      position:relative;
      background:linear-gradient(135deg,#102b3b 0%,#176b9b 62%,#36aee2 100%);
      color:#fff;
      border:0;
      padding:16px 20px;
      overflow:hidden;
    }
    .cliente-modal .modal-header:after{
      content:"";
      position:absolute;
      right:-42px;
      top:-58px;
      width:160px;
      height:160px;
      border-radius:50%;
      background:rgba(255,255,255,.11);
    }
    .cliente-modal .modal-header .modal-title{
      position:relative;
      z-index:2;
      font-weight:900;
      letter-spacing:.01em;
    }
    .cliente-modal .modal-header .close{
      position:relative;
      z-index:3;
      color:#fff;
      opacity:.9;
      text-shadow:none;
      width:32px;
      height:32px;
      border-radius:50%;
      background:rgba(255,255,255,.18);
      line-height:30px;
      font-size:24px;
    }
    .cliente-modal .modal-body{padding:14px;}
    .cliente-modal .modal-footer{
      background:#fff;
      border-top:1px solid #dce8f1;
    }
    .cliente-modal .form-control{border-radius:8px;}
    .cliente-modal label{color:#263845;}
    .cliente-help{color:#7b8b96;font-size:12px;margin-top:4px;}
    .cliente-detail-head{
      position:relative;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      margin-bottom:10px;
      padding:12px;
      border-radius:12px;
      background:#fff;
      border:1px solid #dce8f1;
      box-shadow:0 10px 24px rgba(22,49,64,.06);
    }
    .cliente-detail-identity{
      display:flex;
      align-items:center;
      gap:10px;
      min-width:0;
    }
    .cliente-detail-head .cliente-avatar{
      width:50px;
      height:50px;
      flex:0 0 50px;
      border-radius:14px;
      box-shadow:0 10px 20px rgba(23,107,155,.18);
    }
    .cliente-detail-head h3{
      margin:0 0 4px;
      font-size:19px;
      font-weight:900;
      color:#1f2d3d;
      line-height:1.15;
      overflow-wrap:anywhere;
    }
    .cliente-detail-head p{margin:0;color:#60717f;font-weight:800;}
    .cliente-status-pill{
      display:inline-flex;
      align-items:center;
      gap:7px;
      min-height:28px;
      padding:5px 9px;
      border-radius:999px;
      font-size:11px;
      font-weight:900;
      color:#fff !important;
      white-space:nowrap;
    }
    .cliente-status-pill i,
    .cliente-detail-box p .cliente-status-pill{
      color:#fff !important;
    }
    .cliente-status-pill.success{background:#00a65a;color:#fff !important;}
    .cliente-status-pill.warning{background:#f39c12;color:#fff !important;}
    .cliente-detail-summary{
      display:grid;
      grid-template-columns:repeat(3, minmax(0, 1fr));
      gap:8px;
      margin-bottom:10px;
    }
    .cliente-summary-card{
      background:#fff;
      border:1px solid #dce8f1;
      border-radius:10px;
      padding:10px;
      min-height:66px;
      box-shadow:0 8px 18px rgba(22,49,64,.05);
    }
    .cliente-summary-card strong{
      display:block;
      color:#176b9b;
      font-size:17px;
      font-weight:900;
      line-height:1.15;
      overflow-wrap:anywhere;
    }
    .cliente-detail-section{
      background:#fff;
      border:1px solid #dce8f1;
      border-radius:10px;
      padding:11px;
      margin-top:10px;
      box-shadow:0 8px 18px rgba(22,49,64,.04);
    }
    .cliente-detail-grid{
      display:grid;
      grid-template-columns:repeat(2, minmax(0, 1fr));
      gap:8px;
    }
    .cliente-detail-box{
      border:1px solid #e2ebf2;
      border-radius:9px;
      background:#f8fbfd;
      padding:9px 10px;
      min-height:62px;
    }
    .cliente-detail-box.full{grid-column:1 / -1;}
    .cliente-summary-card span,
    .cliente-detail-box span{
      display:block;
      color:#7b8b96;
      font-size:11px;
      font-weight:900;
      text-transform:uppercase;
      margin-bottom:4px;
    }
    .cliente-summary-card p,
    .cliente-detail-box strong,
    .cliente-detail-box p{
      color:#263845;
      font-size:13px;
      font-weight:800;
      margin:0;
      overflow-wrap:anywhere;
    }
    .cliente-detail-actions{
      margin-top:10px;
      padding:10px;
      border:1px solid #dce8f1;
      border-radius:12px;
      background:#fff;
      display:flex;
      gap:8px;
      flex-wrap:wrap;
      justify-content:flex-end;
    }
    .cliente-detail-actions:before{
      content:"Acciones disponibles";
      margin-right:auto;
      align-self:center;
      color:#60717f;
      font-size:12px;
      font-weight:900;
      text-transform:uppercase;
    }
    .cliente-detail-actions .btn{
      border-radius:8px;
      font-weight:900;
      padding:7px 11px;
    }
    @media(max-width:767px){
      .clientes-hero .btn{width:100%;}
      .cliente-actions{grid-template-columns:repeat(4, minmax(0, 1fr));}
      .cliente-actions .btn{width:100%;}
      .cliente-card-grid{grid-template-columns:1fr;padding:12px;}
      .cliente-detail-head{align-items:flex-start;flex-direction:column;}
      .cliente-detail-summary{grid-template-columns:1fr;}
      .cliente-detail-grid{grid-template-columns:1fr;}
    }
  </style>

  <section class="content-header">
    <h1>Gestion de Clientes</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Clientes</li>
    </ol>
  </section>

  <section class="content">
    <div class="clientes-hero">
      <div>
        <h2>Clientes</h2>
        <p>Administre datos de contacto, documentos y movimiento comercial de cada cliente.</p>
      </div>
      <button class="btn btn-default" data-toggle="modal" data-target="#modalAgregarCliente">
        <i class="fa fa-user-plus"></i> Agregar cliente
      </button>
    </div>

    <div class="row">
      <div class="col-md-3 col-sm-6"><div class="cliente-kpi"><i class="fa fa-users"></i><span>Total clientes</span><strong><?php echo (int)$totalClientes; ?></strong><small>Registrados en el sistema</small></div></div>
      <div class="col-md-3 col-sm-6"><div class="cliente-kpi"><i class="fa fa-calendar-plus-o"></i><span>Nuevos hoy</span><strong><?php echo (int)$clientesHoy; ?></strong><small>Altas del dia</small></div></div>
      <div class="col-md-3 col-sm-6"><div class="cliente-kpi"><i class="fa fa-shopping-cart"></i><span>Total compras</span><strong>Bs <?php echo number_format($totalComprasClientes, 2); ?></strong><small>Acumulado por clientes</small></div></div>
      <div class="col-md-3 col-sm-6"><div class="cliente-kpi"><i class="fa fa-clock-o"></i><span>Ultimo registro</span><strong style="font-size:16px;"><?php echo tmClienteTexto(tmClienteFecha($ultimoRegistroCliente)); ?></strong><small>Cliente agregado recientemente</small></div></div>
    </div>

    <div class="cliente-panel">
      <div class="cliente-panel-header">
        <h3><i class="fa fa-address-book"></i> Administrar clientes</h3>
        <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarCliente"><i class="fa fa-plus"></i> Nuevo cliente</button>
      </div>
      <div class="cliente-card-toolbar">
        <div class="cliente-card-search">
          <i class="fa fa-search"></i>
          <input type="text" class="form-control" id="buscarClienteCards" placeholder="Buscar por nombre, documento, telefono o correo">
        </div>
        <span class="cliente-card-info"><span id="clientesCardsCount"><?php echo (int)$totalClientes; ?></span> cliente(s)</span>
      </div>

      <div class="cliente-card-grid" id="clientesCardGrid">
        <?php if(!is_array($clientes) || count($clientes) == 0): ?>
          <div class="cliente-empty">No hay clientes registrados.</div>
        <?php endif; ?>
        <?php if(is_array($clientes)): ?>
          <?php foreach($clientes as $key => $value): ?>
            <?php
              $clienteSearch = strtolower(trim(($value["nombre"] ?? "")." ".($value["documento"] ?? "")." ".($value["email"] ?? "")." ".($value["telefono"] ?? "")." ".($value["direccion"] ?? "")));
              $clienteNombre = tmClienteTexto($value["nombre"] ?? "");
            ?>
            <article class="cliente-card clienteCardDetalle" idCliente="<?php echo (int)$value["id"]; ?>" data-search="<?php echo htmlspecialchars($clienteSearch, ENT_QUOTES, "UTF-8"); ?>">
              <div class="cliente-card-top">
                <div class="cliente-avatar"><?php echo tmClienteIniciales($value["nombre"] ?? ""); ?></div>
                <div class="cliente-card-name">
                  <h4><?php echo $clienteNombre; ?></h4>
                  <small><i class="fa fa-clock-o"></i> Registro: <?php echo tmClienteTexto(tmClienteFecha($value["fecha"] ?? "")); ?></small>
                </div>
              </div>

              <div class="cliente-card-data">
                <div class="cliente-card-item">
                  <span>Documento</span>
                  <strong><?php echo tmClienteTexto($value["documento"] ?? ""); ?></strong>
                </div>
                <div class="cliente-card-item">
                  <span>Compras</span>
                  <strong>Bs <?php echo number_format((float)($value["compras"] ?? 0), 2); ?></strong>
                </div>
                <div class="cliente-card-item cliente-card-full">
                  <span>Contacto</span>
                  <p><i class="fa fa-envelope-o"></i> <?php echo tmClienteTexto($value["email"] ?? ""); ?></p>
                  <p><i class="fa fa-phone"></i> <?php echo tmClienteTexto($value["telefono"] ?? ""); ?></p>
                </div>
                <div class="cliente-card-item cliente-card-full">
                  <span>Direccion</span>
                  <p><?php echo tmClienteTexto($value["direccion"] ?? ""); ?></p>
                </div>
              </div>

              <div class="cliente-card-footer">
                <div>
                  <?php if(!empty($value["password_web"])): ?>
                    <span class="label label-success">Web configurada</span>
                  <?php else: ?>
                    <span class="label label-warning">Sin clave web</span>
                  <?php endif; ?>
                </div>
                <div class="cliente-actions">
                  <button type="button" class="btn btn-info btn-sm btnVerCliente" title="Ver cliente" idCliente="<?php echo (int)$value["id"]; ?>"><i class="fa fa-eye"></i></button>
                  <button type="button" class="btn btn-warning btn-sm btnEditarCliente" title="Editar cliente" idCliente="<?php echo (int)$value["id"]; ?>"><i class="fa fa-pencil"></i></button>
                  <button type="button" class="btn btn-primary btn-sm btnPasswordWebCliente" title="Generar o cambiar clave web" idCliente="<?php echo (int)$value["id"]; ?>" cliente="<?php echo $clienteNombre; ?>"><i class="fa fa-key"></i></button>
                  <?php if($_SESSION["perfil"] == "Administrador"): ?>
                    <button type="button" class="btn btn-danger btn-sm btnEliminarCliente" title="Eliminar cliente" idCliente="<?php echo (int)$value["id"]; ?>"><i class="fa fa-trash"></i></button>
                  <?php endif; ?>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>

<script>
  window.tmClientePuedeEliminar = <?php echo $_SESSION["perfil"] == "Administrador" ? "true" : "false"; ?>;
</script>

<div id="modalPasswordWebCliente" class="modal fade cliente-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form role="form" method="post" novalidate>
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><i class="fa fa-key"></i> Clave web del cliente</h4>
        </div>
        <div class="modal-body">
          <input type="hidden" name="idClientePasswordWeb" id="idClientePasswordWeb">
          <div class="alert alert-info">
            <b id="nombreClientePasswordWeb">Cliente</b><br>
            Use esta opcion si el cliente olvido su contrasena web o necesita habilitar su acceso.
          </div>
          <div class="form-group">
            <label>Operacion</label>
            <select class="form-control" name="modoPasswordWeb" id="modoPasswordWeb">
              <option value="generar">Generar contrasena aleatoria y enviar al correo</option>
              <option value="manual">Escribir contrasena manualmente</option>
            </select>
          </div>
          <div id="camposPasswordWebManual" style="display:none;">
            <div class="form-group">
              <label>Nueva contrasena</label>
              <input type="password" class="form-control" name="passwordWebManual" id="passwordWebManual" minlength="6" maxlength="20" placeholder="6 a 20 letras o numeros">
            </div>
            <div class="form-group">
              <label>Confirmar contrasena</label>
              <input type="password" class="form-control" name="passwordWebConfirmar" id="passwordWebConfirmar" minlength="6" maxlength="20">
            </div>
          </div>
          <p class="cliente-help">Si el servidor no puede enviar correo, la clave quedara guardada en el log de correos pendientes y tambien se mostrara al confirmar.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar clave web</button>
        </div>
      </form>
      <?php ControladorClientes::ctrActualizarPasswordWebCliente(); ?>
    </div>
  </div>
</div>

<div id="modalAgregarCliente" class="modal fade cliente-modal" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form role="form" method="post" novalidate>
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><i class="fa fa-user-plus"></i> Agregar cliente</h4>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Nombre completo</label>
                <input type="text" class="form-control" name="nuevoCliente" placeholder="Ej. Juan Perez" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Cedula de identidad</label>
                <input type="text" class="form-control" name="nuevoDocumentoId" placeholder="Documento/NIT del cliente" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Email</label>
                <input type="text" class="form-control" name="nuevoEmail" placeholder="correo@dominio.com">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Telefono</label>
                <input type="text" class="form-control" name="nuevoTelefono" placeholder="Telefono o celular">
              </div>
            </div>
            <div class="col-md-8">
              <div class="form-group">
                <label>Direccion</label>
                <input type="text" class="form-control" name="nuevaDireccion" placeholder="Direccion o referencia">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Fecha nacimiento</label>
                <input type="text" class="form-control" name="nuevaFechaNacimiento" placeholder="yyyy-mm-dd">
                <div class="cliente-help">Opcional.</div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar cliente</button>
        </div>
      </form>
      <?php
        $crearCliente = new ControladorClientes();
        $crearCliente -> ctrCrearCliente();
      ?>
    </div>
  </div>
</div>

<div id="modalEditarCliente" class="modal fade cliente-modal" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form role="form" method="post">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><i class="fa fa-pencil"></i> Editar cliente</h4>
        </div>
        <div class="modal-body">
          <input type="hidden" id="idCliente" name="idCliente">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Nombre completo</label>
                <input type="text" class="form-control" name="editarCliente" id="editarCliente" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Cedula de identidad</label>
                <input type="number" min="0" class="form-control" name="editarDocumentoId" id="editarDocumentoId" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" name="editarEmail" id="editarEmail">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Telefono</label>
                <input type="text" class="form-control" name="editarTelefono" id="editarTelefono">
              </div>
            </div>
            <div class="col-md-8">
              <div class="form-group">
                <label>Direccion</label>
                <input type="text" class="form-control" name="editarDireccion" id="editarDireccion">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Fecha nacimiento</label>
                <input type="text" class="form-control" name="editarFechaNacimiento" id="editarFechaNacimiento" data-inputmask="'alias': 'yyyy/mm/dd'" data-mask>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar cambios</button>
        </div>
      </form>
      <?php
        $editarCliente = new ControladorClientes();
        $editarCliente -> ctrEditarCliente();
      ?>
    </div>
  </div>
</div>

<div id="modalVerCliente" class="modal fade cliente-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title"><i class="fa fa-eye"></i> Detalle del cliente</h4>
      </div>
      <div class="modal-body" id="detalleCliente">
        <p class="text-muted">Cargando informacion...</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<?php
  $eliminarCliente = new ControladorClientes();
  $eliminarCliente -> ctrEliminarCliente();
?>
