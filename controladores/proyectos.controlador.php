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
			$permitidos = array(
				"application/pdf" => "pdf",
				"image/jpeg" => "jpg",
				"image/png" => "png",
				"image/webp" => "webp",
				"image/gif" => "gif",
				"video/mp4" => "mp4",
				"video/webm" => "webm",
				"video/quicktime" => "mov",
				"application/msword" => "doc",
				"application/vnd.openxmlformats-officedocument.wordprocessingml.document" => "docx",
				"application/vnd.ms-excel" => "xls",
				"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" => "xlsx",
				"application/zip" => "zip",
				"application/x-zip-compressed" => "zip",
				"application/x-rar" => "rar",
				"application/vnd.rar" => "rar",
				"application/octet-stream" => "bin"
			);
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			$mime = $finfo->file($_FILES["archivoProyecto"]["tmp_name"]);
			if(!isset($permitidos[$mime]) || (int)$_FILES["archivoProyecto"]["size"] > 25 * 1024 * 1024){
				echo '<script>swal({type:"error",title:"Archivo no valido",text:"Use PDF, imagen, video o documento de hasta 25 MB.",confirmButtonText:"Cerrar"});</script>';
				return;
			}

			$directorioRelativo = "vistas/documentos/proyectos/".$proyecto["id"];
			$directorio = dirname(__DIR__)."/".$directorioRelativo;
			if(!is_dir($directorio) && !mkdir($directorio, 0775, true)){
				echo '<script>swal({type:"error",title:"No se pudo guardar el documento",text:"No fue posible preparar la carpeta del proyecto.",confirmButtonText:"Cerrar"});</script>';
				return;
			}
			@chmod($directorio, 0775);
			if(!is_writable($directorio)){
				echo '<script>swal({type:"error",title:"No se pudo guardar el documento",text:"La carpeta de documentos del proyecto no tiene permisos de escritura.",confirmButtonText:"Cerrar"});</script>';
				return;
			}
			$nombreBase = pathinfo($_FILES["archivoProyecto"]["name"], PATHINFO_FILENAME);
			$nombreSeguro = preg_replace('/[^a-zA-Z0-9._-]/', '_', $nombreBase);
			$nombreSeguro = trim($nombreSeguro, "._-");
			if($nombreSeguro === ""){
				$nombreSeguro = "documento";
			}
			$nombreArchivo = date("YmdHis")."_".$nombreSeguro.".".$permitidos[$mime];
			$ruta = $directorioRelativo."/".$nombreArchivo;
			$rutaCompleta = $directorio."/".$nombreArchivo;
			if(!move_uploaded_file($_FILES["archivoProyecto"]["tmp_name"], $rutaCompleta)){
				echo '<script>swal({type:"error",title:"No se pudo guardar el documento",text:"El servidor no pudo mover el archivo cargado. Revise permisos de la carpeta del proyecto.",confirmButtonText:"Cerrar"});</script>';
				return;
			}
			@chmod($rutaCompleta, 0664);
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

	static public function ctrEliminarDocumentoProyecto(){
		if(!isset($_GET["eliminarDocumentoProyecto"])){
			return;
		}
		if(($_SESSION["perfil"] ?? "") != "Administrador" && ($_SESSION["rol"] ?? "") != "desarrollador"){
			echo '<script>window.location = "proyectos";</script>';
			return;
		}
		$idDocumento = (int)$_GET["eliminarDocumentoProyecto"];
		$documento = ModeloProyectos::mdlMostrarDocumento($idDocumento);
		if(!$documento || (($_SESSION["perfil"] ?? "") != "Administrador" && (int)$documento["id_desarrollador"] != (int)$_SESSION["id"])){
			echo '<script>swal({type:"error",title:"Sin permiso",text:"No puede eliminar este documento.",confirmButtonText:"Cerrar"}).then(function(){window.location="proyectos";});</script>';
			return;
		}
		$ruta = trim((string)($documento["archivo"] ?? ""));
		if($ruta !== ""){
			$rutaCompleta = dirname(__DIR__)."/".ltrim(str_replace("\\", "/", $ruta), "/");
			if(is_file($rutaCompleta)){
				@unlink($rutaCompleta);
			}
		}
		$respuesta = ModeloProyectos::mdlEliminarDocumento($idDocumento);
		if($respuesta == "ok"){
			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("eliminar_documento", "proyectos_software", "Documento eliminado del proyecto ".$documento["codigo_proyecto"]);
			}
			echo '<script>swal({type:"success",title:"Documento eliminado",text:"Ya puede volver a subirlo.",confirmButtonText:"Cerrar"}).then(function(){window.location="proyectos";});</script>';
		}else{
			echo '<script>swal({type:"error",title:"No se pudo eliminar",text:"Intente nuevamente.",confirmButtonText:"Cerrar"}).then(function(){window.location="proyectos";});</script>';
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

	static public function ctrEliminarProyectoSoftware(){
		if(!isset($_GET["eliminarProyectoSoftware"])){
			return;
		}
		if(($_SESSION["perfil"] ?? "") !== "Administrador"){
			echo '<script>swal({type:"error",title:"Sin permiso",text:"Solo el administrador puede eliminar proyectos.",confirmButtonText:"Cerrar"}).then(function(){window.location="proyectos";});</script>';
			return;
		}
		$idProyecto = (int)$_GET["eliminarProyectoSoftware"];
		$documentos = ModeloProyectos::mdlMostrarDocumentos($idProyecto);
		$respuesta = ModeloProyectos::mdlEliminarProyectoSoftware($idProyecto);

		if(is_array($respuesta) && ($respuesta["status"] ?? "") === "ok"){
			foreach($documentos as $documento){
				$ruta = trim((string)($documento["archivo"] ?? ""));
				if($ruta !== ""){
					$rutaCompleta = dirname(__DIR__)."/".ltrim(str_replace("\\", "/", $ruta), "/");
					if(is_file($rutaCompleta)){
						@unlink($rutaCompleta);
					}
				}
			}
			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("eliminar", "proyectos_software", "Proyecto ".$respuesta["proyecto"]["codigo"]." eliminado por administrador");
			}
			echo '<script>swal({type:"success",title:"Proyecto eliminado",confirmButtonText:"Cerrar"}).then(function(){window.location="proyectos";});</script>';
		}else{
			echo '<script>swal({type:"error",title:"No se pudo eliminar",text:"Verifique que el proyecto exista.",confirmButtonText:"Cerrar"}).then(function(){window.location="proyectos";});</script>';
		}
	}
}
