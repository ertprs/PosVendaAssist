<?php

include __DIR__ . "/dbconfig.php";
include __DIR__ . "/funcoes.php";

global $login_fabrica, $dbhost, $dbport, $dbnome, $dbusuario, $dbsenha;

use Posvenda\CockpitPosto\Servicos\ServicoDeAgendamento;

$servicoDeAgendamento = new ServicoDeAgendamento($dbnome, $dbhost, $dbport, $dbusuario, $dbsenha);

$data_inicio = str_replace("'", "", fnc_formata_data_hora_pg($_POST['data_inicio']));
$data_fim = str_replace("'", "", fnc_formata_data_hora_pg($_POST['data_termino']));


//$retorno['reagendamento'] = $servicoDeAgendamento->Reagendamento($_POST['os-agendamento'], 'OS', $data_inicio, $_POST['fabrica_id']);

//if($retorno['reagendamento']['http_code'] == 200){	


	if($_POST['cancelar_anterior'] == 'sim'){
		$cancelar_anterior = "sim";
	}else{
		$cancelar_anterior = "nao";
	}
	
	$retorno['editar'] = $servicoDeAgendamento->editarAgendamento(
		$_POST['confirmar-tecnico-agenda'],
	    $_POST['confirmar-tecnico-agendamento'],
	    $_POST['os-agendamento'],
	    $_POST['confirmar-confirmado-agendamento'] == '1' ? date('Y-m-d H:i:s') : null,
	    $data_inicio,
		$data_fim,
		$_POST['fabrica_id'],
		$cancelar_anterior,
		$_POST['confirmar-justificativa-agendamento']
	);

/*	$retorno['novo'] = $servicoDeAgendamento->adicionarAgendamento(
	    $_POST['fabrica_id'],
	    $_POST['confirmar-tecnico-agendamento'],
	    $_POST['os-agendamento'],
	    $data_inicio,
	    $data_inicio,
	    $data_fim,
	    $_POST['confirmar-confirmado-agendamento'] == '1' ? date('Y-m-d H:i:s') : null,
	    $_POST['periodo'],
	    $_POST['confirmar-descricao-agendamento']
	);*/
//}

echo json_encode($retorno);
