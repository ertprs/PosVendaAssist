<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

if ($login_fabrica == 1) {
	header ("Location: pedido_cadastro_blackedecker.php");
	exit;
}

$btn_acao = trim (strtolower($_POST['btn_acao']));

$msg_erro = "";

if (strlen($_GET['pedido']) > 0) {
	$pedido = trim($_GET['pedido']);
}

if (strlen($_POST['pedido']) > 0) {
	$pedido = trim($_POST['pedido']);
}

#HD 273876
if($login_fabrica == 11){
	$sqlA = "SELECT admin, altera_pedido
			 FROM tbl_admin
			 WHERE tbl_admin.admin   = $login_admin
			 AND   tbl_admin.fabrica = $login_fabrica";
	$resA = pg_exec($con,$sqlA);

	if(pg_numrows($resA)>0){
		$altera_pedido = pg_result($resA,0,altera_pedido);

		if($altera_pedido=="t"){
			header ("Location: pedido_cadastro_altera.php?pedido=$pedido");
			exit;
		}
	}
}
#--------

if ($btn_acao == "apagar") {
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$sql = "UPDATE tbl_os_item SET pedido = null
			WHERE  tbl_os_item.pedido = tbl_pedido.pedido
			AND    tbl_os_item.pedido = $pedido
			AND    tbl_pedido.fabrica = $login_fabrica;";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	
	if (strlen($msg_erro) == 0) {
		$sql = "DELETE FROM tbl_os_item
				WHERE  tbl_os_item.pedido = tbl_pedido.pedido
				AND    tbl_os_item.pedido = $pedido
				AND    tbl_pedido.fabrica = $login_fabrica;";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
	
	if($login_fabrica == 24){
		if (strlen($msg_erro) == 0) {
			$sql = "UPDATE tbl_pedido SET status_pedido =14
					WHERE  tbl_pedido.pedido  = $pedido
					AND    tbl_pedido.fabrica = $login_fabrica ";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}

	if($login_fabrica == 7){
		/*PARA A FILIZOLA É GERADO PEDIDO CLIENTE, E TEM QUE ZERAR OS, OS_ITEM E OS_REVENDA*/
		if (strlen($msg_erro) == 0) {
			$sql = "
					UPDATE tbl_os_revenda 
					SET pedido_cliente = null 
					WHERE fabrica =$login_fabrica 
						AND pedido_cliente = $pedido;";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if (strlen($msg_erro) == 0) {
			$sql = "
					UPDATE tbl_os_item 
					SET pedido_cliente = null 
					WHERE pedido_cliente = $pedido;";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if (strlen($msg_erro) == 0) {
			$sql = "
					UPDATE tbl_os 
					SET pedido_cliente = null 
					WHERE fabrica = 7 
						and pedido_cliente = $pedido;";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}


	//$sql = "DELETE FROM tbl_pedido_item
	//		WHERE  tbl_pedido_item.pedido = tbl_pedido.pedido
	//		AND    tbl_pedido_item.pedido = $pedido
	//		AND    tbl_pedido.fabrica     = $login_fabrica;";
	//$res = @pg_exec ($con,$sql);
	//$msg_erro = pg_errormessage($con);
	
	//$sql = "DELETE FROM tbl_pedido
	//		WHERE  tbl_pedido.pedido  = $pedido
	//		AND    tbl_pedido.fabrica = $login_fabrica;";
	//$res = @pg_exec ($con,$sql);
	//$msg_erro = pg_errormessage($con);
	
	if (strlen($msg_erro) == 0) {
		$sql = "SELECT fn_pedido_delete ($pedido, $login_fabrica, $login_admin)";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
	
	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		
		header ("Location: $PHP_SELF");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if ($btn_acao == "gravar") {
	$xtipo_pedido = "'Faturado'";

	if (!function_exists('checaCPF')) {
	    function checaCPF ($cpf,$return_str = true) {
	        global $con, $login_fabrica;// Para conectar com o banco...
	        $cpf = preg_replace("/\D/","",$cpf);   // Limpa o CPF
	        if (!$cpf or $cpf == '' or (strlen($cpf) != 11 and strlen($cpf) != 14)) false;

	        $res_cpf = @pg_query($con,"SELECT fn_valida_cnpj_cpf('$cpf')");
	        if ($res_cpf === false) {
	            return ($return_str) ? pg_last_error($con) : false;
	        }
	        return ($return_str) ? $cpf : true;
	    }
	}

	if (strlen($_POST['tipo_pedido']) > 0) {
		$xtipo_pedido = "'". $_POST['tipo_pedido'] ."'";
	}else{
		$msg_erro = "Selecione o Tipo de Pedido";
		$xtipo_pedido = "null";
	}
	
	if (strlen($_POST['condicao']) > 0) {
		$xcondicao = "'". $_POST['condicao'] ."'";
	}else{
		$xcondicao = "null";
	}
	
	if (strlen($_POST['promocao']) > 0) {
		$xpromocao = "'". $_POST['promocao'] ."'";
	}else{
		$xpromocao = "null";
	}
		
	if (strlen(trim($_POST['desconto'])) > 0) {
		$xdesconto = trim($_POST['desconto']);
		$xdesconto = str_replace(",",".",$xdesconto);
	}else{
		$xdesconto = "null";
	}
		
	if (strlen($_POST['tipo_frete']) > 0) {
		$xtipo_frete = "'". $_POST['tipo_frete'] ."'";
	}else{
		$xtipo_frete = "null";
	}
		
	if (strlen($_POST['valor_frete']) > 0) {
		$xvalor_frete = "'". $_POST['valor_frete'] ."'";
		$xvalor_frete = str_replace(",",".",$xvalor_frete);
	}else{
		$xvalor_frete = "0";
	}

	if (strlen($_POST['linha']) > 0) {
		$xlinha = "'". $_POST['linha'] ."'";
	}else{
		$xlinha = "null";
	}
	
	if (strlen($_POST['pedido_cliente']) > 0) {
		$xpedido_cliente = "'". $_POST['pedido_cliente'] ."'";
	}else{
		$xpedido_cliente = "null";
	}
	
	if (strlen($_POST['validade']) > 0) {
		$xvalidade = "'". $_POST['validade'] ."'";
	}else{
		$xvalidade = "null";
	}
	
	if (strlen($_POST['entrega']) > 0) {
		$xentrega = "'". $_POST['entrega'] ."'";
	}else{
		$xentrega = "null";
	}
	
	if (strlen($_POST['tabela']) > 0) {
		$xtabela = "'". $_POST['tabela'] ."'";
	}else{
		$xtabela = "null";
	}
	
	if (strlen($_POST['transportadora']) > 0) {
		$xtransportadora = $_POST['transportadora'] ;
	}else{
		$xtransportadora = "null";
	}
	if ($login_fabrica == 24 and $pedido) $pedido_reexportar = substr($_POST['reexportar'], 0, 1);

	if (strlen($_POST['cnpj']) >= 11) {
		$cnpj  = preg_replace('/\D/', '', $_POST['cnpj']);
		$xcnpj = "'". $cnpj ."'";
	}else{
		$xcnpj = 'null';
	}
	
	if (strlen($_POST['obs']) > 0) {
		$xobs = "'". $_POST['obs'] ."'";
	}else{
		$xobs = "null";
	}

	if (strlen($_POST['referencia']) > 0) {
		$xreferencia = $_POST['referencia'] ;
		$xreferencia  = str_replace (".","",$xreferencia);
		$xreferencia  = str_replace ("-","",$xreferencia);
		$xreferencia  = str_replace ("/","",$xreferencia);
		$xreferencia  = str_replace (" ","",$xreferencia);
		$xreferencia = "'".$xreferencia."'";

		$sql = "SELECT produto
				FROM   tbl_produto
				WHERE  referencia_pesquisa = $xreferencia";
		$res = pg_exec ($con,$sql);
//$msg_debug .= $sql."<br>";
		if (pg_numrows ($res) == 0) $produto = pg_result($res,0,0);
//$msg_debug .= $produto."<br>";

	}else{
		$xreferencia = "null";
	}

	if ($xcnpj <> "null") {
		$sql = "SELECT tbl_posto.posto
				FROM   tbl_posto
				JOIN   tbl_posto_fabrica USING (posto)
				WHERE  tbl_posto.cnpj            = $xcnpj
				AND    tbl_posto_fabrica.fabrica = $login_fabrica;";
//$msg_debug .= $sql." - 2 <br>";
//if($ip=="201.68.18.41"){echo $sql;}
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 0) {
			$sql = "SELECT tbl_posto.posto
					FROM   tbl_posto
					JOIN   tbl_posto_fabrica USING (posto)
					WHERE  tbl_posto_fabrica.codigo_posto = $xcnpj
					AND    tbl_posto_fabrica.fabrica      = $login_fabrica;";
//$msg_debug .= $sql." - 3 <br>";

			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) == 0) {

				if ($login_fabrica == 7){
					$sql = "SELECT tbl_posto.posto
							FROM   tbl_posto
							JOIN   tbl_posto_consumidor USING (posto)
							WHERE  tbl_posto.cnpj               = $xcnpj
							AND    tbl_posto_consumidor.fabrica = $login_fabrica;";
		//$msg_debug .= $sql." - 4 <br>";
					$res = pg_exec ($con,$sql);
					if (pg_numrows ($res) == 0) {
						$msg_erro = "CNPJ ou Código não cadastrado";
					}
				}else{
					$msg_erro = "CNPJ ou Código não cadastrado";
				}
			}
		}
//if($ip=="201.68.18.41"){echo $sql;}
		$posto = @pg_result ($res,0,0);
	}else{
		$msg_erro = "CNPJ ou Código não informados";
	}

	if ($xtipo_pedido <> "null") {
		$sql = "SELECT tipo_pedido
				FROM   tbl_tipo_pedido
				WHERE  tipo_pedido = $xtipo_pedido
				AND    fabrica     = $login_fabrica";
//$msg_debug .= $sql." - 4 <br>";

		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 0) $msg_erro = "Tipo de Pedido não cadastrado";
	}else{
		$msg_erro = "Tipo de Pedido não informado.";
	}

	/* FILIZOLA: a tabela de preço é de acordo com a Condição de Pagamento - HD 40324 */
	if ($login_fabrica==7){
		if (strlen($xcondicao)>0 AND $xcondicao != 'null'){
			$sql = "SELECT tbl_condicao.condicao, tbl_condicao.tabela
					FROM   tbl_condicao
					WHERE  tbl_condicao.condicao = $xcondicao
					AND    tbl_condicao.fabrica  = $login_fabrica";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) >0){
				$xtabela   = pg_result ($res,0,tabela);
			}
		}
	}

	if ($xcondicao <> "null") {
		$sql = "SELECT tbl_condicao.condicao
				FROM   tbl_condicao
				WHERE  tbl_condicao.condicao = $xcondicao
				AND    tbl_condicao.fabrica  = $login_fabrica";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 0) $msg_erro = "Condição de Pagamento não cadastrada";
	}else{
		$msg_erro = "Condição de Pagamento não informada";
	}

	if ($xtabela <> "null") {
		$sql = "SELECT tbl_tabela.tabela
				FROM   tbl_tabela
				WHERE  tbl_tabela.tabela  = $xtabela
				AND    tbl_tabela.fabrica = $login_fabrica
				AND    tbl_tabela.ativa   IS TRUE ;";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 0) {
			$msg_erro = "Tabela de Preços não cadastrada";
		}
	}else{
		$msg_erro = "Tabela de Preços não informada";
	}

	if (strlen ($msg_erro) == 0) {

		$garantia_antecipada = "f";
		if ($login_fabrica == 3 AND $tipo_pedido == "3"){
			$garantia_antecipada = "t";
		}

		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		if (strlen ($pedido) == 0) {
			#-------------- insere pedido ------------
			$sql = "INSERT INTO tbl_pedido (
						posto         ,
						fabrica       ,
						condicao      ,
						tabela        ,
						admin         ,
						tipo_pedido   ,
						pedido_cliente,
						validade      ,
						entrega       ,
						obs           ,
						linha         ,
						transportadora,
						tipo_frete    ,
						valor_frete   ,
						garantia_antecipada,
						promocao      ,
						desconto
					) VALUES (
						$posto           ,
						$login_fabrica   ,
						$xcondicao       ,
						$xtabela         ,
						$login_admin     ,
						$tipo_pedido     ,
						$xpedido_cliente ,
						$xvalidade       ,
						$xentrega        ,
						$xobs            ,
						$xlinha          ,
						$xtransportadora ,
						$xtipo_frete     ,
						$xvalor_frete    ,
						'$garantia_antecipada',
						$xpromocao         ,
						$xdesconto        
					)";
		}else{
			$sql_exporta = '';
			if($login_fabrica==24){
				$sql_admin	= "admin_alteracao= $login_admin     ";
				if ($pedido_reexportar == 't') { //MLG 01/12/2010 - HD 332453
					$sql_exporta= "exportado	   = NULL			 ,
						status_pedido  = 1				 ,
						exportar_novamente_admin = $login_admin,
						exportar_novamente_data  = CURRENT_TIMESTAMP ,";
				}
			} else{
				$sql_admin	= "admin          = $login_admin     ";
			}
			$sql = "UPDATE tbl_pedido SET
						posto          = $posto          ,
						fabrica        = $login_fabrica  ,
						condicao       = $xcondicao      ,
						tabela         = $xtabela        ,
						tipo_pedido    = $tipo_pedido    ,
						pedido_cliente = $xpedido_cliente,
						validade       = $xvalidade      ,
						entrega        = $xentrega       ,
						obs            = $xobs           ,
						linha          = $xlinha         ,
						transportadora = $xtransportadora,
						tipo_frete     = $xtipo_frete    ,
						valor_frete    = $xvalor_frete   ,
						promocao       = $xpromocao      ,
						desconto       = $xdesconto      ,
						$sql_exporta
						$sql_admin
					WHERE tbl_pedido.pedido  = $pedido
					AND   tbl_pedido.fabrica = $login_fabrica";
		}
//$msg_debug .= $sql." - 7 <br>";

		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		
		if (strlen($msg_erro) == 0 and strlen($pedido) == 0) {
			$res = pg_exec ($con,"SELECT CURRVAL ('seq_pedido')");
//$msg_debug .= $sql." - 8 <br>";

			$pedido   = pg_result ($res,0,0);
			$msg_erro = pg_errormessage($con);
		}
		
		if (strlen($msg_erro) == 0) {
			$qtde_item = $_POST['qtde_item'];
			
			$nacional  = 0;
			$importado = 0;
			
			for ($i = 0 ; $i < $qtde_item ; $i++) {
				$novo            = $_POST["novo".$i];
				$item            = $_POST["item".$i];
				
				$peca_referencia = $_POST['peca_referencia_' . $i];
				$qtde            = $_POST['qtde_'            . $i];
				
				if ($login_fabrica == 50) {
					$defeito            = $_POST['defeito_'            . $i];
				}

				if (strlen($defeito)==0) {
					$defeito = 'null';
				}

				if(strlen($qtde) == 0 OR strlen($peca_referencia) == 0) {
					if (strlen($item) > 0 AND $novo == 'f') {
						$sql = "DELETE FROM tbl_pedido_item
								WHERE  tbl_pedido_item.pedido = $pedido
								AND    tbl_pedido_item.pedido_item = $item;";
						$res = @pg_exec($con,$sql);
						$msg_erro = pg_errormessage($con);
					}
				}
				
				if (strlen($msg_erro) == 0) {
					if (strlen ($peca_referencia) > 0) {
						$peca_referencia = strtoupper ($peca_referencia);
						$peca_referencia = str_replace ("-","",$peca_referencia);
						$peca_referencia = str_replace (".","",$peca_referencia);
						$peca_referencia = str_replace ("/","",$peca_referencia);
						$peca_referencia = str_replace (" ","",$peca_referencia);
						
						$sql = "SELECT  tbl_peca.peca,
										tbl_peca.origem
								FROM    tbl_peca
								WHERE   tbl_peca.referencia_pesquisa = '$peca_referencia'
								AND     tbl_peca.fabrica    = $login_fabrica ";
//$msg_debug .= $sql." - 9<br>";

						$res = pg_exec ($con,$sql);
//$msg_debug .= "num rows ".pg_numrows ($res)."<br>";
						if (pg_numrows ($res) == 0) {
							$msg_erro = "Peça $peca_referencia não cadastrada";
							$linha_erro = $i;
						}else{
							$peca   = pg_result ($res,0,peca);
							$origem = trim(pg_result ($res,0,origem));
						}
						
						if ($origem == "NAC" or $origem == "1") {
							$nacional = $nacional + 1;
						}
						
						if ($origem == "IMP" or $origem == "2") {
							$importado = $importado + 1;
						}

						if ($nacional > 0 and $importado > 0 AND $login_fabrica <> 3 AND $login_fabrica <> 5 AND $login_fabrica <> 8 and $login_fabrica <> 24 and $login_fabrica <> 42 and $login_fabrica <> 72){
							$msg_erro = "Não é permitido realizar um pedido com peça Nacional e Importada";
							$linha_erro = $i;
							break;
						}

						if (strlen ($msg_erro) == 0) {

							$qtde_anterior = 0;
							if (strlen($item) > 0 AND $login_fabrica==3){
								$sql = "SELECT qtde 
										FROM tbl_pedido_item 
										WHERE pedido_item = $item";
								$res = @pg_exec ($con,$sql);
								if (@pg_numrows ($res) > 0){
									$qtde_anterior = pg_result($res,0,qtde);
								}
							}

							if (strlen($pedido) == 0 OR $novo == 't') {
								if (strlen($qtde) == 0) {
									$qtde = 1;
								}

								$sql = "INSERT INTO tbl_pedido_item (
											pedido,
											peca  ,
											qtde  ,
											defeito
										) VALUES (
											$pedido,
											$peca  ,
											$qtde  ,
											$defeito
										)";

								$res = @pg_exec ($con,$sql);
								$msg_erro = pg_errormessage($con);
								
								if (strlen($msg_erro) == 0) {
									$res         = pg_exec ($con,"SELECT CURRVAL ('seq_pedido_item')");
									$pedido_item = pg_result ($res,0,0);
									$msg_erro = pg_errormessage($con);
								}

							}else{
								$sql = "UPDATE tbl_pedido_item SET
											peca = $peca,
											qtde = $qtde
										WHERE  tbl_pedido_item.pedido      = $pedido
										AND    tbl_pedido_item.pedido_item = $item;";
								$res = @pg_exec ($con,$sql);
								$msg_erro = pg_errormessage($con);
							}

							/* Tira do estoque disponivel - HD 11337 */
							if ($login_fabrica==3){
								$sql = "UPDATE tbl_peca 
										SET   qtde_disponivel_site = qtde_disponivel_site + $qtde_anterior - $qtde
										WHERE peca     = $peca
										AND   fabrica  = $login_fabrica
										AND   promocao_site IS TRUE
										AND qtde_disponivel_site IS NOT NULL";
								$res = pg_exec ($con,$sql);
								$msg_erro .= pg_errormessage($con);
							}
//$msg_debug .= $sql." - 10<br>";

							if (strlen($msg_erro) == 0) {
								$sql = "SELECT fn_valida_pedido_item ($pedido,$peca,$login_fabrica)";
								$res = @pg_exec ($con,$sql);
								$msg_erro = pg_errormessage($con);
							}
							
							if (strlen ($msg_erro) > 0) {
								break ;
							}
						}
					}
				}
			}
		}
	}
	
	if (strlen ($msg_erro) == 0) {
		$sql = "SELECT fn_pedido_finaliza ($pedido,$login_fabrica)";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	#---------- Pedido Via DISTRIBUIDOR (forçado) ----------#
	$pedido_via_distribuidor = $_POST['pedido_via_distribuidor'];
	if ($pedido_via_distribuidor == "f") {
		$sql = "UPDATE tbl_pedido SET pedido_via_distribuidor = 'f' , distribuidor = null WHERE pedido = $pedido";
		$res = pg_exec ($con,$sql);
	}




#	if (strlen($msg_erro) == 0) {
#		$res = pg_exec ($con,"SELECT fn_finaliza_pedido_dynacom($pedido)");
#	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		if($login_fabrica == 7){
			header ("Location: pedido_admin_consulta.php?pedido=$pedido");
			exit;
		}
		header ("Location: pedido_cadastro.php");


		echo "<script language='javascript'>";
		echo "window.open ('pedido_finalizado.php?pedido=$pedido','pedido', 'toolbar=yes, location=no, status=no, scrollbars=yes, directories=no, width=500, height=400')";
		echo "</script>";

		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}

}


