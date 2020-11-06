<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
$admin_privilegios = "info_tecnica";
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($_POST['btn_acao']) > 0) {
	$data_inicial = $_POST['data_inicial'];
	$data_final   = $_POST['data_final'];
	$nome_posto   = $_POST['nome_posto'];
	$codigo_posto = trim($_POST['codigo_posto']);
	$comunicado   = trim($_POST['comunicado']);
	$tipo_comunicado = trim($_POST['tipo_comunicado']);
	$btn_acao = $_POST['btn_acao'];
	if(strlen($btn_acao)>0){

		$cond_1 = " 1 = 1 ";
		$cond_2 = " 1 = 1 ";
		$cond_3 = " 1 = 1 ";
		$cond_4 = " 1 = 1 ";

		if (strlen($comunicado) == 0) {

			if (!strlen($data_inicial) or !strlen($data_final)) {
				$msg_erro["msg"][]    = traduz("Preencha os campos obrigatórios");
				$msg_erro["campos"][] = "data";
			} else {
				list($di, $mi, $yi) = explode("/", $data_inicial);
				list($df, $mf, $yf) = explode("/", $data_final);

				if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
					$msg_erro["msg"][]    = traduz("Data Inválida");
					$msg_erro["campos"][] = "data";
				} else {
					$xdata_inicial = "{$yi}-{$mi}-{$di}";
					$xdata_final   = "{$yf}-{$mf}-{$df}";

					if (strtotime($xdata_final) < strtotime($xdata_inicial)) {
						$msg_erro["msg"][]    = traduz("Data Final não pode ser menor que a Data Inicial");
						$msg_erro["campos"][] = "data";
					}
				}
			}

			if (strlen($data_inicial) > 0 and strlen($data_final) > 0){
				$cond_1 =" tbl_comunicado_posto_blackedecker.data_confirmacao between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'";
			}
		}

		if(strlen($codigo_posto)>0){
			$sql="SELECT posto FROM tbl_posto_fabrica join tbl_posto using(posto) where fabrica=$login_fabrica and codigo_posto='$codigo_posto'";
			$res=pg_query($con,$sql);
			if(pg_numrows($res)>0){
				$posto=pg_fetch_result($res,0,0);
				if(strlen($posto) >0 ){
					$cond_2 = " tbl_comunicado_posto_blackedecker.posto=$posto ";
				}
			}
			else{
				$msg_erro["msg"][] = traduz("Posto Inválido");
			}
		}

		if (strlen($tipo_comunicado) > 0 && $tipo_comunicado != '0') {

			$cond_4 = "tbl_comunicado.tipo = '{$tipo_comunicado}'";
		}

		if(strlen($comunicado)>0){
			$cond_3 = " tbl_comunicado.comunicado=$comunicado";
		}
	}

	$limit = (!isset($_POST["gerar_excel"])) ? "LIMIT 501" : "";
	if ((!count($msg_erro["msg"])) && (strlen($btn_acao)>0)) {
		$sqlComunicado = "SELECT DISTINCT 
									tbl_posto_fabrica.codigo_posto ,
									tbl_posto.nome                 ,
									lower(tbl_posto_fabrica.contato_email) as contato_email,
									tbl_comunicado.comunicado      ,
									tbl_comunicado.tipo            ,
									(SELECT to_char(tbl_comunicado_posto_blackedecker.data_confirmacao,'DD/MM/YYYY HH24:MI')
										FROM tbl_comunicado_posto_blackedecker
										WHERE tbl_comunicado.comunicado = tbl_comunicado_posto_blackedecker.comunicado
										AND tbl_posto.posto = tbl_comunicado_posto_blackedecker.posto
										AND $cond_1
										AND $cond_2
										AND $cond_3
										ORDER BY data_confirmacao ASC LIMIT 1
									) AS data_confirmacao,
									tbl_comunicado_posto_blackedecker.leitor
							FROM tbl_posto
							JOIN tbl_posto_fabrica USING (posto)
							JOIN tbl_comunicado USING (fabrica)
							JOIN tbl_comunicado_posto_blackedecker ON tbl_comunicado.comunicado = tbl_comunicado_posto_blackedecker.comunicado
								AND tbl_posto.posto = tbl_comunicado_posto_blackedecker.posto
							WHERE tbl_posto_fabrica.fabrica = $login_fabrica
							AND   tbl_comunicado.fabrica = $login_fabrica
							AND   tbl_comunicado.obrigatorio_site IS TRUE
							AND $cond_1
							AND $cond_2
							AND $cond_3
							AND $cond_4
							order by tbl_comunicado.comunicado asc	{$limit}";			
							
		$resComunicado = pg_query($con,$sqlComunicado);
	}
	if ($_POST["gerar_excel"]) {
		if (pg_num_rows($resComunicado) > 0) {
			$data = date("d-m-Y-H:i");

			$fileName = "relatorio_comunicado-{$data}.xls";

			$colspan = 5;

			if (in_array($login_fabrica, [85])) {
				$headerCpf = "<th bgcolor='#596D9B'><font color='#ffffff'>Nome</font></th>
							  <th bgcolor='#596D9B'><font color='#ffffff'>CPF</font></th>";
				$colspan = 7;
			}

			if ($login_fabrica == 177) {
				
				$lblComunicado = "<th bgcolor='#596D9B'><font color='#ffffff'>Tipo Comunicado</font></th>";
				$colspan = 6;
			}

			$file = fopen("/tmp/{$fileName}", "w");
			$thead = "
				<table border='1'>
					<thead>
						<tr>
							<th colspan='{$colspan}' bgcolor='#D9E2EF' style='color: #333333 !important;'>
								".traduz('RELATÓRIO DE COMUNICADOS')."
							</th>
						</tr>
						<tr>
							<th bgcolor='#596D9B'><font color='#ffffff'>".traduz('Código')."</font></th>
							<th bgcolor='#596D9B'><font color='#ffffff'>".traduz('Posto')."</font></th>
							<th bgcolor='#596D9B'><font color='#ffffff'>".traduz('Email posto')."</font></th>
							<th bgcolor='#596D9B'><font color='#ffffff'>".traduz('Comunicado')."</font></th>
							<th bgcolor='#596D9B'><font color='#ffffff'>".traduz('Data Confirmação')."</font></th>
							{$lblComunicado}
							{$headerCpf}
						</tr>
					<thead>
					<tbody>";
			fwrite($file, $thead);

			for ($i = 0; $i < pg_num_rows($resComunicado); $i++) {
					$contato_email    = pg_fetch_result($resComunicado,$i,'contato_email');
					$codigo_posto     = pg_fetch_result($resComunicado,$i,'codigo_posto');
					$nome             = pg_fetch_result($resComunicado,$i,'nome');
					$comunicado       = pg_fetch_result($resComunicado,$i,'comunicado');
					$data_confirmacao = pg_fetch_result($resComunicado,$i,'data_confirmacao');
					$leitor           = pg_fetch_result($resComunicado,$i,'leitor');
					$arrLeitor 		  = json_decode($leitor, true);
					$tipoComunicado   = pg_fetch_result($resComunicado,$i,'tipo');

					if ($login_fabrica == 177) {

						$campoComunicado = "<td nowrap align='center' valign='top'>{$tipoComunicado}</td>";
					}

					if (in_array($login_fabrica, [85])) {
						$camposCpf = "<td align='left' class='tac'>".utf8_decode(utf8_decode($arrLeitor["nome_comunicado"]))."</td>
									  <td align='left'>".$arrLeitor["cpf_comunicado"]."</td>";
					}

					$body .="
						<tr>
							<td nowrap align='center' valign='top'>{$codigo_posto}</td>
							<td nowrap align='left' valign='top'>{$nome}</td>
							<td nowrap align='left' valign='top'>{$contato_email}</td>
							<td nowrap align='center' valign='top'>{$comunicado}</td>
							<td nowrap align='center' valign='top'>{$data_confirmacao}</td>
							{$campoComunicado}
							{$camposCpf}
						</tr>";
			}

			fwrite($file, $body);
			fwrite($file, "
						<tr>
							<th colspan='{$colspan}' bgcolor='#596D9B'><font color='#ffffff'>".traduz('Total de % registros', null, null, [pg_num_rows($resComunicado)])."</font></th>
						</tr>
					</tbody>
				</table>
			");

			fclose($file);

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");

				echo "xls/{$fileName}";
			}
		}
		exit;
	}
}
$layout_menu = traduz("gerencia");
$title = traduz("RELATÓRIO DE COMUNICADO LIDO");
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

