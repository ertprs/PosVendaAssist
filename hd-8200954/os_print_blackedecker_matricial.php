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
					tbl_os.consumidor_celular                                      ,
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
					tbl_posto_fabrica.contato_endereco  AS endereco                ,
					tbl_posto_fabrica.contato_numero    AS numero                  ,
					tbl_posto_fabrica.contato_cep       AS cep                     ,
					tbl_posto_fabrica.contato_cidade    AS cidade                  ,
					tbl_posto_fabrica.contato_estado    As estado                  ,
					tbl_posto_fabrica.contato_fone_comercial as fone                                                 ,
					tbl_posto.cnpj                                                 ,
					tbl_posto.ie                                                   ,
					tbl_posto_fabrica.codigo_posto                                 ,
					tbl_os.consumidor_revenda                                      ,
					tbl_os.excluida,
                    tbl_os_campo_extra.campos_adicionais
			FROM    tbl_os
			JOIN    tbl_produto USING (produto)
			JOIN    tbl_posto   USING (posto)
			JOIN 	tbl_posto_fabrica 	 ON tbl_posto.posto = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_defeito_reclamado USING (defeito_reclamado)
			LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
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
		$consumidor_celular		= pg_result ($res,0,consumidor_celular);
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
		$campos_adicionais = json_decode(pg_fetch_result($res, 0, 'campos_adicionais'), true);

        $consumidor_profissao = '';

        if (!empty($campos_adicionais) and array_key_exists('consumidor_profissao', $campos_adicionais)) {
            $consumidor_profissao = utf8_decode($campos_adicionais['consumidor_profissao']);
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
	font-family: Draft;
}

.titulo {
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	color: #000000;
	background: #ffffff;
	border-bottom: dotted 0px #000000;
	border-right: dotted 0px #a0a0a0;
 	border-left: dotted 0px #000000;
	padding: 0px,0px,0px,0px;
}

.conteudo {
	font-size: 13px;
	text-align: left;
	background: #ffffff;
	border-right: dotted 0px #a0a0a0;
	border-left: dotted 0px #a0a0a0;
	padding: 1px,1px,1px,1px;
}

.borda {
	border: solid 0px #c0c0c0;
}

.etiqueta {
	font-size: 11px
	width: 110px;
	text-align: center
}

h2 {
	color: #000000
}

</style>
<style type='text/css' media='print'>
.noPrint {display:none;}
</style>
<?
if ($consumidor_revenda == 'R')
	$consumidor_revenda = 'REVENDA';
else
	if ($consumidor_revenda == 'C')
		$consumidor_revenda = 'CONSUMIDOR';
?>

<body>

<div class='noPrint'>
<input type=button name='fbBtPrint' value='Versão Jato de Tinta / Laser'
onclick="window.location='os_print_blackedecker.php?os=<? echo $os; ?>'">
<br>
<hr class='noPrint'>
</div>

<TABLE width="650px" border="0" cellspacing="1" cellpadding="0">
<TR class="titulo" style="text-align: center;">
<?

	if($login_fabrica == 1){
		$img_contrato = "logos/logo_black_2016.png";
	}
	// if ($cliente_contrato == 'f'){
	// 	$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
	// }else{
	// 	$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
	// }
?>
	<TD class="conteudo" rowspan="2" style="text-align: left;"><IMG style='width: 117px;' SRC="<? echo $img_contrato ?>" ALT="ORDEM DE SERVIÇO"><br>VIA DO CLIENTE</TD>
	<TD><? echo $posto_nome ?></TD>
	<TD>DATA EMISSÃO</TD>
	<TD>NÚMERO</TD>
</TR>

<TR class="titulo">
	<TD style="text-align: center; width: 350px; ">
<?
	########## CABECALHO COM DADOS DO POSTOS ##########
	echo $posto_endereco .",".$posto_numero." - CEP ".$posto_cep."<br>";
	echo $posto_cidade ." - ".$posto_estado." - Telefone: ".$posto_fone."<br>";
	echo "CNPJ/CPF ".$posto_cnpj ." - IE/RG ".$posto_ie;
?>
	</TD>
	<TD style="text-align: center;">
<?	########## DATA DE ABERTURA ########## ?>
		<b><? echo $data_abertura ?></b>
	</TD>
	<TD>
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
	<TD class="titulo">CELULAR</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $consumidor_nome ?></TD>
	<TD class="conteudo"><? echo $consumidor_cidade ?></TD>
	<TD class="conteudo"><? echo $consumidor_estado ?></TD>
	<TD class="conteudo"><? echo $consumidor_fone ?></TD>
	<TD class="conteudo"><? echo $consumidor_celular ?></TD>
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
	<TD class="titulo">PROFISSÃO</TD>
	<TD class="titulo">CPF</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $consumidor_cep ?></TD>
	<TD class="conteudo"><?= $consumidor_profissao ?></TD>
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
	<TD style="text-align: left;">
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
	<TD class="titulo">CELULAR</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $consumidor_nome ?></TD>
	<TD class="conteudo"><? echo $consumidor_cidade ?></TD>
	<TD class="conteudo"><? echo $consumidor_estado ?></TD>
	<TD class="conteudo"><? echo $consumidor_fone ?></TD>
	<TD class="conteudo"><? echo $consumidor_celular ?></TD>
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
	<TD class="titulo">PROFISSÃO</TD>
	<TD class="titulo">CPF</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $consumidor_cep ?></TD>
	<TD class="conteudo"><?= $consumidor_profissao ?></TD>
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

