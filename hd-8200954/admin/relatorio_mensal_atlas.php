<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_admin.php';
	
	if($_POST){
		$ano_aux          = $_POST['ano'];
		$mes_aux          = $_POST['mes'];
		$codigo_posto_aux = $_POST['filtro_posto_ref'];
		$posto_nome_aux   = $_POST['filtro_posto_desc'];
		$estado_aux       = $_POST['estado'];
        $relatorio        = $_POST['relatorio'];
		$tipo_nota        = $_POST['tipo_nota'];
		
		
		$data_inicial = "$ano_aux-$mes_aux-01";
		$data_final   = date('Y-m-t', strtotime($ano_aux.'-'.$mes_aux.'-1'));

		$sql = "SELECT date'$data_inicial' - interval '1 month' AS data_pedido_inicio,
					   date'$data_final' - interval '1 month' AS data_pedido_final";
		$res = pg_query($con,$sql);
		$data_pedido_inicio = pg_result($res,0,'data_pedido_inicio');
		$data_pedido_final  = pg_result($res,0,'data_pedido_final');

		$intervalo = "'$data_inicial' - interval '1 month'";

		if(!empty($codigo_posto_aux)){
			$sql = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto_aux' AND fabrica = $login_fabrica";
			$res = pg_query($con,$sql);
			if(pg_numrows($res) == 0){
				$msg_erro = "Posto não encontrado";
			} else {
				$posto = pg_result($res,0,posto);
				$condPosto = " AND tbl_posto.posto = $posto";
				$condPosto2 = " AND tbl_os.posto = $posto";
			}
		}

        if(!empty($tipo_nota)){
            /**
            * - Verifica o estado dos extrato:
            * BAIXADAS  :- Extratos com baixa: incluídas em tbl_extrato_pagamento
            * PENDENTES :- Extratos com NF cadastrada pelo posto
            * SEM_NF    :- Extratos com NF não cadastradas pelo posto
            */
            switch($tipo_nota){
                case "baixadas":
                    $join_nota = " JOIN tbl_extrato_pagamento   ON  tbl_extrato_pagamento.extrato = tbl_extrato.extrato
                                                                AND tbl_extrato.fabrica = $login_fabrica
                    ";
                    if(!empty($posto)){
                        $join_nota .= " AND tbl_extrato.posto = $posto";
                    }
                break;
                case "pendentes":
                    $join_nota = "  JOIN tbl_extrato_extra  ON  tbl_extrato_extra.extrato = tbl_extrato.extrato
                                                            AND tbl_extrato_extra.nota_fiscal_mao_de_obra IS NOT NULL
                    ";
                    if(!empty($posto)){
                        $join_nota .= " AND tbl_extrato.posto = $posto";
                    }
                break;
                case "sem_nf":
                    $join_nota = "  JOIN tbl_extrato_extra  ON  tbl_extrato_extra.extrato = tbl_extrato.extrato
                                                            AND tbl_extrato_extra.nota_fiscal_mao_de_obra IS NULL
                    ";
                    if(!empty($posto)){
                        $join_nota .= " AND tbl_extrato.posto = $posto";
                    }
                break;
                default:
                    $join_nota = " ";
                break;
            }
        }
		if(!empty($estado)){
			$condEstado = " AND tbl_posto_fabrica.contato_estado = '$estado'";
		}
//$msg_erro = "ERROR: statement cancelled due to a statement timeout"; // Para testes!! Descomentar para simular erro de timeout.
		if(empty($msg_erro)){
			
			if($relatorio == 1){

				$sql = "SELECT tbl_os.os,
							   tbl_os.mao_de_obra,
							   tbl_os.qtde_km_calculada,
							   tbl_os.qtde_km,
							   tbl_os.pecas,
							   tbl_os.posto,
							   tbl_os.data_fechamento,
							   tbl_os.data_nf,
							   tbl_os_extra.extrato
							INTO TEMP tmp_todas_os 
						FROM tbl_os 
						JOIN tbl_os_extra ON tbl_os.os            = tbl_os_extra.os and tbl_os_extra.i_fabrica = tbl_os.fabrica
						JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato and tbl_os.fabrica = tbl_extrato.fabrica
						JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto   
						JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica $condEstado
						$join_nota
						WHERE tbl_os.fabrica = $login_fabrica
						AND tbl_extrato.data_geracao::date BETWEEN '$data_inicial' and '$data_final'
						$condPosto2; ";
				$resT = pg_query($con, $sql);

				$tabela = "tmp_todas_os";

				$sql = "SELECT tbl_posto.posto,
							tbl_posto_fabrica.codigo_posto,
							tbl_posto.nome,
							tbl_posto_fabrica.contato_estado,
							SUM(tmp_todas_os.mao_de_obra)       AS mao_de_obra,
							SUM(tmp_todas_os.qtde_km_calculada) AS total_km,
							COUNT(tmp_todas_os.os)              AS qtde_os
						INTO TEMP tmp_total_por_posto
						FROM tmp_todas_os
						JOIN tbl_posto ON tbl_posto.posto = tmp_todas_os.posto   
						JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica $condEstado
						GROUP BY tbl_posto.posto,
							tbl_posto_fabrica.codigo_posto,
							tbl_posto.nome,
							tbl_posto_fabrica.contato_estado;";
				$resT = pg_query($con, $sql);

				$sql = "SELECT tmp_total_por_posto.posto,
								COUNT(tmp_todas_os.os) AS qtde_os_deslocamento
						INTO TEMP tmp_total_os_deslocamento
						FROM tmp_todas_os
						JOIN tmp_total_por_posto ON tmp_total_por_posto.posto = tmp_todas_os.posto
					   WHERE tmp_todas_os.qtde_km > 0
						GROUP BY tmp_total_por_posto.posto;

						SELECT tmp_todas_os.posto,
						       tbl_extrato.pecas AS total_compra
							INTO TEMP tmp_total_compra
						FROM tmp_todas_os 
							JOIN tbl_extrato ON tmp_todas_os.extrato = tbl_extrato.extrato  
						GROUP BY tmp_todas_os.posto,tbl_extrato.pecas;";
				$resT = pg_query($con, $sql);

				$sql = "SELECT tmp_total_por_posto.posto,
							SUM(tmp_todas_os.pecas) AS total_garantia
							INTO TEMP tmp_total_garantia
						FROM tmp_todas_os 
							JOIN tmp_total_por_posto   ON tmp_total_por_posto.posto               = tmp_todas_os.posto
							JOIN tbl_os_produto        ON tbl_os_produto.os                       = tmp_todas_os.os  
							JOIN tbl_os_item           ON tbl_os_item.os_produto                  = tbl_os_produto.os_produto AND tbl_os_item.fabrica_i = {$login_fabrica} 
							JOIN tbl_peca              ON tbl_peca.peca                           = tbl_os_item.peca AND tbl_peca.produto_acabado IS TRUE AND tbl_peca.fabrica = {$login_fabrica} 
							JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.troca_de_peca IS TRUE AND tbl_servico_realizado.fabrica = {$login_fabrica}  
						GROUP BY tmp_total_por_posto.posto;";
				$resT = pg_query($con, $sql);

				$sql = "SELECT tmp_total_por_posto.posto,
							SUM(DISTINCT tbl_faturamento.total_nota) AS total_bonificacao
							INTO TEMP tmp_total_bonificacao
						FROM tbl_pedido
						JOIN tbl_faturamento_item ON tbl_pedido.pedido                = tbl_faturamento_item.pedido
						JOIN tbl_faturamento      ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento AND tbl_faturamento.fabrica = $login_fabrica
						JOIN tmp_total_por_posto  ON tmp_total_por_posto.posto        = tbl_pedido.posto
						WHERE tbl_pedido.fabrica = $login_fabrica 
						AND tbl_pedido.tipo_pedido = 201 
						AND tbl_faturamento.emissao BETWEEN '$data_inicial' and '$data_final'
						GROUP BY tmp_total_por_posto.posto;";
				$resT = pg_query($con, $sql);

				$sql = "SELECT tmp_total_por_posto.posto,
							SUM(DISTINCT tbl_faturamento.total_nota) AS total_venda
							INTO TEMP tmp_total_venda
						FROM tbl_pedido
						JOIN tbl_faturamento_item ON tbl_pedido.pedido                = tbl_faturamento_item.pedido
						JOIN tbl_faturamento      ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento AND tbl_faturamento.fabrica = $login_fabrica
						JOIN tmp_total_por_posto  ON tmp_total_por_posto.posto        = tbl_pedido.posto
						WHERE tbl_pedido.fabrica = $login_fabrica 
						AND tbl_pedido.tipo_pedido = 198
						AND tbl_faturamento.emissao BETWEEN '$data_inicial' and '$data_final'
						AND tbl_faturamento.posto = tmp_total_por_posto.posto
						GROUP BY tmp_total_por_posto.posto;";
				$resT = pg_query($con, $sql);

				$sql = "SELECT tmp_total_por_posto.posto,
							SUM(tmp_todas_os.qtde_km_calculada) AS total_km_90
							INTO TEMP tmp_total_km_90
						FROM tmp_todas_os 
						JOIN tmp_total_por_posto ON tmp_total_por_posto.posto = tmp_todas_os.posto
						WHERE (tmp_todas_os.data_fechamento - tmp_todas_os.data_nf) <= 90 
						GROUP BY tmp_total_por_posto.posto";
				$resT = pg_query($con, $sql);

				$sql = "SELECT  tmp_total_por_posto.posto,
                                tmp_total_por_posto.codigo_posto,
                                tmp_total_por_posto.nome,
                                tmp_total_por_posto.contato_estado,
                                tmp_total_por_posto.mao_de_obra,
                                tmp_total_por_posto.total_km,
                                tmp_total_por_posto.qtde_os,
                                tmp_total_os_deslocamento.qtde_os_deslocamento,
                                tmp_total_compra.total_compra,
                                tmp_total_garantia.total_garantia,
                                tmp_total_bonificacao.total_bonificacao,
                                tmp_total_venda.total_venda,
                                tmp_total_km_90.total_km_90
						FROM    tmp_total_por_posto
						$join_estado
                   LEFT JOIN    tmp_total_compra          ON tmp_total_compra.posto          = tmp_total_por_posto.posto
                   LEFT JOIN    tmp_total_garantia        ON tmp_total_garantia.posto        = tmp_total_por_posto.posto
                   LEFT JOIN    tmp_total_bonificacao     ON tmp_total_bonificacao.posto     = tmp_total_por_posto.posto
                   LEFT JOIN    tmp_total_venda           ON tmp_total_venda.posto           = tmp_total_por_posto.posto
                   LEFT JOIN    tmp_total_km_90           ON tmp_total_km_90.posto           = tmp_total_por_posto.posto
                   LEFT JOIN    tmp_total_os_deslocamento ON tmp_total_os_deslocamento.posto = tmp_total_por_posto.posto
                  GROUP BY      tmp_total_por_posto.posto,
                                tmp_total_por_posto.codigo_posto,
                                tmp_total_por_posto.nome,
                                tmp_total_por_posto.contato_estado,
                                tmp_total_por_posto.mao_de_obra,
                                tmp_total_por_posto.total_km,
                                tmp_total_por_posto.qtde_os,
                                tmp_total_os_deslocamento.qtde_os_deslocamento,
                                tmp_total_compra.total_compra,
                                tmp_total_garantia.total_garantia,
                                tmp_total_bonificacao.total_bonificacao,
                                tmp_total_venda.total_venda,
                                tmp_total_km_90.total_km_90;";
				#echo nl2br($sql);
				$res = pg_query($con,$sql);
				$msg_erro = pg_last_error($con);

				if(pg_num_rows($res) > 0){

					$botao = "<input type='button' value='DOWNLOAD RELATÓRIO' onclick=\" window.location.href='xls/rel_fechamento_mensal_resumido_$mes_aux_$ano_aux_$login_fabrica.xls'\">";
					
					$fp = fopen ("xls/rel_fechamento_mensal_resumido_$mes_aux_$ano_aux_$login_fabrica.xls","w");

						$conteudo = "<table border='1' bgcolor='#000'>
									<tr><td align='center' colspan='12' bgcolor='#596d9b'><font color='#FFFFFF'>Relatório Fechamento Mensal Resumido</font></td></tr>
									<tr>
										<td bgcolor='#FFFF00'>CODIGO</td>
										<td bgcolor='#FFFF00'>POSTO</td>
										<td bgcolor='#FFFF00'>UF</td>
										<td bgcolor='#FFFF00'>SERVIÇO</td>
										<td bgcolor='#FFFF00'>KM</td>
										<td bgcolor='#FFFF00'>COMPRA</td>
										<td bgcolor='#FFFF00'>GARANTIA</td>
										<td bgcolor='#FFFF00'>TROCA</td>
										<td bgcolor='#FFFF00'>VENDA</td>
										<td bgcolor='#FFFF00'>VALOR KM -90</td>
										<td bgcolor='#FFFF00'>ATENDIMENTO C/KM</td>
										<td bgcolor='#FFFF00'>TOTAL DE OS</td>
									</tr>";

						for($i = 0; $i < pg_numrows($res); $i++){
							$codigo_posto         = pg_result($res,$i,codigo_posto);
							$posto_nome           = pg_result($res,$i,nome);
							$estado               = pg_result($res,$i,contato_estado);
							$mao_de_obra          = pg_result($res,$i,mao_de_obra);
							$total_km             = pg_result($res,$i,total_km);
							$total_compra         = pg_result($res,$i,total_compra);
							$total_garantia       = pg_result($res,$i,total_garantia);
							$total_bonificacao    = pg_result($res,$i,total_bonificacao);
							$total_venda          = pg_result($res,$i,total_venda);
							$total_km_90          = pg_result($res,$i,total_km_90);
							$qtde_os              = pg_result($res,$i,qtde_os);
							$qtde_os_deslocamento = pg_result($res,$i,qtde_os_deslocamento);
							
							//Soma para totalizar resultados
							$soma_mao_de_obra           += $mao_de_obra;
							$soma_km                    += $total_km;
							$soma_compra                += $total_compra;
							$soma_garantia              += $total_garantia;
							$soma_bonificacao           += $total_bonificacao;
							$soma_venda                 += $total_venda;
							$soma_km_90                 += $total_km_90;
							$qtde_total_os              += $qtde_os;
							$qtde_total_os_deslocamento += $qtde_os_deslocamento;
							//===============================

							$conteudo .= "<tr>
											<td>".$codigo_posto."<font color='#FFFFFF'>'</font></td>
											<td>".$posto_nome."</td>
											<td>".$estado."</td>
											<td>".number_format($mao_de_obra,2,',','.')."</td>
											<td>".number_format($total_km,2,',','.')."</td>
											<td>".number_format($total_compra,2,',','.')."</td>
											<td>".number_format($total_garantia,2,',','.')."</td>
											<td>".number_format($total_bonificacao,2,',','.')."</td>
											<td>".number_format($total_venda,2,',','.')."</td>
											<td>".number_format($total_km_90,2,',','.')."</td>
											<td>".number_format($qtde_os_deslocamento,2,',','.')."</td>
											<td>".number_format($qtde_os,2,',','.')."</td>
										  </tr>";
						}
						$conteudo .= "<tr>
										<td bgcolor='#CCCCCC'>&nbsp;</td>
										<td bgcolor='#CCCCCC'>&nbsp;</td>
										<td bgcolor='#CCCCCC'>&nbsp;</td>
										<td bgcolor='#CCCCCC'>".number_format($soma_mao_de_obra,2,',','.')."</td>
										<td bgcolor='#CCCCCC'>".number_format($soma_km,2,',','.')."</td>
										<td bgcolor='#CCCCCC'>".number_format($soma_compra,2,',','.')."</td>
										<td bgcolor='#CCCCCC'>".number_format($soma_garantia,2,',','.')."</td>
										<td bgcolor='#CCCCCC'>".number_format($soma_bonificacao,2,',','.')."</td>
										<td bgcolor='#CCCCCC'>".number_format($soma_venda,2,',','.')."</td>
										<td bgcolor='#CCCCCC'>".number_format($soma_km_90,2,',','.')."</td>
										<td bgcolor='#CCCCCC'>".number_format($qtde_total_os_deslocamento,2,',','.')."</td>
										<td bgcolor='#CCCCCC'>".number_format($qtde_total_os,2,',','.')."</td>
									  </tr>
						</table>";
				}

			}

			else if($relatorio == 2){
				$sql = "SELECT  tbl_os.fabrica,tbl_posto_fabrica.codigo_posto,
								tbl_posto.nome AS posto_nome,
								tbl_posto_fabrica.contato_estado,
								tbl_os.os,
								tbl_os.qtde_km,
								tbl_os.data_fechamento,
								tbl_os.serie,
								tbl_os.revenda_nome,
								tbl_cidade.nome AS cidade_revenda,
								tbl_os.consumidor_nome,
								tbl_os.consumidor_cidade,
								tbl_os.consumidor_estado,
								tbl_os.mao_de_obra,
								tbl_os.qtde_km_calculada,
								tbl_os.posto,
								tbl_os.sua_os,
								tbl_os_extra.extrato,
								tbl_produto.referencia AS produto_referencia,
								tbl_produto.descricao  AS produto_descricao,
								tbl_linha.nome         AS linha_nome,
								tbl_numero_serie.data_fabricacao
                   INTO TEMP    tmp_todas_os
                        FROM    tbl_os
                        JOIN    tbl_os_extra      ON tbl_os.os                = tbl_os_extra.os AND tbl_os_extra.i_fabrica = tbl_os.fabrica
                        JOIN    tbl_extrato       ON tbl_os_extra.extrato     = tbl_extrato.extrato AND tbl_extrato.fabrica = tbl_os.fabrica
                        JOIN    tbl_posto         ON tbl_os.posto             = tbl_posto.posto
                        JOIN    tbl_posto_fabrica ON tbl_posto.posto          = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                        JOIN    tbl_produto       ON tbl_os.produto           = tbl_produto.produto     AND tbl_produto.fabrica_i     = $login_fabrica
                        JOIN    tbl_linha         ON tbl_linha.linha          = tbl_produto.linha       AND tbl_linha.fabrica         = $login_fabrica
                   LEFT JOIN    tbl_numero_serie  ON tbl_numero_serie.produto = tbl_os.produto          AND tbl_numero_serie.serie    = tbl_os.serie
                                                                                                        AND tbl_numero_serie.fabrica = $login_fabrica
                   LEFT JOIN    tbl_revenda       ON tbl_os.revenda           = tbl_revenda.revenda
                   LEFT JOIN    tbl_cidade        ON tbl_revenda.cidade       = tbl_cidade.cidade
                        $join_estado
                        WHERE   tbl_os.fabrica = $login_fabrica
                        AND     tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
                        $condPosto2;";
				$resT = pg_query($con, $sql);

	

		   $sql = "CREATE INDEX tmp_todas_os_fabrica_os ON tmp_todas_os(fabrica,os); 

				SELECT  tmp_todas_os.os,
					tbl_peca.peca,
					tbl_peca.referencia    AS peca_referencia,
					tbl_peca.descricao     AS peca_descricao,
					tbl_defeito.descricao  AS defeito_descricao,
					tbl_os_item.qtde       AS qtde,
					tbl_os_item.custo_peca AS pecas
	                   INTO TEMP    tmp_todos_itens
				FROM    tmp_todas_os
				JOIN    tbl_os_produto ON tmp_todas_os.os        = tbl_os_produto.os
				JOIN    tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_item.fabrica_i = $login_fabrica
				JOIN    tbl_peca       ON tbl_os_item.peca       = tbl_peca.peca             AND tbl_peca.fabrica      = $login_fabrica
				JOIN    tbl_defeito    ON tbl_defeito.defeito    = tbl_os_item.defeito       AND tbl_defeito.fabrica   = $login_fabrica
				WHERE   tmp_todas_os.fabrica = $login_fabrica";
				$resT = pg_query($con, $sql);

		   $sql = "SELECT  tmp_todas_os.os,
					tbl_extrato_extra.nota_fiscal_mao_de_obra,
					tbl_extrato_extra.nota_fiscal_serie_mao_de_obra,
					tbl_extrato.extrato,
					tbl_extrato.mao_de_obra AS mao_obra_extrato,
					tbl_extrato.deslocamento
                   INTO TEMP    tmp_totos_extratos
                        FROM    tmp_todas_os
                        JOIN    tbl_os_extra            ON tmp_todas_os.os      = tbl_os_extra.os           AND tbl_os_extra.extrato IS NOT NULL AND tbl_os_extra.i_fabrica=$login_fabrica
                        JOIN    tbl_extrato             ON tbl_os_extra.extrato = tbl_extrato.extrato       AND tbl_extrato.fabrica  = $login_fabrica
                   LEFT JOIN    tbl_extrato_extra       ON tbl_extrato.extrato  = tbl_extrato_extra.extrato
                  GROUP BY      tmp_todas_os.os,
								tbl_extrato_extra.nota_fiscal_mao_de_obra,
								tbl_extrato_extra.nota_fiscal_serie_mao_de_obra,
								tbl_extrato.extrato,
								tbl_extrato.mao_de_obra,
								tbl_extrato.deslocamento";
				$resT = pg_query($con, $sql);

				$sql = "SELECT  DISTINCT tmp_totos_extratos.os,
								tmp_todos_itens.peca,
								tmp_totos_extratos.extrato,
								tbl_faturamento.serie,
								tbl_faturamento.nota_fiscal,
								tbl_faturamento.total_nota,
								tbl_faturamento.serie AS serie_peca,
								tbl_faturamento_item.nota_fiscal_origem,
								tbl_faturamento.total_nota AS compra
                   INTO TEMP    tmp_todos_faturamentos
                        FROM    tmp_totos_extratos
                        JOIN    tmp_todos_itens      ON tmp_totos_extratos.os             = tmp_todos_itens.os
                        JOIN    tbl_faturamento      ON tbl_faturamento.extrato_devolucao = tmp_totos_extratos.extrato  AND tbl_faturamento.fabrica   = $login_fabrica
                        JOIN    tbl_faturamento_item ON tbl_faturamento_item.faturamento  = tbl_faturamento.faturamento AND tbl_faturamento_item.peca = tmp_todos_itens.peca
                  GROUP BY      tmp_totos_extratos.os,
								tmp_todos_itens.peca,
								tmp_totos_extratos.extrato,
								tbl_faturamento.serie,
								tbl_faturamento.nota_fiscal,
								tbl_faturamento.total_nota,
								tbl_faturamento.serie,
								tbl_faturamento_item.nota_fiscal_origem,
								tbl_faturamento.total_nota";
				$resT = pg_query($con, $sql);

				$sql = "SELECT  DISTINCT tmp_todas_os.codigo_posto,
								tmp_todas_os.posto_nome,
								tmp_todas_os.contato_estado,
								tmp_todas_os.qtde_km,
								tmp_todas_os.os,
								tmp_todas_os.sua_os,
								TO_CHAR(tmp_todas_os.data_fechamento,'DD/MM/YYYY') as data_fechamento,
								tmp_todas_os.produto_referencia,
								tmp_todas_os.produto_descricao,
								tmp_todas_os.serie AS serie_os,
								TO_CHAR(tmp_todas_os.data_fabricacao,'DD/MM/YYYY') as data_fabricacao,
								tmp_todas_os.revenda_nome,
								tmp_todas_os.cidade_revenda,
								tmp_todas_os.consumidor_nome,
								tmp_todas_os.consumidor_cidade,
								tmp_todas_os.consumidor_estado,
								tmp_todas_os.mao_de_obra,
								tmp_todas_os.qtde_km_calculada,
								tmp_todas_os.linha_nome,
								tmp_todos_faturamentos.serie,
								tmp_todos_faturamentos.nota_fiscal,
								tmp_todos_faturamentos.total_nota,
								tmp_todos_faturamentos.serie_peca,
								tmp_todos_faturamentos.nota_fiscal_origem,
								tmp_todos_faturamentos.compra,
								tmp_totos_extratos.nota_fiscal_mao_de_obra,
								tmp_totos_extratos.nota_fiscal_serie_mao_de_obra,
								tmp_totos_extratos.extrato,
								tmp_totos_extratos.mao_obra_extrato,
								tmp_totos_extratos.deslocamento,
								tmp_todos_itens.peca_referencia,
								tmp_todos_itens.peca_descricao,
								tmp_todos_itens.defeito_descricao,
								tmp_todos_itens.qtde,
								tmp_todos_itens.pecas
                        FROM tmp_todas_os

								LEFT JOIN tmp_todos_itens        ON tmp_todas_os.os = tmp_todos_itens.os
								JOIN tmp_totos_extratos     ON tmp_todas_os.os = tmp_totos_extratos.os
								LEFT JOIN tmp_todos_faturamentos ON tmp_todas_os.os = tmp_todos_faturamentos.os AND tmp_todos_faturamentos.peca = tmp_todos_itens.peca
								
								GROUP BY tmp_todas_os.codigo_posto,
										tmp_todas_os.posto_nome,
										tmp_todas_os.os,
										tmp_todas_os.sua_os,
										tmp_todos_itens.peca,
										tmp_todas_os.contato_estado,
										tmp_todas_os.qtde_km,
										tmp_todas_os.data_fechamento,
										tmp_todas_os.produto_referencia,
										tmp_todas_os.produto_descricao,
										tmp_todas_os.serie,
										tmp_todas_os.data_fabricacao,
										tmp_todas_os.revenda_nome,
										tmp_todas_os.cidade_revenda,
										tmp_todas_os.consumidor_nome,
										tmp_todas_os.consumidor_cidade,
										tmp_todas_os.consumidor_estado,
										tmp_todas_os.mao_de_obra,
										tmp_todas_os.qtde_km_calculada,
										tmp_todas_os.linha_nome,
										tmp_todos_faturamentos.serie,
										tmp_todos_faturamentos.nota_fiscal,
										tmp_todos_faturamentos.total_nota,
										tmp_todos_faturamentos.serie_peca,
										tmp_todos_faturamentos.nota_fiscal_origem,
										tmp_todos_faturamentos.compra,
										tmp_totos_extratos.nota_fiscal_mao_de_obra,
										tmp_totos_extratos.nota_fiscal_serie_mao_de_obra,
										tmp_totos_extratos.extrato,
										tmp_totos_extratos.mao_obra_extrato,
										tmp_totos_extratos.deslocamento,
										tmp_todos_itens.peca_referencia,
										tmp_todos_itens.peca_descricao,
										tmp_todos_itens.defeito_descricao,
										tmp_todos_itens.qtde,
										tmp_todos_itens.pecas
								ORDER BY tmp_todas_os.posto_nome,tmp_todas_os.os,tmp_todos_faturamentos.nota_fiscal;
				";
				#echo nl2br($sql);
				$res = pg_query($con,$sql);
				$msg_erro = pg_last_error($con);
				
				if(pg_num_rows($res) > 0){

					$botao = "<input type='button' value='DOWNLOAD RELATÓRIO' onclick=\" window.location.href='xls/rel_fechamento_completo_$mes_aux_$ano_aux_$login_fabrica.xls'\">";
					
					$fp = fopen ("xls/rel_fechamento_completo_$mes_aux_$ano_aux_$login_fabrica.xls","w");

					$conteudo = "<table border='1'>
									<tr bgcolor='#596d9b'><td align='center' colspan='29'><font color='#FFFFFF'><b>Relatório Fechamento Completo</b></font></td></tr>
									<tr bgcolor='#FFFF00'>
										<td><b>CÓDIGO</b></td>
										<td><b>POSTO</b></td>
										<td><b>UF</b></td>
										<td><b>SÉRIE</b></td>
										<td><b>DOCTO</b></td>
										<td><b>SERVIÇO</b></td>
										<td><b>KM</b></td>
										<td><b>OS</b></td>
										<td><b>ATENDIMENTO</b></td>
										<td><b>PRODUTO</b></td>
										<td><b>DESCRIÇÃO</b></td>
										<td><b>SÉRIE</b></td>
										<td><b>FABRICAÇÃO</b></td>
										<td><b>REVENDEDOR</b></td>
										<td><b>CIDADE</b></td>
										<td><b>CONSUMIDOR</b></td>
										<td><b>CIDADE</b></td>
										<td><b>UF</b></td>
										<td><b>VALOR OS</b></td>
										<td><b>VALOR KM</b></td>
										<td bgcolor='#FF0000'><b>SÉRIE</b></td>
										<td bgcolor='#FF0000'><b>DOCTO</b></td>
										<td bgcolor='#FF0000'><b>COMPRA</b></td>
										<td bgcolor='#FF0000'><b>PEÇA</b></td>
										<td bgcolor='#FF0000'><b>DESCRIÇÃO</b></td>
										<td bgcolor='#FF0000'><b>DEFEITO DESCRIÇÃO</b></td>
										<td bgcolor='#FF0000'><b>QUANTIDADE</b></td>
										<td bgcolor='#FF0000'><b>VALOR PC</b></td>
										<td bgcolor='#FF0000'><b>LINHA</b></td>
									</tr>";
					$os_ant = "";
					$serie_ant = "";
					for($i = 0; $i < pg_numrows($res); $i++){
						$posto				= pg_result($res,$i,posto);
						$codigo_posto		= pg_result($res,$i,codigo_posto);
						$posto_nome			= pg_result($res,$i,posto_nome);
						$estado				= pg_result($res,$i,contato_estado);
						$serie				= pg_result($res,$i,serie);
						$nota_fiscal		= pg_result($res,$i,nota_fiscal);
						$total_nota			= pg_result($res,$i,total_nota);
						$qtde_km			= pg_result($res,$i,deslocamento);
						$os					= pg_result($res,$i,os);
						$sua_os					= pg_result($res,$i,'sua_os');
						$data_fechamento	= pg_result($res,$i,data_fechamento);
						$produto_referencia = pg_result($res,$i,produto_referencia);
						$produto_descricao	= pg_result($res,$i,produto_descricao);
						$serie_os			= pg_result($res,$i,serie_os);
						$data_fabricacao	= pg_result($res,$i,data_fabricacao);
						$revenda_nome		= pg_result($res,$i,revenda_nome);
						$cidade_nome		= pg_result($res,$i,cidade_revenda);
						$consumidor_nome	= pg_result($res,$i,consumidor_nome);
						$consumidor_cidade	= pg_result($res,$i,consumidor_cidade);
						$consumidor_estado	= pg_result($res,$i,consumidor_estado);
						$mao_de_obra		= pg_result($res,$i,mao_de_obra);
						$qtde_km_calculada	= pg_result($res,$i,qtde_km_calculada);
						$serie_peca			= pg_result($res,$i,serie_peca);
						$nota_fiscal_origem = pg_result($res,$i,nota_fiscal_origem);
						$compra				= pg_result($res,$i,compra);
						$peca_referencia	= pg_result($res,$i,peca_referencia);
						$peca_descricao		= pg_result($res,$i,peca_descricao);
						$defeito_descricao	= pg_result($res,$i,defeito_descricao);
						$qtde				= pg_result($res,$i,qtde);
						$pecas				= pg_result($res,$i,pecas);
						$linha_nome			= pg_result($res,$i,linha_nome);
						$nota_fiscal_mao_de_obra = pg_result($res,$i,nota_fiscal_mao_de_obra);
						$nota_fiscal_serie_mao_de_obra = pg_result($res,$i,nota_fiscal_serie_mao_de_obra);
						$mao_obra_extrato   = pg_result($res,$i,mao_obra_extrato);
						$extrato			= pg_result($res,$i,extrato);
						
						if(strlen($compra)==0 and !empty($posto)){
                            $sqlx = " SELECT SUM(preco * qtde) AS total_nf_peca
										 FROM tbl_os_extra
										 JOIN tbl_extrato           USING (extrato)
										 JOIN tbl_os_produto        USING (os)
										 JOIN tbl_os_item           USING (os_produto)
										 JOIN tbl_peca              USING (peca)
										 JOIN tbl_servico_realizado USING (servico_realizado)
										WHERE tbl_os_extra.extrato = $extrato
										  AND tbl_peca.fabrica     = $login_fabrica
										  AND tbl_extrato.fabrica  = $login_fabrica
										  AND tbl_extrato.posto    = $posto";
							$resx = pg_exec($con, $sqlx);

							if(pg_numrows($resx)>0){
								$compra = pg_result($resx,0,total_nf_peca);
							}
						}


						if($os == $os_ant){
							$serie_peca = $serie_ant;
							$mao_de_obra = 0;
							$qtde_km_calculada = 0;
						}
						$os_ant = $os;
						$serie_ant = $serie_peca;

						//Soma para totalizar
						$soma_total_nota		+= $total_nota;
						$soma_qtde_km			+= $qtde_km;
						$soma_mao_de_obra		+= $mao_de_obra;
						$soma_qtde_km_calculada	+= $qtde_km_calculada;
						$soma_compra			+= $compra;
						$soma_qtde				+= $qtde;
						$soma_pecas				+= $pecas;
						//=================
							$conteudo .= "<tr>
											<td>".$codigo_posto."<font color='#FFFFFF'>'</font></td>
											<td>".$posto_nome."</td>
											<td>".$estado."</td>
											<td>".$nota_fiscal_serie_mao_de_obra."</td>
											<td>".$nota_fiscal_mao_de_obra."</td>
											<td>".number_format($mao_obra_extrato,2,',','.')."</td>
											<td>".number_format($qtde_km,2,',','.')."</td>
											<td>".$sua_os."</td>
											<td>".$data_fechamento."</td>
											<td>".$produto_referencia."</td>
											<td>".$produto_descricao."</td>
											<td>".$serie_os."<font color='#FFFFFF'>'</font></td>
											<td>".$data_fabricacao."</td>
											<td>".$revenda_nome."</td>
											<td>".$cidade_nome."</td>
											<td>".$consumidor_nome."</td>
											<td>".$consumidor_cidade."</td>
											<td>".$consumidor_estado."</td>
											<td>".number_format($mao_de_obra,2,',','.')."</td>
											<td>".number_format($qtde_km_calculada,2,',','.')."</td>
											<td>".$serie_peca."</td>
											<td>".$nota_fiscal."</td>
											<td>".number_format($compra,2,',','.')."</td>
											<td>".$peca_referencia."</td>
											<td>".$peca_descricao."</td>
											<td>".$defeito_descricao."</td>
											<td>".number_format($qtde,2,',','.')."</td>
											<td>".number_format($pecas*$qtde,2,',','.')."</td>
											<td>".$linha_nome."</td>
										</tr>";

										
					}

					$conteudo .= "<tr bgcolor='#CCCCCC'>
											<td colspan='18' align='center'><b>Totais</b></td>
											<td>".number_format($soma_mao_de_obra,2,',','.')."</td>
											<td>".number_format($soma_qtde_km_calculada,2,',','.')."</td>
											<td colspan='6'>&nbsp;</td>
											<td>".number_format($soma_qtde,2,',','.')."</td>
											<td>".number_format($soma_pecas,2,',','.')."</td>
											<td>&nbsp;</td>
										</tr>
									</table>";
				}
			}

			else if($relatorio == 3){

				if(!empty($estado)){
					$join_estado = " 
						JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto $condEstado  
						JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica 
					 ";
				}

				$sql = "SELECT  tbl_os.os, tbl_os.mao_de_obra, 
								tbl_os.pecas, 
								tbl_os.qtde_km_calculada, 
								tbl_os.data_fechamento, 
								tbl_os.data_nf 
								INTO TEMP tmp_os_qtde_total 
								FROM tbl_os
								JOIN tbl_os_extra ON tbl_os.os            = tbl_os_extra.os
								JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
								$join_estado
								$join_nota
								WHERE tbl_os.fabrica = $login_fabrica
								AND tbl_extrato.data_geracao::date BETWEEN '$data_inicial' and '$data_final'
								$condPosto2;

						CREATE INDEX tmp_os_qtde_total_OS ON tmp_os_qtde_total(os);

						CREATE INDEX tmp_os_qtde_total_MO ON tmp_os_qtde_total(mao_de_obra);

                        SELECT tmp_os_qtde_total.mao_de_obra, SUM(tmp_os_qtde_total.mao_de_obra) AS total_os
                          INTO TEMP tmp_os_mo_total
                          FROM tmp_os_qtde_total
                         WHERE (tmp_os_qtde_total.data_fechamento - tmp_os_qtde_total.data_nf) <= 90
                         GROUP BY tmp_os_qtde_total.mao_de_obra;

                        SELECT tmp_os_qtde_total.mao_de_obra, SUM(tmp_os_qtde_total.mao_de_obra) AS total_os_mais_90
                          INTO TEMP tmp_os_mo_total_mais_90
                          FROM tmp_os_qtde_total
                         WHERE (tmp_os_qtde_total.data_fechamento - tmp_os_qtde_total.data_nf) >  90
                         GROUP BY tmp_os_qtde_total.mao_de_obra;

						SELECT tmp_os_qtde_total.mao_de_obra, SUM(tmp_os_qtde_total.qtde_km_calculada) AS total_km
                          INTO TEMP tmp_os_qtde_total_km
                          FROM tmp_os_qtde_total
                         WHERE (tmp_os_qtde_total.data_fechamento - tmp_os_qtde_total.data_nf) <= 90
                         GROUP BY tmp_os_qtde_total.mao_de_obra;

						SELECT tmp_os_qtde_total.mao_de_obra, SUM(tmp_os_qtde_total.qtde_km_calculada) AS total_km_mais_90
                          INTO TEMP tmp_os_qtde_total_km_mais_90
                          FROM tmp_os_qtde_total
                         WHERE (tmp_os_qtde_total.data_fechamento - tmp_os_qtde_total.data_nf) >  90
			 GROUP BY tmp_os_qtde_total.mao_de_obra";

						$resT = pg_query($con, $sql);

				$sql = "SELECT COUNT(tmp_os_qtde_total.os) AS qtde_os,
						tmp_os_qtde_total.mao_de_obra,
						tmp_os_mo_total.total_os,
						tmp_os_mo_total_mais_90.total_os_mais_90,
						tmp_os_qtde_total_km.total_km,
						tmp_os_qtde_total_km_mais_90.total_km_mais_90
						FROM tmp_os_qtde_total
						LEFT JOIN tmp_os_mo_total              ON tmp_os_mo_total.mao_de_obra              = tmp_os_qtde_total.mao_de_obra
						LEFT JOIN tmp_os_qtde_total_km         ON tmp_os_qtde_total_km.mao_de_obra         = tmp_os_qtde_total.mao_de_obra
						LEFT JOIN tmp_os_mo_total_mais_90      ON tmp_os_mo_total_mais_90.mao_de_obra      = tmp_os_qtde_total.mao_de_obra
						LEFT JOIN tmp_os_qtde_total_km_mais_90 ON tmp_os_qtde_total_km_mais_90.mao_de_obra = tmp_os_qtde_total.mao_de_obra
						GROUP BY tmp_os_qtde_total.mao_de_obra,
						tmp_os_mo_total.total_os,
						tmp_os_mo_total_mais_90.total_os_mais_90,
						tmp_os_qtde_total_km.total_km,
						tmp_os_qtde_total_km_mais_90.total_km_mais_90;";
				$res = pg_query($con,$sql);
				$msg_erro = pg_last_error($con);
				
				if(pg_numrows($res) > 0){

					$botao = "<input type='button' value='DOWNLOAD RELATÓRIO' onclick=\" window.location.href='xls/rel_fechamento_servico_$mes_aux_$ano_aux_$login_fabrica.xls'\">";

					$fp = fopen ("xls/rel_fechamento_servico_$mes_aux_$ano_aux_$login_fabrica.xls","w");
					
					$conteudo = "<table border='1'>
									<tr>
										<td align='center' bgcolor='#596d9b' colspan='7'><font color='#FFFFFF'>Relatório de Fechamento (Serviços)</font></td></tr>
										<td bgcolor='#596d9b'>QT OS</td>
										<td bgcolor='#596d9b'>TAXA UNI</td>
										<td bgcolor='#596d9b'>TOTAL</td>
										<td bgcolor='#596d9b'>VL -90</td>
										<td bgcolor='#596d9b'>VL +90</td>
										<td bgcolor='#596d9b'>KM -90</td>
										<td bgcolor='#596d9b'>KM +90</td>
									</tr>";

					for($i = 0; $i < pg_numrows($res); $i++){
						$qtde_os			= pg_result($res,$i,qtde_os);
						$mao_de_obra		= pg_result($res,$i,mao_de_obra);
						$total_os			= pg_result($res,$i,total_os);
						$total_os_mais_90	= pg_result($res,$i,total_os_mais_90);
						$total_km			= pg_result($res,$i,total_km);
						$total_km_mais_90	= pg_result($res,$i,total_km_mais_90);
						$total				= $qtde_os * $mao_de_obra;

						//Soma para totalizar
						$soma_qtde_os			+= $qtde_os;
						$soma_mao_de_obra		+= $mao_de_obra;
						$soma_total				+= $total;
						$soma_total_os			+= $total_os;
						$soma_total_os_mais_90  += $total_os_mais_90;
						$soma_total_km			+= $total_km;
						$soma_total_km_mais_90  += $total_km_mais_90;
						//===================

						$conteudo .= "<tr>
										<td>".$qtde_os."</td>
										<td>".number_format($mao_de_obra,2,',','.')."</td>
										<td>".number_format($total,2,',','.')."</td>
										<td>".number_format($total_os,2,',','.')."</td>
										<td>".number_format($total_os_mais_90,2,',','.')."</td>
										<td>".number_format($total_km,2,',','.')."</td>
										<td>".number_format($total_km_mais_90,2,',','.')."</td>
									  </tr>";
					}
					$conteudo .= "<tr>
										<td bgcolor='#CCCCCC'>".$soma_qtde_os."</td>
										<td bgcolor='#CCCCCC'>".number_format($soma_mao_de_obra,2,',','.')."</td>
										<td bgcolor='#CCCCCC'>".number_format($soma_total,2,',','.')."</td>
										<td bgcolor='#CCCCCC'>".number_format($soma_total_os,2,',','.')."</td>
										<td bgcolor='#CCCCCC'>".number_format($soma_total_os_mais_90,2,',','.')."</td>
										<td bgcolor='#CCCCCC'>".number_format($soma_total_km,2,',','.')."</td>
										<td bgcolor='#CCCCCC'>".number_format($soma_total_km_mais_90,2,',','.')."</td>
									  </tr></table>";

				}

			}

			else if($relatorio == "4"){
				
				if(!empty($estado)){
					$join_estado = " 
						JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto $condEstado  
						JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica 
					 ";
				}

				$sql = "SELECT  tbl_peca.peca,
								tbl_peca.referencia,
								tbl_peca.descricao,
								COUNT(tbl_os_item.peca) AS qtde_peca
								INTO TEMP tmp_total_pecas_atendidas
							FROM tbl_os
							JOIN tbl_os_extra   ON tbl_os.os              = tbl_os_extra.os
							JOIN tbl_extrato    ON tbl_os_extra.extrato   = tbl_extrato.extrato AND tbl_extrato.fabrica = {$login_fabrica} 
							JOIN tbl_os_produto ON tbl_os_produto.os      = tbl_os.os
							JOIN tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_item.fabrica_i = $login_fabrica
							JOIN tbl_peca       ON tbl_peca.peca          = tbl_os_item.peca          AND tbl_peca.fabrica      = $login_fabrica
							$join_estado
							$join_nota
							WHERE tbl_os_item.fabrica_i = $login_fabrica
							AND tbl_extrato.data_geracao::date BETWEEN '$data_inicial' and '$data_final'
							$condPosto2
							GROUP BY 
								tbl_peca.peca,
								tbl_peca.referencia,
								tbl_peca.descricao;

						CREATE INDEX tmp_total_pecas_atendidas_peca ON tmp_total_pecas_atendidas(peca)";
				$resT = pg_query($con, $sql);

				$sql = "SELECT tmp_total_pecas_atendidas.peca,
								COUNT(tbl_os_item.peca) AS qtde_peca_menos_90
								INTO TEMP tmp_qtde_peca_menos_90
							FROM tbl_os
							JOIN tbl_os_extra              ON tbl_os.os                      = tbl_os_extra.os
							JOIN tbl_extrato               ON tbl_os_extra.extrato           = tbl_extrato.extrato AND tbl_extrato.fabrica = {$login_fabrica} 
							JOIN tbl_os_produto            ON tbl_os_produto.os              = tbl_os.os
							JOIN tbl_os_item               ON tbl_os_item.os_produto         = tbl_os_produto.os_produto AND tbl_os_item.fabrica_i = $login_fabrica
							JOIN tmp_total_pecas_atendidas ON tmp_total_pecas_atendidas.peca = tbl_os_item.peca
							WHERE tbl_os.fabrica = $login_fabrica
							AND (tbl_os.data_fechamento - tbl_os.data_nf) <= 90
							AND tbl_extrato.data_geracao::date BETWEEN '$data_inicial' and '$data_final'
							GROUP BY tmp_total_pecas_atendidas.peca;

						SELECT tmp_total_pecas_atendidas.peca,
								COUNT(tbl_os_item.peca) AS qtde_peca_mais_90
								INTO TEMP tmp_qtde_peca_mais_90
							FROM tbl_os
							JOIN tbl_os_extra              ON tbl_os.os                      = tbl_os_extra.os
							JOIN tbl_extrato               ON tbl_os_extra.extrato           = tbl_extrato.extrato AND tbl_extrato.fabrica = {$login_fabrica} 
							JOIN tbl_os_produto            ON tbl_os_produto.os              = tbl_os.os
							JOIN tbl_os_item               ON tbl_os_item.os_produto         = tbl_os_produto.os_produto AND tbl_os_item.fabrica_i = $login_fabrica
							JOIN tmp_total_pecas_atendidas ON tmp_total_pecas_atendidas.peca = tbl_os_item.peca
							WHERE tbl_os.fabrica = $login_fabrica
							AND (tbl_os.data_fechamento - tbl_os.data_nf) > 90
							AND tbl_extrato.data_geracao::date BETWEEN '$data_inicial' and '$data_final'
							GROUP BY tmp_total_pecas_atendidas.peca";
				$resT = pg_query($con, $sql);

				$sql = "SELECT tmp_total_pecas_atendidas.peca,
								SUM(tbl_os_item.custo_peca) AS valor_peca
								INTO TEMP tmp_valor_pecas
							FROM tbl_os
							JOIN tbl_os_extra              ON tbl_os.os                      = tbl_os_extra.os
							JOIN tbl_extrato               ON tbl_os_extra.extrato           = tbl_extrato.extrato AND tbl_extrato.fabrica = {$login_fabrica} 
							JOIN tbl_os_produto            ON tbl_os.os                      = tbl_os_produto.os
							JOIN tbl_os_item               ON tbl_os_produto.os_produto      = tbl_os_item.os_produto AND tbl_os_item.fabrica_i = $login_fabrica
							JOIN tmp_total_pecas_atendidas ON tmp_total_pecas_atendidas.peca = tbl_os_item.peca
							WHERE tbl_extrato.data_geracao::date BETWEEN '$data_inicial' and '$data_final'
							GROUP BY tmp_total_pecas_atendidas.peca";
				$resT = pg_query($con, $sql);

				$sql = "SELECT tmp_total_pecas_atendidas.peca, SUM(tbl_os.qtde_km_calculada) AS qtde_km_por_peca
                          INTO TEMP tmp_qtde_km_por_peca
                          FROM tbl_os
                          JOIN tbl_os_extra              ON tbl_os.os                      = tbl_os_extra.os
                          JOIN tbl_extrato               ON tbl_os_extra.extrato           = tbl_extrato.extrato AND tbl_extrato.fabrica = {$login_fabrica} 
                          JOIN tbl_os_produto            ON tbl_os_produto.os              = tbl_os.os
                          JOIN tbl_os_item               ON tbl_os_item.os_produto         = tbl_os_produto.os_produto AND tbl_os_item.fabrica_i = $login_fabrica
                          JOIN tmp_total_pecas_atendidas ON tmp_total_pecas_atendidas.peca = tbl_os_item.peca
                         WHERE tbl_os.fabrica                 = $login_fabrica
                           AND tbl_extrato.data_geracao::date BETWEEN '$data_inicial' and '$data_final'
                         GROUP BY tmp_total_pecas_atendidas.peca";
				$resT = pg_query($con, $sql);

				$sql = "SELECT tmp_total_pecas_atendidas.peca,
								SUM(tbl_os.mao_de_obra) AS qtde_mao_obra_por_peca
								INTO TEMP tmp_qtde_mao_obra_por_peca
							FROM tbl_os								
							JOIN tbl_os_extra              ON tbl_os.os                      = tbl_os_extra.os
							JOIN tbl_extrato               ON tbl_os_extra.extrato           = tbl_extrato.extrato AND tbl_extrato.fabrica = {$login_fabrica} 
							JOIN tbl_os_produto            ON tbl_os_produto.os              = tbl_os.os
							JOIN tbl_os_item               ON tbl_os_item.os_produto         = tbl_os_produto.os_produto AND tbl_os_item.fabrica_i = $login_fabrica
							JOIN tmp_total_pecas_atendidas ON tmp_total_pecas_atendidas.peca = tbl_os_item.peca
							WHERE tbl_os.fabrica = $login_fabrica
							AND tbl_extrato.data_geracao::date BETWEEN '$data_inicial' and '$data_final'
							GROUP BY tmp_total_pecas_atendidas.peca";
				$resT = pg_query($con, $sql);

				$sql = "SELECT tmp_total_pecas_atendidas.peca,
								tmp_total_pecas_atendidas.referencia,
								tmp_total_pecas_atendidas.descricao,
								tmp_total_pecas_atendidas.qtde_peca,
								tmp_qtde_peca_menos_90.qtde_peca_menos_90,
								tmp_qtde_peca_mais_90.qtde_peca_mais_90,
								tmp_valor_pecas.valor_peca,
								tmp_qtde_km_por_peca.qtde_km_por_peca,
								tmp_qtde_mao_obra_por_peca.qtde_mao_obra_por_peca
							FROM tmp_total_pecas_atendidas
							LEFT JOIN tmp_qtde_peca_menos_90     ON tmp_qtde_peca_menos_90.peca     = tmp_total_pecas_atendidas.peca
							LEFT JOIN tmp_qtde_peca_mais_90      ON tmp_qtde_peca_mais_90.peca      = tmp_total_pecas_atendidas.peca
							LEFT JOIN tmp_valor_pecas            ON tmp_valor_pecas.peca            = tmp_total_pecas_atendidas.peca
							LEFT JOIN tmp_qtde_km_por_peca       ON tmp_qtde_km_por_peca.peca       = tmp_total_pecas_atendidas.peca
							LEFT JOIN tmp_qtde_mao_obra_por_peca ON tmp_qtde_mao_obra_por_peca.peca = tmp_total_pecas_atendidas.peca";
				$res = pg_query($con,$sql);
				$msg_erro = pg_last_error($con);
				#echo nl2br($sql);
				if(pg_numrows($res) > 0){
					
					$botao = "<input type='button' value='DOWNLOAD RELATÓRIO' onclick=\" window.location.href='xls/rel_fechamento_pecas_$mes_aux_$ano_aux_$login_fabrica.xls'\">";

					$fp = fopen ("xls/rel_fechamento_pecas_$mes_aux_$ano_aux_$login_fabrica.xls","w");
					
					$conteudo = "<table border='1'>
									<tr ><td align='center' bgcolor='#596d9b' colspan='8'><font color='#FFFFFF'>Relatório Fechamento (Peças)</font></td></tr>
										<td bgcolor='#596d9b'>PEÇA</td>
										<td bgcolor='#596d9b'>DESCRIÇÃO</td>
										<td bgcolor='#596d9b'>QT ATEND</td>
										<td bgcolor='#596d9b'>QT -90</td>
										<td bgcolor='#596d9b'>QT +90</td>
										<td bgcolor='#596d9b'>VL PEÇA</td>
										<td bgcolor='#596d9b'>KM</td>
										<td bgcolor='#596d9b'>SERVICO</td>
									</tr>";

					for($i = 0; $i < pg_numrows($res); $i++){
						$referencia				= pg_result($res,$i,referencia);
						$descricao				= pg_result($res,$i,descricao);
						$qtde_peca				= pg_result($res,$i,qtde_peca);
						$qtde_peca_menos_90		= pg_result($res,$i,qtde_peca_menos_90);
						$qtde_peca_mais_90		= pg_result($res,$i,qtde_peca_mais_90);
						$valor_peca				= pg_result($res,$i,valor_peca);
						$qtde_km_por_peca		= pg_result($res,$i,qtde_km_por_peca);
						$qtde_mao_obra_por_peca = pg_result($res,$i,qtde_mao_obra_por_peca);
						
						//Soma para totalizar
						$soma_valor_peca			 += $valor_peca;
						$soma_qtde_km_por_peca		 += $qtde_km_por_peca;
						$soma_qtde_mao_obra_por_peca += $qtde_mao_obra_por_peca;
						$total_peca += $qtde_peca;
						$total_peca_menos_90 += $qtde_peca_menos_90;
						$total_peca_mais_90 += $qtde_peca_mais_90;
						//===================

						$conteudo .= "<tr>
										<td>".$referencia."</td>
										<td nowrap>".$descricao."</td>
										<td>".$qtde_peca."</td>
										<td>".$qtde_peca_menos_90."</td>
										<td>".$qtde_peca_mais_90."</td>
										<td>".number_format($valor_peca,2,',','.')."</td>
										<td>".number_format($qtde_km_por_peca,2,',','.')."</td>
										<td>".number_format($qtde_mao_obra_por_peca,2,',','.')."</td>
									  </tr>";
					}
					
					$conteudo .= "<tr>
										<td bgcolor='#CCCCCC'>&nbsp;</td>
										<td bgcolor='#CCCCCC'>&nbsp;</td>
										<td bgcolor='#CCCCCC'>".$total_peca."</td>
										<td bgcolor='#CCCCCC'>".$total_peca_menos_90."</td>
										<td bgcolor='#CCCCCC'>".$total_peca_mais_90."</td>
										<td bgcolor='#CCCCCC'>".number_format($soma_valor_peca,2,',','.')."</td>
										<td bgcolor='#CCCCCC'>".number_format($soma_qtde_km_por_peca,2,',','.')."</td>
										<td bgcolor='#CCCCCC'>".number_format($soma_qtde_mao_obra_por_peca,2,',','.')."</td>
									  </tr>
									  </table>";
				}

			}

			else if($relatorio == 5){
				
				if(!empty($estado)){
					$join_estado = " 
									JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto $condEstado  
									JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica   
								";
				}

				$sql = "SELECT DISTINCT tbl_peca.peca,
										tbl_peca.referencia,
										tbl_peca.descricao            AS peca_nome,
										tbl_defeito.defeito,
										tbl_defeito.codigo_defeito,
										tbl_defeito.descricao,
										count(tbl_os_item.qtde)       AS qtde_peca,
										tbl_os_item.preco             AS pecas,
										SUM(tbl_os.qtde_km_calculada) AS km,
										SUM(tbl_os.mao_de_obra)       AS mao_obra
									FROM tbl_os
									JOIN tbl_os_extra   ON tbl_os_extra.os        = tbl_os.os
									JOIN tbl_extrato    ON tbl_extrato.extrato    = tbl_os_extra.extrato      AND tbl_extrato.fabrica   = $login_fabrica
									JOIN tbl_os_produto ON tbl_os_produto.os      = tbl_os.os
									JOIN tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_item.fabrica_i = $login_fabrica
									JOIN tbl_defeito    ON tbl_defeito.defeito    = tbl_os_item.defeito       AND tbl_defeito.fabrica   = $login_fabrica
									JOIN tbl_peca       ON tbl_peca.peca          = tbl_os_item.peca          AND tbl_peca.fabrica      = $login_fabrica
										$join_estado
										$join_nota
								WHERE tbl_os.fabrica = $login_fabrica
										AND tbl_extrato.data_geracao::date BETWEEN '$data_inicial' and '$data_final'
										$condPosto2
								GROUP BY tbl_peca.peca,
										tbl_peca.referencia,
										tbl_peca.descricao,
										tbl_os_item.preco,
										tbl_defeito.defeito,
										tbl_defeito.codigo_defeito,
										tbl_defeito.descricao;";
				$res = pg_query($con,$sql);
				$msg_erro = pg_last_error($con);
				
				if(pg_numrows($res) > 0){
					
					$botao = "<input type='button' value='DOWNLOAD RELATÓRIO' onclick=\" window.location.href='xls/rel_fechamento_pecas_defeitos_$mes_aux_$ano_aux_$login_fabrica.xls'\">";

					$fp = fopen ("xls/rel_fechamento_pecas_defeitos_$mes_aux_$ano_aux_$login_fabrica.xls","w");
					
					$conteudo = "<table border='1'>
									<tr><td align='center' bgcolor='#596d9b' colspan='8'><font color='#FFFFFF'>Relatório de Fechamento (Peças x Defeitos)</font></td></tr>
									<tr>
										<td bgcolor='#596d9b'>PEÇA</td>
										<td bgcolor='#596d9b'>DESCRIÇÃO</td>
										<td bgcolor='#596d9b'>DEFEITO /DESCRIÇÃO</td>
										<td bgcolor='#596d9b'> QT ATEND</td>
										<td bgcolor='#596d9b'>VL PEÇA</td>
										<td bgcolor='#596d9b'>KM</td>
										<td bgcolor='#596d9b'>SERVICO</td>
										<td bgcolor='#596d9b'>TOTAL</td>
									</tr>";

					for($i = 0; $i < pg_numrows($res); $i++){
						$referencia		= pg_result($res,$i,referencia);
						$peca_nome		= pg_result($res,$i,peca_nome);
						$codigo_defeito = pg_result($res,$i,codigo_defeito);
						$descricao		= pg_result($res,$i,descricao);
						$qtde_peca		= pg_result($res,$i,qtde_peca);
						$pecas			= pg_result($res,$i,pecas) * $qtde_peca;
						$km				= pg_result($res,$i,km);
						$mao_obra		= pg_result($res,$i,mao_obra);
						#$total			= pg_result($res,$i,total); 
					
						$total = $km + $mao_obra + $pecas;
						//Soma para totalizar
						$soma_pecas		+= $pecas;
						$soma_km		+= $km;
						$soma_mao_obra	+= $mao_obra;
						$soma_total		+= $total;
						$total_pecas	+= $qtde_peca;
						//==================

						$conteudo .= "<tr>
										<td>".$referencia."</td>
										<td>".$peca_nome."</td>
										<td>".$descricao."</td>
										<td>".$qtde_peca."</td>
										<td>".number_format($pecas,2,',','.')."</td>
										<td>".number_format($km,2,',','.')."</td>
										<td>".number_format($mao_obra,2,',','.')."</td>
										<td>".number_format($total,2,',','.')."</td>
									  </tr>";
					}

					$conteudo .= "<tr>
										<td bgcolor='#CCCCCC'>&nbsp;</td>
										<td bgcolor='#CCCCCC'>&nbsp;</td>
										<td bgcolor='#CCCCCC'>&nbsp;</td>
										<td bgcolor='#CCCCCC'>$total_pecas</td>
										<td bgcolor='#CCCCCC'>".number_format($soma_pecas,2,',','.')."</td>
										<td bgcolor='#CCCCCC'>".number_format($soma_km,2,',','.')."</td>
										<td bgcolor='#CCCCCC'>".number_format($soma_mao_obra,2,',','.')."</td>
										<td bgcolor='#CCCCCC'>".number_format($soma_total,2,',','.')."</td>
									  </tr>
									</table>";

				}
			}

			fputs ($fp, $conteudo);

		}
	}

