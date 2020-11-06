<?php

error_reporting(E_ALL);
#voltar no dia 01/04/2016
try{

	include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    /* Dados iniciais */
	$fabrica      = 1;
	$fabrica_nome = "Black & Decker";
	$log_posto    = array();
	$msg_erro     = array();
	$env 		  = "producao"; // test | producao
	$observacao   = "Posto com bloqueio por possuir extratos pendentes a mais de 60 dias";

   	$posto = $argv[1];
   	if(strlen($posto) > 0){
		$cond = " AND tbl_posto.posto = $posto";
	}
	/*
    * Log
    */
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro - Bloqueio de postos para abertura de OS - {$fabrica_nome}")); // Titulo
    if ($env == "producao" ) {

	    $logClass->adicionaEmail("fabiola.oliveira@bdk.com");
	    $logClass->adicionaEmail("marisa.silvana@telecontrol.com.br");
	    $logClass->adicionaEmail("projeto@sbdbrasil.com.br");


    } else {
        $logClass->adicionaEmail("guilherme.silva@telecontrol.com.br");
        $limit = " LIMIT 1";
    }


    /*
    * Cron Class
    */
    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    /*
	Seleciona os extratos e postos
    */
    $sql = "SELECT
		tbl_extrato.extrato,
		tbl_extrato.posto
		INTO temp tmp_black_extrato
		FROM tbl_extrato
		INNER JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto
		INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$fabrica}
		JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
		LEFT JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato
		LEFT JOIN tbl_extrato_status ON tbl_extrato_status.extrato = tbl_extrato.extrato
		WHERE tbl_extrato.fabrica = {$fabrica}
		AND (tbl_extrato.data_geracao + INTERVAL '60 DAYS') <= CURRENT_DATE
		AND tbl_extrato.aprovado NOTNULL
		AND tbl_posto_fabrica.credenciamento != 'DESCREDENCIADO'
		AND tbl_extrato_financeiro.data_envio ISNULL
		AND tbl_extrato_extra.baixado ISNULL
		AND tbl_extrato.extrato not in (select extrato from tbl_extrato_status where tbl_extrato_status.conferido notnull and fabrica = $fabrica)
		AND tbl_extrato.data_geracao >= '2015-01-01'
		$cond
		{$limit} ;

		SELECT * FROM tmp_black_extrato ;
	";

	$res = pg_query($con, $sql);

	if(strlen(pg_last_error($con)) > 0){

		$msg_erro[] = "Erro ao selecionar os postos com extratos não aprovados a mais de 60 dias. Erro (".pg_last_error($con).").";

	}else{

		if(pg_num_rows($res) > 0){

	        for($i = 0; $i < pg_num_rows($res); $i++){
				$posto   = pg_fetch_result($res, $i, 'posto');
				$extrato = pg_fetch_result($res, $i, 'extrato');
				$desb    = "";
				$admin   = "";

				$sqlS = "SELECT pendente, conferido, admin_conferiu FROM tbl_extrato_status WHERE extrato = {$extrato} AND fabrica = {$fabrica}";
				$resS = pg_query($con, $sqlS);

				if(strlen(pg_last_error($con)) > 0){

					$msg_erro[] = "Erro ao verificar se o extrato {$extrato} se encontra com Status. Erro (".pg_last_error($con).").";

				}else{
					$desbloqueio_automatico = "";
					for($j=0;$j < pg_num_rows($resS);$j++) {
						$pendente       = pg_fetch_result($resS, $j, "pendente");
						$conferido      = pg_fetch_result($resS, $j, "conferido");
						$admin_conferiu = pg_fetch_result($resS, $j, "admin_conferiu");

						if(strlen($conferido) > 0 && strlen($admin_conferiu) > 0 && $pendente == "t"){
							$sqlP = "SELECT
										desbloqueio,
										admin,
										resolvido
									FROM tbl_posto_bloqueio
									WHERE
										fabrica = {$fabrica}
										AND pedido_faturado IS FALSE
										AND posto = {$posto}
										AND tbl_posto_bloqueio.extrato = TRUE
									ORDER BY data_input DESC LIMIT 1";
							$resP = pg_query($con, $sqlP);

							if(pg_num_rows($resP) > 0){
								$desb        = pg_fetch_result($resP, 0, "desbloqueio");
								$admin       = pg_fetch_result($resP, 0, "admin");
								$resolvido   = pg_fetch_result($resP, 0, "resolvido");

								if($desb == "f" and empty($admin)){

									$sqlB = "INSERT INTO tbl_posto_bloqueio(fabrica, posto, observacao,desbloqueio,extrato) VALUES ($fabrica, $posto,'Desbloqueio automatico por não possuir extrato pendente',true,true)";
									$resB = pg_query($con,$sqlB);
									$desbloqueio_automatico = true;
									if(strlen(pg_last_error($con)) > 0){

										$msg_erro[] = "Erro ao inserir o posto {$posto} para ser bloqueado. Erro (".pg_last_error($con).").";
										/* Posto bloquado: $posto */

									}
								}
							}

						}
					}

					if($desbloqueio_automatico){
						continue;
					}

		            $sqlP = "SELECT
		            			desbloqueio,
								admin,
								resolvido
							FROM tbl_posto_bloqueio
							WHERE
							fabrica = {$fabrica}
							AND pedido_faturado IS FALSE
							AND posto = {$posto}
							AND tbl_posto_bloqueio.extrato = TRUE
							ORDER BY data_input DESC LIMIT 1";
		            $resP = pg_query($con, $sqlP);

		            if(strlen(pg_last_error($con)) > 0){

						$msg_erro[] = "Erro ao verificar se o posto está bloqueado - Posto {$posto}. Erro (".pg_last_error($con).").";

					}else{

			    		if(pg_num_rows($resP) > 0){
			    			$desb        = pg_fetch_result($resP, 0, "desbloqueio");
			    			$admin       = pg_fetch_result($resP, 0, "admin");
			                $resolvido   = pg_fetch_result($resP, 0, "resolvido");
			    		}

			            if(pg_num_rows($resP) == 0 || ($desb == "t" && (empty($admin) || !empty($resolvido)))){

			                $sqlB = "INSERT INTO tbl_posto_bloqueio(fabrica, posto, observacao,extrato) VALUES ($fabrica, $posto,'{$observacao}',true)";
			                $resB = pg_query($con,$sqlB);

			                if(strlen(pg_last_error($con)) > 0){

								$msg_erro[] = "Erro ao inserir o posto {$posto} para ser bloqueado. Erro (".pg_last_error($con).").";
								/* Posto bloquado: $posto */

							}
			            }
			        }
				}
		}

		$sql = "SELECT  posto,
			(SELECT  desbloqueio FROM tbl_posto_bloqueio B WHERE B.fabrica = $fabrica AND B.posto = A.posto AND B.pedido_faturado is false and observacao !~ 'tico, posto não possui OSs abertas a mais de 60 dias' and observacao !~ 'tico, posto finalizou todas as OSs' ORDER BY data_input DESC limit 1) AS desbloqueio,
			(SELECT  admin FROM tbl_posto_bloqueio B WHERE B.fabrica = $fabrica AND B.posto = A.posto AND B.pedido_faturado is false and observacao !~ 'tico, posto não possui OSs abertas a mais de 60 dias' and observacao !~ 'tico, posto finalizou todas as OSs' ORDER BY data_input DESC limit 1) AS admin

			FROM tbl_posto_bloqueio A
			WHERE A.fabrica = $fabrica
			AND A.pedido_faturado is false
			AND A.extrato = TRUE
			GROUP BY A.posto";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
			for($j = 0; $j < pg_num_rows($res); $j++){
				$posto = pg_fetch_result($res, $j, 'posto');
				$desbloqueio = pg_fetch_result($res, $j, 'desbloqueio');
				$admin  = pg_fetch_result($res, $j, 'admin');

				if($desbloqueio != 't' and empty($admin)){

					$sqlP = "SELECT
						tbl_extrato.extrato,
						tbl_extrato.posto
						FROM tbl_extrato
						INNER JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto
						INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$fabrica}
						JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
						LEFT JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato
						LEFT JOIN tbl_extrato_status ON tbl_extrato_status.extrato = tbl_extrato.extrato
						WHERE tbl_extrato.fabrica = {$fabrica}
						AND tbl_extrato.posto = {$posto}
						AND (tbl_extrato.data_geracao + INTERVAL '60 DAYS') <= CURRENT_DATE
						AND tbl_extrato.aprovado NOTNULL
						AND tbl_posto_fabrica.credenciamento != 'DESCREDENCIADO'
						AND tbl_extrato_financeiro.data_envio ISNULL
						AND tbl_extrato_extra.baixado ISNULL
						AND tbl_extrato.extrato not in (select extrato from tbl_extrato_status where tbl_extrato_status.conferido notnull and fabrica = $fabrica)
						AND tbl_extrato.data_geracao >= '2015-01-01' ";
					$resP = pg_query($con,$sqlP);

					if(pg_num_rows($resP) == 0){
						$sqlP = "SELECT observacao FROM tbl_posto_bloqueio WHERE fabrica = $fabrica AND tbl_posto_bloqueio.pedido_faturado is false AND tbl_posto_bloqueio.extrato = TRUE AND posto = $posto ORDER BY data_input DESC LIMIT 1";
						$resP = pg_query($con,$sqlP);
						if(pg_num_rows($resP) > 0){
							$observacao  = pg_result($resP,0,'observacao');
						}
						if($observacao !='Posto com bloqueio por possuir OSs abertas a mais de 6 meses') {
							$sqlT = "INSERT INTO tbl_posto_bloqueio(
								fabrica,
								posto,
								desbloqueio,
								observacao,
								extrato)VALUES(
									$fabrica,
									$posto,
									true,
									'Desbloqueio automatico por não possuir extrato pendente',
									true);";
							$resT = pg_query ($con,$sqlT);
						}
						}
				}
			}
		}
		}else{
			$sqlP = "SELECT
				desbloqueio,
				admin,
				resolvido
				FROM tbl_posto_bloqueio
				WHERE
				fabrica = {$fabrica}
				AND pedido_faturado IS FALSE
				AND posto = {$posto}
				AND tbl_posto_bloqueio.extrato = TRUE
				ORDER BY data_input DESC LIMIT 1";
			$resP = pg_query($con, $sqlP);

			if(pg_num_rows($resP) > 0){
				$desb        = pg_fetch_result($resP, 0, "desbloqueio");
				$admin       = pg_fetch_result($resP, 0, "admin");
				$resolvido   = pg_fetch_result($resP, 0, "resolvido");

				if($desb == "f" and empty($admin)){

					$sqlB = "INSERT INTO tbl_posto_bloqueio(fabrica, posto, observacao,desbloqueio,extrato) VALUES ($fabrica, $posto,'Desbloqueio automatico por não possuir extrato pendente',true, true)";
					$resB = pg_query($con,$sqlB);
					$desbloqueio_automatico = true;
					if(strlen(pg_last_error($con)) > 0){

						$msg_erro[] = "Erro ao inserir o posto {$posto} para ser bloqueado. Erro (".pg_last_error($con).").";
						/* Posto bloquado: $posto */

					}
				}
			}

		}
	}

	if(empty($posto) and empty($cond)) {
		$sql = "SELECT distinct tbl_extrato.posto
			FROM tbl_extrato
			INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto AND tbl_posto_fabrica.fabrica = {$fabrica}
			JOIN  tbl_posto_bloqueio on tbl_extrato.posto = tbl_posto_bloqueio.posto and tbl_posto_bloqueio.pedido_faturado is false and desbloqueio is false and tbl_posto_bloqueio.fabrica = $fabrica and tbl_posto_bloqueio.observacao ~ 'Posto com bloqueio por possuir extrat' and tbl_posto_bloqueio.data_input > current_timestamp - interval '90 days'
			WHERE tbl_extrato.fabrica = {$fabrica}
			AND (tbl_extrato.data_geracao + INTERVAL '30 DAYS') >= CURRENT_DATE
			AND tbl_posto_fabrica.credenciamento != 'DESCREDENCIADO'
			AND tbl_extrato.posto not in (select posto from tmp_black_extrato)
			AND tbl_posto_bloqueio.extrato = TRUE";
		$res = pg_query($con,$sql);
		for($i = 0; $i < pg_num_rows($res); $i++){
			$posto   = pg_fetch_result($res, $i, 'posto');
			system("php /www/assist/www/rotinas/blackedecker/bloqueia-abertura-os.php $posto",$ret);
		}
	}

    if(count($msg_erro) > 0){

    	$logClass->adicionaLog(implode("<br />", $msg_erro));
        $logClass->enviaEmails();

    }

    /*
    * Cron Término
    */
    $phpCron->termino();

} catch (Excpection $e) {
	echo $e->getMessage();
}

?>