#------------ Le Pedido da Base de dados ------------#
if (strlen ($pedido) > 0) {
	$sql = "SELECT  tbl_posto.cnpj           ,
					tbl_posto.nome           ,
					tbl_pedido.condicao      ,
					tbl_pedido.tabela        ,
					tbl_pedido.obs           ,
					tbl_pedido.tipo_pedido   ,
					tbl_pedido.pedido_via_distribuidor  ,
					tbl_pedido.tipo_frete    ,
					tbl_pedido.valor_frete   ,
					tbl_pedido.pedido_cliente,
					tbl_pedido.validade      ,
					tbl_pedido.entrega       ,
					tbl_pedido.exportado     ,
					tbl_pedido.linha         ,
					tbl_pedido.transportadora,
					tbl_pedido.promocao      ,
					tbl_pedido.status_pedido ,
					tbl_status_pedido.descricao AS status_desc,
					tbl_pedido.desconto
			FROM    tbl_pedido
			JOIN    tbl_posto		  USING (posto)
			JOIN    tbl_status_pedido USING(status_pedido)
			WHERE   tbl_pedido.pedido  = $pedido
			AND     tbl_pedido.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) > 0) {
		$condicao       = trim(pg_result ($res,0,condicao));
		$tipo_frete     = trim(pg_result ($res,0,tipo_frete));
		$valor_frete    = trim(pg_result ($res,0,valor_frete));
		$tipo_pedido    = trim(pg_result ($res,0,tipo_pedido));
		$pedido_cliente = trim(pg_result ($res,0,pedido_cliente));
		$pedido_via_distribuidor = trim (pg_result ($res,0,pedido_via_distribuidor));
		$validade       = trim(pg_result ($res,0,validade));
		$entrega        = trim(pg_result ($res,0,entrega));
		$tabela         = trim(pg_result ($res,0,tabela));
		$nome           = trim(pg_result ($res,0,nome));
		$cnpj           = trim(pg_result ($res,0,cnpj));
		$cnpj			= ($login_fabrica == 24) ? $cnpj : preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
// if($login_fabrica<>24){
// 		$cnpj           = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
// }
		$obs            = trim(pg_result ($res,0,obs));
		$linha          = trim(pg_result ($res,0,linha));
#		$referencia     = trim(pg_result ($res,0,referencia));
#		$descricao      = trim(pg_result ($res,0,descricao));
		$transportadora = trim(pg_result ($res,0,transportadora));
		$promocao       = trim(pg_result ($res,0,promocao));
		$desconto       = trim(pg_result ($res,0,desconto));
		$status_pedido  = trim(pg_result ($res,0,status_pedido));
		$status_pedido_desc  = trim(pg_result ($res,0,status_desc));
	}
}


