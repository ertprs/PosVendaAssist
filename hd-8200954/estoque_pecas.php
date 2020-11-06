<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$ajax = $_GET['ajax'];
if(strlen($ajax)>0){

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

    $peca = $_GET['peca'];
    $data_inicial = date("Y-m-d", mktime(0, 0, 0, date("n"), 1,  date("Y")));
    $data_final   = date("Y-m-t", mktime(0, 0, 0, date("n"), 1,  date("Y")));

    if(strlen($peca)>0){

        $tipo = $_GET['tipo'];
        $tipo = ($tipo == "undefined") ? "" : $tipo;

        if(strlen($tipo) > 0){
            if($tipo == "garantia"){
                $cond = " AND     tbl_estoque_posto_movimento.tipo = 'garantia' ";
            }elseif($tipo == "faturada"){
                $cond = " AND     tbl_estoque_posto_movimento.tipo = 'faturada'";
            }
        }else{
            $cond = " AND     tbl_estoque_posto_movimento.tipo IN ('garantia','faturada')";
        }

        $sql = "SELECT  tbl_estoque_posto_movimento.peca                                            ,
                        tbl_peca.referencia                                                         ,
                        tbl_peca.descricao                                      AS peca_descricao   ,
                        tbl_os.sua_os                                                               ,
                        tbl_estoque_posto_movimento.os                                              ,
                        to_char(tbl_estoque_posto_movimento.data,'DD/MM/YYYY')  AS data             ,
                        tbl_estoque_posto_movimento.qtde_entrada                                    ,
                        tbl_estoque_posto_movimento.qtde_saida                                      ,
                        tbl_estoque_posto_movimento.admin                                           ,
                        tbl_estoque_posto_movimento.nf                                              ,
                        tbl_estoque_posto_movimento.obs
                FROM    tbl_estoque_posto_movimento
                JOIN    tbl_peca ON  tbl_peca.peca                  = tbl_estoque_posto_movimento.peca
                                 AND tbl_peca.fabrica               = $login_fabrica
           LEFT JOIN    tbl_os   ON  tbl_estoque_posto_movimento.os = tbl_os.os
                                 AND tbl_os.fabrica                 = $login_fabrica
                WHERE   tbl_estoque_posto_movimento.posto   = $login_posto
                AND     tbl_estoque_posto_movimento.peca    = $peca
                AND     tbl_estoque_posto_movimento.fabrica = $login_fabrica
                $cond
          ORDER BY      tbl_peca.descricao                      ,
                        tbl_estoque_posto_movimento.data        ,
                        tbl_estoque_posto_movimento.qtde_saida  ,
                        tbl_estoque_posto_movimento.os";
        $res = pg_exec($con,$sql);
        //  AND   tbl_estoque_posto_movimento.data between '$data_inicial' and '$data_final'
        //echo $sql;
        if(pg_numrows($res)>0){
        //echo "<div style='border: 1px solid #cdcdcd;background-color: #FFFFFF;width:50px;align:right'><a href='javascript:fechar();'><B>Fechar</b></a></div>";
            //echo "<table border='0' width='100%' cellpadding='4' cellspacing='1' align='rigth' style='font-family: verdana; font-size: 9px'><tr><td width='95%'>&nbsp;</td><td align='right' bgcolor='#FFFFFF'><a href='javascript:fechar(". pg_result ($res,0,peca) .");'><B>Fechar</b></a></td></tr></table>";
            echo "<td colspan='2' align='center'>";
            echo "<table border='0' cellpadding='4' cellspacing='1' class='tabela'>";
            echo "<caption class='titulo_coluna'><a href='javascript:fechar(". pg_result ($res,0,peca) .");' style='color:#ffffff;'><B>Fechar</b></a>";
            echo "</caption>";
            echo "<thead>";
            echo "<tr class='titulo_coluna'>";
            echo "<th>Movimentação</th>";
            echo "<th>Data</th>";
            echo "<th>Peça</th>";
            echo "<th>Entrada</th>";
            echo "<th>Saida</th>";
            echo "<th>Nota Fiscal</th>";
            echo "<th>OS</th>";
            echo "<th>Observação</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";
            for($i=0; pg_numrows($res)>$i;$i++){

                $os             = pg_result ($res,$i,os);
                $sua_os         = pg_result ($res,$i,sua_os);
                $referencia     = pg_result ($res,$i,referencia);
                $peca_descricao = pg_result ($res,$i,peca_descricao);
                $data           = pg_result ($res,$i,data);
                $qtde_entrada   = pg_result ($res,$i,qtde_entrada);
                $qtde_saida     = pg_result ($res,$i,qtde_saida);
                $admin          = pg_result ($res,$i,admin);
                $obs            = pg_result ($res,$i,obs);
                $nf             = pg_result ($res,$i,nf);

                $saida_total  = $saida_total + $qtde_saida;
                $entrada_total = $entrada_total + $qtde_entrada;

                if($qtde_entrada>0){
                    $movimentacao = "<font color='#35532f'>Entrada</font>";
                }else{
                    $movimentacao = "<font color='#f31f1f'>Saida</font>";
                }

                $cor = "#efeeea";
                if ($i % 2 == 0) $cor = '#d2d7e1';

                echo "<tr bgcolor='$cor'>";
                echo "<td align='center'>$movimentacao</td>";
                echo "<td align='center'>$data</td>";
                echo "<td align='left'>$referencia - $peca_descricao</td>";
                echo "<td align='center'>$qtde_entrada</td>";
                echo "<td align='center'>$qtde_saida</td>";
                echo "<td align='center'>$nf</td>";
                echo "<td><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
                echo "<td align='left'>$obs</td>";
                echo "</td>";
                echo "</tr>";

            }
        $total = $entrada_total - $saida_total;
        echo "</tbody>";
        echo "<tfoot>";
            echo "<tr bgcolor='#FFFFFF'>";
            echo "<td colspan='3' align='center'><font color='#2f67cd'><B>SALDO</B></FONT></td>";
            echo "<td colspan='2' align='center'><font color='#2f67cd'><B>"; echo $total; echo "</B></FONT></td>";
            echo "<td>&nbsp;</td>";
            echo "<td>&nbsp;</td>";
            echo "<td>&nbsp;</td>";
            echo "</tr>";
        echo "</tfoot>";
            echo "</table><BR>";

        }else{
            echo "Nenhum resultado encontrado";
        }
        echo "</td>";
    }
    exit;
}

