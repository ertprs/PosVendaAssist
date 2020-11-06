<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

?>
<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
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
	background-color: #D9E2EF
}
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
</style>

<?

	$comunicado = $_GET['comunicado'];

	$sql= "SELECT 
	tbl_comunicado.comunicado,
	tbl_comunicado.descricao,
	tbl_comunicado.mensagem,
	TO_CHAR(tbl_comunicado.data,'dd/mm/yyyy') AS data 
	FROM tbl_comunicado 
	WHERE tbl_comunicado.comunicado = $comunicado AND
	tbl_comunicado.fabrica = $login_fabrica";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
			$comunicado         = pg_result($res,0,comunicado);
			$descricao          = pg_result($res,0,descricao);
			$mensagem           = pg_result($res,0,mensagem);
			$data               = pg_result($res,0,data);

		echo "<TABLE width='100%' border='0' cellpadding='0' cellspancing='0'>";
		echo "<TR>";
			echo "<TD class='menu_top'colspan='4'>Comunicado</TD>";
		echo "</TR>";
		echo "<TR>";
			echo "<TD class='Titulo'>Descrição</TD>";
			echo "<TD class='table_line'>$descricao</TD>";
			echo "<TD class='Titulo'>Data</TD>";
			echo "<TD class='table_line'>$data</TD>";
		echo "</TR>";
		echo "<TR>";
			echo "<TD class='Titulo'>Mensagem</TD>";
			echo "<TD class='table_line'colspan='3'>$mensagem</TD>";
		echo "</TR>";
		echo "</TABLE>";
	}

?>