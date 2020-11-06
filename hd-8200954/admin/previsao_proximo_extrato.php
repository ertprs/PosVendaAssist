<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$qtde_mes = 6;

if ($_POST["btn_acao"] == "submit") {
	
    $data_inicial       = filter_input(INPUT_POST,'data_inicial');
    $data_final         = filter_input(INPUT_POST,'data_final');
    $codigo_posto       = filter_input(INPUT_POST,'codigo_posto');
    $descricao_posto    = filter_input(INPUT_POST,'descricao_posto');

    $cond_unidades = "";
    $join_extra    = "";
    $join_hd       = "";
    $cond_extrato  = "";
    $campos_uni    = "";
    $group_by      = "";

    if ($login_fabrica == 158) {
    	$campos_uni = ", JSON_FIELD('unidadeNegocio', tbl_os_campo_extra.campos_adicionais) AS unidade_negocio, 
    					 CASE WHEN tbl_hd_chamado_extra.hd_chamado IS NOT NULL OR tbl_tipo_atendimento.fora_garantia IS TRUE THEN 't' ELSE 'f' END AS fora_garantia,
    					 tbl_os_campo_extra.valores_adicionais";
		$join_extra = " JOIN tbl_os_campo_extra ON tbl_os.os = tbl_os_campo_extra.os";
		$join_hd    = " LEFT JOIN tbl_hd_chamado_extra ON tbl_os.os = tbl_hd_chamado_extra.os
    				    JOIN tbl_tipo_atendimento ON tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica";
    	$group_by .= ", tbl_os_campo_extra.campos_adicionais, tbl_hd_chamado_extra.hd_chamado, tbl_tipo_atendimento.fora_garantia, tbl_os_campo_extra.valores_adicionais ";

    	if (count($_POST["unidade_negocio"]) > 0) {
    		$unidades_negocio       = $_POST["unidade_negocio"];    		
    		$unidades_negocio_busca = implode(",", $unidades_negocio);
    		
    		$cond_unidades = " AND JSON_FIELD('unidadeNegocio', tbl_os_campo_extra.campos_adicionais)::int in ($unidades_negocio_busca) ";
    	}

    	if (!empty($_POST["tipo_extrato"])) {
    		$tipo_extrato = $_POST["tipo_extrato"];
    		if ($tipo_extrato == "Fora de Garantia") {
    			$cond_extrato = " AND (tbl_hd_chamado_extra.hd_chamado IS NOT NULL OR tbl_tipo_atendimento.fora_garantia IS TRUE) ";
    		} else {
    			$cond_extrato = " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
    		}
    	}
    }
	
    if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
		$sql = "SELECT tbl_posto_fabrica.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND (
					(UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
				)";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Posto não encontrado";
			$msg_erro["campos"][] = "posto";
		} else {
			$posto = pg_fetch_result($res, 0, "posto");
		}
	}

	if (!strlen($data_inicial) or !strlen($data_final)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data_inicial";
		$msg_erro["campos"][] = "data_final";
	} else {
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
		}

		$sqlX = "SELECT '$aux_data_inicial'::date + interval '$qtde_mes months' >= '$aux_data_final'";
		$resSubmitX = pg_query($con,$sqlX);
		$periodo_6meses = pg_fetch_result($resSubmitX,0,0);
		if($periodo_6meses == 'f'){
			$msg_erro['msg'][] = "O intervalo entre as datas deve ser de no máximo {$qtde_mes} meses";
		}
	}

	if (!count($msg_erro["msg"])) {

		if (!empty($posto)) {
			$cond_posto = " AND tbl_os.posto = {$posto} ";
		}else{
			$cond_posto = " AND tbl_os.posto <> 6359 ";
		}

        	if(!isset($_POST['gerar_excel'])){
			$limit = " LIMIT 501 ";
		}
			
		$sql = "SELECT tbl_os.os,
			tbl_os.sua_os,
			SUM(tbl_os.mao_de_obra) AS total_mo,
			SUM(tbl_os.qtde_km_calculada) AS total_km,
			tbl_posto_fabrica.codigo_posto,
			tbl_posto.posto AS postoId,
			tbl_posto.nome
			$campos_uni
		FROM tbl_os
		INNER JOIN tbl_os_extra USING(os)
		INNER JOIN tbl_posto_fabrica USING(posto,fabrica)
		INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
		$join_extra
		$join_hd
		WHERE tbl_os.fabrica = {$login_fabrica}
		AND tbl_os_extra.extrato IS NULL
		AND tbl_os.excluida IS NOT TRUE
		AND tbl_os.finalizada IS NOT NULL
		AND tbl_os.finalizada BETWEEN '{$aux_data_inicial} 00:00:00' and '{$aux_data_final} 23:59:59'
		$cond_posto
		$cond_unidades
		$cond_extrato
		GROUP BY tbl_os.os,
			 tbl_os.sua_os,
			 tbl_posto_fabrica.codigo_posto,
			 tbl_posto.posto,
			 tbl_posto.nome
			 $group_by
		ORDER BY tbl_posto.nome,tbl_os.os
		{$limit}";
		$resSubmit = pg_query($con, $sql);
	}

	if(isset($_POST['gerar_excel'])){

		$data = date("d-m-Y-H:i");

		$filename = "relatorio-previsao-extrato-{$data}.csv";

		$file = fopen("/tmp/{$filename}", "w");

		if ($login_fabrica == 158) {
			fwrite($file,"Código Posto;Nome Posto;Unidade de Negócio;Tipo de Extrato;OS;Total MO;Total KM;Total OS;Valor Adicional\n");
		} else {
			fwrite($file,"Código Posto;Nome Posto;OS;Total MO;Total KM;Total OS\n");
		}
				
		if ($login_fabrica == 158) {
			$unidadesMinasGerais = \Posvenda\Regras::getUnidades("unidadesMinasGerais", $login_fabrica);
		}		

		for ($i = 0; $i < pg_num_rows($resSubmit); $i++){

			$os            = pg_result($resSubmit,$i,'os');
			$sua_os        = pg_result($resSubmit,$i,'sua_os');
			$codigo_posto  = pg_result($resSubmit,$i,'codigo_posto');
			$nome_posto    = pg_result($resSubmit,$i,'nome');
			$total_mo      = pg_result($resSubmit,$i,'total_mo');
			$total_km      = pg_result($resSubmit,$i,'total_km');

			if ($login_fabrica == 158) {
				$valores_adicionais = "";

				if (!empty(pg_fetch_result($resSubmit,$i,'valores_adicionais'))) {
					$valores_adicionais_arr  = json_decode(pg_fetch_result($resSubmit,$i,'valores_adicionais'),true);
					// Retirando 1 nível do array
					$valores_adicionais_arr  = array_map(function($a) {  return array_pop($a); }, $valores_adicionais_arr);
					
					foreach ($valores_adicionais_arr as $key => $value) {
						$valores_adicionais += $value; 
					}
				}

				$unidade_negocio     = pg_fetch_result($resSubmit,$i,'unidade_negocio');
				$fora_garantia       = pg_fetch_result($resSubmit,$i,'fora_garantia');
				$fora_garantia_label = "Garantia";

				if ($fora_garantia == "t") {
					$fora_garantia_label = "Fora de Garantia";
				}

				if (!empty($unidade_negocio)) {
					$sql_uni = "SELECT codigo || ' - ' || nome AS unidade_negocio FROM tbl_unidade_negocio WHERE codigo = '$unidade_negocio'";
					$res_uni = pg_query($con, $sql_uni);
					$unidade_negocio_label = pg_fetch_result($res_uni, 0, 'unidade_negocio');
				}

				$total_geral_add += $valores_adicionais;
				$valores_adicionais = number_format($valores_adicionais,2,',','.');
			}

			if ($fora_garantia == "t" && $login_fabrica == 158 && !empty($unidade_negocio)) {
				require_once dirname(__FILE__)."/../classes/Posvenda/Fabricas/_{$login_fabrica}/Extrato.php";
            	$extratoImbera = new ExtratoImbera($login_fabrica);
				$postoId = pg_fetch_result($resSubmit, $i, 'postoId');

				$unidade_negocio_busca = $unidade_negocio;

				if (in_array($unidade_negocio, $unidadesMinasGerais)) {
					$unidade_negocio_busca = 6101;
				}
				
				$precoFixoExtrato = $extratoImbera->verificaPostoPrecoFixoExtrato($postoId, $unidade_negocio_busca, $con);
				if ($precoFixoExtrato > 0) {
					$total_mo = 0;
				}
			}

			$total_geral_mo += $total_mo;
			$total_geral_km += $total_km;

			$total_os = number_format($total_mo + $total_km,2,',','.');
			$total_mo = number_format($total_mo,2,',','.');
			$total_km = number_format($total_km,2,',','.');

			if ($login_fabrica == 158) {
				fwrite($file,"{$codigo_posto};{$nome_posto};{$unidade_negocio_label};{$fora_garantia_label};{$sua_os};{$total_mo};{$total_km};{$total_os};{$valores_adicionais}\n");	
			} else {
				fwrite($file,"{$codigo_posto};{$nome_posto};{$sua_os};{$total_mo};{$total_km};{$total_os}\n");	
			}
		} 
		
		$total_geral = number_format($total_geral_mo + $total_geral_km,2,',','.');
		$total_geral_mo = number_format($total_geral_mo,2,',','.');
		$total_geral_km = number_format($total_geral_km,2,',','.');

		if ($login_fabrica == 158) {
			$total_geral_add = number_format($total_geral_add,2,',','.');
			fwrite($file,";;;;Totais;{$total_geral_mo};{$total_geral_km};{$total_geral};{$total_geral_add}");
		} else {
			fwrite($file,";;Totais;{$total_geral_mo};{$total_geral_km};{$total_geral}");
		}

		fclose($file);

		if (file_exists("/tmp/{$filename}")) {
			system("mv /tmp/{$filename} xls/{$filename}");

			echo "xls/{$filename}";
		}

		exit;
	}
}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE OS";
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"informacaoCompleta",
	"multiselect"
);

