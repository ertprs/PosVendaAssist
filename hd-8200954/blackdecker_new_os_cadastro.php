<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

include 'token_cookie.php';
$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);

if (strlen($cookie_login["cook_posto"]) > 0) {
	$cook_posto = trim($cookie_login["cook_posto"]);
}
/*
# comentario Ricardo

$res_posto = @pg_exec ($con,"SELECT oid,* FROM tbl_posto WHERE oid = $cook_posto");
if (@pg_numrows ($res_posto) == 0 OR strlen (trim (pg_errormessage($con))) > 0 ) {
	header ("Location: index.php");
	exit;
}
$posto              = trim(pg_result ($res_posto,0,posto));
$tipo_posto         = trim(pg_result ($res_posto,0,tipo_posto));
$posto_codigo       = trim(pg_result ($res_posto,0,codigo));
$pede_peca_garantia = trim(pg_result ($res_posto,0,pede_peca_garantia));
$posto_nome         = trim(pg_result ($res_posto,0,nome));
*/
if ($posto_codigo == "57136") {
	header ("Location: cad_pedido.php");
	exit;
}
/*
# comentario Ricardo

$res_distrib = @pg_exec ($con,"SELECT * FROM tbl_posto WHERE tbl_posto.tipo_posto = 4 AND tbl_posto.posto = $posto");
if (@pg_numrows ($res_distrib) > 0) {
	$visual_black = "os-distr";
	$ident = "distr";
}else{
	$visual_black = "os-posto";
	$ident = "posto";
}
*/
if (strlen($_GET["os"]) > 0) {
	$os = trim($_GET["os"]);
}

$qtde_linhas_defeitos = 10;

###### PAGAMENTO À VISTA ######
/*
# comentario Ricardo

$sql = "SELECT sigla FROM tbl_tabela_politica WHERE condpgto = 5 AND tipo_posto = $tipo_posto";
$res = @pg_exec ($con,$sql);

if (strlen (pg_errormessage ($con)) == 0) {
	if (pg_numrows ($res) > 0) $posto_tabela = "'" . pg_result ($res,0,0) . "'" ;
}
if (strlen ($posto_tabela) == 0) $posto_tabela = "null";
*/

$posto_tabela = "'BASE'";

### QUANDO CLICAR EM CARREGAR DEFEITOS
$btn_acao = strtolower($_POST['btn_acao']);

if ($btn_acao == 'prosseguir') {
	$os                = trim($_POST["os"]);
	$sua_os            = trim($_POST["sua_os"]);
	$data_abertura     = trim($_POST["data_abertura"]);
	$data_fechamento   = trim($_POST["data_fechamento"]);
	$produto           = trim($_POST["produto"]);
	$voltagem          = trim($_POST["voltagem"]);
	$versao            = trim($_POST["versao"]);
	$numero_serie      = trim($_POST["numero_serie"]);
	$consumidor_nome   = trim($_POST["consumidor_nome"]);
	$consumidor_cidade = trim($_POST["consumidor_cidade"]);
	$consumidor_estado = trim($_POST["consumidor_estado"]);
	$consumidor_fone   = trim($_POST["consumidor_fone"]);
	$loja_nome         = trim($_POST["loja_nome"]);
	$loja_cnpj         = trim($_POST["loja_cnpj"]);
	$nota_fiscal       = trim($_POST["nota_fiscal"]);
	$data_nf           = trim($_POST["data_nf"]);
	$satisfacao        = trim($_POST["satisfacao"]);
	$satisfacao_obs    = trim($_POST["satisfacao_obs"]);
	
	if(strlen($satisfacao)> 0){
		$satisfacao='t';
	}
	$prossegue = "sim";
	
	if (strlen($produto) == 0) {
		$xxproduto = "null";
	}
	
	if (strlen($voltagem) == 0) {
		if ($xxproduto <> "null") {
			$erro = "Favor informar a voltagem do produto.";
		}
	}
	
	if (strlen($erro) == 0) {
		$sql = "SELECT  tbl_produto.produto   ,
						tbl_produto.referencia
				FROM    tbl_produto
				WHERE   tbl_produto.referencia ilike '$produto%'
				AND     tbl_produto.voltagem = '$voltagem';";
		$res = @pg_exec ($con,$sql);
		
		if (@pg_numrows($res) == 0) {
			$erro = "Produto informado não encontrado.";
		}else{
			$xxx_produto = "'". trim(pg_result($res,0,produto)) ."'";
			$produto     = trim(pg_result($res,0,referencia));
		}
		
	}
	
	if (strlen($erro) == 0) {
		if (strlen($versao) == 0) {
			$sql = "SELECT  distinct
							tbl_vista_explodida.tipo
					FROM    tbl_vista_explodida
					WHERE   tbl_vista_explodida.equipamento = $xxx_produto;";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows($res) > 0) {
				$erro = "Este equipamento necessita informar a versão.<br> As versões são: ";
				
				$aux = 0;
				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
					$aux++;
					$erro .= trim(pg_result($res,$i,tipo));
					
					if ($aux < pg_numrows($res)) {
						$erro .= " / ";
					}
				}
			}
		}else{
			$sql = "SELECT  distinct
							tbl_vista_explodida.tipo
					FROM    tbl_vista_explodida
					WHERE   tbl_vista_explodida.equipamento = $xxx_produto
					AND     tbl_vista_explodida.tipo ilike '%$versao%';";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows($res) == 0) {
				#$erro = "Versão informada não encontrada";
			}
		}
	}
	
	if (strlen($erro) > 0) {
		$prossegue = "nao";
		
		$msg  = "<b>Foi detectado o seguinte erro: </b><br>";
		$msg .= $erro;
	}
}


