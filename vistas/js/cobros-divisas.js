/* Conversion visible y envio POST. El servidor vuelve a validar y calcular cada importe. */
window.TMCobro = (function($){
  var formularios = {};
  function redondear(n){ return Math.round((n + Number.EPSILON) * 100) / 100; }
  function iniciar(clave, opciones){
    var $divisa = $('.cobro-divisa[data-prefijo="' + clave + '"]');
    formularios[clave] = $.extend({$divisa:$divisa}, opciones);
    $divisa.on('input change', 'input,select', function(){ calcular(clave); });
    $(opciones.metodo).on('change', function(){ calcular(clave); });
  }
  function reiniciar(clave){
    var f = formularios[clave];
    f.$divisa.find('.divisa-moneda').val('BOB');
    f.$divisa.find('.divisa-original,.divisa-tasa').val('');
    calcular(clave);
  }
  function calcular(clave){
    var f = formularios[clave];
    if(!f){ return; }
    var usd = f.$divisa.find('.divisa-moneda').val() === 'USD';
    var parcial = f.parcial && $(f.parcial).val() === '1';
    f.$divisa.find('.divisa-usd').toggle(usd);
    $(f.recibido).prop('readonly',usd);
    $(f.recibido).closest('.form-group').toggle(usd || parcial || $(f.metodo).val() === 'Efectivo');
    if(usd){
      var original = redondear(Number(f.$divisa.find('.divisa-original').val()) || 0);
      var tasa = Math.round((Number(f.$divisa.find('.divisa-tasa').val()) || 0) * 1000000) / 1000000;
      $(f.recibido).val(redondear(original * tasa).toFixed(2));
      $(f.recibido).closest('.form-group').show();
    }
    var total = Number($(f.total).val()) || 0;
    var recibido = Number($(f.recibido).val()) || 0;
    var aplicado = Math.min(Math.max(0,recibido),total);
    $(f.cambio).val(redondear(Math.max(0,recibido-total)).toFixed(2));
    f.$divisa.find('.divisa-aplicado').text(aplicado.toFixed(2));
    f.$divisa.find('.divisa-saldo').text(redondear(Math.max(0,total-aplicado)).toFixed(2));
    f.$divisa.find('.divisa-resumen').toggle(usd || parcial);
  }
  function enviar(clave,datos){
    var f = formularios[clave];
    calcular(clave);
    var moneda = f.$divisa.find('.divisa-moneda').val();
    var original = redondear(moneda === 'USD' ? Number(f.$divisa.find('.divisa-original').val()) : Number($(f.recibido).val()));
    var tasa = moneda === 'USD' ? Math.round(Number(f.$divisa.find('.divisa-tasa').val()) * 1000000) / 1000000 : 1;
    var recibido = redondear(original * tasa);
    var total = Number($(f.total).val());
    var parcial = f.parcial && $(f.parcial).val() === '1';
    if(!Number.isFinite(original) || !Number.isFinite(tasa) || original <= 0 || tasa <= 0 || (!parcial && recibido < total)){
      swal({type:'error',title:'Revise el importe y el tipo de cambio',text:'El pago debe ser positivo y cubrir el total, salvo las amortizaciones de software.',confirmButtonText:'Cerrar'});
      return false;
    }
    datos = $.extend({},datos,{registrarCobro:1,csrf_cobros:f.csrf,solicitud:f.solicitud,moneda:moneda,monto_original:original,tipo_cambio:tasa,fecha_cambio:f.fecha,metodo_pago:$(f.metodo).val()});
    var $form = $('<form>',{method:'post',action:f.ruta});
    Object.keys(datos).forEach(function(k){ $('<input>',{type:'hidden',name:k,value:datos[k]}).appendTo($form); });
    $(f.boton).prop('disabled',true);
    $form.appendTo(document.body)[0].submit();
    return true;
  }
  return {iniciar:iniciar,reiniciar:reiniciar,calcular:calcular,enviar:enviar};
})(jQuery);
