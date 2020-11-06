<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

include 'funcoes.php';

if (strlen($_POST['call_center']) > 0) $call_center = trim($_POST['call_center']);
if (strlen($_GET['call_center']) > 0) $call_center = trim($_GET['call_center']);

$btn_acao = strtolower ($_POST['btn_acao']);

$msg_erro = "";

if ($btn_acao == "continuar") {
	$sua_os = trim($_POST['sua_os']);
	
	if (strlen($sua_os) > 0){
		$sql = "SELECT  to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura,
						tbl_posto.nome                               AS nome_posto   ,
						tbl_posto_fabrica.codigo_posto               AS codigo_posto ,
						tbl_os.consumidor_nome                                       ,
						tbl_os.consumidor_cpf                                        ,
						tbl_os.consumidor_cidade                                     ,
						tbl_os.consumidor_estado                                     ,
						tbl_os.revenda_nome                                          ,
						tbl_os.cliente                                               ,
						tbl_os.nota_fiscal                                           ,
						to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf      ,
						tbl_os.serie                                                 ,
						tbl_os.consumidor_revenda                                    ,
						tbl_produto.referencia                                       ,
						tbl_produto.descricao                                        
				FROM    tbl_os
				JOIN    tbl_posto         USING (posto)
				JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.posto = tbl_os.posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_admin   on tbl_admin.admin = tbl_os.admin
				LEFT JOIN tbl_produto USING (produto)
				WHERE   tbl_os.sua_os  = '$sua_os'
				AND     tbl_os.fabrica = $login_fabrica";
		$resOS = @pg_exec($con,$sql);
		
		$msg_erro = @pg_errormessage($con);
		
		if (strlen($msg_erro) == 0 AND @pg_numrows($resOS) > 0){
			$data_abertura      = pg_result($resOS,0,data_abertura);
			$posto_nome         = pg_result($resOS,0,nome_posto);
			$posto_codigo       = pg_result($resOS,0,codigo_posto);
			$consumidor_nome    = pg_result($resOS,0,consumidor_nome);
			$consumidor_cpf     = pg_result($resOS,0,consumidor_cpf);
			$consumidor_cidade  = pg_result($resOS,0,consumidor_cidade);
			$consumidor_estado  = pg_result($resOS,0,consumidor_estado);
			$revenda_nome       = pg_result($resOS,0,revenda_nome);
			$cliente            = pg_result($resOS,0,cliente);
			$produto_nome       = pg_result($resOS,0,descricao);
			$produto_referencia = pg_result($resOS,0,referencia);
			$produto_serie      = pg_result($resOS,0,serie);
			$nota_fiscal        = pg_result($resOS,0,nota_fiscal);
			$data_nf            = pg_result($resOS,0,data_nf);
			
			$encontrou_os = 't';
		}else{
			//$msg_erro = "A Ordem de Serviço $sua_os não encontrada no sistema.";
		}
	}
}

if ($btn_acao == "continuar" AND $encontrou_os <> 't' AND strlen($msg_erro) == 0) {
	// VERIFICA SE PRODUTO EXISTE E LISTA RESULTADO, CASO SEJA > 1
	$produto            = trim($_POST['produto']);
	$produto_referencia = trim($_POST['produto_referencia']);
	$produto_referencia = strtoupper($produto_referencia);
	$produto_nome       = trim($_POST['produto_nome']);

	if (strlen($produto_referencia) > 0 OR strlen($produto_nome) > 0) {
		$sql = "SELECT	tbl_produto.produto   ,
						tbl_produto.referencia,
						tbl_produto.descricao 
				FROM	tbl_produto
				JOIN	tbl_linha USING (linha)
				WHERE	tbl_linha.fabrica = $login_fabrica ";
		if (strlen($produto) > 0) {
			$sql .= "AND tbl_produto.referencia_pesquisa = '$produto' ";
		}else{
			$sql .= "AND UPPER (tbl_produto.descricao) ILIKE '%$produto_nome%' ";
		}
		$resProduto = pg_exec ($con,$sql);
		if($login_fabrica != 15){
			if (pg_numrows($resProduto) == 0) {
				$msg_erro = "Produto não cadastrado.";
				$btn_acao = "";
			}elseif (pg_numrows($resProduto) == 1){
				$produto_referencia = pg_result($resProduto,0,referencia);
				$produto_nome       = pg_result($resProduto,0,descricao);
			}else{
				$msg_erro = "Selecione um dos Produtos listados.";
				$selecione_produto = 't';
				$btn_acao = "";
			}
		}
	}
}

if ($btn_acao == "continuar" AND $encontrou_os <> 't' AND strlen($msg_erro) == 0) {
	// VERIFICA SE POSTO EXISTE E LISTA RESULTADO, CASO SEJA > 1
	$posto               = trim($_POST['posto']);
	$posto_codigo        = trim($_POST['posto_codigo']);
	$posto_nome          = trim($_POST['posto_nome']);
	$consumidor_cidade   = trim($_POST['consumidor_cidade']);
	$consumidor_estado   = trim($_POST['consumidor_estado']);
	
	if (strlen($posto_codigo) > 0 OR strlen($posto_nome) > 0) {
		$sql = "SELECT	tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome                ,
						tbl_posto.nome_fantasia       ,
						tbl_posto.cidade              ,
						tbl_posto.estado
				FROM	tbl_posto
				JOIN	tbl_posto_fabrica USING (posto)
				WHERE	tbl_posto_fabrica.fabrica = $login_fabrica ";
		
		if (strlen($posto) > 0) {
			$sql .= "AND tbl_posto_fabrica.codigo_posto = '$posto' ";
		}else{
			$sql .= "AND (tbl_posto.nome         ILIKE '%$posto_nome%'
					OR    tbl_posto.nome_fantasia ILIKE '%$posto_nome%') ";
		}
		
		//if (strlen($consumidor_cidade) > 0 AND strlen($posto) == 0) $sql .= " AND trim(tbl_posto.cidade) ILIKE '$consumidor_cidade%' ";
		//if (strlen($consumidor_estado) > 0 AND strlen($posto) == 0) $sql .= " AND trim(tbl_posto.estado) ILIKE '$consumidor_estado' ";
		
		$sql .= "ORDER BY tbl_posto.nome_fantasia, tbl_posto.nome";
		$resPosto = pg_exec ($con,$sql);
		if($login_fabrica != 15){
			if (pg_numrows($resPosto) == 0) {
				$msg_erro = "Posto não cadastrado.";
				$btn_acao = "";
			}elseif (pg_numrows($resPosto) == 1){
				$posto_codigo      = pg_result($resPosto,0,codigo_posto);
				$posto_nome        = pg_result($resPosto,0,nome);
				$consumidor_cidade = pg_result($resPosto,0,cidade);
				$consumidor_estado = pg_result($resPosto,0,estado);
			}else{
				$msg_erro = "Selecione um dos Postos listados.";
				$selecione_posto = 't';
				$btn_acao = "";
			}
		}
	}
}

