<?php
/**
 * header('Content-Type: application/json');

 * include 'dbconfig.php';
 * include 'includes/dbconnect-inc.php';
 * if (!empty($cook_admin)) {
 * 	include 'admin/autentica_admin.php';
 * } else {
 * 	include 'autentica_usuario.php';
 *}
 **/

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'class/aws/s3_config.php';

error_reporting(E_ERROR);

$fabrica    = preg_replace('/\D/', '', $_GET['fabrica']);    // só pode ser número, só aceita números
$comunicado = preg_replace('/\D/', '', $_GET['comunicado']); // só pode ser número, só aceita números
$tipo_s3    = $_GET['tipo'];

/*
    //hd_chamado=2824422
    Provisorio até a criação do bucket contrato
 */
if((in_array($fabrica, array(152,180,181,182))) AND $tipo_s3 == "Contrato"){
    $tipo_s3 = "co";
}

if (($fabrica == 42 AND $tipo_s3 == "FAQ Makita") or ($tipo_s3 == 'Manual da Rede autorizada')) {
    $tipo_s3 = "co";
}

if($fabrica == 163 AND $tipo_s3 == "Laudo Tecnico"){
    $tipo_s3 = "co";

    if (empty($comunicado)) {

        $sql = "SELECT comunicado FROM tbl_comunicado WHERE fabrica = {$fabrica} AND tipo = 'Laudo Tecnico' ORDER BY data DESC LIMIT 1";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            die;
        } else {
            $comunicado = pg_fetch_result($res, 0, "comunicado");
        }
    }
}

if ($S3_sdk_OK) {
    include_once S3CLASS;
    $s3 = new anexaS3($tipo_s3, (int) $fabrica);

    if (is_object($s3)) {

        if ($s3->temAnexos((int) $comunicado)) {
            if ($s3->url)
                die ($s3->url);
        }

        // $data["response"] = $s3->temAnexo;
        // if ($data["response"] != false)

        if ($tipo_s3 != $s3->tipo_anexo) {
            $s3->set_tipo_anexoS3($tipo_s3);

            if ($s3->temAnexos((int) $comunicado))
                die ($s3->url);
        }
        // echo json_encode($data);
    }
}
include 'plugins/fileuploader/TdocsMirror.php';

$tdocsMirror = new TdocsMirror();

$sql = " SELECT tdocs_id FROM tbl_tdocs WHERE referencia_id = '{$_GET['comunicado']}' AND contexto = 'comunicados' AND referencia = 'comunicados' ";
$res = pg_query($con, $sql);
$tdocs = $tdocsMirror->get(pg_fetch_result($res, 0, "tdocs_id"));

die($tdocs['link']);