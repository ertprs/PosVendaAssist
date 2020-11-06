<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$produto_referencia = $_POST['produto_referencia'];
	$produto_descricao  = $_POST['produto_descricao'];
	$peca_referencia    = $_POST['peca_referencia'];
	$peca_descricao     = $_POST['peca_descricao'];
	$serie 				= $_POST['serie'];

	if (!empty($produto_referencia) or !empty($produto_descricao)){
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

	if (!empty($serie)){
		$sql = "SELECT numero_serie FROM tbl_numero_serie WHERE fabrica = {$login_fabrica} AND serie = '{$serie}'";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0){
			$numero_serie = pg_fetch_result($res, 0, 'numero_serie');

			$cond_serie = " AND tbl_numero_serie.numero_serie = {$numero_serie} ";
		}
	}

	if (!empty($peca_referencia) or !empty($peca_descricao)){
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
	}

	if (!empty($data_inicial) AND !empty($data_final)){
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

	if (empty($produto) AND empty($peca) AND empty($numero_serie) AND (empty($data_inicial) OR empty($data_final))){
		$msg_erro["msg"][] = "É necessário informar um parâmetro para a pesquisa";
		$msg_erro["campos"][] = "data";
		$msg_erro["campos"][] = "peca";
		$msg_erro["campos"][] = "produto";
		$msg_erro["campos"][] = "serie";
	}

	if (!count($msg_erro["msg"])) {
		if (!empty($produto)){
			$cond_produto = " AND tbl_produto.produto = '{$produto}' ";
		}

		if (!empty($peca)){
			$cond_peca = " AND tbl_peca.peca = $peca ";
		}

		if (!empty($aux_data_inicial) AND !empty($aux_data_final)){
			$cond_data = "AND tbl_os.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}' ";
		}

		$sql ="
			SELECT 
				tbl_produto.produto AS produto_id,
				tbl_produto.referencia AS produto_referencia,
				tbl_produto.descricao AS produto_descricao,
				tbl_numero_serie.serie AS serie_produto,
				tbl_peca.peca AS peca_id,
				tbl_peca.descricao AS peca_descricao,
				tbl_peca.referencia AS peca_referencia,
				tbl_numero_serie_peca.serie_peca AS serie_peca,
				tbl_os.os AS os_id
			FROM tbl_os
			JOIN tbl_produto on tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
			JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os AND tbl_os_produto.produto = tbl_os.produto AND tbl_os_produto.serie = tbl_os.serie
			JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
			JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica}
			JOIN tbl_numero_serie_peca ON tbl_numero_serie_peca.peca = tbl_os_item.peca AND tbl_numero_serie_peca.fabrica = {$login_fabrica}
				AND tbl_numero_serie_peca.serie_peca = tbl_os_item.peca_serie
			JOIN tbl_numero_serie ON tbl_numero_serie.produto = tbl_os.produto AND tbl_numero_serie.serie = tbl_os.serie AND tbl_numero_serie.fabrica = {$login_fabrica}
			JOIN tbl_lista_basica ON tbl_lista_basica.produto = tbl_os.produto AND tbl_lista_basica.peca = tbl_os_item.peca AND tbl_lista_basica.fabrica = {$login_fabrica}
			WHERE tbl_os.fabrica = {$login_fabrica}
			{$cond_produto}
			{$cond_peca}
			{$cond_data}
			{$cond_serie}
			GROUP BY produto_id, serie_produto, produto_referencia, peca_descricao, peca_referencia, serie_peca,peca_id, produto_descricao, os_id
			ORDER BY tbl_numero_serie_peca.serie_peca DESC ";
		$resSubmit = pg_query($con, $sql);
	}
	if ($_POST["gerar_excel"]) {
		if (pg_num_rows($resSubmit) > 0) {
			$data = date("d-m-Y-H:i");

			$fileName = "relatorio_rastreabilidade_peca-{$data}.csv";

			$file = fopen("/tmp/{$fileName}", "w");
			$thead = "Produto; Série Produto; Peça; Série Peça; OS\n";
			
			fwrite($file, utf8_encode($thead));

			for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
				$produto_referencia = pg_fetch_result($resSubmit, $i, 'produto_referencia');
				$produto_id 		= pg_fetch_result($resSubmit, $i, 'produto_id');
				$produto_descricao 	= pg_fetch_result($resSubmit, $i, 'produto_descricao');
				$serie_produto 		= pg_fetch_result($resSubmit, $i, 'serie_produto');
				$peca_id 			= pg_fetch_result($resSubmit, $i, 'peca_id');
				$peca_descricao 	= pg_fetch_result($resSubmit, $i, 'peca_descricao');
				$peca_referencia 	= pg_fetch_result($resSubmit, $i, 'peca_referencia');
				$serie_peca 		= pg_fetch_result($resSubmit, $i, 'serie_peca');
				$os_id    			= pg_fetch_result($resSubmit, $i, 'os_id');
				
				$body .= "$produto_referencia - $produto_descricao;$serie_produto;$peca_referencia-$peca_descricao;$serie_peca;$os_id\n";
			}

			fwrite($file, utf8_encode($body));
			fclose($file);
			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");
				echo "xls/{$fileName}";
			}
		}
		exit;
	}
}

