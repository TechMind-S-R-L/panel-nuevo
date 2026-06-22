<?php

class ControladorProyectos{

	static public function ctrBuscarDesarrolladorLibre(){
		return ModeloProyectos::mdlBuscarDesarrolladorLibre();
	}

	static public function ctrMostrarProyectoSoftware($item = null, $valor = null){
		return ModeloProyectos::mdlMostrarProyectoSoftware($item, $valor);
	}

	static public function ctrMostrarProyectoPorServicio($idServicio){
		return ModeloProyectos::mdlMostrarProyectoPorServicio($idServicio);
	}

	static public function ctrMostrarProyectosUsuario(){
		if(($_SESSION["perfil"] ?? "") == "Administrador"){
			return ModeloProyectos::mdlMostrarProyectoSoftware();
		}
		return ModeloProyectos::mdlMostrarProyectosDesarrollador((int)$_SESSION["id"]);
	}

	static public function ctrGuardarAvanceProyecto(){
		if(!isset($_POST["guardarAvanceProyecto"])){
			return;
		}
		if(($_SESSION["perfil"] ?? "") != "Administrador" && ($_SESSION["rol"] ?? "") != "desarrollador"){
			echo '<script>window.location = "inicio";</script>';
			return;
		}
		$proyecto = ModeloProyectos::mdlMostrarProyectoSoftware("id", (int)$_POST["idProyectoAvance"]);
		if(!$proyecto || (($_SESSION["perfil"] ?? "") != "Administrador" && (int)$proyecto["id_desarrollador"] != (int)$_SESSION["id"])){
			echo '<script>window.location = "proyectos";</script>';
			return;
		}
		$porcentaje = max(0, min(100, (int)$_POST["porcentajeAvanceProyecto"]));
		$estado = $_POST["estadoAvanceProyecto"] ?? "en_desarrollo";
		if(!in_array($estado, array("en_desarrollo", "revision_interna", "revision_cliente", "pendiente_pago_final"))){
			$estado = "en_desarrollo";
		}
		if($porcentaje >= 100){
			$estado = "pendiente_pago_final";
		}
		$detalleAvance = trim($_POST["descripcionAvanceProyecto"] ?? "");
		$bloqueosAvance = trim($_POST["bloqueosAvanceProyecto"] ?? "");
		$proximoPasoAvance = trim($_POST["proximoPasoAvanceProyecto"] ?? "");
		$urlDemoAvance = trim($_POST["urlDemoAvanceProyecto"] ?? "");
		$partesAvance = array();
		if($detalleAvance !== ""){
			$partesAvance[] = "Avance realizado: ".$detalleAvance;
		}
		if($bloqueosAvance !== ""){
			$partesAvance[] = "Bloqueos o riesgos: ".$bloqueosAvance;
		}
		if($proximoPasoAvance !== ""){
			$partesAvance[] = "Siguiente paso: ".$proximoPasoAvance;
		}
		if($urlDemoAvance !== ""){
			$partesAvance[] = "Demo / repositorio: ".$urlDemoAvance;
		}
		$descripcionFinalAvance = count($partesAvance) ? implode("\n\n", $partesAvance) : "";
		$respuesta = ModeloProyectos::mdlGuardarAvance(array(
			"id_proyecto" => (int)$_POST["idProyectoAvance"],
			"id_usuario" => (int)$_SESSION["id"],
			"porcentaje" => $porcentaje,
			"estado" => $estado,
			"descripcion" => $descripcionFinalAvance,
			"visible_cliente" => isset($_POST["visibleClienteAvanceProyecto"]) ? 1 : 0
		));
		if($respuesta == "ok"){
			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("avance", "proyectos_software", "Avance ".$porcentaje."% registrado en proyecto ".$proyecto["codigo"]);
			}
			echo '<script>swal({type:"success",title:"Avance guardado",confirmButtonText:"Cerrar"}).then(function(result){if(result.value){window.location="proyectos";}});</script>';
		}
	}

	static public function ctrGuardarDocumentoProyecto(){
		if(!isset($_POST["guardarDocumentoProyecto"])){
			return;
		}
		if(($_SESSION["perfil"] ?? "") != "Administrador" && ($_SESSION["rol"] ?? "") != "desarrollador"){
			echo '<script>window.location = "inicio";</script>';
			return;
		}
		$proyecto = ModeloProyectos::mdlMostrarProyectoSoftware("id", (int)$_POST["idProyectoDocumento"]);
		if(!$proyecto || (($_SESSION["perfil"] ?? "") != "Administrador" && (int)$proyecto["id_desarrollador"] != (int)$_SESSION["id"])){
			echo '<script>window.location = "proyectos";</script>';
			return;
		}
		$ruta = "";
		if(isset($_FILES["archivoProyecto"]) && $_FILES["archivoProyecto"]["error"] === UPLOAD_ERR_OK){
			$directorio = "vistas/documentos/proyectos/".$proyecto["id"];
			if(!is_dir($directorio)){
				mkdir($directorio, 0755, true);
			}
			$nombreSeguro = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES["archivoProyecto"]["name"]));
			$ruta = $directorio."/".date("YmdHis")."_".$nombreSeguro;
			move_uploaded_file($_FILES["archivoProyecto"]["tmp_name"], $ruta);
		}
		$respuesta = ModeloProyectos::mdlGuardarDocumento(array(
			"id_proyecto" => (int)$proyecto["id"],
			"id_usuario" => (int)$_SESSION["id"],
			"tipo_documento" => $_POST["tipoDocumentoProyecto"] ?? "Documento",
			"titulo" => $_POST["tituloDocumentoProyecto"] ?? "Documento",
			"archivo" => $ruta,
			"observacion" => $_POST["observacionDocumentoProyecto"] ?? "",
			"visible_cliente" => isset($_POST["visibleClienteDocumentoProyecto"]) ? 1 : 0
		));
		if($respuesta == "ok"){
			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("documento", "proyectos_software", "Documento agregado al proyecto ".$proyecto["codigo"]);
			}
			echo '<script>swal({type:"success",title:"Documento guardado",confirmButtonText:"Cerrar"}).then(function(result){if(result.value){window.location="proyectos";}});</script>';
		}
	}

	static public function ctrEntregarProyecto(){
		if(!isset($_GET["entregarProyectoSoftware"])){
			return;
		}
		if(($_SESSION["perfil"] ?? "") != "Administrador" && ($_SESSION["rol"] ?? "") != "vendedor"){
			echo '<script>window.location = "inicio";</script>';
			return;
		}
		$idProyecto = (int)$_GET["entregarProyectoSoftware"];
		$respuesta = ModeloProyectos::mdlMarcarEntregado($idProyecto);
		if($respuesta == "ok"){
			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("entrega", "proyectos_software", "Proyecto ".$idProyecto." entregado al cliente");
			}
			echo '<script>
				swal({type:"success",title:"Proyecto entregado y completado",confirmButtonText:"Cerrar"}).then(function(result){
					if(result.value){
						window.open("extensiones/tcpdf/pdf/acta-entrega-software.php?idProyecto='.$idProyecto.'", "_blank");
						window.location = "proyectos";
					}
				});
			</script>';
		}else{
			echo '<script>swal({type:"error",title:"Primero debe estar pagado el saldo final",confirmButtonText:"Cerrar"});</script>';
		}
	}
}