?>

<style type="text/css">
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

</style>

<?php
	$layout_menu = "gerencia";
	$title = "RELATÓRIOS MENSAIS";
	include "cabecalho.php";
?>

<script type="text/javascript" src="../plugins/shadowbox/shadowbox.js"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script type="text/javascript">

$().ready(function(){
    Shadowbox.init();

    $("#tipo_nota").change(function(){
        if($(this).val()==""){
            $("input[rel=2]").attr("disabled",false);
        }else{
            $("input[rel=2]").attr("disabled",true);
            $("input[rel=1]").prop("checked","checked");
        }
    });
});

function gravaDados(name, valor){
    try{
        $("input[name="+name+"]").val(valor);
    } catch(err){
        return false;
    }
}

function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento,num_posto){
    gravaDados('filtro_posto_ref',codigo_posto);
    gravaDados('filtro_posto_desc',nome);
}

function pesquisaPosto(campo,tipo){
    var campo = campo.value;

    if (jQuery.trim(campo).length > 2){
        Shadowbox.open({
            content:  "posto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
            player:   "iframe",
            title:    "Pesquisa Posto",
            width:    800,
            height:   500
        });
    }else
        alert("Informar toda ou parte da informação para realizar a pesquisa!");
}
</script>

<?php if (!empty($msg_erro)) {
	if (strpos($msg_erro, 'statement timeout'))
		$msg_erro = "Tempo excedido, tentar novamente.";
?>
		<table align="center" width="700" class="msg_erro">
			<tr><td><?php echo $msg_erro; ?></td></tr>
		</table>
<?php } ?>

