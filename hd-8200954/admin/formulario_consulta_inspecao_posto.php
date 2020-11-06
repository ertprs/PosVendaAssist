<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';


$admin_privilegios="auditoria";
include 'autentica_admin.php';

include 'funcoes.php';

include_once "../class/aws/s3_config.php";
include_once S3CLASS;

$imagem_upload =  $login_admin."_".date("dmyhmi");

# Verifica permissões do usuario para acessar a tela
$sql = "select login,privilegios,nome_completo,admin_sap from tbl_admin where fabrica = $login_fabrica and admin = $login_admin;";
$res = pg_query ($con,$sql);
$usuario_privilegio = pg_fetch_result($res, 0, 1);
$nome_admin = pg_fetch_result($res, 0, 2);
$admin_sap = pg_fetch_result($res,0,3);

if($usuario_privilegio != '*' &&  $admin_sap <> 't'){
	header('location: menu_auditoria.php');
}

/* retorna as perguntas que serão utilizadas para gerar o grafico */
function getQuestionData($arr){
    global $login_fabrica;

    foreach($arr as $idPergunta => $p){

        if($login_fabrica == 74){

            #if(in_array($idPergunta, array(271,280))){

                $newArr[$idPergunta]["descricao"] = $p["descricao"];

                foreach($p as $item){
                    if(is_array($item)){

                        $newArr[$idPergunta]["data"][] = array( $item["descricao"], (int)$item["qtd"]);

                    }
                }

            #}
        }
    }
    return $newArr;
}
if($_POST["btn_acao"] == "Pesquisar"){
    $arrayStats = array();
    $codigo_posto = $_POST["codigo_posto"];
    $decricao_posto = $_POST["descricao_posto"];
    $data_inicial = $_POST["data_inicial"];
    $data_final = $_POST["data_final"];

	if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
		$sql = "SELECT tbl_posto_fabrica.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND (
					(UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
					OR
					(TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
				)";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Posto não encontrado";
			$msg_erro["campos"][] = "posto";
		} else {
			$posto = pg_fetch_result($res, 0, "posto");
            $cond_posto = " AND tbl_posto_fabrica.posto = {$posto} AND
                            tbl_posto_fabrica.fabrica = {$login_fabrica}";
		}
	}else{
        $cond_posto = "";
    }

	if (strlen($data_inicial) > 0 or strlen($data_final) > 0) {

		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
			$aux_data_final   = "{$yf}-{$mf}-{$df}";

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "data";
			}
            if(count($msg_erro["msg"]) == 0){
                $cond_data = " AND tbl_auditoria_online.data_visita BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";
            }else{
                $cond_data = "";
            }
		}
	}

    if(!empty($_POST["auditoria_online"])){
        $cond_data  = "";
        $cond_posto = "";
        $cond_auditoria = "AND tbl_auditoria_online.auditoria_online = ". $_POST["auditoria_online"];

    }
    if(count($msg_erro['msg']) == 0){
/* seleciona a qtde de respostas respondidas por pergunta */
        /* total.total -> total das respostas das pesquisas dos itens radio e a porcentagem (perc) é em relação às pesquisas respondidas  */
        $sqlStats = "SELECT tbl_pergunta.pergunta,
                           tbl_pergunta.descricao as pergunta_descricao,
                           tbl_tipo_resposta_item.descricao as descricao_item,
                           tbl_tipo_resposta_item.tipo_resposta_item as item_resposta,
                           total.total ,
                           count(tbl_resposta.resposta) as qtd_item,
                           (count(tbl_resposta.resposta)  * 100 / total.total ) as perc

                       FROM tbl_pesquisa
                       INNER JOIN tbl_auditoria_online ON tbl_auditoria_online.pesquisa = tbl_pesquisa.pesquisa AND
                                                          tbl_auditoria_online.fabrica  = tbl_pesquisa.fabrica

                       INNER JOIN tbl_posto_fabrica    ON tbl_posto_fabrica.posto       = tbl_auditoria_online.posto AND
                                                          tbl_posto_fabrica.fabrica     = tbl_auditoria_online.fabrica

                       INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                       INNER JOIN tbl_pesquisa_pergunta ON tbl_pesquisa_pergunta.pesquisa = tbl_pesquisa.pesquisa
                       INNER JOIN tbl_pergunta ON tbl_pergunta.pergunta = tbl_pesquisa_pergunta.pergunta AND
                                                  tbl_pergunta.fabrica = {$login_fabrica}
                       INNER JOIN tbl_tipo_pergunta on tbl_tipo_pergunta.tipo_pergunta = tbl_pergunta.tipo_pergunta AND
                                                       tbl_tipo_pergunta.fabrica = tbl_pergunta.fabrica
                       INNER JOIN tbl_tipo_resposta ON tbl_tipo_resposta.tipo_resposta = tbl_pergunta.tipo_resposta AND
                           							tbl_tipo_resposta.fabrica = tbl_pergunta.fabrica

                       INNER JOIN tbl_resposta ON tbl_resposta.pergunta = tbl_pergunta.pergunta AND
                                                  tbl_resposta.auditoria_online = tbl_auditoria_online.auditoria_online

                       INNER JOIN tbl_tipo_resposta_item ON tbl_tipo_resposta_item.tipo_resposta = tbl_tipo_resposta.tipo_resposta AND
                                                            tbl_tipo_resposta_item.tipo_resposta_item = tbl_resposta.tipo_resposta_item
                       left join (
                           			SELECT pergunta, coalesce(count(*),0) as total
                           			FROM tbl_resposta
                                       INNER JOIN tbl_auditoria_online ON tbl_auditoria_online.auditoria_online = tbl_resposta.auditoria_online
                                       where fabrica = {$login_fabrica}
                           			group by pergunta) as  total ON tbl_resposta.pergunta = total.pergunta
                       WHERE tbl_pesquisa.fabrica = {$login_fabrica} AND tbl_tipo_resposta.tipo_descricao = 'radio'
                             {$cond_data}
                             {$cond_posto}
                             {$cond_auditoria}
                       group by tbl_pergunta.pergunta,
                                pergunta_descricao,
                                tbl_tipo_resposta_item.descricao,
                                total.total,
                                item_resposta
                       ORDER BY tbl_pergunta.pergunta";

        $resStats = pg_query($con, $sqlStats);
        $numRowsStats = pg_num_rows($resStats);
        if($numRowsStats > 0){

            for($i = 0; $i<$numRowsStats; $i++){

                $obj = pg_fetch_object($resStats, $i);
                if($obj->pergunta != $perguntaAnt){
                    $perguntaAnt = $obj->pergunta;
                    $qtdRespondidas=0;
                }
                $pergunta = $obj->pergunta;
                $tpoRespostaItem = $obj->item_resposta;

                $arrayStats[$pergunta]["descricao"] = utf8_encode($obj->pergunta_descricao);
                $arrayStats[$pergunta]["total"] = $obj->total;
                $arrayStats[$pergunta][$tpoRespostaItem]["descricao"] = utf8_encode($obj->descricao_item);
                $arrayStats[$pergunta][$tpoRespostaItem]["qtd"] = $obj->qtd_item;
                $arrayStats[$pergunta][$tpoRespostaItem]["perc"] = $obj->perc;
            }
        }
    }

    $sqlInspecoes = "SELECT DISTINCT tbl_auditoria_online.auditoria_online,
                                     tbl_auditoria_online.data_visita,
                                     tbl_admin.nome_completo,
                                     tbl_posto_fabrica.codigo_posto,
                                     tbl_posto.nome
                      FROM tbl_pesquisa
                      INNER JOIN tbl_auditoria_online ON tbl_auditoria_online.pesquisa = tbl_pesquisa.pesquisa AND
                                                                 tbl_auditoria_online.fabrica  = tbl_pesquisa.fabrica
                      INNER JOIN tbl_admin ON tbl_admin.admin = tbl_auditoria_online.admin
                      INNER JOIN tbl_posto_fabrica    ON tbl_posto_fabrica.posto       = tbl_auditoria_online.posto AND
                                                                 tbl_posto_fabrica.fabrica     = tbl_auditoria_online.fabrica

                      INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                      INNER JOIN tbl_resposta ON tbl_resposta.auditoria_online = tbl_auditoria_online.auditoria_online
                      WHERE tbl_auditoria_online.fabrica = {$login_fabrica}
                                    {$cond_data}
                                    {$cond_posto}
                                    {$cond_auditoria}";
