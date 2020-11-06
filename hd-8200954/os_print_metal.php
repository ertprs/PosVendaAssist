<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';


include 'funcoes.php';

if (strlen($_GET['os_revenda']) > 0)  $os_revenda = trim($_GET['os_revenda']);

if(strlen($os_revenda) > 0){
	// seleciona do banco de dados
	$sql = "SELECT   tbl_os_revenda.sua_os                                                ,
					 tbl_os_revenda.obs                                                   ,
					 to_char(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS data_abertura  ,
					 to_char(tbl_os_revenda.digitacao,'DD/MM/YYYY')     AS data_digitacao ,
					 tbl_revenda.nome        AS revenda_nome                              ,
					 tbl_revenda.cnpj        AS revenda_cnpj                              ,
					 tbl_revenda.fone        AS revenda_fone                              ,
					 tbl_revenda.email       AS revenda_email                             ,
					 tbl_revenda.endereco    AS revenda_endereco                          ,
					 tbl_revenda.numero      AS revenda_numero                            ,
					 tbl_revenda.complemento AS revenda_complemento                       ,
					 tbl_revenda.bairro      AS revenda_bairro                            ,
					 tbl_posto_fabrica.codigo_posto AS posto_codigo                       ,
					 tbl_posto.nome    AS posto_nome                                      ,
					 to_char(tbl_os_revenda.data_nf      ,'DD/MM/YYYY') AS data_nf        ,
					 tbl_os_revenda.nota_fiscal                                           ,
					 tbl_os_revenda.consumidor_nome                                       ,
					 tbl_os_revenda.consumidor_cnpj                                       ,
					 tbl_os_revenda.consumidor_email
			FROM	 tbl_os_revenda
			LEFT JOIN	 tbl_revenda ON tbl_os_revenda.revenda = tbl_revenda.revenda
			JOIN	 tbl_fabrica USING (fabrica)
			LEFT JOIN tbl_posto USING (posto)
			LEFT JOIN tbl_posto_fabrica
			ON		 tbl_posto_fabrica.posto = tbl_posto.posto
			AND		 tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
			WHERE	 tbl_os_revenda.os_revenda = $os_revenda
			AND		 tbl_os_revenda.posto = $login_posto
			AND		 tbl_os_revenda.fabrica = $login_fabrica ";
	$res = pg_exec($con, $sql);

	if (pg_numrows($res) > 0){
		$sua_os          = pg_result($res,0,sua_os);
		$data_abertura   = pg_result($res,0,data_abertura);
		$data_digitacao  = pg_result($res,0,data_digitacao);
		$revenda_nome    = pg_result($res,0,revenda_nome);
		$revenda_cnpj    = pg_result($res,0,revenda_cnpj);
		$revenda_fone    = pg_result($res,0,revenda_fone);
		$revenda_email   = pg_result($res,0,revenda_email);
		$revenda_endereco= pg_result($res,0,revenda_endereco);
		$revenda_numero  = pg_result($res,0,revenda_numero);
		$revenda_complemento = pg_result($res,0,revenda_complemento);
		$revenda_bairro  = pg_result($res,0,revenda_bairro);
		$posto_codigo    = pg_result($res,0,posto_codigo);
		$posto_nome      = pg_result($res,0,posto_nome);
		$data_nf         = pg_result($res,0,data_nf);
		$nota_fiscal     = pg_result($res,0,nota_fiscal);
		$consumidor_nome = pg_result($res,0,consumidor_nome);
		$consumidor_cnpj = pg_result($res,0,consumidor_cnpj);
		$consumidor_email= pg_result($res,0,consumidor_email);
		$obs             = pg_result($res,0,obs);
	}else{
		echo "Erro... OS GEO não encontrada.";
		exit;
	}
}


$title = "Ordem de Serviço GEO Metais - Impresso"; 

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
	font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: xx-small;
	text-align: left;
	color: #000000;
	background: #D0D0D0;
	border-bottom: dotted 1px #a0a0a0;
	border-right: dotted 1px #a0a0a0;
	border-left: dotted 1px #a0a0a0;
	padding: 1px,1px,1px,1px;
	padding-left: 3px;
}

.conteudo {
	font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	text-align: left;
	background: #ffffff;
	border-right: dotted 1px #a0a0a0;
	border-left: dotted 1px #a0a0a0;
	padding: 1px,1px,1px,1px;
	padding-left: 3px;
}

