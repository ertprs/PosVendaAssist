<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if ($login_e_distribuidor == 't') {
	if ($login_posto <> 4311 AND $login_posto <>725){
		header ("Location: new_extrato_distribuidor.php");
		exit;
	}
}

$msg_erro = "";

$layout_menu = "os";
$title = "Detalhe do Extrato";

include "cabecalho.php";



?>
<p>
<center>

<?
echo "<table width='400' aling='left' border='0' cellspacing='0' cellpadding='0'>";
echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#D7FFE1'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; Reincidências.</b></font></td>";
			echo "</tr>";
echo "</table><BR><BR>";

$extrato = trim($_GET['extrato']);
$linha   = trim($_GET['linha']);
$mounit  = trim($_GET['mounit']);

$sqlg = " SELECT codigo from tbl_extrato_agrupado where extrato = $extrato and aprovado IS NOT NULL";
$resg = pg_query($con,$sqlg);
$ocultar_valor = (pg_num_rows($resg) == 0) ? false : true ;

$sql = "SELECT to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data
			FROM    tbl_extrato
			WHERE   tbl_extrato.extrato = $extrato AND tbl_extrato.posto = $login_posto";
$res = pg_exec ($con,$sql);

echo "<font size='+1' face='arial'>Detalhe do extrato de </font>" ;
echo "<a href='new_extrato_posto_mao_obra_novo.php?extrato=$extrato'>";
echo pg_result ($res,0,data) ;
echo "</a>" ;

echo "<br>";

$sql = "SELECT nome FROM tbl_linha
			WHERE tbl_linha.linha = $linha AND tbl_linha.fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);

$linha_nome = pg_result ($res,0,nome);
echo "<font size='+1' face='arial'>Para a linha <b>$linha_nome</b> </font>" ;

echo "<br>";

if (strlen($mounit) > 0 and $mounit <> 0 AND $ocultar_valor) {
	$unitario = number_format ($mounit,2,",",".");
	echo "<font size='+1' face='arial'>Com mão-de-obra de <b>R$ $unitario </b> </font>" ;
}
$sql = "SELECT  tbl_os.os              ,
				tbl_os.sua_os          ,
				tbl_os.produto         ,
				tbl_os.consumidor_nome ,
				tbl_os.revenda_nome    ,
				tbl_os.os_reincidente  ,
				tbl_os_extra.mao_de_obra_desconto,
				tbl_produto.referencia AS produto_referencia,
				tbl_produto.descricao  AS produto_descricao ,
				tbl_os.serie,
				tbl_os.nota_fiscal,
				to_char (tbl_os.data_abertura,'DD/MM/YYYY') AS abertura ,
				to_char (tbl_os.data_digitacao,'DD/MM/YYYY') AS digitacao ,
				to_char (tbl_os.data_nf,'DD/MM/YYYY') AS data_nf ,
				to_char (tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento,
				tbl_os.sinalizador
		FROM    tbl_os 
		JOIN    tbl_os_extra ON tbl_os.os = tbl_os_extra.os
		JOIN    tbl_produto  ON tbl_os.produto = tbl_produto.produto
		WHERE   tbl_os.posto = $login_posto
		AND     tbl_os_extra.extrato = $extrato
		AND     tbl_os_extra.linha = $linha
		AND     tbl_os_extra.mao_de_obra = $mounit
		ORDER BY tbl_os.sua_os";
$res = pg_exec ($con,$sql);



echo "<table width='600' aling='center' border='0' cellspacing='2'>";

echo "<tr bgcolor='#FF9900' style='font-size:12px ; color:#ffffff ; font-weight:bold ' >";
if ($_SERVER['REMOTE_ADDR'] == '201.0.9.216') {
	echo "<td align='center' nowrap >#</td>";
}
echo "<td align='center' nowrap >&nbsp;</td>";
echo "<td align='center' nowrap >OS</td>";
echo "<td align='center' nowrap >Série</td>";
echo "<td align='center' nowrap >NF.Compra</td>";
echo "<td align='center' nowrap >Dt.Compra</td>";
echo "<td align='center' nowrap >Digitação</td>";
echo "<td align='center' nowrap >Abertura</td>";
echo "<td align='center' nowrap >Fechamento</td>";
echo "<td align='center' nowrap >Consumidor</td>";
echo "<td align='center' nowrap >Produto</td>";
echo "</tr>";

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	
	$chk = $_POST['chk_'.$i];
	$cor = "#FFFFFF";
	if ($i % 2 == 0) $cor = "#FFEECC";
	//takashi 18-12 HD 854
	$os_reincidente = pg_result ($res,$i,os_reincidente);
	if ($os_reincidente=='t'){ $cor = "#D7FFE1";}
	//takashi 18-12 HD 854
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
        echo "<td align='center' nowrap >" . pg_result ($res,$i,nota_fiscal) . "</td>";
	echo "<td nowrap >" . pg_result ($res,$i,data_nf) . "</td>";
	echo "<td nowrap >" . pg_result ($res,$i,digitacao) . "</td>";
	echo "<td nowrap >" . pg_result ($res,$i,abertura) . "</td>";
	//HD 204146: Fechamento automático de OS
	$sinalizador = pg_result ($res, $i, sinalizador);

	if ($sinalizador == "18") {
		echo "<td nowrap title='Data do Fechamento: " . pg_result ($res,$i,fechamento) . " - FECHAMENTO AUTOMÁTICO' style='color:#FF0000; font-weight: bold;'>F. AUT</td>";
	}
	else {
		echo "<td nowrap >" . pg_result ($res,$i,fechamento) . "</td>";
	}
	if (strlen(pg_result ($res,$i,consumidor_nome)) > 0) {
		echo "<td nowrap >" . pg_result ($res,$i,consumidor_nome) . "</td>";
	}else{
		echo "<td nowrap >" . pg_result ($res,$i,revenda_nome) . "</td>";
	}
	echo "<td nowrap >" . pg_result ($res,$i,produto_referencia) . "-" . pg_result ($res,$i,produto_descricao) . "</td>";
	echo "</tr>";
}

echo "<tr bgcolor='#FF9900' style='font-size:12px ; color:#ffffff ; font-weight:bold ' >";
echo "<td align='center' nowrap colspan='10'>Total - $i OS</td>";
#echo "<td align='center' nowrap colspan='1'></td>";
echo "</tr>";

echo "</table>";



?>

<p><p>

<? include "rodape.php"; ?>
