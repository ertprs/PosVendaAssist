<?php

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';

$pedido_item    = filter_input(INPUT_GET,'pedido_item');
$os_item        = filter_input(INPUT_GET,'os_item');
$qtde           = filter_input(INPUT_GET,'qtde');

if (filter_input(INPUT_POST,'acao')) {
    $pedido_item    = filter_input(INPUT_POST,'pedido_item');
    $os_item        = filter_input(INPUT_POST,'os_item');
    $qtde           = filter_input(INPUT_POST,'qtde',FILTER_VALIDATE_INT);
    $motivo         = filter_input(INPUT_POST,'cancelar_motivo',FILTER_SANITIZE_MAGIC_QUOTES);

    $motivo = utf8_encode($motivo);

    $sql_dados = "
        SELECT  tbl_pedido_item.pedido,
                tbl_os.os,
                tbl_os_item.peca,
                (tbl_pedido_item.qtde - (tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada)) AS qtde
        FROM    tbl_pedido_item
        JOIN    tbl_pedido      USING(pedido)
        JOIN    tbl_os_item     USING(pedido)
        JOIN    tbl_os_produto  USING(os_produto)
        JOIN    tbl_os          USING(os)
        WHERE   tbl_pedido_item.pedido_item = $pedido_item
        AND     tbl_os_item.os_item         = $os_item";
// exit(nl2br($sql_dados));
    $res_dados = pg_query($con,$sql_dados);

    while ($resultado = pg_fetch_object($res_dados)) {
        $os = $resultado->os;
        pg_query($con,"BEGIN TRANSACTION");

        $sqlCancel = "SELECT fn_pedido_cancela_lenoxx($login_fabrica,".$resultado->pedido.",".$resultado->peca.",$qtde,'".$motivo."',$pedido_item,$login_admin);";
        $resCancel = pg_query($con,$sqlCancel);

        if (strlen(pg_last_error($con)) > 0) {
            if (strpos(pg_last_error($con),"Quantidade")) {
                $erro = "Quantidade a cancelar é maior que a informada no pedido";
            }
            $msg_erro .= "Erro ao cancelar o pedido item {$pedido_item} do pedido <strong>{$resultado->pedido}</strong>: $erro";
            pg_query($con,"ROLLBACK TRANSACTION");
        } else {
            pg_query($con,"COMMIT TRANSACTION");
        }
    }
} else {
    $msg_erro = "Preencha os dados para cancelamento do item.";
}
?>

<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tooltips.css" type="text/css" rel="stylesheet" />
<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
<?php
$plugins = array(
    "datepicker",
    "alphanumeric",
    "shadowbox",
    "mask"
);

include("plugin_loader.php");
?>
<script type="text/javascript">
$(function(){
    $(".numeric").numeric();
});
</script>

<?php
if (filter_input(INPUT_POST,'acao')) {
    if (!empty($msg_erro)) {
?>
    <div class="alert alert-error">
        <h4><?=$msg_erro?></h4>
    </div>
<?php
    } else {
?>
    <div class='alert alert-success'><h4>Item Cancelado com sucesso</h4></div>
<?php
    }
} else {
?>

<form name="frm_cancelamento" method="POST" class="" action="<? echo $PHP_SELF; ?> ">
    <input type="hidden" name="pedido_item" value="<?=$pedido_item?>" />
    <input type="hidden" name="os_item" value="<?=$os_item?>" />
    <input type="hidden" name="qtde" value="<?=$qtde?>" />
    <div class="tc_formulario">
        <div class="titulo_tabela">Cancelar Itens de Pedido</div>
        <div class="row-fluid">
            <div class="span1"></div>
            <!--<div class="span4">
                <label for="qtde" >Qtde</label>
                <input type="text" name="cancelar_qtde" id="qtde" class="span3 numeric" />
            </div>-->
            <div class="span8">
                <label for="motivo">Motivo</label>
                <input type="text" name="cancelar_motivo" id="motivo" class="span12" />
            </div>
            <div class="span1"></div>
        </div>
        <input type="hidden" name="acao" value="gravar" />
        <button name="Gravar" id="gravar" class="btn btn-success">Gravar</button>
    </div>
</form>
<?php
}
if (filter_input(INPUT_POST,'acao') && empty($msg_erro)) {
?>
<script type="text/javascript">
    setTimeout(function(){

        window.parent.location.href = "faturar_pedido_os.php?os=<?=$os?>";
    }, 3000);
</script>
<?php
}
?>
