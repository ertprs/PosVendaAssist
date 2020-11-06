<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';

$layout_menu = 'callcenter';
$title = "PESQUISA DE SATISFAÇÃO ENVIADAS POR E-MAIL";
include 'cabecalho.php';

if (isset($_POST)){
    $data_inicial   = $_POST['data_inicial'];
    $data_final     = $_POST['data_final'];
    $pesquisa_radio = $_POST['pesquisa'];

    list($di, $mi, $yi) = explode("/", $data_inicial);
    list($df, $mf, $yf) = explode("/", $data_final);
    $aux_data_inicial = "$yi-$mi-$di 00:00:00";
    $aux_data_final   = "$yf-$mf-$df 23:59:59";
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
<script src="js/jquery-1.8.3.min.js"></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript" src="js/jquery.maskedinput2.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<script src="js/highcharts_4.0.3.js"></script>
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
            url: "relatorio_pesquisa_laudo_tecnico_ajax.php",
            data: "ajax=true&validar=true&enviado_email=true&"+$('form[name=frm_pesquisa_laudo_tecnico]').find('input').serialize(),
            complete: function(http) {
                results = http.responseText;
                results = results.split('|');
                if (results[0] == 1){

                    $('div.msg_erro').  html(results[1]);

                }else{
                    $('form[name=frm_pesquisa_laudo_tecnico]').submit();
                }
            }

        });
    });
    $("#div_26").toggle('slow');
    // $(".divShowChart").click(function(){
    //     // pesquisa = $(this).attr('rel');
    //     $("#div_26").toggle('slow');
    // });

    $('#PesquisaOSEmail').click(function(){
        $('#opcao_pesquisa').show();
    });

    $('#PesquisaCallCenter').click(function(){
        $('#opcao_pesquisa').hide();
    });

});

function createChart(respondido_sim, respondido_nao){
    chart = new Highcharts.Chart({
        chart: {
            renderTo: 'div_26',
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
            marginLeft: 100,
            height: 400,
            width: 600
        },
        title: {
            text: "Pesquisas de Satisfação Enviadas por E-mail"
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.name}</b>: {point.percentage:.1f} %',
                    style: {
                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                    }
                }
            }
        },
        series: [{
            type: 'pie',
            name: 'Pesquisas de Satisfação Enviadas por E-mail',
            data: [
                ['Respondido', respondido_sim],
                ['Não respondido', respondido_nao]
            ]
        }]
    });
}
</script>

<div class="msg_erro"></div>
<div class="sucesso"></div>

