<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

if($login_fabrica != 20){
    header("location: menu_inicial.php");
    exit;
}

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
    $busca      = $_GET["busca"];

    $sql = "SELECT  tbl_peca.peca,
                    tbl_peca.referencia,
                    CASE WHEN '$login_pais' = 'BR'
                         THEN tbl_peca.descricao
                         ELSE tbl_peca_idioma.descricao
                    END AS descricao
            FROM    tbl_peca
       LEFT JOIN    tbl_peca_idioma ON  tbl_peca_idioma.peca    = tbl_peca.peca
                                    AND tbl_peca_idioma.idioma  = 'ES'
            WHERE   tbl_peca.fabrica = $login_fabrica ";

    if ($busca == "codigo"){
        $sql .= " AND UPPER(tbl_peca.referencia) like UPPER('%$q%') ";
    }else{
        if($login_pais == 'BR'){
            $sql .= " AND UPPER(tbl_peca.descricao) like UPPER('%$q%') ";
        }else{
            $sql .= " AND UPPER(tbl_peca_idioma.descricao) like UPPER('%$q%') ";
        }
    }
    $res = pg_query($con,$sql);
    if (pg_num_rows ($res) > 0) {
        for ($i=0; $i<pg_num_rows ($res); $i++ ){
            $peca       = trim(pg_fetch_result($res,$i,peca));
            $referencia = trim(pg_fetch_result($res,$i,referencia));
            $descricao  = trim(pg_fetch_result($res,$i,descricao));
            echo "$peca|$descricao|$referencia";
            echo "\n";
        }
    }

    exit;
}

if($_POST['ajax']){
    $vitrine_apagar = $_POST['vitrine'];

    $res = pg_query($con, "BEGIN TRANSACTION");
    $sql = "
        DELETE  FROM tbl_vitrine
        WHERE   vitrine = $vitrine_apagar
    ";
    $res = pg_query($con,$sql);

    if(!pg_last_error($res)){
        $res = pg_query($con,"COMMIT TRANSACTION");
        echo "ok";
    }else{
        $res = pg_query($con,"ROLLBACK TRANSACTION");
    }
    exit;
}

$btn_acao = strtolower ($_POST['btn_acao']);

function GravaPecas(){
    global $con, $peca, $login_fabrica, $login_posto, $qtde_peca, $peca_valor;

    $sqlV = "
        SELECT  vitrine, qtde
        FROM    tbl_vitrine
        WHERE   peca    = $peca
        AND     posto   = $login_posto
        --AND     ativo   IS NOT TRUE
        ";
    $resV = pg_query($con,$sqlV);

    if(pg_numrows($resV) > 0 ){
        //$msg_erro = "Já existe peça ativa na vitrine para este posto";

        $vitrine        = pg_fetch_result($resV, 0, vitrine);
        $qtde           = pg_fetch_result($resV, 0, qtde);

        $total_qtde = $qtde + $qtde_peca;
    }

    if(strlen($msg_erro) == 0){
        /**
         * - Gravação da peça e quantidade na vitrine
         * para que outros postos verifiquem
         */

        $res = pg_query($con, "BEGIN TRANSACTION");

        if(strlen($vitrine) == 0){

            $sql = "INSERT INTO tbl_vitrine(
                        posto,
                        peca,
                        qtde, 
                        valor
                    ) VALUES (
                        $login_posto,
                        $peca,
                        $qtde_peca,
                        '$peca_valor'
                    )";
        }else{
            if(strlen(trim($peca_valor))>0){
                $update = ", valor = '$peca_valor' ";
            }
            $sql = "UPDATE tbl_vitrine SET  qtde = $total_qtde $update WHERE vitrine = $vitrine";
        }
        $res = pg_query($con,$sql);
        if(!pg_last_error($con)){
            $res    = pg_query($con,"COMMIT TRANSACTION");
            $retorno['ok']     .= traduz("peca.cadastrada.com.sucesso",$con);
        }else{
            $res = pg_query($con,"ROLLBACK TRANSACTION");
            $retorno['msg_erro'] .= traduz("nao.foi.possivel.realizar.a.gravacao",$con);
        }

        return $retorno;
    }
}