if ($btn_acao == "continuar" AND strlen($msg_erro) == 0) {
	$natureza                                                 = trim($_POST['natureza']);
	if (strlen($cliente)            == 0) $cliente            = trim($_POST['cliente']);
	if (strlen($consumidor_nome)    == 0) $consumidor_nome    = trim($_POST['consumidor_nome']);
	if (strlen($consumidor_cpf)     == 0) $consumidor_cpf     = trim($_POST['consumidor_cpf']);
	if (strlen($consumidor_cidade)  == 0) $consumidor_cidade  = trim($_POST['consumidor_cidade']);
	if (strlen($consumidor_estado)  == 0) $consumidor_estado  = trim($_POST['consumidor_estado']);
	if (strlen($posto_nome)         == 0) $posto_nome         = trim($_POST['posto_nome']);
	if (strlen($posto_codigo)       == 0) $posto_codigo       = trim($_POST['posto_codigo']);
	if (strlen($posto)               > 0) $posto_codigo       = trim($_POST['posto']);
	if (strlen($sua_os)             == 0) $sua_os             = trim($_POST['sua_os']);
	if (strlen($data_abertura)      == 0) $data_abertura      = trim($_POST['data_abertura']);
	if (strlen($produto_referencia) == 0) $produto_referencia = trim($_POST['produto_referencia']);
	if (strlen($produto)             > 0) $produto_referencia = trim($_POST['produto']);
	if (strlen($produto_nome)       == 0) $produto_nome       = trim($_POST['produto_serie']);
	if (strlen($produto_serie)      == 0) $produto_serie      = trim($_POST['produto_serie']);
	if (strlen($revenda_nome)       == 0) $revenda_nome       = trim($_POST['revenda_nome']);
	if (strlen($nota_fiscal)        == 0) $nota_fiscal        = trim($_POST['nota_fiscal']);
	if (strlen($data_nf)            == 0) $data_nf            = trim($_POST['data_nf']);
	
	if (strlen ($revenda_nome) == 0)
		$xrevenda_nome = 'null';
	else
		$xrevenda_nome = "'" . strtoupper ($revenda_nome) . "'" ;

	if (strlen ($email) == 0)
		$xemail = 'null';
	else
		$xemail = "'" . strtolower ($email) . "'" ;

	if (strlen ($produto_serie) == 0)
		$xproduto_serie = 'null';
	else
		$xproduto_serie = "'" . strtoupper ($produto_serie) . "'" ;

	//if ( (strlen($produto_nome) > 0 OR strlen($produto_referencia) > 0) AND strlen($data_nf) < 10)
	//	$msg_erro = "Digite a data de compra do produto.";
	
	if (strlen ($produto_nome) == 0)
		$xproduto_nome = 'null';
	else
		$xproduto_nome = "'" . $produto_nome . "'" ;

	if (strlen ($data_abertura) == 0)
		$xdata_abertura = 'null';
	else
		$xdata_abertura = "'" . formata_data ($data_abertura) . "'" ;

	if (strlen ($sua_os) == 0)
		$xsua_os = 'null';
	else
		$xsua_os = "'" . $sua_os . "'" ;

	if (strlen (trim ($consumidor_estado)) == 0)
		$msg_erro  = "Digite o estado.";
	else
		$xconsumidor_estado = "'" . strtoupper($consumidor_estado). "'" ;	
	
	if (strlen (trim ($consumidor_cidade)) == 0)
		$msg_erro  = "Digite a cidade.";
	else
		$xconsumidor_cidade = "'" . strtoupper($consumidor_cidade) . "'" ;

//CPF de consumidor é obrigatório para a Latina
//Modificado por Fernando.
	if($login_fabrica == 15){
		if ($natureza == 'Reclamação' AND strlen($consumidor_cpf) == 0)
			$msg_erro = "Por favor, digite o CPF do consumidor.";
	}else{
		if (strlen (trim($consumidor_cpf)) == 0)
			$xconsumidor_cpf = 'null';
		else
			$xconsumidor_cpf = "'" . $consumidor_cpf . "'" ;
	}

	if($login_fabrica == 15){
		if($natureza == 'Reclamação' AND strlen($produto_nome) == 0){
			$msg_erro = "Escolha o produto.";
		}
	}

	if (strlen ($consumidor_nome) == 0)
		$msg_erro  = "Digite o nome do cliente.";
	else
		$xconsumidor_nome = "'" . $consumidor_nome . "'" ;
	
	if (strlen ($natureza) == 0)
		$msg_erro  = "Selecione a natureza do chamado.";
	else
		$xnatureza = "'" . $natureza . "'" ;

	if (strlen ($nota_fiscal) == 0)
		$xnota_fiscal = 'null';
	else
		$xnota_fiscal = "'" . $nota_fiscal . "'" ;

	if (strlen ($data_nf) == 0)
		$xdata_nf = 'null';
	else
		$xdata_nf = "'" . formata_data ($data_nf) . "'" ;

	if (strlen($msg_erro) == 0){ 
	// seleciona a OS
		if (strlen($xsua_os) > 0 AND $xsua_os <> 'null'){
			$sql = "SELECT tbl_os.*
					FROM   tbl_os
					WHERE  tbl_os.fabrica = $login_fabrica
					AND    tbl_os.sua_os  = $xsua_os";
			$res = pg_exec ($con,$sql);
			if (pg_numrows($res) > 0) 
				$os = pg_result($res,0,0);
			else
				$os = 'null';
		}
		
		// seleciona o posto se digitado
		$xposto_codigo = str_replace ("-","",$posto_codigo);
		$xposto_codigo = str_replace (".","",$xposto_codigo);
		$xposto_codigo = str_replace ("/","",$xposto_codigo);
		$xposto_codigo = substr ($xposto_codigo,0,14);
		
		if (strlen($xposto_codigo) > 0){
			$sql = "SELECT tbl_posto.posto 
					FROM   tbl_posto 
					JOIN   tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto 
					AND    tbl_posto_fabrica.fabrica            = $login_fabrica 
					WHERE  tbl_posto_fabrica.codigo_posto       = '$xposto_codigo'";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows($res) > 0) $posto = pg_result($res,0,0);
		}else{
			$posto = 'null';
		}

		// seleciona consumidor
		#$xconsumidor_nome = str_replace ("'","",$consumidor_nome);
		
		if (strlen(trim($consumidor_cpf)) > 0 and $xconsumidor_cpf <> "null"){
			$xconsumidor_cpf  = str_replace ("-","",$consumidor_cpf);
			$xconsumidor_cpf  = str_replace (".","",$xconsumidor_cpf);
			$xconsumidor_cpf  = str_replace ("/","",$xconsumidor_cpf);
			$xconsumidor_cpf  = str_replace ("'","",$xconsumidor_cpf);
			$xconsumidor_cpf  = trim (substr ($xconsumidor_cpf,0,14));
			$xconsumidor_cpf  = "'". $xconsumidor_cpf ."'";
		}else{
			$xconsumidor_cpf = "null";
		}
		# ---------- TRANSACTION ---------- #
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		if (strlen($consumidor_cidade) > 0 && $consumidor_cidade != "null"){
			$sql = "SELECT fnc_qual_cidade ($xconsumidor_cidade,$xconsumidor_estado)";
			$res = pg_exec ($con,$sql);
			$cidade = pg_result ($res,0,0);
		}
		if (strlen($cliente) == 0) {
			if (strlen(trim($xconsumidor_cpf)) > 0){
				if ($xconsumidor_cpf <> "null") {
					$sql = "SELECT tbl_cliente.cliente
							FROM   tbl_cliente
							WHERE  tbl_cliente.cpf = $xconsumidor_cpf";
					$res = pg_exec ($con,$sql);
					
					if (pg_numrows($res) > 0){
						$cliente = pg_result($res,0,0);
					}else{
						// insere
						$sql = "INSERT INTO tbl_cliente (
									nome,
									cpf ,
									cidade
								) VALUES (
									'$consumidor_nome',
									$xconsumidor_cpf  ,
									$cidade
						)";
						$res = pg_exec ($con,$sql);
						
						$msg_erro = pg_errormessage($con);
						$msg_erro = substr($msg_erro,6);
						
						if (strlen ($msg_erro) == 0) {
							if (strlen($cliente) == 0) {
								$res = pg_exec ($con,"SELECT CURRVAL ('seq_cliente')");
								$cliente = pg_result ($res,0,0);
							}
						}
					}
				}else{
					// insere
					$sql = "INSERT INTO tbl_cliente (
								nome,
								cpf ,
								cidade
							) VALUES (
								'$consumidor_nome',
								$xconsumidor_cpf  ,
								$cidade
							)";
					$res = pg_exec ($con,$sql);
					
					$msg_erro = pg_errormessage($con);
					$msg_erro = substr($msg_erro,6);
					
					if (strlen ($msg_erro) == 0) {
						if (strlen($cliente) == 0) {
							$res = pg_exec ($con,"SELECT CURRVAL ('seq_cliente')");
							$cliente = pg_result ($res,0,0);
						}
					}
				}
			}else{
				if (strlen($cidade) > 0 and strlen($estado) > 0){
					$sql = "INSERT INTO tbl_cliente (nome, cidade) values('$consumidor_nome', $cidade)";
					$res = pg_exec ($con,$sql);
					
					$msg_erro = pg_errormessage($con);
					$msg_erro = substr($msg_erro,6);
					
					if (strlen ($msg_erro) == 0) {
						if (strlen($cliente) == 0) {
							$res = pg_exec ($con,"SELECT CURRVAL ('seq_cliente')");
							$cliente = pg_result ($res,0,0);
						}
					}
				}//fim if(cidade/estado)
			}
		}
		
		// seleciona produto
		$xproduto_referencia = str_replace ("-","",$produto_referencia);
		$xproduto_referencia = str_replace (" ","",$xproduto_referencia);
		$xproduto_referencia = str_replace ("/","",$xproduto_referencia);
		$xproduto_referencia = str_replace (".","",$xproduto_referencia);

		if (strlen($xproduto_referencia) > 0){
			$sql = "SELECT tbl_produto.produto
					FROM   tbl_produto
					JOIN   tbl_linha USING (linha)
					WHERE  UPPER (tbl_produto.referencia_pesquisa) = UPPER ('$xproduto_referencia')
					AND    tbl_linha.fabrica                       = $login_fabrica";

			$res = pg_exec ($con,$sql);
			if (pg_numrows($res) > 0) 
				$produto = pg_result($res,0,0);
			else
				$msg_erro = "Produto não cadastrado.";
		}else{
			$produto = 'null';
		}
		
		if (strlen ($consumidor_cpf) <> 0 and strlen ($consumidor_cpf) <> 11 and strlen ($consumidor_cpf) <> 14) 
			$msg_erro .= 'Tamanho do CPF/CNPJ do cliente inválido';
		
		if (strlen ($msg_erro) == 0) {
			if (strlen ($callcenter) == 0) {
			/*================ INSERE NOVA OS =========================*/
				$sql = "INSERT INTO tbl_callcenter (
							fabrica          ,
							produto          ,
							serie            ,
							admin            ,
							data             ,
							cliente          ,
							email            ,
							revenda_nome     ,
							posto            ,
							sua_os           ,
							data_abertura    ,
							natureza         ,
							solucionado      ,
							nota_fiscal      ,
							data_nf      
						) VALUES (
							$login_fabrica   ,
							$produto         ,
							$xproduto_serie  ,
							$login_admin     ,
							current_timestamp,
							$cliente         ,
							$xemail           ,
							$xrevenda_nome   ,
							$posto           ,
							$xsua_os         ,
							$xdata_abertura  ,
							$xnatureza       ,
							'f'              ,
							$xnota_fiscal    ,
							$xdata_nf    
						);";
			}else{
				/*================ ALTERA =========================*/
				$sql = "UPDATE tbl_callcenter SET 
							produto         = $produto         ,
							serie           = $xproduto_serie  ,
							admin           = $login_admin     ,
							cliente         = $cliente         ,
							email           = $xemail           ,
							revenda_nome    = $xrevenda_nome   ,
							posto           = $posto           ,
							sua_os          = $xsua_os         ,
							data_abertura   = $xdata_abertura  ,
							natureza        = $xnatureza       ,
							nota_fiscal     = $xnota_fiscal    ,
							data_nf         = $xdata_nf    
						WHERE callcenter    = $callcenter
						AND   fabrica       = $login_fabrica ";
			}
