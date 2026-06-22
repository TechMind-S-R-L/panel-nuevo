<?php
if ($_SESSION["perfil"] != "Administrador" && !in_array($_SESSION["rol"], ["almacen", "cajero"])) {
  echo '<script>window.location = "inicio";</script>';
  return;
}

$proveedores = ControladorProveedor::ctrMostrarProveedor(null, null);

function tmProveedorEsc($valor){
  return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function tmProveedorIniciales($nombre){
  $partes = preg_split('/\s+/', trim((string)$nombre));
  $iniciales = "";
  foreach($partes as $parte){
    if($parte !== ""){
      $iniciales .= strtoupper(substr($parte, 0, 1));
    }
    if(strlen($iniciales) >= 2){
      break;
    }
  }
  return $iniciales ?: "PR";
}

function tmProveedorEstado($estado, $idProveedor){
  if((int)$estado !== 0){
    return '<button type="button" class="btn btn-success btn-xs btnActivar" title="Desactivar proveedor" idProveedor="'.(int)$idProveedor.'" estadoProveedor="0">Activado</button>';
  }
  return '<button type="button" class="btn btn-danger btn-xs btnActivar" title="Activar proveedor" idProveedor="'.(int)$idProveedor.'" estadoProveedor="1">Desactivado</button>';
}
?>

<div class="content-wrapper">
  <style>
    .proveedor-page .proveedor-hero{
      border-radius:14px;
      padding:18px 20px;
      margin-bottom:14px;
      color:#fff;
      background:linear-gradient(135deg,#102b3b 0%,#176b9b 62%,#36aee2 100%);
      box-shadow:0 16px 38px rgba(15,23,42,.12);
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      overflow:hidden;
      position:relative;
    }
    .proveedor-page .proveedor-hero:after{
      content:"";
      position:absolute;
      right:-48px;
      top:-72px;
      width:210px;
      height:210px;
      border-radius:50%;
      background:rgba(255,255,255,.12);
    }
    .proveedor-hero h1{margin:0;font-size:24px;font-weight:950;position:relative;z-index:1;}
    .proveedor-hero p{margin:4px 0 0;color:#eaf7ff;font-weight:700;position:relative;z-index:1;}
    .proveedor-hero .btn{position:relative;z-index:1;border-radius:9px;font-weight:900;padding:9px 14px;}
    .proveedor-panel{
      border:1px solid rgba(184,205,232,.68);
      border-radius:14px;
      background:rgba(255,255,255,.70);
      box-shadow:0 16px 38px rgba(15,23,42,.07);
      padding:14px;
    }
    .proveedor-toolbar{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      margin-bottom:12px;
      flex-wrap:wrap;
    }
    .proveedor-search{
      position:relative;
      flex:1 1 280px;
      max-width:520px;
    }
    .proveedor-search i{
      position:absolute;
      left:12px;
      top:50%;
      transform:translateY(-50%);
      color:#7b8b96;
    }
    .proveedor-search input{
      width:100%;
      height:38px;
      border:1px solid #dce8f1;
      border-radius:10px;
      padding:8px 12px 8px 34px;
      font-weight:750;
      background:rgba(255,255,255,.86);
    }
    .proveedor-count{
      display:inline-flex;
      align-items:center;
      gap:6px;
      min-height:30px;
      padding:6px 10px;
      border-radius:999px;
      background:#eaf5fb;
      color:#176b9b;
      font-weight:900;
      font-size:12px;
    }
    .proveedor-grid{
      display:grid;
      grid-template-columns:repeat(auto-fill, minmax(230px, 1fr));
      gap:10px;
    }
    .proveedor-card{
      position:relative;
      min-height:205px;
      border:1px solid rgba(184,205,232,.72);
      border-radius:12px;
      background:rgba(255,255,255,.82);
      padding:11px;
      display:flex;
      flex-direction:column;
      gap:8px;
      cursor:pointer;
      overflow:hidden;
      box-shadow:0 12px 28px rgba(15,23,42,.06);
      transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .proveedor-card:before{
      content:"";
      position:absolute;
      inset:0 auto 0 0;
      width:5px;
      background:#00a65a;
    }
    .proveedor-card.estado-inactivo:before{background:#dd4b39;}
    .proveedor-card:hover{
      transform:translateY(-2px);
      border-color:#3c8dbc;
      box-shadow:0 18px 36px rgba(15,23,42,.12);
    }
    .proveedor-card-head{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:8px;
      padding-left:4px;
    }
    .proveedor-avatar{
      width:40px;
      height:40px;
      border-radius:12px;
      display:flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      font-weight:950;
      background:linear-gradient(135deg,#176b9b,#36aee2);
      box-shadow:0 10px 20px rgba(23,107,155,.18);
      flex:0 0 40px;
    }
    .proveedor-card-title{min-width:0;flex:1;}
    .proveedor-card-title h3{
      margin:0;
      color:#1f2d3d;
      font-size:14px;
      font-weight:950;
      line-height:1.22;
      overflow-wrap:anywhere;
    }
    .proveedor-card-title p{
      margin:3px 0 0;
      color:#60717f;
      font-size:11px;
      font-weight:800;
      overflow-wrap:anywhere;
    }
    .proveedor-card .btnActivar{
      border-radius:999px;
      font-size:10px;
      font-weight:900;
      padding:5px 7px;
      color:#fff !important;
      white-space:normal;
      line-height:1.12;
      max-width:92px;
    }
    .proveedor-card-info{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:6px;
    }
    .proveedor-card-item{
      border:1px solid #edf2f6;
      background:#f8fbfd;
      border-radius:8px;
      padding:7px;
      min-height:48px;
    }
    .proveedor-card-item.full{grid-column:1 / -1;}
    .proveedor-card-item span{
      display:block;
      margin-bottom:3px;
      color:#7b8b96;
      font-size:9.5px;
      font-weight:900;
      text-transform:uppercase;
    }
    .proveedor-card-item strong{
      display:block;
      color:#263845;
      font-size:11px;
      font-weight:850;
      line-height:1.22;
      overflow-wrap:anywhere;
    }
    .proveedor-card-footer{
      margin-top:auto;
      padding-top:7px;
      border-top:1px solid #edf2f6;
      display:grid;
      grid-template-columns:1fr auto;
      align-items:center;
      gap:8px;
      color:#176b9b;
      font-size:11px;
      font-weight:900;
    }
    .proveedor-actions{
      display:flex;
      align-items:center;
      justify-content:flex-end;
      gap:5px;
    }
    .proveedor-state-template{display:none;}
    .proveedor-actions .btn{
      width:31px;
      height:30px;
      padding:0;
      border-radius:8px;
      display:inline-flex;
      align-items:center;
      justify-content:center;
    }
    .proveedor-empty{
      grid-column:1 / -1;
      min-height:140px;
      border:1px dashed #c6d4df;
      border-radius:12px;
      display:flex;
      align-items:center;
      justify-content:center;
      color:#7d8c96;
      background:rgba(248,251,253,.72);
      font-weight:850;
      text-align:center;
      padding:18px;
    }
    .proveedor-modal .modal-dialog{max-width:760px;width:90%;}
    .proveedor-modal .modal-content{
      border:0;
      border-radius:15px;
      overflow:hidden;
      background:#f6f9fc;
      box-shadow:0 24px 64px rgba(10,30,45,.30);
    }
    .proveedor-modal .modal-header{
      position:relative;
      color:#fff;
      border:0;
      padding:15px 19px;
      background:linear-gradient(135deg,#102b3b 0%,#176b9b 62%,#36aee2 100%);
      overflow:hidden;
    }
    .proveedor-modal .modal-header:after{
      content:"";
      position:absolute;
      right:-38px;
      top:-56px;
      width:160px;
      height:160px;
      border-radius:50%;
      background:rgba(255,255,255,.13);
    }
    .proveedor-modal-title{
      position:relative;
      z-index:2;
      display:flex;
      align-items:center;
      gap:10px;
      max-width:88%;
    }
    .proveedor-modal-icon{
      width:42px;
      height:42px;
      flex:0 0 42px;
      border-radius:12px;
      display:flex;
      align-items:center;
      justify-content:center;
      background:rgba(255,255,255,.18);
      border:1px solid rgba(255,255,255,.20);
      font-size:19px;
    }
    .proveedor-modal-kicker{
      display:inline-block;
      margin-bottom:3px;
      font-size:10px;
      font-weight:900;
      text-transform:uppercase;
      letter-spacing:.04em;
      color:#eff9ff;
    }
    .proveedor-modal h4{margin:0;font-size:19px;font-weight:950;line-height:1.2;}
    .proveedor-modal .close{
      position:relative;
      z-index:3;
      color:#fff;
      opacity:.9;
      text-shadow:none;
      width:30px;
      height:30px;
      border-radius:50%;
      background:rgba(255,255,255,.18);
      line-height:28px;
      font-size:22px;
    }
    .proveedor-modal .modal-body{padding:13px;}
    .proveedor-detail-grid{
      display:grid;
      grid-template-columns:repeat(2, minmax(0, 1fr));
      gap:8px;
    }
    .proveedor-detail-item{
      border:1px solid #e2ebf2;
      border-radius:10px;
      background:#fff;
      padding:10px;
      min-height:64px;
    }
    .proveedor-detail-item.full{grid-column:1 / -1;}
    .proveedor-detail-item span{
      display:block;
      margin-bottom:4px;
      color:#7b8b96;
      font-size:10px;
      font-weight:900;
      text-transform:uppercase;
    }
    .proveedor-detail-item strong{
      color:#263845;
      font-size:13px;
      font-weight:850;
      line-height:1.25;
      overflow-wrap:anywhere;
    }
    .proveedor-modal-actions{
      margin-top:9px;
      padding:9px;
      border:1px solid #dce8f1;
      border-radius:10px;
      background:#fff;
      display:flex;
      flex-wrap:wrap;
      justify-content:flex-end;
      gap:7px;
    }
    .proveedor-modal-actions:before{
      content:"Acciones disponibles";
      margin-right:auto;
      align-self:center;
      color:#60717f;
      font-size:11px;
      font-weight:900;
      text-transform:uppercase;
    }
    .proveedor-modal-actions .btn{
      border-radius:8px;
      font-weight:900;
      padding:7px 10px;
    }
    .proveedor-form-modal .modal-dialog{max-width:680px;width:90%;}
    .proveedor-form-modal .modal-content{
      border:0;
      border-radius:15px;
      overflow:hidden;
      background:#f6f9fc;
      box-shadow:0 24px 64px rgba(10,30,45,.30);
    }
    .proveedor-form-modal .modal-header{
      background:linear-gradient(135deg,#102b3b,#176b9b 70%,#36aee2);
      color:white;
      border:0;
      padding:15px 19px;
    }
    .proveedor-form-modal .modal-body{padding:14px;}
    .proveedor-form-grid{
      display:grid;
      grid-template-columns:repeat(2, minmax(0, 1fr));
      gap:10px;
    }
    .proveedor-form-grid .form-group{margin-bottom:0;}
    .proveedor-form-grid .full{grid-column:1 / -1;}
    .proveedor-form-modal label{
      display:block;
      color:#263845;
      font-size:11px;
      font-weight:900;
      text-transform:uppercase;
      margin-bottom:5px;
    }
    .proveedor-form-modal .input-group-addon{
      border-radius:9px 0 0 9px;
      background:#eaf5fb;
      color:#176b9b;
      border-color:#dce8f1;
    }
    .proveedor-form-modal .form-control{
      height:38px;
      border-radius:0 9px 9px 0;
      border-color:#dce8f1;
      font-weight:750;
    }
    body.tm-dark-mode .proveedor-panel,
    body.tm-dark-mode .proveedor-card,
    body.tm-dark-mode .proveedor-detail-item,
    body.tm-dark-mode .proveedor-modal-actions,
    body.tm-dark-mode .proveedor-form-modal .modal-content{
      background:rgba(15,27,48,.72);
      border-color:rgba(147,197,253,.18);
      color:#edf5ff;
    }
    body.tm-dark-mode .proveedor-card h3,
    body.tm-dark-mode .proveedor-card-item strong,
    body.tm-dark-mode .proveedor-detail-item strong,
    body.tm-dark-mode .proveedor-form-modal label{color:#fff;}
    body.tm-dark-mode .proveedor-card-item{background:rgba(15,27,48,.58);border-color:rgba(147,197,253,.18);}
    body.tm-dark-mode .proveedor-modal .modal-body{background:#0d1729;}
    @media(max-width:767px){
      .proveedor-hero{align-items:flex-start;flex-direction:column;}
      .proveedor-grid{grid-template-columns:1fr;}
      .proveedor-detail-grid,
      .proveedor-form-grid{grid-template-columns:1fr;}
      .proveedor-form-grid .full{grid-column:auto;}
    }
  </style>

  <section class="content-header">
    <h1>Proveedores</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Proveedores</li>
    </ol>
  </section>

  <section class="content proveedor-page">
    <div class="proveedor-hero">
      <div>
        <h1>Gestion de proveedores</h1>
        <p>Administra contactos, direcciones, telefonos y estado de proveedores habilitados para compras.</p>
      </div>
      <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarProveedor">
        <i class="fa fa-plus"></i> Nuevo proveedor
      </button>
    </div>

    <div class="proveedor-panel">
      <div class="proveedor-toolbar">
        <div class="proveedor-search">
          <i class="fa fa-search"></i>
          <input type="search" id="buscarProveedorCard" placeholder="Buscar proveedor, contacto, telefono o direccion">
        </div>
        <span class="proveedor-count"><i class="fa fa-industry"></i> <?php echo count($proveedores); ?> proveedor(es)</span>
      </div>

      <div class="proveedor-grid" id="proveedorGrid">
        <?php if(count($proveedores) == 0): ?>
          <div class="proveedor-empty">No hay proveedores registrados.</div>
        <?php endif; ?>

        <?php foreach($proveedores as $value): 
          $estadoActivo = (int)$value["estado"] !== 0;
          $estadoTexto = $estadoActivo ? "Activado" : "Desactivado";
          $estadoClase = $estadoActivo ? "activo" : "inactivo";
        ?>
          <article class="proveedor-card estado-<?php echo $estadoClase; ?>"
            tabindex="0"
            data-id="<?php echo (int)$value["id"]; ?>"
            data-nombre="<?php echo tmProveedorEsc($value["nombre"]); ?>"
            data-contacto="<?php echo tmProveedorEsc($value["contacto"]); ?>"
            data-direccion="<?php echo tmProveedorEsc($value["direccion"]); ?>"
            data-telefono="<?php echo tmProveedorEsc($value["telefono"]); ?>"
            data-estado="<?php echo tmProveedorEsc($estadoTexto); ?>"
            data-search="<?php echo tmProveedorEsc(strtolower(($value["nombre"] ?? "")." ".($value["contacto"] ?? "")." ".($value["direccion"] ?? "")." ".($value["telefono"] ?? ""))); ?>">
            <div class="proveedor-card-head">
              <div class="proveedor-avatar"><?php echo tmProveedorEsc(tmProveedorIniciales($value["nombre"])); ?></div>
              <div class="proveedor-card-title">
                <h3><?php echo tmProveedorEsc($value["nombre"]); ?></h3>
                <p><i class="fa fa-user"></i> <?php echo tmProveedorEsc($value["contacto"] ?: "Sin contacto"); ?></p>
              </div>
              <?php echo tmProveedorEstado($value["estado"], $value["id"]); ?>
            </div>
            <div class="proveedor-card-info">
              <div class="proveedor-card-item"><span>Telefono</span><strong><?php echo tmProveedorEsc($value["telefono"] ?: "-"); ?></strong></div>
              <div class="proveedor-card-item"><span>Estado</span><strong><?php echo tmProveedorEsc($estadoTexto); ?></strong></div>
              <div class="proveedor-card-item full"><span>Direccion</span><strong><?php echo tmProveedorEsc($value["direccion"] ?: "-"); ?></strong></div>
            </div>
            <div class="proveedor-card-footer">
              <span><i class="fa fa-mouse-pointer"></i> Ver detalle</span>
              <div class="proveedor-actions">
                <button type="button" class="btn btn-warning btnEditarProveedor" title="Editar proveedor" idProveedor="<?php echo (int)$value["id"]; ?>" data-toggle="modal" data-target="#modalEditarProveedor"><i class="fa fa-pencil"></i></button>
                <button type="button" class="btn btn-danger btnEliminarProveedor" title="Eliminar proveedor" idProveedor="<?php echo (int)$value["id"]; ?>"><i class="fa fa-trash"></i></button>
              </div>
              <div class="proveedor-state-template"><?php echo tmProveedorEstado($value["estado"], $value["id"]); ?></div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</div>

<div id="modalVerProveedor" class="modal fade proveedor-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <div class="proveedor-modal-title">
          <div class="proveedor-modal-icon"><i class="fa fa-industry"></i></div>
          <div>
            <span class="proveedor-modal-kicker" id="proveedorModalEstado">Detalle</span>
            <h4 id="proveedorModalTitulo">Proveedor</h4>
          </div>
        </div>
      </div>
      <div class="modal-body">
        <div class="proveedor-detail-grid">
          <div class="proveedor-detail-item"><span>Contacto</span><strong id="proveedorModalContacto">-</strong></div>
          <div class="proveedor-detail-item"><span>Telefono</span><strong id="proveedorModalTelefono">-</strong></div>
          <div class="proveedor-detail-item full"><span>Direccion</span><strong id="proveedorModalDireccion">-</strong></div>
        </div>
        <div class="proveedor-modal-actions" id="proveedorModalAcciones"></div>
      </div>
    </div>
  </div>
</div>

<div id="modalAgregarProveedor" class="modal fade proveedor-form-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form role="form" method="post" enctype="multipart/form-data">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><i class="fa fa-plus"></i> Agregar proveedor</h4>
        </div>
        <div class="modal-body">
          <div class="proveedor-form-grid">
            <div class="form-group full">
              <label>Proveedor</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-truck"></i></span>
                <input type="text" class="form-control" name="nuevoProveedor" placeholder="Nombre del proveedor" required>
              </div>
            </div>
            <div class="form-group">
              <label>Contacto</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                <input type="text" class="form-control" name="nuevoContacto" placeholder="Persona de contacto" id="nuevoContacto" required>
              </div>
            </div>
            <div class="form-group">
              <label>Telefono</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-phone"></i></span>
                <input type="number" class="form-control" name="nuevoTelefono" placeholder="Telefono" required>
              </div>
            </div>
            <div class="form-group full">
              <label>Direccion</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-map"></i></span>
                <input type="text" class="form-control" name="nuevaDireccion" placeholder="Direccion del proveedor" required>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar proveedor</button>
        </div>
        <?php
          $crearProveedor = new ControladorProveedor();
          $crearProveedor -> ctrCrearProveedor();
        ?>
      </form>
    </div>
  </div>
</div>

<div id="modalEditarProveedor" class="modal fade proveedor-form-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form role="form" method="post" enctype="multipart/form-data">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><i class="fa fa-pencil"></i> Editar proveedor</h4>
        </div>
        <div class="modal-body">
          <div class="proveedor-form-grid">
            <div class="form-group full">
              <label>Proveedor</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-truck"></i></span>
                <input type="text" class="form-control" id="editarProveedor" name="editarProveedor" value="" readonly>
              </div>
            </div>
            <div class="form-group">
              <label>Contacto</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                <input type="text" class="form-control" id="editarContacto" name="editarContacto" value="" required>
              </div>
            </div>
            <div class="form-group">
              <label>Telefono</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-phone"></i></span>
                <input type="number" class="form-control" id="editarTelefono" name="editarTelefono" value="" required>
              </div>
            </div>
            <div class="form-group full">
              <label>Direccion</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-map"></i></span>
                <input type="text" class="form-control" id="editarDireccion" name="editarDireccion" value="" required>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Modificar proveedor</button>
        </div>
        <?php
          $editarProveedor = new ControladorProveedor();
          $editarProveedor -> ctrEditarProveedor();
        ?>
      </form>
    </div>
  </div>
</div>

<script>
$(function(){
  $('[title]').tooltip({container:'body'});
});

$(document).on("input", "#buscarProveedorCard", function(){
  var termino = ($(this).val() || "").toLowerCase().trim();
  $(".proveedor-card").each(function(){
    var texto = ($(this).attr("data-search") || "").toLowerCase();
    $(this).toggle(texto.indexOf(termino) !== -1);
  });
});

$(document).on("click", ".proveedor-card", function(e){
  if($(e.target).closest("button, a, .btn").length){
    return;
  }

  var $card = $(this);
  $("#proveedorModalEstado").text($card.data("estado") || "Detalle");
  $("#proveedorModalTitulo").text($card.data("nombre") || "Proveedor");
  $("#proveedorModalContacto").text($card.data("contacto") || "-");
  $("#proveedorModalTelefono").text($card.data("telefono") || "-");
  $("#proveedorModalDireccion").text($card.data("direccion") || "-");

  var acciones = $card.find(".proveedor-state-template").html() + $card.find(".proveedor-actions").html();
  $("#proveedorModalAcciones").html(acciones);
  $("#modalVerProveedor").modal("show");
});

$(document).on("keydown", ".proveedor-card", function(e){
  if(e.key === "Enter" || e.key === " "){
    e.preventDefault();
    $(this).trigger("click");
  }
});
</script>

<?php
  $borrarProveedor = new ControladorProveedor();
  $borrarProveedor -> ctrBorrarProveedor();
?>