<TABLE class="borda" width="650px" border="1" cellspacing="0" cellpadding="0">
<TR>
	<TD class="titulo" colspan="6">Peças Utilizadas:</td>
</TR>
<TR>
	<TD style="font-family: Draft; font-size:10px" width="5%" ><center>QTD</center></td>
	<TD style="font-family: Draft; font-size:10px" width="23%"><center>CÓDIGO</center></td>
	<TD style="font-family: Draft; font-size:10px" width="22%"><center>DESCRIÇÃO</center></td>
	<TD style="font-family: Draft; font-size:10px" width="5%" ><center>QTD</center></td>
	<TD style="font-family: Draft; font-size:10px" width="23%"><center>CÓDIGO</center></td>
	<TD style="font-family: Draft; font-size:10px" width="22%"><center>DESCRIÇÃO</center></td>
</TR>
<TR>
	<TD>&nbsp;</td>
	<TD>&nbsp;</td>
	<TD>&nbsp;</td>
	<TD>&nbsp;</td>
	<TD>&nbsp;</td>
	<TD>&nbsp;</td>
</TR>
<TR>
	<TD>&nbsp;</td>
	<TD>&nbsp;</td>
	<TD>&nbsp;</td>
	<TD>&nbsp;</td>
	<TD>&nbsp;</td>
	<TD>&nbsp;</td>
</TR>
<TR>
	<TD>&nbsp;</td>
	<TD>&nbsp;</td>
	<TD>&nbsp;</td>
	<TD>&nbsp;</td>
	<TD>&nbsp;</td>
	<TD>&nbsp;</td>
</TR>
<TR>
	<TD>&nbsp;</td>
	<TD>&nbsp;</td>
	<TD>&nbsp;</td>
	<TD>&nbsp;</td>
	<TD>&nbsp;</td>
	<TD>&nbsp;</td>
</TR>
</TABLE>

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
	<TD><h2><? echo strtoupper($consumidor_nome); ?> - Assinatura: &nbsp; <? echo str_repeat("_",50	 - strlen($consumidor_nome)); ?> &nbsp;&nbsp;&nbsp; Em: _____/_____/__________</h2></TD>
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

<!-- hd 21896 - Francisco Ambrozio. Inclusão do laudo técnico -->
<?
$sql = "SELECT tbl_laudo_tecnico_os.*
			FROM tbl_laudo_tecnico_os
			WHERE os = $os;";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res) > 0){
?>
		<BR><BR>
		<br style="page-break-before:always">

		<TABLE width="650px" border="0" cellspacing="1" cellpadding="0">
			<TR class="titulo" style="text-align: center;">
			<?
			if ($cliente_contrato == 'f')
				$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
			else
				$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";
			?>
				<TD class="conteudo" rowspan="2" style="text-align: left;"><IMG SRC="<? echo $img_contrato ?>" ALT="ORDEM DE SERVIÇO"><br>LAUDO TÉCNICO</TD>
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

		<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
			<TR>
				<TD class="titulo">TÍTULO</TD>
				<TD class="titulo">AFIRMATIVA</TD>
				<TD class="titulo">OBSERVAÇÃO</TD>
			</TR>

		<?
		for($i=0;$i<pg_numrows($res);$i++){
			$laudo		 = pg_result($res,$i,laudo_tecnico_os);
			$titulo      = pg_result($res,$i,titulo);
			$afirmativa  = pg_result($res,$i,afirmativa);
			$observacao  = pg_result($res,$i,observacao);

			echo "<tr>";
			echo "<td class='conteudo' align='left'>&nbsp;$titulo</td>";
			if(strlen($afirmativa) > 0){
				echo "<td class='conteudo'><CENTER>"; if($afirmativa == 't'){ echo "Sim</CENTER></td>";} else { echo "Não</CENTER></td>";}
			}else{
				echo "<td class='conteudo'>&nbsp;</td>";
			}
			if(strlen($observacao) > 0){
				echo "<td class='conteudo'><CENTER>$observacao</CENTER></td>";
			}else{
				echo "<td class='conteudo'>&nbsp;</td>";
			}
			echo "</tr>";
		}
		echo "</table>";
	}
?>
<!-- Fim da inclusão laudo técnico -->

<script language="JavaScript">
	window.print();
</script>


</BODY>
</html>