//echo nl2br($sql);
			$res = pg_exec ($con,$sql);
	
			$msg_erro = pg_errormessage($con);
			$msg_erro = substr($msg_erro,6);

			if (strlen ($msg_erro) == 0 AND strlen($callcenter) == 0) {
				$res = pg_exec ($con,"SELECT CURRVAL ('seq_callcenter')");
				$callcenter = pg_result ($res,0,0);
			}
		}
	}//MSG_ERRO
	
	if (strlen ($msg_erro) > 0) {
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}else{
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header("Location: callcenter_cadastro_2.php?callcenter=$callcenter");
		exit;
	}
}


#  RECARREGA OS CAMPOS
if (strlen($msg_erro) > 0){
	$natureza = $_POST['natureza'];
	if (strlen($cliente)            == 0) $cliente            = trim($_POST['cliente']);
	if (strlen($consumidor_nome)    == 0) $consumidor_nome    = trim($_POST['consumidor_nome']);
	if (strlen($email)              == 0) $email              = trim($_POST['email']);
	if (strlen($consumidor_cpf)     == 0) $consumidor_cpf     = trim($_POST['consumidor_cpf']);
	if (strlen($consumidor_cidade)  == 0) $consumidor_cidade  = trim($_POST['consumidor_cidade']);
	if (strlen($consumidor_estado)  == 0) $consumidor_estado  = trim($_POST['consumidor_estado']);
	if (strlen($posto_nome)         == 0) $posto_nome         = trim($_POST['posto_nome']);
	if (strlen($posto_codigo)       == 0) $posto_codigo       = trim($_POST['posto_codigo']);
	if (strlen($sua_os)             == 0) $sua_os             = trim($_POST['sua_os']);
	if (strlen($data_abertura)      == 0) $data_abertura      = trim($_POST['data_abertura']);
	if (strlen($produto_referencia) == 0) $produto_referencia = trim($_POST['produto_referencia']);
	if (strlen($produto_nome)       == 0) $produto_nome       = trim($_POST['produto_serie']);
	if (strlen($produto_serie)      == 0) $produto_serie      = trim($_POST['produto_serie']);
	if (strlen($revenda_nome)       == 0) $revenda_nome       = trim($_POST['revenda_nome']);
	if (strlen($nota_fiscal)        == 0) $nota_fiscal        = trim($_POST['nota_fiscal']);
	if (strlen($data_nf)            == 0) $data_nf            = trim($_POST['data_nf']);
}


