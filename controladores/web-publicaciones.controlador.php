<?php

class ControladorWebPublicaciones{

	static public function ctrPuedeAdministrar(){
		return ($_SESSION["perfil"] ?? "") === "Administrador"
			|| in_array($_SESSION["rol"] ?? "", array("vendedor", "desarrollador"), true);
	}

	static public function ctrProcesarAcciones(){
		if(!self::ctrPuedeAdministrar()){
			echo '<script>window.location="inicio";</script>';
			return;
		}

		if(isset($_POST["guardarContactoWeb"])){
			$prefijoWhatsapp = preg_replace('/\D+/', '', (string)($_POST["webWhatsappPrefijo"] ?? "591"));
			$numeroWhatsapp = preg_replace('/\D+/', '', (string)($_POST["webWhatsapp"] ?? ""));
			$prefijoTelefono = preg_replace('/\D+/', '', (string)($_POST["webTelefonoPrefijo"] ?? "591"));
			$numeroTelefono = preg_replace('/\D+/', '', (string)($_POST["webTelefono"] ?? ""));
			$whatsapp = $prefijoWhatsapp.$numeroWhatsapp;
			$telefono = $prefijoTelefono.$numeroTelefono;
			$correo = strtolower(trim((string)($_POST["webCorreo"] ?? "")));
			if(strlen($numeroWhatsapp) < 6 || strlen($whatsapp) > 15 || strlen($numeroTelefono) < 6 || strlen($telefono) > 15 || !filter_var($correo, FILTER_VALIDATE_EMAIL)){
				echo '<script>swal({type:"warning",title:"Revise los datos",text:"Ingrese un WhatsApp con codigo de pais, un telefono valido y un correo correcto.",confirmButtonText:"Cerrar"}).then(function(){window.location="centro-web";});</script>';
				return;
			}
			$idUsuario = (int)($_SESSION["id"] ?? 0);
			$respuestas = array(
				ModeloWebPublicaciones::mdlGuardarConfiguracion("web_whatsapp", $whatsapp, $idUsuario),
				ModeloWebPublicaciones::mdlGuardarConfiguracion("web_telefono", $telefono, $idUsuario),
				ModeloWebPublicaciones::mdlGuardarConfiguracion("web_whatsapp_prefijo", $prefijoWhatsapp, $idUsuario),
				ModeloWebPublicaciones::mdlGuardarConfiguracion("web_telefono_prefijo", $prefijoTelefono, $idUsuario),
				ModeloWebPublicaciones::mdlGuardarConfiguracion("web_correo", $correo, $idUsuario)
			);
			if(!in_array("error", $respuestas, true)){
				echo '<script>swal({type:"success",title:"Contacto actualizado",text:"WhatsApp, llamadas y correo ya fueron actualizados en la pagina web.",confirmButtonText:"Cerrar"}).then(function(){window.location="centro-web";});</script>';
			}
			return;
		}

		if(isset($_POST["guardarConfiguracionCatalogoWeb"])){
			$respuestaStock = ModeloWebPublicaciones::mdlGuardarConfiguracion(
				"catalogo_mostrar_stock",
				isset($_POST["catalogoMostrarStockWeb"]) ? "1" : "0",
				(int)($_SESSION["id"] ?? 0)
			);
			$respuestaPrecio = ModeloWebPublicaciones::mdlGuardarConfiguracion(
				"catalogo_mostrar_precio",
				isset($_POST["catalogoMostrarPrecioWeb"]) ? "1" : "0",
				(int)($_SESSION["id"] ?? 0)
			);
			if($respuestaStock === "ok" && $respuestaPrecio === "ok"){
				echo '<script>swal({type:"success",title:"Catalogo actualizado",text:"La visibilidad de stock y precios fue guardada.",confirmButtonText:"Cerrar"}).then(function(){window.location="centro-web";});</script>';
			}
			return;
		}

		if(isset($_POST["editarPublicacionWeb"])){
			$tipo = in_array($_POST["tipoEditarPublicacionWeb"] ?? "", array("novedad","oferta","aviso"), true) ? $_POST["tipoEditarPublicacionWeb"] : "novedad";
			$audiencia = in_array($_POST["audienciaEditarPublicacionWeb"] ?? "", array("todos","con_compras","con_servicios","con_proyectos"), true) ? $_POST["audienciaEditarPublicacionWeb"] : "todos";
			$ruta = trim($_POST["imagenActualEditarPublicacionWeb"] ?? "");
			if(isset($_FILES["imagenEditarPublicacionWeb"]) && $_FILES["imagenEditarPublicacionWeb"]["error"] === UPLOAD_ERR_OK){
				$extension = strtolower(pathinfo($_FILES["imagenEditarPublicacionWeb"]["name"], PATHINFO_EXTENSION));
				if(in_array($extension, array("jpg","jpeg","png","webp"), true)){
					$directorio = "vistas/img/web-publicaciones";
					if(!is_dir($directorio)){ mkdir($directorio, 0755, true); }
					$ruta = $directorio."/".date("YmdHis")."_".bin2hex(random_bytes(4)).".".$extension;
					move_uploaded_file($_FILES["imagenEditarPublicacionWeb"]["tmp_name"], $ruta);
				}
			}
			$fechaInicio = trim($_POST["fechaInicioEditarPublicacionWeb"] ?? "");
			$fechaFin = trim($_POST["fechaFinEditarPublicacionWeb"] ?? "");
			$estado = isset($_POST["estadoEditarPublicacionWeb"]) ? 1 : 0;
			$fechaFinSql = $fechaFin !== "" ? str_replace("T", " ", $fechaFin).":00" : null;
			if($estado === 1 && $fechaFinSql !== null && strtotime($fechaFinSql) < time()){
				echo '<script>swal({type:"warning",title:"Vigencia vencida",text:"Actualice la fecha final antes de activar esta publicacion.",confirmButtonText:"Cerrar"}).then(function(){window.location="centro-web";});</script>';
				return;
			}
			$respuesta = ModeloWebPublicaciones::mdlActualizarPublicacion(array(
				":id" => (int)($_POST["idEditarPublicacionWeb"] ?? 0),
				":titulo" => trim($_POST["tituloEditarPublicacionWeb"] ?? ""),
				":resumen" => trim($_POST["resumenEditarPublicacionWeb"] ?? ""),
				":tipo" => $tipo,
				":imagen" => $ruta,
				":enlace" => trim($_POST["enlaceEditarPublicacionWeb"] ?? ""),
				":texto_boton" => trim($_POST["textoBotonEditarPublicacionWeb"] ?? ""),
				":audiencia" => $audiencia,
				":destacada" => isset($_POST["destacadaEditarPublicacionWeb"]) ? 1 : 0,
				":estado" => $estado,
				":fecha_inicio" => $fechaInicio !== "" ? str_replace("T", " ", $fechaInicio).":00" : date("Y-m-d H:i:s"),
				":fecha_fin" => $fechaFinSql,
				":id_usuario" => (int)($_SESSION["id"] ?? 0)
			));
			if($respuesta === "ok"){
				echo '<script>swal({type:"success",title:"Publicacion actualizada",text:"Los cambios se aplicaron correctamente.",confirmButtonText:"Cerrar"}).then(function(){window.location="centro-web";});</script>';
			}
			return;
		}

		if(isset($_POST["guardarPublicacionWeb"])){
			$tipo = in_array($_POST["tipoPublicacionWeb"] ?? "", array("novedad","oferta","aviso"), true) ? $_POST["tipoPublicacionWeb"] : "novedad";
			$audiencia = in_array($_POST["audienciaPublicacionWeb"] ?? "", array("todos","con_compras","con_servicios","con_proyectos"), true) ? $_POST["audienciaPublicacionWeb"] : "todos";
			$ruta = "";
			if(isset($_FILES["imagenPublicacionWeb"]) && $_FILES["imagenPublicacionWeb"]["error"] === UPLOAD_ERR_OK){
				$extension = strtolower(pathinfo($_FILES["imagenPublicacionWeb"]["name"], PATHINFO_EXTENSION));
				if(in_array($extension, array("jpg","jpeg","png","webp"), true)){
					$directorio = "vistas/img/web-publicaciones";
					if(!is_dir($directorio)){ mkdir($directorio, 0755, true); }
					$ruta = $directorio."/".date("YmdHis")."_".bin2hex(random_bytes(4)).".".$extension;
					move_uploaded_file($_FILES["imagenPublicacionWeb"]["tmp_name"], $ruta);
				}
			}
			$fechaInicio = trim($_POST["fechaInicioPublicacionWeb"] ?? "");
			$fechaFin = trim($_POST["fechaFinPublicacionWeb"] ?? "");
			$respuesta = ModeloWebPublicaciones::mdlGuardarPublicacion(array(
				":titulo" => trim($_POST["tituloPublicacionWeb"] ?? ""),
				":resumen" => trim($_POST["resumenPublicacionWeb"] ?? ""),
				":tipo" => $tipo,
				":imagen" => $ruta,
				":enlace" => trim($_POST["enlacePublicacionWeb"] ?? ""),
				":texto_boton" => trim($_POST["textoBotonPublicacionWeb"] ?? ""),
				":audiencia" => $audiencia,
				":destacada" => isset($_POST["destacadaPublicacionWeb"]) ? 1 : 0,
				":estado" => isset($_POST["estadoPublicacionWeb"]) ? 1 : 0,
				":fecha_inicio" => $fechaInicio !== "" ? str_replace("T", " ", $fechaInicio).":00" : date("Y-m-d H:i:s"),
				":fecha_fin" => $fechaFin !== "" ? str_replace("T", " ", $fechaFin).":00" : null,
				":id_usuario" => (int)($_SESSION["id"] ?? 0)
			));
			if($respuesta === "ok"){
				echo '<script>swal({type:"success",title:"Publicacion creada",text:"La novedad ya esta disponible segun su programacion.",confirmButtonText:"Cerrar"}).then(function(){window.location="centro-web";});</script>';
			}
		}

		if(isset($_GET["estadoPublicacionWeb"], $_GET["idPublicacionWeb"])){
			ModeloWebPublicaciones::mdlCambiarEstado((int)$_GET["idPublicacionWeb"], (int)$_GET["estadoPublicacionWeb"]);
			echo '<script>window.location="centro-web";</script>';
		}

		if(isset($_GET["eliminarPublicacionWeb"])){
			ModeloWebPublicaciones::mdlEliminarPublicacion((int)$_GET["eliminarPublicacionWeb"]);
			echo '<script>window.location="centro-web";</script>';
		}

		if(isset($_POST["guardarPublicidadModalWeb"])){
			$rutaModal = "";
			if(isset($_FILES["imagenPublicidadModalWeb"]) && $_FILES["imagenPublicidadModalWeb"]["error"] === UPLOAD_ERR_OK){
				$extension = strtolower(pathinfo($_FILES["imagenPublicidadModalWeb"]["name"], PATHINFO_EXTENSION));
				if(in_array($extension, array("jpg","jpeg","png","webp"), true)){
					$directorio = "vistas/img/web-publicaciones";
					if(!is_dir($directorio)){ mkdir($directorio, 0755, true); }
					$rutaModal = $directorio."/modal_".date("YmdHis")."_".bin2hex(random_bytes(4)).".".$extension;
					move_uploaded_file($_FILES["imagenPublicidadModalWeb"]["tmp_name"], $rutaModal);
				}
			}
			if($rutaModal !== ""){
				$inicioModal = trim($_POST["fechaInicioPublicidadModalWeb"] ?? "");
				$finModal = trim($_POST["fechaFinPublicidadModalWeb"] ?? "");
				ModeloWebPublicaciones::mdlGuardarPublicidadModal(array(
					":titulo" => trim($_POST["tituloPublicidadModalWeb"] ?? ""),
					":texto" => trim($_POST["textoPublicidadModalWeb"] ?? ""),
					":imagen" => $rutaModal,
					":enlace" => trim($_POST["enlacePublicidadModalWeb"] ?? ""),
					":texto_boton" => trim($_POST["textoBotonPublicidadModalWeb"] ?? ""),
					":estado" => isset($_POST["estadoPublicidadModalWeb"]) ? 1 : 0,
					":fecha_inicio" => $inicioModal !== "" ? str_replace("T"," ",$inicioModal).":00" : date("Y-m-d H:i:s"),
					":fecha_fin" => $finModal !== "" ? str_replace("T"," ",$finModal).":00" : null,
					":id_usuario" => (int)($_SESSION["id"] ?? 0)
				));
				echo '<script>swal({type:"success",title:"Publicidad guardada",text:"El modal de inicio fue actualizado.",confirmButtonText:"Cerrar"}).then(function(){window.location="centro-web";});</script>';
			}
		}

		if(isset($_POST["editarPublicidadModalWeb"])){
			$rutaModal = trim($_POST["imagenActualEditarPublicidadModalWeb"] ?? "");
			if(isset($_FILES["imagenEditarPublicidadModalWeb"]) && $_FILES["imagenEditarPublicidadModalWeb"]["error"] === UPLOAD_ERR_OK){
				$extension = strtolower(pathinfo($_FILES["imagenEditarPublicidadModalWeb"]["name"], PATHINFO_EXTENSION));
				if(in_array($extension, array("jpg","jpeg","png","webp"), true)){
					$directorio = "vistas/img/web-publicaciones";
					if(!is_dir($directorio)){ mkdir($directorio, 0755, true); }
					$rutaModal = $directorio."/modal_".date("YmdHis")."_".bin2hex(random_bytes(4)).".".$extension;
					move_uploaded_file($_FILES["imagenEditarPublicidadModalWeb"]["tmp_name"], $rutaModal);
				}
			}
			$inicioModal = trim($_POST["fechaInicioEditarPublicidadModalWeb"] ?? "");
			$finModal = trim($_POST["fechaFinEditarPublicidadModalWeb"] ?? "");
			$estado = isset($_POST["estadoEditarPublicidadModalWeb"]) ? 1 : 0;
			$finModalSql = $finModal !== "" ? str_replace("T"," ",$finModal).":00" : null;
			if($estado === 1 && $finModalSql !== null && strtotime($finModalSql) < time()){
				echo '<script>swal({type:"warning",title:"Vigencia vencida",text:"Cambie la fecha final para volver a activar esta publicidad.",confirmButtonText:"Cerrar"}).then(function(){window.location="centro-web";});</script>';
				return;
			}
			$respuesta = ModeloWebPublicaciones::mdlActualizarPublicidadModal(array(
				":id" => (int)($_POST["idEditarPublicidadModalWeb"] ?? 0),
				":titulo" => trim($_POST["tituloEditarPublicidadModalWeb"] ?? ""),
				":texto" => trim($_POST["textoEditarPublicidadModalWeb"] ?? ""),
				":imagen" => $rutaModal,
				":enlace" => trim($_POST["enlaceEditarPublicidadModalWeb"] ?? ""),
				":texto_boton" => trim($_POST["textoBotonEditarPublicidadModalWeb"] ?? ""),
				":estado" => $estado,
				":fecha_inicio" => $inicioModal !== "" ? str_replace("T"," ",$inicioModal).":00" : date("Y-m-d H:i:s"),
				":fecha_fin" => $finModalSql,
				":id_usuario" => (int)($_SESSION["id"] ?? 0)
			));
			if($respuesta === "ok"){
				echo '<script>swal({type:"success",title:"Publicidad actualizada",text:"La campaña ya tiene su nueva configuracion.",confirmButtonText:"Cerrar"}).then(function(){window.location="centro-web";});</script>';
			}
			return;
		}

		if(isset($_GET["estadoPublicidadModalWeb"], $_GET["idPublicidadModalWeb"])){
			ModeloWebPublicaciones::mdlCambiarEstadoPublicidadModal((int)$_GET["idPublicidadModalWeb"], (int)$_GET["estadoPublicidadModalWeb"]);
			echo '<script>window.location="centro-web";</script>';
		}

		if(isset($_GET["eliminarPublicidadModalWeb"])){
			ModeloWebPublicaciones::mdlEliminarPublicidadModal((int)$_GET["eliminarPublicidadModalWeb"]);
			echo '<script>window.location="centro-web";</script>';
		}
	}
}
