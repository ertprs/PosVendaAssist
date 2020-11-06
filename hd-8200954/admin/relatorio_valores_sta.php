<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if (isset($_POST['pesquisar'])) {

	$cnpj_posto 		= trim($_POST['cnpj_posto']);

	if (strlen($cnpj_posto) > 0) {
		$sql = "SELECT tbl_posto_fabrica.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND UPPER(tbl_posto.cnpj) = UPPER('{$cnpj_posto}')";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Posto não encontrado";
			$msg_erro["campos"][] = "posto";
		} else {
			$posto  = pg_fetch_result($res, 0, "posto");
			$cond[] = "AND tbl_posto.posto = {$posto}";
		}
	}

	if (count($_POST['unidade_negocio']) > 0) {

		$cond[] = "AND tbl_distribuidor_sla.unidade_negocio IN ('".implode("','", $_POST['unidade_negocio'])."')";

	}

	if (count($_POST['linha']) > 0) {

		$condExcel[] = "AND tbl_excecao_mobra.linha IN ('".implode("','", $_POST['linha'])."')";

	}

	if (count($_POST['classificacao']) > 0) {

		$condExcel[] = "AND tbl_excecao_mobra.classificacao IN ('".implode("','", $_POST['classificacao'])."')";

	}

	if (count($_POST['unidade_negocio_exc']) > 0) {

		$condExcel[] = "AND tbl_excecao_mobra.distribuidor_sla IN ('".implode("','", $_POST['unidade_negocio_exc'])."')";

	}

	if (count($_POST['tipo_atendimento']) > 0) {

		$condExcel[] = "AND tbl_excecao_mobra.tipo_atendimento IN ('".implode("','", $_POST['tipo_atendimento'])."')";

	}

	if (count($cond) == 0 && count($condExcel) == 0) {
		$msg_erro["msg"][] = "Informe algum parâmetro para pesquisa";
	}

}

$postosDesconsiderar = "AND tbl_posto_fabrica.posto NOT IN (443660,444985,627863)";

$cond[] 	 = $postosDesconsiderar;
$condExcel[] = $postosDesconsiderar;

