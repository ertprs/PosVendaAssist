<?php 
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";
include "autentica_admin.php";

$msg_erro = array();
$os_trocadas = array();

if ($_REQUEST['ajax']){

	if (isset($_GET["tipo_busca"])) {

		$busca      = $_GET["busca"];
		$tipo_busca = $_GET["tipo_busca"];

		if (strlen($q) > 2) {

			if ($tipo_busca == 'posto') {

				$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto,tbl_posto_fabrica.posto
						FROM tbl_posto
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
						WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

				$sql .= ($busca == "codigo") ? " AND UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('$q') " : " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";

				$res = pg_query($con,$sql);

				if (pg_num_rows ($res) > 0) {

					for ($i = 0; $i < pg_num_rows($res); $i++) {

						$cnpj         = trim(pg_fetch_result($res, $i, 'cnpj'));
						$nome         = trim(pg_fetch_result($res, $i, 'nome'));
						$codigo_posto = trim(pg_fetch_result($res, $i, 'codigo_posto'));
						$posto 		  = trim(pg_fetch_result($res, $i, 'posto'));

						echo "$cnpj|$nome|$codigo_posto|$posto";
						echo "\n";

					}

				}

			}

		}

	}

	//VALIDAÇÃO DOS CAMPOS PARA EFETUAR A PESQUISA
	if (isset($_GET['validar'])){

		//VALIDAÇÃO DOS CAMPOS DE DATA
			$data_inicial = $_GET["data_inicial"];
			$data_final   = $_GET["data_final"];

	   		if(empty($data_inicial)){
	        	$msg_erro[] = "Informe a Data Inicial";
	    	}

	    	if (empty($data_final)) {
	    		$msg_erro[] = "Informe a Data Final";
	    	}

	    	if ($data_inicial && $data_final){

		        list($di, $mi, $yi) = explode("/", $data_inicial);
		        if(!checkdate($mi,$di,$yi)){
		            $msg_erro[] = "Data Inicial Inválida";
		        }
		    
		        list($df, $mf, $yf) = explode("/", $data_final);
		        if(!checkdate($mf,$df,$yf)) {
		            $msg_erro[] = "Data Final Inválida";
		        }

				$aux_data_inicial = "$yi-$mi-$di";
				$aux_data_final   = "$yf-$mf-$df";
		   
		        if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
		            $msg_erro[] = "Data Final menor que Data Inicial";
		        }
		        
		    	if (strtotime($aux_data_final) > strtotime('today') and $aux_data_final){
		    		$msg_erro[] = "Data Final maior que a data atual";
		    	}

	    	}
	    
	    //FIM VALIDAÇÃO DE DATAS

	    //VALIDA SE O POSTO DIGITADO EXISTE

    		if (!empty($_GET['codigo_posto']) || !empty($_GET['posto_nome'])){
				$codigo_posto = (isset($_GET['codigo_posto'])) ? trim($_GET['codigo_posto']) : '';
				$posto_nome   = (isset($_GET['posto_nome'])) ? trim($_GET['posto_nome']) : '';

			    $sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
								FROM tbl_posto
								JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
								WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

				if ($posto_nome){
					$sql .= "AND UPPER(tbl_posto.nome) = UPPER('$posto_nome')";
				}

				if ($codigo_posto){
					$sql .= "AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
				}

				$res = pg_query($con,$sql);

				if (pg_num_rows ($res) == 0) {

					$msg_erro[] = "Posto não existe";

				}
    		}
		//FIM VALIDACAO SE POSTO EXISTE

	    if (count($msg_erro)>0) {
	    	$msg_erro = implode('<br>', $msg_erro);
	    	$msg_erro = utf8_encode($msg_erro);
	    	echo "1|$msg_erro";
	    } else {
	    	echo "0|Sem Erros";
	    }
	    
	}

	if (isset($_GET['cancelar'])) {
		
		$os_excluir = $_GET['os'];
		
		$res = pg_query($con,"BEGIN TRANSACTION");

		$sql = "SELECT fn_os_excluida($os_excluir,$login_fabrica,$login_admin)";
		$res = pg_query($con,$sql);

		if (strlen(pg_last_error($con))>0) {
			$msg_erro[] = pg_last_error($con);
		}

		if (count($msg_erro)>0) {
		
			$res = pg_query($con,"ROLLBACK TRANSACTION");
	    	$msg_erro = implode('<br>', $msg_erro);
	    	$msg_erro = utf8_encode($msg_erro);
	    	echo "1|$msg_erro";
	    
	    } else {
	    
	    	$res = pg_query($con,"COMMIT TRANSACTION");
	    	echo "0|Sem Erros";
	    
	    }
	
	}

	if (isset($_POST['trocar_varios'])){

		/**
		 * SELECIONA A CAUSA TROCA QUE POSSUI codigo='lote'
		 */
		$sql = "SELECT causa_troca,descricao 
				from tbl_causa_troca 
				where fabrica=$login_fabrica 
				and codigo = 'lote'";

		$res = pg_query($con,$sql);

		if (pg_num_rows($res)>0) {
			$causa_troca_id 		= pg_fetch_result($res,0,0);
			$causa_troca_descricao 	= pg_fetch_result($res,0,1);
		}

		//RECEBE PRODUTO QUE SERA USADO PARA TROCA
		$produto_troca    = (isset($_POST['produto'])) ? $_POST['produto'] : '' ;

		//RECEBE O TIPO DA TROCA
		$tipo_troca = $_POST['tipo_troca'];
		
		//SE FOR RESSARCIMENTO IRÁ POR TRUE NA VAR. QUE VAI PARA O INSERT DA TBL_OS_TROCA E TBL_OS
		if ($tipo_troca == 'ressarcimento' ) {

			$ressarcimento = 'true';
			$produto_troca = 'null';
			$observacao    = "Troca de produto Crítico em Lote - Ressarcimento";
			$peca = "null";

		} else {

			$ressarcimento = 'null';
			$produto_troca = $produto_troca;
			$observacao    = "Troca de produto Crítico em Lote";

			/**
			 * FAZ A INSERÇÃO DO PRODUTO NA tbl_peca COMO PRODUTO ACABADO
			 * SE JA EXISTIR, SÓ PEGA O ID DA PEÇA
			 */

			$sql = "SELECT *
					FROM tbl_produto
					JOIN tbl_familia USING(familia)
					WHERE produto = '$produto_troca'
					   AND fabrica = $login_fabrica;";

			$resProd   = @pg_query($con,$sql);

			if (pg_last_error($con)) {
				$msg_erro[] = "Erro ao efetuar troca das OS's";
			}

			if (pg_num_rows($resProd) == 0) {

				$msg_erro[] = "Erro ao efetuar troca das OS's";

			} else {

				$troca_produto    = pg_fetch_result($resProd, 0, 'produto');
				$troca_ipi        = pg_fetch_result($resProd, 0, 'ipi');
				$troca_referencia = pg_fetch_result($resProd, 0, 'referencia');
				$troca_descricao  = pg_fetch_result($resProd, 0, 'descricao');
				$troca_familia    = pg_fetch_result($resProd, 0, 'familia');
				$troca_linha      = pg_fetch_result($resProd, 0, 'linha');

				$troca_descricao = substr($troca_descricao,0,50);

			}

			if (count($msg_erro) == 0) {

				$res = pg_query($con,"BEGIN TRANSACTION");

				$sql = "SELECT peca,produto_acabado
						  FROM tbl_peca
						 WHERE referencia = '$troca_referencia'
						   AND fabrica    = $login_fabrica";

				$res = pg_query($con, $sql);

				if (pg_last_error($con)) {

					$msg_erro[] = "Erro ao efetuar troca das OS's";

				}

				if (pg_num_rows($res) == 0) {

					if (strlen($troca_ipi) == 0) $troca_ipi = 10;


					$sql = "INSERT INTO tbl_peca (
								fabrica,
								referencia,
								descricao,
								ipi,
								origem,
								produto_acabado
							) VALUES (
								$login_fabrica,
								'$troca_referencia',
								'$troca_descricao',
								$troca_ipi,
								'NAC',
								true
							)
							RETURNING peca
							";

					$res = pg_query($con,$sql);
					if (pg_last_error($con)) {

						$msg_erro[] = "Erro ao efetuar troca das OS's";

					}

					$peca = pg_fetch_result($res,0,0);


					$sql = "INSERT INTO tbl_lista_basica (
								fabrica,
								produto,
								peca,
								qtde
							) VALUES (
								$login_fabrica,
								$produto_troca,
								$peca,
								1
							);";

					$res = pg_query($con,$sql);

					if (pg_last_error($con)) {
						
						$msg_erro[] = "Erro ao efetuar troca das OS's";
					
					}

				} else {

					$produto_acabado = pg_fetch_result($res, 0, 'produto_acabado');

					if ($produto_acabado == 't') {
						
						$peca = pg_fetch_result($res, 0, 'peca');

					}elseif ($produto_acabado == 'f' or empty($produto_acabado)) {
						
						$peca = pg_fetch_result($res, 0, 'peca');

						$sql = "UPDATE tbl_peca set produto_acabado = true where peca = $peca";
						$res = pg_query($con,$sql);

						if (pg_last_error($con)) {
							
							$msg_erro[] = "Erro ao efetuar troca das OS's";
						
						}

					}

				}

			}

		}	

		if (count($msg_erro)>0) {
	
			$res = pg_query($con,"ROLLBACK TRANSACTION");
	    	
	    } else {
	    
	    	$res = pg_query($con,"COMMIT TRANSACTION");
	    	
	    }

		if (count($msg_erro)==0) {

			$res = pg_query($con,"BEGIN TRANSACTION");

			if (!empty($produto_troca) and !empty($peca) and !empty($causa_troca_id) or $tipo_troca == 'ressarcimento') {
				

				foreach ($_POST['check'] as $os_trocar) {

					$sql = "SELECT produto, sua_os, posto FROM tbl_os WHERE os = $os_trocar;";
					$res = pg_query($con,$sql);

					$produto = pg_fetch_result($res, 0, 'produto');
					$sua_os  = pg_fetch_result($res, 0, 'sua_os');
					$posto   = pg_fetch_result($res, 0, 'posto');
					
					/*if ($tipo_troca != 'ressarcimento' ) {
						
						// VERIFICA SE A PEÇA (PRODUTO ACABADO) QUE ESTÁ SENDO TROCADO TEM PREÇO CADASTRADO NAS TABELAS DE PREÇO
					     
						$sql_peca = "SELECT tbl_tabela_item.preco
									   FROM tbl_tabela_item
									   JOIN tbl_tabela      ON tbl_tabela_item.tabela = tbl_tabela.tabela
									   JOIN tbl_posto_linha ON tbl_tabela.tabela      = tbl_posto_linha.tabela
									  WHERE tbl_posto_linha.posto = $posto
										AND tbl_tabela_item.peca  = $peca
										AND tbl_posto_linha.linha = $troca_linha";

						$res = pg_query($con,$sql_peca);

						if (pg_num_rows($res) == 0) {

							$sql_peca2 = "SELECT tbl_tabela_item.preco
									   FROM tbl_tabela_item
									   JOIN tbl_tabela      ON tbl_tabela_item.tabela = tbl_tabela.tabela
									   WHERE tbl_tabela_item.peca  = $peca
									   AND   tbl_tabela.tabela_garantia
									   AND   tbl_tabela.fabrica = $login_fabrica";

							$res2 = pg_query($con,$sql_peca2);
							if (pg_last_error($con)) {
								$msg_erro[] = "Erro sql linha: 351".pg_last_error($con);
							}
							if (pg_num_rows($res2) == 0) {
								$msg_erro[] = "O produto $troca_referencia não tem preço na tabela de preço. Cadastre o preço para poder para dar continuidade na troca.";
								break;
							}

						}

					}*/
					/**
					 * A VERIFICAÇÃO ACIMA FOI RETIRADA POIS O ADMIN SOLICITOU QUE NAO PRECISA DE TER PREÇO NO PRODUTO PARA FAZER TROCA. 
					 * Deixei ai para caso precise algum dia.. já ter...
					 * CASO QUEIRA VOLTAR É SÓ DESCOMENTAR
					 * 
					 * by: gabriel silveira
					 */
					
					//LIBERA A OS DE INTERVENCOES
					$sql = "SELECT status_os FROM tbl_os_status WHERE os=$os_trocar AND status_os IN (62,64,65,72,73,87,88,116,117,127) ORDER BY data DESC LIMIT 1";
					$res = pg_query($con,$sql);
					$qtdex = pg_num_rows($res);
					
					if ($qtdex>0){
						$statuss=pg_fetch_result($res,0,status_os);
						$status_arr = array(62,65,72,87,116,127);
						if (in_array($statuss,$status_arr)){

							$proximo_status = "64";

							if ( $statuss == "72"){
								$proximo_status = "73";
							}
							if ( $statuss == "87"){
								$proximo_status = "88";
							}
							if ( $statuss == "116"){
								$proximo_status = "117";
							}

							$sql = "INSERT INTO tbl_os_status
									(os,status_os,data,observacao,admin)
									VALUES ($os_trocar,$proximo_status,current_timestamp,'OS Liberada',$login_admin)";
							
							$res = pg_query($con,$sql);
							if (pg_last_error($con)) {
								$msg_erro[] = "Erro ao efetuar troca das OS's";
							}

						}
					}

					$sql = "SELECT os_troca,peca,os FROM tbl_os_troca WHERE os = $os_trocar AND pedido IS NULL ";
					$res = pg_query ($con,$sql);
					if(pg_num_rows($res)>0){
						$troca_efetuada =  pg_fetch_result($res,0,os_troca);
						$troca_os       =  pg_fetch_result($res,0,os);
						$troca_peca     =  pg_fetch_result($res,0,peca);

						$sql = "UPDATE tbl_os_troca SET os = 4836000 WHERE os_troca = $troca_efetuada";
						$res = pg_query ($con,$sql);
						if (pg_last_error($con)) {
							$msg_erro[] = "Erro sql linha: 408".pg_last_error($con);
						}

						// HD 13229
						if(strlen($troca_peca) > 0) {
							$sql = "UPDATE tbl_os_produto set os = 4836000 FROM tbl_os_item WHERE tbl_os_item.os_produto=tbl_os_produto.os_produto AND os=$troca_os and peca = $troca_peca";
							$res = pg_query ($con,$sql);
							if (pg_last_error($con)) {
								$msg_erro[] = "Erro ao efetuar troca das OS's";
							}
						}

					}

					$sql = "INSERT INTO tbl_os_troca (

								os,
								peca,
								admin,
								causa_troca,
								ressarcimento,
								fabric,
								obs_causa,
								observacao,
								gerar_pedido
							
							)VALUES(

								$os_trocar,
								$peca,
								$login_admin,
								$causa_troca_id,
								$ressarcimento,
								$login_fabrica,
								'$causa_troca_descricao',
								'$observacao',
								true

							)
					";
					$res = pg_query($con,$sql);

					if (pg_last_error($con)) {
						$msg_erro[] = "Erro ao efetuar troca das OS's";
					}

					$sql = "UPDATE tbl_os SET 
							troca_garantia = true,
							troca_garantia_admin 	= tbl_os_troca.admin, 
							troca_garantia_data 	= tbl_os_troca.data, 
							ressarcimento 			= tbl_os_troca.ressarcimento 
							FROM tbl_os_troca 
							WHERE tbl_os_troca.os = $os_trocar
							and tbl_os.os = $os_trocar";
					$res = pg_query($con,$sql);

					if (pg_last_error($con)) {
						$msg_erro[] = "Erro ao efetuar troca das OS's";
					}

					$sql = "INSERT INTO tbl_os_produto (os, produto) VALUES ($os_trocar, $produto) RETURNING os_produto;";
					$res = pg_query($con,$sql);
					if (pg_last_error($con)) {
						$msg_erro[] = "Erro ao efetuar troca das OS's";
					}

					$os_produto = pg_fetch_result($res,0,0);	

					$sql = "
						SELECT *
						FROM   tbl_os_item
						JOIN   tbl_servico_realizado USING (servico_realizado)
						JOIN   tbl_os_produto        ON tbl_os_item.os_produto = tbl_os_produto.os_produto
						WHERE  tbl_os_produto.os = $os_trocar
						AND tbl_servico_realizado.troca_de_peca
						AND    tbl_os_item.pedido NOTNULL " ;

					$res = pg_query($con,$sql);

					if (pg_last_error($con)) {

						$msg_erro[] = "Erro ao efetuar troca das OS's";

					}

					if ( pg_num_rows($res) > 0 ) {

						for($w = 0 ; $w < pg_num_rows($res) ; $w++ ) {

							$os_item = pg_fetch_result($res,$w,os_item);
							$qtde    = pg_fetch_result($res,$w,qtde);
							$pedido  = pg_fetch_result($res,$w,pedido);
							$pecaxx  = pg_fetch_result($res,$w,peca);

							//Verifica se está faturado, se esta embarcado devolve para estoque e cancela pedido para os itens da OS

							$sql = "SELECT DISTINCT
									tbl_pedido.pedido,
									tbl_peca.peca,
									tbl_peca.descricao,
									tbl_peca.referencia,
									tbl_pedido_item.qtde,
									tbl_pedido_item.pedido_item,
									tbl_pedido.exportado,
									tbl_pedido.posto,
									tbl_os_item.os_item
								FROM tbl_pedido
								JOIN tbl_pedido_item USING(pedido)
								JOIN tbl_peca        USING(peca) 
								JOIN tbl_os_item     ON tbl_os_item.pedido        = tbl_pedido_item.pedido AND tbl_os_item.peca = tbl_pedido_item.peca 
								JOIN tbl_os_produto  ON tbl_os_produto.os_produto = tbl_os_item.os_produto
								WHERE tbl_pedido.pedido       = $pedido
								AND   tbl_peca.fabrica        = $login_fabrica
								AND   tbl_os_produto.os       = $os_trocar
								AND   tbl_pedido_item.peca    = $pecaxx";

							$res_dis = pg_query($con,$sql);

							if (pg_last_error($con)) {

								$msg_erro[] = "Erro ao efetuar troca das OS's";

							}

							if (pg_num_rows($res_dis) > 0) {

								for($x=0;$x<pg_num_rows($res_dis);$x++){

									$pedido_pedido          = pg_fetch_result($res_dis,$x,pedido);
									$pedido_peca            = pg_fetch_result($res_dis,$x,peca);
									$pedido_item            = pg_fetch_result($res_dis,$x,pedido_item);
									$pedido_qtde            = pg_fetch_result($res_dis,$x,qtde);
									$pedido_peca_referencia = pg_fetch_result($res_dis,$x,referencia);
									$pedido_peca_descricao  = pg_fetch_result($res_dis,$x,descricao);
									$pedido_posto           = pg_fetch_result($res_dis,$x,posto);
									$pedido_os_item         = pg_fetch_result($res_dis,$x,os_item);

									if($pedido_posto==4311) $troca_distribuidor = "TRUE";

									$sql = "
										SELECT DISTINCT tbl_embarque.embarque
										FROM tbl_embarque
										JOIN tbl_embarque_item USING(embarque)
										WHERE pedido_item = $pedido_item
										AND   os_item     = $pedido_os_item
										AND   faturar IS NOT NULL";

									$res_x1 = pg_query($con,$sql);
									if (pg_last_error($con)) {
										$msg_erro[] = "Erro ao efetuar troca das OS's";
									}

									$tem_faturamento = pg_num_rows($res_x1);

									if($tem_faturamento>0) {
										$troca_distribuidor = "TRUE";
										$troca_faturado     = "TRUE";
									}

									$pecas_canceladas .= "$pedido_peca_referencia - $pedido_peca_descricao ($pedido_qtde UN.),";

									$distrib = 'null';
									
									
									$sql2 = "SELECT fn_pedido_cancela_garantia($distrib,$login_fabrica,$pedido_pedido,$pedido_peca,$pedido_os_item,'Troca de Produto',$login_admin); ";

									$res_x2 = pg_query($con,$sql2);

									$sql = "UPDATE tbl_pedido_item SET qtde_cancelada = qtde_cancelada + $qtde
											FROM tbl_pedido
												WHERE tbl_pedido_item.pedido      = $pedido
												AND   pedido_item = $pedido_item
												AND   peca        = $pedido_peca
												AND   tbl_pedido_item.pedido = tbl_pedido.pedido
												AND   tbl_pedido.exportado IS NULL ;";
									$res3 = pg_query($con,$sql);
									if (pg_last_error($con)) {
										$msg_erro[] = "Erro ao efetuar troca das OS's";
									}
								
								}
							}
						}
					}

					$sql="UPDATE tbl_os_item
							SET servico_realizado = 738
							WHERE os_item IN (
								SELECT os_item
								FROM tbl_os
								JOIN tbl_os_produto USING(os)
								JOIN tbl_os_item USING(os_produto)
								JOIN tbl_peca USING(peca)
								WHERE tbl_os.os       = $os_trocar
								AND tbl_os.fabrica    = $login_fabrica
							)";
					$res = pg_query($con,$sql);
					
					if (pg_last_error($con)) {
						$msg_erro[] = "Erro ao efetuar troca das OS's";
					}

					$sql = "SELECT servico_realizado FROM tbl_servico_realizado WHERE troca_produto AND fabrica = $login_fabrica" ;
					$res = pg_query($con,$sql);
					
					if (pg_last_error($con)) {
						$msg_erro[] = "Erro ao efetuar troca das OS's";
					}
					if(pg_num_rows($res) > 0){
						$servico_realizado = pg_fetch_result($res,0,0);
					}

					if ($tipo_troca != 'ressarcimento'){

						$sql = "INSERT INTO tbl_os_item (os_produto, peca, qtde, servico_realizado, admin,aguardando_peca_reparo) VALUES ($os_produto, $peca, 1,$servico_realizado, $login_admin,false)";
						$res = pg_query($con,$sql);
						if (pg_last_error($con)) {
							$msg_erro[] = "Erro ao efetuar troca das OS's";
						}
					}

					if (count($msg_erro) == 0) {
						
						$os_trocadas[] = $os_trocar;

					}

				}

			}

		}

		if (count($msg_erro)>0) {
		
			$res = pg_query($con,"ROLLBACK TRANSACTION");
	    	$msg_erro = implode("\n\n", $msg_erro);
	    	$msg_erro = utf8_encode($msg_erro);
	    	echo "1|$msg_erro";
	    
	    } else {
	    
	    	$res = pg_query($con,"COMMIT TRANSACTION");
	    	$os_trocadas = implode(",",$os_trocadas);

	    	echo "0|Sem Erros|$os_trocadas";
	    
	    }

	}

	exit;

}
