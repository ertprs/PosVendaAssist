<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$produto_referencia = $_POST['produto_referencia'];
	$produto_descricao  = $_POST['produto_descricao'];
	$codigo_posto       = $_POST['codigo_posto'];
	$descricao_posto    = $_POST['descricao_posto'];
	$estado             = $_POST['estado'];	

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
			$join_produto = "JOIN tbl_produto on tbl_produto.produto = tbl_os.produto AND
												 tbl_produto.fabrica_i = {$login_fabrica}";
			$cond_produto = "AND tbl_produto.produto = {$produto}";
		}
	}else{
		$cond_produto = "";
		$join_produto = "";
	}

	if (strlen($estado) > 0 ){
		
		$cond_estado = "AND tbl_posto_fabrica.contato_estado = '{$estado}'";	
	}else{
		$cond_estado = "";
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
			$cond_posto = "AND tbl_posto.posto = {$posto}";
		}
	}else{
		$cond_posto = "";
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

			$inicio = new DateTime($aux_data_inicial);
			//adiciona 3 meses na data inicial
			$intervalo = $inicio->add(new DateInterval("P03M"));
			$fim = new DateTime($aux_data_final);

			if($intervalo < $fim){
				$msg_erro["msg"][]    = "Período entre datas deve ser de no máximo 3 meses";
				$msg_erro["campos"][] = "data";	
			}
		}
	}

	if(count($msg_erro) == 0){
		$sql = "SELECT  tbl_posto.posto,
						tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome,
						count(tbl_os.os) AS qtde_os
				FROM tbl_os
				JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
				JOIN tbl_posto_fabrica ON 	tbl_posto.posto = tbl_posto_fabrica.posto AND
											tbl_posto_fabrica.fabrica = {$login_fabrica}
				JOIN tbl_os_status ON tbl_os_status.os = tbl_os.os AND
									  tbl_os_status.status_os = 70
				{$join_produto}
				WHERE tbl_os.fabrica = {$login_fabrica} AND
					  tbl_os.data_digitacao BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59' AND 
					  tbl_os.data_conserto isnull AND
					  tbl_os.data_fechamento isnull AND
					  tbl_os.excluida IS NOT TRUE AND
					  tbl_os.os_reincidente IS TRUE 
					  {$cond_posto}
					  {$cond_estado}
					  {$cond_produto}
				GROUP BY tbl_posto.posto,tbl_posto_fabrica.codigo_posto,tbl_posto.nome
				ORDER BY qtde_os DESC";
				
		$res_consulta = pg_query($con, $sql);
		$numRows = pg_num_rows($res_consulta);
		if($numRows > 0){
			$soma = 0;
			for($i = 0; $i < $numRows; $i++){
				$qtde_os =  pg_fetch_result($res_consulta, $i, "qtde_os");
				
				$soma    += $qtde_os;

			}
			if(isset($_POST["gerar_excel"])){
				$data = date("d-m-Y-H:i");

				$fileName = "pesquisa_posto_os_reincidente-{$data}.xls";

				$file = fopen("/tmp/{$fileName}", "w");

				$head = "Posto;Razão Social;Qtde Os Reincidente;Porcentagem;\n";

				fwrite($file, $head);

				for($i = 0; $i < $numRows; $i++){
					$qtde_os =  pg_fetch_result($res_consulta, $i, "qtde_os");
					$porcentagem = ($qtde_os / $soma) * 100;
					$porcentagem = number_format($porcentagem,2,",",".");

					$posto =  pg_fetch_result($res_consulta, $i, "posto");
					$codigo_posto =  pg_fetch_result($res_consulta, $i, "codigo_posto");
					$nome_posto =  pg_fetch_result($res_consulta, $i, "nome");

					$body = "$codigo_posto;$nome_posto;$qtde_os;$porcentagem %;\n";
					fwrite($file, $body);
				}
				fwrite($file, "TOTAL DE OS;;$soma");
				fclose($file);

				if (file_exists("/tmp/{$fileName}")) {
					system("mv /tmp/{$fileName} xls/{$fileName}");

					echo "xls/{$fileName}";
					exit;
				}else{
					$msg_erro["msg"][] = "Erro ao gerar arquivo";
				}

			}
		}
	}
}


