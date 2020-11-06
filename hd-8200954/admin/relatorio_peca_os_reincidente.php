<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$admin_privilegios="gerencia";
$layout_menu = "gerencia";
$title       = "RELATÓRIO DE PEÇAS EM OS REINCIDENTE";

$msgErrorPattern01 = "Preencha os campos obrigatórios.";

if(!empty($_POST)){
    $mes = (int)$_POST['mes'];
    $ano = (int)$_POST['ano'];

    $codigo_posto   = trim($_POST['codigo_posto']);
    $posto_nome     = trim($_POST['posto_nome']);

    if ((empty($mes) || empty($ano))){
        $msg_erro["msg"][]    = $msgErrorPattern01;
        $msg_erro["campos"][] = "data";
    }else{
        $data_ini = date("$ano-$mes-01");
        $data_fim = date('Y-m-t', strtotime($data_ini));

        list($yi, $mi, $di) = explode("-", $data_ini);
        list($yf, $mf, $df) = explode("-", $data_fim);

        if (!checkdate($mi, $di, $yi) || !checkdate($mf, $df, $yf) || !is_int($ano) || !is_int($mes)) {
            $msg_erro["msg"][]    = $msgErrorPattern01;
            $msg_erro["campos"][] = "data";
        }
    }
}

if($_POST['ajax']){
    $ajax_posto         = $_POST['ajax_posto'];
    $ajax_peca          = $_POST['ajax_peca'];
    $ajax_data_inicial  = $_POST['ajax_data_inicial'];
    $ajax_data_final    = $_POST['ajax_data_final'];

    /**
     * - Buscando
     *  -- OS Reincidente e Primária
     *  -- Distância entre datas de abertura de ambas
     */

    $sqlAjaxOs = "
        SELECT  tbl_os.os                                           AS os_reincidente       ,
                reincidente.os                                      AS os_primeira          ,
                tbl_defeito.descricao                               AS defeito_descricao    ,
                tbl_servico_realizado.descricao                     AS servico_descricao    ,
                (tbl_os.data_abertura - reincidente.data_abertura)  AS diferenca_abertura
        FROM    tbl_os
        JOIN    tbl_posto_fabrica       ON  tbl_posto_fabrica.posto                 = tbl_os.posto
                                        AND tbl_posto_fabrica.fabrica               = $login_fabrica
                                        AND tbl_posto_fabrica.posto                 = $ajax_posto
        JOIN    tbl_os_extra            ON  tbl_os_extra.os                         = tbl_os.os
        JOIN    tbl_os reincidente      ON  reincidente.os                          = tbl_os_extra.os_reincidente
        JOIN    tbl_os_produto          ON  tbl_os_produto.os                       = tbl_os.os
        JOIN    tbl_os_item             ON  tbl_os_item.os_produto                  = tbl_os_produto.os_produto
                                        AND tbl_os_item.peca                        = $ajax_peca
        JOIN    tbl_defeito             ON  tbl_defeito.defeito                     = tbl_os_item.defeito
        JOIN    tbl_servico_realizado   ON  tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
        WHERE   tbl_os.data_abertura::DATE BETWEEN '$ajax_data_inicial' AND '$ajax_data_final'
    ";
    $resAjaxOs = pg_query($con,$sqlAjaxOs);

    for($c = 0; $c < pg_numrows($resAjaxOs); $c++){
        $os_reincidente     = pg_fetch_result($resAjaxOs,$c,os_reincidente);
        $os_primeira        = pg_fetch_result($resAjaxOs,$c,os_primeira);
        $defeito_descricao  = pg_fetch_result($resAjaxOs,$c,defeito_descricao);
        $servico_descricao  = pg_fetch_result($resAjaxOs,$c,servico_descricao);
        $diferenca_abertura = (int)pg_fetch_result($resAjaxOs,$c,diferenca_abertura);

        $defeito_descricao  = htmlentities($defeito_descricao);
        $servico_descricao  = htmlentities($servico_descricao);

        $resultados[] = array(
            "os_reincidente"     => $os_reincidente    ,
            "os_primeira"        => $os_primeira       ,
            "defeito_descricao"  => $defeito_descricao ,
            "servico_descricao"  => $servico_descricao ,
            "diferenca_abertura" => $diferenca_abertura
        );
    }

    echo json_encode($resultados);
    exit;
}

