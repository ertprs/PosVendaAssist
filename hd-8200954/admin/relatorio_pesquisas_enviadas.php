<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';

$layout_menu = 'callcenter';
$title = "PESQUISA DE PERGUNTAS ENVIADAS AO CONSUMIDOR";
include 'cabecalho.php';

if (isset($_POST)){

    //RECEBE PARAMETROS PARA PESQUISA
    $data_inicial  = $_POST['data_inicial'];
    $data_final    = $_POST['data_final'];
    $pesquisa = ($_POST['pesquisa'] <> 'TODOS') ? $_POST['pesquisa'] : '' ;

    list($di, $mi, $yi) = explode("/", $data_inicial);

    list($df, $mf, $yf) = explode("/", $data_final);

    $aux_data_inicial = "$yi-$mi-$di 00:00:00";
    $aux_data_final   = "$yf-$mf-$df 23:59:59";

    $conditionPesquisa = (!empty($pesquisa)) ? " AND tbl_pesquisa.pesquisa = $pesquisa " : '' ;

}
?>


<style type="text/css">
    @import "../plugins/jquery/datepick/telecontrol.datepick.css";
    .formulario{
        background-color:#D9E2EF;
        font:11px Arial;
        text-align:left;
        margin:auto;
        width:700px;
    }

    .msg_erro{
        background-color:#FF0000;
        font: bold 16px "Arial";
        color:#FFFFFF;
        width:700px;
        margin:auto;
        text-align:center;
    }

    .sucesso{
        background-color:#008000;
        font: bold 14px "Arial";
        color:#FFFFFF;
        width:700px;
        margin:auto;
        text-align:center;
    }

    .titulo_tabela{
        background-color:#596d9b;
        font: bold 14px "Arial";
        color:#FFFFFF;
        text-align:center;
    }

    .titulo_coluna{
        background-color:#596d9b !important;
        font: bold 11px "Arial";
        color:#FFFFFF;
        text-align:center;
    }

    table.tabela{
        width:700px;
        margin:auto;
        background-color: #F7F5F0;
    }

    table.tabela tr td{
        font-family: verdana;
        font-size: 11px;
        border-collapse: collapse;
        border:1px solid #596d9b;
    }

    .hideTr{
        display:none;
    }

</style>

<script src="js/jquery-1.6.1.min.js"></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript" src="js/jquery.maskedinput2.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<script src="js/highcharts.js"></script>
<script src="js/exporting.js"></script>
<script type="text/javascript">

    $(function() {

        $('#data_inicial').datepick({startDate:'01/01/2000'});
        $('#data_final').datepick({startDate:'01/01/2000'});
        $("#data_inicial").maskedinput("99/99/9999");
        $("#data_final").maskedinput("99/99/9999");

        //ENVIA PARA O PROGRAMA DO AJAX VALIDAR O FORM
        $('#btn_pesquisa').click(function(){

            $.ajax({
                type: "GET",
                url: "relatorio_pesquisas_chamado_ajax.php",
                data: "ajax=true&validar=true&"+$('form[name=frm_pesquisa]').find('input').serialize(),
                complete: function(http) {
                    results = http.responseText;
                    results = results.split('|');
                    if (results[0] == 1){

                        $('div.msg_erro').html(results[1]);

                    }else{
                        $('form[name=frm_pesquisa]').submit();
                    }
                }

            });
        });
    });
</script>

<div class="msg_erro"></div>
<div class="sucesso"></div>

<form action="<?=$PHP_SELF?>" method="post" name="frm_pesquisa">
<input type="hidden" name="posto" id="posto" value="<?=$posto?>">
<table class="formulario">
    <tr class="titulo_tabela">
        <th colspan='6'>Parâmetros de Pesquisa</th>
    </tr>

    <tr>
        <td colspan='6'>&nbsp;</td>
    </tr>

    <tr>
        <td>&nbsp;</td>
        <td>
            <label for="data_inicial">Data Inicial:</label>
            <input type="text" name="data_inicial" id="data_inicial" class='frm' size="12" value="<?=$data_inicial?>">
        </td>
        <td>
            <label for="data_final">Data Final:</label>
            <input type="text" name="data_final" id="data_final" class='frm' size="12" value="<?=$data_final?>">
        </td>
        <td>&nbsp;</td>
    </tr>

        <tr>    <td colspan='6'>&nbsp;</td> </tr>
    <tr>
        <td colspan='6' align="center">
            <input type="button" value="Pesquisar" id="btn_pesquisa">
        </td>
    </tr>

    <tr>    <td colspan='6'>&nbsp;</td> </tr>
</table>
</form>

<br>

<div id="container" class="container"></div>

