<!-- RELATÓRIO CRIADO EM 22/01/2010 (ATENDENDO HD 188352) (EDUARDO)-->
<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="auditoria,gerencia";
include 'autentica_admin.php';
$cachebypass=md5(time());

$btn_acao 	= $_POST['acao'];
$data_inicial 	= $_POST['data_inicial_01'];
$data_final 	= $_POST['data_final_01'];

$msg_erro = array();
$msgErrorPattern01 = "Preencha os campos obrigatórios.";
$msgErrorPattern02 = "A data de consulta deve ser no máximo de 3 meses.";

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);

if (isset($_GET["q"])) {
	$tipo_busca = $_GET["busca"];

	if (strlen($q) > 3) {
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$sql .= " LIMIT 50 ";
		$res = pg_query($con,$sql);
		if (pg_num_rows($res) > 0) {
			for ($i = 0; $i < pg_num_rows($res); $i++ ) {
				$cnpj = trim(pg_result($res,$i,cnpj));
				$nome = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$codigo_posto|$nome|$cnpj";
				echo "\n";
			}
		}
	}
	exit;
}

##INCIO DA VALIDACAO DE DATAS
if(strlen($btn_acao)>0) {

	if((!$data_inicial OR !$data_final) and !$os ) {
		$msg_erro["msg"][]    = $msgErrorPattern01;
		$msg_erro["campos"][] = "data";
	}

	##TIRA A BARRA
	if(strlen(trim($data_inicial))>0) {
		$dat = explode ("/", $data_inicial );
		$d = $dat[0];
		$m = $dat[1];
		$y = $dat[2];
		if(!checkdate($m,$d,$y)) {
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = "data";
		}
	}

	if(strlen(trim($data_final))>0) {
		$dat = explode ("/", $data_final );//tira a barra
		$d = $dat[0];
		$m = $dat[1];
		$y = $dat[2];
		if(!checkdate($m,$d,$y)) {
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = "data";
		}
	}

	if(count($msg_erro["msg"]) == 0) {
		$d_ini = explode ("/", $data_inicial);//tira a barra
		$nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

		$d_fim = explode ("/", $data_final);//tira a barra
		$nova_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

		if($nova_data_final < $nova_data_inicial) {
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = "data";
		}

		##Fim Validação de Datas
		if(count($msg_erro["msg"]) == 0) {
			$sql = "SELECT '$nova_data_final'::date - INTERVAL '3 MONTHS' > '$nova_data_inicial'::date ";
			$res = pg_query ($con,$sql);
			if (pg_fetch_result($res,0,0) == 't') {
				$msg_erro["msg"][]    = $msgErrorPattern02;
				$msg_erro["campos"][] = "data";
			}
		}
	}
}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE PEÇAS EM ESTOQUE";
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

	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {	$.lupa($(this));});

		function formatItem(row) { return row[0] + " - " + row[1];}

		function formatResult(row) { return row[0]; }
	});

	function retorna_posto(retorno){
		$("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
	}

	

</script>

<?php if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error"> <h4><?php echo $msg_erro["msg"][0]; ?></h4> </div>
<?php } ?>