if ($btn_acao == "gravar") {
    $peca               = $_POST['peca'];
    $peca_referencia    = $_POST['peca_referencia'];
    $peca_descricao     = $_POST['peca_descricao'];
    $qtde_peca          = $_POST['qtde_peca'];
    $peca_valor         = $_POST['peca_valor'];
	$peca_valor         = str_replace(",",".",$peca_valor);
    $arquivo            = file($_FILES["upload_files"]['tmp_name']);


    if(strlen($peca_referencia) == 0 || strlen($peca_descricao) == 0 || strlen($qtde_peca) == 0 and (strlen($_FILES["upload_files"]['tmp_name'])) == 0 ){
       $retorno['msg_erro'] .= traduz("preencher.todos.os.campos",$con);
    }   

    if( (strlen($_FILES["upload_files"]['tmp_name']) > 0) and (count($arquivo) == 0 ) ){
        $retorno['msg_erro'] .= traduz("o.arquivo.esta.vazio",$con);
    }

    if((strlen($_FILES["upload_files"]['tmp_name']) > 0) and $_FILES["upload_files"]['type'] != 'text/csv' and $_FILES["upload_files"]['type'] != "text/plain"){
         $retorno['msg_erro'] .= traduz("formato.de.arquivo.invalido",$con);
    }

    if(strlen(trim($retorno['msg_erro']))==0){

        if(strlen(trim($_FILES["upload_files"]['tmp_name']))>0){
            foreach($arquivo as $linha){
                $dados = explode("\t", $linha);

                $peca_referencia    = $dados[0];
                $qtde_peca          = $dados[1];
                $peca_valor         = $dados[2];
                $peca_valor         = str_replace(",",".",$peca_valor);

                $sqlPeca = "select peca from tbl_peca where referencia = '$peca_referencia'";
                $resPeca = pg_query($con, $sqlPeca);
                if(pg_num_rows($resPeca)>0){
                    $peca = pg_fetch_result($resPeca, 0, 'peca');
                }
                $retorno = GravaPecas();
            }
        }else{
            $retorno = GravaPecas();
        }
    }    
}

/**
* - Lista de peças na vitrine
*/

$sqlBusca = "
        SELECT  tbl_vitrine.vitrine ,
                tbl_peca.referencia ,
                CASE WHEN '$login_pais' = 'BR'
                    THEN tbl_peca.descricao
                    ELSE tbl_peca_idioma.descricao
                END AS descricao    ,
                tbl_vitrine.qtde    ,
                tbl_vitrine.ativo   ,
                tbl_vitrine.valor
        FROM    tbl_vitrine
        JOIN    tbl_peca    USING(peca)
   LEFT JOIN    tbl_peca_idioma ON  tbl_peca_idioma.peca    = tbl_peca.peca
                                AND tbl_peca_idioma.idioma  = 'ES'
        WHERE   tbl_vitrine.posto   = $login_posto
        AND     tbl_peca.fabrica    = $login_fabrica
        AND     tbl_vitrine.ativo   IS TRUE
";
$resBusca = pg_query($con,$sqlBusca);

$title = traduz("cadastro.de.pecas.para.venda",$con);
$layout_menu = 'shop_pecas';

include "cabecalho.php";
?>

<script type="text/javascript" src="js/jquery-1.8.3.min.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="js/jquery.autocomplete2.js"></script>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type="text/javascript" src="js/jquery.bgiframe.js"></script>
<script type="text/javascript" src="js/jquery.blockUI_2.39.js"></script>
<script type="text/javascript" src="js/plugin_verifica_servidor.js"></script>

<script type="text/javascript">
function formatItem(row) {
    return row[2] + " - " + row[1];
}