/*================ LE BASE DE DADOS =========================*/
if (strlen ($callcenter) > 0 AND strlen($msg_erro) == 0) {
	$sql = "SELECT	tbl_callcenter.callcenter                                            ,
					to_char(tbl_callcenter.data_abertura,'DD/MM/YYYY') as data_abertura  ,
					tbl_callcenter.serie                                                 ,
					tbl_callcenter.revenda_nome                                          ,
					tbl_callcenter.natureza                                              ,
					tbl_callcenter.sua_os                                                ,
					tbl_callcenter.cliente                                               ,
					tbl_callcenter.email                                                 ,
					tbl_callcenter.nota_fiscal                                           ,
					to_char(tbl_callcenter.data_nf,'DD/MM/YYYY')       as data_nf        ,
					tbl_cliente.nome        AS consumidor_nome                           ,
					tbl_cliente.cpf         AS consumidor_cpf                            ,
					tbl_cidade.nome         AS consumidor_cidade                         ,
					tbl_cidade.estado       AS consumidor_estado                         ,
					tbl_posto.nome          AS posto_nome                                ,
					tbl_posto_fabrica.codigo_posto AS posto_codigo                       ,
					tbl_posto.fone          AS posto_fone                                ,
					tbl_produto.descricao   AS produto_descricao                         ,
					tbl_produto.referencia  AS produto_referencia                        ,
					tbl_admin.login         AS atendente_nome                            
			FROM	tbl_callcenter
			JOIN	tbl_cliente   USING(cliente)
			JOIN	tbl_cidade    ON tbl_cidade.cidade   = tbl_cliente.cidade
			LEFT JOIN tbl_os      USING(os)
			LEFT JOIN tbl_posto   ON tbl_posto.posto     = tbl_callcenter.posto
			LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_callcenter.produto
			JOIN	tbl_admin     ON tbl_admin.admin     = tbl_callcenter.admin
			LEFT JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE	tbl_callcenter.callcenter = $callcenter
			AND		tbl_callcenter.fabrica    = $login_fabrica";
	$res = pg_exec ($con,$sql);

//	echo $sql."<br>".pg_numrows($res); exit;

	if (pg_numrows($res) > 0) {
		$callcenter          = pg_result ($res,0,callcenter);
		$serie               = pg_result ($res,0,serie);
		$revenda_nome        = pg_result ($res,0,revenda_nome);
		$natureza            = pg_result ($res,0,natureza);
		$sua_os              = pg_result ($res,0,sua_os);
		$cliente             = pg_result ($res,0,cliente);
		$email               = pg_result ($res,0,email);
		$consumidor_nome     = pg_result ($res,0,consumidor_nome);
		$consumidor_cpf      = pg_result ($res,0,consumidor_cpf);
		$consumidor_cidade   = pg_result ($res,0,consumidor_cidade);
		$consumidor_estado   = pg_result ($res,0,consumidor_estado);
		$posto_nome          = pg_result ($res,0,posto_nome);
		$posto_fone          = pg_result ($res,0,posto_fone);
		$posto_codigo        = pg_result ($res,0,posto_codigo);
		$produto_nome        = pg_result ($res,0,produto_descricao);
		$produto_referencia  = pg_result ($res,0,produto_referencia);
		$produto_serie       = pg_result ($res,0,serie);
		$atendente_nome      = pg_result ($res,0,atendente_nome);
		$data_abertura       = pg_result ($res,0,data_abertura);
		$nota_fiscal         = pg_result ($res,0,nota_fiscal);
		$data_nf             = pg_result ($res,0,data_nf);
	}
}

