<?php

require_once "../controladores/usuarios.controlador.php";
require_once "../modelos/usuarios.modelo.php";

class AjaxUsuarios {

    /*=============================================
    EDITAR USUARIO
    =============================================*/    
    public $idUsuario;

    public function ajaxEditarUsuario() {
        $item = "id";
        $valor = $this->idUsuario;

        $respuesta = ControladorUsuarios::ctrMostrarUsuarios($item, $valor);

        echo json_encode($respuesta);
    }

    /*=============================================
    ACTIVAR USUARIO
    =============================================*/    
    public $activarUsuario;
    public $activarId;

    public function ajaxActivarUsuario() {
        $tabla = "usuarios";

        $item1 = "estado";
        $valor1 = $this->activarUsuario;

        $item2 = "id";
        $valor2 = $this->activarId;

        $respuesta = ModeloUsuarios::mdlActualizarUsuario($tabla, $item1, $valor1, $item2, $valor2);
    }

    /*=============================================
    VALIDAR NO REPETIR USUARIO
    =============================================*/    
    public $validarUsuario;

    public function ajaxValidarUsuario() {
        $item = "usuario";
        $valor = $this->validarUsuario;

        $respuesta = ControladorUsuarios::ctrMostrarUsuarios($item, $valor);

        echo json_encode($respuesta);
    }

    /*=============================================
    ACTUALIZAR ROL DEL USUARIO
    =============================================*/
    public $idUsuarioRol;
    public $nuevoRol;

    public function ajaxActualizarRolUsuario() {
        $tabla = "usuarios";

        $item1 = "rol";
        $valor1 = $this->nuevoRol;

        $item2 = "id";
        $valor2 = $this->idUsuarioRol;

        $respuesta = ModeloUsuarios::mdlActualizarUsuario($tabla, $item1, $valor1, $item2, $valor2);

        echo json_encode(["respuesta" => $respuesta]);
    }
}

/*=============================================
EDITAR USUARIO
=============================================*/
if (isset($_POST["idUsuario"])) {
    $editar = new AjaxUsuarios();
    $editar->idUsuario = $_POST["idUsuario"];
    $editar->ajaxEditarUsuario();
}

/*=============================================
ACTIVAR USUARIO
=============================================*/    
if (isset($_POST["activarUsuario"])) {
    $activarUsuario = new AjaxUsuarios();
    $activarUsuario->activarUsuario = $_POST["activarUsuario"];
    $activarUsuario->activarId = $_POST["activarId"];
    $activarUsuario->ajaxActivarUsuario();
}

/*=============================================
VALIDAR NO REPETIR USUARIO
=============================================*/
if (isset($_POST["validarUsuario"])) {
    $valUsuario = new AjaxUsuarios();
    $valUsuario->validarUsuario = $_POST["validarUsuario"];
    $valUsuario->ajaxValidarUsuario();
}

/*=============================================
ACTUALIZAR ROL DEL USUARIO
=============================================*/
if (isset($_POST["idUsuarioRol"]) && isset($_POST["nuevoRol"])) {
    $actualizarRol = new AjaxUsuarios();
    $actualizarRol->idUsuarioRol = $_POST["idUsuarioRol"];
    $actualizarRol->nuevoRol = $_POST["nuevoRol"];
    $actualizarRol->ajaxActualizarRolUsuario();
}
