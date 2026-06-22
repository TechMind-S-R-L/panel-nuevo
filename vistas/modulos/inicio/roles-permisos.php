<?php

$rolesPermisos = array(
  "Administrador" => array(
    "slug" => "administrador",
    "icono" => "fa-user-secret",
    "color" => "aqua",
    "permisos" => array(
      "Usuarios, reportes y logs del sistema",
      "Gestion de almacen completa",
      "Gestion de compras, pagos y precios",
      "Ventas, cotizaciones, facturas y despacho"
    )
  ),
  "Vendedor" => array(
    "slug" => "vendedor",
    "icono" => "fa-shopping-cart",
    "color" => "green",
    "permisos" => array(
      "Administrar ventas",
      "Crear ventas",
      "Administrar cotizaciones",
      "Crear cotizaciones"
    )
  ),
  "Cajero" => array(
    "slug" => "cajero",
    "icono" => "fa-money",
    "color" => "yellow",
    "permisos" => array(
      "Administrar ventas y editar ventas",
      "Aprobar pagos de ventas",
      "Aprobar solicitudes de compra",
      "Asignar precios a productos"
    )
  ),
  "Almacen" => array(
    "slug" => "almacen",
    "icono" => "fa-cubes",
    "color" => "red",
    "permisos" => array(
      "Crear categorias y productos sin precio",
      "Crear solicitudes de compra",
      "Registrar ordenes de ingreso",
      "Despachar productos al cliente"
    )
  ),
  "Mensajero" => array(
    "slug" => "mensajero",
    "icono" => "fa-print",
    "color" => "purple",
    "permisos" => array(
      "Ver solicitudes de compra aprobadas",
      "Imprimir solicitudes aprobadas para compra"
    )
  ),
  "Tecnico" => array(
    "slug" => "tecnico",
    "icono" => "fa-wrench",
    "color" => "gray",
    "permisos" => array(
      "Ver ordenes de servicio cobradas",
      "Imprimir orden de instalacion",
      "Cambiar estado del servicio",
      "Contactar al cliente con ubicacion y referencia"
    )
  ),
  "Desarrollador" => array(
    "slug" => "desarrollador",
    "icono" => "fa-code",
    "color" => "teal",
    "permisos" => array(
      "Ver proyectos de software asignados",
      "Registrar avances y porcentaje de desarrollo",
      "Subir documentos del proyecto",
      "Marcar proyectos listos para cobro final"
    )
  )
);

?>