$layout_menu = "gerencia";
$title = "Relatório de Rastreabilidade de Peças";
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
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});
	});

	function retorna_produto (retorno) {
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
	}

	function retorna_peca (retorno) {
        $("#peca_referencia").val(retorno.referencia);
		$("#peca_descricao").val(retorno.descricao);
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
			<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='peca_referencia'>Ref. Peças</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
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
			<div class='control-group <?=(in_array("serie", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='serie'>Número Série Produto</label>
				<div class='controls controls-row'>
					<div class='span8'>
						<input type="text" id="serie" name="serie" class='span12' maxlength="100" value="<? echo $serie ?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span6'></div>
	</div>

	<p><br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>
<?php
if (isset($resSubmit)) {
	if (pg_num_rows($resSubmit) > 0){
		$dados = pg_fetch_all($resSubmit);

		foreach ($dados as $key => $value) {
			
			$pecas["dados_produto"] [ $value['serie_produto'] ] ["produto_referencia"] = $value["produto_referencia"];
			$pecas["dados_produto"] [ $value['serie_produto'] ] ["produto_descricao"] = $value["produto_descricao"];
			$pecas["dados_produto"] [ $value['serie_produto'] ] ["serie_produto"] = $value["serie_produto"];
			
			$pecas["dados_produto"] [ $value['serie_produto'] ] ["dados_pecas"] [ $value["peca_id"] ] ["peca_referencia"] = $value["peca_referencia"];
			$pecas["dados_produto"] [ $value['serie_produto'] ] ["dados_pecas"] [ $value["peca_id"] ] ["peca_descricao"] = $value["peca_descricao"];
			$pecas["dados_produto"] [ $value['serie_produto'] ] ["dados_pecas"] [ $value["peca_id"] ] ["peca_id"] = $value["peca_id"];
			

			$pecas["dados_produto"] [ $value['serie_produto'] ] ["dados_serie"] [] = [
				"serie_peca" => $value["serie_peca"], 
				"os_id" => $value["os_id"],
				"peca_id"=> $value["peca_id"]
			];	

		}
		$result = array_values($pecas['dados_produto']);
		foreach ($result as $key => $value) {
		?>
			<table id="result_<?=$value['serie_produto']?>" class='table table-bordered table-fixed' >
				<thead>
					<tr>
						<th class="titulo_tabela tal" colspan="2">
							Produto: <?=$value["produto_referencia"]?> - <?=$value["produto_descricao"]?>&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;Série: <?=$value["serie_produto"]?>
					</tr>
				</thead>
				<tbody>
					<?php 
						foreach ($value['dados_pecas'] as $key_p => $value_p) {
								
							echo "<tr>";
								echo "<td class='tac' colspan='2' style='background: #c8d7f7;'>";
									echo "PEÇA:&nbsp;&nbsp;".$value_p["peca_referencia"].' - '.$value_p["peca_descricao"];
								echo "</td>";
							echo "<tr>";
							echo "<tr>";
								echo "<td style='background: #eeeeee;'>Número de série antigo</td>";
								echo "<td style='background: #eeeeee;'>Número de série novo</td>";
							echo "</tr>";
							foreach ($value['dados_serie'] as $key_s => $value_s) {				
								if ($value_s["peca_id"] == $value_p["peca_id"]){
									$serie_peca = $value_s["serie_peca"];
									$os_id = $value_s["os_id"];
									$peca_id = $value_s["peca_id"];
									
									$os_id_anterior = "";
									$serie_peca_anterior = "";
									$peca_id_anterior = "";

									if(array_key_exists("serie_peca", $value['dados_serie'][$key_s+1])){
										$os_id_anterior = $value['dados_serie'][$key_s+1]['os_id'];
										$serie_peca_anterior = $value['dados_serie'][$key_s+1]['serie_peca'];
										$peca_id_anterior = $value['dados_serie'][$key_s+1]['peca_id'];
									}

									echo "<tr>";
									if($os_id_anterior !="" AND $peca_id_anterior == $key_p){
										echo "<td style='width:400px;'>OS: $os_id_anterior - Série: $serie_peca_anterior</td>";	
									}else{
										echo "<td style='width:400px;' ></td>";	
									}
									echo "<td>OS: $os_id - Série: $serie_peca</td>";
									echo "</tr>";
								}
							}
						}
					?>
				</tbody>
			</table>
			<br/><br/>
		<?php	
		}
			$jsonPOST = excelPostToJson($_POST);
		?>
			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span class="txt" style="background: #5e9c76;">Gerar Arquivo CSV</span>
			    <span><img style="width:40px ; height:40px;" src='imagens/icon_csv.png' /></span>
			</div>
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



include 'rodape.php';?>