include("plugin_loader.php");

$form = array(
	"data_inicial" => array(
		"span"      => 4,
		"label"     => "Data Início",
		"type"      => "input/text",
		"width"     => 4,
		"required"  => true,
		"maxlength" => 10
	),
	"data_final" => array(
		"span"      => 4,
		"label"     => "Data Final",
		"type"      => "input/text",
		"width"     => 4,
		"required"  => true,
		"maxlength" => 10
	),
	"codigo_posto" => array(
		"span"      => 4,
		"label"     => "Código do Posto",
		"type"      => "input/text",
		"width"     => 10,
		"lupa"      => array(
			"name" => "lupa_posto",
			"tipo" => "posto",
			"parametro" => "codigo"
		)
	),
	"descricao_posto" => array(
		"span"      => 4,
		"label"     => "Nome do Posto",
		"type"      => "input/text",
		"width"     => 10,
		"lupa"      => array(
			"name" => "lupa_posto",
			"tipo" => "posto",
			"parametro" => "nome"
		)
	),
);

if ($login_fabrica == 158) {

	$form["unidade_negocio[]"] = array(
        'type' => 'select',
        'id' => 'unidade_negocio',
        'label' => traduz('Unidade de Negócio 1'),
        'span' => 4,
        'width' => 10,
        "extra" => array(
            "multiple" => "true"
        ),
        'options' => []
    );

	$sql = "SELECT unidade_negocio, codigo, nome
			FROM tbl_unidade_negocio";
	$res = pg_query($con,$sql);

	foreach (pg_fetch_all($res) as $key) {

		$form["unidade_negocio[]"]["options"][$key["codigo"]] = $key['codigo'] . " - " . $key['nome'];
		/*if (count($unidades_negocio) > 0 && in_array($key['unidade_negocio'], $unidades_negocio)) { 
			$form["unidade_negocio[]"]["options"] = array("checked");
		}*/
	}

	$form["tipo_extrato"] = array(
        'type' => 'select',
        'label' => traduz('Tipo do Extrato'),
        'span' => 4,
        'width' => 10,
        'options' => []
    );

	$array_tipo = array("Fora de Garantia" => "Fora de Garantia", "Garantia" => "Garantia");

	foreach ($array_tipo as $k => $v) {
		$form["tipo_extrato"]["options"][$v] = $v;
    }
}

