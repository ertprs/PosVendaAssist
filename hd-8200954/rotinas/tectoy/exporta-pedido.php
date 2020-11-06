<?php
/**
 *
 * exporta-pedido-excel.php
 *
 * @author  Gabriel Silveira
 * @version 2012-01-13
 *
 */

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require_once dirname(__FILE__) . '/../funcoes.php';

	$msg_erro = array();
	$log = array();

	$vet['fabrica'] = 'tectoy';
	$vet['tipo']    = 'pedido';
	$vet['dest']    = 'helpdesk@telecontrol.com.br';
	$vet['log']     = 2;

	$vet2 = $vet;
	$vet2['log'] = 1;
	$fabrica  = "6" ;
	$arquivos = "/tmp/tectoy";

	system ("mkdir -m 777 $arquivos 2> /dev/null;" );
	system ("mkdir /home/tectoy/nao_bkp 2> /dev/null ; chmod 777 /home/tectoy/nao_bkp" );

	$sql = "SET DateStyle TO 'SQL,EUROPEAN'";
	$result = pg_query($con,$sql);

	###################################################
	#					     						  #
	# Verifica pedidos em garantia sem OS relacionada #
	#					     						  #
	#					  inicio					  #
	#					     						  #
	###################################################

		$sql1 = "SELECT 	tbl_pedido.pedido                              ,
							to_char (tbl_pedido.data,'DD/MM/YYYY') as data ,
							tbl_posto.nome                                 ,
							tbl_pedido.total

				FROM tbl_pedido

				JOIN tbl_posto ON tbl_pedido.posto        = tbl_posto.posto
				JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto
				and  tbl_posto_fabrica.fabrica            = $fabrica
				LEFT JOIN tbl_os_item on tbl_pedido.pedido = tbl_os_item.pedido

				WHERE tbl_pedido.fabrica                    = $fabrica
				and data                     > '2005-08-26 00:00:00'
				and tbl_os_item.pedido       is null
				AND tbl_pedido.tipo_pedido   = $fabrica
				AND NOT (tbl_pedido.posto    = 6359)
				AND NOT (tbl_pedido.status_pedido = 14) ";

		$result1 = pg_query($con,$sql1);

		if (strlen(pg_last_error($con))>0){
			$msg_erro[] =  "Erro sql1 -> ".pg_last_error($con);
		}

		if (pg_num_rows($result1)>0) {

			require dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

			$assunto = ' TECTOY - Pedido sem OS na Tectoy ';

		 	$mail = new PHPMailer();
			$mail->IsHTML(true);
			$mail->From = 'helpdesk@telecontrol.com.br';
			$mail->FromName = 'Telecontrol';
			$mail->AddAddress('helpdesk@telecontrol.com.br');
			$mail->Subject = $assunto;
			$mail->Body = "Existem pedidos sem ordem de serviço na Tectoy.<br/>Verificar <b>Urgente</b><br/>";

			if (!$mail->Send()) {
				echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
			}

		}

	###################################################
	#					     						  #
	# Verifica pedidos em garantia sem OS relacionada #
	#					     						  #
	#					    fim	 					  #
	#					     						  #
	###################################################

	##########################################################
	#														 #
	# Prepara exportação das OS relacionadas a estes pedidos #
	#														 #
	#					   inicio							 #
	#														 #
	##########################################################
	$sql2 = "DROP TABLE tectoy_exporta_os";
	$result2 = pg_query($con,$sql2);

	if (strlen(pg_last_error($con))>0){
		$msg_erro[] =  "Erro sql2 -> ".pg_last_error($con);
	}

	$sql3 = "CREATE TABLE tectoy_exporta_os (pedido int4)";
	$result3 = pg_query($con,$sql3);

	if (strlen(pg_last_error($con))>0){
		$msg_erro[] =  "Erro sql3 -> ".pg_last_error($con);
	}

	$sql4 = "CREATE INDEX tectoy_exporta_os_pedido ON tectoy_exporta_os(pedido)";
	$result4 = pg_query($con,$sql4);

	if (strlen(pg_last_error($con))>0){
		$msg_erro[] =  "Erro sql4 -> ".pg_last_error($con);
	}

	$sql5 = "SELECT TO_CHAR (current_timestamp,'MMDDHH24MI')";
	$result5 = pg_query($con,$sql5);

	if (strlen(pg_last_error($con))>0){
		$msg_erro[] =  "Erro sql5 -> ".pg_last_error($con);
	}

	$prefixo = pg_result($result5,0,0);

	$sql6 = "SELECT      tbl_posto.cnpj                                            ,
						tbl_pedido.pedido                                         ,
						''::char(1)                                 AS os         ,
						upper (substr (tbl_pedido.tipo_pedido::text,1,3)) AS tipo_pedido,
						tbl_pedido.tipo_pedido                      AS x_tipo     ,
						'GERAL'::char(5)                            AS tabela     ,
						CASE WHEN tbl_produto.referencia IS NULL THEN
							'000000000000'
						ELSE
							LPAD (replace (replace (trim (tbl_produto.referencia),'.',''),'-',''),12,'0')
						END                                         AS produto    ,
						tbl_condicao.codigo_condicao::char(3)       AS condicao
			INTO TEMP   tmppedido
			FROM        tbl_pedido
			JOIN        tbl_posto         USING (posto)
			JOIN        tbl_posto_fabrica    ON tbl_posto.posto           = tbl_posto_fabrica.posto
											AND tbl_posto_fabrica.fabrica = $fabrica
			JOIN        tbl_condicao      USING (condicao)
			LEFT JOIN   tbl_produto       USING (produto)
			WHERE       tbl_pedido.exportado  IS NULL
			AND         tbl_pedido.finalizado NOTNULL
			AND         tbl_pedido.fabrica   = $fabrica
			AND         NOT (tbl_posto.posto = 6359)
			and         ((tbl_pedido.tipo_pedido = 4 and tbl_pedido.controle_exportacao notnull)
			or (tbl_pedido.tipo_pedido = 112 and tbl_pedido.controle_exportacao notnull)
			OR tbl_pedido.tipo_pedido = 6 )
			AND NOT (status_pedido = 17)
			AND NOT (status_pedido = 14)
			";

	#takashi 06/10/07 coloquei or (tbl_pedido.tipo_pedido = 112 and tbl_pedido.controle_exportacao notnull)
	# para exportar pedidos de garantia antecipada
	#WHERE       (tbl_pedido.exportado  IS NULL OR tbl_pedido.exportado BETWEEN '2006-04-01' AND '2006-04-30 23:59:59')
	#takashi retirou 11/09/07		ORDER BY    tbl_pedido.pedido

	$result6 = pg_query($con,$sql6);

	if (strlen(pg_last_error($con))>0){

		$msg_erro[] =  "Erro sql6 -> ".pg_last_error($con);


	}

	$sql7 = "CREATE INDEX tmppedido_pedido ON tmppedido (pedido)";

	$result7 = pg_query($con,$sql7);

	if (strlen(pg_last_error($con))>0){

		$msg_erro[] =  "Erro sql7 -> ".pg_last_error($con);

	}

	$sql8 = "SELECT COUNT(*) FROM tmppedido";

	$result8 = pg_query($con,$sql8);

	if (strlen(pg_last_error($con))>0){

		$msg_erro[] =  "Erro sql8 -> ".pg_last_error($con);

	}

	$qtde = pg_result($result8,0,0);

	if ($qtde >0){


	#################################
	#								#
	#    GERA ARQUIVO DE PEDIDOS    #
	#								#
	#			inicio				#
	#								#
	#################################


			$sql9 = "SELECT * FROM tmppedido";
			$result9 = pg_query($con,$sql9);

			if (strlen(pg_last_error($con))>0){
				$msg_erro[] =  "Erro sql9 -> ".pg_last_error($con);
			}

			$f_pedido = fopen("$arquivos/$prefixo.ped", 'w');

			while ($row = pg_fetch_array($result9)) {

				$linha_pedido  = $row['cnpj']		.	"\t";
				$linha_pedido .= $row['pedido']		.	"\t";
				$linha_pedido .= $row['os']			.	"\t";
				$linha_pedido .= $row['tipo_pedido']	.	"\t";
				$linha_pedido .= $row['tabela']		.	"\t";
				$linha_pedido .= $row['produto']		.	"\t";
				$linha_pedido .= $row['condicao']	.	"\n";

				if ($row['x_tipo'] == 6){

					$sql10 = "INSERT INTO tectoy_exporta_os (pedido) VALUES ('" . $row['pedido'] . "')";

					$result10 = pg_query($con,$sql10);

					if (strlen(pg_last_error($con))>0){

						$msg_erro[] =  "Erro sql10 -> ".pg_last_error($con);

					}

					$log[] = "Pedido - " . $row['pedido'] . "\n";

				}

				fwrite($f_pedido, $linha_pedido);

			}
			fclose($f_pedido);

		#################################
		#								#
		#    GERA ARQUIVO DE PEDIDOS    #
		#								#
		#			  fim				#
		#								#
		#################################




		#################################
		#								#
		#    GERA ARQUIVO DE ITENS      #
		#            DOS PEDIDOS        #
		#								#
		#			  inicio			#
		#								#
		#################################
			$sql11 = "SELECT  tbl_pedido_item.pedido                                       AS pedido    ,
					replace (replace (trim (tbl_peca.referencia),'.',''),'-','') AS referencia,
					tbl_pedido_item.qtde                                                      ,
					tbl_pedido_item.preco,
					tbl_pedido_item.pedido_item
				INTO TEMP   tmppedido_item
				FROM        tbl_pedido_item
				JOIN        tbl_peca          USING (peca)
				JOIN        tmppedido ON tbl_pedido_item.pedido = tmppedido.pedido
				LEFT JOIN   tbl_tabela_item  ON tbl_tabela_item.tabela = 53
				AND tbl_tabela_item.peca   = tbl_pedido_item.peca
				ORDER BY    tbl_pedido_item.pedido";

			$result11 = pg_query($con,$sql11);

			if (strlen(pg_last_error($con))>0){

				$msg_erro[] =  "Erro sql11 -> ".pg_last_error($con);

			}

			$sql12 = "SELECT * FROM tmppedido_item";

			$result12 = pg_query($con,$sql12);

			if (strlen(pg_last_error($con))>0){

				$msg_erro[] =  "Erro sql12 -> ".pg_last_error($con);

			}

			$f_pedido_item = fopen("$arquivos/$prefixo.ite", 'w');
			while ($row_item = pg_fetch_array($result12)) {
				$linha_pedido_item = $row_item['pedido']		.	"\t";
				$linha_pedido_item .= $row_item['referencia']	.	"\t";
				$linha_pedido_item .= $row_item['qtde']		.	"\t";
				$linha_pedido_item .= $row_item['preco']		.	"\t";
				$linha_pedido_item .= $row_item['pedido_item']		.	"\n";

				fwrite($f_pedido_item, $linha_pedido_item);
				$log[] = " Item: ".$row_item['referencia']." - Qtde: ".$row_item['qtde']." - preço: ".$row_item['preco']."\n";
			}

			fclose($f_pedido_item);

		#################################
		#								#
		#    GERA ARQUIVO DE ITENS      #
		#            DOS PEDIDOS        #
		#								#
		#			  fim				#
		#								#
		#################################




		#################################
		#								#
		#       GERA ARQUIVO DE OS      #
		#								#
		#			  inicio			#
		#								#
		#################################

			$sql13 = "DROP TABLE IF EXISTS tmpos";
			$result13 = pg_query($con,$sql13);

			if (strlen(pg_last_error($con))>0){

				$msg_erro[] =  "Erro sql13 -> ".pg_last_error($con);

			}


			$sql14 = "SELECT      tbl_os.os                                                               ,
								trim(tbl_os.sua_os)                           AS sua_os                 ,
								trim(tbl_posto.cnpj)                          AS posto_cnpj             ,
								to_char(tbl_os.data_abertura,'YYYY-MM-DD')    AS data_abertura          ,
								to_char(tbl_os.data_digitacao,'YYYY-MM-DD')   AS data_digitacao         ,
								to_char(tbl_os.data_fechamento,'YYYY-MM-DD')  AS data_fechamento        ,
								trim(tbl_produto.referencia)                  AS referencia_produto     ,
								trim(tbl_os.serie)                            AS serie                  ,
								trim(tbl_produto.voltagem)                    AS voltagem               ,
								lpad(trim(tbl_os.nota_fiscal),6,'0')          AS nota_fiscal            ,
								to_char(tbl_os.data_nf,'YYYY-MM-DD')          AS data_nf                ,
								tbl_defeito_reclamado.defeito_reclamado       AS defeito_reclamado      ,
								tbl_defeito_constatado.defeito_constatado     AS defeito_constatado     ,
								tbl_causa_defeito.causa_defeito               AS causa_defeito          ,
								rpad(tbl_os.consumidor_nome,40,' ')           AS consumidor_nome        ,
								rpad(tbl_os.consumidor_fone,20,' ')           AS consumidor_fone
					INTO        TEMP tmpos
					FROM        tbl_os
					JOIN       (SELECT tbl_os_produto.os
								FROM   tbl_os_produto
								JOIN  (SELECT tbl_os_item.os_produto
										FROM tbl_os_item
										WHERE pedido IN (SELECT pedido FROM tectoy_exporta_os)
								) osi ON tbl_os_produto.os_produto = osi.os_produto
					) oss ON tbl_os.os = oss.os
					JOIN        tbl_os_extra                   ON tbl_os.os                       = tbl_os_extra.os
					JOIN        tbl_posto                      ON tbl_os.posto                    = tbl_posto.posto
					JOIN        tbl_produto                    ON tbl_os.produto                  = tbl_produto.produto
					LEFT JOIN   tbl_defeito_constatado         ON tbl_os.defeito_constatado       = tbl_defeito_constatado.defeito_constatado
					LEFT JOIN   tbl_defeito_reclamado          ON tbl_os.defeito_reclamado        = tbl_defeito_reclamado.defeito_reclamado
					LEFT JOIN   tbl_causa_defeito              ON tbl_os.causa_defeito            = tbl_causa_defeito.causa_defeito
					WHERE       tbl_os.fabrica     = $fabrica
					AND         NOT (tbl_os.posto = 6359)";

			#takashi 11/09/07 retirou 			ORDER BY    tbl_os.sua_os

			$result14 = pg_query($con,$sql14);
			if (strlen(pg_last_error($con))>0){

				$msg_erro[] =  "Erro sql14 -> ".pg_last_error($con);

			}

			$sql15 = "SELECT * FROM tmpos";
			$result15 = pg_query($con,$sql15);

			if (strlen(pg_last_error($con))>0){

				$msg_erro[] =  "Erro sql15 -> ".pg_last_error($con);

			}

			$f_os = fopen($arquivos."/$pefixo.osp",'w');

			// open (ARQ_OS,">","$arquivos/$prefixo.osp");

			while ($row_os = pg_fetch_array($result15)) {

				$linha_os  = $row_os['sua_os'] 				. "\t";
				$linha_os .= $row_os['posto_cnpj'] 			. "\t";
				$linha_os .= $row_os['data_abertura'] 		. "\t";
				$linha_os .= $row_os['data_digitacao'] 		. "\t";
				$linha_os .= $row_os['data_fechamento']		. "\t";
				$linha_os .= $row_os['referencia_produto']	. "\t";
				$linha_os .= $row_os['serie'] 				. "\t";
				$linha_os .= $row_os['voltagem'] 			. "\t";
				$linha_os .= $row_os['nota_fiscal'] 			. "\t";
				$linha_os .= $row_os['data_nf'] 				. "\t";
				$linha_os .= $row_os['defeito_reclamado']	. "\t";
				$linha_os .= $row_os['defeito_constatado']	. "\t";
				$linha_os .= $row_os['causa_defeito']		. "\t";
				$linha_os .= $row_os['consumidor_nome']		. "\t";
				$linha_os .= $row_os['consumidor_fone'] 		. "\n";

				fwrite($f_os, $linha_os);

			}

			fclose ($f_os);

		#################################
		#								#
		#       GERA ARQUIVO DE OS      #
		#								#
		#			  fim				#
		#								#
		#################################




		#################################
		#								#
		#        GERA ARQUIVO DE        #
		#          ITENS DA OS          #
		#								#
		#			  inicio			#
		#								#
		#################################

			$sql16 = "DROP TABLE IF EXISTS tmpos_item";
			$result16 = pg_query($con,$sql16);
			if (strlen(pg_last_error($con))>0){

				$msg_erro[] =  "Erro sql16 -> ".pg_last_error($con);

			}

			$sql17 = "SELECT      tbl_os.os                                                               ,
								trim(tbl_os.sua_os)                           AS sua_os                 ,
								trim(tbl_peca.referencia)                     AS referencia_peca        ,
								lpad(tbl_os_item.qtde::text,3,'0')            AS qtde                   ,
								tbl_defeito.defeito                           AS codigo_defeito         ,
								tbl_servico_realizado.servico_realizado       AS servico_realizado      ,
								tbl_os_item.pedido                            AS os_item_pedido
					INTO        temp tmpos_item
					FROM        tbl_os
					JOIN        tbl_os_extra                   ON tbl_os.os                       = tbl_os_extra.os
					JOIN        tbl_os_produto                 ON tbl_os.os                       = tbl_os_produto.os
					JOIN        tbl_os_item                    ON tbl_os_produto.os_produto       = tbl_os_item.os_produto
					JOIN        tbl_peca                       ON tbl_os_item.peca                = tbl_peca.peca
					LEFT JOIN   tbl_defeito                    ON tbl_os_item.defeito             = tbl_defeito.defeito
					LEFT JOIN   tbl_servico_realizado          ON tbl_os_item.servico_realizado   = tbl_servico_realizado.servico_realizado
					WHERE       tbl_os.fabrica = $fabrica
					AND         tbl_os_item.pedido IN (
							SELECT pedido
							FROM   tectoy_exporta_os
					)";
			#takashi 11/09/07 retirou			ORDER BY    tbl_os.sua_os

			$result17 = pg_query($con,$sql17);
			if (strlen(pg_last_error($con))>0){

				$msg_erro[] =  "Erro sql17 -> ".pg_last_error($con);

			}

			$sql18 = "SELECT * FROM tmpos_item";
			$result18 = pg_query($con,$sql18);
			if (strlen(pg_last_error($con))>0){

				$msg_erro[] =  "Erro sql18 -> ".pg_last_error($con);

			}

			$f_os_item = fopen($arquivos."/$prefixo.osi",'w');
			// open (ARQ_OS_ITEM  ,">","$arquivos/$prefixo.osi");
			while ($row_os_item = pg_fetch_array($result18)) {

				$linha_os_item  = $row_os_item['sua_os'] . "\t";
				$linha_os_item .= $row_os_item['referencia_peca'] . "\t";
				$linha_os_item .= $row_os_item['qtde'] . "\t";
				$linha_os_item .= $row_os_item['codigo_defeito'] . "\t";
				$linha_os_item .= $row_os_item['servico_realizado'] . "\t";
				$linha_os_item .= $row_os_item['os_item_pedido'] . "\n";

				fwrite($f_os_item, $linha_os_item);

			}

			fclose($f_os_item);

		#################################
		#								#
		#        GERA ARQUIVO DE        #
		#          ITENS DA OS          #
		#								#
		#			  fim 				#
		#								#
		#################################




		#################################
		#								#
		#        GERA ARQUIVO DE        #
		#      DEFEITOS RECLAMADOS      #
		#								#
		#			  inicio			#
		#								#
		#################################

			$sql19 = "SELECT      tbl_defeito_reclamado.defeito_reclamado,
								tbl_defeito_reclamado.descricao
					INTO TEMP   tmpdefeito_reclamado
					FROM        tbl_defeito_reclamado
					JOIN        tbl_linha USING (linha)
					WHERE       tbl_linha.fabrica = $fabrica";
			$result19 = pg_query($con,$sql19);
			if (strlen(pg_last_error($con))>0){

				$msg_erro[] =  "Erro sql19 -> ".pg_last_error($con);

			}

			$sql20 = "SELECT * FROM tmpdefeito_reclamado";
			$result20 = pg_query($con,$sql20);
			if (strlen(pg_last_error($con))>0){

				$msg_erro[] =  "Erro sql20 -> ".pg_last_error($con);

			}

			$f_defeito_reclamado = fopen ("$arquivos/defeito_reclamado-$prefixo.txt");

			while ($row_def_rec = pg_fetch_array($result20)) {

				$linha_def_rec = $row_def_rec['defeito_reclamado'] . "\t";
				$linha_def_rec .= $row_def_rec['descricao'] . "\n";

				fwrite($f_defeito_reclamado, $linha_def_rec);

			}

			fclose($f_defeito_reclamado);

		#################################
		#								#
		#        GERA ARQUIVO DE        #
		#      DEFEITOS RECLAMADOS      #
		#								#
		#			  fim   			#
		#								#
		#################################




		#################################
		#								#
		#        GERA ARQUIVO DE        #
		#      DEFEITOS CONSTATADOS     #
		#								#
		#			  inicio  			#
		#								#
		#################################

			$sql21 = "SELECT      tbl_defeito_constatado.defeito_constatado,
								tbl_defeito_constatado.descricao
					INTO TEMP   tmpdefeito_constatado
					FROM        tbl_defeito_constatado
					WHERE       tbl_defeito_constatado.fabrica = $fabrica";

			$result21 = pg_query($con,$sql21);

			if (strlen(pg_last_error($con))>0){

				$msg_erro[] =  "Erro sql21 -> ".pg_last_error($con);

			}

			$sql22 = "SELECT * FROM tmpdefeito_constatado";

			$result22 = pg_query($con,$sql22);

			if (strlen(pg_last_error($con))>0){

				$msg_erro[] =  "Erro sql22 -> ".pg_last_error($con);

			}

			$f_def_con =fopen($arquivos."/defeito_constatado-$prefixo.txt",'w');

				// open (ARQ_DEF_CON ,">","$arquivos/defeito_constatado-$prefixo.txt");
				while ($row_def_con = pg_fetch_array($result22)) {

					$linha_def_con = $row_def_con['defeito_constatado'] . "\t";
					$linha_def_con .= $row_def_con['descricao'] . "\n";

					fwrite($f_def_con, $linha_def_con);
				}

			fclose ($f_def_con);

		#################################
		#								#
		#        GERA ARQUIVO DE        #
		#      DEFEITOS CONSTATADOS     #
		#								#
		#			  fim   			#
		#								#
		#################################





		#################################
		#								#
		#        GERA ARQUIVO DE        #
		#       CAUSAS DE DEFEITO       #
		#								#
		#			  inicio   			#
		#								#
		#################################

			$sql23 = "SELECT      tbl_causa_defeito.causa_defeito,
								tbl_causa_defeito.descricao
					INTO TEMP   tmpcausa_defeito
					FROM        tbl_causa_defeito
					WHERE       tbl_causa_defeito.fabrica = $fabrica";
			$result23 = pg_query($con,$sql23);

			if (strlen(pg_last_error($con))>0){

				$msg_erro[] =  "Erro sql23 -> ".pg_last_error($con);

			}

			$sql24 = "SELECT * FROM tmpcausa_defeito";
			$result24 = pg_query($con,$sql24);

			if (strlen(pg_last_error($con))>0){

				$msg_erro[] =  "Erro sql24 -> ".pg_last_error($con);

			}

			fopen($f_cau_def, $arquivos."/causa_defeito-$prefixo.txt");

			while ($row_cau_def = pg_fetch_array($result24)) {

				$linhas_cau_def = $row_cau_def['causa_defeito'] . "\t";
				$linhas_cau_def .= $row_cau_def['descricao'] . "\n";

				fwrite($f_cau_def, $linhas_cau_def);

			}

			fclose ($f_cau_def);

		#################################
		#								#
		#        GERA ARQUIVO DE        #
		#       CAUSAS DE DEFEITO       #
		#								#
		#			  fim   			#
		#								#
		#################################




		#################################
		#								#
		#        GERA ARQUIVO DE        #
		#            DEFEITO            #
		#								#
		#			  inicio   			#
		#								#
		#################################

			$sql25 = "SELECT      tbl_defeito.defeito,
								tbl_defeito.descricao
					INTO TEMP   tmpdefeito
					FROM        tbl_defeito
					WHERE       tbl_defeito.fabrica = $fabrica";
			$result25 = pg_query($con,$sql25);

			if (strlen(pg_last_error($con))>0){

				$msg_erro[] =  "Erro sql25 -> ".pg_last_error($con);

			}

			$sql26 = "SELECT * FROM tmpdefeito";
			$result26 = pg_query($con,$sql26);

			if (strlen(pg_last_error($con))>0){

				$msg_erro[] =  "Erro sql26 -> ".pg_last_error($con);

			}


			fopen($f_def, "$arquivos/defeito-$prefixo.txt");

			while ($row_def = pg_fetch_array($result26)) {

				$linha_def = $row_def['defeito'] .  "\t";
				$linha_def .= $row_def['descricao'] . "\n";

				fwrite($f_def, $linha_def);

			}

			fclose ($f_def);

		#################################
		#								#
		#        GERA ARQUIVO DE        #
		#            DEFEITO            #
		#								#
		#			 fim      			#
		#								#
		#################################




		#################################
		#								#
		#        GERA ARQUIVO DE        #
		#       SERVIÇO REALIZADO       #
		#								#
		#			  inicio   			#
		#								#
		#################################

			$sql27 = "SELECT      tbl_servico_realizado.servico_realizado,
								tbl_servico_realizado.descricao
					INTO TEMP   tmpservico_realizado
					FROM        tbl_servico_realizado
					WHERE       tbl_servico_realizado.fabrica = $fabrica";

			$result27 = pg_query($con,$sql27);

			if (strlen(pg_last_error($con))>0){

				$msg_erro[] =  "Erro sql27 -> ".pg_last_error($con);

			}

			$sql28 = "SELECT * FROM tmpservico_realizado";

			$result28 = pg_query($con,$sql28);

			if (strlen(pg_last_error($con))>0){

				$msg_erro[] =  "Erro sql28 -> ".pg_last_error($con);

			}

			$fp_ser_rea = fopen("$arquivos/servico_realizado-$prefixo.txt", 'w');
			//open (ARQ_SER_REA ,">","$arquivos/servico_realizado-$prefixo.txt");
			while ($row_ser_rea = pg_fetch_array($result28)) {
				$linha_ser_rea = $row_ser_rea['servico_realizado'] . "\t";
				$linha_ser_rea .= $row_ser_rea['descricao'] . "\n";

				fwrite($fp_ser_rea, $linha_ser_rea);
			}
			fclose ($fp_ser_rea);

		#################################
		#								#
		#        GERA ARQUIVO DE        #
		#       SERVIÇO REALIZADO       #
		#								#
		#			  fim   			#
		#								#
		#################################




		#################################
		#								#
		#        ATUALIZA PEDIDOS       #
		#           EXPORTADOS          #
		#								#
		#			  inicio   			#
		#								#
		#################################

			$sql = "UPDATE tbl_pedido SET exportado = current_timestamp,
					status_pedido = (
						SELECT tbl_status_pedido.status_pedido
						FROM   tbl_status_pedido
						WHERE  tbl_status_pedido.descricao = 'Aguardando Faturamento'
					)
					WHERE tbl_pedido.pedido IN (
							SELECT pedido
							FROM   tmppedido
					)
					AND   tbl_pedido.exportado IS NULL
					AND NOT (status_pedido = 17)
					AND NOT (status_pedido = 14) ";
			$result = pg_query($con,$sql);
			if (strlen(pg_last_error($con))>0){

				$msg_erro[] =  "Erro ao atualizar pedidos exportados -> ".pg_last_error($con);

			}

		#################################
		#								#
		#        ATUALIZA PEDIDOS       #
		#           EXPORTADOS          #
		#								#
		#			  fim   			#
		#								#
		#################################
		$sqlos = "SELECT DISTINCT tbl_os.os,
                		tbl_os.data_abertura,
				tbl_posto.posto,
				tbl_posto.cnpj,
				tbl_posto.nome,
				tbl_defeito_reclamado.descricao as defeito_reclamado_descricao,
				tbl_defeito_constatado.descricao as defeito_constatado_descricao
                FROM tbl_os
                INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os AND
                                             tbl_os_produto.produto = tbl_os.produto

                INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto

                INNER JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_os.defeito_reclamado AND
                                                    tbl_defeito_reclamado.fabrica = tbl_os.fabrica

                INNER JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado AND
                                                     tbl_defeito_constatado.fabrica = tbl_os.fabrica

                INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = tbl_os.fabrica AND
                                                tbl_posto_fabrica.posto = tbl_os.posto

                INNER JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto

                WHERE tbl_os.fabrica = 6 AND
                      tbl_posto.posto <> 6359 AND
                      tbl_os.data_fechamento IS NULL AND
                      tbl_os_item.pedido IS NULL";
			$result28 = pg_query($con,$sqlos);

			if (strlen(pg_last_error($con))>0){

				$msg_erro[] =  "Erro sql28 -> ".pg_last_error($con);

			}

			$fp = fopen("$arquivos/os_abertas_sem_pedido-$prefixo.txt", 'w');
			while ($row = pg_fetch_array($result28)) {
				$linha  = $row['os'];
                $linha .= ",";
                $linha .= $row['data_abertura'];
                $linha .= ",";
                $linha .= $row['cnpj'];
                $linha .= ",";
                $linha .= $row['nome'];
                $linha .= ",";
                $linha .= $row['defeito_reclamado_descricao'];
                $linha .= ",";
                $linha .= $row['defeito_constatado_descricao'];
                $linha .= "\n";

				fwrite($fp, $linha);
			}
			fclose ($fp);

		#------------ Envia arquivos para pastas de BKP e FTP --------------
		system("cd $arquivos; zip -o exportacao-$prefixo.zip *$prefixo* > /dev/null");
		system("cp $arquivos/exportacao-$prefixo.zip /home/tectoy/nao_bkp/");
		system("mv $arquivos/exportacao-$prefixo.zip /home/tectoy/telecontrol-tectoy/");
		system("mv $arquivos/$prefixo.*              /home/tectoy/telecontrol-tectoy/");
		system ("/usr/bin/uuencode /home/tectoy/telecontrol-tectoy/exportacao-$prefixo.zip exportacao-$prefixo.zip | /usr/bin/mailsubj \"TECTOY - Pedidos e OS´s\" assistencia\@tectoy.com.br, pribeiro\@tectoy.com.br, martin\@tectoy.com.br, helpdesk\@telecontrol.com.br, fsantos\@tectoy.com.br");
	}

	if ($msg_erro) {
		$msg_erro = implode("\n", $msg_erro);

		Log::envia_email($vet, "TECTOY - Erros ao exportar pedido das OSs", $msg_erro);
	}

	if ($log){

		$log = implode("\n", $log);
		Log::envia_email($vet2, "TECTOY - LOG da exportação de pedido das OSs", $log);

	}

}catch (Exception $e) {

	echo $e->getMessage();

}?>
