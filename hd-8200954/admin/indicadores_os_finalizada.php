<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

use Posvenda\DistribuidorSLA;

$btn_acao = $_POST['btn_acao'];

$oDistribuidorSLA = new DistribuidorSLA();
$oDistribuidorSLA->setFabrica($login_fabrica);
$distribuidores = $oDistribuidorSLA->SelectUnidadeNegocio();

/* Pesquisa Padrão */
if(isset($btn_acao)){
	$ano             = $_POST["ano"];
	$unidade_negocio = $_POST["unidade_negocio"];	
	$unidade_negocio = implode("|", $unidade_negocio);

	if (strlen($ano) == 0) {
		$msg_erro["msg"][] = "Para realizar a pesquisa é necessário selecionar um Ano";
		$msg_erro["campos"][] = "ano";
	}

	if (strlen($unidade_negocio) == 0) {
		$msg_erro["msg"][] = "Para realizar a pesquisa é necessário selecionar pelo menos uma unidade de negócio";
		$msg_erro["campos"][] = "unidade_negocio";
	}

	if (count($msg_erro['msg']) == 0) {

	if (!empty($unidade_negocio)) {
		$whereUnidadeNegocio = "AND JSON_FIELD('unidadeNegocio', oce.campos_adicionais) ~ '{$unidade_negocio}'";
	}

        $sqlPesquisa = "
            SELECT
                dados.familia,
                dados.tipo_atendimento,
                dados.unidade_negocio,
                SUM(CASE WHEN dados.mes = 1 THEN total_os ELSE 0 END) AS janeiro,
                SUM(CASE WHEN dados.mes = 2 THEN total_os ELSE 0 END) AS fevereiro,
                SUM(CASE WHEN dados.mes = 3 THEN total_os ELSE 0 END) AS marco,
                SUM(CASE WHEN dados.mes = 4 THEN total_os ELSE 0 END) AS abril,
                SUM(CASE WHEN dados.mes = 5 THEN total_os ELSE 0 END) AS maio,
                SUM(CASE WHEN dados.mes = 6 THEN total_os ELSE 0 END) AS junho,
                SUM(CASE WHEN dados.mes = 7 THEN total_os ELSE 0 END) AS julho,
                SUM(CASE WHEN dados.mes = 8 THEN total_os ELSE 0 END) AS agosto,
                SUM(CASE WHEN dados.mes = 9 THEN total_os ELSE 0 END) AS setembro,
                SUM(CASE WHEN dados.mes = 10 THEN total_os ELSE 0 END) AS outubro,
                SUM(CASE WHEN dados.mes = 11 THEN total_os ELSE 0 END) AS novembro,
                SUM(CASE WHEN dados.mes = 12 THEN total_os ELSE 0 END) AS dezembro,
                SUM(total_os) AS total
            FROM (
                SELECT
				    unidades.unidade_negocio||' - '||c.nome AS unidade_negocio,
				    ta.descricao AS tipo_atendimento,
				    (
				        SELECT f.descricao
				        FROM tbl_familia f
				        JOIN tbl_produto p ON p.familia = f.familia AND fabrica_i = {$login_fabrica}
				        WHERE p.produto = op.produto
				    ) AS familia,
				    CASE WHEN o.finalizada IS NOT NULL THEN EXTRACT(MONTH FROM o.finalizada) ELSE EXTRACT(MONTH FROM o.data_conserto) END AS mes,
				    COUNT(o.*) AS total_os
				FROM tbl_os o
				JOIN tbl_os_produto op USING(os)
				JOIN tbl_os_campo_extra oce USING(os,fabrica)
				JOIN tbl_tipo_atendimento ta USING(tipo_atendimento,fabrica)
				LEFT JOIN (
				    SELECT DISTINCT unidade_negocio, cidade
				    FROM tbl_distribuidor_sla
				    WHERE fabrica = {$login_fabrica}
				) AS unidades ON unidades.unidade_negocio = JSON_FIELD('unidadeNegocio', oce.campos_adicionais)
				JOIN tbl_cidade c ON c.cidade = unidades.cidade
				WHERE o.fabrica = {$login_fabrica}
				AND (o.finalizada IS NOT NULL OR o.data_conserto IS NOT NULL)
				AND ((EXTRACT(YEAR FROM o.finalizada) = {$ano}) OR (EXTRACT(YEAR FROM o.data_conserto) = {$ano}))
				AND o.posto <> 6359
				AND o.excluida IS NOT TRUE              
				{$whereUnidadeNegocio}
				GROUP BY unidades.unidade_negocio, ta.descricao, c.nome, op.produto, familia, mes
            ) dados
            GROUP BY dados.unidade_negocio, dados.tipo_atendimento, dados.familia
            ORDER BY dados.familia, dados.unidade_negocio;";
		$resPesquisa = pg_query($con, $sqlPesquisa);		
		$count 		 = pg_num_rows($resPesquisa);
		$resultado   = array();

		array_map(function($row) {
			global $resultado, $meses_idioma;

			foreach($meses_idioma['pt-br'] as $mes) {
				$mes = strtolower($mes);
				$resultado['unidade_negocio'][$row['unidade_negocio']][$mes]   			+= $row[$mes];
				$resultado['familia'][$row['familia']][$row['unidade_negocio']][$mes] 	+= $row[$mes];
				$resultado['unidade_familia'][$row['unidade_negocio']][$row['tipo_atendimento']][$row['familia']][$mes] 	+= $row[$mes];
			}
			$resultado['unidade_negocio'][$row['unidade_negocio']]['total']   			+= $row['total'];
			$resultado['familia'][$row['familia']][$row['unidade_negocio']]['total'] 	+= $row['total'];
			$resultado['unidade_familia'][$row['unidade_negocio']][$row['tipo_atendimento']][$row['familia']]['total'] 	+= $row['total'];			
		}, pg_fetch_all($resPesquisa));
	}
}
$layout_menu = "gerencia";
$title = "INDICADORES - QUANTIDADE DE OS FINALIZADAS/CONSERTADAS X ANO X ESTADO";
include "cabecalho_new.php";

