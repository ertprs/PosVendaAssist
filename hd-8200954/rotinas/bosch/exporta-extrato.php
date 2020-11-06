<?php
/**
 *
 * rotinas/bosch/exporta-extrato.php
 *
 * @author  Francisco Ambrozio
 * @version 2012.02.24
 *
 */

error_reporting(E_ALL ^ E_NOTICE);

try{

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require_once dirname(__FILE__) . '/../funcoes.php';

	$vet['fabrica'] = 'bosch';
	$vet['tipo']    = 'extrato';
	$vet['dest']    = ($_serverEnvironment == 'development') ? 'guilherme.silva@telecontrol.com.br' : 'helpdesk@telecontrol.com.br';
	$vet['log']     = 2;

	$fabrica = 20;
	$arquivos = '/tmp/'.$vet['fabrica'];

	if(!empty($argv[1])){
		
		$params = explode(",", $argv[1]);

		if($_serverEnvironment == 'development'){
			$params = array("guilherme.silva@telecontrol.com.br","matheus.knopp@telecontrol.com.br");
		}
		
	}

	if(!empty($argv[2])){
		$tipo_exportacao = $argv[2];
	}

	system("mkdir -p -m 777 $arquivos ; rm -f $arquivos/extrato.txt");
	$erro = 0 ;
	$query = pg_query($con, "BEGIN");

	$protocolo = 1;
	$sql = "SELECT MAX (protocolo::numeric) AS protocolo FROM tbl_extrato WHERE fabrica = $fabrica AND protocolo IS NOT NULL";
	$qry = pg_query($con, $sql);

	if (pg_num_rows($qry) == 1) {
		$protocolo+= pg_fetch_result($qry, 0, 'protocolo');
	}

	$cond_posto = ($_serverEnvironment == 'development') ? " = 6359 " : " <> 6359 ";

	$sql = "SELECT DISTINCT tbl_posto.posto
			FROM   tbl_extrato
			JOIN   tbl_extrato_extra USING(extrato)
			JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto AND tbl_posto_fabrica.fabrica = $fabrica
			JOIN   tbl_posto         ON tbl_posto.posto = tbl_posto_fabrica.posto
			WHERE  tbl_extrato.fabrica = $fabrica
			AND    tbl_extrato.aprovado        IS NOT NULL
			AND    tbl_extrato.liberado        IS NOT NULL
			AND    tbl_extrato_extra.exportado IS NULL
			AND    tbl_extrato.posto           {$cond_posto}
			AND    tbl_posto.pais              = 'BR'
			ORDER BY posto";
	$qry = pg_query($con, $sql);

	if($tipo_exportacao == "valor_pecas_avulsos"){
		$cond_tipo_exportacao = " round(SUM(oss.pecas)::numeric,2 )+round(ext.avulso::numeric,2) ";
	}else{
		$cond_tipo_exportacao = " round(SUM(oss.mao_de_obra+oss.pecas)::numeric,2 )+round(ext.avulso::numeric,2) ";
	}

	while ($fetch = pg_fetch_array($qry)) {
		$posto = $fetch['posto'];
		
		$sql = "UPDATE tbl_extrato SET protocolo = $protocolo,
				total  = round(total::numeric,2)
				FROM  tbl_extrato_extra
				WHERE tbl_extrato.fabrica = $fabrica
				AND   tbl_extrato.posto   = $posto
				AND   tbl_extrato.aprovado IS NOT NULL
				AND   tbl_extrato.liberado IS NOT NULL
				AND   tbl_extrato.extrato = tbl_extrato_extra.extrato
				AND   tbl_extrato_extra.exportado IS NULL";
		$qryUp = pg_query($con, $sql);
		$protocolo+= 1;

		// echo "$protocolo\n";
		
	}

	$sql = "SELECT tbl_extrato.extrato
			INTO TEMP tmp_extrato_exportado_bosch_update
			FROM tbl_extrato
			JOIN tbl_extrato_extra ON tbl_extrato.extrato = tbl_extrato_extra.extrato
			JOIN   tbl_posto         ON tbl_posto.posto = tbl_extrato.posto
			WHERE tbl_extrato.fabrica = $fabrica
			AND   tbl_extrato.aprovado IS NOT NULL
			AND   tbl_extrato.liberado IS NOT NULL
			AND   tbl_extrato_extra.exportado IS NULL
			AND   tbl_posto.pais              = 'BR';

	CREATE INDEX tmp_extrato_exportado_bosch_update_extrato ON tmp_extrato_exportado_bosch_update(extrato);

	SELECT tbl_os.os
		INTO TEMP tmp_extrato_os_exportado_bosch_update
		FROM tbl_os
		JOIN tbl_os_extra ON tbl_os.os                = tbl_os_extra.os AND tbl_os_extra.i_fabrica = $fabrica
		JOIN tmp_extrato_exportado_bosch_update ON tmp_extrato_exportado_bosch_update.extrato = tbl_os_extra.extrato
		WHERE tbl_os.fabrica = $fabrica
		AND tbl_os.excluida IS NOT TRUE;

	UPDATE tbl_os SET mao_de_obra = 0
		WHERE tbl_os.fabrica = $fabrica
		AND   tbl_os.mao_de_obra IS NULL
		AND   tbl_os.os IN (
			SELECT * FROM tmp_extrato_os_exportado_bosch_update
		);

	UPDATE tbl_os SET pecas = 0
		WHERE tbl_os.fabrica = $fabrica
		AND   tbl_os.pecas IS NULL
		AND   tbl_os.os IN (
			SELECT * FROM tmp_extrato_os_exportado_bosch_update
		);  ";

	$qry = pg_query($con, $sql);


	#implementar rotina para conferir:
	#1) Se o valor total da mao-de-obra das OS bate com o valor da mao-de-obra do extrato
	#2) se o valor das pecas idem...
	#3) se o valor da somatoria dos CFAs bate com o extrato
	#4) Conferencia do valor do imposto IVA no campo AVULSO
	# Verificado por Fabio - color o numero 2 para formatar corretamente para posterior comparação
	#Qualquer erro, enviar email para helpdesk e abortar rotina.
	#HD 193856 Foi corrigido o SQL
	$sql = "SELECT tbl_extrato.extrato
		INTO TEMP tmp_extrato_exportado_bosch_conf
		FROM tbl_extrato
		JOIN tbl_extrato_extra ON tbl_extrato.extrato = tbl_extrato_extra.extrato
		JOIN   tbl_posto       ON tbl_posto.posto     = tbl_extrato.posto
		WHERE tbl_extrato.fabrica = $fabrica
		AND   tbl_extrato.aprovado IS NOT NULL
		AND   tbl_extrato.liberado IS NOT NULL
		AND   tbl_extrato_extra.exportado IS NULL
		AND   tbl_posto.pais              = 'BR';

	CREATE INDEX tmp_extrato_exportado_bosch_conf_extrato ON tmp_extrato_exportado_bosch_conf(extrato);

	SELECT (SELECT tbl_extrato.protocolo FROM tbl_extrato WHERE tbl_extrato.extrato = tmp_extrato_exportado_bosch_conf.extrato) AS protocolo,
		tbl_os.os ,
		tbl_os.produto,
		tbl_os.mao_de_obra ,
		tbl_os.pecas
		INTO TEMP tmp_extrato_os_exportado_bosch_conf
		FROM tbl_os
		JOIN tbl_os_extra ON tbl_os.os                = tbl_os_extra.os AND tbl_os_extra.i_fabrica = $fabrica
		JOIN tmp_extrato_exportado_bosch_conf ON tmp_extrato_exportado_bosch_conf.extrato = tbl_os_extra.extrato
		WHERE tbl_os.fabrica = $fabrica
		AND tbl_os.excluida IS NOT TRUE
		;

	SELECT tmp_extrato_os_exportado_bosch_conf.protocolo                ,
		tbl_produto.origem                                             ,
		tbl_familia.bosch_cfa                                          ,
		sum(tmp_extrato_os_exportado_bosch_conf.mao_de_obra) AS mao_de_obra ,
		sum(tmp_extrato_os_exportado_bosch_conf.pecas)       AS pecas
		INTO TEMP tmp_os_exportado_bosch_conf
		FROM tmp_extrato_os_exportado_bosch_conf
		JOIN tbl_produto  ON tbl_produto.produto      = tmp_extrato_os_exportado_bosch_conf.produto
		JOIN tbl_linha ON tbl_linha.linha             = tbl_produto.linha
		JOIN tbl_familia  ON tbl_produto.familia      = tbl_familia.familia
		WHERE tbl_linha.fabrica     = $fabrica
		AND tbl_familia.fabrica = $fabrica
		GROUP BY tmp_extrato_os_exportado_bosch_conf.protocolo,
		tbl_produto.origem,
		tbl_familia.bosch_cfa;

	SELECT {$cond_tipo_exportacao} AS cfa_total_verificar,
		ext.protocolo AS protocolo_verificar,
		round(ext.total::numeric,2) AS total_extrato_verificar
		FROM (  SELECT protocolo,
			SUM (total)  AS total,
			SUM (avulso) AS avulso
			FROM tbl_extrato
			JOIN tmp_extrato_exportado_bosch_conf ON tmp_extrato_exportado_bosch_conf.extrato = tbl_extrato.extrato
			WHERE tbl_extrato.fabrica = $fabrica
			GROUP BY tbl_extrato.protocolo
		) ext
		JOIN   (
			SELECT * FROM tmp_os_exportado_bosch_conf
		) oss ON oss.protocolo = ext.protocolo
		GROUP BY ext.protocolo, ext.total, ext.avulso;
	";

	$res0 = pg_query($con, $sql);

	$extrato_problema="";
	$posto_problema  ="";

	$erro = 0;
	$primeira_vez = 0;

	if (pg_num_rows($res0) > 0) {
		while ($row = pg_fetch_array($res0) and $erro == 0) {
			$cfa_total_verificar     = $row['cfa_total_verificar'];
			$total_extrato_verificar = $row['total_extrato_verificar'];
			$protocolo_verificar	 = $row['protocolo_verificar'];

			if ($cfa_total_verificar <> $total_extrato_verificar) {
				if($primeira_vez <> 1){
					$primeira_vez = 1;
				}

				$vet['arquivo'] = 'extrato-erro.txt';

				$erro = $protocolo_verificar;
				$query_extrato = "SELECT extrato,posto FROM tbl_extrato WHERE fabrica=$fabrica AND protocolo='$protocolo_verificar'";
				Log::log2($vet, $query_extrato);
				$res_extrato = pg_query($con, $query_extrato);

				if (pg_num_rows($res_extrato) > 0) {
					$extrato_problema = pg_fetch_result($res_extrato, 0, 'extrato');
				  	$posto_problema = pg_fetch_result($res_extrato, 0, 'posto');
					$msg_erro = "Não foram exportados os extratos devido a um problema detectado com os valores do extrato. Favor verificar e corrigir. Extratos: $extrato_problema - Postos: $posto_problema cfa_total_verificar: $cfa_total_verificar total_extrato_verificar: $total_extrato_verificar";
					Log::log2($vet, $msg_erro);
				} else {
					Log::log2($vet, "não retornou nada na query");
				}
			}
		}

		if($primeira_vez == 1){
			$dest = $vet['dest'];
			system ("/usr/bin/uuencode $arquivos/extrato-erro.txt extrato-erro.txt | /usr/bin/mailsubj \"ERRO EM Posição de Extratos BOSCH\" $dest");
		}
	}

	if ($erro == 0) {
		# CONFORME HD: 19102 FORAM ALTERADOS OS CÓDIGOS DE ORIGEM DOS PRODUTOS 12/05/2008
		# HD 111271
		# HD 736669 - Alterado o SQL. Foram retiradas as subquery e criadas temporarias porque o SQL estava travando
		$sql = "SELECT tbl_extrato.extrato
				INTO TEMP tmp_extrato_exportado_bosch
				FROM tbl_extrato
				JOIN tbl_extrato_extra ON tbl_extrato.extrato = tbl_extrato_extra.extrato
				JOIN   tbl_posto       ON tbl_posto.posto     = tbl_extrato.posto
				WHERE tbl_extrato.fabrica = $fabrica
				AND   tbl_extrato.aprovado IS NOT NULL
				AND   tbl_extrato.liberado IS NOT NULL
				AND tbl_extrato_extra.exportado ISNULL
				AND   tbl_posto.pais              = 'BR';

		CREATE INDEX tmp_extrato_exportado_bosch_extrato ON tmp_extrato_exportado_bosch(extrato);

		SELECT (SELECT tbl_extrato.protocolo FROM tbl_extrato WHERE tbl_extrato.extrato = tmp_extrato_exportado_bosch.extrato) AS protocolo,
			tbl_os.os ,
			tbl_os.tipo_atendimento,
			tbl_os.produto,
			tbl_os.mao_de_obra ,
			tbl_os.pecas
			INTO TEMP tmp_extrato_os_exportado_bosch
			FROM tbl_os
			JOIN tbl_os_extra ON tbl_os.os                = tbl_os_extra.os AND tbl_os_extra.i_fabrica = $fabrica
			JOIN tmp_extrato_exportado_bosch ON tmp_extrato_exportado_bosch.extrato = tbl_os_extra.extrato
			WHERE tbl_os.fabrica = $fabrica
			AND tbl_os.excluida IS NOT TRUE;

		SELECT tmp_extrato_os_exportado_bosch.protocolo                ,
			tbl_produto.origem                                         ,
			case when e.bosch_cfa notnull then e.bosch_cfa else tbl_familia.bosch_cfa end as bosch_cfa,
			sum(tmp_extrato_os_exportado_bosch.mao_de_obra) AS mao_de_obra ,
			sum(tmp_extrato_os_exportado_bosch.pecas)       AS pecas
			INTO TEMP tmp_os_exportado_bosch
			FROM tmp_extrato_os_exportado_bosch
			JOIN tbl_produto  ON tbl_produto.produto      = tmp_extrato_os_exportado_bosch.produto
			JOIN tbl_linha ON tbl_linha.linha             = tbl_produto.linha
			JOIN tbl_familia  ON tbl_produto.familia      = tbl_familia.familia
			left JOIN (select bosch_cfa, os from tmp_extrato_os_exportado_bosch join tbl_os_produto using(os) join tbl_os_item using(os_produto)
			join tbl_peca using(peca) join tbl_familia on tbl_familia.familia = tbl_peca.familia_peca WHERE tipo_atendimento= 12) e ON tmp_extrato_os_exportado_bosch.os = e.os
			WHERE tbl_linha.fabrica     = $fabrica
			AND tbl_familia.fabrica = $fabrica
			GROUP BY tmp_extrato_os_exportado_bosch.protocolo,
			tbl_produto.origem,
			tbl_familia.bosch_cfa,
			e.bosch_cfa;

		SELECT (SELECT tbl_extrato.protocolo FROM tbl_extrato WHERE tbl_extrato.extrato = tmp_extrato_exportado_bosch.extrato) AS protocolo,
			tbl_extrato_lancamento.conta_garantia AS origem,
			tbl_extrato_lancamento.bosch_cfa,
			sum(tbl_extrato_lancamento.valor) AS mao_de_obra,
			0 AS pecas
			INTO TEMP tmp_avulso_exportado_bosch
			FROM tmp_extrato_exportado_bosch
			JOIN tbl_extrato_lancamento ON tmp_extrato_exportado_bosch.extrato = tbl_extrato_lancamento.extrato AND tbl_extrato_lancamento.fabrica = $fabrica
			GROUP BY protocolo,
			tbl_extrato_lancamento.conta_garantia,
			tbl_extrato_lancamento.bosch_cfa;

		SELECT  LPAD( TRIM (
			(
				SELECT tbl_posto_fabrica.codigo_posto
				FROM tbl_posto_fabrica
				WHERE tbl_posto_fabrica.fabrica = $fabrica
				AND   tbl_posto_fabrica.posto = (
					SELECT posto
					FROM    tbl_extrato
					JOIN tmp_extrato_exportado_bosch ON  tmp_extrato_exportado_bosch.extrato = tbl_extrato.extrato
					WHERE   ext.protocolo = tbl_extrato.protocolo
					AND     tbl_extrato.fabrica = $fabrica
					LIMIT 1)
				)
			),8,'0')   AS codigo_posto      ,
			TO_CHAR (CURRENT_DATE,'MMYYYY')                    AS data              ,
			CASE WHEN TO_CHAR (CURRENT_DATE,'DD')::numeric < 15 THEN
			RPAD (TRIM ('Pag Garant.1 quinz. ' || TO_CHAR (CURRENT_DATE,'MM') || '/' || TO_CHAR (CURRENT_DATE,'YY') ),25,' ')
			ELSE
			RPAD (TRIM ('Pag Garant.2 quinz. ' || TO_CHAR (CURRENT_DATE,'MM') || '/' || TO_CHAR (CURRENT_DATE,'YY') ),25,' ')
			END                                                                   AS texto    ,
			REPLACE (LPAD( TRUNC(ext.total::numeric,2)::text ,13,' ')::text,'.',',')          AS preco    ,
			LPAD( ( SELECT tbl_posto_fabrica.banco
			FROM tbl_posto_fabrica
			WHERE tbl_posto_fabrica.fabrica = $fabrica
			AND   tbl_posto_fabrica.posto = (SELECT posto
			FROM    tbl_extrato
			JOIN tmp_extrato_exportado_bosch ON  tmp_extrato_exportado_bosch.extrato = tbl_extrato.extrato
			WHERE   ext.protocolo = tbl_extrato.protocolo
			AND     tbl_extrato.fabrica = $fabrica
			LIMIT 1)
		)
		,3,' ')                                   AS banco    ,

		CASE WHEN upper(oss.origem) = 'NAC' THEN '1445010801'
		WHEN upper(oss.origem) = 'IMP' THEN '1445010001'
		WHEN upper(oss.origem) = 'USA' THEN '1445010003'
		WHEN upper(oss.origem) = 'ASI' THEN '1445010004'

		END  AS agencia  ,
		LPAD(TRIM(oss.bosch_cfa),6,' ')                                       AS cfa      ,
		REPLACE( LPAD ( TRUNC((oss.mao_de_obra+oss.pecas)::numeric,2)::text,13,' ')::text,'.',',')  AS cfa_total,
		ext.protocolo::char(15)                                               AS protocolo
		FROM    (	SELECT protocolo, SUM (total) AS total
		FROM tbl_extrato
		JOIN tmp_extrato_exportado_bosch ON  tmp_extrato_exportado_bosch.extrato = tbl_extrato.extrato
		WHERE tbl_extrato.fabrica = $fabrica
		GROUP BY tbl_extrato.protocolo
	) ext
	LEFT JOIN   (
		SELECT
		protocolo,
		origem,
		bosch_cfa,
		SUM(mao_de_obra) AS mao_de_obra,
		SUM(pecas) AS pecas

		FROM
		(
			SELECT * FROM tmp_os_exportado_bosch
			UNION
			SELECT * FROM tmp_avulso_exportado_bosch
		) AS todos_extratos
		GROUP BY protocolo, origem, bosch_cfa
	) oss ON oss.protocolo = ext.protocolo
	ORDER BY ext.protocolo;
		";

		$res0 = pg_query($con, $sql);
//echo $sql;

		if (pg_num_rows($res0) > 0) {
			$vet['log'] = 1;
			$vet['arquivo'] = 'extrato.txt';

			$protocolo_anterior = "" ;

			while ($row = pg_fetch_array($res0)) {

				$codigo_posto    = $row['codigo_posto'];
				$data            = $row['data'];
				$texto           = $row['texto'];
				$preco           = $row['preco'];
				$banco           = $row['banco'];
				$agencia         = $row['agencia'];
				$cfa             = $row['cfa'];
				$cfa_total       = $row['cfa_total'];
				$protocolo       = $row['protocolo'];

				// echo  "$codigo_posto \n";

				if (empty ($banco)) {
					$banco = "   ";
				}	

				if (empty ($agencia)) {
					$agencia = "          ";
				}
				if (empty ($cfa)) {
					$cfa = "      ";
				}

				if (empty($protocolo_anterior)) {
					$protocolo_anterior = $protocolo ;
				}

				$linha_log = "9085"        ;    # empresa
				$linha_log.= $codigo_posto ;
				$linha_log.= $data         ;
				$linha_log.= $texto        ;
				$linha_log.= $preco        ;
				$linha_log.= "2110"        ;   # divisao
				$linha_log.= "02"          ;	# cobranca
				$linha_log.= $banco        ;
				$linha_log.= $agencia      ;
				$linha_log.= $cfa          ;

				$chave_debito = 40;
				$cfa_total_x = $cfa_total;
				$cfa_total_x = str_replace(".", "", $cfa_total_x);
				$cfa_total_x = str_replace(",", ".", $cfa_total_x);
				if ($cfa_total_x < 0 ) {
					$cfa_total = $cfa_total_x * -1 ;
					$cfa_total = str_pad(number_format($cfa_total,2,',',''), 13, ' ', STR_PAD_LEFT);
					$chave_debito = 50;
				}

				if (empty($cfa_total)) {
					$linha_log.= "         0,00";
				} else {
					$linha_log.= $cfa_total    ;
				}

				$linha_log.= "11"			;	# Chave Credito
				$linha_log.= $chave_debito	;	# Chave Debito
				$linha_log.= "000000"		;	# Centro de Custo
				$linha_log.= "             " ; # Tipo Material
				$linha_log.= "    "	    ;   # vazio
				$linha_log.= "SA00"        ;	# Grupo
				$linha_log.= "          "  ;	# Nota
				$linha_log.= " "		    ;	# Correcao
				$linha_log.= "BR60"		;	# Organizacao
				$linha_log.= "EW"          ;	# Canal
				$linha_log.= "6845"		;	# Local de Negocio
				$linha_log.= "BRL"         ;	# Moeda

				log::log2($vet, $linha_log);

			}

		} else {
			$res = pg_query($con, "ROLLBACK");
		}

		if (file_exists("$arquivos/extrato.txt") and filesize("$arquivos/extrato.txt") > 0) {
 			
			$sql = "UPDATE tbl_extrato_extra SET exportado = CURRENT_TIMESTAMP
					FROM   tbl_extrato
					WHERE  tbl_extrato.extrato = tbl_extrato_extra.extrato
					AND    tbl_extrato.liberado        IS NOT NULL
					AND    tbl_extrato.aprovado        IS NOT NULL
					AND    tbl_extrato_extra.exportado IS NULL
					AND    tbl_extrato.fabrica       = $fabrica";
			$result = pg_query($con, $sql);
			$pg_erro.= pg_last_error($con);

			if (!empty($pg_erro)) {
			    $res = pg_query($con, "ROLLBACK");
			    throw new Exception($msg_erro);
			}

			$res = pg_query($con, "COMMIT");
			

			require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

			$assunto = utf8_decode('Posição de extrato');

			$mail = new PHPMailer();
			$mail->IsHTML(true);
			$mail->From = 'helpdesk@telecontrol.com.br';
			$mail->FromName = 'Telecontrol';
			
			$mail->AddAddress($vet['dest']);
			$mail->AddAddress('assistencia.ferramentas@br.bosch.com');


			if (!empty($params)) {
				foreach ($params as $email) {
					$mail->AddAddress($email);
				}
			}
			
			$mail->Subject = $assunto;
			$mail->Body = utf8_decode("MENSAGEM AUTOMÁTICA - NÃO RESPONDER A ESTE EMAIL.<br/><br/>$eAdd");
			$mail->AddAttachment("$arquivos/extrato.txt", "extrato.txt");
			$mail->Send();
			
			if (!$mail->Send()) {
				echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
			} else {
				date_default_timezone_set('America/Sao_Paulo');
				$data = date('Y-m-d-H-i');
				rename("$arquivos/extrato.txt", "$arquivos/extrato-$data.txt");
			}

			system("echo 'MENSAGEM AUTOMÁTICA - NÃO RESPONDER A ESTE EMAIL.' | mail -s '$assunto' -a $arquivos/extrato.txt assistencia.ferramentas@br.bosch.com");
		}

	} else {
		$query = pg_query($con, "ROLLBACK");
		exit(1);
	}


} catch (Exception $e) {

	echo $e->getMessage();

}
