<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include 'menu.php';

$faturamento	= $_POST["faturamento"];
if(strlen($faturamento)==0) 
	$faturamento= $_GET["faturamento"];

$pedido	= $_POST["pedido"];
if(strlen($pedido)==0) 
	$pedido= $_GET["pedido"];

$btn_acao = $_POST["btn_gravar"];

$erro="";

function atualiza_qtde_entregar($con){
	//ATUALIZAR ESTOQUE - QTDE_ENTREGAR
	$sql= "	UPDATE tbl_estoque_extra
			SET quantidade_entregar =0;";
	$res_upd= pg_exec($con, $sql);		

	//ESTA ROTINA DEVERÁ SE REALIZADA A CADA CADASTRO DE PEDIDO DE COMPRA
	$sql= "	SELECT tbl_pedido_item.peca,
				sum(tbl_pedido_item.qtde) AS qtde_entregar 
			FROM tbl_pedido 
			JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido 
			WHERE tbl_pedido.fabrica = 27 
				AND tbl_pedido.status_pedido = 16 
			GROUP BY tbl_pedido_item.peca";
	$res= pg_exec($con, $sql);
	//echo "sql: $sql";
	if(pg_numrows($res) > 0){
		for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
			$peca	=trim(pg_result($res,$i,peca));
			$qtde	=trim(pg_result($res,$i,qtde_entregar));
			$sql= "	UPDATE tbl_estoque_extra
					SET quantidade_entregar =$qtde
					WHERE peca = $peca;";
			$res_upd= pg_exec($con, $sql);		
		}
	}
	return;
}

function calcula_custo_medio($con,$peca,$preco_entrada,$qtde_entrada){
	$sql = "SELECT valor_custo_medio,
					valor_compra
			FROM tbl_peca_item
			where peca = $peca";
	$yres = pg_exec($con,$sql);
	$estoque_custo_medio  = pg_result($yres,0,valor_custo_medio);
	$estoque_valor_compra = pg_result($yres,0,valor_compra);

	if($estoque_custo_medio>0){
			$novo_custo_medio = ($estoque_custo_medio + $preco_entrada) / 2;
		}else{
			$novo_custo_medio = $preco_entrada;
	}

	$sql= "UPDATE tbl_peca_item
			SET 
				valor_compra = $preco_entrada
			WHERE
			peca = $peca";
	$res = pg_exec($con, $sql);	


	$sql= "UPDATE tbl_peca_item
			SET 
				valor_custo_medio = $novo_custo_medio
			WHERE
			peca = $peca";
	$res = pg_exec($con, $sql);	

}

function formata_float_entrada($f, $casa_decimal){
	$f = str_replace( '.', '', $f);
	$f = str_replace( ',', '.', $f);
	$f = number_format($f, 2, '.', '');
	//$f = number_format($f, $casa_decimal, '.','');
	$f = trim(str_replace(".00","",$f));
	return($f);
}

