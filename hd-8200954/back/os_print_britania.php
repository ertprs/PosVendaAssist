<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';


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
					tbl_os.defeito_reclamado
			FROM    tbl_os
			WHERE   tbl_os.os = $os
			AND     tbl_os.posto = $login_posto";
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
	}
}





$title = "Confirmação de Ordem de Serviço";
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

<script language="JavaScript">
	window.print();
</script>

<body>
<div id="container" style='text-align: center'>
		<IMG SRC="imagens/cabecalho_os_<? echo $login_fabrica_nome ?>.gif" ALT="">
</div>


<div id="container">
	<div id="page" style='width: 650px;' style='width: 650px;'>
		<h2>
		Informações sobre a Ordem de Serviço
		<div id="contentcenter" style="width: 600px;">
			<div id="contentleft2" style="width: 190px; ">
				OS FABRICANTE
			</div>
			<div id="contentleft2" style="width: 190px; ">
				DATA DE ABERTURA
			</div>
			<div id="contentleft2" style="width: 190px; ">
				DATA DE FECHAMENTO
			</div>
		</div>
		<div id="contentcenter" style="width: 600px;">
			<div id="contentleft" style="width: 190px; ">
				<? echo $sua_os ?>
			</div>
			<div id="contentleft" style="width: 190px; ">
				<? echo $data_abertura ?>
			</div>
			<div id="contentleft" style="width: 190px; ">
				<? echo $data_fechamento ?>
			</div>
		</div>
		</h2>
	</div>
</div>

<!-- ------------- INFORMAÇÕES DO CONSUMIDOR ------------------ -->
<div id="container">
	<div id="page" style='width: 650px;'>
		<h2>
		Informações sobre o CONSUMIDOR
		<div id="contentcenter" style="width: 600px;">
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
	<div id="page" style='width: 650px;'>
		<h2>
		Informações sobre a REVENDA
		<div id="contentcenter" style="width: 600px;">
			<div id="contentleft2" style="width: 170px; ">
				CNPJ REVENDA
			</div>
			<div id="contentleft2" style="width: 150px; ">
				NOME DA REVENDA
			</div>
			<div id="contentleft2" style="width: 150px; ">
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
			<div id="contentleft" style="width: 150px; ">
				<? echo $nota_fiscal ?>
			</div>
			<div id="contentleft" style="width: 100px; ">
				<? echo $data_nf ?>
			</div>
		</div>
		</h2>
	</div>
</div>

<!-- ------------- INFORMAÇÕES DO DEFEITO ------------------ -->

<div id="container">
	<div id="page" style='width: 650px;'>
		<h2>
		Informações sobre o DEFEITO
		<div id="contentcenter" style="width: 600px;">
			<div id="contentleft2">
				<?
				if (strlen($defeito_reclamado) > 0) {
					$sql = "SELECT tbl_defeito_reclamado.descricao
							FROM   tbl_defeito_reclamado
							WHERE  tbl_defeito_reclamado.defeito_reclamado = $defeito_reclamado";
					$res = pg_exec ($con,$sql);
					
					if (pg_numrows($res) > 0) {
						$descricao_defeito = trim(pg_result($res,0,descricao));
						echo $descricao_defeito;
					}
				}
				?>
			</div>
		</div>
		</h2>
	</div>
</div>




<!-- =========== FINALIZA TELA NOVA============== -->

<div id="container">
	<div id="page" style='width: 650px;'>
		<h2>
		Diagnóstico e Solução do Defeito
		<div id="contentcenter" style="width: 600px;">
			<div id="contentleft2" style="width: 160px;">
				Equipamento
			</div>
			<div id="contentleft2" style="width: 300px; ">
				Componente
			</div>
			<div id="contentleft2" style="width: 90px; ">
				Defeito
			</div>
		</div>
<?
$sql = "SELECT  tbl_produto.referencia,
				tbl_produto.descricao,
				tbl_os_produto.serie,
				tbl_os_produto.versao,
				tbl_os_item.serigrafia,
				tbl_defeito.descricao AS defeito,
				tbl_peca.referencia   AS referencia_peca,
				tbl_peca.descricao    AS descricao_peca
		FROM    tbl_os_produto
		JOIN    tbl_os_item USING (os_produto)
		JOIN    tbl_produto USING (produto)
		JOIN    tbl_peca    USING (peca)
		JOIN    tbl_defeito USING (defeito)
		WHERE   tbl_os_produto.os = $os
		ORDER BY tbl_produto.referencia;";
$res = pg_exec ($con,$sql);

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
?>
	<div id="contentcenter" style="width: 600px;">
		<div id="contentleft2" style="width: 160px;font:75% Tahoma, Verdana, Arial, Helvetica, Sans-Serif">
			<? echo pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao) ?>
		</div>
		<div id="contentleft2" style="width: 300px;font:75% Tahoma, Verdana, Arial, Helvetica, Sans-Serif">
			<? echo pg_result ($res,$i,referencia_peca) . " - " . pg_result ($res,$i,descricao_peca) ?>
		</div>
		<div id="contentleft2" style="width: 90px;font:75% Tahoma, Verdana, Arial, Helvetica, Sans-Serif">
			<? echo pg_result ($res,$i,defeito) ?>
		</div>
	</div>
<?
}
?>

		</h2>
	</div>
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

<div id='container' style='text-align: center'>
	<IMG SRC="imagens/cabecalho_os_corte.gif" ALT="">
</div>

<div id="container" style="width: 600px">
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
<BODY>
