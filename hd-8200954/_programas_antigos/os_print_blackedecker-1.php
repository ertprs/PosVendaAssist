<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';


$os   = $_GET['os'];
$modo = $_GET['modo'];

if ($login_fabrica <> 1) {
	header ("Location: menu_os.php");
	exit;
}

#------------ Le OS da Base de dados ------------#
if (strlen ($os) > 0) {
	$sql = "SELECT  tbl_os.sua_os                                                  ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura  ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
					tbl_produto.referencia                                         ,
					tbl_produto.descricao                                          ,
					tbl_produto.voltagem                                           ,
					tbl_defeito_reclamado.descricao AS defeito_cliente             ,
					tbl_os.cliente                                                 ,
					tbl_os.revenda                                                 ,
					tbl_os.serie                                                   ,
					tbl_os.codigo_fabricacao                                       ,
					tbl_os.consumidor_cpf                                         ,
					tbl_os.consumidor_nome                                         ,
					tbl_os.consumidor_cidade                                       ,
					tbl_os.consumidor_estado                                       ,
					tbl_os.consumidor_fone                                         ,
					tbl_os.consumidor_endereco                                     ,
					tbl_os.consumidor_numero                                       ,
					tbl_os.consumidor_cep                                          ,
					tbl_os.consumidor_complemento                                  ,
					tbl_os.consumidor_bairro                                       ,
					tbl_os.revenda_cnpj                                            ,
					tbl_os.revenda_nome                                            ,
					tbl_os.nota_fiscal                                             ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf        ,
					tbl_os.defeito_reclamado                                       ,
					tbl_os.defeito_reclamado_descricao                             ,
					tbl_os.acessorios                                              ,
					tbl_os.aparencia_produto                                       ,
					tbl_os.obs                                                     ,
					tbl_posto.nome                                                 ,
					tbl_posto.endereco                                             ,
					tbl_posto.numero                                               ,
					tbl_posto.cep                                                  ,
					tbl_posto.cidade                                               ,
					tbl_posto.estado                                               ,
					tbl_posto.fone                                                 ,
					tbl_posto.cnpj                                                 ,
					tbl_posto.ie                                                   ,
					tbl_posto_fabrica.codigo_posto                                 ,
					tbl_os.consumidor_revenda                                      ,
					tbl_os.excluida
			FROM    tbl_os
			JOIN    tbl_produto USING (produto)
			JOIN    tbl_posto   USING (posto)
			JOIN 	tbl_posto_fabrica 	 ON tbl_posto.posto = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_defeito_reclamado USING (defeito_reclamado)
			WHERE   tbl_os.os = $os
			AND     tbl_os.posto = $login_posto";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$sua_os					= pg_result ($res,0,sua_os);
		$codigo_posto			= pg_result ($res,0,codigo_posto);
		$data_abertura			= pg_result ($res,0,data_abertura);
		$data_fechamento		= pg_result ($res,0,data_fechamento);
		$referencia				= pg_result ($res,0,referencia);
		$descricao					= pg_result ($res,0,descricao);
		$voltagem				= pg_result ($res,0,voltagem);
		$serie					= pg_result ($res,0,serie);
		$codigo_fabricacao		= pg_result ($res,0,codigo_fabricacao);
		$cliente				= pg_result ($res,0,cliente);
		$revenda				= pg_result ($res,0,revenda);
		$consumidor_cpf			= pg_result ($res,0,consumidor_cpf);
		$consumidor_nome		= pg_result ($res,0,consumidor_nome);
		$consumidor_cidade		= pg_result ($res,0,consumidor_cidade);
		$consumidor_estado		= pg_result ($res,0,consumidor_estado);
		$consumidor_fone		= pg_result ($res,0,consumidor_fone);
		$consumidor_endereco	= pg_result ($res,0,consumidor_endereco);
		$consumidor_numero		= pg_result ($res,0,consumidor_numero);
		$consumidor_cep			= pg_result ($res,0,consumidor_cep);
		$consumidor_complemento	= pg_result ($res,0,consumidor_complemento);
		$consumidor_bairro		= pg_result ($res,0,consumidor_bairro);
		$revenda_cnpj			= pg_result ($res,0,revenda_cnpj);
		$revenda_nome			= pg_result ($res,0,revenda_nome);
		$nota_fiscal			= pg_result ($res,0,nota_fiscal);
		$data_nf				= pg_result ($res,0,data_nf);
		$defeito_reclamado		= pg_result ($res,0,defeito_reclamado);
		$aparencia_produto		= pg_result ($res,0,aparencia_produto);
		$acessorios				= pg_result ($res,0,acessorios);
		$defeito_cliente		= pg_result ($res,0,defeito_cliente);
		$defeito_reclamado_descricao = pg_result ($res,0,defeito_reclamado_descricao);
		$posto_nome			= pg_result ($res,0,nome);
		$posto_endereco		= pg_result ($res,0,endereco);
		$posto_numero		= pg_result ($res,0,numero);
		$posto_cep			= pg_result ($res,0,cep);
		$posto_cidade		= pg_result ($res,0,cidade);
		$posto_estado		= pg_result ($res,0,estado);
		$posto_fone			= pg_result ($res,0,fone);
		$posto_cnpj			= pg_result ($res,0,cnpj);
		$posto_ie			= pg_result ($res,0,ie);
		$consumidor_revenda = pg_result ($res,0,consumidor_revenda);
		$obs				= pg_result ($res,0,obs);
		$excluida			= pg_result ($res,0,excluida);

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
	}
	
