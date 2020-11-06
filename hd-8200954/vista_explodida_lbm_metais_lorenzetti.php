<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';


$produto = $_GET['produto'];
$sql = "SELECT referencia, descricao FROM tbl_produto WHERE produto = $produto";
$res = pg_exec ($con,$sql);
$referencia = pg_result ($res,0,0) ;
$descricao  = pg_result ($res,0,1) ;

$title = "Lista Básica - $referencia - $descricao";

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
	if (window.detwinUSAM) {
		window.detwinUSAM.close();
		window.detwinUSAM = null;
	}
}

window.detwinUSAM = window.open(href, "Metais-USAM", 'width=650,height=500,scrollbars=yes');
window.detwinUSAM.focus();

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
echo "<td colspan='4' bgcolor='#FF6600' height='25'>Lista de Peças do Produto <br> $referencia - $descricao</td>";
echo "</tr>";

echo "<tr class='titulo' >";
echo "<td background='admin/imagens_admin/azul.gif'>Referência</td>";
echo "<td background='admin/imagens_admin/azul.gif'>Descrição</td>";
echo "<td background='admin/imagens_admin/azul.gif'>Arquivos</td>";
echo "</tr>";

$sql = "SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao FROM tbl_peca JOIN tbl_lista_basica USING (peca) WHERE tbl_lista_basica.produto = $produto ORDER BY tbl_peca.descricao";
$res = pg_exec ($con,$sql);

for ($i = 0; $i < pg_numrows ($res) ; $i++) {
	$peca                  = trim(pg_result ($res,$i,peca));
	$referencia            = trim(pg_result ($res,$i,referencia));
	$descricao             = trim(pg_result ($res,$i,descricao));

	$cor = "#ffffff";
	if ($i % 2 == 0) $cor = '#eeeeff';

	echo "<tr bgcolor='$cor' class='conteudo'>\n";
	echo "<td align='left' nowrap>$referencia </td>";
	echo "<td align='left' nowrap>$descricao </td>";
	echo "<td align='left' nowrap>";

	$gif = "imagens_pecas/$login_fabrica/media/$peca.gif";
	$jpg = "imagens_pecas/$login_fabrica/media/$peca.jpg";
	$pdf = "imagens_pecas/$login_fabrica/media/$peca.pdf";

	if (file_exists($gif) == true) echo "<a href='imagens_pecas/$login_fabrica/media/$peca.gif' target='_blank'>";
	if (file_exists($jpg) == true) echo "<a href='imagens_pecas/$login_fabrica/media/$peca.jpg' target='_blank'>";
	if (file_exists($pdf) == true) echo "<a href='imagens_pecas/$login_fabrica/media/$peca.pdf' target='_blank'><img src='/assist/imagens/icone_pdf.jpg' border='0' width='22' alt='Vista Explodida' title='Vista Explodida' align='absmiddle'></a>";

	$sql = "SELECT * FROM tbl_lista_basica WHERE peca = $peca LIMIT 1";
	$resLBM = pg_exec ($con,$sql);
	if (pg_numrows ($resLBM) > 0) {
		echo "&nbsp;&nbsp;&nbsp;<a href='vista_explodida_produtos_usam_metais_lorenzetti.php?peca=$peca' onClick='return popup(this)'>produtos que usam</a>&nbsp;";
	}

	echo "</td>";

	echo "</tr>";

}
echo "</form>\n";
echo "</table>\n";

?>

</body>
</html>