###############################PEDIDO DE COMPRA######################################3
$btn_confirmar = $_POST['btn_confirmar'];
if(strlen($btn_confirmar)>0 and $login_fabrica==24 and 1==2){
    $res = pg_exec ($con,"BEGIN TRANSACTION");
    if($login_fabrica==24){ $condicao = 926; $tipo = 107;}
    $sql = "INSERT INTO tbl_pedido (
                posto          ,
                fabrica        ,
                condicao       ,
                tipo_pedido    ,
                status_pedido
            ) VALUES (
                $login_posto        ,
                $login_fabrica      ,
                $condicao           ,
                $tipo               ,
                1
            )";
//echo nl2br($sql);
    $res = @pg_exec ($con,$sql);
    $msg_erro = pg_errormessage($con);
    if (strlen($msg_erro) == 0){
        $res = @pg_exec ($con,"SELECT CURRVAL ('seq_pedido')");
        $pedido  = @pg_result ($res,0,0);
    }

    $qtde_pedidos = $_POST['qtde_pedidos'];
    for($i=0;$qtde_pedidos>$i;$i++){
        $qtde_pendente = $_POST['qtde_pendente_'.$i];
        $devolucao     = $_POST['devolucao_'    .$i];
        $compra        = $_POST['compra_'       .$i];
        $peca          = $_POST['peca_'         .$i];
    /*  if(strlen($devolucao)>0 and strlen($compra)>0){
            echo "<BR>peca $peca pendente : $qtde_pendente devo: $devolucao compra: $compra<BR>";

        }*/
        if(strlen($compra) > 0 AND strlen($msg_erro) == 0){
            $sql = "INSERT INTO tbl_pedido_item (
                                        pedido ,
                                        peca   ,
                                        qtde
                                    ) VALUES (
                                        $pedido ,
                                        $peca   ,
                                        $compra
                                    )";
            $res = @pg_exec ($con,$sql);
            $msg_erro = pg_errormessage($con);
//          echo "<BR>peca $peca pendente : $qtde_pendente devo: $devolucao compra: $compra<BR>";

        }
    }
    if (strlen ($msg_erro) == 0) {
        $sql = "SELECT pedido_item from tbl_pedido_item where pedido = $pedido";
        $res = @pg_exec ($con,$sql);
        if(pg_numrows($res)==0){
            $sql = "DELETE from tbl_pedido where pedido = $pedido and fabrica = $login_fabrica";
            $res = @pg_exec ($con,$sql);
            $msg_erro = pg_errormessage($con);
        }else{
            $sql = "SELECT fn_pedido_finaliza ($pedido,$login_fabrica)";
            $res = @pg_exec ($con,$sql);
            $msg_erro = pg_errormessage($con);
        }
    } echo $sql;
    if (strlen ($msg_erro) == 0) {
        $res = pg_exec ($con,"COMMIT TRANSACTION");
        header ("Location: pedido_finalizado.php?pedido=$pedido&loc=1");
        exit;
    }else{
        $res = pg_exec ($con,"ROLLBACK TRANSACTION");
    }
