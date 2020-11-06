<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

if($_GET['env'] == 'posto'){
    include 'autentica_usuario.php';
}else{
    include 'autentica_admin.php';    
}

$transferCode = "0tcchat0transfer0code20180220";

include 'funcoes.php';
if($_GET['env'] == 'posto'){
    
    $sql = "SELECT codigo_posto FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
    $result = pg_query($con, $sql);
    $result = pg_fetch_row($result);
    
    $data = array(
        "login" => $result[0],
        "fabrica" => $login_fabrica,
        "env" => "posto"
    );
    $data = json_encode($data);
    $transferData = openssl_encrypt($data,"aes-256-ctr",$transferCode);
    
    $transferData = urlencode($transferData);
    header("Location: https://tchat.telecontrol.com.br/autologin?login=".$transferData);
    exit;
}else{


    $sql = "SELECT login,fabrica FROM tbl_admin WHERE admin = $login_admin";
    $result = pg_query($con,$sql);
    $result = pg_fetch_row($result);
    $data = array(
        "login" => $result[0],
        "fabrica" => $result[1],
        "env" => "admin"
    );
    $data = json_encode($data);
    
    $transferData = openssl_encrypt($data,"aes-256-ctr",$transferCode);
    $transferData = urlencode($transferData);
    header("Location: https://tchat.telecontrol.com.br/autologin?login=".$transferData);
    exit;
}