### APOS CLICAR NO BOTÃO GRAVAR
if ($btn_acao == 'finalizar' and strlen($erro) == 0) {
	### CAMPOS DO FORMULÁRIO
	$os                = trim($_POST["os"]);
	$sua_os            = trim($_POST["sua_os"]);
	$data_abertura     = trim($_POST["data_abertura"]);
	$data_fechamento   = trim($_POST["data_fechamento"]);
	$produto           = trim($_POST["produto"]);
	$voltagem          = trim($_POST["voltagem"]);
	$versao            = trim($_POST["versao"]);
	$numero_serie      = trim($_POST["numero_serie"]);
	$consumidor_nome   = trim($_POST["consumidor_nome"]);
	$consumidor_cidade = trim($_POST["consumidor_cidade"]);
	$consumidor_estado = trim($_POST["consumidor_estado"]);
	$consumidor_fone   = trim($_POST["consumidor_fone"]);
	$loja_nome         = trim($_POST["loja_nome"]);
	$loja_cnpj         = trim($_POST["loja_cnpj"]);
	$nota_fiscal       = trim($_POST["nota_fiscal"]);
	$data_nf           = trim($_POST["data_nf"]);
	$satisfacao        = trim($_POST["satisfacao"]);
	$satisfacao_obs    = trim($_POST["satisfacao_obs"]);
	$qtde_consequencia = trim($_POST["qtde_consequencia"]);
	
	### GERAÇÃO DE CAMPOS AUXILIARES
	if (strlen($sua_os) == 0) {
		$aux_sua_os = "null";
	}else{
		$aux_sua_os = "'". $sua_os ."'";
	}
	
	if (strlen($data_abertura) == 0) {
		$aux_data_abertura = "null";
	}else{
		$aux_data = $data_abertura;
		$aux_data = str_replace ("-","",$aux_data);
		$aux_data = str_replace ("/","",$aux_data);
		$aux_data = str_replace (".","",$aux_data);
		$aux_data = str_replace (" ","",$aux_data);
		
		if (strlen ($aux_data) == 6) $aux_data = substr ($aux_data,0,4) . '20' . substr ($aux_data,4,2);
		
		$aux_data = substr ($aux_data,4,4) . "-" . substr ($aux_data,2,2) . "-" . substr ($aux_data,0,2);
		$aux_data_abertura = "'". $aux_data ."'";
	}
	
	if (strlen($data_fechamento) == 0) {
		$aux_data_fechamento = "null";
	}else{
		$aux_data = $data_fechamento;
		$aux_data = str_replace ("-","",$aux_data);
		$aux_data = str_replace ("/","",$aux_data);
		$aux_data = str_replace (".","",$aux_data);
		$aux_data = str_replace (" ","",$aux_data);
		
		if (strlen ($aux_data) == 6) $aux_data = substr ($aux_data,0,4) . '20' . substr ($aux_data,4,2);
		
		$aux_data = substr ($aux_data,4,4) . "-" . substr ($aux_data,2,2) . "-" . substr ($aux_data,0,2);
		$aux_data_fechamento = "'". $aux_data ."'";
	}
	
	if (strlen($produto) == 0) {
		$aux_produto = "null";
	}else{
		$aux_produto = "'". $produto ."'";
	}
	
	if (strlen($voltagem) == 0) {
		$aux_voltagem = "null";
	}else{
		$aux_voltagem = "'". $voltagem ."'";
	}
	
	if ($aux_produto <> "null" AND $aux_voltagem <> "null") {
		$sql = "SELECT  tbl_produto.produto   ,
						tbl_produto.referencia
				FROM    tbl_produto
				WHERE   tbl_produto.referencia = $aux_produto
				AND     tbl_produto.voltagem   = $aux_voltagem;";
		$res = @pg_exec ($con,$sql);
		
		if (@pg_numrows($res) == 0) {
			$aux_produto = "null";
		}else{
			$aux_produto = "'". trim(pg_result($res,0,produto)) ."'";
		}
	}
	
	if (strlen($numero_serie) == 0) {
		$erro = "É necessário informar o número de série.";
	}else{
		$aux_numero_serie = str_replace ("Í","I",$numero_serie);
		$aux_numero_serie = str_replace ("í","i",$aux_numero_serie);
		$aux_numero_serie = strtoupper($aux_numero_serie);
		
		if (strlen($aux_numero_serie) < 3 and $certo == 0) {
			$erro = "Número de série incorreto: $aux_numero_serie.";
		}else{
			$certo = 0;
		}
		
		if (strlen($erro) == 0) {
			if (strlen($aux_numero_serie) > 9) {
				$erro = "Número de série incorreto: $aux_numero_serie.";
			}else{
				$certo = 0;
			}
		}
		
		if (strlen($erro) == 0) {
			if ($aux_numero_serie <> "ILEGIVEL" and strlen($certo) == 0) {
				$erro = "Número de série incorreto: $aux_numero_serie.";
			}
		}
		
		if (strlen($erro) == 0) {
			$aux_numero_serie = "'". $aux_numero_serie ."'";
		}
	}
	
	if (strlen($consumidor_nome) == 0) {
		$aux_consumidor_nome = "null";
	}else{
		$aux_consumidor_nome = "'". $consumidor_nome ."'";
	}
	
	if (strlen($consumidor_cidade) == 0) {
		$aux_consumidor_cidade = "''";
	}else{
		$aux_consumidor_cidade = "'". $consumidor_cidade ."'";
	}
	
	if (strlen($consumidor_estado) == 0) {
		$aux_consumidor_estado = "''";
	}else{
		$aux_consumidor_estado = "'". $consumidor_estado ."'";
	}
	
	if (strlen($consumidor_fone) == 0) {
		$erro = "É necessário informar o telefone do cliente.";
	}else{
		$aux_consumidor_fone = "'". $consumidor_fone ."'";
	}
	
	if (strlen($loja_nome) == 0) {
		$aux_loja_nome = "null";
	}else{
		$aux_loja_nome = "'". $loja_nome ."'";
	}
	
	if (strlen($loja_cnpj) == 0) {
		$aux_loja_cnpj = "null";
	}else{
		$aux_cnpj = $loja_cnpj;
		$aux_cnpj = str_replace (".","",$aux_cnpj);
		$aux_cnpj = str_replace ("/","",$aux_cnpj);
		$aux_cnpj = str_replace ("-","",$aux_cnpj);
		
		$aux_loja_cnpj = "'". $aux_cnpj ."'";
	}
	
	if (strlen($nota_fiscal) == 0) {
		$aux_nota_fiscal = "null";
	}else{
		$aux_nota_fiscal = "'". $nota_fiscal ."'";
	}
	
	if (strlen($data_nf) == 0) {
		$aux_data_nf = "null";
	}else{
		$aux_data = $data_nf;
		$aux_data = str_replace ("-","",$aux_data);
		$aux_data = str_replace ("/","",$aux_data);
		$aux_data = str_replace (".","",$aux_data);
		$aux_data = str_replace (" ","",$aux_data);
		
		if (strlen ($aux_data) == 6) $aux_data = substr ($aux_data,0,4) . '20' . substr ($aux_data,4,2);
		
		$aux_data = substr ($aux_data,4,4) . "-" . substr ($aux_data,2,2) . "-" . substr ($aux_data,0,2);
		$aux_data_nf = "'". $aux_data ."'";
	}
	
	if (strlen($satisfacao_obs) == 0) {
		$aux_satisfacao_obs = "null";
		$aux_satisfacao     = "'f'";
	}else{
		$aux_satisfacao_obs = "'". $satisfacao_obs ."'";
		$aux_satisfacao     = "'t'";
	}
	
	if (strlen($versao) == 0) {
		if ($aux_produto <> "null") {
			$sql = "SELECT  distinct
							tbl_vista_explodida.tipo
					FROM    tbl_vista_explodida
					WHERE   tbl_vista_explodida.equipamento = $aux_produto;";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows($res) == 0) {
				$aux_tipo = "null";
			}else{
				$erro = "Este equipamento necessita informar a versão.<br> As versões são: ";
				
				$aux = 0;
				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
					$aux++;
					$erro .= trim(pg_result($res,$i,tipo));
					
					if ($aux < pg_numrows($res)) {
						$erro .= " / ";
					}
				}
			}
		}
	}else{
		$aux_tipo = "'". $versao ."'";
	}
	
	$resx = pg_exec ($con,"BEGIN TRANSACTION");
	
	if (strlen($erro) == 0) {
		### DEFEITOS DA LISTA
		$aux = 0;
		for ($i = 0 ; $i < $qtde_consequencia ; $i++) {
			$aux++;
			
			$defeito_lista = $_POST["defeito_lista". $aux];
			
			### VERIFICA SE EXISTEM DEFEITOS MARCADOS
			if (strlen($defeito_lista) > 0) {
				$possui_defeito = "sim";
				
				### VERIFICA SE TEM O DEFEITO TROCA
				$sql = "SELECT tbl_consequencia.descricao
						FROM   tbl_consequencia
						WHERE  tbl_consequencia.consequencia = $defeito_lista
						AND    tbl_consequencia.descricao ilike '%troca%';";
				$resx = pg_exec ($con,$sql);
				
				if (pg_numrows($resx) == 0) {
					$troca .= "nao";
				}else{
					$troca .= "sim";
				}
				
				### VERIFICA SE TEM O DEFEITO TROCA FATURADA
				$sql = "SELECT tbl_consequencia.descricao
						FROM   tbl_consequencia
						WHERE  tbl_consequencia.consequencia = $defeito_lista
						AND    tbl_consequencia.descricao ilike '%faturada%';";
				$resx = pg_exec ($con,$sql);
				
				if (pg_numrows($resx) == 0) {
					$troca_faturada .= "nao";
				}else{
					$troca_faturada .= "sim";
				}
			}
		}
	}
	
	if (strlen($erro) == 0 and strlen($os) > 0) {
		$sql = "SELECT tbl_new_os_defeito.consequencia
				FROM   tbl_new_os_defeito
				WHERE  tbl_new_os_defeito.new_os = $os;";
		$resx = pg_exec ($con,$sql);
		
		for ($ii = 0 ; $ii < pg_numrows($resx) ; $ii++) {
			$xxx_consequencia = trim(pg_result($resx,$ii,consequencia));
			
			### EXCLUI OS DEFEITOS
			$sql = "DELETE FROM tbl_new_os_defeito
					WHERE  tbl_new_os.new_os = tbl_new_os_defeito.new_os
					AND    tbl_new_os_defeito.new_os       = $os
					AND    tbl_new_os_defeito.consequencia = $xxx_consequencia;";
			$resz = @pg_exec ($con,$sql);
			$log_erro = $sql;
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
				
				$matriz1 = $matriz1 . ";" . $aux . ";";
				break;
			}
		}
	}
	
	if (strlen($erro) == 0 and strlen($os) > 0) {
		### EXCLUI AS PEÇAS
		$sql = "DELETE FROM tbl_new_os_item
				WHERE  tbl_new_os.new_os = tbl_new_os_item.new_os
				AND    tbl_new_os_item.new_os = $os
				AND    tbl_new_os_item.defeito_descricao isnull;";
		$resx = @pg_exec ($con,$sql);
		$log_erro = $sql;
		
		if (strlen ( pg_errormessage ($con) ) > 0) {
			$erro = pg_errormessage ($con) ;
		}
	}
	
	if (strlen($erro) == 0) {
		### DEFEITOS FORA DA LISTA
		$aux = 0;
		for ($a = 0 ; $a < $qtde_linhas_defeitos ; $a++) {
			$aux++;
			
			$peca = $_POST["peca". $aux];
			
			### VERIFICA SE POSSUI DEFEITOS FORA DA LISTA
			if (strlen($peca) > 0) {
				$aprovacao_interna = "'t'";
				$possui_defeito    = "sim";
				$troca            .= "nao";
			}
		}
	}
	
	if (strlen($erro) == 0 and strlen($os) > 0) {
		### EXCLUI AS PEÇAS
		$sql = "DELETE FROM tbl_new_os_item
				WHERE  tbl_new_os.new_os = tbl_new_os_item.new_os
				AND    tbl_new_os_item.new_os = $os
				AND    tbl_new_os_item.defeito_descricao notnull;";
		$res = @pg_exec ($con,$sql);
		$log_erro = $sql;
		
		if (strlen ( pg_errormessage ($con) ) > 0) {
			$erro = pg_errormessage ($con) ;
		}
	}
	
	if (strlen($erro) == 0) {
		$checa_troca = strpos($troca, "naosim");
		if (strlen($checa_troca) == 0) {
			$checa_troca = strpos($troca, "simnao");
		}
		
		if (strlen($checa_troca) == 0) {
			$checa_troca = strpos($troca, "simsim");
		}
		
		if (strlen($checa_troca) > 0) {
			$erro = "Não é possível informar outros defeitos<br>se foi informado um defeito de troca de produtos ou de peças.";
		}
	}
	
	if (strlen($erro) == 0) {
		if (strlen($possui_defeito) == 0) {
			$erro = "É necessário informar ao menos um defeito.";
		}
		
		$checa_troca_faturada = strpos($troca_faturada, "sim");
		
		if (strlen($checa_troca_faturada) > 0) {
			$aux_troca_faturada = "'t'";
		}else{
			$aux_troca_faturada = "'f'";
		}
	}
	
	if (strlen($erro) == 0) {
		## INCLUSÃO DE OS
		if (strlen($os) == 0) {
			$sql = "INSERT INTO tbl_new_os (
						posto               ,
						sua_os              ,
						data_digitacao      ,
						data_nf             ,
						data_abertura       ,
						data_fechamento     ,
						produto             ,
						tipo                ,
						numero_serie        ,
						consumidor_nome     ,
						consumidor_municipio,
						consumidor_fone     ,
						loja_nome           ,
						loja_cnpj           ,
						nota_fiscal         ,
						tabela              ,
						satisfacao          ,
						satisfacao_obs      ,
						troca_faturada      ,
						aprovacao_interna
					) VALUES (
						$posto                                                        ,
						$aux_sua_os                                                   ,
						current_timestamp                                             ,
						$aux_data_nf                                                  ,
						$aux_data_abertura                                            ,
						$aux_data_fechamento                                          ,
						$aux_produto                                                  ,
						$aux_tipo                                                     ,
						$aux_numero_serie                                             ,
						$aux_consumidor_nome                                          ,
						fn_qual_cidade($aux_consumidor_cidade, $aux_consumidor_estado),
						$aux_consumidor_fone                                          ,
						$aux_loja_nome                                                ,
						$aux_loja_cnpj                                                ,
						$aux_nota_fiscal                                              ,
						$posto_tabela                                                 ,
						$aux_satisfacao                                               ,
						$aux_satisfacao_obs                                           ,
						$aux_troca_faturada                                           ,
						'f'
					);";
		}else{
			## ALTERAÇÃO DE OS
			$sql = "UPDATE tbl_new_os SET
						sua_os               = $aux_sua_os                                                   ,
						data_digitacao       = current_timestamp                                             ,
						data_nf              = $aux_data_nf                                                  ,
						data_abertura        = $aux_data_abertura                                            ,
						data_fechamento      = $aux_data_fechamento                                          ,
						produto              = $aux_produto                                                  ,
						tipo                 = $aux_tipo                                                     ,
						numero_serie         = $aux_numero_serie                                             ,
						consumidor_nome      = $aux_consumidor_nome                                          ,
						consumidor_municipio = fn_qual_cidade($aux_consumidor_cidade, $aux_consumidor_estado),
						consumidor_fone      = $aux_consumidor_fone                                          ,
						loja_nome            = $aux_loja_nome                                                ,
						loja_cnpj            = $aux_loja_cnpj                                                ,
						nota_fiscal          = $aux_nota_fiscal                                              ,
						tabela               = $posto_tabela                                                 ,
						satisfacao           = $aux_satisfacao                                               ,
						satisfacao_obs       = $aux_satisfacao_obs                                           ,
						troca_faturada       = $aux_troca_faturada                                           ,
						aprovacao_interna    = 'f'
					WHERE tbl_new_os.new_os  = $os
					AND   tbl_new_os.posto   = $posto;";
		}
		## EXECUTA INCLUSÃO OU ALTERAÇÃO DE OS

		$res0 = @pg_exec ($con,$sql);
		$log_erro = $sqll;
		
		if (strlen ( pg_errormessage ($con) ) > 0) {
			$erro = pg_errormessage ($con) ;
		}
	}
	
	if (strlen($erro) == 0 and strlen($os) == 0) {
		## PEGA SEQUÊNCIA DA TABELA DE OS
		$res1 = pg_exec ($con,"SELECT currval ('tbl_new_os_seq')");
		$os   = pg_result ($res1,0,0);
		
		if (strlen ( pg_errormessage ($con) ) > 0) {
			$erro = pg_errormessage ($con) ;
		}
	}
	
	if (strlen($erro) == 0) {
		$aux = 0;
		for ($i = 0 ; $i < $qtde_consequencia ; $i++) {
			$aux++;
			
			$defeito_lista = $_POST["defeito_lista". $aux];
			
			if (strlen($defeito_lista) > 0) {
				$sql = "DELETE FROM tbl_new_os_defeito
						WHERE  tbl_new_os.new_os = tbl_new_os_defeito.new_os
						AND    tbl_new_os_defeito.new_os       = $os
						AND    tbl_new_os_defeito.consequencia = $defeito_lista
						AND    tbl_new_os.posto                = $posto;";
				$resz = pg_exec ($con,$sql);
				$log_erro = $sql;
				
				if (strlen ( pg_errormessage ($con) ) > 0) {
					$erro = pg_errormessage ($con) ;
					
					$matriz0 = $matriz0 . ";" . $aux . ";";
					break;
				}
				
				if (strlen($erro) == 0) {
					$sql = "DELETE FROM tbl_new_os_item
							WHERE  tbl_new_os_item.new_os = tbl_new_os_defeito.new_os
							AND    tbl_new_os_item.new_os          = $os
							AND    tbl_new_os_defeito.consequencia = $defeito_lista
							AND    tbl_new_os_item.defeito_descricao isnull;";
					$resz = pg_exec($con,$sql);
					$log_erro = $sql;
					
					if (strlen ( pg_errormessage ($con) ) > 0) {
						$erro = pg_errormessage ($con) ;
						
						$matriz0 = $matriz0 . ";" . $aux . ";";
						break;
					}
				}
			}
			
			if (strlen($erro) == 0) {
				if (strlen($defeito_lista) > 0) {
					$sql = "INSERT INTO tbl_new_os_defeito (
								new_os       ,
								consequencia
							) VALUES (
								$os           ,
								$defeito_lista
							);";
					$res2 = @pg_exec ($con,$sql);
					$log_erro = $sql;
					
					if (strlen ( pg_errormessage ($con) ) > 0) {
						$erro = pg_errormessage ($con) ;
						
						$matriz0 = $matriz0 . ";" . $aux . ";";
						break;
					}
				}
			}
		}
	}
	
	if (strlen($erro) == 0) {
		$sql = "DELETE FROM tbl_new_os_item
				WHERE  tbl_new_os.new_os = tbl_new_os_item.new_os
				AND    tbl_new_os_item.new_os = $os
				AND    tbl_new_os_item.defeito_descricao notnull;";
		$resz = pg_exec($con,$sql);
		$log_erro = $sql;
		
		if (strlen ( pg_errormessage ($con) ) > 0) {
			$erro = pg_errormessage ($con) ;
		}
	}
	
	if (strlen($erro) == 0) {
		$aux = 0;
		for ($a = 0 ; $a < $qtde_linhas_defeitos ; $a++) {
			$aux++;
			
			$peca = $_POST["peca".$aux];
			if (strlen($peca) == 0) {
				$peca = "null";
			}
			
			$qtde = $_POST["qtde".$aux];
			if (strlen($qtde) == 0) {
				if ($peca <> "null") {
					$erro = "Favor informar a qtde da peça";
					
					$matriz1 = $matriz1 . ";" . $aux . ";";
					break;
				}
			}
			
			$defeito = $_POST["defeito".$aux];
			if (strlen($defeito) == 0) {
				if ($peca <> "null") {
					$erro = "Favor informar o defeito apresentado";
					
					$matriz1 = $matriz1 . ";" . $aux . ";";
					break;
				}
			}else{
				$aux_defeito = "'". $defeito ."'";
			}
			
			if (strlen($erro) == 0) {
				if ($peca <> "null") {
					$sql = "INSERT INTO tbl_new_os_item (
								new_os,
								qtde  ,
								peca  ,
								defeito_descricao
							) VALUES (
								$os  ,
								$qtde,
								(SELECT peca FROM tbl_peca WHERE trim(tbl_peca.referencia) = upper(trim('$peca'))),
								$aux_defeito
							);";
					$res2 = @pg_exec ($con,$sql);
					$log_erro = $sql;
					
					if (strlen ( pg_errormessage ($con) ) > 0) {
						$erro = pg_errormessage ($con) ;
						
						$matriz1 = $matriz1 . ";" . $aux . ";";
						break;
					}
				}
			}
		}
	}
	
	if (strlen($erro) == 0) {
		$resx = pg_exec ($con,"COMMIT TRANSACTION");
		if (strlen($aprovacao_interna) > 0 AND $aprovacao_interna == "'t'") {
			header ("Location: new_os_finaliza.php?os=$os&aprovacao_interna=t");
			exit;
		}else{
			header ("Location: new_os_finaliza.php?os=$os&aprovacao_interna=f");
			exit;
		}
	}else{
		### CASO EXISTA ERROS RECARREGA FORM
		$os                     = trim($_POST["os"]);
		$sua_os                 = trim($_POST["sua_os"]);
		$data_abertura          = trim($_POST["data_abertura"]);
		$data_fechamento        = trim($_POST["data_fechamento"]);
		$produto                = trim($_POST["produto"]);
		$voltagem               = trim($_POST["voltagem"]);
		$versao                 = trim($_POST["versao"]);
		$numero_serie           = trim($_POST["numero_serie"]);
		$consumidor_nome        = trim($_POST["consumidor_nome"]);
		$consumidor_cidade      = trim($_POST["consumidor_cidade"]);
		$consumidor_estado      = trim($_POST["consumidor_estado"]);
		$consumidor_fone        = trim($_POST["consumidor_fone"]);
		$loja_nome              = trim($_POST["loja_nome"]);
		$loja_cnpj              = trim($_POST["loja_cnpj"]);
		$nota_fiscal            = trim($_POST["nota_fiscal"]);
		$data_nf                = trim($_POST["data_nf"]);
		$satisfacao             = trim($_POST["satisfacao"]);
		$satisfacao_obs         = trim($_POST["satisfacao_obs"]);
		
		$resx = pg_exec ($con,"ROLLBACK TRANSACTION");
		
		if (strpos ($erro,"Cannot insert a duplicate key into unique index tbl_new_os_unico") > 0)
		$erro = "Ordem de Serviço $sua_os já informada !!! Impossível duplicar !!!";
		
		if (strpos ($erro,"duplicate key violates unique constraint \"tbl_new_os_unico\"") > 0)
		$erro = "Ordem de Serviço $sua_os já informada !!! Impossível duplicar !!!";
		
		if (strpos ($erro,"null value in column \"peca\" violates not-null constraint") > 0)
		$erro = "Peça informada não encontrada !!!";
		
		if (strpos ($erro,"null value in column \"peca\" violates not-null constraint") > 0)
		$erro = "Peça informada não encontrada ou não cadastrada !!!";
		
		$msg  = "<b>Foi detectado o seguinte erro: </b><br>";
		$msg .= $erro;
	}
}


