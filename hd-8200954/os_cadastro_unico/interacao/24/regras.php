<?php
/*
$smtpEmail = array(
	"usuario" => "tc.sac.suggar@gmail.com",
	"senha"   => "tcsuggar"
);
 */
if ($areaAdmin === false) {
	$funcoes_fabrica = array(
		"desbloqueia_os",
		"libera_abertura_os_posto"
	);

	$interacao_envia_email_regiao = array(
		"default" => "posvenda1@suggar.com.br",
		"posvenda1@suggar.com.br" => array("SP", "RJ"),
		"posvenda3@suggar.com.br" => array("AM", "AC", "AL", "AP", "CE", "PA", "RN", "SC", "RS"),
		"posvenda4@suggar.com.br" => array("BA", "MA", "MS", "MT", "RO", "RR", "SE", "PE"),
		"posvenda6@suggar.com.br" => array("MG", "ES"),
		"posvenda7@suggar.com.br" => array("DF", "GO", "PB", "PI", "TO", "PR")
	);
}

function desbloqueia_os() {
	global $os, $con, $login_fabrica;

	$sql = "UPDATE tbl_os_campo_extra SET 
				os_bloqueada = FALSE
	   		WHERE os = {$os}
	        AND fabrica = {$login_fabrica}";
	$res = pg_query($con, $sql);

	if (strlen(pg_last_error()) > 0) {
		throw new Exception("Erro ao interagir na Ordem de Serviço");
	}
}

function libera_abertura_os_posto() {
	global $login_posto, $con, $login_fabrica, $os;

	$libera_posto = TRUE;

	$sql = "SELECT distinct tbl_os_interacao.os, tbl_os.posto, (select case when admin notnull and current_date > data + interval '3 days' then 'sim' else 'nao' end as bloqueia from tbl_os_interacao h where h.os = tbl_os.os order by data desc limit 1) as bloqueia_os
          FROM tbl_os_interacao
          JOIN tbl_os USING(os)
          WHERE tbl_os_interacao.fabrica= $login_fabrica
          AND tbl_os_interacao.admin IS NOT NULL
          AND tbl_os_interacao.interno is false
          AND tbl_os_interacao.exigir_resposta is true
          AND tbl_os.excluida is not true
          AND tbl_os_interacao.data > '2015-01-01 00:00:00'
					AND tbl_os_interacao.data < current_date - interval '3 days'
					AND tbl_os.fabrica = $login_fabrica
          AND tbl_os.data_fechamento IS NULL 
          AND tbl_os.posto = $login_posto
          AND tbl_os.os <> $os";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0) {
    	$dados = pg_fetch_all($res);
    	foreach ($dados as $key => $value) {
    		if ($value['bloqueia_os'] == 'sim') {
    			$libera_posto = FALSE;
    		}
    	}
    }

    if ($libera_posto) {
		$sql = "SELECT posto 
	            FROM tbl_posto_bloqueio
	            WHERE posto = {$login_posto}
	            AND fabrica = {$login_fabrica}
	            AND os IS TRUE
	            LIMIT 1";
	    $res = pg_query($con, $sql);

	    if (pg_num_rows($res) > 0) {
			$sql = "UPDATE tbl_posto_bloqueio SET 
						os = FALSE
			   		WHERE posto = {$login_posto}
			        AND fabrica = {$login_fabrica}";
			$res = pg_query($con, $sql);
	    }
    }
		
	if (strlen(pg_last_error()) > 0) {
		throw new Exception("Erro ao interagir na Ordem de Serviço");
	}
}
