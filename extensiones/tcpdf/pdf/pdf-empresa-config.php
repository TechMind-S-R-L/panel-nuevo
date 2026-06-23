<?php

require_once __DIR__ . "/../../../modelos/web-publicaciones.modelo.php";

function tmPdfEmpresaConfig(){
	static $config = null;
	if($config !== null){
		return $config;
	}

	$config = array(
		"nombre" => ModeloWebPublicaciones::mdlObtenerConfiguracion("boletas_empresa_nombre", "TECHMIND S.R.L."),
		"direccion" => ModeloWebPublicaciones::mdlObtenerConfiguracion("boletas_empresa_direccion", "Km 6 doble via la guardia, calle paraiso Nro 6387"),
		"telefono" => ModeloWebPublicaciones::mdlObtenerConfiguracion("boletas_empresa_telefono", "(+591) 75556540 | (+591) 78572656"),
		"correo" => ModeloWebPublicaciones::mdlObtenerConfiguracion("boletas_empresa_correo", "techmind.srl.bo@gmail.com")
	);

	return $config;
}

function tmPdfEmpresaTexto($clave){
	$config = tmPdfEmpresaConfig();
	return (string)($config[$clave] ?? "");
}

