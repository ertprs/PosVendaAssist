<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include "funcoes.php";

$title = "Pedidos Desmembrados";

if(isset($_GET['pedido'])){
    $pedidos = $_GET['pedido'];
}

include "cabecalho.php"; ?>
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<style>
.menu_top {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: bold;
    border: 0px solid;
    color:#ffffff;
    background-color: #596D9B
}
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.subtitulo{
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}
.Tabela{
    font-family: Verdana,Sans;
    font-size: 10px;
}
.Tabela thead{
    font-size: 12px;
    font-weight:bold;
}
.table_line1 {
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
}
.table_line1_pendencia {
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
    color: #FF0000;
}

.menu_top2 {
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 12px;
    font-weight: normal;
    color: #000000;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

table.tabela tr td{
    font-family: arial, verdana;
    font-size: 10px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
</style>


<script type="text/javascript" src="js/jquery-1.6.2.js"></script>
<script type='text/javascript' src='js/jquery.alphanumeric.js'></script>

<!------------------AQUI COMEÇA O SUB MENU ---------------------!--><?php

if (strlen($msg_erro) > 0) {
    echo "<center><div style='width:700px;' class='msg_erro'>$msg_erro</div></center>";
}

echo "<font color=blue>$msg_ok</font>";
?>

<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
    <tr>
        <td>A quantidade de linhas do pedido excedeu a capacidade do sistema, por isso, foi necessário desmembrar para os seguintes pedidos: <Br><br></td>
    </tr>
    <tr>
        <td valign="top" align="center">                       

            <table width="700" border="0" cellspacing="5" cellpadding="0" class='formulario' align='center'>
                <caption class='titulo_tabela'>Pedidos desmembrados</caption>
                
            </table>

            <table width="700" border="0" cellspacing="1" cellpadding="2" align='center' class='tabela'><?php                
                #echo nl2br($sql);exit;
                $res = pg_query ($con,$sql);
                $total_pedido = 0 ;

                $lista_os = array();
                $ExibeCabecalho = 0;
            ?>
                    <thead>
                        <tr height="20" class='titulo_coluna'>                            
                            <td>Pedido</td>                            
                            <td>Qtde Itens</td>
                            <td>Total</td>
                        </tr>
                    </thead>
                    <tbody>
            <?php 
                $sql = "SELECT tbl_pedido.pedido, tbl_pedido.seu_pedido, total, count(tbl_pedido_item.pedido_item)as qtde_itens  FROM tbl_pedido inner join tbl_pedido_item on tbl_pedido_item.pedido = tbl_pedido.pedido  WHERE tbl_pedido.pedido in ($pedidos) and tbl_pedido.fabrica = $login_fabrica group by tbl_pedido.pedido,total"; 
                $res = pg_query($con, $sql);
                for($i=0; $i<pg_num_rows($res); $i++){
                    $pedido     = pg_fetch_result($res, $i, 'pedido');
                    $seu_pedido = pg_fetch_result($res, $i, 'seu_pedido');
                    $total      = number_format(pg_fetch_result($res, $i, 'total'), 2, ',', ' ');
                    $qtde_itens = pg_fetch_result($res, $i, 'qtde_itens');
                    ?>
                    <tr>
                        <td align='center'><a target="_blank" href="pedido_finalizado.php?pedido=<?=$pedido?>"><?=$seu_pedido?></a></td>
                        <td align='center'><?=$qtde_itens?></td>
                        <td align='center'>R$ <?=$total?></td>
                    </tr>
            <?php
                }
            ?>
                </tbody>
            </table>



<?php
    include "rodape.php"; 
?>
