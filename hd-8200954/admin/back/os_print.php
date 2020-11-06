<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';


#------------ Le OS da Base de dados ------------#
$os = $HTTP_GET_VARS['os'];
if (strlen ($os) > 0) {
	$sql = "SELECT  tbl_os.sua_os                                                  ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura  ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
					tbl_os.consumidor_nome                                         ,
					tbl_os.consumidor_cidade                                       ,
					tbl_os.consumidor_fone                                         ,
					tbl_os.consumidor_estado                                       ,
					tbl_os.revenda_cnpj                                            ,
					tbl_os.revenda_nome                                            ,
					tbl_os.nota_fiscal                                             ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf        ,
					tbl_os.defeito_reclamado                                       ,
					tbl_os.defeito_reclamado_descricao                             ,
					tbl_os.acessorios                                              ,
					tbl_os.aparencia_produto                                        
			FROM    tbl_os
			WHERE   tbl_os.os = $os";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$sua_os				= pg_result ($res,0,sua_os);
		$data_abertura		= pg_result ($res,0,data_abertura);
		$data_fechamento	= pg_result ($res,0,data_fechamento);
		$consumidor_nome	= pg_result ($res,0,consumidor_nome);
		$consumidor_cidade	= pg_result ($res,0,consumidor_cidade);
		$consumidor_fone	= pg_result ($res,0,consumidor_fone);
		$consumidor_estado	= pg_result ($res,0,consumidor_estado);
		$revenda_cnpj		= pg_result ($res,0,revenda_cnpj);
		$revenda_nome		= pg_result ($res,0,revenda_nome);
		$nota_fiscal		= pg_result ($res,0,nota_fiscal);
		$data_nf			= pg_result ($res,0,data_nf);
		$defeito_reclamado	= pg_result ($res,0,defeito_reclamado);
		$aparencia_produto	= pg_result ($res,0,aparencia_produto);
		$acessorios			= pg_result ($res,0,acessorios);
		$defeito_reclamado_descricao = pg_result ($res,0,defeito_reclamado_descricao);
	}
}



$title = "Ordem de Serviço Balcão - Impresso";
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

<body>
	<div id="container" style='text-align: center'>
		<IMG SRC="logos/cabecalho_print_<? echo strtolower ($login_fabrica_nome) ?>.gif" ALT="">
	</div>

<div id="container">
	<div id="page">
		<h2>Informações sobre a Ordem de Serviço

		<div id="contentcenter" style="width: 650px;">
			<div id="contentleft2" style="width: 90px; ">
				OS FABRICANTE
			</div>
			<div id="contentleft2" style="width: 90px; ">
				DATA ABERT.
			</div>
			<div id="contentleft2" style="width: 80px; ">
				REF.
			</div>
			<div id="contentleft2" style="width: 320px; ">
				DESCRIÇÃO
			</div>
			<div id="contentleft2" style="width: 50px; ">
				SÉRIE
			</div>

		</div>

		<div id="contentcenter" style="width: 650px;">
			<div id="contentleft" style="width: 90px; ">
				<? echo $sua_os ?>
			</div>
			<div id="contentleft" style="width: 90px; ">
				<? echo $data_abertura ?>
			</div>
			<div id="contentleft" style="width: 80px; ">
				<? echo $referencia ?>
			</div>
			<div id="contentleft" style="width: 320px; ">
				<? echo $descricao ?>
			</div>
			<div id="contentleft" style="width: 50px; ">
				<? echo $serie ?>
			</div>

		</div>

</div>