.borda {
	border: solid 1px #c0c0c0;
}
.etiqueta {
	width: 110px;
	font:50% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;
	text-align: center
}
</style>
<style type='text/css' media='print'>
.noPrint {display:none;}
</style> 

<body>

<div class='noPrint'>
<input type=button name='fbBtPrint' value='Versão Matricial'
onclick="window.location='os_revenda_print_matricial.php?os_revenda=<? echo $os_revenda; ?>'">
<br>
<hr class='noPrint'>
</div>

<TABLE width="600px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD><IMG SRC="logos/cabecalho_print_<? echo strtolower ($login_fabrica_nome) ?>.gif" ALT="ORDEM DE SERVIÇO"></TD>
</TR>
</TABLE>
<?
if ($login_fabrica == 19 ) {
	echo '<TABLE width="600px" border="0" cellspacing="1" cellpadding="0">';
	echo '<TR>';
	echo '<TD class="titulo"ALIGN="CENTER" ><FONT SIZE="2"><B>FAVOR IMPRIMIR EM DUAS VIAS</B></FONT></TD>';
	echo '</TR>';
	echo '</TABLE>';
}
?>
<br>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo" colspan="4">Informações sobre a Ordem de Serviço GEO Metais</TD>
</TR>
<TR>
	<TD class="titulo">OS FABRICANTE</TD>
	<TD class="titulo">DATA DA ABERTURA DA OS</TD>
	<TD class="titulo">DATA DA DIGITAÇÃO DA OS</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $sua_os ?></TD>
	<TD class="conteudo"><? echo $data_abertura ?></TD>
	<TD class="conteudo"><? echo $data_digitacao ?></TD>
</TR>
</TABLE>
<? if($login_fabrica== 19) {$aux_revenda = "DO ATACADO";} else {$aux_revenda = "DA REVENDA";}?>
<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">NOME <?=$aux_revenda;?></TD>
	<TD class="titulo">CNPJ <?=$aux_revenda;?></TD>
	<TD class="titulo">FONE</TD>
	<TD class="titulo">E-MAIL</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $revenda_nome ?></TD>
	<TD class="conteudo"><? echo $revenda_cnpj ?></TD>
	<TD class="conteudo"><? echo $revenda_fone ?></TD>
	<TD class="conteudo"><? echo $revenda_email ?></TD>
</TR>
</TABLE>

<? if($login_fabrica == 24){//HD5492?>
<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">ENDEREÇO</TD>
	<TD class="titulo">Nº</TD>
	<TD class="titulo">COMPL.</TD>
	<TD class="titulo">BAIRRO</TD>
</TR>
<TR>
	<TD class="conteudo"><? if(strlen($revenda_endereco) > 0) echo $revenda_endereco; else echo "&nbsp;"; ?></TD>
	<TD class="conteudo"><? if(strlen($revenda_numero) > 0) echo $revenda_numero; else echo "&nbsp;"; ?></TD>
	<TD class="conteudo"><? if(strlen($revenda_complemento) > 0) echo $revenda_complemento; else echo "&nbsp;"; ?></TD>
	<TD class="conteudo"><? if(strlen($revenda_bairro) > 0) echo $revenda_bairro; else echo "&nbsp;"; ?></TD>
</TR>
</TABLE>

<?}?>

<?if($login_fabrica ==1){?>
<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">NOME DO CONSUMIDOR</TD>
	<TD class="titulo">CPF/CNPJ DO CONSUMIDOR</TD>
	<TD class="titulo">EMAIL DO CONSUMIDOR</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $consumidor_nome ?></TD>
	<TD class="conteudo"><? echo $consumidor_cnpj ?></TD>
	<TD class="conteudo"><? echo $consumidor_email ?></TD>
</TR>
</TABLE>
<?}?>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">CÓDIGO DO POSTO</TD>
	<TD class="titulo">NOME DO POSTO</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $posto_codigo ?></TD>
	<TD class="conteudo"><? echo $posto_nome ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">NOTA FISCAL</TD>
	<TD class="titulo">DATA NOTA</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $nota_fiscal ?></TD>
	<TD class="conteudo"><? echo $data_nf ?></TD>
</TR>
</TABLE>


<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">OBSERVAÇÕES</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $obs ?></TD>
</TR>
</TABLE>

