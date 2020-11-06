<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if (strlen($_GET['os']) > 0) {
	$os   = $_GET['os'];
	$modo = $_GET['modo'];
}elseif (strlen($_GET['branco']) > 0) {
	$branco = $_GET['branco'];
}elseif (strlen($os)==0){
	header ("Location: os_parametros.php");
	exit;
}

#------------ Le OS da Base de dados ------------#
if (strlen ($os) > 0) {
	$sql = "SELECT  tbl_os.sua_os                                                    ,
					tbl_os.posto                                                     ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura    ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento  ,
					to_char(tbl_os.data_digitacao,'DD/MM/YYYY')  AS data_digitacao   ,
				/* to_char(tbl_os.data_digitacao,'HH24:MI')     AS hora_digitacao   , */
					tbl_os.hora_abertura as hora_digitacao                           ,
					tbl_os.consumidor_nome                                           ,
					tbl_os.consumidor_cpf                                            ,
					tbl_os.consumidor_cidade                                         ,
					tbl_os.consumidor_fone                                           ,
					tbl_os.consumidor_celular                                        ,
					tbl_os.consumidor_fone_comercial                                 ,
					tbl_os.consumidor_estado                                         ,
					tbl_os.consumidor_endereco                                       ,
					tbl_os.consumidor_numero                                         ,
					tbl_os.consumidor_complemento                                    ,
					tbl_os.consumidor_bairro                                         ,
					tbl_os.consumidor_cep                                            ,
					tbl_os.revenda_nome                                              ,
					tbl_os.revenda_cnpj                                              ,
					tbl_os.nota_fiscal                                               ,
					tbl_os.cliente                                                   ,
					tbl_os.revenda                                                   ,
					tbl_os.obs                                                       ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf          ,
					tbl_defeito_reclamado.descricao              AS defeito_reclamado,
					tbl_os.defeito_reclamado_descricao                               ,
					tbl_os.tipo_atendimento                                          ,
					tbl_os.aparencia_produto                                         ,
					tbl_os.acessorios                                                ,
					tbl_os.capacidade                                                ,
					tbl_produto.referencia                                           ,
					tbl_produto.descricao                                            ,
					tbl_os_extra.desconto_peca                                       ,
					tbl_os_extra.classificacao_os                                    ,
					tbl_os_extra.taxa_visita                                         ,
					tbl_os_extra.hora_tecnica                                        ,
					tbl_os_extra.anormalidades                                       ,
					tbl_os_extra.causas                                              ,
					tbl_os_extra.medidas_corretivas                                  ,
					tbl_os_extra.recomendacoes                                       ,
					tbl_os_extra.obs                                     AS obs_extra,
					tbl_os_extra.selo                                                ,
					tbl_os_extra.lacre_encontrado                                    ,
					tbl_os_extra.tecnico                                             ,
					tbl_os_extra.lacre                                               ,
					tbl_os_extra.regulagem_peso_padrao                               ,
					tbl_os_extra.certificado_conformidade                            ,
					tbl_os_extra.valor_diaria                                        ,
					tbl_os_extra.qtde_horas                                          ,
					tbl_os_extra.deslocamento_km                                     ,
					tbl_os_extra.visita_por_km                                       ,
					tbl_os_extra.valor_por_km                                        ,
					tbl_os_extra.valor_total_deslocamento                            ,
					tbl_os_extra.mao_de_obra                                         ,
					tbl_os_extra.mao_de_obra_por_hora                                ,
					tbl_os_extra.valor_total_diaria                                  ,
					tbl_os_extra.valor_total_hora_tecnica                            ,
					tbl_os_extra.desconto_deslocamento                               ,
					tbl_os_extra.desconto_hora_tecnica                               ,
					tbl_os_extra.desconto_diaria                                     ,
					tbl_os_extra.desconto_regulagem                                  ,
					tbl_os_extra.desconto_certificado                                ,
					tbl_condicao.descricao                      AS condicao_descricao,
					tbl_classificacao_os.descricao      AS classificacao_os_descricao,
					tbl_classificacao_os.garantia       AS classificacao_os_garantia ,
					tbl_os.quem_abriu_chamado                                        ,
					tbl_os.consumidor_revenda                                        ,
					tbl_os.os_numero                                                 ,
					tbl_os_revenda.os_manutencao                                     ,
					tbl_posto_fabrica.posto_empresa                                  ,
					tbl_posto.rg                                                     
			FROM    tbl_os
			LEFT JOIN    tbl_os_revenda        ON tbl_os_revenda.os_revenda = tbl_os.os_numero AND tbl_os_revenda.posto = tbl_os.posto
			JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN    tbl_defeito_reclamado USING(defeito_reclamado)
			LEFT JOIN    tbl_produto           USING(produto)
			LEFT JOIN    tbl_os_extra          USING(os)
			LEFT JOIN    tbl_condicao          ON tbl_condicao.condicao = tbl_os.condicao
			LEFT JOIN    tbl_classificacao_os  ON tbl_os_extra.classificacao_os = tbl_classificacao_os.classificacao_os
			LEFT JOIN    tbl_posto ON tbl_posto.cnpj = tbl_os.consumidor_cpf
			WHERE   tbl_os.os      = $os
			AND     tbl_os.fabrica = $login_fabrica
			";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$sua_os                      = pg_result ($res,0,sua_os);
		$posto                       = pg_result ($res,0,posto);
		$data_abertura               = pg_result ($res,0,data_abertura);
		$data_fechamento             = pg_result ($res,0,data_fechamento);
		$data_digitacao              = pg_result ($res,0,data_digitacao);
		$hora_digitacao              = pg_result ($res,0,hora_digitacao);
		$consumidor_nome             = pg_result ($res,0,consumidor_nome);
		$consumidor_cpf              = pg_result ($res,0,consumidor_cpf);
		$consumidor_cidade           = pg_result ($res,0,consumidor_cidade);
		$consumidor_fone             = pg_result ($res,0,consumidor_fone);
		$consumidor_celular          = pg_result ($res,0,consumidor_celular);
		$consumidor_fone_comercial   = pg_result ($res,0,consumidor_fone_comercial);
		$consumidor_estado           = pg_result ($res,0,consumidor_estado);
		$consumidor_endereco         = pg_result ($res,0,consumidor_endereco);
		$consumidor_numero           = pg_result ($res,0,consumidor_numero);
		$consumidor_complemento      = pg_result ($res,0,consumidor_complemento);
		$consumidor_bairro           = pg_result ($res,0,consumidor_bairro);
		$consumidor_cep              = pg_result ($res,0,consumidor_cep);
		$revenda_cnpj                = pg_result ($res,0,revenda_cnpj);
		$revenda_nome                = pg_result ($res,0,revenda_nome);
		$nota_fiscal                 = pg_result ($res,0,nota_fiscal);
		$data_nf                     = pg_result ($res,0,data_nf);
		$cliente                     = pg_result ($res,0,cliente);
		$revenda                     = pg_result ($res,0,revenda);
		$obs                         = pg_result ($res,0,obs);
		$defeito_reclamado           = pg_result ($res,0,defeito_reclamado);
		$tipo_atendimento            = pg_result ($res,0,tipo_atendimento);
		$aparencia_produto           = pg_result ($res,0,aparencia_produto);
		$acessorios                  = pg_result ($res,0,acessorios);
		$defeito_reclamado_descricao = pg_result ($res,0,defeito_reclamado_descricao);
		$capacidade                  = pg_result ($res,0,capacidade);
		$produto_referencia          = pg_result ($res,0,referencia);
		$produto_descricao           = pg_result ($res,0,descricao);
		$taxa_visita                 = pg_result ($res,0,taxa_visita);
		$hora_tecnica                = pg_result ($res,0,hora_tecnica);
		$anormalidades               = pg_result ($res,0,anormalidades);
		$causas                      = pg_result ($res,0,causas);
		$medidas_corretivas          = pg_result ($res,0,medidas_corretivas);
		$recomendacoes               = pg_result ($res,0,recomendacoes);
		$obs_extra                   = pg_result ($res,0,obs_extra);
		$selo                        = pg_result ($res,0,selo);
		$lacre_encontrado            = pg_result ($res,0,lacre_encontrado);
		$tecnico                     = pg_result ($res,0,tecnico);
		$lacre                       = pg_result ($res,0,lacre);
		$classificacao_os            = pg_result ($res,0,classificacao_os);
		$classificacao_os_garantia   = pg_result ($res,0,classificacao_os_garantia);
		$regulagem_peso_padrao       = pg_result ($res,0,regulagem_peso_padrao);
		$certificado_conformidade    = pg_result ($res,0,certificado_conformidade);
		$quem_abriu_chamado          = pg_result ($res,0,quem_abriu_chamado);
		$valor_diaria                = pg_result ($res,0,valor_diaria);
		$condicao_descricao          = pg_result ($res,0,condicao_descricao);
		$qtde_horas                  = pg_result ($res,0,qtde_horas);
		$deslocamento_km             = pg_result ($res,0,deslocamento_km);
		$visita_por_km               = pg_result ($res,0,visita_por_km);
		$valor_por_km                = pg_result ($res,0,valor_por_km);
		$valor_total_deslocamento    = pg_result ($res,0,valor_total_deslocamento);
		$mao_de_obra                 = pg_result ($res,0,mao_de_obra);
		$mao_de_obra_por_hora        = pg_result ($res,0,mao_de_obra_por_hora);
		$classificacao_os_descricao  = pg_result ($res,0,classificacao_os_descricao);
		$consumidor_revenda          = pg_result ($res,0,consumidor_revenda);
		$os_numero                   = pg_result ($res,0,os_numero);
		$os_manutencao               = pg_result ($res,0,os_manutencao);
		$posto_empresa               = pg_result ($res,0,posto_empresa);
		$valor_total_hora_tecnica    = pg_result ($res,0,valor_total_hora_tecnica);
		$valor_total_diaria          = pg_result ($res,0,valor_total_diaria);
		$desconto_deslocamento       = pg_result ($res,0,desconto_deslocamento);
		$desconto_hora_tecnica       = pg_result ($res,0,desconto_hora_tecnica);
		$desconto_diaria             = pg_result ($res,0,desconto_diaria);
		$desconto_regulagem          = pg_result ($res,0,desconto_regulagem);
		$desconto_certificado        = pg_result ($res,0,desconto_certificado);
		$desconto_peca               = pg_result ($res,0,desconto_peca);
		$consumidor_rg               = pg_result ($res,0,rg);

		if ($os_manutencao == 't'){
			$sql = "SELECT  tbl_os_revenda.os_revenda,
							tbl_os_revenda.taxa_visita,
							tbl_os_revenda.visita_por_km,
							tbl_os_revenda.valor_por_km,
							tbl_os_revenda.deslocamento_km,
							tbl_os_revenda.valor_total_deslocamento,
							tbl_os_revenda.veiculo,
							tbl_os_revenda.hora_tecnica,
							tbl_os_revenda.valor_diaria,
							tbl_os_revenda.qtde_horas,
							tbl_os_revenda.regulagem_peso_padrao,
							tbl_os_revenda.desconto_deslocamento,
							tbl_os_revenda.desconto_hora_tecnica,
							tbl_os_revenda.desconto_diaria,
							tbl_os_revenda.valor_total_hora_tecnica,
							tbl_os_revenda.valor_total_diaria
					FROM   tbl_os
					JOIN   tbl_os_revenda        ON tbl_os_revenda.os_revenda = tbl_os.os_numero AND tbl_os_revenda.posto = tbl_os.posto
					WHERE  tbl_os.os = $os
					AND    tbl_os.fabrica = $login_fabrica
					AND    tbl_os.posto   = $posto";
			$res2 = pg_exec ($con,$sql);
			if (pg_numrows ($res2) > 0 ) {
				$os_revenda				= pg_result ($res2,0,os_revenda);
				$taxa_visita			= pg_result ($res2,0,taxa_visita);
				$visita_por_km			= pg_result ($res2,0,visita_por_km);
				$valor_por_km			= pg_result ($res2,0,valor_por_km);
				$deslocamento_km		= pg_result ($res2,0,deslocamento_km);
				$valor_total_deslocamento= pg_result ($res2,0,valor_total_deslocamento);
				$veiculo				= pg_result ($res2,0,veiculo);
				$hora_tecnica			= pg_result ($res2,0,hora_tecnica);
				$valor_diaria			= pg_result ($res2,0,valor_diaria);
				$regulagem_peso_padrao	= pg_result ($res2,0,regulagem_peso_padrao);/*# HD 45569 */
				$desconto_deslocamento	= pg_result ($res2,0,desconto_deslocamento);
				$desconto_hora_tecnica	= pg_result ($res2,0,desconto_hora_tecnica);
				$desconto_diaria		= pg_result ($res2,0,desconto_diaria);
				$valor_total_hora_tecnica= pg_result ($res2,0,valor_total_hora_tecnica);
				$valor_total_diaria		= pg_result ($res2,0,valor_total_diaria);
			}
		}

		if ($desconto_regulagem>0 and $regulagem_peso_padrao>0){
			$regulagem_peso_padrao = $regulagem_peso_padrao - ($regulagem_peso_padrao*$desconto_deslocamento / 100);
		}
		
		if ($desconto_certificado>0 and $certificado_conformidade>0){
			$certificado_conformidade = $certificado_conformidade - ($certificado_conformidade*$desconto_certificado / 100);
		}

		if ($desconto_diaria>0 AND $valor_diaria>0){
			$valor_diaria = $valor_diaria + ($valor_diaria * $desconto_diaria /100 );
		}
		
		/* PARA OS EM GARANTIA, NAO COBRAR MAO DE OBRA, PEÇAS E REGULAGEM  || HD 51554 1==2 */
		if ($classificacao_os_garantia =='t' AND 1==2){
			$valor_total_diaria       = 0;
			$valor_total_hora_tecnica = 0;
			$regulagem_peso_padrao    = 0;
		}

		/* ZERA VALORES PARA OS CANCELADAS - HD 37170 */
		if ($classificacao_os == 5){
			$valor_total_diaria       = 0;
			$valor_total_hora_tecnica = 0;
			$regulagem_peso_padrao    = 0;
			$certificado_conformidade = 0;
			$regulagem_peso_padrao    = 0;
			$mao_de_obra              = 0;
			$taxa_visita              = 0;
			$deslocamento_km          = 0;
		}

		/* Calculo da Mao de Obra */
		$mao_de_obra = $valor_total_hora_tecnica + $valor_total_diaria;
	}


	if (strlen($cliente) > 0 AND $login_fabrica <>7) {
		# 55101 - Para a Filizola tem que buscar na tbl_posto
		if ($login_fabrica == 7){ // HD 72180 Não pegar mais dados nessa tabela, pega tbl_os
			/*$sql = "SELECT  tbl_posto.endereco   ,
							tbl_posto.numero     ,
							tbl_posto.complemento,
							tbl_posto.bairro     ,
							tbl_posto.cep        ,
							tbl_posto.cnpj AS cpf ,
							tbl_posto.rg
					FROM    tbl_posto
					WHERE   tbl_posto.cnpj = '$consumidor_cpf';";
			$res1 = pg_exec ($con,$sql);*/
		}else{
			$sql = "SELECT  tbl_cliente.endereco   ,
							tbl_cliente.numero     ,
							tbl_cliente.complemento,
							tbl_cliente.bairro     ,
							tbl_cliente.cep        ,
							tbl_cliente.cpf        ,
							tbl_cliente.rg
					FROM    tbl_cliente
					WHERE   tbl_cliente.cliente = $cliente;";
			$res1 = pg_exec ($con,$sql);
		}
		if (pg_numrows($res1) > 0) {
			$consumidor_endereco    = strtoupper(trim(pg_result ($res1,0,endereco)));
			$consumidor_numero      = trim(pg_result ($res1,0,numero));
			$consumidor_complemento = strtoupper(trim(pg_result ($res1,0,complemento)));
			$consumidor_bairro      = strtoupper(trim(pg_result ($res1,0,bairro)));
			$consumidor_cep         = trim(pg_result ($res1,0,cep));
			$consumidor_cep         = substr($consumidor_cep,0,2) .".". substr($consumidor_cep,2,3) ."-". substr($consumidor_cep,5,3);
			$consumidor_cpf         = trim(pg_result ($res1,0,cpf));
			
			if (strlen($consumidor_cpf) == 14){
				$consumidor_cpf = substr($consumidor_cpf,0,2) .".". substr($consumidor_cpf,2,3) .".". substr($consumidor_cpf,5,3) ."/". substr($consumidor_cpf,8,4) ."-". substr($consumidor_cpf,12,2);
			}elseif(strlen($consumidor_cpf) == 11){
				$consumidor_cpf = substr($consumidor_cpf,0,3) .".". substr($consumidor_cpf,3,3) .".". substr($consumidor_cpf,6,3) ."-". substr($consumidor_cpf,9,2);
			}
			$consumidor_rg          = trim(pg_result ($res1,0,rg));
		}
	}
	
	if (strlen($revenda) > 0) {
		$sql = "SELECT  tbl_revenda.endereco   ,
						tbl_revenda.numero     ,
						tbl_revenda.complemento,
						tbl_revenda.bairro     ,
						tbl_revenda.cep
				FROM    tbl_revenda
				WHERE   tbl_revenda.revenda = $revenda;";
		$res1 = pg_exec ($con,$sql);
		
		if (pg_numrows($res1) > 0) {
			$revenda_endereco    = strtoupper(trim(pg_result ($res1,0,endereco)));
			$revenda_numero      = trim(pg_result ($res1,0,numero));
			$revenda_complemento = strtoupper(trim(pg_result ($res1,0,complemento)));
			$revenda_bairro      = strtoupper(trim(pg_result ($res1,0,bairro)));
			$revenda_cep         = trim(pg_result ($res1,0,cep));
			$revenda_cep         = substr($revenda_cep,0,2) .".". substr($revenda_cep,2,3) ."-". substr($revenda_cep,5,3);
		}
	}

	if (strlen($revenda_cnpj) == 14){
		$revenda_cnpj = substr($revenda_cnpj,0,2) .".". substr($revenda_cnpj,2,3) .".". substr($revenda_cnpj,5,3) ."/". substr($revenda_cnpj,8,4) ."-". substr($revenda_cnpj,12,2);
	}elseif(strlen($consumidor_cpf) == 11){
		$revenda_cnpj = substr($revenda_cnpj,0,3) .".". substr($revenda_cnpj,3,3) .".". substr($revenda_cnpj,6,3) ."-". substr($revenda_cnpj,9,2);
	}

	$sql = "UPDATE tbl_os_extra SET impressa = current_timestamp where os=$os;";
	$res = pg_exec($con,$sql);

}elseif (strlen ($branco) > 0) {

	$sql = "SELECT	tbl_posto_fabrica.contato_endereco as endereco,
					tbl_posto_fabrica.contato_numero as numero,
					tbl_posto_fabrica.contato_cep as cep,
					tbl_posto_fabrica.contato_fone_comercial as fone,
					tbl_posto_fabrica.contato_fax as fax,
					tbl_Posto_fabrica.contato_cel as cel,
					tbl_posto.cnpj    ,
					tbl_posto.ie      ,
					tbl_posto_fabrica.contato_cidade as cidade,
					tbl_posto_fabrica.contato_estado as estado
			FROM	tbl_posto
			JOIN    tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE   tbl_posto.posto = $login_posto";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$posto_endereco		= pg_result ($res,0,endereco);
		$posto_numero		= pg_result ($res,0,numero);
		$posto_cep			= pg_result ($res,0,cep);
		$posto_cidade		= pg_result ($res,0,cidade);
		$posto_estado		= pg_result ($res,0,estado);
		$posto_fone			= pg_result ($res,0,fone);
		$posto_cnpj			= pg_result ($res,0,cnpj);
		$posto_ie			= pg_result ($res,0,ie);
	}

	$sua_os				= "&nbsp;";
	$serie				= "&nbsp;";
	$capacidade			= "&nbsp;";
	$data_abertura		= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	$quem_abriu_chamado	= "&nbsp;";
	$obs				= "&nbsp;";
	$descricao_equipamento = "&nbsp;";
	$nome_comercial     = "&nbsp;";
	$defeito_reclamado  = "&nbsp;";
	$cliente			= "&nbsp;";
	$cliente_nome		= "&nbsp;";
	$cliente_cpf		= "&nbsp;";
	$cliente_rg 		= "&nbsp;";
	$cliente_endereco	= "&nbsp;";
	$cliente_numero		= "&nbsp;";
	$cliente_complemento= "&nbsp;";
	$cliente_bairro		= "&nbsp;";
	$cliente_cep		= "&nbsp;";
	$cliente_cidade		= "&nbsp;";
	$cliente_fone		= "&nbsp;";
	$cliente_nome		= "&nbsp;";
	$cliente_estado		= "&nbsp;";
	$cliente_contrato	= "&nbsp;";

	//$taxa_visita				= "&nbsp;";
	//$hora_tecnica				= "&nbsp;";
	//$visita_por_km			= "&nbsp;";
	//$regulagem_peso_padrao	= "&nbsp;";
	//$certificado_conformidade	= "&nbsp;";
	//$anormalidades			= "&nbsp;";
	//$causas					= "&nbsp;";
	//$medidas_corretivas		= "&nbsp;";
	//$recomendacoes			= "&nbsp;";
	//$obs						= "&nbsp;";

}
$consumidor_cep         = substr($consumidor_cep,0,2) .".". substr($consumidor_cep,2,3) ."-". substr($consumidor_cep,5,3);

