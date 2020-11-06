<?php 

include dirname(__FILE__) . '/../dbconfig_pg.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';

$fabrica_distrib = 63;

/*
* Cron Class
*/
$phpCron = new PHPCron($fabrica_distrib, __FILE__);
$phpCron->inicio();

$data        = date('Ymd-His');

$cond_pedido = "";
$arq_log_mode = ">";

if ($argv[1]) {
    $cond_pedido = " AND pedido =  ". $argv[1] ;
    $arq_log_mode = ">>";
}

//$vet['dest']    = 'ronaldo@telecontrol.com.br';
$vet['dest']    = 'gaspar.lucas@telecontrol.com.br';

$arquivos = "/tmp";
system ("mkdir -p -m 777 $arquivos/telecontrol");

$fp = fopen("$arquivos/telecontrol/embarque_novo_faturado_$data.txt", "a+");

if ($arq_log_mode == ">") {
    fwrite($fp, "Comecando\n");
}

$sql = "SELECT fabrica FROM tbl_fabrica WHERE parametros_adicionais ilike '%telecontrol_distrib%'";
$resX = pg_query($con, $sql);
while ($fabrica = pg_fetch_array($resX)) {
    $a_fabricas[] = $fabrica['fabrica'];
}
$fabricas = implode(',', $a_fabricas);

//$fabricas = substr($fabricas, 0,strlen($fabricas) - 1);

#colocar para testes apenas o ID do pedido que eu gerar - ANDREUS TIMM
$sql = "    SELECT pedido , posto, atende_pedido_faturado_parcial,fabrica
        FROM   tbl_pedido
        WHERE  fabrica = 114
        AND    distribuidor = 4311
        AND    data > '2008-07-12'
        AND    data > '2008-11-01'
        AND    data > '2010-04-30' 
        AND    tipo_pedido = 234
		AND    origem_cliente is not true
        AND    posto NOT IN (4311,970,6359,17702)
        AND    (status_pedido IS NULL OR status_pedido IN (1,2,5,7,8,9,10,11,12,29,22,26)) $cond_pedido
    UNION 
    SELECT pedido, posto, atende_pedido_faturado_parcial,fabrica
        FROM   tbl_pedido
        WHERE  fabrica = 119
        AND    distribuidor = 4311
        AND    data > '2008-07-12'
        AND    data > '2008-11-01'
        AND    data > '2010-04-30'
        AND    tipo_pedido = 236
		AND    origem_cliente is not true
        AND    posto NOT IN (4311,970,6359,17702)
        AND    (status_pedido IS NULL OR status_pedido IN (1,2,5,7,8,9,10,11,12,29,22,26)) $cond_pedido
    UNION
    SELECT pedido, posto, atende_pedido_faturado_parcial,fabrica
        FROM   tbl_pedido
        WHERE  fabrica = 81
        AND    distribuidor = 4311
        AND    tipo_pedido = 153
        AND    posto NOT IN (4311,970,6359,17702)
		AND    origem_cliente is not true
        AND    (status_pedido IS NULL OR status_pedido IN (1,2,5,7,8,9,10,11,12,29,22,26)) $cond_pedido
UNION
        SELECT pedido, posto, atende_pedido_faturado_parcial,fabrica
        FROM   tbl_pedido
        WHERE  fabrica = 10
        AND    distribuidor = 4311
        AND    data_aprovacao IS NOT NULL
        AND    tipo_pedido = 77
		AND    origem_cliente is not true
        AND    posto NOT IN (4311,970,6359,17702)
        AND    (status_pedido IS NULL OR status_pedido IN (1,2,5,7,8,9,10,11,12,29,22,26)) $cond_pedido
UNION
        SELECT pedido, posto, atende_pedido_faturado_parcial,fabrica
        FROM   tbl_pedido
        JOIN    tbl_tipo_pedido USING(tipo_pedido,fabrica)
        WHERE  tbl_pedido.fabrica in ($fabricas)
        AND    distribuidor = 4311
		AND    origem_cliente is not true
        AND    (pedido_faturado or upper(tbl_tipo_pedido.descricao) = 'FATURADO')
        AND    posto NOT IN (4311,970,6359,17702)
        AND    (status_pedido IS NULL OR status_pedido IN (1,2,5,7,8,9,10,11,12,29,22,26)) $cond_pedido
