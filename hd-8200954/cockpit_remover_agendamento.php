<?php

include __DIR__ . "/dbconfig.php";

global $login_fabrica, $dbhost, $dbport, $dbnome, $dbusuario, $dbsenha;

use Posvenda\CockpitPosto\Servicos\ServicoDeAgendamento;

$servicoDeAgendamento = new ServicoDeAgendamento($dbnome, $dbhost, $dbport, $dbusuario, $dbsenha);

echo $servicoDeAgendamento->cancelarAgendamento($_POST['remover_tecnico_agenda'], $_POST['fabricaId'], $_POST['os'], $_POST['motivo_cancelamento']);
