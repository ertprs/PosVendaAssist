<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$msg_erro = "";
$msg = "";


$layout_menu = "gerencia";
$titulo = "Auto Credenciamento";
$title = "Auto Credenciamento";

include 'cabecalho.php';
?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}
.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #f5f5f5
}
.table_line2 {
	text-align: left;
	background-color: #fcfcfc
}
.ok {
	text-align: left;
	background-color: #f5f5f5;
	border:1px solid gray;
	font-size:12px;
	font-weight:bold;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
}

</style>

<?

$sql = "SELECT  tbl_posto_fabrica.posto                                      ,
				tbl_posto_fabrica.codigo_posto                               ,
				tbl_posto.nome                                               ,
				to_char(tbl_posto_fabrica.contrato,'dd/mm/yyyy HH24:MI') AS contrato_data      ,
				tbl_posto_fabrica.contato_nome                               ,
				tbl_posto_fabrica.contato_atendentes
			FROM    tbl_posto_fabrica
			JOIN    tbl_posto using(posto)
			WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
			AND     (tbl_posto_fabrica.contrato > '2008-04-25 14:50:00' OR tbl_posto_fabrica.contato_atendentes is not null)
			ORDER BY contrato DESC;";

$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0){
	echo "<br><br><table width='700' align='center' border='0'>";
	echo "<tr>";
	echo "<td align='center' class='menu_top' nowrap>Código</td>";
	echo "<td align='center' class='menu_top' nowrap>Posto</td>";
	echo "<td align='center' class='menu_top' nowrap>Baixou contrato em...</td>";
	echo "<td align='center' class='menu_top' nowrap>Contato</td>";
	echo "<td align='center' class='menu_top' nowrap>Atendentes</td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

		$posto               = trim(pg_result ($res,$i,posto));
		$codigo_posto        = trim(pg_result ($res,$i,codigo_posto));
		$nome                = trim (pg_result ($res,$i,nome));
		$contrato_data       = trim (pg_result ($res,$i,contrato_data));
		$contato_nome        = trim (pg_result ($res,$i,contato_nome));
		$contato_atendentes  = trim (pg_result ($res,$i,contato_atendentes));

		echo "<tr>";

		echo "<td align='left' class='table_line' nowrap>";
		echo $codigo_posto;
		echo "</td>";
		
		echo "<td align='left' class='table_line' nowrap>";
		echo $nome;
		echo "</td>";

		echo "<td class='table_line' align='center' nowrap>";
		echo $contrato_data;
		echo "</td>";

		echo "<td align='left' class='table_line' nowrap>";
		echo $contato_nome;
		echo "</td>";

		echo "<td align='left' class='table_line' nowrap>";
		echo $contato_atendentes;
		echo "</td>";

		echo "</tr>";
	}
	echo "<tr>";
	echo "<td colspan='5' align='center' class='menu_top' nowrap>TOTAL: $i</td>";
	echo "</tr>";
	echo "<tr><td colspan='5'><br><FORM METHOD=POST ACTION='$PHP_SELF'><INPUT TYPE='submit' value='Atualizar'></FORM></td></tr>";
	echo "</table><br><br>";
}


include "rodape.php";
?>
