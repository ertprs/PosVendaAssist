<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include "funcoes.php";

$layout_menu = "pedido";

include "cabecalho.php";


$bloq =  $_GET["bloq"];

if (isset($_GET['pedido'])) {
    $pedidos = $_GET['pedido'];
    $condicao = " AND tbl_pedido.pedido in ($pedidos) ";
}

if(isset($_GET['exportado'])){
    $demanda = true;
    $condicao = "AND tbl_pedido.finalizado is not null 
                 AND tbl_pedido.exportado is null " ;
}


?>
<br>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<?php if($bloq == "f"){?>
<tr>
    <Td style="background-color:#ff0000; padding: 10px; color:#ffffff; text-align:justify; font-weight:bold; font-size:14px">
        A condi&ccedil;&atilde;o do seu pedido foi alterada para "pagamento antecipado" e est&aacute; sujeita a an&aacute;lise
        de cr&eacute;dito. Favor abrir um chamado para o suporte de sua regi&atilde;o informando o n&uacute;mero deste pedido, 
        a fim de ser orientado sobre o procedimento."
    </Td>
</tr>
<?php } ?>
<tr>
    <td valign="top" align="center">
        <table width="650" border="0" cellspacing="5" cellpadding="0">
         <?php if($demanda != true){?>
        <tr>
            <td align="center"> A quantidade de linhas do pedido excedeu a capacidade do sistema, por isso, foi necessário desmembrar para os seguintes pedidos: <br><br></td>
        </tr>
        <?php } ?>
        <tr>
            <td nowrap align='center'>
                <font size="2" face="Geneva, Arial, Helvetica, san-serif">
                
                    <b>Relação de Pedidos</b>
                </font>
            </td>
        </tr>
        </table>
        
        <table width="650" border="0" cellspacing="3" cellpadding="0" align='center'>
        <tr bgcolor='#cccccc' style='color: #000000 ; font-size:12px; font-weight:bold ; text-align:center '>
            <td align='center'>Pedido</td>
            <td align='center'>Qtde Itens</td>
            <td align='center'>Total</td>
            <td align='center'>Situação</td>
            <?php if($demanda == true){ ?>
                <td align='center'></td>
            <?php } ?>
        </tr>
        
        <?php
            $sql = "SELECT tbl_pedido.pedido, tbl_pedido.seu_pedido, tbl_status_pedido.descricao as descricao_status_pedido, total, count(tbl_pedido_item.pedido_item)as qtde_itens  FROM tbl_pedido 

                inner join tbl_pedido_item on tbl_pedido_item.pedido = tbl_pedido.pedido  
                inner join tbl_status_pedido on tbl_status_pedido.status_pedido = tbl_pedido.status_pedido
                WHERE  tbl_pedido.posto = $login_posto AND tbl_pedido.fabrica = $login_fabrica  AND tbl_pedido.status_pedido IN(1,18) AND tbl_pedido.data > '2017-01-01 00:00' $condicao  group by tbl_pedido.pedido, tbl_pedido.seu_pedido, tbl_status_pedido.descricao, total"; 
            $res = pg_query($con, $sql);

            for($i=0; $i<pg_num_rows($res); $i++){
                $seu_pedido     = pg_fetch_result($res, $i, 'seu_pedido');
                $pedido     = pg_fetch_result($res, $i, 'pedido');
                $total      = number_format(pg_fetch_result($res, $i, 'total'), 2, ',', ' ');
                $qtde_itens = pg_fetch_result($res, $i, 'qtde_itens');
                $descricao_status_pedido = ucwords(pg_fetch_result($res, $i, 'descricao_status_pedido'));
                //$pedido = pg_fetch_result($res, $i, 'pedido');
                ?>
               <tr bgcolor='<?echo $cor?>' style='color: #000000 ; font-size:12px; text-align:center '>
                <td><a target="_blank" href='pedido_finalizado.php?pedido=<?=$pedido?>' ><?=$seu_pedido?></a></td>
                <td><?=$qtde_itens?></td>
                <td>R$ <?=$total?></td>
                <td><?=$descricao_status_pedido?></td>
                <?php if($demanda == true){?>
                    <td align='center'> <a href="pedido_blackedecker_cadastro.php?pedido=<?=$pedido?>&reabrir=sim"> <img src='imagens/btn_alterar_cinza.gif'> </a> </td>
                <?php } ?>
                </tr>
                <?php 

            }
        
        ?>
        
        <tr>
       
        </tr>
                </table>
        

<!------------ Atendimento Direto de Pedidos ------------------- -->
<?


echo "</table>";

echo "<br>";
?>
<? include "rodape.php"; ?>