function apagaVitrine(vitrine){
    if(confirm("<?=traduz("Deseja.realmente.retirar.esta.peça.da.vitrine?",$con)?>")){
        $.ajax({
            url:"<?=$PHP_SELF?>",
            type:"POST",
            data:{
                ajax:true,
                vitrine:vitrine
            },
            beforeSend: function(){
                $("#vitrine_"+vitrine).fadeOut("slow");
            }
        })
        .done(function(data){
            if(data == "ok"){
                $("#msg_ajax").fadeIn(200).delay(1000).fadeOut(1000);
            }
        });
    }
}

$(function() {
    $("#peca_descricao").autocomplete("<?echo $PHP_SELF.'?tipo_busca=peca&busca=nome'; ?>", {
        minChars: 3,
        delay: 150,
        width: 350,
        matchContains: true,
        formatItem: formatItem,
        formatResult: function(row) {return row[1];}
    });

    $("#peca_descricao").result(function(event, data, formatted) {
        $("#peca").val(data[0]) ;
        $("#peca_referencia").val(data[2]) ;
    });

    /* Busca pelo Nome */
    $("#peca_referencia").autocomplete("<?echo $PHP_SELF.'?tipo_busca=peca&busca=codigo'; ?>", {
        minChars: 3,
        delay: 150,
        width: 350,
        matchContains: true,
        formatItem: formatItem,
        formatResult: function(row) {return row[2];}
    });

    $("#peca_referencia").result(function(event, data, formatted) {
        $("#peca").val(data[0]) ;
        $("#peca_descricao").val(data[1]) ;
    });
});

</script>

<link rel="stylesheet" href="js/jquery.autocomplete.css"    type="text/css" />

<style type="text/css">
a.lnk:link{
    font-size: 10px;
    font-weight: bold;
    text-decoration: underline;
    color:#FFFF33;
}
a.lnk:visited{
    font-size: 10px;
    font-weight: bold;
    text-decoration: underline;
    color:#FFFF33;
}
.menu_top {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 9px;
    font-weight: bold;
    border: 1px solid;
    color:#596d9b;
    background-color: #d9e2ef
}

.border {
    border: 1px solid #ced7e7;
}

.mensagem{
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 18px;
    font-weight: bold;
    color:#FF0000;
}

.mensagem_ok{
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 18px;
    font-weight: bold;
    color:green;
}

.mensagem#msg_ajax{
    color:#0F0;
    display:none;
}

th{
    background-color:#CCC;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 14px;
}