if( $btn_acao=="Confirmar Recebimento"  AND (strlen($pedido) > 0)) { 

	$sql= "	SELECT  tbl_faturamento.faturamento
			FROM tbl_faturamento 
			WHERE pedido=$pedido
				AND posto = (select posto from tbl_pedido where pedido = $pedido)";
	$res= pg_exec($con, $sql);	

	// SE O PEDIDO AINDA NAO GEROU NOTA FISCAL, ENTAO INSERE OS DADOS DO PEDIDO NA TBL_FATURAMENTO
	if(@pg_numrows($res)==0){

		$nota_fiscal	= $_POST["nota_fiscal"];
		$emissao		= $_POST["emissao"];
		$saida			= $_POST["saida"];
		$condicao		= $_POST["condicao"];
		$natureza		= $_POST["natureza"];
		$serie			= $_POST["serie"];
		$cfop			= $_POST["cfop"];
		$transportadora = $_POST["transportadora"];
		$valor_frete	= $_POST["valor_frete"];
		$frete			= $_POST["frete"];

		if(strlen($nota_fiscal) > 0){	
			$doc		 = "$nota_fiscal";
			$nota_fiscal = "'$nota_fiscal'";

		}else{
			$erro = "Nota fiscal<br>";
		}

		//DATA DE EMISSAO
		if(strlen($emissao) > 0){	
			$emissao = "'".substr ($emissao,6,4) . "-" . substr ($emissao,3,2) . "-" . substr ($emissao,0,2)."'";
		}else{
			$erro = " Data de Emissão<br>";
		}

		//DATA DE SAIDA
		if(strlen($saida)>0){
			$saida = "'".substr ($saida,6,4) . "-" . substr ($saida,3,2) . "-" . substr ($saida,0,2)."'";
		}else{
			$saida= $emissao;
		}

		//CONDICAO DE PAGAMENTO
		if(strlen($condicao) == 0){
			$erro = " Condição de Pagamento<br>";
			//print_r($_POST);
		}

		//NATUREZA DE OPERAÇÃO
		if(strlen($natureza) == 0){
			$erro = " Natureza de Operação<br>";
		}else{
			$natureza = "'$natureza'";
		}

		//CFOP
		if(strlen($cfop) == 0){
			$erro = " Digite o CFOP<br>";
		}else{
			$cfop = "'$cfop'";
		}

		//SERIE
		if(strlen($serie) == 0){
			$erro = " Série da Nota<br>";
		}else{
			$natureza = "'$serie'";
		}

		//TRANSPORTADORA			
		if(strlen($transportadora==0)){
			$erro = " Selecione a Transportadora1!<br>";
			//print_r($_POST);
		}

		//VALOR_FRETE
		if(strlen($frete) == 0) {
			$erro = " Erro no tipo do frete!";
			//print_r($_POST);
		}
		
		//VALOR_FRETE
		if((strlen($valor_frete)==0) AND (strlen($frete) =="FOB")) {
			$erro = " Para o frete FOB, é necessário informar o valor do frete!";
		}else{
			if(strlen($valor_frete) > 0){
				$valor_frete	= str_replace( '.', '', $valor_frete);
				$valor_frete	= number_format(str_replace( ',', '.', $valor_frete), 4, '.','');
			}else{
				$valor_frete	= "0";
			}
		}

		//TOTAL DA NOTA
		if(strlen($total_nota) > 0){
			$total_nota		= str_replace( '.', '', $total_nota);
			$total_nota		= number_format(str_replace( ',', '.', $total_nota), 4, '.','');
		}else{
			$total_nota		= "null";
			$erro			= " Total da Nota<br>";
		}

		if(strlen($erro) == 0){
			
			$res= pg_exec($con, "begin;");	
			$sql = "SELECT pessoa_fornecedor
					FROM tbl_cotacao_fornecedor 
					WHERE cotacao_fornecedor =(
												SELECT cotacao_fornecedor
												FROM tbl_pedido
												WHERE pedido=$pedido
												);";

			$res = pg_exec($con, $sql);	
	
			$fornecedor= trim(pg_result($res, 0, pessoa_fornecedor));

			$sql= "INSERT INTO 
						tbl_faturamento
						(
							pedido, 
							fabrica,
							nota_fiscal,
							emissao,
							saida,
							posto,
							condicao,
							total_nota,
							valor_frete,
							frete,
							natureza,
							cfop,
							serie,
							transportadora,
							pessoa_fornecedor,
							conferencia,
							movimento,
							pessoa_empregado
						)
					VALUES
						(
							$pedido, 
							$login_empresa,
							$nota_fiscal,
							$emissao,
							$saida,
							$login_loja, 
							$condicao,
							$total_nota,
							$valor_frete,
							'$frete',
							$natureza,
							$cfop,
							$serie,
							$transportadora,
							$fornecedor,
							current_timestamp,
							'E',
							$login_empregado
						);";
		//echo "sql fat:$sql<br>";
			$res= pg_exec($con, $sql);	

			if(strlen(pg_errormessage($con))==0) {
				$res		= pg_exec($con, "SELECT CURRVAL('seq_faturamento') AS faturamento;");	
				
				$faturamento = trim(pg_result($res, 0, faturamento));

				$sql= "SELECT 
							descricao,
							parcelas
					   FROM tbl_condicao 
					   WHERE fabrica = $login_empresa AND condicao = $condicao";
				$res_cond= pg_exec($con, $sql);

				$parcelas_condicao = explode("|",trim(pg_result($res_cond,0,parcelas)));	
				
				for($x=0; $x < count($parcelas_condicao); $x++){
					$count_p = count($parcelas_condicao);
					$valor_parcela	= ($total_nota /$count_p);
					$dias_parcela	= ($parcelas_condicao[$x]);
					$doc_x			= "'$doc - ".($x+1)."'";
					//CONTAS A PAGAR
					$sql= "INSERT INTO
							tbl_pagar
							(
							empresa,
							loja,
							pessoa_fornecedor,
							digitacao,
							faturamento,
							vencimento,
							valor,
							documento
							)
							values
							(
							$login_empresa,
							$login_loja,
							$fornecedor,
							current_timestamp,
							$faturamento,
							current_date + interval '$dias_parcela day',
							$valor_parcela,
							$doc_x
							)";
					//echo "<br>pagar: $sql";
					$res = pg_exec($con, $sql);	
					//CONTAS A PAGAR
	
				}

				//ATUALIZA O PEDIDO
				$sql= "UPDATE tbl_pedido
							SET 
								status_pedido = 15 
					   WHERE
							pedido = $pedido";
				$res = pg_exec($con, $sql);	

				$cont_item = $_POST["cont_item"];
				if(strlen($cont_item)== 0 or $cont_item == 0){
					$erro="Não tem itens para cadastrar!>>>>>>>><br>";
					//print_r($_POST);
				}

				for ( $i = 0 ; $i < $cont_item ; $i++){

					$peca			= $_POST["peca_$i"];
					$qtde			= $_POST["qtde_$i"];
					$qtde_estoque	= $_POST["qtde_estoque_$i"];
					$qtde_quebrada	= $_POST["qtde_quebrada_$i"];
					$preco			= $_POST["preco_$i"];
					$aliq_icms		= $_POST["aliq_icms_$i"];
					$aliq_ipi		= $_POST["aliq_ipi_$i"];

					if(strlen($faturamento) == 0)
						$erro	= "Erro no Faturamento!";

					if(strlen($peca) == 0)
						$erro	= "Peça está vazia!";

					if(strlen($qtde) == 0)
						$erro	= "Qtde está vazia!";

					if(strlen($qtde_estoque) == 0)
						$erro	= "Qtde estoque está vazio!";

					if(strlen($qtde_quebrada) == 0)	  
						$qtde_quebrada= "null";

					if(strlen($preco) == 0){
						$erro	= "Preço está vazio!";
					}else{
						$preco	= str_replace( '.', '', $preco);
						$preco	= number_format(str_replace( ',', '.', $preco), 4, '.','');
					}

					if(strlen($aliq_icms) == 0){

						$aliq_icms	= 0;
						$valor_icms	= 0;
						$base_icms	= 0;
					}else{
						$valor_icms= (($aliq_icms * $preco)/100);
						$base_icms = $qtde_estoque*$preco;
						$aliq_icms	= formata_float_entrada($aliq_icms	, 4);
						$valor_icms = formata_float_entrada($valor_icms	, 4);
						$base_icms	= formata_float_entrada($base_icms	, 4);
						//echo "aliq_icms: $aliq_icms - valor_icms: $valor_icms - base: $base_icms";
					}

					if(strlen($aliq_ipi) == 0){
						$aliq_ipi	= 0;
						$valor_ipi	= 0;
						$base_ipi	= 0;
					}else{
						$valor_ipi	= (($aliq_ipi * $preco)/100);
						$base_ipi	= ($qtde_estoque*$preco);

						$aliq_ipi	= formata_float_entrada($aliq_ipi, 4);
						$valor_ipi	= formata_float_entrada($valor_ipi, 4);
						$base_ipi	= formata_float_entrada($base_ipi, 4);
						//echo "aliq_ipi: $aliq_ipi - valor_ipi: $valor_ipi - base: $base_ipi";
					}

					$sql= " 
							INSERT INTO 
								tbl_faturamento_item 
								(
									faturamento, 
									peca, 
									qtde,
									preco, 
									pedido,
									qtde_quebrada,
									aliq_icms,
									aliq_ipi,
									base_icms,
									base_ipi,
									valor_icms,
									valor_ipi
								)
							VALUES
								(
									$faturamento,		
									$peca, 
									$qtde,
									$preco, 
									$pedido,
									$qtde_quebrada,
									$aliq_icms,
									$aliq_ipi,
									$base_icms,
									$base_ipi,
									$valor_icms,
									$valor_ipi	
								)";
					//echo "sql item: $sql";
					$res= pg_exec($con, $sql);


					/*##############################################################################
					#### É NECESSÁRIO ATUALIZAR A QTDE_ESTOQUE, POIS TEM UMA TRIGGER DEIXA NULL ####
					###############################################################################*/
					$res		= pg_exec($con, "SELECT CURRVAL('seq_faturamento_item') AS faturamento_item;");	
					
					$faturamento_item = trim(pg_result($res, 0, faturamento_item));

					$sql= "UPDATE 
								tbl_faturamento_item
								SET
									qtde_estoque	=	$qtde_estoque
								WHERE 	faturamento		 = $faturamento		AND 
										faturamento_item = $faturamento_item;";
					//echo "sql item: $sql";
		
					$res= pg_exec($con, $sql);

					if(strlen(pg_errormessage($con))==0){
						if( pg_affected_rows($res)>1){
							$erro= "Problemas com a atualização da qtde_estoque";
						}
					}

					//ATUALIZA O TOTAL DE FATURAMENTO SE QTDE RECEBIDA DIFERENTE DE QTDE

					if($qtde <> $qtde_estoque) {
						$total_atual=$qtde_estoque*$preco;
						$total_atual= number_format(str_replace( ',', '.', $total_atual), 4, '.','');
						$sql ="UPDATE tbl_faturamento set 
								total_nota=$total_atual
								WHERE faturamento=$faturamento";
						
						$res = pg_exec($con, $sql);
					}

					//ATUALIZA PREÇO DE VENDA E COMPRA DE PEÇA

					$sql= "UPDATE tbl_peca_item
							SET 
								valor_compra = $preco,
								valor_venda = ($preco+(($preco * percento_lucro)/100))
						   WHERE
								peca = $peca";

					//echo "SQL>> valor venda - valor compra: $sql";
					//$res = pg_exec($con, $sql);	
					//echo "ATUALIZOUUUUUUUUUUUUUUUU PECA ITEM $sql";

					//ATUALIZA ESTOQUE
					$sql= " SELECT qtde AS estoque_qtde
							FROM tbl_estoque
							WHERE peca = $peca";
					$res = pg_exec($con, $sql);
					
					$estoque_qtde	= trim(pg_result($res,0,estoque_qtde));
					$qtde_atual		= ($qtde_estoque + $estoque_qtde);

					//echo "<BR><BR>ATUALIZAR QTDE: tbl_estoque.qtde:$estoque_qtde - qtde_estoque:$qtde_estoque - qtde_atual: $qtde_atual<BR>";

					//ATUALIZA ESTOQUE
					$sql= "UPDATE tbl_estoque
							SET 
								qtde = $qtde_atual
						   WHERE
								peca = $peca";
					$res = pg_exec($con, $sql);	
				
					$sql = "SELECT momento_custo_medio 
							FROM tbl_loja_dados 
							WHERE empresa=$login_empresa;";
					$res = pg_exec($con,$sql);
					if(pg_numrows($res)>0){
						if(pg_result($res,0,0)=="recebimento"){
							calcula_custo_medio($con,$peca,$preco,$qtde);
						}
					}
					
				}
			}else{
				
				$erro = "Problemas com insert:". pg_errormessage($con);
			}
			if(strlen(pg_errormessage($con)) > 0 or strlen($erro) > 0){
				$msg_erro = "Erro ao inserir os itens da Nota Fiscal:$erro - sql:".$sql;
				
				$res= pg_exec($con, "rollback;");

				$host  = $_SERVER['HTTP_HOST'];
				ECHO "ANTES ABRIR VERIFICAR O QUE É";
				echo "<script language='javascript'>alert(); </script>\n";
				EXIT;


				header("Location: http://$host/recebimento_confirma.php?msg_erro=$msg_erro");
			}else{		
				$res= pg_exec($con, "commit;");
				//atualizar a qtde a entregar no estoque
				atualiza_qtde_entregar($con);
				echo "<font color='blue'>ok, foi cadastrado com sucesso!</font>";
			}
		}else{
			if(strlen($erro)>0)
				echo "<FONT color='red'> ERRO: $erro</FONT>";
		}
	}
}
/*else{
	//FAZ UPDATE DO FATURAMENTO
	if(strlen($faturamento)>0){
		$sql= "	SELECT  tbl_faturamento.faturamento
				FROM tbl_faturamento 
				WHERE faturamento=$faturamento";
		$res= pg_exec($con, $sql);	
	}else{
		$sql= "	SELECT  tbl_faturamento.faturamento
				FROM tbl_faturamento 
				WHERE pedido=$pedido
					AND posto = (select posto from tbl_pedido where pedido = $pedido)";
		$res= pg_exec($con, $sql);	
	}
	
	// SE EXISTE O FATURAMENTO, ENTAO FAZ UPDATE
	if(@pg_numrows($res) > 0 and $btn_gravar=="Gravar") {

		$nota_fiscal	= $_POST["nota_fiscal"];
		$emissao		= $_POST["emissao"];
		$saida			= $_POST["saida"];
		$condicao		= $_POST["condicao"];
		$natureza		= $_POST["natureza"];
		$transportadora = $_POST["transportadora"];
		$valor_frete	= $_POST["valor_frete"];

		if(strlen($nota_fiscal) > 0){	
			$nota_fiscal = "'$nota_fiscal'";
		}else{
			$erro = "Nota fiscal<br>";
		}

		//DATA DE EMISSAO
		if(strlen($emissao) > 0){	
			$emissao = "'".substr ($emissao,6,4) . "-" . substr ($emissao,3,2) . "-" . substr ($emissao,0,2)."'";
		}else{
			$erro = " Data de Emissão<br>";
		}

		//DATA DE SAIDA
		if(strlen($saida)>0){
			$saida = "'".substr ($saida,6,4) . "-" . substr ($saida,3,2) . "-" . substr ($saida,0,2)."'";
		}else{
			$saida= $emissao;
		}

		//CONDICAO DE PAGAMENTO
		if(strlen($condicao) == 0){
			$erro = " Condição de Pagamento<br>";
		}

		//NATUREZA DE OPERAÇÃO
		if(strlen($natureza) == 0){
			$erro = " Natureza de Operação<br>";
		}else{
			$natureza = "'$natureza'";
		}

		//TRANSPORTADORA			
		if(strlen($_POST["transportadora"])==0){
			$erro = " Selecione a Transportadora<br>";
		}

		//VALOR_FRETE
		if(strlen($valor_frete) > 0){
			$valor_frete	= formata_float_entrada($valor_frete, 4);
		}else{
			$valor_frete	= 0;
		}

		//TOTAL DA NOTA
		if(strlen($total_nota) > 0){
			$total_nota		= formata_float_entrada($total_nota, 4);
		}else{
			$erro			= " Valor do Frete<br>";
		}

		if(strlen($erro) == 0){
			
			$res= pg_exec($con, "begin;");	

			$sql= "UPDATE 
						tbl_faturamento
						SET
							nota_fiscal		=	$nota_fiscal,
							emissao			=	$emissao,
							saida			=	$saida,
							posto			=	$posto,
							condicao		=	$condicao,
							total_nota		=	$total_nota,
							valor_frete		=	$valor_frete,
							transportadora	=	$transportadora
						WHERE faturamento	=	$faturamento;";

				echo "sql fat:$sql<br>";
			$res= pg_exec($con, $sql);

			if(strlen(pg_errormessage($con))==0){
				if( pg_affected_rows($res)>1){
					$erro= "Problemas com a atualização";
				}else{
					//CONTAS A PAGAR
					$sql= "UPDATE tbl_pagar
							SET
								vencimento	= current_date,
								valor		= $total_nota	,
								documento	= $nota_fiscal	
							WHERE
								faturamento = $faturamento;";

					echo "sql fat:$sql<br>";
					$res= pg_exec($con, $sql);

					//ATUALIZA O PEDIDO
					$sql= "UPDATE tbl_pedido
							SET 
								status_pedido = 4 
					   WHERE
							pedido = $pedido";
					$res = pg_exec($con, $sql);	


					if(strlen(pg_errormessage($con))>0){
							$erro= "Problemas com a atualização do contas pagar!";
					}
					if( pg_affected_rows($res) > 1){
						$erro= "Mais de um registro foi atualizado no contas a pagar";
					}
					//FIM DO CONTAS A PAGAR

					$cont_itens = $_POST["cont_itens"];

					for ( $i = 0 ; $i < $cont_itens ; $i++){

						$peca			= $_POST["peca_$i"];
						$qtde			= $_POST["qtde_$i"];
						$qtde_estoque	= $_POST["qtde_estoque_$i"];
						$qtde_quebrada	= $_POST["qtde_quebrada_$i"];
						$preco			= $_POST["preco_$i"];
						$aliq_icms		= $_POST["aliq_icms_$i"];
						$aliq_ipi		= $_POST["aliq_ipi_$i"];

						if(strlen($faturamento) == 0)
							$erro	= "Erro no Faturamento!";

						if(strlen($peca) == 0)
							$erro	= "Peça está vazia!";

						if(strlen($qtde) == 0)
							$erro	= "Qtde está vazia!";

						if(strlen($qtde_estoque) == 0)
							$qtde_estoque = "null";

						if(strlen($qtde_quebrada) == 0)	  
							$qtde_quebrada= "null";

						if(strlen($preco) == 0){
							$erro	= "Preço está vazio!";
						}else{
							$preco	= formata_float_entrada($preco, 4);
						}

						if(strlen($aliq_icms) == 0){
							$aliq_icms	= 0;
							$valor_icms	= 0;
							$base_icms	= 0;
						}else{
							$valor_icms= (($aliq_icms * $preco)/100);
							$base_icms = $qtde_estoque*$preco;
					
							$aliq_icms	= formata_float_entrada($aliq_icms	, 4);
							$valor_icms = formata_float_entrada($valor_icms	, 4);
							$base_icms	= formata_float_entrada($base_icms	, 4);
						}

						if(strlen($aliq_ipi) == 0){
							$aliq_ipi	= 0;
							$valor_ipi	= 0;
							$base_ipi	= 0;
						}else{
							$valor_ipi	= (($aliq_ipi * $preco)/100);
							$base_ipi	= ($qtde_estoque*$preco);

							$aliq_ipi	= formata_float_entrada($aliq_ipi, 4);
							$valor_ipi	= formata_float_entrada($valor_ipi, 4);
							$base_ipi	= formata_float_entrada($base_ipi, 4);
						}

						$sql="
							UPDATE 
								tbl_faturamento_item 
								SET
									qtde			=	$qtde			,
									preco			=	$preco			,
									qtde_estoque	=	$qtde_estoque	,
									qtde_quebrada	=	$qtde_quebrada	,
									aliq_icms		=	$aliq_icms		,
									aliq_ipi		=	$aliq_ipi		,
									base_icms		=	$base_icms		,
									base_ipi		=	$base_ipi		,
									valor_icms		=	$valor_icms		,
									valor_ipi		=	$valor_ipi
							WHERE faturamento = $faturamento 
								AND peca= $peca;";
						echo "<br>UPDATE ITEM FAT: $sql";

						$res= pg_exec($con, $sql);
						if( pg_affected_rows($res)>1){
							$erro= "Problemas com dos itens";
						}
					}
				}
				if((strlen(pg_errormessage($con)) > 0)OR (strlen($erro) > 0)) {
					$erro.= "Erro ao inserir os itens da Nota Fiscal:".$sql;
					$res= pg_exec($con, "rollback;");
					$host  = $_SERVER['HTTP_HOST'];
					header("Location: http://$host/recebimento_confirma.php?msg_erro=$msg_erro");
				}else{		
					$res= pg_exec($con, "commit;");
					echo "<font color='blue'>ok, foi alterado com sucesso!</font>";
				}
			}
		}else{
			echo "<FONT color='red'> ERRO: $erro</FONT>";
		}
	}
}*/
if(strlen($pedido)>0){
	$sql= "	SELECT  tbl_faturamento.faturamento
			FROM tbl_faturamento 
			WHERE pedido=$pedido
				AND posto = (select posto from tbl_pedido where pedido = $pedido)";
	$res= pg_exec($con, $sql);	
	if(pg_numrows($res)>0){
		$faturamento = trim(pg_result($res, 0, faturamento));
	}
}
?> 
<style type="text/css">
.menu_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
}
.titulo2{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #000000;
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color: #000000;
}
.titulo {
	font-family: Arial;
	font-size: 10pt;
	color: #000000;
	background: #ced7e7;
}
</style>
<script type="text/javascript" src="javascript/ajax_busca.js"></script>
<script language='javascript' src='ajax.js'></script>
<script language="JavaScript" type="text/javascript" src="javascript/funcoes.js"></script>

