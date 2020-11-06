<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$btn_acao = $_POST['btn_acao'];

// O Campo origem em tbl_produto.origem só aceita 3 caracteres, por isso a necessidade de criar o De Para
$deParaOrigem = array(
	"MNS" => "Manaus",
	"IMP" => "Importado",
	"CNS" => "Canoas"
);

$arr_meses = array(
	'01' => 'Janeiro',
	'02' => 'Fevereiro',
	'03' => 'Março',
	'04' => 'Abril',
	'05' => 'Maio',
	'06' => 'Junho',
	'07' => 'Julho',
	'08' => 'Agosto',
	'09' => 'Setembro',
	'10' => 'Outubro',
	'11' => 'Novembro',
	'12' => 'Dezembro'
);

/* Pesquisa Padrão */
if (!empty($btn_acao)) {
	$tipo 				= $_POST["tipo"];
	$data_inicial		= $_POST["data_inicial"];
	$data_final 		= $_POST["data_final"];
	$linha_producao 	= $_POST["linha_producao"];
	$origem 			= $_POST["origem"];
	$familia_sap 		= $_POST["familia_sap"];
	$familia_grupo_sap 	= $_POST["familia_grupo_sap"];
	$familia 			= $_POST["familia"];
	$pd 				= $_POST["pd"];
	$por_produto		= $_POST["por_produto"];

	if (is_array($origem)) {
		$origem = array_map(function($e) {
			return "'{$e}'";
		} , $origem);
		$origem = implode(",", $origem);
	}

	if (is_array($linha_producao)) {
		$linha_producao = array_map(function($e) {
			return "'{$e}'";
		} , $linha_producao);
		$linha_producao = implode(",", $linha_producao);
	}

	if (is_array($familia_sap)) {
		$familia_sap = array_map(function($e) {
			return "'{$e}'";
		} , $familia_sap);
		$familia_sap = implode(",", $familia_sap);
	}

	if (is_array($familia_grupo_sap)) {
		$familia_grupo_sap = array_map(function($e) {
			return "'{$e}'";
		} , $familia_grupo_sap);
		$familia_grupo_sap = implode(",", $familia_grupo_sap);
	}

	if (is_array($pd)) {
		$pd = array_map(function($e) {
			return "'{$e}'";
		} , $pd);
		$pd = implode(",", $pd);
	}

	if (empty($data_inicial) || empty($data_final)) {
		$msg_erro["msg"]["obrigatorio"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data";
	}

	if (empty($tipo)) {
		$msg_erro["msg"]["obrigatorio"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "tipo";
	}

	if (count($msg_erro["msg"]) == 0) {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) || !checkdate($mf, $df, $yf)) {
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

		$sqlX = "SELECT '$aux_data_inicial'::date + interval '6 months' >= '$aux_data_final'";
		$resSubmitX = pg_query($con,$sqlX);
		$periodo_6meses = pg_fetch_result($resSubmitX,0,0);
		if($periodo_6meses == 'f'){
			$msg_erro['msg'][] = "O intervalo entre as datas deve ser de no máximo 6 meses";
		}
	}

	if (count($msg_erro['msg']) == 0) {

		if (!empty($familia)) {
			$familia = implode(",", $familia);
			$whereFamilia = "AND f.familia IN ({$familia})";
		}

		if (!empty($origem)) {
			$whereOrigem = "AND p.origem IN ({$origem})";
		}

		if (!empty($linha_producao)) {
			$whereLinhaProducao = "AND p.nome_comercial IN ({$linha_producao})";
		}

		if (!empty($familia_sap)) {
			$whereFamiliaSap = "AND JSON_FIELD('familia_desc', p.parametros_adicionais) IN ({$familia_sap})";
		}

		if (!empty($defeito_constatado)) {
			$whereFamiliaGrupoSap = "AND JSON_FIELD('familia_desc', p.parametros_adicionais) IN ({$familia_sap})";
		}

		if (!empty($pd)) {
			$wherePd = "AND JSON_FIELD('pd', p.parametros_adicionais) IN ({$pd})";
		}

		if ($tipo == "producao") {
			$campoData = "ns.data_fabricacao";
		} else {
			$campoData = "ns.data_venda";
		}

		if ($por_produto == 't') {
			$colAgrupado = "COUNT(*) AS qtde,";
			$groupByProduto = "GROUP BY p.referencia, p.descricao, p.nome_comercial, p.origem, familia_codigo_sap, familia_descricao_sap, product_division, f.descricao";
		} else {
			$colAgrupado = "
				'1' AS qtde,
				TO_CHAR({$campoData}, 'MM/YYYY') AS mes,
				TO_CHAR({$campoData}, 'YYYY') AS ano,
				ns.serie,
			";
		}

		$whereData = "AND {$campoData} BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59'";

		$limit = (!isset($_POST["gerar_excel"]) && $btn_acao != 'excel') ? "LIMIT 500" : "";

		$sqlPesquisa = "
			SELECT DISTINCT
				{$colAgrupado}
				REPLACE(p.referencia, 'YY', '-') AS produto_referencia,
				p.descricao AS produto_descricao,
				p.nome_comercial AS produto_nome_comercial,
				p.origem AS produto_origem,
				JSON_FIELD('familia', p.parametros_adicionais) AS familia_codigo_sap,
				JSON_FIELD('familia_desc', p.parametros_adicionais) AS familia_descricao_sap,
				JSON_FIELD('pd', p.parametros_adicionais) AS product_division,
				f.descricao AS familia_descricao
			FROM tbl_produto p
			JOIN tbl_numero_serie ns ON ns.produto = p.produto AND ns.fabrica = {$login_fabrica}
			JOIN tbl_familia f ON f.familia = p.familia AND f.fabrica = {$login_fabrica}
			WHERE p.fabrica_i = {$login_fabrica}
			{$whereData}
			{$whereFamilia}
			{$whereOrigem}
			{$whereLinhaProducao}
			{$whereFamiliaSap}
			{$whereFamiliaGrupoSap}
			{$wherePd}
			{$groupByProduto}
			{$limit};
		";
		$resPesquisa = pg_query($con, $sqlPesquisa);

		$count = pg_num_rows($resPesquisa);
		$dadosPesquisa = pg_fetch_all($resPesquisa);

		/* Gera arquivo CSV */
		if ((isset($_POST["gerar_excel"]) || $btn_acao == 'excel') && $count > 0) {

			$data = date("d-m-Y-H:i");

			$arquivo_nome 	= "relatorio-producao-venda-qualidade-{$data}.xls";
			$path 			= "xls/";
			$path_tmp 		= "/tmp/";

			$arquivo_completo = $path.$arquivo_nome;
			$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

			$fp = fopen($arquivo_completo_tmp,"w");

			$thead = "<table border='1'>";
			$thead .= "<thead>";
			$thead .= "<tr>";
			$thead .= "<th>Ano</th>";
			$thead .= "<th>Mês</th>";
			$thead .= "<th>Ref. Produto</th>";
			$thead .= "<th>Desc. Produto</th>";
			$thead .= "<th>Família TC</th>";
			$thead .= "<th>Cód. Família</th>";
			$thead .= "<th>Desc. Família</th>";
			$thead .= "<th>Linha Fabricação</th>";
			$thead .= "<th>Product Division</th>";
			$thead .= "<th>Origem</th>";
			$thead .= "<th>Série</th>";
			$thead .= "<th>Qtde</th>";
			$thead .= "</tr>";
			$thead .= "</thead>";

			fwrite($fp, $thead);

			$tbody .= "<tbody>";
			for ($fi = 0; $fi < $count; $fi++) {
				$fmes = pg_fetch_result($resPesquisa, $fi, "mes");
				$fano = pg_fetch_result($resPesquisa, $fi, "ano");
				$fserie = pg_fetch_result($resPesquisa, $fi, "serie");
				$fproduto_referencia = pg_fetch_result($resPesquisa, $fi, "produto_referencia");
				$fproduto_descricao = pg_fetch_result($resPesquisa, $fi, "produto_descricao");
				$fproduto_nome_comercial = pg_fetch_result($resPesquisa, $fi, "produto_nome_comercial");
				$fproduto_origem = pg_fetch_result($resPesquisa, $fi, "produto_origem");
				$ffamilia_descricao = pg_fetch_result($resPesquisa, $fi, "familia_descricao");
				$fproduto_codigo_familia = pg_fetch_result($resPesquisa, $fi, "familia_codigo_sap");
				$fproduto_descricao_familia = pg_fetch_result($resPesquisa, $fi, "familia_descricao_sap");
				$fproduto_product_division = pg_fetch_result($resPesquisa, $fi, "product_division");
				$fqtde = pg_fetch_result($resPesquisa, $fi, "qtde");

				if ($fproduto_origem == "IMP" && $por_produto != 't' && $tipo == "producao") {
					$semana_fab = substr($fserie, 2, 2);
					$ano_fab = substr($fserie, 4, 2);
				
					$mes_aux = ($semana_fab * 7) / 30;
					$mes_aux = (int) $mes_aux;

					if ($mes_aux == 0) {
						$mes_aux = 1;
					}

					if (strlen($mes_aux) == 1) {
						$mes_aux = "0".$mes_aux;
					}

					$fmes = $mes_aux."/20".$ano_fab;
					$fano = "20".$ano_fab;
				}
				
				if ($tipo == "producao") {
					if (!empty($fmes)) {
						$fmes = explode("/", $fmes);
						$fmes_aux = strtoupper($arr_meses[$fmes[0]])."/".$fmes[1];
					} else {
						$fmes_aux = "";
					}
				} else {
					$fmes_aux = $fmes;
				}
				
				$tbody .= "<tr>";
				$tbody .= "<td>".$fano."</td>";
				$tbody .= "<td>".$fmes_aux."</td>";
				$tbody .= "<td>".$fproduto_referencia."</td>";
				$tbody .= "<td>".$fproduto_descricao."</td>";
				$tbody .= "<td>".$ffamilia_descricao."</td>";
				$tbody .= "<td>".$fproduto_codigo_familia."</td>";
				$tbody .= "<td>".$fproduto_descricao_familia."</td>";
				$tbody .= "<td>".$fproduto_nome_comercial."</td>";
				$tbody .= "<td>".$fproduto_product_division."</td>";
				$tbody .= "<td>".$deParaOrigem[$fproduto_origem]."</td>";
				$tbody .= "<td>".$fserie."</td>";
				$tbody .= "<td>".$fqtde."</td>";
				$tbody .= "</tr>";
			}
			$tbody .= "</tbody>";
			$tbody .= "</table>";

			fwrite($fp, $tbody);

			fclose($fp);

			if (file_exists($arquivo_completo_tmp)) {
				system("mv ".$arquivo_completo_tmp." ".$arquivo_completo."");
				if ($btn_acao != 'excel') {
					echo $arquivo_completo;
				} else {
					exit(json_encode(array('sucesso' => utf8_encode($arquivo_completo))));
				}
			}

			if (isset($_POST['gerar_excel'])) {
				exit;
			}
		} else if ($btn_acao == 'excel') {
			$retorno = array("erro" => utf8_encode('Nenhum registro encontrado'));
			exit(json_encode($retorno));
		}
	} else if ($btn_acao == 'excel') { // Se for excel, significa que é um ajax e tem que retornar a mensagem de erro
		$retorno = array("erro" => utf8_encode(implode("<br />", $msg_erro['msg'])));
		exit(json_encode($retorno));
	}
}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE PRODUÇÂO/VENDAS";
include "cabecalho_new.php";

$array_estados = $array_estados();

$plugins = array(
	"lupa",
	"autocomplete",
	"datepicker",
	"mask",
	"dataTable",
	"shadowbox",
	"select2",
);

include "plugin_loader.php";

if (count($msg_erro["msg"]) > 0) { 
	$styleMsgErro = 'style="display:block;"';
} else {
	$styleMsgErro = 'style="display:none;"';
} ?>

<div id="msg-erro" class="alert alert-error" <?= $styleMsgErro; ?>>
	<h4><?= (count($msg_erro["msg"]) > 0) ? implode("<br />", $msg_erro["msg"]) : ""; ?></h4>
</div>

<div class="row">
	<b class="obrigatorio pull-right">* Campos obrigatórios</b>
</div>
<form id="frm_relatorio" name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br />
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span8'>
			<div class='control-group <?=(in_array("tipo", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='producao_venda'>Tipo</label>
				<div class='controls controls-row'>
					<div class='span6'>
						<label class="radio">
							<input type="radio" name="tipo" value="producao" <?= ($tipo == 'producao') ? "checked" : ""; ?>> Produção
		                </label>
		                <label class="radio">
							<input type="radio" name="tipo" value="venda" <?= ($tipo == 'venda') ? "checked" : ""; ?>> Venda
		                </label>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_inicial'>Data Inicial</label>
				<div class='controls controls-row'>
					<div class='span6'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?= $data_inicial; ?>">
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_final'>Data Final</label>
				<div class='controls controls-row'>
					<div class='span6'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?= $data_final; ?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class="span4">
			<div class='control-group'>
				<label class='control-label' for='linha_producao'>Linha de Produto</label>
				<div class='controls controls-row'>
					<?
					$sqlLPrd = "SELECT DISTINCT nome_comercial FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND ativo IS TRUE AND TRIM(nome_comercial) IS NOT NULL ORDER BY nome_comercial;";
					$resLPrd = pg_query($con, $sqlLPrd); ?>
					<select name="linha_producao[]" id="linha_producao" multiple="multiple" class='span12'>
						<? if (is_array($_POST["linha_producao"])) {
							$selected_linha_producao = $_POST['linha_producao'];
						}
						foreach (pg_fetch_all($resLPrd) as $key) { ?>
							<option value="<?= $key['nome_comercial']?>" <?= (in_array($key['nome_comercial'], $selected_linha_producao)) ? "selected" : ""; ?>>
								<?= $key['nome_comercial']; ?>
							</option>
						<? } ?>
					</select>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class='control-group'>
				<label class='control-label' for='familia'>Família</label>
				<div class='controls controls-row'>
					<?
					$sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = {$login_fabrica} AND ativo IS TRUE ORDER BY descricao;";
					$res = pg_query($con, $sql); ?>
					<select name="familia[]" id="familia" multiple="multiple" class='span12'>
						<? $selected_familia = $_POST['familia'];
						foreach (pg_fetch_all($res) as $key) { ?>
							<option value="<?= $key['familia']?>" <?= (in_array($key['familia'], $selected_familia)) ? "selected" : ""; ?>>
								<?= $key['descricao']; ?>
							</option>
						<? } ?>
					</select>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class="span4">
			<div class='control-group'>
				<label class='control-label' for='familia_grupo_sap'>Grupo Família</label>
				<div class='controls controls-row'>
					<?
					$sql = "SELECT DISTINCT JSON_FIELD('grupo_familia_desc', parametros_adicionais) AS familia_grupo_sap FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND ativo IS TRUE AND parametros_adicionais LIKE '%grupo_familia_desc%' ORDER BY familia_grupo_sap;";
					$res = pg_query($con, $sql); ?>
					<select name="familia_grupo_sap[]" id="familia_grupo_sap" multiple="multiple" class='span12'>
						<? $selected_familia_grupo_sap = $_POST['familia_grupo_sap'];
						foreach (pg_fetch_all($res) as $key) { ?>
							<option value="<?= $key['familia_grupo_sap']?>" <?= (in_array($key['familia_grupo_sap'], $selected_familia_grupo_sap)) ? "selected" : ""; ?>>
								<?= $key['familia_grupo_sap']; ?>
							</option>
						<? } ?>
					</select>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class='control-group'>
				<label class='control-label' for='origem'>Desc. Família</label>
				<div class='controls controls-row'>
					<?
					$sql = "SELECT DISTINCT JSON_FIELD('familia_desc', parametros_adicionais) AS familia_sap FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND ativo IS TRUE AND parametros_adicionais LIKE '%familia_desc%' ORDER BY familia_sap;";
					$res = pg_query($con,$sql); ?>
					<select name="familia_sap[]" id="familia_sap" multiple="multiple" class='span12'>
						<? $selected_origem = $_POST['familia_sap'];
						foreach (pg_fetch_all($res) as $key) { ?>
							<option value="<?= $key['familia_sap']?>" <?= (in_array($key['familia_sap'], $selected_origem)) ? "selected" : ""; ?>>
								<?= $key['familia_sap']; ?>
							</option>
						<? } ?>
					</select>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class="span4">
			<div class='control-group'>
				<label class='control-label' for='origem'>Origem</label>
				<div class='controls controls-row'>
					<?
					$sql = "SELECT DISTINCT origem FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND ativo IS TRUE AND TRIM(origem) IS NOT NULL ORDER BY origem;";
					$res = pg_query($con,$sql); ?>
					<select name="origem[]" id="origem" multiple="multiple" class='span12'>
						<? $selected_origem = $_POST['origem'];
						foreach (pg_fetch_all($res) as $key) { ?>
							<option value="<?= $key['origem']?>" <?= (in_array($key['origem'], $selected_origem)) ? "selected" : ""; ?>>
								<?= $deParaOrigem[$key['origem']]; ?>
							</option>
						<? } ?>
					</select>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class='control-group'>
				<label class='control-label' for='origem'>Product Division</label>
				<div class='controls controls-row'>
					<?
					$sql = "SELECT DISTINCT JSON_FIELD('pd', parametros_adicionais) AS pd FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND ativo IS TRUE AND parametros_adicionais like '%pd%' ORDER BY pd;";
					$res = pg_query($con,$sql); ?>
					<select name="pd[]" id="pd" multiple="multiple" class='span12'>
						<? $selected_origem = $_POST['pd'];
						foreach (pg_fetch_all($res) as $key) { ?>
							<option value="<?= $key['pd']?>" <?= (in_array($key['pd'], $selected_origem)) ? "selected" : ""; ?>>
								<?= $key['pd']; ?>
							</option>
						<? } ?>
					</select>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='por_produto'>Por produto</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<label class="checkbox" for="por_produto">
							<input type="checkbox" name="por_produto" value='t' <? if($por_produto == 't') echo " checked"?>>
						</label>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class="row-fluid">
		<p class="tac">
			<button type="button" id="btn_acao" class='btn' onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<button type="button" id="btn_gerar_excel" class='btn btn-primary'>Gerar Excel</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p>
	</div>
</form>
</div>
<? if (isset($btn_acao) && $btn_acao != 'excel' && count($msg_erro['msg']) == 0) {
	if ($count > 0) {
		if ($count > 500) { ?>
			<div class='alert'>
				<h6>Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.</h6>
			</div>
		<? } ?>
		<div class='tal' style='padding-rigth: 5px !important;'>
			<table id="resultado_pesquisa" class='table table-striped table-bordered table-hover table-large'>
				<thead>
					<tr class='titulo_coluna'>
						<th class="tac">Ano</th>
						<th class="tac">Mês</th>
						<th class="tac">Ref. Produto</th>
						<th class="tac">Desc. Produto</th>
						<th class="tac">Família</th>
						<th class="tac">Cód. Família SAP</th>
						<th class="tac">Desc. Família SAP</th>
						<th class="tac">Linha Fabricação</th>
						<th class="tac">Product Division</th>
						<th class="tac">Origem</th>
						<th class="tac">Número de Série</th>
						<th class="tac">Qtde.</th>
					</tr>
				</thead>
				<tbody>
					<? for ($xi = 0; $xi < $count; $xi++) {
						$xmes = pg_fetch_result($resPesquisa, $xi, "mes");
						$xano = pg_fetch_result($resPesquisa, $xi, "ano");
						$xserie = pg_fetch_result($resPesquisa, $xi, "serie");
						$xproduto_referencia = pg_fetch_result($resPesquisa, $xi, "produto_referencia");
						$xproduto_descricao = pg_fetch_result($resPesquisa, $xi, "produto_descricao");
						$xproduto_nome_comercial = pg_fetch_result($resPesquisa, $xi, "produto_nome_comercial");
						$xproduto_origem = pg_fetch_result($resPesquisa, $xi, "produto_origem");
						$xfamilia_descricao = pg_fetch_result($resPesquisa, $xi, "familia_descricao");
						$xproduto_codigo_familia = pg_fetch_result($resPesquisa, $xi, "familia_codigo_sap");
						$xproduto_descricao_familia = pg_fetch_result($resPesquisa, $xi, "familia_descricao_sap");
						$xproduto_product_division = pg_fetch_result($resPesquisa, $xi, "product_division");
						$xqtde = pg_fetch_result($resPesquisa, $xi, "qtde");

						if ($xproduto_origem == "IMP" && $por_produto != 't' && $tipo == "producao") {
							$semana_fab = substr($xserie, 2, 2);
							$ano_fab = substr($xserie, 4, 2);
						
							$mes_aux = ($semana_fab * 7) / 30;
							$mes_aux = (int) $mes_aux;

							if ($mes_aux == 0) {
								$mes_aux = 1;
							}

							if (strlen($mes_aux) == 1) {
								$mes_aux = "0".$mes_aux;
							}

							$xmes = $mes_aux."/20".$ano_fab;
							$xano = "20".$ano_fab;
						}
						
						if ($tipo == 'producao') {
							if (!empty($xmes)) {
								$xmes = explode("/", $xmes);
								$xmes_aux = strtoupper($arr_meses[$xmes[0]])."/".$xmes[1];
							} else {
								$xmes_aux = "";
							}
						} else {
							$xmes_aux = $xmes;
						} ?>
				
						<tr>
							<td><?= $xano; ?></td>
							<td><?= $xmes_aux; ?></td>
							<td><?= $xproduto_referencia; ?></td>
							<td><?= $xproduto_descricao; ?></td>
							<td><?= $xfamilia_descricao; ?></td>
							<td><?= $xproduto_codigo_familia; ?></td>
							<td><?= $xproduto_descricao_familia; ?></td>
							<td><?= $xproduto_nome_comercial; ?></td>
							<td><?= $xproduto_product_division; ?></td>
							<td><?= $deParaOrigem[$xproduto_origem]; ?></td>
							<td><?= $xserie; ?></td>
							<td><?= $xqtde; ?></td>
						</tr>
					<? } ?>
				</tbody>
			</table>
		</div>
		<br />
		<? $jsonPOST = excelPostToJson($_POST); ?>
		<div class="btn_excel gerar_excel">
			<input type="hidden" class="jsonPOST" value='<?= $jsonPOST; ?>' />
			<span><img src="imagens/excel.png" /></span>
			<span class="txt">Gerar Arquivo Excel</span>
		</div>
	<? } else { ?>
		<div class="alert">
			<h4>Nenhum resultado encontrado para essa pesquisa.</h4>
		</div>
		<br />
	<? }
} ?>

<script type="text/javascript">
	Shadowbox.init();
	$.datepickerLoad(Array("data_final", "data_inicial"));

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});

	$("select").select2();

	<? if ($count > 50) { ?>
		$.dataTableLoad({ table: "#resultado_pesquisa" });
	<? } ?>

	$("#btn_gerar_excel").click(function() {
		var form = $("#frm_relatorio");
		var btn = $(form).find("#btn_click");

		if($(btn).val() == ''){
			valor = "excel";
		}

		if ($(btn).val().length > 0) {
			alert("Aguarde Submissão...");
		} else {
			$(btn).val(valor);
			
			$.ajax({
				url: "<?= $_SERVER['PHP_SELF']; ?>",
				type: "POST",
				data: $(form).serialize(),
				beforeSend: function () {
					loading("show");
				},
				complete: function (data) {
					$("#btn_click").val('');
					retorno = $.parseJSON(data.responseText);
					console.log(retorno.erro);
					if (typeof retorno.erro == "undefined") {
						window.open(retorno.sucesso, "_blank");
						$(form).trigger('reset');
					} else {
						$("#msg-erro").html("<h4>"+retorno.erro+"</h4>").show();
						setTimeout(function(){$('#msg-erro').hide();}, 4000);
					}

					loading("hide");
				}
			})
		}
	});
</script>

<? include 'rodape.php'; ?>
