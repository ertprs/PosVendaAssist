<?php

include __DIR__."/dbconfig.php";
include __DIR__ . "/funcoes.php";

global $login_fabrica, $dbhost, $dbport, $dbnome, $dbusuario, $dbsenha;

use Posvenda\CockpitPosto\Servicos\ServicoDeAgendamento;

$servicoDeAgendamento = new ServicoDeAgendamento($dbnome, $dbhost, $dbport, $dbusuario, $dbsenha);

if($_POST['tipo'] == 'multiplo'){
	$data_inicio = str_replace("'", "", fnc_formata_data_hora_pg($_POST['dados']['dataInicio']));
	$data_fim = str_replace("'", "", fnc_formata_data_hora_pg($_POST['dados']['dataFinal']));
	
	echo json_encode($servicoDeAgendamento->adicionarAgendamento(
	    $_POST['dados']['fabricaId'],
	    $_POST['dados']['usuario'],
	    $_POST['dados']['os'],
	    $data_inicio,
	    $data_inicio,
	    $data_fim,
	    $_POST['dados']['confirmado'] == 'true' ? date('Y-m-d H:i:s') : null,
	    $_POST['dados']['periodo'],
	    $_POST['dados']['tituloEvento']
	));
} else {
	$data_inicio = str_replace("'", "", fnc_formata_data_hora_pg($_POST['data_inicio']));
	$data_fim = str_replace("'", "", fnc_formata_data_hora_pg($_POST['data_termino']));

	echo json_encode($servicoDeAgendamento->adicionarAgendamento(
	    $_POST['fabrica_id'],
	    $_POST['tecnico_id'],
	    $_POST['os'],
	    $data_inicio,
	    $data_inicio,
	    $data_fim,
	    $_POST['confirmado'] == '1' ? date('Y-m-d H:i:s') : null,
	    $_POST['periodo'],
	    $_POST['descricao']
	));
}




