<?
//OBS: ESTE ARQUIVO UTILIZA AJAX: form_nf_ret_ajax.php

include 'dbconfig.php';
// $dbnome = 'teste';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
$fabrica = 10;
unset($nota_fiscal) ;
$faturamento	= $_POST["faturamento"];
if(strlen($faturamento)==0)
	$faturamento = $_GET["faturamento"];

$btn_acao= $_POST["btn_acao"];

$total_qtde_item= (strlen($_POST["total_qtde_item"]) > 0) ? $_POST["total_qtde_item"] : 10;

if(strlen($btn_acao)==0)
	$btn_acao = $_GET["btn_acao"];


if ($btn_acao == "Gravar") {
	$tipo_nf    			  = trim($_POST['tipo_nf'])						 ;
	$emissao				  = trim($_POST["emissao"])    		  			 ;
	$saida					  = trim($_POST['saida'])      		  			 ;
	$nota_fiscal			  = substr($_POST['nota_fiscal'],0,30)           ;
	$serie					  = substr($_POST['serie'],0,3)                  ;
	$natureza				  = substr($_POST['natureza'],0,30)              ;
	$cfop					  = substr($_POST['cfop'],0,10)                  ;
	$os_cod		              = trim($_POST['os_id'])  	 	         	  	 ;
	$posto_cod                = trim($_POST['posto_cod']) 		  	         ;
	$distribuidor_cod         = trim($_POST['distribuidor_cod'])             ;
	$fornecedor_distrib		  = substr($_POST['fornecedor_distrib'],0,64)    ;
	$tipo_consumidor          = $_POST['tipo_consumidor']                    ;
	$cpf_consumidor			  = $_POST['cpf_consumidor']  					 ;
	$cnpj_revenda			  = $_POST['cnpj_revenda']				         ;
	$ie_consumidor			  = $_POST['ie_consumidor']				         ;
	$consumidor_cep			  = $_POST['consumidor_cep']                     ;
	$logradouro_consumidor	  = substr($_POST['logradouro_consumidor'],0,64) ;
	$numero_consumidor		  = substr($_POST['numero_consumidor'],0,8)		 ;
	$complemento_consumidor	  = substr($_POST['complemento_consumidor'],0,32);
	$bairro_consumidor        = substr($_POST['bairro_consumidor'],0,32)     ;
	$cidade_consumidor        = substr($_POST['cidade_consumidor'],0,32)	 ;
	$estado_consumidor        = substr($_POST['estado_consumidor'],0,2)      ;
	$fone_consumidor          = $_POST['fone_consumidor']                    ;
	$qtde_volumes             = $_POST['qtde_volumes']                       ;
	$transp					  = substr($_POST['transportadora'],0,30)        ;
	$fornecedor_distrib_posto = trim($_POST['fornecedor_distrib_posto'])     ;
	$base_icms_substtituicao  = trim($_POST['base_icms_substtituicao'])      ;
	$valor_icms_substtituicao = trim($_POST['valor_icms_substtituicao'])     ;
	$valor_frete              = trim($_POST['valor_frete'])                  ;
	$valor_desconto           = trim($_POST['valor_desconto'])               ;
	$outros_valores           = trim($_POST['outros_valores'])               ;
	$valor_seguro          	  = trim($_POST['valor_seguro'])                 ;
	$nf_obs               	  = trim($_POST['nf_obs'])                       ;
	$tipo_frete               = $_POST['tipo_frete']                         ;
	$total_nota				  = trim($_POST['total_nota'])					 ;
	
	
	//TRATAMENTO DE CAMPOS COM PONTOS - HIFENS ... INICIO
	
	$os_cod                   = ($os_cod!='') ? $os_cod : 'NULL'; // 23-08-2011 - MLG - Adicionei o NULL quando não tem nº de OS.
	
	$fone_consumidor_formated = $fone_consumidor;
	$consumidor_cep_formated = $consumidor_cep;
	$ie_consumidor_formated = $ie_consumidor;
	$cpf_consumidor_formated = $cpf_consumidor;
	$cnpj_revenda_formated = $cnpj_revenda;
	
	$fone_consumidor_formated = str_replace("(","",$fone_consumidor_formated );
	$fone_consumidor_formated = str_replace(")","",$fone_consumidor_formated );
	$fone_consumidor_formated = str_replace("-","",$fone_consumidor_formated );
	$fone_consumidor_formated = str_replace(" ","",$fone_consumidor_formated );
	$fone_consumidor_formated = substr($fone_consumidor_formated,0,10);
	
	
	$consumidor_cep_formated = str_replace("-","",$consumidor_cep_formated); 	
	$consumidor_cep_formated = str_replace(".","",$consumidor_cep_formated);
	$consumidor_cep_formated = substr($consumidor_cep_formated,0,8);
	
	$ie_consumidor_formated = str_replace(".","",$ie_consumidor_formated);
	$ie_consumidor_formated = str_replace(" ","",$ie_consumidor_formated);
	
	$cpf_consumidor_formated  = str_replace(".","",$cpf_consumidor_formated);
	$cpf_consumidor_formated  = str_replace("-","",$cpf_consumidor_formated);
	$cpf_consumidor_formated  = substr($cpf_consumidor_formated,0,16);
	
	$cnpj_revenda_formated  = str_replace(".","",$cnpj_revenda_formated);
	$cnpj_revenda_formated  = str_replace("-","",$cnpj_revenda_formated);
	$cnpj_revenda_formated  = str_replace("/","",$cnpj_revenda_formated);
	$cnpj_revenda_formated  = substr($cnpj_revenda_formated,0,16);
	
	
	
	//TRATAMENTO DE CAMPOS COM PONTOS - HIFENS ... FIM
	
	//TRATAMENTO DE VALORES MONETARIOS (INICIO)
	$base_icms_substtituicao_formated = $base_icms_substtituicao;
	$valor_icms_substtituicao_formated = $valor_icms_substtituicao;
	$valor_frete_formated = $valor_frete;
	$valor_desconto_formated = $valor_desconto;
	$outros_valores_formated = $outros_valores;
	$valor_seguro_formated = $valor_seguro;
	
	
	$base_icms_substtituicao_formated = str_replace(".","",$base_icms_substtituicao_formated);
	$base_icms_substtituicao_formated = str_replace(",",".",$base_icms_substtituicao_formated);
	
	$valor_icms_substtituicao_formated = str_replace(".","",$valor_icms_substtituicao_formated);
	$valor_icms_substtituicao_formated = str_replace(",",".",$valor_icms_substtituicao_formated);
	
	$valor_frete_formated = str_replace(".","",$valor_frete_formated);
	$valor_frete_formated = str_replace(",",".",$valor_frete_formated);
	
	$valor_desconto_formated = str_replace(".","",$valor_desconto_formated);
	$valor_desconto_formated = str_replace(",",".",$valor_desconto_formated);
	
	$outros_valores_formated = str_replace(".","",$outros_valores_formated);
	$outros_valores_formated = str_replace(",",".",$outros_valores_formated);
	
	$valor_seguro_formated = str_replace(".","",$valor_seguro_formated);
	$valor_seguro_formated = str_replace(",",".",$valor_seguro_formated);
	
	//TRATAMENTO DE VALORES MONETARIOS (FIM)
	
	
	if(strlen($base_icms_substtituicao)==0){
		$base_icms_substtituicao = 0;
	}
	
	

	if(strlen($valor_icms_substtituicao)==0){
		$valor_icms_substtituicao = 0;
	}

	if(strlen($nota_fiscal) > 0) {
		$sql = "SELECT faturamento 
		FROM tbl_faturamento 
		WHERE fabrica     = $fabrica
		AND   posto       = $login_posto
		AND   nota_fiscal = '$nota_fiscal'";
		$res = pg_query ($con,$sql);

		if(pg_num_rows($res)>0){
			$faturamento = trim(pg_fetch_result($res,0,faturamento));
			$erro_msg = "Nota fiscal ' $nota_fiscal ' já Cadastrada ";
			// exit;
		}
	}
	
	## TRATAMENTO DE EXCEÇÕES
	try
	{
		####### VALIDAÇÃO DE DATAS ##INICIO##
		
		if(empty($emissao) OR empty($saida))
		{
        throw new Exception("Data Inválida");
		}

		list($di, $mi, $yi) = explode("/", $emissao);
		if(!checkdate($mi,$di,$yi))
		{
			throw new Exception("Data Inválida");
		}
		
		list($di, $mi, $yi) = explode("/", $saida);
		if(!checkdate($mi,$di,$yi))
		{
			throw new Exception("Data Inválida");
		}
		
		list($df, $mf, $yf) = explode("/", $emissao);
		if(!checkdate($mf,$df,$yf)) 
			throw new Exception("Data Inválida");
		
		list($df, $mf, $yf) = explode("/", $saida);
		if(!checkdate($mf,$df,$yf)) 
			throw new Exception("Data Inválida");		
		
		####### VALIDAÇÃO DE DATAS ##FIM##
/*
		if(strlen($nota_fiscal)==0)
		{
			throw new Exception('Digite o número da Nota Fiscal');
		}
*/		
		if(strlen($serie)==0)
		{
			throw new Exception('Digite o Número de Série');
		}
		
		if(strlen($cfop)==0)
		{
			throw new Exception('Digite o CFOP');
		}
		
		if(strlen($natureza)==0)
		{
			throw new Exception('Digite a Natureza da Operação');
		}
		
		if(strlen($fornecedor_distrib)==0)
		{
			throw new Exception('Digite o Destinatário');
		}
		
		
		if ($tipo_consumidor == "F")
		{
			if (strlen($cpf_consumidor) == 0)
			{
				throw new Exception("Digite o CPF do Destinatário");
			}
		} 		
		else if ($tipo_consumidor == "J")
		{
			if (strlen($cnpj_revenda)==0)
			{
				throw new Exception("Digite o CNPJ do Destinatário");
			}
		}
		
		if (strlen($consumidor_cep)==0)
		{
			throw new Exception("Digite o CEP do Destinatário ");
		}
		
		if (strlen($logradouro_consumidor)==0)
		{
			throw new Exception("Digite o Endereço do Destinatário");
		}
		
		if (strlen($numero_consumidor)==0)
		{
			throw new Exception("Digite o Número de Endereço do Destinatário");
		}
		
		if (strlen($bairro_consumidor)==0)
		{
			throw new Exception("Digite o Bairro do Destinatário");
		}
		
		if (strlen($cidade_consumidor)==0)
		{
			throw new Exception("Digite a Cidade do Destinatário");
		}
		
		if (strlen($estado_consumidor)==0)
		{
			throw new Exception("Selecione o Estado do Destinatário");
		}
		
		if (strlen($transp)==0)
		{
			throw new Exception("Digite a Transportadora");
		}
		
		if (strlen($tipo_frete)==0)
		{
			throw new Exception("Selecione o Tipo do Frete");
		}
		
		if (strlen($qtde_volumes)==0)
		{
			throw new Exception("Selecione a Quantidade de Volumes");
		}
		
		
	}
	
	catch (Exception $e)
	{
		$erro_msg = $e->getMessage();
	}

	
			
	if ( strlen($valor_seguro_formated)==0 ){
		$valor_seguro_formated = "null";
	}
	
	if ( strlen($valor_desconto_formated)==0 ){
		$valor_desconto_formated = "null";
	}
	
	if ( strlen($outros_valores_formated)==0 ){
		$outros_valores_formated = "null";
	}
	
	if ( strlen($valor_frete_formated)==0 ){
		$valor_frete_formated = "null";
	}
	if ( strlen($valor_icms_substtituicao_formated)==0 ){
		$valor_icms_substtituicao_formated = "null";
	}
	if ( strlen($base_icms_substtituicao_formated)==0 ){
		$base_icms_substtituicao_formated = "null";
	}
	
	
	$sql = "SELECT TO_CHAR(MAX (nota_fiscal::integer) + 1, '000000') AS nota_fiscal FROM tbl_faturamento WHERE distribuidor = 4311";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_last_error($con);
	$nota_fiscal = pg_fetch_result($res,0,0);

	if (strlen ($nota_fiscal) == 0) {
		$nota_fiscal = "000000";
	}

	$re_match_YMD	= '/(\d{4})\W?(\d{2})\W?(\d{2})/';
	$re_match_DMY	= '/(\d{2})\W?(\d{2})\W?(\d{4})/';
	$re_format_YMD	= '$3-$2-$1';
	$re_format_DMY	= '$3/$2/$1';
	
	$repEmissao = $emissao;
	$repSaida = $saida;
	$repSaida   = preg_replace($re_match_DMY, $re_format_YMD, $saida);
	$repEmissao = preg_replace($re_match_DMY, $re_format_YMD, $emissao);

	if(strlen($erro_msg) == 0){
		$res = pg_query ($con,"BEGIN TRANSACTION");
		$sql= "INSERT INTO tbl_faturamento 
			(fabrica          ,
			emissao           ,
			conferencia       ,
			saida             ,";
			if ($tipo_nf == 1){
				if (strlen($posto_cod) > 0){
					$sql .= "
					posto          ,
					distribuidor      ,
					";
				}else{
					$sql .= "
						distribuidor      ,
					";
				}
			}else if($tipo_nf==0){
				$sql .= "	
				posto,	";
			}
			$sql .= "
			cfop              ,
			nota_fiscal       ,
			serie             ,
			transp            ,
			qtde_volume       ,
			natureza          ,
			obs               ,
			total_nota        ,
			tipo_nf           ,
			base_icms_substtituicao,
			valor_icms_substtituicao,
			valor_seguro,
			valor_desconto,
			valor_outros,
			valor_frete,
			tipo_frete
			
			)VALUES (
			
			$faturamento_fabrica,
			'$repEmissao'       ,
			CURRENT_TIMESTAMP   ,
			'$repSaida'         ,";
		
			if ($tipo_nf == 1){
				if (strlen($posto_cod) > 0){
					$sql .= "
					$posto_cod          ,
					$distribuidor_cod   ,
					";
				}else{
					$sql .= "
						$distribuidor_cod      ,
					";
				}
			}else if($tipo_nf==0){
				$sql .= "	
				$posto_cod,	";
			}	
			
			$sql .= "
			'$cfop'             ,
			'$nota_fiscal'      ,
			'$serie'            ,
			'$transp'           ,
			$qtde_volumes       ,
			'$natureza'         ,
			'$nf_obs',
			$total_nota,
			$tipo_nf,
			$base_icms_substtituicao_formated,
			$valor_icms_substtituicao_formated,
			$valor_seguro_formated,
			$valor_desconto_formated,
			$outros_valores_formated,
			$valor_frete_formated,
			'$tipo_frete')";
		
		// echo nl2br($sql);exit;
		
		$res = pg_query ($con,$sql);
		if (!is_resource($res)) 
		{
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
			$erro_msg.= "<br> Erro ao INSERIR nova NF.";
		}
		
		
		
		$somatoria_nota = 0;
		if(strlen($erro_msg) > 0){
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
			$erro_msg="<br> Erro ao inserir a NF:$nota_fiscal$erro_msg";
		}else{
			$res = pg_query ($con,"SELECT CURRVAL ('seq_faturamento') as fat;");
			$faturamento =trim (pg_fetch_result($res, 0 , fat));
			
			$sql = "
				INSERT INTO tbl_faturamento_destinatario(
				faturamento,
				nome,
				cpf_cnpj,
				ie,
				logradouro,
				numero,
				bairro,
				complemento,
				cep,
				municipio,
				uf,
				fone
				) VALUES (
					$faturamento,
					'$fornecedor_distrib',
					";
					if ($tipo_consumidor == "F"){
						$sql .="'$cpf_consumidor_formated',";
					} else if($tipo_consumidor == "J"){
						$sql .="'$cnpj_revenda_formated',";
					}
			$sql .="
				'$ie_consumidor_formated',
				'$logradouro_consumidor',
				'$numero_consumidor',
				'$bairro_consumidor',
				'$complemento_consumidor',
				'$consumidor_cep_formated',
				'$cidade_consumidor',
				'$estado_consumidor',
				'$fone_consumidor_formated'
				)
			";
			
			$res = pg_query ($con,$sql);
			if (!is_resource($res)) $erro_msg.= "<br> Erro ao INSERIR nova NF.";
			$t_item = false;
			for($i=0; $i< $total_qtde_item; $i++){
				$erro_item  = "" ;
				$referencia = $_POST["referencia_$i"];
				$descricao  = $_POST["descricao_$i"];
				$qtde       = $_POST["qtde_$i"];
				$preco      = $_POST["preco_$i"];
				$cfop_item  = $_POST["cfop_$i"];
				$pedido     = $_POST["pedido_$i"]; 
				$aliq_icms  = $_POST["aliq_icms_$i"]; 
				$aliq_ipi   = $_POST["aliq_ipi_$i"]; 
				$base_icms  = $_POST["base_icms_$i"]; 
				$base_ipi   = $_POST["base_ipi_$i"]; 
				$valor_ipi  = $_POST["valor_ipi_$i"]; 
				$valor_icms = $_POST["valor_icms_$i"];
				if (strlen($referencia)>0 and $t_item==false){
					$t_item = true;
				}
				if ( strlen($referencia)==0  and $t_item == false){
					$erro_msg= "Insira um item para a NF";
				}
				
				if (strlen($cfop_item)==0 and !empty($referencia)){
					$erro_msg= "Insira um CFOP para o item da NF";
				}
				
				//HD 141162 Daniel
				$somatoria_nota += ($preco * $qtde) + str_replace(",",".",$valor_ipi);
				
				if(strlen($referencia)>0){
					$sql = "SELECT  peca,
							referencia,
							descricao
							FROM   tbl_peca 
							WHERE  fabrica in (10,51,81)
							AND    referencia = '$referencia';";
							
					$res = pg_query ($con,$sql);
					if(pg_num_rows($res)>0){
						$peca       = trim(pg_fetch_result($res,0,peca));
						$referencia = trim(pg_fetch_result($res,0,referencia));
						$descricao  = trim(pg_fetch_result($res,0,descricao));
					}else{
						//Caso não esteja cadastrado como peça ele irá procurar como Produto
						$sql = "SELECT  produto   ,
								referencia,
								descricao ,
								ipi       ,
								origem    ,
								fabrica
								FROM   tbl_produto
								JOIN   tbl_linha USING(linha)
								WHERE  fabrica in (10,51,81)
								AND    referencia = '$referencia';";
						$res = pg_query ($con,$sql);
						if(pg_num_rows($res)>0){
							$xproduto      = trim(pg_fetch_result($res,0,produto));
							$xreferencia   = trim(pg_fetch_result($res,0,referencia));
							$xdescricao    = trim(pg_fetch_result($res,0,descricao));
							$xipi          = trim(pg_fetch_result($res,0,ipi));
							$xorigem       = trim(pg_fetch_result($res,0,origem));
							$xfabrica      = trim(pg_fetch_result($res,0,fabrica));
							if(strlen($xipi)==0) $xipi = 0;
							$sql = "INSERT INTO tbl_peca (
										fabrica,
										referencia,
										descricao,
										ipi,
										origem,
										produto_acabado
									) VALUES (
										$xfabrica           ,
										'$xreferencia'      ,
										'$xdescricao'       ,
										$xipi               ,
										'NAC'               ,
										't'
								)" ;
								
							$res = @pg_query($con,$sql);
							$erro_item = pg_last_error($con);

							if(strlen($erro_item) == 0) {
								$sql = "SELECT CURRVAL ('seq_peca')";
								$res = pg_query($con,$sql);
								$peca = trim (pg_fetch_result($res, 0 , 0));
							}else{
								$erro_item .="Erro ao inserir peça $xreferencia<br>";
							}
						}else{
							$erro_item .= "Peça $referencia não encontrada!<br>" ;
						}
					}

					if(strlen($qtde)==0)  $erro_item.= "Digite a qtde<br>" ;
					if(strlen($preco)==0) $erro_item.= "Digite o preço<br>";

					if(strlen($pedido)==0){
						$pedido      = "null";
						$pedido_item = "null";
					}

					if(strlen($aliq_icms)==0)  $aliq_icms  = "0";
					if(strlen($aliq_ipi)==0)   $aliq_ipi   = "0";
					if(strlen($base_icms)==0)  $base_icms  = "0";
					if(strlen($valor_icms)==0) $valor_icms = "0";
					if(strlen($base_ipi)==0)   $base_ipi   = "0";
					if(strlen($valor_ipi)==0)  $valor_ipi  = "0";
					$base_icms  = str_replace(",",".",$base_icms);
					$valor_icms = str_replace(",",".",$valor_icms);
					$base_ipi   = str_replace(",",".",$base_ipi);
					$valor_ipi  = str_replace(",",".",$valor_ipi);

					if(strlen($erro_item)==0){
						$sql=  "INSERT INTO tbl_faturamento_item (
							faturamento,
							situacao_tributaria,
							peca       ,
							qtde       ,
							preco      ,
							cfop       ,
							aliq_icms  ,
							aliq_ipi   ,
							base_icms  ,
							valor_icms ,
							base_ipi   ,
							valor_ipi,
							os
						)VALUES(
							$faturamento,
							'00'        ,
							$peca       ,
							$qtde       ,
							$preco      ,
							'$cfop'     ,
							$aliq_icms  ,
							$aliq_ipi   ,
							$base_icms  ,
							$valor_icms ,
							$base_ipi   ,
							$valor_ipi,
							$os_cod
						);";
						// echo nl2br($sql);
							// exit;
						$res = @pg_query ($con,$sql);	
						$erro_msg = pg_last_error($con);

						if(strlen($erro_msg) > 0){
							$erro_msg .=$erro_item . "<br>Erro ao inserir peça: $referencia";
						}

							
					}else{
						$erro_msg .= $erro_item ;
					}
				}

				if(strlen($erro_msg) > 0) {
					break;
				}
			}
			

			if(strlen($erro_msg)==0){
				$res = pg_query ($con,"COMMIT TRANSACTION");

				if(count($peca_mais) > 0) {
					foreach($peca_mais as $pecas){
						$sql = "SELECT referencia,nome
								FROM tbl_peca
								JOIN tbl_fabrica USING(fabrica)
								WHERE peca =".$pecas['peca'];
						$res = pg_query($con,$sql);
						if(pg_num_rows($res) > 0){
							$mensagem_peca .=pg_fetch_result($res,0,referencia).",";
							$fabrica_nome = pg_fetch_result($res,0,nome);
						}
					}

					$nome         = "TELECONTROL";
					$email_from   = "helpdesk@telecontrol.com.br";
					$assunto      = "Peças Faturadas a Mais";
					$destinatario ="paulo@telecontrol.com.br";
					$boundary = "XYZ-" . date("dmYis") . "-ZYX";
					$mensagem = "Prezado,<br> a(s) seguinte(s) peça(s) faturada(s) da $fabrica_nome tem a quantidade há mais que a pendência de pedido(s):<br>$mensagem_peca";
					$body_top = "--Message-Boundary\n";
					$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
					$body_top .= "Content-transfer-encoding: 7BIT\n";
					$body_top .= "Content-description: Mail message body\n\n";
					if(!empty($fabrica_nome)) {
						@mail($destinatario,$assunto,$mensagem,"From: ".$email_from." \n $body_top ");
					}
				}

				if(count($peca_sem_pedido) > 0) {
					foreach($peca_sem_pedido as $pecas){
						$sql = "SELECT referencia,nome
								FROM tbl_peca
								JOIN tbl_fabrica USING(fabrica)
								WHERE peca =".$pecas['peca'];
						$res = pg_query($con,$sql);
						if(pg_num_rows($res) > 0){
							$mensagem_peca .=pg_fetch_result($res,0,referencia).",";
							$fabrica_nome = pg_fetch_result($res,0,nome);
						}
					}
					$nome         = "TELECONTROL";
					$email_from   = "helpdesk@telecontrol.com.br";
					$assunto      = "Peças não encontradas";
					$destinatario ="paulo@telecontrol.com.br";
					$boundary = "XYZ-" . date("dmYis") . "-ZYX";
					$mensagem = "Prezado,<br> a(s) seguinte(s) peça(s) faturada(s) da $fabrica_nome não foram encontradas nos pedidos pendentes:<br>$mensagem_peca";
					$body_top = "--Message-Boundary\n";
					$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
					$body_top .= "Content-transfer-encoding: 7BIT\n";
					$body_top .= "Content-description: Mail message body\n\n";
					if(!empty($fabrica_nome)) {
						@mail($destinatario,$assunto,$mensagem,"From: ".$email_from." \n $body_top ");
					}
				}
				
				header('Location: http://www.telecontrol.com.br/assist/distrib/nf_cadastro_manual.php?s=1');
				
			}else{
				$res = pg_query ($con,"ROLLBACK TRANSACTION");
				$faturamento = "";
			}
			
		}
		
	}
	
}//FIM BTN: GRAVAR