<script language="javascript">

function abrir(URL) { 

	var width = 400; 
	var height = 300; 

	var left = 99; 
	var top = 99; 

	window.open(URL,'janela', 'width='+width+', height='+height+', top='+top+', left='+left+', scrollbars=yes, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no'); 

} 


function msg(){
		document.getElementById('dados').innerHTML = "&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='imagens/carregar_os.gif' >";
		/*document.getElementById('dados').innerHTML = "
	<font size='1'>
		Por favor aguarde um momento, carregando os dados...
	</font>\n
	<br>
	<img src='imagens/carregar_os.gif'>\n";*/
}


// MOSTRA OCULTA UM BLOCO DE HTML
function mostra_oculta(itemID, itemID2){
  if ((document.getElementById(itemID).style.display == 'none')){
	document.getElementById(itemID).style.display = 'inline';
	document.getElementById(itemID2).innerHTML= '-&nbsp;';
  }else{
	document.getElementById(itemID).style.display = 'none';
	document.getElementById(itemID2).innerHTML= '+&nbsp;';
  }
}

function retornaExibe2(http,componente, acao) {

	if (http.readyState == 1) {
		document.getElementById('dados').innerHTML = "&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='imagens/carregar_os.gif' >";
		//CHAMA A FUNCAO DE CARREGANDO(LOADING) ENQUANTO NAO EXISTIR O RETORNO
	}
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					document.getElementById('dados').innerHTML   = results[1] ; //retorna a "LISTA DE CONTAS A PAGAR"
				}else{
					com.innerHTML   = "<h4>Ocorreu um erro</h4>";
				}
			}else{
				alert ('Fechamento nao processado');
			}
		}
	}
}