<form name="frm_pesquisa" method="post">
	<table width="700" align="center" class="formulario">
		<caption class="titulo_tabela">Parâmetros de Pesquisa</caption>
		<tr><td colspan="3">&nbsp;</td></tr>
		<tr>
			<td width="50">&nbsp;</td>
			<td>
				Ano <br />			
				<select name="ano" class="frm">
					<?php
					$ano = date(Y);
					for($i = $ano; $i >= ($ano-3); $i--){
						echo '<option value="'.$i.'"'.($i == $ano_aux ? ' selected="selected"' : '').'>
						'.$i."</option>\n";
					}
				?>
				</select>
			</td>
			<td>
				Mês <br />
				<?php
					$mes_extenso = array(
						'01' => "janeiro",  '02' => "fevereiro", '03' => "março",    '04' => "abril",
						'05' => "maio",     '06' => "junho",     '07' => "julho",    '08' => "agosto",
						'09' => "setembro", '10' => "outubro",   '11' => "novembro", '12' => "dezembro");
				?>
				<select name="mes" class="frm" id="mes"><?php
					foreach ($mes_extenso as $k => $v) {
						echo '<option value="'.$k.'"'.($mes == $k ? ' selected="selected"' : '').'>
						'.ucwords($v)."</option>\n";
					}?>
				</select>
			</td>
		</tr>
		<tr><td colspan="3">&nbsp;</td></tr>
		<tr>
			<td width="50">&nbsp;</td>
			<td>
				Posto Referência <br />
				<input type="text" name="filtro_posto_ref" id="filtro_posto_ref" class='frm' value="<?php echo $codigo_posto_aux;?>" />
				<img src="../imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle"  onclick=" pesquisaPosto (document.frm_pesquisa.filtro_posto_ref, 'codigo');">
			</td>
			<td>
				Posto Descrição <br />
				<input type="text" name="filtro_posto_desc" id="filtro_posto_desc" class='frm' style="width:341px" value="<?php echo $posto_nome_aux;?>" />
				<img src="../imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle"  onclick=" pesquisaPosto (document.frm_pesquisa.filtro_posto_desc, 'nome');">
			</td>
		</tr>
		<tr><td colspan="3">&nbsp;</td></tr>
		<tr>
			<td width="50">&nbsp;</td>
			<td>
				Estado <br />
				<?php
					$array_estado = array(
						"AC" => "AC - Acre"                , "AL" => "AL - Alagoas"        , "AM" => "AM - Amazonas" , 
						"AP" => "AP - Amapá"               , "BA" => "BA - Bahia"          , "CE" => "CE - Ceará"    , "DF" => "DF - Distrito Federal" , 
						"ES" => "ES - Espírito Santo"      , "GO" => "GO - Goiás"          , "MA" => "MA - Maranhão" , "MG" => "MG - Minas Gerais"     , 
						"MS" => "MS - Mato Grosso do Sul"  , "MT" => "MT - Mato Grosso"    , "PA" => "PA - Pará"     , "PB" => "PB - Paraíba"          , 
						"PE" => "PE - Pernambuco"          , "PI" => "PI - Piauí"          , "PR" => "PR - Paraná"   , "RJ" => "RJ - Rio de Janeiro"   , 
						"RN" => "RN - Rio Grande do Norte" , "RO" => "RO - Rondônia"       , "RR" => "RR - Roraima"  , 
						"RS" => "RS - Rio Grande do Sul"   , "SC" => "SC - Santa Catarina" , "SE" => "SE - Sergipe"  , 
						"SP" => "SP - São Paulo"           , "TO" => "TO - Tocantins");
					?>
				<select name="estado" class="frm" id="estado">
					<option value="" selected>Selecione</option>
					<?php
					foreach ($array_estado as $k => $v) {
					echo '<option value="'.$k.'"'.($estado_aux == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
					}?>
				</select>
            </td>
            <td>
                Tipo de Notas<br />
                <select name="tipo_nota" id="tipo_nota" class="frm">
                    <option value="">&nbsp;</option>
                    <option value="baixadas">NOTAS FISCAIS PAGAS NO PERÍODO</option>
                    <option value="pendentes">NOTAS FISCAIS PENDENTES PARA PAGAMENTO</option>
                    <option value="sem_nf">EXTRATO NÃO EMITIDO NOTAS</option>
                </select>
			</td>
		</tr>
		<tr><td colspan="3">&nbsp;</td></tr>
		<tr>
			<td width="50">&nbsp;</td>
			<td colspan="2">
				<fieldset >
					<legend>Relatórios</legend>
					<table width="100%" class="formulario">
						<tr>
							<td>
								<input type="radio" name="relatorio"  rel="1" value="1" CHECKED>&nbsp;Relatório Fechamento Mensal Resumido
							</td>
						</tr>
						<tr>
							<td>
								<input type="radio" name="relatorio" rel="2" value="2" <?php echo ($relatorio == 2) ? "CHECKED" : "";?>>&nbsp;Relatório Fechamento Completo
							</td>
						</tr>
						<tr>
							<td>
								<input type="radio" name="relatorio" value="3" <?php echo ($relatorio == 3) ? "CHECKED" : "";?>>&nbsp;Relatório de Fechamento (Serviços)
							</td>
						</tr>
						<tr>
							<td>
								<input type="radio" name="relatorio" value="4" <?php echo ($relatorio == 4) ? "CHECKED" : "";?>>&nbsp;Relatório Fechamento (Peças)
							</td>
						</tr>
						<tr>
							<td>
								<input type="radio" name="relatorio" value="5" <?php echo ($relatorio == 5) ? "CHECKED" : "";?>>&nbsp;Relatório de Fechamento (Peças x Defeitos)
							</td>
						</tr>
					</table>
				</fieldset>
			</td>
		</tr>
		<tr><td colspan="3">&nbsp;</td></tr>
		<tr>
			<td colspan="3" align="center">
				<input type="submit" value="Gerar Relatório">
			</td>
		</tr>
	</table>
</form>

<?php
	#echo $conteudo;
	if(!empty($conteudo) AND empty($msg_erro)){
		echo "<br><center>".$botao."</center>";
	}

	if($_POST AND empty($conteudo)){
		echo "<center>Nenhum resultado encontrado";
	}
	
	include "rodape.php";
?>