<TABLE width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD>&nbsp;</TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">PRODUTOS</TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">REFERÊNCIA PRODUTO</TD>
	<TD class="titulo">DESCRIÇÃO DO PRODUTO</TD>
<?	if($login_fabrica == 19){
		echo "<TD class='titulo'>QTDE</TD>";
	}else{
		echo "<TD class='titulo'>";
			if($login_fabrica==35){
				echo "PO#";
			}else{
				echo "NÚMERO DE SÉRIE";
			}
		echo "</TD>";
	}
?>
</TR>

<?
	// monta o FOR
	$qtde_item = 20;

		if ($os_revenda){
			// seleciona do banco de dados
			$sql = "SELECT   tbl_os_revenda_item.os_revenda_item ,
							 tbl_os_revenda_item.produto         ,
							 tbl_os_revenda_item.serie           ,
							 tbl_os_revenda_item.qtde             ,
							 tbl_produto.referencia              ,
							 tbl_produto.descricao               
					FROM	 tbl_os_revenda
					JOIN	 tbl_os_revenda_item
					ON		 tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
					JOIN	 tbl_produto
					ON		 tbl_produto.produto = tbl_os_revenda_item.produto
					WHERE	 tbl_os_revenda.os_revenda = $os_revenda
					AND		 tbl_os_revenda.posto      = $login_posto
					AND		 tbl_os_revenda.fabrica    = $login_fabrica ";

			$res = pg_exec($con, $sql);

			for ($i=0; $i<pg_numrows($res); $i++)
			{

				$os_revenda_item    = pg_result($res,$i,os_revenda_item);
				$referencia_produto = pg_result($res,$i,referencia);
				$qtde               = pg_result($res,$i,qtde);
				$produto_descricao  = pg_result($res,$i,descricao);
				$produto_serie      = pg_result($res,$i,serie);
?>
<TR>
	<TD class="conteudo"><? echo $referencia_produto ?></TD>
	<TD class="conteudo"><? echo $produto_descricao ?></TD>
	<?
	if($login_fabrica == 19){
		echo "<TD class='conteudo'> $qtde </TD>";
	}else{
		echo "<TD class='conteudo'>$produto_serie </TD>";
	}
	?>
</TR>
<?
			}
		}
?>
</TABLE>

<br>

<TABLE width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class='conteudo'><h2>Em: <? echo $data_abertura ?></h2></TD>
</TR>
<TR>
	<TD class='conteudo'><h2><? echo $revenda_nome ?> - Assinatura:  _________________________________________</h2></TD>
</TR>
</TABLE>
<?if($login_fabrica==19){?>

<TABLE width="650px" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD><IMG SRC="imagens/cabecalho_os_corte.gif" ALT=""></TD> <!--  IMAGEM CORTE -->
</TR>
</TABLE><BR>
<TABLE width="650px" border="1" cellspacing="0" cellpadding="0">
<TR>
	<TD class="etiqueta">
		<? echo "<b>OS <font size='2px'>$sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
	</TD>
	<TD class="etiqueta">
	<? echo "<b>OS <font size='2px'>$sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
	</TD>
	<TD class="etiqueta">
		<? echo "<b>OS <font size='2px'>$sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
	</TD>
	<TD class="etiqueta">
		<? echo "<b>OS <font size='2px'>$sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
	</TD>
	<TD class="etiqueta">
		<? echo "<b>OS <font size='2px'>$sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
	</TD>
</TR>
<TR>
	<TD class="etiqueta">
		<? echo "<b>OS <font size='2px'>$sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
	</TD>
	<TD class="etiqueta">
	<? echo "<b>OS <font size='2px'>$sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
	</TD>
	<TD class="etiqueta">
		<? echo "<b>OS <font size='2px'>$sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
	</TD>
	<TD class="etiqueta">
		<? echo "<b>OS <font size='2px'>$sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
	</TD>
	<TD class="etiqueta">
		<? echo "<b>OS <font size='2px'>$sua_os</font></b><BR> Cliente:$consumidor_nome<BR>Nota Fiscal: $nota_fiscal<br>Data:$data_abertura"?>
	</TD>
</TR>
</TABLE>
<?}?>
<br><br>
<?php
// HD 3741276 - QRCode
include_once 'os_print_qrcode.php';
?>
<script language="JavaScript">
	window.print();
</script>

</BODY>
</html>

