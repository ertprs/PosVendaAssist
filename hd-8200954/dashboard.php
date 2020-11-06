 <?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'fn_logoResize.php';
$title = "DASHBOARD";
 
include "cabecalho.php";


$sql = "SELECT  tbl_os.os                               ,
                tbl_os.data_digitacao                   ,
                tbl_os.finalizada                       ,
                tbl_status_checkpoint.status_checkpoint ,
                tbl_status_checkpoint.descricao         ,
                tbl_linha.linha                         ,
                tbl_linha.nome              AS linha_nome
   INTO TEMP    status_os
        FROM    tbl_os
        JOIN    tbl_status_checkpoint   USING   (status_checkpoint)
        JOIN    tbl_posto_linha         USING   (posto)
        JOIN    tbl_linha               ON      tbl_linha.linha     = tbl_posto_linha.linha
                                        AND     tbl_linha.fabrica   = $login_fabrica
        JOIN    tbl_produto             ON      tbl_produto.produto = tbl_os.produto
                                        AND     tbl_produto.linha   = tbl_linha.linha
        WHERE   tbl_os.posto   = $login_posto
        AND     tbl_os.fabrica = $login_fabrica
        AND     tbl_os.data_digitacao between current_timestamp - interval '3 months' and current_timestamp
        AND     status_checkpoint in (1,2,3,4,8,9)";

$res = pg_query($con,$sql);

$sql = "SELECT tres_dias, sete_dias,quinze_dias,vintecinco_dias,mais_dias,descricao, status_checkpoint from (

			SELECT  sum(case when data_digitacao between current_timestamp - interval '3 days' and current_timestamp then 1 else 0 end) as tres_dias,
                sum(case when data_digitacao between current_timestamp - interval '7 days' and current_timestamp - interval '4 days' then 1 else 0 end) as sete_dias,
                sum(case when data_digitacao between current_timestamp - interval '15 days' and current_timestamp - interval '8 days' then 1 else 0 end) as quinze_dias,
                sum(case when data_digitacao between current_timestamp - interval '25 days' and current_timestamp - interval '16 days' then 1 else 0 end) as vintecinco_dias,
                sum(case when data_digitacao between current_timestamp - interval '90 days' and current_timestamp - interval '26 days' then 1 else 0 end) as mais_dias,
                descricao,
                status_checkpoint
			FROM    status_os
			WHERE status_checkpoint in (1,2,3,4,8)
		  GROUP BY      descricao ,  status_checkpoint
  union
		  SELECT sum(case when finalizada - data_digitacao between '0 day' and '3 days' then 1 else 0 end) as tres_dias,
				  sum(case when finalizada - data_digitacao between '4 days' and '7 days' then 1 else 0 end) as sete_dias,
				  sum(case when finalizada - data_digitacao between '8 days' and '15 days' then 1 else 0 end) as quinze_dias,
				  sum(case when finalizada - data_digitacao between '16 days' and '25 days' then 1 else 0 end) as vintecinco_dias,
				  sum(case when finalizada - data_digitacao > '25 days' then 1 else 0 end) as mais_dias,
				  descricao,
				  status_checkpoint
		 from status_os where status_checkpoint = 9 GROUP BY descricao , status_checkpoint
) x
  ORDER BY      status_checkpoint";

$res = pg_query($con,$sql);
$resultados = array();
for($i=0;$i<pg_num_rows($res);$i++){
    $tres       = pg_fetch_result($res,$i,0);
    $sete       = pg_fetch_result($res,$i,1);
    $quinze     = pg_fetch_result($res,$i,2);
    $vintecinco = pg_fetch_result($res,$i,3);
    $mais_dias  = pg_fetch_result($res,$i,4);
    $status     = pg_fetch_result($res,$i,5);
    $resultados[] = "
                    {
                        name: '$status',
                        data: [$tres,$sete,$quinze,$vintecinco,$mais_dias]
                    } ";
}
$resultadosArray = implode(",",$resultados) ;

