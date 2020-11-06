<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if (strlen($_GET['os']) > 0) {
	$os   = $_GET['os'];
	$modo = $_GET['modo'];
}elseif (strlen($_GET['branco']) > 0) {
	$branco = $_GET['branco'];
}else{
	header ("Location: os_parametros.php");
	exit;
}

#------------ Le OS da Base de dados ------------#
if (strlen ($os) > 0) {
	$sql = "SELECT * FROM vw_os_print WHERE os = $os AND posto = $login_posto";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$sua_os				= pg_result ($res,0,sua_os);
		$serie				= pg_result ($res,0,serie);
		$capacidade			= pg_result ($res,0,capacidade);
		$data_abertura		= pg_result ($res,0,data_abertura);
//		$chamado			= pg_result ($res,0,chamado);
		$quem_abriu_chamado	= pg_result ($res,0,quem_abriu_chamado);
		$obs_os				= pg_result ($res,0,obs);
		$descricao_equipamento = pg_result ($res,0,descricao_equipamento);
		$nome_comercial     = pg_result ($res,0,nome_comercial);
		$defeito_reclamado  = pg_result ($res,0,defeito_reclamado);
		$cliente			= pg_result ($res,0,cliente);
		$cliente_nome		= pg_result ($res,0,cliente_nome);
		$cliente_cpf		= pg_result ($res,0,cliente_cpf);
		$cliente_rg 		= pg_result ($res,0,cliente_rg);
		$cliente_endereco	= pg_result ($res,0,cliente_endereco);
		$cliente_numero		= pg_result ($res,0,cliente_numero);
		$cliente_complemento= pg_result ($res,0,cliente_complemento);
		$cliente_bairro		= pg_result ($res,0,cliente_bairro);
		$cliente_cep		= pg_result ($res,0,cliente_cep);
		$cliente_cidade		= pg_result ($res,0,cliente_cidade);
		$cliente_fone		= pg_result ($res,0,cliente_fone);
		$cliente_nome		= pg_result ($res,0,cliente_nome);
		$cliente_estado		= pg_result ($res,0,cliente_estado);
		$cliente_contrato	= pg_result ($res,0,cliente_contrato);
		$posto_endereco		= pg_result ($res,0,posto_endereco);
		$posto_numero		= pg_result ($res,0,posto_numero);
		$posto_cep			= pg_result ($res,0,posto_cep);
		$posto_cidade		= pg_result ($res,0,posto_cidade);
		$posto_estado		= pg_result ($res,0,posto_estado);
		$posto_fone			= pg_result ($res,0,posto_fone);
		$posto_cnpj			= pg_result ($res,0,posto_cnpj);
		$posto_ie			= pg_result ($res,0,posto_ie);
	}

	$sql = "SELECT	tbl_os.serie                                                     ,
					tbl_os.os                                                        ,
					tbl_os.sua_os                                                    ,
					tbl_os.nota_fiscal                                               ,
					tbl_os.capacidade                                                ,
					tbl_os_extra.taxa_visita                                         ,
					tbl_os_extra.visita_por_km                                       ,
					tbl_os_extra.mao_de_obra                                         ,
					tbl_os_extra.mao_de_obra_por_hora                                ,
					tbl_os_extra.regulagem_peso_padrao                               ,
					tbl_os_extra.certificado_conformidade                            ,
					tbl_os_extra.valor_diaria                                        ,
					tbl_os_extra.laudo_tecnico                                       ,
					tbl_os_extra.qtde_horas                                          ,
					tbl_os_extra.anormalidades                                       ,
					tbl_os_extra.causas                                              ,
					tbl_os_extra.medidas_corretivas                                  ,
					tbl_os_extra.recomendacoes                                       ,
					tbl_os_extra.obs                                                 ,
					tbl_os_extra.faturamento_cliente_revenda                         ,
					tbl_os_extra.natureza_servico                                    ,
					tbl_os_extra.selo                                                ,
					tbl_os_extra.lacre_encontrado                                    ,
					tbl_os_extra.lacre                                               ,
					tbl_os_extra.tecnico                                             ,
					tbl_os_extra.hora_tecnica                                        ,
					tbl_classificacao_os.descricao AS classificacao_os               ,
					tbl_condicao.descricao         AS condicao                      
			FROM	tbl_os
			LEFT JOIN tbl_produto USING (produto)
			LEFT JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
			LEFT JOIN tbl_classificacao_os USING (classificacao_os)
			LEFT JOIN tbl_condicao USING (condicao)
			WHERE	tbl_os.os    = $os
			AND		tbl_os.posto = $login_posto ";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$os                          = pg_result($res,0,os);
		$sua_os                      = pg_result($res,0,sua_os);
		$serie                       = pg_result($res,0,serie);
		$nota_fiscal                 = pg_result($res,0,nota_fiscal);
		$taxa_visita                 = pg_result($res,0,taxa_visita);
		//$taxa_visita                 = number_format($taxa_visita, 2, '.', ' ');
		$visita_por_km               = pg_result($res,0,visita_por_km);
		$mao_de_obra                 = pg_result($res,0,mao_de_obra);
		//$mao_de_obra                 = number_format($mao_de_obra, 2, '.', ' ');
		$mao_de_obra_por_hora        = pg_result($res,0,mao_de_obra_por_hora);
		$regulagem_peso_padrao       = pg_result($res,0,regulagem_peso_padrao);
		//$regulagem_peso_padrao       = number_format($regulagem_peso_padrao, 2, '.', ' ');
		$certificado_conformidade    = pg_result($res,0,certificado_conformidade);
		$certificado_conformidade    = number_format($certificado_conformidade, 2, '.', ' ');
		$valor_diaria                = pg_result($res,0,valor_diaria);
		//$valor_diaria                = number_format($valor_diaria, 2, '.', ' ');
		$natureza_servico            = pg_result($res,0,natureza_servico);
		$laudo_tecnico               = pg_result($res,0,laudo_tecnico);
		$qtde_horas                  = pg_result($res,0,qtde_horas);
		$anormalidades               = pg_result($res,0,anormalidades);
		$causas                      = pg_result($res,0,causas);
		$medidas_corretivas          = pg_result($res,0,medidas_corretivas);
		$recomendacoes               = pg_result($res,0,recomendacoes);
		$obs                         = pg_result($res,0,obs);
		$faturamento_cliente_revenda = pg_result($res,0,faturamento_cliente_revenda);
		$capacidade                  = pg_result($res,0,capacidade);
		$selo                        = pg_result($res,0,selo);
		$lacre_encontrado            = pg_result($res,0,lacre_encontrado);
		$lacre                       = pg_result($res,0,lacre);
		$tecnico                     = pg_result($res,0,tecnico);
		$classificacao_os            = pg_result($res,0,classificacao_os);
		$condicao                    = pg_result($res,0,condicao);
		$hora_tecnica                = pg_result($res,0,hora_tecnica);
	}

}elseif (strlen ($branco) > 0) {

	$sql = "SELECT	tbl_posto.endereco,
					tbl_posto.numero  ,
					tbl_posto.cep     ,
					tbl_posto.fone    ,
					tbl_posto.cnpj    ,
					tbl_posto.ie      ,
					tbl_posto.cidade  ,
					tbl_posto.estado
			FROM	tbl_posto WHERE posto = $login_posto";
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
	$obs_os				= "&nbsp;";
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

$title = "Ordem de Serviço Balcão - Impressão";

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
 	border-left: dotted 1px #000000;
	padding: 1px,1px,1px,1px;
}

