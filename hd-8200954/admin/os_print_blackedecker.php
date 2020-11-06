<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if ($login_fabrica <> 1) {
	header ("Location: menu_callcenter.php");
	exit;
}

#------------ Le OS da Base de dados ------------#
if(!$xPrint) $os = $_REQUEST["os"];

if (strlen ($os) > 0) {
	$sql = "SELECT  tbl_os.sua_os                                                             ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura             ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento           ,
					tbl_os.consumidor_nome                                                    ,
					tbl_os.consumidor_cidade                                                  ,
					tbl_os.consumidor_fone                                                    ,
					tbl_os.consumidor_estado                                                  ,
					tbl_os.consumidor_celular							  ,
					tbl_os.revenda_cnpj                                                       ,
					tbl_os.revenda_nome                                                       ,
					tbl_os.nota_fiscal                                                        ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf                   ,
					tbl_os.defeito_reclamado_descricao                                        ,
					tbl_os.acessorios                                                         ,
					tbl_os.aparencia_produto                                                  ,
					tbl_defeito_reclamado.descricao              AS defeito_reclamado_cliente ,
					tbl_os.consumidor_revenda                                                 ,
					tbl_os.excluida                                                           ,
					tbl_produto.referencia                                                    ,
					tbl_produto.descricao                                                     ,
					tbl_produto.voltagem                                                      ,
					tbl_os.serie                                                              ,
					tbl_os.codigo_fabricacao                                                  ,
					tbl_posto_fabrica.codigo_posto
			FROM    tbl_os
			JOIN    tbl_produto USING (produto)
			JOIN    tbl_posto USING (posto)
			JOIN    tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
									  AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_defeito_reclamado USING (defeito_reclamado)
			WHERE   tbl_os.os = $os";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) == 1) {
		$sua_os                      = pg_result ($res,0,sua_os);
		$data_abertura               = pg_result ($res,0,data_abertura);
		$data_fechamento             = pg_result ($res,0,data_fechamento);
		$consumidor_nome             = pg_result ($res,0,consumidor_nome);
		$consumidor_cidade           = pg_result ($res,0,consumidor_cidade);
		$consumidor_fone             = pg_result ($res,0,consumidor_fone);
		$consumidor_estado           = pg_result ($res,0,consumidor_estado);
		$revenda_cnpj                = pg_result ($res,0,revenda_cnpj);
		$revenda_nome                = pg_result ($res,0,revenda_nome);
		$nota_fiscal                 = pg_result ($res,0,nota_fiscal);
		$data_nf                     = pg_result ($res,0,data_nf);
		$defeito_reclamado           = pg_result ($res,0,defeito_reclamado_cliente);
		$aparencia_produto           = pg_result ($res,0,aparencia_produto);
		$acessorios                  = pg_result ($res,0,acessorios);
		$defeito_reclamado_descricao = pg_result ($res,0,defeito_reclamado_descricao);
		$consumidor_revenda          = pg_result ($res,0,consumidor_revenda);
		$excluida                    = pg_result ($res,0,excluida);
		$referencia                  = pg_result ($res,0,referencia);
		$descricao                   = pg_result ($res,0,descricao);
		$voltagem                    = pg_result ($res,0,voltagem);
		$serie                       = pg_result ($res,0,serie);
		$codigo_fabricacao           = pg_result ($res,0,codigo_fabricacao);
		$codigo_posto                = pg_result ($res,0,codigo_posto);
		$consumidor_celular		     = pg_result ($res,0,consumidor_celular);
	}
}

if (strlen($sua_os) == 0) $sua_os = $os;

if ($consumidor_revenda == 'C'){
	$consumidor_revenda = 'CONSUMIDOR';
}else if ($consumidor_revenda == 'R'){
	$consumidor_revenda = 'REVENDA';
}

$title = "Ordem de Servi�o Balc�o - Impresso";
?>

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
}

.conteudo {
	font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	text-align: left;
	background: #ffffff;
	border-right: dotted 1px #a0a0a0;
	border-left: dotted 1px #a0a0a0;
	padding: 1px,1px,1px,1px;
}

.borda {
	border: solid 1px #c0c0c0;
}


</style>

<html>