if($_POST['ajax'] == 'sim'){
    $linha = $_POST['linha'];

    $sql = "SELECT  sum(case when data_digitacao between current_timestamp - interval '3 days' and current_timestamp then 1 else 0 end) as tres_dias,
                    sum(case when data_digitacao between current_timestamp - interval '7 days' and current_timestamp - interval '4 days' then 1 else 0 end) as sete_dias,
                    sum(case when data_digitacao between current_timestamp - interval '15 days' and current_timestamp - interval '8 days' then 1 else 0 end) as quinze_dias,
                    sum(case when data_digitacao between current_timestamp - interval '25 days' and current_timestamp - interval '16 days' then 1 else 0 end) as vintecinco_dias,
                    sum(case when data_digitacao between current_timestamp - interval '3 months' and current_timestamp - interval '26 days' then 1 else 0 end) as mais_dias,
                    descricao,
                    status_checkpoint
            FROM    status_os";
    if(strlen($linha) > 0){
        $sql .= "
            WHERE   linha = $linha
        ";
    }
    $sql .= "
      GROUP BY      descricao ,
                    status_checkpoint
      ORDER BY      status_checkpoint";

    $res = pg_query($con,$sql);
    $resultadosNovos = array();
    $contaRes = pg_num_rows($res);
    for($i=0;$i<$contaRes;$i++){
        $tres               = (int)pg_fetch_result($res,$i,0);
        $sete               = (int)pg_fetch_result($res,$i,1);
        $quinze             = (int)pg_fetch_result($res,$i,2);
        $vintecinco         = (int)pg_fetch_result($res,$i,3);
        $mais_dias          = (int)pg_fetch_result($res,$i,4);
        $status             = pg_fetch_result($res,$i,5);
        $status             = htmlentities($status);
        $resultadosNovos[]  = array("nome" => $status,"data" => array($tres,$sete,$quinze,$vintecinco,$mais_dias));
    }

    $sql = "SELECT  sum(case when finalizada isnull then 1 else 0 end) as abertas,
                    count(1)
            FROM    status_os";
    if(strlen($linha) > 0){
        $sql .= "
            WHERE   linha = $linha
        ";
    }
    $res = pg_query($con,$sql);
    if(pg_num_rows($res) > 0) {
        $abertas    = (int)pg_fetch_result($res,0,0);
        $total      = (int)pg_fetch_result($res,0,1);
        $fechadas   = $total - $abertas;
    }
    $resultadosPizza = array("Abertas" => $abertas,"Fechadas" => $fechadas);
    array_push($resultadosNovos,$resultadosPizza);
    $resultadosArrayNovos = json_encode($resultadosNovos);
    echo $resultadosArrayNovos;
    exit;
}

$sql = "SELECT  sum(case when finalizada isnull then 1 else 0 end) as abertas,
                count(1)
        FROM    status_os";
$res = pg_query($con,$sql);
if(pg_num_rows($res) > 0) {
    $abertas  = (int)pg_fetch_result($res,0,0);
    $total    = (int)pg_fetch_result($res,0,1);
    $fechadas = $total - $abertas;
}

$sqlLinha = "   SELECT  DISTINCT
                        linha       ,
                        linha_nome
                FROM    status_os
";
$resLinha = pg_query($con,$sqlLinha);


$sql = "SELECT  count(1) AS pedidos,
                SUM(
                    CASE WHEN status_pedido NOT IN (4,14)
                         THEN 1
                         ELSE 0
                    END
                ) AS pendente
        FROM    tbl_pedido
        WHERE   fabrica = $login_fabrica
        AND     posto   = $login_posto
        AND     data BETWEEN (CURRENT_TIMESTAMP - INTERVAL '3 months') and CURRENT_TIMESTAMP";
$res = pg_query($con,$sql);
if(pg_num_rows($res) > 0){
    $pedidos    = (int)pg_fetch_result($res,0,pedidos);
    $pendente   = (int)pg_fetch_result($res,0,pendente);
    $finalizadas = $pedidos - $pendente;
}

$sql = "SELECT  senha_financeiro
        FROM    tbl_posto_fabrica
        WHERE   tbl_posto_fabrica.posto     = $login_posto
        AND     tbl_posto_fabrica.fabrica   = $login_fabrica
        AND     senha_financeiro            IS NOT NULL
        AND     LENGTH(senha_financeiro)    > 0
";
$res = pg_query($con,$sql);
if(pg_num_rows($res) > 0){
    $senha_financeiro = pg_fetch_result($res,0,senha_financeiro);
    $esconder = "SIM";
}else{
    $esconder = "NAO";
}
function trocaMes($valor){
    switch($valor){
        case 1:
            return "Janeiro";
        break;
        case 2:
            return "Fevereiro";
        break;
        case 3:
            return "Março";
        break;
        case 4:
            return "Abril";
        break;
        case 5:
            return "Maio";
        break;
        case 6:
            return "Junho";
        break;
        case 7:
            return "Julho";
        break;
        case 8:
            return "Agosto";
        break;
        case 9:
            return "Setembro";
        break;
        case 10:
            return "Outubro";
        break;
        case 11:
            return "Novembro";
        break;
        case 12:
            return "Dezembro";
        break;
    }
}

$join_conferencia = '';
if ($login_fabrica == '3') {
	$join_conferencia = ' JOIN tbl_extrato_conferencia USING (extrato)
                          JOIN    tbl_extrato_conferencia_item    USING (extrato_conferencia)
						  ';
	$campo_mo = "0";
	$campo_extrato = "tbl_extrato_conferencia_item";
	$campo_data = "data_conferencia";
    $cond = "AND     cancelada           IS NOT TRUE";
}else{
	$campo_mo = "0" ;
	$campo_data = "data_geracao";
	$campo_extrato = "tbl_extrato";
}

if($login_fabrica == 19){
    $campo_tabela = 'total';
}else{
    $campo_tabela = 'mao_de_obra';
}