if ($login_fabrica == 1) {
	$sql =	"SELECT tipo_os_cortesia
			FROM  tbl_os
			WHERE fabrica = $login_fabrica
			AND   os = $os;";
	$res = pg_exec($con,$sql);
	if (pg_numrows($res) == 1) {
		$tipo_os_cortesia = pg_result($res,0,tipo_os_cortesia);
		if ($tipo_os_cortesia == "Compressor") {
			$compressor='t';
		}
	}
}


	$sql = "UPDATE tbl_os_extra SET	impressa = current_timestamp WHERE os = $os;";
	$res = pg_exec($con,$sql);
//echo $sql;

}

if (strlen($sua_os) == 0) $sua_os = $os;

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
	margin: 0px;
}

.titulo {
	font-family: normal Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 8px;
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
	border-left: dotted 1px #a0a0a0;
	padding: 1px,1px,1px,1px;
}

.borda {
	border: solid 1px #c0c0c0;
}

.etiqueta {
	width: 110px;
	font:9px Tahoma, Verdana, Arial, Helvetica, Sans-Serif;
	text-align: center
}

h2 {
	font:60% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;
	color: #000000
}

</style>
<?
if ($consumidor_revenda == 'R')
	$consumidor_revenda = 'REVENDA';
else 
	if ($consumidor_revenda == 'C')
		$consumidor_revenda = 'CONSUMIDOR';
?>

<body>
<TABLE width="650px" border="0" cellspacing="1" cellpadding="0">
<TR class="titulo" style="text-align: center;">
<?
	if ($cliente_contrato == 'f') 
		$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
	else
		$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";

?>
	<TD class="conteudo" rowspan="2" style="text-align: left;"><IMG SRC="<? echo $img_contrato ?>" ALT="ORDEM DE SERVIÇO"><br>VIA DO CLIENTE</TD>
	<TD style="font-size: 09px;"><? echo $posto_nome ?></TD>
	<TD>DATA EMISSÃO</TD>
	<TD>NÚMERO</TD>
</TR>

<TR class="titulo">
	<TD style="font-size: 09px; text-align: center; width: 350px; ">
<?
	########## CABECALHO COM DADOS DO POSTOS ########## 
	echo $posto_endereco .",".$posto_numero." - CEP ".$posto_cep."<br>";
	echo $posto_cidade ." - ".$posto_estado." - Telefone: ".$posto_fone."<br>";
	echo "CNPJ/CPF ".$posto_cnpj ." - IE/RG ".$posto_ie;
?>
	</TD>
	<TD style="border: 1px solid #a0a0a0; font-size: 14px;">
<?	########## DATA DE ABERTURA ########## ?>
		<b><? echo $data_abertura ?></b>
	</TD>
	<TD style="border: 1px solid #a0a0a0; font-size: 14px;">
