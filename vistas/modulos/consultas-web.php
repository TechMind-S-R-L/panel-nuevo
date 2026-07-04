<?php
if(!ControladorWebConsultas::ctrPuedeResponder()){
  echo '<script>window.location="inicio";</script>';
  return;
}
ModeloWebConsultas::mdlAsegurarTablas();
ControladorWebConsultas::ctrProcesarBandeja();
$consultasWeb = ModeloWebConsultas::mdlConsultasBandeja();
$idConsultaSeleccionada = (int)($_GET["idConsulta"] ?? ($consultasWeb[0]["id"] ?? 0));
$consultaSeleccionada = $idConsultaSeleccionada ? ModeloWebConsultas::mdlConsultaPorId($idConsultaSeleccionada) : null;
$mensajesSeleccionados = array();
if($consultaSeleccionada){
  ModeloWebConsultas::mdlMarcarLeidosInterno($idConsultaSeleccionada);
  $mensajesSeleccionados = ModeloWebConsultas::mdlMensajesConsulta($idConsultaSeleccionada);
}
function tmConsultaEsc($valor){ return htmlspecialchars((string)($valor??""),ENT_QUOTES,"UTF-8"); }
?>
<div class="content-wrapper tm-consultas-admin">
<style>
.tm-consultas-admin{background:transparent!important}.tm-consulta-shell{display:grid;grid-template-columns:350px minmax(0,1fr);gap:15px;min-height:calc(100vh - 155px)}
.tm-consulta-inbox,.tm-consulta-chat{overflow:hidden;border:1px solid rgba(165,199,224,.65);border-radius:20px;background:rgba(255,255,255,.84);box-shadow:0 16px 38px rgba(30,81,120,.08)}
.tm-consulta-inbox-head{padding:18px;color:#fff;background:linear-gradient(135deg,#133a53,#258fbd)}.tm-consulta-inbox-head span{font-size:10px;font-weight:900;text-transform:uppercase;color:#c6ecff}.tm-consulta-inbox-head h2{margin:4px 0 0;font-size:21px;font-weight:900}
.tm-consulta-list{max-height:calc(100vh - 245px);overflow:auto;padding:9px}.tm-consulta-item{position:relative;display:grid;grid-template-columns:43px 1fr auto;gap:9px;align-items:center;padding:11px;margin-bottom:7px;border:1px solid #e0ebf3;border-radius:13px;color:#263d53;background:#fff}.tm-consulta-item:hover,.tm-consulta-item.active{text-decoration:none;border-color:#5faad2;background:#f1f9fd}.tm-consulta-avatar{display:flex;align-items:center;justify-content:center;width:43px;height:43px;border-radius:50%;color:#fff;background:linear-gradient(135deg,#5eaed7,#6786ee);font-weight:900}.tm-consulta-item strong,.tm-consulta-item small,.tm-consulta-item em{display:block}.tm-consulta-item small{color:#7a8c9e;font-size:10px;margin-top:2px}.tm-consulta-item em{max-width:190px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#536c82;font-size:11px;font-style:normal;margin-top:4px}.tm-consulta-badge{min-width:22px;padding:3px 6px;border-radius:999px;color:#fff;background:#ec5b55;text-align:center;font-size:10px;font-weight:900}.tm-consulta-state{font-size:9px;font-weight:900;text-transform:uppercase;color:#4589ad}
.tm-consulta-chat{display:flex;flex-direction:column}.tm-consulta-chat-head{display:flex;justify-content:space-between;align-items:center;gap:14px;padding:16px 18px;border-bottom:1px solid #dfebf4;background:rgba(249,253,255,.94)}.tm-consulta-client{display:flex;align-items:center;gap:11px}.tm-consulta-client strong,.tm-consulta-client span{display:block}.tm-consulta-client strong{font-size:16px;color:#18324a}.tm-consulta-client span{color:#72869a;font-size:11px}.tm-consulta-actions{display:flex;gap:7px}.tm-consulta-actions .btn{border-radius:10px;font-weight:800}
.tm-consulta-messages{flex:1;min-height:390px;max-height:calc(100vh - 330px);overflow:auto;padding:22px;background:linear-gradient(145deg,#f5fbfe,#fff)}.tm-admin-message{display:flex;flex-direction:column;max-width:72%;margin-bottom:14px}.tm-admin-message>div{padding:11px 14px;border-radius:15px;line-height:1.45}.tm-admin-message small{margin-top:4px;color:#8292a3;font-size:10px}.tm-admin-message.cliente{align-items:flex-start}.tm-admin-message.cliente>div{border:1px solid #dce8f2;border-bottom-left-radius:4px;background:#fff;color:#294056}.tm-admin-message.usuario{margin-left:auto;align-items:flex-end}.tm-admin-message.usuario>div{border-bottom-right-radius:4px;color:#fff;background:linear-gradient(135deg,#308ab9,#617ee9)}.tm-consulta-compose{display:grid;grid-template-columns:1fr auto;gap:10px;align-items:end;padding:14px;border-top:1px solid #dfeaf3;background:#fff}.tm-consulta-compose textarea{resize:none;border-radius:13px;border-color:#d7e5ef}.tm-consulta-compose .btn{height:46px;border-radius:12px;font-weight:900}.tm-consulta-empty{display:flex;flex:1;flex-direction:column;align-items:center;justify-content:center;color:#74889c;text-align:center}.tm-consulta-empty i{font-size:48px;color:#73afd0}
@media(max-width:900px){.tm-consulta-shell{grid-template-columns:1fr}.tm-consulta-list{max-height:330px}.tm-consulta-messages{max-height:520px}}
</style>
<section class="content-header"><h1>Consultas Web</h1><ol class="breadcrumb"><li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li><li class="active">Consultas Web</li></ol></section>
<section class="content">
  <div class="tm-consulta-shell">
    <aside class="tm-consulta-inbox">
      <div class="tm-consulta-inbox-head"><span><i class="fa fa-comments"></i> Atención al cliente</span><h2>Bandeja de consultas</h2></div>
      <div class="tm-consulta-list">
        <?php if(!$consultasWeb): ?><div class="text-center text-muted" style="padding:35px 15px"><i class="fa fa-inbox fa-3x"></i><p>Aún no existen consultas.</p></div><?php endif; ?>
        <?php foreach($consultasWeb as $consulta): ?>
          <a class="tm-consulta-item <?php echo (int)$consulta["id"]===$idConsultaSeleccionada?"active":""; ?>" href="consultas-web?idConsulta=<?php echo (int)$consulta["id"]; ?>">
            <span class="tm-consulta-avatar"><?php echo tmConsultaEsc(mb_strtoupper(mb_substr($consulta["cliente"],0,1))); ?></span>
            <span><strong><?php echo tmConsultaEsc($consulta["cliente"]); ?></strong><small><?php echo tmConsultaEsc($consulta["asunto"]); ?></small><em><?php echo tmConsultaEsc($consulta["ultimo_mensaje"]); ?></em></span>
            <span><?php if((int)$consulta["no_leidos"]>0): ?><b class="tm-consulta-badge"><?php echo (int)$consulta["no_leidos"]; ?></b><?php endif; ?><small class="tm-consulta-state"><?php echo tmConsultaEsc(str_replace("_"," ",$consulta["estado"])); ?></small></span>
          </a>
        <?php endforeach; ?>
      </div>
    </aside>
    <section class="tm-consulta-chat">
      <?php if($consultaSeleccionada): ?>
        <div class="tm-consulta-chat-head">
          <div class="tm-consulta-client"><span class="tm-consulta-avatar"><?php echo tmConsultaEsc(mb_strtoupper(mb_substr($consultaSeleccionada["cliente"],0,1))); ?></span><span><strong><?php echo tmConsultaEsc($consultaSeleccionada["cliente"]); ?></strong><span><?php echo tmConsultaEsc($consultaSeleccionada["email"]); ?> · <?php echo tmConsultaEsc($consultaSeleccionada["telefono"]); ?><br>Asesor: <?php echo tmConsultaEsc($consultaSeleccionada["asesor"] ?: "Sin asignar"); ?></span></span></div>
          <div class="tm-consulta-actions">
            <?php if($consultaSeleccionada["estado"]!=="cerrada"): ?><a class="btn btn-default" href="index.php?ruta=consultas-web&idConsultaWeb=<?php echo $idConsultaSeleccionada; ?>&estadoConsultaWeb=cerrada"><i class="fa fa-check"></i> Cerrar consulta</a><?php else: ?><a class="btn btn-info" href="index.php?ruta=consultas-web&idConsultaWeb=<?php echo $idConsultaSeleccionada; ?>&estadoConsultaWeb=abierta"><i class="fa fa-refresh"></i> Reabrir</a><?php endif; ?>
          </div>
        </div>
        <div class="tm-consulta-messages" id="tmConsultaMensajes">
          <?php foreach($mensajesSeleccionados as $mensaje): ?>
            <div class="tm-admin-message <?php echo $mensaje["emisor"]==="cliente"?"cliente":"usuario"; ?>"><div><?php echo nl2br(tmConsultaEsc($mensaje["mensaje"])); ?></div><small><?php echo $mensaje["emisor"]==="cliente"?tmConsultaEsc($consultaSeleccionada["cliente"]):tmConsultaEsc($mensaje["usuario_nombre"] ?: "TechMind"); ?> · <?php echo date("d/m/Y H:i",strtotime($mensaje["fecha"])); ?></small></div>
          <?php endforeach; ?>
        </div>
        <form method="post" class="tm-consulta-compose" id="formConsultaWebAdmin">
          <input type="hidden" name="responderConsultaWeb" value="1"><input type="hidden" name="idConsultaWeb" value="<?php echo $idConsultaSeleccionada; ?>">
          <textarea class="form-control" name="mensajeConsultaWeb" rows="3" maxlength="3000" placeholder="Escribe la respuesta para el cliente..." required></textarea>
          <button class="btn btn-primary" type="submit"><i class="fa fa-send"></i> Enviar respuesta</button>
        </form>
      <?php else: ?>
        <div class="tm-consulta-empty"><i class="fa fa-comments-o"></i><h3>Selecciona una consulta</h3><p>Las conversaciones del portal cliente aparecerán aquí.</p></div>
      <?php endif; ?>
    </section>
  </div>
</section>
</div>
<script>
(function(){
  var idConsulta = <?php echo (int)$idConsultaSeleccionada; ?>;
  var firmaChat = null;
  var consultando = false;
  var chat = document.getElementById("tmConsultaMensajes");
  var form = document.getElementById("formConsultaWebAdmin");
  var lista = document.querySelector(".tm-consulta-list");

  function esc(valor){
    var div = document.createElement("div");
    div.textContent = valor == null ? "" : String(valor);
    return div.innerHTML;
  }

  function pintarMensajes(mensajes){
    if(!chat) return;
    var estabaAbajo = chat.scrollTop + chat.clientHeight >= chat.scrollHeight - 80;
    if(!mensajes || !mensajes.length){
      chat.innerHTML = '<div class="text-center text-muted" style="padding:35px 15px">Sin mensajes.</div>';
      return;
    }
    chat.innerHTML = mensajes.map(function(m){
      var clase = m.emisor === "cliente" ? "cliente" : "usuario";
      return '<div class="tm-admin-message '+clase+'"><div>'+esc(m.mensaje).replace(/\n/g,"<br>")+'</div><small>'+esc(m.autor)+' · '+esc(m.fecha)+'</small></div>';
    }).join("");
    if(estabaAbajo){ chat.scrollTop = chat.scrollHeight; }
  }

  function pintarBandeja(items){
    if(!lista || !items) return;
    if(!items.length){
      lista.innerHTML = '<div class="text-center text-muted" style="padding:35px 15px"><i class="fa fa-inbox fa-3x"></i><p>Aun no existen consultas.</p></div>';
      return;
    }
    lista.innerHTML = items.map(function(c){
      var active = parseInt(c.id,10) === parseInt(idConsulta,10) ? " active" : "";
      var badge = parseInt(c.no_leidos,10) > 0 ? '<b class="tm-consulta-badge">'+esc(c.no_leidos)+'</b>' : "";
      var inicial = (c.cliente || "?").charAt(0).toUpperCase();
      return '<a class="tm-consulta-item'+active+'" href="consultas-web?idConsulta='+encodeURIComponent(c.id)+'">' +
        '<span class="tm-consulta-avatar">'+esc(inicial)+'</span>' +
        '<span><strong>'+esc(c.cliente)+'</strong><small>'+esc(c.asunto)+'</small><em>'+esc(c.ultimo_mensaje)+'</em></span>' +
        '<span>'+badge+'<small class="tm-consulta-state">'+esc(String(c.estado || "").replace(/_/g," "))+'</small></span></a>';
    }).join("");
  }

  function consultar(){
    if(!idConsulta || consultando || document.hidden) return;
    consultando = true;
    $.ajax({
      url:"ajax/consultas-web-tiempo-real.ajax.php",
      method:"GET",
      dataType:"json",
      cache:false,
      data:{accion:"estado",id_consulta:idConsulta}
    }).done(function(res){
      if(!res || !res.ok) return;
      if(res.firma !== firmaChat){
        pintarMensajes(res.mensajes || []);
        pintarBandeja(res.bandeja || []);
        firmaChat = res.firma;
      }
    }).always(function(){ consultando = false; });
  }

  if(form){
    form.addEventListener("submit", function(e){
      e.preventDefault();
      var textarea = form.querySelector("[name='mensajeConsultaWeb']");
      var mensaje = textarea ? textarea.value.trim() : "";
      if(!mensaje) return;
      var boton = form.querySelector("button[type='submit']");
      if(boton) boton.disabled = true;
      $.ajax({
        url:"ajax/consultas-web-tiempo-real.ajax.php",
        method:"POST",
        dataType:"json",
        data:{accion:"responder",id_consulta:idConsulta,mensaje:mensaje}
      }).done(function(res){
        if(res && res.ok){
          if(textarea) textarea.value = "";
          pintarMensajes(res.mensajes || []);
          pintarBandeja(res.bandeja || []);
          firmaChat = res.firma;
        }
      }).always(function(){ if(boton) boton.disabled = false; });
    });
  }

  if(chat){ chat.scrollTop = chat.scrollHeight; }
  setInterval(consultar, 4000);
  document.addEventListener("visibilitychange", function(){ if(!document.hidden) consultar(); });
  $(document).on("techmind:consulta-web-nueva techmind:datos-actualizados", consultar);
  setTimeout(consultar, 800);
})();
</script>
