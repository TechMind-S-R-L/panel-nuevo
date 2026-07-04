(function(window, document, $){
  "use strict";
  if (!$ || !document.body.classList.contains("tm-admin-page")) return;

  var firmaAnterior = null;
  var eventosAnteriores = null;
  var primeraConsulta = true;
  var consultando = false;
  var intervalo = 7000;
  var toast = null;

  function enlaceRuta(ruta){
    return $(".sidebar-menu a").filter(function(){
      var href = ($(this).attr("href") || "").replace(/^\.?\//, "").split("?")[0];
      return href === ruta || (href === "index.php" && ($(this).attr("href") || "").indexOf("ruta="+ruta) !== -1);
    });
  }

  function actualizarBadge(ruta, cantidad){
    enlaceRuta(ruta).each(function(){
      var enlace = $(this);
      var badge = enlace.children(".tm-menu-badge");
      cantidad = parseInt(cantidad, 10) || 0;
      if (cantidad <= 0) {
        badge.remove();
        return;
      }
      var texto = cantidad > 99 ? "99+" : String(cantidad);
      if (!badge.length) {
        badge = $('<small class="label pull-right tm-menu-badge"></small>');
        enlace.append(badge);
      }
      badge.text(texto);
    });
  }

  function actualizarBadgesPadre(){
    $(".sidebar-menu li.treeview").each(function(){
      var item = $(this);
      var total = 0;
      item.children(".treeview-menu").find("a > .tm-menu-badge").each(function(){
        var texto = ($(this).text() || "").replace(/\D/g, "");
        total += parseInt(texto, 10) || 0;
      });
      var enlace = item.children("a").first();
      var badge = enlace.children(".tm-menu-badge");
      if (total <= 0) {
        badge.remove();
      } else {
        if (!badge.length) {
          badge = $('<small class="label pull-right tm-menu-badge"></small>');
          enlace.append(badge);
        }
        badge.text(total > 99 ? "99+" : String(total));
      }
    });
  }

  function mostrarAviso(titulo, detalle, ruta){
    if (toast) {
      clearTimeout(toast._timer);
      toast.remove();
    }
    toast = document.createElement("button");
    toast.type = "button";
    toast.className = "tm-live-toast";
    toast.innerHTML = '<i class="fa fa-bell"></i><span><strong>'+(titulo || "Hay actividad nueva")+'</strong><small>'+(detalle || "Los contadores del menu se actualizaron automaticamente.")+'</small></span><b>Ver</b>';
    toast.addEventListener("click", function(){
      toast.remove();
      toast = null;
      if (ruta) window.location.href = ruta;
    });
    document.body.appendChild(toast);
    requestAnimationFrame(function(){ toast.classList.add("show"); });
    toast._timer = setTimeout(function(){
      if (!toast) return;
      toast.classList.remove("show");
      setTimeout(function(){ if (toast) { toast.remove(); toast = null; } }, 250);
    }, 6500);
  }

  function refrescarTablasAjax(){
    if ($(".modal.in:visible").length || !$.fn.DataTable) return;
    $("table.dataTable").each(function(){
      try {
        var tabla = $(this).DataTable();
        var configuracion = tabla.settings()[0];
        if (configuracion && configuracion.ajax) {
          tabla.ajax.reload(null, false);
        }
      } catch (e) {}
    });
  }

  function revisarEventos(res){
    var eventos = res.eventos || {};
    var avisoMostrado = false;
    if (!eventosAnteriores) {
      eventosAnteriores = eventos;
      return false;
    }
    if ((parseInt(eventos.ultima_cotizacion_web, 10) || 0) > (parseInt(eventosAnteriores.ultima_cotizacion_web, 10) || 0)) {
      mostrarAviso("Nueva solicitud web", "Un cliente envio una nueva cotizacion desde la pagina.", "solicitudes-web");
      avisoMostrado = true;
    }
    if ((parseInt(eventos.ultimo_mensaje_cliente, 10) || 0) > (parseInt(eventosAnteriores.ultimo_mensaje_cliente, 10) || 0)) {
      mostrarAviso("Nuevo mensaje de cliente", "Hay una consulta nueva esperando respuesta.", "consultas-web");
      avisoMostrado = true;
      $(document).trigger("techmind:consulta-web-nueva", [res]);
    }
    eventosAnteriores = eventos;
    return avisoMostrado;
  }

  function consultar(){
    if (consultando || document.hidden) return;
    consultando = true;
    $.ajax({
      url: "ajax/estado-tiempo-real.ajax.php",
      method: "GET",
      dataType: "json",
      cache: false,
      timeout: 8000
    }).done(function(res){
      if (!res || !res.ok) return;
      document.body.setAttribute("data-live-admin", "connected");
      Object.keys(res.badges || {}).forEach(function(ruta){
        actualizarBadge(ruta, res.badges[ruta]);
      });
      actualizarBadgesPadre();
      if (!primeraConsulta && firmaAnterior && res.firma !== firmaAnterior) {
        if (!revisarEventos(res)) {
          mostrarAviso();
        }
        refrescarTablasAjax();
        $(document).trigger("techmind:datos-actualizados", [res]);
      }
      if (primeraConsulta) {
        eventosAnteriores = res.eventos || {};
      }
      firmaAnterior = res.firma;
      primeraConsulta = false;
    }).always(function(){
      consultando = false;
    });
  }

  var estilo = document.createElement("style");
  estilo.textContent =
    ".tm-live-toast{position:fixed;right:22px;bottom:24px;z-index:40000;display:flex;align-items:center;gap:11px;max-width:390px;padding:12px 14px;border:1px solid rgba(125,211,252,.55);border-radius:15px;background:linear-gradient(135deg,#123c68,#168fc2);color:#fff;box-shadow:0 18px 45px rgba(15,23,42,.26);text-align:left;opacity:0;transform:translateY(18px);transition:.22s ease}" +
    ".tm-live-toast.show{opacity:1;transform:translateY(0)}.tm-live-toast>i{width:38px;height:38px;border-radius:12px;background:rgba(255,255,255,.17);display:flex;align-items:center;justify-content:center;font-size:17px}" +
    ".tm-live-toast span{display:flex;flex-direction:column;min-width:0;flex:1}.tm-live-toast strong{font-size:13px}.tm-live-toast small{margin-top:2px;color:rgba(255,255,255,.78);line-height:1.25}.tm-live-toast>b{font-size:11px;background:#fff;color:#176b9b;border-radius:9px;padding:7px 9px}";
  document.head.appendChild(estilo);

  setInterval(consultar, intervalo);
  document.addEventListener("visibilitychange", function(){ if (!document.hidden) consultar(); });
  window.addEventListener("focus", consultar);
  setTimeout(consultar, 1200);
})(window, document, window.jQuery);