$title = "Atendimento Call-Center"; 
$layout_menu = 'callcenter';

include "cabecalho.php";

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

</style>

<!--=============== <FUNÇÕES> ================================!-->
<? include "javascript_pesquisas.php" ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<script language="JavaScript">

function fnc_pesquisa_posto_regiao(nome,cidade,estado) {
	if (cidade.value != "" || estado.value != "" || nome.value != ""){
		var url = "";
		url = "posto_pesquisa_regiao.php?nome=" + nome.value + "&cidade=" + cidade.value + "&estado=" + estado.value;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = document.frm_callcenter.posto_codigo;
		janela.nome    = document.frm_callcenter.posto_nome;
		janela.focus();
	}
}


/* ============= Função PESQUISA DE CONSUMIDOR POR NOME ====================
Nome da Função : fnc_pesquisa_consumidor_nome (nome, cpf)
=================================================================*/
function fnc_pesquisa_consumidor (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_consumidor.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cpf") {
		url = "pesquisa_consumidor.php?cpf=" + campo.value + "&tipo=cpf";
	}
	if (campo.value != "") {
		janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
		janela.cliente		= document.frm_callcenter.cliente;
		janela.nome			= document.frm_callcenter.consumidor_nome;
		janela.cpf			= document.frm_callcenter.consumidor_cpf;
		janela.rg			= document.frm_callcenter.consumidor_rg;
		janela.cidade		= document.frm_callcenter.consumidor_cidade;
		janela.estado		= document.frm_callcenter.consumidor_estado;
		janela.fone			= document.frm_callcenter.consumidor_fone;
		janela.endereco		= document.frm_callcenter.consumidor_endereco;
		janela.numero		= document.frm_callcenter.consumidor_numero;
		janela.complemento	= document.frm_callcenter.consumidor_complemento;
		janela.bairro		= document.frm_callcenter.consumidor_bairro;
		janela.cep			= document.frm_callcenter.consumidor_cep;
		janela.focus();
	}
}

//========================Latinatec====================================

/* ============= Função PESQUISA DE CONSUMIDOR POR NOME ====================
Nome da Função : fnc_pesquisa_consumidor_nome (nome, cpf)
=================================================================*/
function fnc_pesquisa_consumidor_callcenter (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_consumidor_callcenter.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cpf") {
		url = "pesquisa_consumidor_callcenter.php?cpf=" + campo.value + "&tipo=cpf";
	}
	if (campo.value != "") {
		janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
		janela.cliente		= document.frm_callcenter.cliente;
		janela.nome			= document.frm_callcenter.consumidor_nome;
		janela.cpf			= document.frm_callcenter.consumidor_cpf;
		janela.rg			= document.frm_callcenter.consumidor_rg;
		janela.cidade		= document.frm_callcenter.consumidor_cidade;
		janela.estado		= document.frm_callcenter.consumidor_estado;
		janela.fone			= document.frm_callcenter.consumidor_fone;
		janela.endereco		= document.frm_callcenter.consumidor_endereco;
		janela.numero		= document.frm_callcenter.consumidor_numero;
		janela.complemento	= document.frm_callcenter.consumidor_complemento;
		janela.bairro		= document.frm_callcenter.consumidor_bairro;
		janela.cep			= document.frm_callcenter.consumidor_cep;
		janela.focus();
	}
}




//========================Latinatec====================================



function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.nome			= document.frm_callcenter.revenda_nome;
	janela.cnpj			= document.frm_callcenter.revenda_cnpj;
	janela.fone			= document.frm_callcenter.revenda_fone;
	janela.cidade		= document.frm_callcenter.revenda_cidade;
	janela.estado		= document.frm_callcenter.revenda_estado;
	janela.endereco		= document.frm_callcenter.revenda_endereco;
	janela.numero		= document.frm_callcenter.revenda_numero;
	janela.complemento	= document.frm_callcenter.revenda_complemento;
	janela.bairro		= document.frm_callcenter.revenda_bairro;
	janela.cep			= document.frm_callcenter.revenda_cep;
	janela.email		= document.frm_callcenter.revenda_email;
	janela.focus();
}