<!-- ------------- INFORMAÇÕES DO CONSUMIDOR ------------------ -->
<div id="container">
	<div id="page">
		<h2>Informações sobre o CONSUMIDOR

		<div id="contentcenter" style="width: 650px;">
			<div id="contentleft2" style="width: 240px; ">
				NOME DO CONSUMIDOR
			</div>
			<div id="contentleft2" style="width: 150px; ">
				CIDADE
			</div>
			<div id="contentleft2" style="width: 80px; ">
				ESTADO
			</div>
			<div id="contentleft2" style="width: 120px; ">
				FONE
			</div>
		</div>
		
		<div id="contentcenter" style="width: 600px;">
			<div id="contentleft" style="width: 240px; ">
				<? echo $consumidor_nome ?>
			</div>
			<div id="contentleft" style="width: 150px; ">
				<? echo $consumidor_cidade ?>
			</div>
			<div id="contentleft" style="width: 80px; ">
				<? echo $consumidor_estado ?>
			</div>
			<div id="contentleft" style="width: 120px; ">
				<? echo $consumidor_fone ?>
			</div>
		</div>
		</h2>
	</div>
</div>

<!-- ------------- INFORMAÇÕES DO DEFEITO ------------------ -->

<div id="container">
	<div id="page">
		<h2>Defeito Apresentado pelo Cliente
		<div id="contentcenter" style="width: 600px;">
			<div id="contentleft" style="width: 600px; ">
				<? echo $defeito_reclamado_descricao ?>
			</div>
		</div>
		</h2>
	</div>
</div>

<div id="container">
	<div id="page">
		<h2>Aparência Geral do Produto
		<div id="contentcenter" style="width: 600px;">
			<div id="contentleft" style="width: 600px; ">
				<? echo $aparencia_produto ?>
			</div>
		</div>
		</h2>
	</div>
</div>
<div id="container">
	<div id="page">
		<h2>Acessórios Deixados pelo Cliente
		<div id="contentcenter" style="width: 600px;">
			<div id="contentleft" style="width: 600px; ">
				<? echo $acessorios ?>
			</div>
		</div>
		</h2>
	</div>
</div>
<div id="container">
	<div id="page">
			<H3>
				<li>Os Equipamentos não retirados num prazo de 90 dias serão remetidos para sucata. A retirada do Lacre de Serviço invalida a <br> GARANTIA DE MANUTENÇÃO DE 90 DIAS.</li>
				<li>O Serviço Autorizado <? echo $login_fabrica_nome ?>, não se responsabiliza pela origem dos equipamentos deixados para manutenção, ficando total <br> responsabilidade a cargo do cliente portador do equipamento.</li>
			</H3>
	</div>
</div>


	<div id="container" style='text-align: center'>
		<IMG SRC="imagens_admin/cabecalho_os_corte.gif" ALT="">
	</div>

<div id="container">
	<div id="page">
		<h2>Informações sobre a Ordem de Serviço

		<div id="contentcenter" style="width: 650px;">
			<div id="contentleft2" style="width: 90px; ">
				OS FABRICANTE
			</div>
			<div id="contentleft2" style="width: 90px; ">
				DATA ABERT.
			</div>
			<div id="contentleft2" style="width: 80px; ">
				REF.
			</div>
			<div id="contentleft2" style="width: 320px; ">
				DESCRIÇÃO
			</div>
			<div id="contentleft2" style="width: 50px; ">
				SÉRIE
			</div>

		</div>

		<div id="contentcenter" style="width: 650px;">
			<div id="contentleft" style="width: 90px; ">
				<? echo $sua_os ?>
			</div>
			<div id="contentleft" style="width: 90px; ">
				<? echo $data_abertura ?>
			</div>
			<div id="contentleft" style="width: 80px; ">
				<? echo $referencia ?>
			</div>
			<div id="contentleft" style="width: 320px; ">
				<? echo $descricao ?>
			</div>
			<div id="contentleft" style="width: 50px; ">
				<? echo $serie ?>
			</div>

		</div>

</div>

