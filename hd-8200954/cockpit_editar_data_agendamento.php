<?php

include __DIR__ . "/dbconfig.php";

global $login_fabrica, $dbhost, $dbport, $dbnome, $dbusuario, $dbsenha;

use Posvenda\CockpitPosto\Servicos\ServicoDeAgendamento;

$servicoDeAgendamento = new ServicoDeAgendamento($dbnome, $dbhost, $dbport, $dbusuario, $dbsenha);

echo $servicoDeAgendamento->editarDataAgendamento(
	$_POST['tecnico_agenda'],
	$_POST['data_inicio'],
	$_POST['data_final']
);
