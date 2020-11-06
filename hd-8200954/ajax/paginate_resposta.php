<?php
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include '../autentica_admin.php';
include '../funcoes.php';

$indexCol 	   = $_POST['order'][0]['column'];
$colunaOrdenar = $_POST['columns'][$indexCol]['data'];

$data = [];

if (!in_array($colunaOrdenar, ["unorderable"])) {

	$easyBuilderMirror = new \Mirrors\EasyBuilderMirror($login_fabrica, $_GET["categoria_pesquisa"], $login_admin);

	$indexCol = $_POST['order'][0]['column'];

	$objRequest = (object) [
		"offset"      => $_POST['start'],
		"limit"       => $_POST['length'],
		"orderBy"  	  => $colunaOrdenar,
		"order"    	  => $_POST['order'][0]['dir'],
		"strPesquisa" => urlencode($_POST['search']['value'])
	];

	$retorno = $easyBuilderMirror->getRespostas($objRequest, [
		"dataInicial" 		=> $_GET["data_inicial"],
		"dataFinal"   		=> $_GET["data_final"],
		"postoId"     		=> $_GET["posto"],
		"categoriaPesquisa" => $_GET["categoria_pesquisa"],
		"pesquisaId"        => $_GET["pesquisa"]
	]);

	$data = [];
	foreach ($retorno["campos"] as $key => $value) {

		if (!in_array($login_fabrica, [42])) {
			$alterarHidden = "style='display: none;'";
		}

		$descTipoPesquisa = $easyBuilderMirror->_tiposPesquisaFabrica[$login_fabrica][$value["categoria"]]["descricao"];

		$data[] = [
			"tbl_pesquisa-categoria" => $descTipoPesquisa,
			"tbl_pesquisa-descricao" => utf8_decode($value["descricao"]),
			"tbl_posto_fabrica-codigo_posto" => $value["codigo_posto"],
			"tbl_posto-nome" => $value["nome"],
			"tbl_resposta-campos_adicionais+pontuacaoTotal" => $value["pontos"],
			"tbl_admin-nome_completo" => $value["nome_completo"],
			"tbl_tecnico-nome" => $value["nome_tecnico"],
			"tbl_resposta-data_input" => mostra_data_hora($value["data_input"]),
			"unorderable" => "

					<a class='btn btn-info btn-sm btn-visualiza-resposta' data-pesquisa='{$value['pesquisa']}' data-posto='{$value['posto']}' data-resposta='{$value['resposta']}' title='Visualizar Resposta'><i class='glyphicon glyphicon-eye-open'></i></a> 

					<a {$alterarHidden} class='btn btn-primary btn-sm btn-altera-resposta' data-pesquisa='{$value['pesquisa']}' data-posto='{$value['posto']}' data-resposta='{$value['resposta']}' title='Alterar Resposta'><i class='glyphicon glyphicon-pencil'></i></a> 

					<a class='btn btn-danger btn-sm btn-remove-resposta' data-pesquisa='{$value['pesquisa']}' data-posto='{$value['posto']}' data-resposta='{$value['resposta']}' title='Excluir Resposta'><i class='glyphicon glyphicon-remove'></i></a> 

			"
		];

		if (!in_array($login_fabrica, [42])) {
			unset($data[$key]["tbl_resposta-campos_adicionais+pontuacaoTotal"]);
		}

	}

}

$response = array(
  "draw" => (int) $_POST['draw'],
  "iTotalRecords" => (int) $retorno["totalRegistros"],
  "iTotalDisplayRecords" => (int) count($data),
  "aaData" => array_map_recursive("utf8_encode", $data)
);


exit(json_encode($response));