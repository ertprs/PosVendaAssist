<?php

include __DIR__ . "/dbconfig.php";

global $login_fabrica, $dbhost, $dbport, $dbnome, $dbusuario, $dbsenha;

use Posvenda\CockpitPosto\Servicos\ServicoDeAgendamento;

$servicoDeAgendamento = new ServicoDeAgendamento($dbnome, $dbhost, $dbport, $dbusuario, $dbsenha);

$mesCalendario = $_POST['mes'];
$os = $_POST['os'];
$fabricaId = $_POST['fabrica_id'];


if(strlen(trim($mesCalendario))>0){
	// alterar para o mês que será enviado
	$primeiroDiaMes = date('Y-' . $mesCalendario . '-01', strtotime(date('Y-' . $mesCalendario . '-d')));
	$ultimoDiaMes = date('Y-' . $mesCalendario . '-t', strtotime(date('Y-' . $mesCalendario . '-d')));

	echo $servicoDeAgendamento->obtemAgendamentosPorFabrica($fabricaId, $primeiroDiaMes, $ultimoDiaMes);
}

if(strlen(trim($os))>0){
	echo $servicoDeAgendamento->obtemAgendamentosPorOS($fabricaId, $os);
}