.conteudo {
	font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	text-align: left;
	background: #ffffff;
	border-right: dotted 1px #a0a0a0;
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
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 14px;
	font-weight: normal;
	border: 1px solid #a0a0a0;
}

.table_line1 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 09px;
	font-weight: normal;
	border: 1px solid #a0a0a0;
}
</style>

<body>

<TABLE width="600px" border="0" cellspacing="1" cellpadding="0">
<TR class="titulo" style="text-align: center;">
<?
	if ($cliente_contrato <> 't') 
		$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
	else
		$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome)."_contrato.gif";
?>
	<TD rowspan="2" style="text-align: left;"><IMG SRC="<? echo $img_contrato ?>" ALT="ORDEM DE SERVIÇO"></TD>
<!-- 	<TD style="font-size: 09px;">INDÚSTRIAS FILIZOLA S/A</TD> -->
<?
	$sql = "SELECT nome FROM tbl_posto WHERE posto = $login_posto";
	$resP = pg_exec($con,$sql);
?>
	<TD style="font-size: 09px;"><? echo pg_result($resP,0,nome); ?></TD>
	<TD>DATA EMISSÃO</TD>
	<TD>NÚMERO</TD>
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
	<TD style="border: 1px solid #a0a0a0; font-size: 14px;">
<?	########## DATA DE ABERTURA ########## ?>
		<b><? echo $data_abertura ?></b>
	</TD>
	<TD style="border: 1px solid #a0a0a0; font-size: 14px;" nowrap>