echo "==> $msg_erro";
}
###############################PEDIDO DE COMPRA######################################3

$title = "ESTOQUE DE PEÇAS";
$layout_menu = 'os';

include "cabecalho.php";
?>
<script type="text/javascript" src="ajax.js"></script>
<script type="text/javascript" src="js/assist.js"></script>
<script type="text/javascript" src="js/jquery-1.3.2.js"></script>
<script type="text/javascript" src="js/jquery.corner.js"></script>
<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />

<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<script type="text/javascript" src="plugins/shadowbox/shadowbox.js" ></script>

<script type="text/javascript">
$(function () {
    Shadowbox.init();
});

function fechar(peca){
    if (document.getElementById('dados_'+ peca)){
        var style2 = $('#dados_'+peca);

        if ($(style2).is(":visible")){
            $(style2).hide();
        }else{
           $(style2).show();
            retornaMovimentacao(peca);
        }
    }
}
function Calcula(linha,campo){
    var pendente  = document.getElementById('qtde_pendente_'+ linha);
    var devolucao = document.getElementById('devolucao_'+ linha);
    var compra    = document.getElementById('compra_'+ linha);

    if (campo=="devolucao"  && devolucao.value.length >0){
        compra.value = pendente.value - devolucao.value;
        if(compra.value<0){
            alert("Número superior ao total");
            compra.value    ='';
            devolucao.value ='';
        }
    }
    if (campo=="compra" && compra.value.length > 0){
        devolucao.value = pendente.value - compra.value;
        if(devolucao.value<0){
            alert("Número superior ao total");
            compra.value    ='';
            devolucao.value ='';
        }
    }
}

function createRequestObject(){
    var request_;
    var browser = navigator.appName;
    if(browser == "Microsoft Internet Explorer"){
         request_ = new ActiveXObject("Microsoft.XMLHTTP");
    }else{
         request_ = new XMLHttpRequest();
    }
    return request_;
}

function mostraMovimentacao(peca){
    if (document.getElementById('dados_'+peca)){
        var style2 = $('#dados_'+peca);

        if ($(style2).is(":visible")){
            $(style2).hide();
        }else{
           $(style2).show();
            retornaMovimentacao(peca);
        }
    }
}
var http3 = new Array();
function retornaMovimentacao(peca){

    var tipo = $("input[name=tipo]:checked").val();
    var curDateTime = new Date();
    http3[curDateTime] = createRequestObject();

    url = "estoque_pecas.php?ajax=true&peca="+ peca+"&tipo="+ tipo;
    http3[curDateTime].open('get',url);
    var campo = document.getElementById('dados_'+peca);

    http3[curDateTime].onreadystatechange = function(){
        if(http3[curDateTime].readyState == 1) {
            campo.innerHTML = "<font size='1' face='verdana'>Aguarde..</font>";
        }
        if (http3[curDateTime].readyState == 4){
            if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){
                var results = http3[curDateTime].responseText;
                campo.innerHTML   = results;
            }else {
                campo.innerHTML = "Erro";
            }
        }
    }
    http3[curDateTime].send(null);

}

function fnc_pesquisa_peca_2 (referencia, descricao) {
    if (referencia.length > 2 || descricao.length > 2) {
        Shadowbox.open({
            content:"peca_pesquisa_nv.php?referencia=" + referencia + "&descricao=" + descricao,
            player: "iframe",
            title:  "Pesquisa Peça",
            width:  800,
            height: 500
        });
    }
    else{
        alert("Informe toda ou parte da informação para realizar a pesquisa");
    }
}