/*--------------------------------------------------------------------------------
selectMesSimples()
Cria ComboBox com meses de 1 a 12
--------------------------------------------------------------------------------*/
function selectMesSimples($selectedMes){
    $mes = array(
        1 => "Janeiro",
        2 => "Fevereiro",
        3 => "Março",
        4 => "Abril",
        5 => "Maio",
        6 => "Junho",
        7 => "Julho",
        8 => "Agosto",
        9 => "Setembro",
       10 => "Outubro",
       11 => "Novembro",
       12 => "Dezembro"
    );
    for($dtMes=1; $dtMes <= 12; $dtMes++){
        $dtMesTrue  = ($dtMes < 10) ? "0".$dtMes : $dtMes;
        $mesAtual   = $mes[$dtMes];

        echo "<option value=$dtMesTrue ";
        if ($selectedMes == $dtMesTrue) echo "selected";
        echo ">$mesAtual</option>\n";
    }
}
/*--------------------------------------------------------------------------------
    selectAnoSimples($ant,$pos,$dif,$selectedAno)
    // $ant = qtdade de anos retroceder
    // $pos = qtdade de anos posteriores
    // $dif = ve qdo ano termina
    // $selectedAno = ano já setado
    Cria ComboBox com Anos
--------------------------------------------------------------------------------*/
function selectAnoSimples($ant,$pos,$dif=0,$selectedAno){
    $startAno = date("Y"); // ano atual
    for($dtAno = $startAno - $ant; $dtAno <= $startAno + ($pos - $dif); $dtAno++){
        echo "<option value=$dtAno ";
        if ($selectedAno == $dtAno) echo "selected";
        echo ">$dtAno</option>\n";
    }
}

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "dataTable"
    );

include("plugin_loader.php");

?>
<script src="js/highcharts_4.0.3.js"></script>
<script type="text/javascript" charset="utf-8">

$(function() {
    $.autocompleteLoad(Array("posto"));
    Shadowbox.init();

    $("span[rel=lupa]").click(function () { $.lupa($(this));});

    function formatItem(row) { return row[0] + " - " + row[1];}

    function formatResult(row) { return row[0]; }

//         $.dataTableLoad({
//             table: "#gridRelatorioPecaOsReincidente"
//         });

    $(".divShowChart").click(function(){
        $(this).toggle('slow');
    });

});

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#posto_nome").val(retorno.nome);
}

function buscaOsReincidente(posto,peca,data_inicial,data_final){
    if($("#"+posto+"_"+peca).hasClass("tr_esconde")){
        $.ajax({
            url:"<?=$PHP_SELF?>",
            dataType:"json",
            type:"POST",
            data:{
                ajax:true,
                ajax_posto:posto,
                ajax_peca:peca,
                ajax_data_inicial:data_inicial,
                ajax_data_final:data_final
            },
            beforeSend:function(){
                $("#"+posto+"_"+peca).removeClass("tr_esconde").addClass("tr_mostra").html("<td colspan='3'>Aguarde...</td>");
            }
        })
        .done(function(data){
            var conteudo = "<table cellspacing='2' cellpadding='1' border='0'>";
            conteudo += "<thead>";
            conteudo += "<tr>";
            conteudo += "<th>Os Incidente</th>";
            conteudo += "<th>Os Reincidente</th>";
            conteudo += "<th>Defeito da peça</th>";
            conteudo += "<th>Serviço Realizado</th>";
            conteudo += "<th>Diferença de Abertura (dias)</th>";
            conteudo += "</tr>";
            conteudo += "</thead>";
            conteudo += "<tbody>";
            for(i = 0; i < data.length; i++){
                var os_reincidente     = data[i].os_reincidente;
                var os_primeira        = data[i].os_primeira;
                var defeito_descricao  = data[i].defeito_descricao;
                var servico_descricao  = data[i].servico_descricao;
                var diferenca_abertura = data[i].diferenca_abertura;

                conteudo += "<tr>";
                conteudo += "<td><a target='_blank' href='os_press.php?os="+os_primeira+"' >"+os_primeira+"</a></td>";
                conteudo += "<td><a target='_blank' href='os_press.php?os="+os_reincidente+"' >"+os_reincidente+"</a></td>";
                conteudo += "<td>"+defeito_descricao+"</td>";
                conteudo += "<td>"+servico_descricao+"</td>";
                conteudo += "<td>"+diferenca_abertura+"</td>";
                conteudo += "</tr>";
            }

            conteudo += "</tbody>";
            conteudo += "</table>";

            $("#"+posto+"_"+peca).html("<td colspan='3'>"+conteudo+"</td>");
        })
        .fail(function(){
            $("#"+posto+"_"+peca).removeClass("tr_mostra").addClass("tr_esconde");
        });
    }else{
        $("#"+posto+"_"+peca).removeClass("tr_mostra").addClass("tr_esconde");
    }
}
</script>