function fnc_pesquisa_os (campo) {
	url = "pesquisa_os.php?sua_os=" + campo.value;
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.sua_os			= document.frm_callcenter.sua_os;
	janela.data_abertura	= document.frm_callcenter.data_abertura;
	janela.focus();
}

/* ============= Função FORMATA CNPJ =============================
Nome da Função : formata_cnpj (cnpj, form)
		Formata o Campo de CNPJ a medida que ocorre a digitação
		Parâm.: cnpj (numero), form (nome do form)
=================================================================*/
function formata_cnpj(cnpj, form){
	var mycnpj = '';
		mycnpj = mycnpj + cnpj;
		myrecord = "revenda_cnpj";
		myform = form;
		
		if (mycnpj.length == 2){
			mycnpj = mycnpj + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 6){
			mycnpj = mycnpj + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 10){
			mycnpj = mycnpj + '/';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 15){
			mycnpj = mycnpj + '-';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
}

/* ============= Função FORMATA CPF =============================
Nome da Função : formata_cpf (cpf, form)
		Formata o Campo de CPF a medida que ocorre a digitação
		Parâm.: cpf (numero), form (nome do form)
=================================================================*/
function formata_cpf(cpf, form){
	var mycpf = '';
		mycpf = mycpf + cpf;
		myrecord = "consumidor_cpf";
		myform = form;
		
		if (mycpf.length == 3){
			mycpf = mycpf + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycpf;
		}
		if (mycpf.length == 7){
			mycpf = mycpf + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycpf;
		}
		if (mycpf.length == 11){
			mycpf = mycpf + '-';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycpf;
		}
}

/* ========== Função AJUSTA CAMPO DE DATAS =========================
Nome da Função : ajustar_data (input, evento)
		Ajusta a formatação da Máscara de DATAS a medida que ocorre
		a digitação do texto.
=================================================================*/
function ajustar_data(input , evento)
{
	var BACKSPACE=  8; 
	var DEL=  46; 
	var FRENTE=  39; 
	var TRAS=  37; 
	var key; 
	var tecla; 
	var strValidos = "0123456789" ;
	var temp;
	tecla= (evento.keyCode ? evento.keyCode: evento.which ? evento.which : evento.charCode)

	if (( tecla == BACKSPACE )||(tecla == DEL)||(tecla == FRENTE)||(tecla == TRAS)) {
		return true; 
			}
		if ( tecla == 13) return false; 
		if ((tecla<48)||(tecla>57)){
			return false;
			}
		key = String.fromCharCode(tecla); 
		input.value = input.value+key;
		temp="";
		for (var i = 0; i<input.value.length;i++ )
			{
				if (temp.length==2) temp=temp+"/";
				if (temp.length==5) temp=temp+"/";
				if ( strValidos.indexOf( input.value.substr(i,1) ) != -1 ) {
					temp=temp+input.value.substr(i,1);
			}
			}
					input.value = temp.substr(0,10);
				return false;
}
</script>


<? 
if (strlen ($msg_erro) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
<? 
	echo $msg_erro;

	// recarrega os campos
	/*
	$natureza           = trim($_POST['natureza']);
	$consumidor_nome    = trim($_POST['consumidor_nome']);
	$consumidor_cpf     = trim($_POST['consumidor_cpf']);
	$consumidor_cliente = trim($_POST['consumidor_cliente']);
	$sua_os             = trim($_POST['sua_os']);
	$data_abertura      = trim($_POST['data_abertura']);
	$produto_referencia = trim($_POST['produto_referencia']);
	$produto_nome       = trim($_POST['produto_serie']);
	$produto_serie      = trim($_POST['produto_serie']);
	$revenda_nome       = trim($_POST['revenda_nome']);
	$posto_nome         = trim($_POST['posto_nome']);
	$posto_codigo       = trim($_POST['posto_codigo']);
	$data_nf        = trim($_POST['data_nf']);
	*/
?>
	</td>
</tr>
</table>
<?
}
//echo $msg_debug ;
?>

<?
$sql = "SELECT TO_CHAR (current_timestamp , 'DD/MM/YYYY' )";
$res = pg_exec ($con,$sql);
$hoje = pg_result ($res,0,0);


$sql = "SELECT * FROM tbl_providencia WHERE solucionado IS FALSE";
$res = pg_exec ($con,$sql);
if (strlen($res) > 0){
?>
<br>
<table width="700" border="0" cellpadding="2" cellspacing="1" align="center" bgcolor="#FF9933">
<tr>
	<td bgcolor="#ffffff"><FONT SIZE=2><B>ATENÇÃO! </B> Existem atendimentos com pendências. <a href='callcenter_pendencias.php' target='_new'>Clique aqui</a></FONT></td>
</tr>
</table>
<? } ?>

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td><img height="1" width="20" src="/imagens/spacer.gif"></td>
	<td valign="top" align="left">
		<TABLE width=700 border="0" cellpadding="3" cellspacing="3">
		<FORM METHOD=POST name='frm_callcenter' ACTION="<? echo $PHP_SELF; ?>">
		<input type='hidden' name='callcenter' value='<? echo $callcenter; ?>'>
			<TR class='menu_top'>
				<TD colspan='2' width='50%'>Atendente</TD>
				<TD colspan='2'>Natureza do chamado</TD>
			</TR>
			<TR class='table_line'>
				<TD colspan='2'><? echo ucfirst($login_login); if (strlen($atendente_nome) > 0) echo " / Atendido por: ".ucfirst($atendente_nome); ?></TD>
				<TD colspan='2'>
					<SELECT NAME="natureza">
						<? if ($login_fabrica <> 6 AND $login_fabrica <> 15) { ?>
						<option value='' SELECTED>Selecione</option>
						<option value='Reclamação'       <? if($natureza == 'Reclamação')       echo ' selected';?>>Reclamação</option>
						<option value='Dúvidas'          <? if($natureza == 'Dúvidas')          echo ' selected';?>>Dúvidas</option>
						<option value='Insatisfação'     <? if($natureza == 'Insatisfação')     echo ' selected';?>>Insatisfação</option>
						<option value='Troca de produto' <? if($natureza == 'Troca de produto') echo ' selected';?>>Troca de produto</option>
						<option value='Outras áreas'     <? if($natureza == 'Outras áreas')     echo ' selected';?>>Outras áreas</option>
						<option value='Engano'           <? if($natureza == 'Engano')           echo ' selected';?>>Engano</option>
						<option value='Email'            <? if($natureza == 'Email')            echo ' selected';?>>Email</option>
						<? }else{ ?>
						<option value='' SELECTED>Selecione</option>
						<!-- No caso da Latina quando escolhido "Reclamação" será necessário entrar com o produto-->
						<option value='Reclamação'       <? if($natureza == 'Reclamação')       echo ' selected';?>>Reclamação</option>
						<option value='Informação'       <? if($natureza == 'Informação')       echo ' selected';?>>Informação</option>
						<?if($login_fabrica <> 6){ //chamado 1237?>
							<option value='Insatisfação'     <? if($natureza == 'Insatisfação')     echo ' selected';?>>Insatisfação</option>
							<option value='Troca de produto' <? if($natureza == 'Troca de produto') echo ' selected';?>>Troca de produto</option>
						<?}?>
						<option value='Engano'           <? if($natureza == 'Engano')           echo ' selected';?>>Engano</option>
						<option value='Outras áreas'     <? if($natureza == 'Outras áreas')     echo ' selected';?>>Outras áreas</option>
						<option value='Email'            <? if($natureza == 'Email')            echo ' selected';?>>Email</option>
						<? } ?>
						<? if($login_fabrica == 6){?>
							<option value='Ocorrência'       <? if($natureza == 'Ocorrência')       echo ' selected';?>>Ocorrência</option>
						<? } ?>
					</SELECT>
				</TD>
			</TR>
			<TR class='menu_top'>
				<TD colspan='2'>Nome Cliente</TD>
				<TD colspan='2'>CPF/CNPJ Cliente</TD>
			</TR>
			<TR class='table_line'>
				<? if($login_fabrica == 15){ ?>
					<TD colspan='2'><INPUT TYPE="text" NAME="consumidor_nome" size="30" value="<? echo $consumidor_nome; ?>">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, "nome")' style='cursor: pointer'></TD>
					<TD colspan='2'><INPUT TYPE="text" NAME="consumidor_cpf" size="15" value="<? echo $consumidor_cpf; ?>" >&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf,"cpf")'  style='cursor: pointer'></TD>
				<?}else{?>
					<TD colspan='2'><INPUT TYPE="text" NAME="consumidor_nome" size="30" value="<? echo $consumidor_nome; ?>" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_consumidor (document.frm_callcenter.consumidor_nome, 'nome')" <? } ?>>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_callcenter.consumidor_nome, "nome")' style='cursor: pointer'></TD>
					<TD colspan='2'><INPUT TYPE="text" NAME="consumidor_cpf" size="15" value="<? echo $consumidor_cpf; ?>" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_consumidor (document.frm_callcenter.consumidor_cpf,'cpf')" <? } ?>>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_callcenter.consumidor_cpf,"cpf")'  style='cursor: pointer'></TD>
					
				<?}?>
					<input type='hidden' name = 'cliente'  value="<? echo $cliente; ?>">
					<input type='hidden' name = 'consumidor_rg'>
					<input type='hidden' name = 'consumidor_fone'>
					<input type='hidden' name = 'consumidor_endereco'>
					<input type='hidden' name = 'consumidor_bairro'>
					<input type='hidden' name = 'consumidor_numero'>
					<input type='hidden' name = 'consumidor_complemento'>
					<input type='hidden' name = 'consumidor_cep'>
			</TR>
