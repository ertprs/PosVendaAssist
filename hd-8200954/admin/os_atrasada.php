<?

include "dbconfig.php";
include "includes/dbconnect-inc.php";

include "funcoes.php";

$admin_privilegios = "gerencia,call_center";
include "autentica_admin.php";

$posto= $_GET[posto];
$nome= $_GET[nome];


?>
<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10 px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-weight: normal;
	font-size: 10px;
	background-color:#eeeeee
}
</style>

<?

$sql =	"SELECT tbl_os.os                                                  ,
				tbl_os.sua_os                                              ,
				LPAD(tbl_os.sua_os,10,'0')                   AS os_ordem   ,
				tbl_os.posto                                               ,
				TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura   ,
				tbl_produto.referencia                                     ,
				tbl_produto.descricao                                      ,
				tbl_produto.voltagem                                       
		FROM tbl_os
		JOIN tbl_produto    ON tbl_produto.produto    = tbl_os.produto
		WHERE tbl_os.posto = $posto
		AND tbl_os.fabrica = $login_fabrica
		AND tbl_os.data_abertura BETWEEN CURRENT_DATE - INTERVAL '3 months' AND CURRENT_DATE - INTERVAL '10 days'
		AND tbl_os.excluida IS NOT TRUE
		AND   tbl_os.data_fechamento IS NULL
		ORDER BY tbl_os.data_abertura";
$res = pg_exec($con,$sql);
//echo nl2br($sql) . "<br>" . pg_numrows($res) . "<br>";
if (pg_numrows($res) > 0) {
	echo "<table width='600' border='1' align='center' cellpadding='0' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
	echo "<TR>";
	echo "<TD colspan='4' class='Titulo'><br><B><FONT SIZE='3' >$nome</FONT></B><br>&nbsp;</TD>";
	echo "</TR>";
	echo "<tr class='Titulo' height='15' bgcolor='#91C8FF'>";
	echo "<td colspan='4'>OS SEM DATA DE FECHAMENTO HÁ 10 DIAS OU MAIS DA DATA DE ABERTURA</td>";
	echo "</tr>";
	echo "<tr class='Titulo' height='15' bgcolor='#91C8FF'>";

	echo "<td>OS</td>";
	echo "<td>ABERTURA</td>";
	echo "<td>REFERENCIA</td>";
	echo "<td>DESCRIÇÃO</td>";
	echo "</tr>";
	for ($a = 0 ; $a < pg_numrows($res) ; $a++) {
		$os               = trim(pg_result($res,$a,os));
		$sua_os           = $login_codigo_posto . trim(pg_result($res,$a,sua_os));
		$abertura         = trim(pg_result($res,$a,abertura));
		$referencia       = trim(pg_result($res,$a,referencia));
		$descricao        = trim(pg_result($res,$a,descricao));
		$voltagem         = trim(pg_result($res,$a,voltagem));
		$posto            = trim(pg_result($res,$a,posto));
		//$produto_completo = $referencia . " - " . $descricao . " - " . $voltagem;

		$cor = ($a % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

		echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";

		echo "<td >&nbsp;" . $sua_os . "</td>";
		echo "<td >&nbsp;" . $abertura . "</td>";
		echo "<td >&nbsp;" . $referencia . "</td>";
		echo "<td >&nbsp;<acronym title='Referência: $referencia\nDescrição: $descricao\nVoltagem: $voltagem' style='cursor:help;'>" . substr($descricao,0,30) . "</acronym></td>";
		echo "</tr>";
	}
	echo "</table>";
	echo "<br>";
}
?>