<style type="text/css">

.tr_esconde{
    display:none;
}

.tr_mostra{
    display:table-row;
}

a:hover{
    cursor:pointer;
}

</style>
<!-- MENSAGEM DE ERRO -->
<?php if (count($msg_erro["msg"]) > 0) {    ?>
    <div class="alert alert-error"> <h4><?php echo $msg_erro["msg"][0]; ?></h4> </div>
<?php   }   ?>

<div class="row"> <b class="obrigatorio pull-right">  * Campos obrigatórios </b> </div>
<form name='frm_peca_os_reincidente' action='<? echo $PHP_SELF ?>' method="POST" class="form-search form-inline tc_formulario">
    <div class="titulo_tabela">Parâmetros de Pesquisa</div>
    <br />
    <div class="container tc_container">
        <div class='row-fluid'>
            <div class='span2'></div>

            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='Mes'>Mês</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <h5 class='asteristico'>*</h5>
                            <select name='mes' class="frm">
                                <option value=''></option>
                                <?php selectMesSimples($mes); ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='Ano'>Ano</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                        <h5 class='asteristico'>*</h5>
                            <select name='ano' class="frm">
                                <option value=''></option>
                                <?php selectAnoSimples(2,0,'',$ano) ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='codigo_posto'>Código Posto</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
                            <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='posto_nome'>Razão Social</label>
                    <div class='controls controls-row'>
                        <div class='span12 input-append'>
                            <input type="text" name="posto_nome" id="posto_nome" class='span12' value="<? echo $posto_nome ?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
    </div>
    <br />
        <center>
            <input class="btn" type="button" onclick="frm_peca_os_reincidente.submit();" value="Pesquisar">
        </center>
    <br />