$sql = "SELECT  SUM(CASE WHEN $campo_data BETWEEN current_timestamp - INTERVAL '1 month'  AND current_timestamp                        THEN $campo_extrato.$campo_tabela ELSE $campo_mo END)   AS extrato_hoje         ,
                SUM(CASE WHEN $campo_data BETWEEN current_timestamp - INTERVAL '2 months' AND current_timestamp - INTERVAL '1 month'   THEN $campo_extrato.$campo_tabela ELSE $campo_mo END)   AS extrato_mes          ,
                SUM(CASE WHEN $campo_data BETWEEN current_timestamp - INTERVAL '3 months' AND current_timestamp - INTERVAL '2 months'  THEN $campo_extrato.$campo_tabela ELSE $campo_mo END)   AS extrato_dois_meses   ,
                SUM(CASE WHEN $campo_data BETWEEN current_timestamp - INTERVAL '4 months' AND current_timestamp - INTERVAL '3 months'  THEN $campo_extrato.$campo_tabela ELSE $campo_mo END)   AS extrato_tres_meses   ,
                EXTRACT(month FROM current_timestamp)                                                                                                                                                   AS mes_atual            ,
                EXTRACT(month FROM current_timestamp - INTERVAL '1 months')                                                                                                                             AS mes_primeiro         ,
                EXTRACT(month FROM current_timestamp - INTERVAL '2 months')                                                                                                                             AS mes_segundo          ,
                EXTRACT(month FROM current_timestamp - INTERVAL '3 months')                                                                                                                             AS mes_terceiro
        FROM    tbl_extrato
        $join_conferencia
        WHERE   tbl_extrato.fabrica = $login_fabrica
		AND     tbl_extrato.posto   = $login_posto
		$cond
";
$res = pg_query($con,$sql);

$sqlConf = "
        SELECT  SUM(CASE WHEN data_conferencia BETWEEN current_timestamp - INTERVAL '1 month'  AND current_timestamp                        THEN tbl_extrato_lancamento.valor ELSE 0 END)   AS lancamento_hoje         ,
                SUM(CASE WHEN data_conferencia BETWEEN current_timestamp - INTERVAL '2 months' AND current_timestamp - INTERVAL '1 month'   THEN tbl_extrato_lancamento.valor ELSE 0 END)   AS lancamento_mes          ,
                SUM(CASE WHEN data_conferencia BETWEEN current_timestamp - INTERVAL '3 months' AND current_timestamp - INTERVAL '2 months'  THEN tbl_extrato_lancamento.valor ELSE 0 END)   AS lancamento_dois_meses   ,
                SUM(CASE WHEN data_conferencia BETWEEN current_timestamp - INTERVAL '4 months' AND current_timestamp - INTERVAL '3 months'  THEN tbl_extrato_lancamento.valor ELSE 0 END)   AS lancamento_tres_meses
        FROM    tbl_extrato
        JOIN    tbl_extrato_conferencia USING (extrato)
   LEFT JOIN    tbl_extrato_lancamento  ON  tbl_extrato_lancamento.extrato = tbl_extrato.extrato
                                        AND debito_credito = 'C'
        WHERE   tbl_extrato.fabrica = $login_fabrica
        AND     tbl_extrato.posto   = $login_posto
        and     cancelada           IS NOT TRUE
        AND     (
                    tbl_extrato_conferencia.admin IS NOT NULL
                OR  lancamento IN (103,104)
                );
";
#echo nl2br($sqlConf);exit;
$resConf = pg_query($con,$sqlConf);

$extrato_hoje       = (float)pg_fetch_result($res,0,extrato_hoje) + (float)pg_fetch_result($resConf,0,lancamento_hoje);
$extrato_mes        = (float)pg_fetch_result($res,0,extrato_mes) + (float)pg_fetch_result($resConf,0,lancamento_mes);
$extrato_dois_meses = (float)pg_fetch_result($res,0,extrato_dois_meses) + (float)pg_fetch_result($resConf,0,lancamento_dois_meses);
$extrato_tres_meses = (float)pg_fetch_result($res,0,extrato_tres_meses) + (float)pg_fetch_result($resConf,0,lancamento_tres_meses);
$mes_atual          = pg_fetch_result($res,0,mes_atual);
$mes_primeiro       = pg_fetch_result($res,0,mes_primeiro);
$mes_segundo        = pg_fetch_result($res,0,mes_segundo);
$mes_terceiro       = pg_fetch_result($res,0,mes_terceiro);

$extrato_resultado  = array($extrato_tres_meses,$extrato_dois_meses,$extrato_mes,$extrato_hoje);
$extrato_valor      = json_encode($extrato_resultado);

$atual_mes          = trocaMes($mes_atual);
$primeiro_mes       = trocaMes($mes_primeiro);
$segundo_mes        = trocaMes($mes_segundo);
$terceiro_mes       = trocaMes($mes_terceiro);

$meses = "'$terceiro_mes','$segundo_mes','$primeiro_mes','$atual_mes'";
$sqlTipo = "SELECT tbl_posto_fabrica.tipo_posto
            FROM   tbl_posto_fabrica
            WHERE  posto    = $login_posto
            AND    fabrica  = $login_fabrica
";
$resTipo = pg_query($con,$sqlTipo);
$tipo_posto = pg_fetch_result($resTipo,0,tipo_posto);