<?php
if (count($_POST)>0){
?>
    <table class="tabela">
        <tr class="titulo_coluna">
            <th>Atendimento</th>
            <th>Data Chamado</th>
            <th>Atendente</th>
            <th>Respondida</th>
        </tr>
<?
    $sql = "SELECT  DISTINCT
                    tbl_hd_chamado.hd_chamado                               ,
                    tbl_admin.nome_completo                     AS admin_nome ,
                    to_char(email.data,'DD/MM/YYYY')            AS data
            FROM    tbl_hd_chamado
            JOIN    tbl_hd_chamado_extra    ON tbl_hd_chamado.hd_chamado        = tbl_hd_chamado_extra.hd_chamado
            JOIN    tbl_admin               ON tbl_hd_chamado.atendente         = tbl_admin.admin
            JOIN    (
                        SELECT  tbl_hd_chamado_item.data        ,
                                tbl_hd_chamado_item.hd_chamado
                        FROM    tbl_hd_chamado_item
                        WHERE   tbl_hd_chamado_item.enviar_email IS TRUE
                    ) email                 ON email.hd_chamado = tbl_hd_chamado.hd_chamado
            WHERE   tbl_hd_chamado.status   = 'Resolvido'
            AND     tbl_hd_chamado.fabrica  = $login_fabrica
            AND     email.data BETWEEN '$aux_data_inicial' AND '$aux_data_final'
      ORDER BY      tbl_hd_chamado.hd_chamado DESC
    ";
    #echo nl2br($sql);
    $res = pg_query($con,$sql);

    if (pg_num_rows($res)>0) {
        $nao_enviado = 0;
        $enviado_pesquisa = 0;
        $pesquisa_sim = 0;
        $pesquisa_nao = 0;
        $total = pg_num_rows($res);
        for($i=0;$i<$total;$i++){
            $hd_chamado = pg_fetch_result($res,$i,hd_chamado);
            $admin_nome = pg_fetch_result($res,$i,admin_nome);
            $data       = pg_fetch_result($res,$i,data);

            $sqlEnvia = "SELECT DISTINCT
                                CASE WHEN tbl_hd_chamado_item.enviar_email IS TRUE
                                     THEN 'enviado'
                                     ELSE 'nao'
                                END  AS pesquisa_enviada
                        FROM    tbl_hd_chamado_item
                        JOIN    tbl_hd_chamado USING (hd_chamado)
                        WHERE   tbl_hd_chamado.hd_chamado           = $hd_chamado
                        AND     tbl_hd_chamado.fabrica              = $login_fabrica
                        AND     tbl_hd_chamado_item.status_item     = 'Resolvido'
                        AND     tbl_hd_chamado_item.enviar_email    IS NOT NULL
            ";

            $resEnvia = pg_query($con,$sqlEnvia);
            $enviadoArray = pg_fetch_array($resEnvia);
            #var_dump($enviadoArray);
	    if(in_array("enviado",$enviadoArray)){
		if($login_fabrica == 129){
			$cat = array("'externo'","'callcenter'","'ordem_de_servico'");
		}else{
			$cat = array("'externo'");
		}

                $enviado_pesquisa += 1;
                $sqlResp = "SELECT  tbl_resposta.resposta
                            FROM    tbl_resposta
                            JOIN    tbl_pesquisa USING (pesquisa)
                            WHERE   hd_chamado = $hd_chamado
			    AND     categoria IN(".implode(',',$cat).")
                ";

                $resResp = pg_query($con,$sqlResp);
                if(pg_num_rows($resResp) > 0){
                    $respondida_pesquisa = "SIM";
                    $pesquisa_sim += 1;
                }else{
                    $respondida_pesquisa = "NÃO";
                    $pesquisa_nao += 1;
                }
            }else{
                $nao_enviado += 1;
                continue;
            }
?>
        <tr>
            <td><a href="callcenter_interativo_new.php?callcenter=<?=$hd_chamado?>" target='_blank'> <?=$hd_chamado?></a></td>
            <td><?=$data?></td>
            <td><?=$admin_nome?></td>
            <td><?=$respondida_pesquisa?></td>
        </tr>
<?
        }

        if($enviado_pesquisa == 0){
?>
        <tr>
            <td colspan="4"><h4>Nenhum atendimento encontrado</h4></td>
        <tr>
<?
        }else{
?>
        <tr>
            <td colspan="4" style="text-align:right;padding-right:20px;">
                Total de Pesquisas: <?=$enviado_pesquisa?><br />
                Respondidas:<?=$pesquisa_sim?><br />
                Não Respondidas:<?=$pesquisa_nao?>
            </td>
        <tr>
<?
        }
    }else{
?>
        <tr>
            <td colspan="4"><h4>Nenhum atendimento encontrado</h4></td>
        <tr>
<?
    }
}
?>
    </table>
<?
require_once 'rodape.php';
?>