//FUNÇAO USADA PARA ATUALIZAR, INSERIR E ALTERAR		
function Exibir2(componente,solicita, documento, acao) {

	var faturamento = $('#faturamento').val(); // mesma coisa que document.geteElementById

	//msg.style.display='inline';

	//msg.style.display = 'none';
	url = "contas_pagar_retorno_consulta_ajax.php?ajax=sim&faturamento="+escape(faturamento) ;

	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaExibe2 (http,componente, acao) ; } ;
	http.send(null);
}

</script>
<?
if(strlen($faturamento) > 0){
	$titulo = "Nota Fiscal";
	$sql = "SELECT	
				tbl_faturamento.faturamento ,
				tbl_fabrica.nome AS fabrica_nome ,
				tbl_faturamento.nota_fiscal ,
				to_char (tbl_faturamento.emissao,'DD/MM/YYYY') as emissao ,
				TO_CHAR (tbl_faturamento.saida,'DD/MM/YYYY') AS saida, 
				to_char (tbl_faturamento.conferencia,'DD/MM/YYYY') as conferencia ,
				to_char (tbl_faturamento.cancelada,'DD/MM/YYYY') as cancelada ,
				tbl_faturamento.cfop ,
				tbl_faturamento.serie,
				tbl_faturamento.condicao,
				tbl_faturamento.transportadora ,
				tbl_faturamento.valor_frete,
				tbl_faturamento.frete,
				tbl_faturamento.natureza,
				tbl_faturamento.cfop,
				tbl_faturamento.serie,
				tbl_faturamento.total_nota as total_nota,
				tbl_transportadora.nome AS transp_nome ,
				tbl_transportadora.fantasia AS transp_fantasia ,
				to_char (tbl_faturamento.total_nota,'999999.99') as total_nota2,
				tbl_pessoa_fornecedor.pessoa as fornecedor,
				tbl_pessoa.cnpj,
				tbl_pessoa.nome
			FROM    tbl_faturamento
			JOIN    tbl_fabrica			  ON tbl_fabrica.fabrica			   = tbl_faturamento.fabrica
			JOIN	tbl_pessoa_fornecedor ON tbl_pessoa_fornecedor.pessoa	   = tbl_faturamento.pessoa_fornecedor
			JOIN	tbl_pessoa			  ON tbl_pessoa.pessoa				   = tbl_pessoa_fornecedor.pessoa	   
			JOIN	tbl_transportadora	  ON tbl_transportadora.transportadora = tbl_faturamento.transportadora
			WHERE   tbl_faturamento.faturamento= $faturamento";

	$res= pg_exec($con, $sql);

	$fornecedor			= trim(pg_result($res, 0, fornecedor));
	$cnpj				= trim(pg_result($res, 0, cnpj));
	$nome				= trim(pg_result($res, 0, nome));
	$emissao			= trim(pg_result($res, 0, emissao));
	$saida				= trim(pg_result($res, 0, saida));
	$conferencia		= trim(pg_result($res, 0, conferencia));
	$nota_fiscal		= trim(pg_result($res, 0, nota_fiscal));
	$total_nota			= trim(pg_result($res, 0, total_nota));
	$condicao			= trim(pg_result($res, 0, condicao));
	$valor_frete		= trim(pg_result($res, 0, valor_frete));
	$frete				= trim(pg_result($res, 0, frete));
	$transportadora		= trim(pg_result($res, 0, transportadora));
	$natureza			= trim(pg_result($res, 0, natureza));
	$cfop				= trim(pg_result($res, 0, cfop));
	$serie				= trim(pg_result($res, 0, serie));

}else{

	if(strlen($pedido)>0){

		$titulo = "Recebimento";
		$sql= "	SELECT 
					tbl_pedido.pedido,
					tbl_pedido.cotacao_fornecedor,
					tbl_pedido.data ,
					tbl_pedido.entrega ,
					tbl_pedido.entrega as previsao_entrega,
					tbl_pedido.status_pedido,
					tbl_pedido.transportadora,
					tbl_pedido.total,
					tbl_pedido.condicao,
					tbl_pedido.tipo_frete,
					tbl_pedido.valor_frete,
					tbl_pedido_item.pedido_item,
					tbl_pessoa.nome,
					tbl_pessoa.cnpj,
					tbl_cotacao_fornecedor.pessoa_fornecedor as fornecedor,
					tbl_cotacao.cotacao,
					tbl_status_pedido.descricao			
				FROM tbl_cotacao
				JOIN tbl_cotacao_fornecedor USING(cotacao)
				JOIN tbl_pessoa_fornecedor on tbl_pessoa_fornecedor.pessoa  = tbl_cotacao_fornecedor.pessoa_fornecedor
				JOIN tbl_pessoa			   on tbl_pessoa_fornecedor.pessoa  = tbl_pessoa.pessoa
				JOIN tbl_pedido			   on tbl_pedido.cotacao_fornecedor = tbl_cotacao_fornecedor.cotacao_fornecedor
				JOIN tbl_status_pedido     on tbl_pedido.status_pedido      = tbl_status_pedido.status_pedido
				JOIN tbl_pedido_item       on tbl_pedido.pedido				= tbl_pedido_item.pedido
				WHERE tbl_pedido.pedido = $pedido";

		$res= pg_exec($con, $sql);
		if(pg_numrows($res)>0){
			$nome				= trim(pg_result($res, 0, nome));
			$fornecedor			= trim(pg_result($res, 0, fornecedor));
			$cnpj				= trim(pg_result($res, 0, cnpj));
			$pedido_item		= trim(pg_result($res, 0, pedido_item));
			$total_nota			= trim(pg_result($res, 0, total));
			$status_pedido		= trim(pg_result($res, 0, status_pedido));
			$descricao_status	= trim(pg_result($res, 0, descricao));
			$condicao			= trim(pg_result($res, 0, condicao));
			$valor_frete		= trim(pg_result($res, 0, valor_frete));
			$frete				= trim(pg_result($res, 0, tipo_frete));
			$transportadora		= trim(pg_result($res, 0, transportadora));		
		}
	}
}
?>
<table width='700' style='border-collapse: collapse'  bordercolor='#ccccff' class='table_line' border='1' cellspacing='1' cellpadding='0'>
<FORM NAME='form_rec' ACTION='recebimento_confirma.php' METHOD='POST'>
  <tr>
