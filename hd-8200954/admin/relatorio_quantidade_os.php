<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

if ($login_fabrica == 14){
	include 'relatorio_quantidade_os_intelbras.php';
	exit;
}

$btn_acao = $_POST['btn_acao'];

/* Pesquisa Padrão */
if(isset($btn_acao)){
	$mes 			= $_POST["mes"];
	$ano 			= $_POST["ano"];
	$linha 			= $_POST["linha"];
	if (in_array($login_fabrica, array(1))) {
		$marca 		= $_POST["marca"];
	}
	$produto_referencia 	= trim($_POST["produto_referencia"]);
	$produto_descricao 	= trim($_POST["produto_descricao"]);

	if (is_array($linha)) {
		$linha = implode(",", $linha);
	}

	if (strlen($mes) == 0) {
		$msg_erro["msg"][] = traduz("Para realizar a pesquisa é necessário selecionar um Mês");
		$msg_erro["campos"][] = traduz("mes");
	}

	if (strlen($ano) == 0) {
		$msg_erro["msg"][] = traduz("Para realizar a pesquisa é necessário selecionar um Ano");
		$msg_erro["campos"][] = traduz("ano");
	}

	if (count($msg_erro['msg']) == 0) {

		if (in_array($login_fabrica, array(20))) {
			$selectPais = "UPPER(tbl_posto.pais) AS pais,";
			$SelGroupPais = traduz("pais, ");
		}

		if (in_array($login_fabrica, array(1))) {
			$selectMarca = "tbl_marca.nome AS nome_marca,";
			$joinMarca = "INNER JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca AND tbl_marca.fabrica = $login_fabrica";
			$SelGroupMarca = "nome_marca, ";
			if (strlen($marca) > 0) {
				$whereMarca = "AND tbl_marca.marca = $marca";
			}
		}

		if ($linha != "") {
			$whereLinha = "AND tbl_linha.linha in ($linha)";
		}

		if (strlen($produto_referencia)>0){
			$sql = "SELECT produto FROM tbl_produto JOIN tbl_linha USING(linha) WHERE fabrica = $login_fabrica AND referencia = '$produto_referencia'";
			$resY = pg_query($con,$sql);
			if (pg_num_rows($resY) > 0) {
				$produto = pg_fetch_result($resY,0,0);
				$whereProduto = "AND tbl_produto.produto = $produto";
			}
		}

		$sqlPeriodo = "SELECT DATE_PART('MONTH', TO_DATE('{$ano}-{$mes}-01', 'YYYY-MM-DD') - INTERVAL '2 months')||'/'||DATE_PART('year', TO_DATE('{$ano}-{$mes}-01', 'YYYY-MM-DD') - INTERVAL '2 months') AS data_1,
				            DATE_PART('MONTH', TO_DATE('{$ano}-{$mes}-01', 'YYYY-MM-DD') - INTERVAL '1 months')||'/'||DATE_PART('year', TO_DATE('{$ano}-{$mes}-01', 'YYYY-MM-DD') - INTERVAL '1 months') AS data_2,
				            DATE_PART('MONTH', TO_DATE('{$ano}-{$mes}-01', 'YYYY-MM-DD') + interval '1 month - 1 day')||'/'||DATE_PART('year', TO_DATE('{$ano}-{$mes}-01', 'YYYY-MM-DD') + interval '1 month - 1 day') AS data_3;";
	            $resPeriodo = pg_query($con, $sqlPeriodo);

	            $data_1 = pg_fetch_result($resPeriodo, 0, data_1);
	            $data_2 = pg_fetch_result($resPeriodo, 0, data_2);
	            $data_3 = pg_fetch_result($resPeriodo, 0, data_3);

		if (strlen($data_1) < 7)
            		$data_1 = "0".$data_1;

            	if (strlen($data_2) < 7)
            		$data_2 = "0".$data_2;

            	if (strlen($data_3) < 7)
            		$data_3 = "0".$data_3;

            	$limit = (!isset($_POST["gerar_excel"])) ? "LIMIT 500" : "";

		$sqlPesquisa = "SELECT nome_posto,
					codigo_posto,
					posto,
					linha,
					nome_linha,
					$SelGroupMarca
					$SelGroupPais
					SUM(CASE WHEN dados.mes = DATE_PART('month', TO_DATE('{$ano}-{$mes},01', 'YYYY-MM-DD') - INTERVAL '2 months') AND posto = dados.posto THEN qtde_os ELSE 0 END) AS mes_1,
					SUM(CASE WHEN dados.mes = DATE_PART('month', TO_DATE('{$ano}-{$mes},01', 'YYYY-MM-DD') - INTERVAL '1 months') AND posto = dados.posto THEN qtde_os ELSE 0 END) AS mes_2,
					SUM(CASE WHEN dados.mes = DATE_PART('month', TO_DATE('{$ano}-{$mes},01', 'YYYY-MM-DD') + INTERVAL '1 months - 1 day') AND posto = dados.posto THEN qtde_os ELSE 0 END) AS mes_3,
					SUM(CASE WHEN posto = dados.posto THEN qtde_os ELSE 0 END) AS Total
				FROM (SELECT COUNT (DISTINCT tbl_os_produto.os) AS qtde_os,
						tbl_linha.linha,
						tbl_linha.nome AS nome_linha,
						$selectMarca
						$selectPais
						tbl_posto.nome AS nome_posto,
						tbl_posto.posto,
						tbl_posto_fabrica.codigo_posto,
						oe.mes,
						oe.ano
					FROM (SELECT tbl_os_extra.os,
							tbl_extrato.posto,
							tbl_extrato.fabrica,
							DATE_PART('month', tbl_extrato.aprovado) AS mes,
							DATE_PART('year', tbl_extrato.aprovado) AS ano
						FROM tbl_os_extra
						INNER JOIN tbl_extrato USING(extrato)
						WHERE fabrica = $login_fabrica
						AND tbl_extrato.aprovado BETWEEN TO_DATE('{$ano}-{$mes}-01', 'YYYY-MM-DD') - INTERVAL '2 months'
						AND TO_DATE('{$ano}-{$mes}-01', 'YYYY-MM-DD') + interval '1 month - 1 day') oe
					INNER JOIN tbl_os_produto ON tbl_os_produto.os = oe.os
					INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
					INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = $login_fabrica
					$joinMarca
					INNER JOIN tbl_posto ON tbl_posto.posto = oe.posto
					INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE oe.fabrica = $login_fabrica
					$whereProduto
					$whereLinha
					$whereMarca
					GROUP BY ano, mes, tbl_linha.linha, $SelGroupMarca$SelGroupPais nome_linha, nome_posto, tbl_posto.posto, tbl_posto_fabrica.codigo_posto
					ORDER BY tbl_posto_fabrica.codigo_posto, mes, ano)
				dados
				GROUP BY posto, codigo_posto, nome_posto, linha, $SelGroupMarca$SelGroupPais nome_linha
				ORDER BY posto
				$limit;";

		$resPesquisa = pg_query($con, $sqlPesquisa);

		$count = pg_num_rows($resPesquisa);
		$dadosPesquisa = pg_fetch_all($resPesquisa);

		/* Gera arquivo CSV */
		if ($_POST["gerar_excel"] && $count > 0) {

			$data = date("d-m-Y-H:i");

			$arquivo_nome 		= "relatorio-qtde-os-$data.xls";
			$path 				= "xls/";
			$path_tmp 			= "/tmp/";

			$arquivo_completo 		= $path.$arquivo_nome;
			$arquivo_completo_tmp 	= $path_tmp.$arquivo_nome;

			$fp = fopen($arquivo_completo_tmp,"w");

			$thead = "<table border='1'>";
			$thead .= "<thead>";
			$thead .= "<tr>";
			$thead .= "<th rowspan='2'>".traduz("Código")."</th>";
			$thead .= "<th rowspan='2'>".traduz("Nome")."</th>";
			if (in_array($login_fabrica, array(20))) {
				$thead .= "<th rowspan='2'>".traduz("País")."</th>";
			}
			$thead .= "<th rowspan='2'>".traduz("Linha")."</th>";
			if (in_array($login_fabrica, array(1))) {
				$thead .= "<th rowspan='2'>".traduz("Marca")."</th>";
			}
			$thead .= "<th colspan='3'>".traduz("Período")."</th>";
			$thead .= "<th rowspan='2'>".traduz("Total")."</th>";
			$thead .= "</tr>";
			$thead .= "<tr>";
			$thead .= "<th>".$data_1."</th>";
			$thead .= "<th>".$data_2."</th>";
			$thead .= "<th>".$data_3."</th>";
			$thead .= "</tr>";
			$thead .= "</thead>";

			fwrite($fp, $thead);

			$tbody .= "<tbody>";
			$posto_anterior = 0;
			$linha_anterior = 0;
			for ($fi = 0; $fi < $count; $fi++) {
				$fposto 		= pg_fetch_result($resPesquisa, $fi, posto);
				$fcodigo_posto 		= pg_fetch_result($resPesquisa, $fi, codigo_posto);
				$fnome_posto 		= pg_fetch_result($resPesquisa, $fi, nome_posto);
				$flinha 			= pg_fetch_result($resPesquisa, $fi, linha);
				$fnome_linha 		= pg_fetch_result($resPesquisa, $fi, nome_linha);
				if (in_array($login_fabrica, array(20))) {
					$fpais 		= pg_fetch_result($resPesquisa, $fi, pais);
				}
				if (in_array($login_fabrica, array(1))) {
					$fmarca 	= pg_fetch_result($resPesquisa, $fi, nome_marca);
				}
				$fmes_1 		= pg_fetch_result($resPesquisa, $fi, mes_1);
				$fmes_2 		= pg_fetch_result($resPesquisa, $fi, mes_2);
				$fmes_3 		= pg_fetch_result($resPesquisa, $fi, mes_3);
				$ftotal 			= pg_fetch_result($resPesquisa, $fi, total);

				if ($posto_anterior != $fposto) {
					$tbody .= "<tr>";
					$tbody .= "<td>".$fcodigo_posto."</td>";
					$tbody .= "<td>".$fnome_posto."</td>";
					if (in_array($login_fabrica, array(20))) {
						$tbody .= "<td>".$fpais."</td>";
					}
					if (in_array($login_fabrica, array(1))) {
						$ffcolspan = 6;
					} else {
						$ffcolspan = 5;
					}
					$tbody .= "<td colspan='".$ffcolspan."'>&nbsp;</td>";
					$tbody .= "</tr>";
				}
				if (($posto_anterior != $fposto) || ($posto_anterior == $fposto && $linha_anterior != $flinha)) {
					if (in_array($login_fabrica, array(20))) {
						$fcolspan = 3;
					} else {
						$fcolspan = 2;
					}
					$tbody .= "<tr>";
					$tbody .= "<td colspan='".$fcolspan."'>&nbsp;</td>";
					$tbody .= "<td>".$fnome_linha."</td>";
					if (in_array($login_fabrica, array(1))) {
						$tbody .= "<td>".$fmarca."</td>";
					}
					$tbody .= "<td>".$fmes_1."</td>";
					$tbody .= "<td>".$fmes_2."</td>";
					$tbody .= "<td>".$fmes_3."</td>";
					$tbody .= "<td>".$ftotal."</td>";
					$tbody .= "</tr>";
				}
				$linha_anterior = $flinha;
				$posto_anterior = $fposto;
			}
			$tbody .= "</tbody>";
			$tbody .= "</table>";

			fwrite($fp, $tbody);

			fclose($fp);

			if (file_exists($arquivo_completo_tmp)) {
				system("mv ".$arquivo_completo_tmp." ".$arquivo_completo."");
				echo $arquivo_completo;
			}

			exit;
		}
	}
}

