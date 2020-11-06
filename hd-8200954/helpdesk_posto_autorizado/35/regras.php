<?php
$attCfg = array(
	'labels' => array(
		0 => 'Anexar',
	)
);

$fabrica_qtde_anexos = count($attCfg['labels']);
$GLOBALS['attCfg'] = $attCfg;

$regras['anexo']['function'] = array('validaAnexosCadence');

// Valida anexos
function validaAnexosCadence() {
	global $campos, $attCfg, $login_fabrica, $con;

	$sql_obrigatorio = "SELECT JSON_FIELD('3_anexo', campo_obrigatorio) AS obrigatorio_anexo FROM tbl_tipo_solicitacao WHERE tipo_solicitacao = ".$campos["tipo_solicitacao"]." AND fabrica = $login_fabrica" ;
	$res_obrigatorio = pg_query($con, $sql_obrigatorio);
	
	$obrigatorio_anexo = pg_fetch_result($res_obrigatorio, 0, 0);
	$tem_anexo = false;

	if ($obrigatorio_anexo == "3_anexo") {

		if (empty($campos['anexo']) && !empty($_POST['anexo_chave'])) {
			$sql_anexos = "SELECT tdocs FROM tbl_tdocs WHERE fabrica = $login_fabrica AND hash_temp = '".$_POST['anexo_chave']."'";
			$res_anexos = pg_query($con, $sql_anexos);
			if (pg_num_rows($res_anexos) > 0) {
				$anexos_hash = pg_fetch_all($res_anexos);
			}
		}

		foreach ($campos['anexo'] as $key => $value) {
			if ($value != "null" && !empty($value)) {
				$tem_anexo = true;
				break;
			}
		}

		if ($tem_anexo === false && count($anexos_hash) > 0) {
			$tem_anexo = true;
		}

		if ($tem_anexo === false) {
			$msg .= 'Anexo é obrigatório.<br />'; 
		}
	}

	if ($msg)
		throw new Exception ($msg);
}

function gravarAnexosBoxUploader() {

	global $con, $login_fabrica, $hd_chamado_item, $hd_chamado;
	
	$anexo_chave = $_POST["anexo_chave"];
	$anexo_chave_hidden = $_POST["anexo_chave_hidden"];

	$hd_referencia_id = (empty($hd_chamado_item)) ? $hd_chamado : $hd_chamado_item;

	if ($anexo_chave != $hd_referencia_id || $anexo_chave_hidden != $hd_referencia_id) {

		$getTdocs = "SELECT * FROM tbl_tdocs 
					 WHERE fabrica  = $login_fabrica
					 AND contexto   = 'help desk'
					 AND hash_temp = '{$anexo_chave}'
					 AND situacao   = 'ativo'";

		$resTdocs = pg_query($con, $getTdocs);

		$anexos = pg_fetch_all($resTdocs);

		if (!empty($anexos)) {

			$update = "UPDATE tbl_tdocs 
					   SET
						  referencia_id = $hd_referencia_id,
						  hash_temp  = NULL
					   WHERE fabrica = $login_fabrica
					   AND contexto  = 'help desk'
					   AND situacao  = 'ativo'
					   AND hash_temp IN ('$anexo_chave','$anexo_chave_hidden')";

			$uptodate = pg_query($con, $update);

			if (pg_num_rows($uptodate) == 0) {
				$msg_erro["msg"][] = traduz("Erro ao gravar anexos");
			}
		}
	}

}

function retorna_anexos_inseridos() {
	global $con, $login_fabrica, $hd_chamado_item, $fabricaFileUploadOS;

	$anexo_chave 	  = $_POST["anexo_chave"];
	$anexos_inseridos = [];

    if (!empty($hd_chamado_item)){
        $cond_tdocs = "AND tbl_tdocs.referencia_id = {$hd_chamado_item}";
    }else{
        $cond_tdocs = "AND tbl_tdocs.hash_temp = '{$anexo_chave}'";
    }

	$sql = "SELECT obs
			FROM   tbl_tdocs
			WHERE  tbl_tdocs.fabrica = {$login_fabrica}
			AND    tbl_tdocs.situacao = 'ativo'
			{$cond_tdocs}";

	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {

		while ($dados = pg_fetch_object($res)) {

			$json_obs = json_decode($dados->obs, true);

			$anexos_inseridos[] = $json_obs[0]['typeId'];

		}

	}

	return $anexos_inseridos;
}