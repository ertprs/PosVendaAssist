<?php

//OBS: ESTE ARQUIVO UTILIZA AJAX: form_nf_ret_ajax.php

include 'dbconfig.php';
// $dbnome = 'teste';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
require 'ncms.php';

$fabrica = 10;
unset($nota_fiscal) ;
$faturamento	= $_POST["faturamento"];
if(strlen($faturamento)==0)
	$faturamento = $_GET["faturamento"];

$btn_acao= $_POST["btn_acao"];

$total_qtde_item= (strlen($_POST["total_qtde_item"]) > 0) ? $_POST["total_qtde_item"] : 5;

if(strlen($btn_acao)==0)
	$btn_acao = $_GET["btn_acao"];

$btn_acao2= $_POST["btn_acao2"];

/**
 * @since 2012-11-13 - alterações no CFOP e Natureza da Operação
 */
$naturezas = array (
					"ENTRADA DE MERCADORIA OU BEM RECEBIDO P/ CONSERTO OU REPARO"  => array (1915, 2915),
					"ENTRADA DE MATERIAIS DE USO E CONSUMO"                        => array (1556),
					"VENDA DE MERCADORIA ADQUIRIDA OU RECEBIDA DE TERCEIROS"       => array (5102, 6102),
					"VENDA DE MERCADORIA ADQUIRIDA OU RECEBIDA DE TERCEIROS C/ ST" => array (5403, 6403),
					"VENDA DE MERCADORIA SUJ. AO REG. DE SUBST. TRIBUT."           => array (5404, 6404),
					"VENDA DE MERCADORIA REMETIDA EM CONSIGNAÇÃO"                  => array (5114, 6114),
					"VENDA DE MERCADORIA RECEBIDA EM CONSIGNAÇÃO"                  => array (5115, 6115),
					"RETORNO DE BEM RECEB. POR CONTA DE CONTR. DE COMODATO"        => array (5909, 6909),
					"RETORNO DE MERCADORIA OU BEM PARA CONSERTO OU REPARO"         => array (5916, 6916),
					"REMESSA EM CONSIGNAÇÃO MERCANTIL"                             => array (5917, 6917),
					"OUTRAS SAÍDAS - REMESSA EM GARANTIA"                          => array (5949, 6949),
					"DISTRIBUIÇÃO DE CESTA BÁSICA"                                 => array (5949, 6949),
					"DEVOLUÇÃO DE VENDA MERC.ADQ.RECEB.TERCEIROS"                  => array (5403),
					"DEVOLUÇÃO DE REMESSA EM GARANTIA"                             => array (5949),
					"DEV. DE COMPRA P/ COM. EM OP. COM MERC. SUJ. AO REG. DE ST"   => array (5411, 6411),
			);

if ($btn_acao2 == "Consultar") {
	$nota_fiscal              = trim($_POST['nota_fiscal']);
	$sqlf= "SELECT	faturamento                     ,
		TO_CHAR(emissao,'DD/MM/YYYY') as emissao,
		conferencia                             ,
		TO_CHAR(saida,'DD/MM/YYYY') as saida    ,
		tbl_posto.nome as posto_nome            ,
		posto                                   ,
		distribuidor                            ,
		cfop                                    ,
		nota_fiscal                             ,
		serie                                   ,
		transp                                  ,
		qtde_volume                             ,
		natureza                                ,
		obs                                     ,
		total_nota                              ,
		tipo_nf                                 ,
		base_icms_substtituicao                 ,
		valor_icms_substtituicao                ,
		valor_seguro                            ,
		valor_desconto                          ,
		valor_outros                            ,
		valor_frete                             ,
		tipo_frete                              ,
		chave_nfe                               ,
		recibo_nfe                              ,
	       	status_nfe
		FROM tbl_faturamento
		JOIN tbl_faturamento_destinatario using(faturamento)
		LEFT JOIN tbl_posto using(posto)
		WHERE nota_fiscal like '%$nota_fiscal%'
		AND  fabrica in ($telecontrol_distrib)
		LIMIT 1";
	$resf = pg_query ($con,$sqlf);
	if(pg_num_rows($resf)>0){
		$faturamento      = trim(pg_fetch_result($resf,0,faturamento));
		$chave_nfe        = trim(pg_fetch_result($resf,0,chave_nfe));
		$recibo_nfe       = trim(pg_fetch_result($resf,0,recibo_nfe));
		$status_nfe       = trim(pg_fetch_result($resf,0,status_nfe));

		$emissao	      = trim(pg_fetch_result($resf,0,emissao));
		$repSaida         = $emissao;
		$saida	          = trim(pg_fetch_result($resf,0,saida));
		$posto_cod        = trim(pg_fetch_result($resf,0,posto));
		$posto_nome       = trim(pg_fetch_result($resf,0,posto_nome));
		$distribuidor_cod = trim(pg_fetch_result($resf,0,distribuidor));
		$cfop             = trim(pg_fetch_result($resf,0,cfop));
		$nota_fiscal      = trim(pg_fetch_result($resf,0,nota_fiscal));
		$serie            = trim(pg_fetch_result($resf,0,serie));
		$transp           = trim(pg_fetch_result($resf,0,transp));
		$qtde_volumes     = trim(pg_fetch_result($resf,0,qtde_volume));
		$natureza         = trim(pg_fetch_result($resf,0,natureza));
		$nf_obs           = trim(pg_fetch_result($resf,0,obs));
		$total_nota       =	trim(pg_fetch_result($resf,0,total_nota));
		$tipo_nf          = trim(pg_fetch_result($resf,0,tipo_nf));
		$base_icms_substtituicao = trim(pg_fetch_result($resf,0,base_icms_substtituicao));
		$valor_icms_substtituicao = trim(pg_fetch_result($resf,0,valor_icms_substtituicao));
		$valor_seguro             = trim(pg_fetch_result($resf,0,valor_seguro));
		$valor_desconto           = trim(pg_fetch_result($resf,0,valor_desconto));
		$valor_outros             = trim(pg_fetch_result($resf,0,valor_outros));
		$valor_frete              = trim(pg_fetch_result($resf,0,valor_frete));
		$tipo_frete               = trim(pg_fetch_result($resf,0,tipo_frete));
		$sqlff = "SELECT faturamento,nome,cpf_cnpj,ie,logradouro,numero,bairro,complemento,cep,municipio,uf,fone, email
				FROM tbl_faturamento_destinatario
				WHERE faturamento = $faturamento";
		$resff = pg_query ($con,$sqlff);
		if(pg_num_rows($resff)>0){
			$fornecedor_distrib         = trim(pg_fetch_result($resff,0,nome));
			$cpf_consumidor             = trim(pg_fetch_result($resff,0,cpf_cnpj));
			$ie_consumidor              = trim(pg_fetch_result($resff,0,ie));
			$logradouro_consumidor      = trim(pg_fetch_result($resff,0,logradouro));
			$numero_consumidor          = trim(pg_fetch_result($resff,0,numero));
			$bairro_consumidor          = trim(pg_fetch_result($resff,0,bairro));
			$complemento_consumidor     = trim(pg_fetch_result($resff,0,complemento));
			$consumidor_cep             = trim(pg_fetch_result($resff,0,cep));
			$cidade_consumidor          =  trim(pg_fetch_result($resff,0,municipio));
			$estado_consumidor          = trim(pg_fetch_result($resff,0,uf));
			$fone_consumidor            = trim(pg_fetch_result($resff,0,fone));
			$email_consumidor = pg_fetch_result($resff, 0, 'email');
		}
		$sqlfff = "SELECT situacao_tributaria,peca       ,qtde       ,preco      ,cfop       ,aliq_icms  ,
							aliq_ipi   ,base_icms  ,valor_icms ,base_ipi   ,valor_ipi,os
				FROM tbl_faturamento_item
				WHERE faturamento = $faturamento";
		$resfff = pg_query ($con,$sqlfff);
		$total_qtde_item = @pg_num_rows($resfff);
		if($total_qtde_item>0){
			for($i=0; $i< $total_qtde_item; $i++){
				$_POST["qtde_$i"] = trim(pg_fetch_result($resfff,$i,qtde));
				$_POST["preco_$i"] = trim(pg_fetch_result($resfff,$i,preco));
				$_POST["cfop_$i"] = trim(pg_fetch_result($resfff,$i,cfop));
				$_POST["aliq_icms_$i"] = trim(pg_fetch_result($resfff,$i,aliq_icms));
				$_POST["aliq_ipi_$i"] = trim(pg_fetch_result($resfff,$i,aliq_ipi));
				$_POST["base_icms_$i"] = trim(pg_fetch_result($resfff,$i,base_icms));
				$_POST["valor_icms_$i"] = trim(pg_fetch_result($resfff,$i,valor_icms));
				$_POST["base_ipi_$i"] = trim(pg_fetch_result($resfff,$i,base_ipi));
				$_POST["valor_ipi_$i"] = trim(pg_fetch_result($resfff,$i,valor_ipi));

				$osx = trim(pg_fetch_result($resfff,$i,os));
				if(strlen($osx)>0){
					$os_id = $osx;
				}
				$peca       = trim(pg_fetch_result($resfff,$i,peca));
				$sqlx = "SELECT  peca, referencia, descricao
						FROM   tbl_peca
						WHERE  peca = $peca";
				$resx = pg_query ($con,$sqlx);
				if(pg_num_rows($resx)>0){
					$_POST["referencia_$i"] = trim(pg_fetch_result($resx,0,referencia));
					$_POST["descricao_$i"]  = trim(pg_fetch_result($resx,0,descricao));
				}else{
					$sqlxx = "SELECT  produto   ,referencia,descricao ,ipi       ,origem    ,fabrica
							FROM   tbl_produto
							WHERE   produto = $peca";
					$resxx = pg_query ($con,$sqlxx);
					if(pg_num_rows($resxx)>0){
						$_POST["referencia_$i"] = trim(pg_fetch_result($resxx,0,referencia));
						$_POST["descricao_$i"]  = trim(pg_fetch_result($resxx,0,descricao));
						$_POST["aliq_ipi_$i"]   = trim(pg_fetch_result($resxx,0,ipi));
					}
				}
			}
		}
	}else{
		$nota_fiscal = 'Ñ Encontrada';
	}
}

/*
	$valor_desconto           = trim($_POST['valor_desconto'])               ;
	$outros_valores           = trim($_POST['outros_valores'])               ;
	$valor_seguro          	  = trim($_POST['valor_seguro'])                 ;
	$nf_obs               	  = trim($_POST['nf_obs'])                       ;
	$tipo_frete               = $_POST['tipo_frete']                         ;
  */
