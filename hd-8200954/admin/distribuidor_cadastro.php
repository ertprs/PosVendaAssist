<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

use Posvenda\DistribuidorSLA;

$layout_menu = "cadastro";

/**
 * Area para colocar os AJAX
 */
if (isset($_POST["ajax_busca_cidade"]) && !empty($_POST["estado"])) {
    $estado = strtoupper($_POST["estado"]);

    if (array_key_exists($estado, $array_estados())) {
        $sql = "SELECT DISTINCT * FROM (
                    SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$estado}')
                    UNION (
                        SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('{$estado}')
                    )
                ) AS cidade
                ORDER BY cidade ASC";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $array_cidades = array();

            while ($result = pg_fetch_object($res)) {
                $array_cidades[] = $result->cidade;
            }

            $retorno = array("cidades" => $array_cidades);
        } else {
            $retorno = array("error" => utf8_encode("Nenhuma cidade encontrada para o estado: {$estado}"));
        }
    } else {
        $retorno = array("error" => utf8_encode("Estado não encontrado"));
    }

    exit(json_encode($retorno));
}

$btn_acao = trim($_REQUEST['btn_acao']);
$distribuidor_sla = $_REQUEST['distribuidor_sla'];
unset($form);

if ($btn_acao == "gravar" || ($btn_acao == "alterar" && strlen($distribuidor_sla) > 0)) {

	$form['distribuidor_sla'] = $distribuidor_sla;
	$form['descricao'] = $_REQUEST['descricao'];
	$form['centro'] = $_REQUEST['centro'];
	$form['regiao'] = $_REQUEST['regiao'];
	$form['cidade'] = $_REQUEST['cidade'];
	$form['franquia'] = $_REQUEST['franquia'];
	$form['unidade_negocio'] = $_REQUEST['unidade_negocio'];

	if (strlen($form['descricao']) == 0) {
		$msg_erro['msg'][] = "É necessário informar uma Descrição";
		$msg_erro['campos'][] = "descricao";
	}

	if (strlen($form['centro']) == 0) {
		$msg_erro['msg'][] = "É necessário informar um Código";
		$msg_erro['campos'][] = "centro";
	}

	if (strlen($form['regiao']) == 0) {
		$msg_erro['msg'][] = "É necessário selecionar uma Região";
		$msg_erro['campos'][] = "regiao";
	}

	if (strlen($form['cidade']) == 0) {
		$msg_erro['msg'][] = "É necessário selecionar uma Cidade";
		$msg_erro['campos'][] = "cidade";
	} else {
		$sqlCidade = "SELECT DISTINCT * FROM (
					SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER('".$form['cidade']."')
					UNION (
					SELECT cod_ibge FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER('".$form['cidade']."')
					)
				) AS cidade
				ORDER BY cidade ASC;";

		$resCidade = pg_query($con, $sqlCidade);

		if (pg_num_rows($resCidade) == 0 || strlen(pg_last_error()) > 0) {
			$msg_erro['msg'][] = "Ocorreu um problema na seleção da cidade.";
			$msg_erro['campos'][] = "cidade";
		} else {
			$form['id_cidade'] = pg_fetch_result($resCidade, 0, 'cidade');
		}
	}

	if (strlen($form['franquia']) == 0) {
		$msg_erro['msg'][] = "É necessário informar uma Franquia";
		$msg_erro['campos'][] = "franquia";
	}

	if (strlen($form['unidade_negocio']) == 0) {
		$msg_erro['msg'][] = "É necessário selecionar uma Unidade de Negócio";
		$msg_erro['campos'][] = "unidade_negocio";
	}

	if (count($msg_erro['msg']) == 0) {
		if (strlen($distribuidor_sla) == 0) {
			$sql = "INSERT INTO tbl_distribuidor_sla (
							fabrica,
							descricao,
							centro,
							regiao,
							cidade,
							franquia,
							unidade_negocio
						) VALUES (
							{$login_fabrica},
							'{$form['descricao']}',
							'{$form['centro']}',
							'{$form['regiao']}',
							{$form['id_cidade']},
							'{$form['franquia']}',
							{$form['unidade_negocio']}
						);";
		} else {
			$sql = "UPDATE tbl_distribuidor_sla SET
						descricao = '{$form['descricao']}',
						centro = '{$form['centro']}',
						regiao = '{$form['regiao']}',
						cidade = {$form['id_cidade']},
						franquia = '{$form['franquia']}',
						unidade_negocio = {$form['unidade_negocio']}
				WHERE fabrica = {$login_fabrica}
				AND distribuidor_sla = {$form['distribuidor_sla']};";
		}

		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			$msg_erro['msg'][] = "Ocorreu um erro na requisição das informações dos distribuidores.";
		} else {
			$msg_sucesso = "Os dados foram gravados com sucesso.";
			unset($form);
		}
	}

} else if ($btn_acao == "listar_todos") {

	$sql = "SELECT
			tbl_distribuidor_sla.distribuidor_sla,
			tbl_distribuidor_sla.centro,
			tbl_distribuidor_sla.descricao,
			tbl_distribuidor_sla.regiao,
			tbl_unidade_negocio.nome AS cidade,
			tbl_distribuidor_sla.unidade_negocio
		FROM tbl_distribuidor_sla
		LEFT JOIN tbl_unidade_negocio ON tbl_unidade_negocio.codigo=tbl_distribuidor_sla.unidade_negocio
		WHERE fabrica = {$login_fabrica};";

	$res = pg_query($con, $sql);

	if (strlen(pg_last_error()) > 0) {
		$msg_erro['msg'][] = "Ocorreu um erro na requisição das informações dos distribuidores.";
	}

	$qtde_distribuidores = pg_num_rows($res);
	$distribuidores = pg_fetch_all($res);

} else if ($btn_acao == "selecionar" && strlen($distribuidor_sla) > 0) {

	$sql = "SELECT
			tbl_distribuidor_sla.distribuidor_sla,
			tbl_distribuidor_sla.centro,
			tbl_distribuidor_sla.descricao,
			tbl_distribuidor_sla.regiao,
			tbl_unidade_negocio.nome AS cidade,
			tbl_distribuidor_sla.franquia,
			tbl_distribuidor_sla.unidade_negocio
		FROM tbl_distribuidor_sla
		LEFT JOIN tbl_unidade_negocio ON tbl_unidade_negocio.codigo=tbl_distribuidor_sla.unidade_negocio
		WHERE fabrica = {$login_fabrica}
		AND distribuidor_sla = {$distribuidor_sla};";

	$res = pg_query($con, $sql);

	if (strlen(pg_last_error()) > 0) {
		$msg_erro['msg'][] = "Ocorreu um erro ao selecionar o distribuidor.";
	} else if (pg_num_rows($res) == 0) {
		$msg_erro['msg'][] = "Distribuidor não encontrado.";
	} else {
		$form['distribuidor_sla'] = pg_fetch_result($res, 0, 'distribuidor_sla');
		$form['descricao'] = pg_fetch_result($res, 0, 'descricao');
		$form['centro'] = pg_fetch_result($res, 0, 'centro');
		$form['regiao'] = pg_fetch_result($res, 0, 'regiao');
		$form['cidade'] = pg_fetch_result($res, 0, 'cidade');
		$form['franquia'] = pg_fetch_result($res, 0, 'franquia');
		$form['unidade_negocio'] = pg_fetch_result($res, 0, 'unidade_negocio');
	}

} else if ($btn_acao == "deletar" && strlen($distribuidor_sla) > 0) {
	$sql = "DELETE FROM tbl_distribuidor_sla WHERE fabrica = {$login_fabrica} AND distribuidor_sla = {$distribuidor_sla};";
	$res = pg_query($con, $sql);

	if (strlen(pg_last_error()) > 0) {
		$msg_erro['msg'][] = "Ocorreu um erro na exclusão do distribuidor.";
	} else {
		$msg_sucesso = "Registro excluído com sucesso.";
		$sql = "SELECT
				tbl_distribuidor_sla.distribuidor_sla,
				tbl_distribuidor_sla.centro,
				tbl_distribuidor_sla.descricao,
				tbl_distribuidor_sla.regiao,
				tbl_unidade_negocio.nome AS cidade,
				tbl_distribuidor_sla.unidade_negocio
			FROM tbl_distribuidor_sla
			LEFT JOIN tbl_unidade_negocio ON tbl_unidade_negocio.codigo=tbl_distribuidor_sla.unidade_negocio
			WHERE fabrica = {$login_fabrica};";

		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			$msg_erro['msg'][] = "Ocorreu um erro na requisição das informações dos distribuidores.";
		}

		$qtde_distribuidores = pg_num_rows($res);
		$distribuidores = pg_fetch_all($res);
	}
}

