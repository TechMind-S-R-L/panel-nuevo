<?php
if(($_SESSION["perfil"] ?? "") !== "Administrador"){
  echo '<script>window.location = "inicio";</script>';
  return;
}

ModeloWebPublicaciones::mdlAsegurarTablas();

$boletasNombre = ModeloWebPublicaciones::mdlObtenerConfiguracion("boletas_empresa_nombre", "TECHMIND S.R.L.");
$boletasDireccion = ModeloWebPublicaciones::mdlObtenerConfiguracion("boletas_empresa_direccion", "Km 6 doble via la guardia, calle paraiso Nro 6387");
$boletasTelefono = ModeloWebPublicaciones::mdlObtenerConfiguracion("boletas_empresa_telefono", "(+591) 75556540 | (+591) 78572656");
$boletasCorreo = ModeloWebPublicaciones::mdlObtenerConfiguracion("boletas_empresa_correo", "techmind.srl.bo@gmail.com");

function tmBoletaAdminEsc($valor){
  return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}
?>

<div class="content-wrapper tm-boletas-admin">
  <style>
    .tm-boletas-admin{background:#f5f8fc !important;}
    .tm-boletas-hero{
      background:linear-gradient(135deg,#0f355d,#2287b8);
      color:#fff;
      border-radius:18px;
      padding:24px;
      margin-bottom:18px;
      box-shadow:0 18px 42px rgba(15,53,93,.18);
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:16px;
      flex-wrap:wrap;
    }
    .tm-boletas-hero h2{margin:0 0 6px;font-weight:900;font-size:28px;}
    .tm-boletas-hero p{margin:0;color:rgba(255,255,255,.84);max-width:760px;}
    .tm-boletas-card{
      background:rgba(255,255,255,.94);
      border:1px solid #dbe7f4;
      border-radius:18px;
      box-shadow:0 18px 42px rgba(23,75,134,.10);
      overflow:hidden;
    }
    .tm-boletas-card-head{
      padding:18px 20px;
      border-bottom:1px solid #e5edf7;
      display:flex;
      justify-content:space-between;
      gap:12px;
      align-items:center;
      flex-wrap:wrap;
    }
    .tm-boletas-card-head h3{margin:0;font-size:20px;font-weight:900;color:#172033;}
    .tm-boletas-card-head p{margin:4px 0 0;color:#64748b;}
    .tm-boletas-form{padding:20px;}
    .tm-boletas-form label{font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#5b6b83;font-weight:900;}
    .tm-boletas-form .form-control{
      border-radius:12px;
      border:1px solid #d7e2ef;
      min-height:44px;
      box-shadow:none;
    }
    .tm-boletas-preview{
      background:linear-gradient(180deg,#f8fbff,#fff);
      border:1px dashed #b8cbe3;
      border-radius:16px;
      padding:18px;
      margin-top:10px;
      display:flex;
      gap:16px;
      align-items:center;
    }
    .tm-boletas-preview img{width:58px;height:58px;object-fit:contain;}
    .tm-boletas-preview strong{display:block;color:#174b86;font-size:18px;}
    .tm-boletas-preview span{display:block;color:#516276;line-height:1.45;}
    .tm-boletas-actions{
      padding:16px 20px;
      border-top:1px solid #e5edf7;
      background:#fbfdff;
      display:flex;
      justify-content:flex-end;
    }
    .tm-boletas-actions .btn{
      border-radius:12px;
      font-weight:900;
      padding:11px 18px;
      box-shadow:0 12px 26px rgba(93,135,255,.18);
    }
  </style>

  <section class="content-header">
    <div class="tm-boletas-hero">
      <div>
        <h2><i class="fa fa-file-text-o"></i> Datos de boletas</h2>
        <p>Administra los telefonos, direccion y correo que aparecen en el encabezado de cotizaciones, notas, contratos, ordenes y boletas del sistema.</p>
      </div>
      <span class="label label-info" style="font-size:12px;padding:9px 12px;border-radius:999px;">Administracion</span>
    </div>
  </section>

  <section class="content">
    <form method="post" class="tm-boletas-card">
      <div class="tm-boletas-card-head">
        <div>
          <h3>Encabezado institucional</h3>
          <p>Los cambios se aplican a las nuevas boletas generadas despues de guardar.</p>
        </div>
      </div>

      <div class="tm-boletas-form">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Nombre de la empresa</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-building"></i></span>
                <input type="text" class="form-control" name="boletasEmpresaNombre" value="<?php echo tmBoletaAdminEsc($boletasNombre); ?>" maxlength="180" required>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Correo del encabezado</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-envelope"></i></span>
                <input type="email" class="form-control" name="boletasEmpresaCorreo" value="<?php echo tmBoletaAdminEsc($boletasCorreo); ?>" maxlength="180" required>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Telefonos del encabezado</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-phone"></i></span>
                <input type="text" class="form-control" name="boletasEmpresaTelefono" value="<?php echo tmBoletaAdminEsc($boletasTelefono); ?>" maxlength="180" placeholder="(+591) 75556540 | (+591) 78572656" required>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Direccion del encabezado</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-map-marker"></i></span>
                <input type="text" class="form-control" name="boletasEmpresaDireccion" value="<?php echo tmBoletaAdminEsc($boletasDireccion); ?>" maxlength="220" required>
              </div>
            </div>
          </div>
        </div>

        <div class="tm-boletas-preview">
          <img src="vistas/img/plantilla/logo.ico" alt="TechMind">
          <div>
            <strong><?php echo tmBoletaAdminEsc($boletasNombre); ?></strong>
            <span><?php echo tmBoletaAdminEsc($boletasDireccion); ?></span>
            <span><?php echo tmBoletaAdminEsc($boletasTelefono); ?></span>
            <span><?php echo tmBoletaAdminEsc($boletasCorreo); ?></span>
          </div>
        </div>
      </div>

      <div class="tm-boletas-actions">
        <button type="submit" class="btn btn-primary" name="guardarDatosBoletas" value="1">
          <i class="fa fa-save"></i> Guardar datos de boletas
        </button>
      </div>

      <?php ControladorEmpresaBoletas::ctrGuardarDatosBoletas(); ?>
    </form>
  </section>
</div>

