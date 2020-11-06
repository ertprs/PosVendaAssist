<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';
require __DIR__.'/../classes/api/Client.php';

use api\Client;

$tabela = $_GET['parametro'];
$id        = $_GET['id'];

$layout_menu = "gerencia";
$title = "RELATÓRIO DE LOG DE ALTERAÇÃO";
//include 'cabecalho_new.php';

$plugins = array(
	"dataTable"
);

include("plugin_loader.php");

$title = "RELATÓRIO DE LOG DE ALTERAÇÃO";
define('BI_BACK', (strpos($_SERVER['PHP_SELF'],'/bi/') == true)?'../':'');



$client = Client::makeTelecontrolClient("auditor","auditor");
$client->urlParams = array(
	"aplication" => "02b970c30fa7b8748d426f9b9ec5fe70",
	"table" =>$tabela,
	"primaryKey" => $login_fabrica."*".$id,
	"limit" => "50"
);

try{
	$res = $client->get();

	if(count($res)){
		foreach ($res as $key => $value) {
			// Pega o nome do responsável pela alteração
			if($value['data']['user_level'] == "posto"){
				$sql = "SELECT nome FROM tbl_posto where posto = ".$value['data']['user'];

				$result = pg_query($con,$sql);
				$nome = pg_result($result,0,nome);
			}elseif($value['data']['user_level'] == "admin"){
				$sql = "SELECT nome_completo FROM tbl_admin where admin = ".$value['data']['user']." and fabrica = ".$login_fabrica;
				$result = pg_query($con,$sql);
				$nome = pg_result($result,0,nome_completo);
			}

			$value['user_name'] = $nome;

			unset($value['data']['content']['antes']['faq']); //HD-3103180
			unset($value['data']['content']['antes']['faq_causa']); //HD-3103180

			unset($value['data']['content']['depois']['faq']); //HD-3103180
			unset($value['data']['content']['depois']['faq_causa']); //HD-3103180

			$value['data']['content']['antes']['outros_motivos'] = $value['data']['content']['antes']['situacao'];
			$value['data']['content']['depois']['outros_motivos'] = $value['data']['content']['depois']['situacao'];


			$solucao_antes 	= $value['data']['content']['antes']['solucao'];
			$solucao_depois = $value['data']['content']['depois']['solucao'];

			if(strlen(trim($solucao_antes)) > 0){
				$value['data']['content']['antes']['resposta'] = $solucao_antes;
			}

			if(strlen(trim($solucao_depois)) > 0){
				$value['data']['content']['depois']['resposta'] = $solucao_depois;
			}
			#$value['data']['content']['antes']['resposta'] = $value['data']['content']['antes']['solucao'];
			#$value['data']['content']['depois']['resposta'] = $value['data']['content']['depois']['solucao'];

			unset($value['data']['content']['antes']['situacao']);
			unset($value['data']['content']['depois']['situacao']);
			unset($value['data']['content']['antes']['solucao']);
			unset($value['data']['content']['depois']['solucao']);
			unset($value['data']['content']['antes']['faq_solucao']);
			unset($value['data']['content']['depois']['faq_solucao']);
			// Parse de valores do banco para UI
			foreach($value['data']['content']['antes'] AS $keyA => $valueA){

				if($valueA == "t"){
					$value['data']['content']['antes'][$keyA] = "Sim";
				}

				if($valueA == "f"){
					$value['data']['content']['antes'][$keyA] = "Não";
				}

				if($valueA == "null"){
				 	$value['data']['content']['antes'][$keyA] = "";
				}

				if(trim($valueA) == ""){
					$value['data']['content']['antes'][$keyA] = "";
				}

			}

			foreach($value['data']['content']['depois'] AS $keyD => $valueD){
				 if($valueD == "t"){
					$value['data']['content']['depois'][$keyD] = "Sim";
				 }

				 if($valueD == "f"){
					$value['data']['content']['depois'][$keyD] = "Não";
				 }

				 if($valueD == "null"){
				 	$value['data']['content']['depois'][$keyD] = "";
				 }

				 if(trim($valueD) == ""){
				 	$value['data']['content']['depois'][$keyD] = "";
				 }

			}


			//Coloca nome nos admins de antes e depois
			$sql = "SELECT nome_completo FROM tbl_admin where admin = ".$value['data']['content']['antes']['admin']." and fabrica = ".$login_fabrica;

			$result = pg_query($con,$sql);
			$nome = pg_result($result,0,nome_completo);

			if($nome != ""){
			 	$value['data']['content']['antes']['admin'] = $nome;
			}


			$sql = "SELECT nome_completo FROM tbl_admin where admin = ".$value['data']['content']['depois']['admin']." and fabrica = ".$login_fabrica;

			$result = pg_query($con,$sql);
			$nome = pg_result($result,0,nome_completo);

			if($nome != ""){
				$value['data']['admin'] = $nome;
			 	$value['data']['content']['depois']['admin'] = $nome;
			}

			//Verifica diferenças e retira chaves sem valor significativo
			$array_antes = $value['data']['content']['antes'];
			$array_depois = $value['data']['content']['depois'];


			$alteracoes = array();
			if(strtoupper($value['data']['action']) != "INSERT"){
				foreach($array_antes AS $keyA => $valueA){
					if($valueA != $array_depois[$keyA]){
						$alteracoes[$keyA] = $array_depois[$keyA];
					}
				}
			}else{
				foreach ($value['data']['content']['depois'] as $k => $val) {
					if($val == ""){
						$keysUnset[] = $k;
					}
				}
				foreach ($keysUnset as $k) {
					unset($value['data']['content']['depois'][$k]);
				}
				$alteracoes = $value['data']['content']['depois'];
			}
			//-------------------


			$value['data']['alteracoes'] = $alteracoes;

			$res[$key] = $value;
		}

	}else{
		$error = "Nenhum log encontrado";
	}
}catch(Exception $ex){
	$error = $ex->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title><?=$title?></title>
		<link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>bootstrap/css/bootstrap.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>bootstrap/css/extra.css" />
		<!-- <link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>css/tc_css.css" /> -->
		<!-- <link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>css/tooltips.css" /> -->
		<link type="text/css" rel="stylesheet" media="screen" href="<?=BI_BACK?>bootstrap/css/ajuste.css" />

		<script src="<?=BI_BACK?>plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="<?=BI_BACK?>bootstrap/js/bootstrap.js"></script>

		<?php // include("plugin_loader.php"); ?>
		<?php if (strlen($LOG_template['CSS'])): ?>
		<style>
			<?=$LOG_template['CSS']?>
		</style>
		<?php endif; ?>
	</head>
<body>

<?php

if(strlen($error) == 0){

	$colspan = 4;
	if(in_array($login_fabrica, array(91))){
		$colspan = 5;
	}

?>

<table class="table table-striped table-bordered table-hover table-fixed">
	<thead>
		<tr class="titulo_tabela">
			<th colspan="<?=$colspan?>">Logs de Alteração</th>
		</tr>
		<tr class="titulo_coluna">
			<th>Usuário</th>
			<?php
			if(in_array($login_fabrica, array(91))){
				?>
				<th>Ação</th>
				<?php
			}
			?>
			<th>Horário</th>
			<th>Antes</th>
			<th>Depois</th>
		</tr>
	</thead>
	<tbody>
		<?php


	foreach ($res as $key => $value) {
		if(count($value['data']['alteracoes']) == 0 and $login_fabrica == 1 ) {
			continue;
		}

		$atualizacao = (!empty($value['data']['content']['depois']['data_alteracao'])) ? $value['data']['content']['depois']['data_alteracao'] : $value['data']['content']['depois']['data_atualizacao'];

		if($atualizacao == ""){
			$atualizacao = date("Y-m-d H:i:s",$value['data']['created']);
			//Ajuste de time da backend2 para o Pos-Venda
			$atualizacao =  strtotime($atualizacao." -10 minutes");

			//echo date("H:i",$atualizacao);exit;

		}else{
			$atualizacao = strtotime($atualizacao);
		}

		echo "<tr>";

		if($value['user_name'] == ""){
			echo "<td>".$value['data']['content']['depois']['admin']."</td>";
		}else{
			echo "<td>".$value['user_name']."</td>";
		}

		if(in_array($login_fabrica, array(91))){
			echo "<td>".$value['data']['action']."</td>";
		}
		echo "<td class='tac'>".date("d-m-Y H:i:s",$atualizacao)."</td>";

		$arr_nome_colunas = array('nome', 'descricao', 'situacao');

		$keys = array_keys($value['data']['alteracoes']);
		if(count($keys)>0){
			echo "<td>";
			echo "	<ul>";
			foreach ($keys as $keyname) {

				foreach($arr_nome_colunas as $nome_coluna){
					$info = "";

					if(is_int($value['data']['content']['antes'][$keyname])) {
						$sql = "SELECT $nome_coluna FROM tbl_$keyname WHERE $keyname = ".$value['data']['content']['antes'][$keyname];
						$res_nome_coluna = pg_query($con, $sql);

						if(strlen(trim(pg_last_error()))== 0 ){
							$info = pg_fetch_result($res_nome_coluna, 0, $nome_coluna);
							break;
						}
					}
				}

				if(strlen(trim($info))>0){
					echo "<li>".str_replace("_", " ", $keyname)." - ".$info ." (".$value['data']['content']['antes'][$keyname].")"."</li>";
				}else{
					//$value['data']['content']['antes'][$keyname]
					echo "<li>".str_replace("_", " ", $keyname)." - ".$value['data']['content']['antes'][$keyname]."</li>";
				}

			}
			echo "</ul>";
		}else{
			echo "<td colspan='2' class='tac'>";
			echo "Registro gravado sem alterações";
		}
		echo "</td>";

		if(count($keys)>0){
			echo "<td>";
			echo "	<ul>";
			foreach ($value['data']['alteracoes'] as $key => $alt) {

				foreach($arr_nome_colunas as $nome_coluna){
					$info = "";

					if(is_int($alt)) {
						$sql = "SELECT $nome_coluna FROM tbl_$key WHERE $key = ".$alt;
						$res_nome_coluna = pg_query($con, $sql);

						if(strlen(trim(pg_last_error()))== 0 ){
							$info = pg_fetch_result($res_nome_coluna, 0, $nome_coluna);
							break;
						}
					}
				}

				if(strlen(trim($info))>0){
					echo "		<li>".str_replace("_", " ",$key)." - ".$info ." (".$alt.")</li>";
				}else{
					echo "		<li>".str_replace("_", " ",$key)." - ".$alt."</li>";
				}

			}
			echo "</ul>";
			echo "</td>";
		}
		echo "</tr>";

		}
		?>
		</tbody>
</table>


<?php
}else{
	?>
	<div style="align-items: center; display: flex; min-height: 100%; min-height: 100vh;">
		<div class='container'>
			<div class='row-fluid'>
				<div class='span12'>
					<div class="alert">
					  <h4>Nenhum Registro de Log encontrado.</h4>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}
?>

</body>
</html>

 