if ($btn_acao == "Gravar") {
	$empresa = 'ACAC';

	if($_POST['valida_referencia'] != $_POST['referencia_0']){
		//HD 728837
		//$erro_msg = "Nota fiscal manual não pode alterar o produto/peça!";
	}
	$tipo_nf    			  = trim($_POST['tipo_nf'])						 ;
	$emissao				  = trim($_POST["emissao"])    		  			 ;
	$saida					  = trim($_POST['saida'])      		  			 ;
	$nota_fiscal			  = substr($_POST['nota_fiscal'],0,30)           ;
	$serie					  = substr($_POST['serie'],0,3)                  ;
	$natureza				  = preg_replace('/#.*/', '', $_POST['natureza']);
	$cfop					  = $_POST['cfop'];
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
	$total_nota				  = trim($_POST['total_nota']);
	$email_consumidor = trim($_POST['email_consumidor']);

	if (!empty($_POST['empresa'])) {
		$empresa = $_POST['empresa'];
	}

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
		$sql = "SELECT faturamento ,chave_nfe,status_nfe ,recibo_nfe
		FROM tbl_faturamento
		WHERE fabrica     = $fabrica
		AND   posto       = $login_posto
		AND   nota_fiscal = ' $nota_fiscal'";
		$res = pg_query ($con,$sql);

		if(pg_num_rows($res)>0){
			$faturamento = trim(pg_fetch_result($res,0,faturamento));
			$chave_nfe = trim(pg_fetch_result($res,0,chave_nfe));
			$status_nfe = trim(pg_fetch_result($res,0,status_nfe));
			$recibo_nfe = trim(pg_fetch_result($res,0,recibo_nfe));
			if(strlen($chave_nfe) > 0 or strlen($status_nfe)>0 or strlen($recibo_nfe)>0){
				$erro_msg = "Nota fiscal ' $nota_fiscal ' Já foi emitida e não pode ser alterada!";
			}
		}
	}

	if ($empresa <> 'ACAC' and $empresa <> 'TELEC') {
		$empresa = 'ACAC';
	}

	if ($empresa == 'ACAC' and false === strpos($nf_obs, "Documento emitido por empresa optante pelo Simples Nacional")) {
		$nf_obs.= " Documento emitido por empresa optante pelo Simples Nacional.";
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
		else {
			if (!array_key_exists($natureza, $naturezas)) {
				throw new Exception('Natureza da Operação inválida.');
			}
			elseif (!in_array($cfop, $naturezas[$natureza])) {
				throw new Exception('CFOP não corresponde à Natureza da Operação.');
			}
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


	$sql = "SELECT trim(TO_CHAR(MAX (nota_fiscal::integer) + 1, '000000')) AS nota_fiscal FROM tbl_faturamento WHERE distribuidor = 4311 AND empresa = '$empresa'";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_last_error($con);
	$nota_fiscal_new = pg_fetch_result($res,0,0);

	if (strlen ($nota_fiscal_new) == 0) {
		$nota_fiscal_new = "000000";
	}

	$re_match_YMD	= '/(\d{4})\W?(\d{2})\W?(\d{2})/';
	$re_match_DMY	= '/(\d{2})\W?(\d{2})\W?(\d{4})/';
	$re_format_YMD	= '$3-$2-$1';
	$re_format_DMY	= '$3/$2/$1';

	$repEmissao = $emissao;
	$repSaida = $saida;
	$repSaida   = preg_replace($re_match_DMY, $re_format_YMD, $saida);
	$repEmissao = preg_replace($re_match_DMY, $re_format_YMD, $emissao);

	$altera_cfop = false;
	$cfop_alterado = false;

	if(strlen($erro_msg) == 0){
		$res = pg_query ($con,"BEGIN TRANSACTION");
		if(strlen($faturamento)>0){
			$sql = "UPDATE tbl_faturamento  set fabrica    = $faturamento_fabrica    ,
			emissao        = '$repEmissao'           ,
			conferencia    = CURRENT_TIMESTAMP       ,
			saida          = '$repSaida'             ,";
			if ($tipo_nf == 1){
				if (strlen($posto_cod) > 0){
					$sql .= "
					posto         = $posto_cod ,
					distribuidor  = $distribuidor_cod ,
					";
				}else{
					$sql .= "
					distribuidor  = $distribuidor_cod ,
					";
				}
			}else if($tipo_nf==0){
				$sql .= "
				posto = $posto_cod ,";
			}
			$sql .= "
			cfop            = '$cfop'             ,
			nota_fiscal     = '$nota_fiscal'      ,
			serie           = '$serie'            ,
			transp          = '$transp'           ,
			qtde_volume     = $qtde_volumes       ,
			natureza        = '$natureza'         ,
			obs             = '$nf_obs'           ,
			total_nota      = $total_nota         ,
			tipo_nf         = $tipo_nf            ,
			base_icms_substtituicao = $base_icms_substtituicao_formated,
			valor_icms_substtituicao = $valor_icms_substtituicao_formated,
			valor_seguro    = $valor_seguro_formated,
			valor_desconto  = $valor_desconto_formated,
			valor_outros    = $outros_valores_formated,
			valor_frete     = $valor_frete_formated,
			tipo_frete      = '$tipo_frete'
			WHERE faturamento  = $faturamento;";
		}else{
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
					posto, distribuidor,";
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
				tipo_frete,
				empresa

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
					$posto_cod, $distribuidor_cod,";
				}

			$sql .= "
			'$cfop'             ,
			'$nota_fiscal_new'      ,
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
			'$tipo_frete',
			'$empresa')";
		}
		//echo nl2br($sql);
		//echo '<-----><br/><Br/>';

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
			if(strlen($faturamento)>0){
				$sql = "UPDATE tbl_faturamento_destinatario set
					nome        = '$fornecedor_distrib',";
						if ($tipo_consumidor == "F"){
							$sql .="cpf_cnpj = '$cpf_consumidor_formated',";
						} else if($tipo_consumidor == "J"){
							$sql .="cpf_cnpj = '$cnpj_revenda_formated',";
						}
				$sql .="
					ie          = '$ie_consumidor_formated',
					logradouro  = '$logradouro_consumidor',
					numero      = '$numero_consumidor',
					bairro      = '$bairro_consumidor',
					complemento = '$complemento_consumidor',
					cep         = '$consumidor_cep_formated',
					municipio   = '$cidade_consumidor',
					uf          = '$estado_consumidor',
					fone        = '$fone_consumidor_formated',
                    email = '$email_consumidor'
					WHERE      faturamento = $faturamento;";
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
					fone,
                    email
					) VALUES (
						$faturamento,
						'$fornecedor_distrib',
						";
						if ($tipo_consumidor == "F"){
							$sql .="'$cpf_consumidor_formated',";
						} else if($tipo_consumidor == "J"){
							$sql .="'$cnpj_revenda_formated',";
						}else{
							$sql .= " '',";
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
					'$fone_consumidor_formated',
                    '$email_consumidor'
					)
				";
			}
			$res = pg_query ($con,$sql);
			if (!is_resource($res)) $erro_msg.= "<br> Erro ao INSERIR nova NF.";
			$t_item = false;
			if($valida_nf_manual == '1' and $total_qtde_item > 1){
				$erro_msg = "A Nota Fiscal Manual somente pode conter um item";
			}
			for($i=0; $i< $total_qtde_item; $i++){
				$erro_item  = "" ;
				$referencia = $_POST["referencia_$i"];
				$descricao  = $_POST["descricao_$i"];
				$qtde       = $_POST["qtde_$i"];
				$preco      = str_replace(",", ".", $_POST["preco_$i"]);
                
                $frete = str_replace(",", ".", $_POST["frete_$i"]);
                $seguro = str_replace(",", ".", $_POST["seguro_$i"]);
                
				$cfop_item  = $_POST["cfop_$i"];
				$pedido     = $_POST["pedido_$i"];
                
				$aliq_icms  = str_replace(",", ".", $_POST["aliq_icms_$i"]);
				$aliq_ipi   = str_replace(",", ".", $_POST["aliq_ipi_$i"]);
				$base_icms  = str_replace(",", ".", $_POST["base_icms_$i"]);
				$base_ipi   = str_replace(",", ".", $_POST["base_ipi_$i"]);
				$valor_ipi  = str_replace(",", ".", $_POST["valor_ipi_$i"]);
				$valor_icms = str_replace(",", ".", $_POST["valor_icms_$i"]);
				
				$mva = str_replace(",", ".", $_POST["mva_$i"]);
                $perc_red = str_replace(",", ".", $_POST["perc_red_$i"]);
                $st = $_POST["st_$i"];
                $aliq_icms_st = str_replace(",", ".", $_POST["aliq_icms_st_$i"]);
                $base_st = str_replace(",", ".", $_POST["base_st_$i"]);
                $valor_icms_st = str_replace(",", ".", $_POST["valor_icms_st_$i"]);
                
				$os_item    = $_POST["os_item_$i"];
                
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
					$sql = "SELECT peca,
								referencia,
								descricao,
								ncm
							FROM   tbl_peca
							WHERE  fabrica in ($telecontrol_distrib)
							AND    referencia = '$referencia';";

					$res = pg_query ($con,$sql);
					if(pg_num_rows($res)>0){
						$peca       = trim(pg_fetch_result($res,0,peca));
						$referencia = trim(pg_fetch_result($res,0,referencia));
						$descricao  = trim(pg_fetch_result($res,0,descricao));
						$ncm = trim(pg_fetch_result($res, 0, 'ncm'));
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
								WHERE  fabrica in ($telecontrol_distrib)
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

					if ($empresa == "ACAC") {

						if (empty($ncm)) {
							$erro_item.= 'Peça ' . $referencia . ' sem NCM cadastrado.';
						}

						if (in_array($ncm, $ncms_st)) {
							$situacao_tributaria = '60';
							$altera_cfop = true;
						}
						elseif (in_array($ncm, $ncms_trib)) {
							$situacao_tributaria = '00';
						}

						# Acacia é simples nacional e não tem st
						$situacao_tributaria = '';
						
						if (!empty($st)) {
                            $situacao_tributaria = '02';
						}
						
						$base_icms = 0;
						$valor_icms = 0;
					} else {
                        $situacao_tributaria = '00';
                    }

					if(strlen($erro_item)==0){
						$faturamento_item = "";

						if (true === $altera_cfop and in_array($cfop, array(5102, 6102))) {
							switch ($cfop[0]) {
								case '5':
									$cfop_item = '5403';
									break;
								case '6':
									$cfop_item = '6404';
									break;
								default:
									$cfop_item = $cfop;
									break;
							}

							$cfop_alterado = true;
							$cfop_st = $cfop_item;
						}

						if(!empty($os_item)){
							$sqlfi = "SELECT faturamento_item FROM
										tbl_faturamento_item
										WHERE faturamento = $faturamento
										AND   peca        = $peca
										AND os_item = $os_item";
							$resfi = pg_exec($con,$sqlfi);
						}
							if(pg_num_rows($resfi)>0){
								$faturamento_item = trim(pg_fetch_result($resfi,0,faturamento_item));
							}else{
								if(!empty($os_cod)){
									$sqlfi = "SELECT faturamento_item FROM
												tbl_faturamento_item
												WHERE faturamento = $faturamento
												AND   peca        = $peca
												AND os = $os_cod";
									$resfi = pg_exec($con,$sqlfi);
									if(pg_num_rows($resfi)>0){
										$faturamento_item = trim(pg_fetch_result($resfi,0,faturamento_item));
									}
								}
							}
	if(strlen($faturamento_item)>0){
							$sql = "UPDATE tbl_faturamento_item set
									qtde = $qtde,
									preco = $preco,
									cfop = '$cfop',
									aliq_icms = $aliq_icms,
									aliq_ipi  = $aliq_ipi,
									base_icms = $base_icms,
									valor_icms = $valor_icms,
									base_ipi   = $base_ipi,
									valor_ipi  = $valor_ipi,
									os         = $os_cod
									WHERE faturamento_item = $faturamento_item;";
						}else{
							$sql=  "INSERT INTO tbl_faturamento_item (
                                                faturamento,
                                                situacao_tributaria,
                                                peca,
                                                qtde,
                                                preco,
                                                frete_produto,
                                                seguro,
                                                cfop,
                                                aliq_icms,
                                                aliq_ipi,
                                                mva,
                                                percent_reducao_bc,
                                                aliquota_icms_st,
                                                base_st,
                                                valor_icms_st,
                                                base_icms,
                                                valor_icms,
                                                base_ipi,
                                                valor_ipi,
                                                os
                                            ) VALUES (
                                                $faturamento,
                                                '$situacao_tributaria',
                                                $peca,
                                                $qtde,
                                                $preco,
                                                $frete,
                                                $seguro,
                                                '$cfop',
                                                $aliq_icms,
                                                $aliq_ipi,
                                                $mva,
                                                $perc_red,
                                                $aliq_icms_st,
                                                $base_st,
                                                $valor_icms_st,
                                                $base_icms,
                                                $valor_icms,
                                                $base_ipi,
                                                $valor_ipi,
                                                $os_cod
                                            );";
						}
						//echo nl2br($sql);
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

			if (true === $cfop_alterado) {
				$sql = "UPDATE tbl_faturamento SET cfop = $cfop_st WHERE faturamento = $faturamento";
				$res = pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage ($con);
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

				header('Location: nf_cadastro_manual_new.php?s=1');

			} else {
				$res = pg_query ($con,"ROLLBACK TRANSACTION");
				$faturamento = "";
			}

		}

	}

}//FIM BTN: GRAVAR
$title = "Cadastro de Nota Fiscal";
?>
<title><?php echo $title ?></title>
<head>