if (strlen($consumidor_cpf) == 14){
	$consumidor_cpf = substr($consumidor_cpf,0,2) .".". substr($consumidor_cpf,2,3) .".". substr($consumidor_cpf,5,3) ."/". substr($consumidor_cpf,8,4) ."-". substr($consumidor_cpf,12,2);
}elseif(strlen($consumidor_cpf) == 11){
	$consumidor_cpf = substr($consumidor_cpf,0,3) .".". substr($consumidor_cpf,3,3) .".". substr($consumidor_cpf,6,3) ."-". substr($consumidor_cpf,9,2);
}
$title = "Ordem de Serviço - Impressão";

?>

<html>

<head>

	<title><? echo $title ?></title>

	<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
	<meta http-equiv="Expires"       content="0">
	<meta http-equiv="Pragma"        content="no-cache, public">
	<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
	<meta name      ="Author"        content="Telecontrol Networking Ltda">
	<meta name      ="Generator"     content="na mão...">
	<meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assistência Técnica e Fabricantes.">
	<meta name      ="KeyWords"      content="Assistência Técnica, Postos, Manutenção, Internet, Webdesign, Orçamento, Comercial, Jóias, Callcenter">

	<link type="text/css" rel="stylesheet" href="css/css_press.css">

</head>

<style type="text/css">