<?	########## SUA OS ########## ?>
		<b><? echo $sua_os ?></b>
	</TD>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">

<?	########## DADOS DO CLIENTE ########## ?>

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
	<TD class="conteudo" colspan='2'><? echo $cliente_nome ?>&nbsp</TD>
	<TD class="titulo">CNPJ/CPF</TD>
	<TD class="conteudo"><? echo $cliente_cpf ?>&nbsp</TD>
	<TD class="titulo">IE/RG</TD>
	<TD class="conteudo"><? echo $cliente_rg ?>&nbsp</TD>
</TR>

<!-- ====== ENDEREÇO E TELEFONE ================ -->
<TR>
	<?
	if (strlen ($os) > 0) 
		$cliente_cep = substr ($cliente_cep,0,2) . "." . substr ($cliente_cep,2,3) . "-" . substr ($cliente_cep,5,3);
	?>
	<TD class="titulo">Endereço</TD>
	<TD class="conteudo" colspan='2'><? 	
	if (strlen ($os) > 0) 
		echo $cliente_endereco . ", " . $cliente_numero . " " . $cliente_complemento ?>
	&nbsp</TD>
	<TD class="titulo">CEP</TD>
	<TD class="conteudo"><? echo $cliente_cep ?>&nbsp</TD>
	<TD class="titulo">Telefone</TD>
	<TD class="conteudo"><? echo $cliente_fone ?>&nbsp</TD>
</TR>

<!-- ====== Cep Municipio UF ================ -->
<TR>
	<TD class="titulo">Bairro</TD>
	<TD class="conteudo" colspan=2><? echo $cliente_bairro ?>&nbsp</TD>
	<TD class="titulo">Municipio</TD>
	<TD class="conteudo"><? echo $cliente_cidade ?>&nbsp</TD>
	<TD class="titulo">Estado</TD>
	<TD class="conteudo"><? echo $cliente_estado ?>&nbsp</TD>
</TR>

<!-- ====== CONTATO E CHAMADO ================ -->
<TR>
	<TD class="titulo">Obs.:</TD>
	<TD class="conteudo" colspan=2><? echo $obs_os ?>&nbsp</TD>
	<TD class="titulo">Contato</TD>
	<TD class="conteudo"><? echo $quem_abriu_chamado ?>&nbsp</TD>
	<TD class="titulo">Defeito</TD>
	<TD class="conteudo"><? 
#	if (strlen (trim ($nome_comercial)) > 0) {
#		echo $nome_comercial ;
#		echo $descricao_equipamento;
#	}else{
#		echo $descricao_equipamento;
#	}
	echo $defeito_reclamado ?>&nbsp</TD>
</TR>
<TR>
</TR>

<!-- ====== MOTIVO ================ -->
<TR>
	<TD class="titulo">Por equipamento:</TD>
	<TD class="conteudo" colspan=2>
	<?
	if (strlen ($os) > 0) 
		echo "R$ ". number_format ($mao_de_obra,2,",",".");
	?>&nbsp;</TD>
	<TD class="titulo">Taxa de visita:</TD>
	<TD class="conteudo">
	<?
	if (strlen ($os) > 0) 
		echo "R$ ". number_format ($taxa_visita,2,",",".") ; if ($visita_por_km == 't') echo "/Km"; 
	?>&nbsp;</TD>
	<TD class="titulo">Hora técnica:</TD>
	<TD class="conteudo">
	<?
		if (strlen ($os) > 0) {
			//$hora_tecnica = $hora_tecnica + $mao_de_obra;
			echo "R$ ". number_format ($hora_tecnica,2,",",".");
		}
	?>&nbsp;</TD>
</TR>
</TABLE>