<? 
	if(strlen($faturamento ) >0) {
		$sql="	SELECT	tbl_faturamento.pessoa_empregado, 
						tbl_pessoa.nome 
				FROM tbl_faturamento 
				JOIN tbl_pessoa on tbl_pessoa.pessoa=tbl_faturamento.pessoa_empregado
				WHERE faturamento = $faturamento";

		$resfat = pg_exec($con,$sql);
		if(pg_numrows($resfat) > 0) {
			$nome= trim(pg_result($resfat, 0, nome));
			echo "<td nowrap colspan='6' align='left'>RECEBIMENTO DE MERCADORIA POR $nome</td>";
			echo "<td nowrap colspan='1' align='right' >";
			echo "<a href='nf_entrada.php'><font color='#0000ff'>Voltar Recebimento</font></a>";
			echo "</td>";
		} else {
			echo "<td nowrap colspan='7' align='right' >";
			echo "<a href='cotacao_consultar_pedido.php'><font color='#0000ff'>Voltar Recebimento</font></a>";
			echo "</td>";
		}
	}
?>
  </tr>
  <tr bgcolor='#596D9B' >
	<td nowrap colspan='7'  align='center' background='imagens/azul.gif'>
		<font color='#eeeeee' size='4'><?echo $titulo;?></font>
	</td>
  </tr>
  <tr>
	<td nowrap height='5px' colspan='7' class='menu_top' align='center'> </td>
  </tr>
  <tr>
    <td colspan='3' align='left'><b></b></td>
	<td colspan='2' align='center'><b><? echo $descricao_status;?> </b></td>
	<td align='center' class='menu_top' colspan='1' width='20%'>	</td>
	<td align='center' colspan='1' width='20%'><B>DATA</B></td>
  </tr>
  
  <tr>
<?
echo "<td colspan='3' nowrap align='left'><br><b>".substr($nome,0,30)."</b> - CNPJ: <b>$cnpj</b></td>";
?>  
	<td nowrap align='center' width='12%'><b>Nota Fiscal Fatura</b><br>
		<input type='checkbox' disabled>SAÍDA
		<input type='checkbox' disabled checked>ENTRADA&nbsp;
	</td>
	<td nowrap align='center' width='12%'><br>
		Nº <input type='text' name='nota_fiscal' size='6' maxlength='6' value='<?echo $nota_fiscal;?>'>
	</td>
	<td nowrap align='center' class='menu_top' colspan='1'>	</td>
<?	echo "<td nowrap align='center'>
			<b>Data Emissão</b><br>
			<input type='hidden' name='pedido' id='pedido' value='$pedido'>
			<input type='hidden' name='faturamento' id='faturamento' value='$faturamento'>
			<input type='hidden' name='fornecedor' id='fornecedor' value='$fornecedor'>
			<input type='text' name='emissao' size='8' maxlength='10' value='$emissao' onKeyDown= 'formataData(this,event);'>
	  </td>";
?>
  </tr>

  <tr>
	<td nowrap align='left' colspan='3' width='60%'> &nbsp;<b>Natureza de Operação</b> <br>
		&nbsp;<input type='text' name='natureza' size='60' value='<?echo $natureza;?>'>	
	</td>
	<td nowrap align='center' colspan='1' width='20%'><b>CFOP</b><br>
		<input type='text' name='cfop' size='10' value='<?echo $cfop;?>'>	
	</td>
	<td nowrap align='center' colspan='1' width='20%'><b>SÉRIE</b><br>
		<input type='text' name='serie' size='10' maxlength='3' value='<?echo $serie;?>'>	
	</td>
	<td nowrap align='center' class='menu_top' colspan='1' width='10'></td>

<?	echo "<td nowrap align='center'>
			<b>Data Saída </b><br>
			<input type='text' name='saida' size='8' maxlength='10' value='$saida' onKeyDown= 'formataData(this,event);'>
		  </td>";	
?>

  </tr>

<?


//status_pedido | 9
//descricao     | Aguardando Confirmacao
/*
$sql= "SELECT pedido
	   FROM tbl_pedido
	   WHERE status_pedido	='9'
			AND fabrica		= $login_empresa
			AND posto		= $login_loja
		ORDER BY pedido";
$ped= pg_exec($con, $sql);

if(@pg_numrows($ped) >0){
	echo "<tr >";
	echo "<td nowrap height='7px' colspan='7' bgcolor ='#ced7e7' align='center'>
			<font color='#ff0000'>Pedidos a confirmar:</font></td>";
	echo "</tr>";
	echo "<tr bgcolor='#000000'>";
	echo "<td nowrap height='1px' colspan='7' align='center'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td nowrap height='5px' colspan='7' align='center'> ";
	echo "<table align='left' size='100%' border='0'>";
	echo "<tr class='table_line'>";
	for ( $i = 0 ; $i < @pg_numrows ($ped) ; $i++ ){
		$x_pedido=trim(pg_result($ped, $i, pedido));
		echo "<td nowrap colspan='1' align='center'>";
		echo "<a href='recebimento_confirma.php?pedido=$x_pedido'><font color='#ff0000'><b>$x_pedido</b></font></a>";
		echo "</td>";
	}
	echo "</tr>";	
	echo "</table>";
	echo "</td>";	
	echo "</tr>";
	echo "<tr >";	
	echo "<td nowrap height='5px' colspan='7' class='menu_top' align='center'> </td>";	
	echo "</tr>";	
}
*/




/*	if($natureza==1){
		$f_pag1="selected";
		$f_pag2="";
	}else{
		$f_pag1="";
		$f_pag2="selected";	
	}
*/	

	
	echo "<tr>";	
	echo "<td nowrap height='5px' colspan='7' class='menu_top' align='center'> </td>";	
	echo "</tr>";
	
	//FATURA - CONDIÇÃO DE PAGAMENTO
	if(strlen($condicao) > 0) {
		$sql= "SELECT condicao, 
					descricao,
					parcelas
			   FROM tbl_condicao 
			   WHERE fabrica = $login_empresa AND condicao = $condicao
			   ORDER BY descricao";
		$res_sel= pg_exec($con, $sql);
	
		if(pg_numrows($res) >0) {
			$parcelas= trim(pg_result($res_sel,0,parcelas));	
			$aux = explode("|",$parcelas);
			$aux2 = str_replace("|"," / ",$parcelas);
			$descricao = $aux2;
		}
	
	}
	if(count($aux) ==1 and $aux2[0]==0){
		$descricao = "À Vista";
	}

	echo "  
	<tr>
	<td nowrap colspan='7' align='left'> Condição de Pag. <b>$descricao</b>
		<input type='hidden' name='condicao' value='$condicao'>";

	//echo "<input type='text' name='cond_desc' value='$descricao' disabled>";