$sql = "SELECT  DISTINCT
                tbl_comunicado.comunicado                           ,
                tbl_comunicado.mensagem                             ,
                to_char(tbl_comunicado.data, 'DD/MM/YYYY') AS data  ,
                tbl_comunicado.tipo                                 ,
                tbl_comunicado.data
        FROM    tbl_comunicado
   LEFT JOIN    tbl_comunicado_produto  ON tbl_comunicado.comunicado = tbl_comunicado_produto.comunicado
        WHERE   tbl_comunicado.fabrica = $login_fabrica
        AND     tbl_comunicado.obrigatorio_site
        AND     (
                    tbl_comunicado.tipo_posto = $tipo_posto
                OR  tbl_comunicado.tipo_posto IS NULL
                )
        AND     (
                    tbl_comunicado.posto IS NULL
                OR  tbl_comunicado.posto = $login_posto
                )
        AND     (
                    tbl_comunicado.linha IN (
                        SELECT  tbl_linha.linha
                        FROM    tbl_posto_linha
                        JOIN    tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha
                        WHERE   fabrica = $login_fabrica
                        AND     posto   = $login_posto
                  ORDER BY      linha
                    )
                OR  tbl_comunicado.linha IS NULL
                )
        AND     tbl_comunicado.ativo IS TRUE
  ORDER BY      tbl_comunicado.data DESC
        LIMIT   6
";
$res = pg_query($con,$sql);
for($i=0;$i<pg_num_rows($res);$i++){
    $comunicado             = pg_fetch_result($res,$i,comunicado);
    $comunicado_mensagem    = htmlentities(pg_fetch_result($res,$i,mensagem));
    $comunicado_tipo        = htmlentities(pg_fetch_result($res,$i,tipo));
    $comunicado_data        = pg_fetch_result($res,$i,data);
    $resultadosComunicados[$comunicado] = array("mensagem"=>$comunicado_mensagem,"data"=>$comunicado_data,"tipo"=>$comunicado_tipo);
}
$resultadosArrayComunicados = json_encode($resultadosComunicados);

?>
<html>
<head><title>DASHBOARD</title>
<link href="admin/bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="admin/bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="admin/css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="admin/css/tooltips.css" type="text/css" rel="stylesheet" />
<link href="admin/plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
<link href="admin/bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

<script src="admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script type="text/javascript" src="admin/plugins/fixedtableheader/jquery.fixedtableheader.min.js"></script>
<script src="admin/js/novo_highcharts.js"></script>
<script src="admin/js/modules/exporting.js"></script>
<script src="admin/bootstrap/js/bootstrap.js"></script>