<script type="text/javascript" charset="utf-8">
	var hora = new Date();
	var engana = hora.getTime();

	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("posto"));
		Shadowbox.init();
		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});
	});

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    }
</script>
<?php if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
		<h4><?php echo implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php }?>
<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
		<div class='titulo_tabela '> <?=traduz('Parâmetros de Pesquisa')?></div>
		<br/>
		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_inicial'><?=traduz("Data Inicial")?></label>
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
					<label class='control-label' for='data_final'><?=traduz('Data Final')?></label>
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
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'><?=traduz('Código Posto')?></label>
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
					<label class='control-label' for='descricao_posto'><?=traduz('Nome Posto')?></label>
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
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='linha'><?=traduz('Comunicado')?></label>
					<div class='controls controls-row'>
						<div class='span12'>
							<input type="text" class='span12' name="comunicado"  id="comunicado" value='<? echo $comunicado;?>' class='frm'>
						</div>
					</div>
				</div>
			</div>
			<?php if ($login_fabrica == 177) { ?>
				<div class='span4'>
					<div class='control-group <?=(in_array("tipo_comunicado", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='linha'>Tipo Comunicado</label>
						<div class='controls controls-row'>
							<div class='span12 input-append'>
								<?php 

									$sel_tipos = include('menus/comunicado_option_array.php');
									$new_tipos[] = '';

									foreach ($sel_tipos as $key => $value) {
										$new_tipos[$key] = $value;
									}

									asort($new_tipos);

									echo array2select('tipo_comunicado', 'tipo_comunicado', $new_tipos, $tipo_comunicado, "class='span11 tipo_comunicado'", "Selecione", True);
								?>
							</div>
						</div>
					</div>
				</div>
			<?php } ?>
		</div>
		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?=traduz('Pesquisar')?></button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/>
