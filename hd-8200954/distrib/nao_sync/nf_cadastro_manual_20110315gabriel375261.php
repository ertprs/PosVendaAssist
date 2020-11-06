<?
//OBS: ESTE ARQUIVO UTILIZA AJAX: form_nf_ret_ajax.php

include 'dbconfig.php';
// $dbnome = 'teste';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
$fabrica = 10;

// if (count($_POST)) {
// 	echo '<pre>';print_r($_REQUEST);echo '</pre>';
// 	echo '<pre>';print_r($_FILES); echo '</pre>';
// 	exit;
// }

$faturamento	= $_POST["faturamento"];
if(strlen($faturamento)==0)
	$faturamento = $_GET["faturamento"];

$btn_acao= $_POST["btn_acao"];

$total_qtde_item= (strlen($_POST["total_qtde_item"]) > 0) ? $_POST["total_qtde_item"] : 110;

if(strlen($btn_acao)==0)
	$btn_acao = $_GET["btn_acao"];

$erro_msg_= $_GET["erro_msg"];
//SE NAO FOR O POSTO DE TESTE OU O DISTRIB.
if(($login_posto <> 6359) and ($login_posto <> 4311)){
	echo "NÃO É PERMITIDO LANÇAR NOTA FISCAL - longin: $login_posto";
	exit;
}

$re_match_YMD	= '/(\d{4})\W?(\d{2})\W?(\d{2})/';
$re_match_DMY	= '/(\d{2})\W?(\d{2})\W?(\d{4})/';
$re_format_YMD	= '$3-$2-$1';
$re_format_DMY	= '$3/$2/$1';

$peca_mais = array();
$peca_sem_pedido = array();

$fornecedor_distrib  = (empty($_GET['fornecedor_distrib'])) ? trim($_POST['fornecedor_distrib']) : trim($_GET['fornecedor_distrib'])   ;

if ($btn_acao == "Gravar") {
	$nota_fiscal= trim($_POST['nota_fiscal']);
	$total_nota	= trim($_POST['total_nota']) ;
	$emissao	= trim($_POST["emissao"])    ;
	$saida		= trim($_POST['saida'])      ;
	$condicao	= trim($_POST['condicao'])   ;
	$cfop		= trim($_POST['cfop'])       ;
	$serie		= trim($_POST['serie'])      ;
	$natureza	= trim($_POST['natureza'])   ;
	$transp		= substr($_POST['transportadora'],0,30);
	$fornecedor_distrib       = trim($_POST['fornecedor_distrib']);
	$fornecedor_distrib_posto = trim($_POST['fornecedor_distrib_posto']);
	$base_icms_substtituicao  = trim($_POST['base_icms_substtituicao']);
	$valor_icms_substtituicao = trim($_POST['valor_icms_substtituicao']);
	

	if(strlen($base_icms_substtituicao)==0){
		$base_icms_substtituicao = 0;
	}

	if(strlen($valor_icms_substtituicao)==0){
		$valor_icms_substtituicao = 0;
	}

	if(strlen($nota_fiscal) > 0) {
		$sql = "SELECT faturamento 
		FROM tbl_faturamento 
		WHERE fabrica     = $faturamento_fabrica
		AND   posto       = $login_posto
		AND   nota_fiscal = '$nota_fiscal'";
		$res = pg_query ($con,$sql);

		if(pg_num_rows($res)>0){
			$faturamento = trim(pg_fetch_result($res,0,faturamento));
			header ("Location: nf_cadastro_manual.php?faturamento=$faturamento&erro_msg=Já foi Cadastrado a NF:$nota_fiscal");
			exit;
		}
	}

	if(strlen($nota_fiscal)==0)		$erro_msg .= "Digite a Nota Fiscal" ;
	if(strlen($emissao)==0)			$erro_msg .= "Digite a data de emissão $emissao<br>" ;
	if(strlen($saida)==0)			$erro_msg .= "Digite a Data de Saida<br>" ;
	if(strlen($total_nota)==0)		$erro_msg .= "Digite o Total da Nota<br>" ;
	if(strlen($cfop)==0)			$erro_msg .= "Digite o CFOP<br>";
	if(strlen($serie)==0)			$erro_msg .= "Digite o Número da Série<br>" ;
	if (!$fornecedor_distrib_posto) $erro_msg .= 'Fornecedor esconhecido. Avise o Ger. Ronaldo para cadastrar na Fábrica Telecontrol o posto para que sirva de Fornecedor';
	if(strlen($natureza)==0)		$erro_msg .= "Digite a natureza da operação<br>" ;
	if(strlen($fornecedor_distrib)==0)	$erro_msg .= "Por favor escolha um fornecedor<br>" ;
	if(strlen($transp)==0)			$transp    = "";
	if(strlen($condicao)==0)		$condicao  = "null" ;

	$saida   = preg_replace($re_match_DMY, $re_format_YMD, $saida);
	$emissao = preg_replace($re_match_DMY, $re_format_YMD, $emissao);

	if(strlen($erro_msg) == 0){
		$res = pg_query ($con,"BEGIN TRANSACTION");
		$sql= "INSERT INTO tbl_faturamento 
			(fabrica          ,
			emissao           ,
			conferencia       ,
			saida             ,
			posto             ,
			distribuidor      ,
			total_nota        ,
			cfop              ,
			nota_fiscal       ,
			serie             ,
			transp            ,
			natureza          ,
			obs               ,
			base_icms_substtituicao,
			valor_icms_substtituicao
		)VALUES (
			$faturamento_fabrica,
			'$emissao'          ,
			CURRENT_TIMESTAMP   ,
			'$saida'            ,
			$login_posto        ,
			$fornecedor_distrib_posto ,
			$total_nota         ,
			'$cfop'             ,
			'$nota_fiscal'      ,
			'$serie'            ,
			'$transp'           ,
			'$natureza'         ,
			'$condicao'         ,
			$base_icms_substtituicao,
			$valor_icms_substtituicao
		)
		;";
		$res = pg_query ($con,$sql);
		if (!is_resource($res)) $erro_msg.= "Erro ao INSERIR nova NF.";

		$somatoria_nota = 0;
		if(strlen($erro_msg) > 0){
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
			$erro_msg="<br>Erro ao inserir a NF:$nota_fiscal<br>$erro_msg";
		}else{
			$res = pg_query ($con,"SELECT CURRVAL ('seq_faturamento') as fat;");
			$faturamento =trim (pg_fetch_result($res, 0 , fat));

			for($i=0; $i< $total_qtde_item; $i++){
				$erro_item  = "" ;
				$referencia = $_POST["referencia_$i"];
				$descricao  = $_POST["descricao_$i"];
				$qtde       = $_POST["qtde_$i"];
				$preco      = $_POST["preco_$i"];
				$cfop       = $_POST["cfop_$i"];
				$pedido     = $_POST["pedido_$i"]; 
				$aliq_icms  = $_POST["aliq_icms_$i"]; 
				$aliq_ipi   = $_POST["aliq_ipi_$i"]; 
				$base_icms  = $_POST["base_icms_$i"]; 
				$base_ipi   = $_POST["base_ipi_$i"]; 
				$valor_ipi  = $_POST["valor_ipi_$i"]; 
				$valor_icms = $_POST["valor_icms_$i"]; 
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
							peca       ,
							qtde       ,
							preco      ,
							cfop       ,
							aliq_icms  ,
							aliq_ipi   ,
							base_icms  ,
							valor_icms ,
							base_ipi   ,
							valor_ipi
						)VALUES(
							$faturamento,
							$peca       ,
							$qtde       ,
							$preco      ,
							$cfop       ,
							$aliq_icms  ,
							$aliq_ipi   ,
							$base_icms  ,
							$valor_icms ,
							$base_ipi   ,
							$valor_ipi
						)";
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
			
			$somatoria_nota += $valor_icms_substtituicao;

			$somatoria_nota = trim(str_replace(".00","",$somatoria_nota));
			if ($somatoria_nota != $total_nota) {
				$erro_msg .= "Valor Total da Nota diferente do valor da somat&oacute;ria dos &iacute;tens da nota (soma do sistema $somatoria_nota) (total digitado $total_nota)<br>";
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
			}else{
				$res = pg_query ($con,"ROLLBACK TRANSACTION");
				$faturamento = "";
			}
		}//else erro inserir faturamento
	}
}//FIM BTN: GRAVAR