<? if ($login_fabrica == 6) { ?>
						<tr ><TD colspan='4' class='menu_top'> <FONT SIZE="1"> Digite o e-mail caso o consumidor deseje receber informações sobre lançamentos TEC TOY </FONT></td>
						</tr>
						<tr>
						<TD colspan='4' class='table_line'><INPUT TYPE="text" NAME="email" size="40" value="<? echo $email; ?>">
					<?}?>
			<TR class='menu_top'>
				<TD colspan='2'>Cidade</TD>
				<TD colspan='2'>Estado</TD>
			</TR>
			<TR class='table_line'>
				<TD colspan='2'><INPUT TYPE="text" NAME="consumidor_cidade" size="30" value="<? echo $consumidor_cidade; ?>"></TD>
				<TD colspan='2'><select size='1' name='consumidor_estado'>
				<option selected value=''>UF</option>
				<? $ArrayEstados = array('AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO');
					for ($i=0; $i<=26; $i++)
					{
					echo"<option value='".$ArrayEstados[$i]."'";
					if ($consumidor_estado == $ArrayEstados[$i]) echo " selected";
					echo ">".$ArrayEstados[$i]."</option>\n";
					}
					?>
				</select></TD>
			</TR>
			<TR class='menu_top'>
				<TD colspan=4>Nome Posto</TD>
			</TR>
			<TR class='table_line'>
				<TD colspan='2'><INPUT TYPE="text" NAME="posto_nome" size="30" value="<? echo $posto_nome; ?>" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto(document.frm_callcenter.posto_codigo,document.frm_callcenter.posto_nome,'nome')" <? } ?>>&nbsp;
