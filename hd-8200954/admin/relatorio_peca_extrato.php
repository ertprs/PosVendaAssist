<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$peca_referencia    = $_POST['peca_referencia'];
	$peca_descricao     = $_POST['peca_descricao'];
	$codigo_posto       = $_POST['codigo_posto'];
	$descricao_posto    = $_POST['descricao_posto'];
	
	// PESQUISA REF.PRODUTO E DESC.PRODUTO
	if (strlen($produto_referencia) > 0 or strlen($produto_descricao) > 0){
		$sql = "SELECT produto
				FROM tbl_produto
				WHERE fabrica_i = {$login_fabrica}
				AND (
                  	(UPPER(referencia) = UPPER('{$produto_referencia}'))
                    OR
                    (UPPER(descricao) = UPPER('{$produto_descricao}'))
                )";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Produto não encontrado";
			$msg_erro["campos"][] = "produto";
		} else {
			$produto = pg_fetch_result($res, 0, "produto");
		}
	}

	// PESQUISA REF.PEÇA E DESC.PEÇA
	if (strlen($peca_referencia) > 0 or strlen($peca_descricao) > 0){
		$sql = "SELECT peca
				FROM tbl_peca
				WHERE fabrica = {$login_fabrica}
				AND (
                    (UPPER(referencia) = UPPER('{$peca_referencia}'))
                    OR
                    (UPPER(descricao) = UPPER('{$peca_descricao}'))
                )";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Peça não encontrada";
			$msg_erro["campos"][] = "peca";
		} else {
			$peca = pg_fetch_result($res, 0, "peca");
		}
	}else{
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "peca";
	}

	// PESQUISA REF.POSTO E DESC.Posto
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

	// VALIDAÇÃO DATA
	if (!strlen($data_inicial) or !strlen($data_final)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data";

	}else{
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
			if (strtotime($aux_data_inicial) < strtotime($aux_data_final . ' -6 month')){
				$msg_erro["msg"][]    = "O intervalo entre as datas não pode ser maior que 6 meses.";
				$msg_erro["campos"][] = "data";
			}
		}
	}
	
	if(!count($msg_erro["msg"])) {
		$campo_data = "tbl_extrato.data_geracao";
	
		if (!empty($posto)) {
			$cond_posto = " AND tbl_extrato.posto = {$posto} ";
		}else{
			$cond_posto = "";
		}
	

	  $sql = "SELECT 
						TO_CHAR(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_extrato,
						tbl_extrato.extrato AS numero_extrato,
						tbl_peca.referencia AS peca_referencia,
						tbl_peca.descricao AS peca_descricao,
						tbl_posto.nome AS posto_nome,
						tbl_posto_fabrica.codigo_posto AS codigo_posto
					FROM tbl_extrato
						JOIN tbl_os_extra ON tbl_extrato.extrato = tbl_os_extra.extrato
						JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os_extra.os
						JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
						JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
						JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_extrato.fabrica = {$login_fabrica}
					AND tbl_peca.peca = {$peca}
					AND {$campo_data} BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
					$cond_posto
					GROUP BY tbl_extrato.extrato, tbl_extrato.data_geracao, tbl_peca.referencia, tbl_peca.descricao, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
					ORDER BY tbl_extrato.extrato
					";
		$resSubmit = pg_query($con, $sql);
		//echo $sql;exit;
 	}
}

$layout_menu = "auditoria";
$title = "RELATÓRIO DE PEÇAS x Extratos";
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
<script type="text/javascript">
	var hora = new Date();
	var engana = hora.getTime();
	
	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("peca", "posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});
	});

	$(function() {
    var table = new Object();
    table['table'] = '#resultado_os_atendimento';
    table['type'] = 'full';
    $.dataTableLoad(table);
  });
                    

	function retorna_peca(retorno){
    $("#peca_referencia").val(retorno.referencia);
		$("#peca_descricao").val(retorno.descricao);
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

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
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
			<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='peca_referencia'>Ref. Peças</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="text" id="peca_referencia" name="peca_referencia" class='span12' maxlength="20" value="<? echo $peca_referencia ?>" >
						<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='peca_descricao'>Descrição Peça</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="text" id="peca_descricao" name="peca_descricao" class='span12' value="<? echo $peca_descricao ?>" >
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
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

	<p><br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>
</div>

<?php
if(isset($resSubmit)) {
	if(pg_num_rows($resSubmit) > 0) {
		echo "<br />";

		$count = pg_num_rows($resSubmit);
?>
	<table id="resultado_peca_extrato" class='table table-striped table-bordered table-hover table-large'>
		<thead>
			<tr class='titulo_coluna' >
				<th>Numero Extrato</th>
				<th>Data Extrato</th>
				<th>Ref. Peça</th>
				<th>Desc. Peça</th>
				<th>Cod. Posto</th>
				<th>Nome Posto</th>
			</tr>
		</thead>
		<tbody>
			<?php
				for ($i = 0; $i < $count; $i++) {
					$numero_extrato   = pg_fetch_result($resSubmit, $i, 'numero_extrato');
					$data_extrato     = pg_fetch_result($resSubmit, $i, 'data_extrato');
					$peca_referencia	= pg_fetch_result($resSubmit, $i, 'peca_referencia');
					$peca_descricao	  = pg_fetch_result($resSubmit, $i, 'peca_descricao');
					$nome_posto 			= pg_fetch_result($resSubmit, $i, 'posto_nome');
					$codigo_posto 		= pg_fetch_result($resSubmit, $i, 'codigo_posto');
					
					$body = "<tr>
									<td class='tac'><a href='extrato_consulta_os.php?extrato={$numero_extrato}' target='_blank' >{$numero_extrato}</a></td>
									<td class='tac'>{$data_extrato}</td>
									<td class='tac'>{$peca_referencia}</td>
									<td class='tal'>{$peca_descricao}</td>
									<td class='tac'>{$codigo_posto}</td>
									<td class='tal'>{$nome_posto}</td>
								</tr>";
						echo $body;
				}
			?>
		</tbody>
	</table>

<?php

	}else{
		echo '
			<div class="container">
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
			</div>';
	}
}
include 'rodape.php';
?>