/* Processo de NFe. 
if ($btn_acao == "NFe") {   //  Processa NFe
	require_once 'includes/xml2array.php';   // Carrega as duas funções para tratar mais fácil com XML...
	if ($_FILES['Nfe_File']['tmp_name'] != '') {
		$xml_file = $_FILES['Nfe_File'];
// 		echo '<pre>'.var_dump($_FILES).'</pre>';
		if (strpos($_FILES['Nfe_File']['type'],'xml') !== false) {
			$arr_nf = xml2array($_FILES['Nfe_File']['tmp_name']);
// 			echo "<pre>";
// 			print_r($arr_nf);
// 			echo "</pre>";
			if (count($arr_nf) != 0) {
				$idx_nf			= "nfeProc/NFe/infNFe";
				$idx_nf_info	= $idx_nf."/ide";
				$idx_nf_emissor	= $idx_nf."/emit";
				$idx_nf_destino	= $idx_nf."/dest";
				$idx_nf_itens	= $idx_nf."/det";
				$nfe_id			= array_get_value($arr_nf,'nfeProc/NFe/infNFe_attr/Id');
				$cnpj_distrib	= array_get_value($arr_nf,$idx_nf_destino.'/CNPJ');

//  Dados da NF:
				$fornecedor_cnpj	= array_get_value($arr_nf, $idx_nf_emissor.'/CNPJ');
				$fornecedor_distrib = array_get_value($arr_nf, $idx_nf_emissor.'/xNome');
				$nota_fiscal= array_get_value($arr_nf, $idx_nf_info.'/nNF');
				$total_nota	= array_get_value($arr_nf, $idx_nf.'/total/ICMSTot/vNF');
				$emissao	= array_get_value($arr_nf, $idx_nf_info.'/dEmi');
				$emissao	= preg_replace($re_match_YMD, $re_format_DMY, $emissao);
				$saida		= $emissao;
				$serie		= array_get_value($arr_nf, $idx_nf_info.'/serie');
				$natureza	= array_get_value($arr_nf, $idx_nf_info.'/natOp');
				$transp		= array_get_value($arr_nf, $idx_nf.'/transp/transporta/xNome');
				$base_icms_substtituicao  = array_get_value($arr_nf, $idx_nf.'/total/ICMSTot/vBCST');
				$valor_icms_substtituicao = array_get_value($arr_nf, $idx_nf.'/total/ICMSTot/vST');
				$pedido     = array_get_value($arr_nf, $idx_nf_info.'/compra/xPed');

		$sql_f = "SELECT posto, nome FROM tbl_posto JOIN tbl_posto_extra USING(posto) ".
				 "WHERE tbl_posto_extra.fornecedor_distrib IS TRUE ".
				 "AND cnpj = '$fornecedor_cnpj' ";
		$res_f = pg_query($con, $sql_f);
		if (!is_resource ($res_f)) {
		    $erro_msg .= 'Erro ao pesquisar o Fornecedor. Tente novamente. Se continuar o erro, avise a Equipe Telecontrol.';
		} else {
		    if (@pg_num_rows($res_f) == 1) {
		        $fornecedor_distrib_posto	= pg_fetch_result($res_f, 0, posto);
// 		        $fornecedor_distrib         = pg_fetch_result($res_f, 0, nome); // Substitui a razão social que vem na NFe pela do banco
			} else {
			    $erro_msg .= 'Fornecedor esconhecido. Avise o Ger. Ronaldo para cadastrar na Fábrica Telecontrol o posto para que sirva de Fornecedor';
			}
		}


//  Valida o CNPJ do emissor...
				$sql = "SELECT posto,nome
						FROM tbl_posto
						LEFT JOIN tbl_posto_extra using(posto)
						WHERE tbl_posto_extra.fornecedor_distrib IS TRUE
						  AND cnpj = '$fornecedor_cnpj'";
				$res = @pg_query($con,$sql);
				if (!is_resource($res)) $erro_msg = "<p>ERRO NA CONSULTA DE FORNECEDOR!</p><p>".pg_last_error($con).'</p>';
				if (is_resource($res) and @pg_num_rows($res)==0) {
				    $erro_msg = "Fornecedor com CNPJ $fornecedor_cnpj não encontrado. Contate com o gerente Ronaldo.";
				}


				if(strlen($nota_fiscal) > 0 and $faturamento_fabrica) {
					$sql = "SELECT faturamento
					FROM tbl_faturamento
					WHERE fabrica     = $faturamento_fabrica
					AND   posto       = $login_posto
					AND   nota_fiscal = '$nota_fiscal'";
					$res = pg_query($con,$sql);

					if(pg_num_rows($res)>0){
						$faturamento = trim(pg_fetch_result($res,0,faturamento));
						header ("Location: nf_cadastro.php?faturamento=$faturamento&erro_msg=Já foi Cadastrado a NF:$nota_fiscal");
						exit;
					}
				}

//  Itens da Nota Fiscal
				$Itens_NF = array_get_value($arr_nf, $idx_nf_itens);
				if (!is_int(key($Itens_NF))) {$Itens = array(0 => $Itens_NF);}
				    else {$Itens = $Itens_NF;}
				$i = 0;
				foreach ($Itens as $numItem => $arr_item) {
				    if (is_int($numItem)) {
						$imposto_ICMS	= array_get_value($arr_item,'imposto/ICMS/ICMS00');
						$imposto_IPI	= array_get_value($arr_item,'imposto/IPI/IPINT');
						$nfe_itens[$i]['referencia']= $arr_item['prod']['cProd'];
						$nfe_itens[$i]['descricao']	= $arr_item['prod']['xProd'];
						$nfe_itens[$i]['qtde']		= $arr_item['prod']['qCom'];
						$nfe_itens[$i]['preco']		= $arr_item['prod']['vUnCom'];
						$nfe_itens[$i]['cfop']		= $arr_item['prod']['CFOP'];
						$nfe_itens[$i]['base_icms']	= $imposto_ICMS['vBC'];
						$nfe_itens[$i]['aliq_icms']	= $imposto_ICMS['pICMS'];
						$nfe_itens[$i]['valor_icms']= $imposto_ICMS['vICMS'];
						$nfe_itens[$i]['base_ipi']	= $imposto_ICMS['vBC'];
						$nfe_itens[$i]['aliq_ipi']	= 0;    //  Na NFe de amostra não tinha pIPI ou vIPI, cód.52 (saída isenta)...
						$nfe_itens[$i]['valor_ipi']	= 0;
						$somatoria_nota += ($nfe_itens[$i]['preco'] * $nfe_itens[$i]['qtde']) + $nfe_itens[$i]['valor_ipi'];
						$i++;
					}
				}
				$cfop = $nfe_itens[--$i]['cfop']; // Por enquanto...
// 				echo "CFOP da NF: $cfop<br>";
			} else {
			    $erro_msg = 'Não foi possível interpretar o arquivo '.$xml_file['name'];
			}
		} else {
			$erro_msg = 'O arquivo '.$xml_file['name'].' não parece um XML.';
	    }
	} else {
		$erro_msg = 'Arquivo XML não recebido';
	}
}// FIM Processa NFe enviada pela Britânia
*/