?>
<title>Cadastro de Nota Fiscal</title>
<head>

<script type="text/javascript" src="/assist/admin/js/jquery.js"></script>
<link type="text/css" rel="stylesheet" href="css/css.css">
<script language='javascript' src='../admin/ajax_cep.js'></script>
<script type="text/javascript" src="js/ajax_busca.js"></script>
<script language='javascript' src='../ajax.js'></script>

<?php include "javascript_calendario.php";?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript" charset="utf-8" src="../js/jquery.alphanumeric.js"></script>
<script type="text/javascript" src="/assist/admin/js/jquery.maskmoney.js"></script>

<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />

<script type="text/javascript" language="javascript">
$(function(){
	$('#emissao').datePicker({startDate:'01/01/2000'});
	$('#saida').datePicker({startDate:'01/01/2000'});
	$("#emissao").maskedinput("99/99/9999");
	$("#saida").maskedinput("99/99/9999");
	//FORMATA CAMPO CEP
	$( "#consumidor_cep" ).maskedinput("99.999-999");
	$("#fone_consumidor").maskedinput("(99)9999-9999");
	
	
	$('.qtde_prod').each(function() {
		if ( $(this).val().length == 0 || $(this).val() <= 0 ) { return; }
		var _id = $(this).attr('id');
		if ( _id != undefined ) {
			var _tmp = _id.split('_');
			var _i   = _id[1];
			calc_base_icms(_i);
		}
	});
//  Mostra ou escone o formulário para enviar a Nota Fiscal eletrônica NF-e
	$('#openNFeForm').click(function () {
		$('#NFeForm').toggle('normal');
	});
	
	//Campos Monetarios "Base Calculo ICMS Substituição", "Valor ICMS Substituição", "Valor Frete", "Desconto", "Outros Valores", "Seguro"
	$(".money").maskMoney({symbol:"", decimal:".", thousands:'', precision:2, maxlength: 15});
	
	
	
	$(".tipoPessoa").click(function(){
		mudaTipoPessoa();
	});
	

	
});