### CARREGA OS
if (strlen($os) > 0 and $btn_acao == 0) {
	$sql = "SELECT    distinct
					  tbl_produto.referencia                            AS produto                ,
					  tbl_produto.voltagem                                                        ,
					  tbl_vista_explodida.tipo                          AS versao                 ,
					  tbl_new_os.sua_os                                                           ,
					  tbl_new_os.numero_serie                                                     ,
					  to_char(tbl_new_os.data_abertura, 'DD/MM/YYYY')   AS data_abertura          ,
					  to_char(tbl_new_os.data_fechamento, 'DD/MM/YYYY') AS data_fechamento        ,
					  tbl_new_os.consumidor_nome                                                  ,
					  tbl_cidade.cidade                                 AS consumidor_cidade      ,
					  tbl_cidade.estado                                 AS consumidor_estado      ,
					  tbl_new_os.consumidor_fone                                                  ,
					  tbl_new_os.loja_nome                                                        ,
					  tbl_new_os.loja_cnpj                                                        ,
					  tbl_new_os.nota_fiscal                                                      ,
					  to_char(tbl_new_os.data_nf, 'DD/MM/YYYY')         AS data_nf                ,
					  tbl_new_os.satisfacao                                                       ,
					  tbl_new_os.satisfacao_obs
			FROM      tbl_new_os
			JOIN      tbl_produto         ON tbl_produto.produto             = tbl_new_os.produto
			LEFT JOIN tbl_vista_explodida ON tbl_vista_explodida.equipamento = tbl_produto.produto
										 AND tbl_vista_explodida.tipo        = tbl_new_os.tipo::char(10)
			JOIN      tbl_cidade          ON tbl_cidade.municipio            = tbl_new_os.consumidor_municipio
			WHERE     tbl_new_os.new_os = $os
			AND       tbl_new_os.posto  = $posto;";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$sua_os                 = trim(pg_result($res,0,sua_os));
		$data_abertura          = trim(pg_result($res,0,data_abertura));
		$data_fechamento        = trim(pg_result($res,0,data_fechamento));
		
		if (strlen($produto) == 0) {
			$produto = trim(pg_result($res,0,produto));
		}
		
		$voltagem               = trim(pg_result($res,0,voltagem));
		$versao                 = trim(pg_result($res,0,versao));
		$numero_serie           = trim(pg_result($res,0,numero_serie));
		$consumidor_nome        = trim(pg_result($res,0,consumidor_nome));
		$consumidor_cidade      = trim(pg_result($res,0,consumidor_cidade));
		$consumidor_estado      = trim(pg_result($res,0,consumidor_estado));
		$consumidor_fone        = trim(pg_result($res,0,consumidor_fone));
		$loja_nome              = trim(pg_result($res,0,loja_nome));
		$loja_cnpj              = trim(pg_result($res,0,loja_cnpj));
		
		if (strlen($loja_cnpj) > 0) {
			$loja_cnpj = substr($loja_cnpj,0,2) .".". substr($loja_cnpj,2,3) .".". substr($loja_cnpj,5,3) ."/". substr($loja_cnpj,8,4) ."-". substr($loja_cnpj,12,2);
		}
		
		$nota_fiscal            = trim(pg_result($res,0,nota_fiscal));
		$data_nf                = trim(pg_result($res,0,data_nf));
		$satisfacao             = trim(pg_result($res,0,satisfacao));
		$satisfacao_obs         = trim(pg_result($res,0,satisfacao_obs));
	}
}