body {
	margin: 0px,0px,0px,0px;
}

.titulo {
	font-family: normal Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 7px;
	text-align: left;
	color: #000000;
	background: #ffffff;
	border-bottom: dotted 1px #000000;
	/*border-right: dotted 1px #a0a0a0;*/
 	border-left: dotted 0px #000000;
	padding: 1px,1px,1px,1px;
}

.conteudo {
	font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	text-align: left;
	background: #ffffff;
	border-right: dotted 0px #a0a0a0;
	/*border-left: dotted 1px #a0a0a0;*/
	border-bottom: dotted 1px #000000;
	padding: 1px,1px,1px,1px;
}

.borda {
	border: solid 1px #a0a0a0;
}

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
	border: 1px solid #a0a0a0;
	color:#000000;
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 09px;
	font-weight: normal;
	border: 1px solid #a0a0a0;
}

.table_line1 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 09px;
	font-weight: normal;
	border: 1px solid #a0a0a0;
}
</style>
<body>
<TABLE width="650px" border="0" cellspacing="1" cellpadding="2">
<TR class="titulo" style="text-align: center;">
<?
	if($login_fabrica==7 AND strlen($posto_empresa)>0){
		$cond1 =  "AND PO.posto   = $posto_empresa ";
	}else{
		$cond1 =  "AND PO.posto   = $posto ";
	}

	$sql = "SELECT  PO.cnpj      ,
			PO.ie        ,
			PF.contato_fone_comercial AS fone,
			PO.nome      ,
			PF.contato_endereco     AS endereco,
			PF.contato_numero       AS numero,
			PF.contato_complemento  AS complemento,
			PF.contato_bairro       AS bairro,
			PF.contato_cidade       AS cidade,
			PF.contato_estado       AS estado,
			PF.contato_cep          AS cep,
			PF.contato_email        AS email
		FROM tbl_posto         PO
		JOIN tbl_posto_fabrica PF ON PO.posto = PF.posto 
		WHERE PF.fabrica = $login_fabrica
		$cond1";
$res = pg_exec ($con,$sql);

