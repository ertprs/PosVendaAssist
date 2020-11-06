<?php

$buscar_atendente = "buscarAtendente";

$regras = array(
	"posto|id" => array(
		"obrigatorio" => true
	),
	"responsavel_solicitacao" => array(
		"obrigatorio" => true
	),
	"tipo_solicitacao" => array(
		"obrigatorio" => true
	),
	"tipo_solicitacao" => array(
		"function" => 'valida_tipo_solicitacao'
	)
);

function clear_att_fname($str) {
	$str = strtolower(preg_replace('/[() -]+/', '_', $str));
	return preg_replace('/[^a-zA-Z0-9]+$/', '', $str);
}

$attCfg = array(
	'labels' => array('Anexar'),
	'obrigatorio' => array(0)
);

$fabrica_qtde_anexos = count($attCfg['labels']);
$GLOBALS['attCfg'] = $attCfg;

/**
 * Função que valida os campos da os de acordo com o array $regras
 */
function valida_campos() {
	global $msg_erro, $regras, $campos, $label, $regex;

	foreach ($regras as $campo => $array_regras) {
		if (preg_match("/\|/", $campo)) {
			list($key, $value) = explode("|", $campo);
			$input_valor = $campos[$key][$value];
			$campo_nome = "{$key}[{$value}]";
		} else {
			$input_valor = $campos[$campo];
			$campo_nome = "{$campo}";
		}
		foreach ($array_regras as $tipo_regra => $regra) {
			switch ($tipo_regra) {
				case 'obrigatorio':
					if (is_array($input_valor)) {
						if (count($input_valor) == 0 && $regra === true) {
							$msg_erro["msg"]["campo_obrigatorio"][] = " Preencha todos os campos obrigatórios ($campo_nome)";
							$msg_erro["campos"][]                 = "{$campo_nome}";
						}
					} else {
						if (!strlen($input_valor) && $regra === true) {
							$msg_erro["msg"]["campo_obrigatorio"][] = " Preencha todos os campos obrigatórios ($campo_nome)";
							$msg_erro["campos"][]                 = "{$campo_nome}";
						}
					}	
				break;

				case 'function':
					if (is_array($regra)) {
						foreach ($regra as $function) {
							try {
								call_user_func($function);
							} catch(Exception $e) {
								$msg_erro["msg"][] = $e->getMessage();
								$msg_erro["campos"][] = "{$campo_nome}";
							}
						}
					}
				break;
			}
		}
	}
}

function valida_os_posto(){
	global $con, $login_fabrica, $campos;

	$posto_id = $campos['posto']['id'];
	$posto_os = $campos['ordem_de_servico'];

	$naoValidaPosto = (in_array($login_fabrica, [198])) ? true : false;
    $andPosto       = ($naoValidaPosto) ? "" : "AND posto = {$posto_id}";

	$sql = "SELECT os
                FROM tbl_os
                WHERE fabrica = {$login_fabrica}
                {$andPosto}
                AND sua_os = '{$posto_os}'";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res) == 0){
        throw new Exception("OS não encontrada para o Posto Autorizado!");
    }
}

function valida_tipo_solicitacao(){
	global $con, $login_fabrica, $campos;

	$tipo_solicitacao = $campos['tipo_solicitacao'];

	if(!empty($tipo_solicitacao)) {
		$sql = "SELECT tipo_solicitacao
			FROM tbl_tipo_solicitacao
			WHERE fabrica = {$login_fabrica}
			AND tipo_solicitacao = $tipo_solicitacao";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) == 0){
			throw new Exception("Tipo Solicitação inválida");
		}
	}
}

/**
 * Função para mover os anexos do bucket temporario para o bucket da Ordem de Serviço
 */
function grava_anexo() {
	global $campos, $s3, $hd_chamado;

	$arquivos = array();
	$anexos   = $campos['anexo'];
	$attCfg = $GLOBALS['attCfg'];

	if (!empty($campos["anexo"])) {
		if (is_string($anexos)) {
			$ext = preg_replace("/.+\./", "", $anexos);

			$arquivos[] = array(
				"file_temp" => $anexos,
				"file_new"  => "{$hd_chamado}.{$ext}"
			);

		}

		if (is_array($anexos)) {
			foreach($anexos as $attFileName) {
				$renamed = preg_replace('/^[a-z0-9]{32,}/', $hd_chamado.'.', $attFileName);
				$arquivos[] = array(
					'file_temp' => $attFileName,
					'file_new'  => $renamed
				);
			}
		}
	}
	if (count($arquivos) > 0) {
		$s3->moveTempToBucket($arquivos, null, null, false);
	}
}
/**
 * Função para vincular o anexo no tdocs
 */
function grava_anexo_tdocs() {
	global $campos, $tDocs, $hd_chamado;

	if (!empty($campos['anexo'])) {
		foreach($campos['anexo'] as $posicao => $attFileName) {
			if(!$attFileName) { continue; }
			$files    = str_replace('\\', '', $attFileName);
			$fileData = json_decode($files, true);
			if (!count($fileData)) continue;

			$fileData['name'] = $hd_chamado.'_'.$posicao.'.'.pathinfo($fileData['name'], PATHINFO_EXTENSION);
			$tDocs->setContext('hdposto');
			$tDocs->setDocumentReference($fileData, $hd_chamado,'anexar',false);
		}
	}

}

/**
* Função para mover os anexos de interação do bucket temporario para o bucket da Ordem de Serviço
**/
function grava_anexo_interacao() {
	global $campos, $s3, $hd_chamado, $hd_chamado_item;

	$arquivos = array();

	if (!empty($campos["anexo"])) {
		$ext = preg_replace("/.+\./", "", $campos["anexo"]);

		$arquivos[] = array(
			"file_temp" => $campos["anexo"],
			"file_new"  => "{$hd_chamado}_{$hd_chamado_item}.{$ext}"
		);
	}

	if (count($arquivos) > 0) {
		$s3->moveTempToBucket($arquivos, null, null, false);
	}
}

/**
* Função para vincular o anexo interação no tdocs
**/
function grava_anexo_tdocs_interacao() {
	global $campos, $tDocs, $hd_chamado, $hd_chamado_item;

	if (!empty($campos['anexo'])) {
		$files    = str_replace('\\', '', $campos['anexo']);
		$fileData = json_decode($files, true);
		$fileData['name'] = $hd_chamado.'_'.$hd_chamado_item.'.'.pathinfo($fileData['name'], PATHINFO_EXTENSION);
		$tDocs->setDocumentReference($fileData, $hd_chamado_item,'anexar', true, 'hdpostoitem');
	}
}

function gravarAnexosBoxUploaderGeral() {

	global $con, $login_fabrica, $hd_chamado_item;
	
	$anexo_chave = $_POST["anexo_chave"];

	if ($anexo_chave != $hd_chamado_item) {

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
						  referencia_id = $hd_chamado_item,
						  hash_temp  = NULL
					   WHERE fabrica = $login_fabrica
					   AND contexto  = 'help desk'
					   AND situacao  = 'ativo'
					   AND hash_temp = '$anexo_chave'";

			$uptodate = pg_query($con, $update);

			if (pg_num_rows($uptodate) == 0) {
				$msg_erro["msg"][] = traduz("Erro ao gravar anexos");
			}
		}
	}

}

function retorna_anexos_inseridos_geral() {
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