<?php
//============================================================+
// File name   : tcpdf_include.php
// Begin       : 2008-05-14
// Last Update : 2014-12-10
//
// Description : Search and include the TCPDF library.
//
// Author: Nicola Asuni
//
// (c) Copyright:
//               Nicola Asuni
//               Tecnick.com LTD
//               www.tecnick.com
//               info@tecnick.com
//============================================================+

/**
 * Search and include the TCPDF library.
 * @package com.tecnick.tcpdf
 * @abstract TCPDF - Include the main class.
 * @author Nicola Asuni
 * @since 2013-05-14
 */

// Rutas absolutas para funcionar igual en Windows y servidores Linux.
$tcpdf_config_path = __DIR__ . '/config/tcpdf_config_alt.php';
$tcpdf_library_path = dirname(__DIR__) . '/tcpdf.php';

if (!is_file($tcpdf_config_path)) {
	throw new RuntimeException('No se encontró la configuración TCPDF: '.$tcpdf_config_path);
}
if (!is_file($tcpdf_library_path)) {
	throw new RuntimeException('No se encontró la librería TCPDF: '.$tcpdf_library_path);
}

require_once $tcpdf_config_path;
require_once $tcpdf_library_path;

//============================================================+
// END OF FILE
//============================================================+