<?	########## SUA OS ########## ?>
	<?
		if (strlen($consumidor_revenda) == 0){
			echo "<center><b> $codigo_posto$sua_os </b></center>";
		}else{
			echo "<center><b> $codigo_posto$sua_os <br> $consumidor_revenda  </b></center>";
		}
	?>
	</TD>
</tr>
</TABLE>

<?
if ($login_fabrica == 1) $colspan = 7;
else $colspan = 5;
?>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">

<? if ($excluida == "t") { ?>
<TR>
	<TD colspan="<? echo $colspan ?>" bgcolor="#FFE1E1" align="center"><h1>ORDEM DE SERVIÇO EXCLUÍDA</h1></TD>
</TR>
<? } ?>

<TR>
	<TD class="titulo" colspan="<? echo $colspan ?>">Informações sobre a Ordem de Serviço</TD>
</TR>
<TR>
	<TD class="titulo">OS FABR.</TD>
	<TD class="titulo">DT ABERT. OS</TD>
	<TD class="titulo">REF.</TD>
	<TD class="titulo">DESCRIÇÃO</TD>
	<TD class="titulo">VOLT.</TD>
	<TD class="titulo">SÉRIE</TD>
	<TD class="titulo">CÓD. FABRICAÇÃO</TD>
</TR>
<TR>
	<TD class="conteudo"><b><? echo $codigo_posto.$sua_os ?></b></TD>
	<TD class="conteudo"><? echo $data_abertura ?></TD>
	<TD class="conteudo"><? echo $referencia ?></TD>
	<TD class="conteudo"><? echo $descricao ?></TD>
	<TD class="conteudo"><? echo $voltagem ?></TD>
	<TD class="conteudo"><? echo $serie ?></TD>
	<TD class="conteudo"><? echo $codigo_fabricacao ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">NOME DO CONSUMIDOR</TD>
	<TD class="titulo">CIDADE</TD>
	<TD class="titulo">ESTADO</TD>
	<TD class="titulo">FONE</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $consumidor_nome ?></TD>
	<TD class="conteudo"><? echo $consumidor_cidade ?></TD>
	<TD class="conteudo"><? echo $consumidor_estado ?></TD>
	<TD class="conteudo"><? echo $consumidor_fone ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">ENDEREÇO</TD>
	<TD class="titulo">NÚMERO</TD>
	<TD class="titulo">COMPLEMENTO</TD>
	<TD class="titulo">BAIRRO</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $consumidor_endereco ?></TD>
	<TD class="conteudo"><? echo $consumidor_numero ?></TD>
	<TD class="conteudo"><? echo $consumidor_complemento ?></TD>
	<TD class="conteudo"><? echo $consumidor_bairro ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">CEP</TD>
	<TD class="titulo">CPF</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $consumidor_cep ?></TD>
	<TD class="conteudo"><? echo $consumidor_cpf ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">DEFEITO APRESENTADO PELO CLIENTE</TD>
	<TD class="titulo">APARÊNCIA GERAL DO PRODUTO</TD>
</TR>
<TR>
	<TD class="conteudo"><? if(strlen($defeito_reclamado_descricao) > 0) echo $defeito_reclamado_descricao . "<br>"; echo $defeito_cliente ?></TD>
	<TD class="conteudo"><? echo $aparencia_produto ?></TD>
</TR>
</TABLE>

<!--
<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">APARÊNCIA GERAL DO PRODUTO</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $aparencia_produto ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">ACESSÓRIOS DEIXADOS PELO CLIENTE</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $acessorios ?></TD>
</TR>
</TABLE>
-->
<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">OBSERVAÇÃO</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $obs ?></TD>
</TR>
<TR>
	<TD class="conteudo"><b>CARO CLIENTE, O SEU PROBLEMA É DE NOSSO INTERESSE, POR ISSO NÃO HESITE EM NOS CONTATAR. CASO NÃO TENHA RECEBIDO A DEVIDA ATENÇÃO OU ESTEJA EM DIFICULDADES PARA VER A SOLUÇÃO, FALE COM A BLACK & DECKER 0800 7034644, SITE WWW.BLACKANDDECKER.COM.BR OU WWW.DEWALT.COM.BR</b>
</TD>
</TR>
</TABLE>

<TABLE width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD><IMG SRC="imagens/cabecalho_os_corte.gif" ALT=""></TD> <!--  IMAGEM CORTE -->
</TR>
</TABLE>