if (pg_numrows ($res) == 1) {
	$posto_nome			= pg_result ($res,0,nome);
	$posto_endereco		= pg_result ($res,0,endereco);
	$posto_numero		= pg_result ($res,0,numero);
	$posto_cep			= pg_result ($res,0,cep);
	$posto_cidade		= pg_result ($res,0,cidade);
	$posto_estado		= pg_result ($res,0,estado);
	$posto_fone			= pg_result ($res,0,fone);
	$posto_cnpj			= pg_result ($res,0,cnpj);
	$posto_ie			= pg_result ($res,0,ie);
}

	# HD 33819 - Francisco Ambrozio (14/8/2008)
	# Adicionado logo próprio para posto 213 (28468)
	if ($posto == 28468){
		$img_contrato = "logos/cabecalho_print_filizola_213.jpg";
	}elseif ($cliente_contrato <> 't' AND $tipo_atendimento <> "59") {
		$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
	}else{
		$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome)."_contrato.gif";
	}
?>
	<TD rowspan="2" style="text-align: left;"><IMG SRC="<? echo $img_contrato ?>" ALT="ORDEM DE SERVIÇO"></TD>
<?
	$sql = "SELECT nome FROM tbl_posto WHERE posto = $posto";
	$resP = pg_exec($con,$sql);
?>
	<TD style="font-size: 09px;"><? echo $posto_nome; ?></TD>
	<TD>DATA EMISSÃO</TD>
	<TD>NÚMERO</TD>
	<TD>PÁGINA</TD>
</TR>

<TR class="titulo">
	<TD style="font-size: 09px; text-align: center; width: 350px; ">
<?
switch (strlen (trim ($posto_cnpj))) {
	case 0:
		$posto_cnpj = "&nbsp";
		break;
	case 11:
		$posto_cnpj = substr ($posto_cnpj,0,3) . "." . substr ($posto_cnpj,3,3) . "." . substr ($posto_cnpj,6,3) . "-" . substr ($posto_cnpj,9,2);
		break;
	case 14:
		$posto_cnpj = substr ($posto_cnpj,0,2) . "." . substr ($posto_cnpj,2,3) . "." . substr ($posto_cnpj,5,3) . "/" . substr ($posto_cnpj,8,4) . "-" . substr ($posto_cnpj,12,2);
		break;
}

	########## CABECALHO COM DADOS DO POSTOS ########## 
	echo $posto_endereco .",".$posto_numero." - CEP ".$posto_cep."<br>";
	echo $posto_cidade ." - ".$posto_estado." - Telefone: ".$posto_fone."<br>";
	echo "CNPJ ".$posto_cnpj ." <br> IE ".$posto_ie;
?>
	</TD>
	<TD style="border: 1px solid #a0a0a0; font-size: 14px; text-align:center">
		<b><? echo $data_digitacao ?></b>
		<br>
		<b style='font-size:12px'><? echo $hora_digitacao ?></b>
	</TD>
	<TD style="border: 1px solid #a0a0a0; font-size: 14px;"align='center'>
		<b><? echo $sua_os ?></b>
	</TD>
	<TD style="border: 1px solid #a0a0a0; font-size: 14px;" align='center'>
		<b>1/1</b>
	</TD>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">

<? ########## DADOS DO CLIENTE ########## ?>

<?
if (strlen (trim ($cliente_ie)) == 0) $cliente_ie = "&nbsp";


switch (strlen (trim ($cliente_cpf))) {
case 0:
	$cliente_cpf = "&nbsp";
	break;
case 11:
	$cliente_cpf = substr ($cliente_cpf,0,3) . "." . substr ($cliente_cpf,3,3) . "." . substr ($cliente_cpf,6,3) . "-" . substr ($cliente_cpf,9,2);
	break;
case 14:
	$cliente_cpf = substr ($cliente_cpf,0,2) . "." . substr ($cliente_cpf,2,3) . "." . substr ($cliente_cpf,5,3) . "/" . substr ($cliente_cpf,8,4) . "-" . substr ($cliente_cpf,12,2);
	break;
}

?>

<TR>
	<TD class="titulo">Raz.Soc.</TD>
	<TD class="conteudo" colspan='3'><? echo $consumidor_nome ?>&nbsp</TD>
	<TD class="titulo">CNPJ/CPF</TD>
	<TD class="conteudo"><? echo $consumidor_cpf ?>&nbsp</TD>
	<TD class="titulo">IE/RG</TD>
	<TD class="conteudo"><? echo $consumidor_rg ?>&nbsp</TD>
</TR>

<!-- ====== ENDEREÇO E TELEFONE ================ -->
<TR>
	<TD class="titulo">Endereço</TD>
	<TD class="conteudo" colspan='3'><? 	
	if (strlen ($os) > 0) echo $consumidor_endereco . ", " . $consumidor_numero . " " . $consumidor_complemento ?>
	&nbsp</TD>
	<TD class="titulo">CEP</TD>
	<TD class="conteudo"><? echo $consumidor_cep ?>&nbsp</TD>
	<TD class="titulo">Telefone</TD>
	<TD class="conteudo"><? echo $consumidor_fone ?>&nbsp</TD>
</TR>

<!-- ====== Cep Municipio UF ================ -->
<TR>
	<TD class="titulo">Bairro</TD>
	<TD class="conteudo" colspan=3><? echo $consumidor_bairro ?>&nbsp</TD>
	<TD class="titulo">Municipio</TD>
	<TD class="conteudo"><? echo $consumidor_cidade ?>&nbsp</TD>
	<TD class="titulo">Estado</TD>
	<TD class="conteudo"><? echo $consumidor_estado ?>&nbsp</TD>
</TR>

<!-- ====== CONTATO E CHAMADO ================ -->
<TR>
	<TD class="titulo">Contato</TD>
	<TD class="conteudo"><? echo $consumidor_contato  # em branco, nao tem contato! ?>&nbsp</TD>
	<TD class="titulo">Distância (km)</TD>
	<TD class="conteudo"><? if (strlen($deslocamento_km)>0) echo $deslocamento_km." km"; ?> &nbsp</TD>
	<TD class="titulo">Solicitante:</TD>
	<TD class="conteudo"><? echo $quem_abriu_chamado ?>&nbsp;</TD>
</TR>
<TR>
</TR>
</table>
<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<!-- ====== MOTIVO ================ -->
<TR>
	<TD class="titulo"   width='8%'>Taxa de visita:</TD>
	<TD class="conteudo" width='9%'>
	<?
	if (strlen ($os) > 0 AND $taxa_visita>0) {
		echo "R$ ". number_format ($taxa_visita,2,",",".") ; 
	}
	?>&nbsp;</TD>
	<TD class="titulo"   width='8%'>Hora técnica:</TD>
	<TD class="conteudo" width='9%'>
	<?
		if (strlen ($os) > 0 AND $hora_tecnica>0) {
			echo "R$ ". number_format ($hora_tecnica,2,",",".");
		}
	?>&nbsp;</TD>
	<TD class="titulo"   width='8%'>Valor/km:</TD>
	<TD class="conteudo" width='9%'>
	<?
	if (strlen ($os) > 0 AND $valor_por_km > 0) {
		echo "R$ ". number_format ($valor_por_km,2,",",".")."/Km"; 
	}
	?>&nbsp;</TD>
	<TD class="titulo"   width='8%'>Valor diária:</TD>
	<TD class="conteudo" width='9%'>
	<?
	if (strlen ($os) > 0 AND $valor_diaria > 0) 
		echo "R$ ". number_format ($valor_diaria,2,",","."); 
	?>&nbsp;</TD>
	<TD class="titulo"   width='8%'>REGULAGEM</TD>
	<TD class="conteudo" width='9%'><? 
		if (strlen ($os) > 0 AND $regulagem_peso_padrao > 0) {
			echo "R$ ". number_format ($regulagem_peso_padrao,2,",","."); 
		}
		?>  &nbsp;</TD>

	<TD class="titulo"   width='8%'>CERTIF. CONF.</TD>
	<TD class="conteudo" width='9%'><? 
		if (strlen ($os) > 0 AND $certificado_conformidade > 0) {
			echo "R$ ". number_format ($certificado_conformidade,2,",","."); 
		}
		?>  &nbsp;</TD>
</TR>
<!--
<TR>
	<TD class="titulo">Obs.:</TD>
	<TD class="conteudo" colspan="7"><? echo $obs ?>&nbsp;</TD>
</TR>
-->
</TABLE>

