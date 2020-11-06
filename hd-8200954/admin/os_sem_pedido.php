<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center,gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

if($_POST["btn_acao"] == "submit") {
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$codigo_posto       = $_POST['codigo_posto'];
	$descricao_posto    = $_POST['descricao_posto'];


	if(strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
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
			$msg_erro["msg"][]    = traduz("Posto não encontrado");
			$msg_erro["campos"][] = "posto";
		} else {
			$posto = pg_fetch_result($res, 0, "posto");
		}
	}

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
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
			$aux_data_final   = "{$yf}-{$mf}-{$df}";

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = traduz("Data Final não pode ser menor que a Data Inicial");
				$msg_erro["campos"][] = "data";
			}

			if($telecontrol_distrib){
				if(strtotime($aux_data_inicial.'+6 months') < strtotime($aux_data_final) ) {
      		$msg_erro["msg"][]    = traduz("O intervalo entre as datas não pode ser maior que 6 meses");
					$msg_erro["campos"][] = "data";	
      	}
			}else{
				if(strtotime($aux_data_inicial.'+1 months') < strtotime($aux_data_final) ) {
      		$msg_erro["msg"][]    = traduz("O intervalo entre as datas não pode ser maior que 1 mes");
					$msg_erro["campos"][] = "data";	
      	}
			}
			
		}
	}

	

	if(!count($msg_erro["msg"])) {

		if($telecontrol_distrib) {
			$cond_excluida 	= "tbl_os.excluida,";
			$cond_fone 			= "tbl_posto.fone,";
			$cond_email 		=	"tbl_posto.email,";
			$cond_os_excluida =	"AND tbl_os.excluida IS FALSE";
			$cond_finalizada = "AND tbl_os.data_fechamento IS NULL AND tbl_os.finalizada IS NULL";
		}

		$sql = "SELECT 	distinct (tbl_os.os),
			tbl_os.sua_os,
			$cond_excluida
			$cond_fone
			$cond_email
			tbl_posto.nome,
			tbl_posto_fabrica.codigo_posto,
			tbl_produto.descricao
		FROM (
			SELECT  distinct tbl_os.os
			FROM tbl_os
			JOIN tbl_os_produto using (os)
			JOIN tbl_os_item ON tbl_os_produto.os_produto=tbl_os_item.os_produto AND tbl_os_item.fabrica_i=$login_fabrica
			JOIN tbl_servico_realizado using (servico_realizado)
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_os_item.digitacao_item::date BETWEEN '$aux_data_inicial' AND '$aux_data_final'
			AND tbl_os_item.pedido IS NULL
			{$cond_os_excluida}
			{$cond_finalizada}
			AND tbl_servico_realizado.gera_pedido IS TRUE";
			if(strlen($posto)>0){$sql.=" AND tbl_os.posto=$posto ";}
			$sql .=" ) oss
			JOIN tbl_os on oss.os = tbl_os.os
			JOIN tbl_posto on tbl_os.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica on tbl_os.posto = tbl_posto_fabrica.posto and
			tbl_posto_fabrica.fabrica =$login_fabrica
			JOIN tbl_produto on tbl_os.produto = tbl_produto.produto
			JOIN tbl_os_produto on tbl_os.os=tbl_os_produto.os
			JOIN tbl_os_item on tbl_os_item.os_produto=tbl_os_produto.os_produto
			WHERE tbl_os_item.pedido isnull
			ORDER BY tbl_os.os";
			//echo $sql;exit;
		$resSubmit = pg_query($con, $sql);
	}

	if($telecontrol_distrib){
		if($_POST["gerar_excel"]){
			if(pg_num_rows($resSubmit) > 0){
				$data = date("d-m-Y-H:i");
				$fileName = "relatorio_os_sem_pedido-{$data}.xls";
				$file = fopen("/tmp/{$fileName}", "w");

				$thead = "
				<table border='1'>
					<thead>
						<tr>
							<th colspan='9' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
								".traduz("RELATÓRIO DE OS QUE NÃO GERARAM PEDIDOS")."
							</th>
						</tr>
						<tr>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("OS")."</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Código do Posto")."</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Posto")."</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Fone")."</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>E-mail</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Produto")."</th>
						</tr>
					</thead>
					<tbody>
					";
					fwrite($file, $thead);

					for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
						$os									= pg_result($resSubmit,$i,os);
						$sua_os							= pg_result($resSubmit,$i,sua_os);
						$posto_nome					= pg_result($resSubmit,$i,nome);
						$codigo_posto				= pg_result($resSubmit,$i,codigo_posto);
						$produto_descricao	= pg_result($resSubmit,$i,descricao);
						$fone								= pg_result($resSubmit,$i,fone);
						$email 							= pg_result($resSubmit,$i,email);
						
						$body .="  
						<tr>
							<td nowrap align='center' valign='top'>{$sua_os}</td>
							<td nowrap align='center' valign='top'>{$codigo_posto}</td>
							<td nowrap align='center' valign='top'>{$posto_nome}</td>
							<td nowrap align='center' valign='top'>{$fone}</td>
							<td nowrap align='center' valign='top'>{$email}</td>
							<td nowrap align='center' valign='top'>{$produto_descricao}</td>
						</tr>";
					}
					fwrite($file, $body);
					fwrite($file, "
						<tr>
							<th colspan='9' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >".traduz("Total de")." ".pg_num_rows($resSubmit)."".traduz(" registros")."</th>
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

}

