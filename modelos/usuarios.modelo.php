<?php

require_once "conexion.php";

class ModeloUsuarios {

    /*=============================================
    MOSTRAR USUARIOS
    =============================================*/
    static public function mdlMostrarUsuarios($tabla, $item, $valor) {

        if ($item != null) {
            $stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE $item = :$item");
            $stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch();
        } else {
            $stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla");
            $stmt->execute();
            return $stmt->fetchAll();
        }

        $stmt->close();
        $stmt = null;
    }

    static public function mdlMostrarUsuarioPorLogin($tabla, $valor) {

        $stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE usuario = :usuario_login OR email = :email_login LIMIT 1");
        $stmt->bindParam(":usuario_login", $valor, PDO::PARAM_STR);
        $stmt->bindParam(":email_login", $valor, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch();
    }

    static public function mdlAsegurarColumnasSesion($tabla) {

        $conexion = Conexion::conectar();
        $columnas = array();
        $stmt = $conexion->query("SHOW COLUMNS FROM $tabla");

        while($fila = $stmt->fetch()){
            $columnas[] = $fila["Field"];
        }

        $agregar = array();

        if(!in_array("sesion_activa", $columnas)){
            $agregar[] = "ADD COLUMN sesion_activa TINYINT(1) NOT NULL DEFAULT 0";
        }
        if(!in_array("session_token", $columnas)){
            $agregar[] = "ADD COLUMN session_token VARCHAR(128) NULL";
        }
        if(!in_array("session_ip", $columnas)){
            $agregar[] = "ADD COLUMN session_ip VARCHAR(45) NULL";
        }
        if(!in_array("session_user_agent", $columnas)){
            $agregar[] = "ADD COLUMN session_user_agent VARCHAR(255) NULL";
        }
        if(!in_array("session_last_activity", $columnas)){
            $agregar[] = "ADD COLUMN session_last_activity DATETIME NULL";
        }

        if(!empty($agregar)){
            $conexion->exec("ALTER TABLE $tabla ".implode(", ", $agregar));
        }

        return "ok";
    }

    static public function mdlRegistrarSesionActiva($tabla, $idUsuario, $token, $ip, $userAgent, $fecha) {

        $stmt = Conexion::conectar()->prepare("UPDATE $tabla
                                               SET sesion_activa = 1,
                                                   session_token = :token,
                                                   session_ip = :ip,
                                                   session_user_agent = :user_agent,
                                                   session_last_activity = :fecha
                                               WHERE id = :id");

        $stmt->bindParam(":token", $token, PDO::PARAM_STR);
        $stmt->bindParam(":ip", $ip, PDO::PARAM_STR);
        $stmt->bindParam(":user_agent", $userAgent, PDO::PARAM_STR);
        $stmt->bindParam(":fecha", $fecha, PDO::PARAM_STR);
        $stmt->bindParam(":id", $idUsuario, PDO::PARAM_INT);

        return $stmt->execute() ? "ok" : "error";
    }

    static public function mdlLiberarSesion($tabla, $idUsuario, $token = null) {

        if($token !== null){
            $stmt = Conexion::conectar()->prepare("UPDATE $tabla
                                                   SET sesion_activa = 0,
                                                       session_token = NULL,
                                                       session_ip = NULL,
                                                       session_user_agent = NULL,
                                                       session_last_activity = NULL
                                                   WHERE id = :id AND session_token = :token");
            $stmt->bindParam(":token", $token, PDO::PARAM_STR);
        }else{
            $stmt = Conexion::conectar()->prepare("UPDATE $tabla
                                                   SET sesion_activa = 0,
                                                       session_token = NULL,
                                                       session_ip = NULL,
                                                       session_user_agent = NULL,
                                                       session_last_activity = NULL
                                                   WHERE id = :id");
        }

        $stmt->bindParam(":id", $idUsuario, PDO::PARAM_INT);

        return $stmt->execute() ? "ok" : "error";
    }

    static public function mdlActualizarActividadSesion($tabla, $idUsuario, $token, $fecha) {

        $stmt = Conexion::conectar()->prepare("UPDATE $tabla
                                               SET session_last_activity = :fecha
                                               WHERE id = :id
                                               AND session_token = :token
                                               AND sesion_activa = 1");

        $stmt->bindParam(":fecha", $fecha, PDO::PARAM_STR);
        $stmt->bindParam(":id", $idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(":token", $token, PDO::PARAM_STR);

        return $stmt->execute() ? "ok" : "error";
    }

    /*=============================================
    REGISTRO DE USUARIO
    =============================================*/
    static public function mdlIngresarUsuario($tabla, $datos) {

        self::mdlAsegurarColumnasPassword($tabla);

        $stmt = Conexion::conectar()->prepare("INSERT INTO $tabla(nombre, email, usuario, password, debe_cambiar_password, perfil, foto, rol) 
                                               VALUES (:nombre, :email, :usuario, :password, :debe_cambiar_password, :perfil, :foto, :rol)");

        $stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
        $stmt->bindParam(":email", $datos["email"], PDO::PARAM_STR);
        $stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);
        $stmt->bindParam(":password", $datos["password"], PDO::PARAM_STR);
        $stmt->bindParam(":debe_cambiar_password", $datos["debe_cambiar_password"], PDO::PARAM_INT);
        $stmt->bindParam(":perfil", $datos["perfil"], PDO::PARAM_STR);
        $stmt->bindParam(":foto", $datos["foto"], PDO::PARAM_STR);
        $stmt->bindParam(":rol", $datos["rol"], PDO::PARAM_STR);

        if ($stmt->execute()) {
            return "ok";
        } else {
            return "error";
        }

        $stmt->close();
        $stmt = null;
    }

    /*=============================================
    EDITAR USUARIO
    =============================================*/
    static public function mdlEditarUsuario($tabla, $datos) {

        $stmt = Conexion::conectar()->prepare("UPDATE $tabla 
                                               SET nombre = :nombre, email = :email, password = :password, perfil = :perfil, foto = :foto, rol = :rol 
                                               WHERE usuario = :usuario");

        $stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
        $stmt->bindParam(":email", $datos["email"], PDO::PARAM_STR);
        $stmt->bindParam(":password", $datos["password"], PDO::PARAM_STR);
        $stmt->bindParam(":perfil", $datos["perfil"], PDO::PARAM_STR);
        $stmt->bindParam(":foto", $datos["foto"], PDO::PARAM_STR);
        $stmt->bindParam(":rol", $datos["rol"], PDO::PARAM_STR);
        $stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);

        if ($stmt->execute()) {
            return "ok";
        } else {
            return "error";
        }

        $stmt->close();
        $stmt = null;
    }

    /*=============================================
    ACTUALIZAR USUARIO
    =============================================*/
    static public function mdlActualizarUsuario($tabla, $item1, $valor1, $item2, $valor2) {

        $stmt = Conexion::conectar()->prepare("UPDATE $tabla SET $item1 = :$item1 WHERE $item2 = :$item2");

        $stmt->bindParam(":" . $item1, $valor1, PDO::PARAM_STR);
        $stmt->bindParam(":" . $item2, $valor2, PDO::PARAM_STR);

        if ($stmt->execute()) {
            return "ok";
        } else {
            return "error";
        }

        $stmt->close();
        $stmt = null;
    }

    static public function mdlActualizarPasswordUsuario($tabla, $idUsuario, $password, $debeCambiarPassword) {

        self::mdlAsegurarColumnasPassword($tabla);

        $stmt = Conexion::conectar()->prepare("UPDATE $tabla
                                               SET password = :password,
                                                   debe_cambiar_password = :debe_cambiar_password,
                                                   password_reset_token = NULL,
                                                   password_reset_expires = NULL
                                               WHERE id = :id");

        $stmt->bindParam(":password", $password, PDO::PARAM_STR);
        $stmt->bindParam(":debe_cambiar_password", $debeCambiarPassword, PDO::PARAM_INT);
        $stmt->bindParam(":id", $idUsuario, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return "ok";
        } else {
            return "error";
        }
    }

    static public function mdlGuardarTokenRecuperacion($tabla, $idUsuario, $tokenHash, $expira) {

        self::mdlAsegurarColumnasPassword($tabla);

        $stmt = Conexion::conectar()->prepare("UPDATE $tabla
                                               SET password_reset_token = :token,
                                                   password_reset_expires = :expira
                                               WHERE id = :id");

        $stmt->bindParam(":token", $tokenHash, PDO::PARAM_STR);
        $stmt->bindParam(":expira", $expira, PDO::PARAM_STR);
        $stmt->bindParam(":id", $idUsuario, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return "ok";
        } else {
            return "error";
        }
    }

    static public function mdlMostrarUsuarioPorToken($tabla, $tokenHash) {

        self::mdlAsegurarColumnasPassword($tabla);

        $stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla
                                               WHERE password_reset_token = :token
                                               LIMIT 1");

        $stmt->bindParam(":token", $tokenHash, PDO::PARAM_STR);
        $stmt->execute();

        $usuario = $stmt->fetch();
        if(!$usuario){
            return false;
        }

        $expira = strtotime((string)($usuario["password_reset_expires"] ?? ""));
        if(!$expira || $expira < time()){
            return false;
        }

        return $usuario;
    }

    static public function mdlAsegurarColumnasPassword($tabla) {

        $conexion = Conexion::conectar();
        $columnas = array();
        $stmt = $conexion->query("SHOW COLUMNS FROM $tabla");

        while($fila = $stmt->fetch()){
            $columnas[] = $fila["Field"];
        }

        $agregar = array();

        if(!in_array("password_reset_token", $columnas)){
            $agregar[] = "ADD COLUMN password_reset_token VARCHAR(64) NULL";
        }
        if(!in_array("password_reset_expires", $columnas)){
            $agregar[] = "ADD COLUMN password_reset_expires DATETIME NULL";
        }
        if(!in_array("debe_cambiar_password", $columnas)){
            $agregar[] = "ADD COLUMN debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 0";
        }

        if(!empty($agregar)){
            $conexion->exec("ALTER TABLE $tabla ".implode(", ", $agregar));
        }

        return "ok";
    }

    /*=============================================
    BORRAR USUARIO
    =============================================*/
    static public function mdlBorrarUsuario($tabla, $datos) {

        $stmt = Conexion::conectar()->prepare("DELETE FROM $tabla WHERE id = :id");

        $stmt->bindParam(":id", $datos, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return "ok";
        } else {
            return "error";
        }

        $stmt->close();
        $stmt = null;
    }

}