$layout_menu = traduz("gerencia");
$title = traduz("RELATÓRIO - QUANTIDADE DE OS APROVADAS POR POSTO");
include "cabecalho_new.php";

$plugins = array("multiselect",
		"lupa",
		"autocomplete",
		"datepicker",
		"mask",
		"dataTable",
		"shadowbox"
		);

include "plugin_loader.php"; ?>
<script type="text/javascript" charset="utf-8">
	$(function() {
		$.autocompleteLoad(Array("produto"));
		Shadowbox.init();

		$("#linha").multiselect({
        			selectedText: "selecionados # de #"
		});

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$('.btn-explode').click(function() {
			var rel = $(this).attr('rel').split("_");
			var i = rel[0];
			var tipo = rel[1];
			var c = $(this).attr('rel');
			var posto = $("#posto_"+i).val();
			var linha = $("#linha_"+i).val();
			var data = $("#data_"+c).val();

			Shadowbox.open({
				content: "relatorio_quantidade_os_ajax.php?ajax_explode=true&posto="+posto+"&linha="+linha+"&data="+data+"&tipo="+tipo,
				player: "iframe",
				width: 800,
				height: 600
			})

			return false;
		});

	});

	function retorna_produto (retorno) {
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
	}
</script>
<? if (count($msg_erro["msg"]) > 0) { ?>
	<div class="alert alert-error">
		<h4><?= implode("<br />", $msg_erro["msg"])?></h4>
	</div>
<? } ?>
<div class="row">
	<b class="obrigatorio pull-right">* <?php echo traduz("Campos obrigatórios"); ?></b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
	<div class='titulo_tabela '><?php echo traduz("Parâmetros de Pesquisa"); ?></div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("mes", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='mes'><?php echo traduz("Mês:"); ?></label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
					<select id="mes" name="mes" class="span6">
						<option value=""></option>
						<? for ($i = 1; $i <= 12; $i++){
							$meses = array(traduz('Janeiro'), traduz('Fevereiro'), traduz('Março'), traduz('Abril'), traduz('Maio'), traduz('Junho'), traduz('Julho'), traduz('Agosto'), traduz('Setembro'), traduz('Outubro'), traduz('Novembro'), traduz('Dezembro'));
	                            				$mesCombo = ($i < 10) ? "0".$i : $i;
	                            				$selected = ($mes == $mesCombo) ? "selected" : ""; ?>
	                            				<option value='<?= $mesCombo; ?>' <?= $selected; ?>><?= $meses[$i - 1]; ?></option>
                            				<? } ?>
					</select>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("ano", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='ano'><?php echo traduz("Ano:"); ?></label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
					<select id="ano" name="ano" class="span6">
						<?= $ano; ?>
						<option value=""></option>
						<?
						$sqlAno = "SELECT DATE_PART('year', MIN(data_digitacao)) FROM tbl_os WHERE fabrica = $login_fabrica;";
						$resAno = pg_query($con, $sqlAno);

						$anoInicial = pg_fetch_result($resAno, 0, 0);
						$anoAtual = date('Y');
						if ($anoInicial < $anoAtual) {
							for ($i = $anoAtual; $i >= $anoInicial; $i--){
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
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class="<?= ($login_fabrica != 1) ? 'span8' : 'span4'?>">
			<div class='control-group'>
                <?php
                if ($login_fabrica == 117) {?>
                        <label class='control-label' for='linha'><?php echo traduz("Macro - Família"); ?></label>
                <?php
                } else { ?>
                        <label class='control-label' for='linha'><?php echo traduz("Linha"); ?></label>
                <?php
                }?>
				<div class='controls controls-row'>
					<? $w = "";
					// HD 2670 - IGOR - PARA A TECTOY, NÃO MOSTRAR A LINHA GERAL, QUE VAI SER EXCLUIDA
					if($login_fabrica == 6){
						$w = "AND linha != 39";
					}
					if ($login_fabrica == 117) {
						$sql = "SELECT DISTINCT tbl_linha.linha,
									tbl_linha.nome
								FROM tbl_linha
								JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha
								JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha
								WHERE tbl_macro_linha_fabrica.fabrica = $login_fabrica
								AND     tbl_linha.ativo = TRUE
								ORDER BY tbl_linha.nome;";
					} else {
						$sql =	"SELECT * FROM tbl_linha WHERE fabrica = $login_fabrica $w AND ativo = TRUE ORDER BY nome;";
					}
					$res = pg_query($con,$sql);

					if (in_array($login_fabrica, array(86, 149))) { ?>
						<select name="linha[]" id="linha" multiple="multiple" class='span12'>
							<? $selected_linha = explode(",", $linha);
							foreach (pg_fetch_all($res) as $key) { ?>
								<option value="<?= $key['linha']?>" <?= (in_array($key['linha'], $selected_linha)) ? "selected" : ""; ?>>
									<?= $key['nome']; ?>
								</option>
							<? } ?>
						</select>
					<? } else { ?>
						<select name='linha' class='span5'>
							<option value=''></option>
							<? for ($i = 0; $i < pg_num_rows($res); $i++) {
								$aux_linha = trim(pg_fetch_result($res, $i, linha));
								$aux_nome  = trim(pg_fetch_result($res, $i, nome)); ?>
								<option value='<?= $aux_linha ?>' <?= ($linha == $aux_linha) ? "selected" : ""; ?>><?= $aux_nome; ?></option>
							<? } ?>
						</select>
					<? } ?>
				</div>
			</div>
		</div>
		<? if ($login_fabrica == 1) {
		            $sqlMarca = "SELECT marca, nome FROM tbl_marca WHERE ativo IS TRUE AND fabrica = $login_fabrica;";
		            $resMarca = pg_query($con,$sqlMarca);
		            $marcas = pg_fetch_all($resMarca); ?>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='marca'><?php echo traduz("Marca"); ?></label>
					<div class='controls controls-row'>
						<select name="marca" id="marca">
							<option value=""></option>
							<? foreach($marcas as $chave => $valor){ ?>
								<option value="<?=$valor['marca']?>" <?=($valor['marca'] == $marca) ? "selected='selected'" : "" ?>><?=$valor['nome']?></option>
							<? } ?>
						</select>
					</div>
				</div>
			</div>
		<? } ?>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?= (in_array("produto_referencia", $msg_erro["campos"])) ? "error" : ""; ?>'>
				<label class='control-label' for='produto_referencia'><?php echo traduz("Ref. Produto"); ?></label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" id="produto_referencia" name="produto_referencia" class='span9' maxlength="20" value="<?= $produto_referencia; ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?= (in_array("produto_descricao", $msg_erro["campos"])) ? "error" : ""; ?>'>
				<label class='control-label' for='produto_descricao'><?php echo traduz("Descrição Produto"); ?></label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" id="produto_descricao" name="produto_descricao" class='span11' value="<?= $produto_descricao; ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<br/>
	<div class="row-fluid">
		<p class="tac">
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?php echo traduz("Pesquisar"); ?></button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p>
	</div>
</form>
<? if (isset($btn_acao) && count($msg_erro['msg']) == 0) {
	if ($count > 0) {
		if ($count > 500) { ?>
			<div class='alert'>
				<h6><?php echo traduz("Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela."); ?></h6>
			</div>
		<? } ?>
		<table id="resultado_pesquisa" class='table table-striped table-bordered table-hover table-large'>
			<thead>
				<tr class='titulo_coluna'>
					<th rowspan="2" class="tac"><?php echo traduz("Código"); ?></th>
					<th rowspan="2" class="tac"><?php echo traduz("Nome"); ?></th>
					<? if (in_array($login_fabrica, array(20))) { ?>
						<th rowspan="2" class="tac"><?php echo traduz("País"); ?></th>
					<? }
                    if (in_array($login_fabrica, array(117))) { ?>
                            <th rowspan="2" class="tac"><?php echo traduz("Macro - Família"); ?></th>
                    <? } else { ?>
                            <th rowspan="2" class="tac"><?php echo traduz("Linha"); ?></th>
                    <? } ?>

					<? if (in_array($login_fabrica, array(1))) { ?>
						<th rowspan="2" class="tac"><?php echo traduz("Marca"); ?></th>
					<? } ?>
					<th colspan="3" class="tac"><?php echo traduz("Período"); ?></th>
					<th rowspan="2" class="tac"><?php echo traduz("Total"); ?></th>
				</tr>
				<tr class="titulo_coluna">
					<th><?= $data_1; ?></th>
					<th><?= $data_2; ?></th>
					<th><?= $data_3; ?></th>
				</tr>
			</thead>
			<tbody>
				<? $posto_anterior = 0;
				$linha_anterior = 0;
				for ($xi = 0; $xi < $count; $xi++) {
					$xposto 		= pg_fetch_result($resPesquisa, $xi, posto);
					$xcodigo_posto 	= pg_fetch_result($resPesquisa, $xi, codigo_posto);
					$xnome_posto 		= pg_fetch_result($resPesquisa, $xi, nome_posto);
					if (in_array($login_fabrica, array(20))) {
						$xpais 		= pg_fetch_result($resPesquisa, $xi, pais);
					}
					$xlinha 		= pg_fetch_result($resPesquisa, $xi, linha);
					$xnome_linha 		= pg_fetch_result($resPesquisa, $xi, nome_linha);
					if (in_array($login_fabrica, array(1))) {
						$xmarca 	= pg_fetch_result($resPesquisa, $xi, nome_marca);
					}
					$xmes_1 		= pg_fetch_result($resPesquisa, $xi, mes_1);
					$xmes_2 		= pg_fetch_result($resPesquisa, $xi, mes_2);
					$xmes_3 		= pg_fetch_result($resPesquisa, $xi, mes_3);
					$xtotal 			= pg_fetch_result($resPesquisa, $xi, total);

					if ($posto_anterior != $xposto) { ?>
						<tr>
							<td class="tar"><?= $xcodigo_posto; ?></td>
							<td class="tac"><?= $xnome_posto; ?></td>
							<? if (in_array($login_fabrica, array(20))) { ?>
								<td class="tac"><?= $xpais; ?></td>
							<? }
							if (in_array($login_fabrica, array(1))) {
								$xxcolspan = 6;
							} else {
								$xxcolspan = 5;
							} ?>
							<td colspan="<?= $xxcolspan; ?>">&nbsp;</td>
						</tr>
					<? }

					if (($posto_anterior != $xposto) || ($posto_anterior == $xposto && $linha_anterior != $xlinha)) {
						if (in_array($login_fabrica, array(20))) {
							$xcolspan = 3;
						} else {
							$xcolspan = 2;
						} ?>
						<tr>
							<td colspan="<?= $xcolspan; ?>">
								<input type="hidden" id="posto_<?= $xi; ?>" value="<?= $xposto; ?>" />
								<input type="hidden" id="linha_<?= $xi; ?>" value="<?= $xlinha; ?>" />
								&nbsp;
							</td>
							<td class="tac"><?= $xnome_linha; ?></td>
							<? if (in_array($login_fabrica, array(1))) { ?>
								<td class="tac"><?= $xmarca; ?></td>
							<? } ?>
							<td class="tac">
								<? if ($xmes_1 > 0) { ?>
									<input type="hidden" id="data_<?= $xi; ?>_1" value="<?= $data_1; ?>" />
									<a href="<?= $PHP_SELF; ?>" class="btn-explode" rel="<?= $xi; ?>_1"><?= $xmes_1; ?></a>
								<? } else {
									echo $xmes_1;
								} ?>
							</td>
							<td class="tac">
								<? if ($xmes_2 > 0) { ?>
									<input type="hidden" id="data_<?= $xi; ?>_2" value="<?= $data_2; ?>" />
									<a href="<?= $PHP_SELF; ?>" class="btn-explode" rel="<?= $xi; ?>_2"><?= $xmes_2; ?></a>
								<? } else {
									echo $xmes_2;
								} ?>
							</td>
							<td class="tac">
								<? if ($xmes_3 > 0) { ?>
									<input type="hidden" id="data_<?= $xi; ?>_3" value="<?= $data_3; ?>" />
									<a href="<?= $PHP_SELF; ?>" class="btn-explode" rel="<?= $xi; ?>_3"><?= $xmes_3; ?></a>
								<? } else {
									echo $xmes_3;
								} ?>
							</td>
							<td class="tac">
								<? if ($xtotal > 0) { ?>
									<input type="hidden" id="data_<?= $xi; ?>_tot" value="<?= $data_3; ?>" />
									<a href="<?= $PHP_SELF; ?>" class="btn-explode" rel="<?= $xi; ?>_tot"><?= $xtotal; ?></a>
								<? } else {
									echo $xtotal;
								} ?>
							</td>
						</tr>
					<? }
					$linha_anterior = $xlinha;
					$posto_anterior = $xposto;
				} ?>
			</tbody>
		</table>
		<br />
		<? if ($count > 50) { ?>
			<script>
				$.dataTableLoad({ table: "#resultado_pesquisa" });
			</script>
		<? }
		$jsonPOST = excelPostToJson($_POST); ?>
		<div id='gerar_excel' class="btn_excel">
			<input type="hidden" id="jsonPOST" value='<?= $jsonPOST; ?>' />
			<span><img src="imagens/excel.png" /></span>
			<span class="txt"><?php echo traduz("Gerar Arquivo Excel"); ?></span>
		</div>
	<? } else { ?>
		<div class="alert">
			<h4><?php echo traduz("Nenhum resultado encontrado para essa pesquisa."); ?></h4>
		</div>
		<br />
	<? }
}

include 'rodape.php'; ?>