#---------------- Recarrega Form em caso de erro -------------
if (strlen ($msg_erro) > 0) {
	$pedido         = $_POST['pedido'];
	$cnpj           = $_POST['cnpj'];
	$nome           = $_POST['nome'];
	$condicao       = $_POST['condicao'];
	$tipo_frete     = $_POST['tipo_frete'];
	$tipo_pedido    = $_POST['tipo_pedido'];
	$pedido_cliente = $_POST['pedido_cliente'];
	$validade       = $_POST['validade'];
	$entrega        = $_POST['entrega'];
	$tabela         = $_POST['tabela'];
	$cnpj           = $_POST['cnpj'];
	$obs            = $_POST['obs'];
	$linha          = $_POST['linha'];
	$pedido_via_distribuidor = $_POST['pedido_via_distribuidor'];
}

$layout_menu = "callcenter";
$title       = "CADASTRO DE PEDIDOS DE PEÇAS";
$body_onload = "javascript: document.frm_pedido.condicao.focus()";

include "cabecalho.php";

?>

<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>
<script language='javascript' src='ajax_produto.js'></script>
<script language='javascript' src='js/bibliotecaAJAX.js'></script>


<script type="text/javascript" src="js/jquery-1.2.1.pack.js"></script>
<script language="JavaScript">
/* ============= Função PESQUISA DE PRODUTOS ====================
Nome da Função : fnc_pesquisa_produto (codigo,descricao)
		Abre janela com resultado da pesquisa de Produtos pela
		referência (código) ou descrição (mesmo parcial).
=================================================================*/