<head>

	<title><? echo $title ?></title>

	<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
	<meta http-equiv="Expires"       content="0">
	<meta http-equiv="Pragma"        content="no-cache, public">
	<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
	<meta name      ="Author"        content="Telecontrol Networking Ltda">
	<meta name      ="Generator"     content="na m�o...">
	<meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assist�ncia T�cnica e Fabricantes.">
	<meta name      ="KeyWords"      content="Assist�ncia T�cnica, Postos, Manuten��o, Internet, Webdesign, Or�amento, Comercial, J�ias, Callcenter">

	<link type="text/css" rel="stylesheet" href="css/css_press.css">

</head>

<body>
<TABLE width="600px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD><IMG SRC="logos/logo_black_2016.png" style='width: 200px;' ALT="ORDEM DE SERVI�O"></TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">

<? if ($excluida == "t") { ?>
<TR>
	<TD colspan="7" bgcolor="#FFE1E1" align="center"><h1>ORDEM DE SERVI�O EXCLU�DA</h1></TD>
</TR>
<? } ?>

<TR>
	<TD class="titulo" colspan="7">Informa��es sobre a Ordem de Servi�o</TD>
</TR>
<TR>
	<TD class="titulo">OS FABR.</TD>
	<TD class="titulo">DT ABERT. OS</TD>
	<TD class="titulo">REF.</TD>
	<TD class="titulo">DESCRI��O</TD>
	<TD class="titulo">VOLTAGEM</TD>
	<TD class="titulo">S�RIE</TD>
	<TD class="titulo">C�D. FABRICA��O</TD>
</TR>
<TR>
	<TD class="conteudo">
	<?
		if (strlen($consumidor_revenda) > 0){
			echo $codigo_posto.$sua_os ." - ". $consumidor_revenda;
		}else if (strlen($consumidor_revenda) == 0){
				echo $codigo_posto.$sua_os;
		}
	?>
	</TD>
	<TD class="conteudo"><? echo $data_abertura ?></TD>
	<TD class="conteudo"><? echo $referencia ?></TD>
	<TD class="conteudo"><? echo $descricao ?></TD>
	<TD class="conteudo"><? echo $voltagem ?></TD>
	<TD class="conteudo"><? echo $serie ?></TD>
	<TD class="conteudo"><? echo $codigo_fabricacao ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
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

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">DEFEITO APRESENTADO PELO CLIENTE</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $defeito_reclamado_descricao . "<br>" . strtoupper($defeito_reclamado) ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">APAR�NCIA GERAL DO PRODUTO</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $aparencia_produto ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">ACESS�RIOS DEIXADOS PELO CLIENTE</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $acessorios ?></TD>
</TR>
</TABLE>

<TABLE width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD><IMG SRC="imagens/cabecalho_os_corte.gif" ALT=""></TD> <!--  IMAGEM CORTE -->
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo" colspan="6">Informa��es sobre a Ordem de Servi�o</TD>
</TR>
<TR>
	<TD class="titulo">OS FABR.</TD>
	<TD class="titulo">DT ABERT. OS</TD>
	<TD class="titulo">REF.</TD>
	<TD class="titulo">DESCRI��O</TD>
	<TD class="titulo">VOLTAGEM</TD>
	<TD class="titulo">S�RIE</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $codigo_posto.$sua_os ?></TD>
	<TD class="conteudo"><? echo $data_abertura ?></TD>
	<TD class="conteudo"><? echo $referencia ?></TD>
	<TD class="conteudo"><? echo $descricao ?></TD>
	<TD class="conteudo"><? echo $voltagem ?></TD>
	<TD class="conteudo"><? echo $serie ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
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
<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo" colspan="5">Informa��es sobre a Revenda</TD>
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

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">DEFEITO APRESENTADO PELO CLIENTE</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $defeito_reclamado_descricao . "<br>" . strtoupper($defeito_reclamado) ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">APAR�NCIA GERAL DO PRODUTO</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $aparencia_produto ?></TD>
</TR>
</TABLE>

<TABLE class="borda" width="600px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD class="titulo">ACESS�RIOS DEIXADOS PELO CLIENTE</TD>
</TR>
<TR>
	<TD class="conteudo"><? echo $acessorios ?></TD>
</TR>
</TABLE>

<TABLE width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD><IMG SRC="imagens/cabecalho_os_corte.gif" ALT=""></TD> <!--  IMAGEM CORTE -->
</TR>
</TABLE>

