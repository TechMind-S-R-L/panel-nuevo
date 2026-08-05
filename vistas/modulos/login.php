<?php

$tokenRecuperacion = $_GET["recuperarPassword"] ?? "";
$mostrarRecuperar = isset($_GET["recuperar"]);
$modoLogin = $tokenRecuperacion != "" ? "reset" : ($mostrarRecuperar ? "recover" : "login");

?>

<style>
  body.login-page{
    min-height:100vh;
    background:#071525 !important;
    overflow-x:hidden;
  }
  .tm-login-bg{
    position:fixed;
    inset:0;
    z-index:0;
    background:
      radial-gradient(circle at 18% 16%, rgba(45,128,222,.34), transparent 32%),
      radial-gradient(circle at 82% 14%, rgba(20,170,220,.28), transparent 30%),
      linear-gradient(135deg,#06111f 0%,#0b2236 48%,#071525 100%);
  }
  #tmLoginParticles{
    position:fixed;
    inset:0;
    z-index:1;
    width:100vw;
    height:100vh;
    pointer-events:auto;
  }
  .tm-login-shell{
    min-height:100vh;
    position:relative;
    z-index:2;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:28px;
  }
  .tm-login-card{
    width:min(1060px,100%);
    min-height:610px;
    display:grid;
    grid-template-columns:.92fr 1.08fr;
    border:1px solid rgba(255,255,255,.18);
    border-radius:28px;
    overflow:hidden;
    background:rgba(255,255,255,.08);
    box-shadow:0 28px 90px rgba(0,0,0,.38);
    backdrop-filter:blur(18px);
  }
  .tm-login-brand{
    position:relative;
    padding:36px;
    color:#fff;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    background:
      linear-gradient(155deg,rgba(15,43,67,.88),rgba(20,112,164,.48)),
      url("vistas/img/plantilla/back.png") center/cover no-repeat;
  }
  .tm-login-brand:after{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(180deg,rgba(7,21,37,.08),rgba(7,21,37,.72));
    pointer-events:none;
  }
  .tm-login-brand > *{position:relative;z-index:1;}
  .tm-login-logo{
    width:260px;
    max-width:100%;
    display:block;
    margin-bottom:26px;
    filter:drop-shadow(0 16px 28px rgba(0,0,0,.28));
  }
  .tm-login-brand h1{
    margin:0 0 12px;
    font-size:34px;
    line-height:1.08;
    font-weight:900;
    letter-spacing:0;
  }
  .tm-login-brand p{
    margin:0;
    max-width:390px;
    color:rgba(236,248,255,.9);
    font-size:15px;
    line-height:1.55;
    font-weight:600;
  }
  .tm-login-slogan{
    margin-top:24px;
    padding:14px 16px;
    border-radius:18px;
    background:rgba(255,255,255,.12);
    border:1px solid rgba(255,255,255,.2);
    color:#eaf7ff;
    font-weight:800;
  }
  .tm-login-slogan b{color:#47b9ff;}
  .tm-login-pills{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    margin-top:22px;
  }
  .tm-login-pills span{
    padding:8px 10px;
    border-radius:999px;
    background:rgba(255,255,255,.13);
    border:1px solid rgba(255,255,255,.18);
    color:#f4fbff;
    font-size:12px;
    font-weight:800;
  }
  .tm-login-foot{
    color:rgba(234,247,255,.76);
    font-size:12px;
    font-weight:700;
  }
  .tm-login-form{
    padding:44px;
    display:flex;
    align-items:center;
    background:rgba(247,251,255,.92);
  }
  .tm-login-panel{
    width:100%;
    max-width:430px;
    margin:0 auto;
  }
  .tm-login-badge{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 11px;
    border-radius:999px;
    background:#e8f4ff;
    color:#17598f;
    font-weight:900;
    font-size:12px;
    margin-bottom:16px;
  }
  .tm-login-form h2{
    margin:0 0 8px;
    color:#10233f;
    font-size:30px;
    font-weight:900;
    letter-spacing:0;
  }
  .tm-subtitle{
    margin:0 0 24px;
    color:#60748c;
    font-size:14px;
    line-height:1.45;
    font-weight:700;
  }
  .tm-field{
    margin-bottom:15px;
  }
  .tm-field label{
    display:block;
    margin-bottom:7px;
    color:#243b56;
    font-weight:900;
    font-size:12px;
    text-transform:uppercase;
  }
  .tm-input-wrap{
    position:relative;
  }
  .tm-input-wrap i{
    position:absolute;
    left:15px;
    top:50%;
    transform:translateY(-50%);
    color:#2f88bf;
    font-size:15px;
    z-index:2;
  }
  .tm-input{
    height:48px;
    border:1px solid #cfe0ef;
    border-radius:14px;
    box-shadow:none;
    padding-left:42px;
    color:#10233f;
    font-weight:800;
    background:#fff;
  }
  .tm-input-wrap:has(.tm-password-toggle) .tm-input{
    padding-right:50px;
  }
  .tm-password-toggle{
    position:absolute;
    right:8px;
    top:50%;
    transform:translateY(-50%);
    width:38px;
    height:38px;
    border:0;
    border-radius:13px;
    background:#eef6ff;
    color:#2478d4;
    display:grid;
    place-items:center;
    cursor:pointer;
    z-index:3;
    transition:.18s ease;
  }
  .tm-password-toggle:hover,
  .tm-password-toggle.is-visible{
    background:#2478d4;
    color:#fff;
    box-shadow:0 10px 22px rgba(36,120,212,.24);
  }
  .tm-input-wrap .tm-password-toggle i{
    position:static;
    transform:none;
    color:inherit;
  }
  .tm-input:focus{
    border-color:#1fa7dc;
    box-shadow:0 0 0 4px rgba(31,167,220,.12);
  }
  .tm-btn{
    height:48px;
    border-radius:14px;
    font-weight:900;
    border:0;
    background:linear-gradient(135deg,#174b86,#17a8de);
    box-shadow:0 14px 26px rgba(23,75,134,.22);
  }
  .tm-btn:hover,
  .tm-btn:focus{
    background:linear-gradient(135deg,#123d70,#129bd1);
  }
  .tm-login-links{
    display:flex;
    justify-content:space-between;
    gap:12px;
    margin-top:16px;
    flex-wrap:wrap;
  }
  .tm-login-links a{
    color:#17699f;
    font-weight:900;
  }
  .tm-note{
    margin-top:18px;
    padding:13px 14px;
    background:#fff6df;
    border:1px solid #ffe1a8;
    border-left:5px solid #f39c12;
    color:#604314;
    border-radius:14px;
    font-weight:700;
    line-height:1.45;
  }
  .tm-login-alert-space .alert{
    border-radius:14px;
    border:0;
    font-weight:800;
  }
  .tm-login-mode{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:8px;
    margin-bottom:20px;
  }
  .tm-login-mode span{
    border:1px solid #d7e6f4;
    border-radius:13px;
    padding:9px 8px;
    color:#60748c;
    background:#fff;
    text-align:center;
    font-size:11px;
    font-weight:900;
  }
  .tm-login-mode span.is-active{
    background:#174b86;
    border-color:#174b86;
    color:#fff;
  }
  @media(max-width:900px){
    .tm-login-card{grid-template-columns:1fr;max-width:520px;}
    .tm-login-brand{min-height:260px;padding:28px;}
    .tm-login-form{padding:32px 26px;}
    .tm-login-form h2{font-size:26px;}
  }
  @media(max-width:520px){
    .tm-login-shell{padding:14px;}
    .tm-login-card{border-radius:22px;}
    .tm-login-mode{grid-template-columns:1fr;}
  }
</style>

<div class="tm-login-bg"></div>
<canvas id="tmLoginParticles" aria-hidden="true"></canvas>

<div class="tm-login-shell">
  <div class="tm-login-card">
    <div class="tm-login-brand">
      <div>
        <img class="tm-login-logo" src="vistas/img/plantilla/LOGO0.png" alt="TechMind">
        <h1>Bienvenido a TechMind</h1>
        <p>Control interno para ventas, caja, almacen, servicios, proyectos y soporte tecnico.</p>
        <div class="tm-login-slogan"><b>La mente tecnologica</b> que tu empresa necesita.</div>
        <div class="tm-login-pills">
          <span><i class="fa fa-shield"></i> Acceso seguro</span>
          <span><i class="fa fa-cubes"></i> Inventario real</span>
          <span><i class="fa fa-wrench"></i> Servicio tecnico</span>
          <span><i class="fa fa-line-chart"></i> Reportes</span>
        </div>
      </div>
      <div class="tm-login-foot">TechMind S.R.L. | Sistema administrativo</div>
    </div>

    <div class="tm-login-form">
      <div class="tm-login-panel">
        <div class="tm-login-badge"><i class="fa fa-lock"></i> Acceso privado</div>

        <div class="tm-login-mode">
          <span class="<?php echo $modoLogin == "login" ? "is-active" : ""; ?>">Ingreso</span>
          <span class="<?php echo $modoLogin == "recover" ? "is-active" : ""; ?>">Recuperacion</span>
          <span class="<?php echo $modoLogin == "reset" ? "is-active" : ""; ?>">Nueva clave</span>
        </div>

        <?php if($tokenRecuperacion != ""): ?>

          <h2>Nueva contrasena</h2>
          <p class="tm-subtitle">Cree una contrasena personal para recuperar su acceso al sistema.</p>

          <form method="post">
            <input type="hidden" name="tokenPassword" value="<?php echo htmlspecialchars($tokenRecuperacion, ENT_QUOTES, "UTF-8"); ?>">

            <div class="tm-field">
              <label>Nueva contrasena</label>
              <div class="tm-input-wrap">
                <i class="fa fa-key"></i>
                <input id="panel-reset-password" type="password" class="form-control tm-input" name="nuevoPasswordReset" minlength="6" maxlength="20" required>
                <button class="tm-password-toggle" type="button" data-password-toggle="panel-reset-password" aria-label="Mostrar contrasena">
                  <i class="fa fa-eye"></i>
                </button>
              </div>
            </div>

            <div class="tm-field">
              <label>Confirmar contrasena</label>
              <div class="tm-input-wrap">
                <i class="fa fa-check-circle"></i>
                <input id="panel-reset-password-confirmar" type="password" class="form-control tm-input" name="confirmarPasswordReset" minlength="6" maxlength="20" required>
                <button class="tm-password-toggle" type="button" data-password-toggle="panel-reset-password-confirmar" aria-label="Mostrar contrasena">
                  <i class="fa fa-eye"></i>
                </button>
              </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block tm-btn">Cambiar contrasena</button>

            <div class="tm-login-links">
              <a href="index.php"><i class="fa fa-arrow-left"></i> Volver al login</a>
            </div>

            <div class="tm-login-alert-space">
              <?php ControladorUsuarios::ctrRestablecerPassword(); ?>
            </div>
          </form>

        <?php elseif($mostrarRecuperar): ?>

          <h2>Recuperar acceso</h2>
          <p class="tm-subtitle">Ingrese el correo registrado. Enviaremos un enlace para cambiar su contrasena.</p>

          <form method="post">
            <div class="tm-field">
              <label>Correo electronico</label>
              <div class="tm-input-wrap">
                <i class="fa fa-envelope"></i>
                <input type="email" class="form-control tm-input" name="recuperarEmail" placeholder="correo@empresa.com" required>
              </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block tm-btn">Enviar enlace de recuperacion</button>

            <div class="tm-login-links">
              <a href="index.php"><i class="fa fa-arrow-left"></i> Volver al login</a>
            </div>

            <div class="tm-login-alert-space">
              <?php ControladorUsuarios::ctrSolicitarRecuperacionPassword(); ?>
            </div>
          </form>

        <?php else: ?>

          <h2>Ingresar al sistema</h2>
          <p class="tm-subtitle">Use su usuario o correo electronico para continuar.</p>

          <form method="post">
            <div class="tm-field">
              <label>Usuario o correo</label>
              <div class="tm-input-wrap">
                <i class="fa fa-user"></i>
                <input type="text" class="form-control tm-input" name="ingUsuario" placeholder="usuario@empresa.com" required>
              </div>
            </div>

            <div class="tm-field">
              <label>Contrasena</label>
              <div class="tm-input-wrap">
                <i class="fa fa-lock"></i>
                <input type="password" class="form-control tm-input" name="ingPassword" required>
              </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block tm-btn">Ingresar</button>

            <div class="tm-login-links">
              <a href="index.php?recuperar=1"><i class="fa fa-question-circle"></i> Olvide mi contrasena</a>
            </div>

            <div class="tm-note">
              Cuando el administrador cree su usuario, recibira un enlace seguro en su correo para crear su contrasena personal.
            </div>

            <div class="tm-login-alert-space">
              <?php
                $login = new ControladorUsuarios();
                $login -> ctrIngresoUsuario();
              ?>
            </div>
          </form>

        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener("click", function(event){
  var boton = event.target.closest ? event.target.closest("[data-password-toggle]") : null;
  if(!boton){ return; }
  var input = document.getElementById(boton.getAttribute("data-password-toggle"));
  if(!input){ return; }
  var visible = input.type === "text";
  input.type = visible ? "password" : "text";
  boton.classList.toggle("is-visible", !visible);
  boton.setAttribute("aria-label", visible ? "Mostrar contrasena" : "Ocultar contrasena");
  boton.innerHTML = visible ? '<i class="fa fa-eye"></i>' : '<i class="fa fa-eye-slash"></i>';
});

(function(){
  var canvas = document.getElementById("tmLoginParticles");
  if(!canvas){ return; }
  var ctx = canvas.getContext("2d");
  var particles = [];
  var mouse = {x:null,y:null};
  var count = 94;

  function resize(){
    var ratio = window.devicePixelRatio || 1;
    canvas.width = Math.floor(window.innerWidth * ratio);
    canvas.height = Math.floor(window.innerHeight * ratio);
    canvas.style.width = window.innerWidth + "px";
    canvas.style.height = window.innerHeight + "px";
    ctx.setTransform(ratio,0,0,ratio,0,0);
  }

  function createParticles(){
    particles = [];
    for(var i = 0; i < count; i++){
      particles.push({
        x: Math.random() * window.innerWidth,
        y: Math.random() * window.innerHeight,
        vx: (Math.random() - .5) * 1.05,
        vy: (Math.random() - .5) * 1.05,
        r: Math.random() * 2.4 + 1.2
      });
    }
  }

  function draw(){
    ctx.clearRect(0,0,window.innerWidth,window.innerHeight);
    for(var i = 0; i < particles.length; i++){
      var p = particles[i];
      p.x += p.vx;
      p.y += p.vy;
      if(p.x < 0 || p.x > window.innerWidth){ p.vx *= -1; }
      if(p.y < 0 || p.y > window.innerHeight){ p.vy *= -1; }

      if(mouse.x !== null){
        var dx = p.x - mouse.x;
        var dy = p.y - mouse.y;
        var d = Math.sqrt(dx * dx + dy * dy);
        if(d < 135 && d > 0){
          p.x += (dx / d) * 2.2;
          p.y += (dy / d) * 2.2;
        }
      }

      ctx.beginPath();
      ctx.arc(p.x,p.y,p.r,0,Math.PI * 2);
      ctx.fillStyle = "rgba(171,219,255,.9)";
      ctx.fill();

      for(var j = i + 1; j < particles.length; j++){
        var q = particles[j];
        var lx = p.x - q.x;
        var ly = p.y - q.y;
        var ld = Math.sqrt(lx * lx + ly * ly);
        if(ld < 145){
          ctx.beginPath();
          ctx.moveTo(p.x,p.y);
          ctx.lineTo(q.x,q.y);
          ctx.strokeStyle = "rgba(113,190,255," + (1 - ld / 145) * .46 + ")";
          ctx.lineWidth = 1;
          ctx.stroke();
        }
      }
    }
    requestAnimationFrame(draw);
  }

  window.addEventListener("resize", function(){
    resize();
    createParticles();
  });
  window.addEventListener("mousemove", function(e){
    mouse.x = e.clientX;
    mouse.y = e.clientY;
  });
  window.addEventListener("mouseleave", function(){
    mouse.x = null;
    mouse.y = null;
  });

  resize();
  createParticles();
  draw();
})();
</script>