function defeitoLista(peca,linha) {
    try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
    catch(e) {
        try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
        catch(ex) { try {ajax = new XMLHttpRequest();}
            catch(exc) {alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;}
        }
    }
    if(peca.length > 0) {
        if(ajax) {
            var defeito = "defeito_"+linha;
            var op = "op_"+linha;
            eval("document.forms[0]."+defeito+".options.length = 1;");
            idOpcao  = document.getElementById(op);
            ajax.open("GET","ajax_defeito2_testw.php?peca="+peca);
            ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            ajax.onreadystatechange = function() {
                if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}
                if(ajax.readyState == 4 ) {
                    if(ajax.responseXML) {
                        montaComboDefeito(ajax.responseXML,linha);
                    }
                    else {
                        idOpcao.innerHTML = "Selecione a peça";
                    }
                }
            }
            ajax.send(null);
        }
    }
}


function montaComboDefeito(obj,linha){
    var defeito = "defeito_"+linha;
    var op = "op_"+linha;
    var dataArray   = obj.getElementsByTagName("produto");

    if(dataArray.length > 0) {
        for(var i = 0 ; i < dataArray.length ; i++) {
            var item = dataArray[i];
            var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
            var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
            idOpcao.innerHTML = "Selecione o defeito";
            var novo = document.createElement("option");
            novo.setAttribute("id", op);//atribui um ID a esse elemento
            novo.value = codigo;        //atribui um valor
            novo.text  = nome;//atribui um texto
            eval("document.forms[0]."+defeito+".options.add(novo);");//adiciona
        }
    } else {
        idOpcao.innerHTML = "Selecione o defeito";//caso o XML volte vazio, printa a mensagem abaixo
    }
}