<!-- 				<img src='imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto_regiao (document.frm_callcenter.posto_nome,document.frm_callcenter.consumidor_cidade,document.frm_callcenter.consumidor_estado)" style="cursor:pointer;"> -->
					<img src='imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto(document.frm_callcenter.posto_codigo,document.frm_callcenter.posto_nome,'nome')" style="cursor:pointer;">
					<input type='hidden' name = 'posto_codigo'  value="<? echo $posto_codigo; ?>">
				</TD>
				<TD colspan='2'>Para localizar um posto pela <b>Cidade</b> e/ou <b>Estado</b>, digite somente os dados acima e clique na <b>"lupa"</b> ao lado.
				</TD>
			</TR>

<?
if($login_fabrica != 15){
	//lista todos os postos qdo nao for latina.
	if (strlen($selecione_posto) > 0){
		echo "<TR class='menu_top'\n>";
		echo "<TD colspan=4>Selecione o posto na lista abaixo</TD>\n";
		echo "</TR>\n";
		echo "<tr class='table_line'>\n";
		echo "<td  colspan='2' align='left' nowrap>\n";

		$y=1;

		for($z=0; $z<pg_numrows($resPosto); $z++){
			$resto = $y % 2;
			$y++;
			
			$codigo   = trim(pg_result($resPosto,$z,codigo_posto));
			$nome     = trim(pg_result($resPosto,$z,nome));
			$fantasia = trim(strtoupper(pg_result($resPosto,$z,nome_fantasia)));
			$cidade   = trim(strtoupper(pg_result($resPosto,$z,cidade)));
			$estado   = trim(strtoupper(pg_result($resPosto,$z,estado)));
			
			if (strlen($fantasia) == 0) $fantasia = "NOME FANTASIA NÃO CADASTRADO";
			
			echo "$nome<br><INPUT TYPE='radio' NAME='posto' value='$codigo'>&nbsp;<font face='arial' size='-2' color='#FF0000'>$fantasia</font><br><font face='arial' size='-2' color='#330066'><b>$cidade / $estado</b></font>";
			if($resto == 0){
				echo "					</td>\n</tr>\n";
				echo "					<tr class='table_line'>\n<td align='left'>\n";
			}else{
				echo "					</td>\n";
				echo "					<td align='left' colspan=2>\n";
			}
		}
	}
}
?>

			<TR class='menu_top'>
				<TD colspan='2'>Número da OS</TD>
				<TD colspan='2'>Data abertura</TD>
			</TR>
			<TR class='table_line'>
				<TD colspan='2'><INPUT TYPE="text" NAME="sua_os" size="10" value="<? echo $sua_os; ?>" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_os (document.frm_callcenter.sua_os)" <? } ?>>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_os (document.frm_callcenter.sua_os)' style='cursor: pointer'></TD>
				<TD colspan='2'><INPUT TYPE="text" NAME="data_abertura" size="10" maxlength='10'  value="<? echo $data_abertura; ?>">&nbsp;<IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('dataPesquisa')" style="cursor:pointer" alt="Clique aqui para abrir o calendário"></TD>
			</TR>
			<TR class='menu_top'>
				<TD>Descrição Produto</TD>
				<TD>Série</TD>
				<TD>Nota fiscal</TD>
				<TD>Data da compra</TD>
			</TR>
			<TR class='table_line'>
				<TD nowrap><INPUT TYPE="text" NAME="produto_nome" size="30" value="<? echo $produto_nome; ?>" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'descricao')" <? } ?>>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'descricao')"></TD>
				<TD><INPUT TYPE="text" NAME="produto_serie" size="15" value="<? echo $produto_serie; ?>"></TD>
					<input type='hidden' name='produto_referencia' value="<? echo $produto_referencia; ?>">
				<TD><INPUT TYPE="text" NAME="nota_fiscal" size="10" maxlength='10' value="<? echo $nota_fiscal; ?>"></TD>
				<TD><INPUT TYPE="text" NAME="data_nf" size="10" maxlength='10' value="<? echo $data_nf; ?>"> (dd/mm/aaaa)</TD>
			</TR>
<?
	if (strlen($selecione_produto) > 0){
		echo "<TR class='menu_top'\n>";
		echo "<TD colspan=3>Selecione o produto do cliente na lista abaixo</TD>\n";
		echo "</TR>\n";
		echo "<tr class='table_line'>\n";
		echo "<td align='left'>\n";

		$y=1;

		for($z=0; $z<pg_numrows($resProduto); $z++){
			$resto = $y % 2;
			$y++;

			echo "<INPUT TYPE='radio' NAME='produto' value='".pg_result($resProduto,$z,referencia)."'>".pg_result($resProduto,$z,referencia)." - ".pg_result($resProduto,$z,descricao);
			if($resto == 0){
				echo "					</td>\n</tr>\n";
				echo "					<tr class='table_line'>\n<td align='left'>\n";
			}else{
				echo "					</td>\n";
				echo "					<td align='left' colspan=2>\n";
			}
		}
	}
?>
			<TR class='menu_top'>
				<TD colspan='4'>Revenda</TD>
			</TR>
			<TR class='table_line'>
				<TD colspan='4'><INPUT TYPE="text" NAME="revenda_nome" size="50" value="<? echo $revenda_nome; ?>"></TD>
			</TR>
			<TR>
				<TD colspan='4' align='center'>
					<input type="hidden" name="btn_acao" value="">
					<img src='imagens/btn_continuar.gif' style="cursor:pointer" onclick="javascript: document.frm_callcenter.btn_acao.value='continuar'; document.frm_callcenter.submit()" ALT="Continuar" border='0'>
				</TD>
			</TR>
		</FORM>
		</TABLE>
	</td>
	<td><img height="1" width="16" src="/imagens/spacer.gif"></td>
</tr>
</table>

<p>

<? include "rodape.php";?>
