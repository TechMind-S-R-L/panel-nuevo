/*=============================================
SideBar Menu
=============================================*/

$('.sidebar-menu').tree()

/*=============================================
Data Table
=============================================*/

$(".tablas").DataTable({
	"autoWidth": false,

	"language": {

		"sProcessing":     "Procesando...",
		"sLengthMenu":     "Mostrar _MENU_ registros",
		"sZeroRecords":    "No se encontraron resultados",
		"sEmptyTable":     "Ningún dato disponible en esta tabla",
		"sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_",
		"sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0",
		"sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
		"sInfoPostFix":    "",
		"sSearch":         "Buscar:",
		"sUrl":            "",
		"sInfoThousands":  ",",
		"sLoadingRecords": "Cargando...",
		"oPaginate": {
		"sFirst":    "Primero",
		"sLast":     "Último",
		"sNext":     "Siguiente",
		"sPrevious": "Anterior"
		},
		"oAria": {
			"sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
			"sSortDescending": ": Activar para ordenar la columna de manera descendente"
		}

	}

});

$(document).on("expanded.pushMenu collapsed.pushMenu", function(){
	setTimeout(function(){
		if($.fn.dataTable){
			$.fn.dataTable.tables({visible:true, api:true}).columns.adjust();
			if($.fn.dataTable.Responsive){
				$.fn.dataTable.tables({visible:true, api:true}).responsive.recalc();
			}
		}
	}, 350);
});

$(window).on("resize", function(){
	clearTimeout(window.techmindResizeTables);
	window.techmindResizeTables = setTimeout(function(){
		if($.fn.dataTable){
			$.fn.dataTable.tables({visible:true, api:true}).columns.adjust();
		}
	}, 200);
});

/*=============================================
DISEÑO GLOBAL DE TABLAS TECHMIND
=============================================*/