if (count($msg_erro["msg"]) > 0) { ?>
	<div class="alert alert-error">
		<h4><?= implode("<br />", $msg_erro["msg"])?></h4>
	</div>
<? } ?>
<div class="row">
	<b class="obrigatorio pull-right">* Campos obrigatórios</b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?= $PHP_SELF; ?>' align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela'>Parâmetros de Pesquisa</div>
	<br />
	<div class='row-fluid'>
		<div class='span3'></div>
		<div class='span3'>
			<div class='control-group <?=(in_array("ano", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='ano'>Ano:</label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
					<select id="ano" name="ano" class="span12">
						<option value=""></option>
						<?
						$sqlAno = "SELECT DATE_PART('year', MIN(data_digitacao)) FROM tbl_os WHERE fabrica = {$login_fabrica};";
						$resAno = pg_query($con, $sqlAno);

						$anoInicial = pg_fetch_result($resAno, 0, 0);
						$anoAtual = date('Y');
						if ($anoInicial < $anoAtual) {
							for ($i = $anoAtual; $i >= $anoInicial; $i--) {
								$selected = ($ano == $i) ? "selected" : ""; ?>
			                    <option value='<?= $i; ?>' <?= $selected; ?>><?= $i; ?></option>
		                    <? }
                		} else {
                			$selected = ($ano == $anoAtual) ? "selected" : ""; ?>
                			<option value="<?= $anoAtual; ?>" <?= $selected; ?>><?= $anoAtual; ?></option>
            			<? } ?>
            		</select>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("unidade_negocio", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='unidade_negocio'>Unidade de Negócio:</label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
					<select id="unidade_negocio" name='unidade_negocio[]' class='span12' multiple="multiple">
						<option value=''>Selecione</option>
						<? foreach ($distribuidores as $unidadeNegocio) {
							$selected = (in_array($unidadeNegocio['unidade_negocio'], getValue("unidade_negocio"))) ? "selected" : ""; ?>
                            <option value="<?= $unidadeNegocio['unidade_negocio']; ?>" <?= $selected; ?>><?= $unidadeNegocio['cidade']; ?></option>
                        <? } ?>
					</select>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<br/>
	<div class="row-fluid">
		<p class="tac">
			<button class='btn' id="btn_acao" type="button" onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p>
	</div>
</form>
</div>
<? if (isset($btn_acao) && count($msg_erro['msg']) == 0) {
	if ($count > 0) {
		ob_start(); ?>
		<table id="resultado_pesquisa_unidade_negocio" class='table table-bordered table-hover table-large'>
			<thead>
				<tr class='titulo_coluna'>
					<th rowspan="2" class="tar">Unidade de negócio</th>
					<th colspan="13" class="tac">QUANTIDADE DE OS POR UNIDADE DE NEGÓCIO - ANO: <?= $ano; ?></th>
				</tr>
				<tr class="titulo_coluna">
					<? foreach($meses_idioma['pt-br'] as $mes) { ?>
						<th><?= $mes; ?></th>
					<? } ?>
					<th>Total</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$total = array(); 
				foreach ($resultado['unidade_negocio'] as $unidade_negocio => $array_meses) {
				?>
					<tr>
						<td class="tar" nowrap><?= $unidade_negocio; ?></td>
				<?php
					foreach($meses_idioma['pt-br'] as $mes) {
						$mes = strtolower($mes);
						$total[$mes] += $array_meses[$mes];
				?>
						<td class="tac"><?= $array_meses[$mes] ? $array_meses[$mes] : 0; ?></td>
				<?php
					}
				?>
						<td class="tac alert-info"><?= $array_meses['total']; ?></td>
					</tr>
				<?php
					$total_geral += $array_meses['total'];				
				}
				if (count($resultado['unidade_negocio']) > 1) {
				?>				
					<tr class="info">
						<td class="tar" nowrap><strong>Total</strong></td>
				<?php
						foreach($meses_idioma['pt-br'] as $mes) {
							$mes = strtolower($mes);
				?>
						<td class="tac alert-info"><?= $total[$mes]; ?></td>
				<?php
						}
				?>
						<td class="tac alert-info"><?= $total_geral; ?></td>					
					</tr>
				<?php
				}
				?>
			</tbody>			
		</table>
		<?php
        unset($total, $total_geral);
		?>
		<br />
		<table id="resultado_pesquisa_familia" class='table table-bordered table-hover table-large'>
			<thead>
				<tr class='titulo_coluna'>
					<th rowspan="2" class="tar">Família</th>
					<th rowspan="2" class="tar">Unidade de negócio</th>
					<th colspan="13" class="tac">QUANTIDADE DE OS POR FAMÍLIA x UNIDADE DE NEGÓCIO - ANO: <?= $ano; ?></th>
				</tr>
				<tr class="titulo_coluna">
					<? foreach($meses_idioma['pt-br'] as $mes) { ?>
						<th><?= $mes; ?></th>
					<? } ?>
					<th>Total</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$total = array();
				foreach ($resultado['familia'] as $familia => $array_unidade) {
				?>
				<tr>
					<td class="tac" rowspan="<?= count($array_unidade); ?>" nowrap><?= $familia; ?></td>
				<?php
					foreach ($array_unidade as $unidade_negocio => $array_meses) {
				?>
						<td class="tar" nowrap><?= $unidade_negocio; ?></td>
				<?php
						foreach($meses_idioma['pt-br'] as $mes) {
							$mes = strtolower($mes);
							$total[$mes] 	+= $array_meses[$mes];						
				?>
							<td class="tac"><?= $array_meses[$mes] ? $array_meses[$mes] : 0; ?></td>
				<?php
						}
				?>
						<td class="tac alert-info"><?= $array_meses['total']; ?></td>					
					</tr>
				<?php
						$total_geral 	+= $array_meses['total'];
					}
				}
				?>
				<tr class="info">
					<td class="tar" colspan="2" nowrap><strong>Total</strong></td>
				<?php
					foreach($meses_idioma['pt-br'] as $mes) {
						$mes = strtolower($mes);
				?>
						<td class="tac alert-info"><?= $total[$mes]; ?></td>					
				<?php
					}
				?>
					<td class="tac alert-info"><?= $total_geral; ?></td>					
				</tr>
			</tbody>
		</table>
		<br />
		<?php
        unset($total, $total_geral);
		?>
		<table id="resultado_pesquisa_tipo_atendimento" class='table table-bordered table-hover table-large'>
			<thead>
				<tr class='titulo_coluna'>
					<th rowspan="2" class="tar">Unidade de negócio</th>
					<th rowspan="2" class="tar">Tipo de atendimento</th>
					<th rowspan="2" class="tar">Família</th>
					<th colspan="13" class="tac">QUANTIDADE DE OS POR UNIDADE DE NEGÓCIO x TIPO DE ATENDIMENTO x FAMÍLIA - ANO: <?= $ano; ?></th>
				</tr>
				<tr class="titulo_coluna">
					<? foreach($meses_idioma['pt-br'] as $mes) { ?>
						<th><?= $mes; ?></th>
					<? } ?>
					<th>Total</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$total = array();
				$conta_linhas = array();
				foreach ($resultado['unidade_familia'] as $unidade_negocio => $array_atendimento) {
					foreach ($array_atendimento as $tipo_atendimento => $array_familia) {
						$conta_linhas[$unidade_negocio] += count($array_familia) + 1;
					}
				}
				foreach ($resultado['unidade_familia'] as $unidade_negocio => $array_atendimento) {
				?>
					<td class="tar" rowspan="<?= $conta_linhas[$unidade_negocio]; ?>" nowrap><?= $unidade_negocio; ?></td>
				<?php
					foreach ($array_atendimento as $tipo_atendimento => $array_familia) {
				?>
						<td class="tar" rowspan="<?= count($array_familia)+1; ?>" nowrap><?= $tipo_atendimento; ?></td>				
				<?php
						foreach ($array_familia as $familia => $array_meses) {
				?>
							<tr>
								<td class="tac" nowrap><?= $familia; ?></td>
				<?php
							foreach($meses_idioma['pt-br'] as $mes) {
								$mes = strtolower($mes);
								$total[$mes] 	+= $array_meses[$mes];							
				?>							
								<td class="tac"><?= $array_meses[$mes] ? $array_meses[$mes] : 0; ?></td>
				<?php
							}
				?>
								<td class="tac alert-info"><?= $array_meses['total']; ?></td>
							</tr>
				<?php
							$total_geral 	+= $array_meses['total'];
						}
					}
				}				
				?>
				<tr class="info">
					<td class="tar" colspan="3" nowrap><strong>Total</strong></td>
				<?php
					foreach($meses_idioma['pt-br'] as $mes) {
						$mes = strtolower($mes);
				?>
					<td class="tac alert-info"><?= $total[$mes]; ?></td>
				<?php
					}
				?>
					<td class="tac alert-info"><?= $total_geral; ?></td>
				</tr>				
			</tbody>
		</table>
		<br />
		<?
		$html = ob_get_contents();

	    ob_end_flush();
	    ob_clean();

	    $xls  = "indicadores-os-finalizada-{$login_fabrica}-{$login_admin}-".date("YmdHi").".xls";
	    $file = fopen("/tmp/".$xls, "w");
	    fwrite($file, $html);
	    fclose($file);
	    system("mv /tmp/{$xls} xls/{$xls}"); ?>
	    <br />
	    <p class="tac">
	        <button type="button" class="btn btn-success download-xls" data-xls="<?= $xls; ?>"><i class="icon-download-alt icon-white"></i> Download XLS</button>
	    </p>
	<? } else { ?>
		<div class="alert">
			<h4>Nenhum resultado encontrado para essa pesquisa.</h4>
		</div>
		<br />
	<? }
}

$plugins = array(
	"dataTable",
	"select2"
);

include "plugin_loader.php"; ?>
<script>

$("select").select2();

$("button.download-xls").on("click", function() {
    var xls = $(this).data("xls");

    window.open("xls/"+xls);
});

</script>
<? include 'rodape.php'; ?>
