<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if (isset($_POST["pesquisar"])) {

	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$produto_referencia = $_POST['produto_referencia'];
	$produto_descricao  = $_POST['produto_descricao'];
	$codigo_posto       = $_POST['codigo_posto'];
	$descricao_posto    = $_POST['descricao_posto'];

	if (strlen($produto_referencia) > 0 or strlen($produto_descricao) > 0){
		$sql = "SELECT produto
				FROM tbl_produto
				WHERE fabrica_i = {$login_fabrica}
				AND (
                  	(UPPER(referencia) = UPPER('{$produto_referencia}'))
                    OR
                    (UPPER(descricao) = UPPER('{$produto_descricao}'))
                )
				";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Produto não encontrado";
			$msg_erro["campos"][] = "produto";
		} else {
			$produto = pg_fetch_result($res, 0, "produto");
		}
	}

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
		}
	}

	if (!strlen($data_inicial) or !strlen($data_final)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data";
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
	}

	if (!count($msg_erro["msg"])) {

		if (!empty($produto)){
			$cond_produto = " AND tbl_produto.produto = '{$produto}' ";
		}

		if (!empty($posto)) {
			$cond_posto = " AND tbl_os.posto = {$posto} ";
		}

		$cond_periodo = "AND tbl_os.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'";

		$sqlConsulta = "SELECT  tbl_os.os,
								tbl_os.sua_os,
								tbl_os.garantia_produto       as garantia_cadastrada,
								TO_CHAR(tbl_os.data_abertura, 'mm/dd/yyyy') as data_abertura,
								tbl_os.consumidor_nome 		  as nome_consumidor,
								tbl_os.consumidor_cpf  		  as cpf_consumidor,
								tbl_os.consumidor_endereco    as endereco_consumidor,
								tbl_os.consumidor_numero      as numero_consumidor,
								tbl_os.consumidor_complemento as complemento_consumidor,
								tbl_produto.referencia || ' - ' || tbl_produto.descricao  as descricao_produto,
								tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome as descricao_posto,
								tbl_posto.cnpj        as cnpj_posto,
								tbl_posto.endereco 	  as endereco_posto,
								tbl_posto.numero   	  as numero_posto,
								tbl_posto.complemento as complemento_posto,
								tbl_posto.cep   	  as cep_posto,
								tbl_posto.cidade      as cidade_posto,
								tbl_posto.estado      as estado_posto,
								COALESCE(tbl_posto_fabrica.contato_fone_comercial, tbl_posto_fabrica.contato_cel) as contato_posto
						FROM tbl_os
						JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
						JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto 
						AND tbl_posto_fabrica.fabrica = {$login_fabrica}
						JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
						AND tbl_produto.fabrica_i = {$login_fabrica}
						AND CAST(tbl_produto.parametros_adicionais::jsonb->>'garantia2' AS int) IS NOT NULL
						WHERE tbl_os.fabrica = {$login_fabrica}
						AND   tbl_os.tipo_atendimento = 339
						{$cond_periodo}
						{$cond_produto}
						{$cond_posto}
						";
		$resConsulta = pg_query($con, $sqlConsulta);

		if ($_POST["gerar_excel"]) {
			if (pg_num_rows($resConsulta) > 0) {

				$data = date("d-m-Y-H:i");

				$fileName = "relatorio_garantias_adicionais-{$data}.xls";

				$file = fopen("/tmp/{$fileName}", "w");

				$thead = "<table id='resultado_os'>
							<thead>
								<tr class='titulo_coluna' >
									<th>OS</th>
									<th>Abertura</th>
									<th>Produto</th>
					                <th>Garantia Cadastrada</th>
									<th>Nome Consumidor</th>
									<th>CPF</th>
									<th>Endereço Consumidor</th>
									<th>Número Consumidor</th>
									<th>Complemento Consumidor</th>
									<th>Posto Autorizado</th>
									<th>CNPJ</th>
									<th>Endereco Posto</th>
									<th>Número Posto</th>
									<th>Complemento Posto</th>
									<th>CEP posto</th>
									<th>Cidade Posto</th>
									<th>Estado Posto</th>
									<th>Contato Posto</th>
								</tr>
							</thead>
							<tbody>";
				fwrite($file, $thead);

				while ($dadosOs = pg_fetch_assoc($resConsulta)) {

					$tbody .= "<tr>
								<td>{$dadosOs['sua_os']}</td>
								<td>{$dadosOs['data_abertura']}</td>
								<td>{$dadosOs['descricao_produto']} </td>
								<td>{$dadosOs['garantia_cadastrada']}</td>
								<td>{$dadosOs['nome_consumidor']}</td>
								<td>{$dadosOs['cpf_consumidor']}</td>
								<td>{$dadosOs['endereco_consumidor']}</td>
								<td>{$dadosOs['numero_consumidor']}</td>
								<td>{$dadosOs['complemento_consumidor']}</td>
								<td>{$dadosOs['descricao_posto']}</td>
								<td>{$dadosOs['cnpj_posto']}</td>
								<td>{$dadosOs['endereco_posto']}</td>
								<td>{$dadosOs['numero_posto']}</td>
								<td>{$dadosOs['complemento_posto']}</td>
								<td>{$dadosOs['cep_posto']}</td>
								<td>{$dadosOs['cidade_posto']}</td>
								<td>{$dadosOs['estado_posto']}</td>
								<td>{$dadosOs['contato_posto']}</td>
							</tr>";
				
				}

				$tbody .= "</tbody>
						</table>";

				fwrite ($file, $tbody);

				fclose($file);

				if (file_exists("/tmp/{$fileName}")) {
					system("mv /tmp/{$fileName} xls/{$fileName}");

					echo "xls/{$fileName}";
				}
			}

			exit;
		}

	}

}