</form>
<br />

<?php 
	if (isset($resComunicado)) {
		if (pg_num_rows($resComunicado) > 0) {
			if (pg_num_rows($resComunicado) > 500) {
				$count = 500;
				echo "
					<div id='registro_max'>
						<h6>".traduz('Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.')."</h6>
					</div>";
			} else {
				$count = pg_num_rows($resComunicado);
			}
?>

		<table id='resultado_comunicado' class='table table-striped table-bordered table-hover table-large' style="width:100%">
			<thead>
				<tr class='titulo_coluna'>
					<th><?=traduz('Código')?></th>
					<th><?=traduz('Posto')?></th>
					<th><?=traduz('Email posto')?></th>
					<th><?=traduz('Comunicado')?></th>
					<th><?=traduz('Data Confirmação')?></th>
					<?php if ($login_fabrica == 177) { ?> 
						<th><?=traduz('Tipo Comunicado')?></th> 
					<?php }
					if (in_array($login_fabrica, [85])) { ?>
						<th><?=traduz('Nome')?></th>
						<th>CPF</th>
					<?php } ?>
				</tr>
			</thead>
			<tbody>
			<?php 
				for ($i=0; $count > $i; $i++) {
					$contato_email    = pg_fetch_result($resComunicado,$i,'contato_email');
					$codigo_posto     = pg_fetch_result($resComunicado,$i,'codigo_posto');
					$nome             = pg_fetch_result($resComunicado,$i,'nome');
					$comunicado       = pg_fetch_result($resComunicado,$i,'comunicado');
					$data_confirmacao = pg_fetch_result($resComunicado,$i,'data_confirmacao');
					$leitor           = pg_fetch_result($resComunicado,$i,'leitor');
					$tipoComunicado   = pg_fetch_result($resComunicado,$i,'tipo');
					$arrLeitor 		  = json_decode($leitor, true);

					echo "<tr>";
					echo "<td align='left' class='tac'>$codigo_posto</td>";
					echo "<td align='left'>$nome</td>";
					echo "<td align='left'>$contato_email</td>";
					echo "<td align='center' class='tac'><a href='comunicado_produto.php?comunicado=$comunicado' target='_blank'>$comunicado</a></td>";
					echo "<td align='center' class='tac'>$data_confirmacao</td>";
					
					if ($login_fabrica == 177) {

						echo "<td align='left'>$tipoComunicado</td>";
					}

					if (in_array($login_fabrica, [85])) {

						echo "<td align='left' class='tac'>".utf8_decode(utf8_decode($arrLeitor["nome_comunicado"]))."</td>";
						echo "<td align='left'>".$arrLeitor["cpf_comunicado"]."</td>";

					}

					echo "</tr>";
				}
			?>
			</tbody>
		</table>
		<?php if ($count > 50) { ?>
			<script>
				$.dataTableLoad({ table: "#resultado_comunicado" });
			</script>
		<?php }?>
		<br /> <br />
		<?php $jsonPOST = excelPostToJson($_POST);?>
		<div id='gerar_excel' class="btn_excel">
			<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
			<span><img src='imagens/excel.png' /></span>
			<span class="txt"><?=traduz('Gerar Arquivo Excel')?></span>
		</div>
		<br />
		<?php } else {
			echo '<div class="container">
						<div class="alert">
							<h4>'.traduz('Nenhum resultado encontrado').'</h4>
						</div>
					</div>';
		}
}
?>
<? include "rodape.php" ?>