</style>
<?
if(strlen($retorno['msg_erro']) > 0){
?>
    <span class="mensagem"><?=$retorno['msg_erro']?></span>
<?
}
if(strlen($retorno['ok']) > 0){
?>
    <span class="mensagem_ok" ><?=$retorno['ok']?></span>
<?
}
?>
<div class="mensagem" id="msg_ajax">Peça retirada da vitrine</div>
<!-- ## Início da tabela de cadastro / consulta ## -->
<table id="form" border="0" cellpadding="0" cellspacing="0" style=" text-align:center; background-color:#FFF;width:750px;">
    <tr>
        <td><img height="1" width="20" src="imagens/spacer.gif"></td>
        <td style="vertical-align: top;text-align:center">
            <form name="frm_shop" id="frm_shop" method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="vitrine" id="vitrine" value="<?=$vitrine?>" />
                <input type="hidden" name="peca" id="peca" value="<?=$peca?>" />

                <table border="0" cellspacing="5" cellpadding="0" style="width:100%;text-align:left">
                    <tr>
                        <td>
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif"><?=traduz("codigo.da.peca",$con)?></font>
                            <br />
                            <input type="text" id="peca_referencia" name="peca_referencia" value="<?=$peca_referencia?>" size="15" class="frm" />
                        </td>
                        <td>
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif"><?=traduz("descricao",$con)?></font>
                            <br />
                            <input type="text" id="peca_descricao" name="peca_descricao" value="<?=$peca_descricao?>" size="25" class="frm" />
                        </td>
                        <td>
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif"><?=traduz("qtde",$con)?></font>
                            <br />
                            <input type="text" id="qtde_peca" name="qtde_peca" value="<?=$qtde_peca?>" size="4" maxlength="2" class="frm" />
                        </td>
                        <td>
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif">Valor</font>
                            <br />
                            <input type="text" id="peca_valor" name="peca_valor" value="<?=number_format($peca_valor,2,",",".")?>" size="10" maxlength="10" class="frm" />
                        </td>
                        <td>
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif">Upload (somente .csv e .txt)</font>
                            <br />
                            <input type="file" id="upload_files" name="upload_files" value="Arquivo" size="10" class="frm" />
                        </td>
                        <td>
                            <br>
                            <img src='imagens/help.png' title='<?=traduz("clique.aqui.para.obter.informacoes.do.layout.do.arquivo",$con)?>' onclick='mostrarLayoutArquivo()'>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="5" style="text-align:center">
                            <input type="hidden" name="btn_acao" value="">
                            <img src='imagens/btn_gravar.gif' name="nome_frm_os" onclick="javascript: if (document.frm_shop.btn_acao.value == '' ) { document.frm_shop.btn_acao.value='gravar' ;$('#frm_shop').submit(); }" ALT="Gravar peças para venda" border='0' style="cursor:pointer;">
                        </td>
                    </tr>
                </table>
            </form>
        </td>
        <td><img height="1" width="16" src="imagens/spacer.gif"></td>
    </tr>
</table>
<!-- ## Fim da tabela de cadastro / consulta ## -->

<br />

<!-- ## Início da tabela de peças cadastradas ## -->
<table id="result" border="0" cellpadding="0" cellspacing="0" style=" text-align:center; background-color:#FFF;width:750px;">
    <thead>
        <tr>
            <th>Cod.</th>
            <th><?=traduz("peca",$con)?></th>
            <th><?=traduz("qtde",$con)?></th>
            <th>Valor</th>
            <th>&nbsp;</th>
        </tr>
    </thead>
    <tbody>
<?
    if(pg_numrows($resBusca) > 0){
        for($i=0;$i<pg_numrows($resBusca);$i++){
            $peca_vitrine               = pg_fetch_result($resBusca,$i,vitrine);
            $peca_referencia_vitrine    = pg_fetch_result($resBusca,$i,referencia);
            $peca_descricao_vitrine     = pg_fetch_result($resBusca,$i,descricao);
            $peca_qtde_vitrine          = pg_fetch_result($resBusca,$i,qtde);
            $peca_ativo_vitrine         = pg_fetch_result($resBusca,$i,ativo);
            $peca_valor                 = number_format(pg_fetch_result($resBusca,$i,valor), 2, ',', '.');


            $cor = ($i % 2 == 0) ? "background-color: #FFF" : "background-color: #FFC";
?>
        <tr id="vitrine_<?=$peca_vitrine?>" style="<?=$cor?>">
            <td><?=$peca_referencia_vitrine?></td>
            <td><?=$peca_descricao_vitrine?></td>
            <td><?=$peca_qtde_vitrine?></td>
            <td><?=$peca_valor?></td>
            <td style="margin-left:10px;">
                <img id="excluir" border="0" title="Excluir" src="imagens/btn_excluir.gif" onclick="javascript:apagaVitrine(<?=$peca_vitrine?>);" style="display: block; cursor: pointer;">
            </td>
        </tr>
<?
        }
    }else{
?>
        <tr>
            <td colspan="4"><h6><?=traduz("nenhuma.peca.colocada.na.vitrine",$con)?></h6></td>
        </tr>
<?
    }
?>
    </tbody>
</table>
<!-- ## Fim da tabela de peças cadastradas ## -->

<? include "rodape.php";?>
