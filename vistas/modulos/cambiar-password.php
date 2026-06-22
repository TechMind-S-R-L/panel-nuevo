<?php

if(!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok"){
  echo '<script>window.location = "salir";</script>';
  return;
}

?>

<div class="content-wrapper password-change-wrapper">
  <section class="content-header">
    <h1>Cambiar contrasena</h1>
    <ol class="breadcrumb">
      <li><i class="fa fa-lock"></i> Seguridad</li>
      <li class="active">Cambio obligatorio</li>
    </ol>
  </section>

  <section class="content">
    <div class="row">
      <div class="col-md-6 col-md-offset-3">
        <div class="box box-primary">
          <div class="box-header with-border">
            <h3 class="box-title">Cree su contrasena personal</h3>
          </div>

          <form method="post">
            <div class="box-body">
              <div class="alert alert-info">
                Por seguridad debe cambiar la contrasena temporal antes de continuar.
              </div>

              <div class="form-group">
                <label>Contrasena temporal</label>
                <div class="input-group">
                  <span class="input-group-addon"><i class="fa fa-key"></i></span>
                  <input type="password" class="form-control input-lg" name="passwordActualObligatorio" required>
                </div>
              </div>

              <div class="form-group">
                <label>Nueva contrasena</label>
                <div class="input-group">
                  <span class="input-group-addon"><i class="fa fa-lock"></i></span>
                  <input type="password" class="form-control input-lg" name="nuevaPasswordObligatoria" minlength="6" maxlength="20" required>
                </div>
              </div>

              <div class="form-group">
                <label>Confirmar nueva contrasena</label>
                <div class="input-group">
                  <span class="input-group-addon"><i class="fa fa-check"></i></span>
                  <input type="password" class="form-control input-lg" name="confirmarPasswordObligatoria" minlength="6" maxlength="20" required>
                </div>
              </div>
            </div>

            <div class="box-footer">
              <button type="submit" class="btn btn-primary btn-block btn-lg">Guardar y continuar</button>
            </div>

            <?php
              ControladorUsuarios::ctrCambiarPasswordObligatorio();
            ?>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>