<!-- ====== MODELO DO APARELHO ================ -->
<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">SÉRIE</TD>
	<TD class="conteudo"   width="80"><? echo $serie ?> &nbsp;</TD>
	<TD class="titulo">CAPACIDADE</TD>
	<TD class="conteudo" width="80"><? echo $capacidade ?> &nbsp;</TD>
	<TD class="titulo">MODELO</TD>
	<TD class="conteudo" colspan="2"><? echo $descricao_equipamento ?>&nbsp;</TD>
	<TD class="titulo">INSTALAÇÃO</TD>
	<TD class="conteudo">___/___/____</TD>
</TR>
<TR>
	<TD class="titulo">LEITURA</TD>
	<TD class="conteudo" colspan='3'><!-- <? echo $cliente_nome ?> -->&nbsp</TD>
	<TD class="titulo">NF COMPRA/REVENDA</TD>
	<TD class="conteudo" colspan='2'><!-- <? echo $cliente_nome ?> -->&nbsp</TD>
	<TD class="titulo">GARANTIA</TD>
	<TD class="conteudo">___/___/____</TD>
</TR>
</TABLE>

<!-- ======= CONTROLE DE HORAS ========= -->

<TABLE width="650" border="0" cellspacing="0" cellpadding="0">
<TR class="menu_top" bgcolor="#d0d0d0">
	<TD class="menu_top" bgcolor="#d0d0d0">&nbsp;</TD>
	<TD class="menu_top" bgcolor="#d0d0d0" colspan="2">SAÍDA</TD>
	<TD class="menu_top" bgcolor="#d0d0d0" colspan="2">CHEGADA/INÍCIO</TD>
	<TD class="menu_top" bgcolor="#d0d0d0" colspan="2">SAÍDA/FIM</TD>
	<TD class="menu_top" bgcolor="#d0d0d0" colspan="2">CHEGADA</TD>
	<TD class="menu_top" bgcolor="#d0d0d0" colspan="2">SAÍDA</TD>
	<TD class="menu_top" bgcolor="#d0d0d0" colspan="2">CHEGADA</TD>
</TR>
<TR>
	<TD class="menu_top" style='text-align: center;'>LOCAL</TD>
	<TD class="menu_top" style='text-align: center;' colspan="2">SEDE</TD>
	<TD class="menu_top" style='text-align: center;' colspan="2">CLIENTE</TD>
	<TD class="menu_top" style='text-align: center;' colspan="2">CLIENTE</TD>
	<TD class="menu_top" style='text-align: center;' colspan="2">SEDE</TD>
	<TD class="menu_top" style='text-align: center;' colspan="2">ALMOÇO</TD>
	<TD class="menu_top" style='text-align: center;' colspan="2">ALMOÇO</TD>
</TR>

<TR class="table_line1">
	<TD class="table_line1">DATA</TD>
	<TD class="table_line1" width="50">HORA</TD>
	<TD class="table_line1" width="50">Km</TD>
	<TD class="table_line1" width="50">HORA</TD>
	<TD class="table_line1" width="50">Km</TD>
	<TD class="table_line1" width="50">HORA</TD>
	<TD class="table_line1" width="50">Km</TD>
	<TD class="table_line1" width="50">HORA</TD>
	<TD class="table_line1" width="50">Km</TD>
	<TD class="table_line1" width="50">HORA</TD>
	<TD class="table_line1" width="50">Km</TD>
	<TD class="table_line1" width="50">HORA</TD>
	<TD class="table_line1" width="50">Km</TD>
</TR>
<?
if (strlen($os) > 0) {
	// seleciona os visita
	$sql = "SELECT os_visita FROM tbl_os_visita WHERE os = $os ORDER BY os_visita";
	$vis = pg_exec ($con,$sql);
}