</form>
<br />
<?php
flush();
if (strlen($mes) > 0 AND strlen($ano) > 0 && count($msg_erro["msg"]) == 0){

    $data_ano = "$ano-01-01";
    $data     = "$ano-$mes-01";

    $sql          = "SELECT fn_dias_mes('$data',0)";
    $resX         = pg_exec($con,$sql);
    $data_inicial = pg_result($resX,0,0);

    $sql        = "SELECT fn_dias_mes('$data',1)";
    $resX       = pg_exec($con,$sql);
    $data_final = pg_result($resX,0,0);

    $sql              = "SELECT fn_dias_mes('$data_ano',0)";
    $resX             = pg_exec($con,$sql);
    $data_inicial_ano = pg_result($resX,0,0);

    /**
     * - SELECT para busca das quantidades de
     * OS Reincidentes em um período
     */

    $sqlPosto = (strlen($codigo_posto) > 0) ? " AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'" : " ";

    $sqlOs = "
        SELECT  tbl_os.posto                ,
                tbl_peca.peca               ,
                COUNT(1) AS qtde_os
        FROM    tbl_os
        JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto     = tbl_os.posto
                                    AND tbl_posto_fabrica.fabrica   = $login_fabrica
                                    AND tbl_os.fabrica              = $login_fabrica
        JOIN    tbl_os_produto      ON  tbl_os_produto.os           = tbl_os.os
        JOIN    tbl_os_item         ON  tbl_os_item.os_produto      = tbl_os_produto.os_produto
        JOIN    tbl_peca            ON  tbl_peca.peca               = tbl_os_item.peca
        WHERE   tbl_os.os_reincidente  IS TRUE
        AND     tbl_os.cancelada       IS NOT TRUE
        AND     tbl_os.excluida        IS NOT TRUE
        AND     tbl_os.data_abertura::DATE BETWEEN '$data_inicial' AND '$data_final'
        $sqlPosto
  GROUP BY      tbl_os.posto                ,
                tbl_peca.peca
  ORDER BY      tbl_os.posto,
                qtde_os DESC
    ";
    $resOs = pg_query($con,$sqlOs);
    $contaRes = pg_num_rows($resOs);

    /**
     * - Início da montagem dos dados
     * do gráfico
     */

    $postosHighcharts = pg_fetch_all_columns($resOs,0);
    $postos = array_unique($postosHighcharts);

    $pecasHighcharts = pg_fetch_all_columns($resOs,1);
    $pecas = array_unique($pecasHighcharts);



?>
<!--<br />

<div id='showChart' class='divShowChart' style='cursor:pointer;margin:auto;text-align:center;width:1000px'>
    <p class='titulo_tabela'>Mostrar Gráfico</p>
    <div style='margin:auto;display:none' id='div_grafico'></div>
</div>

<br />-->

<table id='gridRelatorioPecaOsReincidente' class='table table-striped table-bordered table-hover table-fixed'>
    <thead>
        <tr class='titulo_coluna'>
            <th>Posto</th>
            <th>Peça</th>
            <th>Qtde</th>
        </tr>
    </thead>
    <tbody>
<?php
    for($i = 0; $i < $contaRes; $i++){
        $posto      = pg_fetch_result($resOs,$i,posto);
        $peca       = pg_fetch_result($resOs,$i,peca);
        $qtde_os    = pg_fetch_result($resOs,$i,qtde_os);

        /**
         * Feita a busca dos códigos e descrições de
         * postos e peças dentro do FOR para não embolar o SELECT
         * principal por ter GROUP BY
         */

        $sqlPosto = "
            SELECT  tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome AS nome_posto
            FROM    tbl_posto_fabrica
            JOIN    tbl_posto   ON  tbl_posto.posto = tbl_posto_fabrica.posto
                                AND tbl_posto.posto = $posto
                                AND tbl_posto_fabrica.fabrica = $login_fabrica
        ";
        $resPosto = pg_query($con,$sqlPosto);
        $nome_posto = pg_fetch_result($resPosto,0,nome_posto);

        $sqlPeca = "
            SELECT  tbl_peca.referencia || ' - ' || tbl_peca.descricao AS nome_peca
            FROM    tbl_peca
            WHERE   tbl_peca.peca = $peca
        ";
        $resPeca = pg_query($con,$sqlPeca);
        $nome_peca = pg_fetch_result($resPeca,0,nome_peca);

        $cor = ($i%2) ? '#F7F5F0' : '#F1F4FA';
?>
        <tr align='center' style="background-color:<?=$cor?>">
            <td style="text-align:left"><?=$nome_posto?></td>
            <td style="text-align:left"><?=$nome_peca?></td>
            <td style="text-align:center"><a title="Clique para visualizar os dados da peça em reincidência" onclick="javascript:buscaOsReincidente('<?=$posto?>','<?=$peca?>','<?=$data_inicial?>','<?=$data_final?>');"><?=$qtde_os?></a></td>
        </tr>
        <tr class="tr_esconde" id="<?=$posto."_".$peca?>" >
        </tr>
<?php
    }
}
?>
    </tbody>
</table>

<script type="text/javascript">
    chart = new Highcharts.Chart({
        chart: {
            renderTo: 'div_grafico',
            type: 'bar',
            height: 600,
            width: 1000
        },
        title: {
            text: "Relação de Peças em OS reincidente"
        },
        xAxis: {
            categories: $.parseJSON(perguntas)
        },
        yAxis: {
            min: 0,
            title: {
                text: 'Quantidade de respostas'
            }
        },
        legend: {
            backgroundColor: '#FFFFFF',
            reversed: true
        },
        tooltip: {
            formatter: function() {
                return ''+
                this.series.name +'= '+ this.y +'';
            }
        },
        plotOptions: {
            series: {
                stacking: 'normal'
            }
        },
        series: $.parseJSON(respostas)
    });
</script>
<?php
include "rodape.php";
?>