<script type="text/javascript" src="../js/jquery.js"></script>
<link type="text/css" rel="stylesheet" href="css/css.css">
<script language='javascript' src='../ajax_cep.js'></script>
<script language='javascript' src='../ajax.js'></script>

<?php include "javascript_calendario.php";?>
<script type='text/javascript' src='../js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript" charset="utf-8" src="../js/jquery.alphanumeric.js"></script>
<script type="text/javascript" src="../admin/js/jquery.maskmoney.js"></script>
<!--<script type='text/javascript' src='js/jquery.numeric.js'></script>-->
<script type="text/javascript" src="../plugins/price_format/jquery.price_format.1.7.min.js"></script>
<script type="text/javascript" src="../plugins/price_format/accounting.js"></script>
<script type="text/javascript" src="../plugins/price_format/config.js"></script>

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
    
    $(".cfop").numeric();
    $(".qtde_prod").numeric();
    $("#valor_frete").numeric();

	$("#consumidor_cep").blur(function(){
		var formCep = $("#consumidor_cep").val();
		var valOs = $("#os_codigo").val();

		$.post("service_cep.php", {formCep:formCep}, function(data){
			//var jsonData = eval(data);
			if(valOs == ""){
				if(data.logradouro != ""){
					$('#logradouro_consumidor').val(data.logradouro);
					$('#bairro_consumidor').val(data.bairro);
					$('#cidade_consumidor').val(data.cidade);
					$('#estado_consumidor').val($.trim(data.estado));
				}else{
					alert("Não foi encontrado informações neste CEP");
				}
			}
		}, "json");
	});

	$('.qtde_prod').each(function() {
		if ( $(this).val().length == 0 || $(this).val() <= 0 ) { return; }
		var _id = $(this).attr('id');
		if ( _id != undefined ) {
			var _tmp = _id.split('_');
			var _i   = _id[1];
			calc_base_icms();
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

function atualizaCFOP(sNatCfop) {
	var aNatCfop = sNatCfop.split('#');
	var natureza = aNatCfop[0];
	var cfop     = aNatCfop[1];

	if (!cfop) {
		document.getElementById('cfop').value = '';
		return true;
	}

	var search  = cfop[0];
	var replace = "0";

	if (search == "5") {
		replace = "6";
	}
	else if (search == "1") {
		replace = "2";
	}

	var uf = document.getElementById('estado_consumidor').value;

	if (uf && uf != "SP") {
		cfop = cfop.replace(search, replace);
	}

	document.getElementById('cfop').value = cfop;

	var i = 0;
	while (true) {
		var itemCfop = document.getElementById('cfop_' + i).value;

		if (itemCfop) {
			document.getElementById('cfop_' + i).value = cfop;
		} else {
			break;
		}
		i++;
	}

	return true;

}

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

function calcula_frete_produto() {
    var total_itens = $('input[name^=referencia_]').length;
    var valor_frete = parseFloat($("#valor_frete").val().toString().replace(",", "."));
    var total_produtos = parseFloat($("#total_produtos").val().toString().replace(",", "."));
    
    for (var i = 0; i < total_itens; i++) {
        var qtde = $("#qtde_" + i).val();
        var preco = $("#preco_" + i).val();
        
        if (preco) {
            preco = preco.toString().replace(",", ".");
        }
        
        var frete = 0;

        if ((valor_frete > 0) && (total_produtos > 0) && (qtde > 0) && (preco > 0)) {
            var calc_frete = ((qtde * preco) / total_produtos) * valor_frete;
            frete = calc_frete.toFixed(2);

            $("#frete_" + i).val(frete.toString().replace(".", ","));
        } else if (valor_frete == 0) {
            if ($("#frete_" + i).val()) {
                $("#frete_" + i).val('0,00');
            }
        }
    }
}

function calc_base_icms(){
	
	var total_itens = $('input[name^=referencia_]').length;
    
    for (var i = 0; i < total_itens; i++) {
        var qtde = $("#qtde_" + i).val();
        var preco = $("#preco_" + i).val();
        var frete = $("#frete_" + i).val();
        var seguro = $("#seguro_" + i).val();
        var aliq_icms = $("#aliq_icms_" + i).val();
        
        if (preco && frete && seguro && aliq_icms) {
            preco = parseFloat(preco.toString().replace(",", "."));
            calcula_frete_produto();
            frete = parseFloat(frete.toString().replace(",", "."));
            seguro = parseFloat(seguro.toString().replace(",", "."));
            aliq_icms = parseFloat(aliq_icms.toString().replace(",", "."));
            
            var icms = aliq_icms / 100;
            var valor_icms = (((qtde * preco) + frete) + seguro) * icms;

            if (!isNaN(valor_icms)) {
                valor_icms = valor_icms.toFixed(2);
                $("#valor_icms_" + i).val(valor_icms.toString().replace(".", ","));
            }
        }
    }
    
    somaValores();
}

function calcImpostos(){
    calc_base_icms();
    calculaValorICMSST();
    somaValores();
}

function somaValores(){
    
    var total_itens = $('input[name^=referencia_]').length;
    var total_produtos = 0;
    var total_nota = 0;
    var icms_st = 0;
    var tipo_frete = $("#tipo_frete").val();
    var seguro = parseFloat($("#valor_seguro").val().toString().replace(",", "."));
    
    if (isNaN(seguro)) {
        seguro = 0;
    }

    for (var i = 0; i < total_itens; i++) {
        var qtde = $("#qtde_" + i).val();
        var preco = $("#preco_" + i).val();
        
        if (preco) {
            preco = parseFloat(preco.toString().replace(",", "."));
        }
        
        var valor_icms_st = $("#valor_icms_st_" + i).val();
        
        if (valor_icms_st) {
            valor_icms_st = parseFloat(valor_icms_st.toString().replace(",", "."));
        }

        if (qtde && preco) {
            total_produtos += (qtde * preco);
            total_produtos = parseFloat(total_produtos);
        }
        
        if (valor_icms_st) {
            icms_st += valor_icms_st;
            icms_st = parseFloat(icms_st);
        }

    }
    
    total_nota = total_produtos + icms_st + seguro;
    
    if (tipo_frete == "2") {
        var frete = parseFloat($("#valor_frete").val().toString().replace(",", "."));
        
        if (!isNaN(frete)) {
            total_nota += frete;
            total_nota = parseFloat(total_nota);
        }
    }
    
    total_produtos = total_produtos.toFixed(2);
    total_nota = total_nota.toFixed(2);
    
    $("#total_nota").val(total_nota);
    $("#total_produtos").val(total_produtos);
    $("#total_texto").html(total_nota.toString().replace(".",","));
}

function calculaBaseST() {
    var total_itens = $('input[name^=referencia_]').length;

    for (var i = 0; i < total_itens; i++) {
        
        var qtde = $("#qtde_" + i).val();
        var preco = $("#preco_" + i).val();
        calcula_frete_produto();
        var frete = $("#frete_" + i).val();
        var seguro = $("#seguro_" + i).val();
        var mva = $("#mva_" + i).val();
    
        if (!qtde || !preco || !frete || !mva) {
            return;
        }
        
        if (!seguro) {
            seguro = 0;
        }
        
        qtde = parseFloat(qtde);
        preco = parseFloat(preco.toString().replace(",", "."));
        frete = parseFloat(frete.toString().replace(",", "."));
        seguro = parseFloat(seguro.toString().replace(",", "."));
        mva = parseFloat(mva.toString().replace(",", "."));
        mva = mva / 100;
        
        var valor = qtde * preco;
        var base_st = ((valor + frete + seguro) * mva) + valor + frete + seguro;
        
        $("#base_st_" + i).val(base_st.toFixed(2).toString().replace(".",","));
    }
    
    somaValores();
}

function calculaValorICMSST() {
    
    var total_itens = $('input[name^=referencia_]').length;
    calculaBaseST();

    for (var i = 0; i < total_itens; i++) {
        var base_st = $("#base_st_" + i).val();
        var aliq_icms_st = $("#aliq_icms_st_" + i).val();
        var icms = $("#valor_icms_" + i).val();
        
        if (!base_st || !aliq_icms_st || !icms) {
            return false;
        }
        
        base_st = parseFloat(base_st.toString().replace(",", "."));
        aliq_icms_st = parseFloat(aliq_icms_st.toString().replace(",", "."));
        icms = parseFloat(icms.toString().replace(",", "."));
        
        var icms_st = (base_st * (aliq_icms_st / 100)) - icms;
        
        $("#valor_icms_st_" + i).val(icms_st.toFixed(2).toString().replace(".",","));
    }
    
    somaValores();
}

function adicionaItens() {
    var total_itens = parseInt($('input[name^=referencia_]').length);
    var adicionar = parseInt($("#adicionar_itens").val());

    total_itens = total_itens - 1;

    var tpl = $("#tpl_itens").html();
    var html = '';
    var ntr = '';
    var id = 0;
    var ii = 0;

    for (var i = 1; i <= adicionar; i++) {
        ii = i + (total_itens - 1);
        id = ii + 1;
        var bgcolor = '';

        if ((total_itens % 2) == 0) {
            if ((i % 2) != 0) {
                bgcolor = ' background-color: #98C7D3;';
            } else {
                bgcolor = '';
            }
        } else {
            if ((i % 2) == 0) {
                bgcolor = ' background-color: #98C7D3;';
            } else {
                bgcolor = '';
            }
        }

        ntr = '<tr style="font-size: 12px;' + bgcolor + '">';
        ntr+= tpl.replace(/%id%/g, id);
        ntr = ntr.replace(/%i%/g, ii);
        ntr+= '</tr>';

        html+= ntr;
    }

    $(html).insertBefore("#tpl_itens");
    $("#total_qtde_item").val(parseInt($('input[name^=referencia_]').length) - 1);
    autocompletaCampos();
    reloadPriceFormat();
}

function reloadPriceFormat(){
    $("input[price=true]").each(function () {
        $(this).priceFormat({
            prefix: '',
            thousandsSeparator: '.',
            centsSeparator: ','
        });
    });
}

function checarNumero(campo){
	var num = campo.value.replace(",",".");
    if (!num) {
        return false;
    }
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='0';
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


function pesquisaOsAjax(){
	var os = $("#os_codigo").val();

	if (!os) {
		return false;
	}
	$.ajax({
			url: "nf_cadastro_manual_ajax_busca_nf.php?tipo=codigo&os="+os,
			success: function(data){
				var retorno = data.split("|");

				//Verificar se retorno = F, ou seja, se não achou a OS.
				if (retorno[0] == "F"){
					alert("Não foi Encontrado Nenhum Resultado Para a sua Pesquisa");
				} else {

					$('#fornecedor_distrib').val(retorno[1]);
					if (retorno[2].length == 11){

						$('#cpf_consumidor').val(retorno[2]);
						$("#tipoDestJuridica").attr("checked",false);
						$("#tipoDestFisica").attr("checked",true);
						mudaTipoPessoa();
						$('#cpf_consumidor').focus();
						$('#consumidor_cep').focus();
						$('#complemento_consumidor').focus();

					}else if(retorno[2].length ==14){

						$('#cnpj_revenda').val(retorno[2]);
						$("#tipoDestJuridica").attr("checked",true);
						$("#tipoDestFisica").attr("checked",false);
						mudaTipoPessoa();
						$('#cnpj_revenda').focus();
						$('#consumidor_cep').focus();
						$('#complemento_consumidor').focus();
					}

					$("#tipoDestJuridica").attr("checked",false);
					$('#consumidor_cep').val(retorno[3]);
					$('#numero_consumidor').val(retorno[4]);
					$('#complemento_consumidor').val(retorno[5]);
					$('#fone_consumidor').val(retorno[6]);
					$('#os_id').val(retorno[7]);
					$('#logradouro_consumidor').val(retorno[8]);
					$('#bairro_consumidor').val(retorno[9]);
					$('#cidade_consumidor').val(retorno[10]);
					$('#estado_consumidor').val($.trim(retorno[11]));
                    $('#email_consumidor').val(retorno[12]);
					$("#posto_nome").attr("disabled",true).css("background", "silver").val("");
					$('#natureza option.entrada').attr('disabled', true);
					$('#natureza option.saida').removeAttr('disabled').attr('selected', true);
					atualizaCFOP($('#natureza').val());

					//
					//$("#natureza").val("REMESSA EM GARANTIA");
					//
					//if(retorno[11] == "SP"){
					//	$("#cfop").val("5949");
					//}else{
					//	$("#cfop").val("6949");
					//}
					//



					var itens = retorno[13].split("¬");
					var contItens;
					var numItens;
					var lcItens;
					var linhasItens = (itens.length-1)/4;
					var valorTotalNotaString;
					var valorTotalNota  = 0;
					var qtde_total = 0;

					for( contItens = 0, lcItens=1; contItens<linhasItens; contItens++){

						var valorIcms = 0;

						$("#referencia_"+contItens+"").val(itens[(lcItens)]);
						$("#descricao_"+contItens+"").val(itens[(lcItens+1)]);

						var qtdeLoop = itens[(lcItens+2)];
						if (!qtdeLoop) {
							break;
						}

						var contaQtdeLoop = qtdeLoop.length;

						if(contaQtdeLoop < 4){
							$("#qtde_"+contItens+"").val(itens[(lcItens+2)]+".00");
						}else{
							$("#qtde_"+contItens+"").val(itens[(lcItens+2)]);
						}

						$("#qtde_"+contItens+"").val(itens[(lcItens+2)]+".00");

						var precoLoop = itens[(lcItens+3)];
						var contaPrecoLoop = precoLoop.length;

						var base_icms = itens[(lcItens+3)]*$("#qtde_"+contItens+"").val();


						if(contaPrecoLoop < 4){
							$("#preco_"+contItens+"").val(itens[(lcItens+3)]+".00");
							$("#base_icms_"+contItens+"").val(base_icms+".00");
						}else{
							$("#preco_"+contItens+"").val((itens[(lcItens+3)]).replace(",","."));
							$("#base_icms_"+contItens+"").val((base_icms.toFixed(2)).replace(",","."));
						}

						var osItem = itens[(lcItens+4)];
						$("#os_item_"+contItens).val(osItem);

						qtde_total = 1;

						if(retorno[11] == "SP"){
							$("#cfop_"+contItens+"").val("5949");
						}else{
							$("#cfop_"+contItens+"").val("6949");
						}

						$("#aliq_ipi_"+contItens+"").val(0+".00");
						$("#base_ipi_"+contItens+"").val(0);
						$("#valor_ipi_"+contItens+"").val(0);


						$("#aliq_icms_"+contItens+"").val(retorno[14]+".00");


						var valorIcms = (itens[(lcItens+3)]*retorno[14])/100*$("#qtde_"+contItens+"").val();

						var valorFinalTotal = itens[(lcItens+3)]*$("#qtde_"+contItens+"").val();

						$("#valor_icms_"+contItens+"").val((valorIcms.toFixed(2)).replace(",","."));

						valorTotalNota = valorFinalTotal + valorTotalNota;

						lcItens = lcItens+5;

						verificaLinha();
					}


					$("#cfop").blur(function(){

						var contItens;
						var numItens;
						var lcItens;
						var linhasItens = (itens.length-1)/4;
						var valorTotalNotaString;
						var valorTotalNota  = 0;
						var qtde_total = 0;
						var digtCFOP = $("#cfop").val();

						//
						//if(digtCFOP == "5102" || digtCFOP == "6102"){
						//	$("#natureza").val("VENDA MERCANTIL");
						//}
						//if(digtCFOP == "6949" || digtCFOP == "5949"){
						//	$("#natureza").val("REMESSA EM GARANTIA");
						//}
						//

						for( contItens = 0, lcItens=1; contItens<linhasItens; contItens++){

							if(retorno[11] == "SP"){
								$("#cfop_"+contItens+"").val(digtCFOP);
							}else{
								$("#cfop_"+contItens+"").val(digtCFOP);
							}

						}

					});

					$("#qtde_volumes").val(qtde_total);

					var msgErroItens = retorno[15];

					valorTotalNotaString = valorTotalNota.toFixed(2);

					if(msgErroItens != "" && msgErroItens != undefined){
						$("#msgErroItens").html("<table align='center' style='background-color: red;color: white;text-align: center; width: 707px;'><tr><td style=\"font: bold 16px Arial !important;\">"+ msgErroItens +"</td></tr></table>");
						$("#msgErroItens").css("display","block");
						for( contItens = 0; contItens<4; contItens++){

							$("#referencia_"+contItens+"").val("");
							$("#descricao_"+contItens+"").val("");
							$("#qtde_"+contItens+"").val("");
							$("#preco_"+contItens+"").val("");

							$("#cfop_"+contItens+"").val("");

							$("#aliq_ipi_"+contItens+"").val("");
							$("#base_ipi_"+contItens+"").val("");
							$("#valor_ipi_"+contItens+"").val("");
							$("#aliq_icms_"+contItens+"").val("");
							$("#base_icms_"+contItens+"").val("");

							$("#valor_icms_"+contItens+"").val("");

						}
					}
					if(msgErroItens == undefined){
						$("#msgErroItens").css("display","none");
					}

					$("#total_nota").val(valorTotalNotaString.replace(",","."));
					$("#total_texto").html(valorTotalNotaString.replace(",","."));

					$('#preco_0').value=0;
					$('#valida_nf_manual').value='1';
					$('#valida_referencia').val(retorno[13]);

					$('#transportadora').focus();
				}
				//load.display = "none";
			}
		});
}


function pesquisaPostoAjax(){
	var q = $("#posto_nome").val();
	$.ajax({
			url: "nf_cadastro_manual_ajax_busca_nf.php?tipo=posto&q=" + encodeURIComponent(q),
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

			}
		});
}

$().ready(function() {
	$("#lupa_consulta").click(function(){
		var nf = document.getElementById('nota_fiscal').value;

		if (nf) {
			document.form_nf.btn_acao2.value='Consultar';
			document.form_nf.submit();
		}
	})

	//MUDA DISTRIBUIDOR DE ACORDO COM O TIPO DE NF

	// TIPO NF: ENTRADA
	$("#tipo_nf").change(function(){
		var tipoNf = $(this).val();

		if ($("#tipo_nf").val() == 0) {
			var month=new Array();
			month[0]="01";
			month[1]="02";
			month[2]="03";
			month[3]="04";
			month[4]="05";
			month[5]="06";
			month[6]="07";
			month[7]="08";
			month[8]="09";
			month[9]="10";
			month[10]="11";
			month[11]="12";

			var data = new Date();
			var dia = data.getDate();
			var mes = month[data.getMonth()];
			var ano = data.getFullYear();
			var auxStr = dia.toString();
			var strDia = auxStr.length;

			if (strDia <= 1) {
				dia = "0" + dia;
			}

			$("#emissao").val(dia + "/"+ mes +"/" + ano);
			$("#saida").val(dia + "/"+ mes +"/" + ano);

			$("#serie").val(0);
			$("#posto_cod").val("4311");
			$('#consumidor_cep').val("");
			$('#numero_consumidor').val("");
			$('#complemento_consumidor').val("");
			$('#fone_consumidor').val("");
			$('#os_id').val("");
			$('#logradouro_consumidor').val("Avenida Carlos Artêncio");
			$('#bairro_consumidor').val("Fragata C");
			$('#cidade_consumidor').val("Marília");
			$('#estado_consumidor').val("SP");
			$("#distribuidor_cod").val("4311");

			$('#natureza option.natuzero').attr('selected', true);
			$('#natureza option.saida').attr('disabled', true);
			$('#natureza option.entrada').removeAttr('disabled');	
			atualizaCFOP($('#natureza').val());

			var contItens;

			for (contItens = 0; contItens<4; contItens++) {
				$("#referencia_"+contItens+"").val("");
				$("#descricao_"+contItens+"").val("");
				$("#qtde_"+contItens+"").val("");
				$("#preco_"+contItens+"").val("");
				$("#cfop_"+contItens+"").val("");
				//$("#cfop").val("");
				$("#aliq_ipi_"+contItens+"").val("");
				$("#base_ipi_"+contItens+"").val("");
				$("#valor_ipi_"+contItens+"").val("");
				$("#aliq_icms_"+contItens+"").val("");
				$("#base_icms_"+contItens+"").val("");
				$("#valor_icms_"+contItens+"").val("");
			}

			$("#msgErroItens").css("display","none");
			$("#total_texto").html("");
			$("#posto_nome").val("Acáciaeletro Paulista Ltda.");
			pesquisaPostoAjax();

			$("#serie").focus();

		}

		if ($("#tipo_nf").val() == 1)
		{
			var month=new Array();
			month[0]="01";
			month[1]="02";
			month[2]="03";
			month[3]="04";
			month[4]="05";
			month[5]="06";
			month[6]="07";
			month[7]="08";
			month[8]="09";
			month[9]="10";
			month[10]="11";
			month[11]="12";

			var data = new Date();
			var dia = data.getDate();
			var mes = month[data.getMonth()];
			var ano = data.getFullYear();
			var serie = $("#serie").val();

			var auxStr = dia.toString();
			var strDia = auxStr.length;

			if(strDia <= 1){
				dia = "0"+dia;
			}

			$("#serie").val(1);
			$("#emissao").val(dia + "/"+ mes +"/" + ano);
			$("#saida").val(dia + "/"+ mes +"/" + ano);
			$("#distribuidor_cod").val("4311");

			$('#natureza option.natuzero').attr('selected', true);
			$('#natureza option.entrada').attr('disabled', true);
			$('#natureza option.saida').removeAttr('disabled');
			atualizaCFOP($('#natureza').val());

			if(serie == "0"){
				$("#posto_cod").val("");
				$('#consumidor_cep').val("");
				$('#numero_consumidor').val("");
				$("#fornecedor_distrib").val("");
				$("#posto_nome").val("");
				$("#cnpj_revenda").val("");
				$("#ie_consumidor").val("");
				$('#complemento_consumidor').val("");
				$('#fone_consumidor').val("");
				$('#os_id').val("");
				$('#logradouro_consumidor').val("");
				$('#bairro_consumidor').val("");
				$('#cidade_consumidor').val("");
				$('#estado_consumidor').val("");

				var contItens;

						for( contItens = 0; contItens<4; contItens++){

							$("#referencia_"+contItens+"").val("");
							$("#descricao_"+contItens+"").val("");
							$("#qtde_"+contItens+"").val("");
							$("#preco_"+contItens+"").val("");

							$("#cfop_"+contItens+"").val("");

							$("#aliq_ipi_"+contItens+"").val("");
							$("#base_ipi_"+contItens+"").val("");
							$("#valor_ipi_"+contItens+"").val("");
							$("#aliq_icms_"+contItens+"").val("");
							$("#base_icms_"+contItens+"").val("");

							$("#valor_icms_"+contItens+"").val("");

						}

						$("#total_texto").html("");
			}
			pesquisaOsAjax();
			$("#serie").focus();

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
		$("#os_codigo").attr("disabled",true).css("background", "silver");
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


	// PESQUISA POSTO E TRANSPORTADORA - AUTOCOMPLETE
	autocompletaCampos();


	$("#lupa_os").click(function(){


		pesquisaOsAjax();
		//var load = document.getElementById('buscando_os').style;
		//load.display = "block";

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

<center><h1>Cadastrar NF Manual (Nova Tela)</h1></center>

<div id="msgErroItens">

</div>

<?
if ($erro_msg) {
?>
<table style="width: 1120px;" align="center" cellpadding="0" cellspacing="0">
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
<table style="width: 1120px;" align="center" >

	<tr>
		<td class="sucesso">
			<?="Nota Fiscal nº <u>$nota_fiscal</u> Gravada com Sucesso"?>
		</td>
	</tr>

</table>
<?}?>

<form name='form_nf' method="POST" action='<? echo $PHP_SELF?>'>
<table align='center' class="formulario"  cellpadding="1" cellspacing="1" style="width: 1120px">
	<!-- Dados da Nota Fiscal -->


	<tr>
		<th class="subtitulo" align="center" colspan="100%">Busca de Destinatário</th>
	</tr>

	<tr><td>&nbsp;</td></tr>

	<tr>
		<td>
			<table cellpadding="0" cellspacing="2" align="center" border="0">
				<!-- Dados do Destinatário -->

				<tr>
					<td>
						Ordem de Serviço
					</td>

					<td style="padding-left:20px;">
						Nota fiscal
					</td>

					<td>
						Posto
					</td>
				</tr>

				<tr>
					<td width="30%">
						<input type="hidden" name="os_id" id="os_id" class="frm"value="<?=$os_id?>" />
						<input type="text" name="os_codigo" id="os_codigo" class="frm" style="width:150px" value="<?=$os_codigo?>" />
						<img src="../imagens/lupa.png" border="0" id="lupa_os" name="lupa_os" style="cursor:pointer" align="absmiddle"><br/>
						<div id="buscando_os" class="carregar" name="buscando_os" style="display: none; text-align: center; padding: 5px; margin-top: -20px; width: 220px;">
							Pesquisando Ordem de Serviço...
						</div>
					</td>
					<td nowrap style="padding-left:20px;">
						<input type='text' class='frm' name='nota_fiscal' id='nota_fiscal' value='<?=$nota_fiscal?>'  size='8'  maxlength='8' >
						<img src="../imagens/lupa.png" border="0" id="lupa_consulta" name="lupa_consulta" style="cursor:pointer" align="absmiddle">
						<div name='f2' id='f2' class='carregar'></div>
						<input type='hidden' name='btn_acao2' value=''>
						<input type='hidden' name='btn_consultar' value='Consultar' onclick="javascript: document.form_nf.btn_acao2.value='Consultar'; document.form_nf.submit()">

					</td>
					<td colspan="2">
						<input type="hidden" id="distribuidor_cod" name="distribuidor_cod" value="<?=$distribuidor_cod?>">
						<input type="hidden" id="posto_cod" name="posto_cod" value="<?=$posto_cod?>">
						<input class="frm" type="text" name="posto_nome" id="posto_nome" value="<? echo $posto_nome ?>" style="width:220px;" title="Digite para pesquisar pelo posto">
					</td>
				</tr>
			</table>
		</td>
	</tr>

	<tr><td>&nbsp;</td></tr>

	<tr><td align="center" class="subtitulo" colspan="100%">Dados da Nota Fiscal</td></tr>
	<tr>
		<td>
			<table class="formulario" cellpadding="0" cellspacing="2" style="width:600px;" align="center" border="0">
				<tr><td>&nbsp;</td></tr>
				<tr>
					<td>Tipo NF</td>
					<td colspan="2">Série</td>
					<td style="padding-left:30px;">Data Emissão</td>
					<td>Data Saída</td>
				</tr>

				<tr>
					<td>
						<select class='frm' name="tipo_nf" id="tipo_nf">
							<option value="NULL"></option>
							<option value="0" <?php echo ($tipo_nf=='0') ? 'selected' : null;?> >Entrada</option>
							<option value="1" <?php echo ($tipo_nf=='1') ? 'selected' : null;?> >Saida</option>
						</select>
					</td>
					<td align="left" colspan="2">
						<input type='text' size="3" class='frm'name='serie' id='serie' value='<?=$serie?>'  maxlength='10' >
					</td>

					<td style="padding-left:30px;">
						<input  type='text' class='frm' name='emissao' id='emissao' value='<?=$emissao?>'  maxlength='10' >
					</td>

					<td>
						<input type='text' class='frm' name='saida' id='saida' value='<?=$saida?>' maxlength='10'>
					</td>
				</tr>

				<tr>

					<td colspan='4'>Natureza</td>
					<td>CFOP</td>

				</tr>

				<tr>



					<td colspan='4'>
						<?php
						/*<input type='text' name='natureza' class="frm" id='natureza' value='<?=$natureza?>'   maxlength='30' style="width:320px">*/
						echo '<select name="natureza" id="natureza" onChange="atualizaCFOP(this.value)" style="width: 320px;" />';
							echo '<option value="" class="natuzero"> --- </option>';
							foreach ($naturezas as $n => $cfops) {
								$class = (in_array($cfops[0],array(1915,1556))) ? ' class="entrada" ' : ' class="saida" ' ;
								echo '<option value="' , $n , '#' , $cfops[0] , '"' , $class , '>' , $n , '</option>';
							}
						echo '</select>';
						?>
					</td>
					<td>
						<input type='text'  name='cfop' class="frm" id='cfop' value='<?=$cfop?>' style="width: 60px;" readonly />
					</td>

				</tr>

				<tr>
					<td>
						Empresa<br/>
						<input type="radio" name="empresa" value="ACAC" />Acácia
						<input type="radio" name="empresa" value="TELEC" />Telecontrol
					</td>
				</tr>
			</table>
		</td>
	</tr>

	<tr>
		<th class="subtitulo" align="center" colspan="100%">Dados do Destinatário</th>
	</tr>

	<tr><td>&nbsp;</td></tr>

	<tr>
		<td>
			<table cellpadding="0" cellspacing="2" width="600px" align="center" border="0">
				<tr>
					<td colspan='3' align="left">
						<label id="nomeDestinatario">
							Nome/Razão Social
						</label>
					</td>
					<td colspan="2" align="left">
						<label id="nomeDestinatario">
							Tipo
						</label>
					</td>
				</tr>

				<tr>
					<td nowrap colspan='3'>
				<?php

				//--------------------------------------------------------------------------------------------------------

				if(strlen($fabrica)>0) echo "<input type='hidden' name='faturamento_fabrica' value='$fabrica'>";
				if(strlen($fabrica)>0) echo "<input type='hidden' name='fabrica' value='$fabrica' id='fabrica'>";

				echo "<input type='text' class='frm' name='fornecedor_distrib' id='fornecedor_distrib' size ='64' maxlenght='64' style='width:100%' value='$fornecedor_distrib'>";
				echo "<input type='hidden'  name='fornecedor_distrib_posto' id='fornecedor_distrib_posto' value='$fornecedor_distrib_posto' >";

				if(strlen($fornecedor_distrib)>0) echo "<input type='hidden' name='faturamento_fabrica' value='$fabrica'>";
				if(strlen($fornecedor_distrib)>0) echo "<input type='hidden' name='fornecedor_distrib' value='$fornecedor_distrib'>";
				//--------------------------------------------------------------------------------------------------------
				?>
					</td>
					<td colspan='2'>
						<?if (strlen($tipo_consumidor)==0) $tipo_consumidor="F"?>
							<input type="radio" name="tipo_consumidor" value="F" id="tipoDestFisica" class="tipoPessoa" <?php echo ($tipo_consumidor=='F') ? 'checked' : null;?>  /> <label for="tipoDestFisica" style="cursor:pointer">Física</label>
							<input type="radio" name="tipo_consumidor" value="J" id="tipoDestJuridica" class="tipoPessoa" <?php echo ($tipo_consumidor=='J') ? 'checked' : null;?> /> <label for="tipoDestJuridica" style="cursor:pointer">Jurídica</label>
					</td>
				</tr>
				<tr>
					<td colspan="2"><label id="label_Cpf_Cnpj">CPF</label></td>
					<td colspan="4"><label id="label_ie" <?php echo ($tipo_consumidor=='F') ? 'style="display:none"' : null;?>>IE</label></td>
				</tr>

				<tr>
					<td colspan="2">

						<input type="text" name="cpf_consumidor" id="cpf_consumidor" onkeypress="mascara_cpf(this, event);" maxlength="14" class="frm"  value="<?php echo $cpf_consumidor ?>" onfocus="formata_cpf_cnpj(this,1)" <?php echo ($tipo_consumidor=='F') ? 'style="display:block;"' : 'style="display:none;"';?> />
						<input type="text" name="cnpj_revenda" id="cnpj_revenda" onkeypress="mascara_cnpj(this, event);" onfocus="formata_cpf_cnpj(this,2);" class="frm" maxlength="18" value="<?php echo $cnpj_revenda?>" <?php echo ($tipo_consumidor=='J') ? 'style="display:block;"' : 'style="display:none;"';?> />
					</td>

					<td colspan="4">
						<input type="text" <?php echo ($tipo_consumidor=='F') ? 'style="display:none"' : 'style="display:block;width:100%"';?>  class="frm" name="ie_consumidor" id="ie_consumidor" value="<?=$ie_consumidor?>" maxlength="16">
					</td>

				</tr>

				<!-- LOGRADOURO -->
				<tr>
					<td>&nbsp;</td>
				</tr>

				<tr>
					<td>CEP</td>
					<td colspan="3">Logradouro</td>
					<td>Número</td>
				</tr>

				<tr>
					<td>
						<input name="consumidor_cep" id='consumidor_cep' style="width: 100px;" value='<?echo $consumidor_cep ;?>' class="frm" type="text" maxlength="10">
					</td>
					<td colspan="3">
						<input class="frm" type="text" style="width:100%" name="logradouro_consumidor" id="logradouro_consumidor" value="<?=$logradouro_consumidor?>" maxlength="64">
					</td>

					<td>
						<input class="frm" type="text" name="numero_consumidor" maxlength="8" style="width:70px;" id="numero_consumidor" value="<?=$numero_consumidor?>">
					</td>
				</tr>

				<tr>
					<td>Complemento</td>
					<td>Bairro</td>
					<td colspan="2">Cidade</td>
					<td>UF</td>
				</tr>

				<tr>
					<td>
						<input type="text" class="frm" style="width:110/px;" name="complemento_consumidor" id="complemento_consumidor" maxlength="32" value="<?=$complemento_consumidor?>">
					</td>

					<td>
						<input type="text" class="frm" style="width:220px;" name="bairro_consumidor" id="bairro_consumidor" maxlength="32" value="<?=$bairro_consumidor?>">
					</td>

					<td colspan="2">
						<input type="text" class="frm" style="width:150;" name="cidade_consumidor" id="cidade_consumidor" maxlength="32" value="<?=$cidade_consumidor?>">
					</td>

					<td>
						<?php
						  $array_estado = array("AC"=>"AC","AL"=>"AL","AM"=>"AM",
						  "AP"=>"AP", "BA"=>"BA", "CE"=>"CE","DF"=>"DF",
						  "ES"=>"ES", "GO"=>"GO","MA"=>"MA","MG"=>"MG",
						  "MS"=>"MS","MT"=>"MT", "PA"=>"PA","PB"=>"PB",
						  "PE"=>"PE","PI"=>"PI","PR"=>"PR","RJ"=>"RJ",
						  "RN"=>"RN","RO"=>"RO","RR"=>"RR",
						  "RS"=>"RS", "SC"=>"SC","SE"=>"SE",
						  "SP"=>"SP","TO"=>"TO");
						?>
						<select name="estado_consumidor" class="frm" style="width:50px" id="estado_consumidor" onChange="atualizaCFOP(document.getElementById('natureza').value)">
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
					<td>Email</td>
				</tr>

				<tr>
					<td>
						<input type="text" class="frm" style="width:110px;" name="fone_consumidor" id="fone_consumidor" value="<?=$fone_consumidor?>" maxlength="10">
					</td>
					<td>
                        <input type="text" class="frm" style="width: 220px;" name="email_consumidor" id="email_consumidor" value="<?=$email_consumidor?>">
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
			<table cellpadding="0" cellspacing="2" align="center" style="width:600px;" class="formulario" border="0">
				<tr>
					<td>Transportadora</td>
					<td>Tipo Frete</td>
					<td style="padding-left:40px;">Qtde. Volumes</td>
				</tr>
				<tr>
					<td>

						<?php #COMBO TIRADO DO PROGRAMA distrib/embarque_faturamento.php?>
						<select name='transportadora' style="width:80%" class='frm'>
							<option value='' <?php echo ( strlen($_POST['transportadora'])==0 or $transp == 0) ? "SELECTED" : "" ; ?> ></option>
							<?php
							#echo "<option value='1055' SELECTED>VARIG-LOG</option>";
							?>
							<option value='1058' <?php echo ($_POST['transportadora'] == '1058' or $transp == '1058') ? "SELECTED" : "" ; ?> >	PAC             </option>
							<option value='1061' <?php echo ($_POST['transportadora'] == '1061' or $transp == '1061') ? "SELECTED" : "" ; ?> >	PAC (TC)        </option>
							<option value='1062' <?php echo ($_POST['transportadora'] == '1062' or $transp == '1062') ? "SELECTED" : "" ; ?> >	PAC (AK)	    </option>
							<option value='1056' <?php echo ($_POST['transportadora'] == '1056' or $transp == '1056') ? "SELECTED" : "" ; ?> >	SEDEX		    </option>
							<?# Adicionei e-sedex. Fabio - 05/08/2007 - Solicitado por Ronaldo?>
							<option value='1060' <?php echo ($_POST['transportadora'] == '1060'  or $transp == '1060') ? "SELECTED" : "" ; ?> >	E-SEDEX		    </option>
							<option value='1057' <?php echo ($_POST['transportadora'] == '1057' or $transp == '1057') ? "SELECTED" : "" ; ?> >	PROPRIO	 	    </option>
							<option value='497'  <?php echo ($_POST['transportadora'] == '497'  or $transp == '497')  ? "SELECTED" : "" ; ?> >	BRASPRESS       </option>
							<option value='703'  <?php echo ($_POST['transportadora'] == '703' or $transp == '703')  ? "SELECTED" : "" ; ?> >	MERCURIO		</option>
							<option value='4176' <?php echo ($_POST['transportadora'] == '4176'  or $transp == '4176') ? "SELECTED" : "" ; ?> >	JADLOG		    </option>
							<option value='4773' <?php echo ($_POST['transportadora'] == '4773' or $transp == '4773') ? "SELECTED" : "" ; ?> >	MAIS TRANSPORTE </option>
							<?#echo "<option value='1059'>RODONAVES</option>";
							#echo "<option value='1060'>E-SEDEX</option>";
							?>
						</select>
					</td>
					<td>
						<select class="frm" name="tipo_frete" id="tipo_frete" style="width:100%" id="tipo_frete" onChange="calcImpostos();">
							<option value=""></option>
							<?
							?>
							<option value="1" <?php echo ($tipo_frete=='1') ? 'selected' : null;?>>Emitente</option>
							<option value="2" <?php echo ($tipo_frete=='2') ? 'selected' : null;?>>Destinatário</option>

						</select>
					</td>
					<td  style="padding-left:40px;">
						<input class="frm" type='text' name='qtde_volumes' id='qtde_volumes' value='<?=$qtde_volumes?>' style="width:30%" maxlength='4'>
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
</table>

<table class="formulario" align='center' id="tbl_itens_nf" name="tbl_itens_nf" cellpadding='0' cellspacing='1' style='border-top: none; border-bottom: none; width: 1120px;'>
	<tr>
		<th class="titulo_tabela" align="center" colspan="15">Itens da Nota Fiscal</th>
		<th class="titulo_tabela" align="right" colspan="5">
            <input type="text" id="adicionar_itens" name="adicionar_itens" value="5" style="width: 30px;" /> <span style="cursor: pointer" onClick="adicionaItens()">linhas +</span>
		</th>
	</tr>
<tr class="titulo_tabela">
	<!-- <td align='center' style="width:10px">#</td> -->
	<td style='width:75px;'>Referência</td>
	<td style='width:300px;'>Descrição Produto/Peça</td>
	<td style="width:70px">Qtde</td>
	<td style="width:70px">Preço</td>
	<td>Frete</td>
	<td>Seguro</td>
	<td style="width:70px">CFOP</td>
	<td style="width:70px">Aliq. ICMS %</td>
	<td style="width:100px">Aliq. IPI %</td>
    <td>Aliq. ICMS ST</td>
	<td>MVA</td>
	<td>Perc. de Redução</td>
	<td>ST</td>
	<td>Base ST</td>
	<td>Valor ICMS ST</td>
	<td style="width:100px">Base ICMS</td>
	<td style="width:100px">Valor ICMS</td>
	<td style="width:100px">Base IPI</td>
	<td style="width:100px">Valor IPI</td>
</tr>

<tr id='0'><td colspan='100%'></td></tr>
<tbody id='tb_itens'>
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
        
        $frete = $_POST["frete_$i"];
        $seguro = $_POST["seguro_$i"];
        $mva = $_POST["mva_$i"];
        $perc_red = $_POST["perc_red_$i"];
        $st = $_POST["st_$i"];
        $aliq_icms_st = $_POST["aliq_icms_st_$i"];
        $base_st = $_POST["base_st_$i"];
        $valor_icms_st = $_POST["valor_icms_st_$i"];

		$qtde_linha = $i+ 1;
		?>
		<tr style='font-size: 12px' id='<?=$qtde_linha?>'>
			<!-- <td align='right' nowrap><?=($i+1)?></td> -->
			<td align='center' nowrap >
				<input type='text' class='frm' style="width: 70px;" name='referencia_<?=$i?>' id='referencia_<?=$i?>' value='<?=$referencia?>' maxlength='20' rel='referencia' alt='descricao_<?=$i?>' style="width:90%;">
			</td>
			<td align='center' nowrap>
				<input type='text' class='frm' style="width: 182px;" name='descricao_<?=$i?>' id='descricao_<?=$i?>' alt='referencia_<?=$i?>' value='<?=$descricao?>' style='width:90%;' maxlength='20' rel='descricao' >
			</td>
			<td align='center' nowrap>
				<input class='frm qtde_prod' style="width:40px;" type='text' maxlength='10' name='qtde_<?=$i?>' id='qtde_<?=$i?>' value='<?=$qtde?>' onBlur='calcImpostos();'>
			</td>
			<td align='center' nowrap>
				<input class='frm' style="width:55px;" type='text' maxlength='12' name='preco_<?=$i?>' id='preco_<?=$i?>' value='<?=$preco?>' onBlur='calcImpostos();' price="true">
			</td>
			
			<td align='center' nowrap>
                <input class='frm frete' style="width:45px;" type='text' maxlength='10' name='frete_<?=$i?>' id='frete_<?=$i?>' value='<?=$frete?>' onBlur='calcImpostos();' price="true" >
            </td>
            
            <td align='center' nowrap>
                <input class='frm' style="width:45px;" type='text' maxlength='10' name='seguro_<?=$i?>' id='seguro_<?=$i?>' value='<?=$seguro?>' onBlur='calcImpostos();' price="true" >
            </td>
            
			<td align='center' nowrap>
				<input class='frm cfop' style="width:55px;" type='text' maxlength='12' name='cfop_<?=$i?>' id='cfop_<?=$i?>' value='<? if ( strlen($_POST["cfop_$i"])>0 ) echo $cfop_item ;?>'>
			</td>
			<td align='center' nowrap>
				<input class='frm' style="width:45px;" type='text' maxlength='10' name='aliq_icms_<?=$i?>' id='aliq_icms_<?=$i?>'	value='<?=$aliq_icms?>' onBlur='calcImpostos();' price="true">
			</td>
			<td align='center' nowrap>
				<input class='frm' style="width:45px;" type='text' maxlength='10' name='aliq_ipi_<?=$i?>' id='aliq_ipi_<?=$i?>' value='<?=$aliq_ipi?>'	onKeyUp='calc_base_icms();' onBlur='calcImpostos();' price="true">
			</td>

            <td align='center' nowrap>
                <input class='frm' style="width:45px;" type='text' maxlength='10' name='aliq_icms_st_<?=$i?>' id='aliq_icms_st_<?=$i?>' value='<?=$aliq_icms_st?>' price="true" >
            </td>
			
			<td align='center' nowrap>
                <input class='frm' style="width:45px;" type='text' maxlength='10' name='mva_<?=$i?>' id='mva_<?=$i?>' value='<?=$mva?>' price="true" onBlur="calcImpostos();" >
            </td>
            
            <td align='center' nowrap>
                <input class='frm' style="width:45px;" type='text' maxlength='10' name='perc_red_<?=$i?>' id='perc_red_<?=$i?>' value='<?=$perc_red?>' price="true" >
            </td>
            
            <td align='center' nowrap>
                <input class='frm' style="width:45px;" type='checkbox' name='st_<?=$i?>' id='st_<?=$i?>'>
            </td>
            
            <td align='center' nowrap>
                <input class='frm' style="width:45px; border: none; background: none;" type='text' maxlength='10' name='base_st_<?=$i?>' id='base_st_<?=$i?>' value='<?=$base_st?>' onfocus='form_nf.referencia_<?=($i+1)?>.focus();' price="true" >
            </td>
            
            <td align='center' nowrap>
                <input class='frm' style="width:45px; border: none; background: none;" type='text' maxlength='10' name='valor_icms_st_<?=$i?>' id='valor_icms_st_<?=$i?>' value='<?=$valor_icms_st?>' onfocus='form_nf.referencia_<?=($i+1)?>.focus();' price="true">
            </td>
            
			<td align='center' nowrap>
				<input class='frm' style="width:55px;border: none; background: none;" type='text' maxlength='10' name='base_icms_<?=$i?>'	id='base_icms_<?=$i?>'	value='<?=$base_icms?>'	onfocus='form_nf.referencia_<?=($i+1)?>.focus();' readonly=''>
			</td>
			<td align='center' nowrap>
				<input class='frm' style="width:40px;border: none; background: none;" type='text' maxlength='10' name='valor_icms_<?=$i?>'	id='valor_icms_<?=$i?>'	value='<?=$valor_icms?>' onfocus='form_nf.referencia_<?=($i+1)?>.focus();' readonly=''>
			</td>
			<td align='center' nowrap>
				<input class='frm' style="width:55px;border: none; background: none;" type='text' maxlength='10' name='base_ipi_<?=$i?>'	id='base_ipi_<?=$i?>'	value='<?=$base_ipi?>'	onfocus='form_nf.referencia_<?=($i+1)?>.focus();' readonly=''>
			</td>
			<td align='center' nowrap>
				<input class='frm' style="width:50px;border: none; background: none;" type='text' maxlength='10' name='valor_ipi_<?=$i?>'	id='valor_ipi_<?=$i?>'	value='<?=$valor_ipi?>'	onfocus='form_nf.referencia_<?=($i+1)?>.focus();' readonly=''>
			</td>
			<input type='hidden'  name='total_item_nf_<?=$i?>' id='total_item_nf_<?=$i?>' value='0' class='total_item_nf'>
			<input type="hidden" name="os_item_<?php echo $i ?>" id="os_item_<?php echo $i ?>" value="" />
		</tr>
		
		
		<?
	}
	
	?>
    <tr id="tpl_itens" style='font-size: 12px; display: none;'>
        <td align='center' nowrap >
            <input type='text' class='frm' style="width: 70px;" name='referencia_%i%' id='referencia_%i%' value='' maxlength='20' rel='referencia' alt='descricao_%i%' style="width:90%;">
        </td>
        <td align='center' nowrap>
            <input type='text' class='frm' style="width: 182px;" name='descricao_%i%' id='descricao_%i%' alt='referencia_%i%' value='' style='width:90%;' maxlength='20' rel='descricao' >
        </td>
        <td align='center' nowrap>
            <input class='frm qtde_prod' style="width:40px;" type='text' maxlength='10' name='qtde_%i%' id='qtde_%i%' value='' onBlur='calcImpostos();'>
        </td>
        <td align='center' nowrap>
            <input class='frm' style="width:55px;" type='text' maxlength='12' name='preco_%i%' id='preco_%i%' value='' onBlur='calcImpostos();' price="true">
        </td>
        
        <td align='center' nowrap>
            <input class='frm frete' style="width:45px;" type='text' maxlength='10' name='frete_%i%' id='frete_%i%' value='' onBlur='calcImpostos();' price="true" >
        </td>
        
        <td align='center' nowrap>
            <input class='frm' style="width:45px;" type='text' maxlength='10' name='seguro_%i%' id='seguro_%i%' value='' onBlur='calcImpostos();' price="true" >
        </td>
        
        <td align='center' nowrap>
            <input class='frm cfop' style="width:55px;" type='text' maxlength='12' name='cfop_%i%' id='cfop_%i%' value=''>
        </td>
        <td align='center' nowrap>
            <input class='frm' style="width:45px;" type='text' maxlength='10' name='aliq_icms_%i%' id='aliq_icms_%i%' value='' onBlur='calcImpostos();' price="true">
        </td>
        <td align='center' nowrap>
            <input class='frm' style="width:45px;" type='text' maxlength='10' name='aliq_ipi_%i%' id='aliq_ipi_%i%' value='' onBlur='calcImpostos();' price="true">
        </td>
        
        <td align='center' nowrap>
            <input class='frm' style="width:45px;" type='text' maxlength='10' name='aliq_icms_st_%i%' id='aliq_icms_st_%i%' value='' onBlur='calcImpostos();' price="true">
        </td>
        
        <td align='center' nowrap>
            <input class='frm' style="width:45px;" type='text' maxlength='10' name='mva_%i%' id='mva_%i%' value='' price="true" onBlur="calcImpostos();" >
        </td>
        
        <td align='center' nowrap>
            <input class='frm' style="width:45px;" type='text' maxlength='10' name='perc_red_%i%' id='perc_red_%i%' value='' price="true" >
        </td>
        
        <td align='center' nowrap>
            <input class='frm' style="width:45px;" type='checkbox' name='st_%i%' id='st_%i%'>
        </td>
        
        
        <td align='center' nowrap>
            <input class='frm' style="width:45px; border: none; background: none;" type='text' maxlength='10' name='base_st_%i%' id='base_st_%i%' value='' onfocus='form_nf.referencia_%id%.focus();' price="true">
        </td>
        
        <td align='center' nowrap>
            <input class='frm' style="width:45px; border: none; background: none;" type='text' maxlength='10' name='valor_icms_st_%i%' id='valor_icms_st_%i%' value='' onfocus='form_nf.referencia_%id%.focus();' price="true" >
        </td>
        
        <td align='center' nowrap>
            <input class='frm' style="width:55px;border: none; background: none;" type='text' maxlength='10' name='base_icms_%i%'   id='base_icms_%i%'  value='' onfocus='form_nf.referencia_%id%.focus();' readonly=''>
        </td>
        <td align='center' nowrap>
            <input class='frm' style="width:40px;border: none; background: none;" type='text' maxlength='10' name='valor_icms_%i%'  id='valor_icms_%i%' value='' onfocus='form_nf.referencia_%id%.focus();' readonly=''>
        </td>
        <td align='center' nowrap>
            <input class='frm' style="width:55px;border: none; background: none;" type='text' maxlength='10' name='base_ipi_%i%'    id='base_ipi_%i%'   value=''  onfocus='form_nf.referencia_%id%.focus();' readonly=''>
        </td>
        <td align='center' nowrap>
            <input class='frm' style="width:50px;border: none; background: none;" type='text' maxlength='10' name='valor_ipi_%i%'   id='valor_ipi_%i%'  value='' onfocus='form_nf.referencia_%id%.focus();' readonly=''>
        </td>
        <input type='hidden'  name='total_item_nf_%i%' id='total_item_nf_%i%' value='0' class='total_item_nf'>
        <input type="hidden" name="os_item_%i%" id="os_item_%i%" value="" />
    </tr>
	<?php
	echo "</tbody>";
		echo "<input type='hidden' name='total_items_nf' id='total_items_nf' value='$total_items_nf'>";

	echo "</table>";

	echo "<input type='hidden' name='total_qtde_item'   id='total_qtde_item' value='{$total_qtde_item}'>";
	echo "<input type='hidden' name='valida_nf_manual'  id='valida_nf_manual' value=''>";
	echo "<input type='hidden' name='valida_referencia' id='valida_referencia' value=''>";
echo "</td>";
echo "</tr>";

?>

<table style="width: 1120px;" align="center" class="formulario"  cellpadding='0' cellspacing='1'>
	<tr>
		<th class="subtitulo" align="center" colspan="100%">Dados Fiscais</th>
	</tr>
	<tr>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>
			<table cellpadding='0' cellspacing='2' align='center' class='formulario' border='0' align="center">
				<tr>
					<td width="233" align="right" >Base ICMS Subst. Trib.</td>
					<td width="233" align="center">Valor ICMS Subst. Trib.</td>
					<td width="233" align="left">Valor Frete</td>
				</tr>
				<tr>
					<td width="233" align="right">
						<input onKeyUp="somaValores()" style='width:150px;text-align:center' class="frm money" type='text' name='base_icms_substtituicao' id='base_icms_substtituicao' value='<?=$base_icms_substtituicao?>'  maxlength='12' title='Colocar neste campo o valor Base de ICMS de  Substituição Tributária.'></td>
					</td>
					<td width="233" nowrap align="center">
						<input onKeyUp="somaValores()" style='width:150px;text-align:center' class="frm money" type='text' name='valor_icms_substtituicao' id='valor_icms_substtituicao' value='<?= $valor_icms_substtituicao ?>'  maxlength='12' title='Colocar neste campo o valor ICMS de Substituição Tributária'></td>
					</td>
					<td width="233" align="left"><input onBlur="calcula_frete_produto(); calcImpostos();" type="text" class="frm" style='width:150px;text-align:center' name="valor_frete" id="valor_frete" maxlength="12"  value="<?=$valor_frete?>" price="true"/></td>
				</tr>
				<tr>
					<td width="233" align="right">Valor Desconto</td>
					<td width="233" align="center">Outros Valores</td>
					<td width="233" align="left">Seguro</td>
				</tr>
				<tr>
					<td width="233" align="right">
						<input onKeyUp="somaValores()" type="text" name="valor_desconto" id="valor_desconto" style='width:150px;text-align:right' class="frm money" value="<?=$valor_desconto?>" />
					</td>
					<td width="233" align="center">
						<input onKeyUp="somaValores()" type="text" name="outros_valores" id="outros_valores" style='width:150px;text-align:right' class="frm money" value="<?=$outros_valores?>" />
					</td>
					<td width="233" align="left">
						<input onKeyUp="somaValores()" type="text" name="valor_seguro" id="valor_seguro" style='width:150px;text-align:right' class="frm money" value="<?=$valor_seguro?>" />
					</td>
				</tr>

				<tr><td>&nbsp;</td></tr>

				<tr height="50px">
					<?if (strlen($total_nota)==0){
						$total_nota=0;
					}
					?>
					<td>
                        <input type="hidden" value="<?=$total_produtos?>" id="total_produtos" name="total_produtos">
                        <input type="hidden" value="<?=$total_nota?>" id="total_nota" name="total_nota">
                    </td>
					<td align="right"><label style="font: bold 11px Arial;color:#000000"></label></td>

					<td align="right"><label style="font: bold 16px Arial;color:#000000">TOTAL DA NOTA: R$ <span id="total_texto"><? echo str_replace(".",",",$total_nota) ?></span></label></td>
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

if ($btn_acao2 == "Consultar" and (strlen($chave_nfe)>0 or strlen($recibo_nfe)>0 or strlen($status_nfe)>0)) {
	?>
	<INPUT TYPE="BUTTON" VALUE="Nova Consulta" ONCLICK="window.location.href='nf_cadastro_manual_new.php'">
	<?
}else{
	echo "<input type='hidden' name='btn_acao' value=''>";
	echo "<input type='button' name='btn_grava' value='Gravar' onclick='javascript: document.form_nf.btn_acao.value=\"Gravar\"; document.form_nf.submit()'>";
}

echo "</td>";
echo "</tr>";
?>

</table>
<?
echo "</table>\n";
echo "</form>";
?>



<? include "rodape.php"; ?>
	<script type="text/javascript">
		$(document).ready(function(){

			fnZebraItens();
			verificaLinha();
		});


		function fnZebraItens(){
			$('table tbody#tb_itens tr:even td').css('background-color','#98C7D3');

			autocompletaCampos();
			verificaGeraLinha();
		}

		function countElement(){
			return $("#tb_itens tr").size();
		}

		function verificaLinha(){
			var registro = 0;

			$('[id*=referencia_]').each(function(indice){
                if($(this).val() != ''){
            		registro += 1;
                }
            });

            var linha = countElement() - registro;
            if(linha < 2){
            	for (i = 0; i < 2 ; i++){
            		geraLinha();
            	}
            }

            return linha;
		}

		function geraLinha(){
			var indice = countElement();
			var html = "";

			html += "<tr>";
				html += "<td align='center' nowrap><input type='text' class='frm' style='width: 70px;' name='referencia_"+indice+"' id='referencia_"+indice+"' maxlength='20' rel='referencia' alt='descricao_"+indice+"'></td>";
				 html += "<td align='center' nowrap><input type='text' class='frm' style='width: 182px;' name='descricao_"+indice+"' id='descricao_"+indice+"' alt='referencia_"+indice+"' style='width:90%;' maxlength='20' rel='descricao' ></td>";
				 html += "<td align='center' nowrap><input class='frm qtde_prod' style='width:40px;' type='text' maxlength='10' name='qtde_"+indice+"'	id='qtde_"+indice+"' onKeyUp='calc_base_icms("+indice+");' onblur='checarNumero(this);'></td>";
				 html += "<td align='center' nowrap><input class='frm' style='width:55px;' type='text' maxlength='12' name='preco_"+indice+"'	id='preco_"+indice+"'	onKeyUp='calc_base_icms("+indice+");' onblur='checarNumero(this);'></td>";
				 html += "<td align='center' nowrap><input class='frm' style='width:55px;' type='text' maxlength='12' name='cfop_"+indice+"'	id='cfop_"+indice+"'></td>";
				 html += "<td align='center' nowrap><input class='frm' style='width:45px;' type='text' maxlength='10' name='aliq_icms_"+indice+"'	id='aliq_icms_"+indice+"' onKeyUp='calc_base_icms("+indice+");' onblur='checarNumero(this);'></td>";
				 html += "<td align='center' nowrap><input class='frm' style='width:45px;' type='text' maxlength='10' name='aliq_ipi_"+indice+"'	id='aliq_ipi_"+indice+"' onKeyUp='calc_base_icms("+indice+");' onblur='checarNumero(this);'></td>";
				 html += "<td align='center' nowrap><input class='frm' style='width:55px;border: none; background: none;' type='text' maxlength='10' name='base_icms_"+indice+"'	id='base_icms_"+indice+"' onfocus='form_nf.referencia_"+(indice+1)+".focus();' readonly=''></td>";
				 html += "<td align='center' nowrap><input class='frm' style='width:40px;border: none; background: none;' type='text' maxlength='10' name='valor_icms_"+indice+"'	id='valor_icms_"+indice+"'  onfocus='form_nf.referencia_"+(indice+1)+".focus();' readonly=''></td>";
				 html += "<td align='center' nowrap><input class='frm' style='width:55px;border: none; background: none;' type='text' maxlength='10' name='base_ipi_"+indice+"'	id='base_ipi_"+indice+"' onfocus='form_nf.referencia_"+(indice+1)+".focus();' readonly=''></td>";
				 html += "<td align='center' nowrap><input class='frm' style='width:50px;border: none; background: none;' type='text' maxlength='10' name='valor_ipi_"+indice+"'	id='valor_ipi_"+indice+"'   onfocus='form_nf.referencia_"+(indice+1)+".focus();' readonly=''><input type='hidden'  name='total_item_nf_"+indice+"' id='total_item_nf_"+indice+"' value='0' class='total_item_nf'></td>";
			html += "</tr>";

			$("table tbody#tb_itens").append(html);


			fnZebraItens();
		}

		function verificaGeraLinha(){
			$('[id*=referencia_]').focus(function(){
				verificaLinha();
			});

			$("#total_qtde_item").val(countElement());
		}
	</script>
</body>
</html>