function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.focus();
	}
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa!");
	}
}

function fnc_pesquisa_peca (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.focus();
	}

	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa!");
	}
}

function fnc_pesquisa_posto (campo, campo2, tipo) {
	if (tipo == "nome" ) {
		var xcampo = campo;
	}

	if (tipo == "cnpj" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=300, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.nome	= campo;
		janela.cnpj	= campo2;
		janela.focus();
	}

	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa!");
	}
}

function fnc_pesquisa_transportadora (xcampo, tipo)
{
	if (xcampo.value != "") {
		var url = "";
		url = "pesquisa_transportadora.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.transportadora = document.frm_pedido.transportadora;
		janela.nome           = document.frm_pedido.transportadora_nome;
		janela.cnpj           = document.frm_pedido.transportadora_cnpj;
		janela.focus();
	}

	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa!");
	}
}

function verificaFrete(campo){
	
	if (campo.value == 'CIF'){
	//	$("#valor_frete").show();
		$("#valor_frete").attr('disabled',false);
	//	$("#text_valor_frete").html('');
	}else{
	//	$("#valor_frete").hide();
		$("#valor_frete").attr('disabled',true);
	//	$("#text_valor_frete").html('-');
	}
}

function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}

var http5 = new Array();
var http6 = new Array();

function calcular_frete(){

	var arrayReferencias = new Array();
	var listaReferencias = "";
	var cliente_cnpj     = $("#cnpj").val();

	$("input[@rel='peca']").each( function (){
		if (this.value.length > 0){
			var qtde_peca = $("input[@name='qtde_"+$(this).attr('alt')+"']").val();
			if (qtde_peca.length == 0){
				qtde_peca = 1;
			}
			arrayReferencias.push( this.value +"|"+qtde_peca );
		}
	});

	listaReferencias = arrayReferencias.join("@");

	if (listaReferencias.length > 0 && cliente_cnpj.length > 0 ) {
		var curDateTime = new Date();
		http5[curDateTime] = createRequestObject();
		url = "pedido_cadastro_ajax.php?calcula_frete=true&relacao_pecas="+listaReferencias+'&cliente_cnpj='+cliente_cnpj+'&data='+curDateTime;
		http5[curDateTime].open('GET',url);
		http5[curDateTime].onreadystatechange = function(){
			if (http5[curDateTime].readyState == 4){
				if (http5[curDateTime].status == 200 || http5[curDateTime].status == 304){
					var results = http5[curDateTime].responseText.split("|");
					if (results[0] == 'ok') {
						if(results[1] == 0){
							alert('CEP não calculado. Provavelmente não é possível o envio devido ao peso das peças');
						}else{
							alert ('Valor do Frete calculado: R$ '+results[1]);
							$('input[name=valor_frete]').val(results[1]);
						}
					}else{
						if (results[0] == 'nao') {
							alert(results[1]);
						}
					}
				}
			}
		}
		http5[curDateTime].send(null);
	}
}

</script>

<style type="text/css">

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}
.formulario td span, .formulario label {
	background-color: transparent;
}
.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

button {
	margin-right: 1ex;
	margin-top: 0.5em;
}
</style>

<? 
	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$erro = "Foi detectado o seguinte erro:<br>";
		$msg_erro = substr($msg_erro, 6, strlen($msg_erro)-6);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}


if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de serviço já foi cadastrada";

?>

<table align='center' width="700" border="0" cellpadding="0" cellspacing="0" >
<tr class='msg_erro'>
<!-- class="menu_top" -->
	<td>
		<? echo $msg_erro ?>
	</td>
</tr>
</table>
<? } 
//echo $msg_debug ;
?>


<!-- ------------- Formulário ----------------- -->
<form name="frm_pedido" method="post" action="<? echo $PHP_SELF ?>">
<input class="frm" type="hidden" name="pedido" value="<? echo $pedido ?>">


<?// HD 2471 - IGOR -PARA LATINATEC SOLICITARAM A MENSAGEM NO INICIO
if ($login_fabrica == 15){
?>
<table width='700' align='center' border='0' cellspacing='3' cellpadding='3' class='formulario'>
<tr>
	<td align='center'>
		
		Observação da Assistência Técnica:
		
	</td>
</tr>
<tr>
	<td align='center'>
		<input type="text" name="obs" size="50" value="<? echo $obs ?>" class="frm">
	</td>
</tr>
</table>
<?
}
?>


<table width='700' align='center' border='0' cellspacing='3' cellpadding='3' class='formulario'>
<tr class='titulo_tabela'><td colspan='3'>Parâmetros de Pesquisa</td></tr>
<tr>
	<td width="10">&nbsp;</td>
	
	<td width="223">
		Código ou CNPJ
	</td>
	<td>
		Razão Social
	</td>
</tr>

<tr>
	<td width="10">&nbsp;</td>
	<td width="215">
		<input type="text" name="cnpj" id="cnpj" size="18" maxlength="18" value="<? echo $cnpj ?>" class="frm" style="width:150px" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pedido.nome,document.frm_pedido.cnpj,'cnpj')" <? } ?>>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_pedido.nome,document.frm_pedido.cnpj,'cnpj')" style="cursor:pointer;">
	</td>
	<td>
		<input type="text" name="nome" size="50" maxlength="60" value="<? echo $nome ?>" class="frm" style="width:300px" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pedido.nome,document.frm_pedido.cnpj,'nome')" <? } ?>>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_pedido.nome,document.frm_pedido.cnpj,'nome')" style="cursor:pointer;">
	</td>