<!-- ====== MODELO DO APARELHO ================ -->

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<?
	$sql = "SELECT	DISTINCT
					tbl_produto.referencia                                  ,
					tbl_produto.descricao                                   ,
					tbl_os.defeito_reclamado_descricao                      ,
					tbl_os.serie                                            ,
					tbl_os.capacidade                                       ,
					tbl_os.versao                                           ,
					tbl_os_extra.certificado_conformidade                   ,
					tbl_os_extra.selo                                       ,
					tbl_os_extra.lacre_encontrado                           ,
					tbl_defeito_constatado.descricao AS defeito_constatado_descricao,
					tbl_causa_defeito.descricao      AS causa_defeito_descricao
			FROM	tbl_os
			LEFT JOIN tbl_os_extra USING(os)
			LEFT JOIN tbl_produto            USING(produto)
			LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
			LEFT JOIN tbl_causa_defeito      USING(causa_defeito)
			WHERE	tbl_os.os    = $os
			AND		tbl_os.posto = $posto ";
	$res = pg_exec ($con,$sql);

	$total_produtos = 0;

	for($i=0; $i<@pg_numrows($res); $i++){
?>
<TR>
	<TD rowspan='3'><b><? echo $i + 1; ?></b></TD>
<!--	<TD class="titulo">REFERÊNCIA</TD>
	<TD class="conteudo"><? echo pg_result($res,$i,referencia); ?> &nbsp;</TD>-->
	<TD class="titulo">MODELO</TD>
	<TD class="conteudo" colspan=4><? echo pg_result($res,$i,descricao); ?>&nbsp;</TD>
	<TD class="titulo">CAPACIDADE</TD>
	<TD class="conteudo"><? echo pg_result($res,$i,capacidade); ?> &nbsp;</TD>
	<TD class="titulo">VERSÃO</TD>
	<TD class="conteudo"><? echo pg_result($res,$i,versao); ?> &nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">SÉRIE</TD>
	<TD class="conteudo" colspan='4'><? echo pg_result($res,$i,serie); ?> &nbsp;</TD>
	<TD class="titulo">SELO</TD>
	<TD class="conteudo"><? echo pg_result($res,$i,selo) ?> &nbsp;</TD>
	<TD class="titulo">LACRE ENCONTRADO</TD>
	<TD class="conteudo"><? echo pg_result($res,$i,lacre_encontrado) ?> &nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">DEFEITO RECLAMADO</TD>
	<TD class="conteudo" colspan='4'><? echo pg_result($res,$i,defeito_reclamado_descricao); ?> &nbsp;</TD>
	<TD class="titulo">NOTA FISCAL</TD>
	<TD class="conteudo"><? echo $nota_fiscal ?> &nbsp;</TD>
	<TD class="titulo">DATA NF</TD>
	<TD class="conteudo"><? echo $data_nf ?> &nbsp;</TD>
</TR>
<TR>
	<TD colspan='10' height='1' bgcolor="#000000"></TD>
</TR>
<?
		$total_produtos ++;
	}
?>
</TABLE>

<!-- ======= CONTROLE DE HORAS ========= -->
<TABLE width="650px" border="0" cellspacing="0" cellpadding="0">
<TR class="menu_top" bgcolor="#d0d0d0">
	<TD class="menu_top" bgcolor="#d0d0d0" width='20%'>DATA</TD>
	<TD class="menu_top" bgcolor="#d0d0d0" width='20%'>CHEGADA/INÍCIO</TD>
	<TD class="menu_top" bgcolor="#d0d0d0" width='20%'>SAÍDA/FIM</TD>
	<TD class="menu_top" bgcolor="#d0d0d0" width='20%'>INÍCIO INTERVALO</TD>
	<TD class="menu_top" bgcolor="#d0d0d0" width='20%'>TÉRMINO INTERVALO</TD>
</TR>
<?
$qtde_visitas = 0;

if (strlen($os) > 0) {
	// seleciona os visita
	$sql = "SELECT os_visita FROM tbl_os_visita WHERE os = $os ORDER BY os_visita";
	if ($os_manutencao == 't'){
		$sql = "SELECT os_visita FROM tbl_os_visita WHERE os_revenda = $os_numero ORDER BY os_visita";
	}
	$vis = pg_exec ($con,$sql);
	$qtde_visitas = pg_numrows($vis);
}

for($i=0; $i<5; $i++){
	$class = 'table_line';

	if (strlen($os) > 0 AND strlen($msg_erro) == 0) {
		if ($qtde_visitas > 0) {
			$os_visita = trim(@pg_result($vis,$i,os_visita));
		}

		if (strlen($os_visita) > 0) {
			$sql  = "SELECT tbl_os_visita.os_visita                                                       ,
							to_char(tbl_os_visita.data,'DD/MM/YYYY')               AS data                ,
							to_char(tbl_os_visita.hora_saida_sede, 'HH24:MI')      AS hora_saida_sede     ,
							tbl_os_visita.km_saida_sede                                                   ,
							to_char(tbl_os_visita.hora_chegada_cliente, 'HH24:MI') AS hora_chegada_cliente,
							tbl_os_visita.km_chegada_cliente                                              ,
							to_char(tbl_os_visita.hora_saida_almoco, 'HH24:MI')    AS hora_saida_almoco   ,
							tbl_os_visita.km_saida_almoco                                                 ,
							to_char(tbl_os_visita.hora_chegada_almoco, 'HH24:MI')  AS hora_chegada_almoco ,
							tbl_os_visita.km_chegada_almoco                                               ,
							to_char(tbl_os_visita.hora_saida_cliente, 'HH24:MI')   AS hora_saida_cliente  ,
							tbl_os_visita.km_saida_cliente                                                ,
							to_char(tbl_os_visita.hora_chegada_sede, 'HH24:MI')    AS hora_chegada_sede   ,
							tbl_os_visita.km_chegada_sede
					FROM    tbl_os_visita
					WHERE   tbl_os_visita.os_visita = $os_visita
					ORDER BY tbl_os_visita.os_visita";
			$res = pg_exec($con,$sql);

			if (@pg_numrows($res) > 0){
				$data					= trim(pg_result($res,0,data));
				$os_visita				= trim(pg_result($res,0,os_visita));
				$data					= trim(pg_result($res,0,data));
				$hora_saida_sede		= trim(pg_result($res,0,hora_saida_sede));
				$km_saida_sede			= trim(pg_result($res,0,km_saida_sede));
				$hora_chegada_cliente	= trim(pg_result($res,0,hora_chegada_cliente));
				$km_chegada_cliente		= trim(pg_result($res,0,km_chegada_cliente));
				$hora_saida_almoco		= trim(pg_result($res,0,hora_saida_almoco));
				$km_saida_almoco		= trim(pg_result($res,0,km_saida_almoco));
				$hora_chegada_almoco	= trim(pg_result($res,0,hora_chegada_almoco));
				$km_chegada_almoco		= trim(pg_result($res,0,km_chegada_almoco));
				$hora_saida_cliente		= trim(pg_result($res,0,hora_saida_cliente));
				$km_saida_cliente		= trim(pg_result($res,0,km_saida_cliente));
				$hora_chegada_sede		= trim(pg_result($res,0,hora_chegada_sede));
				$km_chegada_sede		= trim(pg_result($res,0,km_chegada_sede));

				$class = 'table_line';
			}
		}
	}

	echo "<TR>\n";
	echo "	<TD class='$class'>".$data."&nbsp;</TD>\n";
	#echo "	<TD class='$class'>".$hora_saida_sede."&nbsp;</TD>\n";
	#echo "	<TD class='$class'>".$km_saida_sede."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$hora_chegada_cliente."&nbsp;</TD>\n";
	#echo "	<TD class='$class'>".$km_chegada_cliente."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$hora_saida_cliente."&nbsp;</TD>\n";
	#echo "	<TD class='$class'>".$km_saida_cliente."&nbsp;</TD>\n";
	#echo "	<TD class='$class'>".$hora_chegada_sede."&nbsp;</TD>\n";
	#echo "	<TD class='$class'>".$km_chegada_sede."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$hora_saida_almoco."&nbsp;</TD>\n";
	#echo "	<TD class='$class'>".$km_saida_almoco."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$hora_chegada_almoco."&nbsp;</TD>\n";
	#echo "	<TD class='$class'>".$km_chegada_almoco."&nbsp;</TD>\n";
	echo "</TR>\n";

	$data					= " ";
	$hora_saida_sede		= " ";
	$hora_chegada_cliente	= " ";
	$hora_saida_almoco		= " ";
	$hora_chegada_almoco	= " ";
	$hora_saida_cliente		= " ";
	$hora_chegada_sede		= " ";

	$km_saida_sede			= " ";
	$km_chegada_cliente		= " ";
	$km_saida_almoco		= " ";
	$km_chegada_almoco		= " ";
	$km_saida_cliente		= " ";
	$km_chegada_sede		= " ";

}
?>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<?
if (strlen($anormalidades) > 0){
?>
<TR>
	<TD class="titulo" width="150">ANORMALIDADES ENCONTRADAS</TD>
	<TD class="conteudo"><? echo $anormalidades; ?>&nbsp;</TD>
</TR>
<?
}else{
?>
<TR>
	<TD class="titulo" width="150">ANORMALIDADES ENCONTRADAS</TD>
	<TD class="conteudo"><? echo $anormalidades; ?>&nbsp;</TD>
</TR>
<tr>
	<TD class="conteudo" colspan=2>&nbsp;</TD>
</tr>
<tr>
	<TD class="conteudo" colspan=2>&nbsp;</TD>
</tr>
<?
}
?>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<?
if (strlen($causas) > 0){
?>
<TR>
	<TD class="titulo" width="150">CAUSA DAS ANORMALIDADES</TD>
	<TD class="conteudo"><? echo $causas; ?>&nbsp;</TD>
</TR>
<?
}else{
?>
<TR>
	<TD class="titulo" width="150">CAUSA DAS ANORMALIDADES</TD>
	<TD class="conteudo"><? echo $causas; ?>&nbsp;</TD>
</TR>
<tr>
	<TD class="conteudo" colspan=2>&nbsp;</TD>
</tr>
<tr>
	<TD class="conteudo" colspan=2>&nbsp;</TD>
</tr>
<?
}
?>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<?
if (strlen($medidas_corretivas) > 0){
?>
<TR>
	<TD class="titulo" width="150">MEDIDAS CORRETIVAS</TD>
	<TD class="conteudo"><? echo $medidas_corretivas; ?>&nbsp;</TD>
</TR>
<?
}else{
?>
<TR>
	<TD class="titulo" width="150">MEDIDAS CORRETIVAS</TD>
	<TD class="conteudo"><? echo $medidas_corretivas; ?>&nbsp;</TD>
</TR>
<tr>
	<TD class="conteudo" colspan=2>&nbsp;</TD>
</tr>
<tr>
	<TD class="conteudo" colspan=2>&nbsp;</TD>
</tr>
<?
}
?>
</TABLE>