/*
	$sql= "SELECT 
				condicao, 
				descricao
		   FROM tbl_condicao 
		   WHERE fabrica = $login_empresa
		   ORDER BY descricao";
	$res_sel= pg_exec($con, $sql);

	if(@pg_numrows($res_sel)>0){
		echo "<select name='condicao' disabled>";
		echo "<option value=''>Selecionar";
		for ( $i = 0 ; $i < @pg_numrows ($res_sel) ; $i++ ) {
			$selected="";
			$cod_condicao= trim(pg_result($res_sel,$i,condicao));	
			$descricao= trim(pg_result($res_sel,$i,descricao));	
			if($condicao==$cod_condicao)
				$selected= "selected";
			echo "<option value='$cod_condicao' $selected>$descricao";
		}
		echo "</select>";
	}
	*/
	echo "
	</td>
	</tr>
	<tr>
	<td nowrap colspan='7' align='left'>";
	echo "<DIV class='exibe' id='dados' value='1' align='left'>\n
				</DIV>\n";
	echo "</td>
	</tr>";

	
	echo "<tr>";	
	echo "<td nowrap height='5px' colspan='7' class='menu_top' align='center'> </td>";	
	echo "</tr>";

	echo "	<tr>
		<td colspan='7' align='left'>

		</td>
		</tr>";