function autocompletaCampos(){
	function formatItem(row) {
		//alert(row);
		return row[0] + " - " + row[1];
	}
	
	function formatResult(row) {
		return row[0];
	}

	/* Busca pela Descricao */
	$("input[rel='descricao']").autocomplete("nf_cadastro_manual_ajax.php?tipo=produto&busca=descricao&fabrica=<?=$fabrica?>", {
		minChars: 0,
		delay: 0,
		width: 350,
		max:50,
		matchContains: true,
		formatItem: function(row, i, max) {
			return row[0] + " - " + row[1];
		},
		formatResult: function(row) {
			return row[1];
		}
	});

	$("input[rel='descricao']").result(function(event, data, formatted) {
		$("input[name="+$(this).attr("alt")+"]").val(data[0]) ;
		$(this).focus();
	});

	/* Busca pelo Referencia */
		$("input[rel='referencia']").autocomplete("nf_cadastro_manual_ajax.php?tipo=produto&busca=referencia&fabrica=<?=$fabrica?>", {
		minChars: 0,
		delay: 0,
		width: 350,
		max:50,
		matchContains: true,
		formatItem: function(row, i, max) {
			return row[0] + " - " + row[1];
		},
		formatResult: function(row) {
			return row[0];
			
		}
	});

	$("input[rel='referencia']").result(function(event, data, formatted) {
		$("input[name="+$(this).attr("alt")+"]").val(data[1]) ;
		$(this).focus();
	});

}