<script type="text/javascript">
var hora = new Date();
var engana = hora.getTime();
<?
if(strlen($senha_financeiro) > 0){
?>
function senhaFinanceiro(){
    var senha = document.getElementById("senha_financeiro").value;
    if(senha == '<?=$senha_financeiro?>'){
        document.getElementById("senha_extrato").style.display="none";
        document.getElementById("extratos_chart").style.opacity=1;
    }else{
        document.getElementById("msg").style.display="block";
        document.getElementById("msg").style.color="#F00";
        document.getElementById("senha_financeiro").value="";
    }
}
<?
}
?>
function chartOs(){
    $('#os_chart').highcharts({
        chart: {
            borderColor: '#CCC',
            borderWidth: 2,
            type: 'column'
        },
        title: {

            text: '',
            useHTML: true
        },
        style:{
            visibility:'hidden'
        },
        navigation: {
            buttonOptions: {
                enabled: false
            }
        },
        credits: {
            enabled: false
        },

        xAxis: {
            categories: [
                '0-3 dias'  ,
                '4-7 dias'  ,
                '8-15 dias' ,
                '16-25 dias',
                '> 25 dias'
            ]
        },
        yAxis: {
            minorTickInterval: 'auto',
            minorTickLength: 0,
            min: 0,
            title: {
                text: 'OSs'
            }
        },
        tooltip: {
            headerFormat: '<span style="font-size:10px;width:150px">{point.key}</span><table style="width:150px;">',
            pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                '<td style="padding:0" nowrap><b>{point.y} OS</b></td></tr>',
            footerFormat: '</table>',
            shared: true,
            useHTML: true
        },
        plotOptions: {
            column: {
                pointPadding: 0.2,
                borderWidth: 0,
                dataLabels:{
                    enabled: true,
                    format: '{y}'
                }
            },
            series:{
                cursor: 'pointer',
                point:{
                    events:{
                        click: function(){
                            var data                = this.category;
                            var status              = this.series.name;
                            var status_pie          = this.name;
                            var tipo                = this.series.type;
                            var data_inicial        = new Date();
                            var data_final          = new Date();
                            var linha_id            = $('#linha').val();
                            var status_checkpoint;
                            var dia_inicial;
                            var mes_inicial;
                            var ano_inicial;
                            var dia_final;
                            var mes_final;
                            var ano_final;
							var os_aberta = '';
							var tipo_fechada;

                            if(tipo == 'column'){
                                switch(status){
                                    case 'Aguardando Analise':
                                        status_checkpoint = 1;
                                    break;
                                    case 'Aguardando Peças':
                                        status_checkpoint = 2;
                                    break;
                                    case 'Aguardando Conserto':
                                        status_checkpoint = 3;
                                    break;
                                    case 'Aguardando Retirada':
                                        status_checkpoint = 4;
                                    break;
                                    case 'Finalizada':
                                        status_checkpoint = 9;
                                    break;
                                }

                                var inicial_format;
                                var final_format;

                                switch(data){
                                    case '0-3 dias':
										data_inicial.setDate(data_inicial.getDate()-3);
										tipo_fechada = 1;
                                    break;

                                    case '4-7 dias':
                                        data_inicial.setDate(data_inicial.getDate()-7);
                                        data_final.setDate(data_final.getDate()-4);
										tipo_fechada = 2;
                                    break;
                                    case '8-15 dias':
                                        data_inicial.setDate(data_inicial.getDate()-15);
                                        data_final.setDate(data_final.getDate()-8);
										tipo_fechada = 3;
                                    break;
                                    case '16-25 dias':
                                        data_inicial.setDate(data_inicial.getDate()-25);
                                        data_final.setDate(data_final.getDate()-16);
										tipo_fechada = 4;
                                    break;
                                    case '> 25 dias':
                                        data_inicial.setDate(data_inicial.getDate()-90);
										data_final.setDate(data_final.getDate()-26);
										tipo_fechada = 5;
                                    break;
                                }

                                dia_inicial = data_inicial.getDate();
                                mes_inicial = data_inicial.getMonth()+1;
                                ano_inicial = data_inicial.getFullYear();
                                dia_final = data_final.getDate();
                                mes_final = data_final.getMonth()+1;
                                ano_final = data_final.getFullYear();

                                if(dia_inicial < 10){
                                    dia_inicial = "0"+dia_inicial;
                                }
                                if(dia_final < 10){
                                    dia_final = "0"+dia_final;
                                }

                                if(mes_inicial < 10){
                                    mes_inicial = "0"+mes_inicial;
                                }
                                if(mes_final < 10){
                                    mes_final = "0"+mes_final;
                                }

                                inicial_format = dia_inicial+"/"+mes_inicial+"/"+ano_inicial;
                                final_format   = dia_final  +"/"+mes_final  +"/"+ano_final;
                            }else{
                                data_inicial.setDate(data_inicial.getDate()-90);

                                dia_inicial = data_inicial.getDate();
                                mes_inicial = data_inicial.getMonth()+1;
                                ano_inicial = data_inicial.getFullYear();
                                dia_final = data_final.getDate();
                                mes_final = data_final.getMonth()+1;
                                ano_final = data_final.getFullYear();

                                if(dia_inicial < 10){
                                    dia_inicial = "0"+dia_inicial;
                                }
                                if(dia_final < 10){
                                    dia_final = "0"+dia_final;
                                }

                                if(mes_inicial < 10){
                                    mes_inicial = "0"+mes_inicial;
                                }
                                if(mes_final < 10){
                                    mes_final = "0"+mes_final;
                                }

                                inicial_format = dia_inicial+"/"+mes_inicial+"/"+ano_inicial;
                                final_format   = dia_final  +"/"+mes_final  +"/"+ano_final;

                                if(status_pie == 'Abertas'){
                                    os_aberta = 1;
                                }
                                status_checkpoint = '';
                            }
                            window.open('os_consulta_lite.php?data_inicial='+inicial_format+'&data_final='+final_format+'&status_checkpoint='+status_checkpoint+'&os_aberta='+os_aberta+'&linha='+linha_id+'&btn_acao=1&dash=1&tipo_fechada='+tipo_fechada);
                        }
                    }
                }
            }
        },
        series: [
            <?=$resultadosArray?>  ,
            {
                type: 'pie',
                name: 'Total de OSs',
                tooltip: {
                    pointFormat: '{name}: <b>{point.y} OSs</b>'
                },
                data: [
                    ['Abertas',   <?=$abertas?>],
                    ['Fechadas',<?=$fechadas?>]
                ],
                center:[80,20],
                dataLabels: {
                    enabled: true,
                    formatter:function(){
                        var nome    = this.point.name;
                        var pc      = parseFloat(this.percentage);
                        var novaPc  = Highcharts.numberFormat(pc,2,',','.');
                        return nome+' - '+novaPc+'%';
                    }
                }
            }
        ]
    });
}
$(function () {
    $('select[name=linha]').change(function(){
        var linha_id = $('#linha').val();

        $.ajax({
            url:"<?=$PHP_SELF?>",
            type:"POST",
            dataType:"json",
            data:{
                linha:linha_id,
                ajax:"sim"
            },
            beforeSend:function(){
                var carregando = false;
                var chart = $('#os_chart').highcharts();

                if(!carregando){
                    chart.showLoading("Carregando Linha...");
                }
            }
        })
        .done(function(result){
            var chart           = $('#os_chart').highcharts();
            var nomes           = new Array();
            var nomesMudados    = new Array();
            var idPizza         = 0;
            var idJson          = 0;
            var pizza;
            var data;
            var nome;
            var nomeCorrigido;

            chart.hideLoading();

            $.each(chart.series,function(i,val){
                nomes.push(val.name);
            });
            pizza = nomes.pop();

            for(i=0;i<result.length;i++){
                nome = result[i].nome;
                data = result[i].data;
                nomeCorrigido = $('<div/>').html(nome).text();

                $.each(nomes,function(k,val){
                    if(val == nomeCorrigido){
                        nomesMudados.push(nomeCorrigido);
                        chart.series[k].setData(data);
                    }
                });
            }

            $.each(nomes,function(j,val2){
                if($.inArray(val2,nomesMudados) == -1){
                    chart.series[j].setData([0,0,0,0,0]);
                }
            });

            idPizza = (chart.series.length) - 1;
            idJson = (result.length) - 1;
            chart.series[idPizza].setData([result[idJson].Abertas,result[idJson].Fechadas]);
            chart.series[idPizza].data[0].update({
                name:"Abertas"
            });
            chart.series[idPizza].data[1].update({
                name:"Fechadas"
            });
        });
    });

    chartOs();

    $('#pedidos_chart').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
            borderColor: '#CCC',
            borderWidth: 2
        },
        title: {
            text: '',
            useHTML: true
        },
        navigation: {
            buttonOptions: {
                enabled: false
            }
        },
        credits: {
            enabled: false
        },
        tooltip: {
            headerFormat: '<span style="font-size:10px;width:150px">{point.key}</span><table style="width:150px;">',
            pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                '<td style="padding:0" nowrap><b>{point.y} Pedidos</b></td></tr>',
            footerFormat: '</table>',
            shared: true,
            useHTML: true
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
            },
            series:{
                point:{
                    events:{
                        click: function(){
                            var data_inicial    = new Date();
                            var data_final      = new Date();
                            var status_pie      = this.name;
                            var dia_inicial;
                            var mes_inicial;
                            var ano_inicial;
                            var dia_final;
                            var mes_final;
                            var ano_final;
                            var estado_pedido = '';

                            data_inicial.setDate(data_inicial.getDate()-90);

                            dia_inicial = data_inicial.getDate();
                            mes_inicial = data_inicial.getMonth()+1;
                            ano_inicial = data_inicial.getFullYear();
                            dia_final = data_final.getDate();
                            mes_final = data_final.getMonth()+1;
                            ano_final = data_final.getFullYear();

                            if(dia_inicial < 10){
                                dia_inicial = "0"+dia_inicial;
                            }
                            if(dia_final < 10){
                                dia_final = "0"+dia_final;
                            }

                            if(mes_inicial < 10){
                                mes_inicial = "0"+mes_inicial;
                            }
                            if(mes_final < 10){
                                mes_final = "0"+mes_final;
                            }

                            inicial_format = dia_inicial+"/"+mes_inicial+"/"+ano_inicial;
                            final_format   = dia_final  +"/"+mes_final  +"/"+ano_final;

                            if(status_pie == 'Pendentes'){
                                estado_pedido = 1;
                            }
                            window.open('pedido_relacao.php?btn_acao_pesquisa=continuar&dash=1&estado_pedido='+estado_pedido+'&data_inicial='+inicial_format+'&data_final='+final_format);
                        }
                    }
                }
            }
        },
        series:[{
            type: 'pie',
            name: 'Total de pedidos',
            tooltip: {
                pointFormat: '{name}: <b>{point.y} Pedidos</b>'
            },
            data: [
                ['Pendentes',<?=$pendente?>],
                ['Finalizados',<?=$finalizadas?>]
            ],
            dataLabels: {
                enabled: true,
                formatter:function(){
                    var nome    = this.point.name;
                    var pc      = parseFloat(this.percentage);
                    var novaPc  = Highcharts.numberFormat(pc,2,',','.');
                    return nome+' - '+novaPc+'%';
                }
            }
        }]
    });

    $('#extratos_chart').highcharts({
        chart: {
            borderColor: '#CCC',
            borderWidth: 2,
            type: 'column'
        },
        title: {
            text: ''
        },
        navigation: {
            buttonOptions: {
                enabled: false
            }
        },
        credits: {
            enabled: false
        },

        xAxis: {
            categories: [
                <?=$meses?>
            ]
        },
        yAxis: {
            minorTickInterval: 'auto',
            minorTickLength: 0,
            min: 0,
            title: {
                text: 'Extratos'
            }
        },
        tooltip: {
            formatter:function(){

                var formato = Highcharts.numberFormat(parseFloat(this.y),2,',','.');
                var chart = $('#extratos_chart').highcharts();
                var series  = chart.series;
                var key     = this.x;
                var cor     = series.color;
                var nome    = series.name;

                return '<span style="font-size:10px;width:150px">'+key+'</span><table style="width:150px;">'+
                        '<tr><td style="color:'+cor+';padding:0">Valor: </td>'+
                        '<td style="padding:0" nowrap><b>R$ '+formato+' </b></td></tr>'+
                        '</table>';
            },
            shared: true,
            useHTML: true
        },
        plotOptions: {
            column: {
                pointPadding: 0.2,
                borderWidth: 0,
                dataLabels:{
                    enabled: true,
                    formatter: function(){
                        var valor = Highcharts.numberFormat(parseFloat(this.y),2,',','.');

                        return 'R$ '+valor;
                    }
                }
            },
            series:{
                cursor: 'pointer',
                point:{
                    events:{
                        click: function(){
                            window.open('os_extrato.php');
                        }
                    }
                }
            }
        },
        series:[
            {
                name:"Valor",
                data: <?=$extrato_valor?>
            }
        ]
    });
});

