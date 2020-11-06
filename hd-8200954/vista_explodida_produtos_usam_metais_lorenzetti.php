<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if ($S3_sdk_OK) {
	include_once S3CLASS;

	$s3ve = new anexaS3('ve', (int) $login_fabrica);
	$S3_online = is_object($s3ve);
}

$peca = $_GET['peca'];
$sql = "SELECT referencia, descricao FROM tbl_peca WHERE peca = $peca";
$res = pg_exec ($con,$sql);
$referencia = pg_result ($res,0,0) ;
$descricao  = pg_result ($res,0,1) ;

$title = "Lista Básica Usam - $referencia - $descricao";

?>

<HTML>
<HEAD>
<TITLE><?= $title ?></TITLE>

<SCRIPT TYPE="text/javascript"> 
<!--
function popup(nome_link) {
if (! window.focus)return true;
var href;
if (typeof(nome_link) == 'string') {
	href=nome_link;
}else{
	href=nome_link.href;
}

if (navigator.userAgent.indexOf('Chrome/') > 0) {
	if (window.detwinLBM) {
		window.detwinLBM.close();
		window.detwinLBM = null;
	}
}

window.detwinLBM = window.open(href, "Metais-LBM", 'width=650,height=500,scrollbars=yes');
window.detwinLBM.focus();

return false;
}
//--> 
</SCRIPT> 



<style>
.titulo {
	font-family: Arial;
	font-size: 9pt;
	text-align: center;
	font-weight: bold;
	color: #FFFFFF;
	background: #408BF2;
}
.titulo2 {
	font-family: Arial;
	font-size: 12pt;
	text-align: center;
	font-weight: bold;
	color: #FFFFFF;
	background: #408BF2;
}

.conteudo {
	font-family: Arial;
	FONT-SIZE: 8pt;
	text-align: left;
}
.Tabela{
	border:1px solid #485989;

}
img{
	border: 0px;
}
</style>

</head>

<body>


<?

echo "<table width='600' align='center' class='Tabela' cellspacing='0' cellpadding='3' border='1' style='border-collapse: collapse' bordercolor='#d2e4fc'>";
echo "<tr class='titulo2'>";
echo "<td colspan='4' bgcolor='#FF6600' height='25'>Lista de Produtos que usam a Peça <br> $referencia - $descricao</td>";
echo "</tr>";

echo "<tr class='titulo' >";
echo "<td background='admin/imagens_admin/azul.gif'>Referência</td>";
echo "<td background='admin/imagens_admin/azul.gif'>Descrição</td>";
echo "<td background='admin/imagens_admin/azul.gif'>Arquivos</td>";
echo "</tr>";

$sql = "SELECT tbl_produto.produto, tbl_produto.referencia, tbl_produto.descricao FROM tbl_produto JOIN tbl_lista_basica USING (produto) WHERE tbl_lista_basica.peca = $peca ORDER BY tbl_produto.descricao";
$res = pg_exec ($con,$sql);

for ($i = 0; $i < pg_numrows ($res) ; $i++) {
	$produto               = trim(pg_result ($res,$i,produto));
	$referencia            = trim(pg_result ($res,$i,referencia));
	$descricao             = trim(pg_result ($res,$i,descricao));

	$cor = "#ffffff";
	if ($i % 2 == 0) $cor = '#eeeeff';

	echo "<tr bgcolor='$cor' class='conteudo'>\n";
	echo "<td align='left' nowrap>$referencia </td>";
	echo "<td align='left' nowrap>$descricao </td>";
	echo "<td align='left' nowrap>";

	$sql = "SELECT tbl_comunicado.comunicado FROM tbl_comunicado WHERE tbl_comunicado.produto = $produto AND tbl_comunicado.tipo = 'Vista Explodida'";
	$resCOM = pg_exec ($con,$sql);

	for ($x = 0; $x < pg_numrows ($resCOM) ; $x++) {
		$Xcomunicado = trim(pg_result ($resCOM,$x,comunicado));

		if ($S3_online and ($s3ve->temAnexos($Xcomunicado))) {
			$icone = 'imagens/icone_' . pathinfo($s3ve->attachList[0], PATHINFO_EXTENSION) . '.jpg';
			echo "<a href='" . $s3ve->url . "' target='_blank'>".
				 "<img src='$icone' alt='Vista Explodida' border='0' width='22' title='Vista Explodida' align='absmiddle' /></a>";
		} else {

			$gif = "comunicados/$Xcomunicado.gif";
			$jpg = "comunicados/$Xcomunicado.jpg";
			$pdf = "comunicados/$Xcomunicado.pdf";

			if (file_exists($gif) == true) echo "<a href='comunicados/$Xcomunicado.gif' target='_blank'><img src='/assist/imagens/icone_gif.jpg' border='0' width='22' alt='Vista Explodida' title='Vista Explodida' align='absmiddle'></a>";
			if (file_exists($jpg) == true) echo "<a href='comunicados/$Xcomunicado.jpg' target='_blank'><img src='/assist/imagens/icone_jpg.jpg' border='0' width='22' alt='Vista Explodida' title='Vista Explodida' align='absmiddle'></a>";
			if (file_exists($pdf) == true) echo "<a href='comunicados/$Xcomunicado.pdf' target='_blank'><img src='/assist/imagens/icone_pdf.jpg' border='0' width='22' alt='Vista Explodida' title='Vista Explodida' align='absmiddle'></a>";
		}
	}


	$sql = "SELECT * FROM tbl_lista_basica WHERE produto = $produto LIMIT 1";
	$resLBM = pg_exec ($con,$sql);
	if (pg_numrows ($resLBM) > 0) {
		echo "&nbsp;&nbsp;&nbsp;<a href='vista_explodida_lbm_metais_lorenzetti.php?produto=$produto' onClick='return popup(this)'>lista de peças</a>&nbsp;";
	}

	echo "</td>";

	echo "</tr>";

}
echo "</form>\n";
echo "</table>\n";

?>

</body>
</html>
