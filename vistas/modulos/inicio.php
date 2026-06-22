<div class="content-wrapper">

  <section class="content-header">
    <h1>
      Tablero
      <small>Panel de control por rol</small>
    </h1>

    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Tablero</li>
    </ol>
  </section>

  <section class="content">

    <?php
      include "inicio/dashboard-roles.php";

      if($_SESSION["perfil"] == "Administrador"){
        include "inicio/roles-permisos.php";
      }
    ?>

  </section>

</div>