for($i=0; $i<3; $i++){
	$class = 'table_line';

	if (strlen($os) > 0 AND strlen($msg_erro) == 0) {
		if (@pg_numrows($vis) > 0) {
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
					WHERE   tbl_os_visita.os        = $os
					AND     tbl_os_visita.os_visita = $os_visita
					AND     tbl_os.posto            = $login_posto
					ORDER BY tbl_os_visita.os_visita";
			$res = pg_exec($con,$sql);

			if (@pg_numrows($res) > 0){
				$data					= pg_result($res,$i,data);
				$hora_saida_sede		= pg_result($res,$i,hora_saida_sede);
				$km_saida_sede			= pg_result($res,$i,km_saida_sede);
				$hora_chegada_cliente	= pg_result($res,$i,hora_chegada_cliente);
				$km_chegada_cliente		= pg_result($res,$i,km_chegada_cliente);
				$hora_saida_almoco		= pg_result($res,$i,hora_saida_almoco);
				$km_saida_almoco		= pg_result($res,$i,km_saida_almoco);
				$hora_chegada_almoco	= pg_result($res,$i,hora_chegada_almoco);
				$km_chegada_almoco		= pg_result($res,$i,km_chegada_almoco);
				$hora_saida_cliente		= pg_result($res,$i,hora_saida_cliente);
				$km_saida_cliente		= pg_result($res,$i,km_saida_cliente);
				$hora_chegada_sede		= pg_result($res,$i,hora_chegada_sede);
				$km_chegada_sede		= pg_result($res,$i,km_chegada_sede);
				$class = 'table_line1';
			}
		}
	}

	echo "<TR>\n";
	echo "	<TD class='$class'>".$data."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$hora_saida_sede."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$km_saida_sede."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$hora_chegada_cliente."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$km_chegada_cliente."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$hora_saida_cliente."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$km_saida_cliente."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$hora_chegada_sede."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$km_chegada_sede."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$hora_saida_almoco."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$km_saida_almoco."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$hora_chegada_almoco."&nbsp;</TD>\n";
	echo "	<TD class='$class'>".$km_chegada_almoco."&nbsp;</TD>\n";
	echo "</TR>\n";

	$data					= " ";
	$hora_saida_sede		= " ";
	$km_saida_sede			= " ";
	$hora_chegada_cliente	= " ";
	$km_chegada_cliente		= " ";
	$hora_saida_almoco		= " ";
	$km_saida_almoco		= " ";
	$hora_chegada_almoco	= " ";
	$km_chegada_almoco		= " ";
	$hora_saida_cliente		= " ";
	$km_saida_cliente		= " ";
	$hora_chegada_sede		= " ";
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
<?
if (strlen($obs) > 0){
?>
<TR>
	<TD class="titulo" width="150">OBSERVAÇÕES</TD>
	<TD class="conteudo"><? echo $obs; ?>&nbsp;</TD>
</TR>
<?
}else{
?>
<TR>
	<TD class="titulo" width="150">OBSERVAÇÕES</TD>
	<TD class="conteudo">&nbsp;</TD>
</TR>
<tr>
	<TD class="conteudo" colspan=2>&nbsp;</TD>
</tr>
<?
}
?>
</TABLE>

<TABLE width="650" border="1" cellspacing="0" cellpadding="0">
<TR>
	<TD class="menu_top" colspan="10" bgcolor="#d0d0d0">Serviço de Recuperação (A)</TD>
</TR>
<TR>
	<TD class="menu_top" rowspan="2" style="width: 80px;">CODIGO</TD>
	<TD class="menu_top" rowspan="2" style="width: 30px;">QTDE</TD>
	<TD class="menu_top" rowspan="2" style="width: 30px;">UNID</TD>
	<TD class="menu_top" rowspan="2" style="width: 200px;">MATERIAL</TD>
	<TD class="menu_top" colspan='4'>PREÇO</TD>
</TR>
<TR>
	<TD class="menu_top">UNITÁRIO</TD>
	<TD class="menu_top" style="width: 150px;">TOTAL</TD>
</TR>

<?
if(strlen($os) > 0){

	$sql = "SELECT	tbl_os_item.os_item                ,
					tbl_os_item.pedido                 ,
					tbl_os_item.qtde                   ,
					tbl_peca.referencia                ,
					tbl_peca.descricao                 ,
					tbl_tabela_item.preco AS preco_item
			FROM	tbl_os 
			JOIN	tbl_os_produto ON tbl_os_produto.os = tbl_os.os 
			JOIN	tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto 
			LEFT JOIN tbl_peca     ON tbl_peca.peca = tbl_os_item.peca 
			JOIN	tbl_tabela     ON tbl_tabela.tabela = 29
			LEFT JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca 
									  AND tbl_tabela_item.tabela = tbl_tabela.tabela 
			WHERE	tbl_os.os      = $os
			AND		tbl_os.fabrica = $login_fabrica
			AND tbl_os_item.servico_realizado = 36";
	$res = pg_exec ($con,$sql) ;

	if(pg_numrows($res) > 0) {

		$total_geral_rec = 0;

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$os_item	= pg_result($res,$i,os_item);
			$pedido		= pg_result($res,$i,pedido);
			$peca		= pg_result($res,$i,referencia);
			$qtde		= pg_result($res,$i,qtde);
			$preco		= pg_result($res,$i,preco_item);
			$descricao	= pg_result($res,$i,descricao);
			$total		= $qtde * $preco;

			$total_geral_rec = $total_geral + $total;

			echo "<TR height='20'>\n";
			echo "	<TD class='table_line1'>$peca &nbsp;</TD>\n";
			echo "	<TD class='table_line1'>$qtde &nbsp;</TD>\n";
			echo "	<TD class='table_line1'>$unid &nbsp;</TD>\n";
			echo "	<TD class='table_line1'>$descricao &nbsp;</TD>\n";
			echo "	<TD class='table_line1' align='right' style='padding-right:3;'>".number_format ($preco,2,',','.')."&nbsp;</TD>\n";
			echo "	<TD class='table_line1' align='right' style='padding-right:3;'>".number_format ($total,2,',','.')."&nbsp;</TD>\n";
			echo "</TR>\n";
		}

		if (strlen($desconto_peca_recuperada) > 0 AND strlen($total_geral) > 0)
			$total_geral_rec = $total_geral_rec - ($total_geral_rec * ($desconto_peca_recuperada / 100));

	}else{

		for($i=0; $i<2;$i++){
?>
		<TR>
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
}else{

	for($i=0; $i<2;$i++){
?>
		<TR>
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

<TABLE width="650" border="1" cellspacing="0" cellpadding="0">
<TR>
	<TD class="menu_top" colspan="10" bgcolor="#d0d0d0">Peças (B)</TD>
</TR>
<TR>
	<TD class="menu_top" rowspan="2" style="width: 80px;">CODIGO</TD>
	<TD class="menu_top" rowspan="2" style="width: 30px;">QTDE</TD>
	<TD class="menu_top" rowspan="2" style="width: 30px;">UNID</TD>
	<TD class="menu_top" rowspan="2" style="width: 200px;">MATERIAL</TD>
	<TD class="menu_top" colspan="4">PREÇO</TD>

</TR>
<TR>
	<TD class="menu_top">Trib.</TD>
	<TD class="menu_top">UNITÁRIO</TD>
	<TD class="menu_top" style="width: 150px;">TOTAL</TD>
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
					tbl_tabela_item.preco AS preco_item
			FROM    tbl_os_item
			LEFT JOIN tbl_peca USING (peca)
			LEFT JOIN tbl_tabela ON tbl_tabela.tabela = 29
			LEFT JOIN tbl_tabela_item USING (peca)
			JOIN tbl_os_produto USING(os_produto)
			JOIN tbl_os ON tbl_os_produto.os = tbl_os.os
			WHERE   tbl_os.os      = $os
			AND     tbl_os.fabrica = $login_fabrica
			AND     tbl_os_item.servico_realizado = 12;";
	$res = pg_exec ($con,$sql) ;
	
	if(pg_numrows($res) > 0) {

		$total = 0;
		/*
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$ipi   = trim(pg_result ($res,$i,ipi));
			$preco = pg_result ($res,$i,qtde) * pg_result ($res,$i,preco_item) ;
			$preco = $preco + ($preco * $ipi / 100);
			$total += $preco;
		}
		*/
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

			$preco_sem_ipi = $qtde * $preco;
			if ($consumidor_final <> 'f') {
				$preco = $preco + ($preco_sem_ipi * $ipi / 100);
			}
			$total += $preco;

			$valor_total = $qtde * $preco;
			$total_geral = $total_geral + $total;

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
			echo "	<TD class='table_line1'>$origem &nbsp;</TD>\n";
			echo "	<TD class='table_line1' style='padding-right:7;'>".number_format ($preco_sem_ipi,2,',','.')." &nbsp;</TD>\n";
			echo "	<TD class='table_line1' style='padding-right:7;'>".number_format ($total,2,',','.')." &nbsp;</TD>\n";
			echo "	<TD class='table_line1' style='padding-right:7;'>$ipi %</TD>\n";
			echo "</TR>\n";
		}

		if (strlen($desconto_peca) > 0 AND strlen($total_geral) > 0) 
			$total_geral = $total_geral - ($total_geral * ($desconto_peca / 100));

		$total_os = $total_servicos + $total_geral;

	}else{
		for($i=0; $i<4;$i++){
?>
			<TR>
				<TD class="table_line">&nbsp;</TD>
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
}else{
	for($i=0; $i<4;$i++){
?>
		<TR>
			<TD class="table_line">&nbsp;</TD>
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

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">SELO:</TD>
	<TD class="conteudo"   width="120"><? echo $selo; ?> &nbsp;</TD>
	<TD class="titulo">LIBERAÇÃO FINANCEIRO</TD>
	<TD class="conteudo"   width="80">____/___/_____</TD>
	<TD class="titulo">VISTO</TD>
	<TD class="conteudo" colspan="2">&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">LACRE ENCONTRADO</TD>
	<TD class="conteudo"><? echo $lacre_encontrado; ?> &nbsp;</TD>
	<TD class="titulo">TÉCNICO</TD>
	<TD class="conteudo" width="100"><? echo $tecnico; ?> &nbsp;</TD>
	<TD class="titulo">LACRE</TD>
	<TD class="conteudo" width="120"><? echo $lacre; ?> &nbsp;</TD>
</TR>
</TABLE>


<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">CLASSIF. OS</TD>
	<TD class="conteudo" width="80"><? echo $classificacao_os; ?> &nbsp;</TD>
	<TD class="titulo">TAXA REGULAGEM (C)</TD>
	<TD class="conteudo" width="80">
	<?
	if (strlen ($os) > 0 AND strlen($modo) > 0) 
		echo "R$ ". number_format ($regulagem_peso_padrao,2,",","."); 
	?>&nbsp;</TD>
	<TD class="titulo">TOTAL RECUP. (A)</TD>
	<TD class="conteudo" width="100">
	<? 
	//if($total_geral_rec > 0) 
	echo number_format ($total_geral_rec,2,',','.');
	?>&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">COND. PAGTO</TD>
	<TD class="conteudo"><? echo $condicao; ?> &nbsp;</TD>
	<TD class="titulo">TAXA CERT. CONF. (D)</TD>
	<TD class="conteudo"><?
	if (strlen ($os) > 0 AND strlen($modo) > 0)
		echo "R$ ". number_format ($certificado_conformidade,2,",","."); 
	?>&nbsp;</TD>
	<TD class="titulo">TOTAL PEÇAS (B)</TD>
	<TD class="conteudo"><? if($total_geral > 0) echo number_format ($total_geral,2,',','.'); ?>&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo" rowspan="3" colspan="3">Carimbo e Assinatura</TD>
	<TD class="conteudo" rowspan="3">&nbsp;</TD>
	<TD class="titulo">MÃO-DE-OBRA (E)</TD>
	<TD class="conteudo"><? 
	if($mao_de_obra > 0){
		echo number_format ($mao_de_obra,2,",","."); 
	}
	?>&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">TAXA DE VISITA (F)</TD>
	<TD class="conteudo">
	<?
	if (strlen ($os) > 0 AND $taxa_visita > 0  AND strlen($origem) > 0)
		echo number_format ($taxa_visita,2,",",".");
	?>
	&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo">TOTAL GERAL SEM IPI <BR>(A+B+C+D+E+F)</TD>
	<TD class="conteudo"><? 
	if (strlen($modo) > 0){
		$total_geral_sem_ipi = $total_geral_rec + $total_geral + $regulagem_peso_padrao + $certificado_conformidade + $mao_de_obra + $taxa_visita;
		if($total_geral_sem_ipi > 0) echo number_format ($total_geral_sem_ipi,2,",","."); 
	}
	?>
	&nbsp;</TD>
</TR>
<TR>
	<TD class="titulo" colspan="6">A ASSINATURA DO CLIENTE CONFIRMA A EXECUÇÃO DO SERVIÇO E EVENTUAL TROCA DE PEÇAS, BEM COMO APROVA OS PREÇOS COBRADOS</TD>
</TR>
</TABLE>

<BR><BR>

</BODY>
</html>
<script>
	window.print();
</script>