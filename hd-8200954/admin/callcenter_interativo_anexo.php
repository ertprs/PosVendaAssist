<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$admin_privilegios = "call_center";
$layout_menu       = "callcenter";

$fileApagar     = @$_REQUEST['fileApagar'];
$anexo      = trim($_REQUEST["anexo"]);

if(strlen($fileApagar) > 0){
    $arquivo = 'callcenter_digitalizados/'.$fileApagar;
    if(file_exists($arquivo)){
        unlink($arquivo);
        $sql = "BEGIN TRANSACTION";
        $res = pg_query($con, $sql);
        $msg_erro[] = pg_errormessage($con);
        if(strpos($fileApagar,"-")){
            $callcenter = substr($fileApagar,0,-6);
        }else{
            $callcenter = substr($fileApagar,0,-4);
        }

        $sql = "SELECT  tbl_hd_chamado.status
        FROM    tbl_hd_chamado
        WHERE   tbl_hd_chamado.hd_chamado = $callcenter
        ";
        $res = pg_query($con,$sql);
        $status_item = pg_fetch_result($res,0,0);

        $sql = "INSERT INTO tbl_hd_chamado_item (
                                                hd_chamado  ,
                                                comentario  ,
                                                admin       ,
                                                interno     ,
                                                status_item
                                             ) VALUES (
                                                $callcenter                                                             ,
                                                'Imagem deletada: $fileApagar pelo(a) usuário(a) <b>$login_login</b>' ,
                                                $login_admin                                                            ,
                                                't'                                                                     ,
                                                '$status_item'
                                             )
        ";
        $res = pg_query($con,$sql);
        $msg_erro[] = pg_errormessage($con);

        $msg_erro = implode("", $msg_erro);
        if (strlen($msg_erro)) {

            $sql = "ROLLBACK TRANSACTION";
            $res = pg_query($con, $sql);
        } else {
            $sql = "COMMIT TRANSACTION";
            $res = pg_query($con, $sql);
        }
        echo "<script>window.opener.location.reload();window.close();</script>";
        exit;
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Visualização de Anexo</title>
    <style>
        body {
            margin: 0px;
        }

        .msg {
            border: 0px;
            color: #FF2222;
            text-align: center;
        }
        img {
            width: 100%;
            height: 100%;
        }
    </style>
</head>
<body>

<div align="center">
<?php
if ($anexo) {

    $arquivo = 'callcenter_digitalizados/'.$anexo;

    if (file_exists($arquivo)) {
        echo "<img src='$arquivo' ><br /><a href='$arquivo'>Download</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href='$PHP_SELF?fileApagar=$anexo'>Apagar Arquivo</a>";
    } else {
        $msg_erro = 'Arquivo não encontrado!';
    }

} else {
    $msg_erro = 'Arquivo não informado';
}

if ($msg_erro) {
    echo "<p>$msg_erro</p>";
}?>
</div>
</body>
</html>