if (strlen($sua_os) == 0) {
	$body_options = "onload=\"javascript: document.frm_os.sua_os.focus()\";";
}

$titulo      = "Telecontrol - Ordens de Serviço";
$cabecalho   = "Ordens de Serviço";
$layout_menu = 'os';

include "cabecalho.php";

if (date("d/m/Y H:m:s") >= "26/01/2004 23:59:59" AND 1 == 2) {
	echo "<br><br>\n";
	
	echo "<table width='700' border='0' cellpadding='2' cellspacing='2' align='center'>\n";
	echo "<tr>\n";
	
	echo "<td width='100%' align='center' class='f_" . $css . "_10'>\n";
	echo "<b> O LANÇAMENTO DE OS´S ESTÁ PARALISADO PARA ACERTOS NOS GRUPOS DE DEFEITO.</b>\n";
	echo "</td>\n";
	
	echo "</tr>\n";
	echo "</table>\n";
	exit;
}
?>

<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}
</style>

<script language="JavaScript">
function fnc_pesquisa_sua_os (sua_os) {
	var url = "";
	if (sua_os != "") {
		url = "nova_pesquisa_sua_os.php?sua_os=" + sua_os;
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
		janela.focus();
	}
}


function fnc_fora_linha_os (nome, seq) {
	var url = "";
	if (nome != "") {
		url = "pesquisa_fora_linha_os.php?nome=" + nome + "&seq=" + seq;
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
		janela.focus();
	}
}