function setFocus(lin) {
	$('#qtde_'+lin).focus();
}

//FUNÇÃO PARA CARREGAR FATURAMENTO
function retornaFat(http,componente) {
	var com = document.getElementById('f2');
	if (http.readyState == 1) {
		com.style.display    ='inline';
		com.style.visibility = "visible"
		com.innerHTML        = "&nbsp;&nbsp;<font color='#333333'>Consultando...</font>";
	}
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					com.innerHTML = results[1];
					setTimeout('esconde_carregar()',3000);
				}else{
					com.innerHTML = "&nbsp;&nbsp;<font color='#0000ff'>Sem faturamentos para esse fornecedor</font>";

				}
			}else{
				alert ('Fechamento nao processado');
			}
		}
	}
}


function esconde_carregar(componente_carregando) {
	document.getElementById('f2').style.visibility = "hidden";
}

//FUNÇÃO PARA CALCULAR O TOTAL SELECIONADO DE CADA FORNECEDOR
function calc_base_icms(i){
	var base=0.0, aliq_icms=0.0, valor_icms=0.0, aliq_ipi=0.0, valor_ipi=0.0;;
	preco= document.getElementById('preco_'+i).value;
	qtde= document.getElementById('qtde_'+i).value;
	aliq_icms	= document.getElementById('aliq_icms_'+i).value;
	aliq_ipi	= document.getElementById('aliq_ipi_'+i).value;
	
	

/*
	preco= preco.toString().replace( ".", "" );
	qtde= qtde.toString().replace( ".", "" );
	aliq_icms	= aliq_icms.toString().replace( ".", "" );
	aliq_ipi	= aliq_ipi.toString().replace( ".", "" );
*/
	preco       = preco.toString().replace( ",", "." );
	qtde        = qtde.toString().replace( ",", "." );
	aliq_icms   = aliq_icms.toString().replace( ",", "." );
	aliq_ipi    = aliq_ipi.toString().replace( ",", "." );

	preco       = parseFloat(preco);
	qtde        = parseFloat(qtde);
	aliq_icms   = parseFloat(aliq_icms);
	aliq_ipi    = parseFloat(aliq_ipi);

	base        = parseFloat(preco * qtde);
	base        = base.toFixed(2);
	valor_icms  = ((base * aliq_icms)/100);
	valor_icms  = valor_icms.toFixed(2);
	valor_ipi   = ((base *  aliq_ipi)/100);
	valor_ipi   = valor_ipi.toFixed(2);

	if(aliq_icms > 0) {
		document.getElementById('base_icms_'+i).value = base.toString().replace( ".", "," );
		document.getElementById('valor_icms_'+i).value = valor_icms.toString().replace( ".", "," );
	}else{
		document.getElementById('base_icms_'+i).value = '0';
		document.getElementById('valor_icms_'+i).value = '0';
	}

	if(aliq_ipi > 0) {
		document.getElementById('base_ipi_'+i).value = base.toString().replace( ".", "," );
		document.getElementById('valor_ipi_'+i).value = valor_ipi.toString().replace( ".", "," );
	}else{
		document.getElementById('base_ipi_'+i).value = '0';
		document.getElementById('valor_ipi_'+i).value = '0';
	}
	
	if(isNaN(valor_icms))
		valor_icms = 0.00;
	
	if(isNaN(valor_ipi))
		valor_ipi = 0.00;
		
	if(isNaN(preco))
		preco = 0.00;
		
	if(isNaN(qtde))
		qtde = 0;
	
	var item = 0;
	var total_item = (preco*qtde) + parseFloat(valor_ipi);

	$("#total_item_nf_"+i).val(total_item);
	
	somaValores();
	
	
}

function somaValores(){
	var item = 0;
	var total_itens = 0;
	$(".total_item_nf").each(function(){
		item = $(this).val();

		if(isNaN(item))
			item = 0;

		total_itens += parseFloat(item);
		
	});
	if(isNaN(total_itens))
		total_itens = 0;
	total_itens = parseFloat(total_itens);
	total_itens = total_itens.toFixed(2);
	
	var baseIcmsSubst = $("#base_icms_substtituicao").val();
	var valorIcmsSubst = $("#valor_icms_substtituicao").val();
	var valorFrete = $("#valor_frete").val();
	var valorDesconto = $("#valor_desconto").val();
	var outrosValores = $("#outros_valores").val();
	var valorSeguro = $("#valor_seguro").val();
	
	if(isNaN(baseIcmsSubst) || baseIcmsSubst == "")
		baseIcmsSubst = 0;
		
	if(isNaN(valorIcmsSubst) || valorIcmsSubst == "")
		valorIcmsSubst = 0;
		
	if(isNaN(valorFrete) || valorFrete == "")
		valorFrete = 0;	
		
	if(isNaN(valorDesconto)  || valorDesconto == "")
		valorDesconto = 0;
		
	if(isNaN(outrosValores) || outrosValores == "")
		outrosValores = 0;
		
	if(isNaN(valorSeguro) || valorSeguro == "")
		valorSeguro = 0;
	
	
	var total_geral;
	total_geral = 0;
	
	total_geral = ( (parseFloat(total_itens) + parseFloat(baseIcmsSubst) + parseFloat(valorIcmsSubst) + parseFloat(valorFrete)  + parseFloat(outrosValores) + parseFloat(valorSeguro) ) - parseFloat(valorDesconto) );
	total_geral = total_geral.toFixed(2);
	$("#total_nota").val(total_geral);
	$("#total_texto").html(total_geral);
	
}

function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='0';
	}
}