</tr>

<? if ($login_fabrica <> 5 AND $login_fabrica <> 7) { ?>
<tr class='subtitulo'>
	<td colspan='3' align='center'>
		
		Para efetuar um pedido por modelo do produto, informe a referência <br> ou descrição e clique na lupa, ou simplesmente clique na lupa.
		
	</td>
</tr>
<? } ?>
</table>
<? if ($login_fabrica == 3) { ?>
<table width='700' align='center' border='0' cellspacing='3' cellpadding='3' class='formulario'>
	<tr>
		<td align='left' ><input type="radio" name="pedido_via_distribuidor" value='t' <? if ($pedido_via_distribuidor == 't') echo " checked "; ?>> Atendimento Via Distribuidor</td>
		<td align='left' ><input type="radio" name="pedido_via_distribuidor" value='f' <? if ($pedido_via_distribuidor == 'f') echo " checked "; ?>> Atendimento DIRETO (via Fábrica)</td>
	</tr>
</table>
<? } ?>

<? if ($login_fabrica <> 5 AND $login_fabrica <> 7) { ?>
<table align='center' width='700' align='center' border='0' cellspacing='3' cellpadding='3' class='formulario'>
<tr>
	<td width="10">&nbsp;</td>
	<td width="170">
		
		Linha
		
	</td>
	<td>
		
		Referência do Produto
		
	</td>
	<td>
		
		Descrição do Produto
		
	</td>
</tr>

<tr>
	<td width="10">&nbsp;</td>
	<td width="150">
		<?
		$sql = "SELECT * FROM tbl_linha WHERE fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) > 0) {
			echo "<select name='linha' size='1' class='frm'>";
			echo "<option value=''></option>";
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				echo "<option value='" . pg_result ($res,$i,linha) . "' ";
				if ($linha == pg_result ($res,$i,linha) ) echo " selected ";
				echo ">";
				echo pg_result ($res,$i,nome);
				echo "</option>";
			}
			echo "</select>";
		}
		?>
	</td>
	<td nowrap>
		<input type="text" name="referencia" size="10" maxlength="20" value="<? echo $referencia ?>" class="frm" >&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pedido.referencia,document.frm_pedido.descricao,'referencia')"style="cursor:pointer;">
	</td>
	<td nowrap>
		<input type="text" name="descricao" size="30" maxlength="60" value="<? echo $descricao ?>" class="frm">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pedido.referencia,document.frm_pedido.descricao,'descricao')" style="cursor:pointer;">
	</td>
</tr>
</table>

<? } ?>

<table width='700' align='center' border='0' cellspacing='3' cellpadding='3' class='formulario'>
<tr>
	<td width="10">&nbsp;</td>
	<? if($login_fabrica<>7) echo '<td width="170">'; else echo '<td>'; ?>
	
		
			Tipo do Pedido
		
	</td>
	<? if($login_fabrica==7) $tam=100; else $tam=153; ?>
	<td width="<? echo $tam; ?>">
		
			Tabela de Preços
		
	</td>
	<td>
		
			Condição de Pagamento
		
	</td>
	<? if ($login_fabrica==7){ ?>
		<td align='center'>
			
				Promocional
			
		</td>
		<td>
			
				Desconto
			
		</td>
	<? } ?>
	<td>
		
			Tipo de Frete
		
	</td>
	<? if ($login_fabrica==7){ ?>
		<td align='center'>
			
				Valor do Frete
			
		</td>
	<? } ?>
</tr>

<tr>
	<td width="10">&nbsp;</td>
	<td>
		<?
		$sql = "SELECT * FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica";
		if ($login_fabrica == 3) {
			$sql = "SELECT * FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica AND tipo_pedido IN (2,3)";
		}
		if ($login_fabrica == 5) {
			$sql = "SELECT * FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica AND tipo_pedido in(41)";
		}
		if ($login_fabrica == 6) {
			$sql = "SELECT * FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica AND tipo_pedido in(4,112)";
		}

		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) > 0) {
			echo "<select name='tipo_pedido' size='1' class='frm'>";
			echo "<option selected> </option>";
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				echo "<option value='" . pg_result ($res,$i,tipo_pedido) . "' ";
				if ($tipo_pedido == pg_result ($res,$i,tipo_pedido) ) echo " selected ";
				echo ">";
				echo pg_result ($res,$i,descricao);
				echo "</option>";
			}
			echo "</select>";
		}
		?>
	</td>
	<td width="100">
		<?
		if($login_fabrica == 7){
			echo "<input name='tabela' size=5 id='tabela' value='$tabela' type='hidden'>Junto com <br>a Condição";
		}else{
			$sql = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa IS TRUE";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) > 0) {
				echo "<select name='tabela' size='1' class='frm'>";
				echo "<option selected> </option>";
				for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
					echo "<option value='" . pg_result ($res,$i,tabela) . "' ";
					if ($tabela == pg_result ($res,$i,tabela) ) echo " selected ";
					echo ">";
					echo pg_result ($res,$i,sigla_tabela);
					echo "</option>";
				}
				echo "</select>";
			}
		}
		?>
	</td>
	<td>
		<?
		if($login_fabrica == 5) {
			$condicao1 = " AND visivel IS TRUE ";
		}
		$sql = "SELECT * FROM tbl_condicao WHERE fabrica = $login_fabrica $condicao1 ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text,10,'0');";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) > 0) {
			echo "<select name='condicao' size='1' class='frm'>";
			echo "<option selected> </option>";
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				echo "<option value='" . pg_result ($res,$i,condicao) . "' ";
				if ($condicao == pg_result ($res,$i,condicao) ) echo " selected ";
				echo ">";
				echo pg_result ($res,$i,descricao);
				echo "</option>";
			}
			echo "</select>";
		}
		?>
	</td>
	<? if ($login_fabrica==7) { ?>
		<td align='center'>
			<input type='checkbox' name="promocao" id="promocao" value='t' <? if ($promocao == "t") echo " CHECKED " ?>>

		</td>
		<td align='center'>
		<?
		$sql = "SELECT * FROM tbl_desconto_pedido WHERE fabrica = $login_fabrica AND CURRENT_DATE >= data_vigencia AND termino_vigencia >= CURRENT_DATE ORDER BY data_vigencia";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) > 0) {
			echo "<select name='desconto' size='1' class='frm'>";
			echo "<option selected> </option>";
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				echo "<option value='" . pg_result ($res,$i,desconto) . "' ";
				if ($desconto == pg_result ($res,$i,desconto) ) echo " selected ";
				echo ">";
				echo pg_result ($res,$i,desconto)." %";
				echo "</option>";
			}
			echo "</select>";
		}else{
			echo "<p>-</p>";
		}
		?>
		</td>
	<? } ?>
	<td>
		<SELECT name="tipo_frete" size="1" onChange='javascript:verificaFrete(this)' class='frm'>
		<option selected> </option>
		<option value="FOB" <? if ($tipo_frete == "FOB") echo " selected " ?> >FOB</option>
		<option value="CIF" <? if ($tipo_frete == "CIF") echo " selected " ?> >CIF</option>
		</SELECT>
	</td>
		<? 
		# fabio colocar um campo de valor de frete que ira calcular automatico enquanto esta digitando o pedido. Quando CIF....a transportadora é correio, quando fob, pedir para incluir o codigo da transportadora do DATASUL.
		if ($login_fabrica==7) { ?>
			<td>
				<span id='text_valor_frete'></span>
				<input type='text' name="valor_frete" id="valor_frete" size='10' value='<?=$valor_frete?>' <? if ($tipo_frete == "FOB") echo " DISABLED " ?> class='frm'>
			</td>
		<? } ?>