nextfield = "sua_os"; // coloque o nome do primeiro campo do form
netscape = "";
ver = navigator.appVersion; len = ver.length;
for(iln = 0; iln < len; iln++) if (ver.charAt(iln) == "(") break;
netscape = (ver.charAt(iln+1).toUpperCase() != "C");

function keyDown(DnEvents) {
	// ve quando e o netscape ou IE
	k = (netscape) ? DnEvents.which : window.event.keyCode;
	if (k == 13) { // preciona tecla enter
		if (nextfield == 'done') {
			return true; // envia quando termina os campos 
		} else {
			// se existem mais campos vai para o proximo
			eval('document.frm_os.' + nextfield + '.focus()'); 
			return false; 
		}
	}
}

document.onkeydown = keyDown; // work together to analyze keystrokes 
if (netscape) document.captureEvents(Event.KEYDOWN|Event.KEYUP); 


function formata_cnpj(cnpj, form){
	var mycnpj = '';
	mycnpj = mycnpj + cnpj;
	myrecord = "loja_cnpj";
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

function mascara_data(data, controle, form){
	var mydata = '';
	mydata = mydata + data;
	myrecord = "data" + controle;
	myform = form;
	k = (netscape) ? DnEvents.which : window.event.keyCode;
	
	if (mydata.length == 2 && k != 8){
		mydata = mydata + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mydata;
	}
	
	if (mydata.length == 5 && k != 8){
		mydata = mydata + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mydata;
	}
	
	if (mydata.length == 10){
		verifica_data();
	}
}

function verifica_data () {
	dia = (window.document.forms["" + myform + ""].elements[myrecord].value.substring(0,2));
	mes = (window.document.forms["" + myform + ""].elements[myrecord].value.substring(3,5));
	ano = (window.document.forms["" + myform + ""].elements[myrecord].value.substring(6,10));
	
	situacao = "";
	// verifica o dia valido para cada mes
	if ((dia < 1)||(dia < 1 || dia > 30) && (  mes == 4 || mes == 6 || mes == 9 || mes == 11 ) || dia > 31) {
		situacao = "falsa";
	}
	
	// verifica se o mes e valido
	if (mes < 01 || mes > 12 ) {
		situacao = "falsa";
	}
	
	// verifica se e ano bissexto
	if (mes == 2 && ( dia < 1 || dia > 29 || ( dia > 28 && (parseInt(ano / 4) != ano / 4)))) {
		situacao = "falsa";
	}
	
	if (window.document.forms["" + myform + ""].elements[myrecord].value == "") {
		situacao = "falsa";
	}
	
	if (situacao == "falsa") {
		alert("Data inválida!");
		window.document.forms["" + myform + ""].elements[myrecord].focus();
	}
}

function mascara_hora(hora, controle){
	var myhora = '';
	myhora = myhora + hora;
	myrecord = "hora" + controle;
	
	if (myhora.length == 2){
		myhora = myhora + ':';
		window.document.forms["" + myform + ""].elements[myrecord].value = myhora;
	}
	
	if (myhora.length == 5){
		verifica_hora();
	}
}

function verifica_hora(){
	hrs = (window.document.forms["" + myform + ""].elements[myrecord].value.substring(0,2));
	min = (window.document.forms["" + myform + ""].elements[myrecord].value.substring(3,5));
	
	situacao = "";
	// verifica data e hora
	if ((hrs < 00 ) || (hrs > 23) || ( min < 00) ||( min > 59)){
		situacao = "falsa";
	}
	
	if (window.document.forms["" + myform + ""].elements[myrecord].value == "") {
		situacao = "falsa";
	}
	
	if (situacao == "falsa") {
		alert("Hora inválida!");
		window.document.forms["" + myform + ""].elements[myrecord].focus();
	}
}

</script>

<?if ($tipo_posto <> 12) { ?>
<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align="center" class="menu_top">
		<b>Para lançar ordem de serviço de revenda, <a href='new_os_cadastro_revenda.php' target="_top">CLIQUE AQUI</a></b>
	</td>
</tr>
</table>
<? } ?>


<form name="frm_os" method="post" action="<?echo $PHP_SELF?>">
<input type='hidden' name='os' value='<? echo $os ?>'>

<?
	if(strlen($msg) > 0){
?>
<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align="center" class="error">
		<?
		echo $msg;
		$data_msg = date ('d-m-Y h:i');
		echo `echo '$data_msg ==> $msg | ==> $log_erro' >> /tmp/blackedecker/novo-lancamento-os.err`;
		?>
	</td>
</tr>
</table>
<?
	}
?>

<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align='center' class="menu_top">
		<b>OS Black & Decker</b>
	</td>
	
	<td bgcolor="<?echo $cor_fraca?>" align='center' class="menu_top">
		<b>Data Abertura</b>
	</td>
	
	<td bgcolor="<?echo $cor_fraca?>" align='center' class="menu_top">
		<b>Data Fechamento</b>
	</td>
	
	<td bgcolor="<?echo $cor_fraca?>" align='left' class="menu_top">
		<b>Produto</b>
	</td>
	
	<td bgcolor="<?echo $cor_fraca?>" align='left' class="menu_top">
		<b>Voltagem</b>
	</td>
	
	<td bgcolor="<?echo $cor_fraca?>" align='center' class="menu_top">
		<b>Versão (Type)</b>
	</td>
	
	<td bgcolor="<?echo $cor_fraca?>" align='center' class="menu_top">
		<b>N. Série</b>
	</td>
</tr>
<tr>
	<td align='center'>
		<input type="text" name="sua_os" size = "20" maxlength="20" value="<? echo $sua_os ?>" class="textbox" style="width:80px" onFocus="nextfield ='data_abertura';" onblur="fnc_pesquisa_sua_os(this.value, document.frm_os.os.value)">
	</td>
	
	<td align='center'>
		<input type="text" name="data_abertura" size = "10" maxlength="10" value="<? echo $data_abertura ?>" class="textbox" style="width:80px" OnKeyUp="mascara_data(this.value, '_abertura', 'frm_os')" onFocus="nextfield ='data_fechamento';">
	</td>
	
	<td align='center'>
		<input type="text" name="data_fechamento" size = "10" maxlength="10" value="<? echo $data_fechamento ?>" class="textbox" style="width:80px" OnKeyUp="mascara_data(this.value, '_fechamento', 'frm_os')" onFocus="nextfield ='produto';">
	</td>
	
	<td align='center'>
		<input type="text" name="produto" size="20" maxlength="20" value="<? echo $produto ?>" class="textbox" style="width:80px" onFocus="nextfield ='voltagem';">
	</td>
	
	<td align='center'>
		<input type="text" name="voltagem" size="10" maxlength="3" value="<? echo $voltagem ?>" class="textbox" style="width:40px" onFocus="nextfield ='versao';">
	</td>
	
	<td align='center'>
		<input type="text" name="versao" size="10" maxlength="10" value="<? echo $versao ?>" class="textbox" style="width:60px" onFocus="nextfield ='numero_serie';">
	</td>
	
	<td align='center'>
		<input type="text" name="numero_serie" size = "10" maxlength="10" value="<? echo $numero_serie ?>" class="textbox" style="width:80px" onFocus="nextfield ='consumidor_nome';">
	</td>
</tr>
</table>

<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td bgcolor="<?echo $cor_fraca?>" align='left' class="menu_top">
		<b>Nome Consumidor</b>
	</td>
	
	<td bgcolor="<?echo $cor_fraca?>" align='left' class="menu_top">
		<b>Cidade</b>
	</td>
	
	<td bgcolor="<?echo $cor_fraca?>" align='center' class="menu_top">
		<b>UF</b>
	</td>
	
	<td bgcolor="<?echo $cor_fraca?>" align='center' class="menu_top">
		<b>Fone</b>
	</td>
</tr>
<tr>
	<td align='left'>
		<input type="text" name="consumidor_nome" size="20" maxlength="30" value="<? echo $consumidor_nome ?>" class="textbox" style="width:250px" onFocus="nextfield ='consumidor_cidade';">
	</td>
	
	<td align='left'>
		<input type="text" name="consumidor_cidade" size="20" maxlength="30" value="<? echo $consumidor_cidade ?>" class="textbox" style="width:250px" onFocus="nextfield ='consumidor_estado';">
	</td>
	
	<td align='left'>
		<input type="text" name="consumidor_estado" size="2" maxlength="2" value="<? echo $consumidor_estado ?>" class="textbox" style="width:25px" onFocus="nextfield ='consumidor_fone';">
	</td>
	
	<td align='left'>
		<input type="text" name="consumidor_fone" size="20" maxlength="20" value="<? echo $consumidor_fone ?>" class="textbox" style="width:100px" onFocus="nextfield ='loja_nome';">
	</td>
	
</tr>
</table>


<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td bgcolor="<?echo $cor_fraca?>" align='left' class="menu_top">
		<b>Nome Revenda</b>
	</td>
	
	<td bgcolor="<?echo $cor_fraca?>" align='left' class="menu_top">
		<b>CNPJ</b>
	</td>
	
	<td bgcolor="<?echo $cor_fraca?>" align='center' class="menu_top">
		<b>Nota Fiscal</b>
	</td>
	
	<td bgcolor="<?echo $cor_fraca?>" align='center' class="menu_top">
		<b>Data da Nota Fiscal</b>
	</td>
</tr>
<tr>
	<td align='left'>
		<input type="text" name="loja_nome" size="20" maxlength="30" value="<? echo $loja_nome ?>" class="textbox" style="width:250px" onFocus="nextfield ='loja_cnpj';">
	</td>
	
	<td align='left'>
		<input type="text" name="loja_cnpj" size="20" maxlength="18" value="<? echo $loja_cnpj ?>" class="textbox" style="width:110px" onKeyUp="formata_cnpj(this.value, 'frm_os')" onFocus="nextfield ='nota_fiscal';">
	</td>
	
	<td align='center'>
		<input type="text" name="nota_fiscal" size = "6" maxlength="6" value="<? echo $nota_fiscal ?>" class="textbox" style="width:90px" onFocus="nextfield ='data_nf';">
	</td>
	
	<td align='center'>
		<input type="text" name="data_nf" size = "10" maxlength="10" value="<? echo $data_nf ?>" class="textbox" style="width:80px" OnKeyUp="mascara_data(this.value, '_nf', 'frm_os')" onFocus="nextfield ='satisfacao';">
	</td>
</tr>
</table>

<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td bgcolor="<?echo $cor_fraca?>" align='left' class="menu_top">
		<b>30 dias Satisfação DeWALT</b>
	</td>
	
	<td bgcolor="<?echo $cor_fraca?>" align='left' class="menu_top">
		<b>Nº do laudo técnico</b>
	</td>
</tr>
<tr>
	<td align='left'>
		<input type="radio" name="satisfacao" <?if ($satisfacao == "t") echo " checked ";?> value="dewalt" onFocus="nextfield ='satisfacao_obs';">
		<font face='arial' size='-2'>Ativar "30 dias satisfação DeWALT"</font>&nbsp;
		<input type="radio" name="satisfacao" <?if ($satisfacao == "t") echo " checked ";?> value="porter_cable" onFocus="nextfield ='satisfacao_obs';">
		<font face='arial' size='-2'>Ativar "30 dias satisfação Porter Cable"</font>

	</td>
	
	<td align='left'>
		<input type="text" name="satisfacao_obs" size="10" value="<? echo $satisfacao_obs ?>" class="textbox" onFocus="nextfield ='done';">
	</td>
</tr>
</table>

<!-- ============================ Botoes de Acao ========================= -->

<?
echo "<table width='650' border='0' cellpadding='2' cellspacing='2' align='center'>";
echo "<tr>";

echo "<td align='center' width='50%'>";
echo "<input type='hidden' name='btn_acao' value=''>";
echo "<img src='imagens/btn_continuar.gif' style='cursor: pointer;' onclick=\"javascript: if ( document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='prosseguir'; document.frm_os.submit() ; } else { alert ('Aguarde submissão da OS...'); }\">";
echo "</td>";

echo "</tr>";
echo "</table>";
echo "<br><br>";

if ($btn_acao == '1' AND $prossegue == "sim") {
	echo "<table width='650' border='0' cellpadding='2' cellspacing='2' align='center'>";
	echo "<tr>";
	
	echo "<td bgcolor='#FFFFFF' align='center'><hr></td>";
	
	echo "</tr>";
	echo "</table>";
	
	$sql = "SELECT  tbl_marca.nome         AS equipamento_marca
			FROM    tbl_produto
			JOIN    tbl_grupo ON tbl_grupo.grupo = tbl_produto.grupo
			JOIN    tbl_marca ON tbl_marca.marca = tbl_grupo.marca
			WHERE   tbl_produto.produto = $xxx_produto;";
	$resx = @pg_exec($con,$sql);
	
	if (@pg_numrows($resx) > 0) {
		$equipamento_marca      = trim(pg_result($resx,0,equipamento_marca));
	}
	
	$sql = "SELECT    tbl_causa.descricao            AS causa_descricao        ,
					  tbl_consequencia.descricao     AS consequencia_descricao ,
					  tbl_consequencia.consequencia                            ,
					  tbl_consequencia.isolada                                 ,
					  tbl_consequencia.defeito                                 ,
					  tbl_grupo_consequencia.mao_obra                          ,
					  tbl_grupo_consequencia.grupo_consequencia
			FROM      tbl_causa
			JOIN      tbl_consequencia       ON tbl_causa.causa               = tbl_consequencia.causa
			JOIN      tbl_grupo_consequencia ON tbl_consequencia.consequencia = tbl_grupo_consequencia.consequencia
											AND tbl_grupo_consequencia.grupo  = (SELECT tbl_produto.grupo FROM tbl_produto WHERE tbl_produto.produto = $xxx_produto)
			ORDER BY tbl_consequencia.defeito;";
	$res = @pg_exec ($con,$sql);
	
	if (@pg_numrows($res) > 0) {
		echo "<table width='650' align='center' border='0' cellpadding='2' cellspacing='2'>";
		echo "<tr>";
		
		echo "<td colspan='3'align='center' class='table_line'>";
		echo "<b>Defeitos que constam na lista para este equipamento</b>";
		echo "</td>";
		
		echo "</tr>";
		echo "<tr>";
		
		echo "<td align='center' width='80' class='table_line'>";
		echo "<b>Defeito</b>";
		echo "</td>";
		
		echo "<td align='left' class='table_line'>";
		echo "<b>Descrição</b>";
		echo "</td>";
		
		echo "<td align='center' class='table_line'>";
		echo "<b>Mão-de-obra</b>";
		echo "</td>";
		
		echo "</tr>";
		
		$aux = 0;
		$qtde_consequencia = 0;
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$aux++;
			
			$grupo_consequencia = "";
			$aplicavel          = "";
			
			$consequencia           = trim (pg_result ($res,$i,consequencia));
			$defeito                = trim (pg_result ($res,$i,defeito));
			$grupo_consequencia     = trim (pg_result ($res,$i,grupo_consequencia));
			$mao_obra               = trim (pg_result ($res,$i,mao_obra));
			$consequencia_descricao = trim (pg_result ($res,$i,consequencia_descricao));
			
			if (strlen($os) > 0) {
				$sql = "SELECT  tbl_new_os_defeito.new_os_defeito,
								tbl_new_os_defeito.consequencia
						FROM    tbl_new_os_defeito
						JOIN    tbl_new_os       ON tbl_new_os.new_os             = tbl_new_os_defeito.new_os
						JOIN    tbl_consequencia ON tbl_consequencia.consequencia = tbl_new_os_defeito.consequencia
						WHERE   tbl_new_os_defeito.new_os       = $os
						AND     tbl_new_os_defeito.consequencia = $consequencia
						AND     tbl_new_os.produto              = $xxx_produto;";
				$res1 = @pg_exec ($con,$sql);
				
				if (pg_numrows($res1) > 0) {
					$defeito_existente = trim(pg_result($res1,0,consequencia));
				}else{
					$defeito_existente = $_POST["defeito_lista".$aux];
				}
			}else{
				if (strlen($erro) > 0) {
					$defeito_existente = $_POST["defeito_lista".$aux];
				}
			}
			
			####### QUEBRA DEWALT
			if ($equipamento_marca == "DeWalt") {
				if ($defeito >= 20 AND $defeito <= 27 AND $imprime_0 == false) {
					echo "<tr>";
					echo "<td colspan='3' align='center' class='table_line'><b>Motor desalojado<b>";
					echo "</td>";
					echo "</tr>";
					$imprime_0 = true;
				}
				
				if ($defeito >= 28 AND $defeito <= 34 AND $imprime_1 == false) {
					echo "<tr>";
					echo "<td colspan='3' align='center' class='table_line'><b>Motor em curto ou queimado<b>";
					echo "</td>";
					echo "</tr>";
					$imprime_1 = true;
				}
				
				if ($defeito >= 35 AND $defeito <= 43 AND $imprime_2 == false) {
					echo "<tr>";
					echo "<td colspan='3'><hr>";
					echo "</td>";
					echo "</tr>";
					$imprime_2 = true;
				}
				
				if ($defeito >= 44 AND $defeito <= 45 AND $imprime_3 == false) {
					echo "<tr>";
					echo "<td colspan='3' align='center' class='table_line'><b>Transmissão danificada<b>";
					echo "</td>";
					echo "</tr>";
					$imprime_3 = true;
				}
				
				if ($defeito == 47 AND $imprime_4 == false) {
					echo "<tr>";
					echo "<td colspan='3'><hr>";
					echo "</td>";
					echo "</tr>";
					$imprime_4 = true;
				}
			}
			
			####### QUEBRA HOBBY
			if ($equipamento_marca == "Hobby") {
				if ($defeito >= 13 AND $defeito <= 14 AND $imprime_0 == false) {
					echo "<tr>";
					echo "<td colspan='3' align='center' class='table_line'><b>Transmissão danificada<b>";
					echo "</td>";
					echo "</tr>";
					$imprime_0 = true;
				}
				
				if ($defeito >= 15 AND $defeito <= 23 AND $imprime_1 == false) {
					echo "<tr>";
					echo "<td colspan='3' align='center' class='table_line'><b>Motor desalojado<b>";
					echo "</td>";
					echo "</tr>";
					$imprime_1 = true;
				}
				
				if ($defeito >= 24 AND $defeito <= 30  AND $imprime_2 == false) {
					echo "<tr>";
					echo "<td colspan='3' align='center' class='table_line'><b>Motor em curto ou queimado<b>";
					echo "</td>";
					echo "</tr>";
					$imprime_2 = true;
				}
				
				if ($defeito >= 31 AND $defeito <= 33  AND $imprime_3 == false) {
					echo "<tr>";
					echo "<td colspan='3'><hr>";
					echo "</td>";
					echo "</tr>";
					$imprime_3 = true;
				}
			}
			
			$clicado = "";
			
			if ($defeito_existente == $consequencia) {
				$clicado = " checked ";
				$novo0 = 'f';
			}else{
				$novo0 = 't';
			}
			
			if (strstr($matriz0, ";" . $aux . ";")) {
				$var = "#E69797";
			}else{
				$var = "#FFFFFF";
			}
			
			echo "<tr>";
			
			echo "<td nowrap align='center' bgcolor='$var' class='table_line'>";
			echo "<input type='checkbox' $clicado name='defeito_lista$aux' value='$consequencia'>";
			echo "<font size='2'><b>$defeito</b></font>";
			echo "</td>";
			
			echo "<td align='left' bgcolor='$var' class='table_line'>";
			echo "$consequencia_descricao";
			echo "</td>";
			
			echo "<td align='right' bgcolor='$var' class='table_line' nowrap>";
			echo "<font size='2'><b>". number_format($mao_obra,2,",",".") ."</b></font>";
			echo "</td>";
			
			echo "</tr>";
			
			$qtde_consequencia++;
		}
		echo "<input type='hidden' name='qtde_consequencia' value='$qtde_consequencia'>";
		echo "</table>\n";
	}
	
	
	### DEFEITOS QUE NÃO CONSTAM NA LISTA
	$aux = 0;
	$sql = "SELECT      distinct
						tbl_new_os_item.new_os_item
			FROM        tbl_new_os_item
			LEFT JOIN   tbl_new_os_defeito ON tbl_new_os_defeito.new_os = tbl_new_os_item.new_os
			WHERE       tbl_new_os_item.new_os = $os
			AND         tbl_new_os_item.defeito_descricao NOTNULL
			ORDER BY new_os_item;";
	$res = @pg_exec ($con,$sql);
	
	for ($a = 0 ; $a < $qtde_linhas_defeitos ; $a++) {
		$aux++;
		
		if (strlen($os) > 0 AND strlen($erro) == 0) {
			if (@pg_numrows($res) > 0) {
				$new_os_item = trim(@pg_result($res,$a,new_os_item));
			}
			
			$sql = "SELECT  tbl_new_os_item.new_os_item,
							tbl_peca.referencia        ,
							tbl_new_os_item.qtde       ,
							tbl_new_os_item.defeito_descricao
					FROM    tbl_new_os_item
					JOIN    tbl_peca ON tbl_peca.peca = tbl_new_os_item.peca
					WHERE   tbl_new_os_item.new_os      = $os
					AND     tbl_new_os_item.new_os_item = $new_os_item
					ORDER BY new_os_item;";
			$ped = @pg_exec ($con,$sql);
			
			if (@pg_numrows($ped) == 0) {
				$peca    = $_POST["peca".$aux];
				$qtde    = $_POST["qtde".$aux];
				$defeito = $_POST["defeito".$aux];
			}else{
				$peca    = trim(pg_result($ped,0,referencia));
				$qtde    = trim(pg_result($ped,0,qtde));
				$defeito = trim(pg_result($ped,0,defeito_descricao));
			}
		}else{
			$peca    = $_POST["peca".$aux];
			$qtde    = $_POST["qtde".$aux];
			$defeito = $_POST["defeito".$aux];
		}
		
		if ($a == 0) {
			echo "<table width='650' border='0' cellpadding='2' cellspacing='2' align='center'>";
			echo "<tr>";
			
			echo "<td bgcolor='#FFFFFF' align='center'><hr></td>";
			
			echo "</tr>";
			echo "</table>";
			
			echo "<table width='650' align='center' border='0' cellpadding='2' cellspacing='2'>";
			echo "<tr>";
			
			echo "<td colspan='3'align='center' class='table_line'>";
			echo "<b>Defeitos que não constam na lista para este equipamento</b>";
			echo "</td>";
			
			echo "</tr>";
			echo "</table>";
			
			echo "<table width='650' align='center' border='0' cellpadding='2' cellspacing='2'>";
			echo "<tr>";
			
			echo "<td colspan='3'align='center' class='table_line'>";
			echo "Peça";
			echo "</td>\n";
			
			echo "<td colspan='3'align='center' class='table_line'>";
			echo "Qtde";
			echo "</td>\n";
			
			echo "<td colspan='3'align='center' class='table_line'>";
			echo "Descrição do Defeito (Informe com detalhes o problema apresentado)";
			echo "</td>\n";
			
			echo "</tr>\n";
		}
		
		$prox    = $aux + 1;
		$done    = $qtde_linhas_defeitos;
		
		if (strstr($matriz1, ";" . $aux . ";")) {
			$var = "res_err";
		}else{
			$var = "res";
		}
		
		echo "<tr>";
		
		echo "<td bgcolor='#FFFFFF' colspan='3'align='center'>";
		echo "<input type='text' name='peca$aux' size='20' maxlength='20' value='$peca' class='textbox' style='width:100px' onblur=\"javascript:fnc_fora_linha_os(this.value, $aux)\" onFocus=\"nextfield ='qtde$aux';\">\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' colspan='3'align='center'>";
		echo "<input type='text' name='qtde$aux' size='20' maxlength='6' value='$qtde' class='textbox' style='width:50px' onFocus=\"nextfield ='defeito$aux';\">\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' colspan='3'align='center'>";
		echo "<input type='text' name='defeito$aux' size='20' maxlength='100' value='$defeito' class='textbox' style='width:390px'"; if ($prox >= $done) { echo "onFocus=\"nextfield ='done'\""; }else{ echo "onFocus=\"nextfield ='peca$prox'\""; } echo ">\n";
		echo "</td>\n";
		
		echo "</tr>\n";
	}
	echo "</table>\n";
	
	echo "<table width='650' border='0' cellpadding='2' cellspacing='2' align='center'>";
	echo "<tr>";
	
	echo "<td align='center' width='50%'>";
	echo "<input type='hidden' name='btn_acao' value=''>";
	echo "<img src='imagens/gravar.gif' style='cursor: pointer;' onclick=\"javascript: if ( document.frm_os.btn_acao.value == '0' ) { alert('Gravando OS') ; document.frm_os.btn_acao.value='1'; document.frm_os.submit() ; } else { alert ('Aguarde submissão da OS...'); }\">";
	echo "</td>";
	
	echo "</tr>";
	echo "</table>";
}
?>

</form>


</body>
</html>