<TABLE width="650px" border="0" cellspacing="2" cellpadding="0">
<TR class="titulo">
	<TD style="font-size: 09px; text-align: left;">
<?
	########## CABECALHO COM DADOS DO POSTOS ##########
	echo "POSTO ".$codigo_posto ." - ". $posto_nome ." | CNPJ/CPF ".$posto_cnpj ." | IE/RG ".$posto_ie;
	//echo $posto_endereco .",".$posto_numero." - CEP ".$posto_cep."<br>";
	//echo $posto_cidade ." - ".$posto_estado." - Telefone: ".$posto_fone."<br>";
?>
	</TD>
</tr>
<TR>
	<TD class="conteudo">VIA DA BLACK & DECKER - ASSINADA PELO CLIENTE</TD> <!--  IMAGEM CORTE -->
</TR>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo" colspan="6">Informações sobre a Ordem de Serviço</TD>
</TR>
<TR>
	<TD class="titulo">OS FABR.</TD>
	<TD class="titulo">DT ABERT. OS</TD>
	<TD class="titulo">REF.</TD>
	<TD class="titulo">DESCRIÇÃO</TD>
	<TD class="titulo">VOLT.</TD>
	<TD class="titulo">SÉRIE</TD>
</TR>
<TR>
	<TD class="conteudo"><b><? echo $codigo_posto.$sua_os ?></b></TD>
	<TD class="conteudo"><? echo $data_abertura ?></TD>
	<TD class="conteudo"><? echo $referencia ?></TD>
	<TD class="conteudo"><? echo $descricao ?></TD>
	<TD class="conteudo"><? echo $voltagem ?></TD>
	<TD class="conteudo"><? echo $serie ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">NOME DO CONSUMIDOR</TD>
	<TD class="titulo">CIDADE</TD>
	<TD class="titulo">ESTADO</TD>
	<TD class="titulo">FONE</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $consumidor_nome ?></TD>
	<TD class="conteudo"><? echo $consumidor_cidade ?></TD>
	<TD class="conteudo"><? echo $consumidor_estado ?></TD>
	<TD class="conteudo"><? echo $consumidor_fone ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">ENDEREÇO</TD>
	<TD class="titulo">NÚMERO</TD>
	<TD class="titulo">COMPLEMENTO</TD>
	<TD class="titulo">BAIRRO</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $consumidor_endereco ?></TD>
	<TD class="conteudo"><? echo $consumidor_numero ?></TD>
	<TD class="conteudo"><? echo $consumidor_complemento ?></TD>
	<TD class="conteudo"><? echo $consumidor_bairro ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">CEP</TD>
	<TD class="titulo">CPF</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $consumidor_cep ?></TD>
	<TD class="conteudo"><? echo $consumidor_cpf ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo" colspan="5">Informações sobre a Revenda</TD>
</TR>
<TR>
	<TD class="titulo">CNPJ</TD>
	<TD class="titulo">NOME</TD>
	<TD class="titulo">NF N.</TD>
	<TD class="titulo">DATA NF</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $revenda_cnpj ?></TD>
	<TD class="conteudo"><? echo $revenda_nome ?></TD>
	<TD class="conteudo"><? echo $nota_fiscal ?></TD>
	<TD class="conteudo"><? echo $data_nf ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">ENDEREÇO</TD>
	<TD class="titulo">NÚMERO</TD>
	<TD class="titulo">COMPLEMENTO</TD>
	<TD class="titulo">BAIRRO</TD>
	<TD class="titulo">CEP</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $revenda_endereco ?></TD>
	<TD class="conteudo"><? echo $revenda_numero ?></TD>
	<TD class="conteudo"><? echo $revenda_complemento ?></TD>
	<TD class="conteudo"><? echo $revenda_bairro ?></TD>
	<TD class="conteudo"><? echo $revenda_cep ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">DEFEITO APRESENTADO PELO CLIENTE</TD>
	<TD class="titulo">APARÊNCIA GERAL DO PRODUTO</TD>
</TR>
<TR>
	<TD class="conteudo"><? if(strlen($defeito_reclamado_descricao) > 0) echo $defeito_reclamado_descricao . "<br>"; echo $defeito_cliente ?></TD>
	<TD class="conteudo"><? echo $aparencia_produto ?></TD>