<div id="container">
	<div id="page">
		<h2>Diagn�stico, Pe�as usadas e Resolu��o do Problema:
		<div id="contentcenter" style="width: 600px;">
			<div id="contentleft" style="width: 600px; height: 100px; ">
				<p>T�cnico:</p>
				<p><!-- Aqui vai o texto do t�cnico a m�o --></p>
			</div>
		</div>
		</h2>
	</div>
</div>

<TABLE width="650px" border="0" cellspacing="2" cellpadding="0">
<TR>
	<TD><h2>Em, <? echo $posto_cidade .", ". $data_abertura ?></h2></TD>
</TR>
<TR>
	<TD><h2><? echo $consumidor_nome ?> - Assinatura:</h2></TD>
</TR>
</TABLE>

<div id="container">
	<IMG SRC="imagens/cabecalho_os_corte.gif" ALT="">
</div>


<?
$sql = "SELECT  distinct
				tbl_produto.referencia,
				tbl_produto.descricao
		FROM    tbl_os_produto
		JOIN    tbl_produto USING (produto)
		WHERE   tbl_os_produto.os = $os
		ORDER BY tbl_produto.referencia;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {

?>


<div id="container">
	<div id="contentleft2" style="width: 110px;">
		<div id="page">
			<div id="contentleft2" style="width: 110px;font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;text-align: center;">
				<? echo "<b>$sua_os -". pg_result ($res,0,referencia) . "</b> <br> " . pg_result ($res,0,descricao) ?>
			</div>
		</div>
	</div>
	<div id="contentleft2" style="width: 110px;">
		<div id="page">
			<div id="contentleft2" style="width: 110px;font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;text-align: center;">
				<? echo "<b>$sua_os -". pg_result ($res,0,referencia) . "</b> <br> " . pg_result ($res,0,descricao) ?>
			</div>
		</div>
	</div>
	<div id="contentleft2" style="width: 110px;">
		<div id="page">
			<div id="contentleft2" style="width: 110px;font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;text-align: center;">
				<? echo "<b>$sua_os -". pg_result ($res,0,referencia) . "</b> <br> " . pg_result ($res,0,descricao) ?>
			</div>
		</div>
	</div>
	<div id="contentleft2" style="width: 110px;">
		<div id="page">
			<div id="contentleft2" style="width: 110px;font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;text-align: center;">
				<? echo "<b>$sua_os -". pg_result ($res,0,referencia) . "</b> <br> " . pg_result ($res,0,descricao) ?>
			</div>
		</div>
	</div>
	<div id="contentleft2" style="width: 110px;">
		<div id="page">
			<div id="contentleft2" style="width: 110px;font:70% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;text-align: center;">
				<? echo "<b>$sua_os -". pg_result ($res,0,referencia) . "</b> <br> " . pg_result ($res,0,descricao) ?>
			</div>
		</div>
	</div>
</div>
<? } ?>
</div>

<!-- hd 21896 - Francisco Ambrozio. Inclus�o do laudo t�cnico -->
<?
$sql = "SELECT tbl_laudo_tecnico_os.*
			FROM tbl_laudo_tecnico_os
			WHERE os = $os;";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res) > 0){
?>
	<BR><BR>
	<br style="page-break-before:always">

	<TABLE width="600px" border="0" cellspacing="1" cellpadding="0">
		<TR>
			<TD><IMG SRC="logos/cabecalho_print_<? echo strtolower ($login_fabrica_nome) ?>.gif" ALT="ORDEM DE SERVI�O"></TD>
		</TR>
	</TABLE>

	<TABLE class="borda" width="650px" border="0" cellspacing="2" cellpadding="0">
		<TR>
			<TD class="titulo" colspan="3">LAUDO T�CNICO</TD>
		</TR>
		<TR>
			<TD class="titulo">T�TULO</TD>
			<TD class="titulo">AFIRMATIVA</TD>
			<TD class="titulo">OBSERVA��O</TD>
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
				echo "<td class='conteudo'><CENTER>"; if($afirmativa == 't'){ echo "Sim</CENTER></td>";} else { echo "N�o</CENTER></td>";}
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


<script language="JavaScript">
	<?php if (!$xPrint) {?>
	window.print();
	<?php }?>
</script>


</body>

</html>