<style>
  .tm-roles-panel{
    border:1px solid rgba(223,232,243,.86) !important;
    border-radius:18px !important;
    background:rgba(255,255,255,.46);
    box-shadow:0 18px 42px rgba(23,75,134,.10);
    overflow:hidden;
    backdrop-filter:blur(8px);
  }
  .tm-roles-panel .box-header{
    background:linear-gradient(180deg,rgba(255,255,255,.86),rgba(245,248,252,.72));
    padding:17px 18px;
  }
  .tm-roles-panel .box-title{
    font-weight:850;
    color:#172033;
  }
  .tm-role-card{
    display:block;
    min-height:154px;
    margin-bottom:16px;
    padding:16px;
    border-radius:16px;
    color:#172033;
    background:rgba(255,255,255,.42);
    border:1px solid rgba(223,232,243,.82);
    box-shadow:0 14px 30px rgba(23,75,134,.08);
    text-decoration:none !important;
    position:relative;
    overflow:hidden;
    transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease;
  }
  .tm-role-card:hover{
    color:#172033;
    transform:translateY(-3px);
    border-color:rgba(93,135,255,.30);
    box-shadow:0 22px 46px rgba(23,75,134,.13);
  }
  .tm-role-card.active{
    background:linear-gradient(135deg,rgba(236,242,255,.78),rgba(255,255,255,.50));
    border-color:rgba(23,75,134,.34);
    box-shadow:0 0 0 2px rgba(23,75,134,.10),0 22px 46px rgba(23,75,134,.14);
  }
  .tm-role-card:after{
    content:"";
    position:absolute;
    width:110px;
    height:110px;
    right:-45px;
    bottom:-50px;
    border-radius:50%;
    background:radial-gradient(circle,rgba(93,135,255,.20),transparent 70%);
    pointer-events:none;
  }
  .tm-role-head{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:10px;
    margin-bottom:18px;
    position:relative;
    z-index:1;
  }
  .tm-role-icon{
    width:42px;
    height:42px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border-radius:14px;
    color:#fff;
    background:linear-gradient(135deg,#174b86,#5d87ff);
    box-shadow:0 12px 24px rgba(23,75,134,.18);
    font-size:18px;
  }
  .tm-role-status{
    color:#174b86;
    background:rgba(236,242,255,.62);
    border:1px solid rgba(93,135,255,.22);
    border-radius:999px;
    padding:5px 8px;
    font-size:10px;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.04em;
    white-space:nowrap;
  }
  .tm-role-card h4{
    margin:0 0 6px;
    font-size:17px;
    font-weight:900;
    color:#172033;
    position:relative;
    z-index:1;
  }
  .tm-role-card p{
    margin:0 0 12px;
    color:#61718b;
    font-weight:650;
    font-size:12px;
    position:relative;
    z-index:1;
  }
  .tm-role-foot{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:8px;
    color:#174b86;
    font-weight:850;
    font-size:12px;
    position:relative;
    z-index:1;
  }
  .tm-role-foot span{
    color:#61718b;
    font-weight:750;
  }
  .tm-roles-note{
    margin:2px 0 0;
    color:#61718b;
    font-weight:650;
  }
  @media(max-width:767px){
    .tm-role-card{
      min-height:138px;
    }
  }
  body.tm-dark-mode .tm-roles-panel,
  body.tm-dark-mode .tm-role-card{
    background:rgba(16,26,46,.72);
    border-color:#22314e !important;
    box-shadow:0 18px 42px rgba(0,0,0,.28);
  }
  body.tm-dark-mode .tm-roles-panel .box-header{
    background:rgba(23,35,59,.88);
  }
  body.tm-dark-mode .tm-roles-panel .box-title,
  body.tm-dark-mode .tm-role-card h4{
    color:#e5edf7;
  }
  body.tm-dark-mode .tm-role-card p,
  body.tm-dark-mode .tm-role-foot span,
  body.tm-dark-mode .tm-roles-note{
    color:#9fb0c7;
  }
  body.tm-dark-mode .tm-role-status{
    color:#dbeafe;
    background:rgba(93,135,255,.18);
    border-color:rgba(93,135,255,.30);
  }
</style>

<div class="row">
  <div class="col-xs-12">
    <div class="box box-primary tm-roles-panel">
      <div class="box-header with-border">
        <h3 class="box-title">Roles y accesos habilitados</h3>
      </div>
      <div class="box-body">
        <div class="row">
          <?php foreach ($rolesPermisos as $rol => $info): ?>
            <?php $activo = (($_SESSION["vistaRolMenu"] ?? "administrador") == $info["slug"]); ?>
            <div class="col-lg-2 col-md-4 col-sm-6 col-xs-12">
              <a href="index.php?ruta=inicio&vistaRolMenu=<?php echo $info["slug"]; ?>" class="tm-role-card <?php echo $activo ? "active" : ""; ?>">
                <div class="tm-role-head">
                  <span class="tm-role-icon"><i class="fa <?php echo $info["icono"]; ?>"></i></span>
                  <span class="tm-role-status"><?php echo $activo ? "Activa" : "Vista"; ?></span>
                </div>
                <h4><?php echo $rol; ?></h4>
                <p><?php echo count($info["permisos"]); ?> accesos principales habilitados.</p>
                <div class="tm-role-foot">
                  <span><?php echo $activo ? "Menu en uso" : "Cambiar menu"; ?></span>
                  <i class="fa fa-angle-right"></i>
                </div>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
        <p class="tm-roles-note">Estas tarjetas solo cambian la vista del menu. Tu usuario sigue siendo administrador.</p>
      </div>
    </div>
  </div>
</div>
