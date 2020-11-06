<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';

if ($areaAdmin === true) {
    include __DIR__.'/autentica_admin.php';
} else {
    include __DIR__.'/autentica_usuario.php';
}

$posto   = $_GET['posto'];
$produto = $_GET['produto'];

if(!empty($produto)){
    $sql = "SELECT familia FROM tbl_produto WHERE produto = {$produto}";
    $res = pg_query($con,$sql);

    if(pg_num_rows($res) > 0){
        $familia = pg_fetch_result($res, 0, "familia");
    }else{
        $familia = 0;
    }
}else{
    $familia = 0;
}

if(!empty($posto)){
    $sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} 
        AND codigo_posto = '$posto'";
    $res = pg_query($con,$sql);

    if(pg_num_rows($res) > 0){
        $posto = pg_fetch_result($res, 0, "posto");
    }else{
        $posto = 0;
    }
}

$sql = "SELECT comunicado, 
        mensagem, 
        descricao, 
        to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data 
    FROM tbl_comunicado
    WHERE fabrica = {$login_fabrica} AND 
        (familia = {$familia} OR produto = {$produto} OR posto = {$posto})
        AND ativo IS TRUE 
        AND obrigatorio_os_produto IS TRUE";
$resComunicado = pg_query($con,$sql);

include __DIR__.'/funcoes.php';
?>

<style type="text/css">
form {
    width: 900px;
}
.div_comunicado {
    overflow-y: scroll;
    height: 470px;
}

.table td{
    text-align: center !important;
}

.table {
    width: 850px;
    margin: 0 auto;
}

.error {
    border-color: #b94a48 !important;
    box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075) !important;
    color: #b94a48 !important;
}
</style>
<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" />
<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" >
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="bootstrap/js/bootstrap.js"></script>
<?php

include __DIR__."/admin/plugin_loader.php";

if (count($msg_erro['msg']) > 0) { 
?>
<br/>
<div class="alert alert-error"><h4><?=implode("<br />", $msg_erro['msg'])?></h4></div>
<br/>
<?php
}
if(pg_num_rows($resComunicado) > 0){
?>
<form id="fm_conferencia_peca" method="POST" class="form-search form-inline" >
    <div class="div_comunicado" style="margin: 5px; padding-right: 20px;">
        <br/>
        <table id="table_nota_peca" class='table table-striped table-bordered table-large' >
            <thead>
                <tr class='titulo_coluna'>
                    <th>Título</th>
                    <th>Descrição</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
            <?php
                while($objeto_comunicado = pg_fetch_object($resComunicado)){
                ?>
                    <tr>
                        <td><?=$objeto_comunicado->mensagem?></td>
                        <td><?=$objeto_comunicado->descricao?></td>
                        <td><?=$objeto_comunicado->data?></td>
                    </tr>
                <?php 
                }
            ?>
            </tbody>
        </table>
    </div>
</form>
<?php
}
?>