function inyectarEstiloTablasTechMind(){

	if(document.getElementById("tmFinalTableStyle")){
		return;
	}

	var css = [
		"body.tm-admin-page{--tm-table-font:'Segoe UI',Roboto,Arial,sans-serif;--tm-table-text:12.8px;--tm-table-head:11.8px;--tm-table-border:rgba(184,205,232,.78);}",
		"body.tm-admin-page .box-body,body.tm-admin-page .tab-pane,body.tm-admin-page .nav-tabs-custom,body.tm-admin-page .nav-tabs-custom .tab-content{overflow:visible!important;}",
		"body.tm-admin-page .tm-table-scroll,body.tm-admin-page .table-responsive,body.tm-admin-page .dataTables_wrapper{width:100%!important;max-width:100%!important;overflow-x:auto!important;overflow-y:visible!important;text-align:center!important;border-radius:14px!important;padding-bottom:6px!important;}",
		"body.tm-admin-page .tm-table-scroll::-webkit-scrollbar,body.tm-admin-page .table-responsive::-webkit-scrollbar,body.tm-admin-page .dataTables_wrapper::-webkit-scrollbar{height:8px!important;}",
		"body.tm-admin-page .tm-table-scroll::-webkit-scrollbar-thumb,body.tm-admin-page .table-responsive::-webkit-scrollbar-thumb,body.tm-admin-page .dataTables_wrapper::-webkit-scrollbar-thumb{background:rgba(49,108,190,.28)!important;border-radius:999px!important;}",
		"body.tm-admin-page .dataTables_wrapper .row{width:100%!important;margin:0!important;display:flex!important;flex-wrap:wrap!important;align-items:center!important;justify-content:space-between!important;gap:10px!important;}",
		"body.tm-admin-page .dataTables_wrapper .row>[class*='col-']{width:auto!important;max-width:100%!important;float:none!important;padding-left:0!important;padding-right:0!important;flex:0 1 auto!important;}",
		"body.tm-admin-page .dataTables_wrapper .dataTables_length,body.tm-admin-page .dataTables_wrapper .dataTables_filter{float:none!important;margin:4px 0 12px!important;text-align:left!important;font-family:var(--tm-table-font)!important;font-size:12.5px!important;font-weight:700!important;color:#334155!important;}",
		"body.tm-admin-page .dataTables_wrapper .dataTables_filter{margin-left:auto!important;text-align:right!important;}",
		"body.tm-admin-page .dataTables_wrapper .dataTables_length label,body.tm-admin-page .dataTables_wrapper .dataTables_filter label{display:inline-flex!important;align-items:center!important;gap:7px!important;margin:0!important;white-space:normal!important;}",
		"body.tm-admin-page .dataTables_wrapper .dataTables_length select{width:76px!important;min-width:76px!important;height:34px!important;border-radius:9px!important;}",
		"body.tm-admin-page .dataTables_wrapper .dataTables_filter input{width:clamp(170px,24vw,310px)!important;min-height:34px!important;border-radius:9px!important;}",
		"body.tm-admin-page table.tm-table-fit{width:max-content!important;min-width:100%!important;max-width:none!important;table-layout:auto!important;margin:0 auto!important;border-collapse:separate!important;border-spacing:0!important;border:1px solid var(--tm-table-border)!important;border-radius:14px!important;overflow:hidden!important;background:rgba(255,255,255,.58)!important;font-family:var(--tm-table-font)!important;box-shadow:0 14px 32px rgba(15,23,42,.05)!important;}",
		"body.tm-admin-page table.tm-table-fit th,body.tm-admin-page table.tm-table-fit td{box-sizing:border-box!important;height:auto!important;padding:9px 10px!important;white-space:normal!important;overflow:visible!important;text-overflow:clip!important;overflow-wrap:anywhere!important;word-break:normal!important;text-align:center!important;vertical-align:middle!important;border-right:1px solid rgba(205,218,236,.82)!important;border-bottom:1px solid rgba(218,228,240,.82)!important;font-family:var(--tm-table-font)!important;}",
		"body.tm-admin-page table.tm-table-fit th:last-child,body.tm-admin-page table.tm-table-fit td:last-child{border-right:0!important;}",
		"body.tm-admin-page table.tm-table-fit th{color:#174b86!important;background:rgba(232,241,255,.86)!important;background-image:none!important;font-size:var(--tm-table-head)!important;line-height:1.18!important;font-weight:900!important;letter-spacing:0!important;text-transform:uppercase!important;word-break:normal!important;overflow-wrap:break-word!important;}",
		"body.tm-admin-page table.tm-table-fit td{color:#1f2d42!important;font-size:var(--tm-table-text)!important;line-height:1.24!important;font-weight:650!important;}",
		"body.tm-admin-page table.tm-table-fit tbody tr:nth-child(odd){background:rgba(255,255,255,.58)!important;}",
		"body.tm-admin-page table.tm-table-fit tbody tr:nth-child(even){background:rgba(248,251,255,.54)!important;}",
		"body.tm-admin-page table.tm-table-fit tbody tr:hover{background:rgba(225,239,255,.76)!important;}",
		"body.tm-admin-page table.tm-table-fit th.sorting,body.tm-admin-page table.tm-table-fit th.sorting_asc,body.tm-admin-page table.tm-table-fit th.sorting_desc,body.tm-admin-page table.tm-table-fit th.sorting_disabled{background-image:none!important;padding-right:10px!important;}",
		"body.tm-admin-page table.tm-table-fit th.sorting:before,body.tm-admin-page table.tm-table-fit th.sorting:after,body.tm-admin-page table.tm-table-fit th.sorting_asc:before,body.tm-admin-page table.tm-table-fit th.sorting_asc:after,body.tm-admin-page table.tm-table-fit th.sorting_desc:before,body.tm-admin-page table.tm-table-fit th.sorting_desc:after{display:none!important;content:''!important;}",
		"body.tm-admin-page table.tm-table-fit .tm-col-index{width:44px!important;min-width:44px!important;max-width:44px!important;padding-left:4px!important;padding-right:4px!important;white-space:nowrap!important;overflow:hidden!important;}",
		"body.tm-admin-page table.tm-table-fit .tm-col-image{min-width:76px!important;}",
		"body.tm-admin-page table.tm-table-fit .tm-col-code{min-width:118px!important;}",
		"body.tm-admin-page table.tm-table-fit .tm-col-person,body.tm-admin-page table.tm-table-fit .tm-col-contact,body.tm-admin-page table.tm-table-fit .tm-col-md{min-width:150px!important;}",
		"body.tm-admin-page table.tm-table-fit .tm-col-date{min-width:132px!important;}",
		"body.tm-admin-page table.tm-table-fit .tm-col-money{min-width:122px!important;}",
		"body.tm-admin-page table.tm-table-fit .tm-col-status{min-width:155px!important;max-width:190px!important;overflow:hidden!important;padding-left:7px!important;padding-right:7px!important;}",
		"body.tm-admin-page table.tm-table-fit .tm-col-desc,body.tm-admin-page table.tm-table-fit .tm-col-lg,body.tm-admin-page table.tm-table-fit .tm-col-xl{min-width:230px!important;text-align:center!important;}",
		"body.tm-admin-page table.tm-table-fit .tm-col-actions,body.tm-admin-page table.tm-table-fit .acciones,body.tm-admin-page table.tm-table-fit .cliente-col-acciones{min-width:188px!important;max-width:230px!important;padding-left:7px!important;padding-right:7px!important;overflow:visible!important;white-space:normal!important;}",
		"body.tm-admin-page table.tm-table-fit td:has(.btn):not(.tm-col-status){min-width:188px!important;max-width:230px!important;padding-left:7px!important;padding-right:7px!important;overflow:visible!important;white-space:normal!important;}",
		"body.tm-admin-page table.tm-table-fit td.tm-col-status .label,body.tm-admin-page table.tm-table-fit td.tm-col-status .badge,body.tm-admin-page table.tm-table-fit td.tm-col-status .btn,body.tm-admin-page table.tm-table-fit td.tm-col-status span[class*='label'],body.tm-admin-page table.tm-table-fit td.tm-col-status span[class*='badge']{display:block!important;width:100%!important;max-width:100%!important;min-width:0!important;height:auto!important;margin:0 auto!important;padding:6px 7px!important;white-space:normal!important;overflow:hidden!important;overflow-wrap:anywhere!important;word-break:break-word!important;text-overflow:clip!important;line-height:1.14!important;font-size:10.4px!important;font-weight:850!important;border-radius:9px!important;text-align:center!important;box-sizing:border-box!important;}",
		"body.tm-admin-page table.tm-table-fit .tm-col-actions .btn-group,body.tm-admin-page table.tm-table-fit .acciones .btn-group,body.tm-admin-page table.tm-table-fit .cliente-col-acciones .btn-group{display:flex!important;flex-wrap:wrap!important;align-items:center!important;justify-content:center!important;gap:5px!important;width:100%!important;max-width:100%!important;margin:0 auto!important;float:none!important;}",
		"body.tm-admin-page table.tm-table-fit td:has(.btn):not(.tm-col-status) .btn-group{display:flex!important;flex-wrap:wrap!important;align-items:center!important;justify-content:center!important;gap:5px!important;width:100%!important;max-width:100%!important;margin:0 auto!important;float:none!important;}",
		"body.tm-admin-page table.tm-table-fit .tm-col-actions .btn,body.tm-admin-page table.tm-table-fit .acciones .btn,body.tm-admin-page table.tm-table-fit .cliente-col-acciones .btn,body.tm-admin-page table.tm-table-fit td:not(.tm-col-status) a.btn,body.tm-admin-page table.tm-table-fit td:not(.tm-col-status) button.btn{display:inline-flex!important;align-items:center!important;justify-content:center!important;width:auto!important;min-width:34px!important;max-width:100%!important;min-height:32px!important;margin:1px!important;padding:6px 9px!important;white-space:normal!important;overflow:visible!important;overflow-wrap:anywhere!important;word-break:break-word!important;text-overflow:clip!important;line-height:1.08!important;font-size:11.4px!important;font-weight:850!important;text-align:center!important;border-radius:9px!important;box-sizing:border-box!important;box-shadow:0 5px 12px rgba(15,23,42,.08)!important;transition:transform .16s ease,box-shadow .16s ease,filter .16s ease!important;}",
		"body.tm-admin-page table.tm-table-fit .tm-col-actions .btn:hover{transform:translateY(-1px)!important;box-shadow:0 8px 16px rgba(15,23,42,.14)!important;filter:saturate(1.08)!important;}",
		"body.tm-admin-page table.tm-table-fit img{display:block!important;width:auto!important;max-width:48px!important;max-height:48px!important;object-fit:contain!important;margin:0 auto!important;}",
		"body.tm-admin-page .dataTables_info,body.tm-admin-page .dataTables_paginate{float:none!important;margin-top:12px!important;font-family:var(--tm-table-font)!important;font-size:12px!important;font-weight:700!important;color:#61718b!important;}",
		"body.tm-admin-page .dataTables_paginate{margin-left:auto!important;text-align:right!important;}",
		"body.tm-admin-page .dataTables_paginate .paginate_button a{border-radius:9px!important;font-weight:800!important;}",
		"body.tm-dark-mode .dataTables_wrapper .dataTables_length,body.tm-dark-mode .dataTables_wrapper .dataTables_filter,body.tm-dark-mode .dataTables_info{color:#dbeafe!important;}",
		"body.tm-dark-mode table.tm-table-fit{background:rgba(14,27,49,.50)!important;border-color:rgba(147,197,253,.25)!important;box-shadow:0 14px 32px rgba(0,0,0,.18)!important;}",
		"body.tm-dark-mode table.tm-table-fit th{color:#f8fbff!important;background:rgba(37,70,119,.68)!important;border-right-color:rgba(147,197,253,.25)!important;border-bottom-color:rgba(147,197,253,.28)!important;}",
		"body.tm-dark-mode table.tm-table-fit td{color:#edf5ff!important;border-right-color:rgba(147,197,253,.18)!important;border-bottom-color:rgba(147,197,253,.18)!important;}",
		"body.tm-dark-mode table.tm-table-fit tbody tr:nth-child(odd){background:rgba(15,25,45,.54)!important;}",
		"body.tm-dark-mode table.tm-table-fit tbody tr:nth-child(even){background:rgba(10,18,35,.44)!important;}",
		"body.tm-dark-mode table.tm-table-fit tbody tr:hover{background:rgba(66,133,244,.22)!important;}"
	].join("");

	var style = document.createElement("style");
	style.id = "tmFinalTableStyle";
	style.type = "text/css";
	style.appendChild(document.createTextNode(css));
	document.body.appendChild(style);
}

