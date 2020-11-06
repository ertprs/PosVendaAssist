<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";

include 'autentica_admin.php';
include 'funcoes.php';
include "monitora.php";

$layout_menu = "financeiro";
$title = traduz("RELATÓRIO CUSTO x PRODUTO");

$msg_erro = array();
$fabricas_km = array(15, 24, 30, 35, 50, 52, 72, 74, 85, 90, 91, 94, 114, 117, 125, 128, 129, 131, 142, 143, 145, 146, 148, 149, 152, 154, 157, 158,178);

if(isset($_POST['btn_pesquisar']) || isset($_POST["gerar_excel"])){
		if(!in_array($login_fabrica, array(178))){
			if(strlen($data_inicial) == 0){
				$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		    	$msg_erro["campos"][]   = "data_inicial";
			}

			if(empty($data_final)){
				$msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
		    	$msg_erro["campos"][]   = "data_final";
			}
		}

		if(count($msg_erro) == 0){
			if(!in_array($login_fabrica, array(178))){
				$d_ini = explode ("/", $data_inicial);//tira a barra
				$nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...
				$d = $d_ini[0];
				$m = $d_ini[1];
				$y = $d_ini[2];

				if(!checkdate($m,$d,$y)){
					$msg_erro["msg"]["obg"] = "Data Inicial inválida";
		    		$msg_erro["campos"][]   = "data_inicial";
				}

				$d_fim = explode ("/", $data_final);//tira a barra
				$nova_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...
				$d = $d_fim[0];
				$m = $d_fim[1];
				$y = $d_fim[2];

				if(!checkdate($m,$d,$y)){
					$msg_erro["msg"]["obg"] = "Data Final inválida";
		    		$msg_erro["campos"][]   = "data_final";
				}
			
				if(count($msg_erro) == 0){
					if($nova_data_final < $nova_data_inicial){
						$msg_erro["msg"]["obg"] = "A Data Final não pode ser menor que a Data Incial";
		    			$msg_erro["campos"][]   = "data_final";
					}
				}

				if(strlen($msg_erro)== 0){

					$nova_data_inicial = mktime(0,0,0,$d_ini[1],$d_ini[0],$d_ini[2]); // timestamp da data inicial
					$nova_data_final = mktime(0,0,0,$d_fim[1],$d_fim[0],$d_fim[2]); // timestamp da data final

					$cont = 0;

					while($nova_data_inicial <= $nova_data_final){//enquanto uma data for inferior a outra {
					  	$nova_data_inicial += 86400; // adicionando mais 1 dia (em segundos) na data inicial
					  	$cont++;
					}

					$inicio 	= new DateTime($d_ini[2]."-".$d_ini[1]."-".$d_ini[0]);
					$fim 		= new DateTime($d_fim[2]."-".$d_fim[1]."-".$d_fim[0]);
					$interval 	= date_diff($inicio, $fim);

					if((int)$interval > 90){
						$msg_erro["msg"]["obg"] = "O intervalo entre as datas não pode ser maior que 3 meses";
					}

				}
			}else{

				$mes = $_POST['mes'];
				$ano = $_POST['ano'];

				if ($mes == "" || $ano == "")  {
					$msg_erro["msg"][] = "Selecione o mês e o ano para a pesquisa";
					$msg_erro["campos"][] = "mes";
					$msg_erro["campos"][] = "ano";
				}
			
				$sql          = "SELECT fn_dias_mes('$ano-$mes-01',0)";
				$res3         = pg_query($con,$sql);
				$data_inicial = pg_fetch_result($res3,0,0);
				$x_data_inicial = $data_inicial;

				$sql          = "SELECT fn_dias_mes('$ano-$mes-01',1)";
				$res3         = pg_query($con,$sql);
				$data_final   = pg_fetch_result($res3,0,0);
				$x_data_final = $data_final;
			}
	}

	$limit = (isset($_POST["gerar_excel"])) ? "" : "LIMIT 500";

	if(count($msg_erro) == 0){

		if(!in_array($login_fabrica,array(178))){
			$data_inicial = str_replace (" " , "" , $data_inicial);
			$data_inicial = str_replace ("-" , "" , $data_inicial);
			$data_inicial = str_replace ("/" , "" , $data_inicial);
			$data_inicial = str_replace ("." , "" , $data_inicial);

			$data_final = str_replace (" " , "" , $data_final);
			$data_final = str_replace ("-" , "" , $data_final);
			$data_final = str_replace ("/" , "" , $data_final);
			$data_final = str_replace ("." , "" , $data_final);

			if (strlen ($data_inicial) == 6) $data_inicial = substr ($data_inicial,0,4) . "20" . substr ($data_inicial,4,2);
			if (strlen ($data_final)   == 6) $data_final   = substr ($data_final  ,0,4) . "20" . substr ($data_final  ,4,2);

			if (strlen ($data_inicial) > 0)  $data_inicial = substr ($data_inicial,0,2) . "/" . substr ($data_inicial,2,2) . "/" . substr ($data_inicial,4,4);
			if (strlen ($data_final)   > 0)  $data_final   = substr ($data_final,0,2)   . "/" . substr ($data_final,2,2)   . "/" . substr ($data_final,4,4);
		}

		if(strlen($data_inicial) > 0 AND strlen($data_final) > 0){

			$produto_referencia = trim($_POST['produto_referencia']);
			$produto_descricao  = trim($_POST['produto_descricao']) ;

			if(strlen($produto_referencia) > 0 && strlen($produto_descricao) > 0){ // HD 2003 TAKASHI
				$sql = "SELECT
							tbl_produto.produto
						FROM tbl_produto
						INNER JOIN tbl_familia USING(familia)
						WHERE
							tbl_familia.fabrica = $login_fabrica
							AND tbl_produto.referencia = '$produto_referencia'";
				$res = pg_query($con, $sql);
				if(pg_numrows($res)>0){
					$produto = pg_result($res,0,produto);
				}

			}

			$cond_4 = (strlen($produto) > 0) ? "AND tbl_produto.produto = {$produto}" : "";

			$cond_total = ($extrato_sem_peca != "t") ? " (tbl_os.mao_de_obra + tbl_os.pecas + coalesce(tbl_os.qtde_km_calculada,0) + coalesce(tbl_os.valores_adicionais,0)) " : " (tbl_os.mao_de_obra + coalesce(tbl_os.qtde_km_calculada,0) + coalesce(tbl_os.valores_adicionais,0)) ";

			if(in_array($login_fabrica,array(148,167,203))){ 
				$tipo_os = $_POST['tipo_os'];
				$familia = $_POST['familia'];

				if(isset($_POST['tipo_os'])) {
					$monta_tipo_os = implode(",", $tipo_os);

					$cond_tipo_os = " AND tbl_os.tipo_atendimento IN({$monta_tipo_os})";
				}

				if(isset($_POST['familia'])) {
					$monta_familia = implode(",", $familia);

					$cond_familia = " AND tbl_familia.familia IN({$monta_familia})";
				}

			}


			if(in_array($login_fabrica,array(167,203))){
				$codigo_posto = trim($_POST["codigo_posto"]);

				if(strlen($codigo_posto) > 0){
					$sql = "SELECT  posto
					FROM    tbl_posto_fabrica
					WHERE   fabrica      = $login_fabrica
					AND     codigo_posto = '$codigo_posto';";
					$res = pg_query ($con,$sql);

					if (pg_num_rows($res) > 0){
					
						$posto = trim(pg_fetch_result($res,0,posto));

						$cond_posto = " AND tbl_os.posto = $posto ";

					}
				}
			}

			if($login_fabrica == 164) {
				$cond_posto_interno = " JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto and tbl_os.fabrica = tbl_posto_fabrica.fabrica 
					Join tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto and posto_interno is not true" ; 
			}

			if(in_array($login_fabrica, [167, 203])){

				$campos_brother = " CASE
										WHEN (SELECT COUNT(1) FROM tbl_os_item JOIN tbl_os_produto OP USING(os_produto) JOIN tbl_servico_realizado USING(servico_realizado) WHERE OP.os = tbl_os.os AND (troca_de_peca IS TRUE OR troca_produto IS TRUE)) > 0 THEN
										'COM TROCA DE PEÇA'
										ELSE
										'SEM TROCA DE PEÇA'
										END AS lancamento_pecas,
										to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS recebimento,
										to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS retirada,
										CASE
										WHEN tbl_tipo_atendimento.fora_garantia IS TRUE THEN
										'Garantia Recusada'
										WHEN tbl_os_troca.os IS NOT NULL THEN
										'Trocado/Ressarcimento'
										ELSE
										'Encerrada'
										END AS situacao, ";

				$cond_troca = " LEFT JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os AND tbl_os_troca.fabric = $login_fabrica ";
			}

			if($replica_einhell) {
				$join_dc = " JOIN tbl_os_defeito_reclamado_constatado ON tbl_os.os = tbl_os_defeito_reclamado_constatado.os 
							LEFT JOIN tbl_defeito_constatado ON tbl_os_defeito_reclamado_constatado.defeito_constatado = tbl_defeito_constatado.defeito_constatado ";
			}else{
				$join_dc = " LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado ";
			}

			$sql = "SELECT  tbl_os.sua_os                                                 ,
					tbl_os.os                                                             ,
					tbl_os.serie                                                          ,
					tbl_os.mao_de_obra                                                    ,
					tbl_os.pecas                                                          ,
					tbl_os.solucao_os                                                     ,
					to_char(tbl_os.data_abertura,'MM/YYYY') AS mes                     ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura           ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento       ,
					$campos_brother
					tbl_os.revenda_nome                                                   ,
					tbl_os.consumidor_nome                                                ,
					tbl_os.tipo_os                                                        ,
					tbl_posto.nome                   				 AS posto_nome        ,
					tbl_tipo_atendimento.descricao                   AS tipo_descricao    ,
					tbl_familia.descricao                   		 AS familia_descricao ,
					tbl_linha.nome                   		 		 AS linha_descricao   ,
					tbl_produto.descricao                            AS produto_descricao ,
					tbl_produto.referencia                           AS produto_referencia,
					tbl_produto.referencia_fabrica                   AS produto_referencia_fabrica,
					tbl_defeito_constatado.codigo                    AS defeito_codigo    ,
					tbl_defeito_constatado.descricao                 AS defeito_descricao ,
					tbl_causa_defeito.codigo                         AS causa_codigo      ,
					tbl_causa_defeito.descricao                      AS causa_descricao   ,
					tbl_extrato.extrato                      		 AS extrato           ,
					tbl_extrato.avulso,
					$cond_total AS total,
					to_char (tbl_extrato_extra.exportado,'DD/MM/YYYY') AS data_exportado    ,
					to_char (tbl_extrato.data_geracao,'DD/MM/YYYY')    AS data_geracao      ,
					coalesce(tbl_os.qtde_km_calculada,0) as qtde_km_calculada             ,
					tbl_os.valores_adicionais                                             ,
					to_char (tbl_extrato_pagamento.data_pagamento,'DD/MM/YY') as data_pagamento,
					JSON_FIELD('motivo_reprova',tbl_laudo_tecnico_os.observacao) AS motivo_recusa
				FROM tbl_os
				LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
				LEFT JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
				INNER JOIN tbl_produto USING (produto)
				INNER JOIN tbl_os_extra USING (os)
				INNER JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
				INNER JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
				INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
				INNER JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_os_extra.extrato
				$join_dc
				LEFT JOIN tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato = tbl_os_extra.extrato
				LEFT JOIN tbl_causa_defeito ON tbl_causa_defeito.causa_defeito = tbl_os.causa_defeito
				LEFT JOIN tbl_laudo_tecnico_os ON tbl_os.os = tbl_laudo_tecnico_os.os
				$cond_posto_interno
				$cond_troca
				WHERE tbl_extrato.fabrica = $login_fabrica
				$cond_4
				$cond_tipo_os
				$cond_familia 
				$cond_posto";

			if(!in_array($login_fabrica,array(178))){

				if (strlen ($data_inicial) < 8) $data_inicial = date ("d/m/Y");
				$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

				if (strlen ($data_final) < 8) $data_final = date ("d/m/Y");
				$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);
			}

			if($login_fabrica <> 20){
				if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0){
					if($login_fabrica == 178){
						$sql .= " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial' AND '$x_data_final'";
					}else{
						$sql .= " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
					}
				}
			}else{
				if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0){
					$sql .= " AND tbl_extrato_extra.exportado BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
				}
			}

			$sql .= " ORDER BY tbl_produto.descricao {$limit}";
			$result = pg_query($con, $sql);
			$num = pg_num_rows($result);

			if(isset($_POST["gerar_excel"])){
			
				$file     = "xls/relatorio-extrato-pagamento-produto-{$login_fabrica}.xls";
		        $fileTemp = "/tmp/relatorio-extrato-pagamento-produto-{$login_fabrica}.xls" ;
		        $fp       = fopen($fileTemp,'w');

		       	$head = "
                <table border='1'>
                    <thead>
                        <tr bgcolor='#596D9B'>";

                        	if(in_array($login_fabrica, [167, 203])){
								$head .= "<th><font color='#FFFFFF'>Mês</font></th>";
								$head .= "<th><font color='#FFFFFF'>Revenda</font></th>";
								$head .= "<th><font color='#FFFFFF'>Posto</font></th>";
							}

                            if($login_fabrica != 20){
                            	$head .= "<th><font color='#FFFFFF'>OS</font></th>";
                            }

                            if(in_array($login_fabrica, [167, 203])){
								$head .= "<th><font color='#FFFFFF'>Cliente</font></th>";
							}

                            if ($login_fabrica == 148) {
                            	$head .= "<th><font color='#FFFFFF'>Tipo Atendimento</font></th>";
                            }

                            if ($login_fabrica == 171) {
                            	$head .= "<th><font color='#FFFFFF'>Referência Fábrica</font></th>";
                            }

                            if ($login_fabrica != 178) {
                            	$head .= "<th><font color='#FFFFFF'>Produto</font></th>";
	                        }else{
	                        	$head .= "<th><font color='#FFFFFF'>Ref. Produto</font></th>";
	                        	$head .= "<th><font color='#FFFFFF'>Desc. Produto</font></th>";
	                        }

                            if ($login_fabrica == 178) {
                            	$head .= "<th><font color='#FFFFFF'>Linha</font></th>";
                            	$head .= "<th><font color='#FFFFFF'>Família</font></th>";
                            }

                            if ($login_fabrica == 148) {
                            	$head .= "<th><font color='#FFFFFF'>Família</font></th>";
                            	$head .= "<th><font color='#FFFFFF'>Posto</font></th>";
                            }

                            if ($login_fabrica != 178) {
	                            $head .= "<th><font color='#FFFFFF'>Série</font></th>";
	                        }

                            if(in_array($login_fabrica, [167, 203])){
								$head .= "<th><font color='#FFFFFF'>Peças</font></th>";
								$head .= "<th><font color='#FFFFFF'>Recebimento</font></th>";
								$head .= "<th><font color='#FFFFFF'>>Retirada</font></th>";
								$head .= "<th><font color='#FFFFFF'>Situação</font></th>";
							}

                            if(!in_array($login_fabrica,array(20,167,203))){
								$head .= "<th><font color='#FFFFFF'>Defeito Constatado</font></th>";
                        	}
                        	if($login_fabrica == 20){
                        		$head .= "<th><font color='#FFFFFF'>Identificação</font></th>";
                        		$head .= "<th><font color='#FFFFFF'>Defeito</font></th>";
                        	}

                        	if(!in_array($login_fabrica,array(167,178,203))){
	                            $head .= "
	                            <th><font color='#FFFFFF'>Geração</font></th>
	                            <th><font color='#FFFFFF'>Data Pgto</font></th>";
	                        }

                            if ($login_fabrica == 148) {
                            	$head .= "<th><font color='#FFFFFF'>Extrato</font></th>";
                            }

                            $head .= "<th><font color='#FFFFFF'>M.O.</font></th>";

                            if(in_array($login_fabrica, [167, 203])){
								$head .= "<th><font color='#FFFFFF'>Data Pgto</font></th>";
								$head .= "<th><font color='#FFFFFF'>Motivo Recusa</font></th>";
							}

                            if ((!in_array($login_fabrica, array(157,158)) && $extrato_sem_peca != "t") && !in_array($login_fabrica,array(167,178,203))) {
                            	$head .= "<th><font color='#FFFFFF'>Peças</font></th>";
                        	}
                        	if($telecontrol_distrib || in_array($login_fabrica, $fabricas_km)) {
								$head .= "<th><font color='#FFFFFF'>KM</font></th>";
								$head .= "<th><font color='#FFFFFF'>Adicional</font></th>";
							}

							if ($login_fabrica == 178){
								$head .= "<th><font color='#FFFFFF'>Avulso</font></th>";
							}

							if($login_fabrica == 50){
								$head .= "<th><font color='#FFFFFF'>KM</font></th>";
							}

							if(!in_array($login_fabrica, [167, 203])){
	                            $head .= "<th><font color='#FFFFFF'>Total</font></th>";
	                        }

	                        $head .= "
                        </tr>
                    </thead>
                    <tbody>";
            	fwrite($fp, $head);

            	/* for */

            	for($i = 0; $i < $num; $i++){

					$sua_os             = trim(pg_fetch_result($result, $i, "sua_os"));
					$os                 = trim(pg_fetch_result($result, $i, "os"));
					$produto_referencia = trim(pg_fetch_result($result, $i, "produto_referencia"));
					$produto_referencia_fabrica = trim(pg_fetch_result($result, $i, "produto_referencia_fabrica"));
					$produto_descricao  = trim(pg_fetch_result($result, $i, "produto_descricao")) ;
					$serie              = trim(pg_fetch_result($result, $i, "serie"));
					$solucao_os         = trim(pg_fetch_result($result, $i, "solucao_os"));
					$defeito_codigo     = trim(pg_fetch_result($result, $i, "defeito_codigo"));
					$defeito_descricao  = trim(pg_fetch_result($result, $i, "defeito_descricao"));
					$causa_codigo       = trim(pg_fetch_result($result, $i, "causa_codigo"));
					$causa_descricao    = trim(pg_fetch_result($result, $i, "causa_descricao"));
					$mao_de_obra        = trim(pg_fetch_result($result, $i, "mao_de_obra"));
					$pecas              = trim(pg_fetch_result($result, $i, "pecas"));
					$total              = trim(pg_fetch_result($result, $i, "total"));
					$data_geracao       = trim(pg_fetch_result($result, $i, "data_geracao"));
					$data_exportado     = trim(pg_fetch_result($result, $i, "data_exportado"));
					$qtde_km_calculada  = trim(pg_fetch_result($result, $i, "qtde_km_calculada"));
					$valores_adicionais = trim(pg_fetch_result($result, $i, "valores_adicionais"));
					$data_pagamento     = trim(pg_fetch_result($result, $i, "data_pagamento"));
					$tipo_os          	= trim(pg_fetch_result($result,$i,'tipo_descricao'))    ;
					$familia         	= trim(pg_fetch_result($result,$i,'familia_descricao'))    ;
					$linha          	= trim(pg_fetch_result($result,$i,'linha_descricao'))    ;
					$posto_nome        	= trim(pg_fetch_result($result,$i,'posto_nome'))    ;
					$extrato        	= trim(pg_fetch_result($result,$i,'extrato'))    ;
					$mes        		 	 = trim(pg_result($result,$i,mes))    ;
					$data_abertura 		 	 = trim(pg_result($result,$i,data_abertura))    ;
					$data_fechamento         = trim(pg_result($result,$i,data_fechamento))    ;
					$consumidor_nome         = trim(pg_result($result,$i,consumidor_nome))    ;
					$revenda_nome         = trim(pg_result($result,$i,revenda_nome))    ;
					$motivo_recusa         = trim(pg_result($result,$i,motivo_recusa))    ;

					if ($login_fabrica == 178){
						$avulso = pg_fetch_result($result, $i, avulso);
						$avulso = number_format($avulso, 2, ",", ".");
					}

					if(in_array($login_fabrica, [167, 203])){
						$lancamento_pecas = trim(pg_result($result,$i,lancamento_pecas))    ;
						$situacao         = trim(pg_result($result,$i,situacao))    ;
					}

					$pecas              = number_format($pecas, 2, ",", ".");
					$mao_de_obra        = number_format($mao_de_obra, 2, ",", ".");
					$qtde_km_calculada  = number_format($qtde_km_calculada ,2, ",", ".");
					$valores_adicionais = number_format($valores_adicionais, 2, ",", ".");
					$total              = number_format($total, 2, ",", ".");

					if (strlen($valores_adicionais) == 0) {
						$valores_adicionais = '0,00';
					}

					if($login_fabrica == 74){

						$sql_pecas = "SELECT SUM(qtde * custo_peca) AS total FROM tbl_os_item WHERE os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE os = {$os})";
						$res_pecas = pg_query($con, $sql_pecas);

						$pecas = pg_fetch_result($res_pecas, 0, "total");

						$total = number_format($total + $pecas, 2, ",", ".");
						$pecas = number_format($pecas, 2, ",", ".");

					}

					$body = "<tr>";

						if(in_array($login_fabrica, [167, 203])){
							$body .= "<td align='left'>$mes</td>";
							$body .= "<td align='left' nowrap>$revenda_nome</td>";
							$body .= "<td align='left' nowrap>$posto_nome</td>";
						}

						if($login_fabrica != 20){
							$body .= "<td>$sua_os</td>";
						}

						if($login_fabrica == 148){
							$body .= "<td>$tipo_os</td>";
						}

						if($login_fabrica == 171){
							$body .= "<td>$produto_referencia_fabrica</td>";
						}

						if(!in_array($login_fabrica,array(167,178,203))){
							$body .= "<td align='left'>$produto_referencia - ".substr($produto_descricao,0,20)."</td>";
						}

						if($login_fabrica == 178){
							$body .= "<td align='left' title='$produto_descricao'>$produto_referencia</td>";
							$body .= "<td align='left' title='$produto_descricao'>$produto_descricao</td>";
						}

						if(in_array($login_fabrica, [167, 203])){
							$body .= "<td align='left'>$consumidor_nome</td>";
							$body .= "<td align='left' title='$produto_descricao'>$produto_referencia</td>";
						}

						if($login_fabrica == 148){
							$body .= "<td>$familia</td>";
							$body .= "<td>$posto_nome</td>";
						}

						if($login_fabrica == 178){
							$body .= "<td>$linha</td>";
							$body .= "<td>$familia</td>";
						}

						if($login_fabrica != 178){
							$body .= "<td>$serie</td>";
						}

						if(in_array($login_fabrica, [167, 203])){
							$body .= "<td nowrap>$lancamento_pecas</td>";
							$body .= "<td>$data_abertura</td>";
							$body .= "<td>$data_fechamento</td>";
							$body .= "<td>$situacao</td>";
						}

						if(!in_array($login_fabrica,array(20,167,203))){
							$body .= "<td align='left'>$defeito_codigo - $defeito_descricao</td>";
						}

						if($login_fabrica == 20){
							$xsolucao = "";
							if(strlen($solucao_os) > 0){
								$xsql = "SELECT descricao FROM tbl_servico_realizado WHERE servico_realizado = $solucao_os LIMIT 1";
								$xres = pg_query($con, $xsql);
								$xsolucao = trim(pg_fetch_result($xres, 0, "descricao"));
							}
							$body .= "<td align='left'>$xsolucao</td>";
							$body .= "<td align='left'>$causa_codigo- $causa_descricao</td>";
						}

						if(!in_array($login_fabrica,array(167,178,203))){
							$body .= "<td align='left'>";
								if($login_fabrica == 20){
									$body .= "$data_exportado";
								}else{
									$body .= "$data_geracao";
								}
							$body .= "</td>";

							$body .= "<td align='right'>$data_pagamento</td>";
						}

						

						if($login_fabrica == 148){
							$body .= "<td>$extrato</td>";
						}

						$body .= "<td align='right'>$real . $mao_de_obra</td>";

						if(!in_array($login_fabrica,array(164,167,178,203))){
							$body .= "<td align='right' width='75'>$data_pagamento</td>";
							$body .= "<td align='right' width='75'>$motivo_recusa</td>";
						}

						if((!in_array($login_fabrica, array(157,158)) && $extrato_sem_peca != "t") && !in_array($login_fabrica,array(167,178,203))) {
							$body .= "<td align='right'>$real . $pecas</td>";
						}
						if($telecontrol_distrib || in_array($login_fabrica, $fabricas_km)) {
							$body .= "<td align='right'>$real .  $qtde_km_calculada</td>";
							$body .= "<td align='right'>$real .  $valores_adicionais</td>";
						}

						if($login_fabrica == 50) {
							$body .= "<td align='right'>$real .  $qtde_km_calculada</td>";
						}

						if($login_fabrica == 178){
							$body .= "<td align='right'>R$ ".number_format($avulso, 2, ",", ".")."</td>";
						}

						if(!in_array($login_fabrica, [167, 203])){
							$body .= "<td align='right'>$real .  $total</td>";
						}


					$body .= "</tr>";

	                fwrite($fp, $body);

				}

            	/* for */

            	fwrite($fp, '</tbody></table>');
		        fclose($fp);

		        if(file_exists($fileTemp)){
		            system("mv $fileTemp $file");

		            if(file_exists($file)){
		                echo $file;
		            }
		        }

		        exit;

			}

		}

	}

}

