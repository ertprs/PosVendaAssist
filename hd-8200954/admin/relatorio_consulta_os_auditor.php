<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="auditoria";
include 'autentica_admin.php';
include 'funcoes.php';
require __DIR__.'/../classes/api/Client.php';

use api\Client;

$layout_menu = "auditoria";
$title = "RELATÓRIO CONSULTA OS";
include 'cabecalho_new.php';


$plugins = array(
   "datepicker",
   "dataTable",
);

include("plugin_loader.php");

$os = $_GET['os'];
$cpfCnpj = $_GET['cpfCnpj'];
$dataInicial = $_GET['dataInicial'];
$dataFinal = $_GET['dataFinal'];
if(!empty($_GET['btn_acao'])){

	if(strlen(trim($cpfCnpj)) > 0){
		$valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",$cpfCnpj));

		if(empty($valida_cpf_cnpj)){
			$sqlvalida = "SELECT fn_valida_cnpj_cpf('$cpfCnpj')";
			$resvalida = pg_query($con,$sqlvalida);
			if(strlen(pg_last_error($con)) > 0){
				$msg_erro = "CPF/CNPJ Inválido!";
			}
		}else{
			$msg_erro = $valida_cpf_cnpj;
		}
	}

	if(strlen($msg_erro) == 0){
		if($login_fabrica == 1){
			$client = Client::makeTelecontrolClient("auditor-legacy","logConsultaOs");
		}else{
			$client = Client::makeTelecontrolClient("auditor-legacy","log-consulta-os");
		}

		//http://api2.telecontrol.com.br/auditor-legacy/log-consulta-os/fabrica/117/os/37138341/desc/true
		$client->urlParams = array(
			"fabrica" => $login_fabrica
		);

		if($os != ""){
			$client->urlParams["os"] = $os;
		}

		if(!empty($cpfCnpj)){
			$client->urlParams["cpfCnpj"] = $cpfCnpj;
		}

		if(!empty($dataInicial) and !empty($dataFinal)){
			list($dia, $mes,$ano) = explode("/",$dataInicial);
			$xdataInicial = "$ano-$mes-$dia";
			list($dia, $mes,$ano) = explode("/",$dataFinal);
			$xdataFinal = "$ano-$mes-$dia";
			$client->urlParams["dataInicial"] = $xdataInicial;
			$client->urlParams["dataFinal"] = $xdataFinal;
		}


		try{

			$res = $client->get();
			if(count($res)){
				//filtro de informações para criar tabela
				$data = array();

				if($login_fabrica == 1){
					foreach ($res as $audit) {
						if(is_array($audit['content']) and !empty($audit['content']['os'])) {
							$data[] = array(
								"ip" => $audit['content']['user_ip_address'],
								"data" => date("d-m-Y H:i:s",$audit['created']),
								"os" => $audit['content']['sua_os'],
								"consumidor_cpf" => $audit['content']['consumidor_cpf'],
								"revenda_cnpj" => $audit['content']['revenda_cnpj'],
								"posto" => $audit['content']['nome'],
								"consumidor" => $audit['content']['consumidor_nome'],
								"consumidor_email" => $audit['content']['consumidor_email'],
								"status_os" => $audit['content']['status'],//alterdo para elgin "status_os" => $audit['content']['entity']['status_os']
								"produto" => $audit['content']['descricao_produto'],
							);
						}
					}
				}else if(in_array($login_fabrica, array(3, 169, 170))){
					foreach ($res as $audit) {
						if(is_array($audit['content']) and !empty($audit['content']['entity']['os'])) {

							$checkpoint = $audit['content']['entity']['status_checkpoint'];


							$sql = "SELECT descricao FROM tbl_status_checkpoint WHERE status_checkpoint = {$checkpoint}";
							$res = pg_query($con, $sql);

							$status_checkpoint = pg_fetch_result($res,0,'descricao');

							$data[] = array(
								"ip" => $audit['content']['user_ip_address'],
								"data" => date("d-m-Y H:i:s",$audit['created']),
								"os" => $audit['content']['entity']['sua_os'],
								"consumidor_cpf" => $audit['content']['entity']['consumidor_cpf'],
								"revenda_cnpj" => $audit['content']['entity']['revenda_cnpj'],
								"posto" => $audit['content']['entity']['nome'],
								"consumidor" => $audit['content']['entity']['consumidor_nome'],
								"consumidor_email" => $audit['content']['entity']['consumidor_email'],
								"status_os" => $status_checkpoint,//alterdo para elgin "status_os" => $audit['content']['entity']['status_os']
								"produto" => $audit['content']['entity']['descricao_produto'],
							);
						}
					}
				}else{
					foreach ($res as $audit) {
						if(is_array($audit['content']) and !empty($audit['content']['entity']['os'])) {
							$data[] = array(
								"ip" => $audit['content']['user_ip_address'],
								"data" => date("d-m-Y H:i:s",$audit['created']),
								"os" => $audit['content']['entity']['os'],
								"consumidor_cpf" => $audit['content']['entity']['consumidor_cpf'],
								"revenda_cnpj" => $audit['content']['entity']['revenda_cnpj'],
								"posto" => $audit['content']['entity']['nome'],
								"consumidor" => $audit['content']['entity']['consumidor_nome'],
								"consumidor_email" => $audit['content']['entity']['consumidor_email'],
								"status_os" => $audit['content']['status'],//alterdo para elgin "status_os" => $audit['content']['entity']['status_os']
								"produto" => $audit['content']['entity']['descricao_produto'],
							);
						}
					}
				}
			}else{
				$error = "Nenhum log encontrado para o posto";
			}
		}catch(Exception $ex){

			$error = $ex->getMessage();
		}
	}
}