if($login_fabrica <> 108 and $login_fabrica <> 111){
	$layout_menu = "callcenter";
	}else{
	$layout_menu = "gerencia";
}

$title = traduz("OS QUE NÃO GERARAM PEDIDOS");
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
		$.autocompleteLoad(Array("posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

	 	var table = new Object();
	  table['table'] = '#resultado_os_sem_pedido';
	  table['type'] = 'full';
	  $.dataTableLoad(table);
	});

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
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
	<div class='titulo_tabela '><?=traduz('Parâmetros de Pesquisa')?></div>
	<br/>

	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_inicial'><?=traduz('Data Inicial')?></label>
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

	<p><br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?=traduz('Pesquisar')?></button>
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
	<table id="resultado_os_sem_pedido" class='table table-striped table-bordered table-hover table-large' >
		<thead>
			<tr class='titulo_coluna' >
				<th>OS</th>
				<? if($telecontrol_distrib){?>
				<th><?=traduz('Código do Posto')?></th>
				<?}?>
				<th><?=traduz('Posto Autorizado')?></th>
				<? if($telecontrol_distrib){?>
				<th><?=traduz('Fone')?></th>
				<th>E-mail</th>
				<?}?>
				<th><?=traduz('Produto')?></th>
		</thead>
		<tbody>
		<?php
			for($i = 0; $i < $count; $i++) {
				$os									= pg_result($resSubmit,$i,'os');
				$sua_os							= pg_result($resSubmit,$i,'sua_os');
				$posto_nome					= pg_result($resSubmit,$i,'nome');
				$codigo_posto				= pg_result($resSubmit,$i,'codigo_posto');
				$produto_descricao	= pg_result($resSubmit,$i,'descricao');
				if($telecontrol_distrib){
					$fone								= pg_result($resSubmit,$i,'fone');
					$email 							= pg_result($resSubmit,$i,'email');
				}
				
				if($telecontrol_distrib){
					$body = "  
						<tr>
							<td class='tac' nowrap><a href='os_press.php?os={$os}' target='_blank' >{$sua_os}</a></td>
							<td class='tac'>{$codigo_posto}</td>
							<td>{$posto_nome}</td>
							<td nowrap>{$fone}</td>
							<td>{$email}</td>
							<td>{$produto_descricao}</td>
						</tr>
					";
				}else{
					$body = "  
						<tr>
							<td class='tac' nowrap><a href='os_press.php?os={$os}' target='_blank' >{$sua_os}</a></td>
							<td>{$posto_nome}</td>
							<td>{$produto_descricao}</td>
						</tr>
					";
				}
				echo $body;
			}
		?>
		</body>
	</table>
	<br />
	<?php
		if($telecontrol_distrib){
			$jsonPOST = excelPostToJson($_POST);
		?>
		<div id='gerar_excel' class="btn_excel">
			<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
			<span><img src='imagens/excel.png' /></span>
			<span class="txt"><?=traduz('Gerar Arquivo Excel')?></span>
		</div>

<?php
		}
	}else{
		echo '
			<div class="container">
			<div class="alert">
				    <h4>'.traduz("Nenhum resultado encontrado").'</h4>
			</div>
			</div>';
	}
}

include 'rodape.php';?>