?>

<script type="text/javascript">
	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("posto"));
		Shadowbox.init();

		$("#unidade_negocio").multiselect();
	
    });

      	function retorna_posto(retorno){
        	$("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
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

<style>
	table #resultado{
		margin-top: 5px !important;
	}
</style>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
		<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
		<br/>
		<? echo montaForm($form,null);?>

		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/>
</form>
</div>

<?php

if(isset($resSubmit)){

	if (pg_num_rows($resSubmit) > 0) {

		if (pg_num_rows($resSubmit) > 500) {
			$count = 500;
		?>
			<div id='registro_max'>
				<h6>Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.</h6>
			</div>
		<?php
		} else {
			$count = pg_num_rows($resSubmit);
		}

		echo "<div class='tal' style='padding-rigth: 5px !important;'>";

		$posto_codigo = "";
?>
		<table id='resultado' class="table table-striped table-bordered table-hover table-large" style='margin: 0 auto;' >
			<thead>
				<tr class='titulo_coluna'>
		            <th>Código Posto</th>
		            <th>Nome Posto</th>
		            <?php if ($login_fabrica == 158) { ?>
		            	<th>Unidade de Negócio</th>
		            	<th>Tipo de Extrato</th>
		            <?php } ?>
		            <th>OS</th>
		            <th>Total MO</th>
		            <th>Total KM</th>
		            <th>Total OS</th>
		            <?php if ($login_fabrica == 158) { ?>
		            	<th>Valor Adicional</th>
		            <?php } ?>
		        </tr>
			</thead>
			<tbody>
<?php

		if ($login_fabrica == 158) {
			$unidadesMinasGerais = \Posvenda\Regras::getUnidades("unidadesMinasGerais", $login_fabrica);
		}

		for ($i = 0; $i < $count; $i++) {
			$os                      = pg_result($resSubmit,$i,'os');
			$sua_os                  = pg_result($resSubmit,$i,'sua_os');
			$codigo_posto            = pg_result($resSubmit,$i,'codigo_posto');
			$nome_posto              = pg_result($resSubmit,$i,'nome');
			$total_mo                = pg_result($resSubmit,$i,'total_mo');
			$total_km                = pg_result($resSubmit,$i,'total_km');

			if ($login_fabrica == 158) {
				$valores_adicionais = "";

				if (!empty(pg_fetch_result($resSubmit,$i,'valores_adicionais'))) {
					$valores_adicionais_arr  = json_decode(pg_fetch_result($resSubmit,$i,'valores_adicionais'),true);
					// Retirando 1 nível do array
					$valores_adicionais_arr  = array_map(function($a) {  return array_pop($a); }, $valores_adicionais_arr);
					
					foreach ($valores_adicionais_arr as $key => $value) {
						$valores_adicionais += $value; 
					}
				}

				$unidade_negocio     = pg_fetch_result($resSubmit,$i,'unidade_negocio');
				$fora_garantia       = pg_fetch_result($resSubmit,$i,'fora_garantia');
				$fora_garantia_label = "Garantia";

				if ($fora_garantia == "t") {
					$fora_garantia_label = "Fora de Garantia";
				}

				if (!empty($unidade_negocio)) {
					$sql_uni = "SELECT codigo || ' - ' || nome AS unidade_negocio FROM tbl_unidade_negocio WHERE codigo = '$unidade_negocio'";
					$res_uni = pg_query($con, $sql_uni);
					$unidade_negocio_label = pg_fetch_result($res_uni, 0, 'unidade_negocio');
				}

				$total_geral_add += $valores_adicionais;
			}

			if ($fora_garantia == "t" && $login_fabrica == 158 && !empty($unidade_negocio)) {
				require_once dirname(__FILE__)."/../classes/Posvenda/Fabricas/_{$login_fabrica}/Extrato.php";
            	$extratoImbera = new ExtratoImbera($login_fabrica);
				$postoId = pg_fetch_result($resSubmit, $i, 'postoId');

				$unidade_negocio_busca = $unidade_negocio;
				
				if (in_array($unidade_negocio, $unidadesMinasGerais)) {
					$unidade_negocio_busca = 6101;
				}

				$precoFixoExtrato = $extratoImbera->verificaPostoPrecoFixoExtrato($postoId, $unidade_negocio_busca, $con);
				if ($precoFixoExtrato > 0) {
					$total_mo = 0;
				}
			}

			$total_geral_mo += $total_mo;
			$total_geral_km += $total_km;
?>
			<tr>
				<td><?=$codigo_posto?></td>
				<td><?=$nome_posto?></td>
				<?php if ($login_fabrica == 158) { ?>
						<td class="tac"><?=$unidade_negocio_label?></td>
						<td class='tac'><?=$fora_garantia_label?></td>
				<?php } ?>
				<td class='tac'><a href='os_press.php?os=<?=$os?>' target='_blank'><?=$sua_os?></a></td>
				<td class='tar'><?=number_format($total_mo,2,'.',',')?></td>
				<td class='tar'><?=number_format($total_km,2,'.',',')?></td>
				<td class='tar'><?=number_format($total_mo + $total_km,2,'.',',')?></td>
				<?php if ($login_fabrica == 158) { ?>
						<td class='tar'><?=number_format($valores_adicionais,2,'.',',')?></td>
				<?php } ?>
			</tr>
<?php

		}

		$cols = ($login_fabrica == 158) ? 5 : 3;
?>
		</tbody>
			 <tfoot>
				<tr>
					<td colspan='<?=$cols?>' style='text-align:right;'><b>Totais</b></td>
					<td class='tar'><?=number_format($total_geral_mo,2,'.',',')?></td>
					<td class='tar'><?=number_format($total_geral_km,2,'.',',')?></td>
					<td class='tar'><?=number_format($total_geral_mo + $total_geral_km,2,'.',',')?></td>
					<?php if ($login_fabrica == 158) { ?>
						<td class='tar'><?=number_format($total_geral_add,2,'.',',')?></td>
					<?php } ?>
				</tr>
			</tfoot>
		</table>

<?php

		echo "<br />";

		if ($count > 1) {
		?>
			<script>
				$.dataTableLoad({ table: "#resultado" });
			</script>
		<?php
		}

		$jsonPOST = excelPostToJson($_POST);

		?>

		<br />

		<div id='gerar_excel' class="btn_excel">
			<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
			<span><img src='imagens/excel.png' /></span>
			<span class="txt">Gerar Arquivo Excel</span>
		</div>

		<?php

		echo "</div>";

	}else{
		echo '
		<div class="container">
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
		</div>';
	}
}

include 'rodape.php';?>
