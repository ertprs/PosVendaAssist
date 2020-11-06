<?php
include_once __DIR__ . DIRECTORY_SEPARATOR . 'class/tdocs.class.php';
define("NF_BASE_DIR", dirname(__FILE__) . '/nf_devolucao');
define("NF_BASE_URL", dirname(preg_replace("&admin(_cliente)?/&", '', $_SERVER['PHP_SELF'])) ."/nf_devolucao");
/**
 * 15-08-2016
 * Refatorada para usar o TDocs como repositório de arquivos de anexo de
 * NF para LGR.
 */
function anexaNFDevolucao($arquivo, $extrato, $faturamento_codigo, $nf) {

	$tDocs = new TDocs($GLOBALS['con'], $GLOBALS['login_fabrica']);

	$nome_original = strtolower(basename($arquivo['name']));
	$ext = pathinfo($nome_original, PATHINFO_EXTENSION);

	if(!in_array($ext,array('jpg','pdf','png'))) {
		return "Tipo de imagem inválido para a nota $nota_fiscal1. Tipos aceitos: jpg  png, pdf<br>";
	}

	$tDocs->setContext('lgr');

	$subiu = $tDocs->uploadFileS3($arquivo, $faturamento_codigo, false);

	if (!$subiu) {
		return $tDocs->error;
	}

	$tdocs = key($tDocs->getDocumentsByRef($faturamento_codigo)->attachListInfo);

	if ($tDocs->error) {
		return $tDocs->error;
	}

	$nome_arquivo = $extrato."_".$faturamento_codigo . '.' . $ext;
	$tDocs->setDocumentFileName($tdocs, $nome_arquivo);

	if ($tDocs->error) {
		return $tDocs->error;
	}

	return null;
}

/**
 * Método anônimo para recuperar o endereço do arquivo anexo.
 * Procura primeiro no TDocs, se não encontra o arquivo no banco,
 * procura no sistema de arquivos.
 * Retorna NULL se não encontrou o arquivo.
 */
$NFDevolucao = function($extrato, $faturamento) use ($con, $login_fabrica) {
	$tDocs = new TDocs($con, $login_fabrica);
	$tDocs->setContext('lgr');
	if ($tDocs->getDocumentsByRef($faturamento)->hasAttachment) {
		return $tDocs->url;
	}

	return null;
};