function addTr(numero){
	var numero2 = numero + 1;
	var cor = (numero2 % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

	if($("#"+numero2).length == 0) {
		$("#"+numero).after("<tr style='font-size: 12px' bgcolor='"+cor+"' id="+numero2+">\n<td align='right' nowrap>"+numero2+"</td>\n<td align='center' nowrap><input type='text' class='frm' name='referencia_"+numero+"' id='referencia_"+numero+"' value='' style='width: 90%;' maxlength='20' rel='referencia' alt='descricao_"+numero+"' ;'></td>\n<td align='center' nowrap><input type='text' class='frm' name='descricao_"+numero+"' id='descricao_"+numero+"' alt='referencia_"+numero+"' value='' style='width: 90%;' maxlength='20' rel='descricao' ></td>\n <td align='center' nowrap><input class='frm' type='text' name='qtde_"+numero+"' class='qtde_prod' id='qtde_"+numero+"' value='' style='width: 70px;text-align:right' maxlength='10' onKeyUp='calc_base_icms("+numero+");' onblur=\"checarNumero(this);\"></td>\n<td align='center' nowrap><input class='frm' type='text' name='preco_"+numero+"' id='preco_"+numero+"' value='' style='width: 70px;' maxlength='12' onKeyUp='calc_base_icms("+numero+");' onblur=\"checarNumero(this);\"></td>\n<td align='center' nowrap><input class='frm' type='text' style='width: 70px;' maxlength='12' name='cfop_"+numero+"' id='cfop_"+numero+"' value='<?=$cfop_item?>'>'></td>\n<td align='center' nowrap><input class='frm' type='text' name='aliq_icms_"+numero+"' id='aliq_icms_"+numero+"' value='' style='width: 70px;' maxlength='10' onKeyUp='calc_base_icms("+numero+");' onblur=\"checarNumero(this);\"></td>\n<td align='center' nowrap><input class='frm' type='text' name='aliq_ipi_"+numero+"' id='aliq_ipi_"+numero+"' value='' style='width: 70px;' maxlength='10' onKeyUp='calc_base_icms("+numero+");' onblur=\"checarNumero(this); addTr("+numero2+")\"></td>\n<td align='right' nowrap><input class='frm' type='text' name='base_icms_"+numero+"' id='base_icms_"+numero+"' value='' size='5' maxlength='10' style='background-color: "+cor+"; border: none;' onfocus='form_nf.referencia_"+numero2+".focus();' readonly></td>\n<td align='right' nowrap><input class='frm' type='text' name='valor_icms_"+numero+"' id='valor_icms_"+numero+"' value='' size='5' maxlength='10' style='background-color: "+cor+"; border: none;' onfocus='form_nf.referencia_"+numero2+".focus();' readonly></td>\n<td align='right' nowrap><input class='frm' type='text' name='base_ipi_"+numero+"' id='base_ipi_"+numero+"' value='' size='5' maxlength='10' style='background-color: "+cor+"; border: none;' onfocus='form_nf.referencia_"+numero2+".focus();' readonly></td>\n<td align='right' nowrap><input class='frm' type='text' name='valor_ipi_"+numero+"' id='valor_ipi_"+numero+"' value='' size='5' maxlength='10' style='background-color: "+cor+"; border: none;' onfocus='form_nf.referencia_"+numero2+".focus();' readonly><input type='hidden'  name='total_item_nf_"+numero+"' id='total_item_nf_"+numero+"' value='0' class='total_item_nf'></td>\n</tr>\n");
		$('#descricao_'+numero).blur(function(){
			setFocus(numero);
		});
		$('#referencia_'+numero).blur(function(){
			setFocus(numero);
		});
		$('#total_qtde_item').val(numero2);
		autocompletaCampos();
	}
}

function mudaTipoPessoa(){
	
	if ($("#tipoDestFisica").is(":checked")){
		$("#cnpj_revenda").hide();
		$("#label_ie").hide();
		$("#ie_consumidor").hide();
		$("#cpf_consumidor").show();
		$("#label_Cpf_Cnpj").text("CPF");
		
		}
	
	if ($("#tipoDestJuridica").is(":checked")){
			$("#cpf_consumidor").hide();
			$("#cnpj_revenda").show();
			$("#label_ie").show();
			$("#ie_consumidor").show();
			$("#label_Cpf_Cnpj").text("CNPJ");
	}
	
}

function pesquisaPostoAjax(){
	var q = $("#posto_nome").val();
	$.ajax({
			url: "nf_cadastro_manual_ajax_busca_nf.php?tipo=posto&q="+q, 
			success: function(data){
				var retorno = data.split("|");
				$('#os_text').attr("disabled",true);
				$('#fornecedor_distrib').val(retorno[0]);
				$('#cnpj_revenda').val(retorno[1]);
				$('#ie_consumidor').val(retorno[2]);
				$('#consumidor_cep').val(retorno[3]);
				$('#numero_consumidor').val(retorno[4]);
				$('#fone_consumidor').val(retorno[5]);
				$('#posto_cod').val(retorno[6]);
				$("#tipoDestJuridica").attr("checked",true);
				$("#tipoDestFalse").attr("checked",false);
				mudaTipoPessoa();
				
				$('#consumidor_cep').focus();
				$('#emissao').focus();
			
				
			}
		});
}
/*vvvv PESQUISA POSTO E TRANSPORTADORA - AUTOCOMPLETE vvvv*/
$().ready(function() {
	
	//MUDA DISTRIBUIDOR DE ACORDO COM O TIPO DE NF
	
	/*##TIPO NF: ENTRADA##*/
	$("#tipo_nf").change(function(){
		var tipoNf = $(this).val();

		if ($("#tipo_nf").val() == 0)
		{
			$("#posto_cod").val("4311");
			$("#posto_nome").val("TELECONTROL NETWORKING LTDA");
			pesquisaPostoAjax();
			$("#emissao").focus();
		}
		
		if ($("#tipo_nf").val() == 1)
		{
			$("#distribuidor_cod").val("4311");
			$("#emissao").focus();
		}
	})
	
	
	//PESQUISA POSTO

	$("#posto_nome").autocomplete("nf_cadastro_manual_ajax_busca_nf.php?tipo=posto", {
		minChars: 2,
		delay: 0,
		width: 350,
		max:50,
		matchContains: true,
		formatItem: function(row, i, max) {
			$("#posto_nome").focus();
			return row[0] ;
		},
		formatResult: function(row) {
			$("#posto_nome").focus();
			//alert(row[0]);
			return row[0];
		}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$('#os_text').attr("disabled",true);
		$('#fornecedor_distrib').val(data[0]);
		$('#cnpj_revenda').val(data[1]);
		$('#ie_consumidor').val(data[2]);
		$('#consumidor_cep').val(data[3]);
		$('#numero_consumidor').val(data[4]);
		$('#fone_consumidor').val(data[5]);
		$('#posto_cod').val(data[6]);
		$("#tipoDestJuridica").attr("checked",true);
		$("#tipoDestFalse").attr("checked",false);
		mudaTipoPessoa();
		
		$('#consumidor_cep').focus();
		$('#transportadora').focus();
	});
	
	//PESQUISA TRANSPORTADORA
	$("#transportadora").autocomplete("nf_cadastro_manual_ajax_busca_nf.php?tipo=transportadora", {
		minChars: 2,
		delay: 0,
		width: 350,
		max:50,
		matchContains: true,
		formatItem: function(row, i, max) {
			return row[0] ;
		},
		formatResult: function(row) {return row[0];}
			});

	$("#transportadora").result(function(event, data, formatted) {
		$(this).focus();
	});

	
	autocompletaCampos();
/*^^^^ PESQUISA POSTO E TRANSPORTADORA - AUTOCOMPLETE ^^^^*/
	
	
	$("#lupa_os").click(function(){
		var os = $("#os_codigo").val();
		$.ajax({
			url: "nf_cadastro_manual_ajax_busca_nf.php?tipo=codigo&os="+os,
			success: function(data){
				var retorno = data.split("|");
				
				//Verificar se retorno = F, ou seja, se não achou a OS.
				if (retorno[0] == "F"){
					alert("Não foi Encontrado Nenhum Resultado Para a sua Pesquisa");
				}
				
				$('#fornecedor_distrib').val(retorno[1]);
				if (retorno[2].length == 11){
						
					$('#cpf_consumidor').val(retorno[2]);
					$("#tipoDestJuridica").attr("checked",false);
					$("#tipoDestFalse").attr("checked",true);
					mudaTipoPessoa();
					$('#cpf_consumidor').focus();
					$('#consumidor_cep').focus();
					$('#complemento_consumidor').focus();
				
				}else if(retorno[2].length ==14){ 
					
					$('#cnpj_revenda').val(retorno[2]);
					$("#tipoDestJuridica").attr("checked",true);
					$("#tipoDestFalse").attr("checked",false);
					mudaTipoPessoa();
					$('#cnpj_revenda').focus();
					$('#consumidor_cep').focus();
					$('#complemento_consumidor').focus();
				}
				
				$('#consumidor_cep').val(retorno[3]);
				$('#numero_consumidor').val(retorno[4]);
				$('#complemento_consumidor').val(retorno[5]);
				$('#fone_consumidor').val(retorno[6]);
				$('#os_id').val(retorno[7]);
				$('#logradouro_consumidor').val(retorno[8]);
				$('#bairro_consumidor').val(retorno[9]);
				$('#cidade_consumidor').val(retorno[10]);
				$('#estado_consumidor').val($.trim(retorno[11]));

				$('#consumidor_cep').focus();
				$('#transportadora').focus();
			}
		});
	
	})
	


	


})



</script>

<script type="text/javascript" charset="utf-8">
/*vvvv FORMATA CAMPOS DE CPF E CNPJ vvvv*/
$(function() {
        $("#cpf_consumidor").numeric();
        $("#cnpj_revenda").numeric();
		$("#nota_fiscal").numeric();
		$("#qtde_volumes").numeric();
});

function mascara_cpf(campo, event) {


        var cpf   = campo.value.length;
        var tecla = event.keyCode ? event.keyCode : event.which ? event.which : 
                                                                event.charCode;


        if (tecla != 8 && tecla != 46) {


            if (cpf == 3 || cpf == 7) campo.value += '.';
            if (cpf == 11) campo.value += '-';


        }


}



function mascara_cnpj(campo, event) {


        var cnpj  = campo.value.length;
        var tecla = event.keyCode ? event.keyCode : event.which ? event.which : 
                                                                    event.charCode;


        if (tecla != 8 && tecla != 46) {


            if (cnpj == 2 || cnpj == 6) campo.value += '.';
            if (cnpj == 10) campo.value += '/';
            if (cnpj == 15) campo.value += '-';


        }


}

function formata_cpf_cnpj(campo, tipo) {


        var valor = campo.value;


        valor = valor.replace(".","");
        valor = valor.replace(".","");
        valor = valor.replace("-","");


        if (tipo == 2) {
            valor = valor.replace("/","");
        }


        if (valor.length == 11 && tipo == 1) {


            campo.value = valor.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/,"$1.$2.$3-$4");//CPF


        } else if (valor.length == 14 && tipo == 2) {


            campo.value = valor.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/,'$1.$2.$3/$4-$5');//CNPJ


        }


}
/*^^^^ FORMATA CAMPOS DE CPF E CNPJ ^^^^*/
</script> 