<div class="row"> <b class="obrigatorio pull-right">  * Campos obrigatórios </b> </div>
<form class="form-search form-inline tc_formulario" name="frm_pesquisa" method="POST" action="<?echo $PHP_SELF?>">
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<br />
	<div class="container tc_container">
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("os", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>OS</label>
					<div class='controls controls-row'>
						<div class='span8'>
							<input type="text" name="os" id="os" size="10" maxlength="10" class='span12' value= "<?=$os?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("os", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Qtde Estoque</label>
					<div class='controls controls-row'>
						<div class='span8'>
							<input type="text" name="qtde_estoque" id="qtde_estoque" size="12" maxlength="3" class='span12' value= "<?=$qtde_estoque?>">
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="container tc_container">
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_inicial_01" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Final</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_final_01" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
	</div>

	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='codigo_posto'>Cod. Posto</label>
				<div class='controls controls-row'>
					<div class='span10 input-append'>
						<input type="text" id="codigo_posto" name="codigo_posto" class='span8' maxlength="20" value="<? echo $codigo_posto ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="referencia" />
						<input type="hidden" name="posto" value="<?=$posto?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='descricao_posto'>Nome Posto </label>
				<div class='controls controls-row'>
					<div class='span10 input-append'>
						<input type="text" id="descricao_posto" name="posto_nome" class='span12' value="<? echo $posto_nome; ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="descricao" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>

	<br />
	<center>
		<input type="button" class='btn' value="Pesquisar" onclick="document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" alt="Preencha as opções e clique aqui para pesquisar">
		<input type="hidden" name="acao">
	</center>
	<br />
</form>

<?php
if((strlen($btn_acao) > 0) && (count($msg_erro["msg"]) == 0)) {
	flush();
	$referencia		= $_POST['posto_referencia'];
	$descricao		= $_POST['posto_nome'];
	$os 			= $_POST['os'];
	$qtde_estoque 	= $_POST['qtde_estoque'];
	$codigo_posto 	= $_POST['codigo_posto'];
	$posto_nome   	= $_POST['posto_nome'];

	if(strlen(trim($codigo_posto)) > 0){
		$complemento_sql .= " AND tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
	}

	if(strlen(trim($os)) > 0){
		$complemento_sql .= " AND tbl_os.os = $os ";
	}

	if(strlen(trim($qtde_estoque)) > 0){
		$complemento_sql .= " AND tbl_estoque_posto.qtde = $qtde_estoque ";
	}

	if(strlen(trim($data_inicial))>0 and strlen(trim($data_final))>0){
		$complemento_sql .= " AND tbl_os.data_abertura between '$nova_data_inicial' and '$nova_data_final' ";
	}

	if (count($msg_erro["msg"]) == 0) {
		
		$sql = "SELECT tbl_posto.nome, tbl_os.sua_os, tbl_posto_fabrica.codigo_posto, tbl_os.os, tbl_os_item.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_estoque_posto.qtde
				FROM tbl_os
				INNER join tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto 
				and tbl_posto_fabrica.fabrica = $login_fabrica
				INNER JOIN tbl_posto on tbl_posto.posto =  tbl_posto_fabrica.posto  
				INNER JOIN tbl_os_produto on tbl_os_produto.os = tbl_os.os
				inner join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
				LEFT JOIN tbl_pedido_item on tbl_pedido_item.peca = tbl_os_item.peca 
				and tbl_pedido_item.pedido = tbl_os_item.pedido
				and tbl_pedido_item.qtde_faturada_distribuidor = 0
				INNER join tbl_peca ON tbl_peca.peca = tbl_os_item.peca
				LEFT join tbl_estoque_posto on tbl_estoque_posto.peca = tbl_os_item.peca 
				WHERE tbl_os.fabrica = $login_fabrica				
				and tbl_os_item.servico_realizado <> 11192
				$complemento_sql ";
		#echo nl2br($sql);
		$res = pg_query($con,$sql);
		if (pg_num_rows($res) > 0) {
			echo "	<table class='table table-striped table-bordered table-fixed'>
					<thead>
					<tr class='titulo_tabela'>
						<td colspan='4'>
							<center>";							
								echo "<h4>Período de ".$_POST['data_inicial_01']." até ".$_POST['data_final_01']."</h4>";
								echo "	
							</center>
						</td>
					</tr>";
					echo "<tr class='titulo_coluna'>";
						echo "<th>OS</th>";							
						echo "<th>Posto</th>";
						echo "<th>Peça</th>";
						echo "<th>Estoque</th>";
					echo "</tr>";
					echo "</thead>";
					echo "<tbody>";

			$total = pg_num_rows($res);
			$total_os = 0;
			if ($produto == '') {
				$produto = "0";
			}

			$data = date("d-m-Y-H-i");
			$fileName = "relatorio_peca_estoque_posto_{$data}.csv";
			$file = fopen("/tmp/{$fileName}", "w");

			$head = "OS;Posto;Peça;Estoque;\r\n";

			fwrite($file, $head);

			$body = "";

			for ($i = 0; $i < pg_num_rows($res); $i++) {
				$nome 				= trim(pg_fetch_result($res, $i, nome));
				$codigo_posto 		= trim(pg_fetch_result($res, $i, codigo_posto));
				$qtde				= trim(pg_fetch_result($res, $i, qtde));
				$referencia_peca 	= trim(pg_fetch_result($res, $i, referencia));
				$descricao_peca 	= trim(pg_fetch_result($res, $i, descricao));
				$os 				= trim(pg_fetch_result($res, $i, os));
				$sua_os				= trim(pg_fetch_result($res, $i, sua_os));
				$peca 				= trim(pg_fetch_result($res, $i, peca));

				$descricao_peca 	= str_replace(",", ".", $descricao_peca);

				$qtde = (strlen(trim($qtde > 0))) ? $qtde : 0;

				$body .= "$os;$codigo_posto - $nome; $referencia_peca - $descricao_peca; $qtde; \r\n";

				$total_os += $qtde;
				echo "	<tr>
					<td><a href='os_press.php?os=$sua_os' target='_blank'>$os&nbsp;</a></td>
					<td>$codigo_posto - $nome&nbsp;</td>
					<td>$referencia_peca - $descricao_peca</td>
					<td>$qtde&nbsp;</td>
				</tr>";
			}
			echo "	</tbody>";
			echo "	<tfoot>
					<tr class='titulo_coluna'>
						<td colspan='3' style='text-align:right;'>Total Estoque:</td>
						<td>$total_os&nbsp;</td>
					</tr>";
					echo "</tfoot>
					</table>";

		} else {
			echo "	<div class='alert'><h4>Nenhum resultado encontrado</h4></div>";
		}

		$body = $body;
	   	fwrite($file, $body);
	   	fclose($file);
	   	if (file_exists("/tmp/{$fileName}")) {
			system("mv /tmp/{$fileName} xls/{$fileName}");
			//echo "xls/{$fileName}";
		}

		if(pg_num_rows($res) >0){
			echo "	<div style=' width:700px; margin: 0 auto; text-align:center; font: bold 14px Arial;'><img src='imagens/excel.png' height=20px width=20px><a href='xls/$fileName'>Gerar ArquivoExcel</a></div>";
		}	

	}
}

include "rodape.php" ;

?>