</script>
<style>
.postit{
    background: url('imagens/sticky.png') no-repeat;
    height: 250px;
}

#row-fluid-comunicados{
    border: 2px solid #CCC;
    -moz-border-radius:5px;
    -webkit-border-radius:5px;
    border-radius:5px;
    margin-bottom:0;
    width:100%;
    height: 500px !important;
}

.row-fluid .span4{
    width: 31.2%;
}

#senha_extrato{
    height:350px;
    width:845px;
    position:absolute;
    border:2px solid #CCC;
    -moz-border-radius:5px;
    -webkit-border-radius:5px;
    border-radius:5px;
    z-index:1;
}

#senha{
    background-color: #D9E2EF;
    height:150px;
    width:300px;
    text-align:center;
    position:absolute;
    top:50%;
    left:50%;
    margin-top:-80px;
    margin-left:-160px;
}
#senha span{
    background-color:#596D9B;
    border:1px solid;
    color:#FFF;
    padding-left:91px;
    padding-right:91px;
}
</style>

</head>
<body>

<?

if (strlen($_COOKIE['cook_login_unico']) > 0) {
    #include "cabecalho.php";
?>

<table width='100%'  border='0' cellpadding='0' cellspacing='0'>
    <tr>
        <td align='center' valign='top'><?php
} else {

    $sql = "SELECT digita_os, pedido_faturado FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $login_posto";
    $res = @pg_query($con, $sql);
    if(pg_num_rows($res)){
        $digita_os = pg_fetch_result($res, 0, 'digita_os');
        $pedido_faturado = pg_fetch_result($res, 0, 'pedido_faturado');
    }
}
    ?>