$resInpecoes = pg_query($con, $sqlInspecoes);
$numRowsInspecoes = pg_num_rows($resInpecoes);
}

$layout_menu = "auditoria";
$title = "RELATÓRIO DE INSPEÇÃO";
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");
?>
<script type="text/javascript" src="js/highcharts_4.0.3.js"></script>
<script language="javascript">
	var hora = new Date();
	var engana = hora.getTime();

	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

	});

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    }

function makePieChart(el,data){

    $(el).highcharts({
				chart: {
	                plotBackgroundColor: null,
	                plotBorderWidth: null,
	                plotShadow: false
	            },
				title: {
					text: data["descricao"]
				},
                tooltip: {
    	            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
                },
				plotOptions: {
					pie: {
						allowPointSelect: true,
						cursor: 'pointer',
						dataLabels: {
							enabled: true
						},
						showInLegend: true
					}
				},
				series: [{
					type: 'pie',
					name: data["descricao"],
					data: data["data"]
				}]
			});
}
</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigat&oacute;rios </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
		<div class='titulo_tabela '>Par&acirc;metros de Pesquisa</div>
		<br/>
		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span2'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='data_inicial'>N&uacute;mero Auditoria</label>
						<div class='controls controls-row'>

                            <input type="text" name="auditoria_online" id="auditoria_online" size="12" maxlength="10" class='span10' value= "<?=$auditoria_online?>">
						</div>
					</div>
				</div>

				<div class='span3'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_inicial'>Data Inicial</label>
						<div class='controls controls-row'>
                            <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span6' value= "<?=$data_inicial?>">
						</div>
					</div>
				</div>
			<div class='span3'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'>Data Final</label>
					<div class='controls controls-row'>
					<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span6' value="<?=$data_final?>" >

					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
        <div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='codigo_posto'>C&oacute;digo Posto</label>
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
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Nome Posto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
<br/>
		<div class='row-fluid'>
			<div class='span4'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>

					<div class='tac controls controls-row'>
			            <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'),'Pesquisar');">Pesquisar</button>
               			<input type='hidden' id="btn_click" name='btn_acao' value='' />

					</div>
				</div>
			</div>
			<div class='span4'></div>
		</div>