<!-- <TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">PEÇAS SUBSTITUIDAS</TD>
	<TD class="conteudo">&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">&nbsp;</TD>
	<TD class="conteudo">&nbsp;</TD>
</TR>
</TABLE> -->

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<?
if (strlen($recomendacoes) > 0){
?>
<TR>
	<TD class="titulo" width="150">RECOMENDAÇÕES AO CLIENTE</TD>
	<TD class="conteudo"><? echo $recomendacoes; ?>&nbsp;</TD>
</TR>
<?
}else{
?>
<TR>
	<TD class="titulo" width="150">RECOMENDAÇÕES AO CLIENTE</TD>
	<TD class="conteudo"><? echo $recomendacoes; ?>&nbsp;</TD>
</TR>
<tr>
	<TD class="conteudo" colspan=2>&nbsp;</TD>
</tr>
<?
}
?>
</TABLE>

<!-- 
<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">PARÂMETROS ENCONTRADOS</TD>
	<TD class="conteudo">P4:</TD>
	<TD class="conteudo" width="70">&nbsp;</TD>
	<TD class="conteudo">P5:</TD>
	<TD class="conteudo" width="70">&nbsp;</TD>
	<TD class="conteudo">P6:</TD>
	<TD class="conteudo" width="70">&nbsp;</TD>
	<TD class="conteudo">LACRE ENCONTRADO:</TD>
	<TD class="conteudo" width="70">&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">PARÂMETROS ATUAIS</TD>
	<TD class="conteudo">P4:</TD>
	<TD class="conteudo">&nbsp;</TD>
	<TD class="conteudo">P5:</TD>
	<TD class="conteudo">&nbsp;</TD>
	<TD class="conteudo">P6:</TD>
	<TD class="conteudo">&nbsp;</TD>
	<TD class="conteudo">LACRE ATUAL:</TD>
	<TD class="conteudo">&nbsp;</TD>
</TR>
</TABLE>
 -->

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo" width="100">OBSERVAÇÕES</TD>
<?
if (strlen($obs) > 0){
?>
	<TD class="conteudo"><? echo $obs; ?>&nbsp;</TD>
</TR>
<tr>
	<TD class="conteudo" colspan=2>&nbsp;</TD>
</tr>
<?
}else{
?>
	<TD class="conteudo">&nbsp;</TD>
</TR>
<tr>
	<TD class="conteudo" colspan=2>&nbsp;</TD>
</tr>
<tr>
	<TD class="conteudo" colspan=2>&nbsp;</TD>
</tr>
<?
}
?>
</TABLE>

<?if (1==2){?>
<TABLE width="650" border="1" cellspacing="0" cellpadding="0">
<TR>
	<TD class="menu_top" colspan="10" bgcolor="#d0d0d0">PEÇAS</TD>
</TR>
<TR>
	<TD class="menu_top" rowspan="2" style="width: 40px;">ITEM</TD>
	<TD class="menu_top" rowspan="2" style="width: 80px;">CODIGO</TD>
	<TD class="menu_top" rowspan="2" style="width: 200px;">MATERIAL</TD>
<!--	<TD class="menu_top" rowspan="2" style="width: 200px;">SERVIÇO<BR>REALIZADO</TD>-->
	<TD class="menu_top" rowspan="2" style="width: 30px;">QTDE</TD>
	<TD class="menu_top" rowspan="2" style="width: 30px;">UNID</TD>
	<TD class="menu_top" colspan='4'>TOTAL DE PEÇAS</TD>
</TR>
<TR>
	<TD class="menu_top">RECUPERADA</TD>
	<TD class="menu_top" style="width: 130px;">NOVA</TD>
</TR>

<?
if(strlen($os) > 0){

	$sql = "SELECT	tbl_os_item.os_item                ,
					tbl_os_item.pedido                 ,
					tbl_os_item.qtde                   ,
					tbl_peca.referencia                ,
					tbl_peca.descricao                 ,
					tbl_tabela_item.preco AS preco_item,
					tbl_servico_realizado.descricao AS servico_realizado_descricao
			FROM	tbl_os 
			JOIN	tbl_os_produto ON tbl_os_produto.os = tbl_os.os 
			JOIN	tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto 
			LEFT JOIN tbl_servico_realizado USING(servico_realizado)
			LEFT JOIN tbl_peca     ON tbl_peca.peca = tbl_os_item.peca 
			LEFT JOIN tbl_condicao ON tbl_condicao.condicao = tbl_os.condicao
			LEFT JOIN tbl_tabela   ON tbl_tabela.tabela = tbl_condicao.tabela
			LEFT JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca 
									  AND tbl_tabela_item.tabela = tbl_tabela.tabela 
			WHERE	tbl_os.os      = $os
			AND		tbl_os.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql) ;

	if(pg_numrows($res) > 0) {

		$total_geral_rec = 0;

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$os_item			= pg_result($res,$i,os_item);
			$pedido				= pg_result($res,$i,pedido);
			$peca				= pg_result($res,$i,referencia);
			$qtde				= pg_result($res,$i,qtde);
			$preco				= pg_result($res,$i,preco_item);
			$descricao			= pg_result($res,$i,descricao);
			$servico_realizado	= pg_result($res,$i,servico_realizado_descrica);
			$total				= $qtde * $preco;

			$total_geral_rec = $total_geral + $total;

			echo "<TR height='20'>\n";
			echo "	<TD class='table_line1'>&nbsp;</TD>\n";
			echo "	<TD class='table_line'>$peca &nbsp;</TD>\n";
			echo "	<TD class='table_line1'>$descricao &nbsp;</TD>\n";
			echo "	<TD class='table_line'>$qtde &nbsp;</TD>\n";
			echo "	<TD class='table_line1'>$unid &nbsp;</TD>\n";
			echo "	<TD class='table_line1' align='right' style='padding-right:3;'>".number_format ($preco,2,',','.')."&nbsp;</TD>\n";
			echo "	<TD class='table_line1' align='right' style='padding-right:3;'>".number_format ($total,2,',','.')."&nbsp;</TD>\n";
			echo "</TR>\n";
		}

		if (strlen($desconto_peca_recuperada) > 0 AND strlen($total_geral) > 0)
			$total_geral_rec = $total_geral_rec - ($total_geral_rec * ($desconto_peca_recuperada / 100));

		echo "<TR height='20'>\n";
		echo "	<TD class='table_line' colspan=5><B>TOTAIS</B></TD>\n";
		echo "	<TD class='table_line1' align='right' style='padding-right:3;'><b>".number_format ($valor_recuperada,2,',','.')."</b>&nbsp;</TD>\n";
		echo "	<TD class='table_line1' align='right' style='padding-right:3;'><b>".number_format ($valor_nova,2,',','.')."</b>&nbsp;</TD>\n";
		echo "</TR>\n";

		echo "<TR height='20'>\n";
		echo "	<TD class='table_line' colspan=5><B>TOTAL GERAL</B></TD>\n";
		echo "	<TD class='table_line1' align='right' style='padding-right:3;' colspan=2><b>".number_format ($valor_recuperada,2,',','.')."</b>&nbsp;</TD>\n";
		echo "</TR>\n";

	}else{

		for($i=0; $i<$total_produtos + 5;$i++){
?>
		<TR>
			<TD class="table_line">&nbsp;</TD>
			<TD class="table_line">&nbsp;</TD>
			<TD class="table_line">&nbsp;</TD>
			<TD class="table_line">&nbsp;</TD>
			<TD class="table_line">&nbsp;</TD>
			<TD class="table_line">&nbsp;</TD>
			<TD class="table_line">&nbsp;</TD>
		</TR>

<?
		}
		echo "<TR height='20'>\n";
		echo "	<TD class='table_line' colspan=5><B>TOTAIS</B></TD>\n";
		echo "	<TD class='table_line1' align='right' style='padding-right:3;'>&nbsp;</TD>\n";
		echo "	<TD class='table_line1' align='right' style='padding-right:3;'>&nbsp;</TD>\n";
		echo "</TR>\n";

		echo "<TR height='20'>\n";
		echo "	<TD class='table_line' colspan=5><B>TOTAL GERAL</B></TD>\n";
		echo "	<TD class='table_line1' align='right' style='padding-right:3;' colspan=2>&nbsp;</TD>\n";
		echo "</TR>\n";

	}
}else{

	for($i=0; $i<5;$i++){
?>
		<TR>
			<TD class="table_line">&nbsp;</TD>
			<TD class="table_line">&nbsp;</TD>
			<TD class="table_line">&nbsp;</TD>
			<TD class="table_line">&nbsp;</TD>
			<TD class="table_line">&nbsp;</TD>
			<TD class="table_line">&nbsp;</TD>
			<TD class="table_line">&nbsp;</TD>
		</TR>
<?
	}
}
?>
</TABLE>