function normalizarTablasTechMind(){

	function fijarColumna($elementos, ancho, fija){
		$elementos.each(function(){
			this.style.setProperty("min-width", ancho, "important");
			this.style.setProperty("max-width", fija ? ancho : "none", "important");
			this.style.setProperty("width", fija ? ancho : "auto", "important");
		});
	}

	function limpiarAncho($elementos){
		$elementos.each(function(){
			this.style.removeProperty("width");
			this.style.removeProperty("min-width");
			this.style.removeProperty("max-width");
			this.style.removeProperty("white-space");
			this.style.removeProperty("overflow");
			this.style.removeProperty("text-overflow");
		});
	}

	function anchoAutomaticoCeldas($elementos){
		$elementos.each(function(){
			this.style.setProperty("width", "auto", "important");
			this.style.setProperty("min-width", "0", "important");
			this.style.setProperty("max-width", "none", "important");
		});
	}

	function normalizarEstadoCelda($celdas){
		$celdas.each(function(){
			this.style.setProperty("overflow", "hidden", "important");
			this.style.setProperty("text-align", "center", "important");
			this.style.setProperty("padding-left", "5px", "important");
			this.style.setProperty("padding-right", "5px", "important");
			$(this).find(".label, .badge, .btn, span[class*='label'], span[class*='badge']").each(function(){
				this.style.setProperty("display", "block", "important");
				this.style.setProperty("width", "100%", "important");
				this.style.setProperty("min-width", "0", "important");
				this.style.setProperty("max-width", "100%", "important");
				this.style.setProperty("height", "auto", "important");
				this.style.setProperty("margin", "0 auto", "important");
				this.style.setProperty("padding", "5px 4px", "important");
				this.style.setProperty("box-sizing", "border-box", "important");
				this.style.setProperty("white-space", "normal", "important");
				this.style.setProperty("overflow", "hidden", "important");
				this.style.setProperty("overflow-wrap", "anywhere", "important");
				this.style.setProperty("word-break", "break-word", "important");
				this.style.setProperty("text-overflow", "clip", "important");
				this.style.setProperty("line-height", "1.12", "important");
				this.style.setProperty("font-size", "10.4px", "important");
				this.style.setProperty("text-align", "center", "important");
				this.style.setProperty("border-radius", "8px", "important");
			});
		});
	}

	$("table.table, table.dataTable").each(function(){

		var $tabla = $(this);

		if(!$tabla.closest(".dataTables_wrapper, .table-responsive, .tm-table-scroll").length){
			$tabla.wrap('<div class="tm-table-scroll"></div>');
		}

		$tabla.addClass("tm-table-fit");
		$tabla.closest(".tm-table-scroll, .table-responsive, .dataTables_wrapper").addClass("tm-table-no-scroll");
		$tabla.find("colgroup col").removeAttr("width").removeAttr("style");
		limpiarAncho($tabla.find("th, td, .label, .badge, .btn, .btn-group"));

		var $ths = $tabla.find("thead th");
		if(!$ths.length){
			return;
		}

		$tabla.find("th, td").removeClass("tm-col-index tm-col-image tm-col-code tm-col-contact tm-col-person tm-col-status tm-col-money tm-col-date tm-col-desc tm-col-actions tm-col-xs tm-col-sm tm-col-md tm-col-lg tm-col-xl");

		$ths.each(function(index){

			var $th = $(this);
			var tituloOriginal = $.trim($th.clone().children().remove().end().text()).toLowerCase();
			if(tituloOriginal === ""){
				tituloOriginal = $.trim($th.text()).toLowerCase();
			}
			var titulo = tituloOriginal.normalize ? tituloOriginal.normalize("NFD").replace(/[\u0300-\u036f]/g, "") : tituloOriginal;
			var tituloSimple = titulo.replace(/\s+/g, " ").trim();
			var tituloCompacto = titulo.replace(/[^a-z0-9#]/g, "");

			var clase = "";
			var $celdas = $tabla.find("tbody tr").map(function(){
				return $(this).children("td").eq(index).get(0);
			});
			var tieneBotones = $celdas.filter(function(){
				return $(this).find(".btn, button, a[class*='btn']").length > 0;
			}).length > 0;
			var maxTexto = titulo.length;
			$celdas.each(function(){
				var texto = $.trim($(this).text()).replace(/\s+/g, " ");
				if(texto.length > maxTexto){
					maxTexto = texto.length;
				}
			});

			var esEstado = tituloSimple.indexOf("estado") !== -1 || tituloSimple.indexOf("pago") !== -1 || tituloSimple.indexOf("despacho") !== -1;
			var esAcciones = tituloSimple.indexOf("accion") !== -1 || tituloCompacto.indexOf("acciones") !== -1;

			if(index === 0 && (tituloCompacto === "#" || tituloCompacto === "" || tituloCompacto === "n" || tituloCompacto === "nro" || tituloCompacto === "numero" || tituloCompacto === "num" || tituloCompacto === "fila")){
				clase = "tm-col-index";
			}else if(esAcciones || (tieneBotones && !esEstado)){
				clase = "tm-col-actions";
			}else if(tituloCompacto === "#" || tituloCompacto === "" || tituloCompacto === "n" || tituloCompacto === "nro" || tituloCompacto === "numero" || tituloCompacto === "num" || tituloCompacto === "fila"){
				clase = "tm-col-index";
			}else if(tituloSimple.indexOf("imagen") !== -1 || tituloSimple.indexOf("foto") !== -1){
				clase = "tm-col-image";
			}else if(tituloSimple.indexOf("codigo") !== -1 || tituloCompacto === "nro" || tituloSimple.indexOf("solicitud") !== -1){
				clase = "tm-col-code";
			}else if(tituloSimple.indexOf("telefono") !== -1 || tituloSimple.indexOf("contacto") !== -1 || tituloSimple.indexOf("correo") !== -1){
				clase = "tm-col-contact";
			}else if(esEstado){
				clase = "tm-col-status";
			}else if(tituloSimple.indexOf("total") !== -1 || tituloSimple.indexOf("precio") !== -1 || tituloSimple.indexOf("monto") !== -1 || tituloSimple.indexOf("valor") !== -1){
				clase = "tm-col-money";
			}else if(tituloSimple.indexOf("fecha") !== -1 || tituloSimple.indexOf("creacion") !== -1 || tituloSimple.indexOf("cobro") !== -1 || tituloSimple.indexOf("agregado") !== -1){
				clase = "tm-col-date";
			}else if(tituloSimple.indexOf("cliente") !== -1 || tituloSimple.indexOf("vendedor") !== -1 || tituloSimple.indexOf("cajero") !== -1 || tituloSimple.indexOf("tecnico") !== -1 || tituloSimple.indexOf("desarrollador") !== -1 || tituloSimple.indexOf("mensajero") !== -1 || tituloSimple.indexOf("usuario") !== -1 || tituloSimple.indexOf("promotor") !== -1){
				clase = "tm-col-person";
			}else if(tituloSimple.indexOf("descripcion") !== -1 || tituloSimple.indexOf("detalle") !== -1 || tituloSimple.indexOf("producto") !== -1 || tituloSimple.indexOf("productos") !== -1 || tituloSimple.indexOf("direccion") !== -1 || tituloSimple.indexOf("proceso") !== -1 || tituloSimple.indexOf("observacion") !== -1 || tituloSimple.indexOf("proyecto") !== -1 || tituloSimple.indexOf("servicio") !== -1){
				clase = "tm-col-desc";
			}else if(maxTexto <= 4){
				clase = "tm-col-xs";
			}else if(maxTexto <= 10){
				clase = "tm-col-sm";
			}else if(maxTexto <= 20){
				clase = "tm-col-md";
			}else if(maxTexto <= 42){
				clase = "tm-col-lg";
			}else{
				clase = "tm-col-xl";
			}

			if(clase !== ""){
				$th.addClass(clase);
				$celdas.addClass(clase);
			}
			if(tieneBotones && !esEstado){
				$th.add($celdas).addClass("tm-col-actions");
			}
			if(esEstado){
				$celdas.addClass("tm-col-status");
			}
			$th.add($celdas).removeAttr("width");
			limpiarAncho($th.add($celdas));
			anchoAutomaticoCeldas($th.add($celdas));
			$tabla.find("colgroup col").eq(index).removeAttr("width").css({
				width: "",
				minWidth: "",
				maxWidth: ""
			});

			if($th.hasClass("tm-col-index")){
				fijarColumna($th.add($celdas), "44px", true);
			}else if($th.hasClass("tm-col-image")){
				fijarColumna($th.add($celdas), "76px", false);
			}else if($th.hasClass("tm-col-actions")){
				fijarColumna($th.add($celdas), "188px", false);
			}else if($th.hasClass("tm-col-status")){
				fijarColumna($th.add($celdas), "155px", false);
				normalizarEstadoCelda($celdas);
			}else if($th.hasClass("tm-col-date")){
				fijarColumna($th.add($celdas), "132px", false);
			}else if($th.hasClass("tm-col-money")){
				fijarColumna($th.add($celdas), "122px", false);
			}else if($th.hasClass("tm-col-code")){
				fijarColumna($th.add($celdas), "118px", false);
			}else if($th.hasClass("tm-col-person")){
				fijarColumna($th.add($celdas), "150px", false);
			}else if($th.hasClass("tm-col-desc") || $th.hasClass("tm-col-lg") || $th.hasClass("tm-col-xl")){
				fijarColumna($th.add($celdas), "230px", false);
			}else if($th.hasClass("tm-col-xs")){
				fijarColumna($th.add($celdas), "66px", false);
			}else if($th.hasClass("tm-col-sm")){
				fijarColumna($th.add($celdas), "92px", false);
			}else if($th.hasClass("tm-col-md")){
				fijarColumna($th.add($celdas), "150px", false);
			}

		});

		this.style.setProperty("width", "max-content", "important");
		this.style.setProperty("min-width", "100%", "important");
		this.style.setProperty("max-width", "none", "important");
		this.style.setProperty("table-layout", "auto", "important");
		this.style.setProperty("margin-left", "auto", "important");
		this.style.setProperty("margin-right", "auto", "important");

		normalizarEstadoCelda($tabla.find("td.tm-col-status"));

	});

}

function descripcionBotonTechMind($boton){
	var texto = $.trim($boton.clone().children().remove().end().text()).replace(/\s+/g, " ");
	var clases = (($boton.attr("class") || "") + " " + ($boton.find("i").attr("class") || "")).toLowerCase();

	if(texto.length > 1){
		return texto;
	}
	if(clases.indexOf("imprimir") !== -1 || clases.indexOf("fa-print") !== -1){
		return "Imprimir";
	}
	if(clases.indexOf("contrato") !== -1 || clases.indexOf("fa-file-text") !== -1 || clases.indexOf("fa-file-text-o") !== -1){
		return "Ver documento";
	}
	if(clases.indexOf("ver") !== -1 || clases.indexOf("fa-eye") !== -1 || clases.indexOf("fa-search") !== -1){
		return "Ver detalle";
	}
	if(clases.indexOf("editar") !== -1 || clases.indexOf("fa-pencil") !== -1 || clases.indexOf("fa-edit") !== -1){
		return "Editar";
	}
	if(clases.indexOf("eliminar") !== -1 || clases.indexOf("fa-trash") !== -1 || clases.indexOf("fa-times") !== -1){
		return "Eliminar";
	}
	if(clases.indexOf("avance") !== -1 || clases.indexOf("fa-line-chart") !== -1){
		return "Registrar avance";
	}
	if(clases.indexOf("documento") !== -1 || clases.indexOf("upload") !== -1 || clases.indexOf("fa-upload") !== -1){
		return "Subir documento";
	}
	if(clases.indexOf("download") !== -1 || clases.indexOf("fa-download") !== -1){
		return "Descargar";
	}
	if(clases.indexOf("cobrar") !== -1 || clases.indexOf("caja") !== -1 || clases.indexOf("fa-money") !== -1 || clases.indexOf("fa-dollar") !== -1){
		return "Cobrar";
	}
	if(clases.indexOf("despacho") !== -1 || clases.indexOf("entregar") !== -1 || clases.indexOf("fa-truck") !== -1 || clases.indexOf("fa-check") !== -1){
		return "Entregar";
	}
	if(clases.indexOf("precio") !== -1 || clases.indexOf("fa-tag") !== -1 || clases.indexOf("fa-tags") !== -1){
		return "Poner precio";
	}
	if(clases.indexOf("agregar") !== -1 || clases.indexOf("fa-plus") !== -1){
		return "Agregar";
	}
	if(clases.indexOf("compra") !== -1 || clases.indexOf("fa-shopping-cart") !== -1){
		return "Gestionar compra";
	}
	if(clases.indexOf("map") !== -1 || clases.indexOf("ubicacion") !== -1 || clases.indexOf("fa-map-marker") !== -1){
		return "Ver ubicacion";
	}
	if(clases.indexOf("key") !== -1 || clases.indexOf("clave") !== -1 || clases.indexOf("fa-key") !== -1){
		return "Clave de acceso";
	}
	return "Ejecutar accion";
}

function activarTooltipsTablasTechMind(){
	$("table.table .btn, table.dataTable .btn").each(function(){
		var $boton = $(this);
		var titulo = $.trim($boton.attr("title") || $boton.attr("data-original-title") || "");
		if(!titulo){
			titulo = descripcionBotonTechMind($boton);
			$boton.attr("title", titulo);
		}
		$boton.attr("aria-label", titulo);
		$boton.attr("data-toggle", "tooltip");
		$boton.attr("data-container", "body");
		$boton.attr("data-placement", "top");
	});

	if($.fn.tooltip){
		$("table.table .btn[data-toggle='tooltip'], table.dataTable .btn[data-toggle='tooltip']").tooltip("destroy").tooltip({
			container: "body",
			placement: "top"
		});
	}
}

inyectarEstiloTablasTechMind();
normalizarTablasTechMind();
activarTooltipsTablasTechMind();
setTimeout(normalizarTablasTechMind, 250);
setTimeout(normalizarTablasTechMind, 800);
setTimeout(activarTooltipsTablasTechMind, 300);
setTimeout(activarTooltipsTablasTechMind, 900);

$(document).on("draw.dt shown.bs.tab", function(){
	setTimeout(function(){
		normalizarTablasTechMind();
		activarTooltipsTablasTechMind();
	}, 60);
});

$(document).ajaxComplete(function(){
	setTimeout(function(){
		normalizarTablasTechMind();
		activarTooltipsTablasTechMind();
	}, 80);
});

$(window).on("load", function(){
	normalizarTablasTechMind();
	activarTooltipsTablasTechMind();
	setTimeout(normalizarTablasTechMind, 500);
});

/*=============================================
TARJETAS Y MODALES GLOBALES PARA TABLAS
=============================================*/

function inyectarEstiloTarjetasProcesoTechMind(){
	if(document.getElementById("tmProcessCardsStyle")){
		return;
	}

	var css = [
		"body.tm-admin-page table.tm-cardified-source{display:none!important;}",
		"body.tm-admin-page .tm-process-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px;width:100%;margin:10px 0 12px;}",
		"body.tm-admin-page .tm-process-card{position:relative;min-height:150px;border:1px solid rgba(184,205,232,.78);border-radius:10px;background:rgba(255,255,255,.78);box-shadow:0 12px 28px rgba(15,35,55,.08);padding:12px 12px 12px 16px;color:#102b3b;cursor:pointer;overflow:hidden;transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease;}",
		"body.tm-admin-page .tm-process-card:before{content:'';position:absolute;left:0;top:0;bottom:0;width:7px;background:#3c8dbc;}",
		"body.tm-admin-page .tm-process-card:hover{transform:translateY(-2px);box-shadow:0 16px 34px rgba(15,35,55,.13);border-color:rgba(60,141,188,.46);}",
		"body.tm-admin-page .tm-process-card.tm-state-warning:before{background:#f39c12;}body.tm-admin-page .tm-process-card.tm-state-info:before{background:#00a7d0;}body.tm-admin-page .tm-process-card.tm-state-success:before{background:#00a65a;}body.tm-admin-page .tm-process-card.tm-state-danger:before{background:#dd4b39;}body.tm-admin-page .tm-process-card.tm-state-muted:before{background:#8aa1b2;}",
		"body.tm-admin-page .tm-process-top{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px;}",
		"body.tm-admin-page .tm-process-status{display:inline-flex;align-items:center;max-width:145px;min-height:23px;padding:4px 8px;border-radius:999px;background:#eef5fb;color:#1f5d86;font-size:11px;font-weight:900;line-height:1.12;overflow:hidden;overflow-wrap:anywhere;}",
		"body.tm-admin-page .tm-state-warning .tm-process-status{background:#fff4dd;color:#9a6400;}body.tm-admin-page .tm-state-info .tm-process-status{background:#e2f8ff;color:#007397;}body.tm-admin-page .tm-state-success .tm-process-status{background:#e4f8ee;color:#00733d;}body.tm-admin-page .tm-state-danger .tm-process-status{background:#ffe8e4;color:#b42d1d;}body.tm-admin-page .tm-state-muted .tm-process-status{background:#edf2f6;color:#667888;}",
		"body.tm-admin-page .tm-process-code{font-size:12px;font-weight:900;color:#204c6c;white-space:nowrap;}",
		"body.tm-admin-page .tm-process-title{font-size:15px;font-weight:900;line-height:1.25;color:#142b3d;margin:0 0 9px;overflow-wrap:anywhere;}",
		"body.tm-admin-page .tm-process-meta{display:grid;gap:4px;color:#64798a;font-size:12px;font-weight:750;}",
		"body.tm-admin-page .tm-process-meta span{display:flex;gap:6px;align-items:flex-start;min-width:0;overflow-wrap:anywhere;}",
		"body.tm-admin-page .tm-process-meta i{width:14px;color:#3c8dbc;margin-top:2px;}",
		"body.tm-admin-page .tm-process-amount{margin-top:9px;font-size:18px;font-weight:950;color:#15364c;}",
		"body.tm-admin-page .tm-process-hint{margin-top:5px;color:#7b91a0;font-size:11px;font-weight:800;}",
		"body.tm-admin-page .tm-process-empty{grid-column:1/-1;border:1px dashed #c8d7e2;border-radius:10px;padding:24px;text-align:center;color:#6b7f90;font-weight:850;background:rgba(248,251,253,.78);}",
		"body.tm-admin-page .tm-process-modal .modal-dialog{margin-top:42px;}",
		"body.tm-admin-page .tm-process-modal .modal-content{border:0;border-radius:12px;overflow:hidden;box-shadow:0 24px 70px rgba(13,35,52,.28);}",
		"body.tm-admin-page .tm-process-modal .modal-header{position:relative;color:#fff;border-bottom:0;padding:18px 22px;background:linear-gradient(135deg,#3c8dbc,#1f5d86);}",
		"body.tm-admin-page .tm-process-modal.tm-state-warning .modal-header{background:linear-gradient(135deg,#f39c12,#d98200);}body.tm-admin-page .tm-process-modal.tm-state-info .modal-header{background:linear-gradient(135deg,#00a7d0,#087da3);}body.tm-admin-page .tm-process-modal.tm-state-success .modal-header{background:linear-gradient(135deg,#00a65a,#087a46);}body.tm-admin-page .tm-process-modal.tm-state-danger .modal-header{background:linear-gradient(135deg,#dd4b39,#a92718);}body.tm-admin-page .tm-process-modal.tm-state-muted .modal-header{background:linear-gradient(135deg,#607d8b,#415865);}",
		"body.tm-admin-page .tm-process-modal .modal-header:after{content:'';position:absolute;right:-40px;top:-55px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.16);}",
		"body.tm-admin-page .tm-process-modal .close{position:relative;z-index:2;color:#fff;opacity:.9;text-shadow:none;font-size:28px;}",
		"body.tm-admin-page .tm-process-modal-title{display:flex;align-items:center;gap:12px;position:relative;z-index:1;}",
		"body.tm-admin-page .tm-process-modal-icon{width:46px;height:46px;border-radius:12px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.28);display:flex;align-items:center;justify-content:center;font-size:22px;}",
		"body.tm-admin-page .tm-process-modal-kicker{display:inline-block;margin-bottom:4px;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.04em;opacity:.92;}",
		"body.tm-admin-page .tm-process-modal .modal-title{font-size:22px;font-weight:950;}",
		"body.tm-admin-page .tm-process-modal .modal-body{background:#f5f8fb;padding:18px;}",
		"body.tm-admin-page .tm-process-detail-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-bottom:16px;}",
		"body.tm-admin-page .tm-process-detail-item{border:1px solid #dfeaf2;border-radius:10px;padding:12px;background:#fff;box-shadow:0 6px 16px rgba(22,49,64,.05);}",
		"body.tm-admin-page .tm-process-detail-item span{display:block;color:#6f8190;font-size:12px;font-weight:900;margin-bottom:4px;}",
		"body.tm-admin-page .tm-process-detail-item strong{display:block;color:#1f2d3d;font-size:14px;overflow-wrap:anywhere;}",
		"body.tm-admin-page .tm-process-actions{display:flex;flex-wrap:wrap;gap:8px;background:#fff;border:1px solid #dfeaf2;border-radius:10px;padding:12px;box-shadow:0 6px 16px rgba(22,49,64,.05);}",
		"body.tm-admin-page .tm-process-actions .btn{border-radius:6px;font-weight:850;padding:8px 12px;}",
		"body.tm-dark-mode .tm-process-card{background:rgba(15,27,48,.72);border-color:rgba(147,197,253,.18);color:#edf5ff;}body.tm-dark-mode .tm-process-title,body.tm-dark-mode .tm-process-amount{color:#fff;}body.tm-dark-mode .tm-process-code{color:#dbeafe;}body.tm-dark-mode .tm-process-meta,body.tm-dark-mode .tm-process-hint{color:#c8d8ef;}body.tm-dark-mode .tm-process-modal .modal-body{background:#0d1729;}body.tm-dark-mode .tm-process-detail-item,body.tm-dark-mode .tm-process-actions{background:rgba(15,27,48,.86);border-color:rgba(147,197,253,.18);}body.tm-dark-mode .tm-process-detail-item strong{color:#fff;}"
	].join("");

	var style = document.createElement("style");
	style.id = "tmProcessCardsStyle";
	style.type = "text/css";
	style.appendChild(document.createTextNode(css));
	document.body.appendChild(style);
}

function textoPlanoTechMind($el){
	return $.trim($("<div>").html($el.html() || "").text()).replace(/\s+/g, " ");
}

function claseEstadoProcesoTechMind(texto){
	texto = (texto || "").toLowerCase();
	if(texto.indexOf("rechaz") !== -1 || texto.indexOf("elimin") !== -1 || texto.indexOf("sin stock") !== -1 || texto.indexOf("vencid") !== -1){
		return "tm-state-danger";
	}
	if(texto.indexOf("pendiente") !== -1 || texto.indexOf("esper") !== -1 || texto.indexOf("proceso") !== -1 || texto.indexOf("solicit") !== -1){
		return "tm-state-warning";
	}
	if(texto.indexOf("aprob") !== -1 || texto.indexOf("cobrad") !== -1 || texto.indexOf("entreg") !== -1 || texto.indexOf("complet") !== -1 || texto.indexOf("activo") !== -1){
		return "tm-state-success";
	}
	if(texto.indexOf("asign") !== -1 || texto.indexOf("revision") !== -1 || texto.indexOf("cotiz") !== -1){
		return "tm-state-info";
	}
	return "tm-state-muted";
}

function indiceColumnaProceso($headers, patrones){
	var encontrado = -1;
	$headers.each(function(index){
		var texto = $.trim($(this).text()).toLowerCase();
		if(encontrado !== -1){
			return;
		}
		for(var i = 0; i < patrones.length; i++){
			if(texto.indexOf(patrones[i]) !== -1){
				encontrado = index;
				return;
			}
		}
	});
	return encontrado;
}

function etiquetaAccionProceso($boton){
	var titulo = $.trim($boton.attr("title") || $boton.attr("data-original-title") || $boton.text() || "");
	return titulo || descripcionBotonTechMind($boton);
}

function renderizarTarjetasTablaTechMind(tabla){
	var $tabla = $(tabla);

	var esCardificable = $tabla.is(".tablas, .tablaProductos, .tablaProductos1, .tablaCotizacion, .tablaCompras, .tablaVentas, .report-table");

	if(!esCardificable || $tabla.closest(".ventas-proceso-page").length || $tabla.hasClass("tm-no-cardify")){
		return;
	}

	var id = $tabla.attr("id");
	if(!id){
		id = "tmCardTable"+Math.random().toString(36).slice(2);
		$tabla.attr("id", id);
	}

	var $wrapper = $tabla.closest(".dataTables_wrapper");
	if(!$wrapper.length){
		$wrapper = $tabla.closest(".table-responsive");
	}
	if(!$wrapper.length){
		$wrapper = $tabla.parent();
	}

	var api = null;
	if($.fn.dataTable && $.fn.dataTable.isDataTable($tabla[0])){
		api = $tabla.DataTable();
	}

	var $grid = $wrapper.find(".tm-process-grid[data-source-table='"+id+"']");
	if(!$grid.length){
		$grid = $('<div class="tm-process-grid" data-source-table="'+id+'"></div>');
		var $rowTabla = $tabla.closest(".dataTables_wrapper").length ? $tabla.closest(".row") : $();
		if($rowTabla.length){
			$rowTabla.before($grid);
		}else{
			$tabla.before($grid);
		}
	}

	var $headers = $tabla.find("thead th");
	var idxAcciones = indiceColumnaProceso($headers, ["accion"]);
	var idxEstado = indiceColumnaProceso($headers, ["estado", "pago", "despacho"]);
	var idxTotal = indiceColumnaProceso($headers, ["total", "monto", "precio", "saldo"]);
	var idxCodigo = indiceColumnaProceso($headers, ["codigo", "código", "nota", "boleta", "nro", "#"]);
	var idxCliente = indiceColumnaProceso($headers, ["cliente", "usuario", "nombre", "proveedor", "solicitante", "proyecto", "servicio"]);

	var rows = api ? api.rows({page:"current", search:"applied"}).nodes().toArray() : $tabla.find("tbody tr").toArray();
	var html = "";
	var modales = "";

	rows.forEach(function(row, rowIndex){
		var $row = $(row);
		var $cells = $row.children("td");
		if(!$cells.length || $cells.hasClass("dataTables_empty")){
			return;
		}

		var estadoTexto = idxEstado >= 0 ? textoPlanoTechMind($cells.eq(idxEstado)) : "Detalle";
		var estadoClase = claseEstadoProcesoTechMind(estadoTexto);
		var codigoTexto = idxCodigo >= 0 ? textoPlanoTechMind($cells.eq(idxCodigo)) : "#"+(rowIndex + 1);
		var tituloTexto = idxCliente >= 0 ? textoPlanoTechMind($cells.eq(idxCliente)) : codigoTexto;
		var totalTexto = idxTotal >= 0 ? textoPlanoTechMind($cells.eq(idxTotal)) : "";
		var modalId = id+"Modal"+rowIndex;

		var meta = "";
		$headers.each(function(i){
			if(i === idxAcciones || i === idxCliente || i === idxCodigo || i === idxTotal || i === idxEstado){
				return;
			}
			if(meta.split("<span").length > 4){
				return;
			}
			var valor = textoPlanoTechMind($cells.eq(i));
			if(valor){
				meta += '<span><i class="fa fa-info-circle"></i>'+valor+'</span>';
			}
		});

		html += '<div class="tm-process-card '+estadoClase+'" data-toggle="modal" data-target="#'+modalId+'">'+
			'<div class="tm-process-top"><span class="tm-process-status">'+(estadoTexto || "Detalle")+'</span><strong class="tm-process-code">'+codigoTexto+'</strong></div>'+
			'<h4 class="tm-process-title">'+tituloTexto+'</h4>'+
			'<div class="tm-process-meta">'+meta+'</div>'+
			(totalTexto ? '<div class="tm-process-amount">'+totalTexto+'</div>' : '')+
			'<div class="tm-process-hint">Clic para ver detalle y acciones</div>'+
		'</div>';

		var detalle = "";
		$headers.each(function(i){
			if(i === idxAcciones){
				return;
			}
			var label = textoPlanoTechMind($(this)) || "Detalle";
			var valorHtml = $cells.eq(i).html() || "";
			detalle += '<div class="tm-process-detail-item"><span>'+label+'</span><strong>'+valorHtml+'</strong></div>';
		});

		var acciones = "";
		if(idxAcciones >= 0){
			$cells.eq(idxAcciones).find("button,a").each(function(actionIndex){
				var $original = $(this);
				var $clon = $original.clone(false);
				$clon.addClass("tm-card-action-clone");
				$clon.attr("data-source-table", id);
				$clon.attr("data-source-row", rowIndex);
				$clon.attr("data-source-action", actionIndex);
				$clon.attr("title", etiquetaAccionProceso($original));
				acciones += $("<div>").append($clon).html();
			});
		}

		modales += '<div class="modal fade tm-process-modal '+estadoClase+'" id="'+modalId+'" tabindex="-1" role="dialog">'+
			'<div class="modal-dialog modal-lg" role="document"><div class="modal-content">'+
				'<div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>'+
					'<div class="tm-process-modal-title"><div class="tm-process-modal-icon"><i class="fa fa-folder-open"></i></div><div><span class="tm-process-modal-kicker">'+(estadoTexto || "Detalle")+'</span><h4 class="modal-title">'+tituloTexto+'</h4></div></div>'+
				'</div>'+
				'<div class="modal-body"><div class="tm-process-detail-grid">'+detalle+'</div>'+
					(acciones ? '<h4 class="venta-modal-subtitle"><i class="fa fa-bolt"></i> Acciones</h4><div class="tm-process-actions">'+acciones+'</div>' : '')+
				'</div>'+
			'</div></div>'+
		'</div>';
	});

	if(!html){
		html = '<div class="tm-process-empty">No hay registros para mostrar.</div>';
	}

	$grid.html(html);
	$("body").find(".tm-process-modal[data-source-table='"+id+"']").remove();
	$(modales).attr("data-source-table", id).appendTo("body");
	$tabla.addClass("tm-cardified-source");
}

function activarTarjetasTablasTechMind(){
	inyectarEstiloTarjetasProcesoTechMind();
	$("table.tablas, table.tablaProductos, table.tablaProductos1, table.tablaCotizacion, table.tablaCompras, table.tablaVentas, table.report-table").each(function(){
		renderizarTarjetasTablaTechMind(this);
	});
}

$(document).on("click", ".tm-card-action-clone", function(e){
	e.preventDefault();
	e.stopPropagation();

	var $boton = $(this);
	var idTabla = $boton.attr("data-source-table");
	var rowIndex = parseInt($boton.attr("data-source-row"), 10);
	var actionIndex = parseInt($boton.attr("data-source-action"), 10);
	var $tabla = $("#"+idTabla);
	var api = ($.fn.dataTable && $.fn.dataTable.isDataTable($tabla[0])) ? $tabla.DataTable() : null;
	var row = api ? api.rows({page:"current", search:"applied"}).nodes().toArray()[rowIndex] : $tabla.find("tbody tr").get(rowIndex);
	var $original = $(row).find("button,a").eq(actionIndex);

	if($original.length){
		$original.trigger("click");
	}
});

activarTarjetasTablasTechMind();
setTimeout(activarTarjetasTablasTechMind, 350);
setTimeout(activarTarjetasTablasTechMind, 900);

$(document).on("draw.dt shown.bs.tab", function(){
	setTimeout(activarTarjetasTablasTechMind, 80);
});

$(document).ajaxComplete(function(){
	setTimeout(activarTarjetasTablasTechMind, 120);
});

/*=============================================
 //iCheck for checkbox and radio inputs
=============================================*/

$('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
  checkboxClass: 'icheckbox_minimal-blue',
  radioClass   : 'iradio_minimal-blue'
})

/*=============================================
 //input Mask
=============================================*/

//Datemask dd/mm/yyyy
$('#datemask').inputmask('dd/mm/yyyy', { 'placeholder': 'dd/mm/yyyy' })
//Datemask2 mm/dd/yyyy
$('#datemask2').inputmask('mm/dd/yyyy', { 'placeholder': 'mm/dd/yyyy' })
//Money Euro
$('[data-mask]').inputmask()

/*=============================================
CORRECCIÓN BOTONERAS OCULTAS BACKEND	
=============================================*/

if(window.matchMedia("(max-width:767px)").matches){
	
	$("body").removeClass('sidebar-collapse');

}else{

	$("body").addClass('sidebar-collapse');
}