#-------------------- Pesquisa Posto -----------------
$posto_codigo = trim($_POST["posto_codigo"]);
if(strlen($posto_codigo)> 0){
	$sql = "SELECT  tbl_posto.cnpj                        ,
					tbl_posto.ie                          ,
					tbl_posto.nome                        ,
					tbl_posto.numero                      ,
					tbl_posto.cep                         ,
					tbl_posto.estado                      ,
					tbl_posto.fone                        ,
					tbl_posto.nome_fantasia               
			FROM    tbl_posto
			WHERE   tbl_posto.posto = $posto_codigo;";
	$res = pg_exec ($con,$sql);
		
	if (@pg_numrows ($res) > 0) {
		$cnpj_revenda             = trim(pg_result($res,0,cnpj));
		if (strlen($cnpj_revenda) == 14) {
			$cnpj_revenda = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
		}
		if (strlen($cnpj_revenda) == 11) {
			$cpf_consumidor = substr($cnpj,0,3) .".". substr($cnpj,3,3) .".". substr($cnpj,6,3) ."-". substr($cnpj,9,2);
		}
		$ie_consumidor               = pg_result($res,0,ie);
		$numero_consumidor           = pg_result($res,0,numero);
		$consumidor_cep              = pg_result($res,0,cep);
		$fone_consumidor             = pg_result($res,0,fone);
		$fornecedor_distrib 		 = pg_result($res,0,nome_fantasia);
	}
}
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
	$(".money").maskMoney({symbol:"", decimal:",", thousands:'.', precision:2, maxlength: 15});
	
	
	
	$(".tipoPessoa").click(function(){
		if ($("#tipoDestFisica").is(":checked")){
		$("#cnpj_revenda").hide();
		$("#cpf_consumidor").show();
		$("#label_Cpf_Cnpj").text("CPF");
		}
	
		if ($("#tipoDestJuridica").is(":checked")){
			$("#cpf_consumidor").hide();
			$("#cnpj_revenda").show();
			$("#label_Cpf_Cnpj").text("CNPJ");
		}
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

function exibirFat(componente,conta_pagar, documento, acao) {
	var nota_fiscal = document.getElementById('nota_fiscal').value;
	var fabrica     = document.getElementById('fabrica').value;

	if (nota_fiscal.length > 0) {
		url = "nf_cadastro_manual_ajax?ajax=sim&nota_fiscal="+escape(nota_fiscal)+"&fabrica="+fabrica;
		http.open("GET", url , true);
		http.onreadystatechange = function () { retornaFat (http,componente,nota_fiscal) ; } ;
		http.send(null);
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
		$("#"+numero).after("<tr style='font-size: 12px' bgcolor='"+cor+"' id="+numero2+">\n<td align='right' nowrap>"+numero2+"</td>\n<td align='right' nowrap><input type='text' class='frm' name='referencia_"+numero+"' id='referencia_"+numero+"' value='' size='10' maxlength='20' rel='referencia' alt='descricao_"+numero+"' ;'></td>\n<td align='right' nowrap><input type='text' class='frm' name='descricao_"+numero+"' id='descricao_"+numero+"' alt='referencia_"+numero+"' value='' size='10' maxlength='20' rel='descricao' ></td>\n <td align='right' nowrap><input class='frm' type='text' name='qtde_"+numero+"' class='qtde_prod' id='qtde_"+numero+"' value='' size='5' maxlength='10' onKeyUp='calc_base_icms("+numero+");' onblur=\"checarNumero(this);\"></td>\n<td align='right' nowrap><input class='frm' type='text' name='preco_"+numero+"' id='preco_"+numero+"' value='' size='5' maxlength='12' onKeyUp='calc_base_icms("+numero+");' onblur=\"checarNumero(this);\"></td>\n<td align='right' nowrap><input class='frm' type='text' size='5' maxlength='12' name='cfop_"+numero+"' id='cfop_"+numero+"' value='<?=$cfop?>'></td>\n<td align='right' nowrap><input class='frm' type='text' name='aliq_icms_"+numero+"' id='aliq_icms_"+numero+"' value='' size='5' maxlength='10' onKeyUp='calc_base_icms("+numero+");' onblur=\"checarNumero(this);\"></td>\n<td align='right' nowrap><input class='frm' type='text' name='aliq_ipi_"+numero+"' id='aliq_ipi_"+numero+"' value='' size='5' maxlength='10' onKeyUp='calc_base_icms("+numero+");' onblur=\"checarNumero(this); addTr("+numero2+")\"></td>\n<td align='right' nowrap><input class='frm' type='text' name='base_icms_"+numero+"' id='base_icms_"+numero+"' value='' size='5' maxlength='10' style='background-color: "+cor+"; border: none;' onfocus='form_nf.referencia_"+numero2+".focus();' readonly></td>\n<td align='right' nowrap><input class='frm' type='text' name='valor_icms_"+numero+"' id='valor_icms_"+numero+"' value='' size='5' maxlength='10' style='background-color: "+cor+"; border: none;' onfocus='form_nf.referencia_"+numero2+".focus();' readonly></td>\n<td align='right' nowrap><input class='frm' type='text' name='base_ipi_"+numero+"' id='base_ipi_"+numero+"' value='' size='5' maxlength='10' style='background-color: "+cor+"; border: none;' onfocus='form_nf.referencia_"+numero2+".focus();' readonly></td>\n<td align='right' nowrap><input class='frm' type='text' name='valor_ipi_"+numero+"' id='valor_ipi_"+numero+"' value='' size='5' maxlength='10' style='background-color: "+cor+"; border: none;' onfocus='form_nf.referencia_"+numero2+".focus();' readonly></td>\n</tr>\n");
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

$().ready(function() {
	$("#fornecedor_distrib").autocomplete("nf_cadastro_manual_ajax_busca.php?tipo=fornecedor", {
		minChars: 2,
		delay: 0,
		width: 350,
		max:50,
		matchContains: true,
		formatItem: function(row, i, max) {
			$("#fornecedor_distrib").focus();
			return row[0] ;
		},
		formatResult: function(row) {
		$("#fornecedor_distrib").focus();
			return row[0];
		}
	});

	$("#fornecedor_distrib").result(function(event, data, formatted) {
		$("#fornecedor_distrib").focus();
		$('#fornecedor_distrib_posto').val(data[1]);
	});

	
	
	
	
	$("#transportadora").autocomplete("nf_cadastro_manual_ajax_busca.php?tipo=transportadora", {
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

	$("#condicao").autocomplete("nf_cadastro_manual_ajax_busca.php?tipo=condicao", {
		minChars: 1,
		delay: 0,
		width: 350,
		max:50,
		matchContains: true,
		formatItem: function(row, i, max) {
			return row[0] ;
		},
		formatResult: function(row) {return row[0];}
			});

	$("#condicao").result(function(event, data, formatted) {
		$(this).focus();
	});

	$("input[type='text']").keydown(function(event) {
		if (event.keyCode == 13) {
			event.preventDefault();
		}
	});

	autocompletaCampos();


})




</script>

<script type="text/javascript" charset="utf-8">
function fnc_pesquisa_posto(campo, campo2, tipo, fabrica) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}


	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_distrib_375261.php?campo=" + xcampo.value + "&tipo=" + tipo + "&fabrica=" + fabrica ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}else{
        alert("Informar toda ou parte da informação para realizar a pesquisa!");
    }
	
}
   
$(function() {
        $("#cpf_consumidor").numeric();
        $("#cnpj_revenda").numeric();
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

<script type="text/javascript" charset="utf-8">


    $(function() {
        $("#cpf_consumidor").numeric();
        $("#cnpj_revenda").numeric();
    });


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
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.subtitulo{
    background-color: #7092BE;
    font:bold 12px Arial;
    color: #FFFFFF;
}

</style>
</head>


<body>

<? include 'menu.php';?>

<center><h1>Cadastro de Notas Fiscais</h1></center>
<!--	
<p>

<form name="NFeForm" id="NFeForm" action="<?//=$PHP_SELF?>" method="post" title="Inserir NF-e" accept="text/xml"	accept-charset="iso-8859-1" enctype="multipart/form-data">
	<table width='700' align='center' class="formulario" id='NFeTable' cellpadding="0" cellspacing="0">
	
<?// if ($_FILES['Nfe_File']['tmp_name'] != '' and $erro_msg == '') { ?>
		<tr>
		    <td colspan='2'>
		        <p>Foi processado o arquivo <?//=$_FILES['Nfe_File']['name']?>,
				que contém a NF-e nº <?//=$nota_fiscal?> com ID: <b><?//=$nfe_id?></b></p>
			</td>
		</tr>
<?//}?>	
		<tr >
			<td colspan="100%" class="titulo_tabela">Upload NF-e</td>
		</tr>
		
		<tr>
		    <td align="center"><br>
				
				Para inserir uma NF-e, selecione o arquivo e clique em 'NFe'
			</td>
		<tr>
		    <td align="center">
		            <input  type="file"		id="Nfe_File" accept="text/xml"  name="Nfe_File"
						   title="Selecione o arquivo da NFe" class='frm'>
		            
			</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td colspan="100%" align="center"><br>
				<input type="submit"	id='btn_acao'	name='btn_acao' value="NFe" title='Processar NF-e' style='cursor:pointer'>

			</td>
		</tr>
		<tr><td>&nbsp;</td></tr>
	</table>
</form>
-->
<table class="formulario" width="700px" align="center" cellpadding="0" cellspacing="0">
	
	<tr>
		<th class="titulo_tabela">Inserir Nova Nota Fiscal</th>
	</tr>
	
	<tr><td>&nbsp;</td></tr>

	<tr>
		<td colspan = '4' align='center' nowrap>
			<a href='nf_cadastro_manual_375261.php?novo=novo'>
				<input type="button" value="Nova Nota Fiscal">
			</a>
		</td>
	</tr>
	
	<tr><td>&nbsp;</td></tr>
</table>


<table width="700px" align="center" class="msg_erro">
	<?
	$erro_msg_= $erro_msg;
	if ($erro_msg_) {
	?>
	<tr>
		<td>
			<p style='text-align:center;color:white;font-weight:bold'><?=$erro_msg_?></p>
		</td>
	</tr>
	<?}?>
</table>

<br>

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

				<?
				if(strlen($erro_msg)==0 AND strlen($faturamento) > 0 ){
					$sql = "SELECT	tbl_faturamento.faturamento                                          ,
					tbl_fabrica.fabrica                                                  ,
					tbl_fabrica.nome                                   AS fabrica_nome   ,
					tbl_faturamento.nota_fiscal                                          ,
					tbl_faturamento.natureza                                             ,
					TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY')     AS emissao        ,
					TO_CHAR (tbl_faturamento.saida,'DD/MM/YYYY')       AS saida          ,
					TO_CHAR (tbl_faturamento.conferencia,'DD/MM/YYYY') AS conferencia    ,
					TO_CHAR (tbl_faturamento.cancelada,'DD/MM/YYYY')   AS cancelada      ,
					tbl_faturamento.cfop                                                 ,
					tbl_faturamento.serie                                                ,
					tbl_faturamento.condicao                                             ,
					tbl_faturamento.transp                                               ,
					tbl_transportadora.nome                            AS transp_nome    ,
					tbl_transportadora.fantasia                        AS transp_fantasia,
					to_char (tbl_faturamento.total_nota,'999999.99')   AS total_nota     ,
					to_char (tbl_faturamento.valor_icms_substtituicao,'999999.99')   AS valor_icms_substtituicao     ,
					to_char (tbl_faturamento.base_icms_substtituicao,'999999.99')   AS base_icms_substtituicao     ,
					tbl_condicao.descricao                                               ,
					tbl_faturamento.obs                                                  ,
					tbl_posto.nome as distribuidor                                       
					FROM      tbl_faturamento
					JOIN      tbl_fabrica        USING (fabrica)
					LEFT JOIN tbl_transportadora USING (transportadora)
					LEFT JOIN tbl_condicao       USING (condicao)
					LEFT JOIN tbl_posto ON tbl_posto.posto = tbl_faturamento.distribuidor
					WHERE   tbl_faturamento.posto       = $login_posto
					AND     tbl_faturamento.faturamento = $faturamento
					ORDER BY tbl_faturamento.emissao     DESC,
					 tbl_faturamento.nota_fiscal DESC";
					$res = pg_query ($con,$sql);
					$erro_msg = pg_last_error($con);
					if(strlen($erro_msg) > 0) $erro_msg.= "<font color='#ff0000'>Erro ao consultar faturamento!</font>";
					if(pg_num_rows($res)>0){
						$conferencia      = trim(pg_fetch_result($res,0,conferencia)) ;
						$faturamento      = trim(pg_fetch_result($res,0,faturamento)) ;
						$fabrica          = trim(pg_fetch_result($res,0,fabrica)) ;
						$fabrica_nome     = trim(pg_fetch_result($res,0,fabrica_nome)) ;
						$nota_fiscal      = trim(pg_fetch_result($res,0,nota_fiscal));
						$emissao          = trim(pg_fetch_result($res,0,emissao));
						$saida            = trim(pg_fetch_result($res,0,saida));
						$cancelada        = trim(pg_fetch_result($res,0,cancelada));
						$cfop             = trim(pg_fetch_result($res,0,cfop));
						$serie            = trim(pg_fetch_result($res,0,serie));
						$condicao         = trim(pg_fetch_result($res,0,condicao));
						$transp           = trim(pg_fetch_result($res,0,transp));
						$natureza         = trim(pg_fetch_result($res,0,natureza));
						$transp_nome      = trim(pg_fetch_result($res,0,transp_nome));
						$transp_fantasia  = trim(pg_fetch_result($res,0,transp_fantasia));
						$total_nota       = trim(pg_fetch_result($res,0,total_nota));
						$descricao        = trim(pg_fetch_result($res,0,descricao));
						$obs              = trim(pg_fetch_result($res,0,obs));
						$fornecedor_distrib= trim(pg_fetch_result($res,0,distribuidor));
						$base_icms_substtituicao  = trim(pg_fetch_result($res,0,base_icms_substtituicao));
						$valor_icms_substtituicao = trim(pg_fetch_result($res,0,valor_icms_substtituicao));
						

						$condicao = (!empty($condicao)) ? $descricao : $obs;
					}else{
						$faturamento="";
					}
				}else{
					if (count($_FILES) == 0) {  // Só recarregar o formulário se NÃO houver upload de NFe
						$nota_fiscal = trim($_POST['nota_fiscal']);
						$emissao     = $_POST["emissao"]          ;
						$saida       = $_POST['saida']            ;
						$total_nota  = $_POST['total_nota']       ;
						$cfop        = $_POST['cfop']             ;
						$serie       = $_POST['serie']            ;
						$transp      = $_POST['transportadora']   ;
						$condicao    = $_POST['condicao']         ;
						$base_icms_substtituicao   = $_POST['base_icms_substtituicao']       ;
						$valor_icms_substtituicao  = $_POST['valor_icms_substtituicao']       ;
					}
				}
				
					if (strlen ($transp_nome) > 0)     $transp = $transp_nome;
					if (strlen ($transp_fantasia) > 0) $transp = $transp_fantasia;
					$transp = strtoupper ($transp);
				
				?>
				<tr>
					<td align="left" >
						Tipo de Nota Fiscal
					</td>
				</tr>
				<tr>
					<td>
						<select style="width:100%;" class='frm'>
							<option value="">Entrada</option>
							<option value="">Saída</option>
						</select>
					</td>
				</tr>

				
				<tr>
					<td>Data Emissão</td>
					<td>Data Saida</td>
				</tr>
				
				<tr>
					<td align='center'>
						<input  type='text' class='frm' name='emissao' id='emissao' value='<?=$emissao?>' size='10'   maxlength='10' style="width:89%;text-align:center" >
					</td>
					
					<td align='center'>
						<input type='text' class='frm' name='saida' id='saida' value='<?=$saida?>' size='10' maxlength='10' style="width:89%;text-align:center" >
					</td>
				</tr>
				
				<tr>
				</tr>
				<tr>
					<td style="width:25%">Nota Fiscal</td>
					<td style="width:25%">Série</td>
					<td style="width:25%">Natureza</td>
					<td style="width:25%" title='CFOP da NF: $cfop'>CFOP</td>
				</tr>
				
				
				
				
				<tr>
					<td>
						<input type='text' class='frm' name='nota_fiscal' id='nota_fiscal' value='<?=$nota_fiscal?>' style='width:100%' size='8'  maxlength='8' onBlur=\"exibirFat('dados','','','alterar')\"><br><div name='f2' id='f2' class='carregar'></div>
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
		<th class="subtitulo" align="center" colspan="100%">Dados do Destinatário</th>
	</tr>
	
	<tr>
		<td>
			<table cellpadding="0" cellspacing="2" width="600px" align="center" border="0">
				<!-- Dados do Destinatário -->
				
				<tr>
					<td>
						Os
						
					</td>
					
					<td>
						Posto
						
					</td>
				</tr>
				
				<tr>
					<td>
						<input type="text" name="os_text" id="os_text" class="frm" style="width:86%" />
						<img src="../imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle">
					</td>
					
					<td>
						<input class="frm" type="text" name="posto_codigo" id="posto_codigo" size="15" value="<? echo $posto_codigo ?>">
						<img src='../imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto (document.form_nf.posto_codigo,'','codigo',10)">
					</td>
				</tr>
				
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
					<td style="width:25%">Tipo</td>
					<td style="width:25%"><label id="label_Cpf_Cnpj">CPF</label></td>
					<td style="width:25%">IE</td>

				</tr>
				
				<tr>
					<td>
						<input type="radio" name="tipoDest" id="tipoDestFisica" class="tipoPessoa" checked />Física
						<input type="radio" name="tipoDest" id="tipoDestJuridica" class="tipoPessoa"  />Jurídica
					</td>
					
					<td>
						<input type="text" name="cpf_consumidor" id="cpf_consumidor" onkeypress="mascara_cpf(this, event);" size="17" maxlength="14" class="frm"  value="<?php echo $cpf_consumidor ?>" onfocus="formata_cpf_cnpj(this,1)" style="width:100%" />
						<input type="text" name="cnpj_revenda" id="cnpj_revenda" onkeypress="mascara_cnpj(this, event);" onfocus="formata_cpf_cnpj(this,2);" class="frm" size="22" maxlength="18" value="<?php echo $cnpj_consumidor?>" style="display:none;" />
					</td>
					
					<td>
						<input type="text" style="width:100%" class="frm" name="ie_consumidor" id="ie_consumidor" value="<?=$ie_consumidor?>" maxlength="16">
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
						<input type="text" class="frm" style="width:100%" name="complemento_consumidor" id="complemento_consumidor" maxlength="32" >
					</td>
					
					<td>
						<input type="text" class="frm" style="width:100%" name="bairro_consumidor" id="bairro_consumidor" maxlength="32">
					</td>
					
					<td>
						<input type="text" class="frm" style="width:100%" name="cidade_consumidor" id="cidade_consumidor" maxlength="32" >
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
						<?php
							foreach ($array_estado as $k => $v) {
							echo '<option value="'.$k.'"'.($estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
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
	
	<tr>
		<td>&nbsp;</td>
	</tr>
	
	<tr>
		<th class="subtitulo" align="center" colspan="100%">Dados do Transporte</th>
	</tr>
	
	<tr>
		<td>&nbsp;</td>
	</tr>
	
	<tr>
		<td>
			<table cellpadding="0" cellspacing="2" width="600px" align="center" class="formulario" border="0">
				
				
				<tr>
					<td>Transportadora</td>
					<td>Tipo Frete</td>
				</tr>
				
				<tr>
					<td>
						<input class="frm" type='text' name='transportadora' id='transportadora' value='<?=$transp?>' style="width:100%" maxlength='30'>
					</td>
					<td style="width:148px">
						<select class="frm" name="tipo_frete" style="width:100%"  id="tipo_frete">
							<option value=""></option>
							
							<option value="1">Emitente</option>
							<option value="2">Destinatário</option>
							
						</select>
					</td>
				</tr>
			</table>
		</td>
	</tr>	

	<tr>
		<td>&nbsp;</td>
	</tr>
	
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
					<!--
					<td align='center' title='Colocar neste campo o valor total da Nota (total das peças/produtos + impostos).'>Valor Total NF(?)</td>
					-->
				</tr>
				
				<tr>
					<td>
						<input style='width:100%;text-align:right' class="frm money" type='text' name='base_icms_substtituicao' id='base_icms_substtituicao' value='<?=$base_icms_substtituicao?>'  size='10'  maxlength='12' title='Colocar neste campo o valor Base de ICMS de  Substituição Tributária.'></td>
					</td>
					<td nowrap>
						<input style='width:100%;text-align:right' class="frm money" type='text' name='valor_icms_substtituicao' id='valor_icms_substtituicao' value='<? $valor_icms_substtituicao ?>'  size='10'  maxlength='12' title='Colocar neste campo o valor ICMS de Substituição Tributária'></td>
					</td>
					<td><input type="text" class="frm money" style='width:100%;text-align:right' name="valor_frete" id="valor_frete" maxlength="12" /></td>
					<!--
					<td align='center' nowrap >
						<input type='text' class='frm' name='total_nota' id='total_nota' value='$total_nota'  size='10'  maxlength='12' onblur=\"checarNumero(this);\" ></td>
					<td align='right' nowrap></td>
					-->
				</tr>
				
				<tr>
					<td>Valor Desconto</td>
					<td>Outros Valores</td>
					<td>Seguro</td>
				</tr>
				
				<tr>
					<td>
						<input type="text" value="" name="valor_desconto" id="valor_desconto" style='width:100%;text-align:right' class="frm money"/>
					</td>
					
					<td>
						<input type="text" value="" name="outros_valores" id="outros_valores" style='width:100%;text-align:right' class="frm money"/>
					</td>
					
					<td>
						<input type="text" value="" name="valor_seguro" id="valor_seguro" style='width:100%;text-align:right' class="frm money"/>
					</td>
				</tr>
			</table>
		
		</td>
	</tr>
	
	<tr>
		<td>&nbsp;</td>
	</tr>
	
	<tr>
		<th class="subtitulo" align="center" colspan="100%">Observações</th>
	</tr>
	
	<tr>
		<td>
			<table cellpadding="0" cellspacing="2" width="600px" align="center" class="formulario" border="0">
				<tr>
					<td>
						<textarea class="frm" cols="20" rows="2" name='nf_obs' id='nf_obs' style="width:100%">
						
						</textarea>
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
	<td align='center'>#</td>
	<td align='center'>Peça/Produto</td>
	<td align='center'>Descrição</td>
	<td align='center'>Qtde</td>
	<td align='center'>Preço</td>
	<?
	if(strlen($faturamento)>0)
echo "<td align='center'>Subtotal</td>";
	?>
	<td align='center' title='Adicionada coluna de CFOP por ítem, novo padrão para NF-e'>CFOP</td>
	<td align='center'>Aliq. ICMS</td>
	<td align='center'>Aliq. IPI</td>
	<td align='center'>Base Icms</td>
	<td align='center'>Valor ICMS</td>
	<td align='center'>Base IPI</td>
	<td align='center'>valor IPI</td>
	<input type='hidden' name='total_qtde_item' id='total_qtde_item' value='<?=$total_qtde_item?>'>
</tr>
<tr id='0'><td colspan='100%'></td></tr>
<?

//SE NAO EXISTIR FATURAMENTO ENTAO NAO MOSTRA OS ITENS DA NOTA FISCAL OU NF-e
if(strlen($faturamento)==0){
	for ($i = 0 ; $i < 5 ; $i++) {
//INSERIR ITENS DA NOTA
		if (count($_FILES) == 0) {
			$referencia     = $_POST["referencia_$i"]  ;
			$descricao      = $_POST["descricao_$i"]  ;
			$qtde           = $_POST["qtde_$i"]        ;
			$preco          = $_POST["preco_$i"]       ;
			$cfop			= $_POST["cfop_$i"]       ;
			$aliq_icms      = $_POST["aliq_icms_$i"]   ;
			$aliq_ipi       = $_POST["aliq_ipi_$i"]    ;
			$base_icms      = $_POST["base_icms_$i"]   ;
			$valor_icms     = $_POST["valor_icms_$i"]   ;
			$base_ipi       = $_POST["base_ipi_$i"]     ;
			$valor_ipi      = $_POST["valor_ipi_$i"]    ;
		} else {
//10/03/2010 MLG - INSERIR ÍTENS DA NF-e
			$referencia     = $nfe_itens[$i]['referencia'];
			$descricao      = $nfe_itens[$i]['descricao'];
			$qtde           = $nfe_itens[$i]['qtde'];
			$preco          = $nfe_itens[$i]['preco'];
			$cfop           = $nfe_itens[$i]['cfop'];
			$aliq_icms      = $nfe_itens[$i]['aliq_icms'];
			$aliq_ipi       = $nfe_itens[$i]['aliq_ipi'];
			$base_icms      = $nfe_itens[$i]['base_icms'];
			$valor_icms     = $nfe_itens[$i]['valor_icms'];
			$base_ipi       = $nfe_itens[$i]['base_ipi'];
			$valor_ipi      = $nfe_itens[$i]['valor_ipi'];
		}

		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
		$qtde_linha = $i+ 1;

		echo "<tr style='font-size: 12px' bgcolor='$cor' id='$qtde_linha'>\n";
		echo "<td align='right' nowrap>".($i+1)."</td>\n";
		echo "<td align='right' nowrap>".
			 "<input type='text' class='frm' name='referencia_$i' id='referencia_$i' ".
			 "value='$referencia' size='10' maxlength='20' rel='referencia' alt='descricao_$i'".
			 " onBlur='setFocus(\"$i\");'></td>\n";
		echo "<td align='right' nowrap>".
			 "<input type='text' class='frm' name='descricao_$i' id='descricao_$i' ".
			 "alt='referencia_$i' value='$descricao' size='10' maxlength='20' rel='descricao'".
			 " onBlur='setFocus(\"$i\");'></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' size='5' maxlength='10' name='qtde_$i'		id='qtde_$i'		value='$qtde'		class='qtde_prod' onKeyUp='calc_base_icms($i);' onblur=\"checarNumero(this);\"></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' size='5' maxlength='12' name='preco_$i'		id='preco_$i'		value='$preco'		onKeyUp='calc_base_icms($i);' onblur=\"checarNumero(this);\"></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' size='5' maxlength='12' name='cfop_$i'		id='cfop_$i'		value='$cfop'></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' size='5' maxlength='10' name='aliq_icms_$i'	id='aliq_icms_$i'	value='$aliq_icms'	onKeyUp='calc_base_icms($i);' onblur=\"checarNumero(this);\"></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' size='5' maxlength='10' name='aliq_ipi_$i'	id='aliq_ipi_$i'	value='$aliq_ipi'	onKeyUp='calc_base_icms($i);' onblur=\"checarNumero(this); addTr($qtde_linha)\"></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' size='5' maxlength='10' name='base_icms_$i'	id='base_icms_$i'	value='$base_icms'	style='background-color: $cor; border: none;' onfocus='form_nf.referencia_".($i+1).".focus();' readonly></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' size='5' maxlength='10' name='valor_icms_$i'	id='valor_icms_$i'	value='$valor_icms' style='background-color: $cor; border: none;' onfocus='form_nf.referencia_".($i+1).".focus();' readonly></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' size='5' maxlength='10' name='base_ipi_$i'	id='base_ipi_$i'	value='$base_ipi'	style='background-color: $cor; border: none;' onfocus='form_nf.referencia_".($i+1).".focus();' readonly></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' size='5' maxlength='10' name='valor_ipi_$i'	id='valor_ipi_$i'	value='$valor_ipi'	style='background-color: $cor; border: none;' onfocus='form_nf.referencia_".($i+1).".focus();' readonly></td>\n";
		echo "</tr>\n";
	}
}
echo "</table>";



echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td colspan='5' align='center'>";
echo "<input type='hidden' name='qtde_item' value='$i'>";
echo "<input type='hidden' name='faturamento' value='$faturamento'>";
echo "</td>";
echo "</tr>";

if(strlen($faturamento)>0)
	$desc_bt="Alterar";
else	
	$desc_bt="Gravar";	

echo "<tr>";
echo "<td colspan='12' align='center'>";
if(strlen($faturamento)==0) {
	echo "<input type='hidden' name='btn_acao' value=''>";
	echo "<input type='button' name='btn_grava' value='$desc_bt' onclick='javascript: document.form_nf.btn_acao.value=\"$desc_bt\"; document.form_nf.submit()'>";
}
echo "</td>";
echo "</tr>";

echo "</table>\n";
echo "</form>";
?>

<p>

<? #include "rodape.php"; ?>

</body>
</html>