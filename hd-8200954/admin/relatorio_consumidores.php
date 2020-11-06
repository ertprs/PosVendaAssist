<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

$array_estados = $array_estados();

if (isset($_POST['btn_pesquisa'])) {

	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$produto_referencia = $_POST['produto_referencia'];
	$produto_descricao  = $_POST['produto_descricao'];
	$estados            = $_POST['estados'];
	$cond               = [];

	if (count($estados) > 0) {

		$cond[] = "AND dados.estado_consumidor IN ('".implode("','", $estados)."')";

	}

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
			$cond[] = "AND dados.produto = {$produto}";
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

	if (count($msg_erro) == 0) {

		$sqlPesquisa = "SELECT DISTINCT ON (dados.cpf_consumidor) 
							dados.* FROM (

								SELECT consumidor_nome  			  				 as nome_consumidor,
									   consumidor_cpf   			  				 as cpf_consumidor,
									   consumidor_email 			  				 as email_consumidor,
									   COALESCE(fn_retira_especiais(consumidor_fone)) AS fone_consumidor, 
									   			fn_retira_especiais(consumidor_celular) as celular_consumidor,
									   TO_CHAR(data_nf, 'dd/mm/yyyy') 				 as data_nf,
									   consumidor_estado                             as estado_consumidor,
									   tbl_produto.produto,
									   tbl_produto.referencia || ' - ' || tbl_produto.descricao as descricao_produto,
									   tbl_os.fabrica
								FROM tbl_os
								JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
								AND tbl_produto.fabrica_i = {$login_fabrica}
								WHERE data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
								AND tbl_os.fabrica = {$login_fabrica}

							UNION

								SELECT tbl_hd_chamado_extra.nome  		as nome_consumidor,
									   cpf   							as cpf_consumidor,
									   email 							as email_consumidor,
									   COALESCE(fone, fone2)			as fone_consumidor,
									   fn_retira_especiais(tbl_hd_chamado_extra.celular) as celular_consumidor,
									   TO_CHAR(data_nf, 'dd/mm/yyyy')   as data_nf,
									   tbl_cidade.estado                as estado_consumidor,
									   tbl_produto.produto,
									   tbl_produto.referencia || ' - ' || tbl_produto.descricao as descricao_produto,
									   tbl_hd_chamado.fabrica
								FROM tbl_hd_chamado
								JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
								LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_hd_chamado_extra.produto
								AND tbl_produto.fabrica_i = {$login_fabrica}
								LEFT JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
								WHERE tbl_hd_chamado.data BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
								AND tbl_hd_chamado.fabrica = {$login_fabrica}

						) as dados
						WHERE dados.fabrica = {$login_fabrica}
						".implode(" ", $cond);
		$resPesquisa = pg_query($con, $sqlPesquisa);

		if ($_POST["gerar_excel"]) {
			if (pg_num_rows($resPesquisa) > 0) {
				$data = date("d-m-Y-H:i");

				$fileName = "relatorio_dados_consumidor-{$data}.csv";

				$file = fopen("/tmp/{$fileName}", "w");
				$thead = "Nome;CPF;E-mail;Telefone;Celular;Data da Compra;Estado;Produto \n";
				fwrite($file, $thead);

				$tbody = "";

				while ($dadosConsumidor = pg_fetch_assoc($resPesquisa)) { 

					$tbody .=    str_replace(";", "", $dadosConsumidor['nome_consumidor']).";"
								.$dadosConsumidor['cpf_consumidor'].";"
								.$dadosConsumidor['email_consumidor'].";"
								.$dadosConsumidor['fone_consumidor'].";"
								.$dadosConsumidor['celular_consumidor'].";"
								.$dadosConsumidor['data_nf'].";"
								.$dadosConsumidor['estado_consumidor'].";"
								.str_replace(";", "", $dadosConsumidor['descricao_produto'])." \n";

				}

				fwrite($file, $tbody);

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
$title = "RELATÓRIO DE OS x Atendimentos";
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

	$.datepickerLoad(Array("data_final", "data_inicial"));
	$.autocompleteLoad(Array("produto"));
	Shadowbox.init();

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});

	$("#estados").multiselect({
		selectedText: "selecionados # de #"
	});

});

function retorna_produto (retorno) {
	$("#produto_referencia").val(retorno.referencia);
	$("#produto_descricao").val(retorno.descricao);
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
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<label class='control-label' for='data_final'>Estados</label>
			<div class='controls controls-row'>
				<select id="estados" name="estados[]" class="span12" multiple="multiple">
                    <?php
                    #O $array_estados está no arquivo funcoes.php
                    foreach ($array_estados as $sigla => $nome_estado) {
                        $selected = (in_array($sigla, $estados)) ? "selected" : "";

                        echo "<option value='{$sigla}' {$selected} >" . $nome_estado . "</option>";
                    }
                    ?>
                </select>
			</div>
		</div>
	</div>
	<br />
	<input type="submit" name="btn_pesquisa" value="Pesquisar" class="btn btn-default" />
	<br /><br />
</form>
</div>
<?php
if (isset($resPesquisa)) {
	if (pg_num_rows($resPesquisa) > 0) {

		$jsonPOST = excelPostToJson($_POST);
	?>
	<br />
	<div id='gerar_excel' class="btn_excel">
		<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
		<span><img style="width:40px ; height:40px;" src='imagens/icon_csv.png' /></span>
		<span class="txt">Gerar Arquivo CSV</span>
	</div>
	<br />
	<table class="table table-bordered table-hover table-striped">
		<thead>
			<tr class="titulo_coluna">
				<th>Nome</th>
				<th>CPF</th>
				<th>E-mail</th>
				<th>Telefone</th>
				<th>Celular</th>
				<th>Data de Compra</th>
				<th>Estado</th>
				<th>Produto</th>
			</tr>
		</thead>
		<tbody>
			<?php
			while ($dadosConsumidor = pg_fetch_assoc($resPesquisa)) { ?>
				<tr>
					<td><?= $dadosConsumidor['nome_consumidor'] ?></td>
					<td class="tac"><?= $dadosConsumidor['cpf_consumidor'] ?></td>
					<td><?= $dadosConsumidor['email_consumidor'] ?></td>
					<td class="tac"><?= str_replace(" ", "", $dadosConsumidor['fone_consumidor']) ?></td>
					 <td class="tac"><?= str_replace(" ", "", $dadosConsumidor['celular_consumidor']) ?></td>
					<td class="tac"><?= $dadosConsumidor['data_nf'] ?></td>
					<td class="tac"><?= $dadosConsumidor['estado_consumidor'] ?></td>
					<td><?= $dadosConsumidor['descricao_produto'] ?></td>
				</tr>
			<?php
			}
			?>
		</tbody>
	</table>
	<script>
		$.dataTableLoad({ table: ".table" });
	</script>
	<?php
	} else { ?>
		<div class="container">
			<div class="alert alert-warning">
				<h4>Nenhum resultado encontrado</h4>
			</div>
		</div>
	<?php
	}
} 
?>

<?php
include 'rodape.php';
?>