$layout_menu = "gerencia";
$title = "PESQUISA DE OS REINCIDENTE";
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php"); ?>

<script language="javascript">
	var hora = new Date();
	var engana = hora.getTime();

	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		var oTable = $('#resultado_consulta').dataTable();
		oTable.fnSort( [ [1,'desc'] ] );

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
		<div class='row-fluid' >
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group' >
					<label class='control-label' for='descricao_posto'>Estado</label>
					<div class='controls controls-row'>
						<select name="estado" id="estado" >
							<option value="" <?php if (strlen($estado) == 0) echo " selected ";?> >TODOS OS ESTADOS</option>
							<option value="AC" <?php if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
							<option value="AL" <?php if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
							<option value="AM" <?php if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
							<option value="AP" <?php if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
							<option value="BA" <?php if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
							<option value="CE" <?php if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
							<option value="DF" <?php if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
							<option value="ES" <?php if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
							<option value="GO" <?php if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
							<option value="MA" <?php if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
							<option value="MG" <?php if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
							<option value="MS" <?php if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
							<option value="MT" <?php if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
							<option value="PA" <?php if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
							<option value="PB" <?php if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
							<option value="PE" <?php if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
							<option value="PI" <?php if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
							<option value="PR" <?php if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
							<option value="RJ" <?php if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
							<option value="RN" <?php if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
							<option value="RO" <?php if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
							<option value="RR" <?php if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
							<option value="RS" <?php if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
							<option value="SC" <?php if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
							<option value="SE" <?php if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
							<option value="SP" <?php if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
							<option value="TO" <?php if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
						</select>
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

<? if($_POST['btn_acao'] == 'submit' && count($msg_erro) == 0) {
	if($numRows > 0){ ?>
		<table id="resultado_consulta" class='table table-striped table-bordered table-hover table-large' >
			<thead>
				<tr class='titulo_coluna' >
					<th>Posto</th>
					<th>Qtd. OS Reincidente</th>
					<th>%</th>
				</tr>
			</thead>
			<tbody> <?
			for($i = 0; $i < $numRows; $i++){
				$qtde_os =  pg_fetch_result($res_consulta, $i, "qtde_os");
				$porcentagem = ($qtde_os / $soma) * 100;
				$porcentagem = number_format($porcentagem,2, ",", ".");

				$posto =  pg_fetch_result($res_consulta, $i, "posto");
				$codigo_posto =  pg_fetch_result($res_consulta, $i, "codigo_posto");
				$nome_posto =  pg_fetch_result($res_consulta, $i, "nome");?>

				<tr>
					<td nowrap><a href="posto_os_reincidente.php?posto=<?=$posto?>&data_inicial=<?=$data_inicial?>&data_final=<?=$data_final?>" target="_blank"><?=$codigo_posto?> - <?=$nome_posto?></a></td>
					<td class="tac"><?=$qtde_os?></td>
					<td class="tac"><?=$porcentagem?>%</td>
				</tr>
		<?	} ?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="2">TOTAL DE OS</td>
					<td  class="tac"><?=$soma?></td>
					<td  class="tac">&nbsp</td>
				</tr>
			</tfoot>
		</table>

			
				<script>
					$.dataTableLoad({ table: "#resultado_consulta" });
				</script>
		
			<br />

			<?php
				$jsonPOST = excelPostToJson($_POST);
			?>

			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span><img src='imagens/excel.png' /></span>
				<span class="txt">Gerar Arquivo Excel</span>
			</div>
		<?php
		
	}else{ ?>

			<div class="container">
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
			</div> <?
	}
}
include 'rodape.php';?>
