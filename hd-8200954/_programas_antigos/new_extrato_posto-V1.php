<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if ($login_e_distribuidor == 't') {
	header ("Location: new_extrato_distribuidor.php");
	exit;
}


$extrato = (trim ($_POST['extrato']));
if (strlen ($extrato) > 0) {
	header ("Location: new_extrato_posto_devolucao.php?extrato=$extrato");

	exit;
}


$msg_erro = "";

$layout_menu = "os";
$title = "Extratos";

include "cabecalho.php";

?>

<p>
<center>
<font size='+1' face='arial'>Data do Extrato</font>
<?
$sql = "SELECT  tbl_extrato.extrato                                            ,
				date_trunc('day',tbl_extrato.data_geracao)      AS data_extrato,
				to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data        ,
				to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD') AS periodo
		FROM    tbl_extrato
		WHERE   tbl_extrato.posto = $login_posto
		AND     tbl_extrato.fabrica = $login_fabrica
		AND     tbl_extrato.aprovado IS NOT NULL
		AND     tbl_extrato.data_geracao >= '2005-03-30'
		ORDER   BY  to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD') DESC";

$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<form name='frm_extrato' method='post' action='$PHP_SELF'>";
	echo "<select name='extrato' onchange='javascript:frm_extrato.submit()'>\n";
	echo "<option value=''></option>\n";
	
	for ($x = 0 ; $x < pg_numrows($res) ; $x++){
		$aux_extrato = trim(pg_result($res,$x,extrato));
		$aux_data    = trim(pg_result($res,$x,data));
		$aux_extr    = trim(pg_result($res,$x,data_extrato));
		$aux_peri    = trim(pg_result($res,$x,periodo));
		
		if (1==2 AND $login_fabrica == 3 AND $aux_extr > "2005-11-01" AND $login_posto <> 1053 AND $login_posto <> 1789) {
			echo "<option value=''>Calculando</option>\n";
		}else{
			echo "<option value='$aux_extrato'>$aux_data</option>\n";
		}
	}
	
	echo "</select>\n";
	echo "</form>";
}

?>

<p><p>

<? include "rodape.php"; ?>