</tr>
</table>

<table class="formulario" width='700' align='center' border='0' cellspacing='3' cellpadding='3'>
<tr>
	<td width="10">&nbsp;</td>
	<? if ($login_fabrica!=7) { ?>
	<td width="170">
		
		Pedido Cliente
		
	</td>
	<? } ?>
	<? if($login_fabrica==7) $tam=113; else $tam=153; ?>
	<td width="<? echo $tam; ?>">
		
		Validade
		
	</td>
	<td>
		
		Entrega
		
	</td>
<?

	$sql = "SELECT  tbl_fabrica.pedido_escolhe_transportadora
			FROM    tbl_fabrica
			WHERE   tbl_fabrica.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) > 0) {
		$pedido_escolhe_transportadora = trim(pg_result ($res,0,pedido_escolhe_transportadora));
	}

	if ($pedido_escolhe_transportadora == 't'){
?>
	<td>
		
		Transportadora
		
	</td>
<?
	}
?>
</tr>

<tr>
	<td width="10">&nbsp;</td>
	<? if ($login_fabrica!=7) { ?>
	<td>
		<input type="text" name="pedido_cliente" size="10" maxlength="20" value="<? echo $pedido_cliente ?>" class="frm">
	</td>
	<?}?>

	<?
	if (strlen ($validade) == 0) $validade = "10 dias";
	if (strlen ($entrega) == 0)  $entrega  = "15 dias";
	?>
	<td>
		<input type="text" name="validade" size="10" maxlength="20" value="<? echo $validade ?>" class="frm">
	</td>
	<td>
		<input type="text" name="entrega" size="10" maxlength="20" value="<? echo $entrega ?>" class="frm">
	</td>
<?
	if ($pedido_escolhe_transportadora == 't'){
?>
	<td>
<?
	$sql = "SELECT	tbl_transportadora.transportadora        ,
					tbl_transportadora.cnpj                  ,
					tbl_transportadora.nome                  ,
					tbl_transportadora_fabrica.codigo_interno
			FROM	tbl_transportadora
			JOIN	tbl_transportadora_fabrica USING(transportadora)
			WHERE	tbl_transportadora_fabrica.fabrica = $login_fabrica
			AND		tbl_transportadora_fabrica.ativo  = 't' ";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {

		if (pg_numrows ($res) <= 20) {

			echo "		<select name='transportadora' class='frm'>";
			echo "			<option selected></option>";
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				echo "<option value='".pg_result($res,$i,transportadora)."' ";
				if ($transportadora == pg_result($res,$i,transportadora) ) echo " selected ";
				echo ">";
				echo pg_result($res,$i,codigo_interno) ." - ".pg_result($res,$i,nome);
				echo "</option>\n";
			}
			echo "		</select>";

		}else{

			echo "		<input type='hidden' name='transportadora' value=''>";
			echo "		<input type='text'   name='transportadora_codigo' size='6' maxlength='10' value='$transportadora_codigo' class='textbox' "; if ($login_fabrica == 5) echo " onblur=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_codigo,'codigo')\"";
			echo ">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_codigo,'codigo')\" style='cursor:pointer;'>";
			echo "		<input type='hidden' name='transportadora_cnpj' value='$transportadora_cnpj' class='textbox' >";
			echo "		<input type='text' name='transportadora_nome' size='15' maxlength='50' value='$transportadora_nome' class='textbox' "; if ($login_fabrica == 5) echo " onblur=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_nome,'nome')\"";
			echo ">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_nome,'nome')\" style='cursor:pointer;'>";

		}

	}else{

		echo " - - - ";

	}

?>
	</td>
<?
	}
?>
</tr>
</table>