<style type="text/css">
.titulo {
	font-family: Arial;
	font-size: 10pt;
	color: #000000;
	background: #ced7e7;
}
.Carregar{
	background-color:#ffffff;
	filter: alpha(opacity=90);
	opacity: .90 ;
	width:350px;
	border-color:#cccccc;
	border:1px solid #bbbbbb;
	display:none; 

	position:absolute;
}


.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}


table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial" !important;
    color:#FFFFFF;
    text-align:center;
}

.titulo_coluna td{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:left;
	padding:0 0 0 5px;
}


.msg_erro{
    background-color:#FF0000;
    font: bold 16px Arial !important;
    color:#FFFFFF;
    text-align:center;
}

.subtitulo{
    background-color: #7092BE;
    font:bold 12px Arial;
    color: #FFFFFF;
}


.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
</style>
</head>


<body>

<? include 'menu.php';?>

<center><h1>Cadastrar NF Manual</h1></center>

<?
if ($erro_msg) {
?>
<table width="700px" align="center" cellpadding="0" cellspacing="0">
	<tr>
		<td class="msg_erro">
			<?=$erro_msg?>
		</td>
	</tr>
</table>
<?}?>
<?
if (strlen($_GET['s'])>0) {
?>
<table width="700px" align="center" >
	
	<tr>
		<td class="sucesso">
			<?="Nota Fiscal nº <u>$nota_fiscal</u> Gravada com Sucesso"?>
		</td>
	</tr>
	
</table>
<?}?>