if (isset($_POST['gerar_excel'])) {

	$sqlExcel    = "SELECT tbl_posto.posto,
						   tbl_posto.nome AS razao_social,
						   tbl_posto_fabrica.codigo_posto,
						   tbl_distribuidor_sla.unidade_negocio || '-' || tbl_unidade_negocio.nome as unidade_negocio,
						   round(tbl_posto_preco_unidade.preco::numeric,2) AS valor_mo,
						   round(tbl_posto_fabrica.valor_km::numeric,2) as valor_km,
						   round(tbl_excecao_mobra.mao_de_obra::numeric, 2) as mo_excecao,
						   tbl_linha.nome as nome_linha,
						   tbl_familia.descricao as nome_familia,
						   tbl_produto.descricao as nome_produto,
						   tbl_tipo_atendimento.descricao as descricao_tipo_atendimento,
						   TO_CHAR(tbl_excecao_mobra.data_input, 'dd/mm/yyyy HH24:MI') as data_alt,
						   sla_exc.unidade_negocio || '-' || cidade_exc.nome as unidade_excecao,
						   tbl_excecao_mobra.excecao_mobra as codigo_excecao
					FROM tbl_posto
					JOIN tbl_posto_fabrica 		    	ON tbl_posto.posto = tbl_posto_fabrica.posto
					AND tbl_posto_fabrica.fabrica = {$login_fabrica}
					JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
					AND tbl_tipo_posto.tecnico_proprio IS NOT TRUE
					JOIN tbl_distribuidor_sla_posto 	ON tbl_distribuidor_sla_posto.posto = tbl_posto.posto
					AND tbl_distribuidor_sla_posto.fabrica = {$login_fabrica}
					JOIN tbl_distribuidor_sla 			ON tbl_distribuidor_sla.distribuidor_sla = tbl_distribuidor_sla_posto.distribuidor_sla
					AND tbl_distribuidor_sla.fabrica = {$login_fabrica}
					--LEFT JOIN tbl_cidade 				ON tbl_cidade.cidade = tbl_distribuidor_sla.cidade
					LEFT JOIN tbl_unidade_negocio ON tbl_unidade_negocio.codigo=tbl_distribuidor_sla.unidade_negocio
					LEFT JOIN tbl_posto_preco_unidade   ON tbl_posto_preco_unidade.posto = tbl_posto.posto
					AND tbl_posto_preco_unidade.fabrica = {$login_fabrica}
					AND tbl_posto_preco_unidade.distribuidor_sla = tbl_distribuidor_sla.distribuidor_sla
					LEFT JOIN tbl_excecao_mobra			ON tbl_excecao_mobra.posto = tbl_posto.posto
					AND tbl_excecao_mobra.fabrica = {$login_fabrica} AND (tbl_excecao_mobra.distribuidor_sla = tbl_distribuidor_sla.distribuidor_sla or tbl_excecao_mobra.distribuidor_sla isnull)
					LEFT JOIN tbl_linha 				ON tbl_excecao_mobra.linha = tbl_linha.linha
					AND tbl_linha.fabrica = {$login_fabrica}
					LEFT JOIN tbl_produto ON tbl_excecao_mobra.produto = tbl_produto.produto
					AND tbl_produto.fabrica_i =  {$login_fabrica}
					LEFT JOIN tbl_familia ON tbl_familia.familia = tbl_excecao_mobra.familia
					AND tbl_familia.fabrica = {$login_fabrica}
					LEFT JOIN tbl_tipo_atendimento ON tbl_excecao_mobra.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento
					LEFT JOIN tbl_distribuidor_sla sla_exc ON sla_exc.distribuidor_sla = tbl_excecao_mobra.distribuidor_sla
					LEFT JOIN tbl_cidade cidade_exc ON cidade_exc.cidade = sla_exc.cidade
					WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
					AND   tbl_posto_fabrica.credenciamento != 'DESCREDENCIADO'
					".implode(" ", $cond)."
					".implode(" ", $condExcel)."
							 ";
	$resExcel = pg_query($con, $sqlExcel);

	$data = date("d-m-Y-H:i");
	$fileName = "relatorio_valores_sta-{$data}.csv";

	$file = fopen("/tmp/{$fileName}", "w");

	fwrite($file, "Codigo Posto;Razao Social;Unidade Negocio;Valor por OS;Valor do KM;Modal;Codigo Excecao;Linha (excecao MO);Familia (excecao MO);Produto (excecao MO);Unidade Negocio (excecao MO) ;Tipo de Atendimento (excecao MO);Mao-de-Obra (excecao MO) \n");

	while ($dadosValores = pg_fetch_assoc($resExcel)) {

		$modal 			  = empty($dadosValores['valor_mo']) ? "OS" : "Parque";
		$valor_mo         = empty($dadosValores['valor_mo']) ? "100.00" : $dadosValores['valor_mo'];

		$tbody .=  $dadosValores['codigo_posto'].";"
				  .$dadosValores['razao_social'].";"
				  .$dadosValores['unidade_negocio'].";"
				  .$valor_mo.";"
				  .$dadosValores['valor_km'].";"
				  .$modal.";"
				  .$dadosValores['codigo_excecao'].";"
				  .$dadosValores['nome_linha'].";"
				  .$dadosValores['nome_familia'].";"
				  .$dadosValores['nome_produto'].";"
				  .$dadosValores['unidade_excecao'].";"
				  .$dadosValores['descricao_tipo_atendimento'].";"
				  .$dadosValores['mo_excecao']."\n";

		

	}

	fwrite($file, $tbody);

	fclose($file);

	if (file_exists("/tmp/{$fileName}")) {
		system("mv /tmp/{$fileName} xls/{$fileName}");

		echo "xls/{$fileName}";
	}

	exit;

} else {

	$sqlPesquisa = "SELECT tbl_posto.posto,
					   tbl_posto.nome AS razao_social,
					   tbl_posto_fabrica.codigo_posto,
					   string_agg(tbl_distribuidor_sla.unidade_negocio || ' - ' || tbl_unidade_negocio.nome, '<br />') as unidade_negocio,
					   tbl_posto_preco_unidade.preco AS valor_mo,
					   tbl_posto_fabrica.valor_km,
					   (
						   	SELECT string_agg(round(tbl_excecao_mobra.mao_de_obra::numeric, 2)::text, '<br />')
						   	FROM tbl_excecao_mobra
						   	WHERE tbl_excecao_mobra.posto = tbl_posto.posto
						   	AND tbl_excecao_mobra.fabrica = {$login_fabrica}
						   	LIMIT 1
					   ) AS excecao_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica 		    	ON tbl_posto.posto = tbl_posto_fabrica.posto
					AND tbl_posto_fabrica.fabrica = {$login_fabrica}
					JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
					AND tbl_tipo_posto.tecnico_proprio IS NOT TRUE
					JOIN tbl_distribuidor_sla_posto 	ON tbl_distribuidor_sla_posto.posto = tbl_posto.posto
					AND tbl_distribuidor_sla_posto.fabrica = {$login_fabrica}
					JOIN tbl_distribuidor_sla 			ON tbl_distribuidor_sla.distribuidor_sla = tbl_distribuidor_sla_posto.distribuidor_sla
					AND tbl_distribuidor_sla.fabrica = {$login_fabrica}
					--LEFT JOIN tbl_cidade 				ON tbl_cidade.cidade = tbl_distribuidor_sla.cidade
					LEFT JOIN tbl_unidade_negocio ON tbl_unidade_negocio.codigo=tbl_distribuidor_sla.unidade_negocio
					LEFT JOIN tbl_posto_preco_unidade   ON tbl_posto_preco_unidade.posto = tbl_posto.posto
					AND tbl_posto_preco_unidade.fabrica = {$login_fabrica}
					WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
					".implode(" ", $cond)."
					GROUP BY tbl_posto.nome,
							 tbl_posto_fabrica.codigo_posto,
							 tbl_posto_preco_unidade.preco,
							 tbl_posto_fabrica.valor_km,
							 tbl_posto.posto";
	$resPesquisa = pg_query($con, $sqlPesquisa);

}

