 <header class="main-header">
 	
	<!--=====================================
	LOGOTIPO
	======================================-->
	<a href="inicio" class="logo tm-logo-superior" aria-hidden="true" tabindex="-1"></a>

	<!--=====================================
	BARRA DE NAVEGACIÓN
	======================================-->
	<nav class="navbar navbar-static-top" role="navigation">
		
		<!-- Botón de navegación -->

	 	<a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
        	
        	<span class="sr-only">Toggle navigation</span>
      	
      	</a>

		<!-- perfil de usuario -->

		<div class="navbar-custom-menu">
				
			<ul class="nav navbar-nav">
        <li>
          <a href="#" class="tm-theme-toggle" id="tmThemeToggle" title="Cambiar modo claro u oscuro">
            <i class="fa fa-moon-o"></i><span>Modo oscuro</span>
          </a>
        </li>
				
				<li class="dropdown user user-menu">
					
					<a href="#" class="dropdown-toggle" data-toggle="dropdown">

					<?php

					if($_SESSION["foto"] != ""){

						echo '<img src="'.$_SESSION["foto"].'" class="user-image">';

					}else{


						echo '<img src="vistas/img/usuarios/default/anonymous.png" class="user-image">';

					}


					?>
						
						<span class="hidden-xs tm-navbar-user-text">
              <b><?php echo $_SESSION["usuario"]; ?></b>
              <small><?php echo $_SESSION["perfil"]." / ".ucfirst($_SESSION["rol"]); ?></small>
            </span>

					</a>

					<ul class="dropdown-menu techmind-user-dropdown">
              <!-- User image -->
              <li class="user-header techmind-user-card">
              <?php
              if($_SESSION["foto"] != ""){
                echo '<img src="'.$_SESSION["foto"].'" class="img-circle" alt="User Image">';
              }else{ 
                echo '<img src="vistas/img/usuarios/default/anonymous.png" class="img-circle" alt="User Image">';
              }
                ?>
                <p>
                <?php  echo $_SESSION["nombre"]; ?><br>
                Usuario: <?php echo $_SESSION["usuario"]; ?> - <?php echo $_SESSION["perfil"]; ?> / <?php echo ucfirst($_SESSION["rol"]); ?>
                <?php if($_SESSION["perfil"] == "Administrador" && isset($_SESSION["vistaRolMenu"]) && $_SESSION["vistaRolMenu"] != "" && $_SESSION["vistaRolMenu"] != "administrador"): ?>
                  <br><small>Vista temporal del menu: <?php echo ucfirst($_SESSION["vistaRolMenu"]); ?></small>
                <?php endif; ?>
                  <small>
                    <?php
                    date_default_timezone_set('America/La_Paz');
                     $fechaActual = date('d-m-Y H:i:s');
                     echo $fechaActual;
                     ?>
                     </small>
                </p>
              </li>
            
              <!-- Menu Footer-->
              <li class="user-footer techmind-user-footer">
                <a href="salir" class="btn btn-danger btn-block btn-flat"><i class="fa fa-sign-out"></i> Cerrar sesion</a>
              </li>
            </ul>

				</li>

			</ul>

		</div>

	</nav>

 </header>

 <style>
  .navbar-custom-menu>.navbar-nav>li.user-menu>a{
    max-width:390px;
    overflow:hidden;
    white-space:nowrap;
    display:flex;
    align-items:center;
    gap:9px;
    min-height:50px;
    color:#172033 !important;
    padding:8px 16px;
  }
  .navbar-custom-menu>.navbar-nav>li.user-menu>a .user-image{
    width:32px;
    height:32px;
    border:2px solid rgba(93,135,255,.28);
    margin:0;
    object-fit:cover;
  }
  .navbar-custom-menu>.navbar-nav>li.user-menu>a .tm-navbar-user-text{
    display:inline-flex !important;
    flex-direction:column;
    max-width:310px;
    line-height:1.1;
    overflow:hidden;
    vertical-align:middle;
    white-space:nowrap;
  }
  .tm-navbar-user-text b,
  .tm-navbar-user-text small{
    overflow:hidden;
    text-overflow:ellipsis;
  }
  .tm-navbar-user-text b{
    font-size:13px;
    color:#172033;
  }
  .tm-navbar-user-text small{
    color:#61718b;
    font-weight:700;
  }
  .navbar-nav>.user-menu>.dropdown-menu.techmind-user-dropdown{
    width:300px;
    max-width:300px;
    border:0;
    border-radius:14px;
    padding:0;
    overflow:hidden;
    box-shadow:0 18px 44px rgba(15,23,42,.18);
    background:#fff;
    z-index:40000 !important;
  }
  .main-header .navbar-custom-menu,
  .main-header .navbar-custom-menu .dropdown,
  .main-header .navbar-custom-menu .dropdown-menu{
    z-index:40000 !important;
  }
  .skin-blue .main-header .navbar .nav>li.user-menu.open>a,
  .skin-blue .main-header .navbar .nav>li.user-menu>a:focus,
  .skin-blue .main-header .navbar .nav>li.user-menu>a:hover{
    background:rgba(236,242,255,.46) !important;
  }
  .navbar-nav>.user-menu>.dropdown-menu>li.user-header.techmind-user-card{
    height:auto;
    min-height:190px;
    padding:18px 18px 14px;
    background:linear-gradient(180deg,rgba(255,255,255,.96),rgba(245,248,252,.94)) !important;
    color:#1f2937;
    text-align:center;
  }
  .navbar-nav>.user-menu>.dropdown-menu>li.user-header.techmind-user-card>img{
    width:78px;
    height:78px;
    border:3px solid rgba(93,135,255,.24);
    margin-bottom:10px;
  }
  .navbar-nav>.user-menu>.dropdown-menu>li.user-header.techmind-user-card>p{
    color:#1f2937;
    font-weight:600;
    line-height:1.35;
    margin:0;
    white-space:normal;
    overflow-wrap:anywhere;
  }
  .navbar-nav>.user-menu>.dropdown-menu>li.user-header.techmind-user-card>p small{
    display:block;
    margin-top:8px;
    color:#64748b;
    font-size:12px;
  }
  .navbar-nav>.user-menu>.dropdown-menu>.techmind-user-footer{
    background:#f8fafc;
    padding:12px;
    border-top:1px solid #e5e7eb;
  }
  .navbar-nav>.user-menu>.dropdown-menu>.techmind-user-footer .btn{
    border-radius:9px;
    font-weight:600;
  }
  @media (max-width:767px){
    .navbar-custom-menu>.navbar-nav>li.user-menu>a{
      max-width:70px;
    }
    .navbar-custom-menu>.navbar-nav>li.user-menu>a .tm-navbar-user-text{
      display:none !important;
    }
  }
 </style>
 <script>
  (function(){
    function aplicarTextoTema(){
      var boton = document.getElementById("tmThemeToggle");
      if(!boton){ return; }
      var oscuro = document.body.classList.contains("tm-dark-mode");
      boton.innerHTML = oscuro ? '<i class="fa fa-sun-o"></i><span>Modo claro</span>' : '<i class="fa fa-moon-o"></i><span>Modo oscuro</span>';
    }
    document.addEventListener("DOMContentLoaded", function(){
      try{
        localStorage.removeItem("tmTheme");
      }catch(e){}
      document.body.classList.remove("tm-dark-mode");
      aplicarTextoTema();
      var boton = document.getElementById("tmThemeToggle");
      if(boton){
        boton.addEventListener("click", function(e){
          e.preventDefault();
          document.body.classList.toggle("tm-dark-mode");
          aplicarTextoTema();
        });
      }
    });
  })();
 </script>