$layout_menu = "gerencia";
$title = "Relatório de garantias adicionais";
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
<script>
	$(function(){

		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto","posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

	});


	function retorna_produto (retorno) {
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
	}

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

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
						</div>
					</div>
				</div>
			</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_final'>Data Final</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<h5 class='asteristico'>*</h5>
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='produto_referencia'>Ref. Produto</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='produto_descricao'>Descrição Produto</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
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
	<br />
	<p>
		<input type="submit" class="btn btn-default" name="pesquisar" value="Pesquisar" />
	</p>
	<br />
</form>
<?php

if (pg_num_rows($resConsulta) > 0) { ?>
</div>
	<table id="resultado_os" class='table table-striped table-bordered table-hover table-large' >
		<thead>
			<tr class='titulo_coluna' >
				<th>OS</th>
				<th>Abertura</th>
				<th>Produto</th>
                <th>Garantia Cadastrada</th>
				<th>Nome Consumidor</th>
				<th>CPF</th>
				<th>Endereço Consumidor</th>
				<th>Número Consumidor</th>
				<th>Complemento Consumidor</th>
				<th>Posto Autorizado</th>
				<th>CNPJ</th>
				<th>Endereco Posto</th>
				<th>Número Posto</th>
				<th>Complemento Posto</th>
				<th>CEP posto</th>
				<th>Cidade Posto</th>
				<th>Estado Posto</th>
				<th>Contato Posto</th>
			</tr>
		</thead>
		<tbody>
			<?php
			while ($dadosOs = pg_fetch_assoc($resConsulta)) { ?>
				<tr>
					<td>
						<a href="os_press.php?os=<?= $dadosOs['os'] ?>" target="_blank">
							<?= $dadosOs['sua_os'] ?>
						</a>
					</td>
					<td><?= $dadosOs['data_abertura'] ?></td>
					<td><?= $dadosOs['descricao_produto'] ?></td>
					<td><?= $dadosOs['garantia_cadastrada'] ?></td>
					<td><?= $dadosOs['nome_consumidor'] ?></td>
					<td><?= $dadosOs['cpf_consumidor'] ?></td>
					<td><?= $dadosOs['endereco_consumidor'] ?></td>
					<td><?= $dadosOs['numero_consumidor'] ?></td>
					<td><?= $dadosOs['complemento_consumidor'] ?></td>
					<td><?= $dadosOs['descricao_posto'] ?></td>
					<td><?= $dadosOs['cnpj_posto'] ?></td>
					<td><?= $dadosOs['endereco_posto'] ?></td>
					<td><?= $dadosOs['numero_posto'] ?></td>
					<td><?= $dadosOs['complemento_posto'] ?></td>
					<td><?= $dadosOs['cep_posto'] ?></td>
					<td><?= $dadosOs['cidade_posto'] ?></td>
					<td><?= $dadosOs['estado_posto'] ?></td>
					<td><?= $dadosOs['contato_posto'] ?></td>
				</tr>
			<?php
			} ?>
		</tbody>
	</table>

	<?php
		$jsonPOST = excelPostToJson($_POST);
	?>

	<div id='gerar_excel' class="btn_excel">
		<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
		<span><img src='imagens/excel.png' /></span>
		<span class="txt">Gerar Arquivo Excel</span>
	</div>
	<br />
<?php
} else if (isset($_POST['pesquisar'])) { ?>
	<div class="alert alert-warning">
		<h4>Nenhum resultado encontrado</h4>
	</div>
<?php
} ?>

<script>
	$.dataTableLoad({ table: "#resultado_os" });
</script>

<?php
include 'rodape.php';
?>