<?

if($login_fabrica == 87)
    $table_menu = "700px";
else
    $table_menu = "762px";

// path e prefixo de idioma para a imagem de cada aba
// ex., para a aba 'os' com o ambiente em espanhol, $path_aba = "imagens/aba/es_"
$path_aba = "imagens/aba/";
$path_aba.= ($cook_idioma != 'pt-br') ? $cook_idioma . '_' : '';

// Cria uma variável com o nome do layout menu:  ex., se $layout_menu = 'os',
// cria a variável $aba_os com valor '_ativo'
$aba_ativa  = "aba_$layout_menu";
$$aba_ativa = '_ativo';


echo "<table width='$table_menu' border='0' cellspacing='0' cellpadding='0' align='center'>";
echo "<tr>";
    if($login_fabrica == 87){
        echo "<td align='right'><a href='$link_sair' title='Sair do Sistema'><img src='$path_aba"."sair.gif' border='0'></a></td>";
    }else{
        if(!in_array($login_fabrica, array(87)) OR (in_array($login_fabrica, array(87)) AND $digita_os == 't')){
            //aba OS
            echo "<td><center>";
            echo "<a href='$link_os'><img src='$path_aba" . "os$aba_os.gif' border='0'></a>";
        }

        if ($cook_tipo_posto_et <> "t") {
        //aba INFORMAÇÕES TÉCNICAS
        echo "<a href='$link_vista'>";
        echo"<img src='$path_aba" . "info_tecnico$aba_tecnica.gif' border='0'></a>";


        if($login_fabrica <>19){
            //aba PEDIDO
            if(!in_array($login_fabrica,array(20,152,180,181,182))){
                if(!in_array($login_fabrica, array(87)) OR (in_array($login_fabrica, array(87)) AND $pedido_faturado == 't')){
                    echo "<a href='menu_pedido.php'><img src='$path_aba" . "pedidos$aba_pedido.gif' border='0'></a>";
                }
            }
            // aba CADASTRO
            echo "<a href='menu_cadastro.php'><img src='$path_aba" . "cadastro$aba_cadastro.gif' border='0'></a>";

            //aba TABELA DE PREÇO
            if($login_fabrica <>20 and $login_fabrica <> 15){
            echo "<a href='menu_preco.php'><img src='$path_aba" . "tabela_preco$aba_preco.gif' border='0'></a>";
            }
        }else{
            echo "<a href='peca_reposicao_arvore.php'><img src='$path_aba" . "peca_reposicao$aba_reposicao.gif' border='0'></a>";

            // aba CADASTRO
            echo "<a href='produtos_arvore.php'><img src='$path_aba" . "produtos$aba_produtos.gif' border='0'></a>";

            // aba LANÇAMENTOS
            echo "<a href='lancamentos_arvore.php'><img src='$path_aba" . "lancamentos$aba_lancamentos.gif' border='0'></a>";

            // aba INFORMATIVOS
            echo "<a href='informativos_arvore.php'><img src='$path_aba" . "informativos$aba_informativos.gif' border='0'></a>";

            // aba PROMOÇÕES
            echo "<a href='promocoes_arvore.php'><img src='$path_aba" . "promocoes$aba_promocoes.gif' border='0'></a>";
        }
    }

        //aba SAIR
        echo "<a href='$link_sair'><img src='$path_aba" . "sair.gif' border='0'></a></center></td>";

}



