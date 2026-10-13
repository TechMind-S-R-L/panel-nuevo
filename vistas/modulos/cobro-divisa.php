<?php // $prefijoDivisa identifica el formulario de venta o servicio. ?>
<div class="cobro-divisa" data-prefijo="<?php echo htmlspecialchars($prefijoDivisa, ENT_QUOTES, 'UTF-8'); ?>">
  <div class="form-group"><label>Moneda recibida</label>
    <select class="form-control divisa-moneda"><option value="BOB">Bolivianos (Bs)</option><option value="USD">Dolares (USD)</option></select>
  </div>
  <div class="divisa-usd" style="display:none">
    <div class="row">
      <div class="col-sm-6"><div class="form-group"><label>Monto recibido en dolares</label><div class="input-group"><span class="input-group-addon">USD</span><input type="number" class="form-control divisa-original" min="0.01" max="99999999.99" step="0.01" inputmode="decimal"></div></div></div>
      <div class="col-sm-6"><div class="form-group"><label>Tipo de cambio del dia</label><div class="input-group"><span class="input-group-addon">Bs/USD</span><input type="number" class="form-control divisa-tasa" min="0.000001" max="99999999.999999" step="0.000001" inputmode="decimal" placeholder="Ingrese la cotizacion acordada"></div></div></div>
    </div>
    <p class="help-block">Cotizacion ingresada por caja para el <?php echo (new DateTimeImmutable('now', new DateTimeZone('America/La_Paz')))->format('d/m/Y'); ?>. El equivalente en Bs se aplica al cobro; el cambio se entrega en Bs.</p>
  </div>
  <div class="alert alert-info divisa-resumen" aria-live="polite">Aplicado: Bs <strong class="divisa-aplicado">0.00</strong> · Saldo pendiente: Bs <strong class="divisa-saldo">0.00</strong></div>
</div>
