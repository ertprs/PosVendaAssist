<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
include dirname(__FILE__) . '/../../classes/Posvenda/Os.php';

$sql = "SELECT fn_callcenter_dias_aberto(tbl_hd_chamado.hd_chamado, tbl_hd_chamado.fabrica_responsavel)
		FROM   tbl_hd_chamado
		WHERE  tbl_hd_chamado.fabrica_responsavel <> 10
		AND    tbl_hd_chamado.fabrica_responsavel <> 0
		AND    (tbl_hd_chamado.status not in ('Resolvido','Cancelado','RESOLVIDO')
			OR (tbl_hd_chamado.status ~* 'Resolvido' AND data > current_timestamp - interval '1 month')
		)";
#calcula a qtdade de dias que o atendimento esta aberto
$res = pg_query($con, $sql);

$sql = "SELECT fn_callcenter_dias_interacao(tbl_hd_chamado.hd_chamado, tbl_hd_chamado.fabrica_responsavel)
		FROM   tbl_hd_chamado
		WHERE  tbl_hd_chamado.fabrica_responsavel <> 10
		AND    tbl_hd_chamado.fabrica_responsavel <> 0
		AND    (tbl_hd_chamado.status not IN ('Resolvido','Cancelado','RESOLVIDO')
			OR (tbl_hd_chamado.status ~* 'Resolvido' AND data > current_timestamp - interval '1 month')
		)";
#calcula a qtdade de dias que o atendente não entra em contato com o consumidor
$res = pg_query($con, $sql);
?>