include 'cabecalho_new.php';

$plugins = array(
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"multiselect"
);

include("plugin_loader.php");

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$nomemes = array(1=> "JANEIRO", "FEVEREIRO", "MARÇO", "ABRIL", "MAIO", "JUNHO", "JULHO", "AGOSTO", "SETEMBRO", "OUTUBRO", "NOVEMBRO", "DEZEMBRO");

?>

	<script type="text/javascript">

		$(function(){
			Shadowbox.init();

			$.datepickerLoad(Array("data_inicial", "data_final"));

			$("#data_inicial").mask("99/99/9999");
			$("#data_final").mask("99/99/9999");

			$("span[rel=lupa]").click(function () {
	            $.lupa($(this));
	        });

			$(document).on("click", "span[rel=lupa]", function () {
				$.lupa($(this));
			});

			<? if (in_array($login_fabrica,array(148,167,203))) { ?>

        		$("#tipo_os").multiselect({
        			selectedText: "# de # opções"
				});

				$("#familia").multiselect({
        			selectedText: "# de # opções"
				});
        <? } ?>

		});

		function retorna_produto (retorno) {

			$("#produto_referencia").val(retorno.referencia);
	        $("#produto_descricao").val(retorno.descricao);

	    }

	    function retorna_posto(retorno){
			$("#codigo_posto").val(retorno.codigo);
			$("#descricao_posto").val(retorno.nome);
		}

	</script>

	<?php
	if ((count($msg_erro["msg"]) > 0) ) {
	?>
	    <div class="alert alert-error">
	        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
	    </div>
	<?php
	}
	?>

	<div class="row">
		<b class="obrigatorio pull-right">* <?=traduz('Campos obrigatórios')?> </b>
	</div>

	<form name='frm_relatorio' method='post' action='<?=$PHP_SELF?>' class='tc_formulario'>

		<div class="titulo_tabela"><?=traduz('Paramêtros de Pesquisa')?></div>

		<br />

		<div class="row-fluid">

			<div class="span2"></div>

			<?php if(!in_array($login_fabrica,array(178))){ ?>
			<div class='span4'>
	            <div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='data_inicial'><?=traduz('Data Inicial')?></label>
	                <div class='controls controls-row'>
	                    <div class='span8'>
	                    	<h5 class='asteristico'>*</h5>
	                        <input type="text" name="data_inicial" id="data_inicial" size="12" class='span12' value= "<?=$data_inicial?>">
	                    </div>
	                </div>
	            </div>
	        </div>

	        <div class='span4'>
	            <div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : ""?>'>
	                <label class='control-label' for='data_final'><?=traduz('Data Final')?></label>
	                <div class='controls controls-row'>
	                    <div class='span8'>
	                    	<h5 class='asteristico'>*</h5>
	                        <input type="text" name="data_final" id="data_final" size="12" class='span12' value= "<?=$data_final?>">
	                    </div>
	                </div>
	            </div>
	        </div>
	    <?php } else{ ?>
	    	<div class='span4'>
				<div class='control-group <?=(in_array("mes", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='mes'>Mês</label>
					<div class='controls controls-row'>
						<h5 class='asteristico'>*</h5>
						<select name="mes" class='span7'>
							<option value=''></option>
							<?
							for ($i = 1 ; $i <= count($meses) ; $i++) {
								echo "<option value='$i'";
								if ($mes == $i) echo " selected";
								echo ">" . $meses[$i] . "</option>";
							}
							?>
						</select>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("ano", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='mes'>Ano</label>
					<div class='controls controls-row'>
						<h5 class='asteristico'>*</h5>
						<select name="ano" class="span7" >
							<option value=''></option>
							<?
							for ($i = date('Y'); $i >= 2019; $i--) {
								echo "<option value='$i'";
								if ($ano == $i) echo " selected";
								echo ">$i</option>";
							}
							?>
						</select>
					</div>
				</div>
			</div>
	    <?php } ?>
	    	<div class="span2"></div>
	    </div>
	    <?php if(in_array($login_fabrica,array(167,203))){ ?>
	    <div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='codigo_posto'><?=traduz('Cod. Posto')?></label>
					<div class='controls controls-row'>
						<div class='span8 input-append'>
							<input type="text" id="codigo_posto" name="codigo_posto" class='span12' maxlength="20" value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='descricao_posto'>
						<?=traduz('Nome Posto')?>
					</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" id="descricao_posto" name="posto_nome" class='span12' value="<? echo $posto_nome; ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		 <?php } ?>
	    <?php if(in_array($login_fabrica,array(148,167,203))){ ?>

	    <div class='row-fluid'>

	        <div class='span2'></div>

	        <div class='span4'>
	            <div class='control-group'>
	                <label class='control-label'><?=traduz('Tipo de Atendimento')?></label>
	                <div class='controls controls-row'>
	                    <div class='span10'>

                            <select name='tipo_os[]' id='tipo_os' multiple="multiple">

								<?
			                    $sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica";
								$res = pg_query($con,$sql);

			                    for($y=0;pg_numrows($res)>$y;$y++){
			                        $tipo_atendimento = pg_result($res,$y,tipo_atendimento);
			                        $descricao_atendimento = pg_result($res,$y,descricao);

			                        if (isset($_POST['tipo_os'])) {
			                        	$seleted = (in_array($tipo_atendimento, $_POST['tipo_os'])) ? 'selected' : '';
			                        }
			                    ?>
			                                <option value="<?= $tipo_atendimento ?>" <?=$seleted;?>>

			                                <?= $descricao_atendimento ?>

			                                </option>
								<?php
			                    }
								?>

                        	</select>
	                    </div>
	                </div>
	            </div>
	        </div>
	        <div class='span4'>
	            <div class='control-group'>
	                <label class='control-label' for='produto_descricao'><?=traduz('Família')?></label>
	                <div class='controls controls-row'>
	                    <div class='span11'>
	                    <select name='familia[]' id='familia' multiple="multiple">

								<?
			                    $sql = "SELECT * FROM tbl_familia WHERE fabrica = $login_fabrica";

								$res = pg_query($con,$sql);

			                    for($y=0;pg_numrows($res)>$y;$y++){
			                        $familia = pg_result($res,$y,familia);
			                        $descricao_familia = pg_result($res,$y,descricao);

			                  		if (isset($_POST['familia'])) {
			                        	$seleted = (in_array($familia, $_POST['familia'])) ? 'selected' : '';
			                        }

			                    ?>
			                                <option value="<?= $familia ?>" <?=$seleted;?>>

			                                <?= $descricao_familia ?>

			                                </option>
								<?php
			                    }
								?>

                        	</select>
	                    </div>
	                </div>
	            </div>
	        </div>
	        <div class='span2'></div>
	    </div>


	    <?php } ?>

	    <?php if($login_fabrica == 20){ ?>

	    <div class='row-fluid'>

	        <div class='span2'></div>

	        <div class='span3'>
	            <div class='control-group'>
	                <label class='control-label' for='produto_referencia'><?=traduz('Ref. Produto')?></label>
	                <div class='controls controls-row'>
	                    <div class='span10 input-append'>
	                        <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<?php echo $produto_referencia ?>" >
	                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
	                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
	                    </div>
	                </div>
	            </div>
	        </div>
	        <div class='span5'>
	            <div class='control-group'>
	                <label class='control-label' for='produto_descricao'><?=traduz('Descrição Produto')?></label>
	                <div class='controls controls-row'>
	                    <div class='span11 input-append'>
	                        <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
	                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
	                        <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
	                    </div>
	                </div>
	            </div>
	        </div>

	        <div class='span2'></div>

	    </div>


	    <?php } ?>

	    <p>
			<br/>
	    	<input type="submit" value='<?=traduz("Pesquisar")?>' class="btn">
	    	<input type='hidden' id="btn_click" name='btn_pesquisar' value="pesquisar" />
		</p>

		<br />

	</form>

</div>

<?php
if(count($msg_erro) == 0 && isset($_POST["btn_pesquisar"])){

	if($num > 0){

		if($num >= 500){
			?>

			<div id='registro_max'>
            	<h6><?=traduz('Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.')?></h6>
        	</div>

			<?php
		}

		echo "<table class='table table-bordered table-striped' id='relatorio' style='width: 1200px !important; margin: 0 auto;'>";

			echo "<thead>";

				echo "<tr class='titulo_coluna'>";

					if(in_array($login_fabrica, [167, 203])){
						echo "<td >".traduz("Mês")."</td>";
						echo "<td >".traduz("Revenda")."</td>";
						echo "<td >".traduz("Posto")."</td>";
					}

					if($login_fabrica != 20){
						echo "<td >OS</td>";
					}

					if(in_array($login_fabrica, [167, 203])){
						echo "<td >".traduz("Cliente")."</td>";
					}

					if ($login_fabrica == 148) {
						echo "<td >".traduz("Tipo Atendimento")."</td>";
					}
					
					if ($login_fabrica == 171) {
						echo "<td >".traduz("Referência Fábrica")."</td>";
					}
					
					if ($login_fabrica != 178) {
						echo "<td width='200px'>".traduz("Produto")."</td>";
					} else {
						echo "<td >Ref. Produto</td>";
						echo "<td >Desc. Produto</td>";
					}

					if ($login_fabrica == 148) {
						echo "<td >".traduz("Família")."</td>";
						echo "<td >".traduz("Posto")."</td>";
					}

					if ($login_fabrica == 178) {
						echo "<td >Linha</td>";
						echo "<td >Família</td>";
					} else {
						echo "<td >".traduz("Série")."</td>";
					}

					if(in_array($login_fabrica, [167, 203])){
						echo "<td >".traduz("Peças")."</td>";
						echo "<td >".traduz("Recebimento")."</td>";
						echo "<td >".traduz("Retirada")."</td>";
						echo "<td >".traduz("Situação")."</td>";
					}

					if(!in_array($login_fabrica,array(20,167,203))){
						echo "<td width='200' nowrap>".traduz("Defeito Constatado")."</td>";
					}

					if($login_fabrica == 20){
						echo "<td >".traduz("Identificação")."</td>";
						echo "<td >".traduz("Defeito")."</td>";
					}

					if(!in_array($login_fabrica, array(167,178,203))){
						echo "<td>".traduz("Geração")."</td>";
						echo "<td width='65'>".traduz("Data Pgto")."</td>";
					}

					if($login_fabrica == 148) {
						echo "<td>".traduz("Extrato")."</td>";
					}

					echo "<td>".traduz("M.O")."</td>";

					if(in_array($login_fabrica, [167, 203])){
						echo "<td>".traduz("Data Pgto")."</td>";
						echo "<td>".traduz("Motivo Recusa")."</td>";
					}

					if ((!in_array($login_fabrica, array(157,158)) && $extrato_sem_peca != "t") && !in_array($login_fabrica, array(167,178,203))) {
						echo "<td>".traduz("Peças")."</td>";
					}

					if($telecontrol_distrib || in_array($login_fabrica, $fabricas_km)) {
						echo "<td>KM</td>";
						echo "<td>".traduz("Adicional")."</td>";
					}

					if($login_fabrica == 50) {
						echo "<td>KM</td>";
					}

					if ($login_fabrica == 178){
						echo "<td>Avulso</td>";
					}

					if(!in_array($login_fabrica, [167, 203])){
						echo "<td>".traduz("Total")."</td>";
					}
				echo "</tr>";

			echo "</thead>";

			echo "<tbody>";

				for ($i=0; $i<$num; $i++){

					$sua_os                  = trim(pg_result($result,$i,sua_os))            ;
					$os                      = trim(pg_result($result,$i,os))            ;
					$produto_referencia_fabrica      = trim(pg_result($result,$i,produto_referencia_fabrica));
					$produto_referencia      = trim(pg_result($result,$i,produto_referencia));
					$produto_descricao       = trim(pg_result($result,$i,produto_descricao)) ;
					$serie                   = trim(pg_result($result,$i,serie))             ;
					$solucao_os              = trim(pg_result($result,$i,solucao_os))        ;
					$defeito_codigo          = trim(pg_result($result,$i,defeito_codigo))    ;
					$defeito_descricao       = trim(pg_result($result,$i,defeito_descricao)) ;
					$causa_codigo            = trim(pg_result($result,$i,causa_codigo))      ;
					$causa_descricao         = trim(pg_result($result,$i,causa_descricao))   ;
					$mao_de_obra             = trim(pg_result($result,$i,mao_de_obra))       ;
					$pecas                   = trim(pg_result($result,$i,pecas))             ;
					$total                   = trim(pg_result($result,$i,total))             ;
					$data_geracao            = trim(pg_result($result,$i,data_geracao))      ;
					$data_exportado          = trim(pg_result($result,$i,data_exportado))    ;
					$qtde_km_calculada       = trim(pg_result($result,$i,qtde_km_calculada))    ;
					$valores_adicionais      = trim(pg_result($result,$i,valores_adicionais))    ;
					$data_pagamento          = trim(pg_result($result,$i,data_pagamento))    ;
					$tipo_os          		 = trim(pg_result($result,$i,tipo_descricao))    ;
					$familia         		 = trim(pg_result($result,$i,familia_descricao))    ;
					$linha          		 = trim(pg_result($result,$i,linha_descricao))    ;
					$posto_nome        		 = trim(pg_result($result,$i,posto_nome))    ;
					$extrato        		 = trim(pg_result($result,$i,extrato))    ;
					$mes        		 	 = trim(pg_result($result,$i,mes))    ;
					$data_abertura 		 	 = trim(pg_result($result,$i,data_abertura))    ;
					$data_fechamento         = trim(pg_result($result,$i,data_fechamento))    ;
					$consumidor_nome         = trim(pg_result($result,$i,consumidor_nome))    ;
					$revenda_nome            = trim(pg_result($result,$i,revenda_nome))    ;
					$motivo_recusa           = trim(pg_result($result,$i,motivo_recusa))    ;

					if(in_array($login_fabrica, [167, 203])){
						$lancamento_pecas = trim(pg_result($result,$i,lancamento_pecas))    ;
						$situacao         = trim(pg_result($result,$i,situacao))    ;
					}

					if ($login_fabrica == 178){
						$avulso = pg_fetch_result($result, $i, avulso);
						$avulso = number_format($avulso, 2, ",", ".");
					}

					$pecas              = number_format($pecas, 2, ",", ".");
					$mao_de_obra        = number_format($mao_de_obra, 2, ",", ".");
					$qtde_km_calculada  = number_format($qtde_km_calculada ,2, ",", ".");
					$valores_adicionais = number_format($valores_adicionais, 2, ",", ".");
					$total              = number_format($total, 2, ",", ".");

					if (strlen($valores_adicionais) == 0) {
						$valores_adicionais = '0,00';
					}

					if($login_fabrica == 74){

						$sql_pecas = "SELECT SUM(qtde * custo_peca) AS total FROM tbl_os_item WHERE os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE os = {$os})";
						$res_pecas = pg_query($con, $sql_pecas);

						$pecas = pg_fetch_result($res_pecas, 0, "total");

						$total = number_format($total + $pecas, 2, ",", ".");
						$pecas = number_format($pecas, 2, ",", ".");

					}

					echo "<tr>";

						if(in_array($login_fabrica, [167, 203])){
							echo "<td align='left'>$mes</td>";
							echo "<td align='left' nowrap>$revenda_nome</td>";
							echo "<td align='left' nowrap>$posto_nome</td>";
						}

						if($login_fabrica != 20){
							echo "<td nowrap><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
						}

						if ($login_fabrica == 148) {
							echo "<td align='left' title='$tipo_os'>$tipo_os</td>";
						}

						if ($login_fabrica == 171) {
							echo "<td align='left' title='$tipo_os'>$produto_referencia_fabrica</td>"; 
						}

						if ($login_fabrica == 178) {
							echo "<td align='left' title='$tipo_os'>$produto_referencia</td>"; 
							echo "<td align='left' title='$tipo_os'>".substr($produto_descricao,0,20)."</td>"; 
						}

						if(!in_array($login_fabrica,array(167,178,203))){
							echo "<td align='left' title='$produto_descricao'>$produto_referencia - ".substr($produto_descricao,0,20)."</td>";
						}

						if(in_array($login_fabrica, [167, 203])){
							echo "<td align='left'>$consumidor_nome</td>";
							echo "<td align='left' title='$produto_descricao'>$produto_referencia</td>";
						}

						if ($login_fabrica == 148) {
							echo "<td align='left' title='$familia'>$familia</td>";
							echo "<td align='left' title='$posto_nome'>$posto_nome</td>";
						}

						if ($login_fabrica == 178) {
							echo "<td align='left' title='$familia'>$linha</td>";
							echo "<td align='left' title='$posto_nome'>$familia</td>";
						} else {
							echo "<td>$serie</td>";
						}

						if(in_array($login_fabrica, [167, 203])){
							echo "<td nowrap>$lancamento_pecas</td>";
							echo "<td>$data_abertura</td>";
							echo "<td>$data_fechamento</td>";
							echo "<td>$situacao</td>";
						}

						if(!in_array($login_fabrica,array(20,167,203))){
							echo "<td align='left'>$defeito_codigo - $defeito_descricao</td>";
						}

						if($login_fabrica == 20){
							$xsolucao = "";
							if(strlen($solucao_os) > 0){
								$xsql = "SELECT descricao FROM tbl_servico_realizado WHERE servico_realizado = $solucao_os LIMIT 1";
								$xres = pg_query($con, $xsql);
								$xsolucao = trim(pg_fetch_result($xres, 0, "descricao"));
							}
							echo "<td align='left'>$xsolucao</td>";
							echo "<td align='left'>$causa_codigo- $causa_descricao</td>";
						}

						if(!in_array($login_fabrica, array(167,178,203))){
							echo "<td align='left'>";
							if($login_fabrica == 20)
								echo "$data_exportado";
							else
								echo "$data_geracao";
							echo "</td>";
							echo "<td align='right' width='75'>$data_pagamento</td>";
						}

						if ($login_fabrica == 148) {
							echo "<td align='right' width='75'>$extrato</td>";
						}

						echo "<td align='right' nowrap>$real .  $mao_de_obra</td>";

						if(in_array($login_fabrica, [167, 203])){
							echo "<td align='right' width='75'>$data_pagamento</td>";
							echo "<td align='right' width='75'>$motivo_recusa</td>";
						}

						if((!in_array($login_fabrica, array(157,158)) && $extrato_sem_peca != "t") && !in_array($login_fabrica, array(167,178,203))) {
							echo "<td align='right' width='75'>$real .  $pecas</td>";
						}
						if($telecontrol_distrib || in_array($login_fabrica, $fabricas_km)) {
							echo "<td align='right' width='75'>$real .  $qtde_km_calculada</td>";
							echo "<td align='right' width='75'>$real .  $valores_adicionais</td>";
						}

						if(in_array($login_fabrica, array(50))) {
							echo "<td align='right' width='75'>$real .  $qtde_km_calculada</td>";
						}

						if($login_fabrica == 178) {
							echo "<td align='right' width='75'>R$ $avulso</td>";
						}

						if(!in_array($login_fabrica, [167, 203])){
							echo "<td align='right' width='75'>$real .  $total</td>";
						}

					echo "</tr>";
				}

			echo "<tbody>";

		echo "</table>";

		if($num > 50){

		?>

		<script>
	        $.dataTableLoad({
	            table : "#relatorio"
	        });
        </script>

		<?php

		}


		if(in_array($login_fabrica,array(148,167,203))){
			$tipo_os_post = (isset($_POST["tipo_os"])) ? $_POST["tipo_os"] : array();
			$tipo_familia_post = (isset($_POST["familia"])) ? $_POST["familia"] : array();
			$arr_excel = array(
				"data_inicial" 	=> $_POST["data_inicial"],
				"data_final" 	=> $_POST["data_final"],
				"tipo_os[]" 	=> $tipo_os_post,
				"familia[]" 	=> $tipo_familia_post
			);

		} else if ($login_fabrica == 178) {
			$arr_excel = array(
				"mes" => $_POST["mes"],
				"ano" => $_POST["ano"]
			);
		}else {
			$arr_excel = array(
				"data_inicial" 	=> $_POST["data_inicial"],
				"data_final" 	=> $_POST["data_final"]
			);
		}

		?>

		<br /> <br/>

		<div id='gerar_excel' class="btn_excel">
	        <input type="hidden" id="jsonPOST" value='<?=json_encode($arr_excel)?>' />
	        <span><img src='imagens/excel.png' /></span>
	        <span class="txt"><?=traduz('Gerar Arquivo Excel')?></span>
	    </div>

		<?php

	}else{
		echo "<div class='container'>";
			echo "<div class='alert alert-block alert-warning'><h4>".traduz("Não foram Encontrados Resultados para esta Pesquisa")."</h4></div>";
		echo "</div>";
	}

}

echo "<br /> <br />";

include 'rodape.php';

?>
