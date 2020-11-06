<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

if (strlen($_GET['os_revenda']) > 0)  $os_revenda = trim($_GET['os_revenda']);

if(strlen($os_revenda) > 0){
	// seleciona do banco de dados
	$sql = "SELECT      tbl_os_revenda.sua_os                                                ,
						tbl_os_revenda.obs                                                   ,
						to_char(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS data_abertura  ,
						to_char(tbl_os_revenda.digitacao,'DD/MM/YYYY')     AS data_digitacao ,
						tbl_revenda.nome  AS revenda_nome                                    ,
						tbl_revenda.cnpj  AS revenda_cnpj                                    ,
						tbl_revenda.fone  AS revenda_fone                                    ,
						tbl_revenda.email AS revenda_email                                   ,
						tbl_posto_fabrica.codigo_posto AS posto_codigo                       ,
						tbl_posto.nome    AS posto_nome                                      
			FROM        tbl_os_revenda
			JOIN        tbl_fabrica          ON tbl_fabrica.fabrica       = tbl_os_revenda.fabrica
			JOIN        tbl_posto            ON tbl_posto.posto           = tbl_os_revenda.posto
			JOIN        tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_posto.posto
											AND tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
			LEFt JOIN   tbl_revenda          ON tbl_os_revenda.revenda    = tbl_revenda.revenda
			WHERE       tbl_os_revenda.os_revenda = $os_revenda
			AND         tbl_os_revenda.posto      = $login_posto
			AND         tbl_os_revenda.fabrica    = $login_fabrica ";
	$res = pg_exec($con, $sql);
	
	if (pg_numrows($res) > 0){
		$sua_os         = pg_result($res,0,sua_os);
		$data_abertura  = pg_result($res,0,data_abertura);
		$data_digitacao = pg_result($res,0,data_digitacao);
		$revenda_nome   = pg_result($res,0,revenda_nome);
		$revenda_cnpj   = pg_result($res,0,revenda_cnpj);
		$revenda_fone   = pg_result($res,0,revenda_fone);
		$revenda_email  = pg_result($res,0,revenda_email);
		$posto_codigo   = pg_result($res,0,posto_codigo);
		$posto_nome     = pg_result($res,0,posto_nome);
		$obs            = pg_result($res,0,obs);
	}else{
		echo "Erro... OS da Revenda não encontrada.";
		exit;
	}
}


$title = "Ordem de Serviço Revenda - Impresso"; 

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
	border-bottom: dotted 0px #a0a0a0;
	border-right: dotted 0px #a0a0a0;
	border-left: dotted 0px #a0a0a0;
	padding: 1px,1px,1px,1px;
	padding-left: 3px;
}

.conteudo {
	font-size: 13px;
	text-align: left;
	background: #ffffff;
	border-right: dotted 0px #a0a0a0;
	border-left: dotted 0px #a0a0a0;
	padding: 1px,1px,1px,1px;
	padding-left: 3px;
}

.texto {
	font-size: 12px;
	background: #ffffff;
}

.borda {
	border: solid 0px #c0c0c0;
}
h2 {
	color: #000000
}

</style>
<style type='text/css' media='print'>
.noPrint {display:none;}
</style> 

<body>

<div class='noPrint'>
<input type=button name='fbBtPrint' value='Versão Jato de Tinta / Laser'
onclick="window.location='os_revenda_blackedecker_total_print.php?os_revenda=<? echo $os_revenda; ?>'">
<br>
<hr class='noPrint'>
</div>

<TABLE width="600px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD><IMG SRC="logos/cabecalho_print_<? echo strtolower ($login_fabrica_nome) ?>.gif" ALT="ORDEM DE SERVIÇO"></TD>
	<TD CLASS='texto' align='right'>VIA BLACK & DECKER</TD>
</TR>
</TABLE>

<br>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo" colspan="4">Informações sobre a Ordem de Serviço - Revenda</TD>
</TR>
<TR>
	<TD class="titulo">OS FABRICANTE</TD>
	<TD class="titulo">DATA DA ABERTURA DA OS</TD>
	<TD class="titulo">DATA DA DIGITAÇÃO DA OS</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $posto_codigo . $sua_os ?></TD>
	<TD class="conteudo"><? echo $data_abertura ?></TD>
	<TD class="conteudo"><? echo $data_digitacao ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">NOME DA REVENDA</TD>
	<TD class="titulo">CNPJ DA REVENDA</TD>
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
	<TD class="titulo">REFERÊNCIA</TD>
	<TD class="titulo">DESCRIÇÃO</TD>
	<TD class="titulo">VOLTAGEM</TD>
	<TD class="titulo">QTDE</TD>