if(strlen($faturamento) > 0){

	echo "
  <tr bgcolor='#fafafa'>
	<td nowrap colspan='7' align='left' >
	  <table width='100%' border='0' bordercolor='black' cellspacing='2' cellpadding='3'>
        <tr class='titulo'>
		  <td> &nbsp;</td>
		  <td> Codigo</td>
		  <td> Descrição</td>
		  <td align='center'> Qde. Ped.</td>
		  <td align='center' > Qde. Recebida.</td>
		  <td align='center'> Qde. Queb.</td>
		  <td align='center'> Valor Unitário</td>
		  <td align='center'> Valor Total</td>
		  <td align='center'>% ICMS</td>
		  <td align='center'>% IPI</td>
		  <td align='center'>Valor IPI</td>
		</tr> ";
	
	$sql= "SELECT tbl_faturamento_item.faturamento, 
				tbl_faturamento_item.faturamento_item, 
				tbl_faturamento_item.peca, 
				tbl_faturamento_item.qtde, 
				tbl_faturamento_item.qtde_estoque, 
				tbl_faturamento_item.qtde_quebrada, 
				tbl_faturamento_item.preco, 
				tbl_faturamento_item.pedido, 
				tbl_faturamento_item.os, 
				tbl_faturamento_item.aliq_icms, 
				tbl_faturamento_item.aliq_ipi, 
				tbl_faturamento_item.base_icms, 
				tbl_faturamento_item.valor_icms, 
				tbl_faturamento_item.base_ipi, 
				tbl_faturamento_item.valor_ipi,
				tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_os.sua_os
			FROM tbl_faturamento_item 
			JOIN tbl_peca ON tbl_faturamento_item.peca  = tbl_peca.peca
			LEFT JOIN tbl_os	  ON tbl_faturamento_item.os	= tbl_os.os		
			WHERE faturamento= $faturamento
			ORDER BY tbl_peca.descricao;";
//	echo "sql: $sql";
	$res= pg_exec($con, $sql);

	if(@pg_numrows($res)==0){
		echo "<font color='#ff0000'>SEM PRODUTOS SELECIONADOS!</font>";
	}else{
		for($i=0;$i<@pg_numrows($res);$i++){
			//$pedido_item	= trim(pg_result($res, $i, pedido_item));
			$peca			= trim(pg_result($res, $i, peca));
			$nome			= trim(pg_result($res, $i, descricao));
			$qtde			= trim(pg_result($res, $i, qtde));
			$qtde_estoque	= trim(pg_result($res, $i, qtde_estoque));
			$qtde_quebrada	= trim(pg_result($res, $i, qtde_quebrada));
			$preco			= trim(pg_result($res, $i, preco));
			//$sub_total		= trim(pg_result($res, $i, sub_total));
			$aliq_icms			= trim(pg_result($res, $i, aliq_icms));
			$aliq_ipi			= trim(pg_result($res, $i, aliq_ipi));

			$valor_conf		= $preco;
			if($qtde <> $qtde_estoque) {
				$sub_total		= ($qtde_estoque * $preco);	
			} else {
				$sub_total		= ($qtde * $preco);
			}

			if(strlen($aliq_icms)>0){
				$base_icms	= ($qtde * $preco);
				$valor_icms	= (($base_icms * $aliq_icms)/100);
			}else{
				$aliq_icms		= 0;
				$base_icms	= 0;
				$valor_icms	= 0;
			}
			//echo "alq_icms: $aliq_icms - base_icms: $base_icms - valor_icms: $valor_icms";

			if(strlen($aliq_ipi)>0){
				$base_ipi	= ($qtde * $preco);
				$valor_ipi	= (($base_ipi * $aliq_ipi)/100);
			}else{
				$aliq_ipi		= 0;
				$base_ipi	= 0;
				$valor_ipi	= 0;
			}
			
			//$valor_sem_ipi	= ($preco - $valor_ipi );
			//$sub_total_sem_ipi = (($sub_total * $aliq_ipi)/100);
			//$sub_total_ipi	= ($sub_total - $sub_total_sem_ipi);	
			//$t_valor_sem_ipi= ($t_valor_sem_ipi + $valor_sem_ipi);
			//$t_sub_total_sem_ipi= ($t_sub_total_sem_ipi + $sub_total_sem_ipi);
			//$t_sub_total_ipi	= ($t_sub_total_ipi + $sub_total_ipi);	
			//$valor_sem_ipi		= number_format(str_replace( ',', '', $valor_sem_ipi), 4, ',','');
			//$sub_total_sem_ipi	= number_format(str_replace( ',', '', $sub_total_sem_ipi), 4, ',','');
			//$sub_total_ipi		= number_format(str_replace( ',', '', $sub_total_ipi), 4, ',','');
			

			//CALCULA TOTAIS
			$tot_qtde		= ($tot_qtde		+ $qtde);
			$total			= ($total			+ $sub_total);
			$tot_valor_icms	= ($tot_valor_icms	+ $valor_icms);
			$tot_valor_ipi	= ($tot_valor_ipi	+ $valor_ipi);

			$preco				= number_format(str_replace( ',', '', $preco), 4, ',','');
			$sub_total			= number_format(str_replace( ',', '', $sub_total), 4, ',','');
			$valor_ipi			= number_format(str_replace( ',', '', $valor_ipi), 4, ',','');

			if ($cor=="#fafafa")	$cor= "#eeeeff";
			else					$cor= "#fafafa";

			echo "<tr bgcolor='$cor' style='font-size: 10px'>";
			echo "<td> ".($i+1).
				 "<input type='hidden' name='peca_$i' value='$peca'>
				  <input type='hidden' name='qtde_$i' value='$qtde'>
			</td>";
			echo "<td> $peca</td>";
			echo "<td> $nome</td>";
			echo "<td align='center'>$qtde</td>";
			$diferenca_qtde="";
			if($qtde <> $qtde_estoque){
				$diferenca_qtde = "<span class='text_curto'> <a href='#' title='Diferença na quantidade - Qde. Ped.: $qtde  - Qde. Recebida: $qtde_estoque' class='ajuda'>?</a></span>";
			}
			echo "<td align='right'>$diferenca_qtde
				<input type='text' name='qtde_estoque_$i' value='$qtde_estoque' style='text-align:right;' size='8'>
			</td>";
			echo "<td align='right'>
				<input type='text' name='qtde_quebrada_$i' value='$qtde_quebrada' style='text-align:right;' size='8'>
			</td>";
			echo "<td align='right'>
				<input type='text' name='preco_$i' value='$preco' style='text-align:right;' size='8' onKeyDown='formataValor(this,13,event);'>			
			</td>";
			echo "<td align='right'>$sub_total</td>";
			echo "<td align='right'>
				<input type='text' name='aliq_icms_$i' value='$aliq_icms' style='text-align:right;' size='8' onKeyDown='formataValor(this,13,event);'>			
			</td>";

			echo "<td align='right'>
				<input type='text' name='aliq_ipi_$i' value='$aliq_ipi' style='text-align:right;' size='8' onKeyDown='formataValor(this,13,event);'>			
			</td>";
			//echo "<td align='right'>$valor_icms</td>";
			echo "<td align='right'>$valor_ipi</td>";
			echo "</tr> ";
		}

		$valor_frete	= number_format(str_replace( ',', '', $valor_frete), 2, ',','');
		$base_icms		= number_format(str_replace( ',', '', $base_icms), 2, ',','');
		$total			= number_format(str_replace( ',', '', $total), 2, ',','');
		$tot_valor_ipi	= number_format(str_replace( ',', '', $tot_valor_ipi), 2, ',','');
		$tot_valor_icms	= number_format(str_replace( ',', '', $tot_valor_icms), 2, ',','');
	}
?>

	</table>
	</td>
  </tr>
  <tr>
<?

}else{

	
	echo "
  <tr bgcolor='#fafafa'>
	<td nowrap colspan='7' align='left' >
	  <table width='100%' border='0' bordercolor='black' cellspacing='2' cellpadding='3'>
        <tr class='titulo'>
		  <td> &nbsp;</td>
		  <td> Codigo</td>
		  <td> Descrição</td>
		  <td align='center'> Qde. Ped.</td>
		  <td align='center'> Qde. Receb.</td>
		  <td align='center'> Qde. Queb.</td>
		  <td align='center'> Valor Unitário</td>
		  <td align='center'> Valor Total</td>
		  <td align='center'>% ICMS</td>
		  <td align='center'>% IPI</td>
		  <td align='center'>Valor IPI</td>
		</tr> ";

	$sql= "	SELECT 
				pedido_item,
				peca, 
				descricao, 
				qtde, 
				preco ,		 
				(qtde * preco) as SUB_TOTAL,
				tbl_pedido_item.icms,
				tbl_pedido_item.ipi 

			FROM tbl_pedido
			JOIN tbl_pedido_item USING(pedido)
			JOIN tbl_peca USING(peca)
			WHERE pedido = $pedido
			ORDER BY tbl_peca.descricao";
//	echo "sql: $sql";

	$res= pg_exec($con, $sql);

	$c=1;
	if(@pg_numrows($res)==0){
		echo "<font color='#ff0000'>SEM PRODUTOS SELECIONADOS!</font>";
	}else{
		for($i=0;$i<@pg_numrows($res);$i++){
			//$pedido_item	= trim(pg_result($res, $i, pedido_item));
			$peca			= trim(pg_result($res, $i, peca));
			$nome			= trim(pg_result($res, $i, descricao));
			$qtde			= trim(pg_result($res, $i, qtde));
			$preco			= trim(pg_result($res, $i, preco));
			$sub_total		= trim(pg_result($res, $i, sub_total));
			$aliq_icms			= trim(pg_result($res, $i, icms));
			$aliq_ipi			= trim(pg_result($res, $i, ipi));
			$qtde_estoque	= $qtde;
			$valor_conf		= $preco;

			$qtde_quebrada	= 0;

			if(strlen($aliq_icms)>0){
				$base_icms	= ($qtde * $preco);
				$valor_icms	= (($base_icms * $aliq_icms)/100);
			}else{
				$aliq_icms		= 0;
				$base_icms	= 0;
				$valor_icms	= 0;
			}

			if(strlen($aliq_ipi)>0){
				$base_ipi	= ($qtde * $preco);
				$valor_ipi	= (($base_ipi * $aliq_ipi)/100);
			}else{
				$aliq_ipi		= 0;
				$base_ipi	= 0;
				$valor_ipi	= 0;
			}
			
			//$valor_sem_ipi	= ($preco - $valor_ipi );
			//$sub_total_sem_ipi = (($sub_total * $aliq_ipi)/100);
			//$sub_total_ipi	= ($sub_total - $sub_total_sem_ipi);	
			//$t_valor_sem_ipi= ($t_valor_sem_ipi + $valor_sem_ipi);
			//$t_sub_total_sem_ipi= ($t_sub_total_sem_ipi + $sub_total_sem_ipi);
			//$t_sub_total_ipi	= ($t_sub_total_ipi + $sub_total_ipi);	
			//$valor_sem_ipi		= number_format(str_replace( ',', '', $valor_sem_ipi), 4, ',','');
			//$sub_total_sem_ipi	= number_format(str_replace( ',', '', $sub_total_sem_ipi), 4, ',','');
			//$sub_total_ipi		= number_format(str_replace( ',', '', $sub_total_ipi), 4, ',','');
			

			//CALCULA TOTAIS
			$tot_qtde		= ($tot_qtde		+ $qtde);
			$total			= ($total			+ $sub_total);
			$tot_valor_icms	= ($tot_valor_icms	+ $valor_icms);
			$tot_valor_ipi	= ($tot_valor_ipi	+ $valor_ipi);

			$preco				= number_format(str_replace( ',', '', $preco), 4, ',','');
			$sub_total			= number_format(str_replace( ',', '', $sub_total), 4, ',','');
			$valor_ipi			= number_format(str_replace( ',', '', $valor_ipi), 4, ',','');
			$valor_icms			= number_format(str_replace( ',', '', $valor_icms), 4, ',','');

			if ($cor=="#fafafa")	$cor= "#eeeeff";
			else					$cor= "#fafafa";

			echo "<tr bgcolor='$cor' style='font-size: 10px'>";
			echo "<td> ".($i+1).
				 "<input type='hidden' name='peca_$i' value='$peca'>
				  <input type='hidden' name='qtde_$i' value='$qtde'>
			</td>";
			echo "<td> $peca</td>";
			echo "<td> $nome</td>";
			echo "<td align='center'>$qtde</td>";
			echo "<td align='right'>
				<input type='text' name='qtde_estoque_$i' value='$qtde_estoque' style='text-align:right;' size='8'>			
			</td>";
			echo "<td align='right'>
				<input type='text' name='qtde_quebrada_$i' value='$qtde_quebrada' style='text-align:right;' size='8'>		
			</td>";
			echo "<td align='right'>
				<input type='text' name='preco_$i' value='$preco' style='text-align:right;' size='8' onKeyDown='formataValor(this,13,event);'>			
			</td>";
			echo "<td align='right'>$sub_total</td>";
			echo "<td align='right'>
				<input type='text' name='aliq_icms_$i' value='$aliq_icms' style='text-align:right;' size='8' >			
			</td>";

			echo "<td align='right'>
				<input type='text' name='aliq_ipi_$i' value='$aliq_ipi' style='text-align:right;' size='8' >			
			</td>";
			//echo "<td align='right'>$valor_icms</td>";
			echo "<td align='right'>$valor_ipi</td>";
			echo "</tr> ";
		}
		$tot			= $total;
		$tot_val_ipi	= $tot_valor_ipi;

		$valor_frete	= number_format(str_replace( ',', '', $valor_frete), 2, ',','');
		$base_icms		= number_format(str_replace( ',', '', $base_icms), 2, ',','');
		$total			= number_format(str_replace( ',', '', $total), 2, ',','');
		$tot_valor_ipi	= number_format(str_replace( ',', '', $tot_valor_ipi), 2, ',','');
		$tot_valor_icms	= number_format(str_replace( ',', '', $tot_valor_icms), 2, ',','');
	}
?>

	</table>
	</td>
  </tr>

<?
}

