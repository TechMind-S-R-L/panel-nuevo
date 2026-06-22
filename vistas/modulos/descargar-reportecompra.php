<?php
require_once "../../controladores/compras.controlador.php";
require_once "../../modelos/compras.modelo.php";
require_once "../../controladores/proveedor.controlador.php";
require_once "../../modelos/proveedor.modelo.php";
require_once "../../controladores/usuarios.controlador.php";
require_once "../../modelos/usuarios.modelo.php";

$reporte = new ControladorCompras();
$reporte -> ctrDescargarReporteCompra();