$title = "CADASTRO DE DISTRIBUIDORES";
include 'cabecalho_new.php';

$plugins = array("multiselect",
		"lupa",
		"autocomplete",
		"datepicker",
		"mask",
		"dataTable",
		"shadowbox"
		);

include "plugin_loader.php";

$title_page  = "Cadastro";

if ($btn_acao == "selecionar") {
	$title_page = "Alteração de Cadastro";
} ?>

<script type="text/javascript">
	$(function () {
		$("#regiao").change(function () {
			busca_cidade($(this).val());
		});
	});

	/**
	 * Função que busca as cidades do estado e popula o select cidade
	 */
	function busca_cidade(estado, cidade) {
		$("#cidade").find("option").first().nextAll().remove();

		if (estado.length > 0) {
			$.ajax({
				url: "<?= $PHP_SELF; ?>",
				type: "POST",
				data: { ajax_busca_cidade: true, estado: estado },
				beforeSend: function() {
					if ($("#cidade").next("img").length == 0) {
						$("#cidade").hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
					}
				},
				complete: function(data) {
					data = $.parseJSON(data.responseText);

					if (data.error) {
						alert(data.error);
					} else {
						$.each(data.cidades, function(key, value) {
							var option = $("<option></option>", { value: value, text: value});

							$("#cidade").append(option);
						});
					}
					$("#cidade").show().next().remove();
				}
			});
		}
		if (typeof cidade != "undefined" && cidade.length > 0) {
			$('#cidade option[value='+cidade+']').attr('selected','selected');
		}
	}
