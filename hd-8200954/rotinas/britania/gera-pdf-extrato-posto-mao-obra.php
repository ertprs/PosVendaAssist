<?php

ini_set('default_socket_timeout', 2147483647);

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

$fabrica = 3;
$nome_programa = '/assist/admin/extrato_posto_mao_obra_novo_britania_pdf.php';

$sql = "SELECT tbl_relatorio_agendamento.relatorio_agendamento,
        tbl_relatorio_agendamento.fabrica,
        tbl_relatorio_agendamento.admin,
        tbl_relatorio_agendamento.programa,
        tbl_relatorio_agendamento.parametros
    FROM tbl_relatorio_agendamento
    JOIN tbl_admin USING(admin)
    WHERE tbl_relatorio_agendamento.executado IS NULL
    AND tbl_relatorio_agendamento.inicio_execucao IS NULL
    AND tbl_relatorio_agendamento.agendado  IS NOT FALSE
    AND tbl_relatorio_agendamento.programa = '$nome_programa'";

$res = pg_query($con, $sql);

while ($fetch = pg_fetch_assoc($res)) {
	$relatorio_agendamento = $fetch["relatorio_agendamento"];
	$admin = $fetch["admin"];
	$programa = $fetch["programa"];
	$parametros = $fetch["parametros"];
    $hash = sha1($parametros);
	$parametros .= "&admin=$admin&fabrica=$fabrica&hash=$hash";

	$sql_ini_exec = "UPDATE tbl_relatorio_agendamento SET inicio_execucao = CURRENT_TIMESTAMP WHERE relatorio_agendamento = $relatorio_agendamento";
	$res_ini_exec = pg_query($con, $sql_ini_exec);

    $url = "https://posvenda.telecontrol.com.br";

//	echo "ID: $relatorio_agendamento\n";

//	echo $url . $programa .  '?' . $parametros . "\n";
    
    $result = file_get_contents($url . $programa .  '?' . $parametros);

//	var_dump($result);

	$sql_exec = "UPDATE tbl_relatorio_agendamento SET executado = CURRENT_TIMESTAMP WHERE relatorio_agendamento = $relatorio_agendamento";
	$res_exec = pg_query($con, $sql_exec);
}