</TR>
</TABLE>

<!--
<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">APARÊNCIA GERAL DO PRODUTO</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $aparencia_produto ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">ACESSÓRIOS DEIXADOS PELO CLIENTE</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $acessorios ?></TD>
</TR>
</TABLE>
-->
<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">OBSERVAÇÃO</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $obs ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="650px" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD class="titulo" colspan="6">Peças Utilizadas:</td>
</TR>
<TR>
	<TD style="border-bottom: dotted 1px #a0a0a0;" width="5%" ><center><font face="Verdana" size="1">QTD</font></center></td>
	<TD style="border-bottom: dotted 1px #a0a0a0;" width="23%"><center><font face="Verdana" size="1">CÓDIGO</font></center></td>
	<TD style="border-bottom: dotted 1px #a0a0a0;border-right: dotted 1px #a0a0a0" width="22%"><center><font face="Verdana" size="1">DESCRIÇÃO</font></center></td>
	<TD style="border-bottom: dotted 1px #a0a0a0;" width="5%" ><center><font face="Verdana" size="1">QTD</font></center></td>
	<TD style="border-bottom: dotted 1px #a0a0a0;" width="23%"><center><font face="Verdana" size="1">CÓDIGO</font></center></td>
	<TD style="border-bottom: dotted 1px #a0a0a0;" width="22%"><center><font face="Verdana" size="1">DESCRIÇÃO</font></center></td>
</TR>
<TR>
	<TD style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">&nbsp;</td>
	<TD style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">&nbsp;</td>
	<TD style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">&nbsp;</td>
	<TD style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">&nbsp;</td>
	<TD style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">&nbsp;</td>
	<TD style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">&nbsp;</td>
</TR>
<TR>
	<TD style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">&nbsp;</td>
	<TD style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">&nbsp;</td>
	<TD style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">&nbsp;</td>
	<TD style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">&nbsp;</td>
	<TD style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">&nbsp;</td>
	<TD style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">&nbsp;</td>
</TR>
<TR>
	<TD style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">&nbsp;</td>
	<TD style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">&nbsp;</td>
	<TD style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">&nbsp;</td>
	<TD style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">&nbsp;</td>
	<TD style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">&nbsp;</td>
	<TD style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">&nbsp;</td>
</TR>
<TR>
	<TD style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">&nbsp;</td>
	<TD style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">&nbsp;</td>
	<TD style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">&nbsp;</td>
	<TD style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">&nbsp;</td>
	<TD style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">&nbsp;</td>
	<TD style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">&nbsp;</td>
