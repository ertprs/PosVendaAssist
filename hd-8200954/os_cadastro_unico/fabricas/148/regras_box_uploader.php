<?php

/*
	CONTEXTO => [
		TIPO => [EXTENSOES ACEITAS]
	]
*/

$arrTiposAceitos = [
	"os" => [
		"assinatura" => ["pdf"],
		"notafiscal" => ["pdf"],
		"peca" 	     => ["png","jpg","jpeg"],
		"produto"    => ["png","jpg","jpeg"],
		"display"    => ["png","jpg","jpeg"]
	]
];

/*
$valida_extensao_por_tipo = function ($nomeArquivo, $contexto, $anexoTipo) use($arrTiposAceitos) {

	$extensoesAceitas = $arrTiposAceitos[$contexto][$anexoTipo];

	if (count($extensoesAceitas) > 0) {

		$explodeFileName = explode('.', $nomeArquivo);
		$extension = end($explodeFileName);

		if (!in_array($extension, $extensoesAceitas)) {

			return false;

		}

	}

	return true;

};
*/