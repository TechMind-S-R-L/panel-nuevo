<?php

class ControladorWebConsultas{

	static public function ctrPuedeResponder(){
		return ($_SESSION["perfil"] ?? "") === "Administrador" || ($_SESSION["rol"] ?? "") === "vendedor";
	}

	static public function ctrProcesarBandeja(){
		if(!self::ctrPuedeResponder()){
			echo '<script>window.location="inicio";</script>';
			return;
		}
		if(isset($_POST["responderConsultaWeb"])){
			$id = (int)($_POST["idConsultaWeb"] ?? 0);
			$mensaje = trim((string)($_POST["mensajeConsultaWeb"] ?? ""));
			if($id > 0 && $mensaje !== ""){
				ModeloWebConsultas::mdlResponder($id, (int)($_SESSION["id"] ?? 0), mb_substr($mensaje,0,3000));
			}
			echo '<script>window.location="consultas-web?idConsulta='.$id.'";</script>';
			return;
		}
		if(isset($_GET["estadoConsultaWeb"], $_GET["idConsultaWeb"])){
			$id = (int)$_GET["idConsultaWeb"];
			ModeloWebConsultas::mdlCambiarEstadoConsulta($id, (string)$_GET["estadoConsultaWeb"], (int)($_SESSION["id"] ?? 0));
			echo '<script>window.location="consultas-web?idConsulta='.$id.'";</script>';
			return;
		}
	}

	static public function ctrProcesarRespuestasRapidas(){
		if(!ControladorWebPublicaciones::ctrPuedeAdministrar()){ return; }
		if(isset($_POST["guardarRespuestaRapidaWeb"])){
			ModeloWebConsultas::mdlGuardarRespuestaRapida(array(
				":titulo"=>trim((string)($_POST["tituloRespuestaRapidaWeb"] ?? "")),
				":mensaje"=>trim((string)($_POST["mensajeRespuestaRapidaWeb"] ?? "")),
				":icono"=>trim((string)($_POST["iconoRespuestaRapidaWeb"] ?? "ti-message-circle")),
				":orden"=>(int)($_POST["ordenRespuestaRapidaWeb"] ?? 0),
				":estado"=>isset($_POST["estadoRespuestaRapidaWeb"]) ? 1 : 0,
				":id_usuario"=>(int)($_SESSION["id"] ?? 0)
			));
			echo '<script>swal({type:"success",title:"Mensaje rápido creado",text:"Ya está disponible en Consultas de Mi cuenta.",confirmButtonText:"Cerrar"}).then(function(){window.location="centro-web";});</script>';
			return;
		}
		if(isset($_POST["editarRespuestaRapidaWeb"])){
			ModeloWebConsultas::mdlActualizarRespuestaRapida(array(
				":id"=>(int)($_POST["idEditarRespuestaRapidaWeb"] ?? 0),
				":titulo"=>trim((string)($_POST["tituloEditarRespuestaRapidaWeb"] ?? "")),
				":mensaje"=>trim((string)($_POST["mensajeEditarRespuestaRapidaWeb"] ?? "")),
				":icono"=>trim((string)($_POST["iconoEditarRespuestaRapidaWeb"] ?? "ti-message-circle")),
				":orden"=>(int)($_POST["ordenEditarRespuestaRapidaWeb"] ?? 0),
				":estado"=>isset($_POST["estadoEditarRespuestaRapidaWeb"]) ? 1 : 0,
				":id_usuario"=>(int)($_SESSION["id"] ?? 0)
			));
			echo '<script>swal({type:"success",title:"Mensaje rápido actualizado",confirmButtonText:"Cerrar"}).then(function(){window.location="centro-web";});</script>';
			return;
		}
		if(isset($_GET["estadoRespuestaRapidaWeb"],$_GET["idRespuestaRapidaWeb"])){
			ModeloWebConsultas::mdlCambiarEstadoRespuestaRapida((int)$_GET["idRespuestaRapidaWeb"],(int)$_GET["estadoRespuestaRapidaWeb"]);
			echo '<script>window.location="centro-web";</script>';
			return;
		}
		if(isset($_GET["eliminarRespuestaRapidaWeb"])){
			ModeloWebConsultas::mdlEliminarRespuestaRapida((int)$_GET["eliminarRespuestaRapidaWeb"]);
			echo '<script>window.location="centro-web";</script>';
			return;
		}
	}
}
