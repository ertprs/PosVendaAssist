<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro";
include "autentica_admin.php";


$msg_erro = "";

$layout_menu = "financeiro";
$title = "Consulta e Manutenção de Extratos do Posto";

include "cabecalho.php";

?>

<p>
<center>
<style type='text/css'>
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
</style>
<?
$posto  = trim ($_GET['posto']);
$extrato = trim($_GET['extrato']);
$linha   = trim($_GET['linha']);
$mounit  = trim($_GET['mounit']);

echo "<div style='width:700px;' class='texto_avulso'>";
$sql = "SELECT to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data
			FROM    tbl_extrato
			WHERE   tbl_extrato.extrato = $extrato AND tbl_extrato.posto = $posto";
$res = pg_exec ($con,$sql);

echo "<font size='+1' face='arial'>Detalhe do extrato de </font>" ;
echo "<a href='extrato_posto_britania.php?extrato=$extrato'>";
echo pg_result ($res,0,data) ;
echo "</a>" ;

echo "<br>";

$sql = "SELECT tbl_posto_fabrica.codigo_posto, tbl_posto.nome
		FROM tbl_posto_fabrica
		JOIN tbl_posto USING (posto)
		JOIN tbl_extrato ON tbl_extrato.fabrica = tbl_posto_fabrica.fabrica AND tbl_extrato.posto = tbl_posto_fabrica.posto
		WHERE tbl_extrato.extrato = $extrato";
$resX = pg_exec ($con,$sql);

echo @pg_result ($resX,0,codigo_posto) . " - " . @pg_result ($resX,0,nome);


echo "<br>";



$sql = "SELECT nome FROM tbl_linha
			WHERE tbl_linha.linha = $linha AND tbl_linha.fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);

$linha_nome = pg_result ($res,0,nome);
echo "<font size='+1' face='arial'>Para a linha <b>$linha_nome</b> </font>" ;

echo "<br>";

$unitario = number_format ($mounit,2,",",".");
echo "<font size='+1' face='arial'>Com mão-de-obra de <b>R$ $unitario </b> </font>" ;
echo "</div> <br />";
$sql = "SELECT  tbl_os.os              ,
				tbl_os.sua_os          ,
				tbl_os.produto         ,
				tbl_os.consumidor_nome ,
				tbl_os.revenda_nome    ,
				tbl_produto.referencia AS produto_referencia,
				tbl_produto.descricao  AS produto_descricao ,
				tbl_os.serie,
				tbl_os_extra.mao_de_obra_desconto,
				to_char (tbl_os.data_abertura,'DD/MM/YYYY') AS abertura ,
				to_char (tbl_os.data_digitacao,'DD/MM/YYYY') AS digitacao ,
				to_char (tbl_os.data_nf,'DD/MM/YYYY') AS data_nf ,
				to_char (tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento,
				tbl_os.nota_fiscal
		FROM    tbl_os 
		JOIN    tbl_os_extra ON tbl_os.os = tbl_os_extra.os
		JOIN    tbl_produto  ON tbl_os.produto = tbl_produto.produto
		WHERE   tbl_os.posto = $posto
		AND     tbl_os_extra.extrato = $extrato
		AND     tbl_os_extra.linha = $linha
		AND     tbl_os_extra.mao_de_obra = $mounit
		ORDER BY tbl_os.sua_os";
$res = pg_exec ($con,$sql);

echo "<table width='700' aling='center' border='0' cellspacing='1' class='tabela'>";

echo "<tr class='titulo_coluna' >";
if ($_SERVER['REMOTE_ADDR'] == '201.0.9.216') {
	echo "<td align='center' nowrap >#</td>";
}
echo "<td align='center' nowrap >&nbsp;</td>";
echo "<td align='center' nowrap >OS</td>";
echo "<td align='center' nowrap >Série</td>";
echo "<td align='center' nowrap >NF.Compra</td>";
echo "<td align='center' nowrap >Digitação</td>";
echo "<td align='center' nowrap >Abertura</td>";
echo "<td align='center' nowrap >Fechamento</td>";
echo "<td align='center' nowrap >Consumidor</td>";
echo "<td align='center' nowrap >Produto</td>";
echo "<td align='center' nowrap >MO</td>";
echo "</tr>";

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

	$chk = $_POST['chk_'.$i];
	$cor = "#F7F5F0";
	if ($i % 2 == 0) $cor = "#F1F4FA";

	echo "<tr bgcolor='$cor' style='font-size: 10px'>";
	if ($_SERVER['REMOTE_ADDR'] == '201.0.9.216') {
		echo "<td><input type='checkbox' name='chk_$i' value='ok' ";
		if ($chk == 'ok') echo ' checked ';
		echo "></td>";
	}

	echo "<td>";
	if (pg_result ($res,$i,mao_de_obra_desconto) > 0) echo "<b><font color='#ff6666' size='+1'><a title='OS Recusada'> X </a></font></b>";
	echo "</td>";

	echo "<td nowrap ><a href='os_press.php?os=" . pg_result ($res,$i,os) . "'>" . pg_result ($res,$i,sua_os) . "</a></td>";
	echo "<td nowrap >" . pg_result ($res,$i,serie) . "</td>";
	echo "<td nowrap align='center'>" . pg_result ($res,$i,nota_fiscal) . "</td>";
	echo "<td nowrap >" . pg_result ($res,$i,digitacao) . "</td>";
	echo "<td nowrap >" . pg_result ($res,$i,abertura) . "</td>";
	echo "<td nowrap >" . pg_result ($res,$i,fechamento) . "</td>";
	if (strlen(pg_result ($res,$i,consumidor_nome)) > 0) {
		echo "<td nowrap align='left'>&nbsp;" . pg_result ($res,$i,consumidor_nome) . "</td>";
	}else{
		echo "<td nowrap >" . pg_result ($res,$i,revenda_nome) . "</td>";
	}
	echo "<td nowrap align='left'>&nbsp;" . pg_result ($res,$i,produto_referencia) . "-" . pg_result ($res,$i,produto_descricao) . "</td>";
	if (pg_result ($res,$i,mao_de_obra_desconto) > 0) {
		$xmounit = 0;
	}else{
		$xmounit = $mounit;
	}
	echo "<td nowrap >".number_format($xmounit,2,',','.')."</td>";
	echo "</tr>";
}

echo "<tr class='titulo_coluna' >";
echo "<td align='center' nowrap colspan='10'>Total - $i OS</td>";
echo "</tr>";

echo "</table>";



?>

<p><p>

<? include "rodape.php"; ?>