if ($gerar_xls == 't' && count($data) > 0) {

		$conteudo .= '
				<table border="1">
			      <thead>
					  <tr bgcolor="#596d9b">
						  <th colspan="'.count($data[0]).'">
						  	<font size="3" face="arial" color="white">Logs de Consulta</font>
						  </th>
					  </tr>
					  <tr bgcolor="#596d9b">';

						$keys = array_keys($data[0]);
						foreach ($keys as $value) {
							$value = str_replace("_"," ",$value);
							$conteudo .=  "<th><font size='2' face='arial' color='white'>".strtoupper($value) ."</font></th>";
						}
		$conteudo .= '</tr>
				</thead>
				<tbody>';

					foreach ($data as $value) {
						$conteudo .=  "<tr>";
						foreach ($value as $reg) {
							$conteudo .=  "<td>".$reg."</td>";
						}
						$conteudo .=  "</tr>";
					}

		$conteudo .= '</tbody>
				</table>';

		$arquivo_nome = "relatorio_consulta_os_auditor_$login_fabrica_".date('YmdHis').".xls";
		$path         = "xls/";
		$path_tmp     = "/tmp/";

		$arquivo_completo     = $path.$arquivo_nome;
		$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

		$file = fopen($arquivo_completo_tmp, 'w');
		fwrite($file, $conteudo);
		fclose($file);

		system("cp $arquivo_completo_tmp $path");//COPIA ARQUIVO PARA DIR XLS
	}
?>
<script language="javascript">
	$(function(){

		$("#dataInicial").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" });
		$("#dataFinal").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" });
		$.dataTableLoad();
	});