</form>
<?
if(count($arrayStats) > 0){
    $dadosGrafico = getQuestionData($arrayStats);
    $keys = array_keys($dadosGrafico);?>
<div class="row"> <?

    foreach($keys as $idPergunta){
 ?>

    <div id="pie_<?=$idPergunta?>" class="span5">

    </div>

   <? } ?>
</div>
<br/>
    <table id="lista_auditoria" class='table table-striped table-bordered table-hover table-large' >
        <thead>
            <tr class='titulo_coluna' >
                <th>Inspeção</th>
				<th>Posto</th>
  				<th>Inspetor</th>
				<th>Data</th>
           </tr>
		</thead>
		<tbody>
<?
    for($i = 0; $i < $numRowsInspecoes; $i ++){
        $objInspecoes = pg_fetch_object($resInpecoes, $i);
        $objInspecoes->data_visita = new DateTime($objInspecoes->data_visita)?>
        <tr>


        <td nowrap ><a target="_blank" href="inspecao_posto.php?auditoria_online=<?=$objInspecoes->auditoria_online?>"> <?=$objInspecoes->auditoria_online?></a> </td>
        <td nowrap ><?=$objInspecoes->codigo_posto." - ".$objInspecoes->nome?></td>
        <td nowrap ><?=$objInspecoes->nome_completo?></td>
        <td nowrap ><?=$objInspecoes->data_visita->format("d/m/Y")?></td>


        </tr>
 <? } ?>
        </tbody>
    </table>

    <script type="text/javascript">
    var dadosGrafico = <?=json_encode($dadosGrafico)?>;
    for(i in dadosGrafico){

            var el = $("#pie_"+i);
            console.log(el);
            makePieChart(el, dadosGrafico[i]);
    }

       </script>
<?

	}

include "rodape.php";
?>