<?}?>

<?
################################################################################
#### comentario de itens excluidos
################################################################################

if (1==1){
?>

<TABLE width="650" border="1" cellspacing="0" cellpadding="0">
<TR>
	<TD class="menu_top" colspan="10" bgcolor="#d0d0d0">Peças</TD>
</TR>
<TR>
	<TD class="menu_top" rowspan="2" style="width: 80px;">CODIGO</TD>
	<TD class="menu_top" rowspan="2" style="width: 30px;">QTDE</TD>
	<TD class="menu_top" rowspan="2" style="width: 30px;">UNID</TD>
	<TD class="menu_top" rowspan="2" style="width: 350px;">MATERIAL</TD>
	<!--<TD class="menu_top" rowspan="2" style="width: 200px;">SERVIÇO<BR>REALIZADO</TD>-->
	<TD class="menu_top" colspan="4" style="width: 160px;">PREÇO</TD>

</TR>
<TR>
	<!--<TD class="menu_top">Trib.</TD>-->
	<TD class="menu_top" style="width: 030px;">UNITÁRIO</TD>
	<TD class="menu_top" style="width: 100px;">TOTAL</TD>
	<TD class="menu_top" style="width: 030px;">IPI</TD>
</TR>
<?
if(strlen($os) > 0){

	$total_geral = 0;

	$sql = "SELECT  distinct
					tbl_os_item.os_item                ,
					tbl_os_item.pedido                 ,
					tbl_os_item.qtde                   ,
					tbl_peca.referencia                ,
					tbl_peca.descricao                 ,
					tbl_peca.origem                    ,
					tbl_peca.unidade                   ,
					tbl_peca.ipi                       ,
					tbl_peca.peso                      ,
					tbl_tabela_item.preco AS preco_item,
					tbl_servico_realizado.descricao AS servico_realizado_descricao
			FROM    tbl_os_item
			JOIN tbl_os_produto USING(os_produto)
			JOIN tbl_os         USING(os)
			LEFT JOIN tbl_peca USING (peca)
			LEFT JOIN tbl_servico_realizado USING(servico_realizado)
			LEFT JOIN tbl_condicao ON tbl_condicao.condicao = tbl_os.condicao
			LEFT JOIN tbl_tabela   ON tbl_tabela.tabela = tbl_condicao.tabela
			LEFT JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_os_item.peca AND tbl_tabela_item.tabela = tbl_tabela.tabela
			WHERE   tbl_os.os      = $os
			AND     tbl_os.fabrica = $login_fabrica
			";
	$res = pg_exec ($con,$sql) ;

	if(pg_numrows($res) > 0) {

		$total = 0;

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$os_item				= pg_result($res,$i,os_item);
			$pedido					= pg_result($res,$i,pedido);
			$peca					= pg_result($res,$i,referencia);
			$qtde					= pg_result($res,$i,qtde);
			$preco					= pg_result($res,$i,preco_item);
			$descricao				= pg_result($res,$i,descricao);
			$origem					= pg_result($res,$i,origem);
			$unidade				= pg_result($res,$i,unidade);
			$ipi					= pg_result($res,$i,ipi);
			$peso					= pg_result($res,$i,peso);
			$servico_realizado		= pg_result($res,$i,servico_realizado_descricao);

			if (strlen($desconto_peca) > 0){
				$preco = $preco - ($preco * ($desconto_peca / 100));
			}

			$preco_unitario = $preco;

			$preco  = $preco + ($preco_unitario * $ipi / 100);
			$total += $preco;

			$valor_total = $qtde * $preco;
			$total_geral = $total_geral + $valor_total;

			if ($origem == "TER") {
				$origem = "C";
			}else{
				$origem = "T";
			}

			echo "<TR height='20'>\n";
			echo "	<TD class='table_line1'>$peca &nbsp;</TD>\n";
			echo "	<TD class='table_line1'>$qtde &nbsp;</TD>\n";
			echo "	<TD class='table_line1'>$unid &nbsp;</TD>\n";
			echo "	<TD class='table_line1'>$descricao &nbsp;</TD>\n";
			#echo "	<TD class='table_line1'>$servico_realizado &nbsp;</TD>\n";
			#echo "	<TD class='table_line1'>$origem &nbsp;</TD>\n";
			echo "	<TD class='table_line1' style='padding-right:7;'>".number_format ($preco_unitario,2,',','.')." &nbsp;</TD>\n";
			echo "	<TD class='table_line1' style='padding-right:7;'>".number_format ($valor_total,2,',','.')." &nbsp;</TD>\n";
			echo "	<TD class='table_line1' style='padding-right:7;' nowrap>$ipi %</TD>\n";
			echo "</TR>\n";
		}

		/* PARA OS EM GARANTIA, NAO COBRAR MAO DE OBRA, PEÇAS E REGULAGEM */
		if ($classificacao_os_garantia =='t'){
			$total_geral = 0;
		}

		/* ZERA VALORES PARA OS CANCELADAS - HD 37170 */
		if ($classificacao_os == 5){
			$total_geral = 0;
		}

		$total_os = $total_servicos + $total_geral;

	}else{
		for($i=0; $i<5;$i++){
?>
			<TR>
				<TD class="table_line">&nbsp;</TD>
				<TD class="table_line">&nbsp;</TD>
				<TD class="table_line">&nbsp;</TD>
				<!--<TD class="table_line">&nbsp;</TD>-->
				<!--<TD class="table_line">&nbsp;</TD>-->
				<TD class="table_line">&nbsp;</TD>
				<TD class="table_line">&nbsp;</TD>
				<TD class="table_line">&nbsp;</TD>
			</TR>
<?
		}
	}
}else{
	for($i=0; $i<5;$i++){
?>
		<TR>
			<TD class="table_line">&nbsp;</TD>
			<TD class="table_line">&nbsp;</TD>
			<TD class="table_line">&nbsp;</TD>
			<!--<TD class="table_line">&nbsp;</TD>-->
			<!--<TD class="table_line">&nbsp;</TD>-->
			<TD class="table_line">&nbsp;</TD>
			<TD class="table_line">&nbsp;</TD>
			<TD class="table_line">&nbsp;</TD>
		</TR>
<?
	}
}
?>
</TABLE>

