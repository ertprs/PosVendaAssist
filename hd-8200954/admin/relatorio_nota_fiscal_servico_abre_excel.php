<?php

/**
 * - Arquivo para abrir automaticamente
 * a planilha gerada pelo relatório
 *
 * @author William Ap. Brandino
 */

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$file = filter_input(INPUT_GET,'file');
$caminho = "/tmp/".lcfirst($login_fabrica_nome)."/";
$link = $caminho.$file;

header('Pragma: public');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Cache-Control: private',false);
header('Content-Type: application/force-download');
header('Content-Disposition: attachment; filename="'.basename( $link ).'"');
header('Content-Transfer-Encoding: binary');
header('Connection: close');
readfile( $link );