$comunicado_titulo   = traduz("bem.vindo.ao.assist",$con);

if($login_fabrica == 43){
    include ('posto_medias.php');
}

?>

<table width="745px" border="0" cellspacing="2" cellpadding="0" class='tabela' align='center'  style='width: 745px;'>
    <tr>
        <td colspan='4'>
<br/>
<div class='container tc_container'>
    <div class="row-fluid">
        <div class="span12" style="background-color:#D3D3D3;">
            <br>
<?
                if($login_fabrica == 3){
?>
                <select name="linha" id="linha" >
                    <optgroup label="Selecione a Linha">
                        <option value="">Todas</option>
<?
                    for($c=0;$c<pg_num_rows($resLinha);$c++){
                        $linha      = pg_fetch_result($resLinha,$c,0);
                        $linha_nome = pg_fetch_result($resLinha,$c,1);
?>
                        <option value="<?=$linha?>"><?=$linha_nome?></option>
<?
                    }
?>
                    </optgroup>
                </select>
<?
                }
?>

                <div class="accordion">
                  <div class="accordion-group">
                    <div class="accordion-heading" style="background-color: #ebebeb">
                      <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion4" href="#collapseThree">
                        <b>Ordens de Serviço Abertas nos Últimos 3 meses</b>
                        <i class="icon-zoom-in pull-right"></i>
                      </a>

                    </div>
                    <div id="collapseThree" class="accordion-body collapse in">
                      <div class="accordion-inner">
                        <div id="os_chart" style="height: 350px; margin: 0 auto;"></div>
                      </div>
                    </div>
                  </div>
                </div>

                <br />

                <?php
                    if($login_fabrica == 19){$diplay_none = "style='display:none;'";}
                ?>
                <div class="accordion" <?php echo $diplay_none; ?> >
                  <div class="accordion-group">
                    <div class="accordion-heading" style="background-color: #ebebeb">
                      <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#collapseOne">
                        <b>Pedidos Gerados nos Últimos 3 meses</b>
                        <i class="icon-zoom-in pull-right"></i>
                      </a>

                    </div>
                    <div id="collapseOne" class="accordion-body collapse in">
                      <div class="accordion-inner">
                        <div id="pedidos_chart" style="height: 350px; margin: 0 auto;"></div>
                      </div>
                    </div>
                  </div>
                </div>



                <br />
<?
if($esconder == "SIM"){
?>
        <div class="accordion">
                <div id="senha_extrato">
                    <div id="senha">
                        <span>Validação de Senha</span>
                        <br />
                        <p style="text-align:center;">
                            Para acessar o gráfico, favor Digitar a senha de acesso do financeiro
                        </p>
                        <br />
                        <cite id="msg" style="display:none;">Favor, digitar a senha correta</cite>
                        <input type="password" name="senha_financeiro" id="senha_financeiro" >
                        <br />
                        <button type="button" onclick="javascript:senhaFinanceiro();">Acessar</button>
                    </div>
                </div>
<?
}
?>

                <div class="accordion">
                  <div class="accordion-group">
                    <div class="accordion-heading" style="background-color: #ebebeb">
                      <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion3" href="#collapseTwo">
                        <b>Extratos Gerados nos Últimos 3 meses</b>
                        <i class="icon-zoom-in pull-right"></i>
                      </a>

                    </div>
                    <div id="collapseTwo" class="accordion-body collapse in">
                      <div class="accordion-inner">
                        <div id="extratos_chart" style="height: 350px; margin: 0 auto;<? if($esconder == "SIM"){?>opacity:0;<?}?>"></div>
                      </div>
                    </div>
                  </div>
                </div>

            </div>
        </div>
    </div>
    <br />
    <div class="row-fluid" id="row-fluid-comunicados">
        <div class="span12">
            <h4 style="text-align:center;color:274B6D;font-family:'Lucida Grande', 'Lucida Sans Unicode',Verdana,Arial, Helvetica, sans-serif">
                Últimos Comunicados
            </h4>
<?
$decode = json_decode($resultadosArrayComunicados);
foreach($decode as $comunicado=>$valores){
?>
            <div class="span4 postit" id=<?=$comunicado?>>
                <h6 style="margin-left:30px;margin-right:30px;">
<?
    echo $valores->data." - ".$valores->tipo;
?>
                </h6>
                <br>
                <p style="margin-left:20px;margin-right:60px;">
<?
    echo substr(html_entity_decode($valores->mensagem),0,110)."...";
?>
<a href="comunicado_mostra_pesquisa.php?comunicado=<?=$comunicado?>" target="_BLANK">Continua</a>
                </p>
            </div>
<?
}

?>
        </div>
    </div>
    <br />
</div>


<?php
    include "rodape.php";
?>