?>
  <tr>
    <td nowrap height='5px' colspan='7' class='menu_top' align='center'> </td>
  </tr>
  <tr>
    <td colspan='7' align='left'>
		<input type='hidden' name='cont_item' value='<?echo $i;?>'>

	</td>
  </tr>

  <tr class = 'titulo'>
	<td nowrap colspan='1' class='table_line' align='center'><b>Base ICMS</b></td>
	<td nowrap colspan='1' class='table_line' align='center' ><b>Valor Total do ICMS</b></td>
	<td nowrap colspan='1' class='table_line' align='center' ><b>Vlr Total do IPI</b></td>
	<td nowrap colspan='1' class='table_line' align='center'><b>Vlr do Frete</b></td>
	<td nowrap colspan='1' class='table_line' align='center' ><b>Vlr Total Produtos</b></td>
	<td nowrap colspan='2' class='table_line' align='center' ><b>Vlr Total Nota</b></td>
  </tr>
  <tr >
	<td nowrap colspan='1' align='right'><?echo $total;?></td>
	<td nowrap colspan='1' align='right'><?echo $tot_valor_icms;?></td>
	<td nowrap colspan='1' align='right'><?echo $tot_valor_ipi;?></td>
	<td nowrap colspan='1' align='right'><?echo $valor_frete;?></td>
	<td nowrap colspan='1' align='right'><?echo $total;?></td>
<? if($total_nota <= 0) {
		$total_nota= ($tot+$valor_frete +$tot_val_ipi);
		$total_nota			= number_format(str_replace( ',', '', $total_nota), 2, ',','');
  }
  if(strlen($faturamento) > 0) {
	  $sql="SELECT  
			replace(cast(cast(tbl_faturamento.total_nota as numeric(12,2)) as varchar(14)),'.', ',') as total_nota,
			CASE WHEN tbl_faturamento.total_nota = tbl_pedido.total 
				THEN 1 
				ELSE 0 
			END AS diferenca_total,
			replace(cast(cast(tbl_pedido.total as numeric(12,2)) as varchar(14)),'.', ',') as total_pedido
			FROM tbl_faturamento
			JOIN tbl_pedido on tbl_faturamento.pedido = tbl_pedido.pedido
			JOIN tbl_pessoa on tbl_faturamento.pessoa_fornecedor = tbl_pessoa.pessoa
			WHERE tbl_faturamento.fabrica = $login_empresa
				AND tbl_faturamento.posto = $login_loja
				AND faturamento=$faturamento
				AND tbl_faturamento.movimento = 'E';";
		$res = pg_exec($con, $sql);

		if(@pg_numrows($res)>0){
			for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
				$total_nota2		 = trim(pg_result($res,$i,total_nota));
				$total_pedido    = trim(pg_result($res,$i,total_pedido));

				$diferenca="";
				if($total_nota2 <> $total_pedido){
					$diferenca = "<span class='text_curto'> <a href='#' title='Diferença na nota - Tot Nota: R$ $total_nota  - Tot Pedido: R$ $total_pedido' class='ajuda'>?</a></span>";
				}
			}
		}
  }
	echo "<td nowrap align='center' colspan='2'>
			<input type='text' name='total_nota' style='text-align:right;' size='20' maxlength='15' value='$total_nota'  onKeyDown='formataValor(this,13,event);'>$diferenca
		  </td>";
		  
?>
  </tr>

  <tr><td nowrap height='5px' colspan='7' class='menu_top' align='center'> </td></tr>

  <tr>
    <td colspan='7' align='left'>

    </td>
  </tr>
<?


echo "<tr class='titulo' >";
	echo "<td class='menu_top' colspan='4' align='center'><font color='black'><b>Transportadora</b></font></td>";
	echo "<td class='menu_top' colspan='1' align='center'><font color='black'><b>Tipo Frete</b></font></td>";
	echo "<td class='menu_top' colspan='2' align='center'><font color='black'><b>Valor Frete</b></font></td>";
	echo "</tr>";	  
	echo "<tr>";
	echo "<td nowrap colspan='4' align='center'>";

	$sql= "SELECT * 
			FROM tbl_transportadora
			order by nome";
	
	$res= pg_exec($con, $sql);
	echo "<select name='transportadora'>\n";
	if(strlen($transportadora)==0)
		$transportadora= 1057;
	echo "<option value=''>Selecionar\n";
	if(@pg_numrows($res)>0){
		for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
			$selected="";
			$transportadora_x= trim(pg_result($res,$i,transportadora));	
			$nome	= trim(pg_result($res,$i,nome));	
			$array_t[]=$transportadora_x;
			if($transportadora==$transportadora_x)
				$selected="selected";
			echo "<option value='$transportadora_x' $selected>$nome\n";
		}
	}
	echo "</select>\n";
	echo "</td>";
	echo "<td nowrap colspan='1' align='center'>
			$frete
			<input type='hidden' name='frete' value='$frete' >
		</td>";
	echo "<td nowrap colspan='2' align='center'>
			<input type='text' name='valor_frete' style='text-align:right ; ' size='20' value='$valor_frete' onKeyDown='formataValor(this,13,event);'>
		</td>";
	echo "</tr>";	

	echo "<tr>";	
if(strlen($faturamento)>0){
	echo "<td nowrap colspan='8' align='right'>";
	echo "<script language='javascript'>
		$(function() {
			msg(); 
			Exibir2('dados','','','exibir');
		});
	</script>\n";
	echo "</td>";
}else{
	if($status_pedido == '16')  {
		echo "
		<td nowrap colspan='8' align='right'>
			<input type='hidden' name='pedido' value='$pedido'>
			<input type='submit' name='btn_gravar' value='Confirmar Recebimento'>
		</td>";

	}
}
?>
  </tr>
  <tr>
  <?
	echo "<td colspan='5' class='menu_top' nowrap align='left'>Pedido nº <font color='#ffffff'><b>$pedido</b></font></td>";
	 
  ?>
  </tr>
</form>
</table>
<?
	if(strlen($faturamento) > 0){

		$sql="select 
				faturamento   ,
				tbl_peca.peca ,
				qtde          ,
				preco         ,
				tbl_peca.descricao
				from tbl_faturamento_item
				JOIN tbl_peca USING(peca)
				where faturamento = $faturamento";
		$res= pg_exec($con, $sql);
	echo "<TABLE width='750' style='border-collapse: collapse'  bordercolor='#ccccff' class='table_line' border='1' cellspacing='1' cellpadding='0'>";
		echo "<TR class='titulo'>";
			echo"<TD class='menu_top' height='15'><FONT COLOR='black'><B>Faturamento</B></FONT></TD>";
			echo"<TD class='menu_top'><FONT COLOR='black'><B>Peça</B></FONT></TD>";
			echo"<TD class='menu_top'><FONT COLOR='black'><B>Qtde</B></FONT></TD>";
			echo"<TD class='menu_top'><FONT COLOR='black'><B>Descrição</B></FONT></TD>";
			echo"<TD class='menu_top'><FONT COLOR='black'><B>Preço</B></FONT></TD>";
			echo"<TD class='menu_top'><FONT COLOR='black'><B>Numero de Serie</B></FONT></TD>";
		echo"</TR>";

		if(pg_numrows($res)>0){
		for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
			$faturamento = trim(pg_result($res,$i,faturamento));
			$peca        = trim(pg_result($res,$i,peca));
			$qtde        = trim(pg_result($res,$i,qtde));
			$preco       = trim(pg_result($res,$i,preco));
			$descricao   = trim(pg_result($res,$i,descricao));
			$preco = number_format($preco, 2, ',', ' ');

			echo "<TR>";
				echo"<TD >$faturamento</TD>";
				echo"<TD>$peca</TD>";
				echo"<TD align='center'>$qtde</TD>";
				echo"<TD>$descricao</TD>";
				echo"<TD align='center'>$preco</TD>";
				echo"<TD align='center'>
					<A HREF=javascript:abrir('cadastro_numero_serie.php?faturamento=$faturamento&peca=$peca&qtde=$qtde');>Cadastrar</A>";
				echo "</TD>";
			echo"</TR>";
		}
	}
	echo"</TABLE>";
	}

include 'rodape.php';
?>