<!-- ------------- INFORMAÇÕES DO CONSUMIDOR ------------------ -->
<div id="container">
	<div id="page">
		<h2>Informações sobre o CONSUMIDOR
		<div id="contentcenter" style="width: 650px;">
			<div id="contentleft2" style="width: 240px; ">
				NOME DO CONSUMIDOR
			</div>
			<div id="contentleft2" style="width: 150px; ">
				CIDADE
			</div>
			<div id="contentleft2" style="width: 80px; ">
				ESTADO
			</div>
			<div id="contentleft2" style="width: 120px; ">
				FONE
			</div>
		</div>
		
		<div id="contentcenter" style="width: 600px;">
			<div id="contentleft" style="width: 240px; ">
				<? echo $consumidor_nome ?>
			</div>
			<div id="contentleft" style="width: 150px; ">
				<? echo $consumidor_cidade ?>
			</div>
			<div id="contentleft" style="width: 80px; ">
				<? echo $consumidor_estado ?>
			</div>
			<div id="contentleft" style="width: 120px; ">
				<? echo $consumidor_fone ?>
			</div>
		</div>
		</h2>
	</div>
</div>

<!-- ------------- INFORMAÇÕES DA REVENDA------------------ -->
<div id="container">
	<div id="page">
		<h2>Informações sobre a REVENDA
		<div id="contentcenter" style="width: 600px;">
			<div id="contentleft2" style="width: 170px; ">
				CNPJ REVENDA
			</div>
			<div id="contentleft2" style="width: 150px; ">
				NOME DA REVENDA
			</div>
			<div id="contentleft2" style="width: 170px; ">
				NOTA FISCAL N.
			</div>
			<div id="contentleft2" style="width: 100px; ">
				DATA DA N.F.
			</div>

		</div>
		
		<div id="contentcenter" style="width: 600px;">
			<div id="contentleft" style="width: 170px; ">
				<? echo $revenda_cnpj ?>
			</div>
			<div id="contentleft" style="width: 150px; ">
				<? echo $revenda_nome ?>
			</div>
			<div id="contentleft" style="width: 170px; ">
				<? echo $nota_fiscal ?>
			</div>
			<div id="contentleft" style="width: 100px; ">
				<? echo $data_nf ?>
			</div>

		</div>
</div>

<!-- ------------- INFORMAÇÕES DO DEFEITO ------------------ -->

<div id="container">
	<div id="page">
		<h2>Defeito Apresentado pelo Cliente
		<div id="contentcenter" style="width: 600px;">
			<div id="contentleft" style="width: 600px; ">
				<? echo $defeito_reclamado_descricao ?>
			</div>
		</div>
		</h2>
	</div>
</div>

<div id="container">
	<div id="page">
		<h2>Aparência Geral do Produto
		<div id="contentcenter" style="width: 600px;">
			<div id="contentleft" style="width: 600px; ">
				<? echo $aparencia_produto ?>
			</div>
		</div>
		</h2>
	</div>
</div>
<div id="container">
	<div id="page">
		<h2>Acessórios Deixados pelo Cliente
		<div id="contentcenter" style="width: 600px;">
			<div id="contentleft" style="width: 600px; ">
				<? echo $acessorios ?>
			</div>
		</div>
		</h2>
	</div>
</div>

<div id="container">
	<div id="page">
		<h2>Diagnóstico, Peças usadas e Resolução do Problema:
		<div id="contentcenter" style="width: 600px;">
			<div id="contentleft" style="width: 600px; height: 150px; ">
				<p>Técnico:</p>
				<p><!-- Aqui vai o texto do técnico a mão --></p>
			</div>
		</div>
		</h2>
	</div>
		<h2><? $consumidor_cidade ?>, <? $data_abertura ?>
		<div id="contentcenter" style="width: 600px;">
			<div id="contentleft" style="width: 600px; ">
		<? echo $consumidor_nome ?> --- Assinatura: 
				<!-- Aqui vai o texto do técnico a mão -->
			</div>
		</div>
		</h2>

</div>

<div id="container">
	<IMG SRC="imagens_admin/cabecalho_os_corte.gif" ALT="">
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

<script language="JavaScript">
	window.print();
</script>

</BODY>
</html>