function digita_os(){
	$("#os").keyup(function(event) {
	    var os = $("#os").val().length;
	    if (os > 0) { // 8 = keycode for backspace
	    	$("#cpfCnpj").prop('readonly', true);
	    } else {
	        $("#cpfCnpj").prop('readonly', false);
	    }
	});
}
function digita_cpf_cnpj(){
	$("#cpfCnpj").keyup(function(event) {
	    var cpf_cnpj = $("#cpfCnpj").val().length;
	    if (cpf_cnpj > 0) { // 8 = keycode for backspace
	    	$("#os").prop('readonly', true);
	    } else {
	        $("#os").prop('readonly', false);
	    }
	});
}
</script>
<!-- Fechando container-tc do menu -->
</div>
<!-- ............................. -->
<div class="container tc_container">
	<div class="row-fluid">
		<div class="span12" style="background: #F5F5F5;margin-bottom: 20px">
			<form action="<?php echo $PHP_SELF; ?>" method="GET" class="form-inline">
				<input type="hidden" name="gerar_xls" value="t">
				<div class="row-fluid tac" style="background: #596D9B;color: #fff;height:35px">
					<h4>Log de Consulta Os - Site Institucional</h4>
				</div>
				<div class="row-fluid" style="height:30px">
				</div>
				<div class="row-fluid tac" style="height:25px">
					<div class="span12" >
						<div class="tac">
							<label class="control-label" for="inputWarning">Numero da Os</label>
							<?php if($login_fabrica == 1){ ?>
								<input  type="text" name="os" onclick="digita_os();" id="os" value="<?php echo $os ?>" placeholder="Número da Ordem de Serviço">
							<?php }else{ ?>
								<input  type="text" name="os" value="<?php echo $os ?>" placeholder="Número da Ordem de Serviço">
							<?php } ?>
						</div>
					</div>
				</div>
				<? if($login_fabrica <> 117) { ?>
				<div class="row-fluid tac" style="height:25px">
					<div class="span12" >
						<div class="tac">
							<label class="control-label" for="inputWarning">CPF Consumidor</label>
							<?php if($login_fabrica == 1){ ?>
								<input  type="text" name="cpfCnpj" onclick="digita_cpf_cnpj();" id="cpfCnpj" value="<?php echo $cpfCnpj ?>" placeholder="CPF do Consumidor">
							<?php }else{ ?>
								<input  type="text" name="cpfCnpj" value="<?php echo $cpfCnpj ?>" placeholder="CPF do Consumidor">
							<?php } ?>
						</div>
					</div>
				</div>
				<?php if($login_fabrica <> 1){ ?>
					<div class="row-fluid tac" style="height:25px">
						<div class="span12" >
							<div class="tac">
								<label class="control-label" for="inputWarning">Data Inicial</label>
								<input  type="text" id='dataInicial' name="dataInicial" value="<?php echo $dataInicial ?>" placeholder="Data Inicial">
								<label class="control-label" for="inputWarning">Data Inicial</label>
								<input  type="text" id='dataFinal' name="dataFinal" value="<?php echo $dataFinal ?>" placeholder="Data Final">
							</div>
						</div>
					</div>

				<? }
				}
				?>
				<div class="row-fluid" style="height:25px;margin-top:25px">
					<div class="span12" >
						<div class="control-group">
					    <div class="controls tac">
					      <input type="submit" class="btn" name='btn_acao' value="Buscar">
					      <a class="btn btn-warning" href="<?php echo $PHP_SELF; ?>">Limpar Busca</a>
					    </div>
					  </div>

					</div>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
if(strlen($msg_erro) > 0){
	?>
	<div class='container'>
		<div class='row-fluid'>
			<div class='span12'>
				<div class="alert alert-danger">
					<?=$msg_erro?>
				</div>
			</div>
		</div>
	</div>
	<?php
	exit;
}
if(strlen($error) == 0 and count($_GET) > 0 ){
?>
<table class="table table-striped table-bordered table-hover table-fixed" style="padding-left: 10px;padding-right: 10px">
	<thead>
		<tr class="titulo_tabela">
			<th colspan="<?php echo count($data[0]) ?>">Logs de Consulta</th>
		</tr>
		<tr class="titulo_coluna">
			<?php
			$keys = array_keys($data[0]);
			foreach ($keys as $value) {
				$value = str_replace("_"," ",$value);
				echo "<th>".strtoupper($value) ."</th>";
			}
			?>
		</tr>
	</thead>
	<tbody>
		<?php
		foreach ($data as $value) {
			echo "<tr>";
			foreach ($value as $reg) {
				echo "<td>".$reg."</td>";
			}
			echo "</tr>";
		}
		?>
	</tbody>
</table>


<?php
if ($gerar_xls == 't' && count($data) > 0) {

	echo "
	<a href='../admin/xls/".$arquivo_nome."' target='_blank'>
		<div class='btn_excel'>
			<span>
				<img src='imagens/excel.png' />
			</span>
			<span class='txt'>Download em Excel</span>
		</div>
	</a><br />";
}


}elseif(!empty($error)){
	?>
	<div class='container'>
		<div class='row-fluid'>
			<div class='span12'>
				<div class="alert">
				  Nenhum Registro de Log encontrado para essa fabrica.
				</div>
			</div>
		</div>
	</div>
	<?php
}


 include 'rodape.php';?>