<form name='form_nf' method="POST" action='<? echo $PHP_SELF?>'>
<table width='700px' align='center' class="formulario"  cellpadding="1" cellspacing="1">

	<tr>
		<th class="titulo_tabela" colspan='4'>Cadastro de Nota Fiscal</th>
	</tr>
	
	<tr><td>&nbsp;</td></tr>
	
	<!-- Dados da Nota Fiscal -->
	<tr><td align="center" class="subtitulo" colspan="100%">Dados da Nota Fiscal</td></tr>
	<tr>
		<td>
		
			<table class="formulario" cellpadding="0" cellspacing="2" width="600px" align="center" border="0">
				
				
			
				<tr><td>&nbsp;</td></tr>

				<tr>
					<td align="left" >Tipo NF</td>
					<td>Data Emissão</td>
					<td>Data Saída</td>
				</tr>
				
				<tr>
					<td>
						<select style="width:100%;" class='frm' name="tipo_nf" id="tipo_nf">
							<option value=""></option>
							
							<option value="0" <?php echo ($tipo_nf=='0') ? 'selected' : null;?> >Entrada</option>
							<option value="1" <?php echo ($tipo_nf=='1') ? 'selected' : null;?> >Saida</option>
						</select>
					</td>
					
					<td align='center'>
						<input  type='text' class='frm' name='emissao' id='emissao' value='<?=$emissao?>' size='11'   maxlength='10' >
					</td>
					
					<td align='center'>
						<input type='text' class='frm' name='saida' id='saida' value='<?=$saida?>' size='11' maxlength='10'>
					</td>
				</tr>
								
				<tr>
					<td style="width:25%">Nota Fiscal</td>
					<td style="width:25%">Série</td>
					<td style="width:25%">Natureza</td>
					<td style="width:25%" >CFOP</td>
				</tr>
				
				<tr>
					<td>
						<input type='text' class='frm' name='nota_fiscal' id='nota_fiscal' value='<?=$nota_fiscal?>' readonly='readonly' style='width:100%' size='8'  maxlength='8' onBlur="exibirFat('dados','','','alterar')"><div name='f2' id='f2' class='carregar'></div>
					</td>
					
					<td>
						<input type='text' class='frm' style='width:100%' name='serie' id='serie' value='<?=$serie?>' size='10'  maxlength='10' >
					</td>
					
					<td>
						<input type='text' name='natureza' class="frm" id='natureza' value='<?=$natureza?>' size='10'  maxlength='30' style="width:100%">
					</td>
					
					<td>
						<input type='text' style="width:100%" name='cfop' class="frm" id='cfop' value='<?=$cfop?>' size='8'  maxlength='8' >
					</td>
				</tr>
				
				<tr><td>&nbsp;</td></tr>
			</table>
		</td>
	</tr>
	
	<tr>
		<th class="subtitulo" align="center" colspan="100%">Busca de Destinatário</th>
	</tr>
	
	<tr><td>&nbsp;</td></tr>
	
	<tr>
		<td>
			<table cellpadding="0" cellspacing="2" width="600px" align="center" border="0">
				<!-- Dados do Destinatário -->
				
				<tr>
					<td>
						Ordem de Serviço	
					</td>
					
					<td>
						Posto						
					</td>
				</tr>
				
				<tr>
					<td width="50%">
						<input type="hidden" name="os_id" id="os_id" class="frm"value="<?=$os_id?>" />
						<input type="text" name="os_codigo" id="os_codigo" class="frm" style="width:150px" value="<?=$os_codigo?>" />
						<img src="../imagens/lupa.png" border="0" id="lupa_os" name="lupa_os" style="cursor:pointer" align="absmiddle">
					</td>
					
					<td colspan="2">
						<input type="hidden" id="distribuidor_cod" name="distribuidor_cod" value="<?=$distribuidor_cod?>">
						<input type="hidden" id="posto_cod" name="posto_cod" value="<?=$posto_cod?>">
						<input class="frm" type="text" name="posto_nome" id="posto_nome" value="<? echo $posto_nome ?>" style="width:100%" title="Digite para pesquisar pelo posto">
					</td>
				</tr>
			</table>
		</td>
	</tr>
	
	<tr><td>&nbsp;</td></tr>
	
	<tr>
		<th class="subtitulo" align="center" colspan="100%">Dados do Destinatário</th>
	</tr>
	
	<tr><td>&nbsp;</td></tr>		
	
	<tr>
		<td>	
			<table cellpadding="0" cellspacing="2" width="600px" align="center" border="0">
				<tr>
					<td colspan='4' align="left">
						<label id="nomeDestinatario">
							Nome/Razão Social
						</label>
					</td>
				</tr>
				
				<tr>
					<td nowrap colspan='4'>
				<?php
				
				//--------------------------------------------------------------------------------------------------------
				
				if(strlen($fabrica)>0) echo "<input type='hidden' name='faturamento_fabrica' value='$fabrica'>";
				if(strlen($fabrica)>0) echo "<input type='hidden' name='fabrica' value='$fabrica' id='fabrica'>";

				echo "<input type='text' class='frm' name='fornecedor_distrib' id='fornecedor_distrib' size ='64' maxlenght='64' style='width:100%' value='$fornecedor_distrib'>";
				echo "<input type='hidden'  name='fornecedor_distrib_posto' id='fornecedor_distrib_posto' value='$fornecedor_distrib_posto' >";

				if(strlen($fornecedor_distrib)>0) echo "<input type='hidden' name='faturamento_fabrica' value='$fabrica'>";
				if(strlen($fornecedor_distrib)>0) echo "<input type='hidden' name='fornecedor_distrib' value='$fornecedor_distrib'>";
				echo "</td>\n";
				echo "</tr>";
				//--------------------------------------------------------------------------------------------------------
				?>
				
				<tr>
					<td style="width:148px" rowspan=2>
						<fieldset style="width:148px">
						<legend>Tipo</legend>
							<?if (strlen($tipo_consumidor)==0) $tipo_consumidor="F"?>
							<input type="radio" name="tipo_consumidor" value="F" id="tipoDestFisica" class="tipoPessoa" <?php echo ($tipo_consumidor=='F') ? 'checked' : null;?>  /> <label for="tipoDestFisica" style="cursor:pointer">Física</label>
							<input type="radio" name="tipo_consumidor" value="J" id="tipoDestJuridica" class="tipoPessoa" <?php echo ($tipo_consumidor=='J') ? 'checked' : null;?> /> <label for="tipoDestJuridica" style="cursor:pointer">Jurídica</label>
						</fieldset>
					
					</td>
					<td style="width:25%"><label id="label_Cpf_Cnpj">CPF</label></td>
					<td style="width:25%"><label id="label_ie" <?php echo ($tipo_consumidor=='F') ? 'style="display:none"' : null;?>>IE</label></td>

				</tr>
				
				<tr>
					
					<td>
						
						<input type="text" name="cpf_consumidor" id="cpf_consumidor" onkeypress="mascara_cpf(this, event);" size="17" maxlength="14" class="frm"  value="<?php echo $cpf_consumidor ?>" onfocus="formata_cpf_cnpj(this,1)" <?php echo ($tipo_consumidor=='F') ? 'style="display:block;width:100%"' : 'style="display:none;width:100%"';?> />
						<input type="text" name="cnpj_revenda" id="cnpj_revenda" onkeypress="mascara_cnpj(this, event);" onfocus="formata_cpf_cnpj(this,2);" class="frm" size="22" maxlength="18" value="<?php echo $cnpj_revenda?>" <?php echo ($tipo_consumidor=='J') ? 'style="display:block;"' : 'style="display:none;"';?> />
					</td>
					
					<td>
						<input type="text" <?php echo ($tipo_consumidor=='F') ? 'style="display:none"' : 'style="display:block;width:100%"';?>  class="frm" name="ie_consumidor" id="ie_consumidor" value="<?=$ie_consumidor?>" maxlength="16">
					</td>
					<td>&nbsp;</td>
				</tr>
				
				<!-- LOGRADOURO -->
				<tr>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td>CEP</td>
				</tr>
				
				<tr>
					<td>
						<input name="consumidor_cep" id='consumidor_cep' value='<?echo $consumidor_cep ;?>' class="frm" type="text" size="14" maxlength="10" onblur="buscaCEP(this.value, document.form_nf.logradouro_consumidor, document.form_nf.bairro_consumidor, document.form_nf.cidade_consumidor, document.form_nf.estado_consumidor) ;">
					</td>
				</tr>
				
				<tr>
					<td colspan="3">Logradouro</td>
					<td>Número</td>
				</tr>
				
				<tr>
					<td colspan="3">
						<input class="frm" type="text" style="width:100%" name="logradouro_consumidor" id="logradouro_consumidor" value="<?=$logradouro_consumidor?>" maxlength="64">
					</td>
					
					<td>
						<input class="frm" type="text" style="width:100%" name="numero_consumidor" id="numero_consumidor" value="<?=$numero_consumidor?>" maxlength="8">
					</td>
				</tr>
				
				<tr>
					<td>Complemento</td>
					<td>Bairro</td>
					<td>Cidade</td>
					<td>UF</td>
				</tr>
				
				<tr>
					<td>
						<input type="text" class="frm" style="width:100%" name="complemento_consumidor" id="complemento_consumidor" maxlength="32" value="<?=$complemento_consumidor?>">
					</td>
					
					<td>
						<input type="text" class="frm" style="width:100%" name="bairro_consumidor" id="bairro_consumidor" maxlength="32" value="<?=$bairro_consumidor?>">
					</td>
					
					<td>
						<input type="text" class="frm" style="width:100%" name="cidade_consumidor" id="cidade_consumidor" maxlength="32" value="<?=$cidade_consumidor?>">
					</td>
					
					<td>
						<?php
						  $array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
						  "AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
						  "ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
						  "MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
						  "PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
						  "RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
						  "RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
						  "SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");
						?>
						<select name="estado_consumidor" class="frm" style="width:148px" id="estado_consumidor">
						<option value=""></option>
						<?php
							foreach ($array_estado as $k => $v) {
							echo '<option value="'.$k.'"'.($estado_consumidor == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
						}?>
						
						</select>
					</td>
				</tr>
				
				<tr>
					<td>Fone</td>
				</tr>
				
				<tr>
					<td>
						<input type="text" class="frm" style="width:100%" name="fone_consumidor" id="fone_consumidor" value="<?=$fone_consumidor?>" maxlength="10">
					</td>
				</tr>
				
			</table>
		</td>
	</tr>
	
	<tr><td>&nbsp;</td></tr>
	
	<tr>
		<th class="subtitulo" align="center" colspan="100%">Dados do Transporte</th>
	</tr>
	
	<tr><td>&nbsp;</td></tr>
	
	<tr>
		<td>
			<table cellpadding="0" cellspacing="2" width="600px" align="center" class="formulario" border="0">
				
				
				<tr>
					<td>Transportadora</td>
					<td>Tipo Frete</td>
					<td>Qtde. Volumes</td>
				</tr>
				
				<tr>
					<td>
						
						<?php #COMBO TIRADO DO PROGRAMA distrib/embarque_faturamento.php?>
						<select name='transportadora' style="width:100%" class='frm'>
							<option value='' <?php echo ( strlen($_POST['transportadora'])==0 ) ? "SELECTED" : "" ; ?> ></option>
							<?php 
							#echo "<option value='1055' SELECTED>VARIG-LOG</option>";
							?>
							<option value='1058' <?php echo ($_POST['transportadora'] == '1058') ? "SELECTED" : "" ; ?> >	PAC             </option>
							<option value='1061' <?php echo ($_POST['transportadora'] == '1061') ? "SELECTED" : "" ; ?> >	PAC (TC)        </option>
							<option value='1062' <?php echo ($_POST['transportadora'] == '1062') ? "SELECTED" : "" ; ?> >	PAC (AK)	    </option>
							<option value='1056' <?php echo ($_POST['transportadora'] == '1056') ? "SELECTED" : "" ; ?> >	SEDEX		    </option>
							<?# Adicionei e-sedex. Fabio - 05/08/2007 - Solicitado por Ronaldo?>
							<option value='1060' <?php echo ($_POST['transportadora'] == '1060') ? "SELECTED" : "" ; ?> >	E-SEDEX		    </option>
							<option value='1057' <?php echo ($_POST['transportadora'] == '1057') ? "SELECTED" : "" ; ?> >	PROPRIO	 	    </option>
							<option value='497'  <?php echo ($_POST['transportadora'] == '497')  ? "SELECTED" : "" ; ?> >	BRASPRESS       </option>
							<option value='703'  <?php echo ($_POST['transportadora'] == '703')  ? "SELECTED" : "" ; ?> >	MERCURIO		</option>
							<option value='4176' <?php echo ($_POST['transportadora'] == '4176') ? "SELECTED" : "" ; ?> >	JADLOG		    </option>
							<option value='4773' <?php echo ($_POST['transportadora'] == '4773') ? "SELECTED" : "" ; ?> >	MAIS TRANSPORTE </option>
							<?#echo "<option value='1059'>RODONAVES</option>";
							#echo "<option value='1060'>E-SEDEX</option>";
							?>
						</select>
					</td>
					<td style="width:148px">
						<select class="frm" name="tipo_frete" id="tipo_frete" style="width:100%" id="tipo_frete">
							<option value=""></option>
							<?
							?>
							<option value="1" <?php echo ($tipo_frete=='1') ? 'selected' : null;?>>Emitente</option>
							<option value="2" <?php echo ($tipo_frete=='2') ? 'selected' : null;?>>Destinatário</option>
							
						</select>
					</td>
					<td style="width:148px">
						<input class="frm" type='text' name='qtde_volumes' id='qtde_volumes' value='<?=$qtde_volumes?>' style="width:100%" maxlength='3'>
					</td>
				</tr>
				
				
			</table>
		</td>
	</tr>	

	<tr><td>&nbsp;</td></tr>
	
	<tr>
		<th class="subtitulo" align="center" colspan="100%">Observações</th>
	</tr>
	
	<tr><td>&nbsp;</td></tr>
	
	<tr>
		<td>
			<table cellpadding="0" cellspacing="2" width="600px" align="center" class="formulario" border="0">
				<tr>
					<td>
						<textarea class="frm" cols="20" rows="5" name='nf_obs' id='nf_obs' style="width:100%"><?=$nf_obs?></textarea>
					</td>
					
				</tr>
			</table>
		</td>
	</tr>
	
	<tr>
		<td>&nbsp;</td>
	</tr>
	
	<tr>
		<th class="subtitulo" align="center" colspan="100%">Itens da Nota Fiscal</th>
	</tr>


</table>

	
	
		

<table width='1000px' class="tabela" align='center' id="tbl_itens_nf" name="tbl_itens_nf">

<tr class="titulo_coluna">
	<td align='center' style="width:10px">#</td>
	<td align='center' style='width:75px;'>Referência</td>
	<td align='center' style='width:300px;'>Descrição Produto/Peça</td>
	<td align='center' style="width:70px">Qtde</td>
	<td align='center' style="width:70px">Preço</td>
	<?
	if(strlen($faturamento)>0)
echo "<td align='center'>Subtotal</td>";
	?>
	<td align='center' style="width:70px" title='Adicionada coluna de CFOP por ítem, novo padrão para NF-e'>CFOP</td>
	<td align='center' style="width:70px">Aliq. ICMS %</td>
	<td align='center' style="width:100px">Aliq. IPI %</td>
	<td align='center' style="width:100px">Base Icms</td>
	<td align='center' style="width:100px">Valor ICMS</td>
	<td align='center' style="width:100px">Base IPI</td>
	<td align='center' style="width:100px">valor IPI</td>
	<input type='hidden' name='total_qtde_item' id='total_qtde_item' value='<?=$total_qtde_item?>'>
</tr>
<tr id='0'><td colspan='100%'></td></tr>
<?

	for ($i = 0 ; $i < $total_qtde_item ; $i++) {
//INSERIR ITENS DA NOTA
		
		$referencia     = $_POST["referencia_$i"]  ;
		$descricao      = $_POST["descricao_$i"]  ;
		$qtde           = $_POST["qtde_$i"]        ;
		$preco          = $_POST["preco_$i"]       ;
		$cfop_item		= $_POST["cfop_$i"]       ;
		$aliq_icms      = $_POST["aliq_icms_$i"]   ;
		$aliq_ipi       = $_POST["aliq_ipi_$i"]    ;
		$base_icms      = $_POST["base_icms_$i"]   ;
		$valor_icms     = $_POST["valor_icms_$i"]   ;
		$base_ipi       = $_POST["base_ipi_$i"]     ;
		$valor_ipi      = $_POST["valor_ipi_$i"]    ;
		

		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
		$qtde_linha = $i+ 1;
		?>
		<tr style='font-size: 12px' bgcolor='<?=$cor?>' id='<?=$qtde_linha?>'>
		
			<td align='right' nowrap><?=($i+1)?></td>
			
			<td align='center' nowrap >
			
				<input type='text' class='frm' name='referencia_<?=$i?>' id='referencia_<?=$i?>' value='<?=$referencia?>' maxlength='20' rel='referencia' alt='descricao_<?=$i?>' style="width:90%;">

			</td>
			
			<td align='center' nowrap style="width:250px">
				
				<input type='text' class='frm' name='descricao_<?=$i?>' id='descricao_<?=$i?>' alt='referencia_<?=$i?>' value='<?=$descricao?>' style='width:90%;' maxlength='20' rel='descricao' >
			
			</td>
			
			
			<td align='center' nowrap style="width:75px;">
			
				<input class='frm qtde_prod' style="width:70px;" type='text' maxlength='10' name='qtde_<?=$i?>'	id='qtde_<?=$i?>'	value='<?=$qtde?>'	onKeyUp='calc_base_icms(<?=$i?>);' onblur="checarNumero(this);">
			
			</td>
			
			<td align='center' nowrap style="width:75px;">
				
				<input class='frm' style="width:70px;" type='text' maxlength='12' name='preco_<?=$i?>'	id='preco_<?=$i?>'	value='<?=$preco?>'	onKeyUp='calc_base_icms(<?=$i?>);' onblur="checarNumero(this);">
			
			</td>
			
			<td align='center' nowrap style="width:75px;">
			
				<input class='frm' style="width:70px;" type='text' maxlength='12' name='cfop_<?=$i?>'	id='cfop_<?=$i?>'	value='<? if ( strlen($_POST["cfop_$i"])>0 ) echo $cfop_item ;?>'>
			
			</td>
			
			<td align='center' nowrap style="width:75px;">
				
				<input class='frm' style="width:70px;" type='text' maxlength='10' name='aliq_icms_<?=$i?>'	id='aliq_icms_<?=$i?>'	value='<?=$aliq_icms?>'	onKeyUp='calc_base_icms(<?=$i?>);' onblur="checarNumero(this);">
			
			</td>
			
			<td align='center' nowrap style="width:75px;">
				
				<input class='frm' style="width:70px;" type='text' maxlength='10' name='aliq_ipi_<?=$i?>'	id='aliq_ipi_<?=$i?>'	value='<?=$aliq_ipi?>'	onKeyUp='calc_base_icms(<?=$i?>);' onblur="checarNumero(this); addTr(<?=$qtde_linha?>)">
			
			</td>
			
			<td align='center' nowrap style="width:75px;">
			
				<input class='frm' style="width:70px;border:0px solid" type='text' maxlength='10' name='base_icms_<?=$i?>'	id='base_icms_<?=$i?>'	value='<?=$base_icms?>'	onfocus='form_nf.referencia_<?=($i+1)?>.focus();' readonly=''>
			
			</td>
			
			<td align='center' nowrap style="width:75px;">
			
				<input class='frm' style="width:70px;border:0px solid" type='text' maxlength='10' name='valor_icms_<?=$i?>'	id='valor_icms_<?=$i?>'	value='<?=$valor_icms?>' onfocus='form_nf.referencia_<?=($i+1)?>.focus();' readonly=''>
			
			</td>
			
			<td align='center' nowrap style="width:75px;">
			
				<input class='frm' style="width:70px;border:0px solid" type='text' maxlength='10' name='base_ipi_<?=$i?>'	id='base_ipi_<?=$i?>'	value='<?=$base_ipi?>'	onfocus='form_nf.referencia_<?=($i+1)?>.focus();' readonly=''>
			
			</td>
			
			<td align='center' nowrap style="width:75px;">
			
				<input class='frm' style="width:70px;border:0px solid" type='text' maxlength='10' name='valor_ipi_<?=$i?>'	id='valor_ipi_<?=$i?>'	value='<?=$valor_ipi?>'	onfocus='form_nf.referencia_<?=($i+1)?>.focus();' readonly=''>
			
			</td>
			
			<input type='hidden'  name='total_item_nf_<?=$i?>' id='total_item_nf_<?=$i?>' value='0' class='total_item_nf'>
		</tr>
		<?
	}
		echo "<input type='hidden' name='total_items_nf' id='total_items_nf' value='$total_items_nf'>";

echo "</table>";




echo "</td>";
echo "</tr>";

?>

<table width="700px" align="center" class="formulario">
	<tr>
		<th class="subtitulo" align="center" colspan="100%">Dados Fiscais</th>
	</tr>
	
	<tr>
		<td>&nbsp;</td>
	</tr>
	
	
	<tr>
		<td>
			
			<table cellpadding='0' cellspacing='2' width='600px' align='center' class='formulario' border='0'>
		
				<tr>
				
					<td>Base ICMS Subst. Trib.</td>
					<td>Valor ICMS Subst. Trib.</td>
					<td>Valor Frete</td>
					
				</tr>
				
				<tr>
					<td>
						<input onKeyUp="somaValores()" style='width:100%;text-align:right' class="frm money" type='text' name='base_icms_substtituicao' id='base_icms_substtituicao' value='<?=$base_icms_substtituicao?>'  size='10'  maxlength='12' title='Colocar neste campo o valor Base de ICMS de  Substituição Tributária.'></td>
					</td>
					<td nowrap>
						<input onKeyUp="somaValores()" style='width:100%;text-align:right' class="frm money" type='text' name='valor_icms_substtituicao' id='valor_icms_substtituicao' value='<?= $valor_icms_substtituicao ?>'  size='10'  maxlength='12' title='Colocar neste campo o valor ICMS de Substituição Tributária'></td>
					</td>
					<td><input onKeyUp="somaValores()" type="text" class="frm money" style='width:100%;text-align:right' name="valor_frete" id="valor_frete" maxlength="12"  value="<?=$valor_frete?>" /></td>
					
				</tr>
				
				<tr>
					<td>Valor Desconto</td>
					<td>Outros Valores</td>
					<td>Seguro</td>
				</tr>
				
				<tr>
					<td>
						<input onKeyUp="somaValores()" type="text" name="valor_desconto" id="valor_desconto" style='width:100%;text-align:right' class="frm money" value="<?=$valor_desconto?>" />
					</td>
					
					<td>
						<input onKeyUp="somaValores()" type="text" name="outros_valores" id="outros_valores" style='width:100%;text-align:right' class="frm money" value="<?=$outros_valores?>" />
					</td>
					
					<td>
						<input onKeyUp="somaValores()" type="text" name="valor_seguro" id="valor_seguro" style='width:100%;text-align:right' class="frm money" value="<?=$valor_seguro?>" />
					</td>
				</tr>
				
				<tr><td>&nbsp;</td></tr>
				
				<tr height="50px">
					<?if (strlen($total_nota)==0){
						$total_nota=0;
					}
					?>
					<input type="hidden" value="<?=$total_nota?>" id="total_nota" name="total_nota">
					<td ><label style="font: bold 11px Arial;color:#000000">TOTAL DA NOTA:</label></td>
					
					<td colspan="2"><label style="font: bold 11px Arial;color:#000000">R$ <span id="total_texto"><?=$total_nota?></span></label></td>
				</tr>
			</table>
		
		</td>
	</tr>
	
	<tr>
		<td>&nbsp;</td>
	</tr>
	
<?
echo "<tr>";
echo "<td colspan='5' align='center'>";
echo "<input type='hidden' name='qtde_item' value='$i'>";
echo "<input type='hidden' name='faturamento' value='$faturamento'>";
echo "</td>";
echo "</tr>";


	$desc_bt="Gravar";	

echo "<tr>";
echo "<td colspan='12' align='center'>";

	echo "<input type='hidden' name='btn_acao' value=''>";
	echo "<input type='button' name='btn_grava' value='Gravar' onclick='javascript: document.form_nf.btn_acao.value=\"Gravar\"; document.form_nf.submit()'>";

echo "</td>";
echo "</tr>";
?>
	
</table>
<?
echo "</table>\n";
echo "</form>";
?>



<? #include "rodape.php"; ?>

</body>
</html>
