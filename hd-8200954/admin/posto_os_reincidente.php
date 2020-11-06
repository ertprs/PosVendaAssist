<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ((strlen($_REQUEST["posto"]) > 0) && (strlen($_REQUEST["data_inicial"]) > 0 )&& (strlen($_REQUEST["data_final"]) > 0)) {

	$posto        = $_REQUEST["posto"];
	$data_inicial = $_REQUEST["data_inicial"];
	$data_final   = $_REQUEST["data_final"];

	
	$sql = "SELECT tbl_posto_fabrica.posto
			FROM tbl_posto
			JOIN tbl_posto_fabrica USING(posto)
			WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
			AND tbl_posto.posto = $posto";
	$res = pg_query($con ,$sql);

	if (!pg_num_rows($res)) {
		$msg_erro["msg"][]    = "Posto não encontrado";
		$msg_erro["campos"][] = "posto";
	}



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

	if(count($msg_erro) == 0){
		$sql = " SELECT 	tbl_os_extra.os_reincidente, 
								tbl_os.os,
								tbl_os.serie,
								tbl_os.data_abertura,
								tbl_os.data_fechamento,
								tbl_os.nota_fiscal,
								tbl_os.obs_reincidencia,
								tbl_os.consumidor_nome,
								tbl_posto.cnpj,
								tbl_posto.nome,
								tbl_posto_fabrica.contato_estado,
								tbl_produto.referencia,
								tbl_produto.descricao
				 FROM tbl_os
				 LEFT JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado =  tbl_os.defeito_constatado AND
				 								tbl_defeito_constatado.fabrica = {$login_fabrica}
				 JOIN tbl_posto ON 	tbl_posto.posto = tbl_os.posto
				 JOIN tbl_posto_fabrica ON 	tbl_posto_fabrica.posto = tbl_posto.posto AND
											tbl_posto_fabrica.fabrica = {$login_fabrica}

				 JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
				 JOIN tbl_os_status ON tbl_os_status.os = tbl_os.os AND
									  tbl_os_status.status_os = 70
				 JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
				 WHERE  tbl_os.fabrica = $login_fabrica
					AND tbl_os.posto = $posto
					AND tbl_os.data_digitacao BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59'
					AND tbl_os.data_conserto isnull
					AND tbl_os_extra.os_reincidente notnull 
					AND tbl_os.excluida IS NOT TRUE
					AND tbl_os.os_reincidente IS TRUE ";
				
		$res_consulta = pg_query($con, $sql);
		$numRows = pg_num_rows($res_consulta);
		$sql_ok = true;
		if($numRows > 0){
			
			if(isset($_POST["gerar_excel"])){
				$data = date("d-m-Y-H:i");

				$fileName = "relatorio_posto_os_reincidente-{$data}.xls";

				$file = fopen("/tmp/{$fileName}", "w");

				$head = "OS Mãe;OS Reincidente;Série;Data Abertura;Data Fechamento;Posto;UF;Nota Fiscal;Nome Consumidor;Produto;Defeito Constatado;Motivo da Reincidência;\n";

				fwrite($file, $head);

				for($i = 0; $i < $numRows; $i++){
					$os_mae      		 = 	pg_fetch_result($res_consulta, $i, "os_reincidente"); 
					$os_reincidente      = 	pg_fetch_result($res_consulta, $i, "os");
					$serie               = 	pg_fetch_result($res_consulta, $i, "serie");
					$data_abertura       = 	pg_fetch_result($res_consulta, $i, "data_abertura");
					$data_fechamento     =	pg_fetch_result($res_consulta, $i, "data_fechamento");
					$nota_fiscal         = 	pg_fetch_result($res_consulta, $i, "nota_fiscal");
					$consumidor_nome     = 	pg_fetch_result($res_consulta, $i, "consumidor_nome");
					$obs_reincidencia    =	pg_fetch_result($res_consulta, $i, "obs_reincidencia");
					$codigo_posto        =	pg_fetch_result($res_consulta, $i, "cnpj");
					$posto               =	pg_fetch_result($res_consulta, $i, "nome");
					$contato_estado      =	pg_fetch_result($res_consulta, $i, "contato_estado");
					$produto_rerferencia =	pg_fetch_result($res_consulta, $i, "referencia");
					$produto_nome        =	pg_fetch_result($res_consulta, $i, "nome");
					$defeito_constatado  =	pg_fetch_result($res_consulta, $i, "defeito_constatado");
					
					$body = "$os_mae;$os_reincidente;$serie;$data_abertura;$data_fechamento;$codigo_posto - $posto;$contato_estado;$nota_fiscal;$consumidor_nome;$produto_rerferencia - $produto_nome ;$defeito_constatado;$obs_reincidencia;\n";
					fwrite($file, $body);
				}
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
$title = "RELATÓRIO DE OS REINCIDENTE";
include 'cabecalho_new.php';

$plugins = array(
	"shadowbox",
	"dataTable",
	"tooltip"
);

include("plugin_loader.php"); ?>

<script language="javascript">

	$(function() {
		Shadowbox.init();

	});

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


</div>

<? if($sql_ok == 'true' && count($msg_erro) == 0) {
	if($numRows > 0){ ?>
		<table id="resultado_consulta" class='table table-striped table-bordered table-hover table-large' >
			<thead>
				<tr class='titulo_coluna' >
					<th>OS Mãe</th>
					<th>OS Reincidente</th>
					<th>Série</th>
					<th>Data Abertura</th>
					<th>Data Fechamento</th>
					<th>Posto</th>
					<th>UF</th>
					<th>Nota Fiscal</th>
					<th>Consumidor Nome</th>
					<th>Produto</th>
					<th>Defeito Constatado</th>
					<th>Motivo da Reincidência</th>
				</tr>
			</thead>
			<tbody> <?
			for($i = 0; $i < $numRows; $i++){
					$os_mae      		 = 	pg_fetch_result($res_consulta, $i, "os_reincidente"); 
					$os_reincidente      = 	pg_fetch_result($res_consulta, $i, "os");
					$serie               = 	pg_fetch_result($res_consulta, $i, "serie");
					$data_abertura       = 	pg_fetch_result($res_consulta, $i, "data_abertura");
					$data_fechamento     =	pg_fetch_result($res_consulta, $i, "data_fechamento");
					$nota_fiscal         = 	pg_fetch_result($res_consulta, $i, "nota_fiscal");
					$consumidor_nome     = 	pg_fetch_result($res_consulta, $i, "consumidor_nome");
					$obs_reincidencia    =	pg_fetch_result($res_consulta, $i, "obs_reincidencia");
					$codigo_posto        =	pg_fetch_result($res_consulta, $i, "cnpj");
					$posto_nome          =	pg_fetch_result($res_consulta, $i, "nome");
					$contato_estado      =	pg_fetch_result($res_consulta, $i, "contato_estado");
					$produto_rerferencia =	pg_fetch_result($res_consulta, $i, "referencia");
					$produto_nome        =	pg_fetch_result($res_consulta, $i, "nome");
					$defeito_constatado  =	pg_fetch_result($res_consulta, $i, "defeito_constatado");
					if(strlen($obs_reincidencia)>40){
						$obs_resumo = substr($obs_reincidencia, 0,50)." ...";
					}else{
						$obs_resumo = $obs_reincidencia;
					}
					?>
					
				<tr>
					<td><a href="os_press.php?os=<?=$os_mae?>" target="_blank"><?=$os_mae?></a></td>
					<td><a href="os_press.php?os=<?=$os_reincidente?>" target="_blank"><?=$os_reincidente?></a></td>
					<td><?=$serie?></td>
					<td><?=$data_abertura?></td>
					<td><?=$data_fechamento?></td>
					<td nowrap><?=$codigo_posto." - ".$posto_nome?></td> 
					<td><?=$contato_estado?></td>
					<td><?=$nota_fiscal?></td>
					<td nowrap><?=$consumidor_nome?></td>
					<td nowrap><?=$produto_rerferencia." - ". $produto_nome?></td> 
					<td><?=$defeito_constatado?></td>
					<td nowrap><span data-toggle="tooltip" data-placement="top" title="<?=$obs_reincidencia?>"><?=$obs_resumo?></span></td>
				</tr>
		<?	} ?>
			</tbody>
		</table>
		
			
				<script>
					$.dataTableLoad({ table: "#resultado_consulta" });
				</script>
		
			<br />

			<?php
				$jsonPOST = excelPostToJson($_GET);

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