</script>

<? if (count($msg_erro['msg']) > 0) { ?>
        <div class="alert alert-error">
            <h4><?= implode("<br />", $msg_erro['msg']); ?></h4>
        </div>
<? }
if ($msg_sucesso) { ?>
    <div class="alert alert-success">
        <h4><?= $msg_sucesso; ?></h4>
    </div>
<? } ?>
<div class="row">
	<b class="obrigatorio pull-right">* Campos obrigatórios</b>
</div>

<form name='frm_distribuidor' method="POST" action="<?= $PHP_SELF; ?>" class='form-search form-inline tc_formulario'>
	<input type="hidden" name="distribuidor_sla" id="distribuidor_sla" value="<?= $form['distribuidor_sla']; ?>" />
	<div class="titulo_tabela"><?= $title_page; ?></div>
	<br />
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span6'>
			<div class='control-group <?= (in_array("descricao", $msg_erro["campos"])) ? "error" : ""; ?>'>
				<label class='control-label' for='descricao'>Descrição:</label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
					<input type="text" id="descricao" name="descricao" class="span12" maxlength="50" value="<?= $form['descricao']; ?>" />
				</div>
			</div>
		</div>
		<div class='span2'>
			<div class='control-group <?= (in_array("centro", $msg_erro["campos"])) ? "error" : ""; ?>'>
				<label class='control-label' for='centro'>Código:</label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
					<input type="text" id="centro" name="centro" class="span12" maxlength="4" value="<?= $form['centro']; ?>" />
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span3'>
			<div class='control-group <?= (in_array("regiao", $msg_erro["campos"])) ? "error" : ""; ?>'>
				<label class='control-label' for='regiao'>Região:</label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
					<select id="regiao" name="regiao" class="span12">
						<option value=""></option>
						<? foreach ($array_estados() as $uf => $estado) { 
							$selectedUf = ($uf == $form['regiao']) ? "SELECTED" : ""; ?>
							<option value="<?= $uf; ?>" <?= $selectedUf; ?>><?= $estado; ?></option>
						<? } ?>
					</select>
				</div>
			</div>
		</div>
		<div class='span5'>
			<div class='control-group <?= (in_array("cidade", $msg_erro["campos"])) ? "error" : ""; ?>'>
				<label class='control-label' for='cidade'>Cidade:</label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
					<select id="cidade" name="cidade" class="span12">
						<option value="">SELECIONE</option>
						<? if (strlen($form['regiao']) > 0) {
							$sql = "SELECT DISTINCT * FROM (
										SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".$form['regiao']."')
										UNION (
										SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".$form['regiao']."')
										)
									) AS cidade
									ORDER BY cidade ASC";
							$res = pg_query($con, $sql);
							if (pg_num_rows($res) > 0) {
								while ($result = pg_fetch_object($res)) {
									$selected  = (trim($result->cidade) == trim($form['cidade'])) ? "SELECTED" : ""; ?>
									<option value='<?= $result->cidade; ?>' <?= $selected; ?>><?= $result->cidade; ?></option>
								<? }
							}
						} ?>
					</select>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?= (in_array("franquia", $msg_erro["campos"])) ? "error" : ""; ?>'>
				<label class='control-label' for='franquia'>Franquia:</label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
					<input type="text" id="franquia" name="franquia" class="span12" maxlength="10" value="<?= $form['franquia']; ?>" />
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?= (in_array("unidade_negocio", $msg_erro["campos"])) ? "error" : ""; ?>'>
				<label class='control-label' for='unidade_negocio'>Unidade Negócio:</label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
					    <select name="unidade_negocio"  class="span12" id="unidade_negocio">
						<option value="">Escolha...</option>
						<?
				                $oDistribuidorSLA = new DistribuidorSLA();
						$oDistribuidorSLA->setFabrica($login_fabrica);
						$distribuidores_disponiveis = $oDistribuidorSLA->SelectUnidadeNegocioNotIn();
						    
						foreach ($distribuidores_disponiveis as $unidadeNegocio) {
						    $selected = ($unidadeNegocio["unidade_negocio"] == $form['unidade_negocio']) ? 'SELECTED' : ''; ?>
						    <option value="<?= $unidadeNegocio['unidade_negocio']; ?>" <?= $selected; ?>><?= $unidadeNegocio['cidade']; ?></option>
						<? } ?>
                    			    </select>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>
	<div class="row-fluid tac">
		<input type="hidden" id="btn_acao" name="btn_acao" value="" />
		<? if (strlen($form['distribuidor_sla']) > 0) { ?>
			<input type="button" name="btn_alterar" value="Alterar" class="btn btn-default" onclick="if ($('#btn_acao').val() == '') { $('#btn_acao').val('alterar'); $(this).parents('form').submit(); } else { alert('Aguarde... O formulário está sendo processado!'); }" />
		<? } else { ?>
			<input type="button" name="btn_gravar" value="Gravar" class="btn btn-default" onclick="if ($('#btn_acao').val() == '') { $('#btn_acao').val('gravar'); $(this).parents('form').submit(); } else { alert('Aguarde... O formulário está sendo processado!'); }" />
		<? } ?>
		<input type="button" class="btn btn-primary" value="Listar Todos" onclick="if ($('#btn_acao').val() == '') { $('#btn_acao').val('listar_todos'); $(this).parents('form').submit(); } else { alert('Aguarde... O formulário está sendo processado!'); }" />
	</div>