</TR>
</TABLE>
<? if($compressor=='t'){ ?>
<TABLE class="borda" width="650px" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD class='titulo' colspan='6'>Despesas de visitas:</td>
</TR>
<tr>
	<td nowrap rowspan='2' style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">
		<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Data da visita</font></td>
	<td nowrap  style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0" rowspan='2'>
		<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Hora início</font></td>
	<td nowrap  style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0" rowspan='2'>
		<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Hora fim</font></td>
	<td nowrap  style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0" rowspan='2'>
		<font size='1' face='Geneva, Arial, Helvetica, san-serif'>KM</font></td>
	<td nowrap  style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0" colspan='2' aling='center'>
		<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Despesas Adicionais</font></td>
</tr>
<tr>
	<td nowrap style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">
		<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Valor</font></td>
	<td nowrap style="border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0">
		<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Justificativa</font></td>
</tr>
<?
		$sql  = "SELECT tbl_os_visita.os_visita ,
						to_char(tbl_os_visita.data,'DD/MM/YYYY')               AS data         ,
						to_char(tbl_os_visita.hora_chegada_cliente, 'HH24:MI') AS hora_chegada_cliente ,
						to_char(tbl_os_visita.hora_saida_cliente, 'HH24:MI') AS hora_saida_cliente,
						tbl_os_visita.km_chegada_cliente                                       ,
						tbl_os_visita.justificativa_valor_adicional                            ,
						tbl_os_visita.valor_adicional
			FROM    tbl_os_visita
			WHERE   tbl_os_visita.os = $os
			ORDER BY tbl_os_visita.os_visita;";
		$res = pg_exec($con,$sql);

		$sql_tec = "SELECT tecnico from tbl_os_extra where os=$os";
		$res_tec = pg_exec($con,$sql_tec);
		$tecnico            = trim(@pg_result($res_tec,0,tecnico));
$qtde_visita=4;
		for ($y=0;$qtde_visita>$y;$y++){
			$os_visita            = trim(@pg_result($res,$y,os_visita));
			$visita_data          = trim(@pg_result($res,$y,data));
			$hr_inicio            = trim(@pg_result($res,$y,hora_chegada_cliente));
			$hr_fim               = trim(@pg_result($res,$y,hora_saida_cliente));
			$visita_km            = trim(@pg_result($res,$y,km_chegada_cliente));
			$justificativa_adicionais   = trim(@pg_result($res,$y,justificativa_valor_adicional));
			$valores_adicionais         = trim(@pg_result($res,$y,valor_adicional));
?>	
	<tr>
			<td nowrap align='center'  style='border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'><? echo $visita_data; ?></font>
			</td>

			<td nowrap align='center' style='border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'><? echo $hr_inicio; ?></font>
			 </td>

			<td nowrap align='center' style='border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'><? echo $hr_fim; ?></font>
			</td>

			<td nowrap align='center' style='border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'><? echo $visita_km; ?></font>
			</td>

			<td nowrap align='center' style='border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'> R$ <? echo $valores_adicionais; ?></font>
			</td>

			<td nowrap align='center' style='border-bottom: dotted 1px #a0a0a0; border-right: dotted 1px #a0a0a0'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'><?  echo $justificativa_adicionais ; ?></font>
</td>

			</tr>
	<?	}?>


?>



</TABLE>
<? } ?>
<TABLE class="borda" width="650px" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD class='titulo'>Diagnóstico:</td>
</TR>
<TR>
	<TD>&nbsp;</td>
</TR>
<TR>
	<TD>&nbsp;</td>
</TR>
</TABLE>

<TABLE width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD><h2><? echo $posto_cidade .", ". $data_abertura ?></h2></TD>
</TR>
<TR>
	<TD><h2><? echo strtoupper($consumidor_nome); ?> - Assinatura: &nbsp; <? echo str_repeat("_",75 - strlen($consumidor_nome)); ?> &nbsp;&nbsp;&nbsp; Em: _____/_____/__________</h2></TD>
</TR>
<TR>
	<TD><h2>Ficou satifeito com o serviço? &nbsp; __________________________________________</h2></TD>
</TR>
</TABLE>

<TABLE width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD><IMG SRC="imagens/cabecalho_os_corte.gif" ALT=""></TD> <!--  IMAGEM CORTE -->
</TR>
</TABLE>

<TABLE width="650px" border="1" cellspacing="2" cellpadding="0">
<TR>
	<TD class="etiqueta">
		<? echo "<b>OS n. $codigo_posto$sua_os - Ref. ". $referencia . "</b> <br> " . $descricao . "<br>N.Série $serie<br>$consumidor_nome<br>$consumidor_fone"?>
	</TD>
	<TD class="etiqueta">
		<? echo "<b>OS n. $codigo_posto$sua_os - Ref. ". $referencia . "</b> <br> " . $descricao . "<br>N.Série $serie<br>$consumidor_nome<br>$consumidor_fone"?>
	</TD>
	<TD class="etiqueta">
		<? echo "<b>OS n. $codigo_posto$sua_os - Ref. ". $referencia . "</b> <br> " . $descricao . "<br>N.Série $serie<br>$consumidor_nome<br>$consumidor_fone"?>
	</TD>
	<TD class="etiqueta">
		<? echo "<b>OS n. $codigo_posto$sua_os - Ref. ". $referencia . "</b> <br> " . $descricao . "<br>N.Série $serie<br>$consumidor_nome<br>$consumidor_fone"?>
	</TD>
	<TD class="etiqueta">
		<? echo "<b>OS n. $codigo_posto$sua_os - Ref. ". $referencia . "</b> <br> " . $descricao . "<br>N.Série $serie<br>$consumidor_nome<br>$consumidor_fone"?>
	</TD>

</TR>
</TABLE>



<script language="JavaScript">
	window.print();
</script>


</BODY>
</html>