<?
if ($login_fabrica <> 15){
?>
<table class="formulario" width='700' align='center' border='0' cellspacing='3' cellpadding='3'>
<tr>
	<td width="10">&nbsp;</td>
	<td>
		
		Mensagem
		
	</td>
</tr>
<tr>
	<td width="10">&nbsp;</td>
	<td>
		<input type="text" name="obs" size="80" value="<? echo $obs ?>" class="frm">
	</td>
</tr>
</table>
<?
}
?>
<? if ($login_fabrica==7){?>
<br>
<input class='frm' type='button' onClick='javascript:calcular_frete();' value='Atualizar Valor do Frete'>
<?}?>
<br>
<p>
<div style="position: relative;height: 480px;overflow-y: auto;width:718px; margin: auto;padding-left:20px">
	<table width="700" border="0" cellspacing="3" cellpadding="0" align='center' class='formulario'>
	<tr height="20" class="titulo_coluna">
		<td>Referência Componente</td>
		<td>Descrição Componente</td>
		<td>Qtde</td>
		<?if ($login_fabrica == 50) {?>
			<td>Defeito</td>
		<?}?>
	</tr>
		
		<?
		if (strlen($pedido) > 0) {
			$sql = "SELECT      tbl_peca.peca
					FROM        tbl_pedido_item
					JOIN        tbl_peca   USING (peca)
					JOIN        tbl_pedido USING (pedido)
					WHERE       tbl_pedido_item.pedido = $pedido
					ORDER BY    tbl_peca.referencia, tbl_pedido_item.pedido_item;";
			$ped = pg_exec ($con,$sql);
			$qtde_peca = pg_numrows($ped);
		}

		$qtde_item = $qtde_peca > 80 ? $qtde_peca : 80;
		
		echo "<input class='frm' type='hidden' name='qtde_item' value='$qtde_item'>";
		
		for ($i = 0 ; $i < $qtde_item ; $i++) {
			if (strlen($pedido) > 0) {
				if ($qtde_peca > $i) {
					$peca = trim(pg_result($ped,$i,peca));
				}else{
					$peca='';
				}
				if (strlen($peca) > 0 ){				
					$sql = "SELECT  tbl_pedido_item.pedido_item,
									tbl_pedido_item.qtde       ,
									tbl_pedido_item.preco      ,
									tbl_peca.referencia        ,
									tbl_peca.origem            ,
									tbl_peca.descricao
							FROM    tbl_pedido_item
							JOIN    tbl_peca USING (peca)
							WHERE   tbl_pedido_item.pedido = $pedido
							AND     tbl_pedido_item.peca   = $peca
							ORDER BY    tbl_peca.referencia";
					$aux_ped = pg_exec ($con,$sql);
					$novo            = 'f';
					$item            = trim(pg_result($aux_ped,0,pedido_item));
					$peca_referencia = trim(pg_result($aux_ped,0,referencia));
					$peca_descricao  = trim(pg_result($aux_ped,0,descricao));
					$qtde            = trim(pg_result($aux_ped,0,qtde));
					$preco           = trim(pg_result($aux_ped,0,preco));
					$origem          = trim(pg_result($aux_ped,0,origem));					
				}else{
					$novo            = 't';
					$item            = $HTTP_POST_VARS["item".     $aux];
					$peca_referencia = $HTTP_POST_VARS["peca_referencia_" . $i];
					$peca_descricao  = $HTTP_POST_VARS["peca_descricao_"  . $i];
					$qtde            = $HTTP_POST_VARS["qtde_"            . $i];
					$preco           = $HTTP_POST_VARS["preco_"           . $i];
					
				}
			}else{
				$novo            = 't';
				$item            = $HTTP_POST_VARS["item".     $aux];
				$peca_referencia = $HTTP_POST_VARS["peca_referencia_" . $i];
				$peca_descricao  = $HTTP_POST_VARS["peca_descricao_"  . $i];
				$qtde            = $HTTP_POST_VARS["qtde_"            . $i];
				$preco           = $HTTP_POST_VARS["preco_"           . $i];
			}
			
			#if (strlen ($msg_erro) > 0) {
			#	$peca_referencia = $HTTP_POST_VARS["peca_referencia_" . $i];
			#	$peca_descricao  = $HTTP_POST_VARS["peca_descricao_"  . $i];
			#	$qtde            = $HTTP_POST_VARS["qtde_"            . $i];
			#	$preco           = $HTTP_POST_VARS["preco_"           . $i];
			#}
			
			echo "<input type='hidden' name='novo$i' value='$novo'>\n";
			echo "<input type='hidden' name='item$i' value='$item'>\n";
		?>
		<tr <? if ($linha_erro == $i and strlen ($msg_erro) > 0) echo "bgcolor='#ffcccc'" ?>>
			<td align='center'><input class="frm" type="text" name="peca_referencia_<? echo $i ?>" size="15" rel='peca' alt='<?=$i?>' value="<? echo $peca_referencia ?>" onblur='<? if ($login_fabrica == 5) { echo " document.frm_pedido.lupa_peca_referencia_$i.click(); " ; } ?>;' ><img id='lupa_peca_referencia_<? echo $i ?>' src='imagens/lupa.png' alt="Clique para pesquisar por referência do componente" border='0' hspace='5' align='absmiddle' onclick="fnc_pesquisa_peca (window.document.frm_pedido.peca_referencia_<? echo $i ?> , window.document.frm_pedido.peca_descricao_<? echo $i ?> , 'referencia')" style="cursor:pointer;"></td>
			<td align='center'><input class="frm" type="text" name="peca_descricao_<? echo $i ?>" size="60" value="<? echo $peca_descricao ?>" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_peca ( window.document.frm_pedido.peca_referencia_<? echo $i ?> , window.document.frm_pedido.peca_descricao_<? echo $i ?>,'descricao')" <? } ?>><img id='lupa_peca_descricao_<? echo $i ?>' src='imagens/lupa.png' alt="Clique para pesquisar por descrição do componente" border='0' hspace='5' align='absmiddle' onclick="fnc_pesquisa_peca ( window.document.frm_pedido.peca_referencia_<? echo $i ?> , window.document.frm_pedido.peca_descricao_<? echo $i ?>,'descricao')" style="cursor:pointer;"></td>
			<td align='center'><input class="frm" type="text" name="qtde_<? echo $i ?>" size="5"  value="<? echo $qtde ?>"></td>
					<?if ($login_fabrica == 50) {

			echo "<td><select name='defeito_$i' class='frm' onfocus='defeitoLista(document.frm_pedido.peca_referencia_$i.value,$i);'>";
				echo "<option id='op_$i' value=''>selecione o defeito</option></td>";
			echo "</select>";
			?>

		</tr>
		<?
			}
		}
		?>
		</table>
	</div>
	</td>
	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>

<tr>
	<td height="27" valign="middle" align="center" colspan="3" >
		<input type='hidden' name='btn_acao' value=''>
		<button type="button" value="" id='gravar'	 title="Gravar formulário">Gravar</button>
		<button type="button" value="" id='apagar'	 title="Apagar Pedido">Apagar</button>
		<button type="button" value="" id='reset'	 title="Limpar formulário">Limpar</button>
<?	if ($login_fabrica == 24 and in_array($status_pedido, array(2, 3, 9, 14, 17, 18))) {
		$chkd = ($reexportar == 't') ? ' checked':'';	?>
		<span style='font-size:11px'>Pedido <?=$status_pedido_desc?></span>
		<input type="checkbox" value="t" id='reexportar'<?=$chkd?> name='reexportar' title="Exportar novamente o pedido">
		<label style='font-size:11px' for="reexportar">Exportar novamente</label>
<?}?>
	</td>
</tr>

</form>

</table>
<script type="text/javascript">
	$('button').click(function() {
		var id		= $(this).attr('id');
		var btn_acao= $('input[name=btn_acao]');
		if (btn_acao.val() != '') {
			alert ('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.');
			return false;
		}
		if (id == 'limpar') {window.location.reload();}
		else {
			btn_acao.val(id);
			document.frm_pedido.submit();
		}
	});
</script>
<p>

<? include "rodape.php"; ?>