</form>
<? if (in_array($btn_acao, array("listar_todos", "deletar"))) {
	if ($qtde_distribuidores > 0) { ?>
		<table id='tbl_distribuidor_sla' class='table table-striped table-bordered table-hover table-fixed'>
			<thead>
				<tr class='titulo_coluna'>
					<th>Código</th>
					<th>Descricao</th>
					<th>Região</th>
					<th>Unidade de Negócio</th>
					<th>Ações</th>
				</tr>
			</thead>
			<tbody>
				<? foreach ($distribuidores as $distribuidor) {

                        $xunidade_negocio = $distribuidor['unidade_negocio']." - ".$distribuidor['cidade'];
                        
						
					?>
					<tr>
						<td class="tac"><?= $distribuidor['centro']; ?></td>
						<td><?= $distribuidor['descricao']; ?></td>
						<td class="tac"><?= $distribuidor['regiao']; ?></td>
						<td><?= $xunidade_negocio; ?></td>
						<td class="tac">
							<a href="<?= $PHP_SELF; ?>?btn_acao=selecionar&distribuidor_sla=<?= $distribuidor['distribuidor_sla']; ?>" target="_self" class="btn btn-default">Editar</a>
							<a href="<?= $PHP_SELF; ?>?btn_acao=deletar&distribuidor_sla=<?= $distribuidor['distribuidor_sla']; ?>" onclick="if (confirm('Confirma a exclusão do item?')) { return true; } else {return false; }" target="_self" class="btn btn-danger">Deletar</a>
						</td>

					</tr>
				<? } ?>
			</tbody>
		</table>
	<? 
	if ($qtde_distribuidores > 50) { ?>
		<script>
			$.dataTableLoad({ table: "#tbl_distribuidor_sla" });
		</script>
	<? }
	} else { ?>
	<div class="alert">
		<h4>Nenhum resultado encontrado</h4>
	</div>
	<? }
}
include "rodape.php"; ?>