function retorna_dados_peca (peca, referencia, descricao, ipi, origem, estoque, unidade, ativo, posicao)
{
    gravaDados("referencia", referencia);
    gravaDados("descricao", descricao);
}

function gravaDados(name, valor){
    try {
        $("input[name="+name+"]").val(valor);
    } catch(err){
        return false;
    }
}

</script>
<style type="text/css">
.menu_top {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: bold;
    border: 0px solid;
    color:#ffffff;
    background-color: #596D9B
}
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    border: 1px solid #596d9b;
}
.frm {
    background-color:#F0F0F0;
    border:1px solid #888888;
    font-family:Verdana;
    font-size:8pt;
    font-weight:bold;
}
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px 'Arial';
    color:#FFFFFF;
    text-align:center;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px 'Arial';
    color:#FFFFFF;
    text-align:center;
}
.table_line1 {
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
}
.fechar {
float:left;
text-align: right;color: #FF0000;}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
</style>

<form name='frm_estoque' action='<? echo $PHP_SELF; ?>' method='post'>
<input type='hidden' name='btn_confirmar' value=''>
<!--<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
    <td valign="top" align="center">
        <table width="100%" border="0" cellspacing="1" cellpadding="3" align='center'>
        <tr>
            <td nowrap align='center'>
            <BR><font size="2" face="verdana"><b>
                Estoque de peças em garantia</b><BR>
O estoque do posto é baseado na quantidade de peças pedidas em garantia antecipada<BR> e peças utilizadas em ordens de serviços fechadas.
                </font><BR><BR>
            </td>
        </tr>
        </table><BR>
        <table width="100%" border="0" cellspacing="1" cellpadding="3" align='center'>
        <tr>
            <td nowrap align='center' width='30' bgcolor='#efc7ac'>&nbsp;</td>
            <td nowrap align='left'>
            <font size="1" face="verdana">Peças recebidas a mais de 60 dias, o PA deve comprar ou devolve-las</font>
            </td>
        </tr>
        </table><BR>
        -->
<form name="frm_consulta" method="post" action="<?=$PHP_SELF?>">
<table cellspacing="1" cellpadding="3" align="center" width="700px" class="formulario">
    <tr>
        <td colspan="3" class="titulo_tabela">Parâmetros de Pesquisa</td>
    </tr>
    <tr><td>&nbsp;</td></tr>
    <tr>
        <td width="10%">&nbsp;</td>
        <td style="padding:10px 0 0 0;">
            Referência<br /><input class="frm" type="text" name="referencia" id="referencia" value="<?=$referencia?>" size="8" maxlength="20">
            <a href="javascript: fnc_pesquisa_peca_2 ($('input[name=referencia]').val(), '')"><img src='imagens/lupa.png' style="cursor: pointer" /></a>
        </td>
        <td style="padding:10px 0 0 0;">
            Descrição <br /><input class="frm" type="text" name="descricao"  id="descricao" value="<?=$descricao?>" size="30" maxlength="50">
            <a href="javascript: fnc_pesquisa_peca_2 ('',$('input[name=descricao]').val())"><img src='imagens/lupa.png' style="cursor: pointer" /></a>
        </td>
    </tr>
    <tr>
        <td width="10%">&nbsp;</td>
        <td colspan="2" style="padding:10px 0 0 0;">
            <fieldset style="width:250px;">
                <legend>Tipo Estoque</legend>
                <input type="radio" name="tipo" value="garantia" <?php if($tipo == "garantia"){ echo "checked";} ?>>Estoque Garantia &nbsp;&nbsp;
                <input type="radio" name="tipo" value="faturada" <?php if($tipo == "faturada"){ echo "checked";} ?>>Estoque Faturada
            </fieldset>
        </td>
    </tr>
    <tr>
        <td colspan="3" align="center">
            <input type="submit" name="btn_acao" value="Pesquisar">
        </td>
    </tr>
    <tr>
        <td colspan="3">&nbsp;</td>
    </tr>
</table>
</form>
<?
$btn_acao = $_POST['btn_acao'];
if (strlen($btn_acao)>0){
    $referencia = $_POST['referencia'];
    $descricao  = $_POST['descricao'];
    $tipo       = $_POST['tipo'];
}
$sql = "SELECT  DISTINCT
                tbl_peca.referencia     ,
                tbl_peca.peca           ,
                tbl_peca.descricao      ,
                tbl_estoque_posto.qtde  ,
                (
                    SELECT  tbl_faturamento.emissao - current_date
                    FROM    tbl_faturamento
                    JOIN    tbl_estoque_posto_movimento on tbl_estoque_posto_movimento.faturamento = tbl_faturamento.faturamento
                    WHERE   tbl_estoque_posto.peca                  = tbl_estoque_posto_movimento.peca
                    AND     tbl_estoque_posto_movimento.faturamento IS NOT NULL
              ORDER BY      emissao DESC
                    LIMIT   1
                ) AS emissao_dias       ,
                (
                    SELECT  to_char(tbl_faturamento.emissao,'DD/MM/YYYY')
                    FROM    tbl_faturamento
                    JOIN    tbl_estoque_posto_movimento on tbl_estoque_posto_movimento.faturamento = tbl_faturamento.faturamento
                    WHERE   tbl_estoque_posto.peca                  = tbl_estoque_posto_movimento.peca
                    AND     tbl_estoque_posto_movimento.faturamento IS NOT NULL
              ORDER BY      emissao DESC
                    LIMIT   1
                ) AS emissao
        FROM    tbl_estoque_posto
        JOIN    tbl_peca on tbl_estoque_posto.peca = tbl_peca.peca
        WHERE   tbl_estoque_posto.posto     = $login_posto
        AND     tbl_estoque_posto.fabrica   = $login_fabrica
";
if(strlen($referencia) > 0){
    $sql .= "
        AND     tbl_peca.referencia ILIKE ('%$referencia%')
    ";
}
if(strlen($descricao) > 0){
    $sql .= "
        AND     tbl_peca.descricao ILIKE ('%$descricao%')
    ";
}
if(strlen($tipo) > 0){
    if($tipo == "garantia"){
        $sql .= "
        AND     tbl_estoque_posto.tipo = 'garantia'
        ";
    }elseif($tipo == "faturada"){
        $sql .= "
        AND     tbl_estoque_posto.tipo = 'faturada'
        ";
    }
}else{
    $sql .= "
        AND     tbl_estoque_posto.tipo IN ('garantia','faturada')
    ";
}
$sql .= "
  ORDER BY      tbl_peca.descricao";
        //tbl_estoque_posto.qtde > 0
$res = pg_exec ($con,$sql);
//echo $sql;
if(pg_numrows($res)>0){
?>
<br />
<table width="700" border="0" cellspacing="1" cellpadding="3" align='center' class="tabela">
        <tr class="titulo_coluna">
            <td>Pe&ccedil;a</td>
            <td>Saldo</td>
        </tr>
<?
    for($x=0;pg_numrows($res)>$x;$x++){
        $peca            = pg_result($res,$x,peca);
        $peca_referencia = pg_result($res,$x,referencia);
        $peca_descricao  = pg_result($res,$x,descricao);
        $qtde            = pg_result($res,$x,qtde);
        $emissao         = pg_result($res,$x,emissao);
        $emissao_dias    = pg_result($res,$x,emissao_dias);

        if($cor == "#efeeea")$cor = "#d2d7e1";
        else $cor = "#efeeea";

        $devolve = false;

        if(strlen($emissao_dias) > 0 AND $emissao_dias < -59) {$cor="#efc7ac"; $devolve = true;}
?>
        <tr>
            <td bgcolor='<? echo $cor;?>'>
                 <a href="javascript:mostraMovimentacao(<?=$peca?>);"><?=$peca_referencia?> - <?=$peca_descricao?></a>
            </td>
            <td align='center' class='table_line1' bgcolor='<? echo $cor;?>'>
                <?echo $qtde;?>
                <input type='hidden' id='qtde_pendente_<? echo $x; ?>' name='qtde_pendente_<? echo $x; ?>' value='<? echo $qtde; ?>'>
            </td>
        </tr>
        <tr id='dados_<? echo $peca; ?>' style="display:none;">
            <td>
                <input type='hidden' id='peca_<? echo $x; ?>' name='peca_<? echo $x; ?>' value='<? echo $peca; ?>'>
            </td>
        </tr>
        
<?
    }
} ?>
<input type='hidden' name='qtde_pedidos' value='<? echo $x; ?>'>
</table>

</form>
</td>
</tr>
</table>
<p>

<? include "rodape.php"; ?>
