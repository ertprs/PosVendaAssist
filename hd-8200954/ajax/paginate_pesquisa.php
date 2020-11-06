<?php
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include '../autentica_admin.php';
include '../funcoes.php';

$indexCol 	   = $_POST['order'][0]['column'];
$colunaOrdenar = $_POST['columns'][$indexCol]['data'];

$data = [];

if (!in_array($colunaOrdenar, ["unorderable"])) {

	$easyBuilderMirror = new \Mirrors\EasyBuilderMirror($login_fabrica, "questionario_avaliacao", $login_admin);

	$indexCol = $_POST['order'][0]['column'];

	$objRequest = (object) [
		"offset"      => $_POST['start'],
		"limit"       => $_POST['length'],
		"orderBy"  	  => $colunaOrdenar,
		"order"    	  => $_POST['order'][0]['dir'],
		"strPesquisa" => urlencode($_POST['search']['value'])
	];

	$retorno = $easyBuilderMirror->getAll($objRequest);

	$data = [];
	foreach ($retorno["campos"] as $key => $value) {

		$link  = "questionario_avaliacao.php?pesquisa=".$value["pesquisa"];

		$data[] = [
			"tbl_pesquisa-descricao" => $value["descricao"],
			"tbl_admin-nome_completo" => $value["nome_completo"],
			"tbl_pesquisa-data_input" => mostra_data_hora($value["data_input"]),
			"tbl_pesquisa-ativo" => ($value["ativo"]) ? '<img src="imagens/status_verde.png">' : '<img src="imagens/status_vermelho.png">',
			"unorderable" => "
				<a href='{$link}&copy=true' class='btn btn-info btn-sm' title='Clonar Registro'><i class='glyphicon glyphicon glyphicon glyphicon-copy'></i></a>
				<a href='{$link}' class='btn btn-primary btn-sm' title='Alterar Registro'><i class='glyphicon glyphicon-pencil'></i></a>
				<a href='{$link}&delete=true' class='btn btn-danger btn-sm' title='Excluir Registro'><i class='glyphicon glyphicon glyphicon-remove'></i></a>
			"
		];

	}

}

$response = array(
  "draw" => (int) $_POST['draw'],
  "iTotalRecords" => count($retorno),
  "iTotalDisplayRecords" => $retorno["totalRegistros"],
  "aaData" => $data
);


exit(json_encode($response));