<form action="<?=$PHP_SELF?>" method="post" name="frm_pesquisa_laudo_tecnico">
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
    <tr>
        <td>&nbsp;</td>
        <td colspan="2">
            <fieldset>
                <legend>Pesquisa:</legend>
                <input type="radio" name="pesquisa" id="PesquisaOSEmail" <?php echo $pesquisa == "os_email" ? 'checked' : ''; ?> value="os_email" > <label for="PesquisaOsEmail">Pesquisa de satisfação</label>
                <select name="opcao_pesquisa" id="opcao_pesquisa" style="margin-left: 20px; display: none;">
                    <option value="detalhada" <?php echo ($opcao_pesquisa == "") ? "selected" : ($opcao_pesquisa == "detalhada") ? "selected" : ""; ?>>Pesquisa Detalhada</option>
                    <option value="simplificada" <?php echo ($opcao_pesquisa == "simplificada") ? "selected" : ""; ?>>Pesquisa Simplificada</option>
                </select>
                <br>
                <input type="radio" name="pesquisa" id="PesquisaCallCenter" <?php echo $pesquisa == "call_center" ? 'checked' : ''; ?> value="call_center" > <label for="PesquisaCallCenter">Call-Center</label>
                <br>
            </fieldset>
        </td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td colspan='6'>&nbsp;</td>
    </tr>
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
    if($pesquisa_radio == "os_email"){

        $opcao_pesquisa = $_POST["opcao_pesquisa"];

        $pesquisa = '%"language":"pt"%';

        $sql = "SELECT tbl_os.os,
                tbl_laudo_tecnico_os.titulo,
                tbl_os.sua_os,
                tbl_posto_fabrica.codigo_posto,
                CASE WHEN titulo ILIKE '%Pesquisa de%' THEN 'SIM' ELSE '' END AS respondido,
                tbl_laudo_tecnico_os.data,
                to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
                to_char(tbl_laudo_tecnico_os.data,'DD/MM/YYYY') AS data_resposta,
                (DATE(tbl_laudo_tecnico_os.data) - DATE(tbl_os.data_fechamento)) AS tempo_resposta
            FROM tbl_os
                JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
                LEFT JOIN tbl_laudo_tecnico_os ON tbl_laudo_tecnico_os.os = tbl_os.os AND tbl_laudo_tecnico_os.observacao LIKE '$pesquisa'
                JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            WHERE tbl_os.data_fechamento BETWEEN '$yi-$mi-$di' AND '$yf-$mf-$df'
                AND enviar_email IS TRUE
            ORDER BY tbl_os.data_fechamento";

        // echo nl2br($sql);
        $res = pg_query($con,$sql);

        if (pg_num_rows($res)>0) {
            $nao_enviado = 0;
            $enviado_pesquisa = 0;
            $pesquisa_sim = 0;
            $pesquisa_nao = 0;

            for($i=0;$i<pg_num_rows($res);$i++){
                $resposta   = pg_fetch_result($res,$i,respondido);

                if($resposta == ""){
                    $respondida_pesquisa = "NÃO";
                    $pesquisa_nao++;
                }else{
                    $respondida_pesquisa = "SIM";
                    $pesquisa_sim++;
                }
            }
            $total = $pesquisa_sim + $pesquisa_nao;

            $pesquisa_sim = ($pesquisa_sim/$total)*100;
            $pesquisa_nao = ($pesquisa_nao/$total)*100;

            $pesquisaChart = "26";
            $pesquisaDescChart = "Pesquisas de Satisfação Enviadas por E-mail";
            echo "<div id='showChart_$pesquisaChart' rel='$pesquisaChart' class='divShowChart' style='cursor:pointer;margin:auto;text-align:center;width:700px'>
            <p class='titulo_tabela'>Gráfico: $pesquisaDescChart</p><div style='margin:auto;display:none' id='div_$pesquisaChart' class='div_$pesquisaChart'></div></div>";
            echo '<script>createChart('.$pesquisa_sim.','.$pesquisa_nao.');</script>';
        }
        ?>
        <table class="tabela">
            <tr class="titulo_coluna">
                <th>O.S.</th>
                <th>Data Fechamento</th>
                <th>Data da Resposta</th>
                <th>Tempo de Resposta</th>
                <!-- <th>Respondido</th> -->
            </tr>
        <?
        if (pg_num_rows($res)>0) {
            $nao_enviado = 0;
            $enviado_pesquisa = 0;
            $pesquisa_sim = 0;
            $pesquisa_nao = 0;
            $total_tempo_resposta = 0;

            for($i=0;$i<pg_num_rows($res);$i++){
                $os = pg_fetch_result($res,$i,os);
                $sua_os = pg_fetch_result($res,$i,sua_os);
                $sua_os = pg_fetch_result($res,$i,codigo_posto).$sua_os;
                $data_fechamento = pg_fetch_result($res,$i,data_fechamento);
                $data_resposta = pg_fetch_result($res,$i,data_resposta);
                $resposta   = pg_fetch_result($res,$i,respondido);
                $tempo_resposta   = pg_fetch_result($res,$i,tempo_resposta);

                if($resposta == ""){
                    $respondida_pesquisa = "NÃO";
                    $pesquisa_nao++;
                }else{
                    $respondida_pesquisa = "SIM";
                    $pesquisa_sim++;
                }

                if(strlen($tempo_resposta)){
                    $total_tempo_resposta += $tempo_resposta;
                }

                if($opcao_pesquisa == "detalhada"){
    ?>
                    <tr>
                        <td><a href="os_press.php?os=<?=$os?>" target='_blank'> <?=$sua_os?></a></td>
                        <td><?=$data_fechamento?></td>
                        <td><?=$data_resposta?></td>
                        <td><?=$tempo_resposta?></td>
                        <!-- <td><?=$respondida_pesquisa?></td> -->
                    </tr>
    <?
                }
            }
            $total = $pesquisa_sim + $pesquisa_nao;
    ?>
            <tr>
                <td colspan="4" style="text-align:right;padding-right:20px;">
                    Total de Pesquisas Enviadas: <?=$total?><br />
                    Respondidas: <?=$pesquisa_sim?><br />
                    Não Respondidas: <?=$pesquisa_nao?><br />
                    Tempo médio de resposta: <?php echo ($total_tempo_resposta > 0) ? number_format($total_tempo_resposta / $pesquisa_sim, 1) : 0;  ?> dia(s)
                </td>
            <tr>
    <?
        }else{
    ?>
            <tr>
                <td colspan="4"><h4>Nenhum atendimento encontrado</h4></td>
            <tr>
    <?
        }
    }else{
        ?>
        <table class="tabela">
            <tr class="titulo_coluna">
                <th>Atendimento</th>
                <th>Data Chamado</th>
                <th>Respondida</th>
            </tr>
        <?
        $sql = "SELECT  DISTINCT tbl_hd_chamado.hd_chamado,
                to_char(email.data,'DD/MM/YYYY')            AS data
            FROM tbl_hd_chamado
            JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
            JOIN tbl_admin ON tbl_hd_chamado.atendente = tbl_admin.admin
            JOIN (SELECT tbl_hd_chamado_item.data, tbl_hd_chamado_item.hd_chamado
                FROM tbl_hd_chamado_item WHERE tbl_hd_chamado_item.enviar_email IS TRUE
            ) AS email ON email.hd_chamado = tbl_hd_chamado.hd_chamado
            WHERE tbl_hd_chamado.status   = 'Resolvido' AND tbl_hd_chamado.fabrica  = $login_fabrica
                AND email.data BETWEEN '$aux_data_inicial' AND '$aux_data_final'
            ORDER BY tbl_hd_chamado.hd_chamado DESC";
        // echo nl2br($sql);
        $res = pg_query($con,$sql);

        if (pg_num_rows($res)>0) {
            $nao_enviado = 0;
            $enviado_pesquisa = 0;
            $pesquisa_sim = 0;
            $pesquisa_nao = 0;
            $total = pg_num_rows($res);
            for($i=0;$i<$total;$i++){
                $hd_chamado = pg_fetch_result($res,$i,hd_chamado);
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
                    $cat = array("'externo'");

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
                $pesquisa_sim = ($pesquisa_sim/$enviado_pesquisa)*100;
                $pesquisa_nao = ($pesquisa_nao/$enviado_pesquisa)*100;

                $pesquisaChart = "26";
                $pesquisaDescChart = "Pesquisas de Satisfação Enviadas por E-mail";
                echo "<div id='showChart_$pesquisaChart' rel='$pesquisaChart' class='divShowChart' style='cursor:pointer;margin:auto;text-align:center;width:1000px'>
                <p class='titulo_tabela'>Gráfico: $pesquisaDescChart</p><div style='margin:auto;display:none' id='div_$pesquisaChart' class='div_$pesquisaChart'></div></div>";
                echo '<script>createChart('.$pesquisa_sim.','.$pesquisa_nao.');</script>';
            }
        }else{
        ?>
            <tr>
                <td colspan="4"><h4>Nenhum atendimento encontrado</h4></td>
            <tr>
        <?
        }
    }

}
?>
    </table>
<?
require_once 'rodape.php';
?>