ORDER BY pedido ";

$resPEDIDO = pg_query($con, $sql);

if(strlen(pg_last_error($con))>0){
    $msg .= pg_last_error($con);
}

$pedido_old = 0;

for($i=0; $i<pg_num_rows($resPEDIDO); $i++){
    $log = '';
    $pedido                             = pg_fetch_result($resPEDIDO, $i, pedido);
    $posto                              = pg_fetch_result($resPEDIDO, $i, posto);
    $atende_pedido_faturado_parcial     = pg_fetch_result($resPEDIDO, $i, atende_pedido_faturado_parcial);
    $fabrica                            = pg_fetch_result($resPEDIDO, $i, fabrica);


		$cond_garantia = " AND tbl_embarque.garantia is false";
    
    $pedido_new = $pedido;
    $insert_embarque = false;

    if (($posto == 20682) and ($pedido_old != $pedido_new)) {
        $insert_embarque = true;
        $pedido_old = $pedido_new;
    }

    if ($atende_pedido_faturado_parcial == 'f') {
        #adicinado no chamado 3729605
        $sql_preco = "SELECT SUM(tbl_pedido_item.preco * tbl_pedido_item.qtde) as preco_itens
                        FROM tbl_pedido_item 
                        WHERE pedido = $pedido ";
        $res_preco = pg_query($con, $sql_preco);
        $preco_itens = pg_fetch_result($res_preco, 0, preco_itens);
        if($preco_itens < "50"){
#            continue 1;
        }

        # SQL para pegar todos ITEM do PEDIDO
        # verifica se tem todos itens solicitados no estoque
        $sql = "SELECT tbl_pedido_item.peca,
                         qtde - qtde_faturada_distribuidor - qtde_cancelada AS qtde,
                       tbl_pedido_item.pedido_item
                  FROM tbl_pedido_item
                 WHERE pedido = $pedido 
                AND qtde - qtde_faturada_distribuidor - qtde_cancelada > 0;";
        $resPecas = pg_query($con, $sql);
        $x = 0;

        if (pg_num_rows($resPecas) == 0) {
            $log .= "Pedido $pedido com todas as pecas atendidas \n";
            #print "Pedido $pedido com todas as pecas atendidas \n";
            continue;
        }

        $pedido_completo = true;

        for($j = 0; $j < pg_num_rows($resPecas); $j++) {
            $val_peca = pg_fetch_result($resPecas, $j, peca);
            $val_qtde = pg_fetch_result($resPecas, $j, qtde);
            $distribuidor = 4311;
			if(in_array($fabrica,[11,172])) {
				$sql  = "SELECT qtde, peca FROM tbl_posto_estoque WHERE posto = $distribuidor AND peca in (select peca from tbl_peca where fabrica in (11,172) and referencia in (select referencia from tbl_peca where peca = $val_peca)) AND qtde >= $val_qtde";
			}else{
				$sql  = "SELECT qtde, peca FROM tbl_posto_estoque WHERE posto = $distribuidor AND peca = $val_peca AND qtde >= $val_qtde";
			}	

            $resQtde = pg_query($con, $sql);
            if(pg_num_rows($resQtde) > 0){
                for($k=0; $k<pg_num_rows($resQtde); $k++){
                    $aux_qtde = pg_fetch_result($resQtde, $k, qtde);
                    $aux_peca = pg_fetch_result($resQtde, $k, peca);
                    if($aux_qtde < $val_qtde) {
                        $pedido_completo = false;
                    }
                }
            }else {
                 $sql_peca_alt = "SELECT tbl_posto_estoque.qtde, peca_para AS peca
                     FROM tbl_peca_alternativa
                     JOIN tbl_posto_estoque ON tbl_posto_estoque.peca = tbl_peca_alternativa.peca_para
                     WHERE tbl_peca_alternativa.peca_de = $val_peca
                     AND tbl_peca_alternativa.fabrica = $fabrica
                     AND tbl_posto_estoque.posto = $distribuidor
                     AND tbl_posto_estoque.qtde >= $val_qtde";
                $res_peca_alt = pg_query($con, $sql_peca_alt);

                $rows_peca_alt = pg_num_rows($res_peca_alt);

                if ($rows_peca_alt > 0) {
                    for ($k=0; $k<$rows_peca_alt; $k++) {
                        $aux_qtde = pg_fetch_result($res_peca_alt, $k, 'qtde');
                        $aux_peca = pg_fetch_result($res_peca_alt, $k, 'peca');

                        if($aux_qtde < $val_qtde) {
                            $pedido_completo = false;
                        }
                    }
                } else {
                    $pedido_completo = false;
                }
            }
        }
		$erro = "";

        if($pedido_completo == true) {
            $resBegin = pg_query($con, 'Begin;');

            while (list($peca, $qtde, $pedido_item) = pg_fetch_array($resPecas)){

                /*$sql  = "SELECT tbl_posto_estoque_localizacao.localizacao
                        FROM tbl_posto_estoque_localizacao
                        JOIN tbl_peca USING(peca)
                        WHERE peca = $peca
                        AND ((substr(tbl_posto_estoque_localizacao.localizacao,1,1) in ('R') and ascii(substr(tbl_posto_estoque_localizacao.localizacao,2,1))::numeric between '77' and '90') or
                            (substr(tbl_posto_estoque_localizacao.localizacao,1,1) in ('P') and substr(tbl_posto_estoque_localizacao.localizacao,2,2)  ~ E'\\\\d\\\\d' and substr(tbl_posto_estoque_localizacao.localizacao,2,2)::numeric >= '50' ) or substr(tbl_posto_estoque_localizacao.localizacao,1,3) = 'GAV' or tbl_peca.produto_acabado is not true )";
                $resl = pg_query($con, $sql);
                if (pg_num_rows($resl) == 0) {*/
                        $distribuidor = 4311;
                /*}else{
                        $distribuidor = 376542;
                }*/

                # Validação de estoque nas 2 fabricas Aulik e Pacific, pois se a peça for igual pode pegar de ambas. HD-6372761
                if (in_array($fabrica, [11,172])) {
                    # ve se tem estoque disponivel
                    $sql  = "SELECT qtde, peca FROM tbl_posto_estoque WHERE posto = $distribuidor AND peca = $peca AND qtde > 0";
                    $resX = pg_query($con,$sql);

                    if (pg_num_rows($resX) == 0) {
                        $id_peca = "";
                        $tinha_peca = "";
                        $fab_peca = ($fabrica == 11) ? 172 : 11;

                        $sql_pecas = " SELECT peca 
                                       FROM tbl_peca 
                                       WHERE referencia = (SELECT referencia FROM tbl_peca WHERE peca = $peca AND fabrica = $fabrica)
                                       AND fabrica = $fab_peca";
                        $res_pecas = pg_query($con, $sql_pecas);

                        $id_peca = pg_fetch_result($res_pecas, 0, 'peca'); 
						
						if(!empty($id_peca)) {
							$sql = "SELECT qtde FROM tbl_posto_estoque WHERE posto = $distribuidor AND peca = $id_peca AND qtde >= $qtde";
							$resX = pg_query($con, $sql);

							if (pg_num_rows($resX) > 0) {
								$sql_baixa_estoque = "UPDATE tbl_posto_estoque SET
									qtde = qtde - $qtde
									WHERE peca = $id_peca
									AND posto = $distribuidor";
								$res_baixa_estoque = pg_query($con, $sql_baixa_estoque);

								$sql_tinha_peca = "SELECT posto FROM tbl_posto_estoque WHERE posto = $distribuidor AND peca = $peca";
								$res_tinha_peca = pg_query($con, $sql_tinha_peca);
								$tinha_peca = pg_fetch_result($res_tinha_peca, 0, 'posto'); 

								if ($tinha_peca != "") {
									$sql_carga_estoque = "UPDATE tbl_posto_estoque SET
										qtde = qtde + $qtde
										WHERE peca = $peca
										AND posto = $distribuidor";
								$res_carga_estoque = pg_query($con, $sql_carga_estoque);
								} else {
									$sql_carga_estoque = "INSERT INTO tbl_posto_estoque (posto, peca, qtde) VALUES ($distribuidor, $peca, $qtde)";
									$res_carga_estoque = pg_query($con, $sql_carga_estoque);

								}
							}
						}
                    } 
                }

                $sql  = "SELECT qtde, peca FROM tbl_posto_estoque WHERE posto = $distribuidor AND peca = $peca AND qtde > 0";
                $resQtde = pg_query($con, $sql);
                if (pg_num_rows($resQtde) == 0){
                    $msg .= pg_last_error($con);
                    if (strlen($msg) > 0) {
                        $log .= "ERRO --> nao tem estoque PEDIDO=" . $pedido . " - POSTO=" . $posto . " - PECA=" . $peca . " - " . $referencia . " - EMBARQUE=" . $embarque . " - MSG=" . $msg . "\n";
                    }

                    $sql_peca_alt = "SELECT para as referencia_alternativa, peca_para AS peca_alternativa
                         FROM tbl_peca_alternativa
                         JOIN tbl_posto_estoque ON tbl_posto_estoque.peca = tbl_peca_alternativa.peca_para
                         WHERE tbl_peca_alternativa.peca_de = $peca
                         AND tbl_peca_alternativa.fabrica = $fabrica
                         AND tbl_posto_estoque.posto = $distribuidor
                         AND tbl_posto_estoque.qtde > 0";
                    $res_peca_alt = pg_query($con, $sql_peca_alt);

                    if (pg_num_rows($res_peca_alt) > 0) {
                        $referencia_alternativa = pg_fetch_result($res_peca_alt, 0, 'referencia_alternativa');
                        $peca_alternativa = pg_fetch_result($res_peca_alt, 0, 'peca_alternativa');

						$obs = utf8_decode("A peça $referencia foi alterada para $referencia_alternativa pelo fabricante.");
						$mensagem = utf8_decode("Prezado assistente. <br> Para o pedido <b>$pedido</b> substituímos a peça $referencia pela peça $referencia_alternativa pois ambas apresentam as mesmas características.");

                        $upd_peca_alternativa = "UPDATE tbl_pedido_item SET
                                peca_alternativa = $peca_alternativa,
                                obs = '$obs'
                            WHERE pedido_item = $pedido_item";
                        $res_peca_alternativa = pg_query($con, $upd_peca_alternativa);

                        $sql_comunicado = "INSERT INTO tbl_comunicado (
                                obrigatorio_site,
                                tipo,
                                ativo,
                                mensagem,
                                fabrica,
                                posto
                            ) VALUES (
                                't',
                                'Comunicado',
                                't',
                                '$mensagem',
                                $fabrica,
                                $posto
                            )";
                        $res_comunicado = pg_query($con, $sql_comunicado);

                        $log .= "Pedido $pedido Peca $referencia foi atendida pela alternativa $referencia_alternativa\n";

                        $peca = $peca_alternativa;
                        $referencia = $referencia_alternativa;
                    } else {
                        $log .= "Pedido $pedido Peca $peca - $referencia nao tem no estoque \n";
						continue;
                    }
                }

                if ($x == 0) {
                    
                    if (true === $insert_embarque) {
                        $sql = "INSERT INTO tbl_embarque (distribuidor, posto,garantia,fabrica) VALUES ($distribuidor,$posto,false, $fabrica) returning embarque";
                        $resX = pg_query($con, $sql);
                        if(strlen(pg_last_error($con))==0){
                            $embarque = pg_fetch_result($resX, 0, 'embarque');
                        }
                    } else {

                        $sql = "SELECT embarque FROM tbl_embarque left join embarque_telecontrol e using(embarque) WHERE distribuidor = $distribuidor AND tbl_embarque.posto = $posto AND faturar IS NULL and e.embarque ISNULL and tbl_embarque.fabrica= $fabrica $cond_garantia ORDER BY tbl_embarque.embarque LIMIT 1";
                        $resX = pg_query($con, $sql);
                        if (pg_num_rows($resX) > 0) {
                            $embarque = pg_fetch_result($resX, 0, embarque);
                        }else{
                            $sql = "INSERT INTO tbl_embarque (distribuidor, posto,garantia,fabrica) VALUES ($distribuidor,$posto,false, $fabrica) returning embarque";
                            $resX = pg_query($con, $sql);
                            if(strlen(pg_last_error($con))==0){
                                $embarque = pg_fetch_result($resX, 0, 'embarque');
                            }
                        }
                    }
                }

                $sql = "INSERT INTO tbl_embarque_item (embarque, peca, qtde, pedido_item) VALUES ($embarque, $peca, $qtde, $pedido_item)";
                $resEmbarqueItem = pg_query($con, $sql);
                if(strlen(pg_last_error($con))>0){
                    $msg .= pg_last_error($con);
                }
                $x++;
            }
                if (strlen($msg) > 0) {
                    $log .= "ERRO --> PEDIDO=" . $pedido . " - POSTO=" . $posto . " - PECA=" . $peca . " - EMBARQUE=" . $embarque . " - MSG=" . $msg . "\n";
                } 

				if(strlen(pg_last_error($con))>0){
					$msg .= pg_last_error($con);
				}

				if (strlen($msg) > 0) {            
					$resRollback = pg_query($con, "rollback;");
				} else {
					$resCommit = pg_query($con, "commit;");
				}

				if (strlen($msg) > 0) {
					$log .= "ERRO --> PEDIDO=" . $pedido . " - POSTO=" . $posto . " - PECA=" . $peca . " - EMBARQUE=" . $embarque . " - MSG=" . $msg . "\n";
				} 

			if(strlen(pg_last_error($con))>0){
				$msg .= pg_last_error($con);
			}

			if (strlen($msg) > 0 or strlen($erro) > 0 ) {            
				$resRollback = pg_query($con, "rollback;");
			} else {
				$resCommit = pg_query($con, "commit;");
			}
		}

    }else {
	/*
	    $sql_preco = "SELECT SUM(tbl_pedido_item.preco * tbl_pedido_item.qtde) as preco_itens
                        FROM tbl_pedido_item 
                        WHERE pedido = $pedido ";
        $res_preco = pg_query($con, $sql_preco);
        $preco_itens = pg_fetch_result($res_preco, 0, preco_itens);
        if($preco_itens < "50"){
            continue 1;
        }
	*/
        # despreza peças já atendidas, já embarcadas, ou de responsabilidade da GAMA
        $sql = "SELECT pedido_item, tbl_pedido_item.peca, referencia, qtde - qtde_faturada_distribuidor - qtde_cancelada AS qtde
                FROM tbl_pedido_item
                JOIN tbl_peca on tbl_peca.peca = tbl_pedido_item.peca
                WHERE pedido = $pedido
                AND qtde - qtde_faturada_distribuidor - qtde_cancelada > 0;";

        $resPecas = pg_query($con, $sql);

        if (pg_num_rows($resPecas) == 0) {
            $log .= "Pedido $pedido com todas as pecas atendidas \n";
            #print "Pedido $pedido com todas as pecas atendidas \n";
            continue;
        }
        $resBegin = pg_query($con, 'Begin;');
		$z = 0 ;
        while (list($pedido_item, $peca, $referencia, $qtde) = pg_fetch_array($resPecas)) {
            /*$sql  = "SELECT localizacao FROM tbl_posto_estoque_localizacao
                    WHERE peca = $peca
                    AND ((substr(localizacao,1,1) in ('R') and ascii(substr(localizacao,2,1))::numeric between '77' and '90') or
                        (substr(localizacao,1,1) in ('P') and substr(localizacao,2,2)  ~ E'\\\\d\\\\d' and substr(localizacao,2,2)::numeric >= '50' ) or substr(localizacao,1,3) = 'GAV')";
            $resl = pg_query($con, $sql);
            if (pg_num_rows($resl) == 0) {*/
                    $distribuidor = 4311;
            /*}else{
                    $distribuidor = 376542;
            }*/

            # Validação de estoque nas 2 fabricas Aulik e Pacific, pois se a peça for igual pode pegar de ambas. HD-6372761
            if (in_array($fabrica, [11, 172])) {
                # ve se tem estoque disponivel
                $sql  = "SELECT qtde, peca FROM tbl_posto_estoque WHERE posto = $distribuidor AND peca = $peca AND qtde > 0";
                $resX = pg_query($con, $sql);

                if (pg_num_rows($resX) == 0) {
                    $id_peca = "";
                    $tinha_peca = "";
                    $fab_peca = ($fabrica == 11) ? 172 : 11;

                    $sql_pecas = " SELECT peca 
                                   FROM tbl_peca 
                                   WHERE referencia = (SELECT referencia FROM tbl_peca WHERE peca = $peca AND fabrica = $fabrica)
                                   AND fabrica = $fab_peca";
                    $res_pecas = pg_query($con, $sql_pecas);

                    $id_peca = pg_fetch_result($res_pecas, 0, 'peca'); 
					
					if(!empty($id_peca)) {
						$sql = "SELECT qtde FROM tbl_posto_estoque WHERE posto = $distribuidor AND peca = $id_peca AND qtde >= $qtde";
						$resX = pg_query($con, $sql);

						if (pg_num_rows($resX) > 0) {
							$sql_baixa_estoque = "UPDATE tbl_posto_estoque SET
													qtde = qtde - $qtde
												  WHERE peca = $id_peca
												  AND posto = $distribuidor";
							$res_baixa_estoque = pg_query($con, $sql_baixa_estoque);

							$sql_tinha_peca = "SELECT posto FROM tbl_posto_estoque WHERE posto = $distribuidor AND peca = $peca";
							$res_tinha_peca = pg_query($con, $sql_tinha_peca);
							$tinha_peca = pg_fetch_result($res_tinha_peca, 0, 'posto'); 

							if ($tinha_peca != "") {
								$sql_carga_estoque = "UPDATE tbl_posto_estoque SET
														qtde = qtde + $qtde
													  WHERE peca = $peca
													  AND posto = $distribuidor";
								$res_carga_estoque = pg_query($con, $sql_carga_estoque);
							} else {
								$sql_carga_estoque = "INSERT INTO tbl_posto_estoque (posto, peca, qtde) VALUES ($distribuidor, $peca, $qtde)";
								$res_carga_estoque = pg_query($con, $sql_carga_estoque);

							}
						}
					}
                } 
            }

            $sql  = "SELECT qtde, peca FROM tbl_posto_estoque WHERE posto = $distribuidor AND peca = $peca AND qtde > 0";
            $resQtde = pg_query($con, $sql);
            if (pg_num_rows($resQtde) == 0) {
                $msg = pg_last_error($con);
                if (strlen($msg) > 0) {
                    $log .= "ERRO --> nao tem estoque PEDIDO=" . $pedido . " - POSTO=" . $posto . " - PECA=" . $peca . " - " . $referencia . " - EMBARQUE=" . $embarque . " - MSG=" . $msg . "\n";
                }

                $sql_peca_alt = "SELECT para as referencia_alternativa, peca_para AS peca_alternativa, qtde
                     FROM tbl_peca_alternativa
                     JOIN tbl_posto_estoque ON tbl_posto_estoque.peca = tbl_peca_alternativa.peca_para
                     WHERE tbl_peca_alternativa.peca_de = $peca
                     AND tbl_peca_alternativa.fabrica = $fabrica
                     AND tbl_posto_estoque.posto = $distribuidor
                     AND tbl_posto_estoque.qtde > 0";
                $res_peca_alt = pg_query($con, $sql_peca_alt);

                if (pg_num_rows($res_peca_alt) > 0) {
                    $referencia_alternativa = pg_fetch_result($res_peca_alt, 0, 'referencia_alternativa');
                    $peca_alternativa = pg_fetch_result($res_peca_alt, 0, 'peca_alternativa');
                    $qtde_estoque = pg_fetch_result($res_peca_alt, 0, 'qtde');

					$obs = utf8_decode("A peça $referencia foi alterada para $referencia_alternativa pelo fabricante.");
					$mensagem = utf8_decode("Prezado assistente. <br> Para o pedido <b>$pedido</b> substituímos a peça $referencia pela peça $referencia_alternativa pois ambas apresentam as mesmas características.");

                    $upd_peca_alternativa = "UPDATE tbl_pedido_item SET
                            peca_alternativa = $peca_alternativa,
                            obs = '$obs'
                        WHERE pedido_item = $pedido_item";
                    $res_peca_alternativa = pg_query($con, $upd_peca_alternativa);

                    $sql_comunicado = "INSERT INTO tbl_comunicado (
                            obrigatorio_site,
                            tipo,
                            ativo,
                            mensagem,
                            fabrica,
                            posto
                        ) VALUES (
                            't',
                            'Comunicado',
                            't',
                            '$mensagem',
                            $fabrica,
                            $posto
                        )";
                    $res_comunicado = pg_query($con, $sql_comunicado);

                    $log .= "Pedido $pedido Peca $referencia foi atendida pela alternativa $referencia_alternativa\n";

                    $peca = $peca_alternativa;
                    $referencia = $referencia_alternativa;
                } else {
                    $log .= "Pedido $pedido Peca $peca - $referencia nao tem no estoque \n";
					continue;
                }

            }else{
                $qtde_estoque = pg_fetch_result($resQtde, 0, qtde);
            }

            if ($qtde_estoque < $qtde) {
                $qtde = $qtde_estoque;
            }

			if($z == 0) {
            if (true === $insert_embarque) {
                $sql = "INSERT INTO tbl_embarque (distribuidor, posto,garantia,fabrica) VALUES ($distribuidor,$posto,false, $fabrica) returning embarque";
                $resX = pg_query($con, $sql);
                if(strlen(pg_last_error($con))==0){
                    $embarque = pg_fetch_result($resX, 0, 'embarque');
                }
            } else {
                # ve se posto tem embarque aberto
                $embarque = 0;
                $sql = "SELECT embarque FROM tbl_embarque left join embarque_telecontrol e using(embarque) WHERE distribuidor = $distribuidor AND tbl_embarque.posto = $posto AND faturar IS NULL and e.embarque ISNULL and tbl_embarque.fabrica= $fabrica $cond_garantia ORDER BY tbl_embarque.embarque LIMIT 1";
                $resX = pg_query($con, $sql);
                if (pg_num_rows($resX) > 0) {
                    $embarque = pg_fetch_result($resX, 0, embarque);
                }else{
                    $sql = "INSERT INTO tbl_embarque (distribuidor, posto,garantia,fabrica) VALUES ($distribuidor,$posto,false, $fabrica) returning embarque";
                    $resX = pg_query($con, $sql);
                    if(strlen(pg_last_error($con))==0){
                        $embarque = pg_fetch_result($resX, 0, 'embarque');
                    }
                }
			}
			}
            # insere embarque_item
            $sql = "INSERT INTO tbl_embarque_item (embarque, peca, qtde, pedido_item) VALUES ($embarque, $peca, $qtde, $pedido_item)";
            $resX = pg_query($con, $sql);
            $log .= "Embarcando --> SUA_OS=" . $sua_os . " - POSTO=" . $posto . " - PEDIDO=" . $pedido . " - PECA=" . $referencia . " - EMBARQUE=" . $embarque ;
            $log .= "\n";
			$z++;
        }

        if(strlen(pg_last_error($con))>0){
            $msg .= pg_last_error($con);
        }

        if (strlen($msg) > 0) {
            $resRollback = pg_query($con, "rollback;");
        } else {
            $resCommit = pg_query($con, "commit;");
        }
    }

    if ($atende_pedido_faturado_parcial == 't') {
        
        $sqlVerificaEmbarque = "SELECT embarque_item from tbl_pedido_item join tbl_embarque_item on tbl_embarque_item.pedido_item = tbl_pedido_item.pedido_item where tbl_pedido_item.pedido = $pedido";
        $resVerificaEmbarque = pg_query($con, $sqlVerificaEmbarque);
        if(pg_num_rows($resVerificaEmbarque)>0){
            $sql = "UPDATE tbl_pedido SET status_pedido = 26 WHERE pedido = $pedido";
            $resPedidoStatus2 = pg_query($con, $sql);
            $msg = pg_last_error($con);
            if (strlen($msg) > 0) {
                $log .= "ERRO --> Status pedido = 26 PEDIDO=" . $pedido . " - POSTO=" . $posto . " - PECA=" . $peca . " - " . $referencia . " - EMBARQUE=" . $embarque . " - MSG=" . $msg;
            }

            $aux_sql = " INSERT INTO tbl_pedido_status(pedido, data, status,  observacao) VALUES ($pedido, current_timestamp, 26, 'Separação')";
                $aux_res = pg_query($con, $aux_sql);
        }
    }else{

        #COLOCA STATUS PEDIDO COMO Distrib. Total
        $sql = "SELECT pedido FROM tbl_pedido_item WHERE pedido = $pedido AND qtde - qtde_faturada_distribuidor - qtde_cancelada > 0";
        $resPedido = pg_query($con, $sql);
        if (pg_num_rows($resPedido) == 0) {#Distrib. Total
            $sql = "UPDATE tbl_pedido SET status_pedido = 26 WHERE pedido = $pedido";
            $resPedidoStatus2 = pg_query($con, $sql);
            $msg = pg_last_error($con);
            if (strlen($msg) > 0) {
                $log .= "ERRO --> Status pedido = 26 PEDIDO=" . $pedido . " - POSTO=" . $posto . " - PECA=" . $peca . " - " . $referencia . " - EMBARQUE=" . $embarque . " - MSG=" . $msg;
            }

            $aux_sql = " INSERT INTO tbl_pedido_status(pedido, data, status,  observacao) VALUES ($pedido, current_timestamp, 26, 'Separação')";
                $aux_res = pg_query($con, $aux_sql);

        }
    }

    #COLOCA STATUS PEDIDO COMO Distrib. Aguardando
	$sql = "SELECT tbl_pedido_item.peca
		FROM tbl_pedido_item
		join tbl_pedido using(pedido)
		LEFT JOIN tbl_posto_estoque ON tbl_posto_estoque.peca = tbl_pedido_item.peca AND tbl_posto_estoque.posto = 4311
		WHERE tbl_pedido_item.pedido = $pedido
		and tbl_pedido.status_pedido not in (22,12,13)
		AND (tbl_pedido_item.qtde > tbl_posto_estoque.qtde OR tbl_posto_estoque.peca IS NULL)";
    $resPedido = pg_query($con, $sql);
    if (pg_num_rows($resPedido) > 0) {
            $sql = "UPDATE tbl_pedido SET status_pedido = 22 WHERE pedido = $pedido";
            $resPedidoStatus2 = pg_query($con, $sql);
            $msg = pg_last_error($con);
            if (strlen($msg) > 0) {
                $log .= "ERRO --> Status pedido = 22 PEDIDO=" . $pedido . " - POSTO=" . $posto . " - PECA=" . $peca . " - " . $referencia . " - EMBARQUE=" . $embarque . " - MSG=" . $msg;
                
            }
            $aux_sql = " INSERT INTO tbl_pedido_status(pedido, data, status,  observacao) VALUES ($pedido, current_timestamp, 22, 'Aguardando Estoque')";
            $aux_res = pg_query($con, $aux_sql);
    }

    fwrite($fp, $log);
}

fclose($fp);

if (strlen(trim($log))>0) {
    $msg = "LOG DO EMBARQUE NOVO FATURADO.\n ". $log;
#    Log::envia_email($vet, 'GAMA/DISTRIB: EMBARQUE NOVO FATURADO', $msg);

 #  system ("cat $arquivos/telecontrol/embarque_novo_faturado.txt >> $arquivos/email_embarque_novo_faturado.txt ; cat $arquivos/email_embarque_novo_faturado.txt | qmail-inject");
}

/*
* Cron TÃ©rmino
*/
$phpCron->termino();
