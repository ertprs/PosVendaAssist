<?php
$areaAdmin = preg_match('@/admin/@',$_SERVER['PHP_SELF']) > 0;

if($areaAdmin){
	include __DIR__.'/dbconfig.php';
	include __DIR__.'/includes/dbconnect-inc.php';
	include __DIR__.'/autentica_admin.php';
	include __DIR__.'/funcoes.php';	
}
else{
	include __DIR__.'/../dbconfig.php';
	include __DIR__.'/../includes/dbconnect-inc.php';
	include __DIR__.'/../autentica_usuario.php';
}

use model\ModelHolder;

if ($_POST["buscaDefeitoConstatado"] == true) {
	$produto = strtoupper($_POST["produto"]);

	if (strlen($produto) > 0) {
		$sql = "SELECT familia FROM tbl_produto WHERE fabrica_i = $login_fabrica AND produto = $produto";
		$res = pg_query($con, $sql);

		$familia = pg_fetch_result($res, 0, "familia");

		if (strlen($familia) > 0) {
			$sql = "SELECT DISTINCT(tbl_diagnostico.defeito_constatado) AS defeito_id, tbl_defeito_constatado.descricao AS defeito_descricao
					FROM tbl_diagnostico
			        JOIN tbl_defeito_constatado ON tbl_diagnostico.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.ativo IS TRUE
					WHERE tbl_diagnostico.ativo IS TRUE
					AND tbl_diagnostico.familia = $familia
					ORDER BY tbl_defeito_constatado.descricao";
			$res = pg_query($con, $sql);
	
			if (pg_num_rows($res) > 0) {
				$defeitos = array();
	
				while ($result = pg_fetch_object($res)) {
					$defeitos[$result->defeito_id] = utf8_encode($result->defeito_descricao);
				}
	
				$retorno = array("defeitos" => $defeitos);
			} else {
				$retorno = array("erro" => utf8_encode("Nenhum defeito constatado encontrado para o produto"));
			}
		}
		else{
			$retorno = array("erro" => utf8_encode("Família do produto não encontrada"));	
		}
	} else {
		$retorno = array("erro" => utf8_encode("Nenhum produto selecionado"));
	}

	exit(json_encode($retorno));
}

if ($_POST["buscaSolucao"] == true) {
	header('Content-Type: application/json');
	$produto = strtoupper($_POST["produto"]);

	if (strlen($produto) > 0) {
		$sql = "SELECT familia FROM tbl_produto WHERE fabrica_i = $login_fabrica AND produto = $produto";
		$res = pg_query($con, $sql);

		$familia = pg_fetch_result($res, 0, "familia");

		if (strlen($familia) > 0) {
			$sql = "SELECT DISTINCT(tbl_diagnostico.solucao) AS solucao_id, tbl_solucao.descricao AS solucao_descricao
					FROM tbl_diagnostico
			        JOIN tbl_solucao ON tbl_diagnostico.solucao = tbl_solucao.solucao AND tbl_solucao.ativo IS TRUE
					WHERE tbl_diagnostico.ativo IS TRUE
					AND tbl_diagnostico.familia = $familia
					ORDER BY tbl_solucao.descricao";
			$res = pg_query($con, $sql);
	
			if (pg_num_rows($res) > 0) {
				$solucoes = array();
	
				while ($result = pg_fetch_object($res)) {
					$solucoes[$result->solucao_id] = utf8_encode($result->solucao_descricao);
				}
	
				$retorno = array("solucoes" => $solucoes);
			} else {
				$retorno = array("erro" => utf8_encode("Nenhuma Solução encontrada para a família do produto"));
			}
		}
		else{
			$retorno = array("erro" => utf8_encode("Família do produto não encontrada"));	
		}
	} else {
		$retorno = array("erro" => utf8_encode("Nenhum produto selecionado"));
	}

	exit(json_encode($retorno));
}

if ($_POST["buscaDefeitoPeca"] == true) {
	$peca = strtoupper($_POST["peca"]);

	if (strlen($peca) > 0) {
		$sql = "SELECT tbl_defeito.defeito AS defeito_id, tbl_defeito.descricao AS defeito_descricao
				FROM tbl_defeito
				WHERE tbl_defeito.fabrica = $login_fabrica
				AND tbl_defeito.ativo IS TRUE
				ORDER BY tbl_defeito.descricao";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$defeitos = array();

			while ($result = pg_fetch_object($res)) {
				$defeitos[$result->defeito_id] = utf8_encode($result->defeito_descricao);
			}

			$retorno = array("defeitos" => $defeitos);
		} else {
			$retorno = array("erro" => utf8_encode("Nenhum defeito encontrado para a peça"));
		}
	} else {
		$retorno = array("erro" => utf8_encode("Nenhuma peça selecionado"));
	}

	exit(json_encode($retorno));
}

if($_REQUEST['buscaCidade'] == true && !empty($_REQUEST['estado'])){
	header('Content-Type: application/json');
	$model = ModelHolder::init('Cidade');
	$sql = 'SELECT DISTINCT cidade AS value, nome AS label FROM tbl_cidade WHERE estado = :estado AND nome IS NOT NULL AND NOT (nome ~ E\'^[0-9]*$\') ORDER BY nome ASC';
	$params = array('estado' => strtoupper($_REQUEST['estado']));
	$cidades = $model->executeSql($sql,$params);
	die(json_encode(array_map(function($value){
		return array('value'=>$value['value'],'label'=>utf8_encode($value['label']));
	},$cidades)));
}

if($_REQUEST['buscaCep'] == true && !empty($_REQUEST['cep'])){
	header('Content-Type: application/json');
	require_once __DIR__.'/../classes/cep.php';
	$cep = $_REQUEST['cep'];
	$address = CEP::consulta($cep);
	$address['cidade'] = filter($address['cidade']);
	$sql = 'SELECT cidade FROM tbl_cidade WHERE UPPER(tbl_cidade.nome) LIKE :cidade AND UPPER(tbl_cidade.estado) LIKE :estado LIMIT 1;';
	$params = array(':cidade'=> strtoupper($address['cidade']),':estado' => strtoupper($address['uf']));
	$model = ModelHolder::init('Cidade');
	$result = $model->executeSql($sql,$params);
	$address['cidade'] = $result[0]['cidade'];
	die(json_encode(array_map(utf8_encode,$address)));
}

function filter($value) {   
    $from = "áàãâéêíóôõúüçÁÀÃÂÉÊÍÓÔÕÚÜÇ";
    $to = "aaaaeeiooouucAAAAEEIOOOUUC";
    $value = strtr($value,$from,$to);
    return $value;
}

?>