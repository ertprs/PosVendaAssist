<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include "cabecalho.php";

$os = $_GET["os"];

//HD 56418 N�o estava mostrando o n�mero da sua_os
if(strlen($os)>0){
	$sql = "SELECT sua_os FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
	$res = pg_exec($con, $sql);

	if(pg_numrows($res)>0){
		$os = pg_result($res,0,sua_os);
	}
}

echo "<table width='650' border='0' cellpadding='0' cellspacing='0' align='center'>";
	echo "<tr>";
		echo "<td>";
			if($login_fabrica==50){
				echo "<center><strong><br/><br/>A ordem de servi�o $os est� sob auditoria do fabricante, n�o sendo poss�vel lan�ar itens na mesma enquanto estiver nesta condi��o.</center><br/><br/>";
			}else if($login_fabrica==35){
				echo "<center><strong><br/><br/>A ordem de servi�o $os est� sob auditoria do fabricante, n�o	sendo poss�vel alterar os itens na mesma enquanto estiver nesta condi��o.</center><br/><br/>";
			}
?>
	<center>
	<script language="JavaScript">
	document.write("<form name=History>")
	document.write("<input type=button value='Voltar' onClick=history.back(-1)>")
	document.write("</form>");
	</script>
	</center>
<?php
		echo "</td>";
	echo "</tr>";
echo "</table>";
include "rodape.php"

?>