</TR>
<?
	if (strlen($sua_os) > 0){
		// seleciona do banco de dados
		$sql = "SELECT	tbl_produto.referencia        ,
						tbl_produto.descricao         ,
						tbl_produto.voltagem          ,
						COUNT(tbl_os.produto) as total
				FROM	tbl_os 
				JOIN	tbl_produto  ON tbl_produto.produto = tbl_os.produto
				JOIN	tbl_os_extra ON tbl_os_extra.os     = tbl_os.os
				WHERE	tbl_os.sua_os LIKE '$sua_os%'
				AND		tbl_os.posto =   $login_posto
				AND		tbl_os.fabrica = $login_fabrica
				GROUP BY tbl_produto.referencia, 
						tbl_produto.descricao, 
						tbl_produto.voltagem;";
		$res = pg_exec($con, $sql);

		for ($i=0; $i<pg_numrows($res); $i++){
			$produto_referencia = pg_result($res,$i,referencia);
			$produto_descricao  = pg_result($res,$i,descricao);
			$produto_voltagem   = pg_result($res,$i,voltagem);
			$total_produto      = pg_result($res,$i,total);
?>
<TR>
	<TD class="conteudo"><? echo $produto_referencia ?></TD>
	<TD class="conteudo"><? echo $produto_descricao ?></TD>
	<TD class="conteudo"><? echo $produto_voltagem ?></TD>
	<TD class="conteudo"><? echo $total_produto ?></TD>
</TR>
<?
		}
	}
?>
</TABLE>

<br>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">PEÇAS</TD>
</TR>
</TABLE>
<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">REFERÊNCIA</TD>
	<TD class="titulo">DESCRIÇÃO</TD>
	<TD class="titulo">QTDE</TD>
</TR>
<?
	if (strlen($sua_os) > 0){
		// seleciona do banco de dados
		$sql = "SELECT  sum(tbl_os_item.qtde) as total,
						tbl_peca.referencia          ,
						tbl_peca.descricao
				FROM    tbl_os_item
				JOIN    tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				JOIN    tbl_os         ON tbl_os_produto.os         = tbl_os.os
				JOIN    tbl_os_extra   ON tbl_os_extra.os           = tbl_os.os
				JOIN    tbl_peca       ON tbl_peca.peca             = tbl_os_item.peca
				WHERE   tbl_os.sua_os LIKE '$sua_os%'
				AND     tbl_os.posto =   $login_posto
				AND     tbl_os.fabrica = $login_fabrica
				GROUP BY tbl_peca.referencia, tbl_peca.descricao
				ORDER BY sum(tbl_os_item.qtde) desc;";
		$res = pg_exec($con, $sql);
		
		for ($i=0; $i<pg_numrows($res); $i++){
			$peca_referencia = pg_result($res,$i,referencia);
			$peca_descricao  = pg_result($res,$i,descricao);
			$total_peca      = pg_result($res,$i,total);
?>
<TR>
	<TD class="conteudo"><? echo $peca_referencia ?></TD>
	<TD class="conteudo"><? echo $peca_descricao ?></TD>
	<TD class="conteudo"><? echo $total_peca ?></TD>
</TR>
<?
		}
	}
?>
</TABLE>

<br>
<?/*
<!-- 
<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">OS'S SEM DATA DE FECHAMENTO</TD>
</TR>
<TR>
	<TD class="conteudo">

	if (strlen($sua_os) > 0){
		// seleciona do banco de dados
		$sql = "SELECT	sua_os 
				FROM	tbl_os 
				WHERE	tbl_os.sua_os ILIKE '$sua_os-%' 
				AND		tbl_os.fabrica = $login_fabrica 
				AND		data_fechamento IS NULL;";
		$res = pg_exec($con, $sql);

		for ($i=0; $i<pg_numrows($res); $i++){
			$sua_os = pg_result($res,$i,sua_os);
			echo $sua_os ." , ";
		}
	}

	</TD>
</TR>
</TABLE>

<BR>
 -->
 */
 ?>
<TABLE width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD colspan="2" class='conteudo'><h2>Em: <? echo $data_abertura ?></h2></TD>
</TR>
<TR>
	<TD class='conteudo'><h2><? echo $posto_nome ?></h2></TD>
	<TD class='conteudo'><h2>Assinatura:  _________________________________________</h2></TD>
</TR>
<TR>
	<TD class='conteudo'><h2><? echo $revenda_nome ?></h2></TD>
	<TD class='conteudo'><h2>Assinatura:  _________________________________________</h2></TD>
</TR>

</TABLE>

<br><br>

<script language="JavaScript">
	window.print();
</script>

</BODY>
</html>

