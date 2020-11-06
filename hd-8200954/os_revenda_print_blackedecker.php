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
					 tbl_revenda.nome  AS revenda_nome                                    ,
					 tbl_revenda.cnpj  AS revenda_cnpj                                    ,
					 tbl_revenda.fone  AS revenda_fone                                    ,
					 tbl_revenda.email AS revenda_email                                   ,
					 tbl_posto_fabrica.codigo_posto AS posto_codigo                       ,
					 tbl_posto.nome    AS posto_nome                                      
			FROM	 tbl_os_revenda
			LEFT JOIN tbl_revenda ON tbl_os_revenda.revenda = tbl_revenda.revenda
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

.texto {
	font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	background: #ffffff;
}

.borda {
	border: solid 1px #c0c0c0;
}

</style>

<style type='text/css' media='print'>
.noPrint {display:none;}
</style> 

<body>

<div class='noPrint'>
<input type=button name='fbBtPrint' value='Versão Matricial'
onclick="window.location='os_revenda_print_blackedecker_matricial.php?os_revenda=<? echo $os_revenda; ?>'">
<br>
<hr class='noPrint'>
</div>

<TABLE width="600px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD><IMG SRC="logos/cabecalho_print_<? echo strtolower ($login_fabrica_nome) ?>.gif" ALT="ORDEM DE SERVIÇO"></TD>
	<TD CLASS='texto' align='right'>VIA REVENDA</TD>
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
	<TD class="titulo">CÓDIGO FABRICAÇÃO</TD>
	<TD class="titulo">NÚMERO DE SÉRIE</TD>
</TR>

<?
	// monta o FOR
	$qtde_item = 20;

		if ($os_revenda){
			// seleciona do banco de dados
			$sql = "SELECT   tbl_os_revenda_item.os_revenda_item  ,
							 tbl_os_revenda_item.produto          ,
							 tbl_os_revenda_item.serie            ,
							 tbl_os_revenda_item.codigo_fabricacao,
							 tbl_produto.referencia               ,
							 tbl_produto.descricao                ,
							 tbl_produto.voltagem                 
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
				$produto_descricao  = pg_result($res,$i,descricao);
				$produto_voltagem   = pg_result($res,$i,voltagem);
				$produto_serie      = pg_result($res,$i,serie);
				$codigo_fabricacao  = pg_result($res,$i,codigo_fabricacao);
?>
<TR>
	<TD class="conteudo"><? echo $referencia_produto ?></TD>
	<TD class="conteudo"><? echo $produto_descricao ?></TD>
	<TD class="conteudo"><? echo $produto_voltagem ?></TD>
	<TD class="conteudo"><? echo $codigo_fabricacao ?></TD>
	<TD class="conteudo"><? echo $produto_serie ?></TD>
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
</TABLE>

<br><br>


<script language="JavaScript">
	window.print();
</script>

<br><br><br>

<TABLE width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class='conteudo'><h2><A HREF="os_revenda_blackedecker_total_print.php?os_revenda=<? echo $os_revenda; ?>">IMPRIMIR SIMPLIFICADO</A></h2></TD>
</TR>
</TABLE>

</BODY>
</html>