<?
	/* HD 37870 */
	/* Valores das Outras OS (nos casos de OS Multipla */
	$valores_os = 0;
	/* HD 52362 */
	/* Valores dos Certificados das outras OS */
	$total_certificado_conformidade = 0;

	if ($os_manutencao == 't' AND strlen($os_revenda)>0){

		$sql = "SELECT  tbl_os_item.peca                                              ,
						tbl_os_item.qtde                                              ,
						tbl_peca.ipi          AS ipi                                  ,
						tbl_tabela_item.preco AS preco_item                           ,
						tbl_os_extra.desconto_peca
				FROM      tbl_os
				JOIN      tbl_os_extra    ON tbl_os_extra.os           = tbl_os.os
				JOIN      tbl_os_produto  ON tbl_os_produto.os         = tbl_os.os
				LEFT JOIN tbl_os_revenda  ON tbl_os_revenda.os_revenda = tbl_os.os_numero AND tbl_os_revenda.posto = tbl_os.posto
				JOIN      tbl_produto     ON tbl_produto.produto       = tbl_os.produto
				JOIN	  tbl_os_item     ON tbl_os_item.os_produto    = tbl_os_produto.os_produto 
				JOIN      tbl_peca        ON tbl_peca.peca             = tbl_os_item.peca 
				LEFT JOIN tbl_condicao    ON tbl_condicao.condicao     = tbl_os.condicao
				LEFT JOIN tbl_tabela_item ON tbl_tabela_item.tabela    = tbl_condicao.tabela AND tbl_tabela_item.peca  = tbl_peca.peca
				WHERE tbl_os.os <> $os
				AND   tbl_os_revenda.os_revenda = $os_revenda
				AND   tbl_os.fabrica = $login_fabrica
				AND   tbl_os_extra.classificacao_os <> 46
				";
		$res = pg_exec ($con,$sql);
		$total = pg_numrows ($res);
		for ($i = 0 ; $i < $total ; $i++) {
			$peca   = pg_result ($res,$i,peca);
			$ipi    = pg_result ($res,$i,ipi);
			$preco  = pg_result ($res,$i,preco_item);
			$qtde   = pg_result($res,$i,qtde);
			$desconto_peca_item = pg_result($res,$i,desconto_peca);
			

			if (strlen($desconto_peca_item) > 0){
				$preco = $preco - ($preco * ($desconto_peca_item / 100));
			}

			$preco_unitario = $preco;

			$preco  = $preco + ($preco_unitario * $ipi / 100);

			$valor_total = $qtde * $preco;
			$valores_os = $valores_os + $valor_total;
		}

		/* HD 52362 */
		/* Valores dos Certificados das outras OS */
		$sql = "SELECT    SUM(tbl_os_extra.certificado_conformidade) AS total_certificado_conformidade
				FROM      tbl_os
				JOIN      tbl_os_extra    ON tbl_os_extra.os           = tbl_os.os
				LEFT JOIN tbl_os_revenda  ON tbl_os_revenda.os_revenda = tbl_os.os_numero AND tbl_os_revenda.posto = tbl_os.posto
				WHERE tbl_os.os <> $os
				AND   tbl_os_revenda.os_revenda = $os_revenda
				AND   tbl_os.fabrica = $login_fabrica
				AND   tbl_os_extra.classificacao_os <> 46
				";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) > 0 ){
			$total_certificado_conformidade   = pg_result ($res,0,total_certificado_conformidade);
		}

		$valores_os = $valores_os + $total_certificado_conformidade;
	}

?>

<?
$rowspan   = 7;
$rowspan_x = 3;
if ($valores_os > 0) {
	$rowspan   = 8;
	$rowspan_x = 4;
}
?>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo" rowspan=<?=$rowspan?>>ATENÇÃO, NÃO TEMOS COBRADORES. O PAGAMENTO DEVERÁ SER FEITO SOMENTE ATRAVÉS DE COBRANÇA BANCÁRIA</TD>
</TR>
<TR>
	<TD class="titulo" width='80'>COND. PAGTO</TD>
	<TD class="conteudo" width='100'><? echo $condicao_descricao; ?> &nbsp;</TD>
	<TD class="titulo" width='80'></TD>
	<TD class="conteudo" width="50"></TD>
	<TD class="titulo" width="100">TOTAL PEÇAS (A)</TD>
	<TD class="conteudo" width="100">
	<? 
	if ($qtde_visitas > 0 AND $total_geral>0){
		echo "R$ ". number_format ($total_geral,2,',','.');
	}
	?>&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">% DESCONTO PEÇAS</TD>
	<TD class="conteudo"> <? echo number_format ($desconto_peca,2,',','.'); ?> % </TD>
	<TD class="titulo"></TD>
	<TD class="conteudo"></TD>
	<TD class="titulo">TAXA REGULAGEM (B)</TD>
	<TD class="conteudo">
	<?
	if ($qtde_visitas > 0 AND $regulagem_peso_padrao>0){
		echo "R$ ". number_format ($regulagem_peso_padrao,2,",","."); 
	}
	?>&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo"></TD>
	<TD class="conteudo"></TD>
	<TD class="titulo"></TD>
	<TD class="conteudo"></TD>
	<TD class="titulo">TAXA CERT. CONF. (C)</TD>
	<TD class="conteudo"><?
	if ($qtde_visitas > 0 AND $certificado_conformidade>0){
		echo "R$ ". number_format ($certificado_conformidade,2,",","."); 
	}
	?>&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo" rowspan="<?=$rowspan_x?>" colspan="3">Carimbo e Assinatura</TD>
	<TD class="conteudo" rowspan="<?=$rowspan_x?>">&nbsp;</TD>
	<TD class="titulo">MÃO-DE-OBRA (D)</TD>
	<TD class="conteudo"><? 
		if ($qtde_visitas > 0 AND $mao_de_obra > 0 ){
			echo "R$ ". number_format ($mao_de_obra,2,",","."); 
		}
	?>&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">TAXA DE VISITA (E)</TD>
	<TD class="conteudo">
	<?

	if ($visita_por_km == 't'){
		//$taxa_visita = $deslocamento_km * $valor_por_km * 2;
		$taxa_visita = $valor_total_deslocamento;
	}

	if (strlen ($os) > 0 AND $qtde_visitas > 0 AND $taxa_visita > 0)
		echo "R$ ". number_format ($taxa_visita,2,",",".");
	?>
	&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">TOTAL SEM IPI <BR>(A+B+C+D+E)</TD>
	<TD class="conteudo"><? 
	$total_geral_sem_ipi = $total_geral + $regulagem_peso_padrao + $certificado_conformidade + $mao_de_obra + $taxa_visita;
	if($qtde_visitas > 0 AND $total_geral_sem_ipi > 0) {
		echo "R$ ". number_format ($total_geral_sem_ipi,2,",","."); 
	}
	?>
	&nbsp;</TD>
</TR>
<?if ($valores_os > 0) {?>
<TR>
	<TD class="titulo">TOTAL DAS OS<BR>(TODAS AS OS)</TD>
	<TD class="conteudo"><? 
	$total_geral_sem_ipi = $total_geral_sem_ipi + $valores_os;
	if($qtde_visitas > 0 AND $total_geral_sem_ipi > 0) {
		echo "R$ ". number_format ($total_geral_sem_ipi,2,",","."); 
	}
	?>
	&nbsp;</TD>
</TR>
<?}?>
<TR>
	<TD class="titulo" colspan="6">A ASSINATURA DO CLIENTE CONFIRMA A EXECUÇÃO DO SERVIÇO E EVENTUAL TROCA DE PEÇAS, BEM COMO APROVA OS PREÇOS COBRADOS</TD>
</TR>
</TABLE>
<?
}
################################################################################
#### comentario de itens excluidos
################################################################################
?>
<BR>

<!-- ====== MODELO DO APARELHO ================ -->
<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0" height=40>
<TR>
	<TD class="titulo">EM </TD>
	<TD class="conteudo" width='22%'> &nbsp;____&nbsp;/&nbsp;____&nbsp;/&nbsp;________</TD>
<!--	<TD class="titulo">VISTO</TD>
	<TD class="conteudo" width='25%'> _______________________________</TD>-->
	<TD class="titulo">TÉCNICO</TD>
	<TD class="conteudo" width='38%'><? if (strlen($tecnico) > 0) echo $tecnico; else "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"; ?>&nbsp;</TD>
	<TD class="titulo">LACRE</TD>
	<TD class="conteudo" width='20%'><? echo $lacre ?> &nbsp;</TD>
</TR>
</TABLE>
</BODY>
</html>