$layout_menu = "gerencia";
$title = "Relatório de Valores Acordados STAs";
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"multiselect"
);

include("plugin_loader.php");
?>
<script>
	$(function() {

		$.autocompleteLoad(Array("posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$("select").multiselect({
            selectedText: "selecionados # de #"
        });

	});

	function retorna_posto(retorno){
	    $("#cnpj_posto").val(retorno.cnpj);
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
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>
	<div class="row-fluid">
        <div class='span2'></div>
            <div class="span4">
                <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='CNPJ'>CNPJ do Posto</label><br />
                    <div class='controls controls-row input-append'>
                        <input type='text' class="span10" name='cnpj_posto' id='cnpj_posto' maxlength='18' value='<?= $cnpj_posto ?>' class='frm'>
                         <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="cnpj" />
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='Nome do Posto'>Nome do Posto</label>
                    <div class='controls controls-row input-append'>
                        <input type='text' name='posto_nome' value='<?= $posto_nome ?>' class='frm' id="descricao_posto">
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        <div class='span2'></div>
    </div>
    <div class="row-fluid">
        <div class='span2'></div>
        <div class="span4">
            <div class='control-group <?=(in_array("unidade_negocio", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='Estado'>Unidade de Negócio (posto)</label>
                <div class='controls controls-row'>
                    <select multiple name='unidade_negocio[]' id='unidade_negocio'>
                        <?php
                            $sqlUn = "SELECT codigo, nome, unidade_negocio
                            		  FROM tbl_unidade_negocio";
                            $resUn = pg_query($con, $sqlUn);

                            while ($dadosUn = pg_fetch_assoc($resUn)) {

                            	$selected = (in_array($dadosUn['codigo'], $_POST['unidade_negocio'])) ? "selected" : "";

                            ?>
                            	<option value="<?= $dadosUn['codigo'] ?>" <?= $selected ?>>
                            		<?= $dadosUn['codigo'] ?> - <?= $dadosUn['nome'] ?>
                            	</option>
                            <?php
                            }
                        ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class='titulo_tabela '>Filtros das exceções (excel)</div>
	<br/>
    <div class="row-fluid">
        <div class='span2'></div>
        <div class="span4">
            <div class='control-group <?=(in_array("unidade_negocio", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='Estado'>Classificação</label>
                <div class='controls controls-row'>
                    <select multiple name='classificacao[]' id='classificacao'>
                        <?php
                            $sqlCla = "SELECT classificacao, descricao 
                            		  FROM tbl_classificacao 
                            		  WHERE fabrica = {$login_fabrica} 
                            		  ORDER BY descricao";
                            $resCla = pg_query($con, $sqlCla);

                            while ($dadosCla = pg_fetch_assoc($resCla)) {

                            	$selected = (in_array($dadosCla['classificacao'], $_POST['classificacao'])) ? "selected" : "";

                            ?>
                            	<option value="<?= $dadosCla['classificacao'] ?>" <?= $selected ?>>
                            		<?= $dadosCla['descricao'] ?>
                            	</option>
                            <?php
                            }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class='control-group <?=(in_array("unidade_negocio", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='Estado'>Unidade de Negócio (exceção)</label>
                <div class='controls controls-row'>
                    <select multiple name='unidade_negocio_exc[]' id='unidade_negocio_exc'>
                        <?php
                            $sqlUn = "SELECT distribuidor_sla, unidade_negocio, descricao
                            		  FROM tbl_distribuidor_sla
                            		  JOIN tbl_cidade USING(cidade)";
                            $resUn = pg_query($con, $sqlUn);

                            while ($dadosUn = pg_fetch_assoc($resUn)) {

                            	$selected = (in_array($dadosUn['distribuidor_sla'], $_POST['unidade_negocio_exc'])) ? "selected" : "";

                            ?>
                            	<option value="<?= $dadosUn['distribuidor_sla'] ?>" <?= $selected ?>>
                            		<?= $dadosUn['unidade_negocio'] ?> - <?= $dadosUn['descricao'] ?>
                            	</option>
                            <?php
                            }
                        ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="row-fluid">
        <div class='span2'></div>
        <div class="span4">
            <div class='control-group <?=(in_array("unidade_negocio", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='Estado'>Tipo de Atendimento</label>
                <div class='controls controls-row'>
                    <select multiple name='tipo_atendimento[]' id='tipo_atendimento'>
                        <?php
                            $sqlTp = "SELECT tipo_atendimento, descricao
                            		   FROM tbl_tipo_atendimento
                            		   WHERE ativo IS TRUE
                            		   AND fabrica = {$login_fabrica}";
                            $resTp = pg_query($con, $sqlTp);

                            while ($dadosTp = pg_fetch_assoc($resTp)) {

                            	$selected = (in_array($dadosTp['tipo_atendimento'], $_POST['tipo_atendimento'])) ? "selected" : "";

                            ?>
                            	<option value="<?= $dadosTp['tipo_atendimento'] ?>" <?= $selected ?>>
                            		<?= $dadosTp['descricao'] ?>
                            	</option>
                            <?php
                            }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class='control-group <?=(in_array("unidade_negocio", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='Estado'>Linha</label>
                <div class='controls controls-row'>
                    <select multiple name='linha[]' id='linha'>
                        <?php
                            $sqlLinha = "SELECT linha, nome
                            		  FROM tbl_linha
                            		  WHERE ativo IS TRUE
                            		  AND fabrica = {$login_fabrica}
                            		  ORDER BY nome";
                            $resLinha = pg_query($con, $sqlLinha);

                            while ($dadosLinha = pg_fetch_assoc($resLinha)) {
                            
                            	$selected = (in_array($dadosLinha['linha'], $_POST['linha'])) ? "selected" : "";

                            ?>
                            	<option value="<?= $dadosLinha['linha'] ?>" <?= $selected ?>>
                            		<?= $dadosLinha['nome'] ?>
                            	</option>
                            <?php
                            }
                        ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <br />
    <div class="row-fluid tac">
    	<input type="submit" class="btn" name="pesquisar" value="pesquisar" />
    </div>
</form>
</div>
<br />
<?php
	$jsonPOST = excelPostToJson($_POST);
?>

<div id='gerar_excel' class="btn_excel">
	<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
	<span><img style="width:40px ; height:40px;" src='imagens/icon_csv.png' /></span>
	<span class="txt">Gerar Arquivo CSV</span>
</div>
<br />
<table class="table table-bordered table-large">
	<thead>
		<tr class="titulo_tabela">
			<th colspan="7">Relatório de Valores</th>
		</tr>
		<tr class="titulo_coluna">
			<th>Código Posto</th>
			<th>Razão Social</th>
			<th>Unidade Negócio</th>
			<th>Valor por OS</th>
			<th>Valor do KM</th>
			<th>Modal</th>
		</tr>
	</thead>
	<tbody>
	<?php
	while ($dadosMo = pg_fetch_assoc($resPesquisa)) {


		$valor_mo         = empty($dadosMo['valor_mo']) ? "100.00" : $dadosMo['valor_mo'];
?>
		<tr>
			<td class="tac"><?= $dadosMo['codigo_posto'] ?></td>
			<td><?= $dadosMo['razao_social'] ?></td>
			<td><?= $dadosMo['unidade_negocio'] ?></td>
			<td class="tac"><?= number_format($valor_mo, 2, ",", ".") ?></td>
			<td class="tac"><?= number_format($dadosMo['valor_km'], 2, ",", ".") ?></td>
			<td class="tac"><?= empty($dadosMo['valor_mo']) ? "OS" : "Parque" ?></td>
		</tr>
	<?php
	} ?>
	</tbody>
</table>
<script>
	$.dataTableLoad({ table: ".table" });
</script>
<?php
include("rodape.php");
?>
